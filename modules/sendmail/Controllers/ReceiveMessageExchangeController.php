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
require_once __DIR__. DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR . 'export_seda' . DIRECTORY_SEPARATOR. 'Controllers'. DIRECTORY_SEPARATOR. 'ReceiveMessage.php';

class ReceiveMessageExchangeController
{
    public function saveMessageExchange(RequestInterface $request, ResponseInterface $response)
    {

        if (!ServiceModel::hasService(['id' => 'save_numeric_package', 'userId' => $_SESSION['user']['UserId'], 'location' => 'sendmail', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (empty($_SESSION['user']['UserId'])) {
            return $response->withStatus(401)->withJson(['errors' => 'User Not Connected']);
        }

        $data = $request->getParams();

        if (!$this->checkNeededParameters(['data' => $data, 'needed' => ['base64', 'extension', 'size']])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $file     = base64_decode($data['base64']);

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($file);
        $size     = strlen($file);
        $type     = explode('/', $mimeType);
        $ext      = $data['extension'];
        $tmpName  = 'tmp_file_' .$_SESSION['user']['UserId']. '_ArchiveTransfer_' .rand(). '.' . $ext;

        if(!in_array(strtolower($ext), ['zip', 'tar'])){
            return $response->withJson(["errors" => _WRONG_FILE_TYPE_M2M]);
        }

        if ($mimeType != "application/x-tar" && $mimeType != "application/zip" && $mimeType != "application/tar" && $mimeType != "application/x-gzip") {
            return $response->withJson(['errors' => _WRONG_FILE_TYPE]);
        }

        file_put_contents($_SESSION['config']['tmppath'] . $tmpName, $file);

        /********** EXTRACTION DU ZIP ET CONTROLE *******/
        $receiveMessage = new \ReceiveMessage();
        $res = $receiveMessage->receive($_SESSION['config']['tmppath'], $tmpName);

        if ($res['status'] == 1) {
            return $response->withJson(["errors" => _ERROR_RECEIVE_FAIL. ' ' . $res['content']]);
        }

        $sDataObject = $res['content'];
        $sDataObject = json_decode($sDataObject);
        
        $aDefaultConfig = self::readXmlConfig();

        /*************** RES LETTERBOX **************/
        $resLetterboxReturn = self::saveResLetterbox(["dataObject" => $sDataObject, "defaultConfig" => $aDefaultConfig]);

        if(!empty($resLetterboxReturn['errors'])){
            return $response->withJson(["errors" => $resLetterboxReturn['errors']]);
        }

        /*************** CONTACT **************/
        $contactReturn = self::saveContact(["dataObject" => $sDataObject, "defaultConfig" => $aDefaultConfig]);

        if($contactReturn['returnCode'] <> 0){
            return $response->withJson(["errors" => $contactReturn['errors']]);
        }

        /************** MLB COLL EXT **************/
        $return = self::saveExtensionTable(["contact" => $contactReturn, "resId" => $resLetterboxReturn[0]]);

        if(!empty($return['errors'])){
            return $response->withJson(["errors" => $return['errors']]);
        }

        /************** NOTES *****************/
        $notesReturn = self::saveNotes(["dataObject" => $sDataObject, "resId" => $resLetterboxReturn[0]]);

        if(!empty($notesReturn['errors'])){
            return $response->withJson(["errors" => $notesReturn['errors']]);
        }

        /************** RES ATTACHMENT *****************/
        $resAttachmentReturn = self::saveResAttachment(["dataObject" => $sDataObject, "resId" => $resLetterboxReturn[0], "defaultConfig" => $aDefaultConfig]);

        if(!empty($resAttachmentReturn['errors'])){
            return $response->withJson(["errors" => $resAttachmentReturn['errors']]);
        }

        $basketRedirection = null;
        $userBaskets = BasketsModel::getBasketsByUserId(['userId' => $_SESSION['user']['UserId']]);
        foreach ($userBaskets as $value) {
            if($value['basket_id'] == $aDefaultConfig['basketRedirection_afterUpload'][0]){
                $basketRedirection = 'index.php?page=view_baskets&module=basket&baskets='.$value['basket_id'];
                break;
            }
        }

        if(empty($basketRedirection)){
            $basketRedirection = 'index.php';
        }

        return $response->withJson([
            "resId"             => $resLetterboxReturn[0],
            'basketRedirection' => $basketRedirection
        ]);

    }

    private function checkNeededParameters($aArgs = [])
    {
        foreach ($aArgs['needed'] as $value) {
            if (empty($aArgs['data'][$value])) {
                return false;
            }
        }

        return true;
    }

    protected function readXmlConfig()
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
        array_push($aDataContact, ['column' => 'is_corporate_person', 'value' => 'Y',                                              'type' => 'string',  'table' => 'contacts_v2']);
        array_push($aDataContact, ['column' => 'external_contact_id', 'value' => $transferringAgency->Identifier->value,           'type' => 'string',  'table' => 'contacts_v2']);

        array_push($aDataContact, ['column' => 'contact_purpose_id',  'value' => $defaultConfigAddress['contact_purpose_id'],      'type' => 'integer', 'table' => 'contact_addresses']);
        array_push($aDataContact, ['column' => 'firstname',           'value' => $aPersonName[0],                                  'type' => 'string',  'table' => 'contact_addresses']);
        array_push($aDataContact, ['column' => 'lastname',            'value' => $aPersonName[1],                                  'type' => 'string',  'table' => 'contact_addresses']);
        array_push($aDataContact, ['column' => 'departement',         'value' => $transferringAgencyMetadata->Name,                'type' => 'string',  'table' => 'contact_addresses']);
        array_push($aDataContact, ['column' => 'phone',               'value' => $phone,                                           'type' => 'string',  'table' => 'contact_addresses']);
        array_push($aDataContact, ['column' => 'email',               'value' => $email,                                           'type' => 'string',  'table' => 'contact_addresses']);

        $contactModel = new \ContactsModel();
        $contact      = $contactModel->CreateContact($aDataContact);

        $contactCommunicationExisted = $contactModel->getContactCommunication([
            "contactId" => $contact['contactId']
        ]);

        $contactCommunication = $transferringAgencyMetadata->Communication;
        if(empty($contactCommunicationExisted) && !empty($contactCommunication)){
            foreach ( $contactCommunication as $value) {
                $contactModel->createContactCommunication([
                    "contactId" => $contact['contactId'], 
                    "type"      => $value->Channel, 
                    "value"     => $value->value
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
        foreach ($aArgs['dataObject']->Comment as $value) {
            $aDataNote = [
                "identifier" => $aArgs['resId'],
                "tablename"  => "res_letterbox",
                "user_id"    => "superadmin",
                "note_text"  => $value->value,
                "coll_id"    => "letterbox_coll",
            ];

            $noteModel->create($aDataNote);
        }

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
            }
        }
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

}
