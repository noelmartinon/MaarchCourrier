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

use Contact\controllers\ContactController;
use Contact\controllers\ContactGroupController;
use Contact\models\ContactModel;
use Entity\models\EntityModel;
use MessageExchange\controllers\AnnuaryController;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\TextFormatModel;
use SrcCore\models\ValidatorModel;
use Status\models\StatusModel;
use User\models\UserModel;

class AutoCompleteController
{
    const LIMIT = 50;
    const TINY_LIMIT = 10;

    public static function getContacts(Request $request, Response $response)
    {
        $data = $request->getQueryParams();

        $check = Validator::stringType()->notEmpty()->validate($data['search']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $searchItems = explode(' ', $data['search']);

        $fields = '(contact_firstname ilike ? OR contact_lastname ilike ? OR firstname ilike ? OR lastname ilike ? OR society ilike ?
                    OR address_num ilike ? OR address_street ilike ? OR address_town ilike ? OR address_postal_code ilike ?)';
        $where = [];
        $requestData = [];
        foreach ($searchItems as $item) {
            if (strlen($item) >= 2) {
                $where[] = $fields;
                for ($i = 0; $i < 9; $i++) {
                    $requestData[] = "%{$item}%";
                }
            }
        }

        $contacts = ContactModel::getOnView([
            'select'    => ['*'],
            'where'     => $where,
            'data'      => $requestData,
            'limit'     => self::TINY_LIMIT
        ]);

        $color = (!empty($data['color']) && $data['color'] == 'true');
        $autocompleteData = [];
        foreach ($contacts as $contact) {
            $autocompleteData[] = AutoCompleteController::getFormattedContact(['contact' => $contact, 'color' => $color])['contact'];
        }

        return $response->withJson($autocompleteData);
    }

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
            'select'    => ['id', 'user_id', 'firstname', 'lastname'],
            'where'     => $requestData['where'],
            'data'      => $requestData['data'],
            'orderBy'   => ['lastname'],
            'limit'     => self::LIMIT
        ]);

        $data = [];
        foreach ($users as $value) {
            $primaryEntity = UserModel::getPrimaryEntityByUserId(['userId' => $value['user_id']]);
            $data[] = [
                'type'                  => 'user',
                'id'                    => $value['user_id'],
                'serialId'              => $value['id'],
                'idToDisplay'           => "{$value['firstname']} {$value['lastname']}",
                'descriptionToDisplay'  => empty($primaryEntity) ? '' : $primaryEntity['entity_label'],
                'otherInfo'             => ''
            ];
        }

        return $response->withJson($data);
    }

    public static function getMaarchParapheurUsers(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $check = Validator::stringType()->notEmpty()->validate($data['search']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'search is empty']);
        }

        if (!empty($data['exludeAlreadyConnected'])) {
            $usersAlreadyConnected = UserModel::get([
                'select' => ['external_id->>\'maarchParapheur\' as external_id'],
                'where' => ['external_id->>\'maarchParapheur\' is not null']
            ]);
            $excludedUsers = array_column($usersAlreadyConnected, 'external_id');
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);

        if ($loadedXml->signatoryBookEnabled == 'maarchParapheur') {
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == "maarchParapheur") {
                    $url      = $value->url;
                    $userId   = $value->userId;
                    $password = $value->password;
                    break;
                }
            }

            $curlResponse = CurlModel::execSimple([
                'url'           => rtrim($url, '/') . '/rest/autocomplete/users?search='.urlencode($data['search']),
                'basicAuth'     => ['user' => $userId, 'password' => $password],
                'headers'       => ['content-type:application/json'],
                'method'        => 'GET'
            ]);

            if ($curlResponse['code'] != '200') {
                if (!empty($curlResponse['response']['errors'])) {
                    $errors =  $curlResponse['response']['errors'];
                } else {
                    $errors =  $curlResponse['errors'];
                }
                if (empty($errors)) {
                    $errors = 'An error occured. Please check your configuration file.';
                }
                return $response->withStatus(400)->withJson(['errors' => $errors]);
            }

            foreach ($curlResponse['response'] as $key => $value) {
                if (!empty($data['exludeAlreadyConnected']) && in_array($value['id'], $excludedUsers)) {
                    unset($curlResponse['response'][$key]);
                    continue;
                }
                $curlResponse['response'][$key]['idToDisplay'] = $value['firstname'] . ' ' . $value['lastname'];
                $curlResponse['response'][$key]['externalId']['maarchParapheur'] = $value['id'];
            }
            return $response->withJson($curlResponse['response']);
        } else {
            return $response->withStatus(403)->withJson(['errors' => 'maarchParapheur is not enabled']);
        }
    }

    public static function getContactsAndUsers(Request $request, Response $response)
    {
        $data = $request->getQueryParams();

        $check = Validator::stringType()->notEmpty()->validate($data['search']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $searchItems = explode(' ', $data['search']);

        $fields = ['contact_firstname', 'contact_lastname', 'firstname', 'lastname', 'society', 'society_short', 'address_num', 'address_street', 'address_town', 'address_postal_code'];
        foreach ($fields as $key => $field) {
            $fields[$key] = "translate({$field}, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ', 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')";
            $fields[$key] .= "ilike translate(?, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ', 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')";
        }
        $fields = implode(' OR ', $fields);
        $fields = "($fields)";

        $where = [];
        $requestData = [];
        foreach ($searchItems as $keyItem => $item) {
            if (strlen($item) >= 2) {
                $where[] = $fields;
                $isIncluded = false;
                foreach ($searchItems as $key => $value) {
                    if ($keyItem == $key) {
                        continue;
                    }
                    if (strpos($value, $item) === 0) {
                        $isIncluded = true;
                    }
                }
                for ($i = 0; $i < 10; $i++) {
                    $requestData[] = ($isIncluded ? "%{$item}" : "%{$item}%");
                }
            }
        }

        if ($data['onlyContacts'] == "false") {
            $where[] = '(enabled = \'Y\')';
        }

        $contacts = ContactModel::getOnView([
            'select'    => ['*'],
            'where'     => $where,
            'data'      => $requestData,
            'orderBy'   => ["is_corporate_person DESC", "case is_corporate_person when 'Y' then (society, lastname) else (contact_lastname, society) end"],
            'limit'     => self::TINY_LIMIT
        ]);

        $color = (!empty($data['color']) && $data['color'] == 'true');

        $onlyContacts = [];
        $autocompleteData = [];
        foreach ($contacts as $contact) {
            if (!empty($data['onlyContacts']) && $data['onlyContacts'] == 'true' && !in_array($contact['contact_id'], $onlyContacts)) {
                $autocompleteData[] = AutoCompleteController::getFormattedOnlyContact(['contact' => $contact])['contact'];
                $onlyContacts[] = $contact['contact_id'];
            }
            $autocompleteData[] = AutoCompleteController::getFormattedContact(['contact' => $contact, 'color' => $color])['contact'];
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
            'select'    => ['id', 'user_id', 'firstname', 'lastname'],
            'where'     => $requestData['where'],
            'data'      => $requestData['data'],
            'orderBy'   => ['lastname'],
            'limit'     => self::TINY_LIMIT
        ]);

        foreach ($users as $value) {
            $autocompleteData[] = [
                'type'          => 'user',
                'id'            => $value['id'],
                'idToDisplay'   => "{$value['firstname']} {$value['lastname']}",
                'otherInfo'     => "{$value['firstname']} {$value['lastname']}"
            ];
        }

        return $response->withJson($autocompleteData);
    }

    public static function getUsersForAdministration(Request $request, Response $response)
    {
        $data = $request->getQueryParams();
        $check = Validator::stringType()->notEmpty()->validate($data['search']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $excludedUsers = ['superadmin'];

        if ($GLOBALS['userId'] != 'superadmin') {
            $entities = EntityModel::getAllEntitiesByUserId(['userId' => $GLOBALS['userId']]);

            $requestData = AutoCompleteController::getDataForRequest([
                'search'        => $data['search'],
                'fields'        => '(users.firstname ilike ? OR users.lastname ilike ?)',
                'where'         => [
                    'users.user_id = users_entities.user_id',
                    'users_entities.entity_id in (?)',
                    'users.status != ?',
                    'users.enabled = ?'
                ],
                'data'          => [$entities, 'DEL', 'Y'],
                'fieldsNumber'  => 2,
            ]);

            $users = DatabaseModel::select([
                'select'    => ['DISTINCT users.user_id', 'users.id', 'users.firstname', 'users.lastname'],
                'table'     => ['users, users_entities'],
                'where'     => $requestData['where'],
                'data'      => $requestData['data'],
                'limit'     => self::LIMIT
            ]);

            if (count($users) < self::LIMIT) {
                $requestData = AutoCompleteController::getDataForRequest([
                    'search'        => $data['search'],
                    'fields'        => '(users.firstname ilike ? OR users.lastname ilike ?)',
                    'where'         => [
                        'users_entities IS NULL',
                        'users.user_id not in (?)',
                        'users.status != ?',
                        'users.enabled = ?'
                    ],
                    'data'          => [$excludedUsers, 'DEL', 'Y'],
                    'fieldsNumber'  => 2,
                ]);

                $usersNoEntities = DatabaseModel::select([
                    'select'    => ['users.id', 'users.user_id', 'users.firstname', 'users.lastname'],
                    'table'     => ['users', 'users_entities'],
                    'left_join' => ['users.user_id = users_entities.user_id'],
                    'where'     => $requestData['where'],
                    'data'      => $requestData['data'],
                    'limit'     => (self::LIMIT - count($users))
                ]);

                $users = array_merge($users, $usersNoEntities);
            }
        } else {
            $requestData = AutoCompleteController::getDataForRequest([
                'search'        => $data['search'],
                'fields'        => '(firstname ilike ? OR lastname ilike ?)',
                'where'         => ['enabled = ?', 'status != ?', 'user_id not in (?)'],
                'data'          => ['Y', 'DEL', $excludedUsers],
                'fieldsNumber'  => 2,
            ]);

            $users = UserModel::get([
                'select'    => ['id', 'user_id', 'firstname', 'lastname'],
                'where'     => $requestData['where'],
                'data'      => $requestData['data'],
                'orderBy'   => ['lastname'],
                'limit'     => self::LIMIT
            ]);
        }

        $data = [];
        foreach ($users as $value) {
            $data[] = [
                'type'          => 'user',
                'id'            => $value['id'],
                'idToDisplay'   => "{$value['firstname']} {$value['lastname']}",
                'otherInfo'     => $value['user_id']
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
            'select'    => ['id', 'entity_id', 'entity_label', 'short_label'],
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
                'serialId'      => $value['id'],
                'idToDisplay'   => $value['entity_label'],
                'otherInfo'     => $value['short_label']
            ];
        }

        return $response->withJson($data);
    }

    public static function getStatuses(Request $request, Response $response)
    {
        $statuses = StatusModel::get(['select' => ['id', 'label_status', 'img_filename']]);

        $data = [];
        foreach ($statuses as $value) {
            $data[] = [
                'type'          => 'status',
                'id'            => $value['id'],
                'idToDisplay'   => $value['label_status'],
                'otherInfo'     => $value['img_filename']
            ];
        }

        return $response->withJson($data);
    }

    public static function getContactsForGroups(Request $request, Response $response)
    {
        $data = $request->getQueryParams();

        $check = Validator::stringType()->notEmpty()->validate($data['search']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['type']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $searchItems = explode(' ', $data['search']);

        $fields = '(contact_firstname ilike ? OR contact_lastname ilike ? OR firstname ilike ? OR lastname ilike ? OR society ilike ?
                    OR address_num ilike ? OR address_street ilike ? OR address_town ilike ? OR address_postal_code ilike ?)';
        $where = [];
        $requestData = [];
        if ($data['type'] != 'all') {
            $where = ['contact_type = ?'];
            $requestData = [$data['type']];
        }
        foreach ($searchItems as $item) {
            if (strlen($item) >= 2) {
                $where[] = $fields;
                for ($i = 0; $i < 9; $i++) {
                    $requestData[] = "%{$item}%";
                }
            }
        }

        $contacts = ContactModel::getOnView([
            'select'    => [
                'ca_id', 'firstname', 'lastname', 'contact_lastname', 'contact_firstname', 'society', 'address_num',
                'address_street', 'address_town', 'address_postal_code', 'is_corporate_person'
            ],
            'where'     => $where,
            'data'      => $requestData,
            'limit'     => 1000
        ]);

        $data = [];
        foreach ($contacts as $contact) {
            $data[] = ContactGroupController::getFormattedContact(['contact' => $contact])['contact'];
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
        foreach ($hits as $key => $hit) {
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

    public static function getOuM2MAnnuary(Request $request, Response $response)
    {
        $data = $request->getQueryParams();

        $check = Validator::stringType()->notEmpty()->validate($data['society']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Query society is empty']);
        }

        $control = AnnuaryController::getAnnuaries();
        if (!isset($control['annuaries'])) {
            if (isset($control['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
            }
        }

        $unitOrganizations = [];
        if (!empty($control['annuaries'])) {
            foreach ($control['annuaries'] as $annuary) {
                $ldap = @ldap_connect($annuary['uri']);
                if ($ldap === false) {
                    continue;
                }
                ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
                ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 5);

                $search = @ldap_search($ldap, $annuary['baseDN'], "(ou=*{$data['society']}*)", ['ou', 'postOfficeBox', 'destinationIndicator', 'labeledURI']);
                if ($search === false) {
                    continue;
                }
                $entries = ldap_get_entries($ldap, $search);

                foreach ($entries as $key => $value) {
                    if (!is_numeric($key)) {
                        continue;
                    }
                    if (!empty($value['postofficebox'])) {
                        $unitOrganizations[] = [
                            'communicationValue' => $value['postofficebox'][0],
                            'businessIdValue'    => $value['destinationindicator'][0],
                            'unitOrganization'   => "{$value['ou'][0]} ({$value['postofficebox'][0]})"
                        ];
                    }
                    if (!empty($value['labeleduri'])) {
                        $unitOrganizations[] = [
                            'communicationValue' => $value['labeleduri'][0],
                            'businessIdValue'    => $value['destinationindicator'][0],
                            'unitOrganization'   => "{$value['ou'][0]} ({$value['labeleduri'][0]})"
                        ];
                    }
                }

                break;
            }
        }

        return $response->withJson($unitOrganizations);
    }

    public static function getBusinessIdM2MAnnuary(Request $request, Response $response)
    {
        $data = $request->getQueryParams();

        $check = Validator::stringType()->notEmpty()->validate($data['communicationValue']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Query communicationValue is empty']);
        }

        $control = AnnuaryController::getAnnuaries();
        if (!isset($control['annuaries'])) {
            if (isset($control['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
            }
        }

        foreach ($control['annuaries'] as $annuary) {
            $ldap = @ldap_connect($annuary['uri']);
            if ($ldap === false) {
                $error = 'Ldap connect failed : uri is maybe wrong';
                continue;
            }
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 5);

            if (filter_var($data['communicationValue'], FILTER_VALIDATE_EMAIL)) {
                $search = @ldap_search($ldap, $annuary['baseDN'], "(postofficebox={$data['communicationValue']})", ['destinationIndicator']);
            } else {
                $search = @ldap_search($ldap, $annuary['baseDN'], "(labeleduri={$data['communicationValue']})", ['destinationIndicator']);
            }
            if ($search === false) {
                $error = 'Ldap search failed : baseDN is maybe wrong => ' . ldap_error($ldap);
                continue;
            }
            $entriesOu = ldap_get_entries($ldap, $search);
            foreach ($entriesOu as $keyOu => $valueOu) {
                if (!is_numeric($keyOu)) {
                    continue;
                }
                $siret   = $valueOu['destinationindicator'][0];
                $search  = @ldap_search($ldap, $valueOu['dn'], "(cn=*)", ['cn', 'initials', 'entryUUID']);
                $entries = ldap_get_entries($ldap, $search);

                foreach ($entries as $key => $value) {
                    if (!is_numeric($key)) {
                        continue;
                    }
                    $unitOrganizations[] = [
                        'entryuuid'        => $value['entryuuid'][0],
                        'businessIdValue'  => $siret . '/' . $value['initials'][0],
                        'unitOrganization' => "{$value['cn'][0]} - {$siret}/{$value['initials'][0]}"
                    ];
                }
            }

            return $response->withJson($unitOrganizations);
        }
    }

    public static function getDataForRequest(array $aArgs)
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

    public static function getFormattedContact(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['contact']);
        ValidatorModel::arrayType($aArgs, ['contact']);
        ValidatorModel::boolType($aArgs, ['color']);

        if (!empty($aArgs['color'])) {
            $rate = ContactController::getFillingRate(['contact' => $aArgs['contact']]);
        }
        $rateColor = empty($rate['color']) ? '' : $rate['color'];

        $address = '';
        if ($aArgs['contact']['is_corporate_person'] == 'Y') {
            $address.= $aArgs['contact']['firstname'];
            $address.= (empty($address) ? $aArgs['contact']['lastname'] : " {$aArgs['contact']['lastname']}");
            $address .= ', ';
            if (!empty($aArgs['contact']['address_num'])) {
                $address.= $aArgs['contact']['address_num'] . ' ';
            }
            if (!empty($aArgs['contact']['address_street'])) {
                $address.= $aArgs['contact']['address_street'] . ' ';
            }
            if (!empty($aArgs['contact']['address_postal_code'])) {
                $address.= $aArgs['contact']['address_postal_code'] . ' ';
            }
            if (!empty($aArgs['contact']['address_town'])) {
                $address.= $aArgs['contact']['address_town'] . ' ';
            }
            if (!empty($aArgs['contact']['address_country'])) {
                $address.= $aArgs['contact']['address_country'];
            }
            $address = rtrim($address, ', ');
            $otherInfo = empty($address) ? "{$aArgs['contact']['society']}" : "{$aArgs['contact']['society']} - {$address}";
            $contact = [
                'type'          => 'contact',
                'id'            => $aArgs['contact']['ca_id'],
                'contact'       => $aArgs['contact']['society'],
                'address'       => $address,
                'idToDisplay'   => "{$aArgs['contact']['society']}<br/>{$address}",
                'otherInfo'     => $otherInfo,
                'rateColor'     => $rateColor
            ];
        } else {
            if (!empty($aArgs['contact']['address_num'])) {
                $address.= $aArgs['contact']['address_num'] . ' ';
            }
            if (!empty($aArgs['contact']['address_street'])) {
                $address.= $aArgs['contact']['address_street'] . ' ';
            }
            if (!empty($aArgs['contact']['address_postal_code'])) {
                $address.= $aArgs['contact']['address_postal_code'] . ' ';
            }
            if (!empty($aArgs['contact']['address_town'])) {
                $address.= $aArgs['contact']['address_town'] . ' ';
            }
            if (!empty($aArgs['contact']['address_country'])) {
                $address.= $aArgs['contact']['address_country'];
            }
            $contactToDisplay = "{$aArgs['contact']['contact_firstname']} {$aArgs['contact']['contact_lastname']}";
            if (!empty($aArgs['contact']['society'])) {
                $contactToDisplay .= " ({$aArgs['contact']['society']})";
            }

            $otherInfo = empty($address) ? "{$contactToDisplay}" : "{$contactToDisplay} - {$address}";
            $contact = [
                'type'          => 'contact',
                'id'            => $aArgs['contact']['ca_id'],
                'contact'       => $contactToDisplay,
                'address'       => $address,
                'idToDisplay'   => "{$contactToDisplay}<br/>{$address}",
                'otherInfo'     => $otherInfo,
                'rateColor'     => $rateColor
            ];
        }

        return ['contact' => $contact];
    }

    public static function getFormattedOnlyContact(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['contact']);
        ValidatorModel::arrayType($aArgs, ['contact']);

        if ($aArgs['contact']['is_corporate_person'] == 'Y') {
            $contact = [
                'type'          => 'onlyContact',
                'id'            => $aArgs['contact']['contact_id'],
                'idToDisplay'   => $aArgs['contact']['society'],
                'otherInfo'     => $aArgs['contact']['society'],
                'rateColor'     => ''
            ];
        } else {
            $contactToDisplay = "{$aArgs['contact']['contact_firstname']} {$aArgs['contact']['contact_lastname']}";
            if (!empty($aArgs['contact']['society'])) {
                $contactToDisplay .= " ({$aArgs['contact']['society']})";
            }
            $contact = [
                'type'          => 'onlyContact',
                'id'            => $aArgs['contact']['contact_id'],
                'idToDisplay'   => $contactToDisplay,
                'otherInfo'     => $contactToDisplay,
                'rateColor'     => ''
            ];
        }

        return ['contact' => $contact];
    }
}
