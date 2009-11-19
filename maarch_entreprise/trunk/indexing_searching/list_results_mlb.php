<?php
/*
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
* @brief  Search result list
*
* @file list_results_mlb.php
* @author Claire Figueras <dev@maarch.org>
* @author Loïc Vinet <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup indexing_searching_mlb
*/
include('core/init.php');

require_once("core/class/class_functions.php");
require_once("core/class/class_db.php");
require_once("core/class/class_request.php");
require_once("core/class/class_security.php");
require_once("core/class/class_core_tools.php");
require_once("core/class/class_manage_status.php");
require_once($_SESSION['config']['businessapppath']."class".DIRECTORY_SEPARATOR.'class_list_show.php');
require_once($_SESSION['config']['businessapppath']."class".DIRECTORY_SEPARATOR.'class_contacts.php');
require_once("core/class/class_manage_status.php");

include_once($_SESSION['config']['businessapppath'].'definition_mail_categories.php');

$status_obj = new manage_status();
$core_tools = new core_tools();
$core_tools->test_user();
$core_tools->load_lang();
$sec = new security();
$status_obj = new manage_status();
$contact = new contacts();

$mode = 'normal';
if(isset($_REQUEST['mode'])&& !empty($_REQUEST['mode']))
{
	$mode = $core_tools->wash($_REQUEST['mode'], "alphanum", _MODE);
}
if($mode == 'normal')
{
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
$page_path = $_SESSION['config']['businessappurl'].'index.php?page=list_results_mlb&dir=indexing_searching&order='.$order.'&order_field='.$field.'&start='.$start;
$page_label = _RESULTS;
$page_id = "search_adv_result_mlb";
$core_tools->manage_location_bar($page_path, $page_label, $page_id, $init, $level);
/***********************************************************/
}
elseif($mode == 'popup' || $mode == 'frame')
{
	$core_tools->load_html();
	$core_tools->load_header();
	$time = $core_tools->get_session_time_expire();
	?><body>
	<div id="container">

            <div class="error" id="main_error">
				<?php  echo $_SESSION['error'];?>
            </div>
			<div class="info" id="main_info">
				<?php  echo $_SESSION['info'];?>
            </div><?php
}
//$_SESSION['collection_id_choice'] = $_SESSION['searching']['coll_id'];
$_SESSION['collection_id_choice'] = 'letterbox_coll';
$view = $sec->retrieve_view_from_coll_id($_SESSION['collection_id_choice'] );
$select = array();
//$select[$_SESSION['searching']['coll_view']]= array();
$select[$view]= array();
$where_request = $_SESSION['searching']['where_request'];
array_push($select[$view], "res_id", "status", "subject", "dest_user", "type_label", "creation_date", "destination", "category_id, exp_user_id", "category_id as category_img" );

if($core_tools->is_module_loaded("cases") == true)
{
	array_push($select[$view], "case_id", "case_label", "case_description");
}


$status = $status_obj->get_not_searchable_status();
$status_str = '';
for($i=0; $i<count($status);$i++)
{
	$status_str .=	"'".$status[$i]['ID']."',";
}
$status_str = preg_replace('/,$/', '', $status_str);
$where_request.= "  status not in (".$status_str.") ";
$where_clause = $sec->get_where_clause_from_coll_id($_SESSION['collection_id_choice']);
if(!empty($where_request))
{
	if($_SESSION['searching']['where_clause_bis'] <> "")
	{
		$where_clause = "((".$where_clause.") or (".$_SESSION['searching']['where_clause_bis']."))";
	}
	$where_request = '('.$where_request.') and ('.$where_clause.')';
}
else
{
	if($_SESSION['searching']['where_clause_bis'] <> "")
	{
		$where_clause = "((".$where_clause.") or (".$_SESSION['searching']['where_clause_bis']."))";
	}
	$where_request = $where_clause;
}
$where_request = str_replace("()", "(1=-1)", $where_request);
$where_request = str_replace("and ()", "", $where_request);
$list=new list_show();
$order = '';
if(isset($_REQUEST['order']) && !empty($_REQUEST['order']))
{
	$order = trim($_REQUEST['order']);
}
$field = '';
if(isset($_REQUEST['order_field']) && !empty($_REQUEST['order_field']))
{
	$field = trim($_REQUEST['order_field']);
}

$orderstr = $list->define_order($order, $field);

if(($_REQUEST['template']== 'group_case')&& ($core_tools->is_module_loaded('cases')))
{
	unset($select);
	$select = array();
	$select[$_SESSION['tablename']['cases']]= array();
	$select[$view]= array();
	array_push($select[$_SESSION['tablename']['cases']], "case_id", "case_label", "case_description", "case_typist", "case_creation_date");
	$where = " ".$_SESSION['tablename']['cases'].".case_id = ".$view.".case_id  and ";
	$request = new request();
	$tab=$request->select($select,$where.$where_request,$orderstr,$_SESSION['config']['databasetype'], "default", false, "", "", "", true, false, true);

}
else
{
	$request = new request();
	$tab=$request->select($select,$where_request,$orderstr,$_SESSION['config']['databasetype']);
	//$request->show();
}
//$request->show();
$_SESSION['error_page'] = '';


//Manage of template list
	//###################

	//Defines template allowed for this list
	$template_list=array();
	array_push($template_list, array( "name"=>"search_adv", "img"=>"extend_list.gif", "label"=> _ACCESS_LIST_EXTEND));
	if($core_tools->is_module_loaded('cases'))
		array_push($template_list, array( "name"=>"group_case", "img"=>"box.gif", "label"=> _ACCESS_LIST_CASE));
	if(!$_REQUEST['template'])
		$template_to_use = $template_list[0]["name"];
	if(isset($_REQUEST['template']) && empty($_REQUEST['template']))
		$template_to_use = '';
	if($_REQUEST['template'])
		$template_to_use = $_REQUEST['template'];

	//For status icon
	$extension_icon = '';
	if($template_to_use <> '')
		$extension_icon = "_big";
	//###################



	//#########################

		//build the tab with right format for list_doc function
		if (count($tab) > 0)
		{

			//Specific View for group_case_template, we don' need to load the standard list_result_mlb
			//#########################
			if(($_REQUEST['template']== 'group_case')&& ($core_tools->is_module_loaded('cases')))
			{
				include("modules/cases".DIRECTORY_SEPARATOR.'mlb_list_group_case_addon.php');
			}
			else
			{
			for ($i=0;$i<count($tab);$i++)
			{
				for ($j=0;$j<count($tab[$i]);$j++)
				{
					foreach(array_keys($tab[$i][$j]) as $value)
					{
						if($tab[$i][$j][$value]=='res_id')
						{
							$tab[$i][$j]['res_id']=$tab[$i][$j]['value'];
							$tab[$i][$j]["label"]=_GED_NUM;
							$tab[$i][$j]["size"]="4";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="center";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=true;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["order"]='res_id';
							$_SESSION['mlb_search_current_res_id'] = $tab[$i][$j]['value'];
						}
						if($tab[$i][$j][$value]=="type_label")
						{
							$tab[$i][$j]["label"]=_TYPE;
							$tab[$i][$j]['value'] = $request->show_string($tab[$i][$j]['value']);
							$tab[$i][$j]["size"]="15";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=true;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["order"]="type_label";
						}
						if($tab[$i][$j][$value]=="status")
						{
							$tab[$i][$j]["label"]=_STATUS;
							$res_status = $status_obj->get_status_data($tab[$i][$j]['value'],$extension_icon);
							$tab[$i][$j]['value'] = "<img src = '".$res_status['IMG_SRC']."' alt = '".$res_status['LABEL']."' title = '".$res_status['LABEL']."'>";
							$tab[$i][$j]["size"]="5";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=true;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["order"]="status";
						}
						if($tab[$i][$j][$value]=="subject")
						{
							$tab[$i][$j]["label"]=_SUBJECT;
							$tab[$i][$j]['value'] = $request->show_string($tab[$i][$j]['value']);
							$tab[$i][$j]["size"]="25";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=true;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["order"]="subject";
						}
						if($tab[$i][$j][$value]=="dest_user")
						{
							$tab[$i][$j]["label"]=_DEST_USER;
							$tab[$i][$j]["size"]="10";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=true;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["order"]="dest_user";
						}
						if($tab[$i][$j][$value]=="creation_date")
						{
							$tab[$i][$j]["label"]=_REG_DATE;
							$tab[$i][$j]["size"]="10";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=true;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["value"] = $request->format_date_db($tab[$i][$j]['value'], false);
							$tab[$i][$j]["order"]="creation_date";
						}
						if($tab[$i][$j][$value]=="destination")
						{
							$tab[$i][$j]["label"]=_ENTITY;
							$tab[$i][$j]['value'] = $request->show_string($tab[$i][$j]['value']);
							$tab[$i][$j]["size"]="10";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=false;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["order"]="destination";
						}
						if($tab[$i][$j][$value]=="category_id")
						{
							$_SESSION['mlb_search_current_category_id'] = $tab[$i][$j]['value'];
							$tab[$i][$j]["label"]=_CATEGORY;
							$tab[$i][$j]["size"]="10";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=true;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["value"] = $_SESSION['mail_categories'][$tab[$i][$j]['value']];
							$tab[$i][$j]["order"]="category_id";

						}
						if($tab[$i][$j][$value]=="category_img")
						{
							$tab[$i][$j]["label"]=_CATEGORY;
							$tab[$i][$j]["size"]="10";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=false;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$my_imgcat = get_img_cat($tab[$i][$j]['value'],$extension_icon);
							$tab[$i][$j]['value'] = "<img src = '".$my_imgcat."' alt = '' title = ''>";
							$tab[$i][$j]["value"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["order"]="category_id";
						}
						if($tab[$i][$j][$value]=="exp_user_id")
						{
							$tab[$i][$j]["label"]=_CONTACT;
							$tab[$i][$j]["size"]="10";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=false;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["value"] = $contact->get_contact_information($_SESSION['mlb_search_current_res_id'],$_SESSION['mlb_search_current_category_id'],$view);
							$tab[$i][$j]["order"]=false;
						}
						if($tab[$i][$j][$value]=="case_id" && $core_tools->is_module_loaded("cases") == true)
						{
							$tab[$i][$j]["label"]=_CASE_NUM;
							$tab[$i][$j]["size"]="10";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=true;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							$tab[$i][$j]["value"] = "<a href='".$_SESSION['config']['businessappurl']."index.php?page=details_cases&module=cases&id=".$tab[$i][$j]['value']."'>".$tab[$i][$j]['value']."</a>";
							$tab[$i][$j]["order"]="case_id";
						}
						if($tab[$i][$j][$value]=="case_label" && $core_tools->is_module_loaded("cases") == true)
						{
							$tab[$i][$j]["label"]=_CASE_LABEL;
							$tab[$i][$j]["size"]="10";
							$tab[$i][$j]["label_align"]="left";
							$tab[$i][$j]["align"]="left";
							$tab[$i][$j]["valign"]="bottom";
							$tab[$i][$j]["show"]=true;
							$tab[$i][$j]["value_export"] = $tab[$i][$j]['value'];
							//$tab[$i][$j]["value"] = $contact->get_contact_information($_SESSION['mlb_search_current_res_id'],$_SESSION['mlb_search_current_category_id'],$view);
							$tab[$i][$j]["order"]="case_id";
						}
					}
				}
			}



		}




		if($_GET['template'] == "group_case")
			$found_type = _FOUND_CASE;
		else
			$found_type = _FOUND_DOC;
?>

<h1><img src="<?php  echo $_SESSION['config']['businessappurl']."img/picto_search_b.gif";?>" alt="" /> <?php  echo _SEARCH_RESULTS." - ".count($tab)." ".$found_type;?></h1>
    <div id="inner_content">
	<?php if(!isset($_REQUEST['action_form']) || empty($_REQUEST['action_form']))
	{
		$bool_radio_form = false;
		$method = '';
		$action = '';
		$button_label = '';
	}
	else
	{
		$bool_radio_form = true;
		$method = 'get';
		$button_label = _VALIDATE;
		if(isset($_REQUEST['module'])&& !empty($_REQUEST['module']))
		{
			$action = $_SESSION['urltomodules'].$_REQUEST['module'].'/'.$_REQUEST['action_form'].'.php';
		}
		else
		{
			$action = $_SESSION['config']['businessappurl'].$_REQUEST['action_form'].'.php';
		}
	}
	if($mode == 'popup')
	{
		$show_close = true;
	}
	else
	{
		$show_close = false;
	}
	if(isset($_REQUEST['nodetails']))
	{
		$show_details = false;
	}
	else
	{
		$show_details = true;
	}

	if($mode == 'popup' || $mode == 'frame')
	{
		$export = false;
		$save_mode = false;
		$use_template = false;
		$special = true;
		$name = $_SESSION['config']['businessappurl'].'indexing_searching/list_results_mlb';
	}
	else
	{
		$export = true;
		$save_mode = true;
		$special = false;
		$name = 'list_results_mlb&dir=indexing_searching';
		$use_template = true;
	}
	$comp_link = '&mode='.$mode;

	$list->list_doc($tab,$i,'','res_id',$name,'res_id','details&dir=indexing_searching',true,$bool_radio_form,$method,$action,$button_label,$show_details,true,$special, $export,$show_close,false,true,false,'', '',false,'','','listing spec', $comp_link, false, false, array(), '', '{}', false, '', true, array(), $use_template, $template_list, $template_to_use );
	?></div><?php
}
else
{
	if($mode == 'normal')
	{
		$_SESSION['error_search'] = '<p class="error"><img src="'.$_SESSION['config']['businessappurl'].'img/noresult.gif" /><br />'._NO_RESULTS.'</p><br/><br/><div align="center"><strong><a href="'.$_SESSION['config']['businessappurl'].'index.php?page=search_adv&dir=indexing_searching&init_search">'._MAKE_NEW_SEARCH.'</a></strong></div>';
		?>
		<script language="javascript" type="text/javascript">window.top.location.href='<?php  echo $_SESSION['config']['businessappurl'].'index.php?page=search_adv_error&dir=indexing_searching';?>';</script>
		<?php
	}
	else
	{
		$_SESSION['error_search'] = '<p class="error"><img src="'.$_SESSION['config']['businessappurl'].'img/noresult.gif" /><br />'._NO_RESULTS.'</p><br/><br/><div align="center"><strong><a href="'.$_SESSION['config']['businessappurl'].'indexing_searching/search_adv.php?mode='.$mode.'&init_search">'._MAKE_NEW_SEARCH.'</a></strong></div>';
		?>
		<script language="javascript" type="text/javascript">window.top.location.href='<?php  echo $_SESSION['config']['businessappurl'].'indexing_searching/search_adv_error.php?mode='.$mode;?>';</script>
		<?php
	}
}
?>
	<script type="text/javascript">
	<!--
	var form_txt='<form name="frm_save_query" id="frm_save_query" action="#" method="post" class="forms addforms" onsubmit="send_request(this.id);" ><h2><?php echo _SAVE_QUERY_TITLE;?></h2><p><label for="query_name"><?php echo _QUERY_NAME;?></label><input type="text" name="query_name" id="query_name" value=""/></p><p class="buttons"><input type="submit" name="submit" id="submit" value="<?php echo _VALIDATE;?>" class="button"/> <input type="button" name="cancel" id="cancel" value="<?php echo _CANCEL;?>" class="button" onclick="destroyModal();"/></p></form>';

	function send_request(form_id)
	{
		var form = document.getElementById(form_id);
		var q_name = form.query_name.value;
		$('modal').innerHTML = '<img src="img/loading.gif" />';

		new Ajax.Request('<? echo $_SESSION['config']['businessappurl'];?>indexing_searching/manage_query.php',
	    {
	        method:'post',
	        parameters: {name: q_name,
						action : "creation"},
	        onSuccess: function(answer){
				eval("response = "+answer.responseText)
				if(response.status == 0)
				{
					$('modal').innerHTML ='<h2><?php echo _QUERY_SAVED;?></h2><br/><input type="button" name="close" value="<?php echo _CLOSE_WINDOW;?>" onclick="destroyModal();" class="button" />';
				}
				else if(response.status == 2)
				{
					$('modal').innerHTML = '<div class="error"><?php echo _SQL_ERROR;?></div>'+form_txt;
					form.query_name.value = this.name;
				}
				else if(response.status == 3)
				{
					$('modal').innerHTML = '<div class="error"><?php echo _QUERY_NAME.' '._IS_EMPTY;?></div>'+form_txt;
					form.query_name.value = this.name;
				}
				else
				{
					$('modal').innerHTML = '<div class="error"><?php echo _SERVER_ERROR;?></div>'+form_txt;
					form.query_name.value = this.name;
				}
            },
            onFailure: function(){
	            $('modal').innerHTML = '<div class="error"><?php echo _SERVER_ERROR;?></div>'+form_txt;
				form.query_name.value = this.name;
	           }
	    });
	}
	-->
	</script>
<?php if($save_mode)
{?>
	<input type="button" onclick="createModal(form_txt);" value="<?php echo _SAVE_QUERY;?>" class="button"/>
<?php
}
if($mode == 'popup' || $mode == 'frame')
{
 	echo '</div>';
 	echo '</body></html>';
}
