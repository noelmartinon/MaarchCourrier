<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Export seda Action
* @author dev@maarch.org
* @ingroup export seda
*/

/**
* $confirm  bool true
*/
$confirm = true;
$frm_width='100%';
$frm_height = 'auto';
/**
* $etapes  array Contains only one etap, the status modification
*/
 
$etapes = array('form');
require_once __DIR__ . '/class/ArchiveTransfer.php';
require_once __DIR__ . '/RequestSeda.php';
//require_once __DIR__.'/StreamClient.php';

function get_form_txt($values, $path_manage_action, $id_action, $table, $module, $coll_id, $mode)
{
    $archiveTransfer = new ArchiveTransfer();
    $db = new RequestSeda();
    foreach ($values as $value) {
        $letter = $db->getLetter($value);

        if ($letter->status == 'SEND_SEDA') {
            $_SESSION['error'] = _ERROR_MESSAGE_ALREADY_SENT . " " . $value;
        }
    }

    if (!$_SESSION['error']) {
        $result = $archiveTransfer->deleteMessage($values);

        $result = $archiveTransfer->receive($values);

        $db = new Database();
        $stmt = $db->query("select message_id from unit_identifier where res_id = ?", array($values[0]));
        $unitIdentifier = $stmt->fetchObject();
        $stmt = $db->query("select data from seda where message_id = ?", array($unitIdentifier->message_id));

        $messageData = $stmt->fetchObject();

        $messageObject = json_decode($messageData->data);


        $frm_str = '<div id="frm_error_'.$id_action.'" class="error"></div>';
        $frm_str .= '<h2 class="title">'._MESSAGE.' '. $messageObject->messageIdentifier->value;
        $frm_str .= '</h2><br/>';
        $frm_str .= '<div class="block forms details" >';

        // Information Message
        $frm_str .= '<h3 class="title">'._INFORMATION_MESSAGE.'</h3>';
        $frm_str .= '<table width="100%" cellspacing="2" cellpading="2" border="0"><tbody><tr class="col"><br/>';
        $frm_str .='<td><b>'._MESSAGE_IDENTIFIER.':</b></td>';
        $frm_str .= '<td><input type="text" id="messageIdentifier" name="messageIdentifier" value="'.$messageObject->messageIdentifier->value. '" disabled></td>';
        $frm_str .='<td><b>'._DATE.':</b></td>';
        $frm_str .= '<td><input type="text" id="date" name="date" value="'.$messageObject->date. '" disabled></td></tr><tr class="col">';
        $frm_str .='<td><b>'._ARCHIVAL_AGREEMENT.':</b></td>';
        $frm_str .= '<td><input type="text" id="archivalAgreement" name="archivalAgreement" value="'.$messageObject->archivalAgreement->value. '" disabled></td>';
        $frm_str .='<td><b>'._ARCHIVAL_AGENCY_SIREN.':</b></td>';
        $frm_str .= '<td><input type="text" id="archivalAgency" name="archivalAgency" value="'.$messageObject->archivalAgency->identifier->value. '" disabled></td></tr><tr class="col">';
        $frm_str .='<td><b>'._TRANSFERRING_AGENCY_SIREN.':</b></td>';
        $frm_str .= '<td><input type="text" id="transferringAgency" name="transferringAgency" value="'.$messageObject->transferringAgency->identifier->value. '" disabled></td>';
        $frm_str .= '</tr></tbody></table><hr />';


        foreach ($messageObject->dataObjectPackage->descriptiveMetadata as $archiveUnit) {
            $frm_str .= viewArchiveUnit($archiveUnit);
        }

        $path_to_script = $_SESSION['config']['businessappurl']."index.php?display=true&module=export_seda";

        $frm_str .= '</div>';
        $frm_str .='<div align="center">';
        $frm_str .='<input type="button" name="zip" id="zip" class="button"  value="'._ZIP.'" onclick="actionSeda(\''.$path_to_script.'&page=Ajax_seda_zip&reference='.$messageObject->messageIdentifier->value.'\',\'zip\');"/>&nbsp&nbsp&nbsp';
        if (file_exists(__DIR__.DIRECTORY_SEPARATOR. 'xml' . DIRECTORY_SEPARATOR . "config.xml")) {
            $frm_str .= '<input type="button" name="sendMessage" id="sendMessage" class="button"  value="' . _SEND_MESSAGE . '" onclick="actionSeda(\'' . $path_to_script . '&page=Ajax_transfer_SAE&reference=' . $messageObject->messageIdentifier->value . '&resIds=' . $result . '\',\'sendMessage\');"/>';
        }
            $frm_str .='</div>';

        $frm_str .='<div align="center"  name="validSend" id="validSend" style="display: none "><input type="button" class="button" name="validateMessage" id="validateMessage" value="'._VALIDATE_MANUAL_DELIVERY.'" onclick="actionSeda(\''.$path_to_script.'&page=Ajax_validate_change_status&reference='.$messageObject->messageIdentifier->value.'\',\'validateMessage\');"/></div>';
    } else {
        $frm_str .='<div align="center" style="color:red">';
        $frm_str .= $_SESSION['error'];
        $frm_str .='</div>';
    }

    //$config = parse_ini_file(__DIR__.'/config.ini');
    $frm_str .= '<div align="center"  name="valid" id="valid" style="display: none "><br><input type="button" class="button" name="validateReload" id="validateReload" value="' . _VALIDATE . '" onclick="window.location.reload()"/></div>';
    $frm_str .= '<hr />';

    $frm_str .='<div align="center">';
    $frm_str .='<input type="button" name="cancel" id="cancel" class="button"  value="'._CANCEL.'" onclick="pile_actions.action_pop();destroyModal(\'modal_'.$id_action.'\');"/>';
    $frm_str .='</div>';

    return addslashes($frm_str);
}

function manage_form($arr_id, $history, $id_action, $label_action, $status)
{
    // récupérer l'entité racine du courrier *
    // récupérer archival_agency et archival_agreement *

    // récupérer la retention_final_disposition et retention_rule du type de doc du courrier *

    // appel fonction de transfert et génération bdx *

    

    // historisation du transfert

    // modification statut -> fait automatiquement par mécanique bannette

    // ensuite il y a aura une suppression logique des documents et des contacts (si plus de courriers associés)

    //return array('result' => $result, 'history_msg' => '');
}

function viewArchiveUnit($archiveUnit, $archiveUnitChildren = false) 
{
    $frm_str = '';
    if (!$archiveUnitChildren) {
        $frm_str .= '<h3 class="title">'._INFORMATION_ARCHIVE. ' "'. $archiveUnit->content->title[0].'"</h3>';
    } else {
        $frm_str .= '<h4 class="title">'._INFORMATION_ARCHIVE_CHILDREN. ' "'. $archiveUnit->content->title[0].'"</h4>';
    }
    
    $frm_str .= '<table width="100%" cellspacing="2" cellpading="2" border="0"><tbody><tr class="col"><br/>';
    $frm_str .='<td><b>'._ARCHIVE_IDENTIFIER.':</b></td>';
    $frm_str .= '<td><input type="text" id="archiveIdentifier" name="archiveIdentifier" value="'.$archiveUnit->id. '" disabled></td></tr>';

    if ($archiveUnit->management) {
        
        $frm_str .='<tr class="col"><td><b>'._APPRAISAL_RULE.':</b></td>';
        $frm_str .= '<td><input type="text" id="rule" name="rule" value="'.$archiveUnit->management->appraisalRule->rule->value. '" disabled></td>';
        $frm_str .='<td><b>'._APPRAISAL_FINAL_DISPOSITION.':</b></td>';

        if ($archiveUnit->management->appraisalRule->finalAction == 'Destroy') {
            $frm_str .= '<td><input type="text" id="finalAction" name="finalAction" value="'._DESTROY. '" disabled></td>';
        } else {
            $frm_str .= '<td><input type="text" id="finalAction" name="finalAction" value="'._KEEP. '" disabled></td>';
        }
        $frm_str .= '</tr>';
    }

    $frm_str .= '<tr class="col"><td><b>'._DESCRIPTION_LEVEL.':</b></td>';

    if ($archiveUnit->content->descriptionLevel == "File") {
        $frm_str .= '<td><input type="text" id="descriptionLevel" name="descriptionLevel" value="'._FILE. '" disabled></td>';
    }elseif ($archiveUnit->content->descriptionLevel == "Item") {
        $frm_str .= '<td><input type="text" id="descriptionLevel" name="descriptionLevel" value="'._ITEM. '" disabled></td>';
    }else {
        $frm_str .= '<td><input type="text" id="descriptionLevel" name="descriptionLevel" value="'.$archiveUnit->content->descriptionLevel. '" disabled></td>';
    }
    
    $frm_str .= '<td><b>'._DOCUMENT_TYPE.':</b></td>';

    if ($archiveUnit->content->documentType == "Reply") {
        $frm_str .= '<td><input type="text" id="documentType" name="documentType" value="'._REPLY. '" disabled></td></tr>';
    }elseif ($archiveUnit->content->documentType == "Attachment") {
        $frm_str .= '<td><input type="text" id="documentType" name="documentType" value="'._ATTACHMENT. '" disabled></td></tr>';
    } else {
        $frm_str .= '<td><input type="text" id="documentType" name="documentType" value="'.$archiveUnit->content->documentType. '" disabled></td></tr>';
    }

    
    
    if ($archiveUnit->content->receivedDate) {
        $frm_str .= '<tr class="col"><td><b>'._RECEIVED_DATE.':</b></td>';
        $frm_str .= '<td><input type="text" id="receivedDate" name="receivedDate" value="'.$archiveUnit->content->receivedDate. '" disabled></td>';
    }
    
    if ($archiveUnit->content->sentDate) {
        $frm_str .= '<td><b>'._SENT_DATE.':</b></td>';
        $frm_str .= '<td><input type="text" id="sentDate" name="sentDate" value="'.$archiveUnit->content->sentDate. '" disabled></td></tr>';
    }
    
    $frm_str .= '</tr></tbody></table>';

    if ($archiveUnit->archiveUnit) {
        foreach ($archiveUnit->archiveUnit as $archiveUnitChildren) {
            $frm_str .= viewArchiveUnit($archiveUnitChildren,true);
        }
    }

    return $frm_str;
}
