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

use Attachments\Models\AttachmentsModel;
use Core\Models\UserModel;
use Core\Models\LangModel;
use Baskets\Models\BasketsModel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require_once 'modules/basket/class/class_modules_tools.php';
require_once 'core/class/class_core_tools.php';
require_once 'core/class/class_security.php';
require_once 'apps/maarch_entreprise/Models/ResModel.php';
require_once 'apps/maarch_entreprise/Models/HistoryModel.php';
require_once 'apps/maarch_entreprise/Models/ContactsModel.php';
require_once 'modules/notes/Models/NotesModel.php';
require_once 'modules/visa/Models/VisaModel.php';


class VisaController
{

    public function getSignatureBook(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $resId = $aArgs['resId'];
        $_SESSION['doc_id'] = $resId; //TODO Set session for some actions
        $basketId = $aArgs['basketId'];
        $collId = 'letterbox_coll';

        $coreTools = new \core_tools();
        $security = new \security();
        $allowed = $security->test_right_doc($collId, $resId);
        if (!$allowed) {
            return $response->withJson(['error' => 'Not Allowed']);
        }

        $documents = $this->getIncomingMailAndAttachmentsForSignatureBook(['resId' => $resId]);
        if (!empty($documents['error'])) {
            return $response->withJson($documents);
        }

        $basket = new \basket();
        $actions = $basket->get_actions_from_current_basket($resId, $collId, 'PAGE_USE', true);

        $actionsData = [];
        foreach ($actions as $value) {
            $actionsData[] = ['value' => $value['VALUE'], 'label' => $value['LABEL']];
        }

        $actionLabel = (_ID_TO_DISPLAY == 'res_id' ? $documents[0]['res_id'] : $documents[0]['alt_id']);
        $actionLabel .= " : {$documents[0]['title']}";
        $currentAction = [
            'id' => $_SESSION['current_basket']['default_action'], //TODO No Session
            'actionLabel' => $actionLabel
        ];


        $datas = [];
        $datas['actions']       = $actionsData;
        $datas['attachments']   = $this->getAttachmentsForSignatureBook(['resId' => $resId]);
        $datas['documents']     = $documents;
        $datas['currentAction'] = $currentAction;
        $datas['resList']       = [];
        $datas['nbNotes']       = \NotesModel::countForCurrentUserByResId(['resId' => $resId]);
        $datas['signatures']    = UserModel::getSignaturesById(['userId' => $_SESSION['user']['UserId']]);
        $datas['consigne']      = UserModel::getCurrentConsigneById(['resId' => $resId]);
        $datas['hasWorkflow']   = \VisaModel::hasVisaWorkflowByResId(['resId' => $resId]);
        $datas['canSign']       = $coreTools->test_service('sign_document', 'visa', false);
        $datas['lang']          = LangModel::getSignatureBookLang();


        return $response->withJson($datas);
    }

    public function unsignFile(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $resId = $aArgs['resId'];
        $collId = $aArgs['collId'];

        $bReturnSnd = false;
        $bReturnFirst = \ResModel::put(['collId' => $collId, 'set' => ['status' => 'A_TRA'], 'where' => ['res_id = ?'], 'data' => [$resId]]);
        if ($bReturnFirst) {
            $bReturnSnd = \ResModel::put(['collId' => $collId, 'set' => ['status' => 'DEL'], 'where' => ['origin = ?', 'status != ?'], 'data' => [$resId . ',' .$collId, 'DEL']]);
        }

        if ($bReturnFirst && $bReturnSnd) {
            return $response->withJson(['status' => 'OK']);
        } else {
            return $response->withJson(['status' => 'KO']);
        }
    }

    public function getIncomingMailAndAttachmentsById(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $resId = $aArgs['resId'];

        return $response->withJson($this->getIncomingMailAndAttachmentsForSignatureBook(['resId' => $resId]));
    }

    public function getAttachmentsById(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $resId = $aArgs['resId'];

        return $response->withJson($this->getAttachmentsForSignatureBook(['resId' => $resId]));
    }

    private function getIncomingMailAndAttachmentsForSignatureBook(array $aArgs = [])
    {
        $resId = $aArgs['resId'];

        $incomingMail = \ResModel::getById([
            'resId'  => $resId,
            'select' => ['res_id', 'subject', 'alt_identifier', 'category_id']
        ]);

        if (empty($incomingMail)) {
            return ['error' => 'No Document Found'];
        }

        $incomingMailAttachments = \ResModel::getAvailableLinkedAttachmentsIn([
            'resIdMaster' => $resId,
            'in'          => ['incoming_mail_attachment', 'converted_pdf'],
            'select'      => ['res_id', 'res_id_version', 'title', 'format', 'attachment_type', 'path', 'filename']
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

    private function getAttachmentsForSignatureBook(array $aArgs = [])
    {
        $attachmentTypes = AttachmentsModel::getAttachmentsTypesByXML();

        $orderBy = "CASE attachment_type WHEN 'response_project' THEN 1";
        $c = 2;
        foreach ($attachmentTypes as $key => $value) {
            if ($value['sign'] && $key != 'response_project') {
                $orderBy .= " WHEN '{$key}' THEN {$c}";
                ++$c;
            }
        }
        $orderBy .= " ELSE {$c} END, doc_date DESC NULLS LAST, creation_date DESC";

        $attachments = \ResModel::getAvailableAndTemporaryLinkedAttachmentsNotIn(
            [
                'resIdMaster'   => $aArgs['resId'],
                'notIn'         => ['incoming_mail_attachment', 'print_folder'],
                'select'        => [
                    'res_id', 'res_id_version', 'title', 'identifier', 'attachment_type',
                    'status', 'typist', 'path', 'filename', 'updated_by', 'creation_date',
                    'validation_date', 'format', 'relation', 'dest_user', 'dest_contact_id',
                    'dest_address_id', 'origin', 'doc_date', 'attachment_id_master'
                ],
                'orderBy'       => $orderBy
            ]
        );

        $coreTools = new \core_tools();
        $canModify = $coreTools->test_service('modify_attachments', 'attachments', false);
        $canDelete = $coreTools->test_service('delete_attachments', 'attachments', false);

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
                $attachments[$key]['destUser'] = UserModel::getLabelledUserById(['id' => $value['dest_user']]);
            } elseif (!empty($value['dest_contact_id']) && !empty($value['dest_address_id'])) {
                $attachments[$key]['destUser'] = \ContactsModel::getLabelledContactWithAddress(['contactId' => $value['dest_contact_id'], 'addressId' => $value['dest_address_id']]);
            }
            if (!empty($value['updated_by'])) {
                $attachments[$key]['updated_by'] = UserModel::getLabelledUserById(['id' => $value['updated_by']]);
            }
            if (!empty($value['typist'])) {
                $attachments[$key]['typist'] = UserModel::getLabelledUserById(['id' => $value['typist']]);
            }

            $attachments[$key]['canModify'] = false;
            $attachments[$key]['canDelete'] = false;
            if ($canModify || $value['typist'] == $_SESSION['user']['UserId']) {
                $attachments[$key]['canModify'] = true;
            }
            if ($canDelete || $value['typist'] == $_SESSION['user']['UserId']) {
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

        $obsAttachments = \ResModel::getObsLinkedAttachmentsNotIn([
                'resIdMaster'   => $aArgs['resId'],
                'notIn'         => ['incoming_mail_attachment', 'print_folder', 'converted_pdf', 'signed_response'],
                'select'        => [
                    'res_id', 'res_id_version', 'attachment_id_master', 'relation', 'creation_date', 'title'
                ]
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

    public function getDetailledResList(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $basketId = $aArgs['basketId'];

        $resList = BasketsModel::getResListById(
            [
                'basketId' => $basketId,
                'select'  => ['res_id', 'alt_identifier', 'subject', 'creation_date', 'process_limit_date', 'priority', 'contact_id', 'address_id', 'user_lastname', 'user_firstname']
            ]
        );

        $resListForAttachments = [];
        $resListForRequest = [];
        foreach ($resList as $key => $value) {
            $resListForAttachments[$value['res_id']] = null;
            $resListForRequest[] = $value['res_id'];
        }

        $attachmentsInResList = AttachmentsModel::getAttachmentsWithOptions(
            [
                'select'    => ['res_id_master', 'status', 'attachment_type'],
                'where'     => ['res_id_master in (?)', "attachment_type not in ('incoming_mail_attachment', 'print_folder', 'converted_pdf', 'signed_response')", "status not in ('DEL', 'TMP', 'OBS')"],
                'data'      => [$resListForRequest]
            ]
        );

        $attachmentTypes = AttachmentsModel::getAttachmentsTypesByXML();
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
                $resList[$key]['sender'] = \ContactsModel::getLabelledContactWithAddress(['contactId' => $value['contact_id'], 'addressId' => $value['address_id']]);
            } else {
                $resList[$key]['sender'] = $value['user_firstname'] . ' ' . $value['user_lastname'];
            }

            $resList[$key]['creation_date'] = date(DATE_ATOM, strtotime($resList[$key]['creation_date']));
            $resList[$key]['process_limit_date'] = (empty($resList[$key]['process_limit_date']) ? null : date(DATE_ATOM, strtotime($resList[$key]['process_limit_date'])));
            $resList[$key]['allSigned'] = ($resListForAttachments[$value['res_id']] === null ? false : $resListForAttachments[$value['res_id']]);
            $resList[$key]['priorityColor'] = $_SESSION['mail_priorities_color'][$value['priority']]; //TODO No Session
            $resList[$key]['priorityLabel'] = $_SESSION['mail_priorities'][$value['priority']]; //TODO No Session
            unset($resList[$key]['priority'], $resList[$key]['contact_id'], $resList[$key]['address_id'], $resList[$key]['user_lastname'], $resList[$key]['user_firstname']);
        }

        return $response->withJson(['resList' => $resList]);
    }

    public function getResList(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $basketId = $aArgs['basketId'];

        $resList = BasketsModel::getResListById(
            [
                'basketId' => $basketId,
                'select'  => ['res_id']
            ]
        );

        return $response->withJson(['resList' => $resList]);
    }

}
