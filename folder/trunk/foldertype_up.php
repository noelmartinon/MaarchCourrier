<?php 
session_name('PeopleBox');    
session_start(); 

$admin = new core_tools();

$admin->test_admin('admin_foldertypes', 'folder');
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
$page_path = $_SESSION['config']['businessappurl'].'index.php?page=foldertype_up&module=folder';
$page_label = _MODIFICATION;
$page_id = "foldertype_up";
$admin->manage_location_bar($page_path, $page_label, $page_id, $init, $level);
/***********************************************************/
//require_once($_SESSION['pathtocoreclass']."class_db.php");
require_once($_SESSION['pathtomodules']."folder".$_SESSION['slash_env']."class".$_SESSION['slash_env']."class_admin_foldertypes.php");

$func = new functions();

if(isset($_GET['id']))
{
	$id = addslashes($func->wash($_GET['id'], "alphanum", _THE_ID));
}
else
{
	$id = "";
}

			
$ft = new foldertype();

$ft->formfoldertype("up",$id);
?>