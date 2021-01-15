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
require_once("modules/entities/entities_tables.php");
$_ENV['date_pattern'] = "/^[0-3][0-9]-[0-1][0-9]-[1-2][0-9][0-9][0-9]$/";


$graph = new graphics();
$req   = new request();
$sec   = new security();
$db    = new Database();

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
if (!empty($_POST['priority_chosen']) || $_POST['priority_chosen'] === "0") {
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
	$stmt = $db->query("select entity_id, short_label from ".ENT_ENTITIES." where enabled = 'Y' and entity_id IN (".$entities_chosen.") or entity_id IN (".$sub_entities1.") order by short_label",array());
}else{
    $stmt = $db->query("select entity_id, short_label from ".ENT_ENTITIES." where enabled = 'Y' and entity_id IN (".$entities_chosen.") order by short_label",array());
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
    $val_an = array();
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
	
$totalEntities = count($entities);
	
for($i=0; $i<$totalEntities;$i++)
{
    //Permet d'afficher ou non les entités dont le nombre de courrier est égal à 0
	$valid = true;
    if ($_SESSION['user']['entities']){
        foreach($_SESSION['user']['entities'] as $user_ent){
            if ($entities[$i]['ID'] == $user_ent['ENTITY_ID']){
                $valid = true;
            }
        }
    }
    if ($valid == 'true' || $_SESSION['user']['UserId'] == "superadmin")
    {

        $stmt = $db->query("SELECT ".$req->get_date_diff('closing_date', 'creation_date' )." AS delay, creation_date FROM ".$view
            . " WHERE destination = ? AND status not in ('DEL','BAD') AND closing_date IS NOT NULL".$where_date." ".$where_status." ".$where_priority . $where_clause,array($entities[$i]['ID']));
        
        if( $stmt->rowCount() > 0)
        {
            $tmp = 0;
            $nbDoc=0;
            while($res = $stmt->fetchObject())
            {
                if ($res->delay <> "") {
                    $tmp = $tmp + $res->delay;
                    $nbDoc++;
                }
                
            }
            if ($nbDoc == 0) $nbDoc = 1;
            if($report_type == 'graph')
            {
                array_push($val_an, (string)round($tmp / $nbDoc, 1));
            }
            elseif($report_type == 'array')
            {
                array_push($data, array('LABEL' => $db->show_string($entities[$i]['LABEL']), 'VALUE' => (string)round($tmp / $nbDoc, 1)));
            }
        }
        else
        {
            if($report_type == 'graph')
            {
                array_push($val_an, 0);
            }
            elseif($report_type == 'array')
            {
                array_push($data, array('LABEL' => $db->show_string($entities[$i]['LABEL']), 'VALUE' => _UNDEFINED));
            }
        }
        if($report_type == 'graph')
        {
            array_push($_SESSION['labels1'], addslashes($db->show_string($entities[$i]['LABEL'])));
        }
    }
}

if($report_type == 'graph')
{
    $largeur=50*$totalEntities;
    if ($totalEntities<20){
        $largeur=1000;
    }

    $src1 = $_SESSION['config']['businessappurl']."index.php?display=true&module=reports&page=graphs&type=histo&largeur=$largeur&hauteur=600&marge_bas=300&title=".$title."&labelY="._N_DAYS;

    $_SESSION['GRAPH']['VALUES']=[];
    for($i=0;$i<count($val_an);$i++)
    {
        $_SESSION['GRAPH']['VALUES'][$i]=$val_an[$i];
    }
}
elseif($report_type == 'array')
{
    array_unshift($data, array('LABEL' => _DOCTYPE, 'VALUE' => _PROCESS_DELAY));
}

if($report_type == 'graph') {
    $labels1 = "'".implode("','", $_SESSION['labels1'])."'";
    echo "{label: [".$labels1."] ".
        ", data: ['".utf8_encode(str_replace(",", "','", addslashes(implode(",", $_SESSION['GRAPH']['VALUES']))))."']".
        ", title: '".addslashes($title)."'}";
    exit;

} elseif($report_type == 'array') {
	$_SESSION['export_data_stat'] = $data;
	$form =	"<input type='button' class='button' value='Exporter les données' onclick='record_data(\"" . $_SESSION['config']['businessappurl']."index.php?display=true&dir=reports&page=record_data \")' style='float:right;'/>";
	echo $form;
	
    $graph->show_stats_array($title, $data);
}

exit();
