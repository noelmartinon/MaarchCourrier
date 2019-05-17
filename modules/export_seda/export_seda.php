<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Export seda Action
 *
 * @author dev@maarch.org
 * @ingroup export seda
 */

/**
 * $confirm  bool true.
 */
$confirm = true;
$frm_width = '100%';
$frm_height = 'auto';
/**
 * $etapes  array Contains only one etap, the status modification.
 */

$etapes = array('form');
require_once __DIR__.'/class/ArchiveTransfer.php';
require_once __DIR__.'/RequestSeda.php';
//require_once __DIR__.'/StreamClient.php';

function get_form_txt($values, $path_manage_action, $id_action, $table, $module, $coll_id, $mode)
{
    $archiveTransfer = new ArchiveTransfer();
    $db = new RequestSeda();
    foreach ($values as $value) {
        $status = $db->getStatusLetter($value);

        if ($status == 'SEND_SEDA') {
            $_SESSION['error'] = _ERROR_MESSAGE_ALREADY_SENT.' '.$value;
        }
    }

    $result = $archiveTransfer->deleteMessage($values);

    $result = $archiveTransfer->receive($values);

    if (!$_SESSION['error']) {
        $db = new Database();
        $stmt = $db->query('select message_id from unit_identifier where res_id = ?', array($values[0]));
        $unitIdentifier = $stmt->fetchObject();
        $stmt = $db->query('select data from message_exchange where message_id = ?', array($unitIdentifier->message_id));

        $messageData = $stmt->fetchObject();

        $messageObject = json_decode($messageData->data);

        $frm_str = '<div id="frm_error_'.$id_action.'" class="error"></div>';
        $frm_str .= '<h2 class="title">'._MESSAGE.' '.$messageObject->MessageIdentifier->value;
        $frm_str .= '</h2><br/>';
        $frm_str .= '<div class="block forms details" >';

        // Information Message
        $frm_str .= '<div align="center"><h3>'._PACKAGE_TITLE.'</h3><br/>';
        $frm_str .= '<input type="text" id="messageTitle" name="messageTitle" placeholder="'._PACKAGE_TITLE.'"></div><br/>';
        $frm_str .= '<h4 class="title">'._INFORMATION_MESSAGE.'</h4>';
        $frm_str .= '<table width="100%" cellspacing="2" cellpading="2" border="0"><tbody><tr class="col"><br/>';
        $frm_str .= '<td><b>'._MESSAGE_IDENTIFIER.':</b></td>';
        $frm_str .= '<td><input type="text" id="messageIdentifier" name="messageIdentifier" value="'.$messageObject->MessageIdentifier->value.'" disabled></td>';
        $frm_str .= '<td><b>'._DATE.':</b></td>';
        $frm_str .= '<td><input type="text" id="date" name="date" value="'.$messageObject->Date.'" disabled></td></tr><tr class="col">';
        $frm_str .= '<td><b>'._ARCHIVAL_AGREEMENT.':</b></td>';
        $frm_str .= '<td><input type="text" id="archivalAgreement" name="archivalAgreement" value="'.$messageObject->ArchivalAgreement->value.'" disabled></td>';
        $frm_str .= '<td><b>'._ARCHIVAL_AGENCY_SIREN.':</b></td>';
        $frm_str .= '<td><input type="text" id="archivalAgency" name="archivalAgency" value="'.$messageObject->ArchivalAgency->Identifier->value.'" disabled></td></tr><tr class="col">';
        $frm_str .= '<td><b>'._TRANSFERRING_AGENCY_SIREN.':</b></td>';
        $frm_str .= '<td><input type="text" id="transferringAgency" name="transferringAgency" value="'.$messageObject->TransferringAgency->Identifier->value.'" disabled></td>';
        $frm_str .= '</tr></tbody></table><hr />';

        foreach ($messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit as $archiveUnit) {
            $frm_str .= viewArchiveUnit($archiveUnit);
        }

        $path_to_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=export_seda';

        $frm_str .= '</div>';
        $frm_str .= '<div align="center">';
        //$frm_str .='<input type="button" name="generateMessage" id="generateMessage" class="button"  value="'._GENERATE_MESSAGE.'" onclick="actionSeda(\''.$path_to_script.'&page=Ajax_generate_message&reference='.$messageObject->MessageIdentifier->value.'\',\'generateMessage\');"/>&nbsp&nbsp&nbsp';
        $frm_str .= '<input type="button" name="zip" id="zip" class="button"  value="'._ZIP.'" onclick="actionSeda(\''.$path_to_script.'&page=Ajax_generate_message&reference='.$messageObject->MessageIdentifier->value.'|'.$path_to_script.'&page=Ajax_seda_zip&reference='.$messageObject->MessageIdentifier->value.'\',\'zip\');"/>&nbsp&nbsp&nbsp';
        if (file_exists(__DIR__.DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.'config.xml')) {
            $frm_str .= '<input type="button" name="sendMessage" id="sendMessage" style="disabled: disabled " class="button"  value="'._SEND_MESSAGE.'" onclick="actionSeda(\''.$path_to_script.'&page=Ajax_generate_message&reference='.$messageObject->MessageIdentifier->value.'|'.$path_to_script.'&page=Ajax_transfer_SAE&reference='.$messageObject->MessageIdentifier->value.'&resIds='.$result.'\',\'sendMessage\');"/>';
        }
        $frm_str .= '</div>';

        $frm_str .= '<div align="center"  name="validSend" id="validSend" style="display: none "><input type="button" class="button" name="validateMessage" id="validateMessage" value="'._VALIDATE_MANUAL_DELIVERY.'" onclick="actionSeda(\''.$path_to_script.'&page=Ajax_validate_change_status&reference='.$messageObject->MessageIdentifier->value.'\',\'validateMessage\');"/></div>';
    } else {
        $frm_str .= '<div align="center" style="color:red">';
        $frm_str .= $_SESSION['error'];
        $frm_str .= '</div>';
    }

    //$config = parse_ini_file(__DIR__.'/config.ini');
    $frm_str .= '<div align="center"  name="valid" id="valid" style="display: none "><br><input type="button" class="button" name="validateReload" id="validateReload" value="'._VALIDATE.'" onclick="triggerAngular(\'#/basketList/users/'.$_SESSION['urlV2Basket']['userId'].'/groups/'.$_SESSION['urlV2Basket']['groupIdSer'].'/baskets/'.$_SESSION['urlV2Basket']['basketId'].'\');"/></div>';
    $frm_str .= '<hr />';

    $frm_str .= '<div align="center">';
    $frm_str .= '<input type="button" name="cancel" id="cancel" class="button"  value="'._CANCEL.'" onclick="pile_actions.action_pop();destroyModal(\'modal_'.$id_action.'\');"/>';
    $frm_str .= '</div>';

    return addslashes($frm_str);
}

function viewArchiveUnit($archiveUnit, $archiveUnitChildren = false)
{
    $frm_str = '';
    $frm_str .= '<h4 onclick="new Effect.toggle(\''.$archiveUnit->id.'_fields\', \'blind\', {delay:0.2});'
        .'whatIsTheDivStatus(\''.$archiveUnit->id.'_fields\', \'divStatus_'.$archiveUnit->id.'_fields\');" '
        .'class="categorie" style="width:90%;" onmouseover="this.style.cursor=\'pointer\';">';

    if (!$archiveUnitChildren) {
        $frm_str .= ' <span id="divStatus_'.$archiveUnit->id.'_fields" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span>&nbsp;'
            ._INFORMATION_ARCHIVE.' "'.$archiveUnit->Content->Title[0];
    } else {
        $frm_str .= ' <span style="margin-left:2%" id="divStatus_'.$archiveUnit->id.'_fields" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span>&nbsp;'
            ._INFORMATION_ARCHIVE_CHILDREN.' "'.$archiveUnit->Content->Title[0];
    }

    $frm_str .= '</h4>';
    $frm_str .= '<div id="'.$archiveUnit->id.'_fields"  style="display:none">';

    $frm_str .= '<table width="100%" cellspacing="2" cellpading="2" border="0"><tbody><tr class="col"><br/>';
    $frm_str .= '<td><b>'._ARCHIVE_IDENTIFIER.':</b></td>';
    $frm_str .= '<td><input type="text" id="archiveIdentifier" name="archiveIdentifier" value="'.$archiveUnit->id.'" disabled></td></tr>';

    if ($archiveUnit->Management) {
        $frm_str .= '<tr class="col"><td><b>'._APPRAISAL_RULE.':</b></td>';
        $frm_str .= '<td><input type="text" id="rule" name="rule" value="'.$archiveUnit->Management->AppraisalRule->Rule->value.'" disabled></td>';
        $frm_str .= '<td><b>'._APPRAISAL_FINAL_DISPOSITION.':</b></td>';

        if ($archiveUnit->Management->AppraisalRule->FinalAction == 'Destroy') {
            $frm_str .= '<td><input type="text" id="finalAction" name="finalAction" value="'._DESTROY.'" disabled></td>';
        } else {
            $frm_str .= '<td><input type="text" id="finalAction" name="finalAction" value="'._KEEP.'" disabled></td>';
        }
        $frm_str .= '</tr>';
    }

    $frm_str .= '<tr class="col"><td><b>'._DESCRIPTION_LEVEL.':</b></td>';

    if ($archiveUnit->Content->DescriptionLevel == 'File') {
        $frm_str .= '<td><input type="text" id="descriptionLevel" name="descriptionLevel" value="'._FILE.'" disabled></td>';
    } elseif ($archiveUnit->Content->DescriptionLevel == 'Item') {
        $frm_str .= '<td><input type="text" id="descriptionLevel" name="descriptionLevel" value="'._ITEM.'" disabled></td>';
    } else {
        $frm_str .= '<td><input type="text" id="descriptionLevel" name="descriptionLevel" value="'.$archiveUnit->Content->DescriptionLevel.'" disabled></td>';
    }

    $frm_str .= '<td><b>'._DOCUMENT_TYPE.':</b></td>';

    if ($archiveUnit->Content->DocumentType == 'Reply') {
        $frm_str .= '<td><input type="text" id="documentType" name="documentType" value="'._REPLY.'" disabled></td></tr>';
    } elseif ($archiveUnit->Content->DocumentType == 'Attachment') {
        $frm_str .= '<td><input type="text" id="documentType" name="documentType" value="'._ATTACHMENT.'" disabled></td></tr>';
    } else {
        $frm_str .= '<td><input type="text" id="documentType" name="documentType" value="'.$archiveUnit->Content->DocumentType.'" disabled></td></tr>';
    }

    if ($archiveUnit->Content->SentDate) {
        $frm_str .= '<tr class="col"><td><b>'._SENT_DATE.':</b></td>';
        $frm_str .= '<td><input type="text" id="sentDate" name="sentDate" value="'.$archiveUnit->Content->SentDate.'" disabled></td>';
    }

    if ($archiveUnit->Content->ReceivedDate) {
        $frm_str .= '<td><b>'._RECEIVED_DATE.':</b></td>';
        $frm_str .= '<td><input type="text" id="receivedDate" name="receivedDate" value="'.$archiveUnit->Content->ReceivedDate.'" disabled></td></tr>';
    }

    $frm_str .= '</tr></tbody></table><br>';

    if ($archiveUnit->ArchiveUnit) {
        foreach ($archiveUnit->ArchiveUnit as $archiveUnitChildren) {
            $frm_str .= viewArchiveUnit($archiveUnitChildren, true);
        }
    }

    $frm_str .= '<div>';
    $frm_str .= '</div></div><br>';

    return $frm_str;
}
