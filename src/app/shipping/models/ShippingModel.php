<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
* @brief   Shipping Model
* @author  dev@maarch.org
*/

namespace Shipping\models;

use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

class ShippingModel
{
    public static function create(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId', 'attachmentId', 'accountId']);
        ValidatorModel::intVal($args, ['userId', 'attachmentId', 'recipientEntityId']);
        ValidatorModel::stringType($args, ['accountId']);
        ValidatorModel::boolType($args, ['isVersion']);

        DatabaseModel::insert([
            'table'         => 'shippings',
            'columnsValues' => [
                'user_id'               => $args['userId'],
                'attachment_id'         => $args['attachmentId'],
                'is_version'            => empty($args['isVersion']) ? 'false' : 'true',
                'options'               => $args['options'],
                'fee'                   => $args['fee'],
                'recipient_entity_id'   => $args['recipientEntityId'],
                'account_id'            => $args['accountId'],
                'creation_date'         => 'CURRENT_TIMESTAMP'
            ]
        ]);

        return true;
    }
}
