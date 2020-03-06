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
 */

class FastParapheurController
{
    public static function retrieveSignedMails($aArgs)
    {
        foreach ($aArgs['idsToRetrieve']['noVersion'] as $noVersion) {
            $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sei="http://sei.ws.fast.cdc.com/">
                <soapenv:Header/>
                    <soapenv:Body>
                        <sei:history>
                            <documentId>' .  $noVersion->external_id . '</documentId>
                        </sei:history>
                    </soapenv:Body>
             </soapenv:Envelope>';

            $curlReturn = \SrcCore\models\CurlModel::execSOAP([
                'xmlPostString' => $xmlPostString,
                'url'           => $aArgs['config']['data']['url'],
                'options'       => [
                    CURLOPT_SSLCERT         => $aArgs['config']['data']['certPath'],
                    CURLOPT_SSLCERTPASSWD   => $aArgs['config']['data']['certPass'],
                    CURLOPT_SSLCERTTYPE     => $aArgs['config']['data']['certType']
                ]
            ]);

            $isError    = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
            if (!empty($isError ->Fault[0])) {
                // TODO gestion des erreurs
                echo 'PJ n° ' . $noVersion->res_id . ' et document original n° ' . $noVersion->res_id_master . ' : ' . (string)$curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0]->children()->faultstring . PHP_EOL;
                continue;
            }

            $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->historyResponse->children();

            foreach ($response->return as $res) {    // Loop on all steps of the documents (prepared, send to signature, signed etc...)
                $state      = (string) $res->stateName;
                if ($state == $aArgs['config']['data']['validatedState']) {
                    $response = self::download(['config' => $aArgs['config'], 'documentId' => $noVersion->external_id]);
                    $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->status = 'validated';
                    $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->format = 'pdf';
                    $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->encodedFile = $response['b64FileContent'];
                    break;
                } elseif ($state == $aArgs['config']['data']['refusedState']) {
                    $GLOBALS['db'] = new \SrcCore\models\DatabasePDO(['customId' => $GLOBALS['CustomId']]);
                    $query = $GLOBALS['db']->query(
                        "SELECT user_id, firstname, lastname FROM listinstance LEFT JOIN users ON item_id = user_id WHERE res_id = ? AND item_mode = ?",
                        [$aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->res_id_master, 'sign']
                    );
                    $res = $query->fetchObject();

                    $response = self::getRefusalMessage(['config' => $aArgs['config'], 'documentId' => $noVersion->external_id]);
                    $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->status = 'refused';
                    $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->noteContent = $res->lastname . ' ' . $res->firstname . ' : ' . $response;
                    break;
                } else {
                    $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->status = 'waiting';
                }
            }
        }
        foreach ($aArgs['idsToRetrieve']['isVersion'] as $isVersion) {
            $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sei="http://sei.ws.fast.cdc.com/">
                <soapenv:Header/>
                    <soapenv:Body>
                        <sei:history>
                            <documentId>' .  $isVersion->external_id . '</documentId>
                        </sei:history>
                    </soapenv:Body>
             </soapenv:Envelope>';

            $curlReturn = \SrcCore\models\CurlModel::execSOAP([
                'xmlPostString' => $xmlPostString,
                'url'           => $aArgs['config']['data']['url'],
                'options'       => [
                    CURLOPT_SSLCERT         => $aArgs['config']['data']['certPath'],
                    CURLOPT_SSLCERTPASSWD   => $aArgs['config']['data']['certPass'],
                    CURLOPT_SSLCERTTYPE     => $aArgs['config']['data']['certType']
                ]
            ]);

            $isError    = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
            if (!empty($isError ->Fault[0])) {
                // TODO gestion des erreurs
                echo 'PJ n° ' . $isVersion->res_id_version . ' et document original n° ' . $isVersion->res_id_master . ' : ' . (string)$curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0]->children()->faultstring . PHP_EOL;
                continue;
            }

            $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->historyResponse->children();
            foreach ($response->return as $res) {    // Loop on all steps of the documents (prepared, send to signature, signed etc...)
                $state = (string) $res->stateName;
                if ($state == $aArgs['config']['data']['validatedState']) {
                    $response = self::download(['config' => $aArgs['config'], 'documentId' => $isVersion->external_id]);
                    $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id_version]->status = 'validated';
                    $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id_version]->format = 'pdf';
                    $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id_version]->encodedFile = $response['b64FileContent'];
                    break;
                } elseif ($state == $aArgs['config']['data']['refusedState']) {
                    $GLOBALS['db'] = new \SrcCore\models\DatabasePDO(['customId' => $GLOBALS['CustomId']]);
                    $query = $GLOBALS['db']->query(
                        "SELECT user_id, firstname, lastname FROM listinstance LEFT JOIN users ON item_id = user_id WHERE res_id = ? AND item_mode = ?",
                        [$aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->res_id_master, 'sign']
                    );
                    $res = $query->fetchObject();

                    $response = self::getRefusalMessage(['config' => $aArgs['config'], 'documentId' => $isVersion->external_id]);
                    $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id_version]->status = 'refused';
                    $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id_version]->noteContent = $res->lastname . ' ' . $res->firstname . ' : ' . $response;
                    break;
                } else {
                    $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id_version]->status = 'waiting';
                }
            }
        }
        return $aArgs['idsToRetrieve'];
    }

    public static function upload($aArgs)
    {
        $circuitId          = $aArgs['circuitId'];
        $label              = $aArgs['label'];
        $subscriberId       = $aArgs['businessId'];

        // Retrieve the annexes of the attachemnt to sign (other attachment and the original document)
        $annexes = [];
        $annexes['letterbox']       = \Resource\models\ResModel::getOnView([
            'select'                => ['res_id', 'path', 'filename', 'docserver_id','format', 'category_id'],
            'where'                 => ['res_id = ?'],
            'data'                  => [$aArgs['resIdMaster']]
        ]);

        if ($annexes['letterbox'][0]['category_id'] !== 'outgoing') {
            $letterboxPath                    = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $annexes['letterbox'][0]['docserver_id'], 'select' => ['path_template']]);
            $annexes['letterbox'][0]['filePath'] = $letterboxPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $annexes['letterbox'][0]['path']) . $annexes['letterbox'][0]['filename'];
        }

        $annexes['attachments']     = \Attachment\models\AttachmentModel::getOnView([
            'select'                => ['res_id', 'path', 'filename', 'format'],
            'where'                 => ['res_id_master = ?', 'attachment_type not in (?)', "status NOT IN ('DEL','OBS')", 'in_signature_book = FALSE'],
            'data'                  => [$aArgs['resIdMaster'], 'print_folder']
        ]);

        if (!empty($annexes['attachments'])) {
            for ($i = 0; $i < count($annexes['attachments']); $i++) {
                $annexAttachmentInfo                    = \Attachment\models\AttachmentModel::getById(['id' => $annexes['attachments'][$i]['res_id'], 'isVersion' => false]);
                $annexAttachmentPath                    = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $annexAttachmentInfo['docserver_id'], 'select' => ['path_template']]);
                $annexes['attachments'][$i]['filePath'] = $annexAttachmentPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $annexes['attachments'][$i]['path']) . $annexes['attachments'][$i]['filename'];
            }
        }
        // END annexes

        $attachments         = \Attachment\models\AttachmentModel::getOnView([
            'select'         => ['res_id', 'res_id_version', 'title', 'attachment_type','path', 'res_id_master', 'format'],
            'where'          => ['res_id_master = ?', 'attachment_type not in (?)', "status not in ('DEL', 'OBS', 'FRZ')", 'in_signature_book = TRUE'],
            'data'           => [$aArgs['resIdMaster'], ['converted_pdf', 'incoming_mail_attachment', 'print_folder', 'signed_response']]
        ]);

        $attachmentToFreeze = [];
        for ($i = 0; $i < count($attachments); $i++) {
            if (!empty($attachments[$i]['res_id'])) {
                $resId  = $attachments[$i]['res_id'];
                $collId = 'attachments_coll';
                $adrInfo = \Convert\models\AdrModel::getConvertedDocumentById(['resId' => $resId, 'collId' => $collId, 'type' => 'PDF']);
            } else {
                $resId  = $attachments[$i]['res_id_version'];
                $collId = 'attachments_version_coll';
                $adrInfo = \Convert\models\AdrModel::getConvertedDocumentById(['resId' => $resId, 'collId' => $collId, 'type' => 'PDF', 'isVersion' => true]);
            }

            $attachmentPath         =  \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id'], 'select' => ['path_template']]);
            $attachmentFilePath     = $attachmentPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $adrInfo['path']) . $adrInfo['filename'];
            $attachmentFileName     = 'projet_courrier_' . $attachments[$i]['res_id_master'] . '_' . rand(0001, 9999) . '.pdf';

            $zip            = new ZipArchive();
            $tmpPath        = \SrcCore\models\CoreConfigModel::getTmpPath();
            $zipFilePath    = $tmpPath . DIRECTORY_SEPARATOR
                . $attachmentFileName . '.zip';  // The zip file need to have the same name as the attachment we want to sign

            if ($zip->open($zipFilePath, ZipArchive::CREATE)!==true) {
                exit(_ERROR_CREATE_ZIP . "<$zipFilePath>\n");
            }
            $zip->addFile($attachmentFilePath, $attachmentFileName);

            if ($annexes['letterbox'][0]['category_id'] !== 'outgoing') {
                $zip->addFile($annexes['letterbox'][0]['filePath'], 'document_principal.' . $annexes['letterbox'][0]['format']);
            }

            if (isset($annexes['attachments'])) {
                for ($j = 0; $j < count($annexes['attachments']); $j++) {
                    $zip->addFile(
                        $annexes['attachments'][$j]['filePath'],
                        'PJ_' . ($j + 1) . '.' . $annexes['attachments'][$j]['format']
                    );
                }
            }

            $zip->close();

            $b64Attachment          = base64_encode(file_get_contents($zipFilePath));
            $fileName               = $attachmentFileName . '.zip';

            $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sei="http://sei.ws.fast.cdc.com/">
            <soapenv:Header/>
                <soapenv:Body>
                    <sei:upload>
                        <label>' . $label . '</label>
                        <comment></comment>
                        <subscriberId>' . $subscriberId . '</subscriberId>
                        <circuitId>' . $circuitId . '</circuitId>
                        <dataFileVO>
                            <dataHandler>' . $b64Attachment . '</dataHandler>
                            <filename>' . $fileName . '</filename>
                        </dataFileVO>
                    </sei:upload>
                </soapenv:Body>
        </soapenv:Envelope>';

            $curlReturn = \SrcCore\models\CurlModel::execSOAP([
                'xmlPostString' => $xmlPostString,
                'url'           => $aArgs['config']['data']['url'],
                'options'       => [
                    CURLOPT_SSLCERT         => $aArgs['config']['data']['certPath'],
                    CURLOPT_SSLCERTPASSWD   => $aArgs['config']['data']['certPass'],
                    CURLOPT_SSLCERTTYPE     => $aArgs['config']['data']['certType']
                ]
            ]);

            if (!empty($curlReturn['error'])) {
                return ['error' => $curlReturn['error']];
            } elseif (!empty($curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0])) {
                $error = (string)$curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0]->children()->faultstring . PHP_EOL;
                return ['error' => $error];
            } else {
                $documentId = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->uploadResponse->children();
                $attachmentToFreeze[$collId][$resId] = (string) $documentId;
            }
        }

        return ['sended' => $attachmentToFreeze];
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

        $curlReturn = \SrcCore\models\CurlModel::execSOAP([
            'xmlPostString' => $xmlPostString,
            'url'           => $aArgs['config']['data']['url'],
            'options'       => [
                CURLOPT_SSLCERT         => $aArgs['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD   => $aArgs['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE     => $aArgs['config']['data']['certType']
            ]
        ]);

        $isError    = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
        if (!empty($isError ->Fault[0])) {
            // TODO gestion des erreurs
            echo (string)$curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->Fault[0]->children()->faultstring . PHP_EOL;
            return false;
        } else {
            $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->downloadResponse->children()->return;
            $returnedDocumentId = (string) $response->documentId;
            if ($aArgs['documentId'] !== $returnedDocumentId) {
                // TODO gestion d'une potentiel erreur
                return false;
            } else {
                $b64FileContent = $response->content;
                return ['b64FileContent' => (string)$b64FileContent, 'documentId' => $returnedDocumentId];
            }
        }
    }

    public static function getModal($config)
    {
        $html ='<center style="font-size:15px;">'._ACTION_CONFIRM.'<br/><br/><b>' . _SEND_TO_FAST . '</b></center><br/>';
        return $html;
    }

    public static function sendDatas($aArgs)
    {
        $config = $aArgs['config'];
        // We need the SIRET field and the user_id of the signatory user's primary entity
        $signatory = \SrcCore\models\DatabaseModel::select([
            'select'    => ['user_id', 'business_id', 'entities.entity_label'],
            'table'     => ['listinstance', 'users_entities', 'entities'],
            'left_join' => ['item_id = user_id', 'users_entities.entity_id = entities.entity_id'],
            'where'     => ['res_id = ?', 'item_mode = ?'],
            'data'      => [$aArgs['resIdMaster'], 'sign']
        ])[0];
        $redactor = \SrcCore\models\DatabaseModel::select([
            'select'    => ['short_label'],
            'table'     => ['res_view_letterbox', 'users_entities', 'entities'],
            'left_join' => ['dest_user = user_id', 'users_entities.entity_id = entities.entity_id'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resIdMaster']]
        ])[0];

        if (empty($signatory['business_id']) || substr($signatory['business_id'], 0, 3) == 'org') {
            $signatory['business_id'] = $config['data']['subscriberId'];
        }

        return self::upload(['config' => $config, 'resIdMaster' => $aArgs['resIdMaster'], 'businessId' => $signatory['business_id'], 'circuitId' => $signatory['user_id'], 'label' => $redactor['short_label']]);
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

        $curlReturn = \SrcCore\models\CurlModel::execSOAP([
            'xmlPostString' => $xmlPostString,
            'url'           => $aArgs['config']['data']['url'],
            'options'       => [
                CURLOPT_SSLCERT         => $aArgs['config']['data']['certPath'],
                CURLOPT_SSLCERTPASSWD   => $aArgs['config']['data']['certPass'],
                CURLOPT_SSLCERTTYPE     => $aArgs['config']['data']['certType']
            ]
        ]);

        $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://sei.ws.fast.cdc.com/')->getRefusalMessageResponse->children()->return;

        return $response;
    }
}
