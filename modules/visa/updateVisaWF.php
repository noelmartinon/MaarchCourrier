<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   updateVisaWF
* @author  dev <dev@maarch.org>
* @ingroup visa
*/

require_once "modules" . DIRECTORY_SEPARATOR . "visa" . DIRECTORY_SEPARATOR
        . "class" . DIRECTORY_SEPARATOR
        . "class_modules_tools.php";

$res_id  = $_REQUEST['resId'];
$coll_id = 'letterbox_coll';

$userList = json_decode($_REQUEST['userList']);

$visa = new visa();
$_SESSION['visa_wf']['diff_list']['visa']['users'] = array();
$_SESSION['visa_wf']['diff_list']['sign']['users'] = array();

$i = 0;
if ($userList) {
    foreach ($userList as $user) {
        if (++$i === count($userList)) {
            array_push(
                $_SESSION['visa_wf']['diff_list']['sign']['users'], array(
                    'user_id'               => $user->userId,
                    'process_comment'       => $user->userConsigne,
                    'process_date'          => $user->userVisaDate,
                    'viewed'                => 0,
                    'visible'               => 'Y',
                    'difflist_type'         => 'VISA_CIRCUIT',
                    'signatory'             => $user->userSignatory,
                    'requested_signature'   => $user->userRequestSign
                    )
            );
        } else {
            array_push(
                $_SESSION['visa_wf']['diff_list']['visa']['users'], array(
                    'user_id'               => $user->userId,
                    'process_comment'       => $user->userConsigne,
                    'process_date'          => $user->userVisaDate,
                    'viewed'                => 0,
                    'visible'               => 'Y',
                    'difflist_type'         => 'VISA_CIRCUIT',
                    'signatory'             => $user->userSignatory,
                    'requested_signature'   => $user->userRequestSign
                    )
            );
        }
    }
}
$visa->saveWorkflow($res_id, $coll_id, $_SESSION['visa_wf']['diff_list'], 'VISA_CIRCUIT');

$db   = new Database();
$stmt = $db->query("SELECT status FROM res_letterbox WHERE res_id = ?", array($res_id));
$res  = $stmt->fetchObject();
if ($res->status == 'EVIS' || $res->status == 'ESIG') {
    $visa->setStatusVisa($res_id, $coll_id);
}

//LOAD TOOLBAR BADGE
$toolbarBagde_script = $_SESSION['config']['businessappurl'] . 'index.php?display=true&module=visa&page=load_toolbar_visa&origin=parent&resId=' . $res_id . '&collId=' . $coll_id;
$js ='loadToolbarBadge(\'visa_tab\',\'' . $toolbarBagde_script . '\');';


echo "{\"status\" : 0, \"exec_js\" : \"" . $js . "\"}";
exit();
