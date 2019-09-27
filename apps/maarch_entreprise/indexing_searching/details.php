<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   details
*
* @author  dev <dev@maarch.org>
* @ingroup indexing_searching
*/

//DECLARATIONS
require_once 'core/manage_bitmask.php';
require_once 'core/class/class_request.php';
require_once 'core/class/class_security.php';
require_once 'apps/'.$_SESSION['config']['app_id'].'/security_bitmask.php';
require_once 'apps/'.$_SESSION['config']['app_id'].'/class/class_list_show.php';
require_once 'core/class/class_history.php';
require_once 'core/class/LinkController.php';
require_once 'apps/'.$_SESSION['config']['app_id'].'/class/class_indexing_searching_app.php';
require_once 'apps/'.$_SESSION['config']['app_id'].'/class/class_types.php';
if (file_exists(
    $_SESSION['config']['corepath'].'custom/apps/'.$_SESSION['config']['app_id']
    .'/definition_mail_categories.php'
)
) {
    $path = $_SESSION['config']['corepath'].'custom/apps/'.$_SESSION['config']['app_id']
          .'/definition_mail_categories.php';
} else {
    $path = 'apps/'.$_SESSION['config']['app_id'].'/definition_mail_categories.php';
}
include_once $path;

//INSTANTIATE
$core = new core_tools();
$hist = new history();
$security = new security();
$func = new functions();
$request = new request();
$type = new types();
$is = new indexing_searching_app();
$db = new Database();

//INITIALIZE
$core->test_user();
$core->load_lang();
$_SESSION['basket_used'] = $_SESSION['current_basket']['id'];
if (!isset($_REQUEST['coll_id'])) {
    $_REQUEST['coll_id'] = '';
}
$_SESSION['doc_convert'] = array();
$_SESSION['stockCheckbox'] = '';
$_SESSION['save_list']['fromDetail'] = 'true';
$s_id = '';
$_SESSION['req'] = 'details';
$_SESSION['indexing'] = array();
$coll_id = '';
$table = '';
if (!isset($_REQUEST['coll_id']) || empty($_REQUEST['coll_id'])) {
    //$_SESSION['error'] = _COLL_ID.' '._IS_MISSING;
    $coll_id = $_SESSION['collections'][0]['id'];
    $table = $_SESSION['collections'][0]['view'];
    $is_view = true;
} else {
    $coll_id = $_REQUEST['coll_id'];
    $table = $security->retrieve_view_from_coll_id($coll_id);
    $is_view = true;
    if (empty($table)) {
        $table = $security->retrieve_table_from_coll($coll_id);
        $is_view = false;
    }
}
$_SESSION['collection_id_choice'] = $coll_id;
$_SESSION['current_basket']['coll_id'] = $coll_id;
$idCourrier = $_GET['id'];

//test service print_details
$printDetails = false;
if ($core->test_service('print_details', 'apps', false)) {
    $printDetails = true;
}
//test service put_in_validation
$putInValid = false;
if ($core->test_service('put_in_validation', 'apps', false)) {
    $putInValid = true;
}
//test service view technical infos
$viewTechnicalInfos = false;
if ($core->test_service('view_technical_infos', 'apps', false)) {
    $viewTechnicalInfos = true;
}

//test service view doc history
$viewDocHistory = false;
if ($core->test_service('view_doc_history', 'apps', false) || $core->test_service('view_full_history', 'apps', false)) {
    $viewDocHistory = true;
}

//test service add new version
$addNewVersion = false;
if ($core->test_service('add_new_version', 'apps', false)) {
    $addNewVersion = true;
}

/****************Management of the location bar  ************/
$init = false;
if (isset($_REQUEST['reinit']) && $_REQUEST['reinit'] == 'true') {
    $init = true;
}
if (isset($_SESSION['indexation']) && $_SESSION['indexation'] == true) {
    $init = true;
}
$level = '';
if (
    isset($_REQUEST['level'])
    && (
        $_REQUEST['level'] == 2
        || $_REQUEST['level'] == 3
        || $_REQUEST['level'] == 4
        || $_REQUEST['level'] == 1
    )
) {
    $level = $_REQUEST['level'];
}
$page_path = $_SESSION['config']['businessappurl']
    .'index.php?page=details&dir=indexing_searching&coll_id='
    .$_REQUEST['coll_id']
    .'&id='.$_REQUEST['id'];
$page_label = _DETAILS;
$page_id = 'details';
$core->manage_location_bar($page_path, $page_label, $page_id, $init, $level);
/***********************************************************/

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $s_id = addslashes($func->wash($_GET['id'], 'num', _THE_DOC));
}

//CHECK IF DATAS IN MLB_COLL_EXT
$stmt = $db->query('SELECT res_id FROM mlb_coll_ext WHERE res_id = ?', array($s_id));
if ($stmt->rowCount() <= 0) {
    $_SESSION['error'] = _QUALIFY_FIRST;
    echo "<script language=\"javascript\" type=\"text/javascript\">window.top.location.href='index.php';</script>";
    exit();
}

$_SESSION['doc_id'] = $s_id;
$right = $security->test_right_doc($coll_id, $s_id);

$stmt = $db->query('SELECT typist, creation_date, filename FROM '.$table.' WHERE res_id = ?', array($s_id));
$info_mail = $stmt->fetchObject();

$date1 = new DateTime($info_mail->creation_date);
$date2 = new DateTime();
$date2->sub(new DateInterval('PT1M'));

//WARNING IF NO RIGHT BUT OWNER OF CURRENT DOC
if (!$right && $_SESSION['user']['UserId'] == $info_mail->typist && $date1 > $date2) {
    $right = true;
    $_SESSION['info'] = _MAIL_WILL_DISAPPEAR;
}

//REDIRECT IF NO RIGHT
if (!$right) {
    $_SESSION['error'] = _NO_RIGHT_TXT;
    echo "<script language=\"javascript\" type=\"text/javascript\">window.top.location.href='index.php';</script>";
    exit();
}

//RECORD ACCESS TO THE DOC
if (isset($s_id) && !empty($s_id) && $_SESSION['history']['resview'] == 'true') {
    $hist->add(
        $table,
        $s_id,
        'VIEW',
        'resview',
        _VIEW_DETAILS_NUM.$s_id,
        $_SESSION['config']['databasetype'],
        'apps'
    );
}

$modify_doc = $core->test_service('edit_document_in_detail', 'apps', false); 

//UPDATE DATAS (IF FIELDS CAN BE MODIFIED) OF DOC
if (isset($_POST['submit_index_doc'])) {
    $is->update_mail($_POST, 'POST', $s_id, $coll_id);

    if ($core->is_module_loaded('tags')) {
        $tags = $_POST['tag_userform'];
        $tags_list = $tags;
        include_once 'modules'.DIRECTORY_SEPARATOR.'tags'.DIRECTORY_SEPARATOR.'tags_update.php';
    }

    //thesaurus
    if ($core->is_module_loaded('thesaurus')) {
        require_once 'modules'.DIRECTORY_SEPARATOR.'thesaurus'
                    .DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR
                    .'class_modules_tools.php';
        $thesaurus = new thesaurus();

        if (!empty($_POST['thesaurus'])) {
            $thesaurusList = implode('__', $_POST['thesaurus']);
        } else {
            $thesaurusList = '';
        }
        $thesaurus->updateResThesaurusList($thesaurusList, $s_id);
    }
}

//DELETE DOC (status to DEL)
if (isset($_POST['delete_doc'])) {
    $is->delete_doc($s_id, $coll_id);
    echo "<script language=\"javascript\" type=\"text/javascript\">window.top.location.href='index.php?page=search_adv&dir=indexing_searching';</script>";
    exit();
}

//CHANGE STATUS DOC TO VAL
if (isset($_POST['put_doc_on_validation'])) {
    $is->update_doc_status($s_id, $coll_id, 'VAL');
    echo "<script language=\"javascript\" type=\"text/javascript\">window.top.location.href='index.php?page=search_adv&dir=indexing_searching';</script>";
    exit();
}
$_SESSION['adresses']['to'] = array();
$_SESSION['adresses']['addressid'] = array();
$_SESSION['adresses']['contactid'] = array();

$comp_fields = '';
$stmt = $db->query('SELECT type_id FROM '.$table.' WHERE res_id = ?', array($s_id));
if ($stmt->rowCount() > 0) {
    $res = $stmt->fetchObject();
    $type_id = $res->type_id;
    $indexes = $type->get_indexes($type_id, $coll_id, 'minimal');
    for ($i = 0; $i < count($indexes); ++$i) {
        // In the view all custom from res table begin with doc_
        if (preg_match('/^custom_/', $indexes[$i])) {
            $comp_fields .= ', doc_'.$indexes[$i];
        } else {
            $comp_fields .= ', '.$indexes[$i];
        }
    }
}
$case_sql_complementary = '';
if ($core->is_module_loaded('cases') == true) {
    $case_sql_complementary = ' , case_id';
}
$stmt = $db->query(
    'SELECT status, format, typist, creation_date, fingerprint, filesize, '
    .'res_id, destination, work_batch, page_count, is_paper, scan_date, scan_user, '
    .'scan_location, scan_wkstation, scan_batch, source, doc_language, '
    .'description, closing_date, alt_identifier, initiator, entity_label '.$comp_fields
    .$case_sql_complementary.' FROM '.$table.' WHERE res_id = ?',
    array($s_id)
);
$res = $stmt->fetchObject();
?>
<div id="details_div">
<?php
echo '<h1 class="titdetail"><i class="fa fa-info-circle fa-2x"></i> ';
if (_ID_TO_DISPLAY == 'res_id') {
    $idToDisplay = $s_id;
    $titleToDisplay = $res->alt_identifier;
} else {
    $idToDisplay = $res->alt_identifier;
    $titleToDisplay = strtolower(_NUM).$s_id;
}
echo "<i style='font-style:normal;' title='{$titleToDisplay}'>"._DETAILS.' : '._DOC." {$idToDisplay}</i> <span>({$security->retrieve_coll_label_from_coll_id($coll_id)})</span>";
echo '</h1>';
?>
<div id="inner_content" class="clearfix">
<?php
if ($stmt->rowCount() == 0) {
    $_SESSION['error'] = _NO_DOCUMENT_CORRESPOND_TO_IDENTIFIER;
    echo "<script language=\"javascript\" type=\"text/javascript\">window.top.location.href='index.php';</script>";
    exit();
} else {
    //POPUP INFO AND ERROR
    echo '<div class="info" id="info_detail" onclick="this.hide();" style="display: none;">'.$_SESSION['info'].'</div>';
    echo '<div class="error" id="error_detail" onclick="this.hide();" style="display: none;">'.$_SESSION['error'].'</div>';

    if (isset($_SESSION['info']) && $_SESSION['info'] != '') {
        echo '<script language=\'javascript\' type=\'text/javascript\'>$j("#info_detail").show().delay(5000).fadeOut();</script>';
        $_SESSION['info'] = '';
    }
    if ((!empty($_SESSION['error']) && !($_SESSION['indexation']))) {
        echo '<script language=\'javascript\' type=\'text/javascript\'>$j("#error_detail").show().delay(5000).fadeOut();</script>';
        $_SESSION['error'] = '';
    }

    $typist = $res->typist;
    $format = $res->format;
    $filesize = $res->filesize;
    $creation_date = functions::format_date_db($res->creation_date, false);
    $chrono_number = $res->alt_identifier;
    $initiator = $res->initiator;
    $fingerprint = $res->fingerprint;
    $work_batch = $res->work_batch;
    $destination = $res->destination;
    $page_count = $res->page_count;
    $is_paper = $res->is_paper;
    $scan_date = functions::format_date_db($res->scan_date);
    $scan_user = $res->scan_user;
    $scan_location = $res->scan_location;
    $scan_wkstation = $res->scan_wkstation;
    $scan_batch = $res->scan_batch;
    $doc_language = $res->doc_language;
    $closing_date = functions::format_date_db($res->closing_date, false);
    $indexes = $type->get_indexes($type_id, $coll_id);
    $entityLabel = $res->entity_label;

    $queryUser = 'SELECT firstname, lastname FROM users WHERE user_id = ?';
    $stmt = $db->query($queryUser, array($typist));
    $resultUser = $stmt->fetchObject();

    $queryEntities = 'SELECT entity_label FROM entities WHERE entity_id = ?';
    $stmt = $db->query($queryEntities, array($initiator));
    $resultEntities = $stmt->fetchObject();
    $entities = $resultEntities->entity_label;

    if ($resultUser->lastname != '') {
        $typistLabel = $resultUser->firstname.' '.$resultUser->lastname;
    } else {
        $typistLabel = $typist;
    }

    if ($core->is_module_loaded('cases') == true) {
        require_once 'modules/cases/class/class_modules_tools.php';
        $case = new cases();
        if ($res->case_id != '') {
            $case_properties = $case->get_case_info($res->case_id);
        }
    }

    foreach (array_keys($indexes) as $key) {
        if (preg_match('/^custom/', $key)) {
            $tmp = 'doc_'.$key;
        } else {
            $tmp = $key;
        }
        if ($indexes[$key]['type'] == 'date') {
            $res->{$tmp} = functions::format_date_db($res->{$tmp}, false);
        }
        $indexes[$key]['value'] = $res->{$tmp};
        $indexes[$key]['show_value'] = $res->{$tmp};
        if ($indexes[$key]['type'] == 'string') {
            $indexes[$key]['show_value'] = functions::show_string($res->{$tmp});
        } elseif ($indexes[$key]['type'] == 'date') {
            $indexes[$key]['show_value'] = functions::format_date_db($res->{$tmp}, true);
        }
    }
    $status = $res->status;
    if (!empty($status)) {
        require_once 'core/class/class_manage_status.php';
        $status_obj = new manage_status();
        $res_status = $status_obj->get_status_data($status);
        if ($modify_doc) {
            $can_be_modified = $status_obj->can_be_modified($status);
            if (!$can_be_modified) {
                $modify_doc = false;
            }
        }
        if ($_SESSION['user']['UserId'] == 'superadmin') {
            $modify_doc = true;
        }
    }
    $mode_data = 'full';
    if ($modify_doc) {
        $mode_data = 'form';
    }
    foreach (array_keys($indexes) as $key) {
        $indexes[$key]['opt_index'] = true;
        if ($indexes[$key]['type_field'] == 'select') {
            for ($i = 0; $i < count($indexes[$key]['values']); ++$i) {
                if ($indexes[$key]['values'][$i]['id'] == $indexes[$key]['value']) {
                    $indexes[$key]['show_value'] = $indexes[$key]['values'][$i]['label'];
                    break;
                }
            }
        }
        if (!$modify_doc) {
            $indexes[$key]['readonly'] = true;
            $indexes[$key]['type_field'] = 'input';
        } else {
            $indexes[$key]['readonly'] = false;
        }
    }
    $data = get_general_data($coll_id, $s_id, $mode_data, $param_data); ?>
    <div class="block">
        <b>
        <p id="back_list">
            <?php
            if (!isset($_POST['up_res_id']) || !$_POST['up_res_id']) {
                if ($_SESSION['indexation'] == false) {
                    echo '<a href="#" onclick="document.getElementById(\'ariane\').childNodes[document.getElementById(\'ariane\').childNodes.length-2].click();"><i class="fa fa-arrow-circle-left fa-2x" title="'._BACK.'"></i></a>';
                }
            } ?>
        </p>
        <p id="viewdoc">
            <?php if ($info_mail->filename) {
                ?>
                <a href="index.php?display=true&editingMode=true&dir=indexing_searching&page=view_resource_controler&id=<?php functions::xecho($s_id); ?>" 
                    target="_blank"><i class="fa fa-download fa-2x" title="<?php echo _VIEW_DOC; ?>"></i></a>
            <?php
            } ?>
            &nbsp;&nbsp;&nbsp;
        </p>
        </b>&nbsp;
    </div>
    <br/>
    <?php
    //CONSTRUCT TABS
    echo '<div class="whole-panel">';

    //DETAILS TAB
    if ($nbAttach == 0 && strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
        $style = 'padding-right: 0px;';
        $styleBadge = 'visibility:hidden;"';
    } else {
        $style = 'padding-right: 15px;';
        $styleBadge = 'display:none;"';
    }
    echo "<div class='fa fa-info-circle detailsTab DetailsTabFunc TabSelected' id='DetailstachometerTab' style='font-size:2em;padding-left: 15px;{$style}' title='"._PROPERTIES."' onclick=\"tabClicked('DetailstachometerTab',false);\">";
    echo "<sup><span style='font-size: 10px;{$styleBadge}' class='nbResZero'>0</span></sup>";
    echo '</div>';

    //TECHNICAL INFO TAB
    if ($viewTechnicalInfos) {
        $technicalInfo_frame = '';
        $pathScriptTab = $_SESSION['config']['businessappurl'].'index.php?display=true&page=show_technicalInfo_tab';
        $technicalInfo_frame .= '<div class="fa fa-cogs DetailsTabFunc" id="DetailsCogdTab" style="font-size:2em;padding-left: 15px;padding-right: 15px;" title="'._TECHNICAL_INFORMATIONS.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'DetailsCogdTab\',true);"><sup><span style="font-size: 10px;display: none;" class="nbResZero"></span></sup></div>';
        echo $technicalInfo_frame;
    }

    //DIFF LIST TAB
    if ($core->is_module_loaded('entities')) {
        require_once 'modules/entities/class/class_manage_listdiff.php';
        $diff_list = new diffusion_list();
        $_SESSION['details']['diff_list'] = $diff_list->get_listinstance($s_id, false, $coll_id);
        $_SESSION['details']['difflist_type'] = $diff_list->get_difflist_type($_SESSION['details']['diff_list']['difflist_type']);
        $roles = $diff_list->list_difflist_roles();

        $roles_str = json_encode($roles);

        $diffList_frame = '';
        $category = $data['category_id']['value'];

        $onlyCC = '';
        if ($core->test_service('add_copy_in_indexing_validation', 'entities', false) && $_SESSION['user']['UserId'] != 'superadmin') {
            $onlyCC = '&only_cc';
        }
        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_diffList_tab&module=entities&resId='.$s_id.'&collId='.$coll_id.'&fromDetail=true&category='.$category.'&roles='.urlencode($roles_str).$onlyCC;

        $diffList_frame .= '<div class="DetailsTabFunc" id="DetailsGearTab" style="display:block !important;font-size:2em;padding-left: 15px;';
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
            $diffList_frame .= 'padding-right: 0px;height: 29px;';
        } else {
            $diffList_frame .= 'padding-right: 15px;height: 30px;';
        }
        $diffList_frame .= '" title="'._DIFF_LIST.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'DetailsGearTab\',true);"><i class="fa fa-share-alt" onclick="this.closest(\'div\').click()"></i> <sup><span style="font-size: 10px;';
        $diffList_frame .= $styleBadge.' class="nbResZero">0</span></sup></div>';

        echo $diffList_frame;
    }

    //PRINT FOLDER TAB
    if ($core->test_service('print_folder_doc', 'visa', false)) {
        $printFolder_frame = '';
        require_once 'modules'.DIRECTORY_SEPARATOR.'visa'.DIRECTORY_SEPARATOR
                .'class'.DIRECTORY_SEPARATOR
                .'class_modules_tools.php';

        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_printFolder_tab&module=visa&resId='
                .$s_id.'&collId='.$coll_id.'&table='.$table;
        $printFolder_frame .= '<div class="fa fa-print DetailsTabFunc" id="DetailsPrintTab" style="font-size:2em;padding-left: 15px;';
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
            $printFolder_frame .= 'padding-right: 0px;';
        } else {
            $printFolder_frame .= 'padding-right: 15px;';
        }
        $printFolder_frame .= '" title="'._PRINTFOLDER.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'DetailsPrintTab\',true);"> <sup><span style="font-size: 10px;';
        $printFolder_frame .= $styleBadge.'class="nbResZero">0</span></sup></div>';
        echo $printFolder_frame;
    }

    //VISA TAB
    if ($core->is_module_loaded('visa')) {
        $visa_frame = '';
        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_visa_tab&module=visa&resId='.$s_id.'&collId='.$coll_id.'&destination='.$destination.'&fromDetail=true';
        $visa_frame .= '<div id="visa_tab" class="fa fa-list-ol DetailsTabFunc" style="font-size:2em;padding-left: 15px;padding-right: 15px;" title="'._VISA_WORKFLOW.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'visa_tab\',true);"> <sup id="visa_tab_badge"></sup>';
        $visa_frame .= '</div>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=visa&page=load_toolbar_visa&resId='.$s_id.'&collId='.$coll_id;
        $visa_frame .= '<script>loadToolbarBadge(\'visa_tab\',\''.$toolbarBagde_script.'\');</script>';

        echo $visa_frame;
    }

    //AVIS TAB
    if ($core->is_module_loaded('avis')) {
        $avis_frame = '';
        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_avis_tab&module=avis&resId='.$s_id.'&collId='.$coll_id.'&fromDetail=true';
        $avis_frame .= '<div id="avis_tab" class="fa fa-commenting DetailsTabFunc" style="font-size:2em;padding-left: 15px;padding-right: 15px;" title="'._AVIS_WORKFLOW.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'avis_tab\',true);"> <sup id="avis_tab_badge"></sup>';
        $avis_frame .= '</div>';
        $avis_frame .= '<div id="page_circuit_avis" style="overflow-x: hidden;"></div>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=avis&page=load_toolbar_avis&resId='.$s_id.'&collId='.$coll_id;
        $avis_frame .= '<script>loadToolbarBadge(\'avis_tab\',\''.$toolbarBagde_script.'\');</script>';

        echo $avis_frame;
    }

    //ATTACHMENTS TAB
    if ($core->is_module_loaded('attachments')) {
        $attachments_frame = '';
        $extraParam = '&attach_type_exclude=response_project,signed_response,outgoing_mail_signed,converted_pdf,outgoing_mail,print_folder,aihp';
        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_attachments_details_tab&module=attachments&resId='
                .$s_id.'&collId='.$coll_id.'&fromDetail=attachments'.$extraParam;
        $attachments_frame .= '<div class="fa fa-paperclip DetailsTabFunc" id="attachments_tab" style="font-size:2em;padding-left: 15px;padding-right: 15px;" title="'._ATTACHMENTS.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'attachments_tab\',true);"> <sup id="attachments_tab_badge"></sup>';
        $attachments_frame .= '</div>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=load_toolbar_attachments&resId='.$s_id.'&collId='.$coll_id;
        $attachments_frame .= '<script>loadToolbarBadge(\'attachments_tab\',\''.$toolbarBagde_script.'\');</script>';

        echo $attachments_frame;
    }

    //RESPONSES TAB
    if ($core->is_module_loaded('attachments')) {
        $responses_frame = '';
        $extraParam = '&attach_type=response_project,outgoing_mail_signed,signed_response,outgoing_mail,aihp';
        $pathScriptTab = $_SESSION['config']['businessappurl']
                    .'index.php?display=true&page=show_attachments_details_tab&module=attachments&fromDetail=response&resId='
                    .$s_id.'&collId='.$coll_id.$extraParam;
        $responses_frame .= '<div id="responses_tab" class="fa fa-mail-reply DetailsTabFunc" style="font-size:2em;padding-left: 15px;padding-right: 15px;" title="'._DONE_ANSWERS.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'responses_tab\',true);"> <sup id="responses_tab_badge"></sup>';
        $responses_frame .= '</div>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=load_toolbar_attachments&responses&resId='.$s_id.'&collId='.$coll_id;
        $responses_frame .= '<script>loadToolbarBadge(\'responses_tab\',\''.$toolbarBagde_script.'\');</script>';

        echo $responses_frame;
    }

    //HISTORY TAB
    if ($viewDocHistory) {
        $history_frame = '';

        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_history_tab&resId='
                .$s_id.'&collId='.$coll_id;
        $history_frame .= '<div class="fa fa-history DetailsTabFunc" id="DetailsLineChartTab" style="font-size:2em;padding-left: 15px;';
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
            $history_frame .= 'padding-right: 0px;';
        } else {
            $history_frame .= 'padding-right: 15px;';
        }
        $history_frame .= '" title="'._DOC_HISTORY.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'DetailsLineChartTab\',true);"> <sup><span style="font-size: 10px;';
        $history_frame .= $styleBadge.' class="nbResZero">0</span></sup>';
        $history_frame .= '</div>';
        echo $history_frame;
    }

    //NOTES TAB
    if ($core->is_module_loaded('notes')) {
        $note = '';
        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&module=notes&page=notes&identifier='
                .$s_id.'&origin=document&coll_id='.$coll_id.'&load&size=full';
        $note .= '<div id="notes_tab" class="fa fa-pencil DetailsTabFunc" style="font-size:2em;padding-left: 15px;padding-right: 15px;" title="'._NOTES.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'notes_tab\',true);"> <sup id="notes_tab_badge"></sup>';
        $note .= '</div>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=notes&page=load_toolbar_notes&resId='.$s_id.'&collId='.$coll_id;
        $note .= '<script>loadToolbarBadge(\'notes_tab\',\''.$toolbarBagde_script.'\');</script>';

        echo $note;
    }

    //CASES TAB
    if ($core->is_module_loaded('cases') == true) {
        $case_frame = '';
        $pathScriptTab = $_SESSION['config']['businessappurl']
            .'index.php?display=true&page=show_case_tab&module=cases&collId='.$coll_id.'&resId='.$s_id;
        $case_frame .= '<div id="cases_tab" class="fa fa-suitcase DetailsTabFunc" style="font-size:2em;padding-left: 15px;padding-right: 15px;" title="'._CASE.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'cases_tab\',true);"> <sup id="cases_tab_badge"></sup>';
        $case_frame .= '</div>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=cases&page=load_toolbar_cases&resId='.$s_id.'&collId='.$coll_id;
        $case_frame .= '<script>loadToolbarBadge(\'cases_tab\',\''.$toolbarBagde_script.'\');</script>';

        echo $case_frame;
    }

    //SENDMAILS TAB
    if ($core->test_service('sendmail', 'sendmail', false) === true) {
        $sendmail = '';
        $pathScriptTab = $_SESSION['config']['businessappurl'].'index.php?display=true&module=sendmail&page=sendmail&identifier='.$s_id.'&origin=document&coll_id='.$coll_id.'&load&size=medium';

        $sendmail .= '<div id="sendmail_tab" class="fa fa-envelope DetailsTabFunc" style="font-size:2em;padding-left: 15px;padding-right: 15px;" title="'._SENDED_EMAILS.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'sendmail_tab\',true);"> <sup id="sendmail_tab_badge"></sup>';
        $sendmail .= '</div>';

        //LOAD TOOLBAR BADGE
        $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&module=sendmail&page=load_toolbar_sendmail&resId='.$s_id.'&collId='.$coll_id;
        $sendmail .= '<script>loadToolbarBadge(\'sendmail_tab\',\''.$toolbarBagde_script.'\');</script>';

        echo $sendmail;
    }

    //VERSIONS TAB
    if ($core->test_service('view_version_letterbox', 'apps', false)) {
        $version = '';
        $versionTable = $security->retrieve_version_table_from_coll_id(
                $coll_id
            );
        $selectVersions = 'SELECT res_id FROM '
                .$versionTable." WHERE res_id_master = ? and status <> 'DEL' order by res_id desc";

        $stmt = $db->query($selectVersions, array($s_id));
        $nb_versions_for_title = $stmt->rowCount();
        $lineLastVersion = $stmt->fetchObject();
        $lastVersion = $lineLastVersion->res_id;
        if ($lastVersion != '') {
            $objectId = $lastVersion;
            $objectTable = $versionTable;
        } else {
            $objectTable = $security->retrieve_table_from_coll(
                    $coll_id
                );
            $objectId = $s_id;
            $_SESSION['cm']['objectId4List'] = $s_id;
        }
        if ($nb_versions_for_title == 0) {
            $extend_title_for_versions = '0';
            $class = 'nbResZero';
            if ($nbAttach == 0 && strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
                $style = 'visibility:hidden;font-size: 10px;';
            } else {
                $style = 'display:none;font-size: 10px;';
            }

            $style2 = 'color:#9AA7AB;font-size:2em;padding-left: 15px;padding-right: 15px;';
        } else {
            $extend_title_for_versions = $nb_versions_for_title;
            $class = 'nbRes';
            $style = 'font-size: 10px;';
        }
        $_SESSION['cm']['resMaster'] = '';

        $pathScriptTab = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page=show_versions_tab&collId='.$coll_id.'&resId='.$s_id.'&objectTable='.$objectTable;
        $version .= '<div  class="fa fa-code-fork DetailsTabFunc" id="DetailsCodeForkTab" style="font-size:2em;padding-left: 15px;';
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
            $version .= 'padding-right: 0px;';
        } else {
            $version .= 'padding-right: 15px;';
        }
        $version .= '"title="'._VERSIONS.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'DetailsCodeForkTab\',true);">';
        $version .= ' <sup><span id="nbVersions" ';
        $version .= 'class="'.$class.'" style="'.$styleBadge.'">'.$extend_title_for_versions.'</span></sup>';
        $version .= '</div>';
        echo $version;
    }

    //LINKS TAB
    $Links = '';
    $pathScriptTab = $_SESSION['config']['businessappurl'].'index.php?display=true&page=show_links_tab';
    $Links .= '<div id="links_tab" class="fa fa-link DetailsTabFunc" style="font-size:2em;padding-left: 15px;padding-right: 15px;" title="'._LINK_TAB.'" onclick="loadSpecificTab(\'uniqueDetailsIframe\',\''.$pathScriptTab.'\');tabClicked(\'links_tab\',true);"> <sup id="links_tab_badge"></sup>';
    $Links .= '</div>';

    //LOAD TOOLBAR BADGE
    $toolbarBagde_script = $_SESSION['config']['businessappurl'].'index.php?display=true&page=load_toolbar_links&resId='.$s_id.'&collId='.$coll_id;
    $Links .= '<script>loadToolbarBadge(\'links_tab\',\''.$toolbarBagde_script.'\');</script>';
    echo $Links; ?>

    <div class="detailsDisplayDiv" id = "home-panel">         
        <br/>
        <form method="post" name="index_doc" id="index_doc" action="index.php?page=details&dir=indexing_searching&id=<?php functions::xecho($s_id); ?>">
        <div align="center">
        <?php
        //TOOLBAR
        $toolBar = '';
    if ($printDetails) {
        $toolBar .= '<input type="button" class="button" name="print_details" id="print_details" value="'._PRINT_DETAILS.'" onclick="window.open(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&page=print&id='.$s_id.'\', \'_blank\');" /> ';
    }

    if ($putInValid) {
        $toolBar .= '<input type="submit" class="button"  value="'._PUT_DOC_ON_VALIDATION.'" name="put_doc_on_validation" onclick="return(confirm(\''._REALLY_PUT_DOC_ON_VALIDATION.'\n\r\n\r\'));" /> ';
    }

    if ($core->test_service('delete_document_in_detail', 'apps', false)) {
        $toolBar .= '<input type="submit" class="button"  value="'._DELETE_DOC.'" name="delete_doc" onclick="return(confirm(\''._REALLY_DELETE.' '._THIS_DOC.' ?\n\r\n\r\'));" /> ';
    }

    if ($modify_doc) {
        $toolBar .= '<input type="submit" class="button"  value="'._SAVE_MODIFICATION.'" name="submit_index_doc" /> ';
    }
    $toolBar .= '<input type="button" class="button" name="back_welcome" id="back_welcome" value="'._BACK_TO_WELCOME.'" onclick="window.top.location.href=\''.$_SESSION['config']['businessappurl'].'index.php\';" />';

    echo $toolBar; ?>
        </div>
        <h2>
            <span class="date">
                <b><?php echo _FILE_DATA; ?></b>
            </span>
        </h2>
        <table cellpadding="2" cellspacing="2" border="0" class="block forms details" width="100%">
        <?php
        $i = 0;
    if (!$modify_doc) {
        $data['process_limit_date']['readonly'] = true;
    }
    foreach (array_keys($data) as $key) {
        if ($key != 'is_multicontacts' || ($key == 'is_multicontacts' && $data[$key]['show_value'] == 'Y')) {
            if ($i % 2 != 1 || $i == 0) { // pair
                echo '<tr class="col">';
            }
            $folder_id = '';
            if ($key == 'folder' && $data[$key]['show_value'] != '') {
                $folderTmp = $data[$key]['show_value'];
                $find1 = strpos($folderTmp, '(');
                $folder_id = substr($folderTmp, $find1, strlen($folderTmp));
                $folder_id = str_replace('(', '', $folder_id);
                $folder_id = str_replace(')', '', $folder_id);
            }
            //GET DATA ICON
            echo '<th align="center" class="picto" >';

            $iconLabel = $data[$key]['label'];
            $iconLinkAttr = '';

            if ($key == 'is_multicontacts') {
                echo '<i class="fa fa-book fa-2x"></i>';
            } elseif (isset($data[$key]['img'])) {
                $iconCode = $data[$key]['img'];

                $iconShow = "<i class='fa fa-{$iconCode} fa-2x' title='{$iconLabel}'></i>";

                if ($folder_id != '') {
                    $iconShow = "<a href='index.php?page=show_folder&module=folder&id={$folder_id}'>".$iconShow.'</a>';
                } elseif (in_array($key, ['dest_contact_id', 'exp_contact_id'])) {
                    $inputValue = $data[$key]['value'];
                    $inputAddressValue = $data[$key]['address_value'];

                    $iconShow = "<a href=\"#\" onclick=\"window.open('index.php?display=true&dir=my_contacts&page=info_contact_iframe&mode=view&popup&contactid={$inputValue}&addressid={$inputAddressValue}', 'contact_info', 'height=800, width=1000,scrollbars=yes,resizable=yes');\">".$iconShow.'</a>';
                } elseif (in_array($key, ['dest_user_id', 'exp_user_id'])) {
                    $inputValue = $data[$key]['value'];
                    $inputAddressValue = $data[$key]['address_value'];

                    $iconShow = "<a style='cursor:pointer;' onclick=\"window.open('index.php?display=true&page=user_info&id={$inputValue}', 'contact_info', 'height=400, width=600,scrollbars=yes,resizable=yes');\">".$iconShow.'</a>';
                }

                echo $iconShow;
            } else {
                echo "<i class='fa fa-question-circle-o fa-2x' title='{$iconLabel}'></i>";
            }
            echo '</th>';
            // END DATA ICON

            //GET DATA LABEL
            echo '<td align="left" width="200px">';
            echo $data[$key]['label'];
            echo '</td>';
            //END DATA LABEL

            //GET DATA INPUT
            echo '<td>';
            if (!isset($data[$key]['readonly']) || $data[$key]['readonly'] == true) {
                $disabledAttr = 'disabled="disabled"';
                $disabledClass = 'readonly';
            } else {
                $disabledAttr = '';
                $disabledClass = '';
            }
            if ($data[$key]['field_type'] == 'textfield') {
                $inputValue = $data[$key]['show_value'];

                if (in_array($key, ['exp_contact_id', 'dest_contact_id', 'exp_user_id', 'dest_user_id'])) {
                    unset($_SESSION['adresses']);

                    echo "<textarea name='contact' id='contact' rows='3' class='{$disabledClass}' {$disabledAttr}/>{$inputValue}</textarea>";

                    $inputValue = $data[$key]['value'];
                    $inputAddressValue = $data[$key]['address_value'];
                    echo "<input type='hidden' name='contactid' id='contactid' value='{$inputValue}' title='{$inputValue}' alt='{$inputValue}' size='40' />";
                    echo "<input type='hidden' name='addressid' id='addressid' value='{$inputAddressValue}' title='{$inputAddressValue}' alt='{$inputAddressValue}' size='40' />";
                    echo "<input type='hidden' name='contact_type' id='contact_type' value='{$key}' title='{$key}' alt='{$key}' size='40' />";

                    //initialize autocomplete
                    echo '<div id="show_contacts" class="autocomplete autocompleteIndex" style="width:200px;"></div><div class="autocomplete autocompleteIndex" id="searching_autocomplete" style="display: none;text-align:left;padding:5px;width:200px;"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i> chargement ...</div></span>';
                    echo '<script>launch_autocompleter_contacts_v2(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts\', \'\', \'\', \'\', \'contactid\', \'addressid\');</script>';
                } elseif ($key == 'is_multicontacts') {
                    $_SESSION['adresses']['to'] = array();
                    $_SESSION['adresses']['addressid'] = array();
                    $_SESSION['adresses']['contactid'] = array();

                    $path_to_script = $_SESSION['config']['businessappurl']
                                        .'index.php?display=true&dir=indexing_searching&page=add_multi_contacts&coll_id='.$collId;

                    if (empty($disabledAttr)) {
                        echo "<div id='input_multi_contact_add' style=''>";
                        echo "<input type='text' name='{$key}' id='{$key}' value='' title='' alt='' size='40' style='width:140px;'/>";
                        echo '<div id="multiContactList" class="autocomplete" style="width:200px;"></div><div class="autocomplete autocompleteIndex" id="searching_autocomplete_multi" style="display: none;text-align:left;padding:5px;width:200px;"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i> chargement ...</div></span>';
                        echo '<script type="text/javascript">addMultiContacts(\'is_multicontacts\', \'multiContactList\', \''
                                                    .$_SESSION['config']['businessappurl']
                                                    .'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts\', \'Input\', \'2\', \'contactid\', \'addressid\');</script>';
                        echo ' <input type="button" name="add" value="&nbsp;'._ADD
                                                .'&nbsp;" id="valid_multi_contact" class="button" onclick="updateMultiContacts(\''.$path_to_script
                                                .'&mode=adress\', \'add\', document.getElementById(\'is_multicontacts\').value, '
                                                .'\'to\', false, document.getElementById(\'addressid\').value, document.getElementById(\'contactid\').value);" />';
                        echo '</div>';
                    }

                    echo '<div name="to" id="to" class="multicontactInput">';

                    $nbContacts = count($data[$key]['multi']['contact_id']);
                    if ($nbContacts > 0) {
                        for ($icontacts = 0; $icontacts < $nbContacts; ++$icontacts) {
                            $_SESSION['adresses']['to'][] = $data[$key]['multi']['arr_values'][$icontacts];
                            $_SESSION['adresses']['addressid'][] = $data[$key]['multi']['address_id'][$icontacts];
                            $_SESSION['adresses']['contactid'][] = $data[$key]['multi']['contact_id'][$icontacts];

                            echo '<div class="multicontact_element" style="display:table;width:200px;" id="'.$icontacts.'_'.$data[$key]['multi']['contact_id'][$icontacts].'"><div style="display:table-cell;width:100%;vertical-align:middle;">'.$data[$key]['multi']['arr_values'][$icontacts].'</div>';

                            if (empty($disabledAttr)) {
                                echo '&nbsp;<div class="email_delete_button" style="display:table-cell;vertical-align:middle" id="'.$icontacts.'"'
                                                        .'onclick="updateMultiContacts(\''.$path_to_script
                                                        .'&mode=adress\', \'del\', \''.$data[$key]['multi']['arr_values'][$icontacts].'\', \'to\', this.id, \''.$data[$key]['multi']['address_id'][$icontacts].'\', \''.$data[$key]['multi']['contact_id'][$icontacts].'\');" alt="'._DELETE.'" title="'
                                                        ._DELETE.'">x</div>';
                                echo '</div>';
                            } else {
                                echo '&nbsp;<div class="email_delete_button" style="display:none;vertical-align:middle" id="'.$icontacts.'"'
                                                    .'onclick="" alt="'._DELETE.'" title="'
                                                    ._DELETE.'">x</div>';
                                echo '</div>';
                            }
                        }
                    }
                    echo '</div>';
                    echo "<input type='hidden' name='contactid' id='contactid' value='' title='' alt='' size='40' />";
                    echo "<input type='hidden' name='addressid' id='addressid' value='' title='' alt='' size='40' />";
                } else {
                    echo "<input type='text' name='{$key}' id='{$key}' value='{$inputValue}' title='{$inputValue}' alt='{$inputValue}' size='40' class='{$disabledClass}' {$disabledAttr}/>";
                }
            } elseif ($data[$key]['display'] == 'textarea') {
                $inputValue = $data[$key]['show_value'];

                echo "<textarea name='{$key}' id='{$key}' rows='3' style='width: 200px; max-width: 200px;' class='{$disabledClass}' {$disabledAttr}>{$inputValue}</textarea>";
            } elseif ($data[$key]['field_type'] == 'date') {
                $inputValue = $data[$key]['show_value'];

                echo "<input type='text' name='{$key}' id='{$key}' value='{$inputValue}' size='40' title='{$inputValue}' alt='{$inputValue}' class='{$disabledClass}' {$disabledAttr} onclick='showCalender(this);' />";
            } elseif ($data[$key]['field_type'] == 'select') {
                $inputUrl = $_SESSION['config']['businessappurl'];
                if ($key == 'type_id') {
                    $inputAttr = 'onchange="change_doctype_details(this.options[this.options.selectedIndex].value, \''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=change_doctype_details\' , \''._DOCTYPE.' '._MISSING.'\', ' . $s_id . ');"';
                } elseif ($key == 'priority') {
                    $inputAttr = 'onchange="updateProcessDate(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=update_process_date\', '.$s_id.')"';
                }

                echo "<select id='{$key}' name='{$key}' class='{$disabledClass}' {$disabledAttr} {$inputAttr} >";

                if ($key == 'type_id') {
                    if ($_SESSION['features']['show_types_tree'] == 'true') {
                        for ($k = 0; $k < count($data[$key]['select']); ++$k) {
                            $inputValue = $data[$key]['select'][$k]['label'];
                            echo "<optgroup class='doctype_level1' label='{$inputValue}'>";

                            for ($j = 0; $j < count($data[$key]['select'][$k]['level2']); ++$j) {
                                $inputValue = $data[$key]['select'][$k]['level2'][$j]['label'];
                                echo "<optgroup class='doctype_level2' label='&nbsp;&nbsp;&nbsp;&nbsp;{$inputValue}'>";

                                for ($l = 0; $l < count($data[$key]['select'][$k]['level2'][$j]['types']); ++$l) {
                                    $inputValue = $data[$key]['select'][$k]['level2'][$j]['types'][$l]['label'];
                                    $inputId = $data[$key]['select'][$k]['level2'][$j]['types'][$l]['id'];

                                    if ($data[$key]['value'] == $data[$key]['select'][$k]['level2'][$j]['types'][$l]['id']) {
                                        $inputAttr = 'selected="selected"';
                                    } else {
                                        $inputAttr = '';
                                    }
                                    echo "<option value='{$inputId}' {$inputAttr}>&nbsp;&nbsp;&nbsp;&nbsp;".functions::xssafe($inputValue)."</option>";
                                }
                                echo '</optgroup>';
                            }
                            echo '</optgroup>';
                        }
                    } else {
                        for ($k = 0; $k < count($data[$key]['select']); ++$k) {
                            $inputValue = $data[$key]['select'][$k]['LABEL'];
                            $inputId = $data[$key]['select'][$k]['ID'];

                            if ($data[$key]['value'] == $data[$key]['select'][$k]['ID']) {
                                $inputAttr = 'selected="selected"';
                            } else {
                                $inputAttr = '';
                            }
                            echo "<option value='{$inputId}' {$inputAttr}>{$inputValue}</option>";
                        }
                    }
                } else {
                    for ($k = 0; $k < count($data[$key]['select']); ++$k) {
                        $inputValue = $data[$key]['select'][$k]['LABEL'];
                        $inputId = $data[$key]['select'][$k]['ID'];

                        if ($data[$key]['value'] == $data[$key]['select'][$k]['ID']) {
                            $inputAttr = 'selected="selected"';
                        } else {
                            $inputAttr = '';
                        }
                        echo "<option value='{$inputId}' {$inputAttr}>{$inputValue}</option>";
                    }
                }
                echo '</select>';
            } elseif ($data[$key]['field_type'] == 'radio') {
                for ($k = 0; $k < count($data[$key]['radio']); ++$k) {
                    $inputValue = $data[$key]['radio'][$k]['LABEL'];
                    $inputId = $data[$key]['radio'][$k]['ID'];

                    if ($data[$key]['value'] == $data[$key]['radio'][$k]['ID']) {
                        $inputAttr = 'checked';
                    } else {
                        $inputAttr = '';
                    }
                    echo "<input type='radio' name='{$key}' id='{$key}_{$inputId}' value='{$inputId}' class='{$disabledClass}' {$disabledAttr} {$inputAttr}/>{$inputValue}";
                }
            } elseif ($data[$key]['field_type'] == 'autocomplete') {
                if ($key == 'folder' && $core->is_module_loaded('folder') && ($core->test_service('associate_folder', 'folder', false) == 1)) {
                    $inputValue = $data['folder']['show_value'];
                    echo '<div class="typeahead__container" style="width:206px">
                    	     <div class="typeahead__field">
                                <input type="text" name="folder" id="folder" value="'.$inputValue.'" class="folderSearch '.$disabledClass.'" '.$disabledAttr.' autocomplete="off" style="font-size: small;"/>
                    	     </div>
                         </div>';

                    echo '<script type="text/javascript">loadTypeahead(\'.folderSearch\', \'desc\', true, \'index.php?display=true&module=folder&page=autocomplete_folders&mode=folder\');</script>';
                } else {
                    $inputValue = $data['folder']['show_value'];
                    echo "<input type='text' name='folder' id='folder' class='readonly' onblur='' value='{$inputValue}' readonly='readonly'/>";
                }
            } elseif ($data[$key]['display'] == 'textinput') {
                $inputValue = $data[$key]['show_value'];

                echo "<input type='text' name='{$key}' id='{$key}' value='{$inputValue}' title='{$inputValue}' alt='{$inputValue}' size='40' class='{$disabledClass}' {$disabledAttr}/>";

                if ($key == 'type_id') {
                    $inputValue = $data[$key]['value'];
                    echo "<input type='hidden' name='{$key}' id='{$key}' value='{$inputValue}' title='{$inputValue}' alt='{$inputValue}' size='40' readonly='readonly' class='readonly'/>";
                }
            }

            echo '</td>';
            //END DATA INPUT

            if ($i % 2 == 1 && $i != 0) { // impair
                echo '</tr>';
            } else {
                if ($i + 1 == count($data)) {
                    echo '<td  colspan="2">&nbsp;</td></tr>';
                }
            }
            ++$i;
        }
    }
    //OTHER DATAS STATUS, CHRONO NUMBER
    echo '<tr class="col">';

    //STATUS
    echo '<th align="left" class="picto">';
    $statusId = $res_status['ID'];
    $statusLabel = $res_status['LABEL'];
    $iconClass = substr($res_status['IMG_SRC'], 0, 2);
    $iconCode = $res_status['IMG_SRC'];

    echo "<i class='{$iconClass} {$iconCode} {$iconClass}-2x' alt='{$statusLabel}' title='$statusLabel'></i>";
    echo '</th>';
    echo '<td align="left" width="200px">';
    echo  _STATUS;
    echo '</td>';
    echo '<td>';
    echo "<input type='text' class='readonly' readonly='readonly' value='{$statusLabel}' title='{$statusId}' size='40'";
    echo '</td>';

    //CHRONO NUMBER
    if (_ID_TO_DISPLAY == 'res_id') {
        echo '<th align="left" class="picto">';
        echo '<i class="fa fa-compass fa-2x" title="'._CHRONO_NUMBER.'" ></i>';
        echo '</th>';
        echo '<td align="left" width="200px">';
        echo _CHRONO_NUMBER;
        echo '</td>';
        echo '<td>';
        echo "<input type='text' class='readonly' readonly='readonly' value='{$chrono_number}' title='{$chrono_number}' alt='{$chrono_number}' size='40'";
        echo '</td>';
    }
    echo '</tr>';

    //OTHER DATAS INITIATOR, TYPIST
    echo '<tr class="col">';

    //INITIATOR
    echo '<th align="left" class="picto">';
    echo '<i class="fa fa-sitemap fa-2x" title="'._INITIATOR.'"></i>';
    echo '</th>';
    echo '<td align="left" width="200px">';
    echo  _INITIATOR;
    echo '</td>';
    echo '<td>';
    echo "<textarea rows='2' style='width: 200px; max-width: 200px;' class='readonly' readonly='readonly'>{$entities}</textarea>";
    echo '</td>';

    //TYPIST
    echo '<th align="left" class="picto">';
    echo '<i class="fa fa-user fa-2x" title="'._TYPIST.'"></i>';
    echo '</th>';
    echo '<td align="left" width="200px">';
    echo  _TYPIST;
    echo '</td>';
    echo '<td>';
    echo "<input type='text' class='readonly' readonly='readonly' value='{$typistLabel}' title='{$typistLabel}' alt='{$typistLabel}' size='40'";
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    //END GENERAL DATAS?>
                    
        <div id="opt_indexes">
            <?php if (count($indexes) > 0 || ($core->is_module_loaded('tags') && ($core->test_service('tag_view', 'tags', false) == 1)) || ($core->is_module_loaded('thesaurus') && ($core->test_service('thesaurus_view', 'thesaurus', false) == 1))) {
        ?>
            <br/>
            <h2>
            <span class="date">
                <b><?php echo _OPT_INDEXES; ?></b>
            </span>
            </h2>
            <br/>
            <div class="block forms details">
                <table cellpadding="2" cellspacing="2" border="0" id="opt_indexes_custom" width="100%">
                    <?php
                    $i = 0;
        foreach (array_keys($indexes) as $key) {
            if ($i % 2 != 1 || $i == 0) { // pair
                echo '<tr class="col">';
            } ?>
                        <th align="left" class="picto" >
                        <?php
                        if (isset($indexes[$key]['img'])) {
                            ?>
                            <i class="fa fa-<?php functions::xecho($indexes[$key]['img']); ?> fa-2x" title="<?php echo $indexes[$key]['label']; ?>" ></i>
                            <?php
                        } ?>
                        </th>
                        <td align="left" width="200px">
                            <?php
                            echo $indexes[$key]['label']; ?> :
                        </td>
                        <td>
                        <?php
                        if ($indexes[$key]['type_field'] == 'input') {
                            ?>
                        <!--<input type="text" name="<?php functions::xecho($key); ?>" id="<?php functions::xecho($key); ?>" value="<?php functions::xecho($indexes[$key]['show_value']); ?>" <?php if (!isset($indexes[$key]['readonly']) || $indexes[$key]['readonly'] == true) {
                                echo 'readonly="readonly" class="readonly"';
                            } elseif ($indexes[$key]['type'] == 'date') {
                                echo 'onclick="showCalender(this);"';
                            } ?> style="width: 99%; font-size:100%"  title="<?php functions::xecho($indexes[$key]['show_value']); ?>" alt="<?php functions::xecho($indexes[$key]['show_value']); ?>"   />-->
                        <textarea name="<?php functions::xecho($key); ?>" id="<?php functions::xecho($key); ?>" <?php if (!isset($indexes[$key]['readonly']) || $indexes[$key]['readonly'] == true) {
                                echo 'readonly="readonly" class="readonly"';
                            } elseif ($indexes[$key]['type'] == 'date') {
                                echo 'onclick="showCalender(this);"';
                            } ?> style="width: 200px; "  title="<?php echo $indexes[$key]['show_value']; ?>" alt="<?php echo $indexes[$key]['show_value']; ?>" ><?php echo $indexes[$key]['show_value']; ?></textarea>
                        <?php
                        } else {
                            ?>
                            <select name="<?php functions::xecho($key); ?>" id="<?php functions::xecho($key); ?>" >
                                <option value=""><?php echo _CHOOSE; ?>...</option>
                                <?php
                                for ($j = 0; $j < count($indexes[$key]['values']); ++$j) {
                                    ?>
                                    <option value="<?php functions::xecho($indexes[$key]['values'][$j]['id']); ?>" <?php
                                    if ($indexes[$key]['values'][$j]['id'] == $indexes[$key]['value']) {
                                        echo 'selected="selected"';
                                    } ?>><?php echo $indexes[$key]['values'][$j]['label']; ?></option><?php
                                } ?>
                            </select><?php
                        }
            echo '</td>';

            if ($i % 2 == 1 && $i != 0) { // impair
                echo '</tr>';
            } else {
                if ($i + 1 == count($indexes)) {
                    echo '<td  colspan="2">&nbsp;</td></tr>';
                }
            }
            ++$i;
        } ?>
                </table>
                <table cellpadding="2" cellspacing="2" border="0" width="100%">
                <?php
                if ($core->is_module_loaded('tags') && ($core->test_service('tag_view', 'tags', false) == 1)) {
                    include_once 'modules/tags/templates/details/index.php';
                }
        if ($core->is_module_loaded('thesaurus') && ($core->test_service('thesaurus_view', 'thesaurus', false) == 1)) {
            require_once 'modules'.DIRECTORY_SEPARATOR.'thesaurus'
                                    .DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_modules_tools.php';
            $thesaurus = new thesaurus();

            $thesaurusListRes = array();

            $thesaurusListRes = $thesaurus->getThesaursusListRes($s_id);

            echo '<tr id="thesaurus_tr_label" >';
            echo '<th align="left" class="picto" ><i class="fa fa-bookmark fa-2x" title="'._THESAURUS.'"></i></th>';
            echo '<td style="font-weight:bold;width:200px;">'._THESAURUS.'</td>';
            echo '<td id="thesaurus_field" colspan="6"><select multiple="multiple" id="thesaurus" name="thesaurus[]" data-placeholder=" "';

            if (!$core->test_service('add_thesaurus_to_res', 'thesaurus', false)) {
                echo 'disabled="disabled"';
            }

            echo '>';
            if (!empty($thesaurusListRes)) {
                foreach ($thesaurusListRes as $key => $value) {
                    echo '<option title="'.functions::show_string($value['LABEL']).'" data-object_type="thesaurus_id" id="thesaurus_'.$value['ID'].'"  value="'.$value['ID'].'"';
                    echo ' selected="selected"';
                    echo '>'
                                                .functions::show_string($value['LABEL'])
                                                .'</option>';
                }
            }

            echo '</select> <i onclick="lauch_thesaurus_list(this);" class="fa fa-search" title="parcourir le thésaurus" aria-hidden="true" style="cursor:pointer;"></i></td>';
            echo '</tr>';
            echo '<div onClick="$(\'return_previsualise_thes\').style.display=\'none\';" id="return_previsualise_thes" style="cursor: pointer; display: none; border-radius: 10px; box-shadow: 10px 10px 15px rgba(0, 0, 0, 0.4); padding: 10px; width: auto; height: auto; position: absolute; top: 0; left: 0; z-index: 999; color: #4f4b47; text-shadow: -1px -1px 0px rgba(255,255,255,0.2);background:#FFF18F;border-radius:5px;overflow:auto;">\';
                                                    <input type="hidden" id="identifierDetailFrame" value="" />
                                                </div>';
            echo '<script>$j("#thesaurus").chosen({width: "95%", disable_search_threshold: 10});getInfoIcon();</script>';
            echo '<style>#thesaurus_chosen .chosen-drop{display:none;}</style>';

            /*****************/
        }

        if ($core->is_module_loaded('fileplan') && ($core->test_service('put_doc_in_fileplan', 'fileplan', false) == 1) && $fileplanLabel != '') {
            //Requete pour récupérer position_label
            $stmt = $db->query('SELECT position_label FROM fp_fileplan_positions INNER JOIN fp_res_fileplan_positions 
                                        ON fp_fileplan_positions.position_id = fp_res_fileplan_positions.position_id
                                        WHERE fp_res_fileplan_positions.res_id=?', array($idCourrier));

            while ($res_fileplan = $stmt->fetchObject()) {
                if (!isset($positionLabel)) {
                    $positionLabel = $res_fileplan->position_label;
                } else {
                    $positionLabel = $positionLabel.' / '.$res_fileplan->position_label;
                }
            }

            //Requete pour récuperer fileplan_label
            $stmt = $db->query('SELECT fileplan_label FROM fp_fileplan INNER JOIN fp_res_fileplan_positions
                                        ON fp_fileplan.fileplan_id = fp_res_fileplan_positions.fileplan_id
                                        WHERE fp_res_fileplan_positions.res_id=? AND fp_fileplan.user_id = ?', array($idCourrier, $_SESSION['user']['UserId']));
            $res2 = $stmt->fetchObject();
            $fileplanLabel = $res2->fileplan_label;
            $planClassement = $fileplanLabel.' / '.$positionLabel; ?>
                                <tr class="col">
                                    <th align="left" class="picto">
                                        <i class="fa fa-bookmark fa-2x" title="<?php echo _FILEPLAN; ?>"></i>
                                    </th>
                                    <td align="left" width="200px">
                                        <?php echo _FILEPLAN; ?> :
                                    </td>
                                    <td colspan="6">
                                        <input type="text" class="readonly" readonly="readonly" style="width:95%;" value="<?php functions::xecho($planClassement); ?>" size="110"  />
                                    </td>
                                </tr>
                        <?php
        } ?>
                </table>
            </div>
            <?php
    } ?>
        </div>
    </form>
    <br>
    <br>    
    <?php
}
    ?>
</div>
<?php 
    $technicalInfo_frame = '<div class="detailsDisplayDiv" id="uniqueDetailsDiv" style="display:none;">';
    $technicalInfo_frame .= '<iframe src="" name="uniqueDetailsIframe" width="100%" align="left" scrolling="yes" frameborder="0" id="uniqueDetailsIframe" style="height:100%;"></iframe>';
    $technicalInfo_frame .= '</div>';
    echo $technicalInfo_frame; ?>     
    </div>
</div>
</div>
<?php
//INITIALIZE INDEX TABS

//OUTGOING CREATION MODE
if ($_SESSION['indexation'] == true && $category == 'outgoing') {
    $_SESSION['indexation'] = false;
    $is_outgoing_indexing_mode = false;
    $selectAttachments = 'SELECT attachment_type FROM res_view_attachments'
        ." WHERE res_id_master = ? and coll_id = ? and status <> 'DEL' and attachment_type in ('outgoing_mail', 'outgoing_mail_signed')";
    $stmt = $db->query($selectAttachments, array($_SESSION['doc_id'], $_SESSION['collection_id_choice']));
    if ($stmt->rowCount() == 0) {
        //launch outgoing_mail creation
        echo '<script type="text/javascript">document.getElementById(\'responses_tab\').click();showAttachmentsForm(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=attachments_content&fromDetail=create&cat=outgoing\',\'98%\',\'auto\');</script>';
    }
}

$_SESSION['info'] = '';
