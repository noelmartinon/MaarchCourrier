<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace CMIS\Models;

use CMIS\Utils\Utils;
use Couchbase\Document;
use Folder\Models\FoldersModel;

class CMISObject extends \SplObjectStorage
{
    private $_objectId,
        $_path,
        $_objectTypeId,
        $_description,
        $_createdBy,
        $_baseTypeId,
        $_parentId,
        $_creationDate,
        $_changeToken,
        $_name,
        $_lastModificationDate,
        $_lastModifiedBy,
        $_otherProperties;

    /**
     * CMISObject constructor.
     * @param $_objectId
     * @param $_path
     * @param $_objectTypeId
     * @param $_description
     * @param $_createdBy
     * @param $_baseTypeId
     * @param $_parentId
     * @param $_creationDate
     * @param $_changeToken
     * @param $_name
     * @param $_lastModificationDate
     * @param $_lastModifiedBy
     * @param $_otherProperties
     */
    public function __construct($_objectId = null, $_path = '/', $_objectTypeId = 'cmis:folder', $_description = '',
                                $_createdBy = 'System', $_baseTypeId = 'cmis:folder', $_parentId = null, $_creationDate = null,
                                $_changeToken = null, $_name = '', $_lastModificationDate = null, $_lastModifiedBy = 'System', $_otherProperties = [])
    {


        $_lastModificationDate = ($_lastModificationDate) ? $_lastModificationDate : date(DATE_ATOM);
        $_creationDate = ($_creationDate) ? $_creationDate : date(DATE_ATOM);
        $this->_otherProperties = $_otherProperties;

        $this->_objectId = [
            'id' => 'cmis:objectId',
            'localName' => 'objectId',
            'displayName' => 'Object Id',
            'queryName' => 'cmis:objectId',
            'type' => 'id',
            'cardinality' => 'single',
            'value' => $_objectId
        ];

        $this->_path = [
            'id' => 'cmis:path',
            'localName' => 'path',
            'displayName' => 'Path',
            'queryName' => 'cmis:path',
            'type' => 'string',
            'cardinality' => 'single',
            'value' => $_path,

        ];

        $this->_objectTypeId = [
            'id' => 'cmis:objectTypeId',
            'localName' => 'objectTypeId',
            'displayName' => 'Object Type Id',
            'queryName' => 'cmis:objectTypeId',
            'type' => 'id',
            'cardinality' => 'single',
            'value' => $_objectTypeId
        ];

        $this->_description = [
            'id' => 'cmis:description',
            'localName' => 'description',
            'displayName' => 'Description',
            'queryName' => 'cmis:description',
            'type' => 'string',
            'cardinality' => 'single',
            'value' => $_description
        ];

        $this->_createdBy = [
            'id' => 'cmis:createdBy',
            'localName' => 'createdBy',
            'displayName' => 'Created by',
            'queryName' => 'cmis:createdBy',
            'type' => 'string',
            'cardinality' => 'single',
            'value' => $_createdBy
        ];

        $this->_baseTypeId = [
            'id' => 'cmis:baseTypeId',
            'localName' => 'baseTypeId',
            'displayName' => 'Base Type Id',
            'queryName' => 'cmis:baseTypeId',
            'type' => 'id',
            'cardinality' => 'single',
            'value' => $_baseTypeId
        ];

        $this->_parentId = [
            'id' => 'cmis:parentId',
            'localName' => 'parentId',
            'displayName' => 'Parent Id',
            'queryName' => 'cmis:parentId',
            'type' => 'id',
            'cardinality' => 'single',
            'value' => ($_parentId == 0) ? Utils::createObjectId('/') : $_parentId
        ];

        $this->_creationDate = [
            'id' => 'cmis:creationDate',
            'localName' => 'creationDate',
            'displayName' => 'Creation Date',
            'queryName' => 'cmis:creationDate',
            'type' => 'dateTime',
            'cardinality' => 'single',
            'value' => Utils::formatDateAtom($_creationDate)
        ];

        $this->_changeToken = [
            'id' => 'cmis:changeToken',
            'localName' => 'changeToken',
            'displayName' => 'Change token',
            'queryName' => 'cmis:changeToken',
            'type' => 'string',
            'cardinality' => 'single',
            'value' => $_changeToken
        ];

        $this->_name = [
            'id' => 'cmis:name',
            'localName' => 'name',
            'displayName' => 'Name',
            'queryName' => 'cmis:name',
            'type' => 'string',
            'cardinality' => 'single',
            'value' => $_name
        ];

        $this->_lastModificationDate = [
            'id' => 'cmis:lastModificationDate',
            'localName' => 'lastModificationDate',
            'displayName' => 'Last Modified Date',
            'queryName' => 'cmis:lastModificationDate',
            'type' => 'dateTime',
            'cardinality' => 'single',
            'value' => Utils::formatDateAtom($_lastModificationDate)
        ];

        $this->_lastModifiedBy = [
            'id' => 'cmis:lastModifiedBy',
            'localName' => 'lastModifiedBy',
            'displayName' => 'Last Modified By',
            'queryName' => 'cmis:lastModifiedBy',
            'type' => 'string',
            'cardinality' => 'single',
            'value' => $_lastModifiedBy
        ];
    }

    public function toArray()
    {


        $array = array_merge($this->getOtherPropertiesFormatted(), (array)$this);
        return $array;
    }

    /**
     * @return array
     */
    public function getObjectId()
    {
        return $this->_objectId;
    }

    /**
     * @param array $objectId
     * @return $this
     */
    public function setObjectId($objectId)
    {
        $this->_objectId['value'] = $objectId;
        return $this;
    }

    /**
     * @return array
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @param array $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->_path['value'] = $path;
        return $this;
    }

    /**
     * @return array
     */
    public function getObjectTypeId()
    {
        return $this->_objectTypeId;
    }

    /**
     * @param array $objectTypeId
     * @return $this
     */
    public function setObjectTypeId($objectTypeId)
    {
        $this->_objectTypeId['value'] = $objectTypeId;
        return $this;
    }

    /**
     * @return array
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param array $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->_description['value'] = $description;
        return $this;
    }

    /**
     * @return array
     */
    public function getCreatedBy()
    {
        return $this->_createdBy;
    }

    /**
     * @param array $createdBy
     * @return $this
     */
    public function setCreatedBy($createdBy)
    {
        $this->_createdBy['value'] = $createdBy;
        return $this;
    }

    /**
     * @return array
     */
    public function getBaseTypeId()
    {
        return $this->_baseTypeId;
    }

    /**
     * @param array $baseTypeId
     * @return $this
     */
    public function setBaseTypeId($baseTypeId)
    {
        $this->_baseTypeId['value'] = $baseTypeId;
        return $this;
    }

    /**
     * @return array
     */
    public function getParentId()
    {
        return $this->_parentId;
    }

    /**
     * @param array $parentId
     * @return $this
     */
    public function setParentId($parentId)
    {
        $this->_parentId['value'] = $parentId;
        return $this;
    }

    /**
     * @return array
     */
    public function getCreationDate()
    {
        return $this->_creationDate;
    }

    /**
     * @param array $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->_creationDate['value'] = $creationDate;
        return $this;
    }

    /**
     * @return array
     */
    public function getChangeToken()
    {
        return $this->_changeToken;
    }

    /**
     * @param array $changeToken
     * @return $this
     */
    public function setChangeToken($changeToken)
    {
        $this->_changeToken['value'] = $changeToken;
        return $this;
    }

    /**
     * @return array
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param array $name
     * @return $this
     */
    public function setName($name)
    {
        $this->_name['value'] = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getLastModificationDate()
    {
        return $this->_lastModificationDate;
    }

    /**
     * @param array $lastModificationDate
     * @return $this
     */
    public function setLastModificationDate($lastModificationDate)
    {
        $this->_lastModificationDate['value'] = $lastModificationDate;
        return $this;
    }

    /**
     * @return array
     */
    public function getLastModifiedBy()
    {
        return $this->_lastModifiedBy;
    }

    /**
     * @param array $lastModifiedBy
     * @return $this
     */
    public function setLastModifiedBy($lastModifiedBy)
    {
        $this->_lastModifiedBy['value'] = $lastModifiedBy;
        return $this;
    }

    public static function getById($id)
    {
        $type = Utils::getObjectType($id);

        if ($type == 'document') {
            return self::documentToCMISObjetct(DocumentModel::getById(Utils::readObjectId($id, 'document')));
        } else if ($type == 'folder') {
            return self::folderToCMISObject(FoldersModel::getById(Utils::readObjectId($id, 'folder')));
        } else if ($type == 'workspace') {
            return self::folderToCMISObject(FoldersModel::getById(null));
        } else {
            return new \Exception("Invalid type of object");
        }
    }


    public static function getByPath($path)
    {
        return self::folderToCMISObject(FoldersModel::getByPath($path));
    }

    public static function getAllObjects($objectId = null, $maxItems, $skipCount)
    {
        $conf = parse_ini_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'conf/conf.ini', true);
        $isRootFolder = ($objectId == Utils::createObjectId($conf['CMIS']['rootFolder']));
        $root = null;
        $CMISObject = null;
        $id = Utils::readObjectId($objectId);

        if (Utils::getObjectType($objectId) == "document") {
            $document = DocumentModel::getById($id);
            $array[] = new self($id, $document->getPath(), 'cmis:document', '', $document->getTypist(), 'cmis:document'
                , $document->getFolderUniqueId(), null, null, $document->getFilename());

        } else {
            $folders = ($id == '/') ? DocumentModel::getListWithFolders('', $maxItems, $skipCount) : DocumentModel::getListWithFolders($id, $maxItems, $skipCount);

            if ($isRootFolder) {
                $root = new self($objectId, '/', 'cmis:folder', 'Espace Racine');

            }

            /** @var $folder FoldersModel */
            foreach ($folders as $folder) {
                if ($folder->getType() == 'folder') {

                    $CMISObject = new self($folder->getUniqid(), '/' . $folder->getFolderName(), 'cmis:folder', ''
                        , $folder->getTypist(), 'cmis:folder', $folder->getParentUniqid(), $folder->getCreationDate()
                        , null, $folder->getFolderName(), $folder->getLastModifiedDate(), $folder->getTypist(), $folder->getOtherProperties());


                    if ($folder->count() > 0) {
                        foreach ($folder as $first_level) {

                            if ($first_level->getType() == 'folder') {
                                /** @var  $first_level FoldersModel */


                                $CMISObject2 = new self($first_level->getUniqid(), '/' . $folder->getFolderName() . '/' . $first_level->getFolderName(), 'cmis:folder', ''
                                    , $first_level->getTypist(), 'cmis:folder', $first_level->getParentUniqid()
                                    , $first_level->getCreationDate(), null, $first_level->getFolderName()
                                    , $first_level->getLastModifiedDate(), $first_level->getTypist(), $folder->getOtherProperties());

                                if ($folder->count() > 0) {
                                    foreach ($first_level as $second_level) {
                                        /** @var $second_level DocumentModel */
                                        $CMISObject3 = new self($second_level->getUniqid(), $second_level->getPath(), 'cmis:document', ''
                                            , $second_level->getTypist(), 'cmis:document', $second_level->getFolderUniqueId(), $second_level->getCreationDate(), null
                                            , $second_level->getFilename(), $second_level->getModificationDate(), $second_level->getTypist(), $second_level->getOtherProperties());

                                        $CMISObject2->attach($CMISObject3);
                                    }
                                }

                            } else {
                                /** @var $first_level DocumentModel */
                                $CMISObject2 = new self($first_level->getUniqid(), $first_level->getPath(), 'cmis:document', ''
                                    , $first_level->getTypist(), 'cmis:document', $first_level->getFolderUniqueId(), null, null
                                    , $first_level->getFilename(), $first_level->getModificationDate(), $first_level->getTypist(), $first_level->getOtherProperties());
                            }

                            $CMISObject->attach($CMISObject2);
                        }
                    }
                } else {
                    if ($isRootFolder) {
                        /** @var $folder DocumentModel */
                        $root->attach(self::documentToCMISObjetct($folder));
                    }
                }

                if ($isRootFolder) {
                    $root->attach($CMISObject);
                }
            }
        }

        return ($isRootFolder) ? $root : $CMISObject;
    }

    public function getOtherPropertiesFormatted()
    {
        $array = [];

        if ($this->_otherProperties) {
            foreach ($this->_otherProperties as $key => $property) {
                if (!empty($key)) {

                    if ($property['type'] == 'DateTime' && empty($property['value'])) {
                        //$property['value'] = '0000-00-00T00:00:00+00:00';
                        $property['value'] = date(DATE_ATOM, 0);
                    } else if ($property['type'] == 'DateTime' && !empty($property['value'])) {
                        $property['value'] = Utils::formatDateAtom($property['value']);
                    }

                    array_push($array, [
                        'id' => 'cmis:' . $key,
                        'localName' => $key,
                        'displayName' => $key,
                        'queryName' => 'cmis:' . $key,
                        'type' => $property['type'],
                        'cardinality' => 'single',
                        'value' => $property['value']
                    ]);
                }
            }
        }

        array_push($array, [
            'id' => 'cmis:res_parent',
            'localName' => 'res_parent',
            'displayName' => 'res_parent',
            'queryName' => 'cmis:res_parent',
            'type' => 'Id',
            'cardinality' => 'single',
            'value' => $property['value']
        ]);

        return $array;
    }

    public static function documentToCMISObjetct(DocumentModel $document)
    {
        return new CMISObject($document->getUniqid(), $document->getPath(), 'cmis:document', '', $document->getTypist(), 'cmis:document'
            , $document->getFolderUniqueId(), null, null, $document->getFilename(), $document->getModificationDate(), $document->getTypist(), $document->getOtherProperties());

    }

    public static function folderToCMISObject(FoldersModel $folder)
    {
        return new CMISObject($folder->getUniqid(), '/' . $folder->getFolderName(), 'cmis:folder', ''
            , $folder->getTypist(), 'cmis:folder', $folder->getParentUniqid(), $folder->getCreationDate()
            , null, $folder->getFolderName(), $folder->getLastModifiedDate(), $folder->getTypist(), $folder->getOtherProperties());
    }

}