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
}
