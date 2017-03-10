<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace Folder\Models;

use CMIS\Utils\Utils;

class FoldersModel extends FoldersModelAbstract
{
    public function create()
    {

    }

    public function delete()
    {

    }

    public static function getById()
    {

    }

    /**
     * @return array of SplObjectStorage
     */
    public static function getFolderTree()
    {
        $database = new \Database();
        $array = [];

        $stmt = $database->query('SELECT folders_system_id,folder_id,foldertype_id,parent_id,folder_name,subject,description,
            author,typist,status,folder_level,creation_date,destination, last_modified_date FROM folders ORDER BY folder_level');

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

            if($value['folder_level'] == 1){
                $array[$value['folders_system_id']] = $folder;
            } else {
                $array[$value['parent_id']]->attach($folder);
            }
        }



        return $array;


    }
}