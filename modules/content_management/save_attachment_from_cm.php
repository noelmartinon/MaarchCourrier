<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   save_attachment_from_cm
* @author  dev <dev@maarch.org>
* @ingroup content_management
*/

// FOR ADD, UP TEMPLATES and temporary backup
if (empty($_REQUEST['uniqueId']) || $_REQUEST['uniqueId'] == null) {
    $i = 0;
} else {
    $i = $_REQUEST['uniqueId'];
}

if (!isset($_SESSION['upfile'])) {
    $_SESSION['upfile'] = [];
}

$_SESSION['upfile'][$i]['tmp_name']             = $_SESSION['config']['tmppath'] . $tmpFileName;
$_SESSION['upfile'][$i]['size']                 = filesize($_SESSION['config']['tmppath'] . $tmpFileName);
$_SESSION['upfile'][$i]['error']                = "";
$_SESSION['upfile'][$i]['fileNameOnTmp']        = $tmpFileName;
$_SESSION['upfile'][$i]['format']               = $fileExtension;
$_SESSION['upfile'][$i]['upAttachment']         = true;

$_SESSION['m_admin']['templates']['applet']     = true;

if ($_SESSION['modules_loaded']['attachments']['convertPdf'] == true) {
	$_SESSION['upfile'][$i]['fileNamePdfOnTmp'] = $tmpFilePdfName;
}

// Temporary backup

require_once "core/class/class_security.php";
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

require_once 'core/docservers_tools.php';

//CHECK AUTHORIZED EXTENSION
$arrayIsAllowed = array();
$arrayIsAllowed = Ds_isFileTypeAllowed(
    $_SESSION['config']['tmppath'] . $_SESSION['upfile'][$i]['fileNameOnTmp']
);

if ($arrayIsAllowed['status'] == false) {
	$_SESSION['error']  = _WRONG_FILE_TYPE . ' ' . $arrayIsAllowed['mime_type'];
	$_SESSION['upfile'] = array();
} else {
    if (! isset($_SESSION['collection_id_choice']) || empty($_SESSION['collection_id_choice'])) {
        $_SESSION['collection_id_choice'] = $_SESSION['user']['collections'][0];
    }

    $docserver = $docserverControler->getDocserverToInsert($_SESSION['collection_id_choice']);

    if (empty($docserver)) {
        $_SESSION['error'] = _DOCSERVER_ERROR . ' : ' . _NO_AVAILABLE_DOCSERVER . ". " . _MORE_INFOS . ".";
        $location = "";
    } else {

        // some checking on docserver size limit
        $newSize = $docserverControler->checkSize(
            $docserver, $_SESSION['upfile'][$i]['size']
        );
        if ($newSize == 0) {
            $_SESSION['error'] = _DOCSERVER_ERROR . ' : ' . _NOT_ENOUGH_DISK_SPACE . ". " . _MORE_INFOS . ".";
            ?>
            <script type="text/javascript">
                var eleframe1 = window.parent.top.document.getElementById('list_attach');
                eleframe1.location.href = '<?php
		            echo $_SESSION['config']['businessappurl'];
		            ?>index.php?display=true&module=attachments&page=frame_list_attachments&attach_type_exclude=converted_pdf,print_folder&mode=normal&load';
            </script>
            <?php
            exit();
        } else {
            $fileInfos = array(
                "tmpDir"      => $_SESSION['config']['tmppath'],
                "size"        => $_SESSION['upfile'][$i]['size'],
                "format"      => $_SESSION['upfile'][$i]['format'],
                "tmpFileName" => $_SESSION['upfile'][$i]['fileNameOnTmp'],
            );

            //SAVE FILE ON DOCSERVER ATTACHMENT
            $storeResult = array();
            $storeResult = $docserverControler->storeResourceOnDocserver(
                $_SESSION['collection_id_choice'], $fileInfos
            );

            if (isset($storeResult['error']) && $storeResult['error'] <> '') {
                $_SESSION['error'] = $storeResult['error'];

            //MODE SECOND BACKUP AND MORE
            } else if(isset($_SESSION['attachmentInfo'][$i]['inProgressResId'])){

                //DELETE OLD BACKUP
				$ac->removeTemporaryAttachmentOnDocserver($_SESSION['attachmentInfo'][$i]['inProgressResId'], $_SESSION['doc_id'], $_SESSION['user']['UserId']);

		        require_once 'core/class/docserver_types_controler.php';
				$docserverTypeControler = new docserver_types_controler();

                //RETRIEVE FILE PATH
				$filetmp = $storeResult['path_template'];
				$filetmp .= str_replace('#',DIRECTORY_SEPARATOR, $storeResult['destination_dir']);
				$filetmp .= $storeResult['file_destination_name'];

				$docserver           = $docserverControler->get($storeResult['docserver_id']);
				$docserverTypeObject = $docserverTypeControler->get($docserver->docserver_type_id);
				$fingerprint         = Ds_doFingerprint($filetmp, $docserverTypeObject->fingerprint_mode);

				if ($_SESSION['targetAttachment'] == 'add') {
		        	$tableName = 'res_attachments';
	        	} else if ($_SESSION['targetAttachment'] == 'edit') {
		        	$tableName = 'res_version_attachments';
                }
                
                //UPDATE NEW FILE PATH
	        	$db->query('UPDATE '.$tableName.' SET fingerprint = ?, filesize = ?, path = ?, filename = ? WHERE res_id = ?', 
	        		array($fingerprint, filesize($filetmp), $storeResult['destination_dir'], $storeResult['file_destination_name'], $_SESSION['attachmentInfo'][$i]['inProgressResId']));
            
            //MODE FIRST BACKUP
            } else {
                $_SESSION['data'] = array();

                array_push($_SESSION['data'], array( 'column' => "typist", 			'value' => $_SESSION['user']['UserId'], 			'type' => "string" ) );
                array_push($_SESSION['data'], array( 'column' => "format", 			'value' => $fileInfos['format'], 					'type' => "string" ) );
                array_push($_SESSION['data'], array( 'column' => "docserver_id", 	'value' => $storeResult['docserver_id'], 			'type' => "string" ) );
                array_push($_SESSION['data'], array( 'column' => "status", 			'value' => 'TMP', 									'type' => "string" ) );
                array_push($_SESSION['data'], array( 'column' => "offset_doc", 		'value' => ' ', 									'type' => "string" ) );
                array_push($_SESSION['data'], array( 'column' => "logical_adr", 	'value' => ' ', 									'type' => "string" ) );
                array_push($_SESSION['data'], array( 'column' => "title", 			'value' => $_SESSION['attachmentInfo'][$i]['title'], 	'type' => "string" ) );
                array_push($_SESSION['data'], array( 'column' => "coll_id", 		'value' => $_SESSION['collection_id_choice'], 		'type' => "string" ) );
                array_push($_SESSION['data'], array( 'column' => "res_id_master", 	'value' => $_SESSION['doc_id'], 					'type' => "integer") );
                
                if ($objectType == 'outgoingMail') {
                    $_SESSION['upfile'][$i]['outgoingMail'] = true;
                    array_push($_SESSION['data'], array( 'column' => "type_id", 		'value' => 1, 										'type' => "integer" ) );
                } else {
                    array_push($_SESSION['data'], array( 'column' => "type_id", 		'value' => 0, 										'type' => "integer" ) );
                }
                

                if ($_SESSION['origin'] == "scan") {
                    array_push($_SESSION['data'], array( 'column' => "scan_user", 	'value' => $_SESSION['user']['UserId'], 			'type' => "string" ) );
                    array_push($_SESSION['data'], array( 'column' => "scan_date", 	'value' => $req->current_datetime(), 				'type' => "function" ) );
                }
                if (isset($_SESSION['attachmentInfo'][$i]['back_date']) && $_SESSION['attachmentInfo'][$i]['back_date'] <> '') {
                    array_push($_SESSION['data'], array( 'column' => "validation_date", 'value' => $func->format_date_db($_SESSION['attachmentInfo'][$i]['back_date']), 'type' => "date", ) );
                }

                if (isset($_SESSION['attachmentInfo'][$i]['contactId']) && $_SESSION['attachmentInfo'][$i]['contactId'] != '' && is_numeric($_SESSION['attachmentInfo'][$i]['contactId'])) {
                    array_push($_SESSION['data'], array( 'column' => 'dest_contact_id', 'value' => $_SESSION['attachmentInfo'][$i]['contactId'], 'type' => 'integer' ) );
                } else if (isset($_SESSION['attachmentInfo'][$i]['contactId']) && $_SESSION['attachmentInfo'][$i]['contactId'] != '' && !is_numeric($_SESSION['attachmentInfo'][$i]['contactId'])) {
                    $_SESSION['data'][] = [
                            'column' => 'dest_user',
                            'value' => $_SESSION['attachmentInfo'][$i]['contactId'],
                            'type' => 'string',
                        ];
                }

                if (isset($_SESSION['attachmentInfo'][$i]['addressId']) && $_SESSION['attachmentInfo'][$i]['addressId'] <> '') {
                    array_push($_SESSION['data'], array( 'column' => "dest_address_id", 'value' => $_SESSION['attachmentInfo'][$i]['addressId'], 'type' => "integer" ) );
                }

                if ($_SESSION['targetAttachment'] == 'add'){
					$relation       = 1;
                    if(!empty($_SESSION['attachmentInfo'][$i]['chrono'])){
                        $identifier     = $_SESSION['attachmentInfo'][$i]['chrono'];
                    }else{
                        $identifier     = null;    
                    }
					
					$attachmentType = $_SESSION['attachmentInfo'][$i]['type'];
					$TableName      = RES_ATTACHMENTS_TABLE;

                } else if ($_SESSION['targetAttachment'] == 'edit') {

		            if ((int)$_SESSION['relationAttachment'] > 1) {
		                $column_res = 'res_id_version';
		            } else {
		                $column_res = 'res_id';
		            }

		            $stmt = $db->query("SELECT attachment_type, identifier, relation, attachment_id_master 
		                            FROM res_view_attachments
		                            WHERE ".$column_res." = ? and res_id_master = ?
		                            ORDER BY relation desc", array($_SESSION['resIdVersionAttachment'], $_SESSION['doc_id']));

					$previous_attachment = $stmt->fetchObject();
					$relation            = (int)$previous_attachment->relation;
					$relation++;
                    if(!empty($previous_attachment->identifier)){
                        $identifier      = $previous_attachment->identifier;
                    }else{
                        $identifier      = null;    
                    }
					$attachmentType      = $previous_attachment->attachment_type;
					$TableName           = 'res_version_attachments';

					if ((int)$previous_attachment->attachment_id_master == 0) {
						$attachmentIdMaster = $_SESSION['resIdVersionAttachment'];
					} else {
						$attachmentIdMaster = $previous_attachment->attachment_id_master;
					}
					array_push($_SESSION['data'], array( 'column' => "attachment_id_master", 'value' => $attachmentIdMaster, 'type' => "integer" ) );
                }
                
                array_push($_SESSION['data'], array( 'column' => "relation", 		'value' => $relation,								'type' => "integer" ) );
                array_push($_SESSION['data'], array( 'column' => "identifier", 		'value' => $identifier, 							'type' => "string" ) );
                array_push($_SESSION['data'], array( 'column' => "attachment_type", 'value' => $attachmentType, 						'type' => "string" ) );

                $resAttach = new resource();

				$id = $resAttach->load_into_db(
					$TableName,
					$storeResult['destination_dir'],
					$storeResult['file_destination_name'] ,
					$storeResult['path_template'],
					$storeResult['docserver_id'], $_SESSION['data'],
					$_SESSION['config']['databasetype']
				);

				$_SESSION['attachmentInfo'][$i]['inProgressResId'] = $id;
	        }
        }
    }
}
