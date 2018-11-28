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
* @brief   Action : simple confirm
*
* Open a modal box to confirm a status modification. Used by the core (manage_action.php page).
*
* @file
* @date $date$
* @version $Revision$
* @ingroup apps
*/

/**
* $confirm  bool false
*/
$confirm    = false;
$etapes     = array('form');
$frm_width  = '285px';
$frm_height = 'auto';

function get_form_txt($values, $path_manage_action, $id_action, $table, $module, $coll_id, $mode)
{
    $labelAction = '';
    if ($id_action <> '') {
        $resAction   = \Action\models\ActionModel::getById(['id' => $id_action]);
        $labelAction = functions::show_string($resAction['label_action']);
    }
    
    $values_str = '';
    if (empty($_SESSION['stockCheckbox'])) {
        for ($i=0; $i < count($values); $i++) {
            $values_str .= $values[$i].', ';
        }
    } else {
        for ($i=0; $i < count($_SESSION['stockCheckbox']); $i++) {
            $values_str .= $_SESSION['stockCheckbox'][$i].', ';
        }
    }
    $values_str = preg_replace('/, $/', '', $values_str);
  
    $templates = array();
    $destination = \Resource\models\ResModel::get(['select' => ['destination'], 'where' => ['res_id in (?)'], 'data' => [explode(", ", $values_str)]]);

    if ($destination <> '') {
        $aDestination = [];
        foreach ($destination as $value) {
            $aDestination[] = $value['destination'];
        }
        $templates = \Template\models\TemplateModel::getByEntity(['select' => ['t.*'], 'entities' => $aDestination]);
    } else {
        $templates = \Template\models\TemplateModel::get();
    }
    $frm_str .='<center style="font-size:15px;">'._ACTION_CONFIRM.'<br/><br/><b>'.$labelAction.' ?</b></center><br/>';
    if ($_SESSION['current_basket']['id'] != 'IndexingBasket') {
        $frm_str .='<b>'._PROCESS_NOTES.':</b><br/>';
        $frm_str .= '<select name="templateNotes" id="templateNotes" style="width:98%;margin-bottom: 10px;background-color: White;border: 1px solid #999;color: #666;text-align: left;" '
                    . 'onchange="addTemplateToNote($(\'templateNotes\').value, \''
                    . $_SESSION['config']['businessappurl'] . 'index.php?display=true'
                    . '&module=templates&page=templates_ajax_content_for_notes\');document.getElementById(\'notes\').focus();">';
        $frm_str .= '<option value="">' . _SELECT_NOTE_TEMPLATE . '</option>';
        foreach ($templates as $value) {
            if ($value['template_type'] == 'TXT' && ($value['template_target'] == 'notes' || $value['template_target'] == '')) {
                $frm_str .= '<option value="';
                $frm_str .= $value['template_id'];
                $frm_str .= '">';
                $frm_str .= $value['template_label'];
            }
            $frm_str .= '</option>';
        }
        $frm_str .= '</select><br />';

        $frm_str .= '<textarea placeholder="motif de la clôture (optionnel) ..." style="width:98%;height:60px;resize:none;" name="notes"  id="notes" onblur="document.getElementById(\'note_content_to_users\').value=document.getElementById(\'notes\').value;"></textarea>';
    }
    $frm_str .='<div id="form2" style="border:none;">';
    $frm_str .= '<form name="frm_redirect_dep" id="frm_redirect_dep" method="post" class="forms" action="#">';
    $frm_str .= '<input type="hidden" name="chosen_action" id="chosen_action" value="end_action" />';
    $frm_str .= '<input type="hidden" name="note_content_to_users" id="note_content_to_users" />';
    $frm_str .='</form>';
    $frm_str .='</div>';

    $frm_str .='<div align="center">';
    $frm_str .=' <input type="button" name="redirect_dep" value="'._VALIDATE.'" id="redirect_dep" class="button" onclick="valid_action_form( \'frm_redirect_dep\', \''.$path_manage_action.'\', \''. $id_action.'\', \''.$values_str.'\', \''.$table.'\', \''.$module.'\', \''.$coll_id.'\', \''.$mode.'\');" />';
    $frm_str .=' <input type="button" name="cancel" id="cancel" class="button"  value="'._CANCEL.'" onclick="pile_actions.action_pop();actions_status.action_pop();destroyModal(\'modal_'.$id_action.'\');"/>';
    $frm_str .='</div>';
    return addslashes($frm_str);
}

function check_form($form_id, $values)
{
    return true;
}

function manage_form($arr_id, $history, $id_action, $label_action, $status, $coll_id, $table, $values_form)
{
    if (empty($values_form) || count($arr_id) < 1) {
        return false;
    }
    
    $formValues = array();
    for ($i=0; $i<count($values_form); $i++) {
        $formValue       = $values_form[$i];
        $id              = $formValue['ID'];
        $value           = $formValue['VALUE'];
        $formValues[$id] = $value;
    }
    
    $_SESSION['action_error'] = '';
    $result = '';

    foreach ($arr_id as $res_id) {
        $result .= $res_id.'#';
        \Resource\models\ResModel::updateExt(['set' => ['closing_date' => 'CURRENT_TIMESTAMP'], 'where' => ['res_id = ?'], 'data' => [$res_id]]);
        
        # save note
        if ($formValues['note_content_to_users'] != '') {
            \Note\models\NoteModel::create(['identifier' => $res_id, 'tablename' => 'res_letterbox', 'user_id' => $_SESSION['user']['UserId'], 'coll_id' => 'letterbox_coll', 'note_text' => $formValues['note_content_to_users']]);
        }

        if (\SrcCore\models\CurlModel::isEnabled(['curlCallId' => 'closeResource'])) {
            $bodyData = [];
            $config = \SrcCore\models\CurlModel::getConfigByCallId(['curlCallId' => 'closeResource']);
            $configResource = \SrcCore\models\CurlModel::getConfigByCallId(['curlCallId' => 'sendResourceToExternalApplication']);

            $resource = \Resource\models\ResModel::getOnView(['select' => ['doc_' . $configResource['return']['value']], 'where' => ['res_id = ?'], 'data' => [$res_id]]);

            if (!empty($resource[0]['doc_' . $configResource['return']['value']])) {
                if (!empty($config['inObject'])) {
                    $multipleObject = true;

                    foreach ($config['objects'] as $object) {
                        $select = [];
                        $tmpBodyData = [];
                        foreach ($object['rawData'] as $value) {
                            if ($value == $configResource['return']['value']) {
                                $select[] = 'doc_' . $configResource['return']['value'];
                            } elseif ($value != 'note') {
                                $select[] = $value;
                            }
                        }

                        $document = \Resource\models\ResModel::getOnView(['select' => $select, 'where' => ['res_id = ?'], 'data' => [$res_id]]);
                        if (!empty($document[0])) {
                            foreach ($object['rawData'] as $key => $value) {
                                if ($value == 'note') {
                                    $tmpBodyData[$key] = $formValues['note_content_to_users'];
                                } elseif ($value == $configResource['return']['value']) {
                                    $tmpBodyData[$key] = $document[0]['doc_' . $value];
                                } else {
                                    $tmpBodyData[$key] = $document[0][$value];
                                }
                            }
                        }

                        if (!empty($object['data'])) {
                            $tmpBodyData = array_merge($tmpBodyData, $object['data']);
                        }

                        $bodyData[$object['name']] = $tmpBodyData;
                    }
                } else {
                    $multipleObject = false;

                    $select = [];
                    foreach ($config['rawData'] as $value) {
                        if ($value != 'note') {
                            $select[] = $value;
                        }
                    }

                    $document = \Resource\models\ResModel::getOnView(['select' => $select, 'where' => ['res_id = ?'], 'data' => [$res_id]]);
                    if (!empty($document[0])) {
                        foreach ($config['rawData'] as $key => $value) {
                            if ($value == 'note') {
                                $bodyData[$key] = $formValues['note_content_to_users'];
                            } else {
                                $bodyData[$key] = $document[0][$value];
                            }
                        }

                    }

                    if (!empty($config['data'])) {
                        $bodyData = array_merge($bodyData, $config['data']);
                    }
                }

                \SrcCore\models\CurlModel::exec(['curlCallId' => 'closeResource', 'bodyData' => $bodyData, 'multipleObject' => $multipleObject, 'noAuth' => true]);
            }
        }
    }

    return array('result' => $result, 'history_msg' => '');
}
