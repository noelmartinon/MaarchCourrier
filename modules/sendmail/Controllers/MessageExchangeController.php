<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Resource Controller
* @author dev@maarch.org
* @ingroup core
*/

require_once 'apps/maarch_entreprise/Models/ContactsModel.php';
require_once 'apps/maarch_entreprise/Models/ResModel.php';
require_once 'modules/export_seda/RequestSeda.php';

class MessageExchangeController
{

    public static function createMessageExchange($aArgs = [])
    {
        $errors = self::control($aArgs);

        if(!empty($errors)){
            return ['errors' => $errors];
        }
        $mlbCollExt = ResModel::getMlbCollExtById(['resId' => $aArgs['identifier']]);
        if(empty($mlbCollExt)){
            return ['errors' => "wrong identifier"];
        }

        if(empty($mlbCollExt['exp_contact_id']) && empty($mlbCollExt['dest_contact_id'])){
            return ['errors' => "no contact"];
        }

        if($mlbCollExt['exp_contact_id'] != null){
            $contact_id = $mlbCollExt['exp_contact_id'];
        } else {
            $contact_id = $mlbCollExt['dest_contact_id'];
        }

        $ArchivalAgencyCommunicationType   = ContactsModel::getContactCommunication([
                                                'contactId' => $contact_id, 
                                                'allValues' => true
                                            ]);

        $ArchivalAgencyContactInformations = ContactsModel::getFullAddressById(['addressId' => $mlbCollExt['address_id']]);
        $TransferringAgencyInformations    = \Entities\Models\EntitiesModel::getById(['entityId' => $_SESSION['user']['primaryentity']['id']]);
        $AllInfoMainMail                   = ResModel::getById(['resId' => $aArgs['identifier']]);


        if(!empty($aArgs['join_file'])){
            $fileInfo = [$AllInfoMainMail];
        }
        if(!empty($aArgs['join_attachment'])){
            $AttachmentsInfo = \Attachments\Models\AttachmentsModel::getAttachmentsWithOptions(['where' => ['res_id in (?)'], 'data' => $aArgs['join_attachment']]);
        }
        // if(!empty($aArgs['join_version'])){
        //     $AttachmentsVersionInfo = \Attachments\Models\AttachmentsModel::getAttachmentsWithOptions(['where' => ['res_id_version in (?)'], 'data' => $aArgs['join_version']]);
        // }
        // if(!empty($aArgs['notes'])){          // TODO !!
        //     $NotesInfo = $AllInfoMainMail;
        // }

        $return = self::generateMessageObject([
            'Comment' => [$aArgs['body_from_raw']],
            'ArchivalAgency' => [
                'CommunicationType'   => $ArchivalAgencyCommunicationType,
                'ContactInformations' => $ArchivalAgencyContactInformations[0]
            ],
            'TransferringAgency' => [
                'EntitiesInformations' => $TransferringAgencyInformations
            ],
            'file'               => $fileInfo,
            'attachment'         => $AttachmentsInfo,
            'attachment_version' => $AttachmentsVersionInfo,
            // 'notes'              => [],   // TODO !!
            'res'                => ResModel::getById(['resId' => $aArgs['identifier']])[0]

        ]);
var_export($return);exit;
        return $return;
    }

    public static function generateMessageObject($aArgs = [])
    {
        $RequestSeda = new RequestSeda();
        $date        = new DateTime;

        $messageObject = new stdClass();
        $messageObject->Comment           = $aArgs['Comment'];
        $messageObject->Date              = $date->format(DateTime::ATOM);
        $messageObject->MessageIdentifier = $RequestSeda->generateUniqueId();

        /********* DATA OBJECT PACKAGE *********/
        $messageObject->DataObjectPackage = new stdClass();

        $messageObject->DataObjectPackage->BinaryDataObject = [];

        if(!empty($aArgs['file'])) {
            $binaryDataObject = self::getBinaryDataObject($aArgs['file'], 'res_letterbox');
            array_push($messageObject->DataObjectPackage->BinaryDataObject, $binaryDataObject);
        }

        if(!empty($aArgs['attachment'])) {
            $binaryDataObject = self::getBinaryDataObject($aArgs['attachment'], 'res_attachments');
            array_push($messageObject->DataObjectPackage->BinaryDataObject, $binaryDataObject);
        }

        /********* ARCHIVAL AGENCY *********/
        $messageObject->ArchivalAgency             = new stdClass();
        $messageObject->ArchivalAgency->Identifier = $aArgs['ArchivalAgency']['ContactInformations']['external_contact_id'];

        $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata       = new stdClass();
        $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Name = trim($aArgs['ArchivalAgency']['ContactInformations']['society'] . ' ' . $aArgs['ArchivalAgency']['ContactInformations']['contact_lastname'] . ' ' . $aArgs['ArchivalAgency']['ContactInformations']['contact_firstname']);

        if(isset($aArgs['ArchivalAgency']['CommunicationType']['type'])){
            $arcCommunicationObject          = new stdClass();
            $arcCommunicationObject->Channel = $aArgs['ArchivalAgency']['CommunicationType']['type'];
            $arcCommunicationObject->value   = $aArgs['ArchivalAgency']['CommunicationType']['value'];

            $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication = [$arcCommunicationObject];
        }

        $contactObject = new stdClass();
        $contactObject->DepartmentName = $aArgs['ArchivalAgency']['ContactInformations']['department'];
        $contactObject->PersonName     = $aArgs['ArchivalAgency']['ContactInformations']['lastname'] . " " . $aArgs['ArchivalAgency']['ContactInformations']['firstname'];

        $addressObject = new stdClass();
        $addressObject->CityName      = $aArgs['ArchivalAgency']['ContactInformations']['address_town'];
        $addressObject->Country       = $aArgs['ArchivalAgency']['ContactInformations']['address_country'];
        $addressObject->Postcode      = $aArgs['ArchivalAgency']['ContactInformations']['address_postal_code'];
        $addressObject->PostOfficeBox = $aArgs['ArchivalAgency']['ContactInformations']['address_num'];
        $addressObject->StreetName    = $aArgs['ArchivalAgency']['ContactInformations']['address_street'];

        $contactObject->Address = [$addressObject];

        $communicationContactPhoneObject          = new stdClass();
        $communicationContactPhoneObject->Channel = 'phone';
        $communicationContactPhoneObject->value   = $aArgs['ArchivalAgency']['ContactInformations']['phone'];

        $communicationContactEmailObject          = new stdClass();
        $communicationContactEmailObject->Channel = 'email';
        $communicationContactEmailObject->value   = $aArgs['ArchivalAgency']['ContactInformations']['email'];

        $contactObject->Communication = [$communicationContactPhoneObject, $communicationContactEmailObject];

        $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Contact = [$contactObject];

        /********* TRANSFERRING AGENCY *********/
        $messageObject->TransferringAgency             = new stdClass();
        $messageObject->TransferringAgency->Identifier = $aArgs['TransferringAgency']['EntitiesInformations']['business_id'];

        $messageObject->TransferringAgency->OrganizationDescriptiveMetadata                      = new stdClass();
        $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->LegalClassification = "";  // TODO : GET ENTITY ROOT
        $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->Name                = $aArgs['TransferringAgency']['EntitiesInformations']['entity_label'];

        $traCommunicationObject          = new stdClass();
        $traCommunicationObject->Channel = 'email';
        $traCommunicationObject->value   = $aArgs['TransferringAgency']['EntitiesInformations']['email'];

        $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->Communication = [$traCommunicationObject];

        $contactUserObject                 = new stdClass();
        $contactUserObject->DepartmentName = $aArgs['TransferringAgency']['EntitiesInformations']['entity_label'];
        $contactUserObject->PersonName     = $_SESSION['user']['LastName'] . " " . $_SESSION['user']['FirstName'];

        $communicationUserPhoneObject          = new stdClass();
        $communicationUserPhoneObject->Channel = 'phone';
        $communicationUserPhoneObject->value   = $_SESSION['user']['Phone'];

        $communicationUserEmailObject          = new stdClass();
        $communicationUserEmailObject->Channel = 'email';
        $communicationUserEmailObject->value   = $_SESSION['user']['Mail'];

        $contactUserObject->Communication = [$communicationUserPhoneObject, $communicationUserEmailObject];

        $messageObject->TransferringAgency->OrganizationDescriptiveMetadata->Contact = [$contactUserObject];

        return $messageObject;
    }

    public static function getBinaryDataObject($aArgs = [], $tablename)
    {
        $binaryDataObject = new stdClass();
        $RequestSeda      = new RequestSeda();

        foreach ($aArgs as $value) {
            $docServers = $RequestSeda->getDocServer($value['docserver_id']);

            if ($tablename == 'res_version') {
                $value['res_id'] = $value['res_id_version'];
            }
            if ($tablename) {
                $binaryDataObjectId = $tablename . "_" . $value['res_id'];
            } else {
                $binaryDataObjectId = $value['res_id'];
            }

            $binaryDataObject->$binaryDataObjectId = new stdClass();

            $binaryDataObject->$binaryDataObjectId->messageDigest = new stdClass();
            $binaryDataObject->$binaryDataObjectId->messageDigest->value = $value['fingerprint'];
            $binaryDataObject->$binaryDataObjectId->messageDigest->algorithm = "sha256";

            $binaryDataObject->$binaryDataObjectId->size = $value['filesize'];

            $uri = str_replace("##", DIRECTORY_SEPARATOR, $value['path']);
            $uri = str_replace("#", DIRECTORY_SEPARATOR, $uri);
            
            $binaryDataObject->$binaryDataObjectId->Attachment           = new stdClass();
            $binaryDataObject->$binaryDataObjectId->Attachment->uri      = $docServers->path_template . $uri;
            $binaryDataObject->$binaryDataObjectId->Attachment->filename = basename($value['filename']);

            $binaryDataObject->$binaryDataObjectId->FormatIdentification = mime_content_type($docServers->path_template . $uri . $value['filename']);
        }

        return $binaryDataObject;
    }

    protected function control($aArgs = []){
        $errors = [];

        if (empty($aArgs['object'])) {
            array_push(
                $errors,
                _SUBJECT . ' ' . _EMPTY
            );
        }

        if (empty($aArgs['identifier']) || !is_numeric($aArgs['identifier'])) {
            array_push(
                $errors,
                'wrong format for identifier'
            );
        }

        if (empty($aArgs['join_file']) && empty($aArgs['join_attachment']) && empty($aArgs['join_version']) && empty($aArgs['notes'])) {
            array_push(
                $errors,
                'no attachment'
            );
        }

        return $errors;
    }

}
