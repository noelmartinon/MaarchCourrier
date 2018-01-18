<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   getStatFile
*
* @author  dev <dev@maarch.org>
* @ingroup reports
*/
$status = 1;
$content = '';

//GET XML CONFIG STAT FILES PATH
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

$dir = $path.'*.csv';

//GET STAT FILES
$files = glob($dir, GLOB_BRACE);

//GET MOST RECENT FILES
$files = array_reverse($files);

//GET ONLY LAST 10 FILES
$files = array_splice($files, 0, 10);

if (empty($files)) {
    $status = 404;
    $error = _NO_STAT_FILES_AVAILABLE." {$path}";
} else {
    $content .= '<table id="keywords-helper" class="small_text" style="width:30%;border:solid 1px;margin:auto;">';
    $content .= '<td><i class="fa fa-info-circle fa-3x"></i></td><td>'._FILESTAT_DESC.'</td>';
    $content .= '</table>';

    $content .= '<div>';
    $content .= '<ul class="reports_list">';
    //LISTING STAT FILES (last 10 files)
    $content .= '<table class="listing spec zero_padding" style="border-collapse:collapse;width:100%;">';
    for ($i = 0; $i < count($files); ++$i) {
        //GET FILE INFOS
        $fileInfo = pathinfo($files[$i]);
        $fileDate = date('d-m-Y', filemtime($files[$i]));
        if ($i % 2 == 0) {
            $class = '';
        } else {
            $class = 'class="col"';
        }
        $content .= '<tr '.$class.'>';
        $content .= '<td style="width:20px;padding:5px;">';
        $content .= "<a target=\"_blank\" href=\"index.php?display=true&module=reports&page=saveStatFile&filename={$fileInfo['filename']}\"><i class=\"fa fa-download fa-2x\" title=\""._DOWNLOAD.'"></i></a>';
        $content .= '</td>';
        $content .= '<td style="padding:5px;">';
        $content .= "{$fileInfo['filename']}.{$fileInfo['extension']}<br/><small><i class=\"fa fa-calendar\"></i> <i style='font-size:9px;'>{$fileDate}</i></small>";
        $content .= '</td>';
        $content .= '</tr>';
    }
    $content .= '</table>';
    $content .= '</ul>';
    $content .= '</div>';
}
echo '{"status" : "'.$status.'", "content" : "'.addslashes($content).'", "error" : "'.addslashes($error).'", "exec_js" : "'.addslashes($js).'"}';
exit();
