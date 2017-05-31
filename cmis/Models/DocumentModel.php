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

    /**
     * @return array
     */
    public static function getList()
    {
        $array = [];
        $database = new \Database();
        $stmt = $database->query('SELECT * FROM res_letterbox');

        $result = $stmt->fetchAll();

        foreach ($result as $value) {
            $otherProperties = self::getOtherPropertiesArray($stmt, $value);

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
                ->setFilesize($value['filesize'])
                ->setOtherProperties($otherProperties);

            $array[$value['folders_system_id']][] = $document;
        }

        return $array;
    }


    public static function getById($id)
    {
        $database = new \Database();
        $stmt = $database->query('
            SELECT * 
            FROM res_letterbox 
            WHERE res_id = :id', [':id' => $id]);

        $value = $stmt->fetch();
        $otherProperties = self::getOtherPropertiesArray($stmt, $value);
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
            ->setFilesize($value['filesize'])
            ->setOtherProperties($otherProperties);

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

        if (!empty($documents[""])) {
            foreach ($documents[""] as $document) {
                array_push($folders, $document);
            }
        }

        return $folders;
    }

    public function create()
    {

        $db = new \Database();

        if (empty($this->getOtherProperties())) {
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


            $statement = "insert into res_letterbox ( subject ,  format , creation_date, path, filename ,status, description, tablename, initiator, destination, typist, type_id, docserver_id, folders_system_id ," . implode(',', $columns) . " ) 
                      values (:subject, :format, CURRENT_TIMESTAMP, :path, :filename, :status, :description, :tablename, :initiator, :destination, :typist, :typeid, 'FASTHD_MAN', :folders_system_id," . implode(',', $flags) . ")";


            $result = $db->query($statement, array_merge([
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
            ], $queryParameters));
        }
        //TODO gerer les cas d erreurs
        if ($result === false) {
            //TODO throw storageException
            echo "<br />ERREUR : création du fichier non réalisée storageException.<br />";
        }

        $lastval = $db->query('SELECT lastval();')->fetch()[0];

        $this->setResId($lastval);

        return $lastval;
    }

    public static function getOtherPropertiesArray($stmt, $value)
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
    }

    public function linked($parent)
    {
        $db = new \Database();
        $db->query("INSERT INTO res_linked (res_parent, res_child, coll_id) VALUES (:parent, :child, 'letterbox_coll');", [
            ":parent" => Utils::readObjectId($parent, 'document'),
            ":child" => $this->getResId()
        ]);

    }

}