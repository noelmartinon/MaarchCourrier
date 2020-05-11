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

use Attachment\models\AttachmentModel;
use Basket\models\BasketModel;
use Basket\models\GroupBasketModel;
use Basket\models\RedirectBasketModel;
use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Convert\controllers\ConvertPdfController;
use Convert\models\AdrModel;
use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use Entity\models\ListInstanceModel;
use Group\controllers\PrivilegeController;
use Group\models\GroupModel;
use History\controllers\HistoryController;
use Note\models\NoteModel;
use Priority\models\PriorityModel;
use Resource\controllers\ResController;
use Resource\controllers\ResourceListController;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;
use User\models\UserGroupModel;
use User\models\UserModel;
use User\models\UserSignatureModel;

class SignatureBookController
{
    public function getSignatureBook(Request $request, Response $response, array $aArgs)
    {
        $resId = $aArgs['resId'];

        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $documents = SignatureBookController::getIncomingMailAndAttachmentsForSignatureBook(['resId' => $resId]);
        if (!empty($documents['errors'])) {
            return $response->withStatus(400)->withJson($documents);
        }

        $basket = BasketModel::getById(['id' => $aArgs['basketId'], 'select' => ['basket_id', 'basket_clause']]);

        $listInstances = ListInstanceModel::get([
            'select'    => ['COUNT(*)'],
            'where'     => ['res_id = ?', 'item_mode in (?)'],
            'data'      => [$aArgs['resId'], ['visa', 'sign']]
        ]);

        $owner = UserModel::getById(['id' => $aArgs['userId'], 'select' => ['user_id']]);
        $whereClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $owner['user_id']]);
        $resources = ResModel::getOnView([
            'select'    => ['res_id'],
            'where'     => [$whereClause]
        ]);

        $datas = [];
        $datas['attachments']           = SignatureBookController::getAttachmentsForSignatureBook(['resId' => $resId, 'userId' => $GLOBALS['id']]);
        $datas['documents']             = $documents;
        $datas['resList']               = $resources;
        $datas['nbNotes']               = NoteModel::countByResId(['resId' => $resId, 'userId' => $GLOBALS['id'], 'login' => $GLOBALS['login']]);
        $datas['nbLinks']               = 0;
        $datas['signatures']            = UserSignatureModel::getByUserSerialId(['userSerialid' => $GLOBALS['id']]);
        $datas['consigne']              = UserModel::getCurrentConsigneById(['resId' => $resId]);
        $datas['hasWorkflow']           = ((int)$listInstances[0]['count'] > 0);
        $datas['listinstance']          = ListInstanceModel::getCurrentStepByResId(['resId' => $resId]);
        $datas['canSign']               = PrivilegeController::hasPrivilege(['privilegeId' => 'sign_document', 'userId' => $GLOBALS['id']]);
        $datas['isCurrentWorkflowUser'] = $datas['listinstance']['item_id'] == $GLOBALS['login'];

        return $response->withJson($datas);
    }

    public function getIncomingMailAndAttachmentsById(Request $request, Response $response, array $aArgs)
    {
        if (!ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        return $response->withJson(SignatureBookController::getIncomingMailAndAttachmentsForSignatureBook(['resId' => $aArgs['resId']]));
    }

    public function getAttachmentsById(Request $request, Response $response, array $aArgs)
    {
        if (!ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        return $response->withJson(SignatureBookController::getAttachmentsForSignatureBook(['resId' => $aArgs['resId'], 'userId' => $GLOBALS['id']]));
    }

    private static function getIncomingMailAndAttachmentsForSignatureBook(array $aArgs)
    {
        $resId = $aArgs['resId'];

        $incomingMail = ResModel::getById([
            'resId'     => $resId,
            'select'    => ['res_id', 'subject', 'alt_identifier', 'category_id', 'filename', 'integrations']
        ]);
        if (empty($incomingMail)) {
            return ['errors' => 'No Document Found'];
        }

        $integrations = json_decode($incomingMail['integrations'], true);
        $documents = [];
        if (!empty($incomingMail['filename']) && empty($integrations['inSignatureBook'])) {
            $documents[] = [
                'res_id'          => $incomingMail['res_id'],
                'alt_id'          => $incomingMail['alt_identifier'],
                'title'           => $incomingMail['subject'],
                'category_id'     => $incomingMail['category_id'],
                'viewerLink'      => "../rest/resources/{$resId}/content",
                'thumbnailLink'   => "../rest/resources/{$resId}/thumbnail",
                'inSignatureBook' => false
            ];
        } else {
            $documents[] = [
                'alt_id'          => $incomingMail['alt_identifier'],
                'title'           => $incomingMail['subject'],
                'inSignatureBook' => true
            ];
        }

        $incomingMailAttachments = AttachmentModel::get([
            'select'    => ['res_id', 'title', 'format', 'attachment_type', 'path', 'filename'],
            'where'     => ['res_id_master = ?', 'attachment_type in (?)', "status not in ('DEL', 'TMP', 'OBS')"],
            'data'      => [$resId, ['incoming_mail_attachment']]
        ]);
        foreach ($incomingMailAttachments as $value) {
            $realId = $value['res_id'];

            $documents[] = [
                'res_id'        => $realId,
                'title'         => $value['title'],
                'format'        => $value['format'],
                'isConverted'   => ConvertPdfController::canConvert(['extension' => $value['format']]),
                'viewerLink'    => "../rest/attachments/{$realId}/content",
                'thumbnailLink' => "../rest/attachments/{$realId}/thumbnail"
            ];
        }

        return $documents;
    }

    private static function getAttachmentsForSignatureBook(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'userId']);
        ValidatorModel::intVal($args, ['resId', 'userId']);

        $attachmentTypes = AttachmentModel::getAttachmentsTypesByXML();

        $orderBy = "CASE attachment_type WHEN 'response_project' THEN 1";
        $c = 2;
        foreach ($attachmentTypes as $key => $value) {
            if ($value['sign'] && $key != 'response_project') {
                $orderBy .= " WHEN '{$key}' THEN {$c}";
                ++$c;
            }
        }
        $orderBy .= " ELSE {$c} END, validation_date DESC NULLS LAST, creation_date DESC";

        $attachments = AttachmentModel::get([
            'select'    => [
                'res_id', 'title', 'identifier', 'attachment_type',
                'status', 'typist', 'path', 'filename', 'modified_by', 'creation_date',
                'validation_date', 'format', 'relation', 'recipient_id', 'recipient_type',
                'origin', 'validation_date', 'origin_id'
            ],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', "status not in ('DEL', 'OBS')", 'in_signature_book = TRUE'],
            'data'      => [$args['resId'], ['incoming_mail_attachment']],
            'orderBy'   => [$orderBy]
        ]);

        $canManageAttachment = PrivilegeController::hasPrivilege(['privilegeId' => 'manage_attachments', 'userId' => $args['userId']]);

        foreach ($attachments as $key => $value) {
            if (($value['attachment_type'] == 'signed_response' && !empty($value['origin']))) {
                continue;
            }

            $realId         = $value['res_id'];
            $viewerId       = $realId;
            $viewerNoSignId = $realId;
            $pathToFind     = $value['path'] . str_replace(strrchr($value['filename'], '.'), '.pdf', $value['filename']);
            $isConverted    = false;

            foreach ($attachments as $tmpKey => $tmpValue) {
                if ($value['status'] == 'SIGN' && $tmpValue['attachment_type'] == 'signed_response' && !empty($tmpValue['origin'])) {
                    $signDaddy = explode(',', $tmpValue['origin']);
                    if (($signDaddy[0] == $value['res_id'] && $signDaddy[1] == "res_attachments")
                    ) {
                        $viewerId = $tmpValue['res_id'];
                        unset($attachments[$tmpKey]);
                    }
                }
            }

            if (!empty($value['recipient_id'])) {
                if ($value['recipient_type'] == 'user') {
                    $attachments[$key]['recipient'] = UserModel::getLabelledUserById(['id' => $value['recipient_id']]);
                } elseif ($value['recipient_type'] == 'contact') {
                    $contactRaw = ContactModel::getById(['select' => ['firstname', 'lastname', 'company'], 'id' => $value['recipient_id']]);
                    $attachments[$key]['recipient'] = ContactController::getFormattedOnlyContact(['contact' => $contactRaw]);
                }
            }
            if (!empty($value['modified_by'])) {
                $attachments[$key]['modified_by'] = UserModel::getLabelledUserById(['id' => $value['modified_by']]);
            }
            if (!empty($value['typist'])) {
                $attachments[$key]['typist'] = UserModel::getLabelledUserById(['id' => $value['typist']]);
            }

            $attachments[$key]['canModify'] = false;
            $attachments[$key]['canDelete'] = false;
            if ($canManageAttachment || $value['typist'] == $args['userId']) {
                $attachments[$key]['canModify'] = true;
                $attachments[$key]['canDelete'] = true;
            }

            $attachments[$key]['creation_date'] = date(DATE_ATOM, strtotime($attachments[$key]['creation_date']));
            if ($attachments[$key]['validation_date']) {
                $attachments[$key]['validation_date'] = date(DATE_ATOM, strtotime($attachments[$key]['validation_date']));
            }
            if ($attachments[$key]['doc_date']) {
                $attachments[$key]['doc_date'] = date(DATE_ATOM, strtotime($attachments[$key]['doc_date']));
            }
            $attachments[$key]['isConverted']     = ConvertPdfController::canConvert(['extension' => $attachments[$key]['format']]);
            $attachments[$key]['viewerNoSignId']  = $viewerNoSignId;
            $attachments[$key]['attachment_type'] = $attachmentTypes[$value['attachment_type']]['label'];
            $attachments[$key]['icon']            = $attachmentTypes[$value['attachment_type']]['icon'];
            $attachments[$key]['sign']            = $attachmentTypes[$value['attachment_type']]['sign'];

            if ($value['status'] == 'SIGN') {
                $attachments[$key]['viewerLink'] = "../rest/attachments/{$viewerId}/content?".rand();
            } else {
                $attachments[$key]['viewerLink'] = "../rest/attachments/{$realId}/content?".rand();
            }
        }

        $obsAttachments = AttachmentModel::get([
            'select'    => ['res_id', 'origin_id', 'relation', 'creation_date', 'title'],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', 'status = ?'],
            'data'      => [$args['resId'], ['incoming_mail_attachment', 'signed_response'], 'OBS'],
            'orderBy'  => ['relation ASC']
        ]);

        $obsData = [];
        foreach ($obsAttachments as $value) {
            if ($value['relation'] == 1) {
                $obsData[$value['res_id']][] = ['resId' => $value['res_id'], 'title' => $value['title'], 'relation' => $value['relation'], 'creation_date' => $value['creation_date']];
            } else {
                $obsData[$value['origin_id']][] = ['resId' => $value['res_id'], 'title' => $value['title'], 'relation' => $value['relation'], 'creation_date' => $value['creation_date']];
            }
        }

        foreach ($attachments as $key => $value) {
            if ($value['attachment_type'] == 'signed_response') {
                unset($attachments[$key]);
                continue;
            }

            $attachments[$key]['obsAttachments'] = [];
            if ($value['relation'] > 1 && !empty($obsData[$value['origin_id']])) {
                $attachments[$key]['obsAttachments'] = $obsData[$value['origin_id']];
            }

            unset($attachments[$key]['path'], $attachments[$key]['filename'], $attachments[$key]['dest_user'],
                $attachments[$key]['dest_contact_id'], $attachments[$key]['dest_address_id'], $attachments[$key]['origin_id']
            );
        }

        $attachments = array_values($attachments);

        $resource = ResModel::getById(['resId' => $args['resId'], 'select' => ['res_id', 'subject', 'alt_identifier', 'filename', 'integrations', 'format']]);
        $integrations = json_decode($resource['integrations'], true);
        if (!empty($resource['filename']) && !empty($integrations['inSignatureBook'])) {
            array_unshift($attachments, $resource);
            $attachments[0]['isResource'] = true;
            $attachments[0]['attachment_type'] = _MAIN_DOCUMENT;
            $attachments[0]['title'] = $attachments[0]['subject'];
            $attachments[0]['sign'] = true;
            $attachments[0]['viewerLink'] = "../rest/resources/{$args['resId']}/content?".rand();

            $isSigned = AdrModel::getDocuments([
                'select'    => [1],
                'where'     => ['res_id = ?', 'type = ?'],
                'data'      => [$args['resId'], 'SIGN']
            ]);
            if (!empty($isSigned)) {
                $attachments[0]['status'] = 'SIGN';
            }

            $attachments[0]['isConverted'] = ConvertPdfController::canConvert(['extension' => $attachments[0]['format']]);
        }

        return $attachments;
    }

    public function getResources(Request $request, Response $response, array $aArgs)
    {
        $errors = ResourceListController::listControl(['groupId' => $aArgs['groupId'], 'userId' => $aArgs['userId'], 'basketId' => $aArgs['basketId'], 'currentUserId' => $GLOBALS['id']]);
        if (!empty($errors['errors'])) {
            return $response->withStatus($errors['code'])->withJson(['errors' => $errors['errors']]);
        }

        $basket = BasketModel::getById(['id' => $aArgs['basketId'], 'select' => ['basket_clause', 'basket_id', 'basket_name', 'basket_res_order']]);

        $user   = UserModel::getById(['id' => $aArgs['userId'], 'select' => ['user_id']]);
        $whereClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $user['user_id']]);
        $resources = ResModel::getOnView([
            'select'    => ['res_id', 'alt_identifier', 'subject', 'creation_date', 'process_limit_date', 'priority'],
            'where'     => [$whereClause],
            'orderBy'   => empty($basket['basket_res_order']) ? ['creation_date DESC'] : [$basket['basket_res_order']]
        ]);

        $resListForAttachments = [];
        $resIds = [];
        foreach ($resources as $value) {
            $resListForAttachments[$value['res_id']] = null;
            $resIds[] = $value['res_id'];
        }

        $attachmentsInResList = AttachmentModel::get([
            'select'    => ['res_id_master', 'status', 'attachment_type'],
            'where'     => ['res_id_master in (?)', "attachment_type not in ('incoming_mail_attachment', 'signed_response')", "status not in ('DEL', 'TMP', 'OBS')"],
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

    public function signResource(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route resId is not an integer']);
        } elseif (!SignatureBookController::isResourceInSignatureBook(['resId' => $args['resId'], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of signatory book']);
        }

        $body = $request->getParsedBody();
        if (!Validator::intVal()->notEmpty()->validate($body['signatureId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body signatureId is empty or not an integer']);
        }

        $signature = UserSignatureModel::getById(['id' => $body['signatureId'], 'select' => ['user_serial_id', 'signature_path', 'signature_file_name']]);
        if (empty($signature)) {
            return $response->withStatus(400)->withJson(['errors' => 'Signature does not exist']);
        } elseif ($signature['user_serial_id'] != $GLOBALS['id']) {
            return $response->withStatus(400)->withJson(['errors' => 'Signature out of perimeter']);
        }

        $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);
        if (empty($docserver['path_template']) || !is_dir($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver TEMPLATES does not exist']);
        }
        $signaturePath = $docserver['path_template'] . str_replace('#', '/', $signature['signature_path']) . $signature['signature_file_name'];
        if (!file_exists($signaturePath)) {
            return $response->withStatus(404)->withJson(['errors' => 'Signature not found on docserver']);
        }

        $convertedDocument = AdrModel::getDocuments([
            'select'    => ['docserver_id', 'path', 'filename', 'type'],
            'where'     => ['res_id = ?', 'type in (?)'],
            'data'      => [$args['resId'], ['PDF', 'SIGN']],
            'orderBy'   => ["type='SIGN' DESC", 'version DESC'],
            'limit'     => 1
        ]);
        if (empty($convertedDocument[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'Converted document does not exist']);
        } elseif ($convertedDocument[0]['type'] == 'SIGN') {
            return $response->withStatus(400)->withJson(['errors' => 'Document has already been signed']);
        }

        $convertedDocument = $convertedDocument[0];
        $docserver = DocserverModel::getByDocserverId(['docserverId' => $convertedDocument['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !is_dir($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }
        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedDocument['path']) . $convertedDocument['filename'];
        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/config.xml']);
        $width = (int)$loadedXml->CONFIG->width_blocsign ?? 150;
        $height = (int)$loadedXml->CONFIG->height_blocsign ?? 100;
        $tmpPath = CoreConfigModel::getTmpPath();

        $command = "java -jar modules/visa/dist/SignPdf.jar {$pathToDocument} {$signaturePath} {$width} {$height} {$tmpPath} 2> /dev/null";
        exec($command, $output, $return);

        $signedDocument = @file_get_contents($tmpPath.$convertedDocument['filename']);
        if ($signedDocument === false) {
            return $response->withStatus(400)->withJson(['errors' => 'Signature failed : ' . implode($output)]);
        }
        unlink($tmpPath.$convertedDocument['filename']);

        $storeResult = DocserverController::storeResourceOnDocServer([
            'collId'            => 'letterbox_coll',
            'docserverTypeId'   => 'DOC',
            'encodedResource'   => base64_encode($signedDocument),
            'format'            => 'pdf'
        ]);
        if (!empty($storeResult['errors'])) {
            return ['errors' => "[storeResourceOnDocServer] {$storeResult['errors']}"];
        }
        $resource = ResModel::getById(['resId' => $args['resId'], 'select' => ['version']]);
        AdrModel::createDocumentAdr([
            'resId'         => $args['resId'],
            'type'          => 'SIGN',
            'docserverId'   => $storeResult['docserver_id'],
            'path'          => $storeResult['directory'],
            'filename'      => $storeResult['file_destination_name'],
            'version'       => $resource['version'],
            'fingerprint'   => $storeResult['fingerPrint']
        ]);
        AdrModel::deleteDocumentAdr(['where' => ['res_id = ?', 'type = ?', 'version = ?'], 'data' => [$args['resId'], 'TNL', $resource['version']]]);

        ListInstanceModel::update([
            'set'   => ['signatory' => 'true'],
            'where' => ['res_id = ?', 'item_id = ?', 'difflist_type = ?'],
            'data'  => [$args['resId'], $GLOBALS['id'], 'VISA_CIRCUIT']
        ]);

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $args['resId'],
            'eventType' => 'SIGN',
            'eventId'   => 'resourceSign',
            'info'      => _DOCUMENT_SIGNED
        ]);

        return $response->withStatus(204);
    }

    public function unsignResource(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route resId is not an integer']);
        } elseif (!ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $resource = ResModel::getById(['resId' => $args['resId'], 'select' => ['typist', 'version']]);
        if ($resource['typist'] != $GLOBALS['id'] && !PrivilegeController::hasPrivilege(['privilegeId' => 'sign_document', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Privilege forbidden']);
        }

        AdrModel::deleteDocumentAdr(['where' => ['res_id = ?', 'type in (?)', 'version = ?'], 'data' => [$args['resId'], ['SIGN', 'TNL'], $resource['version']]]);

        if (!AttachmentModel::hasAttachmentsSignedByResId(['resId' => $args['resId'], 'userId' => $GLOBALS['id']])) {
            ListInstanceModel::update([
                'set'   => ['signatory' => 'false'],
                'where' => ['res_id = ?', 'item_id = ?', 'difflist_type = ?'],
                'data'  => [$args['resId'], $GLOBALS['id'], 'VISA_CIRCUIT']
            ]);
        }

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $args['resId'],
            'eventType' => 'UNSIGN',
            'eventId'   => 'resourceUnsign',
            'info'      => _DOCUMENT_UNSIGNED
        ]);

        return $response->withStatus(204);
    }

    public function signAttachment(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not an integer']);
        }

        $attachment = AttachmentModel::getById(['id' => $args['id'], 'select' => ['res_id_master', 'title', 'typist', 'identifier', 'recipient_id', 'recipient_type']]);
        if (empty($attachment)) {
            return $response->withStatus(403)->withJson(['errors' => 'Attachment out of perimeter']);
        } elseif (!SignatureBookController::isResourceInSignatureBook(['resId' => $attachment['res_id_master'], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of signatory book']);
        }

        $body = $request->getParsedBody();
        if (!Validator::intVal()->notEmpty()->validate($body['signatureId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body signatureId is empty or not an integer']);
        }

        $signature = UserSignatureModel::getById(['id' => $body['signatureId'], 'select' => ['user_serial_id', 'signature_path', 'signature_file_name']]);
        if (empty($signature)) {
            return $response->withStatus(400)->withJson(['errors' => 'Signature does not exist']);
        } elseif ($signature['user_serial_id'] != $GLOBALS['id']) {
            return $response->withStatus(400)->withJson(['errors' => 'Signature out of perimeter']);
        }

        $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);
        if (empty($docserver['path_template']) || !is_dir($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver TEMPLATES does not exist']);
        }
        $signaturePath = $docserver['path_template'] . str_replace('#', '/', $signature['signature_path']) . $signature['signature_file_name'];
        if (!file_exists($signaturePath)) {
            return $response->withStatus(404)->withJson(['errors' => 'Signature not found on docserver']);
        }

        $convertedDocument = AdrModel::getAttachments([
            'select'    => ['docserver_id', 'path', 'filename', 'type'],
            'where'     => ['res_id = ?', 'type = ?'],
            'data'      => [$args['id'], 'PDF']
        ]);
        if (empty($convertedDocument[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'Converted document does not exist']);
        }

        $convertedDocument = $convertedDocument[0];
        $docserver = DocserverModel::getByDocserverId(['docserverId' => $convertedDocument['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !is_dir($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }
        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedDocument['path']) . $convertedDocument['filename'];
        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/config.xml']);
        $width = (int)$loadedXml->CONFIG->width_blocsign ?? 150;
        $height = (int)$loadedXml->CONFIG->height_blocsign ?? 100;
        $tmpPath = CoreConfigModel::getTmpPath();

        $command = "java -jar modules/visa/dist/SignPdf.jar {$pathToDocument} {$signaturePath} {$width} {$height} {$tmpPath} 2> /dev/null";
        exec($command, $output, $return);

        $signedDocument = @file_get_contents($tmpPath.$convertedDocument['filename']);
        if ($signedDocument === false) {
            return $response->withStatus(400)->withJson(['errors' => 'Signature failed : ' . implode($output)]);
        }
        unlink($tmpPath.$convertedDocument['filename']);

        $data = [
            'title'             => $attachment['title'],
            'encodedFile'       => base64_encode($signedDocument),
            'format'            => 'pdf',
            'typist'            => $attachment['typist'],
            'resIdMaster'       => $attachment['res_id_master'],
            'chrono'            => $attachment['identifier'],
            'type'              => 'signed_response',
            'originId'          => $args['id'],
            'recipientId'       => $attachment['recipient_id'],
            'recipientType'     => $attachment['recipient_type'],
            'inSignatureBook'   => true
        ];
        $id = StoreController::storeAttachment($data);
        if (!empty($id['errors'])) {
            return ['errors' => $id['errors']];
        }

        AttachmentModel::update([
            'set'   => ['status' => 'SIGN'],
            'where' => ['res_id = ?'],
            'data'  => [$args['id']]
        ]);

        ListInstanceModel::update([
            'set'   => ['signatory' => 'true'],
            'where' => ['res_id = ?', 'item_id = ?', 'difflist_type = ?'],
            'data'  => [$attachment['res_id_master'], $GLOBALS['id'], 'VISA_CIRCUIT']
        ]);

        HistoryController::add([
            'tableName' => 'res_attachments',
            'recordId'  => $args['id'],
            'eventType' => 'SIGN',
            'eventId'   => 'attachmentSign',
            'info'      => _DOCUMENT_SIGNED
        ]);

        return $response->withJson(['id' => $id]);
    }

    public function unsignAttachment(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not an integer']);
        }

        $attachment = AttachmentModel::getById(['id' => $args['id'], 'select' => ['res_id_master', 'typist']]);
        if (empty($attachment) || !ResController::hasRightByResId(['resId' => [$attachment['res_id_master']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        } elseif ($attachment['typist'] != $GLOBALS['id'] && !PrivilegeController::hasPrivilege(['privilegeId' => 'sign_document', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Privilege forbidden']);
        }

        AttachmentModel::update([
            'set'       => ['status' => 'A_TRA', 'signatory_user_serial_id' => null],
            'where'     => ['res_id = ?'],
            'data'      => [$args['id']]
        ]);
        AttachmentModel::update([
            'set'       => ['status' => 'DEL'],
            'where'     => ['origin = ?', 'status != ?'],
            'data'      => ["{$args['id']},res_attachments", 'DEL']
        ]);

        if (!AttachmentModel::hasAttachmentsSignedByResId(['resId' => $attachment['res_id_master'], 'userId' => $GLOBALS['id']])) {
            ListInstanceModel::update([
                'set'   => ['signatory' => 'false'],
                'where' => ['res_id = ?', 'item_id = ?', 'difflist_type = ?'],
                'data'  => [$attachment['res_id_master'], $GLOBALS['id'], 'VISA_CIRCUIT']
            ]);
        }

        HistoryController::add([
            'tableName' => 'res_attachments',
            'recordId'  => $args['id'],
            'eventType' => 'UNSIGN',
            'eventId'   => 'attachmentUnsign',
            'info'      => _DOCUMENT_UNSIGNED
        ]);

        return $response->withStatus(204);
    }

    public static function isResourceInSignatureBook(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'userId']);
        ValidatorModel::intVal($args, ['resId', 'userId']);

        $currentUser = UserModel::getById(['id' => $args['userId'], 'select' => ['id', 'user_id']]);

        $basketsClause = '';

        $groups = UserGroupModel::get(['select' => ['group_id'], 'where' => ['user_id = ?'], 'data' => [$currentUser['id']]]);
        $groups = array_column($groups, 'group_id');
        if (!empty($groups)) {
            $groups = GroupModel::get(['select' => ['group_id'], 'where' => ['id in (?)'], 'data' => [$groups]]);
            $groups = array_column($groups, 'group_id');

            $baskets = GroupBasketModel::get(['select' => ['basket_id'], 'where' => ['group_id in (?)', 'list_event = ?'], 'data' => [$groups, 'signatureBookAction']]);
            $baskets = array_column($baskets, 'basket_id');
            if (!empty($baskets)) {
                $clauses = BasketModel::get(['select' => ['basket_clause'], 'where' => ['basket_id in (?)'], 'data' => [$baskets]]);

                foreach ($clauses as $clause) {
                    $basketClause = PreparedClauseController::getPreparedClause(['clause' => $clause['basket_clause'], 'login' => $currentUser['user_id']]);
                    if (!empty($basketsClause)) {
                        $basketsClause .= ' or ';
                    }
                    $basketsClause .= "({$basketClause})";
                }
            }
        }

        $assignedBaskets = RedirectBasketModel::getAssignedBasketsByUserId(['userId' => $currentUser['id']]);
        foreach ($assignedBaskets as $basket) {
            $hasSB = GroupBasketModel::get(['select' => [1], 'where' => ['basket_id = ?', 'group_id = ?', 'list_event = ?'], 'data' => [$basket['basket_id'], $basket['oldGroupId'], 'signatureBookAction']]);
            if (!empty($hasSB)) {
                $basketOwner = UserModel::getById(['id' => $basket['owner_user_id'], 'select' => ['user_id']]);
                $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $basketOwner['user_id']]);
                if (!empty($basketsClause)) {
                    $basketsClause .= ' or ';
                }
                $basketsClause .= "({$basketClause})";
            }
        }

        try {
            $res = ResModel::getOnView(['select' => [1], 'where' => ['res_id = ?', "({$basketsClause})"], 'data' => [$args['resId']]]);
            if (empty($res)) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
