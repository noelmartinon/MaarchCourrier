<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   checkEditingDoc
* @author  dev <dev@maarch.org>
* @ingroup content_management
*/
if (!empty($_SESSION['cm_applet'][$_SESSION['user']['UserId']])) {
    echo "{\"status\" : 1, \"status_txt\" : \"LCK FOUND !\"}";
} else if (strpos($_SESSION['upfile'][$_POST['attachNum']]['format'], 'xl') !== false || strpos($_SESSION['upfile'][$_POST['attachNum']]['format'], 'ppt') !== false) {
    echo "{\"status\" : 0, \"status_txt\" : \"LCK NOT FOUND !\", \"pdf_version\" : \"\"}";
} else {
    echo "{\"status\" : 0, \"status_txt\" : \"LCK NOT FOUND !\", \"pdf_version\" : \"".$_SESSION['config']['tmppath'].$_SESSION['upfile'][$_POST['attachNum']]['fileNamePdfOnTmp']."\"}";
}
