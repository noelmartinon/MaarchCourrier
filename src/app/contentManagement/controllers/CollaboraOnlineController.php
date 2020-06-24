<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Collabora Online Controller
 *
 * @author dev@maarch.org
 */

namespace ContentManagement\controllers;

use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Firebase\JWT\JWT;
use Resource\controllers\ResController;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\UrlController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use User\models\UserModel;

class CollaboraOnlineController
{
    public function getFileContent(Request $request, Response $response, array $args)
    {
        $queryParams = $request->getQueryParams();

        if (!Validator::stringType()->notEmpty()->validate($queryParams['access_token'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query access_token is empty or not a string']);
        }

        try {
            $jwt = JWT::decode($queryParams['access_token'], CoreConfigModel::getEncryptKey(), ['HS256']);
        } catch (\Exception $e) {
            return $response->withStatus(401)->withJson(['errors' => 'Access token is invalid']);
        }

        if ($jwt->resId != $args['id']) {
            return $response->withStatus(401)->withJson(['errors' => 'Access token is invalid']);
        }

        $GLOBALS['id'] = $jwt->userId;

        if ($jwt->type != 'resource') {
            return $response->withStatus(400)->withJson(['errors' => 'WIP - only resources can be edited for now']);
        }

        if (!ResController::hasRightByResId(['resId' => [$args['id']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename', 'category_id', 'version', 'fingerprint'], 'resId' => $args['id']]);
        if (empty($document)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        if (empty($document['filename'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Document has no file']);
        }

        $convertedDocument = AdrModel::getDocuments([
            'select' => ['docserver_id', 'path', 'filename', 'fingerprint'],
            'where'  => ['res_id = ?', 'type = ?', 'version = ?'],
            'data'   => [$args['resId'], 'SIGN', $document['version']],
            'limit'  => 1
        ]);
        $document = $convertedDocument[0] ?? $document;

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (empty($convertedDocument) && empty($document['fingerprint'])) {
            ResModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);
            $document['fingerprint'] = $fingerprint;
        }

        if ($document['fingerprint'] != $fingerprint) {
            return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
        }

        $fileContent = file_get_contents($pathToDocument);
        if ($fileContent === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $pathInfo = pathinfo($pathToDocument);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "attachment; filename=maarch.{$pathInfo['extension']}");
        return $response->withHeader('Content-Type', $mimeType);
    }

    public function getCheckFileInfo(Request $request, Response $response, array $args)
    {
        $queryParams = $request->getQueryParams();

        if (!Validator::stringType()->notEmpty()->validate($queryParams['access_token'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query access_token is empty or not a string']);
        }

        try {
            $jwt = JWT::decode($queryParams['access_token'], CoreConfigModel::getEncryptKey(), ['HS256']);
        } catch (\Exception $e) {
            return $response->withStatus(401)->withJson(['errors' => 'Access token is invalid']);
        }

        if ($jwt->resId != $args['id']) {
            return $response->withStatus(401)->withJson(['errors' => 'Access token is invalid']);
        }

        $GLOBALS['id'] = $jwt->userId;

        if ($jwt->type != 'resource') {
            return $response->withStatus(400)->withJson(['errors' => 'WIP - only resources can be edited for now']);
        }

        if (!ResController::hasRightByResId(['resId' => [$args['id']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename', 'category_id', 'version', 'filesize', 'modification_date'], 'resId' => $args['id']]);
        if (empty($document)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        if (empty($document['filename'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Document has no file']);
        }

        $modificationDate = new \DateTime($document['modification_date']);
        $modificationDate = $modificationDate->format(\DateTime::ISO8601);

        return $response->withJson([
            'BaseFileName'            => $document['filename'],
            'Size'                    => $document['filesize'],
            'UserCanNotWriteRelative' => true,
            'UserCanWrite'            => true,
            'UserFriendlyName'        => UserModel::getLabelledUserById(['id' => $GLOBALS['id']]),
            'OwnerId'                 => $GLOBALS['id'],
            'UserId'                  => $GLOBALS['id'],
            'LastModifiedTime'        => $modificationDate
        ]);
    }

    public function saveFile(Request $request, Response $response, array $args)
    {
        return $response->withStatus(200);
    }

    public static function isAvailable(Request $request, Response $response)
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/documentEditorsConfig.xml']);
        if (empty($loadedXml) || empty($loadedXml->collaboraonline->enabled) || $loadedXml->collaboraonline->enabled == 'false') {
            return $response->withStatus(400)->withJson(['errors' => 'Collabora Online is not enabled', 'lang' => 'collaboraOnlineNotEnabled']);
        } elseif (empty($loadedXml->collaboraonline->server_uri)) {
            return $response->withStatus(400)->withJson(['errors' => 'Collabora Online server_uri is empty', 'lang' => 'uriIsEmpty']);
        } elseif (empty($loadedXml->collaboraonline->server_port)) {
            return $response->withStatus(400)->withJson(['errors' => 'Collabora Online server_port is empty', 'lang' => 'portIsEmpty']);
        }

        $uri  = (string)$loadedXml->collaboraonline->server_uri;
        $port = (string)$loadedXml->collaboraonline->server_port;

        $aUri = explode("/", $uri);
        $exec = shell_exec("nc -vz -w 5 {$aUri[0]} {$port} 2>&1");

        if (strpos($exec, 'not found') !== false) {
            return $response->withStatus(400)->withJson(['errors' => 'Netcat command not found', 'lang' => 'preRequisiteMissing']);
        }

        $isAvailable = strpos($exec, 'succeeded!') !== false || strpos($exec, 'open') !== false || strpos($exec, 'Connected') !== false;

        return $response->withJson(['isAvailable' => $isAvailable]);
    }

    public static function getConfiguration(Request $request, Response $response)
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/documentEditorsConfig.xml']);

        if (empty($loadedXml) || empty($loadedXml->collaboraonline->enabled) || $loadedXml->collaboraonline->enabled == 'false' || empty($loadedXml->collaboraonline->server_uri)) {
            return $response->withStatus(400)->withJson(['errors' => 'Collabora Online server is disabled']);
        }

        $body = $request->getParsedBody();
        if (!Validator::intVal()->notEmpty()->validate($body['resId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body resId is empty or not an integer']);
        }
        if (!Validator::stringType()->notEmpty()->validate($body['type'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body type is empty or not a string']);
        }

        if ($body['type'] != 'resource') {
            return $response->withStatus(400)->withJson(['errors' => 'WIP - only resources can be edited for now']);
        }

        if (!ResController::hasRightByResId(['resId' => [$body['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $document = ResModel::getById(['select' => ['filename'], 'resId' => $body['resId']]);
        if (empty($document)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }
        $extension = pathinfo($document['filename'], PATHINFO_EXTENSION);

        $url = (string)$loadedXml->collaboraonline->server_uri . ':' . (string)$loadedXml->collaboraonline->server_port;

        $discovery = CurlModel::execSimple([
            'url'          => $url . '/hosting/discovery',
            'method'       => 'GET',
            'jsonResponse' => false
        ]);

        if ($discovery['code'] != 200) {
            return $response->withStatus(400)->withJson(['errors' => 'Collabora discovery failed']);
        }

        $urlSrc = null;
        foreach ($discovery['response']->{'net-zone'}->app as $app) {
            if ($app->action['ext'] == $extension) {
                $urlSrc = (string) $app->action['urlsrc'];
                break;
            }
        }

        $coreUrl = str_replace('rest/', '', UrlController::getCoreUrl());

        $jwt = null;
        $payload = [
            'userId' => $GLOBALS['id'],
            'resId'  => $body['resId'],
            'type'   => $body['type']
        ];

        $jwt = JWT::encode($payload, CoreConfigModel::getEncryptKey());

        $urlIFrame = $urlSrc . 'WOPISrc=' . $coreUrl . 'rest/wopi/files/' . $body['resId'] . '&access_token=' . $jwt;

        return $response->withJson(['url' => $urlIFrame]);
    }
}
