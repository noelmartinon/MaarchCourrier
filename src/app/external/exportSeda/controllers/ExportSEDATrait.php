<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ExportSEDATrait
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace ExportSeda\controllers;

use Attachment\models\AttachmentModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use MessageExchange\models\MessageExchangeModel;
use Resource\controllers\StoreController;
use SrcCore\models\ValidatorModel;

trait ExportSEDATrait
{
    public static function sendToRecordManagement(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);

        // TODO : CONTROL + GET DATAS
        $data = [];

        $controller = ExportSEDATrait::generateSEDAPackage(['data' => $data]);
        if (!empty($controller['errors'])) {
            return ['errors' => [$controller['errors']]];
        }

        // TODO : SEND PACKAGE TO RM

        return true;
    }

    public static function generateSEDAPackage(array $args)
    {
        $encodedFile = '';
        
        return ['encodedFile' => $encodedFile];
    }

    public static function checkAcknowledgmentRecordManagement(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);

        $acknowledgement = AttachmentModel::get([
            'select' => ['res_id_master', 'path', 'filename', 'docserver_id', 'fingerprint'],
            'where'  => ['res_id_master = ?', 'attachment_type = ?', 'status = ?'],
            'data'   => [$args['resId'], 'acknowledgement_record_management', 'TRA']
        ])[0];
        if (empty($acknowledgement)) {
            return ['errors' => ['No acknowledgement found']];
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $acknowledgement['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => ['Docserver does not exists']];
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $acknowledgement['path']) . $acknowledgement['filename'];
        if (!file_exists($pathToDocument)) {
            return ['errors' => ['File does not exists']];
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (!empty($acknowledgement['fingerprint']) && $acknowledgement['fingerprint'] != $fingerprint) {
            return ['errors' => ['Fingerprint does not match']];
        }
        
        $acknowledgementXml = @simplexml_load_file($pathToDocument);
        if (empty($acknowledgementXml)) {
            return ['errors' => ['Acknowledgement is not readable']];
        }

        $messageExchange = MessageExchangeModel::getMessageByReference(['select' => ['message_id'], 'reference' => (string)$acknowledgementXml->MessageReceivedIdentifier]);
        if (empty($messageExchange)) {
            return ['errors' => ['No acknowledgement found with this reference']];
        }

        $unitIdentifier = MessageExchangeModel::getUnitIdentifierByResId(['select' => ['message_id'], 'resId' => $args['resId']]);
        if ($unitIdentifier['message_id'] != $messageExchange['message_id']) {
            return ['errors' => ['Wrong acknowledgement']];
        }

        return true;
    }

    public static function checkReplyRecordManagement(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);

        $reply = AttachmentModel::get([
            'select' => ['res_id_master', 'path', 'filename', 'docserver_id', 'fingerprint'],
            'where'  => ['res_id_master = ?', 'attachment_type = ?', 'status = ?'],
            'data'   => [$args['resId'], 'reply_record_management', 'TRA']
        ])[0];
        if (empty($reply)) {
            return ['errors' => ['No reply found']];
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $reply['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => ['Docserver does not exists']];
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $reply['path']) . $reply['filename'];
        if (!file_exists($pathToDocument)) {
            return ['errors' => ['File does not exists']];
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (!empty($reply['fingerprint']) && $reply['fingerprint'] != $fingerprint) {
            return ['errors' => ['Fingerprint does not match']];
        }
        
        $replyXml = @simplexml_load_file($pathToDocument);
        if (empty($replyXml)) {
            return ['errors' => ['Reply is not readable']];
        }

        $messageExchange = MessageExchangeModel::getMessageByReference(['select' => ['message_id'], 'reference' => (string)$replyXml->MessageReceivedIdentifier]);
        if (empty($messageExchange)) {
            return ['errors' => ['No reply found with this reference']];
        }

        $unitIdentifier = MessageExchangeModel::getUnitIdentifierByResId(['select' => ['message_id'], 'resId' => $args['resId']]);
        if ($unitIdentifier['message_id'] != $messageExchange['message_id']) {
            return ['errors' => ['Wrong reply']];
        }

        return true;
    }
}
