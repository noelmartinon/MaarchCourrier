<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief   Signature Book Controller
* @author  dev@maarch.org
*/

namespace SignatureBook\controllers;

use Action\models\ActionModel;
use Attachment\models\AttachmentModel;
use Basket\models\ActionGroupBasketModel;
use Basket\models\BasketModel;
use Contact\models\ContactModel;
use Convert\controllers\ConvertPdfController;
use Entity\models\ListInstanceModel;
use Group\models\GroupModel;
use Group\models\ServiceModel;
use Link\models\LinkModel;
use Note\models\NoteModel;
use Priority\models\PriorityModel;
use Resource\controllers\ResController;
use Resource\controllers\ResourceListController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;
use User\models\UserSignatureModel;

class SignatureBookController
{
    public function getSignatureBook(Request $request, Response $response, array $aArgs)
    {
        $resId = $aArgs['resId'];

        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $documents = SignatureBookController::getIncomingMailAndAttachmentsForSignatureBook(['resId' => $resId]);
        if (!empty($documents['error'])) {
            return $response->withJson($documents);
        }

        $basket = BasketModel::getById(['id' => $aArgs['basketId'], 'select' => ['basket_id', 'basket_clause']]);
        $group = GroupModel::getById(['id' => $aArgs['groupId'], 'select' => ['group_id']]);

        $actions = [];
        $rawActions = ActionModel::getForBasketPage(['basketId' => $basket['basket_id'], 'groupId' => $group['group_id']]);
        foreach ($rawActions as $rawAction) {
            if ($rawAction['default_action_list'] == 'Y') {
                $actions[] = ['value' => 'end_action', 'label' => $rawAction['label_action'] . ' ('. _BY_DEFAULT .')'];
            } else {
                if (empty($rawAction['where_clause'])) {
                    $actions[] = ['value' => $rawAction['id_action'], 'label' => $rawAction['label_action']];
                } else {
                    $whereClause = PreparedClauseController::getPreparedClause(['clause' => $rawAction['where_clause'], 'login' => $GLOBALS['userId']]);
                    $ressource = ResModel::getOnView(['select' => [1], 'where' => ['res_id = ?', $whereClause], 'data' => [$aArgs['resId']]]);
                    if (!empty($ressource)) {
                        $actions[] = ['value' => $rawAction['id_action'], 'label' => $rawAction['label_action']];
                    }
                }
            }
        }

        $defaultAction = ActionGroupBasketModel::get([
            'select'    => ['id_action'],
            'where'     => ['basket_id = ?', 'group_id = ?', 'default_action_list = ?'],
            'data'      => [$basket['basket_id'], $group['group_id'], 'Y']
        ]);

        $actionLabel = (_ID_TO_DISPLAY == 'res_id' ? $documents[0]['res_id'] : $documents[0]['alt_id']);
        $actionLabel .= " : {$documents[0]['title']}";
        $currentAction = [
            'id'            => $defaultAction[0]['id_action'],
            'actionLabel'   => $actionLabel
        ];
        $listInstances = ListInstanceModel::get([
            'select'    => ['COUNT(*)'],
            'where'     => ['res_id = ?', 'item_mode in (?)'],
            'data'      => [$aArgs['resId'], ['visa', 'sign']]
        ]);

        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $owner = UserModel::getById(['id' => $aArgs['userId'], 'select' => ['user_id']]);
        $whereClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $owner['user_id']]);
        $resources = ResModel::getOnView([
            'select'    => ['res_id'],
            'where'     => [$whereClause]
        ]);

        $datas = [];
        $datas['actions']               = $actions;
        $datas['attachments']           = SignatureBookController::getAttachmentsForSignatureBook(['resId' => $resId, 'userId' => $GLOBALS['userId']]);
        $datas['documents']             = $documents;
        $datas['currentAction']         = $currentAction;
        $datas['resList']               = $resources;
        $datas['nbNotes']               = NoteModel::countByResId(['resId' => $resId, 'login' => $GLOBALS['userId']]);
        $datas['nbLinks']               = count(LinkModel::getByResId(['resId' => $resId]));
        $datas['signatures']            = UserSignatureModel::getByUserSerialId(['userSerialid' => $currentUser['id']]);
        $datas['consigne']              = UserModel::getCurrentConsigneById(['resId' => $resId]);
        $datas['hasWorkflow']           = ((int)$listInstances[0]['count'] > 0);
        $datas['listinstance']          = ListInstanceModel::getCurrentStepByResId(['resId' => $resId]);
        $datas['canSign']               = ServiceModel::hasService(['id' => 'sign_document', 'userId' => $GLOBALS['userId'], 'location' => 'visa', 'type' => 'use']);
        $datas['isCurrentWorkflowUser'] = $datas['listinstance']['item_id'] == $GLOBALS['userId'];

        return $response->withJson($datas);
    }

    public function unsignFile(Request $request, Response $response, array $aArgs)
    {
        $data = $request->getParams();
        if (!Validator::stringType()->notEmpty()->validate($data['table'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $attachment = AttachmentModel::getById(['id' => $aArgs['resId'], 'isVersion' => ($data['table'] != 'res_attachments'), 'select' => ['res_id_master']]);
        if (!ResController::hasRightByResId(['resId' => [$attachment['res_id_master']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        AttachmentModel::unsignAttachment(['table' => $data['table'], 'resId' => $aArgs['resId']]);

        $isVersion = ($data['table'] == 'res_attachments' ? 'false' : 'true');
        $user = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        if (!AttachmentModel::hasAttachmentsSignedForUserById(['id' => $aArgs['resId'], 'isVersion' => $isVersion, 'user_serial_id' => $user['id']])) {
            ListInstanceModel::update([
                'set'   => ['signatory' => 'false'],
                'where' => ['res_id = ?', 'item_id = ?', 'difflist_type = ?'],
                'data'  => [$attachment['res_id_master'], $GLOBALS['userId'], 'VISA_CIRCUIT']
            ]);
        }

        return $response->withJson(['success' => 'success']);
    }

    public function getIncomingMailAndAttachmentsById(Request $request, Response $response, array $aArgs)
    {
        if (!ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        return $response->withJson(SignatureBookController::getIncomingMailAndAttachmentsForSignatureBook(['resId' => $aArgs['resId']]));
    }

    public function getAttachmentsById(Request $request, Response $response, array $aArgs)
    {
        if (!ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        return $response->withJson(SignatureBookController::getAttachmentsForSignatureBook(['resId' => $aArgs['resId'], 'userId' => $GLOBALS['userId']]));
    }

    private static function getIncomingMailAndAttachmentsForSignatureBook(array $aArgs)
    {
        $resId = $aArgs['resId'];

        $incomingMail = ResModel::getById([
            'resId'     => $resId,
            'select'    => ['res_id', 'subject']
        ]);

        if (empty($incomingMail)) {
            return ['error' => 'No Document Found'];
        }
        $incomingExtMail = ResModel::getExtById([
            'resId'     => $resId,
            'select'    => ['alt_identifier', 'category_id']
        ]);
        $incomingMail['alt_identifier'] = $incomingExtMail['alt_identifier'];
        $incomingMail['category_id'] = $incomingExtMail['category_id'];

        $incomingMailAttachments = AttachmentModel::getOnView([
            'select'      => ['res_id', 'res_id_version', 'title', 'format', 'attachment_type', 'path', 'filename'],
            'where'     => ['res_id_master = ?', 'attachment_type in (?)', "status not in ('DEL', 'TMP', 'OBS')"],
            'data'      => [$resId, ['incoming_mail_attachment', 'converted_pdf']]
        ]);

        $documents = [
            [
                'res_id'        => $incomingMail['res_id'],
                'alt_id'        => $incomingMail['alt_identifier'],
                'title'         => $incomingMail['subject'],
                'category_id'   => $incomingMail['category_id'],
                'viewerLink'    => "../../rest/res/{$resId}/content",
                'thumbnailLink' => "rest/res/{$resId}/thumbnail"
            ]
        ];

        foreach ($incomingMailAttachments as $value) {
            if ($value['attachment_type'] == 'converted_pdf') {
                continue;
            }

            $realId = 0;
            if ($value['res_id'] == 0) {
                $realId = $value['res_id_version'];
                $isVersion = true;
            } elseif ($value['res_id_version'] == 0) {
                $realId = $value['res_id'];
                $isVersion = false;
            }

            $convertedAttachment = ConvertPdfController::getConvertedPdfById(['select' => ['docserver_id', 'path', 'filename'], 'resId' => $realId, 'collId' => 'attachments_coll', 'isVersion' => $isVersion]);

            if (empty($convertedAttachment['errors'])) {
                $isConverted = true;
            } else {
                $isConverted = false;
            }

            $documents[] = [
                'res_id'        => $realId,
                'title'         => $value['title'],
                'format'        => $value['format'],
                'isConverted'   => $isConverted,
                'viewerLink'    => "../../rest/res/{$resId}/attachments/{$realId}/content",
                'thumbnailLink' => "rest/res/{$resId}/attachments/{$realId}/thumbnail"
            ];
        }

        return $documents;
    }

    private static function getAttachmentsForSignatureBook(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $attachmentTypes = AttachmentModel::getAttachmentsTypesByXML();

        $orderBy = "CASE attachment_type WHEN 'response_project' THEN 1";
        $c = 2;
        foreach ($attachmentTypes as $key => $value) {
            if ($value['sign'] && $key != 'response_project') {
                $orderBy .= " WHEN '{$key}' THEN {$c}";
                ++$c;
            }
        }
        $orderBy .= " ELSE {$c} END, doc_date DESC NULLS LAST, creation_date DESC";

        $attachments = AttachmentModel::getOnView([
            'select'    => [
                'res_id', 'res_id_version', 'title', 'identifier', 'attachment_type',
                'status', 'typist', 'path', 'filename', 'updated_by', 'creation_date',
                'validation_date', 'format', 'relation', 'dest_user', 'dest_contact_id',
                'dest_address_id', 'origin', 'doc_date', 'attachment_id_master'
            ],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', "status not in ('DEL', 'OBS')", 'in_signature_book = TRUE'],
            'data'      => [$aArgs['resId'], ['incoming_mail_attachment', 'print_folder']],
            'orderBy'   => [$orderBy]
        ]);

        $canModify = ServiceModel::hasService(['id' => 'modify_attachments', 'userId' => $aArgs['userId'], 'location' => 'attachments', 'type' => 'use']);
        $canDelete = ServiceModel::hasService(['id' => 'delete_attachments', 'userId' => $aArgs['userId'], 'location' => 'attachments', 'type' => 'use']);

        foreach ($attachments as $key => $value) {
            if ($value['attachment_type'] == 'converted_pdf' || ($value['attachment_type'] == 'signed_response' && !empty($value['origin']))) {
                continue;
            }

            $realId = 0;
            if ($value['res_id'] == 0) {
                $realId = $value['res_id_version'];
                $isVersion = true;
            } elseif ($value['res_id_version'] == 0) {
                $realId = $value['res_id'];
                $isVersion = false;
            }

            $viewerId       = $realId;
            $viewerNoSignId = $realId;
            $pathToFind     = $value['path'] . str_replace(strrchr($value['filename'], '.'), '.pdf', $value['filename']);
            $isConverted    = false;

            $convertedAttachment = ConvertPdfController::getConvertedPdfById(['select' => [1], 'resId' => $realId, 'collId' => 'attachments_coll', 'isVersion' => $isVersion]);

            if (empty($convertedAttachment['errors'])) {
                $isConverted = true;
            }

            foreach ($attachments as $tmpKey => $tmpValue) {
                if (strpos($value['format'], 'xl') !== 0 && $value['format'] != 'pptx' && $tmpValue['attachment_type'] == 'converted_pdf' && ($tmpValue['path'] . $tmpValue['filename'] == $pathToFind)) {
                    if ($value['status'] != 'SIGN') {
                        $viewerId = $tmpValue['res_id'];
                    }
                    $viewerNoSignId = $tmpValue['res_id'];
                    $isConverted = true;
                    unset($attachments[$tmpKey]);
                }
                if ($value['status'] == 'SIGN' && $tmpValue['attachment_type'] == 'signed_response' && !empty($tmpValue['origin'])) {
                    $signDaddy = explode(',', $tmpValue['origin']);
                    if (($signDaddy[0] == $value['res_id'] && $signDaddy[1] == "res_attachments")
                        || ($signDaddy[0] == $value['res_id_version'] && $signDaddy[1] == "res_version_attachments")
                    ) {
                        $viewerId = $tmpValue['res_id'];
                        unset($attachments[$tmpKey]);
                    }
                }
            }

            if (!empty($value['dest_user'])) {
                $attachments[$key]['destUser'] = UserModel::getLabelledUserById(['login' => $value['dest_user']]);
            } elseif (!empty($value['dest_contact_id']) && !empty($value['dest_address_id'])) {
                $attachments[$key]['destUser'] = ContactModel::getLabelledContactWithAddress(['contactId' => $value['dest_contact_id'], 'addressId' => $value['dest_address_id']]);
            }
            if (!empty($value['updated_by'])) {
                $attachments[$key]['updated_by'] = UserModel::getLabelledUserById(['login' => $value['updated_by']]);
            }
            if (!empty($value['typist'])) {
                $attachments[$key]['typist'] = UserModel::getLabelledUserById(['login' => $value['typist']]);
            }

            $attachments[$key]['canModify'] = false;
            $attachments[$key]['canDelete'] = false;
            if ($canModify || $value['typist'] == $aArgs['userId']) {
                $attachments[$key]['canModify'] = true;
            }
            if ($canDelete || $value['typist'] == $aArgs['userId']) {
                $attachments[$key]['canDelete'] = true;
            }

            $attachments[$key]['creation_date'] = date(DATE_ATOM, strtotime($attachments[$key]['creation_date']));
            if ($attachments[$key]['validation_date']) {
                $attachments[$key]['validation_date'] = date(DATE_ATOM, strtotime($attachments[$key]['validation_date']));
            }
            if ($attachments[$key]['doc_date']) {
                $attachments[$key]['doc_date'] = date(DATE_ATOM, strtotime($attachments[$key]['doc_date']));
            }
            $attachments[$key]['isConverted'] = $isConverted;
            $attachments[$key]['viewerNoSignId'] = $viewerNoSignId;
            $attachments[$key]['attachment_type'] = $attachmentTypes[$value['attachment_type']]['label'];
            $attachments[$key]['icon'] = $attachmentTypes[$value['attachment_type']]['icon'];
            $attachments[$key]['sign'] = $attachmentTypes[$value['attachment_type']]['sign'];

            if ($value['status'] == 'SIGN') {
                $attachments[$key]['viewerLink'] = "../../rest/res/{$aArgs['resId']}/attachments/{$viewerId}/content?".rand();
            } else {
                $attachments[$key]['viewerLink'] = "../../rest/res/{$aArgs['resId']}/attachments/{$realId}/content?".rand();
            }
        }

        $obsAttachments = AttachmentModel::getOnView([
            'select'    => ['res_id', 'res_id_version', 'attachment_id_master', 'relation', 'creation_date', 'title'],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', 'status = ?'],
            'data'      => [$aArgs['resId'], ['incoming_mail_attachment', 'print_folder', 'converted_pdf', 'signed_response'], 'OBS'],
            'orderBy'  => ['relation ASC']
        ]);

        $obsData = [];
        foreach ($obsAttachments as $value) {
            if ($value['relation'] == 1) {
                $obsData[$value['res_id']][] = ['resId' => $value['res_id'], 'title' => $value['title'], 'relation' => $value['relation'], 'creation_date' => $value['creation_date']];
            } else {
                $obsData[$value['attachment_id_master']][] = ['resId' => $value['res_id_version'], 'title' => $value['title'], 'relation' => $value['relation'], 'creation_date' => $value['creation_date']];
            }
        }

        foreach ($attachments as $key => $value) {
            if ($value['attachment_type'] == 'converted_pdf' || $value['attachment_type'] == 'signed_response') {
                unset($attachments[$key]);
                continue;
            }

            $attachments[$key]['obsAttachments'] = [];
            if ($value['relation'] > 1 && !empty($obsData[$value['attachment_id_master']])) {
                $attachments[$key]['obsAttachments'] = $obsData[$value['attachment_id_master']];
            }

            unset($attachments[$key]['path'], $attachments[$key]['filename'], $attachments[$key]['dest_user'],
                $attachments[$key]['dest_contact_id'], $attachments[$key]['dest_address_id'], $attachments[$key]['attachment_id_master']
            );
        }

        return array_values($attachments);
    }

    public function getResources(Request $request, Response $response, array $aArgs)
    {
        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $errors = ResourceListController::listControl(['groupId' => $aArgs['groupId'], 'userId' => $aArgs['userId'], 'basketId' => $aArgs['basketId'], 'currentUserId' => $currentUser['id']]);
        if (!empty($errors['errors'])) {
            return $response->withStatus($errors['code'])->withJson(['errors' => $errors['errors']]);
        }

        $basket = BasketModel::getById(['id' => $aArgs['basketId'], 'select' => ['basket_clause', 'basket_id', 'basket_name', 'basket_res_order']]);

        $user   = UserModel::getById(['id' => $aArgs['userId'], 'select' => ['user_id']]);
        $whereClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $user['user_id']]);
        $resources = ResModel::getOnView([
            'select'    => ['res_id', 'alt_identifier', 'subject', 'creation_date', 'process_limit_date', 'priority', 'contact_id', 'address_id', 'user_lastname', 'user_firstname'],
            'where'     => [$whereClause],
            'orderBy'   => empty($basket['basket_res_order']) ? ['creation_date DESC'] : [$basket['basket_res_order']]
        ]);

        $resListForAttachments = [];
        $resIds = [];
        foreach ($resources as $value) {
            $resListForAttachments[$value['res_id']] = null;
            $resIds[] = $value['res_id'];
        }

        $attachmentsInResList = AttachmentModel::getOnView([
            'select'    => ['res_id_master', 'status', 'attachment_type'],
            'where'     => ['res_id_master in (?)', "attachment_type not in ('incoming_mail_attachment', 'print_folder', 'converted_pdf', 'signed_response')", "status not in ('DEL', 'TMP', 'OBS')"],
            'data'      => [$resIds]
        ]);

        $attachmentTypes = AttachmentModel::getAttachmentsTypesByXML();
        foreach ($attachmentsInResList as $value) {
            if ($resListForAttachments[$value['res_id_master']] === null) {
                $resListForAttachments[$value['res_id_master']] = true;
            }
            if ($attachmentTypes[$value['attachment_type']]['sign'] && ($value['status'] == 'TRA' || $value['status'] == 'A_TRA')) {
                $resListForAttachments[$value['res_id_master']] = false;
            }
        }

        foreach ($resources as $key => $value) {
            if (!empty($value['contact_id'])) {
                $resources[$key]['sender'] = ContactModel::getLabelledContactWithAddress(['contactId' => $value['contact_id'], 'addressId' => $value['address_id']]);
            } else {
                $resources[$key]['sender'] = $value['user_firstname'] . ' ' . $value['user_lastname'];
            }

            $resources[$key]['creation_date'] = date(DATE_ATOM, strtotime($resources[$key]['creation_date']));
            $resources[$key]['process_limit_date'] = (empty($resources[$key]['process_limit_date']) ? null : date(DATE_ATOM, strtotime($resources[$key]['process_limit_date'])));
            $resources[$key]['allSigned'] = ($resListForAttachments[$value['res_id']] === null ? false : $resListForAttachments[$value['res_id']]);
            if (!empty($value['priority'])) {
                $priority = PriorityModel::getById(['id' => $value['priority'], 'select' => ['color', 'label']]);
            }
            if (!empty($priority)) {
                $resources[$key]['priorityColor'] = $priority['color'];
                $resources[$key]['priorityLabel'] = $priority['label'];
            }
            unset($resources[$key]['priority'], $resources[$key]['contact_id'], $resources[$key]['address_id'], $resources[$key]['user_lastname'], $resources[$key]['user_firstname']);
        }

        return $response->withJson(['resources' => $resources]);
    }
}
