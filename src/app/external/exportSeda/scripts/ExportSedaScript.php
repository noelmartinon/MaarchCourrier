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
use SrcCore\models\DatabasePDO;
use User\models\UserModel;

// ARGS
// --encodedData : All data encoded in base64

ExportSedaScript::initialize($argv);

class ExportSedaScript
{
    public static function initialize($args)
    {
        if (array_search('--encodedData', $args) > 0) {
            $cmd = array_search('--encodedData', $args);
            $data = json_decode(base64_decode($args[$cmd+1]), true);
        }

        if (!empty($data)) {
            ExportSedaScript::send(['data' => $data]);
        }
    }

    public static function send(array $args)
    {
        DatabasePDO::reset();
        new DatabasePDO(['customId' => $args['data']['customId']]);
        $GLOBALS['customId'] = $args['data']['customId'];

        $currentUser = UserModel::getById(['id' => $args['data']['userId'], 'select' => ['user_id']]);
        $GLOBALS['login'] = $currentUser['user_id'];
        $GLOBALS['id']    = $args['data']['userId'];

        $path = 'apps/maarch_entreprise/xml/config.json';
        if (!empty($args['data']['customId']) && file_exists("custom/{$args['data']['customId']}/{$path}")) {
            $path = "custom/{$args['data']['customId']}/{$path}";
        }

        $config = file_get_contents($path);
        $config = json_decode($config, true);

        foreach ($args['data']['resources'] as $resource) {
            $elementSend  = ExportSEDATrait::sendSedaPackage([
                'messageId'       => $resource['messageId'],
                'config'          => $config,
                'encodedFilePath' => $resource['encodedFilePath'],
                'messageFilename' => $resource['messageFilename'],
                'resId'           => $resource['resId'],
                'reference'       => $resource['reference']
            ]);
            unlink($resource['encodedFilePath']);
            if (!empty($elementSend['errors'])) {
                ResModel::update(['set' => ['status' => $args['data']['errorStatus']], 'where' => ['res_id = ?'], 'data' => [$resource['resId']]]);
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'exportSeda',
                    'level'     => 'ERROR',
                    'tableName' => 'letterbox_coll',
                    'recordId'  => $resource['resId'],
                    'eventType' => "Export Seda failed : {$elementSend['errors']}",
                    'eventId'   => "resId : {$resource['resId']}"
                ]);
            } else {
                ResModel::update(['set' => ['status' => $args['data']['successStatus']], 'where' => ['res_id = ?'], 'data' => [$resource['resId']]]);
                LogsController::add([
                    'isTech'    => true,
                    'moduleId'  => 'exportSeda',
                    'level'     => 'INFO',
                    'tableName' => 'letterbox_coll',
                    'recordId'  => $resource['resId'],
                    'eventType' => "Export Seda success",
                    'eventId'   => "resId : {$resource['resId']}"
                ]);
            }
        }

        return true;
    }
}
