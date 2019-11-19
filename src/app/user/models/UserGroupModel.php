<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief User Group Model
 * @author dev@maarch.org
 */

namespace User\models;

use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class UserGroupModel
{

    public static function delete(array $args)
    {
        ValidatorModel::notEmpty($args, ['where']);
        ValidatorModel::arrayType($args, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'usergroup_content',
            'where' => $args['where'],
            'data'  => $args['data'] ?? []
        ]);

        return true;
    }
}
