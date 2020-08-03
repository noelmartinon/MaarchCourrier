<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Entity Controller
* @author dev@maarch.org
*/

namespace Entity\controllers;

use Basket\models\GroupBasketRedirectModel;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use Entity\models\ListTemplateItemModel;
use Entity\models\ListTemplateModel;
use Group\controllers\PrivilegeController;
use Group\models\GroupModel;
use History\controllers\HistoryController;
use MessageExchange\controllers\AnnuaryController;
use Parameter\models\ParameterModel;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\PasswordModel;
use Template\models\TemplateAssociationModel;
use User\models\UserEntityModel;
use User\models\UserModel;
use \Template\models\TemplateModel;

class EntityController
{
    public function get(Request $request, Response $response)
    {
        return $response->withJson(['entities' => EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']])]);
    }

    public function getById(Request $request, Response $response, array $aArgs)
    {
        $entity = EntityModel::getById(['id' => $aArgs['id'], 'select' => ['id', 'entity_label', 'short_label', 'entity_full_name', 'entity_type', 'entity_id', 'enabled', 'parent_entity_id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        return $response->withJson($entity);
    }

    public function getDetailledById(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_entities', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entity = EntityModel::getByEntityId(['entityId' => $aArgs['id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $aEntities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($aEntities as $aEntity) {
            if ($aEntity['entity_id'] == $aArgs['id'] && $aEntity['allowed'] == false) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        $entity['types'] = EntityModel::getTypes();
        $listTemplateTypes = ListTemplateModel::getTypes(['select' => ['difflist_type_roles'], 'where' => ['difflist_type_id = ?'], 'data' => ['entity_id']]);
        $rolesForService = empty($listTemplateTypes[0]['difflist_type_roles']) ? [] : explode(' ', $listTemplateTypes[0]['difflist_type_roles']);

        //List Templates
        $listTemplates = ListTemplateModel::get([
            'select'    => ['id', 'title', 'description', 'type'],
            'where'     => ['entity_id = ?'],
            'data'      => [$entity['id']]
        ]);

        $entity['listTemplate'] = [];
        foreach ($rolesForService as $role) {
            $role == 'copy' ? $entity['listTemplate']['cc'] = [] : $entity['listTemplate'][$role] = [];
        }
        $entity['visaCircuit'] = [];
        $entity['opinionCircuit'] = [];
        foreach ($listTemplates as $listTemplate) {
            $listTemplateItems = ListTemplateItemModel::get(['select' => ['*'], 'where' => ['list_template_id = ?'], 'data' => [$listTemplate['id']]]);

            if ($listTemplate['type'] == 'diffusionList') {
                $entity['listTemplate'] = $listTemplate;
                $entity['listTemplate']['items'] = [];
                foreach ($listTemplateItems as $listTemplateItem) {
                    if ($listTemplateItem['item_type'] == 'user') {
                        $entity['listTemplate']['items'][$listTemplateItem['item_mode']][] = [
                            'id'                    => $listTemplateItem['item_id'],
                            'type'                  => $listTemplateItem['item_type'],
                            'sequence'              => $listTemplateItem['sequence'],
                            'labelToDisplay'        => UserModel::getLabelledUserById(['id' => $listTemplateItem['item_id']]),
                            'descriptionToDisplay'  => UserModel::getPrimaryEntityById(['id' => $listTemplateItem['item_id'], 'select' => ['entities.entity_label']])['entity_label']
                        ];
                    } elseif ($listTemplateItem['item_type'] == 'entity') {
                        $entity['listTemplate']['items'][$listTemplateItem['item_mode']][] = [
                            'id'                    => $listTemplateItem['item_id'],
                            'type'                  => $listTemplateItem['item_type'],
                            'sequence'              => $listTemplateItem['sequence'],
                            'labelToDisplay'        => EntityModel::getById(['id' => $listTemplateItem['item_id'], 'select' => ['entity_label']])['entity_label'],
                            'descriptionToDisplay'  => ''
                        ];
                    }
                }
            } else {
                $entity[$listTemplate['type']] = $listTemplate;
                $entity[$listTemplate['type']]['items'] = [];
                foreach ($listTemplateItems as $listTemplateItem) {
                    $entity[$listTemplate['type']]['items'][] = [
                        'id'                    => $listTemplateItem['item_id'],
                        'type'                  => $listTemplateItem['item_type'],
                        'mode'                  => $listTemplateItem['item_mode'],
                        'sequence'              => $listTemplateItem['sequence'],
                        'idToDisplay'           => UserModel::getLabelledUserById(['id' => $listTemplateItem['item_id']]),
                        'descriptionToDisplay'  => UserModel::getPrimaryEntityById(['id' => $listTemplateItem['item_id'], 'select' => ['entities.entity_label']])['entity_label']
                    ];
                }
            }
        }

        $entity['templates'] = TemplateModel::getByEntity([
            'select'    => ['t.template_id', 't.template_label', 'template_comment', 't.template_target', 't.template_attachment_type'],
            'entities'  => [$aArgs['id']]
        ]);

        $entity['externalId'] = json_decode($entity['external_id'], true);
        unset($entity['externalId']['alfrescoPassword'], $entity['external_id']);


        $entity['users'] = EntityModel::getUsersById(['id' => $entity['entity_id'], 'select' => ['users.id','users.user_id', 'users.firstname', 'users.lastname', 'users.status']]);
        $children = EntityModel::get(['select' => [1], 'where' => ['parent_entity_id = ?'], 'data' => [$aArgs['id']]]);
        $entity['hasChildren'] = count($children) > 0;
        $documents = ResModel::get(['select' => [1], 'where' => ['destination = ?'], 'data' => [$aArgs['id']]]);
        $entity['documents'] = count($documents);
        $instances = ListInstanceModel::get(['select' => [1], 'where' => ['item_id = ?', 'item_type = ?'], 'data' => [$aArgs['id'], 'entity_id']]);
        $entity['instances'] = count($instances);
        $redirects = GroupBasketRedirectModel::get(['select' => [1], 'where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);
        $entity['redirects'] = count($redirects);
        $entity['canAdminUsers'] = PrivilegeController::hasPrivilege(['privilegeId' => 'admin_users', 'userId' => $GLOBALS['id']]);
        $entity['canAdminTemplates'] = PrivilegeController::hasPrivilege(['privilegeId' => 'admin_templates', 'userId' => $GLOBALS['id']]);
        $siret = ParameterModel::getById(['id' => 'siret', 'select' => ['param_value_string']]);
        $entity['canSynchronizeSiret'] = !empty($siret['param_value_string']);

        return $response->withJson(['entity' => $entity]);
    }

    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_entities', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::stringType()->notEmpty()->validate($data['entity_id']) && preg_match("/^[\w-]*$/", $data['entity_id']) && (strlen($data['entity_id']) < 33);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['entity_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['short_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['entity_type']);
        if (!empty($data['email'])) {
            $check = $check && preg_match("/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/", $data['email']);
        }
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $existingEntity = EntityModel::getByEntityId(['entityId' => $data['entity_id'], 'select' => [1]]);
        if (!empty($existingEntity)) {
            return $response->withStatus(400)->withJson(['errors' => _ENTITY_ID_ALREADY_EXISTS]);
        }

        $externalId = [];
        if (!empty($data['externalId'])) {
            $externalId = $data['externalId'];
            if (!empty($data['externalId']['alfrescoPassword'])) {
                $externalId['alfrescoPassword'] = PasswordModel::encrypt(['password' => $data['externalId']['alfrescoPassword']]);
            }
        }
        $data['external_id'] = json_encode($externalId);

        EntityModel::create($data);
        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $data['entity_id'],
            'eventType' => 'ADD',
            'info'      => _ENTITY_CREATION . " : {$data['entity_id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entityCreation',
        ]);

        if (empty($data['parent_entity_id']) && $GLOBALS['userId'] != 'superadmin') {
            $user = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
            $primaryEntity = UserModel::getPrimaryEntityByUserId(['userId' => $GLOBALS['userId']]);
            $pEntity = 'N';
            if (empty($primaryEntity)) {
                $pEntity = 'Y';
            }

            UserEntityModel::addUserEntity(['id' => $user['id'], 'entityId' => $data['entity_id'], 'role' => '', 'primaryEntity' => $pEntity]);
            HistoryController::add([
                'tableName' => 'users',
                'recordId'  => $GLOBALS['userId'],
                'eventType' => 'UP',
                'info'      => _USER_ENTITY_CREATION . " : {$GLOBALS['userId']} {$data['entity_id']}",
                'moduleId'  => 'user',
                'eventId'   => 'userModification',
            ]);
        }

        return $response->withJson(['entities' => EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']])]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_entities', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entity = EntityModel::getByEntityId(['entityId' => $aArgs['id'], 'select' => [1]]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $aEntities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($aEntities as $aEntity) {
            if ($aEntity['entity_id'] == $aArgs['id'] && $aEntity['allowed'] == false) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        $data = $request->getParams();

        $check = Validator::stringType()->notEmpty()->validate($data['entity_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['short_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['entity_type']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $fatherAndSons = EntityModel::getEntityChildren(['entityId' => $aArgs['id']]);
        if (in_array($data['parent_entity_id'], $fatherAndSons)) {
            return $response->withStatus(400)->withJson(['errors' => _CAN_NOT_MOVE_IN_CHILD_ENTITY]);
        }

        $neededData = [
            'entity_label', 'short_label', 'entity_type', 'adrs_1', 'adrs_2', 'adrs_3',
            'zipcode', 'city', 'country', 'email', 'business_id', 'parent_entity_id',
            'ldap_id', 'archival_agreement', 'archival_agency', 'entity_full_name'
        ];
        foreach ($data as $key => $value) {
            if (!in_array($key, $neededData)) {
                unset($data[$key]);
            }
        }

        $externalId = $entity['external_id'];
        if (!empty($data['externalId'])) {
            $externalId = array_merge($externalId, $data['externalId']);
            if (!empty($data['externalId']['alfrescoPassword'])) {
                $externalId['alfrescoPassword'] = PasswordModel::encrypt(['password' => $data['externalId']['alfrescoPassword']]);
            }
        }
        $data['external_id'] = json_encode($externalId);

        EntityModel::update(['set' => $data, 'where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);
        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _ENTITY_MODIFICATION . " : {$aArgs['id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entityModification',
        ]);

        if (empty($data['parent_entity_id']) && $GLOBALS['userId'] != 'superadmin') {
            $hasEntity = UserEntityModel::get(['select' => [1], 'where' => ['user_id = ?', 'entity_id = ?'], 'data' => [$GLOBALS['userId'], $aArgs['id']]]);
            if (empty($hasEntity)) {
                $user = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
                $primaryEntity = UserModel::getPrimaryEntityByUserId(['userId' => $GLOBALS['userId']]);
                $pEntity = 'N';
                if (empty($primaryEntity)) {
                    $pEntity = 'Y';
                }

                UserEntityModel::addUserEntity(['id' => $user['id'], 'entityId' => $aArgs['id'], 'role' => '', 'primaryEntity' => $pEntity]);
                HistoryController::add([
                    'tableName' => 'users',
                    'recordId'  => $GLOBALS['userId'],
                    'eventType' => 'UP',
                    'info'      => _USER_ENTITY_CREATION . " : {$GLOBALS['userId']} {$aArgs['id']}",
                    'moduleId'  => 'user',
                    'eventId'   => 'userModification',
                ]);
            }
        }

        return $response->withJson(['entities' => EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']])]);
    }

    public function delete(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_entities', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entity = EntityModel::getByEntityId(['entityId' => $aArgs['id'], 'select' => ['id', 'business_id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $aEntities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($aEntities as $aEntity) {
            if ($aEntity['entity_id'] == $aArgs['id'] && $aEntity['allowed'] == false) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        $children  = EntityModel::get(['select' => [1], 'where' => ['parent_entity_id = ?'], 'data' => [$aArgs['id']]]);
        $documents = ResModel::get(['select' => [1], 'where' => ['destination = ?'], 'data' => [$aArgs['id']]]);
        $users     = EntityModel::getUsersById(['select' => [1], 'id' => $aArgs['id']]);
        $templates = TemplateAssociationModel::get(['select' => [1], 'where' => ['value_field = ?'], 'data' => [$aArgs['id']]]);
        $instances = ListInstanceModel::get(['select' => [1], 'where' => ['item_id = ?', 'item_type = ?'], 'data' => [$aArgs['id'], 'entity_id']]);
        $redirects = GroupBasketRedirectModel::get(['select' => [1], 'where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);

        $allowedCount = count($children) + count($documents) + count($users) + count($templates) + count($instances) + count($redirects);
        if ($allowedCount > 0) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity is still used']);
        }

        $entities = [];
        if (!empty($entity['business_id'])) {
            $control = AnnuaryController::deleteEntityToOrganization(['entityId' => $aArgs['id']]);
            if (!empty($control['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
            }
            $entities['deleted'] = $control['deleted'];
        }

        $templateLists = ListTemplateModel::get(['select' => ['id'], 'where' => ['entity_id = ?'], 'data' => [$entity['id']]]);
        if (!empty($templateLists)) {
            foreach ($templateLists as $templateList) {
                ListTemplateModel::delete([
                    'where' => ['id = ?'],
                    'data'  => [$templateList['id']]
                ]);
                ListTemplateItemModel::delete(['where' => ['list_template_id = ?'], 'data' => [$templateList['id']]]);
            }
        }

        GroupModel::update([
            'postSet'   => ['indexation_parameters' => "jsonb_set(indexation_parameters, '{entities}', (indexation_parameters->'entities') - '{$entity['id']}')"],
            'where'     => ["indexation_parameters->'entities' @> ?"],
            'data'      => ['"'.$entity['id'].'"']
        ]);

        EntityModel::delete(['where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);

        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'info'      => _ENTITY_SUPPRESSION . " : {$aArgs['id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entitySuppression',
        ]);

        $entities['entities'] = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        return $response->withJson($entities);
    }

    public function reassignEntity(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_entities', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $dyingEntity = EntityModel::getByEntityId(['entityId' => $aArgs['id'], 'select' => ['id', 'parent_entity_id', 'business_id']]);
        $successorEntity = EntityModel::getByEntityId(['entityId' => $aArgs['newEntityId'], 'select' => [1]]);
        if (empty($dyingEntity) || empty($successorEntity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity does not exist']);
        }
        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($entities as $entity) {
            if (($entity['entity_id'] == $aArgs['id'] && $entity['allowed'] == false) || ($entity['entity_id'] == $aArgs['newEntityId'] && $entity['allowed'] == false)) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        $entities = [];
        if (!empty($dyingEntity['business_id'])) {
            $control = AnnuaryController::deleteEntityToOrganization(['entityId' => $aArgs['id']]);
            if (!empty($control['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
            }
            $entities['deleted'] = $control['deleted'];
        }

        //Documents
        ResModel::update(['set' => ['destination' => $aArgs['newEntityId']], 'where' => ['destination = ?', 'status != ?'], 'data' => [$aArgs['id'], 'DEL']]);

        //Users
        $users = UserEntityModel::get(['select' => ['user_id', 'entity_id', 'primary_entity'], 'where' => ['entity_id = ? OR entity_id = ?'], 'data' => [$aArgs['id'], $aArgs['newEntityId']]]);
        $tmpUsers = [];
        $doubleUsers = [];
        foreach ($users as $user) {
            if (in_array($user['user_id'], $tmpUsers)) {
                $doubleUsers[] = $user['user_id'];
            }
            $tmpUsers[] = $user['user_id'];
        }
        foreach ($users as $user) {
            if (in_array($user['user_id'], $doubleUsers)) {
                if ($user['entity_id'] == $aArgs['id'] && $user['primary_entity'] == 'N') {
                    UserEntityModel::delete(['where' => ['user_id = ?', 'entity_id = ?'], 'data' => [$user['user_id'], $aArgs['id']]]);
                } elseif ($user['entity_id'] == $aArgs['id'] && $user['primary_entity'] == 'Y') {
                    UserEntityModel::delete(['where' => ['user_id = ?', 'entity_id = ?'], 'data' => [$user['user_id'], $aArgs['newEntityId']]]);
                }
            }
        }
        UserEntityModel::update(['set' => ['entity_id' => $aArgs['newEntityId']], 'where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);

        //Entities
        $entities = EntityModel::get(['select' => ['entity_id', 'parent_entity_id'], 'where' => ['parent_entity_id = ?'], 'data' => [$aArgs['id']]]);
        foreach ($entities as $entity) {
            if ($entity['entity_id'] = $aArgs['newEntityId']) {
                EntityModel::update(['set' => ['parent_entity_id' => $dyingEntity['parent_entity_id']], 'where' => ['entity_id = ?'], 'data' => [$aArgs['newEntityId']]]);
            } else {
                EntityModel::update(['set' => ['parent_entity_id' => $aArgs['newEntityId']], 'where' => ['entity_id = ?'], 'data' => [$entity['entity_id']]]);
            }
        }

        //Baskets
        GroupBasketRedirectModel::update(['set' => ['entity_id' => $aArgs['newEntityId']], 'where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);
        //ListInstances
        ListInstanceModel::update(['set' => ['item_id' => $aArgs['newEntityId']], 'where' => ['item_id = ?', 'item_type = ?'], 'data' => [$aArgs['id'], 'entity_id']]);
        //ListTemplates
        $templateLists = ListTemplateModel::get(['select' => ['id'], 'where' => ['entity_id = ?'], 'data' => [$dyingEntity['id']]]);
        if (!empty($templateLists)) {
            foreach ($templateLists as $templateList) {
                ListTemplateModel::delete([
                    'where' => ['id = ?'],
                    'data'  => [$templateList['id']]
                ]);
                ListTemplateItemModel::delete(['where' => ['list_template_id = ?'], 'data' => [$templateList['id']]]);
            }
        }
        //Templates
        TemplateAssociationModel::update(['set' => ['value_field' => $aArgs['newEntityId']], 'where' => ['value_field = ?'], 'data' => [$aArgs['id']]]);
        //GroupIndexing
        GroupModel::update([
            'postSet'   => ['indexation_parameters' => "jsonb_set(indexation_parameters, '{entities}', (indexation_parameters->'entities') - '{$dyingEntity['id']}')"],
            'where'     => ["indexation_parameters->'entities' @> ?"],
            'data'      => ['"'.$dyingEntity['id'].'"']
        ]);


        EntityModel::delete(['where' => ['entity_id = ?'], 'data' => [$aArgs['id']]]);
        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'info'      => _ENTITY_SUPPRESSION . " : {$aArgs['id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entitySuppression',
        ]);

        $entities['entities'] = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        return $response->withJson($entities);
    }

    public function updateStatus(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_entities', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entity = EntityModel::getByEntityId(['entityId' => $aArgs['id'], 'select' => [1]]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $aEntities = EntityModel::getAllowedEntitiesByUserId(['userId' => $GLOBALS['userId']]);
        foreach ($aEntities as $aEntity) {
            if ($aEntity['entity_id'] == $aArgs['id'] && $aEntity['allowed'] == false) {
                return $response->withStatus(403)->withJson(['errors' => 'Entity out of perimeter']);
            }
        }

        $data = $request->getParams();
        $check = Validator::stringType()->notEmpty()->validate($data['method']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if ($data['method'] == 'disable') {
            $status = 'N';
        } else {
            $status = 'Y';
        }
        $fatherAndSons = EntityModel::getEntityChildren(['entityId' => $aArgs['id']]);

        EntityModel::update(['set' => ['enabled' => $status], 'where' => ['entity_id in (?)'], 'data' => [$fatherAndSons]]);
        HistoryController::add([
            'tableName' => 'entities',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _ENTITY_MODIFICATION . " : {$aArgs['id']}",
            'moduleId'  => 'entity',
            'eventId'   => 'entityModification',
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function getUsersById(Request $request, Response $response, array $aArgs)
    {
        $entity = EntityModel::getById(['id' => $aArgs['id'], 'select' => ['entity_id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $users = UserEntityModel::getWithUsers([
            'select'    => ['DISTINCT users.id', 'users.user_id', 'firstname', 'lastname'],
            'where'     => ['users_entities.entity_id = ?', 'status not in (?)'],
            'data'      => [$entity['entity_id'], ['DEL', 'ABS']],
            'orderBy'   => ['lastname', 'firstname']
        ]);

        foreach ($users as $key => $user) {
            $users[$key]['labelToDisplay'] = "{$user['firstname']} {$user['lastname']}";
            $users[$key]['descriptionToDisplay'] = UserModel::getPrimaryEntityByUserId(['userId' => $user['user_id']])['entity_label'];
        }

        return $response->withJson(['users' => $users]);
    }

    public function getTypes(Request $request, Response $response)
    {
        return $response->withJson(['types' => EntityModel::getTypes()]);
    }
}
