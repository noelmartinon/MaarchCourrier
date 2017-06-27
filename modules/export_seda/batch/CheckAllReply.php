<?php

require_once __DIR__ . '/../RequestSeda.php';
require_once __DIR__ . '/../AbstractMessage.php';
require_once __DIR__ . '/../CheckReply.php';

$checkAllReply = new CheckAllReply();
$checkAllReply->checkAll();

Class CheckAllReply {
    protected $token;
    protected $SAE;
    protected $db;
    protected $checkReply;

    public function __construct()
    {
        $this->initSession();
        $this->db = new RequestSeda();
        $this->checkReply = new CheckReply();
    }

    private function initSession() {
        $xml = simplexml_load_file(__DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . "config.xml");

        $_SESSION['config']['databaseserver'] = $xml->CONFIG_BASE->databaseserver;
        $_SESSION['config']['databaseserverport'] = $xml->CONFIG_BASE->databaseserverport;
        $_SESSION['config']['databaseuser'] = $xml->CONFIG_BASE->databaseuser;
        $_SESSION['config']['databasepassword'] = $xml->CONFIG_BASE->databasepassword;
        $_SESSION['config']['databasename'] = $xml->CONFIG_BASE->databasename;
        $_SESSION['config']['databasetype'] = $xml->CONFIG_BASE->databasetype;
        $_SESSION['collection_id_choice'] = $xml->COLLECTION->Id;
        $_SESSION['tablename']['docservers'] = 'docservers';
    }
    public function checkAll()
    {
        $abstractMessage = new AbstractMessage();

        $letters = $this->db->getLettersByStatus("ACK_SEDA");

        $unitIdentifiers = [];
        foreach ($letters as $letter) {
            $unitIdentifier = $this->db->getUnitIdentifierByResId($letter->res_id);
            $message = $this->db->getMessageByIdentifier($unitIdentifier->message_id);

            if(array_key_exists($message->reference, $unitIdentifiers)) {
                $unitIdentifiers[$message->reference] .= "," . $unitIdentifier->res_id;
            } else {
                $unitIdentifiers[$message->reference] = $unitIdentifier->res_id;
            }
        }

        foreach ($unitIdentifiers as $key => $value) {
            $messageReplyIdentifier = $key. '_Reply';
            $messageReply = $this->checkReply->getReply($messageReplyIdentifier);

            if (empty($messageReply)) {
                continue;
            }

            $data = json_decode($messageReply[0]->data);
            $this->db->insertMessage($data, "ArchiveTransferReply");
            $abstractMessage->saveXml($data,"ArchiveTransferReply", ".txt");

            $resIds = explode(',',$value);
            foreach ($resIds as $resId) {
                $abstractMessage->addAttachment($messageReplyIdentifier,$resId,$messageReplyIdentifier.".txt","txt","Réponse de transfert",2);
                $this->db->updateStatusLetterbox($resId,"REPLY_SEDA");
            }
        }

        return true;
    }
}