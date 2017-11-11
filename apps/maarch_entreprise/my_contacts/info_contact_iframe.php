<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   info_contact_iframe
* @author  dev <dev@maarch.org>
* @ingroup apps
*/

$core_tools2 = new core_tools();
$core_tools2->test_user();
$core_tools2->load_lang();
$core_tools2->load_html();
$core_tools2->load_header('', true, false);

require_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_request.php';
$request = new request();
$db = new Database();

echo '<div class="error" id="main_error">';
functions::xecho($_SESSION['error']);
echo '</div>';

echo '<div class="info" id="main_info">';
functions::xecho($_SESSION['info']);
echo '</div>';

$_SESSION['error'] = '';
$_SESSION['info'] = '';

if (isset($_GET['contactid']) && $_GET['contactid'] <> '') {
    $_SESSION['contact']['current_contact_id'] = $id;
} else if ($_SESSION['contact']['current_contact_id'] <> '') {
    $_GET['contactid'] = $_SESSION['contact']['current_contact_id'];
}

if (isset($_GET['fromAttachmentContact']) && $_GET['fromAttachmentContact'] == "Y") {
    $_SESSION['AttachmentContact'] = "1";
}
if (isset($_GET['addressid']) && $_GET['addressid'] <> '') {
    $_SESSION['contact']['current_address_id'] = $id;
} else if ($_SESSION['contact']['current_address_id'] <> '') {
    $_GET['addressid'] = $_SESSION['contact']['current_address_id'];
}

if (!isset($_GET['contactid']) || $_GET['contactid'] == '') {
    echo '<div class="error" id="main_error">';
    echo _YOU_MUST_SELECT_CONTACT;
    echo '</div>';
    exit;
}
if (!is_numeric($_GET['contactid'])) {
    $_REQUEST['id'] = $_GET['contactid'];
    $from_iframe = true;
    include_once 'apps/' . $_SESSION['config']['app_id'] . '/user_info.php';
    exit;
}

$query = "SELECT * FROM ".$_SESSION['tablename']['contacts_v2']." WHERE contact_id = ?";
$stmt = $db->query($query, array($_GET['contactid']));
$line = $stmt->fetchObject();

$_SESSION['m_admin']['contact'] = array();
$_SESSION['m_admin']['contact']['ID']                  = $line->contact_id;
$_SESSION['m_admin']['contact']['TITLE']               = $request->show_string($line->title);
$_SESSION['m_admin']['contact']['LASTNAME']            = $request->show_string($line->lastname);
$_SESSION['m_admin']['contact']['FIRSTNAME']           = $request->show_string($line->firstname);
$_SESSION['m_admin']['contact']['SOCIETY']             = $request->show_string($line->society);
$_SESSION['m_admin']['contact']['SOCIETY_SHORT']       = $request->show_string($line->society_short);
$_SESSION['m_admin']['contact']['FUNCTION']            = $request->show_string($line->function);
$_SESSION['m_admin']['contact']['OTHER_DATA']          = $request->show_string($line->other_data);
$_SESSION['m_admin']['contact']['IS_CORPORATE_PERSON'] = $request->show_string($line->is_corporate_person);
$_SESSION['m_admin']['contact']['CONTACT_TYPE']        = $line->contact_type;
$_SESSION['m_admin']['contact']['OWNER']               = $line->user_id;

if (isset($_GET['mode']) && $_GET['mode'] <> '') {
    $mode = $_GET['mode'];
} else {
    $mode = '';
}

if ($core_tools2->test_admin('update_contacts', 'apps', false) && $mode <> "view") {
    $_SESSION['contact']['current_contact_id'] = $_GET['contactid'];
    $_SESSION['contact']['current_address_id'] = $_GET['addressid'];
    $from_iframe = true;
    if (isset($_REQUEST['popup'])) {
        $_SESSION['info_contact_popup'] = "true";
    }

    if (isset($_GET['seeAllAddresses'])) {
        $core_tools2->load_js();
        unset($_SESSION['fromContactTree']);
        include_once 'apps/' . $_SESSION['config']['app_id'] . '/my_contacts/my_contact_up.php';
    } else {
        $_GET['id'] = $_GET['addressid'];
        include_once 'apps/' . $_SESSION['config']['app_id'] . '/my_contacts/update_address_iframe.php';
    }

} else {
    include_once "apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_contacts_v2.php";
    include_once "apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_business_app_tools.php";
    $contact = new contacts_v2();

    $query = "SELECT * FROM ".$_SESSION['tablename']['contact_addresses']." WHERE id = ?";
    $stmt = $db->query($query, array($_GET['addressid']));
    $line = $stmt->fetchObject();

    $_SESSION['m_admin']['address'] = array();
    $_SESSION['m_admin']['address']['ID']                 = $line->id;
    $_SESSION['m_admin']['address']['CONTACT_ID']         = $line->contact_id;
    $_SESSION['m_admin']['address']['TITLE']              = $request->show_string($line->title);
    $_SESSION['m_admin']['address']['LASTNAME']           = $request->show_string($line->lastname);
    $_SESSION['m_admin']['address']['FIRSTNAME']          = $request->show_string($line->firstname);
    $_SESSION['m_admin']['address']['FUNCTION']           = $request->show_string($line->function);
    $_SESSION['m_admin']['address']['OTHER_DATA']         = $request->show_string($line->other_data);
    $_SESSION['m_admin']['address']['OWNER']              = $line->user_id;
    $_SESSION['m_admin']['address']['DEPARTEMENT']        = $request->show_string($line->departement);
    $_SESSION['m_admin']['address']['CONTACT_PURPOSE_ID'] = $line->contact_purpose_id;
    $_SESSION['m_admin']['address']['OCCUPANCY']          = $request->show_string($line->occupancy);
    $_SESSION['m_admin']['address']['ADD_NUM']            = $request->show_string($line->address_num);
    $_SESSION['m_admin']['address']['ADD_STREET']         = $request->show_string($line->address_street);
    $_SESSION['m_admin']['address']['ADD_COMP']           = $request->show_string($line->address_complement);
    $_SESSION['m_admin']['address']['ADD_TOWN']           = $request->show_string($line->address_town);
    $_SESSION['m_admin']['address']['ADD_CP']             = $request->show_string($line->address_postal_code);
    $_SESSION['m_admin']['address']['ADD_COUNTRY']        = $request->show_string($line->address_country);
    $_SESSION['m_admin']['address']['PHONE']              = $request->show_string($line->phone);
    $_SESSION['m_admin']['address']['MAIL']               = $request->show_string($line->email);
    $_SESSION['m_admin']['address']['WEBSITE']            = $request->show_string($line->website);
    $_SESSION['m_admin']['address']['IS_PRIVATE']         = $request->show_string($line->is_private);
    $_SESSION['m_admin']['address']['SALUTATION_HEADER']  = $request->show_string($line->salutation_header);
    $_SESSION['m_admin']['address']['SALUTATION_FOOTER']  = $request->show_string($line->salutation_footer);

    $core_tools2->load_js();
	?>
	    <div id="inner_content" class="clearfix" align="center" style="padding:0px;width:100% !important;">
	    	<div class="block">
	<?php
		$contact->get_contact_form();
		$contact->get_address_form();
	?>		
			</div>
		</div>
<?php
}

?>
	<script type="text/javascript">
		resize_frame_contact('contact');
	</script>
<?php


if (isset($_SESSION['AttachmentContact']) && $_SESSION['AttachmentContact'] =="1" && $_GET['created'] <> '' && isset($_GET['created'])) {
	$infoContactDiv = "info_contact_div_attach";
	?>
	<script>
		simpleAjax("<?php echo $_SESSION['config']['businessappurl'].'index.php?display=true&page=unsetAttachmentContact';?>");
	</script>
	<?php
} else {
	$infoContactDiv = "show_tab";
}

if ($_GET['created'] == "open") {
	?>
		<script type="text/javascript">
			set_new_contact_address("<?php echo $_SESSION['config']['businessappurl'] . 'index.php?display=false&dir=my_contacts&page=get_last_contact_address&mode=up';?>", "<?php functions::xecho($infoContactDiv );?>", "false");
		</script>
	<?php
} else if(isset($_GET['created']) && $_GET['created'] == 'add'){
?>
	<script type="text/javascript">
		set_new_contact_address("<?php echo $_SESSION['config']['businessappurl'] . 'index.php?display=false&dir=my_contacts&page=get_last_contact_address';?>", "<?php functions::xecho($infoContactDiv );?>", "true");
	</script>
<?php
} else if(isset($_GET['created']) && $_GET['created'] <> ''){
?>
	<script type="text/javascript">
		set_new_contact_address("<?php echo $_SESSION['config']['businessappurl'] . 'index.php?display=false&dir=my_contacts&page=get_last_contact_address&mode=up';?>", "<?php functions::xecho($infoContactDiv );?>", "true");
	</script>
<?php
}
