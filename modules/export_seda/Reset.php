<?php

require_once __DIR__.'/RequestSeda.php';

Class Reset{
    public function __construct()
    {
        $this->db = new RequestSeda();
    }

    public function reset($resId) {
        $reply = $this->db->getReply($resId);

        $tabDir = explode('#',$reply->path);

        $dir = '';
        for ($i = 0; $i < count($tabDir); $i++) {
            $dir .= $tabDir[$i] . DIRECTORY_SEPARATOR;
        }

        $docServer = $this->db->getDocServer($reply->docserver_id);
        $fileName = $docServer->path_template. DIRECTORY_SEPARATOR . $dir . $reply->filename;
        $xml = simplexml_load_file($fileName);


        if ((string) $xml->ReplyCode == "000") {
            $_SESSION['error'] = _ERROR_LETTER_ARCHIVED. $resId;
            return false;
        }

        // Change status letter
        $this->db->updateStatusLetterbox($resId,'END');
        // Del attachment
        $this->db->updateStatusAttachment($resId,'DEL');
        // Del unitIdentifier
        $this->db->deleteUnitIdentifier($resId);

        // Del message
        $message = $this->db->getMessageByReference((string) $xml->MessageRequestIdentifier);
        $messageReply = $this->db->getMessageByReference((string) $xml->MessageIdentifier);

        $this->db->deleteMessage($message->message_id);
        $this->db->deleteMessage($messageReply->message_id);

        return true;
    }
}