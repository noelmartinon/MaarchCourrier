<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Resource Controller
* @author dev@maarch.org
*/

namespace Resource\controllers;

use AcknowledgementReceipt\models\AcknowledgementReceiptModel;
use Attachment\models\AttachmentModel;
use Basket\models\BasketModel;
use Basket\models\RedirectBasketModel;
use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Convert\controllers\ConvertPdfController;
use Convert\controllers\ConvertThumbnailController;
use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Docserver\models\ResDocserverModel;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use Group\controllers\GroupController;
use Group\models\ServiceModel;
use History\controllers\HistoryController;
use Note\models\NoteModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Respect\Validation\Validator;
use setasign\Fpdi\Tcpdf\Fpdi;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;
use Status\models\StatusModel;
use User\models\UserModel;

class ResController
{
    //*****************************************************************************************
    //LOG ONLY LOG FOR DEBUG
    // ob_flush();
    // ob_start();
    // print_r($data);
    // file_put_contents("storeResourceLogs.log", ob_get_flush());
    //END LOG FOR DEBUG ONLY
    //*****************************************************************************************

    public function create(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'index_mlb', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (empty($body)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body is not set or empty']);
        } elseif (!Validator::notEmpty()->validate($body['encodedFile'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body encodedFile is empty']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['format'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body format is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['status'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body status is empty or not a string']);
        } elseif (!Validator::intVal()->notEmpty()->validate($body['type_id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body type_id is empty or not an integer']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['category_id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body category_id is empty or not a string']);
        }

        $resId = StoreController::storeResource($body);
        if (empty($resId) || !empty($resId['errors'])) {
            return $response->withStatus(500)->withJson(['errors' => '[ResController create] ' . $resId['errors']]);
        }

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $resId,
            'eventType' => 'ADD',
            'info'      => _DOC_ADDED,
            'moduleId'  => 'res',
            'eventId'   => 'resadd',
        ]);

        return $response->withJson(['resId' => $resId]);
    }

    public function createRes(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'index_mlb', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::notEmpty()->validate($data['encodedFile']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['fileFormat']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['status']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['collId']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['table']);
        $check = $check && Validator::arrayType()->notEmpty()->validate($data['data']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $mandatoryColumns = [];
        if ($data['table'] == 'res_letterbox') {
            $mandatoryColumns[] = 'type_id';
        }

        foreach ($data['data'] as $value) {
            foreach ($mandatoryColumns as $columnKey => $column) {
                if ($column == $value['column'] && !empty($value['value'])) {
                    unset($mandatoryColumns[$columnKey]);
                }
            }
        }
        if (!empty($mandatoryColumns)) {
            return $response->withStatus(400)->withJson(['errors' => 'Data array needs column(s) [' . implode(', ', $mandatoryColumns) . ']']);
        }

        $resId = StoreController::storeResourceRes($data);

        if (empty($resId) || !empty($resId['errors'])) {
            return $response->withStatus(500)->withJson(['errors' => '[ResController create] ' . $resId['errors']]);
        }

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $resId,
            'eventType' => 'ADD',
            'info'      => _DOC_ADDED,
            'moduleId'  => 'res',
            'eventId'   => 'resadd',
        ]);

        return $response->withJson(['resId' => $resId]);
    }

    public function createExt(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'index_mlb', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::intVal()->notEmpty()->validate($data['resId']);
        $check = $check && Validator::arrayType()->notEmpty()->validate($data['data']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $document = ResModel::getById(['resId' => $data['resId'], 'select' => ['1']]);
        if (empty($document)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document does not exist']);
        }
        $documentExt = ResModel::getExtById(['resId' => $data['resId'], 'select' => ['1']]);
        if (!empty($documentExt)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document already exists in mlb_coll_ext']);
        }

        $formatedData = StoreController::prepareExtStorage(['resId' => $data['resId'], 'data' => $data['data']]);

        $check = Validator::stringType()->notEmpty()->validate($formatedData['category_id']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        ResModel::createExt($formatedData);

        return $response->withJson(['status' => true]);
    }

    public function updateStatus(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (empty($data['status'])) {
            $data['status'] = 'COU';
        }
        if (empty(StatusModel::getById(['id' => $data['status']]))) {
            return $response->withStatus(400)->withJson(['errors' => _STATUS_NOT_FOUND]);
        }
        if (empty($data['historyMessage'])) {
            $data['historyMessage'] = _UPDATE_STATUS;
        }

        $check = Validator::arrayType()->notEmpty()->validate($data['chrono']) || Validator::arrayType()->notEmpty()->validate($data['resId']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['status']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['historyMessage']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $identifiers = !empty($data['chrono']) ? $data['chrono'] : $data['resId'];
        foreach ($identifiers as $id) {
            if (!empty($data['chrono'])) {
                $document = ResModel::getResIdByAltIdentifier(['altIdentifier' => $id]);
            } else {
                $document = ResModel::getById(['resId' => $id, 'select' => ['res_id']]);
            }
            if (empty($document)) {
                return $response->withStatus(400)->withJson(['errors' => _DOCUMENT_NOT_FOUND]);
            }
            if (!ResController::hasRightByResId(['resId' => [$document['res_id']], 'userId' => $GLOBALS['userId']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }
    
            ResModel::update(['set' => ['status' => $data['status']], 'where' => ['res_id = ?'], 'data' => [$document['res_id']]]);
    
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $document['res_id'],
                'eventType' => 'UP',
                'info'      => $data['historyMessage'],
                'moduleId'  => 'apps',
                'eventId'   => 'resup',
            ]);
        }

        return $response->withJson(['success' => 'success']);
    }

    public function getFileContent(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename', 'fingerprint'], 'resId' => $aArgs['resId']]);
        $extDocument = ResModel::getExtById(['select' => ['category_id', 'alt_identifier'], 'resId' => $aArgs['resId']]);
        if (empty($document) || empty($extDocument)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        if ($extDocument['category_id'] == 'outgoing') {
            $attachment = AttachmentModel::getOnView([
                'select'    => ['res_id', 'res_id_version', 'docserver_id', 'path', 'filename'],
                'where'     => ['res_id_master = ?', 'attachment_type = ?', 'status not in (?)'],
                'data'      => [$aArgs['resId'], 'outgoing_mail', ['DEL', 'OBS']],
                'limit'     => 1
            ]);
            if (!empty($attachment[0])) {
                $attachmentTodisplay = $attachment[0];
                $id = (empty($attachmentTodisplay['res_id']) ? $attachmentTodisplay['res_id_version'] : $attachmentTodisplay['res_id']);
                $isVersion = empty($attachmentTodisplay['res_id']);
                if ($isVersion) {
                    $collId = "attachments_version_coll";
                } else {
                    $collId = "attachments_coll";
                }
                $convertedDocument = ConvertPdfController::getConvertedPdfById(['select' => ['docserver_id', 'path', 'filename'], 'resId' => $id, 'collId' => $collId, 'isVersion' => $isVersion]);
                if (empty($convertedDocument['errors'])) {
                    $attachmentTodisplay = $convertedDocument;
                }
                $document['docserver_id'] = $attachmentTodisplay['docserver_id'];
                $document['path'] = $attachmentTodisplay['path'];
                $document['filename'] = $attachmentTodisplay['filename'];
                $document['fingerprint'] = $attachmentTodisplay['fingerprint'];
            }
        } else {
            $convertedDocument = ConvertPdfController::getConvertedPdfById(['select' => ['docserver_id', 'path', 'filename'], 'resId' => $aArgs['resId'], 'collId' => 'letterbox_coll', 'isVersion' => false]);

            if (empty($convertedDocument['errors'])) {
                $documentTodisplay = $convertedDocument;
                $document['docserver_id'] = $documentTodisplay['docserver_id'];
                $document['path'] = $documentTodisplay['path'];
                $document['filename'] = $documentTodisplay['filename'];
                $document['fingerprint'] = $documentTodisplay['fingerprint'];
            }
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];

        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (!empty($document['fingerprint']) && $document['fingerprint'] != $fingerprint) {
            return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/features.xml']);
        if ($loadedXml) {
            $watermark = (array)$loadedXml->FEATURES->watermark;
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
                        } elseif ($value == 'alt_identifier') {
                            $tmp = $extDocument['alt_identifier'];
                        } else {
                            $backFromView = ResModel::getOnView(['select' => $value, 'where' => ['res_id = ?'], 'data' => [$aArgs['resId']]]);
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

        ListInstanceModel::update([
            'postSet'   => ['viewed' => 'viewed + 1'],
            'where'     => ['item_id = ?', 'res_id = ?'],
            'data'      => [$GLOBALS['userId'], $aArgs['resId']]
        ]);
        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $aArgs['resId'],
            'eventType' => 'VIEW',
            'info'      => _DOC_DISPLAYING . " : {$aArgs['resId']}",
            'moduleId'  => 'res',
            'eventId'   => 'resview',
        ]);

        return $response->withHeader('Content-Type', $mimeType);
    }

    public function getOriginalFileContent(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename'], 'resId' => $aArgs['resId']]);
        $extDocument = ResModel::getExtById(['select' => ['category_id', 'alt_identifier'], 'resId' => $aArgs['resId']]);
        if (empty($document) || empty($extDocument)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        if ($extDocument['category_id'] == 'outgoing') {
            $attachment = AttachmentModel::getOnView([
                'select'    => ['res_id', 'res_id_version', 'docserver_id', 'path', 'filename', 'fingerprint'],
                'where'     => ['res_id_master = ?', 'attachment_type = ?', 'status not in (?)'],
                'data'      => [$aArgs['resId'], 'outgoing_mail', ['DEL', 'OBS']],
                'limit'     => 1
            ]);
            if (!empty($attachment[0])) {
                $document['docserver_id'] = $attachment[0]['docserver_id'];
                $document['path'] = $attachment[0]['path'];
                $document['filename'] = $attachment[0]['filename'];
                $document['fingerprint'] = $attachment[0]['fingerprint'];
            }
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (!empty($document['fingerprint']) && $document['fingerprint'] != $fingerprint) {
            return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
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
        $response = $response->withAddedHeader('Content-Disposition', "attachment; filename=maarch.{$pathInfo['extension']}");

        ListInstanceModel::update([
            'postSet'   => ['viewed' => 'viewed + 1'],
            'where'     => ['item_id = ?', 'res_id = ?'],
            'data'      => [$GLOBALS['userId'], $aArgs['resId']]
        ]);
        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $aArgs['resId'],
            'eventType' => 'VIEW',
            'info'      => _DOC_DISPLAYING . " : {$aArgs['resId']}",
            'moduleId'  => 'res',
            'eventId'   => 'resview',
        ]);

        return $response->withHeader('Content-Type', $mimeType);
    }

    public function getThumbnailContent(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $pathToThumbnail = 'apps/maarch_entreprise/img/noThumbnail.png';
        if (ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['userId']])) {
            $tnlAdr = AdrModel::getTypedDocumentAdrByResId([
                'select'    => ['docserver_id', 'path', 'filename'],
                'resId'     => $aArgs['resId'],
                'type'      => 'TNL'
            ]);
            if (empty($tnlAdr)) {
                $extDocument = ResModel::getExtById(['select' => ['category_id'], 'resId' => $aArgs['resId']]);
                if ($extDocument['category_id'] == 'outgoing') {
                    $attachment = AttachmentModel::getOnView([
                        'select'    => ['res_id', 'res_id_version'],
                        'where'     => ['res_id_master = ?', 'attachment_type = ?', 'status not in (?)'],
                        'data'      => [$aArgs['resId'], 'outgoing_mail', ['DEL', 'OBS']],
                        'limit'     => 1
                    ]);
                    if (!empty($attachment[0])) {
                        ConvertThumbnailController::convert([
                            'collId'            => 'letterbox_coll',
                            'resId'             => $aArgs['resId'],
                            'outgoingId'        => empty($attachment[0]['res_id']) ? $attachment[0]['res_id_version'] : $attachment[0]['res_id'],
                            'isOutgoingVersion' => empty($attachment[0]['res_id'])
                        ]);
                    }
                } else {
                    ConvertThumbnailController::convert(['collId' => 'letterbox_coll', 'resId' => $aArgs['resId']]);
                }
                $tnlAdr = AdrModel::getTypedDocumentAdrByResId([
                    'select'    => ['docserver_id', 'path', 'filename'],
                    'resId'     => $aArgs['resId'],
                    'type'      => 'TNL'
                ]);
            }

            if (!empty($tnlAdr)) {
                $docserver = DocserverModel::getByDocserverId(['docserverId' => $tnlAdr['docserver_id'], 'select' => ['path_template']]);
                if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
                }

                $pathToThumbnail = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $tnlAdr['path']) . $tnlAdr['filename'];
            }
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

    public function updateExternalInfos(Request $request, Response $response)
    {
        //TODO Revoir cette fonction
        $data = $request->getParams();

        if (empty($data['externalInfos'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request : externalInfos is empty']);
        }
        if (empty($data['status'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request : status is empty']);
        }

        foreach ($data['externalInfos'] as $mail) {
            if (!Validator::intType()->validate($mail['res_id'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: invalid res_id']);
            }
            if (!Validator::StringType()->notEmpty()->validate($mail['external_id'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: invalid external_id for element : '.$mail['res_id']]);
            }
            if (!Validator::StringType()->notEmpty()->validate($mail['external_link'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request:  invalid external_link for element'.$mail['res_id']]);
            }
        }

        foreach ($data['externalInfos'] as $mail) {
            $document = ResModel::getById(['resId' => $mail['res_id'], 'select' => ['res_id', 'external_id']]);
            if (empty($document)) {
                return $response->withStatus(400)->withJson(['errors' => _DOCUMENT_NOT_FOUND]);
            }
            if (!ResController::hasRightByResId(['resId' => [$document['res_id']], 'userId' => $GLOBALS['userId']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }
            $externalId = json_decode($document['external_id'], true);
            $externalId['publikId'] = $mail['external_id'];
            ResModel::update(['set' => ['external_id' => json_encode($externalId), 'external_link' => $mail['external_link'], 'status' => $data['status']], 'where' => ['res_id = ?'], 'data' => [$document['res_id']]]);
        }

        return $response->withJson(['success' => 'success']);
    }

    public function getNotesCountForCurrentUserById(Request $request, Response $response, array $aArgs)
    {
        return $response->withJson(NoteModel::countByResId(['resId' => $aArgs['resId'], 'login' => $GLOBALS['userId']]));
    }

    public function getContacts(Request $request, Response $response, array $args)
    {
        $ext = ResModel::getExtById(['resId' => $args['resId']]);

        if (empty($ext)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document does not exist']);
        }

        $queryParams = $request->getQueryParams();

        $contacts = [];
        if ($queryParams['type'] == 'senders') {
            if ($ext['category_id'] == 'outgoing') {
                $contacts = ResController::getFormattedContacts(['ext' => $ext, 'tableMulti' => 'resource_contacts', 'columnRes' => 'dest_user_id']);
            } else {
                $contacts = ResController::getFormattedContacts(['ext' => $ext, 'tableMulti' => 'contacts_res', 'columnRes' => 'exp_user_id']);
            }
        } elseif ($queryParams['type'] == 'recipients') {
            if ($ext['category_id'] == 'outgoing') {
                $contacts = ResController::getFormattedContacts(['ext' => $ext, 'tableMulti' => 'contact_res', 'columnRes' => 'exp_user_id']);
            } else {
                $contacts = ResController::getFormattedContacts(['ext' => $ext, 'tableMulti' => 'resource_contacts', 'columnRes' => null]);
            }
        }

        return $response->withJson(['contacts' => $contacts]);
    }

    public static function getEncodedDocument(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::boolType($aArgs, ['original']);

        $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename', 'subject'], 'resId' => $aArgs['resId']]);
        $extDocument = ResModel::getExtById(['select' => ['category_id'], 'resId' => $aArgs['resId']]);

        if (empty($aArgs['original'])) {
            if ($extDocument['category_id'] == 'outgoing') {
                $attachment = AttachmentModel::getOnView([
                    'select'    => ['res_id', 'res_id_version', 'docserver_id', 'path', 'filename'],
                    'where'     => ['res_id_master = ?', 'attachment_type = ?', 'status not in (?)'],
                    'data'      => [$aArgs['resId'], 'outgoing_mail', ['DEL', 'OBS']],
                    'limit'     => 1
                ]);
                if (!empty($attachment[0])) {
                    $attachmentTodisplay = $attachment[0];
                    $id = (empty($attachmentTodisplay['res_id']) ? $attachmentTodisplay['res_id_version'] : $attachmentTodisplay['res_id']);
                    $isVersion = empty($attachmentTodisplay['res_id']);
                    if ($isVersion) {
                        $collId = "attachments_version_coll";
                    } else {
                        $collId = "attachments_coll";
                    }
                    $convertedDocument = ConvertPdfController::getConvertedPdfById(['resId' => $id, 'collId' => $collId, 'isVersion' => $isVersion]);
                    if (empty($convertedDocument['errors'])) {
                        $attachmentTodisplay = $convertedDocument;
                    }
                    $document['docserver_id'] = $attachmentTodisplay['docserver_id'];
                    $document['path'] = $attachmentTodisplay['path'];
                    $document['filename'] = $attachmentTodisplay['filename'];
                    $document['fingerprint'] = $attachmentTodisplay['fingerprint'];
                }
            } else {
                $convertedDocument = ConvertPdfController::getConvertedPdfById(['resId' => $aArgs['resId'], 'collId' => 'letterbox_coll', 'isVersion' => false]);

                if (empty($convertedDocument['errors'])) {
                    $document['docserver_id'] = $convertedDocument['docserver_id'];
                    $document['path'] = $convertedDocument['path'];
                    $document['filename'] = $convertedDocument['filename'];
                    $document['fingerprint'] = $convertedDocument['fingerprint'];
                }
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
        if (!empty($document['fingerprint']) && $document['fingerprint'] != $fingerprint) {
            ['errors' => 'Fingerprints do not match'];
        }

        $fileContent = file_get_contents($pathToDocument);
        if ($fileContent === false) {
            return ['errors' => 'Document not found on docserver'];
        }

        $encodedDocument = base64_encode($fileContent);

        if (!empty($document['subject'])) {
            $document['subject'] = preg_replace(utf8_decode('@[\\/:*?"<>|]@i'), '_', substr($document['subject'], 0, 30));
        }

        $pathInfo = pathinfo($pathToDocument);
        $fileName = (empty($document['subject']) ? 'document' : $document['subject']) . ".{$pathInfo['extension']}";

        return ['encodedDocument' => $encodedDocument, 'fileName' => $fileName];
    }

    public static function hasRightByResId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId', 'userId']);
        ValidatorModel::stringType($aArgs, ['userId']);
        ValidatorModel::arrayType($aArgs, ['resId']);

        $aArgs['resId'] = array_unique($aArgs['resId']);
        $nbResId = count($aArgs['resId']);

        if ($aArgs['userId'] == 'superadmin') {
            return true;
        }
        $groups = UserModel::getGroupsByUserId(['userId' => $aArgs['userId']]);
        $groupsClause = '';
        foreach ($groups as $key => $group) {
            if (!empty($group['where_clause'])) {
                $groupClause = PreparedClauseController::getPreparedClause(['clause' => $group['where_clause'], 'login' => $aArgs['userId']]);
                if ($key > 0) {
                    $groupsClause .= ' or ';
                }
                $groupsClause .= "({$groupClause})";
            }
        }

        if (!empty($groupsClause)) {
            $res = ResModel::getOnView(['select' => [1], 'where' => ['res_id in (?)', "({$groupsClause})"], 'data' => [$aArgs['resId']]]);
            if (!empty($res) && count($res) == $nbResId) {
                return true;
            }
        }

        $baskets = BasketModel::getBasketsByLogin(['login' => $aArgs['userId'], 'unneededBasketId' => ['IndexingBasket']]);
        $basketsClause = '';
        foreach ($baskets as $basket) {
            if (!empty($basket['basket_clause'])) {
                $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $aArgs['userId']]);
                if (!empty($basketsClause)) {
                    $basketsClause .= ' or ';
                }
                $basketsClause .= "({$basketClause})";
            }
        }
        $user = UserModel::getByLogin(['login' => $aArgs['userId'], 'select' => ['id']]);
        $assignedBaskets = RedirectBasketModel::getAssignedBasketsByUserId(['userId' => $user['id']]);
        foreach ($assignedBaskets as $basket) {
            if (!empty($basket['basket_clause'])) {
                $basketOwner = UserModel::getById(['id' => $basket['owner_user_id'], 'select' => ['user_id']]);
                $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $basketOwner['user_id']]);
                if (!empty($basketsClause)) {
                    $basketsClause .= ' or ';
                }
                $basketsClause .= "({$basketClause})";
            }
        }

        if (!empty($basketsClause)) {
            try {
                $res = ResModel::getOnView(['select' => [1], 'where' => ['res_id in (?)', "({$basketsClause})"], 'data' => [$aArgs['resId']]]);
                if (!empty($res) && count($res) == $nbResId) {
                    return true;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    public function getList(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (!Validator::stringType()->notEmpty()->validate($data['select'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request: select is not valid']);
        }
        if (!Validator::stringType()->notEmpty()->validate($data['clause'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request: clause is not valid']);
        }
        if (!empty($data['withFile'])) {
            if (!Validator::boolType()->validate($data['withFile'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: withFile parameter is not a boolean']);
            }
        }

        if (!empty($data['orderBy'])) {
            if (!Validator::arrayType()->notEmpty()->validate($data['orderBy'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: orderBy parameter not valid']);
            }
        }

        if (!empty($data['limit'])) {
            if (!Validator::intType()->validate($data['limit'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: limit parameter not valid']);
            }
        }
        $select = explode(',', $data['select']);

        $sve_start_date = false;
        $keySve = array_search('sve_start_date', array_map('trim', $select));
        if ($keySve !== false) {
            unset($select[$keySve]);
            $sve_start_date = true;
        }
        
        if ($sve_start_date && empty($select)) {
            $select[] = 'res_id';
        }

        if (!PreparedClauseController::isRequestValid(['select' => $select, 'clause' => $data['clause'], 'orderBy' => $data['orderBy'], 'limit' => $data['limit'], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(400)->withJson(['errors' => _INVALID_REQUEST]);
        }

        $where = [$data['clause']];
        if ($GLOBALS['userId'] != 'superadmin') {
            $groupsClause = GroupController::getGroupsClause(['userId' => $GLOBALS['userId']]);
            if (empty($groupsClause)) {
                return $response->withStatus(400)->withJson(['errors' => 'User has no groups']);
            }
            $where[] = "({$groupsClause})";
        }

        if ($data['withFile'] === true) {
            $select[] = 'res_id';
        }

        $resources = ResModel::getOnView(['select' => $select, 'where' => $where, 'orderBy' => $data['orderBy'], 'limit' => $data['limit']]);
        if (!empty($resources) && $data['withFile'] === true) {
            foreach ($resources as $key => $res) {
                $path = ResDocserverModel::getSourceResourcePath(['resId' => $res['res_id'], 'resTable' => 'res_letterbox', 'adrTable' => 'null']);
                $file = file_get_contents($path);
                $base64Content = base64_encode($file);
                $resources[$key]['fileBase64Content'] = $base64Content;
            }
        }
        if (!empty($resources) && $sve_start_date) {
            $aResId = [];
            foreach ($resources as $res) {
                $aResId[] = $res['res_id'];
            }
            $aSveStartDate = AcknowledgementReceiptModel::getByResIds([
                'select'  => ['res_id', 'min(send_date) as send_date'],
                'resIds'  => $aResId,
                'where'   => ['send_date IS NOT NULL', 'send_date != \'\''],
                'groupBy' => ['res_id']
            ]);
            foreach ($resources as $key => $res) {
                $resources[$key]['sve_start_date'] = null;
                foreach ($aSveStartDate as $valueSveStartDate) {
                    if ($res['res_id'] == $valueSveStartDate['res_id']) {
                        $resources[$key]['sve_start_date'] = $valueSveStartDate['send_date'];
                        break;
                    }
                }
            }
        }

        return $response->withJson(['resources' => $resources, 'count' => count($resources)]);
    }

    public function getCategories(Request $request, Response $response)
    {
        return $response->withJson(['categories' => ResModel::getCategories()]);
    }

    public function getNatures(Request $request, Response $response)
    {
        return $response->withJson(['natures' => ResModel::getNatures()]);
    }

    public function isAllowedForCurrentUser(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['userId']])) {
            return $response->withJson(['isAllowed' => false]);
        }

        return $response->withJson(['isAllowed' => true]);
    }

    public static function getFormattedContacts(array $args)
    {
        ValidatorModel::notEmpty($args, ['ext', 'tableMulti']);
        ValidatorModel::arrayType($args, ['ext']);
        ValidatorModel::stringType($args, ['tableMulti', 'columnRes']);

        $ext = $args['ext'];

        $rawContacts = [];
        if ($ext['is_multicontacts'] == 'Y' || !isset($args['columnRes'])) {
            if ($args['tableMulti'] == 'contacts_res') {
                $multiContacts = DatabaseModel::select([
                    'select' => ['contact_id', 'address_id', 'mode'],
                    'table' => ['contacts_res'],
                    'where' => ['res_id = ?'],
                    'data' => [$ext['res_id']]
                ]);

                foreach ($multiContacts as $multiContact) {
                    $rawContacts[] = [
                        'login'         => $multiContact['contact_id'],
                        'address_id'    => $multiContact['address_id'],
                        'mode'          => $multiContact['mode']
                    ];
                }
            } elseif ($args['tableMulti'] == 'resource_contacts') {
                $multiContacts = DatabaseModel::select([
                    'select' => ['item_id', 'type', 'mode'],
                    'table' => ['resource_contacts'],
                    'where' => ['res_id = ?'],
                    'data' => [$ext['res_id']]
                ]);

                foreach ($multiContacts as $multiContact) {
                    $rawContacts[] = [
                        'user_id' => $multiContact['type'] == 'user' ? $multiContact['item_id'] : null,
                        'address_id' => $multiContact['type'] == 'contact' ? $multiContact['item_id'] : null,
                        'entity_id' => $multiContact['type'] == 'entity' ? $multiContact['item_id'] : null,
                    ];
                }
            }
        } else {
            $rawContacts[] = [
                'login'         => $ext[$args['columnRes']],
                'address_id'    => $ext['address_id'],
            ];
        }

        $contacts = [];

        foreach ($rawContacts as $rawContact) {
            if (!empty($rawContact['address_id'])) {
                $contactView = ContactModel::getOnView([
                    'select' => [
                        'is_corporate_person', 'lastname', 'firstname', 'address_num', 'address_street', 'address_complement',
                        'address_town', 'address_postal_code', 'address_country', 'ca_id', 'society', 'website', 'phone',
                        'contact_firstname', 'contact_lastname', 'address_country', 'email', 'function', 'contact_other_data',
                        'occupancy'
                    ],
                    'where'     => ['ca_id = ?'],
                    'data'      => [$rawContact['address_id']]
                ]);

                $contactView = $contactView[0];

                if (!empty($rawContact['mode']) && $rawContact['mode'] == 'third') {
                    $mode = 'third';
                } else {
                    $mode = $contactView['is_corporate_person'] == 'Y' ? 'corporate' : 'physical';
                }

                $contact = [
                    'mode'      => $mode,
                    'firstname' => $contactView['firstname'] ?? '',
                    'lastname'  => $contactView['lastname'] ?? '',
                    'email'     => $contactView['email'] ?? '',
                    'phone'     => $contactView['phone'] ?? '',
                    'society'   => $contactView['society'] ?? '',
                    'function'  => $contactView['function'] ?? '',
                    'num'       => $contactView['address_num'] ?? '',
                    'street'    => $contactView['address_street'] ?? '',
                    'complement'=> $contactView['address_complement'] ?? '',
                    'town'      => $contactView['address_town'] ?? '',
                    'postalCode'=> $contactView['address_postal_code'] ?? '',
                    'country'   => $contactView['address_country'] ?? '',
                    'otherData' => $contactView['contact_other_data'] ?? '',
                    'website'   => $contactView['website'] ?? '',
                    'occupancy' => $contactView['occupancy'] ?? '',
                    'department' => $contactView['departement'] ?? ''
                ];

                $filling = ContactController::getFillingRate(['contact' => $contact]);

                $contact['filling'] = $filling['color'];

                $contacts[] = $contact;
            } elseif (!empty($rawContact['login'] || !empty($rawContact['user_id']))) {
                if (!empty($rawContact['login'])) {
                    $user = UserModel::getByLowerLogin(['login' => $rawContact['login']]);
                } else {
                    $user = UserModel::getById(['id' => $rawContact['user_id']]);
                }

                $phone = '';
                if (!empty($phone)) {
                    $phone = $user['phone'];
                }

                $primaryEntity = UserModel::getPrimaryEntityById(['id' => $user['id']]);

                $userEntities = UserModel::getNonPrimaryEntitiesById(['id' => $user['id']]);
                $userEntities = array_column($userEntities, 'entity_label');

                $nonPrimaryEntities = implode(', ', $userEntities);

                $contact = [
                    'mode'      => 'internal',
                    'firstname' => $user['firstname'],
                    'lastname'  => $user['lastname'],
                    'email'     => $user['mail'],
                    'phone'     => $phone,
                    'society'   => '',
                    'function'  => '',
                    'num'       => '',
                    'street'    => '',
                    'complement'=> '',
                    'town'      => '',
                    'postalCode'=> '',
                    'country'   => '',
                    'otherData' => '',
                    'website'   => '',
                    'occupancy' => $nonPrimaryEntities,
                    'department' => $primaryEntity['entity_label']
                ];

                $filling = ContactController::getFillingRate(['contact' => $contact]);

                $contact['filling'] = $filling['color'];

                $contacts[] = $contact;
            } elseif (!empty($rawContact['entity_id'])) {
                $entity = EntityModel::getById(['id' => $rawContact['entity_id']]);

                $contact = [
                    'mode'      => 'entity',
                    'firstname' => '',
                    'lastname'  => $entity['entity_label'],
                    'email'     => $entity['email'],
                    'phone'     => '',
                    'society'   => '',
                    'function'  => '',
                    'num'       => '',
                    'street'    => '',
                    'complement'=> '',
                    'town'      => '',
                    'postalCode'=> '',
                    'country'   => '',
                    'otherData' => '',
                    'website'   => '',
                    'occupancy' => '',
                    'department' => ''
                ];

                $filling = ContactController::getFillingRate(['contact' => $contact]);

                $contact['filling'] = $filling['color'];

                $contacts[] = $contact;
            }
        }

        return $contacts;
    }
}
