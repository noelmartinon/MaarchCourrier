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
    public function create()
    {

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
        $database = new \Database();
        $stmt = $database->query('
            SELECT folders_system_id,folder_id,foldertype_id,parent_id,folder_name,subject,description,
            author,typist,status,folder_level,creation_date,destination, last_modified_date 
            FROM folders 
            WHERE folders_system_id = :id 
            OR parent_id = :id 
            ORDER BY folder_level', [':id' => $id]);

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
                $array[$value['parent_id']]->attach($folder);
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
                , null, $value['folder_name'], $value['last_modified_date'],$value['typist']);


            if ($value['folder_level'] == 1) {
                $array[$value['folders_system_id']] = $CMISObject;
            } else {
                $array[$value['parent_id']]->attach($CMISObject);
            }
        }

        return $array;
    }


}