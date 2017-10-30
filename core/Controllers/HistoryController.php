<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief History Controller
 * @author dev@maarch.org
 * @ingroup core
 */

namespace Core\Controllers;

use Core\Models\CoreConfigModel;
use Core\Models\TextFormatModel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Core\Models\HistoryModel;
use Core\Models\ServiceModel;
use Core\Models\ValidatorModel;

class HistoryController
{

    /**
     * Inserts a record in the history table
     *
     * @param $aArgs array
     */
    public static function add(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['tableName', 'recordId', 'eventType', 'info', 'eventId']);
        ValidatorModel::stringType($aArgs, ['tableName', 'recordId', 'eventType', 'info', 'eventId', 'moduleId', 'level']);

        if(empty($aArgs['isTech'])){
            $aArgs['isTech'] = false;
        }
        if(empty($aArgs['moduleId'])){
            $aArgs['moduleId'] = 'admin';
        }
        if(empty($aArgs['level'])){
            $aArgs['level'] = 'DEBUG';
        }

        $traceInformations = [
            'WHERE'         => $aArgs['tableName'],
            'ID'            => $aArgs['recordId'],
            'HOW'           => $aArgs['eventType'],
            'USER'          => $_SESSION['user']['UserId'],
            'WHAT'          => $aArgs['eventId'],
            'ID_MODULE'     => $aArgs['moduleId'],
            'REMOTE_IP'     => $_SERVER['REMOTE_ADDR'],
            'LEVEL'         => $aArgs['level']
        ];

        $loggingMethods = HistoryModel::build_logging_method();

        foreach ($loggingMethods as $loggingMethod) {
            if ($loggingMethod['ACTIVATED'] == true) {
                if ($loggingMethod['ID'] == 'log4php') {
                    if (empty($loggingMethod['LOGGER_NAME_TECH'])) {
                        $loggingMethod['LOGGER_NAME_TECH'] = 'loggerTechnique';
                    }
                    if (empty($loggingMethod['LOGGER_NAME_FUNC'])) {
                        $loggingMethod['LOGGER_NAME_FUNC'] = 'loggerFonctionnel';
                    }
                    HistoryController::addToLog4php([
                        'traceInformations' => $traceInformations,
                        'loggingMethod'     => $loggingMethod,
                        'isTech'            => $aArgs['isTech'],
                    ]);
                }
            }
        }

        HistoryModel::create([
            'tableName' => $aArgs['tableName'],
            'recordId'  => $aArgs['recordId'],
            'eventType' => $aArgs['eventType'],
            'userId'   => $_SESSION['user']['UserId'],
            'info'      => $aArgs['info'],
            'moduleId'  => $aArgs['moduleId'],
            'eventId'   => $aArgs['eventId'],
        ]);
    }

    /**
     * Insert a log line into log4php
     *
     * @param $aArgs array
     */
    private static function addToLog4php(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['traceInformations', 'loggingMethod']);
        ValidatorModel::arrayType($aArgs, ['traceInformations', 'loggingMethod']);

        $loggingMethod    = $aArgs['loggingMethod'];
        $traceInformations = $aArgs['traceInformations'];

        $customId = CoreConfigModel::getCustomId();
        if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/log4php.xml")) {
            $path = "custom/{$customId}/apps/maarch_entreprise/xml/log4php.xml";
        } else if (file_exists('apps/maarch_entreprise/xml/log4php.xml')) {
            $path = 'apps/maarch_entreprise/xml/log4php.xml';
        } else {
            $path = 'apps/maarch_entreprise/xml/log4php.default.xml';
        }

        \Logger::configure($path);

        if (!empty($aArgs['isTech'])) {
            $logger = \Logger::getLogger($loggingMethod['LOGGER_NAME_TECH']);
        } else {
            $logger = \Logger::getLogger($loggingMethod['LOGGER_NAME_FUNC']);
        }

        $logLine = str_replace(
            [
                '%RESULT%',
                '%CODE_METIER%',
                '%WHERE%',
                '%ID%',
                '%HOW%',
                '%USER%',
                '%WHAT%',
                '%ID_MODULE%',
                '%REMOTE_IP%'
            ],
            [
                'OK',
                $loggingMethod['CODE_METIER'],
                $traceInformations['WHERE'],
                $traceInformations['ID'],
                $traceInformations['HOW'],
                $traceInformations['USER'],
                $traceInformations['WHAT'],
                $traceInformations['ID_MODULE'],
                $traceInformations['REMOTE_IP']
            ],
            $loggingMethod['LOG_FORMAT']
        );

        $logLine = TextFormatModel::htmlWasher($logLine);
        $logLine = HistoryController::wd_remove_accents(['string' => $logLine]);

        HistoryModel::writeLog([
            'logger'  => $logger,
            'logLine' => $logLine,
            'level'   => $traceInformations['LEVEL']
        ]);
    }

    public static function wd_remove_accents(array $aArgs)
    {
        if (empty($aArgs['charset'])) {
            $aArgs['charset'] = 'utf-8';
        }

        $str = htmlentities($aArgs['string'], ENT_NOQUOTES, $aArgs['charset']);

        $str = preg_replace(
            '#\&([A-za-z])(?:uml|circ|tilde|acute|grave|cedil|ring)\;#',
            '\1',
            $str
        );
        $str = preg_replace(
            '#\&([A-za-z]{2})(?:lig)\;#',
            '\1',
            $str
        );
        $str = preg_replace(
            '#\&[^;]+\;#',
            '',
            $str
        );

        return $str;
    }

    public function getForAdministration(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'history', 'userId' => $_SESSION['user']['UserId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $historyList = HistoryModel::getHistoryList(['event_date' => $aArgs['date']]);
        $historyListFilters['users'] = HistoryModel::getFilter(['select' => 'user_id','event_date' => $aArgs['date']]);
        $historyListFilters['eventType'] = HistoryModel::getFilter(['select' => 'event_type','event_date' => $aArgs['date']]);

        $return = [];
        $return['filters'] = $historyListFilters;
        $return['historyList'] = $historyList;

        return $response->withJson($return);
    }

    public function getBatchForAdministration(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'view_history_batch', 'userId' => $_SESSION['user']['UserId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $historyList = HistoryModel::getHistoryBatchList(['event_date' => $aArgs['date']]);
        $historyListFilters['modules'] = HistoryModel::getBatchFilter(['select' => 'module_name','event_date' => $aArgs['date']]);

        $return = [];
        $return['filters'] = $historyListFilters;
        $return['historyList'] = $historyList;

        return $response->withJson($return);
    }
}
