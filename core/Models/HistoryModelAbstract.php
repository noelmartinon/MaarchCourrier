<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief History Model Abstract
 * @author dev@maarch.org
 * @ingroup core
 */

namespace Core\Models;

class HistoryModelAbstract
{
    /**
     * Get the logging method in the configuration file
     */
    public static function build_logging_method()
    {
        $loggingMethods = [];

        $customId = CoreConfigModel::getCustomId();
        if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/logging_method.xml")) {
            $path = "custom/{$customId}/apps/maarch_entreprise/xml/logging_method.xml";
        } else {
            $path = 'apps/maarch_entreprise/xml/logging_method.xml';
        }

        if (file_exists($path)) {
            $xmlConfig = simplexml_load_file($path);

            if ($xmlConfig) {
                foreach ($xmlConfig->METHOD as $METHOD) {
                    $loggingMethods[] = [
                        'ID'               => (string)$METHOD->ID,
                        'ACTIVATED'        => (boolean)$METHOD->ENABLED,
                        'LOGGER_NAME_TECH' => (string)$METHOD->LOGGER_NAME_TECH,
                        'LOGGER_NAME_FUNC' => (string)$METHOD->LOGGER_NAME_FUNC,
                        'LOG_FORMAT'       => (string)$METHOD->APPLI_LOG_FORMAT,
                        'CODE_METIER'      => (string)$METHOD->CODE_METIER
                    ];
                }
            }
        } else {
            $loggingMethods[0]['ID']               = 'database';
            $loggingMethods[0]['ACTIVATED']        = true;
            $loggingMethods[1]['ID']               = 'log4php';
            $loggingMethods[1]['ACTIVATED']        = true;
            $loggingMethods[1]['LOGGER_NAME_TECH'] = 'loggerTechnique';
            $loggingMethods[1]['LOGGER_NAME_FUNC'] = 'loggerFonctionnel';
            $loggingMethods[1]['LOG_FORMAT']       = '[%RESULT%][%CODE_METIER%][%WHERE%][%ID%][%HOW%][%USER%][%WHAT%][%ID_MODULE%][%REMOTE_IP%]';
            $loggingMethods[1]['CODE_METIER']      = 'MAARCH';
        }

        return $loggingMethods;
    }

    /**
     * Write a log entry with a specific log level
     *
     * @param  $aArgs array
     */
    public static function writeLog(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['logger', 'logLine', 'level']);
        ValidatorModel::stringType($aArgs, ['logLine', 'level']);

        $logger  = $aArgs['logger'];

        switch ($aArgs['level']) {
            case 'DEBUG':
                $logger->debug($aArgs['logLine']);
                break;
            case 'INFO':
                $logger->info($aArgs['logLine']);
                break;
            case 'WARN':
                $logger->warn($aArgs['logLine']);
                break;
            case 'ERROR':
                $logger->error($aArgs['logLine']);
                break;
            case 'FATAL':
                $logger->fatal($aArgs['logLine']);
                break;
        }
    }

    public static function create(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['tableName', 'recordId', 'eventType', 'userId', 'info', 'moduleId', 'eventId']);
        ValidatorModel::stringType($aArgs, ['tableName', 'recordId', 'eventType', 'userId', 'info', 'moduleId', 'eventId']);

        DatabaseModel::insert([
            'table'         => 'history',
            'columnsValues' => [
                'table_name'    => $aArgs['tableName'],
                'record_id'     => $aArgs['recordId'],
                'event_type'    => $aArgs['eventType'],
                'user_id'       => $aArgs['userId'],
                'event_date'    => 'CURRENT_TIMESTAMP',
                'info'          => $aArgs['info'],
                'id_module'     => $aArgs['moduleId'],
                'remote_ip'     => $_SERVER['REMOTE_ADDR'],
                'event_id'      => $aArgs['eventId'],
            ]
        ]);

        return true;
    }

    public static function getHistoryList(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['event_date']);

        $aReturn = DatabaseModel::select(
            [
                'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
                'table'     => ['history'],
                'where'     => ["event_date >= date '".$aArgs['event_date']."'","event_date < date '".$aArgs['event_date']."' + interval '1 month'"],
                'order_by'  => ['event_date DESC']
            ]
        );

        return $aReturn;
    }

    public static function getHistoryBatchList(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['event_date']);

        $aReturn = DatabaseModel::select(
            [
                'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
                'table'     => ['history_batch'],
                'where'     => ["event_date >= date '".$aArgs['event_date']."'","event_date < date '".$aArgs['event_date']."' + interval '1 month'"],
                'order_by'  => ['event_date DESC']
            ]
        );

        return $aReturn;
    }

    public static function getHistoryByUserId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $aHistories = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['history'],
            'where'     => ['user_id = ?', 'event_date > (CURRENT_TIMESTAMP - interval \'30 DAYS\')'],
            'data'      => [$aArgs['userId']]
        ]);

        return $aHistories;
    }

    public static function getFilter(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['select','event_date']);
        ValidatorModel::stringType($aArgs, ['select']);

        $aReturn = DatabaseModel::select(
            [
                'select'    => ['DISTINCT('.$aArgs['select'].')'],
                'table'     => ['history'],
                'where'     => ["event_date >= date '".$aArgs['event_date']."'","event_date < date '".$aArgs['event_date']."' + interval '1 month'"],
                'order_by'  => [$aArgs['select'].' ASC']
            ]
        );

        return $aReturn;
    }

    public static function getBatchFilter(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['select','event_date']);
        ValidatorModel::stringType($aArgs, ['select']);

        $aReturn = DatabaseModel::select(
            [
                'select'    => ['DISTINCT('.$aArgs['select'].')'],
                'table'     => ['history_batch'],
                'where'     => ["event_date >= date '".$aArgs['event_date']."'","event_date < date '".$aArgs['event_date']."' + interval '1 month'"],
                'order_by'  => [$aArgs['select'].' ASC']
            ]
        );

        return $aReturn;
    }
}
