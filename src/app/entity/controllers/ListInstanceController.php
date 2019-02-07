<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief List Instance Controller
 * @author dev@maarch.org
 */

namespace Entity\controllers;

use Entity\models\ListInstanceModel;
use Slim\Http\Request;
use Slim\Http\Response;
use Respect\Validation\Validator;
use Resource\controllers\ResController;
use Entity\models\EntityModel;
use SrcCore\models\DatabaseModel;
use User\models\UserModel;
use Resource\models\ResModel;
use Group\models\ServiceModel;

class ListInstanceController
{
    public function getById(Request $request, Response $response, array $aArgs)
    {
        $listinstance = ListInstanceModel::getById(['id' => $aArgs['id']]);

        return $response->withJson($listinstance);
    }

    public function getListByResId(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => $aArgs['resId'], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }
        $listinstances = ListInstanceModel::getListByResId(['select' => ['listinstance_id', 'sequence', 'CASE WHEN item_mode=\'cc\' THEN \'copy\' ELSE item_mode END', 'item_id', 'item_type', 'firstname as item_firstname', 'lastname as item_lastname', 'entity_label as item_entity', 'viewed', 'process_date', 'process_comment', 'signatory', 'requested_signature'], 'id' => $aArgs['resId']]);
        
        $roles = EntityModel::getRoles();
        
        foreach ($listinstances as $key2 => $listinstance) {
            foreach ($roles as $key => $role) {
                if ($role['id'] == $listinstance['item_mode']) {
                    $listinstancesFormat[$role['label']][] = $listinstance;
                }
            }
        }
        return $response->withJson($listinstancesFormat);
    }

    public function getVisaCircuitByResId(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => $aArgs['resId'], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }
        $listinstances = ListInstanceModel::getVisaCircuitByResId(['select' => ['listinstance_id', 'sequence', 'item_id', 'item_type', 'firstname as item_firstname', 'lastname as item_lastname', 'entity_label as item_entity', 'viewed', 'process_date', 'process_comment', 'signatory', 'requested_signature'], 'id' => $aArgs['resId']]);
        
        return $response->withJson($listinstances);
    }

    public function getAvisCircuitByResId(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => $aArgs['resId'], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }
        $listinstances = ListInstanceModel::getAvisCircuitByResId(['select' => ['listinstance_id', 'sequence', 'item_id', 'item_type', 'firstname as item_firstname', 'lastname as item_lastname', 'entity_label as item_entity', 'viewed', 'process_date', 'process_comment'], 'id' => $aArgs['resId']]);
        
        return $response->withJson($listinstances);
    }

    public function getListWhereUserIsDest(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_users', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }
        
        $data = ListInstanceModel::getListWhereUserIsDest(['select' => ['li.*'], 'id' => $aArgs['itemId']]);

        $listinstances = [];

        if (!empty($data)) {
            $res_id = 0;
            $array = [];
            foreach ($data as $value) {
                if ($res_id == 0) {
                    $res_id = $value['res_id'];
                } elseif ($res_id != $value['res_id']) {
                    $listinstances[] = ['resId' => $res_id, "listinstances" => $array];
                    $res_id = $value['res_id'];
                    $array = [];
                }
                array_push($array, $value);
            }
            $listinstances[] = ['resId' => $res_id, "listinstances" => $array];
        }
            
        return $response->withJson(['listinstances' => $listinstances]);
    }

    public function update(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (empty($data['listinstances'])) {
            return $response->withStatus(400)->withJson(['errors' => 'listinstances is missing or is empty']);
        }

        foreach ($data['listinstances'] as $ListInstanceByRes) {
            foreach ($ListInstanceByRes['listinstances'] as $instance) {
                if (empty($instance['res_id']) || empty($instance['item_id']) || empty($instance['item_type']) || empty($instance['item_mode']) || empty($instance['difflist_type'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Some data are empty']);
                }
                
                if (isset($instance['listinstance_id']) && !empty($instance['listinstance_id'])) {
                    $check = ListInstanceModel::getById(['select' => ['listinstance_id'], 'id' => $instance['listinstance_id']]);
                    if (!$check) {
                        return $response->withStatus(400)->withJson(['errors' => 'listinstance_id is not correct']);
                    }
    
                    ListInstanceModel::delete(['listinstance_id' => $instance['listinstance_id']]);
                }
                
                if($instance['item_type'] == 'user_id'){
                    $user = UserModel::getByUserId(['userId' => $instance['item_id']]);
                    if (empty($user) || $user['status'] != "OK") {
                        return $response->withStatus(400)->withJson(['errors' => 'User not found or not active']);
                    }
                } 

                unset($instance['listinstance_id']);
                unset($instance['requested_signature']);
                unset($instance['signatory']);
                
                ListInstanceModel::create($instance);

                if ($instance['item_mode'] == 'dest') {
                    ResModel::update([
                        'set'   => ['dest_user' => $instance['item_id']],
                        'where' => ['res_id = ?'],
                        'data'  => [$instance['res_id']]
                    ]);
                }
            }
        }

        return $response->withJson(['success' => 'success']);
    }
}
