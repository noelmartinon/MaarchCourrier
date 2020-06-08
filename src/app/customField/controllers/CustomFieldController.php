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
 * @brief Custom Field Controller
 * @author dev@maarch.org
 */

namespace CustomField\controllers;

use Action\models\ActionModel;
use CustomField\models\CustomFieldModel;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use IndexingModel\models\IndexingModelFieldModel;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;

class CustomFieldController
{
    public function get(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $customFields = CustomFieldModel::get(['orderBy' => ['label']]);

        foreach ($customFields as $key => $customField) {
            $customFields[$key]['values'] = json_decode($customField['values'], true);
            $customFields[$key]['SQLMode'] = !empty($customFields[$key]['values']['table']);
            if (empty($queryParams['admin']) || !PrivilegeController::hasPrivilege(['privilegeId' => 'admin_custom_fields', 'userId' => $GLOBALS['id']])) {
                if (!empty($customFields[$key]['values']['table'])) {
                    $customFields[$key]['values'] = CustomFieldModel::getValuesSQL($customFields[$key]['values']);
                } elseif (!empty($customFields[$key]['values'])) {
                    $values = $customFields[$key]['values'];
                    $customFields[$key]['values'] = [];
                    foreach ($values as $value) {
                        $customFields[$key]['values'][] = ['key' => $value, 'label' => $value];
                    }
                }
            }
        }

        return $response->withJson(['customFields' => $customFields]);
    }

    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_custom_fields', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['type']) || !in_array($body['type'], ['string', 'integer', 'select', 'date', 'radio', 'checkbox', 'banAutocomplete'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body type is empty or not a string']);
        } elseif (!empty($body['values']) && !Validator::arrayType()->notEmpty()->validate($body['values'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body values is not an array']);
        }

        $fields = CustomFieldModel::get(['select' => [1], 'where' => ['label = ?'], 'data' => [$body['label']]]);
        if (!empty($fields)) {
            return $response->withStatus(400)->withJson(['errors' => 'Custom field with this label already exists']);
        }

        if (!empty($body['values']['table'])) {
            $control = CustomFieldController::controlSQLMode(['body' => $body]);
            if (!empty($control['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
            }
        }

        $id = CustomFieldModel::create([
            'label'         => $body['label'],
            'type'          => $body['type'],
            'values'        => empty($body['values']) ? '[]' : json_encode($body['values'])
        ]);

        HistoryController::add([
            'tableName' => 'custom_fields',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'info'      => _CUSTOMFIELDS_CREATION . " : {$body['label']}",
            'moduleId'  => 'customField',
            'eventId'   => 'customFieldCreation',
        ]);

        return $response->withJson(['customFieldId' => $id]);
    }

    public function update(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_custom_fields', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->notEmpty()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Param id is empty or not an integer']);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        } elseif (!empty($body['values']) && !Validator::arrayType()->notEmpty()->validate($body['values'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body values is not an array']);
        }

        if (count(array_unique($body['values'])) < count($body['values'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Some values have the same name']);
        }

        $field = CustomFieldModel::getById(['select' => ['type', 'values'], 'id' => $args['id']]);
        if (empty($field)) {
            return $response->withStatus(400)->withJson(['errors' => 'Custom field not found']);
        }

        $fields = CustomFieldModel::get(['select' => [1], 'where' => ['label = ?', 'id != ?'], 'data' => [$body['label'], $args['id']]]);
        if (!empty($fields)) {
            return $response->withStatus(400)->withJson(['errors' => 'Custom field with this label already exists']);
        }

        if (!empty($body['values']['table'])) {
            $control = CustomFieldController::controlSQLMode(['body' => $body]);
            if (!empty($control['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
            }
        } else {
            if (count(array_unique($body['values'])) < count($body['values'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Some values have the same name']);
            }
        }

        $values = json_decode($field['values'], true);
        if (empty($body['values']['table']) && empty($values['table'])) {
            if (in_array($field['type'], ['checkbox'])) {
                foreach ($values as $key => $value) {
                    if (!empty($body['values'][$key]) && $body['values'][$key] != $value) {
                        ResModel::update([
                            'postSet'   => ['custom_fields' => "jsonb_insert(custom_fields, '{{$args['id']}, 0}', '\"{$body['values'][$key]}\"')"],
                            'where'     => ["custom_fields->'{$args['id']}' @> ?"],
                            'data'      => ["\"{$value}\""]
                        ]);
                        ResModel::update([
                            'postSet'   => ['custom_fields' => "jsonb_set(custom_fields, '{{$args['id']}}', (custom_fields->'{$args['id']}') - '{$value}')"],
                            'where'     => ['1 = ?'],
                            'data'      => [1]
                        ]);
                    }
                }
            } elseif (in_array($field['type'], ['select', 'radio'])) {
                foreach ($values as $key => $value) {
                    if (!empty($body['values'][$key]) && $body['values'][$key] != $value) {
                        ResModel::update([
                            'postSet'   => ['custom_fields' => "jsonb_set(custom_fields, '{{$args['id']}}', '\"{$body['values'][$key]}\"')"],
                            'where'     => ['1 = ?'],
                            'data'      => [1]
                        ]);
                    }
                }
            }
        }

        CustomFieldModel::update([
            'set'   => [
                'label'  => $body['label'],
                'values' => empty($body['values']) ? '[]' : json_encode($body['values'])
            ],
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        HistoryController::add([
            'tableName' => 'custom_fields',
            'recordId'  => $args['id'],
            'eventType' => 'UP',
            'info'      => _CUSTOMFIELDS_MODIFICATION . " : {$body['label']}",
            'moduleId'  => 'customField',
            'eventId'   => 'customFieldModification',
        ]);

        return $response->withStatus(204);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_custom_fields', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->notEmpty()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Param id is empty or not an integer']);
        }

        $field = CustomFieldModel::getById(['select' => ['label'], 'id' => $args['id']]);

        IndexingModelFieldModel::delete(['where' => ['identifier = ?'], 'data' => ['indexingCustomField_' . $args['id']]]);
        ResModel::update(['postSet' => ['custom_fields' => "custom_fields - '{$args['id']}'"], 'where' => ['1 = ?'], 'data' => [1]]);

        ActionModel::update([
            'postSet' => ['parameters' => "jsonb_set(parameters, '{parameters}', (parameters->'requiredFields') - 'indexingCustomField_{$args['id']}')"],
            'where'   => ["parameters->'requiredFields' @> ?"],
            'data'    => ['"indexingCustomField_'.$args['id'].'"']
        ]);

        CustomFieldModel::delete([
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        HistoryController::add([
            'tableName' => 'custom_fields',
            'recordId'  => $args['id'],
            'eventType' => 'DEL',
            'info'      => _CUSTOMFIELDS_SUPPRESSION . " : {$field['label']}",
            'moduleId'  => 'customField',
            'eventId'   => 'customFieldSuppression',
        ]);

        return $response->withStatus(204);
    }

    public function getWhiteList(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_custom_fields', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $allowedTables = CoreConfigModel::getJsonLoaded(['path' => 'apps/maarch_entreprise/xml/customFieldsWhiteList.json']);

        return $response->withJson(['allowedTables' => $allowedTables]);
    }

    public static function controlSQLMode(array $args)
    {
        $body = $args['body'];

        if ($body['type'] == 'banAutocomplete') {
            return ['errors' => 'SQL is not allowed for type BAN'];
        }
        if (!Validator::stringType()->notEmpty()->validate($body['values']['key'])) {
            return ['errors' => 'Body values[key] is empty or not a string'];
        } elseif (!Validator::stringType()->notEmpty()->validate($body['values']['label'])) {
            return ['errors' => 'Body values[label] is empty or not a string'];
        } elseif (!Validator::stringType()->notEmpty()->validate($body['values']['table'])) {
            return ['errors' => 'Body values[table] is empty or not a string'];
        } elseif (!Validator::stringType()->notEmpty()->validate($body['values']['clause'])) {
            return ['errors' => 'Body values[clause] is empty or not a string'];
        }
        if (strpos($body['values']['key'], 'password') !== false || strpos($body['values']['key'], 'token') !== false) {
            return ['errors' => 'Body values[key] is not allowed'];
        } elseif (strpos($body['values']['label'], 'password') !== false || strpos($body['values']['label'], 'token') !== false) {
            return ['errors' => 'Body values[key] is not allowed'];
        }
        $allowedTables = CoreConfigModel::getJsonLoaded(['path' => 'apps/maarch_entreprise/xml/customFieldsWhiteList.json']);
        if (!in_array($body['values']['table'], $allowedTables)) {
            return ['errors' => 'Body values[table] is not allowed'];
        }
        if (in_array($body['values']['type'], ['string', 'date', 'int'])) {
            $limitPos = stripos($body['values']['clause'], 'limit');
            if (!empty($limitPos)) {
                $body['values']['clause'] = substr_replace($body['values']['clause'], 'LIMIT 1', $limitPos);
            } else {
                $body['values']['clause'] .= ' LIMIT 1';
            }
        }
        try {
            CustomFieldModel::getValuesSQL($body['values']);
        } catch (\Exception $e) {
            return ['errors' => 'Clause is not valid'];
        }

        return true;
    }
}
