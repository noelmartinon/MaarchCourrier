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
use CustomField\models\CustomFieldModel;
use Doctype\models\DoctypeModel;
use Entity\models\EntityModel;
use Folder\models\ResourceFolderModel;
use Priority\models\PriorityModel;
use RegisteredMail\models\RegisteredMailModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\AutoCompleteController;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\ValidatorModel;
use Status\models\StatusModel;
use Tag\models\ResourceTagModel;
use User\controllers\UserController;
use User\models\UserModel;

class SearchController
{
    public function get(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $userdataClause = SearchController::getUserDataClause(['userId' => $GLOBALS['id'], 'login' => $GLOBALS['login']]);
        $searchWhere = $userdataClause['searchWhere'];
        $searchData = $userdataClause['searchData'];


        //TODO meta champs
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
        //TODO à garder ?
//        if (!empty($queryParams['contactField'])) {
//            $fields = ['company', 'firstname', 'lastname'];
//            $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => $fields]);
//            $requestData = AutoCompleteController::getDataForRequest([
//                'search'        => $queryParams['contactField'],
//                'fields'        => $fields,
//                'where'         => ['type = ?'],
//                'data'          => ['contact'],
//                'fieldsNumber'  => 3
//            ]);
//
//            $contactsMatch = DatabaseModel::select([
//                'select'    => ['res_id'],
//                'table'     => ['resource_contacts', 'contacts'],
//                'left_join' => ['resource_contacts.item_id = contacts.id'],
//                'where'     => $requestData['where'],
//                'data'      => $requestData['data']
//            ]);
//            if (empty($contactsMatch)) {
//                return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
//            }
//            $contactsMatch = array_column($contactsMatch, 'res_id');
//            $searchWhere[] = 'res_id in (?)';
//            $searchData[] = $contactsMatch;
//        }
        $searchClause = SearchController::getMainFieldsClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData = $searchClause['searchData'];

        $searchClause = SearchController::getCustomFieldsClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData = $searchClause['searchData'];

        $searchClause = SearchController::getRegisteredMailsClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData = $searchClause['searchData'];

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

    private static function getUserDataClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId', 'login']);
        ValidatorModel::intVal($args, ['userId']);
        ValidatorModel::stringType($args, ['login']);

        if (UserController::isRoot(['id' => $args['userId']])) {
            $whereClause = '1=?';
            $dataClause = [1];
        } else {
            $entities = UserModel::getEntitiesById(['id' => $args['userId'], 'select' => ['entities.id']]);
            $entities = array_column($entities, 'id');
            $entities = empty($entities) ? [0] : $entities;

            $foldersClause = 'res_id in (select res_id from folders LEFT JOIN entities_folders ON folders.id = entities_folders.folder_id LEFT JOIN resources_folders ON folders.id = resources_folders.folder_id ';
            $foldersClause .= 'WHERE entities_folders.entity_id in (?) OR folders.user_id = ?)';

            $whereClause = "(res_id in (select res_id from users_followed_resources where user_id = ?)) OR ({$foldersClause})";
            $dataClause = [$args['userId'], $entities, $args['userId']];

            $groups = UserModel::getGroupsByLogin(['login' => $args['login'], 'select' => ['where_clause']]);
            $groupsClause = '';
            foreach ($groups as $key => $group) {
                if (!empty($group['where_clause'])) {
                    $groupClause = PreparedClauseController::getPreparedClause(['clause' => $group['where_clause'], 'login' => $args['login']]);
                    if ($key > 0) {
                        $groupsClause .= ' or ';
                    }
                    $groupsClause .= "({$groupClause})";
                }
            }
            if (!empty($groupsClause)) {
                $whereClause .= " OR ({$groupsClause})";
            }

            $baskets = BasketModel::getBasketsByLogin(['login' => $args['login']]);
            $basketsClause = '';
            foreach ($baskets as $basket) {
                if (!empty($basket['basket_clause'])) {
                    $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $args['login']]);
                    if (!empty($basketsClause)) {
                        $basketsClause .= ' or ';
                    }
                    $basketsClause .= "({$basketClause})";
                }
            }
            $assignedBaskets = RedirectBasketModel::getAssignedBasketsByUserId(['userId' => $args['userId']]);
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

        return ['searchWhere' => ["({$whereClause})"], 'searchData' => $dataClause];
    }

    private static function getMainFieldsClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['body', 'searchWhere', 'searchData']);
        ValidatorModel::arrayType($args, ['body', 'searchWhere', 'searchData']);

        $body = $args['body'];

        if (!empty($body['subject']) && !empty($body['subject']['values']) && is_string($body['subject']['values'])) {
            if ($body['subject']['values'][0] == '"' && $body['subject']['values'][strlen($body['subject']['values']) - 1] == '"') {
                $args['searchWhere'][] = "(subject = ? OR res_id in (select res_id_master from res_attachments where title = ?))";
                $subject = trim($body['subject']['values'], '"');
                $args['searchData'][] = $subject;
                $args['searchData'][] = $subject;
            } else {
                $fields = ['subject'];
                $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => $fields]);
                $requestData = AutoCompleteController::getDataForRequest([
                    'search'        => $body['subject']['values'],
                    'fields'        => $fields,
                    'where'         => [],
                    'data'          => [],
                    'fieldsNumber'  => 1
                ]);
                $subjectGlue = implode(' AND ',$requestData['where']);
                $subjectGlue = "(($subjectGlue) OR res_id in (select res_id_master from res_attachments where title ilike ?))";
                $args['searchWhere'][] = $subjectGlue;
                $args['searchData'] = array_merge($args['searchData'], $requestData['data']);
                $args['searchData'][] = "%{$body['subject']['values']}%";
            }
        }
        if (!empty($body['chrono']) && !empty($body['chrono']['values']) && is_string($body['chrono']['values'])) {
            $args['searchWhere'][] = '(alt_identifier ilike ? OR res_id in (select res_id_master from res_attachments where identifier ilike ?))';
            $args['searchData'][] = "%{$body['chrono']['values']}%";
            $args['searchData'][] = "%{$body['chrono']['values']}%";
        }
        if (!empty($body['resId']) && !empty($body['resId']['values']) && is_array($body['resId']['values'])) {
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $body['resId']['values'];
        }
        if (!empty($body['doctype']) && !empty($body['doctype']['values']) && is_array($body['doctype']['values'])) {
            $args['searchWhere'][] = 'type_id in (?)';
            $args['searchData'][] = $body['doctype']['values'];
        }
        if (!empty($body['priority']) && !empty($body['priority']['values']) && is_array($body['priority']['values'])) {
            if (in_array(null, $body['priority']['values'])) {
                $args['searchWhere'][] = '(priority in (?) OR priority is NULL)';
            } else {
                $args['searchWhere'][] = 'priority in (?)';
            }
            $args['searchData'][] = $body['priority']['values'];
        }
        if (!empty($body['confidentiality']) && is_bool($body['confidentiality']['values'])) {
            $args['searchWhere'][] = 'confidentiality = ?';
            $args['searchData'][] = empty($body['confidentiality']['values']) ? 'N' : 'Y';
        }
        if (!empty($body['initiator']) && !empty($body['initiator']['values']) && is_array($body['initiator']['values'])) {
            if (in_array(null, $body['initiator']['values'])) {
                $args['searchWhere'][] = '(initiator in (?) OR priority is NULL)';
            } else {
                $args['searchWhere'][] = 'initiator in (?)';
            }
            $args['searchData'][] = $body['initiator']['values'];
        }
        if (!empty($body['destination']) && !empty($body['destination']['values']) && is_array($body['destination']['values'])) {
            if (in_array(null, $body['destination']['values'])) {
                $args['searchWhere'][] = '(destination in (?) OR priority is NULL)';
            } else {
                $args['searchWhere'][] = 'destination in (?)';
            }
            $args['searchData'][] = $body['destination']['values'];
        }
        if (!empty($body['documentDate']) && !empty($body['documentDate']['values']) && is_array($body['documentDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['documentDate']['values']['start'])) {
                $args['searchWhere'][] = 'doc_date >= ?';
                $args['searchData'][] = $body['documentDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['documentDate']['values']['end'])) {
                $args['searchWhere'][] = 'doc_date <= ?';
                $args['searchData'][] = $body['documentDate']['values']['end'];
            }
        }
        if (!empty($body['arrivalDate']) && !empty($body['arrivalDate']['values']) && is_array($body['arrivalDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['arrivalDate']['values']['start'])) {
                $args['searchWhere'][] = 'admission_date >= ?';
                $args['searchData'][] = $body['arrivalDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['arrivalDate']['values']['end'])) {
                $args['searchWhere'][] = 'admission_date <= ?';
                $args['searchData'][] = $body['arrivalDate']['values']['end'];
            }
        }
        if (!empty($body['departureDate']) && !empty($body['departureDate']['values']) && is_array($body['departureDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['departureDate']['values']['start'])) {
                $args['searchWhere'][] = 'departure_date >= ?';
                $args['searchData'][] = $body['departureDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['departureDate']['values']['end'])) {
                $args['searchWhere'][] = 'departure_date <= ?';
                $args['searchData'][] = $body['departureDate']['values']['end'];
            }
        }
        if (!empty($body['processLimitDate']) && !empty($body['processLimitDate']['values']) && is_array($body['processLimitDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['processLimitDate']['values']['start'])) {
                $args['searchWhere'][] = 'process_limit_date >= ?';
                $args['searchData'][] = $body['processLimitDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['processLimitDate']['values']['end'])) {
                $args['searchWhere'][] = 'process_limit_date <= ?';
                $args['searchData'][] = $body['processLimitDate']['values']['end'];
            }
        }

        if (!empty($body['senders']) && is_array($body['senders']['values']) && !empty($body['senders']['values'])) {
            $where = '';
            $data = [];
            foreach ($body['senders']['values'] as $value) {
                if (!empty($where)) {
                    $where .= ' OR ';
                }
                $where .= '(item_id = ? AND type = ?)';
                $data[] = $value['id'];
                $data[] = $value['type'];
            }
            $data[] = 'sender';
            $sendersMatch = ResourceContactModel::get([
                'select'    => ['res_id'],
                'where'     => ["({$where})", 'mode = ?'],
                'data'      => $data
            ]);
            if (empty($sendersMatch)) {
                return null;
            }
            $sendersMatch = array_column($sendersMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $sendersMatch;
        }
        if (!empty($body['recipients']) && is_array($body['recipients']['values']) && !empty($body['recipients']['values'])) {
            $where = '';
            $data = [];
            foreach ($body['recipients']['values'] as $value) {
                if (!empty($where)) {
                    $where .= ' OR ';
                }
                $where .= '(item_id = ? AND type = ?)';
                $data[] = $value['id'];
                $data[] = $value['type'];
            }
            $data[] = 'recipient';
            $recipientsMatch = ResourceContactModel::get([
                'select'    => ['res_id'],
                'where'     => ["({$where})", 'mode = ?'],
                'data'      => $data
            ]);
            if (empty($recipientsMatch)) {
                return null;
            }
            $recipientsMatch = array_column($recipientsMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $recipientsMatch;
        }
        if (!empty($body['tags']) && is_array($body['tags']['values']) && !empty($body['tags']['values'])) {
            if (!(in_array(null, $body['tags']['values']) && count($body['tags']['values']) === 1)) {
                $tagsMatch = ResourceTagModel::get([
                    'select'    => ['res_id'],
                    'where'     => ['tag_id in (?)'],
                    'data'      => [$body['tags']['values']]
                ]);
            }
            if (empty($tagsMatch) && !in_array(null, $body['tags']['values'])) {
                return null;
            }
            if (empty($tagsMatch)) {
                $args['searchWhere'][] = 'res_id not in (select distinct res_id from resources_tags)';
            } else {
                $args['searchWhere'][] = '(res_id in (?) OR res_id not in (select distinct res_id from resources_tags))';
                $tagsMatch = array_column($tagsMatch, 'res_id');
                $args['searchData'][] = $tagsMatch;
            }
        }
        if (!empty($body['folders']) && is_array($body['folders']['values']) && !empty($body['folders']['values'])) {
            if (!(in_array(null, $body['folders']['values']) && count($body['folders']['values']) === 1)) {
                $foldersMatch = ResourceFolderModel::get([
                    'select'    => ['res_id'],
                    'where'     => ['folder_id in (?)'],
                    'data'      => [$body['folders']['values']]
                ]);
            }
            if (empty($foldersMatch) && !in_array(null, $body['folders']['values'])) {
                return null;
            }
            if (empty($foldersMatch)) {
                $args['searchWhere'][] = 'res_id not in (select distinct res_id from resources_folders)';
            } else {
                $args['searchWhere'][] = '(res_id in (?) OR res_id not in (select distinct res_id from resources_folders))';
                $foldersMatch = array_column($foldersMatch, 'res_id');
                $args['searchData'][] = $foldersMatch;
            }
        }

        return ['searchWhere' => $args['searchWhere'], 'searchData' => $args['searchData']];
    }

    private static function getCustomFieldsClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['body', 'searchWhere', 'searchData']);
        ValidatorModel::arrayType($args, ['body', 'searchWhere', 'searchData']);

        $body = $args['body'];

        foreach ($body as $key => $value) {
            if (strpos($key, 'indexingCustomField_') !== false) {
                $customFieldId = substr($key, 20);
                $customField = CustomFieldModel::getById(['select' => ['type'], 'id' => $customFieldId]);
                if (empty($customField)) {
                    continue;
                }
                if ($customField['type'] == 'string') {
                    if (!empty($value) && !empty($value['values']) && is_string($value['values'])) {
                        if ($value['values'][0] == '"' && $value['values'][strlen($value['values']) - 1] == '"') {
                            $args['searchWhere'][] = "custom_fields->>'{$customFieldId}' = ?";
                            $subject = trim($value['values'], '"');
                            $args['searchData'][] = $subject;
                        } else {
                            $fields = ["custom_fields->>'{$customFieldId}'"];
                            $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => $fields]);
                            $requestData = AutoCompleteController::getDataForRequest([
                                'search'        => $value['values'],
                                'fields'        => $fields,
                                'where'         => [],
                                'data'          => [],
                                'fieldsNumber'  => 1
                            ]);
                            $args['searchWhere'] = array_merge($args['searchWhere'], $requestData['where']);
                            $args['searchData'] = array_merge($args['searchData'], $requestData['data']);
                        }
                    }
                } elseif ($customField['type'] == 'integer') {
                    if (!empty($value) && !empty($value['values']) && is_numeric($value['values'])) {
                        $args['searchWhere'][] = "custom_fields->>'{$customFieldId}' = ?";
                        $args['searchData'][] = $value['values'];
                    }
                } elseif ($customField['type'] == 'radio' || $customField['type'] == 'select') {
                    if (!empty($value) && !empty($value['values']) && is_array($value['values'])) {
                        if (in_array(null, $value['values'])) {
                            $args['searchWhere'][] = "(custom_fields->>'{$customFieldId}' in (?) OR custom_fields->>'{$customFieldId}' is NULL)";
                        } else {
                            $args['searchWhere'][] = "custom_fields->>'{$customFieldId}' in (?)";
                        }
                        $args['searchData'][] = $value['values'];
                    }
                } elseif ($customField['type'] == 'checkbox') {
                    if (!empty($value) && !empty($value['values']) && is_array($value['values'])) {
                        $where = '';
                        foreach ($value['values'] as $item) {
                            if (!empty($where)) {
                                $where .= ' OR ';
                            }
                            $where .= "custom_fields->'{$customFieldId}' @> ?";
                            $args['searchData'][] = "\"{$item}\"";
                        }

                        $args['searchWhere'][] = $where;
                    }
                } elseif ($customField['type'] == 'date') {
                    if (Validator::date()->notEmpty()->validate($value['values']['start'])) {
                        $args['searchWhere'][] = "(custom_fields->>'{$customFieldId}')::timestamp >= ?";
                        $args['searchData'][] = $value['values']['start'];
                    }
                    if (Validator::date()->notEmpty()->validate($value['values']['end'])) {
                        $args['searchWhere'][] = "(custom_fields->>'{$customFieldId}')::timestamp <= ?";
                        $args['searchData'][] = $value['values']['end'];
                    }
                }
            }
        }

        return ['searchWhere' => $args['searchWhere'], 'searchData' => $args['searchData']];
    }

    private static function getRegisteredMailsClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['body', 'searchWhere', 'searchData']);
        ValidatorModel::arrayType($args, ['body', 'searchWhere', 'searchData']);

        $body = $args['body'];

        if (!empty($body['registeredMail_type']) && is_array($body['registeredMail_type']['values']) && !empty($body['registeredMail_type']['values'])) {
            $registeredMailsMatch = RegisteredMailModel::get([
                'select'    => ['res_id'],
                'where'     => ['type in (?)'],
                'data'      => [$body['registeredMail_type']['values']]
            ]);
            if (empty($registeredMailsMatch)) {
                return null;
            }
            $registeredMailsMatch = array_column($registeredMailsMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $registeredMailsMatch;
        }
        if (!empty($body['registeredMail_issuingSite']) && is_array($body['registeredMail_issuingSite']['values']) && !empty($body['registeredMail_issuingSite']['values'])) {
            $registeredMailsMatch = RegisteredMailModel::get([
                'select'    => ['res_id'],
                'where'     => ['issuing_site in (?)'],
                'data'      => [$body['registeredMail_issuingSite']['values']]
            ]);
            if (empty($registeredMailsMatch)) {
                return null;
            }
            $registeredMailsMatch = array_column($registeredMailsMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $registeredMailsMatch;
        }
        if (!empty($body['registeredMail_issuingSite']) && is_array($body['registeredMail_issuingSite']['values']) && !empty($body['registeredMail_issuingSite']['values'])) {
            $registeredMailsMatch = RegisteredMailModel::get([
                'select'    => ['res_id'],
                'where'     => ['issuing_site in (?)'],
                'data'      => [$body['registeredMail_issuingSite']['values']]
            ]);
            if (empty($registeredMailsMatch)) {
                return null;
            }
            $registeredMailsMatch = array_column($registeredMailsMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $registeredMailsMatch;
        }
        if (!empty($body['registeredMail_warranty']) && is_array($body['registeredMail_warranty']['values']) && !empty($body['registeredMail_warranty']['values'])) {
            $registeredMailsMatch = RegisteredMailModel::get([
                'select'    => ['res_id'],
                'where'     => ['warranty in (?)'],
                'data'      => [$body['registeredMail_warranty']['values']]
            ]);
            if (empty($registeredMailsMatch)) {
                return null;
            }
            $registeredMailsMatch = array_column($registeredMailsMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $registeredMailsMatch;
        }
        if (!empty($body['registeredMail_letter']) && is_bool($body['registeredMail_letter']['values'])) {
            $registeredMailsMatch = RegisteredMailModel::get([
                'select'    => ['res_id'],
                'where'     => ['letter = ?'],
                'data'      => [empty($body['registeredMail_letter']['values']) ? 'false' : 'true']
            ]);
            if (empty($registeredMailsMatch)) {
                return null;
            }
            $registeredMailsMatch = array_column($registeredMailsMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $registeredMailsMatch;
        }

        return ['searchWhere' => $args['searchWhere'], 'searchData' => $args['searchData']];
    }
}
