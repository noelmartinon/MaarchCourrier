<?php

/*
*   Copyright 2013-2016 Maarch
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
* modules tools Class for notes
*
*  Contains all the functions to load modules tables for notes
*
* @package  maarch
* @version 3.0
* @since 10/2005
* @license GPL v3
* @author  Claire Figueras  <dev@maarch.org>
*
*/


// Loads the required class
try {
    require_once("core/class/class_db.php");
    require_once("modules/entities/entities_tables.php");
    require_once ("modules/notes/class/class_modules_tools.php");
    require_once "modules/entities/class/EntityControler.php";
} catch (Exception $e){
    functions::xecho($e->getMessage()).' // ';
}

abstract class notes_Abstract
{

    /**
    * Db query object used to connnect to the database
    */
    private static $db;
    
    /**
    * Entity object
    */
    public static $ent;
    
    /**
    * Entities table
    */
    public static $entities_table ;
    
    /**
    * Build Maarch module tables into sessions vars with a xml configuration
    * file
    */
    public function build_modules_tables()
    {
    }
    
    /**
     * Function to get which user can see a note
     * @id note identifier
     */
    public function getNotesEntities($id)
    {
        $db = new Database();
        $ent = new EntityControler();
        
        $query = "SELECT entity_id, entity_label, short_label FROM note_entities , entities WHERE item_id LIKE entity_id and note_id = ?";
        
        try {
            $stmt = $db->query($query, array($id));
        } catch (Exception $e) {
        }

        $entitiesList = array();
        $entitiesChosen = array();
        $entitiesList = $ent->getAllEntities();
        

        while ($res = $stmt->fetchObject()) {
            array_push($entitiesChosen, $ent->get($res->entity_id));
        }
        
        return $entitiesChosen;
    }
}
