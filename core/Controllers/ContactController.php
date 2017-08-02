<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Contact Controller
 * @author dev@maarch.org
 * @ingroup core
 */

namespace Core\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Core\Models\ContactModel;

include_once 'core/class/docservers_controler.php';
include_once 'modules/basket/class/class_modules_tools.php';

class ContactController
{
    public function getCheckCommunication(RequestInterface $request, ResponseInterface $response, $aArgs) {
        $data = $request->getParams();

        if (isset($data['contactId'])) {
            $contactId = $data['contactId'];
            $obj = ContactModel::getCommunicationByContactId([
                'contactId' => $contactId
            ]);
        } else {
            return $response
                ->withStatus(500)
                ->withJson(['errors' => _ID . ' ' . _IS_EMPTY]);
        }

        $data = [
            $obj,
        ];

        return $response->withJson($data);
    }
}