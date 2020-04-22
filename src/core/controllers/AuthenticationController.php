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
        'GET/authenticationInformations', 'GET/validUrl', 'GET/authenticate/token', 'GET/images', 'POST/password', 'PUT/password', 'GET/passwordRules',
        'GET/jnlp/{jnlpUniqueId}', 'GET/onlyOffice/mergedFile', 'POST/onlyOfficeCallback', 'POST/authenticate'
    ];

    public function getInformations(Request $request, Response $response)
    {
        $path = CoreConfigModel::getConfigPath();
        $hashedPath = md5($path);

        $appName = CoreConfigModel::getApplicationName();
        $parameter = ParameterModel::getById(['id' => 'loginpage_message', 'select' => ['param_value_string']]);

        return $response->withJson(['instanceId' => $hashedPath, 'applicationName' => $appName, 'loginMessage' => $parameter['param_value_string'] ?? null]);
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
                $user = UserModel::getByLogin(['select' => ['id', 'loginmode'], 'login' => $_SERVER['PHP_AUTH_USER']]);
                if ($loginMethod['id'] != 'standard') {
                    if ($user['loginmode'] == 'restMode') {
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
        ValidatorModel::notEmpty($args, ['userId', 'currentRoute']);
        ValidatorModel::intVal($args, ['userId']);
        ValidatorModel::stringType($args, ['currentRoute']);

        $user = UserModel::getById(['select' => ['status', 'password_modification_date'], 'id' => $args['userId']]);

        if ($user['status'] == 'ABS' && !in_array($args['currentRoute'], ['/users/{id}/status', '/currentUser/profile', '/header', '/passwordRules', '/users/{id}/password'])) {
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

        $check = Validator::stringType()->notEmpty()->validate($body['login']);
        $check = $check && Validator::stringType()->notEmpty()->validate($body['password']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $login = strtolower($body['login']);
        $authenticated = AuthenticationModel::authentication(['login' => $login, 'password' => $body['password']]);
        if (empty($authenticated)) {
            $user = UserModel::getByLogin(['login' => $login, 'select' => ['id', 'status']]);
            if (empty($user)) {
                return $response->withStatus(401)->withJson(['errors' => 'Authentication Failed']);
            } elseif ($user['status'] == 'SPD') {
                return $response->withStatus(401)->withJson(['errors' => 'Account Suspended']);
            } else {
                $handle = AuthenticationController::handleFailedAuthentication(['userId' => $user['id']]);
                if (!empty($handle['accountLocked'])) {
                    return $response->withStatus(401)->withJson(['errors' => 'Account Locked', 'date' => $handle['lockedDate']]);
                }
                return $response->withStatus(401)->withJson(['errors' => 'Authentication Failed']);
            }
        }

        $user = UserModel::getByLogin(['login' => $login, 'select' => ['id', 'loginmode', 'refresh_token', 'user_id']]);
        if (empty($user) || $user['loginmode'] == 'restMode') {
            return $response->withStatus(403)->withJson(['errors' => 'Authentication unauthorized']);
        }

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

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);
        if ($loadedXml) {
            if (!empty($loadedXml->CONFIG->CookieTime)) {
                if ($sessionTime > (int)$loadedXml->CONFIG->CookieTime) {
                    $sessionTime = (int)$loadedXml->CONFIG->CookieTime;
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

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);
        if ($loadedXml) {
            $sessionTime = (int)$loadedXml->CONFIG->CookieTime;
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

        $url = UrlController::getCoreUrl() . 'dist/index.html#/update-password?token=' . $resetToken;

        $configuration = ConfigurationModel::getByService(['service' => 'admin_email_server', 'select' => ['value']]);
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
}
