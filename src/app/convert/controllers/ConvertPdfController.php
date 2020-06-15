<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Convert PDF Controller
 * @author dev@maarch.org
 */

namespace Convert\controllers;


use Attachment\models\AttachmentModel;
use Convert\models\AdrModel;
use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;

class ConvertPdfController
{
    public static function tmpConvert(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['fullFilename']);

        if (!file_exists($aArgs['fullFilename'])) {
            return ['errors' => '[ConvertPdf] Document '.$aArgs['fullFilename'].' does not exist'];
        }

        $docInfo = pathinfo($aArgs['fullFilename']);

        $tmpPath = CoreConfigModel::getTmpPath();


        $command = "unoconv -f pdf " . escapeshellarg($aArgs['fullFilename']);
        

        exec('export HOME=' . $tmpPath . ' && '.$command.' 2>&1', $output, $return);

        if (!file_exists($tmpPath.$docInfo["filename"].'.pdf')) {
            return ['errors' => '[ConvertPdf]  Conversion failed ! '. implode(" ", $output)];
        } else {
            return ['fullFilename' => $tmpPath.$docInfo["filename"].'.pdf'];
        }
    }

    public static function convert(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['collId', 'resId']);
        ValidatorModel::stringType($aArgs, ['collId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::boolType($aArgs, ['isVersion']);

        if ($aArgs['collId'] == 'letterbox_coll') {
            $resource = ResModel::getById(['resId' => $aArgs['resId'], 'select' => ['docserver_id', 'path', 'filename']]);
        } else {
            $resource = AttachmentModel::getById(['id' => $aArgs['resId'], 'isVersion' => $aArgs['isVersion'], 'select' => ['docserver_id', 'path', 'filename']]);
        }

        if (empty($resource)) {
            return ['errors' => '[ConvertPdf] Resource does not exist'];
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $resource['docserver_id'], 'select' => ['path_template']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => '[ConvertPdf] Docserver does not exist'];
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $resource['path']) . $resource['filename'];

        if (!file_exists($pathToDocument)) {
            return ['errors' => '[ConvertPdf] Document does not exist on docserver'];
        }

        $docInfo = pathinfo($pathToDocument);

        $tmpPath = CoreConfigModel::getTmpPath();
        $fileNameOnTmp = rand() . $docInfo["filename"];

        copy($pathToDocument, $tmpPath.$fileNameOnTmp.'.'.$docInfo["extension"]);

        if (strtolower($docInfo["extension"]) != 'pdf') {
    
            $command = "unoconv -f pdf " . escapeshellarg($tmpPath.$fileNameOnTmp.'.'.$docInfo["extension"]);
            exec('export HOME=' . $tmpPath . ' && '.$command, $output, $return);
    
            if (!file_exists($tmpPath.$fileNameOnTmp.'.pdf')) {
                return ['errors' => '[ConvertPdf]  Conversion failed ! '. implode(" ", $output)];
            }
        }
        
        $storeResult = DocserverController::storeResourceOnDocServer([
            'collId'    => $aArgs['collId'],
            'fileInfos' => [
                'tmpDir'        => $tmpPath,
                'tmpFileName'   => $fileNameOnTmp . '.pdf',
            ],
            'docserverTypeId'   => 'CONVERT'
        ]);

        if (!empty($storeResult['errors'])) {
            return ['errors' => "[ConvertPdf] {$storeResult['errors']}"];
        }

        if ($aArgs['collId'] == 'letterbox_coll') {
            AdrModel::createDocumentAdr([
                'resId'         => $aArgs['resId'],
                'type'          => 'PDF',
                'docserverId'   => $storeResult['docserver_id'],
                'path'          => $storeResult['destination_dir'],
                'filename'      => $storeResult['file_destination_name'],
                'fingerprint'   => $storeResult['fingerPrint']
            ]);
        } else {
            AdrModel::createAttachAdr([
                'resId'         => $aArgs['resId'],
                'isVersion'     => $aArgs['isVersion'],
                'type'          => 'PDF',
                'docserverId'   => $storeResult['docserver_id'],
                'path'          => $storeResult['destination_dir'],
                'filename'      => $storeResult['file_destination_name'],
                'fingerprint'   => $storeResult['fingerPrint']
            ]);
        }

        return ['docserver_id' => $storeResult['docserver_id'], 'path' => $storeResult['destination_dir'], 'filename' => $storeResult['file_destination_name'], 'fingerprint' => $storeResult['fingerPrint']];
    }

    public static function getConvertedPdfById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId', 'collId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::boolType($aArgs, ['isVersion']);
        ValidatorModel::arrayType($aArgs, ['select']);

        $convertedDocument = AdrModel::getConvertedDocumentById([
            'select'    => ['id', 'docserver_id','path', 'filename', 'fingerprint'],
            'resId'     => $aArgs['resId'],
            'collId'    => $aArgs['collId'],
            'type'      => 'PDF',
            'isVersion' => $aArgs['isVersion']
        ]);
        if (!empty($convertedDocument) && empty($convertedDocument['fingerprint'])) {
            $docserver = DocserverModel::getByDocserverId(['docserverId' => $convertedDocument['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
            $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedDocument['path']) . $convertedDocument['filename'];
            if (is_file($pathToDocument)) {
                $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
                $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
                if ($aArgs['collId'] == 'letterbox_coll') {
                    AdrModel::updateDocumentAdr(['set' => ['fingerprint' => $fingerprint], 'where' => ['id = ?'], 'data' => [$convertedDocument['id']]]);
                } else if ($aArgs['isVersion']) {
                    AdrModel::updateAttachmentVersionAdr(['set' => ['fingerprint' => $fingerprint], 'where' => ['id = ?'], 'data' => [$convertedDocument['id']]]);
                } else {
                    AdrModel::updateAttachmentAdr(['set' => ['fingerprint' => $fingerprint], 'where' => ['id = ?'], 'data' => [$convertedDocument['id']]]);
                }

                $convertedDocument['fingerprint'] = $fingerprint;
            }
        }

        if (empty($convertedDocument)) {
            $convertedDocument = ConvertPdfController::convert([
                'resId'     => $aArgs['resId'],
                'collId'    => $aArgs['collId'],
                'isVersion' => $aArgs['isVersion'],
            ]);
        }

        return $convertedDocument;
    }
}
