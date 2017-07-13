<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Message Exchange Controller
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

        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        $mlbCollExt = ResModel::getMlbCollExtById(['resId' => $aArgs['identifier']]);
        if (empty($mlbCollExt)) {
            return ['errors' => "wrong identifier"];
        }

        if (empty($mlbCollExt['exp_contact_id']) && empty($mlbCollExt['dest_contact_id'])) {
            return ['errors' => "no contact"];
        }

        if ($mlbCollExt['exp_contact_id'] != null) {
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

        $tmpMainExchangeDoc = explode("__", $aArgs['main_exchange_doc']);
        $MainExchangeDoc    = ['tablename' => $tmpMainExchangeDoc[0], 'res_id' => $tmpMainExchangeDoc[1]];

        if (!empty($aArgs['join_file']) || $MainExchangeDoc['tablename'] == 'res_letterbox') {
            $AllInfoMainMail['Title']                                  = $AllInfoMainMail['subject'];
            $AllInfoMainMail['OriginatingAgencyArchiveUnitIdentifier'] = $AllInfoMainMail['alt_identifier'];
            $AllInfoMainMail['DocumentType']                           = $AllInfoMainMail['type_label'];
            $AllInfoMainMail['tablenameExchangeMessage']               = 'res_letterbox';
            $fileInfo = [$AllInfoMainMail];
        }

        if(!empty($aArgs['join_attachment'])){
            foreach ($aArgs['join_attachment'] as $key => $value) {
                if (empty($value)) {
                    unset($aArgs['join_attachment'][$key]);
                }
            }
        }
        if($MainExchangeDoc['tablename'] == 'res_attachments'){
            $aArgs['join_attachment'][] = $MainExchangeDoc['res_id']; 
        }

        if (!empty($aArgs['join_attachment'])) {
            $AttachmentsInfo = \Attachments\Models\AttachmentsModel::getAttachmentsWithOptions(['where' => ['res_id in (?)'], 'data' => [$aArgs['join_attachment']]]);
        } else {
            $AttachmentsInfo = [];
        }

        if($MainExchangeDoc['tablename'] == 'res_letterbox'){
            $mainDocument     = $fileInfo;
            $aMergeAttachment = array_merge($fileInfo, $AttachmentsInfo);
        } else {
            foreach ($AttachmentsInfo as $key => $value) {
                $AttachmentsInfo[$key]['Title']                                  = $value['title'];
                $AttachmentsInfo[$key]['OriginatingAgencyArchiveUnitIdentifier'] = $value['identifier'];
                $AttachmentsInfo[$key]['DocumentType']                           = $_SESSION['attachment_types'][$value['attachment_type']];
                $AttachmentsInfo[$key]['tablenameExchangeMessage']               = 'res_attachments';
                if($value['res_id'] == $MainExchangeDoc['res_id']){
                    $mainDocument = [$AttachmentsInfo[$key]];
                    unset($AttachmentsInfo[$key]);
                }
            }
            $aMergeAttachment = array_merge($mainDocument, $AttachmentsInfo);
        }

        /******** GENERATE MESSAGE EXCHANGE OBJECT *********/
        $return = self::generateMessageObject([
            'Comment' => [$aArgs['body_from_raw']],
            'ArchivalAgency' => [
                'CommunicationType'   => $ArchivalAgencyCommunicationType,
                'ContactInformations' => $ArchivalAgencyContactInformations[0]
            ],
            'TransferringAgency' => [
                'EntitiesInformations' => $TransferringAgencyInformations
            ],
            'attachment'            => $aMergeAttachment,
            'res'                   => $mainDocument,
            'mainExchangeDocument'  => $MainExchangeDoc
        ]);
        var_export($return);
        exit;

        /******** SAVE MESSAGE EXCHANGE *********/

        return $return;
    }

    public static function generateMessageObject($aArgs = [])
    {
        $RequestSeda = new RequestSeda();
        $date        = new DateTime;

        $messageObject                    = new stdClass();
        $messageObject->Comment           = $aArgs['Comment'];
        $messageObject->Date              = $date->format(DateTime::ATOM);
        $messageObject->MessageIdentifier = $RequestSeda->generateUniqueId();

        /********* BINARY DATA OBJECT PACKAGE *********/
        $messageObject->DataObjectPackage                   = new stdClass();
        $messageObject->DataObjectPackage->BinaryDataObject = [];

        $binaryDataObject = self::getBinaryDataObject($aArgs['attachment']);
        array_push($messageObject->DataObjectPackage->BinaryDataObject, $binaryDataObject);

        /********* DESCRIPTIVE META DATA *********/
        $messageObject->DataObjectPackage->DescriptiveMetadata = self::getDescriptiveMetaDataObject($aArgs);

        /********* ARCHIVAL AGENCY *********/
        $messageObject->ArchivalAgency = self::getArchivalAgencyObject(['ArchivalAgency' => $aArgs['ArchivalAgency']]);

        /********* TRANSFERRING AGENCY *********/
        $messageObject->TransferringAgency = self::getTransferringAgencyObject(['TransferringAgency' => $aArgs['TransferringAgency']]);

        return $messageObject;
    }

    public static function getBinaryDataObject($aArgs = [])
    {
        $binaryDataObject = new stdClass();
        $RequestSeda      = new RequestSeda();

        foreach ($aArgs as $key => $value) {
            $docServers = $RequestSeda->getDocServer($value['docserver_id']);

            if ($value['tablenameExchangeMessage'] == 'res_version_attachments') {
                $value['res_id'] = $value['res_id_version'];
            }
            if ($value['tablenameExchangeMessage']) {
                $binaryDataObjectId = $value['tablenameExchangeMessage'] . "_" . $key . "_" . $value['res_id'];
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

    public static function getDescriptiveMetaDataObject($aArgs = [])
    {
        $DescriptiveMetadataObject = new stdClass();

        $DescriptiveMetadataArchiveUnitId = 'mail_1';
        $DescriptiveMetadataObject->$DescriptiveMetadataArchiveUnitId = new stdClass();
        $DescriptiveMetadataObject->$DescriptiveMetadataArchiveUnitId->Content = self::getContent([
            'DescriptionLevel'                       => 'File',
            'Title'                                  => $aArgs['res'][0]['Title'],
            'OriginatingSystemId'                    => $aArgs['res'][0]['res_id'],
            'OriginatingAgencyArchiveUnitIdentifier' => $aArgs['res'][0]['OriginatingAgencyArchiveUnitIdentifier'],
            'DocumentType'                           => $aArgs['res'][0]['DocumentType'],
            'Status'                                 => $aArgs['res'][0]['status'],
            'Writer'                                 => $aArgs['res'][0]['typist'],
            'CreatedDate'                            => $aArgs['res'][0]['creation_date'],
        ]);

        $DescriptiveMetadataObject->$DescriptiveMetadataArchiveUnitId->ArchiveUnit = [];
        foreach ($aArgs['attachment'] as $key => $value) {
            $attachmentArchiveUnit = new stdClass();
            $DescriptiveMetadataArchiveUnitIdAttachment = 'archiveUnit_'.$value['tablenameExchangeMessage'] . "_" . $key . "_" . $value['res_id'];
            $attachmentArchiveUnit->$DescriptiveMetadataArchiveUnitIdAttachment          = new stdClass();
            $attachmentArchiveUnit->$DescriptiveMetadataArchiveUnitIdAttachment->content = self::getContent([
                'DescriptionLevel'                       => 'Item',
                'Title'                                  => $value['Title'],
                'OriginatingSystemId'                    => $value['res_id'],
                'OriginatingAgencyArchiveUnitIdentifier' => $value['OriginatingAgencyArchiveUnitIdentifier'],
                'DocumentType'                           => $value['DocumentType'],
                'Status'                                 => $value['status'],
                'Writer'                                 => $value['typist'],
                'CreatedDate'                            => $value['creation_date'],
            ]);
            $dataObjectReference                        = new stdClass();
            $dataObjectReference->DataObjectReferenceId = $value['tablenameExchangeMessage'].'_'.$key.'_'.$value['res_id'];
            $attachmentArchiveUnit->$DescriptiveMetadataArchiveUnitIdAttachment->DataObjectReference = [$dataObjectReference];

            array_push($DescriptiveMetadataObject->$DescriptiveMetadataArchiveUnitId->ArchiveUnit, $attachmentArchiveUnit);
        }

        return $DescriptiveMetadataObject;
    }

    public static function getContent($aArgs = [])
    {
        $contentObject                                         = new stdClass();
        $contentObject->DescriptionLevel                       = $aArgs['DescriptionLevel'];
        $contentObject->Title                                  = [$aArgs['Title']];
        $contentObject->OriginatingSystemId                    = $aArgs['OriginatingSystemId'];
        $contentObject->OriginatingAgencyArchiveUnitIdentifier = $aArgs['OriginatingAgencyArchiveUnitIdentifier'];
        $contentObject->DocumentType                           = $aArgs['DocumentType'];
        $contentObject->Status                                 = \Core\Models\StatusModel::getById(['id' => $aArgs['Status']])[0]['label_status'];

        $userInfos = \Core\Models\UserModel::getById(['userId' => $aArgs['Writer']]);
        $writer = new stdClass();
        $writer->FirstName     = $userInfos['firstname'];
        $writer->LastName      = $userInfos['lastname'];
        $contentObject->Writer = [$writer];

        $contentObject->CreatedDate = $aArgs['CreatedDate'];

        return $contentObject;
    }

    public static function getArchivalAgencyObject($aArgs = [])
    {
        $archivalAgencyObject             = new stdClass();
        $archivalAgencyObject->Identifier = $aArgs['ArchivalAgency']['ContactInformations']['external_contact_id'];

        $archivalAgencyObject->OrganizationDescriptiveMetadata       = new stdClass();
        $archivalAgencyObject->OrganizationDescriptiveMetadata->Name = trim($aArgs['ArchivalAgency']['ContactInformations']['society'] . ' ' . $aArgs['ArchivalAgency']['ContactInformations']['contact_lastname'] . ' ' . $aArgs['ArchivalAgency']['ContactInformations']['contact_firstname']);

        if (isset($aArgs['ArchivalAgency']['CommunicationType']['type'])) {
            $arcCommunicationObject          = new stdClass();
            $arcCommunicationObject->Channel = $aArgs['ArchivalAgency']['CommunicationType']['type'];
            $arcCommunicationObject->value   = $aArgs['ArchivalAgency']['CommunicationType']['value'];

            $archivalAgencyObject->OrganizationDescriptiveMetadata->Communication = [$arcCommunicationObject];
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

        $archivalAgencyObject->OrganizationDescriptiveMetadata->Contact = [$contactObject];

        return $archivalAgencyObject;
    }

    public static function getTransferringAgencyObject($aArgs = [])
    {
        $TransferringAgencyObject             = new stdClass();
        $TransferringAgencyObject->Identifier = $aArgs['TransferringAgency']['EntitiesInformations']['business_id'];

        $TransferringAgencyObject->OrganizationDescriptiveMetadata                      = new stdClass();

        $entityRoot = \Entities\Models\EntitiesModel::getEntityRootById(['entityId' => $aArgs['TransferringAgency']['EntitiesInformations']['entity_id']]);
        $TransferringAgencyObject->OrganizationDescriptiveMetadata->LegalClassification = $entityRoot[0]['entity_label'];
        $TransferringAgencyObject->OrganizationDescriptiveMetadata->Name                = $aArgs['TransferringAgency']['EntitiesInformations']['entity_label'];

        $traCommunicationObject          = new stdClass();
        $traCommunicationObject->Channel = 'email';
        $traCommunicationObject->value   = $aArgs['TransferringAgency']['EntitiesInformations']['email'];

        $TransferringAgencyObject->OrganizationDescriptiveMetadata->Communication = [$traCommunicationObject];

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

        $TransferringAgencyObject->OrganizationDescriptiveMetadata->Contact = [$contactUserObject];

        return $TransferringAgencyObject;
    }

    protected function control($aArgs = [])
    {
        $errors = [];

        if (empty($aArgs['identifier']) || !is_numeric($aArgs['identifier'])) {
            array_push($errors, 'wrong format for identifier');
        }

        if (empty($aArgs['main_exchange_doc'])) {
            array_push($errors, 'wrong format for main_exchange_doc');
        }

        if (empty($aArgs['join_file']) && empty($aArgs['join_attachment']) && empty($aArgs['main_exchange_doc'])) {
            array_push($errors, 'no attachment');
        }

        return $errors;
    }
}
