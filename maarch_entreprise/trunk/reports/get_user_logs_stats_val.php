<?php
include('core/init.php');

require_once("core/class/class_functions.php");
require_once("core/class/class_db.php");
require_once("core/class/class_core_tools.php");
require_once("core/class/class_request.php");
require_once($_SESSION['config']['businessapppath']."class".DIRECTORY_SEPARATOR."class_list_show.php");
$core_tools = new core_tools();
$core_tools->load_lang();
$db = new dbquery();
$db->connect();
$req = new request();
$list = new list_show();

if(isset($_REQUEST['user']) && $_REQUEST['user'] != '')
{
	$db->query('select lastname, firstname from '.$_SESSION['tablename']['users']." where user_id ='".$db->protect_string_db($_REQUEST['user'])."'");
	if($db->nb_result() == 0)
	{
		?>
		<div class="error"><?php echo _USER.' '._UNKNOWN;?></div>
		<?php
	}

	$res = $db->fetch_object();
	$user_name = $res->firstname.' '.$res->lastname;

	$select[$_SESSION['tablename']['history']] = array();
	array_push($select[$_SESSION['tablename']['history']],'id','event_type','event_date' );
	$where = " (".$_SESSION['tablename']['history'].".event_type = 'LOGIN' or ".$_SESSION['tablename']['history'].".event_type = 'LOGOUT') AND ".$_SESSION['tablename']['history'].".user_id = '".$_REQUEST['user']."' ";
	$req = new request();
	$tab = $req->select($select, $where, " ORDER BY ".$_SESSION['tablename']['history'].".event_date DESC ", $_SESSION['config']['databasetype'], $limit="500",false);

	if (count($tab) > 0)
	{
		for ($i=0;$i<count($tab);$i++)
		{
			for ($j=0;$j<count($tab[$i]);$j++)
			{
				foreach(array_keys($tab[$i][$j]) as $value)
				{
					if($tab[$i][$j][$value] == "id")
					{
						$tab[$i][$j]["label"]=_ID;
						$tab[$i][$j]["size"]="20";
						$tab[$i][$j]["label_align"]="left";
						$tab[$i][$j]["align"]="left";
						$tab[$i][$j]["valign"]="bottom";
						$tab[$i][$j]["show"]=true;
						//$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
						$tab[$i][$j]["value"]=$tab[$i][$j]['value'];
					}
					if($tab[$i][$j][$value]=="event_type"){
						$tab[$i][$j]["label"]=_ACTION;
						$tab[$i][$j]["size"]="30";
						$tab[$i][$j]["label_align"]="left";
						$tab[$i][$j]["align"]="center";
						$tab[$i][$j]["valign"]="bottom";
						$tab[$i][$j]["show"]=true;
						//$tab[$i][$j]["value_export"] = $core_tools->is_var_in_history_keywords_tab($tab[$i][$j]['value']);
						$tab[$i][$j]["value"] = $core_tools->is_var_in_history_keywords_tab($tab[$i][$j]['value']);
					}

					if($tab[$i][$j][$value]=="event_date"){
						$tab[$i][$j]["label"]=_DATE;
						$tab[$i][$j]["size"]="30";
						$tab[$i][$j]["label_align"]="left";
						$tab[$i][$j]["align"]="center";
						$tab[$i][$j]["valign"]="bottom";
						$tab[$i][$j]["show"]=true;
						//$tab[$i][$j]["value_export"] = $funct -> dateformat($tab[$i][$j]['value']);
						$tab[$i][$j]["value"] = $db -> dateformat($tab[$i][$j]['value']);
					}
				}
			}
		}
		$title = _TITLE_STATS_USER_LOG.' :  '.$user_name.' ('.$_REQUEST['user'].')';
		?><div align="center"><?php $list->list_simple($tab, $i, $title, 'folder_id', 'istats_result', false, "", 'listing spec', '', 400, 500, '', false);  ?></div>
		<?php
	}
	else
	{
		$title = _TITLE_STATS_USER_LOG.' :  '.$user_name.' ('.$_REQUEST['user'].')';
		echo '<h3>'.$title.'</h3>';
		?><div align="center"><?php echo _NO_RESULTS;?></div>
		<?php
	}
}
else
{
?>
	<div class="error"><?php echo _USER.' '._IS_EMPTY;?></div>
<?php
	exit();
}
