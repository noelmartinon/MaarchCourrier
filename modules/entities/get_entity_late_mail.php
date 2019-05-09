<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   get_entity_vol
* @author  dev <dev@maarch.org>
* @ingroup entities
*/

require_once("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_request.php");
require_once("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_security.php");
require_once("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_manage_status.php");
require_once("apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_list_show.php");
require_once('modules'.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_graphics.php");
require_once('modules'.DIRECTORY_SEPARATOR.'entities'.DIRECTORY_SEPARATOR.'entities_tables.php');
require_once('modules'.DIRECTORY_SEPARATOR.'entities'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_users_entities_Abstract.php');

$_ENV['date_pattern'] = "/^[0-3][0-9]-[0-1][0-9]-[1-2][0-9][0-9][0-9]$/";


$graph = new graphics();
$req   = new request();
$db    = new Database();
$sec   = new security();

$entities_chosen = explode("#", $_POST['entities_chosen']);
if($_REQUEST['sub_entities'] == 'true'){
	$sub_entities = [];
	foreach($entities_chosen as $value){
		$sub_entities[] = users_entities_Abstract::getEntityChildren($value);
	}
	$sub_entities1 = "'";
	for( $i=0; $i< count($sub_entities ); $i++){
		$sub_entities1 .= implode("','",$sub_entities[$i]);
		$sub_entities1 .= "','";
	}
	$sub_entities1 = substr($sub_entities1, 0, -2);
}
$entities_chosen = "'" . join("','", $entities_chosen) . "'";
$status_chosen = '';
$where_status = '';
if (!empty($_POST['status_chosen'])) {
    $status_chosen = explode("#", $_POST['status_chosen']);
    $status_chosen = "'" . join("','", $status_chosen) . "'";
    $where_status = ' AND status in (' . $status_chosen . ') ';
}

$priority_chosen = '';
$where_priority = '';
if (!empty($_POST['priority_chosen'])  || $_POST['priority_chosen'] === "0") {
    $priority_chosen = explode("#", $_POST['priority_chosen']);
    $priority_chosen = "'" . join("','", $priority_chosen) . "'";
    $where_priority = ' AND priority in (' . $priority_chosen . ') ';
}

$period_type   = $_REQUEST['period_type'];
$status_obj    = new manage_status();
$ind_coll      = $sec->get_ind_collection('letterbox_coll');
$table         = $_SESSION['collections'][$ind_coll]['table'];
$view          = $_SESSION['collections'][$ind_coll]['view'];
$search_status = $status_obj->get_searchable_status();
$default_year  = date('Y');
$report_type   = $_REQUEST['report_type'];
$core_tools    = new core_tools();
$core_tools->load_lang();

//Récupération de l'ensemble des types de documents
if (!$_REQUEST['entities_chosen']){
    $stmt = $db->query("select entity_id, short_label from ".ENT_ENTITIES." where enabled = 'Y' order by short_label");
}elseif($_REQUEST['sub_entities'] == 'true'){
    $stmt = $db->query("select entity_id, short_label from ".ENT_ENTITIES." where enabled = 'Y' and entity_id IN (".$entities_chosen.") or entity_id IN (".$sub_entities1.") order by short_label");
}else{
    $stmt = $db->query("select entity_id, short_label from ".ENT_ENTITIES." where enabled = 'Y' and entity_id IN (".$entities_chosen.") order by short_label");
}

$entities = array();
while($res = $stmt->fetchObject())
{
    array_push($entities, array('ID' => $res->entity_id, 'LABEL' => $res->short_label));
}

if($period_type == 'period_year')
{
	if(empty($_REQUEST['the_year']) || !isset($_REQUEST['the_year']))
	{
		?>
		<div class="error"><?php echo _YEAR.' '._MISSING;?></div>
		<?php
		exit();
	}
	if(	!preg_match('/^[1-2](0|9)[0-9][0-9]$/', $_REQUEST['the_year']))
	{
		?>
		<div class="error"><?php echo _YEAR.' '._WRONG_FORMAT;?></div>
		<?php
		exit();
	}
	$where_date = " and ".$req->extract_date('creation_date', 'year')." = '".$_REQUEST['the_year']."'";
	$date_title = _FOR_YEAR.' '.$_REQUEST['the_year'];
}

else if($period_type == 'period_month')
{
	$arr_month = array('01','02','03','04','05','06','07','08','09','10','11','12');
	if(empty($_REQUEST['the_month']) || !isset($_REQUEST['the_month']))
	{
		?>
		<div class="error"><?php echo _MONTH.' '._MISSING;?></div>
		<?php
		exit();
	}
	if(	!in_array($_REQUEST['the_month'], $arr_month))
	{
		?>
		<div class="error"><?php echo _MONTH.' '._WRONG_FORMAT;?></div>
		<?php
		exit();
	}
	$where_date = " and ".$req->extract_date('creation_date', 'year')." = '".$default_year."' and ".$req->extract_date('creation_date', 'month')." = '".$_REQUEST['the_month']."'";
	$month = '';
	switch($_REQUEST['the_month'])
	{
		case '01':
			$month = _JANUARY;
			break;
		case '02':
			$month = _FEBRUARY;
			break;
		case '03':
			$month = _MARCH;
			break;
		case '04':
			$month = _APRIL;
			break;
		case '05':
			$month = _MAY;
			break;
		case '06':
			$month = _JUNE;
			break;
		case '07':
			$month = _JULY;
			break;
		case '08':
			$month = _AUGUST;
			break;
		case '09':
			$month = _SEPTEMBER;
			break;
		case '10':
			$month = _OCTOBER;
			break;
		case '11':
			$month = _NOVEMBER;
		case '12':
			$month = _DECEMBER;
			break;
		default:
			$month = '';
	}
	$date_title = _FOR_MONTH.' '.$month;
}
else if($period_type == 'custom_period')
{
	if (empty($_REQUEST['date_start']) && empty($_REQUEST['date_fin'])){
		echo '<div class="error">'._DATE.' '._IS_EMPTY.''.$_REQUEST['date_start'].'</div>';
		exit();
	}
	
	
	if( preg_match($_ENV['date_pattern'],$_REQUEST['date_start'])==false  && $_REQUEST['date_start'] <> ''  )
	{
		
		echo '<div class="error">'._WRONG_DATE_FORMAT.' : '.$_REQUEST['date_start'].'</div>';
		exit();
	
	}
	if( preg_match($_ENV['date_pattern'],$_REQUEST['date_fin'])==false && $_REQUEST['date_fin'] <> '' )
	{
		
		echo '<div class="error">'._WRONG_DATE_FORMAT.' : '.$_REQUEST['date_fin'].'</div>';
		exit();

	}

	if(isset($_REQUEST['date_start']) && $_REQUEST['date_start'] <> '')
	{
		$where_date  .= " AND ".$req->extract_date('creation_date')." >= '".$db->format_date_db($_REQUEST['date_start'])."'";
		$date_title .= strtolower(_SINCE).' '.$_REQUEST['date_start'].' ';
	}

	if(isset($_REQUEST['date_fin']) && $_REQUEST['date_fin'] <> '')
	{
		$where_date  .= " AND ".$req->extract_date('creation_date')." <= '".$db->format_date_db($_REQUEST['date_fin'])."'";
		$date_title.= strtolower(_FOR).' '.$_REQUEST['date_fin'].' ';
	}
	if(empty($where_date))
	{
		$where_date = $req->extract_date('creation_date', 'year')." = '".$default_year."'";
		$date_title = _FOR_YEAR.' '.$default_year;
	}
}
else
{
	?>
	<div class="error"><?php echo _PERIOD.' '._MISSING;?></div>
	<?php
	exit();
}

if($report_type == 'graph')
{
    $vol = array();
    $_SESSION['labels1'] = array();
}
elseif($report_type == 'array')
{
    $data = array();
}
$has_data = true;

//Utilisation de la clause de sécurité de Maarch
$where_clause = $sec->get_where_clause_from_coll_id_and_basket('letterbox_coll');

if ($where_clause)
    $where_clause = " and ".$where_clause;
	
$totalCourrier=array();
$totalEntities = count($entities);

for($i=0; $i<$totalEntities;$i++)
{
    $valid = true;

    if ($valid == 'true' || $_SESSION['user']['UserId'] == "superadmin")
    {

        $stmt = $db->query("SELECT count(res_id) AS total FROM ".$view
                    ." WHERE destination = ? and status not in ('DEL','BAD','END') and date(process_limit_date) <= date(now()) and closing_date is null".$where_date." ".$where_status." ".$where_priority . $where_clause,array($entities[$i]['ID']));

        if( $stmt->rowCount() > 0)
        {
            $tmp = 0;
            $res = $stmt->fetchObject();

            if($report_type == 'graph')
            {

                array_push($vol, $res->total);
            }
            elseif($report_type == 'array')
            {
                array_push($data, array('LABEL' => $entities[$i]['LABEL'], 'VALUE' => $res->total ));
                array_push($totalCourrier, $res->total);
            }
        }
        else
        {
            if($report_type == 'graph')
            {

                array_push($vol, 0);
            }
            elseif($report_type == 'array')
            {
                array_push($data, array('LABEL' => $entities[$i]['LABEL'], 'VALUE' => _UNDEFINED));
            }
        }
        if($report_type == 'graph')
        {
            array_push($_SESSION['labels1'], functions::wash_html($entities[$i]['LABEL'], 'NO_ACCENT'));
        }
    }
}

if ($report_type == 'array'){
    $totalCourriers=array_sum($totalCourrier);
    array_push($data, array('LABEL' => 'Total des courriers rattachés à une entité existante :', 'VALUE' => $totalCourriers));
}

if($report_type == 'graph')
{
    $largeur=50*$totalEntities;
    if ($totalEntities<20){
        $largeur=1000;
    }

    $src1 = $_SESSION['config']['businessappurl']."index.php?display=true&module=reports&page=graphs&type=histo&largeur=$largeur&hauteur=600&marge_bas=300&title=".$title;

    $_SESSION['GRAPH']['VALUES']=[];
    for($i=0;$i<count($vol);$i++)
    {
        $_SESSION['GRAPH']['VALUES'][$i]=$vol[$i];
    }
}
elseif($report_type == 'array')
{
    array_unshift($data, array('LABEL' => _ENTITY, 'VALUE' =>_NB_MAILS1));
}

if ($has_data) {
    if($report_type == 'graph') {
        echo "{label: ['".str_replace(",", "','", addslashes(implode(",", $_SESSION['labels1'])))."'] ".
            ", data: ['".utf8_encode(str_replace(",", "','", addslashes(implode(",", $_SESSION['GRAPH']['VALUES']))))."']".
            ", title: '".addslashes($title)."'}";
        exit;
    } elseif($report_type == 'array') {
	
		$_SESSION['export_data_stat'] = $data;
		$form =	"<input type='button' class='button' value='Exporter les données' onclick='record_data(\"" . $_SESSION['config']['businessappurl']."index.php?display=true&dir=reports&page=record_data \")' style='float:right;'/>";
		echo $form;
		
        $graph->show_stats_array($title, $data);
    }
} else {
    $error = _NO_DATA_MESSAGE;
    echo "{status : 2, error_txt : '".addslashes(functions::xssafe($error))."'}";
}
exit();

?>
