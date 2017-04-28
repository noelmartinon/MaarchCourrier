<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace CMIS\Models;


use CMIS\Utils\Utils;
use Folder\Models\FoldersModel;

class DocumentModel extends DocumentModelAbstract
{
    /**
     * @return array
     */
    public static function getList()
    {
        $array = [];
        $database = new \Database();
        $stmt = $database->query('SELECT res_id, title, subject, description, type_id, format, typist, creation_date,
          modification_date, folders_system_id, path, filename, filesize FROM res_letterbox');

        $result = $stmt->fetchAll();

        foreach ($result as $value) {
            $document = new self();
            $document
                ->setResId($value['res_id'])
                ->setTitle($value['title'])
                ->setSubject($value['subject'])
                ->setDescription($value['description'])
                ->setTypeId($value['type_id'])
                ->setFormat($value['format'])
                ->setTypist($value['typist'])
                ->setCreationDate($value['creation_date'])
                ->setModificationDate($value['modification_date'])
                ->setFoldersSystemId($value['folders_system_id'])
                ->setPath($value['path'])
                ->setFilename($value['filename'])
                ->setFilesize($value['filesize']);


            $array[$value['folders_system_id']][] = $document;
        }

        return $array;
    }


    public static function getById($id)
    {
        $database = new \Database();
        $stmt = $database->query('
            SELECT res_id, title, subject, description, type_id, format, typist, creation_date,
            modification_date, folders_system_id, path, filename, filesize 
            FROM res_letterbox 
            WHERE res_id => :id', [':id' => $id]);

        $value = $stmt->fetch();

        $document = new self();
        $document
            ->setResId($value['res_id'])
            ->setTitle($value['title'])
            ->setSubject($value['subject'])
            ->setDescription($value['description'])
            ->setTypeId($value['type_id'])
            ->setFormat($value['format'])
            ->setTypist($value['typist'])
            ->setCreationDate($value['creation_date'])
            ->setModificationDate($value['modification_date'])
            ->setFoldersSystemId($value['folders_system_id'])
            ->setPath($value['path'])
            ->setFilename($value['filename'])
            ->setFilesize($value['filesize']);

        return $document;

    }

    public static function getListWithFolders($folder_id)
    {
        $folders = FoldersModel::getFolderTree($folder_id);
        $documents = self::getList();


        //TODO add the documents at the root of the folder
        /**
         * @var $folder FoldersModel
         */

        foreach ($folders as $folder) {
            if (!empty($documents[$folder->getFoldersSystemId()])) {
                foreach ($documents[$folder->getFoldersSystemId()] as $document) {
                    $folder->attach($document);
                }
            }

            foreach ($folder as $child) {
                if (!empty($documents[$child->getFoldersSystemId()]) && method_exists($child, 'attach')) {
                    foreach ($documents[$child->getFoldersSystemId()] as $document) {
                        $child->attach($document);
                    }
                }
            }
        }
        return $folders;
    }

}