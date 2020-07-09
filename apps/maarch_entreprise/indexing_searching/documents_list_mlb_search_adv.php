<?php
/*
*
*    Copyright 2008,2012 Maarch
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
 * @brief   Displays document list in search mode
 *
 * @file
 *
 * @author Yves Christian Kpakpo <dev@maarch.org>
 * @date $date$
 *
 * @version $Revision$
 * @ingroup apps
 */
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_request.php';
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php';
require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_manage_status.php';
require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR
            .'class'.DIRECTORY_SEPARATOR.'class_lists.php';

$status_obj = new manage_status();
$sec = new security();
$core_tools = new core_tools();
$request = new request();
$list = new lists();

//Labels
if ($core_tools->is_module_loaded('labels')) {
    require_once 'modules'.DIRECTORY_SEPARATOR.'labels'.DIRECTORY_SEPARATOR
    .'class'.DIRECTORY_SEPARATOR
    .'class_modules_tools.php';
    $labels = new labels();
}

//Include definition fields
include_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR.'definition_mail_categories.php';

//Parameters
$urlParameters = '';
    //Mode
    $mode = 'normal';
    if (isset($_REQUEST['mode']) && !empty($_REQUEST['mode'])) {
        $mode = $core_tools->wash($_REQUEST['mode'], 'alphanum', _MODE);
    }
     $urlParameters .= '&mode='.$mode;
    //No details
    $showIconDetails = true;
    if (isset($_REQUEST['nodetails'])) {
        $showIconDetails = false;
        $urlParameters .= '&nodetails';
    }
    //module
    if (isset($_REQUEST['modulename'])) {
        $urlParameters .= '&modulename='.$_REQUEST['modulename'];
    }

    //Form
    if (isset($_REQUEST['action_form'])) {
        $urlParameters .= '&action_form='.$_REQUEST['action_form'];
    }

//Start
if ($mode == 'normal') {
    $saveTool = true;
    $useTemplate = true;
    $exportTool = true;
    $printTool = true;
    $bigPageTitle = true;
    $standaloneForm = false;
    $radioButton = false;

    //Templates
    $defaultTemplate = 'documents_list_search_adv';
    $selectedTemplate = $list->getTemplate();
    if (empty($selectedTemplate)) {
        if (!empty($defaultTemplate)) {
            $list->setTemplate($defaultTemplate);
            $selectedTemplate = $list->getTemplate();
        }
    }
    $template_list = array();
    array_push($template_list, 'documents_list_search_adv');

    //For status icon
    $extension_icon = '';
    if ($selectedTemplate != 'none') {
        $extension_icon = '_big';
    }

    //error and search url
    $url_error = $_SESSION['config']['businessappurl'].'index.php?page=search_adv_error&dir=indexing_searching';
    $url_search = $_SESSION['config']['businessappurl'].'index.php?page=search_adv&dir=indexing_searching';

    //error
    $_SESSION['error_search'] = '<p style="text-align:center;color:red;"><i class="fa fa-times fa-2x"></i><br />'
        ._NO_RESULTS.'</p><br/><br/><div align="center"><strong><a href="javascript://" '
        .' onclick = "window.top.location.href=\''.$url_search.'\'">'._MAKE_NEW_SEARCH.'</a></strong></div>';
} elseif ($mode == 'popup' || $mode == 'frame') {
    $core_tools->load_html();
    $core_tools->load_header('', true, false);
    $core_tools->load_js();
    $time = $core_tools->get_session_time_expire(); ?>

<body>
    <div id="container" style="height:auto;">
        <div class="error" id="main_error">
            <?php functions::xecho($_SESSION['error']); ?>
        </div>
        <div class="info" id="main_info">
            <?php functions::xecho($_SESSION['info']); ?>
        </div>
        <div id="divList"><?php

    $saveTool = false;
    $useTemplate = false;
    $exportTool = false;
    $printTool = false;
    $bigPageTitle = false;
    $radioButton = true;

    if ($mode == 'popup') {
        //Form object
        $standaloneForm = true;
        $formMethod = 'get';
        $hiddenFormFields = array();
        array_push($hiddenFormFields, array('ID' => 'display', 'NAME' => 'display', 'VALUE' => 'true'));
        array_push($hiddenFormFields, array('ID' => 'page', 'NAME' => 'page', 'VALUE' => $_REQUEST['action_form']));
        if (isset($_REQUEST['modulename']) && !empty($_REQUEST['modulename'])) {
            array_push($hiddenFormFields, array('ID' => 'module', 'NAME' => 'module', 'VALUE' => $_REQUEST['modulename']));
            $formAction = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page='
                .$_REQUEST['action_form'].'&module='.$_REQUEST['modulename'];
        } else {
            $formAction = $_SESSION['config']['businessappurl']
                .'index.php?display=true&page='
                .$_REQUEST['action_form'];
        }

        $buttons = array();
        if (isset($_REQUEST['fromValidateMail'])) {
            array_push(
                $buttons,
                array('ID' => 'valid',
                                        'LABEL' => _VALIDATE,
                                        'ACTION' => 'formList.submit();opener.$(\'to_link\').click();',
                                       )
                        );
        } elseif ($_SESSION['fromValidateMail'] == 'ok') {
            array_push(
                $buttons,
                array('ID' => 'valid',
                                        'LABEL' => _VALIDATE,
                                        'ACTION' => 'formList.submit();',
                                       )
                        );
        } else {
            array_push(
                $buttons,
                array('ID' => 'valid',
                                        'LABEL' => _VALIDATE,
                                        //'ACTION'    => 'formList.submit();opener.$(\'attach\').click();'
                                        'ACTION' => 'formList.submit();',
                                       )
                        );
        }

        array_push(
            $buttons,
            array('ID' => 'close',
                                    'LABEL' => _CLOSE_WINDOW,
                                    'ACTION' => 'window.top.close();',
                                   )
                    );
    }

    //error and search url

    if ($_REQUEST['mode'] == 'popup') {
        $url_error = $_SESSION['config']['businessappurl']
        .'index.php?page=search_adv_error'
        .'&dir=indexing_searching&display=true&mode='.$_REQUEST['mode'];
    } else {
        $url_error = $_SESSION['config']['businessappurl']
        .'index.php?page=search_adv_error'
        .'&dir=indexing_searching';
    }

    if (isset($_REQUEST['exclude'])) {
        $_SESSION['excludeId'] = $_REQUEST['exclude'];
    }
    if ($_REQUEST['mode'] == 'popup' && isset($_SESSION['excludeId'])) {
        $urlParameters .= '&exclude='.$_SESSION['excludeId'];
    }

    $url_search = $_SESSION['config']['businessappurl']
        .'index.php?display=true&dir=indexing_searching'
        .'&page=search_adv&load&mode='.$mode.$urlParameters;

    //Displayed error text
    $_SESSION['error_search'] = '<p style="color:red;text-align:center;"><i class="fa fa-times fa-2x"></i><br />'
        ._NO_RESULTS.'</p><br/><br/><div align="center"><strong><a href="javascript://" '
        .' onclick = "window.top.location.href=\''.$url_search.'\'">'._MAKE_NEW_SEARCH.'</a></strong></div>';
}

/************Construction de la requete*******************/
//Table or view
    $_SESSION['collection_id_choice'] = 'letterbox_coll';
    $view = $sec->retrieve_view_from_coll_id($_SESSION['collection_id_choice']);
    $select = array();
    $select[$view] = array();

//Fields
    //Documents
    array_push(
        $select[$view],
        'res_id',
        'res_id as is_labeled',
        'alt_identifier',
        'priority',
        'status',
        'subject',
        'category_id as category_img',
        'category_id',
        'dest_user',
        'type_label',
                                'creation_date',
        'entity_label',
        'res_id as count_attachment',
        'filename',
        'res_id as real_dest'
    );
//Where clause
    $where_tab = array();
    $arrayPDO = array();
    //From search
    if (!empty($_SESSION['searching']['where_request'])) {
        $where_tab[] = $_SESSION['searching']['where_request'].'(1=1)';
        $arrayPDO = array_merge($arrayPDO, $_SESSION['searching']['where_request_parameters']);
    }

    if (isset($_REQUEST['exclude'])) {
        $_SESSION['excludeId'] = $_REQUEST['exclude'];
    }

    //From popup excluding some id
    if ($_REQUEST['mode'] == 'popup' && isset($_SESSION['excludeId'])) {
        $where_tab[] = 'res_id <> :excludeId and '
                        .'(res_id not in (SELECT res_parent FROM res_linked WHERE res_child = :excludeId) and '
                        .'res_id not in (SELECT res_child FROM res_linked WHERE res_parent = :excludeId))';
        $arrayPDO = array_merge($arrayPDO, array(':excludeId' => $_SESSION['excludeId']));
        unset($_SESSION['excludeId']);
    }

    $status = $status_obj->get_not_searchable_status();

    if (count($status) > 0) {
        $status_tab = array();
        // $status_str = '';
        for ($i = 0; $i < count($status); ++$i) {
            array_push($status_tab, $status[$i]['ID']);
        }
        // $status_str = implode(' ,', $status_tab);
        $where_tab[] = 'status not in (:statustab)';
        $arrayPDO = array_merge($arrayPDO, array(':statustab' => $status_tab));
    }

    if (isset($_SESSION['where_from_contact_check']) && $_SESSION['where_from_contact_check'] != '' && (isset($_REQUEST['fromContactCheck']) || $_SESSION['fromContactCheck'] == 'ok')) {
        for ($ind_bask = 0; $ind_bask < count($_SESSION['user']['baskets']); ++$ind_bask) {
            if ($_SESSION['user']['baskets'][$ind_bask]['coll_id'] == $_SESSION['collection_id_choice']) {
                if (isset($_SESSION['user']['baskets'][$ind_bask]['clause']) && trim($_SESSION['user']['baskets'][$ind_bask]['clause']) != '') {
                    $_SESSION['searching']['comp_query'] .= ' or ('.$_SESSION['user']['baskets'][$ind_bask]['clause'].')';
                }
            }
        }
        $_SESSION['searching']['comp_query'] = preg_replace('/^ or/', '', $_SESSION['searching']['comp_query']);
    }
    //From searching comp query
    if (isset($_SESSION['searching']['comp_query']) && trim($_SESSION['searching']['comp_query']) != '') {
        $where_clause = $sec->get_where_clause_from_coll_id($_SESSION['collection_id_choice']);

        $user = \User\models\UserModel::getByLogin(['login' => $_SESSION['user']['UserId'], 'select' => ['id']]);
        $whereFolders = '1=0';
        $whereFollowed = '1=0';
        if (\Group\controllers\PrivilegeController::hasPrivilege(['privilegeId' => 'include_folder_perimeter', 'userId' => $user['id']])) {
            $whereFollowed = 'res_id in (select res_id from users_followed_resources where user_id = :userSerialId)';

            $whereFolders = 'res_id in (select res_id from resources_folders, folders, entities_folders ';
            $whereFolders .= 'where folders.id = entities_folders.folder_id AND folders.id = resources_folders.folder_id AND (user_id = :userSerialId OR entity_id in (:userEntitiesId)))';
            $entities = \User\models\UserModel::getEntitiesByLogin(['login' => $_SESSION['user']['UserId']]);
            $entities = array_column($entities, 'id');

            $arrayPDO[':userSerialId'] = $user['id'];
            $arrayPDO[':userEntitiesId'] = $entities;
        }
        if (count($where_tab) != 0) {
            $where = implode(' and ', $where_tab);
            $where_request = '('.$where.') and (('.$where_clause.') or ('.$_SESSION['searching']['comp_query'].") OR ({$whereFolders}) OR ({$whereFollowed}))";
        } else {
            $where_request = '('.$where_clause.' or '.$_SESSION['searching']['comp_query']. " OR ({$whereFolders}) OR ({$whereFollowed}))";
        }

        $add_security = false;
    } else {
        $where_request = implode(' and ', $where_tab);
        $add_security = true;
    }

//Order
    $order = $order_field = '';
    $order = $list->getOrder();
    $order_field = $list->getOrderField();
    $_SESSION['save_list']['order'] = $order;
    $_SESSION['save_list']['order_field'] = $order_field;

    if (!empty($order_field) && !empty($order)) {
        if ($_REQUEST['order_field'] == 'alt_identifier') {
            $orderstr = 'order by order_alphanum(alt_identifier)'.' '.$order;
        } elseif ($_REQUEST['order_field'] == 'priority') {
            $where_request .= ' and '.$view.'.priority = priorities.id';
            $select['priorities'] = ['order', 'id'];
            $orderstr = 'order by priorities.order '.$order;
        } else {
            $orderstr = 'order by '.$order_field.' '.$order;
        }
    } else {
        $list->setOrder();
        $list->setOrderField('res_id');
        $orderstr = 'order by res_id desc';
    }

//URL extra Parameters
    $parameters = '';
    $start = $list->getStart();
    if (!empty($order_field) && !empty($order)) {
        $parameters .= '&order='.$order.'&order_field='.$order_field;
    }
    if (!empty($what)) {
        $parameters .= '&what='.$what;
    }
    if (!empty($selectedTemplate)) {
        $parameters .= '&template='.$selectedTemplate;
    }
    
    $parameters .= '&start='.$start;
    $_SESSION['save_list']['start'] = $start;

    if (isset($_SESSION['where_from_contact_check']) && $_SESSION['where_from_contact_check'] != '' && (isset($_REQUEST['fromContactCheck']) || $_SESSION['fromContactCheck'] == 'ok')) {
        $_SESSION['fromContactCheck'] = 'ok';
        $where_request .= $_SESSION['where_from_contact_check'];
    }
    if (isset($_REQUEST['lines'])) {
        $limit = $_REQUEST['lines'];
    } else {
        $limit = 'default';
    }
//Query
    $tab = $request->PDOselect($select, $where_request, $arrayPDO, $orderstr, $_SESSION['config']['databasetype'], $limit, false, '', '', '', $add_security, false, false, $_SESSION['save_list']['start']);
    // $request->show();
//Result array
    $tabI = count($tab);

    $resIdList = [];

    for ($i = 0; $i < $tabI; ++$i) {
        $tabJ = count($tab[$i]);
        for ($j = 0; $j < $tabJ; ++$j) {
            foreach (array_keys($tab[$i][$j]) as $value) {
                if ($tab[$i][$j][$value] == 'is_labeled'
                    && $core_tools->is_module_loaded('labels')
                    && (isset($_SESSION['user']['services']['labels'])
                    && $_SESSION['user']['services']['labels'] === true)
                ) {
                    $str_label = $labels->get_labels_resid($tab[$i][$j]['value'], $_SESSION['collection_id_choice']);
                    if (!empty($str_label)) {
                        $tab[$i][$j]['value'] = '';
                    } else {
                        $tab[$i][$j]['value'] = '&nbsp;';
                    }
                    $tab[$i][$j]['label'] = _LABELS;
                    $tab[$i][$j]['size'] = '4';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = true;
                    $tab[$i][$j]['order'] = false;
                }

                if ($tab[$i][$j][$value] == 'res_id') {
                    $resIdList[] = intval($tab[$i][$j]['value']);
                    $tab[$i][$j]['res_id'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['label'] = _GED_NUM;
                    $tab[$i][$j]['size'] = '4';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    if ($_REQUEST['mode'] == 'popup') {
                        $tab[$i][$j]['show'] = false;
                    } else {
                        $tab[$i][$j]['show'] = true;
                    }
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['order'] = 'res_id';
                    $_SESSION['mlb_search_current_res_id'] = $tab[$i][$j]['value'];
                    // notes
                    $db = new Database();

                    $arrayPDO = array();
                    $query = 'SELECT ';
                    $query .= 'notes.id ';
                    $query .= 'FROM ';
                    $query .= 'notes ';
                    $query .= 'left join ';
                    $query .= 'note_entities ';
                    $query .= 'on ';
                    $query .= 'notes.id = note_entities.note_id ';
                    $query .= 'WHERE ';
                    $query .= 'identifier = ? ';
                    $arrayPDO = array($tab[$i][$j]['value']);

                    $query .= 'AND ';
                    $query .= '( ';
                    $query .= '( ';
                    $query .= 'item_id IN (';

                    if (!empty($_SESSION['user']['entities'])) {
                        foreach ($_SESSION['user']['entities'] as $entitiestmpnote) {
                            $query .= '?, ';
                            $arrayPDO = array_merge($arrayPDO, array($entitiestmpnote['ENTITY_ID']));
                        }
                        $query = substr($query, 0, -2);
                    } else {
                        $query .= "''";
                    }

                    $query .= ') ';
                    $query .= 'OR ';
                    $query .= 'item_id IS NULL ';
                    $query .= ') ';
                    $query .= 'OR ';
                    $query .= 'user_id = ? ';
                    $query .= ') ';
                    $user = \User\models\UserModel::getByLogin(['login' => $_SESSION['user']['UserId'], 'select' => ['id']]);
                    $arrayPDO = array_merge($arrayPDO, array($user['id']));

                    $stmt = $db->query($query, $arrayPDO);
                    $tab[$i][$j]['hasNotes'] = $stmt->fetchObject();
                    $tab[$i][$j]['res_multi_contacts'] = $_SESSION['mlb_search_current_res_id'];
                }
                if ($tab[$i][$j][$value] == 'alt_identifier') {
                    $target_chrono = $_SESSION['searching']['where_request_parameters'][':chrono'];
                    $target_chrono = str_replace('%', '', $target_chrono);
                    $chrono = $tab[$i][$j]['value'];
                    $chrono = str_replace($target_chrono, '<i style="background: #135F7F none repeat scroll 0 0;border-radius: 4px;color: white;padding: 3px;" title="mot cible">'.$target_chrono.'</i>', $chrono);
                    $tab[$i][$j]['res_id'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['label']=_CHRONO_NUMBER;
                    $tab[$i][$j]['value'] = $chrono;
                    $tab[$i][$j]['size'] = '10';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = true;
                    $tab[$i][$j]['order'] = 'alt_identifier';
                }
                if ($tab[$i][$j][$value] == 'type_label') {
                    if (!empty($_SESSION['searching']['where_request_parameters'][':doctypesChosen'])) {
                        $doctype = '<i style="background: #135F7F none repeat scroll 0 0;border-radius: 4px;color: white;padding: 3px;" title="mot cible">'.$tab[$i][$j]['value'].'</i>';
                    } else {
                        $doctype = $tab[$i][$j]['value'];
                    }
                    $tab[$i][$j]['label'] = _TYPE;
                    $tab[$i][$j]['value'] = $doctype;
                    $tab[$i][$j]['size'] = '15';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = true;
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['order'] = 'type_label';
                }

                if ($tab[$i][$j][$value] == 'status') {
                    $style = "style='color:".$_SESSION['mail_priorities_color'][$fakeId].";font-size:36px'";

                    $tab[$i][$j]['label'] = _STATUS;
                    $res_status = $status_obj->get_status_data($tab[$i][$j]['value'], $extension_icon);
                    $statusCmp = $tab[$i][$j]['value'];
                    $img_class = substr($res_status['IMG_SRC'], 0, 2);
                    // $tab[$i][$j]['value'] = '<img src = "'.$res_status['IMG_SRC'].'" alt = "'.$res_status['LABEL'].'" title = "'.$res_status['LABEL'].'">';
                    if (!isset($res_status['IMG_SRC']) || empty($res_status['IMG_SRC'])) {
                        $tab[$i][$j]['value'] = '<i '.$style." class = 'fm fm-letter-status-new fm-3x' alt = '".$res_status['LABEL']."' title = '".$res_status['LABEL']."'></i>";
                    } else {
                        $tab[$i][$j]['value'] = '<i '.$style." class = '".$img_class.' '.$res_status['IMG_SRC'].' '.$img_class."-3x' alt = '".$res_status['LABEL']."' title = '".$res_status['LABEL']."'></i>";
                    }
                    $tab[$i][$j]['size'] = '5';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = true;
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['order'] = 'status';
                }

                if ($tab[$i][$j][$value] == 'subject') {
                    mb_internal_encoding('UTF-8');

                    $target_subj = $_SESSION['searching']['where_request_parameters'][':subject'];

                    $target_subj = str_replace('%', '', trim($target_subj));

                    if (!empty($target_subj)) {
                        $subj = $request->cut_string($request->show_string($tab[$i][$j]['value']), 250);

                        $subj_no_accent = functions::normalize($subj);

                        $begin_pos_subj = mb_strpos($subj_no_accent, $target_subj);

                        if ($begin_pos_subj != false || $begin_pos_subj === 0) {
                            $result = strlen($subj) - strlen($subj_no_accent);

                            $subj_length = mb_strlen($target_subj);

                            $target_subj_new = mb_substr($subj, $begin_pos_subj, $subj_length);

                            $subj = str_replace($target_subj_new, '<i style="background: #135F7F none repeat scroll 0 0;border-radius: 4px;color: white;padding: 3px;" title="mot cible">'.$target_subj_new.'</i>', $subj);
                        } else {
                            $subj = $request->show_string($tab[$i][$j]['value']);
                        }
                    } else {
                        $subj = $request->show_string($tab[$i][$j]['value']);
                    }

                    $tab[$i][$j]['label'] = _SUBJECT;
                    $tab[$i][$j]['value'] = $subj;
                    $tab[$i][$j]['size'] = '25';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = true;
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['order'] = 'subject';
                }

                if ($tab[$i][$j][$value] == 'creation_date') {
                    $tab[$i][$j]['label'] = _REG_DATE;
                    $tab[$i][$j]['size'] = '10';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = true;
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['value'] = $request->format_date_db($tab[$i][$j]['value'], false);
                    $tab[$i][$j]['order'] = 'creation_date';
                }

                if ($tab[$i][$j][$value] == 'entity_label') {
                    if (!empty($_SESSION['searching']['where_request_parameters'][':serviceChosen'])) {
                        $service = '<i style="background: #135F7F none repeat scroll 0 0;border-radius: 4px;color: white;padding: 3px;" title="mot cible">'.$tab[$i][$j]['value'].'</i>';
                    } else {
                        $service = $tab[$i][$j]['value'];
                    }
                    $tab[$i][$j]['label'] = _ENTITY;
                    $tab[$i][$j]['value'] = $request->show_string($service);
                    $tab[$i][$j]['size'] = '10';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = false;
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['order'] = 'entity_label';
                }

                if ($tab[$i][$j][$value] == 'category_id') {
                    if (!empty($_SESSION['searching']['where_request_parameters'][':category'])) {
                        $cat = '<i style="background: #135F7F none repeat scroll 0 0;border-radius: 4px;color: white;padding: 3px;" title="mot cible">'.$_SESSION['coll_categories']['letterbox_coll'][$tab[$i][$j]['value']].'</i>';
                    } else {
                        $cat = $_SESSION['coll_categories']['letterbox_coll'][$tab[$i][$j]['value']];
                    }
                    $categoryId = $tab[$i][$j]['value'];
                    $_SESSION['mlb_search_current_category_id'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['value'] = $cat;
                    $tab[$i][$j]['label'] = _CATEGORY;
                    $tab[$i][$j]['size'] = '10';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = true;
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['order'] = 'category_id';
                }

                if ($tab[$i][$j][$value] == 'category_img') {
                    $tab[$i][$j]['label'] = _CATEGORY;
                    $tab[$i][$j]['size'] = '10';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = false;
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    $my_imgcat = get_img_cat($tab[$i][$j]['value'], $extension_icon);
                    $tab[$i][$j]['value'] = $my_imgcat;
                    $tab[$i][$j]['value'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['order'] = 'category_id';
                }

                if ($tab[$i][$j][$value] == 'priority') {
                    $fakeId = null;
                    foreach ($_SESSION['mail_priorities_id'] as $key => $prioValue) {
                        if ($prioValue == $tab[$i][$j]['value']) {
                            $fakeId = $key;
                        }
                    }

                    $priority = $_SESSION['mail_priorities'][$fakeId];
                    if (!empty($_SESSION['searching']['where_request_parameters'][':priorityChosen'])) {
                        $priority = '<i style="background: #009dc5 none repeat scroll 0 0;border-radius: 4px;color: white;padding: 3px;" title="mot cible">'.$_SESSION['mail_priorities'][$fakeId].'</i>';
                    }
                    $tab[$i][$j]['value'] = $priority;
                    $tab[$i][$j]['label'] = _PRIORITY;
                    $tab[$i][$j]['size'] = '10';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = false;
                    $tab[$i][$j]['order'] = 'priority';
                }

                if ($tab[$i][$j][$value] == 'dest_user') {
                    if (!empty($_SESSION['searching']['where_request_parameters'][':destinataireChosen'])) {
                        foreach ($_SESSION['searching']['where_request_parameters'][':destinataireChosen'] as $key => $value) {
                            if ($value == $tab[$i][$j]['value']) {
                                $user = \User\models\UserModel::getByLogin(['login' => $value, 'select' => ['firstname', 'lastname']]);
                                $target_dest = $value;
                                $target_dest = str_replace('%', '', $target_dest);
                                $dest = $tab[$i][$j]['value'];
                                $dest = str_replace($user['firstname'] . ' ' . $user['lastname'], '<i style="background: #135F7F none repeat scroll 0 0;border-radius: 4px;color: white;padding: 3px;" title="mot cible">'.$user['firstname'] . ' ' . $user['lastname'].'</i>', $user['firstname'] . ' ' . $user['lastname']);
                                break;
                            }
                        }
                    } else {
                        if (!empty($tab[$i][$j]['value'])) {
                            $user = \User\models\UserModel::getByLogin(['login' => $tab[$i][$j]['value'], 'select' => ['firstname', 'lastname']]);
                            $dest = $tab[$i][$j]['value'];
                            $dest = $user['firstname'] . ' ' . $user['lastname'];
                        } else {
                            $dest = '<i style="opacity:0.5;">'._UNDEFINED_DATA.'</i>';
                        }
                    }

                    $tab[$i][$j]["label"]=_DEST_USER;
                    $tab[$i][$j]["size"]="10";
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]["value"]=$dest;
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = false;
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    if ($categoryId == 'outgoing') {
                        $tab[$i][$j]['value'] = '<b>'._WRITTEN_BY.' : </b>'.$tab[$i][$j]['value'];
                    } else {
                        $tab[$i][$j]['value'] = '<b>'._PROCESSED_BY.' : </b>'.$tab[$i][$j]['value'];
                    }
                    $tab[$i][$j]['order'] = false;
                }

                if ($tab[$i][$j][$value] == 'count_attachment') {
                    $query = "SELECT count(res_id) as total FROM res_attachments 
                            WHERE res_id_master = ? 
                            AND status NOT IN ('DEL', 'OBS') AND (status <> 'TMP' or (typist = ? and status = 'TMP'))";
                    $arrayPDO = array($tab[$i][0]['res_id'], $_SESSION['user']['UserId']);
                    $stmt2 = $db->query($query, $arrayPDO);
                    $return_count = $stmt2->fetchObject();

                    $tab[$i][$j]['label'] = _ATTACHMENTS;
                    $tab[$i][$j]['size'] = '12';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['value'] = "$return_count->total";
                    $tab[$i][$j]['show'] = false;
                    $tab[$i][$j]['order'] = 'count_attachment';
                }

//                if ($tab[$i][$j][$value] == 'contact_firstname') {
//                    $contact_firstname = $tab[$i][$j]['value'];
//                    $tab[$i][$j]['show'] = false;
//                }
//                if ($tab[$i][$j][$value] == 'contact_lastname') {
//                    $contact_lastname = $tab[$i][$j]['value'];
//                    $tab[$i][$j]['show'] = false;
//                }
//                if ($tab[$i][$j][$value] == 'contact_society') {
//                    $contact_society = $tab[$i][$j]['value'];
//                    $tab[$i][$j]['show'] = false;
//                }
//                if ($tab[$i][$j][$value] == 'user_firstname') {
//                    $user_firstname = $tab[$i][$j]['value'];
//                    $tab[$i][$j]['show'] = false;
//                }
//                if ($tab[$i][$j][$value] == 'user_lastname') {
//                    $user_lastname = $tab[$i][$j]['value'];
//                    $tab[$i][$j]['show'] = false;
//                }
//
//                if ($tab[$i][$j][$value] == 'is_multicontacts') {
//                    if ($tab[$i][$j]['value'] == 'Y') {
//                        $tab[$i][$j]['label'] = _CONTACT;
//                        $tab[$i][$j]['size'] = '10';
//                        $tab[$i][$j]['label_align'] = 'left';
//                        $tab[$i][$j]['align'] = 'left';
//                        $tab[$i][$j]['valign'] = 'bottom';
//                        $tab[$i][$j]['show'] = false;
//                        if ($_SESSION['mlb_search_current_category_id'] == 'incoming') {
//                            $prefix = '<b>'._TO_CONTACT_C.'</b>';
//                        } elseif ($_SESSION['mlb_search_current_category_id'] == 'outgoing' || $_SESSION['mlb_search_current_category_id'] == 'internal') {
//                            $prefix = '<b>'._FOR_CONTACT_C.'</b>';
//                        } else {
//                            $prefix = '';
//                        }
//                        $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
//                        $tab[$i][$j]['value'] = $prefix.' '._MULTI_CONTACT;
//                        $tab[$i][$j]['order'] = false;
//                        $tab[$i][$j]['is_multi_contacts'] = 'Y';
//                        $tab[$itContactI][$itContactJ]['value'] = null;
//                    }
//                }

                if ($tab[$i][$j][$value] == 'folder_name') {
                    $tab[$i][$j]['label'] = _FOLDER;
                    $tab[$i][$j]['size'] = '10';
                    $tab[$i][$j]['label_align'] = 'left';
                    $tab[$i][$j]['align'] = 'left';
                    $tab[$i][$j]['valign'] = 'bottom';
                    $tab[$i][$j]['show'] = true;
                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                    $tab[$i][$j]['order'] = 'folder_name';
                }

                // -------------------------------------------------------------
//                if ($tab[$i][$j][$value] == 'address_id') {
//                    $addressId = $tab[$i][$j]['value'];
//                    $tab[$i][$j]['show'] = false;
//                }
//                if ($tab[$i][$j][$value] == 'exp_user_id') {
//                    $itContactI = $i;
//                    $itContactJ = $j;
//
//                    if (empty($contact_lastname) && empty($contact_firstname) && empty($user_lastname) && empty($user_firstname) && !empty($addressId)) {
//                        $query = 'SELECT ca.firstname, ca.lastname FROM contact_addresses ca WHERE ca.id = ?';
//                        $arrayPDO = array($addressId);
//                        $stmt2 = $db->query($query, $arrayPDO);
//                        $return_contact = $stmt2->fetchObject();
//
//                        if (!empty($return_contact)) {
//                            $contact_firstname = $return_contact->firstname;
//                            $contact_lastname = $return_contact->lastname;
//                        }
//                    }
//
//                    $tab[$i][$j]['label'] = _CONTACT;
//                    $tab[$i][$j]['size'] = '10';
//                    $tab[$i][$j]['label_align'] = 'left';
//                    $tab[$i][$j]['align'] = 'left';
//                    $tab[$i][$j]['valign'] = 'bottom';
//                    $tab[$i][$j]['show'] = false;
//                    $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
//                    if (empty($contact_lastname) && empty($contact_firstname) && empty($user_lastname) && empty($user_firstname) && empty($contact_society)) {
//                        $tab[$i][$j]['value'] = '<i style="opacity:0.5;">'._UNDEFINED_DATA.'</i>';
//                    } else {
//                        $tab[$i][$j]['value'] = $contact->get_contact_information_from_view($_SESSION['mlb_search_current_category_id'], $contact_lastname, $contact_firstname, $contact_society, $user_lastname, $user_firstname);
//                    }
//
//                    $tab[$i][$j]['order'] = false;
//                }


                // Contacts
                if ($tab[$i][$j][$value] == 'real_dest') {
                    $resId = $tab[$i][$j]['value'];
                    $recipientList = \Contact\controllers\ContactController::getFormattedContacts(['resId' => $resId, 'mode' => 'recipient', 'onlyContact' => true]);

                    $senderList = \Contact\controllers\ContactController::getFormattedContacts(['resId' => $resId, 'mode' => 'sender', 'onlyContact' => true]);

                    $tab[$i][$j]['value'] = '';

                    $formattedRecipients = '';
                    $formattedSenders = '';

                    if (!empty($senderList)) {
                        if (count($senderList) >= 2) {
                            $formattedSenders = count($senderList) . ' ' . _SENDERS
                                    . '  <i class="fa fa-book fa-2x" style="cursor: pointer;" title="'
                                    . _VIEW_CONTACTS.'"onclick="'
                                    . "loadContactsList(".$resId.", 'sender');"
                                    . '"></i>';
                        } else {
                            $formattedSenders = implode("<br>", $senderList);
                        }
                        $formattedSenders = '<b>'._TO_CONTACT_C.'</b>'.$formattedSenders;
                        $tab[$i][$j]['value'] .= $formattedSenders;

                        if (!empty($recipientList)) {
                            $tab[$i][$j]['value'] .= "<br>";
                        }
                    }
                    if (!empty($recipientList)) {
                        if (count($recipientList) >= 2) {
                            $formattedRecipients = count($recipientList) . ' ' . _RECIPIENTS
                                    . '  <i class="fa fa-book fa-2x" style="cursor: pointer;" title="'
                                    . _VIEW_CONTACTS.'"onclick="'
                                    . "loadContactsList(".$resId.", 'recipient');"
                                    . '"></i>';;
                        } else {
                            $formattedRecipients = implode("<br>", $recipientList);
                        }
                        $formattedRecipients = '<b>'._FOR_CONTACT_C.'</b>'.$formattedRecipients;
                        $tab[$i][$j]['value'] .= $formattedRecipients;
                    }

                    $tab[$i][$j]['order'] = false;
                }
            }
        }
    }

$nbTab = $_SESSION['save_list']['full_count'];
if ($nbTab > 0) {
    /************Construction de la liste*******************/
    //Clé de la liste
    $listKey = 'res_id';

    //Initialiser le tableau de paramètres
    $paramsTab = array();
    $paramsTab['bool_modeReturn'] = false;                                              //Desactivation du mode return (vs echo)
    $paramsTab['listCss'] = 'listing largerList spec';                                  //css
    $paramsTab['urlParameters'] = $urlParameters.'&dir=indexing_searching';            //Parametres supplémentaires
    $paramsTab['pageTitle'] = _RESULTS.' : '.$nbTab.' '._FOUND_DOCS;              //Titre de la page
    $paramsTab['pagePicto'] = 'search';                                      //Image de la page
    $paramsTab['bool_bigPageTitle'] = $bigPageTitle;                                    //Titre de la page en grand
    $paramsTab['bool_showIconDocument'] = true;                                        //Affichage de l'icone du document
    $paramsTab['bool_showIconDetails'] = $showIconDetails;                             //Affichage de l'icone de la page de details
    $paramsTab['bool_showAttachment'] = true;                                           //Affichage du nombre de document attaché (mode étendu)
    if ($radioButton) {                                                                 //Boutton radio
        $paramsTab['bool_radioButton'] = $radioButton;
    }
    $paramsTab['defaultTemplate'] = $defaultTemplate;                                   //Default template
    if ($useTemplate && count($template_list) > 0) {                                    //Templates
        $paramsTab['templates'] = array();
        $paramsTab['templates'] = $template_list;
    }
    $paramsTab['bool_showTemplateDefaultList'] = true;                                  //Default list (no template)

    //Form attributs
    //Standalone form
    $paramsTab['bool_standaloneForm'] = $standaloneForm;
    //Method
    if (isset($formMethod) && !empty($formMethod)) {
        $paramsTab['formMethod'] = $formMethod;
    }
    //Action
    if (isset($formAction) && !empty($formAction)) {
        $paramsTab['formAction'] = $formAction;
    }
    //Hiden fields
        if (isset($hiddenFormFields) && count($hiddenFormFields) > 0) {                 //Champs hidden supplémentaire | mots clés = id, name, value
            $paramsTab['hiddenFormFields'] = array();
            $paramsTab['hiddenFormFields'] = $hiddenFormFields;
        }
    //Buttons
        if (isset($buttons) && count($buttons) > 0) {                                   //Liste des boutons de formulaire
            $paramsTab['buttons'] = array();
            $paramsTab['buttons'] = $buttons;
        }
    $paramsTab['start'] = $_SESSION['save_list']['start'];
    //Toolbar
        $paramsTab['tools'] = array();                                                  //Icones dans la barre d'outils

    if ($saveTool) {
        $save = array(
                    'script' => "createModal(form_txt);window.location.href='#top';",
                    'icon' => 'save',
                    'tooltip' => _SAVE_QUERY,
                    'disabledRules' => $nbTab.' == 0',
                    );
        array_push($paramsTab['tools'], $save);
    }

    if ($exportTool) {
        $export = array(
                    'script' => "window.open('".$_SESSION['config']['businessappurl']."index.php?display=true&page=export', '_blank');",
                    'icon' => 'file-excel',
                    'tooltip' => _EXPORT_LIST,
                    'disabledRules' => $nbTab.' == 0',
                    );
        array_push($paramsTab['tools'], $export);
        $export2 = array(
                'script' => "print_current_result_list('".$_SESSION['config']['businessappurl']."');",
                'icon' => 'print',
                'tooltip' => _PRINT_LIST,
                'disabledRules' => $nbTab.' == 0',
                );
        array_push($paramsTab['tools'], $export2);
    }

    if ($printTool) {
        $resIdList = json_encode($resIdList);
        $urlPrint = $_SESSION['config']['businessappurl'] . '../../rest/resourcesList/summarySheets?resources=' . $resIdList;
        $print = array(
//                    'script' => "window.open('".$_SESSION['config']['businessappurl']."index.php?display=true&page=print', '_blank');",
                    'script' => "window.open('" . $urlPrint . "', '_blank');",
                    'icon' => 'link',
                    'tooltip' => _PRINT_DOC_FROM_LIST,
                    'disabledRules' => $nbTab.' == 0',
                    );
        array_push($paramsTab['tools'], $print);
    }

    //Afficher la liste
    $list->showList($tab, $paramsTab, $listKey);
    // $list->debug();

    /*************************Extra javascript***********************/ ?>
            <script type="text/javascript">
                var form_txt = '<form name="frm_save_query" id="frm_save_query" action="#" method="post" class="forms" onsubmit="send_request(this.id, <?php echo "\'creation\'"; ?>);" ><h2><?php
            echo _SAVE_QUERY_TITLE; ?></h2><p><label for="query_name"><?php echo _QUERY_NAME; ?></label><input type="text" name="query_name" id="query_name" style="width:200px;" value=""/></p><br/><p class="buttons"><input type="submit" name="submit" id="submit" value="<?php
            echo _VALIDATE; ?>" class="button"/> <input type="button" name="cancel" id="cancel" value="<?php echo _CANCEL; ?>" class="button" onclick="destroyModal();"/></p></form>';

                function send_request(form_id, form_action) {
                    if (form_action == 'creation_ok') {
                        var q_name = form_id;
                        var q_creation = form_action;
                        $('modal').innerHTML = '<i class="fa fa-spinner fa-2x"></i>';

                        new Ajax.Request('<?php echo $_SESSION['config']['businessappurl']; ?>index.php?display=true&dir=indexing_searching&page=manage_query', {
                            method: 'post',
                            parameters: {
                                action: q_creation
                            },
                            onSuccess: function(answer) {
                                eval("response = " + answer.responseText)
                                if (response.status == 0) {
                                    $('modal').innerHTML = '<h2><?php echo _QUERY_SAVED; ?></h2><br/><input type="button" name="close" value="<?php echo _CLOSE_WINDOW; ?>" onclick="destroyModal();" class="button" />';
                                } else if (response.status == 2) {
                                    $('modal').innerHTML = '<div class="error"><?php echo _SQL_ERROR; ?></div>' + form_txt;
                                    form.query_name.value = this.name;
                                } else if (response.status == 3) {
                                    $('modal').innerHTML = '<div class="error"><?php echo _QUERY_NAME.' '._IS_EMPTY; ?></div>' + form_txt;
                                    form.query_name.value = this.name;
                                } else {
                                    $('modal').innerHTML = '<div class="error"><?php echo _SERVER_ERROR; ?></div>' + form_txt;
                                    form.query_name.value = this.name;
                                }
                            },
                            onFailure: function() {
                                $('modal').innerHTML = '<div class="error"><?php echo _SERVER_ERROR; ?></div>' + form_txt;
                                form.query_name.value = this.name;
                            }
                        });


                    }
                    var form = $(form_id);
                    if (form) {
                        var q_name = form.query_name.value;
                        var q_creation = form_action;
                        $('modal').innerHTML = '<i class="fa fa-spinner fa-2x"></i>';

                        new Ajax.Request('<?php echo $_SESSION['config']['businessappurl']; ?>index.php?display=true&dir=indexing_searching&page=manage_query', {
                            method: 'post',
                            parameters: {
                                name: q_name,
                                action: q_creation
                            },
                            onSuccess: function(answer) {
                                eval("response = " + answer.responseText)
                                if (response.status == 0) {
                                    $('modal').innerHTML = '<h2><?php echo _QUERY_SAVED; ?></h2><br/><input type="button" name="close" value="<?php echo _CLOSE_WINDOW; ?>" onclick="destroyModal();" class="button" />';
                                } else if (response.status == 2) {
                                    $('modal').innerHTML = '<div class="error"><?php echo _SQL_ERROR; ?></div>' + form_txt;
                                    form.query_name.value = this.name;
                                } else if (response.status == 3) {
                                    $('modal').innerHTML = '<div class="error"><?php echo _QUERY_NAME.' '._IS_EMPTY; ?></div>' + form_txt;
                                    form.query_name.value = this.name;
                                } else if (response.status == 4) {
                                    $('modal').innerHTML = '<form name="frm_save_query" id="<?php echo $_SESSION['seekName']; ?>" action="#" method="post" class="forms" onsubmit="send_request(this.id, <?php echo "\'creation_ok\'"; ?>);" ><h2><?php
            echo _SAVE_CONFIRM; ?></h2><p><b><?php echo _SAVED_ALREADY_EXIST; ?></b></p><p><?php echo _OK_FOR_CONFIRM; ?></p><br/><p class="buttons"><input type="submit" name="submit" id="submit" value="<?php
            echo _VALIDATE; ?>" class="button"/> <input type="button" name="cancel" id="cancel" value="<?php echo _CANCEL; ?>" class="button" onclick="destroyModal();"/></p></form>';
                                } else {
                                    $('modal').innerHTML = '<div class="error"><?php echo _SERVER_ERROR; ?></div>' + form_txt;
                                    form.query_name.value = this.name;
                                }
                            },
                            onFailure: function() {
                                $('modal').innerHTML = '<div class="error"><?php echo _SERVER_ERROR; ?></div>' + form_txt;
                                form.query_name.value = this.name;
                            }
                        });
                    }
                }
            </script>
            <?php
     exit();
} else {
    echo '<script type="text/javascript">window.top.location.href=\''.$url_error.'\';</script>';
    exit();
}

if ($mode == 'popup' || $mode == 'frame') {
    echo '</div>';
    echo '</div>';
    echo '</body>';
}
