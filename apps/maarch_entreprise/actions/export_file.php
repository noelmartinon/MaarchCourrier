<?php
/*
*    Copyright 2008,2009 Maarch
*
*  This file is part of Maarch Framework.
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
*    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief   Action : export file
*
* Export file to a gived path. Used by the core (manage_action.php page).
*
* @file
* @author Nathan Cheval <nathan.cheval@edissyum.com>
* @date $date$
* @version $Revision$
* @ingroup apps
*/

/**
* $confirm  bool true
*/
 $confirm = false;


 $etapes = array('export');
 
function manage_export($arr_id, $history, $id_action, $label_action, $status)
{

	$result = '';
	require_once('core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'docservers_controler.php');
	require_once('core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php');
    require_once('apps' . DIRECTORY_SEPARATOR . 'maarch_entreprise' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_export.php');
	$dc = new docservers_controler();
	$sec = new security();
	$export = new export();

	$ind_coll = $sec -> get_ind_collection($_POST['coll_id']);
    $ind_coll_attachment = $sec -> get_ind_collection('attachments_coll');

	$table = $_SESSION['collections'][$ind_coll]['table'];
	$tableAttachment = $_SESSION['collections'][$ind_coll_attachment]['table'];
	$adr = $_SESSION['collections'][$ind_coll]['adr'];
    $adrAttachment = $_SESSION['collections'][$ind_coll_attachment]['adr'];

    $path = $export -> get_path();
    $authorizedAttachmentTypes = $export -> get_authorized_attachment_type();
    $attachmentSuffix = $export -> get_suffix_attachment();

    if(!is_writable($path)){
        $_SESSION['error'] = _PATH_ERROR;
        return array('result' => '', 'history_msg' => '');
    }

    for($i=0; $i<count($arr_id );$i++) {
        if(!$export -> check_status($arr_id[$i])){
            $_SESSION['error'] = _STATUS_ERROR;
            continue;
        }
        // begin document export
        $resource = $dc -> viewResource($arr_id[$i], $table, $adr);   // resource of the exported document
        if($resource['status'] == 'ok') {
            $name = $export -> generate_name($arr_id[$i]);
            $finalName = $path . '' . $name;
            $tmpFilePath = $resource['file_path'];
            if(copy($tmpFilePath, $finalName)){
                $time = strtotime($export -> retrieve_creation_date($arr_id[$i], $table));  // get the creation date of the exported document
                touch($finalName, $time);                                                   // and modify the creation date of the exported file with it to keep track of the document date.
                // begin attachment export
                $listAttachments = $export -> retrieve_attachments($arr_id[$i], $authorizedAttachmentTypes);
                if(!empty($listAttachments)){
                    $attachmentNumber = 1;
                    foreach($listAttachments as $attachment){
                        $resourceAttachment = $dc -> viewResource($attachment['res_id'], $tableAttachment, $adrAttachment);     // Resource of the exported document's attachment
                        if($resourceAttachment['status'] == 'ok'){
                            $tmpAttachmentFilePath = $resourceAttachment['file_path'];
                            $attachmentName = $export -> generate_name($arr_id[$i], $attachmentSuffix, $attachmentNumber, true);
                            $attachmentFinalName = $path . '' . $attachmentName;
                            if(copy($tmpAttachmentFilePath, $attachmentFinalName)){
                                $attachmentTime = strtotime($export -> retrieve_creation_date($attachment['res_id'], $tableAttachment));
                                touch($attachmentFinalName, $attachmentTime);
                            }else{
                                $_SESSION['error'] = _ATTACHMENT_COPY_ERROR;
                            }
                            $attachmentNumber = $attachmentNumber + 1;
                        }else{
                            $_SESSION['error'] = _ERROR_WHILE_RETRIEVING_ATTACHMENT_FILE;
                        }
                    }
                }
                $export -> change_status($arr_id[$i]);
                $resIdList[$i] = $arr_id[$i];
            }else {
                $_SESSION['error'] = _COPY_ERROR;
            }
        }else{
            $_SESSION['error'] = _ERROR_WHILE_RETRIEVING_FILE;
        }
    }
    if(count($resIdList) == 1)
        $result = " " . _N° . " " . $resIdList[0] . " " . _WITH_SUCCESS . "#";
    else
        $result .= " " . _N° . " " . join(', ', $resIdList) . " " . _WITH_SUCCESS . "#";
    $list = join('#', $resIdList) . '#';

	return array('result' => $list, 'history_msg' => $result);
 }


