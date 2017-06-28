<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Export seda transfer
* @author dev@maarch.org
* @ingroup export_seda
*/
require_once __DIR__ .'/class/Acknowledgement.php';
require_once __DIR__ . '/class/AbstractMessage.php';
require_once __DIR__ . '/Transfer.php';

$status = 0;
$error = $content = '';
if ($_REQUEST['reference']) {
    $transferToSAE = new TransferToSAE();
    $res = $transferToSAE->send($_REQUEST['reference']);
    $status = $res['status'];
    if ($status != 0) {
        $error = $res['error'];
    } else {
        $content = $res['content'];
    }
} else {
    $status = 1;
}

    echo "{status : " . $status . ", content : '" . addslashes($content) . "', error : '" . addslashes($error) . "'}";
    exit ();

class TransferToSAE
{
    protected $xml;

    public function __construct()
    {
        $this->xml = simplexml_load_file(__DIR__.DIRECTORY_SEPARATOR. 'xml' . DIRECTORY_SEPARATOR . "config.xml");
    }

    public function send($reference)
    {
        $abstractMessage = new AbstractMessage();
        $res = [];
        $res['status'] = 0;
        $res['content'] = _RECEIVED_MESSAGE;

        $transfer = new Transfer();
        $dataTransfer = $transfer->transfer(strtolower($this->xml->CONFIG->sae),$reference);

        if ($dataTransfer['status'] == 1) {
            $res['status'] = 1;
            $res['error']= _ERROR_MESSAGE. ' '. _ERR. ' : ' . $dataTransfer['content'];
        } else {
            $abstractMessage->changeStatus($reference, 'SEND_SEDA');
            $acknowledgement = new Acknowledgement();
            $resIds = explode(',',$_REQUEST['resIds']);
            $acknowledgement->send($dataTransfer['content'], $resIds);
            $abstractMessage->changeStatus($reference, 'ACK_SEDA');
            $res['content'] .= $reference;
        }
        
        return $res;
    }
}
