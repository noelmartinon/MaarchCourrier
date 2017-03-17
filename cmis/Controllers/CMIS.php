<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */
namespace CMIS\Controllers;

use CMIS\Models\CMISObject;
use CMIS\Models\OutputStrategyInterface;
use CMIS\Utils\Utils;

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
        $this->output->descendants($id);
        return $this;
    }

    public function createDocument($objectId = '', $files = [])
    {
        if (!empty($files) && $files['error'] == UPLOAD_ERR_OK) {
            $extension = pathinfo($files['name'])['extension'];
            $mime_type = mime_content_type($files['tmp_name']);

            if (in_array($extension, $this->_conf['upload']['acceptedType']) && $mime_type == $this->_mime_types[$extension]) {
                move_uploaded_file($files['tmp_name'], 'workspace/' . $files['name']);
            }
        }

        $this->output->createDocument();
    }

    public function createFolder($objectId = '', $name = '')
    {
        $dir = str_replace('//', '/', 'workspace/' . Utils::readObjectId($objectId) . $name);
        error_log($dir);
        if (!file_exists($dir)) {
            mkdir($dir);
        }
    }

    public function id($id, $succinct, $selector)
    {
        $object = new CMISObject($id);

        $this->output->id([$object], $succinct, $selector);
        return $this;
    }

    public function renderType($type)
    {
        $this->output->renderType($type);
    }

}