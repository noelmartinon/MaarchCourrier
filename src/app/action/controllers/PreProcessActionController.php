<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   Pre Process Action Controller
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace Action\controllers;

use AcknowledgementReceipt\models\AcknowledgementReceiptModel;
use Attachment\models\AttachmentModel;
use Basket\models\BasketModel;
use Basket\models\GroupBasketRedirectModel;
use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Convert\controllers\ConvertPdfController;
use Docserver\models\DocserverModel;
use Doctype\models\DoctypeExtModel;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use ExternalSignatoryBook\controllers\MaarchParapheurController;
use Group\models\GroupModel;
use Parameter\models\ParameterModel;
use Resource\controllers\ResController;
use Resource\controllers\ResourceListController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Shipping\controllers\ShippingTemplateController;
use Shipping\models\ShippingTemplateModel;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use Template\models\TemplateModel;
use User\models\UserEntityModel;
use User\models\UserModel;

class PreProcessActionController
{
    public static function getRedirectInformations(Request $request, Response $response, array $args)
    {
        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);

        $errors = ResourceListController::listControl(['groupId' => $args['groupId'], 'userId' => $args['userId'], 'basketId' => $args['basketId'], 'currentUserId' => $currentUser['id']]);
        if (!empty($errors['errors'])) {
            return $response->withStatus($errors['code'])->withJson(['errors' => $errors['errors']]);
        }

        $basket = BasketModel::getById(['id' => $args['basketId'], 'select' => ['basket_id']]);
        $group = GroupModel::getById(['id' => $args['groupId'], 'select' => ['group_id']]);
        $user = UserModel::getById(['id' => $args['userId'], 'select' => ['user_id']]);

        $keywords = [
            'ALL_ENTITIES'          => '@all_entities',
            'ENTITIES_JUST_BELOW'   => '@immediate_children[@my_primary_entity]',
            'ENTITIES_BELOW'        => '@subentities[@my_entities]',
            'ALL_ENTITIES_BELOW'    => '@subentities[@my_primary_entity]',
            'ENTITIES_JUST_UP'      => '@parent_entity[@my_primary_entity]',
            'MY_ENTITIES'           => '@my_entities',
            'MY_PRIMARY_ENTITY'     => '@my_primary_entity',
            'SAME_LEVEL_ENTITIES'   => '@sisters_entities[@my_primary_entity]'
        ];

        $users = [];
        $allEntities = [];

        foreach (['ENTITY', 'USERS'] as $mode) {
            $entityRedirects = GroupBasketRedirectModel::get([
                'select'    => ['entity_id', 'keyword'],
                'where'     => ['basket_id = ?', 'group_id = ?', 'action_id = ?', 'redirect_mode = ?'],
                'data'      => [$basket['basket_id'], $group['group_id'], $args['actionId'], $mode]
            ]);

            $allowedEntities = [];
            $clauseToProcess = '';
            foreach ($entityRedirects as $entityRedirect) {
                if (!empty($entityRedirect['entity_id'])) {
                    $allowedEntities[] = $entityRedirect['entity_id'];
                } elseif (!empty($entityRedirect['keyword'])) {
                    if (!empty($keywords[$entityRedirect['keyword']])) {
                        if (!empty($clauseToProcess)) {
                            $clauseToProcess .= ', ';
                        }
                        $clauseToProcess .= $keywords[$entityRedirect['keyword']];
                    }
                }
            }

            if (!empty($clauseToProcess)) {
                $preparedClause = PreparedClauseController::getPreparedClause(['clause' => $clauseToProcess, 'login' => $user['user_id']]);
                $preparedEntities = EntityModel::get(['select' => ['entity_id'], 'where' => ['enabled = ?', "entity_id in {$preparedClause}"], 'data' => ['Y']]);
                foreach ($preparedEntities as $preparedEntity) {
                    $allowedEntities[] = $preparedEntity['entity_id'];
                }
            }

            $allowedEntities = array_unique($allowedEntities);

            if ($mode == 'USERS') {
                if (!empty($allowedEntities)) {
                    $users = UserEntityModel::getWithUsers([
                        'select'    => ['DISTINCT users.id', 'users.user_id', 'firstname', 'lastname'],
                        'where'     => ['users_entities.entity_id in (?)', 'status not in (?)'],
                        'data'      => [$allowedEntities, ['DEL', 'ABS']],
                        'orderBy'   => ['lastname', 'firstname']
                    ]);

                    foreach ($users as $key => $user) {
                        $users[$key]['labelToDisplay'] = "{$user['firstname']} {$user['lastname']}";
                        $users[$key]['descriptionToDisplay'] = UserModel::getPrimaryEntityByUserId(['userId' => $user['user_id']])['entity_label'];
                    }
                }
            } elseif ($mode == 'ENTITY') {
                $primaryEntity = UserModel::getPrimaryEntityByUserId(['userId' => $GLOBALS['userId']]);

                $allEntities = EntityModel::get(['select' => ['id', 'entity_id', 'entity_label', 'parent_entity_id'], 'where' => ['enabled = ?'], 'data' => ['Y'], 'orderBy' => ['parent_entity_id']]);
                foreach ($allEntities as $key => $value) {
                    $allEntities[$key]['id'] = $value['entity_id'];
                    $allEntities[$key]['serialId'] = $value['id'];
                    if (empty($value['parent_entity_id'])) {
                        $allEntities[$key]['parent'] = '#';
                        $allEntities[$key]['icon'] = "fa fa-building";
                    } else {
                        $allEntities[$key]['parent'] = $value['parent_entity_id'];
                        $allEntities[$key]['icon'] = "fa fa-sitemap";
                    }
                    $allEntities[$key]['state']['opened'] = false;
                    if (in_array($value['entity_id'], $allowedEntities)) {
                        $allEntities[$key]['allowed'] = true;
                        if ($primaryEntity['entity_id'] == $value['entity_id']) {
                            $allEntities[$key]['state']['opened'] = true;
                            $allEntities[$key]['state']['selected'] = true;
                        }
                    } else {
                        $allEntities[$key]['allowed'] = false;
                        $allEntities[$key]['state']['disabled'] = true;
                    }
                    $allEntities[$key]['text'] = $value['entity_label'];
                }
            }
        }

        $parameter = ParameterModel::getById(['select' => ['param_value_int'], 'id' => 'keepDestForRedirection']);

        return $response->withJson(['entities' => $allEntities, 'users' => $users, 'keepDestForRedirection' => !empty($parameter['param_value_int'])]);
    }

    public function checkAcknowledgementReceipt(Request $request, Response $response, array $aArgs)
    {
        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);

        $errors = ResourceListController::listControl(['groupId' => $aArgs['groupId'], 'userId' => $aArgs['userId'], 'basketId' => $aArgs['basketId'], 'currentUserId' => $currentUser['id']]);
        if (!empty($errors['errors'])) {
            return $response->withStatus($errors['code'])->withJson(['errors' => $errors['errors']]);
        }

        $data = $request->getParsedBody();

        if (!Validator::arrayType()->notEmpty()->validate($data['resources'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Data resources is empty or not an array']);
        }

        $sendList = [];
        $sendEmail = 0;
        $sendPaper = 0;
        $noSendAR = [
            'number'    => 0,
            'list'      => [],
        ];
        $alreadyGenerated = [
            'number'    => 0,
            'list'      => [],
        ];
        $alreadySend = [
            'number'    => 0,
            'list'      => [],
        ];

        $data['resources'] = array_slice($data['resources'], 0, 500);

        $resources = ResModel::get([
            'select' => ['res_id', 'locker_user_id', 'locker_time'],
            'where'  => ['res_id in (?)'],
            'data'   => [$data['resources']]
        ]);

        $resourcesForProcess = [];
        foreach ($resources as $resource) {
            $lock = true;
            if (empty($resource['locker_user_id'] || empty($resource['locker_time']))) {
                $lock = false;
            } elseif ($resource['locker_user_id'] == $currentUser['id']) {
                $lock = false;
            } elseif (strtotime($resource['locker_time']) < time()) {
                $lock = false;
            }
            if (!$lock) {
                $resourcesForProcess[] = $resource['res_id'];
            }
        }
        $data['resources'] = $resourcesForProcess;

        foreach ($data['resources'] as $resId) {
            $ext = ResModel::getExtById(['select' => ['res_id', 'category_id', 'address_id', 'is_multicontacts', 'alt_identifier'], 'resId' => $resId]);

            if (empty($ext)) {
                $noSendAR['number'] += 1;
                $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _DOCUMENT_NOT_FOUND ];
                continue;
            }

            if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['userId']])) {
                $noSendAR['number'] += 1;
                $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _DOCUMENT_OUT_PERIMETER ];
                continue;
            }

            if ($ext['category_id'] != 'incoming') {
                $noSendAR['number'] += 1;
                $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _NOT_INCOMING_CATEGORY ];
                continue;
            }

            //Verify template
            $resource = ResModel::getById(['select' => ['type_id', 'destination'], 'resId' => $resId]);
            $doctype = DoctypeExtModel::getById(['id' => $resource['type_id'], 'select' => ['process_mode']]);

            if (empty($resource['destination'])) {
                $noSendAR['number'] += 1;
                $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _NO_ENTITY];
                continue;
            }

            $entity = EntityModel::getByEntityId(['select' => ['entity_label'], 'entityId' => $resource['destination']]);

            if ($doctype['process_mode'] == 'SVA') {
                $templateAttachmentType = 'sva';
            } elseif ($doctype['process_mode'] == 'SVR') {
                $templateAttachmentType = 'svr';
            } else {
                $templateAttachmentType = 'simple';
            }

            $template = TemplateModel::getWithAssociation([
                'select'    => ['template_content', 'template_path', 'template_file_name'],
                'where'     => ['templates.template_id = templates_association.template_id', 'template_target = ?', 'template_attachment_type = ?', 'value_field = ?'],
                'data'      => ['acknowledgementReceipt', $templateAttachmentType, $resource['destination']]
            ]);

            if (empty($template[0])) {
                $noSendAR['number'] += 1;
                $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _NO_TEMPLATE . ' \'' . $templateAttachmentType . '\' ' . _FOR_ENTITY . ' ' .$entity['entity_label'] ];
                continue;
            }

            $docserver = DocserverModel::getByDocserverId(['docserverId' => 'TEMPLATES', 'select' => ['path_template']]);
            $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $template[0]['template_path']) . $template[0]['template_file_name'];

            //Verify sending
            $acknowledgements = AcknowledgementReceiptModel::get([
                'select'    => ['res_id', 'type', 'format', 'creation_date', 'send_date'],
                'where'     => ['res_id = ?', 'type = ?'],
                'data'      => [$resId, $templateAttachmentType],
            ]);

            if (!empty($acknowledgements)) {
                $sended = 0;
                $generated = 0;

                foreach ($acknowledgements as $acknowledgement) {
                    if (!empty($acknowledgement['creation_date']) && !empty($acknowledgement['send_date'])) {
                        $sended += 1;
                    } elseif (!empty($acknowledgement['creation_date']) && empty($acknowledgement['send_date'])) {
                        $generated += 1;
                    }
                }

                if ($sended > 0) {
                    $alreadySend['number'] += $sended;
                    $alreadySend['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier']];
                }

                if ($generated > 0) {
                    $alreadyGenerated['number'] += $generated;
                    $alreadyGenerated['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier']];
                }
            }

            //Verify associated contact
            $contactsToProcess = [];
            if ($ext['is_multicontacts'] == 'Y') {
                $multiContacts = DatabaseModel::select([
                    'select'    => ['address_id'],
                    'table'     => ['contacts_res'],
                    'where'     => ['res_id = ?', 'mode = ?', 'address_id != ?'],
                    'data'      => [$resId, 'multi', 0]
                ]);
                foreach ($multiContacts as $multiContact) {
                    $contactsToProcess[] = $multiContact['address_id'];
                }
            } else {
                $contactsToProcess[] = $ext['address_id'];
            }
            if (empty($contactsToProcess)) {
                $noSendAR['number'] += 1;
                $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _NO_CONTACT ];
                continue;
            }

            //Verify contact informations
            $email = 0;
            $paper = 0;
            foreach ($contactsToProcess as $contactToProcess) {
                if (empty($contactToProcess)) {
                    $noSendAR['number'] += 1;
                    $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _NO_CONTACT ];
                    continue 2;
                }

                $contact = ContactModel::getByAddressId(['addressId' => $contactToProcess, 'select' => ['email', 'address_street', 'address_town', 'address_postal_code']]);

                if (empty($contact['email']) && (empty($contact['address_street']) || empty($contact['address_town']) || empty($contact['address_postal_code']))) {
                    $noSendAR['number'] += 1;
                    $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _USER_MISSING_INFORMATIONS ];
                    continue 2;
                }

                if (!empty($contact['email'])) {
                    if (empty($template[0]['template_content'])) {
                        $noSendAR['number'] += 1;
                        $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _NO_EMAIL_TEMPLATE . ' \'' . $templateAttachmentType . '\' ' . _FOR_ENTITY . ' ' . $entity['entity_label'] ];
                        continue 2;
                    } else {
                        $email += 1;
                    }
                } elseif (!empty($contact['address_street']) && !empty($contact['address_town']) && !empty($contact['address_postal_code'])) {
                    if (!file_exists($pathToDocument) || !is_file($pathToDocument)) {
                        $noSendAR['number'] += 1;
                        $noSendAR['list'][] = ['resId' => $resId, 'alt_identifier' => $ext['alt_identifier'], 'info' => _NO_PAPER_TEMPLATE . ' \'' . $templateAttachmentType . '\' ' . _FOR_ENTITY . ' ' . $entity['entity_label'] ];
                        continue 2;
                    } else {
                        $paper += 1;
                    }
                }
            }

            if ($email > 0) {
                $sendEmail += $email;
            }
            if ($paper > 0) {
                $sendPaper += $paper;
            }
            if ($email > 0 || $paper > 0) {
                $sendList[] = $resId;
            }
        }

        return $response->withJson(['sendEmail' => $sendEmail, 'sendPaper' => $sendPaper, 'sendList' => $sendList,  'noSendAR' => $noSendAR, 'alreadySend' => $alreadySend, 'alreadyGenerated' => $alreadyGenerated]);
    }

    public function checkExternalSignatoryBook(Request $request, Response $response, array $aArgs)
    {
        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);

        $errors = ResourceListController::listControl(['groupId' => $aArgs['groupId'], 'userId' => $aArgs['userId'], 'basketId' => $aArgs['basketId'], 'currentUserId' => $currentUser['id']]);
        if (!empty($errors['errors'])) {
            return $response->withStatus($errors['code'])->withJson(['errors' => $errors['errors']]);
        }

        $data = $request->getParsedBody();

        $data['resources'] = array_slice($data['resources'], 0, 500);
        if (!ResController::hasRightByResId(['resId' => $data['resources'], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $resources = ResModel::get([
            'select' => ['res_id', 'locker_user_id', 'locker_time'],
            'where'  => ['res_id in (?)'],
            'data'   => [$data['resources']]
        ]);

        $resourcesForProcess = [];
        foreach ($resources as $resource) {
            $lock = true;
            if (empty($resource['locker_user_id'] || empty($resource['locker_time']))) {
                $lock = false;
            } elseif ($resource['locker_user_id'] == $currentUser['id']) {
                $lock = false;
            } elseif (strtotime($resource['locker_time']) < time()) {
                $lock = false;
            }
            if (!$lock) {
                $resourcesForProcess[] = $resource['res_id'];
            }
        }
        $data['resources'] = $resourcesForProcess;

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);

        $errors = [];
        $signatureBookEnabled = '';
        if (!empty($loadedXml)) {
            $config['id'] = (string)$loadedXml->signatoryBookEnabled;
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == $config['id']) {
                    $config['data'] = (array)$value;
                    break;
                }
            }

            $signatureBookEnabled = $config['id'];
            $additionalsInfos = [
                'attachments'   => [],
                'noAttachment'  => []
            ];
            if ($signatureBookEnabled == 'ixbus') {
                // TODO
            } elseif ($signatureBookEnabled == 'iParapheur') {
                // TODO
            } elseif ($signatureBookEnabled == 'fastParapheur') {
                // TODO
            } elseif ($signatureBookEnabled == 'maarchParapheur') {
                if (is_array($data['resources']) && count($data['resources']) == 1) {
                    $resDestination = ResModel::getById([
                        'select'   => ['entities.id'],
                        'table'    => ['entities'],
                        'leftJoin' => ['res_letterbox.destination = entities.entity_id'],
                        'resId'    => $data['resources'][0]
                    ]);
                    $additionalsInfos['destinationId'] = $resDestination['id'];
                } else {
                    $additionalsInfos['destinationId'] = '';
                }

                foreach ($data['resources'] as $resId) {
                    $noAttachmentsResource = ResModel::getExtById(['resId' => $resId, 'select' => ['alt_identifier']]);
                    if (empty($noAttachmentsResource['alt_identifier'])) {
                        $noAttachmentsResource['alt_identifier'] = _UNDEFINED;
                    }

                    // Check attachments
                    $attachments = AttachmentModel::getOnView([
                        'select'    => [
                            'res_id', 'res_id_version', 'title', 'identifier', 'attachment_type',
                            'status', 'typist', 'docserver_id', 'path', 'filename', 'creation_date',
                            'validation_date', 'relation', 'attachment_id_master'
                        ],
                        'where'     => ["res_id_master = ?", "attachment_type not in (?)", "status not in ('DEL', 'OBS', 'FRZ', 'TMP', 'SEND_MASS')", "in_signature_book = 'true'"],
                        'data'      => [$resId, ['converted_pdf', 'print_folder', 'signed_response']]
                    ]);
                    
                    if (empty($attachments)) {
                        $additionalsInfos['noAttachment'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resId, 'reason' => 'noAttachmentInSignatoryBook'];
                    } else {
                        foreach ($attachments as $value) {
                            if (!empty($value['res_id'])) {
                                $resIdAttachment  = $value['res_id'];
                                $collId = 'attachments_coll';
                                $is_version = false;
                            } else {
                                $resIdAttachment  = $value['res_id_version'];
                                $collId = 'attachments_version_coll';
                                $is_version = true;
                            }
                            
                            $adrInfo = ConvertPdfController::getConvertedPdfById(['resId' => $resIdAttachment, 'collId' => $collId, 'isVersion' => $is_version]);
                            if (empty($adrInfo['docserver_id'])) {
                                $additionalsInfos['noAttachment'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resIdAttachment, 'reason' => 'noAttachmentConversion'];
                                break;
                            }
                            $docserverInfo = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
                            if (empty($docserverInfo['path_template'])) {
                                $additionalsInfos['noAttachment'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resIdAttachment, 'reason' => 'docserverDoesNotExists'];
                                break;
                            }
                            $filePath = $docserverInfo['path_template'] . str_replace('#', '/', $adrInfo['path']) . $adrInfo['filename'];
                            if (!is_file($filePath)) {
                                $additionalsInfos['noAttachment'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resIdAttachment, 'reason' => 'fileDoesNotExists'];
                                break;
                            }
                        }
                        $additionalsInfos['attachments'][] = ['res_id' => $resId];
                    }
                }
            } elseif ($signatureBookEnabled == 'xParaph') {
                $userInfos  = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['external_id']]);
                $externalId = json_decode($userInfos['external_id'], true);
                $additionalsInfos['accounts'] = [];
                if (!empty($externalId['xParaph'])) {
                    $additionalsInfos['accounts'] = $externalId['xParaph'];
                }

                foreach ($data['resources'] as $resId) {
                    $noAttachmentsResource = ResModel::getExtById(['resId' => $resId, 'select' => ['alt_identifier']]);
                    if (empty($noAttachmentsResource['alt_identifier'])) {
                        $noAttachmentsResource['alt_identifier'] = _UNDEFINED;
                    }

                    // Check attachments
                    $attachments = AttachmentModel::getOnView([
                        'select'    => [
                            'res_id', 'res_id_version', 'title', 'identifier', 'attachment_type',
                            'status', 'typist', 'docserver_id', 'path', 'filename', 'creation_date',
                            'validation_date', 'relation', 'attachment_id_master'
                        ],
                        'where'     => ["res_id_master = ?", "attachment_type not in (?)", "status not in ('DEL', 'OBS', 'FRZ', 'TMP', 'SEND_MASS')", "in_signature_book = 'true'"],
                        'data'      => [$resId, ['converted_pdf', 'print_folder', 'signed_response']]
                    ]);
                    
                    if (empty($attachments)) {
                        $additionalsInfos['noAttachment'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resId, 'reason' => 'noAttachmentInSignatoryBook'];
                    } else {
                        foreach ($attachments as $value) {
                            if (!empty($value['res_id'])) {
                                $resIdAttachment = $value['res_id'];
                                $collId          = 'attachments_coll';
                                $is_version      = false;
                            } else {
                                $resIdAttachment = $value['res_id_version'];
                                $collId          = 'attachments_version_coll';
                                $is_version      = true;
                            }
                            
                            $adrInfo = ConvertPdfController::getConvertedPdfById(['resId' => $resIdAttachment, 'collId' => $collId, 'isVersion' => $is_version]);
                            if (empty($adrInfo['docserver_id'])) {
                                $additionalsInfos['noAttachment'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resIdAttachment, 'reason' => 'noAttachmentConversion'];
                                break;
                            }
                            $docserverInfo = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
                            if (empty($docserverInfo['path_template'])) {
                                $additionalsInfos['noAttachment'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resIdAttachment, 'reason' => 'docserverDoesNotExists'];
                                break;
                            }
                            $filePath = $docserverInfo['path_template'] . str_replace('#', '/', $adrInfo['path']) . $adrInfo['filename'];
                            if (!is_file($filePath)) {
                                $additionalsInfos['noAttachment'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resIdAttachment, 'reason' => 'fileDoesNotExists'];
                                break;
                            }
                        }
                        $additionalsInfos['attachments'][] = ['res_id' => $resId];
                    }
                }
            }
        }

        return $response->withJson(['signatureBookEnabled' => $signatureBookEnabled, 'additionalsInfos' => $additionalsInfos, 'errors' => $errors]);
    }

    public function checkExternalNoteBook(Request $request, Response $response, array $aArgs)
    {
        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);

        $errors = ResourceListController::listControl(['groupId' => $aArgs['groupId'], 'userId' => $aArgs['userId'], 'basketId' => $aArgs['basketId'], 'currentUserId' => $currentUser['id']]);
        if (!empty($errors['errors'])) {
            return $response->withStatus($errors['code'])->withJson(['errors' => $errors['errors']]);
        }

        $data = $request->getParsedBody();

        $data['resources'] = array_slice($data['resources'], 0, 500);
        if (!ResController::hasRightByResId(['resId' => $data['resources'], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $resources = ResModel::get([
            'select' => ['res_id', 'locker_user_id', 'locker_time'],
            'where'  => ['res_id in (?)'],
            'data'   => [$data['resources']]
        ]);

        $resourcesForProcess = [];
        foreach ($resources as $resource) {
            $lock = true;
            if (empty($resource['locker_user_id'] || empty($resource['locker_time']))) {
                $lock = false;
            } elseif ($resource['locker_user_id'] == $currentUser['id']) {
                $lock = false;
            } elseif (strtotime($resource['locker_time']) < time()) {
                $lock = false;
            }
            if (!$lock) {
                $resourcesForProcess[] = $resource['res_id'];
            }
        }
        $data['resources'] = $resourcesForProcess;

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);

        $errors = [];
        if (!empty($loadedXml)) {
            $config['id'] = 'maarchParapheur';
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == $config['id']) {
                    $config['data'] = (array)$value;
                    break;
                }
            }

            $additionalsInfos = [
                'mails'  => [],
                'noMail' => []
            ];

            $userList = MaarchParapheurController::getInitializeDatas(['config' => $config]);
            if (!empty($userList['users'])) {
                $additionalsInfos['users'] = $userList['users'];
            } else {
                $additionalsInfos['users'] = [];
            }
            if (!empty($userList['errors'])) {
                $errors[] = $userList['errors'];
            }

            foreach ($data['resources'] as $resId) {
                $noAttachmentsResource = ResModel::getExtById(['resId' => $resId, 'select' => ['alt_identifier']]);
                if (empty($noAttachmentsResource['alt_identifier'])) {
                    $noAttachmentsResource['alt_identifier'] = _UNDEFINED;
                }

                $adrMainInfo = ConvertPdfController::getConvertedPdfById(['resId' => $resId, 'collId' => 'letterbox_coll']);
                if (empty($adrMainInfo['docserver_id'])) {
                    $additionalsInfos['noMail'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resId, 'reason' => 'noMailConversion'];
                    continue;
                }
                $docserverMainInfo = DocserverModel::getByDocserverId(['docserverId' => $adrMainInfo['docserver_id']]);
                if (empty($docserverMainInfo['path_template'])) {
                    $additionalsInfos['noMail'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resId, 'reason' => 'docserverDoesNotExists'];
                    continue;
                }
                $filePath = $docserverMainInfo['path_template'] . str_replace('#', '/', $adrMainInfo['path']) . $adrMainInfo['filename'];
                if (!is_file($filePath)) {
                    $additionalsInfos['noMail'][] = ['alt_identifier' => $noAttachmentsResource['alt_identifier'], 'res_id' => $resId, 'reason' => 'fileDoesNotExists'];
                    continue;
                }
                $additionalsInfos['mails'][] = ['res_id' => $resId];
            }
        }

        return $response->withJson(['additionalsInfos' => $additionalsInfos, 'errors' => $errors]);
    }

    public function checkShippings(Request $request, Response $response)
    {
        $mailevaConfig = CoreConfigModel::getMailevaConfiguration();
        if (empty($mailevaConfig)) {
            return $response->withStatus(400)->withJson(['errors' => 'Maileva configuration does not exist', 'errorLang' => 'missingMailevaConfig']);
        }

        $data = $request->getParsedBody();

        if (!Validator::arrayType()->notEmpty()->validate($data['resources'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body resources is empty or not an array']);
        }

        $data['resources'] = array_slice($data['resources'], 0, 500);
        if (!ResController::hasRightByResId(['resId' => $data['resources'], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $resources = ResModel::get([
            'select' => ['res_id', 'locker_user_id', 'locker_time'],
            'where'  => ['res_id in (?)'],
            'data'   => [$data['resources']]
        ]);

        $resourcesForProcess = [];
        foreach ($resources as $resource) {
            $lock = true;
            if (empty($resource['locker_user_id'] || empty($resource['locker_time']))) {
                $lock = false;
            } elseif ($resource['locker_user_id'] == $currentUser['id']) {
                $lock = false;
            } elseif (strtotime($resource['locker_time']) < time()) {
                $lock = false;
            }
            if (!$lock) {
                $resourcesForProcess[] = $resource['res_id'];
            }
        }
        $data['resources'] = $resourcesForProcess;

        $aDestination = ResModel::get([
            'select' => ['distinct(destination)'],
            'where'  => ['res_id in (?)'],
            'data'   => [$data['resources']]
        ]);

        $aTemplates = [];
        $entities   = [];
        $resources  = [];
        $canNotSend = [];

        foreach ($aDestination as $values) {
            if (!empty($values['destination'])) {
                $entities[] = $values['destination'];
            }
        }

        $entitiesInfos = [];
        if (!empty($entities)) {
            $aEntities = EntityModel::get(['select' => ['id', 'entity_label'], 'where' => ['entity_id in (?)'], 'data' => [$entities]]);

            $entitiesId = [];
            foreach ($aEntities as $value) {
                $entitiesId[] = (string)$value['id'];
                $entitiesInfos[] = $value['entity_label'];
            }

            $aTemplates = ShippingTemplateModel::getByEntities([
                'select'   => ['id', 'label', 'description', 'options', 'fee'],
                'entities' => $entitiesId
            ]);
    
            foreach ($aTemplates as $key => $value) {
                $aTemplates[$key]['options']  = json_decode($value['options'], true);
                $aTemplates[$key]['fee']      = json_decode($value['fee'], true);
            }
        }

        if (!empty($aTemplates)) {
            $aAttachments = AttachmentModel::getAttachmentToSend(['ids' => $data['resources']]);
            foreach ($data['resources'] as $valueResId) {
                $resIdFound = false;
                foreach ($aAttachments as $key => $attachment) {
                    if ($attachment['res_id_master'] == $valueResId) {
                        $resIdFound = true;
                        if (!empty($attachment['res_id'])) {
                            $isVersion    = false;
                            $attachmentId = $attachment['res_id'];
                            $collId       = 'attachments_coll';
                        } else {
                            $isVersion    = true;
                            $attachmentId = $attachment['res_id_version'];
                            $collId       = 'attachments_version_coll';
                        }
                        $convertedDocument = ConvertPdfController::getConvertedPdfById([
                            'select'    => ['docserver_id','path', 'filename', 'fingerprint'],
                            'resId'     => $attachmentId,
                            'collId'    => $collId,
                            'type'      => 'PDF',
                            'isVersion' => $isVersion
                        ]);
                        if (empty($convertedDocument['docserver_id'])) {
                            $resInfo = ResModel::getExtById(['select' => ['alt_identifier'], 'resId' => $valueResId]);
                            $canNotSend[] = ['resId' => $valueResId, 'chrono' => $resInfo['alt_identifier'], 'reason' => 'noAttachmentConversion', 'attachmentIdentifier' => $attachment['identifier']];
                            unset($aAttachments[$key]);
                            break;
                        }
                        if (empty($attachment['dest_address_id'])) {
                            $resInfo = ResModel::getExtById(['select' => ['alt_identifier'], 'resId' => $valueResId]);
                            $canNotSend[] = ['resId' => $valueResId, 'chrono' => $resInfo['alt_identifier'], 'reason' => 'noAttachmentContact', 'attachmentIdentifier' => $attachment['identifier']];
                            unset($aAttachments[$key]);
                            break;
                        }
                        $contact = ContactModel::getOnView(['select' => ['*'], 'where' => ['ca_id = ?'], 'data' => [$attachment['dest_address_id']]]);
                        if (empty($contact[0])) {
                            $resInfo = ResModel::getExtById(['select' => ['alt_identifier'], 'resId' => $valueResId]);
                            $canNotSend[] = ['resId' => $valueResId, 'chrono' => $resInfo['alt_identifier'], 'reason' => 'noAttachmentContact', 'attachmentIdentifier' => $attachment['identifier']];
                            unset($aAttachments[$key]);
                            break;
                        }
                        if (!empty($contact[0]['address_country']) && strtoupper(trim($contact[0]['address_country'])) != 'FRANCE') {
                            $resInfo = ResModel::getExtById(['select' => ['alt_identifier'], 'resId' => $valueResId]);
                            $canNotSend[] = ['resId' => $valueResId, 'chrono' => $resInfo['alt_identifier'], 'reason' => 'noFranceContact', 'attachmentIdentifier' => $attachment['identifier']];
                            unset($aAttachments[$key]);
                            break;
                        }
                        $afnorAddress = ContactController::getContactAfnor($contact[0]);
                        if ((empty($afnorAddress[1]) && empty($afnorAddress[2])) || empty($afnorAddress[6]) || !preg_match("/^\d{5}\s/", $afnorAddress[6])) {
                            $resInfo = ResModel::getExtById(['select' => ['alt_identifier'], 'resId' => $valueResId]);
                            $canNotSend[] = ['resId' => $valueResId, 'chrono' => $resInfo['alt_identifier'], 'reason' => 'incompleteAddressForPostal', 'attachmentIdentifier' => $attachment['identifier']];
                            unset($aAttachments[$key]);
                            break;
                        }

                        $resources[] = $attachment;
                        unset($aAttachments[$key]);
                    }
                }

                if (!$resIdFound) {
                    $resInfo = ResModel::getExtById(['select' => ['alt_identifier'], 'resId' => $valueResId]);
                    $canNotSend[] = ['resId' => $valueResId, 'chrono' => $resInfo['alt_identifier'], 'reason' => 'noAttachmentToSend'];
                }
            }
    
            foreach ($aTemplates as $key => $value) {
                if (!empty($resources)) {
                    $templateFee = ShippingTemplateController::calculShippingFee([
                        'fee'       => $value['fee'],
                        'resources' => $resources
                    ]);
                } else {
                    $templateFee = 0;
                }
                $aTemplates[$key]['fee'] = number_format($templateFee, 2, '.', '');
            }
        }

        return $response->withJson([
            'shippingTemplates' => $aTemplates,
            'entities'          => $entitiesInfos,
            'resources'         => $resources,
            'canNotSend'        => $canNotSend
        ]);
    }

    public function isDestinationChanging(Request $request, Response $response, array $args)
    {
        if (!ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $user = UserModel::getById(['id' => $args['userId'], 'select' => ['user_id']]);
        if (empty($user)) {
            return $response->withStatus(400)->withJson(['errors' => 'User does not exist']);
        }

        $changeDestination = true;
        $entities = UserEntityModel::get(['select' => ['entity_id'], 'where' => ['user_id = ?'], 'data' => [$user['user_id']]]);
        $resource = ResModel::getById(['select' => ['destination'], 'resId' => $args['resId']]);
        foreach ($entities as $entity) {
            if ($entity['entity_id'] == $resource['destination']) {
                $changeDestination = false;
            }
        }

        return $response->withJson(['isDestinationChanging' => $changeDestination]);
    }
}
