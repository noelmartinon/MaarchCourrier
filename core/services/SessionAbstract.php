<?php

/**
*   @copyright 2017 Maarch
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

require_once 'core/services/Abstract.php';
require_once 'core/class/class_functions.php';
require_once 'core/class/class_security.php';

/**
 * Service de gestion des données en session
 */
class Core_SessionAbstract_Service extends Core_Abstract_Service {
    
    /**
     * Récupération de la liste des méthodes disponibles via api
     * 
     * @return string[] La liste des méthodes
     */
    public static function getApiMethod() {
        $aApiMethod = parent::getApiMethod();
        $aApiMethod['getUserId']          = 'getUserId';
        $aApiMethod['getUserEntitiesLst'] = 'getUserEntitiesLst';
        $aApiMethod['InitializeAntiXss']  = 'InitializeAntiXss';
        return $aApiMethod;
    }
    
    /**
     * Renvoie les informations de l'utilisateur courant dans la session
     * @api rest.php?module=core&service=Core_Session_Service&method=getUser
     * @throw \Exception $e
     * @param array $args
     * @return array $aUser
     **/
    public static function getUser(array $args = []) {
        return empty($_SESSION['user'])?null:$_SESSION['user'];
    }

    /**
     * Renvoie la valeur de la session anti_xss. on la définie si elle n'existe pas
     * @api rest.php?module=core&service=Core_Session_Service&method=InitializeAntiXss
     * @throw \Exception $e
     * @param array $args
     * @return array $aUser
     **/
    public static function InitializeAntiXss(array $args = []){
        if(empty($_SESSION['anti_xss'])){
            $_SESSION['anti_xss'] = uniqid();
        }
        return $_SESSION['anti_xss'];
    }
    
    /**
     * Renvoie le userid courant dans la session
     * @throw \Exception $e
     * @param array $args
     *  - none
     * @return string $sUserId
     **/
    public static function getUserId(array $args = []) {
        $aUser = self::getUser();
        if ( !empty($aUser['UserId']) ){
            return $aUser['UserId'];
        }

        $userSSOHeader = '';
        if (!empty($_SERVER['HTTP_'.HEADER_USER_UID])) {
            $userSSOHeader = $_SERVER['HTTP_' .HEADER_USER_UID];
        } else if (!empty($_SERVER['HTTP_' .HEADER_USER_NIGEND])) {
            $userSSOHeader = $_SERVER['HTTP_' .HEADER_USER_NIGEND];
        }
        return $userSSOHeader;
    }

    /**
     * Renvoie les entité de l'utilisateur en session
     * @throw \Exception $e
     * @param array $args
     * @return array $aEntities [aEntitie]
     **/
    public static function getUserEntities(array $args = []) {
        return $_SESSION['user']['entities'];
    }

    /**
     * Renvoie la liste des entités de l'utilisateur en session (juste leur name)
     * @throw \Exception $e
     * @param array $args
     * @return array $aEntities [string ENTITY_ID,string ENTITY_ID,...]
     **/
    public static function getUserEntitiesLst(array $args = []) {
        $aUserEntities = self::getUserEntities();
        $aLst = [];
        foreach ($aUserEntities as $aEntitie) {
            $aLst[] = $aEntitie['ENTITY_ID'];
        }
        return $aLst;
    }

    /**
     * Renvoie les entité de l'utilisateur en session
     * @throw \Exception $e
     * @param array $args
     * @return string $sEntities
     **/
    public static function getUserPrimaryentity(array $args = []) {
        return $_SESSION['user']['primaryentity'];
    }

    /**
     * Authentification d'un utilisateur
     * - Vérifie que l'utilisateur existe (pas son code)
     * - Charge l'utilisateur en session (le connecte)
     * @param string $userId identifiant de l'utilisateur
     * @return false|array false en cas d'echec, un tableau avec l'utilisateur sinon
     */
    public function authentication($userId) {
        if ( empty($userId) ) {
            return false;
        }
        if ( ! is_string($userId) ) {
            return false;
        }
        $authenticated = false;
        $func = new functions();

        $connexion = new Database();

        $_SESSION['user']['UserId'] = $userId;
        $userID = str_replace('\'', '', $_SESSION['user']['UserId']);
        $userID = str_replace('=', '', $userID);
        $userID = str_replace('"', '', $userID);
        $userID = str_replace('*', '', $userID);
        $userID = str_replace(';', '', $userID);
        $userID = str_replace('--', '', $userID);
        $userID = str_replace(',', '', $userID);
        $userID = str_replace('$', '', $userID);
        $userID = str_replace('>', '', $userID);
        $userID = str_replace('<', '', $userID);

        $sec = new security();
        $query = "SELECT * FROM users WHERE user_id = ? AND STATUS <> 'DEL'";

        $stmt = $connexion->query(
            $query, 
            [$userID]
        );

        if ($stmt->rowCount() <= 0) {
            return false;
        }
        $array = array();
        $error = '';
        $uc = new users_controler();

        $database = new Database();
        $comp = " and STATUS <>:status";
        $params = array('status' => 'DEL');
        $s_login = $userId;
        $user = $uc->getWithComp($s_login, $comp, $params);
        if (empty($user)) {
            return false;
        }
        if ($user->__get('enabled') != 'Y') {
            return false;
        }
        $ugc = new usergroups_controler();
        $sec_controler = new SecurityControler();
        $serv_controler = new ServiceControler();
        if (isset($_SESSION['modules_loaded']['visa'])) {
            if ($user->__get('signature_path') <> '' 
                && $user->__get('signature_file_name') <> '' 
            ) {
                $_SESSION['user']['signature_path'] = $user->__get('signature_path');
                $_SESSION['user']['signature_file_name'] = $user->__get('signature_file_name');
                $db = new Database();
                $query = "select path_template from " 
                    . _DOCSERVERS_TABLE_NAME 
                    . " where docserver_id = 'TEMPLATES'";
                $stmt = $db->query($query);
                $resDs = $stmt->fetchObject();
                $pathToDs = $resDs->path_template;
                $_SESSION['user']['pathToSignature'] = $pathToDs . str_replace(
                        "#", 
                        DIRECTORY_SEPARATOR, 
                        $_SESSION['user']['signature_path']
                    )
                    . $_SESSION['user']['signature_file_name'];
            }
        }

        $array = array(
            'change_pass'         => $user->__get('change_password'),
            'UserId'              => $user->__get('user_id'),
            'FirstName'           => $user->__get('firstname'),
            'LastName'            => $user->__get('lastname'),
            'Phone'               => $user->__get('phone'),
            'Mail'                => $user->__get('mail'),
            'department'          => $user->__get('department'),
            'thumbprint'          => $user->__get('thumbprint'),
            'signature_path'      => $user->__get('signature_path'),
            'signature_file_name' => $user->__get('signature_file_name'),
            'pathToSignature'     => empty($_SESSION['user']['pathToSignature'])?'':$_SESSION['user']['pathToSignature'],
            'Status'              => $user->__get('status'),
            'cookie_date'         => $user->__get('cookie_date'),
        );

        $array['primarygroup'] = $ugc ->getPrimaryGroup(
            $array['UserId']
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
        Core_CoreConfig_Service::loadVarSession($_SESSION['modules'], $array);
        
        /************Temporary fix*************/ 
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
        /*************************************/
        $array['services'] = $serv_controler->loadUserServices(
            $array['UserId']
        );
        
        if ($_SESSION['history']['userlogin'] == 'true') {
            //add new instance in history table for the user's connexion
            $hist = new history();
            if(!isset($_SERVER['REMOTE_ADDR'])){
                $ip = 'testU';
            } else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}

            $_SESSION['user']['UserId']       = $s_login;
            $_SESSION['user']['department']   = $array['department'];
            $_SESSION['user']['thumbprint']   = $array['thumbprint'];
            $_SESSION['user']['primarygroup'] = $array['primarygroup'];
            $hist->add(
                $_SESSION['tablename']['users'],
                $s_login,
                'LOGIN','userlogin',
                _LOGIN_HISTORY . ' '. $s_login . ' IP : ' . $ip,
                $_SESSION['config']['databasetype']
            );
        }

        return array(
            'user'  => $array/*,
            'error' => $error,
            'url'   => 'index.php?' . $_SESSION['requestUri']*/
        );

        return true;
    }
}