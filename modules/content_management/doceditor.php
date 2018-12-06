<?php
/**
 * Created by PhpStorm.
 * User: Jules DIEDHIOU
 * Date: 28/11/2018
 * Time: 10:18
 */
	/*
		*
		* (c) Copyright Ascensio System Limited 2010-2017
		*
		* This program is freeware. You can redistribute it and/or modify it under the terms of the GNU
		* General Public License (GPL) version 3 as published by the Free Software Foundation (https://www.gnu.org/copyleft/gpl.html).
		* In accordance with Section 7(a) of the GNU GPL its Section 15 shall be amended to the effect that
		* Ascensio System SIA expressly excludes the warranty of non-infringement of any third-party rights.
		*
		* THIS PROGRAM IS DISTRIBUTED WITHOUT ANY WARRANTY; WITHOUT EVEN THE IMPLIED WARRANTY OF MERCHANTABILITY OR
		* FITNESS FOR A PARTICULAR PURPOSE. For more details, see GNU GPL at https://www.gnu.org/copyleft/gpl.html
		*
		* You can contact Ascensio System SIA by email at sales@onlyoffice.com
		*
		* The interactive user interfaces in modified source and object code versions of ONLYOFFICE must display
		* Appropriate Legal Notices, as required under Section 5 of the GNU GPL version 3.
		*
		* Pursuant to Section 7 § 3(b) of the GNU GPL you must retain the original ONLYOFFICE logo which contains
		* relevant author attributions when distributing the software. If the display of the logo in its graphic
		* form is not reasonably feasible for technical reasons, you must include the words "Powered by ONLYOFFICE"
		* in every copy of the program you distribute.
		* Pursuant to Section 7 § 3(e) we decline to grant you any rights under trademark law for use of our trademarks.
		*
	*/
	require_once 'core/class/class_core_tools.php';
	$core_tools = new core_tools();
	//$core_tools->load_html();
	//$core_tools->load_header();
	//$core_tools->load_js();
	
	// sessions use for temporary backup
	if (!isset($_REQUEST['transmissionNumber'])) {
		$_SESSION['attachmentInfo'] = array();
		$_SESSION['attachmentInfo']['title'] = $_REQUEST['titleAttachment'];
		$_SESSION['attachmentInfo']['chrono'] = $_REQUEST['chronoAttachment'];
		$_SESSION['attachmentInfo']['type'] = $_REQUEST['attachType'];
		$_SESSION['attachmentInfo']['contactId'] = $_REQUEST['contactId'];
		$_SESSION['attachmentInfo']['addressId'] = $_REQUEST['addressId'];
		$_SESSION['attachmentInfo']['back_date'] = $_REQUEST['back_date'];
	}
	
	
	
	if (isset($_REQUEST['attachType']) && $_REQUEST['attachType'] == 'outgoing_mail'){
		$objType = 'outgoingMail';
	}
	else {
		$objType = $_REQUEST['objectType'];
	}
	
	/*if (
		file_exists(
		$_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
		. $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
		. DIRECTORY_SEPARATOR . 'content_management' . DIRECTORY_SEPARATOR . 'applet_launcher.php'
		)
		) {
		$path = 'custom/'. $_SESSION['custom_override_id'] .'/modules/content_management/applet_launcher.php';
		} else {
		$path = 'modules/content_management/applet_launcher.php';
	}*/
	require_once "core/class/class_security.php";
	require_once "core/class/class_request.php";
	require_once "core/class/class_resource.php";
	require_once "apps" . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
    . DIRECTORY_SEPARATOR . "class" . DIRECTORY_SEPARATOR
    . "class_indexing_searching_app.php";
	require_once "core/class/docservers_controler.php";
	require_once 'modules/attachments/attachments_tables.php';
	require_once "core/class/class_history.php";
	require_once 'modules/attachments/class/attachments_controler.php';
	
	$core               = new core_tools();
	$core->load_lang();
	$sec                = new security();
	$func               = new functions();
	$db                 = new Database();
	$req                = new request();
	$docserverControler = new docservers_controler();
	$ac                 = new attachments_controler();
	
	$_SESSION['error'] = "";
	
	$status = 0;
	$error  = $content = $js = $parameters = ''; 
	$_SESSION['cm_applet'][$_SESSION['user']['UserId']] = '';
	if (!isset($_REQUEST['transmissionNumber'])) {
		$_SESSION['attachmentInfo'] = array();
		$_SESSION['attachmentInfo']['title'] = $_REQUEST['titleAttachment'];
		$_SESSION['attachmentInfo']['chrono'] = $_REQUEST['chronoAttachment'];
		$_SESSION['attachmentInfo']['type'] = $_REQUEST['attachType'];
		$_SESSION['attachmentInfo']['contactId'] = $_REQUEST['contactId'];
		$_SESSION['attachmentInfo']['addressId'] = $_REQUEST['addressId'];
		$_SESSION['attachmentInfo']['back_date'] = $_REQUEST['back_date'];
	}
	
	if (isset($_REQUEST['attachType']) && $_REQUEST['attachType'] == 'outgoing_mail'){
		$objType = 'outgoingMail';
	}
	else {
        $objType = $_REQUEST['objectType'];
	}
	if (isset($_REQUEST['uniqueId'])) {
		$uniqueId = $_REQUEST['uniqueId'];
		} else {
		$uniqueId = '';
	}
?>

<?php
	require_once 'modules/attachments/attachments_tables.php';
	require_once 'modules/content_management/config.php' ;
	require_once 'modules/content_management/common.php' ;
	require_once 'modules/content_management/functions.php' ;
	
	$objectID  = $_REQUEST['objectId'];
	$res_id_master = $_REQUEST['resMaster'];
	$title = $_REQUEST['titleAttachment'];
	$chrono = $_REQUEST['chronoAttachment'];
	$contactId = $_REQUEST['contactId'];
	$addressId = $_REQUEST['addressId'];
	 $objectTable = $_REQUEST['objectTable'];
	$dbAttachment = new Database();
	 $objectType =  $objType;
	
	 
	
	//if ($stmt->rowCount() == 0)
//si édition Modéle de document à partir d'un template
	if ($objectType=='templateStyle'){
	 $_REQUEST['objectId']=$_SESSION['m_admin']['templates']['current_style'];

		$fileExtension = $func->extractFileExt($_REQUEST['objectId']);
		  $fileNameOnTmp = 'tmp_file_' . $_SESSION['user']['UserId']. '_' . rand() . '.' . $fileExtension;
		 $filePathOnTmp = $_SESSION['config']['tmppath']. $fileNameOnTmp;
	         //si echec copie
		 	if (!copy($_REQUEST['objectId'], $filePathOnTmp)) {
			$result = array('ERROR' => _FAILED_TO_COPY_ON_TMP . ':' . $_REQUEST['objectId']. ' ' . $filePathOnTmp
			);
			// createXML('ERROR', $result);
		}
		//$filePathOnTmp = $templateCtrl->merge($objectID, $params, 'file')."docx";
		// $templateObj = $templateCtrl->get($objectID);
		// $_SESSION['cm']['templateStyle'] = $templateObj->template_style;
		
		//$fileExtension = $func->extractFileExt($filePathOnTmp);   // } else {
		//$format = $fileExtension;
		
		}else if($objectType == 'template' || $objectType == 'attachmentFromTemplate' || $objectType == 'attachmentVersion' || $objectType == 'outgoingMail' || $objectType == 'transmission'){
		if ($_SESSION['m_admin']['templates']['current_style'] <> '') {
        // edition in progress
        $fileExtension = $func->extractFileExt($_SESSION['m_admin']['templates']['current_style']);
        $filePathOnTmp = $_SESSION['m_admin']['templates']['current_style'];
	   
			 }else {

		$sec = new security();
		$collId = $sec->retrieve_coll_id_from_table($objectTable);
		$res_view ="res_view_attachments";// $sec->retrieve_view_from_table($objectTable);
		// new	edition
            require_once 'modules/templates/class/templates_controler.php';
		$templateCtrl = new templates_controler();
		$params = array(
		'res_id' => $_REQUEST['resMaster'],
		'coll_id' => $collId,
		'res_view' => $res_view,
		'res_table' => $objectTable,
		'res_contact_id' => $_REQUEST['contactId'],
		'res_address_id' => $_REQUEST['addressId'],
		'chronoAttachment' => $_REQUEST['chronoAttachment']
		);
		if ($objectType == 'attachmentFromTemplate' || $objectType == 'attachmentVersion' || $objectType == 'outgoingMail' || $objectType == 'transmission') {
         // var_dump($params);
		   $filePathOnTmp = $templateCtrl->merge($objectID, $params, 'file');
            $templateObj = $templateCtrl->get($objectId);
            $_SESSION['cm']['templateStyle'] = $templateObj->template_style;
        } elseif ($objectType == 'template') {
          //$objectID;
		   $filePathOnTmp = $templateCtrl->copyTemplateOnTmp($objectID);
			
            if ($filePathOnTmp == '') {
                $result = array('ERROR' => _FAILED_TO_COPY_ON_TMP 
                    . ':' . $objectID . ' ' . $filePathOnTmp);
               // createXML('ERROR', $result);
            }
        }
            $fileExtension = $func->extractFileExt($filePathOnTmp);
	   
			 }
			 //$objectType<>'templateStyle' || $objectType<>'templateS'
		}
		else  {
		
		$query = "SELECT relation, docserver_id, path, res_id, filename, format FROM res_view_attachments WHERE (res_id = ? OR res_id_version = ?) AND res_id_master = ? ORDER BY relation desc";

	$stmt = $dbAttachment->query($query, array($objectID, $objectID, $res_id_master));
		$line = $stmt->fetchObject();
		$docserver = $line->docserver_id;
		$path = $line->path;
		$pathfile = str_replace('#', DIRECTORY_SEPARATOR, $path);
		$filname = $line->filename;
		$format = $line->format;
		$inProgressResId= $line->res_id;
		
		$query = "select path_template from " . _DOCSERVERS_TABLE_NAME . " where docserver_id = ?";
		$stmt = $dbAttachment->query($query, array($docserver));
		$func = new functions();
		$lineDoc = $stmt->fetchObject();
		$docserver = $lineDoc->path_template;
		$path = str_replace('##', '#', $path);
		$fileOnDs = $docserver . $path . $filname;
		$fileOnDs = str_replace('#', DIRECTORY_SEPARATOR, $fileOnDs);
		 $fileExtension = $func->extractFileExt($fileOnDs);
		$fileNameOnTmp = 'tmp_file_' . $_SESSION['user']['UserId']. '_' . rand() . '.' . $fileExtension;
		$filePathOnTmp = str_replace('/', DIRECTORY_SEPARATOR,$_SESSION['config']['tmppath']) . $fileNameOnTmp;
		if (!copy($fileOnDs, $filePathOnTmp)) {
			$result = array('ERROR' => _FAILED_TO_COPY_ON_TMP . ':' . $fileOnDs . ' ' . $filePathOnTmp
			);
			// createXML('ERROR', $result);
		}
		
		//$logpath = $filepath[0].DIRECTORY_SEPARATOR.$filepath[1].DIRECTORY_SEPARATOR.$filepath[2].'/modules/'
		//$logpath = 'modules/content_management/logs/';
	}
	
	 $filePathOnTmp=str_replace('/',DIRECTORY_SEPARATOR,$filePathOnTmp);
	// var_dump($filePathOnTmp);
	 
	$filepath = explode(DIRECTORY_SEPARATOR,$filePathOnTmp);
	 $urlFile = $_SESSION['config']['businessappurl'].$filepath[6]."/" .$filepath[7];
	 $fileNameOnTmp=$filepath[7];
	 //$fileExtension;
	
	//$filePathOnTmp;
	//echo $GLOBALS['STORAGE_PATH'];
	
     $externalUrl = $urlFile; 
	// $_GET["fileUrl"]
    $filename;
	//$fp = fopen( $filePathOnTmp.'.lck',"w");
    //$externalUrl = $filePathOnTmp; //$_GET["fileUrl"];
	$externalUrl=str_replace(DIRECTORY_SEPARATOR,'/',$externalUrl);
    if (!empty($externalUrl))
    {
        $filename = $fileNameOnTmp;//DoUpload($externalUrl);
		// exit;
		} else {
        $filename = basename($_GET["fileID"]);
	}
    $createExt = $fileExtension;//$_GET["fileExt"];
   
	// FileUri
	if (empty($createExt))
    {
        $filename = tryGetDefaultByType($createExt);		
        $new_url = $_SESSION['config']['businessappurl']."index.php?modules=content_management&page=doceditor&fileID=" . $filename . "&user=" . $_GET["user"];
        header('Location: ' . $new_url, true);
        exit;
	}

    $fileuri  = FileUri($filename, true);
    $fileuriUser = $fileuri;
	
	
    function tryGetDefaultByType($createExt) {
        $demoName = ($_GET["sample"] ? "demo." : "new.") . $createExt;
        $demoFilename = GetCorrectName($demoName);
		
        if(!@copy(dirname(__FILE__) . DIRECTORY_SEPARATOR . "app_data" . DIRECTORY_SEPARATOR . $demoName, getStoragePath($demoFilename)))
        {
			echo "test";
            //sendlog( "modules/content_management/logs/common.log","Copy file error to ". getStoragePath($demoFilename));
			$GLOBALS['logger']->write($totalEmailsToProcess . ' e-mails to proceed.', 'INFO');
            //Copy error!!!
		}
		
        return $demoFilename;
	}
	
    function getCallbackUrl($fileName,$userAddress,$extension,$userID,$objectTable,$objectId,$objectType,$resMaster,$uniqueId,$docserver,$path,$filname,$format,$inProgressResId) {
		$_SESSION['file'] = $fileName;
		$_SESSION['IpAddress'] = $userAddress;
		$_SESSION['fileExtension'] = $extension;
		$_SESSION['userId'] = $userID;
		$_SESSION['objectTable'] = $objectTable;
		$_SESSION['objectId'] = $objectId;
		$_SESSION['objectType'] = $objectType;
		$_SESSION['resMaster'] = $resMaster;
		$_SESSION['uniqueId'] = $uniqueId;
		$_SESSION['docserver'] = $docserver;
		$_SESSION['path'] = $path;
		$_SESSION['namefile'] = $filname;
		$_SESSION['format'] = $format;
		$_SESSION['inProgressResId'] =$inProgressResId;
		//ici faudra voir comment dynamiser le chemin car à chaque fois on est obligé de changer le nom du repertoire maarch ici on a mis maarch_onlyoffice
        return serverPath(TRUE).'/maarch_onlyoffice/webeditor_ajax.php?'."objectType=".$objectType
		. "&type=track"
		. "&fileName=" . urlencode($fileName)
		. "&userAddress=" . getClientIp();
		
	}
	
	if (($body_stream = file_get_contents("php://input"))===FALSE){
		echo "Bad Request";
	}
	
	$data = json_decode($body_stream, TRUE);
	
	if ($data["status"] == 2 or $data["status"] == 4){
		echo "url : ".$downloadUri = $data["url"];
        
		/* if (($new_data = file_get_contents($downloadUri))===FALSE){
			echo "Bad Response";
			} else {
			file_put_contents($path_for_save, $new_data, LOCK_EX);
		}*/
	}
	
 	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="icon" href="./favicon.ico" type="image/x-icon" />
		<title>MAARCH ONLYOFFICE</title>
		<style>
			html {
            height: 100%;
            width: 100%;
			}
			body {
            background: #fff;
            color: #333;
            font-family: Arial, Tahoma,sans-serif;
            font-size: 12px;
            font-weight: normal;
            height: 100%;
            margin: 0;
            overflow-y: hidden;
            padding: 0;
            text-decoration: none;
			}
			form {
            height: 100%;
			}
			div {
            margin: 0;
            padding: 0;
			}
		</style>
		
		<script type="text/javascript" src="<?php echo $GLOBALS["DOC_SERV_API_URL"] ?>"></script>
		
		<script type="text/javascript">
			
			var docEditor;
			var fileName = "<?php echo $filename ?>";
			var fileType = "<?php echo strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?>";
			
			var innerAlert = function (message) {
				if (console && console.log)
                console.log(message);
			};
			var onReady = function () {
				innerAlert("Document editor ready");
			};
			var onDocumentStateChange = function (event) {
				var title = document.title.replace(/\*$/g, "");
				document.title = title + (event.data ? "*" : "");
				//alert(event.data);
			};
			var onRequestEditRights = function () {
				location.href = location.href.replace(RegExp("action=view\&?", "i"), "");
			};
			var onError = function (event) {
				if (event)
                innerAlert(event.data);
			};
			var onOutdatedVersion = function (event) {
				location.reload(true);
			};
			var onRequestHistoryClose = function() {
				//document.location.reload();
				alert(data);
			};
			var сonnectEditor = function () {
				<?php
					if (!file_exists(getStoragePath($filename))) {
						echo "alert('File not found'); return;";
					}
				?>
				//alert(data);
				var user = [{id:"0","name":"<?php echo $_SESSION['user']['UserId'] ?>" }]["<?php echo $_SESSION['user']['UserId'] ?>" || 0];
				var type = "<?php echo ($_GET["type"] == "mobile" ? "mobile" : ($_GET["type"] == "embedded" ? "embedded" : ($_GET["type"] == "desktop" ? "desktop" : ""))) ?>";
				if (type == "") {
					type = new RegExp("<?php echo $GLOBALS['MOBILE_REGEX'] ?>", "i").test(window.navigator.userAgent) ? "mobile" : "desktop";
				}
				docEditor = new DocsAPI.DocEditor("iframeEditor",
                {
                    width: "100%",
                    height: "100%",
                    type: type,
                    documentType: "text",
                    document: {
                        title:  "<?php echo $title; ?>",
                        url: "<?php echo $fileuri;// echo $externalUrl ?>",
                        fileType: "<?php echo $createExt; ?>",
						key: "<?php echo getDocEditorKey($filename) ?>",
						
                        info: {
                            author: "<?php echo $_SESSION['user']['UserId']; ?>",
                            created: "<?php echo date('d.m.y') ?>",
						},
						
                        permissions: {
                            download: true,
                            edit: <?php echo (in_array(strtolower('.' . pathinfo($filename, PATHINFO_EXTENSION)), $GLOBALS['DOC_SERV_EDITED']) && $_GET["action"] != "review" ? "true" : "false") ?>,
                            review: true
							}

					},
                    editorConfig: {
                        mode: "edit",
                        lang: "fr",
                        callbackUrl: "<?php echo getCallbackUrl($filename,$userAddress,$fileExtension,$userID,$objectTable,$objectID,$objectType,$res_id_master,$uniqueId,$docserver,$path,$filname,$format,$inProgressResId).'&objectId='.$objectID
							.'&contactId='.$contactId
							.'&resMaster='. $res_id_master
							.'&titleAttachment='.$title
							.'&addressId='.$addressId
							.'&objectTable='.$objectTable
							.'&uniqueId='.$uniqueId 
							.'&extension='.$fileExtension
							.'&userID='.$_SESSION['user']['UserId']
							.'&docserver='.str_replace(DIRECTORY_SEPARATOR,'/',$docserver)
							.'&path='.str_replace(DIRECTORY_SEPARATOR,'/',$pathfile)
							.'&namefile='.$filname 
							.'&format='.$format
						.'&fileOnDs='.str_replace(DIRECTORY_SEPARATOR,'/',$fileOnDs)?>",
						user: "<?php echo $_SESSION['user']['UserId']; ?>",
						embedded: {
                            saveUrl: "<?php echo $fileuriUser ?>",
                            embedUrl: "<?php echo $fileuriUser ?>",
                            shareUrl: "<?php echo $fileuriUser ?>",
                            toolbarDocked: "top",
						},						
                        customization: {
                            about: true,
                            feedback: true,
                            goback: {
                                url: "<?php echo serverPath(true) ;?>",
							},
						},						
					},
                    events: {
                        'onReady': onReady,
                        'onDocumentStateChange': onDocumentStateChange,
                        'onRequestEditRights': onRequestEditRights,
                        'onError': onError,
                        'onOutdatedVersion': onOutdatedVersion,
                        'onRequestHistoryClose': onRequestHistoryClose,
					}
				});
			};
			if (window.addEventListener) {
				window.addEventListener("load", сonnectEditor);
				} else if (window.attachEvent) {
				window.attachEvent("load", сonnectEditor);
			}
			function getXmlHttp() {
				var xmlhttp;
				try {
					xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
					} catch (e) {
					try {
						xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
						} catch (ex) {
						xmlhttp = false;
					}
				}
				if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
					xmlhttp = new XMLHttpRequest();
				}
				return xmlhttp;
			}
		</script>
	</head>
	<body>
		<form id="form1">
			<div id="iframeEditor">
			</div>
			
		</form>
	</body>
</html>