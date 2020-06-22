<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Installer Controller
 *
 * @author dev@maarch.org
 */

namespace SrcCore\controllers;

use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;

class InstallerController
{
    public function getPrerequisites(Request $request, Response $response)
    {
        $phpVersion = phpversion();
        $phpVersionValid = (version_compare(PHP_VERSION, '7.2') >= 0);

        exec('whereis unoconv', $output, $return);
        $output = explode(':', $output[0]);
        $unoconv = !empty($output[1]);

        $pdoPgsql = @extension_loaded('pdo_pgsql');
        $pgsql = @extension_loaded('pgsql');
        $mbstring = @extension_loaded('mbstring');
        $fileinfo = @extension_loaded('fileinfo');
        $gd = @extension_loaded('gd');
        $imagick = @extension_loaded('imagick');
        $imap = @extension_loaded('imap');
        $xsl = @extension_loaded('xsl');
        $gettext = @extension_loaded('gettext');
        $xmlrpc = @extension_loaded('xmlrpc');
        $curl = @extension_loaded('curl');
        $zip = @extension_loaded('zip');

        $writable = is_writable('.') && is_readable('.');

        $displayErrors = (ini_get('display_errors') == '1');
        $errorReporting = (ini_get('error_reporting') >= 22519);

        exec('whereis netcat', $outputNetcat, $returnNetcat);
        $outputNetcat = explode(':', $outputNetcat[0]);
        exec('whereis nmap', $outputNmap, $returnNmap);
        $outputNmap = explode(':', $outputNmap[0]);
        $netcatOrNmap = !empty($outputNetcat[1]) || !empty($outputNmap[1]);

        $prerequisites = [
            'phpVersion'        => $phpVersion,
            'phpVersionValid'   => $phpVersionValid,
            'unoconv'           => $unoconv,
            'pdoPgsql'          => $pdoPgsql,
            'pgsql'             => $pgsql,
            'mbstring'          => $mbstring,
            'fileinfo'          => $fileinfo,
            'gd'                => $gd,
            'imagick'           => $imagick,
            'imap'              => $imap,
            'xsl'               => $xsl,
            'gettext'           => $gettext,
            'xmlrpc'            => $xmlrpc,
            'curl'              => $curl,
            'zip'               => $zip,
            'writable'          => $writable,
            'displayErrors'     => $displayErrors,
            'errorReporting'    => $errorReporting,
            'netcatOrNmap'      => $netcatOrNmap
        ];

        return $response->withJson(['prerequisites' => $prerequisites]);
    }

    public function checkDatabaseConnection(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        if (!Validator::stringType()->notEmpty()->validate($queryParams['server'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body server is empty or not a string']);
        } elseif (!Validator::intVal()->notEmpty()->validate($queryParams['port'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body port is empty or not an integer']);
        } elseif (!Validator::stringType()->notEmpty()->validate($queryParams['user'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body user is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($queryParams['password'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body password is empty or not a string']);
        }

        $connected = false;
        $name = 'postgres';
        if (!empty($queryParams['name'])) {
            $name = $queryParams['name'];
            $connection = "host={$queryParams['server']} port={$queryParams['port']} user={$queryParams['user']} password={$queryParams['password']} dbname={$queryParams['name']}";
            $connected = @pg_connect($connection);
        }
        if (!$connected) {
            $name = 'postgres';
            $connection = "host={$queryParams['server']} port={$queryParams['port']} user={$queryParams['user']} password={$queryParams['password']} dbname=postgres";
            if (!@pg_connect($connection)) {
                return $response->withStatus(400)->withJson(['errors' => 'Database connection failed']);
            }
        }

        $request = "select datname from pg_database where datname = '{$name}'";
        $result = @pg_query($request);
        if (!$result) {
            return $response->withStatus(400)->withJson(['errors' => 'Database request failed']);
        }

        if (!empty($queryParams['name']) && !$connected) {
            return $response->withJson(['success' => 'First connection failed']);
        }

        return $response->withStatus(204);
    }

    public function createCustom(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['customName'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body customName is empty or not a string']);
        }

        if (is_dir("custom/{$body['customName']}")) {
            return $response->withStatus(400)->withJson(['errors' => 'Custom with this name already exists']);
        } elseif (!@mkdir("custom/{$body['customName']}/apps/maarch_entreprise/xml", 0755, true)) {
            return $response->withStatus(400)->withJson(['errors' => 'Custom folder creation failed']);
        }

        if (!is_file("custom/custom.json")) {
            $fp = fopen('custom/custom.json', 'w');
            fwrite($fp, json_encode([], JSON_PRETTY_PRINT));
            fclose($fp);
        }

        $customFile = CoreConfigModel::getJsonLoaded(['path' => 'custom/custom.json']);
        $customFile[] = [
            'id'    => $body['customName'],
            'uri'   => null,
            'path'  => $body['customName']
        ];
        $fp = fopen('custom/custom.json', 'w');
        fwrite($fp, json_encode($customFile, JSON_PRETTY_PRINT));
        fclose($fp);

        $jsonFile = [
            'config'    => [
                'lang'              => $body['lang'] ?? 'fr',
                'applicationName'   => $body['customName'],
                'cookieTime'        => 10080,
                'timezone'          => 'Europe/Paris'
            ],
            'database'  => []
        ];
        $fp = fopen("custom/{$body['customName']}/apps/maarch_entreprise/xml/config.json", 'w');
        fwrite($fp, json_encode($jsonFile, JSON_PRETTY_PRINT));
        fclose($fp);

        $cmd = 'ln -s ' . realpath('.') . "/ {$body['customName']}";
        exec($cmd);

        return $response->withStatus(204);
    }

    public function createDatabase(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['server'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body server is empty or not a string']);
        } elseif (!Validator::intVal()->notEmpty()->validate($body['port'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body port is empty or not an integer']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['user'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body user is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['password'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body password is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['name'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body name is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['customName'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body customName is empty or not a string']);
        }

        if (empty($body['alreadyCreated'])) {
            $connection = "host={$body['server']} port={$body['port']} user={$body['user']} password={$body['password']} dbname=postgres";
            if (!@pg_connect($connection)) {
                return $response->withStatus(400)->withJson(['errors' => 'Database connection failed']);
            }

            $request = "CREATE DATABASE \"{$body['name']}\" WITH TEMPLATE template0 ENCODING = 'UTF8'";
            $result = pg_query($request);
            if (!$result) {
                return $response->withStatus(400)->withJson(['errors' => 'Database creation failed']);
            }

            @pg_query("ALTER DATABASE '{$body['name']}' SET DateStyle =iso, dmy");
            pg_close();
        }

        $options = [
            \PDO::ATTR_PERSISTENT   => true,
            \PDO::ATTR_ERRMODE      => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_CASE         => \PDO::CASE_NATURAL
        ];

        $dsn = "pgsql:host={$body['server']};port={$body['port']};dbname={$body['name']}";
        $db = new \PDO($dsn, $body['user'], $body['password'], $options);

        $fileContent = @file_get_contents('sql/structure.sql');
        if (!$fileContent) {
            return $response->withStatus(400)->withJson(['errors' => 'Cannot read structure.sql']);
        }
        $result = $db->exec($fileContent);
        if ($result === false) {
            return $response->withStatus(400)->withJson(['errors' => 'Request failed : run structure.sql']);
        }

        if (!empty($body['data'])) {
            $fileContent = @file_get_contents("sql/{$body['data']}.sql");
            if (!$fileContent) {
                return $response->withStatus(400)->withJson(['errors' => "Cannot read {$body['data']}.sql"]);
            }
            $result = $db->exec($fileContent);
            if ($result ===  false) {
                return $response->withStatus(400)->withJson(['errors' => "Request failed : run {$body['data']}.sql"]);
            }
        }

        $configFile = CoreConfigModel::getJsonLoaded(['path' => "custom/{$body['customName']}/apps/maarch_entreprise/xml/config.json"]);
        $configFile['database'] = [
            [
                "server"    => $body['server'],
                "port"      => $body['port'],
                "type"      => 'POSTGRESQL',
                "name"      => $body['name'],
                "user"      => $body['user'],
                "password"  => $body['password']
            ]
        ];

        $fp = fopen("custom/{$body['customName']}/apps/maarch_entreprise/xml/config.json", 'w');
        fwrite($fp, json_encode($configFile, JSON_PRETTY_PRINT));
        fclose($fp);

        return $response->withStatus(204);
    }
}
