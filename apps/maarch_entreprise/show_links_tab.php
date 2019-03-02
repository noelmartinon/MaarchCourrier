<?php

require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_request.php';
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php';
require_once 'core/class/LinkController.php';

if (!empty($_REQUEST['id'])) {
    $_SESSION['doc_id'] = $_REQUEST['id'];
}
$security = new security();
$right = $security->test_right_doc($_SESSION['collection_id_choice'], $_SESSION['doc_id']);

if (!$right) {
    exit(_NO_RIGHT_TXT);
}

$core_tools = new core_tools();
$core_tools->load_lang();
$core_tools->load_html();
$core_tools->load_header('', true, false);
$core_tools->load_js();

$Class_LinkController = new LinkController();

$frm_str .= '<div id="loadLinks">';
$nbLinkDesc = $Class_LinkController->nbDirectLink(
        $_SESSION['doc_id'],
    $_SESSION['collection_id_choice'],
    'desc'
);
if ($nbLinkDesc > 0) {
    $frm_str .= '<i class="fa fa-long-arrow-alt-right fa-2x"></i>';
    $frm_str .= $Class_LinkController->formatMap(
            $Class_LinkController->getMap(
                    $_SESSION['doc_id'],
                $_SESSION['collection_id_choice'],
                'desc'
            ),
        'desc'
    );
    $frm_str .= '<br />';
}
$nbLinkAsc = $Class_LinkController->nbDirectLink(
        $_SESSION['doc_id'],
    $_SESSION['collection_id_choice'],
    'asc'
);
if ($nbLinkAsc > 0) {
    $frm_str .= '<i class="fa fa-long-arrow-alt-left fa-2x"></i>';
    $frm_str .= $Class_LinkController->formatMap(
            $Class_LinkController->getMap(
                    $_SESSION['doc_id'],
                $_SESSION['collection_id_choice'],
                'asc'
            ),
        'asc'
    );
    $frm_str .= '<br />';
}
$frm_str .= '</div>';
if ($core_tools->test_service('add_links', 'apps', false)) {
    include 'apps/'.$_SESSION['config']['app_id'].'/add_links.php';
    $frm_str .= $Links;
}

echo $frm_str;
