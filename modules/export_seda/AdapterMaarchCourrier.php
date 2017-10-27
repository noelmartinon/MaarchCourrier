<?php

require_once __DIR__. DIRECTORY_SEPARATOR. 'RequestSeda.php';
require_once "core/Models/DocserverModel.php";
require_once "core/Models/DocserverTypeModel.php";
require_once "core/Controllers/DocserverToolsController.php";

class AdapterMaarchCourrier{

    private $db;
    public function __construct()
    {
        $this->db = new RequestSeda();
    }

    public function getInformations($messageId, $type)
    {
        $res = []; // [0] = url, [1] = header, [2] = cookie, [3] = data

        $message = $this->db->getMessageByIdentifier($messageId);

        $messageObject = json_decode($message->data);

        $docserver     = \Core\Models\DocserverModel::getById(['docserver_id' => $message->docserver_id]);
        $docserverType = \Core\Models\DocserverTypeModel::getById(['docserver_type_id' => $docserver[0]['docserver_type_id']]);

        $pathDirectory = str_replace('#', DIRECTORY_SEPARATOR, $message->path);
        $filePath      = $docserver[0]['path_template'] . $pathDirectory . $message->filename;
        $fingerprint   = \Core\Controllers\DocserverToolsController::doFingerprint([
            'path'            => $filePath,
            'fingerprintMode' => $docserverType[0]['fingerprint_mode'],
        ]);

        if($fingerprint['fingerprint'] != $message->fingerprint){
            echo _PB_WITH_FINGERPRINT_OF_DOCUMENT;exit;
        }

        $pathParts = pathinfo($filePath);
        $res[0] =  $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value
            . '?extension='. $pathParts['extension']
            . '&size='. filesize($filePath)
            . '&type='. $type;

        $res[1] = [
            'accept:application/json',
            'content-type:application/json'
        ];

        $res[2] = '';

        $postData = new stdClass();
        $postData->base64 = base64_encode(file_get_contents($filePath));

        $res[3] = json_encode($postData);

        return $res;
    }
}
