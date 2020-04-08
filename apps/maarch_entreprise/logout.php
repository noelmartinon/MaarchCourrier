<?php
/**
* File : deco.php
*
* use this to terminate your session
*
* @package  Maarch PeopleBox 1.0
* @version 2.1
* @since 10/2005
* @license GPL
* @author  Claire Figueras  <dev@maarch.org>
*/

require_once 'core/class/class_history.php';
require_once 'core/core_tables.php';
$core = new core_tools();
$core->load_lang();
//$name = 'maarch';

if (!empty($_SESSION['user']['UserId'])) {
    $user = \User\models\UserModel::getByLogin(['login' => $_SESSION['user']['UserId'], 'select' => ['id']]);
    \Resource\models\ResModel::update(['set' => ['locker_user_id' => null, 'locker_time' => null], 'where' => ['locker_user_id = ?'], 'data' => [$user['id']]]);
}
$name = $_SESSION['sessionName'];

setcookie($name, "", 1);
setcookie($name, false);
unset($_COOKIE[$name]);

$_SESSION['error'] = _NOW_LOGOUT;
if (isset($_GET['abs_mode'])) {
    $_SESSION['error'] .= ', ' . _ABS_LOG_OUT;
}

if ($core->is_module_loaded('content_management')) {
    require_once 'modules/content_management/class/class_content_manager_tools.php';
    $cM = new content_management_tools();
    $cM->deleteUserCM();
}

if ($_SESSION['history']['userlogout'] == "true"
    && isset($_SESSION['user']['UserId'])
) {
    $hist = new history();
    if ($_SERVER['REMOTE_ADDR'] == '::1') {
        $ip = 'localhost';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $navigateur = addslashes($_SERVER['HTTP_USER_AGENT']);
    //$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
    $host = $_SERVER['REMOTE_ADDR'];
    $hist->add(
        USERS_TABLE, $_SESSION['user']['UserId'], "LOGOUT", 'userlogout',
        _LOGOUT_HISTORY . ' '. $_SESSION['user']['UserId'] . ' IP : ' . $ip,
        $_SESSION['config']['databasetype']
    );
}

$custom   = $_SESSION['custom_override_id'];
$corePath = $_SESSION['config']['corepath'];
$appUrl   = $_SESSION['config']['businessappurl'];
$appId    = $_SESSION['config']['app_id'];

// Destruction du cookie. La session est entièrement détruite et revenir sur le site attribuera un nouvel identifiant
$args = session_get_cookie_params();
$args['lifetime'] = time() - 3600;
if (PHP_MAJOR_VERSION >= 7 && PHP_MINOR_VERSION >= 3) {
    setcookie(session_name(), '', $args);
} else {
    setcookie(session_name(), '', $args['lifetime'], $args['path'], $args['domain'], $args['secure'], $args['httponly']);
}

if (isset($_SESSION['web_sso_url'])) {
    $webSSOurl = $_SESSION['web_sso_url'];
} elseif (isset($_SESSION['web_cas_url'])) {
    $webSSOurl = $_SESSION['web_cas_url'];
}

if (!empty($_SESSION['keycloak']['accessToken'])) {
    $accessToken = $_SESSION['keycloak']['accessToken'];
}

session_unset();
session_destroy(); // Suppression physique de la session
unset($_SESSION['sessionName']);

$_SESSION = [];
$_SESSION['custom_override_id'] = $custom;
$_SESSION['config']['corepath'] = $corePath ;
$_SESSION['config']['app_id'] = $appId ;

if (isset($_GET['logout']) && $_GET['logout']) {
    $logoutExtension = "&logout=true";
} else {
    $logoutExtension = "";
}
\SrcCore\models\AuthenticationModel::deleteCookieAuth();

if (isset($webSSOurl) && $webSSOurl <> '') {
    header("location: " . $webSSOurl);
    exit();
} elseif (!empty($accessToken)) {
    $keycloakConfig = \SrcCore\models\CoreConfigModel::getKeycloakConfiguration();

    $provider = new \Stevenmaguire\OAuth2\Client\Provider\Keycloak($keycloakConfig);

    $url = $provider->getLogoutUrl(['client_id' => $keycloakConfig['clientId'], 'refresh_token' => $accessToken]);

    header("location: " . $url);
} else {
    header(
     "location: " . $appUrl . "index.php?display=true&page=login"
     . $logoutExtension
    );
    exit();
}
