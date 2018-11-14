<?php
/*
 *   Copyright 2008-2016 Maarch
 *
 *   This file is part of Maarch Framework.
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
 *   along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @defgroup basket Basket Module
 */

/**
 * @brief   Module Basket :  Module Tools Class
 *
 * <ul>
 * <li>Set the session variables needed to run the basket module</li>
 * <li>Loads the baskets for the current user</li>
 * <li>Manage the current basket with its actions (if any)</li>
 *</ul>
 *
 * @file
 * @author Claire Figueras <dev@maarch.org>
 * @date $date$
 * @version $Revision$
 * @ingroup basket
 */


require_once 'core/class/SecurityControler.php';
require_once 'core/class/class_security.php';
require_once 'core/core_tables.php';
require_once 'modules/basket/basket_tables.php';
require_once 'modules/entities/entities_tables.php';


abstract class basket_Abstract extends Database
{
    /**
     * Loads basket  tables into sessions vars from the basket/xml/config.xml
     * Loads basket log setting into sessions vars from the basket/xml/config.xml
     */
    public function build_modules_tables()
    {
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'basket' . DIRECTORY_SEPARATOR . 'xml'
            . DIRECTORY_SEPARATOR . 'config.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
            . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
            . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
            . 'basket' . DIRECTORY_SEPARATOR . 'xml' .DIRECTORY_SEPARATOR
            . 'config.xml';
        } else {
            $path = 'modules' . DIRECTORY_SEPARATOR . 'basket'
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
            . 'config.xml';
        }
        $xmlconfig = simplexml_load_file($path);

        $config = $xmlconfig->CONFIG;
        $_SESSION['config']['basket_reserving_time'] = (string) $config->reserving_time;

        // Loads the tables of the module basket  into session
        // ($_SESSION['tablename'] array)
        $tablename = $xmlconfig->TABLENAME;
        $_SESSION['tablename']['bask_baskets'] = (string) $tablename->bask_baskets;
        $_SESSION['tablename']['bask_groupbasket'] = (string) $tablename->bask_groupbasket;
        $_SESSION['tablename']['bask_actions_groupbaskets'] = (string) $tablename->bask_actions_groupbaskets;

        // Loads the log setting of the module basket  into session
        // ($_SESSION['history'] array)
        $history = $xmlconfig->HISTORY;
        $_SESSION['history']['basketup'] = (string) $history->basketup;
        $_SESSION['history']['basketadd'] = (string) $history->basketadd;
        $_SESSION['history']['basketdel'] = (string) $history->basketdel;
        $_SESSION['history']['basketval'] = (string) $history->basketval;
        $_SESSION['history']['basketban'] = (string) $history->basketban;
        $_SESSION['history']['userabs'] = (string) $history->userabs;
    }

    /**
     * Load into session vars all the basket specific vars : calls private
     * methods
     */
    public function load_module_var_session($userData)
    {
        $_SESSION['user']['baskets'] = [];

        $this->_loadBasketsPages();
        $user = \User\models\UserModel::getByUserId(['userId' => $userData['UserId'], 'select' => ['id']]);

        if (isset($userData['primarygroup']) && isset($userData['UserId'])) {
            $db = new Database();
            $stmt = $db->query("SELECT ubp.basket_id, ubp.group_serial_id FROM users_baskets_preferences ubp, baskets WHERE user_serial_id = ? AND ubp.display = TRUE AND ubp.basket_id = baskets.basket_id order by ubp.group_serial_id, baskets.basket_order, baskets.basket_name ", [$user['id']]);
            while ($res = $stmt->fetchObject()) {
                $group = \Group\models\GroupModel::getById(['id' => $res->group_serial_id, 'select' => ['group_id']]);
                $tmp = $this->get_baskets_data($res->basket_id, $userData['UserId'], $group['group_id'], true);
                $_SESSION['user']['baskets'][] = $tmp;
            }
        }

        if (isset($userData['primarygroup']) && isset($userData['UserId'])) {
            $absBasketsArr = $this->load_basket_abs($userData['UserId']);
            $_SESSION['user']['baskets'] = array_merge(
               $_SESSION['user']['baskets'],
                $absBasketsArr
            );
        }
    }

    /**
     * Return the url of the basket result page  given an basket identifier.
     *
     * @param   $basketIdPage  string  Basket results page identifier
     * @param   $mode_page   string "frame" or "no_frame"
     * @return string url of the basket results page or empty string in error
     * case
     */
    public function retrieve_path_page($basketIdPage, $mode)
    {
        // Gets the indice of the $basketIdPage in the
        // $_SESSION['basket_page'] to access all the informations on this page
        $path = '';
        $ind = -1;
        for ($i = 0; $i < count($_SESSION['basket_page']); $i ++) {
            if (trim($_SESSION['basket_page'][$i]['ID']) == trim(
                $basketIdPage
            )
            ) {
                $ind = $i;
                break;
            }
        }
        // If the page identifier is not found return an empty string
        if ($ind == -1) {
            return '';
        } else {// building the url
            // The page is in the apps
            if (strtoupper($_SESSION['basket_page'][$ind]['ORIGIN']) == 'APPS'
            ) {
                if (strtoupper($mode) == 'NO_FRAME') {
                    $path = $_SESSION['config']['businessappurl']
                    . 'index.php?page='
                    . $_SESSION['basket_page'][$ind]['NAME'];
                } elseif (strtoupper($mode) == 'FRAME') {
                    $path = $_SESSION['config']['businessappurl']
                    . $_SESSION['basket_page'][$ind]['NAME'] . '.php';
                } elseif (strtoupper($mode) == 'INCLUDE') {
                    $path = 'apps/' . $_SESSION['config']['app_id'] . '/'
                    . $_SESSION['basket_page'][$ind]['NAME'] . '.php';
                } else {
                    return '';
                }
            } elseif (strtoupper(
                $_SESSION['basket_page'][$ind]['ORIGIN']
            ) == "MODULE"
            ) { // The page is in a module
                $core = new core_tools();
                // Error : The module name is empty or the module is not loaded
                if (empty($_SESSION['basket_page'][$ind]['MODULE'])
                    || ! $core->is_module_loaded(
                        $_SESSION['basket_page'][$ind]['MODULE']
                    )
                ) {
                    return '';
                } else {
                    if (strtoupper($mode) == 'NO_FRAME') {
                        $path = $_SESSION['config']['businessappurl']
                        . 'index.php?page='
                        . $_SESSION['basket_page'][$ind]['NAME']
                        . '&module='
                        . $_SESSION['basket_page'][$ind]['MODULE'];
                    } elseif (strtoupper($mode) == 'FRAME') {
                        $path = $_SESSION['config']['businessappurl']
                        . 'index.php?display=true&module='
                        . $_SESSION['basket_page'][$ind]['MODULE']
                        . '&page='
                        . $_SESSION['basket_page'][$ind]['NAME'];
                    } elseif (strtoupper($mode) == 'INCLUDE') {
                        $path = 'modules' . DIRECTORY_SEPARATOR
                        . $_SESSION['basket_page'][$ind]['MODULE']
                        . DIRECTORY_SEPARATOR
                        . $_SESSION['basket_page'][$ind]['NAME'] . '.php';
                    } else {
                        return '';
                    }
                }
            } else { // Error
                return '';
            }
        }
        return $path;
    }

    /**
     * Loads in session ($_SESSION['basket_page'] array) the informations on the
     *  baskets results page from the basket/xml/basketpage.xml
     *
     */
    protected function _loadBasketsPages()
    {
        $_SESSION['basket_page'] = array();
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'basket' . DIRECTORY_SEPARATOR . 'xml'
            . DIRECTORY_SEPARATOR . 'basketpage.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
            . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
            . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
            . 'basket' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
            . 'basketpage.xml';
        } else {
            $path = 'modules' . DIRECTORY_SEPARATOR . 'basket'
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
            . 'basketpage.xml';
        }
        $xmlfile = simplexml_load_file($path);
        include_once 'modules' . DIRECTORY_SEPARATOR . 'basket'
        . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR
        . $_SESSION['config']['lang'] . '.php';
        $i = 0;
        foreach ($xmlfile->BASKETPAGE as $basketPage) {
            $desc = (string) $basketPage->LABEL;
            if (!empty($desc) && defined($desc) && constant($desc) <> null) {
                $desc = constant($desc);
            }
            $collections = array();
            $collectionsTag = $basketPage->COLLECTIONS;
            foreach ($collectionsTag->COLL_ID as $collection) {
                array_push($collections, (string) $collection);
            }
            $_SESSION['basket_page'][$i] = array(
                'ID'     => (string) $basketPage->ID,
                'LABEL'  => $desc,
                'NAME'   => (string) $basketPage->NAME,
                'ORIGIN' => (string) $basketPage->ORIGIN,
                'MODULE' => (string) $basketPage->MODULE,
                'COLLECTIONS' => $collections,
            );
            $i++;
        }
    }

    /**
     * Loads the baskets datas into session variables
     *
     */
    public function load_basket($primaryGroup, $userId)
    {
        $arr = array();
        $db = new Database();
        $stmt = $db->query(
            "SELECT gb.basket_id FROM " . GROUPBASKET_TABLE . " gb, "
            . BASKET_TABLE . " b WHERE gb.group_id = ? and gb.basket_id = b.basket_id order by b.basket_order, b.basket_name ",
            array($primaryGroup)
        );
        // $db->show();
        while ($res = $stmt->fetchObject()) {
            $tmp = $this->get_baskets_data(
                $res->basket_id,
                $userId,
                $primaryGroup
            );
            //$this->show_array($tmp);
            array_push($arr, $tmp);
        }
        return $arr;
    }

    public function load_basket_abs($userId)
    {
        $db = new Database();
        $arr = array();
        $stmt = $db->query(
            "select system_id, basket_id, user_abs from user_abs where new_user = ?",
            array($userId)
        );
        //$db->show();
        while ($res = $stmt->fetchObject()) {
            $stmt2 = $db->query(
            "select status from users"
            . " where user_id = ?",
                array($res->user_abs)
            );

            $res2 = $stmt2->fetchObject();

            if ($res2->status <> 'DEL') {
                array_push(
                    $arr,
                    $this->get_abs_baskets_data(
                        $res->basket_id,
                        $userId,
                        $res->system_id
                    )
                );
            }
        }
        return $arr;
    }

    /**
     * Get the actions for a group in a basket.
     *
     * @param   $basketId   string  Basket identifier
     * @param   $groupId string  Users group identifier
     * @return array actions
     */
    protected function _getActionsFromGroupbaket($basketId, $groupId, $userId = '')
    {
        $actions = array();
        $db = new Database();

        $stmt = $db->query(
            "select agb.id_action, agb.where_clause, agb.used_in_basketlist, "
            . "agb.used_in_action_page, a.label_action, a.id_status, "
            . "a.action_page from " . ACTIONS_TABLE . " a, "
            . ACTIONS_GROUPBASKET_TABLE . " agb where a.id = agb.id_action and "
            . "agb.group_id = ? and agb.basket_id = ? and a.enabled = 'Y' and "
            . "agb.default_action_list ='N'",
            array($groupId,$basketId)
        );
        require_once('core/class/ActionControler.php');
        $actionControler = new actionControler();
        $secCtrl = new SecurityControler();

        while ($res = $stmt->fetchObject()) {
            if ($res->where_clause <> '') {
                $whereClause = $secCtrl->process_security_where_clause(
                    $res->where_clause,
                    $userId
                );
                $whereClause = substr($whereClause, -strlen($whereClause)+6);
            } else {
                $whereClause = $res->where_clause;
            }
            $categories = $actionControler->getAllCategoriesLinkedToAction($res->id_action);
            array_push(
                $actions,
                array(
                    'ID' => $res->id_action,
                    'LABEL' => $res->label_action,
                    'WHERE' => $whereClause,
                    'MASS_USE' => $res->used_in_basketlist,
                    'PAGE_USE' => $res->used_in_action_page,
                    'ID_STATUS' => $res->id_status,
                    'ACTION_PAGE' => $res->action_page,
                    'CATEGORIES' => $categories
                )
            );
        }
        return $actions;
    }

    /**
     * Get the default action in a basket for a group
     *
     * @param  $basketId   string  Basket identifier
     * @param   $groupId  string  Users group identifier
     * @return string action identifier or empty string in error case
     */
    protected function _getDefaultAction($basketId, $groupId)
    {
        $db = new Database();
        $stmt = $db->query(
            "select agb.id_action from " . ACTIONS_TABLE . " a, "
            . ACTIONS_GROUPBASKET_TABLE . " agb where a.id = agb.id_action "
            . "and agb.group_id = ? and agb.basket_id = ? and a.enabled = 'Y' "
            . "and agb.default_action_list ='Y'",
            array($groupId,$basketId)
        );

        if ($stmt->rowCount() < 1) {
            return '';
        } else {
            $res = $stmt->fetchObject();
            return $res->id_action;
        }
    }


    /**
     * Make a given basket the current basket
     * (using $_SESSION['current_basket'] array)
     *
     * @param   $basketId   string Basket identifier
     * @param   $groupId   string Group identifier
     */
    public function load_current_basket($basketId, $groupId = null)
    {
        $_SESSION['current_basket'] = [];
        $_SESSION['current_basket']['id'] = trim($basketId);
        $ind = -1;
        for ($i = 0; $i < count($_SESSION['user']['baskets']); $i ++) {
            if ($_SESSION['user']['baskets'][$i]['id'] == trim($basketId)
                && (empty($groupId) || $_SESSION['user']['baskets'][$i]['group_id'] == trim($groupId))) {
                $ind = $i;
                break;
            }
        }
        if ($ind > -1) {
            $_SESSION['current_basket']['table'] = $_SESSION['user']['baskets'][$ind]['table'];
            $_SESSION['current_basket']['view'] = $_SESSION['user']['baskets'][$ind]['view'];
            $_SESSION['current_basket']['coll_id'] = $_SESSION['user']['baskets'][$ind]['coll_id'];
            $_SESSION['current_basket']['page_frame'] = $_SESSION['user']['baskets'][$ind]['page_frame'];
            $_SESSION['current_basket']['page_no_frame'] = $_SESSION['user']['baskets'][$ind]['page_no_frame'];
            $_SESSION['current_basket']['page_include'] = $_SESSION['user']['baskets'][$ind]['page_include'];
            $_SESSION['current_basket']['default_action'] = $_SESSION['user']['baskets'][$ind]['default_action'];
            $_SESSION['current_basket']['label'] = $_SESSION['user']['baskets'][$ind]['name'];
            $_SESSION['current_basket']['clause'] = $_SESSION['user']['baskets'][$ind]['clause'];
            $_SESSION['current_basket']['actions'] = $_SESSION['user']['baskets'][$ind]['actions'];
            $_SESSION['current_basket']['basket_res_order'] = $_SESSION['user']['baskets'][$ind]['basket_res_order'];
            // $_SESSION['current_basket']['redirect_services'] = $_SESSION['user']['baskets'][$ind]['redirect_services'];
            // $_SESSION['current_basket']['redirect_users'] = $_SESSION['user']['baskets'][$ind]['redirect_users'];
            $_SESSION['current_basket']['basket_owner'] = $_SESSION['user']['baskets'][$ind]['basket_owner'];
            $_SESSION['current_basket']['abs_basket'] = $_SESSION['user']['baskets'][$ind]['abs_basket'];
            $_SESSION['current_basket']['lock_list'] = $_SESSION['user']['baskets'][$ind]['lock_list'];
            $_SESSION['current_basket']['lock_sublist'] = $_SESSION['user']['baskets'][$ind]['lock_suvlist'];
            $_SESSION['current_basket']['group_id'] = $_SESSION['user']['baskets'][$ind]['group_id'];
            $_SESSION['current_basket']['group_desc'] = $_SESSION['user']['baskets'][$ind]['group_desc'];
        }
    }

    public function translates_actions_to_json($actions = array())
    {
        $jsonActions = '{';

        if (count($actions) > 0) {
            for ($i = 0; $i < count($actions); $i ++) {
                $jsonActions .= "'"  . $actions[$i]['ID'] . "' : { 'where' : '"
                . addslashes($actions[$i]['WHERE']) . "',";
                $jsonActions .= "'id_status' : '" . $actions[$i]['ID_STATUS']
                . "', 'confirm' : '" ;
                if (isset($actions[$i]['CONFIRM'])) {
                    $jsonActions .= $actions[$i]['CONFIRM'];
                } else {
                    $jsonActions .= 'false';
                }
                $jsonActions .= "', ";
                $jsonActions .= "'id_action_page' : '"
                . $actions[$i]['ACTION_PAGE'] . "'}, ";
            }
            $jsonActions = preg_replace('/, $/', '}', $jsonActions);
        }

        if ($jsonActions == '{') {
            $jsonActions = '{}';
        }
        return $jsonActions;
    }
    /**
     * Builds the basket results list (using class_list_show.php method)
     *
     * @param   $paramsList  array  Parameters array used to display the result
     *                              list
     * @param   $actions actions  Array to be displayed in the list
     * @param   $lineTxt  string String to be displayed at the bottom of the
     *                       list to describe the default action
     */
    public function basket_list_doc($paramsList, $actions, $lineTxt)
    {
        ////////////////////////////////////////////////////////////////////////
        //$this->show_array($paramsList);
        ////////////////////////////////////////////////////////////////////////
        $actionForm = '';
        $boolCheckForm = false;
        $method = '';
        $actionsList = array();
        // Browse the actions array to build the jason string that will be used
        // to display the actions in the list
        if (count($actions) > 0) {
            for ($i = 0; $i < count($actions); $i ++) {
                if ($actions[$i]['MASS_USE'] == 'Y') {
                    array_push(
                        $actionsList,
                        array(
                            'VALUE' => $actions[$i]['ID'],
                            'LABEL' => addslashes($actions[$i]['LABEL']),
                        )
                    );
                }
            }
        }

        $jsonActions = $this->translates_actions_to_json($actions);

        if (count($actionsList) > 0) {
            $actionForm = $_SESSION['config']['businessappurl']
            . 'index.php?display=true&page=manage_action'
            . '&module=core';
            $boolCheckForm = true;
            $method = 'get';
        }

        $doAction = false;
        if (! empty($_SESSION['current_basket']['default_action'])) {
            $doAction = true;
        }

        $list = new list_show();
        if (! isset($paramsList['link_in_line'])) {
            $paramsList['link_in_line'] = false;
        }
        if (! isset($paramsList['template'])) {
            $paramsList['template'] = false;
        }
        if (! isset($paramsList['template_list'])) {
            $paramsList['template_list'] = array();
        }
        if (! isset($paramsList['actual_template'])) {
            $paramsList['actual_template'] = '';
        }
        if (! isset($paramsList['bool_export'])) {
            $paramsList['bool_export'] = false;
        }
        if (! isset($paramsList['comp_link'])) {
            $paramsList['comp_link'] = '';
        }
        $str = '';
        // Displays the list using list_doc method from class_list_shows
        $str .= $list->list_doc(
            $paramsList['values'],
            count($paramsList['values']),
            $paramsList['title'],
            $paramsList['what'],
            $paramsList['page_name'],
            $paramsList['key'],
            $paramsList['detail_destination'],
            $paramsList['view_doc'],
            false,
            $method,
            $actionForm,
            '',
            $paramsList['bool_details'],
            $paramsList['bool_order'],
            $paramsList['bool_frame'],
            $paramsList['bool_export'],
            false,
            false,
            true,
            $boolCheckForm,
            '',
            $paramsList['module'],
            false,
            '',
            '',
            $paramsList['css'],
            $paramsList['comp_link'],
            $paramsList['link_in_line'],
            true,
            $actionsList,
            $paramsList['hidden_fields'],
            $jsonActions,
            $doAction,
            $_SESSION['current_basket']['default_action'],
            $paramsList['open_details_popup'],
            $paramsList['do_actions_arr'],
            $paramsList['template'],
            $paramsList['template_list'],
            $paramsList['actual_template'],
            true
        );

        // Displays the text line if needed
        if (count($paramsList['values']) > 0 && ($paramsList['link_in_line']
        || $doAction)
        ) {
            $str .= "<em>".$lineTxt."</em>";
        }
        if (! isset($paramsList['mode_string'])
        || $paramsList['mode_string'] == false
        ) {
            echo $str;
        } else {
            return $str;
        }
    }

    /**
     * Returns the actions for the current basket for a given mode.
     * The mode can be "MASS_USE" or "PAGE_USE".
     *
     * @param   $resId  string  Resource identifier
     *   (used in PAGE_USE mode to test the action where_clause)
     * @param   $collId  string Collection identifier
     *   (used in PAGE_USE mode to test the action where_clause)
     * @param   $mode  string  "PAGE_USE" or "MASS_USE"
     * @param   $testWhere  boolean
     * @return array  Actions to be displayed
     */
    public function get_actions_from_current_basket($resId, $collId, $mode, $testWhere = true)
    {
        $arr = [];

        if ($_SESSION['category_id'] == '') {
            $_SESSION['category_id'] = $_SESSION['coll_categories'][$collId]['default_category'];
        }

        if (empty($resId) || empty($collId) || (strtoupper($mode) != 'MASS_USE' && strtoupper($mode) != 'PAGE_USE')) {
            return $arr;
        } else {
            $sec = new security();
            $db = new Database();
            $table = $sec->retrieve_view_from_coll_id($collId);
            if (empty($table)) {
                $table = $sec->retrieve_table_from_coll_id($collId);
            }
            if (empty($table)) {
                // If the view and the table of the collection is empty,
                return $arr;
            }
            // If mode "PAGE_USE", add the action 'end_action' to validate the current action
            if ($mode == 'PAGE_USE') {
                $db = new Database();
                $stmt = $db->query("SELECT label_action FROM actions WHERE id= ?", [$_SESSION['current_basket']['default_action']]);
                $label_action = $stmt->fetchObject();
                $arr[] = ['VALUE' => 'end_action', 'LABEL' => $label_action->label_action.' (par défaut)'];
            }

            // Browsing the current basket actions to build the actions array
            for ($i = 0; $i < count($_SESSION['current_basket']['actions']); $i++) {
                $noFilterOnCat = true;
                if (!empty($_SESSION['current_basket']['actions'][$i]['CATEGORIES'])
                    && is_array($_SESSION['current_basket']['actions'][$i]['CATEGORIES'])
                    && count($_SESSION['current_basket']['actions'][$i]['CATEGORIES']) > 0) {
                    $noFilterOnCat = false;
                }
                $categoryIdForActions = '';
                $cl = 0;
                if (!empty($_SESSION['current_basket']['actions'][$i]['CATEGORIES']) && is_array($_SESSION['current_basket']['actions'][$i]['CATEGORIES'])) {
                    $cl = count($_SESSION['current_basket']['actions'][$i]['CATEGORIES']);
                }

                for ($cptCat=0; $cptCat < $cl; $cptCat++) {
                    if ($_SESSION['current_basket']['actions'][$i]['CATEGORIES'][$cptCat] == $_SESSION['category_id']) {
                        $categoryIdForActions = $_SESSION['category_id'];
                    }
                }
                if ($noFilterOnCat || $categoryIdForActions != '') {
                    // If in mode "PAGE_USE", testing the action where clause on the res_id before adding the action
                    if (
                        strtoupper($mode) == 'PAGE_USE'
                        && $_SESSION['current_basket']['actions'][$i]['PAGE_USE'] == 'Y'
                        && $testWhere && strtoupper($resId) != 'NONE'
                    ) {
                        $where = ' where res_id = ' . $resId;
                        if (!empty($_SESSION['current_basket']['actions'][$i]['WHERE'])) {
                            $where = $where . ' and ' . $_SESSION['current_basket']['actions'][$i]['WHERE'];
                        }
                        $stmt = $db->query('select res_id from ' . $table . ' ' . $where);
                        if ($stmt->rowCount() > 0) {
                            $arr[] = ['VALUE' => $_SESSION['current_basket']['actions'][$i]['ID'], 'LABEL' => $_SESSION['current_basket']['actions'][$i]['LABEL']];
                        }
                    } elseif (strtoupper($mode) == 'PAGE_USE' && $_SESSION['current_basket']['actions'][$i]['PAGE_USE'] == 'Y') {
                        $arr[] = ['VALUE' => $_SESSION['current_basket']['actions'][$i]['ID'], 'LABEL' => $_SESSION['current_basket']['actions'][$i]['LABEL']];
                    } elseif (strtoupper($mode) == 'MASS_USE' && $_SESSION['current_basket']['actions'][$i]['MASS_USE'] == 'Y') {
                        // If "MASS_USE" adding the actions in the array
                        $arr[] = ['VALUE' => $_SESSION['current_basket']['actions'][$i]['ID'], 'LABEL' => $_SESSION['current_basket']['actions'][$i]['LABEL']];
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * Returns in an array all the data of a basket for a user
     *(checks if the basket is a redirected one and then if already a virtual one)
     *
     * @param  $basketId string Basket identifier
     * @param  $userId string User identifier
     */
    public function get_baskets_data($basketId, $userId, $groupId, $isSecondary = false)
    {
        $tab = array();
        $db = new Database();

        $sec = new security();
        $secCtrl = new SecurityControler();
        $stmt = $db->query(
            "select basket_id, coll_id, basket_name, basket_desc, "
            . "basket_clause, is_visible, color, basket_res_order from "
            . BASKET_TABLE . " where basket_id = ? and enabled = 'Y'",
            array($basketId)
        );
        $res = $stmt->fetchObject();
        $tab['id'] = $res->basket_id;
        $tab['coll_id'] = $res->coll_id;
        $tab['table'] =  $sec->retrieve_table_from_coll($tab['coll_id']);
        $tab['view'] = $sec->retrieve_view_from_coll_id($tab['coll_id']);
        $tab['desc'] = $this->show_string($res->basket_desc);
        $tab['name'] = $this->show_string($res->basket_name);
        $tab['color'] = $this->show_string($res->color);
        $tab['basket_res_order'] = $this->show_string($res->basket_res_order);
        $tab['clause'] = $res->basket_clause;
        $tab['is_visible'] = $res->is_visible;
        $isVirtual = 'N';
        $basketOwner = '';
        $absBasket = false;

        if (!$isSecondary) {
            $stmt = $db->query(
                "select group_id from "
                . $_SESSION['tablename']['usergroup_content']
                . " where primary_group = 'Y' and user_id = ?",
                array($userId)
            );
            $res = $stmt->fetchObject();
            $groupId = $res->group_id;
        }
        $stmt = $db->query(
            "select result_page from "
            . GROUPBASKET_TABLE . " where group_id = ? and basket_id = ?",
            array($groupId,$basketId)
        );
        $res = $stmt->fetchObject();

        $basketIdPage = $res->result_page;
        $tab['id_page'] = $basketIdPage;
        // Retrieves the basket url (frame and no_frame modes)
        $basketPathPageNoFrame = $this->retrieve_path_page(
            $basketIdPage,
            'no_frame'
        );
        $basketPathPageFrame = $this->retrieve_path_page(
            $basketIdPage,
            'frame'
        );
        $basketPathPageInclude = $this->retrieve_path_page(
            $basketIdPage,
            'include'
        );
        $tab['page_no_frame'] = $basketPathPageNoFrame;
        $tab['page_frame'] = $basketPathPageFrame;
        $tab['page_include'] = $basketPathPageInclude;
        // Gets actions of the basket
        // #TODO : make one method to get all actions : merge _getDefaultAction and _getActionsFromGroupbaket
        $tab['default_action'] = $this->_getDefaultAction(
            $basketId,
            $groupId
        );
        $tab['actions'] = $this->_getActionsFromGroupbaket(
            $basketId,
            $groupId,
            $userId
        );

        $tab['abs_basket'] = $absBasket;
        $tab['is_virtual'] = $isVirtual;
        $tab['basket_owner'] = $basketOwner;
        $tab['clause'] = $secCtrl->process_security_where_clause(
            $tab['clause'],
            $userId
        );
        $tab['clause'] = str_replace('where', '', $tab['clause']);
        
        $tab['lock_list'] = '';
        $tab['lock_sublist'] = '';
        

        $db = new Database();
        $stmt = $db->query(
            "select id, group_desc from usergroups where group_id = ?",
            array($groupId)
        );
        $res = $stmt->fetchObject();
        $groupDesc = $res->group_desc;

        $tab['group_id'] = $groupId;
        $tab['group_serial_id'] = $res->id;
        $tab['group_desc'] = $groupDesc;
        $tab['is_secondary'] = $isSecondary;
        
        return $tab;
    }

    /**
     * Returns in an array all the data of a basket for a user
     * (checks if the basket is a redirected one and then if already a virtual one)
     *
     * @param  $basketId string Basket identifier
     * @param  $userId string User identifier
     */
    public function get_abs_baskets_data($basketId, $userId, $systemId)
    {
        $tab = array();
        $db = new Database();
        $sec = new security();
        $secCtrl = new SecurityControler();
        $stmt = $db->query(
            "select basket_id, coll_id, basket_name, basket_desc, basket_clause, is_visible"
            . " from " . BASKET_TABLE . " where basket_id = ? and enabled = 'Y'",
            array($basketId)
        );

        $res = $stmt->fetchObject();
        $tab['id'] = $res->basket_id;
        $tab['coll_id'] = $res->coll_id;
        $tab['table'] = $sec->retrieve_table_from_coll($tab['coll_id']);
        $tab['view'] = $sec->retrieve_view_from_coll_id($tab['coll_id']);

        $tab['desc'] = $res->basket_desc;
        $tab['name'] = $res->basket_name;
        $tab['clause'] = $res->basket_clause;
        $tab['is_visible'] = $res->is_visible;
        $stmt = $db->query(
            "select user_abs, is_virtual, basket_owner from user_abs where basket_id = ? and new_user = ? and system_id = ?",
            array($basketId,$userId,$systemId)
        );

        $absBasket = true;
        $res = $stmt->fetchObject();
        $isVirtual = $res->is_virtual;
        $basketOwner = $res->basket_owner;
        $userAbs = $res->user_abs;

        if (empty($basketOwner)) {
            $basketOwner = $userAbs;
        }
        if ($isVirtual == 'N') {
            $tmpUser = $userAbs;
            $stmt = $db->query(
                "select firstname, lastname from " . USERS_TABLE
                . " where user_id = ? ",
                array($userAbs)
            );
            $res = $stmt->fetchObject();
            $nameUserAbs = $res->firstname . ' ' . $res->lastname;
            $tab['name'] .= " (" . $nameUserAbs . ")";
            $tab['desc'] .= " (" . $nameUserAbs . ")";
            $tab['id'] .= "_" . $userAbs;
        } else {
            $tmpUser = $basketOwner;  /// TO DO : test if basket_owner empty
            $stmt = $db->query(
                "select firstname, lastname from " . USERS_TABLE
                ." where user_id = ?",
                array($basketOwner)
            );
            $res = $stmt->fetchObject();
            $nameBasketOwner = $res->firstname . ' ' . $res->lastname;
            $tab['name'] .= " (" . $nameBasketOwner . ")";
            $tab['desc'] .= " (" . $nameBasketOwner . ")";
            $tab['id'] .= "_" . $basketOwner;
        }

        /// TO DO : Test if tmp_user is empty
        if ((isset($_SESSION['user']['UserId'])
            && $tmpUser <> $_SESSION['user']['UserId'])
            || (!isset($_SESSION['user']['UserId']))
        ) {
            $stmt = $db->query(
                "select group_id from usergroup_content where primary_group = 'Y' and user_id = ?",
                array($tmpUser)
            );

            $groups = [];
            while ($res = $stmt->fetchObject()) {
                $groups[] = $res->group_id;
            }
            $stmt = $db->query("select result_page, group_id from groupbasket where group_id in (?) and basket_id = ?", array($groups, $basketId));
            $res = $stmt->fetchObject();
            $primaryGroup = $res->group_id;
        } else {
            $primaryGroup = $_SESSION['user']['primarygroup'];
            $stmt = $db->query("select result_page from groupbasket where group_id = ? and basket_id = ?", array($primaryGroup,$basketId));
            $res = $stmt->fetchObject();
        }

        $basketIdPage = $res->result_page;
        $tab['id_page'] = $basketIdPage;
        // Retrieves the basket url (frame and no_frame modes)
        $basketPathPageNoFrame = $this->retrieve_path_page(
            $basketIdPage,
            'no_frame'
        );
        $basketPathPageFrame = $this->retrieve_path_page(
            $basketIdPage,
            'frame'
        );
        $basketPathPageInclude = $this->retrieve_path_page(
            $basketIdPage,
            'include'
        );
        $tab['page_no_frame'] = $basketPathPageNoFrame;
        $tab['page_frame'] = $basketPathPageFrame;
        $tab['page_include'] = $basketPathPageInclude;
        // Gets actions of the basket
        $tab['default_action'] = $this->_getDefaultAction(
            $basketId,
            $primaryGroup
        );
        $tab['actions'] = $this->_getActionsFromGroupbaket(
            $basketId,
            $primaryGroup,
            $userId
        );

        $tab['is_virtual'] = $isVirtual;
        $tab['basket_owner'] = $basketOwner;
        $tab['abs_basket'] = $absBasket;

        $aGroups = \SrcCore\models\DatabaseModel::select([
            'select'    => ['usergroup_content.group_id'],
            'table'     => ['usergroup_content, groupbasket'],
            'where'     => ['groupbasket.group_id = usergroup_content.group_id', 'usergroup_content.user_id = ?', 'groupbasket.basket_id =  ?'],
            'data'      => [$basketOwner, $basketId]
        ]);

        $tab['group_id'] = $aGroups[0]['group_id'];

        $tab['clause'] = $secCtrl->process_security_where_clause(
            $tab['clause'],
            $basketOwner
        );
        $tab['clause'] = str_replace('where', '', $tab['clause']);
        
        $tab['lock_list'] = '';
        $tab['lock_sublist'] = '';
        
        return $tab;
    }

    public function is_redirect_to_action_basket($basketId, $groupId)
    {
        $db = new Database();
        $stmt = $db->query(
                "select result_page from " . GROUPBASKET_TABLE
                . " where group_id = ? and basket_id = ?",
            array($groupId,$basketId)
        );
        $res = $stmt->fetchObject();

        $basketIdPage = $res->result_page;
        if ($basketIdPage == 'redirect_to_action') {
            return true;
        } else {
            return false;
        }
    }
}
