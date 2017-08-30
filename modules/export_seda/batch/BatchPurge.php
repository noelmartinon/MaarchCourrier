<?php

require_once __DIR__ . '/../RequestSeda.php';
require_once __DIR__ . '/../class/AbstractMessage.php';
require_once __DIR__ . '/../Purge.php';

new BatchPurge();


Class BatchPurge {
    protected $db;

    public function __construct()
    {
        $this->initSession();
        $this->db = new RequestSeda();
        $this->purge();
    }

    private function initSession()
    {
        $getXml = false;
        $path = '';
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'batch'
            . DIRECTORY_SEPARATOR . 'config'. DIRECTORY_SEPARATOR . 'config.xml'
        )) {
            $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'batch'
                . DIRECTORY_SEPARATOR . 'config'. DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        } else if (file_exists($_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'batch' . DIRECTORY_SEPARATOR . 'config'. DIRECTORY_SEPARATOR . 'config.xml')) {
            $path = $_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'
                . DIRECTORY_SEPARATOR . 'batch' . DIRECTORY_SEPARATOR . 'config'. DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        }

        if ($getXml) {
            $xml = simplexml_load_file($path);
        }

        $_SESSION['config']['databaseserver'] = $xml->CONFIG_BASE->databaseserver;
        $_SESSION['config']['databaseserverport'] = $xml->CONFIG_BASE->databaseserverport;
        $_SESSION['config']['databaseuser'] = $xml->CONFIG_BASE->databaseuser;
        $_SESSION['config']['databasepassword'] = $xml->CONFIG_BASE->databasepassword;
        $_SESSION['config']['databasename'] = $xml->CONFIG_BASE->databasename;
        $_SESSION['config']['databasetype'] = $xml->CONFIG_BASE->databasetype;
        $_SESSION['collection_id_choice'] = $xml->COLLECTION->Id;
        $_SESSION['tablename']['docservers'] = 'docservers';
    }

    private function purge()
    {
        $letters = $this->db->getLettersByStatus('REPLY_SEDA');


        $purge = new Purge();
        foreach ($letters as $letter) {
            $purge->purge($letter->res_id);
        }
    }
}