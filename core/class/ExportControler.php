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

class EmptyObject
{
    public function __construct()
    {
        $test = '';
    }
}

class ExportControler extends ExportFunctions
{
    public $collection = false;
    public $configuration = false;
    public $delimiter = false;
    public $enclosure = false;

    public $object_export = false;
    public $array_export = false;
    public $pos = 0;

    public function __construct()
    {
        $this->collection = $_SESSION['collection_id_choice'];
        $this->load_configuration();
        $this->retrieve_datas();
        $this->insert_emptyColumns();
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

        $this->object_export = new EmptyObject();
        while ($line = $stmt->fetchObject()) {
            // STEP 1 : ADD HEADER
            if ($this->pos == 0) {
                $this->object_export->{$this->pos} = $this->retrieve_header();
                $this->pos = 1;
            }

            // STEP 2 : FORMAT DATE
            if ($line->doc_date) {
                $line->doc_date = substr($line->doc_date, 0, 10);
            }
            if ($line->admission_date) {
                $line->admission_date = substr($line->admission_date, 0, 10);
            }
            if ($line->process_limit_date) {
                $line->process_limit_date = substr($line->process_limit_date, 0, 10);
            }
            $this->object_export->{$this->pos} = $line;

            //STEP 3 : EXPORT OTHER DATA
            $this->process_functions($line->res_id);

            ++$this->pos;
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

    private function retrieve_header()
    {
        // HEADER DATA
        $return = new StdClass();
        $fields = $this->configuration->FIELD;
        $i_max = count($fields);
        for ($i = 0; $i < $i_max; ++$i) {
            $field = $fields[$i];
            $database_field = end(explode('.', $field->DATABASE_FIELD));
            $return->{$database_field} = end($field->LIBELLE);
        }

        // HEADER FOR CUSTOM DATA
        $fields = $this->configuration->FUNCTIONS;
        for ($i = 0; $i < count($fields->FUNCTION); ++$i) {
            $field = $fields->FUNCTION[$i];

            $database_field = end($field->CALL);
            $return->{$database_field} = end($field->LIBELLE);
        }

        return $return;
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

    private function process_functions($res_id)
    {
        $functions = $this->configuration->FUNCTIONS->FUNCTION;
        $functions_max = count($functions);
        for ($i = 0; $i < $functions_max; ++$i) {
            $function = $functions[$i];
            $call = $function->CALL;
            if (method_exists($this, $call)) {
                eval('$this->'.$call.'(\''.$function->LIBELLE.'\',\''.$res_id.'\');');
            }
        }
    }

    private function insert_emptyColumns()
    {
        $emptys = $this->configuration->EMPTYS->EMPTY;
        $emptys_max = count($emptys);
        for ($i = 0; $i < $emptys_max; ++$i) {
            $empty = $emptys[$i];
            $libelle = end($empty->LIBELLE);
            $colname = end($empty->COLNAME);
            foreach ($this->object_export as $line_name => $line_value) {
                $line_value->{$colname} = $libelle;
                break;
            }
        }
    }

    private function make_csv()
    {
        // Manage encoding
        $this->encode();

        // Object2Array
        $functions = new functions();
        $this->array_export = $functions->object2array($this->object_export);

        // Create temp path
        do {
            $csvName = $_SESSION['user']['UserId'].'-'.md5(date('Y-m-d H:i:s')).'.csv';
            if (isset($pathToCsv) && !empty($pathToCsv)) {
                $csvName = $_SESSION['user']['UserId'].'-'.md5($pathToCsv).'.csv';
            }
            $pathToCsv = $_SESSION['config']['tmppath'].$csvName;
        } while (file_exists($pathToCsv));

        // Write csv
        $csv = fopen($pathToCsv, 'a+');
        foreach ($this->array_export as $line) {
            fputcsv($csv, $line, $this->delimiter, $this->enclosure);
        }
        fclose($csv);

        // Return csvName
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

        $query = 'SELECT item_id, ue.entity_id FROM listinstance l LEFT JOIN users_entities ue on l.item_id = ue.user_id WHERE l.res_id = ? AND l.coll_id = ? AND l.item_mode = ?';
        $stmt = $db->query($query, array($res_id, $this->collection, 'cc'));

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

    public function get_tags($libelle, $res_id)
    {
        $db = new Database();
        $collection = $this->collection;

        $query = 'SELECT t.tag_label FROM tags t LEFT JOIN tag_res tr ON t.tag_id = tr.tag_id WHERE t.coll_id = ? AND tr.res_id = ?';
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

        $query = 'SELECT ct.label from contacts_v2 cont LEFT JOIN mlb_coll_ext mlb ON (mlb.exp_contact_id = cont.contact_id OR mlb.dest_contact_id = cont.contact_id) LEFT JOIN contact_types ct ON ct.id = cont.contact_type WHERE mlb.res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_contact_type = $result->label;
    }

    public function get_contact_civility($libelle, $res_id)
    {
        $db = new Database();

        $query = 'SELECT cont.title from contacts_v2 cont LEFT JOIN mlb_coll_ext mlb ON (mlb.exp_contact_id = cont.contact_id OR mlb.dest_contact_id = cont.contact_id) WHERE mlb.res_id = ?';
        $stmt = $db->query($query, array($res_id));
        $result = $stmt->fetchObject();

        $this->object_export->{$this->pos}->get_contact_civility = $_SESSION['mail_titles'][$result->title];
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

        $query = 'SELECT DISTINCT u.lastname, u.firstname FROM res_view_attachments r LEFT JOIN users u ON u.user_id = r.typist WHERE r.attachment_type = ? AND r.status = ? AND r.res_id_master = ?';
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

        $query = 'SELECT creation_date FROM res_view_attachments r WHERE r.attachment_type = ? and r.status = ? and r.res_id_master = ?';
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
}
