<?php

require_once __DIR__. DIRECTORY_SEPARATOR. 'RequestSeda.php';

class AdapterMaarchRM{
    private $xml;
    private $db;

    public function __construct()
    {
        $this->db = new RequestSeda();
        $getXml = false;
        $path = '';
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'xml'
            . DIRECTORY_SEPARATOR . 'config.xml'
        ))
        {
            $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'xml'
                . DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        } elseif (file_exists($_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'.  DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml')) {
            $path = $_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        }

        if ($getXml) {
            $this->xml = simplexml_load_file($path);
        }
    }

    public function getInformations($messageId)
    {
        $message = $this->db->getMessageByIdentifier($messageId);
        $reference = $message->reference;
        $res = []; // [0] = url, [1] = header, [2] = cookie, [3] = data

        $res[0] =  (string) $this->xml->CONFIG->urlSAEService. "/medona/Archivetransfer";
        $res[1] = [
            'accept:application/json',
            'content-type:application/json',
            'user-agent:maarchrestclient'
        ];

        $token = urlencode((string)$this->xml->CONFIG->token);
        $res[2] = "LAABS-AUTH=".$token;

        $data = new stdClass();
        $messageDirectory = (string) $this->xml->CONFIG->directoryMessage.DIRECTORY_SEPARATOR.$reference;
        $messageFile = $reference.".xml";

        $files = scandir($messageDirectory);
        foreach ($files as $file) {
            if ($file != $messageFile && $file != ".." && $file != ".") {
                $attachment = new stdClass;
                $attachment->data = base64_encode(
                    file_get_contents($messageDirectory . DIRECTORY_SEPARATOR . $file)
                );
                $attachment->filename = $file;

                $data->attachments[] = $attachment;
            }
        }

        $data->messageFile = base64_encode(
            file_get_contents($messageDirectory . DIRECTORY_SEPARATOR . $reference.".xml")
        );

        $res[3] = json_encode($data);

        return $res;
    }
}
