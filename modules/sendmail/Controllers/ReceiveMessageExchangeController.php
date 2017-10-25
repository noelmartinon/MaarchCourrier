<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Receive Message Exchange Controller
* @author dev@maarch.org
* @ingroup core
*/

namespace Sendmail\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Core\Controllers\ResController;
use Core\Controllers\ResExtController;
use Core\Models\UserModel;
use Core\Models\CoreConfigModel;
use Core\Models\ServiceModel;
use Entities\Models\EntitiesModel;
use Baskets\Models\BasketsModel;

require_once 'apps/maarch_entreprise/Models/ContactsModel.php';
require_once 'modules/notes/Models/NotesModel.php';
require_once 'modules/export_seda/Controllers/ReceiveMessage.php';
require_once 'modules/export_seda/Controllers/SendMessage.php';
require_once "core/class/class_history.php";
require_once "modules/sendmail/Controllers/SendMessageExchangeController.php";
require_once 'modules/export_seda/RequestSeda.php';

class ReceiveMessageExchangeController
{

    private static $aComments = [];

    public function saveMessageExchange(RequestInterface $request, ResponseInterface $response)
    {

        if (!ServiceModel::hasService(['id' => 'save_numeric_package', 'userId' => $_SESSION['user']['UserId'], 'location' => 'sendmail', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        $data = $request->getParams();

        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] Réception de l\'archive';
        $tmpName = self::createFile(['base64' => $data['base64'], 'extension' => $data['extension'], 'size' => $data['size']]);
        if(!empty($tmpName['errors'])){
            return $response->withStatus(400)->withJson($tmpName);
        }
        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] Archive déposée sur le serveur';
        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] Validation de l\'archive';
        /********** EXTRACTION DU ZIP ET CONTROLE *******/
        $receiveMessage = new \ReceiveMessage();
        $res = $receiveMessage->receive($_SESSION['config']['tmppath'], $tmpName, 'ArchiveTransfer');

        if ($res['status'] == 1) {
            return $response->withStatus(400)->withJson(["errors" => _ERROR_RECEIVE_FAIL. ' ' . $res['content']]);
        }
        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] Archive validée';

        $sDataObject = $res['content'];
        $sDataObject = json_decode($sDataObject);
        
        self::sendAcknowledgement(["dataObject" => $sDataObject]);

        $aDefaultConfig = self::readXmlConfig();

        /*************** RES LETTERBOX **************/
        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] Enregistrement du message';
        $resLetterboxReturn = self::saveResLetterbox(["dataObject" => $sDataObject, "defaultConfig" => $aDefaultConfig]);

        if(!empty($resLetterboxReturn['errors'])){
            return $response->withStatus(400)->withJson(["errors" => $resLetterboxReturn['errors']]);
        }

        /*************** CONTACT **************/
        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] Selection ou création du contact';
        $contactReturn = self::saveContact(["dataObject" => $sDataObject, "defaultConfig" => $aDefaultConfig]);

        if($contactReturn['returnCode'] <> 0){
            return $response->withStatus(400)->withJson(["errors" => $contactReturn['errors']]);
        }
        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] Contact sélectionné ou créé';

        /************** MLB COLL EXT **************/
        $return = self::saveExtensionTable(["contact" => $contactReturn, "resId" => $resLetterboxReturn[0]]);

        if(!empty($return['errors'])){
            return $response->withStatus(400)->withJson(["errors" => $return['errors']]);
        }
        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] Message enregistré';
        /************** NOTES *****************/
        $notesReturn = self::saveNotes(["dataObject" => $sDataObject, "resId" => $resLetterboxReturn[0]]);

        if(!empty($notesReturn['errors'])){
            return $response->withStatus(400)->withJson(["errors" => $notesReturn['errors']]);
        }

        /************** RES ATTACHMENT *****************/
        $resAttachmentReturn = self::saveResAttachment(["dataObject" => $sDataObject, "resId" => $resLetterboxReturn[0], "defaultConfig" => $aDefaultConfig]);

        if(!empty($resAttachmentReturn['errors'])){
            return $response->withStatus(400)->withJson(["errors" => $resAttachmentReturn['errors']]);
        }

        $hist = new \history();
        $hist->add(
                'res_letterbox', $resLetterboxReturn[0], "ADD", 'resadd', _NUMERIC_PACKAGE_IMPORTED, 'POSTGRESQL', 'sendmail'
            );

        $basketRedirection = null;
        $userBaskets = BasketsModel::getBasketsByUserId(['userId' => $_SESSION['user']['UserId']]);
        if (!empty($userBaskets)) {
            foreach ($userBaskets as $value) {
                if ($value['basket_id'] == $aDefaultConfig['basketRedirection_afterUpload'][0]) {
                    $userGroups = UserModel::getGroupsById(['userId' => $_SESSION['user']['UserId']]);
                    foreach ($userGroups as $userGroupValue) {
                        if ($userGroupValue['primary_group'] == 'Y') {
                            $userPrimaryGroup = $userGroupValue['group_id'];
                            break;
                        }
                    }
                    $defaultAction = BasketsModel::getDefaultActionIdByBasketId(['basketId' => $value['basket_id'], 'groupId' => $userPrimaryGroup]);
                    $basketRedirection = 'index.php?page=view_baskets&module=basket&baskets=' . $value['basket_id'] . '&resId=' . $resLetterboxReturn[0] . '&defaultAction=' . $defaultAction;
                    break;
                }
            }
        }

        if(empty($basketRedirection)){
            $basketRedirection = 'index.php';
        }

        self::sendReply(['dataObject' => $sDataObject, 'Comment' => self::$aComments, 'replyCode' => '000 : OK', 'res_id_master' => $resLetterboxReturn[0]]);

        return $response->withJson([
            "resId"             => $resLetterboxReturn[0],
            'basketRedirection' => $basketRedirection
        ]);

    }

    public static function checkNeededParameters($aArgs = [])
    {
        foreach ($aArgs['needed'] as $value) {
            if (empty($aArgs['data'][$value])) {
                return false;
            }
        }

        return true;
    }

    public function createFile($aArgs = [])
    {
        if (!self::checkNeededParameters(['data' => $aArgs, 'needed' => ['base64', 'extension', 'size']])) {
            return ['errors' => 'Bad Request'];
        }

        $file     = base64_decode($aArgs['base64']);

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($file);
        $size     = strlen($file);
        $type     = explode('/', $mimeType);
        $ext      = $aArgs['extension'];
        $tmpName  = 'tmp_file_' .$_SESSION['user']['UserId']. '_ArchiveTransfer_' .rand(). '.' . $ext;

        if(!in_array(strtolower($ext), ['zip', 'tar'])){
            return ["errors" => _WRONG_FILE_TYPE_M2M];
        }

        if ($mimeType != "application/x-tar" && $mimeType != "application/zip" && $mimeType != "application/tar" && $mimeType != "application/x-gzip") {
            return ['errors' => _WRONG_FILE_TYPE];
        }

        file_put_contents($_SESSION['config']['tmppath'] . $tmpName, $file);

        return $tmpName;
    }

    public static function readXmlConfig()
    {
        $customId = CoreConfigModel::getCustomId();

        if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/m2m_config.xml")) {
            $path = "custom/{$customId}/apps/maarch_entreprise/xml/m2m_config.xml";
        } else {
            $path = 'apps/maarch_entreprise/xml/m2m_config.xml';
        }

        $aDefaultConfig = [];
        if (file_exists($path)) {
            $loadedXml = simplexml_load_file($path);
            foreach ($loadedXml as $key => $value) {
                $aDefaultConfig[$key] = (array)$value;
            }
        }

        if(filter_var($aDefaultConfig['m2m_communication'][0], FILTER_VALIDATE_EMAIL)){
            $aDefaultConfig['m2m_communication_type'][0] = 'email';
        } else if (filter_var($aDefaultConfig['m2m_communication'][0], FILTER_VALIDATE_URL)) {
            $aDefaultConfig['m2m_communication_type'][0] = 'url';
        }

        return $aDefaultConfig;
    }

    protected static function saveResLetterbox($aArgs = [])
    {
        $dataObject    = $aArgs['dataObject'];
        $defaultConfig = $aArgs['defaultConfig']['res_letterbox'];

        $DescriptiveMetadata = $dataObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0];

        $mainDocumentMetaData  = $DescriptiveMetadata->Content;
        $DataObjectReferenceId = $DescriptiveMetadata->ArchiveUnit[0]->DataObjectReference[0]->DataObjectReferenceId;

        $documentMetaData = self::getBinaryDataObjectInfo(['binaryDataObject' => $dataObject->DataObjectPackage->BinaryDataObject, 'binaryDataObjectId' => $DataObjectReferenceId]);

        $filename         = $documentMetaData->Attachment->filename;
        $fileFormat       = substr($filename, strrpos($filename, '.') + 1);

        $archivalAgency = $dataObject->ArchivalAgency;
        $destination    = EntitiesModel::getByBusinessId(['businessId' => $archivalAgency->Identifier->value]);
        $Communication  = $archivalAgency->OrganizationDescriptiveMetadata->Contact[0]->Communication;

        foreach ($Communication as $value) {
            if($value->Channel == 'email'){
                $email = $value->value;
                break;
            }
        }

        if(!empty($email)){
            $destUser = UserModel::getByEmail(['mail' => $email]);
        }

        $dataValue = [];
        array_push($dataValue, ['column' => 'typist',           'value' => 'superadmin',                        'type' => 'string']);
        array_push($dataValue, ['column' => 'type_id',          'value' => $defaultConfig['type_id'],           'type' => 'integer']);
        array_push($dataValue, ['column' => 'subject',          'value' => $mainDocumentMetaData->Title[0],     'type' => 'string']);
        array_push($dataValue, ['column' => 'doc_date',         'value' => $mainDocumentMetaData->CreatedDate,  'type' => 'date']);
        array_push($dataValue, ['column' => 'destination',      'value' => $destination[0]['entity_id'],        'type' => 'string']);
        array_push($dataValue, ['column' => 'initiator',        'value' => $destination[0]['entity_id'],        'type' => 'string']);
        array_push($dataValue, ['column' => 'dest_user',        'value' => $destUser[0]['user_id'],             'type' => 'string']);
        array_push($dataValue, ['column' => 'reference_number', 'value' => $dataObject->MessageIdentifier->value, 'type' => 'string']);
        array_push($dataValue, ['column' => 'priority',         'value' => $defaultConfig['priority'],          'type' => 'integer']);
        array_push($dataValue, ['column' => 'confidentiality',  'value' => 'N',                                 'type' => 'string']);

        $allDatas = [
            "encodedFile" => $documentMetaData->Attachment->value,
            "data"        => $dataValue,
            "collId"      => "letterbox_coll",
            "table"       => "res_letterbox",
            "fileFormat"  => $fileFormat,
            "status"      => $defaultConfig['status']
        ];

        $resController = new ResController();
        $resId         = $resController->storeResource($allDatas);
        return $resId;
    }

    protected static function saveContact($aArgs = [])
    {
        $dataObject                 = $aArgs['dataObject'];
        $defaultConfigContacts      = $aArgs['defaultConfig']['contacts_v2'];
        $defaultConfigAddress       = $aArgs['defaultConfig']['contact_addresses'];
        $transferringAgency         = $dataObject->TransferringAgency;
        $transferringAgencyMetadata = $transferringAgency->OrganizationDescriptiveMetadata;

        $personName  = $transferringAgencyMetadata->Contact[0]->PersonName;
        $aPersonName = explode(" ", $personName);

        $Communication = $transferringAgencyMetadata->Contact[0]->Communication;

        foreach ($Communication as $value) {
            if($value->Channel == 'phone'){
                $phone = $value->value;
            }
            if($value->Channel == 'email'){
                $email = $value->value;
            }
        }

        $aDataContact = [];
        array_push($aDataContact, ['column' => 'contact_type',        'value' => $defaultConfigContacts['contact_type'],           'type' => 'integer', 'table' => 'contacts_v2']);
        array_push($aDataContact, ['column' => 'society',             'value' => $transferringAgencyMetadata->LegalClassification, 'type' => 'string',  'table' => 'contacts_v2']);
        array_push($aDataContact, ['column' => 'is_corporate_person', 'value' => 'Y', 'type' => 'string',  'table' => 'contacts_v2']);
        array_push($aDataContact, ['column' => 'is_external_contact', 'value' => 'Y', 'type' => 'string',  'table' => 'contacts_v2']);

        array_push($aDataContact, ['column' => 'contact_purpose_id',  'value' => $defaultConfigAddress['contact_purpose_id'],      'type' => 'integer', 'table' => 'contact_addresses']);
        array_push($aDataContact, ['column' => 'external_contact_id', 'value' => $transferringAgency->Identifier->value,           'type' => 'string',  'table' => 'contact_addresses']);
        array_push($aDataContact, ['column' => 'departement',         'value' => $transferringAgencyMetadata->Name,                'type' => 'string',  'table' => 'contact_addresses']);
        $contactModel = new \ContactsModel();
        $contactAlreadyCreated = $contactModel->getAddressByExternalContactId(['externalContactId' => $transferringAgency->Identifier->value]);
        if(!empty($contactAlreadyCreated)){
            $contact['contactId'] = $contactAlreadyCreated['contact_id'];
            $contact['addressId'] = $contactAlreadyCreated['ca_id'];
        } else {
            $contact = $contactModel->CreateContactM2M($aDataContact, $transferringAgencyMetadata->Communication[0]->value);
        }
        $contactCommunicationExisted = $contactModel->getContactCommunication([
            "contactId" => $contact['contactId']
        ]);

        $contactCommunication = $transferringAgencyMetadata->Communication;
        if(empty($contactCommunicationExisted) && !empty($contactCommunication)){
            foreach ( $contactCommunication as $value) {
                if(strrpos($value->value, "/rest/") !== false){
                    $contactCommunicationValue = substr($value->value, 0, strrpos($value->value, "/rest/")+1);
                } else {
                    $contactCommunicationValue = $value->value;
                }
                $contactModel->createContactCommunication([
                    "contactId" => $contact['contactId'], 
                    "type"      => $value->Channel, 
                    "value"     => $contactCommunicationValue
                ]);
            }
        }
        return $contact;
    }

    protected static function saveExtensionTable($aArgs = [])
    {
        $contact = $aArgs['contact'];
        
        $dataValue = [];
        array_push($dataValue, ['column' => 'nature_id',       'value' => 'message_exchange',    'type' => 'string']);
        array_push($dataValue, ['column' => 'category_id',     'value' => 'incoming',            'type' => 'string']);
        array_push($dataValue, ['column' => 'alt_identifier',  'value' => '',                    'type' => 'string']);
        array_push($dataValue, ['column' => 'exp_contact_id',  'value' => $contact['contactId'], 'type' => 'integer']);
        array_push($dataValue, ['column' => 'address_id',      'value' => $contact['addressId'], 'type' => 'integer']);
        array_push($dataValue, ['column' => 'admission_date',  'value' => 'CURRENT_TIMESTAMP',   'type' => 'date']);

        $allDatas = [
            "resId"    => $aArgs['resId'],
            "data"     => $dataValue,
            "table"    => "mlb_coll_ext",
            "resTable" => "res_letterbox"
        ];

        $ResExtController = new ResExtController();
        $return           = $ResExtController->storeExtResource($allDatas); 

        return $return;
    }

    protected static function saveNotes($aArgs = [])
    {
        $noteModel = new \NotesModel();
        $countNote = 0;
        foreach ($aArgs['dataObject']->Comment as $value) {
            $aDataNote = [
                "identifier" => $aArgs['resId'],
                "tablename"  => "res_letterbox",
                "user_id"    => "superadmin",
                "note_text"  => $value->value,
                "coll_id"    => "letterbox_coll",
            ];

            $noteModel->create($aDataNote);
            $countNote++;
        }
        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] '.$countNote . ' note(s) enregistrée(s)';
        return true;
    }

    protected static function saveResAttachment($aArgs = [])
    {
        $dataObject        = $aArgs['dataObject'];
        $resIdMaster       = $aArgs['resId'];
        $defaultConfig     = $aArgs['defaultConfig']['res_attachments'];
        $dataObjectPackage = $dataObject->DataObjectPackage;
        $resController     = new ResController();

        $attachments = $dataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit;

        // First one is the main document. Already added
        unset($attachments[0]);
        $countAttachment = 0;
        if(!empty($attachments)){
            foreach ($attachments as $value) {
                $attachmentContent      = $value->Content;
                $attachmentDataObjectId = $value->DataObjectReference[0]->DataObjectReferenceId;

                $BinaryDataObjectInfo = self::getBinaryDataObjectInfo(["binaryDataObject" => $dataObjectPackage->BinaryDataObject, "binaryDataObjectId" => $attachmentDataObjectId]);
                $filename             = $BinaryDataObjectInfo->Attachment->filename;
                $fileFormat           = substr($filename, strrpos($filename, '.') + 1);

                $dataValue = [];
                array_push($dataValue, ['column' => 'typist',          'value' => 'superadmin',                      'type' => 'string']);
                array_push($dataValue, ['column' => 'type_id',         'value' => '0',                               'type' => 'integer']);
                array_push($dataValue, ['column' => 'res_id_master',   'value' => $resIdMaster,                      'type' => 'integer']);
                array_push($dataValue, ['column' => 'attachment_type', 'value' => $defaultConfig['attachment_type'], 'type' => 'string']);
                array_push($dataValue, ['column' => 'relation',        'value' => '1',                               'type' => 'integer']);
                array_push($dataValue, ['column' => 'coll_id',         'value' => 'letterbox_coll',                  'type' => 'string']);

                array_push($dataValue, ['column' => 'doc_date',        'value' => $attachmentContent->CreatedDate,   'type' => 'date']);
                array_push($dataValue, ['column' => 'title',           'value' => $attachmentContent->Title,         'type' => 'string']);

                $allDatas = [
                    "encodedFile" => $BinaryDataObjectInfo->Attachment->value,
                    "data"        => $dataValue,
                    "collId"      => "letterbox_coll",
                    "table"       => "res_attachments",
                    "fileFormat"  => $fileFormat,
                    "status"      => 'TRA'
                ];
                
                $resId = $resController->storeResource($allDatas);
                $countAttachment++;
            }
        }
        self::$aComments[] = '['.date("d/m/Y H:i:s") . '] '.$countAttachment . ' attachement(s) enregistré(s)';
        return $resId;
    }

    protected function getBinaryDataObjectInfo($aArgs = [])
    {
        $dataObject   = $aArgs['binaryDataObject'];
        $dataObjectId = $aArgs['binaryDataObjectId'];

        foreach ($dataObject as $value) {
            if($value->id == $dataObjectId){
                return $value;
            }
        }
        return null;
    }

    protected function sendAcknowledgement($aArgs = [])
    {
        $dataObject = $aArgs['dataObject'];
        $date       = new \DateTime;

        $acknowledgementObject                                   = new \stdClass();
        $acknowledgementObject->Date                             = $date->format(\DateTime::ATOM);

        $acknowledgementObject->MessageIdentifier                = new \stdClass();
        $acknowledgementObject->MessageIdentifier->value         = $dataObject->MessageIdentifier->value . '_AckSent';

        $acknowledgementObject->MessageReceivedIdentifier        = new \stdClass();
        $acknowledgementObject->MessageReceivedIdentifier->value = $dataObject->MessageIdentifier->value;

        $acknowledgementObject->Sender                           = $dataObject->ArchivalAgency;
        $acknowledgementObject->Receiver                         = $dataObject->TransferringAgency;

        if ($acknowledgementObject->Receiver->OrganizationDescriptiveMetadata->Communication[0]->Channel == 'url') {
            $acknowledgementObject->Receiver->OrganizationDescriptiveMetadata->Communication[0]->value .= '/rest/saveMessageExchangeReturn';
        }

        $sendMessage = new \SendMessage();

        $acknowledgementObject->MessageIdentifier->value          = $dataObject->MessageIdentifier->value . '_Ack';
        $filePath = $sendMessage->generateMessageFile($acknowledgementObject, 'Acknowledgement', $_SESSION['config']['tmppath']);

        $acknowledgementObject->ArchivalAgency = $acknowledgementObject->Receiver;
        $acknowledgementObject->TransferringAgency = $acknowledgementObject->Sender;

        $acknowledgementObject->TransferringAgency->OrganizationDescriptiveMetadata->UserIdentifier = $_SESSION['user']['UserId'];

        $acknowledgementObject->MessageIdentifier->value          = $dataObject->MessageIdentifier->value . '_AckSent';
        $messageId = \SendMessageExchangeController::saveMessageExchange(['dataObject' => $acknowledgementObject, 'res_id_master' => 0, 'type' => 'Acknowledgement', 'file_path' => $filePath]);

        $acknowledgementObject->DataObjectPackage = new \stdClass();
        $acknowledgementObject->DataObjectPackage->DescriptiveMetadata = new \stdClass();
        $acknowledgementObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit = array();
        $acknowledgementObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0] = new \stdClass();
        $acknowledgementObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content = new \stdClass();
        $acknowledgementObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->Title[0] = '[CAPTUREM2M_ACK]';

        $sendMessage->send($acknowledgementObject,$messageId, 'Acknowledgement');
    }

    protected function sendReply($aArgs = [])
    {
        $dataObject = $aArgs['dataObject'];
        $date       = new \DateTime;

        $replyObject                                    = new \stdClass();
        $replyObject->Comment                           = $aArgs['Comment'];
        $replyObject->Date                              = $date->format(\DateTime::ATOM);

        $replyObject->MessageIdentifier                 = new \stdClass();
        $replyObject->MessageIdentifier->value          = $dataObject->MessageIdentifier->value . '_ReplySent';

        $replyObject->ReplyCode                         = new \stdClass();
        $replyObject->ReplyCode->value                  = $aArgs['replyCode'];

        $replyObject->MessageRequestIdentifier        = new \stdClass();
        $replyObject->MessageRequestIdentifier->value = $dataObject->MessageIdentifier->value;

        $replyObject->TransferringAgency                = $dataObject->ArchivalAgency;
        $replyObject->TransferringAgency->OrganizationDescriptiveMetadata->UserIdentifier = $_SESSION['user']['UserId'];
        $replyObject->ArchivalAgency                    = $dataObject->TransferringAgency;

        $sendMessage = new \SendMessage();

        $replyObject->MessageIdentifier->value          = $dataObject->MessageIdentifier->value . '_Reply';
        $filePath = $sendMessage->generateMessageFile($replyObject, "ArchiveTransferReply", $_SESSION['config']['tmppath']);

        $replyObject->MessageIdentifier->value          = $dataObject->MessageIdentifier->value . '_ReplySent';
        $messageId = \SendMessageExchangeController::saveMessageExchange(['dataObject' => $replyObject, 'res_id_master' => $aArgs['res_id_master'], 'type' => 'ArchiveTransferReply', 'file_path' => $filePath]);

        $replyObject->MessageIdentifier->value          = $dataObject->MessageIdentifier->value . '_Reply';

        $replyObject->DataObjectPackage = new \stdClass();
        $replyObject->DataObjectPackage->DescriptiveMetadata = new \stdClass();
        $replyObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit = array();
        $replyObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0] = new \stdClass();
        $replyObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content = new \stdClass();
        $replyObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->OriginatingSystemId = $aArgs['res_id_master'];

        $replyObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->Title[0] = '[CAPTUREM2M_REPLY]';

        $sendMessage->send($replyObject,$messageId, 'ArchiveTransferReply');
    }

    public function saveMessageExchangeReturn(RequestInterface $request, ResponseInterface $response)
    {

        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        $data = $request->getParams();

        if (!self::checkNeededParameters(['data' => $data, 'needed' => ['type']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $tmpName = self::createFile(['base64' => $data['base64'], 'extension' => $data['extension'], 'size' => $data['size']]);

        $receiveMessage = new \ReceiveMessage();
        $res = $receiveMessage->receive($_SESSION['config']['tmppath'], $tmpName, $data['type']);

        $sDataObject = $res['content'];
        $dataObject = json_decode($sDataObject);

        $RequestSeda = new \RequestSeda();

        if ($dataObject->type == 'Acknowledgement') {
            $messageExchange                = $RequestSeda->getMessageByReference($dataObject->MessageReceivedIdentifier->value);
            $dataObject->TransferringAgency = $dataObject->Sender;
            $dataObject->ArchivalAgency     = $dataObject->Receiver;
            $RequestSeda->updateReceptionDateMessage(['reception_date' => $dataObject->Date, 'message_id' => $messageExchange->message_id]);
        } else if($dataObject->type == 'ArchiveTransferReply'){
            $messageExchange = $RequestSeda->getMessageByReference($dataObject->MessageRequestIdentifier->value);
            $RequestSeda->updateOperationDateMessage(['operation_date' => $dataObject->Date, 'message_id' => $messageExchange->message_id]);
            
        }

        $messageId = \SendMessageExchangeController::saveMessageExchange(['dataObject' => $dataObject, 'res_id_master' => $messageExchange->res_id_master, 'type' => $data['type']]);

        return $response->withJson([
            "messageId" => $messageId
        ]);

    }

}
