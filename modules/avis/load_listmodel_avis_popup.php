<?php
/**
* File : load_listmodel_avis.php.
*
* Script called by an ajax object load list avis during
* avis workflow
*
* @version 1
*
* @since 01/2016
*
* @license GPL v3
* @author  Alex Orluc  <dev@maarch.org>
*/
require_once 'modules/entities/class/class_manage_listdiff.php';
require_once 'modules'.DIRECTORY_SEPARATOR.'avis'.DIRECTORY_SEPARATOR
            .'class'.DIRECTORY_SEPARATOR
            .'avis_controler.php';

    $db = new Database();
    $core = new core_tools();
    $core->load_lang();
    $diffList = new diffusion_list();

    $objectType = $_REQUEST['objectType'];
    $objectId = $_REQUEST['objectId'];
    $origin = 'avis';

    // Get listmodel_parameters
    $_SESSION[$origin]['difflist_type'] = $diffList->get_difflist_type($objectType);

    if ($objectId != '') {
        $_SESSION[$origin]['difflist_object']['object_id'] = $objectId;
        if ($objectType == 'entity_id') {
            $stmt = $db->query('select entity_label from entities where entity_id = ?', array($objectId));
            $res = $stmt->fetchObject();
            if ($res->entity_label != '') {
                $_SESSION[$origin]['difflist_object']['object_label'] = $res->entity_label;
            }
        }
    }

    // Fill session with listmodel
    $_SESSION[$origin]['diff_list'] = $diffList->get_listmodel($objectType, $objectId);
    $_SESSION[$origin]['diff_list']['difflist_type'] = $_SESSION[$origin]['diff_list']['object_type'];
    $roles = $diffList->list_difflist_roles();
    $circuit = $_SESSION[$origin]['diff_list'];
    if (!isset($circuit['avis']['users'])) {
        echo "{status : 1, error_txt : 'Modèle inexistant'}";
        exit();
    }
    if ($circuit['object_type'] == 'AVIS_CIRCUIT') {
        $id_tab = 'tab_avisSetWorkflowPopup';
        $id_form = 'form_avisSetWorkflowPopup';
    }

    $content = '';

    $content .= '<thead><tr>';
    $content .= '<th style="width:40%;" align="left" valign="bottom"><span>Avis</span></th>';
    $content .= '<th style="width:5%;"></th>';
    $content .= '<th style="width:5%;"></th>';
    $content .= '<th style="width:5%;"></th>';
    $content .= '<th style="width:5%;"></th>';
    $content .= '<th style="width:45%;" align="left" valign="bottom"><span>Consigne</span></th>';
    $content .= '<th style="width:0;display:none" align="left" valign="bottom"></th>';
    $content .= '<th style="width:0;display:none" align="center" valign="bottom"></th>';

    $content .= '</tr></thead>';
    $content .= '<tbody>';
    $color = '';
    $avis = new avis_controler();
    if (isset($circuit['avis']['users'])) {
        foreach ($circuit['avis']['users'] as $seq => $step) {
            if ($color == ' class="col"') {
                $color = '';
            } else {
                $color = ' class="col"';
            }

            $content .= '<tr '.$color.'>';
            $content .= '<td>';

            $content .= '<span id="avisPopup_rank_'.$seq.'"> <span class="nbResZero" style="font-weight:bold;opacity:0.5;">'.($seq + 1).'</span> </span>';
            $content .= '<select id="avisPopup_'.$seq.'" name="avisPopup_'.$seq.'" >';
            $content .= '<option value="" >Sélectionnez un utilisateur</option>';

            $tab_userentities = $avis->getEntityAvis();
            $tab_users = $avis->getUsersAvis();

            /* Order by parent entity **/
            foreach ($tab_userentities as $key => $value) {
                $content .= '<optgroup label="'.$tab_userentities[$key]['entity_id'].'">';
                foreach ($tab_users as $user) {
                    if ($tab_userentities[$key]['entity_id'] == $user['entity_id']) {
                        $selected = ' ';
                        if ($user['id'] == $step['user_id']) {
                            $selected = ' selected';
                        }
                        $content .= '<option value="'.$user['id'].'" '.$selected.'>'.$user['lastname'].', '.$user['firstname'].'</option>';
                    }
                }
                $content .= '</optgroup>';
            }
            $content .= '</select>';
            $content .= '<span id="lastAvisPopup_'.$seq.'">';
            if (empty($circuit['sign']['users']) && $seq == count($circuit['avis']['users']) - 1) {
                $content .= ' <i title="'._LAST_AVIS.'" style="color : #F99830" class="fa fa-certificate fa-lg fa-fw"></i>';
            }
            $content .= '</span></td>';

            $up = ' style="visibility:visible"';
            $down = ' style="visibility:visible"';
            if ($seq == count($circuit['avis']['users']) - 1) {
                $add = ' style="visibility:visible"';
                $down = ' style="visibility:hidden"';
            } else {
                $add = ' style="visibility:hidden"';
            }
            if ($seq == 0) {
                $up = ' style="visibility:hidden"';
            }

            $content .= '<td><a href="javascript://"  '.$down.' id="avisPopup_down_'.$seq.'" name="avisPopup_down_'.$seq.'" onclick="deplacerLigneAvisPopup(this.parentNode.parentNode.rowIndex, this.parentNode.parentNode.rowIndex+2,\''.$id_tab.'\')" ><i class="fa fa-arrow-down fa-2x"></i></a></td>';
            $content .= '<td><a href="javascript://"   '.$up.' id="avisPopup_up_'.$seq.'" name="avisPopup_up_'.$seq.'" onclick="deplacerLigneAvisPopup(this.parentNode.parentNode.rowIndex, this.parentNode.parentNode.rowIndex-1,\''.$id_tab.'\')" ><i class="fa fa-arrow-up fa-2x"></i></a></td>';
            $content .= '<td><a href="javascript://" onclick="delRowAvisPopup(this.parentNode.parentNode.rowIndex,\''.$id_tab.'\')" id="avisPopup_suppr_'.$j.'" name="avisPopup_suppr_'.$j.'" style="visibility:visible;" ><i class="fa fa-user-times fa-2x"></i></a></td>';
            $content .= '<td><a href="javascript://" '.$add.'  id="avisPopup_add_'.$seq.'" name="avisPopup_add_'.$seq.'" onclick="addRowAvisPopup(\''.$id_tab.'\')" ><i class="fa fa-user-plus fa-2x"></i></a></td>';
            $content .= '<td><input type="text" id="avisPopup_consigne_'.$seq.'" name="avisPopup_consigne_'.$seq.'" value="'.$step['process_comment'].'" onmouseover="setTitle(this);" style="width:95%;"/></td>';
            $content .= '<td style="display:none"><input type="hidden" value="'.$step['process_date'].'" id="avisPopup_date_'.$seq.'" name="avisPopup_date_'.$seq.'"/></td>';
            $content .= '<td style="display:none"><input type="checkbox" style="visibility:hidden" id="avisPopup_isSign_'.$seq.'" name="avisPopup_isSign_'.$seq.'" /></td>';
            $content .= '<td><i class="fa fa-plus fa-lg" title="Nouvel utilisateur ajouté"></i></td>';
            $content .= '</tr>';
        }
    }

    $content .= '</tbody>';

    echo "{status : 0, div_content : '".addslashes($content.'<br>')."'}";
    exit();