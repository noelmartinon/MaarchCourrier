<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Auto Complete Controller
* @author dev@maarch.org
*/

namespace SrcCore\controllers;

use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use Entity\models\EntityModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\TextFormatModel;
use SrcCore\models\ValidatorModel;
use Status\models\StatusModel;
use User\models\UserModel;

class AutoCompleteController
{
    const LIMIT = 50;

    public static function getUsers(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $check = Validator::stringType()->notEmpty()->validate($data['search']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $excludedUsers = ['superadmin'];

        $requestData = AutoCompleteController::getDataForRequest([
            'search'        => $data['search'],
            'fields'        => '(firstname ilike ? OR lastname ilike ?)',
            'where'         => ['enabled = ?', 'status != ?', 'user_id not in (?)'],
            'data'          => ['Y', 'DEL', $excludedUsers],
            'fieldsNumber'  => 2,
        ]);

        $users = UserModel::get([
            'select'    => ['user_id', 'firstname', 'lastname'],
            'where'     => $requestData['where'],
            'data'      => $requestData['data'],
            'orderBy'   => ['lastname'],
            'limit'     => self::LIMIT
        ]);

        $data = [];
        foreach ($users as $value) {
            $data[] = [
                'type'          => 'user',
                'id'            => $value['user_id'],
                'idToDisplay'   => "{$value['firstname']} {$value['lastname']}",
                'otherInfo'     => ''
            ];
        }

        return $response->withJson($data);
    }

    public static function getUsersForVisa(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $check = Validator::stringType()->notEmpty()->validate($data['search']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $excludedUsers = ['superadmin'];

        $requestData = AutoCompleteController::getDataForRequest([
            'search'        => $data['search'],
            'fields'        => '(users.firstname ilike ? OR users.lastname ilike ?)',
            'where'         => [
                'usergroup_content.group_id = usergroups_services.group_id',
                'usergroup_content.user_id = users.user_id',
                'usergroups_services.service_id in (?)',
                'users.user_id not in (?)',
                'users.enabled = ?',
                'users.status != ?'
            ],
            'data'          => [['visa_documents', 'sign_document'], $excludedUsers, 'Y', 'DEL'],
            'fieldsNumber'  => 2,
        ]);

        $users = DatabaseModel::select([
            'select'    => ['DISTINCT users.user_id', 'users.firstname', 'users.lastname'],
            'table'     => ['users, usergroup_content, usergroups_services'],
            'where'     => $requestData['where'],
            'data'      => $requestData['data'],
            'order_by'  => ['users.lastname'],
            'limit'     => self::LIMIT
        ]);

        $data = [];
        foreach ($users as $key => $value) {
            $data[] = [
                'type'          => 'user',
                'id'            => $value['user_id'],
                'idToDisplay'   => "{$value['firstname']} {$value['lastname']}",
                'otherInfo'     => ''
            ];
        }

        return $response->withJson($data);
    }

    public static function getEntities(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $check = Validator::stringType()->notEmpty()->validate($data['search']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $requestData = AutoCompleteController::getDataForRequest([
            'search'        => $data['search'],
            'fields'        => '(entity_label ilike ?)',
            'where'         => ['enabled = ?'],
            'data'          => ['Y'],
            'fieldsNumber'  => 1,
        ]);

        $entities = EntityModel::get([
            'select'    => ['entity_id', 'entity_label', 'short_label'],
            'where'     => $requestData['where'],
            'data'      => $requestData['data'],
            'orderBy'   => ['entity_label'],
            'limit'     => self::LIMIT
        ]);

        $data = [];
        foreach ($entities as $value) {
            $data[] = [
                'type'          => 'entity',
                'id'            => $value['entity_id'],
                'idToDisplay'   => $value['entity_label'],
                'otherInfo'     => $value['short_label']
            ];
        }

        return $response->withJson($data);
    }

    public static function getStatuses(Request $request, Response $response)
    {
        $statuses = StatusModel::get([
            'select'    => ['id', 'label_status', 'img_filename']
        ]);

        $data = [];
        foreach ($statuses as $key => $value) {
            $data[] = [
                'type'          => 'status',
                'id'            => $value['id'],
                'idToDisplay'   => $value['label_status'],
                'otherInfo'     => $value['img_filename']
            ];
        }

        return $response->withJson($data);
    }

    public static function getBanAddresses(Request $request, Response $response)
    {
        $data = $request->getQueryParams();

        $check = Validator::stringType()->notEmpty()->validate($data['address']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['department']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }
        $customId = CoreConfigModel::getCustomId();

        if (is_dir("custom/{$customId}/referential/ban/indexes/{$data['department']}")) {
            $path = "custom/{$customId}/referential/ban/indexes/{$data['department']}";
        } elseif (is_dir('referential/ban/indexes/' . $data['department'])) {
            $path = 'referential/ban/indexes/' . $data['department'];
        } else {
            return $response->withStatus(400)->withJson(['errors' => 'Department indexes do not exist']);
        }

        \Zend_Search_Lucene_Analysis_Analyzer::setDefault(new \Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
        \Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(\Zend_Search_Lucene_Search_QueryParser::B_AND);
        \Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');

        $index = \Zend_Search_Lucene::open($path);
        \Zend_Search_Lucene::setResultSetLimit(100);

        $data['address'] = str_replace(['*', '~', '-', '\''], ' ', $data['address']);
        $aAddress = explode(' ', $data['address']);
        foreach ($aAddress as $key => $value) {
            if (strlen($value) <= 2 && !is_numeric($value)) {
                unset($aAddress[$key]);
                continue;
            }
            if (strlen($value) >= 3 && $value != 'rue' && $value != 'avenue' && $value != 'boulevard') {
                $aAddress[$key] .= '*';
            }
        }
        $data['address'] = implode(' ', $aAddress);
        if (empty($data['address'])) {
            return $response->withJson([]);
        }

        $hits = $index->find(TextFormatModel::normalize(['string' => $data['address']]));

        $addresses = [];
        foreach($hits as $key => $hit){
            $addresses[] = [
                'banId'         => $hit->banId,
                'number'        => $hit->streetNumber,
                'afnorName'     => $hit->afnorName,
                'postalCode'    => $hit->postalCode,
                'city'          => $hit->city,
                'address'       => "{$hit->streetNumber} {$hit->afnorName}, {$hit->city} ({$hit->postalCode})"
            ];
        }

        return $response->withJson($addresses);
    }

    private static function getDataForRequest(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['search', 'fields', 'where', 'data', 'fieldsNumber']);
        ValidatorModel::stringType($aArgs, ['search', 'fields']);
        ValidatorModel::arrayType($aArgs, ['where', 'data']);
        ValidatorModel::intType($aArgs, ['fieldsNumber']);

        $searchItems = explode(' ', $aArgs['search']);

        foreach ($searchItems as $item) {
            if (strlen($item) >= 2) {
                $aArgs['where'][] = $aArgs['fields'];
                for ($i = 0; $i < $aArgs['fieldsNumber']; $i++) {
                    $aArgs['data'][] = "%{$item}%";
                }
            }
        }

        return ['where' => $aArgs['where'], 'data' => $aArgs['data']];
    }
}
