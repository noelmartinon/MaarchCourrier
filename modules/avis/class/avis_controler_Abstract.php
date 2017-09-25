<?php
/* 
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 * 
 */

/* 
 * @brief avis_controler_Abstract
 * @author dev@maarch.org
 * @ingroup avis
 * 
 */
abstract class avis_controler_Abstract
{

    public function getAvis($resId)
    {
        //define avis limit date
        $db = new Database();

        $query = "SELECT notes.user_id,notes.note_text,recommendation_limit_date FROM notes,mlb_coll_ext WHERE identifier = ? AND note_text LIKE '[POUR AVIS]%' AND notes.identifier=mlb_coll_ext.res_id";

        $stmt = $db->query($query, array($resId));

        $avis = $stmt->fetchObject();

        return $avis;
    }

    #####################################
    ## send avis
    #####################################

    public function processAvis($resId, $recommendation_limit_date = '')
    {
        //define avis limit date
        $db = new Database();

        if ($recommendation_limit_date <> '') {

            $query = "UPDATE mlb_coll_ext SET recommendation_limit_date = ? where res_id = ?";
            $stmt = $db->query($query, array($recommendation_limit_date, $resId));
        }

        $query = "UPDATE res_letterbox SET modification_date = CURRENT_DATE where res_id = ?";
        $stmt = $db->query($query, array($resId));
    }

    public function getList($res_id, $coll_id, $bool_modif = false, $typeList, $isAvisStep = false, $fromDetail = "")
    {

        $circuit = $this->getWorkflow($res_id, $coll_id, $typeList);

        $str .= '<div class="error" id="divErrorAvis" onclick="this.hide();"></div>';
        $str .= '<div class="info" id="divInfoAvis" onclick="this.hide();"></div>';

        //AVIS USER LIST
        if ($bool_modif == true) {
            $str .= '<select data-placeholder="' . _ADD_AVIS_ROLE . '" id="avisUserList" onchange="addAvisUser();">';
            $str .= '<option value="" ></option>';

            $tab_userentities = $this->getEntityAvis();
            $tab_users = $this->getUsersAvis();
            //Order by parent entity
            foreach ($tab_userentities as $key => $value) {
                $str .= '<optgroup label="' . $tab_userentities[$key]['entity_id'] . '">';
                foreach ($tab_users as $user) {
                    if ($tab_userentities[$key]['entity_id'] == $user['entity_id']) {
                        $selected = " ";
                        if ($user['id'] == $step['user_id'])
                            $selected = " selected";
                        $str .= '<option value="' . $user['id'] . '" ' . $selected . '>' . $user['lastname'] . ' ' . $user['firstname'] . '</option>';
                    }
                }
                $str .= '</optgroup>';
            }
            $str .= '</select>';
            $str .= '<script>';
            $str .= 'new Chosen($(\'avisUserList\'),{width: "250px", disable_search_threshold: 10});';
            $str .= '</script>';

            include_once "modules/entities/class/class_manage_listdiff.php";
            $diff_list = new diffusion_list();
            $listModels = $diff_list->select_listmodels($typeList);

            $str .= ' <select data-placeholder="'._ADD_AVIS_MODEL.'" name="modelList" id="modelList" onchange="loadAvisModelUsers();">';
            $str .= '<option value=""></option>';
            foreach ($listModels as $lm) {

                $str .= '<option value="' . $lm['object_id'] . '">' . $lm['title'] . '</option>';
            }
            $str .= '</select>';

            $str .= '<script>';
            $str .= 'new Chosen($(\'modelList\'),{width: "250px;", disable_search_threshold: 10});';
            $str .= '</script>';
            $str .= '<br/><br/>';
        }

        $str .= '<div id="avis_content">';
        //AVIS USER IN DOCUMENT
        $i = 1;
        $lastUserAvis = true;

        if (count($circuit['avis']['users']) == 0) {
            $str .= '<div id="emptyAvis"><strong><em>' . _EMPTY_AVIS_WORKFLOW . '</em></strong></div>';
        } else {
            $str .= '<div id="emptyAvis" style="display:none;"><strong><em>' . _EMPTY_AVIS_WORKFLOW . '</em></strong></div>';
            if (count($circuit['avis']['users']) > 0) {
                foreach ($circuit['avis']['users'] as $it => $info_userAvis) {
                    if (empty($info_userAvis['process_date'])) {
                        if ($lastUserAvis == true && $isAvisStep == true) {
                            $vised = ' currentAvis';
                            $modif = 'false';
                            $disabled = '';
                            $link_vis = 'arrow-right ';
                            $del_vis = '<div class="delete_avis"></div>';
                            if ($info_userAvis['user_id'] <> $_SESSION['user']['UserId']) {
                                //$info_vised = '<p style="color:red;">Vous donnez votre avis à la place de ' . $info_userAvis['firstname'] . ' ' . $info_userAvis['lastname'] . '!</p>';
                                $dropZone = '';
                            } else {
                                //$info_vised = 'Vous êtes l\'actuel conseiller';
                                $dropZone = '';
                            }
                        } else {
                            $dropZone = '<i class="fa fa-exchange fa-2x fa-rotate-90" aria-hidden="true" title="'._DRAG_N_DROP_CHANGE_ORDER.'" style="cursor: pointer"></i>';
                            $vised = '';
                            if ($bool_modif == true) {
                                $modif = 'true';
                                $del_vis = '<i class="fa fa-trash" aria-hidden="true" onclick="delAvisUser(this.parentElement.parentElement);" title="' . _DELETE . '"></i>';
                                $disabled = '';
                            } else {
                                $dropZone = '';
                                $modif = 'false';
                                $del_vis = '';
                                $disabled = ' disabled="disabled"';
                            }


                            $info_vised = '';
                            $link_vis = 'hourglass-half';
                        }



                        $lastUserAvis = false;
                    } else {
                        $lastUserAvis = true;
                        $modif = 'false';
                        $vised = ' vised';
                        $link_vis = 'check';
                        $disabled = ' disabled="disabled"';
                        $info_vised = '<br/><sub>avis donné le : ' . functions::format_date_db($info_userAvis['process_date'], '', '', true) . '</sub>';
                        $del_vis = '';
                    }
                    //AVIS USER LINE CIRCUIT
                    $str .= '<div class="droptarget' . $vised . '" id="avis_' . $i . '" draggable="' . $modif . '">';
                    $str .= '<span class="avisUserStatus">';
                    $str .= '<i class="fa fa-' . $link_vis . '" aria-hidden="true"></i>';
                    $str .= '</span>';
                    $str .= '<span class="avisUserInfo">';
                    $str .= '<sup class="avisUserPos nbResZero">'.$i.'</sup>&nbsp;&nbsp;';
                    $str .= '<i class="fa fa-user fa-2x" aria-hidden="true"></i> ' . $info_userAvis['lastname'] . ' ' . $info_userAvis['firstname'] . ' <sup class="nbRes">' . $info_userAvis['entity_id'] . '</sup>' . $info_vised;
                    $str .= '</span>';
                    $str .= '<span class="avisUserAction">';
                    $str .= $del_vis;
                    $str .= '</span>';
                    $str .= '<span class="avisUserConsigne">';
                    $str .= '<input class="userId" type="hidden" value="' . $info_userAvis['user_id'] . '"/><input class="avisDate" type="hidden" value="' . $info_userAvis['process_date'] . '"/><input' . $disabled . ' class="consigne" type="text" value="' . $info_userAvis['process_comment'] . '"/>';
                    $str .= '</span>';
                    $str .= '<span id="dropZone">';
                    $str .= $dropZone;
                    $str .= '</span>';
                    $str .= '</div>';

                    $i++;
                }
            }
        }

        $str .= '</div>';

        if ($bool_modif == true) {
            //SAVE AVIS CIRCUIT
            $str .= '<input type="button" name="send" id="send" value="' . _SAVE_CHANGES . '" class="button" ';
            $str .= 'onclick="updateAvisWorkflow(' . $res_id . ');" /> ';

            //SAVE AS MODEL
            $str .= '<input type="button" name="save" id="save" value="Enregistrer comme modèle" class="button" onclick="$(\'modalSaveAvisModel\').style.display = \'block\';" />';
            $str .= '<div id="modalSaveAvisModel" >';
            $str .= '<h3>' . _SAVE_POSITION . ' ' . _AVIS_WORKFLOW . '</h3><br/>';
            $str .= '<label for="titleModel">' . _TITLE . '</label> ';
            $str .= '<input type="text" name="titleModel" id="titleModel"/><br/>';
            $str .= '<input type="button" name="saveModel" id="saveModel" value="' . _VALIDATE . '" class="button" onclick="saveAvisWorkflowAsModel();" /> ';
            $str .= '<input type="button" name="cancelModel" id="cancelModel" value="' . _CANCEL . '" class="button" onclick="$(\'modalSaveAvisModel\').style.display = \'none\';" />';
            $str .= '</div>';
        }
        $str .= '<script>initDragNDropAvis();</script>';
        return $str;
    }

    public function getWorkflow($res_id, $coll_id, $typeList)
    {
        require_once('modules/entities/class/class_manage_listdiff.php');
        $listdiff = new diffusion_list();
        $roles = $listdiff->list_difflist_roles();
        $circuitAvis = $listdiff->get_listinstance($res_id, false, $coll_id, $typeList);
        if (isset($circuitAvis['copy']))
            unset($circuitAvis['copy']);
        return $circuitAvis;
    }

    public function getEntityAvis()
    {
        $db = new Database();

        $stmt = $db->query("SELECT distinct(entities.entity_id) from users, usergroup_content, users_entities,entities WHERE users_entities.user_id = users.user_id and 
            users_entities.primary_entity = 'Y' and users.user_id = usergroup_content.user_id AND entities.entity_id = users_entities.entity_id AND group_id IN 
            (SELECT group_id FROM usergroups_services WHERE service_id = ?)  
            order by entities.entity_id", array('avis_documents'));

        $tab_userentities = array();


        while ($res = $stmt->fetchObject()) {
            array_push($tab_userentities, array('entity_id' => $res->entity_id));
        }
        //print_r($tab_userentities);
        return $tab_userentities;
    }

    public function getGroupAvis()
    {
        $db = new Database();

        $stmt = $db->query("SELECT DISTINCT(usergroup_content.group_id),group_desc from usergroups, usergroup_content WHERE usergroups.group_id = usergroup_content.group_id AND usergroup_content.group_id IN (SELECT group_id FROM usergroups_services WHERE service_id = ?)", array('avis_documents'));

        $tab_usergroup = array();


        while ($res = $stmt->fetchObject()) {
            array_push($tab_usergroup, array('group_id' => $res->group_id, 'group_desc' => $res->group_desc));
        }
        //print_r($tab_usergroup);
        return $tab_usergroup;
    }

    public function getUsersAvis($group_id = null)
    {
        $db = new Database();

        if ($group_id <> null) {
            $stmt = $db->query("SELECT users.user_id, users.firstname, users.lastname, usergroup_content.group_id,entities.entity_id from users, usergroup_content, users_entities,entities WHERE users_entities.user_id = users.user_id and users.status <> 'DEL' and 
                users_entities.primary_entity = 'Y' and users.user_id = usergroup_content.user_id AND entities.entity_id = users_entities.entity_id AND group_id IN 
                (SELECT group_id FROM usergroups_services WHERE service_id = ? AND group_id = ?)  order by users.lastname", array('avis_documents', $group_id));
        } else {
            $stmt = $db->query("SELECT users.user_id, users.firstname, users.lastname, usergroup_content.group_id,entities.entity_id from users, usergroup_content, users_entities,entities WHERE users_entities.user_id = users.user_id and users.status <> 'DEL' and 
                users_entities.primary_entity = 'Y' and users.user_id = usergroup_content.user_id AND entities.entity_id = users_entities.entity_id AND group_id IN 
                (SELECT group_id FROM usergroups_services WHERE service_id = ?)  
                order by users.lastname", array('avis_documents'));
        }

        $tab_users = array();


        while ($res = $stmt->fetchObject()) {
            array_push($tab_users, array('id' => $res->user_id, 'firstname' => $res->firstname, 'lastname' => $res->lastname, 'group_id' => $res->group_id, 'entity_id' => $res->entity_id));
        }
        return $tab_users;
    }

    public function myPosAvis($res_id, $coll_id, $listDiffType)
    {
        $db = new Database();
        $stmt = $db->query("SELECT sequence, item_mode from listinstance WHERE res_id= ? and coll_id = ? and difflist_type = ? and item_id = ? and  process_date ISNULL ORDER BY listinstance_id ASC LIMIT 1", array($res_id, $coll_id, $listDiffType, $_SESSION['user']['UserId']));
        $res = $stmt->fetchObject();
        /* if ($res->item_mode == 'sign'){
          return $this->nbAvis($res_id, $coll_id);
          } */
        return $res->sequence;
    }

    public function saveModelWorkflow($id_list, $workflow, $typeList, $title)
    {
        require_once('modules/entities/class/class_manage_listdiff.php');
        $diff_list = new diffusion_list();


        $diff_list->save_listmodel(
                $workflow, $typeList, $id_list, $title
        );
    }

    public function saveWorkflow($res_id, $coll_id, $workflow, $typeList)
    {
        require_once('modules/entities/class/class_manage_listdiff.php');
        $diff_list = new diffusion_list();

        $diff_list->save_listinstance(
                $workflow, $typeList, $coll_id, $res_id, $_SESSION['user']['UserId'], $_SESSION['user']['primaryentity']['id']
        );
    }

    public function getCurrentStepAvis($res_id, $coll_id, $listDiffType)
    {
        $db = new Database();
        if ($listDiffType == 'entity_id') {
            $order = 'DESC';
        } else {
            $order = 'ASC';
        }
        $stmt = $db->query("SELECT sequence, item_mode from listinstance WHERE res_id= ? and coll_id = ? and difflist_type = ? and process_date ISNULL ORDER BY listinstance_id " . $order . " LIMIT 1", array($res_id, $coll_id, $listDiffType));
        $res = $stmt->fetchObject();
        /* if ($res->item_mode == 'avis'){
          return $this->nbAvis($res_id, $coll_id);
          } */
        return $res->sequence;
    }

    public function getStepDetailsAvis($res_id, $coll_id, $listDiffType, $sequence)
    {
        $stepDetails = array();
        $db = new Database();
        $stmt = $db->query("SELECT * "
                . "from listinstance WHERE res_id= ? and coll_id = ? "
                . "and difflist_type = ? and sequence = ? "
                . "ORDER BY listinstance_id ASC LIMIT 1", array($res_id, $coll_id, $listDiffType, $sequence));
        $res = $stmt->fetchObject();
        $stepDetails['listinstance_id'] = $res->listinstance_id;
        $stepDetails['coll_id'] = $res->coll_id;
        $stepDetails['res_id'] = $res->res_id;
        $stepDetails['listinstance_type'] = $res->listinstance_type;
        $stepDetails['sequence'] = $res->sequence;
        $stepDetails['item_id'] = $res->item_id;
        $stepDetails['item_type'] = $res->item_type;
        $stepDetails['item_mode'] = $res->item_mode;
        $stepDetails['added_by_user'] = $res->added_by_user;
        $stepDetails['added_by_entity'] = $res->added_by_entity;
        $stepDetails['visible'] = $res->visible;
        $stepDetails['viewed'] = $res->viewed;
        $stepDetails['difflist_type'] = $res->difflist_type;
        $stepDetails['process_date'] = $res->process_date;
        $stepDetails['process_comment'] = $res->process_comment;

        return $stepDetails;
    }

    public function nbAvis($res_id, $coll_id)
    {
        $db = new Database();
        $stmt = $db->query("SELECT listinstance_id from listinstance WHERE res_id= ? and coll_id = ? and item_mode = ? and difflist_type = 'AVIS_CIRCUIT'", array($res_id, $coll_id, 'avis'));
        return $stmt->rowCount();
    }

    #####################################
    ## add note on a resource
    #####################################

    public function UpdateNoteAvis($resId, $collId, $noteContent)
    {
        $status = 'ok';
        $error = '';
        //control parameters
        if (isset($resId) && empty($resId)) {
            $status = 'ko';
            $error = 'resId empty ';
        }
        if (isset($collId) && empty($collId)) {
            $status = 'ko';
            $error = 'collId empty ';
        }
        if (isset($noteContent) && empty($noteContent)) {
            $status = 'ko';
            $error .= 'noteContent empty ';
        }
        //process
        if ($status == 'ok') {
            require_once 'core/class/class_security.php';
            require_once 'modules/notes/notes_tables.php';
            $security = new security();
            $view = $security->retrieve_view_from_coll_id($collId);
            $table = $security->retrieve_table_from_coll($collId);
            $db = new Database();
            $query = "SELECT res_id FROM " . $view . " WHERE res_id = ?";
            $stmt = $db->query($query, array($resId));
            if ($stmt->rowCount() == 0) {
                $status = 'ko';
                $error .= 'resId not exists';
            } else {
                $query = "UPDATE " . NOTES_TABLE
                        . " SET note_text = ?"
                        . ", date_note = CURRENT_TIMESTAMP"
                        . " WHERE identifier = ?"
                        . " AND note_text LIKE '[POUR AVIS]%'"
                        . " AND coll_id = ?";

                $stmt = $db->query($query, array($noteContent, $resId, $collId));

                $hist = new history();
                $hist->add(
                        $view, $resId, 'UP', 'resup', _AVIS_UPDATED
                        . _ON_DOC_NUM . $resId . ' ' . _BY . ' ' . $_SESSION['user']['UserId'], $_SESSION['config']['databasetype'], 'notes'
                );
            }
        }
        $returnArray = array(
            'status' => $status,
            'value' => $id,
            'error' => $error,
        );
        return $returnArray;
    }

}
