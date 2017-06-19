<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace Folder\Models;

use CMIS\Models\CMISObject;
use CMIS\Utils\Utils;
use CMIS\Utils\Database;

class FoldersModel extends FoldersModelAbstract
{

    public function create()
    {
        $foldertype_id = 5;

        $creation_date = Database::getInstance()->current_datetime();


        if (empty($this->getOtherProperties())) {
            $statement = "insert into folders ( folder_id , foldertype_id , folder_name , creation_date, description, parent_id, folder_level )
                      values ( :folder_id,  :foldertype_id , :folder_name , NOW(), :description, :parent_id, :folder_level)";

            $result = Database::getInstance()->query($statement, [
                ":folder_id" => $this->getFolderName(),
                ":foldertype_id" => $foldertype_id,
                ":folder_name" => $this->getFolderName(),
                ":description" => $this->getDescription(),
                ":parent_id" => $this->getParentId(),
                ":folder_level" => $this->getFolderLevel()
            ]);
        } else {
            $columns = [];
            $flags = [];
            $queryParameters = [];
            foreach ($this->getOtherProperties() as $key => $property) {
                if ($key != 'res_parent') {
                    $columns[] = $key;
                    $flags[] = ':' . $key;
                    $queryParameters[':' . $key] = $property;
                }
            }

            $statement = "insert into folders ( folder_id , foldertype_id , folder_name , creation_date, description, parent_id, folder_level ," . implode(',', $columns) . " )
                      values ( :folder_id,  :foldertype_id , :folder_name , NOW(), :description, :parent_id, :folder_level," . implode(',', $flags) . ")";

            $result = Database::getInstance()->query($statement, array_merge([
                ":folder_id" => $this->getFolderName(),
                ":foldertype_id" => $foldertype_id,
                ":folder_name" => $this->getFolderName(),
                ":description" => $this->getDescription(),
                ":parent_id" => $this->getParentId(),
                ":folder_level" => $this->getFolderLevel()
            ], $queryParameters));
        }

        //TODO gerer les cas d erreurs
        if ($result === false) {
            //TODO throw storageException
            echo "<br />ERREUR : création du fichier non réalisée storageException.<br />";
        }

        $lastval = Database::getInstance()->lastInsertId('folders_system_id_seq');

        $this->setFoldersSystemId($lastval);

        return $lastval;
    }

    public function delete()
    {

    }

    /**
     * @param integer $id
     * @return FoldersModel
     */
    public static function getById($id = null)
    {
        $folder = new self();

        if (!empty($id)) {

            $stmt = Database::getInstance()->query('
            SELECT *
            FROM folders 
            WHERE folders_system_id = :id 
            OR parent_id = :id 
            ORDER BY folder_level', [':id' => $id]);

            $value = $stmt->fetch();

            $otherProperties = Database::getOtherPropertiesArray($value);
            $folder
                ->setFoldersSystemId($value['folders_system_id'])
                ->setFolderId($value['folder_id'])
                ->setFoldertypeId($value['foldertype_id'])
                ->setParentId($value['parent_id'])
                ->setFolderName($value['folder_name'])
                ->setSubject($value['subject'])
                ->setDescription($value['description'])
                ->setAuthor($value['author'])
                ->setTypist($value['typist'])
                ->setStatus($value['status'])
                ->setFolderLevel($value['folder_level'])
                ->setCreationDate($value['creation_date'])
                ->setLastModifiedDate($value['last_modified_date'])
                ->setOtherProperties($otherProperties);
        } else {
            $folder->setUniqid(Utils::createObjectId('/'), true);
        }

        return $folder;
    }

    /**
     * @param string $path
     * @return FoldersModel
     */
    public static function getByPath($path = "/")
    {

        if (preg_match("/^\//", $path)) {
            $path = substr($path, 1);
        }

        $stmt = Database::getInstance()->query('
            SELECT *
            FROM folders 
            WHERE folder_name = :path 
            ORDER BY folder_level', [':path' => $path]);

        $value = $stmt->fetch();

        $otherProperties = self::getOtherPropertiesArray($value);
        $folder = new self();

        $folder
            ->setFoldersSystemId($value['folders_system_id'])
            ->setFolderId($value['folder_id'])
            ->setFoldertypeId($value['foldertype_id'])
            ->setParentId($value['parent_id'])
            ->setFolderName($value['folder_name'])
            ->setSubject($value['subject'])
            ->setDescription($value['description'])
            ->setAuthor($value['author'])
            ->setTypist($value['typist'])
            ->setStatus($value['status'])
            ->setFolderLevel($value['folder_level'])
            ->setCreationDate($value['creation_date'])
            ->setLastModifiedDate($value['last_modified_date'])
            ->setOtherProperties($otherProperties);


        return $folder;
    }

    /**
     * @param integer $id
     * @return array
     */
    public static function getFolderTree($id = null)
    {
        $array = [];

        if ($id) {
            $stmt = Database::getInstance()->query('
            SELECT *
            FROM folders 
            WHERE folders_system_id = :id 
            OR parent_id = :id 
            ORDER BY folder_level', [':id' => $id]);
        } else {
            $stmt = Database::getInstance()->query('
            SELECT * 
            FROM folders 
            ORDER BY folder_level');
        }


        $result = $stmt->fetchAll();


        foreach ($result as $value) {
            $folder = new self();

            $otherProperties = Database::getOtherPropertiesArray($value);

            $folder
                ->setFoldersSystemId($value['folders_system_id'])
                ->setFolderId($value['folder_id'])
                ->setFoldertypeId($value['foldertype_id'])
                ->setParentId($value['parent_id'])
                ->setFolderName($value['folder_name'])
                ->setSubject($value['subject'])
                ->setDescription($value['description'])
                ->setAuthor($value['author'])
                ->setTypist($value['typist'])
                ->setStatus($value['status'])
                ->setFolderLevel($value['folder_level'])
                ->setCreationDate($value['creation_date'])
                ->setLastModifiedDate($value['last_modified_date'])
                ->setOtherProperties($otherProperties);

            if ($value['folder_level'] == 1) {
                $array[$value['folders_system_id']] = $folder;
            } else {
                if ($array[$value['parent_id']]) {
                    $array[$value['parent_id']]->attach($folder);
                }
            }
        }

        return $array;
    }


    /**
     * @param integer $id
     * @return array
     */
    public static function getFolderTreeAsCMISObject($id = null)
    {
        $array = [];

        if ($id) {
            $stmt = Database::getInstance()->query('
            SELECT folders_system_id,folder_id,foldertype_id,parent_id,folder_name,subject,description,
            author,typist,status,folder_level,creation_date,destination, last_modified_date 
            FROM folders 
            WHERE folders_system_id = :id 
            OR parent_id = :id 
            ORDER BY folder_level', [':id' => $id]);
        } else {
            $stmt = Database::getInstance()->query('
            SELECT folders_system_id,folder_id,foldertype_id,parent_id,folder_name,subject,description,
            author,typist,status,folder_level,creation_date,destination, last_modified_date 
            FROM folders 
            ORDER BY folder_level');
        }


        $result = $stmt->fetchAll();

        foreach ($result as $value) {


            $CMISObject = new CMISObject(bin2hex('folder_' . $value['folders_system_id']), '/', 'cmis:folder', ''
                , $value['typist'], 'cmis:folder', bin2hex('folder_' . $value['parent_id']), $value['creation_date']
                , null, $value['folder_name'], $value['last_modified_date'], $value['typist']);


            if ($value['folder_level'] == 1) {
                $array[$value['folders_system_id']] = $CMISObject;
            } else {
                $array[$value['parent_id']]->attach($CMISObject);
            }
        }

        return $array;
    }

    // CANNOT WORKS WITH ORACLE
    /*public static function getOtherPropertiesArray($stmt, $value)
    {
        $otherProperties = [];
        $size = sizeof($value);
        for ($i = 0; $i < $size; $i++) {
            $meta = $stmt->getColumnMeta($i);
            if (!empty($meta['name'])) {
                if ($meta['native_type'] == 'int2' || $meta['native_type'] == 'int4' || $meta['native_type'] == 'int8' || $meta['native_type'] == 'int16' || $meta['native_type'] == 'numeric') {
                    $type = 'Id';
                } else if ($meta['native_type'] == 'varchar' || $meta['native_type'] == 'bpchar' || $meta['native_type'] == 'text') {
                    $type = 'String';
                } else if ($meta['native_type'] == 'date' || $meta['native_type'] == 'timestamp') {
                    $type = 'DateTime';
                } else {
                    $type = $meta['native_type'];
                }

                $otherProperties[$meta['name']] = [
                    "type" => $type,
                    "value" => $value[$meta['name']]
                ];
            }
        }

        return $otherProperties;
    }*/
}