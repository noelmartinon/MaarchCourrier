<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Listinstance Model Abstract
* @author dev@maarch.org
* @ingroup listinstance
*/

namespace Core\Models;

class ListinstanceModelAbstract
{
    public static function getById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::arrayType($aArgs, ['select']);

        $aListinstance = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['listinstance'],
            'where'     => ['listinstance_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        if (empty($aListinstance[0])) {
            return [];
        }

        return $aListinstance[0];
    }

    public static function getCurrentStepByResId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::arrayType($aArgs, ['select']);

        $aListinstance = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['listinstance'],
            'where'     => ['res_id = ?', 'difflist_type = ?', 'process_date is null'],
            'data'      => [$aArgs['resId'], 'VISA_CIRCUIT'],
            'order_by'  => ['listinstance_id ASC'],
            'limit'     => 1
        ]);

        if (empty($aListinstance[0])) {
            return [];
        }

        return $aListinstance[0];
    }

    public static function setSignatory(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId', 'signatory', 'userId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::stringType($aArgs, ['signatory', 'userId']);

        DatabaseModel::update([
            'table'     => 'listinstance',
            'set'       => [
                'signatory' => $aArgs['signatory']
            ],
            'where'     => ['res_id = ?', 'item_id = ?', 'difflist_type = ?'],
            'data'      => [$aArgs['resId'], $aArgs['userId'], 'VISA_CIRCUIT'],
        ]);

        return true;

    }

    public static function setRequestedSignature(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::boolType($aArgs, ['requestedSignature']);

        if (!empty($aArgs['requestedSignature'])) {
            $aArgs['requestedSignature'] =  'true';
        } else {
            $aArgs['requestedSignature'] =  'false';
        }

        DatabaseModel::update([
            'table'     => 'listinstance',
            'set'       => [
                'requested_signature' => $aArgs['requestedSignature']
            ],
            'where'     => ['listinstance_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        return true;
    }
}
