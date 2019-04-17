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
if(empty($_COOKIE)){
    $_SESSION['error'] = 'Le cache utilisateur à été réinitialisé veuillez re-saisir vos identifiants';
    header(
        'location: ' . $_SESSION['config']['businessappurl']
        . 'index.php?display=true&page=login'
    );
    exit;
}

if (file_exists('../../core/init.php')) {
    include_once '../../core/init.php';
}
require_once('core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_functions.php');
require_once('core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_db.php');
require_once('core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_core_tools.php');

$core = new core_tools();
$core->load_lang();
$func = new functions();

$_SESSION['error'] = '';
if(isset($_SESSION['web_cas_url'])){
    include_once('apps/maarch_entreprise/tools/phpCAS/CAS.php');

    phpCAS::client(constant($_SESSION['cas_version']), $_SESSION['cas_serveur'], (int)$_SESSION['cas_port'], $_SESSION['cas_context'], true);

    if(!empty($_SESSION['cas_certificate'])){
        phpCAS::setCasServerCACert($_SESSION['cas_certificate']);
    } else {
        phpCAS::setNoCasServerValidation();
    }

    phpCAS::forceAuthentication();
    $Id = phpCAS::getUser();

    if(!empty($_SESSION['cas_id_separator'])){
        $tmpId = explode($_SESSION['cas_id_separator'], $Id);
        $login = $tmpId[0];
    } else {
        $login = $Id;
    }

    $_REQUEST['pass'] = 'maarch';

} else if (!empty($_SESSION['sso']['userId'])) {
    $login = $_SESSION['sso']['userId'];
    $_REQUEST['pass'] = 'maarch';
} else if (isset($_REQUEST['login'])) {
    $login = $func->wash($_REQUEST['login'], 'no', _THE_ID, 'yes');
} else {
    $login = '';
}
if (isset($_REQUEST['pass'])) {
    $password = $_REQUEST['pass'];
} else {
    $password = '';
}
if (isset($_REQUEST['ra_code'])) {
    $ra_code = $func->wash($_REQUEST['ra_code'], 'no', _RA_CODE, 'yes');
} else {
    $ra_code = '';
}
require_once 'core/class/class_security.php';
require_once 'core/class/class_request.php';
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

if (! empty($_SESSION['error'])) {
    header(
        'location: ' . $_SESSION['config']['businessappurl']
        . 'index.php?display=true&page=login'
    );
    exit();
} else {
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
        if (strtolower($type_ldap) == 'openldap') {
            $classLdap = 'class_openLDAP.php';
        }else{
            $classLdap = 'class_adLDAP.php';
        }

        //customized or not
        if (!@include $_SESSION['config']['corepath'] . '/custom/' . $_SESSION['custom_override_id'] . '/modules/ldap/class/'.$classLdap) 
        {
            if(!@include $_SESSION['config']['corepath'] . 'modules/ldap/class/'.$classLdap){
                exit('Impossible de charger class_' . $_SESSION['config']['corepath'] . '/modules/ldap/class/'.$classLdap."\n"); 
            }
        }
        
        if ($prefix_login <> '') {
            $login_admin = $prefix_login . "\\" . $login_admin;
        }
        
        //Try to create a new ldap instance
        try {
            if (strtolower($type_ldap) == 'openldap') {
                $ad = new LDAP($domain, $login_admin, $pass, $ssl, $hostname);
            } else {
                $ad = new LDAP($domain, $login_admin, $pass, $ssl);
            }
        } catch (Exception $conFailure) {
            if (!empty($standardConnect) && $standardConnect == 'true') {
                $pass = $sec->getPasswordHash($password);
                $res = $sec->login($login, $pass);
                $_SESSION['user'] = $res['user'];
                if (empty($res['error'])) {
                    \Core\Models\AuthenticationModel::resetFailedAuthentication(['userId' => $login]);
                    \Core\Models\UserModel::updatePassword(['userId' => $login, 'password' => $password]);
                    $core->load_menu($_SESSION['modules']);
                } else {
                    $_SESSION['error'] = $res['error'];
                }

                header('location: '.$_SESSION['config']['businessappurl'].$res['url']);
                exit();
            } else {
                echo functions::xssafe($conFailure->getMessage());
                exit;
            }
        }

        if ($prefix_login <> '') {
            $loginToAd = $prefix_login . "\\" . $login;
        } else {
            $loginToAd = $login;
        }
        
        if ($ad -> authenticate($loginToAd, $password)) {
            //TODO: protect sql injection with PDO
                require_once 'core/class/class_db_pdo.php';

            // Instantiate database.
            $database = new Database();
            $stmt = $database->query(
                "SELECT * FROM users WHERE user_id ILIKE ?", 
                array($login)
            ); //permet de rechercher les utilisateurs dans le LDAP sans prendre en compte la casse
            $result = $stmt->fetch();

            if ($result) {
                $_SESSION['error'] = '';

                if (!empty($_SESSION['config']['enhancedPassword'])) {
                    if (!empty($result['locked_until'])) {
                        $lockedDate = new \DateTime($result['locked_until']);
                        $currentDate = new \DateTime();
                        if ($currentDate < $lockedDate) {
                            $_SESSION['error'] = _ACCOUNT_LOCKED_UNTIL . " {$lockedDate->format('d/m/Y H:i')}";
                            header(
                                'location: ' . $_SESSION['config']['businessappurl']
                                . 'index.php?display=true&page=login'
                            );
                            exit;
                        }
                    }
                    \Core\Models\AuthenticationModel::resetFailedAuthentication(['userId' => $login]);
                }
                $pass = $sec->getPasswordHash($password);
                if (!empty($standardConnect) && $standardConnect == 'true') {
                    \Core\Models\UserModel::updatePassword(['userId' => $login, 'password' => $password]);
                }
                $res = $sec->login($login, $pass, 'ldap');
                if ($res['error'] == '') {
                    $_SESSION['user'] = $res['user'];
                } else {
                    $_SESSION['error'] = $res['error'];
                }
                $core->load_menu($_SESSION['modules']);
                header(
                    'location: ' . $_SESSION['config']['businessappurl']
                    . $res['url']
                );
                exit();
            } else {
                $_SESSION['error'] = _BAD_LOGIN_OR_PSW;
                header(
                    'location: ' . $_SESSION['config']['businessappurl']
                    . 'index.php?display=true&page=login'
                );
                exit;
            }
        } else {
            if (!empty($_SESSION['config']['enhancedPassword'])) {
                $error = \Core\Controllers\AuthenticationController::handleFailedAuthentication(['userId' => $login]);
            } else {
                $error = _BAD_LOGIN_OR_PSW;
            }
            $_SESSION['error'] = $error;
            header(
                'location: ' . $_SESSION['config']['businessappurl']
                . 'index.php?display=true&page=login'
            );
            exit;
        }
    }
    elseif(isset($_REQUEST['ra_code'])) {
        if (empty($login) || empty($password) || empty($ra_code)) {
            $_SESSION['error'] = _IP_NOT_ALLOWED;
            header(
                'location: ' . $_SESSION['config']['businessappurl']
                . 'index.php?display=true&page=login'
            );
            exit;
        }
        else {
            $_SESSION['error'] = '';
            $pass = $sec->getPasswordHash($password);
            $res = $sec->login($login, $pass, false, $ra_code);
            //$core->show_array($res);
            $_SESSION['user'] = $res['user'];
            if ($res['error'] == '') {
               // $businessAppTools->load_app_var_session($_SESSION['user']);
                //$core->load_var_session($_SESSION['modules'], $_SESSION['user']);
                $core->load_menu($_SESSION['modules']);
               // exit;
            }
            if (empty($_SESSION['error'])) {
                $_SESSION['error'] = $res['error'];
            }
            header(
                'location: ' . $_SESSION['config']['businessappurl'] . $res['url']
            );
            exit();
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
            $res = $sec->login($login, $pass);
            //$core->show_array($res);
            $_SESSION['user'] = $res['user'];
            if ($res['error'] == '') {
               // $businessAppTools->load_app_var_session($_SESSION['user']);
                //$core->load_var_session($_SESSION['modules'], $_SESSION['user']);
                if (!empty($_SESSION['config']['enhancedPassword'])) {
                    \Core\Models\AuthenticationModel::resetFailedAuthentication(['userId' => $login]);
                }
                $core->load_menu($_SESSION['modules']);
               // exit;
            }
            else {
                $_SESSION['error'] = $res['error'];
            }
            
            $pathToIPFilter = '';
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
            $ipArray = $func->object2array(simplexml_load_file($pathToIPFilter));
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
            }
            header(
                'location: ' . $_SESSION['config']['businessappurl'] . $res['url']
            );
            exit();
        }
    }
}
