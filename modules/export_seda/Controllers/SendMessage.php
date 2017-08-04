<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Send Message
 * @author dev@maarch.org
 * @ingroup export_seda
 */

require_once 'modules/export_seda/RequestSeda.php';
require_once __DIR__ . DIRECTORY_SEPARATOR .'../DOMTemplateProcessor.php';
require_once __DIR__. DIRECTORY_SEPARATOR .'../Zip.php';
require_once __DIR__. DIRECTORY_SEPARATOR . '/AdapterWS.php';
require_once __DIR__. DIRECTORY_SEPARATOR . '/AdapterEmail.php';

class SendMessage {

    private $db;

    public function __construct()
    {
        $this->db = new RequestSeda();
    }

    public function send($messageObject)
    {
        $channel = $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->Channel;
        $communicationValue = $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->value;

        if ($channel == 'url') {
            $adapterWS = new AdapterWS();
            //$adapterWS->send()
        } elseif ($channel == 'email') {
            $adapterEmail = new AdapterEmail();
            $adapterEmail->send($communicationValue,$messageObject->MessageIdentifier->value);
        } else {
            return false;
        }
    }

    public function generateMessageFile($messageObject, $type)
    {
        $DOMTemplate = new DOMDocument();
        $DOMTemplate->load(__DIR__ .DIRECTORY_SEPARATOR. '..'. DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.$type.'.xml');
        $DOMTemplateProcessor = new DOMTemplateProcessor($DOMTemplate);
        $DOMTemplateProcessor->setSource($type, $messageObject);
        $DOMTemplateProcessor->merge();
        $DOMTemplateProcessor->removeEmptyNodes();

        file_put_contents($_SESSION['config']['tmppath'] . $messageObject->MessageIdentifier->value . ".xml", $DOMTemplate->saveXML());

        foreach ($messageObject->DataObjectPackage->BinaryDataObject as $binaryDataObject) {
            $base64_decoded = base64_decode($binaryDataObject->Attachment->value);
            $file = fopen($_SESSION['config']['tmppath'] . $binaryDataObject->Attachment->filename, 'w');
            fwrite($file,$base64_decoded);
            fclose($file);
        }
        $filename = $this->generateZip($messageObject,$DOMTemplate);

        return $filename;
    }

    private function generateZip($messageObject)
    {
        $zip = new ZipArchive();
        $filename = $_SESSION['config']['tmppath'].$messageObject->MessageIdentifier->value. ".zip";

        $zip->open($filename, ZipArchive::CREATE);

        $zip->addFile($_SESSION['config']['tmppath'] . $messageObject->MessageIdentifier->value . ".xml", $messageObject->MessageIdentifier->value . ".xml");

        foreach ($messageObject->DataObjectPackage->BinaryDataObject as $binaryDataObject) {
            $zip->addFile($_SESSION['config']['tmppath'] . $binaryDataObject->Attachment->filename, $binaryDataObject->Attachment->filename);
        }

        return $filename;
    }
}