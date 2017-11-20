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
use Respect\Validation\Validator;

require_once 'apps/maarch_entreprise/Models/ContactsModel.php';

class ContactController
{
    public function create(RequestInterface $request, ResponseInterface $response)
    {
        $data = $request->getParams();

        $check = Validator::notEmpty()->validate($data['firstname']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['lastname']);
        $check = $check && Validator::intVal()->notEmpty()->validate($data['contactType']);
        $check = $check && Validator::intVal()->notEmpty()->validate($data['contactPurposeId']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['isCorporatePerson']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['email']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if (empty($data['userId'])) {
            $data['userId'] = 'superadmin';
        }
        if (empty($data['entityId'])) {
            $data['entityId'] = 'SUPERADMIN';
        }
        if ($data['isCorporatePerson'] != 'Y') {
            $data['isCorporatePerson'] = 'N';
        } else {
            $data['addressFirstname'] = $data['firstname'];
            $data['addressLastname'] = $data['lastname'];
            unset($data['firstname'], $data['lastname']);
        }
        if (empty($data['isPrivate'])) {
            $data['isPrivate'] = 'N';
        } elseif ($data['isPrivate'] != 'N') {
            $data['isPrivate'] = 'Y';
        }

        $contactId = \ContactsModel::create($data);

        $data['contactId'] = $contactId;
        $addressId = \ContactsModel::createAddress($data);

        if (empty($contactId) || empty($addressId)) {
            return $response->withStatus(500)->withJson(['errors' => '[ContactController create] Contact creation has failed']);
        }

        return $response->withJson(['contactId' => $contactId, 'addressId' => $addressId]);
    }
}
