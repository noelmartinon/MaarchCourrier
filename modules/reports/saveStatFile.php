<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   saveStatFile
*
* @author  dev <dev@maarch.org>
* @ingroup reports
*/
if (isset($_GET['filename'])) {
    //COPY FILE IN TMP PATH
    if (file_exists('custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'life_cycle'.DIRECTORY_SEPARATOR.'batch'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config_extract_data.xml')) {
        $xmlpath = 'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'life_cycle'.DIRECTORY_SEPARATOR.'batch'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config_extract_data.xml';
        $xml = simplexml_load_file($xmlpath);
        $path = $xml->CONFIG->ExportFolder;
    } else {
        if (file_exists('modules'.DIRECTORY_SEPARATOR.'life_cycle'.DIRECTORY_SEPARATOR.'batch'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config_extract_data.xml')) {
            $xmlpath = 'modules'.DIRECTORY_SEPARATOR.'life_cycle'.DIRECTORY_SEPARATOR.'batch'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config_extract_data.xml';
            $xml = simplexml_load_file($xmlpath);
            $path = $xml->CONFIG->ExportFolder;
        } else {
            if (!empty($_SESSION['custom_override_id'])) {
                $path = 'custom'.DIRECTORY_SEPARATOR.$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'life_cycle'.DIRECTORY_SEPARATOR.'batch'.DIRECTORY_SEPARATOR.'files/';
            } else {
                $path = 'modules'.DIRECTORY_SEPARATOR.'life_cycle'.DIRECTORY_SEPARATOR.'batch'.DIRECTORY_SEPARATOR.'files/';
            }
        }
    }

    if (!@copy($path."{$_GET['filename']}.csv", $_SESSION['config']['tmppath']."{$_GET['filename']}.csv")) {
        exit('We cannot retrieve this file ! (permission problem)');
    }

    //DOWNLOAD THE FILE
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: inline; filename="'.$_GET['filename'].'.csv"');
    ob_clean();
    flush();
    readfile($_SESSION['config']['tmppath']."{$_GET['filename']}.csv");
}
exit();
