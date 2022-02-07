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

use Attachment\models\AttachmentModel;
use Attachment\models\AttachmentModelAbstract;
use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;
use Email\controllers\EmailController;
use Configuration\models\ConfigurationModel;

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
        // return ['sended' => 'JL FastParapheurSmtpController => sendDatas()', 'data' => 'JL data blabla', 'historyInfos' => ' JL history blabla'];
        return ['sended' => 'success'];
    }
}