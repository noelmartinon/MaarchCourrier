<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief IParapheur Controller
 * @author nathan.cheval@edissyum.com
 */


class IParapheurController
{
    public static function returnCurl($xmlPostString, $config)
    {
        $curlReturn = \SrcCore\models\CurlModel::execSOAP([
            'xmlPostString' => $xmlPostString,
            'url'           => $config['data']['url'],
            'options'       => [
                CURLOPT_SSLCERT         => $config['data']['certPath'],
                CURLOPT_SSLCERTTYPE     => $config['data']['certType'],
                CURLOPT_SSL_VERIFYPEER  => 'false',
                CURLOPT_USERPWD         => $config['data']['userId'] . ':' . $config['data']['password'],
            ],
            'delete_header' => true
        ]);

        return $curlReturn;
    }

    public static function getModal($config)
    {
        $html ='<center style="font-size:15px;">' . _ACTION_CONFIRM . '<br/><br/><b>' . _SEND_TO_IPARAPHEUR . '</b></center><br/>';
        return $html;
    }

    public static function sendDatas($aArgs)
    {
        $config = $aArgs['config'];
        $signatory = \SrcCore\models\DatabaseModel::select([
            'select'    => ['item_id'],
            'table'     => ['listinstance', ],
            'where'     => ['res_id = ?', 'item_mode = ?'],
            'data'      => [$aArgs['resIdMaster'], 'sign']
        ])[0];
        $sousType   = self::getSousType(['config' => $config, 'sousType' => $signatory['item_id']]);
        $type       = self::getType(['config' => $config]);

        if (!$type) {
            // TODO gestion erreurs
            return false;
        }
        return self::upload(['config' => $config, 'resIdMaster' => $aArgs['resIdMaster'], 'sousType' => $sousType ]);
    }

    public static function upload($aArgs)
    {
        $typeTechnique  = $aArgs['config']['data']['defaultType'];
        $sousType       = $aArgs['sousType'];

        // Retrieve the annexes of the attachment to sign (other attachments and the original document)
        $annexes = [];
        $annexes['letterbox']       = \Resource\models\ResModel::get([
            'select'                => ['res_id', 'path', 'filename', 'docserver_id'],
            'where'                 => ['res_id = ?'],
            'data'                  => [$aArgs['resIdMaster']]
        ])[0];
        $letterboxPath                      = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $annexes['letterbox']['docserver_id'], 'select' => ['path_template']]);
        $annexes['letterbox']['filePath']   = $letterboxPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $annexes['letterbox']['path']) . $annexes['letterbox']['filename'];

        $annexes['attachments']     = \Attachment\models\AttachmentModel::getOnView([
            'select'        => ['res_id', 'path', 'filename' ],
            'where'         => ['res_id_master = ?', 'attachment_type not in (?)', "status NOT IN ('DEL','OBS')", 'in_signature_book = FALSE', "format = 'pdf'"],
            'data'          => [$aArgs['resIdMaster'], 'print_folder']
        ]);

        if (!empty($annexes['attachments'])) {
            for ($i =0; $i < count($annexes['attachments']); $i++) {
                $annexAttachmentInfo                    = \Attachment\models\AttachmentModel::getById(['id' => $annexes['attachments'][$i]['res_id'], 'isVersion' => false]);
                $annexAttachmentPath                    = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $annexAttachmentInfo['docserver_id'], 'select' => ['path_template']]);
                $annexes['attachments'][$i]['filePath'] = $annexAttachmentPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $annexes['attachments'][$i]['path']) . $annexes['attachments'][$i]['filename'];
            }
        }
        // END annexes

        $attachments         = \Attachment\models\AttachmentModel::getOnView([
            'select'         => ['res_id', 'res_id_version', 'title', 'attachment_type','path'],
            'where'          => ['res_id_master = ?', 'attachment_type not in (?)', "status not in ('DEL', 'OBS', 'FRZ', 'TMP')", 'in_signature_book = TRUE'],
            'data'           => [$aArgs['resIdMaster'], ['converted_pdf', 'incoming_mail_attachment', 'print_folder', 'signed_response']]
        ]);

        for ($i = 0; $i < count($attachments); $i++) {
            if (!empty($attachments[$i]['res_id'])) {
                $resId  = $attachments[$i]['res_id'];
                $collId = 'attachments_coll';
                $is_version = false;
            } else {
                $resId  = $attachments[$i]['res_id_master'];
                $collId = 'attachments_version_coll';
                $is_version = true;
            }
            $attachmentInfo         = \Convert\controllers\ConvertPdfController::getConvertedPdfById(['resId' => $resId, 'collId' => $collId, 'isVersion' => $is_version]);

            $attachmentPath         = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $attachmentInfo['docserver_id'], 'select' => ['path_template']]);
            $attachmentFilePath     = $attachmentPath['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $attachmentInfo['path']) . $attachmentInfo['filename'];
            $dossierId              = $attachments[$i]['res_id'] . '_' . rand(0001, 9999);
            $dossierTitre           = _PROJECT_NUMBER . $attachments[$i]['res_id'];

            $mainResource = \Resource\models\ResModel::getExtById(['resId' => $aArgs['resIdMaster'], 'select' => ['process_limit_date']]);
            if (empty($mainResource['process_limit_date'])) {
                $processLimitDate = $mainResource['process_limit_date'] = date('Y-m-d', strtotime(date("Y-m-d"). ' + 14 days'));
            } else {
                $processLimitDateTmp = explode(" ", $mainResource['process_limit_date']);
                $processLimitDate = $processLimitDateTmp[0];
            }

            $b64Attachment          = base64_encode(file_get_contents($attachmentFilePath));
            $b64AnnexesLetterbox    = base64_encode(file_get_contents($annexes['letterbox']['filePath']));

            $annexLetterboxMimeType = mime_content_type($annexes['letterbox']['filePath']);
            if ($annexLetterboxMimeType) {
                $annexesXmlPostString = '<ns:DocAnnexe>
                                    <ns:nom>Fichier original</ns:nom>
                                    <ns:fichier xm:contentType="' . $annexLetterboxMimeType . '">' . $b64AnnexesLetterbox . '</ns:fichier>
                                    <ns:mimetype>' . $annexLetterboxMimeType . '</ns:mimetype>
                                    <ns:encoding>utf-8</ns:encoding>
                                </ns:DocAnnexe>';
            }
            if (!empty($annexes['attachments'])) {
                for ($j = 0; $j < count($annexes['attachments']); $j++) {
                    $b64AnnexesAttachment = base64_encode(file_get_contents($annexes['attachments'][$j]['filePath']));
                    $annexesXmlPostString .= '<ns:DocAnnexe> 
                                    <ns:nom>PJ_' . ($j + 1) . '</ns:nom> 
                                    <ns:fichier xm:contentType="application/pdf">' . $b64AnnexesAttachment . '</ns:fichier> 
                                    <ns:mimetype>application/pdf</ns:mimetype> 
                                    <ns:encoding>utf-8</ns:encoding>
                                </ns:DocAnnexe>';
                }
            }
            $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
                                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.adullact.org/spring-ws/iparapheur/1.0" xmlns:xm="http://www.w3.org/2005/05/xmlmime">
                                   <soapenv:Header/>
                                   <soapenv:Body>
                                      <ns:CreerDossierRequest>
                                         <ns:TypeTechnique>' . $typeTechnique . '</ns:TypeTechnique>
                                         <ns:SousType>' . $sousType . '</ns:SousType>
                                         <ns:DossierID>' . $dossierId . '</ns:DossierID>
                                         <ns:DossierTitre>' . $dossierTitre . '_' . $dossierId . '</ns:DossierTitre>
                                         <ns:DocumentPrincipal xm:contentType="application/pdf">' . $b64Attachment . '</ns:DocumentPrincipal>
                                         <ns:DocumentsSupplementaires></ns:DocumentsSupplementaires>
                                         <ns:DocumentsAnnexes>' . $annexesXmlPostString . '</ns:DocumentsAnnexes>
                                         <ns:MetaData>
                                            
                                         </ns:MetaData>
                                         <ns:AnnotationPublique></ns:AnnotationPublique>
                                         <ns:AnnotationPrivee></ns:AnnotationPrivee>
                                         <ns:Visibilite>CONFIDENTIEL</ns:Visibilite>
                                         <ns:DateLimite>' . $processLimitDate . '</ns:DateLimite>
                                      </ns:CreerDossierRequest>
                                   </soapenv:Body>
                                </soapenv:Envelope>';

            $curlReturn = self::returnCurl($xmlPostString, $aArgs['config']);

            if (!empty($curlReturn['error'])) {
                // TODO gestin d'une erreur
                echo $curlReturn['error'];
                return false;
            }
            $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://www.adullact.org/spring-ws/iparapheur/1.0')->CreerDossierResponse[0];

            if ($response->MessageRetour->codeRetour == $aArgs['config']['data']['errorCode'] || $curlReturn['infos']['http_code'] >= 500) {
                // TODO gestion d'une potentielle erreur
                echo '[' . $response->MessageRetour->severite . ']' . $response->MessageRetour->message;
                return false;
            } else {
                $attachmentToFreeze[$collId][$resId] = $dossierId;
            }
        }
        return $attachmentToFreeze;
    }

    public static function download($aArgs)
    {
        $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.adullact.org/spring-ws/iparapheur/1.0">
               <soapenv:Header/>
               <soapenv:Body>
                  <ns:GetDossierRequest>' . $aArgs['documentId'] . '</ns:GetDossierRequest>
               </soapenv:Body>
            </soapenv:Envelope>';

        $curlReturn = $curlReturn = self::returnCurl($xmlPostString, $aArgs['config']);

        $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://www.adullact.org/spring-ws/iparapheur/1.0')->GetDossierResponse[0];
        if ($response->MessageRetour->codeRetour == $aArgs['config']['data']['errorCode']) {
            // TODO gestion d'une potentielle erreur
            echo '[' . $response->MessageRetour->severite . ']' . $response->MessageRetour->message;
            return false;
        } else {
            $returnedDocumentId = (string) $response->DossierID;
            if ($aArgs['documentId'] !== $returnedDocumentId) {
                // TODO gestion d'une potentielle erreur
                return false;
            } else {
                $b64FileContent = (string)$response->DocPrincipal;
                return ['b64FileContent' => $b64FileContent, 'documentId' => $returnedDocumentId];
            }
        }
    }

    public static function retrieveSignedMails($aArgs)
    {
        foreach ($aArgs['idsToRetrieve']['noVersion'] as $noVersion) {
            if (!empty($noVersion->external_id)) {
                $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
                    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.adullact.org/spring-ws/iparapheur/1.0">
                        <soapenv:Header/> 
                        <soapenv:Body> 
                            <ns:GetHistoDossierRequest>' . $noVersion->external_id . '</ns:GetHistoDossierRequest> 
                        </soapenv:Body> 
                    </soapenv:Envelope>';

                $curlReturn = self::returnCurl($xmlPostString, $aArgs['config']);

                if (!empty($curlReturn['response'])) {
                    // TODO gestin d'une erreur
                    echo $curlReturn['error'];
                    return false;
                }

                $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://www.adullact.org/spring-ws/iparapheur/1.0')->GetHistoDossierResponse[0];

                if ($response->MessageRetour->codeRetour == $aArgs['config']['data']['errorCode']) {
                    // TODO gestion d'une potentielle erreur
                    echo 'retrieveSignedMails noVersion : [' . $response->MessageRetour->severite . ']' . $response->MessageRetour->message;
                    return false;
                } else {
                    $noteContent = '';
                    foreach ($response->LogDossier as $res) {    // Loop on all steps of the documents (prepared, send to signature, signed etc...)
                        $status = $res->status;
                        if ($status == $aArgs['config']['data']['visaState'] || $status == $aArgs['config']['data']['signState']) {
                            $noteContent .= $res->nom . ' : ' . $res->annotation . PHP_EOL;

                            $response = self::download([
                               'config' => $aArgs['config'],
                               'documentId' => $noVersion->external_id
                           ]);
                            $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->status = 'validated';
                            $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->format = 'pdf';
                            $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->encodedFile = $response['b64FileContent'];
                            $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->noteContent = $noteContent;
                            if ($status == $aArgs['config']['data']['signState']) {
                                break;
                            }
                        } elseif ($status == $aArgs['config']['data']['refusedVisa'] || $status == $aArgs['config']['data']['refusedSign']) {
                            $noteContent .= $res->nom . ' : ' . $res->annotation . PHP_EOL;
                            $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->status = 'refused';
                            $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->noteContent = $noteContent;
                            break;
                        } else {
                            $aArgs['idsToRetrieve']['noVersion'][$noVersion->res_id]->status = 'waiting';
                        }
                    }
                }
            } else {
                echo _EXTERNAL_ID_EMPTY;
            }
        }
        foreach ($aArgs['idsToRetrieve']['isVersion'] as $isVersion) {
            if (!empty($noVersion->external_id)) {
                $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
                   <?xml version="1.0" encoding="utf-8"?>
                    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.adullact.org/spring-ws/iparapheur/1.0">
                        <soapenv:Header/> 
                        <soapenv:Body> 
                            <ns:GetHistoDossierRequest>' . $isVersion->external_id . '</ns:GetHistoDossierRequest> 
                        </soapenv:Body> 
                    </soapenv:Envelope>';

                $curlReturn = self::returnCurl($xmlPostString, $aArgs['config']);

                if (!empty($curlReturn['response'])) {
                    // TODO gestin d'une erreur
                    echo $curlReturn['error'];
                    return false;
                }

                $response = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://www.adullact.org/spring-ws/iparapheur/1.0')->GetHistoDossierResponse[0];

                if ($response->MessageRetour->codeRetour == $aArgs['config']['data']['errorCode']) {
                    // TODO gestion d'une potentielle erreur
                    echo 'retrieveSignedMails isVersion : [' . $response->MessageRetour->severite . ']' . $response->MessageRetour->message;
                    return false;
                } else {
                    $noteContent = '';
                    foreach ($response->LogDossier as $res) {    // Loop on all steps of the documents (prepared, send to signature, signed etc...)
                        $status = $res->status;
                        if ($status == $aArgs['config']['data']['visaState'] || $status == $aArgs['config']['data']['signState']) {
                            $noteContent .= $res->nom . ' : ' . $res->annotation . PHP_EOL;
                            $response = self::download([
                                'config' => $aArgs['config'],
                                'documentId' => $isVersion->external_id
                            ]);
                            $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id]->status = 'validated';
                            $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id]->format = 'pdf';
                            $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id]->encodedFile = $response['b64FileContent'];
                            $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id]->noteContent = $noteContent;
                            if ($status == $aArgs['config']['data']['signState']) {
                                break;
                            }
                        } elseif ($status == $aArgs['config']['data']['refusedVisa'] || $status == $aArgs['config']['data']['refusedSign']) {
                            $noteContent .= $res->nom . ' : ' . $res->annotation . PHP_EOL;
                            $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id]->status = 'refused';
                            $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id]->noteContent = $noteContent;
                            break;
                        } else {
                            $aArgs['idsToRetrieve']['isVersion'][$isVersion->res_id]->status = 'waiting';
                        }
                    }
                }
            } else {
                echo _EXTERNAL_ID_EMPTY;
            }
        }
        return $aArgs['idsToRetrieve'];
    }

    public static function getType($aArgs)
    {
        $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
           <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.adullact.org/spring-ws/iparapheur/1.0">
               <soapenv:Header/>
               <soapenv:Body>
                  <ns:GetListeTypesRequest></ns:GetListeTypesRequest>
               </soapenv:Body>
            </soapenv:Envelope>';

        $curlReturn = $curlReturn = self::returnCurl($xmlPostString, $aArgs['config']);

        if (!empty($curlReturn['error'])) {
            // TODO gestin d'une erreur
            echo $curlReturn['error'];
            return false;
        }

        $response   = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://www.adullact.org/spring-ws/iparapheur/1.0')->GetListeTypesResponse[0];

        $typeExist  = false;
        foreach ($response->TypeTechnique as $res) {
            if ($res == $aArgs['config']['data']['defaultType']) {
                $typeExist = true;
                break;
            }
        }
        if (!$typeExist) {
            // TODO Gestion erreur
            return false;
        } else {
            return true;
        }
    }

    public static function getSousType($aArgs)
    {
        $xmlPostString = '<?xml version="1.0" encoding="utf-8"?>
           <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.adullact.org/spring-ws/iparapheur/1.0">
               <soapenv:Header/>
               <soapenv:Body>
                  <ns:GetListeSousTypesRequest>' . $aArgs['config']['data']['defaultType'] . '</ns:GetListeSousTypesRequest>
               </soapenv:Body>
            </soapenv:Envelope>';

        $curlReturn = $curlReturn = self::returnCurl($xmlPostString, $aArgs['config']);

        if (!empty($curlReturn['error'])) {
            // TODO gestin d'une erreur
            echo $curlReturn['error'];
            return false;
        }

        $response   = $curlReturn['response']->children('http://schemas.xmlsoap.org/soap/envelope/')->Body->children('http://www.adullact.org/spring-ws/iparapheur/1.0')->GetListeSousTypesResponse[0];

        $subTypeExist = false;
        foreach ($response->SousType as $res) {
            if ($res == $aArgs['sousType']) {
                $subTypeExist = true;
                break;
            }
        }

        if (!$subTypeExist) {
            return $aArgs['config']['data']['defaultSousType'];
        } else {
            return $aArgs['sousType'];
        }
    }
}
