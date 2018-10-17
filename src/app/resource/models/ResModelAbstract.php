<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Res Model
* @author dev@maarch.org
*/

namespace Resource\models;

use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class ResModelAbstract
{
    public static function getOnView(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy']);

        $aResources = DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => ['res_view_letterbox'],
            'where'     => $aArgs['where'],
            'data'      => $aArgs['data'],
            'order_by'  => $aArgs['orderBy'],
            'limit'     => $aArgs['limit']
        ]);
        
        return $aResources;
    }

    public static function get(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data']);

        $aResources = DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => ['res_letterbox'],
            'where'     => $aArgs['where'],
            'data'      => $aArgs['data']
        ]);

        return $aResources;
    }

    public static function getById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $aResources = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['res_letterbox'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        return $aResources[0];
    }

    public static function getExtById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $aResources = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['mlb_coll_ext'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        if (empty($aResources[0])) {
            return [];
        }

        return $aResources[0];
    }

    public static function create(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['format', 'typist', 'creation_date', 'docserver_id', 'path', 'filename', 'fingerprint', 'filesize', 'status']);
        ValidatorModel::stringType($aArgs, ['format', 'typist', 'creation_date', 'docserver_id', 'path', 'filename', 'fingerprint', 'status']);
        ValidatorModel::intVal($aArgs, ['filesize']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'res_id_mlb_seq']);
        $aArgs['res_id'] = $nextSequenceId;

        DatabaseModel::insert([
            'table'         => 'res_letterbox',
            'columnsValues' => $aArgs
        ]);

        return $nextSequenceId;
    }

    public static function createExt(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['res_id', 'category_id']);
        ValidatorModel::stringType($aArgs, ['category_id']);
        ValidatorModel::intVal($aArgs, ['res_id']);

        DatabaseModel::insert([
            'table'         => 'mlb_coll_ext',
            'columnsValues' => $aArgs
        ]);

        return true;
    }

    public static function update(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['set', 'where', 'data']);
        ValidatorModel::arrayType($aArgs, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'res_letterbox',
            'set'   => $aArgs['set'],
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }

    public static function delete(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        DatabaseModel::update([
            'table' => 'res_letterbox',
            'set'   => [
                'status'    => 'DEL'
            ],
            'where' => ['res_id = ?'],
            'data'  => [$aArgs['resId']]
        ]);

        return true;
    }

    public static function isLock(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId', 'userId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $aReturn = DatabaseModel::select([
            'select'    => ['locker_user_id', 'locker_time'],
            'table'     => ['res_letterbox'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        $lock = true;
        $lockBy = empty($aReturn[0]['locker_user_id']) ? '' : $aReturn[0]['locker_user_id'];

        if (empty($aReturn[0]['locker_user_id'] || empty($aReturn[0]['locker_time']))) {
            $lock = false;
        } elseif ($aReturn[0]['locker_user_id'] == $aArgs['userId']) {
            $lock = false;
        } elseif (strtotime($aReturn[0]['locker_time']) < time()) {
            $lock = false;
        }

        return ['lock' => $lock, 'lockBy' => $lockBy];
    }

    public static function getDocsByClause(array $aArgs = [])
    {
		ValidatorModel::notEmpty($aArgs, ['clause']);

        if (!empty($aArgs['table'])) {
            $table = $aArgs['table'];
        } else {
            $table = 'res_view_letterbox';
        }

        $aReturn = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => [$table],
            'where'     => [$aArgs['clause']],
            'order_by'  => ['res_id']
        ]);

        return $aReturn;
    }

    public static function getResIdByAltIdentifier(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['altIdentifier']);
        ValidatorModel::stringType($aArgs, ['altIdentifier']);

        $aResources = DatabaseModel::select([
            'select'    => ['res_id'],
            'table'     => ['mlb_coll_ext'],
            'where'     => ['alt_identifier = ?'],
            'data'      => [$aArgs['altIdentifier']]
        ]);

        if (empty($aResources[0])) {
            return [];
        }

        return $aResources[0];
    }

    public static function getStoredProcessLimitDate(array $aArgs)
    {
        ValidatorModel::intVal($aArgs, ['resId', 'typeId']);
        ValidatorModel::stringType($aArgs, ['admissionDate']);

        if (!empty($aArgs['typeId'])) {
            $typeId = $aArgs['type_id'];
        } else {
            $document = ResModel::getById(['resId' => $aArgs['resId'], 'select' => ['type_id']]);
            $typeId = $document['type_id'];
        }

        $processDelay = 30;
        if (!empty($typeId)) {
            $doctypeExt = DatabaseModel::select([
                'select'    => ['process_delay'],
                'table'     => ['mlb_doctype_ext'],
                'where'     => ['type_id = ?'],
                'data'      => [$typeId]
            ]);
            $processDelay = $doctypeExt[0]['process_delay'];
        }

        if (!empty($aArgs['admissionDate'])) {
            if (strtotime($aArgs['admissionDate']) === false) {
                $defaultDate = date('c');
            } else {
                $defaultDate = $aArgs['admissionDate'];
            }
        } else {
            $defaultDate = date('c');
        }

        $date = new \DateTime($defaultDate);

        $calendarType = 'calendar';
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/features.xml']);

        if ($loadedXml && !empty((string)$loadedXml->FEATURES->type_calendar)) {
            $calendarType = (string)$loadedXml->FEATURES->type_calendar;
        }

        if ($calendarType == 'workingDay') {
            $hollidays = [
                '01-01',
                '01-05',
                '08-05',
                '14-07',
                '15-08',
                '01-11',
                '11-11',
                '25-12'
            ];
            $hollidays[] = date('d-m', easter_date() + 86400);

            $processDelayUpdated = 1;
            for ($i = 1; $i <= $processDelay; $i++) {
                $tmpDate = new \DateTime($defaultDate);
                $tmpDate->add(new \DateInterval("P{$i}D"));
                if (in_array($tmpDate->format('N'), [6, 7]) || in_array($tmpDate->format('d-m'), $hollidays)) {
                    ++$processDelay;
                }
                ++$processDelayUpdated;
            }

            $date->add(new \DateInterval("P{$processDelayUpdated}D"));
        } else {
            $date->add(new \DateInterval("P{$processDelay}D"));
        }

        return $date->format('Y-m-d H:i:s');
    }
}
