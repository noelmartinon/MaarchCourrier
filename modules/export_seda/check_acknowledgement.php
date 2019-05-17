<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Acknowledgement letter
 * @author dev@maarch.org
 * @ingroup export seda
 */

/**
 * $confirm  bool true
 */
$confirm = true;

/**
 * $etapes  array Contains only one etap, the status modification
 */

$confirm = false;
$etapes = array('form');
$frm_width='285px';
$frm_height = 'auto';

function get_form_txt($values, $path_manage_action, $id_action, $table, $module, $coll_id, $mode)
{
    $res_id = $values[0];
    $db = new Database();
    $labelAction = '';
    if ($id_action <> '') {
        $stmt = $db->query("SELECT label_action FROM actions WHERE id = ?", array($id_action));
        $resAction = $stmt->fetchObject();
        $labelAction = functions::show_string($resAction->label_action);
    }

    $values_str = '';
    if (empty($_SESSION['stockCheckbox'])) {
        for ($i = 0; $i < count($values); $i++) {
            $values_str .= $values[$i] . ', ';
        }
    } else {
        for ($i = 0; $i < count($_SESSION['stockCheckbox']); $i++) {
            $values_str .= $_SESSION['stockCheckbox'][$i] . ', ';
        }
    }
    $path_to_script = $_SESSION['config']['businessappurl']."index.php?display=true&module=export_seda";

    $values_str = preg_replace('/, $/', '', $values_str);
    $frm_str ='<center style="font-size:15px;">'._ACTION_CONFIRM.'<br/><br/><b>'.$labelAction.' ?</b></center><br/>';
    $frm_str .='<div id="form2" style="border:none;">';
    $frm_str .= '<form name="frm_redirect_dep" id="frm_redirect_dep" method="post" class="forms" action="#">';
    $frm_str .= '<input type="hidden" name="chosen_action" id="chosen_action" value="end_action" />';
    $frm_str .= '<input type="hidden" name="resIds" id="resIds" value="'. $values_str .'" />';
    $frm_str .='</form>';
    $frm_str .='</div>';
    $frm_str .='<div align="center">';
    $frm_str .=' <input type="button" name="redirect_dep" value="'._VALIDATE.'" id="redirect_dep" class="button" onclick="actionValidation(\''.$path_to_script.'&page=Ajax_validation&type=acknowledgement&reference='.$values_str.'\', \''. $_SESSION['urlV2Basket']['userId'] .'\', \''. $_SESSION['urlV2Basket']['groupIdSer'] .'\', \''. $_SESSION['urlV2Basket']['basketId'] .'\');" />';
    $frm_str .=' <input type="button" name="cancel" id="cancel" class="button"  value="'._CANCEL.'" onclick="pile_actions.action_pop();actions_status.action_pop();destroyModal(\'modal_'.$id_action.'\');"/>';
    $frm_str .='</div>';

    return addslashes($frm_str);
}
