<?php
	//ini_set('error_reporting', E_ALL);
	//require_once "core/class/class_security.php";
	
	include_once 'core/init.php';
	require_once 'core/class/class_portal.php';
	require_once "core/class/class_functions.php";
	require_once "core/class/class_db.php"; 
	require_once "core/class/class_db_pdo.php";
	require_once "core/class/class_request.php";
	require_once "core/class/class_resource.php";
	require_once "core/class/docservers_controler.php";
	require_once 'modules/attachments/attachments_tables.php';
	require_once 'modules/attachments/class/attachments_controler.php';
	
	$docserverControler = new docservers_controler();
	$func               = new functions();
	$req                = new request();
	$db              	= new Database();
	$ac 				= new attachments_controler();
	
	/**
		* WebEditor AJAX Process Execution.
	*/
	require_once( 'modules/content_management/config.php' );
	require_once( 'modules/content_management/ajax.php' );
	require_once( 'modules/content_management/common.php' );
	require_once( 'modules/content_management/functions.php' );
	
	$status = 'ko';
	$objectType = '';
	$objectTable = '';
	$objectId = '';
	$appPath = '';
	$fileContent = '';
	$fileExtension = '';
	$error = '';
	
	//$cM = new content_management_tools();
	
	
	$_trackerStatus = array(
    0 => 'NotFound',
    1 => 'Editing',
    2 => 'MustSave',
    3 => 'Corrupted',
    4 => 'Closed'
	);
	//sendlog("_SESSION CONFIG: " . $_SESSION['config'] ,"logs/webeditor_ajax.log");
	
	
	$file = $_REQUEST["fileName"];
	$IpAddress = $_REQUEST["userAddress"];
	$fileExtension = $_REQUEST["extension"];
	$userId = $_REQUEST["userID"];
 	$objectId = $_REQUEST["objectId"];
	$objectType = $_REQUEST["objectType"];
	$resMaster = $_REQUEST["resMaster"];
	$uniqueId = $_REQUEST["uniqueId"];
	$docserver = $_REQUEST["docserver"];
	$path = $_REQUEST["path"];
	//$pathfile = str_replace('#', '/', $path);
	$namefile = $_REQUEST["namefile"];
	$format = $_REQUEST["format"];
	
	sendlog("type:".$_GET["type"],"logs/webeditor_ajax.log");
	if (isset($_GET["type"]) && !empty($_GET["type"])) { //Checks if type value exists
		$response_array;
		@header( 'Content-Type: application/json; charset==utf-8');
		@header( 'X-Robots-Tag: noindex' );
		@header( 'X-Content-Type-Options: nosniff' );
		sendlog(serialize($_GET),"logs/webeditor_ajax.log");
		$type = $_GET["type"];
        sendlog("_GET params: " . serialize( $_GET ),"logs/webeditor_ajax.log");
		if ($type=='track'){
			$response_array['status'] = 'success';
            sendlog("_GET status " . $response_array['status'],"logs/webeditor_ajax.log");
			sendlog("Track START","logs/webeditor_ajax.log");
			sendlog("_GET params: " . serialize( $_GET ),"logs/webeditor_ajax.log");
			global $_trackerStatus;
			$data;
			$result["error"] = 0;
			
			if (($body_stream = file_get_contents('php://input'))===FALSE){
				$result["error"] = "Bad Request";          
			}
			
			$data = json_decode($body_stream, TRUE); //json_decode - PHP 5 >= 5.2.0

			if ($data === NULL){
				$result["error"] = "Bad Response";        
			}
			
			if (! isset($_SESSION['collection_id_choice']) || empty($_SESSION['collection_id_choice'])
			) {
				$_SESSION['collection_id_choice'] = 'letterbox_coll';
				sendlog("_SESSION: " . $_SESSION['collection_id_choice'] ,"logs/webeditor_ajax.log");
			}
			sendlog("_SESSION2: " . $_SESSION['collection_id_choice'] ,"logs/webeditor_ajax.log");
			sendlog("InputStream data: " . serialize($data),"logs/webeditor_ajax.log");    
			$status = $_trackerStatus[$data["status"]];
			sendlog("_STATUS: " . $status ,"logs/webeditor_ajax.log");
            sendlog("_URL: " . $data["url"] ,"logs/webeditor_ajax.log");
			if($status=='MustSave' or $status=='Closed'){
				$fileexplode=explode('(',$file);
				sendlog("EXPLOD: File ".$fileexplode[0].'.'.$fileExtension.' -- '.$userId ,"logs/webeditor_ajax.log");
				$pathTmp = $_SESSION['config']['tmppath'];
				$filePathIptmp   = $pathTmp . $file;
				$filePathOnTmp = $filePathIptmp;

				sendlog("Copy:" .$data["url"]." ---> ".$filePathOnTmp  ,"logs/webeditor_ajax.log");
				if (!copy($data["url"] , $filePathOnTmp)) {
					$result = array('ERROR' => _FAILED_TO_COPY_ON_TMP1 . ':' . $filePathIptmp  . ' ' . $filePathOnTmp);
					sendlog("COPY FAILED "  ,"logs/webeditor_ajax.log");
					}else{
					sendlog("SUCCESS COPY: ".$filePathIptmp.' ---> '.$filePathOnTmp  ,"logs/webeditor_ajax.log");
				}
				
				$fp = fopen( $filePathOnTmp.'.lck',"w");
				//sendlog("CREATE LCK: ".' ---> '.$filePathOnTmp.'.lck'  ,"logs/webeditor_ajax.log");
				//fclose($fp);
			}
			
			switch ($status){
				case "MustSave":
				case "Corrupted":
				
				$userAddress = $_GET["userAddress"];
				$fileName = $_GET["fileName"];
				
				$downloadUri = $data["url"];
				
				$curExt = strtolower('.' . pathinfo($fileName, PATHINFO_EXTENSION));
				$downloadExt = strtolower('.' . pathinfo($downloadUri, PATHINFO_EXTENSION));

				if ($downloadExt != $curExt) {
					$key = getDocEditorKey($downloadUri);
					
					try {
						sendlog("Convert " . $downloadUri . " from " . $downloadExt . " to " . $curExt, "logs/webeditor_ajax.log");
						$convertedUri;
						$percent = GetConvertedUri($downloadUri, $downloadExt, $curExt, $key, FALSE, $convertedUri);
						$downloadUri = $convertedUri;
						} catch (Exception $e) {
						sendlog("Convert after save ".$e->getMessage(),"logs/webeditor_ajax.log");
						$result["error"] = "error: " . $e->getMessage();
						echo  $result["error"];
					}
				}
				
				$saved = 1;
				
				if (($new_data = file_get_contents($downloadUri))===FALSE){
					$saved = 0;
					} else {
					$storagePath = getStoragePath($fileName, $userAddress);
					file_put_contents($storagePath, $new_data, LOCK_EX);
				}
				
				$result["c"] = "saved";
				$result["status"] = $saved;
				break;
			}
			
			sendlog( "track result: " . serialize($result),"logs/webeditor_ajax.log");
			//var_dump($result);
			$response_array = $result;
			//$response_array['status'] = 'success';
			die (json_encode($response_array));
			
		}
	}
	
	
	/*function track($file,$IpAddress) {
		
		
		sendlog("Track START","logs/webeditor_ajax.log");
		sendlog("_GET params: " . serialize( $_GET ),"logs/webeditor_ajax.log");
		
		global $_trackerStatus;
		$data;
		$result["error"] = 0;
		
		if (($body_stream = file_get_contents('php://input'))===FALSE){
        $result["error"] = "Bad Request";
        return $result;
		}
		
		$data = json_decode($body_stream, TRUE); //json_decode - PHP 5 >= 5.2.0
		
		if ($data === NULL){
        $result["error"] = "Bad Response";
        return $result;
		}
		//$data =serialize( $_GET );
	/************************/
	
	
    //$fileExtension = $_REQUEST["extension"];
	
	
	
	/*$pathTmp = $_SESSION['config']['tmppath'].$IpAddress;
		
		$_SESSION['upfile']['tmp_name']             = $pathTmp .'/'. $file;
		$_SESSION['upfile']['size']                 = filesize($pathTmp .'/'. $file);
		$_SESSION['upfile']['error']                = "";
		$_SESSION['upfile']['fileNameOnTmp']        = $file;
		$_SESSION['upfile']['format']               = $fileExtension;
		
		
		
		//require_once 'core/docservers_tools.php';
		
		
		if (! isset($_SESSION['collection_id_choice'])
        || empty($_SESSION['collection_id_choice'])
		) {
        $_SESSION['collection_id_choice'] = 'letterbox_coll';
        sendlog("_SESSION: " . $_SESSION['collection_id_choice'] ,"logs/webeditor_ajax.log");
		}
		sendlog("_SESSION2: " . $_SESSION['collection_id_choice'] ,"logs/webeditor_ajax.log");
		$docserver = $docserverControler->getDocserverToInsert($_SESSION['collection_id_choice']);
		
		if (empty($docserver)) {
        $_SESSION['error'] = _DOCSERVER_ERROR . ' : ' . _NO_AVAILABLE_DOCSERVER . ". " . _MORE_INFOS . ".";
        $location = "";
        sendlog("_SESSION_ERROR_DOCSERVER: " . $_SESSION['error'],"logs/webeditor_ajax.log");
		}else {
		sendlog("_DOCSERVER: " . $docserver ,"logs/webeditor_ajax.log");
		}
		sendlog("InputStream data: " . serialize($data),"logs/webeditor_ajax.log");
		
		$status = $_trackerStatus[$data["status"]];
		
		switch ($status){
        case "MustSave":
        case "Corrupted":
		
		$userAddress = $_GET["userAddress"];
		$fileName = $_GET["fileName"];
		
		$downloadUri = $data["url"];
		
		$curExt = strtolower('.' . pathinfo($fileName, PATHINFO_EXTENSION));
		$downloadExt = strtolower('.' . pathinfo($downloadUri, PATHINFO_EXTENSION));
		
		if ($downloadExt != $curExt) {
		$key = getDocEditorKey(downloadUri);
		
		try {
		sendlog("Convert " . $downloadUri . " from " . $downloadExt . " to " . $curExt, "logs/webeditor_ajax.log");
		$convertedUri;
		$percent = GetConvertedUri($downloadUri, $downloadExt, $curExt, $key, FALSE, $convertedUri);
		$downloadUri = $convertedUri;
		} catch (Exception $e) {
		sendlog("Convert after save ".$e->getMessage(),"logs/webeditor_ajax.log");
		$result["error"] = "error: " . $e->getMessage();
		return $result;
		}
		}
		
		$saved = 1;
		
		if (($new_data = file_get_contents($downloadUri))===FALSE){
		$saved = 0;
		} else {
		$storagePath = getStoragePath($fileName, $userAddress);
		file_put_contents($storagePath, $new_data, LOCK_EX);
		}
		
		$result["c"] = "saved";
		$result["status"] = $saved;
		break;
		}
		
		sendlog( "track result: " . serialize($result),"logs/webedior-ajax.log");
		return $result;
		}
		
		function convert() {
		$fileName = $_GET["filename"];
		$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		$internalExtension = trim(getInternalExtension($fileName),'.');
		
		if (in_array("." + $extension, $GLOBALS['DOC_SERV_CONVERT']) && $internalExtension != "") {
		
        $fileUri = $_GET["fileUri"];
        if ($fileUri == NULL || $fileUri == "") {
		$fileUri = FileUri($fileName, TRUE);
        }
        $key = getDocEditorKey($fileName);
		
        $newFileUri;
        $result;
        $percent;
		
        try {
		$percent = GetConvertedUri($fileUri, $extension, $internalExtension, $key, TRUE, $newFileUri);
        }
        catch (Exception $e) {
		$result["error"] = "error: " . $e->getMessage();
		return $result;
        }
		
        if ($percent != 100)
        {
		$result["step"] = $percent;
		$result["filename"] = $fileName;
		$result["fileUri"] = $fileUri;
		return $result;
        }
		
        $baseNameWithoutExt = substr($fileName, 0, strlen($fileName) - strlen($extension) - 1);
		
        $newFileName = GetCorrectName($baseNameWithoutExt . "." . $internalExtension);
		
        if (($data = file_get_contents(str_replace(" ","%20",$newFileUri)))===FALSE){
		$result["error"] = 'Bad Request';
		return $result;
        } else {
		file_put_contents(getStoragePath($newFileName), $data, LOCK_EX);
        }
		
        unlink(getStoragePath($fileName));
		
        $fileName = $newFileName;
		}
		
		$result["filename"] = $fileName;
		return $result;
		}
		
		function delete() {
		try {
        $fileName = $_GET["fileName"];
		
        $filePath = getStoragePath($fileName);
		
        unlink($filePath);
		}
		catch (Exception $e) {
		sendlog("logs/webedior-ajax.log","Deletion ".$e->getMessage());
        $result["error"] = "error: " . $e->getMessage();
        return $result;
		}
	}*/
	
	
?>