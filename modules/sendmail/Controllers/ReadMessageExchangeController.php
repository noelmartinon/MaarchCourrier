<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Message Exchange Controller
* @author dev@maarch.org
* @ingroup core
*/

require_once 'apps/maarch_entreprise/Models/ContactsModel.php';
require_once 'apps/maarch_entreprise/Models/ResModel.php';
require_once 'modules/export_seda/RequestSeda.php';

class SendMessageExchangeController
{
    public static function getMessageExchange($aArgs = [])
    {
        $errors = self::control($aArgs);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $RequestSeda         = new RequestSeda();
        $messageExchangeData = $RequestSeda->getMessageByIdentifier($aArgs['identifier']);
        $unitIdentifierData  = $RequestSeda->getUnitIdentifierByMessageId($aArgs['identifier']);
        var_dump($messageExchangeData);exit;
        // $AttachmentsInfo = [];
        // if (!empty($aArgs['join_attachment'])) {
        //     $AttachmentsInfo = \Attachments\Models\AttachmentsModel::getAttachmentsWithOptions(['where' => ['res_id in (?)'], 'data' => [$aArgs['join_attachment']]]);
        // }

        return true;
    }

    protected function control($aArgs = [])
    {
        $errors = [];

        if (empty($aArgs['identifier']) || !is_numeric($aArgs['identifier'])) {
            array_push($errors, 'wrong format for identifier');
        }

        return $errors;
    }

}
