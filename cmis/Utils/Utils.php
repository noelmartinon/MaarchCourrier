<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace CMIS\Utils;

use CMIS\Models\AtomPubOutput;
use CMIS\Models\BrowserOutput;

class Utils
{
    public static function dump($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }

    // Access logs (for CMIS Workbench)
    public static function log()
    {
        file_put_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs/access.log', $_SERVER['REQUEST_METHOD'] . ' ' . http_response_code() . ' - ' . $_SERVER['REQUEST_URI'] . ' - [' . date('d/m/Y H:i:s') . ']' . PHP_EOL, FILE_APPEND);
        if (!empty($_GET)) file_put_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs/access.log', 'GET : ' . print_r($_GET, true) . PHP_EOL, FILE_APPEND);
        if (!empty($_POST)) file_put_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs/access.log', 'POST : ' . print_r($_POST, true) . PHP_EOL, FILE_APPEND);
        if (!empty($_FILES)) file_put_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs/access.log', 'FILES : ' . print_r($_FILES, true) . PHP_EOL, FILE_APPEND);
        file_put_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs/access.log', 'RAW : ' . print_r(file_get_contents('php://input'), true) . PHP_EOL, FILE_APPEND);
        file_put_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs/access.log', ' --------------------- ' . PHP_EOL, FILE_APPEND);
    }

    public static function webroot($server, $raw = false)
    {
        return ($raw) ?
            "http" . (($server['SERVER_PORT'] == 443) ? "s://" : "://") . $server['HTTP_HOST'] . $server['REQUEST_URI'] :
            "http" . (($server['SERVER_PORT'] == 443) ? "s://" : "://") . $server['HTTP_HOST'] . str_replace('?' . $server['QUERY_STRING'], '', $server['REQUEST_URI']);
    }

    public static function renderXML($pathToXML)
    {
        header("Content-type: text/xml");
        echo file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . $pathToXML);
    }

    public static function renderJSON($pathToJSON)
    {
        header('Content-Type: application/json');
        echo file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . $pathToJSON);
    }

    public static function outputFactory($output)
    {
        switch ($output) {
            case 'browser':
                return new BrowserOutput();
                break;
            case 'atom':
                return new AtomPubOutput();
                break;
            default:
                return new AtomPubOutput();
        }
    }

    public static function createObjectId($string, $type = null)
    {
        switch (strtolower($type)) {
            case 'folder':
                return bin2hex('folder_' . $string);
                break;
            case 'document' :
                return bin2hex('document_' . $string);
                break;
            default:
                return bin2hex('workspace:' . $string);
        }
    }

    public static function readObjectId($objectId, $type = null)
    {
        switch (strtolower($type)) {
            case 'folder':
                return str_replace('folder_', '', hex2bin($objectId));
                break;
            case 'document' :
                return str_replace('document_', '', hex2bin($objectId));
                break;
            default:
                return str_replace(['workspace:', 'folder_', 'document_'], '', hex2bin($objectId));
        }

    }

    public static function getObjectType($objectId)
    {
        if(preg_match('/^document/', hex2bin($objectId))){
            return 'document';
        } else if(preg_match('/^folder/', hex2bin($objectId))){
            return 'folder';
        } else {
            return 'workspace';
        }

    }

    public static function echo_memory_usage()
    {
        $mem_usage = memory_get_usage(true);

        if ($mem_usage < 1024)
            echo $mem_usage . " bytes";
        elseif ($mem_usage < 1048576)
            echo round($mem_usage / 1024, 2) . " kilobytes";
        else
            echo round($mem_usage / 1048576, 2) . " megabytes";

        echo "<br/>";
    }

    public static function echo_memory_peak_usage()
    {
        $mem_usage = memory_get_peak_usage(true);

        if ($mem_usage < 1024)
            echo $mem_usage . " bytes";
        elseif ($mem_usage < 1048576)
            echo round($mem_usage / 1024, 2) . " kilobytes";
        else
            echo round($mem_usage / 1048576, 2) . " megabytes";

        echo "<br/>";
    }
}