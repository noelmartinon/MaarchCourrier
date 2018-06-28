<?php
require_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_request.php';
require_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_security.php';
require_once 'modules/cases/class/class_modules_tools.php';

$res_id = $_REQUEST["resId"];
$coll_id = $_REQUEST["collId"];

$security = new security();
$right = $security->test_right_doc($coll_id, $res_id);

if(!$right){
    exit(_NO_RIGHT_TXT);
}

$core_tools = new core_tools();
$core_tools->load_lang();
$core_tools->load_html();
$core_tools->load_header('', true, false);
$core_tools->load_js();

$cases = new cases();
$case_id = $cases->get_case_id($res_id);
if ($case_id <> false) {
    $case_properties = $cases->get_case_info($case_id);  
} else {
    $case_properties = array();
}

if (!isset($case_properties['case_id'])) {
    $case_properties = array();
    $case_properties['case_id'] = '';
    $case_properties['case_label'] = '';
    $case_properties['case_description'] = '';
}


$frm_str .= '<form name="cases" method="post" id="cases" action="#" class="forms addforms2" style="text-align:center;">';
$frm_str .= '<table width="98%" align="center" border="0">';
$frm_str .= '<tr>';
$frm_str .= '<td><label for="case_id" class="case_label" >' . _CASE . '</label></td>';
$frm_str .= '<td>&nbsp;</td>';
$frm_str .= '<td><input type="text" readonly="readonly" class="readonly" name="case_id" id="case_id" value="'
        . $case_properties['case_id'] . '"  onblur=""/>';
$frm_str .= '</td>';
$frm_str .= '</tr>';
$frm_str .= '<tr>';
$frm_str .= '<td><label for="case_label" class="case_label" >' . _CASE_LABEL . '</label></td>';
$frm_str .= '<td>&nbsp;</td>';
$frm_str .= '<td><input type="text" readonly="readonly" class="readonly" name="case_label" '
        . 'id="case_label" onblur="" value="' . $case_properties['case_label'] . '" />';
$frm_str .= '</td>';
$frm_str .= '</tr>';
$frm_str .= '<tr>';
$frm_str .= '<td><label for="case_description" class="case_description" >'
        . _CASE_DESCRIPTION . '</label></td>';
$frm_str .= '<td>&nbsp;</td>';
$frm_str .= '<td><input type="text" readonly="readonly" class="readonly" '
        . 'name="case_description" id="case_description" onblur="" value="'
        . $case_properties['case_description'] . '" />';
$frm_str .= '</td>';
$frm_str .= '</tr>';
$frm_str .= '<tr>';
if ($core_tools->test_service('join_res_case_in_process', 'cases', false) == 1) {
    $frm_str .= '<td colspan="3"> <input type="button" class="button" name="search_case" '
            . 'id="search_case" value="';
    if ($case_properties['case_id'] <> '') {
        $frm_str .= _MODIFY_CASE;
    } else {
        $frm_str .= _JOIN_CASE;
    }
    $frm_str .= '" onclick="window.open(\'' . $_SESSION['config']['businessappurl']
            . 'index.php?display=true&module=cases&page=search_adv_for_cases'
            . '&searched_item=res_id_in_process&searched_value=' . $_SESSION['doc_id']
            . '\',\'\', \'scrollbars=yes,menubar=no,toolbar=no,resizable=yes,'
            . 'status=no,width=1020,height=710\');"/>'
            . ' <span id="unlink_case">';

    if($case_properties['case_id'] <> ''){
        $frm_str .= ' <input type="button" class="button" name="btn_unlink_case" '
        . 'id="btn_unlink_case" value="'._UNLINK_CASE.'"'
        . 'onclick="if(confirm(\''.addslashes(_UNLINK_CASE).'?\')){unlink_case(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=cases&page=unlink_case\','.$case_properties['case_id'].','. $res_id .');}"/>';
    }
    $frm_str .= '</span>';
    $frm_str .= '</td>';
}
$frm_str .= '</tr>';
$frm_str .= '</table>';
$frm_str .= '</form>';

echo $frm_str;