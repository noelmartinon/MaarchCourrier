<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief   Alfresco Controller
* @author  dev@maarch.org
*/

namespace Alfresco\controllers;

use Attachment\models\AttachmentModel;
use Convert\controllers\ConvertPdfController;
use Docserver\models\DocserverModel;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\PasswordModel;
use User\models\UserModel;

class AlfrescoController
{
    public function getRootFolders(Request $request, Response $response)
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/alfrescoConfig.xml']);

        if (empty($loadedXml) || (string)$loadedXml->ENABLED != 'true') {
            return $response->withStatus(400)->withJson(['errors' => 'Alfresco configuration is not enabled']);
        } elseif (empty((string)$loadedXml->URI)) {
            return $response->withStatus(400)->withJson(['errors' => 'Alfresco configuration URI is empty']);
        }
        $alfrescoUri = rtrim((string)$loadedXml->URI, '/');

        $entity = UserModel::getPrimaryEntityById(['id' => $GLOBALS['id'], 'select' => ['entities.external_id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'User has no primary entity']);
        }
        $entityInformations = json_decode($entity['external_id'], true);
        if (empty($entityInformations['alfrescoNodeId']) || empty($entityInformations['alfrescoLogin']) || empty($entityInformations['alfrescoPassword'])) {
            return $response->withStatus(400)->withJson(['errors' => 'User primary entity has not enough alfresco informations']);
        }
//        $entityInformations['alfrescoPassword'] = PasswordModel::decrypt(['cryptedPassword' => $entityInformations['alfrescoPassword']]);

        $curlResponse = CurlModel::execSimple([
            'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$entityInformations['alfrescoNodeId']}/children",
            'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
            'headers'       => ['content-type:application/json'],
            'method'        => 'GET',
            'queryParams'   => ['where' => '(isFolder=true)']
        ]);
        if ($curlResponse['code'] != 200) {
            return $response->withStatus(400)->withJson(['errors' => json_encode($curlResponse['response'])]);
        }

        $folders = [];
        if (!empty($curlResponse['response']['list']['entries'])) {
            foreach ($curlResponse['response']['list']['entries'] as $value) {
                $folders[] = [
                    'id'        => $value['entry']['id'],
                    'icon'      => 'fa fa-folder',
                    'text'      => $value['entry']['name'],
                    'parent'    => '#',
                    'children'  => true
                ];
            }
        }

        return $response->withJson($folders);
    }

    public function getChildrenFoldersById(Request $request, Response $response, array $args)
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/alfrescoConfig.xml']);

        if (empty($loadedXml) || (string)$loadedXml->ENABLED != 'true') {
            return $response->withStatus(400)->withJson(['errors' => 'Alfresco configuration is not enabled']);
        } elseif (empty((string)$loadedXml->URI)) {
            return $response->withStatus(400)->withJson(['errors' => 'Alfresco configuration URI is empty']);
        }
        $alfrescoUri = rtrim((string)$loadedXml->URI, '/');

        $entity = UserModel::getPrimaryEntityById(['id' => $GLOBALS['id'], 'select' => ['entities.external_id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'User has no primary entity']);
        }
        $entityInformations = json_decode($entity['external_id'], true);
        if (empty($entityInformations['alfrescoNodeId']) || empty($entityInformations['alfrescoLogin']) || empty($entityInformations['alfrescoPassword'])) {
            return $response->withStatus(400)->withJson(['errors' => 'User primary entity has not enough alfresco informations']);
        }
//        $entityInformations['alfrescoPassword'] = PasswordModel::decrypt(['cryptedPassword' => $entityInformations['alfrescoPassword']]);

        $curlResponse = CurlModel::execSimple([
            'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$args['id']}/children",
            'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
            'headers'       => ['content-type:application/json'],
            'method'        => 'GET',
            'queryParams'   => ['where' => '(isFolder=true)']
        ]);
        if ($curlResponse['code'] != 200) {
            return $response->withStatus(400)->withJson(['errors' => json_encode($curlResponse['response'])]);
        }

        $folders = [];
        if (!empty($curlResponse['response']['list']['entries'])) {
            foreach ($curlResponse['response']['list']['entries'] as $value) {
                $folders[] = [
                    'id'        => $value['entry']['id'],
                    'icon'      => 'fa fa-folder',
                    'text'      => $value['entry']['name'],
                    'parent'    => $args['id'],
                    'children'  => true
                ];
            }
        }

        return $response->withJson($folders);
    }

    public function getFolders(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        if (!Validator::stringType()->notEmpty()->validate($queryParams['search'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params search is empty']);
        } elseif (strlen($queryParams['search']) < 3) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params search is too short']);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/alfrescoConfig.xml']);

        if (empty($loadedXml) || (string)$loadedXml->ENABLED != 'true') {
            return $response->withStatus(400)->withJson(['errors' => 'Alfresco configuration is not enabled']);
        } elseif (empty((string)$loadedXml->URI)) {
            return $response->withStatus(400)->withJson(['errors' => 'Alfresco configuration URI is empty']);
        }
        $alfrescoUri = rtrim((string)$loadedXml->URI, '/');

        $entity = UserModel::getPrimaryEntityById(['id' => $GLOBALS['id'], 'select' => ['entities.external_id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'User has no primary entity']);
        }
        $entityInformations = json_decode($entity['external_id'], true);
        if (empty($entityInformations['alfrescoNodeId']) || empty($entityInformations['alfrescoLogin']) || empty($entityInformations['alfrescoPassword'])) {
            return $response->withStatus(400)->withJson(['errors' => 'User primary entity has not enough alfresco informations']);
        }
//        $entityInformations['alfrescoPassword'] = PasswordModel::decrypt(['cryptedPassword' => $entityInformations['alfrescoPassword']]);

        $body = [
            'query' => [
                'query'     => "select * from cmis:folder where cmis:name like '{$queryParams['search']}%' and IN_TREE('{$entityInformations['alfrescoNodeId']}')",
                'language'  => 'cmis',
            ],
            'fields' => ['id', 'name']
        ];
        $curlResponse = CurlModel::execSimple([
            'url'           => "{$alfrescoUri}/search/versions/1/search",
            'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
            'headers'       => ['content-type:application/json', 'Accept: application/json'],
            'method'        => 'POST',
            'body'          => json_encode($body)
        ]);
        if ($curlResponse['code'] != 200) {
            return $response->withStatus(400)->withJson(['errors' => json_encode($curlResponse['response'])]);
        }

        $folders = [];
        if (!empty($curlResponse['response']['list']['entries'])) {
            foreach ($curlResponse['response']['list']['entries'] as $value) {
                $folders[] = [
                    'id'        => $value['entry']['id'],
                    'icon'      => 'fa fa-folder',
                    'text'      => $value['entry']['name'],
                    'parent'    => '#',
                    'children'  => true
                ];
            }
        }

        return $response->withJson($folders);
    }

    public function sendResource(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/alfrescoConfig.xml']);

        if (empty($loadedXml) || (string)$loadedXml->ENABLED != 'true') {
            return $response->withStatus(400)->withJson(['errors' => 'Alfresco configuration is not enabled']);
        } elseif (empty((string)$loadedXml->URI)) {
            return $response->withStatus(400)->withJson(['errors' => 'Alfresco configuration URI is empty']);
        }
        $alfrescoUri = rtrim((string)$loadedXml->URI, '/');

        $entity = UserModel::getPrimaryEntityById(['id' => $GLOBALS['id'], 'select' => ['entities.external_id']]);
        if (empty($entity)) {
            return $response->withStatus(400)->withJson(['errors' => 'User has no primary entity']);
        }
        $entityInformations = json_decode($entity['external_id'], true);
        if (empty($entityInformations['alfrescoNodeId']) || empty($entityInformations['alfrescoLogin']) || empty($entityInformations['alfrescoPassword'])) {
            return $response->withStatus(400)->withJson(['errors' => 'User primary entity has not enough alfresco informations']);
        }
//        $entityInformations['alfrescoPassword'] = PasswordModel::decrypt(['cryptedPassword' => $entityInformations['alfrescoPassword']]);

        $document = ResModel::getById(['select' => ['filename', 'subject', 'alt_identifier', 'external_id'], 'resId' => $args['resId']]);
        if (empty($document)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        } elseif (empty($document['filename'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Document has no file']);
        }

        $convertedDocument = ConvertPdfController::getConvertedPdfById(['resId' => $args['resId'], 'collId' => 'letterbox_coll']);
        if (!empty($convertedDocument['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Conversion error : ' . $convertedDocument['errors']]);
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $convertedDocument['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedDocument['path']) . $convertedDocument['filename'];
        if (!is_file($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $fileContent = file_get_contents($pathToDocument);
        if ($fileContent === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $multipartBody = [
            'filedata' => ['isFile' => true, 'filename' => $document['subject'], 'content' => $fileContent],
        ];
        $curlResponse = CurlModel::execSimple([
            'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$args['id']}/children",
            'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
            'method'        => 'POST',
            'multipartBody' => $multipartBody
        ]);
        if ($curlResponse['code'] != 201) {
            return $response->withStatus(400)->withJson(['errors' => json_encode($curlResponse['response'])]);
        }

        $documentId = $curlResponse['response']['entry']['id'];

        $body = [
            'properties' => [
                'cm:description'    => $document['alt_identifier'],
            ],
        ];
        $curlResponse = CurlModel::execSimple([
            'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$documentId}",
            'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
            'headers'       => ['content-type:application/json', 'Accept: application/json'],
            'method'        => 'PUT',
            'body'          => json_encode($body)
        ]);
        if ($curlResponse['code'] != 200) {
            return $response->withStatus(400)->withJson(['errors' => json_encode($curlResponse['response'])]);
        }

        $externalId = json_decode($document['external_id'], true);
        $externalId['alfrescoId'] = $documentId;
        ResModel::update(['set' => ['external_id' => json_encode($externalId)], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);

        $attachments = AttachmentModel::get([
            'select'    => ['res_id', 'title', 'identifier', 'external_id'],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', 'status not in (?)'],
            'data'      => [$args['resId'], ['signed_response'], ['DEL', 'OBS']]
        ]);
        foreach ($attachments as $attachment) {
            $adrInfo = ConvertPdfController::getConvertedPdfById(['resId' => $attachment['res_id'], 'collId' => 'attachments_coll']);
            if (empty($adrInfo['docserver_id'])) {
                continue;
            }
            $docserver = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
            if (empty($docserver['path_template'])) {
                return ['error' => 'Docserver does not exist ' . $adrInfo['docserver_id']];
            }
            $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $adrInfo['path']) . $adrInfo['filename'];
            if (!is_file($pathToDocument)) {
                return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
            }

            $fileContent = file_get_contents($pathToDocument);
            if ($fileContent === false) {
                return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
            }

            $multipartBody = [
                'filedata' => ['isFile' => true, 'filename' => $attachment['title'], 'content' => $fileContent],
            ];
            $curlResponse = CurlModel::execSimple([
                'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$args['id']}/children",
                'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
                'method'        => 'POST',
                'multipartBody' => $multipartBody
            ]);
            if ($curlResponse['code'] != 201) {
                return $response->withStatus(400)->withJson(['errors' => json_encode($curlResponse['response'])]);
            }

            $attachmentId = $curlResponse['response']['entry']['id'];

            $body = [
                'properties' => [
                    'cm:description'    => $attachment['identifier'],
                ],
            ];
            $curlResponse = CurlModel::execSimple([
                'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$attachmentId}",
                'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
                'headers'       => ['content-type:application/json', 'Accept: application/json'],
                'method'        => 'PUT',
                'body'          => json_encode($body)
            ]);
            if ($curlResponse['code'] != 200) {
                return $response->withStatus(400)->withJson(['errors' => json_encode($curlResponse['response'])]);
            }

            $externalId = json_decode($attachment['external_id'], true);
            $externalId['alfrescoId'] = $attachmentId;
            AttachmentModel::update(['set' => ['external_id' => json_encode($externalId)], 'where' => ['res_id = ?'], 'data' => [$attachment['res_id']]]);
        }

        return $response->withStatus(204);
    }
}
