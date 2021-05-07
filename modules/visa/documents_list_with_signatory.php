<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   documents_list_with_signatory
*
* @author  dev <dev@maarch.org>
* @ingroup visa
*/
require_once 'core/class/class_request.php';
require_once 'core/class/class_security.php';
require_once 'apps/'.$_SESSION['config']['app_id'].'/class/class_contacts_v2.php';
require_once 'core/class/class_manage_status.php';
require_once 'apps/'.$_SESSION['config']['app_id'].'/class/class_lists.php';

$status_obj = new manage_status();
$security = new security();
$core_tools = new core_tools();
$request = new request();
$contact = new contacts_v2();
$list = new lists();

//Include definition fields
require_once 'apps/'.$_SESSION['config']['app_id'].'/definition_mail_categories.php';

//Order
    $order = $order_field = '';
    $order = $list->getOrder();
    $order_field = $list->getOrderField();
    $_SESSION['save_list']['order'] = $order;
    $_SESSION['save_list']['order_field'] = $order_field;
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

//Keep some parameters
$parameters = '';
if (isset($_REQUEST['order']) && !empty($_REQUEST['order'])) {
    $parameters .= '&order='.$_REQUEST['order'];
    $_SESSION['save_list']['order'] = $_REQUEST['order'];

    if (isset($_REQUEST['order_field']) && !empty($_REQUEST['order_field'])) {
        $parameters .= '&order_field='.$_REQUEST['order_field'];
        $_SESSION['save_list']['order_field'] = $_REQUEST['order_field'];
    }
}
if (isset($_REQUEST['what']) && !empty($_REQUEST['what'])) {
    $parameters .= '&what='.$_REQUEST['what'];
}
if (isset($_REQUEST['template']) && !empty($_REQUEST['template'])) {
    $parameters .= '&template='.$_REQUEST['template'];
}
if (isset($_REQUEST['start']) && !empty($_REQUEST['start'])) {
    $parameters .= '&start='.$_REQUEST['start'];
    $_SESSION['save_list']['start'] = $_REQUEST['start'];
}

//URL extra parameters
$urlParameters = '';

//origin
if ($_REQUEST['origin'] == 'searching') {
    $urlParameters .= '&origin=searching';
}

//Basket information
if (!empty($_SESSION['current_basket']['view'])) {
    $table = $_SESSION['current_basket']['view'];
} else {
    $table = $_SESSION['current_basket']['table'];
}
$_SESSION['origin'] = 'basket';
$_SESSION['collection_id_choice'] = $_SESSION['current_basket']['coll_id']; //Collection

//Table
$select[$table] = array();

//Fields
array_push(
    $select[$table], 'res_id', 'status', 'category_id as category_img',
    'contact_firstname', 'priority', 'creation_date', 'modification_date', 'admission_date', 'subject',
    'process_limit_date', 'recommendation_limit_date', 'entity_label', 'dest_user', 'category_id', 'type_label', 'doc_custom_n1 as count_attachment', 'alt_identifier', 'locker_user_id', 'locker_time', 'address_id', 'res_id as signatory_user', 'filename', 'res_id as real_dest'
);

if ($core_tools->is_module_loaded('cases') == true) {
    array_push($select[$table], 'case_id', 'case_label', 'case_description');
}

$arrayPDO = array();
//Where clause
$where_tab = array();
//From basket
if (!empty($_SESSION['current_basket']['clause'])) {
    $where_tab[] = '('.stripslashes($_SESSION['current_basket']['clause']).')';
} //Basket clause
//From filters
    $filterClause = $list->getFilters();
if (!empty($filterClause)) {
    $where_tab[] = $filterClause;
} //Filter clause
//From search
if ((isset($_REQUEST['origin']) && $_REQUEST['origin'] == 'searching')
    && !empty($_SESSION['searching']['where_request'])
) {
    $where_tab[] = $_SESSION['searching']['where_request'].'(1=1)';
    $arrayPDO = array_merge($arrayPDO, $_SESSION['searching']['where_request_parameters']);
}
//Build where
$where = implode(' and ', $where_tab);

//Order
$order = $order_field = '';
$arr_order = explode(', ', $_SESSION['current_basket']['basket_res_order']);
if (!empty($arr_order) && count($arr_order) == 1) {
    $order = $list->getOrder();
    $order_field = $list->getOrderField();
}
if (!empty($order_field) && !empty($order)) {
    if ($_REQUEST['order_field'] == 'alt_identifier') {
        $orderstr = 'order by order_alphanum(alt_identifier)'.' '.$order;
    } else if ($_REQUEST['order_field'] == 'priority') {
        $where .= ' and '.$table.'.priority = priorities.id';
        $select['priorities'] = ['order', 'id'];
        $orderstr = 'order by priorities.order '.$order;
    } else {
        $orderstr = 'order by '.$order_field.' '.$order;
    }
    $_SESSION['last_order_basket'] = $orderstr;
} elseif (!empty($_SESSION['save_list']['order']) && !empty($_SESSION['save_list']['order_field'])) {
    if ($_SESSION['save_list']['order_field'] == 'alt_identifier') {
        $orderstr = 'order by order_alphanum(alt_identifier)'.' '.$_SESSION['save_list']['order'];
    } else if ($_SESSION['save_list']['order_field']) {
        $where .= ' and '.$table.'.priority = priorities.id';
        $select['priorities'] = ['order', 'id'];
        $orderstr = 'order by priorities.order '.$_SESSION['save_list']['order'];
    } else {
        $orderstr = 'order by '.$_SESSION['save_list']['order_field'].' '.$_SESSION['save_list']['order'];
    }
    $_SESSION['last_order_basket'] = $orderstr;
} else {
    if (!empty($_SESSION['current_basket']['basket_res_order'])) {
        if (!empty($arr_order) && count($arr_order) == 1) {
            $orders = explode(' ', $arr_order[0]);
            if (!empty($orders[1])) {
                $list->setOrder($orders[1]);
            } else {
                $orders[] = 'desc';
                $list->setOrder();
            }
            $list->setOrderField($orders[0]);
        }
        $orderstr = 'order by '.str_replace('alt_identifier', 'order_alphanum(alt_identifier)', $_SESSION['current_basket']['basket_res_order']);
        if (strpos($_SESSION['current_basket']['basket_res_order'], 'priority') !== false) {
            $where .= ' and '.$table.'.priority = priorities.id';
            $select['priorities'] = ['order', 'id'];
            $orderstr = 'order by priorities.order '.$orders[1];
        }
        $_SESSION['last_order_basket'] = $_SESSION['current_basket']['basket_res_order'];
    } else {
        $list->setOrder();
        $list->setOrderField('res_id');
        $orderstr = 'order by res_id desc';
        $_SESSION['last_order_basket'] = $orderstr;
    }
}

if (isset($_REQUEST['lines'])) {
    $limit = $_REQUEST['lines'];
} else {
    $limit = 'default';
}

//Request
$tab = $request->PDOselect($select, $where, $arrayPDO, $orderstr, $_SESSION['config']['databasetype'], $limit, false, '', '', '', false, false, false, $_SESSION['save_list']['start']);
// $request->show(); exit;
//Templates
$defaultTemplate = 'documents_list_with_signatory';
$selectedTemplate = $list->getTemplate();
if (empty($selectedTemplate)) {
    if (!empty($defaultTemplate)) {
        $list->setTemplate($defaultTemplate);
        $selectedTemplate = $list->getTemplate();
    }
}
$template_list = array();
array_push($template_list, 'documents_list_with_signatory');
if ($core_tools->is_module_loaded('cases')) {
    array_push($template_list, 'cases_list');
}

//For status icon
$extension_icon = '';
if ($selectedTemplate != 'none') {
    $extension_icon = '_big';
}

$db = new Database();

//Result Array

$tabI = count($tab);
for ($i = 0; $i < $tabI; ++$i) {
    $tabJ = count($tab[$i]);
    for ($j = 0; $j < $tabJ; ++$j) {
        foreach (array_keys($tab[$i][$j]) as $value) {
            if ($tab[$i][$j][$value] == 'res_id') {
                $tab[$i][$j]['res_id'] = $tab[$i][$j]['value'];
                $tab[$i][$j]['label'] = _GED_NUM;
                $tab[$i][$j]['size'] = '4';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = true;
                $tab[$i][$j]['order'] = 'res_id';
                $_SESSION['mlb_search_current_res_id'] = $tab[$i][$j]['value'];

                // notes
                $query = 'SELECT ';
                $query .= 'notes.id ';
                $query .= 'FROM ';
                $query .= 'notes ';
                $query .= 'left join ';
                $query .= 'note_entities ';
                $query .= 'on ';
                $query .= 'notes.id = note_entities.note_id ';
                $query .= 'WHERE ';
                $query .= "tablename = 'res_letterbox' ";
                $query .= 'AND ';
                $query .= "coll_id = 'letterbox_coll' ";
                $query .= 'AND ';
                $query .= 'identifier = ? ';
                $arrayPDOnotes = array($tab[$i][$j]['value']);
                $query .= 'AND ';
                $query .= '( ';
                $query .= '( ';
                $query .= 'item_id IN (';

                if(!empty($_SESSION['user']['entities'])){
                    foreach ($_SESSION['user']['entities'] as $entitiestmpnote) {
                        $query .= '?, ';
                        $arrayPDOnotes = array_merge($arrayPDOnotes, array($entitiestmpnote['ENTITY_ID']));
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
                $arrayPDOnotes = array_merge($arrayPDOnotes, array($_SESSION['user']['UserId']));
                $query .= ') ';
                $stmt = $db->query($query, $arrayPDOnotes);
                $tab[$i][$j]['hasNotes'] = $stmt->fetchObject();
                $tab[$i][$j]['res_multi_contacts'] = $_SESSION['mlb_search_current_res_id'];
            }
            if ($tab[$i][$j][$value] == 'creation_date') {
                $tab[$i][$j]['value'] = $core_tools->format_date_db($tab[$i][$j]['value'], false, '', true);
                $tab[$i][$j]['label'] = _CREATION_DATE;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = true;
                $tab[$i][$j]['order'] = 'creation_date';
            }
            if ($tab[$i][$j][$value] == 'modification_date') {
                $tab[$i][$j]['value'] = $core_tools->format_date_db($tab[$i][$j]['value'], false, '', true);
                $tab[$i][$j]['label'] = _MODIFICATION_DATE;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = true;
                $tab[$i][$j]['order'] = 'modification_date';
            }
            if ($tab[$i][$j][$value] == 'admission_date') {
                $tab[$i][$j]['value'] = $core_tools->format_date_db($tab[$i][$j]['value'], false);
                $tab[$i][$j]['label'] = _ADMISSION_DATE;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = false;
                $tab[$i][$j]['order'] = 'admission_date';
            }
            if ($tab[$i][$j][$value] == 'process_limit_date') {
                $tab[$i][$j]['value'] = $core_tools->format_date_db($tab[$i][$j]['value'], false);
                $compareDate = '';
                if ($tab[$i][$j]['value'] != '' && ($statusCmp == 'NEW' || $statusCmp == 'COU' || $statusCmp == 'VAL' || $statusCmp == 'RET')) {
                    $compareDate = $core_tools->compare_date($tab[$i][$j]['value'], date('d-m-Y'));
                    if ($compareDate == 'date2') {
                        $tab[$i][$j]['value'] = "<span style='color:red;'><b>".$tab[$i][$j]['value'].'<br><small>('.$core_tools->nbDaysBetween2Dates($tab[$i][$j]['value'], date('d-m-Y')).' '._DAYS.')</small></b></span>';
                    } elseif ($compareDate == 'date1') {
                        $tab[$i][$j]['value'] = $tab[$i][$j]['value'].'<br><small>('.$core_tools->nbDaysBetween2Dates(date('d-m-Y'), $tab[$i][$j]['value']).' '._DAYS.')</small>';
                    } elseif ($compareDate == 'equal') {
                        $tab[$i][$j]['value'] = "<span style='color:blue;'><b>".$tab[$i][$j]['value'].'<br><small>('._LAST_DAY.')</small></b></span>';
                    }
                }
                if (empty($tab[$i][$j]['value'])) {
                    $tab[$i][$j]['value'] = '<i style="opacity:0.5;">'._UNDEFINED_DATA.'</i>';
                }
                $tab[$i][$j]['label'] = _PROCESS_LIMIT_DATE;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = true;
                $tab[$i][$j]['order'] = 'process_limit_date';
            }
            if ($tab[$i][$j][$value] == 'recommendation_limit_date') {
                $tab[$i][$j]['value'] = $core_tools->format_date_db($tab[$i][$j]['value'], false);
                $tab[$i][$j]['label'] = _RECOMMENDATION_LIMIT_DATE;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = false;
                $tab[$i][$j]['order'] = 'recommendation_limit_date';
            }
            if ($tab[$i][$j][$value] == 'category_id') {
                $_SESSION['mlb_search_current_category_id'] = $tab[$i][$j]['value'];
                $tab[$i][$j]['value'] = $_SESSION['coll_categories'][$_SESSION['collection_id_choice']][$tab[$i][$j]['value']];
                $tab[$i][$j]['label'] = _CATEGORY;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = true;
                $tab[$i][$j]['order'] = 'category_id';
            }
            if ($tab[$i][$j][$value] == 'priority') {
                $fakeId = null;
                foreach ($_SESSION['mail_priorities_id'] as $key => $prioValue) {
                    if ($prioValue == $tab[$i][$j]['value']) {
                        $fakeId = $key;
                    }
                }
                $tab[$i][$j]['value'] = $_SESSION['mail_priorities'][$fakeId];
                $tab[$i][$j]['label'] = _PRIORITY;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = false;
                $tab[$i][$j]['order'] = 'priority';
            }
            if ($tab[$i][$j][$value] == 'subject') {
                $tab[$i][$j]['value'] = $request->cut_string($request->show_string($tab[$i][$j]['value'], '', '', '', false), 250);
                $tab[$i][$j]['label'] = _SUBJECT;
                $tab[$i][$j]['size'] = '12';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = true;
                $tab[$i][$j]['order'] = 'subject';
            }
            if ($tab[$i][$j][$value] == 'dest_user') {
                $tab[$i][$j]['label'] = 'dest_user';
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = false;
                $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                if (!empty($tab[$i][$j]['value'])) {
                    $user = \User\models\UserModel::getByUserId(['userId' => $tab[$i][$j]['value'], 'select' => ['firstname', 'lastname']]);
                    $dest = $tab[$i][$j]['value'];
                    $dest = $user['firstname'] . ' ' . $user['lastname'];
                } else {
                    $dest = '<i style="opacity:0.5;">'._UNDEFINED_DATA.'</i>';
                }
                $tab[$i][$j]["value"]=$dest;
                if ($tab[$i][15]['value'] == 'outgoing') {
                    $tab[$i][$j]['value'] = '<b>'._WRITTEN_BY.' : </b>'.$tab[$i][$j]['value'];
                } else {
                    $tab[$i][$j]['value'] = '<b>'._PROCESSED_BY.' : </b>'.$tab[$i][$j]['value'];
                }
                $tab[$i][$j]['order'] = false;
            }
            if ($tab[$i][$j][$value] == 'type_label') {
                $tab[$i][$j]['value'] = $request->show_string($tab[$i][$j]['value']);
                $tab[$i][$j]['label'] = _TYPE;
                $tab[$i][$j]['size'] = '12';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = true;
                $tab[$i][$j]['order'] = 'type_label';
            }
            if ($tab[$i][$j][$value] == 'status') {
                //couleurs des priorités
                $fakeId = null;
                foreach ($_SESSION['mail_priorities_id'] as $key => $prioValue) {
                    if ($prioValue == $tab[$i][4]['value']) {
                        $fakeId = $key;
                    }
                }
                $style = "style='color:".$_SESSION['mail_priorities_color'][$fakeId].";font-size:36px'";
                $res_status = $status_obj->get_status_data($tab[$i][$j]['value'], $extension_icon);
                $statusCmp = $tab[$i][$j]['value'];
                $img_class = substr($res_status['IMG_SRC'], 0, 2);
                if (!isset($res_status['IMG_SRC']) || empty($res_status['IMG_SRC'])) {
                    $tab[$i][$j]['value'] = '<i  '.$style." class = 'fm fm-letter-status-new fm-3x' alt = '".$res_status['LABEL']."' title = '".$res_status['LABEL']."'></i>";
                } else {
                    $tab[$i][$j]['value'] = '<i '.$style." class = '".$img_class.' '.$res_status['IMG_SRC'].' '.$img_class."-3x' alt = '".$res_status['LABEL']."' title = '".$res_status['LABEL']."'></i>";
                }
                $tab[$i][$j]['label'] = _STATUS;
                $tab[$i][$j]['size'] = '4';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = true;
                $tab[$i][$j]['order'] = 'status';
            }
            if ($tab[$i][$j][$value] == 'category_img') {
                $tab[$i][$j]['label'] = _CATEGORY;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'right';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = false;
                $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                $my_imgcat = get_img_cat($tab[$i][$j]['value'], $extension_icon);
                $tab[$i][$j]['value'] = $my_imgcat;
                $tab[$i][$j]['value'] = $tab[$i][$j]['value'];
                $tab[$i][$j]['order'] = 'category_id';
            }
            if ($tab[$i][$j][$value] == 'count_attachment') {
                $query = "SELECT count(*) as total FROM res_view_attachments
                            WHERE res_id_master = ?
                            AND status NOT IN ('DEL', 'OBS') AND attachment_type NOT IN ('converted_pdf', 'print_folder') AND coll_id = ? AND (status <> 'TMP' or (typist = ? and status = 'TMP'))";
                $arrayPDO = array($tab[$i][0]['res_id'], $_SESSION['collection_id_choice'], $_SESSION['user']['UserId']);
                $stmt2 = $db->query($query, $arrayPDO);
                $return_count = $stmt2->fetchObject();
                $tab[$i][$j]['label'] = _ATTACHMENTS;
                $tab[$i][$j]['size'] = '12';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = false;
                $tab[$i][$j]['value'] = "$return_count->total";
                $tab[$i][$j]['order'] = 'count_attachment';
            }
            if ($tab[$i][$j][$value] == 'case_id' && $core_tools->is_module_loaded('cases') == true) {
                $tab[$i][$j]['label'] = _CASE_NUM;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = false;
                $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                $tab[$i][$j]['value'] = "<a href='".$_SESSION['config']['businessappurl'].'index.php?page=details_cases&module=cases&id='.$tab[$i][$j]['value']."'>".$tab[$i][$j]['value'].'</a>';
                $tab[$i][$j]['order'] = 'case_id';
            }
            if ($tab[$i][$j][$value] == 'case_label' && $core_tools->is_module_loaded('cases') == true) {
                $tab[$i][$j]['label'] = _CASE_LABEL;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = false;
                $tab[$i][$j]['value_export'] = $tab[$i][$j]['value'];
                $tab[$i][$j]['order'] = 'case_label';
            }
            if ($tab[$i][$j][$value] == 'signatory_user') {
                $query = "SELECT u.firstname || ' ' || u.lastname || ' (' || ue.user_role || ')' as signatory_user, process_date, signatory, requested_signature
                    FROM users u INNER JOIN listinstance l ON u.user_id = l.item_id
                    INNER JOIN users_entities ue ON ue.user_id=u.user_id WHERE ue.primary_entity = 'Y' AND l.difflist_type = 'VISA_CIRCUIT' AND (requested_signature = 'true' OR signatory = 'true') AND l.res_id = ? ORDER BY listinstance_id ASC";
                $arrayPDO = array($tab[$i][0]['res_id']);
                $stmt2 = $db->query($query, $arrayPDO);

                $res = '';
                while ($resTmp = $stmt2->fetchObject()) {
                    // On recupere le 1er signataire
                    if ($resTmp->signatory) {
                        $res = $resTmp;
                        break;
                    // Sinon on recupere le 1er requested signatory
                    } elseif ($resTmp->requested_signature && empty($resTmp->process_date) && empty($res)) {
                        $res = $resTmp;
                    }
                }

                if (!empty($res->signatory_user)) {
                    $res->signatory_user = str_replace('()', '', $res->signatory_user);
                    if (empty($res->process_date) || $res->process_date == null) {
                        $tab[$i][$j]['value'] = '<i class="fa fa-hourglass-half" title="'._SIGN_IN_PROGRESS.'"></i> <span style="color:#135F7F">'.$res->signatory_user.'</span>';
                    } else {
                        $tab[$i][$j]['value'] = '<i class="fa fa-check" style="color:green;" title="'._SIGNED_TO.' '.$core_tools->format_date_db($res->process_date, false, '', true).'"></i> <span style="color:#135F7F">'.$res->signatory_user.'</span>';
                    }
                } else {
                    $tab[$i][$j]['value'] = '<i style="opacity:0.5;">'._NO_SIGNATORY.'</i>';
                }
                $tab[$i][$j]['label'] = _SIGNATORY;
                $tab[$i][$j]['size'] = '10';
                $tab[$i][$j]['label_align'] = 'left';
                $tab[$i][$j]['align'] = 'left';
                $tab[$i][$j]['valign'] = 'bottom';
                $tab[$i][$j]['show'] = false;
                $tab[$i][$j]['order'] = '';
            }

            if ($tab[$i][$j][$value] == 'real_dest') {
                $query = 'SELECT item_id, type FROM resource_contacts WHERE res_id = ?';
                $arrayPDO = array($tab[$i][$j]['value']);
                $stmt2 = $db->query($query, $arrayPDO);
                $return_stmt = $stmt2->fetchObject();

                if ($return_stmt->type == 'contact') {
                    $query = 'SELECT * FROM view_contacts WHERE ca_id = ?';
                    $arrayPDO = array($return_stmt->item_id);
                    $stmt2 = $db->query($query, $arrayPDO);
                    $return_stmt = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if ($return_stmt == false) {
                        $tab[$i][$j]['value'] = '';
                    } else {
                        $formattedContact = \SrcCore\controllers\AutoCompleteController::getFormattedContact(['contact' => $return_stmt]);
                        $tab[$i][$j]['value'] = $formattedContact['contact']['contact'];
                    }
                } else if ($return_stmt->type == 'entity') {
                    $query = 'SELECT short_label FROM entities WHERE id = ?';
                    $arrayPDO = array($return_stmt->item_id);
                    $stmt2 = $db->query($query, $arrayPDO);
                    $return_stmt = $stmt2->fetchObject();
                    $tab[$i][$j]['value'] = $return_stmt->short_label;
                } else {
                    $query = 'SELECT firstname, lastname FROM users WHERE id = ?';
                    $arrayPDO = array($return_stmt->item_id);
                    $stmt2 = $db->query($query, $arrayPDO);
                    $return_stmt = $stmt2->fetchObject();
                    $tab[$i][$j]['value'] = $return_stmt->firstname.' '. $return_stmt->lastname;
                }
                if (empty(trim($tab[$i][$j]['value']))) {
                    $tab[$i][$j]['value'] = null;
                } else if ($_SESSION['mlb_search_current_category_id'] == 'outgoing') {
                    $tab[$i][$j]['value'] = '<b>'._TO_CONTACT_C.'</b>'.$tab[$i][$j]['value'];
                } else {
                    $tab[$i][$j]['value'] = '<b>'._FOR_CONTACT_C.'</b>'.$tab[$i][$j]['value'];
                }
                $tab[$i][$j]['order'] = false;
            }
        }
    }
}
//Cle de la liste
$listKey = 'res_id';

//Initialiser le tableau de param�tres
$paramsTab = array();
$paramsTab['pageTitle'] = _RESULTS.' : '.$_SESSION['save_list']['full_count'].' '._FOUND_DOCS;              //Titre de la page
$paramsTab['listCss'] = 'listing largerList spec';                                  //css
$paramsTab['bool_sortColumn'] = true;                                               //Affichage Tri
$paramsTab['bool_bigPageTitle'] = false;                                            //Affichage du titre en grand
$paramsTab['bool_showIconDocument'] = true;                                         //Affichage de l'icone du document
$paramsTab['bool_showIconDetails'] = true;                                          //Affichage de l'icone de la page de details
$paramsTab['urlParameters'] = 'baskets='.$_SESSION['current_basket']['id']
            .$urlParameters;                                                        //Parametres d'url supplementaires
$paramsTab['filters'] = array('entity', 'entity_subentities', 'category', 'priority', 'identifier');          //Filtres
if (count($template_list) > 0) {                                                   //Templates
    $paramsTab['templates'] = array();
    $paramsTab['templates'] = $template_list;
}
$paramsTab['bool_showTemplateDefaultList'] = true;                                  //Default list (no template)
$paramsTab['start'] = $_SESSION['save_list']['start'];
$paramsTab['defaultTemplate'] = $defaultTemplate;                                   //Default template
$paramsTab['tools'] = array();                                                      //Icones dans la barre d'outils
//Fileplan
if ($core_tools->test_service('fileplan', 'fileplan', false)) {
    include_once 'modules'.DIRECTORY_SEPARATOR.'fileplan'.DIRECTORY_SEPARATOR
        .'class'.DIRECTORY_SEPARATOR.'class_modules_tools.php';
    $fileplan = new fileplan();
    if (count($fileplan->getUserFileplan()) > 0 || (count($fileplan->getEntitiesFileplan()) > 0 && $core_tools->test_service('put_doc_in_fileplan', 'fileplan', false))) {
        $paramsTab['bool_checkBox'] = true;
        $paramsTab['bool_standaloneForm'] = true;
        $positions = array(
                'script' => "showFileplanList('".$_SESSION['config']['businessappurl']
                                        .'index.php?display=true&module=fileplan&page=fileplan_ajax_script'
                                        .'&mode=setPosition&origin=basket&coll_id='.$_SESSION['current_basket']['coll_id']
                                        .$parameters."', 'formList', '600px', '510px', '"
                                        ._CHOOSE_ONE_DOC."')",
                'icon' => 'bookmark',
                'tooltip' => _FILEPLAN,
                'disabledRules' => count($tab).' == 0 || '.$selectedTemplate." == 'cases_list_search_adv'",
                );
        array_push($paramsTab['tools'], $positions);
    }
}
if (isset($_REQUEST['origin']) && $_REQUEST['origin'] == 'searching') {
    $save = array(
            'script' => "createModal(form_txt, 'save_search', '100px', '500px');window.location.href='#top';",
            'icon' => 'save',
            'tooltip' => _SAVE_QUERY,
            'disabledRules' => count($tab).' == 0',
            );
    array_push($paramsTab['tools'], $save);
}
$export = array(
    'script' => "window.open('".$_SESSION['config']['businessappurl']."index.php?display=true&page=export', '_blank');",
    'icon' => 'file-excel',
    'tooltip' => _EXPORT_LIST,
    'disabledRules' => count($tab).' == 0',
    );
array_push($paramsTab['tools'], $export);
$export2 = array(
'script' => "print_current_result_list('".$_SESSION['config']['businessappurl']."');",
'icon' => 'print',
'tooltip' => _PRINT_LIST,
'disabledRules' => count($tab).' == 0',
);
array_push($paramsTab['tools'], $export2);
if ($core_tools->test_service('print_doc_details_from_list', 'apps', false)) {
    $print = array(
            'script' => "window.open('".$_SESSION['config']['businessappurl']."index.php?display=true&page=print', '_blank');",
            'icon' => 'link',
            'tooltip' => _PRINT_DOC_FROM_LIST,
            'disabledRules' => count($tab).' == 0',
        );
    array_push($paramsTab['tools'], $print);
}

//Afficher la liste
$status = 0;
$content = $list->showList($tab, $paramsTab, $listKey, $_SESSION['current_basket']);
// $debug = $list->debug(false);

$content .= '<script>$j(\'#container\').attr(\'style\', \'width: 90%; min-width: 1000px;\');$j(\'#content\').attr(\'style\', \'width: auto; min-width: 1000px;\');';
$content .= '$j(\'#inner_content\').attr(\'style\', \'width: auto; min-width: 1000px;\');</script>';

echo "{'status' : ".$status.", 'content' : '".addslashes($debug.$content)."', 'error' : '".addslashes(functions::xssafe($error))."'}";