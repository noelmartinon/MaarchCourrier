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
use History\controllers\HistoryController;
use Note\models\NoteModel;
use Priority\models\PriorityModel;
use Resource\controllers\SummarySheetController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use setasign\Fpdi\Tcpdf\Fpdi;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use User\controllers\UserController;
use User\models\UserModel;
use User\models\UserSignatureModel;

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
            if (!empty($aArgs['steps'])) {
                foreach ($aArgs['steps'] as $step) {
                    $workflow[] = ['userId' => $step['externalId'], 'mode' => $step['action']];
                }
            } else {
                return ['error' => 'steps is empty'];
            }

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
        
                    $attachmentsData = [];
                    if ($mainResource[0]['category_id'] != 'outgoing') {
                        $attachmentsData = [[
                            'encodedDocument' => $encodedMainZipFile,
                            'title'           => $mainResource[0]['subject'],
                            'reference'       => $mainResource[0]['alt_identifier']
                        ]];
                    }
                    $summarySheetEncodedZip = MaarchParapheurController::createZip(['filepath' => $filename, 'filename' => "summarySheet.pdf"]);
                    $attachmentsData[] = [
                        'encodedDocument' => $summarySheetEncodedZip,
                        'title'           => "summarySheet.pdf",
                        'reference'       => ""
                    ];
    
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
                        'encodedDocument' => $encodedZipDocument,
                        'sender'          => trim($sender['firstname'] . ' ' .$sender['lastname']),
                        'deadline'        => $processLimitDate,
                        'attachments'     => $attachmentsData,
                        'workflow'        => $workflow,
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

            $workflow = [['userId' => $processingUser, 'mode' => 'note']];
            $bodyData = [
                'title'            => $mainResource[0]['subject'],
                'reference'        => $mainResource[0]['alt_identifier'],
                'encodedDocument'  => $encodedMainZipFile,
                'sender'           => trim($sender['firstname'] . ' ' .$sender['lastname']),
                'deadline'         => $processLimitDate,
                'workflow'         => $workflow,
                'metadata'         => $metadata
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

        $workflowInfos = [];
        foreach ($workflow as $value) {
            $userInfos = UserModel::getByExternalId([
                'select'            => ['firstname', 'lastname'],
                'externalId'        => $value['userId'],
                'externalName'      => 'maarchParapheur'
            ]);
            if (empty($userInfos)) {
                $curlResponse = CurlModel::execSimple([
                    'url'           => rtrim($aArgs['config']['data']['url'], '/') . '/rest/users/'.$value['userId'],
                    'basicAuth'     => ['user' => $aArgs['config']['data']['userId'], 'password' => $aArgs['config']['data']['password']],
                    'headers'       => ['content-type:application/json'],
                    'method'        => 'GET'
                ]);
                $userInfos['firstname'] = $curlResponse['response']['user']['firstname'];
                $userInfos['lastname'] = $curlResponse['response']['user']['lastname'];
            }
            if ($value['mode'] == 'note') {
                $mode = _NOTE_USER;
            } elseif ($value['mode'] == 'visa') {
                $mode = _VISA_USER;
            } elseif ($value['mode'] == 'sign') {
                $mode = _SIGNATORY;
            }
            $workflowInfos[] = $userInfos['firstname'] . ' ' . $userInfos['lastname'] . ' ('. $mode .')';
        }
        if (!empty($workflowInfos)) {
            $historyInfos = ' ' . _WF_SEND_TO . ' ' . implode(", ", $workflowInfos);
        }

        return ['sended' => $attachmentToFreeze, 'historyInfos' => $historyInfos];
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
        foreach (['noVersion', 'isVersion', 'resLetterbox'] as $version) {
            foreach ($aArgs['idsToRetrieve'][$version] as $resId => $value) {
                $documentWorkflow = MaarchParapheurController::getDocumentWorkflow(['config' => $aArgs['config'], 'documentId' => $value->external_id]);
                $state = MaarchParapheurController::getState(['workflow' => $documentWorkflow]);
                
                if (in_array($state['status'], ['validated', 'refused'])) {
                    $signedDocument = MaarchParapheurController::getDocument(['config' => $aArgs['config'], 'documentId' => $value->external_id]);
                    $aArgs['idsToRetrieve'][$version][$resId]->format = 'pdf';
                    $aArgs['idsToRetrieve'][$version][$resId]->encodedFile = $signedDocument['encodedDocument'];
                    if ($state['status'] == 'validated' && in_array($state['mode'], ['sign', 'visa'])) {
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'validated';
                    } elseif ($state['status'] == 'refused' && in_array($state['mode'], ['sign', 'visa'])) {
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'refused';
                    } elseif ($state['status'] == 'validated' && $state['mode'] == 'note') {
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'validatedNote';
                    } elseif ($state['status'] == 'refused' && $state['mode'] == 'note') {
                        $aArgs['idsToRetrieve'][$version][$resId]->status = 'refusedNote';
                    }
                    if (!empty($state['note'])) {
                        $aArgs['idsToRetrieve'][$version][$resId]->noteContent = $state['note'];
                        $userInfos = UserModel::getByExternalId([
                            'select'            => ['user_id'],
                            'externalId'        => $state['noteCreatorId'],
                            'externalName'      => 'maarchParapheur'
                        ]);
                        if (!empty($userInfos)) {
                            $aArgs['idsToRetrieve'][$version][$resId]->noteCreatorId = $userInfos['user_id'];
                        } else {
                            $aArgs['idsToRetrieve'][$version][$resId]->noteCreatorName = $state['noteCreatorName'];
                        }
                    }
                    $aArgs['idsToRetrieve'][$version][$resId]->workflowInfo = implode(", ", $state['workflowInfo']);
                } else {
                    unset($aArgs['idsToRetrieve'][$version][$resId]);
                }
            }
        }

        // retourner seulement les mails récupérés (validés ou signés)
        return $aArgs['idsToRetrieve'];
    }

    public static function getDocumentWorkflow(array $aArgs)
    {
        $response = CurlModel::exec([
            'url'      => rtrim($aArgs['config']['data']['url'], '/') . '/rest/documents/'.$aArgs['documentId'].'/workflow',
            'user'     => $aArgs['config']['data']['userId'],
            'password' => $aArgs['config']['data']['password'],
            'method'   => 'GET'
        ]);

        return $response['workflow'];
    }

    public static function getDocument(array $aArgs)
    {
        $response = CurlModel::exec([
            'url'      => rtrim($aArgs['config']['data']['url'], '/') . '/rest/documents/'.$aArgs['documentId'].'/content',
            'user'     => $aArgs['config']['data']['userId'],
            'password' => $aArgs['config']['data']['password'],
            'method'   => 'GET'
        ]);

        return $response;
    }

    public static function getState($aArgs)
    {
        $state['status'] = 'validated';
        $state['workflowInfo'] = [];
        foreach ($aArgs['workflow'] as $step) {
            if ($step['status'] == 'VAL' && $step['mode'] == 'sign') {
                $state['workflowInfo'][] = $step['userDisplay'] . ' (Signé le ' . $step['processDate'] . ')';
            } elseif ($step['status'] == 'VAL' && $step['mode'] == 'visa') {
                $state['workflowInfo'][] = $step['userDisplay'] . ' (Visé le ' . $step['processDate'] . ')';
            }
            if ($step['status'] == 'REF') {
                $state['status']          = 'refused';
                $state['note']            = $step['note'];
                $state['noteCreatorId']   = $step['userId'];
                $state['noteCreatorName'] = $step['userDisplay'];
                $state['workflowInfo'][]  = $step['userDisplay'] . ' (Refusé le ' . $step['processDate'] . ')';
                break;
            } elseif (empty($step['status'])) {
                $state['status'] = 'inProgress';
                break;
            }
        }

        $state['mode'] = $step['mode'];
        return $state;
    }

    public static function getUserPicture(Request $request, Response $response, array $aArgs)
    {
        $check = Validator::intVal()->validate($aArgs['id']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'id should be an integer']);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);

        if ($loadedXml->signatoryBookEnabled == 'maarchParapheur') {
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == "maarchParapheur") {
                    $url      = $value->url;
                    $userId   = $value->userId;
                    $password = $value->password;
                    break;
                }
            }

            $curlResponse = CurlModel::execSimple([
                'url'           => rtrim($url, '/') . '/rest/users/'.$aArgs['id'].'/picture',
                'basicAuth'     => ['user' => $userId, 'password' => $password],
                'headers'       => ['content-type:application/json'],
                'method'        => 'GET'
            ]);

            if ($curlResponse['code'] != '200') {
                if (!empty($curlResponse['response']['errors'])) {
                    $errors =  $curlResponse['response']['errors'];
                } else {
                    $errors =  $curlResponse['errors'];
                }
                if (empty($errors)) {
                    $errors = 'An error occured. Please check your configuration file.';
                }
                return $response->withStatus(400)->withJson(['errors' => $errors]);
            }
        } else {
            return $response->withStatus(403)->withJson(['errors' => 'maarchParapheur is not enabled']);
        }

        return $response->withJson(['picture' => $curlResponse['response']['picture']]);
    }

    public static function sendUserToMaarchParapheur(Request $request, Response $response, array $aArgs)
    {
        $body = $request->getParsedBody();
        $check = Validator::stringType()->notEmpty()->validate($body['login']) && preg_match("/^[\w.@-]*$/", $body['login']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'login is empty or wrong format']);
        }
        
        $userController = new UserController();
        $error = $userController->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);

        if ($loadedXml->signatoryBookEnabled == 'maarchParapheur') {
            $userInfo = UserModel::getById(['select' => ['firstname', 'lastname', 'mail', 'external_id'], 'id' => $aArgs['id']]);

            $bodyData = [
                "lastname"  => $userInfo['lastname'],
                "firstname" => $userInfo['firstname'],
                "login"     => $body['login'],
                "email"     => $userInfo['mail']
            ];

            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == "maarchParapheur") {
                    $url      = $value->url;
                    $userId   = $value->userId;
                    $password = $value->password;
                    break;
                }
            }

            $curlResponse = CurlModel::execSimple([
                'url'           => rtrim($url, '/') . '/rest/users',
                'basicAuth'     => ['user' => $userId, 'password' => $password],
                'headers'       => ['content-type:application/json'],
                'method'        => 'POST',
                'body'          => json_encode($bodyData)
            ]);

            if ($curlResponse['code'] != '200') {
                if (!empty($curlResponse['response']['errors'])) {
                    $errors =  $curlResponse['response']['errors'];
                } else {
                    $errors =  $curlResponse['errors'];
                }
                if (empty($errors)) {
                    $errors = 'An error occured. Please check your configuration file.';
                }
                return $response->withStatus(400)->withJson(['errors' => $errors]);
            }
        } else {
            return $response->withStatus(403)->withJson(['errors' => 'maarchParapheur is not enabled']);
        }

        $externalId = json_decode($userInfo['external_id'], true);
        $externalId['maarchParapheur'] = $curlResponse['response']['id'];

        UserModel::updateExternalId(['id' => $aArgs['id'], 'externalId' => json_encode($externalId)]);

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['userId'],
            'eventType'    => 'ADD',
            'eventId'      => 'userCreation',
            'info'         => _USER_CREATED_IN_MAARCHPARAPHEUR . " {$userInfo['firstname']} {$userInfo['lastname']}"
        ]);

        return $response->withJson(['externalId' => $curlResponse['response']['id']]);
    }

    public static function linkUserToMaarchParapheur(Request $request, Response $response, array $aArgs)
    {
        $body = $request->getParsedBody();
        $check = Validator::intType()->notEmpty()->validate($body['maarchParapheurUserId']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'maarchParapheurUserId is empty or not an integer']);
        }
        
        $userController = new UserController();
        $error = $userController->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);

        if ($loadedXml->signatoryBookEnabled == 'maarchParapheur') {
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == "maarchParapheur") {
                    $url      = $value->url;
                    $userId   = $value->userId;
                    $password = $value->password;
                    break;
                }
            }

            $curlResponse = CurlModel::execSimple([
                'url'           => rtrim($url, '/') . '/rest/users/'.$body['maarchParapheurUserId'],
                'basicAuth'     => ['user' => $userId, 'password' => $password],
                'headers'       => ['content-type:application/json'],
                'method'        => 'GET'
            ]);

            if ($curlResponse['code'] != '200') {
                if (!empty($curlResponse['response']['errors'])) {
                    $errors =  $curlResponse['response']['errors'];
                } else {
                    $errors =  $curlResponse['errors'];
                }
                if (empty($errors)) {
                    $errors = 'An error occured. Please check your configuration file.';
                }
                return $response->withStatus(400)->withJson(['errors' => $errors]);
            }

            if (empty($curlResponse['response']['user'])) {
                return $response->withStatus(400)->withJson(['errors' => 'User does not exist in Maarch Parapheur']);
            }
        } else {
            return $response->withStatus(403)->withJson(['errors' => 'maarchParapheur is not enabled']);
        }

        $userInfos = UserModel::getByExternalId([
            'select'            => ['user_id'],
            'externalId'        => $body['maarchParapheurUserId'],
            'externalName'      => 'maarchParapheur'
        ]);

        if (!empty($userInfos)) {
            return $response->withStatus(403)->withJson(['errors' => 'This maarch parapheur user is already linked to someone. Choose another one.']);
        }

        $userInfo = UserModel::getById(['select' => ['external_id', 'firstname', 'lastname'], 'id' => $aArgs['id']]);

        $externalId = json_decode($userInfo['external_id'], true);
        $externalId['maarchParapheur'] = $body['maarchParapheurUserId'];

        UserModel::updateExternalId(['id' => $aArgs['id'], 'externalId' => json_encode($externalId)]);

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['userId'],
            'eventType'    => 'ADD',
            'eventId'      => 'userCreation',
            'info'         => _USER_LINKED_TO_MAARCHPARAPHEUR . " {$userInfo['firstname']} {$userInfo['lastname']}"
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public static function unlinkUserToMaarchParapheur(Request $request, Response $response, array $aArgs)
    {
        $userController = new UserController();
        $error = $userController->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $userInfo = UserModel::getById(['select' => ['external_id', 'firstname', 'lastname'], 'id' => $aArgs['id']]);

        $externalId = json_decode($userInfo['external_id'], true);
        unset($externalId['maarchParapheur']);

        UserModel::updateExternalId(['id' => $aArgs['id'], 'externalId' => json_encode($externalId)]);

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $GLOBALS['userId'],
            'eventType'    => 'ADD',
            'eventId'      => 'userCreation',
            'info'         => _USER_UNLINKED_TO_MAARCHPARAPHEUR . " {$userInfo['firstname']} {$userInfo['lastname']}"
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public static function userStatusInMaarchParapheur(Request $request, Response $response, array $aArgs)
    {
        $userController = new UserController();
        $error = $userController->hasUsersRights(['id' => $aArgs['id']]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);

        if ($loadedXml->signatoryBookEnabled == 'maarchParapheur') {
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == "maarchParapheur") {
                    $url      = $value->url;
                    $userId   = $value->userId;
                    $password = $value->password;
                    break;
                }
            }

            $userInfo = UserModel::getById(['select' => ['external_id->\'maarchParapheur\' as external_id'], 'id' => $aArgs['id']]);

            if (!empty($userInfo['external_id'])) {
                $curlResponse = CurlModel::execSimple([
                    'url'           => rtrim($url, '/') . '/rest/users/'.$userInfo['external_id'],
                    'basicAuth'     => ['user' => $userId, 'password' => $password],
                    'headers'       => ['content-type:application/json'],
                    'method'        => 'GET'
                ]);
            } else {
                return $response->withStatus(400)->withJson(['errors' => 'User does not have Maarch Parapheur Id']);
            }

            $errors = '';
            if ($curlResponse['code'] != '200') {
                if (!empty($curlResponse['response']['errors'])) {
                    $errors =  $curlResponse['response']['errors'];
                } else {
                    $errors =  $curlResponse['errors'];
                }
                if (empty($errors)) {
                    $errors = 'An error occured. Please check your configuration file.';
                }
            }

            if (empty($curlResponse['response']['user'])) {
                return $response->withJson(['link' => '', 'errors' => $errors]);
            }
        } else {
            return $response->withStatus(403)->withJson(['errors' => 'maarchParapheur is not enabled']);
        }

        return $response->withJson(['link' => $curlResponse['response']['user']['login'], 'errors' => '']);
    }

    public static function sendSignaturesToMaarchParapheur(Request $request, Response $response, array $aArgs)
    {
        $userController = new UserController();
        $error = $userController->hasUsersRights(['id' => $aArgs['id'], 'himself' => true]);
        if (!empty($error['error'])) {
            return $response->withStatus($error['status'])->withJson(['errors' => $error['error']]);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);

        if ($loadedXml->signatoryBookEnabled == 'maarchParapheur') {
            $userInfo   = UserModel::getById(['select' => ['external_id', 'user_id'], 'id' => $aArgs['id']]);
            $externalId = json_decode($userInfo['external_id'], true);

            if (!empty($externalId['maarchParapheur'])) {
                $userSignatures = UserSignatureModel::get([
                    'select'    => ['signature_path', 'signature_file_name', 'id'],
                    'where'     => ['user_serial_id = ?'],
                    'data'      => [$aArgs['id']]
                ]);
                if (empty($userSignatures)) {
                    return $response->withStatus(400)->withJson(['errors' => 'User has no signature']);
                }
        
                $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);
                if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Path for signature docserver does not exists']);
                }

                $signatures = [];
                $signaturesId = [];
                foreach ($userSignatures as $value) {
                    $pathToSignature = $docserver['path_template'] . str_replace('#', '/', $value['signature_path']) . $value['signature_file_name'];
                    if (is_file($pathToSignature)) {
                        $base64          = base64_encode(file_get_contents($pathToSignature));
                        $format          = pathinfo($pathToSignature, PATHINFO_EXTENSION);
                        $signatures[]    = ['encodedSignature' => $base64, 'format' => $format];
                        $signaturesId[]   = $value['id'];
                    } else {
                        return $response->withStatus(403)->withJson(['errors' => 'File does not exists : ' . $pathToSignature]);
                    }
                }

                $bodyData = [
                    "signatures"          => $signatures,
                    "externalApplication" => 'maarchCourrier'
                ];
    
                foreach ($loadedXml->signatoryBook as $value) {
                    if ($value->id == "maarchParapheur") {
                        $url      = $value->url;
                        $userId   = $value->userId;
                        $password = $value->password;
                        break;
                    }
                }

                $curlResponse = CurlModel::execSimple([
                    'url'           => rtrim($url, '/') . '/rest/users/' . $externalId['maarchParapheur'] . '/externalSignatures',
                    'basicAuth'     => ['user' => $userId, 'password' => $password],
                    'headers'       => ['content-type:application/json'],
                    'method'        => 'PUT',
                    'body'          => json_encode($bodyData)
                ]);
            } else {
                return $response->withStatus(403)->withJson(['errors' => 'user does not exists in maarch Parapheur']);
            }

            if ($curlResponse['code'] != '204') {
                if (!empty($curlResponse['response']['errors'])) {
                    $errors =  $curlResponse['response']['errors'];
                } else {
                    $errors =  $curlResponse['errors'];
                }
                if (empty($errors)) {
                    $errors = 'An error occured. Please check your configuration file.';
                }
                return $response->withStatus(400)->withJson(['errors' => $errors]);
            }
        } else {
            return $response->withStatus(403)->withJson(['errors' => 'maarchParapheur is not enabled']);
        }

        HistoryController::add([
            'tableName'    => 'users',
            'recordId'     => $userInfo['user_id'],
            'eventType'    => 'UP',
            'eventId'      => 'signatureSync',
            'info'         => _SIGNATURES_SEND_TO_MAARCHPARAPHEUR . " : " . implode(", ", $signaturesId)
        ]);

        return $response->withJson(['success' => 'success']);
    }
}
