<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

require_once 'apps/maarch_entreprise/services/Table.php';

class VisaModelAbstract extends Apps_Table_Service
{

    public static function hasVisaWorkflowByResId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['resId']);
        static::checkNumeric($aArgs, ['resId']);


        $aReturn = static::select([
            'select'    => ['COUNT(*) as count'],
            'table'     => ['listinstance'],
            'where'     => ['res_id = ?', 'item_mode in (?)'],
            'data'      => [$aArgs['resId'], ['visa', 'sign']]
        ]);


        return ((int)$aReturn[0]['count'] > 0);
    }
}
