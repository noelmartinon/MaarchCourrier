<?php

class AdapterMaarchRM{
    private $xml;

    public function __construct()
    {
        $this->xml = simplexml_load_file(__DIR__.DIRECTORY_SEPARATOR. 'xml' . DIRECTORY_SEPARATOR . "config.xml");
    }

    public function getInformations($reference) {
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
        $messageDirectory = __DIR__.DIRECTORY_SEPARATOR.'message'.DIRECTORY_SEPARATOR.$reference;
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