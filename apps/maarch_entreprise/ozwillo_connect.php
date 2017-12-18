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
    \Core\Models\UserModel::create(['user' => ['userId' => $idToken->sub, 'firstname' => $firstname, 'lastname' => $lastname, 'changePassword' => 'N']]);
    \Core\Models\UserModel::addGroup(['userId' => $idToken->sub, 'groupId' => 'AGENT']);
    \Core\Models\UserModel::addEntity(['userId' => $idToken->sub, 'entityId' => 'VILLE', 'primaryEntity' => 'Y']);
}

$_SESSION['ozwillo']['userId'] = $idToken->sub;
$_SESSION['ozwillo']['accessToken'] = $oidc->getAccessToken();
unset($_REQUEST['code']);
unset($_REQUEST['state']);

header("location: log.php");
$trace = new history();
$trace->add('users', $idToken->sub, 'LOGIN', 'userlogin', 'Ozwillo Connection', $_SESSION['config']['databasetype'], 'ADMIN', false);
