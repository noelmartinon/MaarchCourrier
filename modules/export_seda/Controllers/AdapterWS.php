<?php

require_once __DIR__. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Transfer.php';
require_once __DIR__. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'RequestSeda.php';

class AdapterWS{

    private $db;
    public function __construct()
    {
        $this->db = new RequestSeda();
    }

    public function send($messageObject)
    {
        $transfer = new Transfer();

        $res = $transfer->transfer('maarchcourrier',$messageObject->MessageIdentifier->value);

        if ($res['status'] == 1) {
            $this->db->updateStatusMessage($messageObject->MessageIdentifier->value,'E');
            return $res;
        }

        $this->db->updateStatusMessage($messageObject->MessageIdentifier->value,'S');

        /***** TODO save acknowledgement **/

    }
}