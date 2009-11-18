<?php
/**
* File : index.php
*
* Maarch index
*
* @package  Maarch PeopleBox 1.0
* @version 2.1
* @since 10/2005
* @license GPL
* @author  Laurent Giovannoni <dev@maarch.org>
* @author  Claire Figueras  <dev@maarch.org>
* @author  Lo�c Vinet <dev@maarch.org>
*/
//session_name('PeopleBox');
//session_start();

include_once('init.php');
//var_dump(get_include_path());
if(!isset($_SESSION['user']['UserId']))
{
	if(trim($_SERVER['argv'][0]) <> "")
	{
		header("location: reopen.php?".$_SERVER['argv'][0]);
	}
	else
	{
		header("location: reopen.php");
	}
	exit;
}
if(isset($_GET['show']))
{
	$show = $_GET['show'];
}
else
{
	$show = "true";
}
require_once($_SESSION['pathtocoreclass']."class_functions.php");
require_once($_SESSION['pathtocoreclass']."class_db.php");
require_once($_SESSION['pathtocoreclass']."class_core_tools.php");
$core_tools = new core_tools();
//$core_tools->test_user();
$core_tools->start_page_stat();
$core_tools->configPosition();
//here we loading the lang vars
$core_tools->load_lang();
//here we loading the html
$core_tools->load_html();
//here we building the header
$core_tools->load_header();
$time = $core_tools->get_session_time_expire();

?>
<body onLoad="HideMenu('menunav');session_expirate(<?php  echo $time;?>, '<?php  echo $_SESSION['config']['coreurl'];?>');">
<!--<script type='text/javascript'
        src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'></script>-->
	<div id="header">
        <div id="nav">
            <div id="menu" onMouseOver="ShowHideMenu('menunav','on');" onMouseOut="ShowHideMenu('menunav','off');" class="off">
                <p>
                	<img src="<?php  echo $_SESSION['config']['img'];?>/but_menu.gif" alt="<?php  echo _MENU;?>" />
                </p>
                <div id="menunav" style="display: none;">
                <?php
                echo '<div class="header_menu"><div class="user_name_menu">'.$_SESSION['user']['FirstName'].' '.$_SESSION['user']['LastName'].'</div></div>';
				echo '<div class="header_menu_blank">&nbsp;</div>';?>
                <ul  >
                    <?php
                    //Username for menu

                    //here we building the maarch menu
                    $core_tools->build_menu($_SESSION['menu']);

                   ?>
                </ul>
               		 <?php
                	echo '<div class="header_menu_blank">&nbsp;</div>';
                   	echo '<div class="footer_menu"><a style="color:white;" href="'.$_SESSION['config']['businessappurl'].'index.php?page=maarch_credits"">';
                   	echo ''._MAARCH_CREDITS.'</a></div>';?>
                </div>
        	</div>
			<div><p id="ariane"><?php  //$core_tools->where_am_i();
			?></p></div>
			<p id="gauchemenu"><img src="img/bando_tete_gche.gif" alt=""></p>
			<p id="logo"><a href="index.php"><img src="<?php  echo $_SESSION['config']['img'];?>/bando_tete_dte.gif" alt="<?php  echo _LOGO_ALT;?>" /></a></p>
       </div>
		<!--
		<dl class="protohud" id="protohudInde">
			<dt class="trig">Tab 1</dt>
			<dd class="targ">
				<p>Panel 1</p>
			</dd>
			<dt class="trig">Tab 2</dt>
			<dd class="targ">
				<p>Panel 2</p>
			</dd>
		</dl>-->
	<div id="container">
        <div id="content">
            <div class="error" id="main_error">
				<?php  echo $_SESSION['error'];?>
            </div>
			<div class="info" id="main_info">
				<?php  echo $_SESSION['info'];?>
            </div>
            <?php
            if($core_tools->is_module_loaded("basket") && $_SESSION['abs_user_status'] ==true)
			{
				include($_SESSION['pathtomodules'].'basket'.DIRECTORY_SEPARATOR."advert_missing.php");
			}
			else
			{
          	  $core_tools->insert_page();
          	}
            ?>
        </div>
        <p id="footer">
			<?php
            $core_tools->load_footer();
            ?>
        </p>
        <?php
        $_SESSION['error'] = "";
		$_SESSION['info'] = "";
        $core_tools->view_debug();
        ?>
	</div>
</div>
</body>
</html>
