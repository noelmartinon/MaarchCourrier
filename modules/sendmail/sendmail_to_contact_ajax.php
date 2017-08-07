<?php

require_once('core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_request.php');

$db = new Database();
$db->query("UPDATE mlb_coll_ext SET sve_start_date = CURRENT_TIMESTAMP WHERE res_id = ?", array($_REQUEST['identifier']));

?>
