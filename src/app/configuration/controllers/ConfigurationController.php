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
use Group\controllers\PrivilegeController;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\PasswordModel;

class ConfigurationController
{
    public function getByPrivilege(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => $args['privilege'], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => $args['privilege']]);
        $configuration['value'] = json_decode($configuration['value'], true);
        if (!empty($configuration['value']['password'])) {
            $configuration['value']['password'] = '';
            $configuration['value']['passwordAlreadyExists'] = true;
        } else {
            $configuration['value']['passwordAlreadyExists'] = false;
        }

        return $response->withJson(['configuration' => $configuration]);
    }

    public function update(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => $args['privilege'], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (empty(ConfigurationModel::getByPrivilege(['privilege' => $args['privilege'], 'select' => [1]]))) {
            return $response->withStatus(400)->withJson(['errors' => 'Privilege configuration does not exist']);
        }

        $data = $request->getParams();

        if ($args['privilege'] == 'admin_email_server') {
            if ($data['auth'] && empty($data['password'])) {
                $configuration = ConfigurationModel::getByPrivilege(['privilege' => $args['privilege']]);
                $configuration['value'] = json_decode($configuration['value'], true);
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
        ConfigurationModel::update(['set' => ['value' => $data], 'where' => ['privilege = ?'], 'data' => [$args['privilege']]]);

        return $response->withJson(['success' => 'success']);
    }

    private static function checkMailer(array $args)
    {
        if (!Validator::stringType()->notEmpty()->validate($args['type'])) {
            return ['errors' => 'Configuration type is missing', 'code' => 400];
        }
        if (!Validator::email()->notEmpty()->validate($args['from'])) {
            return ['errors' => 'Configuration from is missing or not well formatted', 'code' => 400];
        }
        
        if (in_array($args['type'], ['smtp', 'mail'])) {
            $check = Validator::stringType()->notEmpty()->validate($args['host']);
            $check = $check && Validator::intVal()->notEmpty()->validate($args['port']);
            $check = $check && Validator::boolType()->validate($args['auth']);
            if ($args['auth']) {
                $check = $check && Validator::stringType()->notEmpty()->validate($args['user']);
                $check = $check && Validator::stringType()->notEmpty()->validate($args['password']);
            }
            $check = $check && Validator::stringType()->validate($args['secure']);
            if (!$check) {
                return ['errors' => "Configuration data is missing or not well formatted", 'code' => 400];
            }
        }

        return ['success' => 'success'];
    }
}
