<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

require_once "core/class/class_request.php";

class RequestSeda
{
    private $db;

    protected $statement;

    public function __construct($db = null)
    {

        //create session if NO SESSION
        if (empty($_SESSION['user'])) {
            require_once('core/class/class_functions.php');
            include_once('core/init.php');
            require_once('core/class/class_portal.php');
            require_once('core/class/class_db.php');
            require_once('core/class/class_request.php');
            require_once('core/class/class_core_tools.php');

            //load Maarch session vars
            $portal = new portal();
            $portal->unset_session();
            $portal->build_config();
            $coreTools = new core_tools();
            $_SESSION['custom_override_id'] = $coreTools->get_custom_id();
            if (isset($_SESSION['custom_override_id'])
                && ! empty($_SESSION['custom_override_id'])
                && isset($_SESSION['config']['corepath'])
                && ! empty($_SESSION['config']['corepath'])
            ) {
                $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                    . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR;
                set_include_path(
                    $path . PATH_SEPARATOR . $_SESSION['config']['corepath']
                    . PATH_SEPARATOR . get_include_path()
                );
            } elseif (isset($_SESSION['config']['corepath'])
                && ! empty($_SESSION['config']['corepath'])
            ) {
                set_include_path(
                    $_SESSION['config']['corepath'] . PATH_SEPARATOR . get_include_path()
                );
            }
            // Load configuration from xml into session
            $_SESSION['config']['app_id'] = $_SESSION['businessapps'][0]['appid'];
            require_once('apps/maarch_entreprise/class/class_business_app_tools.php');

            $businessAppTools = new business_app_tools();
            $coreTools->build_core_config('core/xml/config.xml');

            $businessAppTools->build_business_app_config();
            $coreTools->load_modules_config($_SESSION['modules']);
        }

        $this->statement = [];
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = new Database();
        }
    }

    public function getEntitiesByBusinessId($businessId)
    {
        $queryParams = [];

        $queryParams[] = $businessId;

        $query = "SELECT * FROM entities WHERE business_id = ?";

        $smtp = $this->db->query($query, $queryParams);

        while ($res = $smtp->fetchObject()) {
            $entities[] = $res;
        }

        return $entities;
    }
}
