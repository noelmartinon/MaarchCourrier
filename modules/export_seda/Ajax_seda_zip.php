<?php
/*
*   Copyright 2008-2017 Maarch
*
*   This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

	require_once __DIR__.'/Zip.php';
    require_once __DIR__ . '/RequestSeda.php';

	$status = 0;
	$error = $content = '';
	if ($_REQUEST['reference']) {
		$extract = new Extract();
		$zipfile = $extract->exportZip($_REQUEST['reference']);
		$extract->download($zipfile);
	} else {
		$status = 1;
	}

	
	echo "{status : " . $status . ", content : '" . addslashes($content) . "', error : '" . addslashes($error) . "'}";
	exit ();

class Extract
{
	protected $zip;

	public function __construct() 
	{
		$this->zip = new Zip();
	}

	public function exportZip($reference)
	{
		$messageDirectory = __DIR__.DIRECTORY_SEPARATOR.'message'.DIRECTORY_SEPARATOR.$reference;
		$zipDirectory = __DIR__.DIRECTORY_SEPARATOR.'message'.DIRECTORY_SEPARATOR.$reference. ".zip";

		if (!is_file($zipDirectory)) {
            if (is_dir($messageDirectory)) {
                $zip = new ZipArchive();
                $zip->open($zipDirectory, ZipArchive::CREATE);

                $listFiles = scandir($messageDirectory.DIRECTORY_SEPARATOR);

                foreach ( $listFiles as $filename) {
                    if ($filename != '.' && $filename != '..') {
                        $zip->addFile($messageDirectory . DIRECTORY_SEPARATOR . $filename, $filename);
                    }

                }
            }
        }

        return $zipDirectory;
	}

	public function download($full_path)
    {
        $file_name = basename($full_path);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Tranfer-Encoding: fichier');
        header('Content-Length: ' . filesize($full_path));
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');

        readfile($full_path);
    }
}