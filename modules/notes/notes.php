<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief notes
* @author <dev@maarch.org>
* @ingroup notes
*/

require_once "core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_request.php";
require_once "apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR
            ."class".DIRECTORY_SEPARATOR."class_lists.php";
require_once "modules".DIRECTORY_SEPARATOR."notes".DIRECTORY_SEPARATOR."notes_tables.php";
require_once "modules" . DIRECTORY_SEPARATOR . "notes" . DIRECTORY_SEPARATOR
    . "class" . DIRECTORY_SEPARATOR
    . "class_modules_tools.php";

$core_tools = new core_tools();
$request    = new request();
$list       = new lists();
$notes_tools = new notes();

$identifier = '';
$origin = '';
$parameters = '';

//Collection ID
if (isset($_REQUEST['coll_id']) && !empty($_REQUEST['coll_id'])) {
    $parameters = "&coll_id=".$_REQUEST['coll_id'];
}

//Identifier
if (isset($_REQUEST['identifier']) && !empty($_REQUEST['identifier'])) {
    $identifier = $_REQUEST['identifier'];
} else {
    echo '<span class="error">'._IDENTIFIER.' '._IS_EMPTY.'</span>';
    exit();
}

//Origin
if (isset($_REQUEST['origin']) && !empty($_REQUEST['origin'])) {
    $origin = $_REQUEST['origin'];
}

if (empty($origin) || $origin != 'folder') {
    $right = \Resource\controllers\ResController::hasRightByResId(['resId' => [$identifier], 'userId' => $_SESSION['user']['UserId']]);
    if (!$right) {
        exit(_NO_RIGHT_TXT);
    }
}

//Extra parameters
if (isset($_REQUEST['size']) && !empty($_REQUEST['size'])) {
    $parameters .= '&size='.$_REQUEST['size'];
}
if (isset($_REQUEST['order']) && !empty($_REQUEST['order'])) {
    $parameters .= '&order='.$_REQUEST['order'];
}
if (isset($_REQUEST['order_field']) && !empty($_REQUEST['order_field'])) {
    $parameters .= '&order_field='.$_REQUEST['order_field'];
}
if (isset($_REQUEST['what']) && !empty($_REQUEST['what'])) {
    $parameters .= '&what='.$_REQUEST['what'];
}

if (isset($_REQUEST['load'])) {
    $core_tools->load_lang();
    $core_tools->load_html();
    $core_tools->load_header('', true, false); ?>

<body><?php
    $core_tools->load_js();

    //Load list
    if (!empty($identifier) && !empty($origin)) {
        $target = $_SESSION['config']['businessappurl']
                .'index.php?module=notes&page=notes&identifier='
                .$identifier.'&origin='.$origin.$parameters;
            
        $listContent = $list->loadList($target);
        echo $listContent;
    } else {
        echo '<span class="error">'._ERROR_IN_PARAMETERS.'</span>';
    } ?>
    <div id="container" style="width:100%;min-height:0px;height:0px;"></div>
</body>

</html><?php
} else {
        //If size is full change some parameters
        if (isset($_REQUEST['size'])
        && ($_REQUEST['size'] == "full")
    ) {
            $sizeUser = "10";
            $sizeText = "40";
            $css = "listing spec";
            $cutString = 150;
        } elseif (isset($_REQUEST['size'])
        && ($_REQUEST['size'] == "medium")
    ) {
            $sizeUser = "15";
            $sizeText = "30";
            $css = "listingsmall";
            $cutString = 100;
        } else {
            $sizeUser = "10";
            $sizeText = "10";
            $css = "listingsmall";
            $cutString = 20;
        }
    
        //Table or view
    $select[NOTES_TABLE] = array(); //Notes
    $select[USERS_TABLE] = array(); //Users
        
    //Fields
    array_push($select[NOTES_TABLE], "id", "identifier", "creation_date", "user_id", "note_text", "note_text as note_short");    //Notes
    array_push($select[USERS_TABLE], "user_id", "lastname || ' ' || firstname as user", "lastname as visibleBy");           //Users
        
    //Where clause
        $where_tab = array();
        
        $rawUserEntities = \Entity\models\EntityModel::getByLogin(['login' => $_SESSION['user']['UserId'], 'select' => ['entity_id']]);
        $userEntities = array_column($rawUserEntities, 'entity_id');
        $userEntities = !empty($userEntities) ? $userEntities : [''];

        $where_tab[] = "identifier = ?";
        $where_tab[] = "type = ?";
        $where_tab[] = "notes.id in (select notes.id from notes left join note_entities on notes.id = note_entities.note_id where item_id IS NULL OR item_id in (?) or notes.user_id = '".$_SESSION['user']['UserId']."')";
        $arrayPDO = array($identifier);
        if (empty($origin) || $origin != 'folder') {
            $arrayPDO[] = 'resource';
        } else {
            $arrayPDO[] = 'folder';
        }

        $arrayPDO[] = $userEntities;

        //Build where
        $where = implode(' and ', $where_tab);
    
        //Order
        $order = $order_field = '';
        $order = $list->getOrder();
        $order_field = $list->getOrderField();
        if (!empty($order_field) && !empty($order)) {
            $orderstr = "order by ".$order_field." ".$order;
        } else {
            $list->setOrder();
            $list->setOrderField('creation_date');
            $orderstr = "order by creation_date desc";
        }

        if (isset($_REQUEST['start']) && !empty($_REQUEST['start'])) {
            $parameters .= '&start='.$_REQUEST['start'];
            $start = $_REQUEST['start'];
        } else {
            $start = $list->getStart();
            $parameters .= '&start='.$start;
        }

        $nbLines = !empty($_REQUEST['lines']) ? $_REQUEST['lines'] : 'default';
    
        //Request
        $tabNotes=$request->PDOselect(
        $select,
        $where,
        $arrayPDO,
        $orderstr,
        $_SESSION['config']['databasetype'],
        $nbLines,
        true,
        NOTES_TABLE,
        USERS_TABLE,
        "user_id",
        true,
        false,
        false,
        $start
    );
        
        // $request->show_array($tabNotes);
        for ($indNotes1 = 0; $indNotes1 < count($tabNotes); $indNotes1 ++) {
            for ($indNotes2 = 0; $indNotes2 < count($tabNotes[$indNotes1]); $indNotes2 ++) {
                foreach (array_keys($tabNotes[$indNotes1][$indNotes2]) as $value) {
                    if ($tabNotes[$indNotes1][$indNotes2][$value] == "id") {
                        $tabNotes[$indNotes1][$indNotes2]["id"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                        $tabNotes[$indNotes1][$indNotes2]["label"] = 'ID';
                        $tabNotes[$indNotes1][$indNotes2]["size"] = 1;
                        $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                        $tabNotes[$indNotes1][$indNotes2]["show"] = false;
                        $tabNotes[$indNotes1][$indNotes2]["order"] = "id";
                        $indNotes1d = $tabNotes[$indNotes1][$indNotes2]['value'];
                    }
                    if ($tabNotes[$indNotes1][$indNotes2][$value] == "user_id") {
                        $tabNotes[$indNotes1][$indNotes2]["user_id"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                        $tabNotes[$indNotes1][$indNotes2]["label"] = _ID;
                        $tabNotes[$indNotes1][$indNotes2]["size"] = 5;
                        $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                        $tabNotes[$indNotes1][$indNotes2]["show"] = false;
                        $tabNotes[$indNotes1][$indNotes2]["order"] = "user_id";
                    }
                
                    if ($tabNotes[$indNotes1][$indNotes2][$value] == "creation_date") {
                        $tabNotes[$indNotes1][$indNotes2]["creation_date"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                        $tabNotes[$indNotes1][$indNotes2]["value"] = $core_tools->format_date_db($tabNotes[$indNotes1][$indNotes2]['value'], false, '', true);
                        $tabNotes[$indNotes1][$indNotes2]["label"] = _DATE;
                        $tabNotes[$indNotes1][$indNotes2]["size"] = 10;
                        $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                        $tabNotes[$indNotes1][$indNotes2]["show"] = true;
                        $tabNotes[$indNotes1][$indNotes2]["order"] = "creation_date";
                    }
                    if ($tabNotes[$indNotes1][$indNotes2][$value] == "user") {
                        $tabNotes[$indNotes1][$indNotes2]["user"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                        $tabNotes[$indNotes1][$indNotes2]["label"] = _USER;
                        $tabNotes[$indNotes1][$indNotes2]["size"] = 10;
                        $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                        $tabNotes[$indNotes1][$indNotes2]["show"] = true;
                        $tabNotes[$indNotes1][$indNotes2]["order"] = "lastname";
                    }
                    if ($tabNotes[$indNotes1][$indNotes2][$value] == "note_text") {
                        //$tabNotes[$indNotes1][$indNotes2]["note_text"] = $tabNotes[$indNotes1][$indNotes2]['value'];
                        $tabNotes[$indNotes1][$indNotes2]["note_text"] = $request->cut_string($request->show_string($tabNotes[$indNotes1][$indNotes2]['value']), $cutString);
                        $tabNotes[$indNotes1][$indNotes2]["label"] = _NOTES;
                        $tabNotes[$indNotes1][$indNotes2]["size"] = 60;
                        $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                        $tabNotes[$indNotes1][$indNotes2]["show"] = true;
                        $tabNotes[$indNotes1][$indNotes2]["order"] = "note_text";
                    }

                    if ($tabNotes[$indNotes1][$indNotes2][$value] == "visibleby") {
                        $noteEntities = $notes_tools->getNotesEntities($indNotes1d);
                        $tabEntityLabel = [];
                        $tabEntityId = [];
                        $allEntities = '';
                        $allEntitiesId = '';

                        foreach ($noteEntities as $value) {
                            $tabEntityLabel[] = $value->short_label;
                            $tabEntityId[] = $value->entity_id;
                        }

                        if (!empty($tabEntityLabel)) {
                            $allEntities = implode(' - ', $tabEntityLabel);
                            if (count($tabEntityId) > 3) {
                                $allEntitiesId = $tabEntityId[0] .'<br/>'.$tabEntityId[1].'<br/>'.$tabEntityId[2].'<br/>...';
                            } else {
                                $allEntitiesId = implode('<br/>', $tabEntityId);
                            }
                        }
                    

                        $tabNotes[$indNotes1][$indNotes2]['value'] = '<div style="cursor:pointer;text-overflow: ellipsis;clear:both;white-space: nowrap;overflow: hidden;"><i title="'.$allEntities.'" >'.$allEntitiesId.'</i></div>';
                        $tabNotes[$indNotes1][$indNotes2]["label"] = _VISIBLEBY;
                        $tabNotes[$indNotes1][$indNotes2]["size"] = 10;
                        $tabNotes[$indNotes1][$indNotes2]["label_align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["align"] = "left";
                        $tabNotes[$indNotes1][$indNotes2]["valign"] = "bottom";
                        $tabNotes[$indNotes1][$indNotes2]["show"] = true;
                    }
                }
            }
        }

    //List
    $listKey = 'id';                                                                    //Clé de la liste
    $paramsTab = array();                                                               //Initialiser le tableau de paramètres
    $paramsTab['bool_sortColumn'] = true;                                               //Affichage Tri
    $paramsTab['pageTitle'] ='';                                                        //Titre de la page
    $paramsTab['bool_bigPageTitle'] = false;                                            //Affichage du titre en grand
    $paramsTab['urlParameters'] = 'identifier='.$identifier
            ."&origin=".$origin.'&display=true'.$parameters;                            //Parametres d'url supplementaires
    $paramsTab['filters'] = array();                                                   //Filtres
    $paramsTab['listHeight'] = '100%';                                                 //Hauteur de la liste
    $paramsTab['start'] = $start;
        $paramsTab['listCss'] = $css;                                                       //CSS
    $paramsTab['tools'] = array();                                                      //Icones dans la barre d'outils
        
    $add = array(
            "script"        =>  "showNotesForm('".$_SESSION['config']['businessappurl']
                                    . "index.php?display=true&module=notes&page=notes_ajax_content"
                                    . "&mode=add&identifier=".$identifier."&origin=".$origin
                                    . $parameters."')",
            "icon"          =>  'pencil-alt',
            "tooltip"       =>  _ADD_NOTE,
            "alwaysVisible" =>  true
            );
        array_push($paramsTab['tools'], $add);
    
        //Action icons array
        $paramsTab['actionIcons'] = array();
    
        $read = array(
        "script"        => "showNotesForm('".$_SESSION['config']['businessappurl']
                                ."index.php?display=true&module=notes&page=notes_ajax_content"
                                ."&mode=up&id=@@id@@&identifier=".$identifier."&origin=".$origin
                                . $parameters."');",
        "class"         =>  "read",
        "icon"          =>  "pencil-alt",
        // "label"         =>  _UPDATE.'/'._DELETE,
        "tooltip"       =>  _UPDATE.'/'._DELETION,
        "disabledRules" => "@@user_id@@ != '".$_SESSION['user']['UserId']."'"
        );
        array_push($paramsTab['actionIcons'], $read);
        
        //Output
        $status = 0;
        $content = $list->showList($tabNotes, $paramsTab, $listKey);

        echo "{status : " . $status . ", content : '" . addslashes($debug.$content) . "', error : '" . addslashes($error) . "'}";
    }
