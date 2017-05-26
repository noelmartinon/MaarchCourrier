<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace Folder\Models;

use CMIS\Models\CMISObject;
use CMIS\Utils\Utils;

class FoldersModel extends FoldersModelAbstract
{
    public function set($param, $value)
    {
        switch ($param) {
            case "description":
                $this->setDescription($value);
                break;
            case "createdBy":
                $this->setTypist($value);
                break;
            case "parentId":
                $this->setParentId($value);
                break;
            case "lastModificationDate":
                $this->setLastModifiedDate($value);
                break;
        }
    }

    public function create()
    {
        $foldertype_id = 5;
        $db = new \Database();

        $creation_date = $db->current_datetime();

        $statement = "insert into folders ( folder_id , foldertype_id , folder_name , creation_date, description, parent_id, folder_level )
                      values ( :folder_id,  :foldertype_id , :folder_name , NOW(), :description, :parent_id, :folder_level)";

        $result = $db->query($statement, [
            ":folder_id" => $this->getFolderName(),
            ":foldertype_id" => $foldertype_id,
            ":folder_name" => $this->getFolderName(),
            ":description" => $this->getDescription(),
            ":parent_id" => $this->getParentId(),
            ":folder_level" => $this->getFolderLevel()
        ]);

        //TODO gerer les cas d erreurs
        if ($result === false) {
            //TODO throw storageException
            echo "<br />ERREUR : création du fichier non réalisée storageException.<br />";
        }

        $lastval = $db->query('SELECT lastval();')->fetch()[0];

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


            $database = new \Database();
            $stmt = $database->query('
            SELECT folders_system_id,folder_id,foldertype_id,parent_id,folder_name,subject,description,
            author,typist,status,folder_level,creation_date,destination, last_modified_date 
            FROM folders 
            WHERE folders_system_id = :id 
            OR parent_id = :id 
            ORDER BY folder_level', [':id' => $id]);

            $value = $stmt->fetch();



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
                ->setLastModifiedDate($value['last_modified_date']);
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

        $database = new \Database();
        $stmt = $database->query('
            SELECT folders_system_id,folder_id,foldertype_id,parent_id,folder_name,subject,description,
            author,typist,status,folder_level,creation_date,destination, last_modified_date 
            FROM folders 
            WHERE folder_name = :path 
            ORDER BY folder_level', [':path' => $path]);

        $value = $stmt->fetch();

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
            ->setLastModifiedDate($value['last_modified_date']);


        return $folder;
    }

    /**
     * @param integer $id
     * @return array
     */
    public static function getFolderTree($id = null)
    {
        $database = new \Database();
        $array = [];

        if ($id) {
            $stmt = $database->query('
            SELECT folders_system_id,folder_id,foldertype_id,parent_id,folder_name,subject,description,
            author,typist,status,folder_level,creation_date,destination, last_modified_date 
            FROM folders 
            WHERE folders_system_id = :id 
            OR parent_id = :id 
            ORDER BY folder_level', [':id' => $id]);
        } else {
            $stmt = $database->query('
            SELECT folders_system_id,folder_id,foldertype_id,parent_id,folder_name,subject,description,
            author,typist,status,folder_level,creation_date,destination, last_modified_date 
            FROM folders 
            ORDER BY folder_level');
        }


        $result = $stmt->fetchAll();

        foreach ($result as $value) {
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
                ->setLastModifiedDate($value['last_modified_date']);

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

    public function hasChildren()
    {
        $database = new \Database();
        $database->query('SELECT ');
    }

    /**
     * @param integer $id
     * @return array
     */
    public static function getFolderTreeAsCMISObject($id = null)
    {
        $database = new \Database();
        $array = [];

        if ($id) {
            $stmt = $database->query('
            SELECT folders_system_id,folder_id,foldertype_id,parent_id,folder_name,subject,description,
            author,typist,status,folder_level,creation_date,destination, last_modified_date 
            FROM folders 
            WHERE folders_system_id = :id 
            OR parent_id = :id 
            ORDER BY folder_level', [':id' => $id]);
        } else {
            $stmt = $database->query('
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


}