<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Send Message Exchange Controller
* @author dev@maarch.org
* @ingroup core
*/

require_once 'modules/sendmail/Controllers/ReceiveMessageExchangeController.php';
require_once 'modules/export_seda/RequestSeda.php';
require_once 'modules/export_seda/Controllers/SendMessage.php';

class SendMessageExchangeController
{
    public static function createMessageExchange($aArgs = [])
    {
        $errors = self::control($aArgs);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }
        $mlbCollExt = Resource\models\ResModel::getExtById(['resId' => $aArgs['identifier']]);
        if (empty($mlbCollExt)) {
            return ['errors' => "wrong identifier"];
        }

        // if (empty($mlbCollExt['exp_contact_id']) && empty($mlbCollExt['dest_contact_id'])) {
        //     return ['errors' => "no contact"];
        // }

        /***************** GET MAIL INFOS *****************/
        $AllUserEntities = \Entity\models\EntityModel::getEntitiesByUserId(['user_id' => $_SESSION['user']['UserId']]);
        foreach ($AllUserEntities as $value) {
            if ($value['entity_id'] == $aArgs['sender_email']) {
                $TransferringAgencyInformations = $value;
            }
        }

        if (empty($TransferringAgencyInformations)) {
            return ['errors' => "no sender"];
        }

        $AllInfoMainMail = Resource\models\ResModel::getById(['resId' => $aArgs['identifier']]);

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

        if ($MainExchangeDoc['tablename'] == 'res_attachments') {
            $aArgs['join_attachment'][] = $MainExchangeDoc['res_id'];
        }
        if ($MainExchangeDoc['tablename'] == 'res_version_attachments') {
            $aArgs['join_version_attachment'][] = $MainExchangeDoc['res_id'];
        }

        /**************** GET ATTACHMENTS INFOS ***************/
        $AttachmentsInfo = [];
        if (!empty($aArgs['join_attachment'])) {
            $AttachmentsInfo = \Attachment\models\AttachmentModel::getOnView(['select' => ['*'], 'where' => ['res_id in (?)'], 'data' => [$aArgs['join_attachment']]]);
            foreach ($AttachmentsInfo as $key => $value) {
                $AttachmentsInfo[$key]['Title']                                  = $value['title'];
                $AttachmentsInfo[$key]['OriginatingAgencyArchiveUnitIdentifier'] = $value['identifier'];
                $AttachmentsInfo[$key]['DocumentType']                           = $_SESSION['attachment_types'][$value['attachment_type']];
                $AttachmentsInfo[$key]['tablenameExchangeMessage']               = 'res_attachments';
            }
        }
        $AttVersionInfo = [];
        if (!empty($aArgs['join_version_attachment'])) {
            $AttVersionInfo = \Attachment\models\AttachmentModel::getOnView(['select' => ['*'], 'where' => ['res_id_version in (?)'], 'data' => [$aArgs['join_version_attachment']]]);
            foreach ($AttVersionInfo as $key => $value) {
                $AttVersionInfo[$key]['res_id']                                 = $value['res_id_version'];
                $AttVersionInfo[$key]['Title']                                  = $value['title'];
                $AttVersionInfo[$key]['OriginatingAgencyArchiveUnitIdentifier'] = $value['identifier'];
                $AttVersionInfo[$key]['DocumentType']                           = $_SESSION['attachment_types'][$value['attachment_type']];
                $AttVersionInfo[$key]['tablenameExchangeMessage']               = 'res_version_attachments';
            }
        }
        $aAllAttachment = array_merge($AttachmentsInfo, $AttVersionInfo);

        /******************* GET NOTE INFOS **********************/
        $aComments = self::generateComments([
            'resId' => $aArgs['identifier'],
            'notes' => $aArgs['notes'],
            'body'  => $aArgs['body_from_raw'],
            'TransferringAgencyInformations' => $TransferringAgencyInformations]);

        /*********** ORDER ATTACHMENTS IN MAIL ***************/
        if ($MainExchangeDoc['tablename'] == 'res_letterbox') {
            $mainDocument     = $fileInfo;
            $aMergeAttachment = array_merge($fileInfo, $aAllAttachment);
        } else {
            foreach ($aAllAttachment as $key => $value) {
                if ($value['res_id'] == $MainExchangeDoc['res_id'] && $MainExchangeDoc['tablename'] == $value['tablenameExchangeMessage']) {
                    if ($AllInfoMainMail['category_id'] == 'outgoing') {
                        $aOutgoingMailInfo                                           = $AllInfoMainMail;
                        $aOutgoingMailInfo['Title']                                  = $AllInfoMainMail['subject'];
                        $aOutgoingMailInfo['OriginatingAgencyArchiveUnitIdentifier'] = $AllInfoMainMail['alt_identifier'];
                        $aOutgoingMailInfo['DocumentType']                           = $AllInfoMainMail['type_label'];
                        $aOutgoingMailInfo['tablenameExchangeMessage']               = $AllInfoMainMail['tablenameExchangeMessage'];
                        $mainDocument = [$aOutgoingMailInfo];
                    } else {
                        $mainDocument = [$aAllAttachment[$key]];
                    }
                    $firstAttachment = [$aAllAttachment[$key]];
                    unset($aAllAttachment[$key]);
                }
            }
            $aMergeAttachment = array_merge($firstAttachment, $fileInfo, $aAllAttachment);
        }

        $mainDocument[0]['Title'] = '[CAPTUREM2M]'.$aArgs['object'];

        $sendMessage = new SendMessage();

        foreach ($_SESSION['adresses']['to'] as $key => $value) {
            /******** GET ARCHIVAl INFORMATIONs **************/
            $contactInfo                       = \Contact\models\ContactModel::getFullAddressById(['addressId' => $key]);
            $ArchivalAgencyCommunicationType   = \Contact\models\ContactModel::getContactCommunication(['contactId' => $contactInfo[0]['contact_id']]);
            $ArchivalAgencyContactInformations = \Contact\models\ContactModel::getFullAddressById(['addressId' => $key]);

            /******** GENERATE MESSAGE EXCHANGE OBJECT *********/
            $dataObject = self::generateMessageObject([
                'Comment' => $aComments,
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
            /******** GENERATION DU BORDEREAU */
            $filePath = $sendMessage->generateMessageFile($dataObject, "ArchiveTransfer", $_SESSION['config']['tmppath']);

            /******** SAVE MESSAGE *********/
            $messageId = self::saveMessageExchange(['dataObject' => $dataObject, 'res_id_master' => $aArgs['identifier'], 'file_path' => $filePath, 'type' => 'ArchiveTransfer']);
            self::saveUnitIdentifier(['attachment' => $aMergeAttachment, 'notes' => $aArgs['notes'], 'messageId' => $messageId]);

            \History\controllers\HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $aArgs['identifier'],
                'eventType' => 'UP',
                'eventId'   => 'resup',
                'info'       => _NUMERIC_PACKAGE_ADDED . _ON_DOC_NUM
                    . $aArgs['identifier'] . ' ('.$messageId.') : "' . \SrcCore\models\TextFormatModel::cutString(['string' => $mainDocument[0]['Title'], 'max' => 254]),
                'userId' => $_SESSION['user']['UserId']
            ]);

            \History\controllers\HistoryController::add([
                'tableName' => 'message_exchange',
                'recordId'  => $messageId,
                'eventType' => 'ADD',
                'eventId'   => 'messageexchangeadd',
                'info'       => _NUMERIC_PACKAGE_ADDED . ' (' . $messageId . ')',
                'userId' => $_SESSION['user']['UserId']
            ]);

            /******** ENVOI *******/
            $res = $sendMessage->send($dataObject, $messageId, 'ArchiveTransfer');

            if ($res['status'] == 1) {
                $errors = [];
                array_push($errors, _SENDS_FAIL);
                array_push($errors, $res['content']);
                return ['errors' => $errors];
            }
        }

        return true;
    }

    protected static function control($aArgs = [])
    {
        $errors = [];

        if (empty($aArgs['identifier']) || !is_numeric($aArgs['identifier'])) {
            array_push($errors, 'wrong format for identifier');
        }

        if (empty($aArgs['main_exchange_doc'])) {
            array_push($errors, 'wrong format for main_exchange_doc');
        }

        if (empty($aArgs['object'])) {
            array_push($errors, _EMAIL_OBJECT . ' ' . _IS_EMPTY);
        }

        if (empty($aArgs['join_file']) && empty($aArgs['join_attachment']) && empty($aArgs['main_exchange_doc'])) {
            array_push($errors, 'no attachment');
        }

        if (empty($_SESSION['adresses']['to'])) {
            array_push($errors, _NO_RECIPIENT);
        }

        if (empty($aArgs['sender_email'])) {
            array_push($errors, _NO_SENDER);
        }

        return $errors;
    }

    protected static function generateComments($aArgs = [])
    {
        $aReturn    = [];

        $entityRoot = \Entity\models\EntityModel::getEntityRootById(['entityId' => $aArgs['TransferringAgencyInformations']['entity_id']]);
        $headerNote = $_SESSION['user']['FirstName'] . ' ' . $_SESSION['user']['LastName'] . ' (' . $entityRoot['entity_label'] . ' - ' . $aArgs['TransferringAgencyInformations']['entity_label'] . ' - ' .$_SESSION['user']['Mail'].') : ';
        $oBody        = new stdClass();
        $oBody->value = $headerNote . ' ' . $aArgs['body'];
        array_push($aReturn, $oBody);

        if (!empty($aArgs['notes'])) {
            $notes     = \Note\models\NoteModel::getByResId([
                'select' => ['notes.id', 'notes.user_id', 'notes.creation_date', 'notes.note_text', 'users.firstname', 'users.lastname', 'users_entities.entity_id'],
                'resId' => $aArgs['resId']
            ]);

            if (!empty($notes)) {
                foreach ($notes as $value) {
                    if (!in_array($value['id'], $aArgs['notes'])) {
                        continue;
                    }

                    $oComment        = new stdClass();
                    $date            = new DateTime($value['creation_date']);
                    $additionalUserInfos = '';
                    if (!empty($value['entity_id'])) {
                        $entityRoot      = \Entity\models\EntityModel::getEntityRootById(['entityId' => $value['entity_id']]);
                        $userEntity      = \Entity\models\entitymodel::getByEntityId(['entityId' => $value['entity_id']]);
                        $additionalUserInfos = ' ('.$entityRoot['entity_label'].' - '.$userEntity['entity_label'].')';
                    }
                    $oComment->value = $value['firstname'].' '.$value['lastname'].' - '.$date->format('d-m-Y H:i:s'). $additionalUserInfos . ' : '.$value['note_text'];
                    array_push($aReturn, $oComment);
                }
            }
        }
        return $aReturn;
    }

    public static function generateMessageObject($aArgs = [])
    {
        $date = new DateTime;

        $messageObject          = new stdClass();
        $messageObject->Comment = $aArgs['Comment'];
        $messageObject->Date    = $date->format(DateTime::ATOM);

        $messageObject->MessageIdentifier = new stdClass();
        $messageObject->MessageIdentifier->value = 'ArchiveTransfer_'.date("Ymd_His").'_'.$_SESSION['user']['UserId'];

        /********* BINARY DATA OBJECT PACKAGE *********/
        $messageObject->DataObjectPackage                   = new stdClass();
        $messageObject->DataObjectPackage->BinaryDataObject = self::getBinaryDataObject($aArgs['attachment']);

        /********* DESCRIPTIVE META DATA *********/
        $messageObject->DataObjectPackage->DescriptiveMetadata = self::getDescriptiveMetaDataObject($aArgs);

        /********* ARCHIVAL AGENCY *********/
        $messageObject->ArchivalAgency = self::getArchivalAgencyObject(['ArchivalAgency' => $aArgs['ArchivalAgency']]);

        /********* TRANSFERRING AGENCY *********/
        $channelType = $messageObject->ArchivalAgency->OrganizationDescriptiveMetadata->Communication[0]->Channel;
        $messageObject->TransferringAgency = self::getTransferringAgencyObject(['TransferringAgency' => $aArgs['TransferringAgency'], 'ChannelType' => $channelType]);

        return $messageObject;
    }

    public static function getBinaryDataObject($aArgs = [])
    {
        $aReturn     = [];
        $RequestSeda = new RequestSeda();

        foreach ($aArgs as $key => $value) {
            if (!empty($value['tablenameExchangeMessage'])) {
                $binaryDataObjectId = $value['tablenameExchangeMessage'] . "_" . $key . "_" . $value['res_id'];
            } else {
                $binaryDataObjectId = $value['res_id'];
            }

            $binaryDataObject                           = new stdClass();
            $binaryDataObject->id                       = $binaryDataObjectId;

            $binaryDataObject->MessageDigest            = new stdClass();
            $binaryDataObject->MessageDigest->value     = $value['fingerprint'];
            $binaryDataObject->MessageDigest->algorithm = "sha256";

            $binaryDataObject->Size                     = $value['filesize'];

            $uri = str_replace("##", DIRECTORY_SEPARATOR, $value['path']);
            $uri = str_replace("#", DIRECTORY_SEPARATOR, $uri);
            
            $docServers = $RequestSeda->getDocServer($value['docserver_id']);
            $binaryDataObject->Attachment           = new stdClass();
            $binaryDataObject->Attachment->uri      = '';
            $binaryDataObject->Attachment->filename = basename($value['filename']);
            $binaryDataObject->Attachment->value    = base64_encode(file_get_contents($docServers->path_template . $uri . '/'. $value['filename']));

            $binaryDataObject->FormatIdentification           = new stdClass();
            $binaryDataObject->FormatIdentification->MimeType = mime_content_type($docServers->path_template . $uri . $value['filename']);

            array_push($aReturn, $binaryDataObject);
        }

        return $aReturn;
    }

    public static function getDescriptiveMetaDataObject($aArgs = [])
    {
        $DescriptiveMetadataObject              = new stdClass();
        $DescriptiveMetadataObject->ArchiveUnit = [];

        $documentArchiveUnit                    = new stdClass();
        $documentArchiveUnit->id                = 'mail_1';

        $documentArchiveUnit->Content = self::getContent([
            'DescriptionLevel'                       => 'File',
            'Title'                                  => $aArgs['res'][0]['Title'],
            'OriginatingSystemId'                    => $aArgs['res'][0]['res_id'],
            'OriginatingAgencyArchiveUnitIdentifier' => $aArgs['res'][0]['OriginatingAgencyArchiveUnitIdentifier'],
            'DocumentType'                           => $aArgs['res'][0]['DocumentType'],
            'Status'                                 => $aArgs['res'][0]['status'],
            'Writer'                                 => $aArgs['res'][0]['typist'],
            'CreatedDate'                            => $aArgs['res'][0]['creation_date'],
        ]);

        $documentArchiveUnit->ArchiveUnit = [];
        foreach ($aArgs['attachment'] as $key => $value) {
            $attachmentArchiveUnit     = new stdClass();
            $attachmentArchiveUnit->id = 'archiveUnit_'.$value['tablenameExchangeMessage'] . "_" . $key . "_" . $value['res_id'];
            $attachmentArchiveUnit->Content = self::getContent([
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
            $attachmentArchiveUnit->DataObjectReference = [$dataObjectReference];

            array_push($documentArchiveUnit->ArchiveUnit, $attachmentArchiveUnit);
        }
        array_push($DescriptiveMetadataObject->ArchiveUnit, $documentArchiveUnit);

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
        $contentObject->Status                                 = \Status\models\StatusModel::getById(['id' => $aArgs['Status']])['label_status'];

        $userInfos = \User\models\UserModel::getByLogin(['login' => $aArgs['Writer']]);
        $writer                = new stdClass();
        $writer->FirstName     = $userInfos['firstname'];
        $writer->BirthName     = $userInfos['lastname'];
        $contentObject->Writer = [$writer];

        $contentObject->CreatedDate = date("Y-m-d", strtotime($aArgs['CreatedDate']));

        return $contentObject;
    }

    public static function getArchivalAgencyObject($aArgs = [])
    {
        $archivalAgencyObject                    = new stdClass();
        $archivalAgencyObject->Identifier        = new stdClass();
        $externalId = (array)json_decode($aArgs['ArchivalAgency']['ContactInformations']['external_id']);
        $archivalAgencyObject->Identifier->value = $externalId['m2m'];

        $archivalAgencyObject->OrganizationDescriptiveMetadata       = new stdClass();
        $archivalAgencyObject->OrganizationDescriptiveMetadata->Name = trim($aArgs['ArchivalAgency']['ContactInformations']['society'] . ' ' . $aArgs['ArchivalAgency']['ContactInformations']['contact_lastname'] . ' ' . $aArgs['ArchivalAgency']['ContactInformations']['contact_firstname']);

        if (isset($aArgs['ArchivalAgency']['CommunicationType']['type'])) {
            $arcCommunicationObject          = new stdClass();
            $arcCommunicationObject->Channel = $aArgs['ArchivalAgency']['CommunicationType']['type'];
            if ($aArgs['ArchivalAgency']['CommunicationType']['type'] == 'url') {
                $postUrl = '/rest/saveNumericPackage';
            }
            $arcCommunicationObject->value   = $aArgs['ArchivalAgency']['CommunicationType']['value'].$postUrl;

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
        $TransferringAgencyObject                    = new stdClass();
        $TransferringAgencyObject->Identifier        = new stdClass();
        $TransferringAgencyObject->Identifier->value = $aArgs['TransferringAgency']['EntitiesInformations']['business_id'];

        $TransferringAgencyObject->OrganizationDescriptiveMetadata                      = new stdClass();

        $entityRoot = \Entity\models\EntityModel::getEntityRootById(['entityId' => $aArgs['TransferringAgency']['EntitiesInformations']['entity_id']]);
        $TransferringAgencyObject->OrganizationDescriptiveMetadata->LegalClassification = $entityRoot['entity_label'];
        $TransferringAgencyObject->OrganizationDescriptiveMetadata->Name                = $aArgs['TransferringAgency']['EntitiesInformations']['entity_label'];
        $TransferringAgencyObject->OrganizationDescriptiveMetadata->UserIdentifier      = $_SESSION['user']['UserId'];

        $traCommunicationObject          = new stdClass();

        $aDefaultConfig = \Sendmail\Controllers\ReceiveMessageExchangeController::readXmlConfig();

        $traCommunicationObject->Channel = $aArgs['ChannelType'];
        $traCommunicationObject->value   = rtrim($aDefaultConfig['m2m_communication_type'][$aArgs['ChannelType']], "/");

        $TransferringAgencyObject->OrganizationDescriptiveMetadata->Communication = [$traCommunicationObject];

        $contactUserObject                 = new stdClass();
        $contactUserObject->DepartmentName = $aArgs['TransferringAgency']['EntitiesInformations']['entity_label'];
        $contactUserObject->PersonName     = $_SESSION['user']['FirstName'] . " " . $_SESSION['user']['LastName'];

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

        $oData->MessageIdentifier                     = new stdClass();
        $oData->MessageIdentifier->value              = $dataObject->MessageIdentifier->value;
        
        $oData->TransferringAgency                    = new stdClass();
        $oData->TransferringAgency->Identifier        = new stdClass();
        $oData->TransferringAgency->Identifier->value = $dataObject->TransferringAgency->Identifier->value;
        
        $oData->ArchivalAgency                        = new stdClass();
        $oData->ArchivalAgency->Identifier            = new stdClass();
        $oData->ArchivalAgency->Identifier->value     = $dataObject->ArchivalAgency->Identifier->value;
        
        $oData->archivalAgreement                     = new stdClass();
        $oData->archivalAgreement->value              = ""; // TODO : ???
        
        $replyCode = "";
        if (!empty($dataObject->ReplyCode)) {
            $replyCode = $dataObject->ReplyCode;
        }

        $oData->replyCode                             = new stdClass();
        $oData->replyCode                             = $replyCode;

        $dataObject = self::cleanBase64Value(['dataObject' => $dataObject]);

        $aDataExtension = [
            'status'            => 'W',
            'fullMessageObject' => $dataObject,
            'resIdMaster'       => $aArgs['res_id_master'],
            'SenderOrgNAme'     => $dataObject->TransferringAgency->OrganizationDescriptiveMetadata->Contact[0]->DepartmentName,
            'RecipientOrgNAme'  => $dataObject->ArchivalAgency->OrganizationDescriptiveMetadata->Name,
            'filePath'          => $aArgs['file_path'],
        ];

        $messageId = $RequestSeda->insertMessage($oData, $aArgs['type'], $aDataExtension);

        return $messageId;
    }

    public static function saveUnitIdentifier($aArgs = [])
    {
        $messageId   = $aArgs['messageId'];
        $RequestSeda = new RequestSeda();

        foreach ($aArgs['attachment'] as $key => $value) {
            $disposition = "attachment";
            if ($key == 0) {
                $disposition = "body";
            }

            $RequestSeda->insertUnitIdentifier($messageId, $value['tablenameExchangeMessage'], $value['res_id'], $disposition);
        }

        if (!empty($aArgs['notes'])) {
            foreach ($aArgs['notes'] as $value) {
                $RequestSeda->insertUnitIdentifier($messageId, "notes", $value, "note");
            }
        }

        return true;
    }

    protected static function cleanBase64Value($aArgs = [])
    {
        $dataObject = $aArgs['dataObject'];
        $aCleanDataObject = [];
        if (!empty($dataObject->DataObjectPackage->BinaryDataObject)) {
            foreach ($dataObject->DataObjectPackage->BinaryDataObject as $key => $value) {
                $value->Attachment->value = "";
                $aCleanDataObject[$key] = $value;
            }
            $dataObject->DataObjectPackage->BinaryDataObject = $aCleanDataObject;
        }
        return $dataObject;
    }
}
