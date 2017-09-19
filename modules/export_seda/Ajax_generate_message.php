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

    require_once __DIR__ . '/RequestSeda.php';
    require_once __DIR__ . '/class/AbstractMessage.php';

    $status = 0;
    $error = $content = '';
    if (!$_REQUEST['messageTitle']) {
        $status = 1;
        $error = _MESSAGE_TITLE_EMPTY;
    } else {
        if ($_REQUEST['reference']) {
            if (!file_exists(__DIR__.DIRECTORY_SEPARATOR.'..'. DIRECTORY_SEPARATOR.'message'.DIRECTORY_SEPARATOR. $_REQUEST['reference'] .DIRECTORY_SEPARATOR. $_REQUEST['reference'] . '.xml')) {
                $abstractMessage = new AbstractMessage();
                $abstractMessage->addTitleToMessage($_REQUEST['reference'],$_REQUEST['messageTitle']);
                $abstractMessage->generatePackage($_REQUEST['reference'],'ArchiveTransfer');
            }

            if ($status != 0) {
                $error = $res['error'];
            } else {
                $content = $res['content'];
            }
        } else {
            $status = 1;
        }
    }

	echo "{status : " . $status . ", content : '" . addslashes($content) . "', error : '" . addslashes($error) . "'}";
	exit ();