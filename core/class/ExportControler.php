<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   ExportControler
*
* @author  dev <dev@maarch.org>
* @ingroup core
*/
require_once 'core/class/class_functions.php';
require_once 'core/class/class_history.php';

class ExportControler extends ExportFunctions
{
    public $collection = false;
    public $configuration = false;
    public $delimiter = false;
    public $enclosure = false;

    public $object_export = false;
    public $array_export = [];
    public $pos = 0;

    public function __construct()
    {
        $this->collection = $_SESSION['collection_id_choice'];
        $this->load_configuration();
        $this->retrieve_datas();
        $_SESSION['export']['filename'] = $this->make_csv();
    }

    // Private
    private function load_configuration()
    {
        // Retrieve id to create paths (app & custom)
        $id_app = $_SESSION['config']['app_id'];
        $id_custom = false;
        if (!empty($_SESSION['custom_override_id'])) {
            $id_custom = $_SESSION['custom_override_id'];
        }
        $collection = $this->collection;

        // Retrieve name for export configuration file
        $fileName = 'export.xml';

        // Make paths to xml dir
        $pathToDir_app = 'apps/'.$id_app.'/xml/';
        $pathToDir_custom = 'custom/'.$id_custom.'/'.$pathToDir_app;

        $pathToFile_app = $pathToDir_app.$fileName;
        $pathToFile_custom = $pathToDir_custom.$fileName;

        // Load the configuration file
        if ($id_custom && file_exists($pathToFile_custom)) {
            $configuration = simplexml_load_file($pathToFile_custom);
        } else {
            $configuration = simplexml_load_file($pathToFile_app);
        }

        // Store interesting part of the configuration
        $this->configuration = $configuration->{$collection};
        $this->delimiter = end($configuration->CSVOPTIONS->DELIMITER);
        $this->enclosure = end($configuration->CSVOPTIONS->ENCLOSURE);
        $this->isUtf8 = end($configuration->CSVOPTIONS->IS_UTF8);
    }

    private function retrieve_datas()
    {
        // Retrieve the query
        $query = $this->make_query();

        // Retrieve datas
        $db = new Database();

        $stmt = $db->query($query, $_SESSION['last_select_query_parameters']);

        while ($line = $stmt->fetchObject()) {
            $this->array_export[] = $line->res_id;
        }
    }

    private function make_query()
    {
        // Retrieve the end of last select query on the list
        $endLastQuery = substr(
                $_SESSION['last_select_query'],
                strpos(
                    $_SESSION['last_select_query'],
                    'FROM'
                )
            );

        // Create template for the new query
        $query_template = 'SELECT ';
        $query_template .= '##DATABASE_FIELDS## ';
        $query_template .= $endLastQuery;

        // Retrieve ##DATABASE_FIELDS##
        $fields = $this->configuration->FIELD;
        $i_max = count($fields);
        $database_fields = false;
        for ($i = 0; $i < $i_max; ++$i) {
            $field = $fields[$i];
            $database_fields .= $field->DATABASE_FIELD;
            if ($i != ($i_max - 1)) {
                $database_fields .= ', ';
            }
        }

        // Return query
        return str_replace(
                '##DATABASE_FIELDS##',
                $database_fields,
                $query_template
            );
    }

    private function encode()
    {
        foreach ($this->object_export as $line_name => $line_value) {
            foreach ($line_value as $column_name => $column_value) {
                if ($this->retrieve_encoding($column_value) === false) {
                    $column_value = utf8_encode($column_value);
                }
                if ($this->isUtf8 != 'TRUE') {
                    $column_value = utf8_decode($column_value);
                }
                $column_value = $this->unprotect_string($column_value);
                $this->object_export->{$line_name}->{$column_name} = $column_value;
            }
        }
    }

    private function retrieve_encoding($string)
    {
        return mb_detect_encoding($string, 'UTF-8', true);
    }

    private function unprotect_string($string)
    {
        return str_replace("\'", "'", $string);
    }

    private function make_csv()
    {
        $currentUser = \User\models\UserModel::getByLogin(['login' => $_SESSION['user']['UserId'], 'select' => ['id']]);
        $rawTemplate = \Resource\models\ExportTemplateModel::get(['select' => ['delimiter', 'data'], 'where' => ['user_id = ?', 'format = ?'], 'data' => [$currentUser['id'], 'csv']]);
        if (!empty($rawTemplate[0])) {
            $rawTemplate = $rawTemplate[0];
            $data = json_decode($rawTemplate['data'], true);
        } else {
            $rawTemplate = ['delimiter' => ';'];
            $data = [
                ["value" => "res_id", "label" => "Identifiant GED", "isFunction" => false],
                ["value" => "doc_date", "label" => "Date d'arrivée", "isFunction" => false],
                ["value" => "getInitiatorEntity", "label" => "Entité initiatrice", "isFunction" => true],
                ["value" => "getDestinationEntity", "label" => "Entité traitante", "isFunction" => true],
                ["value" => "getAssignee", "label" => "Destinataire", "isFunction" => true],
                ["value" => "subject", "label" => "Objet", "isFunction" => false],
                ["value" => "type_label", "label" => "Type de courrier", "isFunction" => false],
                ["value" => "getStatus", "label" => "Statut", "isFunction" => true],
                ["value" => "getPriority", "label" => "Priorité", "isFunction" => true],
                ["value" => "getCopies", "label" => "Utilisateurs / Entités en copie", "isFunction" => true],
                ["value" => "getCategory", "label" => "Catégorie", "isFunction" => true],
                ["value" => "getSenders", "label" => "Expéditeurs", "isFunction" => true],
                ["value" => "getRecipients", "label" => "Destinataires", "isFunction" => true],
                ["value" => "getSignatories", "label" => "Signataires", "isFunction" => true],
                ["value" => "getSignatureDates", "label" => "Date de signature", "isFunction" => true],
                ["value" => "getTags", "label" => "Mots clés", "isFunction" => true],
            ];
        }

        $select           = ['res_view_letterbox.res_id'];
        $tableFunction    = [];
        $leftJoinFunction = [];
        $csvHead          = [];
        foreach ($data as $value) {
            $csvHead[] = $value['label'];
            if (empty($value['value'])) {
                continue;
            }
            if ($value['isFunction']) {
                if ($value['value'] == 'getStatus') {
                    $select[] = 'status.label_status AS "status.label_status"';
                    $tableFunction[] = 'status';
                    $leftJoinFunction[] = 'res_view_letterbox.status = status.id';
                } elseif ($value['value'] == 'getPriority') {
                    $select[] = 'priorities.label AS "priorities.label"';
                    $tableFunction[] = 'priorities';
                    $leftJoinFunction[] = 'res_view_letterbox.priority = priorities.id';
                } elseif ($value['value'] == 'getCategory') {
                    $select[] = 'res_view_letterbox.category_id';
                } elseif ($value['value'] == 'getInitiatorEntity') {
                    $select[] = 'enone.short_label AS "enone.short_label"';
                    $tableFunction[] = 'entities enone';
                    $leftJoinFunction[] = 'res_view_letterbox.initiator = enone.entity_id';
                } elseif ($value['value'] == 'getDestinationEntity') {
                    $select[] = 'entwo.short_label AS "entwo.short_label"';
                    $tableFunction[] = 'entities entwo';
                    $leftJoinFunction[] = 'res_view_letterbox.destination = entwo.entity_id';
                } elseif ($value['value'] == 'getDestinationEntityType') {
                    $select[] = 'enthree.entity_type AS "enthree.entity_type"';
                    $tableFunction[] = 'entities enthree';
                    $leftJoinFunction[] = 'res_view_letterbox.destination = enthree.entity_id';
                } elseif ($value['value'] == 'getTypist') {
                    $select[] = 'res_view_letterbox.typist';
                } elseif ($value['value'] == 'getAssignee') {
                    $select[] = 'res_view_letterbox.dest_user';
                } elseif ($value['value'] == 'getDepartment') {
                    $select[] = 'res_view_letterbox.department_number_id';
                }
            } else {
                $select[] = "res_view_letterbox.{$value['value']}";
            }
        }

        $order = 'CASE res_view_letterbox.res_id ';
        foreach ($this->array_export as $key => $resId) {
            $order .= "WHEN {$resId} THEN {$key} ";
        }
        $order .= 'END';

        $aChunkedResources = array_chunk($this->array_export, 10000);
        $resources = [];
        foreach ($aChunkedResources as $chunkedResource) {
            $resourcesTmp = \Resource\models\ResourceListModel::getOnView([
                'select'    => $select,
                'table'     => $tableFunction,
                'leftJoin'  => $leftJoinFunction,
                'where'     => ['res_view_letterbox.res_id in (?)'],
                'data'      => [$chunkedResource],
                'orderBy'   => [$order]
            ]);
            $resources = array_merge($resources, $resourcesTmp);
        }

        $file = \Resource\controllers\ExportController::getCsv(['delimiter' => $rawTemplate['delimiter'], 'data' => $data, 'resources' => $resources, 'chunkedResIds' => $aChunkedResources]);

        $csvName = $_SESSION['user']['UserId'].'-'.md5(date('Y-m-d H:i:s')).'.csv';
        $pathToCsv = $_SESSION['config']['tmppath'].$csvName;
        file_put_contents($pathToCsv, stream_get_contents($file));
        fclose($file);

        return $csvName;
    }
}

class ExportFunctions
{
    /* -------------------------------------------------------------------------
    - Functions
    -
    - All the functions must have only one argument
    - This argument is the name of the column for the header of the export
    ------------------------------------------------------------------------- */
    public function retrieve_copies($libelle, $res_id)
    {
        $db = new Database();

        $collection = $this->collection;

        $query = 'SELECT item_id, ue.entity_id FROM listinstance l LEFT JOIN users_entities ue on l.item_id = ue.user_id WHERE l.res_id = ? AND l.item_mode = ?';
        $stmt = $db->query($query, array($res_id, 'cc'));

        $arr_copy = [];
        while ($result = $stmt->fetchObject()) {
            if (!empty($result->entity_id)) {
                // USER COPY
                $arr_copy[] = "{$result->item_id} : {$result->entity_id}";
            } else {
                // ENTITY COPY
                $arr_copy[] = "{$result->item_id}";
            }
        }

        $copyList = implode(' # ', $arr_copy);

        $this->object_export->{$this->pos}->retrieve_copies = $copyList;
    }

    public function makeLink_detail($libelle, $res_id)
    {
        $link_template = $_SESSION['config']['businessappurl']."index.php?page=details&dir=indexing_searching&id={$res_id}";
        $this->object_export->{$this->pos}->makeLink_detail = $link_template;
    }

    public function get_priority($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT priority FROM res_letterbox WHERE res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $label_priority = $_SESSION['mail_priorities'][$result->priority];

        $this->object_export->{$this->pos}->get_priority = $label_priority;
    }

    public function get_status($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT label_status FROM res_letterbox r LEFT JOIN status s on r.status = s.id WHERE r.res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_status = $result->label_status;
    }

    function get_department($libelle)
    {

        $query_status = "SELECT department_number_id FROM res_view_letterbox WHERE res_id = ##res_id## ";

        $db = new Database();

        $i=0;
        foreach($this->object_export as $line_name => $line_value) {
            if ($i == 0) {
                $line_value->get_department = $libelle;
                $i++;
                continue;
            }

            $res_id = $line_value->res_id;
            $query = str_replace('##res_id##', '?', $query_status);
            $stmt = $db->query($query, array($res_id));

            $result = $stmt->fetchObject();

            $deptName = "";

            require_once("apps".DIRECTORY_SEPARATOR."maarch_entreprise".DIRECTORY_SEPARATOR."department_list.php");
            if ($result->department_number_id <> '') {
                $deptName = $result->department_number_id . ' - ' . $depts[$result->department_number_id];
            }

            $line_value->get_department = $deptName;
        }
    }

    public function get_tags($libelle, $res_id)
    {
        $db = new Database();
        $collection = $this->collection;

        $query = 'SELECT t.tag_label FROM tags t LEFT JOIN resources_tags tr ON t.tag_id = tr.tag_id WHERE t.coll_id = ? AND tr.res_id = ?';
        $stmt = $db->query($query, array($collection, $res_id));

        $arr_tags = [];
        while ($result = $stmt->fetchObject()) {
            $arr_tags[] = $result->tag_label;
        }

        $this->object_export->{$this->pos}->get_tags = implode(' ## ', $arr_tags);
    }

    public function get_contact_type($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT ct.label from contacts_v2 cont LEFT JOIN res_letterbox rlb ON (rlb.exp_contact_id = cont.contact_id OR rlb.dest_contact_id = cont.contact_id) LEFT JOIN contact_types ct ON ct.id = cont.contact_type WHERE rlb.res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_contact_type = $result->label;
    }

    public function get_contact_civility($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT cont.title from contacts_v2 cont LEFT JOIN res_letterbox rlb ON (rlb.exp_contact_id = cont.contact_id OR rlb.dest_contact_id = cont.contact_id) WHERE rlb.res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_contact_civility = $_SESSION['mail_titles'][$result->title];
    }

    public function get_contact_function($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT c.function FROM res_letterbox r LEFT JOIN contacts_v2 c ON c.contact_id = r.dest_contact_id WHERE r.res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_contact_function = $result->function;
    }

    public function get_entity_initiator_short_label($libelle)
    {
        require_once 'modules/entities/class/class_manage_entities.php';
        $db = new Database();
        $entities = new entity();

        $query = 'SELECT initiator FROM res_letterbox r WHERE r.res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_entity_initiator_short_label = $entities->getentityshortlabel($result->initiator);
    }

    public function get_entity_dest_short_label($libelle, $res_id)
    {
        require_once 'modules/entities/class/class_manage_entities.php';
        $db = new Database();
        $entities = new entity();

        $query = 'SELECT destination FROM res_letterbox r WHERE r.res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_entity_dest_short_label = $entities->getentityshortlabel($result->destination);
    }

    public function get_signatory_name($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT DISTINCT u.lastname, u.firstname FROM res_attachments r LEFT JOIN users u ON u.user_id = r.typist WHERE r.attachment_type = ? AND r.status = ? AND r.res_id_master = ?';
        $stmt = $db->query($query, array('signed_response', 'TRA', $res_id));

        $arr_signatory = [];
        while ($result = $stmt->fetchObject()) {
            $arr_signatory[] = strtoupper($result->lastname).' '.ucfirst($result->firstname);
        }

        $this->object_export->{$this->pos}->get_signatory_name = implode(', ', $arr_signatory);
    }

    public function get_signatory_date($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT creation_date FROM res_attachments r WHERE r.attachment_type = ? and r.status = ? and r.res_id_master = ?';
        $stmt = $db->query($query, array('signed_response', 'TRA', $res_id));

        $arr_signatoryDate = [];
        while ($result = $stmt->fetchObject()) {
            $arr_signatoryDate[] = functions::format_date_db($result->creation_date);
        }

        $this->object_export->{$this->pos}->get_signatory_date = implode(', ', $arr_signatoryDate);
    }

    public function get_parent_folder($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT folder_name FROM folders WHERE folders_system_id in ( SELECT f.parent_id FROM res_letterbox r LEFT JOIN folders f ON r.folders_system_id = f.folders_system_id WHERE r.res_id = ?)';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_parent_folder = $result->folder_name;
    }

    public function get_category_label($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT category_id FROM res_letterbox WHERE res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_category_label = $_SESSION['coll_categories']['letterbox_coll'][$result->category_id];
    }
}
