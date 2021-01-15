<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   document_workflow_history
* @author  dev <dev@maarch.org>
* @ingroup indexing_searching
*/

require_once "core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_request.php";
require_once "core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_security.php";
require_once "apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR
            ."class".DIRECTORY_SEPARATOR."class_lists.php";
            
$core_tools = new core_tools();
$request    = new request();
$sec        = new security();
$list       = new lists();
$db         = new Database();

$parameters = '';

//Ressource ID
if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
} else {
    echo '<span class="error">'._ID.' '._IS_EMPTY.'</span>';
    exit();
}

$right = $sec->test_right_doc('letterbox_coll', $id);
if (!$right) {
    exit(_NO_RIGHT_TXT);
}

//Collection ID
if (isset($_REQUEST['coll_id']) && !empty($_REQUEST['coll_id'])) {
    $table = $sec->retrieve_table_from_coll($_REQUEST['coll_id']);
    $view = $sec->retrieve_view_from_coll_id($_REQUEST['coll_id']);
    $parameters = "&coll_id=".$_REQUEST['coll_id'];
} else {
    echo '<span class="error">'._COLLECTION.' '._IS_EMPTY.'</span>';
    exit();
}

//Extra parameters
if (isset($_REQUEST['size']) && !empty($_REQUEST['size'])) {
    $parameters .= '&size='.$_REQUEST['size'];
}

if (isset($_REQUEST['load'])) {
    //
    $core_tools->load_lang();
    $core_tools->load_html();
    $core_tools->load_header('', true, false); ?>

<body>
    <?php
        $core_tools->load_js();
        
    //Load list
    $target = $_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=document_workflow_history&id='.$id.$parameters;
    $listContent = $list->loadList($target);
    echo $listContent; ?>
</body>

</html>
<?php
} else {
        //If size is full change some parameters
        if (isset($_REQUEST['size']) && ($_REQUEST['size'] == "full")) {
            $sizeUser = "15";
            $sizeText = "30";
            $css = "listing spec";
            $cutString = 200;
        // $linesToShow  = 15;
        } elseif (isset($_REQUEST['size']) && ($_REQUEST['size'] == "medium")) {
            $sizeUser = "15";
            $sizeText = "30";
            $css = "listing2 spec";
            $cutString = 100;
            $linesToShow  = 15;
        } else {
            $sizeUser = "10";
            $sizeText = "10";
            $css = "listingsmall";
            $cutString = 20;
            $linesToShow  = 15;
        }

        //Order
        $order = $order_field = '';
        $order = $list->getOrder();
        $order_field = $list->getOrderField();
        if (!empty($order_field) && !empty($order)) {
            $orderstr = "order by ".$order_field." ".$order;
        } else {
            $list->setOrder();
            $list->setOrderField('event_date');
            $orderstr = "order by event_date desc";
        }
        if (isset($_REQUEST['start']) && !empty($_REQUEST['start'])) {
            $parameters .= '&start='.$_REQUEST['start'];
            $start = $_REQUEST['start'];
        } else {
            $start = $list->getStart();
            $parameters .= '&start='.$start;
        }

        //select
        $select['history'] = array();
        $select['users'] = array();

        array_push(
    $select['history'],
    'event_date',
    'info',
    'info'
    );
        array_push(
    $select['users'],
    'user_id',
    'firstname',
    'lastname'
    );

        //From filters
        $whereTab = [];
        $filterClause = $list->getFilters();
        if (!empty($filterClause)) {
            $whereTab[] = $filterClause;
        }

        //Where tablename or view
        if ((empty($table) || !$table) && (!empty($view) && $view <> false)) {
            $whereTab[] = "history.table_name= '" . $view . "'";
        } elseif ((empty($view) || !$view) && (!empty($table) && $table <> false)) {
            $whereTab[] = "history.table_name= '" . $table . "'";
        } elseif (!empty($view) && !empty($table) && $view <> false && $table <> false) {
            $whereTab[] = "(history.table_name= '" . $table . "' OR history.table_name = '" . $view . "')";
        }

        //Where query
        $whereTab[] = "history.record_id = ? and history.user_id = users.user_id and event_id NOT LIKE '^[0-9]+$' and event_type like 'ACTION#%'";

        //Build Where
        $where = implode(' AND ', $whereTab);

        $arrayPDO = [$id];

        $nbLines = !empty($_REQUEST['lines']) ? $_REQUEST['lines'] : 'default';

        $tab = $request->PDOselect(
    $select,
    $where,
    $arrayPDO,
    $orderstr,
    $_SESSION['config']['databasetype'],
    $nbLines,
    false,
    '',
    '',
    '',
    false,
    false,
    false,
    $start
    );

        //Result Array
        for ($i=0; $i<count($tab); $i++) {
            for ($j=0; $j<count($tab[$i]); $j++) {
                foreach (array_keys($tab[$i][$j]) as $value) {
                    if ($tab[$i][$j][$value]=="id") {
                        $tab[$i][$j]["id"]=$tab[$i][$j]['value'];
                        $tab[$i][$j]["label"]=_ID;
                        $tab[$i][$j]["size"]="1";
                        $tab[$i][$j]["label_align"]="left";
                        $tab[$i][$j]["align"]="left";
                        $tab[$i][$j]["valign"]="bottom";
                        $tab[$i][$j]["show"]=true;
                        $tab[$i][$j]["order"]='id';
                    }
                    if ($tab[$i][$j][$value]=="event_date") {
                        $tab[$i][$j]["value"]=$request->dateformat($tab[$i][$j]["value"]);
                        $tab[$i][$j]["label"]=_DATE;
                        $tab[$i][$j]["size"]="10";
                        $tab[$i][$j]["label_align"]="left";
                        $tab[$i][$j]["align"]="left";
                        $tab[$i][$j]["valign"]="bottom";
                        $tab[$i][$j]["show"]=true;
                        $tab[$i][$j]["order"]='event_date';
                    }
                    if ($tab[$i][$j][$value]=="firstname") {
                        $firstname =  $request->show_string($tab[$i][$j]["value"]);
                    }
                    if ($tab[$i][$j][$value]=="lastname") {
                        $tab[$i][$j]["value"] = $firstname . ' ' . $request->show_string($tab[$i][$j]["value"]) ;
                        $tab[$i][$j]["label"]=_USER;
                        $tab[$i][$j]["size"]=$sizeUser;
                        $tab[$i][$j]["label_align"]="left";
                        $tab[$i][$j]["align"]="left";
                        $tab[$i][$j]["valign"]="bottom";
                        $tab[$i][$j]["show"]=true;
                        $tab[$i][$j]["order"]='lastname';
                    }
                    if ($tab[$i][$j][$value]=="info") {
                        $tab[$i][$j]["value"] = $request->show_string($tab[$i][$j]["value"]);
                        $tab[$i][$j]["label"]=_EVENT;
                        $tab[$i][$j]["size"]=$sizeText;
                        $tab[$i][$j]["label_align"]="left";
                        $tab[$i][$j]["align"]="left";
                        $tab[$i][$j]["valign"]="bottom";
                        $tab[$i][$j]["show"]=true;
                        $tab[$i][$j]["order"]='info';
                    }
                }
            }
        }

        //List
    $listKey = 'id';                                                            //Clee de la liste
    $paramsTab = array();                                                       //Initialiser le tableau de parametres
    $paramsTab['bool_sortColumn'] = true;                                       //Affichage Tri
    $paramsTab['pageTitle'] ='';                                                //Titre de la page
    $paramsTab['bool_bigPageTitle'] = false;                                    //Affichage du titre en grand
    $paramsTab['urlParameters'] = 'dir=indexing_searching&id='
        .$id.'&display=true'.$parameters;                                       //Parametres d'url supplementaires
    $paramsTab['listHeight'] = '100%';                                          //Hauteur de la liste
    $paramsTab['start'] = $start;

        //Output
        $status = 0;
        $content = $list->showList($tab, $paramsTab, $listKey);
        $debug = '';

        echo "{status : " . $status . ", content : '" . addslashes($debug.$content) . "', error : '" . addslashes($error) . "'}";
    }
