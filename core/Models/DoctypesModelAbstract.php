<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Docserver Model
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Models;

require_once 'apps/maarch_entreprise/services/Table.php';

class DoctypesModelAbstract extends \Apps_Table_Service
{
    public static function getList()
    {
        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['doctypes'],
        ]);

        return $aReturn;
    }

    public static function getByTypeId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['types_id']);
        static::checkString($aArgs, ['types_id']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['doctypes'],
            'where'     => ['types_id = ?'],
            'data'      => [$aArgs['types_id']]
        ]);

        return $aReturn;
    }
}
