<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief fastParapheur Controller
 * @author nathan.cheval@edissyum.com
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\controllers;

use Attachment\models\AttachmentModel;
use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Resource\models\ResModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\DatabaseModel;

/**
    * @codeCoverageIgnore
*/
class FastParapheurController
{
    public static function retrieveSignedMails($aArgs)
    {
        foreach (['noVersion', 'resLetterbox'] as $version) {
            foreach ($aArgs['idsToRetrieve'][$version] as $resId => $value) {
                $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sei="http://sei.ws.fast.cdc.com/">
                    <soapenv:Header/>
                        <soapenv:Body>
                            <sei:history>
                                <documentId>' .  $value->external_id . '</documentId>
                            </sei:history>
                        </soapenv:Body>
                </soapenv:Envelope>';

                $curlReturn = CurlModel::execSOAP([
                    'xmlPostString' => $xmlPostString,
                    'url'           => $aArgs['config']['data']['url'],
                    'options'       => [
                        CURLOPT_SSLCERT         => $aArgs['config']['data']['certPath'],
                        CURLOPT_SSLCERTPASSWD   => $aArgs['config']['data']['certPass'],
                        CURLOPT_SSLCERTTYPE     => $aArgs['config']['data']['certType']
                    ]
                ]);

                if ($curlReturn['infos']['http_code'] == 404) {
                    return ['error' => 'Erreur 404 : ' . $curlReturn['raw']];
                }

                $isError = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
                if (!empty($isError ->Fault[0]) && !empty($value->res_id_master)) {
                    echo 'PJ n° ' . $resId . ' et document original n° ' . $value->res_id_master . ' : ' . (string)$curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0]->children()->faultstring . PHP_EOL;
                    continue;
                } elseif (!empty($isError ->Fault[0])) {
                    echo 'Document principal n° ' . $resId . ' : ' . (string)$curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0]->children()->faultstring . PHP_EOL;
                    continue;
                }

                $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->historyResponse->children();

                foreach ($response->return as $res) {    // Loop on all steps of the documents (prepared, send to signature, signed etc...)
                    $state      = (string) $res->stateName;
                    if ($state == $aArgs['config']['data']['validatedState']) {
                        $response = FastParapheurController::download(['config' => $aArgs['config'], 'documentId' => $value->external_id]);
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'validated';
                        $aArgs['idsToRetrieve'][$version][$resId]->format = 'pdf';
                        $aArgs['idsToRetrieve'][$version][$resId]->encodedFile = $response['b64FileContent'];
                        break;
                    } elseif ($state == $aArgs['config']['data']['refusedState']) {
                        $res = DatabaseModel::select([
                            'select'    => ['firstname', 'lastname'],
                            'table'     => ['listinstance', 'users'],
                            'left_join' => ['listinstance.item_id = users.user_id'],
                            'where'     => ['res_id = ?', 'item_mode = ?'],
                            'data'      => [$aArgs['idsToRetrieve'][$version][$resId]->res_id_master, 'sign']
                        ])[0];

                        $response = FastParapheurController::getRefusalMessage(['config' => $aArgs['config'], 'documentId' => $value->external_id]);
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'refused';
                        $aArgs['idsToRetrieve'][$version][$resId]->noteContent = $res['lastname'] . ' ' . $res['firstname'] . ' : ' . $response;
                        break;
                    } else {
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'waiting';
                    }
                }
            }
        }
        return $aArgs['idsToRetrieve'];
    }

    public static function upload($aArgs)
    {
        $circuitId    = $aArgs['circuitId'];
        $label        = $aArgs['label'];
        $subscriberId = $aArgs['businessId'];

        // Retrieve the annexes of the attachemnt to sign (other attachment and the original document)
        $annexes = [];
        $annexes['letterbox'] = ResModel::get([
            'select' => ['res_id', 'path', 'filename', 'docserver_id', 'format', 'category_id', 'external_id', 'integrations'],
            'where'  => ['res_id = ?'],
            'data'   => [$aArgs['resIdMaster']]
        ]);

        if (!empty($annexes['letterbox'][0]['docserver_id'])) {
            $letterboxPath = DocserverModel::getByDocserverId(['docserverId' => $annexes['letterbox'][0]['docserver_id'], 'select' => ['path_template']]);
            $annexes['letterbox'][0]['filePath'] = $letterboxPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $annexes['letterbox'][0]['path']) . $annexes['letterbox'][0]['filename'];
        }

        $attachments = AttachmentModel::get([
            'select'    => [
                'res_id', 'docserver_id', 'path', 'filename', 'format', 'attachment_type'
            ],
            'where'     => ["res_id_master = ?", "attachment_type not in (?)", "status not in ('DEL', 'OBS', 'FRZ', 'TMP', 'SEND_MASS')", "in_signature_book = 'true'"],
            'data'      => [$aArgs['resIdMaster'], ['signed_response']]
        ]);

        $attachmentTypes = AttachmentModel::getAttachmentsTypesByXML();
        foreach ($attachments as $key => $value) {
            if (!$attachmentTypes[$value['attachment_type']]['sign']) {
                $annexeAttachmentPath = DocserverModel::getByDocserverId(['docserverId' => $value['docserver_id'], 'select' => ['path_template']]);
                $value['filePath']    = $annexeAttachmentPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $value['path']) . $value['filename'];
                unset($attachments[$key]);
                $annexes['attachments'][] = $value;
            }
        }
        // END annexes

        $attachmentToFreeze = [];
        foreach ($attachments as $attachment) {
            $resId  = $attachment['res_id'];
            $collId = 'attachments_coll';
            
            $curlReturn = FastParapheurController::uploadFile([
                'resId'        => $resId,
                'collId'       => $collId,
                'resIdMaster'  => $aArgs['resIdMaster'],
                'annexes'      => $annexes,
                'circuitId'    => $circuitId,
                'label'        => $label,
                'subscriberId' => $subscriberId,
                'config'       => $aArgs['config']
            ]);

            if ($curlReturn['infos']['http_code'] == 404) {
                return ['error' => 'Erreur 404 : ' . $curlReturn['raw']];
            } elseif (!empty($curlReturn['error'])) {
                return ['error' => $curlReturn['error']];
            } elseif (!empty($curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0])) {
                $error = (string)$curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0]->children()->faultstring . PHP_EOL;
                return ['error' => $error];
            } else {
                $documentId = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->uploadResponse->children();
                $attachmentToFreeze[$collId][$resId] = (string) $documentId;
            }
        }

        // Send main document if in signature book
        if (!empty($annexes['letterbox'][0])) {
            $mainDocumentIntegration = json_decode($annexes['letterbox'][0]['integrations'], true);
            $externalId              = json_decode($annexes['letterbox'][0]['external_id'], true);
            if ($mainDocumentIntegration['inSignatureBook'] && empty($externalId['signatureBookId'])) {
                $resId  = $annexes['letterbox'][0]['res_id'];
                $collId = 'letterbox_coll';
                unset($annexes['letterbox']);
    
                $curlReturn = FastParapheurController::uploadFile([
                    'resId'        => $resId,
                    'collId'       => $collId,
                    'resIdMaster'  => $aArgs['resIdMaster'],
                    'annexes'      => $annexes,
                    'circuitId'    => $circuitId,
                    'label'        => $label,
                    'subscriberId' => $subscriberId,
                    'config'       => $aArgs['config']
                ]);
    
                if ($curlReturn['infos']['http_code'] == 404) {
                    return ['error' => 'Erreur 404 : ' . $curlReturn['raw']];
                } elseif (!empty($curlReturn['error'])) {
                    return ['error' => $curlReturn['error']];
                } elseif (!empty($curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0])) {
                    $error = (string)$curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0]->children()->faultstring . PHP_EOL;
                    return ['error' => $error];
                } else {
                    $documentId = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->uploadResponse->children();
                    $attachmentToFreeze[$collId][$resId] = (string) $documentId;
                }
            }
        }

        return ['sended' => $attachmentToFreeze];
    }

    public static function uploadFile($aArgs)
    {
        $adrInfo = AdrModel::getConvertedDocumentById(['resId' => $aArgs['resId'], 'collId' => $aArgs['collId'], 'type' => 'PDF']);
        $attachmentPath     =  DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id'], 'select' => ['path_template']]);
        $attachmentFilePath = $attachmentPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $adrInfo['path']) . $adrInfo['filename'];
        $attachmentFileName = 'projet_courrier_' . $aArgs['resIdMaster'] . '_' . rand(0001, 9999) . '.pdf';

        $zip         = new \ZipArchive();
        $tmpPath     = CoreConfigModel::getTmpPath();
        $zipFilePath = $tmpPath . DIRECTORY_SEPARATOR
            . $attachmentFileName . '.zip';  // The zip file need to have the same name as the attachment we want to sign

        if ($zip->open($zipFilePath, \ZipArchive::CREATE)!==true) {
            return ['error' => "Can not open file : <$zipFilePath>\n"];
        }
        $zip->addFile($attachmentFilePath, $attachmentFileName);

        if (!empty($aArgs['annexes']['letterbox'])) {
            $zip->addFile($aArgs['annexes']['letterbox'][0]['filePath'], 'document_principal.' . $aArgs['annexes']['letterbox'][0]['format']);
        }

        if (isset($aArgs['annexes']['attachments'])) {
            for ($j = 0; $j < count($aArgs['annexes']['attachments']); $j++) {
                $zip->addFile(
                    $aArgs['annexes']['attachments'][$j]['filePath'],
                    'PJ_' . ($j + 1) . '.' . $aArgs['annexes']['attachments'][$j]['format']
                );
            }
        }

        $zip->close();

        $b64Attachment = base64_encode(file_get_contents($zipFilePath));
        $fileName      = $attachmentFileName . '.zip';
        $circuitId     = str_replace('.', '-', $aArgs['circuitId']);

        $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sei="http://sei.ws.fast.cdc.com/">
                <soapenv:Header/>
                    <soapenv:Body>
                        <sei:upload>
                            <label>' . $aArgs['label'] . '</label>
                            <comment></comment>
                            <subscriberId>' . $aArgs['subscriberId'] . '</subscriberId>
                            <circuitId>' . $circuitId . '</circuitId>
                            <dataFileVO>
                                <dataHandler>' . $b64Attachment . '</dataHandler>
                                <filename>' . $fileName . '</filename>
                            </dataFileVO>
                        </sei:upload>
                    </soapenv:Body>
            </soapenv:Envelope>';

        $curlReturn = CurlModel::execSOAP([
            'xmlPostString' => $xmlPostString,
            'url'           => $aArgs['config']['data']['url'],
            'options'       => [
                CURLOPT_SSLCERT       => $aArgs['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD => $aArgs['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE   => $aArgs['config']['data']['certType']
            ]
        ]);

        return $curlReturn;
    }

    public static function download($aArgs)
    {
        $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sei="http://sei.ws.fast.cdc.com/">
                <soapenv:Header/>
                <soapenv:Body>
                    <sei:download>
                        <documentId>' . $aArgs['documentId'] . '</documentId>
                    </sei:download>
                </soapenv:Body>
            </soapenv:Envelope>';

        $curlReturn = CurlModel::execSOAP([
            'xmlPostString' => $xmlPostString,
            'url'           => $aArgs['config']['data']['url'],
            'options'       => [
                CURLOPT_SSLCERT       => $aArgs['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD => $aArgs['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE   => $aArgs['config']['data']['certType']
            ]
        ]);

        $isError = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
        if (!empty($isError ->Fault[0])) {
            echo (string)$curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0]->children()->faultstring . PHP_EOL;
            return false;
        } else {
            $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->downloadResponse->children()->return;
            $returnedDocumentId = (string) $response->documentId;
            if ($aArgs['documentId'] !== $returnedDocumentId) {
                return false;
            } else {
                $b64FileContent = $response->content;
                return ['b64FileContent' => (string)$b64FileContent, 'documentId' => $returnedDocumentId];
            }
        }
    }

    public static function sendDatas($aArgs)
    {
        $config = $aArgs['config'];
        // We need the SIRET field and the user_id of the signatory user's primary entity
        $signatory = DatabaseModel::select([
            'select'    => ['user_id', 'business_id', 'entities.entity_label'],
            'table'     => ['listinstance', 'users_entities', 'entities'],
            'left_join' => ['item_id = user_id', 'users_entities.entity_id = entities.entity_id'],
            'where'     => ['res_id = ?', 'item_mode = ?'],
            'data'      => [$aArgs['resIdMaster'], 'sign']
        ])[0];
        $redactor = DatabaseModel::select([
            'select'    => ['short_label'],
            'table'     => ['res_view_letterbox', 'users_entities', 'entities'],
            'left_join' => ['dest_user = user_id', 'users_entities.entity_id = entities.entity_id'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resIdMaster']]
        ])[0];

        if (empty($signatory['business_id']) || substr($signatory['business_id'], 0, 3) == 'org') {
            $signatory['business_id'] = $config['data']['subscriberId'];
        }

        return FastParapheurController::upload(['config' => $config, 'resIdMaster' => $aArgs['resIdMaster'], 'businessId' => $signatory['business_id'], 'circuitId' => $signatory['user_id'], 'label' => $redactor['short_label']]);
    }

    public static function getRefusalMessage($aArgs)
    {
        $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sei="http://sei.ws.fast.cdc.com/">
            <soapenv:Header/>
                <soapenv:Body>
                    <sei:getRefusalMessage>
                        <nodeRefId>' .  $aArgs['documentId'] . '</nodeRefId>
                    </sei:getRefusalMessage>
                </soapenv:Body>
         </soapenv:Envelope>';

        $curlReturn = CurlModel::execSOAP([
            'xmlPostString' => $xmlPostString,
            'url'           => $aArgs['config']['data']['url'],
            'options'       => [
                CURLOPT_SSLCERT       => $aArgs['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD => $aArgs['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE   => $aArgs['config']['data']['certType']
            ]
        ]);

        $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->getRefusalMessageResponse->children()->return;

        return $response;
    }
}
