<?php

require_once __DIR__ . '/RequestSeda.php';

class CheckAcknowledgement
{
    private $db;
    public function __construct()
    {
        $this->db = new RequestSeda();
    }

    public function checkAttachment($resId) {
        $letter = $this->db->getLetter($resId);
        if ($letter->status != "SEND_SEDA") {
            $_SESSION['error'] = _ERROR_STATUS_SEDA . $resId;
            return false;
        }

        $acknowledgement = $this->db->getAcknowledgement($resId);
        if (!$acknowledgement) {
            $_SESSION['error'] = _ERROR_NO_ACKNOWLEDGEMENT . $resId;
            return false;
        }

        $tabDir = explode('#',$acknowledgement->path);

        $dir = '';
        for ($i = 0; $i < count($tabDir); $i++) {
            $dir .= $tabDir[$i] . DIRECTORY_SEPARATOR;
        }

        $docServer = $this->db->getDocServer($acknowledgement->docserver_id);
        $fileName = $docServer->path_template. DIRECTORY_SEPARATOR . $dir . $acknowledgement->filename;
        $xml = simplexml_load_file($fileName);

        if (!$xml) {
            $_SESSION['error'] = _ERROR_NO_XML_ACKNOWLEDGEMENT . $resId;
            return false;
        }

        $message = $this->db->getMessageByReference($xml->MessageReceivedIdentifier);
        if (!$message) {
            $_SESSION['error'] = _ERROR_NO_REFERENCE_MESSAGE_ACKNOWLEDGEMENT . $resId;
            return false;
        }

        $unitIdentifier = $this->db->getUnitIdentifierByResId($resId);

        if ($unitIdentifier->message_id != $message->message_id) {
            $_SESSION['error'] = _ERROR_WRONG_ACKNOWLEDGEMENT . $resId;
            return false;
        }

        return $resId;
    }
}