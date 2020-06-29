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

use Attachment\models\AttachmentModel;
use Convert\controllers\ConvertPdfController;
use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Firebase\JWT\JWT;
use History\controllers\HistoryController;
use Resource\controllers\ResController;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\CoreController;
use SrcCore\controllers\UrlController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class CollaboraOnlineController
{
    public function getFileContent(Request $request, Response $response, array $args)
    {
        $queryParams = $request->getQueryParams();

        if (!Validator::stringType()->notEmpty()->validate($queryParams['access_token'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query access_token is empty or not a string']);
        }

        $tokenCheckResult = CollaboraOnlineController::checkToken(['token' => $queryParams['access_token'], 'id' => $args['id']]);
        if (!empty($tokenCheckResult['errors'])) {
            return $response->withStatus($tokenCheckResult['code'])->withJson($tokenCheckResult['errors']);
        }

        if ($tokenCheckResult['type'] == 'resource') {
            if (!ResController::hasRightByResId(['resId' => [$args['id']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }

            $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename', 'version', 'fingerprint'], 'resId' => $args['id']]);
            if (empty($document['filename'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Document has no file']);
            }

            // If the document has a signed version, it cannot be edited
            $convertedDocument = AdrModel::getDocuments([
                'select' => ['docserver_id', 'path', 'filename', 'fingerprint'],
                'where'  => ['res_id = ?', 'type = ?', 'version = ?'],
                'data'   => [$args['resId'], 'SIGN', $document['version']],
                'limit'  => 1
            ]);
            if (!empty($convertedDocument[0])) {
                return $response->withStatus(400)->withJson(['errors' => 'Document was signed : it cannot be edited']);
            }
        }

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

        if (empty($convertedDocument) && empty($document['fingerprint']) && $tokenCheckResult['type'] == 'resource') {
            ResModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$args['id']]]);
            $document['fingerprint'] = $fingerprint;
        } else if (empty($convertedDocument) && empty($document['fingerprint']) && $tokenCheckResult['type'] == 'attachment') {
            AttachmentModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$args['id']]]);
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

        $result = CollaboraOnlineController::checkToken(['token' => $queryParams['access_token'], 'id' => $args['id']]);
        if (!empty($result['errors'])) {
            return $response->withStatus($result['code'])->withJson($result['errors']);
        }

        if ($result['type'] == 'resource') {
            if (!ResController::hasRightByResId(['resId' => [$args['id']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }

            $document = ResModel::getById(['select' => ['filename', 'filesize', 'modification_date'], 'resId' => $args['id']]);
        } else if ($result['type'] == 'attachment'){
            $document = AttachmentModel::getById(['select' => ['res_id_master', 'filename', 'filesize', 'modification_date'], 'resId' => $args['id']]);
            if (empty($document)) {
                return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
            }

            if (!ResController::hasRightByResId(['resId' => [$document['res_id_master']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }
        } else {
            return $response->withStatus(501)->withJson(['errors' => 'WIP - only resources and attachments can be edited for now']);
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
            'DisablePrint'            => true,
            'HideSaveOption'          => true,
            'UserFriendlyName'        => UserModel::getLabelledUserById(['id' => $GLOBALS['id']]),
            'OwnerId'                 => $GLOBALS['id'],
            'UserId'                  => $GLOBALS['id'],
            'LastModifiedTime'        => $modificationDate
        ]);
    }

    public function saveFile(Request $request, Response $response, array $args)
    {
        $headers = $request->getHeaders();

        // Collabora online saves automatically every X seconds, but we do not want to save the document yet
        if (empty($headers['HTTP_X_LOOL_WOPI_EXTENDEDDATA'][0])) {
            return $response->withStatus(200);
        }
        $extendedData = $headers['HTTP_X_LOOL_WOPI_EXTENDEDDATA'][0];
        $extendedData = explode('=', $extendedData);
        if (empty($extendedData) || $extendedData[0] != 'FinalSave' || $extendedData[1] != 'True') {
            return $response->withStatus(200);
        }

        $queryParams = $request->getQueryParams();

        if (!Validator::stringType()->notEmpty()->validate($queryParams['access_token'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query access_token is empty or not a string']);
        }

        $result = CollaboraOnlineController::checkToken(['token' => $queryParams['access_token'], 'id' => $args['id']]);
        if (!empty($result['errors'])) {
            return $response->withStatus($result['code'])->withJson($result['errors']);
        }

        if ($result['type'] == 'resource') {
            if (!ResController::hasRightByResId(['resId' => [$args['id']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }

            $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename', 'category_id', 'version', 'fingerprint', 'alt_identifier'], 'resId' => $args['id']]);
            if (empty($document['filename'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Document has no file']);
            }

            AdrModel::createDocumentAdr([
                'resId'       => $args['id'],
                'type'        => 'DOC',
                'docserverId' => $document['docserver_id'],
                'path'        => $document['path'],
                'filename'    => $document['filename'],
                'version'     => $document['version'],
                'fingerprint' => $document['fingerprint']
            ]);

            $fileContent = $request->getBody()->getContents();
            $encodedFile = base64_encode($fileContent);

            $extension = pathinfo($document['filename'], PATHINFO_EXTENSION);

            $data = [
                'resId'       => $args['id'],
                'encodedFile' => $encodedFile,
                'format'      => $extension
            ];

            $resId = StoreController::storeResource($data);
            if (empty($resId) || !empty($resId['errors'])) {
                return $response->withStatus(500)->withJson(['errors' => '[ResController update] ' . $resId['errors']]);
            }

            ConvertPdfController::convert([
                'resId'   => $args['id'],
                'collId'  => 'letterbox_coll',
                'version' => $document['version'] + 1
            ]);

            $customId = CoreConfigModel::getCustomId();
            $customId = empty($customId) ? 'null' : $customId;
            exec("php src/app/convert/scripts/FullTextScript.php --customId {$customId} --resId {$args['id']} --collId letterbox_coll --userId {$GLOBALS['id']} > /dev/null &");

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $args['id'],
                'eventType' => 'UP',
                'info'      => _FILE_UPDATED . " : {$document['alt_identifier']}",
                'moduleId'  => 'resource',
                'eventId'   => 'fileModification'
            ]);
        }

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

        $isAvailable = DocumentEditorController::isAvailable(['uri' => $uri, 'port' => $port]);

        if (!empty($isAvailable['errors'])) {
            return $response->withStatus(400)->withJson($isAvailable);
        }

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
        if (!Validator::stringType()->notEmpty()->validate($body['mode'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body mode is empty or not a string']);
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
            'url'    => $url . '/hosting/discovery',
            'method' => 'GET',
            'ixXml'  => true
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
            'type'   => $body['type'],
            'mode'   => $body['mode']
        ];

        $jwt = JWT::encode($payload, CoreConfigModel::getEncryptKey());

        // TODO check if ssl
        $urlIFrame = $urlSrc . 'WOPISrc=' . $coreUrl . 'rest/wopi/files/' . $body['resId'] . '&access_token=' . $jwt . '&NotWOPIButIframe=true';

        return $response->withJson(['url' => $urlIFrame]);
    }

    private static function checkToken(array $args)
    {
        ValidatorModel::notEmpty($args, ['token', 'id']);
        ValidatorModel::stringType($args, ['token']);
        ValidatorModel::intVal($args, ['id']);

        try {
            $jwt = JWT::decode($args['token'], CoreConfigModel::getEncryptKey(), ['HS256']);
        } catch (\Exception $e) {
            return ['code' => 401, 'errors' => 'Access token is invalid'];
        }

        if (empty($jwt->resId) || empty($jwt->userId) || empty($jwt->type) || empty($jwt->mode)) {
            return ['code' => 401, 'errors' => 'Access token is invalid'];
        }

        if ($jwt->resId != $args['id']) {
            return ['code' => 401, 'errors' => 'Access token is invalid'];
        }

        CoreController::setGlobals(['userId' => $jwt->userId]);

        if ($jwt->type != 'resource' && $jwt->type != 'attachment') {
            return ['code' => 400, 'errors' => 'WIP - only resources and attachments can be edited for now'];
        }

        return [
            'type' => $jwt->type,
            'mode' => $jwt->mode
        ];
    }
}
