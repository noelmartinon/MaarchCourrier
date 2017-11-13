<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.

 * @brief   Parameter Model Abstract
 * @author  dev <dev@maarch.org>
 * @ingroup core
 */

namespace Core\Models;

class ParameterModelAbstract
{
    public static function getById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['id']);

        $parameter = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['parameters'],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        if (empty($parameter[0])) {
            return [];
        }
        if (!empty($parameter[0]['param_value_date'])) {
            $parameter[0]['param_value_date'] = TextFormatModel::formatDate($parameter[0]['param_value_date']);
        }

        return $parameter[0];
    }

    public static function create(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['id', 'description', 'param_value_string']);
        ValidatorModel::intVal($aArgs, ['param_value_int']);

        DatabaseModel::insert([
            'table'         => 'parameters',
            'columnsValues' => [
                'id'                    => $aArgs['id'],
                'description'           => $aArgs['description'],
                'param_value_string'    => $aArgs['param_value_string'],
                'param_value_int'       => $aArgs['param_value_int'],
                'param_value_date'      => $aArgs['param_value_date'],
            ]
        ]);

        return true;
    }

    public static function update(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['id']);

        $aReturn = DatabaseModel::update([
            'table'     => 'parameters',
            'set'       => $aArgs,
            'where'     => ['id = ?'],
            'data'      => [$aArgs['id']]
        ]);

        return $aReturn;
    }

    public static function delete(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['id']);

        $aReturn = DatabaseModel::delete([
            'table' => 'parameters',
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        return $aReturn;
    }

}
