<?php

/**
*   @copyright 2017 Maarch
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

require_once 'core' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'Abstract.php';
require_once 'core' . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'CoreConfig.php';
require_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_core_tools.php';

/**
 * Service de configuration du core
 */
class Core_CoreConfigAbstract_Service extends Core_Abstract_Service {
	/**
	 * Get TableName from the core/xml/config.xml file to load Maarch core configuration into session 
	 * @param  string $pathtoxmlcore path to the xml core config file
	 * @return array the list of TableName
	 */
	private static function getTableName($pathtoxmlcore)
	{
		$xmlconfig = simplexml_load_file($pathtoxmlcore);
		$TABLENAME = $xmlconfig->TABLENAME ;
		return $TABLENAME;
	}

     /**
     * Load Maarch core configuration into sessions vars from the core/xml/config.xml file
     * @param  string $pathtoxmlcore path to the xml core config file
     */
    public static function buildCoreConfig($pathtoxmlcore)
    {
        // Get TableName from xml file
        $TABLENAME = SELF::getTableName($pathtoxmlcore);

        // Loads  core tables into session ($_SESSION['tablename'] array)
        $_SESSION['tablename']['actions']            = (string) $TABLENAME->actions;
        $_SESSION['tablename']['authors']            = (string) $TABLENAME->authors;
        $_SESSION['tablename']['docservers']         = (string) $TABLENAME->docservers;
        $_SESSION['tablename']['doctypes']           = (string) $TABLENAME->doctypes;
        $_SESSION['tablename']['history']            = (string) $TABLENAME->history;
        $_SESSION['tablename']['history_batch']      = (string) $TABLENAME->history_batch;
        $_SESSION['tablename']['param']              = (string) $TABLENAME->param;
        $_SESSION['tablename']['security']           = (string) $TABLENAME->security;
        $_SESSION['tablename']['status']             = (string) $TABLENAME->status;
        $_SESSION['tablename']['usergroups']         = (string) $TABLENAME->usergroups;
        $_SESSION['tablename']['usergroup_content']  = (string) $TABLENAME->usergroupcontent;
        $_SESSION['tablename']['usergroup_services'] = (string) $TABLENAME->usergroups_services;
        $_SESSION['tablename']['users']              = (string) $TABLENAME->users;
    }

    /**
    * Build Maarch business app configuration into sessions vars with a xml
    * configuration file
    */
    public static function buildBusinessAppConfig()
    {
        // build Maarch business app configuration into sessions vars

        $core = new core_tools();

       // $_SESSION['config']['app_id']='maarch_entreprise';
        require_once 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR  . 'class' . DIRECTORY_SEPARATOR . 'class_business_app_tools.php';
        $businessAppTools = new business_app_tools();

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
        $xmlconfig = @simplexml_load_file($path);
        if ( ! $xmlconfig ) {
            throw new \Exception('conf not-found : '.$path);
        }
        if ($xmlconfig <> false) {
            $config = $xmlconfig->CONFIG;
            $uriBeginning = strpos($_SERVER['SCRIPT_NAME'], 'apps');
            if (empty($uriBeginning)) {
                $_SESSION['config']['businessappurl'] = $_SESSION['config']['coreurl']
                    . 'apps/maarch_entreprise/';
            } else {
                $url = $_SESSION['config']['coreurl']
                    .substr($_SERVER['SCRIPT_NAME'], $uriBeginning);
                $_SESSION['config']['businessappurl'] = str_replace(
                    'index.php', '', $url
                );    
            }
            
            //echo $_SESSION['config']['businessappurl'];exit;

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
            $_SESSION['config']['exportdirectory']     = (string) $config->exportdirectory;
            $_SESSION['config']['cookietime']          = (string) $config->CookieTime;
            $_SESSION['config']['ldap']                = (string) $config->ldap;
            $_SESSION['config']['userdefaultpassword'] = (string) $config->userdefaultpassword;
            $_SESSION['config']['usePDO']              = (string) $config->usePDO;
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
            $_SESSION['tablename']['mlb_doctype_ext']       = (string) $tablename->mlb_doctype_ext;
            $_SESSION['tablename']['doctypes_indexes']      = (string) $tablename->doctypes_indexes;
            $_SESSION['tablename']['saved_queries']         = (string) $tablename->saved_queries;
            $_SESSION['tablename']['contacts_v2']           = (string) $tablename->contacts_v2;
            $_SESSION['tablename']['contact_types']         = (string) $tablename->contact_types;
            $_SESSION['tablename']['contact_purposes']      = (string) $tablename->contact_purposes;
            $_SESSION['tablename']['contact_addresses']     = (string) $tablename->contact_addresses;
            $_SESSION['tablename']['tags']                  = (string) $tablename->tags;
            
            $_SESSION['config']['tmppath'] = $_SESSION['config']['corepath'] . 'apps' 
                . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
                . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
            
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
                $extensions = $col->extensions;
                $collId = (string) $col->id;
                $tab = array();

                if ($extensions->count()) {
                    $extensionTables = $extensions->table;
                    if ($extensionTables->count() > 0) {
                        foreach ($extensions->table as $table) {
                            if (strlen($extensionTables) > 0) {
                                array_push($tab, (string) $table);
                            }
                        }
                    }
                }
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
                        'path_to_lucene_index' => (string) $col->path_to_lucene_index,
                        'extensions'           => $tab,
                    );
                    
                    $categories = $col->categories;
                    
                    if (count($categories) > 0) {
                        foreach ($categories->category as $cat) {
                            $label = (string) $cat->label;
                            if (!empty($label) && defined($label)
                                && constant($label) <> NULL
                             ) {
                                $label = constant($label);
                            }
                            $_SESSION['coll_categories'][$collId][(string) $cat->id] = $label;
                        }
                        $_SESSION['coll_categories'][$collId]['default_category'] = (string) $categories->default_category;
                    }
                    $i++;
                } else {
                    $_SESSION['collections'][$i] = array(
                        'id'                   => (string) $col->id,
                        'label'                => (string) $tmp,
                        'view'                 => (string) $col->view,
                        'adr'                  => (string) $col->adr,
                        'index_file'           => (string) $col->index_file,
                        'script_add'           => (string) $col->script_add,
                        'script_search'        => (string) $col->script_search,
                        'script_search_result' => (string) $col->script_search_result,
                        'script_details'       => (string) $col->script_details,
                        'path_to_lucene_index' => (string) $col->path_to_lucene_index,
                        'extensions'           => $tab,
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
            //$_SESSION['history']['docserversclose']        = (string) $history->docserversclose;
            $_SESSION['history']['docserverslocationsadd']   = (string) $history->docserverslocationsadd;
            $_SESSION['history']['docserverslocationsdel']   = (string) $history->docserverslocationsdel;
            $_SESSION['history']['docserverslocationsallow'] = (string) $history->docserverslocationsallow;
            $_SESSION['history']['docserverslocationsban']   = (string) $history->docserverslocationsban;
            $_SESSION['history']['docserverstypesadd']       = (string) $history->docserverstypesadd;
            $_SESSION['history']['docserverstypesdel']       = (string) $history->docserverstypesdel;
            $_SESSION['history']['docserverstypesallow']     = (string) $history->docserverstypesallow;
            $_SESSION['history']['docserverstypesban']       = (string) $history->docserverstypesban;
            $_SESSION['history']['contact_types_del']        = (string) $history->contact_types_del;
            $_SESSION['history']['contact_types_add']        = (string) $history->contact_types_add;
            $_SESSION['history']['contact_types_up']         = (string) $history->contact_types_up;
            $_SESSION['history']['contact_purposes_del']     = (string) $history->contact_purposes_del;
            $_SESSION['history']['contact_purposes_add']     = (string) $history->contact_purposes_add;
            $_SESSION['history']['contact_purposes_up']      = (string) $history->contact_purposes_up;
            $_SESSION['history']['contact_addresses_del']    = (string) $history->contact_addresses_del;
            $_SESSION['history']['contact_addresses_add']    = (string) $history->contact_addresses_add;
            $_SESSION['history']['contact_addresses_up']     = (string) $history->contact_addresses_up;

            $i = 0;
            foreach ($xmlconfig->MODULES as $modules) {

                $_SESSION['modules'][$i] = array(
                    'moduleid' => (string) $modules->moduleid,
                    //,"comment" => (string) $MODULES->comment
                );
                $i ++;
            }
            $businessAppTools->_loadActionsPages();
        }

        if ($_SESSION['config']['usePHPIDS'] == 'true') {
            $businessAppTools->_loadPHPIDSExludes();
        }
    }

   /**
    * Load Maarch modules configuration into sessions vars from modules/module_name/xml/config.xml files
    * @param  array  $modules    Enabled modules of the application
    * @param  boolean $mode_batch [description]
    */
    public static function loadModulesConfig($modules, $mode_batch=false)
    {
        require_once "core/class/class_request.php";
        $coreTools = new core_tools();

        // Browses enabled modules
        for ($i = 0; $i < count($modules); $i ++) {
            if (file_exists(
                $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR
                . 'modules' . DIRECTORY_SEPARATOR . $modules[$i]['moduleid']
                . DIRECTORY_SEPARATOR . "xml" . DIRECTORY_SEPARATOR
                . "config.xml"
            )
            ) {
                $configPath = $_SESSION['config']['corepath'] . 'custom'
                    . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                    . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "xml"
                    . DIRECTORY_SEPARATOR . "config.xml";
            } else {
                $configPath = 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "xml"
                    . DIRECTORY_SEPARATOR . "config.xml";
            }

            if (file_exists('modules'.DIRECTORY_SEPARATOR.$modules[$i]['moduleid'].DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$_SESSION['config']['lang'].'.php')) {
                include_once 'modules'.DIRECTORY_SEPARATOR.$modules[$i]['moduleid'].DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$_SESSION['config']['lang'].'.php';
            }

            // Reads the config.xml file of the current module
            if ( ! file_exists($configPath) ) {
                throw new \Exception($configPath.' not-found');
            }

            $xmlconfig = simplexml_load_file($configPath);
            // Loads into $_SESSION['modules_loaded'] module's informations
            foreach ($xmlconfig->CONFIG as $CONFIG) {
                $_SESSION['modules_loaded'][$modules[$i]['moduleid']]['name'] =
                    (string) $CONFIG->name;
                $_SESSION['modules_loaded'][$modules[$i]['moduleid']]['path'] =
                    'modules' . DIRECTORY_SEPARATOR . $modules[$i]['moduleid']
                    . DIRECTORY_SEPARATOR;
                $comment = (string) $CONFIG->comment;
                if ( !empty($comment) && defined($comment)
                    && constant($comment) <> NULL
                ) {
                    $comment = constant($comment);
                }
                $_SESSION['modules_loaded'][$modules[$i]['moduleid']]['comment'] =
                    $comment;

                $_SESSION['modules_loaded'][$modules[$i]['moduleid']]['fileprefix'] = (string) $CONFIG->fileprefix;
                $_SESSION['modules_loaded'][$modules[$i]['moduleid']]['loaded'] = (string) $CONFIG->loaded;
            }

            if (file_exists(
                $_SESSION['config']['corepath'] . 'custom'
                    . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                    . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "class"
                    . DIRECTORY_SEPARATOR . "class_modules_tools.php"
            )
            ) {
                $path_module_tools = $_SESSION['config']['corepath'] . 'custom'
                    . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                    . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "class"
                    . DIRECTORY_SEPARATOR . "class_modules_tools.php";
            } else {
                $path_module_tools = 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "class"
                    . DIRECTORY_SEPARATOR . "class_modules_tools.php";
            }

            if (file_exists($path_module_tools)) {
                require_once($path_module_tools);
                $modules_tools = new $modules[$i]['moduleid'];
                //Loads the tables of the module into session
                $modules_tools->build_modules_tables();
                //Loads log keywords of the module
            }

            foreach ($xmlconfig->KEYWORDS as $keyword) {
                $tmp = (string) $keyword->label;
                if ( !empty($tmp) && defined($tmp) && constant($tmp) <> NULL ) {
                    $tmp = constant($tmp);
                }

                $id = (string) $keyword->id;

                if (!$coreTools->is_var_in_history_keywords_tab($id)) {
                    array_push(
                        $_SESSION['history_keywords'],
                        array(
                            'id' => $id,
                            'label' => $tmp
                        )
                    );
                }
            }
        }

//        if (!$mode_batch) {
//            //Loads logs keywords of the actions
//            $db = new Database();
//            $stmt = $db->query(
//                "select id, label_action from "
//                . $_SESSION['tablename']['actions']
//                . " where enabled = 'Y' and history = 'Y'"
//            );
//            while ($res = $stmt->fetchObject()) {
//                array_push(
//                    $_SESSION['history_keywords'],
//                    array(
//                        'id' =>'ACTION#' . $res->id,
//                        'label' => $coreTools->show_string($res->label_action)
//                    )
//                );
//            }
//        }
    }

    /**
     * Loads the modules specific vars into session
     * @param  array $modules  Enabled modules of the application
     * @param  array $userData [description]
     */
    public static function loadVarSession($modules, $userData)
    {
        for ($i = 0; $i < count($modules); $i ++) {
            if (file_exists(
                $_SESSION['config']['corepath'] . 'custom'
                    . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                    . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "class"
                    . DIRECTORY_SEPARATOR . "class_modules_tools.php"
            )
            ) {
                $path_module_tools = $_SESSION['config']['corepath'] . 'custom'
                    . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                    . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "class"
                    . DIRECTORY_SEPARATOR . "class_modules_tools.php";
            } else {
                $path_module_tools = 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "class"
                    . DIRECTORY_SEPARATOR . "class_modules_tools.php";
            }
            if (file_exists($path_module_tools)) {
                require_once $path_module_tools;
                $modules_tools = new $modules[$i]['moduleid'];
                if (method_exists(
                    $modules[$i]['moduleid'], 'load_module_var_session'
                )
                ) {
                    $modules_tools->load_module_var_session($userData);
                }
            }
            //$coreTools = new core_tools();
            //$coreTools->show_array($_SESSION['user']['baskets']);
        }
    }

    /**
    * Loads menu items of each module and the application into session from menu.xml files
    * @param  array $modules Enabled modules of the application
    * @return string          [description]
    */
    public static  function loadMenu($modules)
    {
        $k = 0;
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps'
            . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'menu.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
                . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR
                . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR . 'xml'
                . DIRECTORY_SEPARATOR . 'menu.xml';
        } else {
            $path = 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'menu.xml';
        }
        // Reads the apps/apps_name/xml/menu.xml file  and loads into session
        $xmlconfig = simplexml_load_file($path);
        foreach ($xmlconfig->MENU as $MENU2) {
            $_SESSION['menu'][$k]['id'] = (string) $MENU2->id;
            if (isset($_SESSION['menu'][$k]['id'])
                && isset($_SESSION['user']['services'][$_SESSION['menu'][$k]['id']])
                && $_SESSION['user']['services'][$_SESSION['menu'][$k]['id']] == true
            ) { // Menu Identifier must be equal to the Service identifier
                $libmenu = (string) $MENU2->libconst;
                if ( !empty($libmenu) && defined($libmenu)
                    && constant($libmenu) <> NULL
                ) {
                    $libmenu  = constant($libmenu);
                }
                $_SESSION['menu'][$k]['libconst'] = $libmenu;
                $_SESSION['menu'][$k]['url'] = $_SESSION['config']['businessappurl']
                    . (string) $MENU2->url;
                if (trim((string) $MENU2->target) <> "") {
                    $tmp = preg_replace(
                        '/\/core\/$/', '/', $_SESSION['urltocore']
                    );
                    $_SESSION['menu'][$k]['url'] = $tmp. (string) $MENU2->url;
                    $_SESSION['menu'][$k]['target'] = (string) $MENU2->target;
                }
                $_SESSION['menu'][$k]['style'] = (string) $MENU2->style;
                $_SESSION['menu'][$k]['show'] = true;
            } else {
                $_SESSION['menu'][$k]['libconst'] ='';
                $_SESSION['menu'][$k]['url'] ='';
                $_SESSION['menu'][$k]['style'] = '';
                $_SESSION['menu'][$k]['show'] = false;
            }
            $k ++;
        }
        // Browses the enabled modules array
        for ($i = 0; $i < count($modules); $i ++) {
            if (file_exists(
                $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR
                . 'modules' . DIRECTORY_SEPARATOR . $modules[$i]['moduleid']
                . DIRECTORY_SEPARATOR . "xml" . DIRECTORY_SEPARATOR . "menu.xml"
                )
            ) {
                $menuPath = $_SESSION['config']['corepath'] . 'custom'
                    . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                    . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "xml"
                    . DIRECTORY_SEPARATOR . "menu.xml";
            } else {
                $menuPath = 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "xml"
                    . DIRECTORY_SEPARATOR . "menu.xml";
            }

            if (file_exists(
                $_SESSION['config']['corepath'] . 'modules'
                . DIRECTORY_SEPARATOR . $modules[$i]['moduleid']
                . DIRECTORY_SEPARATOR . "xml" . DIRECTORY_SEPARATOR . "menu.xml"
            ) || file_exists(
                $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR
                . 'modules' . DIRECTORY_SEPARATOR . $modules[$i]['moduleid']
                . DIRECTORY_SEPARATOR . "xml" . DIRECTORY_SEPARATOR . "menu.xml"
            )
            ) {
                $xmlconfig = simplexml_load_file($menuPath);
                foreach ($xmlconfig->MENU as $MENU) {
                    $_SESSION['menu'][$k]['id'] = (string) $MENU->id;
                    if (isset(
                        $_SESSION['user']['services'][$_SESSION['menu'][$k]['id']]
                    )
                    && $_SESSION['user']['services'][$_SESSION['menu'][$k]['id']] == true
                    ) {
                        $libmenu = (string) $MENU->libconst;
                        if ( !empty($libmenu) && defined($libmenu)
                            && constant($libmenu) <> NULL
                        ) {
                            $libmenu  = constant($libmenu);
                        }
                        $_SESSION['menu'][$k]['libconst'] = $libmenu;
                        $_SESSION['menu'][$k]['url'] = $_SESSION['config']['businessappurl']
                            . (string) $MENU->url;
                        if (trim((string) $MENU->target) <> "") {
                            $tmp = preg_replace(
                                '/\/core\/$/', '/', $_SESSION['urltocore']
                            );
                            $_SESSION['menu'][$k]['url'] = $tmp
                                . (string) $MENU->url;
                            $_SESSION['menu'][$k]['target'] = (string) $MENU->target;
                        }
                        $_SESSION['menu'][$k]['style'] = (string) $MENU->style;
                        $_SESSION['menu'][$k]['show'] = true;
                    } else {
                        $_SESSION['menu'][$k]['libconst'] = '';
                        $_SESSION['menu'][$k]['url'] = '';
                        $_SESSION['menu'][$k]['style'] = '';
                        $_SESSION['menu'][$k]['show'] = false;
                    }
                    $k ++;
                }
            }
        }

        $coreTools = new core_tools();
        $coreTools->load_quicklaunch($modules);
    }

    /**
    * Loads application services into session
    */
    public static  function loadAppServices()
    {
        // Reads the application config.xml file
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps'
            . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'services.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
                . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR
                . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR . 'xml'
                . DIRECTORY_SEPARATOR . 'services.xml';
        } else {
            $path = 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR
                . 'services.xml';
        }
        $xmlconfig = simplexml_load_file($path);
        $k = 0;
        $m = 0;
        include_once 'apps/' .$_SESSION['config']['app_id']. '/lang/' . $_SESSION['config']['lang'].'.php' ;
        // Browses the services in that file and loads $_SESSION['app_services']
        foreach ($xmlconfig->SERVICE as $service) {
            $_SESSION['app_services'][$k] = array();
            $_SESSION['app_services'][$k]['id'] = (string) $service->id;
            $name = (string) $service->name;
            if ( !empty($name) && defined($name) && constant($name) <> NULL ) {
                $name  = constant($name);
            }
            $_SESSION['app_services'][$k]['name'] = $name;
            $comment = (string) $service->comment;
            if ( !empty($comment) && defined($comment)
                && constant($comment) <> NULL
            ) {
                $comment  = constant($comment);
            }
            $_SESSION['app_services'][$k]['comment'] = $comment;
            if (isset($service->servicepage)) {
                $_SESSION['app_services'][$k]['servicepage'] = (string) $service->servicepage;
                $_SESSION['app_services'][$k]['servicepage'] = preg_replace(
                    '/&admin/', '&amp;admin',
                    $_SESSION['app_services'][$k]['servicepage']
                );
                $_SESSION['app_services'][$k]['servicepage'] = preg_replace(
                    '/&module/', '&amp;module',
                    $_SESSION['app_services'][$k]['servicepage']
                );
            }
            $_SESSION['app_services'][$k]['servicetype'] = (string) $service->servicetype;

            if (isset($service->style)) {
                $_SESSION['app_services'][$k]['style'] = (string) $service->style;
            }

            $systemService = (string) $service->system_service;
            if ($systemService == "false") {
                $_SESSION['app_services'][$k]['system_service'] = false;
            } else {
                $_SESSION['app_services'][$k]['system_service'] = true;
            }
            $_SESSION['app_services'][$k]['enabled'] = (string) $service->enabled;
            $l = 0;
            foreach ($service->WHEREAMIUSED as $whereAmIUsed) {
                if (isset($whereAmIUsed)) {
                    $_SESSION['app_services'][$k]['whereamiused'][$l]['page'] = (string) $whereAmIUsed->page;
                    $_SESSION['app_services'][$k]['whereamiused'][$l]['nature'] = (string) $whereAmIUsed->nature;
                    if (isset($whereAmIUsed->button_label)) {
                        $_SESSION['app_services'][$k]['whereamiused'][$l]['button_label'] = (string) $whereAmIUsed->button_label;
                    }
                    if (isset($whereAmIUsed->tab_label)) {
                        $label = (string) $whereAmIUsed->tab_label;
                        if ( !empty($label) && defined($label)
                            && constant($label) <> NULL
                        ) {
                            $label  = constant($label);
                        }
                        $_SESSION['app_services'][$k]['whereamiused'][$l]['tab_label'] = $label;
                    }
                    if (isset($whereAmIUsed->tab_order)) {
                        $_SESSION['app_services'][$k]['whereamiused'][$l]['tab_order'] = (string) $whereAmIUsed->tab_order;
                    }
                    if (isset($whereAmIUsed->width)) {
                        $_SESSION['app_services'][$k]['whereamiused'][$l]['width'] = (string) $whereAmIUsed->width;
                    }
                    if (isset($whereAmIUsed->frame_id)) {
                        $_SESSION['app_services'][$k]['whereamiused'][$l]['frame_id'] = (string) $whereAmIUsed->frame_id;
                    }
                    if (isset($whereAmIUsed->height)) {
                        $_SESSION['app_services'][$k]['whereamiused'][$l]['height'] = (string) $whereAmIUsed->height;
                    }
                    if (isset($whereAmIUsed->scrolling)){
                        $_SESSION['app_services'][$k]['whereamiused'][$l]['scrolling'] = (string) $whereAmIUsed->scrolling;
                    }
                    if (isset($whereAmIUsed->style)) {
                        $_SESSION['app_services'][$k]['whereamiused'][$l]['style'] = (string) $whereAmIUsed->style;
                    }
                    if (isset($whereAmIUsed->border)) {
                        $_SESSION['app_services'][$k]['whereamiused'][$l]['border'] = (string) $whereAmIUsed->border;
                    }
                    $l ++;
                }
            }
            $m = 0;
            // Loads preprocess and postprocess
            foreach ($service->PROCESSINBACKGROUND as $processInBackground) {
                $_SESSION['app_services'][$k]['processinbackground'][$m]['page'] = (string) $processInBackground->page;
                if ((string) $processInBackground->preprocess <> "") {
                    $_SESSION['app_services'][$k]['processinbackground'][$m]['preprocess'] = (string) $processInBackground->preprocess;
                }
                if ((string) $processInBackground->postprocess <> "") {
                    $_SESSION['app_services'][$k]['processinbackground'][$m]['postprocess'] = (string) $processInBackground->postprocess;
                }
                $_SESSION['app_services'][$k]['processinbackground'][$m]['processorder'] = (string) $processInBackground->processorder;
                $m++;
            }
            $k ++;
        }
    }

    /**
    * Loads the services of each module into session
    *
    * @param array $modules Enabled modules of the application
    */
    public static  function loadModulesServices($modules)
    {
        // Browses the enabled modules array
        for ($i = 0; $i < count($modules); $i ++) {
            // Reads the module config.xml file
            if (file_exists(
                $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR
                . 'modules' . DIRECTORY_SEPARATOR . $modules[$i]['moduleid']
                . DIRECTORY_SEPARATOR . "xml" . DIRECTORY_SEPARATOR
                . "services.xml"
            )
            ) {
                $path = $_SESSION['config']['corepath'] . 'custom'
                    . DIRECTORY_SEPARATOR  . $_SESSION['custom_override_id']
                    . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "xml"
                    . DIRECTORY_SEPARATOR . "services.xml";
            } else {
                $path = 'modules' . DIRECTORY_SEPARATOR
                    . $modules[$i]['moduleid'] . DIRECTORY_SEPARATOR . "xml"
                    . DIRECTORY_SEPARATOR . "services.xml";
            }
            $xmlconfig = simplexml_load_file($path);
            $k = 0;
            $m = 0;
            foreach ($xmlconfig->SERVICE as $service) {
                if ((string) $service->enabled == "true") {
                    $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['id'] = (string) $service->id;
                    $name = (string) $service->name;
                    if ( !empty($name) && defined($name)
                        && constant($name) <> NULL
                    ) {
                        $name  = constant($name);
                    }
                    $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['name'] =
                        $name;

                    $comment = (string) $service->comment;
                    if ( !empty($comment) && defined($comment)
                        && constant($comment) <> NULL
                    ) {
                        $comment  = constant($comment);
                    }
                    $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['comment'] =
                        $comment;

                    if (isset($service->servicepage)) {
                        $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['servicepage'] = (string) $service->servicepage;
                    }
                    $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['servicetype'] = (string) $service->servicetype;

                    if (isset($service->style)) {
                        $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['style'] = (string) $service->style;
                    }
                    $systemService =  (string) $service->system_service;
                    if ($systemService == "false") {
                        $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['system_service'] = false;
                    } else {
                        $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['system_service'] = true;
                    }
                    $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['enabled'] = (string) $service->enabled;

                    $l = 0;
                    foreach ($service->WHEREAMIUSED as $whereAmIUsed) {
                        $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['page'] = (string) $whereAmIUsed->page;
                        $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['nature'] = (string) $whereAmIUsed->nature;
                        if (isset($whereAmIUsed->button_label)) {
                            $label = (string) $whereAmIUsed->button_label;
                            if ( !empty($label) && defined($label)
                                && constant($label) <> NULL
                            ) {
                                $label  = constant($label);
                            }
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['button_label'] =
                                $label;
                        }
                        if (isset($whereAmIUsed->tab_label)) {
                            $label = (string) $whereAmIUsed->tab_label;
                            if ( !empty($label) && defined($label)
                                && constant($label) <> NULL
                            ) {
                                $label  = constant($label);
                            }
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['tab_label'] =
                                $label;
                        }
                        if (isset($whereAmIUsed->tab_order)) {
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['tab_order'] = (string) $whereAmIUsed->tab_order;
                        }
                        if (isset($whereAmIUsed->frame_id)) {
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['frame_id'] = (string) $whereAmIUsed->frame_id;
                        }
                        if (isset($whereAmIUsed->width)) {
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['width'] = (string) $whereAmIUsed->width;
                        }
                        if (isset($whereAmIUsed->height)) {
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['height'] = (string) $whereAmIUsed->height;
                        }
                        if (isset($whereAmIUsed->scrolling)) {
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['scrolling'] = (string) $whereAmIUsed->scrolling;
                        }
                        if (isset($whereAmIUsed->style)) {
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['style'] = (string) $whereAmIUsed->style;
                        }
                        if (isset($whereAmIUsed->border)) {
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['whereamiused'][$l]['border'] = (string) $whereAmIUsed->border;
                        }
                        $l ++;
                    }
                    $m = 0;
                    foreach ($service->PROCESSINBACKGROUND as $processInBackground) {
                        $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['processinbackground'][$m]['page'] = (string) $processInBackground->page;
                        if ((string) $processInBackground->preprocess <> "") {
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['processinbackground'][$m]['preprocess'] = (string) $processInBackground->preprocess;
                        }
                        if ((string) $processInBackground->postprocess <> "") {
                            $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['processinbackground'][$m]['postprocess'] = (string) $processInBackground->postprocess;
                        }
                        $_SESSION['modules_services'][$modules[$i]['moduleid']][$k]['processinbackground'][$m]['processorder'] = (string) $processInBackground->processorder;
                        $m ++;
                    }
                    $k ++;
                }
            }
        }
    }
}