<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief User Controller
* @author dev@maarch.org
*/

namespace User\controllers;

use Basket\models\BasketModel;
use Basket\models\GroupBasketModel;
use Basket\models\RedirectBasketModel;
use Configuration\models\ConfigurationModel;
use ContentManagement\controllers\DocumentEditorController;
use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use Email\controllers\EmailController;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use Entity\models\ListTemplateItemModel;
use Entity\models\ListTemplateModel;
use Firebase\JWT\JWT;
use Group\controllers\PrivilegeController;
use Group\models\GroupModel;
use History\controllers\HistoryController;
use History\models\HistoryModel;
use Notification\controllers\NotificationsEventsController;
use Parameter\models\ParameterModel;
use Resource\controllers\ResController;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use Resource\models\UserFollowedResourceModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\AuthenticationController;
use SrcCore\controllers\PasswordController;
use SrcCore\controllers\UrlController;
use SrcCore\models\AuthenticationModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\ValidatorModel;
use Template\models\TemplateModel;
use User\models\UserBasketPreferenceModel;
use User\models\UserEmailSignatureModel;
use User\models\UserEntityModel;
use User\models\UserGroupModel;
use User\models\UserModel;
use User\models\UserSignatureModel;

class UserController
{
    const ALTERNATIVES_CONNECTIONS_METHODS = ['sso', 'cas', 'ldap', 'keycloak', 'shibboleth'];

    public function get(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_users', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (UserController::isRoot(['id' => $GLOBALS['id']])) {
            $users = UserModel::get([
                'select'    => ['id', 'user_id', 'firstname', 'lastname', 'status', 'mail', 'mode'],
                'where'     => ['status != ?'],
                'data'      => ['DEL']
            ]);
        } else {
            $entities = EntityModel::getAllEntitiesByUserId(['userId' => $GLOBALS['id']]);
            $users = [];
            if (!empty($entities)) {
                $users = UserEntityModel::getWithUsers([
                    'select'    => ['DISTINCT users.id', 'users.user_id', 'firstname', 'lastname', 'status', 'mail', 'mode'],
                    'where'     => ['users_entities.entity_id in (?)', 'status != ?'],
                    'data'      => [$entities, 'DEL']
                ]);
            }
            $usersNoEntities = UserEntityModel::getUsersWithoutEntities(['select' => ['id', 'users.user_id', 'firstname', 'lastname', 'status', 'mail', 'mode']]);
            $users = array_merge($users, $usersNoEntities);
        }

        $quota = [];
        $userQuota = ParameterModel::getById(['id' => 'user_quota', 'select' => ['param_value_int']]);
        if (!empty($userQuota['param_value_int'])) {
            $activeUser = UserModel::get(['select' => ['count(1)'], 'where' => ['status = ?', 'mode != ?'], 'data' => ['OK', 'root_invisible']]);
            $inactiveUser = UserModel::get(['select' => ['count(1)'], 'where' => ['status = ?', 'mode != ?'], 'data' => ['SPD', 'root_invisible']]);
            $quota = ['actives' => $activeUser[0]['count'], 'inactives' => $inactiveUser[0]['count'], 'userQuota' => $userQuota['param_value_int']];
        }

        return $response->withJson(['users' => $users, 'quota' => $quota]);
    }

    public function getById(Request $request, Response $response, array $args)
    {
        $user = UserModel::getById(['id' => $args['id'], 'select' => ['id', 'firstname', 'lastname', 'status']]);
        if (empty($user)) {
            return $response->withStatus(400)->withJson(['errors' => 'User does not exist']);
        }
        $user['enabled'] = $user['status'] != 'SPD';
        unset($user['status']);

        return $response->withJson($user);
    }

    public function getDetailledById(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['id', 'user_id', 'firstname', 'lastname', 'status', 'phone', 'mail', 'initials', 'mode', 'external_id']]);
        $user['external_id']        = json_decode($user['external_id'], true);

        if ($GLOBALS['id'] == $aArgs['id'] || PrivilegeController::hasPrivilege(['privilegeId' => 'view_personal_data', 'userId' => $GLOBALS['id']])) {
            $user['signatures'] = UserSignatureModel::getByUserSerialId(['userSerialid' => $aArgs['id']]);
            $user['emailSignatures'] = UserEmailSignatureModel::getByUserId(['userId' => $user['id']]);
        } else {
            $user['signatures'] = [];
            $user['emailSignatures'] = [];
            unset($user['phone']);
        }

        $user['groups']             = UserModel::getGroupsByLogin(['login' => $user['user_id']]);
        $user['allGroups']          = GroupModel::getAvailableGroupsByUserId(['userId' => $user['id'], 'administratorId' => $GLOBALS['id']]);
        $user['entities']           = UserModel::getEntitiesById(['id' => $aArgs['id'], 'select' => ['entities.id', 'users_entities.entity_id', 'entities.entity_label', 'users_entities.user_role', 'users_entities.primary_entity'], 'orderBy' => ['users_entities.primary_entity DESC']]);
        $user['allEntities']        = EntityModel::getAvailableEntitiesForAdministratorByUserId(['userId' => $user['user_id'], 'administratorUserId' => $GLOBALS['login']]);
        $user['baskets']            = BasketModel::getBasketsByLogin(['login' => $user['user_id']]);
        $user['assignedBaskets']    = RedirectBasketModel::getAssignedBasketsByUserId(['userId' => $user['id']]);
        $user['redirectedBaskets']  = RedirectBasketModel::getRedirectedBasketsByUserId(['userId' => $user['id']]);
        $user['history']            = HistoryModel::get(['select' => ['record_id', 'event_date', 'info', 'remote_ip'], 'where' => ['user_id = ?'], 'data' => [$aArgs['id']], 'orderBy' => ['event_date DESC'], 'limit' => 500]);
        $user['canModifyPassword']              = false;
        $user['canSendActivationNotification']  = false;
        $user['canCreateMaarchParapheurUser']   = false;

        if ($user['mode'] == 'rest') {
            $user['canModifyPassword'] = true;
        }
        $loggingMethod = CoreConfigModel::getLoggingMethod();
        if ($user['mode'] != 'rest' && $loggingMethod['id'] == 'standard') {
            $user['canSendActivationNotification'] = true;
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if ((string)$loadedXml->signatoryBookEnabled == 'maarchParapheur' && $user['mode'] != 'rest' && empty($user['external_id']['maarchParapheur'])) {
            $user['canCreateMaarchParapheurUser'] = true;
        }

        return $response->withJson($user);
    }

    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_users', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::stringType()->notEmpty()->validate($data['userId']) && preg_match("/^[\w.@-]*$/", $data['userId']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['firstname']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['lastname']);
        $check = $check && (empty($data['mail']) || filter_var($data['mail'], FILTER_VALIDATE_EMAIL));
        if (PrivilegeController::hasPrivilege(['privilegeId' => 'manage_personal_data', 'userId' => $GLOBALS['id']])) {
            $check = $check && (empty($data['phone']) || preg_match("/\+?((|\ |\.|\(|\)|\-)?(\d)*)*\d$/", $data['phone']));
        }
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $loggingMethod = CoreConfigModel::getLoggingMethod();
        $existingUser = UserModel::getByLowerLogin(['login' => $data['userId'], 'select' => ['id', 'status', 'mail']]);

        if (!empty($existingUser) && $existingUser['status'] == 'DEL') {
            UserModel::update([
                'set'   => [
                    'status'    => 'OK',
                    'password'  => AuthenticationModel::getPasswordHash(AuthenticationModel::generatePassword()),
                ],
                'where' => ['id = ?'],
                'data'  => [$existingUser['id']]
            ]);

            if ($loggingMethod['id'] == 'standard') {
                AuthenticationController::sendAccountActivationNotification(['userId' => $existingUser['id'], 'userEmail' => $existingUser['mail']]);
            }

            return $response->withJson(['id' => $existingUser['id']]);
        } elseif (!empty($existingUser)) {
            return $response->withStatus(400)->withJson(['errors' => _USER_ID_ALREADY_EXISTS]);
        }

        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_personal_data', 'userId' => $GLOBALS['id']])) {
            $data['phone'] = null;
        }

        $modes = ['standard', 'rest', 'root_visible', 'root_invisible'];
        if (empty($data['mode']) || !in_array($data['mode'], $modes)) {
            $data['mode'] = 'standard';
        }

        if (in_array($data['mode'], ['root_visible', 'root_invisible']) && !UserController::isRoot(['id' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $preferences = ['documentEdition' => 'java'];
        $allowedMethods = DocumentEditorController::getAllowedMethods();
        if (in_array('onlyoffice', $allowedMethods)) {
            $preferences = ['documentEdition' => 'onlyoffice'];
        }
        $data['preferences'] = json_encode($preferences);

        $id = UserModel::create(['user' => $data]);

        $userQuota = ParameterModel::getById(['id' => 'user_quota', 'select' => ['param_value_int']]);
        if (!empty($userQuota['param_value_int'])) {
            $activeUser = UserModel::get(['select' => ['count(1)'], 'where' => ['status = ?', 'mode != ?'], 'data' => ['OK', 'root_invisible']]);
            if ($activeUser[0]['count'] > $userQuota['param_value_int']) {
                NotificationsEventsController::fillEventStack(['eventId' => 'user_quota', 'tableName' => 'users', 'recordId' => 'quota_exceed', 'userId' => $GLOBALS['id'], 'info' => _QUOTA_EXCEEDED]);
            }
        }

        if ($loggingMethod['id'] == 'standard') {
            AuthenticationController::sendAccountActivationNotification(['userId' => $id, 'userEmail' => $data['mail']]);
        }

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['id'],
            'eventType'    => 'ADD',
            'eventId'      => 'userCreation',
            'info'         => _USER_CREATED . " {$data['userId']}"
        ]);

        return $response->withJson(['id' => $id]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['firstname'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body firstname is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['lastname'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body lastname is empty or not a string']);
        } elseif (!empty($body['mail']) && !filter_var($body['mail'], FILTER_VALIDATE_EMAIL)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body mail is not correct']);
        } elseif (PrivilegeController::hasPrivilege(['privilegeId' => 'manage_personal_data', 'userId' => $GLOBALS['id']]) && !empty($body['phone']) && !preg_match("/\+?((|\ |\.|\(|\)|\-)?(\d)*)*\d$/", $body['phone'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body phone is not correct']);
        }

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['status', 'mode']]);

        $set = [
            'firstname' => $body['firstname'],
            'lastname'  => $body['lastname'],
            'mail'      => $body['mail'],
            'initials'  => $body['initials'],
        ];

        if (PrivilegeController::hasPrivilege(['privilegeId' => 'manage_personal_data', 'userId' => $GLOBALS['id']])) {
            $set['phone'] = $body['phone'];
        }

        if (!empty($body['status']) && $body['status'] == 'OK') {
            $set['status'] = 'OK';
        }
        if (!empty($body['mode']) && $user['mode'] != $body['mode']) {
            if ((in_array($body['mode'], ['root_visible', 'root_invisible']) || in_array($user['mode'], ['root_visible', 'root_invisible'])) && !UserController::isRoot(['id' => $GLOBALS['id']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
            }
            $set['mode'] = $body['mode'];
        }

        $userQuota = ParameterModel::getById(['id' => 'user_quota', 'select' => ['param_value_int']]);

        UserModel::update([
            'set'   => $set,
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        if (!empty($userQuota['param_value_int'])) {
            if ($user['status'] == 'SPD' && $body['status'] == 'OK') {
                $activeUser = UserModel::get(['select' => ['count(1)'], 'where' => ['status = ?', 'mode != ?'], 'data' => ['OK', 'root_invisible']]);
                if ($activeUser[0]['count'] > $userQuota['param_value_int']) {
                    NotificationsEventsController::fillEventStack(['eventId' => 'user_quota', 'tableName' => 'users', 'recordId' => 'quota_exceed', 'userId' => $GLOBALS['id'], 'info' => _QUOTA_EXCEEDED]);
                }
            }
        }

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['id'],
            'eventType'    => 'UP',
            'eventId'      => 'userModification',
            'info'         => _USER_UPDATED . " {$body['firstname']} {$body['lastname']}"
        ]);

        return $response->withStatus(204);
    }

    public function isDeletable(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'delete' => true, 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $isListInstanceDeletable = true;
        $isListTemplateDeletable = true;

        $listInstanceEntities = [];
        $listInstanceResIds = [];
        $listInstances = ListInstanceModel::getWhenOpenMailsByUserId(['select' => ['listinstance.res_id', 'res_letterbox.destination'], 'userId' => $aArgs['id'], 'itemMode' => 'dest']);
        foreach ($listInstances as $listInstance) {
            if (!ResController::hasRightByResId(['resId' => [$listInstance['res_id']], 'userId' => $GLOBALS['id']])) {
                $isListInstanceDeletable = false;
            }
            $listInstanceResIds[] = $listInstance['res_id'];
            if (!empty($listInstance['destination'])) {
                $listInstanceEntities[] = $listInstance['destination'];
            }
        }

        $listTemplateEntities = [];
        $listTemplates = ListTemplateModel::getWithItems([
            'select'    => ['entity_id', 'title'],
            'where'     => ['item_id = ?', 'type = ?', 'item_mode = ?', 'item_type = ?', 'entity_id is not null'],
            'data'      => [$aArgs['id'], 'diffusionList', 'dest', 'user']
        ]);
        $allEntities = EntityModel::getAllEntitiesByUserId(['userId' => $GLOBALS['id']]);
        if (!empty($allEntities)) {
            $allEntities = EntityModel::get(['select' => ['id'], 'where' => ['entity_id in (?)'], 'data' => [$allEntities]]);
            $allEntities = array_column($allEntities, 'id');
        }
        foreach ($listTemplates as $listTemplate) {
            if (!in_array($listTemplate['entity_id'], $allEntities)) {
                $isListTemplateDeletable = false;
            }
            $listTemplateEntities[] = $listTemplate['entity_id'];
        }

        if (!$isListInstanceDeletable || !$isListTemplateDeletable) {
            $formattedLIEntities = [];
            $listInstanceEntities = array_unique($listInstanceEntities);
            foreach ($listInstanceEntities as $listInstanceEntity) {
                $entity = Entitymodel::getByEntityId(['select' => ['short_label'], 'entityId' => $listInstanceEntity]);
                $formattedLIEntities[] = $entity['short_label'];
            }
            $formattedLTEntities = [];
            $listTemplateEntities = array_unique($listTemplateEntities);
            foreach ($listTemplateEntities as $listTemplateEntity) {
                $entity = Entitymodel::getById(['select' => ['short_label'], 'id' => $listTemplateEntity]);
                $formattedLTEntities[] = $entity['short_label'];
            }

            return $response->withJson(['isDeletable' => false, 'listInstanceEntities' => $formattedLIEntities, 'listTemplateEntities' => $formattedLTEntities]);
        }

        $listInstances = [];
        foreach ($listInstanceResIds as $listInstanceResId) {
            $rawListInstances = ListInstanceModel::get([
                'select'    => ['*'],
                'where'     => ['res_id = ?', 'difflist_type = ?'],
                'data'      => [$listInstanceResId, 'entity_id'],
                'orderBy'   => ['listinstance_id']
            ]);
            $listInstances[] = [
                'resId'         => $listInstanceResId,
                'listInstances' => $rawListInstances
            ];
        }

        return $response->withJson(['isDeletable' => true, 'listTemplates' => $listTemplates, 'listInstances' => $listInstances]);
    }

    public function suspend(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'delete' => true, 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['firstname', 'lastname', 'user_id', 'mode']]);
        if (in_array($user['mode'], ['root_visible', 'root_invisible']) && !UserController::isRoot(['id' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $listInstances = ListInstanceModel::getWhenOpenMailsByUserId(['select' => [1], 'userId' => $aArgs['id'], 'itemMode' => 'dest']);
        if (!empty($listInstances)) {
            return $response->withStatus(403)->withJson(['errors' => 'User is still present in listInstances']);
        }

        $listTemplates = ListTemplateModel::getWithItems([
            'select'    => [1],
            'where'     => ['item_id = ?', 'type = ?', 'item_mode = ?', 'item_type = ?', 'entity_id is not null'],
            'data'      => [$aArgs['id'], 'diffusionList', 'dest', 'user']
        ]);
        if (!empty($listTemplates)) {
            return $response->withStatus(403)->withJson(['errors' => 'User is still present in listTemplates']);
        }

        ListInstanceModel::delete([
            'where' => ['item_id = ?', 'difflist_type = ?', 'item_type = ?', 'item_mode != ?'],
            'data'  => [$aArgs['id'], 'entity_id', 'user_id', 'dest']
        ]);
        RedirectBasketModel::delete([
            'where' => ['owner_user_id = ? OR actual_user_id = ?'],
            'data'  => [$aArgs['id'], $aArgs['id']]
        ]);

        UserModel::update([
            'set'   => [
                'status'   => 'SPD'
            ],
            'where' => ['id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['id'],
            'eventType'    => 'DEL',
            'eventId'      => 'userSuppression',
            'info'         => _USER_SUSPENDED . " {$user['firstname']} {$user['lastname']}"
        ]);

        return $response->withStatus(204);
    }

    public function delete(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'delete' => true, 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['firstname', 'lastname', 'user_id', 'mode']]);
        if (in_array($user['mode'], ['root_visible', 'root_invisible']) && !UserController::isRoot(['id' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $listInstances = ListInstanceModel::getWhenOpenMailsByUserId(['select' => [1], 'userId' => $aArgs['id'], 'itemMode' => 'dest']);
        if (!empty($listInstances)) {
            return $response->withStatus(403)->withJson(['errors' => 'User is still present in listInstances']);
        }

        $listTemplates = ListTemplateModel::getWithItems([
            'select'    => [1],
            'where'     => ['item_id = ?', 'type = ?', 'item_mode = ?', 'item_type = ?', 'entity_id is not null'],
            'data'      => [$aArgs['id'], 'diffusionList', 'dest', 'user']
        ]);
        if (!empty($listTemplates)) {
            return $response->withStatus(403)->withJson(['errors' => 'User is still present in listTemplates']);
        }

        ListInstanceModel::delete([
            'where' => ['item_id = ?', 'difflist_type = ?', 'item_type = ?', 'item_mode != ?'],
            'data'  => [$aArgs['id'], 'entity_id', 'user_id', 'dest']
        ]);
        ListTemplateItemModel::delete([
            'where' => ['item_id = ?', 'item_type = ?'],
            'data'  => [$aArgs['id'], 'user']
        ]);
        ListTemplateModel::deleteNoItemsOnes();
        RedirectBasketModel::delete([
            'where' => ['owner_user_id = ? OR actual_user_id = ?'],
            'data'  => [$aArgs['id'], $aArgs['id']]
        ]);

        // Delete from groups
        UserGroupModel::delete(['where' => ['user_id = ?'], 'data' => [$aArgs['id']]]);
        UserBasketPreferenceModel::delete([
            'where' => ['user_serial_id = ?'],
            'data'  => [$aArgs['id']]
        ]);
        RedirectBasketModel::delete([
            'where' => ['owner_user_id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        UserEntityModel::delete([
            'where' => ['user_id = ?'],
            'data'  => [$aArgs['id']]
        ]);

        UserModel::delete(['id' => $aArgs['id']]);

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['id'],
            'eventType'    => 'DEL',
            'eventId'      => 'userSuppression',
            'info'         => _USER_DELETED . " {$user['firstname']} {$user['lastname']}"
        ]);

        return $response->withStatus(204);
    }

    public function getProfile(Request $request, Response $response)
    {
        $user = UserModel::getById(['id' => $GLOBALS['id'], 'select' => ['id', 'user_id', 'firstname', 'lastname', 'phone', 'mail', 'initials', 'preferences', 'external_id', 'status', 'mode']]);
        $user['external_id']        = json_decode($user['external_id'], true);
        $user['preferences']        = json_decode($user['preferences'], true);
        $user['signatures']         = UserSignatureModel::getByUserSerialId(['userSerialid' => $user['id']]);
        $user['emailSignatures']    = UserEmailSignatureModel::getByUserId(['userId' => $GLOBALS['id']]);
        $user['groups']             = UserModel::getGroupsByLogin(['login' => $user['user_id']]);
        $user['entities']           = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['entities.id', 'users_entities.entity_id', 'entities.entity_label', 'users_entities.user_role', 'users_entities.primary_entity'], 'orderBy' => ['users_entities.primary_entity DESC']]);
        $user['baskets']            = BasketModel::getBasketsByLogin(['login' => $user['user_id']]);
        $user['assignedBaskets']    = RedirectBasketModel::getAssignedBasketsByUserId(['userId' => $user['id']]);
        $user['redirectedBaskets']  = RedirectBasketModel::getRedirectedBasketsByUserId(['userId' => $user['id']]);
        $user['regroupedBaskets']   = BasketModel::getRegroupedBasketsByUserId(['userId' => $user['user_id']]);
        $user['passwordRules']      = PasswordModel::getEnabledRules();
        $user['canModifyPassword']  = true;
        $user['privileges']         = PrivilegeController::getPrivilegesByUser(['userId' => $user['id']]);
        $userFollowed = UserFollowedResourceModel::get(['select' => ['count(1) as nb'], 'where' => ['user_id = ?'], 'data' => [$GLOBALS['id']]]);
        $user['nbFollowedResources'] = $userFollowed[0]['nb'];

        $loggingMethod = CoreConfigModel::getLoggingMethod();
        if (in_array($loggingMethod['id'], self::ALTERNATIVES_CONNECTIONS_METHODS)) {
            $user['canModifyPassword'] = false;
        }

        foreach ($user['baskets'] as $key => $basket) {
            if (!$basket['allowed']) {
                unset($user['baskets'][$key]);
            }
            unset($user['baskets'][$key]['basket_clause']);
        }
        $user['baskets'] = array_values($user['baskets']);
        foreach ($user['groups'] as $key => $group) {
            unset($user['groups'][$key]['where_clause']);
        }
        foreach ($user['assignedBaskets'] as $key => $basket) {
            unset($user['assignedBaskets'][$key]['basket_clause']);
        }

        return $response->withJson($user);
    }

    public function updateProfile(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['firstname'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body firstname is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['lastname'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body lastname is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['mail']) || !filter_var($body['mail'], FILTER_VALIDATE_EMAIL)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body mail is empty or not a valid email']);
        } elseif (!empty($body['phone']) && !preg_match("/\+?((|\ |\.|\(|\)|\-)?(\d)*)*\d/", $body['phone'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body phone is not a valid phone number']);
        }

        UserModel::update([
            'set'   => [
                'firstname'     => $body['firstname'],
                'lastname'      => $body['lastname'],
                'mail'          => $body['mail'],
                'phone'         => $body['phone'],
                'initials'      => $body['initials']
            ],
            'where' => ['id = ?'],
            'data'  => [$GLOBALS['id']]
        ]);

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['id'],
            'eventType'    => 'UP',
            'eventId'      => 'userModification',
            'info'         => _USER_UPDATED . " {$body['firstname']} {$body['lastname']}"
        ]);

        return $response->withStatus(204);
    }

    public function updateCurrentUserPreferences(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $user = UserModel::getById(['id' => $GLOBALS['id'], 'select' => ['preferences', 'firstname', 'lastname']]);
        $preferences = json_decode($user['preferences'], true);

        if (!empty($body['documentEdition'])) {
            if (!in_array($body['documentEdition'], DocumentEditorController::DOCUMENT_EDITION_METHODS)) {
                return $response->withStatus(400)->withJson(['errors' => 'Body preferences[documentEdition] is not allowed']);
            }
            $preferences['documentEdition'] = $body['documentEdition'];
        }
        if (!empty($body['homeGroups'])) {
            $preferences['homeGroups'] = $body['homeGroups'];
        }

        UserModel::update([
            'set'   => [
                'preferences'   => json_encode($preferences)
            ],
            'where' => ['id = ?'],
            'data'  => [$GLOBALS['id']]
        ]);

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['id'],
            'eventType'    => 'UP',
            'eventId'      => 'userModification',
            'info'         => _USER_PREFERENCE_UPDATED . " {$user['firstname']} {$user['lastname']}"
        ]);

        return $response->withStatus(204);
    }

    public function updatePassword(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $body = $request->getParsedBody();

        $check = Validator::stringType()->notEmpty()->validate($body['currentPassword']);
        $check = $check && Validator::stringType()->notEmpty()->validate($body['newPassword']);
        $check = $check && Validator::stringType()->notEmpty()->validate($body['reNewPassword']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id', 'mode']]);
        if ($user['mode'] != 'rest' && $user['user_id'] != $GLOBALS['login']) {
            return $response->withStatus(403)->withJson(['errors' => 'Not allowed']);
        }

        if ($body['newPassword'] != $body['reNewPassword']) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        } elseif (!AuthenticationModel::authentication(['login' => $user['user_id'], 'password' => $body['currentPassword']])) {
            return $response->withStatus(401)->withJson(['errors' => _WRONG_PSW]);
        } elseif (!PasswordController::isPasswordValid(['password' => $body['newPassword']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Password does not match security criteria']);
        } elseif (!PasswordModel::isPasswordHistoryValid(['password' => $body['newPassword'], 'userSerialId' => $aArgs['id']])) {
            return $response->withStatus(400)->withJson(['errors' => _ALREADY_USED_PSW]);
        }

        UserModel::updatePassword(['id' => $aArgs['id'], 'password' => $body['newPassword']]);
        PasswordModel::setHistoryPassword(['userSerialId' => $aArgs['id'], 'password' => $body['newPassword']]);

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $aArgs['id'],
            'eventType'    => 'UP',
            'eventId'      => 'userModification',
            'info'         => _USER_PASSWORD_UPDATED
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function setRedirectedBaskets(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $data = $request->getParams();

        DatabaseModel::beginTransaction();
        foreach ($data as $key => $value) {
            if (empty($value['actual_user_id']) || empty($value['basket_id']) || empty($value['group_id'])) {
                DatabaseModel::rollbackTransaction();
                return $response->withStatus(400)->withJson(['errors' => 'Some data are empty']);
            }

            $userBasketPreference = UserBasketPreferenceModel::get([
                'select' => ['display'],
                'where'  => ['basket_id =?', 'group_serial_id = ?', 'user_serial_id = ?'],
                'data'   => [$value['basket_id'], $value['group_id'], $aArgs['id']]
            ]);

            if (empty($userBasketPreference)) {
                unset($data[$key]);
                continue;
            }

            $check = UserModel::getById(['id' => $value['actual_user_id'], 'select' => ['1']]);
            if (empty($check)) {
                DatabaseModel::rollbackTransaction();
                return $response->withStatus(400)->withJson(['errors' => 'User not found']);
            }

            $check = RedirectBasketModel::get([
                'select' => [1],
                'where'  => ['actual_user_id = ?', 'owner_user_id = ?', 'basket_id = ?', 'group_id = ?'],
                'data'   => [$value['actual_user_id'], $aArgs['id'], $value['basket_id'], $value['group_id']]
            ]);
            if (!empty($check)) {
                DatabaseModel::rollbackTransaction();
                return $response->withStatus(400)->withJson(['errors' => 'Redirection already exist']);
            }

            if (!empty($value['originalOwner'])) {
                RedirectBasketModel::update([
                    'actual_user_id'    => $value['actual_user_id'],
                    'basket_id'         => $value['basket_id'],
                    'group_id'          => $value['group_id'],
                    'owner_user_id'     => $value['originalOwner']
                ]);
                HistoryController::add([
                    'tableName'    => 'redirected_baskets',
                    'recordId'     => $GLOBALS['login'],
                    'eventType'    => 'UP',
                    'eventId'      => 'basketRedirection',
                    'info'         => _BASKET_REDIRECTION . " {$value['basket_id']} {$value['actual_user_id']}"
                ]);
                unset($data[$key]);
            }
        }

        if (!empty($data)) {
            foreach ($data as $value) {
                RedirectBasketModel::create([
                    'actual_user_id'    => $value['actual_user_id'],
                    'basket_id'         => $value['basket_id'],
                    'group_id'          => $value['group_id'],
                    'owner_user_id'     => $aArgs['id']
                ]);
                HistoryController::add([
                    'tableName'    => 'redirected_baskets',
                    'recordId'     => $GLOBALS['login'],
                    'eventType'    => 'UP',
                    'eventId'      => 'basketRedirection',
                    'info'         => _BASKET_REDIRECTION . " {$value['basket_id']} {$aArgs['id']} => {$value['actual_user_id']}"
                ]);
            }
        }

        DatabaseModel::commitTransaction();

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);

        $userBaskets = BasketModel::getBasketsByLogin(['login' => $user['user_id']]);

        if ($GLOBALS['login'] == $user['user_id']) {
            foreach ($userBaskets as $key => $basket) {
                if (!$basket['allowed']) {
                    unset($userBaskets[$key]);
                }
            }
            $userBaskets = array_values($userBaskets);
        }

        return $response->withJson([
            'redirectedBaskets' => RedirectBasketModel::getRedirectedBasketsByUserId(['userId' => $aArgs['id']]),
            'baskets'           => $userBaskets
        ]);
    }

    public function deleteRedirectedBasket(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $data = $request->getQueryParams();

        DatabaseModel::beginTransaction();

        $check = Validator::notEmpty()->arrayType()->validate($data['redirectedBasketIds']);
        if (!$check) {
            DatabaseModel::rollbackTransaction();
            return $response->withStatus(400)->withJson(['errors' => 'RedirectedBasketIds is empty or not an array']);
        }

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        if (empty($user)) {
            DatabaseModel::rollbackTransaction();
            return $response->withStatus(400)->withJson(['errors' => 'User not found']);
        }

        foreach ($data['redirectedBasketIds'] as $redirectedBasketId) {
            $redirectedBasket = RedirectBasketModel::get(['select' => ['actual_user_id', 'owner_user_id', 'basket_id'], 'where' => ['id = ?'], 'data' => [$redirectedBasketId]]);
            if (empty($redirectedBasket[0]) || ($redirectedBasket[0]['actual_user_id'] != $aArgs['id'] && $redirectedBasket[0]['owner_user_id'] != $aArgs['id'])) {
                DatabaseModel::rollbackTransaction();
                return $response->withStatus(403)->withJson(['errors' => 'Redirected basket out of perimeter']);
            }

            RedirectBasketModel::delete(['where' => ['id = ?'], 'data' => [$redirectedBasketId]]);

            HistoryController::add([
                'tableName'    => 'redirected_baskets',
                'recordId'     => $GLOBALS['login'],
                'eventType'    => 'DEL',
                'eventId'      => 'basketRedirection',
                'info'         => _BASKET_REDIRECTION_SUPPRESSION . " {$user['user_id']} : " . $redirectedBasket[0]['basket_id']
            ]);
        }

        DatabaseModel::commitTransaction();

        $userBaskets = BasketModel::getBasketsByLogin(['login' => $user['user_id']]);

        if ($GLOBALS['login'] == $user['user_id']) {
            foreach ($userBaskets as $key => $basket) {
                if (!$basket['allowed']) {
                    unset($userBaskets[$key]);
                }
            }
            $userBaskets = array_values($userBaskets);
        }

        return $response->withJson([
            'baskets'   => $userBaskets
        ]);
    }

    public function getStatusByUserId(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_users', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }
        
        $user = UserModel::getByLowerLogin(['login' => $aArgs['userId'], 'select' => ['status']]);

        if (empty($user)) {
            return $response->withJson(['status' => null]);
        }

        return $response->withJson(['status' => $user['status']]);
    }

    public function updateStatus(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $data = $request->getParams();

        $check = Validator::stringType()->notEmpty()->validate($data['status']);
        $check = $check && ($data['status'] == 'OK' || $data['status'] == 'ABS');
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        UserModel::updateStatus(['id' => $aArgs['id'], 'status' => $data['status']]);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id', 'firstname', 'lastname']]);
        $message = "{$user['firstname']} {$user['lastname']} ";
        $message .= ($data['status'] == 'ABS' ? _GO_ON_VACATION : _BACK_FROM_VACATION);

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $aArgs['id'],
            'eventType'    => $data['status'] == 'ABS' ? 'ABS' : 'PRE',
            'eventId'      => 'userabs',
            'info'         => $message
        ]);

        return $response->withJson(['user' => UserModel::getById(['id' => $aArgs['id'], 'select' => ['status']])]);
    }

    public function getImageContent(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['id']) || !Validator::intVal()->validate($aArgs['signatureId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'view_personal_data', 'userId' => $GLOBALS['id']])
            && $aArgs['id'] != $GLOBALS['id']) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $signatures = UserSignatureModel::get([
            'select'    => ['signature_path', 'signature_file_name'],
            'where'     => ['user_serial_id = ?', 'id = ?'],
            'data'      => [$aArgs['id'], $aArgs['signatureId']]
        ]);
        if (empty($signatures[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'Signature does not exist']);
        }

        $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return [];
        }

        $pathToSignature = $docserver['path_template'] . str_replace('#', '/', $signatures[0]['signature_path']) . $signatures[0]['signature_file_name'];
        $image = file_get_contents($pathToSignature);
        if ($image === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Signature not found on docserver']);
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($image);

        $response->write($image);

        return $response->withHeader('Content-Type', $mimeType);
    }

    public function addSignature(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_personal_data', 'userId' => $GLOBALS['id']])
            && $aArgs['id'] != $GLOBALS['id']) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['base64', 'name', 'label']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $file     = base64_decode($data['base64']);
        $tmpName  = "tmp_file_{$aArgs['id']}_" .rand(). "_{$data['name']}";

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($file);
        $size     = strlen($file);
        $type     = explode('/', $mimeType);
        $ext      = strtoupper(substr($data['name'], strrpos($data['name'], '.') + 1));

        $fileAccepted  = StoreController::isFileAllowed(['extension' => $ext, 'type' => $mimeType]);

        if (!$fileAccepted || $type[0] != 'image') {
            return $response->withStatus(400)->withJson(['errors' => _WRONG_FILE_TYPE]);
        } elseif ($size > 2000000) {
            return $response->withStatus(400)->withJson(['errors' => _MAX_SIZE_UPLOAD_REACHED . ' (2 MB)']);
        }

        file_put_contents(CoreConfigModel::getTmpPath() . $tmpName, $file);

        $storeInfos = DocserverController::storeResourceOnDocServer([
            'collId'            => 'templates',
            'docserverTypeId'   => 'TEMPLATES',
            'encodedResource'   => base64_encode($file),
            'format'            => $ext
        ]);

        if (!file_exists($storeInfos['path_template']. str_replace('#', '/', $storeInfos['destination_dir']) .$storeInfos['file_destination_name'])) {
            return $response->withStatus(500)->withJson(['errors' => $storeInfos['error'] .' '. _PATH_OF_DOCSERVER_UNAPPROACHABLE]);
        }

        UserSignatureModel::create([
            'userSerialId'      => $aArgs['id'],
            'signatureLabel'    => $data['label'],
            'signaturePath'     => $storeInfos['destination_dir'],
            'signatureFileName' => $storeInfos['file_destination_name'],
        ]);

        return $response->withJson([
            'signatures' => UserSignatureModel::getByUserSerialId(['userSerialid' => $aArgs['id']])
        ]);
    }

    public function updateSignature(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_personal_data', 'userId' => $GLOBALS['id']])
            && $aArgs['id'] != $GLOBALS['id']) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['label']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        UserSignatureModel::update([
            'signatureId'   => $aArgs['signatureId'],
            'userSerialId'  => $aArgs['id'],
            'label'         => $data['label']
        ]);

        return $response->withJson([
            'signature' => UserSignatureModel::getById(['id' => $aArgs['signatureId'], 'select' => ['id', 'user_serial_id', 'signature_label']])
        ]);
    }

    public function deleteSignature(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'manage_personal_data', 'userId' => $GLOBALS['id']])
            && $aArgs['id'] != $GLOBALS['id']) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $error = $this->hasUsersRights(['id' => $aArgs['id'], 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        UserSignatureModel::delete(['signatureId' => $aArgs['signatureId'], 'userSerialId' => $aArgs['id']]);

        return $response->withJson([
            'signatures' => UserSignatureModel::getByUserSerialId(['userSerialid' => $aArgs['id']])
        ]);
    }

    public function createCurrentUserEmailSignature(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['title', 'htmlBody']])) {
            return $response->withJson(['errors' => 'Bad Request']);
        }

        UserEmailSignatureModel::create([
            'userId'    => $GLOBALS['id'],
            'title'     => $data['title'],
            'htmlBody'  => $data['htmlBody']
        ]);

        return $response->withJson([
            'emailSignatures' => UserEmailSignatureModel::getByUserId(['userId' => $GLOBALS['id']])
        ]);
    }

    public function updateCurrentUserEmailSignature(Request $request, Response $response, array $aArgs)
    {
        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['title', 'htmlBody']])) {
            return $response->withJson(['errors' => 'Bad Request']);
        }

        UserEmailSignatureModel::update([
            'id'        => $aArgs['id'],
            'userId'    => $GLOBALS['id'],
            'title'     => $data['title'],
            'htmlBody'  => $data['htmlBody']
        ]);

        return $response->withJson([
            'emailSignature' => UserEmailSignatureModel::getById(['id' => $aArgs['id']])
        ]);
    }

    public function deleteCurrentUserEmailSignature(Request $request, Response $response, array $aArgs)
    {
        UserEmailSignatureModel::delete([
            'id'        => $aArgs['id'],
            'userId'    => $GLOBALS['id']
        ]);

        return $response->withJson(['emailSignatures' => UserEmailSignatureModel::getByUserId(['userId' => $GLOBALS['id']])]);
    }

    public function addGroup(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $data = $request->getParams();
        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['groupId']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $group = GroupModel::getByGroupId(['select' => ['id'], 'groupId' => $data['groupId']]);

        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        } elseif (UserModel::hasGroup(['id' => $aArgs['id'], 'groupId' => $data['groupId']])) {
            return $response->withStatus(400)->withJson(['errors' => _USER_ALREADY_LINK_GROUP]);
        }
        if (!PrivilegeController::canAssignGroup(['userId' => $GLOBALS['id'], 'groupId' => $group['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }
        if (empty($data['role'])) {
            $data['role'] = null;
        }

        UserGroupModel::create(['user_id' => $aArgs['id'], 'group_id' => $group['id'], 'role' => $data['role']]);

        $baskets = GroupBasketModel::get(['select' => ['basket_id'], 'where' => ['group_id = ?'], 'data' => [$data['groupId']]]);
        foreach ($baskets as $basket) {
            UserBasketPreferenceModel::create([
                'userSerialId'  => $aArgs['id'],
                'groupSerialId' => $group['id'],
                'basketId'      => $basket['basket_id'],
                'display'       => 'true'
            ]);
        }

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _USER_GROUP_CREATION . " : {$user['user_id']} {$data['groupId']}",
            'moduleId'  => 'user',
            'eventId'   => 'userModification',
        ]);

        return $response->withJson([
            'groups'    => UserModel::getGroupsByLogin(['login' => $user['user_id']]),
            'baskets'   => BasketModel::getBasketsByLogin(['login' => $user['user_id']])
        ]);
    }

    public function updateGroup(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $group = GroupModel::getByGroupId(['select' => ['id'], 'groupId' => $aArgs['groupId']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        if (!PrivilegeController::canAssignGroup(['userId' => $GLOBALS['id'], 'groupId' => $group['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();
        if (empty($data['role'])) {
            $data['role'] = '';
        }

        UserGroupModel::update(['set' => ['role' => $data['role']], 'where' => ['user_id = ?', 'group_id = ?'], 'data' => [$aArgs['id'], $group['id']]]);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _USER_GROUP_MODIFICATION . " : {$user['user_id']} {$aArgs['groupId']}",
            'moduleId'  => 'user',
            'eventId'   => 'userModification',
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function deleteGroup(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }
        $group = GroupModel::getByGroupId(['select' => ['id'], 'groupId' => $aArgs['groupId']]);
        if (empty($group)) {
            return $response->withStatus(400)->withJson(['errors' => 'Group not found']);
        }

        if (!PrivilegeController::canAssignGroup(['userId' => $GLOBALS['id'], 'groupId' => $group['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        UserGroupModel::delete(['where' => ['user_id = ?', 'group_id = ?'], 'data' => [$aArgs['id'], $group['id']]]);

        UserBasketPreferenceModel::delete([
            'where' => ['user_serial_id = ?', 'group_serial_id = ?'],
            'data'  => [$aArgs['id'], $group['id']]
        ]);
        RedirectBasketModel::delete([
            'where' => ['owner_user_id = ?', 'group_id = ?'],
            'data'  => [$aArgs['id'], $group['id']]
        ]);

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _USER_GROUP_SUPPRESSION . " : {$user['user_id']} {$aArgs['groupId']}",
            'moduleId'  => 'user',
            'eventId'   => 'userModification',
        ]);

        return $response->withJson([
            'groups'            => UserModel::getGroupsByLogin(['login' => $user['user_id']]),
            'baskets'           => BasketModel::getBasketsByLogin(['login' => $user['user_id']]),
            'redirectedBaskets' => RedirectBasketModel::getRedirectedBasketsByUserId(['userId' => $aArgs['id']]),
        ]);
    }

    public function getEntities(Request $request, Response $response, array $args)
    {
        $user = UserModel::getById(['id' => $args['id'], 'select' => ['user_id']]);
        if (empty($user)) {
            return $response->withStatus(400)->withJson(['errors' => 'User does not exist']);
        }

        $entities = UserModel::getEntitiesById(['id' => $args['id'], 'select' => ['entities.id', 'users_entities.entity_id', 'entities.entity_label', 'users_entities.user_role', 'users_entities.primary_entity'], 'orderBy' => ['users_entities.primary_entity DESC']]);

        return $response->withJson(['entities' => $entities]);
    }

    public function addEntity(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $data = $request->getParams();
        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['entityId']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }
        if (empty(entitymodel::getByEntityId(['entityId' => $data['entityId']]))) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        } elseif (UserModel::hasEntity(['id' => $aArgs['id'], 'entityId' => $data['entityId']])) {
            return $response->withStatus(400)->withJson(['errors' => _USER_ALREADY_LINK_ENTITY]);
        }
        if (empty($data['role'])) {
            $data['role'] = '';
        }
        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
        $primaryEntity = UserModel::getPrimaryEntityById(['id' => $aArgs['id'], 'select' => [1]]);
        $pEntity = 'N';
        if (empty($primaryEntity)) {
            $pEntity = 'Y';
        }

        UserEntityModel::addUserEntity(['id' => $aArgs['id'], 'entityId' => $data['entityId'], 'role' => $data['role'], 'primaryEntity' => $pEntity]);
        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _USER_ENTITY_CREATION . " : {$user['user_id']} {$data['entityId']}",
            'moduleId'  => 'user',
            'eventId'   => 'userModification',
        ]);

        return $response->withJson([
            'entities'      => UserModel::getEntitiesById(['id' => $aArgs['id'], 'select' => ['entities.id', 'users_entities.entity_id', 'entities.entity_label', 'users_entities.user_role', 'users_entities.primary_entity'], 'orderBy' => ['users_entities.primary_entity DESC']]),
            'allEntities'   => EntityModel::getAvailableEntitiesForAdministratorByUserId(['userId' => $user['user_id'], 'administratorUserId' => $GLOBALS['login']])
        ]);
    }

    public function updateEntity(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }
        if (empty(entitymodel::getByEntityId(['entityId' => $aArgs['entityId']]))) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $data = $request->getParams();
        if (empty($data['user_role'])) {
            $data['user_role'] = '';
        }

        UserEntityModel::updateUserEntity(['id' => $aArgs['id'], 'entityId' => $aArgs['entityId'], 'role' => $data['user_role']]);
        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _USER_ENTITY_MODIFICATION . " : {$aArgs['id']} {$aArgs['entityId']}",
            'moduleId'  => 'user',
            'eventId'   => 'userModification',
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function updatePrimaryEntity(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }
        if (empty(EntityModel::getByEntityId(['entityId' => $aArgs['entityId']]))) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        UserEntityModel::updateUserPrimaryEntity(['id' => $aArgs['id'], 'entityId' => $aArgs['entityId']]);

        return $response->withJson(['entities' => UserModel::getEntitiesById(['id' => $aArgs['id'], 'select' => ['entities.id', 'users_entities.entity_id', 'entities.entity_label', 'users_entities.user_role', 'users_entities.primary_entity'], 'orderBy' => ['users_entities.primary_entity DESC']])]);
    }

    public function deleteEntity(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }
        $entityInfo = EntityModel::getByEntityId(['entityId' => $aArgs['entityId'], 'select' => ['id']]);
        if (empty($entityInfo)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity not found']);
        }

        $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);

        $data = $request->getParams();
        if (!empty($data['mode'])) {
            $templateLists = ListTemplateModel::get(['select' => ['id'], 'where' => ['entity_id = ?'], 'data' => [$entityInfo['id']]]);
            if (!empty($templateLists)) {
                foreach ($templateLists as $templateList) {
                    ListTemplateItemModel::delete(['where' => ['list_template_id = ?'], 'data' => [$templateList['id']]]);
                }
            }

            if ($data['mode'] == 'reaffect') {
                $listInstances = ListInstanceModel::getWithConfidentiality(['select' => ['listinstance.res_id'], 'entityId' => $aArgs['entityId'], 'userId' => $aArgs['id']]);
                $resIdsToReplace = array_column($listInstances, 'res_id');
                if (!empty($resIdsToReplace)) {
                    ListInstanceModel::update([
                        'set'   => ['item_id' => $data['newUser']['serialId']],
                        'where' => ['res_id in (?)', 'item_id = ?', 'process_date is null'],
                        'data'  => [$resIdsToReplace, $aArgs['id']]
                    ]);
                }
            } else {
                $ressources = ResModel::getOnView([
                    'select'    => ['res_id'],
                    'where'     => ['confidentiality = ?', 'destination = ?', 'closing_date is null'],
                    'data'      => ['Y', $aArgs['entityId']]
                ]);
                foreach ($ressources as $ressource) {
                    $listInstanceId = ListInstanceModel::get([
                        'select'    => ['listinstance_id'],
                        'where'     => ['res_id = ?', 'item_id = ?', 'item_type = ?', 'difflist_type = ?', 'item_mode = ?', 'process_date is null'],
                        'data'      => [$ressource['res_id'], $aArgs['id'], 'user_id', 'VISA_CIRCUIT', 'sign']
                    ]);

                    if (!empty($listInstanceId)) {
                        ListInstanceModel::update([
                            'set'   => ['process_date' => null],
                            'where' => ['res_id = ?', 'difflist_type = ?', 'listinstance_id = ?'],
                            'data'  => [$ressource['res_id'], 'VISA_CIRCUIT', $listInstanceId[0]['listinstance_id'] - 1]
                        ]);
                        $listInstanceMinus = ListInstanceModel::get([
                            'select'    => ['requested_signature'],
                            'where'     => ['listinstance_id = ?'],
                            'data'      => [$listInstanceId[0]['listinstance_id'] - 1]
                        ]);
                        if ($listInstanceMinus[0]['requested_signature']) {
                            ResModel::update(['set' => ['status' => 'ESIG'], 'where' => ['res_id = ?'], 'data' => [$ressource['res_id']]]);
                        } else {
                            ResModel::update(['set' => ['status' => 'EVIS'], 'where' => ['res_id = ?'], 'data' => [$ressource['res_id']]]);
                        }
                    }
                }

                $listInstances = ListInstanceModel::getWithConfidentiality(['select' => ['listinstance.res_id', 'listinstance.difflist_type'], 'entityId' => $aArgs['entityId'], 'userId' => $aArgs['id']]);
                $resIdsToReplace = [];
                foreach ($listInstances as $listInstance) {
                    $resIdsToReplace[] = $listInstance['res_id'];
                }
                if (!empty($resIdsToReplace)) {
                    ListInstanceModel::update([
                        'set'   => ['process_comment' => '[DEL] supprimé - changement d\'entité', 'process_date' => 'CURRENT_TIMESTAMP'],
                        'where' => ['res_id in (?)', 'item_id = ?'],
                        'data'  => [$resIdsToReplace, $aArgs['id']]
                    ]);
                }
            }
        }

        $primaryEntity = UserModel::getPrimaryEntityById(['id' => $aArgs['id'], 'select' => ['entities.entity_label', 'entities.entity_id']]);
        UserEntityModel::deleteUserEntity(['id' => $aArgs['id'], 'entityId' => $aArgs['entityId']]);

        if (!empty($primaryEntity['entity_id']) && $primaryEntity['entity_id'] == $aArgs['entityId']) {
            UserEntityModel::reassignUserPrimaryEntity(['userId' => $aArgs['id']]);
        }

        HistoryController::add([
            'tableName' => 'users',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _USER_ENTITY_SUPPRESSION . " : {$user['user_id']} {$aArgs['entityId']}",
            'moduleId'  => 'user',
            'eventId'   => 'userModification',
        ]);

        return $response->withJson([
            'entities'      => UserModel::getEntitiesById(['id' => $aArgs['id'], 'select' => ['entities.id', 'users_entities.entity_id', 'entities.entity_label', 'users_entities.user_role', 'users_entities.primary_entity'], 'orderBy' => ['users_entities.primary_entity DESC']]),
            'allEntities'   => EntityModel::getAvailableEntitiesForAdministratorByUserId(['userId' => $user['user_id'], 'administratorUserId' => $GLOBALS['login']])
        ]);
    }

    public function isEntityDeletable(Request $request, Response $response, array $args)
    {
        $error = $this->hasUsersRights(['id' => $args['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }
        $entity = EntityModel::getByEntityId(['entityId' => $args['entityId'], 'select' => ['id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'Entity does not exist']);
        }

        $listInstances = ListInstanceModel::getWithConfidentiality(['select' => [1], 'entityId' => $args['entityId'], 'userId' => $args['id']]);

        $listTemplates = ListTemplateModel::getWithItems(['select' => [1], 'where' => ['entity_id = ?', 'item_type = ?', 'item_id = ?'], 'data' => [$entity['id'], 'user', $args['id']]]);

        return $response->withJson(['hasConfidentialityInstances' => !empty($listInstances), 'hasListTemplates' => !empty($listTemplates)]);
    }

    public function updateBasketsDisplay(Request $request, Response $response, array $aArgs)
    {
        $error = $this->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $data = $request->getParams();
        $check = Validator::arrayType()->notEmpty()->validate($data['baskets']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        foreach ($data['baskets'] as $basketContainer) {
            $check = Validator::stringType()->notEmpty()->validate($basketContainer['basketId']);
            $check = $check && Validator::intVal()->notEmpty()->validate($basketContainer['groupSerialId']);
            $check = $check && Validator::boolType()->validate($basketContainer['allowed']);
            if (!$check) {
                return $response->withStatus(400)->withJson(['errors' => 'Element is missing']);
            }
        }

        foreach ($data['baskets'] as $basketContainer) {
            $group = GroupModel::getById(['id' => $basketContainer['groupSerialId'], 'select' => ['group_id']]);
            $basket = BasketModel::getByBasketId(['basketId' => $basketContainer['basketId'], 'select' => [1]]);
            if (empty($group) || empty($basket)) {
                return $response->withStatus(400)->withJson(['errors' => 'Group or basket does not exist']);
            }

            $user = UserModel::getById(['id' => $aArgs['id'], 'select' => ['user_id']]);
            $groups = UserModel::getGroupsByLogin(['login' => $user['user_id']]);
            $groupFound = false;
            foreach ($groups as $value) {
                if ($value['id'] == $basketContainer['groupSerialId']) {
                    $groupFound = true;
                }
            }
            if (!$groupFound) {
                return $response->withStatus(400)->withJson(['errors' => 'Group is not linked to this user']);
            }
            $groups = GroupBasketModel::get(['where' => ['basket_id = ?'], 'data' => [$basketContainer['basketId']]]);
            $groupFound = false;
            foreach ($groups as $value) {
                if ($value['group_id'] == $group['group_id']) {
                    $groupFound = true;
                }
            }
            if (!$groupFound) {
                return $response->withStatus(400)->withJson(['errors' => 'Group is not linked to this basket']);
            }

            if ($basketContainer['allowed']) {
                $preference = UserBasketPreferenceModel::get([
                    'select'    => [1],
                    'where'     => ['user_serial_id = ?', 'group_serial_id = ?', 'basket_id = ?'],
                    'data'      => [$aArgs['id'], $basketContainer['groupSerialId'], $basketContainer['basketId']]
                ]);
                if (!empty($preference)) {
                    return $response->withStatus(400)->withJson(['errors' => 'Preference already exists']);
                }
                $basketContainer['userSerialId'] = $aArgs['id'];
                $basketContainer['display'] = 'true';
                UserBasketPreferenceModel::create($basketContainer);
            } else {
                UserBasketPreferenceModel::delete([
                    'where' => ['user_serial_id = ?', 'group_serial_id = ?', 'basket_id = ?'],
                    'data'  => [$aArgs['id'], $basketContainer['groupSerialId'], $basketContainer['basketId']]
                ]);
            }
        }

        return $response->withJson(['success' => 'success']);
    }

    public function getTemplates(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $entities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['users_entities.entity_id']]);
        $entities = array_column($entities, 'entity_id');
        if (empty($entities)) {
            $entities = [0];
        }

        $where = ['(templates_association.value_field in (?) OR templates_association.template_id IS NULL)'];
        $data = [$entities];
        if (!empty($queryParams['type'])) {
            $where[] = 'templates.template_type = ?';
            $data[] = strtoupper($queryParams['type']);
        }
        if (!empty($queryParams['target'])) {
            $where[] = 'templates.template_target = ?';
            $data[] = $queryParams['target'];
        }
        $templates = TemplateModel::getWithAssociation([
            'select'    => ['DISTINCT(templates.template_id)', 'templates.template_label', 'templates.template_file_name', 'templates.template_path', 'templates.template_target', 'templates.template_attachment_type'],
            'where'     => $where,
            'data'      => $data,
            'orderBy'   => ['templates.template_label']
        ]);

        $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);
        foreach ($templates as $key => $template) {
            $explodeFile = explode('.', $template['template_file_name']);
            $ext = $explodeFile[count($explodeFile) - 1];
            $exists = is_file($docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $template['template_path']) . $template['template_file_name']);

            $templates[$key] = [
                'id'                => $template['template_id'],
                'label'             => $template['template_label'],
                'extension'         => $ext,
                'exists'            => $exists,
                'target'            => $template['template_target'],
                'attachmentType'    => $template['template_attachment_type']
            ];
        }
        
        return $response->withJson(['templates' => $templates]);
    }

    public function updateCurrentUserBasketPreferences(Request $request, Response $response, array $aArgs)
    {
        $data = $request->getParsedBody();

        if (isset($data['color']) && $data['color'] == '') {
            UserBasketPreferenceModel::update([
                'set'   => ['color' => null],
                'where' => ['user_serial_id = ?', 'group_serial_id = ?', 'basket_id = ?'],
                'data'  => [$GLOBALS['id'], $aArgs['groupId'], $aArgs['basketId']]
            ]);
        } elseif (!empty($data['color'])) {
            UserBasketPreferenceModel::update([
                'set'   => ['color' => $data['color']],
                'where' => ['user_serial_id = ?', 'group_serial_id = ?', 'basket_id = ?'],
                'data'  => [$GLOBALS['id'], $aArgs['groupId'], $aArgs['basketId']]
            ]);
        }

        return $response->withJson([
            'userBaskets' => BasketModel::getRegroupedBasketsByUserId(['userId' => $GLOBALS['login']])
        ]);
    }

    public function sendAccountActivationNotification(Request $request, Response $response, array $args)
    {
        $control = $this->hasUsersRights(['id' => $args['id']]);
        if (!empty($control['error'])) {
            return $response->withStatus($control['status'])->withJson(['errors' => $control['error']]);
        }

        $loggingMethod = CoreConfigModel::getLoggingMethod();
        if ($loggingMethod['id'] != 'standard') {
            return $response->withStatus(403)->withJson(['errors' => 'Cannot send activation notification when not using standard connection']);
        }

        $user = UserModel::getById(['id' => $args['id'], 'select' => ['mail']]);

        AuthenticationController::sendAccountActivationNotification(['userId' => $args['id'], 'userEmail' => $user['mail']]);

        return $response->withStatus(204);
    }

    public function getExport(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_users', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (UserController::isRoot(['id' => $GLOBALS['id']])) {
            $users = UserModel::get([
                'select'    => ['id', 'user_id', 'firstname', 'lastname', 'mail', 'phone'],
                'where'     => ['status != ?'],
                'data'      => ['DEL']
            ]);
        } else {
            $entities = EntityModel::getAllEntitiesByUserId(['userId' => $GLOBALS['id']]);
            $users = [];
            if (!empty($entities)) {
                $users = UserEntityModel::getWithUsers([
                    'select'    => ['DISTINCT users.id', 'users.user_id', 'firstname', 'lastname', 'mail', 'phone'],
                    'where'     => ['users_entities.entity_id in (?)', 'status != ?'],
                    'data'      => [$entities, 'DEL']
                ]);
            }
            $usersNoEntities = UserEntityModel::getUsersWithoutEntities(['select' => ['id', 'users.user_id', 'firstname', 'lastname', 'mail', 'phone']]);
            $users = array_merge($users, $usersNoEntities);
        }

        $delimiter = ',';
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams['delimiter'])) {
            $queryParams['delimiter'] = urldecode($queryParams['delimiter']);
            if (in_array($queryParams['delimiter'], [',', ';', 'TAB'])) {
                $delimiter = ($queryParams['delimiter'] == 'TAB' ? "\t" : $queryParams['delimiter']);
            }
        }

        $file = fopen('php://temp', 'w');

        $csvHead = ['id', 'user_id', 'firstname', 'lastname', 'mail', 'phone'];
        fputcsv($file, $csvHead, $delimiter);

        foreach ($users as $user) {
            $csvContent = [$user['id'], $user['user_id'], utf8_decode($user['firstname']), utf8_decode($user['lastname']), utf8_decode($user['mail']), $user['phone']];
            fputcsv($file, $csvContent, $delimiter);
        }

        rewind($file);

        $response->write(stream_get_contents($file));
        $response = $response->withAddedHeader('Content-Disposition', 'attachment; filename=export_maarch.csv');
        $contentType = 'application/vnd.ms-excel';
        fclose($file);

        return $response->withHeader('Content-Type', $contentType);
    }

    public function hasUsersRights(array $args)
    {
        if (!is_numeric($args['id'])) {
            return ['status' => 400, 'error' => 'id must be an integer'];
        }

        $user = UserModel::getById(['id' => $args['id'], 'select' => ['id']]);
        if (empty($user['id'])) {
            return ['status' => 400, 'error' => 'User not found'];
        }

        if (empty($args['himself']) || $GLOBALS['id'] != $user['id']) {
            if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_users', 'userId' => $GLOBALS['id']])) {
                return ['status' => 403, 'error' => 'Service forbidden'];
            }
            $isRoot = UserController::isRoot(['id' => $GLOBALS['id']]);
            if (!$isRoot) {
                $users = [];
                $entities = EntityModel::getAllEntitiesByUserId(['userId' => $GLOBALS['id']]);
                if (!empty($entities)) {
                    $users = UserEntityModel::getWithUsers([
                        'select'    => ['users.id'],
                        'where'     => ['users_entities.entity_id in (?)', 'status != ?'],
                        'data'      => [$entities, 'DEL']
                    ]);
                }
                $usersNoEntities = UserEntityModel::getUsersWithoutEntities(['select' => ['id']]);
                $users = array_merge($users, $usersNoEntities);
                $allowed = false;
                foreach ($users as $value) {
                    if ($value['id'] == $args['id']) {
                        $allowed = true;
                    }
                }
                if (!$allowed) {
                    return ['status' => 403, 'error' => 'UserId out of perimeter'];
                }
            }
        } elseif ($args['delete'] && $GLOBALS['id'] == $user['id']) {
            return ['status' => 403, 'error' => 'Can not delete yourself'];
        }

        return true;
    }

    private function checkNeededParameters(array $aArgs)
    {
        foreach ($aArgs['needed'] as $value) {
            if (empty($aArgs['data'][$value])) {
                return false;
            }
        }

        return true;
    }

    public function forgotPassword(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['login'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body login is empty']);
        }

        $user = UserModel::getByLogin(['select' => ['id', 'mail'], 'login' => strtolower($body['login'])]);
        if (empty($user)) {
            return $response->withStatus(204);
        }

        $GLOBALS['id'] = $user['id'];
        $GLOBALS['login'] = $body['login'];

        $resetToken = AuthenticationController::getResetJWT(['id' => $user['id'], 'expirationTime' => 3600]); // 1 hour
        UserModel::update(['set' => ['reset_token' => $resetToken], 'where' => ['id = ?'], 'data' => [$user['id']]]);

        $url = UrlController::getCoreUrl() . 'dist/index.html#/reset-password?token=' . $resetToken;
        $configuration = ConfigurationModel::getByService(['service' => 'admin_email_server', 'select' => ['value']]);
        $configuration = json_decode($configuration['value'], true);
        if (!empty($configuration['from'])) {
            $sender = $configuration['from'];
        } else {
            $sender = $user['mail'];
        }
        $email = EmailController::createEmail([
            'userId'    => $user['id'],
            'data'      => [
                'sender'        => ['email' => $sender],
                'recipients'    => [$user['mail']],
                'object'        => _NOTIFICATIONS_FORGOT_PASSWORD_SUBJECT,
                'body'          => _NOTIFICATIONS_FORGOT_PASSWORD_BODY . '<a href="' . $url . '">'._CLICK_HERE.'</a>' . _NOTIFICATIONS_FORGOT_PASSWORD_FOOTER,
                'isHtml'        => true,
                'status'        => 'WAITING'
            ]
        ]);

        if (!empty($email['errors'])) {
            $historyMessage = $email['errors'];
        } else {
            $historyMessage = _PASSWORD_REINIT_SENT;
        }
        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['id'],
            'eventType'    => 'RESETPSW',
            'eventId'      => 'userModification',
            'info'         => $historyMessage
        ]);

        return $response->withStatus(204);
    }

    public function passwordInitialization(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $check = Validator::stringType()->notEmpty()->validate($body['token']);
        $check = $check && Validator::stringType()->notEmpty()->validate($body['password']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Body token or body password is empty']);
        }

        try {
            $jwt = JWT::decode($body['token'], CoreConfigModel::getEncryptKey(), ['HS256']);
        } catch (\Exception $e) {
            return $response->withStatus(403)->withJson(['errors' => 'Invalid token', 'lang' => 'invalidToken']);
        }

        $user = UserModel::getById(['id' => $jwt->user->id, 'select' => ['user_id', 'id', 'reset_token']]);
        if (empty($user)) {
            return $response->withStatus(400)->withJson(['errors' => 'User does not exist']);
        }

        if ($body['token'] != $user['reset_token']) {
            return $response->withStatus(403)->withJson(['errors' => 'Invalid token', 'lang' => 'invalidToken']);
        }

        if (!PasswordController::isPasswordValid(['password' => $body['password']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Password does not match security criteria']);
        }

        UserModel::resetPassword(['password' => $body['password'], 'id'  => $user['id']]);

        $GLOBALS['id'] = $user['id'];
        $GLOBALS['login'] = $user['user_id'];

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['id'],
            'eventType'    => 'UP',
            'eventId'      => 'userModification',
            'info'         => _PASSWORD_REINIT . " {$body['login']}"
        ]);

        return $response->withStatus(204);
    }

    public function getCurrentUserEmailSignatures(Request $request, Response $response)
    {
        $signatureModels = UserEmailSignatureModel::getByUserId(['userId' => $GLOBALS['id']]);

        $signatures = [];

        foreach ($signatureModels as $signature) {
            $signatures[] = [
                'id'      => $signature['id'],
                'label'   => $signature['title'],
            ];
        }

        return $response->withJson(['emailSignatures' => $signatures]);
    }

    public function getCurrentUserEmailSignatureById(Request $request, Response $response, array $args)
    {
        if (!Validator::notEmpty()->intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body param id is empty or not an integer']);
        }

        $signature = UserEmailSignatureModel::getById(['id' => $args['id']]);
        if (empty($signature) || $signature['user_id'] != $GLOBALS['id']) {
            return $response->withStatus(404)->withJson(['errors' => 'Signature not found']);
        }

        $signature = [
            'id'      => $signature['id'],
            'label'   => $signature['title'],
            'content' => $signature['html_body']
        ];

        return $response->withJson(['emailSignature' => $signature]);
    }

    public static function isRoot(array $args)
    {
        ValidatorModel::notEmpty($args, ['id']);
        ValidatorModel::intVal($args, ['id']);

        $user = UserModel::getById(['select' => ['mode'], 'id' => $args['id']]);

        $isRoot = ($user['mode'] == 'root_visible' || $user['mode'] == 'root_invisible');

        return $isRoot;
    }
}
