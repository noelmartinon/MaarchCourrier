<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Message Exchange Review Controller
* @author dev@maarch.org
* @ingroup core
*/

namespace Sendmail\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Core\Models\ResModel;
use Core\Models\ActionsModel;

require_once __DIR__. '/../../export_seda/Controllers/ReceiveMessage.php';
require_once "core/class/class_history.php";
require_once "modules/sendmail/Controllers/SendMessageExchangeController.php";
require_once 'modules/export_seda/RequestSeda.php';

class MessageExchangeReviewController
{
    protected static function canSendMessageExchangeReview($aArgs = [])
    {
        if (empty($aArgs['res_id']) || !is_numeric($aArgs['res_id'])) {
            return false;
        }

        $resLetterboxData = ResModel::getById([
            'select'  => ['nature_id, reference_number', 'entity_label', 'res_id', 'identifier'],
            'table'   => 'res_view_letterbox',
            'resId'   => $aArgs['res_id'],
            'orderBy' => 'res_id']);

        if ($resLetterboxData[0]['nature_id'] == 'message_exchange' && substr($resLetterboxData[0]['reference_number'], 0, 16) == 'ArchiveTransfer_') {
            return $resLetterboxData[0];
        } else {
            return false;
        }
    }

    public static function sendMessageExchangeReview($aArgs = [])
    {
        $messageExchangeData = self::canSendMessageExchangeReview(['res_id' => $aArgs['res_id']]);
        if ($messageExchangeData) {
            $actionInfo = ActionsModel::getById(['id' => $aArgs['action_id']]);
            $reviewObject                           = new \stdClass();
            $reviewObject->Comment                  = ['['.date("d/m/Y H:i:s") . '] Action réalisée : '. $actionInfo['label_action'].'. Le service traitant est : '.$messageExchangeData['entity_label'].'.'];
            
            $date                                   = new \DateTime;
            $reviewObject->Date                     = $date->format(\DateTime::ATOM);
            
            $reviewObject->MessageIdentifier        = new \stdClass();
            $reviewObject->MessageIdentifier->value = $messageExchangeData['reference_number'].'_Review';
            
            $reviewObject->CodeListVersions         = new \stdClass();
            $reviewObject->CodeListVersions->value  = '';
            
            $reviewObject->UnitIdentifier           = new \stdClass();
            $reviewObject->UnitIdentifier->value    = $messageExchangeData['reference_number'];
            $RequestSeda                            = new \RequestSeda();
            $messageExchangeReply                   = $RequestSeda->getMessageByReference($messageExchangeData['reference_number'].'_ReplySent');
            $dataObject                             = json_decode($messageExchangeReply->data);
            $reviewObject->OriginatingAgency        = $dataObject->TransferringAgency;
            $reviewObject->ArchivalAgency           = $dataObject->ArchivalAgency;

            /***************** ENVOI SUIVI DEMANDE A L EMETTEUR VIA ALEXANDRE ****************/

            $service_url = $dataObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value.'/rest/saveMessageExchangeReview';
            $curl        = curl_init($service_url);
            $curl_post_data = array(
                    'data' => json_encode($reviewObject)
            );

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
            $curl_response = curl_exec($curl);
            curl_close($curl);
        }
    }

    public function saveMessageExchangeReview(RequestInterface $request, ResponseInterface $response)
    {
        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        $data = $request->getParams();

        // $tmpName = self::createFile(['base64' => $data['base64'], 'extension' => $data['extension'], 'size' => $data['size']]);
        // if(!empty($tmpName['errors'])){
        //     return $response->withStatus(400)->withJson($tmpName);
        // }
        
        /********** EXTRACTION DU ZIP ET CONTROLE PAR ALEXANDRE*******/

        $dataObject = json_decode($data['data']); //TODO : A REMPLACER PAR EXTRACTION
        $RequestSeda = new \RequestSeda();

        $dataObject->TransferringAgency = $dataObject->OriginatingAgency;

        $messageExchange = $RequestSeda->getMessageByReference($dataObject->UnitIdentifier->value);
        $messageId = \SendMessageExchangeController::saveMessageExchange(['dataObject' => $dataObject, 'res_id_master' => $messageExchange->res_id_master, 'type' => 'ArchiveTransferReview']);
        return $response->withJson([
            "messageId" => $messageId
        ]);
    }
}
