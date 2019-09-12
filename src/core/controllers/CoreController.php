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

use Basket\models\GroupBasketModel;
use Group\controllers\ServiceController;
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
        $aInit['applicationMinorVersion'] = CoreConfigModel::getApplicationVersion()['applicationMinorVersion'];
        $aInit['lang'] = CoreConfigModel::getLanguage();
        $aInit['user'] = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id', 'user_id', 'firstname', 'lastname']]);
        $aInit['customLanguage'] = CoreConfigModel::getCustomLanguage(['lang' => $aInit['lang']]);

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

        for ($i = 0; $i < count($scriptsToInject); $i++) {
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

        return $response->withJson($aInit);
    }

    public function getHeader(Request $request, Response $response)
    {
        $user = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id', 'user_id', 'firstname', 'lastname']]);
        $user['groups'] = UserModel::getGroupsByUserId(['userId' => $GLOBALS['userId']]);
        $user['entities'] = UserModel::getEntitiesById(['userId' => $GLOBALS['userId']]);
        $user['indexingGroups'] = [];

        if ($GLOBALS['userId'] == 'superadmin') {
            $menu = ServiceModel::getApplicationServicesByXML(['type' => 'menu']);
            foreach ($menu as $key => $value) {
                if ($value['id'] == 'index_mlb' && $GLOBALS['userId'] == 'superadmin') {
                    unset($menu[$key]);
                }
            }
            $menuModules = ServiceModel::getModulesServicesByXML(['type' => 'menu']);
            $menu = array_merge($menu, $menuModules);
        } else {
            $menu = ServiceController::getMenuServicesByUserId(['userId' => $GLOBALS['userId']]);
            foreach ($menu as $value) {
                if ($value['id'] == 'index_mlb') {
                    foreach ($user['groups'] as $group) {
                        if (GroupBasketModel::hasBasketByGroupId(['groupId' => $group['group_id'], 'basketId' => 'IndexingBasket'])) {
                            $user['indexingGroups'][] = ['groupId' => $group['group_id'], 'label' => $group['group_desc']];
                        }
                    }
                }
            }
        }

        return $response->withJson([
            'user'  => $user,
            'menu'  => $menu
        ]);
    }

    public static function getAdministration(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if ($GLOBALS['userId'] == 'superadmin') {
            $administration                    = [];
            $administrationApplication         = ServiceModel::getApplicationServicesByXML(['type' => 'admin']);
            $administrationModule              = ServiceModel::getModulesServicesByXML(['type' => 'admin']);
            $administration['administrations'] = array_merge_recursive($administrationApplication, $administrationModule);
        } else {
            $administration = ServiceModel::getAdministrationServicesByUserId(['userId' => $GLOBALS['userId']]);
        }

        return $response->withJson($administration);
    }
}
