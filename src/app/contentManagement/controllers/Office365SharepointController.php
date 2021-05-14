<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Office 365 Sharepoint Controller
 *
 * @author dev@maarch.org
 */

namespace ContentManagement\controllers;

use Attachment\models\AttachmentModel;
use Configuration\models\ConfigurationModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CurlModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;


class Office365SharepointController
{
    public function sendDocument(Request $request, Response $response, array $args)
    {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_document_editors', 'select' => ['value']]);
        $configuration = !empty($configuration['value']) ? json_decode($configuration['value'], true) : [];

        if (empty($configuration) || empty($configuration['office365sharepoint'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Office 365 Sharepoint Online is not enabled', 'lang' => 'office365SharepointNotEnabled']);
        }
        $configuration = $configuration['office365sharepoint'];

        $body = $request->getParsedBody();
        if (!Validator::intVal()->notEmpty()->validate($body['resId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body resId is empty or not an integer']);
        }
        if (!Validator::stringType()->notEmpty()->validate($body['type'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body type is empty or not a string']);
        }
        if (!empty($body['format']) && !Validator::stringType()->validate($body['format'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body format is not a string']);
        }
        if (!empty($body['path']) && !Validator::stringType()->validate($body['path'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body path is not a string']);
        }
        if (!empty($body['data']) && !Validator::arrayType()->validate($body['data'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body data is not a string']);
        }

        if (empty($body['encodedContent'])) {
            $document = CollaboraOnlineController::getDocument([
                'id'     => $body['resId'],
                'type'   => $body['type'],
                'format' => $body['format'],
                'path'   => $body['path']
            ]);

            if (!empty($document['errors'])) {
                return $response->withStatus($document['code'])->withJson(['errors' => $document['errors']]);
            }

            if (!empty($document['docserver_id'])) {
                $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
                if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
                }
            } else {
                $docserver['path_template'] = '';
            }

            $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
            if (!file_exists($pathToDocument)) {
                return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
            }

            if ($body['type'] == 'resourceModification' || $body['type'] == 'attachmentModification') {
                $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
                $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);

                if (empty($document['fingerprint']) && $body['type'] == 'resourceModification') {
                    ResModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$args['id']]]);
                    $document['fingerprint'] = $fingerprint;
                } elseif (empty($document['fingerprint']) && $body['type'] == 'attachmentModification') {
                    AttachmentModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$args['id']]]);
                    $document['fingerprint'] = $fingerprint;
                }

                if ($document['fingerprint'] != $fingerprint) {
                    return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
                }
            }

            if ($body['type'] == 'resourceCreation' || $body['type'] == 'attachmentCreation') {
                $dataToMerge = ['userId' => $GLOBALS['id']];
                if (!empty($tokenCheckResult['data']) && is_array($tokenCheckResult['data'])) {
                    $dataToMerge = array_merge($dataToMerge, $tokenCheckResult['data']);
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
            $pathInfo = pathinfo($pathToDocument);
        } else {
            $content = $body['encodedContent'];
            $pathInfo['extension'] = $body['format'];
        }
        $fileContent = base64_decode($content);
        $fileSize = strlen($fileContent);

        $filename = "maarch_{$GLOBALS['login']}_" . rand() . ".{$pathInfo['extension']}";

        $accessToken = Office365SharepointController::getAuthenticationToken(['configuration' => $configuration]);

        if (!empty($accessToken['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $accessToken['errors']]);
        }

        $sendResult = CurlModel::exec([
            'url'        => 'https://graph.microsoft.com/v1.0/sites/' . $configuration['siteId'] . '/drive/root:/' . $filename . ':/content',
            'bearerAuth' => ['token' => $accessToken],
            'headers'    => ['Content-Type: text/plain'],
            'method'     => 'PUT',
            'body'       => 'maarch'
        ]);

        if ($sendResult['code'] != 201) {
            return $response->withStatus(400)->withJson(['errors' => 'Could not create the document in sharepoint', 'sharepointError' => $sendResult['response']['error']]);
        }

        $id = $sendResult['response']['id'];

        $body = json_encode(['item' => ['@microsoft.graph.conflictBehavior' => 'replace']]);
        $sendResult = CurlModel::exec([
            'url'        => 'https://graph.microsoft.com/v1.0/sites/' . $configuration['siteId'] . '/drive/items/' . $id . '/createUploadSession',
            'bearerAuth' => ['token' => $accessToken],
            'headers'    => ['Content-Type: application/json', 'Content-Length: ' . strlen($body)],
            'method'     => 'POST',
            'body'       => $body
        ]);

        if ($sendResult['code'] != 200) {
            return $response->withStatus(400)->withJson(['errors' => 'Could not create upload session to send the document to sharepoint', 'sharepointError' => $sendResult['response']['error']]);
        }

        $sendResult = CurlModel::exec([
            'url'        => $sendResult['response']['uploadUrl'],
            'bearerAuth' => ['token' => $accessToken],
            'headers'    => ['Content-Type: text/plain', 'Content-Range: bytes 0-' . ($fileSize - 1) . '/' . $fileSize],
            'method'     => 'PUT',
            'body'       => $fileContent
        ]);

        if ($sendResult['code'] != 200) {
            return $response->withStatus(400)->withJson(['errors' => 'Could not send the document to sharepoint', 'sharepointError' => $sendResult['response']['error']]);
        }

        $webUrl = $sendResult['response']['webUrl'];
        $id = $sendResult['response']['id'];

        $currentUser = UserModel::getById(['id' => $GLOBALS['id'], 'select' => ['mail']]);

        $body = [
            'requireSignIn'  => true,
            'sendInvitation' => false,
            'roles'          => ['read', 'write'],
            'recipients'     => [
                ['email' => $currentUser['mail']]
            ]
        ];

        // Add access permission to the document to the current user
        $result = CurlModel::exec([
            'url'        => 'https://graph.microsoft.com/v1.0/sites/' . $configuration['siteId'] . '/drive/items/' . $id . '/invite',
            'bearerAuth' => ['token' => $accessToken],
            'headers'    => ['Content-Type: application/json'],
            'method'     => 'POST',
            'body'       => json_encode($body)
        ]);

        if ($result['code'] != 200) {
            return $response->withStatus(400)->withJson(['errors' => 'Could not share the document with user', 'sharepointError' => $result['response']['error']]);
        }

        return $response->withJson(['webUrl' => $webUrl, 'documentId' => $id]);
    }

    public function getFileContent(Request $request, Response $response, array $args)
    {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_document_editors', 'select' => ['value']]);
        $configuration = !empty($configuration['value']) ? json_decode($configuration['value'], true) : [];

        if (empty($configuration) || empty($configuration['office365sharepoint'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Office 365 Sharepoint Online is not enabled', 'lang' => 'office365SharepointNotEnabled']);
        }
        $configuration = $configuration['office365sharepoint'];

        if (!Validator::stringType()->notEmpty()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Argument id is empty or not a string']);
        }

        $accessToken = Office365SharepointController::getAuthenticationToken(['configuration' => $configuration]);

        if (!empty($accessToken['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $accessToken['errors']]);
        }

        $result = CurlModel::exec([
            'url'            => 'https://graph.microsoft.com/v1.0/sites/' . $configuration['siteId'] . '/drive/items/' . $args['id'] . '/content',
            'bearerAuth'     => ['token' => $accessToken],
            'method'         => 'GET',
            'followRedirect' => true,
            'fileResponse'   => true
        ]);

        if ($result['code'] != 200) {
            return $response->withStatus(400)->withJson(['errors' => 'Could not get the document from sharepoint', 'sharepointError' => $result['response']['error']]);
        }

        if (empty($result['response'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Could not get the document content from sharepoint']);
        }

        $content = $result['response'];

        return $response->withJson(['content' => base64_encode($content)]);
    }

    public function deleteFile(Request $request, Response $response, array $args)
    {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_document_editors', 'select' => ['value']]);
        $configuration = !empty($configuration['value']) ? json_decode($configuration['value'], true) : [];

        if (empty($configuration) || empty($configuration['office365sharepoint'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Office 365 Sharepoint Online is not enabled', 'lang' => 'office365SharepointNotEnabled']);
        }
        $configuration = $configuration['office365sharepoint'];

        if (!Validator::stringType()->notEmpty()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Argument id is empty or not a string']);
        }

        $accessToken = Office365SharepointController::getAuthenticationToken(['configuration' => $configuration]);

        if (!empty($accessToken['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $accessToken['errors']]);
        }

        $result = CurlModel::exec([
            'url'        => 'https://graph.microsoft.com/v1.0/sites/' . $configuration['siteId'] . '/drive/items/' . $args['id'],
            'bearerAuth' => ['token' => $accessToken],
            'method'     => 'DELETE'
        ]);

        if ($result['code'] != 204) {
            return $response->withStatus(400)->withJson(['errors' => 'Could not delete document in Sharepoint', 'sharepointError' => $result['response']['error']]);
        }

        return $response->withStatus(204);
    }

    public static function getSiteId(array $args) {
        ValidatorModel::notEmpty($args, ['clientId', 'clientSecret', 'tenantId', 'siteUrl']);
        ValidatorModel::stringType($args, ['clientId', 'clientSecret', 'tenantId', 'siteUrl']);

        $accessToken = Office365SharepointController::getAuthenticationToken([
            'configuration' => [
                'clientId'     => $args['clientId'],
                'clientSecret' => $args['clientSecret'],
                'tenantId'     => $args['tenantId']
            ]
        ]);

        $args['siteUrl'] = str_replace('https://', '', $args['siteUrl']);
        $args['siteUrl'] = str_replace('http://', '', $args['siteUrl']);
        $explodedSite =  explode('/', $args['siteUrl']);

        $tenantDomain = $explodedSite[0];
        unset($explodedSite[0]);
        $sitePath = implode('/', $explodedSite);

        $url = 'https://graph.microsoft.com/v1.0/sites/' . $tenantDomain . ':/' . $sitePath;

        $result = CurlModel::exec([
            'url'            => $url,
            'bearerAuth'     => ['token' => $accessToken],
            'method'         => 'GET'
        ]);

        if ($result['code'] != 200) {
            return ['errors' => 'Could not get the site id'];
        }

        if (empty($result['response']['id'])) {
            return ['errors' => 'Could not get the site id'];
        }

        return $result['response']['id'];
    }

    private static function getAuthenticationToken(array $args) {
        ValidatorModel::notEmpty($args, ['configuration']);
        ValidatorModel::arrayType($args, ['configuration']);

        $configuration = $args['configuration'];

        $body = [
            'grant_type=client_credentials',
            'scope=https://graph.microsoft.com/.default',
            'client_id=' . $configuration['clientId'],
            'client_secret=' . $configuration['clientSecret']
        ];

        $curlResponse = CurlModel::exec([
            'url'     => 'https://login.microsoftonline.com/' . $configuration['tenantId'] . '/oauth2/v2.0/token',
            'headers' => ['Content-Type: application/x-www-form-urlencoded'],
            'method'  => 'POST',
            'body' => implode('&', $body)
        ]);

        if ($curlResponse['code'] != 200) {
            if (!empty($curlResponse['errors'])) {
                return ['errors' => $curlResponse['errors']];
            }
            return ['errors' => 'Error while getting token for Microsoft Graph'];
        }

        if (empty($curlResponse['response']['access_token'])) {
            return ['errors' => 'Microsoft Graph access token is empty'];
        }

        return $curlResponse['response']['access_token'];
    }
}
