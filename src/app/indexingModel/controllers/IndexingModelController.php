<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ParametersController
* @author  dev <dev@maarch.org>
* @ingroup core
*/

/**
 * @brief Indexing Model Controller
 * @author dev@maarch.org
 */

namespace IndexingModel\controllers;

use CustomField\models\CustomFieldModel;
use Entity\models\EntityModel;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use IndexingModel\models\IndexingModelFieldModel;
use IndexingModel\models\IndexingModelModel;
use Resource\controllers\IndexingController;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use Tag\models\ResourceTagModel;

class IndexingModelController
{
    const INDEXABLE_DATES = ['documentDate', 'departureDate', 'arrivalDate', 'processLimitDate'];

    public function get(Request $request, Response $response)
    {
        $query = $request->getQueryParams();
        $where = ['(owner = ? OR private = ?)'];

        $showDisabled = false;
        if (Validator::notEmpty()->validate($query['showDisabled'])) {
            $showDisabled = $query['showDisabled'] == 'true';
        }

        if (!$showDisabled) {
            $where[] = 'enabled = TRUE';
        } elseif (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_indexing_models', 'userId' => $GLOBALS['id']])) {
            $where[] = 'enabled = TRUE';
        }

        $models = IndexingModelModel::get(['where' => $where, 'data' => [$GLOBALS['id'], 'false']]);

        foreach ($models as $key => $value) {
            $resources = ResModel::get([
                'select'  => ['status', 'count(1)'],
                'where'   => ['model_id = ?'],
                'data'    => [$value['id']],
                'groupBy' => ['status']
            ]);
            $models[$key]['used'] = $resources;
        }

        return $response->withJson(['indexingModels' => $models]);
    }

    public function getById(Request $request, Response $response, array $args)
    {
        $model = IndexingModelModel::getById(['id' => $args['id']]);
        if (empty($model)) {
            return $response->withStatus(400)->withJson(['errors' => 'Model not found']);
        } elseif ($model['private'] && $model['owner'] != $GLOBALS['id']) {
            return $response->withStatus(400)->withJson(['errors' => 'Model out of perimeter']);
        }

        $fields = IndexingModelFieldModel::get(['select' => ['identifier', 'mandatory', 'default_value', 'unit'], 'where' => ['model_id = ?'], 'data' => [$args['id']]]);
        foreach ($fields as $key => $value) {
            $fields[$key]['default_value'] = json_decode($value['default_value'], true);
        }
        $model['fields'] = $fields;

        return $response->withJson(['indexingModel' => $model]);
    }

    public function create(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $categories = ResModel::getCategories();
        $categories = array_column($categories, 'id');

        if (!Validator::stringType()->notEmpty()->length(1, 256)->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string or more than 256 characters']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['category']) || !in_array($body['category'], $categories)) {
            return $response->withStatus(400)->withJson(['errors' => "Body category is empty, not a string or not a valid category"]);
        } elseif (!Validator::arrayType()->notEmpty()->validate($body['fields'])) {
            return $response->withStatus(400)->withJson(['errors' => "Body fields is empty or not an array"]);
        }

        $foundDoctype = false;
        $foundSubject = false;

        foreach ($body['fields'] as $key => $field) {
            if (!Validator::stringType()->notEmpty()->validate($field['identifier'])) {
                return $response->withStatus(400)->withJson(['errors' => "Body fields[{$key}] identifier is empty or not a string"]);
            }

            if ($field['identifier'] == 'doctype') {
                $foundDoctype = true;
            } elseif ($field['identifier'] == 'subject') {
                $foundSubject = true;
            }
        }

        if (!$foundDoctype) {
            return $response->withStatus(400)->withJson(['errors' => "Mandatory 'doctype' field is missing"]);
        }
        if (!$foundSubject) {
            return $response->withStatus(400)->withJson(['errors' => "Mandatory 'subject' field is missing"]);
        }

        $master = null;
        if (Validator::intVal()->notEmpty()->validate($body['master'])) {
            $masterModel = IndexingModelModel::getById(['id' => $body['master']]);
            if (empty($masterModel)) {
                return $response->withStatus(400)->withJson(['errors' => 'Master model not found']);
            }

            if ($masterModel['private']) {
                return $response->withStatus(400)->withJson(['errors' => 'Master model is a private model']);
            }
            $master = $body['master'];

            $fieldsMaster = IndexingModelFieldModel::get(['select' => ['identifier', 'mandatory', 'default_value', 'unit'], 'where' => ['model_id = ?'], 'data' => [$body['master']]]);
            foreach ($fieldsMaster as $key => $value) {
                $fieldsMaster[$key]['default_value'] = json_decode($value['default_value'], true);
            }


            // Look for fields in master model
            // if field in master is not in child, return an error
            // if field is not in master but in child, is ignored
            $arrayTmp = [];
            foreach ($fieldsMaster as $field) {
                $found = false;
                foreach ($body['fields'] as $value) {
                    if ($value['identifier'] == $field['identifier'] && $value['mandatory'] == $field['mandatory'] && $value['unit'] == $field['unit']) {
                        array_push($arrayTmp, $value);
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    return $response->withStatus(400)->withJson(['errors' => "Field '" . $field['identifier'] . "' from master model is missing"]);
                }
            }
            $body['fields'] = $arrayTmp;
        }

        if (PrivilegeController::hasPrivilege(['privilegeId' => 'admin_indexing_models', 'userId' => $GLOBALS['id']])) {
            $body['private'] = empty($body['private']) ? 'false' : 'true';
            $defaultModel = IndexingModelModel::get(['select' => [1], 'where' => ['"default" = ?'], 'data' => ['true']]);
            $body['default'] = empty($defaultModel) ? 'true' : 'false';
        } else {
            $body['private'] = 'true';
            $body['default'] = 'false';
        }

        $modelId = IndexingModelModel::create([
            'label'     => $body['label'],
            'category'  => $body['category'],
            'default'   => $body['default'],
            'owner'     => $GLOBALS['id'],
            'private'   => $body['private'],
            'master'    => $master
        ]);

        foreach ($body['fields'] as $field) {
            if (in_array($field['identifier'], IndexingModelController::INDEXABLE_DATES) && !empty($field['default_value']) && $field['default_value'] != '_TODAY') {
                $date = new \DateTime($field['default_value']);
                $field['default_value'] = $date->format('Y-m-d');
            }
            if (strpos($field['identifier'], 'indexingCustomField_') !== false && !empty($field['default_value']) && $field['default_value'] != '_TODAY') {
                $customFieldId = explode('_', $field['identifier'])[1];
                $customField = CustomFieldModel::getById(['id' => $customFieldId, 'select' => ['type']]);
                if ($customField['type'] == 'date') {
                    $date = new \DateTime($field['default_value']);
                    $field['default_value'] = $date->format('Y-m-d');
                }
            }
            IndexingModelFieldModel::create([
                'model_id'      => $modelId,
                'identifier'    => $field['identifier'],
                'mandatory'     => empty($field['mandatory']) ? 'false' : 'true',
                'default_value' => !isset($field['default_value']) ? null : json_encode($field['default_value']),
                'unit'          => $field['unit']
            ]);
        }

        HistoryController::add([
            'tableName' => 'indexing_models',
            'recordId'  => $modelId,
            'eventType' => 'ADD',
            'info'      => _INDEXINGMODEL_CREATION . " : {$body['label']}",
            'moduleId'  => 'indexingModel',
            'eventId'   => 'indexingModelCreation',
        ]);

        return $response->withJson(['id' => $modelId]);
    }

    public function update(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_indexing_models', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (!Validator::intVal()->notEmpty()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Param id is empty or not an integer']);
        }
        if (!Validator::stringType()->notEmpty()->length(1, 256)->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string or more than 256 characters']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['category'])) {
            return $response->withStatus(400)->withJson(['errors' => "Body category is empty or not a string"]);
        } elseif (!Validator::boolType()->validate($body['default'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body default is empty or not a boolean']);
        }

        $foundDoctype = false;
        $foundSubject = false;

        foreach ($body['fields'] as $key => $field) {
            if (!Validator::stringType()->notEmpty()->validate($field['identifier'])) {
                return $response->withStatus(400)->withJson(['errors' => "Body fields[{$key}] identifier is empty or not a string"]);
            }

            if ($field['identifier'] == 'doctype') {
                $foundDoctype = true;
            } elseif ($field['identifier'] == 'subject') {
                $foundSubject = true;
            } elseif ($field['identifier'] == 'initiator') {
                unset($body['fields'][$key]['default_value']);
            }
        }

        if (!$foundDoctype) {
            return $response->withStatus(400)->withJson(['errors' => "Mandatory 'doctype' field is missing"]);
        }
        if (!$foundSubject) {
            return $response->withStatus(400)->withJson(['errors' => "Mandatory 'subject' field is missing"]);
        }

        $model = IndexingModelModel::getById(['select' => ['owner', 'private'], 'id' => $args['id']]);
        if (empty($model)) {
            return $response->withStatus(400)->withJson(['errors' => 'Model not found']);
        } elseif ($model['private']) {
            return $response->withStatus(400)->withJson(['errors' => 'Model out of perimeter']);
        }

        if ($body['default']) {
            IndexingModelModel::update(['set' => ['"default"' => 'false'], 'where' => ['"default" = ?'], 'data' => ['true']]);
        }

        IndexingModelModel::update([
            'set'   => [
                'label'     => $body['label'],
                'category'  => $body['category'],
                '"default"' => $body['default'] ? 'true' : 'false'
            ],
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        $childrenModels = IndexingModelModel::get(['select' => ['id', 'label'], 'where' => ['master = ?'], 'data' => [$args['id']]]);

        // If model has children, update the children
        if (!empty($childrenModels)) {
            // Update children models of master
            foreach ($childrenModels as $child) {
                $childFields = IndexingModelFieldModel::get(['select' => ['identifier', 'mandatory', 'default_value', 'unit'], 'where' => ['model_id = ?'], 'data' => [$child['id']]]);
                foreach ($childFields as $key => $value) {
                    $childFields[$key]['default_value'] = json_decode($value['default_value'], true);
                }

                // Look for fields in master model
                $fieldsToKeep = [];
                foreach ($body['fields'] as $field) {
                    $found = false;
                    foreach ($childFields as $value) {
                        if ($value['identifier'] == $field['identifier'] && $value['mandatory'] == $field['mandatory'] && $value['unit'] == $field['unit']) {
                            $fieldsToKeep[] = $value;
                            $found = true;
                        }
                    }
                    if (!$found) {
                        $fieldsToKeep[] = $field;
                    }
                }

                IndexingModelFieldModel::delete(['where' => ['model_id = ?'], 'data' => [$child['id']]]);

                foreach ($fieldsToKeep as $field) {
                    if (in_array($field['identifier'], IndexingModelController::INDEXABLE_DATES) && !empty($field['default_value']) && $field['default_value'] != '_TODAY') {
                        $date = new \DateTime($field['default_value']);
                        $field['default_value'] = $date->format('Y-m-d');
                    }
                    if (strpos($field['identifier'], 'indexingCustomField_') !== false && !empty($field['default_value']) && $field['default_value'] != '_TODAY') {
                        $customFieldId = explode('_', $field['identifier'])[1];
                        $customField = CustomFieldModel::getById(['id' => $customFieldId, 'select' => ['type']]);
                        if ($customField['type'] == 'date') {
                            $date = new \DateTime($field['default_value']);
                            $field['default_value'] = $date->format('Y-m-d');
                        }
                    }
                    IndexingModelFieldModel::create([
                        'model_id'      => $child['id'],
                        'identifier'    => $field['identifier'],
                        'mandatory'     => empty($field['mandatory']) ? 'false' : 'true',
                        'default_value' => !isset($field['default_value']) ? null : json_encode($field['default_value']),
                        'unit'          => $field['unit']
                    ]);
                }

                HistoryController::add([
                    'tableName' => 'indexing_models',
                    'recordId'  => $child['id'],
                    'eventType' => 'UP',
                    'info'      => _INDEXINGMODEL_MODIFICATION . " : {$child['label']}",
                    'moduleId'  => 'indexingModel',
                    'eventId'   => 'indexingModelModification',
                ]);
            }
        }

        $allResourcesUsingModel = ResModel::get(['select' => ['res_id'], 'where' => ['model_id = ?'], 'data' => [$args['id']]]);
        $allResourcesUsingModel = array_column($allResourcesUsingModel, 'res_id');

        if (!empty($allResourcesUsingModel)) {
            $oldFieldList = IndexingModelFieldModel::get(['select' => ['identifier'], 'where' => ['model_id = ?'], 'data' => [$args['id']]]);
            $oldFieldList = array_column($oldFieldList, 'identifier');

            $newFieldList = array_column($body['fields'], 'identifier');

            ResController::resetResourceFields(['oldFieldList' => $oldFieldList, 'newFieldList' => $newFieldList, 'modelId' => $args['id']]);

            $fieldsToDelete = array_diff($oldFieldList, $newFieldList);

            if (in_array('senders', $fieldsToDelete)) {
                ResourceContactModel::delete(['where' => ['res_id in (?)',  'mode = ?'], 'data' => [$allResourcesUsingModel, 'sender']]);
            }
            if (in_array('recipients', $fieldsToDelete)) {
                ResourceContactModel::delete(['where' => ['res_id in (?)',  'mode = ?'], 'data' => [$allResourcesUsingModel, 'recipient']]);
            }
            if (in_array('tags', $fieldsToDelete)) {
                ResourceTagModel::delete(['where' => ['res_id in (?)'], 'data' => [$allResourcesUsingModel]]);
            }
        }

        IndexingModelFieldModel::delete(['where' => ['model_id = ?'], 'data' => [$args['id']]]);

        foreach ($body['fields'] as $field) {
            if (in_array($field['identifier'], IndexingModelController::INDEXABLE_DATES) && !empty($field['default_value']) && $field['default_value'] != '_TODAY') {
                $date = new \DateTime($field['default_value']);
                $field['default_value'] = $date->format('Y-m-d');
            }
            if (strpos($field['identifier'], 'indexingCustomField_') !== false && !empty($field['default_value']) && $field['default_value'] != '_TODAY') {
                $customFieldId = explode('_', $field['identifier'])[1];
                $customField = CustomFieldModel::getById(['id' => $customFieldId, 'select' => ['type']]);
                if ($customField['type'] == 'date') {
                    $date = new \DateTime($field['default_value']);
                    $field['default_value'] = $date->format('Y-m-d');
                }
            }
            IndexingModelFieldModel::create([
                'model_id'      => $args['id'],
                'identifier'    => $field['identifier'],
                'mandatory'     => empty($field['mandatory']) ? 'false' : 'true',
                'default_value' => !isset($field['default_value']) ? null : json_encode($field['default_value']),
                'unit'          => $field['unit']
            ]);
        }

        HistoryController::add([
            'tableName' => 'indexing_models',
            'recordId'  => $args['id'],
            'eventType' => 'UP',
            'info'      => _INDEXINGMODEL_MODIFICATION . " : {$body['label']}",
            'moduleId'  => 'indexingModel',
            'eventId'   => 'indexingModelModification',
        ]);

        return $response->withStatus(204);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->notEmpty()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Param id is empty or not an integer']);
        }
        $model = IndexingModelModel::getById(['select' => ['owner', 'private', '"default"', 'label'], 'id' => $args['id']]);
        if (empty($model)) {
            return $response->withStatus(400)->withJson(['errors' => 'Model not found']);
        } elseif ($model['private'] && $model['owner'] != $GLOBALS['id']) {
            return $response->withStatus(400)->withJson(['errors' => 'Model out of perimeter']);
        } elseif (!$model['private'] && !PrivilegeController::hasPrivilege(['privilegeId' => 'admin_indexing_models', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Model out of perimeter']);
        } elseif ($model['default']) {
            return $response->withStatus(400)->withJson(['errors' => 'Default model can not be deleted']);
        }

        $resources = ResModel::get([
            'select' => ['res_id'],
            'where'  => ['model_id = ?'],
            'data'   => [$args['id']]
        ]);
        $resources = array_column($resources, 'res_id');
        if (!empty($resources)) {
            $body = $request->getParsedBody();

            // No targetId provided, trying to delete without redirection
            if (!Validator::intVal()->notEmpty()->validate($body['targetId'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Model is used by at least one resource', 'lang' => 'modelUsedByResources']);
            }

            // targetId provided, redirect model before deleting
            $oldFieldList = IndexingModelFieldModel::get(['select' => ['identifier'], 'where' => ['model_id = ?'], 'data' => [$args['id']]]);
            $oldFieldList = array_column($oldFieldList, 'identifier');

            $newFieldList = IndexingModelFieldModel::get(['select' => ['identifier'], 'where' => ['model_id = ?'], 'data' => [$body['targetId']]]);
            $newFieldList = array_column($newFieldList, 'identifier');

            ResController::resetResourceFields(['oldFieldList' => $oldFieldList, 'newFieldList' => $newFieldList, 'modelId' => $args['id']]);

            $fieldsToDelete = array_diff($oldFieldList, $newFieldList);

            if (in_array('senders', $fieldsToDelete)) {
                ResourceContactModel::delete(['where' => ['res_id in (?)',  'mode = ?'], 'data' => [$resources, 'sender']]);
            }
            if (in_array('recipients', $fieldsToDelete)) {
                ResourceContactModel::delete(['where' => ['res_id in (?)',  'mode = ?'], 'data' => [$resources, 'recipient']]);
            }
            if (in_array('tags', $fieldsToDelete)) {
                ResourceTagModel::delete(['where' => ['res_id in (?)'], 'data' => [$resources]]);
            }

            ResModel::update([
                'set'   => ['model_id' => $body['targetId']],
                'where' => ['model_id = ?'],
                'data'  => [$args['id']]
            ]);
        }

        $childrenModels = IndexingModelModel::get(['select' => ['id', 'label'], 'where' => ['"master" = ?'], 'data' => [$args['id']]]);

        if (!empty($childrenModels)) {
            foreach ($childrenModels as $child) {
                IndexingModelFieldModel::delete(['where' => ['model_id = ?'], 'data' => [$child['id']]]);

                HistoryController::add([
                    'tableName' => 'indexing_models',
                    'recordId'  => $child['id'],
                    'eventType' => 'DEL',
                    'info'      => _INDEXINGMODEL_SUPPRESSION . " : {$child['label']}",
                    'moduleId'  => 'indexingModel',
                    'eventId'   => 'indexingModelSuppression',
                ]);
            }
        }

        IndexingModelModel::delete([
            'where' => ['(id = ? or master = ?)'],
            'data'  => [$args['id'], $args['id']]
        ]);

        IndexingModelFieldModel::delete(['where' => ['model_id = ?'], 'data' => [$args['id']]]);

        HistoryController::add([
            'tableName' => 'indexing_models',
            'recordId'  => $args['id'],
            'eventType' => 'DEL',
            'info'      => _INDEXINGMODEL_SUPPRESSION . " : {$model['label']}",
            'moduleId'  => 'indexingModel',
            'eventId'   => 'indexingModelSuppression',
        ]);

        return $response->withStatus(204);
    }

    public function disable(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_indexing_models', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->notEmpty()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is empty or not an integer']);
        }

        $model = IndexingModelModel::getById(['select' => ['enabled', '"default"'], 'id' => $args['id']]);
        if (empty($model)) {
            return $response->withStatus(400)->withJson(['errors' => 'Model not found']);
        }
        if ($model['default']) {
            return $response->withStatus(400)->withJson(['errors' => 'Can not disable this model because this is the default model']);
        }

        IndexingModelModel::update([
            'set'   => [
                'enabled'   => 'false'
            ],
            'where' => ['id = ? or master = ?'],
            'data'  => [$args['id'], $args['id']]
        ]);

        return $response->withStatus(204);
    }

    public function enable(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_indexing_models', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->notEmpty()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is empty or not an integer']);
        }

        $model = IndexingModelModel::getById(['select' => ['enabled'], 'id' => $args['id']]);
        if (empty($model)) {
            return $response->withStatus(400)->withJson(['errors' => 'Model not found']);
        }

        IndexingModelModel::update([
            'set'   => [
                'enabled'   => 'true'
            ],
            'where' => ['id = ? or master = ?'],
            'data'  => [$args['id'], $args['id']]
        ]);

        return $response->withStatus(204);
    }

    public function getEntities(Request $request, Response $response)
    {
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
}
