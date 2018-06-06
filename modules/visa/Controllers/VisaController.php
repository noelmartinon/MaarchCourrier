<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief   VisaController
* @author  <dev@maarch.org>
* @ingroup visa
*/
namespace Visa\Controllers;

use Attachment\models\AttachmentModel;
use Basket\models\BasketModel;
use Action\models\ActionModel;
use Contact\models\ContactModel;
use Core\Models\LangModel;
use Docserver\models\DocserverModel;
use Entity\models\ListInstanceModel;
use Group\models\ServiceModel;
use Link\models\LinkModel;
use Note\models\NoteModel;
use Priority\models\PriorityModel;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;


class VisaController
{
    public function getSignatureBook(Request $request, Response $response, array $aArgs)
    {
        $resId = $aArgs['resId'];
        $_SESSION['doc_id'] = $resId; //TODO Set session for some actions

        if (!ResController::hasRightByResId(['resId' => $resId, 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }
        $docserver = DocserverModel::getByTypeId(['docserver_type_id' => 'TEMPLATES', 'select' => ['path_template']]);
        if (!file_exists($docserver['path_template'])) {
            return $response->withStatus(500)->withJson(['errors' => _UNREACHABLE_DOCSERVER]);
        }

        $documents = VisaController::getIncomingMailAndAttachmentsForSignatureBook(['resId' => $resId]);
        if (!empty($documents['error'])) {
            return $response->withJson($documents);
        }

        $actions = [];
        $rawActions = ActionModel::getForBasketPage(['basketId' => $aArgs['basketId'], 'groupId' => $aArgs['groupId']]);
        foreach ($rawActions as $rawAction) {
            if ($rawAction['default_action_list'] == 'Y') {
                $actions[] = ['value' => 'end_action', 'label' => $rawAction['label_action'] . ' ('. _BY_DEFAULT .')'];
            } else {
                if (empty($rawAction['where_clause'])) {
                    $actions[] = ['value' => $rawAction['id_action'], 'label' => $rawAction['label_action']];
                } else {
                    $whereClause = PreparedClauseController::getPreparedClause(['clause' => $rawAction['where_clause'], 'userId' => $GLOBALS['userId']]);
                    $ressource = ResModel::getOnView(['select' => [1], 'where' => ['res_id = ?', $whereClause], 'data' => [$aArgs['resId']]]);
                    if (!empty($ressource)) {
                        $actions[] = ['value' => $rawAction['id_action'], 'label' => $rawAction['label_action']];
                    }
                }
            }
        }

        $actionLabel = (_ID_TO_DISPLAY == 'res_id' ? $documents[0]['res_id'] : $documents[0]['alt_id']);
        $actionLabel .= " : {$documents[0]['title']}";
        $currentAction = [
            'id' => ActionModel::getDefaultActionByGroupBasketId(['groupId' => $aArgs['groupId'], 'basketId' => $aArgs['basketId']]),
            'actionLabel' => $actionLabel
        ];
        $listInstances = ListInstanceModel::get([
            'select'    => ['COUNT(*)'],
            'where'     => ['res_id = ?', 'item_mode in (?)'],
            'data'      => [$aArgs['resId'], ['visa', 'sign']]
        ]);

        $user = UserModel::getByUserId(['userId' => $GLOBALS['userId'], 'select' => ['id']]);

        $datas = [];
        $datas['actions']       = $actions;
        $datas['attachments']   = VisaController::getAttachmentsForSignatureBook(['resId' => $resId, 'userId' => $GLOBALS['userId']]);
        $datas['documents']     = $documents;
        $datas['currentAction'] = $currentAction;
        $datas['resList']       = [];
        $datas['nbNotes']       = NoteModel::countByResId(['resId' => $resId, 'userId' => $GLOBALS['userId']]);
        $datas['nbLinks']       = count(LinkModel::getByResId(['resId' => $resId]));
        $datas['signatures']    = UserModel::getSignaturesById(['id' => $user['id']]);
        $datas['consigne']      = UserModel::getCurrentConsigneById(['resId' => $resId]);
        $datas['hasWorkflow']   = ((int)$listInstances[0]['count'] > 0);
        $datas['listinstance']  = ListInstanceModel::getCurrentStepByResId(['resId' => $resId]);
        $datas['canSign']       = ServiceModel::hasService(['id' => 'sign_document', 'userId' => $GLOBALS['userId'], 'location' => 'visa', 'type' => 'use']);
        $datas['lang']          = LangModel::getSignatureBookLang();
        $datas['isCurrentWorkflowUser'] = $datas['listinstance']['item_id'] == $GLOBALS['userId'];

        return $response->withJson($datas);
    }

    public function unsignFile(Request $request, Response $response, array $aArgs)
    {
        AttachmentModel::unsignAttachment(['table' => $aArgs['collId'], 'resId' => $aArgs['resId']]);

        $isVersion = ($aArgs['collId'] == 'res_attachments' ? 'false' : 'true');
        $user = UserModel::getByUserId(['userId' => $GLOBALS['userId'], 'select' => ['id']]);
        if (!AttachmentModel::hasAttachmentsSignedForUserById(['id' => $aArgs['resId'], 'isVersion' => $isVersion, 'user_serial_id' => $user['id']])) {
            $attachment = AttachmentModel::getById(['id' => $aArgs['resId'], 'isVersion' => $isVersion, 'select' => ['res_id_master']]);
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
        return $response->withJson(VisaController::getIncomingMailAndAttachmentsForSignatureBook(['resId' => $aArgs['resId']]));
    }

    public function getAttachmentsById(Request $request, Response $response, array $aArgs)
    {
        return $response->withJson(VisaController::getAttachmentsForSignatureBook(['resId' => $aArgs['resId'], 'userId' => $GLOBALS['userId']]));
    }

    private static function getIncomingMailAndAttachmentsForSignatureBook(array $aArgs = [])
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
                'viewerLink'    => "index.php?display=true&dir=indexing_searching&page=view_resource_controler&visu&id={$resId}&collid=letterbox_coll",
                'thumbnailLink' => "index.php?page=doc_thumb&module=thumbnails&res_id={$resId}&coll_id=letterbox_coll&display=true&advanced=true"
            ]
        ];

        foreach ($incomingMailAttachments as $value) {
            if ($value['attachment_type'] == 'converted_pdf') {
                continue;
            }

            $realId = 0;
            if ($value['res_id'] == 0) {
                $realId = $value['res_id_version'];
            } elseif ($value['res_id_version'] == 0) {
                $realId = $value['res_id'];
            }

            $viewerId = $realId;
            $pathToFind = $value['path'] . str_replace(strrchr($value['filename'], '.'), '.pdf', $value['filename']);
            $isConverted = false;
            foreach ($incomingMailAttachments as $tmpKey => $tmpValue) {
                if ($tmpValue['attachment_type'] == 'converted_pdf' && ($tmpValue['path'] . $tmpValue['filename'] == $pathToFind)) {
                    $viewerId = $tmpValue['res_id'];
                    $isConverted = true;
                }
            }

            $documents[] = [
                'res_id'        => $value['res_id'],
                'title'         => $value['title'],
                'format'        => $value['format'],
                'isConverted'   => $isConverted,
                'viewerLink'    => "index.php?display=true&module=visa&page=view_pdf_attachement&res_id_master={$resId}&id={$viewerId}",
                'thumbnailLink' => "index.php?page=doc_thumb&module=thumbnails&res_id={$value['res_id']}&coll_id=attachments_coll&display=true&advanced=true"
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

            $collId = '';
            $realId = 0;
            $isVersion = 'false';
            if ($value['res_id'] == 0) {
                $collId = 'version_attachments_coll';
                $realId = $value['res_id_version'];
                $isVersion = 'true';
            } elseif ($value['res_id_version'] == 0) {
                $collId = 'attachments_coll';
                $realId = $value['res_id'];
                $isVersion = 'false';
            }

            $viewerId = $realId;
            $viewerNoSignId = $realId;
            $pathToFind = $value['path'] . str_replace(strrchr($value['filename'], '.'), '.pdf', $value['filename']);
            $isConverted = false;
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
                        || ($signDaddy[0] == $value['res_id_version'] && $signDaddy[1] == "res_attachments")
                    ) {
                        $viewerId = $tmpValue['res_id'];
                        unset($attachments[$tmpKey]);
                    }
                }
            }

            if (!empty($value['dest_user'])) {
                $attachments[$key]['destUser'] = UserModel::getLabelledUserById(['userId' => $value['dest_user']]);
            } elseif (!empty($value['dest_contact_id']) && !empty($value['dest_address_id'])) {
                $attachments[$key]['destUser'] = ContactModel::getLabelledContactWithAddress(['contactId' => $value['dest_contact_id'], 'addressId' => $value['dest_address_id']]);
            }
            if (!empty($value['updated_by'])) {
                $attachments[$key]['updated_by'] = UserModel::getLabelledUserById(['userId' => $value['updated_by']]);
            }
            if (!empty($value['typist'])) {
                $attachments[$key]['typist'] = UserModel::getLabelledUserById(['userId' => $value['typist']]);
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

            $attachments[$key]['thumbnailLink'] = "index.php?page=doc_thumb&module=thumbnails&res_id={$realId}&coll_id={$collId}&display=true&advanced=true";

            if(!in_array(strtoupper($value['format']), ['PDF', 'JPG', 'JPEG', 'PNG', 'GIF']) ){
                $isVersion = 'false';
            }
            $attachments[$key]['viewerLink'] = "index.php?display=true&module=attachments&page=view_attachment&res_id_master={$aArgs['resId']}&id={$viewerId}&isVersion={$isVersion}";
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

    public function getDetailledResList(Request $request, Response $response, array $aArgs)
    {
        $resList = BasketModel::getResListById([
            'basketId'  => $aArgs['basketId'],
            'userId'    => $GLOBALS['userId'],
            'select'    => ['res_id', 'alt_identifier', 'subject', 'creation_date', 'process_limit_date', 'priority', 'contact_id', 'address_id', 'user_lastname', 'user_firstname']
        ]);

        $resListForAttachments = [];
        $resListForRequest = [];
        foreach ($resList as $key => $value) {
            $resListForAttachments[$value['res_id']] = null;
            $resListForRequest[] = $value['res_id'];
        }

        $attachmentsInResList = AttachmentModel::getOnView([
            'select'    => ['res_id_master', 'status', 'attachment_type'],
            'where'     => ['res_id_master in (?)', "attachment_type not in ('incoming_mail_attachment', 'print_folder', 'converted_pdf', 'signed_response')", "status not in ('DEL', 'TMP', 'OBS')"],
            'data'      => [$resListForRequest]
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

        foreach ($resList as $key => $value) {
            if (!empty($value['contact_id'])) {
                $resList[$key]['sender'] = ContactModel::getLabelledContactWithAddress(['contactId' => $value['contact_id'], 'addressId' => $value['address_id']]);
            } else {
                $resList[$key]['sender'] = $value['user_firstname'] . ' ' . $value['user_lastname'];
            }

            $priority = PriorityModel::getById(['id' => $value['priority'], 'select' => ['color', 'label']]);
            $resList[$key]['creation_date'] = date(DATE_ATOM, strtotime($resList[$key]['creation_date']));
            $resList[$key]['process_limit_date'] = (empty($resList[$key]['process_limit_date']) ? null : date(DATE_ATOM, strtotime($resList[$key]['process_limit_date'])));
            $resList[$key]['allSigned'] = ($resListForAttachments[$value['res_id']] === null ? false : $resListForAttachments[$value['res_id']]);
            if (!empty($priority)) {
                $resList[$key]['priorityColor'] = $priority['color'];
                $resList[$key]['priorityLabel'] = $priority['label'];
            }
            unset($resList[$key]['priority'], $resList[$key]['contact_id'], $resList[$key]['address_id'], $resList[$key]['user_lastname'], $resList[$key]['user_firstname']);
        }

        return $response->withJson(['resList' => $resList]);
    }

    public function getResList(Request $request, Response $response, array $aArgs)
    {
        $resList = BasketModel::getResListById([
            'basketId'  => $aArgs['basketId'],
            'userId'    => $GLOBALS['userId'],
            'select'    => ['res_id']
        ]);

        return $response->withJson(['resList' => $resList]);
    }

}
