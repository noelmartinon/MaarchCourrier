<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief List Instance History Controller
 * @author dev@maarch.org
 */

namespace Entity\controllers;

use Entity\models\EntityModel;
use Entity\models\ListInstanceHistoryDetailModel;
use Entity\models\ListInstanceHistoryModel;
use Resource\controllers\ResController;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use User\models\UserModel;

class ListInstanceHistoryController
{
    public function getByResId(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $queryParams = $request->getQueryParams();

        $listInstancesModification = ListInstanceHistoryModel::get(['select' => ['listinstance_history_id', 'updated_date', 'user_id'], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);

        $formattedHistory = [];
        foreach ($listInstancesModification as $value) {
            $where = ['listinstance_history_id = ?'];
            $data = [$value['listinstance_history_id']];
            if (!empty($queryParams['type']) && in_array($queryParams['type'], ['diffusionList', 'visaCircuit', 'opinionCircuit'])) {
                $where[] = 'difflist_type = ?';
                $data[] = str_replace(['diffusionList', 'visaCircuit', 'opinionCircuit'], ['entity_id', 'VISA_CIRCUIT', 'AVIS_CIRCUIT'], $queryParams['type']);
            }
            $listInstancesDetails = ListInstanceHistoryDetailModel::get([
                'select'    => ['*'],
                'where'     => $where,
                'data'      => $data
            ]);
            foreach ($listInstancesDetails as $key => $listInstancesDetail) {
                if ($value['item_type'] == 'entity_id') {
                    $entity = EntityModel::getById(['id' => $listInstancesDetail['item_id'], 'select' => ['entity_label', 'entity_id']]);
                    $listInstances[$key]['item_id'] = $entity['entity_id'];
                    $listInstances[$key]['itemSerialId'] = $listInstancesDetail['item_id'];
                    $listInstances[$key]['labelToDisplay'] = $entity['entity_label'];
                    $listInstances[$key]['descriptionToDisplay'] = '';
                } else {
                    $listInstances[$key]['itemSerialId'] = $listInstancesDetail['item_id'];
                    $listInstances[$key]['labelToDisplay'] = UserModel::getLabelledUserById(['id' => $listInstancesDetail['item_id']]);
                    $listInstances[$key]['descriptionToDisplay'] = UserModel::getPrimaryEntityById(['id' => $listInstancesDetail['item_id'], 'select' => ['entities.entity_label']])['entity_label'];
                }
            }
            if (!empty($listInstancesDetails)) {
                $formattedHistory[] = [
                    'userId'            => $value['user_id'],
                    'user'              => UserModel::getLabelledUserById(['id' => $value['user_id']]),
                    'modificationDate'  => $value['updated_date'],
                    'details'           => $listInstancesDetails
                ];
            }
        }

        return $response->withJson(['listInstanceHistory' => $formattedHistory]);
    }
}
