<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   update_address_iframe
* @author  dev <dev@maarch.org>
* @ingroup apps
*/

$core_tools2 = new core_tools();
$core_tools2->load_lang();
if (!$core_tools2->test_service('my_contacts', 'apps', false)) {
    if (!$core_tools2->test_service('update_contacts', 'apps', false)) {
        $core_tools2->test_service('my_contacts_menu', 'apps');
    }
}
$core_tools2->load_html();
$core_tools2->load_header('', true, false);

require_once "apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_contacts_v2.php";
require_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR
    . 'class_request.php';

$request = new request();
$contact = new contacts_v2();

echo '<div class="error" id="main_error">';
functions::xecho($_SESSION['error']);
echo '</div>';

echo '<div class="info" id="main_info">';
functions::xecho($_SESSION['info']);
echo '</div>';

$_SESSION['error'] = '';
$_SESSION['info'] = '';

$core_tools2->load_js();
$func = new functions();

if (isset($_GET['id'])) {
    $id = addslashes($func->wash($_GET['id'], "alphanum", _ADDRESS));

} else {
    $id = "";
}

if (isset($_GET['fromContactIframe'])) {
    $iframe_txt = "fromContactIframe";
    $_SESSION['contact']['current_address_id'] = $id;
} else {
    $iframe_txt = "iframe_add_up";
}

$load_js = '<script type="text/javascript">';
$load_js .= "resize_frame_contact('address');";
$load_js .= '</script>';

$contact->formaddress("up", $id, false, $iframe_txt);

echo $load_js;
?>
