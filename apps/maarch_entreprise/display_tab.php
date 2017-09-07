<?php

if(isset($_REQUEST["titleTab"])){
    $titleTab = $_REQUEST["titleTab"];
    $res_id   = $_REQUEST["resId"];
    $coll_id  = $_REQUEST["collId"];
    $script   = $_REQUEST["pathScriptTab"];
} else {
    echo 'NO TAB FOUND!';
    exit();
}
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_request.php';

$core_tools = new core_tools();
$core_tools->load_lang();
$core_tools->load_html();
$core_tools->load_header('', true, false);
$core_tools->load_header();
$core_tools->load_js();
    
$frm_str .= '<div class="desc" id="notes_div">';
$frm_str .= '<div class="ref-unit block" style="margin-top:-2px;">';
if(!empty($titleTab)){
    $frm_str .= '<center><h2 onmouseover="this.style.cursor=\'pointer\';">' . urldecode($titleTab) . '</h2></center>';
}
$frm_str .= '<div id="load_tab" title="loading..." style="text-align:center;"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></div>';
$frm_str .= '<iframe src="' . $script . '" '
            . 'name="iframe_tab" id="iframe_tab" width="100%" height="590px" align="center" '
            . 'scrolling="auto" frameborder="0" onload="document.getElementById(\'load_tab\').style.display=\'none\';"></iframe>';

echo $frm_str;

