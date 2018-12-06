<?php

/*
*
*    Copyright 2008,2015 Maarch
*
*  This file is part of Maarch Framework.
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
*    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*
*   @author <dev@maarch.org>
*/

// FOR ADD, UP TEMPLATES and temporary backup

/*$_SESSION['m_admin']['templates']['current_style'] 
        = $_SESSION['config']['tmppath'] . $tmpFileName; */
$fileName = $_SESSION['file'];
$userAddress = $_SESSION['IpAddress'];
$extension = $_SESSION['fileExtension'];
$userID = $_SESSION['userId'];
$objectTable = $_SESSION['objectTable'];
$objectId = $_SESSION['objectId'];
$objectType = $_SESSION['objectType'];
$resMaster = $_SESSION['resMaster'];
$uniqueId = $_SESSION['uniqueId'];
$docserver = $_SESSION['docserver'] ;
$path = $_SESSION['path'] ;
$namefile=$_SESSION['namefile'];
$format=$_SESSION['format'];
//$_SESSION['attachmentInfo']['inProgressResId'] = $objectId;
$_SESSION['collection_id_choice'] = 'letterbox_coll';
$path = str_replace('##', '#', $path);
$path = str_replace('#', DIRECTORY_SEPARATOR, $path);
$docserver = str_replace('/', DIRECTORY_SEPARATOR, $docserver);
$newfile = $namefile;
$filePdf = explode('.',$namefile);
$fileNamepdf = $filePdf[0].'.pdf';
$fileOnDs = $docserver .$path. $namefile;
$newfileOnDs = $docserver .$path.  $newfile;
$PdffileOnDs = $docserver .$path. $fileNamepdf;
$NewPdfFile = $filePdf[0].'.pdf';
$newfilePdfOnDs = $docserver .$path.  $NewPdfFile;

$filetmpdoc = $_SESSION['config']['tmppath'].$fileName;
//commande pour linux
/*
   $cmd = 'libreoffice --headless --convert-to pdf --outdir '.$_SESSION['config']['tmppath'].'  '.$filetmpdoc.' ';
 */
//commande pour windows
  $cmd = 'cd C:\Program Files\LibreOffice\program  && soffice --convert-to pdf --outdir  C:\wamp\www\maarch_onlyoffice\apps\maarch_entreprise\tmp  '.$filetmpdoc.' ';
exec($cmd);
if(exec($cmd)){
	echo "conversion ok";
	//exit;
}else{
	echo "echec";
	//exit;
}

$filetmppdf = str_replace('.doc','.pdf',str_replace('.docx','.pdf',$fileName));

$_SESSION["doc_id"] = $resMaster;
// FOR ADD, UP TEMPLATES and temporary backup
if (empty($_REQUEST['uniqueId']) || $_REQUEST['uniqueId'] == null) {
    $i = 0;
} else {
    $i = $_REQUEST['uniqueId'];
}
if (!isset($_SESSION['upfile'])) {
    $_SESSION['upfile'] = [];
}
$_SESSION['upfile'][$i]['tmp_name']             = $_SESSION['config']['tmppath'] . $fileName;
$_SESSION['upfile'][$i]['size']                 = filesize($_SESSION['config']['tmppath'] . $fileName);
$_SESSION['upfile'][$i]['error']                = "";
$_SESSION['upfile'][$i]['fileNameOnTmp']        = $fileName;
$_SESSION['upfile'][$i]['format']               = $extension;
$_SESSION['upfile'][$i]['upAttachment']         = true;
$_SESSION['m_admin']['templates']['applet'] = true;
$_SESSION['upfile'][$i]['outgoingMail'] = true;

if ($_SESSION['modules_loaded']['attachments']['convertPdf'] == true){
	echo "<br>upfile".$_SESSION['upfile'][$i]['fileNamePdfOnTmp'] = $filetmppdf;
}



?>
    