<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief   BasketModelAbstract
* @author  <dev@maarch.org>
* @ingroup basket
*/

namespace Baskets\Models;

use Core\Models\UserModel;

require_once 'apps/maarch_entreprise/services/Table.php';
require_once 'core/class/SecurityControler.php';

class BasketsModelAbstract extends \Apps_Table_Service
{

    public static function getResListById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['basketId']);
        static::checkString($aArgs, ['basketId']);


        $aBasket = static::select(
            [
            'select'    => ['basket_clause', 'basket_res_order'],
            'table'     => ['baskets'],
            'where'     => ['basket_id = ?'],
            'data'      => [$aArgs['basketId']]
            ]
        );

        if (empty($aBasket[0]) || empty($aBasket[0]['basket_clause'])) {
            return [];
        }

        $sec = new \SecurityControler();
        $where = $sec->process_security_where_clause($aBasket[0]['basket_clause'], $_SESSION['user']['UserId'], false);

        $aResList = static::select(
            [
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['res_view_letterbox'],
            'where'     => [$where],
            'order_by'  => empty($aBasket[0]['basket_res_order']) ? ['creation_date DESC'] : $aBasket[0]['basket_res_order'],
            ]
        );

        return $aResList;
    }

    public static function getActionByActionId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['actionId']);
        static::checkNumeric($aArgs, ['actionId']);


        $aAction = static::select(
            [
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['actions'],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['actionId']]
            ]
        );

        return $aAction[0];
    }

    public static function getActionIdById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['basketId']);
        static::checkString($aArgs, ['basketId']);


        $aAction = static::select(
            [
            'select'    => ['id_action'],
            'table'     => ['actions_groupbaskets'],
            'where'     => ['basket_id = ?'],
            'data'      => [$aArgs['basketId']]
            ]
        );

        if (empty($aAction[0])) {
            return '';
        }

        return $aAction[0]['id_action'];
    }

    public static function getDefaultActionIdByBasketId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['basketId', 'groupId']);
        static::checkString($aArgs, ['basketId', 'groupId']);

        $aAction = static::select(
            [
            'select'    => ['id_action'],
            'table'     => ['actions_groupbaskets'],
            'where'     => ['basket_id = ?', 'group_id = ?', 'default_action_list = \'Y\''],
            'data'      => [$aArgs['basketId'], $aArgs['groupId']]
            ]
        );

        if (empty($aAction[0])) {
            return '';
        }

        return $aAction[0]['id_action'];
    }

    public static function getBasketsByUserId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId']);
        static::checkString($aArgs, ['userId']);

        $rawUserGroups = UserModel::getGroupsById(['userId' => $aArgs['userId']]);

        $userGroups = [];
        if (!empty($rawUserGroups)) {
            foreach ($rawUserGroups as $value) {
                $userGroups[] = $value['group_id'];
            }

            $aRawBaskets = static::select(
                [
                    'select'    => ['DISTINCT basket_id'],
                    'table'     => ['groupbasket'],
                    'where'     => ['group_id in (?)'],
                    'data'      => [$userGroups]
                ]
            );
        }

        $basketIds = [];
        if (!empty($aRawBaskets)) {
            foreach ($aRawBaskets as $value) {
                $basketIds[] = $value['basket_id'];
            }

            $aBaskets = static::select(
                [
                    'select' => ['basket_id', 'basket_name'],
                    'table' => ['baskets'],
                    'where' => ['basket_id in (?)'],
                    'data' => [$basketIds],
                    'order_by' => 'basket_order, basket_name'
                ]
            );
        }
        if (empty($aBaskets)) {
            return '';
        }

        return $aBaskets;
    }

}