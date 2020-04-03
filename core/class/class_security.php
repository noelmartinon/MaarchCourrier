<?php
/*
*    Copyright 2008-2015 Maarch
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

/**
 * @brief   Contains all the functions to manage the users groups security
 * and connexion to the application
 *
 * @file
 *
 * @author Claire Figueras <dev@maarch.org>
 * @date $date$
 *
 * @version $Revision$
 * @ingroup core
 */

/**
 * @brief   contains all the functions to manage the users groups security
 * through session variables
 *
 *<ul>
 *  <li>Management of application connexion</li>
 *  <li>Management of user rigths</li>
 *</ul>
 * @ingroup core
 */

//Requires to launch history functions
require_once 'core/class/class_db_pdo.php';
require_once 'core/class/class_history.php';
require_once 'core/class/SecurityControler.php';
require_once 'core/class/class_core_tools.php';
require_once 'core/class/users_controler.php';
if (isset($_SESSION['config']['app_id'])) {
    require_once 'apps/'.$_SESSION['config']['app_id']
        .'/class/class_business_app_tools.php';
}
require_once 'core/class/usergroups_controler.php';
require_once 'core/class/ServiceControler.php';

$core = new core_tools();
$core->load_lang();

class security extends Database
{
    /**
     * Gets the indice of the collection in the  $_SESSION['collections'] array.
     *
     * @param  $coll_id string  Collection identifier
     *
     * @return int Indice of the collection in the $_SESSION['collections'] or -1 if not found
     */
    public function get_ind_collection($coll_id)
    {
        for ($i = 0; $i < count($_SESSION['collections']); ++$i) {
            if (trim($_SESSION['collections'][$i]['id']) == trim($coll_id)) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Logs a user.
     *
     * @param  $s_login  string User login
     * @param  $pass string User password
     */
    public function login($s_login, $pass, $method = false, $standardConnect = 'false')
    {
        $array = array();
        $error = '';
        $uc = new users_controler();

        $s_login = str_replace('\'', '', $s_login);
        $s_login = str_replace('=', '', $s_login);
        $s_login = str_replace('"', '', $s_login);
        $s_login = str_replace('*', '', $s_login);
        $s_login = str_replace(';', '', $s_login);
        $s_login = str_replace('--', '', $s_login);
        $s_login = str_replace(',', '', $s_login);
        $s_login = str_replace('$', '', $s_login);
        $s_login = str_replace('>', '', $s_login);
        $s_login = str_replace('<', '', $s_login);

        // #TODO : Not usefull anymore, loginmode field is always in users table
        //Compatibility test, if loginmode column doesn't exists, Maarch can't crash
        if ($this->test_column($_SESSION['tablename']['users'], 'loginmode')) {
            // #TODO : do evolution of the loginmethod in sql query
            if ($method == 'activex') {
                $comp = " and STATUS <> 'DEL' and loginmode = 'activex'";
            } elseif ($method == 'ldap' || $method == 'shibboleth') {
                $comp = " and STATUS <> 'DEL'";
                $params = [];
            } else {
                $comp = " and STATUS <> 'DEL' "
                      .'and loginmode in (:loginmode1)';
                $params = ['loginmode1' => ['standard', 'sso', 'cas', 'keycloak']];
                if ($method == 'restMode') {
                    array_push($params['loginmode1'], 'restMode');
                }
            }
        } else {
            $comp = " and STATUS <> 'DEL'";
            $params = [];
        }

        $check = \SrcCore\models\AuthenticationModel::authentication(['login' => $s_login, 'password' => $pass]);
        if ($check || (in_array($method, ['ldap', 'shibboleth', 'cas', 'sso']) && $standardConnect == 'false')) {
            $user = $uc->getWithComp($s_login, $comp, $params);
        }

        if (isset($user)) {
            if ($user->__get('status') != 'SPD') {
                \User\models\UserModel::update([
                    'set'   => ['reset_token' => null],
                    'where' => ['user_id = ?'],
                    'data'  => [$s_login]
                ]);
                $ugc = new usergroups_controler();
                $sec_controler = new SecurityControler();
                $serv_controler = new ServiceControler();
                if (isset($_SESSION['modules_loaded']['visa'])) {
                    require_once 'modules'.DIRECTORY_SEPARATOR.'visa'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_user_signatures.php';
                    $us = new UserSignatures();
                    $db = new Database();
                    $query = 'select path_template from '
                        ._DOCSERVERS_TABLE_NAME
                        ." where docserver_id = 'TEMPLATES'";
                    $stmt = $db->query($query);
                    $resDs = $stmt->fetchObject();
                    $pathToDs = $resDs->path_template;

                    $tab_sign = $us->getForUser($s_login);
                    $_SESSION['user']['pathToSignature'] = array();
                    foreach ($tab_sign as $sign) {
                        $path = $pathToDs.str_replace(
                            '#',
                            DIRECTORY_SEPARATOR,
                            $sign['signature_path']
                        )
                        .$sign['signature_file_name'];
                        array_push($_SESSION['user']['pathToSignature'], $path);
                    }
                }
                $array = array(
                    'UserId' => $user->__get('user_id'),
                    'FirstName' => $user->__get('firstname'),
                    'LastName' => $user->__get('lastname'),
                    'Initials' => $user->__get('initials'),
                    'Phone' => $user->__get('phone'),
                    'Mail' => $user->__get('mail'),
                    'department' => $user->__get('department'),
                    'pathToSignature' => $_SESSION['user']['pathToSignature'],
                    'Status' => $user->__get('status'),
                    'cookie_date' => $user->__get('cookie_date')
                );

                $tmp = $sec_controler->load_security(
                    $array['UserId']
                );
                $array['collections'] = $tmp['collections'];
                $array['security'] = $tmp['security'];
                $serv_controler->loadEnabledServices();
                $business_app_tools = new business_app_tools();
                $core_tools = new core_tools();
                $business_app_tools->load_app_var_session($array);
                $core_tools->load_var_session($_SESSION['modules'], $array);

                /************Temporary fix*************/
                // #TODO : revoir les functions load_var_session dans class_modules_tools pour ne plus charger en session les infos
                if (isset($_SESSION['user']['baskets'])) {
                    $array['baskets'] = $_SESSION['user']['baskets'];
                }
                if (isset($_SESSION['user']['entities'])) {
                    $array['entities'] = $_SESSION['user']['entities'];
                }
                if (isset($_SESSION['user']['primaryentity'])) {
                    $array['primaryentity'] = $_SESSION['user']['primaryentity'];
                }

                if (isset($_SESSION['user']['redirect_groupbasket'])) {
                    $array['redirect_groupbasket'] = $_SESSION['user']['redirect_groupbasket'];
                }

                if (isset($_SESSION['user']['redirect_groupbasket_by_group'])) {
                    $array['redirect_groupbasket_by_group'] = $_SESSION['user']['redirect_groupbasket_by_group'];
                }
                /*************************************/
                $array['services'] = $serv_controler->loadUserServices(
                    $array['UserId']
                );

                if ($_SESSION['history']['userlogin'] == 'true') {
                    //add new instance in history table for the user's connexion
                    $hist = new history();
                    if ($_SERVER['REMOTE_ADDR'] == '::1') {
                        $ip = 'localhost';
                    } else {
                        $ip = $_SERVER['REMOTE_ADDR'];
                    }
                    $_SESSION['user']['UserId'] = $s_login;
                    $_SESSION['user']['department'] = $array['department'];
                    $hist->add(
                        $_SESSION['tablename']['users'],
                        $s_login,
                        'LOGIN',
                        'userlogin',
                        _LOGIN_HISTORY.' '.$s_login.' IP : '.$ip,
                        $_SESSION['config']['databasetype']
                    );
                }

                if (isset($_SESSION['requestUri'])
                    && trim($_SESSION['requestUri']) != ''
                    && !preg_match('/page=login/', $_SESSION['requestUri'])) {
                    return array(
                        'user' => $array,
                        'error' => $error,
                        'url' => 'index.php?'.$_SESSION['requestUri'],
                    );
                } else {
                    return array(
                        'user' => $array,
                        'error' => $error,
                        'url' => 'index.php',
                    );
                }
            } else {
                $error = _SUSPENDED_ACCOUNT.'. '._MORE_INFOS
                    .' <a href="mailto:'.$_SESSION['config']['adminmail']
                    .'">'.$_SESSION['config']['adminname'].'</a>';

                return array(
                    'user' => $array,
                    'error' => $error,
                    'url' => 'index.php',
                );
            }
        } else {
            if ($standardConnect == 'false') {
                $error = \SrcCore\controllers\AuthenticationController::handleFailedAuthentication(['userId' => $s_login]);
            } else {
                $error = _BAD_LOGIN_OR_PSW;
            }

            return [
                'user'  => $array,
                'error' => $error,
                'url'   => 'index.php?display=true&page=login'
            ];
        }
    }

    /**
     * Reopens a session with the user's cookie.
     *
     * @param  $s_UserId  string User identifier
     * @param  $s_key string Cookie key
     */
    public function reopen($s_UserId, $s_key)
    {
        header('location: '.$_SESSION['config']['businessappurl'].'index.php?display=true&page=login');
        exit();
    }

    /******************* COLLECTION MANAGEMENT FUNCTIONS *******************/

    /**
     * Returns all collections where we can insert new documents (with tables).
     *
     * @return array Collections where inserts are allowed
     */
    public function retrieve_insert_collections()
    {
        $arr = array();
        for ($i = 0; $i < count($_SESSION['collections']); ++$i) {
            if (isset($_SESSION['collections'][$i]['table']) && !empty($_SESSION['collections'][$i]['table'])) {
                array_push($arr, $_SESSION['collections'][$i]);
            }
        }

        return $arr;
    }

    /**
     * @param  $textToHash
     *
     * @return string hashedText
     */
    public function getPasswordHash($textToHash)
    {
        return password_hash($textToHash, PASSWORD_DEFAULT);
    }

    /**
     * Returns the collection identifier from a table.
     *
     * @param  $table  string Tablename
     *
     * @return string Collection identifier or empty string if not found
     */
    public function retrieve_coll_id_from_table($table)
    {
        for ($i = 0; $i < count($_SESSION['collections']); ++$i) {
            if (
                $_SESSION['collections'][$i]['table'] == $table
                || $_SESSION['collections'][$i]['version_table'] == $table
            ) {
                return $_SESSION['collections'][$i]['id'];
            }
        }

        return '';
    }

    /**
     * Returns the collection version table from a collId.
     *
     * @param  $collId string collection ID
     *
     * @return string version table or empty string if not found
     */
    public function retrieve_version_table_from_coll_id($collId)
    {
        if ($collId == 'letterbox_coll') {
            return '';
        }

        for ($i = 0; $i < count($_SESSION['collections']); ++$i) {
            if ($_SESSION['collections'][$i]['id'] == $collId) {
                return $_SESSION['collections'][$i]['version_table'];
            }
        }

        return '';
    }

    /**
     * Returns the view of a collection from the collection identifier.
     *
     * @param string $coll_id Collection identifier
     *
     * @return string View name or empty string if not found
     */
    public function retrieve_view_from_coll_id($coll_id)
    {
        for ($i = 0; $i < count($_SESSION['collections']); ++$i) {
            if ($_SESSION['collections'][$i]['id'] == $coll_id) {
                return $_SESSION['collections'][$i]['view'];
            }
        }

        return '';
    }

    /**
     * Returns the view of a collection from the table of the collection.
     *
     * @param string $table Tablename
     *
     * @return string View name or empty string if not found
     */
    public function retrieve_view_from_table($table)
    {
        for ($i = 0; $i < count($_SESSION['collections']); ++$i) {
            if ($_SESSION['collections'][$i]['table'] == $table) {
                return $_SESSION['collections'][$i]['view'];
            }
        }

        return '';
    }

    /**
     * Returns the table of the collection from the collection identifier.
     *
     * @param string $coll_id Collection identifier
     *
     * @return string Table name or empty string if not found
     */
    public function retrieve_table_from_coll($coll_id)
    {
        for ($i = 0; $i < count($_SESSION['collections']); ++$i) {
            if ($_SESSION['collections'][$i]['id'] == $coll_id) {
                return $_SESSION['collections'][$i]['table'];
            }
        }

        return '';
    }

    /**
     * Returns where clause of the collection for the current user from the collection identifier.
     *
     * @param  $coll_id string Collection identifier
     *
     * @return string Collection where clause or empty string if not found or the where clause is empty
     */
    public function get_where_clause_from_coll_id($coll_id)
    {
        if (isset($_SESSION['user']['security'][$coll_id]['DOC']['where'])) {
            return $_SESSION['user']['security'][$coll_id]['DOC']['where'];
        }

        return '';
    }

    /**
     * Returns where clause of the collection for the current user from the collection identifier and basket where clause.
     *
     * @param  $coll_id string Collection identifier
     *
     * @return string Collection where clause
     */
    public function get_where_clause_from_coll_id_and_basket($coll_id)
    {
        $collectionWhereClause = $this->get_where_clause_from_coll_id($coll_id);

        if (empty($collectionWhereClause)) {
            $collectionWhereClause = '1=0';
        }

        $userBaskets = count($_SESSION['user']['baskets']);

        for ($ind_bask = 0; $ind_bask < $userBaskets; ++$ind_bask) {
            if ($_SESSION['user']['baskets'][$ind_bask]['coll_id'] == $coll_id
                && isset($_SESSION['user']['baskets'][$ind_bask]['clause'])
                && trim($_SESSION['user']['baskets'][$ind_bask]['clause']) != '') {
                $basketWhereClause .= ' or ('.$_SESSION['user']['baskets'][$ind_bask]['clause'].')';
            }
        }

        if (empty($basketWhereClause)) {
            $basketWhereClause = '1=0';
        } else {
            $basketWhereClause = preg_replace('/^ or/', '', $basketWhereClause);
        }

        $whereRequest = '('.$collectionWhereClause.' or '.$basketWhereClause.')';

        return $whereRequest;
    }

    /**
     * Checks the right on the document of a collection for the current user.
     *
     * @param  $coll_id string Collection identifier
     * @param  $s_id string Document Identifier (res_id)
     *
     * @return bool True if the current user has the right, False otherwise
     */
    public function test_right_doc($coll_id, $s_id)
    {
        $user = \User\models\UserModel::getByLogin(['login' => $_SESSION['user']['UserId'], 'select' => ['id']]);

        return \Resource\controllers\ResController::hasRightByResId(['resId' => [$s_id], 'userId' => $user['id']]);
    }
}
