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

abstract class ResModelAbstract
{
    public static function getOnView(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy', 'groupBy']);
        ValidatorModel::intType($aArgs, ['limit', 'offset']);

        $aResources = DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => ['res_view_letterbox'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'groupBy'   => empty($aArgs['groupBy']) ? [] : $aArgs['groupBy'],
            'offset'    => empty($aArgs['offset']) ? 0 : $aArgs['offset'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);
        
        return $aResources;
    }

    public static function get(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy']);
        ValidatorModel::intType($aArgs, ['limit']);

        $aResources = DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => ['res_letterbox'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);

        return $aResources;
    }

    public static function getExt(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data']);

        $aResources = DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => ['mlb_coll_ext'],
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
            'table'     => empty($aArgs['table']) ? ['res_letterbox'] : array_merge(['res_letterbox'], $aArgs['table']),
            'left_join' => empty($aArgs['leftJoin']) ? [] : $aArgs['leftJoin'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        if (empty($aResources[0])) {
            return [];
        }

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
        ValidatorModel::intVal($aArgs, ['filesize', 'res_id']);

        if (empty($aArgs['res_id'])) {
            $aArgs['res_id'] = DatabaseModel::getNextSequenceValue(['sequenceId' => 'res_id_mlb_seq']);
        }

        DatabaseModel::insert([
            'table'         => 'res_letterbox',
            'columnsValues' => $aArgs
        ]);

        return $aArgs['res_id'];
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

    public static function updateExt(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['set', 'where', 'data']);
        ValidatorModel::arrayType($aArgs, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'mlb_coll_ext',
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

    public static function getLastResources(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['limit', 'userId', 'select']);
        ValidatorModel::intType($aArgs, ['limit']);
        ValidatorModel::stringType($aArgs, ['userId']);
        ValidatorModel::arrayType($aArgs, ['select']);

        $resources = DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => ['history, res_letterbox r, mlb_coll_ext mlb, status, priorities'],
            'where'     => [
                'history.user_id = ?', 'history.table_name IN (?)',
                'history.record_id IS NOT NULL', 'history.record_id != ?',
                'history.event_id != ?', 'history.event_id NOT LIKE ?',
                'CAST(history.record_id AS INT) = r.res_id',
                'r.res_id = r.res_id', 'r.status != ?',
                'r.status = status.id',
                'r.priority = priorities.id',
                'r.res_id = mlb.res_id',
            ],
            'data'      => [$aArgs['userId'], ['res_letterbox', 'res_view_letterbox'], 'none', 'linkup', 'attach%', 'DEL'],
            'groupBy'   => ['r.subject', 'r.creation_date', 'r.res_id', 'mlb.alt_identifier', 'mlb.closing_date', 'mlb.process_limit_date', 'status.id', 'status.label_status', 'status.img_filename', 'priorities.color', 'priorities.label'],
            'order_by'  => ['MAX(history.event_date) DESC'],
            'limit'     => $aArgs['limit']
        ]);

        return $resources;
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
            if (function_exists('easter_date')) {
                $hollidays[] = date('d-m', easter_date() + 86400);
            }

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

    public static function getCategories()
    {
        static $categories;

        if (!empty($categories)) {
            return $categories;
        }

        $categories = [];

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);
        if ($loadedXml) {
            foreach ($loadedXml->COLLECTION as $collection) {
                $collection = (array)$collection;

                if ($collection['id'] == 'letterbox_coll') {
                    foreach ($collection['categories']->category as $category) {
                        $category = (array)$category;

                        $categories[] = [
                            'id'                => $category['id'],
                            'label'             => defined($category['label']) ? constant($category['label']) : $category['label'],
                            'defaultCategory'   => $category['id'] == $collection['categories']->default_category
                        ];
                    }
                }
            }
        }

        return $categories;
    }

    public static function getCategoryLabel(array $args)
    {
        ValidatorModel::stringType($args, ['categoryId']);

        $categories = ResModel::getCategories();
        foreach ($categories as $category) {
            if ($category['id'] == $args['categoryId']) {
                return $category['label'];
            }
        }

        return '';
    }

    public static function getNatures()
    {
        static $natures;

        if (!empty($natures)) {
            return $natures;
        }

        $natures = [];

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/entreprise.xml']);
        if ($loadedXml) {
            foreach ($loadedXml->mail_natures->nature as $nature) {
                $withReference = (string)$nature['with_reference'] == 'true' ? true : false;
                $nature = (array)$nature;

                $natures[] = [
                    'id'            => $nature['id'],
                    'label'         => defined($nature['label']) ? constant($nature['label']) : $nature['label'],
                    'withReference' => $withReference,
                    'defaultNature' => $nature['id'] == $loadedXml->mail_natures->default_nature
                ];
            }
        }

        return $natures;
    }

    public static function getNatureLabel(array $args)
    {
        ValidatorModel::stringType($args, ['natureId']);

        $natures = ResModel::getNatures();
        foreach ($natures as $nature) {
            if ($nature['id'] == $args['natureId']) {
                return $nature['label'];
            }
        }

        return '';
    }

    public static function getNbContactsByResId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $aResources = DatabaseModel::select([
            'select'    => ['count(1) as nb_contacts'],
            'table'     => ['contacts_res'],
            'where'     => ['res_id = ?', 'mode = ?'],
            'data'      => [$aArgs['resId'], 'multi']
        ]);
        return $aResources[0]['nb_contacts'];
    }
}
