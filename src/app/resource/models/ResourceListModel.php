<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Resource List Model
* @author dev@maarch.org
*/

namespace Resource\models;

use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class ResourceListModel
{
    public static function get(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resIds']);
        ValidatorModel::arrayType($aArgs, ['resIds']);

        $order = 'CASE res_letterbox.res_id ';
        foreach ($aArgs['resIds'] as $key => $resId) {
            $order .= "WHEN {$resId} THEN {$key} ";
        }
        $order .= 'END';

        $resources = DatabaseModel::select([
            'select'    => [
                'res_letterbox.res_id',
                'res_letterbox.subject',
                'res_letterbox.creation_date',
                'res_letterbox.barcode',
                'mlb_coll_ext.alt_identifier',
                'mlb_coll_ext.category_id',
                'mlb_coll_ext.closing_date',
                'mlb_coll_ext.process_limit_date',
                'mlb_coll_ext.is_multicontacts',
                'entities.entity_label as entity_destination',
                'doctypes.description as doctype_label',
                'contacts_v2.firstname as contact_firstname',
                'contacts_v2.lastname as contact_lastname',
                'contacts_v2.society as contact_society',
                'users.firstname as user_firstname',
                'users.lastname as user_lastname',
                'priorities.color as priority_color',
                'priorities.label as priority_label',
                'status.img_filename as status_icon',
                'status.label_status as status_label',
                'status.id as status_id',
                'us.lastname as user_dest_lastname',
                'us.firstname as user_dest_firstname',
            ],
            'table'     => ['res_letterbox', 'mlb_coll_ext', 'entities', 'doctypes', 'contacts_v2', 'users', 'priorities', 'status', 'users us'],
            'left_join' => [
                'res_letterbox.res_id = mlb_coll_ext.res_id',
                'res_letterbox.destination = entities.entity_id',
                'res_letterbox.type_id = doctypes.type_id',
                'mlb_coll_ext.exp_contact_id = contacts_v2.contact_id OR mlb_coll_ext.dest_contact_id = contacts_v2.contact_id',
                'mlb_coll_ext.exp_user_id = users.user_id OR mlb_coll_ext.dest_user_id = users.user_id',
                'res_letterbox.priority = priorities.id',
                'res_letterbox.status = status.id',
                'res_letterbox.dest_user = us.user_id'
            ],
            'where'     => ['res_letterbox.res_id in (?)'],
            'data'      => [$aArgs['resIds']],
            'order_by'  => [$order]
        ]);

        return $resources;
    }

    public static function getOnResource(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'table', 'leftJoin', 'where', 'data', 'orderBy', 'groupBy']);
        ValidatorModel::intType($aArgs, ['limit', 'offset']);

        $aResources = DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => array_merge(['res_letterbox'], $aArgs['table']),
            'left_join' => empty($aArgs['leftJoin']) ? [] : $aArgs['leftJoin'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'groupBy'   => empty($aArgs['groupBy']) ? [] : $aArgs['groupBy'],
            'offset'    => empty($aArgs['offset']) ? 0 : $aArgs['offset'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);

        return $aResources;
    }

    public static function getOnView(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'table', 'leftJoin', 'where', 'data', 'orderBy', 'groupBy']);
        ValidatorModel::intType($aArgs, ['limit', 'offset']);

        $aResources = DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => array_merge(['res_view_letterbox'], $aArgs['table']),
            'left_join' => empty($aArgs['leftJoin']) ? [] : $aArgs['leftJoin'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'groupBy'   => empty($aArgs['groupBy']) ? [] : $aArgs['groupBy'],
            'offset'    => empty($aArgs['offset']) ? 0 : $aArgs['offset'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);

        return $aResources;
    }
}
