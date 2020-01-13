<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Configuration Controller
 * @author dev@maarch.org
 */

namespace Configuration\controllers;

use Configuration\models\ConfigurationModel;
use Group\models\ServiceModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\PasswordModel;

class ConfigurationController
{
    public function getByService(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => $aArgs['service'], 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $configuration = ConfigurationModel::getByService(['service' => $aArgs['service']]);
        $configuration['value'] = (array)json_decode($configuration['value']);
        if (!empty($configuration['value']['password'])) {
            $configuration['value']['password'] = '';
            $configuration['value']['passwordAlreadyExists'] = true;
        } else {
            $configuration['value']['passwordAlreadyExists'] = false;
        }

        return $response->withJson(['configuration' => $configuration]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => $aArgs['service'], 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (empty(ConfigurationModel::getByService(['service' => $aArgs['service'], 'select' => [1]]))) {
            return $response->withStatus(400)->withJson(['errors' => 'Service configuration does not exist']);
        }

        $data = $request->getParams();

        if ($aArgs['service'] == 'admin_email_server') {
            if ($data['auth'] && empty($data['password'])) {
                $configuration = ConfigurationModel::getByService(['service' => $aArgs['service']]);
                $configuration['value'] = (array)json_decode($configuration['value']);
                if (!empty($configuration['value']['password'])) {
                    $data['password'] = $configuration['value']['password'];
                }
            } elseif ($data['auth'] && !empty($data['password'])) {
                $data['password'] = PasswordModel::encrypt(['password' => $data['password']]);
            }
            $check = ConfigurationController::checkMailer($data);
            if (!empty($check['errors'])) {
                return $response->withStatus($check['code'])->withJson(['errors' => $check['errors']]);
            }
            $data['charset'] = empty($data['charset']) ? 'utf-8' : $data['charset'];
            unset($data['passwordAlreadyExists']);
        }

        $data = json_encode($data);
        ConfigurationModel::update(['set' => ['value' => $data], 'where' => ['service = ?'], 'data' => [$aArgs['service']]]);

        return $response->withJson(['success' => 'success']);
    }

    private static function checkMailer(array $aArgs)
    {
        if (!Validator::stringType()->notEmpty()->validate($aArgs['type'])) {
            return ['errors' => 'Configuration type is missing', 'code' => 400];
        }
        if (!Validator::email()->notEmpty()->validate($aArgs['from'])) {
            return ['errors' => 'Configuration from is missing or not well formatted', 'code' => 400];
        }
        
        if (in_array($aArgs['type'], ['smtp', 'mail'])) {
            $check = Validator::stringType()->notEmpty()->validate($aArgs['host']);
            $check = $check && Validator::intVal()->notEmpty()->validate($aArgs['port']);
            $check = $check && Validator::boolType()->validate($aArgs['auth']);
            if ($aArgs['auth']) {
                $check = $check && Validator::stringType()->notEmpty()->validate($aArgs['user']);
                $check = $check && Validator::stringType()->notEmpty()->validate($aArgs['password']);
            }
            $check = $check && Validator::stringType()->validate($aArgs['secure']);
            if (!$check) {
                return ['errors' => "Configuration data is missing or not well formatted", 'code' => 400];
            }
        }

        return ['success' => 'success'];
    }
}
