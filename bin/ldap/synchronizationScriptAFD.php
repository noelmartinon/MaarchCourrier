<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Synchronization Script
 * @author dev@maarch.org
 */
libxml_use_internal_errors(true);
chdir('../..');

require 'vendor/autoload.php';

main($argv);

function main($argv)
{
    $customId = null;
    if (!empty($argv[1]) && $argv[1] == '--customId' && !empty($argv[2])) {
        $customId = $argv[2];
        $GLOBALS['customId'] = $customId;
    }
    
    $xmlfile = initialize($customId);
    $synchronizeUsers = false;
    if (!empty($xmlfile->synchronizeUsers) && (string)$xmlfile->synchronizeUsers == 'true') {
        $synchronizeUsers = true;
    }
    $synchronizeEntities = false;
    if (!empty($xmlfile->synchronizeEntities) && (string)$xmlfile->synchronizeEntities == 'true') {
        $synchronizeEntities = true;
    }
    
    if ($synchronizeEntities) {
        $maarchEntities = \Entity\models\EntityModel::get(['select' => ['id', 'entity_id', 'entity_label', 'short_label', 'entity_type', 'parent_entity_id']]);
        $ldapEntities = getEntitiesEntries($xmlfile);
        if (!empty($ldapEntities['errors'])) {
            writeLog(['message' => "[ERROR] {$ldapEntities['errors']}"]);
            $synchronizeEntities = false;
            $ldapEntities = null;
        }
    }

    if ($synchronizeUsers) {
        $maarchUsers = \User\models\UserModel::get(['select' => ['id', 'user_id', 'firstname', 'lastname', 'phone', 'mail', 'status']]);
        $ldapUsers = getUsersEntries($xmlfile);
        if (!empty($ldapUsers['errors'])) {
            writeLog(['message' => "[ERROR] {$ldapUsers['errors']}"]);
            $synchronizeUsers = false;
            $ldapUsers = null;
        }
    }
    
    if (!empty($ldapUsers)) {
        foreach ($ldapUsers as $key => $ldapUser) {
            if (!empty($ldapUser['entityId'])) {
                if (!empty($ldapEntities)) {
                    foreach ($ldapEntities as $ldapEntity) {
                        if ($ldapEntity['dn'] == $ldapUser['entityId']) {
                            $ldapUsers[$key]['entityId'] = $ldapEntity['entity_id'];
                            break;
                        }
                    }
                } else {
                    $ldapUsers[$key]['entityId'] = null;
                }
            }
        }
    }
    
    if ($synchronizeEntities) {
        synchronizeEntities($ldapEntities, $maarchEntities);
    }
    if ($synchronizeUsers) {
        synchronizeUsers($ldapUsers, $maarchUsers);
    }

}

function initialize($customId)
{
    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $customId]);


    $path = 'modules/ldap/xml/config.xml';
    if (!empty($customId) && is_file("custom/{$customId}/{$path}")) {
        $path = "custom/{$customId}/{$path}";
    }
    if (!is_file($path)) {
        writeLog(['message' => "[ERROR] Ldap configuration file is missing"]);
        exit();
    }
    $file = file_get_contents($path);
    $xmlfile = simplexml_load_file($path);

    if (false === $xmlfile) {
        $errors = libxml_get_errors();
        echo 'Errors are '.var_export($errors, true);
        throw new \Exception('invalid XML');
    }

    if (empty((string)$xmlfile->userWS) || empty((string)$xmlfile->passwordWS)) {
        writeLog(['message' => "[ERROR] Rest user informations are missing"]);
        exit();
    }
    $GLOBALS['user'] = (string)$xmlfile->userWS;
    $GLOBALS['password'] = (string)$xmlfile->passwordWS;

    $path = 'apps/maarch_entreprise/xml/config.json';
    if (!empty($customId)) {
        $path = "custom/{$customId}/apps/maarch_entreprise/xml/config.json";
    }
    $file = file_get_contents($path);
    $file = json_decode($file, true);
    if (empty($file['config']['maarchUrl'])) {
        writeLog(['message' => "[ERROR] Tag maarchUrl is missing in config.json"]);
        exit();
    }
    $GLOBALS['maarchUrl'] = $file['config']['maarchUrl'];

    return $xmlfile;
}

function getUsersEntries($xmlfile)
{
    $xmlldap = simplexml_load_file((string)$xmlfile->xmlUserPath);

    if (false === $xmlldap) {
        $errors = libxml_get_errors();
        echo 'Errors are '.var_export($errors, true);
        throw new \Exception('invalid XML');
    }
    $json = json_encode($xmlldap);
    $array = json_decode($json,TRUE);

    if (empty($xmlfile->mapping->user->user_id)) {
        return ['errors' => 'No mapping configurations (user_id)'];
    }
    $mapping = [
        'user_id'       => (string)$xmlfile->mapping->user->user_id,
        'firstname'     => (string)$xmlfile->mapping->user->firstname,
        'lastname'      => (string)$xmlfile->mapping->user->lastname,
        'phone'         => (string)$xmlfile->mapping->user->phone,
        'mail'          => (string)$xmlfile->mapping->user->mail,
        'entityId'      => (string)$xmlfile->mapping->user->user_entity ?? null
    ];
    $defaultEntity = (string)$xmlfile->mapping->user->defaultEntity ?? null;

    $entries = $array;
    $ldapEntries = [];
    foreach ($entries as $key => $entry) {
        $user = [
            'defaultEntity' => $defaultEntity
        ];
        foreach ($mapping as $mcField => $ldapField) {
            if (empty($ldapField)) {
                continue;
            }
            if (!array_key_exists($ldapField,$entry)) {
                $user[$mcField] = '';
            }else if (isset($entry[$ldapField]) && !empty($entry[$ldapField])) {
                $user[$mcField] = $entry[$ldapField];
            } else {
                $user[$mcField] = '';
            }
        }
        $ldapEntries[$key] = $user;
    }

    return $ldapEntries;
}

function getEntitiesEntries($xmlfile)
{
    $xmlldap = simplexml_load_file((string)$xmlfile->xmlEntityPath);

    if (false === $xmlldap) {
        $errors = libxml_get_errors();
        echo 'Errors are '.var_export($errors, true);
        throw new \Exception('invalid XML');
    }
    $json = json_encode($xmlldap);
    $array = json_decode($json,TRUE);

    if (empty($xmlfile->mapping->entity->entity_id)) {
        return ['errors' => 'No mapping configurations (entity_id)'];
    }
    $mapping = [
        'entity_id'         => (string)$xmlfile->mapping->entity->entity_id,
        'entity_label'      => (string)$xmlfile->mapping->entity->entity_label,
        'parent_entity_id'  => (string)$xmlfile->mapping->entity->parent_entity_id
    ];

    $entries = $array;
    $ldapEntries = [];
    foreach ($entries as $key => $entry) {

        foreach ($mapping as $mcField => $ldapField) {
            if (empty($ldapField)) {
                continue;
            }

            if (!array_key_exists($ldapField,$entry)) {
                $entity[$mcField] = '';
            }else if (isset($entry[$ldapField]) && !empty($entry[$ldapField])) {
                $entity[$mcField] = $entry[$ldapField];
            } else {
                $entity[$mcField] = '';
            }
        }
        $ldapEntries[$key] = $entity;
    }

    return $ldapEntries;
}

function synchronizeUsers(array $ldapUsers, array $maarchUsers)
{
    $maarchUsersLogin = [];
    foreach ($maarchUsers as $maarchUser) {
        $maarchUsersLogin[$maarchUser['user_id']] = $maarchUser;
    }
    $ldapUsersLogin = [];
    foreach ($ldapUsers as $ldapUser) {
        $ldapUsersLogin[$ldapUser['user_id']] = $ldapUser;
    }

    foreach ($ldapUsers as $user) {
        $user['userId'] = $user['user_id'];
        if (!empty($maarchUsersLogin[$user['userId']])) {
            $user = array_merge($maarchUsersLogin[$user['userId']], $user);
            if ($maarchUsersLogin[$user['userId']]['status'] == 'DEL' || $maarchUsersLogin[$user['userId']]['status'] == 'SPD') {
                $update = \User\models\UserModel::update([
                    'set'   => ['status' => 'OK'],
                    'where' => ['id = ?'],
                    'data'  => [$user['id']]
                ]);
                if (!$update) {
                    writeLog(['message' => "[ERROR] Update user status failed"]);
                    continue;
                }
            }
            if ($user['firstname'] != $maarchUsersLogin[$user['user_id']]['firstname']
                || $user['lastname'] != $maarchUsersLogin[$user['user_id']]['lastname']
                || $user['phone'] != $maarchUsersLogin[$user['user_id']]['phone']
                || $user['mail'] != $maarchUsersLogin[$user['user_id']]['mail']
            ) {
                $curlResponse = \SrcCore\models\CurlModel::execSimple([
                    'url'           => rtrim($GLOBALS['maarchUrl'], '/') . '/rest/users/' . $maarchUsersLogin[$user['user_id']]['id'],
                    'basicAuth'     => ['user' => $GLOBALS['user'], 'password' => $GLOBALS['password']],
                    'headers'       => ['content-type:application/json'],
                    'method'        => 'PUT',
                    'body'          => json_encode($user)
                ]);
                if ($curlResponse['code'] != 204) {
                    writeLog(['message' => "[ERROR] Update user failed : {".json_encode($user).$curlResponse['response']['errors']."}"]);
                    continue;
                }
                if (!empty($user['entityId'])) {
                    userAddEntity($maarchUsersLogin[$user['user_id']]['id'], $user);
                }
            }
        } else {
            $control = controlUser($user);
            if (!empty($control['errors'])) {
                writeLog(['message' => "[ERROR] Control create user [{$maarchUsersLogin[$user['userId']]['userId']}] failed : {$control['errors']}"]);
                continue;
            }

            $curlResponse = \SrcCore\models\CurlModel::execSimple([
                'url'           => rtrim($GLOBALS['maarchUrl'], '/') . '/rest/users',
                'basicAuth'     => ['user' => $GLOBALS['user'], 'password' => $GLOBALS['password']],
                'headers'       => ['content-type:application/json'],
                'method'        => 'POST',
                'body'          => json_encode($user)
            ]);
            if ($curlResponse['code'] != 200) {
                writeLog(['message' => "[ERROR] Create user failed : {".json_encode($user).$curlResponse['response']['errors']."}"]);
                continue;
            }

            userAddEntity($curlResponse['response']['id'], $user);
        }
    }

    $finalMaarchUsers = \User\models\UserModel::get(['select' => ['id','user_id', 'firstname', 'lastname', 'phone', 'mail', 'status'],'where' => ["status not in ('DEL','SPD')"]]);
    foreach ($finalMaarchUsers as $user) {
        $compare = true;
        foreach ($ldapUsers as $v) {
            if($user['user_id'] == $v['user_id']) 
            {
                $compare = false;
            }
        }
        if($compare){
            if ($GLOBALS['user'] != $user['user_id'] &&
            $user['user_id'] != 'superadmin') 
            {
                $delete = \User\models\UserModel::update([
                    'set'   => ['status' => 'SPD'],
                    'where' => ['id = ?'],
                    'data'  => [$user['id']]
                ]);
            }
        }
    }

    return true;
}

function synchronizeEntities(array $ldapEntities, array $maarchEntities)
{
    $maarchEntitiesId = [];
    foreach ($maarchEntities as $maarchEntity) {
        $maarchEntitiesId[$maarchEntity['entity_id']] = $maarchEntity;
    }
    $ldapEntitiesId = [];
    foreach ($ldapEntities as $ldapEntity) {
        $ldapEntitiesId[$ldapEntity['entity_id']] = $ldapEntity;
    }

    foreach ($ldapEntities as $entity) {
        if (!empty($maarchEntitiesId[$entity['entity_id']])) {
            if ($entity['entity_label'] != $maarchEntitiesId[$entity['entity_id']]['entity_label']
                || $entity['parent_entity_id'] != $maarchEntitiesId[$entity['entity_id']]['parent_entity_id']
            ) {
                $entity['short_label'] = $maarchEntitiesId[$entity['entity_id']]['entity_id'];
                $entity['entity_type'] = $maarchEntitiesId[$entity['entity_id']]['entity_type'];
                $curlResponse = \SrcCore\models\CurlModel::execSimple([
                    'url'           => rtrim($GLOBALS['maarchUrl'], '/') . '/rest/entities/' . $entity['entity_id'],
                    'basicAuth'     => ['user' => $GLOBALS['user'], 'password' => $GLOBALS['password']],
                    'headers'       => ['content-type:application/json'],
                    'method'        => 'PUT',
                    'body'          => json_encode($entity)
                ]);
                if ($curlResponse['code'] != 200) {
                    writeLog(['message' => "[ERROR] Update entity failed : {$curlResponse['response']['errors']}"]);
                }
            }
        } else {
            $entity['short_label'] = $entity['entity_id'];
            $entity['entity_type'] = 'Service';

            $maarchEntityParent = \Entity\models\EntityModel::get(['select' => ['id'], 'where' => ['entity_id = ?'], 'data' => [$entity['parent_entity_id']]]);

            if(empty($maarchEntityParent)) 
            {
                writeLog(['message' => "[ERROR] Create entity failed : Entity parent ".$entity['parent_entity_id']." not already present in maarch "]);
                continue ; 
            }

            $curlResponse = \SrcCore\models\CurlModel::execSimple([
                'url'           => rtrim($GLOBALS['maarchUrl'], '/') . '/rest/entities',
                'basicAuth'     => ['user' => $GLOBALS['user'], 'password' => $GLOBALS['password']],
                'headers'       => ['content-type:application/json'],
                'method'        => 'POST',
                'body'          => json_encode($entity)
            ]);
            if ($curlResponse['code'] != 200) {
                writeLog(['message' => "[ERROR] Create entity failed : {$curlResponse['response']['errors']}"]);
            }
        }
    }
    
    return true;
}

function writeLog(array $args)
{
    if (strpos($args['message'], '[ERROR]') === 0) {
        \SrcCore\controllers\LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'synchronizationLddap',
            'level'     => 'ERROR',
            'tableName' => '',
            'recordId'  => 'synchronizationLddap',
            'eventType' => 'synchronizationLddap',
            'eventId'   => $args['message']
        ]);
    } else {
        \SrcCore\controllers\LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'synchronizationLddap',
            'level'     => 'INFO',
            'tableName' => '',
            'recordId'  => 'synchronizationLddap',
            'eventType' => 'synchronizationLddap',
            'eventId'   => $args['message']
        ]);
    }
}

function controlUser(array $user)
{
    if (!\Respect\Validation\Validator::stringType()->length(1, 128)->notEmpty()->validate($user['userId'] ?? null) || !preg_match("/^[\w.@-]*$/", $user['userId'])) {
        return ['errors' => 'Body userId is empty, not a string or not valid'];
    } elseif (!\Respect\Validation\Validator::stringType()->length(1, 255)->notEmpty()->validate($user['firstname'] ?? null)) {
        return ['errors' => 'Body firstname is empty or not a string'];
    } elseif (!\Respect\Validation\Validator::stringType()->length(1, 255)->notEmpty()->validate($user['lastname'] ?? null)) {
        return ['errors' => 'Body lastname is empty or not a string'];
    } elseif (!\Respect\Validation\Validator::stringType()->length(1, 255)->notEmpty()->validate($user['mail'] ?? null) || !filter_var($user['mail'], FILTER_VALIDATE_EMAIL)) {
        return ['errors' => 'Body mail is empty or not valid'];
    } elseif (!empty($user['phone']) && (!preg_match("/\+?((|\ |\.|\(|\)|\-)?(\d)*)*\d$/", $user['phone']) || !\Respect\Validation\Validator::stringType()->length(0, 32)->validate($user['phone'] ?? ''))) {
        return ['errors' => 'Body phone is not valid'];
    }

    return true;
}

function userAddEntity($userId, $user)
{
    $entityId = null;
    $entityExists = \Entity\models\EntityModel::getByEntityId(['entityId' => $user['entityId'], 'select' => [1]]);
    $defaultEntityExists = \Entity\models\EntityModel::getByEntityId(['entityId' => $user['defaultEntity'], 'select' => [1]]);

    if(count($entityExists) > 0) $entityId = $user['entityId'];
    else if (count($defaultEntityExists) > 0) $entityId = $user['defaultEntity'];

    if(!empty($entityId))
    {
        $curlResponse = \SrcCore\models\CurlModel::execSimple([
            'url'           => rtrim($GLOBALS['maarchUrl'], '/') . '/rest/users/' . $userId . '/entities',
            'basicAuth'     => ['user' => $GLOBALS['user'], 'password' => $GLOBALS['password']],
            'headers'       => ['content-type:application/json'],
            'method'        => 'POST',
            'body'          => json_encode(['entityId' => $entityId])
        ]);
        if ($curlResponse['code'] != 200) {
            writeLog(['message' => "[ERROR] Add entity to user failed : {$curlResponse['response']['errors']}"]);
        }
    }
    else{
        writeLog(['message' => "[ERROR] Add entity to user failed : {Entity not found}"]);
    }
}