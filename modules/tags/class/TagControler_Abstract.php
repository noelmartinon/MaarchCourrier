<?php
/*
*    Copyright 2008-2016 Maarch
*
*  This file is part of Maarch Framework.
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
*    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* Module : Tags
*
* This module is used to store ressources with any keywords
* V: 1.0
*
* @file
* @author Loic Vinet
* @date $date$
* @version $Revision$
*/


// To activate de debug mode of the class
$_ENV['DEBUG'] = false;
/*
define("_CODE_SEPARATOR","/");
define("_CODE_INCREMENT",1);
*/

// Loads the required class
try {
    require_once("core/class/class_db.php");
    require_once("core/class/class_history.php");
    require_once("modules/tags/class/Tag.php");
    require_once("modules/tags/tags_tables_definition.php");
    require_once("core/class/users_controler.php");
    require_once("modules/entities/class/class_users_entities_Abstract.php");
    require_once("core/class/users_controler.php");
} catch (Exception $e) {
    functions::xecho($e->getMessage()).' // ';
}

/**
* @brief  Controler of the Tag Object
* @ingroup core
*/
abstract class tag_controler_Abstract extends ObjectControler
{
    /**
     * Get event with given event_id.
     * Can return null if no corresponding object.
     * @param $id Id of event to get
     * @return event
     */
    
    
    public function get_all_tags($coll_id = '')
    {
        $core = new core_tools();

        /*
         * Return a complete list of tags in Maarch
         */
          
        $return     = array();
        $where_what = array();
  
        $db = new Database();
        
        if ($core->test_service('private_tag', 'tags', false) == 1) {
            $entitiesRestriction = array();
            $uc = new users_controler();
            $userEntities = $uc->getEntities($_SESSION['user']['UserId']);

            foreach ($userEntities as $entity) {
                $entity_id = $entity['ENTITY_ID'];
                $entitiesRestriction[] = $entity_id;
            }

            //CHECK TAG IS ALLOW FOR THESE ENTITIES
            if (!empty($entitiesRestriction)) {
                $entitiesRestriction = "'".implode("','", $entitiesRestriction)."'";
                $where = ' WHERE entity_id IN ('.$entitiesRestriction.')';
            } else {
                $where = '';
            }
            $stmt = $db->query(
            'SELECT distinct(tag_id)'
            . ' FROM tags_entities'
            . $where,
                array()
            );
            
            $restrictedTagIdList = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            //CHECK TAG WHO IS NOT RESTRICTED
            $stmt = $db->query(
            'SELECT tag_id'
            . ' FROM tags'
            . ' WHERE tag_id NOT IN (select distinct(tag_id) from tags_entities)',
                array()
            );
            $freeTagIdList = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            
            //MERGE ALLOWED TAGS AND FREE TAGS
            $tagIdList = array_merge($restrictedTagIdList, $freeTagIdList);
            
            if (!empty($tagIdList)) {
                $tagIdList = "'".implode("','", $tagIdList)."'";
                $where = ' WHERE tag_id IN ('.$tagIdList.')';
            } else {
                // NO TAG ALLOWED
                $where = ' WHERE tag_id = 0';
            }
            
            $stmt = $db->query(
            'SELECT tag_id, tag_label FROM '
            . _TAG_TABLE_NAME
            . $where
            . ' ORDER BY tag_label ASC ',
                $where_what
            );
        } else {
            $stmt = $db->query(
            'SELECT tag_id, tag_label FROM '
            . _TAG_TABLE_NAME
            . ' ORDER BY tag_label ASC ',
                $where_what
            );
        }
  
        self::set_specific_id('tag_id');
      
        if ($stmt->rowCount() > 0) {
            while ($tag=$stmt->fetchObject()) {
                $tougue['tag_id']    = $tag->tag_id;
                $tougue['tag_label'] = $tag->tag_label;
                $tougue['coll_id']   = $tag->coll_id;
                array_push($return, $tougue);
            }
            return $return;
        }
        return false;
    }
    
    public function get_by_id($tag_id, $coll_id = 'letterbox_coll')
    {
        /*
         * Searching a tag by label
         * @If tag exists, return this value, else, return false
         */
        if (empty($tag_id) || empty($coll_id)) {
            return null;
        }

        $db = new Database();
        $entities = array();

        $stmt = $db->query(
                'SELECT tag_id, tag_label, coll_id FROM ' . _TAG_TABLE_NAME
                . ' WHERE tag_id = ? AND'
                . ' coll_id = ?',
            array($tag_id, $coll_id)
        );

        self::set_specific_id('tag_id');

        $tag = $stmt->fetchObject();

        //Retrieve entities restriction
        $stmt = $db->query(
                'SELECT entity_id FROM tags_entities'
                . ' WHERE tag_id = ?',
            array($tag_id)
        );
        $entities = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        $tag->entities = $entities;

        if (isset($tag)) {
            return $tag;
        } else {
            return null;
        }
    }

    public function get_by_label($tag_label, $coll_id = 'letterbox_coll')
    {
        /*
         * Searching a tag by label
         * @If tag exists, return this value, else, return false
         */
        $tag_label = str_replace("''", "'", $tag_label);
        if (empty($tag_label) || empty($coll_id)) {
            return null;
        }

        $db = new Database();
        $stmt = $db->query(
            'SELECT tag_id, tag_label FROM '._TAG_TABLE_NAME
            . ' WHERE tag_label = ? AND'
            . ' coll_id = ?',
            array($tag_label,$coll_id)
        );
        
        $tag=$stmt->fetchObject();

        if (isset($tag)) {
            return $tag;
        } else {
            return null;
        }
    }
    
    public function get($tag_label, $coll_id, $res_id)
    {
        /*
         * Standard Get Object, not used at this time
         */
        if (empty($tag_label) || empty($coll_id) || empty($res_id)) {
            return null;
        }

        self::set_specific_id('tag_label');
      
        $tag = self::advanced_get($tag_label, _TAG_TABLE_NAME);

        if (isset($tag)) {
            return $tag;
        } else {
            return null;
        }
    }
  
    public function get_by_res($res_id, $coll_id)
    {
        /*
         * Searching tags by a ressources
         * @Return : list of tags for one ressource
         */
        $db = new Database();
        
        $stmt = $db->query(
            "SELECT tag_res.tag_id FROM tag_res"
            . " INNER JOIN tags ON tag_res.tag_id = tags.tag_id"
            . " WHERE tag_res.res_id = ? AND tags.coll_id = ?",
            array($res_id,$coll_id)
        );
        //$db->show();
        
        
        $return = array();
        while ($res = $stmt->fetchObject()) {
            array_push($return, $res->tag_id);
        }
        if ($return) {
            return $return;
        } else {
            return false;
        }
    }
  
    public function delete_this_tag($res_id, $coll_id, $tag_label)
    {
        /*
         * Deleting a tag for a ressource
         */
        $db = new Database();
        $stmt = $db->query(
            "SELECT tag_label FROM " ._TAG_TABLE_NAME
            . " WHERE res_id = ? AND coll_id = ? AND tag_label = ?",
            array($res_id,$coll_id,$tag_label)
        );

        if ($stmt->rowCount()>0) {
            //Lancement de la suppression de l'occurence
            $stmt = $db->query(
                "DELETE FROM " ._TAG_TABLE_NAME
                . " WHERE res_id = ? AND coll_id = ? AND tag_label = ? ",
                array($res_id,$coll_id,$tag_label)
            );
            if ($stmt) {
                $hist = new history();
                $hist->add(
                    _TAG_TABLE_NAME,
                    $tag_label,
                    "DEL",
                    'tagdel',
                    _TAG_DELETED.' : "'.
                    substr(functions::protect_string_db($tag_label), 0, 254) .'"',
                    $_SESSION['config']['databasetype'],
                    'tags'
                );
                return true;
            }
        }
        return fasle;
        
        //$db->show();
    }
    
    public function countdocs($tag_id)
    {
        /*
         * Count ressources for one tag : used by tags administration
         */
         
        $db = new Database();
        $stmt = $db->query(
                "SELECT count(res_id) AS bump FROM tag_res"
                . " WHERE tag_id = ?"
                . " AND res_id <> 0",
            array($tag_id)
        );
        
        $result = $stmt->fetchObject();
        $return = 0;
        
        if ($result) {
            $return = $result->bump;
        }
        
        return $return;
    }
    
    
    /*
     * Searching a list of ressources by label
     * @Return : an Array with label's ressources or 0
     */
    public function getresarray_byId($tag_id)
    {
        $result = array();
        $db = new Database();
        $stmt = $db->query(
                "SELECT res_id FROM tag_res"
                . " WHERE tag_id = ?",
            array($tag_id)
        );

        $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $result;
    }
    
    
    public function deleteTagsRes($res_id)
    {
        /*
         * Searching a tag by label
         * @If tag exists, return this value, else, return false
         */
        $db = new Database();
        $core = new core_tools();
        
        $where = '';
        if ($core->test_service('private_tag', 'tags', false) == 1) {
            $entitiesRestriction = array();
            $uc = new users_controler();
            $userEntities = $uc->getEntities($_SESSION['user']['UserId']);
            foreach ($userEntities as $entity) {
                $entity_id = $entity['ENTITY_ID'];
                $entitiesRestriction[] = $entity_id;
            }
            //CHECK TAG IS ALLOW FOR THESE ENTITIES
            if (!empty($entitiesRestriction)) {
                $entitiesRestriction = "'".implode("','", $entitiesRestriction)."'";
                $where = ' WHERE entity_id IN ('.$entitiesRestriction.')';
            } else {
                $where = '';
            }
            $stmt = $db->query(
            'SELECT distinct(tag_id)'
            . ' FROM tags_entities'
            . $where,
                array()
            );

            $restrictedTagIdList = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            //var_dump($restrictedTagIdList);
            //CHECK TAG WHO IS NOT RESTRICTED
            $stmt = $db->query(
            'SELECT tag_id'
            . ' FROM tags'
            . ' WHERE tag_id NOT IN (select distinct(tag_id) from tags_entities)',
                array()
            );
            $freeTagIdList = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            //var_dump($freeTagIdList);
            //MERGE ALLOWED TAGS AND FREE TAGS
            $tagIdList = array_merge($restrictedTagIdList, $freeTagIdList);

            if (!empty($tagIdList)) {
                $tagIdList = "'".implode("','", $tagIdList)."'";
                $where = ' AND tag_id IN ('.$tagIdList.')';
            } else {
                $where = '';
            }
        }
        
        $stmt = $db->query(
                "DELETE FROM tag_res"
                . " WHERE res_id = ?"
                . $where,
            array($res_id)
        );
        /*$hist = new history();
        $hist->add(
            'res_view_letterbox', $res_id, "DEL", 'tagdel', _ALL_TAG_DELETED_FOR_RES_ID.' : "'.
            substr(functions::protect_string_db($res_id), 0, 254) .'"',
            $_SESSION['config']['databasetype'], 'tags'
        );*/
        //$db->show();
    }
    
    
    public function delete($tag_id, $coll_id='letterbox_coll')
    {
        /*
         * Deleting  [REALLY] a tag for a ressource
         */
        $db = new Database();
        $stmt = $db->query(
                "DELETE FROM " ._TAG_TABLE_NAME
                . " WHERE tag_id = ?",
            array($tag_id)
        );
        $stmt = $db->query(
                "DELETE FROM tag_res"
                . " WHERE tag_id = ?",
            array($tag_id)
        );
        $stmt = $db->query(
                "DELETE FROM tags_entities"
                . " WHERE tag_id = ?",
            array($tag_id)
        );
        
        if ($stmt) {
            $hist = new history();
            $hist->add(
                _TAG_TABLE_NAME,
                $tag_label,
                "DEL",
                'tagdel',
                _TAG_DELETED.' : "'.
                substr(functions::protect_string_db($tag_label), 0, 254) .'"',
                $_SESSION['config']['databasetype'],
                'tags'
            );
            return true;
        }
        return false;
        //$db->show();
    }
    
    
    public function store($tag_id, $mode='up', $params)
    {
        /*
         * Store into the database a tag for a ressource
         */
        
        if ($mode=='add') {
            $new_tag_label = $params[0];
            $coll_id = $params[1];
            $this->insert_tag_label($new_tag_label, $coll_id);
            
            /*$hist = new history();
            $hist->add(
                _TAG_TABLE_NAME, $new_tag_label, "ADD", 'tagadd', _TAG_ADDED.' : "'.
                substr(functions::protect_string_db($new_tag_label), 0, 254) .'"',
                $_SESSION['config']['databasetype'], 'tags'
            );*/
            return true;
        } elseif ($mode=='up') {
            $new_tag_label = $params[0];
            $coll_id = $params[1];
            $this->update_tag($tag_id, $coll_id);
            /*$hist = new history();
            $hist->add(
                _TAG_TABLE_NAME, $new_tag_label, "ADD", 'tagup', _TAG_ADDED.' : "'.
                substr(functions::protect_string_db($new_tag_label), 0, 254) .'"',
                $_SESSION['config']['databasetype'], 'tags'
            );*/
            return true;
        } else {
            return false;
        }
    }
    
    public function update_tag_label($new_tag_label, $old_taglabel, $coll_id)
    {
        /*
         * Update in the memory [Session] the tag value for one ressource
         */
        $new_tag_label = $this->control_label($new_tag_label);
        $new_tag_label = str_replace("''", "'", $new_tag_label);
        $old_taglabel = str_replace("''", "'", $old_taglabel);
        
        $db = new Database();
        $stmt = $db->query(
            "SELECT tag_label FROM " ._TAG_TABLE_NAME
            . " WHERE  coll_id = ? and tag_label = ?  ",
            array($coll_id,$new_tag_label)
        );
        if ($stmt->rowCount() == 0) {
            $stmt = $db->query(
                "UPDATE " ._TAG_TABLE_NAME
                . " SET tag_label = ?"
                . " WHERE coll_id = ? AND tag_label = ?",
                array($new_tag_label,$coll_id,$old_taglabel)
            );
            $hist = new history();
            $hist->add(
                _TAG_TABLE_NAME,
                $new_tag_label,
                "UP",
                'tagup',
                _TAG_UPDATED.' : "'.
                substr(functions::protect_string_db($new_tag_label), 0, 254) .'"',
                $_SESSION['config']['databasetype'],
                'tags'
            );
        } else {
            $_SESSION['error'] = _TAG_ALREADY_EXISTS;
        }
    }
    
    public function update_tag($tag_id, $coll_id)
    {
        /*
         * Add in the memory [Session] the tag value for one ressource
         */

        $new_tag_label = str_replace("''", "'", $_SESSION['m_admin']['tag']['tag_label']);
        $new_tag_label = $this->control_label($_SESSION['m_admin']['tag']['tag_label']);
        
        $db = new Database();
        
        //Primo, test de l'existance du mot clé en base.
        $stmt = $db->query(
            "UPDATE " ._TAG_TABLE_NAME
            . " SET  tag_label = ?"
            . " WHERE  tag_id = ?",
            array($new_tag_label,$tag_id)
        );
        
        //reset entities restrictions
        $stmt = $db->query(
                "DELETE FROM tags_entities"
                . " WHERE tag_id = ?",
            array($tag_id)
        );
        
        if (!empty($_SESSION['m_admin']['tag']['entities'])) {
            foreach ($_SESSION['m_admin']['tag']['entities'] as $entity_id) {
                $stmt = $db->query(
                    "INSERT INTO tags_entities"
                    . "(tag_id, entity_id) VALUES (?, ?)",
                    array($tag_id,$entity_id)
                );
            }
        }
    }
    
    public function insert_tag_label($new_tag_label, $coll_id)
    {
        /*
         * Add in the memory [Session] the tag value for one ressource
         */

        $new_tag_label = str_replace("''", "'", $new_tag_label);
        $new_tag_label = $this->control_label($new_tag_label);
        
        $db = new Database();
        
        //Primo, test de l'existance du mot clé en base.
        /*$stmt = $db->query(
            "SELECT tag_label FROM " ._TAG_TABLE_NAME
            . " WHERE  coll_id = ? AND tag_label ILIKE ?"
        ,array($coll_id,$new_tag_label));
        //$db->show();exit();
        if ($stmt->rowCount() == 0)
        {*/
        $stmt = $db->query(
                "INSERT INTO " ._TAG_TABLE_NAME
                . "(tag_label, coll_id, entity_id_owner) VALUES (?, ?, ?)",
                 array($new_tag_label,$coll_id,$_SESSION['user']['primaryentity']['id'])
             );
             
        $tag_id = $db->lastInsertId('tag_id_seq');
            
        if (!empty($_SESSION['m_admin']['tag']['entities'])) {
            foreach ($_SESSION['m_admin']['tag']['entities'] as $entity_id) {
                $stmt = $db->query(
                        "INSERT INTO tags_entities"
                        . "(tag_id, entity_id) VALUES (?, ?)",
                        array($tag_id,$entity_id)
                    );
            }
        }
            
        /*}else{
            $_SESSION['error'] = _TAG_DEFAULT.' '.': '.$new_tag_label.' '._ALREADY_EXISTS;
            return false;
        }*/
    }
    
    
    public function add_this_tag($res_id, $tag_id)
    {
        /*
         * Adding  [REALLY] a tag for a ressource
         */
        $db = new Database();

        $stmt = $db->query(
                "INSERT INTO tag_res"
                . " (tag_id, res_id)"
                . " VALUES (?,?)  ",
            array($tag_id, $res_id)
        );
        if ($stmt) {

            /* $hist = new history();
              $hist->add(
              'res_view_letterbox', $res_id, "ADD", 'tagadd', _TAG_ADDED.' : "'.
              substr(functions::protect_string_db($tag_label), 0, 254) .'"',
              $_SESSION['config']['databasetype'], 'tags'
              ); */
            return true;
        }

        return false;

        //$db->show();
    }

    public function load_sessiontag($res_id, $coll_id)
    {
        $_SESSION['tagsuser'] = array();
        $_SESSION['tagsuser'] = $this->get_by_res($res_id, $coll_id);
    }
    
    
    public function add_this_tags_in_session($tag_label, $coll_id)
    {
        $_SESSION['m_admin']['tag'] = array();
        $_SESSION['m_admin']['tag']['tag_label'] = $tag_label;
        $_SESSION['m_admin']['tag']['entities'] = array();
        $core = new core_tools();
        
        if ($core->test_service('private_tag', 'tags', false) == 1) {
            $entitiesRestriction = array();
            $entitiesDirection = users_controler::getParentEntitiesWithType($_SESSION['user']['UserId'], 'Direction');
            //var_dump($entitiesDirection);
            foreach ($entitiesDirection as $entity_id) {
                $entitiesRestriction[] = $entity_id;
                $tmp_arr = users_entities_Abstract::getEntityChildren($entity_id);
                $entitiesRestriction = array_merge($entitiesRestriction, $tmp_arr);
            }
            $_SESSION['m_admin']['tag']['entities'] = array_unique($entitiesRestriction);
        }

        $this->insert_tag_label($tag_label, $coll_id);
        return true;
    }

    public function remove_this_tags_in_session($tag_label)
    { //remplir le formulaire de session
    
        if ($_SESSION['tagsuser']) {
            $ready = false;
            foreach ($_SESSION['tagsuser'] as $this_tag) {
                if ($this_tag == $tag_label) {
                    $ready = true;
                }
            }
            
            if ($ready == true) {
                unset($_SESSION['tagsuser'][array_search($tag_label, $_SESSION['tagsuser'])]);
                return true;
            }
            return false;
        } else {
            return false;
        }
    }
    
    public function associateTagToRes($res_id, $coll_id, $tag_array)
    {
        $core_tools = new core_tools();
        if ($core_tools->test_service('add_tag_to_res', 'tags', false) == 1) {
            $this->deleteTagsRes($res_id);
            if (!empty($tag_array[0])) {
                foreach ($tag_array as $this_tagId) {
                    $this->add_this_tag($res_id, $this_tagId);
                }
            }
        }
    }
    
    
    protected function control_label($label)
    {
        $label  = str_replace('\r', '', $label);
        $label  = str_replace('\n', '', $label);
        // $label  = str_replace('\'', ' ', $label);
        $label  = str_replace('"', ' ', $label);
        $label  = str_replace('\\', ' ', $label);
        // $label  = str_replace(' ', '', $label);
        
        
        //On découpe la chaine composée de virgules
        $tabrr = array( CHR(13) => ",", CHR(10) => "," );
        $label = strtr($label, $tabrr);
        
        return $label;
    }
}
