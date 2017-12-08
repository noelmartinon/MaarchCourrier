<?php

/*
*   Copyright 2008-2015 Maarch
*
*   This file is part of Maarch Framework.
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
*   along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief Action : Process a document
*
* Open a modal box to displays the process form, make the form checks and loads the result in database.
* Used by the core (manage_action.php page).
*
* @file
* @author Claire Figueras <dev@maarch.org>
* @author Laurent Giovannoni <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup apps
*/

/**
* $confirm  bool false
*/
$confirm = false;
/**
* $etapes  array Contains 2 etaps : form and status (order matters)
*/
$etapes = array('form');
/**
* $frm_width  Width of the modal (empty)
*/
$frm_width='';
/**
* $frm_height  Height of the modal (empty)
*/
$frm_height = '';
/**
* $mode_form  Mode of the modal : fullscreen
*/
$mode_form = 'fullscreen';

include('apps/' . $_SESSION['config']['app_id']. '/definition_mail_categories.php');

/**
 * Returns the indexing form text
 *
 * @param $values Array Contains the res_id of the document to process
 * @param $path_manage_action String Path to the PHP file called in Ajax
 * @param $id_action String Action identifier
 * @param $table String Table
 * @param $module String Origin of the action
 * @param $coll_id String Collection identifier
 * @param $mode String Action mode 'mass' or 'page'
 * @return String The form content text
 **/
function get_form_txt($values, $path_manage_action,  $id_action, $table, $module, $coll_id, $mode)
{

    //DECLARATIONS
    require_once('core/class/class_security.php');
    require_once('modules/basket/class/class_modules_tools.php');
    require_once('core/class/class_request.php');
    require_once('apps/' . $_SESSION['config']['app_id'] . '/class/class_types.php');
    require_once('apps/' . $_SESSION['config']['app_id'] . '/class/class_indexing_searching_app.php');
    require_once('apps/' . $_SESSION['config']['app_id'] . '/class/class_chrono.php');
    
    //INSTANTIATE
    $type = new types();
    $sec = new security();
    $core_tools = new core_tools();
    $b = new basket();
    $is = new indexing_searching_app();
    $cr = new chrono();
    $db = new Database();
    $data = array();
    $indexes = array();
    
    //INITIALIZE
    $frm_str = '';
    $_SESSION['stockCheckbox']= '';
    $_SESSION['req'] = "action";
    $res_id = $values[0];
    $doctypes = $type->getArrayTypes($coll_id);
    $params_data = array('show_folder' => true);
    $data = get_general_data($coll_id, $res_id, 'full', $params_data);
    $_SESSION['save_list']['fromProcess'] = "true";
    $_SESSION['count_view_baskets'] = 0;
    $chrono_number = $cr->get_chrono_number($res_id, $sec->retrieve_view_from_table($table));
    $_SESSION['doc_id'] = $res_id;
    
    //LAUNCH DOCLOCKER
    $docLockerCustomPath = 'apps/maarch_entreprise/actions/docLocker.php';
    $docLockerPath = $_SESSION['config']['businessappurl'] . '/actions/docLocker.php';
    if (is_file($docLockerCustomPath)){
        require_once $docLockerCustomPath;
    }else if (is_file($docLockerPath)){
        require_once $docLockerPath;
    }else{
        exit("can't find docLocker.php");
    }

    $docLocker = new docLocker($res_id);
    if (!$docLocker->canOpen()) {
        $docLockerscriptError = '<script>';
            $docLockerscriptError .= 'destroyModal("modal_' . $id_action . '");';
            $docLockerscriptError .= 'alert("'._DOC_LOCKER_RES_ID.''
                .$res_id.''._DOC_LOCKER_USER.' ' . functions::xssafe($_SESSION['userLock']) . '");';
        $docLockerscriptError .= '</script>';
        return $docLockerscriptError;
    }
    // DocLocker constantly 
    $frm_str .= '<script>';
    $frm_str .= 'setInterval("new Ajax.Request(\'' . $_SESSION['config']['businessappurl'] . 'index.php?display=true&dir=actions&page=docLocker\',{ method:\'post\', parameters: {\'AJAX_CALL\': true, \'lock\': true, \'res_id\': ' . $res_id . '} });", 50000);';
    $frm_str .= '</script>';
    
    $docLocker->lock();
    
    if (isset($data['type_id'])) {
        $indexes = $type->get_indexes($data['type_id']['value'], $coll_id);
        $fields = 'res_id';
        foreach (array_keys($indexes) as $key) {
            $fields .= ',' . $key;
        }
        $stmt = $db->query("SELECT " . $fields . " FROM " . $table . " WHERE res_id = ?", array($res_id));
        $values_fields = $stmt->fetchObject();
        //print_r($indexes);
    }
    if ($core_tools->is_module_loaded('entities')) {
        require_once('modules/entities/class/class_manage_listdiff.php');
        $listdiff = new diffusion_list();
        $roles = $listdiff->list_difflist_roles();
        $_SESSION['process']['diff_list'] = $listdiff->get_listinstance($res_id, false, $coll_id);
        $_SESSION['process']['difflist_type'] = $listdiff->get_difflist_type($_SESSION['process']['diff_list']['object_type']);
    }
    
    //Load multicontacts
    $query = "SELECT c.firstname, c.lastname, c.society, c.contact_id, c.ca_id  ";
    $query .= "FROM view_contacts c, contacts_res cres  ";
    $query .= "WHERE cres.coll_id = 'letterbox_coll' AND cres.res_id = ? AND cast (c.contact_id as varchar) = cres.contact_id AND c.ca_id = cres.address_id ";
    $query .= "GROUP BY c.firstname, c.lastname, c.society, c.contact_id, c.ca_id";

    $stmt = $db->query($query, array($res_id));
    $nbContacts = 0;
    $frameContacts = "";
    $frameContacts = "{";
    while($res = $stmt->fetchObject()){
        $nbContacts = $nbContacts + 1;
        $firstname = str_replace("'","\'", $res->firstname);
        $firstname = str_replace('"'," ", $firstname);
        $lastname = str_replace("'","\'", $res->lastname);
        $lastname = str_replace('"'," ", $lastname);
        $society = str_replace("'","\'", $res->society);
        $society = str_replace('"'," ", $society);
        $frameContacts .= "'contact ".$nbContacts."' : '" 
            . functions::xssafe($firstname) . " " . functions::xssafe($lastname) 
            . " " . functions::xssafe($society) . " (contact)', ";
    }
    $query = "select u.firstname, u.lastname, u.user_id ";
    $query .= "from users u, contacts_res cres  ";
    $query .= "where cres.coll_id = 'letterbox_coll' AND cres.res_id = ? AND cast (u.user_id as varchar) = cres.contact_id ";
    $query .= "GROUP BY u.firstname, u.lastname, u.user_id";
            
    $stmt = $db->query($query, array($res_id));
    
    while($res = $stmt->fetchObject()){
            $nbContacts = $nbContacts + 1;
            $firstname = str_replace("'","\'", $res->firstname);
            $firstname = str_replace('"'," ", $firstname);
            $lastname = str_replace("'","\'", $res->lastname);
            $lastname = str_replace('"'," ", $lastname);
            $frameContacts .= "'contact ".$nbContacts."' : '" 
        . functions::xssafe($firstname) . " " . functions::xssafe($lastname) . " (utilisateur)', ";
    }
    $frameContacts = substr($frameContacts, 0, -2);
    $frameContacts .= "}";
    
    $frm_str .= '<form name="process" method="post" id="process" action="#" '
            . 'class="formsProcess addformsProcess" style="text-align:left;width:100%;">';

    //_ID_TO_DISPLAY ?
    if(_ID_TO_DISPLAY == 'res_id'){
        //MODAL HEADER
        $frm_str .= '<div style="margin:-10px;margin-bottom:10px;background-color: #009DC5;">';
        $frm_str .= '<h2 class="tit" id="action_title" style="display:table-cell;vertical-align:middle;margin:0px;">'. _PROCESS . _LETTER_NUM . $res_id.' : ';
        $frm_str .= '</h2>';
        $frm_str .= '<div style="display:table-cell;vertical-align:middle;">';      
    }else{
        //MODAL HEADER
        $frm_str .= '<div style="margin:-10px;margin-bottom:10px;background-color: #009DC5;">';
        $frm_str .= '<h2 class="tit" title="'. _LETTER_NUM . $res_id . '" id="action_title" style="display:table-cell;vertical-align:middle;margin:0px;">'. _PROCESS . _DOCUMENT . ' ' . $chrono_number.' : ';
        $frm_str .= '</h2>';
        $frm_str .= '<div style="display:table-cell;vertical-align:middle;">';
    }

    //GET ACTION LIST BY AJAX REQUEST
    $frm_str .= '<span id="actionSpan"></span>';
    $frm_str .= '<script>';
        $frm_str .= 'change_category_actions(\'' 
            . $_SESSION['config']['businessappurl'] 
            . 'index.php?display=true&dir=indexing_searching&page=change_category_actions'
            . '&resId=' . $res_id . '&collId=' . $coll_id . '\',\'' . $res_id . '\',\'' . $coll_id . '\',\''.$data['category_id']['value'].'\');';
    $frm_str .= '</script>';

    $frm_str .= '<input type="button" name="send" id="send" value="'
        . _VALIDATE
        . '" class="button" onclick="new Ajax.Request(\'' 
        . $_SESSION['config']['businessappurl'] . 'index.php?display=true&dir=actions&page=docLocker\',{ method:\'post\', parameters: {\'AJAX_CALL\': true, \'unlock\': true, \'res_id\': ' . $res_id . '} });valid_action_form(\'process\', \''
        . $path_manage_action . '\', \'' . $id_action.'\', \''
        . $res_id . '\', \'' . $table . '\', \'' . $module . '\', \''
        . $coll_id . '\', \'' . $mode . '\');"/> ';
    $frm_str .= '</div>';
    $frm_str .= '</div>';

    $frm_str .='<i onmouseover="this.style.cursor=\'pointer\';" '
        .'onclick="new Ajax.Request(\'' . $_SESSION['config']['businessappurl'] 
        . 'index.php?display=true&dir=actions&page=docLocker\',{ method:\'post\', parameters: {\'AJAX_CALL\': true, \'unlock\': true, \'res_id\': ' 
        . $res_id . '}, onSuccess: function(answer){window.location.href=window.location.href.replace(\'&directLinkToAction\', \'\');} });var tmp_bask=$(\'baskets\');';
    $frm_str .= 'if (tmp_bask){tmp_bask.style.visibility=\'visible\';}var tmp_ent =$(\'entity\');';
    $frm_str .= 'if (tmp_ent){tmp_ent.style.visibility=\'visible\';} var tmp_cat =$(\'category\');';
    $frm_str .= 'if (tmp_cat){tmp_cat.style.visibility=\'visible\';}destroyModal(\'modal_'
        . $id_action . '\');reinit();"'
        .' class="fa fa-times-circle fa-2x closeModale" title="'._CLOSE.'"/>';
    $frm_str .='</i>';
    /********************************* LEFT PART **************************************/
    $frm_str .= '<div style="height:90vh;overflow:auto;">';
    $frm_str .= '<div id="validleftprocess" style="display:none;">';
    $frm_str .= '<div id="frm_error_' . $id_action . '" class="error"></div>';
    $frm_str .= '<input type="hidden" name="values" id="values" value="' . $res_id . '" />';
    $frm_str .= '<input type="hidden" name="action_id" id="action_id" value="' . $id_action . '" />';
    $frm_str .= '<input type="hidden" name="mode" id="mode" value="' . $mode . '" />';
    $frm_str .= '<input type="hidden" name="table" id="table" value="' . $table . '" />';
    $frm_str .= '<input type="hidden" name="coll_id" id="coll_id" value="' . $coll_id . '" />';
    $frm_str .= '<input type="hidden" name="module" id="module" value="' . $module . '" />';
    $frm_str .= '<input type="hidden" name="req" id="req" value="second_request" />';

    $frm_str .= '<h3 onclick="new Effect.toggle(\'general_datas_div\', \'blind\', {delay:0.2});'
            . 'whatIsTheDivStatus(\'general_datas_div\', \'divStatus_general_datas_div\');return false;" '
            . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
    $frm_str .= ' <span id="divStatus_general_datas_div" style="color:#1C99C5;"><i class="fa fa-minus-square-o"></i></span>&nbsp;<b>'
            . _GENERAL_INFO . '</b>';
    $frm_str .= '<span class="lb1-details">&nbsp;</span>';
    $frm_str .= '</h3>';

    //GENERAL DATAS
    $frm_str .= '<div id="general_datas_div" style="display:block">';
    $frm_str .= '<div>';
    $frm_str .= '<table width="95%" align="left" border="0">';
    // Displays the document indexes
    foreach (array_keys($data) as $key) {
        if ($key != 'is_multicontacts' && $key != 'folder' || ($key == 'is_multicontacts' && $data[$key]['show_value'] == 'Y')) {
            $frm_str .= '<tr>';
            $frm_str .= '<td width="50%" align="left"><span class="form_title_process">'
                . $data[$key]['label'] . ' :</span>';
            if (isset($data[$key]['addon'])) {
                $frm_str .= ' ' . $data[$key]['addon'];
            }
            $frm_str .= '<td>';
            if ($data[$key]['display'] == 'textinput') {
                $frm_str .= '<input type="text" name="' . $key . '" id="' . $key
                    . '" value="' . $data[$key]['show_value']
                    . '" readonly="readonly" class="readonly" style="border:none;" />';
            } elseif ($data[$key]['display'] == 'textarea') {
                if ($key == 'is_multicontacts') {
                    $frm_str .= '<input type="hidden" name="' . $key . '" id="' . $key
                        . '" value="' . $data[$key]['show_value']
                        . '" readonly="readonly" class="readonly" style="border:none;" />';

                    $frm_str .= '<div onClick="$(\'return_previsualise\').style.display=\'none\';" id="return_previsualise" style="cursor: pointer; display: none; border-radius: 10px; box-shadow: 10px 10px 15px rgba(0, 0, 0, 0.4); padding: 10px; width: auto; height: auto; position: absolute; top: 0; left: 0; z-index: 999; background-color: rgba(255, 255, 255, 0.9); border: 3px solid #459ed1;">';
                    $frm_str .= '<input type="hidden" id="identifierDetailFrame" value="" />';
                    $frm_str .= '</div>';
 
                    $frm_str .= '<input type="text" value="' . $nbContacts . ' ' . _CONTACTS . '" readonly="readonly" class="readonly" size="40" style="cursor: pointer; border:none;" title="' . _SHOW_MULTI_CONTACT . '" alt="' . SHOW_MULTI_CONTACT . '"';
                    $frm_str .= 'onClick = "previsualiseAdminRead(event, ' . $frameContacts . ');"';
                    $frm_str .= '/>';
                } else {
                    $frm_str .= '<textarea name="' . $key . '" id="' . $key . '" rows="3" readonly="readonly" class="readonly" '
                        . 'title="' . $data[$key]['show_value'] . '" style="width: 150px; max-width: 150px; border: none; color: #666666;">'
                        . $data[$key]['show_value']
                        . '</textarea>';
                }
            } else if ($data[$key]['field_type'] == 'radio') {
                for ($k = 0; $k < count($data[$key]['radio']); $k++) {
                    $frm_str .= '<input name ="' . $key . '" ';
                    if ($data[$key]['value'] == $data[$key]['radio'][$k]['ID']) {
                        $frm_str .= 'checked';
                    }
                    $frm_str .= ' type="radio" id="' . $key . '_' . $data[$key]['radio'][$k]['ID'] . '" value="' . $data[$key]['radio'][$k]['ID'] . '" disabled >' . $data[$key]['radio'][$k]['LABEL'];
                }
            }

            if ($key == 'type_id') {
                $_SESSION['category_id_session'] = $data[$key]['value'];
            }

            $frm_str .= '</td>';
            $frm_str .= '</tr>';
        }
    } 
    if ($chrono_number <> '') {
        $frm_str .= '<tr>';
        $frm_str .= '<td width="50%" align="left"><span class="form_title_process">'
            . _CHRONO_NUMBER . ' :</span></td>';
        $frm_str .= '<td>';
        $frm_str .= '<input type="text" name="alt_identifier" id="alt_identifier" value="'
            . functions::xssafe($chrono_number) 
            . '" readonly="readonly" class="readonly" style="border:none;" />';
        $frm_str .= '</td>';
        $frm_str .= '</tr>';
    }
    if (count($indexes) > 0) {
        foreach (array_keys($indexes) as $key) {
            $frm_str .= '<tr>';
                $frm_str .= '<td width="50%" align="left"><span class="form_title_process" >'
                          . $indexes[$key]['label'] . ' :</span></td>';
                $frm_str .= '<td>';
                $frm_str .= '<textarea name="'.$key.'"';
                $frm_str .= ' id="'.$key.'"';
                if (!isset($indexes[$key]['readonly']) || $indexes[$key]['readonly'] == true)
                { 
                    $frm_str.='readonly="readonly" class="readonly"';
                }
                else if ($indexes[$key]['type'] == 'date')
                {
                    $frm_str.= 'onclick="showCalender(this);"';
                }
                $frm_str .= 'style="width: 200px; max-width: 150px; border: medium none; color: rgb(102, 102, 102); height: 60px;"';
                $frm_str .=  '>'.str_replace(array("\n", "\r"), " ", $values_fields->{$key});
                $frm_str .= '</textarea>';
                $frm_str .= '</td >';
            $frm_str .= '</tr>';
        }
        //exit;
    }
    //extension
    $db = new Database();
    $stmt = $db->query("SELECT format FROM ".$table." WHERE res_id = ?", array($res_id));
    $formatLine = $stmt->fetchObject();
    $frm_str .= '<tr>';
    $frm_str .= '<td width="50%" align="left"><span class="form_title_process">' . _FORMAT . ' :</span></td>';
    $frm_str .= '<td>';
    $frm_str .= '<input type="text" name="alt_identifier" id="alt_identifier" value="'
        . functions::xssafe($formatLine->format) . '" readonly="readonly" class="readonly" style="border:none;" />';
    $frm_str .= '</td >';
    $frm_str .= '</tr>';
    $frm_str .= '</table>';
    $frm_str .= '</div>';
    $frm_str .= '</div><br/>';

    /**** Other informations ****/
    $frm_str .= '<h3 onclick="new Effect.toggle(\'complementary_fields\', \'blind\', {delay:0.2});'
        . 'whatIsTheDivStatus(\'complementary_fields\', \'divStatus_complementary_fields\');" '
        . 'class="categorie" style="width:90%;" onmouseover="this.style.cursor=\'pointer\';">';
    $frm_str .= ' <span id="divStatus_complementary_fields" style="color:#1C99C5;"><i class="fa fa-minus-square-o"></i></span>&nbsp;' 
        . _OPT_INDEXES;
    $frm_str .= '</h3>';

    $frm_str .= '<table style="width:100%;" id="complementary_fields">';

	//THESAURUS
    if ($core->is_module_loaded('thesaurus') && $core->test_service('thesaurus_view', 'thesaurus', false)) {
        
        require_once 'modules' . DIRECTORY_SEPARATOR . 'thesaurus' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_modules_tools.php';
        
        $thesaurus = new thesaurus();
        $thesaurusListRes = array();

        $thesaurusListRes = $thesaurus->getThesaursusListRes($res_id);

        $frm_str .= '<tr id="thesaurus_tr" style="display:block;">';
            $frm_str .= '<td colspan="2">'. _THESAURUS . '</td>';
        $frm_str .= '</tr>';

        $frm_str .= '<tr id="thesaurus_tr" style="display:' . $displayValue . ';">';
            $frm_str .= '<td class="indexing_field" id="thesaurus_field"><select multiple="multiple" id="thesaurus" data-placeholder=" "';

        if (!$core->test_service('add_thesaurus_to_res', 'thesaurus', false)) {
            $frm_str .= 'disabled="disabled"';
        }

        $frm_str .= '>';
        if(!empty($thesaurusListRes)){
            foreach ($thesaurusListRes as $key => $value) {

                $frm_str .= '<option title="'.functions::show_string($value['LABEL']).'" data-object_type="thesaurus_id" id="thesaurus_'.$value['ID'].'"  value="' . $value['ID'] . '"';
                    $frm_str .= ' selected="selected"'; 
                $frm_str .= '>' 
                    .  functions::show_string($value['LABEL']) 
                    . '</option>';

            }
        }
        $frm_str .= '</select></td>';
        $frm_str .= ' <td style="width:5px;"><i onclick="lauch_thesaurus_list(this);" class="fa fa-search" title="parcourir le thésaurus" aria-hidden="true" style="cursor:pointer;"></i></td>';
        $frm_str .= '</tr>';
        $frm_str .= '<style>#thesaurus_chosen{width:100% !important;}#thesaurus_chosen .chosen-drop{display:none;}</style>';

        //script
        $frm_str .= '<script>';
            $frm_str .= 'new Chosen($(\'thesaurus\'),{width: "95%", disable_search_threshold: 10});';
        $frm_str .= '</script>';
        /*****************/
    }

    //TAGS
    if ($core_tools->is_module_loaded('tags') && ($core_tools->test_service('tag_view', 'tags', false) == 1)) {
        
        include_once("modules" . DIRECTORY_SEPARATOR . "tags" . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "process" . DIRECTORY_SEPARATOR . "index.php");

    }

    //FOLDERS
    if ($core_tools->is_module_loaded('folder') && ($core->test_service('view_folder_tree', 'folder',false))) {
        
        require_once 'modules' . DIRECTORY_SEPARATOR . 'folder' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_modules_tools.php';
        
        $folders = new folder();
        $folder_info = $folders->get_folders_tree('0');
        $folder = '';
        $folder_id = '';        

        if(isset($data['folder'])&& !empty($data['folder']))
        {
            $folder = $data['folder']['show_value'];
            $folder_id = str_replace(')', '', substr($folder, strrpos($folder,'(')+1));
        }

        $frm_str .= '<tr>';
        $frm_str .= '<td colspan="2">'. _FOLDER . '</td>';
        $frm_str .= '</tr>';
        $frm_str .= '<tr>';
        $frm_str .= '<td class="indexing_field"><select id="folder" name="folder"';

        if(!$core->test_service('associate_folder', 'folder',false)){
            $frm_str .= ' disabled="disabled"';
        }

        $frm_str .= ' onchange="displayFatherFolder(\'folder\')" style="width:95%;"><option value="">Sélectionnez un dossier</option>';
        foreach ($folder_info as $key => $value) {
            if($value['folders_system_id'] == $folder_id){
                $frm_str .= '<option selected="selected" value="'.$value['folders_system_id'].'" parent="' . $value['parent_id'] . '">'.$value['folder_name'].'</option>';
            }else{
                $frm_str .= '<option value="'.$value['folders_system_id'].'" parent="' . $value['parent_id'] . '">'.$value['folder_name'].'</option>';
            }              
        }

        $frm_str .= '</select></td>';
        if ($core->test_service('create_folder', 'folder', false) == 1 && $core->test_service('associate_folder', 'folder',false)) {

            $pathScriptTab = $_SESSION['config']['businessappurl']
                . 'index.php?page=create_folder_form_iframe&module=folder&display=false';

            $frm_str .= '<td style="width:5px;"> <a href="#" id="create_folder" title="' . _CREATE_FOLDER
                . '" onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._CREATE_FOLDER.'\',\''.$pathScriptTab.'\',\'folders\');return false;" '
                . 'style="display:inline;" ><i class="fa fa-plus-circle" title="' 
                . _CREATE_FOLDER . '"></i></a></td>';
        }
        $frm_str .= '</tr>';
        $frm_str .= '<tr id="parentFolderTr" style="display: none"><td colspan="2"><span id="parentFolderSpan" style="font-style: italic;font-size: 10px"></span></td></tr>';
        $frm_str .='<input type="hidden" name="res_id_to_process" id="res_id_to_process"  value="' . $res_id . '" />';
        
        //script
        $frm_str .= '<script>';
            $frm_str .= 'new Chosen($(\'folder\'),{width: "100%", disable_search_threshold: 10, search_contains: true});displayFatherFolder(\'folder\');';
        $frm_str .= '</script>';
    }
    $frm_str .= '</table>';
    $frm_str .= '</div>';

    // ****************************** RIGHT PART *******************************************/

    $frm_str .= '<div id="validright" style="display:none;">';
    
    /*** TOOLBAR ***/
    $frm_str .= '<div class="block" align="center" style="height:20px;width=100%;">';
    
    $frm_str .= '<table width="95%" cellpadding="0" cellspacing="0">';
    $frm_str .= '<tr align="center">';
    
    //HISTORY
    if ($core_tools->test_service('view_doc_history', 'apps', false) || $core->test_service('view_full_history', 'apps', false)) {
        $frm_str .= '<td>';
        
        $pathScriptTab = $_SESSION['config']['businessappurl']
            . 'index.php?display=true&page=show_history_tab&resId='
            . $res_id . '&collId=' . $coll_id;
        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._DOC_HISTORY.'\',\''.$pathScriptTab.'\',\'history\');return false;" '
            . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= '<span id="history_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span>'
            . '&nbsp;<i class="fa fa-history fa-2x" title="'._DOC_HISTORY.'"></i> <sup><span style="display:none;"></span></sup>';
        $frm_str .= '</span>';
        $frm_str .= '</td>';
    }
    
    //NOTE
    if ($core_tools->is_module_loaded('notes')) {
        $frm_str .= '<td>';

        $pathScriptTab = $_SESSION['config']['businessappurl'] . 'index.php?display=true&module=notes&page=notes&identifier=' . $res_id . '&origin=document&coll_id=' . $coll_id . '&load&size=medium';
        
        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._NOTES.'\',\''.$pathScriptTab.'\',\'notes\');return false;" '
            . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= '<span id="notes_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span>'
            . '&nbsp;<i id="notes_tab_img" class="fa fa-pencil fa-2x" title="'._NOTES.'"></i><span id="notes_tab_badge"></span>';
        $frm_str .= '</span>';
        
        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'] . 'index.php?display=true&module=notes&page=load_toolbar_notes&resId='.$res_id.'&collId='.$coll_id;
        $frm_str .='<script>loadToolbarBadge(\'notes_tab\',\''.$toolbarBagde_script.'\');</script>';
        
        $frm_str .= '</td>';
    }
    
    //SENDMAILS                
    if ($core_tools->is_module_loaded('sendmail') === true && $core_tools->test_service('sendmail', 'sendmail', false) === true) {

        $frm_str .= '<td>';
        
        $pathScriptTab = $_SESSION['config']['businessappurl'] . 'index.php?display=true&module=sendmail&page=sendmail&identifier='. $res_id . '&origin=document&coll_id=' . $coll_id . '&load&size=medium';     
        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._SENDED_EMAILS.'\',\''.$pathScriptTab.'\',\'sendmail\');return false;" '
            . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= '<span id="sendmail_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span><b>&nbsp;'
            . '<i id="sendmail_tab_img" class="fa fa-envelope fa-2x" title="'._SENDED_EMAILS.'"></i><span id="sendmail_tab_badge"></span>';
        $frm_str .= '</span>';
        
        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'] . 'index.php?display=true&module=sendmail&page=load_toolbar_sendmail&resId='.$res_id.'&collId='.$coll_id;
        $frm_str .='<script>loadToolbarBadge(\'sendmail_tab\',\''.$toolbarBagde_script.'\');</script>';
        
        $frm_str .= '</td>';
    }
    
    //DIFFUSION LIST
    if ($core_tools->is_module_loaded('entities')) {      
        $category = $data['category_id']['value'];
        if($core->test_service('add_copy_in_indexing_validation', 'entities', false)){
            $onlyCC = '&only_cc';
        }
        json_encode($roles);
        $roles_str = json_encode($roles);
        $frm_str .= '<td>';
        $pathScriptTab = $_SESSION['config']['businessappurl']
                    . 'index.php?display=true&page=show_diffList_tab&module=entities&resId='.$res_id.'&collId='.$coll_id.'&category='.$category.'&roles='.urlencode($roles_str).$onlyCC;     
        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._DIFF_LIST_COPY.'\',\''.$pathScriptTab.'\',\'difflist\');return false;" '
            . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= '<span id="difflist_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span>'
            . '&nbsp;<i class="fa fa-share-alt fa-2x" title="'._DIFF_LIST_COPY.'"></i> <sup><span style="display:none;"></span></sup>';
        $frm_str .= '</span>';
        $frm_str .= '</td>';
    }
    
    //VERSIONS (NOT USED ?)
    if ($core->is_module_loaded('content_management') && $viewVersions) {
        $versionTable = $sec->retrieve_version_table_from_coll_id(
            $coll_id
        );
        $selectVersions = "SELECT res_id FROM "
            . $versionTable . " WHERE res_id_master = ? and status <> 'DEL' order by res_id desc";
        $dbVersions = new Database();
        $stmt = $dbVersions->query($selectVersions, array($res_id));
        $nb_versions_for_title = $stmt->rowCount();
        $lineLastVersion = $stmt->fetchObject();
        $lastVersion = $lineLastVersion->res_id;
        if ($lastVersion <> '') {
            $objectId = $lastVersion;
            $objectTable = $versionTable;
        } else {
            $objectTable = $sec->retrieve_table_from_coll(
                $coll_id
            );
            $objectId = $res_id;
            $_SESSION['cm']['objectId4List'] = $res_id;
        }
        if ($nb_versions_for_title == 0) {
            $extend_title_for_versions = '0';
            $class = 'nbResZero';
            $style2 = 'display:none';
            $style = 'opacity:0.5;';
        } else {
            $extend_title_for_versions = $nb_versions_for_title;
            $class = 'nbRes';
            $style = '';
            $style2 = '';
        }
        $_SESSION['cm']['resMaster'] = '';
        $frm_str .= '<td>';
        $pathScriptTab = $_SESSION['config']['businessappurl']
                    . 'index.php?display=true&page=show_versions_tab&collId=' . $coll_id . '&resId='.$res_id.'&objectTable='.$objectTable;
        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._VERSIONS.'\',\''.$pathScriptTab.'\',\'versions\');return false;" '
            . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= '<span id="versions_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span><b>'
            . '&nbsp;<i class="fa fa-code-fork fa-2x" style="'.$style.'" title="'._VERSIONS.'"></i> <sup><span id="nbVersions" style="'.$style2.'" class="'.$class.'">' 
            . $extend_title_for_versions . '</span></sup>';
        $frm_str .= '</b></span>';
        $frm_str .= '</td>';
    }
    
    //LINKS
    $frm_str .= '<td>';
    
    $pathScriptTab = $_SESSION['config']['businessappurl']
                    . 'index.php?display=true&page=show_links_tab';  
    $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._LINK_TAB.'\',\''.$pathScriptTab.'\',\'links\');return false;" '
        . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
    $frm_str .= '<span id="links_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span><b>&nbsp;'
            . '<i id="links_tab_img" class="fa fa-link fa-2x" title="'._LINK_TAB.'"></i><span id="links_tab_badge"></span>';
    $frm_str .= '</span>';
    
    //LOAD TOOLBAR BADGE
    $toolbarBagde_script = $_SESSION['config']['businessappurl'] . 'index.php?display=true&page=load_toolbar_links&resId='.$res_id.'&collId='.$coll_id;
    $frm_str .='<script>loadToolbarBadge(\'links_tab\',\''.$toolbarBagde_script.'\');</script>';
        
    $frm_str .= '</td>';
    
    //VISA CIRCUIT
    if($core_tools->is_module_loaded('visa')){
  
        if ($core->test_service('config_visa_workflow', 'visa', false)){
            $frm_str .= '<td>';
            
            $pathScriptTab = $_SESSION['config']['businessappurl']
                    . 'index.php?display=true&page=show_visa_tab&module=visa&resId='.$res_id.'&collId='.$coll_id.'&destination='.$data['destination']['value'];
            
            $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._VISA_WORKFLOW.'\',\''.$pathScriptTab.'\',\'visa\');return false;" '
                . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
            $frm_str .= '<span id="visa_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span><b>&nbsp;'
                . '<i id="visa_tab_img" class="fa fa-list-ol fa-2x" title="'._VISA_WORKFLOW.'"></i><span id="visa_tab_badge"></span>';
            $frm_str .= '</span>';
            $frm_str .= '</td>';
            
            //LOAD TOOLBAR BADGE
            $toolbarBagde_script = $_SESSION['config']['businessappurl'] . 'index.php?display=true&module=visa&page=load_toolbar_visa&resId='.$res_id.'&collId='.$coll_id;
            $frm_str .='<script>loadToolbarBadge(\'visa_tab\',\''.$toolbarBagde_script.'\');</script>';
        }
    }

    //AVIS CIRCUIT
    if ($core_tools->is_module_loaded('avis')) { 
    
        if ($core->test_service('config_avis_workflow', 'avis', false)){
            
            $frm_str .= '<td>';
            
            $pathScriptTab = $_SESSION['config']['businessappurl']
                . 'index.php?display=true&page=show_avis_tab&module=avis&resId='.$res_id.'&collId='.$coll_id;
            
            $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''.urlencode(_AVIS_WORKFLOW).'\',\''.$pathScriptTab.'\',\'avis\');return false;" '
                . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
            $frm_str .= '<span id="avis_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span><b>&nbsp;'
                . '<i id="avis_tab_img" class="fa fa-commenting fa-2x" title="'._AVIS_WORKFLOW.'"></i><span id="avis_tab_badge"></span>';
            $frm_str .= '</span>';   
            $frm_str .= '</td>';
            
            //LOAD TOOLBAR BADGE
            $toolbarBagde_script = $_SESSION['config']['businessappurl'] . 'index.php?display=true&module=avis&page=load_toolbar_avis&resId='.$res_id.'&collId='.$coll_id;
            $frm_str .='<script>loadToolbarBadge(\'avis_tab\',\''.$toolbarBagde_script.'\');</script>';
        }
    }

    //ATTACHMENTS
    if ($core_tools->is_module_loaded('attachments')) {
        $frm_str .= '<td>';
        $_SESSION['destination_entity'] = $data['destination']['value'];
        $pathScriptTab = $_SESSION['config']['businessappurl']
                        . 'index.php?display=true&page=show_attachments_tab&module=attachments&resId='.$res_id.'&collId='.$coll_id;
        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''.urlencode(_PJ).'\',\''.$pathScriptTab.'\',\'attachments\');return false;" '
                  . 'onmouseover="this.style.cursor=\'pointer\';" style="width:90%;">';

        $frm_str .= '<span id="attachments_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span><b>&nbsp;'
                . '<i id="attachments_tab_img" class="fa fa-paperclip fa-2x" title="'._PJ.'"></i><span id="attachments_tab_badge"></span>';
        $frm_str .= '</span>';   
        $frm_str .= '</td>';
        
        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'] . 'index.php?display=true&module=attachments&page=load_toolbar_attachments&resId='.$res_id.'&collId='.$coll_id;
        $frm_str .='<script>loadToolbarBadge(\'attachments_tab\',\''.$toolbarBagde_script.'\');</script>';
    }
    //CASES
    if ($core_tools->is_module_loaded('cases')) {
        $frm_str .= '<td>';
        
        $pathScriptTab = $_SESSION['config']['businessappurl']
                    . 'index.php?display=true&page=show_case_tab&module=cases&resId='.$res_id.'&collId='.$coll_id;
        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''.urlencode(_CASE).'\',\''.$pathScriptTab.'\',\'cases\');return false;" '
            . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        
        $frm_str .= '<span id="cases_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span><b>&nbsp;'
                . '<i id="cases_tab_img" class="fa fa-suitcase fa-2x" title="'._CASE.'"></i><span id="cases_tab_badge"></span>';
        $frm_str .= '</span>';  
        $frm_str .= '</td>';
        
        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'] . 'index.php?display=true&module=cases&page=load_toolbar_cases&resId='.$res_id.'&collId='.$coll_id;
        $frm_str .='<script>loadToolbarBadge(\'cases_tab\',\''.$toolbarBagde_script.'\');</script>';
    }

    //PRINT FOLDER
   if ($core_tools->test_service('print_folder_doc', 'visa', false)) {
       $frm_str .= '<td>';
        $pathScriptTab = $_SESSION['config']['businessappurl']
                . 'index.php?display=true&page=show_printFolder_tab&module=visa&resId='
                . $res_id . '&collId=' . $coll_id . '&table=' . $table;
        $frm_str .= '<span onclick="loadTab(\'' . $res_id . '\',\'' . $coll_id . '\',\'' . urlencode(_PRINTFOLDER) . '\',\'' . $pathScriptTab . '\',\'printfolder\');return false;" '
                . 'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= ' <span id="printfolder_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square-o"></i></span>&nbsp;<i class="fa fa-print fa-2x" title="' . _PRINTFOLDER . '"></i><sup><span style="display:none;"></span></sup>';
        $frm_str .= '</span>';
        $frm_str .= '</td>';
    }

    //END TOOLBAR
    $frm_str .= '</table>';
    $frm_str .= '</div>';
    $frm_str .='<div id =\'show_tab\' module=\'\'>';
    $frm_str .='</div>';
    
    //RESOURCE FRAME
    $frm_str .= '<iframe src="' . $_SESSION['config']['businessappurl']
        . 'index.php?display=true&dir=indexing_searching&page=view_resource_controler&id='
        . $res_id . '&watermark_outgoing=true" name="viewframe" id="viewframe" scrolling="auto" frameborder="0" width="100%" style="width:100% !important;"></iframe>';

    $frm_str .= '</div>';

    //EXTRA SCRIPT
    $frm_str .= '<script type="text/javascript">';
    $frm_str .= 'window.scrollTo(0,0);';
    $frm_str .= '$j("#validleftprocess").show();';
    $frm_str .= '$j("#validright").show();';
    $frm_str .= '$(\'entity\').style.visibility=\'hidden\';';
    $frm_str .= '$(\'category\').style.visibility=\'hidden\';';
    $frm_str .= '$(\'baskets\').style.visibility=\'hidden\';';
    $frm_str .= '</script>';
    
    /*** Extra CSS ***/
    $frm_str .= '<style>';
    $frm_str .= '#destination_chosen .chosen-drop{width:400px;}#folder_chosen .chosen-drop{width:400px;}';
    $frm_str .= '#modal_'. $id_action . '{height:96% !important;width:98% !important;min-width:1250px;overflow:hidden;}';
    $frm_str .= '#modal_'. $id_action . '_layer{height:100% !important;width:98% !important;min-width:1250px;overflow:hidden;}';
    $frm_str .= '#validleftprocess{height:100% !important;width:300px !important;}';
    $frm_str .= '#validright{width:76% !important;height:100% !important;}';
    $frm_str .= '@media screen and (max-width: 1280px) {#validright{width:73% !important;}}';
    $frm_str .= '#viewframe{width:100% !important;height:93% !important;}';
    $frm_str .= '#maarch_body{overflow:hidden !important;}';
    $frm_str .= '</style>';
    
    $frm_str .= '</div>';
    $frm_str .= '</form>';

    return addslashes($frm_str);
}

/**
 * Checks the action form
 *
 * @param $form_id String Identifier of the form to check
 * @param $values Array Values of the form
 * @return Bool true if no error, false otherwise
 **/
function check_form($form_id,$values)
{
    $db = new Database();
    $core = new core_tools();
    $check = true;
    $folder = '';
    $folder_id = '';
    $foldertype_id = '';
    
    
    if ($core->is_module_loaded('folder')) {
           
        if (!empty($folder)) {

            $folder_id = $folder;
            $stmt = $db->query("SELECT folders_system_id FROM ".$_SESSION['tablename']['fold_folders']." WHERE folders_system_id = ?", array($folder_id));
            if ($stmt->rowCount() == 0) {
                $_SESSION['action_error'] = _FOLDER.' '.$folder_id.' '._UNKNOWN;
                return false;
            }
        }

        if (!empty($res_id) && !empty($coll_id) && !empty($folder_id)) {
            require_once('core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php');
            $sec = new security();
            $table = $sec->retrieve_table_from_coll($coll_id);
            if (empty($table)) {
                $_SESSION['action_error'] .= _COLLECTION.' '._UNKNOWN;
                return false;
            }
            $stmt = $db->query("SELECT type_id FROM ".$table." WHERE res_id = ?", array($res_id));
            $res = $stmt->fetchObject();
            $type_id = $res->type_id;
            $stmt = $db->query("SELECT foldertype_id FROM ".$_SESSION['tablename']['fold_folders']." WHERE folders_system_id = ?", array($folder_id));
            $res = $stmt->fetchObject();
            $foldertype_id = $res->foldertype_id;
            $stmt = $db->query("SELECT fdl.foldertype_id FROM ".$_SESSION['tablename']['fold_foldertypes_doctypes_level1']
                ." fdl, ".$_SESSION['tablename']['doctypes']
                ." d WHERE d.doctypes_first_level_id = fdl.doctypes_first_level_id and fdl.foldertype_id = ? and d.type_id = ?", array($foldertype_id, $type_id));
            if ($stmt->rowCount() == 0) {
                $_SESSION['action_error'] .= _ERROR_COMPATIBILITY_FOLDER;
                return false;
            }
        }
    }
    return $check;
}

/**
 * Action of the form : loads the index in the db
 *
 * @param $arr_id Array Not used here
 * @param $history String Log the action in history table or not
 * @param $id_action String Action identifier
 * @param $label_action String Action label
 * @param $status String  Not used here
 * @param $coll_id String Collection identifier
 * @param $table String Table
 * @param $values_form String Values of the form to load
 * @return false or an array
 *             $data['result'] : res_id of the new file followed by #
 *             $data['history_msg'] : Log complement (empty by default)
 **/
function manage_form($arr_id, $history, $id_action, $label_action, $status,  $coll_id, $table, $values_form)
{
    if (empty($values_form) || count($arr_id) < 1 || empty($coll_id)) {
        return false;
    }

    require_once("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_security.php");
    
    $sec = new security();
    $db = new Database();
    $core = new core_tools();

    $res_table = $sec->retrieve_table_from_coll($coll_id);
    $ind = $sec->get_ind_collection($coll_id);
    $table = $_SESSION['collections'][$ind]['extensions'][0];
    $other_txt = '';
    $process_notes = '';
    $folder = '';
    $thesaurusList = '';

    for ($j=0; $j<count($values_form); $j++) {
        if ($values_form[$j]['ID'] == "process_notes") {
            $process_notes = $values_form[$j]['VALUE'];
        }
        if ($values_form[$j]['ID'] == "folder") {
            $folder = $values_form[$j]['VALUE'];
        }
        if ($values_form[$j]['ID'] == "tag_userform") {
            $tags = $values_form[$j]['VALUE'];
        }
        if ($values_form[$j]['ID'] == "thesaurus") {
            $thesaurusList = $values_form[$j]['VALUE'];
        }
    }

    if ($core->is_module_loaded('tags')) {
        $tags_list = explode('__', $tags);
        include_once("modules" . DIRECTORY_SEPARATOR . "tags" . DIRECTORY_SEPARATOR . "tags_update.php");
    }

    //THESAURUS
    if ($core->is_module_loaded('thesaurus')) {
        require_once 'modules' . DIRECTORY_SEPARATOR . 'thesaurus' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_modules_tools.php';
        
        $thesaurus = new thesaurus();
        
        $thesaurus->updateResThesaurusList($thesaurusList,$arr_id[0]);
    }

    //FOLDERS
    if ($core->is_module_loaded('folder') && ($core->test_service('associate_folder', 'folder',false) == 1)) {
        $folder_id = '';
        $old_folder_id = '';
        
        //get old folder ID
        $stmt = $db->query("SELECT folders_system_id FROM ".$res_table." WHERE res_id = ?", array($arr_id[0]));
        $res = $stmt->fetchObject();
        $old_folder_id = $res->folders_system_id;
            
        if (!empty($folder)) {    
            $folder_id = $folder;

            if ($folder_id <> $old_folder_id && $_SESSION['history']['folderup']) {
                require_once("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_history.php");
                
                $hist = new history();
                
                $hist->add($_SESSION['tablename']['fold_folders'], $folder_id, "UP", 'folderup', _DOC_NUM.$arr_id[0]._ADDED_TO_FOLDER, $_SESSION['config']['databasetype'],'apps');
                if (isset($old_folder_id) && !empty($old_folder_id)) {
                    $hist->add($_SESSION['tablename']['fold_folders'], $old_folder_id, "UP", 'folderup', _DOC_NUM.$arr_id[0]._DELETED_FROM_FOLDER, $_SESSION['config']['databasetype'],'apps');
                }
            }

            $db->query("UPDATE ".$res_table." SET folders_system_id = ? WHERE res_id = ? ", array($folder_id, $arr_id[0]));
        } else if(empty($folder) && !empty($old_folder_id)) { //Delete folder reference in res_X
            $db->query("UPDATE ".$res_table." SET folders_system_id = NULL WHERE res_id = ?", array($arr_id[0]));
        }
    }
    //DIFFLIST
    if ($core->is_module_loaded('entities') && count($_SESSION['redirect']['diff_list']) == 0) {
        require_once('modules/entities/class/class_manage_listdiff.php');
        
        $list = new diffusion_list();
        
        $params = array('mode'=> 'listinstance', 'table' => $_SESSION['tablename']['ent_listinstance'], 'coll_id' => $coll_id, 'res_id' => $arr_id[0], 'user_id' => $_SESSION['user']['UserId'], 'concat_list' => true, 'only_cc' => true);
        
        $list->load_list_db($_SESSION['process']['diff_list'], $params); //pb enchainement avec action redirect
    }
    //$_SESSION['process']['diff_list'] = array();
    $_SESSION['redirect']['diff_list'] = array();
    unset($_SESSION['redirection']);
    unset($_SESSION['redirect']);
    $db->query("UPDATE ".$table." SET answer_type_bitmask = ?, process_notes = ?, other_answer_desc = ? WHERE res_id= ?",
    array($bitmask, $process_notes, $other_txt, $arr_id[0]));
    return array('result' => $arr_id[0].'#', 'history_msg' => '');
}

function manage_unlock($arr_id, $history, $id_action, $label_action, $status, $coll_id, $table)
{
    $db = new Database();
    
    $result = '';
    
    for ($i=0; $i<count($arr_id);$i++) {
        $result .= $arr_id[$i].'#';
        $db->query("UPDATE ".$table. " SET video_user = '', video_time = 0 WHERE res_id = ?", array($arr_id[$i]));
    }
    return array('result' => $result, 'history_msg' => '');
}

