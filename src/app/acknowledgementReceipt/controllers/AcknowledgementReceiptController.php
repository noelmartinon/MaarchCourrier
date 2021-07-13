<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Acknowledgement Receipt Controller
* @author dev@maarch.org
*/

namespace AcknowledgementReceipt\controllers;

use AcknowledgementReceipt\models\AcknowledgementReceiptModel;
use Docserver\models\DocserverModel;
use History\controllers\HistoryController;
use Resource\controllers\ResController;
use Resource\controllers\StoreController;
use Respect\Validation\Validator;
use setasign\Fpdi\Tcpdf\Fpdi;
use Slim\Http\Request;
use Slim\Http\Response;
use User\models\UserModel;

class AcknowledgementReceiptController
{
    public function createPaperAcknowledgement(Request $request, Response $response)
    {
        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);

        $bodyData = $request->getParsedBody();

        if (!Validator::arrayType()->notEmpty()->validate($bodyData['resources'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Resources is not set or empty']);
        }

        $bodyData['resources'] = array_slice($bodyData['resources'], 0, 500);

        $acknowledgements = AcknowledgementReceiptModel::getByIds([
            'select'  => ['res_id', 'docserver_id', 'path', 'filename', 'fingerprint', 'send_date', 'format'],
            'ids'     => $bodyData['resources'],
            'orderBy' => ['res_id']
        ]);

        $resourcesInBasket = [];
        foreach ($acknowledgements as $acknowledgement) {
            $resourcesInBasket[] = $acknowledgement['res_id'];
        }

        if (!ResController::hasRightByResId(['resId' => $resourcesInBasket, 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Documents out of perimeter']);
        }

        $pdf = new Fpdi('P', 'pt');
        $pdf->setPrintHeader(false);

        foreach ($acknowledgements as $value) {
            if (empty($value['send_date']) && $value['format'] == 'pdf') {
                $docserver = DocserverModel::getByDocserverId(['docserverId' => $value['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
                if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
                }
                $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $value['path']) . $value['filename'];
                if (!file_exists($pathToDocument)) {
                    return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
                }

                $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument]);
                if (!empty($value['fingerprint']) && $value['fingerprint'] != $fingerprint) {
                    return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
                }

                $nbPages = $pdf->setSourceFile($pathToDocument);
                for ($i = 1; $i <= $nbPages; $i++) {
                    $page = $pdf->importPage($i, 'CropBox');
                    $size = $pdf->getTemplateSize($page);
                    $pdf->AddPage($size['orientation'], $size);
                    $pdf->useImportedPage($page);
                }
            }
        }

        $fileContent = $pdf->Output('', 'S');
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "inline; filename=maarch.pdf");

        return $response->withHeader('Content-Type', $mimeType);
    }

    public function getAcknowledgementReceipt(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['userId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $document = AcknowledgementReceiptModel::getByIds([
            'select'  => ['docserver_id', 'path', 'filename', 'fingerprint'],
            'ids'     => [$aArgs['id']]
        ]);

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document[0]['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document[0]['path']) . $document[0]['filename'];

        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument]);
        if (!empty($document[0]['fingerprint']) && $document[0]['fingerprint'] != $fingerprint) {
            return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
        }

        $fileContent = file_get_contents($pathToDocument);

        if ($fileContent === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $pathInfo = pathinfo($pathToDocument);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "inline; filename=maarch.{$pathInfo['extension']}");

        HistoryController::add([
            'tableName' => 'acknowledgement_receipts',
            'recordId'  => $aArgs['id'],
            'eventType' => 'VIEW',
            'info'      => _ACKNOWLEDGEMENT_RECEIPT_DISPLAYING . " : {$aArgs['id']}",
            'moduleId'  => 'res',
            'eventId'   => 'acknowledgementreceiptview',
        ]);

        if ($mimeType == 'text/plain') {
            $mimeType = 'text/html';
        }

        return $response->withHeader('Content-Type', $mimeType);
    }
}
