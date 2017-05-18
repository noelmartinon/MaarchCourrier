<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace CMIS\Controllers;

use CMIS\Utils\Utils;

class FrontController
{
    public static function initSession()
    {
        if (empty($_SESSION['user'])) {
            $portal = new \portal();
            $portal->unset_session();
            $portal->build_config();
            $coreTools = new \core_tools();
            $_SESSION['custom_override_id'] = $coreTools->get_custom_id();
            if (isset($_SESSION['custom_override_id'])
                && !empty($_SESSION['custom_override_id'])
                && isset($_SESSION['config']['corepath'])
                && !empty($_SESSION['config']['corepath'])
            ) {
                $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                    . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR;
                set_include_path(
                    $path . PATH_SEPARATOR . $_SESSION['config']['corepath']
                    . PATH_SEPARATOR . get_include_path()
                );
            } else if (isset($_SESSION['config']['corepath'])
                && !empty($_SESSION['config']['corepath'])
            ) {
                set_include_path(
                    $_SESSION['config']['corepath'] . PATH_SEPARATOR . get_include_path()
                );
            }
            // Load configuration from xml into session
            \Core_CoreConfig_Service::buildCoreConfig('core' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml');
            $_SESSION['config']['app_id'] = $_SESSION['businessapps'][0]['appid'];
            require_once 'apps/' . $_SESSION['businessapps'][0]['appid'] . '/class/class_business_app_tools.php';

            \Core_CoreConfig_Service::buildBusinessAppConfig();

            // Load Modules configuration from xml into session
            \Core_CoreConfig_Service::loadModulesConfig($_SESSION['modules']);
            \Core_CoreConfig_Service::loadAppServices();
            \Core_CoreConfig_Service::loadModulesServices($_SESSION['modules']);
        }
    }

    public static function login()
    {
        if (empty($_SESSION['user'])) {
            $loginObj = new \login();
            $loginMethods = $loginObj->build_login_method();
            $oSessionService = new \Core_Session_Service();
            $loginObj->execute_login_script($loginMethods, true);
        }

        if ($_SESSION['error']) {
            http_response_code(401);
            echo $_SESSION['error'];
            exit();
        }
    }

    public static function repository($output)
    {
        $cmis = new CMIS(Utils::outputFactory($output));
        if ($output == 'browser' && !empty($_GET['cmisselector']) && $_GET['cmisselector'] == 'typeDefinition' && !empty($_GET['typeId'])) {
            $cmis->renderType($_GET['typeId']);
        } else {
            $cmis->repository()->render();
        }
    }

    public static function descendants($output)
    {
        $cmis = new CMIS(Utils::outputFactory($output));
        $id = (!empty($_GET['id'])) ? $_GET['id'] : Utils::createObjectId('/');
        $cmis->descendants($id)->render();
    }

    public static function id($output)
    {
        $cmis = new CMIS(Utils::outputFactory($output));
        $succinct = (isset($_GET['succinct']) && $_GET['succinct'] == 'true');
        $selector = (!empty($_GET['cmisselector'])) ? $_GET['cmisselector'] : null;
        $id = (!empty($_GET['objectId'])) ? $_GET['objectId'] : Utils::createObjectId('/');
        $cmis->id($id, $succinct, $selector)->render();
    }

    public static function query($output)
    {
        $cmis = new CMIS(Utils::outputFactory($output));

        $dom = new \DOMDocument();
        $queryParameters = [];

        if ($dom->loadXML(file_get_contents('php://input'))) {
            $properties = $dom->getElementsByTagName('query');
            foreach($properties[0]->childNodes as $property){
                $queryParameters[str_ireplace('cmis:','',$property->nodeName)] = $property->nodeValue;
            }
            $cmis->query($queryParameters);
        }
    }

    public static function create($output)
    {
        $cmis = new CMIS(Utils::outputFactory($output));

        $queryParameters = [];
        $dom = new \DOMDocument();

        // Stream handler
        if ($dom->loadXML(file_get_contents('php://input'))) {
            $properties = $dom->getElementsByTagName('properties');
            foreach($properties[0]->childNodes as $property){
                $queryParameters[str_ireplace('cmis:','',$property->getAttribute('propertyDefinitionId'))] = $property->nodeValue;
            }

            $type = $dom->getElementsByTagName('propertyId')[0]->nodeValue;

            switch ($type) {
                case 'cmis:document':
                    $cmis->createDocument(Utils::readObjectId($_GET['id']), $dom->getElementsByTagName('propertyString')[0]->nodeValue, $dom->getElementsByTagName('base64')[0]->nodeValue);

                    break;
                case 'cmis:folder':
                    $cmis->createFolder(Utils::readObjectId($_GET['id']), $dom->getElementsByTagName('propertyString')[0]->nodeValue);
                    break;
            }

        } else {
           /* switch ($_REQUEST['cmisaction']) {
                case 'createDocument':
                    $cmis->output->createDocument($_REQUEST['objectId'], $_FILES['content']);

                    break;
                case 'createFolder':
                    $cmis->output->createFolder(null, null);
                    break;
            }*/
        }

    }

    public static function type($output)
    {
        $cmis = new CMIS(Utils::outputFactory($output));
        $cmis->renderType($_GET['id']);
    }

    public static function fakeAuth()
    {
        $_SERVER['PHP_AUTH_USER'] = 'ppetit';
        $_SERVER['PHP_AUTH_PW'] = 'maarch';
    }
}