<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Authentication Model
* @author dev@maarch.org
*/

namespace Core\Models;

require_once 'apps/maarch_entreprise/services/Table.php';

class AuthenticationModel extends \Apps_Table_Service
{
    public static function resetFailedAuthentication(array $aArgs)
    {
        static::update([
            'table'     => 'users',
            'set'       => [
                'failed_authentication' => 0,
                'locked_until'          => null,
            ],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        return true;
    }

    public static function increaseFailedAuthentication(array $aArgs)
    {
        static::update([
            'table'     => 'users',
            'set'       => [
                'failed_authentication' => $aArgs['tentatives']
            ],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']]
        ]);

        return true;
    }

    public static function lockUser(array $aArgs)
    {
        static::update([
            'table' => 'users',
            'set'   => [
                'locked_until'  => date('Y-m-d H:i:s', $aArgs['lockedUntil'])
            ],
            'where' => ['user_id = ?'],
            'data'  => [$aArgs['userId']]
        ]);

        return true;
    }
}
