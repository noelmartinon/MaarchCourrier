<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   Outlook Controller
 * @author  dev@maarch.org
 */

namespace Outlook\controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\LanguageController;
use SrcCore\models\CoreConfigModel;

class OutlookController
{
    public function generateManifest(Request $request, Response $response)
    {
        $config = CoreConfigModel::getJsonLoaded(['path' => 'apps/maarch_entreprise/xml/config.json']);
        $appName = $config['config']['applicationName'];
        $maarchUrl = $config['config']['maarchUrl'];
//        $maarchUrl = str_replace('//', '/', $maarchUrl);

        if (strpos($maarchUrl, 'https://') === false) {
            return $response->withStatus(400)->withJson(['errors' => 'You cannot use the Outlook plugin because maarchUrl is not using https', 'lang' => 'addinOutlookUnavailable']);
        }

        $path = CoreConfigModel::getConfigPath();
        $hashedPath = md5($path);

        $uuid = substr_replace($hashedPath, '-', 8, 0);
        $uuid = substr_replace($uuid, '-', 13, 0);
        $uuid = substr_replace($uuid, '-', 18, 0);
        $uuid = substr_replace($uuid, '-', 23, 0);

        $appDomain = str_replace(CoreConfigModel::getCustomId(), '', $maarchUrl);

        $data = [
            'config.applicationName' => $appName,
            'config.instanceUrl'     => $maarchUrl,
            'config.applicationUrl'  => $appDomain,
            'config.applicationUuid' => $uuid
        ];

        $language = LanguageController::getLanguage(['language' => $config['config']['lang']]);
        foreach ($language['lang'] as $key => $lang) {
            $data['lang.' . $key] = $lang;
        }

        $manifestTemplate = file_get_contents('plugins/addin-outlook/src/config/manifest.xml.default');

        $newContent = $manifestTemplate;
        foreach ($data as $key => $value) {
            $newContent = str_replace('{' . $key . '}', $value, $newContent);
        }

        $response->write($newContent);
        $response = $response->withAddedHeader('Content-Disposition', 'attachment; filename="manifest.xml"');
        return $response->withHeader('Content-Type', 'application/xml');
    }
}
