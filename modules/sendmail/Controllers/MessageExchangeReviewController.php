<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Receive Message Exchange Controller
* @author dev@maarch.org
* @ingroup core
*/

namespace Sendmail\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Core\Models\ResModel;
use Core\Models\StatusModel;


require_once __DIR__. '/../../export_seda/Controllers/ReceiveMessage.php';
require_once "core/class/class_history.php";
require_once "modules/sendmail/Controllers/SendMessageExchangeController.php";
require_once 'modules/export_seda/RequestSeda.php';

class MessageExchangeReviewController
{

    private static $aComments = [];

    protected static function canSendMessageExchangeReview($aArgs = [])
    {

        if(!ReceiveMessageExchangeController::checkNeededParameters(['data' => $aArgs, 'needed' => ['res_id']])){
            return false;
        }

        $resLetterboxData = ResModelAbstract::getById([
            'select' => ['nature_id, reference_number', 'entity_label', 'status', 'res_id', 'identifier'],
            'table' => 'res_view_letterbox',
            'resId' => $aArgs['res_id']]);

        if ($resLetterboxData['nature_id'] == 'message_exchange' && substr($resLetterboxData['reference_number'], 0, 16) == 'ArchiveTransfer_') {
            return $resLetterboxData;
        } else {
            return false;
        }

    }

    protected function sendMessageExchangeReview($aArgs = [])
    {
        $messageExchangeData = self::canSendMessageExchangeReview(['res_id' => $aArgs['res_id']]);
        if ($messageExchangeData) {

            $statusInfo = StatusModel::getById(['id' => $messageExchangeData['status']]);

            $reviewObject                           = new \stdClass();
            $reviewObject->Comment                  = '['.date("d/m/Y H:i:s") . '] Le courrier est passé au statut : '. $statusInfo[0]['label'].'. Le service traitant est : '.$messageExchangeData['entity_label'].'.';
            
            $date                                   = new \DateTime;
            $reviewObject->Date                     = $date->format(\DateTime::ATOM);
            
            $reviewObject->MessageIdentifier        = new \stdClass();
            $reviewObject->MessageIdentifier->value = $messageExchangeData['reference_number'];
            
            $reviewObject->CodeListVersions         = new \stdClass();
            $reviewObject->CodeListVersions->value  = '';
            
            $reviewObject->UnitIdentifier           = new \stdClass();
            $reviewObject->UnitIdentifier->value    = $messageExchangeData['identifier'];
            
            $RequestSeda                            = new \RequestSeda();
            $messageExchangeReply                   = $RequestSeda->getMessageByReference($resLetterboxData[0]['reference_number']);
            $dataObject                             = json_decode($messageExchangeReply->data);
            
            $reviewObject->TransferringAgency       = $dataObject->ArchivalAgency;
            $reviewObject->ArchivalAgency           = $dataObject->TransferringAgency;

            /***************** ENVOI SUIVI DEMANDE A L EMETTEUR VIA ALEXANDRE ****************/

            $service_url = 'http://bblier:maarch@192.168.1.194/maarch_v2/rest/saveMessageExchangeReview';
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

        $tmpName = self::createFile(['base64' => $data['base64'], 'extension' => $data['extension'], 'size' => $data['size']]);
        if(!empty($tmpName['errors'])){
            return $response->withStatus(400)->withJson($tmpName);
        }
    }

}
