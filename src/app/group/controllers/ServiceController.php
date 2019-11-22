<?php

namespace Group\controllers;

use Group\models\GroupModel;
use Group\models\ServiceModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

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

    public static function getAssignableGroups(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId']);
        ValidatorModel::stringType($args, ['userId']);

        $rawUserGroups = UserModel::getGroupsByUserId(['userId' => $args['userId']]);
        $userGroups = array_column($rawUserGroups, 'group_id');

        $assignable = [];
        foreach ($userGroups as $userGroup) {
            $groups = ServiceModel::getParametersFromGroupPrivilege(['groupId' => $userGroup, 'privilegeId' => 'admin_users']);
            if (!empty($groups)) {
                $groups = $groups['groups'];
                $assignable = array_merge($assignable, $groups);
            }
        }

        foreach ($assignable as $key => $group) {
            $assignable[$key] = GroupModel::getById(['id' => $group, 'select' => ['group_id', 'group_desc']]);
        }

        return $assignable;
    }

    public static function canAssignGroup(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId', 'groupId']);
        ValidatorModel::stringType($args, ['userId']);
        ValidatorModel::intVal($args, ['groupId']);

        if ($args['userId'] == 'superadmin') {
            return true;
        }

        $privileges = ServiceModel::getByUserAndPrivilege(['userId' => $args['userId'], 'privilegeId' => 'admin_users']);
        $privileges = array_column($privileges, 'parameters');

        if (empty($privileges)) {
            return false;
        }
        $assignable = [];

        foreach ($privileges as $groups) {
            $groups = json_decode($groups);
            $groups = $groups->groups;
            if ($groups != null) {
                $assignable = array_merge($assignable, $groups);
            }
        }

        if (count($assignable) == 0) {
            return false;
        }

        return in_array($args['groupId'], $assignable);
    }
}
