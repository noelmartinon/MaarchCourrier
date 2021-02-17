<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Group Controller
 * @author dev@maarch.org
 */

namespace Contact\controllers;

use Contact\models\ContactGroupListModel;
use Contact\models\ContactGroupModel;
use Contact\models\ContactModel;
use Entity\models\EntityModel;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\AutoCompleteController;
use User\models\UserModel;

class ContactGroupController
{
    public function get(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $where = [];
        $data = [];
        if (empty($queryParams['profile']) && PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            $where[] = '1=1';
        } else {
            $userEntities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['entities.id']]);

            $entitiesId = [];
            foreach ($userEntities as $userEntity) {
                $entitiesId[] = (string)$userEntity['id'];
            }
            $where[] = 'owner = ? OR entities @> ?';
            $data[] = $GLOBALS['id'];
            $data[] = json_encode($entitiesId);
        }
        $contactsGroups = ContactGroupModel::get(['where' => $where, 'data' => $data]);
        foreach ($contactsGroups as $key => $contactsGroup) {
            $correspondents = ContactGroupListModel::get(['select' => ['COUNT(1)'], 'where' => ['contacts_groups_id = ?'], 'data' => [$contactsGroup['id']]]);

            $contactsGroups[$key]['labelledOwner']      = UserModel::getLabelledUserById(['id' => $contactsGroup['owner']]);
            $contactsGroups[$key]['entities']           = (array)json_decode($contactsGroup['entities'], true);
            $contactsGroups[$key]['nbCorrespondents']   = $correspondents[0]['count'];
        }
        
        return $response->withJson(['contactsGroups' => $contactsGroups]);
    }

    public function getById(Request $request, Response $response, array $args)
    {
        if (!ContactGroupController::hasRightById(['id' => $args['id'], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Contacts group out of perimeter']);
        }

        $contactsGroup = ContactGroupModel::getById(['id' => $args['id']]);

        $contactsGroup['labelledOwner'] = UserModel::getLabelledUserById(['id' => $contactsGroup['owner']]);
        $contactsGroup['entities']      = (array)json_decode($contactsGroup['entities'], true);

        $correspondents = ContactGroupListModel::get(['select' => ['COUNT(1)'], 'where' => ['contacts_groups_id = ?'], 'data' => [$args['id']]]);
        $contactsGroup['nbCorrespondents']  = $correspondents[0]['count'];

        $queryParams = $request->getQueryParams();

        $hasPrivilege = false;
        if (empty($queryParams['profile']) && PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            $hasPrivilege = true;
        }

        $userEntities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['entities.id']]);
        $userEntities = array_column($userEntities, 'id');

        $allEntities = EntityModel::get([
            'select'    => ['e1.id', 'e1.entity_id', 'e1.entity_label', 'e2.id as parent_id'],
            'table'     => ['entities e1', 'entities e2'],
            'left_join' => ['e1.parent_entity_id = e2.entity_id'],
            'where'     => ['e1.enabled = ?'],
            'data'      => ['Y']
        ]);

        foreach ($allEntities as $key => $value) {
            $allEntities[$key]['id'] = (string)$value['id'];
            if (empty($value['parent_id'])) {
                $allEntities[$key]['parent'] = '#';
                $allEntities[$key]['icon']   = "fa fa-building";
            } else {
                $allEntities[$key]['parent'] = (string)$value['parent_id'];
                $allEntities[$key]['icon']   = "fa fa-sitemap";
            }

            $allEntities[$key]['state']['opened']   = true;
            $allEntities[$key]['state']['disabled'] = false;
            if (!$hasPrivilege && !in_array($value['id'], $userEntities)) {
                $allEntities[$key]['state']['disabled'] = true;
            } elseif (in_array($value['id'], $contactsGroup['entities'])) {
                $allEntities[$key]['state']['selected'] = true;
            }

            $allEntities[$key]['text'] = $value['entity_label'];
        }

        return $response->withJson(['contactsGroup' => $contactsGroup, 'entities' => $allEntities]);
    }

    public function create(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['label'] ?? null)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['description'] ?? null)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body description is empty or not a string']);
        }

        $existingGroup = ContactGroupModel::get(['select' => [1], 'where' => ['label = ?', 'owner = ?'], 'data' => [$body['label'], $GLOBALS['id']]]);
        if (!empty($existingGroup)) {
            return $response->withStatus(400)->withJson(['errors' => _CONTACTS_GROUP_LABEL_ALREADY_EXISTS]);
        }

        if (!empty($body['entities']) && !PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            $userEntities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['entities.id']]);
            $userEntities = array_column($userEntities, 'id');
            if (!empty(array_diff($body['entities'], $userEntities))) {
                return $response->withStatus(400)->withJson(['errors' => 'Body entities has entities out of perimeter']);
            }
        }

        $body['entities']   = !empty($body['entities']) ? json_encode($body['entities']) : '{}';
        $body['owner']      = $GLOBALS['id'];

        $id = ContactGroupModel::create($body);

        HistoryController::add([
            'tableName' => 'contacts_groups',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'info'      => _CONTACTS_GROUP_ADDED . " : {$body['label']}",
            'moduleId'  => 'contact',
            'eventId'   => 'contactsGroupCreation',
        ]);

        return $response->withJson(['id' => $id]);
    }

    public function update(Request $request, Response $response, array $args)
    {
        if (!ContactGroupController::hasRightById(['id' => $args['id'], 'userId' => $GLOBALS['id'], 'canUpdate' => true])) {
            return $response->withStatus(403)->withJson(['errors' => 'Contacts group out of perimeter']);
        }

        $body = $request->getParsedBody();
        if (!Validator::stringType()->notEmpty()->validate($body['label'] ?? null)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['description'] ?? null)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body description is empty or not a string']);
        }

        $existingGroup = ContactGroupModel::get(['select' => [1], 'where' => ['label = ?', 'owner = ?', 'id != ?'], 'data' => [$body['label'], $GLOBALS['id'], $args['id']]]);
        if (!empty($existingGroup)) {
            return $response->withStatus(400)->withJson(['errors' => _CONTACTS_GROUP_LABEL_ALREADY_EXISTS]);
        }

        if (!empty($body['entities']) && !PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            $userEntities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['entities.id']]);
            $userEntities = array_column($userEntities, 'id');
            if (!empty(array_diff($body['entities'], $userEntities))) {
                return $response->withStatus(400)->withJson(['errors' => 'Body entities has entities out of perimeter']);
            }
        }

        $body['entities'] = !empty($body['entities']) ? json_encode($body['entities']) : '{}';

        ContactGroupModel::update([
            'set'   => [
                'label'         => $body['label'],
                'description'   => $body['description'],
                'entities'      => $body['entities']
            ],
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        HistoryController::add([
            'tableName' => 'contacts_groups',
            'recordId'  => $args['id'],
            'eventType' => 'UP',
            'info'      => _CONTACTS_GROUP_UPDATED . " : {$body['label']}",
            'moduleId'  => 'contact',
            'eventId'   => 'contactsGroupModification',
        ]);

        return $response->withStatus(204);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        if (!ContactGroupController::hasRightById(['id' => $args['id'], 'userId' => $GLOBALS['id'], 'canUpdate' => true])) {
            return $response->withStatus(403)->withJson(['errors' => 'Contacts group out of perimeter']);
        }

        ContactGroupModel::delete(['where' => ['id = ?'], 'data' => [$args['id']]]);
        ContactGroupListModel::delete(['where' => ['contacts_groups_id = ?'], 'data' => [$args['id']]]);

        HistoryController::add([
            'tableName' => 'contacts_groups',
            'recordId'  => $args['id'],
            'eventType' => 'DEL',
            'info'      => _CONTACTS_GROUP_DELETED . " : {$args['id']}",
            'moduleId'  => 'contact',
            'eventId'   => 'contactsGroupSuppression',
        ]);

        return $response->withStatus(204);
    }

    public function duplicate(Request $request, Response $response, array $args)
    {
        if (!ContactGroupController::hasRightById(['id' => $args['id'], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Contacts group out of perimeter']);
        }

        $contactGroup = ContactGroupModel::getById(['select' => ['*'], 'id' => $args['id']]);

        $label = $contactGroup['label'];
        $existingCopy = ContactGroupModel::get(['select' => ['label'], 'where' => ['label like ?'], 'data' => ["{$contactGroup['label']} copy(%"], 'orderBy' => ['id DESC'], 'limit' => 1]);
        if (!empty($existingCopy[0])) {
            $pos = strpos($existingCopy[0]['label'], 'copy(');
            $number = (int)$existingCopy[0]['label'][$pos + 5];
            ++$number;
            $label .= " copy({$number})";
        } else {
            $label .= ' copy(1)';
        }

        $id = ContactGroupModel::create([
            'label'         => $label,
            'description'   => $contactGroup['description'],
            'entities'      => $contactGroup['entities'],
            'owner'         => $GLOBALS['id']
        ]);

        $correspondents = ContactGroupListModel::get(['select' => ['*'], 'where' => ['contacts_groups_id = ?'], 'data' => [$args['id']]]);
        foreach ($correspondents as $correspondent) {
            ContactGroupListModel::create([
                'contacts_groups_id'    => $id,
                'correspondent_id'      => $correspondent['correspondent_id'],
                'correspondent_type'    => $correspondent['correspondent_type']
            ]);
        }

        HistoryController::add([
            'tableName' => 'contacts_groups',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'info'      => _CONTACTS_GROUP_ADDED . " : {$label}",
            'moduleId'  => 'contact',
            'eventId'   => 'contactsGroupCreation',
        ]);

        return $response->withJson(['id' => $id]);
    }

    public function getCorrespondents(Request $request, Response $response, array $args)
    {
        if (!ContactGroupController::hasRightById(['id' => $args['id'], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Contacts group out of perimeter']);
        }

        $queryParams = $request->getQueryParams();

        $queryParams['offset'] = (empty($queryParams['offset']) || !is_numeric($queryParams['offset']) ? 0 : (int)$queryParams['offset']);
        $queryParams['limit'] = (empty($queryParams['limit']) || !is_numeric($queryParams['limit']) ? 25 : (int)$queryParams['limit']);

        $where = ['contacts_groups_id = ?'];
        $data = [$args['id']];

        if (!empty($queryParams['types']) && is_array($queryParams['types'])) {
            $where[] = 'correspondent_type in (?)';
            $data[] = $queryParams['types'];
        }

        $order   = !in_array($queryParams['order'], ['ASC', 'DESC']) ? '' : $queryParams['order'];
        $orderBy = !in_array($queryParams['orderBy'], ['type', 'name']) ? ['name'] : [$queryParams['orderBy']];
        $orderBy = str_replace(['type', 'name'], ["correspondent_type {$order}", "contacts.lastname {$order}, users.lastname {$order}, entities.entity_label {$order}"], $orderBy);

        if (!empty($queryParams['search'])) {
            $fields = [
                'contacts.firstname', 'contacts.lastname', 'users.firstname', 'users.lastname', 'entities.entity_label',
                'contacts.address_number', 'contacts.address_street', 'contacts.address_town', 'contacts.address_postcode',
                'entities.address_number', 'entities.address_street', 'entities.address_town', 'entities.address_postcode'
            ];

            $fields = AutoCompleteController::getInsensitiveFieldsForRequest(['fields' => $fields]);
            $requestData = AutoCompleteController::getDataForRequest([
                'search'        => trim($queryParams['search']),
                'fields'        => $fields,
                'where'         => $where,
                'data'          => $data,
                'fieldsNumber'  => 13,
            ]);

            $rawCorrespondents = ContactGroupListModel::getWithCorrespondents([
                'select'    => ['correspondent_id', 'correspondent_type', 'count(1) OVER()'],
                'where'     => $requestData['where'],
                'data'      => $requestData['data'],
                'offset'    => $queryParams['offset'],
                'limit'     => $queryParams['limit'],
                'orderBy'   => $orderBy
            ]);

        } else {
            $rawCorrespondents = ContactGroupListModel::getWithCorrespondents([
                'select'    => ['correspondent_id', 'correspondent_type', 'count(1) OVER()'],
                'where'     => $where,
                'data'      => $data,
                'offset'    => $queryParams['offset'],
                'limit'     => $queryParams['limit'],
                'orderBy'   => $orderBy
            ]);
        }

        $correspondents = [];
        foreach ($rawCorrespondents as $correspondent) {
            if ($correspondent['correspondent_type'] == 'contact') {
                $contact = ContactModel::getById([
                    'select'    => ['id', 'firstname', 'lastname', 'email', 'company', 'address_number', 'address_street', 'address_town', 'address_postcode', 'enabled'],
                    'id'        => $correspondent['correspondent_id']
                ]);
                $contactToDisplay = ContactController::getFormattedContactWithAddress(['contact' => $contact]);
                $correspondents[] = [
                    'id'        => $correspondent['correspondent_id'],
                    'type'      => $correspondent['correspondent_type'],
                    'name'      => $contactToDisplay['contact']['contact'],
                    'address'   => $contactToDisplay['contact']['address']
                ];
            } elseif ($correspondent['correspondent_type'] == 'user') {
                $correspondents[] = [
                    'id'    => $correspondent['correspondent_id'],
                    'type'  => $correspondent['correspondent_type'],
                    'name'  => UserModel::getLabelledUserById(['id' => $correspondent['correspondent_id']])
                ];
            } elseif ($correspondent['correspondent_type'] == 'entity') {
                $entity = EntityModel::getById(['id' => $correspondent['correspondent_id'], 'select' => ['*']]);
                $contactToDisplay = ContactController::getFormattedContactWithAddress(['contact' => $entity]);
                $correspondents[] = [
                    'id'        => $correspondent['correspondent_id'],
                    'type'      => $correspondent['correspondent_type'],
                    'name'      => $entity['entity_label'],
                    'address'   => $contactToDisplay['contact']['address']
                ];
            }
        }

        return $response->withJson(['correspondents' => $correspondents, 'count' => $rawCorrespondents[0]['count'] ?? 0]);
    }

    public function addCorrespondents(Request $request, Response $response, array $args)
    {
        if (!ContactGroupController::hasRightById(['id' => $args['id'], 'userId' => $GLOBALS['id'], 'canUpdate' => true])) {
            if (!ContactGroupController::hasRightById(['id' => $args['id'], 'userId' => $GLOBALS['id']]) || !PrivilegeController::hasPrivilege(['privilegeId' => 'add_correspondent_in_shared_groups_on_profile', 'userId' => $args['userId']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Contacts group out of perimeter']);
            }
        }

        $body = $request->getParsedBody();

        if (!Validator::arrayType()->notEmpty()->validate($body['correspondents'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body correspondents is empty or not an array']);
        }

        $rawList = ContactGroupListModel::get(['select' => ['correspondent_id', 'correspondent_type'], 'where' => ['contacts_groups_id = ?'], 'data' => [$args['id']]]);
        $correspondents = [
            'contacts'  => [],
            'users'     => [],
            'entities'  => []
        ];
        foreach ($rawList as $value) {
            $correspondents["{$value['correspondent_type']}s"][] = $value['correspondent_id'];
        }

        foreach ($body['correspondents'] as $correspondent) {
            if ($correspondent['type'] == 'contact' && !in_array($correspondent['id'], $correspondents['contacts'])) {
                ContactGroupListModel::create([
                    'contacts_groups_id'    => $args['id'],
                    'correspondent_id'      => $correspondent['id'],
                    'correspondent_type'    => 'contact'
                ]);
            } elseif ($correspondent['type'] == 'user' && !in_array($correspondent['id'], $correspondents['users'])) {
                ContactGroupListModel::create([
                    'contacts_groups_id'    => $args['id'],
                    'correspondent_id'      => $correspondent['id'],
                    'correspondent_type'    => 'user'
                ]);
            } elseif ($correspondent['type'] == 'entity' && !in_array($correspondent['id'], $correspondents['entities'])) {
                ContactGroupListModel::create([
                    'contacts_groups_id'    => $args['id'],
                    'correspondent_id'      => $correspondent['id'],
                    'correspondent_type'    => 'entity'
                ]);
            }
        }

        HistoryController::add([
            'tableName' => 'contacts_groups_lists',
            'recordId'  => $args['id'],
            'eventType' => 'ADD',
            'info'      => _CONTACTS_GROUP_LIST_ADDED ,
            'moduleId'  => 'contact',
            'eventId'   => 'contactsGroupListCreation',
        ]);

        return $response->withStatus(204);
    }

    public function deleteCorrespondents(Request $request, Response $response, array $args)
    {
        if (!ContactGroupController::hasRightById(['id' => $args['id'], 'userId' => $GLOBALS['id'], 'canUpdate' => true])) {
            if (!ContactGroupController::hasRightById(['id' => $args['id'], 'userId' => $GLOBALS['id']]) || !PrivilegeController::hasPrivilege(['privilegeId' => 'add_correspondent_in_shared_groups_on_profile', 'userId' => $args['userId']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Contacts group out of perimeter']);
            }
        }

        $body = $request->getParsedBody();

        if (!Validator::arrayType()->notEmpty()->validate($body['correspondents'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body correspondents is empty or not an array']);
        }

        foreach ($body['correspondents'] as $correspondent) {
            if (!empty($correspondent['id']) && !empty($correspondent['type'])) {
                ContactGroupListModel::delete(['where' => ['contacts_groups_id = ?', 'correspondent_id = ?', 'correspondent_type = ?'], 'data' => [$args['id'], $correspondent['id'], $correspondent['type']]]);
            }
        }

        HistoryController::add([
            'tableName' => 'contacts_groups_lists',
            'recordId'  => $args['id'],
            'eventType' => 'DEL',
            'info'      => _CONTACTS_GROUP_LIST_DELETED,
            'moduleId'  => 'contact',
            'eventId'   => 'contactsGroupListSuppression',
        ]);

        return $response->withStatus(204);
    }

    public function getAllowedEntities(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $hasPrivilege = false;
        if (empty($queryParams['profile']) && PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            $hasPrivilege = true;
        }

        $userEntities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['entities.id']]);
        $userEntities = array_column($userEntities, 'id');

        $allEntities = EntityModel::get([
            'select'    => ['e1.id', 'e1.entity_id', 'e1.entity_label', 'e2.id as parent_id'],
            'table'     => ['entities e1', 'entities e2'],
            'left_join' => ['e1.parent_entity_id = e2.entity_id'],
            'where'     => ['e1.enabled = ?'],
            'data'      => ['Y']
        ]);

        foreach ($allEntities as $key => $value) {
            $allEntities[$key]['id'] = (string)$value['id'];
            if (empty($value['parent_id'])) {
                $allEntities[$key]['parent'] = '#';
                $allEntities[$key]['icon']   = "fa fa-building";
            } else {
                $allEntities[$key]['parent'] = (string)$value['parent_id'];
                $allEntities[$key]['icon']   = "fa fa-sitemap";
            }

            $allEntities[$key]['state']['disabled'] = false;
            $allEntities[$key]['state']['opened']   = true;
            if (!$hasPrivilege && !in_array($value['id'], $userEntities)) {
                $allEntities[$key]['state']['disabled'] = true;
            }

            $allEntities[$key]['text'] = $value['entity_label'];
        }

        return $response->withJson(['entities' => $allEntities]);
    }

    private static function hasRightById(array $args)
    {
        $contactsGroup = ContactGroupModel::getById(['id' => $args['id'], 'select' => ['owner', 'entities']]);
        if (empty($contactsGroup)) {
            return false;
        }

        if (PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $args['userId']])) {
            return true;
        } elseif ($contactsGroup['owner'] == $args['userId']) {
            return true;
        }

        if (!empty($args['canUpdate'])) {
            return false;
        }
        $groupEntities = json_decode($contactsGroup['entities'], true);
        $userEntities = UserModel::getEntitiesById(['id' => $args['userId'], 'select' => ['entities.id']]);
        foreach ($userEntities as $userEntity) {
            if (in_array($userEntity['id'], $groupEntities)) {
                return true;
            }
        }

        return false;
    }
}
