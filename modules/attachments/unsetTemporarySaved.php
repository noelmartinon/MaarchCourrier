<?php

/*
*   Copyright 2015 Maarch
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

/**
* @brief Unset temporary saved attachments
*
* @file
* @author <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup apps
*/

$core = new core_tools();
$core->test_user();

$db = new Database();

if ($_REQUEST['mode'] == 'add') {
    $tableName = "res_attachments";
} else if($_REQUEST['mode'] == 'edit'){
    $tableName = "res_version_attachments";
}

$stmt = $db->query(
    "SELECT docserver_id, path, filename FROM ".$tableName." WHERE res_id_master = ? AND status = 'TMP' AND typist = ?",
    [$_SESSION['doc_id'], $_SESSION['user']['UserId']]
);
if ($stmt->rowCount() !== 0) {
    while ($line = $stmt->fetchObject()) {
        $stmt = $db->query("SELECT path_template FROM docservers WHERE docserver_id = ?", array($line->docserver_id));
        $lineDoc   = $stmt->fetchObject();
        $file      = $lineDoc->path_template . $line->path . $line->filename;
        $file      = str_replace("#", DIRECTORY_SEPARATOR, $file);
        unlink($file);
    }
}

$db->query("DELETE FROM ".$tableName." WHERE res_id_master = ? and status = 'TMP' and typist = ?", [$_SESSION['doc_id'], $_SESSION['user']['UserId']]);
unset($_SESSION['attachmentInfo']);

unset($_SESSION['AttachmentContact']);
