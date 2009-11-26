<?php
/*
*
*    Copyright 2008,2009 Maarch
*
*  This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief  Page displayed when reconnecting after an absence
*
* @file
* @author Claire Figueras <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup basket
*/

if ($_POST['value'] == "submit")
{
	$db = new dbquery();
	$db->connect();
	$db2 = new dbquery();
	$db2->connect();
	require_once('modules'.DIRECTORY_SEPARATOR.'basket'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_modules_tools.php');

	$bask = new basket();
	$bask->cancel_abs($_SESSION['user']['UserId']);

	$_SESSION['abs_user_status'] = false;
	if($_SESSION['history']['userabs'] == "true")
	{
		require_once("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_history.php");
		$history = new history();
		$history->connect();
		$history->query("select firstname, lastname from ".$_SESSION['tablename']['users']." where user_id = '".$this_user."'");
		$res = $history->fetch_object();
		$history->add($_SESSION['tablename']['users'],$this_user,"RET",$res->firstname." ".$res->lastname.' '._BACK_FROM_VACATION, $_SESSION['config']['databasetype']);
	}
	?>
		 <script language="javascript"> window.location.href="<?php echo $_SESSION['config']['businessappurl'];?>index.php";</script>
	<?php
	exit();
}
?>
<h1 ><img src="<?php echo $_SESSION['config']['businessappurl'];?>static.php?filename=picto_help_b.gif"  align="middle" /><?php echo _MISSING_ADVERT_TITLE; ?></h1>
<div id="inner_content" class="clearfix">
<h2 class="tit" align="center"><?php echo_MISSING_ADVERT_01; ?></h2>
<p align="center"><?php echo _MISSING_ADVERT_02; ?> </p>
<p align="center"><?php echo _MISSING_CHOOSE; ?></p>

<form name="redirect_form" method="post" action="<?php echo $_SESSION['config']['businessappurl'];?>index.php?page=advert_missing&module=basket">
	<p align="center">
    <input name="value" type="hidden" value="submit">
    <input name="cancel" type="submit"  value="<?php echo _CONTINUE; ?>" align="middle" class="button" />
    <input name="cancel" type="button" value="<?php echo _CANCEL;?>" onclick="window.location.href='<?php echo $_SESSION['config']['businessappurl'];?>logout.php?coreurl=<?php echo $_SESSION['config']['coreurl'];?>';" align="middle" class="button" />
    </p>
</form>
</div>
