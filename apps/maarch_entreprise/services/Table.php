<?php
/*
*    Copyright 2017 Maarch
*
*  This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'core/services/Abstract.php';

// Prés-requis BDD :
require_once 'core/core_tables.php';
require_once 'core/class/class_functions.php';
require_once 'core/class/class_db_pdo.php';

/**
 * Fonctions pour réaliser le CRUD sur la base de donnees
 */
class Apps_Table_Service extends Core_Abstract_Service {
    /**
     * Récupération de la liste des méthodes disponibles via api
     * 
     * @return string[] La liste des méthodes
     */
    public static function getApiMethod() {
        $aApiMethod = parent::getApiMethod();
        return $aApiMethod;
    }

    /**
     * Permet de faire un select en BDD
     * @api apiurl
     * @param array $args donnée sur l'attachement
     *  - from/table : FROM [table]
     *  - string/array select : SELECT [select]
     *  - string/array where : WHERE [where]
     *  - data : for remplace ? on query
     *  - array conditions : [condition => valeur]
     * @return array       [description]
     */
    public static function select(array $args=[]){
        // Table :
        if ( !empty($args['from']) ) {
            $args['table'] = $args['from'];
        }
        if ( empty($args['table']) ) {
            throw new Core_MaarchException_Service('table empty');
        }
        if ( is_array($args['table']) ) {
            if (empty($args['table'][0])) {
                $args['table'] = array_values($args['table']);
            }
            $tmpTable = $args['table'];
            if (!empty($args['left_join'])) {
                $keywordJoin = ' LEFT JOIN ';
                $args['table'] = $args['table'][0];
                $args['join'] = $args['left_join'];
            } else {
                $keywordJoin = ' JOIN ';
                $args['table'] = $args['table'][0];
            }
            
            // Join :
            if ( ! empty($args['join']) ) {
                if ( ! is_array($args['join']) ) {
                    throw new Core_MaarchException_Service('where must be an array');
                } else if (count($tmpTable) - 1 != count($args['join'])) {
                    throw new Core_MaarchException_Service('Number of tables doesn\'t match with number of joins');
                }
                $z = 1;
                foreach ($args['join'] as $cond) {
                    if ( empty($args['where']) ) {
                        $args['where'] = [];
                    }
                    $args['table'] .=  $keywordJoin . $tmpTable[$z] . ' ON '.  $cond;
                    $z++;
                }

            }
        }
        if ( defined(strtoupper($args['table']).'_TABLE')) {
            $tablename = constant(strtoupper($args['table']).'_TABLE');
        } else {
            $tablename = $args['table'];
        }
        // Select :
        if ( ! empty($args['select']) ) {
            if ( is_array($args['select']) )
                $args['select'] = implode(',', $args['select']);
            if ( ! is_string($args['select']) ) {
                throw new Core_MaarchException_Service('select must be : string or array');
            }
        }
        $select = empty($args['select']) ? '*' : $args['select'];
        // Where :
        if ( empty($args['where']) ) {
            $args['where'] = [];
        }
        if ( is_string($args['where']) ) {
            $args['where'] = [$args['where']];
        }
        $aWhere = $args['where'];
        if ( ! is_array($aWhere) ) {
            throw new Core_MaarchException_Service('where must be : string or array');
        }
        // Data :
        if ( empty($args['data']) ) {
            $args['data'] = [];
        }
        if ( ! is_array($args['data']) ) {
            throw new Core_MaarchException_Service('data must be an array');
        }
        $data = $args['data'];
        // Conditions :
        if ( ! empty($args['conditions']) ) {
            if ( ! is_array($args['conditions']) ) {
                throw new Core_MaarchException_Service('where must be an array');
            }
            foreach ($args['conditions'] as $cond => $value) {
                $aWhere[] = $cond;
                $data[] = $value;
            }
        }
        // Fusion des données de recherche :
        $where = empty($aWhere) ? '' : ' WHERE '.implode(' AND ', $aWhere);

        // GroupBy :
        if ( empty($args['group_by']) ) {
            $group_by = '';
        } else {
            if ( is_array($args['group_by']) )
                $args['group_by'] = implode(',', $args['group_by']);
            if ( ! is_string($args['group_by']) ) {
                throw new Core_MaarchException_Service('group_by must be : string or array');
            }
            $group_by = ' GROUP BY '.$args['group_by'];
        }
        // OrderBy :
        if ( empty($args['order_by']) ) {
            $order_by = '';
        } else {
            if ( is_array($args['order_by']) )
                $args['order_by'] = implode(',', $args['order_by']);
            if ( ! is_string($args['order_by']) ) {
                throw new Core_MaarchException_Service('order_by must be : string or array');
            }
            $order_by = ' ORDER BY '.$args['order_by'];
        }
        // Limit :
        if ( empty($args['limit']) ) {
            $limit = '';
        } else {
            if ( ! is_numeric($args['limit']) ) {
                throw new Core_MaarchException_Service('limit must be : numeric');
            }
            if ($_SESSION['config']['databasetype'] == 'ORACLE') {
                $where .= ' AND ROWNUM <=' . $args['limit'];
            } else {
                $limit = ' LIMIT '.$args['limit'];
            }
        }

        if(!isset($GLOBALS['configFile'])){
            $GLOBALS['configFile'] = null;
        }
        $db = new Database($GLOBALS['configFile']);
        
        // Query :
        // if ($limit <> '') {
        //     $queryExt = $db->limit_select(0, $limit, $select, $tablename, $where, $group_by, '', $order_by);
        // } else {
            $queryExt = "SELECT $select FROM $tablename $where $group_by $order_by";
        //}
        
        //Core_Logs_Service::debug(['message'=>'Requête:'.$queryExt]); 
        //echo "the query " . $queryExt . PHP_EOL;var_export($data). PHP_EOL;
        
        $stmt = empty($data) ? $db->query($queryExt) : $db->query($queryExt, $data);

        $rowset = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rowset[] = $row;
        }
        return $rowset;
    }

    /**
     * Ajoute un row dans la base de données
     * @param array $aData donnée a ajouter
     * @param array $table table de l'ajout
     * @param string $getLastId
     * @return type        [description]
     */
    public static function insertInto(array $aData, $table, $getLastId = null){
        if ( ! is_string($table) ) {
            throw new Core_MaarchException_Service('$table is not a string');
        }
        $queryExtFields = [];
        $queryExtJokers = [];
        $queryExtValues = [];
        foreach ($aData as $key => $value) {
            if ($value == 'SYSDATE' ||
                $value == 'CURRENT_TIMESTAMP') {
                $queryExtJokers[] = $value;
            } else {
                $queryExtJokers[] = '?';
                $queryExtValues[] = $value;
            }
            $queryExtFields[] = $key;
        }
        $queryExt = 'INSERT INTO '.$table.'('.implode(',', $queryExtFields).')values('.implode(',', $queryExtJokers).')';
        //echo "the query " . $queryExt . PHP_EOL;var_export($queryExtFields). PHP_EOL;var_export($queryExtValues). PHP_EOL;
        $db = new Database();
        $return = $db->query($queryExt, $queryExtValues);
        if (!empty($getLastId)) {
            return $db->lastInsertId($getLastId);
        }
        return $return;
    }

    /**
     * [updateTable description]
     * @param  array  $aData  [description]
     * @param  string $table  [description]
     * @param  array  $aWhere [description]
     * @return type         [description]
     */
    public static function updateTable(array $aData, $table, $aWhere = []){
        // Prés-requis :
        if ( ! is_string($table) ) {
            throw new Core_MaarchException_Service('$table not a string');
        }
        if ( ! is_array($aData) ) {
            throw new Core_MaarchException_Service('$aData not an array');
        }
        if ( empty($aData) ) {
            throw new Core_MaarchException_Service('$aData empty');
        }
        // Initialisation :
        $queryExtUpdate = [];
        $queryExtWhere  = [];
        $queryExtValues = [];
        // SET :
        foreach ($aData as $key => $value) {
            $queryExtUpdate[$key] = "{$key}=?";
            $queryExtValues[] = $value;
        }
        // Where :
        foreach ($aWhere as $key => $value) {
            if ( strpos($key, '?')===false )
                $key = "{$key}=?";
            $queryExtWhere[$key] = $key;
            $queryExtValues[] = $value;
        }

        $sWhere = empty($aWhere)?'': ' WHERE '.implode(' AND ', $queryExtWhere);
        $queryExt = 'UPDATE '.$table.' SET '.implode(',', $queryExtUpdate).$sWhere;
        $db = new Database();
        return $db->query($queryExt, $queryExtValues);
    }

    /**
     * Fonction de suppression dans la base de données
     * @param array $args
     * @throws Core_MaarchException_Service if table Argument is empty or is not a string
     * @throws Core_MaarchException_Service if where Argument is empty or is not an array
     * @throws Core_MaarchException_Service if set Argument is empty or is not an array
     * @throws Core_MaarchException_Service if data Argument is not an array
     *
     * @return bool
     */
    public static function update(array $args = []){
        if (empty($args['table']) || !is_string($args['table'])) {
            throw new Core_MaarchException_Service('Table Argument is empty or is not a string.');
        }
        if (empty($args['set']) || !is_array($args['set'])) {
            throw new Core_MaarchException_Service('Set Argument is empty or is not an array.');
        }
        if (empty($args['where']) || !is_array($args['where'])) {
            throw new Core_MaarchException_Service('Where Argument is empty or is not an array.');
        }

        if (empty($args['data'])) {
            $args['data'] = [];
        }
        if (!is_array($args['data'])) {
            throw new Core_MaarchException_Service('Data Argument is not an array.');
        }

        $querySet  = [];
        $setData = [];
        foreach ($args['set'] as $key => $value) {
            $querySet[] = "{$key} = ?";
            $setData[] = $value;
        }
        $args['data'] = array_merge($setData, $args['data']);

        $queryExt = 'UPDATE ' .$args['table']. ' SET '.implode(',', $querySet). ' WHERE ' . implode(' AND ', $args['where']);

        $db = new Database();
        $db->query($queryExt, $args['data']);

        return true;
    }

    /**
     * Fonction de suppression dans la base de données
     * @param array $args
     * @throws Core_MaarchException_Service if Table Argument is empty or is not a string
     * @throws Core_MaarchException_Service if Where Argument is empty or is not an array
     * @throws Core_MaarchException_Service if Data Argument is not an array
     *
     * @return bool
     */
    public static function deleteFrom(array $args = []){
        if (empty($args['table']) || !is_string($args['table'])) {
            throw new Core_MaarchException_Service('Table Argument is empty or is not a string.');
        }
        if (empty($args['where']) || !is_array($args['where'])) {
            throw new Core_MaarchException_Service('Where Argument is empty or is not an array.');
        }

        if (empty($args['data'])) {
            $args['data'] = [];
        }
        if (!is_array($args['data'])) {
            throw new Core_MaarchException_Service('Data Argument is not an array.');
        }

        $queryExt = 'DELETE FROM ' .$args['table']. ' WHERE ' . implode(' AND ', $args['where']);

        $db = new Database();
        $db->query($queryExt, $args['data']);

        return true;
    }
}
