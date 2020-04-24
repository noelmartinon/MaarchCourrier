<?php
/*
*   Copyright 2008-2016 Maarch and Document Image Solutions
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
 * @brief Contains the functions to manage visa and notice workflow.
 *
 * @file
 *
 * @author Nicolas Couture <couture@docimsol.com>
 * @date $date$
 *
 * @version $Revision$
 * @ingroup visa
 */
define('FPDF_FONTPATH', $core_path.'apps/maarch_entreprise/tools/pdfb/fpdf_1_7/font/');
require_once $core_path.'apps/maarch_entreprise/tools/pdfb/fpdf_1_7/fpdf.php';
require_once $core_path.'apps/maarch_entreprise/tools/pdfb/fpdf_1_7/fpdi.php';

abstract class visa_Abstract extends Database
{
    public $errorMessageVisa;

    /***
    * Build Maarch module tables into sessions vars with a xml configuration file
    *
    *
    */
    public function build_modules_tables()
    {
        if (file_exists(
            $_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR
            .$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'
            .DIRECTORY_SEPARATOR.'visa'.DIRECTORY_SEPARATOR.'xml'
            .DIRECTORY_SEPARATOR.'config.xml'
        )
        ) {
            $configPath = $_SESSION['config']['corepath'].'custom'
                        .DIRECTORY_SEPARATOR.$_SESSION['custom_override_id']
                        .DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR
                        .'visa'.DIRECTORY_SEPARATOR.'xml'
                        .DIRECTORY_SEPARATOR.'config.xml';
        } else {
            $configPath = 'modules'.DIRECTORY_SEPARATOR.'visa'
                        .DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR
                        .'config.xml';
        }

        $xmlconfig = simplexml_load_file($configPath);
        $conf = $xmlconfig->CONFIG;
        $_SESSION['modules_loaded']['visa']['exeSign'] = (string) $conf->exeSign;
        $_SESSION['modules_loaded']['visa']['showAppletSign'] = (string) $conf->showAppletSign;
        $_SESSION['modules_loaded']['visa']['reason'] = (string) $conf->reason;
        $_SESSION['modules_loaded']['visa']['location'] = (string) $conf->location;
        $_SESSION['modules_loaded']['visa']['licence_number'] = (string) $conf->licence_number;

        $_SESSION['modules_loaded']['visa']['width_blocsign'] = (string) $conf->width_blocsign;
        $_SESSION['modules_loaded']['visa']['height_blocsign'] = (string) $conf->height_blocsign;

        $_SESSION['modules_loaded']['visa']['confirm_sign_by_email'] = (string) $conf->confirm_sign_by_email;

        $routing_template = (string) $conf->routing_template;

        if (file_exists(
            $_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR
            .$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'
            .DIRECTORY_SEPARATOR.'visa'.DIRECTORY_SEPARATOR.'Bordereau_visa_modele.pdf'
        )
        ) {
            $routing_template = $_SESSION['config']['corepath'].'custom'.DIRECTORY_SEPARATOR
            .$_SESSION['custom_override_id'].DIRECTORY_SEPARATOR.'modules'
            .DIRECTORY_SEPARATOR.'visa'.DIRECTORY_SEPARATOR.'Bordereau_visa_modele.pdf';
        }

        $_SESSION['modules_loaded']['visa']['routing_template'] = $routing_template;
    }

    public function getDocsBasket()
    {
        require_once 'core/class/class_request.php';
        $request = new request();
        $table = $_SESSION['current_basket']['view'];
        $select[$table] = array();
        array_push(
            $select[$table],
            'res_id',
            'status',
            'category_id as category_img',
                        'contact_firstname',
            'contact_lastname',
            'contact_society',
            'user_lastname',
                        'user_firstname',
            'priority',
            'creation_date',
            'admission_date',
            'subject',
                        'process_limit_date',
            'entity_label',
            'dest_user',
            'category_id',
            'type_label',
                        'exp_user_id',
            'doc_custom_n1 as count_attachment',
            'alt_identifier',
            'is_multicontacts',
            'locker_user_id',
            'locker_time'
        );

        $where_tab = array();

        // $_SESSION['current_basket']['last_query']['select'] = $select;
        // $_SESSION['current_basket']['last_query']['where'] = $where;
        // $_SESSION['current_basket']['last_query']['arrayPDO'] = $arrayPDO;
        // $_SESSION['current_basket']['last_query']['orderstr'] = $orderstr;
        // $_SESSION['current_basket']['last_query']['limit'] = $_SESSION['config']['databasesearchlimit'];

        //From basket
        if (!empty($_SESSION['current_basket']['last_query']['where'])) {
            $where_tab[] = stripslashes($_SESSION['current_basket']['last_query']['where']); //Basket clause
        } elseif (!empty($_SESSION['current_basket']['clause'])) {
            $where_tab[] = stripslashes($_SESSION['current_basket']['clause']); //Basket clause
        }

        //Order
        $orderstr = 'order by creation_date desc';
        if (!empty($_SESSION['current_basket']['last_query']['orderstr'])) {
            $orderstr = $_SESSION['current_basket']['last_query']['orderstr'];
        } elseif (isset($_SESSION['last_order_basket'])) {
            $orderstr = $_SESSION['last_order_basket'];
        }

        //Request
        $where = implode(' and ', $where_tab);
        $tab = $request->PDOselect(
            $select,
            $where,
            array(),
            $orderstr,
            $_SESSION['config']['databasetype'],
            $_SESSION['config']['databasesearchlimit'],
            false,
            '',
            '',
            '',
            false,
            false,
            'distinct'
        );

        $tab_docs = array();
        foreach ($tab as $doc) {
            array_push($tab_docs, $doc[0]['value']);
        }

        return $tab_docs;
    }

    public function get_rep_path($res_id, $coll_id)
    {
        require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_security.php';
        require_once 'core'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'docservers_controler.php';
        $docserverControler = new docservers_controler();
        $sec = new security();
        $view = $sec->retrieve_view_from_coll_id($coll_id);
        if (empty($view)) {
            $view = $sec->retrieve_table_from_coll($coll_id);
        }

        $db = new Database();
        $stmt = $db->query(
            'select docserver_id from res_view_attachments where res_id_master = ?'
            ."AND status <> 'DEL' order by res_id desc",
            array($res_id)
        );
        while ($res = $stmt->fetchObject()) {
            $docserver_id = $res->docserver_id;
            break;
        }

        $stmt = $db->query(
            'select path_template from '.$_SESSION['tablename']['docservers'].' where docserver_id = ?',
            array($docserver_id)
        );

        $res = $stmt->fetchObject();
        $docserver_path = $res->path_template;

        $stmt = $db->query(
            'select filename, format, path, title, res_id, res_id_version, attachment_type '
            ."from res_view_attachments where res_id_master = ? AND status <> 'OBS' AND status <> 'SIGN' "
            ."AND status <> 'DEL' and attachment_type NOT IN "
            ."('converted_pdf','print_folder') order by creation_date desc",
            array($res_id)
        );

        $array_reponses = array();
        $cpt_rep = 0;
        while ($res2 = $stmt->fetchObject()) {
            $filename = $res2->filename;
            $format = 'pdf';
            $filename_pdf = str_ireplace($res2->format, $format, $filename);
            $path = preg_replace('/#/', DIRECTORY_SEPARATOR, $res2->path);
            //$filename_pdf = str_replace(pathinfo($filename, PATHINFO_EXTENSION), "pdf",$filename);
            if (file_exists($docserver_path.$path.$filename_pdf)) {
                $array_reponses[$cpt_rep]['path'] = $docserver_path.$path.$filename_pdf;
                $array_reponses[$cpt_rep]['title'] = $res2->title;
                $array_reponses[$cpt_rep]['attachment_type'] = $res2->attachment_type;
                if ($res2->res_id_version == 0) {
                    $array_reponses[$cpt_rep]['res_id'] = $res2->res_id;
                    $array_reponses[$cpt_rep]['is_version'] = 0;
                } else {
                    $array_reponses[$cpt_rep]['res_id'] = $res2->res_id_version;
                    $array_reponses[$cpt_rep]['is_version'] = 1;
                }
                if ($res2->res_id_version == 0 && $array_reponses[$cpt_rep]['attachment_type'] == 'outgoing_mail') {
                    $array_reponses[$cpt_rep]['is_version'] = 2;
                }
                ++$cpt_rep;
            }
        }
        /*echo "<pre>";
        print_r($array_reponses);
        echo "</pre>";*/
        return $array_reponses;
    }

    protected function isSameFile($firstFile, $secondFile)
    {
        $nb1 = strrpos($firstFile, '.');
        $nb2 = strrpos($secondFile, '.');

        return substr($firstFile, 0, $nb1) === substr($secondFile, 0, $nb2);
    }

    protected function hasSameFileInArray($fileName, $filesArray)
    {
        foreach ($filesArray as $tmpFileName) {
            if ($this->isSameFile($fileName, $tmpFileName)) {
                return true;
            }
        }

        return false;
    }

    public function checkResponseProject($res_id, $coll_id)
    {
        $this->errorMessageVisa = null;

        $attachmentTypes = \Attachment\models\AttachmentModel::getAttachmentsTypesByXML();

        $noSignableAttachments = [];
        foreach ($attachmentTypes as $key => $value) {
            if (!$value['sign']) {
                $noSignableAttachments[] = $key;
            }
        }

        $db = new Database();
        if (empty($noSignableAttachments)) {
            $stmt = $db->query("SELECT * FROM res_view_attachments WHERE res_id_master = ? AND coll_id = ? AND status NOT IN ('DEL','OBS','TMP') AND in_signature_book = ?", [$res_id, $coll_id, true]);
        } else {
            $stmt = $db->query("SELECT * FROM res_view_attachments WHERE res_id_master = ? AND coll_id = ? AND status NOT IN ('DEL','OBS','TMP') AND attachment_type NOT IN (?) AND in_signature_book = ? ", [$res_id, $coll_id, $noSignableAttachments, true]);
        }
        if ($stmt->rowCount() <= 0) {
            $this->errorMessageVisa = _NO_RESPONSE_PROJECT_VISA;

            return false;
        }

        /*$resFirstFiles = [];

        while ($res = $stmt->fetchObject()) {
            if (($res->format == 'doc' || $res->format == 'docx' || $res->format == 'odt') && !in_array($res->attachment_type, ['simple_attachment', 'simple_attachment_rp'])) {
                array_push($resFirstFiles, $res);
            }
        }

        $stmt = $db->query("SELECT * FROM res_attachments WHERE res_id_master = ? AND coll_id = ? AND attachment_type IN ('converted_pdf') AND status NOT IN ('DEL','OBS','TMP')", array($res_id, $coll_id));

        $resSecondFiles = [];

        while ($res = $stmt->fetchObject()) {
            array_push($resSecondFiles, $res->filename);
        }
        foreach ($resFirstFiles as $tmpObj) {
            if ($this->hasSameFileInArray($tmpObj->filename, $resSecondFiles)) {
                continue;
            }
            if (!$this->errorMessageVisa) {
                $this->errorMessageVisa .= _PLEASE_CONVERT_PDF_VISA;
            }
            $this->errorMessageVisa .= '<br/>&nbsp;&nbsp;';
            $this->errorMessageVisa .= $_SESSION['attachment_types'][$tmpObj->attachment_type].' : ';
            $this->errorMessageVisa .= $tmpObj->title;
        }*/

        return true;
    }

    public function getWorkflow($res_id, $coll_id, $typeList)
    {
        require_once 'modules/entities/class/class_manage_listdiff.php';
        $listdiff = new diffusion_list();
        $roles = $listdiff->list_difflist_roles();
        $circuit = $listdiff->get_listinstance($res_id, false, $coll_id, $typeList);
        if (isset($circuit['copy'])) {
            unset($circuit['copy']);
        }

        return $circuit;
    }

    public function saveWorkflow($res_id, $coll_id, $workflow, $typeList)
    {
        require_once 'modules/entities/class/class_manage_listdiff.php';
        $diff_list = new diffusion_list();

        $diff_list->save_listinstance(
            $workflow,
            $typeList,
            $coll_id,
            $res_id,
            $_SESSION['user']['UserId'],
            $_SESSION['user']['primaryentity']['id']
        );
    }

    public function saveModelWorkflow($id_list, $workflow, $typeList, $title)
    {
        require_once 'modules/entities/class/class_manage_listdiff.php';
        $diff_list = new diffusion_list();

        $diff_list->save_listmodel(
            $workflow,
            $typeList,
            $id_list,
            $title
        );
    }

    protected function getWorkflowsNumberByTitle($title)
    {
        $db = new Database();
        $stmt = $db->query('SELECT * FROM listmodels WHERE title = ?', array($title));

        return $stmt->rowCount();
    }

    public function isWorkflowTitleFree($title)
    {
        $nb = $this->getWorkflowsNumberByTitle($title);
        if ($nb == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function deleteWorkflow($res_id, $coll_id)
    {
        $db = new Database();
        $db->query('DELETE FROM visa_circuit WHERE res_id= ? AND coll_id= ?', array($res_id, $coll_id));
    }

    public function nbVisa($res_id, $coll_id)
    {
        $db = new Database();
        $stmt = $db->query('SELECT listinstance_id from listinstance WHERE res_id= ? and coll_id = ? and item_mode = ?', array($res_id, $coll_id, 'visa'));

        return $stmt->rowCount();
    }

    public function getCurrentStep($res_id, $coll_id, $listDiffType)
    {
        $db = new Database();
        $where = 'res_id= ? and coll_id = ? and difflist_type = ? and process_date IS NULL';
        $order = 'ORDER BY listinstance_id ASC';
        $query = $db->limit_select(0, 1, 'sequence, item_mode', 'listinstance', $where, '', '', $order);

        $stmt = $db->query($query, array($res_id, $coll_id, $listDiffType));
        $res = $stmt->fetchObject();
        if ($res->item_mode == 'sign') {
            return $this->nbVisa($res_id, $coll_id);
        }

        return $res->sequence;
    }

    public function getUsersCurrentVis($res_id)
    {
        $db = new Database();
        $result = array();
        $stmt = $db->query("SELECT item_id from listinstance WHERE res_id= ? and difflist_type = 'VISA_CIRCUIT'  ORDER BY sequence ASC", array($res_id));
        while ($res = $stmt->fetchObject()) {
            $result[] = $res->item_id;
        }

        return $result;
    }

    public function getCurrentUserStep($res_id)
    {
        $db = new Database();
        $stmt = $db->query('SELECT item_id from listinstance WHERE res_id= ? and coll_id = ? and difflist_type = ? and process_date ISNULL ORDER BY listinstance_id ASC LIMIT 1', array($res_id, 'letterbox_coll', 'VISA_CIRCUIT'));
        $res = $stmt->fetchObject();

        return $res->item_id;
    }

    public function getStepDetails($res_id, $coll_id, $listDiffType, $sequence)
    {
        $stepDetails = array();
        $db = new Database();
        $order = 'ORDER by listinstance_id ASC';
        $where = 'res_id= ? and coll_id = ? and difflist_type = ? and sequence = ? ';
        $query = $db->limit_select(0, 1, '*', 'listinstance', $where, '', '', $order);

        $stmt = $db->query($query, array($res_id, $coll_id, $listDiffType, $sequence));

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

    public function processVisaWorkflow($aArgs = [])
    {
        $message = [];
        $db = new Database();
        //enables to process the visa if i am not the item_id
        if ($aArgs['stepDetails']['item_id'] != $_SESSION['user']['UserId']) {
            $db->query(
                'UPDATE listinstance SET process_date = CURRENT_TIMESTAMP '
                .' WHERE listinstance_id = ? AND item_mode = ? AND res_id = ? AND item_id = ? AND difflist_type = ?',
                array($aArgs['stepDetails']['listinstance_id'], $aArgs['stepDetails']['item_mode'], $aArgs['res_id'], $aArgs['stepDetails']['item_id'], 'VISA_CIRCUIT')
            );

            $stmt = $db->query('SELECT firstname, lastname, user_id FROM users WHERE user_id IN (?)', array([$_SESSION['user']['UserId'], $aArgs['stepDetails']['item_id']]));
            foreach ($stmt as $value) {
                if ($value['user_id'] == $_SESSION['user']['UserId']) {
                    $user1 = $value['firstname'].' '.$value['lastname'];
                } else {
                    $user2 = $value['firstname'].' '.$value['lastname'];
                }
            }

            $message[] = ' '._VISA_BY.' '.$user1.' '._INSTEAD_OF.' '.$user2;
        } else {
            $db->query(
                'UPDATE listinstance SET process_date = CURRENT_TIMESTAMP '
                .' WHERE listinstance_id = ? AND item_mode = ? AND res_id = ? AND item_id = ? AND difflist_type = ?',
                array($aArgs['stepDetails']['listinstance_id'], $aArgs['stepDetails']['item_mode'], $aArgs['res_id'], $_SESSION['user']['UserId'], 'VISA_CIRCUIT')
            );
            $message[] = '';
        }

        return $message;
    }

    public function myPosVisa($res_id, $coll_id, $listDiffType)
    {
        $db = new Database();
        $order = 'ORDER by listinstance_id ASC';
        $where = 'res_id= ? and coll_id = ? and difflist_type = ? and item_id = ? and  process_date IS NULL';
        $query = $db->limit_select(0, 1, 'sequence, item_mode', 'listinstance', $where, '', '', $order);

        $stmt = $db->query($select, array($res_id, $coll_id, $listDiffType, $_SESSION['user']['UserId']));

        $res = $stmt->fetchObject();
        if ($res->item_mode == 'sign') {
            return $this->nbVisa($res_id, $coll_id);
        }

        return $res->sequence;
    }

    public function getUsersVis($group_id = null)
    {
        $db = new Database();

        if ($group_id != null) {
            $stmt = $db->query("SELECT users.user_id, users.firstname, users.lastname, usergroup_content.group_id,entities.entity_id from users, usergroup_content, users_entities,entities WHERE users_entities.user_id = users.user_id and users.status <> 'DEL' and 
				users_entities.primary_entity = 'Y' and users.user_id = usergroup_content.user_id AND entities.entity_id = users_entities.entity_id AND group_id IN 
				(SELECT group_id FROM usergroups_services WHERE service_id = ? AND group_id = ?)  order by users.lastname", array('visa_documents', $group_id));
        } else {
            $stmt = $db->query("SELECT distinct on(users.user_id) users.user_id, users.firstname, users.lastname, usergroup_content.group_id,entities.entity_id, users.enabled from users, usergroup_content, users_entities,entities WHERE users_entities.user_id = users.user_id and users.status <> 'DEL' and 
				users_entities.primary_entity = 'Y' and users.user_id = usergroup_content.user_id AND entities.entity_id = users_entities.entity_id AND group_id IN 
				(SELECT group_id FROM usergroups_services WHERE service_id = ?)  
				order by users.user_id,users.lastname", array('visa_documents'));
        }

        $tab_users = array();

        while ($res = $stmt->fetchObject()) {
            array_push($tab_users, array('id' => $res->user_id, 'firstname' => $res->firstname, 'lastname' => $res->lastname, 'group_id' => $res->group_id, 'entity_id' => $res->entity_id, 'enabled' => $res->enabled));
        }

        return $tab_users;
    }

    public function getGroupVis()
    {
        $db = new Database();

        $stmt = $db->query('SELECT DISTINCT(usergroup_content.group_id),group_desc FROM usergroups, usergroup_content WHERE usergroups.group_id = usergroup_content.group_id AND usergroup_content.group_id IN (SELECT group_id FROM usergroups_services WHERE service_id = ?)', array('visa_documents'));

        $tab_usergroup = array();

        while ($res = $stmt->fetchObject()) {
            array_push($tab_usergroup, array('group_id' => $res->group_id, 'group_desc' => $res->group_desc));
        }

        return $tab_usergroup;
    }

    public function getEntityVis()
    {
        $db = new Database();

        $stmt = $db->query("SELECT distinct(entities.entity_id) FROM users, usergroup_content, users_entities,entities WHERE users_entities.user_id = users.user_id and 
			users_entities.primary_entity = 'Y' and users.user_id = usergroup_content.user_id AND entities.entity_id = users_entities.entity_id AND group_id IN 
			(SELECT group_id FROM usergroups_services WHERE service_id = ?)  
			order by entities.entity_id", array('visa_documents'));

        $tab_userentities = array();

        while ($res = $stmt->fetchObject()) {
            array_push($tab_userentities, array('entity_id' => $res->entity_id));
        }

        return $tab_userentities;
    }

    public function allUserVised($res_id, $coll_id, $typeList)
    {
        $circuit = $this->getWorkflow($res_id, $coll_id, 'VISA_CIRCUIT');
        if (isset($circuit['visa'])) {
            foreach ($circuit['visa']['users'] as $seq => $step) {
                if ($step['process_date'] == '') {
                    return false;
                }
            }
        }

        return true;
    }

    public function getConsigne($res_id, $coll_id, $userId)
    {
        $circuit = $this->getWorkflow($res_id, $coll_id, 'VISA_CIRCUIT');
        if (isset($circuit['visa'])) {
            foreach ($circuit['visa']['users'] as $seq => $step) {
                if ($step['user_id'] == $userId) {
                    return $step['process_comment'];
                }
            }
        }
        if (isset($circuit['sign'])) {
            foreach ($circuit['sign']['users'] as $seq => $step) {
                if ($step['user_id'] == $userId) {
                    return $step['process_comment'];
                }
            }
        }

        return '';
    }

    public function setStatusVisa($res_id, $coll_id, $inDetails = false)
    {
        $curr_visa_wf = $this->getWorkflow($res_id, $coll_id, 'VISA_CIRCUIT');

        $db = new Database();
        $where = 'res_id= ? and coll_id = ? and difflist_type = ? and process_date IS NULL';
        $order = 'ORDER BY listinstance_id ASC';
        $query = $db->limit_select(0, 1, 'requested_signature', 'listinstance', $where, '', '', $order);

        $stmt = $db->query($query, array($res_id, $coll_id, 'VISA_CIRCUIT'));
        $resListDiffVisa = $stmt->fetchObject();

        // If there is only one step in the visa workflow, we set status to ESIG
        if ($resListDiffVisa->requested_signature) {
            $mailStatus = 'ESIG';
            if ($inDetails == false) {
                \Attachment\controllers\AttachmentController::generateAttachForMailing([
                    'resIdMaster' => $res_id,
                    'userId' => $_SESSION['user']['UserId']
                ]);
            }
        } else {
            $mailStatus = 'EVIS';
        }

        $db->query('UPDATE res_letterbox SET status = ? WHERE res_id = ? ', array($mailStatus, $res_id));
    }

    public function getList($res_id, $coll_id, $bool_modif = false, $typeList, $isVisaStep = false, $fromDetail = '')
    {
        $core = new core_tools();
        $circuit = $this->getWorkflow($res_id, $coll_id, $typeList);
        $sAllAttachmentSigned = $this->isAllAttachementSigned($res_id);
        if ($sAllAttachmentSigned == 'noAttachment') {
            $str = '<input type="hidden" id="isAllAttachementSigned" value="false"/>';
            $isAllAttachementSigned = '';
            $isAllAttachementSignedInfo = '';
        } elseif ($sAllAttachmentSigned == 'yes') {
            $str = '<input type="hidden" id="isAllAttachementSigned" value="allsigned"/>';
            $isAllAttachementSigned = '';
            $isAllAttachementSignedInfo = _IS_ALL_ATTACHMENT_SIGNED_INFO2;
        } else {
            $str = '<input type="hidden" id="isAllAttachementSigned" value="false"/>';
            $isAllAttachementSigned = '';
        }

        $str .= '<div class="error" id="divErrorVisa" onclick="this.hide();"></div>';
        $str .= '<div class="info" id="divInfoVisa" onclick="this.hide();"></div>';

        //VISA USER LIST
        if ($bool_modif == true) {
            $str .= '<select data-placeholder="'._ADD_VISA_ROLE.'" id="visaUserList" onchange="addVisaUser();">';
            $str .= '<option value="" ></option>';

            $tab_userentities = $this->getEntityVis();
            $tab_users = $this->getUsersVis();
            /* Order by parent entity **/
            foreach ($tab_userentities as $key => $value) {
                $str .= '<optgroup label="'.$tab_userentities[$key]['entity_id'].'">';
                foreach ($tab_users as $user) {
                    if ($user['enabled'] == 'Y') {
                        if ($tab_userentities[$key]['entity_id'] == $user['entity_id']) {
                            $selected = ' ';
                            if ($user['id'] == $step['user_id']) {
                                $selected = ' selected';
                            }
                            $str .= '<option value="'.$user['id'].'" '.$selected.'>'.$user['lastname'].' '.$user['firstname'].'</option>';
                        }
                    }
                }
                $str .= '</optgroup>';
            }
            $str .= '</select>';
            $str .= '<script>';
            $str .= ' $j("#visaUserList").chosen({width: "250px", disable_search_threshold: 10});';
            $str .= '</script>';

            require_once 'modules/entities/class/class_manage_listdiff.php';
            $diff_list = new diffusion_list();
            $listModels = $diff_list->select_listmodels($typeList);

            $str .= ' <select data-placeholder="'._ADD_VISA_MODEL.'" name="modelList" id="modelList" onchange="loadVisaModelUsers();">';
            $str .= '<option value=""></option>';
            foreach ($listModels as $lm) {
                $str .= '<option value="'.$lm['object_id'].'">'.$lm['title'].'</option>';
            }
            $str .= '</select>';

            $str .= '<script>';
            $str .= ' $j("#modelList").chosen({width: "250px", disable_search_threshold: 10});';
            $str .= '</script>';
            $str .= '<br/><br/>';
        }
        if (!empty($isAllAttachementSignedInfo)) {
            $str .= '<b style="color:red;">'.$isAllAttachementSignedInfo.'</b>';
        }
        $str .= '<div id="visa_content">';
        //VISA USER IN DOCUMENT
        $i = 1;
        $lastUserVis = true;

        if ((empty($circuit['visa']['users']) || !is_array($circuit['visa']['users']) || count($circuit['visa']['users']) == 0) && (empty($circuit['sign']['users']) || !is_array($circuit['sign']['users']) || count($circuit['sign']['users']) == 0)) {
            $str .= '<div id="emptyVisa"><strong><em>'._EMPTY_VISA_WORKFLOW.'</em></strong></div>';
        } else {
            $str .= '<div id="emptyVisa" style="display:none;"><strong><em>'._EMPTY_VISA_WORKFLOW.'</em></strong></div>';
            if (!empty($circuit['visa']['users']) && is_array($circuit['visa']['users']) && count($circuit['visa']['users']) > 0) {
                $isCurrentVisa = false;
                foreach ($circuit['visa']['users'] as $it => $info_userVis) {
                    if (empty($info_userVis['process_date'])) {
                        if ($lastUserVis == true && $isVisaStep == true && $isCurrentVisa === false) {
                            $vised = ' currentVis';
                            $disabled = '';
                            $link_vis = 'arrow-right ';
                            $del_vis = '<div class="delete_visa"></div>';
                            if ($info_userVis['requested_signature'] && $info_userVis['user_id'] != $_SESSION['user']['UserId']) {
                                $info_vised = '<p style="color:red;">'._SIGN_USER_COU_DESC.' '.$info_userVis['firstname'].' '.$info_userVis['lastname'].'</p>';
                                $dropZone = '';
                            } elseif ($info_userVis['requested_signature'] && $info_userVis['user_id'] == $_SESSION['user']['UserId']) {
                                $info_vised = '<p style="font-weight:normal;">'._SIGN_USER_COU.'</p>';
                                $dropZone = '';
                            } elseif (!$info_userVis['requested_signature'] && $info_userVis['user_id'] != $_SESSION['user']['UserId']) {
                                $info_vised = '<p style="color:red;">'._VISA_USER_COU_DESC.' '.$info_userVis['firstname'].' '.$info_userVis['lastname'].'</p>';
                                $dropZone = '';
                            } else {
                                $info_vised = '<p style="font-weight:normal;">'._VISA_USER_COU.'</p>';
                                $dropZone = '';
                            }
                            if ($core->test_service('modify_visa_in_signatureBook', 'visa', false)) {
                                $modif = 'true';
                                $dropZone = '<i class="fa fa-exchange-alt fa-2x fa-rotate-90" aria-hidden="true" title="'._DRAG_N_DROP_CHANGE_ORDER.'" style="cursor: pointer"></i>';
                                $del_vis = '<i class="fa fa-trash-alt" aria-hidden="true" onclick="delVisaUser(this.parentElement.parentElement);" title="'._DELETE.'"></i>';
                            } else {
                                $modif = 'false';
                            }

                            $info_vised .= '<select style="display:none;" id="signRequest_'.$i.'" '.$isAllAttachementSigned;
                            $info_vised .= ' disabled="disabled" ';
                            $info_vised .= '>';
                            $info_vised .= '<option value="false">'._VISA_USER_SEARCH.'</option>';

                            $info_vised .= '<option value="true"';
                            if (!empty($info_userVis['requested_signature'])) {
                                $info_vised .= ' selected="selected" ';
                            }
                            $info_vised .= '>'._SIGNATORY.'</option>';
                            $info_vised .= '</select>';
                        } else {
                            $dropZone = '<i class="fa fa-exchange-alt fa-2x fa-rotate-90" aria-hidden="true" title="'._DRAG_N_DROP_CHANGE_ORDER.'" style="cursor: pointer"></i>';
                            $vised = '';
                            if ($bool_modif == true) {
                                $modif = 'true';
                                $del_vis = '<i class="fa fa-trash-alt" aria-hidden="true" onclick="delVisaUser(this.parentElement.parentElement);" title="'._DELETE.'"></i>';
                                $disabled = '';
                            } else {
                                $modif = 'false';
                                $dropZone = '';
                                $del_vis = '';
                                $disabled = ' disabled="disabled"';
                            }

                            $info_vised = '<br/><select id="signRequest_'.$i.'" '.$isAllAttachementSigned;
                            if (!empty($info_userVis['signatory'])) {
                                $info_vised .= ' disabled="disabled" ';
                            }
                            $info_vised .= '>';
                            $info_vised .= '<option value="false">'._VISA_USER_SEARCH.'</option>';

                            $info_vised .= '<option value="true"';
                            if (!empty($info_userVis['requested_signature'])) {
                                $info_vised .= ' selected="selected" ';
                            }
                            $info_vised .= '>'._SIGNATORY.'</option>';
                            $info_vised .= '</select>';
                            $link_vis = 'hourglass-half';
                        }

                        $lastUserVis = false;
                        $isCurrentVisa = true;
                    } else {
                        $lastUserVis = true;
                        $modif = 'false';

                        $disabled = ' disabled="disabled"';
                        if (preg_match("/\[DEL\]/", $info_userVis['process_comment'])) {
                            $info_vised = '<br/><select id="signRequest_'.$i.'" '.$isAllAttachementSigned;
                            if (!empty($info_userVis['signatory'])) {
                                $info_vised .= ' disabled="disabled" ';
                            }
                            $info_vised .= '>';
                            $info_vised .= '<option value="false">'._VISA_USER_SEARCH.'</option>';

                            $info_vised .= '<option value="true"';
                            if (!empty($info_userVis['requested_signature'])) {
                                $info_vised .= ' selected="selected" ';
                            }
                            $info_vised .= '>'._SIGNATORY.'</option>';
                            $info_vised .= '</select>';
                            $link_vis = 'times';
                            $vised = ' moved vised';
                            $del_vis = '<i class="fa fa-trash-alt" aria-hidden="true" onclick="delVisaUser(this.parentElement.parentElement);" title="'._DELETE.'"></i>';
                        } else {
                            if (!empty($info_userVis['signatory'])) {
                                $info_vised = '<br/><sub>signé le : '.functions::format_date_db($info_userVis['process_date'], '', '', true).'</sub>';
                                $info_vised .= '<br/><select id="signRequest_'.$i.'" style="width:auto;display:none;" disabled="disabled" '.$isAllAttachementSigned;
                                $info_vised .= '>';
                                $info_vised .= '<option value="false" selected="selected">'._VISA_USER_SEARCH.'</option>';

                                $info_vised .= '<option value="true"';
                                $info_vised .= '>'._SIGNATORY.'</option>';
                                $info_vised .= '</select>';
                            } else {
                                $info_vised = '<br/><sub>visé le : '.functions::format_date_db($info_userVis['process_date'], '', '', true).'</sub>';

                                $info_vised .= '<br/><select id="signRequest_'.$i.'" style="width:auto;display:none;" disabled="disabled" '.$isAllAttachementSigned;
                                $info_vised .= '>';
                                $info_vised .= '<option value="false">'._VISA_USER_SEARCH.'</option>';

                                $info_vised .= '<option value="true" selected="selected"';
                                $info_vised .= '>'._SIGNATORY.'</option>';
                                $info_vised .= '</select>';
                            }

                            $link_vis = 'check';
                            $vised = ' vised';
                            $del_vis = '';
                        }
                    }
                    //VISA USER LINE CIRCUIT
                    $str .= '<div class="droptarget'.$vised.'" id="visa_'.$i.'" draggable="'.$modif.'">';
                    $str .= '<span class="visaUserStatus">';
                    $str .= '<i class="fa fa-'.$link_vis.'" aria-hidden="true"></i>';
                    $str .= '</span>';
                    $str .= '<span class="visaUserInfo">';
                    $str .= '<sup class="visaUserPos nbResZero">'.$i.'</sup>&nbsp;&nbsp;';
                    $str .= '<i class="fa fa-user fa-2x" aria-hidden="true"></i> '.$info_userVis['lastname'].' '.$info_userVis['firstname'].' <sup class="nbRes">'.$info_userVis['entity_id'].'</sup>';
                    $str .= '&nbsp;&nbsp; <sub><i id="signedUser_'.$i.'" title="au moins un document a été signé par cet utilisateur" class="visaUserSign fa fa-certificate" aria-hidden="true" style="color:#F99830;';
                    if (empty($info_userVis['signatory'])) {
                        $str .= 'visibility:hidden';
                    }
                    $str .= '"></i>'.$info_vised;
                    $str .= '</span>';
                    $str .= '<span class="visaUserAction">';
                    $str .= $del_vis;
                    $str .= '</span>';
                    $str .= '<span class="visaUserConsigne">';
                    $str .= '<input class="userId" type="hidden" value="'.$info_userVis['user_id'].'"/><input class="visaDate" type="hidden" value="'.$info_userVis['process_date'].'"/><input'.$disabled.' class="consigne" type="text" value="'.$info_userVis['process_comment'].'"/>';
                    $str .= '</span>';

                    $str .= '<span id="dropZone">';
                    $str .= $dropZone;
                    $str .= '</span>';
                    $str .= '</div>';

                    ++$i;
                }
            }

            //FOR USER SIGN
            if (!empty($circuit['sign']['users'])) {
                foreach ($circuit['sign']['users'] as $info_userSign) {
                    if (empty($info_userSign['process_date'])) {
                        if (($lastUserVis == true && $isVisaStep == true)) {
                            $vised = ' currentVis';
                            $modif = 'false';
                            $disabled = '';
                            $del_vis = '';
                            $link_vis = 'arrow-right ';
                            if ($info_userSign['requested_signature'] && $info_userSign['user_id'] != $_SESSION['user']['UserId']) {
                                $dropZone = '';
                                $info_vised = '<p style="color:red;">'._SIGN_USER_COU_DESC.' '.$info_userSign['firstname'].' '.$info_userSign['lastname'].'</p>';
                            } elseif ($info_userSign['requested_signature'] && $info_userSign['user_id'] == $_SESSION['user']['UserId']) {
                                $dropZone = '';
                                $info_vised = '<p style="font-weight:normal;">'._SIGN_USER_COU.'</p>';
                            } elseif (!$info_userSign['requested_signature'] && $info_userSign['user_id'] != $_SESSION['user']['UserId']) {
                                $dropZone = '';
                                $info_vised = '<p style="color:red;">'._VISA_USER_COU_DESC.' '.$info_userSign['firstname'].' '.$info_userSign['lastname'].'</p>';
                            } else {
                                $dropZone = '';
                                $info_vised = '<p style="font-weight:normal;">'._VISA_USER_COU.'</p>';
                            }
                            if ($core->test_service('modify_visa_in_signatureBook', 'visa', false)) {
                                $modif = 'true';
                                $dropZone = '<i class="fa -alt fa-2x fa-rotate-90" aria-hidden="true" title="'._DRAG_N_DROP_CHANGE_ORDER.'" style="cursor: pointer"></i>';
                                $del_vis = '<i class="fa fa-trash-alt" aria-hidden="true" onclick="delVisaUser(this.parentElement.parentElement);" title="'._DELETE.'"></i>';
                            } else {
                                $modif = 'false';
                            }
                            $info_vised .= '<select style="display:none;" id="signRequest_'.$i.'" '.$isAllAttachementSigned;
                            $info_vised .= ' disabled="disabled" ';
                            $info_vised .= '>';
                            $info_vised .= '<option value="false">'._VISA_USER_SEARCH.'</option>';
    
                            $info_vised .= '<option value="true"';
                            if (!empty($info_userSign['requested_signature'])) {
                                $info_vised .= ' selected="selected" ';
                            }
                            $info_vised .= '>'._SIGNATORY.'</option>';
                            $info_vised .= '</select>';
                        } else {
                            $dropZone = '<i class="fa fa-exchange-alt fa-2x fa-rotate-90" aria-hidden="true" title="'._DRAG_N_DROP_CHANGE_ORDER.'" style="cursor: pointer"></i>';
                            $vised = '';
                            if ($bool_modif == true) {
                                $modif = 'true';
                                $del_vis = '<i class="fa fa-trash-alt" aria-hidden="true" onclick="delVisaUser(this.parentElement.parentElement);" title="'._DELETE.'"></i>';
                                $disabled = '';
                            } else {
                                $dropZone = '';
                                $modif = 'false';
                                $del_vis = '';
                                $disabled = ' disabled="disabled"';
                            }
    
                            $info_vised = '<br/><select id="signRequest_'.$i.'" '.$isAllAttachementSigned;
                            if (!empty($info_userSign['signatory'])) {
                                $info_vised .= ' disabled="disabled" ';
                            }
                            $info_vised .= '>';
                            $info_vised .= '<option value="false">'._VISA_USER_SEARCH.'</option>';
    
                            $info_vised .= '<option value="true"';
                            if (!empty($info_userSign['requested_signature'])) {
                                $info_vised .= ' selected="selected" ';
                            }
                            $info_vised .= '>'._SIGNATORY.'</option>';
                            $info_vised .= '</select>';
                            $link_vis = 'hourglass-half';
                        }
                    } else {
                        $modif = 'false';
                        if (preg_match("/\[DEL\]/", $info_userSign['process_comment'])) {
                            $info_vised = '<br/><select id="signRequest_'.$i.'" '.$isAllAttachementSigned;
                            if (!empty($info_userSign['signatory'])) {
                                $info_vised .= ' disabled="disabled" ';
                            }
                            $info_vised .= '>';
                            $info_vised .= '<option value="false">'._VISA_USER_SEARCH.'</option>';
    
                            $info_vised .= '<option value="true"';
                            if (!empty($info_userSign['requested_signature'])) {
                                $info_vised .= ' selected="selected" ';
                            }
                            $info_vised .= '>'._SIGNATORY.'</option>';
                            $info_vised .= '</select>';
    
                            $link_vis = 'hourglass-half';
                            $link_vis = 'times';
                            $vised = ' moved vised';
                            $del_vis = '<i class="fa fa-trash-alt" aria-hidden="true" onclick="delVisaUser(this.parentElement.parentElement);" title="'._DELETE.'"></i>';
                        } else {
                            $vised = ' vised';
                            $link_vis = 'check';
                            
                            if (!empty($info_userSign['signatory'])) {
                                $info_vised = '<br/><sub>signé le : '.functions::format_date_db($info_userSign['process_date'], '', '', true).'</sub>';
    
                                $info_vised .= '<br/><select id="signRequest_'.$i.'" style="width:auto;display:none;" '.$isAllAttachementSigned;
                                $info_vised .= ' disabled="disabled" ';
                                $info_vised .= '>';
                                $info_vised .= '<option value="false">'._VISA_USER_SEARCH.'</option>';
                                $info_vised .= '<option value="true"';
                                $info_vised .= ' selected="selected" ';
                                $info_vised .= '>'._SIGNATORY.'</option>';
                                $info_vised .= '</select>';
                            } else {
                                $info_vised = '<br/><sub>visé le : '.functions::format_date_db($info_userSign['process_date'], '', '', true).'</sub>';
    
                                $info_vised .= '<br/><select id="signRequest_'.$i.'" style="width:auto;display:none;" '.$isAllAttachementSigned;
                                $info_vised .= ' disabled="disabled" ';
                                $info_vised .= '>';
                                $info_vised .= '<option value="false" selected="selected">'._VISA_USER_SEARCH.'</option>';
                                $info_vised .= '<option value="true"';
                                $info_vised .= '>'._SIGNATORY.'</option>';
                                $info_vised .= '</select>';
                            }
                        }
                    }
                    //VISA USER LINE CIRCUIT
                    $str .= '<div class="droptarget'.$vised.'" id="visa_'.$i.'" draggable="'.$modif.'">';
                    $str .= '<span class="visaUserStatus">';
                    $str .= '<i class="fa fa-'.$link_vis.'" aria-hidden="true"></i>';
                    $str .= '</span>';
                    $str .= '<span class="visaUserInfo">';
                    $str .= '<sup class="visaUserPos nbResZero">'.$i.'</sup>&nbsp;&nbsp;';
                    $str .= '<i class="fa fa-user fa-2x" aria-hidden="true"></i> '.$info_userSign['lastname'].' '.$info_userSign['firstname'].' <sup class="nbRes">'.$info_userSign['entity_id'].'</sup>';
                    $str .= '&nbsp;&nbsp; <sub><i id="signedUser_'.$i.'" title="au moins un document a été signé par cet utilisateur" class="visaUserSign fa fa-certificate" aria-hidden="true" style="color:#F99830;';
                    if (empty($info_userSign['signatory'])) {
                        $str .= 'visibility:hidden';
                    }
                    $str .= '"></i>'.$info_vised;
                    $str .= '</span>';
                    $str .= '<span class="visaUserAction">';
                    $str .= $del_vis;
                    $str .= '</span>';
                    $str .= '<span class="visaUserConsigne">';
                    $str .= '<input class="userId" type="hidden" value="'.$info_userSign['user_id'].'"/><input class="visaDate" type="hidden" value="'.$info_userSign['process_date'].'"/><input'.$disabled.' class="consigne" type="text" value="'.$info_userSign['process_comment'].'"/>';
                    $str .= '</span>';
                    $str .= '<span id="dropZone">';
                    $str .= $dropZone;
                    $str .= '</span>';
                    $str .= '</div>';
                    ++$i;
                }
            }
        }

        $str .= '</div>';

        if ($bool_modif == true) {
            //SAVE VISA CIRCUIT
            $str .= '<input type="button" name="send" id="send" value="'._SAVE_CHANGES.'" class="button" ';
            $str .= 'onclick="updateVisaWorkflow('.$res_id.');" /> ';

            //SAVE AS MODEL
            $str .= '<input type="button" name="save" id="save" value="Enregistrer comme modèle" class="button" onclick="$(\'modalSaveVisaModel\').style.display = \'block\';" />';
            $str .= '<div id="modalSaveVisaModel" >';
            $str .= '<h3>'._SAVE_POSITION.' '._VISA_WORKFLOW.'</h3><br/>';
            $str .= '<label for="titleModel">'._TITLE.'</label> ';
            $str .= '<input type="text" name="titleModel" id="titleModel"/><br/>';
            $str .= '<input type="button" name="saveModel" id="saveModel" value="'._VALIDATE.'" class="button" onclick="saveVisaWorkflowAsModel();" /> ';
            $str .= '<input type="button" name="cancelModel" id="cancelModel" value="'._CANCEL.'" class="button" onclick="$(\'modalSaveVisaModel\').style.display = \'none\';" />';
            $str .= '</div>';
        }
        $str .= '<script>initDragNDropVisa();</script>';

        return $str;
    }

    /* DOSSIER IMPRESSION */
    public function getJoinedFiles($coll_id, $table, $id, $from_res_attachment = false, $filter_attach_type = 'all')
    {
        $joinedFiles = array();
        $db = new Database();
        if ($from_res_attachment === false) {
            $stmt = $db->query(
                'select res_id, description, subject, title, format, filesize, relation, creation_date from '
                .$table." where res_id = ? and status <> 'DEL'",
                array($id)
            );
        } else {
            require_once 'modules/attachments/attachments_tables.php';
            if ($filter_attach_type == 'all') {
                $stmt = $db->query(
                    'select res_id, description, subject, title, format, filesize, res_id_master, attachment_type, creation_date, typist from '
                    .RES_ATTACHMENTS_TABLE
                    ." where res_id_master = ? and coll_id = ? and attachment_type <> 'converted_pdf' and attachment_type <> 'print_folder' and status <> 'DEL' order by attachment_type, creation_date",
                    array($id, $coll_id)
                );
            } else {
                $stmt = $db->query(
                    'select res_id, res_id_version, description, subject, title, format, filesize, res_id_master, attachment_type, creation_date, typist from '
                    .' res_view_attachments '
                    ." where res_id_master = ? and coll_id = ? and attachment_type = '"
                    .$filter_attach_type."' and status not in ('DEL', 'OBS') order by creation_date",
                    array($id, $coll_id)
                );
            }
        }

        while ($res = $stmt->fetchObject()) {
            $pdf_exist = true;
            if ($from_res_attachment) {
                require_once 'modules/attachments/class/attachments_controler.php';
                $ac = new attachments_controler();
                if ($res->res_id != 0) {
                    $idFile = $res->res_id;
                    $isVersion = false;
                } else {
                    $idFile = $res->res_id_version;
                    $isVersion = true;
                }
                $convertedDocument =  \Convert\models\AdrModel::getConvertedDocumentById(['select' => ['docserver_id', 'path', 'filename'], 'type' => 'PDF', 'resId' => $idFile, 'collId' => 'attachments_coll', 'isVersion' => $isVersion]);
                $viewLink = $_SESSION['config']['businessappurl']
                        .'index.php?display=true&module=attachments&page=view_attachment&res_id_master='
                        .$id.'&id='.$res->res_id;
                
                if (!empty($convertedDocument)) {
                    $docserver = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $convertedDocument['docserver_id'], 'select' => ['path_template']]);
                    $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedDocument['path']) . $convertedDocument['filename'];
                    
                    
                    if (!file_exists($pathToDocument)) {
                        $pdf_exist = false;
                    }
                } else {
                    $pdf_exist = false;
                }
            } else {
                $idFile = $res->res_id;
                $convertedDocument =  \Convert\models\AdrModel::getConvertedDocumentById(['select' => ['docserver_id', 'path', 'filename'], 'type' => 'PDF', 'resId' => $idFile, 'collId' => 'letterbox_coll', 'isVersion' => $isVersion]);
                $viewLink = $_SESSION['config']['businessappurl']
                        .'index.php?display=true&dir=indexing_searching&page=view_resource_controler&id='
                        .$id;
                if (!empty($convertedDocument)) {
                    $docserver = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $convertedDocument['docserver_id'], 'select' => ['path_template']]);
                    $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedDocument['path']) . $convertedDocument['filename'];
                    
                    if (!file_exists($pathToDocument)) {
                        $pdf_exist = false;
                    }
                } else {
                    $pdf_exist = false;
                }
            }
            $label = '';
            //Tile, or subject or description
            if (strlen(trim($res->title)) > 0) {
                $label = $res->title;
            } elseif (strlen(trim($res->subject)) > 0) {
                $label = $res->subject;
            } elseif (strlen(trim($res->description)) > 0) {
                $label = $res->description;
            }

            if (isset($res->attachment_type) && $res->attachment_type != '') {
                $attachment_type = $res->attachment_type;
            } else {
                $attachment_type = '';
            }

            if (isset($res->typist) && $res->typist != '') {
                $typist = $res->typist;
            } else {
                $typist = '';
            }

            if ($pdf_exist == false) {
                $isVersionString = ($isVersion) ? 'true' : 'false';
                $collIdConv = ($from_res_attachment) ? 'attachments_coll' : 'letterbox_coll';
                
                $viewLinkHtml = '<a id="gen_'.$idFile.'" style="cursor:pointer;" title="'._GENERATE_PDF .'" target="_blank" onclick="generatePdf(\''.$idFile.'\',\''.$collIdConv.'\',\''.$isVersionString.'\')">'
                    .'<i id="spinner_'.$idFile.'" class="fa fa-sync-alt fa-2x" title="'._GENERATE_PDF.'"></i>'
                    .'</a>';
            } else {
                $viewLinkHtml = '';
            }
            array_push(
                $joinedFiles,
                array('id' => $idFile, //ID
                      'label' => $label, //Label
                      'format' => $res->format, //Format
                      'filesize' => $res->filesize, //Filesize
                      'creation_date' => $res->creation_date, //Filesize
                      'attachment_type' => $attachment_type, //attachment_type
                      'typist' => $typist, //attachment_type
                      'is_version' => $isVersion,
                      'pdf_exist' => $pdf_exist,
                      'version' => '',
                      'viewLink' => $viewLinkHtml,
                    )
            );
        }

        return $joinedFiles;
    }

    public function showPrintFolder($coll_id, $table, $id)
    {
        require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id']
        .DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR
        .'class_indexing_searching_app.php';
        $is = new indexing_searching_app();

        require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id']
                .DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR
                .'class_users.php';

        $users_tools = new class_users();

        require_once 'core/class/class_request.php';

        $request = new request();

        require_once 'core/class/class_security.php';
        $sec = new security();
        $view = $sec->retrieve_view_from_coll_id($coll_id);
        $stmt = $this->query("select subject, contact_society, category_id from $view where res_id = ?", array($id));
        $res = $stmt->fetchObject();
        $str = '';
        $str .= '<div align="left" class="block">';
        $str .= '<div class="error" id="divErrorPrint" name="divErrorPrint" onclick="this.hide();"></div>';

        $str .= '<p><b>Requérant</b> : '.$res->contact_society.'</p>';
        $str .= '<p><b>'._SUBJECT.'</b> : '.$res->subject.'</p>';
        $str .= '<hr/>';
        $str .= '<form style="width:99%;" name="print_folder_form" id="print_folder_form" action="#" method="post">';
        $str .= '<table style="width:99%;" name="print_folder" id="print_folder" >';
        $str .= '<thead><tr><th style="width:25%;text-align:left;"></th><th style="width:40%;text-align:left;">Titre</th><th style="width:20%;text-align:left;">Rédacteur</th><th style="width:10%;text-align:left;">Date</th><th style="width:5%;text-align:left;"><input title="'._SELECT_ALL.'" id="allPrintFolder" type="checkbox" onclick="selectAllPrintFolder();"></th></tr></thead>';
        $str .= '<tbody>';

        if ($res->category_id != 'outgoing') {
            $str .= '<tr><td><h3>+ Courrier entrant</h3></td><td></td><td></td><td></td><td></td></tr>';
            $joined_files = $this->getJoinedFiles($coll_id, $table, $id, false);
            for ($i = 0; $i < count($joined_files); ++$i) {
                //Get data
                $id_doc = $joined_files[$i]['id'];
                $description = $joined_files[$i]['label'];
                $format = $joined_files[$i]['format'];

                $contact = $users_tools->get_user($joined_files[$i]['typist']);
                $dateFormat = explode(' ', $joined_files[$i]['creation_date']);
                $creation_date = $request->dateformat($dateFormat[0]);
                if ($joined_files[$i]['pdf_exist']) {
                    $check = 'class="check checkPrintFolder" checked="checked"';
                } else {
                    $check = ' disabled title="'._NO_PDF_FILE.'"';
                }
                //Show data
                if ($joined_files[$i]['is_version'] === true) {
                    //Version
                    $version = ' - '._VERSION.' '.$joined_files[$i]['version'];
                    $str .= '<tr><td>'
                            .'</td><td>'.$description.$version.'</td><td>'.$contact['firstname'].' '
                            .$contact['lastname'].'</td><td>'.$creation_date
                            .'</td><td><input id="join_file_'.$id_doc.'_V'.$joined_files[$i]['version']
                            .'" type="checkbox" name="join_version[]"  value="'.$id_doc
                            .'"/>'.$joined_files[$i]['viewLink'].'</td></tr>';
                } else {
                    $str .= '<tr><td></td><td>'.$description.'</td><td>'.$res->contact_society
                            .'</td><td>'.$creation_date.'</td><td><input id="join_file_'
                            .$id_doc.'" type="checkbox" name="join_file[]" value="'.$id_doc.'"  '.$check
                            .'/>'.$joined_files[$i]['viewLink'].'</td></tr>';
                }
            }
        }
        //ATTACHMENTS TYPES LOOP
        foreach ($_SESSION['attachment_types'] as $attachmentTypeId => $attachmentTypeLabel) {
            if ($attachmentTypeId != 'print_folder' && $attachmentTypeId != 'converted_pdf') {
                $joined_files = $this->getJoinedFiles($coll_id, $table, $id, true, $attachmentTypeId);
                if (count($joined_files) > 0) {
                    $str .= '<tr><td><h3>+ '.$attachmentTypeLabel.'</h3></td><td></td><td></td><td></td><td></td></tr>';
                    for ($i = 0; $i < count($joined_files); ++$i) {
                        $id_doc = $joined_files[$i]['id'];
                        $description = $joined_files[$i]['label'];
                        $format = $joined_files[$i]['format'];
                        $contact = $users_tools->get_user($joined_files[$i]['typist']);
                        $dateFormat = explode(' ', $joined_files[$i]['creation_date']);
                        $creation_date = $request->dateformat($dateFormat[0]);
                        if ($joined_files[$i]['pdf_exist']) {
                            $check = 'class="check checkPrintFolder" checked="checked"';
                        } else {
                            $check = ' disabled title="'._NO_PDF_FILE.'"';
                        }
                        if ($joined_files[$i]['is_version'] == true) {
                            $str .= '<tr><td></td><td>'.$description.'</td><td>'.$contact['firstname'].' '
                                .$contact['lastname'].'</td><td>'.$creation_date.'</td><td><input id="join_file_'
                                .$id_doc.'" type="checkbox" name="join_version[]"  value="'.$id_doc.'"  '.$check
                                .'/>'.$joined_files[$i]['viewLink'].'</td></tr>';
                        } else {
                            $str .= '<tr><td></td><td>'.$description.'</td><td>'.$contact['firstname'].' '
                                .$contact['lastname'].'</td><td>'.$creation_date.'</td><td><input id="join_file_'
                                .$id_doc.'" type="checkbox" name="join_attachment[]"  value="'.$id_doc.'"  '.$check
                                .'/>'.$joined_files[$i]['viewLink'].'</td></tr>';
                        }
                    }
                }
            }
        }

        //NOTES
        $core_tools = new core_tools();
        if ($core_tools->is_module_loaded('notes')) {
            require_once 'modules'.DIRECTORY_SEPARATOR.'notes'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'class_modules_tools.php';

            $notes_tools = new notes();
            $user_notes = $notes_tools->getUserNotes($id, $coll_id);
            if (count($user_notes) > 0) {
                $str .= '<tr><td><h3>+ '._NOTES.'</h3></td><td></td><td></td><td></td><td></td></tr>';
                for ($i = 0; $i < count($user_notes); ++$i) {
                    //Get data
                    $idNote = $user_notes[$i]['id'];
                    //$noteShort = $request->cut_string($user_notes[$i]['label'], 50);
                    $noteShort = $request->cut_string(
                        str_replace(array("'", "\r", "\n", '"'), array("'", ' ', ' ', '&quot;'), $user_notes[$i]['label']),
                        50
                    );
                    $noteShort = functions::xssafe($noteShort);
                    $note = $user_notes[$i]['label'];
                    $userArray = $users_tools->get_user($user_notes[$i]['author']);
                    $date = $request->dateformat($user_notes[$i]['date']);

                    $check = ' ';

                    $str .= '<tr><td></td><td>'.$noteShort.'</td><td>'
                                 .$userArray['firstname'].' '.$userArray['lastname']
                                 .'</td><td>'.$date.'</td><td><input id="note_'.$idNote.'" class="checkPrintFolder" type="checkbox" name="notes[]"  value="'
                                 .$idNote.'"  '.$check.'/></td></tr>';
                }
            }
        }

        $str .= '</body>';
        $str .= '</table>';

        $path_to_script = $_SESSION['config']['businessappurl']
        .'index.php?display=true&module=visa&page=printFolder_ajax';

        $str .= '<hr/>';
        $str .= '<input style="margin-left:44%" type="button" name="send" id="send" value="Imprimer" class="button" onclick="printFolder(\''.$id.'\', \''.$coll_id.'\', \'print_folder_form\', \''.$path_to_script.'\');" /> ';
        $str .= '</form>';
        $str .= '</div>';

        return $str;
    }

    public function isAllAttachementSigned($res_id)
    {
        $db = new Database();
        $stmt2 = $db->query("SELECT count(1) as nb from res_view_attachments WHERE in_signature_book = true AND signatory_user_serial_id IS NULL AND status NOT IN ('DEL','OBS','TMP') AND attachment_type NOT IN ('converted_pdf','print_folder','signed_response') AND res_id_master = ?", array($res_id));
        $res2 = $stmt2->fetchObject();
        $stmt3 = $db->query("SELECT count(1) as nb from res_view_attachments WHERE in_signature_book = true AND status NOT IN ('DEL','OBS','TMP') AND attachment_type NOT IN ('converted_pdf','print_folder','signed_response') AND res_id_master = ?", array($res_id));
        $res3 = $stmt3->fetchObject();
        if ($res3->nb == 0) {
            return 'noAttachment';
        } elseif ($res2->nb == 0) {
            return 'yes';
        } else {
            return false;
        }
    }

    public function currentUserSignRequired($res_id)
    {
        $user_id = $this->getCurrentUserStep($res_id);
        if ($_SESSION['user']['UserId'] != $user_id) {
            return 'false';
        }
        $db = new Database();
        $stmt = $db->query("SELECT count(listinstance_id) as nb from listinstance l where l.res_id=? AND l.item_id=? AND l.difflist_type='VISA_CIRCUIT' AND l.requested_signature='true'", array($res_id, $user_id));
        $res = $stmt->fetchObject();
        $stmt2 = $db->query("SELECT count(1) as nb from res_view_attachments r where r.res_id_master=? AND r.signatory_user_serial_id = (select id from users where user_id = ?) AND status NOT IN ('DEL','OBS','TMP') AND attachment_type NOT IN ('converted_pdf','print_folder')", array($res_id, $user_id));
        $res2 = $stmt2->fetchObject();

        if ($res->nb > 0 && $res2->nb == 0) {
            return 'true';
        } else {
            return 'false';
        }
    }
}

abstract class PdfNotes_Abstract extends FPDI
{
    public function LoadData($tab, $collId)
    {
        require_once 'modules/notes/notes_tables.php';
        require_once 'core/class/class_request.php';
        $request = new request();
        // Lecture des lignes du fichier
        $data = array();

        $db2 = new Database();
        foreach ($tab as $id) {
            //Check if ID exists
            $arrayPDO = array();
            if (!empty($collId)) {
                $where = ' and coll_id = :collId';
                $arrayPDO = array_merge($arrayPDO, array(':collId' => $collId));
            }
            $arrayPDO = array_merge($arrayPDO, array(':Id' => $id));
            $stmt2 = $db2->query(
                'SELECT n.identifier, n.date_note, n.user_id, n.note_text, u.lastname, '
                .'u.firstname FROM '.NOTES_TABLE.' n inner join '.USERS_TABLE
                .' u on n.user_id  = u.user_id WHERE n.id = :Id '.$where,
                $arrayPDO
            );

            if ($stmt2->rowCount() > 0) {
                $line = $stmt2->fetchObject();
                $user = $request->show_string($line->lastname.' '.$line->firstname);
                $notes = str_replace('←', '<=', $line->note_text);
                $userId = $line->user_id;
                $date = explode('-', date('d-m-Y', strtotime($line->date_note)));
                $date = $date[0].'/'.$date[1].'/'.$date[2].' '.date('H:i', strtotime($line->date_note));
                $identifier = $line->identifier;
            }
            $data[] = array(utf8_decode($user), $date, utf8_decode($notes));
        }

        return $data;
    }

    public $widths;
    public $aligns;

    public function SetWidths($w)
    {
        $this->widths = $w;
    }

    public function SetAligns($a)
    {
        $this->aligns = $a;
    }

    public function Row($data)
    {
        //Calcule la hauteur de la ligne
        $nb = 0;
        for ($i = 0; $i < count($data); ++$i) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = 5 * $nb;
        $this->CheckPageBreak($h);
        for ($i = 0; $i < count($data); ++$i) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    public function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    public function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n") {
            --$nb;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                ++$i;
                $sep = -1;
                $j = $i;
                $l = 0;
                ++$nl;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        ++$i;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                ++$nl;
            } else {
                ++$i;
            }
        }

        return $nl;
    }
}

abstract class ConcatPdf_Abstract extends FPDI
{
    public $files = array();

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function concat()
    {
        foreach ($this->files as $file) {
            $pageCount = $this->setSourceFile($file);
            for ($pageNo = 1; $pageNo <= $pageCount; ++$pageNo) {
                $tplIdx = $this->ImportPage($pageNo);
                $s = $this->getTemplatesize($tplIdx);
                $this->AddPage($s['w'] > $s['h'] ? 'L' : 'P', array($s['w'], $s['h']));
                $this->useTemplate($tplIdx);
            }
        }
    }
}

/* EXEMPLE TAB VISA_CIRCUIT

Array
(
    [coll_id] => letterbox_coll
    [res_id] => 190
    [difflist_type] => entity_id
    [sign] => Array
        (
            [users] => Array
                (
                    [0] => Array
                        (
                            [user_id] => sgros
                            [lastname] => GROS
                            [firstname] => Sébastien
                            [entity_id] => CHEFCABINET
                            [entity_label] => Chefferie
                            [visible] => Y
                            [viewed] => 0
                            [difflist_type] => VISA_CIRCUIT
                            [process_date] =>
                            [process_comment] =>
                        )

                )

        )

    [visa] => Array
        (
            [users] => Array
                (
                    [0] => Array
                        (
                            [user_id] => sbes
                            [lastname] => BES
                            [firstname] => Stéphanie
                            [entity_id] => CHEFCABINET
                            [entity_label] => Chefferie
                            [visible] => Y
                            [viewed] => 0
                            [difflist_type] => VISA_CIRCUIT
                            [process_date] =>
                            [process_comment] =>
                        )

                    [1] => Array
                        (
                            [user_id] => fbenrabia
                            [lastname] => BENRABIA
                            [firstname] => Fadela
                            [entity_id] => POLESOCIAL
                            [entity_label] => Pôle social
                            [visible] => Y
                            [viewed] => 0
                            [difflist_type] => VISA_CIRCUIT
                            [process_date] =>
                            [process_comment] =>
                        )

                    [2] => Array
                        (
                            [user_id] => bpont
                            [lastname] => PONT
                            [firstname] => Brieuc
                            [entity_id] => POLEAFFAIRESETRANGERES
                            [entity_label] => Pôle affaires étrangères
                            [visible] => Y
                            [viewed] => 0
                            [difflist_type] => VISA_CIRCUIT
                            [process_date] =>
                            [process_comment] =>
                        )

                )

        )

)





<h3>Document</h3><pre>Array
(
    [0] => Array
        (
            [id] => 197
            [label] => 123456
            [format] => pdf
            [filesize] => 46468
            [attachment_type] =>
            [is_version] =>
            [version] =>
        )

)
</pre><h3>Document</h3><pre>Array
(
    [0] => Array
        (
            [id] => 400
            [label] => reponse 1 v5
            [format] => docx
            [filesize] => 36219
            [attachment_type] => response_project
            [is_version] =>
            [version] =>
        )

    [1] => Array
        (
            [id] => 409
            [label] => Nouvelle PJ
            [format] => pdf
            [filesize] => 1204460
            [attachment_type] => simple_attachment
            [is_version] =>
            [version] =>
        )

    [2] => Array
        (
            [id] => 410
            [label] => pj 2
            [format] => pdf
            [filesize] => 361365
            [attachment_type] => simple_attachment
            [is_version] =>
            [version] =>
        )

)

*/
