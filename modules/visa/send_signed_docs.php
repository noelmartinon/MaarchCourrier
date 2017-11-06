<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   send_signed_docs
* @author  dev <dev@maarch.org>
* @ingroup visa
*/

require_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_db.php';

function checkAllSigned($id)
{
    $db = new Database();
    $stmt = $db->query("SELECT status from res_view_attachments where attachment_type= ? and res_id_master = ?", array('response_project', $id));
    while ($line = $stmt->fetchObject()) {
        if ($line->status == 'TRA' || $line->status == 'A_TRA' ) {
            return false;
        }
    }
    return true;
}

require_once 'modules/visa/class/class_modules_tools.php';
$visa = new visa();

if ($visa->currentUserSignRequired($_SESSION['doc_id']) == 'true') {
    $confirm = true;
    $label_action .=" (".NO_USER_SIGNED_DOC.")";
} else {
    $confirm = false;
}
$etapes = ['empty_error'];
 
/**
* $etapes  array Contains only one etap, the status modification
*/
 $etapes = array('empty_error');
 
function manage_empty_error($arr_id, $history, $id_action, $label_action, $status)
{
    $_SESSION['action_error'] = '';
    $result = '';
    $new_result = '';
    for ($i=0; $i<count($arr_id);$i++) {

        if (checkAllSigned($arr_id[$i]))
            $new_result .= $arr_id[$i].',';
        $result .= $arr_id[$i].'#';
    }
    $new_result = substr($new_result, 0, -1);

    return array('result' => $result, 'history_msg' => '', 'newResultId' => $new_result, 'action_status' => $status);
}

?>
