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
        'GET/jnlp/{jnlpUniqueId}', 'POST/password', 'PUT/password', 'GET/passwordRules', 'GET/onlyOffice/mergedFile', 'POST/onlyOfficeCallback'
    ];

    public static function authentication()
    {
        $userId = null;
        if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
            if (AuthenticationModel::authentication(['login' => $_SERVER['PHP_AUTH_USER'], 'password' => $_SERVER['PHP_AUTH_PW']])) {
                $loginMethod = CoreConfigModel::getLoggingMethod();
                if ($loginMethod['id'] != 'standard') {
                    $user = UserModel::getByLogin(['select' => ['loginmode'], 'userId' => $_SERVER['PHP_AUTH_USER']]);
                    if ($user['loginmode'] == 'restMode') {
                        $userId = $_SERVER['PHP_AUTH_USER'];
                    }
                } else {
                    $userId = $_SERVER['PHP_AUTH_USER'];
                }
            }
        } else {
            $cookie = AuthenticationModel::getCookieAuth();
            if (!empty($cookie) && AuthenticationModel::cookieAuthentication($cookie)) {
                AuthenticationModel::setCookieAuth(['userId' => $cookie['userId']]);
                $userId = $cookie['userId'];
            }

//            if (!empty($authorizationHeaders)) {
//                $token = null;
//                foreach ($authorizationHeaders as $authorizationHeader) {
//                    if (strpos($authorizationHeader, 'Bearer') === 0) {
//                        $token = str_replace('Bearer ', '', $authorizationHeader);
//                    }
//                }
//                if (!empty($token)) {
//                    try {
//                        $jwt = (array)JWT::decode($token, CoreConfigModel::getEncryptKey(), ['HS256']);
//                    } catch (\Exception $e) {
//                        return null;
//                    }
//                    $jwt['user'] = (array)$jwt['user'];
//                    if (!empty($jwt) && !empty($jwt['user']['id'])) {
//                        $id = $jwt['user']['id'];
//                    }
//                }
//            }
        }

        if (!empty($userId)) {
            UserModel::update([
                'set'   => ['reset_token' => null],
                'where' => ['user_id = ?'],
                'data'  => [$userId]
            ]);
        }

        return $userId;
    }

    public static function isRouteAvailable(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['login', 'currentRoute']);
        ValidatorModel::stringType($aArgs, ['login', 'currentRoute']);

        if ($aArgs['currentRoute'] != '/initialize') {
            $user = UserModel::getByLogin(['select' => ['status'], 'login' => $aArgs['login']]);

            if ($user['status'] == 'ABS' && !in_array($aArgs['currentRoute'], ['/users/{id}/status', '/currentUser/profile', '/header', '/passwordRules', '/users/{id}/password'])) {
                return ['isRouteAvailable' => false, 'errors' => 'User is ABS and must be activated'];
            }

            if (!in_array($aArgs['currentRoute'], ['/passwordRules', '/users/{id}/password'])) {
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
        }

        return ['isRouteAvailable' => true];
    }

    public static function handleFailedAuthentication(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $passwordRules = PasswordModel::getEnabledRules();

        if (!empty($passwordRules['lockAttempts'])) {
            $user = UserModel::getByLowerLogin(['select' => ['failed_authentication', 'locked_until'], 'login' => $aArgs['userId']]);

            if (!empty($user)) {
                if (!empty($user['locked_until'])) {
                    $lockedDate = new \DateTime($user['locked_until']);
                    $currentDate = new \DateTime();
                    if ($currentDate > $lockedDate) {
                        AuthenticationModel::resetFailedAuthentication(['userId' => $aArgs['userId']]);
                        $user['failed_authentication'] = 0;
                    } else {
                        return _ACCOUNT_LOCKED_UNTIL . " {$lockedDate->format('d/m/Y H:i')}";
                    }
                }

                AuthenticationModel::increaseFailedAuthentication(['userId' => $aArgs['userId'], 'tentatives' => $user['failed_authentication'] + 1]);

                if (!empty($user['failed_authentication']) && ($user['failed_authentication'] + 1) >= $passwordRules['lockAttempts'] && !empty($passwordRules['lockTime'])) {
                    $lockedUntil = time() + 60 * $passwordRules['lockTime'];
                    AuthenticationModel::lockUser(['userId' => $aArgs['userId'], 'lockedUntil' => $lockedUntil]);
                    return _ACCOUNT_LOCKED_FOR . " {$passwordRules['lockTime']} mn";
                }
            }
        }

        return _BAD_LOGIN_OR_PSW;
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
            return $response->withStatus(401)->withJson(['errors' => 'Authentication Failed']);
        }

        $user = UserModel::getByLogin(['login' => $login, 'select' => ['id', 'loginmode', 'refresh_token']]);
        if (empty($user) || $user['loginmode'] == 'restMode') {
            return $response->withStatus(403)->withJson(['errors' => 'Authentication unauthorized']);
        }

        $GLOBALS['id'] = $user['id'];

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
            'set'   => ['reset_token' => null, 'refresh_token' => json_encode($user['refresh_token'])],
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

        $token = [
            'exp'   => time() + 60 * $sessionTime,
            'user'  => [
                'id' => $GLOBALS['id']
            ]
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

        $url = UrlController::getCoreUrl() . 'apps/maarch_entreprise/index.php?display=true&page=login&update-password-token=' . $resetToken;

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
