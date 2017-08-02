<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Contact Model
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Models;

require_once 'apps/maarch_entreprise/services/Table.php';

class ContactModelAbstract extends \Apps_Table_Service
{
    public static function getCommunicationByContactId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['contactId']);
        static::checkString($aArgs, ['contactId']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['contact_communication'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['contactId']],
        ]);

        return $aReturn[0];
    }
}
