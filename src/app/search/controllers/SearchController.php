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
use Configuration\models\ConfigurationModel;
use Contact\models\ContactModel;
use Convert\controllers\FullTextController;
use CustomField\models\CustomFieldModel;
use Docserver\models\DocserverModel;
use Doctype\models\DoctypeModel;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use Folder\models\FolderModel;
use Folder\models\ResourceFolderModel;
use Note\models\NoteModel;
use Priority\models\PriorityModel;
use RegisteredMail\models\RegisteredMailModel;
use Resource\controllers\ResourceListController;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Resource\models\ResourceListModel;
use Resource\models\UserFollowedResourceModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\AutoCompleteController;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\TextFormatModel;
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
        $searchWhere    = $userdataClause['searchWhere'];
        $searchData     = $userdataClause['searchData'];


        $searchClause = SearchController::getQuickFieldClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData  = $searchClause['searchData'];

        $searchClause = SearchController::getMainFieldsClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData  = $searchClause['searchData'];

        $searchClause = SearchController::getListFieldsClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData  = $searchClause['searchData'];

        $searchClause = SearchController::getCustomFieldsClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData  = $searchClause['searchData'];

        $searchClause = SearchController::getRegisteredMailsClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData  = $searchClause['searchData'];

        $searchClause = SearchController::getFulltextClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData  = $searchClause['searchData'];

        $searchClause = SearchController::getFiltersClause(['body' => $body, 'searchWhere' => $searchWhere, 'searchData' => $searchData]);
        if (empty($searchClause)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }
        $searchWhere = $searchClause['searchWhere'];
        $searchData  = $searchClause['searchData'];

        $nonSearchableStatuses = StatusModel::get(['select' => ['id'], 'where' => ['can_be_searched = ?'], 'data' => ['N']]);
        if (!empty($nonSearchableStatuses)) {
            $nonSearchableStatuses = array_column($nonSearchableStatuses, 'id');
            $searchWhere[] = 'status not in (?)';
            $searchData[] = $nonSearchableStatuses;
        }

        $queryParams = $request->getQueryParams();

        $limit = 25;
        if (!empty($queryParams['limit']) && is_numeric($queryParams['limit'])) {
            $limit = (int)$queryParams['limit'];
        }
        $offset = 0;
        if (!empty($queryParams['offset']) && is_numeric($queryParams['offset'])) {
            $offset = (int)$queryParams['offset'];
        }
        $order   = !in_array($queryParams['orderDir'], ['ASC', 'DESC']) ? '' : $queryParams['orderDir'];
        $orderBy = str_replace(['chrono', 'typeLabel', 'creationDate', 'category', 'destUser', 'processLimitDate', 'entityLabel'], ['order_alphanum(alt_identifier)', 'type_label', 'creation_date', 'category_id', 'dest_user', 'process_limit_date', 'entity_label'], $queryParams['order']);
        $orderBy = !in_array($orderBy, ['order_alphanum(alt_identifier)', 'status', 'subject', 'type_label', 'creation_date', 'category_id', 'dest_user', 'process_limit_date', 'entity_label', 'priority']) ? ['creation_date'] : ["{$orderBy} {$order}"];

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
        $order  = 'CASE res_id ';
        for ($i = $offset; $i < ($offset + $limit); $i++) {
            if (empty($allResources[$i])) {
                break;
            }
            $order .= "WHEN {$allResources[$i]} THEN {$i} ";
            $resIds[] = $allResources[$i];
        }
        $order .= 'END';

        $adminSearch   = ConfigurationModel::getByPrivilege(['privilege' => 'admin_search', 'select' => ['value']]);
        if (empty($adminSearch)) {
            return $response->withStatus(400)->withJson(['errors' => 'no admin_search configuration found', 'lang' => 'noAdminSearchConfiguration']);
        }
        $configuration = json_decode($adminSearch['value'], true);
        $listDisplay   = $configuration['listDisplay']['subInfos'];

        $selectData = ResourceListController::getSelectData(['listDisplay' => $listDisplay]);

        $resources = ResourceListModel::getOnResource([
            'select'    => $selectData['select'],
            'table'     => $selectData['tableFunction'],
            'leftJoin'  => $selectData['leftJoinFunction'],
            'where'     => ['res_letterbox.res_id in (?)'],
            'data'      => [$resIds],
            'orderBy'   => [$order]
        ]);
        if (empty($resources)) {
            return $response->withJson(['resources' => [], 'count' => 0, 'allResources' => []]);
        }

        $excludeAttachmentTypes = ['signed_response'];
        $attachments = AttachmentModel::get([
            'select'    => ['COUNT(res_id)', 'res_id_master'],
            'where'     => ['res_id_master in (?)', 'status not in (?)', 'attachment_type not in (?)', '((status = ? AND typist = ?) OR status != ?)'],
            'data'      => [$resIds, ['DEL', 'OBS'], $excludeAttachmentTypes, 'TMP', $GLOBALS['id'], 'TMP'],
            'groupBy'   => ['res_id_master']
        ]);

        $followedDocuments = UserFollowedResourceModel::get([
            'select' => ['res_id'],
                'where'  => ['user_id = ?'],
                'data'   => [$GLOBALS['id']],
            ]);
    
        $trackedMails = array_column($followedDocuments, 'res_id');

        $formattedResources = ResourceListController::getFormattedResources([
            'resources'     => $resources,
            'userId'        => $GLOBALS['id'],
            'attachments'   => $attachments,
            'checkLocked'   => false,
            'listDisplay'   => $listDisplay,
            'trackedMails'  => $trackedMails
        ]);

        $filters = [];
        if (empty($queryParams['filters'])) {
            $filters = SearchController::getFilters(['body' => $body, 'resources' => $allResources]);
        }

        return $response->withJson([
            'resources'         => $formattedResources,
            'count'             => count($allResources),
            'allResources'      => $allResources,
            'defaultTab'        => $configuration['listEvent']['defaultTab'],
            'displayFolderTags' => in_array('getFolders', array_column($listDisplay, 'value')),
            'templateColumns'   => $configuration['listDisplay']['templateColumns'],
            'filters'           => $filters
        ]);
    }

    public function getConfiguration(Request $request, Response $response)
    {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_search']);
        $configuration = json_decode($configuration['value'], true);

        return $response->withJson(['configuration' => $configuration]);
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

    private static function getQuickFieldClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['searchWhere', 'searchData']);
        ValidatorModel::arrayType($args, ['body', 'searchWhere', 'searchData']);

        $body = $args['body'];

        if (!empty($body['meta']) && !empty($body['meta']['values']) && is_string($body['meta']['values'])) {
            if ($body['meta']['values'][0] == '"' && $body['meta']['values'][strlen($body['meta']['values']) - 1] == '"') {
                $quick = trim($body['meta']['values'], '"');
                $quickWhere = "subject = ? OR res_id in (select res_id_master from res_attachments where title = ?)";
                $quickWhere .= ' OR alt_identifier = ? OR res_id in (select res_id_master from res_attachments where identifier = ?)';
                $quickWhere .= ' OR barcode = ?';
                if (ctype_digit($quick)) {
                    $quickWhere .= ' OR res_id = ?';
                    $args['searchData'][] = $quick;
                }

                $args['searchWhere'][] = '(' . $quickWhere . ')';
                $args['searchData'] = array_merge($args['searchData'], [$quick, $quick, $quick, $quick, $quick]);
            } else {
                $fields = ['subject', 'alt_identifier', 'barcode'];
                $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => $fields]);
                $requestDataDocument = AutoCompleteController::getDataForRequest([
                    'search'        => $body['meta']['values'],
                    'fields'        => $fields,
                    'where'         => [],
                    'data'          => [],
                    'fieldsNumber'  => 3
                ]);

                $fields = ['title', 'identifier'];
                $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => $fields]);
                $requestDataAttachment = AutoCompleteController::getDataForRequest([
                    'search'        => $body['meta']['values'],
                    'fields'        => $fields,
                    'where'         => [],
                    'data'          => [],
                    'fieldsNumber'  => 2
                ]);

                if (!empty($requestDataDocument['where'])) {
                    $whereClause[]      = implode(' OR ', $requestDataDocument['where']);
                    $args['searchData'] = array_merge($args['searchData'], $requestDataDocument['data']);
                }
                if (!empty($requestDataAttachment['where'])) {
                    $whereClause[]      = 'res_id in (select res_id_master from res_attachments where ' . implode(' OR ', $requestDataAttachment['where']) . ')';
                    $args['searchData'] = array_merge($args['searchData'], $requestDataAttachment['data']);
                }

                if (ctype_digit(trim($body['meta']['values']))) {
                    $whereClause[] = 'res_id = ?';
                    $args['searchData'][] = trim($body['meta']['values']);
                }

                if (!empty($whereClause)) {
                    $args['searchWhere'][] = '(' . implode(' OR ', $whereClause) . ')';
                }
            }
        }

        return ['searchWhere' => $args['searchWhere'], 'searchData' => $args['searchData']];
    }

    private static function getMainFieldsClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['searchWhere', 'searchData']);
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
                $subjectGlue = implode(' AND ', $requestData['where']);
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
        if (!empty($body['barcode']) && !empty($body['barcode']['values']) && is_string($body['barcode']['values'])) {
            $args['searchWhere'][] = 'barcode ilike ?';
            $args['searchData'][] = "%{$body['barcode']['values']}%";
        }
        if (!empty($body['resId']) && !empty($body['resId']['values']) && is_array($body['resId']['values'])) {
            if (Validator::intVal()->notEmpty()->validate($body['resId']['values']['start'])) {
                $args['searchWhere'][] = 'res_id >= ?';
                $args['searchData'][] = $body['resId']['values']['start'];
            }
            if (Validator::intVal()->notEmpty()->validate($body['resId']['values']['end'])) {
                $args['searchWhere'][] = 'res_id <= ?';
                $args['searchData'][] = $body['resId']['values']['end'];
            }
        }
        if (!empty($body['doctype']) && !empty($body['doctype']['values']) && is_array($body['doctype']['values'])) {
            $args['searchWhere'][] = 'type_id in (?)';
            $args['searchData'][] = $body['doctype']['values'];
        }
        if (!empty($body['category']) && !empty($body['category']['values']) && is_array($body['category']['values'])) {
            $args['searchWhere'][] = 'category_id in (?)';
            $args['searchData'][] = $body['category']['values'];
        }
        if (!empty($body['status']) && !empty($body['status']['values']) && is_array($body['status']['values'])) {
            if (in_array(null, $body['status']['values'])) {
                $args['searchWhere'][] = '(status in (select id from status where identifier in (?)) OR status is NULL)';
            } else {
                $args['searchWhere'][] = 'status in (select id from status where identifier in (?))';
            }
            $args['searchData'][] = $body['status']['values'];
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
        if (!empty($body['creationDate']) && !empty($body['creationDate']['values']) && is_array($body['creationDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['creationDate']['values']['start'])) {
                $args['searchWhere'][] = 'creation_date >= ?';
                $args['searchData'][] = $body['creationDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['creationDate']['values']['end'])) {
                $args['searchWhere'][] = 'creation_date <= ?';
                $args['searchData'][] = SearchController::getEndDayDate(['date' => $body['creationDate']['values']['end']]);
            }
        }
        if (!empty($body['documentDate']) && !empty($body['documentDate']['values']) && is_array($body['documentDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['documentDate']['values']['start'])) {
                $args['searchWhere'][] = 'doc_date >= ?';
                $args['searchData'][] = $body['documentDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['documentDate']['values']['end'])) {
                $args['searchWhere'][] = 'doc_date <= ?';
                $args['searchData'][] = SearchController::getEndDayDate(['date' => $body['documentDate']['values']['end']]);
            }
        }
        if (!empty($body['arrivalDate']) && !empty($body['arrivalDate']['values']) && is_array($body['arrivalDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['arrivalDate']['values']['start'])) {
                $args['searchWhere'][] = 'admission_date >= ?';
                $args['searchData'][] = $body['arrivalDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['arrivalDate']['values']['end'])) {
                $args['searchWhere'][] = 'admission_date <= ?';
                $args['searchData'][] = SearchController::getEndDayDate(['date' => $body['arrivalDate']['values']['end']]);
            }
        }
        if (!empty($body['departureDate']) && !empty($body['departureDate']['values']) && is_array($body['departureDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['departureDate']['values']['start'])) {
                $args['searchWhere'][] = 'departure_date >= ?';
                $args['searchData'][] = $body['departureDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['departureDate']['values']['end'])) {
                $args['searchWhere'][] = 'departure_date <= ?';
                $args['searchData'][] = SearchController::getEndDayDate(['date' => $body['departureDate']['values']['end']]);
            }
        }
        if (!empty($body['processLimitDate']) && !empty($body['processLimitDate']['values']) && is_array($body['processLimitDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['processLimitDate']['values']['start'])) {
                $args['searchWhere'][] = 'process_limit_date >= ?';
                $args['searchData'][] = $body['processLimitDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['processLimitDate']['values']['end'])) {
                $args['searchWhere'][] = 'process_limit_date <= ?';
                $args['searchData'][] = SearchController::getEndDayDate(['date' => $body['processLimitDate']['values']['end']]);
            }
        }
        if (!empty($body['closingDate']) && !empty($body['closingDate']['values']) && is_array($body['closingDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['closingDate']['values']['start'])) {
                $args['searchWhere'][] = 'closing_date >= ?';
                $args['searchData'][] = $body['closingDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['closingDate']['values']['end'])) {
                $args['searchWhere'][] = 'closing_date <= ?';
                $args['searchData'][] = SearchController::getEndDayDate(['date' => $body['closingDate']['values']['end']]);
            }
        }
        if (!empty($body['senders']) && !empty($body['senders']['values']) && is_array($body['senders']['values']) && is_array($body['senders']['values'][0])) {
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
        if (!empty($body['senders']) && !empty($body['senders']['values']) && is_array($body['senders']['values']) && is_string($body['senders']['values'][0])) {
            $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => ['company']]);

            $requestData = AutoCompleteController::getDataForRequest([
                'search'       => $body['senders']['values'][0],
                'fields'       => $fields,
                'fieldsNumber' => 1
            ]);

            $contacts = ContactModel::get([
                'select' => ['id'],
                'where'  => $requestData['where'],
                'data'   => $requestData['data']
            ]);
            $contactIds = array_column($contacts, 'id');
            if (empty($contactIds)) {
                return null;
            } else {
                $recipientsMatch = ResourceContactModel::get([
                    'select'    => ['res_id'],
                    'where'     => ['item_id in (?)', 'type = ?', 'mode = ?'],
                    'data'      => [$contactIds, 'contact', 'sender']
                ]);
                $resourceByRecipients = array_column($recipientsMatch, 'res_id');
                if (empty($resourceByRecipients)) {
                    return null;
                } else {
                    $args['searchWhere'][] = 'res_id in (?)';
                    $args['searchData'][] = $resourceByRecipients;
                }
            }
        }
        if (!empty($body['recipients']) && !empty($body['recipients']['values']) && is_array($body['recipients']['values']) && is_array($body['recipients']['values'][0])) {
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
        if (!empty($body['recipients']) && !empty($body['recipients']['values']) && is_array($body['recipients']['values']) && is_string($body['recipients']['values'][0])) {
            $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => ['company']]);

            $requestData = AutoCompleteController::getDataForRequest([
                'search'       => $body['recipients']['values'][0],
                'fields'       => $fields,
                'fieldsNumber' => 1
            ]);

            $contacts = ContactModel::get([
                'select' => ['id'],
                'where'  => $requestData['where'],
                'data'   => $requestData['data']
            ]);
            $contactIds = array_column($contacts, 'id');
            if (empty($contactIds)) {
                return null;
            } else {
                $recipientsMatch = ResourceContactModel::get([
                    'select'    => ['res_id'],
                    'where'     => ['item_id in (?)', 'type = ?', 'mode = ?'],
                    'data'      => [$contactIds, 'contact', 'recipient']
                ]);
                $resourceByRecipients = array_column($recipientsMatch, 'res_id');
                if (empty($resourceByRecipients)) {
                    return null;
                } else {
                    $args['searchWhere'][] = 'res_id in (?)';
                    $args['searchData'][] = $resourceByRecipients;
                }
            }
        }
        if (!empty($body['tags']) && !empty($body['tags']['values']) && is_array($body['tags']['values'])) {
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
            } elseif (in_array(null, $body['tags']['values'])) {
                $args['searchWhere'][] = '(res_id in (?) OR res_id not in (select distinct res_id from resources_tags))';
                $tagsMatch = array_column($tagsMatch, 'res_id');
                $args['searchData'][] = $tagsMatch;
            } else {
                $args['searchWhere'][] = 'res_id in (?)';
                $tagsMatch = array_column($tagsMatch, 'res_id');
                $args['searchData'][] = $tagsMatch;
            }
        }
        if (!empty($body['folders']) && !empty($body['folders']['values']) && is_array($body['folders']['values'])) {
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
            } elseif (in_array(null, $body['folders']['values'])) {
                $args['searchWhere'][] = '(res_id in (?) OR res_id not in (select distinct res_id from resources_folders))';
                $foldersMatch = array_column($foldersMatch, 'res_id');
                $args['searchData'][] = $foldersMatch;
            } else {
                $args['searchWhere'][] = 'res_id in (?)';
                $foldersMatch = array_column($foldersMatch, 'res_id');
                $args['searchData'][] = $foldersMatch;
            }
        }
        if (!empty($body['notes']) && !empty($body['notes']['values']) && is_string($body['notes']['values'])) {
            $notesMatch = NoteModel::get(['select' => ['identifier'], 'where' => ['note_text ilike ?'], 'data' => ["%{$body['notes']['values']}%"]]);
            if (empty($notesMatch)) {
                return null;
            }

            $args['searchWhere'][] = 'res_id in (?)';
            $notesMatch = array_column($notesMatch, 'identifier');
            $args['searchData'][] = $notesMatch;
        }
        if (!empty($body['attachment_type']) && !empty($body['attachment_type']['values']) && is_array($body['attachment_type']['values'])) {
            $args['searchWhere'][] = 'res_id in (select DISTINCT res_id_master from res_attachments where attachment_type in (?))';
            $args['searchData'][] = $body['attachment_type']['values'];
        }
        if (!empty($body['attachment_creationDate']) && !empty($body['attachment_creationDate']['values']) && is_array($body['attachment_creationDate']['values'])) {
            if (Validator::date()->notEmpty()->validate($body['attachment_creationDate']['values']['start'])) {
                $args['searchWhere'][] = 'res_id in (select DISTINCT res_id_master from res_attachments where creation_date >= ?)';
                $args['searchData'][] = $body['attachment_creationDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['attachment_creationDate']['values']['end'])) {
                $args['searchWhere'][] = 'res_id in (select DISTINCT res_id_master from res_attachments where creation_date <= ?)';
                $args['searchData'][] = SearchController::getEndDayDate(['date' => $body['attachment_creationDate']['values']['end']]);
            }
        }
        if (!empty($body['groupSign']) && !empty($body['groupSign']['values']) && is_array($body['groupSign']['values'])) {
            $args['searchWhere'][] = 'res_id in (select DISTINCT res_id from listinstance where signatory = ? AND item_id in (select DISTINCT user_id from usergroup_content where group_id in (?)))';
            $args['searchData'][] = 'true';
            $args['searchData'][] = $body['groupSign']['values'];
        }
        if (!empty($body['senderDepartment']) && !empty($body['senderDepartment']['values']) && is_array($body['senderDepartment']['values'])) {
            $departments = '';
            foreach ($body['senderDepartment']['values'] as $value) {
                if (!is_numeric($value)) {
                    continue;
                }
                if (!empty($departments)) {
                    $departments .= ', ';
                }
                $departments .= "'{$value}%'";
            }
            $contacts = ContactModel::get([
                'select' => ['id'],
                'where'  => ["address_postcode like any (array[{$departments}])"]
            ]);
            $contactIds = array_column($contacts, 'id');
            if (empty($contactIds)) {
                return null;
            } else {
                $sendersMatch = ResourceContactModel::get([
                    'select'    => ['res_id'],
                    'where'     => ['item_id in (?)', 'type = ?', 'mode = ?'],
                    'data'      => [$contactIds, 'contact', 'sender']
                ]);
                $sendersMatch = array_column($sendersMatch, 'res_id');
                if (empty($sendersMatch)) {
                    return null;
                } else {
                    $args['searchWhere'][] = 'res_id in (?)';
                    $args['searchData'][] = $sendersMatch;
                }
            }
        }

        return ['searchWhere' => $args['searchWhere'], 'searchData' => $args['searchData']];
    }

    private static function getListFieldsClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['searchWhere', 'searchData']);
        ValidatorModel::arrayType($args, ['body', 'searchWhere', 'searchData']);

        $body = $args['body'];

        foreach ($body as $key => $value) {
            if (strpos($key, 'role_') !== false) {
                $roleId = substr($key, 5);

                if (!empty($value['values']) && is_array($value['values'])) {
                    $where = '';
                    $data = [];
                    foreach ($value['values'] as $itemValue) {
                        if (!empty($where)) {
                            $where .= ' OR ';
                        }
                        $where .= '(item_id = ? AND item_type = ?)';
                        $data[] = $itemValue['id'];
                        $data[] = $itemValue['type'] == 'user' ? 'user_id' : 'entity_id';
                    }
                    if ($roleId == 'sign') {
                        $data[] = 'true';
                        $rolesMatch = ListInstanceModel::get([
                            'select'    => ['res_id'],
                            'where'     => ["({$where})", 'signatory = ?'],
                            'data'      => $data
                        ]);
                    } else {
                        $data[] = $roleId;
                        $rolesMatch = ListInstanceModel::get([
                            'select'    => ['res_id'],
                            'where'     => ["({$where})", 'item_mode = ?'],
                            'data'      => $data
                        ]);
                    }
                    if (empty($rolesMatch)) {
                        return null;
                    }
                    $rolesMatch = array_column($rolesMatch, 'res_id');
                    $args['searchWhere'][] = 'res_id in (?)';
                    $args['searchData'][] = $rolesMatch;
                }
            }
        }

        return ['searchWhere' => $args['searchWhere'], 'searchData' => $args['searchData']];
    }

    private static function getCustomFieldsClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['searchWhere', 'searchData']);
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
                    if (!empty($value) && !empty($value['values']) && is_array($value['values'])) {
                        if (Validator::intVal()->notEmpty()->validate($value['values']['start'])) {
                            $args['searchWhere'][] = "(custom_fields->>'{$customFieldId}')::int >= ?";
                            $args['searchData'][] = $value['values']['start'];
                        }
                        if (Validator::intVal()->notEmpty()->validate($value['values']['end'])) {
                            $args['searchWhere'][] = "(custom_fields->>'{$customFieldId}')::int <= ?";
                            $args['searchData'][] = $value['values']['end'];
                        }
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
                        $args['searchData'][] = SearchController::getEndDayDate(['date' => $value['values']['end']]);
                    }
                } elseif ($customField['type'] == 'banAutocomplete') {
                    if (!empty($value) && !empty($value['values']) && is_array($value['values'])) {
                        $where = '';
                        foreach ($value['values'] as $item) {
                            if (!empty($where)) {
                                $where .= ' OR ';
                            }
                            $where .= "custom_fields->'{$customFieldId}'->0->>'id' = ?";
                            $args['searchData'][] = "{$item['id']}";
                        }
                        $args['searchWhere'][] = $where;
                    }
                } elseif ($customField['type'] == 'contact') {
                    if (!empty($value['values']) && is_array($value['values']) && is_array($value['values'][0])) {
                        $contactSearchWhere = [];
                        foreach ($value['values'] as $contactValue) {
                            $contactSearchWhere[] = "custom_fields->'{$customFieldId}' @> ?";
                            $args['searchData'][] = '[{"id": ' . $contactValue['id'] . ', "type": "' . $contactValue['type'] . '"}]';
                        }
                        $args['searchWhere'][] = '(' . implode(' or ', $contactSearchWhere) . ')';
                    } elseif (!empty($value['values']) && is_array($value['values']) && is_string($value['values'][0])) {
                        $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => ['company']]);

                        $requestData = AutoCompleteController::getDataForRequest([
                            'search'       => $value['values'],
                            'fields'       => $fields,
                            'fieldsNumber' => 1
                        ]);

                        $contacts = ContactModel::get([
                            'select'    => ['id'],
                            'where'     => $requestData['where'],
                            'data'      => $requestData['data']
                        ]);
                        $contactIds = array_column($contacts, 'id');
                        if (empty($contactIds)) {
                            return null;
                        }

                        $contactsStandalone = [];
                        foreach ($contactIds as $contactIdStandalone) {
                            $contactsStandalone[] = "custom_fields->'{$customFieldId}' @> ?";
                            $args['searchData'][] = '[{"id": ' . $contactIdStandalone . ', "type": "contact"}]';
                        }
                        $args['searchWhere'][] = '(' . implode(' or ', $contactsStandalone) . ')';
                    }
                }
            }
        }

        return ['searchWhere' => $args['searchWhere'], 'searchData' => $args['searchData']];
    }

    private static function getRegisteredMailsClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['searchWhere', 'searchData']);
        ValidatorModel::arrayType($args, ['body', 'searchWhere', 'searchData']);

        $body = $args['body'];

        if (!empty($body['registeredMail_reference']) && !empty($body['registeredMail_reference']['values']) && is_string($body['registeredMail_reference']['values'])) {
            $registeredMailsMatch = RegisteredMailModel::get([
                'select'    => ['res_id'],
                'where'     => ['reference ilike ?'],
                'data'      => ["%{$body['registeredMail_reference']['values']}%"]
            ]);
            if (empty($registeredMailsMatch)) {
                return null;
            }
            $registeredMailsMatch = array_column($registeredMailsMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $registeredMailsMatch;
        }
        if (!empty($body['registeredMail_issuingSite']) && !empty($body['registeredMail_issuingSite']['values']) && is_array($body['registeredMail_issuingSite']['values'])) {
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
        if (!empty($body['registeredMail_receivedDate']) && !empty($body['registeredMail_receivedDate']['values']) && is_array($body['registeredMail_receivedDate']['values'])) {
            $where = [];
            $data = [];
            if (Validator::date()->notEmpty()->validate($body['registeredMail_receivedDate']['values']['start'])) {
                $where[] = 'received_date >= ?';
                $data[] = $body['registeredMail_receivedDate']['values']['start'];
            }
            if (Validator::date()->notEmpty()->validate($body['registeredMail_receivedDate']['values']['end'])) {
                $where[] = 'received_date <= ?';
                $data[] = SearchController::getEndDayDate(['date' => $body['registeredMail_receivedDate']['values']['end']]);
            }

            $registeredMailsMatch = RegisteredMailModel::get([
                'select'    => ['res_id'],
                'where'     => $where,
                'data'      => $data
            ]);
            if (empty($registeredMailsMatch)) {
                return null;
            }
            $registeredMailsMatch = array_column($registeredMailsMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $registeredMailsMatch;
        }
        if (!empty($body['registeredMail_recipient']) && !empty($body['registeredMail_recipient']['values']) && is_array($body['registeredMail_recipient']['values']) && is_array($body['registeredMail_recipient']['values'][0])) {
            $contactsIds = array_column($body['registeredMail_recipient']['values'], 'id');
            $contacts = ContactModel::get([
                'select'    => ['company', 'lastname', 'address_number', 'address_street', 'address_postcode', 'address_country'],
                'where'     => ['id in (?)'],
                'data'      => [$contactsIds]
            ]);
            if (empty($contacts)) {
                return null;
            }
            $where = '';
            $data = [];
            foreach ($contacts as $contact) {
                if (!empty($where)) {
                    $where .= ' OR ';
                }
                $columnMatch = 'company';
                if (!empty($contact['lastname'])) {
                    $columnMatch = 'lastname';
                }
                $where .= "(recipient->>'{$columnMatch}' = ? AND recipient->>'addressNumber' = ? AND recipient->>'addressStreet' = ? AND recipient->>'addressPostcode' = ? AND recipient->>'addressCountry' = ?)";
                $data = array_merge($data, [$contact[$columnMatch], $contact['address_number'], $contact['address_street'], $contact['address_postcode'], $contact['address_country']]);
            }
            $registeredMailsMatch = RegisteredMailModel::get([
                'select'    => ['res_id'],
                'where'     => [$where],
                'data'      => $data
            ]);
            if (empty($registeredMailsMatch)) {
                return null;
            }
            $registeredMailsMatch = array_column($registeredMailsMatch, 'res_id');
            $args['searchWhere'][] = 'res_id in (?)';
            $args['searchData'][] = $registeredMailsMatch;
        }
        if (!empty($body['registeredMail_recipient']) && !empty($body['registeredMail_recipient']['values']) && is_array($body['registeredMail_recipient']['values']) && is_string($body['registeredMail_recipient']['values'][0])) {
            $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => ["recipient->>'company'"]]);
            $requestData = AutoCompleteController::getDataForRequest([
                'search'       => $body['registeredMail_recipient']['values'][0],
                'fields'       => $fields,
                'fieldsNumber' => 1
            ]);

            $registeredMailsMatch = RegisteredMailModel::get([
                'select'    => ['res_id'],
                'where'     => $requestData['where'],
                'data'      => $requestData['data']
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

    private function getFulltextClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['searchWhere', 'searchData']);
        ValidatorModel::arrayType($args, ['body', 'searchWhere', 'searchData']);

        if (!empty($args['body']['fulltext']['values'])) {
            if (strpos($args['body']['fulltext']['values'], "'") === false && ($args['body']['fulltext']['values'][0] != '"' || $args['body']['fulltext']['values'][strlen($args['body']['fulltext']['values']) - 1] != '"')) {
                $query_fulltext = explode(" ", trim($args['body']['fulltext']['values']));
                foreach ($query_fulltext as $key => $value) {
                    if (strpos($value, "*") !== false && (strlen(substr($value, 0, strpos($value, "*"))) < 4 || preg_match("([,':!+])", $value) === 1)) {
                        return null;
                        break;
                    }
                    $query_fulltext[$key] = $value . "*";
                }
                $args['body']['fulltext']['values'] = implode(" ", $query_fulltext);
            }

            \Zend_Search_Lucene_Analysis_Analyzer::setDefault(new \Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
            \Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(\Zend_Search_Lucene_Search_QueryParser::B_AND);
            \Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');

            $whereRequest = [];
            foreach (['letterbox_coll', 'attachments_coll'] as $tmpCollection) {
                $fullTextDocserver = DocserverModel::getCurrentDocserver(['collId' => $tmpCollection, 'typeId' => 'FULLTEXT']);
                $pathToLuceneIndex = $fullTextDocserver['path_template'];

                if (is_dir($pathToLuceneIndex) && !FullTextController::isDirEmpty($pathToLuceneIndex)) {
                    $index     = \Zend_Search_Lucene::open($pathToLuceneIndex);
                    $hits      = $index->find(TextFormatModel::normalize(['string' => $args['body']['fulltext']['values']]));
                    $listIds   = [];
                    $cptIds    = 0;
                    foreach ($hits as $hit) {
                        if ($cptIds < 500) {
                            $listIds[] = $hit->Id;
                        } else {
                            break;
                        }
                        $cptIds ++;
                    }

                    if (empty($listIds)) {
                        continue;
                    }

                    if ($tmpCollection == 'attachments_coll') {
                        $idMasterDatas = AttachmentModel::get([
                            'select' => ['DISTINCT res_id_master'],
                            'where'  => ['res_id in (?)', 'status in (?)'],
                            'data'   => [$listIds, ['DEL','OBS','TMP']]
                        ]);

                        $listIds = array_column($idMasterDatas, 'res_id_master');
                    }

                    if (!empty($listIds)) {
                        $whereRequest[] = " res_id in (?) ";
                        $args['searchData'][] = $listIds;
                    }
                }
            }

            if (!empty($whereRequest)) {
                $args['searchWhere'][] = '(' . implode(" or ", $whereRequest) . ')';
            } else {
                return null;
            }
        }
        return ['searchWhere' => $args['searchWhere'], 'searchData' => $args['searchData']];
    }

    private static function getFiltersClause(array $args)
    {
        ValidatorModel::notEmpty($args, ['searchWhere', 'searchData']);
        ValidatorModel::arrayType($args, ['body', 'searchWhere', 'searchData']);

        $body = $args['body'];

        if (!empty($body['filters'])) {
            if (!empty($body['filters']['doctypes']) && is_array($body['filters']['doctypes'])) {
                $doctypes = [];
                foreach ($body['filters']['doctypes'] as $filter) {
                    if ($filter['selected']) {
                        $doctypes[] = $filter['id'];
                    }
                }
                if (!empty($doctypes)) {
                    $args['searchWhere'][] = 'type_id in (?)';
                    $args['searchData'][] = $doctypes;
                }
            }
            if (!empty($body['filters']['categories']) && is_array($body['filters']['categories'])) {
                $categories = [];
                foreach ($body['filters']['categories'] as $filter) {
                    if ($filter['selected']) {
                        $categories[] = $filter['id'];
                    }
                }
                if (!empty($categories)) {
                    $args['searchWhere'][] = 'category_id in (?)';
                    $args['searchData'][] = $categories;
                }
            }
            if (!empty($body['filters']['priorities']) && is_array($body['filters']['priorities'])) {
                $priorities = [];
                foreach ($body['filters']['priorities'] as $filter) {
                    if ($filter['selected']) {
                        $priorities[] = $filter['id'];
                    }
                }
                if (!empty($priorities)) {
                    $args['searchWhere'][] = 'priority in (?)';
                    $args['searchData'][] = $priorities;
                }
            }
            if (!empty($body['filters']['statuses']) && is_array($body['filters']['statuses'])) {
                $statuses = [];
                foreach ($body['filters']['statuses'] as $filter) {
                    if ($filter['selected']) {
                        $statuses[] = $filter['id'];
                    }
                }
                if (!empty($statuses)) {
                    $args['searchWhere'][] = 'status in (?)';
                    $args['searchData'][] = $statuses;
                }
            }
            if (!empty($body['filters']['entities']) && is_array($body['filters']['entities'])) {
                $entities = [];
                foreach ($body['filters']['entities'] as $filter) {
                    if ($filter['selected']) {
                        $entities[] = $filter['id'];
                    }
                }
                if (!empty($entities)) {
                    $args['searchWhere'][] = 'destination in (?)';
                    $args['searchData'][] = $entities;
                }
            }
            if (!empty($body['filters']['folders']) && is_array($body['filters']['folders'])) {
                $folders = [];
                foreach ($body['filters']['folders'] as $filter) {
                    if ($filter['selected']) {
                        $folders[] = $filter['id'];
                    }
                }
                if (!empty($folders)) {
                    $args['searchWhere'][] = 'res_id in (select distinct res_id from resources_folders where folder_id in (?))';
                    $args['searchData'][] = $folders;
                }
            }
        }

        return ['searchWhere' => $args['searchWhere'], 'searchData' => $args['searchData']];
    }

    private static function getFilters(array $args)
    {
        ValidatorModel::arrayType($args, ['body', 'resources']);

        if (empty($args['resources'])) {
            return ['entities' => [], 'priorities' => [], 'categories' => [], 'statuses' => [], 'doctypes' => [], 'folders' => []];
        }

        $body = $args['body'];


        $priorities = [];
        $rawPriorities = ResModel::get([
            'select'    => ['count(res_id)', 'priority'],
            'where'     => ['res_id in (?)'],
            'data'      => [$args['resources']],
            'groupBy'   => ['priority']
        ]);
        foreach ($rawPriorities as $key => $value) {
            $label = null;
            $selected = false;
            if (!empty($body['filters']['priorities']) && is_array($body['filters']['priorities'])) {
                foreach ($body['filters']['priorities'] as $filter) {
                    if ($filter['id'] == $value['priority']) {
                        $selected = $filter['selected'];
                        $label = $filter['label'];
                    }
                }
            }
            if (!empty($value['priority']) && empty($label)) {
                $priority = PriorityModel::getById(['select' => ['label'], 'id' => $value['priority']]);
                $label = $priority['label'];
            }

            $priorities[] = [
                'id'        => $value['priority'],
                'label'     => $label ?? '_UNDEFINED',
                'count'     => $value['count'],
                'selected'  => $selected
            ];
        }

        $categories = [];
        $rawCategories = ResModel::get([
            'select'    => ['count(res_id)', 'category_id'],
            'where'     => ['res_id in (?)'],
            'data'      => [$args['resources']],
            'groupBy'   => ['category_id']
        ]);
        foreach ($rawCategories as $key => $value) {
            $selected = false;
            if (!empty($body['filters']['categories']) && is_array($body['filters']['categories'])) {
                foreach ($body['filters']['categories'] as $filter) {
                    if ($filter['id'] == $value['category_id']) {
                        $selected = $filter['selected'];
                    }
                }
            }
            $label = ResModel::getCategoryLabel(['categoryId' => $value['category_id']]);
            $categories[] = [
                'id'        => $value['category_id'],
                'label'     => empty($label) ? '_UNDEFINED' : $label,
                'count'     => $value['count'],
                'selected'  => $selected
            ];
        }

        $statuses = [];
        $rawStatuses = ResModel::get([
            'select'    => ['count(res_id)', 'status'],
            'where'     => ['res_id in (?)'],
            'data'      => [$args['resources']],
            'groupBy'   => ['status']
        ]);
        foreach ($rawStatuses as $key => $value) {
            $label = null;
            $selected = false;
            if (!empty($body['filters']['statuses']) && is_array($body['filters']['statuses'])) {
                foreach ($body['filters']['statuses'] as $filter) {
                    if ($filter['id'] == $value['category_id']) {
                        $selected = $filter['selected'];
                        $label = $filter['label'];
                    }
                }
            }
            if (!empty($value['status']) && empty($label)) {
                $status = StatusModel::getById(['select' => ['label_status'], 'id' => $value['status']]);
                $label = $status['label_status'];
            }

            $statuses[] = [
                'id'        => $value['status'],
                'label'     => $label ?? '_UNDEFINED',
                'count'     => $value['count'],
                'selected'  => $selected
            ];
        }

        $docTypes = [];
        $rawDocType = ResModel::get([
            'select'    => ['count(res_id)', 'type_id'],
            'where'     => ['res_id in (?)'],
            'data'      => [$args['resources']],
            'groupBy'   => ['type_id']
        ]);
        foreach ($rawDocType as $key => $value) {
            $label = null;
            $selected = false;
            if (!empty($body['filters']['doctypes']) && is_array($body['filters']['doctypes'])) {
                foreach ($body['filters']['doctypes'] as $filter) {
                    if ($filter['id'] == $value['type_id']) {
                        $selected = $filter['selected'];
                        $label = $filter['label'];
                    }
                }
            }
            if (empty($label)) {
                $doctype = DoctypeModel::getById(['select' => ['description'], 'id' => $value['type_id']]);
                $label = $doctype['description'];
            }

            $docTypes[] = [
                'id'        => $value['type_id'],
                'label'     => $label ?? '_UNDEFINED',
                'count'     => $value['count'],
                'selected'  => $selected
            ];
        }

        $entities = [];
        $rawEntities = ResModel::get([
            'select'    => ['count(res_id)', 'destination'],
            'where'     => ['res_id in (?)'],
            'data'      => [$args['resources']],
            'groupBy'   => ['destination']
        ]);
        foreach ($rawEntities as $key => $value) {
            $label = null;
            $selected = false;
            if (!empty($body['filters']['entities']) && is_array($body['filters']['entities'])) {
                foreach ($body['filters']['entities'] as $filter) {
                    if ($filter['id'] == $value['destination']) {
                        $selected = $filter['selected'];
                        $label = $filter['label'];
                    }
                }
            }
            if (!empty($value['destination']) && empty($label)) {
                $entity = EntityModel::getByEntityId(['select' => ['entity_label'], 'entityId' => $value['destination']]);
                $label = $entity['entity_label'];
            }

            $entities[] = [
                'id'        => $value['destination'],
                'label'     => $label ?? '_UNDEFINED',
                'count'     => $value['count'],
                'selected'  => $selected
            ];
        }

        $folders = [];
        $userEntities = EntityModel::getWithUserEntities([
            'select' => ['entities.id'],
            'where'  => ['users_entities.user_id = ?'],
            'data'   => [$GLOBALS['id']]
        ]);
        $userEntities = !empty($userEntities) ? array_column($userEntities, 'id') : [0];

        $rawFolders = FolderModel::getWithEntitiesAndResources([
            'select'  => ['folders.id', 'folders.label', 'count(resources_folders.res_id) as count'],
            'where'   => ['resources_folders.res_id in (?)', '(folders.user_id = ? OR entities_folders.entity_id in (?) or keyword = ?)'],
            'data'    => [$args['resources'], $GLOBALS['id'], $userEntities, 'ALL_ENTITIES'],
            'groupBy' => ['folders.id', 'folders.label']
        ]);
        foreach ($rawFolders as $key => $value) {
            $selected = false;
            if (!empty($body['filters']['folders']) && is_array($body['filters']['folders'])) {
                foreach ($body['filters']['folders'] as $filter) {
                    if ($filter['id'] == $value['id']) {
                        $selected = $filter['selected'];
                    }
                }
            }

            $folders[] = [
                'id'        => $value['id'],
                'label'     => $value['label'],
                'count'     => $value['count'],
                'selected'  => $selected
            ];
        }

        $priorities = (count($priorities) >= 2) ? $priorities : [];
        $categories = (count($categories) >= 2) ? $categories : [];
        $statuses   = (count($statuses) >= 2) ? $statuses : [];
        $docTypes   = (count($docTypes) >= 2) ? $docTypes : [];
        $entities   = (count($entities) >= 2) ? $entities : [];
        $folders    = (count($folders) >= 2) ? $folders : [];

        usort($priorities, ['Resource\controllers\ResourceListController', 'compareSortOnLabel']);
        usort($categories, ['Resource\controllers\ResourceListController', 'compareSortOnLabel']);
        usort($statuses, ['Resource\controllers\ResourceListController', 'compareSortOnLabel']);
        usort($docTypes, ['Resource\controllers\ResourceListController', 'compareSortOnLabel']);
        usort($entities, ['Resource\controllers\ResourceListController', 'compareSortOnLabel']);
        usort($folders, ['Resource\controllers\ResourceListController', 'compareSortOnLabel']);


        return ['priorities' => $priorities, 'categories' => $categories, 'statuses' => $statuses, 'doctypes' => $docTypes, 'entities' => $entities, 'folders' => $folders];
    }

    private static function getEndDayDate(array $args)
    {
        ValidatorModel::notEmpty($args, ['date']);
        ValidatorModel::stringType($args, ['date']);

        $date = new \DateTime($args['date']);
        $date->setTime(23, 59, 59);

        return $date->format('d-m-Y H:i:s');
    }
}
