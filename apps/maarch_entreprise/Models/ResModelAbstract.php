<?php

/*
*    Copyright 2015 Maarch
*
*  This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'apps/maarch_entreprise/services/Table.php';

class ResModelAbstract extends Apps_Table_Service
{

    public static function getById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['resId']);
        static::checkNumeric($aArgs, ['resId']);


        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['res_view_letterbox'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        return $aReturn[0];
    }

    public static function getMlbCollExtById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['resId']);
        static::checkNumeric($aArgs, ['resId']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['mlb_coll_ext'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        return $aReturn[0];
    }

    public static function put(array $aArgs = [])
    {
        // TODO collId stands for table in DB => à Changer pour aller récupérer la table lié à collId
        static::checkRequired($aArgs, ['collId', 'set', 'where', 'data']);
        static::checkString($aArgs, ['collId']);
        static::checkArray($aArgs, ['set', 'where', 'data']);

        $bReturn = static::update([
            'table'     => $aArgs['collId'],
            'set'       => $aArgs['set'],
            'where'     => $aArgs['where'],
            'data'      => $aArgs['data']
        ]);

        return $bReturn;
    }

    public static function getAvailableLinkedAttachmentsIn(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['resIdMaster', 'in']);
        static::checkNumeric($aArgs, ['resIdMaster']);
        static::checkArray($aArgs, ['in']);


        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['res_view_attachments'],
            'where'     => ['res_id_master = ?', 'attachment_type in (?)', "status not in ('DEL', 'TMP', 'OBS')"],
            'data'      => [$aArgs['resIdMaster'], $aArgs['in']]
        ]);

        return $aReturn;
    }

    public static function getAvailableLinkedAttachmentsNotIn(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['resIdMaster', 'notIn']);
        static::checkNumeric($aArgs, ['resIdMaster']);
        static::checkArray($aArgs, ['notIn']);


        $select = [
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['res_view_attachments'],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', "status not in ('DEL', 'TMP', 'OBS')"],
            'data'      => [$aArgs['resIdMaster'], $aArgs['notIn']],
        ];
        if (!empty($aArgs['orderBy'])) {
            $select['order_by'] = $aArgs['orderBy'];
        }

        $aReturn = static::select($select);

        return $aReturn;
    }

    public static function getAvailableAndTemporaryLinkedAttachmentsNotIn(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['resIdMaster', 'notIn']);
        static::checkNumeric($aArgs, ['resIdMaster']);
        static::checkArray($aArgs, ['notIn']);


        $select = [
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['res_view_attachments'],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', "status not in ('DEL', 'OBS')"],
            'data'      => [$aArgs['resIdMaster'], $aArgs['notIn']],
        ];
        if (!empty($aArgs['orderBy'])) {
            $select['order_by'] = $aArgs['orderBy'];
        }

        $aReturn = static::select($select);

        return $aReturn;
    }

    public static function getObsLinkedAttachmentsNotIn(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['resIdMaster', 'notIn']);
        static::checkNumeric($aArgs, ['resIdMaster']);
        static::checkArray($aArgs, ['notIn']);


        $select = [
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['res_view_attachments'],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', 'status = ?'],
            'data'      => [$aArgs['resIdMaster'], $aArgs['notIn'], 'OBS'],
            'order_by'  => 'relation ASC'
        ];

        $aReturn = static::select($select);

        return $aReturn;
    }

}