<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief load Notes in results list
* @author <dev@maarch.org>
* @ingroup notes
*/

require_once 'core/class/class_core_tools.php';
require_once "modules" . DIRECTORY_SEPARATOR . "notes" . DIRECTORY_SEPARATOR
    . "class" . DIRECTORY_SEPARATOR
    . "class_modules_tools.php";
$Core_Tools = new core_tools;
$Core_Tools->load_lang();
$Core_Tools->test_user();

$return = '';

if (isset($_REQUEST['identifier'])) {
    $status = 0;
    $return .= '<td>';
    $return .= '<div align="center">';
    $return .= '<table width="97%%">';

    $db = new Database();

    $query = "SELECT ";
    $query .= "DISTINCT(notes.id), ";
    $query .= "user_id, ";
    $query .= "date_note, ";
    $query .= "note_text ";
    $query .= "FROM ";
    $query .= "notes ";
    $query .= "left join ";
    $query .= "note_entities ";
    $query .= "on ";
    $query .= "notes.id = note_entities.note_id ";
    $query .= "WHERE ";
    $query .= "coll_id = ? ";
    $arrayPDO = array($_SESSION['collection_id_choice']);
    $query .= "AND ";
    $query .= "identifier = ? ";
    $arrayPDO = array_merge($arrayPDO, array($_REQUEST['identifier']));
    $query .= "AND ";
    $query .= "( ";
    $query .= "( ";
    $query .= "item_id IN (";
          
    if(!empty($_SESSION['user']['entities'])){
        foreach ($_SESSION['user']['entities'] as $entitiestmpnote) {
            $query .= "?, ";
            $arrayPDO = array_merge($arrayPDO, array($entitiestmpnote['ENTITY_ID']));
        }
        $query = substr($query, 0, -2);
    } else {
        $query .= "''";
    }
          
    $query .= ") ";
    $query .= "OR ";
    $query .= "item_id IS NULL ";
    $query .= ") ";
    $query .= "OR ";
    $query .= "user_id = '" . $_SESSION['user']['UserId'] . "' ";
    $query .= ") ";
    $query .= " order by date_note desc";

    $stmt = $db->query($query, $arrayPDO);

    $fetch = '';
    while ($return_db = $stmt->fetchObject()) {
        // get lastname and firstname for user_id
        //var_dump($return_db);
        $stmt2 = $db->query("SELECT lastname, firstname FROM users WHERE user_id =?", array($return_db->user_id));
        while ($user_db = $stmt2->fetchObject()) {
            $lastname = $user_db->lastname;
            $firstname = $user_db->firstname;
        }
        $stmt3 = $db->query("SELECT notes.id as id, identifier, user_id, note_text, item_id, entity_label FROM notes, note_entities, entities WHERE identifier = ? AND note_id = notes.id AND entities.entity_id = note_entities.item_id and notes.id = ?", array($_REQUEST['identifier'], $return_db->id));
        $entity_label = '';
        $Tabentity = [];
        while ($entity = $stmt3->fetchObject()) {
            $Tabentity[] = $entity->entity_label;
            $item_id = $entity->id;
            $entity_label = $entity->entity_label;
        }
        $return .= '<tr>';
        $return .= '<td style="background: transparent; padding-left:30px; padding-right:30px; border: 1px dashed rgb(200, 200, 200);">';
        // $return .= '<blockquote style="padding: 1px;">';
        $return .= '<div style="text-align: right; background-color: rgb(230, 230, 230); padding: 2px;">';
        $allEntity = '';
        foreach ($Tabentity as $value) {
            $allEntity .= $value." / ";
        }
        $notes_tools = new notes();
        $noteEntities = $notes_tools->getNotesEntities($return_db->id);
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
            $allEntitiesId = implode(', ', $tabEntityId);
        }

        $return .= functions::xssafe($firstname) . ' ' . functions::xssafe($lastname);
        $return .= ', ';
        $return .= functions::xssafe($Core_Tools->format_date_db($return_db->date_note));
                                    
        $return .= '</div>';

        // $return .= '<br />';
        if ($entity_label != '') {
            $return .= '<div style="padding-top:2px;padding-bottom:2px;">';
            $note_text = str_replace(array("\r", "\n"), array("<br />", "<br />"), functions::xssafe($return_db->note_text));
            $return .= str_replace('<br /><br />', '<br />', $note_text);
            $return .= '</div>';
            $return .= '<div style="padding-top:2px;padding-bottom:2px;">';
            $return .= '<div style="font-style:italic;clear:both;"><i title="'.$allEntities.'" >'._RESTRICTED_SERVICES.$allEntitiesId.'</i></div>';
            $return .= '</div>';
        } else {
            $return .= '<div style="padding-top:2px;padding-bottom:2px;">';
            $note_text = str_replace(array("\r", "\n"), array("<br />", "<br />"), functions::xssafe($return_db->note_text));
            $return .= str_replace('<br /><br />', '<br />', $note_text);
            $return .= '</div>';
        }


        // $return .= '</blockquote>';
        $return .= '</td>';
        $return .= '</tr>';
    }
    $return .= '</table>';
    $return .= '<br />';
    $return .= '</div>';
    $return .= '</td>';
} else {
    $status = 1;
    $return .= '<td colspan="6" style="background-color: red;">';
    $return .= '<p style="padding: 10px; color: black;">';
    $return .= 'Erreur lors du chargement des notes';
    $return .= '</p>';
    $return .= '</td>';
}

echo "{status : " . $status . ", toShow : '" . addslashes($return) . "'}";
exit();
