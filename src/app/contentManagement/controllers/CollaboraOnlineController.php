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
use SrcCore\controllers\CoreController;
use SrcCore\controllers\UrlController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\ValidatorModel;
use Template\models\TemplateModel;
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
            return $response->withStatus($tokenCheckResult['code'])->withJson(['errors' => $tokenCheckResult['errors']]);
        }

        $document = CollaboraOnlineController::getDocument([
            'id'   => $args['id'],
            'type' => $tokenCheckResult['type'],
            'mode' => $tokenCheckResult['mode']
        ]);

        if (!empty($document['errors'])) {
            return $response->withStatus($document['code'])->withJson(['errors' => $document['errors']]);
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        if ($tokenCheckResult['mode'] == 'edition' && ($tokenCheckResult['type'] == 'resource' || $tokenCheckResult['type'] == 'attachment')) {
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

        $tokenCheckResult = CollaboraOnlineController::checkToken(['token' => $queryParams['access_token'], 'id' => $args['id']]);
        if (!empty($tokenCheckResult['errors'])) {
            return $response->withStatus($tokenCheckResult['code'])->withJson(['errors' => $tokenCheckResult['errors']]);
        }

        $document = CollaboraOnlineController::getDocument([
            'id'   => $args['id'],
            'type' => $tokenCheckResult['type'],
            'mode' => $tokenCheckResult['mode']
        ]);

        if (!empty($document['errors'])) {
            return $response->withStatus($document['code'])->withJson(['errors' => $document['errors']]);
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

        $tokenCheckResult = CollaboraOnlineController::checkToken(['token' => $queryParams['access_token'], 'id' => $args['id']]);
        if (!empty($tokenCheckResult['errors'])) {
            return $response->withStatus($tokenCheckResult['code'])->withJson(['errors' => $tokenCheckResult['errors']]);
        }

        $document = CollaboraOnlineController::getDocument([
            'id'   => $args['id'],
            'type' => $tokenCheckResult['type'],
            'mode' => $tokenCheckResult['mode']
        ]);

        if (!empty($document['errors'])) {
            return $response->withStatus($document['code'])->withJson(['errors' => $document['errors']]);
        }

        $fileContent = $request->getBody()->getContents();

        $extension = pathinfo($document['filename'], PATHINFO_EXTENSION);
        $tmpPath = CoreConfigModel::getTmpPath();
        $filename = "collabora_{$GLOBALS['id']}_{$tokenCheckResult['type']}_{$tokenCheckResult['mode']}_{$args['id']}.{$extension}";

        $put = file_put_contents($tmpPath . $filename, $fileContent);
        if ($put === false) {
            return $response->withStatus(400)->withJson(['errors' => 'File put contents failed']);
        }

        return $response->withStatus(200);
    }

    public function getTmpFile(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['token'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query token is empty or not a string']);
        }

        $tokenCheckResult = CollaboraOnlineController::checkToken(['token' => $body['token']]);
        if (!empty($tokenCheckResult['errors'])) {
            return $response->withStatus($tokenCheckResult['code'])->withJson(['errors' => $tokenCheckResult['errors']]);
        }

        $document = CollaboraOnlineController::getDocument([
            'id'   => $tokenCheckResult['resId'],
            'type' => $tokenCheckResult['type'],
            'mode' => $tokenCheckResult['mode']
        ]);

        if (!empty($document['errors'])) {
            return $response->withStatus($document['code'])->withJson(['errors' => $document['errors']]);
        }

        $extension = pathinfo($document['filename'], PATHINFO_EXTENSION);
        $filename = "collabora_{$GLOBALS['id']}_{$tokenCheckResult['type']}_{$tokenCheckResult['mode']}_{$tokenCheckResult['resId']}.{$extension}";
        $tmpPath = CoreConfigModel::getTmpPath();
        $pathToDocument = $tmpPath . $filename;

        if ($tokenCheckResult['mode'] == 'creation' && ($tokenCheckResult['type'] == 'resource' || $tokenCheckResult['type'] == 'attachment')) {
            $dataToMerge = ['userId' => $GLOBALS['id']];
            if (!empty($body['data']) && is_array($body['data'])) {
                $dataToMerge = array_merge($dataToMerge, $body['data']);
            }

            $mergedDocument = MergeController::mergeDocument([
                'path' => $pathToDocument,
                'data' => $dataToMerge
            ]);
            $content = $mergedDocument['encodedDocument'];
        } else {
            $fileContent = file_get_contents($pathToDocument);
            if ($fileContent === false) {
                return $response->withStatus(404)->withJson(['errors' => 'Document not found']);
            }

            $content = base64_encode($fileContent);
        }

        return $response->withJson(['content' => $content, 'format' => $extension]);
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

        $document = CollaboraOnlineController::getDocument([
            'id'   => $body['resId'],
            'type' => $body['type'],
            'mode' => $body['mode']
        ]);

        if (!empty($document['errors'])) {
            return $response->withStatus($document['code'])->withJson(['errors' => $document['errors']]);
        }

        $extension = pathinfo($document['filename'], PATHINFO_EXTENSION);

        $url = (string)$loadedXml->collaboraonline->server_uri . ':' . (string)$loadedXml->collaboraonline->server_port;

        $discovery = CurlModel::execSimple([
            'url'    => $url . '/hosting/discovery',
            'method' => 'GET',
            'isXml'  => true
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

        return $response->withJson(['url' => $urlIFrame, 'token' => $jwt]);
    }

    private static function checkToken(array $args)
    {
        ValidatorModel::notEmpty($args, ['token']);
        ValidatorModel::stringType($args, ['token']);
        ValidatorModel::intVal($args, ['id']);

        try {
            $jwt = JWT::decode($args['token'], CoreConfigModel::getEncryptKey(), ['HS256']);
        } catch (\Exception $e) {
            return ['code' => 401, 'errors' => 'Collabora Online access token is invalid'];
        }

        if (empty($jwt->resId) || empty($jwt->userId) || empty($jwt->type) || empty($jwt->mode)) {
            return ['code' => 401, 'errors' => 'Collabora Online access token is invalid'];
        }

        if (!empty($args['id']) && $jwt->resId != $args['id']) {
            return ['code' => 401, 'errors' => 'Collabora Online access token is invalid'];
        }

        CoreController::setGlobals(['userId' => $jwt->userId]);

        if ($jwt->type != 'resource' && $jwt->type != 'attachment') {
            return ['code' => 501, 'errors' => 'WIP - only resources and attachments can be edited for now'];
        }

        return [
            'type'  => $jwt->type,
            'mode'  => $jwt->mode,
            'resId' => $jwt->resId
        ];
    }

    private static function getDocument(array $args)
    {
        ValidatorModel::notEmpty($args, ['id', 'type', 'mode']);
        ValidatorModel::stringType($args, ['type', 'mode']);
        ValidatorModel::intVal($args, ['id']);

        if ($args['type'] == 'resource' && $args['mode'] == 'edition') {
            if (!ResController::hasRightByResId(['resId' => [$args['id']], 'userId' => $GLOBALS['id']])) {
                return ['code' => 403, 'errors' => 'Document out of perimeter'];
            }

            $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename', 'version', 'fingerprint'], 'resId' => $args['id']]);

            // If the document has a signed version, it cannot be edited
            $convertedDocument = AdrModel::getDocuments([
                'select' => ['docserver_id', 'path', 'filename', 'fingerprint'],
                'where'  => ['res_id = ?', 'type = ?', 'version = ?'],
                'data'   => [$args['resId'], 'SIGN', $document['version']],
                'limit'  => 1
            ]);
            if (!empty($convertedDocument[0])) {
                return ['code' => 400, 'errors' => 'Document was signed : it cannot be edited'];
            }
        } else if ($args['type'] == 'resource' && $args['mode'] == 'creation') {
            $document = TemplateModel::getById(['select' => ['template_file_name', 'template_target', 'template_path', 'template_file_name'], 'id' => $args['id']]);
            if (empty($document)) {
                return ['code' => 400, 'errors' => 'Document does not exist'];
            }
            // TODO check template perimeter
            if ($document['template_target'] != 'indexingFile') {
                return ['code' => 400, 'errors' => 'Template is not for resource creation'];
            }
            $document['filename'] = $document['template_file_name'];
            $document['docserver_id'] = 'TEMPLATES';
            $document['path'] = $document['template_path'];
            $document['filename'] = $document['template_file_name'];

            $document['modification_date'] = new \DateTime();
            $document['modification_date'] = $document['modification_date']->format(\DateTime::ISO8601);

        } else if ($args['type'] == 'attachment' && $args['mode'] == 'edition') {
            $document = AttachmentModel::getById(['select' => ['res_id_master', 'filename', 'filesize', 'modification_date', 'docserver_id', 'path', 'fingerprint'], 'id' => $args['id']]);
            if (empty($document)) {
                return ['code' => 400, 'errors' => 'Document does not exist'];
            }

            if (!ResController::hasRightByResId(['resId' => [$document['res_id_master']], 'userId' => $GLOBALS['id']])) {
                return ['code' => 403, 'errors' => 'Document out of perimeter'];
            }

            // TODO check if editing last version
            // TODO check if last version is signed
            // If the document has a signed version, it cannot be edited
//            $convertedDocument = AdrModel::getAttachments([
//                'select' => ['docserver_id', 'path', 'filename', 'fingerprint'],
//                'where'  => ['res_id = ?', 'type = ?'],
//                'data'   => [$args['resId'], 'SIGN'],
//                'limit'  => 1
//            ]);
//            if (!empty($convertedDocument[0])) {
//                return $response->withStatus(400)->withJson(['errors' => 'Document was signed : it cannot be edited']);
//            }
        }  else if ($args['type'] == 'attachment' && $args['mode'] == 'creation') {
            $document = TemplateModel::getById(['select' => ['template_file_name', 'template_target', 'template_path'], 'id' => $args['id']]);
            if (empty($document)) {
                return ['code' => 400, 'errors' => 'Document does not exist'];
            }

            if ($document['template_target'] != 'attachments') {
                return ['code' => 400, 'errors' => 'Template is not for attachments creation'];
            }
            $document['filename'] = $document['template_file_name'];
            $document['docserver_id'] = 'TEMPLATES';
            $document['path'] = $document['template_path'];

            $document['modification_date'] = new \DateTime();
            $document['modification_date'] = $document['modification_date']->format(\DateTime::ISO8601);
        } else {
            // TODO create + edit templates
            return ['code' => 501, 'errors' => 'WIP'];
        }

        if (empty($document['filename'])) {
            return ['code' => 400, 'errors' => 'Document has no file'];
        }

        return $document;
    }
}
