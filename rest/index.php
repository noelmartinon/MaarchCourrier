<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Rest Routes File
* @author dev@maarch.org
*/

require '../vendor/autoload.php';

//create session if NO SESSION
if (empty($_SESSION['user'])) {
    require_once('../core/class/class_functions.php');
    include_once('../core/init.php');
    require_once('core/class/class_portal.php');
    require_once('core/class/class_db.php');
    require_once('core/class/class_request.php');
    require_once('core/class/class_core_tools.php');
    require_once('core/class/web_service/class_web_service.php');
    require_once('core/services/CoreConfig.php');

    //load Maarch session vars
    $portal = new portal();
    $portal->unset_session();
    $portal->build_config();
    $coreTools = new core_tools();
    $_SESSION['custom_override_id'] = $coreTools->get_custom_id();
    if (isset($_SESSION['custom_override_id'])
        && ! empty($_SESSION['custom_override_id'])
        && isset($_SESSION['config']['corepath'])
        && ! empty($_SESSION['config']['corepath'])
    ) {
        $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR;
        set_include_path(
            $path . PATH_SEPARATOR . $_SESSION['config']['corepath']
            . PATH_SEPARATOR . get_include_path()
        );
    } elseif (isset($_SESSION['config']['corepath'])
        && ! empty($_SESSION['config']['corepath'])
    ) {
        set_include_path(
            $_SESSION['config']['corepath'] . PATH_SEPARATOR . get_include_path()
        );
    }
    // Load configuration from xml into session
    Core_CoreConfig_Service::buildCoreConfig('core' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml');
    $_SESSION['config']['app_id'] = $_SESSION['businessapps'][0]['appid'];
    require_once 'apps/' .$_SESSION['businessapps'][0]['appid']. '/class/class_business_app_tools.php';

    Core_CoreConfig_Service::buildBusinessAppConfig();

    // Load Modules configuration from xml into session
    Core_CoreConfig_Service::loadModulesConfig($_SESSION['modules']);
    Core_CoreConfig_Service::loadAppServices();
    Core_CoreConfig_Service::loadModulesServices($_SESSION['modules']);
}

//login management
if (empty($_SESSION['user'])) {
    require_once('apps/maarch_entreprise/class/class_login.php');
    $loginObj = new login();
    $loginMethods = $loginObj->build_login_method();
    require_once('core/services/Session.php');
    $oSessionService = new \Core_Session_Service();

    $loginObj->execute_login_script($loginMethods, true);
}

if ($_SESSION['error']) {
    echo $_SESSION['error'];
    exit();
}

if (strpos(getcwd(), '/rest')) {
    chdir('..');
}

$userId = null;
if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
    if (\SrcCore\models\SecurityModel::authentication(['userId' => $_SERVER['PHP_AUTH_USER'], 'password' => $_SERVER['PHP_AUTH_PW']])) {
        $loginMethod = \SrcCore\models\CoreConfigModel::getLoggingMethod();
        if ($loginMethod['id'] != 'standard') {
            $user = \User\models\UserModel::getByUserId(['select' => ['loginmode'], 'userId' => $_SERVER['PHP_AUTH_USER']]);
            if ($user['loginmode'] == 'restMode') {
                $userId = $_SERVER['PHP_AUTH_USER'];
            }
        } else {
            $userId = $_SERVER['PHP_AUTH_USER'];
        }
    }
} else {
    $cookie = \SrcCore\models\SecurityModel::getCookieAuth();
    if (!empty($cookie) && \SrcCore\models\SecurityModel::cookieAuthentication($cookie)) {
        \SrcCore\models\SecurityModel::setCookieAuth(['userId' => $cookie['userId']]);
        $userId = $cookie['userId'];
    }
}

if (empty($userId)) {
    echo 'Authentication Failed';
    exit();
}

$language = \SrcCore\models\CoreConfigModel::getLanguage();
require_once("src/core/lang/lang-{$language}.php");


$app = new \Slim\App(['settings' => ['displayErrorDetails' => true]]);


//Initialize
$app->get('/initialize', \SrcCore\controllers\CoreController::class . ':initialize');

//Actions
$app->get('/actions', \Action\controllers\ActionController::class . ':get');
$app->get('/initAction', \Action\controllers\ActionController::class . ':initAction');
$app->get('/actions/{id}', \Action\controllers\ActionController::class . ':getById');
$app->post('/actions', \Action\controllers\ActionController::class . ':create');
$app->put('/actions/{id}', \Action\controllers\ActionController::class . ':update');
$app->delete('/actions/{id}', \Action\controllers\ActionController::class . ':delete');

//Administration
$app->get('/administration', \SrcCore\controllers\CoreController::class . ':getAdministration');

//AutoComplete
$app->get('/autocomplete/users', \SrcCore\controllers\AutoCompleteController::class . ':getUsers');
$app->get('/autocomplete/users/visa', \SrcCore\controllers\AutoCompleteController::class . ':getUsersForVisa');
$app->get('/autocomplete/entities', \SrcCore\controllers\AutoCompleteController::class . ':getEntities');
$app->get('/autocomplete/statuses', \SrcCore\controllers\AutoCompleteController::class . ':getStatuses');
$app->get('/autocomplete/banAddresses', \SrcCore\controllers\AutoCompleteController::class . ':getBanAddresses');

//Baskets
$app->get('/baskets', \Basket\controllers\BasketController::class . ':get');
$app->post('/baskets', \Basket\controllers\BasketController::class . ':create');
$app->get('/baskets/{id}', \Basket\controllers\BasketController::class . ':getById');
$app->put('/baskets/{id}', \Basket\controllers\BasketController::class . ':update');
$app->delete('/baskets/{id}', \Basket\controllers\BasketController::class . ':delete');
$app->get('/baskets/{id}/groups', \Basket\controllers\BasketController::class . ':getGroups');
$app->post('/baskets/{id}/groups', \Basket\controllers\BasketController::class . ':createGroup');
$app->put('/baskets/{id}/groups/{groupId}', \Basket\controllers\BasketController::class . ':updateGroup');
$app->delete('/baskets/{id}/groups/{groupId}', \Basket\controllers\BasketController::class . ':deleteGroup');
$app->get('/baskets/{id}/groups/data', \Basket\controllers\BasketController::class . ':getDataForGroupById');
$app->get('/sortedBaskets', \Basket\controllers\BasketController::class . ':getSorted');
$app->put('/sortedBaskets/{id}', \Basket\controllers\BasketController::class . ':updateSort');

//BatchHistories
$app->get('/batchHistories', \History\controllers\BatchHistoryController::class . ':get');

//Contacts
$app->post('/contacts', \Contact\controllers\ContactController::class . ':create');
$app->get('/contacts/{contactId}/communication', \Contact\controllers\ContactController::class . ':getCommunicationByContactId');

//Docservers
$app->get('/docservers', \Docserver\controllers\DocserverController::class . ':get');
$app->get('/docservers/{id}', \Docserver\controllers\DocserverController::class . ':getById');

//DocserverTypes
$app->get('/docserverTypes', \Docserver\controllers\DocserverTypeController::class . ':get');
$app->get('/docserverTypes/{id}', \Docserver\controllers\DocserverTypeController::class . ':getById');

//doctypes
$app->get('/doctypes', \Doctype\controllers\FirstLevelController::class . ':getTree');
$app->post('/doctypes/firstLevel', \Doctype\controllers\FirstLevelController::class . ':create');
$app->get('/doctypes/firstLevel/{id}', \Doctype\controllers\FirstLevelController::class . ':getById');
$app->put('/doctypes/firstLevel/{id}', \Doctype\controllers\FirstLevelController::class . ':update');
$app->delete('/doctypes/firstLevel/{id}', \Doctype\controllers\FirstLevelController::class . ':delete');
$app->post('/doctypes/secondLevel', \Doctype\controllers\SecondLevelController::class . ':create');
$app->get('/doctypes/secondLevel/{id}', \Doctype\controllers\SecondLevelController::class . ':getById');
$app->put('/doctypes/secondLevel/{id}', \Doctype\controllers\SecondLevelController::class . ':update');
$app->delete('/doctypes/secondLevel/{id}', \Doctype\controllers\SecondLevelController::class . ':delete');
$app->post('/doctypes/types', \Doctype\controllers\DoctypeController::class . ':create');
$app->get('/doctypes/types/{id}', \Doctype\controllers\DoctypeController::class . ':getById');
$app->put('/doctypes/types/{id}', \Doctype\controllers\DoctypeController::class . ':update');
$app->delete('/doctypes/types/{id}', \Doctype\controllers\DoctypeController::class . ':delete');
$app->put('/doctypes/types/{id}/redirect', \Doctype\controllers\DoctypeController::class . ':deleteRedirect');
$app->get('/administration/doctypes/new', \Doctype\controllers\FirstLevelController::class . ':initDoctypes');

//Entities
$app->get('/entities', \Entity\controllers\EntityController::class . ':get');
$app->post('/entities', \Entity\controllers\EntityController::class . ':create');
$app->get('/entities/{id}', \Entity\controllers\EntityController::class . ':getById');
$app->put('/entities/{id}', \Entity\controllers\EntityController::class . ':update');
$app->delete('/entities/{id}', \Entity\controllers\EntityController::class . ':delete');
$app->get('/entities/{id}/details', \Entity\controllers\EntityController::class . ':getDetailledById');
$app->put('/entities/{id}/reassign/{newEntityId}', \Entity\controllers\EntityController::class . ':reassignEntity');
$app->put('/entities/{id}/status', \Entity\controllers\EntityController::class . ':updateStatus');
$app->get('/entityTypes', \Entity\controllers\EntityController::class . ':getTypes');

//Groups
$app->get('/groups', \Group\controllers\GroupController::class . ':get');
$app->post('/groups', \Group\controllers\GroupController::class . ':create');
$app->get('/groups/{id}', \Group\controllers\GroupController::class . ':getById');
$app->put('/groups/{id}', \Group\controllers\GroupController::class . ':update');
$app->delete('/groups/{id}', \Group\controllers\GroupController::class . ':delete');
$app->get('/groups/{id}/details', \Group\controllers\GroupController::class . ':getDetailledById');
$app->put('/groups/{id}/services/{serviceId}', \Group\controllers\GroupController::class . ':updateService');
$app->put('/groups/{id}/reassign/{newGroupId}', \Group\controllers\GroupController::class . ':reassignUsers');

//Histories
$app->get('/histories', \History\controllers\HistoryController::class . ':get');
$app->get('/histories/users/{userSerialId}', \History\controllers\HistoryController::class . ':getByUserId');

//Links
$app->get('/links/resId/{resId}', \Link\controllers\LinkController::class . ':getByResId');

//Listinstance
$app->get('/listinstance/{id}', \Entity\controllers\ListInstanceController::class . ':getById');

//ListTemplates
$app->get('/listTemplates', \Entity\controllers\ListTemplateController::class . ':get');
$app->post('/listTemplates', \Entity\controllers\ListTemplateController::class . ':create');
$app->get('/listTemplates/{id}', \Entity\controllers\ListTemplateController::class . ':getById');
$app->put('/listTemplates/{id}', \Entity\controllers\ListTemplateController::class . ':update');
$app->delete('/listTemplates/{id}', \Entity\controllers\ListTemplateController::class . ':delete');
$app->get('/listTemplates/entityDest/itemId/{itemId}', \Entity\controllers\ListTemplateController::class . ':getByUserWithEntityDest');
$app->put('/listTemplates/entityDest/itemId/{itemId}', \Entity\controllers\ListTemplateController::class . ':updateByUserWithEntityDest');
$app->get('/listTemplates/types/{typeId}/roles', \Entity\controllers\ListTemplateController::class . ':getTypeRoles');
$app->put('/listTemplates/types/{typeId}/roles', \Entity\controllers\ListTemplateController::class . ':updateTypeRoles');

//Parameters
$app->get('/parameters', \Parameter\controllers\ParameterController::class . ':get');
$app->post('/parameters', \Parameter\controllers\ParameterController::class . ':create');
$app->get('/parameters/{id}', \Parameter\controllers\ParameterController::class . ':getById');
$app->put('/parameters/{id}', \Parameter\controllers\ParameterController::class . ':update');
$app->delete('/parameters/{id}', \Parameter\controllers\ParameterController::class . ':delete');

//Priorities
$app->get('/priorities', \Priority\controllers\PriorityController::class . ':get');
$app->post('/priorities', \Priority\controllers\PriorityController::class . ':create');
$app->get('/priorities/{id}', \Priority\controllers\PriorityController::class . ':getById');
$app->put('/priorities/{id}', \Priority\controllers\PriorityController::class . ':update');
$app->delete('/priorities/{id}', \Priority\controllers\PriorityController::class . ':delete');
$app->get('/sortedPriorities', \Priority\controllers\PriorityController::class . ':getSorted');
$app->put('/sortedPriorities', \Priority\controllers\PriorityController::class . ':updateSort');

//Reports
$app->get('/reports/groups', \Report\controllers\ReportController::class . ':getGroups');
$app->get('/reports/groups/{groupId}', \Report\controllers\ReportController::class . ':getByGroupId');
$app->put('/reports/groups/{groupId}', \Report\controllers\ReportController::class . ':updateForGroupId');

//Ressources
$app->post('/resources', \Resource\controllers\ResController::class . ':create');
$app->post('/res', \Resource\controllers\ResController::class . ':createRes');
$app->post('/resExt', \Resource\controllers\ResController::class . ':createExt');
$app->put('/res/resource/status', \Resource\controllers\ResController::class . ':updateStatus');
$app->post('/res/list', \Resource\controllers\ResController::class . ':getList');
$app->get('/res/{resId}/lock', \Resource\controllers\ResController::class . ':isLock');
$app->get('/res/{resId}/notes/count', \Resource\controllers\ResController::class . ':getNotesCountForCurrentUserById');
$app->put('/res/externalInfos', \Resource\controllers\ResController::class . ':updateExternalInfos');

//statuses
$app->get('/statuses', \Status\controllers\StatusController::class . ':get');
$app->post('/statuses', \Status\controllers\StatusController::class . ':create');
$app->get('/statuses/{identifier}', \Status\controllers\StatusController::class . ':getByIdentifier');
$app->get('/status/{id}', \Status\controllers\StatusController::class . ':getById');
$app->put('/statuses/{identifier}', \Status\controllers\StatusController::class . ':update');
$app->delete('/statuses/{identifier}', \Status\controllers\StatusController::class . ':delete');
$app->get('/administration/statuses/new', \Status\controllers\StatusController::class . ':getNewInformations');

//Templates
$app->post('/templates/{id}/duplicate', \Template\controllers\TemplateController::class . ':duplicate');

//Users
$app->get('/users', \User\controllers\UserController::class . ':get');
$app->post('/users', \User\controllers\UserController::class . ':create');
$app->get('/users/{id}/details', \User\controllers\UserController::class . ':getDetailledById');
$app->put('/users/{id}', \User\controllers\UserController::class . ':update');
$app->put('/users/{id}/password', \User\controllers\UserController::class . ':resetPassword');
$app->put('/users/{id}/modifyPassword', \User\controllers\UserController::class . ':updatePassword');
$app->get('/users/{userId}/status', \User\controllers\UserController::class . ':getStatusByUserId');
$app->put('/users/{id}/status', \User\controllers\UserController::class . ':updateStatus');
$app->delete('/users/{id}', \User\controllers\UserController::class . ':delete');
$app->post('/users/{id}/groups', \User\controllers\UserController::class . ':addGroup');
$app->put('/users/{id}/groups/{groupId}', \User\controllers\UserController::class . ':updateGroup');
$app->delete('/users/{id}/groups/{groupId}', \User\controllers\UserController::class . ':deleteGroup');
$app->post('/users/{id}/entities', \User\controllers\UserController::class . ':addEntity');
$app->put('/users/{id}/entities/{entityId}', \User\controllers\UserController::class . ':updateEntity');
$app->put('/users/{id}/entities/{entityId}/primaryEntity', \User\controllers\UserController::class . ':updatePrimaryEntity');
$app->get('/users/{id}/entities/{entityId}', \User\controllers\UserController::class . ':isEntityDeletable');
$app->delete('/users/{id}/entities/{entityId}', \User\controllers\UserController::class . ':deleteEntity');
$app->post('/users/{id}/signatures', \User\controllers\UserController::class . ':addSignature');
$app->put('/users/{id}/signatures/{signatureId}', \User\controllers\UserController::class . ':updateSignature');
$app->delete('/users/{id}/signatures/{signatureId}', \User\controllers\UserController::class . ':deleteSignature');
$app->post('/users/{id}/redirectedBaskets', \User\controllers\UserController::class . ':setRedirectedBaskets');
$app->delete('/users/{id}/redirectedBaskets/{basketId}', \User\controllers\UserController::class . ':deleteRedirectedBaskets');
$app->put('/users/{id}/baskets', \User\controllers\UserController::class . ':updateBasketsDisplay');

//Visa
$app->get('/{basketId}/signatureBook/resList', \Visa\Controllers\VisaController::class . ':getResList');
$app->get('/{basketId}/signatureBook/resList/details', \Visa\Controllers\VisaController::class . ':getDetailledResList');
$app->get('/groups/{groupId}/baskets/{basketId}/signatureBook/{resId}', \Visa\Controllers\VisaController::class . ':getSignatureBook');
$app->get('/signatureBook/{resId}/attachments', \Visa\Controllers\VisaController::class . ':getAttachmentsById');
$app->get('/signatureBook/{resId}/incomingMailAttachments', \Visa\Controllers\VisaController::class . ':getIncomingMailAndAttachmentsById');
$app->put('/{collId}/{resId}/unsign', \Visa\Controllers\VisaController::class . ':unsignFile');
$app->put('/attachments/{id}/inSignatureBook', \Attachment\controllers\AttachmentController::class . ':setInSignatureBook');

//CurrentUser
$app->get('/currentUser/profile', \User\controllers\UserController::class . ':getProfile');
$app->put('/currentUser/profile', \User\controllers\UserController::class . ':updateProfile');
$app->put('/currentUser/password', \User\controllers\UserController::class . ':updateCurrentUserPassword');
$app->post('/currentUser/emailSignature', \User\controllers\UserController::class . ':createCurrentUserEmailSignature');
$app->put('/currentUser/emailSignature/{id}', \User\controllers\UserController::class . ':updateCurrentUserEmailSignature');
$app->delete('/currentUser/emailSignature/{id}', \User\controllers\UserController::class . ':deleteCurrentUserEmailSignature');
$app->put('/currentUser/groups/{groupId}/baskets/{basketId}', \User\controllers\UserController::class . ':updateBasketPreference');

//Notifications
$app->get('/notifications', \Notification\controllers\NotificationController::class . ':get');
$app->post('/notifications', \Notification\controllers\NotificationController::class . ':create');
$app->get('/notifications/schedule', \Notification\controllers\NotificationScheduleController::class . ':get');
$app->post('/notifications/schedule', \Notification\controllers\NotificationScheduleController::class . ':create');
$app->put('/notifications/{id}', \Notification\controllers\NotificationController::class . ':update');
$app->delete('/notifications/{id}', \Notification\controllers\NotificationController::class . ':delete');
$app->get('/administration/notifications/new', \Notification\controllers\NotificationController::class . ':initNotification');
$app->get('/notifications/{id}', \Notification\controllers\NotificationController::class . ':getBySid');
$app->post('/scriptNotification', \Notification\controllers\NotificationScheduleController::class . ':createScriptNotification');

$app->post('/saveNumericPackage', \Sendmail\Controllers\ReceiveMessageExchangeController::class . ':saveMessageExchange');
$app->post('/saveMessageExchangeReturn', \Sendmail\Controllers\ReceiveMessageExchangeController::class . ':saveMessageExchangeReturn');
$app->post('/saveMessageExchangeReview', \Sendmail\Controllers\MessageExchangeReviewController::class . ':saveMessageExchangeReview');

$app->run();
