<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Summary Sheet Controller
* @author dev@maarch.org
*/

namespace Resource\controllers;

use Basket\models\BasketModel;
use Contact\models\ContactModel;
use Endroid\QrCode\QrCode;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use Note\models\NoteEntityModel;
use Note\models\NoteModel;
use Parameter\models\ParameterModel;
use Priority\models\PriorityModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Respect\Validation\Validator;
use setasign\Fpdi\Tcpdf\Fpdi;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\AutoCompleteController;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\TextFormatModel;
use SrcCore\models\ValidatorModel;
use Status\models\StatusModel;
use User\models\UserModel;

class SummarySheetController
{
    public function createList(Request $request, Response $response, array $aArgs)
    {
        set_time_limit(240);
        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);

        $errors = ResourceListController::listControl(['groupId' => $aArgs['groupId'], 'userId' => $aArgs['userId'], 'basketId' => $aArgs['basketId'], 'currentUserId' => $currentUser['id']]);
        if (!empty($errors['errors'])) {
            return $response->withStatus($errors['code'])->withJson(['errors' => $errors['errors']]);
        }

        $bodyData = $request->getParsedBody();
        $units    = empty($bodyData['units']) ? [] : $bodyData['units'];

        if (!Validator::arrayType()->notEmpty()->validate($bodyData['resources'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Resources is not set or empty']);
        }

        $bodyData['resources'] = array_slice($bodyData['resources'], 0, 500);
        $basket = BasketModel::getById(['id' => $aArgs['basketId'], 'select' => ['basket_clause', 'basket_res_order', 'basket_name']]);
        $user   = UserModel::getById(['id' => $aArgs['userId'], 'select' => ['user_id']]);

        $whereClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $user['user_id']]);
        $rawResourcesInBasket = ResModel::getOnView([
            'select'    => ['res_id'],
            'where'     => [$whereClause, 'res_view_letterbox.res_id in (?)'],
            'data'      => [$bodyData['resources']]
        ]);
        $allResourcesInBasket = [];
        foreach ($rawResourcesInBasket as $resource) {
            $allResourcesInBasket[] = $resource['res_id'];
        }

        $order = 'CASE res_view_letterbox.res_id ';
        foreach ($bodyData['resources'] as $key => $resId) {
            if (!in_array($resId, $allResourcesInBasket)) {
                return $response->withStatus(403)->withJson(['errors' => 'Resources out of perimeter']);
            }
            $order .= "WHEN {$resId} THEN {$key} ";
        }
        $order .= 'END';

        $select = ['res_id', 'subject', 'alt_identifier'];
        foreach ($units as $unit) {
            $unit = (array)$unit;
            if ($unit['unit'] == 'primaryInformations') {
                $informations = ['admission_date', 'creation_date', 'nature_id', 'doc_date', 'type_label', 'initiator', 'typist'];
                $select = array_merge($select, $informations);
            } elseif ($unit['unit'] == 'secondaryInformations') {
                $informations = ['category_id', 'priority', 'process_limit_date', 'status'];
                $select = array_merge($select, $informations);
            } elseif ($unit['unit'] == 'diffusionList') {
                $informations = ['destination'];
                $select = array_merge($select, $informations);
            }
        }

        $resources = ResModel::getOnView([
            'select'    => $select,
            'where'     => ['res_view_letterbox.res_id in (?)'],
            'data'      => [$bodyData['resources']],
            'orderBy'   => [$order]
        ]);

        $pdf = new Fpdi('P', 'pt');
        $pdf->setPrintHeader(false);

        $tmpIds = [];
        foreach ($resources as $resource) {
            $tmpIds[] = $resource['res_id'];
        }

        // Data for resources
        $data = [];
        foreach ($units as $unit) {
            if ($unit['unit'] == 'notes') {
                $data['notes'] = NoteModel::get([
                    'select'   => ['id', 'note_text', 'user_id', 'creation_date', 'identifier'],
                    'where'    => ['identifier in (?)'],
                    'data'     => [$tmpIds],
                    'order_by' => ['identifier']]);

                $userEntities = EntityModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['entity_id']]);
                $data['userEntities'] = [];
                foreach ($userEntities as $userEntity) {
                    $data['userEntities'][] = $userEntity['entity_id'];
                }
            } elseif ($unit['unit'] == 'opinionWorkflow') {
                $data['listInstancesOpinion'] = ListInstanceModel::get([
                    'select'    => ['item_id', 'process_date', 'res_id'],
                    'where'     => ['difflist_type = ?', 'res_id in (?)'],
                    'data'      => ['AVIS_CIRCUIT', $tmpIds],
                    'orderBy'   => ['listinstance_id']
                ]);
            } elseif ($unit['unit'] == 'visaWorkflow') {
                $data['listInstancesVisa'] = ListInstanceModel::get([
                    'select'    => ['item_id', 'requested_signature', 'process_date', 'res_id'],
                    'where'     => ['difflist_type = ?', 'res_id in (?)'],
                    'data'      => ['VISA_CIRCUIT', $tmpIds],
                    'orderBy'   => ['listinstance_id']
                ]);
            } elseif ($unit['unit'] == 'diffusionList') {
                $data['listInstances'] = ListInstanceModel::get([
                    'select' => ['item_id', 'item_type', 'item_mode', 'res_id'],
                    'where'  => ['difflist_type = ?', 'res_id in (?)'],
                    'data'   => ['entity_id', $tmpIds],
                    'orderBy' => ['listinstance_id']
                ]);
            } elseif ($unit['unit'] == 'senderRecipientInformations') {
                $data['mlbCollExt'] = ResModel::getExt([
                    'select' => ['category_id', 'address_id', 'exp_user_id', 'dest_user_id', 'is_multicontacts', 'res_id'],
                    'where' => ['res_id in (?)'],
                    'data' => [$tmpIds]
                ]);
            }
        }

        foreach ($resources as $resource) {
            SummarySheetController::createSummarySheet($pdf, ['resource' => $resource, 'units' => $units, 'login' => $GLOBALS['userId'], 'data' => $data]);
        }

        $fileContent = $pdf->Output('', 'S');
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "inline; filename=maarch.pdf");

        return $response->withHeader('Content-Type', $mimeType);
    }

    public static function createSummarySheet(Fpdi $pdf, array $args)
    {
        ValidatorModel::notEmpty($args, ['resource', 'login']);
        ValidatorModel::arrayType($args, ['resource', 'units', 'data']);
        ValidatorModel::stringType($args, ['login']);

        $resource = $args['resource'];
        $units    = $args['units'];

        $pdf->AddPage();
        $dimensions     = $pdf->getPageDimensions();
        $widthNoMargins = $dimensions['w'] - $dimensions['rm'] - $dimensions['lm'];
        $bottomHeight   = $dimensions['h'] - $dimensions['bm'];

        $widthMultiCell = $widthNoMargins / 10 * 4.5;
        $widthCell      = $widthNoMargins / 10;
        $widthNotes     = $widthNoMargins / 2;
        $specialWidth   = $widthNoMargins / 4;
        $widthAssignee  = $widthNoMargins / 6;

        $appName = CoreConfigModel::getApplicationName();
        $pdf->SetFont('', 'B', 12);
        $pdf->Cell(0, 20, _SUMMARY_SHEET, 0, 2, 'C', false);
        $pdf->SetY($pdf->GetY() - 20);
        $pdf->SetFont('', '', 8);
        $pdf->Cell(0, 20, "$appName / " . date('d-m-Y'), 0, 2, 'R', false);
        $pdf->Cell(0, 1, $resource['alt_identifier'], 0, 2, 'C', false);

        $pdf->SetY($pdf->GetY() + 15);
        $pdf->SetFont('', 'B', 16);
        $pdf->MultiCell(0, 1, $resource['subject'], 1, 'C', false);

        foreach ($units as $key => $unit) {
            $units[$key] = (array)$unit;
            $unit        = (array)$unit;
            if ($unit['unit'] == 'qrcode') {
                $parameter = ParameterModel::getById(['select' => ['param_value_int'], 'id' => 'QrCodePrefix']);
                $prefix = '';
                if ($parameter['param_value_int'] == 1) {
                    $prefix = 'Maarch_';
                }
                $qrCode = new QrCode($prefix . $resource['res_id']);
                $qrCode->setSize(110);
                $qrCode->setMargin(25);
                $pdf->Image('@'.$qrCode->writeString(), 21, 10, 50, 50);
            }
        }
        foreach ($units as $key => $unit) {
            if ($unit['unit'] == 'primaryInformations') {
                $nature        = ResModel::getNatureLabel(['natureId' => $resource['nature_id']]);
                $nature        = empty($nature) ? '<i>'._UNDEFINED.'</i>' : "<b>{$nature}</b>";
                $admissionDate = TextFormatModel::formatDate($resource['admission_date'], 'd-m-Y');
                $admissionDate = empty($admissionDate) ? '<i>'._UNDEFINED.'</i>' : "<b>{$admissionDate}</b>";
                $creationdate  = TextFormatModel::formatDate($resource['creation_date'], 'd-m-Y');
                $creationdate  = empty($creationdate) ? '<i>'._UNDEFINED.'</i>' : "<b>{$creationdate}</b>";
                $docDate       = TextFormatModel::formatDate($resource['doc_date'], 'd-m-Y');
                $docDate       = empty($docDate) ? '<i>'._UNDEFINED.'</i>' : "<b>{$docDate}</b>";
                if (!empty($resource['initiator'])) {
                    $initiator = EntityModel::getByEntityId(['entityId' => $resource['initiator'], 'select' => ['short_label']]);
                }
                $initiatorEntity = empty($initiator) ? '' : "({$initiator['short_label']})";
                $typist          = UserModel::getLabelledUserById(['login' => $resource['typist']]);
                $doctype         = empty($resource['type_label']) ? '<i>'._UNDEFINED.'</i>' : "<b>{$resource['type_label']}</b>";

                $pdf->SetY($pdf->GetY() + 40);
                if (($pdf->GetY() + 77) > $bottomHeight) {
                    $pdf->AddPage();
                }
                if (!($key == 0 || ($key == 1 && $units[0]['unit'] == 'qrcode'))) {
                    $pdf->SetFont('', 'B', 11);
                    $pdf->Cell(0, 15, $unit['label'], 0, 2, 'L', false);
                    $pdf->SetY($pdf->GetY() + 2);
                }

                $pdf->SetFont('', '', 10);
                $pdf->MultiCell($widthMultiCell, 15, _DOC_DATE . " : {$docDate}", 0, 'L', false, 0, '', '', true, 0, true);
                $pdf->Cell($widthCell, 15, '', 0, 0, 'L', false);
                $pdf->MultiCell($widthMultiCell, 15, _ADMISSION_DATE . " : {$admissionDate}", 0, 'L', false, 1, '', '', true, 0, true);
                $pdf->MultiCell($widthMultiCell, 15, _NATURE . " : {$nature}", 0, 'L', false, 0, '', '', true, 0, true);
                $pdf->Cell($widthCell, 15, '', 0, 0, 'L', false);
                $pdf->MultiCell($widthMultiCell, 15, _CREATED . " : {$creationdate}", 0, 'L', false, 1, '', '', true, 0, true);
                $pdf->MultiCell($widthMultiCell, 15, _DOCTYPE . " : {$doctype}", 0, 'L', false, 0, '', '', true, 0, true);
                $pdf->Cell($widthCell, 15, '', 0, 0, 'L', false);
                $pdf->MultiCell($widthMultiCell, 15, _TYPIST . " : <b>{$typist} {$initiatorEntity}</b>", 0, 'L', false, 1, '', '', true, 0, true);
            } elseif ($unit['unit'] == 'secondaryInformations') {
                $category = ResModel::getCategoryLabel(['categoryId' => $resource['category_id']]);
                $category = empty($category) ? '<i>'._UNDEFINED.'</i>' : "<b>{$category}</b>";
                $status   = StatusModel::getById(['id' => $resource['status'], 'select' => ['label_status']]);
                $status   = empty($status['label_status']) ? '<i>'._UNDEFINED.'</i>' : "<b>{$status['label_status']}</b>";
                $priority = '';
                if (!empty($resource['priority'])) {
                    $priority = PriorityModel::getById(['id' => $resource['priority'], 'select' => ['label']]);
                }
                $priority = empty($priority['label']) ? '<i>'._UNDEFINED.'</i>' : "<b>{$priority['label']}</b>";
                $processLimitDate = TextFormatModel::formatDate($resource['process_limit_date'], 'd-m-Y');
                $processLimitDate = empty($processLimitDate) ? '<i>'._UNDEFINED.'</i>' : "<b>{$processLimitDate}</b>";

                $pdf->SetY($pdf->GetY() + 40);
                if (($pdf->GetY() + 57) > $bottomHeight) {
                    $pdf->AddPage();
                }
                $pdf->SetFont('', 'B', 11);
                $pdf->Cell(0, 15, $unit['label'], 0, 2, 'L', false);
                $pdf->SetY($pdf->GetY() + 2);

                $pdf->SetFont('', '', 10);
                $pdf->MultiCell($widthNotes, 30, _CATEGORY . " : {$category}", 1, 'L', false, 0, '', '', true, 0, true);
                $pdf->MultiCell($widthNotes, 30, _STATUS . " : {$status}", 1, 'L', false, 1, '', '', true, 0, true);
                $pdf->MultiCell($widthNotes, 30, _PRIORITY . " : {$priority}", 1, 'L', false, 0, '', '', true, 0, true);
                $pdf->MultiCell($widthNotes, 30, _PROCESS_LIMIT_DATE . " : {$processLimitDate}", 1, 'L', false, 1, '', '', true, 0, true);
            } elseif ($unit['unit'] == 'senderRecipientInformations') {
                $senders = [];
                $recipients = [];
                foreach ($args['data']['mlbCollExt'] as $mlbKey => $mlbValue) {
                    if ($mlbValue['res_id'] == $resource['res_id']) {
                        $resourcesContacts = ResourceContactModel::getFormattedByResId(['resId' => $resource['res_id']]);
                        
                        foreach ($resourcesContacts as $contactsKey => $value) {
                            $entitiesFormat = '';
                            if ($value['type'] == 'user') {
                                $userEntity = UserModel::getPrimaryEntityById(['id' => $value['item_id']]);
                                if (!empty($userEntity)) {
                                    $entitiesFormat = ' (' . $userEntity['entity_label'] . ')';
                                }
                            }
                            $resourcesContacts[$contactsKey]['format'] = $value['format'] . $entitiesFormat;
                        }
    
                        $oldContacts = [];
                        $rawContacts = [];
                        if ($mlbValue['is_multicontacts'] == 'Y') {
                            $multiContacts = DatabaseModel::select([
                                'select'    => ['contact_id', 'address_id'],
                                'table'     => ['contacts_res'],
                                'where'     => ['res_id = ?', 'mode = ?'],
                                'data'      => [$resource['res_id'], 'multi']
                            ]);
                            foreach ($multiContacts as $multiContact) {
                                $rawContacts[] = [
                                    'login'         => $multiContact['contact_id'],
                                    'address_id'    => $multiContact['address_id'],
                                ];
                            }
                        } else {
                            $rawContacts[] = [
                                'login'         => $mlbValue['dest_user_id'],
                                'address_id'    => $mlbValue['address_id'],
                            ];
                        }
                        foreach ($rawContacts as $rawContact) {
                            if (!empty($rawContact['address_id'])) {
                                $contact = ContactModel::getOnView([
                                    'select' => [
                                        'is_corporate_person', 'lastname', 'firstname', 'address_num', 'address_street', 'address_town', 'address_postal_code',
                                        'ca_id', 'society', 'contact_firstname', 'contact_lastname', 'address_country'
                                    ],
                                    'where' => ['ca_id = ?'],
                                    'data' => [$rawContact['address_id']]
                                ]);
                                if (isset($contact[0])) {
                                    $contact = AutoCompleteController::getFormattedContact(['contact' => $contact[0]]);
                                    $oldContacts[] = ['format' => $contact['contact']['otherInfo']];
                                }
                            } else {
                                $format = UserModel::getLabelledUserById(['login' => $rawContact['login']]);
                                if (!empty($rawContact['login'])) {
                                    $userEntity = UserModel::getPrimaryEntityByUserId(['userId' => $rawContact['login']]);
                                    if (!empty($userEntity)) {
                                        $format .= ' (' . $userEntity['entity_label'] . ')';
                                    }
                                }
                                $oldContacts[] = ['format' => $format];
                            }
                        }
                        if (!empty($oldContacts) && count($oldContacts) > 2) {
                            $nbOldContacts = count($oldContacts);
                            $oldContacts = [];
                            $oldContacts[0]['format'] = $nbOldContacts . ' ' . _CONTACTS;
                        }
                        if (!empty($resourcesContacts) && count($resourcesContacts) > 2) {
                            $nbResourcesContacts = count($resourcesContacts);
                            $resourcesContacts = [];
                            $resourcesContacts[0]['format'] = $nbResourcesContacts . ' ' . _CONTACTS;
                        }
                        if ($mlbValue['category_id'] == 'outgoing') {
                            $senders    = $resourcesContacts;
                            $recipients = $oldContacts;
                        } else {
                            $senders    = $oldContacts;
                            $recipients = $resourcesContacts;
                        }
                        unset($args['data']['mlbCollExt'][$mlbKey]);
                        break;
                    }
                }

                $pdf->SetY($pdf->GetY() + 40);
                if (($pdf->GetY() + 57) > $bottomHeight) {
                    $pdf->AddPage();
                }
                $pdf->SetFont('', 'B', 11);
                $pdf->Cell(0, 15, $unit['label'], 0, 2, 'L', false);
                $pdf->SetY($pdf->GetY() + 2);

                $pdf->SetFont('', '', 10);
                $pdf->Cell($widthMultiCell, 15, _SENDERS, 1, 0, 'C', false);
                $pdf->Cell($widthCell, 15, '', 0, 0, 'C', false);
                $pdf->Cell($widthMultiCell, 15, _RECIPIENTS, 1, 1, 'C', false);
                for ($i = 0; !empty($senders[$i]) || !empty($recipients[$i]); $i++) {
                    if ($i == 0 && empty($senders[$i]['format'])) {
                        $pdf->MultiCell($widthMultiCell, 40, _UNDEFINED, 1, 'L', false, 0, '', '', true, 0, true);
                    } else {
                        $pdf->MultiCell($widthMultiCell, 40, empty($senders[$i]['format']) ? '' : $senders[$i]['format'], empty($senders[$i]['format']) ? 0 : 1, 'L', false, 0, '', '', true, 0, true);
                    }

                    $pdf->MultiCell($widthCell, 40, '', 0, 'L', false, 0, '', '', true, 0, true);

                    if ($i == 0 && empty($recipients[$i]['format'])) {
                        $pdf->MultiCell($widthMultiCell, 40, _UNDEFINED, 1, 'L', false, 1, '', '', true, 0, true);
                    } else {
                        $pdf->MultiCell($widthMultiCell, 40, empty($recipients[$i]['format']) ? '' : $recipients[$i]['format'], empty($recipients[$i]['format']) ? 0 : 1, 'L', false, 1, '', '', true, 0, true);
                    }
                }
            } elseif ($unit['unit'] == 'diffusionList') {
                $assignee    = '';
                $copies      = [];
                $destination = '';
                $found       = false;
                foreach ($args['data']['listInstances'] as $listKey => $listInstance) {
                    if ($found && $listInstance['res_id'] != $resource['res_id']) {
                        break;
                    } elseif ($listInstance['res_id'] == $resource['res_id']) {
                        $item = '';
                        if ($listInstance['item_type'] == 'user_id') {
                            $item = UserModel::getLabelledUserById(['login' => $listInstance['item_id']]);
                        } elseif ($listInstance['item_type'] == 'entity_id') {
                            $item = $listInstance['item_id'];
                            $entity = EntityModel::getByEntityId(['entityId' => $listInstance['item_id'], 'select' => ['short_label']]);
                            if (!empty($entity)) {
                                $item = "{$entity['short_label']} ({$item})";
                            }
                        }
                        if ($listInstance['item_mode'] == 'dest') {
                            $assignee = $item;
                        } else {
                            $copies[] = $item;
                        }
                        unset($args['data']['listInstances'][$listKey]);
                        $found = true;
                    }
                }
                if (!empty($resource['destination'])) {
                    $destination = EntityModel::getByEntityId(['entityId' => $resource['destination'], 'select' => ['short_label']]);
                }
                $destinationEntity = empty($destination) ? '' : "({$destination['short_label']})";

                if (empty($assignee)) {
                    $assignee = _UNDEFINED;
                }
                $pdf->SetY($pdf->GetY() + 40);
                if (($pdf->GetY() + 37 + count($copies) * 20) > $bottomHeight) {
                    $pdf->AddPage();
                }
                $pdf->SetFont('', 'B', 11);
                $pdf->Cell(0, 15, $unit['label'], 0, 2, 'L', false);
                $pdf->SetY($pdf->GetY() + 2);

                $pdf->SetFont('', '', 10);
                $pdf->MultiCell($widthAssignee, 20, _ASSIGNEE, 1, 'C', false, 0, '', '', true, 0, false, true, 20, 'M');
                $pdf->SetFont('', 'B', 10);
                $pdf->Cell($widthAssignee * 5, 20, "- {$assignee} {$destinationEntity}", 1, 1, 'L', false);
                if (!empty($copies)) {
                    $pdf->SetFont('', '', 10);
                    $pdf->MultiCell($widthAssignee, count($copies) * 20, _TO_CC, 1, 'C', false, 0, '', '', true, 0, false, true, count($copies) * 20, 'M');
                    foreach ($copies as $copy) {
                        $pdf->Cell($widthAssignee * 5, 20, "- {$copy}", 1, 2, 'L', false);
                    }
                }
            } elseif ($unit['unit'] == 'visaWorkflow') {
                $users = [];
                $found = false;
                foreach ($args['data']['listInstancesVisa'] as $listKey => $listInstance) {
                    if ($found && $listInstance['res_id'] != $resource['res_id']) {
                        break;
                    } elseif ($listInstance['res_id'] == $resource['res_id']) {
                        $users[] = [
                            'user'  => UserModel::getLabelledUserById(['login' => $listInstance['item_id']]),
                            'mode'  => $listInstance['requested_signature'] ? 'Signataire' : 'Viseur',
                            'date'  => TextFormatModel::formatDate($listInstance['process_date']),
                        ];
                        unset($args['data']['listInstancesVisa'][$listKey]);
                        $found = true;
                    }
                }

                if (!empty($users)) {
                    $pdf->SetY($pdf->GetY() + 40);
                    if (($pdf->GetY() + 37 + count($users) * 20) > $bottomHeight) {
                        $pdf->AddPage();
                    }
                    $pdf->SetFont('', 'B', 11);
                    $pdf->Cell(0, 15, $unit['label'], 0, 2, 'L', false);
                    $pdf->SetY($pdf->GetY() + 2);

                    $pdf->SetFont('', '', 10);
                    $pdf->Cell($specialWidth * 3, 20, _USERS, 1, 0, 'L', false);
                    $pdf->Cell($specialWidth, 20, _ACTION_DATE, 1, 1, 'L', false);
                    foreach ($users as $keyUser => $user) {
                        $pdf->Cell($specialWidth * 3, 20, $keyUser + 1 . ". {$user['user']} ({$user['mode']})", 1, 0, 'L', false);
                        $pdf->Cell($specialWidth, 20, $user['date'], 1, 1, 'L', false);
                    }
                }
            } elseif ($unit['unit'] == 'opinionWorkflow') {
                $users = [];
                $found = false;
                foreach ($args['data']['listInstancesOpinion'] as $listKey => $listInstance) {
                    if ($found && $listInstance['res_id'] != $resource['res_id']) {
                        break;
                    } elseif ($listInstance['res_id'] == $resource['res_id']) {
                        $users[] = [
                            'user'  => UserModel::getLabelledUserById(['login' => $listInstance['item_id']]),
                            'date'  => TextFormatModel::formatDate($listInstance['process_date']),
                        ];
                        unset($args['data']['listInstancesOpinion'][$listKey]);
                        $found = true;
                    }
                }

                if (!empty($users)) {
                    $pdf->SetY($pdf->GetY() + 40);
                    if (($pdf->GetY() + 37 + count($users) * 20) > $bottomHeight) {
                        $pdf->AddPage();
                    }
                    $pdf->SetFont('', 'B', 11);
                    $pdf->Cell(0, 15, $unit['label'], 0, 2, 'L', false);
                    $pdf->SetY($pdf->GetY() + 2);

                    $pdf->SetFont('', '', 10);
                    $pdf->Cell($specialWidth * 3, 20, _USERS, 1, 0, 'L', false);
                    $pdf->Cell($specialWidth, 20, _ACTION_DATE, 1, 1, 'L', false);
                    foreach ($users as $keyUser => $user) {
                        $pdf->Cell($specialWidth * 3, 20, $keyUser + 1 . ". {$user['user']}", 1, 0, 'L', false);
                        $pdf->Cell($specialWidth, 20, $user['date'], 1, 1, 'L', false);
                    }
                }
            } elseif ($unit['unit'] == 'notes') {
                $notes = [];
                $found = false;
                foreach ($args['data']['notes'] as $noteKey => $rawNote) {
                    if ($found && $rawNote['identifier'] != $resource['res_id']) {
                        break;
                    } elseif ($rawNote['identifier'] == $resource['res_id']) {
                        $allowed = false;
                        if ($rawNote['user_id'] == $args['login']) {
                            $allowed = true;
                        } else {
                            $noteEntities = NoteEntityModel::get(['select' => ['item_id'], 'where' => ['note_id = ?'], 'data' => [$rawNote['id']]]);
                            if (!empty($noteEntities)) {
                                foreach ($noteEntities as $noteEntity) {
                                    if (in_array($noteEntity['item_id'], $args['data']['userEntities'])) {
                                        $allowed = true;
                                        break;
                                    }
                                }
                            } else {
                                $allowed = true;
                            }
                        }
                        if ($allowed) {
                            $notes[] = [
                                'user'  => UserModel::getLabelledUserById(['login' => $rawNote['user_id']]),
                                'date'  => TextFormatModel::formatDate($rawNote['creation_date']),
                                'note'  => $rawNote['note_text']
                            ];
                        }
                        unset($args['data']['notes'][$noteKey]);
                        $found = true;
                    }
                }

                if (!empty($notes)) {
                    $pdf->SetY($pdf->GetY() + 40);
                    if (($pdf->GetY() + 80) > $bottomHeight) {
                        $pdf->AddPage();
                    }

                    $pdf->SetFont('', 'B', 11);
                    $pdf->Cell(0, 15, $unit['label'], 0, 2, 'L', false);

                    $pdf->SetY($pdf->GetY() + 2);
                    $pdf->SetFont('', '', 10);

                    foreach ($notes as $note) {
                        if (($pdf->GetY() + 65) > $bottomHeight) {
                            $pdf->AddPage();
                        }
                        $pdf->SetFont('', 'B', 10);
                        $pdf->Cell($widthNotes, 20, $note['user'], 1, 0, 'L', false);
                        $pdf->SetFont('', '', 10);
                        $pdf->Cell($widthNotes, 20, $note['date'], 1, 1, 'L', false);
                        $pdf->MultiCell(0, 40, $note['note'], 1, 'L', false);
                        $pdf->SetY($pdf->GetY() + 5);
                    }
                }
            } elseif ($unit['unit'] == 'freeField') {
                $pdf->SetY($pdf->GetY() + 40);
                if (($pdf->GetY() + 77) > $bottomHeight) {
                    $pdf->AddPage();
                }
                $pdf->SetFont('', 'B', 11);
                $pdf->Cell(0, 15, $unit['label'], 0, 2, 'L', false);

                $pdf->SetY($pdf->GetY() + 2);
                $pdf->Cell(0, 60, '', 1, 2, 'L', false);
            }
        }
    }
}
