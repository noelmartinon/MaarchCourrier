<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief MaarchParapheur Controller
 * @author dev@maarch.org
 */

namespace ExternalSignatoryBook\controllers;

use Attachment\models\AttachmentModel;
use Convert\controllers\ConvertPdfController;
use Docserver\models\DocserverModel;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use Note\models\NoteModel;
use Priority\models\PriorityModel;
use Resource\controllers\SummarySheetController;
use Resource\models\ResModel;
use setasign\Fpdi\Tcpdf\Fpdi;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use User\models\UserModel;

class MaarchParapheurController
{
    public static function getInitializeDatas(array $aArgs)
    {
        $rawResponse['users'] = MaarchParapheurController::getUsers(['config' => $aArgs['config']]);
        if (!empty($rawResponse['users']['error'])) {
            return ['error' => $rawResponse['users']['error']];
        }
        return $rawResponse;
    }

    public static function getUsers(array $aArgs)
    {
        $response = CurlModel::exec([
            'url'      => rtrim($aArgs['config']['data']['url'], '/') . '/rest/users',
            'user'     => $aArgs['config']['data']['userId'],
            'password' => $aArgs['config']['data']['password'],
            'method'   => 'GET'
        ]);

        if (!empty($response['error'])) {
            return ["error" => $response['error']];
        }

        return $response['users'];
    }

    public static function sendDatas(array $aArgs)
    {
        $attachmentToFreeze = [];

        $adrMainInfo = ConvertPdfController::getConvertedPdfById(['resId' => $aArgs['resIdMaster'], 'collId' => 'letterbox_coll']);
        if (empty($adrMainInfo['docserver_id'])) {
            return ['error' => 'Document ' . $resId . ' is not converted in pdf'];
        }
        $docserverMainInfo = DocserverModel::getByDocserverId(['docserverId' => $adrMainInfo['docserver_id']]);
        if (empty($docserverMainInfo['path_template'])) {
            return ['error' => 'Docserver does not exist ' . $adrMainInfo['docserver_id']];
        }
        $arrivedMailMainfilePath = $docserverMainInfo['path_template'] . str_replace('#', '/', $adrMainInfo['path']) . $adrMainInfo['filename'];

        $mainResource = ResModel::getOnView([
            'select' => ['process_limit_date', 'status', 'category_id', 'alt_identifier', 'subject', 'priority', 'contact_firstname', 'contact_lastname', 'contact_society', 'res_id', 'nature_id', 'admission_date', 'creation_date', 'doc_date', 'initiator', 'typist', 'type_label', 'destination'],
            'where'  => ['res_id = ?'],
            'data'   => [$aArgs['resIdMaster']]
        ]);

        if (empty($mainResource)) {
            return ['error' => 'Mail does not exist'];
        }

        $units = [];
        $units[] = ['unit' => 'primaryInformations'];
        $units[] = ['unit' => 'secondaryInformations',       'label' => _SECONDARY_INFORMATION];
        $units[] = ['unit' => 'senderRecipientInformations', 'label' => _DEST_INFORMATION];
        $units[] = ['unit' => 'diffusionList',               'label' => _DIFFUSION_LIST];
        $units[] = ['unit' => 'visaWorkflow',                'label' => _VISA_WORKFLOW];
        $units[] = ['unit' => 'opinionWorkflow',             'label' => _AVIS_WORKFLOW];
        $units[] = ['unit' => 'notes',                       'label' => _NOTES_COMMENT];

        // Data for resources
        $tmpIds = [$aArgs['resIdMaster']];
        $data   = [];
        foreach ($units as $unit) {
            if ($unit['unit'] == 'notes') {
                $data['notes'] = NoteModel::get([
                    'select'   => ['id', 'note_text', 'user_id', 'creation_date', 'identifier'],
                    'where'    => ['identifier in (?)'],
                    'data'     => [$tmpIds],
                    'order_by' => ['identifier']]);

                $userEntities = EntityModel::getByLogin(['login' => $aArgs['userId'], 'select' => ['entity_id']]);
                $data['userEntities'] = [];
                foreach ($userEntities as $userEntity) {
                    $data['userEntities'][] = $userEntity['entity_id'];
                }
            } elseif ($unit['unit'] == 'opinionWorkflow') {
                $data['listInstancesOpinion'] = ListInstanceModel::get([
                    'select'    => ['item_id', 'process_date', 'res_id'],
                    'where'     => ['difflist_type = ?', 'res_id in (?)'],
                    'data'      => ['AVIS_CIRCUIT', $tmpIds],
                    'orderBy'   => ['listinstance_id']
                ]);
            } elseif ($unit['unit'] == 'visaWorkflow') {
                $data['listInstancesVisa'] = ListInstanceModel::get([
                    'select'    => ['item_id', 'requested_signature', 'process_date', 'res_id'],
                    'where'     => ['difflist_type = ?', 'res_id in (?)'],
                    'data'      => ['VISA_CIRCUIT', $tmpIds],
                    'orderBy'   => ['listinstance_id']
                ]);
            } elseif ($unit['unit'] == 'diffusionList') {
                $data['listInstances'] = ListInstanceModel::get([
                    'select'  => ['item_id', 'item_type', 'item_mode', 'res_id'],
                    'where'   => ['difflist_type = ?', 'res_id in (?)'],
                    'data'    => ['entity_id', $tmpIds],
                    'orderBy' => ['listinstance_id']
                ]);
            } elseif ($unit['unit'] == 'senderRecipientInformations') {
                $data['mlbCollExt'] = ResModel::getExt([
                    'select' => ['category_id', 'address_id', 'exp_user_id', 'dest_user_id', 'is_multicontacts', 'res_id'],
                    'where'  => ['res_id in (?)'],
                    'data'   => [$tmpIds]
                ]);
            }
        }

        $pdf = new Fpdi('P', 'pt');
        $pdf->setPrintHeader(false);
        SummarySheetController::createSummarySheet($pdf, ['resource' => $mainResource[0], 'units' => $units, 'login' => $aArgs['userId'], 'data' => $data]);

        $tmpPath = CoreConfigModel::getTmpPath();
        $filename = $tmpPath . "summarySheet_".$aArgs['resIdMaster'] . "_" . $aArgs['userId'] ."_".rand().".pdf";
        $pdf->Output($filename, 'F');

        $concatPdf = new Fpdi('P', 'pt');
        $concatPdf->setPrintHeader(false);

        if ($aArgs['objectSent'] == 'mail') {
            foreach ([$filename, $arrivedMailMainfilePath] as $file) {
                $pageCount = $concatPdf->setSourceFile($file);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $pageId = $concatPdf->ImportPage($pageNo);
                    $s = $concatPdf->getTemplatesize($pageId);
                    $concatPdf->AddPage($s['orientation'], $s);
                    $concatPdf->useImportedPage($pageId);
                }
            }

            unlink($filename);
            $concatFilename = $tmpPath . "concatPdf_".$aArgs['resIdMaster'] . "_" . $aArgs['userId'] ."_".rand().".pdf";
            $concatPdf->Output($concatFilename, 'F');
            $arrivedMailMainfilePath = $concatFilename;
        }

        $encodedMainZipFile = MaarchParapheurController::createZip(['filepath' => $arrivedMailMainfilePath, 'filename' => 'courrier_arrivee.pdf']);

        if (empty($mainResource[0]['process_limit_date'])) {
            $processLimitDate = date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s"). ' + 14 days'));
        } else {
            $processLimitDate = $mainResource[0]['process_limit_date'];
        }

        $processingUser = $aArgs['processingUser'];
        $priority = null;
        if (!empty($mainResource[0]['priority'])) {
            $priority = PriorityModel::getById(['select' => ['label'], 'id' => $mainResource[0]['priority']]);
        }
        $sender              = UserModel::getByLogin(['select' => ['firstname', 'lastname'], 'login' => $aArgs['userId']]);
        $senderPrimaryEntity = UserModel::getPrimaryEntityByUserId(['userId' => $aArgs['userId']]);

        if ($aArgs['objectSent'] == 'attachment') {
            $excludeAttachmentTypes = ['converted_pdf', 'print_folder', 'signed_response'];

            $attachments = AttachmentModel::getOnView([
                'select'    => [
                    'res_id', 'res_id_version', 'title', 'identifier', 'attachment_type',
                    'status', 'typist', 'docserver_id', 'path', 'filename', 'creation_date',
                    'validation_date', 'relation', 'attachment_id_master'
                ],
                'where'     => ["res_id_master = ?", "attachment_type not in (?)", "status not in ('DEL', 'OBS', 'FRZ', 'TMP', 'SEND_MASS')", "in_signature_book = 'true'"],
                'data'      => [$aArgs['resIdMaster'], $excludeAttachmentTypes]
            ]);

            if (empty($attachments)) {
                return ['error' => 'No attachment to send'];
            } else {
                foreach ($attachments as $value) {
                    if (!empty($value['res_id'])) {
                        $resId  = $value['res_id'];
                        $collId = 'attachments_coll';
                        $is_version = false;
                    } else {
                        $resId  = $value['res_id_version'];
                        $collId = 'attachments_version_coll';
                        $is_version = true;
                    }
                    
                    $adrInfo = ConvertPdfController::getConvertedPdfById(['resId' => $resId, 'collId' => $collId, 'isVersion' => $is_version]);
                    if (empty($adrInfo['docserver_id'])) {
                        return ['error' => 'Attachment ' . $resId . ' is not converted in pdf'];
                    }
                    $docserverInfo = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
                    if (empty($docserverInfo['path_template'])) {
                        return ['error' => 'Docserver does not exist ' . $adrInfo['docserver_id']];
                    }
                    $filePath      = $docserverInfo['path_template'] . str_replace('#', '/', $adrInfo['path']) . $adrInfo['filename'];
        
                    $encodedZipDocument = MaarchParapheurController::createZip(['filepath' => $filePath, 'filename' => $adrInfo['filename']]);
        
                    if ($mainResource[0]['category_id'] != 'outgoing') {
                        $attachmentsData = [[
                            'encodedDocument' => $encodedMainZipFile,
                            'title'           => $mainResource[0]['subject'],
                            'reference'       => $mainResource[0]['alt_identifier']
                        ]];

                        $summarySheetEncodedZip = MaarchParapheurController::createZip(['filepath' => $filename, 'filename' => "summarySheet.pdf"]);
                        $attachmentsData[] = [
                            'encodedDocument' => $summarySheetEncodedZip,
                            'title'           => "summarySheet.pdf",
                            'reference'       => ""
                        ];
                    } else {
                        $attachmentsData = [];
                    }
    
                    $metadata = [];
                    if (!empty($priority['label'])) {
                        $metadata[_PRIORITY] = $priority['label'];
                    }
                    if (!empty($senderPrimaryEntity['entity_label'])) {
                        $metadata[_INITIATOR_ENTITY] = $senderPrimaryEntity['entity_label'];
                    }
                    $contact = trim($mainResource[0]['contact_firstname'] . ' ' . $mainResource[0]['contact_lastname'] . ' ' . $mainResource[0]['contact_society']);
                    if (!empty($contact)) {
                        $metadata[_RECIPIENTS] = $contact;
                    }
        
                    $bodyData = [
                        'title'           => $value['title'],
                        'reference'       => $value['identifier'],
                        'mode'            => $aArgs['config']['data']['signature'],
                        'encodedDocument' => $encodedZipDocument,
                        'processingUser'  => $processingUser,
                        'sender'          => trim($sender['firstname'] . ' ' .$sender['lastname']),
                        'deadline'        => $processLimitDate,
                        'attachments'     => $attachmentsData,
                        'metadata'        => $metadata
                    ];
        
                    $response = CurlModel::exec([
                        'url'      => rtrim($aArgs['config']['data']['url'], '/') . '/rest/documents',
                        'user'     => $aArgs['config']['data']['userId'],
                        'password' => $aArgs['config']['data']['password'],
                        'method'   => 'POST',
                        'bodyData' => $bodyData
                    ]);
        
                    $attachmentToFreeze[$collId][$resId] = $response['id'];
                }
            }
        } elseif ($aArgs['objectSent'] == 'mail') {
            $metadata = [];
            if (!empty($priority['label'])) {
                $metadata[_PRIORITY] = $priority['label'];
            }
            if (!empty($senderPrimaryEntity['entity_label'])) {
                $metadata[_INITIATOR_ENTITY] = $senderPrimaryEntity['entity_label'];
            }
            $contact = trim($mainResource[0]['contact_firstname'] . ' ' . $mainResource[0]['contact_lastname'] . ' ' . $mainResource[0]['contact_society']);
            if (!empty($contact)) {
                $metadata[_RECIPIENTS] = $contact;
            }

            $bodyData = [
                'title'              => $mainResource[0]['subject'],
                'reference'          => $mainResource[0]['alt_identifier'],
                'mode'               => $aArgs['config']['data']['annotation'],
                'encodedDocument'    => $encodedMainZipFile,
                'processingUser'     => $processingUser,
                'sender'             => trim($sender['firstname'] . ' ' .$sender['lastname']),
                'deadline'           => $processLimitDate,
                'metadata'           => $metadata
            ];

            $response = CurlModel::exec([
                'url'      => rtrim($aArgs['config']['data']['url'], '/') . '/rest/documents',
                'user'     => $aArgs['config']['data']['userId'],
                'password' => $aArgs['config']['data']['password'],
                'method'   => 'POST',
                'bodyData' => $bodyData
            ]);

            $attachmentToFreeze['letterbox_coll'][$aArgs['resIdMaster']] = $response['id'];
        }

        return ['sended' => $attachmentToFreeze];
    }

    public static function createZip(array $aArgs)
    {
        $zip = new \ZipArchive();

        $pathInfo    = pathinfo($aArgs['filepath'], PATHINFO_FILENAME);
        $tmpPath     = CoreConfigModel::getTmpPath();
        $zipFilename = $tmpPath . $pathInfo."_".rand().".zip";

        if ($zip->open($zipFilename, \ZipArchive::CREATE) === true) {
            $zip->addFile($aArgs['filepath'], $aArgs['filename']);

            $zip->close();

            $fileContent = file_get_contents($zipFilename);
            $base64 =  base64_encode($fileContent);
            unlink($zipFilename);
            return $base64;
        } else {
            echo 'Impossible de créer l\'archive;';
        }
    }

    public static function getUserById(array $aArgs)
    {
        $response = CurlModel::exec([
            'url'      => rtrim($aArgs['config']['data']['url'], '/') . '/rest/users/'.$aArgs['id'],
            'user'     => $aArgs['config']['data']['userId'],
            'password' => $aArgs['config']['data']['password'],
            'method'   => 'GET'
        ]);

        return $response['user'];
    }

    public static function retrieveSignedMails(array $aArgs)
    {
        $validated = $aArgs['config']['data']['externalValidated'];
        $refused   = $aArgs['config']['data']['externalRefused'];

        foreach (['noVersion', 'isVersion', 'resLetterbox'] as $version) {
            foreach ($aArgs['idsToRetrieve'][$version] as $resId => $value) {
                $documentStatus = MaarchParapheurController::getDocumentStatus(['config' => $aArgs['config'], 'documentId' => $value->external_id]);
                
                if (in_array($documentStatus['reference'], [$validated, $refused])) {
                    $signedDocument = MaarchParapheurController::getProcessedDocument(['config' => $aArgs['config'], 'documentId' => $value->external_id]);
                    $aArgs['idsToRetrieve'][$version][$resId]->format = 'pdf'; // format du fichier récupéré
                    $aArgs['idsToRetrieve'][$version][$resId]->encodedFile = $signedDocument;
                    if ($documentStatus['reference'] == $validated && $documentStatus['mode'] == 'SIGN') {
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'validated';
                    } elseif ($documentStatus['reference'] == $refused && $documentStatus['mode'] == 'SIGN') {
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'refused';
                    } elseif ($documentStatus['reference'] == $validated && $documentStatus['mode'] == 'NOTE') {
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'validatedNote';
                    } elseif ($documentStatus['reference'] == $refused && $documentStatus['mode'] == 'NOTE') {
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'refusedNote';
                    }
                } else {
                    unset($aArgs['idsToRetrieve'][$version][$resId]);
                }
            }
        }

        // retourner seulement les mails récupérés (validés ou signés)
        return $aArgs['idsToRetrieve'];
    }

    public static function getDocumentStatus(array $aArgs)
    {
        $response = CurlModel::exec([
            'url'      => rtrim($aArgs['config']['data']['url'], '/') . '/rest/documents/'.$aArgs['documentId'].'/status',
            'user'     => $aArgs['config']['data']['userId'],
            'password' => $aArgs['config']['data']['password'],
            'method'   => 'GET'
        ]);

        return $response['status'];
    }

    public static function getProcessedDocument(array $aArgs)
    {
        $response = CurlModel::exec([
            'url'      => rtrim($aArgs['config']['data']['url'], '/') . '/rest/documents/'.$aArgs['documentId'].'/processedDocument',
            'user'     => $aArgs['config']['data']['userId'],
            'password' => $aArgs['config']['data']['password'],
            'method'   => 'GET'
        ]);

        return $response['encodedDocument'];
    }
}
