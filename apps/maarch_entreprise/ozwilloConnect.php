<?php

require 'vendor/autoload.php';

$ozwilloConfig = \SrcCore\models\CoreConfigModel::getOzwilloConfiguration();

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
$user = \User\models\UserModel::getByLogin(['login' => $idToken->sub]);

if (empty($user)) {
    if (empty($ozwilloConfig['groupId'])) {
        $ozwilloConfig['groupId'] = 'AGENT';
    }
    if (empty($ozwilloConfig['entityId'])) {
        $ozwilloConfig['entityId'] = 'VILLE';
    }
    $firstname = empty($profile->given_name) ? 'utilisateur' : $profile->given_name;
    $lastname = empty($profile->family_name) ? 'utilisateur' : $profile->family_name;
    \User\models\UserModel::create(['user' => ['userId' => $idToken->sub, 'firstname' => $firstname, 'lastname' => $lastname, 'changePassword' => 'N']]);
    $user = \User\models\UserModel::getByLogin(['login' => $idToken->sub]);
    \User\models\UserModel::addGroup(['id' => $user['id'], 'groupId' => $ozwilloConfig['groupId']]);
    \User\models\UserEntityModel::addUserEntity(['id' => $user['id'], 'entityId' => $ozwilloConfig['entityId'], 'primaryEntity' => 'Y']);
}

$_SESSION['ozwillo']['userId'] =  $idToken->sub;
$_SESSION['ozwillo']['accessToken'] = $oidc->getAccessToken();
unset($_REQUEST['code']);
unset($_REQUEST['state']);

header("location: log.php");
$trace = new history();
$trace->add('users', $idToken->sub, 'LOGIN', 'userlogin', 'Ozwillo Connection', $_SESSION['config']['databasetype'], 'ADMIN', false, 'ok', 'DEBUG', $_SESSION['ozwillo']['userId']);
