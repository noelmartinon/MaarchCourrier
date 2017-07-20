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
require_once "core/class/class_request.php";
require_once "core/class/class_history.php";

class SendMessageExchangeController
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

        $fileInfo = [];
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

        $AttachmentsInfo = [];
        if (!empty($aArgs['join_attachment'])) {
            $AttachmentsInfo = \Attachments\Models\AttachmentsModel::getAttachmentsWithOptions(['where' => ['res_id in (?)'], 'data' => [$aArgs['join_attachment']]]);
        }

        if($MainExchangeDoc['tablename'] == 'res_letterbox'){
            foreach ($AttachmentsInfo as $key => $value) {
                $AttachmentsInfo[$key]['Title']                                  = $value['title'];
                $AttachmentsInfo[$key]['OriginatingAgencyArchiveUnitIdentifier'] = $value['identifier'];
                $AttachmentsInfo[$key]['DocumentType']                           = $_SESSION['attachment_types'][$value['attachment_type']];
                $AttachmentsInfo[$key]['tablenameExchangeMessage']               = 'res_attachments';
            }
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
            $aMergeAttachment = array_merge($mainDocument, $fileInfo, $AttachmentsInfo);
        }

        /******** GENERATE MESSAGE EXCHANGE OBJECT *********/
        $dataObject = self::generateMessageObject([
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

        /******** SAVE MESSAGE *********/
        $messageId = self::saveMessageExchange(['dataObject' => $dataObject, 'res_id_master' => $aArgs['identifier']]);
        self::saveUnitIdentifier(['attachment' => $aMergeAttachment, 'messageId' => $messageId]);

        $hist    = new history();
        $request = new request();
        $hist->add(
            'res_letterbox', $aArgs['identifier'], "UP", 'resup',  _NUMERIC_PACKAGE_ADDED . _ON_DOC_NUM
            . $aArgs['identifier'] . ' ('.$messageId.') : "' . $request->cut_string($mainDocument[0]['Title'], 254) .'"',
            $_SESSION['config']['databasetype'], 'sendmail'
        );
        $hist->add(
            'message_exchange', $messageId, "ADD", 'messageexchangeadd', _NUMERIC_PACKAGE_ADDED . ' (' . $messageId . ')',
            $_SESSION['config']['databasetype'], 'sendmail'
        );

        /******** GENERATION DU BORDEREAU + ENVOI

        /*********** TODO : ALEX MORIN *********/

        return true;
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

    public static function generateMessageObject($aArgs = [])
    {
        $date        = new DateTime;

        $messageObject                    = new stdClass();
        $messageObject->Comment           = $aArgs['Comment'];
        $messageObject->Date              = $date->format(DateTime::ATOM);
        $messageObject->MessageIdentifier = 'ArchiveTransfer_'.date("Ymd_His").'_'.$_SESSION['user']['UserId'];

        /********* BINARY DATA OBJECT PACKAGE *********/
        $messageObject->DataObjectPackage                   = new stdClass();
        $messageObject->DataObjectPackage->BinaryDataObject = self::getBinaryDataObject($aArgs['attachment']);

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
        $aReturn     = [];
        $RequestSeda = new RequestSeda();

        foreach ($aArgs as $key => $value) {
            if ($value['tablenameExchangeMessage'] == 'res_version_attachments') {
                $value['res_id'] = $value['res_id_version'];
            }
            if ($value['tablenameExchangeMessage']) {
                $binaryDataObjectId = $value['tablenameExchangeMessage'] . "_" . $key . "_" . $value['res_id'];
            } else {
                $binaryDataObjectId = $value['res_id'];
            }

            $binaryDataObject                      = new stdClass();
            $binaryDataObject->$binaryDataObjectId = new stdClass();

            $binaryDataObject->$binaryDataObjectId->messageDigest            = new stdClass();
            $binaryDataObject->$binaryDataObjectId->messageDigest->value     = $value['fingerprint'];
            $binaryDataObject->$binaryDataObjectId->messageDigest->algorithm = "sha256";

            $binaryDataObject->$binaryDataObjectId->size                     = $value['filesize'];

            $uri = str_replace("##", DIRECTORY_SEPARATOR, $value['path']);
            $uri = str_replace("#", DIRECTORY_SEPARATOR, $uri);
            
            $docServers = $RequestSeda->getDocServer($value['docserver_id']);
            $binaryDataObject->$binaryDataObjectId->Attachment           = new stdClass();
            $binaryDataObject->$binaryDataObjectId->Attachment->uri      = '';
            $binaryDataObject->$binaryDataObjectId->Attachment->filename = basename($value['filename']);
            $binaryDataObject->$binaryDataObjectId->Attachment->value    = base64_encode(file_get_contents($docServers->path_template . $uri . '/'. $value['filename']));

            $binaryDataObject->$binaryDataObjectId->FormatIdentification           = new stdClass();
            $binaryDataObject->$binaryDataObjectId->FormatIdentification->MimeType = mime_content_type($docServers->path_template . $uri . $value['filename']);

            array_push($aReturn, $binaryDataObject);
        }

        return $aReturn;
    }

    public static function getDescriptiveMetaDataObject($aArgs = [])
    {
        $DescriptiveMetadataObject = new stdClass();

        $DescriptiveMetadataArchiveUnitId = 'mail_1';
        $DescriptiveMetadataObject->$DescriptiveMetadataArchiveUnitId          = new stdClass();
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
            $attachmentArchiveUnit->$DescriptiveMetadataArchiveUnitIdAttachment->Content = self::getContent([
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
        $writer                = new stdClass();
        $writer->FirstName     = $userInfos['firstname'];
        $writer->BirthName     = $userInfos['lastname'];
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

    public static function saveMessageExchange($aArgs = [])
    {
        $RequestSeda = new RequestSeda();

        $dataObject = $aArgs['dataObject'];
        $oData                                        = new stdClass();
        $oData->messageId                             = $RequestSeda->generateUniqueId();
        $oData->date                                  = $dataObject->Date;

        $oData->messageIdentifier                     = new stdClass();
        $oData->messageIdentifier->value              = $dataObject->MessageIdentifier;
        
        $oData->transferringAgency                    = new stdClass();
        $oData->transferringAgency->identifier        = new stdClass();
        $oData->transferringAgency->identifier->value = $dataObject->TransferringAgency->Identifier;
        
        $oData->archivalAgency                        = new stdClass();
        $oData->archivalAgency->identifier            = new stdClass();
        $oData->archivalAgency->identifier->value     = $dataObject->ArchivalAgency->Identifier;
        
        $oData->archivalAgreement                     = new stdClass();
        $oData->archivalAgreement->value              = ""; // TODO : ???
        
        $oData->replyCode                             = new stdClass();
        $oData->replyCode->value                      = ""; // TODO : ???

        $aDataExtension = [
            'status'            => 'W', 
            'fullMessageObject' => $dataObject, 
            'resIdMaster'       => $aArgs['res_id_master'],
            'SenderOrgNAme'     => $dataObject->TransferringAgency->OrganizationDescriptiveMetadata->Contact[0]->DepartmentName,
            'RecipientOrgNAme'  => $dataObject->ArchivalAgency->OrganizationDescriptiveMetadata->Name,
        ];

        $messageId = $RequestSeda->insertMessage($oData, 'ArchiveTransfer', $aDataExtension);

        return $messageId;
    }

    public static function saveUnitIdentifier($aArgs = [])
    {
        $messageId   = $aArgs['messageId'];
        $RequestSeda = new RequestSeda();

        foreach ($aArgs['attachment'] as $key => $value) {
            $disposition = "attachment";
            if($key == 0){
                $disposition = "body";
            }

            $RequestSeda->insertUnitIdentifier($messageId, $value['tablenameExchangeMessage'], $value['res_id'], $disposition);
        }

        return true;
        
    }

}
