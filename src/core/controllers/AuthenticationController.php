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

use Firebase\JWT\JWT;
use SrcCore\models\AuthenticationModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class AuthenticationController
{
    public static function authentication()
    {
        $userId = null;
        if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
            if (AuthenticationModel::authentication(['userId' => $_SERVER['PHP_AUTH_USER'], 'password' => $_SERVER['PHP_AUTH_PW']])) {
                $userId = $_SERVER['PHP_AUTH_USER'];
            }
        } else {
            $cookie = AuthenticationModel::getCookieAuth();
            if (!empty($cookie) && AuthenticationModel::cookieAuthentication($cookie)) {
                AuthenticationModel::setCookieAuth(['userId' => $cookie['userId']]);
                $userId = $cookie['userId'];
            }
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
        ValidatorModel::notEmpty($aArgs, ['userId', 'currentRoute']);
        ValidatorModel::stringType($aArgs, ['userId', 'currentRoute']);

        if ($aArgs['currentRoute'] != '/initialize') {
            $user = UserModel::getByLogin(['select' => ['status'], 'login' => $aArgs['userId']]);

            if ($user['status'] == 'ABS' && !in_array($aArgs['currentRoute'], ['/users/{id}/status', '/currentUser/profile', '/header', '/passwordRules', '/users/{id}/password'])) {
                return ['isRouteAvailable' => false, 'errors' => 'User is ABS and must be activated'];
            }

            if (!in_array($aArgs['currentRoute'], ['/passwordRules', '/users/{id}/password'])) {
                $loggingMethod = CoreConfigModel::getLoggingMethod();

                if (!in_array($loggingMethod['id'], ['sso', 'cas', 'ldap', 'ozwillo', 'shibboleth'])) {

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
}
