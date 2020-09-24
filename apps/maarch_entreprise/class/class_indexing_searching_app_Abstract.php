<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   class_indexing_searching_app_Apstract
*
* @author  dev <dev@maarch.org>
* @ingroup apps
*/
abstract class indexing_searching_app_Abstract extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    public function send_criteria_data($param)
    {
        /*list_criteres = Array ("num_courrier" => Array (label => "reference courrier',
                                             parametres => Array ( ...),
                                             type => "text",
                                             ),
                       "date courrier" => array*/
        //    $this->show_array($param);
        $options_criteria_list = '<option id="default" value="">'._CHOOSE_PARAMETERS.'</option>';

        $json_tab = '';
        foreach ($param as $key => $value) {
            $json_tab .= "'".$key."' : {";
            //echo 'key '.$key."<br/>val ";
            //$this->show_array($value);
            if ($value['param']['autocompletion'] || $value['param']['autocompletionCustomField']) {
                $idListByName = $key.'ListByName';
                $autocompleteId = 'ac_'.$key;
                $options_criteria_list .= '<option id="option_'.$key.'" value="'.$value['label'].'" data-load={"id":"'.$key.'","idList":"'.$idListByName.'","autocompleteId":"'.$autocompleteId.'","config":"'.$_SESSION['config']['businessappurl'].'"} > '.$value['label'].'</option>';
            } else {
                $options_criteria_list .= '<option id="option_'.$key.'" value="'.$value['label'].'"> '.$value['label'].'</option>';
            }
            $json_tab .= $this->json_line($key, $value['type'], $value['param']);
            $json_tab .= '}
            ,';
        }
        $json_tab = preg_replace('/,$/', '', $json_tab);

        $tab = array($options_criteria_list, $json_tab);

        return $tab;
    }

    public function json_line($id, $field_type, $param)
    {
        $str = '';
        $init = "'label' : '".addslashes($param['field_label'])."', 'value' :'";
        $end = "'";
        //$hidden = '<input type="hidden" name="meta[]" value="" />';
        if ($field_type == 'input_text') {
            if ($param['autocompletionCustomField']) {
                $str = $init.'<input type="hidden" name="meta[]" value="'.$id.'#'.$id.'#input_text"/>';
                $str .= '<div class="adv_search_field indexing_field" style="width: 200px;>';
                $str .= '<span style="position:relative;">';
                $str .= '<div class="typeahead__container">';
                $str .= '<div class="typeahead__field">';
                $str .= '<span class="typeahead__query">';
                $str .= '<input name="'.$id.'" type="text" id="'.$id.'" autocomplete="off" placeholder="'._CONTACTS_USERS_SEARCH.'" title="'._CONTACTS_USERS_SEARCH.'" />';
                $str .= '<input type="hidden" name="meta[]" value="'.$id.'#'.$id.'#input_text" />';
                $str .= '</span>';
                $str .= '</div>';
                $str .= '</div>';
                $str .= '</span>';
                $str .= '<input type="hidden" name="'.$id.'_id" id="'.$id.'_id" />';
                $str .= '<input type="hidden" name="'.$id.'_type" id="'.$id.'_type" />';
                $str .= '</div>'.$end;
            } elseif ($param['autocompletion']) {
                $idListByName = $id.'ListByName';
                $autocompleteId = 'ac_'.$id;
                $str = $init.'<input type="hidden" name="meta[]" value="'.$id.'#'.$id.'#input_text"/>';
                $str .= '<input name="'.$id.'"  id="'.$id.'" type="text" '.$param['other'].' value="" onkeyup="erase_contact_external_id('."\'".$id."\'".','."\'".$autocompleteId."\'".')"/>';
                $str .= '<div id="'.$idListByName.'" class="autocomplete"></div>';
                $str .= '<script type="text/javascript">';
                $str .= 'initList_hidden_input("'.$id.'", "'.$idListByName.'","'.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=users_list_by_name_search", "what", "2", "'.$autocompleteId.'");</script>';
                $str .= '<input id="'.$autocompleteId.'" name="'.$autocompleteId.'" type="hidden" />'.$end;
            } else {
                $str = $init.'<input type="hidden" name="meta[]" value="'.$id.'#'.$id.'#input_text" /><input name="'.$id.'"  id="'.$id.'" type="text" '.$param['other'].' value="" />'.$end;
            }
        } elseif ($field_type == 'textarea') {
            $str = $init.'<input type="hidden" name="meta[]" value="'.$id.'#'.$id.'#textarea" /><textarea name="'.$id.'"  id="'.$id.'" '.$param['other'].' rows="2" style="display:block;width:530px;"></textarea>'.$end;
        } elseif ($field_type == 'date_range') {
            $str = $init.addslashes(_SINCE).' : <input type="text" name="'.$param['id1'].'" id="'.$param['id1'].'" value="" onclick="showCalender(this);" /> '
            .addslashes(_FOR).' : <input type="text" name="'.$param['id2'].'" id="'.$param['id2'].'" value=""  onclick="showCalender(this);" />';
            $str .= '<input type="hidden" name="meta[]" value="'.$id.'#'.$param['id1'].','.$param['id2'].'#date_range" />'.$end;
        } elseif ($field_type == 'num_range') {
            $str = $init.addslashes(_NUM_BETWEEN).' : <input type="text" name="'.$param['id1'].'" id="'.$param['id1'].'" value=""/ > '
            .addslashes(_AND).' : <input type="text" name="'.$param['id2'].'" id="'.$param['id2'].'" value="" / >';
            $str .= '<input type="hidden" name="meta[]" value="'.$id.'#'.$param['id1'].','.$param['id2'].'#num_range" />'.$end;
        } elseif ($field_type == 'select_simple') {
            $str = $init.'<select name="'.$id.'" id="'.$id.'">';
            if (isset($param['default_label']) && !empty($param['default_label'])) {
                $str .= '<option value="">'.$param['default_label'].'</option>';
            }
            for ($i = 0; $i < count($param['options']); ++$i) {
                $str .= '<option value="'.addslashes($param['options'][$i]['VALUE']).'" alt="'.addslashes($param['options'][$i]['LABEL']).'" title="'.addslashes($param['options'][$i]['LABEL']).'">'.addslashes($param['options'][$i]['LABEL']).'</option>';
            }
            $str .= '</select>';
            $str .= '<input type="hidden" name="meta[]" value="'.$id.'#'.$id.'#select_simple" />'.$end;
        } elseif ($field_type == 'select_multiple') {
            $str .= '<tr><td colspan="3">'.$param['label_title'].' :</td></tr>';
            $str .= '<tr>';
            $str .= '<td width="150" align="left">';
            $str .= '<select name="'.$param['id'].'_available[]" id="'.$param['id'].'_available" size="10" ondblclick="moveclick_ext('." '".$param['id']."_available', '".$param['id']."_chosen'".');" multiple="multiple" >';
            for ($i = 0; $i < count($param['options']); ++$i) {
                $str .= '<option value="'.$param['options'][$i]['VALUE'].'"  alt="'.addslashes($param['options'][$i]['LABEL'])
                                .'" title="'.addslashes($param['options'][$i]['LABEL']).'" ';
                if (isset($param['options'][$i]['CLASS'])) {
                    $str .= ' class="'.$param['options'][$i]['CLASS'].'" ';
                }
                $str .= '>'.$param['options'][$i]['LABEL'].'</option>';
            }
            $str .= '</select>';
            $str .= "<br/><em><a href=\"javascript:selectall_ext( '".$param['id']."_available');\" >"._SELECT_ALL.'</a></em>';
            $str .= '</td>';
            $str .= '<td width="135" align="center">';
            $str .= '<input type="button" class="button" value="'._ADD.'" onclick="Move_ext('."'".$param['id']."_available', '".$param['id']."_chosen'".');" /><br />';
            $str .= '<br /><input type="button" class="button" value="'._REMOVE.'" onclick="Move_ext('." '".$param['id']."_chosen', '".$param['id']."_available'".');" />';
            $str .= '</td>';
            $str .= '<td width="150" align="left">';
            $str .= '<select name="'.$param['id'].'_chosen[]" id="'.$param['id'].'_chosen" size="10" ondblclick="moveclick_ext('." '".$param['id']."_chosen', '".$param['id']."_available'".');" multiple="multiple" " >';
            $str .= '</select>';
            $str .= "<br/><em><a href=\"javascript:selectall_ext( '".$param['id']."_chosen');\" >"._SELECT_ALL.'</a></em>';
            $str .= '</td>';
            $str .= '</tr>';
            $str = addslashes($str);
            $str = $init.'<table align="center" border="0" width="100%" >'.$str.'<input type="hidden" name="meta[]" value="'.$id.'#'.$param['id'].'_chosen#select_multiple" /></table>'.$end;
        } elseif ($field_type == 'checkbox') {
            $str = $init.'<table align="center" border="0" width="100%" >';

            $tmp_ids = '';
            for ($i = 0; $i < count($param['checkbox_data']); $i = $i + 2) {
                $tmp_ids .= $param['checkbox_data'][$i]['ID'].',';
                $str .= '<tr>';
                if (isset($param['checkbox_data'][$i + 1]['ID'])) {
                    $tmp_ids .= $param['checkbox_data'][$i + 1]['ID'].',';
                    $str .= '<td><input type="checkbox" class="check" name="'.$param['checkbox_data'][$i]['ID'].'" id="'.$param['checkbox_data'][$i]['ID'].'" value="'.addslashes($param['checkbox_data'][$i]['VALUE']).'" />'.addslashes($param['checkbox_data'][$i]['LABEL']).'</td>';
                    $str .= '<td><input type="checkbox"  class="check" name="'.$param['checkbox_data'][$i + 1]['ID'].'" id="'.$param['checkbox_data'][$i + 1]['ID'].'" value="'.addslashes($param['checkbox_data'][$i + 1]['VALUE']).'" />'.addslashes($param['checkbox_data'][$i + 1]['LABEL']).'</td>';
                } else {
                    $str .= '<td colspan="2"><input type="checkbox"  class="check" name="'.$param['checkbox_data'][$i]['ID'].'" id="'.$param['checkbox_data'][$i]['ID'].'" value="'.addslashes($param['checkbox_data'][$i]['VALUE']).'" />'.addslashes($param['checkbox_data'][$i]['LABEL']).'</td>';
                }
                $str .= '</tr>';
            }
            $tmp_ids = preg_replace('/,$/', '', $tmp_ids);
            $str .= '</table>';
            $str .= '<input type="hidden" name="meta[]" value="'.$id.'#'.$tmp_ids.'#checkbox" />'.$end;
        } elseif ($field_type == 'address') {
            $str = $init.'<input type="hidden" name="meta[]" value="'.$id.'#'.$param['address_data']['NUM']['ID'].','.$param['address_data']['ROAD']['ID'].','.$param['address_data']['CP']['ID'].','.$param['address_data']['CITY']['ID'].','.$param['address_data']['DISTRICTS']['ID'].'#address" />';
            $str .= '<table align="center" border="0" width="100%" >';
            $str .= '<tr>';
            $str .= '<td>'.$param['address_data']['NUM']['LABEL'].'</td><td><input type="text" name="'.$param['address_data']['NUM']['ID'].'" id="'.$param['address_data']['NUM']['ID'].'" class="small"/></td>';
            $str .= '<td>'.$param['address_data']['ROAD']['LABEL'].'</td><td><input type="text" name="'.$param['address_data']['ROAD']['ID'].'" id="'.$param['address_data']['ROAD']['ID'].'" /></td>';
            $str .= '</tr>';
            $str .= '<tr>';
            $str .= '<td>'.$param['address_data']['CP']['LABEL'].'</td><td><input type="text" name="'.$param['address_data']['CP']['ID'].'" id="'.$param['address_data']['CP']['ID'].'" class="medium" maxlength="5"/></td>';
            $str .= '<td>'.$param['address_data']['CITY']['LABEL'].'</td><td><input type="text" name="'.$param['address_data']['CITY']['ID'].'" id="'.$param['address_data']['CITY']['ID'].'" /></td>';
            $str .= '</tr>';
            if (isset($param['address_data']['DISTRICTS'])) {
                $str .= '<tr>';
                $str .= '<td>'.$param['address_data']['DISTRICTS']['LABEL'].'</td><td colspan="3">';
                $str .= '<select name="'.$param['address_data']['DISTRICTS']['ID'].'" id="'.$param['address_data']['DISTRICTS']['ID'].'">';
                $str .= '<option value="">'.$param['address_data']['DISTRICTS']['default_label'].'</option>';
                for ($i = 0; $i < count($param['address_data']['DISTRICTS']['options']); ++$i) {
                    $str .= '<option value="'.$param['address_data']['DISTRICTS']['options'][$i]['VALUE'].'" >'.$param['address_data']['DISTRICTS']['options'][$i]['LABEL'].'</option>';
                }
                $str .= '</select>';
                $str .= '</td>';
                $str .= '</tr>';
            }
            $str .= '</table>'.$end;
        } elseif ($field_type == 'simple_list_or_input_text') {
            // td open in the showing function (js)
            $str .= '<input type="hidden" name="meta[]" value="'.$id.'#select_'.$param['id'].',input_'.$param['id'].'#simple_list_or_input_text" />';
            $str .= '<select name="select_'.$param['id'].'" id="select_'.$param['id'].'" onchange="start_action_list('."'".'div_'.$param['id']."', 'select_".$param['id']."', this.selectedIndex".')">';
            $str .= '<option value="">'.$param['default_label_select'].'</option>';
            $str .= '<option value="SHOW_DATA">'.$param['label_define_option'].'</option>';
            for ($i = 0; $i < count($param['options']); ++$i) {
                $str .= '<option value="'.addslashes($param['options'][$i]['VALUE']).'">'.addslashes($param['options'][$i]['LABEL']).'</option>';
            }
            $str .= '</select>';
            $str .= '</td>';
            $str .= '<td>';
            $str .= '<div id="div_'.$param['id'].'" style="visibility:hidden">';
            $str .= '<table width="100%" border="0">';
            $str .= '<tr>';
            $str .= '<td>'.$param['label_input'].' : <input type="text" name="input_'.$param['id'].'" id="input_'.$param['id'].'" '.$param['other'].' value="" /></td>';
            $str .= '</tr>';
            $str .= '</table>';
            $str .= '</div>';
            // td close in the showing function (js)
            $str = addslashes($str);
            $str = $init.$str.$end;
        } elseif ($field_type == 'inputs_in_2_col') {
            $str = $init.'<table align="center" border="0" width="100%" >';
            $tmp = '';

            for ($i = 0; $i < count($param['input_ids']); ++$i) {
                $tmp .= $param['input_ids'][$i]['ID'].',';

                if ($i % 2 != 1 || $i == 0) { // pair
                    $str .= '<tr>';
                }
                $str .= '<td >'.addslashes($param['input_ids'][$i]['LABEL']).'</td><td><input type="text" name="'.$param['input_ids'][$i]['ID'].'" id="'.$param['input_ids'][$i]['ID'].'" value="" /></td>';
                if ($i % 2 == 1 && $i != 0) { // impair
                    echo '</tr>';
                } else {
                    if ($i + 1 == count($param['input_ids'])) {
                        echo '<td  colspan="3">&nbsp;</td></tr>';
                    }
                }
            }
            $tmp = preg_replace('/,$/', '', $tmp);
            $str .= '</table>';
            $str .= '<input type="hidden" name="meta[]" value="'.$id.'#'.$tmp.'#inputs_in_2_col" />'.$end;
        } elseif ($field_type == 'select_or_other_data') {
            // td open in the showing function (js)
            $str .= '<table align="center" border="0" width="100%" >';
            $str .= '<tr>';
            $str .= '<td>';
            $str .= '<select name="select_'.$param['id'].'" id="select_'.$param['id'].'" onchange="start_action_list('."'".'div_'.$param['id']."', 'select_".$param['id']."', this.selectedIndex".')">';
            $str .= '<option value="">'.$param['default_label_select'].'</option>';
            $str .= '<option value="SHOW_DATA">'.$param['label_define_option'].'</option>';
            for ($i = 0; $i < count($param['options']); ++$i) {
                $str .= '<option value="'.$param['options'][$i]['VALUE'].'">'.$param['options'][$i]['LABEL'].'</option>';
            }
            $str .= '</select>';
            $str .= '</td>';
            $str .= '</tr>';
            $str .= '<tr>';
            $str .= '<td>';
            $str .= '<div id="div_'.$param['id'].'" style="display:none;">';
            $str .= '<table align="center" border="0" width="100%" >';
            $tmp = 'select_'.$param['id'].',';
            for ($i = 0; $i < count($param['input_ids']); ++$i) {
                $tmp .= $param['input_ids'][$i]['ID'].',';
                if ($i % 2 != 1 || $i == 0) { // pair
                    $str .= '<tr>';
                }
                $str .= '<td >'.$param['input_ids'][$i]['LABEL'].' :</td><td><input type="text" name="'.$param['input_ids'][$i]['ID'].'" id="'.$param['input_ids'][$i]['ID'].'" value="" /></td>';
                if ($i % 2 == 1 && $i != 0) { // impair
                    echo '</tr>';
                } else {
                    if ($i + 1 == count($param['input_ids'])) {
                        echo '<td  colspan="3">&nbsp;</td></tr>';
                    }
                }
            }
            $tmp = preg_replace('/,$/', '', $tmp);
            $str .= '</table>';
            $str .= '</div>';
            $str .= '<input type="hidden" name="meta[]" value="'.$id.'#'.$tmp.'#select_or_other_data" />';
            $str .= '</td>';
            $str .= '</tr>';
            $str .= '</table>';
            // td close in the showing function (js)
            $str = addslashes($str);
            $str = $init.$str.$end;
        } else {
        }

        return $str;
    }
}
