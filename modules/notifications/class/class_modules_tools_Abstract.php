<?php

/*
*   Copyright 2008-2016 Maarch
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
*   along with Maarch Framework. If not, see <http://www.gnu.org/licenses/>.
*/

/**
* modules tools Class for physical archives
*
*  Contains all the functions to  modules tables for physical archives
*
* @package  maarch
* @version 3.0
* @since 10/2005
* @license GPL v3
* @author  Loic Vinet  <dev@maarch.org>
*
*/

abstract class notifications_Abstract
{
    public function build_modules_tables()
    {
        require_once(
            "modules"
            .DIRECTORY_SEPARATOR."notifications"
               .DIRECTORY_SEPARATOR."notifications_tables_definition.php"
        );
        if (file_exists($_SESSION['config']['corepath'].'custom'
                        .DIRECTORY_SEPARATOR.$_SESSION['custom_override_id']
                        .DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR
                        ."notifications".DIRECTORY_SEPARATOR
                        ."xml".DIRECTORY_SEPARATOR."config.xml")
        ) {
            $path = $_SESSION['config']['corepath'].'custom'
                .DIRECTORY_SEPARATOR.$_SESSION['custom_override_id']
                .DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR."notifications"
                .DIRECTORY_SEPARATOR."xml".DIRECTORY_SEPARATOR."config.xml";
        } else {
            $path = "modules".DIRECTORY_SEPARATOR."notifications"
                .DIRECTORY_SEPARATOR."xml".DIRECTORY_SEPARATOR."config.xml";
        }
        $xmlconfig = simplexml_load_file($path);
    }
    
    public function load_module_var_session()
    {
        if (file_exists($_SESSION['config']['corepath'].'custom'
                        .DIRECTORY_SEPARATOR.$_SESSION['custom_override_id']
                        .DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR
                        ."notifications".DIRECTORY_SEPARATOR
                        ."xml".DIRECTORY_SEPARATOR."event_type.xml")
        ) {
            $path = $_SESSION['config']['corepath'].'custom'
                .DIRECTORY_SEPARATOR.$_SESSION['custom_override_id']
                .DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR."notifications"
                .DIRECTORY_SEPARATOR."xml".DIRECTORY_SEPARATOR."event_type.xml";
        } else {
            $path = "modules".DIRECTORY_SEPARATOR."notifications"
                .DIRECTORY_SEPARATOR."xml".DIRECTORY_SEPARATOR."event_type.xml";
        }
        $xmlconfig = simplexml_load_file($path);
        
        foreach ($xmlconfig->event_type as $event) {
            $id = (string)$event->id;
            $label = (string)$event->label;
            if (@constant($label)) {
                $_SESSION['notif_events'][$id] = constant($label);
            } else {
                $_SESSION['notif_events'][$id] = $label;
            }
        }
    }
}
