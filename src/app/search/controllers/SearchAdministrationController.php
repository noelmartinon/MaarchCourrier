<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Search Administration Controller
* @author dev@maarch.org
*/

namespace Search\controllers;

use Configuration\models\ConfigurationModel;
use Group\controllers\PrivilegeController;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;

class SearchAdministrationController
{
    public function get(Request $request, Response $response) {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_search']);
        $configuration = json_decode($configuration, true);

        return $response->withJson(['configuration' => $configuration]);
    }

    public function update(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_search', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (!Validator::notEmpty()->arrayType()->validate($body['listDisplay'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body listDisplay is empty or not an array']);
        }
        if (isset($body['listDisplay']['subInfos']) && !Validator::arrayType()->validate($body['listDisplay']['subInfos'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body listDisplay[subInfos] is not set or not an array']);
        }
        if (!Validator::intVal()->validate($body['listDisplay']['templateColumns'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body listDisplay[templateColumns] is not set or not an array']);
        }
        foreach ($body['listDisplay']['subInfos'] as $value) {
            if (!Validator::stringType()->notEmpty()->validate($value['value'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Body listDisplay[subInfos][value] is empty or not a string']);
            } elseif (!isset($value['cssClasses']) || !is_array($value['cssClasses'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Body listDisplay[subInfos][cssClasses] is not set or not an array']);
            }
        }

        if (empty($body['listEvent']['defaultTab'])) {
            $body['listEvent']['defaultTab'] = 'dashboard';
        }

        $configuration = ['listDisplay' => $body['listDisplay'], 'listEvent' => $body['listEvent']];
        $configuration = json_encode($configuration);

        ConfigurationModel::update([
            'set'   => ['value' => $configuration],
            'where' => ['privilege = ?'],
            'data'  => ['admin_search']
        ]);

        return $response->withStatus(204);
    }
}
