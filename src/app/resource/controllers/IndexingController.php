<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Indexing Controller
* @author dev@maarch.org
*/

namespace Resource\controllers;

use Action\models\ActionModel;
use Doctype\models\DoctypeModel;
use Entity\models\EntityModel;
use Group\models\GroupModel;
use Priority\models\PriorityModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\ValidatorModel;

class IndexingController
{
    public function getIndexingActions(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->notEmpty()->validate($aArgs['groupId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Param groupId must be an integer val']);
        }

        $indexingParameters = IndexingController::getIndexingParameters(['login' => $GLOBALS['userId'], 'groupId' => $aArgs['groupId']]);
        if (!empty($indexingParameters['errors'])) {
            return $response->withStatus(403)->withJson($indexingParameters);
        }

        $actions = [];
        foreach ($indexingParameters['indexingParameters']['actions'] as $value) {
            $actions[] = ActionModel::getById(['id' => $value, 'select' => ['id', 'label_action', 'component']]);
        }

        return $response->withJson(['actions' => $actions]);
    }

    public function getIndexingEntities(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->notEmpty()->validate($aArgs['groupId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Param groupId must be an integer val']);
        }

        $indexingParameters = IndexingController::getIndexingParameters(['login' => $GLOBALS['userId'], 'groupId' => $aArgs['groupId']]);
        if (!empty($indexingParameters['errors'])) {
            return $response->withStatus(403)->withJson($indexingParameters);
        }

        $keywords = [
            'ALL_ENTITIES'          => '@all_entities',
            'ENTITIES_JUST_BELOW'   => '@immediate_children[@my_primary_entity]',
            'ENTITIES_BELOW'        => '@subentities[@my_entities]',
            'ALL_ENTITIES_BELOW'    => '@subentities[@my_primary_entity]',
            'ENTITIES_JUST_UP'      => '@parent_entity[@my_primary_entity]',
            'MY_ENTITIES'           => '@my_entities',
            'MY_PRIMARY_ENTITY'     => '@my_primary_entity',
            'SAME_LEVEL_ENTITIES'   => '@sisters_entities[@my_primary_entity]'
        ];

        $allowedEntities = [];
        $clauseToProcess = '';

        foreach ($indexingParameters['indexingParameters']['keywords'] as $keywordValue) {
            if (!empty($clauseToProcess)) {
                $clauseToProcess .= ', ';
            }
            $clauseToProcess .= $keywords[$keywordValue];
        }

        if (!empty($clauseToProcess)) {
            $preparedClause = PreparedClauseController::getPreparedClause(['clause' => $clauseToProcess, 'login' => $GLOBALS['userId']]);
            $preparedEntities = EntityModel::get(['select' => ['id'], 'where' => ['enabled = ?', "entity_id in {$preparedClause}"], 'data' => ['Y']]);
            $allowedEntities = array_column($preparedEntities, 'id');
        }

        $allowedEntities = array_merge($indexingParameters['indexingParameters']['entities'], $allowedEntities);
        $allowedEntities = array_unique($allowedEntities);

        $entitiesTmp = EntityModel::get([
            'select'   => ['id', 'entity_label', 'entity_id'], 
            'where'    => ['enabled = ?', '(parent_entity_id is null OR parent_entity_id = \'\')'], 
            'data'     => ['Y'],
            'orderBy'  => ['entity_label']
        ]);
        if (!empty($entitiesTmp)) {
            foreach ($entitiesTmp as $key => $value) {
                $entitiesTmp[$key]['level'] = 0;
            }
            $entitiesId = array_column($entitiesTmp, 'entity_id');
            $entitiesChild = IndexingController::getEntitiesChildrenLevel(['entitiesId' => $entitiesId, 'level' => 1]);
            $entitiesTmp = array_merge([$entitiesTmp], $entitiesChild);
        }

        $entities = [];
        foreach ($entitiesTmp as $keyLevel => $levels) {
            foreach ($levels as $entity) {
                if (in_array($entity['id'], $allowedEntities)) {
                    $entity['enabled'] = true;
                } else {
                    $entity['enabled'] = false;
                }
                if ($keyLevel == 0) {
                    $entities[] = $entity;
                    continue;
                } else {
                    foreach ($entities as $key => $oEntity) {
                        if ($oEntity['entity_id'] == $entity['parent_entity_id']) {
                            array_splice($entities, $key+1, 0, [$entity]);
                            continue;
                        }
                    }
                }
            }
        }

        return $response->withJson(['entities' => $entities]);
    }

    public function getProcessLimitDate(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        if (!empty($queryParams['doctype'])) {
            $doctype = DoctypeModel::getById(['id' => $queryParams['doctype'], 'select' => ['process_delay']]);
            $delay = $doctype['process_delay'];
        } elseif (!empty($queryParams['priority'])) {
            $priority = PriorityModel::getById(['id' => $queryParams['priority'], 'select' => ['delays']]);
            $delay = $priority['delays'];
        }
        if (empty($delay)) {
            return $response->withStatus(400)->withJson(['errors' => 'Delay is empty']);
        }

        $processLimitDate = IndexingController::calculateProcessDate(['date' => date('c'), 'delay' => $delay]);

        return $response->withJson(['processLimitDate' => $processLimitDate]);
    }

    public static function getEntitiesChildrenLevel($aArgs = [])
    {
        $entities = EntityModel::getEntityChildrenSubLevel([
            'entitiesId' => $aArgs['entitiesId'],
            'select'     => ['id', 'entity_label', 'entity_id', 'parent_entity_id'],
            'orderBy'    => ['entity_label desc']
        ]);
        if (!empty($entities)) {
            foreach ($entities as $key => $value) {
                $entities[$key]['level'] = $aArgs['level'];
            }
            $entitiesId = array_column($entities, 'entity_id');
            $entitiesChild = IndexingController::getEntitiesChildrenLevel(['entitiesId' => $entitiesId, 'level' => $aArgs['level']+1]);
            $entities = array_merge([$entities], $entitiesChild);
        }

        return $entities;
    }

    public static function getIndexingParameters($aArgs = [])
    {
        $group = GroupModel::getGroupByLogin(['login' => $aArgs['login'], 'groupId' => $aArgs['groupId'], 'select' => ['can_index', 'indexation_parameters']]);
        if (empty($group)) {
            return ['errors' => 'This user is not in this group'];
        }
        if (!$group[0]['can_index']) {
            return ['errors' => 'This group can not index document'];
        }

        $group[0]['indexation_parameters'] = json_decode($group[0]['indexation_parameters'], true);

        return ['indexingParameters' => $group[0]['indexation_parameters']];
    }

    public static function calculateProcessDate(array $args)
    {
        ValidatorModel::notEmpty($args, ['date', 'delay']);
        ValidatorModel::intVal($args, ['delay']);

        $date = new \DateTime($args['date']);

        $calendarType = 'calendar';

        if ($calendarType == 'workingDay') {
            $hollidays = [
                '01-01',
                '01-05',
                '08-05',
                '14-07',
                '15-08',
                '01-11',
                '11-11',
                '25-12'
            ];
            if (function_exists('easter_date')) {
                $hollidays[] = date('d-m', easter_date() + 86400);
            }

            $processDelayUpdated = 1;
            for ($i = 1; $i <= $args['delay']; $i++) {
                $tmpDate = new \DateTime($args['delay']);
                $tmpDate->add(new \DateInterval("P{$i}D"));
                if (in_array($tmpDate->format('N'), [6, 7]) || in_array($tmpDate->format('d-m'), $hollidays)) {
                    ++$args['delay'];
                }
                ++$processDelayUpdated;
            }

            $date->add(new \DateInterval("P{$processDelayUpdated}D"));
        } else {
            $date->add(new \DateInterval("P{$args['delay']}D"));
        }

        return $date->format('Y-m-d H:i:s');
    }
}
