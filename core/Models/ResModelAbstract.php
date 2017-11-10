<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Res Model
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Models;

class ResModelAbstract
{
    /**
     * Retrieve info of resId
     * @param  $aArgs array
     *
     * @return array $res
     */
    public static function getById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::stringType($aArgs, ['table']);

        if (empty($aArgs['table'])) {
            $aArgs['table'] = 'res_letterbox';
        }

        $aReturn = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => [$aArgs['table']],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        if (empty($aReturn[0])) {
            return [];
        }

        return $aReturn[0];
    }

    /**
     * Retrieve info of last resId
     * @param  $aArgs array

     * @return array $res
     */
    public static function getLastId(array $aArgs = [])
    {
        if (!empty($aArgs['table'])) {
            $table = $aArgs['table'];
        } else {
            $table = 'res_letterbox';
        }

        $aReturn = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => [$table],
            'order_by'  => ['res_id desc'],
            'limit'     => 1,
        ]);

        return $aReturn;
    }

    public static function getDocsByClause(array $aArgs = []){
		ValidatorModel::notEmpty($aArgs, ['clause']);

        if (!empty($aArgs['table'])) {
            $table = $aArgs['table'];
        } else {
            $table = 'res_view_letterbox';
        }

        $aReturn = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => [$table],
            'where'     => [$aArgs['clause']],
            'order_by'  => ['res_id']
        ]);

        return $aReturn;
    }

    /**
     * Retrieve info of resId by path
     * @param  $aArgs array
     *
     * @return array $res
     */
    public static function getByPath(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['docserverId', 'path', 'filename']);
        ValidatorModel::stringType($aArgs, ['docserverId', 'path', 'filename', 'table']);


        if (!empty($aArgs['table'])) {
            $table = $aArgs['table'];
        } else {
            $table = 'res_letterbox';
        }

        $aReturn = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => [$table],
            'where'     => ['docserver_id = ? and path = ? and filename = ?'],
            'data'      => [$aArgs['docserverId'], $aArgs['path'], $aArgs['filename']],
            'order_by'  => ['res_id desc'],
        ]);

        return $aReturn;
    }

    /**
     * insert into a resTable
     * @param  $aArgs array
     *
     * @return boolean
     */
    public static function create(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['data']);
        ValidatorModel::arrayType($aArgs, ['data']);
        ValidatorModel::stringType($aArgs, ['table']);

        if (empty($aArgs['table'])) {
            $aArgs['table'] = 'res_letterbox';
        }

        DatabaseModel::insert([
            'table'         => $aArgs['table'],
            'columnsValues' => $aArgs['data']

        ]);

        return true;
    }

    /**
     * deletes into a resTable
     * @param  $aArgs array
     *
     * @return boolean
     */
    public static function delete(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['table']);

        if (empty($aArgs['table'])) {
            $aArgs['table'] = 'res_letterbox';
        }

        DatabaseModel::delete([
            'table' => $aArgs['table'],
            'where' => ['res_id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        return true;
    }

    /**
     * update a resTable
     * @param  $aArgs array
     *
     * @return boolean
     */
    public static function update(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['res_id']);
        ValidatorModel::intVal($aArgs, ['res_id']);
        ValidatorModel::stringType($aArgs, ['table']);
        ValidatorModel::arrayType($aArgs, ['data']);

        if (empty($aArgs['table'])) {
            $aArgs['table'] = 'res_letterbox';
        }

        DatabaseModel::update([
            'table' => $aArgs['table'],
            'set'   => $aArgs['data'],
            'where' => ['res_id = ?'],
            'data'  => [$aArgs['res_id']]
        ]);

        return true;
    }

    public static function isLockForCurrentUser(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $aReturn = DatabaseModel::select([
            'select'    => ['locker_user_id', 'locker_time'],
            'table'     => ['res_letterbox'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        $lock = true;
        $lockBy = empty($aReturn[0]['locker_user_id']) ? '' : $aReturn[0]['locker_user_id'];

        if (empty($aReturn[0]['locker_user_id'] || empty($aReturn[0]['locker_time']))) {
            $lock = false;
        } elseif ($aReturn[0]['locker_user_id'] == $_SESSION['user']['UserId']) { //TODO SESSION
            $lock = false;
        } elseif (strtotime($aReturn[0]['locker_time']) < time()) {
            $lock = false;
        }

        return ['lock' => $lock, 'lockBy' => $lockBy];
    }
}
