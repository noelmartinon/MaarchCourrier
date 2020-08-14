<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*/

/**
 * @brief Issuing Site Controller
 * @author dev@maarch.org
 */

namespace RegisteredMail\controllers;

use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use RegisteredMail\models\IssuingSiteEntitiesModel;
use RegisteredMail\models\IssuingSiteModel;
use RegisteredMail\models\RegisteredNumberRangeModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;

class IssuingSiteController
{
    public function get(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_registered_mail', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $sites = IssuingSiteModel::get();

        foreach ($sites as $key => $site) {
            $ranges = RegisteredNumberRangeModel::get(['where' => ['site_id = ?'], 'data' => [$site['id']]]);
            $sites[$key] = [
                'id'                 => $site['id'],
                'label'              => $site['label'],
                'postOfficeLabel'    => $site['post_office_label'],
                'accountNumber'      => $site['account_number'],
                'addressNumber'      => $site['address_number'],
                'addressStreet'      => $site['address_street'],
                'addressAdditional1' => $site['address_additional1'],
                'addressAdditional2' => $site['address_additional2'],
                'addressPostcode'    => $site['address_postcode'],
                'addressTown'        => $site['address_town'],
                'addressCountry'     => $site['address_country'],
                'countRanges'        => count($ranges)
            ];
        }

        return $response->withJson(['sites' => $sites]);
    }

    public function getById(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_registered_mail', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $site = IssuingSiteModel::getById(['id' => $args['id']]);

        if (empty($site)) {
            return $response->withStatus(400)->withJson(['errors' => 'Issuing site not found']);
        }

        $site = [
            'id'                 => $site['id'],
            'label'              => $site['label'],
            'postOfficeLabel'    => $site['post_office_label'] ?? null,
            'accountNumber'      => $site['account_number'] ?? null,
            'addressNumber'      => $site['address_number'] ?? null,
            'addressStreet'      => $site['address_street'] ?? null,
            'addressAdditional1' => $site['address_additional1'] ?? null,
            'addressAdditional2' => $site['address_additional2'] ?? null,
            'addressPostcode'    => $site['address_postcode'] ?? null,
            'addressTown'        => $site['address_town'] ?? null,
            'addressCountry'     => $site['address_country'] ?? null
        ];

        $entities = IssuingSiteEntitiesModel::get([
            'select' => ['entity_id'],
            'where'  => ['site_id = ?'],
            'data'   => [$args['id']]
        ]);

        $entities = array_column($entities, 'entity_id');

        $site['entities'] = $entities;

        return $response->withJson(['site' => $site]);
    }

    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_registered_mail', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        }
        if (!empty($body['entities']) && !Validator::arrayType()->validate($body['entities'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body entities is not an array']);
        } elseif (!empty($body['entities']) && Validator::arrayType()->validate($body['entities'])) {
            foreach ($body['entities'] as $key => $entity) {
                if (!Validator::intVal()->validate($entity)) {
                    return $response->withStatus(400)->withJson(['errors' => "Body entities[$key] is not an integer"]);
                }
            }
        }

        $id = IssuingSiteModel::create([
            'label'              => $body['label'],
            'postOfficeLabel'    => $body['postOfficeLabel'] ?? null,
            'accountNumber'      => $body['accountNumber'],
            'addressNumber'      => $body['addressNumber'] ?? null,
            'addressStreet'      => $body['addressStreet'] ?? null,
            'addressAdditional1' => $body['addressAdditional1'] ?? null,
            'addressAdditional2' => $body['addressAdditional2'] ?? null,
            'addressPostcode'    => $body['addressPostcode'] ?? null,
            'addressTown'        => $body['addressTown'] ?? null,
            'addressCountry'     => $body['addressCountry'] ?? null
        ]);

        if (!empty($body['entities'])) {
            foreach ($body['entities'] as $entity) {
                IssuingSiteEntitiesModel::create(['siteId' => $id, 'entityId' => $entity]);
            }
        }

        HistoryController::add([
            'tableName' => 'issuing_sites',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'info'      => _ISSUING_SITE_CREATED . " : {$id}",
            'moduleId'  => 'issuing_sites',
            'eventId'   => 'issuingSitesCreation',
        ]);

        return $response->withJson(['id' => $id]);
    }

    public function update(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_registered_mail', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $site = IssuingSiteModel::getById(['id' => $args['id']]);
        if (empty($site)) {
            return $response->withStatus(400)->withJson(['errors' => 'Issuing site not found']);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        }
        if (!empty($body['entities']) && !Validator::arrayType()->validate($body['entities'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body entities is not an array']);
        } elseif (!empty($body['entities']) && Validator::arrayType()->validate($body['entities'])) {
            foreach ($body['entities'] as $key => $entity) {
                if (!Validator::intVal()->validate($entity)) {
                    return $response->withStatus(400)->withJson(['errors' => "Body entities[$key] is not an integer"]);
                }
            }
        }

        IssuingSiteModel::update([
            'set'   => [
                'label'               => $body['label'],
                'post_office_label'   => $body['postOfficeLabel'] ?? null,
                'account_number'      => $body['accountNumber'] ?? null,
                'address_number'      => $body['addressNumber'] ?? null,
                'address_street'      => $body['addressStreet'] ?? null,
                'address_additional1' => $body['addressAdditional1'] ?? null,
                'address_additional2' => $body['addressAdditional2'] ?? null,
                'address_postcode'    => $body['addressPostcode'] ?? null,
                'address_town'        => $body['addressTown'] ?? null,
                'address_country'     => $body['addressCountry'] ?? null
            ],
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        IssuingSiteEntitiesModel::delete([
            'where' => ['site_id = ?'],
            'data'  => [$args['id']]
        ]);

        if (!empty($body['entities'])) {
            foreach ($body['entities'] as $entity) {
                IssuingSiteEntitiesModel::create(['siteId' => $args['id'], 'entityId' => $entity]);
            }
        }

        HistoryController::add([
            'tableName' => 'issuing_sites',
            'recordId'  => $args['id'],
            'eventType' => 'UP',
            'info'      => _ISSUING_SITE_UPDATED . " : {$args['id']}",
            'moduleId'  => 'issuing_sites',
            'eventId'   => 'issuingSitesModification',
        ]);

        return $response->withStatus(204);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_registered_mail', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $site = IssuingSiteModel::getById(['id' => $args['id']]);
        if (empty($site)) {
            return $response->withStatus(204);
        }

        IssuingSiteEntitiesModel::delete([
            'where' => ['site_id = ?'],
            'data'  => [$args['id']]
        ]);

        IssuingSiteModel::delete([
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        HistoryController::add([
            'tableName' => 'issuing_sites',
            'recordId'  => $args['id'],
            'eventType' => 'DEL',
            'info'      => _ISSUING_SITE_DELETED . " : {$args['id']}",
            'moduleId'  => 'issuing_sites',
            'eventId'   => 'issuingSitesSuppression',
        ]);

        return $response->withStatus(204);
    }

    public function getByType(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_registered_mail', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $sitesIds = RegisteredNumberRangeModel::get([
            'select' => ['site_id'],
            'where'  => ['type = ?', 'status = ?'],
            'data'   => [$args['type'], 'OK']
        ]);

        if (empty($sitesIds)) {
            return $response->withStatus(200)->withJson(['sites' => []]);
        }
        $sitesIds = array_column($sitesIds, 'site_id');

        $sites = IssuingSiteModel::get([
            'where' => ['id in (?)'],
            'data'  => [$sitesIds]
        ]);

        foreach ($sites as $key => $site) {
            $sites[$key] = [
                'id'                 => $site['id'],
                'label'              => $site['label'],
                'postOfficeLabel'    => $site['post_office_label'],
                'accountNumber'      => $site['account_number'],
                'addressNumber'      => $site['address_number'],
                'addressStreet'      => $site['address_street'],
                'addressAdditional1' => $site['address_additional1'],
                'addressAdditional2' => $site['address_additional2'],
                'addressPostcode'    => $site['address_postcode'],
                'addressTown'        => $site['address_town'],
                'addressCountry'     => $site['address_country']
            ];
        }

        return $response->withJson(['sites' => $sites]);
    }
}
