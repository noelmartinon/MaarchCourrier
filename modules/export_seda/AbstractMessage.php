<?php

require_once __DIR__ . '/RequestSeda.php';
require_once __DIR__ . '/DOMTemplateProcessor.php';

Class AbstractMessage{

    public function saveXml($messageObject, $name, $extension)
    {
        $DOMTemplate = new DOMDocument();
        $DOMTemplate->load(__DIR__.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.$name.'.xml');
        $DOMTemplateProcessor = new DOMTemplateProcessor($DOMTemplate);
        $DOMTemplateProcessor->setSource($name, $messageObject);
        $DOMTemplateProcessor->merge();
        $DOMTemplateProcessor->removeEmptyNodes();

        try {
            if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'seda2')) {
                mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'seda2', 0777, true);
            }

            if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'seda2' . DIRECTORY_SEPARATOR . $messageObject->messageIdentifier->value)) {
                mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'seda2' . DIRECTORY_SEPARATOR . $messageObject->messageIdentifier->value, 0777, true);
            }

            if (!file_exists(__DIR__.DIRECTORY_SEPARATOR.'seda2'.DIRECTORY_SEPARATOR.$messageObject->messageIdentifier->value.DIRECTORY_SEPARATOR. $messageObject->messageIdentifier->value . $extension)) {
                file_put_contents(__DIR__.DIRECTORY_SEPARATOR.'seda2'.DIRECTORY_SEPARATOR.$messageObject->messageIdentifier->value.DIRECTORY_SEPARATOR. $messageObject->messageIdentifier->value . $extension, $DOMTemplate->saveXML());
            }

        } catch (Exception $e) {
            return false;
        }
    }

    public function addAttachment($reference, $resIdMaster, $fileName, $extension, $title, $type) {
        $db = new RequestSeda();
        $object = new stdClass();
        $dir =  __DIR__.DIRECTORY_SEPARATOR.'seda2'.DIRECTORY_SEPARATOR.$reference.DIRECTORY_SEPARATOR;

        $object->tmpDir = $dir;
        $object->size = filesize($dir);
        $object->format = $extension;
        $object->tmpFileName = $fileName;
        $object->title = $title;
        $object->attachmentType = "simple_attachment";
        $object->resIdMaster = $resIdMaster;

        return $db->insertAttachment($object, $type);
    }

    public function changeStatus($reference, $status) {
        $db = new RequestSeda();
        $message = $db->getMessageByReference($reference);
        $listResId = $db->getUnitIdentifierByMessageId($message->message_id);

        for ($i=0; $i < count($listResId); $i++) {
            $db->updateStatusLetterbox($listResId[$i]->res_id,$status);
        }

        return true;
    }
}