<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Password Model
 * @author dev@maarch.org
 */

namespace Core\Models;

require_once 'apps/maarch_entreprise/services/Table.php';

class PasswordModel extends \Apps_Table_Service
{
    public static function getRules(array $aArgs = [])
    {
        $aRules = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['password_rules'],
            'where'     => $aArgs['where'],
            'data'      => $aArgs['data'],
        ]);

        return $aRules;
    }

    public static function getEnabledRules()
    {
        $aRules = static::select([
            'select'    => ['label', 'value'],
            'table'     => ['password_rules'],
            'where'     => ['enabled = ?'],
            'data'      => [true],
        ]);

        $formattedRules = [];
        foreach ($aRules as $rule) {
            if (strpos($rule['label'], 'complexity') === false) {
                $formattedRules[$rule['label']] = $rule['value'];
            } else {
                $formattedRules[$rule['label']] = true;
            }
        }

        return $formattedRules;
    }

    public static function isPasswordHistoryValid(array $aArgs)
    {
        $passwordRules = PasswordModel::getEnabledRules();

        if (!empty($passwordRules['historyLastUse'])) {
            $passwordHistory = static::select([
                'select'    => ['password'],
                'table'     => ['password_history'],
                'where'     => ['user_id = ?'],
                'data'      => [$aArgs['userId']],
                'order_by'  => ['id DESC'],
                'limit'     => $passwordRules['historyLastUse']
            ]);

            foreach ($passwordHistory as $value) {
                if (hash('sha512', $aArgs['password']) == $value['password']) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function setHistoryPassword(array $aArgs)
    {
        $passwordHistory = static::select([
            'select'    => ['id'],
            'table'     => ['password_history'],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']],
            'order_by'  => ['id DESC']
        ]);

        if (count($passwordHistory) >= 10) {
            static::deleteFrom([
                'table'     => 'password_history',
                'where'     => ['id < ?', 'user_id = ?'],
                'data'      => [$passwordHistory[8], $aArgs['userId']]
            ]);
        }

        static::insertInto(
            [
                'user_id'    => $aArgs['userId'],
                'password'          => SecurityModel::getPasswordHash($aArgs['password'])
            ],
            'password_history'
        );

        return true;
    }
}
