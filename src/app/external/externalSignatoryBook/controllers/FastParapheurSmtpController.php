<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief fastParapheur via SMTP Controller
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Attachment\models\AttachmentModel;
use SrcCore\models\ValidatorModel;
use Email\controllers\EmailController;
use Configuration\models\ConfigurationModel;
use SrcCore\models\CoreConfigModel;

/**
    * @codeCoverageIgnore
*/
class FastParapheurSmtpController 
{
    public static function sendDatas($args)
    {
        ValidatorModel::notEmpty($args, ['config', 'resIdMaster']);
        ValidatorModel::intVal($args, ['resIdMaster']);

        // get config
        $config = $args['config'];
        $smtpConfig = ConfigurationModel::getByPrivilege(['privilege' => 'admin_email_server', 'select' => ['value']]);

        if (empty($smtpConfig)) {
            return ['historyInfos' => ' Mail server settings are missing!'];
        }
        $smtpConfig = json_decode($smtpConfig['value'], true);

        // get attachments
        $attachments = AttachmentModel::get([
            'select'    => ['res_id as id', '(select false) as original'],
            'where'     => ['res_id_master = ?'],
            'data'      => [$args['resIdMaster']]
        ]);
        
        // create email
        $emailId = EmailController::createEmail([
            'userId'    => $GLOBALS['id'],
            'data'      => [
                'sender'        => ['email' => $smtpConfig['from']],
                'recipients'    => [$config['data']['email']],
                'object'        => $config['data']['subject'],
                'body'          => empty($args['note']) && empty($args['note']['content']) ? '' : 'Annotation du documment ' . $args['note']['content'],
                'document'      => ['id' => $args['resIdMaster'], 'isLinked' => true, 'original' => true, 'attachments' => $attachments],
                'isHtml'        => true,
                'status'        => 'EXPRESS'
            ]
        ]);

        if (!empty($emailId['errors'])) {
            return ['error' => $emailId['errors']];
        }
        return ['sended' => 'success'];
    }

    public static function createDocument(Request $request, Response $response) {

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        $config = [];

        if (empty($loadedXml)) {
            return $response->withStatus(404)->withJson(['Remote Signatory Books configuration is missing!']);
        }

        $config['id'] = (string)$loadedXml->signatoryBookEnabled;
        foreach ($loadedXml->signatoryBook as $value) {
            if ($value->id == $config['id']) {
                $config['data'] = (array)$value;
                break;
            }
        }

        $body = $request->getParsedBody();

        if (empty($body['status']) || empty($body['status']['type'])) {
            return $response->withStatus(404)->withJson(['errors' => 'Status type is missing!']);
        }

        if ($body['status']['type'] == $config['data']['errorState']) {
            
        }
        else if ($body['status']['type'] == $config['data']['refusedState']) {
            
        }
        else if ($body['status']['type'] == $config['data']['signedState']) {
            
        }
        else {
            return $response->withStatus(400)->withJson(['The query syntax is incorrect.']);
        }

        return $response->withStatus(201)->withJson(['Document created']);
    }
}