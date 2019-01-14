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
 * @ingroup Core
 */

namespace SrcCore\controllers;

use Group\models\ServiceModel;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use User\models\UserModel;

require_once 'core/class/Url.php';

class CoreController
{
    public function initialize(Request $request, Response $response)
    {
        $aInit = [];
        $aInit['coreUrl'] = str_replace('rest/', '', \Url::coreurl());
        $aInit['applicationName'] = CoreConfigModel::getApplicationName();
        $aInit['lang'] = CoreConfigModel::getLanguage();
        $aInit['user'] = UserModel::getByUserId(['userId' => $GLOBALS['userId'], 'select' => ['id', 'user_id', 'firstname', 'lastname']]);
        $aInit['user']['groups'] = UserModel::getGroupsByUserId(['userId' => $GLOBALS['userId']]);
        $aInit['user']['entities'] = UserModel::getEntitiesById(['userId' => $GLOBALS['userId']]);

        $aInit['scriptsToinject'] = [];
        $aInit['scriptsInjected'] = [];

        $scriptsToInject = scandir('dist');
        foreach ($scriptsToInject as $value) {
            if (strstr($value, 'inline.') !== false || strstr($value, 'main.') !== false || strstr($value, 'vendor.') !== false || strstr($value, 'scripts.') !== false) {
                if (strstr($value, '.js.map') === false) {
                    $scriptName          = explode(".", $value);
                    $modificationDate    = filemtime(realpath("dist/" . $value));
                    $idArrayTime         = $scriptName[0] . "." . pathinfo($value, PATHINFO_EXTENSION);

                    if (!isset($aInit['scriptsInjected'][$idArrayTime]) || $modificationDate > $aInit['scriptsInjected'][$idArrayTime][0]) {
                        if (isset($aInit['scriptsInjected'][$idArrayTime])) {
                            array_pop($aInit['scriptsToinject']);
                        }
                        $aInit['scriptsInjected'][$idArrayTime][0] = filemtime(realpath("dist/" . $value));
                        $aInit['scriptsInjected'][$idArrayTime][1] = $value;

                        $aInit['scriptsToinject'][] = $value;
                    }
                }
            }
        }

        if (!empty($aInit['scriptsToinject'][3]) && strstr($aInit['scriptsToinject'][3], 'vendor.') !== false) {
            $tmp = $aInit['scriptsToinject'][1];
            $aInit['scriptsToinject'][1] = $aInit['scriptsToinject'][2];
            $aInit['scriptsToinject'][2] = $tmp;
        }

        return $response->withJson($aInit);
    }

    public static function getAdministration(Request $request, Response $response)
    {
        if ($GLOBALS['userId'] == 'superadmin') {
            $administration                    = [];
            $administrationMenu                = ServiceModel::getApplicationAdministrationMenuByXML();
            $administrationApplication         = ServiceModel::getApplicationAdministrationServicesByXML();
            $administrationModule              = ServiceModel::getModulesAdministrationServicesByXML();
            $administration['administrations'] = array_merge_recursive($administrationApplication, $administrationModule);
            $administration                    = array_merge_recursive($administration, $administrationMenu);
        } else {
            $administration = ServiceModel::getAdministrationServicesByUserId(['userId' => $GLOBALS['userId']]);
        }

        return $response->withJson($administration);
    }
}
