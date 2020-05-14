<?php
/*
*    Copyright 2008-2017 Maarch
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
* @brief Contains the apps tools class
*
*
* @file
* @author Laurent Giovannoni
* @date $date$
* @version $Revision$
* @ingroup apps
*/

require_once 'core/core_tables.php';

abstract class business_app_tools_Abstract extends Database
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
    * Build Maarch business app configuration into sessions vars with a xml
    * configuration file
    */
    public function build_business_app_config()
    {
        // build Maarch business app configuration into sessions vars

        $core = new core_tools();
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps'
            . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
                  . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                  . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR
                  . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR . 'xml'
                  . DIRECTORY_SEPARATOR . 'config.xml';
        } else {
            $path = 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
                  . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
                  . 'config.xml';
        }

        if(file_exists($path)){
            $xmlconfig = simplexml_load_file($path);
        }else{
            $xmlconfig = false;
            exit('<i style="color:red;">Fichier de configuration manquant ...</i><br/><br/>Si un custom est utilis&eacute; assurez-vous que l\'url soit correct');
        }

        if ($xmlconfig <> false) {
            $config = $xmlconfig->CONFIG;

            $uriBeginning = strpos($_SERVER['SCRIPT_NAME'], 'apps');
            $url = $_SESSION['config']['coreurl']
                 .substr($_SERVER['SCRIPT_NAME'], $uriBeginning);
            $_SESSION['config']['businessappurl'] = str_replace(
                'index.php', '', $url
            );

            $i = 0;
            $_SESSION['config']['databases'] = [];
            while (!empty($config->databaseserver[$i])) {
                $_SESSION['config']['databases'][] = [
                    'server' => (string) $config->databaseserver[$i],
                    'port' => (string) $config->databaseserverport[$i],
                    'driver' => (string) $config->databasetype[$i],
                    'database' => (string) $config->databasename[$i],
                    'user' => (string) $config->databaseuser[$i],
                    'password' => (string) $config->databasepassword[$i]
                ];
                $i++;
            }

            $_SESSION['config']['databaseserver'] =
                (string) $config->databaseserver;
            $_SESSION['config']['databaseserverport'] =
                (string) $config->databaseserverport;
            $_SESSION['config']['databasetype'] =
                (string) $config->databasetype;
            $_SESSION['config']['databasename'] =
                (string) $config->databasename;
            $_SESSION['config']['databaseschema'] =
                (string) $config->databaseschema;
            $_SESSION['config']['databaseuser'] =
                (string) $config->databaseuser;
            $_SESSION['config']['databasepassword'] =
                (string) $config->databasepassword;
            $_SESSION['config']['databasesearchlimit'] =
                (string) $config->databasesearchlimit;
            $_SESSION['config']['nblinetoshow'] =
                (string) $config->nblinetoshow;
            $_SESSION['config']['limitcharsearch'] =
                (string) $config->limitcharsearch;
            $_SESSION['config']['lang']                = (string) $config->lang;
            $_SESSION['config']['adminmail']           = (string) $config->adminmail;
            $_SESSION['config']['adminname']           = (string) $config->adminname;
            $_SESSION['config']['debug']               = (string) $config->debug;
            $_SESSION['config']['applicationname']     = (string) $config->applicationname;
            $_SESSION['config']['defaultPage']         = (string) $config->defaultPage;
            $_SESSION['config']['cookietime']          = (string) $config->CookieTime;
            $_SESSION['config']['userdefaultpassword'] = (string) $config->userdefaultpassword;
            $_SESSION['config']['usePHPIDS']           = (string) $config->usePHPIDS;
            if (isset($config->showfooter)) {
                $_SESSION['config']['showfooter'] = (string) $config->showfooter;
            } else {
                $_SESSION['config']['showfooter'] = 'true';
            }
            //$_SESSION['config']['databaseworkspace'] = (string) $config->databaseworkspace;

            $tablename = $xmlconfig->TABLENAME;
            $_SESSION['tablename']['doctypes_first_level']  = (string) $tablename->doctypes_first_level;
            $_SESSION['tablename']['doctypes_second_level'] = (string) $tablename->doctypes_second_level;
            $_SESSION['tablename']['doctypes_indexes']      = (string) $tablename->doctypes_indexes;
            $_SESSION['tablename']['saved_queries']         = (string) $tablename->saved_queries;
            $_SESSION['tablename']['tags']                  = (string) $tablename->tags;
            
            $_SESSION['config']['tmppath'] = \SrcCore\models\CoreConfigModel::getTmpPath();
            
            $i = 0;

            if ( isset($_SESSION['custom_override_id']) && file_exists(
                'custom/' . $_SESSION['custom_override_id'] . '/'
                . $_SESSION['config']['lang'] . '.php'
            )
            ) {
               include_once 'custom/' . $_SESSION['custom_override_id'] . '/'
                . $_SESSION['config']['lang'] . '.php';
            }
            include_once 'apps' . DIRECTORY_SEPARATOR
                    . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR
                    . 'lang' . DIRECTORY_SEPARATOR . $_SESSION['config']['lang']
                    . '.php';
            $_SESSION['collections'] = array();
            $_SESSION['coll_categories'] = array();
            foreach ($xmlconfig->COLLECTION as $col) {
                $tmp = (string) $col->label;
                if (!empty($tmp) && defined($tmp) && constant($tmp) <> NULL) {
                    $tmp = constant($tmp);
                }
                $collId = (string) $col->id;

                if (isset($col->table) && ! empty($col->table)) {
                    $_SESSION['collections'][$i] = array(
                        'id'                   => (string) $col->id,
                        'label'                => (string) $tmp,
                        'table'                => (string) $col->table,
                        'version_table'        => (string) $col->version_table,
                        'view'                 => (string) $col->view,
                        'adr'                  => (string) $col->adr,
                        'index_file'           => (string) $col->index_file,
                        'script_add'           => (string) $col->script_add,
                        'script_search'        => (string) $col->script_search,
                        'script_search_result' => (string) $col->script_search_result,
                        'script_details'       => (string) $col->script_details,
                    );

                    $_SESSION['coll_categories']['letterbox_coll'] = [
                        'incoming' => defined('_INCOMING') ? _INCOMING : '_INCOMING',
                        'outgoing' => defined('_OUTGOING') ? _OUTGOING : '_OUTGOING',
                        'internal' => defined('_INTERNAL') ? _INTERNAL : '_INTERNAL',
                        'ged_doc' => defined('_GED_DOC') ? _GED_DOC : '_GED_DOC'
                    ];

                    $i++;
                } else {
                    $_SESSION['collections'][$i] = array(
                        'id' => (string) $col->id,
                        'label' => (string) $tmp,
                        'view' => (string) $col->view,
                        'adr' => (string) $col->adr,
                        'index_file' => (string) $col->index_file,
                        'script_add' => (string) $col->script_add,
                        'script_search' => (string) $col->script_search,
                        'script_search_result' => (string) $col->script_search_result,
                        'script_details' => (string) $col->script_details,
                    );
                }
            }
            $history = $xmlconfig->HISTORY;
            $_SESSION['history']['usersdel']        = (string) $history->usersdel;
            $_SESSION['history']['usersban']        = (string) $history->usersban;
            $_SESSION['history']['usersadd']        = (string) $history->usersadd;
            $_SESSION['history']['usersup']         = (string) $history->usersup;
            $_SESSION['history']['usersval']        = (string) $history->usersval;
            $_SESSION['history']['doctypesdel']     = (string) $history->doctypesdel;
            $_SESSION['history']['doctypesadd']     = (string) $history->doctypesadd;
            $_SESSION['history']['doctypesup']      = (string) $history->doctypesup;
            $_SESSION['history']['doctypesval']     = (string) $history->doctypesval;
            $_SESSION['history']['doctypesprop']    = (string) $history->doctypesprop;
            $_SESSION['history']['usergroupsdel']   = (string) $history->usergroupsdel;
            $_SESSION['history']['usergroupsban']   = (string) $history->usergroupsban;
            $_SESSION['history']['usergroupsadd']   = (string) $history->usergroupsadd;
            $_SESSION['history']['usergroupsup']    = (string) $history->usergroupsup;
            $_SESSION['history']['usergroupsval']   = (string) $history->usergroupsval;
            $_SESSION['history']['structuredel']    = (string) $history->structuredel;
            $_SESSION['history']['structureadd']    = (string) $history->structureadd;
            $_SESSION['history']['structureup']     = (string) $history->structureup;
            $_SESSION['history']['subfolderdel']    = (string) $history->subfolderdel;
            $_SESSION['history']['subfolderadd']    = (string) $history->subfolderadd;
            $_SESSION['history']['subfolderup']     = (string) $history->subfolderup;
            $_SESSION['history']['resadd']          = (string) $history->resadd;
            $_SESSION['history']['resup']           = (string) $history->resup;
            $_SESSION['history']['resdel']          = (string) $history->resdel;
            $_SESSION['history']['resview']         = (string) $history->resview;
            $_SESSION['history']['userlogin']       = (string) $history->userlogin;
            $_SESSION['history']['userlogout']      = (string) $history->userlogout;
            $_SESSION['history']['actionadd']       = (string) $history->actionadd;
            $_SESSION['history']['actionup']        = (string) $history->actionup;
            $_SESSION['history']['actiondel']       = (string) $history->actiondel;
            $_SESSION['history']['contactadd']      = (string) $history->contactadd;
            $_SESSION['history']['contactup']       = (string) $history->contactup;
            $_SESSION['history']['contactdel']      = (string) $history->contactdel;
            $_SESSION['history']['statusadd']       = (string) $history->statusadd;
            $_SESSION['history']['statusup']        = (string) $history->statusup;
            $_SESSION['history']['statusdel']       = (string) $history->statusdel;
            $_SESSION['history']['docserversadd']   = (string) $history->docserversadd;
            $_SESSION['history']['docserversdel']   = (string) $history->docserversdel;
            $_SESSION['history']['docserversallow'] = (string) $history->docserversallow;
            $_SESSION['history']['docserversban']   = (string) $history->docserversban;
            //$_SESSION['history']['docserversclose'] = (string) $history->docserversclose;
            $_SESSION['history']['docserverslocationsadd']   = (string) $history->docserverslocationsadd;
            $_SESSION['history']['docserverslocationsdel']   = (string) $history->docserverslocationsdel;
            $_SESSION['history']['docserverslocationsallow'] = (string) $history->docserverslocationsallow;
            $_SESSION['history']['docserverslocationsban']   = (string) $history->docserverslocationsban;
            $_SESSION['history']['docserverstypesadd']       = (string) $history->docserverstypesadd;
            $_SESSION['history']['docserverstypesdel']       = (string) $history->docserverstypesdel;
            $_SESSION['history']['docserverstypesallow']     = (string) $history->docserverstypesallow;
            $_SESSION['history']['docserverstypesban']       = (string) $history->docserverstypesban;
            $_SESSION['history_keywords'] = array();
            foreach ($xmlconfig->KEYWORDS as $keyword) {
                $tmp = (string) $keyword->label;
                if (!empty($tmp) && defined($tmp) && constant($tmp) <> NULL) {
                    $tmp = constant($tmp);
                }

                array_push(
                    $_SESSION['history_keywords'],
                    array(
                        'id'    => (string) $keyword->id,
                        'label' => $tmp,
                    )
                );
            }

            $i = 0;
            foreach ($xmlconfig->MODULES as $modules) {

                $_SESSION['modules'][$i] = array(
                    'moduleid' => (string) $modules->moduleid,
                    //,"comment" => (string) $MODULES->comment
                );
                $i ++;
            }
            $this->_loadActionsPages();
        }

        if ($_SESSION['config']['usePHPIDS'] == 'true') {
            $this->_loadPHPIDSExludes();
        }
    }

    /**
    * Load actions in session
    */
    public function _loadActionsPages()
    {
        if (isset($_SESSION['config']['corepath'])
            && isset($_SESSION['config']['app_id'])
            && isset($_SESSION['config']['lang']
        )
        ) {
            $core = new core_tools();
            if (file_exists(
                $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'core'
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
                . 'actions_pages.xml'
            )
            ) {
                $path = $_SESSION['config']['corepath'] . 'custom'
                      . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                      . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR
                      . 'xml' . DIRECTORY_SEPARATOR . 'actions_pages.xml';
            } else {
                $path = 'core' . DIRECTORY_SEPARATOR . 'xml'
                      . DIRECTORY_SEPARATOR . 'actions_pages.xml';
            }
            $xmlfile = simplexml_load_file($path);
            $langPath = 'apps' . DIRECTORY_SEPARATOR
                       . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR
                       . 'lang' . DIRECTORY_SEPARATOR
                       . $_SESSION['config']['lang'] . '.php';

            $i = 0;
            foreach ($xmlfile->ACTIONPAGE as $actionPage) {
                $label = (string) $actionPage->LABEL;
                if (!empty($label) && defined($label)
                    && constant($label) <> NULL
                ) {
                    $label = constant($label);
                }
                $keyword = '';
                if (isset($actionPage->KEYWORD)
                    && ! empty($actionPage->KEYWORD)
                ) {
                    $keyword = (string) $actionPage->KEYWORD;
                }
                $createFlag = 'N';
                if (isset($actionPage->FLAG_CREATE)
                    && (string) $actionPage->FLAG_CREATE == 'true'
                ) {
                    $createFlag = 'Y';
                }
                $_SESSION['actions_pages'][$i] = array(
                    'ID'          => (string) $actionPage->ID,
                    'LABEL'       => $label,
                    'NAME'        => (string) $actionPage->NAME,
                    'ORIGIN'      => (string) $actionPage->ORIGIN,
                    'MODULE'      => (string) $actionPage->MODULE,
                    'KEYWORD'     => $keyword,
                    'FLAG_CREATE' => $createFlag,
                );
                $i++;
            }
        }

        //LOAD actions in other modules
        foreach ($_SESSION['modules'] as $key => $value) {
          
            if (file_exists(
                $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . 'modules' . DIRECTORY_SEPARATOR . $value['moduleid']
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
                . 'actions_pages.xml'
            )
            ) {
                $path = $_SESSION['config']['corepath'] . 'custom'
                      . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                      . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $value['moduleid'] . DIRECTORY_SEPARATOR
                      . 'xml' . DIRECTORY_SEPARATOR . 'actions_pages.xml';

            } else {
                $path = 'modules' . DIRECTORY_SEPARATOR . $value['moduleid'] . DIRECTORY_SEPARATOR . 'xml'
                      . DIRECTORY_SEPARATOR . 'actions_pages.xml';
            }

            if (file_exists(
                $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $value['moduleid']
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
                . 'actions_pages.xml'
            ) || file_exists(
                $_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . $value['moduleid']
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
                . 'actions_pages.xml'
            )

            ) {
            $xmlfile = simplexml_load_file($path);

            $langPath = 'modules' . DIRECTORY_SEPARATOR . $value['moduleid'] . DIRECTORY_SEPARATOR
                      . 'lang' . DIRECTORY_SEPARATOR. $_SESSION['config']['lang'] . '.php';

            include_once($langPath);
            foreach ($xmlfile->ACTIONPAGE as $actionPage) {
                $label = (string) $actionPage->LABEL;
                if (!empty($label) && defined($label)
                    && constant($label) <> NULL
                ) {
                    $label = constant($label);
                }
                $keyword = '';
                if (isset($actionPage->KEYWORD)
                    && ! empty($actionPage->KEYWORD)
                ) {
                    $keyword = (string) $actionPage->KEYWORD;
                }
                $createFlag = 'N';
                if (isset($actionPage->FLAG_CREATE)
                    && (string) $actionPage->FLAG_CREATE == 'true'
                ) {
                    $createFlag = 'Y';
                }
                $_SESSION['actions_pages'][$i] = array(
                    'ID'          => (string) $actionPage->ID,
                    'LABEL'       => $label,
                    'NAME'        => (string) $actionPage->NAME,
                    'ORIGIN'      => (string) $actionPage->ORIGIN,
                    'MODULE'      => (string) $actionPage->MODULE,
                    'KEYWORD'     => $keyword,
                    'FLAG_CREATE' => $createFlag
                );
                $i++;
            }
        }

        }
    }

    public static function _loadEntrepriseVar()
    {
        $core = new core_tools();
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR
            . 'apps'.DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
            . 'entreprise.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
                . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR
                . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR . 'xml'
                . DIRECTORY_SEPARATOR . 'entreprise.xml';
        } else {
            $path = 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
                . 'entreprise.xml';
        }
        $xmlfile = simplexml_load_file($path);
        $langPath = 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
            . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR
            . $_SESSION['config']['lang'] . '.php';

        $_SESSION['attachment_types'] = array();
        $_SESSION['attachment_types_with_chrono'] = array();
        $_SESSION['attachment_types_show'] = array();
        $_SESSION['attachment_types_with_process'] = array();
        $_SESSION['attachment_types_with_delay'] = array();
        $_SESSION['attachment_types_reconciliation'] = array(); //NCH01
        $attachmentTypes = $xmlfile->attachment_types;
        if (count($attachmentTypes) > 0) {
            foreach ($attachmentTypes->type as $type ) {
                $label = (string) $type->label;
                $with_chrono = (string) $type['with_chrono'];
                $get_chrono = (string) $type['get_chrono'];
                $attach_in_mail = (string) $type['attach_in_mail'];
                $show_attachment_type = (string) $type['show'];
                $delay = (string) $type['with_delay'];
                $select_in_reconciliation = (string) $type['select_in_reconciliation']; //NCH01
                $process = (string) $type->process_mode;
                if (!empty($label) && defined($label)
                    && constant($label) <> NULL
                ) {
                    $label = constant($label);
                }

                $array_get_chrono = explode(',', $get_chrono);
                $_SESSION['attachment_types'][(string) $type->id] = $label;
                $_SESSION['attachment_types_with_chrono'][(string) $type->id] = $with_chrono;
                $_SESSION['attachment_types_show'][(string) $type->id] = $show_attachment_type;
                $_SESSION['attachment_types_get_chrono'][(string) $type->id] = $array_get_chrono;
                $_SESSION['attachment_types_attach_in_mail'][(string) $type->id] = $attach_in_mail;
                $_SESSION['attachment_types_with_process'][(string) $type->id] = $process;
                $_SESSION['attachment_types_with_delay'][(string) $type->id] = $delay;
                $_SESSION['attachment_types_reconciliation'][(string) $type->id] = $select_in_reconciliation; //NCH01
            }
        }

        $_SESSION['mail_priorities']            = [];
        $_SESSION['mail_priorities_id']         = [];
        $_SESSION['mail_priorities_attribute']  = [];
        $_SESSION['mail_priorities_wdays']      = [];
        $_SESSION['mail_priorities_color']      = [];
        $_SESSION['default_mail_priority']      = 0;

        $priorities = \Priority\models\PriorityModel::get(['orderBy' => ['"order" NULLS LAST']]);
        $i = 0;
        foreach ($priorities as $priority) {
            $_SESSION['mail_priorities'][$i] = $priority['label'];
            $_SESSION['mail_priorities_id'][$i] = $priority['id'];
            $_SESSION['mail_priorities_attribute'][$i] = ($priority['delays'] == null ? 'false' : $priority['delays']);
            $_SESSION['mail_priorities_wdays'][$i] = ($priority['working_days'] ? 'true' : 'false');
            $_SESSION['mail_priorities_color'][$i] = $priority['color'];
            if ($priority['default_priority']) {
                $_SESSION['default_mail_priority'] = $i;
            }
            $i++;
        }

        $mailPriorities = $xmlfile->priorities;
        if (count($mailPriorities) > 0) {
            $_SESSION['default_sve_priority'] = (string) $mailPriorities->default_sve_priority;
        }

        $contact_check = $xmlfile->contact_check;
        if (count($contact_check) > 0) {
            $_SESSION['check_days_before'] = (string) $contact_check->check_days_before;
        }

        $_SESSION['mail_titles'] = array();
        $mailTitles = $xmlfile->titles;
        if (count($mailTitles) > 0) {
            $i = 0;
            foreach ($mailTitles->title as $title ) {
                $label = (string) $title->label;
                if (!empty($label) && defined($label)
                    && constant($label) <> NULL
                ) {
                    $label = constant($label);
                }
                $_SESSION['mail_titles'][(string)$title->id] = $label;
            }
            $_SESSION['default_mail_title'] = (string) $mailTitles->default_title;
        }
        
    }

    public function compare_base_version($xmlVersionBase)
    {
        // Compare version value beetwen version base xml file and version base
        // value in the database
        $xmlBase = simplexml_load_file($xmlVersionBase);
        //Find value in the xml database_version tag
        if ($xmlBase) {
            $versions = explode('.', (string)$xmlBase->version);
            $_SESSION['maarch_entreprise']['xml_versionbase'] = "{$versions[0]}.{$versions[1]}";
        } else {
            $_SESSION['maarch_entreprise']['xml_versionbase'] = 'none';
        }
        $checkBase = new Database();
        $query = "SELECT param_value_string FROM " . PARAM_TABLE
               . " WHERE id = 'database_version'";

        $stmt = $checkBase->query($query); //Find value in parameters table on database
        if ($stmt->rowCount() == 0) {
            $_SESSION['maarch_entreprise']['database_version'] = "none";
        } else {
            $vbg = $stmt->fetchObject();
            $_SESSION['maarch_entreprise']['database_version'] = $vbg->param_value_string;
        }
        //If this two parameters is not find, this is the end of this function
        if ($_SESSION['maarch_entreprise']['xml_versionbase'] <> 'none' ) {
            if (($_SESSION['maarch_entreprise']['xml_versionbase'] <> $_SESSION['maarch_entreprise']['database_version'])
                || ($_SESSION['maarch_entreprise']['database_version'] == 'none')
            ) {
                $_SESSION['error'] .= _VERSION_BASE_AND_XML_BASEVERSION_NOT_MATCH. "(".$_SESSION['maarch_entreprise']['xml_versionbase']."/".$_SESSION['maarch_entreprise']['database_version'].")";
            }
        }
    }

    public function load_features($xmlFeatures)
    {
        $_SESSION['features'] = array();
        //Defines all features by  default at 'false'
        $_SESSION['features']['search_notes']                    = "false";
        $_SESSION['features']['show_types_tree']                 = "false";
        $_SESSION['features']['watermark']                       = array();
        $_SESSION['features']['watermark']['enabled']            = "false";
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR
            . $xmlFeatures
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
                  . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                  . DIRECTORY_SEPARATOR . $xmlFeatures;
        } else {
            $path = $xmlFeatures;
        }

        $xmlfeatures = simplexml_load_file($path);
        if ($xmlfeatures) {
            $feats = $xmlfeatures->FEATURES;
            $_SESSION['features']['search_notes']                    = (string) $feats->search_notes;
            $_SESSION['features']['show_types_tree']                 = (string) $feats->show_types_tree;
            $watermark = $feats->watermark;
            $_SESSION['features']['watermark']['enabled']    = (string) $watermark->enabled;
            $_SESSION['features']['watermark']['text']       = (string) $watermark->text;
            $_SESSION['features']['watermark']['position']   = (string) $watermark->position;
            $_SESSION['features']['watermark']['font']       = (string) $watermark->font;
            $_SESSION['features']['watermark']['text_color'] = (string) $watermark->text_color;
            $_SESSION['features']['type_calendar']           = (string) $feats->type_calendar;
            $send_to_contact_with_mandatory_attachment       = (string) $feats->send_to_contact_with_mandatory_attachment;
            if(strtoupper($send_to_contact_with_mandatory_attachment) == 'TRUE'){
                $_SESSION['features']['send_to_contact_with_mandatory_attachment'] = TRUE;
            }elseif(strtoupper($send_to_contact_with_mandatory_attachment) == 'FALSE'){
                $_SESSION['features']['send_to_contact_with_mandatory_attachment'] = FALSE;
            }
            if(!empty($feats->notes_in_print_page->label)){
                foreach ($feats->notes_in_print_page->label as $value) {
                    $_SESSION['features']['notes_in_print_page'][] = (string) $value;
                }
            }
        }
    }

    /**
    * Loads app specific vars in session
    *
    */
    public function load_app_var_session($userData = '')
    {
        $this->_loadEntrepriseVar();
        $this->load_features(
            'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'features.xml'
        );
        
        $this->_loadListsConfig();
    }

    protected function _loadListsConfig() {

        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps'
            . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
            . 'lists_parameters.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
                  . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                  . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR
                  . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR .'xml'
                  . DIRECTORY_SEPARATOR . 'lists_parameters.xml';
        } else {
            $path = 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
            . 'lists_parameters.xml';
        }
        $xmlfile = simplexml_load_file($path);
        
        //Load filters
        $_SESSION['filters'] = array();
        foreach ($xmlfile->FILTERS as $filtersObject) {
        
            foreach ($filtersObject as $filter) {
            
                $desc = (string) $filter->LABEL;
                if (!empty($desc) && defined($desc) && constant($desc) <> NULL) {
                    $desc = constant($desc);
                }
                $id = (string) $filter->ID;
                $enabled = (string) $filter->ENABLED;
                if( trim($enabled) == 'true') {
                    $_SESSION['filters'][$id] = array(
                        'ID'      => $id,
                        'LABEL'   => $desc,
                        'ENABLED' => $enabled,
                        'VALUE'   => '',
                        'CLAUSE'  => ''
                    );
                }
            }
        }
        
        //Init
        $_SESSION['html_templates'] = array();
        
        //Default list (no template)
        $_SESSION['html_templates']['none'] = array(
            'ID'        =>  'none',
            'LABEL'     =>  _DOCUMENTS_LIST,
            'IMG'       =>  'fa fa-list-alt fa-2x',
            'ENABLED'   =>  'true',
            'PATH'      =>  '',
            'GOTOLIST'  =>  ''
        );
        
        //Load templates
        foreach ($xmlfile->TEMPLATES as $templatesObject) {
        
            foreach ($templatesObject as $template) {
            
                $desc = (string) $template->LABEL;
                if (!empty($desc) && defined($desc) && constant($desc) <> NULL) {
                    $desc = constant($desc);
                }
                $id         = (string) $template->ID;
                $enabled    = (string) $template->ENABLED;
                $name       = (string) $template->NAME;
                $origin     = (string) $template->ORIGIN;
                $module     = (string) $template->MODULE;
                $listObject = $template->GOTOLIST;

                $pathToList = '';
                if (!empty($listObject)) {
                    foreach ($listObject as $list) {
                        $listId = (string) $list->ID;
                        $listName = (string) $list->NAME;
                        $listOrigin = (string) $list->ORIGIN;
                        $listModule = (string) $list->MODULE;
                        
                        // The page is in the apps
                        if (strtoupper($listOrigin) == 'APPS'
                        ) {
                            if( file_exists(
                                $_SESSION['config']['corepath'].'custom' . DIRECTORY_SEPARATOR 
                                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps' 
                                . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id'] 
                                . DIRECTORY_SEPARATOR . $listName . '.php'
                            ) ||
                                file_exists('apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
                                . DIRECTORY_SEPARATOR . $listName.'.php'
                            )
                            )
                            {
                                $pathToList = $_SESSION['config']['businessappurl']
                                            . 'index.php?display=true&page='. $listName;
                            }
                        } else if (strtoupper(
                            $listOrigin
                        ) == "MODULE"
                        ) { 
                            // The page is in a module
                            $core = new core_tools();
                            // Error : The module name is empty or the module is not loaded
                            if (empty($listModule)
                                || ! $core->is_module_loaded(
                                    $listModule
                                )
                            ) {
                                $pathToList = '';
                            } else {
                                if (
                                    file_exists(
                                    $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                                    . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
                                    . DIRECTORY_SEPARATOR . $listModule . DIRECTORY_SEPARATOR . $listName . '.php'
                                ) ||
                                    file_exists('modules' . DIRECTORY_SEPARATOR . $listModule
                                        . DIRECTORY_SEPARATOR . $listName . '.php'
                                )
                                ) {
                                    $pathToList = $_SESSION['config']['businessappurl']
                                        . 'index.php?display=true&page=' . $listName
                                        . '&module=' . $listModule;
                                }
                            }
                        }
                    }
                }
                
                //Path to template
                if ($origin == "apps") { //Origin apps
                    if(file_exists(
                        $_SESSION['config']['corepath'].'custom' . DIRECTORY_SEPARATOR 
                        . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps' 
                        . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id'] 
                        . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR 
                        . $name . '.html'
                    )
                    ) {
                        $path = $_SESSION['config']['corepath'] . 'custom' 
                        . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                        . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR
                        . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR
                        . "template" . DIRECTORY_SEPARATOR . $name . '.html';
                    } else {
                        $path = 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
                        . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR . $name.'.html';
                    }
                } else if ($origin == "module") { //Origin module
                    if (file_exists(
                        $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                        . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
                        . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'template'
                        . DIRECTORY_SEPARATOR .  $name . '.html'
                    )
                    ) {
                        $path = $_SESSION['config']['corepath'] . 'custom'
                        . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                        . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
                        . $module . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR
                        .  $name . '.html';
                    } else {
                        $path = 'modules' . DIRECTORY_SEPARATOR . $module
                        . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR
                        .  $name . '.html';
                    }
                }
                
                //Values of html_templates array
                if( trim($enabled) == 'true') {
                    $_SESSION['html_templates'][$id] = array(
                        'ID'       => $id,
                        'LABEL'    => $desc,
                        'IMG'      => (string) $template->IMG,
                        'ENABLED'  => $enabled,
                        'PATH'     => $path,
                        'GOTOLIST' => $pathToList
                    );
                }
            }
        }
    }

    /**
    * Load phpids excludes in session
    */
    public function _loadPHPIDSExludes()
    {
        if (isset($_SESSION['config']['corepath'])
            && isset($_SESSION['config']['app_id'])
        ) {
            $core = new core_tools();
            if (file_exists(
                $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps'
                . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR
                . 'tools' . DIRECTORY_SEPARATOR . 'phpids' . DIRECTORY_SEPARATOR
                . 'lib' . DIRECTORY_SEPARATOR . 'IDS' . DIRECTORY_SEPARATOR
                . 'maarch_exclude.xml'
            )
            ) {
                $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                        . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps'
                        . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR
                        . 'tools' . DIRECTORY_SEPARATOR . 'phpids' . DIRECTORY_SEPARATOR
                        . 'lib' . DIRECTORY_SEPARATOR . 'IDS' . DIRECTORY_SEPARATOR
                        . 'maarch_exclude.xml';
            } else {
                $path = 'apps'
                        . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR
                        . 'tools' . DIRECTORY_SEPARATOR . 'phpids' . DIRECTORY_SEPARATOR
                        . 'lib' . DIRECTORY_SEPARATOR . 'IDS' . DIRECTORY_SEPARATOR
                        . 'maarch_exclude.xml';
            }
            $xmlfile = simplexml_load_file($path);
            $_SESSION['PHPIDS_EXCLUDES'] = array();
            foreach ($xmlfile->exclude as $exclude) {
                array_push(
                    $_SESSION['PHPIDS_EXCLUDES'], 
                    array(
                        'TARGET' => (string) $exclude->target,
                        'PAGE'   => (string) $exclude->page,
                    )
                );
            }
        }
    }
}
