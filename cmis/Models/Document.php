<?php
/**
 * Created by PhpStorm.
 * User: nidextc
 * Date: 10/03/2017
 * Time: 23:08
 */

namespace CMIS\Models;


use CMIS\Utils\Utils;

class Document extends DocumentAbstract
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
            WHERE id => :id', [':id' => $id]);

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

}