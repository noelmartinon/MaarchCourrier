<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Listinstance Controller
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Controllers;

use Core\Models\ListinstanceModel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class ListinstanceController
{
    public function getById(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $listinstance = ListinstanceModel::getById(['id' => $aArgs['id']]);

        return $response->withJson($listinstance);
    }
}
