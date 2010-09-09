<?php
/**
* File : log.php
*
* User identification
*
* @package  Maarch PeopleBox 1.0
* @version 2.1
* @since 10/2005
* @license GPL
* @author  Claire Figueras  <dev@maarch.org>
* @author  Laurent Giovannoni  <dev@maarch.org>
*/
$core_tools = new core_tools();
$core_tools->load_lang();
$func = new functions();

$_SESSION['error'] = "";
if(isset($_REQUEST['login']))
{
	$s_login = $func->wash($_REQUEST['login'],"no",_THE_ID,"yes");
}
else
{
	$s_login = '';
}
if(isset($_REQUEST['pass']))
{
	$s_pass =$func->wash($_REQUEST['pass'],"no",_PASSWORD_FOR_USER,"yes");
}
else
{
	$s_pass = '';
}
require("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_security.php");
require("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_request.php");
require("apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_business_app_tools.php");
$sec = new security();
$business_app_tools = new business_app_tools();

if(count($_SESSION['config']) <= 0)
{
	//echo 'config vide <br/>';
	//$_SESSION['slash_env'] = DIRECTORY_SEPARATOR;
	
	$path_tmp = explode(DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR,$_SERVER['SCRIPT_FILENAME']));
	$path_server = implode(DIRECTORY_SEPARATOR,array_slice($path_tmp,0,array_search('apps',$path_tmp))).DIRECTORY_SEPARATOR;

	$core_tools->build_core_config("core".DIRECTORY_SEPARATOR."xml".DIRECTORY_SEPARATOR."config.xml");
	
	$business_app_tools->build_business_app_config();
	$core_tools->load_modules_config($_SESSION['modules']);
	$core_tools->load_menu($_SESSION['modules']);
}

if(!empty($_SESSION['error']))
{
	header("location: ".$_SESSION['config']['businessappurl']."index.php?display=true&page=login&coreurl=".$_SESSION['config']['coreurl']);
	exit();
}
else
{
	if ($_SESSION['config']['ldap'] == "true" && $s_login <> "superadmin")
	{
		//Extraction de /root/config dans le fichier de conf
		$ldap_conf = new DomDocument();
		try
		{
			if(!@$ldap_conf->load("apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR."ldap".DIRECTORY_SEPARATOR."config_ldap.xml"))
			{
				throw new Exception("Impossible de charger le document : ".$_SESSION['config']['businessappurl']."ldap".DIRECTORY_SEPARATOR."config_ldap.xml");
			}
		}
		catch(Exception $e)
		{
			exit($e->getMessage());
		}

		$xp_ldap_conf = new domxpath($ldap_conf);

		foreach($xp_ldap_conf->query("/root/config/*") as $cf)
		{
			${$cf->nodeName} = $cf->nodeValue;
		}

		//On inclus la class LDAP qui correspond à l'annuaire
		if(!include("apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR."ldap".DIRECTORY_SEPARATOR."class_".$type_ldap.".php"))
		{
			exit("Impossible de charger class_".$type_ldap.".php\n");
		}

		//Try to create a new ldap instance
		try
		{
			$ad = new LDAP($domain,$login_admin,$pass,$ssl);
		}
		catch(Exception $con_failure)
		{
			echo $con_failure->getMessage();
			exit;
		}

		if($ad -> authenticate($s_login, $s_pass))
		{
			$db = new dbquery();
			$db->connect();


			if ($_SESSION['config']['databasetype'] == "POSTGRESQL")
				$query = "select * from ".$_SESSION['tablename']['users']." where user_id ilike '".$this->protect_string_db($s_login)."' ";
			else
				$query = "select * from ".$_SESSION['tablename']['users']." where user_id like '".$this->protect_string_db($s_login)."' ";



			$db->query($query);
			if($db->fetch_object())
			{
				$pass = md5($s_pass);
				$sec->login($s_login,$pass);
			}
			else
			{
				$_SESSION['error'] =  _NO_LOGIN_OR_PSW_BY_LDAP."...";
				header("location: ".$_SESSION['config']['businessappurl']."index.php?display=true&page=login&coreurl=".$_SESSION['config']['coreurl']);
				exit;
			}
		}
		else
		{
			$_SESSION['error'] =  _BAD_LOGIN_OR_PSW."...";
			header("location: ".$_SESSION['config']['businessappurl']."index.php?display=true&page=login&coreurl=".$_SESSION['config']['coreurl']);
			exit;
		}
	}
	else
	{
		$pass = md5($s_pass);
		$sec->login($s_login,$pass);
	}
}
?>
