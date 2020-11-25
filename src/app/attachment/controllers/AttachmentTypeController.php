<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Attachment Type Controller
* @author dev@maarch.org
*/

namespace Attachment\controllers;

use Attachment\models\AttachmentTypeModel;
use Group\controllers\PrivilegeController;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;

class AttachmentTypeController
{
    public function get(Request $request, Response $response)
    {
        $attachmentsTypes = AttachmentTypeModel::get(['select' => ['*']]);

        return $response->withJson(['attachmentsTypes' => $attachmentsTypes]);
    }

    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_attachments', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (empty($body)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body is not set or empty']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['typeId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body typeId is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        }

        $id = AttachmentTypeModel::create([
            'type_id'               => $body['type_id'],
            'label'                 => $body['label'],
            'visible'               => empty($body['visible']) ? 'false' : 'true',
            'email_link'            => empty($body['emailLink']) ? 'false' : 'true',
            'signable'              => empty($body['signable']) ? 'false' : 'true',
            'icon'                  => $body['icon'] ?? null,
            'version_enabled'       => empty($body['versionEnabled']) ? 'false' : 'true',
            'new_version_default'   => empty($body['newVersionDefault']) ? 'false' : 'true'
        ]);

        return $response->withJson(['id' => $id]);
    }

    public function update(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_attachments', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (empty($body)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body is not set or empty']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        }

        $attachmentType = AttachmentTypeModel::getById(['select' => 1, 'id' => $args['id']]);
        if (empty($attachmentType)) {
            return $response->withStatus(400)->withJson(['errors' => 'Attachment type does not exist']);
        }

        $set = ['label' => $body['label']];
        if (isset($body['visible'])) {
            $set['visible'] = empty($body['visible']) ? 'false' : 'true';
        }
        if (isset($body['emailLink'])) {
            $set['email_link'] = empty($body['emailLink']) ? 'false' : 'true';
        }
        if (isset($body['signable'])) {
            $set['signable'] = empty($body['signable']) ? 'false' : 'true';
        }
        if (isset($body['versionEnabled'])) {
            $set['version_enabled'] = empty($body['versionEnabled']) ? 'false' : 'true';
        }
        if (isset($body['newVersionDefault'])) {
            $set['new_version_default'] = empty($body['newVersionDefault']) ? 'false' : 'true';
        }
        if (isset($body['icon'])) {
            $set['icon'] = $body['icon'];
        }

        AttachmentTypeModel::update([
            'set'       => $set,
            'where'     => ['id = ?'],
            'data'      => [$args['id']],
        ]);

        return $response->withStatus(204);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_attachments', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        //TODO que faire quand on supprime
        $attachmentType = AttachmentTypeModel::getById(['select' => 1, 'id' => $args['id']]);
        if (empty($attachmentType)) {
            return $response->withStatus(400)->withJson(['errors' => 'Attachment type does not exist']);
        }

        AttachmentTypeModel::delete([
            'where'     => ['id = ?'],
            'data'      => [$args['id']],
        ]);

        return $response->withStatus(204);
    }
}
