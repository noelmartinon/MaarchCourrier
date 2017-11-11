<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   create_contact_iframe
* @author  dev <dev@maarch.org>
* @ingroup apps
*/

$core_tools2 = new core_tools();
$core_tools2->load_lang();
$core_tools2->test_admin('my_contacts', 'apps');
$core_tools2->load_html();
$core_tools2->load_header('', true, false);

require_once "apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_contacts_v2.php";

$contact = new contacts_v2();

echo '<div class="error" id="main_error">';
functions::xecho($_SESSION['error']);
echo '</div>';

echo '<div class="info" id="main_info">';
functions::xecho($_SESSION['info']);
echo '</div>';

if (!empty($_SESSION['error'])) {
    echo "<script>var main_error = document.getElementById('main_error');if (main_error != null) {main_error.style.display = 'table-cell';}</script>";
}

if (!empty($_SESSION['info'])) {
    echo "<script>var main_info = document.getElementById('main_info');if (main_info != null) {main_info.style.display = 'table-cell';}</script>";
}

$_SESSION['error'] = '';
$_SESSION['info'] = '';

if ((!isset($_GET['created']) || $_GET['created'] == '') && $_SESSION['error'] <> '') {
    $_SESSION['m_admin']['contact'] = '';
}

if (isset($_GET['fromAttachmentContact']) && $_GET['fromAttachmentContact'] == "Y") {
    $_SESSION['AttachmentContact'] = "1";
}

if ($_SESSION['AttachmentContact'] != "1") {
    $_SESSION['transmissionInput']= "";
}

if (isset($_GET['transmissionInput'])) {
    $_SESSION['transmissionInput'] = $_GET['transmissionInput'];
}

$core_tools2->load_js();
if (isset($_GET['created']) && $_GET['created'] <> '') {
    $contact->chooseContact(true);
    echo "<br/><br/><br/>";
} else {
    /*$contact->chooseContact(false);
    echo "<br/><br/><br/>";*/
}
$contact->formcontact("add", "", false, true);

$load_js = '<script type="text/javascript">';
$load_js .= "resize_frame_contact('contact');";
$load_js .= '</script>';

echo $load_js;

if ($_SESSION['AttachmentContact'] == "1") {
    $createContactDiv = "create_contact_div_attach";
} else {
    $createContactDiv = "show_tab";
}

if (isset($_GET['created']) && $_GET['created'] <> '') { ?>
    <script type="text/javascript">
        set_new_contact_address("<?php echo $_SESSION['config']['businessappurl'] . 'index.php?display=false&dir=my_contacts&page=get_last_contact_address';?>", "<?php functions::xecho($createContactDiv);?>", "true", "<?php echo $_SESSION['transmissionInput'];?>");
    </script>
<?php
}
?>
