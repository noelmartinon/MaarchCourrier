<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   visa_workflow
* @author  dev <dev@maarch.org>
* @ingroup visa
*/
require_once 'modules/visa/class/class_modules_tools.php';
$visa = new visa();

if ($visa->currentUserSignRequired($_SESSION['doc_id']) == 'true') {
    $confirm = true;
    $label_action .=" ("._NO_USER_SIGNED_DOC.")";
} else {
    $confirm = false;
}
$etapes = ['empty_error'];

function manage_empty_error($arr_id, $history, $id_action, $label_action, $status)
{
    $db = new Database();
    $result = '';

    if (!empty($_SESSION['stockCheckbox'])) {
        $arr_id = $_SESSION['stockCheckbox'];
    }

    for ($i=0; $i<count($arr_id);$i++) {
        $_SESSION['action_error'] = '';
        $coll_id = $_SESSION['current_basket']['coll_id'];
        $res_id = $arr_id[$i];
        include_once "core/class/class_security.php";
        $sec = new security();
        include_once "core/class/class_history.php";
        $history = new history();
        $table = $sec->retrieve_table_from_coll($coll_id);
        $circuit_visa = new visa();
        $sequence = $circuit_visa->getCurrentStep($res_id, $coll_id, 'VISA_CIRCUIT');
        $stepDetails = array();
        $stepDetails = $circuit_visa->getStepDetails($res_id, $coll_id, 'VISA_CIRCUIT', $sequence);
        $message = [];

        //enables to process the visa if i am not the item_id
        if ($stepDetails['item_id'] <> $_SESSION['user']['UserId']) {
            $stmt = $db->query(
                "UPDATE listinstance SET process_date = CURRENT_TIMESTAMP "
                . " WHERE listinstance_id = ? AND item_mode = ? AND res_id = ? AND item_id = ? AND difflist_type = ?",
                array($stepDetails['listinstance_id'], $stepDetails['item_mode'], $res_id, $stepDetails['item_id'], 'VISA_CIRCUIT')
            );

            $stmt = $db->query("SELECT firstname, lastname, user_id FROM users WHERE user_id IN (?)", array([$_SESSION['user']['UserId'], $stepDetails['item_id']]));
            foreach ($stmt as $value) {
                if ($value['user_id'] == $_SESSION['user']['UserId']) {
                    $user1 = $value['firstname'] . ' ' . $value['lastname'];
                } else {
                    $user2 = $value['firstname'] . ' ' . $value['lastname'];
                }
            }

            $message[] = " " ._VISA_BY . " " . $user1 . " " . _INSTEAD_OF . " " . $user2;
        } else {
            $stmt = $db->query(
                "UPDATE listinstance SET process_date = CURRENT_TIMESTAMP "
                . " WHERE listinstance_id = ? AND item_mode = ? AND res_id = ? AND item_id = ? AND difflist_type = ?",
                array($stepDetails['listinstance_id'], $stepDetails['item_mode'], $res_id, $_SESSION['user']['UserId'], 'VISA_CIRCUIT')
            );
            $message[] = "";
        }

        if ($circuit_visa->getCurrentStep($res_id, $coll_id, 'VISA_CIRCUIT') == $circuit_visa->nbVisa($res_id, $coll_id)) {
            $mailStatus = 'ESIG';
            $db->query("UPDATE res_letterbox SET status = ? WHERE res_id = ? ", [$mailStatus, $res_id]);
        }
        $result .= $arr_id[$i] . '#';
    }

    return array('result' => $result, 'history_msg' => $message);
}
