<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   validate_mail
*
* @author  dev <dev@maarch.org>
* @ingroup apps
*/

/**
 * $confirm  bool false.
 */
$confirm = false;
/**
 * $etapes  array Contains only one etap : form.
 */
$etapes = array('form');
/**
 * $frm_width  Width of the modal (empty).
 */
$frm_width = '';
/**
 * $frm_height  Height of the modal (empty).
 */
$frm_height = '';
/**
 * $mode_form  Mode of the modal : fullscreen.
 */
$mode_form = 'fullscreen';

$_SESSION['is_multi_contact'] = '';

include 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'definition_mail_categories.php';

///////////////////// Pattern to check dates
$_ENV['date_pattern'] = '/^[0-3][0-9]-[0-1][0-9]-[1-2][0-9][0-9][0-9]$/';

function check_category($coll_id, $res_id)
{
    require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php';
    $sec = new security();
    $view = $sec->retrieve_view_from_coll_id($coll_id);

    $db = new Database();
    $stmt = $db->query('SELECT category_id FROM '.$view.' WHERE res_id = ?', array($res_id));
    $res = $stmt->fetchObject();

    if (!isset($res->category_id)) {
        $ind_coll = $sec->get_ind_collection($coll_id);
        $table_ext = $_SESSION['collections'][$ind_coll]['extensions'][0];
        $db->query('INSERT INTO '.$table_ext.' (res_id, category_id) VALUES (?, ?)',
            array($res_id, $_SESSION['coll_categories']['letterbox_coll']['default_category']));
    }
}

/**
 * Returns the validation form text.
 *
 * @param $values Array Contains the res_id of the document to validate
 * @param $path_manage_action String Path to the PHP file called in Ajax
 * @param $id_action String Action identifier
 * @param $table String Table
 * @param $module String Origin of the action
 * @param $coll_id String Collection identifier
 * @param $mode String Action mode 'mass' or 'page'
 *
 * @return string The form content text
 **/
function get_form_txt($values, $path_manage_action, $id_action, $table, $module, $coll_id, $mode)
{
    $displayValue = 'table-row';
    //DECLARATIONS
    require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php';
    require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_business_app_tools.php';
    require_once 'modules'.DIRECTORY_SEPARATOR.'basket'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_modules_tools.php';
    require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_types.php';
    require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_request.php';

    //INSTANTIATE
    $sec = new security();
    $core_tools = new core_tools();
    $b = new basket();
    $type = new types();
    $db = new Database();

    //INITIALIZE
    $frm_str = '';
    $_SESSION['stockCheckbox'] = '';
    $_SESSION['ListDiffFromRedirect'] = false;
    unset($_SESSION['m_admin']['contact']);
    $_SESSION['req'] = 'action';
    $res_id = $values[0];
    $_SESSION['doc_id'] = $res_id;
    $_SESSION['save_list']['fromValidateMail'] = 'true';
    $_SESSION['count_view_baskets'] = 0;
    check_category($coll_id, $res_id);
    $data = get_general_data($coll_id, $res_id, 'minimal');
    $_SESSION['category_id'] = $data['category_id']['value'];
    $view = $sec->retrieve_view_from_coll_id($coll_id);
    $stmt = $db->query('SELECT initiator, alt_identifier, creation_date FROM '.$view.' WHERE res_id = ?', array($res_id));
    $resChrono = $stmt->fetchObject();
    $chrono_number = explode('/', $resChrono->alt_identifier);
    $chrono_number = $chrono_number[1];
    $creation_date = functions::format_date_db($resChrono->creation_date, false);
    $initiator = $resChrono->initiator;

    //LAUNCH DOCLOCKER
    $docLockerCustomPath = 'apps/maarch_entreprise/actions/docLocker.php';
    $docLockerPath = $_SESSION['config']['businessappurl'].'/actions/docLocker.php';

    if (is_file($docLockerCustomPath)) {
        require_once $docLockerCustomPath;
    } elseif (is_file($docLockerPath)) {
        require_once $docLockerPath;
    } else {
        exit("can't find docLocker.php");
    }

    $docLocker = new docLocker($res_id);

    if (!$docLocker->canOpen()) {
        $docLockerscriptError = '<script>';
        $docLockerscriptError .= 'destroyModal("modal_'.$id_action.'");';
        $docLockerscriptError .= 'alert("'._DOC_LOCKER_RES_ID.''.$res_id.''._DOC_LOCKER_USER.' '.$_SESSION['userLock'].'");';
        $docLockerscriptError .= '</script>';

        return $docLockerscriptError;
    }

    // DocLocker constantly
    $frm_str .= '<script>';
    $frm_str .= 'setInterval("new Ajax.Request(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=actions&page=docLocker\',{ method:\'post\', parameters: {\'AJAX_CALL\': true, \'lock\': true, \'res_id\': '.$res_id.'} });", 50000);';
    $frm_str .= '</script>';

    $docLocker->lock();

    if ($_SESSION['features']['show_types_tree'] == 'true') {
        $doctypes = $type->getArrayStructTypes($coll_id);
    } else {
        $doctypes = $type->getArrayTypes($coll_id);
    }

    $hidden_doctypes = array();

    if ($core_tools->is_module_loaded('templates')) {
        $stmt = $db->query('SELECT type_id FROM '.$_SESSION['tablename']['temp_templates_doctype_ext']." WHERE is_generated = 'NULL!!!'");
        while ($res = $stmt->fetchobject()) {
            array_push($hidden_doctypes, $res->type_id);
        }
    }
    $today = date('d-m-Y');

    if ($core_tools->is_module_loaded('entities')) {
        //DECLARATIONS
        require_once 'modules/entities/class/class_manage_entities.php';
        require_once 'modules/entities/class/class_manage_listdiff.php';

        //INSTANTIATE
        $allEntitiesTree = array();
        $ent = new entity();
        $diff_list = new diffusion_list();

        //INITIALIZE
        $EntitiesIdExclusion = array();
        $load_listmodel = true;

        if (!empty($_SESSION['user']['redirect_groupbasket_by_group'][$_SESSION['current_basket']['id']][$_SESSION['current_basket']['group_id']][$id_action]['entities'])) {
            $stmt = $db->query(
                'SELECT entity_id FROM '
                .ENT_ENTITIES.' WHERE entity_id not in ('
                .$_SESSION['user']['redirect_groupbasket_by_group'][$_SESSION['current_basket']['id']][$_SESSION['current_basket']['group_id']][$id_action]['entities']
                .") and enabled= 'Y' order by entity_id"
            );
            while ($res = $stmt->fetchObject()) {
                array_push($EntitiesIdExclusion, $res->entity_id);
            }
        }

        $allEntitiesTree = $ent->getShortEntityTreeAdvanced(
            $allEntitiesTree, 'all', '', $EntitiesIdExclusion, 'all'
        );

        //diffusion list in this basket ?
        if ($_SESSION['current_basket']['difflist_type'] == 'entity_id') {
            $target_model = 'document.getElementById(\'destination\').options[document.getElementById(\'destination\').selectedIndex]';
            $func_load_listdiff_by_entity = 'change_entity(this.options[this.selectedIndex].value, \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=entities&page=load_listinstance'.'\',\'diff_list_div\', \'indexing\', \''.$display_value.'\', \'\', $j(\'#category_id\').val());';
        } elseif ($_SESSION['current_basket']['difflist_type'] == 'type_id') {
            $target_model = 'document.getElementById(\'type_id\').options[document.getElementById(\'type_id\').selectedIndex]';
            $func_load_listdiff_by_type = 'load_listmodel('.$target_model.', \'diff_list_div\', \'indexing\', $j(\'#category_id\').val());';
        } else {
            $target_model = 'document.getElementById(\'destination\').options[document.getElementById(\'destination\').selectedIndex]';
            $func_load_listdiff_by_entity = 'change_entity(this.options[this.selectedIndex].value, \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=entities&page=load_listinstance'.'\',\'diff_list_div\', \'indexing\', \''.$display_value.'\', \'\', $j(\'#category_id\').val());';
        }

        //LOADING LISTMODEL
        $stmt = $db->query('SELECT res_id FROM '.$_SESSION['tablename']['ent_listinstance'].' WHERE res_id = ?', array($res_id));
        if ($stmt->rowCount() > 0) {
            $load_listmodel = false;
            $_SESSION['indexing']['diff_list'] = $diff_list->get_listinstance($res_id);
        }
    }

    //Load Multicontacts
    //CONTACTS
    $_SESSION['adresses']['to'] = array();
    $_SESSION['adresses']['addressid'] = array();
    $_SESSION['adresses']['contactid'] = array();

    $query = 'SELECT c.is_corporate_person, c.is_private, c.contact_lastname, c.contact_firstname, c.society, c.society_short, c.contact_purpose_id, c.address_num, c.address_street, c.address_postal_code, c.address_town, c.lastname, c.firstname, c.contact_id, c.ca_id ';
    $query .= 'FROM view_contacts c, contacts_res cres ';
    $query .= "WHERE cres.coll_id = 'letterbox_coll' AND cres.res_id = ? AND cast (c.contact_id as varchar(128)) = cres.contact_id AND c.ca_id = cres.address_id";
    $stmt = $db->query($query, array($res_id));

    while ($res = $stmt->fetchObject()) {
        if ($res->is_corporate_person == 'Y') {
            $addContact = $res->society.' ';
            if (!empty($res->society_short)) {
                $addContact .= '('.$res->society_short.') ';
            }
        } else {
            $addContact = $res->contact_lastname.' '.$res->contact_firstname.' ';
            if (!empty($res->society)) {
                $addContact .= '('.$res->society.') ';
            }
        }
        if ($res->is_private == 'Y') {
            $addContact .= '('._CONFIDENTIAL_ADDRESS.')';
        } else {
            require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_contacts_v2.php';
            $contact = new contacts_v2();
            $addContact .= '- '.$contact->get_label_contact($res->contact_purpose_id, $_SESSION['tablename']['contact_purposes']).' : ';
            if (!empty($res->lastname) || !empty($res->firstname)) {
                $addContact .= $res->lastname.' '.$res->firstname;
            }
            if (!empty($res->address_num) || !empty($res->address_street) || !empty($res->address_town) || !empty($res->address_postal_code)) {
                $addContact .= ', '.$res->address_num.' '.$res->address_street.' '.$res->address_postal_code.' '.strtoupper($res->address_town);
            }
        }

        array_push($_SESSION['adresses']['to'], $addContact);
        array_push($_SESSION['adresses']['addressid'], $res->ca_id);
        array_push($_SESSION['adresses']['contactid'], $res->contact_id);
    }

    $resourceContacts = \Resource\models\ResourceContactModel::getFormattedByResId(['resId' => $res_id]);

    //USERS
    $query = 'SELECT u.firstname, u.lastname, u.user_id ';
    $query .= 'FROM users u, contacts_res cres ';
    $query .= "WHERE cres.coll_id = 'letterbox_coll' AND cres.res_id = ? AND cast (u.user_id as varchar(128)) = cres.contact_id";
    $stmt = $db->query($query, array($res_id));

    while ($res = $stmt->fetchObject()) {
        $addContact = $res->firstname.$res->lastname;
        array_push($_SESSION['adresses']['to'], $addContact);
        array_push($_SESSION['adresses']['addressid'], 0);
        array_push($_SESSION['adresses']['contactid'], $res->user_id);
    }

    $frm_str .= '<form name="index_file" method="post" id="index_file" action="#" class="forms indexingform" style="text-align:left;width:100%;">';
    //MODAL HEADER
    $frm_str .= '<div style="margin:-10px;margin-bottom:10px;background-color: #135F7F;">';
    if (_ID_TO_DISPLAY == 'res_id') {
        $frm_str .= '<h2 class="tit" id="action_title" style="display:table-cell;vertical-align:middle;margin:0px;">'._VALIDATE_MAIL.' '._NUM.functions::xssafe($res_id).' : ';
        $frm_str .= '</h2>';
    } else {
        $frm_str .= '<h2 class="tit" id="action_title" title="'._LETTER_NUM.$res_id.'" style="display:table-cell;vertical-align:middle;margin:0px;">'._PROCESS._DOCUMENT.' '.$resChrono->alt_identifier.' : ';
        $frm_str .= '</h2>';
    }

    $frm_str .= '<div style="display:table-cell;vertical-align:middle;">';

    //GET ACTION LIST BY AJAX REQUEST
    $frm_str .= '<span id="actionSpan"></span>';

    $frm_str .= '<input type="button" name="send" id="send" value="'._VALIDATE.'" class="button" onclick="if(document.getElementById(\'contactcheck\').value!=\'success\'){if (confirm(\''._CONTACT_CHECK.'\n\nContinuer ?\')){new Ajax.Request(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=actions&page=docLocker\',{ method:\'post\', parameters: {\'AJAX_CALL\': true, \'unlock\': true, \'res_id\': '.$res_id.'} });valid_action_form( \'index_file\', \''.$path_manage_action.'\', \''.$id_action.'\', \''.$res_id.'\', \''.$table.'\', \''.$module.'\', \''.$coll_id.'\', \''.$mode.'\');}}else{new Ajax.Request(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=actions&page=docLocker\',{ method:\'post\', parameters: {\'AJAX_CALL\': true, \'unlock\': true, \'res_id\': '.$res_id.'} });valid_action_form( \'index_file\', \''.$path_manage_action.'\', \''.$id_action.'\', \''.$res_id.'\', \''.$table.'\', \''.$module.'\', \''.$coll_id.'\', \''.$mode.'\');}"/> ';
    $frm_str .= '</div>';
    $frm_str .= '</div>';

    $frm_str .= '<i onmouseover="this.style.cursor=\'pointer\';" '
        .'onclick="new Ajax.Request(\''.$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=actions&page=docLocker\',{ method:\'post\', parameters: {\'AJAX_CALL\': true, \'unlock\': true, \'res_id\': '
        .functions::xssafe($res_id).'}, onSuccess: function(answer){window.location.href=\''
        .$_SESSION['config']['businessappurl'].'index.php?page=view_baskets&module=basket&baskets='
        .$_SESSION['current_basket']['id'].'\';} });$j(\'#baskets\').css(\'visibility\',\'visible\');destroyModal(\'modal_'.$id_action.'\');reinit();"'
        .' class="fa fa-times-circle fa-2x closeModale" title="'._CLOSE.'"/>';
    $frm_str .= '</i>';

    //PART LEFT
    $frm_str .= '<div style="height:90vh;overflow:auto;">';
    $frm_str .= '<div id="validleft" style="width:430px;">';
    $frm_str .= '<div id="valid_div" style="display:none;";>';
    $frm_str .= '<div id="frm_error_'.$id_action.'" class="indexing_error"></div>';

    $frm_str .= '<input type="hidden" name="values" id="values" value="'.$res_id.'" />';
    $frm_str .= '<input type="hidden" name="action_id" id="action_id" value="'.$id_action.'" />';
    $frm_str .= '<input type="hidden" name="mode" id="mode" value="'.$mode.'" />';
    $frm_str .= '<input type="hidden" name="table" id="table" value="'.$table.'" />';
    $frm_str .= '<input type="hidden" name="coll_id" id="coll_id" value="'.$coll_id.'" />';
    $frm_str .= '<input type="hidden" name="module" id="module" value="'.$module.'" />';
    $frm_str .= '<input type="hidden" name="req" id="req" value="second_request" />';
    $frm_str .= '<input type="hidden" id="check_days_before" value="'.$_SESSION['check_days_before'].'" />';

    $frm_str .= '<div  style="display:block">';

    //INDEXING MODELS
    $query = 'SELECT * FROM indexingmodels order by label ASC';
    $stmt = $db->query($query, array());

    $frm_str .= '<div style="display:table;width:100%;">';
    $frm_str .= '<div style="display:table-cell;vertical-align:middle;">';
    $frm_str .= '<select id="indexing_models_select" data-placeholder="'._USE_MODEL_MAILING.'" onchange="loadIndexingModel();"><option value="none"></option>';
    while ($resIndexingModels = $stmt->fetchObject()) {
        $frm_str .= '<option value="'.$resIndexingModels->id.'">'.$resIndexingModels->label.'</option>';
    }
    $frm_str .= '</select>';
    $frm_str .= '</div>';
    $frm_str .= '<div style="display:table-cell;text-align:right;vertical-align:middle;width: 12%;">';
    $frm_str .= '<a style="cursor:pointer;"><i id="action1_indexingmodels" class="fa fa-plus fa-2x" onclick="saveIndexingModel();"></i></a> <a style="cursor:pointer;"><i class="fa fa-trash-alt fa-2x" onclick="delIndexingModel();"></i></a>';
    $frm_str .= '</div>';
    $frm_str .= '</div>';
    $frm_str .= '<script>$j("#indexing_models_select").chosen({width: "100%", disable_search_threshold: 10, search_contains: true, allow_single_deselect: true});</script>';

    $frm_str .= '<hr width="90%" align="center"/>';

    $frm_str .= '<h4 onclick="new Effect.toggle(\'general_infos_div\', \'blind\', {delay:0.2});'
        .'whatIsTheDivStatus(\'general_infos_div\', \'divStatus_general_infos_div\');" '
        .'class="categorie" style="width:90%;" onmouseover="this.style.cursor=\'pointer\';">';
    $frm_str .= ' <span id="divStatus_general_infos_div" style="color:#1C99C5;"><i class="fa fa-minus-square"></i></span>&nbsp;'
        ._GENERAL_INFO;
    $frm_str .= '</h4>';
    $frm_str .= '<div id="general_infos_div"  style="display:inline">';
    $frm_str .= '<div class="ref-unit">';
    $frm_str .= '<table width="100%" align="center" border="0"  id="indexing_fields" style="display:block;">';

    //NCH01
    $frm_str .= '<tr id="attachment_tr" style="display:none'
        .';">';
    $frm_str .= '<td>'._LINK_TO_DOC.'</td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input type="radio" '
        .'name="attachment" id="attach_reconciliation" value="true" checked="checked"'
        .'onclick="show_attach(\'true\');"'
        .' /> '
        ._YES.' <input type="radio" name="attachment" id="no_attach"'
        .' value="false" '
        .'onclick="show_attach(\'false\');"'
        .' /> '
        ._NO.'</td>';
    $frm_str .= ' <td><span class="red_asterisk" id="attachment_mandatory" '
        .'style="display:inline;vertical-align:middle;"><i class="fa fa-star"></i></span></td>';
    $frm_str .= '</tr>';

    $frm_str .= '<tr id="attach_show" style="display:none;">';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td style="text-align: right;">';
    $frm_str .= '<a ';
    $frm_str .= 'href="javascript://" ';
    $frm_str .= 'onclick="window.open(';
    $frm_str .= '\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=search_adv&mode=popup&action_form=fill_input&modulename=attachments&init_search&nodetails\', ';
    $frm_str .= '\'search_doc_for_attachment\', ';
    $frm_str .= '\'scrollbars=yes,menubar=no,toolbar=no,resizable=yes,status=no,width=1100,height=775\'';
    $frm_str .= ');"';
    $frm_str .= ' title="'._SEARCH.'"';
    $frm_str .= '>';
    $frm_str .= '<span style="font-weight: bold;">';
    $frm_str .= '<i class="fa fa-link"></i>';
    $frm_str .= '</span>';
    $frm_str .= '</a>';
    $frm_str .= '</td>';
    $frm_str .= '<td style="text-align: right;">';
    $frm_str .= '<input ';
    $frm_str .= 'type="text" ';
    $frm_str .= 'name="res_id" ';
    $frm_str .= 'id="res_id" ';
    $frm_str .= 'class="readonly" ';
    $frm_str .= 'readonly="readonly" ';
    $frm_str .= 'value=""';
    $frm_str .= '/>';
    $frm_str .= '</td>';
    $frm_str .= '<td>';
    $frm_str .= '<span class="red_asterisk" id="attachment_link_mandatory" '
        .'style="display:inline;vertical-align:middle;"><i class="fa fa-star"></i></span>';
    $frm_str .= '</td>';
    $frm_str .= '</tr>';
    // END NCH01

    /*** CODE BARRE ***/
    $barcode = '';
    if(isset($data['barcode'])&& !empty($data['barcode'])) {
        $barcode = $data['barcode'];
        $frm_str .= '<tr id="barcode_tr" style="display:' . $displayValue . ';">';
        $frm_str .= '<td><label for="barcode" class="form_title" >'. _BARCODE . '</label></td>';
        $frm_str .= '<td>&nbsp;</td>';
        $frm_str .= '<td class="indexing_field"><input name="barcode" ' . 'type="text" id="barcode" value="'.$barcode.'" readonly="readonly" class="readonly"' . ' /></td>';
        $frm_str .= '<td>&nbsp;</td>';
        $frm_str .= '</tr>';
    }
    
    /*** Category ***/
    $frm_str .= '<tr id="category_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="category_id" class="form_title" >'._CATEGORY.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><select name="category_id" id="category_id" onchange="clear_error(\'frm_error_'
        .$id_action.'\');change_category(this.options[this.selectedIndex].value, \''.$display_value.'\',  \''
        .$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=change_category\',  \''
        .$_SESSION['config']['businessappurl'].'index.php?display=true&page=get_content_js\');change_category_actions(\''
        .$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=change_category_actions'
        .'&resId='.$res_id.'&collId='.$coll_id.'\',\''.$res_id.'\',\''.$coll_id.'\',this.options[this.selectedIndex].value);">';
    $frm_str .= '<option value="">'._CHOOSE_CATEGORY.'</option>';
    foreach (array_keys($_SESSION['coll_categories']['letterbox_coll']) as $cat_id) {
        if ($cat_id != 'default_category') {
            $frm_str .= '<option value="'.functions::xssafe($cat_id).'"';
            if (
                (isset($data['category_id']['value']) && $data['category_id']['value'] == $cat_id)
                || $_SESSION['coll_categories']['letterbox_coll']['default_category'] == $cat_id
                || $_SESSION['indexing']['category_id'] == $cat_id
            ) {
                $frm_str .= 'selected="selected"';
            }
            $frm_str .= '>'.functions::xssafe($_SESSION['coll_categories']['letterbox_coll'][$cat_id]).'</option>';
        }
    }
    $frm_str .= '</select></td>';
    $frm_str .= '<td><span class="red_asterisk" id="category_id_mandatory" style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span></td>';
    $frm_str .= '</tr>';
    $frm_str .= '<script>$j("#category_id").chosen({width: "226px", disable_search_threshold: 10, search_contains: true});</script>';

    /*** Doctype ***/
    $frm_str .= '<tr id="type_id_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="type_id" class="form_title" id="doctype_res" style="display:none;">'._DOCTYPE.'</label><label for="type_id" class="form_title" id="doctype_mail" style="display:inline;" >'._DOCTYPE_MAIL.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><select name="type_id" id="type_id" onchange="clear_error(\'frm_error_'.$id_action.'\');changePriorityForSve(this.options[this.selectedIndex].value,\''
        .$_SESSION['config']['businessappurl'].'index.php?display=true'
        .'&dir=indexing_searching&page=priority_for_sve\');change_doctype(this.options[this.selectedIndex].value, \''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=change_doctype\', \''._ERROR_DOCTYPE.'\', \''.$id_action.'\', \''.$_SESSION['config']['businessappurl'].'index.php?display=true&page=get_content_js\' , \''.$display_value.'\','.$res_id.', \''.$coll_id.'\')'.$func_load_listdiff_by_type.'">';
    $frm_str .= '<option value="">'._CHOOSE_TYPE.'</option>';
    if ($_SESSION['features']['show_types_tree'] == 'true') {
        for ($i = 0; $i < count($doctypes); ++$i) {
            $frm_str .= '<optgroup value="" class="' //doctype_level1
                    .$doctypes[$i]['style'].'" label="'
                    .functions::xssafe($doctypes[$i]['label']).'" >';
            for ($j = 0; $j < count($doctypes[$i]['level2']); ++$j) {
                $frm_str .= '<optgroup value="" class="' //doctype_level2
                        .$doctypes[$i]['level2'][$j]['style'].'" label="&nbsp;&nbsp;'
                        .functions::xssafe($doctypes[$i]['level2'][$j]['label']).'" >';
                for ($k = 0; $k < count($doctypes[$i]['level2'][$j]['types']);
                    ++$k
                ) {
                    if (!in_array($doctypes[$i]['level2'][$j]['types'][$k]['id'], $hidden_doctypes)) {
                        $frm_str .= '<option data-object_type="type_id" value="'.functions::xssafe($doctypes[$i]['level2'][$j]['types'][$k]['id']).'" ';
                        if (isset($data['type_id']) && !empty($data['type_id']) && $data['type_id'] == $doctypes[$i]['level2'][$j]['types'][$k]['id']) {
                            $frm_str .= ' selected="selected" ';
                        }
                        $frm_str .= ' title="'.functions::xssafe($doctypes[$i]['level2'][$j]['types'][$k]['label'])
                        .'" label="'.functions::xssafe($doctypes[$i]['level2'][$j]['types'][$k]['label'])
                        .'">&nbsp;&nbsp;&nbsp;&nbsp;'.functions::xssafe($doctypes[$i]['level2'][$j]['types'][$k]['label']).'</option>';
                    }
                }
                $frmStr .= '</optgroup>';
            }
            $frmStr .= '</optgroup>';
        }
    } else {
        for ($i = 0; $i < count($doctypes); ++$i) {
            $frm_str .= '<option value="'.functions::xssafe($doctypes[$i]['ID']).'" ';
            if (isset($data['type_id']) && !empty($data['type_id']) && $data['type_id'] == $doctypes[$i]['ID']) {
                $frm_str .= ' selected="selected" ';
            }
            $frm_str .= ' >'.functions::xssafe($doctypes[$i]['LABEL']).'</option>';
        }
    }
    $frm_str .= '</select>';
    $frm_str .= '<td><span class="red_asterisk" id="type_id_mandatory" style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span></td>';
    $frm_str .= '</tr>';
    $frm_str .= '<script>$j("#type_id").chosen({width: "226px", disable_search_threshold: 10, search_contains: true});</script>';

    /*** Object NCH01 ***/
    $frm_str .= '<tr id="title_tr" style="display:none">';
    $frm_str .= '<td><label for="title" class="form_title" >'._OBJECT.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input type="text" name="title" value="" id="title" onchange="clear_error(\'frm_error_'.$id_action.'\');"/></td>';
    $frm_str .= '<td><span class="red_asterisk" id="title_mandatory" style="display:inline;"><i class="fa fa-star"></i></span>&nbsp;</td>';
    $frm_str .= '</tr>';

    /*** Chrono number ***/
    $frm_str .= '<tr id="chrono_number_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td><label for="chrono_number" class="form_title" >'._CHRONO_NUMBER.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input type="text" name="chrono_number" value="'
        .functions::xssafe($chrono_number).'" id="chrono_number" onchange="clear_error(\'frm_error_'.$id_action.'\');"/></td>';
    $frm_str .= '<td><span class="red_asterisk" id="chrono_number_mandatory" style="display:inline;"><i class="fa fa-star"></i></span>&nbsp;</td>';
    $frm_str .= '</tr>';

    // NCH01 list of chrono number
    $frm_str .= '<tr style="display:none" id="chrono_check"><td></td></tr>';
    $frm_str .= '<tr id="list_chrono_number_tr" style="display:none">';
    $frm_str .= '<td><label for="list_chrono_number" class="form_title" >'._CHRONO_NUMBER.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field" id="list_chrono_number"></td>';
    $frm_str .= '<input type="hidden" name="hiddenChronoNumber" id="hiddenChronoNumber" value="">';
    $frm_str .= '</tr>';
    $frm_str .= '<tr style="display:none" id="chrono_number_generate"><td colspan="3" style="text-align:center">';
    $frm_str .= '<a href="#" onclick="affiche_chrono_reconciliation()">'._GENERATE_CHRONO_NUMBER.'</a>';    // NCH
    $frm_str .= '</td></tr>';

    /*** Priority ***/
    $frm_str .= '<tr id="priority_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="priority" class="form_title" >'._PRIORITY.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><select name="priority" id="priority" onChange="updateProcessDate(\''
                      .$_SESSION['config']['businessappurl'].'index.php?display=true'
                      .'&dir=indexing_searching&page=update_process_date\');" onFocus="updateProcessDate(\''
                      .$_SESSION['config']['businessappurl'].'index.php?display=true'
                      .'&dir=indexing_searching&page=update_process_date\');clear_error(\'frm_error_'.$id_action
                      .'\');">';
    $frm_str .= '<option value="">'._CHOOSE_PRIORITY.'</option>';
    for ($i = 0; $i < count($_SESSION['mail_priorities']); ++$i) {
        $frm_str .= '<option value="'.functions::xssafe($_SESSION['mail_priorities_id'][$i]).'" ';
        if (isset($data['type_id']) && $data['priority'] === $_SESSION['mail_priorities_id'][$i]) {
            $frm_str .= 'selected="selected"';
        } elseif ($data['priority'] == '' && $_SESSION['default_mail_priority'] == $i) {
            $frm_str .= 'selected="selected"';
        }
        $frm_str .= '>'.functions::xssafe($_SESSION['mail_priorities'][$i]).'</option>';
    }
    $frm_str .= '</select></td>';
    $frm_str .= '<td><span class="red_asterisk" id="priority_mandatory" style="display:inline;"><i class="fa fa-star"></i></span>&nbsp;</td>';
    $frm_str .= '</tr>';
    $frm_str .= '<script>$j("#priority").chosen({width: "226px", disable_search_threshold: 10, search_contains: true});</script>';

    /*** Confidentiality ***/
    $frm_str .= '<tr id="confidentiality_tr" style="display:'.$display_value
            .';">';
    $frm_str .= '<td><label for="confidentiality" class="form_title" >'
            ._CONFIDENTIALITY.' </label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input type="radio" '
            .'name="confidentiality" id="confidential" value="Y" />'
            ._YES.' <input type="radio" name="confidentiality" id="no_confidential"'
            .' value="N" checked="checked" />'
            ._NO.'</td>';
    $frm_str .= ' <td><span class="red_asterisk" id="confidentiality_mandatory" '
            .'style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span>&nbsp;</td>';
    $frm_str .= '</tr>';

    /*** Doc date ***/
    $frm_str .= '<tr id="doc_date_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="doc_date" class="form_title" id="mail_date_label" style="display:inline;" >'._MAIL_DATE.'</label><label for="doc_date" class="form_title" id="doc_date_label" style="display:none;" >'._DOC_DATE.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input name="doc_date" type="text" id="doc_date" value="';
    if (isset($data['doc_date']) && !empty($data['doc_date'])) {
        $frm_str .= $data['doc_date'];
    }
    $frm_str .= '" placeholder="JJ-MM-AAAA" onfocus="checkRealDate(\'docDate\');" onChange="checkRealDate(\'docDate\');" onclick="clear_error(\'frm_error_'.$id_action.'\');showCalender(this);"/></td>';
    $frm_str .= '<td><span class="red_asterisk" id="doc_date_mandatory" style="display:inline;"><i class="fa fa-star"></i></span>&nbsp;</td>';
    $frm_str .= '</tr >';    

    /*** Author ***/
    $frm_str .= '<tr id="author_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="author" class="form_title" >'._AUTHOR.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input name="author" type="text" id="author" onchange="clear_error(\'frm_error_'.$id_action.'\');"';
    if (isset($data['author']) && !empty($data['author'])) {
        $frm_str .= ' value="'.$data['author'].'" ';
    } else {
        $frm_str .= ' value="" ';
    }
    $frm_str .= '/></td>';
    $frm_str .= '<td><span class="red_asterisk" id="author_mandatory" style="display:inline;"><i class="fa fa-star"></i></span>&nbsp;</td>';
    $frm_str .= '</tr>';

    /*** Admission date ***/
    $frm_str .= '<tr id="admission_date_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="admission_date" class="form_title" >'._RECEIVING_DATE.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input name="admission_date" type="text" id="admission_date" value="';
    if (isset($data['admission_date']) && !empty($data['admission_date'])) {
        $frm_str .= $data['admission_date'];
    } else {
        $frm_str .= $creation_date;
    }
    $frm_str .= '" onclick="clear_error(\'frm_error_'.$actionId.'\');'
        .'showCalender(this);" onChange="checkRealDate(\'admissionDate\');updateProcessDate(\''
        .$_SESSION['config']['businessappurl'].'index.php?display=true'
        .'&dir=indexing_searching&page=update_process_date\');" onFocus="checkRealDate(\'admissionDate\');updateProcessDate(\''
        .$_SESSION['config']['businessappurl'].'index.php?display=true'
        .'&dir=indexing_searching&page=update_process_date\');"/></td>';
    $frm_str .= '<td><span class="red_asterisk" id="admission_date_mandatory" style="display:inline;"><i class="fa fa-star"></i></span>&nbsp;</td>';
    $frm_str .= '</tr>';
     
    /*** Reference courrier ***/
    $frm_str .= '<tr id="external_id_tr" style="display:' . $display_value . ';">';
    $frm_str .= '<td><label for="external_id" class="form_title" >' . _REFERENCE_MAIL
            . '</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input name="external_id" type="text" value="';
        if( isset($data['external_id']) && !empty($data['external_id']))
        {
            $frm_str .= $data['external_id'];
        }                    
        $frm_str .= '" id="external_id"/></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '</tr>';

    /*** Date de depart ***/
    $frm_str .= '<tr id="departure_date_tr" style="display:' . $displayValue
            . ';">';
    $frm_str .= '<td><label for="departure_date" class="form_title" >'
            . _EXP_DATE . '</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input name="departure_date" '
            . 'type="text" id="departure_date" onclick="clear_error(\'frm_error_' . $id_action . '\');'
            . 'showCalender(this);" onChange="checkRealDate(\'departure_date\');" onFocus="checkRealDate(\'departure_date\');" value="';        
            if( isset($data['departure_date']) && !empty($data['departure_date'])) {
                $frm_str .= $data['departure_date'];
            }   
            $frm_str .= '"/></td>';
    $frm_str .= '<td><span class="red_asterisk" id="departure_date_mandatory" '
            . 'style="display:inline;">*</span>&nbsp;</td>';
    $frm_str .= '</tr>';

    /*** Contact ***/
    $frm_str .= '<tr id="contact_choose_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="type_contact" class="form_title" ><span id="exp_contact_choose_label">'._SHIPPER_TYPE.'</span><span id="dest_contact_choose_label">'._DEST_TYPE.'</span></label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input type="radio" name="type_contact" id="type_contact_internal" value="internal"  class="check" onclick="clear_error(\'frm_error_'.$id_action.'\');change_contact_type(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts\', false);update_contact_type_session(\''
        .$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts_prepare_multi\');reset_check_date_exp();"';

    $frm_str .= ' /><label for="type_contact_internal">'._INTERNAL2.'</label></td></tr>';

    $frm_str .= '<tr id="contact_choose_2_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field">';
    $frm_str .= '<input type="radio" name="type_contact" class="check" id="type_contact_external" value="external" onclick="clear_error(\'frm_error_'.$id_action.'\');change_contact_type(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts\', false);update_contact_type_session(\''
        .$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts_prepare_multi\');"';
    if ($data['type_contact'] == 'external') {
        $frm_str .= ' checked="checked" ';
    }
    $frm_str .= '/><label for="type_contact_external">'._EXTERNAL.'</label></td></tr>';

    $frm_str .= '<tr id="contact_choose_3_tr" style="display:'.$displayValue.';">';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input type="radio" name="type_contact" '
        .'id="type_multi_contact_external" value="multi_external" '
        .'onclick="clear_error(\'frm_error_'.$id_action.'\');'
        .'change_contact_type(\''.$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching'
        .'&autocomplete_contacts\', true);update_contact_type_session(\''
        .$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts_prepare_multi\');"  class="check" ';
    if ($data['type_contact'] == 'multi_external') {
        $frm_str .= ' checked="checked" ';
    }
    $frm_str .= '/><label for="type_multi_contact_external">'._MULTI_CONTACT.'</label>'
        .'</td>';
    $frm_str .= '</tr>';

    $frm_str .= '<tr id="contact_id_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label" style="vertical-align:bottom;"><label for="contact" class="form_title" ><span id="exp_contact">'._SHIPPER.'</span><span id="dest_contact">'._DEST.'</span>'
        .'<span id="author_contact">'._AUTHOR_DOC.'</span>';
    if ($core->test_admin('my_contacts', 'apps', false)) {
        $pathScriptTab = $_SESSION['config']['businessappurl']
        .'index.php?display=false&dir=my_contacts&page=create_contact_iframe';
        $frm_str .= ' <a href="#" id="create_contact" title="'._CREATE_CONTACT.'" onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\'\',\''.$pathScriptTab.'\',\'create_contact\');return false;" >'
            .'<i class="fa fa-pen-square" title="'._CREATE_CONTACT.'"></i></a>';
    } else {
        $frm_str .= ' <a href="#" id="create_contact"/></a>';
    }
    $frm_str .= '</label></td>';
    $contact_mode = 'view';
    if ($core_tools->test_service('update_contacts', 'apps', false)) {
        $contact_mode = 'up';
    }
    $frm_str .= '<td style="vertical-align:bottom;"><a href="#" id="contact_card" class="fa fa-book fa-2x" title="'._CONTACT_CARD.'" onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''.urlencode(_CONTACT).'\',loadInfoContact(),\'info_contact\');return false;"'
        .' style="visibility:hidden;display:inline;" ></a>&nbsp;</td>';
    //Path to actual script
    $path_to_script = $_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=contact_check&coll_id='.$collId;
    $path_check_date_link = $_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=documents_list_mlb_search_adv&mode=popup&action_form=show_res_id&modulename=attachments&init_search&nodetails&fromContactCheck&fromValidateMail';
    //check functions on load page
    /* if (condition) {
        $frm_str.="<script>check_date_exp('".$path_to_script."');</script>";
    }*/

    $frm_str .= '<td class="indexing_field">';
    if ($data['type_contact'] == 'internal') {
        //MODIF:
        $frm_str .= ' <i class="fa fa-user" title="'._SINGLE_CONTACT.'" style="cursor:pointer;color:#135F7F;" id="type_contact_internal_icon" onclick="$j(\'#type_contact_internal\')[0].click();$j(\'#type_contact_internal_icon\').css(\'color\', \'#666\');$j(\'#type_contact_internal_icon\').css(\'color\', \'#135F7F\');$j(\'#type_multi_contact_internal_icon\').css(\'color\', \'#666\');"></i>';
    } else {
        //MODIF:
        $frm_str .= ' <i class="fa fa-user" title="'._SINGLE_CONTACT.'" style="cursor:pointer;color:#135F7F;" id="type_contact_external_icon" onclick="$j(\'#type_contact_external\')[0].click();$j(\'#type_contact_external_icon\').css(\'color\', \'#666\');$j(\'#type_contact_external_icon\').css(\'color\', \'#135F7F\');$j(\'#type_multi_contact_external_icon\').css(\'color\', \'#666\');"></i>';
    }

    $frm_str .= ' <i class="fa fa-users" title="'._MULTI_CONTACT.'" style="cursor:pointer;" id="type_multi_contact_external_icon" onclick="$j(\'#type_multi_contact_external\')[0].click();$j(\'#type_contact_internal_icon\').css(\'color\',\'#666\');$j(\'#type_contact_external_icon\').css(\'color\',\'#666\');$j(\'#type_multi_contact_external_icon\').css(\'color\',\'#135F7F\');"></i>';

    if (!empty($data['addressId'])) {
        $contactData = \Contact\models\ContactModel::getOnView(['select' => ['*'], 'where' => ['ca_id = ?'], 'data' => [$data['addressId']]]);
        $rate = \Contact\controllers\ContactController::getFillingRate(['contact' => (array)$contactData[0]]);
    }

    $frm_str .= '<span style="position:relative;"><input type="text" placeholder="'._CONTACTS_USERS_SEARCH.'" onkeyup="erase_contact_external_id(\'contact\', \'contactid\');erase_contact_external_id(\'contact\', \'addressid\');" name="contact" id="contact" onchange="clear_error(\'frm_error_'.$id_action.'\');display_contact_card(\'visible\');" onblur="display_contact_card(\'visible\');if(document.getElementById(\'type_contact_external\').checked == true){check_date_exp(\''.$path_to_script.'\',\''.$path_check_date_link.'\');}"';
    if (isset($data['contact']) && !empty($data['contact'])) {
        $frm_str .= ' value="'.$data['contact'].'" ';
    }
    if (!empty($rate['color'])) {
        $frm_str .= ' style="background-color:'.$rate['color'].'" ';
    }

    $frm_str .= ' /><div id="show_contacts" class="autocomplete autocompleteIndex" style="width:100%;left:0px;top:17px;"></div><div class="autocomplete autocompleteIndex" id="searching_autocomplete" style="display: none;text-align:left;padding:5px;left:0px;width:100%;top:17px;"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i> chargement ...</div></span></td>';
    $frm_str .= '<td><span class="red_asterisk" id="contact_mandatory" style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span></td>';
    $frm_str .= '</tr>';
    $frm_str .= '<tr style="display:none" id="contact_check"><td></td></tr>';
    $frm_str .= '<input type="hidden" id="contactid" ';
    if (isset($data['contactId']) && !empty($data['contactId'])) {
        $frm_str .= ' value="'.$data['contactId'].'" ';
    }
    $frm_str .= '/>';
    $frm_str .= '<input type="hidden" id="addressid" ';
    if (isset($data['addressId']) && !empty($data['addressId'])) {
        $frm_str .= ' value="'.$data['addressId'].'" ';
    }
    $frm_str .= '/>';
    $frm_str .= '<input type="hidden" id="contactcheck" value="success"/>';

    /****multicontact***/

    //Path to actual script
    $path_to_script = $_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=add_multi_contacts&coll_id='.$collId;

    //$_SESSION['adresses'] = '';

    $frm_str .= '<tr id="add_multi_contact_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td><label for="contact" class="form_title" >'
        .'<span id="exp_multi_contact">'._SHIPPER.'</span>'
        .'<span id="dest_multi_contact">'._DEST.'</span>';

    if ($core->test_admin('my_contacts', 'apps', false)) {
        $pathScriptTab = $_SESSION['config']['businessappurl']
            .'index.php?display=false&dir=my_contacts&page=create_contact_iframe';
        $frm_str .= ' <a href="#" id="create_multi_contact" title="'._CREATE_CONTACT
            .'" onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\'\',\''.$pathScriptTab.'\',\'create_contact\');return false;" '
            .'style="display:inline;" ><i class="fa fa-pen-square" title="'._CREATE_CONTACT.'"></i></a>';
    }
    $frm_str .= '</label></td>';
    $contact_mode = 'view';
    if ($core->test_service('update_contacts', 'apps', false)) {
        $contact_mode = 'update';
    }
    $frm_str .= '<td><a href="#" id="multi_contact_card" class="fa fa-book fa-2x" title="'._CONTACT_CARD
        .'" onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''.urlencode(_CONTACT).'\',loadInfoContact(),\'info_contact\');return false;" '
        .'style="visibility:hidden;display:inline;text-align:right;" ></a>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field">';

    //$frm_str .= '<i class="fa fa-user" title="'._INTERNAL2.'" style="cursor:pointer;" id="type_contact_internal_icon" onclick="$$(\'#type_contact_internal\')[0].click();$(\'type_contact_internal_icon\').setStyle({color: \'#135F7F\'});$(\'type_contact_external_icon\').setStyle({color: \'#666\'});$(\'type_multi_contact_external_icon\').setStyle({color: \'#666\'});"></i>';

    $frm_str .= ' <i class="fa fa-user" title="'._SINGLE_CONTACT.'" style="cursor:pointer;" id="type_contact_external_icon" onclick="$j(\'#type_contact_external\')[0].click();$j(\'#type_contact_internal_icon\').css(\'color\',\'#666\');$j(\'#type_contact_external_icon\').css(\'color\',\'#135F7F\');$j(\'#type_multi_contact_external_icon\').css(\'color\',\'#666\');"></i>';

    $frm_str .= ' <i class="fa fa-users" title="'._MULTI_CONTACT.'" style="cursor:pointer;color:#135F7F;" id="type_multi_contact_external_icon" onclick="$j(\'#type_multi_contact_external\')[0].click();$j(\'#type_contact_internal_icon\').css(\'color\',\'#666\');$j(\'#type_contact_external_icon\').css(\'color\',\'#666\');$j(\'#type_multi_contact_external_icon\').css(\'color\',\'#135F7F\');"></i>';

    $frm_str .= '<span style="position:relative;"><input type="text" name="email" id="email" placeholder="'._CONTACTS_USERS_GROUPS_SEARCH.'" onblur="clear_error(\'frm_error_'.$id_action.'\');display_contact_card(\'visible\', \'multi_contact_card\');"/>';
    $frm_str .= '<div id="multiContactList" class="autocomplete" style="left:0px;width:100%;top:17px;"></div><div class="autocomplete autocompleteIndex" id="searching_autocomplete_multi" style="display: none;text-align:left;padding:5px;left:0px;width:100%;top:17px;"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i> chargement ...</div></span>';
    $frm_str .= '<script type="text/javascript">addMultiContacts(\'email\', \'multiContactList\', \''
        .$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts\', \'Input\', \'2\', \'contactid\', \'addressid\');</script>';
    $frm_str .= '<input type="button" name="add" value="&nbsp;'._ADD
        .'&nbsp;" id="valid_multi_contact" class="button" onclick="updateMultiContacts(\''.$path_to_script
        .'&mode=adress\', \'add\', document.getElementById(\'email\').value, '
        .'\'to\', false, document.getElementById(\'addressid\').value, document.getElementById(\'contactid\').value);display_contact_card(\'hidden\', \'multi_contact_card\');" />';
    $frm_str .= '</td>';
    $frm_str .= '</tr>';
    $frm_str .= '<tr id="show_multi_contact_tr">';
    $frm_str .= '<td align="right" nowrap width="10%" id="to_multi_contact"><label>'
        ._SEND_TO_SHORT.'</label></td>';
    $frm_str .= '<td>&nbsp;</td><td style="width:200px"><div name="to" id="to" class="multicontactInput">';

    $nbContacts = count($_SESSION['adresses']['to']);

    if ($nbContacts > 0) {
        for ($icontacts = 0; $icontacts < $nbContacts; ++$icontacts) {
            $frm_str .= '<div class="multicontact_element" id="'.$icontacts.'_'.$_SESSION['adresses']['to'][$icontacts].'">'.$_SESSION['adresses']['to'][$icontacts];
            //if ($readOnly === false) {
            $frm_str .= '&nbsp;<div class="email_delete_button" id="'.$icontacts.'"'
                    .'onclick="updateMultiContacts(\''.$path_to_script
                    .'&mode=adress\', \'del\', \''.$_SESSION['adresses']['to'][$icontacts].'\', \'to\', this.id, \''.$_SESSION['adresses']['addressid'][$icontacts].'\', \''.$_SESSION['adresses']['contactid'][$icontacts].'\');" alt="'._DELETE.'" title="'
                    ._DELETE.'">x</div>';
            //}
            $frm_str .= '</div>';
        }
    }

    $frm_str .= '</div></td>';
    $frm_str .= '<td><span class="red_asterisk" id="contact_mandatory" '
                    .'style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span></td>';
    $frm_str .= '</tr>';

    if ($_SESSION['modules_loaded']['attachments']['reconciliation']['close_incoming'] == 'true') {  // NCH01 - Close incoming
        $frm_str .= '<tr style="display:none" id="close_incoming">';
        $frm_str .= '<td><label for="close_incoming_mail" class="form_title" >'._CLOSE_INCOMING.'</label></td>';
        $frm_str .= '<td>&nbsp;</td>';
        $frm_str .= '<td class="indexing_field"><input type="radio" id="close_incoming_mail" name="close_incoming_mail" value="true">  '._YES.'  ';
        $frm_str .= '<input type="radio" id="close_incoming_mail" name="close_incoming_mail" checked="checked" value="false">  '._NO.'  </td>';
        $frm_str .= '</tr>';
    }

    foreach ($resourceContacts as $resourceContact) {
        if ($resourceContact['mode'] == 'recipient' && ($data['category_id']['value'] == 'incoming' || $data['category_id']['value'] == 'internal')) {
            $sr = $resourceContact;
        } elseif ($resourceContact['mode'] == 'sender' && $data['category_id']['value'] == 'outgoing') {
            $sr = $resourceContact;
        }
    }
    $rate = [];
    if (!empty($sr['type']) && $sr['type'] == 'contact') {
        $contactData = \Contact\models\ContactModel::getOnView(['select' => ['*'], 'where' => ['ca_id = ?'], 'data' => [$sr['item_id']]]);
        $rate = \Contact\controllers\ContactController::getFillingRate(['contact' => (array)$contactData[0]]);
    }

    /*** Sender/Recipient ***/
    $frm_str .= '<tr id="sender_recipient_tr" style="display:' . $displayValue . ';">';
    $frm_str .= '<td><label for="sender_recipient" class="form_title" >';
    $frm_str .= '<span id="sr_sender_span">'._SHIPPER.'</span>';
    $frm_str .= '<span id="sr_recipient_span">'._DEST.'</span>';
    $frm_str .= '</label></td>';
    if (!empty($sr['format']) && $sr['type'] != 'entity') {
        $cardVisibility = 'visible';
    } else {
        $cardVisibility = 'hidden';
    }
    $frm_str .= '<td><a href="#" id="sender_recipient_card" class="fa fa-book fa-2x" title="'._CONTACT_CARD
    .'" onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''.urlencode(_CONTACT).'\',loadInfoContactSenderRecipient(),\'info_contact\');return false;" '
    .'style="visibility:'.$cardVisibility.';" ></a>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field">';
    $frm_str .= '<i id="sender_recipient_icon_contactsUsers" class="fa fa-user" onclick="switchAutoCompleteType(\'sender_recipient\',\'contactsUsers\', false);" style="color:#135F7F;display: inline-block;cursor:pointer;" title="'._CONTACTS_USERS_LIST.'" ></i> <i id="sender_recipient_icon_entities" class="fa fa-sitemap" onclick="switchAutoCompleteType(\'sender_recipient\',\'entities\', false);" style="display: inline-block;cursor:pointer;" title="'._ENTITIES_LIST.'" ></i>';
    $frm_str .= '<div class="typeahead__container"><div class="typeahead__field"><span class="typeahead__query">';
    $frm_str .= '<input name="sender_recipient" type="text" placeholder="'._CONTACTS_USERS_SEARCH.'" id="sender_recipient" autocomplete="off"';
    if (!empty($sr['format'])) {
        $frm_str .= ' value="'. $sr['format'].'"';
    }
    if (!empty($rate['color'])) {
        $frm_str .= ' style="background-color:'.$rate['color'].'"';
    }
    $frm_str .= '/></span></div></div>';
    $frm_str .= '</td><td>&nbsp;</td>';
    $frm_str .= '<input type="hidden" id="sender_recipient_id"';
    if (!empty($sr['item_id'])) {
        $frm_str .= 'value="'. $sr['item_id'].'"';
    }
    $frm_str .= '/>';
    $frm_str .= '<input type="hidden" id="sender_recipient_type"';
    if (!empty($sr['type'])) {
        $frm_str .= 'value="'. $sr['type'].'"';
    }

    $frm_str .= '/>';
    if ($sr['type'] == 'entity') {
        $frm_str .= '<script>$j("#sender_recipient_icon_contactsUsers").css({"color":"#666"});</script>';
        $frm_str .= '<script>$j("#sender_recipient_icon_entities").css({"color":"#135F7F"});</script>';
    } else {
        $frm_str .= '<script>$j("#sender_recipient_icon_contactsUsers").css({"color":"#135F7F"});</script>';
        $frm_str .= '<script>$j("#sender_recipient_icon_entities").css({"color":"#666"});</script>';
    }
    $frm_str .= '</tr>';

    /*** Nature ***/
    $frm_str .= '<tr id="nature_id_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="nature_id" class="form_title" >'._NATURE.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><select name="nature_id" id="nature_id" onchange="clear_error(\'frm_error_'.$id_action.'\');affiche_reference();">';
    $frm_str .= '<option value="">'._CHOOSE_NATURE.'</option>';
    foreach (array_keys($_SESSION['mail_natures']) as $nature) {
        $frm_str .= '<option value="'.functions::xssafe($nature).'"  with_reference = "'.$_SESSION['mail_natures_attribute'][$nature].'"';
        if (isset($data['nature_id']) && $data['nature_id'] == $nature) {
            $frm_str .= 'selected="selected"';
        } elseif ($data['nature_id'] == '' && $_SESSION['default_mail_nature'] == $nature) {
            $frm_str .= 'selected="selected"';
        }
        $frm_str .= '>'.functions::xssafe($_SESSION['mail_natures'][$nature]).'</option>';
    }
    $frm_str .= '</select></td>';
    $frm_str .= '<td><span class="red_asterisk" id="nature_mandatory" style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span></td>';
    $frm_str .= '</tr>';
    $frm_str .= '<script>$j("#nature_id").chosen({width: "226px", disable_search_threshold: 10, search_contains: true});</script>';

    /*** Recommande ***/
    $frm_str .= '<tr id="reference_number_tr" style="display:none;">';
    $frm_str .= '<td><label for="reference_number" class="form_title" >'._MONITORING_NUMBER.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input type="text" name="reference_number" id="reference_number"';
    if (isset($data['reference_number']) && $data['reference_number'] != '') {
        $frm_str .= 'value = "'.$data['reference_number'].'"';
    }
    $frm_str .= '/></td>';
    $frm_str .= '</tr>';

    /*** Initiator ***/
    $frm_str .= '<tr id="initiator_tr" style="display:'.$displayValue.';">';
    $frm_str .= '<td><label for="intitiator" class="form_title" >'
            ._INITIATOR.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field">'
            .'<select name="initiator" id="initiator">';
    if ($initiator) {
        $frm_str .= '<optgroup label="Service initiateur actuel">';
        $frm_str .= '<option value="'.$initiator.'">'.$ent->getentitylabel($initiator).'</option>';
        $frm_str .= '</optgroup>';
    }
    $frm_str .= '<optgroup label="Autre(s) service(s) disponible">';
    foreach ($_SESSION['user']['entities'] as $entity) {
        $frm_str .= '<option value="'.$entity['ENTITY_ID'].'"';
        if ($_SESSION['user']['primaryentity']['id'] == $entity['ENTITY_ID'] && (empty($initiator) || $initiator == null)) {
            $frm_str .= ' selected="selected" ';
        }
        $frm_str .= '>'.$entity['ENTITY_LABEL'].'</option>';
    }
    $frm_str .= '</optgroup>';
    $frm_str .= '</select>'
            .'</td>';
    $frm_str .= '<td><span class="red_asterisk" '
            .'id="initiator_mandatory" style="display:inline;"><i class="fa fa-star"></i>'
            .'</span>&nbsp;</td>';
    $frm_str .= '</tr>';
    $frm_str .= '<script>$j("#initiator").chosen({width: "226px", disable_search_threshold: 10, search_contains: true});</script>';

    /*** Subject ***/
    $frm_str .= '<tr id="subject_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="subject" class="form_title" >'._SUBJECT.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><textarea style="resize:vertical" name="subject" id="subject" rows="4" onchange="clear_error(\'frm_error_'.$id_action.'\');" >';
    if (isset($data['subject']) && !empty($data['subject'])) {
        $frm_str .= $data['subject'];
    }
    $frm_str .= '</textarea></td>';
    $frm_str .= '<td><span class="red_asterisk" id="subject_mandatory" style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span></td>';
    $frm_str .= '</tr>';

    /*** Entities : department + diffusion list ***/
    if ($core_tools->is_module_loaded('entities')) {
        $_SESSION['validStep'] = 'ok';
        $countAllEntities = count($allEntitiesTree);

        $frm_str .= '<tr id="department_tr" style="display:'.$display_value.';">';
        $frm_str .= '<td class="indexing_label"><label for="destination" class="form_title" id="label_dep_dest" style="display:inline;" >'._DEPARTMENT_DEST.'</label><label for="destination" class="form_title" id="label_dep_exp" style="display:none;" >'._DEPARTMENT_EXP.'</label><label for="destination" '.'class="form_title" id="label_dep_owner" style="display:none;" >'._DEPARTMENT_OWNER.'</label></td>';
        $frm_str .= '<td>&nbsp;</td>';
        $frm_str .= '<td class="indexing_field"><select name="destination" id="destination" onchange="clear_error(\'frm_error_'.$id_action.'\');'.$func_load_listdiff_by_entity.'">';
        $frm_str .= '<option value="">'._CHOOSE_DEPARTMENT.'</option>';

        for ($cptEntities = 0; $cptEntities < $countAllEntities; ++$cptEntities) {
            if (!$allEntitiesTree[$cptEntities]['KEYWORD']) {
                $frm_str .= '<option data-object_type="entity_id" value="'.$allEntitiesTree[$cptEntities]['ID'].'"';
                if (isset($data['destination']) && $data['destination'] == $allEntitiesTree[$cptEntities]['ID']) {
                    $frm_str .= ' selected="selected"';
                }
                if ($allEntitiesTree[$cptEntities]['DISABLED']) {
                    $frm_str .= ' disabled="disabled" class="disabled_entity"';
                } else {
                    //$frm_str .= ' style="font-weight:bold;"';
                }
                $frm_str .= '>'
                    .functions::show_string($allEntitiesTree[$cptEntities]['SHORT_LABEL'])
                    .'</option>';
            }
        }
        $frm_str .= '</select></td>';
        $frm_str .= '<td><span class="red_asterisk" id="destination_mandatory" style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span></td>';
        $frm_str .= '</tr>';
        $frm_str .= '<tr id="diff_list_tr" style="display:none;">';
        $frm_str .= '<td colspan="3">';
        $frm_str .= '<div id="diff_list_div" class="scroll_div" style="width:420px; max-width: 420px;"></div>';
        $frm_str .= '</td>';
        $frm_str .= '</tr>';
        $frm_str .= '<script>$j("#destination").chosen({width: "226px", disable_search_threshold: 10, search_contains: true});</script>';
    }

    /*** Process limit date ***/
    $frm_str .= '<tr id="process_limit_date_use_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="process_limit_date_use" class="form_title" >'._PROCESS_LIMIT_DATE_USE.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input type="radio"  class="check" name="process_limit_date_use" id="process_limit_date_use_yes" value="yes" ';
    if ($data['process_limit_date_use'] == true) {
        $frm_str .= ' checked="checked"';
    }
    $frm_str .= ' onclick="clear_error(\'frm_error_'.$id_action.'\');activate_process_date(true, \''.$display_value.'\');" />'._YES.'<input type="radio" name="process_limit_date_use"  class="check"  id="process_limit_date_use_no" value="no" onclick="clear_error(\'frm_error_'.$id_action.'\');activate_process_date(false, \''.$display_value.'\');" ';
    if (!isset($data['process_limit_date_use'])) {
        $frm_str .= ' checked="checked"';
    }
    $frm_str .= '/>'._NO.'</td>';
    $frm_str .= '<td><span class="red_asterisk" id="process_limit_date_use_mandatory" style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span></td>';
    $frm_str .= '</tr>';
    $frm_str .= '<tr id="process_limit_date_tr" style="display:'.$display_value.';">';
    $frm_str .= '<td class="indexing_label"><label for="process_limit_date" class="form_title" >'._PROCESS_LIMIT_DATE.'</label></td>';
    $frm_str .= '<td>&nbsp;</td>';
    $frm_str .= '<td class="indexing_field"><input name="process_limit_date" type="text" id="process_limit_date"  onclick="clear_error(\'frm_error_'.$id_action.'\');showCalender(this);" value="';
    if (isset($data['process_limit_date']) && !empty($data['process_limit_date'])) {
        $frm_str .= $data['process_limit_date'];
    }
    $frm_str .= '"/></td>';
    $frm_str .= '<td><span class="red_asterisk" id="process_limit_date_mandatory" style="display:inline;vertical-align:text-top"><i class="fa fa-star"></i></span></td>';
    $frm_str .= '</tr>';

    /*** Status ***/
    // Select statuses from groupbasket
    $statuses = array();

    /* Basket of ABS users */
    if ($_SESSION['current_basket']['abs_basket'] == '1') {
        $query = "SELECT group_id FROM usergroup_content WHERE user_id= ? AND primary_group='Y'";
        $stmt = $db->query($query, array($_SESSION['current_basket']['basket_owner']));
        $grp_status = $stmt->fetchObject();
        $owner_usr_grp = $grp_status->group_id;
        $owner_basket_id = str_replace('_'.$_SESSION['current_basket']['basket_owner'], '', $_SESSION['current_basket']['id']);
    } else {
        $owner_usr_grp = $_SESSION['current_basket']['group_id'];
        $owner_basket_id = $_SESSION['current_basket']['id'];
    }
    $query = 'SELECT status_id, label_status FROM groupbasket_status left join status on status_id = id '
        .' WHERE basket_id= ? and (group_id = ?) and action_id = ? ORDER BY groupbasket_status.order';
    $stmt = $db->query($query, array($owner_basket_id, $owner_usr_grp, $id_action));

    if ($stmt->rowCount() > 0) {
        while ($status = $stmt->fetchObject()) {
            $statuses[] = array(
                'ID' => $status->status_id,
                'LABEL' => functions::show_string($status->label_status),
            );
        }
    }

    if (count($statuses) > 0) {
        //load current status
        $stmt = $db->query('SELECT status FROM '
            .$view
            .' WHERE res_id = ?', array($res_id));
        $statusObj = $stmt->fetchObject();
        $current_status = $statusObj->status;
        if ($current_status != '') {
            $stmt = $db->query('SELECT label_status FROM '.STATUS_TABLE
                .' WHERE id = ?', array($current_status));
            $statusObjLabel = $stmt->fetchObject();
            $current_status_label = $statusObjLabel->label_status;
        }
        $frm_str .= '<tr id="status_tr" style="display:'.$display_value.';">';
        $frm_str .= '<td><label for="status" class="form_title" >'._STATUS
                .'</label></td>';
        $frm_str .= '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
        $frm_str .= '<td class="indexing_field"><select name="status" '
                .'id="status" onchange="clear_error(\'frm_error_'.$id_action
                .'\');">';
        if ($current_status != '') {
            $frm_str .= '<option value="'.$current_status.'">'._CHOOSE_CURRENT_STATUS
                .' : '.$current_status_label.'('.$current_status.')</option>';
        } else {
            $frm_str .= '<option value="">'._CHOOSE_CURRENT_STATUS.')</option>';
        }
        for ($i = 0; $i < count($statuses); ++$i) {
            $frm_str .= '<option value="'.functions::xssafe($statuses[$i]['ID']).'" ';
            $frm_str .= '>'.functions::xssafe($statuses[$i]['LABEL']).'</option>';
        }
        $frm_str .= '</select></td><td><span class="red_asterisk" id="market_mandatory" '
            .'style="display:inline;"><i class="fa fa-star"></i></span>&nbsp;</td>';
        $frm_str .= '</tr>';
        $frm_str .= '<script>$j("#status").chosen({width: "226px", disable_search_threshold: 10, search_contains: true});</script>';
    }

    $frm_str .= '</table>';

    $frm_str .= '</div>';
    $frm_str .= '</div>';

    /*** CUSTOM INDEXES ***/
    $frm_str .= '<div id="comp_indexes" style="display:block;">';
    $frm_str .= '</div>';

    /*** Complementary fields ***/
    $frm_str .= '<hr />';

    $frm_str .= '<h4 onclick="new Effect.toggle(\'complementary_fields\', \'blind\', {delay:0.2});'
        .'whatIsTheDivStatus(\'complementary_fields\', \'divStatus_complementary_fields\');" '
        .'class="categorie" style="width:90%;" onmouseover="this.style.cursor=\'pointer\';">';
    $frm_str .= ' <span id="divStatus_complementary_fields" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span>&nbsp;'
        ._OPT_INDEXES;
    $frm_str .= '</h4>';
    $frm_str .= '<div id="complementary_fields"  style="display:none">';
    $frm_str .= '<div>';

    $frm_str .= '<table width="100%" align="center" border="0" '
        .'id="indexing_fields" style="display:table;">';

    /*** Folder  ***/
    if ($core_tools->is_module_loaded('folder') && ($core->test_service('associate_folder', 'folder', false) == 1)) {
        //DECLARATIONS
        require_once 'modules'.DIRECTORY_SEPARATOR.'folder'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_modules_tools.php';

        //INSTANTIATE
        $folders = new folder();

        //INITIALIZE
        $folder_info = $folders->get_folders_tree('0');
        $folder = '';
        $folder_id = '';

        if (isset($data['folder']) && !empty($data['folder'])) {
            $folder = $data['folder'];
            $folder_id = str_replace(')', '', substr($folder, strrpos($folder, '(') + 1));
        }
        $frm_str .= '<tr id="folder_tr" style="display:'.$display_value.';">';
        $frm_str .= '<td><label for="folder" class="form_title" >'._FOLDER_OR_SUBFOLDER.'</label></td>';
        $frm_str .= '<td class="indexing_field" style="text-align:right;"><select id="folder" name="folder" onchange="displayFatherFolder(\'folder\')"><option value="">'._SELECT_FOLDER_TITLE.'</option>';

        foreach ($folder_info as $key => $value) {
            if ($value['folders_system_id'] == $folder_id) {
                $frm_str .= '<option selected="selected" value="'.$value['folders_system_id'].'" parent="'.$value['parent_id'].'">'.$value['folder_name'].'</option>';
            } else {
                $frm_str .= '<option value="'.$value['folders_system_id'].'" parent="'.$value['parent_id'].'">'.$value['folder_name'].'</option>';
            }
        }
        $frm_str .= '</select>';
        $frm_str .= '</td>';
        if ($core->test_service('create_folder', 'folder', false) == 1) {
            $pathScriptTab = $_SESSION['config']['businessappurl']
                    .'index.php?page=create_folder_form_iframe&module=folder&display=false';

            $frm_str .= '<td style="width:5%;"> <a href="#" id="create_folder" title="'._CREATE_FOLDER
                    .'" onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._CREATE_FOLDER.'\',\''.$pathScriptTab.'\',\'folders\');return false;" '
                    .'style="display:inline;" ><i class="fa fa-plus-circle" title="'
                    ._CREATE_FOLDER.'"></i></a></td>';
        }
        $frm_str .= '</tr>';
        $frm_str .= '<tr id="parentFolderTr" style="display: none"><td>&nbsp;</td><td colspan="2"><span id="parentFolderSpan" style="font-style: italic;font-size: 10px"></span></td></tr>';
        $frm_str .= '<script>$j("#folder").chosen({width: "226px", disable_search_threshold: 10, search_contains: true});</script>';
    }

    /*** Thesaurus ***/
    if ($core->is_module_loaded('thesaurus') && $core->test_service('thesaurus_view', 'thesaurus', false)) {
        //DECLARATIONS
        require_once 'modules'.DIRECTORY_SEPARATOR.'thesaurus'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_modules_tools.php';

        //INSTANTIATE
        $thesaurus = new thesaurus();

        //INITIALIZE
        $thesaurusListRes = array();
        $thesaurusListRes = $thesaurus->getThesaursusListRes($res_id);

        $frm_str .= '<tr id="thesaurus_tr" style="display:'.$display_value.';">';
        $frm_str .= '<td colspan="3" style="width:100%;"><label for="thesaurus" class="form_title" >'._THESAURUS.'</label></td>';
        $frm_str .= '</tr>';

        $frm_str .= '<tr id="thesaurus_tr" style="display:'.$display_value.';">';
        $frm_str .= '<td colspan="2" class="indexing_field" id="thesaurus_field" style="text-align:left;"><select multiple="multiple" style="width:100%;" id="thesaurus" data-placeholder=" "';

        if (!$core->test_service('add_thesaurus_to_res', 'thesaurus', false)) {
            $frm_str .= 'disabled="disabled"';
        }
        $frm_str .= '>';
        if (!empty($thesaurusListRes)) {
            foreach ($thesaurusListRes as $key => $value) {
                $frm_str .= '<option title="'.functions::show_string($value['LABEL']).'" data-object_type="thesaurus_id" id="thesaurus_'.$value['ID'].'"  value="'.$value['ID'].'"';
                $frm_str .= ' selected="selected"';
                $frm_str .= '>'
                        .functions::show_string($value['LABEL'])
                        .'</option>';
            }
        }
        $frm_str .= '</optgroup>';
        $frm_str .= '</select></td><td style="width:5%;"><i onclick="lauch_thesaurus_list(this);" class="fa fa-search" title="parcourir le thésaurus" aria-hidden="true" style="cursor:pointer;"></i></td>';
        $frm_str .= '</tr>';
        $frm_str .= '<script>$j("#thesaurus").chosen({width: "226px", disable_search_threshold: 10, search_contains: true});getInfoIcon();</script>';
        $frm_str .= '<style>#thesaurus_chosen{width:100% !important;}#thesaurus_chosen .chosen-drop{display:none;}</style>';

        /*****************/
    }


	/*** Description ***/
	$frm_str .= '<tr id="description_tr" style="display:' . $display_value . ';">';
	$frm_str .= '<td colspan="3">' . _OTHERS_INFORMATIONS . '</label></td>';
	$frm_str .= '</tr>';
    $frm_str .= '<tr>';
	$frm_str .= '<td class="indexing_field" colspan="2"><textarea style="width:97%;resize:vertical" name="description" '
	. 'id="description"  rows="2" onchange="clear_error(\'frm_error_'
	. $id_action . '\');" >';
	if( isset($data['subject']) && !empty($data['subject'])) {
	$frm_str .= $data['description'];
	}
	$frm_str .= '</textarea></td>';
	$frm_str .= '</tr>';

	//Departement concerne
	require_once("apps".DIRECTORY_SEPARATOR."maarch_entreprise".DIRECTORY_SEPARATOR."department_list.php");

	$frm_str .= '<tr id="department_number_tr" style="display:' . $display_value . ';">';
	$frm_str .= '<td >' . _DEPARTMENT_NUMBER . '</td>';
	$frm_str .= '<td class="indexing_field" ><input type="text" style="width:97%;" onkeyup="erase_contact_external_id(\'department_number\', \'department_number_id\');"'
	. 'name="department_number" id="department_number" value="';
	if( isset($data['department_number']) && !empty($data['department_number'])) {
	$frm_str .= $data['department_number'] . ' - ' . $depts[$data['department_number']];
	}                
	$frm_str .= '"/><div id="show_department_number" '
	. 'class="autocomplete autocompleteIndex"></div></td>';
	$frm_str .= '</tr>';
	$frm_str .= '<input type="hidden" id="department_number_id" value="';
	if( isset($data['department_number']) && !empty($data['department_number'])) {
	$frm_str .= $data['department_number'];
	}                
	$frm_str .= '"/>';
	/*****************/

    if ($core_tools->is_module_loaded('tags') && ($core_tools->test_service('tag_view', 'tags', false) == 1)) {
        //INITIALIZE
        $tags = get_value_fields($formValues, 'tag_userform');
        $tags_list = explode('__', $tags);

        include_once 'modules'.DIRECTORY_SEPARATOR.'tags'.DIRECTORY_SEPARATOR.'templates/validate_mail/index.php';
    }

    $frm_str .= '</table>';

    $frm_str .= '</div>';
    $frm_str .= '</div>';
    $frm_str .= '</div>';
    $frm_str .= '</div>';
    $frm_str .= '</div>';

    $frm_str .= '<div id="validright" style="display:none;">';

    /*** TOOLBAR ***/
    $frm_str .= '<div class="block" align="center" style="height:20px;width=95%;">';

    $frm_str .= '<table width="95%" cellpadding="0" cellspacing="0">';
    $frm_str .= '<tr align="center">';

    // HISTORY
    if ($core_tools->test_service('view_doc_history', 'apps', false) || $core->test_service('view_full_history', 'apps', false)) {
        $frm_str .= '<td>';
        $pathScriptTab = $_SESSION['config']['businessappurl']
        .'index.php?display=true&page=show_history_tab&resId='
        .$res_id.'&collId='.$coll_id;
        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._DOC_HISTORY.'\',\''.$pathScriptTab.'\',\'history\');return false;" '
            .'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= '<span id="history_tab"  class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span>';
        $frm_str .= '&nbsp;<i class="fa fa-history fa-2x" title="'._DOC_HISTORY.'"></i><sup><span style="display:none;"></span></sup>';
        $frm_str .= '</span>';
        $frm_str .= '</td>';
    }

    //NOTE
    if ($core_tools->is_module_loaded('notes')) {
        $frm_str .= '<td>';

        $pathScriptTab = $_SESSION['config']['businessappurl']
        .'index.php?display=true&module=notes&page=notes&identifier='
        .$res_id.'&origin=document&coll_id='.$coll_id.'&load&size=medium';

        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._NOTES.'\',\''.$pathScriptTab.'\',\'notes\');return false;" '
            .'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= '<span id="notes_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span>'
        .'&nbsp;<i id="notes_tab_img" class="fa fa-pen-square fa-2x" title="'._NOTES.'"></i><span id="notes_tab_badge"></span>';
        $frm_str .= '</span>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=notes&page=load_toolbar_notes&resId='.$res_id.'&collId='.$coll_id;
        $frm_str .= '<script>loadToolbarBadge(\'notes_tab\',\''.$toolbarBagde_script.'\');</script>';
        $frm_str .= '</td>';
    }

    //ATTACHMENTS
    if ($core_tools->is_module_loaded('attachments')) {
        $frm_str .= '<td>';

        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_attachments_tab&module=attachments&resId='.$res_id.'&collId='.$coll_id;

        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''.urlencode(_PJ).'\',\''.$pathScriptTab.'\',\'attachments\');return false;" '
            .'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= '<span id="attachments_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span><b>&nbsp;'
            .'<i id="attachments_tab_img" class="fa fa-paperclip fa-2x" title="'._PJ.'"></i><span id="attachments_tab_badge"></span>';
        $frm_str .= '</span>';
        $frm_str .= '</td>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=load_toolbar_attachments&resId='.$res_id.'&collId='.$coll_id;
        $frm_str .= '<script>loadToolbarBadge(\'attachments_tab\',\''.$toolbarBagde_script.'\');</script>';
    }

    //DIFFLIST HISTORY
    if ($core_tools->is_module_loaded('entities')) {
        $frm_str .= '<td>';

        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_diffListHistory_tab&module=entities&resId='.$res_id.'&collId='.$coll_id;

        $frm_str .= '<span class="diff_list_history" style="width: 90%; cursor: pointer;" onmouseover="this.style.cursor=\'pointer\';" onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._DIFF_LIST_HISTORY.'\',\''.$pathScriptTab.'\',\'difflistHistory\');return false;">';
        $frm_str .= '<span id="difflistHistory_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span>';
        $frm_str .= '<b>&nbsp;<i class="fa fa-share-alt fa-2x" title="'._DIFF_LIST_HISTORY.'"></i><sup><span style="display:none;"></span></sup></b>';
        $frm_str .= '</span>';
        $frm_str .= '</td>';
    }

    //LINKS
    $frm_str .= '<td>';

    $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_links_tab';

    $frm_str .= '<span id="to_link" onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''._LINK_TAB.'\',\''.$pathScriptTab.'\',\'links\');return false;" '
        .'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
    $frm_str .= '<span id="links_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span><b>&nbsp;'
        .'<i id="links_tab_img" class="fa fa-link fa-2x" title="'._LINK_TAB.'"></i><span id="links_tab_badge"></span>';
    $frm_str .= '</span>';

    //LOAD TOOLBAR BADGE
    $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&page=load_toolbar_links&resId='.$res_id.'&collId='.$coll_id;
    $frm_str .= '<script>loadToolbarBadge(\'links_tab\',\''.$toolbarBagde_script.'\');</script>';
    $frm_str .= '</td>';

    //CASES
    if ($core_tools->is_module_loaded('cases')) {
        $frm_str .= '<td>';

        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_case_tab&module=cases&resId='.$res_id.'&collId='.$coll_id;

        $frm_str .= '<span onclick="loadTab(\''.$res_id.'\',\''.$coll_id.'\',\''.urlencode(_CASE).'\',\''.$pathScriptTab.'\',\'cases\');return false;" '
            .'onmouseover="this.style.cursor=\'pointer\';" class="categorie" style="width:90%;">';
        $frm_str .= '<span id="cases_tab" class="tab_module" style="color:#1C99C5;"><i class="fa fa-plus-square"></i></span><b>&nbsp;'
            .'<i id="cases_tab_img" class="fa fa-suitcase fa-2x" title="'._CASE.'"></i><span id="cases_tab_badge"></span>';
        $frm_str .= '</span>';
        $frm_str .= '</td>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=cases&page=load_toolbar_cases&resId='.$res_id.'&collId='.$coll_id;
        $frm_str .= '<script>loadToolbarBadge(\'cases_tab\',\''.$toolbarBagde_script.'\');</script>';
    }

    //END TOOLBAR
    $frm_str .= '</table>';
    $frm_str .= '</div>';
    $frm_str .= '<div id =\'show_tab\' module=\'\'></div>';

    $frm_str .= '<script type="text/javascript">show_admin_contacts(true);</script>';

    //DOCUMENT VIEWER
    $frm_str .= '<iframe src="../../rest/res/'.$res_id.'/content" name="viewframevalid" id="viewframevalid"  scrolling="auto" frameborder="0" style="width:100% !important;" ></iframe>';

    //END RIGHT DIV
    $frm_str .= '</div>';

    /*** Extra javascript ***/
    $frm_str .= '<script type="text/javascript">$j(\'#validright\').css(\'display\',\'block\');displayFatherFolder(\'folder\');window.scrollTo(0,0);';

    $frm_str .= 'init_validation(\''.$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts\', \''
        .$display_value.'\', \''
        .$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=change_category\',  \''
        .$_SESSION['config']['businessappurl']
        .'index.php?display=true&page=get_content_js\');$j(\'#baskets\').css(\'visibility\',\'hidden\');var item = $j(\'#valid_div\'); if(item){item.css(\'display\',\'block\');}';
    $frm_str .= 'var type_id = $j(\'#type_id\')[0];change_category_actions(\''
        .$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=change_category_actions'
        .'&resId='.$res_id.'&collId='.$coll_id.'\',\''.$res_id.'\',\''.$coll_id.'\',document.getElementById(\'category_id\').options[document.getElementById(\'category_id\').selectedIndex].value);';
    $frm_str .= 'if(type_id){change_doctype(type_id.options[type_id.selectedIndex].value, \''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=change_doctype\', \''._ERROR_DOCTYPE.'\', \''.$id_action.'\', \''.$_SESSION['config']['businessappurl'].'index.php?display=true&page=get_content_js\' , \''.$display_value.'\', '.$res_id.', \''.$coll_id.'\', true);}';
    if ($data['process_limit_date'] == null) {
        $frm_str .= "activate_process_date(false, '".$display_value."');";
    }
    if ($core_tools->is_module_loaded('entities')) {
        if ($_SESSION['current_basket']['difflist_type'] == 'entity_id') {
            $frm_str .= 'change_entity(\''.$data['destination'].'\', \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=entities&page=load_listinstance'.'\',\'diff_list_div\', \'indexing\', \''.$display_value.'\'';
            if (!$load_listmodel) {
                $frm_str .= ',\'false\',$j(\'#category_id\').val());';
            } else {
                $frm_str .= ',\'true\',$j(\'#category_id\').val());';
            }
        } elseif ($_SESSION['current_basket']['difflist_type'] == 'type_id') {
            if (!$load_listmodel) {
                $frm_str .= 'change_entity(\''.$data['destination'].'\', \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=entities&page=load_listinstance'.'\',\'diff_list_div\', \'indexing\', \''.$display_value.'\'';
                $frm_str .= ',\'false\',$j(\'#category_id\').val());';
            } else {
                $frm_str .= 'load_listmodel('.$target_model.', \'diff_list_div\', \'indexing\', $j(\'#category_id\').val());';
                $frm_str .= '$j(\'#diff_list_tr\').css(\'display\',\''.$display_value.'\');';
            }
        } else {
            $frm_str .= 'change_entity(\''.$data['destination'].'\', \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=entities&page=load_listinstance'.'\',\'diff_list_div\', \'indexing\', \''.$display_value.'\'';
            if (!$load_listmodel) {
                $frm_str .= ',\'false\',$j(\'#category_id\').val());';
            } else {
                $frm_str .= ',\'true\',$j(\'#category_id\').val());';
            }
        }
    }
    if ($data['confidentiality'] == 'Y') {
        $frm_str .= '$j(\'#confidential\').prop("checked",true);';
    } elseif ($data['confidentiality'] == 'N') {
        $frm_str .= '$j(\'#no_confidential\').prop("checked",true);';
    }

    if ($data['type_contact'] == 'internal') {
        $frm_str .= '$j(\'#type_contact_internal\').prop("checked",true);';
    } elseif ($data['type_contact'] == 'external') {
        $frm_str .= '$j(\'#type_contact_external\').prop("checked",true);';
    }
    //Path to actual script
    $path_to_script = $_SESSION['config']['businessappurl']
    .'index.php?display=true&dir=indexing_searching&page=contact_check&coll_id='.$collId;
    //check functions on load page
    if ($data['type_contact'] != 'internal') {
        $frm_str .= "check_date_exp('".$path_to_script."','".$path_check_date_link."');";
    }
    $frm_str .='launch_autocompleter_contacts_v2(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts\', \'\', \'\', \'\', \'contactid\', \'addressid\', \''. $_SESSION['config']['businessappurl'] . 'index.php?display=true'. '&page=getDepartment\');update_contact_type_session(\''
        .$_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts_prepare_multi\');';
    $frm_str .= 'affiche_reference();';
    if (!empty($sr['type']) && $sr['type'] == 'entity') {
        $frm_str .= 'initSenderRecipientAutocomplete(\'sender_recipient\',\'entity\');';
    } else {
        $frm_str .= 'initSenderRecipientAutocomplete(\'sender_recipient\',\'contactsUsers\', false, \'sender_recipient_card\');';
    }
    $frm_str .= 'initList_hidden_input(\'department_number\', \'show_department_number\',\''
         . $_SESSION['config']['businessappurl'] . 'index.php?display='
         . 'true&page=autocomplete_department_number\','
         . ' \'Input\', \'2\', \'department_number_id\');';
    $frm_str .='</script>';
    /*** Extra CSS ***/
    $frm_str .= '<style>';
    $frm_str .= '#destination_chosen .chosen-drop{width:400px;}#folder_chosen .chosen-drop{width:400px;}';
    $frm_str .= '#modal_'.$id_action.'{height:96% !important;width:98% !important;min-width:1250px;overflow:hidden;}';
    $frm_str .= '#modal_'.$id_action.'_layer{height:100% !important;width:98% !important;min-width:1250px;overflow:hidden;}';
    $frm_str .= '#validleft{height:100% !important;width:30% !important;}';
    $frm_str .= '#validright{width:67% !important;height:100% !important;}';
    $frm_str .= '@media screen and (min-width: 1280px) {#validleft{width:447px !important;}}';
    $frm_str .= '@media screen and (max-width: 1400px) {#validright{width:57% !important;}}';
    $frm_str .= '#viewframevalid{width:100% !important;height:93% !important;}';
    $frm_str .= '#maarch_body{overflow:hidden !important;}';
    $frm_str .= '</style>';

    $frm_str .= '</div>';
    $frm_str .= '</form>';

    return addslashes($frm_str);
}

/**
 * Checks the action form.
 *
 * @param $form_id String Identifier of the form to check
 * @param $values Array Values of the form
 *
 * @return bool true if no error, false otherwise
 **/
function check_form($form_id, $values)
{
    $_SESSION['action_error'] = '';
    if (count($values) < 1 || empty($form_id)) {
        $_SESSION['action_error'] = _FORM_ERROR;

        return false;
    } else {
        $attach = get_value_fields($values, 'attach');

        if ($attach) {
            $idDoc = get_value_fields($values, 'res_id');
            if (!$idDoc || empty($idDoc)) {
                $_SESSION['action_error'] .= _LINK_REFERENCE.'<br/>';
            }
            if (!empty($_SESSION['action_error'])) {
                return false;
            }
        }

        $cat_id = get_value_fields($values, 'category_id');

        if ($cat_id == false) {
            $_SESSION['action_error'] = _CATEGORY.' '._IS_EMPTY;

            return false;
        }
        $no_error = process_category_check($cat_id, $values);

        return $no_error;
    }
}

/**
 * Checks the values of the action form for a given category.
 *
 * @param $cat_id String Category identifier
 * @param $values Array Values of the form to check
 *
 * @return bool true if no error, false otherwise
 **/
function process_category_check($cat_id, $values)
{
    //DECLARATIONS
    require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_types.php';

    //INSTANTIATE
    $db = new Database();
    $core = new core_tools();
    $type = new types();

    // If No category : Error
    if (!isset($_ENV['categories'][$cat_id])) {
        $_SESSION['action_error'] = _CATEGORY.' '._UNKNOWN.': '.$cat_id;

        return false;
    }

    // Simple cases
    for ($i = 0; $i < count($values); ++$i) {
        if ($_ENV['categories'][$cat_id][$values[$i]['ID']]['mandatory'] == true && (empty($values[$i]['VALUE']))) { //&& ($values[$i]['VALUE'] == 0 && $_ENV['categories'][$cat_id][$values[$i]['ID']]['type_form'] <> 'integer')
            $_SESSION['action_error'] = $_ENV['categories'][$cat_id][$values[$i]['ID']]['label'].' '._IS_EMPTY;

            return false;
        }
        if ($_ENV['categories'][$cat_id][$values[$i]['ID']]['type_form'] == 'date' && !empty($values[$i]['VALUE']) && preg_match($_ENV['date_pattern'], $values[$i]['VALUE']) == 0) {
            $_SESSION['action_error'] = $_ENV['categories'][$cat_id][$values[$i]['ID']]['label'].' '._WRONG_FORMAT.'';

            return false;
        }
        if ($_ENV['categories'][$cat_id][$values[$i]['ID']]['type_form'] == 'integer' && (!empty($values[$i]['VALUE']) || $values[$i]['VALUE'] == 0) && preg_match('/^[0-9]*$/', $values[$i]['VALUE']) == 0) {
            $_SESSION['action_error'] = $_ENV['categories'][$cat_id][$values[$i]['ID']]['label'].' '._WRONG_FORMAT.'';

            return false;
        }
        if ($_ENV['categories'][$cat_id][$values[$i]['ID']]['type_form'] == 'radio' && !empty($values[$i]['VALUE']) && !in_array($values[$i]['VALUE'], $_ENV['categories'][$cat_id][$values[$i]['ID']]['values'])) {
            $_SESSION['action_error'] = $_ENV['categories'][$cat_id][$values[$i]['ID']]['label'].' '._WRONG_FORMAT.'';

            return false;
        }
    }

    ///// Checks the complementary indexes depending on the doctype

    $type_id = get_value_fields($values, 'type_id');
    $coll_id = get_value_fields($values, 'coll_id');
    $indexes = $type->get_indexes($type_id, $coll_id, 'minimal');
    $val_indexes = array();
    for ($i = 0; $i < count($indexes); ++$i) {
        $val_indexes[$indexes[$i]] = get_value_fields($values, $indexes[$i]);
    }
    $test_type = $type->check_indexes($type_id, $coll_id, $val_indexes);
    if (!$test_type) {
        $_SESSION['action_error'] .= $_SESSION['error'];
        $_SESSION['error'] = '';

        return false;
    }

    ///////////////////////// Other cases
    //doc date
    /*$doc_date = get_value_fields($values, 'doc_date');
    $admission_date = get_value_fields($values, 'admission_date');
    if ($admission_date < $doc_date)
    {
        $_SESSION['action_error'] = "La date du courrier doit être antérieure à la date d'arrivée du courrier ";
        return false;
    }*/

    // Process limit Date
    $_SESSION['store_process_limit_date'] = '';
    if (isset($_ENV['categories'][$cat_id]['other_cases']['process_limit_date'])) {
        $process_limit_date_use_yes = get_value_fields($values, 'process_limit_date_use_yes');
        $process_limit_date_use_no = get_value_fields($values, 'process_limit_date_use_no');

        if ($process_limit_date_use_yes == 'yes') {
            $_SESSION['store_process_limit_date'] = 'ok';
            $process_limit_date = get_value_fields($values, 'process_limit_date');
            if (trim($process_limit_date) == '' || preg_match($_ENV['date_pattern'], $process_limit_date) == 0) {
                $_SESSION['action_error'] = $_ENV['categories'][$cat_id]['other_cases']['process_limit_date']['label'].' '._WRONG_FORMAT.'';

                return false;
            }
        } elseif ($process_limit_date_use_no == 'no') {
            $_SESSION['store_process_limit_date'] = 'ko';
        }

        $process_limit_date = new datetime($process_limit_date);
        $process_limit_date = date_add($process_limit_date, date_interval_create_from_date_string('23 hours + 59 minutes + 59 seconds'));
    }

    if (isset($_ENV['categories'][$cat_id]['priority'])) {
        $priority = get_value_fields($values, 'priority');

        if ($priority === '') {
            $_SESSION['action_error'] = $_ENV['categories'][$cat_id]['priority']['label'].' '.strtolower(_MANDATORY);

            return false;
        }
    }

    // Contact
    if (isset($_ENV['categories'][$cat_id]['other_cases']['contact'])) {
        $contact = get_value_fields($values, 'contactid');
        $contact_type = get_value_fields($values, 'type_contact_external');
        $nb_multi_contact = count($_SESSION['adresses']['to']);

        if (!$contact_type) {
            $contact_type = get_value_fields($values, 'type_contact_internal');
        }
        if (!$contact_type) {
            $contact_type = get_value_fields($values, 'type_multi_contact_external');
        }
        if (!$contact_type) {
            $_SESSION['action_error'] = $_ENV['categories'][$cat_id]['other_cases']['type_contact']['label'].' '.strtolower(_MANDATORY).'';

            return false;
        }

        $contact_field = get_value_fields($values, 'contact');

        if ($contact_field != '' && empty($contact)) {
            $_SESSION['action_error'] = $_ENV['categories'][$cat_id]['other_cases']['contact']['label']
                .' '._WRONG_FORMAT.'. '._USE_AUTOCOMPLETION;

            return false;
        }

        if ($_ENV['categories'][$cat_id]['other_cases']['contact']['mandatory'] == true) {
            if ((empty($contact) && $contact_type != 'multi_external') || ($nb_multi_contact == 0 && $contact_type == 'multi_external')) {
                $_SESSION['action_error'] = $_ENV['categories'][$cat_id]['other_cases']['contact']['label'].' '._IS_EMPTY;

                return false;
            }
        }
    }

    if ($core->is_module_loaded('entities')) {
        // Diffusion list
        if (isset($_ENV['categories'][$cat_id]['other_cases']['diff_list']) && $_ENV['categories'][$cat_id]['other_cases']['diff_list']['mandatory'] == true) {
            if (empty($_SESSION['indexing']['diff_list']['dest']['users'][0]['user_id']) || !isset($_SESSION['indexing']['diff_list']['dest']['users'][0]['user_id'])) {
                $_SESSION['action_error'] = $_ENV['categories'][$cat_id]['other_cases']['diff_list']['label'].' '.strtolower(_MANDATORY).'';

                return false;
            }
        }
    }
    if ($core->is_module_loaded('folder')) {
        $folder_id = '';
        $foldertype_id = '';

        $folder_id = get_value_fields($values, 'folder');

        if (isset($_ENV['categories'][$cat_id]['other_cases']['folder']) && $_ENV['categories'][$cat_id]['other_cases']['folder']['mandatory'] == true) {
            if (empty($folder)) {
                $_SESSION['action_error'] = $_ENV['categories'][$cat_id]['other_cases']['folder']['label'].' '._IS_EMPTY;

                return false;
            }
        }
        if (!empty($type_id) && !empty($folder_id)) {
            $stmt = $db->query('SELECT foldertype_id FROM '.$_SESSION['tablename']['fold_folders'].' WHERE folders_system_id = ?', array($folder_id));
            $res = $stmt->fetchObject();
            $foldertype_id = $res->foldertype_id;
            $stmt = $db->query('SELECT fdl.foldertype_id FROM '
                .$_SESSION['tablename']['fold_foldertypes_doctypes_level1'].' fdl, '
                .$_SESSION['tablename']['doctypes'].' d WHERE d.doctypes_first_level_id = fdl.doctypes_first_level_id and fdl.foldertype_id = ? and d.type_id = '.$type_id, array($foldertype_id));
            if ($stmt->rowCount() == 0) {
                $_SESSION['action_error'] .= _ERROR_COMPATIBILITY_FOLDER;

                return false;
            }
        }
    }

    return true;
}

/**
 * Get the value of a given field in the values returned by the form.
 *
 * @param $values Array Values of the form to check
 * @param $field String the field
 *
 * @return string the value, false if the field is not found
 **/
function get_value_fields($values, $field)
{
    $ct = 0;
    if (!empty($values) && is_array($values)) {
        $ct = count($values);
    }
    for ($i = 0; $i < $ct; ++$i) {
        if ($values[$i]['ID'] == $field) {
            return  $values[$i]['VALUE'];
        }
    }

    return false;
}

/**
 * Action of the form : update the database.
 *
 * @param $arr_id Array Contains the res_id of the document to validate
 * @param $history String Log the action in history table or not
 * @param $id_action String Action identifier
 * @param $label_action String Action label
 * @param $status String  Not used here
 * @param $coll_id String Collection identifier
 * @param $table String Table
 * @param $values_form String Values of the form to load
 **/
function manage_form($arr_id, $history, $id_action, $label_action, $status, $coll_id, $table, $values_form)
{
    //var_dump("manage_form");
    if (empty($values_form) || count($arr_id) < 1 || empty($coll_id)) {
        $_SESSION['action_error'] = _ERROR_MANAGE_FORM_ARGS;

        return false;
    }

    //INSTANTIATE require_once('core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php');
    require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_request.php';
    require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_resource.php';
    require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_types.php';

    //INSTANTIATE
    $db = new Database();
    $sec = new security();
    $core = new core_tools();
    $resource = new resource();
    $type = new types();

    //INITIALIZE
    $arrayPDOres = array();
    $arrayPDOext = array();
    $val_indexes = array();
    $table = $sec->retrieve_table_from_coll($coll_id);
    $ind_coll = $sec->get_ind_collection($coll_id);
    $cat_id = get_value_fields($values_form, 'category_id');
    $table_ext = $_SESSION['collections'][$ind_coll]['extensions'][0];
    $res_id = $arr_id[0];
    $status_id = get_value_fields($values_form, 'status');
    $type_id = get_value_fields($values_form, 'type_id');
    $indexes = $type->get_indexes($type_id, $coll_id, 'minimal');

    if ($core->is_module_loaded('tags')) {
        $tags_list = get_value_fields($values_form, 'tag_userform');
        $tags_list = explode('__', $tags_list);

        include_once 'modules'.DIRECTORY_SEPARATOR.'tags'.DIRECTORY_SEPARATOR.'tags_update.php';
    }

    //Thesaurus
    if ($core->is_module_loaded('thesaurus')) {
        require_once 'modules'.DIRECTORY_SEPARATOR.'thesaurus'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_modules_tools.php';

        $thesaurus = new thesaurus();

        $thesaurusList = get_value_fields($values_form, 'thesaurus');

        $thesaurus->updateResThesaurusList($thesaurusList, $res_id);
    }

    $query_ext = 'update '.$table_ext.' set ';
    $query_res = 'update '.$table.' set ';
    $query_ext .= ' category_id = ? ';
    $arrayPDOext = array_merge($arrayPDOext, array($cat_id));
    //$query_res .= " status = 'NEW' " ;

    // Specific indexes : values from the form
    // Simple cases
    for ($i = 0; $i < count($values_form); ++$i) {
        if ($values_form[$i]['ID'] == 'destination' && $_SESSION['ListDiffFromRedirect'] == true) {
            //fix redirect action in validate_page
        } else {
            if($values_form[$i]['ID'] != 'departure_date'){
            if ($_ENV['categories'][$cat_id][$values_form[$i]['ID']]['type_field'] == 'integer' && $_ENV['categories'][$cat_id][$values_form[$i]['ID']]['table'] != 'none') {
                if ($_ENV['categories'][$cat_id][$values_form[$i]['ID']]['table'] == 'res') {
                    $query_res .= ', '.$values_form[$i]['ID'].' = ? ';
                    $arrayPDOres = array_merge($arrayPDOres, array($values_form[$i]['VALUE']));
                } elseif ($_ENV['categories'][$cat_id][$values_form[$i]['ID']]['table'] == 'coll_ext') {
                    $query_ext .= ', '.$values_form[$i]['ID'].' = ? ';
                    $arrayPDOext = array_merge($arrayPDOext, array($values_form[$i]['VALUE']));
                }
            } elseif ($_ENV['categories'][$cat_id][$values_form[$i]['ID']]['type_field'] == 'string' && $_ENV['categories'][$cat_id][$values_form[$i]['ID']]['table'] != 'none') {
                if ($_ENV['categories'][$cat_id][$values_form[$i]['ID']]['table'] == 'res') {
                    $query_res .= ', '.$values_form[$i]['ID'].' = ?';
                    $arrayPDOres = array_merge($arrayPDOres, array($values_form[$i]['VALUE']));
                } elseif ($_ENV['categories'][$cat_id][$values_form[$i]['ID']]['table'] == 'coll_ext') {
                    $query_ext .= ', '.$values_form[$i]['ID'].' = ?';
                    $arrayPDOext = array_merge($arrayPDOext, array($values_form[$i]['VALUE']));
                }
            } elseif ($_ENV['categories'][$cat_id][$values_form[$i]['ID']]['type_field'] == 'date' && $_ENV['categories'][$cat_id][$values_form[$i]['ID']]['table'] != 'none') {
                if ($_ENV['categories'][$cat_id][$values_form[$i]['ID']]['table'] == 'res') {
                    $query_res .= ', '.$values_form[$i]['ID'].' = ?';
                    $arrayPDOres = array_merge($arrayPDOres, array($values_form[$i]['VALUE']));
                } elseif ($_ENV['categories'][$cat_id][$values_form[$i]['ID']]['table'] == 'coll_ext') {
                    $query_ext .= ', '.$values_form[$i]['ID'].' = ?';
                    $arrayPDOext = array_merge($arrayPDOext, array($values_form[$i]['VALUE']));
                }
                }
            }
        }
    }
    if (empty($status_id) || $status_id === '') {
        $status_id = 'BAD';
    } else {
        $query_res .= ', status = ?';
        $arrayPDOres = array_merge($arrayPDOres, array($status_id));
    }

    ///////////////////////// Other cases
    //$type->inits_opt_indexes($coll_id, $res_id);

    for ($i = 0; $i < count($indexes); ++$i) {
        if($indexes[$i] != 'departure_date'){
        $val_indexes[$indexes[$i]] = get_value_fields($values_form, $indexes[$i]);
        }
    }
    $query_res .= $type->get_sql_update($type_id, $coll_id, $val_indexes);

    // Confidentiality
    $confidentiality_yes = get_value_fields($values_form, 'confidential');

    if (!empty($confidentiality_yes)) {
        $query_res .= ', confidentiality = ?';
        $arrayPDOres = array_merge($arrayPDOres, array($confidentiality_yes));
    } else {
        $confidentiality_no = get_value_fields($values_form, 'no_confidential');
        $query_res .= ', confidentiality = ?';
        $arrayPDOres = array_merge($arrayPDOres, array($confidentiality_no));
    }

    // Process limit Date
    if (isset($_ENV['categories'][$cat_id]['other_cases']['process_limit_date'])) {
        $process_limit_date = get_value_fields($values_form, 'process_limit_date');
        $process_limit_date = new datetime($process_limit_date);
        $process_limit_date = date_add($process_limit_date, date_interval_create_from_date_string('23 hours + 59 minutes + 59 seconds'));
        $process_limit_date = (array) $process_limit_date;

        if ($_ENV['categories'][$cat_id]['other_cases']['process_limit_date']['table'] == 'res') {
            $query_res .= ", process_limit_date = '".$db->format_date_db($process_limit_date['date'], 'true', '', 'true')."'";
        } elseif ($_ENV['categories'][$cat_id]['other_cases']['process_limit_date']['table'] == 'coll_ext') {
            if ($_SESSION['store_process_limit_date'] == 'ok') {
                $query_ext .= ", process_limit_date = '".$db->format_date_db($process_limit_date['date'], 'true', '', 'true')."'";
            } else {
                $query_ext .= ', process_limit_date = null';
            }
            $_SESSION['store_process_limit_date'] = '';
        }
    }

    // Contact
    if (isset($_ENV['categories'][$cat_id]['other_cases']['contact'])) {
        $contact = get_value_fields($values_form, 'contact');

        $contact_type = get_value_fields($values_form, 'type_contact_external');
        if (!$contact_type) {
            $contact_type = get_value_fields($values_form, 'type_contact_internal');
        }
        if (!$contact_type) {
            $contact_type = get_value_fields($values_form, 'type_multi_contact_external');
        }
        $nb_multi_contact = count($_SESSION['adresses']['to']);

        $db->query('DELETE FROM contacts_res where res_id = ?', array($res_id));

        $db->query('UPDATE '.$table_ext
            .' SET exp_user_id = NULL, dest_user_id = NULL, exp_contact_id = NULL, dest_contact_id = NULL where res_id = ?',
        array($res_id));
        if ($nb_multi_contact > 0 && $contact_type == 'multi_external') {
            for ($icontact = 0; $icontact < $nb_multi_contact; ++$icontact) {
                $db->query('INSERT INTO contacts_res (coll_id, res_id, contact_id, address_id) VALUES (?, ?, ?, ?)',
                    array($coll_id, $res_id, $_SESSION['adresses']['contactid'][$icontact], $_SESSION['adresses']['addressid'][$icontact]));
            }
            $query_ext .= ", is_multicontacts = 'Y'";
        } else {
            $contact_id = get_value_fields($values_form, 'contactid');

            if (!ctype_digit($contact_id)) {
                $contact_type = 'internal';
            } else {
                $contact_type = 'external';
            }

            if ($contact_type == 'internal') {
                if ($cat_id == 'incoming' || $cat_id == 'internal' || $cat_id == 'ged_doc') {
                    $query_ext .= ', exp_user_id = ?';
                    $arrayPDOext = array_merge($arrayPDOext, array($contact_id));
                } elseif ($cat_id == 'outgoing') {
                    $query_ext .= ', dest_user_id = ?';
                    $arrayPDOext = array_merge($arrayPDOext, array($contact_id));
                }
                $db->query('DELETE FROM contacts_res where res_id = ?', array($res_id));
                $query_ext .= ', is_multicontacts = null';
            } elseif ($contact_type == 'external') {
                if ($cat_id == 'incoming' || $cat_id == 'ged_doc') {
                    $query_ext .= ', exp_contact_id = ?';
                    $arrayPDOext = array_merge($arrayPDOext, array($contact_id));
                } elseif ($cat_id == 'outgoing' || $cat_id == 'internal') {
                    $query_ext .= ', dest_contact_id = ?';
                    $arrayPDOext = array_merge($arrayPDOext, array($contact_id));
                }
                $addressId = get_value_fields($values_form, 'addressid');
                $query_ext .= ', address_id = ?';
                $arrayPDOext = array_merge($arrayPDOext, array($addressId));

                $db->query('DELETE FROM contacts_res where res_id = ?', array($res_id));
                $query_ext .= ', is_multicontacts = null';
            }
        }
    }

    // Sender/Recipient
    $srId = get_value_fields($values_form, 'sender_recipient_id');
    $srType = get_value_fields($values_form, 'sender_recipient_type');

    if ($cat_id == 'incoming' || $cat_id == 'internal') {
        $srMode = 'recipient';
    } else {
        $srMode = 'sender';
    }
    \Resource\models\ResourceContactModel::delete(['where' => ['res_id = ?', 'mode = ?'], 'data' => [$res_id, $srMode]]);
    if (!empty($srId) && !empty($srType) && in_array($cat_id, ['incoming', 'outgoing', 'internal'])) {
        \Resource\models\ResourceContactModel::create([
            'res_id'    => $res_id,
            'item_id'   => $srId,
            'type'      => $srType,
            'mode'      => $srMode
        ]);
    }

    if ($core->is_module_loaded('folder') && ($core->test_service('associate_folder', 'folder', false) == 1)) {
        $folder_id = get_value_fields($values_form, 'folder');

        $stmt = $db->query('SELECT folders_system_id FROM '.$table.' WHERE res_id = ?', array($res_id));
        $res = $stmt->fetchObject();
        $old_folder_id = $res->folders_system_id;

        if (!empty($folder_id)) {
            $query_res .= ', folders_system_id = ?';
            $arrayPDOres = array_merge($arrayPDOres, array($folder_id));
        } elseif (empty($folder_id) && !empty($old_folder_id)) {
            $query_res .= ', folders_system_id = NULL';
        }

        if ($folder_id != $old_folder_id && $_SESSION['history']['folderup']) {
            require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_history.php';

            $hist = new history();

            $hist->add($_SESSION['tablename']['fold_folders'], $folder_id, 'UP', 'folderup', _DOC_NUM.$res_id._ADDED_TO_FOLDER, $_SESSION['config']['databasetype'], 'apps');
            if (isset($old_folder_id) && !empty($old_folder_id)) {
                $hist->add($_SESSION['tablename']['fold_folders'], $old_folder_id, 'UP', 'folderup', _DOC_NUM.$res_id._DELETED_FROM_FOLDER, $_SESSION['config']['databasetype'], 'apps');
            }
        }
    }

    if ($core->is_module_loaded('entities') && $_SESSION['ListDiffFromRedirect'] == false) {
        // Diffusion list
        $load_list_diff = false;
        if (isset($_ENV['categories'][$cat_id]['other_cases']['diff_list'])) {
            if (!empty($_SESSION['indexing']['diff_list']['dest']['users'][0]['user_id']) && isset($_SESSION['indexing']['diff_list']['dest']['users'][0]['user_id'])) {
                $query_res .= ', dest_user = ?';
                $arrayPDOres = array_merge($arrayPDOres, array($_SESSION['indexing']['diff_list']['dest']['users'][0]['user_id']));
            }
            $load_list_diff = true;
        }
    }

    //store the initiator entity
    $initiator = get_value_fields($values_form, 'initiator');
    if (!empty($initiator)) {
        $query_res .= ', initiator = ?';
        $arrayPDOres = array_merge($arrayPDOres, array($initiator));
    } else {
        if (isset($_SESSION['user']['primaryentity']['id'])) {
            $query_res .= ', initiator = ?';
            $arrayPDOres = array_merge($arrayPDOres, array($_SESSION['user']['primaryentity']['id']));
        }
    }

    $query_res = preg_replace('/set ,/', 'set ', $query_res);
    //$query_res = substr($query_res, strpos($query_string, ','));

    $arrayPDOres = array_merge($arrayPDOres, array($res_id));
    $db->query($query_res.' where res_id = ? ', $arrayPDOres);

    $arrayPDOext = array_merge($arrayPDOext, array($res_id));
    $db->query($query_ext.' where res_id = ?', $arrayPDOext);

    if ($core->is_module_loaded('entities') && $_SESSION['ListDiffFromRedirect'] == false) {
        if ($load_list_diff) {
            require_once 'modules'.DIRECTORY_SEPARATOR.'entities'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_manage_listdiff.php';

            $diff_list = new diffusion_list();

            $params = array(
                'mode' => 'listinstance',
                'table' => $_SESSION['tablename']['ent_listinstance'],
                'coll_id' => $coll_id,
                'res_id' => $res_id,
                'user_id' => $_SESSION['user']['UserId'],
                'fromQualif' => true,
            );
            $diff_list->load_list_db($_SESSION['indexing']['diff_list'], $params);
        }
    }

    //Create chrono number
    if ($cat_id == 'outgoing') {
        require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_chrono.php';
        $queryChrono = 'SELECT alt_identifier FROM '.$table_ext
            .' WHERE res_id = ?';
        $stmt = $db->query($queryChrono, array($res_id));
        $resultChrono = $stmt->fetchObject();

        if ($resultChrono->alt_identifier == '' || $resultChrono->alt_identifier == null) {
            $chronoX = new chrono();

            $cTypeId = get_value_fields($values_form, 'type_id');
            $cEntity = get_value_fields($values_form, 'destination');
            $cChronoOut = get_value_fields($values_form, 'chrono_number');
            $myVars = array(
                'entity_id' => $cEntity,
                'type_id' => $cTypeId,
                'category_id' => $cat_id,
            );
            $myForm = array(
                'chrono_out' => $cChronoOut,
            );
            $myChrono = $chronoX->generate_chrono($cat_id, $myVars, $myForm);

            if ($myChrono != '' && $cChronoOut == '') {
                $db->query('UPDATE '.$table_ext.' SET alt_identifier = ? WHERE res_id = ? ',
                    array($myChrono, $res_id));
            }
        }
    } elseif ($cat_id == 'incoming' || $cat_id == 'internal') {
        $queryChrono = 'SELECT alt_identifier FROM '.$table_ext
            .' WHERE res_id = ?';
        $stmt = $db->query($queryChrono, array($res_id));
        $resultChrono = $stmt->fetchObject();
        if ($resultChrono->alt_identifier == '' or $resultChrono->alt_identifier == null) {
            require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_chrono.php';

            $chronoX = new chrono();

            $cTypeId = get_value_fields($values_form, 'type_id');
            $cEntity = get_value_fields($values_form, 'destination');
            $cChronoOut = get_value_fields($values_form, 'chrono_number');

            $myVars = array(
                'entity_id' => $cEntity,
                'type_id' => $cTypeId,
                'category_id' => $cat_id,
                'res_id' => $res_id,
            );
            //print_r($myVars);
            $myForm = array(
                'chrono_out' => $cChronoOut,
            );
            $myChrono = $chronoX->generate_chrono($cat_id, $myVars, $myForm);
            if ($myChrono != '') {
                $db->query('UPDATE '.$table_ext.' SET alt_identifier = ? where res_id = ?', array($myChrono, $res_id));
            }
        }
    } elseif ($cat_id == 'attachment') {
        require 'modules/attachments/add_attachments.php';                     //      NCH01
        require 'modules/attachments/remove_letterbox.php';
    }

    if ($cat_id == 'incoming') {
        if (\SrcCore\models\CurlModel::isEnabled(['curlCallId' => 'sendResourceToExternalApplication'])) {
            $bodyData = [];
            $config = \SrcCore\models\CurlModel::getConfigByCallId(['curlCallId' => 'sendResourceToExternalApplication']);

            $columnsInContact = ['external_contact_id'];
            $resource = \Resource\models\ResModel::getById(['select' => [$config['return']['value'], 'docserver_id', 'path', 'filename'], 'resId' => $res_id]);

            if (empty($resource[$config['return']['value']])) {
                if (!empty($config['inObject'])) {
                    $multipleObject = true;

                    foreach ($config['objects'] as $object) {
                        $select = [];
                        $tmpBodyData = [];
                        $getContact = false;
                        foreach ($object['rawData'] as $value) {
                            if (in_array($value, $columnsInContact)) {
                                $getContact = true;
                            } else {
                                $select[] = $value;
                            }
                        }

                        $select[] = 'address_id';
                        $document = \Resource\models\ResModel::getOnView(['select' => $select, 'where' => ['res_id = ?'], 'data' => [$res_id]]);
                        if (!empty($document[0])) {
                            if ($getContact && !empty($document[0]['address_id'])) {
                                $contact = \Contact\models\ContactModel::getOnView(['select' => $columnsInContact, 'where' => ['ca_id = ?'], 'data' => [$document[0]['address_id']]]);
                            }
                            foreach ($object['rawData'] as $key => $value) {
                                if (in_array($value, $columnsInContact)) {
                                    $tmpBodyData[$key] = '';
                                    if (!empty($contact[0][$value])) {
                                        $tmpBodyData[$key] = $contact[0][$value];
                                    }
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

                    if (!empty($config['file'])) {
                        $docserver = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $resource['docserver_id'], 'select' => ['path_template']]);
                        $bodyData[$config['file']] = \SrcCore\models\CurlModel::makeCurlFile(['path' => $docserver['path_template'] . str_replace('#', '/', $resource['path']) . $resource['filename']]);
                    }
                } else {
                    $multipleObject = false;
                    $getContact = false;

                    $select = [];
                    foreach ($config['rawData'] as $value) {
                        if (in_array($value, $columnsInContact)) {
                            $getContact = true;
                        } else {
                            $select[] = $value;
                        }
                    }

                    $select[] = 'address_id';
                    $document = \Resource\models\ResModel::getOnView(['select' => $select, 'where' => ['res_id = ?'], 'data' => [$res_id]]);
                    if (!empty($document[0])) {
                        if ($getContact) {
                            $contact = \Contact\models\ContactModel::getOnView(['select' => $columnsInContact, 'where' => ['ca_id = ?'], 'data' => [$document[0]['address_id']]]);
                        }
                        foreach ($config['rawData'] as $key => $value) {
                            if (in_array($value, $columnsInContact)) {
                                $bodyData[$key] = $contact[0][$value];
                            } else {
                                $bodyData[$key] = $document[0][$value];
                            }
                        }

                    }

                    if (!empty($config['data'])) {
                        $bodyData = array_merge($bodyData, $config['data']);
                    }
                }

                $response = \SrcCore\models\CurlModel::exec(['curlCallId' => 'sendResourceToExternalApplication', 'bodyData' => $bodyData, 'multipleObject' => $multipleObject, 'noAuth' => true]);

                \Resource\models\ResModel::update(['set' => [$config['return']['value'] => $response[$config['return']['key']]], 'where' => ['res_id = ?'], 'data' => [$res_id]]);
            }
        }
    }

    unset($_SESSION['upfile']);

    //$_SESSION['indexation'] = true;
    return array('result' => $res_id.'#', 'history_msg' => '');
}
