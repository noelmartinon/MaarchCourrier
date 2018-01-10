<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   saveStatFile
* @author  dev <dev@maarch.org>
* @ingroup reports
*/
if (isset($_GET['filename'])) {

	//COPY FILE IN TMP PATH
	if (!empty($_SESSION['custom_override_id'])) {
		if (!@copy("custom/{$_SESSION['custom_override_id']}/modules/life_cycle/batch/files/{$_GET['filename']}.csv",$_SESSION['config']['tmppath']."{$_GET['filename']}.csv")) {
			exit('We cannot retrieve this file ! (permission problem)');
		}
	} else {
		if (!@copy("modules/life_cycle/batch/files/{$_GET['filename']}.csv",$_SESSION['config']['tmppath']."{$_GET['filename']}.csv")) {
			exit('We cannot retrieve this file ! (permission problem)');
		}
	}

	//DOWNLOAD THE FILE
	header('Pragma: public');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Content-Type: application/vnd.ms-excel; charset=utf-8');
	header('Content-Disposition: inline; filename=test.csv;');
	ob_clean();
	flush();
	readfile($_SESSION['config']['tmppath']."{$_GET['filename']}.csv");
}
exit();