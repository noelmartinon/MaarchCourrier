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
use SrcCore\models\DatabaseModel;
use Convert\controllers\ConvertPdfController;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Doctype\models\DoctypeModel;
use Resource\models\ResModel;
use Resource\controllers\StoreController;
use User\models\UserModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\CurlModel;
use Group\controllers\PrivilegeController;
use Resource\controllers\ResourceControlController;
use Respect\Validation\Validator;
use Docserver\controllers\DocserverController;
use Entity\models\ListInstanceModel;

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

        $smtpConfig = ConfigurationModel::getByPrivilege(['privilege' => 'admin_email_server', 'select' => ['value']]);
        if (empty($smtpConfig)) {
            return ['error' => 'Mail server settings are missing!', 'historyInfos' => ' Mail server settings are missing!'];
        }
        $smtpConfig = json_decode($smtpConfig['value'], true);

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
            'smtpConfig'    => $smtpConfig,
            'resIdMaster'   => $args['resIdMaster'], 
            'businessId'    => $config['data']['subscriberId'], 
            'circuitId'     => $user['user_id'],
            'label'         => $redactor['short_label'],
            'note'         => $args['note'],
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
        ValidatorModel::notEmpty($args, ['config', 'smtpConfig', 'circuitId', 'label', 'resIdMaster']);
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
            'data'      => [$args['resIdMaster'], ['signed_response', 'request_json']]
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
            } else{
                $documentsToSign['attachments'][]= $value;
            }
        }

        $count = 0;
        $sizeLimit = $args['sizeLimit'];
        if (!empty($documentsToSign['letterbox'])) {

            // Send main document if in signature book
            $mainDocumentIntegration = json_decode($documentsToSign['letterbox'][0]['integrations'], true);
            $externalId              = json_decode($documentsToSign['letterbox'][0]['external_id'], true);
            if ($mainDocumentIntegration['inSignatureBook'] && empty($externalId['signatureBookId'])) {
                // check size
                if ($sizeLimit < $documentsToSign['letterbox'][0]['filesize']) {
                    HistoryController::add([
                        'tableName' => 'res_letterbox',
                        'recordId'  => $documentsToSign['letterbox'][0]['res_id'],
                        'eventType' => 'UP',
                        'info'      => _SEND_TO_EXTERNAL_SB . ' - ' . _FAST_PARAPHEUR_SMTP . ' : The main document size \'' . $documentsToSign['letterbox'][0]['subject'] . '\' was too heavy to send via email',
                        'eventId'   => 'sendToFastParapheurSmtp'                
                    ]);
                } else {
                    $sizeLimit = $sizeLimit - $documentsToSign['letterbox'][0]['filesize'];

                    $documentsToSign['annexes'] = FastParapheurSmtpController::filterMainDocumentAttachments(['attachments' => $annexes, 'sizeLimit' => $sizeLimit]);

                    // make request json
                    $jsonRequest = FastParapheurSmtpController::makeJsonRequest([
                        'res_id'        => $documentsToSign['letterbox'][0]['res_id'],
                        'clientDocType' => 'mainDocument',
                        'documentName'  => $documentsToSign['letterbox'][0]['subject'] . '.pdf', 
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
                        'smtpConfig'        => $args['smtpConfig'],
                        'note'              => $args['note'],
                        'documentsToSign'   => $documentsToSign['letterbox'][0],
                        'documentType'      => 'letterbox',
                        'attachments'       => $_annexes,
                        'resIdMaster'       => $args['resIdMaster'],
                        'label'             => $label,
                        'subscriberId'      => $subscriberId,
                    ]);

                    if(!empty($resultEmail['error'])) {
                        return ['error' => $resultEmail['error'], 'jsonRequest' => $jsonRequest];
                    }
                    $count++;
                }
            }
        }


        $sizeLimit = $args['sizeLimit'];
        if (!empty($documentsToSign['attachments'])) {
            foreach ($documentsToSign['attachments'] as $document) {
                
                // check size
                if ($sizeLimit < $document['filesize']) {
                    HistoryController::add([
                        'tableName' => 'res_letterbox',
                        'recordId'  => $document['id'],
                        'eventType' => 'UP',
                        'info'      => _SEND_TO_EXTERNAL_SB . ' - ' . _FAST_PARAPHEUR_SMTP . ' : The attachment size \'' . $document['title'] . '\' was too heavy to send via email',
                        'eventId'   => 'sendToFastParapheurSmtp'                
                    ]);
                    continue;
                }
                $sizeLimit = $sizeLimit - $document['filesize'];

                $documentsToSign['annexes'] = FastParapheurSmtpController::filterMainDocumentAttachments(['attachments' => $annexes, 'sizeLimit' => $sizeLimit]);

                // make request json
                $jsonRequest = FastParapheurSmtpController::makeJsonRequest([
                    'res_id'        => $document['id'],
                    'clientDocType'  => 'attachment',
                    'documentName'  => $document['title'] . '.pdf', 
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
                    'smtpConfig'        => $args['smtpConfig'],
                    'config'            => $args['config'],
                    'note'              => $args['note'],
                    'documentsToSign'   => $document,
                    'documentType'      => 'attachments',
                    'attachments'       => $_annexes,
                    'resIdMaster'       => $args['resIdMaster'],
                    'label'             => $label,
                    'subscriberId'      => $subscriberId,
                ]);

                if(!empty($resultEmail['error'])) {
                    return ['error' => $resultEmail['error'], 'jsonRequest' => $jsonRequest];
                }
                $count++;
            }
        }

        return ['sended' => $resultEmail['success'], 'historyInfos' => ", $count email(s) was send successfully"];
    }

    /**
     * Create and send mail
     * 
     * @param   array   $args
     * @return  array
     */
    public static function uploadEmail(array $args)
    {
        ValidatorModel::notEmpty($args, ['config', 'label', 'resIdMaster', 'smtpConfig']);
        ValidatorModel::intVal($args, ['resIdMaster', 'sizeLimit']);
        ValidatorModel::arrayType($args, ['config']);

        $document = [];
        if ($args['documentType'] == 'letterbox') {
            $document['id'] = $args['documentsToSign']['res_id'];
            $document['isLinked'] = true;
            $document['attachments'] = $args['attachments'];
        } elseif ($args['documentType'] == 'attachments') {
            $document['id'] = $args['resIdMaster'];
            $document['isLinked'] = false; // to not link letterbox pdf to the email
            $document['attachments'] = $args['attachments'];
            $document['attachments'][]= ['id' => $args['documentsToSign']['id'], 'original' => $args['documentsToSign']['original']];
        } else {
            return ['error' => 'documentType'];
        }

        // create email and send it
        $emailId = EmailController::createEmail([
            'userId'    => $GLOBALS['id'],
            'data'      => [
                'sender'        => ['email' => $args['smtpConfig']['from']],
                'recipients'    => [$args['config']['data']['email']],
                'object'        => $args['config']['data']['subject'],
                'body'          => (empty($args['note']) ? '' : $args['note']),
                'document'      => ['id' => $document['id'], 'isLinked' => $document['isLinked'], 'original' => false, 'attachments' => $document['attachments']],
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
    public static function filterMainDocumentAttachments(array $args) 
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
                    "name"          => $attachment['title'] . '.pdf',
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
     * Create json request
     * 
     * @param   array   $args   resIdMaster(int) res_id(int) circuitId subscriberId
     * @return  array   attachment id && original
     */
    public static function makeJsonRequest(array $args) 
    {
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
            'title'         => 'request',
            'resIdMaster'   => $args['resIdMaster'],
            'type'          => 'request_json',
            'format'        => 'JSON',
            'typist'        => $GLOBALS['id'],
            'encodedFile'   => base64_encode(json_encode($jsonRequest, JSON_PRETTY_PRINT))
        ];

        $id = StoreController::storeAttachment($body);
        if (empty($id) || !empty($id['errors'])) {
            return ['error' => '[FastParapheurSmtpController makeJsonRequest] ' . $id['errors']];
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
    public static function convertSizeToBytes(array $args) 
    {
        ValidatorModel::notEmpty($args, ['size', 'format']);
        ValidatorModel::intVal($args, ['size']);
        ValidatorModel::stringType($args, ['format']);

        switch ($args['format']) {
            case 'O':
                return $args['size'];
            case 'Ko':
                return $args['size'] * 1024;
            case 'Mo':
                return $args['size'] * (1024*1024);
            case 'Go':
                return $args['size'] * (1024*1024*1024);
            case 'To':
                return $args['size'] * (1024*1024*1024*1024);
            default:
                return ['error' => "Unknown format to convert it's value"];
        }
    }

    /**
     * API endpoint, to notify letterbox status and if status is signed, then create new sign version document/attachment
     */
    public function createDocument(Request $request, Response $response) 
    {
        $body = $request->getParsedBody();
        $body = StoreController::setDisabledFields($body);
        
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        $config = [];

        if (empty($loadedXml)) {
            return $response->withStatus(404)->withJson(['Remote Signatory Books configuration is missing!']);
        }
        $config['id'] = (string)$loadedXml->signatoryBookEnabled;
        foreach ($loadedXml->signatoryBook as $value) {
            if ($value->id == $config['id']) {
                $config['data'] = (array)$value;
                break;
            }
        }

        if (empty($body)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body is not set or empty']);
        } elseif (!Validator::arrayType()->notEmpty()->validate($body['metadata'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body metadata is missing or not an array']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['metadata']['clientDocId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body metadata clientDocId is missing or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['metadata']['clientDocType'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body metadata clientDocType is missing or not a string']);
        } elseif(!in_array($body['metadata']['clientDocType'], ['mainDocument', 'attachment'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body metadata \'clientDocType\' require \'mainDocument\' or \'attachment\' as value']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['encodedFile'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body encodedFile is missing, empty or not a string']);
        }

        $result = [];

        if ($body['metadata']['status']['type'] == $config['data']['errorState']) {

            $res = FastParapheurSmtpController::documentErrorState([
                'metadata'      => $body['metadata'],
                'encodedFile'   => $body['encodedFile']
            ]);
            if (!empty($res['error'])) {
                return $response->withStatus($res['code'])->withJson(['errors' => $res['error']]);
            }
            $result = ['info' => 'Document was updated with the error', 'code' => 200];

        } elseif ($body['metadata']['status']['type'] == $config['data']['refusedState']) {

            $res = FastParapheurSmtpController::documentRefusedState([
                'metadata'      => $body['metadata'],
                'encodedFile'   => $body['encodedFile']
            ]);
            if (!empty($res['error'])) {
                return $response->withStatus($res['code'])->withJson(['errors' => $res['error']]);
            }
            $result = ['info' => 'Refused document was updated', 'code' => 200];

        } elseif ($body['metadata']['status']['type'] == $config['data']['signedState']) {

            $res = FastParapheurSmtpController::documentSignedState([
                'metadata'      => $body['metadata'],
                'encodedFile'   => $body['encodedFile']
            ]);
            if (!empty($res['error'])) {
                return $response->withStatus($res['code'])->withJson(['errors' => $res['error']]);
            }
            $result = ['info' => 'Signed document created', 'code' => 201];
        }
        else {
            return $response->withStatus(400)->withJson(['The query syntax is incorrect.']);
        }

        return $response->withStatus($result['code'])->withJson($result['info']);
    }

    /**
     * Change letterbox/attachment document state and notify the user
     * 
     * @param   array   $args   metadata(array) encodedFile(string)
     * @return  void|array      return an array if an error occurs
     */
    public static function documentErrorState(array $args) {

        if (!Validator::stringType()->notEmpty()->validate($args['metadata']['clientDocId'])) {
            return ['error' => 'Body metadata clientDocId is missing, empty or not a string', 'code' => 400];
        } elseif (!Validator::stringType()->notEmpty()->validate($args['metadata']['clientDocType'])) {
            return ['error' => 'Body metadata clientDocType is missing, empty or not a string', 'code' => 400];
        } elseif (!Validator::stringType()->notEmpty()->validate($args['encodedFile'])) {
            return ['error' => 'Body encodedFile is missing, empty or not a string', 'code' => 400];
        }

        if ($args['metadata']['clientDocType'] == 'mainDocument') {
            $resLetterbox = ResModel::get([
                'select' => ['*'],
                'where'  => ['res_id = ?'],
                'data'   => [$args['metadata']['clientDocId']]
            ])[0];
            if (empty($resLetterbox)) {
                return ['error' => 'client document id \'' . $args['metadata']['clientDocId'] . '\' does not exist!'];
            }

            $jsonResponse = FastParapheurSmtpController::makeJsonResonse([
                'resIdMaster'   => $resLetterbox['res_id_master'],
                'typist'        => $resLetterbox['typist'],
                'metadata'      => $args['metadata'],
                'encodedFile'   => $args['encodedFile'],
            ]);
            if (empty($jsonResponse['error'])) {
                return ['error' => $jsonResponse['error'], 'code' => $jsonResponse['code']];
            }

            ResModel::update([
                'set' => ['status' => 'COU'],
                'where' => ['res_id = ?'],
                'data' => [$resLetterbox['res_id']]
            ]);

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $resLetterbox['res_id'],
                'eventType' => 'ACTION#1',
                'info'      => _RECEIVE_FROM_EXTERNAL . ' - ' . _FAST_PARAPHEUR_SMTP . " : " . _MAIN_DOCUMENT_ERROR_FROM_SB,
                'eventId'   => 'fromFastParapheurSmtp'
            ]);

        } elseif ($args['metadata']['clientDocType'] == 'attachment') {
            $fetchedAttachment = AttachmentModel::get([
                'select'  => ['*'],
                'where'   => ['res_id = ?'],
                'data'    => [$args['metadata']['clientDocId']]
            ])[0];
            if (empty($fetchedAttachment)) {
                return ['error' => 'client document id \'' . $args['metadata']['clientDocId'] . '\' does not exist!'];
            }

            $jsonResponse = FastParapheurSmtpController::makeJsonResonse([
                'resIdMaster'   => $fetchedAttachment['res_id_master'],
                'typist'        => $fetchedAttachment['typist'],
                'metadata'      => $args['metadata'],
                'encodedFile'   => $args['encodedFile'],
            ]);
            if (empty($jsonResponse['error'])) {
                return ['error' => $jsonResponse['error'], 'code' => $jsonResponse['code']];
            }

            ListInstanceModel::update([
                'set' => ['process_date' => null],
                'where' => ['res_id = ?', 'difflist_type = ?'],
                'data' => [$fetchedAttachment['res_id'], 'VISA_CIRCUIT']
            ]);
            AttachmentModel::update([
                'set'     => ['status' => 'A_TRA'],
                'postSet' => ['external_id' => "external_id - 'signatureBookId'"],
                'where'   => ['res_id = ?'],
                'data'    => [$fetchedAttachment['res_id']]
            ]);
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $fetchedAttachment['res_id_master'],
                'eventType' => 'ACTION#1',
                'info'      => _RECEIVE_FROM_EXTERNAL . ' - ' . _FAST_PARAPHEUR_SMTP . " : " . _ATTACHMENT_ERROR_FROM_SB,
                'eventId'   => 'fromFastParapheurSmtp'
            ]);         
        }
    }

    /**
     * Change letterbox/attachment document state and notify the user
     * 
     * @param   array   $args   metadata(array) encodedFile(string)
     * @return  void|array      return an array if an error occurs
     */
    public static function documentRefusedState(array $args) {
        if (!Validator::stringType()->notEmpty()->validate($args['metadata']['clientDocId'])) {
            return ['error' => 'Body metadata clientDocId is missing, empty or not a string', 'code' => 400];
        } elseif (!Validator::stringType()->notEmpty()->validate($args['metadata']['clientDocType'])) {
            return ['error' => 'Body metadata clientDocType is missing, empty or not a string', 'code' => 400];
        } elseif (!Validator::stringType()->notEmpty()->validate($args['encodedFile'])) {
            return ['error' => 'Body encodedFile is missing, empty or not a string', 'code' => 400];
        }

        if ($args['metadata']['clientDocType'] == 'mainDocument') {
            $resLetterbox = ResModel::get([
                'select' => ['*'],
                'where'  => ['res_id = ?'],
                'data'   => [$args['metadata']['clientDocId']]
            ])[0];
            if (empty($resLetterbox)) {
                return ['error' => 'client document id \'' . $args['metadata']['clientDocId'] . '\' does not exist!'];
            }

            $jsonResponse = FastParapheurSmtpController::makeJsonResonse([
                'resIdMaster'   => $resLetterbox['res_id'],
                'typist'        => $resLetterbox['typist'],
                'metadata'      => $args['metadata'],
                'encodedFile'   => $args['encodedFile'],
            ]);
            if (empty($jsonResponse['error'])) {
                return ['error' => $jsonResponse['error'], 'code' => $jsonResponse['code']];
            }

            ResModel::update([
                'set' => ['status' => 'COU'],
                'where' => ['res_id = ?'],
                'data' => [$resLetterbox['res_id']]
            ]);

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $resLetterbox['res_id'],
                'eventType' => 'ACTION#1',
                'info'      => _RECEIVE_FROM_EXTERNAL . ' - ' . _FAST_PARAPHEUR_SMTP . " : " . _MAIN_DOCUMENT_REFUSED_FROM_SB,
                'eventId'   => 'fromFastParapheurSmtp'
            ]);
        } elseif ($args['metadata']['clientDocType'] == 'attachment') {
            $fetchedAttachment = AttachmentModel::get([
                'select'  => ['*'],
                'where'   => ['res_id = ?'],
                'data'    => [$args['metadata']['clientDocId']]
            ])[0];
            if (empty($fetchedAttachment)) {
                return ['error' => 'client document id \'' . $args['metadata']['clientDocId'] . '\' does not exist!'];
            }

            $jsonResponse = FastParapheurSmtpController::makeJsonResonse([
                'resIdMaster'   => $fetchedAttachment['res_id_master'],
                'typist'        => $fetchedAttachment['typist'],
                'metadata'      => $args['metadata'],
                'encodedFile'   => $args['encodedFile'],
            ]);
            if (empty($jsonResponse['error'])) {
                return ['error' => $jsonResponse['error'], 'code' => $jsonResponse['code']];
            }
            
            ListInstanceModel::update([
                'set' => ['process_date' => null],
                'where' => ['res_id = ?', 'difflist_type = ?'],
                'data' => [$fetchedAttachment['res_id'], 'VISA_CIRCUIT']
            ]);
            AttachmentModel::update([
                'set'     => ['status' => 'A_TRA'],
                'postSet' => ['external_id' => "external_id - 'signatureBookId'"],
                'where'   => ['res_id = ?'],
                'data'    => [$fetchedAttachment['res_id']]
            ]);
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $fetchedAttachment['res_id_master'],
                'eventType' => 'ACTION#1',
                'info'      => _RECEIVE_FROM_EXTERNAL . ' - ' . _FAST_PARAPHEUR_SMTP . " : " . _ATTACHMENT_REFUSED_FROM_SB,
                'eventId'   => 'fromFastParapheurSmtp'
            ]);

        }
    }

    /**
     * Create new signed letterbox/attachment document, change the state and notify the user
     * 
     * @param   array   $args   metadata(array) encodedFile(string)
     * @return  void|array      return an array if an error occurs
     */
    public static function documentSignedState(array $args) {
        if (!Validator::stringType()->notEmpty()->validate($args['metadata']['clientDocId'])) {
            return ['error' => 'Body metadata clientDocId is missing, empty or not a string', 'code' => 400];
        } elseif (!Validator::stringType()->notEmpty()->validate($args['metadata']['clientDocType'])) {
            return ['error' => 'Body metadata clientDocType is missing, empty or not a string', 'code' => 400];
        } elseif (!Validator::stringType()->notEmpty()->validate($args['encodedFile'])) {
            return ['error' => 'Body encodedFile is missing, empty or not a string', 'code' => 400];
        }

        if ($args['metadata']['clientDocType'] == 'mainDocument') {
            $resLetterbox = ResModel::get([
                'select' => ['*'],
                'where'  => ['res_id = ?'],
                'data'   => [$args['metadata']['clientDocId']]
            ])[0];
            if (empty($resLetterbox)) {
                return ['error' => 'client document id \'' . $args['metadata']['clientDocId'] . '\' does not exist!'];
            }

            $jsonResponse = FastParapheurSmtpController::makeJsonResonse([
                'resIdMaster'   => $resLetterbox['res_id'],
                'typist'        => $resLetterbox['typist'],
                'metadata'      => $args['metadata'],
                'encodedFile'   => $args['encodedFile'],
            ]);
            if (empty($jsonResponse['error'])) {
                return ['error' => $jsonResponse['error'], 'code' => $jsonResponse['code']];
            }

            DatabaseModel::delete([
                'table' => 'adr_letterbox',
                'where' => ['res_id = ?', 'type in (?)', 'version = ?'],
                'data'  => [$resLetterbox['res_id'], ['SIGN', 'TNL'], $resLetterbox['version']]
            ]);
            $storeResult = DocserverController::storeResourceOnDocServer([
                'collId'          => 'letterbox_coll',
                'docserverTypeId' => 'DOC',
                'encodedResource' => $args['encodedFile'],
                'format'          => 'pdf'
            ]);
            DatabaseModel::insert([
                'table'         => 'adr_letterbox',
                'columnsValues' => [
                    'res_id'       => $resLetterbox['res_id'],
                    'type'         => 'SIGN',
                    'docserver_id' => $storeResult['docserver_id'],
                    'path'         => $storeResult['destination_dir'],
                    'filename'     => $storeResult['file_destination_name'],
                    'version'      => $resLetterbox['version'],
                    'fingerprint'  => empty($storeResult['fingerPrint']) ? null : $storeResult['fingerPrint']
                ]
            ]);

            ResModel::update([
                'set' => ['status' => 'COU'],
                'where' => ['res_id = ?'],
                'data' => [$resLetterbox['res_id']]
            ]);
            
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $resLetterbox['res_id'],
                'eventType' => 'ACTION#1',
                'info'      => _RECEIVE_FROM_EXTERNAL . ' - ' . _FAST_PARAPHEUR_SMTP . " : " . _MAIN_DOCUMENT_SIGNED_FROM_SB,
                'eventId'   => 'fromFastParapheurSmtp'
            ]);
        } elseif ($args['metadata']['clientDocType'] == 'attachment') {

            $fetchedAttachment = AttachmentModel::get([
                'select'  => ['*'],
                'where'   => ['res_id = ?'],
                'data'    => [$args['metadata']['clientDocId']]
            ])[0];
            if (empty($fetchedAttachment)) {
                return ['error' => 'client document id \'' . $args['metadata']['clientDocId'] . '\' does not exist!'];
            }

            $jsonResponse = FastParapheurSmtpController::makeJsonResonse([
                'resIdMaster'   => $fetchedAttachment['res_id_master'],
                'typist'        => $fetchedAttachment['typist'],
                'metadata'      => $args['metadata'],
                'encodedFile'   => $args['encodedFile'],
            ]);
            if (empty($jsonResponse['error'])) {
                return ['error' => $jsonResponse['error'], 'code' => $jsonResponse['code']];
            }

            DatabaseModel::delete([
                'table' => 'res_attachments',
                'where' => ['res_id_master = ?', 'status = ?', 'relation = ?', 'origin = ?'],
                'data'  => [$fetchedAttachment['res_id_master'], 'SIGN', $fetchedAttachment['relation'], $fetchedAttachment['res_id'] . ',res_attachments']
            ]);

            $newAttachmentData = [
                'resIdMaster'               => $fetchedAttachment['res_id_master'],
                'title'                     => $fetchedAttachment['title'],
                'chrono'                    => $fetchedAttachment['identifier'],
                'recipientId'               => $fetchedAttachment['recipient_id'],
                'recipientType'             => $fetchedAttachment['recipient_type'],
                'typist'                    => $fetchedAttachment['typist'],
                'format'                    => 'PDF',
                'type'                      => 'signed_response',
                'status'                    => 'TRA',
                'encodedFile'               => $args['encodedFile'],
                'inSignatureBook'           => true,
                'originId'                  => $fetchedAttachment['res_id'],
                'signatory_user_serial_id'  => $fetchedAttachment['signatory_user_serial_id'] ?? null
            ];
            $control = AttachmentController::controlAttachment(['body' => $newAttachmentData]);
            if (!empty($control['errors'])) {
                return ['error' => $control['errors'], 'code' => 400];
            }

            $storeControllerId = StoreController::storeAttachment($newAttachmentData);
            if (empty($storeControllerId) || !empty($storeControllerId['errors'])) {
                return ['error' => '[FastParapheurSmtp -> AttachmentController create] ' . $storeControllerId['errors'], 'code' => 400];
            }

            ConvertPdfController::convert([
                'resId'     => $storeControllerId,
                'collId'    => 'attachments_coll'
            ]);

            AttachmentModel::update([
                'set'     => ['status' => 'SIGN', 'in_signature_book' => 'false'],
                'postSet' => ['external_id' => "external_id - 'signatureBookId'"],
                'where'   => ['res_id = ?'],
                'data'    => [$storeControllerId]
            ]);

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $fetchedAttachment['res_id_master'],
                'eventType' => 'ACTION#1',
                'info'      => _RECEIVE_FROM_EXTERNAL . ' - ' . _FAST_PARAPHEUR_SMTP . " : " . _ATTACHMENT_SIGNED_FROM_SB,
                'eventId'   => 'fromFastParapheurSmtp'
            ]);
        }

    }

    /**
     * Create json response
     * 
     * @param   array   $args   metadata(array) encodedFile(string) resIdMaster(int) typist(int)
     * @return  array   attachment id && original
     */
    public static function makeJsonResonse(array $args)
    {
        if (!Validator::arrayType()->notEmpty()->validate($args['metadata'])) {
            return ['error' => 'Body metadata is missing or not an array', 'code' => 400];
        } elseif (!Validator::stringType()->notEmpty()->validate($args['encodedFile'])) {
            return ['errors' => 'Body encodedFile is missing, empty or not a string', 'code' => 400];
        } elseif (!Validator::intVal()->notEmpty()->validate($args['resIdMaster'])) {
            return ['errors' => 'Body encodedFile is missing, empty or not a string', 'code' => 400];
        } elseif (!Validator::intVal()->notEmpty()->validate($args['typist'])) {
            return ['errors' => 'Body encodedFile is missing, empty or not a string', 'code' => 400];
        }

        $jsonRequest = [
            "metadata"   => $args['metadata'],
            "encodedFile" => $args['encodedFile']
        ];

        $body = [
            'title'         => 'response',
            'resIdMaster'   => $args['resIdMaster'],
            'type'          => 'response_json',
            'format'        => 'JSON',
            'typist'        => $args['typist'],
            'encodedFile'   => base64_encode(json_encode($jsonRequest, JSON_PRETTY_PRINT))
        ];

        $id = StoreController::storeAttachment($body);
        if (empty($id) || !empty($id['errors'])) {
            return ['error' => '[FastParapheurSmtpController makeJsonResonse] ' . $id['errors'], 'code' => 400];
        }

        return [
            "id" => $id,
            "original" => true
        ];
    }
}