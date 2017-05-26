<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace CMIS\Models;


use CMIS\Utils\Utils;
use function FastRoute\TestFixtures\empty_options_cached;
use Folder\Models\FoldersModel;

class DocumentModel extends DocumentModelAbstract
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
                $this->setFoldersSystemId($value);
                break;
            case "lastModificationDate":
                $this->setModificationDate($value);
                break;
        }
    }

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
            WHERE res_id = :id', [':id' => $id]);

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

        if(!empty($documents[""])){
            foreach ($documents[""] as $document) {
                array_push($folders, $document);
            }
        }

        return $folders;
    }

    public function create()
    {
        $db = new \Database();
        $statement = "insert into res_letterbox ( subject ,  format , creation_date, path, filename ,status, description, tablename, initiator, destination, typist, type_id, docserver_id, folders_system_id) 
                      values (:subject, :format, CURRENT_TIMESTAMP, :path, :filename, :status, :description, :tablename, :initiator, :destination, :typist, :typeid, 'FASTHD_MAN', :folders_system_id)";

        $result = $db->query($statement, [
            ":subject" => $this->getSubject(),
            ":format" => $this->getFormat(),
            ":path" => $this->getPath(),
            ":typeid" => $this->getTypeId(),
            ":filename" => $this->getFilename(),
            ":status" => "NEW",
            ":description" => $this->getDescription(),
            ":tablename" => "res_letterbox",
            ":initiator" => "VILLE",
            ":destination" => "VILLE",
            ":typist" => $this->getTypist(),
            ":folders_system_id" => $this->getFoldersSystemId()
        ]);

        //TODO gerer les cas d erreurs
        if ($result === false) {
            //TODO throw storageException
            echo "<br />ERREUR : création du fichier non réalisée storageException.<br />";
        }

        $lastval = $db->query('SELECT lastval();')->fetch()[0];

        $this->setResId($lastval);

        return $lastval;
    }

}