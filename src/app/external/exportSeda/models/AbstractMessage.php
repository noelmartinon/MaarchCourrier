<?php


namespace ExportSeda\models;

use ExportSeda\controllers\RequestSeda;
use ExportSeda\controllers\DOMTemplateProcessorController;

class AbstractMessage{

    private $xml;
    private $directoryMessage;
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
        )) {
            $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'xml'
                . DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        } elseif (file_exists(
            $_SESSION['config']['corepath'] . 'modules'
            . DIRECTORY_SEPARATOR . 'export_seda'.  DIRECTORY_SEPARATOR . 'xml'
            . DIRECTORY_SEPARATOR . 'config.xml'
        )) {
            $path = $_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        }

        if ($getXml) {
            $this->xml = simplexml_load_file($path);

            $this->directoryMessage = (string) $this->xml->CONFIG->directoryMessage;
        }
    }

    public function generatePackage($reference, $name)
    {
        $message = $this->db->getMessageByReference($reference);
        $messageObject = json_decode($message->data);

        if (!is_dir($this->directoryMessage)) {
            umask(0);
            mkdir($this->directoryMessage, 0777, true);
        }

        if (!is_dir($this->directoryMessage . DIRECTORY_SEPARATOR . $messageObject->MessageIdentifier->value)) {
            umask(0);
            mkdir($this->directoryMessage . DIRECTORY_SEPARATOR . $messageObject->MessageIdentifier->value, 0777, true);
        }

        $this->sendAttachment($messageObject);

        $this->saveXml($messageObject, $name, ".xml");
    }

    public function saveXml($messageObject, $name, $extension)
    {
        if (isset($messageObject->DataObjectPackage)) {
            if ($messageObject->DataObjectPackage->BinaryDataObject) {
                foreach ($messageObject->DataObjectPackage->BinaryDataObject as $binaryDataObject) {
                    unset($binaryDataObject->Attachment->value);
                }
            }
        }

        $DOMTemplate = new DOMDocument();
        $DOMTemplate->preserveWhiteSpace = false;
        $DOMTemplate->formatOutput = true;
        $DOMTemplate->load(__DIR__ .DIRECTORY_SEPARATOR. '..'. DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.$name.'.xml');
        $DOMTemplateProcessor = new DOMTemplateProcessor($DOMTemplate);
        $DOMTemplateProcessor->setSource($name, $messageObject);
        $DOMTemplateProcessor->merge();
        $DOMTemplateProcessor->removeEmptyNodes();

        try {
            if (!is_dir($this->directoryMessage)) {
                umask(0);
                mkdir($this->directoryMessage, 0777, true);
            }

            if (!is_dir($this->directoryMessage . DIRECTORY_SEPARATOR . $messageObject->MessageIdentifier->value)) {
                umask(0);
                mkdir($this->directoryMessage . DIRECTORY_SEPARATOR . $messageObject->MessageIdentifier->value, 0777, true);
            }

            if (!file_exists($this->directoryMessage . DIRECTORY_SEPARATOR.$messageObject->MessageIdentifier->value.DIRECTORY_SEPARATOR. $messageObject->MessageIdentifier->value . $extension)) {
                $DOMTemplate->save($this->directoryMessage . DIRECTORY_SEPARATOR.$messageObject->MessageIdentifier->value.DIRECTORY_SEPARATOR. $messageObject->MessageIdentifier->value . $extension);
            }

        } catch (Exception $e) {
            return false;
        }
    }

    public function addAttachment($reference, $resIdMaster, $fileName, $extension, $title, $type) {
        $db = new RequestSeda();
        $object = new stdClass();
        $dir =  $this->directoryMessage . DIRECTORY_SEPARATOR . $reference . DIRECTORY_SEPARATOR;

        $object->tmpDir = $dir;
        $object->size = filesize($dir);
        $object->format = $extension;
        $object->tmpFileName = $fileName;
        $object->title = $title;
        $object->attachmentType = "simple_attachment";
        $object->resIdMaster = $resIdMaster;

        return $db->insertAttachment($object, $type);
    }

    private function sendAttachment($messageObject)
    {
        $messageId = $messageObject->MessageIdentifier->value;

        foreach ($messageObject->DataObjectPackage->BinaryDataObject as $binaryDataObject) {
            $dest = $this->directoryMessage . DIRECTORY_SEPARATOR . $messageId . DIRECTORY_SEPARATOR . $binaryDataObject->Attachment->filename;

            if (!file_exists($dest)) {
                copy($binaryDataObject->Uri, $dest);

                unset($binaryDataObject->Uri);
            }
        }

        $this->db->updateDataMessage($messageObject->MessageIdentifier->value, json_encode($messageObject));
    }

    public function addTitleToMessage($reference, $title = ' ')
    {
        $message = $this->db->getMessageByReference($reference);

        $messageObject = json_decode($message->data);

        $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->Title[0] = $title;

        $this->db->updateDataMessage($reference, json_encode($messageObject));

        return true;
    }

    public function changeStatus($reference, $status)
    {
        $message = $this->db->getMessageByReference($reference);
        $listResId = $this->db->getUnitIdentifierByMessageId($message->message_id);

        for ($i=0; $i < count($listResId); $i++) {
            $this->db->updateStatusLetterbox($listResId[$i]->res_id, $status);
        }

        return true;
    }

    public function createPDF($name, $body)
    {
        $pdf = new PDF("p", "pt", "A4");
        $pdf->SetAuthor("MAARCH");
        $pdf->SetTitle($name);

        $pdf->SetFont('times', '', 12);
        $pdf->SetTextColor(50, 60, 100);

        $pdf->AddPage('P');

        $pdf->SetAlpha(1);

        $pdf->MultiCell(0, 10, utf8_decode($body), 0, 'L');

        $dir = $_SESSION['config']['tmppath'] . $name . '.pdf';
        $pdf->Output($dir, "F");
    }
}
