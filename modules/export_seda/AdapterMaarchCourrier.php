<?php

require_once __DIR__. DIRECTORY_SEPARATOR. 'RequestSeda.php';

class AdapterMaarchCourrier{

    private $db;
    public function __construct()
    {
        $this->db = new RequestSeda();
    }

    public function getInformations($reference)
    {
        $res = []; // [0] = url, [1] = header, [2] = cookie, [3] = data

        $message = $this->db->getMessageByReference($reference);

        $messageObject = json_decode($message->data);

        $pathParts = pathinfo($message->file_path);
        $res[0] =  $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value
            . '?base64='. urlencode(base64_encode(file_get_contents($message->file_path)))
            . '&extension='. $pathParts['extension']
            . '&size='. filesize($message->file_path);

        $res[1] = [
            'accept:application/json',
            'content-type:application/json'
        ];

        $res[2] = '';

        $res[3] = '';

        return $res;
    }
}