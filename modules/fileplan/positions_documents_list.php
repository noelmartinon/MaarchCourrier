<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   positions_documents_list
* @author  dev <dev@maarch.org>
* @ingroup fileplan
*/

require_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_request.php';
require_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_security.php';
require_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_manage_status.php';
require_once 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR
    . 'class' . DIRECTORY_SEPARATOR . 'class_lists.php';
require_once 'modules' . DIRECTORY_SEPARATOR . 'fileplan' . DIRECTORY_SEPARATOR
    . 'class' . DIRECTORY_SEPARATOR
    . 'class_modules_tools.php';

$security = new security();
$core_tools = new core_tools();
$status_obj = new manage_status();
$list = new lists();
$fileplan = new fileplan();
$request = new request();

$status = 0;
$change_fileplan = false;

if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
    $branch_array = array();
    $branch_array = explode('@@', $_REQUEST['id']);

    //Get  ID
    $fileplan_id = $branch_array[0];
    $position_id = $branch_array[1];

    //Process
    if (!empty($fileplan_id) && !empty($position_id)) {
        //Change fileplan

        $change_fileplan = $fileplan->userCanChangeFileplan($fileplan_id);

        //URL extra Parameters
        $parameters = '&id=' . $_REQUEST['id'];
        $start = $list->getStart();
        if (!empty($order_field) && !empty($order)) {
            $parameters .= '&order=' . $order . '&order_field=' . $order_field;
        }
        

        //Order
        $order = $order_field = '';
        $order = $list->getOrder();
        $order_field = $list->getOrderField();
        if (!empty($order_field) && !empty($order)) {
            $orderstr = 'order by ' . $order_field . ' ' . $order;
        } else {
            $list->setOrder();
            $list->setOrderField('alt_identifier');
            $orderstr = 'order by alt_identifier desc';
        }

        if (isset($_REQUEST['start']) && !empty($_REQUEST['start'])) {
            $parameters .= '&start=' . $_REQUEST['start'];
            $start = $_REQUEST['start'];
        } else {
            $start = $list->getStart();
            $parameters .= '&start=' . $start;
        }

        //select
        $select['fp_res_fileplan_positions'] = array();
        $select['res_view_letterbox'] = array();

        array_push(
            $select['fp_res_fileplan_positions'],
            'coll_id',
            'coll_id as coll_label',
            'res_id',
            'fileplan_id',
            'position_id'
        );

        array_push(
            $select['res_view_letterbox'],
            'res_id as right_doc',
            'res_id as page_details',
            'res_id as list_id',
            'alt_identifier',
            'status',
            'type_label',
            'category_id',
            'subject',
            'creation_date'
        );

        $whereTab = [];

        //SECURITY CLAUSE
        $whereTab[] = "fp_res_fileplan_positions.fileplan_id = ? AND fp_res_fileplan_positions.position_id = ?";

        //Build Where
        $where = implode(' AND ', $whereTab);

        $arrayPDO = [$fileplan_id, $position_id];

        $tab = $request->PDOselect(
            $select,
            $where,
            $arrayPDO,
            $orderstr,
            $_SESSION['config']['databasetype'],
            'default',
            true,
            'fp_res_fileplan_positions',
            'res_view_letterbox',
            'res_id',
            true,
            false,
            false,
            $start
        );

        $description = functions::xssafe($fileplan->getPositionPath($fileplan_id, $position_id, true));

        //Result Array
        if (!empty($tab)) {
            for ($i = 0; $i < count($tab); ++$i) {
                for ($j = 0; $j < count($tab[$i]); ++$j) {
                    foreach (array_keys($tab[$i][$j]) as $value) {
                        if ($tab[$i][$j][$value] == 'list_id') {
                            $tab[$i][$j]['list_id'] = $coll_id . '@@' . $res_id;
                            $tab[$i][$j]['label'] = _ID;
                            $tab[$i][$j]['size'] = '1';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = false;
                            $tab[$i][$j]['order'] = false;
                        }
                        if ($tab[$i][$j][$value] == 'res_id') {
                            $display = false;
                            $res_id = $tab[$i][$j]['value'];
                            $tab[$i][$j]['label'] = _GED_NUM;
                            $tab[$i][$j]['size'] = '1';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            if (_ID_TO_DISPLAY == 'res_id') {
                                $display = true;
                            }
                            $tab[$i][$j]['show'] = $display;
                            $tab[$i][$j]['order'] = 'res_id';
                        }
                        if ($tab[$i][$j][$value] == 'alt_identifier') {
                            $display = false;
                            $tab[$i][$j]['label'] = _CHRONO_NUMBER;
                            $tab[$i][$j]['size'] = '1';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            if (_ID_TO_DISPLAY == 'chrono_number') {
                                $display = true;
                            }
                            $tab[$i][$j]['show'] = $display;
                            $tab[$i][$j]['order'] = 'alt_identifier';
                        }
                        if ($tab[$i][$j][$value] == 'coll_id') {
                            $coll_id = $tab[$i][$j]['value']; //Keep collection ID
                            $tab[$i][$j]['label'] = _COLLECTION;
                            $tab[$i][$j]['size'] = '1';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = false;
                            $tab[$i][$j]['order'] = 'coll_id';
                        }
                        if ($tab[$i][$j][$value] == 'coll_label') {
                            $tab[$i][$j]['label'] = _COLLECTION;
                            $tab[$i][$j]['size'] = '1';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = false;
                            $tab[$i][$j]['order'] = 'coll_label';
                        }
                        if ($tab[$i][$j][$value] == 'page_details') {
                            $coll_script_details = $security->get_script_from_coll($coll_id, 'script_details');
                            $coll_script_details = substr($coll_script_details, 0, strlen($coll_script_details) - 4);
                            $tab[$i][$j]['value'] = $coll_script_details;
                            $tab[$i][$j]['label'] = _DETAILS;
                            $tab[$i][$j]['size'] = '1';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = false;
                            $tab[$i][$j]['order'] = false;
                        }
                        if ($tab[$i][$j][$value] == 'right_doc') {
                            $tab[$i][$j]['value'] = ($security->test_right_doc($coll_id, $tab[$i][$j]['value']) === true) ? 'true' : 'false';
                            $tab[$i][$j]['label'] = _RIGHT;
                            $tab[$i][$j]['size'] = '1';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = false;
                            $tab[$i][$j]['order'] = false;
                        }

                        if ($tab[$i][$j][$value] == 'status') {
                            $res_status = $status_obj->get_status_data($tab[$i][$j]['value'], $extension_icon);
                            $statusCmp = $tab[$i][$j]['value'];
                            $img_class = substr($res_status['IMG_SRC'], 0, 2);
                            $tab[$i][$j]['value'] = '<i ' . $style . " class = '" . $img_class . ' ' . $res_status['IMG_SRC'] . ' ' . $img_class . "-3x' alt = '" . $res_status['LABEL'] . "' title = '" . $res_status['LABEL'] . "'></i>";
                            $tab[$i][$j]['label'] = _STATUS;
                            $tab[$i][$j]['size'] = '4';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = true;
                            $tab[$i][$j]['order'] = true;
                        }
                        if ($tab[$i][$j][$value] == 'subject') {
                            $tab[$i][$j]['value'] = functions::cut_string(functions::show_string($tab[$i][$j]['value']), 250);
                            $tab[$i][$j]['label'] = _SUBJECT;
                            $tab[$i][$j]['size'] = '12';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = true;
                            $tab[$i][$j]['order'] = true;
                        }
                        if ($tab[$i][$j][$value] == 'category_id') {
                            $_SESSION['mlb_search_current_category_id'] = $tab[$i][$j]['value'];
                            $tab[$i][$j]['value'] = $_SESSION['mail_categories'][$tab[$i][$j]['value']];
                            $tab[$i][$j]['label'] = _CATEGORY;
                            $tab[$i][$j]['size'] = '10';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = false;
                            $tab[$i][$j]['order'] = false;
                        }
                        if ($tab[$i][$j][$value] == 'type_label') {
                            $tab[$i][$j]['value'] = functions::show_string($tab[$i][$j]['value']);
                            $tab[$i][$j]['label'] = _TYPE;
                            $tab[$i][$j]['size'] = '10';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = true;
                            $tab[$i][$j]['order'] = true;
                        }
                        if ($tab[$i][$j][$value] == 'creation_date') {
                            $tab[$i][$j]['value'] = $core_tools->format_date_db($tab[$i][$j]['value'], false);
                            $tab[$i][$j]['label'] = _CREATION_DATE;
                            $tab[$i][$j]['size'] = '10';
                            $tab[$i][$j]['label_align'] = 'left';
                            $tab[$i][$j]['align'] = 'left';
                            $tab[$i][$j]['valign'] = 'bottom';
                            $tab[$i][$j]['show'] = false;
                            $tab[$i][$j]['order'] = false;
                        }
                    }
                }
            }
        }

            //List
        $listKey = 'list_id';                                                               //Cle de la liste
        $paramsTab = array();                                                               //Initialiser le tableau de param�tres
        $paramsTab['bool_sortColumn'] = true;                                               //Affichage Tri
        $paramsTab['pageTitle'] = '<h2 style="margin-left:0px;">' . $description . ':</h2><br/> '
            . $_SESSION['save_list']['full_count'] . ' ' . _FOUND_DOC . '<br/>';     		//Titre de la page
        $paramsTab['bool_bigPageTitle'] = false;                                            //Affichage du titre en grand
        $paramsTab['urlParameters'] = 'id=' . $_REQUEST['id'] . '&display=true';                //Parametres d'url supplementaires
        $paramsTab['start'] = $start;
        $paramsTab['bool_changeLinesToShow'] = false;                                       //Modifier le nombre de ligne a afficher
        $paramsTab['listCss'] = 'listingsmall';                                             //CSS
        $paramsTab['divListId'] = 'list_doc';                                               //Id du Div de retour ajax
        $paramsTab['bool_checkBox'] = false;                                                 //Case a cocher
        $paramsTab['bool_standaloneForm'] = true;                                           //Formulaire  

        $paramsTab['disabledRules'] = "@@right_doc@@ == 'false' || "
            . (int)$change_fileplan . ' == 0';                           						//Veroullage de ligne(heckbox ou radio button)

        $paramsTab['tools'] = array();                                                      //Icones dans la barre d'outils
        /*$positions = array(
            'script' => "showFileplanList('" . $_SESSION['config']['businessappurl']
                . 'index.php?display=true&module=fileplan&page=fileplan_ajax_script'
                . '&mode=setPosition&origin=fileplan&fileplan_id=' . $fileplan_id
                . '&actual_position_id=' . $position_id . $parameters
                . "', 'formList', '600px', '510px', '"
                . _CHOOSE_ONE_DOC . "')",
            'icon' => 'bookmark',
            'tooltip' => _FILEPLAN,
            'disabledRules' => count($tab) . ' == 0 || ' . (int)$change_fileplan . ' == 0',
        );
        array_push($paramsTab['tools'], $positions);*/

            //Action icons array
        $paramsTab['actionIcons'] = array();
        $remove = array(
            'script' => "execFileplanScript('" . $_SESSION['config']['businessappurl']
                . 'index.php?display=true&module=fileplan&page=fileplan_ajax_script'
                . '&mode=remove&origin=fileplan&fileplan_id=' . $fileplan_id
                . '&actual_position_id=' . $position_id . '&res_id=@@res_id@@'
                . '&coll_id=@@coll_id@@' . $parameters . "')",
            'icon' => 'trash-alt',
            'tooltip' => _REMOVED_DOC_FROM_POSITION,
            'alertText' => _REALLY_REMOVE_DOC_FROM_POSITION . ': ' . $description . '?',
                        //"disabledRules" => (int)$change_fileplan." == 0"
                        //"disabledRules" => "@@right_doc@@ == 'false'"
        );
        array_push($paramsTab['actionIcons'], $remove);
        $viewDoc = array(
            'script' => "window.top.location='" . $_SESSION['config']['businessappurl']
                . "index.php?page=@@page_details@@&dir=indexing_searching&coll_id=@@coll_id@@&id=@@res_id@@'",
            'icon' => 'info',
            'tooltip' => _DETAILS,
            'disabledRules' => "@@right_doc@@ == 'false'",
        );
        array_push($paramsTab['actionIcons'], $viewDoc);

            //Output
        $content = $list->showList($tab, $paramsTab, $listKey);
    } else {
        $content = '&nbsp;<em>' . $description . ': ' . _NO_DOC_IN_POSITION . '</em>';
    }
}

echo '{status : ' . $status . ", content : '" . addslashes($debug . $content) . "', error : '" . addslashes($error) . "'}";
