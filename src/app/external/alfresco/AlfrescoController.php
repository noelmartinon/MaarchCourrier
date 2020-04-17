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
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\ValidatorModel;
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
        $entityInformations['alfrescoPassword'] = PasswordModel::decrypt(['cryptedPassword' => $entityInformations['alfrescoPassword']]);

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
        $entityInformations['alfrescoPassword'] = PasswordModel::decrypt(['cryptedPassword' => $entityInformations['alfrescoPassword']]);

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
        $entityInformations['alfrescoPassword'] = PasswordModel::decrypt(['cryptedPassword' => $entityInformations['alfrescoPassword']]);

        $search = addslashes($queryParams['search']);
        $body = [
            'query' => [
                'query'     => "select * from cmis:folder where CONTAINS ('cmis:name:*{$search}*') and IN_TREE('{$entityInformations['alfrescoNodeId']}')",
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

    public static function sendResource(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'folderId', 'userId']);
        ValidatorModel::intVal($args, ['resId', 'userId']);
        ValidatorModel::stringType($args, ['folderId', 'folderName']);

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/alfrescoConfig.xml']);

        if (empty($loadedXml) || (string)$loadedXml->ENABLED != 'true') {
            return ['errors' => 'Alfresco configuration is not enabled'];
        } elseif (empty((string)$loadedXml->URI)) {
            return ['errors' => 'Alfresco configuration URI is empty'];
        }
        $alfrescoUri = rtrim((string)$loadedXml->URI, '/');

        $entity = UserModel::getPrimaryEntityById(['id' => $args['userId'], 'select' => ['entities.external_id']]);
        if (empty($entity)) {
            return ['errors' => 'User has no primary entity'];
        }
        $entityInformations = json_decode($entity['external_id'], true);
        if (empty($entityInformations['alfrescoNodeId']) || empty($entityInformations['alfrescoLogin']) || empty($entityInformations['alfrescoPassword'])) {
            return ['errors' => 'User primary entity has not enough alfresco informations'];
        }
        $entityInformations['alfrescoPassword'] = PasswordModel::decrypt(['cryptedPassword' => $entityInformations['alfrescoPassword']]);

        $document = ResModel::getById(['select' => ['filename', 'subject', 'alt_identifier', 'external_id'], 'resId' => $args['resId']]);
        if (empty($document)) {
            return ['errors' => 'Document does not exist'];
        } elseif (empty($document['filename'])) {
            return ['errors' => 'Document has no file'];
        }

        $convertedDocument = ConvertPdfController::getConvertedPdfById(['resId' => $args['resId'], 'collId' => 'letterbox_coll']);
        if (!empty($convertedDocument['errors'])) {
            return ['errors' => 'Conversion error : ' . $convertedDocument['errors']];
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $convertedDocument['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => 'Docserver does not exist'];
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedDocument['path']) . $convertedDocument['filename'];
        if (!is_file($pathToDocument)) {
            return ['errors' => 'Document not found on docserver'];
        }

        $fileContent = file_get_contents($pathToDocument);
        if ($fileContent === false) {
            return ['errors' => 'Document not found on docserver'];
        }

        $curlResponse = CurlModel::execSimple([
            'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$args['folderId']}/children",
            'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
            'headers'       => ['content-type:application/json', 'Accept: application/json'],
            'method'        => 'POST',
            'body'          => json_encode(['name' => str_replace('/', '_', $document['alt_identifier']), 'nodeType' => 'cm:folder'])
        ]);
        if ($curlResponse['code'] != 201) {
            return ['errors' => "Create folder {$document['alt_identifier']} failed : " . json_encode($curlResponse['response'])];
        }
        $resourceFolderId = $curlResponse['response']['entry']['id'];

        $multipartBody = [
            'filedata' => ['isFile' => true, 'filename' => $document['subject'], 'content' => $fileContent],
        ];
        $curlResponse = CurlModel::execSimple([
            'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$resourceFolderId}/children",
            'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
            'method'        => 'POST',
            'multipartBody' => $multipartBody
        ]);
        if ($curlResponse['code'] != 201) {
            return ['errors' => "Send resource {$args['resId']} failed : " . json_encode($curlResponse['response'])];
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
            return ['errors' => "Update resource {$args['resId']} failed : " . json_encode($curlResponse['response'])];
        }

        $externalId = json_decode($document['external_id'], true);
        $externalId['alfrescoId'] = $documentId;
        ResModel::update(['set' => ['external_id' => json_encode($externalId)], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);

        $attachments = AttachmentModel::get([
            'select'    => ['res_id', 'title', 'identifier', 'external_id', 'docserver_id', 'path', 'filename', 'format'],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', 'status not in (?)'],
            'data'      => [$args['resId'], ['signed_response'], ['DEL', 'OBS']]
        ]);
        $firstAttachment = true;
        $attachmentsTitlesSent = [];
        foreach ($attachments as $attachment) {
            if ($attachment['format'] == 'xml') {
                $adrInfo = [
                    'docserver_id'  => $attachment['docserver_id'],
                    'path'          => $attachment['path'],
                    'filename'      => $attachment['filename']
                ];
            } else {
                $adrInfo = ConvertPdfController::getConvertedPdfById(['resId' => $attachment['res_id'], 'collId' => 'attachments_coll']);
            }
            if (empty($adrInfo['docserver_id'])) {
                continue;
            }
            $docserver = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
            if (empty($docserver['path_template'])) {
                continue;
            }
            $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $adrInfo['path']) . $adrInfo['filename'];
            if (!is_file($pathToDocument)) {
                continue;
            }
            $fileContent = file_get_contents($pathToDocument);
            if ($fileContent === false) {
                continue;
            }

            if ($firstAttachment) {
                $curlResponse = CurlModel::execSimple([
                    'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$resourceFolderId}/children",
                    'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
                    'headers'       => ['content-type:application/json', 'Accept: application/json'],
                    'method'        => 'POST',
                    'body'          => json_encode(['name' => 'Pièces jointes', 'nodeType' => 'cm:folder'])
                ]);
                if ($curlResponse['code'] != 201) {
                    return ['errors' => "Create folder 'Pièces jointes' failed : " . json_encode($curlResponse['response'])];
                }
                $attachmentsFolderId = $curlResponse['response']['entry']['id'];
            }

            if (empty($attachmentsFolderId)) {
                continue;
            }
            $firstAttachment = false;
            if (in_array($attachment['title'], $attachmentsTitlesSent)) {
                $i = 1;
                $newTitle = "{$attachment['title']}_{$i}";
                while (in_array($newTitle, $attachmentsTitlesSent)) {
                    $newTitle = "{$attachment['title']}_{$i}";
                    ++$i;
                }
                $attachment['title'] = $newTitle;
            }
            $multipartBody = [
                'filedata' => ['isFile' => true, 'filename' => $attachment['title'], 'content' => $fileContent],
            ];
            $curlResponse = CurlModel::execSimple([
                'url'           => "{$alfrescoUri}/alfresco/versions/1/nodes/{$attachmentsFolderId}/children",
                'basicAuth'     => ['user' => $entityInformations['alfrescoLogin'], 'password' => $entityInformations['alfrescoPassword']],
                'method'        => 'POST',
                'multipartBody' => $multipartBody
            ]);
            if ($curlResponse['code'] != 201) {
                return ['errors' => "Send attachment {$attachment['res_id']} failed : " . json_encode($curlResponse['response'])];
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
                return ['errors' => "Update attachment {$attachment['res_id']} failed : " . json_encode($curlResponse['response'])];
            }

            $attachmentsTitlesSent[] = $attachment['title'];

            $externalId = json_decode($attachment['external_id'], true);
            $externalId['alfrescoId'] = $attachmentId;
            AttachmentModel::update(['set' => ['external_id' => json_encode($externalId)], 'where' => ['res_id = ?'], 'data' => [$attachment['res_id']]]);
        }

        $message = empty($args['folderName']) ? " (envoyé au dossier {$args['folderId']})" : " (envoyé au dossier {$args['folderName']})";
        return ['history' => $message];
    }
}
