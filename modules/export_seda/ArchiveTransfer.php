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

require_once __DIR__ . '/RequestSeda.php';
require_once __DIR__ . '/DOMTemplateProcessor.php';
require_once __DIR__ . '/AbstractMessage.php';

class ArchiveTransfer
{
    private $db;

    public function __construct()
    {
        $this->db = new RequestSeda();
        $_SESSION['error'] = "";
    }

    public function receive($listResId)
    {
        if (!$listResId) {
            return false;
        }

        $messageObject = new stdClass();
        $messageObject = $this->initMessage($messageObject);

        $result = '';
        foreach ($listResId as $resId) {
            if (!empty($result)) {
                $result .= ',';
            }
            $result .= $resId;

            $letterbox = $this->db->getLetter($resId);
            $attachments = $this->db->getAttachments($letterbox->res_id);

            $archiveUnitId = uniqid();
            if ($letterbox->filename) {
                $messageObject->dataObjectPackage->descriptiveMetadata[] = $this->getArchiveUnit($letterbox, "File", $attachments, $archiveUnitId, $letterbox->res_id, null);
                $messageObject->dataObjectPackage->binaryDataObject[] = $this->getBinaryDataObject($letterbox);
            } else {
                $messageObject->dataObjectPackage->descriptiveMetadata[] = $this->getArchiveUnit($letterbox, "File");
            }

            if ($attachments) {
                foreach ($attachments as $attachment) {
                    //if ($attachment->attachment_type == "simple_attachment" || $attachment->attachment_type == "signed_response") {
                    if ($attachment->attachment_type == "signed_response") {
                        $messageObject->dataObjectPackage->descriptiveMetadata[] = $this->getArchiveUnit($attachment, "Response", null, null, "response_" . $attachment->res_id, "arch_" . $archiveUnitId);
                        $messageObject->dataObjectPackage->binaryDataObject[] = $this->getBinaryDataObject($attachment, "response");
                    } else {
                        $messageObject->dataObjectPackage->binaryDataObject[] = $this->getBinaryDataObject($attachment, "attachment");
                    }


                    //}
                }
            }
        }

        $messageId = $this->db->insertMessage($messageObject, "ArchiveTransfer");

        foreach ($listResId as $resId) {
            $this->db->insertUnitIdentifier($messageId, "res_letterbox", $resId);
        }

        if ($messageId) {
            $abstractMessage = new AbstractMessage();
            $abstractMessage->saveXml($messageObject,"ArchiveTransfer",".xml");

            $this->sendAttachment($messageObject);
        } else {
            return false;
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

    private function sendAttachment($messageObject)
    {
        $messageId = $messageObject->messageIdentifier->value;

        foreach ($messageObject->dataObjectPackage->binaryDataObject as $binaryDataObject) {
            $basename = basename($binaryDataObject->uri);
            $dest = __DIR__ . DIRECTORY_SEPARATOR . 'seda2' . DIRECTORY_SEPARATOR . $messageId . DIRECTORY_SEPARATOR . $basename;

            copy($binaryDataObject->uri, $dest);
        }
    }

    private function initMessage($messageObject)
    {
        $date = new DateTime;
        $messageObject->date = $date->format(DateTime::ATOM);
        $messageObject->messageIdentifier = new stdClass();
        $messageObject->messageIdentifier->value = $_SESSION['user']['UserId'] . "-" . date('Ymd-His');

        $messageObject->transferringAgency = new stdClass();
        $messageObject->transferringAgency->identifier = new stdClass();

        $messageObject->archivalAgency = new stdClass();
        $messageObject->archivalAgency->identifier = new stdClass();

        $messageObject->archivalAgreement = new stdClass();

        foreach ($_SESSION['user']['entities'] as $entitie) {
            $entitie = $this->db->getEntitie($entitie['ENTITY_ID']);
            if ($entitie) {
                $messageObject->transferringAgency->identifier->value = $entitie->business_id;
                $messageObject->archivalAgency->identifier->value = $entitie->archival_agency;

                if (!$entitie->business_id) {
                    $_SESSION['error'] .= _TRANSFERRING_AGENCY_SIREN_COMPULSORY;
                }

                if (!$entitie->archival_agency) {
                    $_SESSION['error'] .= _ARCHIVAL_AGENCY_SIREN_COMPULSORY;
                }

                $messageObject->archivalAgreement->value = $entitie->archival_agreement;
            } else {
                $_SESSION['error'] .= _NO_ENTITIES;
            }
        }

        $messageObject->dataObjectPackage = new stdClass();
        $messageObject->dataObjectPackage->binaryDataObject = [];
        $messageObject->dataObjectPackage->descriptiveMetadata = [];
        $messageObject->dataObjectPackage->managementMetadata = new stdClass();

        return $messageObject;
    }

    private function getArchiveUnit($object, $type, $attachments = null, $archiveUnitId = null, $dataObjectReferenceId = null, $relatedObjectReference = null)
    {
        $archiveUnit = new stdClass();

        if ($archiveUnitId) {
            $archiveUnit->id = $archiveUnitId;
        } else {
            $archiveUnit->id = uniqid();
        }

        if ($relatedObjectReference) {
            $archiveUnit->content = $this->getContent($object, $type, $relatedObjectReference);
        } else {
            $archiveUnit->content = $this->getContent($object, $type);
        }

        if ($object->type_id != 0) {
            $archiveUnit->management = $this->getManagement($object);
        }

        if ($dataObjectReferenceId) {
            $archiveUnit->dataObjectReference = new stdClass();
            $archiveUnit->dataObjectReference->dataObjectReferenceId = "doc_" . $dataObjectReferenceId;
        }

        if ($attachments) {
            $archiveUnit->archiveUnit = [];
            foreach ($attachments as $attachment) {
                if ($attachment->res_id_master == $object->res_id) {
                    if ($attachment->attachment_type != "signed_response") {
                        $archiveUnit->archiveUnit[] = $this->getArchiveUnit($attachment, "Item", null, null, "attachment_" . $attachment->res_id);
                    }
                }
            }
            if (count($archiveUnit->archiveUnit) == 0) {
                unset($archiveUnit->archiveUnit);
            }
        }

        return $archiveUnit;
    }

    private function getContent($object, $type, $relatedObjectReference = null)
    {

        $content = new stdClass();

        if ($type == "File") {
            $content->descriptionLevel = $type;

            $content->receivedDate = $object->admission_date;
            $sentDate = new DateTime($object->doc_date);
            $receivedDate = new DateTime($object->admission_date);
            $acquiredDate = new DateTime();
            $content->sentDate = $sentDate->format(DateTime::ATOM);
            $content->receivedDate = $receivedDate->format(DateTime::ATOM);
            $content->acquiredDate = $acquiredDate->format(DateTime::ATOM);

            $content->addressee = [];
            $content->keyword = [];

            if ($object->exp_contact_id) {

                $contact = $this->db->getContact($object->exp_contact_id);
                $entitie = $this->db->getEntitie($object->destination);

                $content->keyword[] = $this->getKeyword($contact);
                $content->addressee[] = $this->getAddresse($entitie, "entitie");
            } else if ($object->dest_contact_id) {
                $contact = $this->db->getContact($object->dest_contact_id);
                $entitie = $this->db->getEntitie($object->destination);

                $content->addressee[] = $this->getAddresse($contact);
                $content->keyword[] = $this->getKeyword($entitie, "entitie");
            } else if ($object->exp_user_id) {
                $user = $this->db->getUserInformation($object->exp_user_id);
                $entitie = $this->db->getEntitie($object->initiator);
                //$entitie = $this->getEntitie($letterbox->destination);

                $content->keyword[] = $this->getKeyword($user);
                $content->addressee[] = $this->getAddresse($entitie, "entitie");
            }

            $content->source = $_SESSION['mail_nature'][$object->nature_id];

            $content->documentType = $object->type_label;
            $content->originatingAgencyArchiveUnitIdentifier = $object->alt_identifier;
            $content->originatingSystemId = $object->res_id;

            $content->title = [];
            $content->title[] = $object->subject;

        } else {
            $content->descriptionLevel = "Item";
            $content->title = [];
            $content->title[] = $object->title;
            $content->originatingSystemId = $object->res_id;
            $content->documentType = "Attachment";

            if ($type == "Response") {
                $content->documentType = "Reply";


                $content->relatedObjectReference = new stdClass();
                $content->relatedObjectReference->references = [];

                $reference = new stdClass();
                $reference->archiveUnitRefId = $relatedObjectReference;
                $content->relatedObjectReference->references[] = $reference;

            }
        }

        if (isset($object->initiator)) {
            $content->originatingAgency = new stdClass();
            $content->originatingAgency->identifier = new stdClass();
            $content->originatingAgency->identifier->value = $this->db->getEntitie($object->initiator)->business_id;
        }

        /*$notes = $this->getNotes($letterbox->res_id);
        $content->custodialHistory = new stdClass();
        $content->custodialHistory->custodialHistoryItem = [];

        foreach ($notes as $note) {
            $content->custodialHistory->custodialHistoryItem[] = $this->getCustodialHistoryItem($note);
        }*/

        return $content;
    }

    private function getManagement($letterbox)
    {
        $management = new stdClass();

        $docTypes = $this->db->getDocTypes($letterbox->type_id);

        $management->appraisalRule = new stdClass();
        $management->appraisalRule->rule = new stdClass();
        $management->appraisalRule->rule->value = $docTypes->retention_rule;
        if ($docTypes->retention_final_disposition == "preservation") {
            $management->appraisalRule->finalAction = "Keep";
        } else {
            $management->appraisalRule->finalAction = "Destroy";
        }


        return $management;
    }

    private function getBinaryDataObject($object, $attachment = false)
    {
        $docServers = $this->db->getDocServer($object->docserver_id);

        $binaryDataObject = new stdClass();

        if ($attachment) {
            $binaryDataObject->id = $attachment . "_" . $object->res_id;
        } else {
            $binaryDataObject->id = $object->res_id;
        }

        $binaryDataObject->messageDigest = new stdClass();
        $binaryDataObject->messageDigest->value = $object->fingerprint;
        $binaryDataObject->messageDigest->algorithm = "sha256";

        $binaryDataObject->size = new stdClass();
        $binaryDataObject->size->value = $object->filesize;

        $uri = str_replace("##", DIRECTORY_SEPARATOR, $object->path);
        $uri = str_replace("#", DIRECTORY_SEPARATOR, $uri);
        $uri .= $object->filename;
        $binaryDataObject->uri = $docServers->path_template . $uri;

        $binaryDataObject->fileInfo = new stdClass();
        $binaryDataObject->fileInfo->filename = basename($binaryDataObject->uri);

        return $binaryDataObject;
    }

    private function getKeyword($informations, $type = null)
    {
        $keyword = new stdClass();
        $keyword->keywordContent = new stdClass();

        if ($type == "entitie") {
            $keyword->keywordType = "corpname";
            $keyword->keywordContent->value = $informations->business_id;
        } else if ($informations->is_corporate_person == "Y") {
            $keyword->keywordType = "corpname";
            $keyword->keywordContent->value = $informations->society;
        } else {
            $keyword->keywordType = "persname";
            $keyword->keywordContent->value = $informations->lastname . " " . $informations->firstname;
        }

        return $keyword;
    }

    private function getAddresse($informations, $type = null)
    {
        $addressee = new stdClass();
        if ($type == "entitie") {
            $addressee->corpname = $informations->entity_label;
            $addressee->identifier = $informations->business_id;
        } else if ($informations->is_corporate_person == "Y") {
            $addressee->corpname = $informations->society;
            $addressee->identifier = $informations->contact_id;
        } else {
            $addressee->firstName = $informations->firstname;
            $addressee->birthName = $informations->lastname;
        }


        return $addressee;
    }

    private function getCustodialHistoryItem($note)
    {
        $custodialHistoryItem = new stdClass();

        $custodialHistoryItem->value = $note->note_text;
        $custodialHistoryItem->when = $note->date_note;

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