<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Authentication Controller
 *
 * @author dev@maarch.org
 */

namespace SrcCore\controllers;

use Configuration\models\ConfigurationModel;
use Email\controllers\EmailController;
use Firebase\JWT\JWT;
use History\controllers\HistoryController;
use Parameter\models\ParameterModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\AuthenticationModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class AuthenticationController
{
    const MAX_DURATION_TOKEN = 30; //Minutes
    const ROUTES_WITHOUT_AUTHENTICATION = [
        'GET/authenticationInformations', 'PUT/versionsUpdateSQL', 'GET/validUrl', 'GET/authenticate/token', 'GET/images', 'POST/password', 'PUT/password', 'GET/passwordRules',
        'GET/jnlp/{jnlpUniqueId}', 'GET/onlyOffice/mergedFile', 'POST/onlyOfficeCallback', 'POST/authenticate',
        'GET/installer/prerequisites', 'GET/installer/databaseConnection', 'GET/installer/sqlDataFiles', 'GET/installer/docservers', 'GET/installer/custom',
        'POST/installer/custom', 'POST/installer/database', 'POST/installer/docservers', 'POST/installer/customization',
        'PUT/installer/administrator', 'DELETE/installer/lock',
        'GET/wopi/files/{id}', 'GET/wopi/files/{id}/contents', 'POST/wopi/files/{id}/contents','GET/onlyOffice/content','GET/languages/{lang}',
    ];

    public function getInformations(Request $request, Response $response)
    {
        $path = CoreConfigModel::getConfigPath();
        $hashedPath = md5($path);

        $appName = CoreConfigModel::getApplicationName();
        $parameter = ParameterModel::getById(['id' => 'loginpage_message', 'select' => ['param_value_string']]);

        $encryptKey = CoreConfigModel::getEncryptKey();

        $loggingMethod = CoreConfigModel::getLoggingMethod();
        $authUri = null;
        if ($loggingMethod['id'] == 'cas') {
            $casConfiguration = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/cas_config.xml']);
            $hostname = (string)$casConfiguration->WEB_CAS_URL;
            $port = (string)$casConfiguration->WEB_CAS_PORT;
            $uri = (string)$casConfiguration->WEB_CAS_CONTEXT;
            $authUri = "https://{$hostname}:{$port}{$uri}/login?service=" . UrlController::getCoreUrl() . 'dist/index.html#/login';
        }

        return $response->withJson([
            'instanceId'        => $hashedPath,
            'applicationName'   => $appName,
            'loginMessage'      => $parameter['param_value_string'] ?? null,
            'changeKey'         => $encryptKey == 'Security Key Maarch Courrier #2008',
            'authMode'          => $loggingMethod['id'],
            'authUri'           => $authUri
        ]);
    }

    public function getValidUrl(Request $request, Response $response)
    {
        if (!is_file('custom/custom.json')) {
            return $response->withJson(['message' => 'No custom file', 'lang' => 'noConfiguration']);
        }

        $jsonFile = file_get_contents('custom/custom.json');
        $jsonFile = json_decode($jsonFile, true);
        if (count($jsonFile) == 0) {
            return $response->withJson(['message' => 'No custom', 'lang' => 'noConfiguration']);
        } elseif (count($jsonFile) > 1) {
            return $response->withJson(['message' => 'There is more than 1 custom', 'lang' => 'moreOneCustom']);
        }

        $url = null;
        if (!empty($jsonFile[0]['path'])) {
            $coreUrl = UrlController::getCoreUrl();
            $url = $coreUrl . $jsonFile[0]['path'] . "/dist/index.html";
        } elseif (!empty($jsonFile[0]['uri'])) {
            $url = $jsonFile[0]['uri'] . "/dist/index.html";
        }

        return $response->withJson(['url' => $url]);
    }

    public static function authentication($authorizationHeaders = [])
    {
        $userId = null;
        if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
            if (AuthenticationModel::authentication(['login' => $_SERVER['PHP_AUTH_USER'], 'password' => $_SERVER['PHP_AUTH_PW']])) {
                $loginMethod = CoreConfigModel::getLoggingMethod();
                $user = UserModel::getByLogin(['select' => ['id', 'mode'], 'login' => $_SERVER['PHP_AUTH_USER']]);
                if ($loginMethod['id'] != 'standard') {
                    if ($user['mode'] == 'rest') {
                        $userId = $user['id'];
                    }
                } else {
                    $userId = $user['id'];
                }
            }
        } else {
            if (!empty($authorizationHeaders)) {
                $token = null;
                foreach ($authorizationHeaders as $authorizationHeader) {
                    if (strpos($authorizationHeader, 'Bearer') === 0) {
                        $token = str_replace('Bearer ', '', $authorizationHeader);
                    }
                }
                if (!empty($token)) {
                    try {
                        $jwt = (array)JWT::decode($token, CoreConfigModel::getEncryptKey(), ['HS256']);
                    } catch (\Exception $e) {
                        return null;
                    }
                    $jwt['user'] = (array)$jwt['user'];
                    if (!empty($jwt) && !empty($jwt['user']['id'])) {
                        $userId = $jwt['user']['id'];
                    }
                }
            }
        }

        if (!empty($userId)) {
            UserModel::update([
                'set'   => ['reset_token' => null],
                'where' => ['id = ?'],
                'data'  => [$userId]
            ]);
        }

        return $userId;
    }

    public static function isRouteAvailable(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId', 'currentRoute', 'currentMethod']);
        ValidatorModel::intVal($args, ['userId']);
        ValidatorModel::stringType($args, ['currentRoute', 'currentMethod']);

        $user = UserModel::getById(['select' => ['status', 'password_modification_date', 'mode', 'authorized_api'], 'id' => $args['userId']]);

        if ($user['mode'] == 'rest') {
            $authorizedApi = json_decode($user['authorized_api'], true);
            if (!empty($authorizedApi) && !in_array($args['currentMethod'].$args['currentRoute'], $authorizedApi)) {
                return ['isRouteAvailable' => false, 'errors' => 'This route is not authorized for this user'];
            }
            return ['isRouteAvailable' => true];
        } elseif ($user['status'] == 'ABS' && !in_array($args['currentRoute'], ['/users/{id}/status', '/currentUser/profile', '/header', '/passwordRules', '/users/{id}/password'])) {
            return ['isRouteAvailable' => false, 'errors' => 'User is ABS and must be activated'];
        }

        if (!in_array($args['currentRoute'], ['/passwordRules', '/users/{id}/password'])) {
            $loggingMethod = CoreConfigModel::getLoggingMethod();

            if (!in_array($loggingMethod['id'], ['sso', 'cas', 'ldap', 'keycloak', 'shibboleth'])) {
                $passwordRules = PasswordModel::getEnabledRules();
                if (!empty($passwordRules['renewal'])) {
                    $currentDate = new \DateTime();
                    $lastModificationDate = new \DateTime($user['password_modification_date']);
                    $lastModificationDate->add(new \DateInterval("P{$passwordRules['renewal']}D"));

                    if ($currentDate > $lastModificationDate) {
                        return ['isRouteAvailable' => false, 'errors' => 'User must change his password'];
                    }
                }
            }
        }

        return ['isRouteAvailable' => true];
    }

    public static function handleFailedAuthentication(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId']);
        ValidatorModel::intVal($args, ['userId']);

        $passwordRules = PasswordModel::getEnabledRules();

        if (!empty($passwordRules['lockAttempts'])) {
            $user = UserModel::getById(['select' => ['failed_authentication', 'locked_until'], 'id' => $args['userId']]);
            $set = [];
            if (!empty($user['locked_until'])) {
                $currentDate = new \DateTime();
                $lockedUntil = new \DateTime($user['locked_until']);
                if ($lockedUntil < $currentDate) {
                    $set['locked_until'] = null;
                    $user['failed_authentication'] = 0;
                } else {
                    return ['accountLocked' => true, 'lockedDate' => $user['locked_until']];
                }
            }

            $set['failed_authentication'] = $user['failed_authentication'] + 1;
            UserModel::update([
                'set'       => $set,
                'where'     => ['id = ?'],
                'data'      => [$args['userId']]
            ]);

            if (!empty($user['failed_authentication']) && ($user['failed_authentication'] + 1) >= $passwordRules['lockAttempts'] && !empty($passwordRules['lockTime'])) {
                $lockedUntil = time() + 60 * $passwordRules['lockTime'];
                UserModel::update([
                    'set'       => ['locked_until'  => date('Y-m-d H:i:s', $lockedUntil)],
                    'where'     => ['id = ?'],
                    'data'      => [$args['userId']]
                ]);
                return ['accountLocked' => true, 'lockedDate' => date('Y-m-d H:i:s', $lockedUntil)];
            }
        }

        return true;
    }

    public function authenticate(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $loggingMethod = CoreConfigModel::getLoggingMethod();
        if (in_array($loggingMethod['id'], ['standard', 'ldap'])) {
            if (!Validator::stringType()->notEmpty()->validate($body['login']) || !Validator::stringType()->notEmpty()->validate($body['password'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
            }
        }

        if ($loggingMethod['id'] == 'standard') {
            $login = strtolower($body['login']);
            if (!AuthenticationController::isUserAuthorized(['login' => $login])) {
                return $response->withStatus(403)->withJson(['errors' => 'Authentication Failed']);
            }
            $authenticated = AuthenticationController::standardConnection(['login' => $login, 'password' => $body['password']]);
            if (!empty($authenticated['date'])) {
                return $response->withStatus(401)->withJson(['errors' => $authenticated['errors'], 'date' => $authenticated['date']]);
            } elseif (!empty($authenticated['errors'])) {
                return $response->withStatus(401)->withJson(['errors' => $authenticated['errors']]);
            }
        } elseif ($loggingMethod['id'] == 'ldap') {
            $login = strtolower($body['login']);
            if (!AuthenticationController::isUserAuthorized(['login' => $login])) {
                return $response->withStatus(403)->withJson(['errors' => 'Authentication Failed']);
            }
            $authenticated = AuthenticationController::ldapConnection(['login' => $login, 'password' => $body['password']]);
            if (!empty($authenticated['errors'])) {
                return $response->withStatus(401)->withJson(['errors' => $authenticated['errors']]);
            }
        } elseif ($loggingMethod['id'] == 'cas') {
            $authenticated = AuthenticationController::casConnection();
            if (!empty($authenticated['errors'])) {
                return $response->withStatus(401)->withJson(['errors' => $authenticated['errors']]);
            }
            $login = strtolower($authenticated['login']);
            if (!AuthenticationController::isUserAuthorized(['login' => $login])) {
                return $response->withStatus(403)->withJson(['errors' => 'Authentication Failed']);
            }
        } else {
            return $response->withStatus(403)->withJson(['errors' => 'Logging method unauthorized']);
        }

        $user = UserModel::getByLogin(['login' => $login, 'select' => ['id', 'refresh_token', 'user_id']]);

        $GLOBALS['id'] = $user['id'];
        $GLOBALS['login'] = $user['user_id'];

        $user['refresh_token'] = json_decode($user['refresh_token'], true);
        foreach ($user['refresh_token'] as $key => $refreshToken) {
            try {
                JWT::decode($refreshToken, CoreConfigModel::getEncryptKey(), ['HS256']);
            } catch (\Exception $e) {
                unset($user['refresh_token'][$key]);
            }
        }
        $user['refresh_token'] = array_values($user['refresh_token']);
        if (count($user['refresh_token']) > 10) {
            array_shift($user['refresh_token']);
        }

        $refreshToken = AuthenticationController::getRefreshJWT();
        $user['refresh_token'][] = $refreshToken;
        UserModel::update([
            'set'   => ['reset_token' => null, 'refresh_token' => json_encode($user['refresh_token']), 'failed_authentication' => 0, 'locked_until' => null],
            'where' => ['id = ?'],
            'data'  => [$user['id']]
        ]);

        $response = $response->withHeader('Token', AuthenticationController::getJWT());
        $response = $response->withHeader('Refresh-Token', $refreshToken);

        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $user['id'],
            'eventType' => 'LOGIN',
            'info'      => _LOGIN . ' : ' . $login,
            'moduleId'  => 'authentication',
            'eventId'   => 'login'
        ]);

        return $response->withStatus(204);
    }

    public function logout(Request $request, Response $response)
    {
        $loggingMethod = CoreConfigModel::getLoggingMethod();

        if ($loggingMethod['id'] == 'cas') {
            $res = AuthenticationController::casDisconnection();
        }
        return $response->withJson(['logoutUrl' => $res['logoutUrl'], 'redirectUrl' => $res['redirectUrl']]);
    }

    private static function standardConnection(array $args)
    {
        $login = $args['login'];
        $password = $args['password'];

        $authenticated = AuthenticationModel::authentication(['login' => $login, 'password' => $password]);
        if (empty($authenticated)) {
            $user = UserModel::getByLogin(['login' => $login, 'select' => ['id']]);
            $handle = AuthenticationController::handleFailedAuthentication(['userId' => $user['id']]);
            if (!empty($handle['accountLocked'])) {
                return ['errors' => 'Account Locked', 'date' => $handle['lockedDate']];
            }
            return ['errors' => 'Authentication Failed'];
        }

        return true;
    }

    private static function ldapConnection(array $args)
    {
        $login = $args['login'];
        $password = $args['password'];

        $ldapConfigurations = CoreConfigModel::getXmlLoaded(['path' => 'modules/ldap/xml/config.xml']);
        if (empty($ldapConfigurations)) {
            return ['errors' => 'No ldap configurations'];
        }

        foreach ($ldapConfigurations->config->ldap as $ldapConfiguration) {
            $ssl = (string)$ldapConfiguration->ssl;
            $domain = (string)$ldapConfiguration->domain;
            $prefix = (string)$ldapConfiguration->prefix_login;
            $suffix = (string)$ldapConfiguration->suffix_login;
            $standardConnect = (string)$ldapConfiguration->standardConnect;

            $uri = ($ssl == 'true' ? "LDAPS://{$domain}" : $domain);

            $ldap = @ldap_connect($uri);
            if ($ldap === false) {
                $error = 'Ldap connect failed : uri is maybe wrong';
                continue;
            }
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 10);
            $ldapLogin = (!empty($prefix) ? $prefix . '\\' . $login : $login);
            $ldapLogin = (!empty($suffix) ? $ldapLogin . $suffix : $ldapLogin);
            if (!empty((string)$ldapConfiguration->baseDN)) { //OpenLDAP
                $search = @ldap_search($ldap, (string)$ldapConfiguration->baseDN, "(uid={$ldapLogin})", ['dn']);
                if ($search === false) {
                    $error = 'Ldap search failed : baseDN is maybe wrong => ' . ldap_error($ldap);
                    continue;
                }
                $entries = ldap_get_entries($ldap, $search);
                $ldapLogin = $entries[0]['dn'];
            }
            $authenticated = @ldap_bind($ldap, $ldapLogin, $password);
            if ($authenticated) {
                break;
            }
            $error = ldap_error($ldap);
        }

        if (!empty($standardConnect) && $standardConnect == 'true') {
            if (empty($authenticated)) {
                $authenticated = AuthenticationModel::authentication(['login' => $login, 'password' => $password]);
            } else {
                $user = UserModel::getByLogin(['login' => $login, 'select' => ['id']]);
                UserModel::updatePassword(['id' => $user['id'], 'password' => $password]);
            }
        }

        if (empty($authenticated) && !empty($error) && $error != 'Invalid credentials') {
            return ['errors' => $error];
        } elseif (empty($authenticated) && !empty($error) && $error == 'Invalid credentials') {
            return ['errors' => 'Authentication Failed'];
        }

        return true;
    }

    private static function casConnection()
    {
        $casConfiguration = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/cas_config.xml']);

        $version = (string)$casConfiguration->CAS_VERSION;
        $hostname = (string)$casConfiguration->WEB_CAS_URL;
        $port = (string)$casConfiguration->WEB_CAS_PORT;
        $uri = (string)$casConfiguration->WEB_CAS_CONTEXT;
        $certificate = (string)$casConfiguration->PATH_CERTIFICATE;
        $separator = (string)$casConfiguration->ID_SEPARATOR;

        if (!in_array($version, ['CAS_VERSION_2_0', 'CAS_VERSION_3_0'])) {
            return ['errors' => 'Cas version not supported'];
        }

        \phpCAS::setDebug();
        \phpCAS::setVerbose(true);
        \phpCAS::client(constant($version), $hostname, (int)$port, $uri, $version != 'CAS_VERSION_3_0');

        if (!empty($certificate)) {
            \phpCAS::setCasServerCACert($certificate);
        } else {
            \phpCAS::setNoCasServerValidation();
        }
        \phpCAS::setFixedServiceURL(UrlController::getCoreUrl() . 'dist/index.html');
        \phpCAS::setNoClearTicketsFromUrl();
        if (!\phpCAS::isAuthenticated()) {
            return ['errors' => 'Cas authentication failed'];
        }

        $casId = \phpCAS::getUser();
        if (!empty($separator)) {
            $login = explode($separator, $casId)[0];
        } else {
            $login = $casId;
        }

        return ['login' => $login];
    }

    private static function casDisconnection()
    {
        $casConfiguration = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/cas_config.xml']);

        $version = (string)$casConfiguration->CAS_VERSION;
        $hostname = (string)$casConfiguration->WEB_CAS_URL;
        $port = (string)$casConfiguration->WEB_CAS_PORT;
        $uri = (string)$casConfiguration->WEB_CAS_CONTEXT;
        $certificate = (string)$casConfiguration->PATH_CERTIFICATE;

        \phpCAS::setDebug();
        \phpCAS::setVerbose(true);
        \phpCAS::client(constant($version), $hostname, (int)$port, $uri, $version != 'CAS_VERSION_3_0');

        if (!empty($certificate)) {
            \phpCAS::setCasServerCACert($certificate);
        } else {
            \phpCAS::setNoCasServerValidation();
        }
        \phpCAS::setFixedServiceURL(UrlController::getCoreUrl() . 'dist/index.html');
        \phpCAS::setNoClearTicketsFromUrl();
        $logoutUrl = \phpCAS::getServerLogoutURL();
        return ['logoutUrl' => $logoutUrl, 'redirectUrl' => UrlController::getCoreUrl() . 'dist/index.html'];
    }

    public function getRefreshedToken(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        if (!Validator::stringType()->notEmpty()->validate($queryParams['refreshToken'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Refresh Token is empty']);
        }

        try {
            $jwt = JWT::decode($queryParams['refreshToken'], CoreConfigModel::getEncryptKey(), ['HS256']);
        } catch (\Exception $e) {
            return $response->withStatus(401)->withJson(['errors' => 'Authentication Failed']);
        }

        $user = UserModel::getById(['select' => ['id', 'refresh_token'], 'id' => $jwt->user->id]);
        if (empty($user['refresh_token'])) {
            return $response->withStatus(401)->withJson(['errors' => 'Authentication Failed']);
        }

        $user['refresh_token'] = json_decode($user['refresh_token'], true);
        if (!in_array($queryParams['refreshToken'], $user['refresh_token'])) {
            return $response->withStatus(401)->withJson(['errors' => 'Authentication Failed']);
        }

        $GLOBALS['id'] = $user['id'];

        return $response->withJson(['token' => AuthenticationController::getJWT()]);
    }

    public static function getJWT()
    {
        $sessionTime = AuthenticationController::MAX_DURATION_TOKEN;

        $file = CoreConfigModel::getJsonLoaded(['path' => 'apps/maarch_entreprise/xml/config.json']);
        if ($file) {
            if (!empty($file['config']['cookieTime'])) {
                if ($sessionTime > (int)$file['config']['cookieTime']) {
                    $sessionTime = (int)$file['config']['cookieTime'];
                }
            }
        }

        $user = UserModel::getById(['id' => $GLOBALS['id'], 'select' => ['id', 'firstname', 'lastname', 'status', 'user_id as login']]);

        $token = [
            'exp'   => time() + 60 * $sessionTime,
            'user'  => $user
        ];

        $jwt = JWT::encode($token, CoreConfigModel::getEncryptKey());

        return $jwt;
    }

    public static function getRefreshJWT()
    {
        $sessionTime = AuthenticationController::MAX_DURATION_TOKEN;

        $file = CoreConfigModel::getJsonLoaded(['path' => 'apps/maarch_entreprise/xml/config.json']);
        if ($file) {
            $sessionTime = (int)$file['config']['cookieTime'];
        }

        $token = [
            'exp'   => time() + 60 * $sessionTime,
            'user'  => [
                'id' => $GLOBALS['id']
            ]
        ];

        $jwt = JWT::encode($token, CoreConfigModel::getEncryptKey());

        return $jwt;
    }

    public static function getResetJWT($args = [])
    {
        $token = [
            'exp'   => time() + $args['expirationTime'],
            'user'  => [
                'id' => $args['id']
            ]
        ];

        $jwt = JWT::encode($token, CoreConfigModel::getEncryptKey());

        return $jwt;
    }

    public static function sendAccountActivationNotification(array $args)
    {
        $resetToken = AuthenticationController::getResetJWT(['id' => $args['userId'], 'expirationTime' => 1209600]); // 14 days
        UserModel::update(['set' => ['reset_token' => $resetToken], 'where' => ['id = ?'], 'data' => [$args['userId']]]);

        $url = UrlController::getCoreUrl() . 'dist/index.html#/reset-password?token=' . $resetToken;

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_email_server', 'select' => ['value']]);
        $configuration = json_decode($configuration['value'], true);
        if (!empty($configuration['from'])) {
            $sender = $configuration['from'];
        } else {
            $sender = $args['userEmail'];
        }
        EmailController::createEmail([
            'userId'    => $args['userId'],
            'data'      => [
                'sender'        => ['email' => $sender],
                'recipients'    => [$args['userEmail']],
                'object'        => _NOTIFICATIONS_USER_CREATION_SUBJECT,
                'body'          => _NOTIFICATIONS_USER_CREATION_BODY . '<a href="' . $url . '">'._CLICK_HERE.'</a>' . _NOTIFICATIONS_USER_CREATION_FOOTER,
                'isHtml'        => true,
                'status'        => 'WAITING'
            ]
        ]);

        return true;
    }

    private static function isUserAuthorized(array $args)
    {
        $user = UserModel::getByLogin(['login' => $args['login'], 'select' => ['mode', 'status']]);
        if (empty($user) || $user['mode'] == 'rest' || $user['status'] == 'SPD') {
            return false;
        }

        return true;
    }
}
