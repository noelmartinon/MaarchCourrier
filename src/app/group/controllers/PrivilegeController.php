<?php

namespace Group\controllers;

use Basket\models\BasketModel;
use Basket\models\GroupBasketModel;
use Basket\models\RedirectBasketModel;
use Group\models\GroupModel;
use Group\models\PrivilegeModel;
use Resource\controllers\ResController;
use Resource\controllers\ResourceListController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;
use User\models\UserGroupModel;
use User\models\UserModel;

class PrivilegeController
{
    public static function addPrivilege(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $group = GroupModel::getById(['id' => $args['id']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        if (PrivilegeModel::groupHasPrivilege(['privilegeId' => $args['privilegeId'], 'groupId' => $group['group_id']])) {
            return $response->withStatus(204);
        }

        PrivilegeModel::addPrivilegeToGroup(['privilegeId' => $args['privilegeId'], 'groupId' => $group['group_id']]);

        if ($args['privilegeId'] == 'admin_users') {
            $groups = GroupModel::get(['select' => ['id']]);
            $groups = array_column($groups, 'id');

            $parameters = json_encode(['groups' => $groups]);

            PrivilegeModel::updateParameters(['groupId' => $group['group_id'], 'privilegeId' => $args['privilegeId'], 'parameters' => $parameters]);
        }

        return $response->withStatus(204);
    }

    public static function removePrivilege(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $group = GroupModel::getById(['id' => $args['id']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        if (!PrivilegeModel::groupHasPrivilege(['privilegeId' => $args['privilegeId'], 'groupId' => $group['group_id']])) {
            return $response->withStatus(204);
        }

        PrivilegeModel::removePrivilegeToGroup(['privilegeId' => $args['privilegeId'], 'groupId' => $group['group_id']]);

        return $response->withStatus(204);
    }

    public static function updateParameters(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_groups', 'userId' => $GLOBALS['id']])) {
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

        PrivilegeModel::updateParameters(['groupId' => $group['group_id'], 'privilegeId' => $args['privilegeId'], 'parameters' => $parameters]);

        return $response->withStatus(204);
    }

    public static function getParameters(Request $request, Response $response, array $args)
    {
        $group = GroupModel::getById(['id' => $args['id']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        $queryParams = $request->getQueryParams();

        $parameters = PrivilegeModel::getParametersFromGroupPrivilege(['groupId' => $group['group_id'], 'privilegeId' => $args['privilegeId']]);

        if (!empty($queryParams['parameter'])) {
            if (!isset($parameters[$queryParams['parameter']])) {
                return $response->withStatus(400)->withJson(['errors' => 'Parameter not found']);
            }

            $parameters = $parameters[$queryParams['parameter']];
        }

        return $response->withJson($parameters);
    }

    public static function hasPrivilege(array $args)
    {
        ValidatorModel::notEmpty($args, ['privilegeId', 'userId']);
        ValidatorModel::stringType($args, ['privilegeId']);
        ValidatorModel::intVal($args, ['userId']);

        $user = UserModel::getById([
            'select'    => ['user_id'],
            'id'        => $args['userId']
        ]);
        if ($user['user_id'] == 'superadmin') {
            return true;
        }

        $hasPrivilege = DatabaseModel::select([
            'select'    => [1],
            'table'     => ['usergroup_content, usergroups_services, usergroups'],
            'where'     => [
                'usergroup_content.group_id = usergroups.id',
                'usergroups.group_id = usergroups_services.group_id',
                'usergroup_content.user_id = ?',
                'usergroups_services.service_id = ?'
            ],
            'data'      => [$args['userId'], $args['privilegeId']]
        ]);

        return !empty($hasPrivilege);
    }

    public static function getPrivilegesByUser(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId']);
        ValidatorModel::intVal($args, ['userId']);

        $user = UserModel::getById([
            'select'    => ['user_id'],
            'id'        => $args['userId']
        ]);
        if ($user['user_id'] == 'superadmin') {
            return ['ALL_PRIVILEGES'];
        }

        $privilegesStoredInDB = PrivilegeModel::getByUser(['id' => $args['userId']]);
        $privilegesStoredInDB = array_column($privilegesStoredInDB, 'service_id');

        return $privilegesStoredInDB;
    }

    public static function getAssignableGroups(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId']);
        ValidatorModel::intVal($args, ['userId']);

        $rawUserGroups = UserModel::getGroupsByUser(['id' => $args['userId']]);
        $userGroups = array_column($rawUserGroups, 'group_id');

        $assignable = [];
        foreach ($userGroups as $userGroup) {
            $groups = PrivilegeModel::getParametersFromGroupPrivilege(['groupId' => $userGroup, 'privilegeId' => 'admin_users']);
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
        ValidatorModel::intVal($args, ['userId', 'groupId']);

        $user = UserModel::getById([
            'select'    => ['user_id'],
            'id'        => $args['userId']
        ]);
        if ($user['user_id'] == 'superadmin') {
            return true;
        }

        $privileges = PrivilegeModel::getByUserAndPrivilege(['userId' => $args['userId'], 'privilegeId' => 'admin_users']);
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

    public static function canIndex(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId']);
        ValidatorModel::intVal($args, ['userId']);

        $canIndex = UserGroupModel::getWithGroups([
            'select'    => [1],
            'where'     => ['usergroup_content.user_id = ?', 'usergroups.can_index = ?'],
            'data'      => [$args['userId'], true]
        ]);

        return !empty($canIndex);
    }

    public static function canUpdateResource(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId', 'resId']);
        ValidatorModel::intVal($args, ['userId', 'resId']);

        if (PrivilegeController::hasPrivilege(['privilegeId' => 'edit_resource', 'userId' => $args['userId']])) {
            return ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $args['userId']]);
        }

        return PrivilegeController::isResourceInProcess(['userId' => $args['userId'], 'resId' => $args['resId'], 'canUpdate' => true]);
    }

    public static function isResourceInProcess(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'userId']);
        ValidatorModel::intVal($args, ['resId', 'userId']);

        $currentUser = UserModel::getById(['id' => $args['userId'], 'select' => ['id', 'user_id']]);

        $basketsClause = '';

        $groups = UserGroupModel::get(['select' => ['group_id'], 'where' => ['user_id = ?'], 'data' => [$currentUser['id']]]);
        $groups = array_column($groups, 'group_id');
        if (!empty($groups)) {
            $groups = GroupModel::get(['select' => ['group_id'], 'where' => ['id in (?)'], 'data' => [$groups]]);
            $groups = array_column($groups, 'group_id');

            $where = ['group_id in (?)', 'list_event = ?'];
            $data = [$groups, 'processDocument'];
            if (!empty($args['canUpdate'])) {
                $where[] = "list_event_data->>'canUpdate' = ?";
                $data[] = 'true';
            }
            $baskets = GroupBasketModel::get(['select' => ['basket_id'], 'where' => $where, 'data' => $data]);
            $baskets = array_column($baskets, 'basket_id');
            if (!empty($baskets)) {
                $clauses = BasketModel::get(['select' => ['basket_clause'], 'where' => ['basket_id in (?)'], 'data' => [$baskets]]);

                foreach ($clauses as $clause) {
                    $basketClause = PreparedClauseController::getPreparedClause(['clause' => $clause['basket_clause'], 'login' => $currentUser['user_id']]);
                    if (!empty($basketsClause)) {
                        $basketsClause .= ' or ';
                    }
                    $basketsClause .= "({$basketClause})";
                }
            }
        }

        $assignedBaskets = RedirectBasketModel::getAssignedBasketsByUserId(['userId' => $currentUser['id']]);
        foreach ($assignedBaskets as $basket) {
            $where = ['basket_id = ?', 'group_id = ?', 'list_event = ?'];
            $data = [$basket['basket_id'], $basket['oldGroupId'], 'processDocument'];
            if (!empty($args['canUpdate'])) {
                $where[] = "list_event_data->>'canUpdate' = ?";
                $data[] = 'true';
            }
            $hasSB = GroupBasketModel::get(['select' => [1], 'where' => $where, 'data' => $data]);
            if (!empty($hasSB)) {
                $basketOwner = UserModel::getById(['id' => $basket['owner_user_id'], 'select' => ['user_id']]);
                $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $basketOwner['user_id']]);
                if (!empty($basketsClause)) {
                    $basketsClause .= ' or ';
                }
                $basketsClause .= "({$basketClause})";
            }
        }

        try {
            $res = ResModel::getOnView(['select' => [1], 'where' => ['res_id = ?', "({$basketsClause})"], 'data' => [$args['resId']]]);
            if (empty($res)) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}