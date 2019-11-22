<?php

namespace Group\controllers;

use Group\models\GroupModel;
use Group\models\ServiceModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\ValidatorModel;

class ServiceController
{
    public static function getMenuServicesByUserId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['userId']);
        ValidatorModel::stringType($aArgs, ['userId']);

        $rawServicesStoredInDB = ServiceModel::getByUserId(['userId' => $aArgs['userId']]);
        $servicesStoredInDB = [];
        foreach ($rawServicesStoredInDB as $value) {
            $servicesStoredInDB[] = $value['service_id'];
        }

        $menu = [];
        if (!empty($servicesStoredInDB)) {
            $menu = ServiceModel::getApplicationServicesByUserServices(['userServices' => $servicesStoredInDB, 'type' => 'menu']);
            $menuModules = ServiceModel::getModulesServicesByUserServices(['userServices' => $servicesStoredInDB, 'type' => 'menu']);
            $menu = array_merge($menu, $menuModules);
        }

        return $menu;
    }

    public static function updateParameters(Request $request, Response $response, array $args)
    {
        if (!ServiceModel::hasService(['id' => 'admin_groups', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $group = GroupModel::getById(['id' => $args['id']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        $data = $request->getParams();

        if (!Validator::arrayType()->validate($data['parameters'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body parameters is not an array']);
        }

        $parameters = json_encode($data['parameters']);

        ServiceModel::updateParameters(['groupId' => $group['group_id'], 'privilegeId' => $args['privilegeId'], 'parameters' => $parameters]);

        return $response->withStatus(204);
    }

    public static function getParameters(Request $request, Response $response, array $args)
    {
        $group = GroupModel::getById(['id' => $args['id']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        $queryParams = $request->getQueryParams();

        $parameters = ServiceModel::getParametersFromGroupPrivilege(['groupId' => $group['group_id'], 'privilegeId' => $args['privilegeId']]);

        if (!empty($queryParams['parameter'])) {
            if (!isset($parameters[$queryParams['parameter']])) {
                return $response->withStatus(400)->withJson(['errors' => 'Parameter not found']);
            }

            $parameters = $parameters[$queryParams['parameter']];
        }

        return $response->withJson($parameters);
    }
}
