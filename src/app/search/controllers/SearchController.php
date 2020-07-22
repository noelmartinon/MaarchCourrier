<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Search Controller
* @author dev@maarch.org
*/

namespace Search\controllers;

use Attachment\models\AttachmentModel;
use Basket\models\BasketModel;
use Basket\models\RedirectBasketModel;
use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Doctype\models\DoctypeModel;
use Entity\models\EntityModel;
use Priority\models\PriorityModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\AutoCompleteController;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\DatabaseModel;
use Status\models\StatusModel;
use User\controllers\UserController;
use User\models\UserModel;

class SearchController
{
    public static function get(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        if (UserController::isRoot(['id' => $GLOBALS['id']])) {
            $whereClause = '1=1';
            $dataClause = [];
        } else {
            $entities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['entities.id']]);
            $entities = array_column($entities, 'id');
            $entities = empty($entities) ? [0] : $entities;

            $foldersClause = 'res_id in (select res_id from folders LEFT JOIN entities_folders ON folders.id = entities_folders.folder_id LEFT JOIN resources_folders ON folders.id = resources_folders.folder_id ';
            $foldersClause .= 'WHERE entities_folders.entity_id in (?) OR folders.user_id = ?)';

            $whereClause = "(res_id in (select res_id from users_followed_resources where user_id = ?)) OR ({$foldersClause})";
            $dataClause = [$GLOBALS['id'], $entities, $GLOBALS['id']];

            $groups = UserModel::getGroupsByLogin(['login' => $GLOBALS['login'], 'select' => ['where_clause']]);
            $groupsClause = '';
            foreach ($groups as $key => $group) {
                if (!empty($group['where_clause'])) {
                    $groupClause = PreparedClauseController::getPreparedClause(['clause' => $group['where_clause'], 'login' => $GLOBALS['login']]);
                    if ($key > 0) {
                        $groupsClause .= ' or ';
                    }
                    $groupsClause .= "({$groupClause})";
                }
            }
            if (!empty($groupsClause)) {
                $whereClause .= " OR ({$groupsClause})";
            }

            $baskets = BasketModel::getBasketsByLogin(['login' => $GLOBALS['login']]);
            $basketsClause = '';
            foreach ($baskets as $basket) {
                if (!empty($basket['basket_clause'])) {
                    $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $GLOBALS['login']]);
                    if (!empty($basketsClause)) {
                        $basketsClause .= ' or ';
                    }
                    $basketsClause .= "({$basketClause})";
                }
            }
            $assignedBaskets = RedirectBasketModel::getAssignedBasketsByUserId(['userId' => $GLOBALS['id']]);
            foreach ($assignedBaskets as $basket) {
                if (!empty($basket['basket_clause'])) {
                    $basketOwner = UserModel::getById(['id' => $basket['owner_user_id'], 'select' => ['user_id']]);
                    $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $basketOwner['user_id']]);
                    if (!empty($basketsClause)) {
                        $basketsClause .= ' or ';
                    }
                    $basketsClause .= "({$basketClause})";
                }
            }
            if (!empty($basketsClause)) {
                $whereClause .= " OR ({$basketsClause})";
            }
        }


        $searchWhere = ["({$whereClause})"];
        $searchData = $dataClause;

        if (!empty($queryParams['resourceField'])) {
            $fields = ['subject', 'alt_identifier'];
            $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => $fields]);
            $requestData = AutoCompleteController::getDataForRequest([
                'search'        => $queryParams['resourceField'],
                'fields'        => $fields,
                'where'         => [],
                'data'          => [],
                'fieldsNumber'  => 2
            ]);
            $searchWhere = array_merge($searchWhere, $requestData['where']);
            $searchData = array_merge($searchData, $requestData['data']);
        }
        if (!empty($queryParams['contactField'])) {
            $fields = ['company', 'firstname', 'lastname'];
            $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => $fields]);
            $requestData = AutoCompleteController::getDataForRequest([
                'search'        => $queryParams['contactField'],
                'fields'        => $fields,
                'where'         => ['type = ?'],
                'data'          => ['contact'],
                'fieldsNumber'  => 3
            ]);

            $contactsMatch = DatabaseModel::select([
                'select'    => ['res_id'],
                'table'     => ['resource_contacts', 'contacts'],
                'left_join' => ['resource_contacts.item_id = contacts.id'],
                'where'     => $requestData['where'],
                'data'      => $requestData['data']
            ]);
            if (empty($contactsMatch)) {
                return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
            }
            $contactsMatch = array_column($contactsMatch, 'res_id');
            $searchWhere[] = 'res_id in (?)';
            $searchData[] = $contactsMatch;
        }

        $nonSearchableStatuses = StatusModel::get(['select' => ['id'], 'where' => ['can_be_searched = ?'], 'data' => ['N']]);
        if (!empty($nonSearchableStatuses)) {
            $nonSearchableStatuses = array_column($nonSearchableStatuses, 'id');
            $searchWhere[] = 'status not in (?)';
            $searchData[] = $nonSearchableStatuses;
        }

        $limit = 25;
        if (!empty($queryParams['limit']) && is_numeric($queryParams['limit'])) {
            $limit = (int)$queryParams['limit'];
        }
        $offset = 0;
        if (!empty($queryParams['offset']) && is_numeric($queryParams['offset'])) {
            $offset = (int)$queryParams['offset'];
        }
        $order = !in_array($queryParams['order'], ['asc', 'desc']) ? '' : $queryParams['order'];
        $orderBy = str_replace(['chrono', 'typeLabel', 'creationDate', 'category'], ['order_alphanum(alt_identifier)', 'type_label', 'creation_date', 'category_id'], $queryParams['orderBy']);
        $orderBy = !in_array($orderBy, ['order_alphanum(alt_identifier)', 'status', 'subject', 'type_label', 'creation_date', 'category_id']) ? ['creation_date'] : ["{$orderBy} {$order}"];

        $allResources = ResModel::getOnView([
            'select'    => ['res_id as "resId"'],
            'where'     => $searchWhere,
            'data'      => $searchData,
            'orderBy'   => $orderBy
        ]);
        if (empty($allResources[$offset])) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }

        $allResources = array_column($allResources, 'resId');

        $resIds = [];
        $order = 'CASE res_id ';
        for ($i = $offset; $i < ($offset + $limit); $i++) {
            if (empty($allResources[$i])) {
                break;
            }
            $order .= "WHEN {$allResources[$i]} THEN {$i} ";
            $resIds[] = $allResources[$i];
        }
        $order .= 'END';

        $resources = ResModel::get([
            'select'    => [
                'res_id as "resId"', 'category_id as "category"', 'alt_identifier as "chrono"', 'subject', 'barcode', 'filename', 'creation_date as "creationDate"',
                'type_id as "type"', 'priority', 'status', 'dest_user as "destUser"'
            ],
            'where'     => ['res_id in (?)'],
            'data'      => [$resIds],
            'orderBy'   => [$order]
        ]);
        if (empty($resources)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }

        $resourcesIds = array_column($resources, 'resId');
        $attachments = AttachmentModel::get(['select' => ['count(1)', 'res_id_master'], 'where' => ['res_id_master in (?)', 'status not in (?)'], 'data' => [$resourcesIds, ['DEL']], 'groupBy' => ['res_id_master']]);

        $prioritiesIds = array_column($resources, 'priority');
        $priorities = PriorityModel::get(['select' => ['id', 'color'], 'where' => ['id in (?)'], 'data' => [$prioritiesIds]]);

        $statusesIds = array_column($resources, 'status');
        $statuses = StatusModel::get(['select' => ['id', 'label_status', 'img_filename'], 'where' => ['id in (?)'], 'data' => [$statusesIds]]);

        $doctypesIds = array_column($resources, 'type');
        $doctypes = DoctypeModel::get(['select' => ['type_id', 'description'], 'where' => ['type_id in (?)'], 'data' => [$doctypesIds]]);

        $correspondents = ResourceContactModel::get([
            'select'    => ['item_id', 'type', 'mode', 'res_id'],
            'where'     => ['res_id in (?)'],
            'data'      => [$resourcesIds]
        ]);

        foreach ($resources as $key => $resource) {
            if (!empty($resource['priority'])) {
                foreach ($priorities as $priority) {
                    if ($priority['id'] == $resource['priority']) {
                        $resources[$key]['priorityColor'] = $priority['color'];
                        break;
                    }
                }
            }
            $resources[$key]['statusLabel'] = null;
            $resources[$key]['statusImage'] = null;
            if (!empty($resource['status'])) {
                foreach ($statuses as $status) {
                    if ($status['id'] == $resource['status']) {
                        $resources[$key]['statusLabel'] = $status['label_status'];
                        $resources[$key]['statusImage'] = $status['img_filename'];
                        break;
                    }
                }
            }
            foreach ($doctypes as $doctype) {
                if ($doctype['type_id'] == $resource['type']) {
                    $resources[$key]['typeLabel'] = $doctype['description'];
                    break;
                }
            }
            if (!empty($resource['destUser'])) {
                $resources[$key]['destUserLabel'] = UserModel::getLabelledUserById(['id' => $resource['destUser']]);
            }
            $resources[$key]['hasDocument'] = !empty($resource['filename']);

            $resources[$key]['senders'] = [];
            $resources[$key]['recipients'] = [];
            foreach ($correspondents as $correspondent) {
                if ($correspondent['res_id'] == $resource['resId']) {
                    if ($correspondent['type'] == 'contact') {
                        $contactRaw = ContactModel::getById(['select' => ['firstname', 'lastname', 'company'], 'id' => $correspondent['item_id']]);
                        $contactToDisplay = ContactController::getFormattedOnlyContact(['contact' => $contactRaw]);
                        $formattedCorrespondent = $contactToDisplay['contact']['otherInfo'];
                    } elseif ($correspondent['type'] == 'user') {
                        $formattedCorrespondent = UserModel::getLabelledUserById(['id' => $correspondent['item_id']]);
                    } else {
                        $entity = EntityModel::getById(['id' => $correspondent['item_id'], 'select' => ['entity_label']]);
                        $formattedCorrespondent = $entity['entity_label'];
                    }

                    $resources[$key]["{$correspondent['mode']}s"][] = $formattedCorrespondent;
                }
            }

            $resources[$key]['attachments'] = 0;
            foreach ($attachments as $attachment) {
                if ($attachment['res_id_master'] == $resource['resId']) {
                    $resources[$key]['attachments'] = $attachment['count'];
                    break;
                }
            }
        }

        return $response->withJson(['resources' => $resources, 'count' => count($allResources), 'allResources' => $allResources]);
    }
}
