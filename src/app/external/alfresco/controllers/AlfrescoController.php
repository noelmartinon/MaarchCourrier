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

use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\PasswordModel;
use User\models\UserModel;

class AlfrescoController
{
    public function get(Request $request, Response $response)
    {
        $enabledSignatureBook = null;


        $id = 'a66121dd-57eb-48de-990a-e770341cdbb7';
//        $id = 'bded6ba4-a085-4649-aef1-73fe5dca7819';
        $id = '8944c9fb-2cee-41d0-89e0-5de36456ed3a';


        $body = [
            'name' => 'my file pdf',
            'nodeType'  => 'cm:content'
        ];
        $multipartBody = [
            'filedata' => ['isFile' => true, 'filename' => 'mon test4', 'content' => file_get_contents('install/samples/res_letterbox/empty.pdf')],
        ];
        $curlResponse = CurlModel::execSimple([
            'url'           => "https://bluecourrier-alfresco-demo.atolcd.com/alfresco/api/-default-/public/alfresco/versions/1/nodes/{$id}/children",
            'basicAuth'     => [],
            'method'        => 'POST',
            'multipartBody' => $multipartBody
        ]);

        $createdId = $curlResponse['response']['entry']['id'];

        $body = [
            'properties' => [
                'cm:title' => 'un titre',
                'cm:description' => 'une description',
            ],
        ];
        $curlResponse = CurlModel::execSimple([
            'url'           => "https://bluecourrier-alfresco-demo.atolcd.com/alfresco/api/-default-/public/alfresco/versions/1/nodes/{$createdId}",
            'basicAuth'     => [],
            'headers'       => ['content-type:application/json', 'Accept: application/json'],
            'method'        => 'PUT',
            'body'          => json_encode($body)
        ]);

        return $response->withJson(['response' => $curlResponse['response']]);
    }

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
                $folders[] = ['id' => $value['entry']['id'], 'name' => $value['entry']['name']];
            }
        }

        return $response->withJson(['folders' => $folders]);
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
                $folders[] = ['id' => $value['entry']['id'], 'name' => $value['entry']['name']];
            }
        }

        return $response->withJson(['folders' => $folders]);
    }

    public function getFolders(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        if (!Validator::stringType()->notEmpty()->validate($queryParams['search'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params search is empty']);
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
                $folders[] = ['id' => $value['entry']['id'], 'name' => $value['entry']['name']];
            }
        }

        return $response->withJson(['folders' => $folders]);
    }
}
