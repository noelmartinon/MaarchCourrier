<?php

/*
*   Copyright 2008 - 2015 Maarch
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
* @brief  Advanced search form management
*
* @file search_adv_result.php
* @author Claire Figueras <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup indexing_searching_mlb
*/

require_once('core/class/class_request.php');
require_once('core/class/class_security.php');
require_once('apps/' . $_SESSION['config']['app_id'] . '/class/class_indexing_searching_app.php');
$core_tools = new core_tools();
$core_tools->test_user();
$core_tools->load_lang();
$is = new indexing_searching_app();
$func = new functions();
$req = new request();
$fields = "";
$orderby = "";

$baskets_clause = '';
$coll_id = 'letterbox_coll';
$_SESSION['error_search'] = '';
$_SESSION['searching']['comp_query'] = '';
$_SESSION['save_list']['fromDetail'] = "false";
$_SESSION['fullTextAttachments'] = [];


// define the row of the start
if (isset($_REQUEST['start'])) {
    $start = $_REQUEST['start'];
} else {
    $start = 0;
}

if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'frame') {
    $mode = 'frame';
} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'popup') {
    $mode = 'popup';
} else {
    $mode = 'normal';
    $core_tools->test_service('adv_search_mlb', 'apps');
}
$where_request = "";
$arrayPDO = array();
 $_ENV['date_pattern'] = "/^[0-3][0-9]-[0-1][0-9]-[1-2][0-9][0-9][0-9]$/";
$json_txt = '{';

/**
 * Array $_REQUEST['meta'] exemple
(
    [0] => copies#copies_false,copies_true#radio
    [1] => objet#objet#input_text
    [2] => numged#numged#input_text
    [3] => multifield#multifield#input_text
    [4] => category#category#select_simple
    [5] => doc_date#doc_date_from,doc_date_to#date_range
)
**/
//$func->show_array($_REQUEST['meta']);exit;
$nbMeta = count($_REQUEST['meta']);
if ($nbMeta > 0) {
    //Verif for parms sended by url
    if ($_GET['meta']) {
        for ($m=0; $m < $nbMeta; $m++) {
            if (strstr($_REQUEST['meta'][$m], '||') == true) {
                $_REQUEST['meta'][$m] = str_replace('||', '#', $_REQUEST['meta'][$m]);
            }
        }
    }
    $opt_indexes = array();
    $_SESSION['meta_search'] = $_REQUEST['meta'];
    for ($i=0; $i < $nbMeta; $i++) {
        $tab = explode('#', $_REQUEST['meta'][$i]);
        if ($tab[0] == 'welcome') {
            $tab[0] = 'multifield';
            $tab[2] = 'input_text';
        }
        $id_val = $tab[0];
        $json_txt .= "'".$tab[0]."' : { 'type' : '".$tab[2]."', 'fields' : {";
        $tab_id_fields = explode(',', $tab[1]);
        //$func->show_array($tab_id_fields);
        $nbFields = count($tab_id_fields);
        for ($j=0; $j < $nbFields; $j++) {
            // ENTITIES
            if ($tab_id_fields[$j] == 'services_chosen' && isset($_REQUEST['services_chosen'])) {
                $json_txt .= " 'services_chosen' : [";

                for ($get_i = 0; $get_i <count($_REQUEST['services_chosen']); $get_i++) {
                    $json_txt .= "'".$_REQUEST['services_chosen'][$get_i]."',";
                }
                $json_txt = substr($json_txt, 0, -1);
                $where_request .= " destination IN  (:serviceChosen) ";
                $where_request .=" and  ";
                $arrayPDO = array_merge($arrayPDO, array(":serviceChosen" => $_REQUEST['services_chosen']));
                $json_txt .= '],';
            } elseif ($tab_id_fields[$j] == 'initiatorServices_chosen' && isset($_REQUEST['initiatorServices_chosen'])) {
                $json_txt .= " 'initiatorServices_chosen' : [";

                for ($get_i = 0; $get_i <count($_REQUEST['initiatorServices_chosen']); $get_i++) {
                    $json_txt .= "'".$_REQUEST['initiatorServices_chosen'][$get_i]."',";
                }
                $json_txt = substr($json_txt, 0, -1);
                $where_request .= " initiator IN  (:initiatorServiceChosen) ";
                $where_request .=" and  ";
                $arrayPDO = array_merge($arrayPDO, array(":initiatorServiceChosen" => $_REQUEST['initiatorServices_chosen']));
                $json_txt .= '],';
            } elseif ($tab_id_fields[$j] == 'multifield' && !empty($_REQUEST['multifield'])) {
                // MULTIFIELD : subject, process notes
                $multifield = trim($_REQUEST['multifield']);
                $json_txt .= "'multifield' : ['".addslashes(trim($multifield))."'],";

                $where_request .= "(REGEXP_REPLACE(lower(translate(subject,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')),'( ){2,}', ' ') like lower(:multifield) "
                    ."or (lower(translate(alt_identifier,'/','')) like lower(:multifield) OR lower(alt_identifier) like lower(:multifield)) "
                    ."or lower(barcode) LIKE lower(:multifield) "
                    ."or res_id in (select identifier from notes where lower(translate(note_text,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) like lower(:multifield)) "
                    ."or res_id in (select res_id_master from res_attachments where (lower(translate(identifier,'/','')) like lower(:multifield) OR lower(identifier) like lower(:multifield) AND status NOT IN ('DEL','OBS','TMP')))) ";
                if (ctype_digit($_REQUEST['multifield'])) {
                    $where_request .= "or res_id = :multifield2 ";
                    $arrayPDO = array_merge($arrayPDO, array(":multifield2" => $multifield));
                }

                $multifield = \SrcCore\models\TextFormatModel::normalize(['string' => $multifield]);
                $multifield = preg_replace('/\s+/', ' ', $multifield);
                $arrayPDO = array_merge($arrayPDO, array(":multifield" => "%".$multifield."%"));
                
                $where_request .=" and  ";
            } elseif ($tab_id_fields[$j] == 'chrono' && !empty($_REQUEST['chrono'])) {
                $json_txt .= " 'chrono' : ['".addslashes(trim($_REQUEST['chrono']))."'],";
                $chrono = $func->wash($_REQUEST['chrono'], "no", _CHRONO_NUMBER, "no");
                $where_request .= " (lower(alt_identifier) like lower(:chrono) or (res_id in (SELECT res_id_master FROM res_attachments WHERE lower(identifier) like lower(:chrono)  AND status NOT IN ('DEL','OBS','TMP'))))";
                $arrayPDO = array_merge($arrayPDO, array(":chrono" => "%".$chrono."%"));
                $where_request .=" and  ";
            }

            // CODE A BARRES
            elseif ($tab_id_fields[$j] == 'barcode' && !empty($_REQUEST['barcode'])) {
                $json_txt .= " 'barcode' : ['".addslashes(trim($_REQUEST['barcode']))."'],";
                $barcode = $func->wash($_REQUEST['barcode'], "no", _BARCODE, "no");
                $where_request .= " lower(barcode) like lower(:barcode) ";
                $arrayPDO = array_merge($arrayPDO, array(":barcode" => "%".$barcode."%"));
                $where_request .=" and  ";
            }
            // PRIORITY
            elseif ($tab_id_fields[$j] == 'priority_chosen' && isset($_REQUEST['priority_chosen'])) {
                $json_txt .= " 'priority_chosen' : [";

                for ($get_i = 0; $get_i <count($_REQUEST['priority_chosen']); $get_i++) {
                    $json_txt .= "'".$_REQUEST['priority_chosen'][$get_i]."',";
                }
                $json_txt = substr($json_txt, 0, -1);
                $where_request .= " priority IN  (:priorityChosen) ";
                $where_request .=" and  ";
                $arrayPDO = array_merge($arrayPDO, array(":priorityChosen" => $_REQUEST['priority_chosen']));
                $json_txt .= '],';
            }
            // SIGNATORY GROUP
            elseif ($tab_id_fields[$j] == 'signatory_group' && !empty($_REQUEST['signatory_group'])) {
                $json_txt .= " 'signatory_group' : ['".addslashes(trim($_REQUEST['signatory_group']))."'],";

                $where_request .= " (res_id in (select res_id from listinstance where item_id in (select user_id from users where id in (select user_id from usergroup_content where group_id = :signatoryGroup)) "
                        ."and item_mode = 'sign' and difflist_type = 'VISA_CIRCUIT')) ";
                $group = \Group\models\GroupModel::getByGroupId(['groupId' => $_REQUEST['signatory_group'], 'select' => ['id']]);
                $arrayPDO = array_merge($arrayPDO, array(":signatoryGroup" => $group['id']));
                $where_request .=" and  ";
            }

            // TYPE D'ATTACHEMENT
            elseif ($tab_id_fields[$j] == 'attachment_types' && !empty($_REQUEST['attachment_types'])) {
                $json_txt .= " 'attachment_types' : ['".addslashes(trim($_REQUEST['attachment_types']))."'],";
                $where_request .= " (res_id in (SELECT res_id_master FROM res_attachments WHERE attachment_type = :attachmentTypes AND status NOT IN ('DEL','OBS','TMP')) )";
                $arrayPDO = array_merge($arrayPDO, array(":attachmentTypes" => $_REQUEST['attachment_types']));
                $where_request .=" and  ";
            }
            // DEPARTMENT NUMBER
            elseif ($tab_id_fields[$j] == 'department_number_chosen' && !empty($_REQUEST['department_number_chosen'])) {
                $json_txt .= " 'department_number_chosen' : [";

                for ($get_i = 0; $get_i <count($_REQUEST['department_number_chosen']); $get_i++) {
                    $json_txt .= "'".$_REQUEST['department_number_chosen'][$get_i]."',";
                }

                $json_txt = substr($json_txt, 0, -1);
                $json_txt .= '],';

                $sDepartment = join('|', $_REQUEST['department_number_chosen']);
                $sDepartment = str_replace(['2A', '2B'], '20', $sDepartment);

                $where_request .= " ((res_id in (SELECT res_id FROM resource_contacts rc LEFT JOIN contacts ON rc.item_id = contacts.id 
                WHERE type = 'contact' AND mode = 'sender' AND (address_country ILIKE 'FRANCE' OR address_country = '' OR address_country IS NULL)
                AND address_postcode similar to :department_number))) and ";
                $arrayPDO = array_merge($arrayPDO, array(":department_number" => '('.$sDepartment . ')%'));
            }
            // NOTES
            elseif ($tab_id_fields[$j] == 'doc_notes' && !empty($_REQUEST['doc_notes'])) {
                $json_txt .= " 'doc_notes' : ['".addslashes(trim($_REQUEST['doc_notes']))."'],";
                $s_doc_notes = $func->wash($_REQUEST['doc_notes'], "no", _NOTES, "no");
                $where_request .= " res_id in(select identifier from ".$_SESSION['tablename']['not_notes']." where lower(note_text) LIKE lower(:noteText)) and ";
                $arrayPDO = array_merge($arrayPDO, array(":noteText" => "%".$s_doc_notes."%"));
            }
            // Folder
            elseif ($tab_id_fields[$j] == 'folder' && !empty($_REQUEST['folder'])) {
                $json_txt .= " 'folder' : ['".addslashes(trim($_REQUEST['folder']))."'],";
                $folder = $func->wash($_REQUEST['folder'], "no", _MARKET, "no");

                $where_request .= " res_id in ( ";

                $where_request .= "select res_id
from resources_folders
         left join folders on resources_folders.folder_id = folders.id
where lower(translate(folders.label , 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ',
                                       'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) ilike
                       lower(translate(:label_folders, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ',
                                      'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))
    and (
        folders.id in (
            select folders.id
            from folders
            left join users on folders.user_id = users.id
            where users.user_id = :user_id_folders
        ) or folders.id in (
            select entities_folders.folder_id
            from entities_folders
                left join entities on entities_folders.entity_id = entities.id
                left join users_entities on entities.entity_id = users_entities.entity_id
            where users_entities.user_id = :user_id_folders OR entities_folders.keyword = 'ALL_ENTITIES'
        )
    )";

                $where_request .=" ) and ";

                $arrayPDO = array_merge($arrayPDO, array(":label_folders" => "%".$folder."%", ":user_id_folders" => $_SESSION['user']['UserId']));
            }
            // GED NUM
            elseif ($tab_id_fields[$j] == 'numged' && !empty($_REQUEST['numged'])) {
                $json_txt .= " 'numged' : ['".addslashes(trim($_REQUEST['numged']))."'],";
                require_once('core/class/class_security.php');
                $sec = new security();
                $view = $sec->retrieve_view_from_coll_id($_SESSION['collection_id_choice']);
                if ($view <> '') {
                    $view .= '.';
                }
                $where_request .= $view . "res_id = :numGed and ";
                $arrayPDO = array_merge($arrayPDO, array(":numGed" => $_REQUEST['numged']));

                if (!is_numeric($_REQUEST['numged'])) {
                    $_SESSION['error_search'] = _NUMERO_GED;
                }
            }
            // DEST_USER
            elseif ($tab_id_fields[$j] == 'destinataire_chosen' && !empty($_REQUEST['destinataire_chosen'])) {
                $json_txt .= " 'destinataire_chosen' : [";

                for ($get_i = 0; $get_i <count($_REQUEST['destinataire_chosen']); $get_i++) {
                    $json_txt .= "'".$_REQUEST['destinataire_chosen'][$get_i]."',";
                }

                $json_txt = substr($json_txt, 0, -1);

                $where_request .= " (dest_user IN  (:destinataireChosen) or res_id in (select res_id from ".$_SESSION['tablename']['ent_listinstance']." where item_id in (:destinataireChosen) and item_mode = 'dest')) ";
                $where_request .=" and  ";
                $arrayPDO = array_merge($arrayPDO, array(":destinataireChosen" => $_REQUEST['destinataire_chosen']));
                $json_txt .= '],';
            }
            // SUBJECT
            elseif ($tab_id_fields[$j] == 'subject' && !empty($_REQUEST['subject'])) {
                $subject = trim($_REQUEST['subject']);
                $subject = preg_replace('/\s+/', ' ', $func->normalize($subject));
                $json_txt .= " 'subject' : ['".addslashes(trim($subject))."'],";

                $where_request .= " (REGEXP_REPLACE(lower(translate(subject,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')),'( ){2,}', ' ') like lower(:subject) "
                    ."or (res_id in (SELECT res_id_master FROM res_attachments WHERE lower(translate(title,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))  like lower(:subject) AND status NOT IN ('DEL','OBS','TMP') ))) and ";
                $arrayPDO = array_merge($arrayPDO, array(":subject" => "%".$subject."%"));
            } elseif ($tab_id_fields[$j] == 'fulltext' && !empty($_REQUEST['fulltext'])
            ) {
                $query_fulltext = explode(" ", trim($_REQUEST['fulltext']));
                $error_fulltext = false;

                foreach ($query_fulltext as $value) {
                    if (strpos($value, "*") !== false &&
                        (strlen(substr($value, 0, strpos($value, "*"))) < 3 || preg_match("([,':!+])", $value) === 1)
                        ) {
                        $error_fulltext = true;
                        break;
                    }
                }

                if ($error_fulltext == true) {
                    $_SESSION['error_search'] = _FULLTEXT_ERROR;
                } else {
                    // FULLTEXT
                    $fulltext_request = $func->normalize($_REQUEST['fulltext']);
                    $json_txt .= " 'fulltext' : ['"
                        . addslashes(trim($_REQUEST['fulltext'])) . "'],";
                    set_include_path(
                        'apps' . DIRECTORY_SEPARATOR
                        . $_SESSION['config']['app_id']
                        . DIRECTORY_SEPARATOR . 'tools'
                        . DIRECTORY_SEPARATOR . PATH_SEPARATOR . get_include_path()
                    );
                    require_once('Zend/Search/Lucene.php');
                    Zend_Search_Lucene_Analysis_Analyzer::setDefault(
                        new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive() // we need utf8 for accents
                    );
                    Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(Zend_Search_Lucene_Search_QueryParser::B_AND);
                    Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
                    
                    $_SESSION['search']['plain_text'] = $_REQUEST['fulltext'];

                    foreach (['letterbox_coll', 'attachments_coll'] as $key => $tmpCollection) {
                        $fullTextDocserver = \Docserver\models\DocserverModel::getCurrentDocserver(['collId' => $tmpCollection, 'typeId' => 'FULLTEXT']);

                        $path_to_lucene_index = $fullTextDocserver['path_template'];

                        if (is_dir($path_to_lucene_index)) {
                            if (!$func->isDirEmpty($path_to_lucene_index)) {
                                $index = Zend_Search_Lucene::open($path_to_lucene_index);
                                $hits = $index->find(urldecode($fulltext_request));
                                $Liste_Ids = "0";
                                $cptIds = 0;
                                foreach ($hits as $hit) {
                                    if ($cptIds < 500) {
                                        $Liste_Ids .= ", '". $hit->Id ."'";
                                    } else {
                                        break;
                                    }
                                    $cptIds ++;
                                }

                                if ($tmpCollection == 'attachments_coll') {
                                    $tmpArray = preg_split("/[,' ]/", $Liste_Ids);
                                    array_splice($tmpArray, 0, 1);
                                    $_SESSION['fullTextAttachments']['attachments'] = array_filter($tmpArray);
                                    $db = new Database();
                                    $stmt = $db->query("SELECT DISTINCT res_id_master FROM res_attachments WHERE res_id IN ($Liste_Ids) AND status NOT IN ('DEL','OBS','TMP')");
                                    $idMasterDatas = [];
                                    while ($tmp = $stmt->fetchObject()) {
                                        $idMasterDatas[] = $tmp;
                                    }

                                    $Liste_Ids = '0';
                                    foreach ($idMasterDatas as $tmpIdMaster) {
                                        $Liste_Ids .= ", '{$tmpIdMaster->res_id_master}'";
                                        $_SESSION['fullTextAttachments']['letterbox'][] = $tmpIdMaster->res_id_master;
                                    }
                                }

                                if ($key == 0) {
                                    $where_request .= ' (';
                                }

                                $where_request .= " res_id IN ($Liste_Ids) ";

                                if ($key == 1) {
                                    $where_request .= ') and ';
                                } else {
                                    $where_request .= ' or ';
                                }
                            } else {
                                if ($key == 0) {
                                    $where_request .= ' (';
                                }

                                $where_request .= " 1=-1 ";

                                if ($key == 1) {
                                    $where_request .= ') and ';
                                } else {
                                    $where_request .= ' or ';
                                }
                            }
                        } else {
                            if ($key == 0) {
                                $where_request .= ' (';
                            }

                            $where_request .= " 1=-1 ";

                            if ($key == 1) {
                                $where_request .= ') and ';
                            } else {
                                $where_request .= ' or ';
                            }
                        }
                    }
                }
            }
            // TAGS
            elseif ($tab_id_fields[$j] == 'tags_chosen' && !empty($_REQUEST['tags_chosen'])) {
                include_once("modules".DIRECTORY_SEPARATOR."tags".
                   DIRECTORY_SEPARATOR."tags_search.php");
            }
            //WELCOME PAGE
            elseif ($tab_id_fields[$j] == 'welcome'  && (!empty($_REQUEST['welcome']))) {
                $welcome = trim($_REQUEST['welcome']);
                $json_txt .= "'multifield' : ['".addslashes($welcome)."'],";
                if (ctype_digit($_REQUEST['welcome'])) {
                    $where_request_welcome .= "(res_id = :resIdWelcome) or ";
                    $arrayPDO = array_merge($arrayPDO, array(":resIdWelcome" => $welcome));
                }
                $where_request_welcome .= "( REGEXP_REPLACE(lower(translate(subject,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')),'( ){2,}', ' ') like lower(:multifieldWelcome) "
                    ."or (lower(translate(alt_identifier,'/','')) like lower(:multifieldWelcome) OR lower(alt_identifier) like lower(:multifieldWelcome)) "
                    ."or lower(barcode) LIKE lower(:multifieldWelcome) "
                    ."or res_id in (select identifier from notes where lower(translate(note_text,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) like lower(:multifieldWelcome)) "
                    ."or res_id in (select res_id_master from res_attachments where (lower(translate(identifier,'/','')) like lower(:multifieldWelcome) OR lower(identifier) like lower(:multifieldWelcome)) AND status NOT IN ('DEL','OBS','TMP')))";

                $multifieldWelcome = \SrcCore\models\TextFormatModel::normalize(['string' => $welcome]);
                $multifieldWelcome = preg_replace('/\s+/', ' ', $multifieldWelcome);
                $arrayPDO = array_merge($arrayPDO, array(":multifieldWelcome" => "%".$multifieldWelcome."%"));
                set_include_path(
                    'apps' . DIRECTORY_SEPARATOR
                    . $_SESSION['config']['app_id']
                    . DIRECTORY_SEPARATOR . 'tools'
                    . DIRECTORY_SEPARATOR . PATH_SEPARATOR . get_include_path()
                );
            }

            // CONFIDENTIALITY
            elseif ($tab_id_fields[$j] == 'confidentiality' && ($_REQUEST['confidentiality'] <> "")) {
                $json_txt .= " 'confidentiality' : ['".addslashes(trim($_REQUEST['confidentiality']))."'],";
                $where_request .= " confidentiality  = :confidentiality and ";
                $arrayPDO = array_merge($arrayPDO, array(":confidentiality" => $_REQUEST['confidentiality']));
            }
            // DOCTYPES
            elseif ($tab_id_fields[$j] == 'doctypes_chosen' && !empty($_REQUEST['doctypes_chosen'])) {
                $json_txt .= " 'doctypes_chosen' : [";

                for ($get_i = 0; $get_i <count($_REQUEST['doctypes_chosen']); $get_i++) {
                    $json_txt .= "'".$_REQUEST['doctypes_chosen'][$get_i]."',";
                }

                $json_txt = substr($json_txt, 0, -1);

                $where_request .= " type_id IN  (:doctypesChosen) ";
                $where_request .=" and  ";
                $arrayPDO = array_merge($arrayPDO, array(":doctypesChosen" => $_REQUEST['doctypes_chosen']));
                $json_txt .= '],';
            }

            // CREATION DATE PJ : FROM
            elseif ($tab_id_fields[$j] == 'creation_date_pj_from' && !empty($_REQUEST['creation_date_pj_from'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['creation_date_pj_from'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['creation_date_pj_from'];
                } else {
                    $where_request .= " res_id in (SELECT res_id_master FROM res_attachments WHERE (".$req->extract_date("creation_date")." >= :creationDatePjFrom) AND status NOT IN ('DEL','OBS','TMP') ) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":creationDatePjFrom" => $func->format_date_db($_REQUEST['creation_date_pj_from'])));
                    $json_txt .= " 'creation_date_pj_from' : ['".trim($_REQUEST['creation_date_pj_from'])."'],";
                }
            }
            // CREATION DATE PJ : TO
            elseif ($tab_id_fields[$j] == 'creation_date_pj_to' && !empty($_REQUEST['creation_date_pj_to'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['creation_date_pj_to'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['creation_date_pj_to'];
                } else {
                    $where_request .= " res_id in (SELECT res_id_master FROM res_attachments WHERE (".$req->extract_date("creation_date")." <= :creationDatePjTo) AND status NOT IN ('DEL','OBS','TMP') ) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":creationDatePjTo" => $func->format_date_db($_REQUEST['creation_date_pj_to'])));
                    $json_txt .= " 'creation_date_pj_to' : ['".trim($_REQUEST['creation_date_pj_to'])."'],";
                }
            }
            // CREATION DATE : FROM
            elseif ($tab_id_fields[$j] == 'creation_date_from' && !empty($_REQUEST['creation_date_from'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['creation_date_from'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['creation_date_from'];
                } else {
                    $where_request .= " (".$req->extract_date("creation_date")." >= :creationDateFrom) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":creationDateFrom" => $func->format_date_db($_REQUEST['creation_date_from'])));
                    $json_txt .= " 'creation_date_from' : ['".trim($_REQUEST['creation_date_from'])."'],";
                }
            }
            // CREATION DATE : TO
            elseif ($tab_id_fields[$j] == 'creation_date_to' && !empty($_REQUEST['creation_date_to'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['creation_date_to'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['creation_date_to'];
                } else {
                    $where_request .= " (".$req->extract_date("creation_date")." <= :creationDateTo) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":creationDateTo" => $func->format_date_db($_REQUEST['creation_date_to'])));
                    $json_txt .= " 'creation_date_to' : ['".trim($_REQUEST['creation_date_to'])."'],";
                }
            }
            // EXP DATE : FROM
            elseif ($tab_id_fields[$j] == 'exp_date_from' && !empty($_REQUEST['exp_date_from'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['exp_date_from'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['exp_date_from'];
                } else {
                    $where_request .= " (".$req->extract_date("departure_date")." >= :expDateFrom) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":expDateFrom" => $func->format_date_db($_REQUEST['exp_date_from'])));
                    $json_txt .= " 'exp_date_from' : ['".trim($_REQUEST['exp_date_from'])."'],";
                }
            }
            // EXP DATE : TO
            elseif ($tab_id_fields[$j] == 'exp_date_to' && !empty($_REQUEST['exp_date_to'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['exp_date_to'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['exp_date_to'];
                } else {
                    $where_request .= " (".$req->extract_date("departure_date")." <= :expDateTo) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":expDateTo" => $func->format_date_db($_REQUEST['exp_date_to'])));
                    $json_txt .= " 'exp_date_to' : ['".trim($_REQUEST['exp_date_to'])."'],";
                }
            }
            // PROCESS DATE : FROM (closing_date)
            elseif ($tab_id_fields[$j] == 'closing_date_from' && !empty($_REQUEST['closing_date_from'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['closing_date_from'])==false) {
                    $_SESSION['error'] .=  _WRONG_DATE_FORMAT.' : '.$_REQUEST['closing_date_from'];
                } else {
                    $where_request .= " (".$req->extract_date("closing_date")." >= :closingDateFrom) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":closingDateFrom" => $func->format_date_db($_REQUEST['closing_date_from'])));
                    $json_txt .= "'closing_date_from' : ['".trim($_REQUEST['closing_date_from'])."'],";
                }
            }
            // CLOSING DATE : TO
            elseif ($tab_id_fields[$j] == 'closing_date_to' && !empty($_REQUEST['closing_date_to'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['closing_date_to'])==false) {
                    $_SESSION['error'] = _WRONG_DATE_FORMAT.' : '.$_REQUEST['closing_date_to'];
                } else {
                    $where_request .= " (".$req->extract_date("closing_date")." <= :closingDateTo) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":closingDateTo" => $func->format_date_db($_REQUEST['closing_date_to'])));
                    $json_txt .= "'closing_date_to' : ['".trim($_REQUEST['closing_date_to'])."'],";
                }
            }
            // PROCESS LIMIT DATE : FROM
            elseif ($tab_id_fields[$j] == 'process_limit_date_from' && !empty($_REQUEST['process_limit_date_from'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['process_limit_date_from'])==false) {
                    $_SESSION['error'] = _WRONG_DATE_FORMAT.' : '.$_REQUEST['process_limit_date_from'];
                } else {
                    $where_request .= " (".$req->extract_date("process_limit_date")." >= :processLimitDateFrom) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":processLimitDateFrom" => $func->format_date_db($_REQUEST['process_limit_date_from'])));
                    $json_txt .= "'process_limit_date_from' : ['".trim($_REQUEST['process_limit_date_from'])."'],";
                }
            }
            // PROCESS LIMIT DATE : TO
            elseif ($tab_id_fields[$j] == 'process_limit_date_to' && !empty($_REQUEST['process_limit_date_to'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['process_limit_date_to'])==false) {
                    $_SESSION['error'] = _WRONG_DATE_FORMAT.' : '.$_REQUEST['process_limit_date_to'];
                } else {
                    $where_request .= " (".$req->extract_date("process_limit_date")." <= :processLimitDateTo) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":processLimitDateTo" => $func->format_date_db($_REQUEST['process_limit_date_to'])));
                    $json_txt .= "'process_limit_date_to' : ['".trim($_REQUEST['process_limit_date_to'])."'],";
                }
            }
            // STATUS
            elseif ($tab_id_fields[$j] == 'status_chosen' && isset($_REQUEST['status_chosen'])) {
                $json_txt .= " 'status_chosen' : [";
                $where_request .="( ";
                for ($get_i = 0; $get_i <count($_REQUEST['status_chosen']); $get_i++) {
                    $json_txt .= "'".$_REQUEST['status_chosen'][$get_i]."',";
                    $where_request .= " ( status = :statusChosen_".$get_i.") or ";
                    $arrayPDO = array_merge($arrayPDO, array(":statusChosen_".$get_i => $_REQUEST['status_chosen'][$get_i]));
                }
                $where_request = preg_replace("/or $/", "", $where_request);
                $json_txt = substr($json_txt, 0, -1);
                $where_request .=") and ";
                $json_txt .= '],';
            }
            // MAIL CATEGORY
            elseif ($tab_id_fields[$j] == 'category' && !empty($_REQUEST['category'])) {
                $where_request .= " category_id = :category AND ";
                $arrayPDO = array_merge($arrayPDO, array(":category" => $_REQUEST['category']));
                $json_txt .= "'category' : ['".addslashes($_REQUEST['category'])."'],";
            }
            // ADMISSION DATE : FROM
            elseif ($tab_id_fields[$j] == 'admission_date_from' && !empty($_REQUEST['admission_date_from'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['admission_date_from'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['admission_date_from'];
                } else {
                    $where_request .= " (".$req->extract_date("admission_date")." >= :admissionDateFrom) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":admissionDateFrom" => $func->format_date_db($_REQUEST['admission_date_from'])));
                    $json_txt .= " 'admission_date_from' : ['".trim($_REQUEST['admission_date_from'])."'],";
                }
            }
            // ADMISSION DATE : TO
            elseif ($tab_id_fields[$j] == 'admission_date_to' && !empty($_REQUEST['admission_date_to'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['admission_date_to'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['admission_date_to'];
                } else {
                    $where_request .= " (".$req->extract_date("admission_date")." <= :admissionDateTo) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":admissionDateTo" => $func->format_date_db($_REQUEST['admission_date_to'])));
                    $json_txt .= " 'admission_date_to' : ['".trim($_REQUEST['admission_date_to'])."'],";
                }
            }
            // DOC DATE : FROM
            elseif ($tab_id_fields[$j] == 'doc_date_from' && !empty($_REQUEST['doc_date_from'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['doc_date_from'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['doc_date_from'];
                } else {
                    $where_request .= " (".$req->extract_date("doc_date")." >= :docDateFrom) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":docDateFrom" => $func->format_date_db($_REQUEST['doc_date_from'])));
                    $json_txt .= " 'doc_date_from' : ['".trim($_REQUEST['doc_date_from'])."'],";
                }
            }
            // DOC DATE : TO
            elseif ($tab_id_fields[$j] == 'doc_date_to' && !empty($_REQUEST['doc_date_to'])) {
                if (preg_match($_ENV['date_pattern'], $_REQUEST['doc_date_to'])==false) {
                    $_SESSION['error'] .= _WRONG_DATE_FORMAT.' : '.$_REQUEST['doc_date_to'];
                } else {
                    $where_request .= " (".$req->extract_date("doc_date")." <= :docDateTo) and ";
                    $arrayPDO = array_merge($arrayPDO, array(":docDateTo" => $func->format_date_db($_REQUEST['doc_date_to'])));
                    $json_txt .= " 'doc_date_to' : ['".trim($_REQUEST['doc_date_to'])."'],";
                }
            }
            // Senders
            elseif ($tab_id_fields[$j] == 'sender' && !empty($_REQUEST['sender_type']) && !empty($_REQUEST['sender_id'])) {
                $json_txt .= " 'sender' : ['".addslashes(trim($_REQUEST['sender']))."'], 'sender_id' : ['".addslashes(trim($_REQUEST['sender_id']))."'], 'sender_type' : ['".addslashes(trim($_REQUEST['sender_type']))."']";

                $recipientType = $_REQUEST['sender_type'] == 'onlyContact' ? 'contact' : $_REQUEST['sender_type'];

                $where_request .= " ((res_id in (select res_id from resource_contacts where item_id = :senderId and type = :senderType and mode = 'sender'))";

                $arrayPDO = array_merge($arrayPDO, [":senderId" => $_REQUEST['sender_id']]);
                $arrayPDO = array_merge($arrayPDO, [":senderType" => $recipientType]);

                $where_request .= ') and ';
            }
            // Recipients
            elseif ($tab_id_fields[$j] == 'recipient' && !empty($_REQUEST['recipient_type']) && !empty($_REQUEST['recipient_id'])) {
                $json_txt .= " 'recipient' : ['".addslashes(trim($_REQUEST['recipient']))."'], 'recipient_id' : ['".addslashes(trim($_REQUEST['recipient_id']))."'], 'recipient_type' : ['".addslashes(trim($_REQUEST['recipient_type']))."']";

                $recipientType = $_REQUEST['recipient_type'] == 'onlyContact' ? 'contact' : $_REQUEST['recipient_type'];

                $where_request .= " ((res_id in (select res_id from resource_contacts where item_id = :recipientId and type = :recipientType and mode = 'recipient'))";
                $arrayPDO = array_merge($arrayPDO, [":recipientId" => $_REQUEST['recipient_id']]);
                $arrayPDO = array_merge($arrayPDO, [":recipientType" => $recipientType]);

                // Search on attachments ?
//                if ($_REQUEST['recipient_type'] != 'entity') {
//                    $where_request .= " or (res_id in (SELECT res_id_master FROM res_attachments WHERE dest_address_id = :recipientId AND status NOT IN ('DEL','OBS','TMP')))";
//                }

                $where_request .= ') and ';
            }
            //recherche sur le destinataire en fonction de ce que la personne a saisi
            elseif ($tab_id_fields[$j] == 'recipient' && !empty(trim($_REQUEST['recipient'])) && empty($_REQUEST['recipient_type']) && empty($_REQUEST['recipient_id'])) {
                $fields = \SrcCore\controllers\AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => ['company']]);
        
                $requestData = \SrcCore\controllers\AutoCompleteController::getDataForRequest([
                    'search'       => $_REQUEST['recipient'],
                    'fields'       => $fields,
                    'fieldsNumber' => 1
                ]);
        
                $contacts = \Contact\models\ContactModel::get([
                    'select'    => ['id'],
                    'where'     => $requestData['where'],
                    'data'      => $requestData['data']
                ]);
                $contactIds     = array_column($contacts, 'id');
                $contactIds    = !empty($contactIds) ? $contactIds : [0];
                $json_txt      .= " 'recipient' : ['".addslashes(trim($_REQUEST['recipient']))."'],";
                $where_request .= " (res_id in (select res_id from resource_contacts where item_id in (:recipientIds) and type = 'contact' and mode = 'recipient')) and ";
                $arrayPDO       = array_merge($arrayPDO, array(":recipientIds" => $contactIds));
            }
            //recherche sur l'expéditeur en fonction de ce que la personne a saisi
            elseif ($tab_id_fields[$j] == 'sender' && !empty(trim($_REQUEST['sender'])) && empty($_REQUEST['sender_type']) && empty($_REQUEST['sender_id'])) {
                $fields = \SrcCore\controllers\AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => ['company']]);
        
                $requestData = \SrcCore\controllers\AutoCompleteController::getDataForRequest([
                    'search'       => $_REQUEST['sender'],
                    'fields'       => $fields,
                    'fieldsNumber' => 1
                ]);
        
                $contacts = \Contact\models\ContactModel::get([
                    'select'    => ['id'],
                    'where'     => $requestData['where'],
                    'data'      => $requestData['data']
                ]);
                $contactIds     = array_column($contacts, 'id');
                $contactIds    = !empty($contactIds) ? $contactIds : [0];
                $json_txt      .= " 'sender' : ['".addslashes(trim($_REQUEST['sender']))."'],";
                $where_request .= " (res_id in (select res_id from resource_contacts where item_id in (:senderIds) and type = 'contact' and mode = 'sender')) and ";
                $arrayPDO       = array_merge($arrayPDO, array(":senderIds" => $contactIds));
            }
            //VISA USER
            elseif ($tab_id_fields[$j] == 'visa_user' && !empty($_REQUEST['ac_visa_user'])) {
                $json_txt .= " 'visa_user' : ['".addslashes(trim($_REQUEST['visa_user']))."'], 'user_visa' : ['".addslashes(trim($_REQUEST['ac_visa_user']))."']";
                $userVisa = $_REQUEST['ac_visa_user'];
                $where_request .= " (res_id in (select res_id from listinstance where difflist_type = 'VISA_CIRCUIT' and signatory = false and item_id in (select user_id from users where user_id = :user_visa))) and  ";
                $arrayPDO = array_merge($arrayPDO, array(":user_visa" => $userVisa));
            } elseif ($tab_id_fields[$j] == 'visa_user' && empty($_REQUEST['ac_visa_user']) && !empty($_REQUEST['visa_user'])) {
                $json_txt .= " 'visa_user' : ['".addslashes(trim($_REQUEST['visa_user']))."'], 'user_visa' : ['".addslashes(trim($_REQUEST['visa_user']))."']";
                $visaUser = pg_escape_string($_REQUEST['visa_user']);
                //$where_request .= " ((user_firstname = '".$contactid_internal."' or user_lastname = '".$contactid_internal."') or ";
                $where_request .= " (res_id in (select res_id from listinstance where difflist_type = 'VISA_CIRCUIT' and signatory = false and item_id in (select user_id from users where firstname ilike :visa_user or lastname ilike :visa_user))) and ";
                $arrayPDO = array_merge($arrayPDO, array(":visa_user" => "%".$visaUser."%"));
            }
            // Nom du signataire
            elseif ($tab_id_fields[$j] == 'signatory_name' && !empty($_REQUEST['ac_signatory_name'])) {
                $json_txt .= " 'signatory_name' : ['".addslashes(trim($_REQUEST['signatory_name']))."'], 'signatory_name_id' : ['".addslashes(trim($_REQUEST['ac_signatory_name']))."']";
                $signatory_name = $_REQUEST['ac_signatory_name'];
                $where_request .= " (res_id in (select res_id from listinstance where difflist_type = 'VISA_CIRCUIT' and (signatory = true or (process_date is null and requested_signature = true)) and item_id = :signatory_name_id)) and  ";
                $arrayPDO = array_merge($arrayPDO, array(":signatory_name_id" => $signatory_name));
            }
            //recherche sur les signataires en fonction de ce que la personne a saisi
            elseif ($tab_id_fields[$j] == 'signatory_name' && empty($_REQUEST['signatory_name_id']) && !empty($_REQUEST['signatory_name'])) {
                $json_txt .= " 'signatory_name' : ['".addslashes(trim($_REQUEST['signatory_name']))."']";
                $signatory_name = pg_escape_string($_REQUEST['signatory_name']);
                //$where_request .= " ((user_firstname = '".$contactid_internal."' or user_lastname = '".$contactid_internal."') or ";
                $where_request .= " (res_id in (select res_id from listinstance where difflist_type = 'VISA_CIRCUIT' and (signatory = true or (process_date is null and requested_signature = true)) and item_id in (select user_id from users where firstname ilike :signatoryName or lastname ilike :signatoryName))) and ";
                $arrayPDO = array_merge($arrayPDO, array(":signatoryName" => "%".$signatory_name."%"));
            }
            // SEARCH IN BASKETS
            elseif ($tab_id_fields[$j] == 'baskets_clause' && !empty($_REQUEST['baskets_clause'])) {
                //$func->show_array($_REQUEST);exit;
                switch ($_REQUEST['baskets_clause']) {
                    case 'false':
                        $baskets_clause = "false";
                        $json_txt .= "'baskets_clause' : ['false'],";
                        break;
                        
                    case 'true':
                        for ($ind_bask = 0; $ind_bask < count($_SESSION['user']['baskets']); $ind_bask++) {
                            if ($_SESSION['user']['baskets'][$ind_bask]['coll_id'] == $coll_id) {
                                if (isset($_SESSION['user']['baskets'][$ind_bask]['clause']) && trim($_SESSION['user']['baskets'][$ind_bask]['clause']) <> '') {
                                    $_SESSION['searching']['comp_query'] .= ' or ('.$_SESSION['user']['baskets'][$ind_bask]['clause'].')';
                                }
                            }
                        }
                        $_SESSION['searching']['comp_query'] = preg_replace('/^ or/', '', $_SESSION['searching']['comp_query']);
                        $baskets_clause = ($_REQUEST['baskets_clause']);
                        $json_txt .= " 'baskets_clause' : ['true'],";
                        break;
                    
                    default:
                        $json_txt .= " 'baskets_clause' : ['".addslashes(trim($_REQUEST['baskets_clause']))."'],";
                        for ($ind_bask = 0; $ind_bask < count($_SESSION['user']['baskets']); $ind_bask++) {
                            if ($_SESSION['user']['baskets'][$ind_bask]['id'] == $_REQUEST['baskets_clause']) {
                                if (isset($_SESSION['user']['baskets'][$ind_bask]['clause']) && trim($_SESSION['user']['baskets'][$ind_bask]['clause']) <> '') {
                                    $where_request .= ' (' . $_SESSION['user']['baskets'][$ind_bask]['clause'] . ') and ' ;
                                    break;
                                }
                            }
                        }
                }
            } elseif (preg_match('/^indexingCustomField_/', $tab_id_fields[$j]) && !empty($_REQUEST[$tab_id_fields[$j]])) {  // opt indexes check
                $customFieldId = str_replace("indexingCustomField_", "", $tab_id_fields[$j]);
                $customFieldId = str_replace("_min", "", $customFieldId);
                $customFieldId = str_replace("_max", "", $customFieldId);
                $customFieldId = str_replace("_from", "", $customFieldId);
                $customFieldId = str_replace("_to", "", $customFieldId);
                $customField   = \CustomField\models\CustomFieldModel::getById(['id' => $customFieldId]);
                $json_txt     .= " '".$tab_id_fields[$j]."' : ['".addslashes(trim($_REQUEST[$tab_id_fields[$j]]))."'],";
                if (in_array($customField['type'], ['select', 'radio', 'checkbox'])) {
                    $where_request .= " (custom_fields->'".$customFieldId."' @> :valueCustom_".$customFieldId.") and ";
                    $arrayPDO       = array_merge($arrayPDO, array(":valueCustom_".$customFieldId => '"'.$_REQUEST[$tab_id_fields[$j]].'"'));
                } elseif ($customField['type'] == 'date') {
                    if (strpos($tab_id_fields[$j], '_from') !== false) {
                        $where_request .= " ((custom_fields->>'".$customFieldId."')::timestamp >= :valueCustom_".$customFieldId."_".$j."::timestamp) and ";
                    } elseif (strpos($tab_id_fields[$j], '_to') !== false) {
                        $where_request .= " ((custom_fields->>'".$customFieldId."')::timestamp <= :valueCustom_".$customFieldId."_".$j."::timestamp) and ";
                    }
                    $arrayPDO = array_merge($arrayPDO, array(":valueCustom_".$customFieldId."_".$j => $_REQUEST[$tab_id_fields[$j]]));
                } elseif ($customField['type'] == 'string') {
                    $where_request .= " (custom_fields->>'".$customFieldId."' ilike (:valueCustom_".$customFieldId.")) and ";
                    $arrayPDO       = array_merge($arrayPDO, array(":valueCustom_".$customFieldId => '%'.$_REQUEST[$tab_id_fields[$j]].'%'));
                } elseif ($customField['type'] == 'integer') {
                    if (strpos($tab_id_fields[$j], '_min') !== false) {
                        $where_request .= " (custom_fields->'".$customFieldId."' >= :valueCustom_".$customFieldId."_".$j.") and ";
                    } elseif (strpos($tab_id_fields[$j], '_max') !== false) {
                        $where_request .= " (custom_fields->'".$customFieldId."' <= :valueCustom_".$customFieldId."_".$j.") and ";
                    }
                    $arrayPDO = array_merge($arrayPDO, array(":valueCustom_".$customFieldId."_".$j => $_REQUEST[$tab_id_fields[$j]]));
                }
            }
        }

        $json_txt = preg_replace('/,$/', '', $json_txt);
        $json_txt .= "}},";
    }
    $json_txt = preg_replace('/,$/', '', $json_txt);
}
$json_txt = preg_replace("/,$/", "", $json_txt);
$json_txt .= '}';


$_SESSION['current_search_query'] = $json_txt;
if (!empty($_SESSION['error_search'])) {
    $_SESSION['error'] = _MUST_CORRECT_ERRORS.' : '.$_SESSION['error_search'];

    if ($mode == 'normal') {
        ?>
<script type="text/javascript">
    window.top.location.href = '<?php echo $_SESSION['config']['businessappurl'].'index.php?page=search_adv&dir=indexing_searching'; ?>';
</script>
<?php
    } else {
        ?>
<script type="text/javascript">
    window.top.location.href = '<?php echo $_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=search_adv&mode='.$mode; ?>';
</script>
<?php
    }
    exit();
} else {
    if ($where_request_welcome <> '') {
        // $where_request_welcome = substr($where_request_welcome, 0, -4);
        $where_request .= '(' . $where_request_welcome . ') and ';
    }
    $where_request = trim($where_request);
    $_SESSION['searching']['where_request'] = $where_request;
    $_SESSION['searching']['where_request_parameters'] = $arrayPDO;
}
if (empty($_SESSION['error_search'])) {
    //##################
    $page = 'list_results_mlb'; ?>
<script type="text/javascript">
    window.top.location.href = '<?php if ($mode == 'normal') {
        echo $_SESSION['config']['businessappurl'].'index.php?page='.$page.'&dir=indexing_searching&load';
    } elseif ($mode=='frame' || $mode == 'popup') {
        echo $_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page='.$page.'&mode='.$mode.'&action_form='.$_REQUEST['action_form'].'&modulename='.$_REQUEST['modulename'];
    }
    if (isset($_REQUEST['nodetails'])) {
        echo '&nodetails';
    } ?>';
</script>
<?php
    exit();
}
$_SESSION['error_search'] = '';
