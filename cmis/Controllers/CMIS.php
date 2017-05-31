<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

namespace CMIS\Controllers;

use CMIS\Models\CMISObject;
use CMIS\Models\DocumentModel;
use CMIS\Models\OutputStrategyInterface;
use CMIS\Utils\Utils;
use Folder\Models\FoldersModel;

class CMIS
{
    private
        $_mime_types = [
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];

    public function __construct(OutputStrategyInterface $output)
    {
        $this->_conf = parse_ini_file(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'conf/conf.ini', true);
        $this->output = $output;

        $this->_conf['CMIS']['rootFolderId'] = Utils::createObjectId($this->_conf['CMIS']['rootFolder']);

        $this->output
            ->webroot(Utils::webroot($_SERVER))
            ->loadConfiguration($this->_conf);
    }


    public function query($queryParameters)
    {

        // TODO Need to refactor w/ the WHERE condition

        $objects = [];
        $request = str_ireplace(['cmis:folder', 'cmis:document'], ['folders', 'res_letterbox'], $queryParameters['statement']);
        $db = new \Database();
        $stmt = $db->query($request);
        $results = $stmt->fetchAll();


        if (array_key_exists('res_id', $results[0])) {
            foreach ($results as $result) {
                array_push($objects, new CMISObject(Utils::createObjectId($result['res_id'], 'document'), $result['path'], 'cmis:document', $result['filename'],
                    $result['typist'], 'cmis:document', $result['folders_system_id'], $result['creation_date'],
                    null, $result['filename'], $result['modification_date'], $result['typist'], DocumentModel::getOtherPropertiesArray($stmt, $result)));

            }
        } else if (array_key_exists('folders_system_id', $results[0])) {
            foreach ($results as $result) {
                array_push($objects, new CMISObject(Utils::createObjectId($result['folders_system_id'], 'folder'), $_path = '/', 'cmis:folder', $result['folder_name'],
                    $result['typist'], 'cmis:folder', $result['parent_id'], $result['creation_date'],
                    null, $result['folder_name'], $result['last_modified_date'], $result['typist'], FoldersModel::getOtherPropertiesArray($stmt, $result)));
            }
        }

        http_response_code(201);

        $this
            ->output
            ->query($objects)
            ->render();

        // TODO check why there is an error if 0 result
    }

    public function render()
    {
        $this->output->render();
    }

    public function repository()
    {
        $this->output->repository()->capabilities();
        return $this;
    }

    public function descendants($id)
    {
        $objects = CMISObject::getAllObjects($id);
        $this->output->descendants($objects);
        return $this;
    }

    public function children($id)
    {
        $objects = CMISObject::getAllObjects($id);
        $this->output->children($objects);
        return $this;
    }

    public function createFolder($parent, $queryParameters)
    {
        $folder = new FoldersModel();

        $properties = [];
        foreach ($queryParameters as $key => $queryParameter) {
            if ($key != 'name' && $key != 'objectTypeId') {
                $properties[$key]['value'] = $queryParameter;
            }
        }
        $folder->setOtherProperties($properties);

        if ($parent != '/') {
            $folder
                ->setParentId($parent)
                ->setFolderLevel(2);
        } else {
            $folder->setFolderLevel(1);
        }

        $folder
            ->setCreationDate(date(DATE_ATOM))
            ->setLastModifiedDate(date(DATE_ATOM))
            ->setTypist($_SERVER['PHP_AUTH_USER'])
            ->setFolderName($queryParameters['name'])
            ->create();

        http_response_code(201);

        $this->output->id($folder->getUniqid(), CMISObject::folderToCMISObject($folder), true, 'object')->render();
    }

    public function createDocument($parent, $queryParameters, $content, $base64 = true)
    {

        if ($base64) {
            $document = new DocumentModel();

            $properties = [];
            foreach ($queryParameters as $key => $queryParameter) {
                if ($key != 'name' && $key != 'objectTypeId') {
                    $properties[$key]['value'] = $queryParameter;
                }
            }
            $document->setOtherProperties($properties);


            if ($parent != '/') {
                $document->setFoldersSystemId($parent);
            }


            $extension = pathinfo($queryParameters['name'])['extension'];
            $f = finfo_open();
            $mime_type = finfo_buffer($f, base64_decode($content), FILEINFO_MIME_TYPE);

            $document
                ->setFormat($extension)
                ->setSubject($queryParameters['name'])
                ->setFilename($queryParameters['name'])
                ->setCreationDate(date(DATE_ATOM))
                ->setModificationDate(date(DATE_ATOM))
                ->setTypeId(101)
                ->setTypist($_SERVER['PHP_AUTH_USER'])
                ->setPath("/" . $queryParameters['name']);


            // TODO Uncomment in production
            //if (in_array($extension, $this->_conf['upload']['acceptedType']) && $mime_type == $this->_mime_types[$extension]) {

            file_put_contents($_SESSION['config']['tmppath'] . DIRECTORY_SEPARATOR . $queryParameters['name'], base64_decode($content));

            $document->create();


            if (!empty($queryParameters['res_parent'])) {
                $document->linked($queryParameters['res_parent']);
            }

            // }

            http_response_code(201);

            $this->output->id($document->getUniqid(), CMISObject::documentToCMISObjetct($document), true, 'object')->render();
        }
    }


    public function id($id, $succinct, $selector)
    {
        $object = CMISObject::getById($id);

        $this->output->id($id, $object, $succinct, $selector);
        return $this;
    }

    public function path($id, $path)
    {
        $object = CMISObject::getByPath($path);

        $this->output->id($id, $object, false, null);
        return $this;
    }


    public function renderType($type)
    {
        $this->output->renderType($type);
    }

}