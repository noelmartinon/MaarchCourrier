<?php

/**
* File : log.php
*
* User identification
*
* @package  Maarch PeopleBox 1.0
* @version 2.1
* @since 10/2005
* @license GPL
* @author  Claire Figueras  <dev@maarch.org>
* @author  Laurent Giovannoni  <dev@maarch.org>
*/
if (file_exists('../../core/init.php')) {
    include_once '../../core/init.php';
}
require_once('core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_functions.php');
require_once('core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_db_pdo.php');
require_once('core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_core_tools.php');

$core = new core_tools();
$core->load_lang();

$_SESSION['error'] = '';
if (isset($_REQUEST['login'])) {
    $login = functions::wash($_REQUEST['login'], 'no', _THE_ID, 'yes');
} else {
    $login = '';
}
if (isset($_REQUEST['pass'])) {
    $password = functions::wash($_REQUEST['pass'], 'no', _PASSWORD_FOR_USER, 'yes');
} else {
    $password = '';
}
if (isset($_REQUEST['ra_code'])) {
    $ra_code = functions::wash($_REQUEST['ra_code'], 'no', _RA_CODE, 'yes');
} else {
    $ra_code = '';
}
require_once 'core/class/class_security.php';
require_once 'apps/' . $_SESSION['config']['app_id']
    . '/class/class_business_app_tools.php';
$sec = new security();
$businessAppTools = new business_app_tools();

if (count($_SESSION['config']) <= 0) {
    $tmpPath = explode(
        DIRECTORY_SEPARATOR, str_replace(
            '/', DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']
        )
    );
    $serverPath = implode(
        DIRECTORY_SEPARATOR, array_slice(
            $tmpPath, 0, array_search('apps', $tmpPath)
        )
    ).DIRECTORY_SEPARATOR;

    $core->build_core_config('core/xml/config.xml');

    $businessAppTools->build_business_app_config();
    $core->load_modules_config($_SESSION['modules']);
    $core->load_menu($_SESSION['modules']);
}

    if ($_SESSION['config']['ldap'] == 'true' && $login <> 'superadmin') {
        //Extraction de /root/config dans le fichier de conf
        if (file_exists($_SESSION['config']['corepath'] 
            . '/custom/' . $_SESSION['custom_override_id']
            . '/modules/ldap/xml/config.xml')
        ) {
            $pathtoConfig = $_SESSION['config']['corepath'] 
            . '/custom/' . $_SESSION['custom_override_id']
            . '/modules/ldap/xml/config.xml';
        } else {
             $pathtoConfig = $_SESSION['config']['corepath'] 
                . 'modules/ldap/xml/config.xml';
        }
               $ldapConf = new DomDocument();
        try {
            if (!@$ldapConf->load($pathtoConfig)) 
            {
                throw new Exception(
                    'Impossible de charger le document : '
                    . $pathtoConfig
                );
            }
        } catch(Exception $e) {
            exit($e->getMessage());
        }

        $xpLdapConf = new domxpath($ldapConf);

        foreach ($xpLdapConf->query('/root/config/*') as $cf) {
            ${$cf->nodeName} = $cf->nodeValue;
        }

        //On inclus la class LDAP qui correspond à l'annuaire
        if (!include $_SESSION['config']['corepath'] . 'modules/ldap/class/class_adLDAP.php') 
        {
            exit('Impossible de charger class_' . $_SESSION['config']['corepath'] 
                . 'modules/ldap/class/class_adLDAP.php'."\n");
        }
        
        if ($prefix_login <> '') {
            $login_admin = $prefix_login . "\\" . $login_admin;
        }
        
        //Try to create a new ldap instance
        try {
            $ad = new LDAP($domain, $login_admin, $pass, $ssl);
        } catch(Exception $conFailure) {
            echo $conFailure->getMessage();
            exit;
        }
        
        if ($prefix_login <> '') {
            $loginToAd = $prefix_login . "\\" . $login;
        } else {
            $loginToAd = $login;
        }
        
        if ($ad -> authenticate($loginToAd, $password)) {
            $db = new Database();
            
            $login = end(explode('\\', $login));

            $query = "SELECT * FROM users WHERE user_id ILIKE ?";
            $stmt = $db->query($query,array($login));
            
            if ($stmt->fetchObject()) {
                $_SESSION['error'] = '';
                $pass = $sec->getPasswordHash($password);
                $res = $sec->login($login, $pass, 'ldap');
                $_SESSION['user'] = $res['user'];
                if (empty($_SESSION['error'])) {
                    $_SESSION['error'] = $res['error'];
                }
                $core->load_menu($_SESSION['modules']);
                header('location: smartphone/index.php?page=welcome');
                exit();
            } else {
                $_SESSION['error'] = _NO_LOGIN_OR_PSW_BY_LDAP . '...';
                header(
                    'location: ' . $_SESSION['config']['businessappurl']
                    . 'index.php?display=true&page=login'
                );
                exit;
            }
        } else {
            $_SESSION['error'] = _BAD_LOGIN_OR_PSW . ' (ad authenticate) ...';
            header(
                'location: ' . $_SESSION['config']['businessappurl']
                . 'index.php?display=true&page=login'
            );
            exit;
        }
    }
    else {
        if (empty($login) || empty($password)) {
            $_SESSION['error'] = _BAD_LOGIN_OR_PSW . '...';
            header(
                'location: ' . $_SESSION['config']['businessappurl']
                . 'index.php?display=true&page=login'
            );
            exit;
        } else {
            $_SESSION['error'] = '';
            $pass = $sec->getPasswordHash($password);
            if ($ra_code != '') $res = $sec->login($login, $pass, false, $ra_code);
            else $res = $sec->login($login, $pass);

            if (!$sec->test_allowed_ip() && $ra_code == ''){
                $_SESSION['error'] = _TRYING_TO_CONNECT_FROM_NOT_ALLOWED_IP;
                $sec->generateRaCode($login, $password);
                exit();
            }

            //$core->show_array($res);exit();
            $_SESSION['user'] = $res['user'];
            if ($res['error'] == '') {
               // $businessAppTools->load_app_var_session($_SESSION['user']);
                //$core->load_var_session($_SESSION['modules'], $_SESSION['user']);
                $core->load_menu($_SESSION['modules']);
               // exit;
            }
            else {
                $_SESSION['error'] = $res['error'];
                header(
                'location: ' . $_SESSION['config']['businessappurl']
                . 'index.php?display=true&page=login'
                );
                exit();
            }
            
            /*$pathToIPFilter = '';
            if(file_exists($_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'ip_filter.xml')){
                $pathToIPFilter = $_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'ip_filter.xml';
            } elseif (file_exists('apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'ip_filter.xml')) {
                $pathToIPFilter = 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'ip_filter.xml';
            }
            else {
                $ipArray = array();
                $ipArray['enabled'] = 'false';
                $ipArray['duration'] = '0';
            }
            $ipArray = array();
            $ipArray = functions::object2array(simplexml_load_file($pathToIPFilter));
            //print_r($ipArray);
            if ($ipArray['enabled'] == 'true') {
                $isAllowed = false;
                if($ipArray['IP'] <> '') {
                    $isAllowed = preg_match($ipArray['IP'], $_SERVER['REMOTE_ADDR']);
                }
                
                if (empty($_SESSION['error'])) {
                    $_SESSION['error'] = $res['error'];
                }
                if (!$isAllowed && $res['error'] == '') {
                    if ($ipArray['duration'] == 0) {
                        $_SESSION['error'] = _IP_NOT_ALLOWED_NO_RA_CODE;
                    }
                    else {
                        $_SESSION['error'] = _IP_NOT_ALLOWED;
                    }
                    $res['url'] = 'index.php?display=true&page=login';
                }
            }*/
                header('location: smartphone/index.php?page=welcome');
            exit();
        }
    }
