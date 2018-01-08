<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   getStatFile
* @author  dev <dev@maarch.org>
* @ingroup reports
*/
$status = 1;
$content = '';

//GET STAT FILES
if (!empty($_SESSION['custom_override_id'])) {
	$dir =  'custom/' . $_SESSION['custom_override_id'] . 'modules/life_cycle/batch/files/*.csv';
} else {
	$dir = 'modules/life_cycle/batch/files/*.csv';
}

$files = glob($dir, GLOB_BRACE);

//GET MOST RECENT FILES
$files = array_reverse($files);

//GET ONLY LAST 10 FILES
$files = array_splice($files,0,10);



if (empty($files)) {
	$status = 404;
	$error = _NO_DATA_MESSAGE;
} else {
	//BEDING HEADER
	$content .= '<h2>&nbsp;Fichiers de statistiques</h2>';

	$content .= '<table id="keywords-helper" class="small_text" style="width:240px;border:solid 1px;">';
	$content .= '<td><i class="fa fa-info-circle fa-3x"></i></td><td>'._FILESTAT_DESC.'</td>';
	$content .= '</table>';

	//LISTING STAT FILES (last 10 files)
	$content .= '<table class="listing spec zero_padding" style="border-collapse:collapse;">';
	for ($i=0;$i<count($files);$i++) {
		//GET FILE INFOS
		$fileInfo = pathinfo($files[$i]);
		$fileDate = date ('d-m-Y', filemtime($files[$i]));
		if ($i%2 == 0) {
			$class= "";
		} else {
			$class= 'class="col"';
		}
		$content .= '<tr '.$class.'><td style="padding:5px;">';
			$content .= "{$fileInfo['filename']}.{$fileInfo['extension']}<br/><small><i class=\"fa fa-calendar\"></i> <i style='font-size:9px;'>{$fileDate}</i></small>";
		$content .= '</td>';
		$content .= '<td>';
			$content .= "<a target=\"_blank\" href=\"index.php?display=true&module=reports&page=saveStatFile&filename={$fileInfo['filename']}\"><i class=\"fa fa-download fa-2x\" title=\""._DOWNLOAD."\"></i></a>";
		$content .= '</td></tr>';
	}
	$content .= '</table>';
	//CLOSE BUTTON
	$content .= '<p align="center">';
	$content .= '<input type="button" value="';
	$content .=  _CLOSE;
	$content .= '" name="cancel" class="button"  onclick="destroyModal(\'showStatFilesList\');"/>';
	$content .= '</p>';
}
echo "{\"status\" : \"" . $status . "\", \"content\" : \"" . addslashes($content) . "\", \"error\" : \"" . addslashes($error) . "\", \"exec_js\" : \"".addslashes($js)."\"}";
exit();