<?php

require_once 'vendor/autoload.php';
require_once 'apps/maarch_entreprise/Models/ContactsModel.php';
require_once __DIR__.'/RequestSeda.php';

Class Purge{
    protected $xml;
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
        } else if (file_exists($_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'.  DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml')) {
            $path = $_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        }

        if ($getXml) {
            $this->xml = simplexml_load_file($path);
        }

        $this->deleteData = (string) $this->xml->CONFIG->deleteData;
    }

    public function purge($resId) {
        $reply = $this->db->getReply($resId);
        if (!$reply) {
            $_SESSION['error'] = _ERROR_NO_REPLY . $resId;
            return false;
        }

        $tabDir = explode('#',$reply->path);

        $dir = '';
        for ($i = 0; $i < count($tabDir); $i++) {
            $dir .= $tabDir[$i] . DIRECTORY_SEPARATOR;
        }

        $docServer = $this->db->getDocServer($reply->docserver_id);
        $fileName = $docServer->path_template. DIRECTORY_SEPARATOR . $dir . $reply->filename;
        $xml = simplexml_load_file($fileName);

        if ($xml->ReplyCode != "000") {
            $_SESSION['error'] = _LETTER_NO_ARCHIVED. $resId;
            return false;
        }
        $letter = $this->db->getLetter($resId);
        $message = $this->db->getMessageByReference($xml->MessageRequestIdentifier);

        $this->db->deleteUnitIdentifier($resId);
        $this->purgeResource($resId);
        $this->purgeContact($letter->contact_id);

        $unitIdentifiers = $this->db->getUnitIdentifierByMessageId($message->message_id);
        if (!$unitIdentifiers) {
            $this->db->deleteMessage($message->message_id);
        }

        return $resId;
    }

    private function purgeResource($resId)
    {
        $action = new \Core\Controllers\ResController();
        $data = [];

        array_push($data, array(
            'column' => 'status',
            'value' => 'DEL',
            'type' => 'string'
        ));

        $aArgs = [
            'table' => 'res_letterbox',
            'res_id'=> $resId,
            'data'  => $data
        ];

        $response = $action->updateResource($aArgs);

        return $response;
    }

    private function purgeContact($contactId)
    {
        $contacts = new \ContactsModel();
        $contactDetails = $contacts->purgeContact([
            'id'=>$contactId
        ]);
    }
}