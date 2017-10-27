<?php

require 'vendor/autoload.php';

$ozwilloConfig = \Core\Models\CoreConfigModel::getOzwilloConfiguration();

if (!empty($_SESSION['ozwillo']['code']) && !empty($_SESSION['ozwillo']['state'])) {
    $_REQUEST['code'] = $_SESSION['ozwillo']['code'];
    $_REQUEST['state'] = $_SESSION['ozwillo']['state'];
    $_SESSION['ozwillo'] = null;
}

$oidc = new OpenIDConnectClient($ozwilloConfig['uri'], $ozwilloConfig['clientId'], $ozwilloConfig['clientSecret']);
$oidc->addScope('openid');
$oidc->addScope('email');
$oidc->authenticate();

$userId = $oidc->requestUserInfo('email');
$user = \Core\Models\UserModel::getById(['userId' => $userId]);

if (empty($user)) {
    echo '<br>' . _USER_NOT_EXIST;
    exit;
}

$_SESSION['ozwillo']['userId'] = $userId;
$_SESSION['ozwillo']['accessToken'] = $oidc->getAccessToken();
unset($_REQUEST['code']);
unset($_REQUEST['state']);

$trace = new history();
if ($restMode) {
    $_SESSION['error'] = '';
    $security = new security();
    $pass = $security->getPasswordHash('maarch');
    $res  = $security->login($userId, $pass);

    $_SESSION['user'] = $res['user'];
    if (!empty($res['error'])) {
        $_SESSION['error'] = $res['error'];
    }

    $trace->add('users', $userId, 'LOGIN', 'userlogin', 'Ozwillo Connection', $_SESSION['config']['databasetype'], 'ADMIN', false);
} else {
    header("location: log.php");
    $trace->add('users', $userId, 'LOGIN', 'userlogin', 'Ozwillo Connection', $_SESSION['config']['databasetype'], 'ADMIN', false);
}
