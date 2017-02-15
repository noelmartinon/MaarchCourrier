<?php
	require_once 'modules/thumbnails/class/class_modules_tools.php';
			
	$resId    = $_REQUEST['res_id'];
	$collId   = $_REQUEST['coll_id'];
	$advanced = $_REQUEST['advanced'];

	$tnl = new thumbnails();
	if (empty($advanced)) {
		$path = $tnl->getPathTnl($resId, $collId); // Old Behaviour
	} else {
		$path = $tnl->getTnlPathWithColl(['resId' => $resId, 'collId' => $collId]); // New Behaviour
	}
	if (!is_file($path)){
		//$path = 'modules'. DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR . 'no_thumb.png';
		exit();
	}
	$mime_type = 'image/png';	
	$date = mktime(0,0,0,date("m" ) + 2  ,date("d" ) ,date("Y" )  );
	$date = date("D, d M Y H:i:s", $date);
	$time = 30*12*60*60;
	header("Pragma: public");
	header("Expires: ".$date." GMT");
	header("Cache-Control: max-age=".$time.", must-revalidate");
	header("Content-Description: File Transfer");
	header("Content-Type: ".$mime_type);
	header("Content-Disposition: inline; filename=filename;");
	header("Content-Transfer-Encoding: binary");
	readfile($path);
		
	exit();
