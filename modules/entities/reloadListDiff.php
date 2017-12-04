<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   reloadListDiff
* @author  dev <dev@maarch.org>
* @ingroup entities
*/

$origin = $_REQUEST['origin'];
$role_id = $_REQUEST['role_id'];
$rank = $_REQUEST['rank'];

$oldDest = $_SESSION[$origin]['diff_list']['dest']['users'][0];

$_SESSION[$origin]['diff_list']['dest']['users'][0] = $_SESSION[$origin]['diff_list'][$role_id]['users'][$rank];

$_SESSION[$origin]['diff_list'][$role_id]['users'][$rank] = $oldDest;

echo "{\"status\" : 0}";