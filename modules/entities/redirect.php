<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   redirect
* @author  dev <dev@maarch.org>
* @ingroup entities
*/

$confirm = false;
$etapes = array('form');
$frm_width='400px';
$frm_height = '90%';

$destination = '';

require "modules/entities/entities_tables.php";
require_once "modules/entities/class/EntityControler.php";
require_once 'modules/entities/class/class_manage_entities.php';
require_once 'apps/' . $_SESSION['config']['app_id'] . '/class/class_chrono.php';
require_once "apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR ."class".DIRECTORY_SEPARATOR."class_lists.php";

function get_form_txt($values, $path_manage_action,  $id_action, $table, $module, $coll_id, $mode )
{

    $ent = new entity();
    $entity_ctrl = new EntityControler();
    $services = array();
    $servicesCompare = array();
    $cr7 = new chrono();
    $db = new Database();
    $sec = new security();
    $labelAction = '';
    if ($id_action <> '') {
        $stmt = $db->query("select label_action from actions where id = ?", array($id_action));
        $resAction = $stmt->fetchObject();
        $labelAction = functions::show_string($resAction->label_action);
    }
    
    preg_match("'^ ,'", $_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['entities'], $out);
    if (count($out[0]) == 1) {
        $_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['entities'] = substr($_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['entities'], 2, strlen($_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['entities']));
    }
    if (!empty($_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['entities'])) {

        $stmt = $db->query("select entity_id, entity_label from ".ENT_ENTITIES." where entity_id in (".$_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['entities'].") and enabled= 'Y' order by entity_label");
        while ($res = $stmt->fetchObject()) {

            array_push($services, array( 'ID' => $res->entity_id, 'LABEL' => $db->show_string($res->entity_label)));
            array_push($servicesCompare, $res->entity_id);
        }
    }
    $users = array();
    if (!empty($_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['users_entities']) ) {

        $stmt = $db->query("select distinct ue.user_id, u.lastname, u.firstname from ".ENT_USERS_ENTITIES." ue, ".$_SESSION['tablename']['users']." u where ue.entity_id in (".$_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['users_entities'].") and u.user_id = ue.user_id and (u.status = 'OK' or u.status = 'ABS') and enabled = 'Y' order by u.lastname asc");
        while ($res = $stmt->fetchObject()) {

            array_push($users, array( 'ID' => $res->user_id, 'NOM' => functions::show_string($res->lastname), "PRENOM" => functions::show_string($res->firstname)));
        }
    }

    $frm_str = '<div id="frm_error_'.$id_action.'" class="error"></div>';
    if ($labelAction <> '') {
        $frm_str .= '<h2 class="title">' . $labelAction . ' ' . _NUM;
    } else {
        $frm_str .= '<h2 class="title">'._REDIRECT_MAIL.' '._NUM;
    }
    $values_str = '';
    if (empty($_SESSION['stockCheckbox'])) {

        for ($i=0; $i < count($values);$i++) {

            if (_ID_TO_DISPLAY == 'res_id') {
                $values_str .= $values[$i].', ';

            } else if (_ID_TO_DISPLAY == 'chrono_number') {
                $values_str      .= $values[$i].', ';
                $chrono_number   = $cr7->get_chrono_number($values[$i], 'res_view_letterbox');
                $values_str_chrn .= $chrono_number.', ';
            }
        }
    } else { 

        for ($i=0; $i < count($_SESSION['stockCheckbox']);$i++) {
            if (_ID_TO_DISPLAY == 'res_id') {
                $values_str .= $_SESSION['stockCheckbox'][$i].', ';

            } else if (_ID_TO_DISPLAY == 'chrono_number') {
                $values_str .= $_SESSION['stockCheckbox'][$i].', ';

                $chrono_number = $cr7->get_chrono_number($_SESSION['stockCheckbox'][$i], 'res_view_letterbox');
                $values_str_chrn .= $chrono_number.', ';
            }
        }
    }

    $values_str = preg_replace('/, $/', '', $values_str);

    if (_ID_TO_DISPLAY == 'res_id') {
        $frm_str .= $values_str;
    } else if (_ID_TO_DISPLAY == 'chrono_number') {
        $values_str_chrn = preg_replace('/, $/', '', $values_str_chrn);
        $frm_str .= $values_str_chrn;
    }
    $frm_str .= '</h2><br/><br/>';
    include 'modules/templates/class/templates_controler.php';
    $templatesControler = new templates_controler();
    $templates = array();

    if (!empty($_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['entities'])) {
      
        $EntitiesIdExclusion = array();
        $entities            = $entity_ctrl->getAllEntities();
        $countEntities       = count($entities);

        for ($cptAllEnt = 0;$cptAllEnt<$countEntities;$cptAllEnt++) {
            if (!is_integer(array_search($entities[$cptAllEnt]->__get('entity_id'), $servicesCompare))) {
                array_push($EntitiesIdExclusion, $entities[$cptAllEnt]->__get('entity_id'));
            }
        }
        
        $allEntitiesTree = array();
        $allEntitiesTree = $ent->getShortEntityTreeAdvanced(
            $allEntitiesTree, 'all', '', $EntitiesIdExclusion, 'all'
        );
        //Collection
        if (isset($_REQUEST['coll_id']) && ! empty($_REQUEST['coll_id'])) {
            $collId = trim($_REQUEST['coll_id']);
            $parameters .= '&coll_id='.$_REQUEST['coll_id'];
            $view = $sec->retrieve_view_from_coll_id($collId);
            $table = $sec->retrieve_table_from_coll($collId);
            //retrieve the process entity of document
            $aResId = explode(", ", $values_str);
            $stmt = $db->query(
                "SELECT destination FROM " . $table . " WHERE res_id in (?)", array($aResId)
            );
            $resultDest = $stmt->fetchObject();
            $destination = $resultDest->destination;
        }
        if ($destination <> '') {
            $templates = $templatesControler->getAllTemplatesForProcess($destination);
        } else {
            $templates = $templatesControler->getAllTemplatesForSelect();
        } 
        $frm_str .='<br/><b>'._REDIRECT_NOTE.':</b><br/>';
        $frm_str .= '<select name="templateNotes" id="templateNotes" style="width:98%;margin-bottom: 10px;background-color: White;border: 1px solid #999;color: #666;text-align: left;" '
                    . 'onchange="addTemplateToNote($(\'templateNotes\').value, \''
                    . $_SESSION['config']['businessappurl'] . 'index.php?display=true'
                    . '&module=templates&page=templates_ajax_content_for_notes\');document.getElementById(\'notes\').focus();">';
        $frm_str .= '<option value="">' . _SELECT_NOTE_TEMPLATE . '</option>';
        for ($i=0;$i<count($templates);$i++) {
            if ($templates[$i]['TYPE'] == 'TXT' && ($templates[$i]['TARGET'] == 'notes' || $templates[$i]['TARGET'] == '')) {
                $frm_str .= '<option value="';
                $frm_str .= $templates[$i]['ID'];
                $frm_str .= '">';
                $frm_str .= $templates[$i]['LABEL'];
            }
            $frm_str .= '</option>';
        }
        $frm_str .= '</select><br />';

        $frm_str .= '<textarea style="width:98%;height:60px;resize:none;" name="notes"  id="notes" onblur="setNoteRedirect()"></textarea>';
        $frm_str .= '<hr />';
        $frm_str .='<div id="form2" style="border:none;">';
        $frm_str .= '<form name="frm_redirect_dep" id="frm_redirect_dep" method="post" class="forms" action="#">';
        $frm_str .= '<input type="hidden" name="chosen_action" id="chosen_action" value="end_action" />';
        $frm_str .= '<input type="hidden" name="note_content_to_dep" id="note_content_to_dep" />';
        $frm_str .='<p>';
        $frm_str .= '<b>'._REDIRECT_TO_OTHER_DEP.' :</b><br/>';
        $frm_str .= '<select name="department" id="department" data-placeholder="'._CHOOSE_DEPARTMENT.'" onchange="change_entity(this.options[this.selectedIndex].value, \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=entities&page=load_listinstance'.'\', \'diff_list_div_redirect\', \'redirect\');" style="float:left;">';
        $frm_str .='<option value=""></option>';
        $countAllEntities = count($allEntitiesTree);
        for ($cptEntities = 0;$cptEntities < $countAllEntities;$cptEntities++) {
            if (!$allEntitiesTree[$cptEntities]['KEYWORD']) {
                $frm_str .= '<option data-object_type="entity_id" value="' . $allEntitiesTree[$cptEntities]['ID'] . '"';
                if ($allEntitiesTree[$cptEntities]['ID'] == $_SESSION['user']['primaryentity']['id']) {
                    $frm_str .= ' selected="selected"';
                }
                if ($allEntitiesTree[$cptEntities]['DISABLED']) {
                    $frm_str .= ' disabled="disabled" class="disabled_entity"';
                }
                $frm_str .=  '>' 
                    .  $ent->show_string($allEntitiesTree[$cptEntities]['SHORT_LABEL']) 
                    . '</option>';
            }
        }
        $frm_str .='</select>';
        $frm_str .='<script>$j("#department").chosen({width: "80%", disable_search_threshold: 10, search_contains: true,allow_single_deselect: true});document.getElementById("department").onchange();$j("#department").trigger("chosen:updated");</script>';
        $frm_str .=' <input type="button" style="float:right;margin:0px;" name="redirect_dep" value="'._REDIRECT.'" id="redirect_dep" class="button" onclick="valid_action_form( \'frm_redirect_dep\', \''.$path_manage_action.'\', \''. $id_action.'\', \''.$values_str.'\', \''.$table.'\', \''.$module.'\', \''.$coll_id.'\', \''.$mode.'\');" />';
        $frm_str .='<div style="clear:both;"></div>';
        $frm_str .= '<div id="diff_list_div_redirect" class="scroll_div" style="height:auto;"></div>';
        $frm_str .='</p>';
        $frm_str .='</form>';
        $frm_str .='</div>';
    }
    if (!empty($_SESSION['user']['redirect_groupbasket'][$_SESSION['current_basket']['id']][$id_action]['users_entities'])) {

        $frm_str .='<hr />';
        $frm_str .='<div id="form3">';
        $frm_str .= '<form name="frm_redirect_user" id="frm_redirect_user" method="post" class="forms" action="#">';
        $frm_str .= '<input type="hidden" name="chosen_action" id="chosen_action" value="end_action" />';
        $frm_str .= '<input type="hidden" name="note_content_to_user" id="note_content_to_user" value="" />';
        $frm_str .='<p>';
        $frm_str .='<label><b>'._REDIRECT_TO_USER.' :</b></label>';
        $frm_str .='<select name="user" id="user" style="float:left;" data-placeholder="'._CHOOSE_USER2.'">';
        $frm_str .='<option value=""></option>';
        for ($i=0; $i < count($users); $i++) {
            $frm_str .='<option value="'.$users[$i]['ID'].'">'.$users[$i]['NOM'].' '.$users[$i]['PRENOM'].'</option>';
        }
        $frm_str .='</select>';
        $frm_str .='<script>$j("#user").chosen({width: "80%", disable_search_threshold: 10, search_contains: true,allow_single_deselect: true});</script>';
        $frm_str .=' <input type="button" style="float:right;margin:0px;" name="redirect_user" id="redirect_user" value="'
        ._REDIRECT
        . '" class="button" onclick="valid_action_form( \'frm_redirect_user\', \''
        . $path_manage_action . '\', \'' . $id_action . '\', \'' . $values_str . '\', \'' . $table . '\', \'' . $module . '\', \'' . $coll_id . '\', \'' . $mode . '\');"  />';
        $frm_str .='</p>';
        $frm_str .='<div style="clear:both;"></div>';
        $frm_str .='</form>';
        $frm_str .='</div>';
    }
    $frm_str .='<hr />';

    $frm_str .='<div align="center">';
            $frm_str .='<input type="button" name="cancel" id="cancel" class="button"  value="'._CANCEL.'" onclick="pile_actions.action_pop();destroyModal(\'modal_'.$id_action.'\');"/>';
    $frm_str .='</div>';
    return addslashes($frm_str);
}

function check_form($form_id,$values)
{
    if ($form_id == 'frm_redirect_dep') {
        $dep = get_value_fields($values, 'department');
        if ($dep == '') {
            $_SESSION['action_error'] = _MUST_CHOOSE_DEP;
            return false;
        } else if (empty($_SESSION['redirect']['diff_list']['dest']['users'][0]) || ! isset($_SESSION['redirect']['diff_list']['dest']['users'][0])) {
                
            $_SESSION['action_error'] = _DEST . " " . _MANDATORY;
            return false;
        } else {
            return true;
        }
    } else if ($form_id == 'frm_redirect_user') {
        $user = get_value_fields($values, 'user');
        if ($user == '') {

            $_SESSION['action_error'] = _MUST_CHOOSE_USER;
            return false;
        } else {
            return true;
        }
    } else {
        $_SESSION['action_error'] = _FORM_ERROR;
        return false;
    }
}

function manage_form($arr_id, $history, $id_action, $label_action, $status, $coll_id, $table, $values_form )
{
    /*
        Redirect to dep:
        $values_form = array (size=3)
          0 => 
            array (size=2)
              'ID' => string 'chosen_action' (length=13)
              'VALUE' => string 'end_action' (length=10)
          1 => 
            array (size=2)
              'ID' => string 'department' (length=10)
              'VALUE' => string 'DGA' (length=3)
          2 => 
            array (size=2)
              'ID' => string 'redirect_dep' (length=12)
              'VALUE' => string 'Rediriger' (length=9)
        
        Redirect to user:
        $values_form = array (size=3)
          0 => 
            array (size=2)
              'ID' => string 'chosen_action' (length=13)
              'VALUE' => string 'end_action' (length=10)
          1 => 
            array (size=2)
              'ID' => string 'user' (length=4)
              'VALUE' => string 'aackermann' (length=10)
          2 => 
            array (size=2)
              'ID' => string 'redirect_user' (length=13)
              'VALUE' => string 'Rediriger' (length=9)
    
    */
    
    if(empty($values_form) || count($arr_id) < 1) 
        return false;
    
    include_once 'modules/entities/class/class_manage_listdiff.php';
    $diffList = new diffusion_list();
    
    $db = new Database();
    
    $formValues = array();
    for ($i=0; $i<count($values_form); $i++) {
        $formValue = $values_form[$i];
        $id = $formValue['ID'];
        $value = $formValue['VALUE'];
        $formValues[$id] = $value;
    }
    
    // 1 : Redirect to user :
    //   - create new listinstance from scratch with only dest user
    //   - do not change destination
    if (isset($formValues['redirect_user'])) {
        $userId = $formValues['user'];

        // Select new_dest user info
        $stmt = $db->query(
            "select u.user_id, u.firstname, u.lastname, e.entity_id, e.entity_label "
            . "FROM " . $_SESSION['tablename']['users'] . " u, " . ENT_ENTITIES . " e, "
            . ENT_USERS_ENTITIES . " ue WHERE u.status <> 'DEL' and u.enabled = 'Y' and"
            . " e.entity_id = ue.entity_id and u.user_id = ue.user_id and"
            . " e.enabled = 'Y' and ue.primary_entity='Y' and u.user_id = ?", array($userId)
        );
        $user = $stmt->fetchObject();
        
        // Create new listinstance
        $_SESSION['redirect']['diff_list'] = array();
        $_SESSION['redirect']['diff_list']['difflist_type'] = 'entity_id';
        $_SESSION['redirect']['diff_list']['dest']['users'][0] = array(
            'user_id' => $userId,
            'lastname' => $user->lastname,
            'firstname' => $user->firstname,
            'entity_id' => $user->entity_id,
            'viewed' => 0,
            'visible' => 'Y',
            'difflist_type' => 'entity_id'
        );
        $message = ' (' . _REDIRECT_TO_USER_OK . ': ' . $userId . ')';

    } elseif (isset($formValues['redirect_dep'])) {
        // 2 : Redirect to departement (+ dest user)
        //   - listinstance has laready been loaded when selecting entity
        //   - get entity_id that will update destination
        $entityId = $formValues['department'];

        $stmt = $db->query("SELECT entity_label FROM entities WHERE entity_id = ?", array($entityId));
        $list = $stmt->fetchObject();
        $entity_label = $list->entity_label;
        $message = " (" . _REDIRECT_TO_DEP_OK . ": " . $entity_label . ')';

    }
    
    // 1 + 2 :
    //   - update dest_user
    //   - move former dest user to copy if requested
    //   - finally save listinstance 
    for ($i=0; $i<count($arr_id); $i++) {

        $new_difflist = $_SESSION['redirect']['diff_list'];

        // Fix lorsque l'on redirige vers une entité qui n'a pas de liste de diffusion par défaut
        if (empty($new_difflist['difflist_type'])) {
            $new_difflist['difflist_type'] = 'entity_id';
        }

        $res_id = $arr_id[$i];
        // update dest_user
        $new_dest = $new_difflist['dest']['users'][0]['user_id'];
        if ($new_dest) {
            if ($formValues['note_content_to_user'] != '') {
                //Add notes
                $userIdTypist = $_SESSION['user']['UserId'];
                $content_note = $formValues['note_content_to_user'];
                $content_note = str_replace(";", ".", $content_note);
                $content_note = str_replace("--", "-", $content_note);
                $content_note = str_replace("___", "\n", $content_note);

                $stmt = $db->query(
                    "INSERT INTO notes (identifier, tablename, user_id, "
                            . "date_note, note_text, coll_id ) VALUES (?,?,?,CURRENT_TIMESTAMP,?,?)", array($res_id,$table,$userIdTypist,$content_note,$coll_id)
                );
            }
            
            $stmt = $db->query("update ".$table." set dest_user = ? where res_id = ?", array($new_dest,$res_id));
            // If new dest was in other roles, get number of views
            $stmt = $db->query(
                "select viewed"
                . " from " . $_SESSION['tablename']['ent_listinstance'] 
                . " where coll_id = ? and res_id = ? and item_type = 'user_id' and item_id = ?", array($coll_id,$res_id,$new_dest)
            );
            $res = $stmt->fetchObject();
            $viewed = $res->viewed;
            $new_difflist['dest']['users'][0]['viewed'] = (integer)$viewed;
        }
        
        // Update destination if needed
        if ($entityId) {
            if ($formValues['note_content_to_dep'] != '') {
                //Add notes
                $userIdTypist = $_SESSION['user']['UserId'];
                $content_note = $formValues['note_content_to_dep'];
                $content_note = str_replace(";", ".", $content_note);
                $content_note = str_replace("--", "-", $content_note);
                $content_note = str_replace("___", "\n", $content_note);
                
                $stmt = $db->query(
                    "INSERT INTO notes (identifier, tablename, user_id, "
                            . "date_note, note_text, coll_id ) VALUES (?,?,?,CURRENT_TIMESTAMP,?,?)", array($res_id, $table, $userIdTypist, $content_note, $coll_id)
                );
            }
            $stmt = $db->query("update ".$table." set destination = ? where res_id = ?", array($entityId, $res_id)); 
        }

        $new_difflist = $diffList->list_difflist_roles_to_keep($res_id, $coll_id, $new_difflist['difflist_type'], $new_difflist);
        
        // If feature activated, put old dest in copy
        if ($_SESSION['features']['dest_to_copy_during_redirection'] == 'true') {
            // Get old dest
            $stmt = $db->query(
                "select * "
                . " from " . $_SESSION['tablename']['ent_listinstance'] 
                . " where coll_id = ? and res_id = ? and item_type = 'user_id' and item_mode = 'dest'", array($coll_id, $res_id)
            );

            $old_dest = $stmt->fetchObject();
            
            if ($old_dest) {
                // try to find old dest in copies already
                $found = false;
                if (isset($new_difflist['copy']['users'])) {
                    for ($ci=0; $ci<count($new_difflist['copy']['users']);$ci++) {
                    
                        // If in copies before, add number of views as dest to number of views as copy
    			if ($new_difflist['copy']['users'][$ci]['user_id'] == $old_dest->item_id) {
                            $found = true;
                            $new_difflist['copy']['users'][$ci]['viewed'] = 
                                $new_difflist['copy']['users'][$ci]['viewed'] + (integer)$old_dest->viewed;
                            break;
                        }
                    }
                    
                    //re-built session without dest in copy
                    $tab=array();
                    for ($ci=0; $ci<count($new_difflist['copy']['users']);$ci++) {
                        if ($new_difflist['copy']['users'][$ci]['user_id'] != $new_dest) {
                        array_push(
                            $tab, 
                            array(
    				'user_id' => $new_difflist['copy']['users'][$ci]['user_id'], 
    				'viewed' => (integer)$new_difflist['copy']['users'][$ci]['viewed'],
    				'visible' => 'Y',
    				'difflist_type' => $new_difflist['copy']['users'][$ci]['viewed']
                            )
                        );
                        }
                    }
                    $new_difflist['copy']['users']=$tab;
                } else {
                    if (!isset($new_difflist['copy'])) {
                        $new_difflist['copy'] = array();
                    }
                    $new_difflist['copy']['users'] = array();
                }
                
                if (!$found) {
                    array_push(
                        $new_difflist['copy']['users'], 
                        array(
                        'user_id'       => $old_dest->item_id, 
                        'viewed'        => (integer)$old_dest->viewed,
                        'visible'       => 'Y',
                        'difflist_type' => $new_difflist['difflist_type']
                        )
                    );
                }
            }
        }

        // Save listinstance
        $diffList->save_listinstance(
            $new_difflist, 
            $new_difflist['difflist_type'],
            $coll_id, 
            $res_id, 
            $_SESSION['user']['UserId'],
            $_SESSION['user']['primaryentity']['id']
        );           
    }
    
    // Pb with action chain : main action page is saved after this. 
    //   if process, $_SESSION['process']['diff_list'] will override this one
    $_SESSION['ListDiffFromRedirect'] = true;
    $_SESSION['process']['diff_list'] = $new_difflist;
    $_SESSION['action_error'] = $message;
    return array('result' => implode('#', $arr_id), 'history_msg' => $message);

    // OLD SCRIPT
    $list = new diffusion_list();
    $arr_list = '';

    for ($j=0; $j<count($values_form); $j++) {

        $queryEntityLabel = "SELECT entity_label FROM entities WHERE entity_id=?";
        $stmt = $db->query($queryEntityLabel, array($values_form[$j]['VALUE']));
        while ($entityLabel = $stmt->fetchObject()) {
            $zeEntityLabel = $entityLabel->entity_label;
        }
        $msg = _TO." : ".$zeEntityLabel." (".$values_form[$j]['VALUE'].")";
        if ($values_form[$j]['ID'] == "department") {
            for ($i=0; $i < count($arr_id); $i++) {

                $arr_list .= $arr_id[$i].'#';
                $stmt = $db->query("update ".$table." set destination = ? where res_id = ?", array($values_form[$j]['VALUE'],$arr_id[$i]));
                if (isset($_SESSION['redirect']['diff_list']['dest']['users'][0]['user_id']) && !empty($_SESSION['redirect']['diff_list']['dest']['users'][0]['user_id'])) {

                    $stmt = $db->query("update ".$table." set dest_user = ? where res_id = ?", array($_SESSION['redirect']['diff_list']['dest']['user_id'],$arr_id[$i]));
                }
                $newDestViewed = 0;
                // Récupère le nombre de fois où le futur destinataire principal a vu le document
                $stmt = $db->query("select viewed from ".$_SESSION['tablename']['ent_listinstance']." where coll_id = ? and res_id = ? and item_type = 'user_id' and item_id = ?", array($coll_id,$arr_id[$i],$_SESSION['redirect']['diff_list']['dest']['users'][0]['user_id']));
                //$db->show();
                $res = $stmt->fetchObject();
                if ($res->viewed <> "") {

                    $_SESSION['redirect']['diff_list']['dest']['users'][0]['viewed'] = $res->viewed;
                    $newDestViewed = $res->viewed;
                }
                if ($_SESSION['features']['dest_to_copy_during_redirection'] == 'true') {

                    $lastDestViewed = 0;
                    // Récupère le nombre de fois où l'ancien destinataire principal a vu le document
                    $stmt = $db->query("select viewed from ".$_SESSION['tablename']['ent_listinstance']." where coll_id = ? and res_id = ? and item_type = 'user_id' and item_mode = 'dest'", array($coll_id,$arr_id[$i]));
 
                    $res = $stmt->fetchObject();
                    if ($res->viewed <> "") {

                        $lastDestViewed = $res->viewed;
                    }
                    for ($cptCopy=0;$cptCopy<count($_SESSION['redirect']['diff_list']['copy']['users']);$cptCopy++) {

                        if ($_SESSION['redirect']['diff_list']['copy']['users'][$cptCopy]['user_id'] == $_SESSION['user']['UserId']) {

                            $_SESSION['redirect']['diff_list']['copy']['users'][$cptCopy]['viewed'] = $lastDestViewed;
                        }
                    }
                    array_push($_SESSION['redirect']['diff_list']['copy']['users'], array('user_id' => $_SESSION['user']['UserId'], 'lastname' => $_SESSION['user']['LastName'], 'firstname' => $_SESSION['user']['FirstName'], 'entity_id' => $_SESSION['user']['primaryentity']['id'], 'viewed' => $lastDestViewed));
                }
                $params = array('mode'=> 'listinstance', 'table' => $_SESSION['tablename']['ent_listinstance'], 'coll_id' => $coll_id, 'res_id' => $arr_id[$i], 'user_id' => $_SESSION['user']['UserId'], 'concat_list' => true);

                $list->load_list_db($_SESSION['redirect']['diff_list'], $params);
            }
            $_SESSION['action_error'] = _REDIRECT_TO_DEP_OK;

            return array('result' => $arr_list, 'history_msg' => $msg );
        
        } elseif ($values_form[$j]['ID'] == "user") {
            
            for ($i=0;$i<count($arr_id);$i++) {
                // Update listinstance
                $difflist['dest'] = array();
                $difflist['copy'] = array();
                $difflist['copy']['users'] = array();
                $difflist['copy']['entities'] = array();
                $difflist['dest']['users'][0]['user_id'] = $values_form[$j]['VALUE'];
                $arr_list .= $arr_id[$i].'#';
                // Récupère le nombre de fois où le futur destinataire principal a vu le document
                $stmt = $db->query("select viewed from ".$_SESSION['tablename']['ent_listinstance']." where coll_id = ? and res_id = ? and item_type = 'user_id' and item_id = ?", array($coll_id,$arr_id[$i],$difflist['dest']['users'][0]['user_id']));
                //$db->show();
                $res = $stmt->fetchObject();
                $newDestViewed = 0;
                if ($res->viewed <> "") {

                    $difflist['dest']['users'][0]['viewed'] = $res->viewed;
                    $newDestViewed = $res->viewed;
                }
                // Récupère le nombre de fois où l'ancien destinataire principal a vu le document
                $stmt = $db->query("select viewed from ".$_SESSION['tablename']['ent_listinstance']." where coll_id = ? and res_id = ? and item_type = 'user_id' and item_mode = 'dest'", array($coll_id,$arr_id[$i]));

                $res = $stmt->fetchObject();
                $lastDestViewed = 0;
                if ($res->viewed <> "") {

                    $lastDestViewed = $res->viewed;
                }
                // Update dest_user in res table
                $stmt = $db->query("update ".$table." set dest_user = ? where res_id = ?", array($values_form[$j]['VALUE'],$arr_id[$i]));
                $list->set_main_dest($values_form[$j]['VALUE'], $coll_id, $arr_id[$i], 'DOC', 'user_id', $newDestViewed);
                if ($_SESSION['features']['dest_to_copy_during_redirection'] == 'true') {

                    array_push($difflist['copy']['users'], array('user_id' => $_SESSION['user']['UserId'], 'lastname' => $_SESSION['user']['LastName'], 'firstname' => $_SESSION['user']['FirstName'], 'entity_id' => $_SESSION['user']['primaryentity']['id'], 'viewed' => $lastDestViewed));
                }
                $params = array('mode'=> 'listinstance', 'table' => $_SESSION['tablename']['ent_listinstance'], 'coll_id' => $coll_id, 'res_id' => $arr_id[$i], 'user_id' => $_SESSION['user']['UserId'], 'concat_list' => true);
                $list->load_list_db($difflist, $params);
            }
            $_SESSION['action_error'] = _REDIRECT_TO_USER_OK;
            return array('result' => $arr_list, 'history_msg' => $msg);
        }
    }
    return false;
}

function manage_unlock($arr_id, $history, $id_action, $label_action, $status, $coll_id, $table)
{
    $db = new Database();
    for ($i=0; $i<count($arr_id);$i++) {

        $req = $db->query("update ".$table. " set video_user = '', video_time = 0 where res_id = ?", array($arr_id[$i]));

        if (!$req) {

            $_SESSION['action_error'] = _SQL_ERROR;
            return false;
        }
    }
    return true;
}

 /**
 * Get the value of a given field in the values returned by the form
 *
 * @param $values Array Values of the form to check
 * @param $field String the field
 * @return String the value, false if the field is not found
 **/
function get_value_fields($values, $field)
{
    for ($i=0; $i<count($values);$i++) {

        if ($values[$i]['ID'] == $field) {

            return  $values[$i]['VALUE'];
        }
    }
    return false;
}

?>
