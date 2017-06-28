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


$status = 0;
$error = $content = $res = '';

if ($_REQUEST['reference']) {
    $resIds = explode(',',$_REQUEST['reference']);
    if ($_REQUEST['type'] == 'acknowledgement') {
        require_once __DIR__.'/CheckAcknowledgement.php';

        $checkAcknowledgement = new CheckAcknowledgement();
        foreach ($resIds as $id) {
            $res = $checkAcknowledgement->checkAttachment($id);

            if ($res == false) {
                $status = 1;
                $error = $_SESSION['error'];
                break;
            }
        }
        $content = $res;
    } else if ($_REQUEST['type'] == 'reply') {
        require_once __DIR__.'/CheckReply.php';

        $checkReply = new CheckReply();
        foreach ($resIds as $id) {
            $res = $checkReply->checkAttachment($id);

            if ($res == false) {
                $status = 1;
                $error = $_SESSION['error'];
                break;
            }
        }
        $content = $res;
    } else if ($_REQUEST['type'] == 'purge') {
        require_once __DIR__.'/Purge.php';

        $purge = new Purge();
        foreach ($resIds as $id) {
            $res = $purge->purge($id);

            if ($res == false) {
                $status = 1;
                $error = $_SESSION['error'];
                break;
            }
        }
        $content = $res;
    } else if ($_REQUEST['type'] == 'reset') {
        require_once __DIR__.'/Reset.php';

        $reset = new Reset();
        foreach ($resIds as $id) {
            $res = $reset->reset($id);

            if ($res == false) {
                $status = 1;
                $error = $_SESSION['error'];
                break;
            }
        }
        $content = $res;
    } else {
        $status = 1;
    }
} else {
    $status = 1;
}

echo "{status : " . $status . ", content : '" . addslashes($content) . "', error : '" . addslashes($error) . "'}";
exit ();
