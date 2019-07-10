<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Message Exchange Review Controller
 *
 * @author dev@maarch.org
 * @ingroup core
 */

namespace Sendmail\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Resource\models\ResModel;
use Action\models\ActionModel;
use SrcCore\models\CoreConfigModel;

require_once __DIR__.'/../../export_seda/Controllers/ReceiveMessage.php';
require_once 'modules/export_seda/RequestSeda.php';
require_once 'modules/export_seda/Controllers/SendMessage.php';
require_once 'modules/sendmail/Controllers/SendMessageExchangeController.php';

class MessageExchangeReviewController
{
    protected static function canSendMessageExchangeReview($aArgs = [])
    {
        if (empty($aArgs['res_id']) || !is_numeric($aArgs['res_id'])) {
            return false;
        }

        $resLetterboxData = ResModel::getOnView([
            'select' => ['nature_id, reference_number', 'entity_label', 'res_id', 'identifier'],
            'where' => ['res_id = ?'],
            'data' => [$aArgs['res_id']],
            'orderBy' => ['res_id'], ]);

        if ($resLetterboxData[0]['nature_id'] == 'message_exchange' && substr($resLetterboxData[0]['reference_number'], 0, 16) == 'ArchiveTransfer_') {
            return $resLetterboxData[0];
        } else {
            return false;
        }
    }

    /*
    * Used in manage_action.php, so does not remove sessions
    */
    public static function sendMessageExchangeReview($aArgs = [])
    {
        $messageExchangeData = self::canSendMessageExchangeReview(['res_id' => $aArgs['res_id']]);
        if ($messageExchangeData) {
            $actionInfo = ActionModel::getById(['id' => $aArgs['action_id']]);
            $reviewObject = new \stdClass();
            $reviewObject->Comment = array();
            $reviewObject->Comment[0] = new \stdClass();
            $reviewObject->Comment[0]->value = '['.date('d/m/Y H:i:s').'] "'.$actionInfo['label_action'].'" '._M2M_ACTION_DONE.' '.$_SESSION['user']['entities'][0]['ENTITY_LABEL'].'. '._M2M_ENTITY_DESTINATION.' : '.$messageExchangeData['entity_label'];

            $date = new \DateTime();
            $reviewObject->Date = $date->format(\DateTime::ATOM);

            $reviewObject->MessageIdentifier = new \stdClass();
            $reviewObject->MessageIdentifier->value = $messageExchangeData['reference_number'].'_NotificationSent';

            $reviewObject->CodeListVersions = new \stdClass();
            $reviewObject->CodeListVersions->value = '';

            $reviewObject->UnitIdentifier = new \stdClass();
            $reviewObject->UnitIdentifier->value = $messageExchangeData['reference_number'];

            $RequestSeda = new \RequestSeda();
            $messageExchangeReply = $RequestSeda->getMessageByReference($messageExchangeData['reference_number'].'_ReplySent');
            $dataObject = json_decode($messageExchangeReply->data);
            $reviewObject->OriginatingAgency = $dataObject->TransferringAgency;
            $reviewObject->ArchivalAgency = $dataObject->ArchivalAgency;

            if ($reviewObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->Channel == 'url') {
                $tab = explode('saveMessageExchangeReturn', $reviewObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value);
                $reviewObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value = $tab[0].'saveMessageExchangeReview';
            }

            $sendMessage = new \SendMessage();

            $reviewObject->MessageIdentifier->value = $messageExchangeData['reference_number'].'_Notification';

            $tmpPath = CoreConfigModel::getTmpPath();
            $filePath = $sendMessage->generateMessageFile($reviewObject, 'ArchiveModificationNotification', $tmpPath);

            $reviewObject->MessageIdentifier->value = $messageExchangeData['reference_number'].'_NotificationSent';
            $reviewObject->TransferringAgency = $reviewObject->OriginatingAgency;
            $messageExchangeSaved = \SendMessageExchangeController::saveMessageExchange(['dataObject' => $reviewObject, 'res_id_master' => $aArgs['res_id_master'], 'type' => 'ArchiveModificationNotification', 'file_path' => $filePath]);

            $reviewObject->MessageIdentifier->value = $messageExchangeData['reference_number'].'_Notification';

            $reviewObject->DataObjectPackage = new \stdClass();
            $reviewObject->DataObjectPackage->DescriptiveMetadata = new \stdClass();
            $reviewObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit = array();
            $reviewObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0] = new \stdClass();
            $reviewObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content = new \stdClass();
            $reviewObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->OriginatingSystemId = $aArgs['res_id_master'];
            $reviewObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->Title[0] = '[CAPTUREM2M_NOTIFICATION]'.date('Ymd_his');

            $reviewObject->TransferringAgency->OrganizationDescriptiveMetadata = new \stdClass();
            $reviewObject->TransferringAgency->OrganizationDescriptiveMetadata->UserIdentifier = $_SESSION['user']['UserId'];
            $sendMessage->send($reviewObject, $messageExchangeSaved['messageId'], 'ArchiveModificationNotification');
        }
    }

    public function saveMessageExchangeReview(Request $request, Response $response)
    {
        if (empty($GLOBALS['userId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        $data = $request->getParams();

        if (!ReceiveMessageExchangeController::checkNeededParameters(['data' => $data, 'needed' => ['type']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $tmpName = ReceiveMessageExchangeController::createFile(['base64' => $data['base64'], 'extension' => $data['extension'], 'size' => $data['size']]);
        if (!empty($tmpName['errors'])) {
            return $response->withStatus(400)->withJson($tmpName);
        }

        $receiveMessage = new \ReceiveMessage();
        $tmpPath = CoreConfigModel::getTmpPath();
        $res = $receiveMessage->receive($tmpPath, $tmpName, $data['type']);

        $sDataObject = $res['content'];
        $dataObject = json_decode($sDataObject);
        $RequestSeda = new \RequestSeda();

        $dataObject->TransferringAgency = $dataObject->OriginatingAgency;

        $messageExchange = $RequestSeda->getMessageByReference($dataObject->UnitIdentifier->value);

        if (empty($messageExchange->operation_date)) {
            $RequestSeda->updateOperationDateMessage(['operation_date' => $dataObject->Date, 'message_id' => $messageExchange->message_id]);
        }

        $messageExchangeSaved = \SendMessageExchangeController::saveMessageExchange(['dataObject' => $dataObject, 'res_id_master' => $messageExchange->res_id_master, 'type' => 'ArchiveModificationNotification']);

        return $response->withJson([
            'messageId' => $messageExchangeSaved['messageId'],
        ]);
    }
}
