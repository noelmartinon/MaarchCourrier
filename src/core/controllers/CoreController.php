<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Core Controller
 *
 * @author dev@maarch.org
 */

namespace SrcCore\controllers;

use Resource\controllers\StoreController;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class CoreController
{
    public function getHeader(Request $request, Response $response)
    {
        $user = UserModel::getById(['id' => $GLOBALS['id'], 'select' => ['id', 'user_id', 'firstname', 'lastname']]);
        $user['groups'] = UserModel::getGroupsByLogin(['login' => $GLOBALS['login']]);
        $user['entities'] = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['entities.id', 'users_entities.entity_id', 'entities.entity_label', 'users_entities.user_role', 'users_entities.primary_entity']]);

        return $response->withJson(['user' => $user]);
    }

    public static function setGlobals(array $args)
    {
        ValidatorModel::notEmpty($args, ['userId']);
        ValidatorModel::intVal($args, ['userId']);

        $user = UserModel::getById(['id' => $args['userId'], 'select' => ['user_id']]);
        $GLOBALS['login'] = $user['user_id'];
        $GLOBALS['id'] = $args['userId'];
    }

    public function externalConnectionsEnabled(Request $request, Response $response)
    {
        $connections = [];
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        if (!empty($loadedXml->signatoryBookEnabled)) {
            $connections[(string)$loadedXml->signatoryBookEnabled] = true;
        }
        $mailevaConfig = CoreConfigModel::getMailevaConfiguration();
        if ($mailevaConfig['enabled']) {
            $connections['maileva'] = true;
        }

        return $response->withJson(['connection' => $connections]);
    }

    public function getImages(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $customId = CoreConfigModel::getCustomId();

        $assetPath = 'dist/assets';

        if ($queryParams['image'] == 'loginPage') {
            $filename = 'bodylogin.jpg';
            if (!empty($customId) && is_file("custom/{$customId}/img/{$filename}")) {
                $path = "custom/{$customId}/{$filename}";
            } else {
                $path = "{$assetPath}/{$filename}";
            }
        } elseif ($queryParams['image'] == 'logo') {
            $filename = 'logo.svg';
            if (!empty($customId) && is_file("custom/{$customId}/img/{$filename}")) {
                $path = "custom/{$customId}/{$path}";
            } else {
                $path = "{$assetPath}/{$filename}";
            }
        } elseif ($queryParams['image'] == 'onlyLogo') {
            $filename = 'logo_only.svg';
            if (!empty($customId) && is_file("custom/{$customId}/img/{$filename}")) {
                $path = "custom/{$customId}/{$path}";
            } else {
                $path = "{$assetPath}/{$filename}";
            }
        } else {
            return $response->withStatus(404)->withJson(['errors' => 'QueryParams image is empty or not valid']);
        }

        $fileContent = file_get_contents($path);
        if ($fileContent === false) {
            return $response->withStatus(400)->withJson(['errors' => 'Image not found']);
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $pathInfo = pathinfo($path);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "inline; filename=maarch.{$pathInfo['extension']}");

        return $response->withHeader('Content-Type', $mimeType);
    }

    public static function getMaximumAllowedSizeFromPhpIni()
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $uploadMaxFilesize = StoreController::getBytesSizeFromPhpIni(['size' => $uploadMaxFilesize]);
        $postMaxSize = ini_get('post_max_size');
        $postMaxSize = $postMaxSize == 0 ? $uploadMaxFilesize : StoreController::getBytesSizeFromPhpIni(['size' => $postMaxSize]);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimit = $memoryLimit < 1 ? $uploadMaxFilesize : StoreController::getBytesSizeFromPhpIni(['size' => $memoryLimit]);

        $maximumSize = min($uploadMaxFilesize, $postMaxSize, $memoryLimit);

        return $maximumSize;
    }
}
