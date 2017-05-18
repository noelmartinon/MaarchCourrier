<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace CMIS\Models;


use CMIS\Utils\Utils;

abstract class DocumentModelAbstract
{
    private $_res_id, $_title, $_subject, $_description, $_type_id, $_format, $_typist, $_creation_date, $_modification_date
    , $_folders_system_id, $_path, $_filename, $_filesize, $_uniqid;

    /**
     * DocumentAbstract constructor.
     * @param $_res_id
     * @param $_title
     * @param $_subject
     * @param $_description
     * @param $_type_id
     * @param $_format
     * @param $_typist
     * @param $_creation_date
     * @param $_modification_date
     * @param $_folders_system_id
     * @param $_path
     * @param $_filename
     * @param $_filesize
     */
    public function __construct($_res_id = null, $_title = null, $_subject = null, $_description = null, $_type_id = null
        , $_format = null, $_typist = null, $_creation_date = null, $_modification_date = null, $_folders_system_id = null
        , $_path = null, $_filename = null, $_filesize = null)
    {
        $this->_res_id = $_res_id;
        $this->_title = $_title;
        $this->_subject = $_subject;
        $this->_description = $_description;
        $this->_type_id = $_type_id;
        $this->_format = $_format;
        $this->_typist = $_typist;
        $this->_creation_date = $_creation_date;
        $this->_modification_date = $_modification_date;
        $this->_folders_system_id = $_folders_system_id;
        $this->_path = $_path;
        $this->_filename = $_filename;
        $this->_filesize = $_filesize;
        $this->_uniqid = Utils::createObjectId($_res_id, 'document');
    }


    /**
     * @return null
     */
    public function getResId()
    {
        return $this->_res_id;
    }

    /**
     * @param null $res_id
     * @return $this
     */
    public function setResId($res_id)
    {
        $this->_res_id = $res_id;
        $this->_uniqid = Utils::createObjectId($res_id, 'document');
        return $this;
    }

    /**
     * @return null
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param null $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->_title = $title;
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
    public function getTypeId()
    {
        return $this->_type_id;
    }

    /**
     * @param null $type_id
     * @return $this
     */
    public function setTypeId($type_id)
    {
        $this->_type_id = $type_id;
        return $this;
    }

    /**
     * @return null
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * @param null $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->_format = $format;
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
    public function getModificationDate()
    {
        return $this->_modification_date;
    }

    /**
     * @param null $modification_date
     * @return $this
     */
    public function setModificationDate($modification_date)
    {
        $this->_modification_date = $modification_date;
        return $this;
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
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @param null $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->_path = $path;
        return $this;
    }

    /**
     * @return null
     */
    public function getFilename()
    {
        return $this->_filename;
    }

    /**
     * @param null $filename
     * @return $this
     */
    public function setFilename($filename)
    {
        $this->_filename = $filename;
        return $this;
    }

    /**
     * @return null
     */
    public function getFilesize()
    {
        return $this->_filesize;
    }

    /**
     * @param null $filesize
     * @return $this
     */
    public function setFilesize($filesize)
    {
        $this->_filesize = $filesize;
        return $this;
    }

    /**
     * @param bool $raw
     * @return mixed|string
     */
    public function getUniqid($raw = true)
    {
        return ($raw) ? $this->_uniqid :  Utils::readObjectId($this->_uniqid, 'document');
    }


    /**
     * @param string $id
     * @return $this
     */
    public function setUniqid($id)
    {
        $this->_uniqid = Utils::createObjectId($id, 'document');
        return $this;
    }

    public function getFolderUniqueId()
    {
        return bin2hex('folder_' . $this->_folders_system_id);
    }

    public function getType()
    {
        return 'document';
    }

}