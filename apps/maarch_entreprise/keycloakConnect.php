<?php

require 'vendor/autoload.php';

$keycloakConfig = \SrcCore\models\CoreConfigModel::getKeycloakConfiguration();

if (empty($keycloakConfig)) {
    echo _MISSING_KEYCLOAK_CONFIG;
    exit;
}

if (empty($keycloakConfig['authServerUrl']) || empty($keycloakConfig['realm']) || empty($keycloakConfig['clientId']) || empty($keycloakConfig['clientSecret']) || empty($keycloakConfig['redirectUri'])) {
    echo _MISSING_KEYCLOAK_CONFIG;
    exit;
}

$provider = new \Stevenmaguire\OAuth2\Client\Provider\Keycloak($keycloakConfig);

if (!isset($_GET['code'])) {
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);

    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    header('Location: '.$keycloakConfig['redirectUri']);
    exit;

} else {

    // Try to get an access token (using the authorization coe grant)
    try {
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);
    } catch (Exception $e) {
//        exit('Failed to get access token: '.$e->getMessage());
        header('Location: '.$keycloakConfig['redirectUri']);
        exit;
    }

    try {
        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        $userMaarch = \User\models\UserModel::getByLogin(['login' => $user->getId()]);

        if (empty($userMaarch)) {
            echo _USER_NOT_IN_APP;
        } else {
            $_SESSION['keycloak']['userId'] = $user->getId();
            $_SESSION['keycloak']['accessToken'] = $token->getToken();
            unset($_REQUEST['code']);
            unset($_REQUEST['state']);
            unset($_REQUEST['session_state']);
            header("location: log.php");
        }
    } catch (Exception $e) {
//        exit('Failed to get resource owner: '.$e->getMessage());
        header('Location: '.$keycloakConfig['redirectUri']);
        exit;
    }
}
