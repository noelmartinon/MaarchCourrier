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

namespace Core\models;

use SrcCore\models\SecurityModel;
use SrcCore\models\DatabaseModel;

class PasswordModel
{
    public static function getRules(array $aArgs = [])
    {
        $aRules = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['password_rules'],
            'where'     => $aArgs['where'],
            'data'      => $aArgs['data'],
        ]);

        return $aRules;
    }

    public static function getEnabledRules()
    {
        $aRules = DatabaseModel::select([
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
            $passwordHistory = DatabaseModel::select([
                'select'    => ['password'],
                'table'     => ['password_history'],
                'where'     => ['user_id = ?'],
                'data'      => [$aArgs['userId']],
                'order_by'  => ['id DESC'],
                'limit'     => $passwordRules['historyLastUse']
            ]);

            foreach ($passwordHistory as $value) {
                if (password_verify($aArgs['password'], $value['password'])) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function setHistoryPassword(array $aArgs)
    {
        $passwordHistory = DatabaseModel::select([
            'select'    => ['id'],
            'table'     => ['password_history'],
            'where'     => ['user_id = ?'],
            'data'      => [$aArgs['userId']],
            'order_by'  => ['id DESC']
        ]);

        if (count($passwordHistory) >= 10) {
            DatabaseModel::delete([
                'table'     => 'password_history',
                'where'     => ['id < ?', 'user_id = ?'],
                'data'      => [$passwordHistory[8], $aArgs['userId']]
            ]);
        }

        DatabaseModel::insert([
            'table'         => 'password_history',
            'columnsValues' => [
                'user_id'    => $aArgs['userId'],
                'password'   => SecurityModel::getPasswordHash($aArgs['password'])
            ]
        ]);

        return true;
    }
}
