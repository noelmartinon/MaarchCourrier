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
    public function initialize(Request $request, Response $response)
    {
        $aInit = [];
        $aInit['coreUrl']            = str_replace('rest/', '', UrlController::getCoreUrl());
        $aInit['applicationName']    = CoreConfigModel::getApplicationName();
        $aInit['applicationVersion'] = CoreConfigModel::getApplicationVersion();
        $aInit['lang']               = CoreConfigModel::getLanguage();
        if (!empty($GLOBALS['userId'])) {
            $aInit['user']               = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id', 'user_id', 'firstname', 'lastname']]);
        }
        $aInit['customLanguage']     = CoreConfigModel::getCustomLanguage(['lang' => $aInit['lang']]);

        $aInit['scriptsToinject'] = [];
        $aInit['scriptsInjected'] = [];

        $scriptsToInject = [];
        $scripts = scandir('dist');
        foreach ($scripts as $value) {
            if (strstr($value, 'runtime.') !== false || strstr($value, 'main.') !== false || strstr($value, 'vendor.') !== false || strstr($value, 'scripts.') !== false) {
                if (strstr($value, '.js.map') === false) {
                    $scriptName          = explode(".", $value);
                    $modificationDate    = filemtime(realpath("dist/" . $value));
                    $idArrayTime         = $scriptName[0] . "." . pathinfo($value, PATHINFO_EXTENSION);

                    if (!isset($aInit['scriptsInjected'][$idArrayTime]) || $modificationDate > $aInit['scriptsInjected'][$idArrayTime][0]) {
                        if (isset($aInit['scriptsInjected'][$idArrayTime])) {
                            array_pop($scriptsToInject);
                        }
                        $aInit['scriptsInjected'][$idArrayTime][0] = filemtime(realpath("dist/" . $value));
                        $aInit['scriptsInjected'][$idArrayTime][1] = $value;

                        $scriptsToInject[] = $value;
                    }
                }
            }
        }
        unset($aInit['scriptsInjected']);

        $nbScriptsToInject = count($scriptsToInject);
        for ($i = 0; $i < $nbScriptsToInject; $i++) {
            foreach ($scriptsToInject as $value) {
                if ($i == 0 && strstr($value, 'scripts.') !== false) {
                    $aInit['scriptsToinject'][] = $value;
                } elseif ($i == 1 && strstr($value, 'main.') !== false) {
                    $aInit['scriptsToinject'][] = $value;
                } elseif ($i == 2 && strstr($value, 'runtime.') !== false) {
                    $aInit['scriptsToinject'][] = $value;
                } elseif ($i == 3 && strstr($value, 'vendor.') !== false) {
                    $aInit['scriptsToinject'][] = $value;
                }
            }
        }

        $control = \VersionUpdate\controllers\VersionUpdateController::executeSQLAtConnection();
        if (!empty($control['errors'])) {
            $aInit['sqlUpdateErrors'] = $control['errors'];
        }

        return $response->withJson($aInit);
    }

    public function getHeader(Request $request, Response $response)
    {
        $user = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id', 'user_id', 'firstname', 'lastname']]);
        $user['groups'] = UserModel::getGroupsByLogin(['login' => $GLOBALS['userId']]);
        $user['entities'] = UserModel::getEntitiesByLogin(['login' => $GLOBALS['userId']]);

        return $response->withJson([
            'user'      => $user
        ]);
    }

    public static function setGlobals(array $args)
    {
        ValidatorModel::notEmpty($args, ['login']);
        ValidatorModel::stringType($args, ['login']);

        $user = UserModel::getByLogin(['login' => $args['login'], 'select' => ['id']]);
        $GLOBALS['userId'] = $args['login'];
        $GLOBALS['id'] = $user['id'];
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
