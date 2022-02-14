<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief fastParapheur via SMTP Controller
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Attachment\models\AttachmentModel;
use Attachment\controllers\AttachmentController;
use SrcCore\models\ValidatorModel;
use Email\controllers\EmailController;
use Configuration\models\ConfigurationModel;
use SrcCore\models\CoreConfigModel;
use History\controllers\HistoryController;
use \SrcCore\models\DatabaseModel;
use Convert\controllers\ConvertPdfController;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Doctype\models\DoctypeModel;
use Resource\models\ResModel;
use Resource\controllers\StoreController;
use User\models\UserModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\CurlModel;

/**
    * @codeCoverageIgnore
*/
class FastParapheurSmtpController 
{
    /**
     * Prepare data before sending to Fast Parapheur via email
     * 
     * @param   array   $args
     * @return  array   
     */
    public static function sendDatas(array $args)
    {
        $config = $args['config'];
        $_TOTAL_EMAIL_SIZE = FastParapheurSmtpController::convertSizeToBytes([
            'size'      => explode("|", $config['data']['emailSize'])[0],
            'format'    => explode("|", $config['data']['emailSize'])[1]
        ]);

        // We need the SIRET field and the user_id of the signatory user's primary entity
        $signatory = DatabaseModel::select([
            'select'    => ['user_id', 'business_id', 'entities.entity_label'],
            'table'     => ['listinstance', 'users_entities', 'entities'],
            'left_join' => ['item_id = user_id', 'users_entities.entity_id = entities.entity_id'],
            'where'     => ['res_id = ?', 'item_mode = ?', 'process_date is null'],
            'data'      => [$args['resIdMaster'], 'sign']
        ])[0];
        $redactor = DatabaseModel::select([
            'select'    => ['short_label'],
            'table'     => ['res_view_letterbox', 'users_entities', 'entities'],
            'left_join' => ['dest_user = user_id', 'users_entities.entity_id = entities.entity_id'],
            'where'     => ['res_id = ?'],
            'data'      => [$args['resIdMaster']]
        ])[0];

        if (empty($signatory['business_id']) || substr($signatory['business_id'], 0, 3) == 'org') {
            $signatory['business_id'] = $config['data']['subscriberId'];
        }

        if (!empty($signatory['user_id'])) {
            $user = UserModel::getById(['id' => $signatory['user_id'], 'select' => ['user_id']]);
        }

        if (empty($user)) {
            return ['error' => _CONFIGURE_VISA_CIRCUIT];
        }

        $prepareUpload = FastParapheurSmtpController::prepareUpload([
            'config'        => $config, 
            'resIdMaster'   => $args['resIdMaster'], 
            'businessId'    => $signatory['business_id'], 
            'circuitId'     => $user['user_id'], 
            'label'         => $redactor['short_label'],
            'notes'         => $args['note'],
            'sizeLimit'     => $_TOTAL_EMAIL_SIZE
        ]);

        if (!empty($prepareUpload['error'])) {
            AttachmentModel::delete([
                'where' => ['res_id = ?'],
                'data'  => [$prepareUpload['jsonRequest']['id']]
            ]);
            return ['error' => $prepareUpload['error']];
        }

        return ['sended' => $prepareUpload['sended'], 'historyInfos' => $prepareUpload['historyInfos']];
    }

    /**
     * Prepare letterbox, attachments (to sign) and annexes data
     * 
     * @param   array   $args
     * @return  array
     */
    public static function prepareUpload(array $args)
    {
        ValidatorModel::notEmpty($args, ['config', 'circuitId', 'label', 'resIdMaster']);
        ValidatorModel::intVal($args, ['resIdMaster', 'sizeLimit']);
        ValidatorModel::arrayType($args, ['config']);

        $circuitId    = $args['circuitId'];
        $label        = $args['label'];
        $subscriberId = $args['businessId'];

        $documentsToSign = [];
        $documentsToSign['letterbox'] = ResModel::get([
            'select' => ['res_id', 'path', 'subject', 'filename', 'filesize', 'fingerprint', 'docserver_id', 'format', 'category_id', 'external_id', 'integrations'],
            'where'  => ['res_id = ?', 'filesize < ?'],
            'data'   => [$args['resIdMaster'], $args['sizeLimit']]
        ]);

        if (!empty($documentsToSign['letterbox'][0]['docserver_id'])) {
            $adrMainInfo   = ConvertPdfController::getConvertedPdfById(['resId' => $args['resIdMaster'], 'collId' => 'letterbox_coll']);
            $letterboxPath = DocserverModel::getByDocserverId(['docserverId' => $adrMainInfo['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
            $docserverType = DocserverTypeModel::getById(['id' => $letterboxPath['docserver_type_id'], 'select' => ['fingerprint_mode']]);
            $documentsToSign['letterbox'][0]['fingerprint_mode'] = $docserverType['fingerprint_mode'];
            $documentsToSign['letterbox'][0]['filePath'] = $letterboxPath['path_template'] . str_replace('#', '/', $adrMainInfo['path']) . $adrMainInfo['filename'];
        }

        $attachments = AttachmentModel::get([
            'select'    => ['res_id as id', '(select false) as original', 'title', 'filesize', 'docserver_id', 'path', 'filename', 'format', 'attachment_type', 'fingerprint'],
            'where'     => ["res_id_master = ?", "attachment_type not in (?)", "status not in ('DEL', 'OBS', 'FRZ', 'TMP', 'SEND_MASS')", "in_signature_book = 'true'"],
            'data'      => [$args['resIdMaster'], ['signed_response', 'response_json']]
        ]);
        
        $annexes = [];
        $documentsToSign['attachments'] = [];
        $attachmentTypes = AttachmentModel::getAttachmentsTypesByXML();
        foreach ($attachments as $key => $value) {

            $annexeAttachmentPath = DocserverModel::getByDocserverId(['docserverId' => $value['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
            $value['filePath']    = $annexeAttachmentPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $value['path']) . $value['filename'];

            $docserverType = DocserverTypeModel::getById(['id' => $annexeAttachmentPath['docserver_type_id'], 'select' => ['fingerprint_mode']]);
            $value['fingerprint_mode'] = $docserverType['fingerprint_mode'];
            $fingerprint = StoreController::getFingerPrint(['filePath' => $value['filePath'], 'mode' => $docserverType['fingerprint_mode']]);
            if ($value['fingerprint'] != $fingerprint) {
                return ['error' => 'Fingerprints do not match'];
            }
            
            if (!$attachmentTypes[$value['attachment_type']]['sign']) {
                
                unset($attachments[$key]);
                $annexes[] = $value;
            }
            else{
                $documentsToSign['attachments'][]= $value;
            }
        }

        $documentsToSign['annexes'] = FastParapheurSmtpController::filterMainDocumentAttachments(['attachments' => $annexes, 'sizeLimit' => $args['sizeLimit']]);

        $count = 0;
        if (!empty($documentsToSign['letterbox'])) {
            // make request json
            $jsonRequest = FastParapheurSmtpController::makeJsonRequest([
                'res_id'        => $documentsToSign['letterbox'][0]['res_id'],
                'clientDocType' => 'mainDocument',
                'documentName'  => $documentsToSign['letterbox'][0]['subject'] . '.' . $documentsToSign['letterbox'][0]['format'], 
                'fingerprint'   => $documentsToSign['letterbox'][0]['fingerprint'], 
                'hashAlgorithm' => $documentsToSign['letterbox'][0]['fingerprint_mode'], 
                'circuitId'     => $circuitId,
                'subscriberId'  => $subscriberId,
                'label'         => $label,
                'note'          => $args['note'], 
                'attachments'   => $documentsToSign['annexes']['jsonAttachments'],
                'resIdMaster'   => $args['resIdMaster'],
            ]);
            if (!empty($jsonRequest['error'])) {
                return ['error' => $jsonRequest['error']];
            }

            $_annexes  = $documentsToSign['annexes']['emailAttachments'];
            $_annexes[]= $jsonRequest;

            $resultEmail = FastParapheurSmtpController::uploadEmail([
                'config'            => $args['config'],
                'note'              => $args['note'],
                'documentsToSign'   => $documentsToSign['letterbox'][0],
                'documentType'      => 'letterbox',
                'attachments'       => $_annexes,
                'resIdMaster'       => $args['resIdMaster'],
                'circuitId'         => $circuitId,
                'label'             => $label,
                'subscriberId'      => $subscriberId,
            ]);

            if(!empty($resultEmail['error'])) {
                return ['error' => $resultEmail['error'], 'jsonRequest' => $jsonRequest];
            }
            $count++;
        }

        
        if (!empty($documentsToSign['attachments'])) {
            foreach ($documentsToSign['attachments'] as $document) {
             
                // make request json
                $jsonRequest = FastParapheurSmtpController::makeJsonRequest([
                    'res_id'        => $document['id'],
                    'clientDocType'  => 'attachment',
                    'documentName'  => $document['title'] . '.' . $document['format'], 
                    'fingerprint'   => $document['fingerprint'], 
                    'hashAlgorithm' => $document['fingerprint_mode'], 
                    'circuitId'     => $circuitId,
                    'subscriberId'  => $subscriberId,
                    'label'         => $label,
                    'note'          => $args['note'], 
                    'attachments'   => $documentsToSign['annexes']['jsonAttachments'],
                    'resIdMaster'   => $args['resIdMaster'],
                ]);

                if (!empty($jsonRequest['error'])) {
                    return ['error' => $jsonRequest['error']];
                }
    
                $_annexes = $documentsToSign['annexes']['emailAttachments'];
                $_annexes[]= $jsonRequest;
    
                $resultEmail = FastParapheurSmtpController::uploadEmail([
                    'config'            => $args['config'],
                    'note'              => $args['note'],
                    'documentsToSign'   => $document,
                    'documentType'      => 'attachments',
                    'attachments'       => $_annexes,
                    'resIdMaster'       => $args['resIdMaster'],
                    'circuitId'         => $circuitId,
                    'label'             => $label,
                    'subscriberId'      => $subscriberId,
                ]);

                if(!empty($resultEmail['error'])) {
                    return ['error' => $resultEmail['error'], 'jsonRequest' => $jsonRequest];
                }
                $count++;
            }
        }

        return ['sended' => $resultEmail['success'], 'historyInfos' => " $count email(s) was send successfully"];
    }

    /**
     * Create and send mail
     * 
     * @param   array   $args
     * @return  array
     */
    public static function uploadEmail($args)
    {
        ValidatorModel::notEmpty($args, ['config', 'circuitId', 'label', 'resIdMaster']);
        ValidatorModel::intVal($args, ['resIdMaster', 'sizeLimit']);
        ValidatorModel::arrayType($args, ['config']);

        $smtpConfig = ConfigurationModel::getByPrivilege(['privilege' => 'admin_email_server', 'select' => ['value']]);

        if (empty($smtpConfig)) {
            return ['error' => 'Mail server settings are missing!', 'historyInfos' => ' Mail server settings are missing!'];
        }
        $smtpConfig = json_decode($smtpConfig['value'], true);

        $document;
        if ($args['documentType'] == 'letterbox') {
            $document['id'] = $args['documentsToSign']['res_id'];
            $document['isLinked'] = true;
        } elseif ($args['documentType'] == 'attachments') {
            $document['id'] = $args['resIdMaster'];
            $document['isLinked'] = false;
        } else {
            return ['error' => 'documentType'];
        }

        // create email and send it
        $emailId = EmailController::createEmail([
            'userId'    => $GLOBALS['id'],
            'data'      => [
                'sender'        => ['email' => $smtpConfig['from']],
                'recipients'    => [$args['config']['data']['email']],
                'object'        => $args['config']['data']['subject'],
                'body'          => (empty($args['documentsToSign']['note']) ? '' : 'Annotation du documment ' . $args['documentsToSign']['note']),
                'document'      => ['id' => $document['id'], 'isLinked' => $document['isLinked'], 'original' => false, 'attachments' => $args['attachments']],
                'isHtml'        => true,
                'status'        => 'EXPRESS'
            ]
        ]);

        if (!empty($emailId['errors'])) {
            return ['error' => $emailId['errors']];
        }

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $document['id'],
            'eventType' => 'UP',
            'info'      => _SEND_TO_EXTERNAL_SB . ' : ' . _FAST_PARAPHEUR_SMTP,
            'eventId'   => 'sendToFastParapheurSmtp'                
        ]);
        
        return ['success' => 'success'];
    }
    
    /**
     * Filter attachments by checking the sizeLimit and prepare two arrays
     * 
     * @param   array   $args   sizeLimit(int) attachments(array)
     * @return  array   jsonAttachments and emailAttachments
     */
    public static function filterMainDocumentAttachments($args) 
    {
        ValidatorModel::notEmpty($args, ['sizeLimit']);
        ValidatorModel::arrayType($args, ['attachments']);
        ValidatorModel::intVal($args, ['sizeLimit']);

        $jsonAttachments  = [];
        $emailAttachments = [];
        $currentSize      = $args['sizeLimit'];

        foreach ($args['attachments'] as $key => $attachment) {

            if (filesize($attachment['filePath']) > 0 && $attachment['filesize'] < $currentSize) {
                
                $jsonAttachments[]= array(
                    "name"          => $attachment['title'] . '.' . $attachment['format'],
                    "hash"          => $attachment['fingerprint'],
                    "hashAlgorithm" => $attachment['fingerprint_mode']
                );
                $emailAttachments[]= array(
                    "id"        => $attachment['id'],
                    "original"  => $attachment['original']
                );
                $currentSize = $currentSize - $attachment['filesize'];
            }
        }

        return ['jsonAttachments' => $jsonAttachments, 'emailAttachments' => $emailAttachments];
    }

    /**
     * Create response json
     * 
     * @param   array   $args   resIdMaster(int) res_id(int) circuitId subscriberId
     * @return  array   attachment id && original
     */
    public static function makeJsonRequest($args) {
        ValidatorModel::notEmpty($args, ['resIdMaster', 'res_id', 'circuitId', 'subscriberId']);
        ValidatorModel::intVal($args, ['resIdMaster', 'res_id']);

        $jsonRequest = [
            "clientDocId"   => $args['res_id'],
            "clientDocType" => $args['clientDocType'],
            "documentName"  => $args['documentName'],
            "documentHash"  => $args['fingerprint'],
            "hashAlgorithm" => $args['hashAlgorithm'],
            "circuitId"     => $args['circuitId'],
            "businessId"    => $args['subscriberId'],
            "label"         => $args['label'],
            "comment"       => empty($args['note']) ? '' : $args['note'],
            "annexes"       => $args['attachments']
        ];

        $body = [
            'title'         => 'response',
            'resIdMaster'   => $args['resIdMaster'],
            'type'          => 'response_json',
            'format'        => 'JSON',
            'typist'        => $GLOBALS['id'],
            'encodedFile'   => base64_encode(json_encode($jsonRequest))
        ];

        $id = StoreController::storeAttachment($body);
        if (empty($id) || !empty($id['errors'])) {
            return ['error' => '[FastParapheurSmtpController makeJsonRequest] ' . $id['errors']];
        }

        if (!empty($response['errors'])) {
            return ['error' => $response['errors']];
        }

        return [
            "id" => $id,
            "original" => true
        ];
    }

    /**
     * 
     * @param   array       $args   size(int) format(string)
     * @return  int|array   converted size or error
     */
    public static function convertSizeToBytes($args) {
        ValidatorModel::notEmpty($args, ['size', 'format']);
        ValidatorModel::intVal($args, ['size']);
        ValidatorModel::stringType($args, ['format']);

        switch ($args['format']) {
            case 'O':
                return $args['size'];
            case 'Ko':
                return $args['size'] * 1000;
            case 'Mo':
                return $args['size'] * 1000000;
            case 'Go':
                return $args['size'] * 1000000000;
            case 'To':
                return $args['size'] * 1000000000000;
            default:
                return ['error' => "Unknown format to convert it's value"];
        }
    }
}