<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Attachment Controller
* @author dev@maarch.org
*/

namespace Attachment\controllers;

use Attachment\models\AttachmentModel;
use Contact\models\ContactModel;
use Convert\controllers\ConvertPdfController;
use Convert\controllers\ConvertThumbnailController;
use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Group\models\ServiceModel;
use History\controllers\HistoryController;
use Resource\controllers\ResController;
use Resource\controllers\StoreController;
use Resource\models\ChronoModel;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use setasign\Fpdi\Tcpdf\Fpdi;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\AutoCompleteController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;
use Template\controllers\TemplateController;

class AttachmentController
{
    public function create(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (empty($body)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body is not set or empty']);
        } elseif (!Validator::notEmpty()->validate($body['encodedFile'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body encodedFile is empty']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['format'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body format is empty or not a string']);
        }

        $attachmentsTypes = AttachmentModel::getAttachmentsTypesByXML();
        $generateChrono = false;
        $mandatoryColumns = ['res_id_master', 'attachment_type'];
        foreach ($body['data'] as $key => $value) {
            foreach ($mandatoryColumns as $columnKey => $column) {
                if ($column == $value['column'] && !empty($value['value'])) {
                    if ($column == 'res_id_master') {
                        if (!ResController::hasRightByResId(['resId' => [$value['value']], 'userId' => $GLOBALS['userId']])) {
                            return $response->withStatus(403)->withJson(['errors' => 'ResId master out of perimeter']);
                        }
                        $resId = $value['value'];
                    } elseif ($column == 'attachment_type') {
                        if (empty($attachmentsTypes[$value['value']])) {
                            return $response->withStatus(400)->withJson(['errors' => 'Attachment Type does not exist']);
                        } elseif ($attachmentsTypes[$value['value']]['chrono']) {
                            $generateChrono = true;
                        }
                    }
                    unset($mandatoryColumns[$columnKey]);
                }
            }
            if (in_array($value['column'], ['identifier'])) {
                unset($body['data'][$key]);
            }
        }
        if (!empty($mandatoryColumns)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body data array needs column(s) [' . implode(', ', $mandatoryColumns) . ']']);
        } elseif (empty($resId)) {
            return $response->withStatus(400)->withJson(['errors' => 'ResId master is missing']);
        }

        if ($generateChrono) {
            $resource = ResModel::getById(['select' => ['destination', 'type_id'], 'resId' => $resId]);
            $chrono = ChronoModel::getChrono(['id' => 'outgoing', 'entityId' => $resource['destination'], 'typeId' => $resource['type_id'], 'resId' => $resId]);
            $body['data'][] = ['column' => 'identifier', 'value' => $chrono];
        }

        $body['table'] = empty($body['version']) ? 'res_attachments' : 'res_version_attachments';
        $body['status'] = 'A_TRA';
        $body['collId'] = 'letterbox_coll';
        $body['data'][] = ['column' => 'coll_id', 'value' => 'letterbox_coll'];
        $body['data'][] = ['column' => 'type_id', 'value' => '0'];
        $body['data'][] = ['column' => 'relation', 'value' => '1'];
        $body['fileFormat'] = $body['format'];
        $resId = StoreController::storeResourceRes($body);

        if (empty($resId) || !empty($resId['errors'])) {
            return $response->withStatus(500)->withJson(['errors' => '[AttachmentController create] ' . $resId['errors']]);
        }

        HistoryController::add([
            'tableName' => $body['table'],
            'recordId'  => $resId,
            'eventType' => 'ADD',
            'info'      => _DOC_ADDED,
            'moduleId'  => 'attachment',
            'eventId'   => 'attachmentAdd',
        ]);

        return $response->withJson(['resId' => $resId]);
    }

    public function getByResId(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $excludeAttachmentTypes = ['converted_pdf', 'print_folder'];
        if (!ServiceModel::hasService(['id' => 'view_documents_with_notes', 'userId' => $GLOBALS['userId'], 'location' => 'attachments', 'type' => 'use'])) {
            $excludeAttachmentTypes[] = 'document_with_notes';
        }

        $attachments = AttachmentModel::getListByResIdMaster([
            'resId'                     => $aArgs['resId'],
            'login'                     => $GLOBALS['userId'],
            'excludeAttachmentTypes'    => $excludeAttachmentTypes,
            'orderBy'                   => ['res_id DESC']
        ]);
        $attachmentsTypes = AttachmentModel::getAttachmentsTypesByXML();
        foreach ($attachments as $key => $attachment) {
            if (!empty($attachment['res_id_version'])) {
                $attachments[$key]['res_id'] = $attachment['res_id_version'];
            }
            $attachments[$key]['contact'] = '';
            if (!empty($attachment['dest_address_id'])) {
                $contact = ContactModel::getOnView([
                    'select' => [
                        'is_corporate_person', 'lastname', 'firstname',
                        'ca_id', 'society', 'contact_firstname', 'contact_lastname'
                    ],
                    'where' => ['ca_id = ?'],
                    'data'  => [$attachment['dest_address_id']]
                ]);
                if (!empty($contact[0])) {
                    $contact = AutoCompleteController::getFormattedContact(['contact' => $contact[0]]);
                    $attachments[$key]['contact'] = $contact['contact']['contact'];
                }
            }
            if (!empty($attachmentsTypes[$attachment['attachment_type']]['label'])) {
                $attachments[$key]['typeLabel'] = $attachmentsTypes[$attachment['attachment_type']]['label'];
            }
        }

        $mailevaConfig = CoreConfigModel::getMailevaConfiguration();
        $mailevaEnabled = false;
        if (!empty($mailevaConfig) && $mailevaConfig['enabled']) {
            $mailevaEnabled = true;
        }

        return $response->withJson(['attachments' => $attachments, 'mailevaEnabled' => $mailevaEnabled]);
    }

    public function setInSignatureBook(Request $request, Response $response, array $aArgs)
    {
        $body = $request->getParsedBody();

        $body['isVersion'] = filter_var($body['isVersion'], FILTER_VALIDATE_BOOLEAN);

        $attachment = AttachmentModel::getById(['id' => $aArgs['id'], 'isVersion' => $body['isVersion'], 'select' => ['in_signature_book', 'res_id_master']]);
        if (empty($attachment)) {
            return $response->withStatus(400)->withJson(['errors' => 'Attachment not found']);
        }

        if (!ResController::hasRightByResId(['resId' => [$attachment['res_id_master']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        AttachmentModel::setInSignatureBook(['id' => $aArgs['id'], 'isVersion' => $body['isVersion'], 'inSignatureBook' => !$attachment['in_signature_book']]);

        return $response->withJson(['success' => 'success']);
    }

    public function setInSendAttachment(Request $request, Response $response, array $aArgs)
    {
        $body = $request->getParsedBody();

        $body['isVersion'] = filter_var($body['isVersion'], FILTER_VALIDATE_BOOLEAN);

        $attachment = AttachmentModel::getById(['id' => $aArgs['id'], 'isVersion' => $body['isVersion'], 'select' => ['in_send_attach', 'res_id_master']]);
        if (empty($attachment)) {
            return $response->withStatus(400)->withJson(['errors' => 'Attachment not found']);
        }

        if (!ResController::hasRightByResId(['resId' => [$attachment['res_id_master']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        AttachmentModel::setInSendAttachment(['id' => $aArgs['id'], 'isVersion' => $body['isVersion'], 'inSendAttachment' => !$attachment['in_send_attach']]);

        return $response->withJson(['success' => 'success']);
    }

    public function getThumbnailContent(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !Validator::intVal()->validate($aArgs['resIdMaster']) || !ResController::hasRightByResId(['resId' => [$aArgs['resIdMaster']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $pathToThumbnail = 'apps/maarch_entreprise/img/noThumbnail.png';

        $attachment = AttachmentModel::getOnView([
            'select'    => ['res_id', 'res_id_version', 'docserver_id', 'path', 'filename'],
            'where'     => ['res_id = ? or res_id_version = ?', 'res_id_master = ?', 'status not in (?)'],
            'data'      => [$aArgs['resId'], $aArgs['resId'], $aArgs['resIdMaster'], ['DEL', 'OBS']],
            'limit'     => 1
        ]);

        if (empty($attachment[0])) {
            return $response->withStatus(403)->withJson(['errors' => 'Attachment not found']);
        }

        $attachmentTodisplay = $attachment[0];
        $isVersion = empty($attachmentTodisplay['res_id']);
        if ($isVersion) {
            $collId = "attachments_version_coll";
        } else {
            $collId = "attachments_coll";
        }

        $tnlAdr = AdrModel::getTypedAttachAdrByResId([
            'select'    => ['docserver_id', 'path', 'filename'],
            'resId'     => $aArgs['resId'],
            'type'      => 'TNL',
            'isVersion' => $isVersion
        ]);

        if (empty($tnlAdr)) {
            ConvertThumbnailController::convert(['collId' => $collId, 'resId' => $aArgs['resId'], 'isVersion' => $isVersion]);
            
            $tnlAdr = AdrModel::getTypedAttachAdrByResId([
                'select'    => ['docserver_id', 'path', 'filename'],
                'resId'     => $aArgs['resId'],
                'type'      => 'TNL',
                'isVersion' => $isVersion
            ]);
        }

        if (!empty($tnlAdr)) {
            $docserver = DocserverModel::getByDocserverId(['docserverId' => $tnlAdr['docserver_id'], 'select' => ['path_template']]);
            if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
            }

            $pathToThumbnail = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $tnlAdr['path']) . $tnlAdr['filename'];
        }

        $fileContent = file_get_contents($pathToThumbnail);
        if ($fileContent === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Thumbnail not found on docserver']);
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $pathInfo = pathinfo($pathToThumbnail);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "inline; filename=maarch.{$pathInfo['extension']}");

        return $response->withHeader('Content-Type', $mimeType);
    }
    
    public function getFileContent(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resIdMaster']) || !ResController::hasRightByResId(['resId' => [$aArgs['resIdMaster']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $attachment = AttachmentModel::getOnView([
            'select'    => ['res_id', 'res_id_version', 'docserver_id', 'path', 'filename', 'fingerprint'],
            'where'     => ['res_id = ? or res_id_version = ?', 'res_id_master = ?', 'status not in (?)'],
            'data'      => [$aArgs['resId'], $aArgs['resId'], $aArgs['resIdMaster'], ['DEL']],
            'limit'     => 1
        ]);

        if (empty($attachment[0])) {
            return $response->withStatus(403)->withJson(['errors' => 'Attachment not found']);
        }
   
        $attachmentTodisplay = $attachment[0];
        $id = (empty($attachmentTodisplay['res_id']) ? $attachmentTodisplay['res_id_version'] : $attachmentTodisplay['res_id']);
        $isVersion = empty($attachmentTodisplay['res_id']);

        $convertedAttachment = ConvertPdfController::getConvertedPdfById(['resId' => $id, 'collId' => 'attachments_coll', 'isVersion' => $isVersion]);
        if (empty($convertedAttachment['errors'])) {
            $attachmentTodisplay = $convertedAttachment;
        }
        $document['docserver_id'] = $attachmentTodisplay['docserver_id'];
        $document['path'] = $attachmentTodisplay['path'];
        $document['filename'] = $attachmentTodisplay['filename'];
        $document['fingerprint'] = $attachmentTodisplay['fingerprint'];

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Attachment not found on docserver']);
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint   = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if ($document['fingerprint'] != $fingerprint) {
            return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/attachments/xml/config.xml']);
        if ($loadedXml) {
            $watermark = (array)$loadedXml->CONFIG->watermark;
            if ($watermark['enabled'] == 'true') {
                $text = "watermark by {$GLOBALS['userId']}";
                if (!empty($watermark['text'])) {
                    $text = $watermark['text'];
                    preg_match_all('/\[(.*?)\]/i', $watermark['text'], $matches);

                    foreach ($matches[1] as $value) {
                        $tmp = '';
                        if ($value == 'date_now') {
                            $tmp = date('d-m-Y');
                        } elseif ($value == 'hour_now') {
                            $tmp = date('H:i');
                        } else {
                            $backFromView = AttachmentModel::getOnView(['select' => [$value], 'where' => ['res_id = ?'], 'data' => [$aArgs['resId']]]);
                            if (!empty($backFromView[0][$value])) {
                                $tmp = $backFromView[0][$value];
                            }
                        }
                        $text = str_replace("[{$value}]", $tmp, $text);
                    }
                }

                $color = ['192', '192', '192']; //RGB
                if (!empty($watermark['text_color'])) {
                    $rawColor = explode(',', $watermark['text_color']);
                    $color = count($rawColor) == 3 ? $rawColor : $color;
                }

                $font = ['helvetica', '10']; //Familly Size
                if (!empty($watermark['font'])) {
                    $rawFont = explode(',', $watermark['font']);
                    $font = count($rawFont) == 2 ? $rawFont : $font;
                }

                $position = [30, 35, 0, 0.5]; //X Y Angle Opacity
                if (!empty($watermark['position'])) {
                    $rawPosition = explode(',', $watermark['position']);
                    $position = count($rawPosition) == 4 ? $rawPosition : $position;
                }

                try {
                    $pdf = new Fpdi('P', 'pt');
                    $nbPages = $pdf->setSourceFile($pathToDocument);
                    $pdf->setPrintHeader(false);
                    for ($i = 1; $i <= $nbPages; $i++) {
                        $page = $pdf->importPage($i, 'CropBox');
                        $size = $pdf->getTemplateSize($page);
                        $pdf->AddPage($size['orientation'], $size);
                        $pdf->useImportedPage($page);
                        $pdf->SetFont($font[0], '', $font[1]);
                        $pdf->SetTextColor($color[0], $color[1], $color[2]);
                        $pdf->SetAlpha($position[3]);
                        $pdf->Rotate($position[2]);
                        $pdf->Text($position[0], $position[1], $text);
                    }
                    $fileContent = $pdf->Output('', 'S');
                } catch (\Exception $e) {
                    $fileContent = null;
                }
            }
        }

        if (empty($fileContent)) {
            $fileContent = file_get_contents($pathToDocument);
        }
        if ($fileContent === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $pathInfo = pathinfo($pathToDocument);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "inline; filename=maarch.{$pathInfo['extension']}");

        HistoryController::add([
            'tableName' => 'res_attachments',
            'recordId'  => $aArgs['resId'],
            'eventType' => 'VIEW',
            'info'      => _ATTACH_DISPLAYING . " : {$id}",
            'moduleId'  => 'attachments',
            'eventId'   => 'resview',
        ]);

        return $response->withHeader('Content-Type', $mimeType);
    }

    public function getOriginalFileContent(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $attachment = AttachmentModel::getOnView([
            'select'    => ['res_id', 'res_id_version', 'docserver_id', 'path', 'filename', 'fingerprint'],
            'where'     => ['res_id = ? or res_id_version = ?', 'res_id_master = ?', 'status not in (?)'],
            'data'      => [$args['id'], $args['id'], $args['resId'], ['DEL']],
            'limit'     => 1
        ]);
        if (empty($attachment[0])) {
            return $response->withStatus(403)->withJson(['errors' => 'Attachment not found']);
        }

        $attachmentTodisplay = $attachment[0];
        $id = (empty($attachmentTodisplay['res_id']) ? $attachmentTodisplay['res_id_version'] : $attachmentTodisplay['res_id']);

        $document['docserver_id'] = $attachmentTodisplay['docserver_id'];
        $document['path'] = $attachmentTodisplay['path'];
        $document['filename'] = $attachmentTodisplay['filename'];
        $document['fingerprint'] = $attachmentTodisplay['fingerprint'];

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];

        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Attachment not found on docserver']);
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (empty($document['fingerprint'])) {
            AttachmentModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$args['id']], 'isVersion' => !empty($attachmentTodisplay['res_id_version'])]);
            $document['fingerprint'] = $fingerprint;
        }

        if ($document['fingerprint'] != $fingerprint) {
            return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
        }

        if (empty($fileContent)) {
            $fileContent = file_get_contents($pathToDocument);
        }
        if ($fileContent === false) {
            return $response->withStatus(400)->withJson(['errors' => 'Document not found on docserver']);
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $pathInfo = pathinfo($pathToDocument);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "attachment; filename=maarch.{$pathInfo['extension']}");

        HistoryController::add([
            'tableName' => 'res_attachments',
            'recordId'  => $args['resId'],
            'eventType' => 'VIEW',
            'info'      => _ATTACH_DISPLAYING . " : {$id}",
            'moduleId'  => 'attachments',
            'eventId'   => 'resview',
        ]);

        return $response->withHeader('Content-Type', $mimeType);
    }

    public function getAttachmentsTypes(Request $request, Response $response)
    {
        $attachmentsTypes = AttachmentModel::getAttachmentsTypesByXML();

        return $response->withJson(['attachmentsTypes' => $attachmentsTypes]);
    }

    public static function getEncodedDocument(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::boolType($aArgs, ['original']);
        ValidatorModel::boolType($aArgs, ['isVersion']);

        $document = AttachmentModel::getById(['select' => ['docserver_id', 'path', 'filename', 'title', 'fingerprint'], 'id' => $aArgs['id'], 'isVersion' => $aArgs['isVersion']]);

        if (empty($aArgs['original'])) {
            $convertedDocument = ConvertPdfController::getConvertedPdfById(['resId' => $aArgs['id'], 'collId' => 'attachments_coll', 'isVersion' => $aArgs['isVersion']]);

            if (empty($convertedDocument['errors'])) {
                $document['docserver_id'] = $convertedDocument['docserver_id'];
                $document['path'] = $convertedDocument['path'];
                $document['filename'] = $convertedDocument['filename'];
                $document['fingerprint'] = $convertedDocument['fingerprint'];
            }
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => 'Docserver does not exist'];
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!file_exists($pathToDocument)) {
            return ['errors' => 'Document not found on docserver'];
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (empty($convertedDocument) && empty($document['fingerprint'])) {
            AttachmentModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$aArgs['id']], 'isVersion' => $aArgs['isVersion']]);
            $document['fingerprint'] = $fingerprint;
        }
        if ($document['fingerprint'] != $fingerprint) {
            return ['errors' => 'Fingerprints do not match'];
        }

        $fileContent = file_get_contents($pathToDocument);
        if ($fileContent === false) {
            return ['errors' => 'Document not found on docserver'];
        }

        $encodedDocument = base64_encode($fileContent);

        if (!empty($document['title'])) {
            $document['title'] = preg_replace(utf8_decode('@[\\/:*?"<>|]@i'), '_', substr($document['title'], 0, 30));
        }

        $pathInfo = pathinfo($pathToDocument);
        $fileName = (empty($document['title']) ? 'document' : $document['title']) . ".{$pathInfo['extension']}";

        return ['encodedDocument' => $encodedDocument, 'fileName' => $fileName];
    }

    public static function generateAttachForMailing(array $aArgs)
    {
        $attachments = AttachmentModel::getOnView([
            'select'    => ['*'],
            'where'     => ['res_id_master = ?', 'status = ?', 'in_signature_book = ?'],
            'data'      => [$aArgs['resIdMaster'], 'SEND_MASS', true]
        ]);

        $contactsForMailing = DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['contacts_res'],
            'where'     => ['res_id = ?', 'address_id <> 0'],
            'data'      => [$aArgs['resIdMaster']]
        ]);

        if (!empty($attachments[0])) {
            foreach ($attachments as $attachment) {
                if ($attachment['res_id_version'] <> 0) {
                    $resId = $attachment['res_id_version'];
                    $table = 'res_version_attachments';
                } else {
                    $resId = $attachment['res_id'];
                    $table = 'res_attachments';
                }

                $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'DOC', 'collId' => 'letterbox_coll', 'select' => ['path_template']]);
                $pathToAttachmentToCopy = $docserver['path_template'] . str_replace('#', '/', $attachment['path']) . $attachment['filename'];

                foreach ($contactsForMailing as $keyContact => $contactForMailing) {
                    $chronoPubli = $attachment['identifier'].'-'.($keyContact+1);

                    $dataValue = [];

                    $dataValue[] = [
                        'column'    => 'coll_id',
                        'value'     => 'letterbox_coll',
                        'type'      => 'string'
                    ];
                    array_push($dataValue, [
                        'column' => 'res_id_master',
                        'value' => $aArgs['resIdMaster'],
                        'type' => 'integer'
                    ]);
                    array_push($dataValue, [
                        'column' => 'attachment_type',
                        'value' => $attachment['attachment_type'],
                        'type' => 'string'
                    ]);
                    array_push($dataValue, [
                        'column' => 'identifier',
                        'value' => $chronoPubli,
                        'type' => 'string'
                    ]);
                    array_push($dataValue, [
                        'column' => 'title',
                        'value' => $attachment['title'],
                        'type' => 'string'
                    ]);
                    array_push($dataValue, [
                        'column' => 'type_id',
                        'value' => $attachment['type_id'],
                        'type' => 'integer'
                    ]);
                    array_push($dataValue, [
                        'column' => 'format',
                        'value' => $attachment['format'],
                        'type' => 'string'
                    
                    ]);
                    array_push($dataValue, [
                        'column' => 'typist',
                        'value' => $attachment['typist'],
                        'type' => 'string'
                    ]);
                    array_push($dataValue, [
                        'column' => 'relation',
                        'value' => $attachment['relation'],
                        'type' => 'integer'
                    ]);
                    array_push($dataValue, [
                        'column' => 'dest_contact_id',
                        'value' => $contactForMailing['contact_id'],
                        'type' => 'integer'
                    ]);
                    array_push($dataValue, [
                        'column' => 'dest_address_id',
                        'value' => $contactForMailing['address_id'],
                        'type' => 'integer'
                    ]);
                    array_push($dataValue, [
                        'column' => 'in_signature_book',
                        'value' => 'true',
                    ]);

                    $params = [
                        'userId'           => $aArgs['userId'],
                        'res_id'           => $aArgs['resIdMaster'],
                        'coll_id'          => 'letterbox_coll',
                        'res_view'         => 'res_view_attachments',
                        'res_table'        => 'res_attachments',
                        'res_contact_id'   => $contactForMailing['contact_id'],
                        'res_address_id'   => $contactForMailing['address_id'],
                        'pathToAttachment' => $pathToAttachmentToCopy,
                        'chronoAttachment' => $chronoPubli,
                    ];

                    $filePathOnTmp = TemplateController::mergeDatasource($params);

                    $allDatas = [
                        "encodedFile" => base64_encode(file_get_contents($filePathOnTmp)),
                        "data"        => $dataValue,
                        "collId"      => "letterbox_coll",
                        "table"       => "res_attachments",
                        "fileFormat"  => $attachment['format'],
                        "status"      => 'A_TRA'
                    ];

                    StoreController::storeResourceRes($allDatas);
                }
                
                AttachmentModel::update([
                    'table'     => $table,
                    'set'       => [
                        'status'  => 'DEL',
                    ],
                    'where'     => ['res_id = ?'],
                    'data'      => [$resId]
                ]);
            }
        }

        return ['success' => 'success'];
    }

    public static function isMailingAttach(array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resIdMaster']) || !ResController::hasRightByResId(['resId' => [$aArgs['resIdMaster']], 'userId' => $aArgs['userId']])) {
            return ['errors' => 'Document out of perimeter'];
        }

        $attachments = AttachmentModel::getOnView([
            'select' => ['res_id'],
            'where' => ['res_id_master = ?', 'status = ?'],
            'data' => [$aArgs['resIdMaster'],'SEND_MASS']
        ]);

        $return['nbAttach'] = count($attachments);

        if ($return['nbAttach'] == 0) {
            return false;
        }

        $return['nbContacts'] = ResModel::getNbContactsByResId(["resId" => $aArgs['resIdMaster']]);

        return $return;
    }
}
