<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief History Controller
* @author dev@maarch.org
*/

namespace History\controllers;

use Action\models\ActionModel;
use Resource\controllers\ResController;
use Respect\Validation\Validator;
use SrcCore\controllers\LogsController;
use Group\models\ServiceModel;
use SrcCore\models\ValidatorModel;
use History\models\HistoryModel;
use Notification\controllers\NotificationsEventsController;
use Slim\Http\Request;
use Slim\Http\Response;
use User\models\UserModel;

class HistoryController
{
    public function get(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        if (!empty($queryParams['resId'])) {
            if (!Validator::intVal()->notEmpty()->validate($queryParams['resId']) || !ResController::hasRightByResId(['resId' => [$queryParams['resId']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            } elseif (!ServiceModel::hasService(['id' => 'view_full_history', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
                if (empty($queryParams['onlyActions']) || !ServiceModel::hasService(['id' => 'view_doc_history', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
                    return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
                }
            }
        } elseif (!ServiceModel::hasService(['id' => 'view_history', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $limit = 25;
        if (!empty($queryParams['limit']) && is_numeric($queryParams['limit'])) {
            $limit = (int)$queryParams['limit'];
        }
        $offset = 0;
        if (!empty($queryParams['offset']) && is_numeric($queryParams['offset'])) {
            $offset = (int)$queryParams['offset'];
        }

        $where = [];
        $data = [];
        if (!empty($queryParams['users']) && is_array($queryParams['users'])) {
            $userIds = [];
            $userLogins = [];
            foreach ($queryParams['users'] as $user) {
                if (is_numeric($user)) {
                    $userIds[] = $user;
                } else {
                    $userLogins[] = $user;
                }
            }
            $users = [];
            if (!empty($userIds)) {
                $users = UserModel::get(['select' => ['user_id'], 'where' => ['id in (?)'], 'data' => [$userIds]]);
                $users = array_column($users, 'user_id');
            }
            $users = array_merge($users, $userLogins);
            $where[] = 'user_id in (?)';
            $data[] = $users;
        }

        if (!empty($queryParams['startDate'])) {
            $where[] = 'event_date > ?';
            $data[] = $queryParams['startDate'];
        }
        if (!empty($queryParams['endDate'])) {
            $where[] = 'event_date < ?';
            $data[] = $queryParams['endDate'];
        }

        if (!empty($queryParams['resId'])) {
            $where[] = 'table_name in (?)';
            $data[] = ['res_letterbox', 'res_view_letterbox'];

            $where[] = 'record_id = ?';
            $data[] = $queryParams['resId'];
        }
        if (!empty($queryParams['onlyActions'])) {
            $where[] = 'event_type like ?';
            $data[] = 'ACTION#%';
        }

        $eventTypes = [];
        if (!empty($queryParams['actions']) && is_array($queryParams['actions'])) {
            foreach ($queryParams['actions'] as $action) {
                $eventTypes[] = "ACTION#{$action}";
            }
        }
        if (!empty($queryParams['systemActions']) && is_array($queryParams['systemActions'])) {
            $eventTypes = array_merge($eventTypes, $queryParams['systemActions']);
        }
        if (!empty($eventTypes)) {
            $where[] = 'event_type in (?)';
            $data[] = $eventTypes;
        }

        $order = !in_array($queryParams['order'], ['asc', 'desc']) ? '' : $queryParams['order'];
        $queryParams['orderBy'] = (!empty($queryParams['orderBy']) && $queryParams['orderBy'] == 'userLabel') ? 'user_id' : null;
        $orderBy = !in_array($queryParams['orderBy'], ['event_date', 'user_id', 'info']) ? ['event_date DESC'] : ["{$queryParams['orderBy']} {$order}"];

        $history = HistoryModel::get([
            'select'    => ['event_date', 'user_id', 'info', 'remote_ip', 'count(1) OVER()'],
            'where'     => $where,
            'data'      => $data,
            'orderBy'   => $orderBy,
            'offset'    => $offset,
            'limit'     => $limit
        ]);

        $total = $history[0]['count'] ?? 0;
        foreach ($history as $key => $value) {
            $history[$key]['userLabel'] = UserModel::getLabelledUserById(['login' => $value['user_id']]);
            unset($history[$key]['count']);
        }

        return $response->withJson(['history' => $history, 'count' => $total]);
    }

    public static function add(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['tableName', 'recordId', 'eventType', 'info', 'eventId']);
        ValidatorModel::stringType($aArgs, ['tableName', 'eventType', 'info', 'eventId', 'moduleId', 'level', 'userId']);

        if (empty($aArgs['isTech'])) {
            $aArgs['isTech'] = false;
        }
        if (empty($aArgs['moduleId'])) {
            $aArgs['moduleId'] = 'admin';
        }
        if (empty($aArgs['level'])) {
            $aArgs['level'] = 'DEBUG';
        }

        LogsController::add($aArgs);

        if (empty($aArgs['userId'])) {
            $aArgs['userId'] = $GLOBALS['userId'];
        }

        HistoryModel::create([
            'tableName' => $aArgs['tableName'],
            'recordId'  => $aArgs['recordId'],
            'eventType' => $aArgs['eventType'],
            'userId'    => $aArgs['userId'],
            'info'      => $aArgs['info'],
            'moduleId'  => $aArgs['moduleId'],
            'eventId'   => $aArgs['eventId'],
        ]);

        NotificationsEventsController::fillEventStack([
            "eventId"   => $aArgs['eventId'],
            "tableName" => $aArgs['tableName'],
            "recordId"  => $aArgs['recordId'],
            "userId"    => $aArgs['userId'],
            "info"      => $aArgs['info'],
        ]);
    }

    public function getByUserId(Request $request, Response $response, array $aArgs)
    {
        $user = UserModel::getById(['id' => $aArgs['userSerialId'], 'select' => ['user_id']]);
        if ($user['user_id'] != $GLOBALS['userId'] && !ServiceModel::hasService(['id' => 'view_history', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $aHistories = HistoryModel::getByUserId(['userId' => $user['user_id'], 'select' => ['info', 'event_date']]);

        return $response->withJson(['histories' => $aHistories]);
    }

    public function getAvailableFilters(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        if (!empty($queryParams['resId'])) {
            if (!Validator::intVal()->notEmpty()->validate($queryParams['resId']) || !ResController::hasRightByResId(['resId' => [$queryParams['resId']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            } elseif (!ServiceModel::hasService(['id' => 'view_full_history', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
                if (empty($queryParams['onlyActions']) || !ServiceModel::hasService(['id' => 'view_doc_history', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
                    return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
                }
            }
        } elseif (!ServiceModel::hasService(['id' => 'view_history', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $where = [];
        $data = [];

        if (!empty($queryParams['resId'])) {
            $where[] = 'table_name in (?)';
            $data[] = ['res_letterbox', 'res_view_letterbox'];
            $where[] = 'record_id = ?';
            $data[] = $queryParams['resId'];
        }
        if (!empty($queryParams['onlyActions'])) {
            $where[] = 'event_type like ?';
            $data[] = 'ACTION#%';
        }

        $eventTypes = HistoryModel::get([
            'select'    => ['DISTINCT(event_type)'],
            'where'     => $where,
            'data'      => $data
        ]);

        $actions = [];
        $systemActions = [];
        foreach ($eventTypes as $eventType) {
            if (strpos($eventType['event_type'], 'ACTION#') === 0) {
                $exp = explode('#', $eventType['event_type']);
                if (!empty($exp[1])) {
                    $action = ActionModel::getById(['select' => ['label_action'], 'id' => $exp[1]]);
                }
                $label = !empty($action) ? $action['label_action'] : null;
                $actions[] = ['id' => $exp[1], 'label' => $label];
            } else {
                $systemActions[] = ['id' => $eventType['event_type'], 'label' => null];
            }
        }

        $usersInHistory = HistoryModel::get([
            'select'    => ['DISTINCT(user_id)'],
            'where'     => $where,
            'data'      => $data
        ]);

        $users = [];
        foreach ($usersInHistory as $value) {
            $user = null;
            if (!empty($value['user_id'])) {
                $user = UserModel::getByLogin(['login' => $value['user_id'], 'select' => ['id', 'firstname', 'lastname']]);
            }

            $users[] = ['id' => $user['id'] ?? null, 'login' => $value['user_id'], 'label' => !empty($user['id']) ? "{$user['firstname']} {$user['lastname']}" : null];
        }

        return $response->withJson(['actions' => $actions, 'systemActions' => $systemActions, 'users' => $users]);
    }

    public function getPrivileges(Request $request, Response $response)
    {
        $history = ServiceModel::hasService(['id' => 'view_history', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin']);
        $historyBatch = ServiceModel::hasService(['id' => 'view_history_batch', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin']);

        return $response->withJson(['history' => $history, 'historyBatch' => $historyBatch]);
    }
}
