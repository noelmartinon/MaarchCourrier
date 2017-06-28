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

	public function __construct($db = null)
	{
	    if ($db) {
	        $this->db = $db;
        } else {
            $this->db = new Database();
        }
	}

	public function getMessageByReference($reference)
    {
        $queryParams = [];

        $queryParams[] = $reference;

        $query = "SELECT * FROM seda WHERE reference = ?";

        $smtp = $this->db->query($query,$queryParams);
        
        $message = $smtp->fetchObject();

        return $message;
    }

    public function getMessageByIdentifier($id)
    {
        $queryParams = [];

        $queryParams[] = $id;

        $query = "SELECT * FROM seda WHERE message_id = ?";

        $smtp = $this->db->query($query,$queryParams);

        $message = $smtp->fetchObject();

        return $message;
    }

    public function getUnitIdentifierByMessageId($messageId)
    {
        $queryParams = [];

        $queryParams[] = $messageId;

        $query = "SELECT res_id FROM unit_identifier WHERE message_id = ?";

        $smtp = $this->db->query($query,$queryParams);
        
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

		$query = "SELECT * FROM unit_identifier WHERE res_id = ?";

		$smtp = $this->db->query($query,$queryParams);
		
		$unitIdentifier = $res = $smtp->fetchObject();

		return $unitIdentifier;
	}

    public function getLetter($resId)
    {
        $queryParams = [];

        $queryParams[] = $resId;

        $query = "SELECT * FROM res_view_letterbox WHERE res_id = ?";

        $smtp = $this->db->query($query,$queryParams);

        $letterbox = $smtp->fetchObject();

        return $letterbox;
    }

    public function getLettersByStatus($status)
    {
        $queryParams = [];

        $queryParams[] = $status;

        $query = "SELECT * FROM res_letterbox WHERE status = ?";

        $smtp = $this->db->query($query,$queryParams);

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

		$query = "SELECT * FROM doctypes WHERE type_id = ?";

		$smtp = $this->db->query($query,$queryParams);
		
		$docTypes = $smtp->fetchObject();

		return $docTypes;
	}

	public function getUserInformation($userId) 
	{
		$queryParams = [];

		$queryParams[] = $userId;

		$query = "SELECT * FROM users WHERE user_id = ?";

		$smtp = $this->db->query($query,$queryParams);
		
		$user = $smtp->fetchObject();

		return $user;
	}

	public function getNotes($letterboxId) 
	{
		$queryParams = [];

		$queryParams[] = $letterboxId;

		$query = "SELECT * FROM notes WHERE identifier = ?";

		$smtp = $this->db->query($query,$queryParams);

		$notes = [];
		while ($res = $smtp->fetchObject()) {
			$notes[] = $res;
		}

		return $notes;
	}

	public function getEntitie($entityId)
	{
		$queryParams = [];

		$queryParams[] = $entityId;

		$query = "SELECT * FROM entities WHERE entity_id = ?";

		$smtp = $this->db->query($query,$queryParams);
		
		$entitie = $smtp->fetchObject();

		return $entitie;
	}

	public function getContact($contactId)
	{
		$queryParams = [];

		$queryParams[] = $contactId;

		$query = "SELECT * FROM contacts_v2 WHERE contact_id = ?";

		$smtp = $this->db->query($query,$queryParams);
		
		$contact = $smtp->fetchObject();

		return $contact;
	}

	public function getDocServer($docServerId)
	{
		$queryParams = [];

		$queryParams[] = $docServerId;

		$query = "SELECT * FROM docservers WHERE docserver_id = ?";

		$smtp = $this->db->query($query,$queryParams);
		
		$docServers = $smtp->fetchObject();

		return $docServers;
	}

	public function getAttachments($resIdMaster)
	{
		$queryParams = [];

		$queryParams[] = $resIdMaster;

		$query = "SELECT * FROM res_attachments WHERE res_id_master = ? AND status != 'DEL'";

		$smtp = $this->db->query($query,$queryParams);
		
		while ($res = $smtp->fetchObject()) {
			$attachments[] = $res;
		}

		return $attachments;
	}

	public function getUseContact($orgIdentifier)
	{
		$queryParams = [];

		$queryParams[] = $orgIdentifier;
		$queryParams[] = $orgIdentifier;

		$query = "SELECT COUNT(*) FROM seda WHERE sender_org_identifier = ? OR recipient_org_identifier = ?";

		$smtp = $this->db->query($query,$queryParams);
		
		$res = $smtp->fetchObject();

		return $res;
	}

	public function getAcknowledgement($resIdMaster) {
        $queryParams = [];

        $queryParams[] = $resIdMaster;

        $query = "SELECT * FROM res_attachments WHERE res_id_master = ? and type_id = 1 and status != 'DEL'";

        $smtp = $this->db->query($query,$queryParams);

        $res = $smtp->fetchObject();

        return $res;
    }

    public function getReply($resIdMaster) {
        $queryParams = [];

        $queryParams[] = $resIdMaster;

        $query = "SELECT * FROM res_attachments WHERE res_id_master = ? and type_id = 2 and status != 'DEL'";

        $smtp = $this->db->query($query,$queryParams);

        $res = $smtp->fetchObject();

        return $res;
    }

	public function insertMessage($messageObject, $type)
	{
		$queryParams = [];
		$messageId = uniqid();

		try {
			$query = ("INSERT INTO seda (
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
				archived)
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

			$queryParams[] = $messageId; // Message Id
			$queryParams[] = "2.1"; //Schema
			$queryParams[] = $type; // Type
			$queryParams[] = "sent"; // Status
			$queryParams[] = $messageObject->date; // Date
			$queryParams[] = $messageObject->messageIdentifier->value; // Reference
			$queryParams[] = $_SESSION['user']['UserId']; // Account Id
			$queryParams[] = $messageObject->transferringAgency->identifier->value; // Sender org identifier id
			$queryParams[] = ""; //SenderOrgNAme
			$queryParams[] = $messageObject->archivalAgency->identifier->value; // Recipient org identifier id
			$queryParams[] = ""; //RecipientOrgNAme
			$queryParams[] = $messageObject->archivalAgreement->value; // Archival agreement reference
			$queryParams[] = $messageObject->replyCode->value; //ReplyCode
			$queryParams[] = 0; // size
			$queryParams[] = json_encode($messageObject);//$messageObject; // Data
			$queryParams[] = 1; // active
			$queryParams[] = 0; // archived

			$res = $this->db->query($query,$queryParams);

		} catch (Exception $e) {
			return false;
		}

		return $messageId;
	}

	public function insertAttachment($data,$type) {
        $docserverControler = new docservers_controler();

	    $fileInfos = array(
            "tmpDir"      => $data->tmpDir,
            "size"        => $data->size,
            "format"      => $data->format,
            "tmpFileName" => $data->tmpFileName,
        );

        $storeResult = array();

        $storeResult = $docserverControler->storeResourceOnDocserver(
            $_SESSION['collection_id_choice'], $fileInfos
        );

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
                    'column' => "logical_adr",
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
                    'value' => $_SESSION['collection_id_choice'],
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

	public function insertUnitIdentifier($messageId, $tableName, $resId) 
	{
		try {
			$query = ("INSERT INTO unit_identifier VALUES (?,?,?)");
			$queryParams = [];

			$queryParams[] = $messageId;
			$queryParams[] = $tableName;
			$queryParams[] = $resId;

			$res = $this->db->query($query,$queryParams);
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}

	public function updateStatusMessage($reference, $status){
        $queryParams = [];
        $queryParams[] = $status;
        $queryParams[] = $reference;

        try {
            $query = "UPDATE seda SET status = ? WHERE reference = ?";

            $smtp = $this->db->query($query,$queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

	public function updateStatusLetterbox($resId,$status) {
        $queryParams = [];
        $queryParams[] = $status;
        $queryParams[] = $resId;

        try {
            $query = "UPDATE res_letterbox SET status = ? WHERE res_id = ?";

            $smtp = $this->db->query($query,$queryParams);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function updateStatusAttachment($resId,$status) {
        $queryParams = [];
        $queryParams[] = $status;
        $queryParams[] = $resId;

        try {
            $query = "UPDATE res_attachments SET status = ? WHERE res_id_master = ? AND type_id IN (1,2) ";

            $smtp = $this->db->query($query,$queryParams);
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
			$query = "DELETE FROM seda WHERE message_id = ?";

			$smtp = $this->db->query($query,$queryParams);
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
			$query = "DELETE FROM unit_identifier WHERE res_id = ?";

			$smtp = $this->db->query($query,$queryParams);
		} catch (Exception $e) {
			return false;
		}

		return true;
	}

}