<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   load_toolbarEntities
* @author  dev <dev@maarch.org>
* @ingroup entities
*/
require_once "modules" . DIRECTORY_SEPARATOR . "entities" . DIRECTORY_SEPARATOR
    .   "class" . DIRECTORY_SEPARATOR . "class_manage_entities.php";
$db = new Database();
$ent = new entity();

$stmt = $db->query(
    "SELECT distinct(r.destination) as entity_id, count(distinct r.res_id)"
    . " as total, e.short_label FROM " 
    . " res_view_letterbox r left join entities"
    . " e on e.entity_id = r.destination " .$_POST['where'] . " and entity_id <> ''"
    . " group by e.short_label, r.destination order by e.short_label"
);
$options ='<option value="none" style="text-align:center;"></option>';

while ($res = $stmt->fetchObject()) {
    
    if ((isset($_SESSION['filters']['entity']['VALUE']) || isset($_SESSION['filters']['entity_subentities']))
        && $_SESSION['filters']['entity']['VALUE'] == $res->entity_id
        )  $selected = 'selected="selected"'; else $selected =  '';
        
    if ($ent->is_user_in_entity($_SESSION['user']['UserId'], $res->entity_id)) $style = 'style="font-weight:bold;"';  else $style =  '';

    $options .='<option value="'.$res->entity_id.'" '.$selected.' '.$style.'>'.$res->short_label.' ('.$res->total.')</option>';
}

echo "{status : 0, resultContent : '" . addslashes($options) . "'}";
exit();
