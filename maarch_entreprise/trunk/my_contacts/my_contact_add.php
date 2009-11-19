<?php
/**
* File : my_contact_add.php
*
* Add contact form
*
* @package Maarch LetterBox 2.3
* @version 2.0
* @since 10/2007
* @license GPL
* @author  Claire Figueras  <dev@maarch.org>
*/
include('core/init.php');


$core_tools2 = new core_tools();
//here we loading the lang vars
$core_tools2->load_lang();
$core_tools2->test_service('my_contacts', 'apps');
/****************Management of the location bar  ************/
$init = false;
if($_REQUEST['reinit'] == "true")
{
	$init = true;
}
$level = "";
if($_REQUEST['level'] == 2 || $_REQUEST['level'] == 3 || $_REQUEST['level'] == 4 || $_REQUEST['level'] == 1)
{
	$level = $_REQUEST['level'];
}
$page_path = $_SESSION['config']['businessappurl'].'index.php?page=my_contact_add&dir=my_contacts';
$page_label = _ADDITION;
$page_id = "my_contact_add";
$core_tools2->manage_location_bar($page_path, $page_label, $page_id, $init, $level);
/***********************************************************/

require_once($_SESSION['config']['businessapppath']."class".DIRECTORY_SEPARATOR."class_contacts.php");

$contact = new contacts();
$contact->formcontact("add", '', false);
?>
