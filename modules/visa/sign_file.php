<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   sign_file
*
* @author  dev <dev@maarch.org>
* @ingroup visa
*/
require_once 'core/class/class_core_tools.php';
require_once 'core/class/class_db.php';
require_once 'core/core_tables.php';
require_once 'modules/attachments/attachments_tables.php';
require_once 'core/class/docservers_controler.php';
require_once 'core/docservers_tools.php';
require_once 'core/class/class_resource.php';

function writeLogIndex($EventInfo)
{
    $logFileOpened = fopen($_SESSION['config']['corepath'].'/modules/visa/log/signFile_'.date('Y').'_'.date('m').'_'.date('d').'.log', 'a');
    fwrite(
        $logFileOpened,
        '['.date('d').'/'.date('m').'/'.date('Y')
        .' '.date('H').':'.date('i').':'.date('s').'] '.$EventInfo
        ."\r\n"
    );
    fclose($logFileOpened);
}

$core_tools = new core_tools();
$core_tools->test_user();
$core_tools->load_lang();

if (!empty($_REQUEST['id']) && !empty($_REQUEST['collId'])) {
    $db = new Database();

    if (!empty($_REQUEST['resIdMaster'])) {
        $objectResIdMaster = $_REQUEST['resIdMaster'];
    }
    if (!empty($_REQUEST['signatureId'])) {
        $stmt = $db->query('select signature_path, signature_file_name FROM user_signatures WHERE id = ?', [$_REQUEST['signatureId']]);

        $signature = $stmt->fetchObject();
        if (empty($signature)) {
            $_SESSION['error'] = _IMG_SIGN_MISSING;
            echo '{"status":1, "error" : "'._IMG_SIGN_MISSING.'"}';
            exit;
        }

        $docserver = \Docserver\models\DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);
        $pathToWantedSignature = $docserver['path_template'] . str_replace('#', '/', $signature->signature_path) . $signature->signature_file_name;
    } else {
        $pathToWantedSignature = $_SESSION['user']['pathToSignature'][0];
    }

    $objectId = $_REQUEST['id'];
    $tableName = 'res_attachments';

    $stmt = $db->query("select relation, res_id, format, res_id_master, title, identifier, attachment_type from "
        . $tableName
        . " where res_id = ?", array($objectId));

    if ($stmt->rowCount() < 1) {
        echo '{"status":1, "error" : "'._FILE.' '._UNKNOWN.'"}';
        exit;
    } else {
        $line = $stmt->fetchObject();
        $_SESSION['visa']['last_resId_signed']['res_id'] = $line->res_id_master;
        $_SESSION['visa']['last_resId_signed']['title'] = $line->title;
        $_SESSION['visa']['last_resId_signed']['identifier'] = $line->identifier;
        $_SESSION['visa']['last_resId_signed']['type_id'] = $line->type_id;

        if ($line->attachment_type == 'response_project') {
            //Update outgoing date
            $date = date("Y-m-d");
            $db->query("update res_letterbox SET departure_date = ? where res_id = ?", array($date,$line->res_id_master));
        }

        $attachResId = $line->res_id;
        
        $convertedAttachment =  \Convert\controllers\ConvertPdfController::getConvertedPdfById(['resId' => $attachResId, 'collId' => 'attachments_coll']);
        if (!empty($convertedAttachment['errors'])) {
            echo "{\"status\":1, \"error\" : \""._ATTACH_PDF_NOT_FOUND . ": {$attachResId}, version : false\"}";
            exit;
        }

        $_SESSION['visa']['repSignRel'] = $line->relation;
        $_SESSION['visa']['repSignId'] = $attachResId;
        $docserver = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $convertedAttachment["docserver_id"], 'select' => ['path_template']]);

        $fileOnDs = $docserver["path_template"] . $convertedAttachment["path"] . $convertedAttachment["filename"];
        $fileOnDs = str_replace('#', DIRECTORY_SEPARATOR, $fileOnDs);
        $fileExtension = pathinfo($fileOnDs, PATHINFO_EXTENSION);
        $fileNameOnTmp = 'tmp_file_' . $_SESSION['user']['UserId']
            . '_' . rand() . '.' . $fileExtension;
        $filePathOnTmp = $_SESSION['config']['tmppath'] . $fileNameOnTmp;
        if (!copy($fileOnDs, $filePathOnTmp)) {
            echo "{\"status\":1, \"error\" : \""._FAILED_TO_COPY_ON_TMP . ": {$fileOnDs} {$filePathOnTmp}\"}";
            exit;
        }

        $tmpPathToWantedSignature = $pathToWantedSignature;
//        if ($core->test_service('use_date_in_signBlock', 'visa', false) == 1) {
//            $infoSignFile = pathinfo($pathToWantedSignature);
//
//            //GET SIGN FILE
//            if ($infoSignFile['extension'] == 'png') {
//                $source_image = @imagecreatefrompng($pathToWantedSignature);
//            } elseif ($infoSignFile['extension'] == 'jpeg' || $infoSignFile['extension'] == 'jpg') {
//                $source_image = @imagecreatefromjpeg($pathToWantedSignature);
//            } elseif ($infoSignFile['extension'] == 'gif') {
//                $source_image = @imagecreatefromgif($pathToWantedSignature);
//            }
//
//            $source_imagex = imagesx($source_image);
//            $source_imagey = imagesy($source_image);
//
//            $dest_imagex = $_SESSION['modules_loaded']['visa']['width_blocsign'] + 100;
//            $dest_imagey = $_SESSION['modules_loaded']['visa']['height_blocsign'] + 100;
//            $im2 = imagecreatetruecolor($dest_imagex, $dest_imagey);
//
//            imagecopyresampled($im2, $source_image, 0, 0, 0, 0, $dest_imagex, $dest_imagey, $source_imagex, $source_imagey);
//
//            $im = imagecreatetruecolor(imagesx($im2), imagesy($im2) + 30);
//            $white = imagecolorallocate($im, 255, 255, 255);
//            imagefilledrectangle($im, 0, 0, imagesx($im2), 30, $white);
//
//            $stmt = $db->query(
//                'select city from entities'
//                ." where (parent_entity_id IS NULL or parent_entity_id = '') and (city IS NOT NULL or city <> '')",
//                array()
//            );
//            $res = $stmt->fetchObject();
//            if (!empty($res->city)) {
//                $text = $res->city.', le '.date('d/m/Y');
//            } else {
//                $text = 'Le '.date('d/m/Y');
//            }
//
//            $font = 'modules/visa/LibraSerifModern-Regular.otf';
//
//            imagettftext($im, 14, 0, 10, 20, $black, $font, $text);
//
//            imagecopy($im, $im2, 0, 30, 0, 0, imagesx($im2), imagesy($im2));
//
//            $tmpPathToWantedSignature = $_SESSION['config']['tmppath'].'tmp_file_'.$_SESSION['user']['UserId'].'_'.rand().'.png';
//            imagepng($im, $tmpPathToWantedSignature);
//        }

        if (!file_exists($fileOnDs)) {
            echo "{\"status\":1, \"error\" : \"Fichier $fileOnDs non present\"}";
            exit;
        }
        $cmd = 'java -jar '
            .escapeshellarg($_SESSION['config']['corepath'].'modules/visa/dist/SignPdf.jar').' '
            .escapeshellarg($fileOnDs).' '
            .escapeshellarg($tmpPathToWantedSignature).' '
            .escapeshellarg($_SESSION['modules_loaded']['visa']['width_blocsign']).' '
            .escapeshellarg($_SESSION['modules_loaded']['visa']['height_blocsign']).' '
            .escapeshellarg($_SESSION['config']['tmppath']);

        //echo $cmd;
        exec($cmd);

        $tmpFileName = pathinfo($fileOnDs, PATHINFO_BASENAME);
        $fileExtension = 'pdf';

        include 'modules/visa/save_attach_res_from_cm.php';
        $db->query('UPDATE listinstance set signatory = TRUE WHERE listinstance_id = (SELECT listinstance_id FROM listinstance WHERE res_id = ? AND item_id = ? AND difflist_type = ? AND process_date is null ORDER BY listinstance_id LIMIT 1)', [$objectResIdMaster, $_SESSION['user']['UserId'], 'VISA_CIRCUIT']);

        echo "{\"status\": 0, \"new_id\": $id}";
        exit;
    }
} else {
    $_SESSION['error'] = _ATTACHMENT_ID_AND_COLL_ID_REQUIRED;
}
exit;