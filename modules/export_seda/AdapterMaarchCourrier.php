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
        $res[0] =  $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value;
        $res[1] = [
            'accept:application/json',
            'content-type:application/json'
        ];

        $res[2] = '';

        $data = '';
        if (is_file($message->file_path)) {
            $data = base64_encode(file_get_contents($message->file_path));
        }

        $res[3] = json_encode($data);

        return $res;
    }
}