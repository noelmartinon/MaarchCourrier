<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Only Office Controller
 *
 * @author dev@maarch.org
 */

namespace ContentManagement\controllers;

use Attachment\models\AttachmentModel;
use Docserver\models\DocserverModel;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\UrlController;
use SrcCore\models\CoreConfigModel;
use Template\models\TemplateModel;

class OnlyOfficeController
{
    public static function getConfiguration(Request $request, Response $response)
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/documentEditorsConfig.xml']);

        if (empty($loadedXml) || empty($loadedXml->onlyoffice->enabled) || $loadedXml->onlyoffice->enabled == 'false' || empty($loadedXml->onlyoffice->server_uri)) {
            return $response->withJson(['enabled' => false]);
        }

        $coreUrl = str_replace('rest/', '', UrlController::getCoreUrl());

        $configurations = [
            'enabled'       => true,
            'serverUri'     => (string)$loadedXml->onlyoffice->server_uri,
            'serverPort'    => (int)$loadedXml->onlyoffice->server_port,
            'serverSsl'     => filter_var((string)$loadedXml->onlyoffice->server_ssl, FILTER_VALIDATE_BOOLEAN),
            'coreUrl'       => $coreUrl
        ];

        return $response->withJson($configurations);
    }

    public static function saveMergedFile(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['onlyOfficeKey'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params onlyOfficeKey is empty']);
        } elseif (!preg_match('/[A-Za-z0-9]/i', $body['onlyOfficeKey'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params onlyOfficeKey is forbidden']);
        }

        if ($body['objectType'] == 'templateCreation') {
            $customId = CoreConfigModel::getCustomId();
            if (!empty($customId) && is_dir("custom/{$customId}/modules/templates/templates/styles/")) {
                $stylesPath = "custom/{$customId}/modules/templates/templates/styles/";
            } else {
                $stylesPath = 'modules/templates/templates/styles/';
            }
            if (strpos($body['objectId'], $stylesPath) !== 0 || substr_count($body['objectId'], '.') != 1) {
                return $response->withStatus(400)->withJson(['errors' => 'Template path is not valid']);
            }

            $path = $body['objectId'];
            $fileContent = file_get_contents($path);
        } elseif ($body['objectType'] == 'templateModification') {
            $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);
            $template = TemplateModel::getById(['id' => $body['objectId'], 'select' => ['template_path', 'template_file_name']]);
            if (empty($template)) {
                return $response->withStatus(400)->withJson(['errors' => 'Template does not exist']);
            }

            $path = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $template['template_path']) . $template['template_file_name'];
            $fileContent = file_get_contents($path);
        } elseif ($body['objectType'] == 'resourceCreation' || $body['objectType'] == 'attachmentCreation') {
            $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);
            $template = TemplateModel::getById(['id' => $body['objectId'], 'select' => ['template_path', 'template_file_name']]);
            if (empty($template)) {
                return $response->withStatus(400)->withJson(['errors' => 'Template does not exist']);
            }

            $path = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $template['template_path']) . $template['template_file_name'];

            $dataToMerge = ['userId' => $GLOBALS['id']];
            if (!empty($body['data']) && is_array($body['data'])) {
                $dataToMerge = array_merge($dataToMerge, $body['data']);
            }
            $mergedDocument = MergeController::mergeDocument([
                'path' => $path,
                'data' => $dataToMerge
            ]);
            $fileContent = base64_decode($mergedDocument['encodedDocument']);
        } elseif ($body['objectType'] == 'resourceModification') {
            if (!ResController::hasRightByResId(['resId' => [$body['objectId']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(400)->withJson(['errors' => 'Resource out of perimeter']);
            }
            $resource = ResModel::getById(['resId' => $body['objectId'], 'select' => ['docserver_id', 'path', 'filename']]);
            if (empty($resource['filename'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Resource has no file']);
            }

            $docserver  = DocserverModel::getByDocserverId(['docserverId' => $resource['docserver_id'], 'select' => ['path_template']]);

            $path = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $resource['path']) . $resource['filename'];
            $fileContent = file_get_contents($path);
        } elseif ($body['objectType'] == 'attachmentModification') {
            $attachment = AttachmentModel::getById(['id' => $body['objectId'], 'select' => ['docserver_id', 'path', 'filename', 'res_id_master']]);
            if (empty($attachment)) {
                return $response->withStatus(400)->withJson(['errors' => 'Attachment does not exist']);
            }
            if (!ResController::hasRightByResId(['resId' => [$attachment['res_id_master']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(400)->withJson(['errors' => 'Attachment out of perimeter']);
            }

            $docserver  = DocserverModel::getByDocserverId(['docserverId' => $attachment['docserver_id'], 'select' => ['path_template']]);

            $path = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $attachment['path']) . $attachment['filename'];
            $fileContent = file_get_contents($path);
        } else {
            return $response->withStatus(400)->withJson(['errors' => 'Query param objectType does not exist']);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $tmpPath = CoreConfigModel::getTmpPath();
        $filename = "onlyOffice_{$GLOBALS['id']}_{$body['onlyOfficeKey']}.{$extension}";

        $put = file_put_contents($tmpPath . $filename, $fileContent);
        if ($put === false) {
            return $response->withStatus(400)->withJson(['errors' => 'File put contents failed']);
        }

        $halfFilename = substr($filename, 11);
        return $response->withJson(['filename' => $halfFilename]);
    }

    public static function getMergedFile(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        if (!Validator::stringType()->notEmpty()->validate($queryParams['filename'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params filename is empty']);
        } elseif (substr_count($queryParams['filename'], '\\') > 0 || substr_count($queryParams['filename'], '.') != 1) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params filename forbidden']);
        }

        $tmpPath = CoreConfigModel::getTmpPath();
        $filename = "onlyOffice_{$queryParams['filename']}";

        $fileContent = file_get_contents($tmpPath . $filename);
        if ($fileContent == false) {
            return $response->withStatus(400)->withJson(['errors' => 'No content found']);
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $extension = pathinfo($tmpPath . $filename, PATHINFO_EXTENSION);
        unlink($tmpPath . $filename);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "attachment; filename=maarch.{$extension}");

        return $response->withHeader('Content-Type', $mimeType);
    }

    public static function getEncodedFileFromUrl(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        if (!Validator::stringType()->notEmpty()->validate($queryParams['url'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params url is empty']);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/documentEditorsConfig.xml']);
        if (empty($loadedXml) || empty($loadedXml->onlyoffice->enabled) || $loadedXml->onlyoffice->enabled == 'false' || empty($loadedXml->onlyoffice->server_uri)) {
            return $response->withStatus(400)->withJson(['errors' => 'Onlyoffice is not enabled']);
        }

        $checkUrl = str_replace('http://', '', $queryParams['url']);
        $checkUrl = str_replace('https://', '', $checkUrl);
        $uri = (string)$loadedXml->onlyoffice->server_uri;
        $port = (string)$loadedXml->onlyoffice->server_port;

        if (strpos($checkUrl, "{$uri}:{$port}/cache/files/") !== 0 && (($port != 80 && $port != 443) || strpos($checkUrl, "{$uri}/cache/files/") !== 0)) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params url is not allowed']);
        }

        $fileContent = file_get_contents($queryParams['url']);
        if ($fileContent == false) {
            return $response->withStatus(400)->withJson(['errors' => 'No content found']);
        }

        return $response->withJson(['encodedFile' => base64_encode($fileContent)]);
    }

    public static function isAvailable(Request $request, Response $response)
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/documentEditorsConfig.xml']);
        if (empty($loadedXml) || empty($loadedXml->onlyoffice->enabled) || $loadedXml->onlyoffice->enabled == 'false') {
            return $response->withStatus(400)->withJson(['errors' => 'Onlyoffice is not enabled', 'lang' => 'onlyOfficeNotEnabled']);
        } elseif (empty($loadedXml->onlyoffice->server_uri)) {
            return $response->withStatus(400)->withJson(['errors' => 'Onlyoffice server_uri is empty', 'lang' => 'uriIsEmpty']);
        } elseif (empty($loadedXml->onlyoffice->server_port)) {
            return $response->withStatus(400)->withJson(['errors' => 'Onlyoffice server_port is empty', 'lang' => 'portIsEmpty']);
        }

        $uri = (string)$loadedXml->onlyoffice->server_uri;
        $port = (string)$loadedXml->onlyoffice->server_port;

        $exec = shell_exec("nc -vz -w 5 {$uri} {$port} 2>&1");

        if (strpos($exec, 'not found') !== false) {
            return $response->withStatus(400)->withJson(['errors' => 'Netcat command not found', 'lang' => 'preRequisiteMissing']);
        }

        $isAvailable = strpos($exec, 'succeeded!') !== false || strpos($exec, 'open') !== false || strpos($exec, 'Connected') !== false;

        return $response->withJson(['isAvailable' => $isAvailable]);
    }
}
