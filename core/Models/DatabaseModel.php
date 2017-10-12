<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Database Model
 * @author dev@maarch.org
 * @ingroup core
 */

namespace Core\Models;

class DatabaseModel
{

    /**
     * Database Unique Id Function
     *
     * @return string $uniqueId
     */
    public static function uniqueId()
    {
        $parts = explode('.', microtime(true));
        $sec = $parts[0];
        if (!isset($parts[1])) {
            $msec = 0;
        } else {
            $msec = $parts[1];
        }

        $uniqueId = str_pad(base_convert($sec, 10, 36), 6, '0', STR_PAD_LEFT);
        $uniqueId .= str_pad(base_convert($msec, 10, 16), 4, '0', STR_PAD_LEFT);
        $uniqueId .= str_pad(base_convert(mt_rand(), 10, 36), 6, '0', STR_PAD_LEFT);

        return $uniqueId;
    }

    /**
     * Database Nextval Sequence Function
     * @param array $args
     *
     * @return int
     */
    public static function getNextSequenceValue(array $args)
    {
        ValidatorModel::notEmpty($args, ['sequenceId']);
        ValidatorModel::stringType($args, ['sequenceId']);

        $query = "SELECT nextval('{$args['sequenceId']}')";

        $db = new DatabasePDO();
        $stmt = $db->query($query);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row['nextval'];
    }

    /**
     * Database Select Function
     * @param array $args
     * @throws \Exception if number of tables is different from number of joins
     *
     * @return array
     */
    public static function select(array $args)
    {
        ValidatorModel::notEmpty($args, ['select', 'table']);
        ValidatorModel::arrayType($args, ['select', 'table']);

        $tmpTable = $args['table'];
        $args['table'] = $args['table'][0];

        if (!empty($args['left_join'])) {
            ValidatorModel::arrayType($args, ['left_join']);
            if (count($tmpTable) - 1 != count($args['left_join'])) {
                throw new \Exception('Number of tables doesn\'t match with number of joins');
            }
            $i = 1;
            foreach ($args['left_join'] as $value) {
                $args['table'] .=  " LEFT JOIN {$tmpTable[$i]} ON {$value}";
                $i++;
            }
        }

        $select = implode(', ', $args['select']);

        if (empty($args['where'])) {
            $where = '';
        } else {
            ValidatorModel::arrayType($args, ['where']);
            $where = ' WHERE ' . implode(' AND ', $args['where']);
        }

        if (empty($args['group_by'])) {
            $groupBy = '';
        } else {
            ValidatorModel::arrayType($args, ['group_by']);
            $groupBy = ' GROUP BY ' . implode(', ', $args['group_by']);
        }

        if (empty($args['order_by'])) {
            $orderBy = '';
        } else {
            ValidatorModel::arrayType($args, ['order_by']);
            $orderBy = ' ORDER BY ' . implode(', ', $args['order_by']);
        }

        if (empty($args['limit'])) {
            $limit = '';
        } else {
            ValidatorModel::intType($args, ['limit']);
            $limit = $args['limit'];
        }

        if (empty($args['data'])) {
            $args['data'] = [];
        }
        ValidatorModel::arrayType($args, ['data']);


        $db = new DatabasePDO();

        if (!empty($limit)) {
            $limitData = $db->setLimit(['where' => $where, 'limit' => $limit]);
            $where = $limitData['where'];
            $limit = $limitData['limit'];
        }

        $query = "SELECT {$select} FROM {$args['table']}{$where}{$groupBy}{$orderBy}{$limit}";

        $stmt = $db->query($query, $args['data']);

        $rowset = [];
        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $rowset[] = $row;
        }

        return $rowset;
    }

    /**
     * Database Insert Into Function
     * @param array $args
     *
     * @return bool
     */
    public static function insert(array $args)
    {
        ValidatorModel::notEmpty($args, ['table', 'columnsValues']);
        ValidatorModel::stringType($args, ['table']);
        ValidatorModel::arrayType($args, ['columnsValues']);

        $data = [];
        $columnsQuery = [];
        $valuesQuery = [];
        foreach ($args['columnsValues'] as $key => $value) {
            if ($value == 'SYSDATE' || $value == 'CURRENT_TIMESTAMP') {
                $valuesQuery[] = $value;
            } else {
                $valuesQuery[] = '?';
                $data[] = $value;
            }
            $columnsQuery[] = $key;
        }
        $columns = implode(', ', $columnsQuery);
        $values = implode(', ', $valuesQuery);

        $query = "INSERT INTO {$args['table']} ({$columns}) VALUES ({$values})";

        $db = new DatabasePDO();
        $db->query($query, $data);

        return true;
    }

    /**
     * Database Update Function
     * @param array $args
     *
     * @return bool
     */
    public static function update(array $args)
    {
        ValidatorModel::notEmpty($args, ['table', 'set', 'where']);
        ValidatorModel::stringType($args, ['table']);
        ValidatorModel::arrayType($args, ['set', 'where']);

        if (empty($args['data'])) {
            $args['data'] = [];
        }
        ValidatorModel::arrayType($args, ['data']);

        $querySet  = [];
        $dataSet = [];
        foreach ($args['set'] as $key => $value) {
            $querySet[] = "{$key} = ?";
            $dataSet[] = $value;
        }
        $args['data'] = array_merge($dataSet, $args['data']);
        $set = implode(', ', $querySet);
        $where = implode(' AND ', $args['where']);

        $query = "UPDATE {$args['table']} SET {$set} WHERE {$where}";

        $db = new DatabasePDO();
        $db->query($query, $args['data']);

        return true;
    }

    /**
     * Database Delete From Function
     * @param array $args
     *
     * @return bool
     */
    public static function delete(array $args)
    {
        ValidatorModel::notEmpty($args, ['table', 'where']);
        ValidatorModel::stringType($args, ['table']);
        ValidatorModel::arrayType($args, ['where']);

        if (empty($args['data'])) {
            $args['data'] = [];
        }
        ValidatorModel::arrayType($args, ['data']);

        $where = implode(' AND ', $args['where']);
        $query = "DELETE FROM {$args['table']} WHERE {$where}";

        $db = new DatabasePDO();
        $db->query($query, $args['data']);

        return true;
    }
}
