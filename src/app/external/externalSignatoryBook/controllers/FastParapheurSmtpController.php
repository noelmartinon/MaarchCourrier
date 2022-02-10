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
     * description send unsigned pdf with attachments via SMTP
     * 
     * @param   array   $agrs   Need config and resIdMaster attributs/value
     * @return  array   
     */
    public static function sendDatas($args)
    {
        ValidatorModel::notEmpty($args, ['config', 'resIdMaster']);
        ValidatorModel::intVal($args, ['resIdMaster']);

        // get config
        $config = $args['config'];
        $smtpConfig = ConfigurationModel::getByPrivilege(['privilege' => 'admin_email_server', 'select' => ['value']]);

        if (empty($smtpConfig)) {
            return ['error' => 'Mail server settings are missing!', 'historyInfos' => ' Mail server settings are missing!'];
        }
        $smtpConfig = json_decode($smtpConfig['value'], true);

        // get courrier
        $mainDocument = FastParapheurSmtpController::getMainDocument(['res_id'=> $args['resIdMaster'], 'collId' => 'letterbox_coll']);
        if (!empty($mainDocument['error'])) {
            return ['error' => $mainDocument['error']];
        }

        // get attachments
        $attachments = FastParapheurSmtpController::getMainDocumentAttachments(['res_id'=> $args['resIdMaster']]);
        if (!empty($attachments['error'])) {
            return ['error' => $attachments['error']];
        }

        // make request json
        $jsonRequest = FastParapheurSmtpController::makeJsonRequest([
            'config' => $config, 
            'res_id'=> $args['resIdMaster'], 
            'userId' => $args['userId'], 
            'note' => $args['note'], 
            'mainDocument' => $mainDocument, 
            'attachments' => $attachments['jsonAttachments']
        ]);
        if (!empty($jsonRequest['error'])) {
            return ['error' => $jsonRequest['error']];
        }

        $attachments['emailAttachments'][]= $jsonRequest;

        // create email and send it
        $emailId = EmailController::createEmail([
            'userId'    => $GLOBALS['id'],
            'data'      => [
                'sender'        => ['email' => $smtpConfig['from']],
                'recipients'    => [$config['data']['email']],
                'object'        => $config['data']['subject'],
                'body'          => empty($args['note']) ? '' : 'Annotation du documment ' . $args['note'],
                'document'      => ['id' => $args['resIdMaster'], 'isLinked' => true, 'original' => false, 'attachments' => $attachments['emailAttachments']],
                'isHtml'        => true,
                'status'        => 'EXPRESS'
            ]
        ]);

        if (!empty($emailId['errors'])) {
            return ['error' => $emailId['errors']];
        }

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $args['resIdMaster'],
            'eventType' => 'UP',
            'info'      => _SEND_TO_EXTERNAL_SB . ' : ' . _FAST_PARAPHEUR_SMTP,
            'eventId'   => 'sendToFastParapheurSmtp'                
        ]);

        return ['sended' => 'success', 'historyInfos' => ' The Document was send successfully'];
    }

    public static function createDocument(Request $request, Response $response) {

        $body = $request->getParsedBody();

        $mainDocument = FastParapheurSmtpController::getMainDocument(['res_id'=> $body['res_id'], 'collId' => 'letterbox_coll']);
        return $response->withStatus(400)->withJson($mainDocument);

        ValidatorModel::notEmpty($body, ['clientDocId', 'clientDocType', 'documentName', 'documentHash', 'hashAlgorithm', 'documentBase64']);
        ValidatorModel::stringType($body, ['clientDocType', 'documentName', 'documentHash', 'hashAlgorithm', 'documentBase64']);
        ValidatorModel::intVal($body, ['clientDocId']);

        if (empty($body['status'])) {
            return $response->withStatus(409)->withJson(['errors' => "The object 'status' is missing. Exemple : \"status\": {\"message\": \"something\",\"type\": \"error|refused|signed\"}"]);
        }
        if (empty($body['status']['type'])) {
            return $response->withStatus(409)->withJson(['errors' => 'The status type is missing!']);
        }
        if (empty($body['status']['message'])) {
            return $response->withStatus(409)->withJson(['errors' => 'The status message is missing!']);
        }

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

        

        

        if ($body['status']['type'] == $config['data']['errorState']) {
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $body['clientDocId'],
                'eventType' => 'UP',
                'info'      => _RECEIVE_FROM_EXTERNAL . ' : ' . _DOCUMENT_ERROR . (!empty($body['status']['message']) ? ', ' . $body['status']['message'] : ''),
                'eventId'   => 'fromFastParapheurSmtp'
            ]);
        }
        else if ($body['status']['type'] == $config['data']['refusedState']) {
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $body['clientDocId'],
                'eventType' => 'UP',
                'info'      => _RECEIVE_FROM_EXTERNAL . ' : ' . _DOCUMENT_REFUSED . (!empty($body['status']['message']) ? ', ' . $body['status']['message'] : ''),
                'eventId'   => 'fromFastParapheurSmtp'
            ]);
        }
        else if ($body['status']['type'] == $config['data']['signedState']) {
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $body['clientDocId'],
                'eventType' => 'UP',
                'info'      => _RECEIVE_FROM_EXTERNAL . ' : ' . _DOCUMENT_SIGNED,
                'eventId'   => 'fromFastParapheurSmtp'
            ]);
        }
        else {
            return $response->withStatus(400)->withJson(['The query syntax is incorrect.']);
        }

        return $response->withStatus(201)->withJson(['Document created']);
    }
    
    public static function getMainDocument($args) {
        ValidatorModel::notEmpty($args, ['res_id']);
        ValidatorModel::intVal($args, ['res_id']);
        ValidatorModel::stringType($args, ['collId']);

        $document['res_id'] = $args['res_id'];

        if (!empty($args['collId']) && in_array($args['collId'], ['letterbox_coll', 'attachments_coll'])) {
            $document = ConvertPdfController::getConvertedPdfById(['resId' => $document['res_id'], 'collId' => $args['collId']]);
            if (!empty($document['errors'])) {
                return ['error' => 'Conversion error : ' . $document['errors'], 'code' => 400];
            }

            if (strtolower(pathinfo($document['filename'], PATHINFO_EXTENSION)) != 'pdf') {
                return ['error' => 'Document can not be converted', 'code' => 400];
            }
        }

        $docserver = DocserverModel::getByDocserverId([
            'docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']
        ]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['error' => 'Docserver does not exist', 'code' => 400];
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];

        if (!file_exists($pathToDocument)) {
            return ['error' => 'Document not found on docserver', 'code' => 404];
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if ($document['fingerprint'] != $fingerprint) {
            return ['error' => 'Fingerprints do not match', 'code' => 400];
        }

        return [
            'documentName' => $document['filename'],
            'fingerprint' => $document['fingerprint'],
            "hashAlgorithm" => $docserverType['fingerprint_mode'],
            'pathToDocument' => $pathToDocument
        ];

    }

    public static function getMainDocumentAttachments($args) {
        ValidatorModel::notEmpty($args, ['res_id']);
        ValidatorModel::intVal($args, ['res_id']);

        $attachments = DatabaseModel::select([
            'select'    => ['ra.res_id as id', '(select false) as original', 'ra.title', 'ra.format', 'ra.fingerprint', 'ra.filesize', 'dt.fingerprint_mode', 'CONCAT(d.path_template, ra.path, ra.filename) as file_path_name'],
            'table'     => ['res_attachments as ra, docservers as d, docserver_types as dt'],
            'where'     => ['ra.res_id_master = ?', 'ra.docserver_id = d.docserver_id', 'd.docserver_type_id = dt.docserver_type_id'],
            'data'      => [$args['res_id']]
        ]);

        $errorMsg = '';
        $jsonAttachments = [];
        $emailAttachments = [];
        
        if (!empty($attachments)) {
            foreach ($attachments as $key => $attachment) {
                $fingerprint = StoreController::getFingerPrint(['filePath' => $attachment['file_path_name'], 'mode' => $attachment['fingerprint_mode']]);
                if ($attachment['fingerprint'] != $fingerprint) {
                    $errorMsg .= "Fingerprint do not match for file {$attachment['title']}\n";
                    continue;
                }
    
                $jsonAttachments[]= array(
                    "name" => $attachment['title'] . '.' . $attachment['format'],
                    "hash" => $attachment['fingerprint'],
                    "hashAlgorithm" => $attachment['fingerprint_mode']
                );
                $emailAttachments[]= array(
                    "id" => $attachment['id'],
                    "original" => $attachment['original']
                );
            }
    
            if (!empty($errorMsg)) {
                return ['error' => $errorMsg, 'code' => 400];
            }
        }

        return ['jsonAttachments' => $jsonAttachments, 'emailAttachments' => $emailAttachments];
    }

    public static function makeJsonRequest($args) {
        ValidatorModel::notEmpty($args, ['config', 'res_id', 'userId', 'mainDocument']);
        ValidatorModel::intVal($args, ['res_id']);

        $jsonRequest = array(
            "clientDocId" => $args['res_id'],
            "clientDocType" => "mainDocument",
            "documentName" => $args['mainDocument']['documentName'],
            "documentHash" => $args['mainDocument']['fingerprint'],
            "hashAlgorithm" => $args['mainDocument']['hashAlgorithm'],
            "circuitId" => $args['userId'],
            "businessId" => $args['config']['data']['subscriberId'],
            "label" => "test label",
            "comment" => empty($args['note']) ? '' : $args['note'],
            "annexes" => $args['attachments']
        );

        // $userCryptedPwd = UserModel::get([
        //     'select'    => ['password'],
        //     'where'     => ['user_id = ?'],
        //     'data'      => [$GLOBALS['login']]
        // ]);

        $_body = [
            'title'         => 'Request-Json',
            'resIdMaster'   => $args['res_id'],
            'type'          => 'simple_attachment',
            'format'        => 'JSON',
            'encodedFile'   => base64_encode(json_encode($jsonRequest))
        ];

        $id = StoreController::storeAttachment($_body);
        if (empty($id) || !empty($id['errors'])) {
            return ['error' => '[AttachmentController create] ' . $id['errors']];
        }

        // this works
        // $response = CurlModel::exec([
        //     'url'      => $_SERVER['HTTP_ORIGIN'] . $_SERVER['PHP_SELF'] . '/../../rest/attachments',
        //     'user'     => $GLOBALS['login'],
        //     'password' => "maarch", //PasswordModel::decrypt(['cryptedPassword' => $userCryptedPwd[0]['password']]),
        //     'method'   => 'POST',
        //     'bodyData' => [
        //         'title'         => 'Request-Json',
        //         'resIdMaster'   => $args['res_id'],
        //         'type'          => 'simple_attachment',
        //         'format'        => 'JSON',
        //         'encodedFile'   => base64_encode(json_encode($jsonRequest))
        //     ]
        // ]);
        // var_dump($_COOKIE);

        // $response = CurlModel::execSimple([
        //     'url'           => $_SERVER['HTTP_ORIGIN'] . $_SERVER['PHP_SELF'] . '/../../rest/attachments',
        //     'basicAuth'     => ['user' => $GLOBALS['login'], 'password' => PasswordModel::decrypt(['cryptedPassword' => $userCryptedPwd[0]['password']])],
        //     'headers'       => ['content-type:application/json', 'Accept: application/json'],
        //     'method'        => 'POST',
        //     'body'          => json_encode([
        //         'title'         => 'Request-Json',
        //         'resIdMaster'   => $args['res_id'],
        //         'type'          => 'request_json',
        //         'format'        => 'JSON',
        //         'encodedFile'   => base64_encode(json_encode($jsonRequest))
        //     ])
        // ]);

        if (!empty($response['errors'])) {
            return ['error' => $response['errors']];
        }

        return [
            "id" => $id,
            "original" => true
        ];
    }

    
}