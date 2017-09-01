<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Entities Model
* @author dev@maarch.org
* @ingroup entities
*/

namespace Entities\Models;

require_once 'apps/maarch_entreprise/services/Table.php';

class EntitiesModelAbstract extends \Apps_Table_Service
{

    public static function getById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['entityId']);
        if (is_array($aArgs['entityId'])) {
            $where = ['entity_id in (?)'];
        } else {
            static::checkString($aArgs, ['entityId']);
            $where = ['entity_id = ?'];
        }

        $aEntities = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['entities'],
            'where'     => $where,
            'data'      => [$aArgs['entityId']]
        ]);

        if (empty($aEntities[0])) {
            return [];
        } elseif (is_array($aArgs['entityId'])) {
            return $aEntities;
        } else {
            return $aEntities[0];
        }
    }


    public static function getByEmail(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['email']);
        static::checkString($aArgs, ['email']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['entities'],
            'where'     => ['email = ? and enabled = ?'],
            'data'      => [$aArgs['email'], 'Y'],
            'limit'     => 1,
        ]);

        return $aReturn;
    }

    public static function getByBusinessId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['businessId']);
        static::checkString($aArgs, ['businessId']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['entities'],
            'where'     => ['business_id = ? and enabled = ?'],
            'data'      => [$aArgs['businessId'], 'Y'],
            'limit'     => 1,
        ]);

        return $aReturn;
    }

    public static function getByUserId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['user_id']);
        static::checkString($aArgs, ['user_id']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users_entities'],
            'where'     => ['user_id = ? and primary_entity = ?'],
            'data'      => [$aArgs['user_id'], 'Y'],
            'limit'     => 1,
        ]);

        return $aReturn;
    }

    public static function getEntitiesByUserId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['user_id']);
        static::checkString($aArgs, ['user_id']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['users_entities', 'entities'],
            'left_join' => ['users_entities.entity_id = entities.entity_id'],
            'where'     => ['user_id = ?', 'business_id <> \'\''],
            'data'      => [$aArgs['user_id']]
        ]);

        return $aReturn;
    }

    public static function getEntityRootById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['entityId']);
        static::checkString($aArgs, ['entityId']);

        $aReturn = self::getById([
            'select'   => ['entity_id', 'entity_label', 'parent_entity_id'],
            'entityId' => [$aArgs['entityId']]
        ]);

        if(!empty($aReturn[0]['parent_entity_id'])){
            $aReturn = self::getEntityRootById(['entityId' => $aReturn[0]['parent_entity_id']]);
        }

        return $aReturn;
    }

}
