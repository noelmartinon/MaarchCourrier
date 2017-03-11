<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace Folder\Models;


use CMIS\Utils\Utils;

require_once('../core/class/class_functions.php');
require_once('../core/class/class_db_pdo.php');

abstract class FoldersModelAbstract extends \SplObjectStorage
{
    private $_folders_system_id, $_folder_id, $_foldertype_id, $_parent_id, $_folder_name, $_subject, $_description
    , $_author, $_typist, $_status, $_folder_level, $_creation_date, $_last_modified_date, $_uniqid;

    /**
     * FoldersModelAbstract constructor.
     * @param $_folders_system_id
     * @param $_folder_id
     * @param $_foldertype_id
     * @param $_parent_id
     * @param $_folder_name
     * @param $_subject
     * @param $_description
     * @param $_author
     * @param $_typist
     * @param $_status
     * @param $_folder_level
     * @param $_creation_date
     * @param $_last_modified_date
     */
    public function __construct($_folders_system_id = null, $_folder_id = null, $_foldertype_id = null, $_parent_id = null
        , $_folder_name = null, $_subject = null, $_description = null, $_author = null, $_typist = null, $_status = null
        , $_folder_level = null, $_creation_date = null, $_last_modified_date = null)
    {
        $this->_folders_system_id = $_folders_system_id;
        $this->_folder_id = $_folder_id;
        $this->_foldertype_id = $_foldertype_id;
        $this->_parent_id = $_parent_id;
        $this->_folder_name = $_folder_name;
        $this->_subject = $_subject;
        $this->_description = $_description;
        $this->_author = $_author;
        $this->_typist = $_typist;
        $this->_status = $_status;
        $this->_folder_level = $_folder_level;
        $this->_creation_date = $_creation_date;
        $this->_last_modified_date = $_last_modified_date;
        $this->_uniqid = bin2hex('folder_' . $_folders_system_id);
    }


    public function attach($obj, $inf = null)
    {
        parent::attach($obj, null);
    }

    public function detach($obj)
    {
        parent::detach($obj);
    }

    /**
     * @return null
     */
    public function getFoldersSystemId()
    {
        return $this->_folders_system_id;
    }

    /**
     * @param null $folders_system_id
     * @return $this
     */
    public function setFoldersSystemId($folders_system_id)
    {
        $this->_folders_system_id = $folders_system_id;
        return $this;
    }

    /**
     * @return null
     */
    public function getFolderId()
    {
        return $this->_folder_id;
    }

    /**
     * @param null $folder_id
     * @return $this
     */
    public function setFolderId($folder_id)
    {
        $this->_folder_id = $folder_id;
        return $this;
    }

    /**
     * @return null
     */
    public function getFoldertypeId()
    {
        return $this->_foldertype_id;
    }

    /**
     * @param null $foldertype_id
     * @return $this
     */
    public function setFoldertypeId($foldertype_id)
    {
        $this->_foldertype_id = $foldertype_id;
        return $this;
    }

    /**
     * @return null
     */
    public function getParentId()
    {
        return $this->_parent_id;
    }

    /**
     * @param null $parent_id
     * @return $this
     */
    public function setParentId($parent_id)
    {
        $this->_parent_id = $parent_id;
        return $this;
    }

    /**
     * @return null
     */
    public function getFolderName()
    {
        return $this->_folder_name;
    }

    /**
     * @param null $folder_name
     * @return $this
     */
    public function setFolderName($folder_name)
    {
        $this->_folder_name = $folder_name;
        return $this;
    }

    /**
     * @return null
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * @param null $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;
        return $this;
    }

    /**
     * @return null
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param null $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->_description = $description;
        return $this;
    }

    /**
     * @return null
     */
    public function getAuthor()
    {
        return $this->_author;
    }

    /**
     * @param null $author
     * @return $this
     */
    public function setAuthor($author)
    {
        $this->_author = $author;
        return $this;
    }

    /**
     * @return null
     */
    public function getTypist()
    {
        return $this->_typist;
    }

    /**
     * @param null $typist
     * @return $this
     */
    public function setTypist($typist)
    {
        $this->_typist = $typist;
        return $this;
    }

    /**
     * @return null
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param null $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }

    /**
     * @return null
     */
    public function getFolderLevel()
    {
        return $this->_folder_level;
    }

    /**
     * @param null $folder_level
     * @return $this
     */
    public function setFolderLevel($folder_level)
    {
        $this->_folder_level = $folder_level;
        return $this;
    }

    /**
     * @return null
     */
    public function getCreationDate()
    {
        return $this->_creation_date;
    }

    /**
     * @param null $creation_date
     * @return $this
     */
    public function setCreationDate($creation_date)
    {
        $this->_creation_date = $creation_date;
        return $this;
    }

    /**
     * @return null
     */
    public function getLastModifiedDate()
    {
        return $this->_last_modified_date;
    }

    /**
     * @param null $last_modified_date
     * @return $this
     */
    public function setLastModifiedDate($last_modified_date)
    {
        $this->_last_modified_date = $last_modified_date;
        return $this;
    }

    /**
     * @param bool $raw
     * @return mixed|string
     */
    public function getUniqid($raw = true)
    {
        return ($raw) ? $this->_uniqid : str_replace('folder_', '', hex2bin($this->_uniqid));
    }

    /**
     * @param string $uniqid
     * @return $this
     */
    public function setUniqid($uniqid)
    {
        $this->_uniqid = bin2hex('folder_' . $uniqid);
        return $this;
    }


}