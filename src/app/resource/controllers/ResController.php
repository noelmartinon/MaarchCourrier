<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Resource Controller
* @author dev@maarch.org
*/

namespace Resource\controllers;

use Basket\models\BasketModel;
use Group\controllers\GroupController;
use Note\models\NoteModel;
use SrcCore\controllers\StoreController;
use Group\models\ServiceModel;
use Status\models\StatusModel;
use SrcCore\models\ValidatorModel;
use History\controllers\HistoryController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\PreparedClauseController;
use User\models\UserModel;
use Docserver\models\ResDocserverModel;

class ResController
{
    //*****************************************************************************************
    //LOG ONLY LOG FOR DEBUG
    // $file = fopen('storeResourceLogs.log', a);
    // fwrite($file, '[' . date('Y-m-d H:i:s') . '] new request' . PHP_EOL);
    // foreach ($data as $key => $value) {
    //     if ($key <> 'encodedFile') {
    //         fwrite($file, '[' . date('Y-m-d H:i:s') . '] ' . $key . ' : ' . $value . PHP_EOL);
    //     }
    // }
    // fclose($file);
    // ob_flush();
    // ob_start();
    // print_r($data);
    // file_put_contents("storeResourceLogs.log", ob_get_flush());
    //END LOG FOR DEBUG ONLY
    //*****************************************************************************************

    public function create(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'index_mlb', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::notEmpty()->validate($data['encodedFile']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['format']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['status']);
        $check = $check && Validator::intVal()->notEmpty()->validate($data['type_id']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['category_id']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $resId = StoreController::storeResource($data);
        if (empty($resId) || !empty($resId['errors'])) {
            return $response->withStatus(500)->withJson(['errors' => '[ResController create] ' . $resId['errors']]);
        }

        return $response->withJson(['resId' => $resId]);
    }

    public function createRes(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'index_mlb', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::notEmpty()->validate($data['encodedFile']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['fileFormat']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['status']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['collId']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['table']);
        $check = $check && Validator::arrayType()->notEmpty()->validate($data['data']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $resId = StoreController::storeResourceRes($data);

        if (empty($resId) || !empty($resId['errors'])) {
            return $response->withStatus(500)->withJson(['errors' => '[ResController create] ' . $resId['errors']]);
        }

        return $response->withJson(['resId' => $resId]);
    }

    public function createExt(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'index_mlb', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::intVal()->notEmpty()->validate($data['resId']);
        $check = $check && Validator::arrayType()->notEmpty()->validate($data['data']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $document = ResModel::getById(['resId' => $data['resId'], 'select' => ['1']]);
        if (empty($document)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document does not exist']);
        }
        $documentExt = ResModel::getExtById(['resId' => $data['resId'], 'select' => ['1']]);
        if (!empty($documentExt)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document already exists in mlb_coll_ext']);
        }

        $formatedData = StoreController::prepareExtStorage(['resId' => $data['resId'], 'data' => $data['data']]);

        $check = Validator::stringType()->notEmpty()->validate($formatedData['category_id']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        ResModel::createExt($formatedData);

        return $response->withJson(['status' => true]);
    }

    public function updateStatus(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (empty($data['status'])) {
            $data['status'] = 'COU';
        }
        if (empty(StatusModel::getById(['id' => $data['status']]))) {
            return $response->withStatus(400)->withJson(['errors' => _STATUS_NOT_FOUND]);
        }
        if (empty($data['historyMessage'])) {
            $data['historyMessage'] = _UPDATE_STATUS;
        }

        $check = Validator::arrayType()->notEmpty()->validate($data['chrono']) || Validator::arrayType()->notEmpty()->validate($data['resId']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['status']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['historyMessage']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $identifiers = !empty($data['chrono']) ? $data['chrono'] : $data['resId'];
        foreach ($identifiers as $id) {
            if (!empty($data['chrono'])) {
                $document = ResModel::getResIdByAltIdentifier(['altIdentifier' => $id]);
            } else {
                $document = ResModel::getById(['resId' => $id, 'select' => ['res_id']]);
            }
            if (empty($document)) {
                return $response->withStatus(400)->withJson(['errors' => _DOCUMENT_NOT_FOUND]);
            }
            if (!ResController::hasRightByResId(['resId' => $document['res_id'], 'userId' => $GLOBALS['userId']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }
    
            ResModel::update(['set' => ['status' => $data['status']], 'where' => ['res_id = ?'], 'data' => [$document['res_id']]]);
    
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $document['res_id'],
                'eventType' => 'UP',
                'info'      => $data['historyMessage'],
                'moduleId'  => 'apps',
                'eventId'   => 'resup',
            ]);
        }        

        return $response->withJson(['success' => 'success']);
    }

    public function updateExternalInfos(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (empty($data['externalInfos'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }
        if (empty($data['status'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        foreach ($data['externalInfos'] as $mail) {
            if(!Validator::intType()->validate($mail['res_id'])){
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: invalid res_id']);
            }
            if(!Validator::StringType()->notEmpty()->validate($mail['external_id'])){
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: invalid external_id for element : '.$mail['res_id']]);
            }
            if(!Validator::StringType()->notEmpty()->validate($mail['external_link'])){
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request:  invalid external_link for element'.$mail['res_id']]);
            }          
        }

        foreach ($data['externalInfos'] as $mail) {
            $document = ResModel::getById(['resId' => $mail['res_id'], 'select' => ['res_id']]);
            if (empty($document)) {
                return $response->withStatus(400)->withJson(['errors' => _DOCUMENT_NOT_FOUND]);
            }
            if (!ResController::hasRightByResId(['resId' => $document['res_id'], 'userId' => $GLOBALS['userId']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }
            ResModel::update(['set' => ['external_id' => $mail['external_id'] , 'external_link' => $mail['external_link'], 'status' => $data['status']], 'where' => ['res_id = ?'], 'data' => [$document['res_id']]]);
        }        

        return $response->withJson(['success' => 'success']);
    }

    public function isLock(Request $request, Response $response, array $aArgs)
    {
        return $response->withJson(ResModel::isLock(['resId' => $aArgs['resId'], 'userId' => $GLOBALS['userId']]));
    }

    public function getNotesCountForCurrentUserById(Request $request, Response $response, array $aArgs)
    {
        return $response->withJson(NoteModel::countByResId(['resId' => $aArgs['resId'], 'userId' => $GLOBALS['userId']]));
    }

    public static function hasRightByResId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId', 'userId']);
        ValidatorModel::stringType($aArgs, ['userId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        if ($aArgs['userId'] == 'superadmin') {
            return true;
        }
        $groups = UserModel::getGroupsByUserId(['userId' => $aArgs['userId']]);
        $groupsClause = '';
        foreach ($groups as $key => $group) {
            if (!empty($group['where_clause'])) {
                $groupClause = PreparedClauseController::getPreparedClause(['clause' => $group['where_clause'], 'userId' => $aArgs['userId']]);
                if ($key > 0) {
                    $groupsClause .= ' or ';
                }
                $groupsClause .= "({$groupClause})";
            }
        }

        if (!empty($groupsClause)) {
            $res = ResModel::getOnView(['select' => [1], 'where' => ['res_id = ?', "({$groupsClause})"], 'data' => [$aArgs['resId']]]);
            if (!empty($res)) {
                return true;
            }
        }

        $baskets = BasketModel::getBasketsByUserId(['userId' => $aArgs['userId'], 'unneededBasketId' => ['IndexingBasket']]);
        $basketsClause = '';
        foreach ($baskets as $key => $basket) {
            if (!empty($basket['basket_clause'])) {
                $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'userId' => $basket['basket_owner']]);
                if ($key > 0) {
                    $basketsClause .= ' or ';
                }
                $basketsClause .= "({$basketClause})";
            }
        }

        if (!empty($basketsClause)) {
            $res = ResModel::getOnView(['select' => [1], 'where' => ['res_id = ?', "({$basketsClause})"], 'data' => [$aArgs['resId']]]);
            if (!empty($res)) {
                return true;
            }
        }

        return false;
    }

    public function getList(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (!Validator::stringType()->notEmpty()->validate($data['select'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request: select is not valid']);
        }
        if (!Validator::stringType()->notEmpty()->validate($data['clause'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request: clause is not valid']);
        }
        if (!empty($data['withFile'])) {
            if(!Validator::boolType()->validate($data['withFile'])){
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: withFile parameter is not a boolean']);
            }            
        }

        if (!empty($data['orderBy'])) {
            if (!Validator::arrayType()->notEmpty()->validate($data['orderBy'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: orderBy parameter not valid']);
            }            
        }

        if (!empty($data['limit'])) {
            if (!Validator::intType()->validate($data['limit'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: limit parameter not valid']);
            }
        }
        $orderBy = $data['orderBy'];
        $limit = $data['limit'];
        $select = explode(',', $data['select']);
        
        if (!PreparedClauseController::isRequestValid(['select' => $select,'clause' => $data['clause'], 'orderBy' => $orderBy, 'limit' => $limit, 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(400)->withJson(['errors' => _INVALID_REQUEST]);
        }

        $where = [$data['clause']];
        if ($GLOBALS['userId'] != 'superadmin') {
            $groupsClause = GroupController::getGroupsClause(['userId' => $GLOBALS['userId']]);
            if (empty($groupsClause)) {
                return $response->withStatus(400)->withJson(['errors' => 'User has no groups']);
            }
            $where[] = "({$groupsClause})";
        }

        if($data['withFile'] === true){
            $select[] = 'res_id';            
        }

        $resources = ResModel::getOnView(['select' => $select, 'where' => $where, 'orderBy' => $orderBy, 'limit' => $limit]);
        if ($data['withFile'] === true) {
            foreach ($resources as $key => $res) {
                $path = ResDocserverModel::getSourceResourcePath(['resId' => $res['res_id'], 'resTable' => 'res_letterbox', 'adrTable' => 'null']);
                $file = file_get_contents($path);
                $base64Content = base64_encode($file);
                $resources[$key]['fileBase64Content'] = $base64Content;
            }
        }

        return $response->withJson(['resources' => $resources, 'count' => count($resources)]);
    }
}
