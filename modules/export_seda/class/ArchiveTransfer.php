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

require_once __DIR__ . DIRECTORY_SEPARATOR .'../RequestSeda.php';
require_once __DIR__ . DIRECTORY_SEPARATOR .'../DOMTemplateProcessor.php';
require_once __DIR__ . '/AbstractMessage.php';

class ArchiveTransfer
{
    private $db;
    private $abstractMessage;

    public function __construct()
    {
        $this->db = new RequestSeda();
        $this->abstractMessage = new AbstractMessage();
        $_SESSION['error'] = "";
    }

    public function receive($listResId)
    {
        if (!$listResId) {
            return false;
        }

        $messageObject = new stdClass();
        $messageObject = $this->initMessage($messageObject);

        $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[] = $this->getArchiveUnit("RecordGrp",null, null, 'folder_1', null, null);

        $result = $startDate = $endDate = '';
        $i = 1;
        foreach ($listResId as $resId) {
            if (!empty($result)) {
                $result .= ',';
            }
            $result .= $resId;

            $letterbox = $this->db->getLetter($resId);
            $attachments = $this->db->getAttachments($letterbox->res_id);
            $notes = $this->db->getNotes($letterbox->res_id);
            $mails = $this->db->getMails($letterbox->res_id);

            $archiveUnitId = 'mail_'.$i;
            if ($letterbox->filename) {
                $docServers = $this->db->getDocServer($letterbox->docserver_id);
                $uri = str_replace("##", DIRECTORY_SEPARATOR, $letterbox->path);
                $uri = str_replace("#", DIRECTORY_SEPARATOR, $uri);
                $uri .= $letterbox->filename;
                $filePath = $docServers->path_template . $uri;

                $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit[] = $this->getArchiveUnit("File",$letterbox, $attachments, $archiveUnitId, $letterbox->res_id, 'folder_1');
                $messageObject->DataObjectPackage->BinaryDataObject[] = $this->getBinaryDataObject($filePath, $_SESSION['collections'][0]['table'] . '_' . $letterbox->res_id);
            } else {
                $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit[] = $this->getArchiveUnit("File", $letterbox);
            }

            if ($attachments) {
                $j = 1;
                foreach ($attachments as $attachment) {
                    $docServers = $this->db->getDocServer($attachment->docserver_id);

                    $uri = str_replace("##", DIRECTORY_SEPARATOR, $attachment->path);
                    $uri = str_replace("#", DIRECTORY_SEPARATOR, $uri);
                    $uri .= $attachment->filename;

                    $filePath = $docServers->path_template . $uri;
                    if ($attachment->attachment_type == "signed_response") {
                        $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit[] = $this->getArchiveUnit("Response", $attachment, null, 'attachment_'. $i. '_'. $j, "response_" . $attachment->res_id, $archiveUnitId);
                        $messageObject->DataObjectPackage->BinaryDataObject[] = $this->getBinaryDataObject($filePath,$_SESSION['collections'][1]['table'] . '_'.  $attachment->res_id);
                        $j++;
                    } else {
                        $messageObject->DataObjectPackage->BinaryDataObject[] = $this->getBinaryDataObject($filePath, $_SESSION['collections'][1]['table']. '_'.  $attachment->res_id);
                    }
                }
            }

            if ($notes) {
                foreach ($notes as $note) {
                    $id = 'note_'.$note->id;
                    $filePath = $_SESSION['config']['tmppath']. DIRECTORY_SEPARATOR. $id. '.pdf';

                    $this->abstractMessage->createPDF($id,$note->note_text);
                    $messageObject->DataObjectPackage->BinaryDataObject[] = $this->getBinaryDataObject($filePath, $id);
                }
            }

            if ($mails) {
                foreach ($mails as $mail) {
                    $id = 'email_'.$mail->email_id;
                    $filePath = $_SESSION['config']['tmppath']. DIRECTORY_SEPARATOR. $id. '.pdf';
                    $body = str_replace('###', ';', $mail->email_body);
                    $data = 'email n°' . $mail->email_id . '
' .'de ' . $mail->sender_email . '
' . 'à ' . $mail->to_list . '
' . 'objet : ' . $mail->email_object . '
' . 'corps : ' . strip_tags(html_entity_decode($body));

                    $this->abstractMessage->createPDF($id,$data);
                    $messageObject->DataObjectPackage->BinaryDataObject[] = $this->getBinaryDataObject($filePath, $id);
                }
            }

            $format = 'Y-m-d H:i:s.u';
            $creationDate = DateTime::createFromFormat($format, $letterbox->creation_date);
            if ($startDate == '' ) {
                $startDate = $creationDate;
            } else if ( date_diff($startDate,$creationDate) > 0 ) {
                $startDate = $creationDate;
            }

            $modificationDate = DateTime::createFromFormat($format, $letterbox->modification_date);
            if ($endDate == '') {
                $endDate = $modificationDate;
            } else if ( date_diff($endDate,$modificationDate) < 0) {
                $endDate = $modificationDate;
            }

            $i++;
        }


        $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->StartDate = $startDate->format('Y-m-d');
        $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->EndDate = $endDate->format('Y-m-d');

        $messageId = $this->saveMessage($messageObject);

        foreach ($listResId as $resId) {
            $this->db->insertUnitIdentifier($messageId, "res_letterbox", $resId);
        }

        return $result;
    }

    public function deleteMessage($listResId)
    {
        if (!$listResId) {
            return false;
        }

        $resIds = [];
        if (!is_array($listResId)) {
            $resIds[] = $listResId;
        } else {
            $resIds = $listResId;
        }


        foreach ($resIds as $resId) {
            $unitIdentifier = $this->db->getUnitIdentifierByResId($resId);
            $this->db->deleteMessage($unitIdentifier->message_id);
            $this->db->deleteUnitIdentifier($resId);
        }

        return true;
    }

    private function saveMessage($messageObject)
    {
        $data = new stdClass();

        $data->messageId                             = $messageObject->MessageIdentifier->value;
        $data->date                                  = $messageObject->Date;

        $data->messageIdentifier                     = new stdClass();
        $data->messageIdentifier->value              = $messageObject->MessageIdentifier->value;

        $data->transferringAgency                    = new stdClass();
        $data->transferringAgency->identifier        = new stdClass();
        $data->transferringAgency->identifier->value = $messageObject->TransferringAgency->Identifier->value;

        $data->archivalAgency                        = new stdClass();
        $data->archivalAgency->identifier            = new stdClass();
        $data->archivalAgency->identifier->value     = $messageObject->ArchivalAgency->Identifier->value;

        $data->archivalAgreement                     = new stdClass();
        $data->archivalAgreement->value              = $messageObject->ArchivalAgreement->value;

        $data->replyCode                             = new stdClass();
        $data->replyCode->value                      = $messageObject->ReplyCode->value;

        $aArgs                                       = [];
        $aArgs['fullMessageObject']                  = $messageObject;
        $aArgs['SenderOrgNAme']                      = "";
        $aArgs['RecipientOrgNAme']                   = "";

        $messageId = $this->db->insertMessage($data, "ArchiveTransfer", $aArgs);

        return $messageId;
    }

    private function initMessage($messageObject)
    {
        $date = new DateTime;
        $messageObject->Date = $date->format(DateTime::ATOM);
        $messageObject->MessageIdentifier = new stdClass();
        $messageObject->MessageIdentifier->value = $_SESSION['user']['UserId'] . "-" . date('Ymd-His');

        $messageObject->TransferringAgency = new stdClass();
        $messageObject->TransferringAgency->Identifier = new stdClass();

        $messageObject->ArchivalAgency = new stdClass();
        $messageObject->ArchivalAgency->Identifier = new stdClass();

        $messageObject->ArchivalAgreement = new stdClass();

        foreach ($_SESSION['user']['entities'] as $entitie) {
            $entitie = $this->db->getEntitie($entitie['ENTITY_ID']);
            if ($entitie) {
                $messageObject->TransferringAgency->Identifier->value = $entitie->business_id;
                $messageObject->ArchivalAgency->Identifier->value = $entitie->archival_agency;

                if (!$entitie->business_id) {
                    $_SESSION['error'] .= _TRANSFERRING_AGENCY_SIREN_COMPULSORY;
                }

                if (!$entitie->archival_agency) {
                    $_SESSION['error'] .= _ARCHIVAL_AGENCY_SIREN_COMPULSORY;
                }

                $messageObject->ArchivalAgreement->value = $entitie->archival_agreement;
            } else {
                $_SESSION['error'] .= _NO_ENTITIES;
            }
        }

        $messageObject->DataObjectPackage = new stdClass();
        $messageObject->DataObjectPackage->BinaryDataObject = [];
        $messageObject->DataObjectPackage->DescriptiveMetadata = new stdClass();
        $messageObject->DataObjectPackage->ManagementMetadata = new stdClass();

        return $messageObject;
    }

    private function getArchiveUnit($type, $object = null, $attachments = null, $archiveUnitId = null, $dataObjectReferenceId = null, $relatedObjectReference = null)
    {
        $archiveUnit = new stdClass();

        if ($archiveUnitId) {
            $archiveUnit->id = $archiveUnitId;
        } else {
            $archiveUnit->id = uniqid();
        }

        if (isset($object)) {
            if ($relatedObjectReference) {
                $archiveUnit->Content = $this->getContent($type, $object, $relatedObjectReference);
            } else {
                $archiveUnit->Content = $this->getContent($type, $object);
            }

            if ($object->type_id != 0) {
                $archiveUnit->Management = $this->getManagement($object);
            }
        } else {
            $archiveUnit->Content = $this->getContent($type);
        }


        if ($dataObjectReferenceId) {
            $archiveUnit->DataObjectReference = new stdClass();
            if ($type == 'File') {
                $archiveUnit->DataObjectReference->DataObjectReferenceId = $_SESSION['collections'][0]['table'] . '_' .$dataObjectReferenceId;
            } else if ($type == 'Note') {
                $archiveUnit->DataObjectReference->DataObjectReferenceId = 'note_' .$dataObjectReferenceId;
            } else if ($type == 'Email') {
                $archiveUnit->DataObjectReference->DataObjectReferenceId = 'email_' .$dataObjectReferenceId;
            } else  {
                $archiveUnit->DataObjectReference->DataObjectReferenceId = $_SESSION['collections'][1]['table'] . '_' .$dataObjectReferenceId;
            }

        }

        $archiveUnit->ArchiveUnit = [];
        if ($attachments) {
            $i = 1;
            foreach ($attachments as $attachment) {
                if ($attachment->res_id_master == $object->res_id) {
                    if ($attachment->attachment_type != "signed_response") {
                        $archiveUnit->ArchiveUnit[] = $this->getArchiveUnit("Item",$attachment, null, $archiveUnitId. '_attachment_' . $i , $attachment->res_id);
                    }
                }
                $i++;
            }
        }

        $notes = $this->db->getNotes($object->res_id);
        if ($notes) {
            $i = 1;
            foreach ($notes as $note) {
                $noteObject = new stdClass();
                $noteObject->title = 'Note n° ' . $note->id;
                $archiveUnit->ArchiveUnit[] = $this->getArchiveUnit("Note", $noteObject, null, $archiveUnitId. '_note_' . $i,$note->id);
                $i++;
            }
        }

        $emails = $this->db->getMails($object->res_id);
        if ($emails) {
            $i = 1;
            foreach ($emails as $email) {
                $emailObject = new stdClass();
                $emailObject->title = 'Email n° ' . $email->email_id;
                $archiveUnit->ArchiveUnit[] = $this->getArchiveUnit("Email", $emailObject, null, $archiveUnitId. '_email_' . $i,$email->email_id);
                $i++;
            }
        }

        if (count($archiveUnit->ArchiveUnit) == 0) {
            unset($archiveUnit->ArchiveUnit);
        }

        return $archiveUnit;
    }

    private function getContent($type, $object = null, $relatedObjectReference = null)
    {

        $content = new stdClass();

        switch ($type) {
            case 'RecordGrp' :
                $content->DescriptionLevel = $type;
                $content->Title = [];
                $content->DocumentType = 'Folder';

                return $content;
                break;
            case 'File' :
                $content->DescriptionLevel = $type;

                $content->ReceivedDate = $object->admission_date;
                $sentDate = new DateTime($object->doc_date);
                $receivedDate = new DateTime($object->admission_date);
                $acquiredDate = new DateTime();
                $content->SentDate = $sentDate->format(DateTime::ATOM);
                $content->ReceivedDate = $receivedDate->format(DateTime::ATOM);
                $content->AcquiredDate = $acquiredDate->format(DateTime::ATOM);

                $content->Addressee = [];
                $content->Keyword = [];

                if ($object->exp_contact_id) {

                    $contact = $this->db->getContact($object->exp_contact_id);
                    $entitie = $this->db->getEntitie($object->destination);

                    $content->Keyword[] = $this->getKeyword($contact);
                    $content->Addressee[] = $this->getAddresse($entitie, "entitie");
                } else if ($object->dest_contact_id) {
                    $contact = $this->db->getContact($object->dest_contact_id);
                    $entitie = $this->db->getEntitie($object->destination);

                    $content->Addressee[] = $this->getAddresse($contact);
                    $content->Keyword[] = $this->getKeyword($entitie, "entitie");
                } else if ($object->exp_user_id) {
                    $user = $this->db->getUserInformation($object->exp_user_id);
                    $entitie = $this->db->getEntitie($object->initiator);
                    //$entitie = $this->getEntitie($letterbox->destination);

                    $content->Keyword[] = $this->getKeyword($user);
                    $content->Addressee[] = $this->getAddresse($entitie, "entitie");
                }

                $content->Source = $_SESSION['mail_nature'][$object->nature_id];

                $content->DocumentType = $object->type_label;
                $content->OriginatingAgencyArchiveUnitIdentifier = $object->alt_identifier;
                $content->OriginatingSystemId = $object->res_id;

                $content->Title = [];
                $content->Title[] = $object->subject;
                break;
            case 'Item'      :
            case 'Attachment':
            case 'Response'  :
            case 'Note'      :
            case 'Email'     :
                $content->DescriptionLevel = "Item";
                $content->Title = [];
                $content->Title[] = $object->title;

                if ($type == "Item") {
                    $content->DocumentType = "Attachment";
                } else {
                    $content->DocumentType = $type;
                }
                break;
        }

        if (isset($relatedObjectReference)) {
            $content->RelatedObjectReference = new stdClass();
            $content->RelatedObjectReference->References = [];

            $reference = new stdClass();
            $reference->ArchiveUnitRefId = $relatedObjectReference;
            $content->RelatedObjectReference->References[] = $reference;
        }

        if (isset($object->initiator)) {
            $content->OriginatingAgency = new stdClass();
            $content->OriginatingAgency->Identifier = new stdClass();
            $content->OriginatingAgency->Identifier->value = $this->db->getEntitie($object->initiator)->business_id;
        }

        if (isset($object->res_id)) {
            $content->CustodialHistory = new stdClass();
            $content->CustodialHistory->CustodialHistoryItem = [];

            $histories = $this->db->getHistory($_SESSION['collections'][0]['view'],$object->res_id);
            foreach ($histories as $history) {
                $content->CustodialHistory->CustodialHistoryItem[] = $this->getCustodialHistoryItem($history);
            }
        }

        return $content;
    }

    private function getManagement($letterbox)
    {
        $management = new stdClass();

        $docTypes = $this->db->getDocTypes($letterbox->type_id);

        $management->AppraisalRule = new stdClass();
        $management->AppraisalRule->Rule = new stdClass();
        $management->AppraisalRule->Rule->value = $docTypes->retention_rule;
        if ($docTypes->retention_final_disposition == "preservation") {
            $management->AppraisalRule->FinalAction = "Keep";
        } else {
            $management->AppraisalRule->FinalAction = "Destroy";
        }


        return $management;
    }

    private function getBinaryDataObject($filePath, $id)
    {
        $binaryDataObject = new stdClass();

        $data = file_get_contents($filePath);

        $binaryDataObject->id = $id;
        $binaryDataObject->MessageDigest = new stdClass();
        $binaryDataObject->MessageDigest->value = openssl_digest($data,'sha256');
        $binaryDataObject->MessageDigest->algorithm = "sha256";
        $binaryDataObject->Size = filesize($filePath);


        $binaryDataObject->Attachment = new stdClass();
        $binaryDataObject->Attachment->value = base64_encode($data);
        $binaryDataObject->Attachment->filename = basename($filePath);

        $binaryDataObject->Uri = $filePath;

        $binaryDataObject->FileInfo = new stdClass();
        $binaryDataObject->FileInfo->Filename = basename($filePath);

        return $binaryDataObject;
    }

    private function getKeyword($informations, $type = null)
    {
        $keyword = new stdClass();
        $keyword->KeywordContent = new stdClass();

        if ($type == "entitie") {
            $keyword->KeywordType = "corpname";
            $keyword->KeywordContent->value = $informations->business_id;
        } else if ($informations->is_corporate_person == "Y") {
            $keyword->KeywordType = "corpname";
            $keyword->KeywordContent->value = $informations->society;
        } else {
            $keyword->KeywordType = "persname";
            $keyword->KeywordContent->value = $informations->lastname . " " . $informations->firstname;
        }

        return $keyword;
    }

    private function getAddresse($informations, $type = null)
    {
        $addressee = new stdClass();
        if ($type == "entitie") {
            $addressee->Corpname = $informations->entity_label;
            $addressee->Identifier = $informations->business_id;
        } else if ($informations->is_corporate_person == "Y") {
            $addressee->Corpname = $informations->society;
            $addressee->Identifier = $informations->contact_id;
        } else {
            $addressee->FirstName = $informations->firstname;
            $addressee->BirthName = $informations->lastname;
        }


        return $addressee;
    }

    private function getCustodialHistoryItem($history)
    {
        $custodialHistoryItem = new stdClass();

        $custodialHistoryItem->value = $history->info;
        $custodialHistoryItem->when = $history->event_date;

        return $custodialHistoryItem;
    }

    private function getEntitie($entityId, $param) {
        $entitie = $this->db->getEntitie($entityId);

        if (!$entitie) {
            return false;
        }

        if (!$entitie->business_id) {
            $businessId = $this->getEntitieParent($entitie->parent_entity_id,'business_id');

            if (!$businessId) {
                return false;
            }

            $entitie->business_id = $businessId;
        }

        if (!$entitie->archival_agreement) {
            $archivalAgreement = $this->getEntitieParent($entitie->parent_entity_id,'archival_agreement');

            if (!$archivalAgreement) {
                return false;
            }

            $entitie->archival_agreement = $archivalAgreement;
        }

        if (!$entitie->archival_agency) {
            $archivalAgency = $this->getEntitieParent($entitie->parent_entity_id,'archival_agency');

            if (!$archivalAgency) {
                return false;
            }

            $entitie->archival_agency = $archivalAgency;
        }

        return $entitie;
    }

    private function getEntitieParent($parentId,$param) {
        $entitie = $this->db->getEntitie($parentId);

        if (!$entitie) {
            return false;
        }

        $res = false;

        if ($param == 'business_id') {
            if (!$entitie->business_id) {
                $res = $this->getEntitieParent($entitie->parent_entity_id,'business_id');
            } else {
                $res = $entitie->business_id;
            }
        }

        if ($param == 'archival_agreement') {
            if (!$entitie->archival_agreement) {
                $res = $this->getEntitieParent($entitie->parent_entity_id,'archival_agreement');
            } else {
                $res = $entitie->archival_agreement;
            }
        }

        if ($param == 'archival_agency') {
            if (!$entitie->archival_agency) {
                $res = $this->getEntitieParent($entitie->parent_entity_id,'archival_agency');
            } else {
                $res = $entitie->archival_agency;
            }
        }

        return $res;
    }
}