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
 * @brief  Advanced search form
 *
 * @file search_adv.php
 *
 * @author Claire Figueras <dev@maarch.org>
 * @author Loïc Vinet <dev@maarch.org>
 * @date $date$
 *
 * @version $Revision$
 * @ingroup indexing_searching_mlb
 */
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_request.php';
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php';
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_manage_status.php';
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'usergroups_controler.php';
require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_indexing_searching_app.php';
$core_tools = new core_tools();
$core_tools->test_user();
$core_tools->load_lang();

$_SESSION['search']['plain_text'] = '';
$_SESSION['fromContactCheck'] = '';

if (isset($_REQUEST['fromValidateMail'])) {
    $_SESSION['fromValidateMail'] = 'ok';
} else {
    $_SESSION['fromValidateMail'] = '';
}

$func = new functions();
$conn = new Database();

$search_obj = new indexing_searching_app();
$status_obj = new manage_status();
$sec = new security();
$_SESSION['indexation'] = false;

if (isset($_REQUEST['exclude'])) {
    $_SESSION['excludeId'] = $_REQUEST['exclude'];
}

$mode = 'normal';
if (isset($_REQUEST['mode']) && !empty($_REQUEST['mode'])) {
    $mode = $func->wash($_REQUEST['mode'], 'alphanum', _MODE);
}
if ($mode == 'normal') {
    $core_tools->test_service('adv_search_mlb', 'apps');
    /****************Management of the location bar  ************/
    $init = false;
    if (isset($_REQUEST['reinit']) && $_REQUEST['reinit'] == 'true') {
        $init = true;
        $_SESSION['current_search_query'] = '';
    }
    $level = '';
    if (isset($_REQUEST['level']) && ($_REQUEST['level'] == 2 || $_REQUEST['level'] == 3 || $_REQUEST['level'] == 4 || $_REQUEST['level'] == 1)) {
        $level = $_REQUEST['level'];
    }
    $page_path = $_SESSION['config']['businessappurl'].'index.php?page=search_adv&dir=indexing_searching';
    $page_label = _SEARCH_ADV_SHORT;
    $page_id = 'search_adv_mlb';
    $core_tools->manage_location_bar($page_path, $page_label, $page_id, $init, $level);
/***********************************************************/
} elseif ($mode == 'popup' || $mode == 'frame') {
    $core_tools->load_html();
    $core_tools->load_header('', true, false);
    $core_tools->load_js();
    $time = $core_tools->get_session_time_expire();
    $_SESSION['stockCheckbox'] = ''; ?>

<body>
    <div id="container" style="height:auto;">

        <div class="error" id="main_error">
            <?php functions::xecho($_SESSION['error']); ?>
        </div>
        <div class="info" id="main_info">
            <?php functions::xecho($_SESSION['info']); ?>
        </div>
        <?php
}

// load saved queries for the current user in an array
$stmt = $conn->query('SELECT query_id, query_name FROM '.$_SESSION['tablename']['saved_queries'].' WHERE user_id = ? order by query_name', array($_SESSION['user']['UserId']));
$queries = array();
while ($res = $stmt->fetchObject()) {
    array_push($queries, array('ID' => $res->query_id, 'LABEL' => $res->query_name));
}

$stmt = $conn->query('SELECT user_id, firstname, lastname, status FROM '.$_SESSION['tablename']['users']." WHERE status != 'SPD' and status != 'DEL' order by lastname asc");
$users_list = array();
while ($res = $stmt->fetchObject()) {
    array_push($users_list, array('ID' => functions::show_string($res->user_id), 'NOM' => functions::show_string($res->lastname), 'PRENOM' => functions::show_string($res->firstname), 'STATUT' => $res->status));
}

$coll_id = 'letterbox_coll';
$view = $sec->retrieve_view_from_coll_id($coll_id);
$where = $sec->get_where_clause_from_coll_id($coll_id);
if (!empty($where)) {
    $where = ' where '.$where;
}

$browser_ie = 'false';
$class_for_form = 'forms';
$hr = '';
$size = '';

// building of the parameters array used to pre-load the category list and the search elements
$param = array();

// Custom fields
$customFields = \CustomField\models\CustomFieldModel::get(['where' => ['type != ?'], 'data' => ['banAutocomplete'], 'orderBy' => ['label']]);
foreach ($customFields as $customField) {
    $field = 'indexingCustomField_'.$customField['id'];

    if (in_array($customField['type'], ['select', 'radio', 'checkbox'])) {
        $arr_tmp = array();
        array_push($arr_tmp, array('VALUE' => '', 'LABEL' => _CHOOSE.'...'));
        $customValues = json_decode($customField['values'], true);
        foreach ($customValues as $customValue) {
            array_push($arr_tmp, array('VALUE' => $customValue, 'LABEL' => $customValue));
        }
        $arr_tmp2 = array('label' => $customField['label'], 'type' => 'select_simple', 'param' => array('field_label' => $customField['label'], 'default_label' => '', 'options' => $arr_tmp));
    } elseif ($customField['type'] == 'date') {
        $arr_tmp2 = array('label' => $customField['label'], 'type' => 'date_range', 'param' => array('field_label' => $customField['label'], 'id1' => $field.'_from', 'id2' => $field.'_to'));
    } elseif ($customField['type'] == 'string') {
        $arr_tmp2 = array('label' => $customField['label'], 'type' => 'input_text', 'param' => array('field_label' => $customField['label'], 'other' => ''));
    } else {  // integer
        $arr_tmp2 = array('label' => $customField['label'], 'type' => 'num_range', 'param' => array('field_label' => $customField['label'], 'id1' => $field.'_min', 'id2' => $field.'_max'));
    }
    $param[$field] = $arr_tmp2;
}

//Coming date
$arr_tmp2 = array('label' => _DATE_START, 'type' => 'date_range', 'param' => array('field_label' => _DATE_START, 'id1' => 'admission_date_from', 'id2' => 'admission_date_to'));
$param['admission_date'] = $arr_tmp2;

//Loaded date
$arr_tmp2 = array('label' => _REG_DATE, 'type' => 'date_range', 'param' => array('field_label' => _REG_DATE, 'id1' => 'creation_date_from', 'id2' => 'creation_date_to'));
$param['creation_date'] = $arr_tmp2;

//Closing date
$arr_tmp2 = array('label' => _CLOSING_DATE, 'type' => 'date_range', 'param' => array('field_label' => _CLOSING_DATE, 'id1' => 'closing_date_from', 'id2' => 'closing_date_to'));
$param['closing_date'] = $arr_tmp2;

//Departure date
$arr_tmp2 = array('label' => _EXP_DATE, 'type' => 'date_range', 'param' => array('field_label' => _EXP_DATE, 'id1' => 'exp_date_from', 'id2' =>'exp_date_to'));
$param['exp_date'] = $arr_tmp2;

//Document date
$arr_tmp2 = array('label' => _DOC_DATE, 'type' => 'date_range', 'param' => array('field_label' => _DOC_DATE, 'id1' => 'doc_date_from', 'id2' => 'doc_date_to'));
$param['doc_date'] = $arr_tmp2;

//Process limit date
$arr_tmp2 = array('label' => _LIMIT_DATE_PROCESS, 'type' => 'date_range', 'param' => array('field_label' => _LIMIT_DATE_PROCESS, 'id1' => 'process_limit_date_from', 'id2' => 'process_limit_date_to'));
$param['process_limit_date'] = $arr_tmp2;

//Creation date pj
$arr_tmp2 = array('label' => '('._PJ.') '._CREATION_DATE, 'type' => 'date_range', 'param' => array('field_label' => '('._PJ.') '._CREATION_DATE, 'id1' => 'creation_date_pj_from', 'id2' => 'creation_date_pj_to'));
$param['creation_date_pj'] = $arr_tmp2;

//destinataire
$arr_tmp = array();
for ($i = 0; $i < count($users_list); ++$i) {
    array_push($arr_tmp, array('VALUE' => $users_list[$i]['ID'], 'LABEL' => $users_list[$i]['NOM'].' '.$users_list[$i]['PRENOM']));
}
$arr_tmp2 = array('label' => _ASSIGNEE . ' / ' . _REDACTOR, 'type' => 'select_multiple', 'param' => array('field_label' => _ASSIGNEE . ' / ' . _REDACTOR, 'label_title' => _CHOOSE_RECIPIENT_SEARCH_TITLE,
'id' => 'destinataire', 'options' => $arr_tmp, ));
$param['destinataire'] = $arr_tmp2;

//priority
$arr_tmp = array();
foreach (array_keys($_SESSION['mail_priorities']) as $priority) {
    array_push($arr_tmp, array('VALUE' => $_SESSION['mail_priorities_id'][$priority], 'LABEL' => $_SESSION['mail_priorities'][$priority]));
}
$arr_tmp2 = array('label' => _PRIORITY, 'type' => 'select_multiple', 'param' => array('field_label' => _MAIL_PRIORITY, 'default_label' => addslashes(_CHOOSE_PRIORITY), 'id' => 'priority','options' => $arr_tmp, 'label_title' => _CHOOSE_PRIORITY));
$param['priority'] = $arr_tmp2;

//Type de pièce jointe
$arr_tmp = array();
foreach (array_keys($_SESSION['attachment_types']) as $attachment_types) {
    array_push($arr_tmp, array('VALUE' => $attachment_types, 'LABEL' => $_SESSION['attachment_types'][$attachment_types]));
}
$arr_tmp2 = array('label' => '('._PJ.') '._ATTACHMENT_TYPES, 'type' => 'select_simple', 'param' => array('field_label' => '('._PJ.') '._ATTACHMENT_TYPES, 'default_label' => addslashes(_CHOOSE_ATTACHMENT_TYPE), 'options' => $arr_tmp));
$param['attachment_types'] = $arr_tmp2;

// dest
/*$arr_tmp2 = array('label' => _DEST, 'type' => 'input_text', 'param' => array('field_label' => _DEST, 'other' => $size));
$param['dest'] = $arr_tmp2;

//shipper
$arr_tmp2 = array('label' => _SHIPPER, 'type' => 'input_text', 'param' => array('field_label' => _SHIPPER, 'other' => $size));
$param['shipper'] = $arr_tmp2;
*/
if ($_SESSION['features']['search_notes'] == 'true') {
    //annotations
    $arr_tmp2 = array('label' => _NOTES, 'type' => 'textarea', 'param' => array('field_label' => _NOTES, 'other' => $size));
    $param['doc_notes'] = $arr_tmp2;
}

//tags
if ($core_tools->is_module_loaded('tags')) {
    $arr_tmptag = array();
    require_once 'modules/tags/class/TagControler.php';
    require_once 'modules/tags/tags_tables_definition.php';
    $tag = new tag_controler();
    $tag_return_value = $tag->get_all_tags($coll_id);

    if ($tag_return_value) {
        foreach ($tag_return_value as $tagelem) {
            array_push($arr_tmptag, array('VALUE' => functions::protect_string_db($tagelem['tag_id']), 'LABEL' => $tagelem['tag_label']));
        }
    } else {
        array_push($arr_tmptag, array('VALUE' => '', 'LABEL' => _TAGNONE));
    }
    $param['tag_mu'] = array('label' => _TAG_SEARCH, 'type' => 'select_multiple', 'param' => array('field_label' => _TAG_SEARCH, 'label_title' => _CHOOSE_TAG,
    'id' => 'tags', 'options' => $arr_tmptag, ));
}

//destination (department)
if ($core_tools->is_module_loaded('entities')) {
    $where = $sec->get_where_clause_from_coll_id($coll_id);
    $table = $sec->retrieve_view_from_coll_id($coll_id);
    if (empty($table)) {
        $table = $sec->retrieve_table_from_coll($coll_id);
    }
    if (!empty($where)) {
        $where = ' where '.$where;
    }

    $stmt = $conn->query('SELECT DISTINCT '.$table.'.destination, e.short_label FROM '.$table.' join '.$_SESSION['tablename']['ent_entities'].' e on e.entity_id = '.$table.'.destination 
                            '.$where.' group by e.short_label, '.$table.'.destination order by e.short_label');

    $arr_tmp = array();
    while ($res = $stmt->fetchObject()) {
        array_push($arr_tmp, array('VALUE' => $res->destination, 'LABEL' => $res->short_label));
    }

    $param['destination_mu'] = array('label' => _DESTINATION_SEARCH, 'type' => 'select_multiple', 'param' => array('field_label' => _DESTINATION_SEARCH, 'label_title' => _CHOOSE_ENTITES_SEARCH_TITLE,
    'id' => 'services', 'options' => $arr_tmp, ));
    //Initiator
    $stmt = $conn->query("SELECT DISTINCT ".$table.".initiator, e.short_label FROM ".$table." join ".$_SESSION['tablename']['ent_entities']." e on e.entity_id = ".$table.".initiator 
    ".$where." group by e.short_label, ".$table.".initiator order by e.short_label");
    
    $arr_tmp = array();
    while ($res = $stmt->fetchObject()) {
        array_push($arr_tmp, array('VALUE' => $res->initiator, 'LABEL' => $res->short_label));
    }

    $param['initiator_mu'] = array('label' => _INITIATORS, 'type' => 'select_multiple', 'param' => array('field_label' => _INITIATORS, 'label_title' => _CHOOSE_ENTITES_SEARCH_TITLE,
    'id' => 'initiatorServices', 'options' => $arr_tmp, ));
}

// Folder
$arr_tmp2 = array('label' => _PROJECT, 'type' => 'input_text', 'param' => array('field_label' => _PROJECT, 'other' => $size));
$param['folder'] = $arr_tmp2;

// Department number
$arr_tmp = array();
foreach (\Resource\controllers\DepartmentController::FRENCH_DEPARTMENTS as $key => $value) {
    array_push($arr_tmp, array('VALUE' => $key, 'LABEL' => $key . " - " . $value));
}

$param['department_number_mu'] = array('label' => _DEPARTMENT_NUMBER, 'type' => 'select_multiple', 'param' => array('field_label' => _DEPARTMENT_NUMBER, 'label_title' => _CHOOSE_DEPARTMENT_NUMBER,
'id' => 'department_number','options' => $arr_tmp));

// GED Number
$arr_tmp2 = array('label' => _N_GED, 'type' => 'input_text', 'param' => array('field_label' => _N_GED, 'other' => $size));
$param['numged'] = $arr_tmp2;

//status
$status = $status_obj->get_searchable_status();
$arr_tmp = array();
for ($i = 0; $i < count($status); ++$i) {
    array_push($arr_tmp, array('VALUE' => $status[$i]['ID'], 'LABEL' => $status[$i]['LABEL']));
}

/* TO DO : bug a corriger, ne prendre pas en compte les autres statuts selectionnes */
/*array_push($arr_tmp,  array('VALUE'=> 'REL1', 'LABEL' =>_FIRST_WARNING));
array_push($arr_tmp,  array('VALUE'=> 'REL2', 'LABEL' =>_SECOND_WARNING));
array_push($arr_tmp,  array('VALUE'=> 'LATE', 'LABEL' =>_LATE));*/

// Sorts the $param['status'] array
function cmp_status($a, $b)
{
    return strcmp(strtolower($a['LABEL']), strtolower($b['LABEL']));
}
usort($arr_tmp, 'cmp_status');
$arr_tmp2 = array('label' => _STATUS_PLUR, 'type' => 'select_multiple', 'param' => array('field_label' => _STATUS, 'label_title' => _CHOOSE_STATUS_SEARCH_TITLE, 'id' => 'status',  'options' => $arr_tmp));
$param['status'] = $arr_tmp2;

//confidentifality
$arr_tmp = array();
array_push($arr_tmp, array('VALUE' => 'Y', 'LABEL' => _YES));
array_push($arr_tmp, array('VALUE' => 'N', 'LABEL' => _NO));
$arr_tmp2 = array('label' => _CONFIDENTIALITY, 'type' => 'select_simple', 'param' => array('field_label' => _CONFIDENTIALITY, 'id' => 'confidentiality',  'options' => $arr_tmp));
$param['confidentiality'] = $arr_tmp2;

//doc_type
$stmt = $conn->query(
    'SELECT type_id, description  FROM  '
    .$_SESSION['tablename']['doctypes']." WHERE enabled = 'Y' order by description asc",
    []
);
$arr_tmp = array();
while ($res = $stmt->fetchObject()) {
    array_push($arr_tmp, array('VALUE' => $res->type_id, 'LABEL' => functions::show_string($res->description)));
}
$arr_tmp2 = array('label' => _DOCTYPES_MAIL, 'type' => 'select_multiple', 'param' => array('field_label' => _DOCTYPES_MAIL, 'label_title' => _CHOOSE_DOCTYPES_MAIL_SEARCH_TITLE, 'id' => 'doctypes', 'options' => $arr_tmp));
$param['doctype'] = $arr_tmp2;

//category
$arr_tmp = array();
array_push($arr_tmp, array('VALUE' => '', 'LABEL' => _CHOOSE_CATEGORY));
foreach (array_keys($_SESSION['coll_categories']['letterbox_coll']) as $cat_id) {
    if ($cat_id != 'default_category') {
        array_push(
            $arr_tmp,
            array(
                'VALUE' => $cat_id,
                'LABEL' => $_SESSION['coll_categories']['letterbox_coll'][$cat_id],
            )
        );
    }
}
$arr_tmp2 = array('label' => _CATEGORY, 'type' => 'select_simple', 'param' => array('field_label' => _CATEGORY, 'default_label' => '', 'options' => $arr_tmp));
$param['category'] = $arr_tmp2;

$usergroups_controler = new usergroups_controler();
$array_groups = $usergroups_controler->getAllUsergroups('', false);

//signatory group
$arr_tmp = array();
for ($iGroups = 0; $iGroups < count($array_groups); ++$iGroups) {
    array_push($arr_tmp, array('VALUE' => $array_groups[$iGroups]->group_id, 'LABEL' => $array_groups[$iGroups]->group_desc));
}
$arr_tmp2 = array('label' => _SIGNATORY_GROUP, 'type' => 'select_simple', 'param' => array('field_label' => _SIGNATORY_GROUP, 'default_label' => addslashes(_CHOOSE_GROUP), 'options' => $arr_tmp));
$param['signatory_group'] = $arr_tmp2;

//Visa user
$arr_tmp2 = array('label' => _VISA_USER_SEARCH_MIN, 'type' => 'input_text', 'param' => array('field_label' => _VISA_USER_SEARCH_MIN, 'other' => $size, 'autocompletion' => true));
$param['visa_user'] = $arr_tmp2;

// Sorts the param array
function cmp($a, $b)
{
    return strcmp(strtolower($a['label']), strtolower($b['label']));
}
uasort($param, 'cmp');

$tab = $search_obj->send_criteria_data($param);

// criteria list options
$src_tab = $tab[0];

//$core_tools->show_array($param);
?>
        <script type="text/javascript" src="<?php echo $_SESSION['config']['businessappurl']; ?>static.php?filename=search_adv.js"></script>
        <script type="text/javascript">
            <!--
            var valeurs = {
                <?php echo $tab[1]; ?> };
            var loaded_query =
                <?php if (isset($_SESSION['current_search_query']) && !empty($_SESSION['current_search_query'])) {
    echo $_SESSION['current_search_query'];
} else {
    echo '{}';
}?>
            ;

            function del_query_confirm() {
                if (confirm('<?php echo _REALLY_DELETE.' '._THIS_SEARCH.'?'; ?>')) {
                    del_query_db($('query').options[$('query').selectedIndex], 'select_criteria', 'frmsearch2', '<?php echo _SQL_ERROR; ?>', '<?php echo _SERVER_ERROR; ?>', '<?php echo $_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=manage_query'; ?>');
                    return false;
                }
            }
            -->
        </script>
        <?php if ($_GET['mode'] != 'popup') {
    ?>
        <h1>
            <i class="fa fa-search fa-2x"></i> <?php echo _ADV_SEARCH_MLB; ?>
        </h1>
        <?php
} ?>
        <div id="inner_content">

            <?php if (count($queries) > 0) {
        ?>
            <form name="choose_query" id="choose_query" action="#" method="post">
                <div align="center" style="display:block;" id="div_query">

                    <label for="query"><?php echo _MY_SEARCHES; ?> : </label>
                    <select name="query" id="query" onchange="load_query_db(this.options[this.selectedIndex].value, 'select_criteria', 'parameters_tab', '<?php echo _SQL_ERROR; ?>', '<?php echo _SERVER_ERROR; ?>', '<?php echo $_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=manage_query'; ?>');return false;">
                        <option id="default_query" value=""><?php echo _CHOOSE_SEARCH; ?>
                        </option>
                        <?php for ($i = 0; $i < count($queries); ++$i) {
            ?>
                        <option value="<?php functions::xecho($queries[$i]['ID']); ?>" id="query_<?php functions::xecho($queries[$i]['ID']); ?>"><?php functions::xecho($queries[$i]['LABEL']); ?>
                        </option><?php
        } ?>
                    </select>

                    <input name="del_query" id="del_query" value="<?php echo _DELETE_QUERY; ?>" type="button" onclick="del_query_confirm();" class="button" style="display:none" />
                </div>
            </form>
            <?php
    } ?>
            <form name="frmsearch2" method="post" action="<?php
    if ($mode == 'normal') {
        //echo $_SESSION['config']['businessappurl'] . 'index.php';
        echo $_SESSION['config']['businessappurl']
            .'index.php?display=true&dir=indexing_searching&page=search_adv_result';
    } elseif ($mode == 'frame' || $mode == 'popup') {
        echo $_SESSION['config']['businessappurl']
            .'index.php?display=true&dir=indexing_searching&page=search_adv_result';
    }?>" id="frmsearch2" class="<?php functions::xecho($class_for_form); ?>">
                <input type="hidden" name="dir" value="indexing_searching" />
                <input type="hidden" name="page" value="search_adv_result" />
                <input type="hidden" name="mode" value="<?php functions::xecho($mode); ?>" />
                <?php if ($mode == 'frame' || $mode == 'popup') {
        ?>
                <input type="hidden" name="display" value="true" />
                <input type="hidden" name="action_form" value="<?php functions::xecho($_REQUEST['action_form']); ?>" />
                <input type="hidden" name="modulename" value="<?php functions::xecho($_REQUEST['modulename']); ?>" />
                <?php
    }
if (isset($_REQUEST['nodetails'])) {
    ?>
                <input type="hidden" name="nodetails" value="true" />
                <?php
}?>
                <table align="center" border="0" width="100%">
                    <tr>
                        <td>
                            <a href="#" onclick="clear_search_form('frmsearch2','select_criteria');clear_q_list();erase_contact_external_id('recipient', 'recipient_id');erase_contact_external_id('recipient', 'recipient_type');erase_contact_external_id('sender', 'sender_id');erase_contact_external_id('sender', 'sender_type');erase_contact_external_id('signatory_name', 'ac_signatory_name');">
                                <i class="fa fa-sync fa-4x" title="<?php echo _CLEAR_SEARCH; ?>"></i>
                            </a>
                        </td>
                        <td align="right">
                            <span style="display:none;">
                                <input name="imageField" type="submit" value="" onclick="valid_search_form('frmsearch2');this.form.submit();" />
                            </span>
                            <a href="#" onclick="valid_search_form('frmsearch2');$('frmsearch2').submit();">
                                <i class="fa fa-search fa-4x" title="<?php echo _SEARCH; ?>"></i>
                            </a>
                        </td>
                    </tr>
                </table>
                <table align="center" border="0" width="100%">
                    <tr>
                        <td>
                            <?php
if ($core_tools->is_module_loaded('basket') == true) {
        ?>
                            <div class="block">
                                <h2><?php echo _SEARCH_SCOPE; ?>
                                </h2>

                                <div class="adv_search_field_content">
                                    <div class="adv_search_field">
                                        <label for="baskets_clause" class="bold"><?php echo _SPREAD_SEARCH_TO_BASKETS; ?> : </label>
                                    </div>
                                    <div class="adv_search_field">
                                        <input type="hidden" name="meta[]" value="baskets_clause#baskets_clause#select_simple" />
                                        <select name="baskets_clause" id="baskets_clause">
                                            <option id="true" value="true"><?php echo _ALL_BASKETS; ?>
                                            </option>
                                            <?php
                        $aSearchBasket = [];
        if ($_REQUEST['mode'] != 'popup') {
            for ($i = 0; $i < count($_SESSION['user']['baskets']); ++$i) {
                if ($_SESSION['user']['baskets'][$i]['coll_id'] == $coll_id
                                    && $_SESSION['user']['baskets'][$i]['id'] != 'EmailsToQualify'
                                    && $_SESSION['user']['baskets'][$i]['id'] != 'InitBasket'
                                    && $_SESSION['user']['baskets'][$i]['id'] != 'RetourCourrier'
                                    && $_SESSION['user']['baskets'][$i]['id'] != 'QualificationBasket'
                                    && empty($aSearchBasket[$_SESSION['user']['baskets'][$i]['id']])) {
                    ?>
                                            <option id="<?php echo functions::xecho($_SESSION['user']['baskets'][$i]['id']); ?>" value="<?php echo functions::xecho($_SESSION['user']['baskets'][$i]['id']); ?>">[<?php echo _BASKET; ?>] <?php echo functions::xecho($_SESSION['user']['baskets'][$i]['name']); ?>
                                            </option>';
                                            <?php
                                    $aSearchBasket[$_SESSION['user']['baskets'][$i]['id']] = true;
                }
            }
        } ?>
                                        </select>
                                    </div>
                                    <div class="adv_search_field">
                                        <em><?php echo _SEARCH_SCOPE_HELP; ?></em>
                                    </div>
                                </div>
                            </div>
                            <?php
    }
?>
                        </td>
                    </tr>
                </table>
                <table align="center" border="0" width="100%">
                    <tr>
                        <td colspan="2"></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="block">
                                <h2><?php echo _LETTER_INFO; ?>
                                </h2>
                                <div class="adv_search_field_content">
                                    <div class="adv_search_field">
                                        <label for="subject" class="bold"><?php echo _MAIL_OBJECT; ?></label>
                                    </div>
                                    <div class="adv_search_field">
                                        <input type="text" name="subject" id="subject" <?php functions::xecho($size); ?> />
                                        <input type="hidden" name="meta[]" value="subject#subject#input_text" /><span class="green_asterisk"><i class="fa fa-star"></i></span>
                                    </div>
                                    <div class="adv_search_field">
                                        <em><?php echo _MAIL_OBJECT_HELP; ?></em>
                                    </div>
                                </div>
                                <div class="adv_search_field_content">
                                    <div class="adv_search_field">
                                        <label for="chrono" class="bold"><?php echo _CHRONO_NUMBER;?></label>
                                    </div>
                                    <div class="adv_search_field">
                                        <input type="text" name="chrono" id="chrono" <?php functions::xecho($size); ?> />
                                        <input type="hidden" name="meta[]" value="chrono#chrono#input_text" /><span class="green_asterisk"><i class="fa fa-star"></i></span>
                                    </div>
                                    <div class="adv_search_field">
                                        <em><?php echo _CHRONO_NUMBER_HELP; ?></em>
                                    </div>
                                </div>
                                <div class="adv_search_field_content">
                                    <div class="adv_search_field">
                                        <label for="barcode" class="bold"><?php echo _BARCODE;?></label>
                                    </div>
                                    <div class="adv_search_field">
                                        <input type="text" name="barcode" id="barcode" <?php echo $size; ?> />
                                        <input type="hidden" name="meta[]" value="barcode#barcode#input_text" />
                                    </div>
                                    <div class="adv_search_field">
                                        <em><?php echo _BARCODE_HELP; ?></em>
                                    </div>
                                </div>
                                <div class="adv_search_field_content">
                                    <div class="adv_search_field">
                                        <label for="sender" class="bold"><?php echo _SENDER; ?></label>
                                    </div>
                                    <div class="adv_search_field indexing_field">
                                        <span style="position:relative;">
                                            <div class="typeahead__container">
                                                <div class="typeahead__field">
                                                    <span class="typeahead__query">
                                                        <input name="sender" type="text" id="sender" autocomplete="off" placeholder="<?php echo _CONTACTS_USERS_SEARCH; ?>" title="<?php echo _CONTACTS_USERS_SEARCH; ?>" />
                                                        <input type="hidden" name="meta[]" value="sender#sender#input_text" />
                                                    </span>
                                                </div>
                                            </div>
                                        </span>
                                        <script type="text/javascript">
                                            initSenderRecipientAutocomplete('sender', 'contactsUsers', true);
                                        </script>
                                        <input type="hidden" name="sender_id" id="sender_id" />
                                        <input type="hidden" name="sender_type" id="sender_type" />
                                    </div>
                                    <div class="adv_search_field">
                                        <em><?php echo _SEARCH_CONTACT_AUTOCOMPLETION_HELP; ?></em>
                                    </div>
                                </div>
                                <div class="adv_search_field_content">
                                    <div class="adv_search_field">
                                        <label for="recipient" class="bold"><?php echo _DEST; ?></label>
                                    </div>
                                    <div class="adv_search_field indexing_field">
                                        <span style="position:relative;">
                                            <div class="typeahead__container">
                                                <div class="typeahead__field">
                                                    <span class="typeahead__query">
                                                        <input name="recipient" type="text" id="recipient" autocomplete="off" placeholder="<?php echo _CONTACTS_USERS_SEARCH; ?>" title="<?php echo _CONTACTS_USERS_SEARCH; ?>" />
                                                        <span class="green_asterisk" style="position: absolute;right: -10px;top: 0px;"><i class="fa fa-star"></i></span>
                                                        <input type="hidden" name="meta[]" value="recipient#recipient#input_text" />
                                                    </span>
                                                </div>
                                            </div>
                                        </span>
                                        <script type="text/javascript">
                                            initSenderRecipientAutocomplete('recipient', 'contactsUsers', true);
                                        </script>
                                        <input type="hidden" name="recipient_id" id="recipient_id" />
                                        <input type="hidden" name="recipient_type" id="recipient_type" />
                                    </div>
                                    <div class="adv_search_field">
                                        <em><?php echo _SEARCH_CONTACT_AUTOCOMPLETION_HELP; ?></em>
                                    </div>
                                </div>
                                <div class="adv_search_field_content">
                                    <div class="adv_search_field">
                                        <label for="signatory_name" class="bold"><?php echo _SIGNATORY_NAME;?></label>
                                    </div>
                                    <div class="adv_search_field">
                                        <input type="text" name="signatory_name" id="signatory_name" onkeyup="erase_contact_external_id('signatory_name', 'ac_signatory_name');" />
                                        <input type="hidden" name="meta[]" value="signatory_name#signatory_name#input_text" />
                                        <div id="signatoryNameList" class="autocomplete"></div>
                                        <script type="text/javascript">
                                            initList_hidden_input('signatory_name', 'signatoryNameList', '<?php
                        echo $_SESSION['config']['businessappurl'];?>index.php?display=true&dir=indexing_searching&page=users_list_by_name_search', 'what', '2', 'ac_signatory_name');
                                        </script>
                                        <input id="ac_signatory_name" name="ac_signatory_name" type="hidden" />
                                    </div>
                                    <div class="adv_search_field">

                                    </div>
                                </div>
                                <div class="adv_search_field_content">
                                    <div class="adv_search_field">
                                        <label for="fulltext" class="bold"><?php echo _FULLTEXT; ?></label>
                                    </div>
                                    <div class="adv_search_field">
                                        <input type="text" name="fulltext" id="fulltext" <?php functions::xecho($size); ?> />
                                        <input type="hidden" name="meta[]" value="fulltext#fulltext#input_text" />
                                        <a href="javascript::" onclick='$j("#iframe_fulltext_help").slideToggle("fast");'><i class="fa fa-search" title="<?php echo _HELP_FULLTEXT_SEARCH; ?>"></i></a>
                                    </div>
                                    <div class="adv_search_field">
                                        <em><?php echo _FULLTEXT_HELP; ?></em>
                                    </div>
                                </div>
                                <div class="adv_search_field_content" id="iframe_fulltext_help" style="display:none;">
                                    <iframe src="<?php echo $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=fulltext_search_help'; ?>" frameborder="0" width="100%" height="240px">
                                    </iframe>
                                </div>
                                <div class="adv_search_field_content">
                                    <div class="adv_search_field">
                                        <label for="multifield" class="bold"><?php echo _MULTI_FIELD; ?></label>
                                    </div>
                                    <div class="adv_search_field">
                                        <input type="text" name="multifield" id="multifield" <?php functions::xecho($size); ?> />
                                        <input type="hidden" name="meta[]" value="multifield#multifield#input_text" />
                                    </div>
                                    <div class="adv_search_field">
                                        <em><?php echo _MULTI_FIELD_HELP; ?></em>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="green_asterisk"><i class="fa fa-star" style="vertical-align:50%"></i></span><?php echo _SEARCH_INDICATION; ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>
                            <div class="block">
                                <h2><?php echo _ADD_PARAMETERS; ?>&nbsp;:&nbsp;<select name="select_criteria" id="select_criteria" style="display:inline;" onchange="add_criteria(this.options[this.selectedIndex].id, 'parameters_tab', <?php
        echo $browser_ie; ?>, '<?php echo _ERROR_IE_SEARCH; ?>');window.location.href = '#bottom';">
                                        <?php echo $src_tab; ?>
                                    </select></h2>
                                <table border="0" width="100%" class="content" id="parameters_tab">
                                    <tr>
                                        <td width="100%" colspan="3" style="text-align:center;"><em><?php echo _ADD_PARAMETERS_HELP; ?></em></td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>

                <table align="center" border="0" width="100%">
                    <tr>
                        <td>
                            <a href="#" onclick="clear_search_form('frmsearch2','select_criteria');clear_q_list();erase_contact_external_id('recipient', 'recipient_id');erase_contact_external_id('recipient', 'recipient_type');erase_contact_external_id('sender', 'sender_id');erase_contact_external_id('sender', 'sender_type');erase_contact_external_id('signatory_name', 'ac_signatory_name');">
                                <i class="fa fa-sync fa-4x" title="<?php echo _CLEAR_FORM; ?>"></i>
                            </a>
                        </td>
                        <td align="right">
                            <a href="#" onclick="valid_search_form('frmsearch2');$('frmsearch2').submit();">
                                <i class="fa fa-search fa-4x" title="<?php echo _SEARCH; ?>"></i>
                            </a>
                        </td>
                    </tr>
                </table>

            </form>
            <br />
        </div>

        <script type="text/javascript">
            load_query(valeurs, loaded_query, 'parameters_tab', '<?php echo $browser_ie; ?>', '<?php echo _ERROR_IE_SEARCH; ?>');
            <?php if (isset($_REQUEST['init_search'])) {
            ?>
            clear_search_form('frmsearch2', 'select_criteria');
            clear_q_list();
            erase_contact_external_id('recipient', 'recipient_id');
            erase_contact_external_id('recipient', 'recipient_type');
            erase_contact_external_id('sender', 'sender_id');
            erase_contact_external_id('sender', 'sender_type');
            erase_contact_external_id('signatory_name', 'ac_signatory_name')
            <?php
        }?>
        </script>

        <?php if ($mode == 'popup' || $mode == 'frame') {
            echo '</div>';
            if ($mode == 'popup') {
                ?><br />
        <div align="center"><input type="button" name="close" class="button" value="<?php echo _CLOSE_WINDOW; ?>" onclick="self.close();" /></div> <?php
            }
            echo '</body></html>';
        }
