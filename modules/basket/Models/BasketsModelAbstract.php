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

use Core\Models\DatabaseModel;
use Core\Models\UserModel;
use Core\Models\ValidatorModel;

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

    public static function getBasketsByUserId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);
        ValidatorModel::arrayType($aArgs, ['rejectedBaskets']);


        $userGroup = UserModel::getPrimaryGroupById(['userId' => $aArgs['userId']]);

        $select = [
            'select'    => ['DISTINCT basket_id'],
            'table'     => ['groupbasket'],
            'where'     => ['group_id = ?'],
            'data'      => [$userGroup['group_id']]
        ];
        if (!empty($aArgs['rejectedBaskets'])) {
            $select['where'][] = 'basket_id not in (?)';
            $select['data'][] = $aArgs['rejectedBaskets'];
        }

        $aRawBaskets = DatabaseModel::select($select);

        $basketIds = [];
        foreach ($aRawBaskets as $value) {
            $basketIds[] = $value['basket_id'];
        }

        $aBaskets = [];
        if (!empty($basketIds)) {
            $aBaskets = static::select(
                [
                    'select'    => ['basket_id', 'basket_name'],
                    'table'     => ['baskets'],
                    'where'     => ['basket_id in (?)', 'is_visible = ?'],
                    'data'      => [$basketIds, 'Y'],
                    'order_by'  => 'basket_order, basket_name'
                ]
            );
        }

        $aBaskets = array_merge($aBaskets, self::getSecondaryBasketsByUserId(['userId' => $aArgs['userId']]));
        foreach ($aBaskets as $key => $value) {
            $aBaskets[$key]['is_virtual'] = 'N';
            $aBaskets[$key]['basket_owner'] = $aArgs['userId'];
        }
        $aBaskets = array_merge($aBaskets, self::getAbsBasketsByUserId(['userId' => $aArgs['userId']]));

        return $aBaskets;
    }

    public static function getSecondaryBasketsByUserId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId']);
        static::checkString($aArgs, ['userId']);

        $aBaskets = static::select(
            [
                'select'    => ['ba.basket_id', 'ba.basket_name'],
                'table'     => ['baskets ba, user_baskets_secondary ubs'],
                'where'     => ['ubs.user_id = ?', 'ubs.basket_id = ba.basket_id'],
                'data'      => [$aArgs['userId']],
                'order_by'  => 'ba.basket_order, ba.basket_name'
            ]
        );

        return $aBaskets;
    }

    public static function getAbsBasketsByUserId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId']);
        static::checkString($aArgs, ['userId']);

        $aBaskets = static::select(
            [
                'select'    => ['ba.basket_id', 'ba.basket_name', 'ua.user_abs', 'ua.basket_owner', 'ua.is_virtual'],
                'table'     => ['baskets ba, user_abs ua'],
                'where'     => ['ua.new_user = ?', 'ua.basket_id = ba.basket_id'],
                'data'      => [$aArgs['userId']],
                'order_by'  => 'ba.basket_order, ba.basket_name'
            ]
        );

        foreach ($aBaskets as $key => $value) {
            $aBaskets[$key]['userToDisplay'] = UserModel::getLabelledUserById(['id' => $value['user_abs']]);
        }

        return $aBaskets;
    }

    public static function getRegroupedBasketsByUserId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $regroupedBaskets = [];

        $user = UserModel::getById(['userId' => $aArgs['userId'], 'select' => ['id']]);

        $groups = UserModel::getGroupsById(['userId' => $aArgs['userId']]);
        foreach ($groups as $group) {
            $baskets = DatabaseModel::select([
                'select'    => ['baskets.basket_id', 'baskets.basket_name', 'baskets.color'],
                'table'     => ['groupbasket, baskets'],
                'where'     => ['groupbasket.basket_id = baskets.basket_id', 'groupbasket.group_id = ?', 'baskets.is_visible = ?', 'baskets.basket_id != ?'],
                'data'      => [$group['group_id'], 'Y', 'IndexingBasket'],
                'order_by'  => ['baskets.basket_order', 'baskets.basket_name']
            ]);
            $coloredBaskets = DatabaseModel::select([
                'select'    => ['basket_id', 'color'],
                'table'     => ['users_baskets'],
                'where'     => ['user_serial_id = ?', 'group_id = ?', 'color is not null'],
                'data'      => [$user['id'], $group['group_id']]
            ]);

            foreach ($baskets as $kBasket => $basket) {
                foreach ($coloredBaskets as $coloredBasket) {
                    if ($basket['basket_id'] == $coloredBasket['basket_id']) {
                        $baskets[$kBasket]['color'] = $coloredBasket['color'];
                    }
                }
                if (empty($baskets[$kBasket]['color'])) {
                    $baskets[$kBasket]['color'] = '#666666';
                }
            }

            $regroupedBaskets[] = [
              'groupId'     => $group['group_id'],
              'groupDesc'   => $group['group_desc'],
              'baskets'     => $baskets
            ];
        }

        return $regroupedBaskets;
    }

    public static function getColoredBasketsByUserId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $user = UserModel::getById(['userId' => $aArgs['userId'], 'select' => ['id']]);

        $coloredBaskets = DatabaseModel::select([
            'select'    => ['basket_id', 'group_id', 'color'],
            'table'     => ['users_baskets'],
            'where'     => ['user_serial_id = ?', 'color is not null'],
            'data'      => [$user['id']]
        ]);

        return $coloredBaskets;
    }

    public static function setBasketsRedirection(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId', 'data']);
        static::checkString($aArgs, ['userId']);
        static::checkArray($aArgs, ['data']);


        foreach ($aArgs['data'] as $value) {
            parent::insertInto(
                [
                    'user_abs'      => $aArgs['userId'],
                    'new_user'      => $value['newUser'],
                    'basket_id'     => $value['basketId'],
                    'basket_owner'  => $value['basketOwner'],
                    'is_virtual'    => $value['virtual']
                ],
                'user_abs'
            );
        }

        return true;
    }

    public static function updateBasketsRedirection(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId', 'basketOwner', 'basketId', 'userAbs', 'newUser']);
        static::checkString($aArgs, ['userId']);

        $isUpdated = parent::update([
            'table'     => 'user_abs',
            'set'       => [
                'new_user' => $aArgs['newUser']
            ],
            'where'     => ['basket_id = ?', 'basket_owner = ?', 'user_abs = ?', 'new_user = ?'],
            'data'      => [$aArgs['basketId'], $aArgs['basketOwner'], $aArgs['userAbs'], $aArgs['userId']]
        ]);

        return true;
    }

    public static function deleteBasketRedirection(array $aArgs)
    {
        static::checkRequired($aArgs, ['userId', 'basketId']);
        static::checkString($aArgs, ['userId', 'basketId']);

        parent::deleteFrom([
            'table' => 'user_abs',
            'where' => ['(user_abs = ? OR basket_owner = ?)', 'basket_id = ?'],
            'data'  => [$aArgs['userId'], $aArgs['userId'], $aArgs['basketId']]
        ]);

        return true;
    }

    public static function getBasketsRedirectedByUserId(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['userId']);
        static::checkString($aArgs, ['userId']);


        $aBaskets = static::select(
            [
                'select'    => ['ba.basket_id', 'ba.basket_name', 'ua.new_user', 'ua.basket_owner'],
                'table'     => ['baskets ba, user_abs ua'],
                'where'     => ['ua.user_abs = ?', 'ua.basket_id = ba.basket_id'],
                'data'      => [$aArgs['userId']],
                'order_by'  => 'ua.system_id'
            ]
        );

        foreach ($aBaskets as $key => $value) {
            $user = UserModel::getById(['userId' => $value['new_user'], 'select' => ['firstname', 'lastname']]);
            $aBaskets[$key]['userToDisplay']     = "{$user['firstname']} {$user['lastname']}";
            $aBaskets[$key]['userIdRedirection'] = $value['new_user'];
            $aBaskets[$key]['user']              = "{$user['firstname']} {$user['lastname']}" ;
        }

        return $aBaskets;
    }

}