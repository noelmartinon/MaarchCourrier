<?php

$s_id = $_REQUEST['resId'];
$category = $_REQUEST['category'];
$coll_id = $_REQUEST['collId'];

$onlyCC = '';

$roles = json_decode(urldecode($_REQUEST['roles']));

require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_request.php';
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php';

$security = new security();
$right = $security->test_right_doc($coll_id, $s_id);

if (!$right) {
    exit(_NO_RIGHT_TXT);
}

$core_tools = new core_tools();
$core_tools->load_lang();
$core_tools->load_html();
$core_tools->load_header('', true, false);
$core_tools->load_js();

if (isset($_REQUEST['fromDetail']) && $_REQUEST['fromDetail'] == true) {
    $from_detail = true;
} else {
    $from_detail = false;
}

if ($from_detail == true) {
    if ($core_tools->test_service('update_list_diff_in_details', 'entities', false)) {
        if ($core_tools->test_service('add_copy_in_indexing_validation', 'entities', false)) {
            $onlyCC = '&only_cc';
        }
        $frm_str .= '<br />';
        $frm_str .= '<div style="text-align:center;">';

        $frm_str .= '<input type="button" class="button" title="'._UPDATE_LIST_DIFF.'" value="'._UPDATE_LIST_DIFF.'" onclick="window.open(\''
            .$_SESSION['config']['businessappurl']
            .'index.php?display=true&module=entities&page=manage_listinstance&cat='.$category.'&origin=details'.$onlyCC.'\', \'\', \'scrollbars=yes,menubar=no,toolbar=no,status=no,resizable=yes,width=1280,height=980,location=no\');" />';
        $frm_str .= '<input type="button" class="button" style="display:none;" id="save_list_diff" onClick="saveListDiff(\'listinstance\', \''.$_SESSION['tablename']['ent_listinstance'].'\', \''.$coll_id.'\', \''.$s_id.'\',\''.$_SESSION['user']['UserId'].'\', \''.true.'\',\''.false.'\');$(\'div_diff_list_message\').show();" value="'._STORE_DIFF_LIST.'" />';
        $frm_str .= '</div>';
        $frm_str .= '<br />';
        $frm_str .= '<div id="div_diff_list_message" style="color:red;text-align: center;"></div>';
        $frm_str .= '<br />';
    }
    $difflist = $_SESSION['details']['diff_list'];
} else {
    if (isset($_REQUEST['only_cc'])) {
        $onlyCC = '&only_cc';
    }
    if ($core_tools->test_service('add_copy_in_process', 'entities', false)) {
        $frm_str .= '<div style="text-align:center;"><input type="button" class="button" title="'._UPDATE_LIST_DIFF.'" value="'._UPDATE_LIST_DIFF.'" onclick="window.open(\''
                .$_SESSION['config']['businessappurl']
                .'index.php?display=true&module=entities&cat='.$category.'&page=manage_listinstance'
                .'&origin=process'.$onlyCC.'\', \'\', \'scrollbars=yes,menubar=no,'
                .'toolbar=no,status=no,resizable=yes,width=1024,height=650,location=no\');" /></div>';
    }
    // Get content from buffer of difflist_display
    $difflist = $_SESSION['process']['diff_list'];
}

ob_start();
require_once 'modules/entities/difflist_display.php';
$frm_str .= str_replace(array("\r", "\n", "\t"), array('', '', ''), ob_get_contents());
ob_end_clean();

$frm_str .= '<br/>';
$frm_str .= '<br/>';
$frm_str .= '<span class="diff_list_history" style="width: 90%; cursor: pointer;" onmouseover="this.style.cursor=\'pointer\';" onclick="new Effect.toggle(\'diff_list_history_div\', \'blind\', {delay:0.2});whatIsTheDivStatus(\'diff_list_history_div\', \'divStatus_diff_list_history_div\');return false;">';
$frm_str .= '<span id="divStatus_diff_list_history_div" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span>';
$frm_str .= '<b>&nbsp;<small>'._DIFF_LIST_HISTORY.'</small></b>';
$frm_str .= '</span>';
$frm_str .= '<div id="diff_list_history_div" style="display:none">';

$return_mode = true;
$diffListType = 'entity_id';
require_once 'modules/entities/difflist_history_display.php';

echo $frm_str;
