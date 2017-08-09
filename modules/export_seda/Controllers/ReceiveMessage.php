<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Receive Message
 * @author dev@maarch.org
 * @ingroup export_seda
 */

require_once __DIR__ . DIRECTORY_SEPARATOR .'../RequestSeda.php';
require_once __DIR__. DIRECTORY_SEPARATOR .'../Zip.php';

class ReceiveMessage
{

    private $db;

    public function __construct()
    {
        $this->db = new RequestSeda();
    }

    /**
     * @param $messageObject
     * @return bool|mixed
     */
    public function receive($tmpPath, $tmpName)
    {
        $res['status'] = 0;
        $res['content'] = '';


        $zipPathParts = pathinfo($tmpPath. DIRECTORY_SEPARATOR. $tmpName);
        $messageDirectory = $tmpPath . $zipPathParts['filename'];

        $zip = new ZipArchive();
        $zip->open($tmpPath. DIRECTORY_SEPARATOR. $tmpName);
        $zip->extractTo($messageDirectory);

        $messageFileName = '';
        foreach (glob($messageDirectory. DIRECTORY_SEPARATOR. '*.xml') as $filename) {
            $pathParts = pathinfo($filename);
            if(strpos($zipPathParts['filename'],$pathParts['filename'] ) === false) {
                break;
            } else {
                $messageFileName = $filename;
            }
        }

        if (!$messageFileName) {
            $res['status'] = 1;
            $res['content'] = _ERROR_MESSAGE_NOT_PRESENT;

            return $res;
        }

        libxml_use_internal_errors(true);

        $xml = new DOMDocument();
        $xml->load($messageFileName);

        // FORMAT MESSAGE XML
        /*$xsd = __DIR__ . DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'xsd' . DIRECTORY_SEPARATOR . 'seda-2.0-main.xsd';
        if (!$xml->schemaValidate($xsd)){
            $res['status'] = 1;
            $res['content'] = _ERROR_MESSAGE_STRUCTURE_WRONG;

            $this->libxml_display_errors();

            return $res;
        }*/

        // TEST ATTACHMENT
        $listFiles = scandir($messageDirectory);
        $dataObject = simplexml_load_file($messageFileName);
        foreach ($dataObject->DataObjectPackage->BinaryDataObject as $binaryDataObject) {
            $filename = '';
            // ATTACHMENT FILENAME
            $filename = $binaryDataObject->Attachment->attributes();
            if (!array_search($filename, $listFiles)) {
                $res['status'] = 1;
                $res['content'] = _ERROR_ATTACHMENT_FILE_MISSING . ' : ' . $filename;

                return $res;
            }

            // ATTACHMENT BASE 64
            $data = file_get_contents($messageDirectory. DIRECTORY_SEPARATOR . $filename);
            $dataBase64 = base64_encode($data);

            if ($dataBase64 != $binaryDataObject->Attachment) {
                $res['status'] = 1;
                $res['content'] = _ERROR_ATTACHMENT_WRONG_BASE64 . ' : ' . $filename;

                return $res;
            }
        }

        // ARCHIVER AGENCY CONTACT
        if(!$this->db->getEntitiesByBusinessId($dataObject->ArchivalAgency->Identifier)) {
            $res['status'] = 1;
            $res['content'] = _ERROR_CONTACT_UNKNOW . ' : ' . $dataObject->ArchivalAgency->Identifier;

            return $res;
        }

        $res['content'] = json_encode($this->getMessageObject($dataObject));

        return $res;
    }


    private function getMessageObject($dataObject) {
        $messageObject = new stdClass();

        $listComment= array();
        $messageObject->Comment = new stdClass();
        foreach ($dataObject->Comment as $comment) {
            $listComment[]->value = (string) $comment;
        }
        $messageObject->Comment = $listComment;

        $messageObject->MessageIdentifier = new stdClass();
        $messageObject->MessageIdentifier->value = (string) $dataObject->MessageIdentifier;

        $messageObject->Date = (string) $dataObject->Date;

        $messageObject->DataObjectPackage = $this->getDataObjectPackage($dataObject->DataObjectPackage);
        $messageObject->ArchivalAgency = $this->getOrganization($dataObject->ArchivalAgency);
        $messageObject->TransferringAgency = $this->getOrganization($dataObject->TransferringAgency);

        return $messageObject;
    }

    private function getDataObjectPackage($dataObject) {

        $dataObjectPackage = new stdClass();
        $dataObjectPackage->BinaryDataObject = new stdClass();
        $dataObjectPackage->BinaryDataObject = $this->getBinaryDataObject($dataObject->BinaryDataObject);

        $dataObjectPackage->DescriptiveMetadata = new stdClass();
        $dataObjectPackage->DescriptiveMetadata->ArchiveUnit = new stdClass();
        $dataObjectPackage->DescriptiveMetadata->ArchiveUnit = $this->getArchiveUnit($dataObject->DescriptiveMetadata->ArchiveUnit);

        return $dataObjectPackage;
    }

    private function getBinaryDataObject($dataObject) {
        $listBinaryDataObject = array();
        $i = 0;
        foreach ($dataObject as $BinaryDataObject) {

            $listBinaryDataObject[$i]->id = (string) $BinaryDataObject->attributes();

            $listBinaryDataObject[$i]->MessageDigest = new stdClass();
            $listBinaryDataObject[$i]->MessageDigest->value = (string) $BinaryDataObject->MessageDigest;
            $listBinaryDataObject[$i]->MessageDigest->algorithm = (string) $BinaryDataObject->MessageDigest->attributes();

            $listBinaryDataObject[$i]->Size = (string) $BinaryDataObject->Size;

            $listBinaryDataObject[$i]->Attachment = new stdClass();
            $listBinaryDataObject[$i]->Attachment->value = (string) $BinaryDataObject->Attachment;
            foreach ($BinaryDataObject->Attachment->attributes() as $key => $value) {
                if ($key == 'filename') {
                    $listBinaryDataObject[$i]->Attachment->filename = (string) $value;
                } elseif ($key == 'uri') {
                    $listBinaryDataObject[$i]->Attachment->uri = (string) $value;
                }
            }

            $listBinaryDataObject[$i]->FormatIdentification = new stdClass();
            $listBinaryDataObject[$i]->FormatIdentification->MimeType = (string) $BinaryDataObject->FormatIdentification->MimeType;
            $i++;
        }

        return $listBinaryDataObject;
    }
    
    private function getArchiveUnit($dataObject) {
        $listArchiveUnit = array();
        $i =0;
        foreach ($dataObject as $ArchiveUnit) {
            $listArchiveUnit[$i]->id = (string) $ArchiveUnit->attributes();
            $listArchiveUnit[$i]->Content = new stdClass();
            $listArchiveUnit[$i]->Content->DescriptionLevel = (string) $ArchiveUnit->Content->DescriptionLevel;

            $listArchiveUnit[$i]->Content->Title = array();
            foreach ($ArchiveUnit->Content->Title as $title) {
                $listArchiveUnit[$i]->Content->Title[] = (string) $title;
            }

            $listArchiveUnit[$i]->Content->OriginatingSystemId = (string) $ArchiveUnit->Content->OriginatingSystemId;
            $listArchiveUnit[$i]->Content->OriginatingAgencyArchiveUnitIdentifier = (string) $ArchiveUnit->Content->OriginatingAgencyArchiveUnitIdentifier;
            $listArchiveUnit[$i]->Content->DocumentType = (string) $ArchiveUnit->Content->DocumentType;
            $listArchiveUnit[$i]->Content->Status = (string) $ArchiveUnit->Content->Status;
            $listArchiveUnit[$i]->Content->CreatedDate = (string) $ArchiveUnit->Content->CreatedDate;

            if ($ArchiveUnit->Content->Writer) {
                $listArchiveUnit[$i]->Content->Writer = array();
                $j = 0;
                foreach ($ArchiveUnit->Content->Writer as $Writer) {
                    $listArchiveUnit[$i]->Content->Writer[$j]->FirstName = (string)$Writer->FirstName;
                    $listArchiveUnit[$i]->Content->Writer[$j]->BirthName = (string)$Writer->BirthName;
                    $j++;
                }
            }

            if ($ArchiveUnit->DataObjectReference) {
                $listArchiveUnit[$i]->DataObjectReference = array();
                $j = 0;
                foreach ($ArchiveUnit->DataObjectReference as $DataObjectReference) {
                    $listArchiveUnit[$i]->DataObjectReference[$j]->DataObjectReferenceId = (string) $DataObjectReference->DataObjectReferenceId;
                    $j++;
                }
            }

            if ($ArchiveUnit->ArchiveUnit) {
                $listArchiveUnit[$i]->ArchiveUnit = $this->getArchiveUnit($ArchiveUnit->ArchiveUnit);
            }

            $i++;
        }
        return $listArchiveUnit;
    }

    private function getOrganization($dataObject) {
        $organization= new stdClass();

        $organization->Identifier = new stdClass();
        $organization->Identifier->value = (string) $dataObject->Identifier;

        $organization->OrganizationDescriptiveMetadata = new stdClass();

        if ($dataObject->OrganizationDescriptiveMetadata->LegalClassification) {
            $organization->OrganizationDescriptiveMetadata->LegalClassification = (string) $dataObject->OrganizationDescriptiveMetadata->LegalClassification;
        }

        if ($dataObject->OrganizationDescriptiveMetadata->Name) {
            $organization->OrganizationDescriptiveMetadata->Name = (string) $dataObject->OrganizationDescriptiveMetadata->Name;
        }

        if ($dataObject->OrganizationDescriptiveMetadata->Communication) {
            $organization->OrganizationDescriptiveMetadata->Communication = $this->getCommunication($dataObject->OrganizationDescriptiveMetadata->Communication);
        }

        if ($dataObject->OrganizationDescriptiveMetadata->Contact) {
            $organization->OrganizationDescriptiveMetadata->Contact = $this->getContact($dataObject->OrganizationDescriptiveMetadata->Contact);
        }

        return $organization;
    }

    private function getCommunication($dataObject) {
        $listCommunication = array();
        $i=0;
        foreach ($dataObject as $Communication) {
            $listCommunication[$i]->Channel = (string) $Communication->Channel;
            $listCommunication[$i]->value = (string) $Communication->CompleteNumber;
            $i++;
        }

        return $listCommunication;
    }

    private function getAddress($dataObject) {
        $listAddress = array();
        $i=0;
        foreach ($dataObject as $Address) {
            $listAddress[$i]->CityName = (string) $Address->CityName;
            $listAddress[$i]->Country = (string) $Address->Country;
            $listAddress[$i]->Postcode = (string) $Address->Postcode;
            $listAddress[$i]->PostOfficeBox = (string) $Address->PostOfficeBox;
            $listAddress[$i]->StreetName = (string) $Address->StreetName;
            $i++;
        }

        return $listAddress;
    }

    private function getContact($dataObject) {
        $listContact = array();
        $i=0;
        foreach ($dataObject as $Contact) {
            $listContact[$i]->DepartmentName = (string) $Contact->DepartmentName;
            $listContact[$i]->PersonName = (string) $Contact->PersonName;

            if ($Contact->Communication) {
                $listContact[$i]->Communication = $this->getCommunication($Contact->Communication);
            }

            if ($Contact->Address) {
                $listContact[$i]->Address = $this->getAddress($Contact->Address);
            }
            $i++;
        }

        return $listContact;
    }

    private function libxml_display_error($error)
    {
        $return = "<br/>\n";
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "<b>Warning $error->code</b>: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "<b>Error $error->code</b>: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "<b>Fatal Error $error->code</b>: ";
                break;
        }
        $return .= trim($error->message);
        if ($error->file) {
            $return .=    " in <b>$error->file</b>";
        }
        $return .= " on line <b>$error->line</b>\n";

        return $return;
    }

    private function libxml_display_errors()
    {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            //var_dump($this->libxml_display_error($error));
        }
        libxml_clear_errors();
    }
}