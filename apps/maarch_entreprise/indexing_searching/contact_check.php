<?php
/*
*
*   Copyright 2014 Maarch
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
* @brief    Script to return ajax result
*
* @author   Alex ORLUC <dev@maarch.org>
* @date     $date$
* @version  $Revision$
*/

//NO CHECK IF OUTGOING
if(empty($_POST['contact_id']) || $_POST['category'] == 'outgoing'){
	echo "success";
} else {
	require_once 'apps/'. $_SESSION['config']['app_id'] .'/class/class_users.php';

	$db       = new Database();
	$arrayPDO = array();
	$user     = new class_users();

	$whereSec = $user->get_global_security();

	//IF EXTERNAL CONTACT
	if (!empty(trim($_POST['address_id']))) {
		$where = "status <> 'DEL' AND contact_id = ".$_POST['contact_id']." AND address_id = ".$_POST['address_id']
			." AND (creation_date >= " . $db->current_datetime() . " - INTERVAL '".$_SESSION['check_days_before']."' DAY)";
		$wherePDO = "status <> 'DEL' AND contact_id = ? AND address_id = ? AND (creation_date >= " . $db->current_datetime() . " - INTERVAL '".$_SESSION['check_days_before']."' DAY)";
		$arrayPDO = array($_POST['contact_id'], $_POST['address_id']);
	//IF INTERNAL CONTACT
	} else {
		$where = "status <> 'DEL' AND (exp_user_id = '".$_POST['contact_id']."' OR dest_user_id = '".$_POST['contact_id']."') AND (creation_date >= " . $db->current_datetime() . " - INTERVAL '".$_SESSION['check_days_before']."' DAY)";
		$wherePDO = "status <> 'DEL' AND (exp_user_id = ? OR dest_user_id = ?) AND (creation_date >= " . $db->current_datetime() . " - INTERVAL '".$_SESSION['check_days_before']."' DAY)";
		$arrayPDO = array($_POST['contact_id'], $_POST['contact_id']);
	}

	//MERGE GLOBAL SECURITY WITH QUERY DOC
	$wherePDO = $wherePDO . ' AND ('.$whereSec.')';

	//EXCLUDE OWN RES_ID
	if($_POST['res_id'] != "none"){
		$wherePDO .= " AND res_id NOT IN (?)";
		$allResId = explode(",", $_POST['res_id']);
		$arrayPDO = array_merge($arrayPDO, array($allResId));
		$_SESSION['excludeId'] = $_POST['res_id'];
	}

	$order = "ORDER by creation_date DESC";
	$query = $db->limit_select(0, 1, 'res_id', 'res_view_letterbox', $wherePDO, '', '', $order);

	$stmt = $db->query($query, $arrayPDO);

	if ($stmt->rowCount() > 0){
		$_SESSION['where_from_contact_check'] = " AND (".$where.")";
		echo "fail";
	} else {
		echo "success";
	}
}
