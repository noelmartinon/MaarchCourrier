<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Core Controller
 * @author dev@maarch.org
 * @ingroup core
 */

namespace Core\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

include_once 'core/class/class_portal.php';

class CoreController
{
    public function initialize(RequestInterface $request, ResponseInterface $response)
    {
        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        $data = $request->getParams();

        $aInit = [];
        $aInit['coreUrl'] = str_replace('rest/', '', \Url::coreurl());
        $aInit['applicationName'] = $_SESSION['config']['applicationname']; //Todo No Session

        if (!empty($data['views'])) {
            foreach ($data['views'] as $view) {
                $aInit[$view . 'View'] = 'Views/' . $view . '.component.html';
                if(file_exists("{$_SESSION['config']['corepath']}custom/{$_SESSION['custom_override_id']}/apps/maarch_entreprise/Views/{$view}.component.html")) {
                    $aInit[$view . 'View'] = "../../custom/{$_SESSION['custom_override_id']}/apps/maarch_entreprise/Views/{$view}.component.html";
                }
            }
        }

        return $response->withJson($aInit);
    }
}
