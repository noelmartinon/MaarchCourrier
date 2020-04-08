<?php


/**
 * File : log.php.
 *
 * User identification
 *
 * @version 2.1
 *
 * @since 10/2005
 *
 * @license GPL
 * @author  Claire Figueras  <dev@maarch.org>
 * @author  Laurent Giovannoni  <dev@maarch.org>
 */
if (empty($_COOKIE)) {
    $_SESSION['error'] = 'Le cache utilisateur à été réinitialisé veuillez re-saisir vos identifiants';
    header(
        'location: '.$_SESSION['config']['businessappurl']
        .'index.php?display=true&page=login'
    );
    exit;
}

if (file_exists('../../core/init.php')) {
    include_once '../../core/init.php';
}
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_functions.php';
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_db.php';
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_core_tools.php';

$core = new core_tools();
$core->load_lang();
$func = new functions();

$_SESSION['error'] = '';
$method = false;
if (isset($_SESSION['web_cas_url'])) {
    include_once 'apps/maarch_entreprise/tools/phpCAS/CAS.php';

    phpCAS::client(constant($_SESSION['cas_version']), $_SESSION['cas_serveur'], (int) $_SESSION['cas_port'], $_SESSION['cas_context'], true);

    if (!empty($_SESSION['cas_certificate'])) {
        phpCAS::setCasServerCACert($_SESSION['cas_certificate']);
    } else {
        phpCAS::setNoCasServerValidation();
    }

    phpCAS::forceAuthentication();
    $Id = phpCAS::getUser();

    if (!empty($_SESSION['cas_id_separator'])) {
        $tmpId = explode($_SESSION['cas_id_separator'], $Id);
        $login = $tmpId[0];
    } else {
        $login = $Id;
    }

    $_REQUEST['pass'] = 'maarch';
    $method = 'cas';
} elseif (!empty($_SESSION['keycloak']['userId'])) {
    $login = $_SESSION['keycloak']['userId'];
    $_REQUEST['pass'] = 'maarch';
} elseif (!empty($_SESSION['sso']['userId'])) {
    $login = $_SESSION['sso']['userId'];
    $_REQUEST['pass'] = 'maarch';
    $method = 'sso';
} elseif (isset($_REQUEST['login'])) {
    $login = $func->wash($_REQUEST['login'], 'no', _THE_ID, 'yes');
} else {
    $login = '';
}
if (isset($_REQUEST['pass'])) {
    $password = $_REQUEST['pass'];
} else {
    $password = '';
}
require_once 'core/class/class_security.php';
require_once 'core/class/class_request.php';
require_once 'apps/'.$_SESSION['config']['app_id']
    .'/class/class_business_app_tools.php';
$sec = new security();
$businessAppTools = new business_app_tools();

if (empty($_SESSION['config']['databasename'])) {
    $tmpPath = explode(
        DIRECTORY_SEPARATOR,
        str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $_SERVER['SCRIPT_FILENAME']
        )
    );
    $serverPath = implode(
        DIRECTORY_SEPARATOR,
        array_slice(
            $tmpPath,
            0,
            array_search('apps', $tmpPath)
        )
    ).DIRECTORY_SEPARATOR;

    $core->build_core_config('core/xml/config.xml');

    $businessAppTools->build_business_app_config();
    $core->load_modules_config($_SESSION['modules']);
    $core->load_lang();
    $core->load_app_services();
    $core->load_modules_services($_SESSION['modules']);
}

if (empty($login) || empty($password)) {
    $_SESSION['error'] = _BAD_LOGIN_OR_PSW . '...';
}
if (!empty($_SESSION['error'])) {
    header('location: '.$_SESSION['config']['businessappurl'] .'index.php?display=true&page=login');
    exit();
} else {
    $loginMethod = \SrcCore\models\CoreConfigModel::getLoggingMethod();
    if ($loginMethod['id'] == 'ldap' && $login != 'superadmin') {
        //Extraction de /root/config dans le fichier de conf
        if (file_exists($_SESSION['config']['corepath']
            .'/custom/'.$_SESSION['custom_override_id']
            .'/modules/ldap/xml/config.xml')
        ) {
            $pathtoConfig = $_SESSION['config']['corepath']
            .'/custom/'.$_SESSION['custom_override_id']
            .'/modules/ldap/xml/config.xml';
        } else {
            $pathtoConfig = $_SESSION['config']['corepath']
                .'modules/ldap/xml/config.xml';
        }
        $ldapConf = new DomDocument();
        try {
            if (!@$ldapConf->load($pathtoConfig)) {
                throw new Exception(
                    'Impossible de charger le document : '
                    .$pathtoConfig
                );
            }
        } catch (Exception $e) {
            exit($e->getMessage());
        }

        if (!file_exists($pathtoConfig)) {
            exit();
        }


        $xpLdapConf = new domxpath($ldapConf);
        $ldapConfig = simplexml_load_file($pathtoConfig);

        foreach ($ldapConfig->config->ldap as $ldap) {
            $_SESSION['error'] = '';
            foreach ($ldap as $node => $value) {
                ${$node} = (string)$value;
            }

            //On inclus la class LDAP qui correspond à l'annuaire
            if (strtolower($type_ldap) == 'openldap') {
                $classLdap = 'class_openLDAP.php';
            } else {
                $classLdap = 'class_adLDAP.php';
            }

            //customized or not
            if (!@include_once $_SESSION['config']['corepath'] . '/custom/' . $_SESSION['custom_override_id'] . '/modules/ldap/class/' . $classLdap) {
                if (!@include_once $_SESSION['config']['corepath'] . 'modules/ldap/class/' . $classLdap) {
                    exit('Impossible de charger class_' . $_SESSION['config']['corepath'] . '/modules/ldap/class/' . $classLdap . "\n");
                }
            }

            if (!empty($prefix_login)) {
                $login_admin = $prefix_login . '\\' . $login_admin;
            }

            if (!empty($suffix_login)) {
                $login_admin = $login_admin . $suffix_login;
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
                    $res = $sec->login($login, $password, 'ldap', $standardConnect);
                    $login = $res['user']['UserId'];
                    $_SESSION['user'] = $res['user'];
                    if (empty($res['error'])) {
                        \SrcCore\models\AuthenticationModel::setCookieAuth(['userId' => $login]);
                        \SrcCore\models\AuthenticationModel::resetFailedAuthentication(['userId' => $login]);
                        $user = \User\models\UserModel::getByLogin(['login' => $login, 'select' => ['id']]);
                        header(
                            'location: ' . $_SESSION['config']['businessappurl']
                            . $res['url']
                        );
                        exit();
                    } else {
                        $_SESSION['error'] = $res['error'];
                    }

                    header('location: ' . $_SESSION['config']['businessappurl'] . $res['url']);
                    continue;
                } else {
                    echo functions::xssafe($conFailure->getMessage());
                    continue;
                }
            }

            if ($prefix_login != '') {
                $loginToAd = $prefix_login . '\\' . $login;
            } else {
                $loginToAd = $login;
            }

            if ($suffix_login != '') {
                $loginToAd = $loginToAd . $suffix_login;
            }

            if ($ad->authenticate($loginToAd, $password)) {
                require_once 'core/class/class_db_pdo.php';

                // Instantiate database.
                $database = new Database();
                $stmt = $database->query(
                    'SELECT * FROM users WHERE user_id ILIKE ?',
                    array($login)
                ); //permet de rechercher les utilisateurs dans le LDAP sans prendre en compte la casse
                $result = $stmt->fetch();
                $login = $result['user_id'];

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
                \SrcCore\models\AuthenticationModel::resetFailedAuthentication(['userId' => $login]);

                if ($result) {
                    $_SESSION['error'] = '';
                    if (!empty($standardConnect) && $standardConnect == 'true') {
                        \User\models\UserModel::updatePassword(['id' => $result['id'], 'password' => $password]);
                    }
                    $res = $sec->login($login, $password, 'ldap', $standardConnect);
                    $_SESSION['user'] = $res['user'];
                    if ($res['error'] == '') {
                        \SrcCore\models\AuthenticationModel::setCookieAuth(['userId' => $login]);
                    } else {
                        $_SESSION['error'] = $res['error'];
                    }
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
                    continue;
                }
            } else {
                $error = _BAD_LOGIN_OR_PSW;
                $_SESSION['error'] = $error;
                header(
                    'location: ' . $_SESSION['config']['businessappurl']
                    . 'index.php?display=true&page=login'
                );
                continue;
            }
        }
        $error = \SrcCore\controllers\AuthenticationController::handleFailedAuthentication(['userId' => $login]);
        $_SESSION['error'] = $error;
        header(
            'location: ' . $_SESSION['config']['businessappurl']
            . 'index.php?display=true&page=login'
        );
    } else {
        $_SESSION['error'] = '';
        $res = $sec->login($login, $password, $method);
        $_SESSION['user'] = $res['user'];
        $login = $res['user']['UserId'];
        if (empty($res['error'])) {
            \SrcCore\models\AuthenticationModel::setCookieAuth(['userId' => $login]);
            \SrcCore\models\AuthenticationModel::resetFailedAuthentication(['userId' => $login]);
        } else {
            $_SESSION['error'] = $res['error'];
        }

        if ($_SESSION['user']['UserId'] == 'superadmin') {
            $res['url'] .= '?administration=true';
        }
        header('location: '.$_SESSION['config']['businessappurl'].$res['url']);
        exit();
    }
}
