<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

use CMIS\Controllers\CMIS;
use CMIS\Utils\Router;
use CMIS\Utils\Utils;

session_start();
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
    } else if (isset($_SESSION['config']['corepath'])
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
    //TODO : return http bad authent error
    echo $_SESSION['error'];exit();
}


$router = new Router();
$router->setBasePath('/cmis');


Utils::dump(\CMIS\Models\CMISObject::getAllObjects());
die();

$router->map('GET', '/', function () {
    header('Location:atom');
});

/**
 * @param $output string atom or browser
 */
$router->map('GET', '/[a:output]/?', function ($output) {
    $cmis = new CMIS(Utils::outputFactory($output));
    if ($output == 'browser' && !empty($_GET['cmisselector']) && $_GET['cmisselector'] == 'typeDefinition' && !empty($_GET['typeId'])) {
        $cmis->renderType($_GET['typeId']);
    } else {
        $cmis->repository()->render();
    }
}, 'catalog');

$router->map('GET', '/atom/types/?', function () {
    Utils::renderXML('assets/atom/types.xml');
}, 'types');

$router->map('GET', '/[a:output]/descendants/?', function ($output) {
    $cmis = new CMIS(Utils::outputFactory($output));
    $id = (!empty($_GET['id'])) ? $_GET['id'] : Utils::createObjectId('/');
    $cmis->descendants($id)->render();
}, 'descendants');

$router->map('GET', '/[a:output]/id/?', function ($output) {
    $cmis = new CMIS(Utils::outputFactory($output));
    $succinct = (isset($_GET['succinct']) && $_GET['succinct'] == 'true');
    $selector = (!empty($_GET['cmisselector'])) ? $_GET['cmisselector'] : null;
    $id = (!empty($_GET['objectId'])) ? $_GET['objectId'] : Utils::createObjectId('/');
    $cmis->id($id, $succinct, $selector)->render();
}, 'id');

$router->map('POST', '/[a:output]/*', function ($output) {
    $cmis = new CMIS(Utils::outputFactory($output));
    switch ($_REQUEST['cmisaction']) {
        case 'createDocument':
            $cmis->createDocument($_REQUEST['objectId'], $_FILES['content']);
            break;
        case 'createFolder':
            $property = array_combine($_REQUEST['propertyId'], $_REQUEST['propertyValue']);
            $cmis->createFolder($_REQUEST['objectId'], $property['cmis:name']);
            break;
    }
}, 'post_id');

$router->map('GET', '/[a:output]/type/?', function ($output) {
    $cmis = new CMIS(Utils::outputFactory($output));
    $cmis->renderType($_GET['id']);
}, 'type');


$match = $router->match();

if ($match && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}
