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

        self::addBom($aArgs['fullFilename']);
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
            $resource = ResModel::getById(['resId' => $aArgs['resId'], 'select' => ['docserver_id', 'path', 'filename', 'format']]);
        } else {
            $resource = AttachmentModel::getById(['id' => $aArgs['resId'], 'isVersion' => $aArgs['isVersion'], 'select' => ['docserver_id', 'path', 'filename', 'format']]);
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
        if (empty($docInfo['extension'])) {
            $docInfo['extension'] = $resource['format'];
        }

        $canConvert = ConvertPdfController::canConvert(['extension' => $docInfo['extension']]);
        if (!$canConvert) {
            return ['docserver_id' => $resource['docserver_id'], 'path' => $resource['path'], 'filename' => $resource['filename']];
        }

        $tmpPath = CoreConfigModel::getTmpPath();
        $fileNameOnTmp = rand() . $docInfo["filename"];

        copy($pathToDocument, $tmpPath.$fileNameOnTmp.'.'.$docInfo["extension"]);

        if (strtolower($docInfo["extension"]) != 'pdf') {
            self::addBom($tmpPath.$fileNameOnTmp.'.'.$docInfo["extension"]);
            $command = "unoconv -f pdf " . escapeshellarg($tmpPath.$fileNameOnTmp.'.'.$docInfo["extension"]);
            exec('export HOME=' . $tmpPath . ' && '.$command, $output, $return);

            if (!file_exists($tmpPath.$fileNameOnTmp.'.pdf')) {
                return ['errors' => '[ConvertPdf]  Conversion failed ! '. implode(" ", $output)];
            }
        }

        $resource = file_get_contents("{$tmpPath}{$fileNameOnTmp}.pdf");
        $storeResult = DocserverController::storeResourceOnDocServer([
            'collId'            => $aArgs['collId'],
            'docserverTypeId'   => 'CONVERT',
            'encodedResource'   => base64_encode($resource),
            'format'            => 'pdf'
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

        return ['docserver_id' => $storeResult['docserver_id'], 'path' => $storeResult['destination_dir'], 'filename' => $storeResult['file_destination_name']];
    }

    public static function convertFromEncodedResource(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['encodedResource']);
        ValidatorModel::stringType($aArgs, ['encodedResource']);

        $tmpPath = CoreConfigModel::getTmpPath();
        $tmpFilename = 'converting' . rand();

        file_put_contents($tmpPath . $tmpFilename, base64_decode($aArgs['encodedResource']));

        self::addBom($tmpPath.$tmpFilename);
        $command = "unoconv -f pdf {$tmpPath}{$tmpFilename}";
        exec('export HOME=' . $tmpPath . ' && '.$command, $output, $return);

        if (!file_exists($tmpPath.$tmpFilename.'.pdf')) {
            return ['errors' => '[ConvertPdf]  Conversion failed ! '. implode(" ", $output)];
        }

        $resource = file_get_contents("{$tmpPath}{$tmpFilename}.pdf");
        unlink("{$tmpPath}{$tmpFilename}");
        unlink("{$tmpPath}{$tmpFilename}.pdf");

        return base64_encode($resource);
    }

    public static function getConvertedPdfById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId', 'collId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::boolType($aArgs, ['isVersion']);

        $convertedDocument = AdrModel::getConvertedDocumentById([
            'select'    => ['docserver_id','path', 'filename', 'fingerprint'],
            'resId'     => $aArgs['resId'],
            'collId'    => $aArgs['collId'],
            'type'      => 'PDF',
            'isVersion' => $aArgs['isVersion']
        ]);
        
        if (empty($convertedDocument)) {
            $convertedDocument = ConvertPdfController::convert([
                'resId'     => $aArgs['resId'],
                'collId'    => $aArgs['collId'],
                'isVersion' => $aArgs['isVersion'],
            ]);
        }

        return $convertedDocument;
    }

    private static function canConvert(array $args)
    {
        ValidatorModel::notEmpty($args, ['extension']);
        ValidatorModel::stringType($args, ['extension']);

        $canConvert = false;
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/extensions.xml']);
        if ($loadedXml) {
            foreach ($loadedXml->FORMAT as $value) {
                if (strtoupper((string)$value->name) == strtoupper($args['extension']) && (string)$value->index_frame_show == 'true') {
                    $canConvert = true;
                }
            }
        }

        return $canConvert;
    }

    public static function addBom($filePath) {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (strtolower($extension) == strtolower('txt')) {
            $content = file_get_contents($filePath);
            $bom = chr(239) . chr(187) . chr(191); # use BOM to be on safe side
            file_put_contents($filePath, $bom.$content);
        }
    }
}
