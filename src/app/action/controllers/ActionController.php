<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ActionController
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace Action\controllers;

use History\controllers\HistoryController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Action\models\ActionModel;
use Status\models\StatusModel;
use Group\models\ServiceModel;
use Slim\Http\Request;
use Slim\Http\Response;

class ActionController
{
    public function get(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_actions', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        return $response->withJson(['actions' => ActionModel::get()]);
    }

    public function getById(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not an integer']);
        }

        $action['action'] = ActionModel::getById(['id' => $aArgs['id']]);
        if (empty($action['action'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Action does not exist']);
        }

        $categories = ActionModel::getCategoriesById(['id' => $aArgs['id']]);

        $action['action']['history'] = ($action['action']['history'] == 'Y');
        $action['action']['is_system'] = ($action['action']['is_system'] == 'Y');

        $action['action']['actionCategories'] = [];
        foreach ($categories as $category) {
            $action['action']['actionCategories'][] = $category['category_id'];
        }

        $action['categoriesList'] = ResModel::getCategories();
        if (empty($action['action']['actionCategories'])) {
            foreach ($action['categoriesList'] as $category) {
                $action['action']['actionCategories'][] = $category['id'];
            }
        }

        $action['statuses'] = StatusModel::get();
        array_unshift($action['statuses'], ['id' => '_NOSTATUS_', 'label_status' => _UNCHANGED]);
        $action['actionPages'] = ActionModel::getActionPages();
        $action['keywordsList'] = ActionModel::getKeywords();

        foreach ($action['actionPages'] as $actionPage) {
            if ($actionPage['id'] == $action['action']['action_page']) {
                $action['action']['actionPageId'] = $actionPage['id'];
            }
        }

        return $response->withJson($action);
    }

    public function create(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_actions', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        $body = $this->manageValue($body);
        
        $errors = $this->control($body, 'create');
        if (!empty($errors)) {
            return $response->withStatus(400)->withJson(['errors' => $errors]);
        }

        unset($body['action_page']);
        $actionPages = ActionModel::getActionPages();
        foreach ($actionPages as $actionPage) {
            if ($actionPage['id'] == $body['actionPageId']) {
                $body['action_page'] = $actionPage['name'];
                $body['component'] = $actionPage['component'];
            }
        }
        if (empty($body['action_page'])) {
            $body['component'] = 'noConfirmAction';
        }

        unset($body['actionPageId']);
        $id = ActionModel::create($body);
        if (!empty($body['actionCategories'])) {
            ActionModel::createCategories(['id' => $id, 'categories' => $body['actionCategories']]);
        }

        HistoryController::add([
            'tableName' => 'actions',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'eventId'   => 'actionadd',
            'info'      => _ACTION_ADDED . ' : ' . $body['label_action']
        ]);

        return $response->withJson(['actionId' => $id]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_actions', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        $body['id'] = $aArgs['id'];

        $body    = $this->manageValue($body);
        $errors = $this->control($body, 'update');
        if (!empty($errors)) {
            return $response->withStatus(500)->withJson(['errors' => $errors]);
        }

        unset($body['action_page']);
        $actionPages = ActionModel::getActionPages();
        foreach ($actionPages as $actionPage) {
            if ($actionPage['id'] == $body['actionPageId']) {
                $body['action_page'] = $actionPage['id'];
                $body['component'] = $actionPage['component'];
            }
        }
        if (empty($body['action_page'])) {
            $body['component'] = 'noConfirmAction';
        }

        ActionModel::update($body);
        ActionModel::deleteCategories(['id' => $aArgs['id']]);
        if (!empty($body['actionCategories'])) {
            ActionModel::createCategories(['id' => $aArgs['id'], 'categories' => $body['actionCategories']]);
        }

        HistoryController::add([
            'tableName' => 'actions',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'eventId'   => 'actionup',
            'info'      => _ACTION_UPDATED. ' : ' . $body['label_action']
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function delete(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_actions', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->validate($aArgs['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not an integer']);
        }

        ActionModel::delete(['id' => $aArgs['id']]);
        ActionModel::deleteCategories(['id' => $aArgs['id']]);

        $action = ActionModel::getById(['id' => $aArgs['id'], 'select' => ['label_action']]);
        HistoryController::add([
            'tableName' => 'actions',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'eventId'   => 'actiondel',
            'info'      => _ACTION_DELETED. ' : ' . $action['label_action']
        ]);

        return $response->withJson(['actions' => ActionModel::get()]);
    }

    protected function control($aArgs, $mode)
    {
        $errors = [];
      
        $objs = StatusModel::get();

        foreach ($objs as $obj) {
            $status[] = $obj['id'];
        }
        array_unshift($status, '_NOSTATUS_');

        if (!(in_array($aArgs['id_status'], $status))) {
            $errors[]= 'Invalid Status';
        }

        if ($mode == 'update') {
            if (!Validator::intVal()->validate($aArgs['id'])) {
                $errors[] = 'Id is not a numeric';
            } else {
                $obj = ActionModel::getById(['id' => $aArgs['id'], 'select' => [1]]);
            }
           
            if (empty($obj)) {
                $errors[] = 'Id ' .$aArgs['id']. ' does not exist';
            }
        }
           
        if (!Validator::notEmpty()->validate($aArgs['label_action']) ||
            !Validator::length(1, 255)->validate($aArgs['label_action'])) {
            $errors[] = 'Invalid label action';
        }
        /*if (!Validator::stringType()->notEmpty()->validate($aArgs['actionPageId'])) {
            $errors[] = 'Invalid page action';
        }*/

        if (!Validator::notEmpty()->validate($aArgs['id_status'])) {
            $errors[] = 'id_status is empty';
        }

        if (!Validator::notEmpty()->validate($aArgs['history']) || ($aArgs['history'] != 'Y' && $aArgs['history'] != 'N')) {
            $errors[]= 'Invalid history value';
        }

        return $errors;
    }

    public function initAction(Request $request, Response $response)
    {
        $obj['action']['history']          = true;
        $obj['action']['keyword']          = '';
        $obj['action']['actionPageId']     = 'confirm_status';
        $obj['action']['id_status']        = '_NOSTATUS_';
        $obj['categoriesList']             = ResModel::getCategories();

        foreach ($obj['categoriesList'] as $key => $value) {
            $obj['action']['actionCategories'][] = $value['id'];
        }

        $obj['statuses'] = StatusModel::get();
        array_unshift($obj['statuses'], ['id'=>'_NOSTATUS_','label_status'=> _UNCHANGED]);
        $obj['actionPages'] = ActionModel::getActionPages();
        $obj['keywordsList'] = ActionModel::getKeywords();
        
        return $response->withJson($obj);
    }

    protected function manageValue($request)
    {
        foreach ($request as $key => $value) {
            if (in_array($key, ['history'])) {
                if (empty($value)) {
                    $request[$key] = 'N';
                } else {
                    $request[$key] = 'Y';
                }
            }
        }
        return $request;
    }
}
