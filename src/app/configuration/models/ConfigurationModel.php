<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Configuration Model
* @author dev@maarch.org
*/

namespace Configuration\models;

use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class ConfigurationModel
{
    public static function getByService(array $args)
    {
        ValidatorModel::notEmpty($args, ['service']);
        ValidatorModel::stringType($args, ['service']);
        ValidatorModel::arrayType($args, ['select']);

        $configuration = DatabaseModel::select([
            'select'    => empty($args['select']) ? ['*'] : $args['select'],
            'table'     => ['configurations'],
            'where'     => ['service = ?'],
            'data'      => [$args['service']],
        ]);

        if (empty($configuration[0])) {
            return [];
        }

        return $configuration[0];
    }

    public static function create(array $args)
    {
        ValidatorModel::notEmpty($args, ['service', 'value']);
        ValidatorModel::stringType($args, ['service', 'value']);

        DatabaseModel::insert([
            'table'         => 'configurations',
            'columnsValues' => [
                'service'   => $args['service'],
                'value'     => $args['value']
            ]
        ]);

        return true;
    }

    public static function update(array $args)
    {
        ValidatorModel::notEmpty($args, ['set', 'where', 'data']);
        ValidatorModel::arrayType($args, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'configurations',
            'set'   => $args['set'],
            'where' => $args['where'],
            'data'  => $args['data']
        ]);

        return true;
    }
}
