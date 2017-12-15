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
$oidc->addScope('profile');
$oidc->authenticate();

$idToken = $oidc->getIdTokenPayload();
if (empty($idToken->app_user) && empty($idToken->app_admin)) {
    echo '<br>Utilisateur non autorisé';
    exit;
}

$profile = $oidc->requestUserInfo();
$user = \Core\Models\UserModel::getById(['userId' => $idToken->sub]);

if (empty($user)) {
    $firstname = empty($profile->given_name) ? 'utilisateur' : $profile->given_name;
    $lastname = empty($profile->family_name) ? 'utilisateur' : $profile->family_name;
    \Core\Models\UserModel::create(['user' => ['userId' => $idToken->sub, 'firstname' => $firstname, 'lastname' => $lastname]]);
    $user = \Core\Models\UserModel::getById(['userId' => $idToken->sub]);
    \Core\Models\UserModel::addGroup(['id' => $user['id'], 'groupId' => 'AGENT']);
}

$_SESSION['ozwillo']['userId'] = $idToken->sub;
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
