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
        $custom = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'custom.xml';
        $general = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . 'maarch_entreprise' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml';

        $dom = new \DOMDocument();
        if (file_exists($custom)) {
            $dom = new \DOMDocument();
            $dom->loadXML(file_get_contents($custom));

            $customName = '';


            $scriptPath = explode('/', $_SERVER['SCRIPT_NAME']);

            $customs = $dom->getElementsByTagName('custom');
            foreach ($customs as $custom) {
                if ($custom->getElementsByTagName('path')->length != 0) {
                    if ($scriptPath[(sizeof($scriptPath) - 3)] == $dom->getElementsByTagName('path')->item(0)->nodeValue) {
                        $customName = $custom->getElementsByTagName('custom_id')->item(0)->nodeValue;
                        break;
                    }
                } else {
                    $customName = $custom->getElementsByTagName('custom_id')->item(0)->nodeValue;
                }
            }

            $dom->loadXML(file_get_contents(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . $customName . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . 'maarch_entreprise' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml'));

        } else {

            $dom->loadXML(file_get_contents($general));
        }

        $_SESSION['cmis_databaseserver'] = $dom->getElementsByTagName('databaseserver')->item(0)->nodeValue;
        $_SESSION['cmis_databaseserverport'] = $dom->getElementsByTagName('databaseserverport')->item(0)->nodeValue;
        $_SESSION['cmis_databasetype'] = $dom->getElementsByTagName('databasetype')->item(0)->nodeValue;
        $_SESSION['cmis_databasename'] = $dom->getElementsByTagName('databasename')->item(0)->nodeValue;
        $_SESSION['cmis_databaseuser'] = $dom->getElementsByTagName('databaseuser')->item(0)->nodeValue;
        $_SESSION['cmis_databasepassword'] = $dom->getElementsByTagName('databasepassword')->item(0)->nodeValue;
        $_SESSION['cmis_databasesearchlimit'] = $dom->getElementsByTagName('databasesearchlimit')->item(0)->nodeValue;

    }

    public static function login()
    {
        if (!empty($_SESSION['cmis_username'])) {
            $valid = true;
        } else if (empty($_SESSION['cmis_username'])) {
            $valid = Utils::userExists($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
            $_SESSION['cmis_username'] = $_SERVER['PHP_AUTH_USER'];
        } else {
            $valid = false;
        }

        if (!$valid) {
            header('WWW-Authenticate: Basic realm="My Realm"');
            header('HTTP/1.0 401 Unauthorized');
            echo "Access denied";
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

    public static function children($output)
    {
        $cmis = new CMIS(Utils::outputFactory($output));
        $id = (!empty($_GET['id'])) ? $_GET['id'] : Utils::createObjectId('/');
        $cmis->children($id)->render();
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
            foreach ($properties[0]->childNodes as $property) {
                $queryParameters[str_ireplace('cmis:', '', $property->nodeName)] = $property->nodeValue;
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
            foreach ($properties[0]->childNodes as $property) {
                $queryParameters[str_ireplace('cmis:', '', $property->getAttribute('propertyDefinitionId'))] = $property->nodeValue;
            }

            switch ($queryParameters['objectTypeId']) {
                case 'cmis:document':
                    $cmis->createDocument(Utils::readObjectId($_GET['id']), $queryParameters, $dom->getElementsByTagName('base64')[0]->nodeValue);

                    break;
                case 'cmis:folder':
                    $cmis->createFolder(Utils::readObjectId($_GET['id']), $queryParameters);
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


    public static function path($output)
    {
        $cmis = new CMIS(Utils::outputFactory($output));
        $path = (!empty($_GET['path'])) ? $_GET['path'] : "/";

        $cmis->path($_GET['objectId'], $path)->render();
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