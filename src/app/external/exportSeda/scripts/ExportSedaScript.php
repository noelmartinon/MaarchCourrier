<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Export Seda Script
 * @author dev@maarch.org
 */

namespace ExportSeda\controllers;

require 'vendor/autoload.php';

use ExportSeda\controllers\ExportSEDATrait;
use Resource\models\ResModel;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabasePDO;
use User\models\UserModel;

// ARGS
// --customId    : instance id;
// --resId      : res_id of the mail to archive;
// --userId      : technical identifer user (for saving log);
// --successStatus   : status of the mail if script end without problem
// --errorStatus   : status of the mail if script end with failures
// --messageId : message_id in message_exchange table
// --encodedFilePath : Path of the encoded archive file
// --messageFilename : Name of the archive file
// --reference : reference of the archive

ExportSedaScript::initialize($argv);

class ExportSedaScript
{
    public static function initialize($args)
    {
        $customId        = '';
        $resId           = '';
        $successStatus   = '';
        $errorStatus     = '';
        $messageId       = '';
        $encodedFilePath = '';
        $messageFilename = '';
        $reference       = '';

        if (array_search('--customId', $args) > 0) {
            $cmd = array_search('--customId', $args);
            $customId = $args[$cmd+1];
        }
        
        if (array_search('--resId', $args) > 0) {
            $cmd = array_search('--resId', $args);
            $resId = $args[$cmd+1];
        }

        if (array_search('--userId', $args) > 0) {
            $cmd = array_search('--userId', $args);
            $userId = $args[$cmd+1];
        }
        
        if (array_search('--successStatus', $args) > 0) {
            $cmd = array_search('--successStatus', $args);
            $successStatus = $args[$cmd+1];
        }

        if (array_search('--errorStatus', $args) > 0) {
            $cmd = array_search('--errorStatus', $args);
            $errorStatus = $args[$cmd+1];
        }

        if (array_search('--messageId', $args) > 0) {
            $cmd = array_search('--messageId', $args);
            $messageId = $args[$cmd+1];
        }

        if (array_search('--encodedFilePath', $args) > 0) {
            $cmd = array_search('--encodedFilePath', $args);
            $encodedFilePath = $args[$cmd+1];
        }

        if (array_search('--messageFilename', $args) > 0) {
            $cmd = array_search('--messageFilename', $args);
            $messageFilename = $args[$cmd+1];
        }

        if (array_search('--reference', $args) > 0) {
            $cmd = array_search('--reference', $args);
            $reference = $args[$cmd+1];
        }

        if (!empty($userId)) {
            ExportSedaScript::send([
                'customId' => $customId, 'resId' => $resId, 'userId' => $userId, 'successStatus' => $successStatus, 'errorStatus' => $errorStatus,
                'messageId' => $messageId, 'encodedFilePath' => $encodedFilePath, 'messageFilename' => $messageFilename, 'reference' => $reference]);
        }
    }

    public static function send(array $args)
    {
        DatabasePDO::reset();
        new DatabasePDO(['customId' => $args['customId']]);
        $GLOBALS['customId'] = $args['customId'];

        $currentUser = UserModel::getById(['id' => $args['userId'], 'select' => ['user_id']]);
        $GLOBALS['login'] = $currentUser['user_id'];
        $GLOBALS['id']    = $args['userId'];

        $config = CoreConfigModel::getJsonLoaded(['path' => 'apps/maarch_entreprise/xml/config.json']);

        $elementSend  = ExportSEDATrait::sendSedaPackage([
            'messageId'       => $args['messageId'],
            'config'          => $config,
            'encodedFilePath' => $args['encodedFilePath'],
            'messageFilename' => $args['messageFilename'],
            'resId'           => $args['resId'],
            'reference'       => $args['reference']
        ]);
        unlink($args['encodedFilePath']);
        if (!empty($elementSend['errors'])) {
            ResModel::update(['set' => ['status' => $args['errorStatus']], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);
            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'exportSeda',
                'level'     => 'ERROR',
                'tableName' => 'letterbox_coll',
                'recordId'  => $args['resId'],
                'eventType' => "Export Seda failed : {$elementSend['errors']}",
                'eventId'   => "resId : {$args['resId']}"
            ]);
        } else {
            ResModel::update(['set' => ['status' => $args['successStatus']], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);
            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'exportSeda',
                'level'     => 'INFO',
                'tableName' => 'letterbox_coll',
                'recordId'  => $args['resId'],
                'eventType' => "Export Seda success",
                'eventId'   => "resId : {$args['resId']}"
            ]);
        }

        return true;
    }
}
