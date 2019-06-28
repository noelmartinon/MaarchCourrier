<?php

/*
*   Copyright 2008-2017 Maarch
*
*   This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once "core/class/class_request.php";
require_once "core/class/class_resource.php";
require_once "core/class/docservers_controler.php";

class RequestSeda
{
    private $db;

    protected $statement;

    public function __construct($db = null)
    {

        //create session if NO SESSION
        if (empty($_SESSION['user'])) {
            require_once('core/class/class_functions.php');
            include_once('core/init.php');
            require_once('core/class/class_portal.php');
            require_once('core/class/class_db.php');
            require_once('core/class/class_request.php');
            require_once('core/class/class_core_tools.php');
            require_once('core/class/web_service/class_web_service.php');

            //load Maarch session vars
            $portal = new portal();
            $portal->unset_session();
            $portal->build_config();
            $coreTools = new core_tools();
            $_SESSION['custom_override_id'] = $coreTools->get_custom_id();
            if (isset($_SESSION['custom_override_id'])
                && ! empty($_SESSION['custom_override_id'])
                && isset($_SESSION['config']['corepath'])
                && ! empty($_SESSION['config']['corepath'])
            ) {
                $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                    . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR;
                set_include_path(
                    $path . PATH_SEPARATOR . $_SESSION['config']['corepath']
                    . PATH_SEPARATOR . get_include_path()
                );
            } elseif (isset($_SESSION['config']['corepath'])
                && ! empty($_SESSION['config']['corepath'])
            ) {
                set_include_path(
                    $_SESSION['config']['corepath'] . PATH_SEPARATOR . get_include_path()
                );
            }
            // Load configuration from xml into session
            $_SESSION['config']['app_id'] = $_SESSION['businessapps'][0]['appid'];
            require_once('apps/maarch_entreprise/class/class_business_app_tools.php');

            $businessAppTools = new business_app_tools();
            $coreTools->build_core_config('core/xml/config.xml');

            $businessAppTools->build_business_app_config();
            $coreTools->load_modules_config($_SESSION['modules']);
            $coreTools->load_menu($_SESSION['modules']);
        }

        $this->statement = [];
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = new Database();
        }

        $query = "SELECT * FROM message_exchange WHERE reference = ?";
        $this->statement['getMessageByReference'] = $this->db->prepare($query);

        $query = "SELECT status FROM res_letterbox WHERE res_id = ?";
        $this->statement['getStatusLetter'] = $this->db->prepare($query);

        $query = "SELECT destination FROM res_letterbox WHERE res_id = ?";
        $this->statement['getDestinationLetter'] = $this->db->prepare($query);

        $query = "SELECT res_id, contact_id, filename, docserver_id, path, creation_date, modification_date, type_id, doc_date, admission_date, creation_date, exp_contact_id, dest_contact_id, destination, nature_id, type_label, alt_identifier, subject, title
                  FROM res_view_letterbox
                  WHERE res_id = ?";
        $this->statement['getLetter'] = $this->db->prepare($query);

        $query = "SELECT * FROM entities WHERE entity_id = ?";
        $this->statement['getEntity'] = $this->db->prepare($query);

        $query = "SELECT * FROM notes WHERE identifier = ?";
        $this->statement['getNotes'] = $this->db->prepare($query);

        $query = "SELECT * FROM emails WHERE document->>'id' = ?";
        $this->statement['getMails'] = $this->db->prepare($query);

        $query = "SELECT * FROM doctypes WHERE type_id = ?";
        $this->statement['getDocTypes'] = $this->db->prepare($query);

        $query = "SELECT * FROM unit_identifier WHERE res_id = ?";
        $this->statement['getUnitIdentifierByResId'] = $this->db->prepare($query);

        $query =
            "SELECT res_parent,res_child 
            FROM res_linked 
            WHERE coll_id = 'letterbox_coll' 
            AND  (res_child = ? OR res_parent = ?)";
        $this->statement['getLinks'] = $this->db->prepare($query);

        $query = "SELECT * FROM contacts_v2 WHERE contact_id = ?";
        $this->statement['getContact'] = $this->db->prepare($query);

        $query = "SELECT * FROM docservers WHERE docserver_id = ?";
        $this->statement['getDocServer'] = $this->db->prepare($query);

        $query = "SELECT * FROM res_attachments WHERE res_id_master = ? AND status != 'DEL'";
        $this->statement['getAttachments'] = $this->db->prepare($query);

        $query = "SELECT * FROM history WHERE table_name = ? and record_id = ?";
        $this->statement['getHistory'] = $this->db->prepare($query);

        $query = "INSERT INTO unit_identifier VALUES (?,?,?,?)";
        $this->statement['insertUnitIdentifier'] = $this->db->prepare($query);

        $query = "DELETE FROM message_exchange WHERE message_id = ?";
        $this->statement['deleteMessage'] = $this->db->prepare($query);

        $query = "DELETE FROM unit_identifier WHERE res_id = ?";
        $this->statement['deleteUnitIdentifier'] = $this->db->prepare($query);
    }

    public function getMessageByReference($reference)
    {
        $queryParams = [];

        $queryParams[] = $reference;

        $query = "SELECT * FROM message_exchange WHERE reference = ?";

        $smtp = $this->db->query($query, $queryParams);

        $message = $smtp->fetchObject();

        return $message;
    }

    public function getMessageByIdentifier($id)
    {
        $queryParams = [];

        $queryParams[] = $id;

        $query = "SELECT * FROM message_exchange WHERE message_id = ?";

        $smtp = $this->db->query($query, $queryParams);

        $message = $smtp->fetchObject();

        return $message;
    }

    public function getUnitIdentifierByMessageId($messageId)
    {
        $queryParams = [];

        $queryParams[] = $messageId;

        $query = "SELECT * FROM unit_identifier WHERE message_id = ?";

        $smtp = $this->db->query($query, $queryParams);
        
        $unitIdentifier = [];
        while ($res = $smtp->fetchObject()) {
            $unitIdentifier[] = $res;
        }

        return $unitIdentifier;
    }

    public function getUnitIdentifierByResId($resId)
    {
        $queryParams = [];

        $queryParams[] = $resId;

        $this->statement['getUnitIdentifierByResId']->execute($queryParams);

        $unitIdentifier = $res = $this->statement['getUnitIdentifierByResId']->fetchObject();

        return $unitIdentifier;
    }

    public function getLetter($resId)
    {
        $queryParams = [];

        $queryParams[] = $resId;

        $this->statement['getLetter']->execute($queryParams);

        $letterbox = $this->statement['getLetter']->fetchObject();

        return $letterbox;
    }

    public function getStatusLetter($resId)
    {
        $queryParams = [];

        $queryParams[] = $resId;

        $this->statement['getStatusLetter']->execute($queryParams);

        $res = $this->statement['getStatusLetter']->fetchObject();

        return $res->status;
    }

    public function getDestinationLetter($resId)
    {
        $queryParams = [];

        $queryParams[] = $resId;

        $this->statement['getDestinationLetter']->execute($queryParams);

        $res = $this->statement['getDestinationLetter']->fetchObject();

        return $res->destination;
    }

    public function getLinks($resId)
    {
        $queryParams = [];

        $queryParams[] = $resId;
        $queryParams[] = $resId;

        $this->statement['getLinks']->execute($queryParams);
        $links = [];
        while ($res = $this->statement['getLinks']->fetchObject()) {
            if ($resId == $res->res_parent) {
                $links[] = $res->res_child;
            } else {
                $links[] = $res->res_parent;
            }
        }

        return $links;
    }

    public function getLettersByStatus($status)
    {
        $queryParams = [];

        $queryParams[] = $status;

        $query = "SELECT * FROM res_letterbox WHERE status = ?";

        $smtp = $this->db->query($query, $queryParams);

        $letters = [];
        while ($res = $smtp->fetchObject()) {
            $letters[] = $res;
        }

        return $letters;
    }

    public function getDocTypes($typeId)
    {
        $queryParams = [];

        $queryParams[] = $typeId;

        $this->statement['getDocTypes']->execute($queryParams);

        $docTypes = $this->statement['getDocTypes']->fetchObject();

        return $docTypes;
    }

    public function getUserInformation($userId)
    {
        $queryParams = [];

        $queryParams[] = $userId;

        $query = "SELECT * FROM users WHERE user_id = ?";

        $smtp = $this->db->query($query, $queryParams);

        $user = $smtp->fetchObject();

        return $user;
    }

    public function getNotes($letterboxId)
    {
        $queryParams = [];

        $queryParams[] = $letterboxId;

        $this->statement['getNotes']->execute($queryParams);

        $notes = [];
        while ($res = $this->statement['getEntity']->fetchObject()) {
            $notes[] = $res;
        }

        return $notes;
    }

    public function getMails($letterboxId)
    {
        $queryParams = [];

        $queryParams[] = $letterboxId;

        $this->statement['getMails']->execute($queryParams);

        $mails = [];
        while ($res = $this->statement['getMails']->fetchObject()) {
            $mails[] = $res;
        }

        return $mails;
    }

    public function getEntity($entityId)
    {
        $queryParams = [];

        $queryParams[] = $entityId;

        $this->statement['getEntity']->execute($queryParams);

        $entity = $this->statement['getEntity']->fetchObject();

        return $entity;
    }

    public function getContact($contactId)
    {
        $queryParams = [];

        $queryParams[] = $contactId;

        $this->statement['getContact']->execute($queryParams);

        $contact = $this->statement['getContact']->fetchObject();

        return $contact;
    }

    public function getDocServer($docServerId)
    {
        $queryParams = [];

        $queryParams[] = $docServerId;

        $this->statement['getDocServer']->execute($queryParams);

        $docServers = $this->statement['getDocServer']->fetchObject();

        return $docServers;
    }

    public function getAttachments($resIdMaster)
    {
        $queryParams = [];

        $queryParams[] = $resIdMaster;

        $this->statement['getAttachments']->execute($queryParams);

        $attachments = [];
        while ($res = $this->statement['getAttachments']->fetchObject()) {
            $attachments[] = $res;
        }

        return $attachments;
    }

    public function getUseContact($orgIdentifier)
    {
        $queryParams = [];

        $queryParams[] = $orgIdentifier;
        $queryParams[] = $orgIdentifier;

        $query = "SELECT COUNT(*) FROM message_exchange WHERE sender_org_identifier = ? OR recipient_org_identifier = ?";

        $smtp = $this->db->query($query, $queryParams);

        $res = $smtp->fetchObject();

        return $res;
    }

    public function getAcknowledgement($resIdMaster)
    {
        $queryParams = [];

        $queryParams[] = $resIdMaster;

        $query = "SELECT * FROM res_attachments WHERE res_id_master = ? and type_id = 1 and status != 'DEL'";

        $smtp = $this->db->query($query, $queryParams);

        $res = $smtp->fetchObject();

        return $res;
    }

    public function getReply($resIdMaster)
    {
        $queryParams = [];

        $queryParams[] = $resIdMaster;

        $query = "SELECT * FROM res_attachments WHERE res_id_master = ? and type_id = 2 and status != 'DEL'";

        $smtp = $this->db->query($query, $queryParams);

        $res = $smtp->fetchObject();

        return $res;
    }

    public function getHistory($tableName, $recordId)
    {
        $queryParams = [];

        $queryParams[] = $tableName;
        $queryParams[] = $recordId;

        $this->statement['getHistory']->execute($queryParams);

        $history = [];
        while ($res = $this->statement['getHistory']->fetchObject()) {
            $history[] = $res;
        }

        return $history;
    }

    /*** Generates a local unique identifier
    @return string The unique id*/
    public function generateUniqueId()
    {
        $parts = explode('.', microtime(true));
        $sec   = $parts[0];
        if (!isset($parts[1])) {
            $msec = 0;
        } else {
            $msec = $parts[1];
        }
        $uniqueId = str_pad(base_convert($sec, 10, 36), 6, '0', STR_PAD_LEFT) . str_pad(base_convert($msec, 10, 16), 4, '0', STR_PAD_LEFT);
        $uniqueId .= str_pad(base_convert(mt_rand(), 10, 36), 6, '0', STR_PAD_LEFT);

        return $uniqueId;
    }


    public function insertMessage($messageObject, $type, $aArgs = [])
    {
        $queryParams = [];

        if (!empty($_SESSION['user']['UserId'])) {
            $userId = $_SESSION['user']['UserId'];
        } else {
            $userId = $GLOBALS['userId'];
        }

        if (empty($messageObject->messageId)) {
            $messageObject->messageId = $this->generateUniqueId();
        }

        if (empty($aArgs['status'])) {
            $status = "sent";
        } else {
            $status = $aArgs['status'];
        }

        if (empty($aArgs['fullMessageObject'])) {
            $messageObjectToSave = $messageObject;
        } else {
            $messageObjectToSave = $aArgs['fullMessageObject'];
        }

        if (empty($aArgs['resIdMaster'])) {
            $resIdMaster = null;
        } else {
            $resIdMaster = $aArgs['resIdMaster'];
        }

        if (empty($aArgs['filePath'])) {
            $filePath = null;
        } else {
            $filePath = $aArgs['filePath'];
            $filesize = filesize($filePath);

            //Store resource on docserver

            $resource = file_get_contents($filePath);
            $pathInfo = pathinfo($filePath);
            $storeResult = \Docserver\controllers\DocserverController::storeResourceOnDocServer([
                'collId'            => 'archive_transfer_coll',
                'docserverTypeId'   => 'ARCHIVETRANSFER',
                'encodedResource'   => base64_encode($resource),
                'format'            => $pathInfo['extension']
            ]);

            if (!empty($storeResult['errors'])) {
                var_dump($storeResult['errors']);
            }
            $docserver_id = $storeResult['docserver_id'];
            $filepath     = $storeResult['destination_dir'];
            $filename     = $storeResult['file_destination_name'];
            $docserver     = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $docserver_id]);

            $docserverType = \Docserver\models\DocserverTypeModel::getById(
                ['id' => $docserver['docserver_type_id']]
            );

            $fingerprint = \Resource\controllers\StoreController::getFingerPrint([
                'filePath' => $filePath,
                'mode'     => $docserverType['fingerprint_mode'],
            ]);
        }

        try {
            $query = ("INSERT INTO message_exchange (
				message_id,
				schema,
				type,
				status,
				date,
				reference,
	            account_id ,
				sender_org_identifier,
				sender_org_name,
				recipient_org_identifier,
				recipient_org_name,
				archival_agreement_reference,
				reply_code,
				size,
				data,
				active,
				archived,
                res_id_master,
                docserver_id,
                path,
                filename,
                fingerprint,
                filesize)
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

            $queryParams[] = $messageObject->messageId; // Message Id
            $queryParams[] = "2.1"; //Schema
            $queryParams[] = $type; // Type
            $queryParams[] = $status; // Status
            $queryParams[] = $messageObject->date; // Date
            $queryParams[] = $messageObject->MessageIdentifier->value; // Reference
            $queryParams[] = $userId; // Account Id
            $queryParams[] = $messageObject->TransferringAgency->Identifier->value; // Sender org identifier id
            $queryParams[] = $aArgs['SenderOrgNAme']; //SenderOrgNAme
            $queryParams[] = $messageObject->ArchivalAgency->Identifier->value; // Recipient org identifier id
            $queryParams[] = $aArgs['RecipientOrgNAme']; //RecipientOrgNAme
            $queryParams[] = $messageObject->ArchivalAgreement->value; // Archival agreement reference
            $queryParams[] = $messageObject->ReplyCode; //ReplyCode
            $queryParams[] = 0; // size
            $queryParams[] = json_encode($messageObjectToSave);//$messageObject; // Data
            $queryParams[] = 1; // active
            $queryParams[] = 0; // archived
            $queryParams[] = $resIdMaster; // res_id_master
            $queryParams[] = $docserver_id;
            $queryParams[] = $filepath;
            $queryParams[] = $filename;
            $queryParams[] = $fingerprint;
            $queryParams[] = $filesize;

            $res = $this->db->query($query, $queryParams);
        } catch (Exception $e) {
            return false;
        }

        return $messageObject->messageId;
    }

    public function insertAttachment($data, $type)
    {
        $docserverControler = new docservers_controler();

        $fileInfos = array(
            "tmpDir"      => $data->tmpDir,
            "size"        => $data->size,
            "format"      => $data->format,
            "tmpFileName" => $data->tmpFileName,
        );

        $storeResult = array();

        $resource = file_get_contents($data->tmpDir . '/' . $data->tmpFileName);
        $pathInfo = pathinfo($data->tmpDir . '/' . $data->tmpFileName);
        $storeResult = \Docserver\controllers\DocserverController::storeResourceOnDocServer([
            'collId'            => 'attachments_coll',
            'docserverTypeId'   => 'FASTHD',
            'encodedResource'   => base64_encode($resource),
            'format'            => $pathInfo['extension']
        ]);

        if (isset($storeResult['error']) && $storeResult['error'] <> '') {
            $_SESSION['error'] = $storeResult['error'];
        } else {
            $resAttach = new resource();
            $_SESSION['data'] = array();
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "typist",
                    'value' => ' ',
                    'type' => "string",
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "format",
                    'value' => $data->format,
                    'type' => "string",
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "docserver_id",
                    'value' => $storeResult['docserver_id'],
                    'type' => "string",
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "status",
                    'value' => 'TRA',
                    'type' => "string",
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "offset_doc",
                    'value' => ' ',
                    'type' => "string",
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "title",
                    'value' => $data->title,
                    'type' => "string",
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "attachment_type",
                    'value' => $data->attachmentType,
                    'type' => "string",
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "coll_id",
                    'value' => 'letterbox_coll',
                    'type' => "string",
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "res_id_master",
                    'value' => $data->resIdMaster,
                    'type' => "integer",
                )
            );

            /*if (isset($_REQUEST['contactidAttach']) && $_REQUEST['contactidAttach'] <> '' && is_numeric($_REQUEST['contactidAttach'])) {
                array_push(
                    $_SESSION['data'],
                    array(
                        'column' => "dest_contact_id",
                        'value' => $_REQUEST['contactidAttach'],
                        'type' => "integer",
                    )
                );
            } else if (isset($_REQUEST['contactidAttach']) && $_REQUEST['contactidAttach'] != '' && !is_numeric($_REQUEST['contactidAttach'])) {
                $_SESSION['data'][] = [
                    'column' => 'dest_user',
                    'value' => $_REQUEST['contactidAttach'],
                    'type' => 'string',
                ];
            }

            if (isset($_REQUEST['addressidAttach']) && $_REQUEST['addressidAttach'] <> '' && is_numeric($_REQUEST['addressidAttach'])) {
                array_push(
                    $_SESSION['data'],
                    array(
                        'column' => "dest_address_id",
                        'value' => $_REQUEST['addressidAttach'],
                        'type' => "integer",
                    )
                );
            }
            if(!empty($_REQUEST['chrono'])){
                array_push(
                    $_SESSION['data'],
                    array(
                        'column' => "identifier",
                        'value' => $_REQUEST['chrono'],
                        'type' => "string",
                    )
                );
            }*/
            array_push(
                $_SESSION['data'],
                array(
                    'column' => "type_id",
                    'value' => $type,
                    'type' => "int",
                )
            );

            array_push(
                $_SESSION['data'],
                array(
                    'column' => "relation",
                    'value' => 1,
                    'type' => "int",
                )
            );

            $id = $resAttach->load_into_db(
                'RES_ATTACHMENTS',
                $storeResult['destination_dir'],
                $storeResult['file_destination_name'],
                $storeResult['path_template'],
                $storeResult['docserver_id'],
                $_SESSION['data'],
                $_SESSION['config']['databasetype']
            );
        }
        return true;
    }

    public function insertUnitIdentifier($messageId, $tableName, $resId, $disposition = "")
    {
        try {
            $queryParams = [];

            $queryParams[] = $messageId;
            $queryParams[] = $tableName;
            $queryParams[] = $resId;
            $queryParams[] = $disposition;

            $this->statement['insertUnitIdentifier']->execute($queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function updateDataMessage($reference, $data)
    {
        $queryParams = [];
        $queryParams[] = $data;
        $queryParams[] = $reference;

        try {
            $query = "UPDATE message_exchange SET data = ? WHERE reference = ?";

            $smtp = $this->db->query($query, $queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function updateStatusMessage($reference, $status)
    {
        $queryParams = [];
        $queryParams[] = $status;
        $queryParams[] = $reference;

        try {
            $query = "UPDATE message_exchange SET status = ? WHERE reference = ?";

            $smtp = $this->db->query($query, $queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function updateStatusLetterbox($resId, $status)
    {
        $queryParams = [];
        $queryParams[] = $status;
        $queryParams[] = $resId;

        try {
            $query = "UPDATE res_letterbox SET status = ? WHERE res_id = ?";

            $smtp = $this->db->query($query, $queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function updateStatusAttachment($resId, $status)
    {
        $queryParams = [];
        $queryParams[] = $status;
        $queryParams[] = $resId;

        try {
            $query = "UPDATE res_attachments SET status = ? WHERE res_id_master = ? AND type_id IN (1,2) ";

            $smtp = $this->db->query($query, $queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function deleteMessage($messageId)
    {
        $queryParams = [];
        $queryParams[] = $messageId;
        try {
            $this->statement['deleteMessage']->execute($queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function deleteUnitIdentifier($resId)
    {
        $queryParams = [];

        $queryParams[] = $resId;
        try {
            $this->statement['deleteUnitIdentifier']->execute($queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function getMessagesByReference($id)
    {
        $queryParams = [];

        $queryParams[] = $id;

        $query = "SELECT * FROM message_exchange WHERE reference = ?";

        return $this->db->query($query, $queryParams);
    }

    public function getMessagesByReferenceByDate($id)
    {
        $queryParams = [];

        $queryParams[] = $id;

        $query = "SELECT * FROM message_exchange WHERE reference = ? ORDER BY date asc";

        return $this->db->query($query, $queryParams);
    }

    public function getMessageByIdentifierAndResId($aArgs = [])
    {
        $queryParams = [];

        $query = "SELECT * FROM message_exchange WHERE message_id = ? and res_id_master = ?";
        $queryParams[] = $aArgs['message_id'];
        $queryParams[] = $aArgs['res_id_master'];

        $smtp = $this->db->query($query, $queryParams);

        $message = $smtp->fetchObject();

        return $message;
    }

    public function getEntitiesByBusinessId($businessId)
    {
        $queryParams = [];

        $queryParams[] = $businessId;

        $query = "SELECT * FROM entities WHERE business_id = ?";

        $smtp = $this->db->query($query, $queryParams);

        while ($res = $smtp->fetchObject()) {
            $entities[] = $res;
        }

        return $entities;
    }
    public function updateOperationDateMessage($aArgs = [])
    {
        $queryParams = [];
        $queryParams[] = $aArgs['operation_date'];
        $queryParams[] = $aArgs['message_id'];

        try {
            $query = "UPDATE message_exchange SET operation_date = ? WHERE message_id = ?";

            $smtp = $this->db->query($query, $queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function updateReceptionDateMessage($aArgs = [])
    {
        $queryParams = [];
        $queryParams[] = $aArgs['reception_date'];
        $queryParams[] = $aArgs['message_id'];

        try {
            $query = "UPDATE message_exchange SET reception_date = ? WHERE message_id = ?";

            $smtp = $this->db->query($query, $queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
