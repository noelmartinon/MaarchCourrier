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
    private $externalLink;
    private $xml;
    protected $entities;

    public function __construct()
    {
        $this->db = new RequestSeda();
        $this->abstractMessage = new AbstractMessage();
        $_SESSION['error'] = "";

        $getXml = false;
        $path = '';
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'xml'
            . DIRECTORY_SEPARATOR . 'config.xml'
        )) {
            $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'xml'
                . DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        } elseif (file_exists(
            $_SESSION['config']['corepath'] . 'modules'
            . DIRECTORY_SEPARATOR . 'export_seda'.  DIRECTORY_SEPARATOR . 'xml'
            . DIRECTORY_SEPARATOR . 'config.xml'
        )) {
            $path = $_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        }

        if ($getXml) {
            $this->xml = simplexml_load_file($path);
        }

        $this->entities = [];
    }

    public function receive($listResId)
    {
        if (!$listResId) {
            return false;
        }

        $messageObject = new stdClass();
        $messageObject = $this->initMessage($messageObject);

        if (!empty($_SESSION['error'])) {
            return;
        }

        $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[] = $this->getArchiveUnit(
            "RecordGrp",
            null,
            null,
            'group_1',
            null,
            null
        );

        $result = $startDate = $endDate = '';
        $i = 1;
        foreach ($listResId as $resId) {
            $this->externalLink = false;

            if (!empty($result)) {
                $result .= ',';
            }
            $result .= $resId;

            $letterbox = $this->db->getLetter($resId);
            $attachments = $this->db->getAttachments($letterbox->res_id);
            $notes = $this->db->getNotes($letterbox->res_id);
            $mails = $this->db->getMails($letterbox->res_id);
            $links = $this->db->getLinks($letterbox->res_id);

            $relatedObjectReference = [];
            if (is_array($links)) {
                foreach ($links as $link) {
                    if (!array_search($link, $listResId)) {
                        $relatedObjectReference[$link] = false;
                    } else {
                        $relatedObjectReference[$link] = true;
                    }
                }
            } else {
                if (!array_search($links, $listResId)) {
                    $relatedObjectReference[$links] = false;
                } else {
                    $relatedObjectReference[$links] = true;
                }
            }

            $archiveUnitId = 'letterbox_' . $resId;
            if ($letterbox->filename) {
                $docServers = $this->db->getDocServer($letterbox->docserver_id);
                $uri = str_replace("##", DIRECTORY_SEPARATOR, $letterbox->path);
                $uri = str_replace("#", DIRECTORY_SEPARATOR, $uri);
                $uri .= $letterbox->filename;
                $filePath = $docServers->path_template . $uri;

                if (!file_exists($filePath)) {
                    $_SESSION['error'] = _ERROR_FILE_NOT_EXIST;
                    return;
                }

                $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit[] = $this->getArchiveUnit(
                    "File",
                    $letterbox,
                    $attachments,
                    $archiveUnitId,
                    $letterbox->res_id,
                    $relatedObjectReference
                );

                $messageObject->DataObjectPackage->BinaryDataObject[] = $this->getBinaryDataObject(
                    $filePath,
                    $_SESSION['collections'][0]['table'] . '_' . $letterbox->res_id
                );
            } else {
                $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit[] = $this->getArchiveUnit(
                    "File",
                    $letterbox,
                    null,
                    null,
                    null,
                    $relatedObjectReference
                );
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
                        $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit[] = $this->getArchiveUnit(
                            "Response",
                            $attachment,
                            null,
                            'attachment_'. $i. '_'. $j,
                            "response_" . $attachment->res_id
//                            $archiveUnitId
                        );

                        $messageObject->DataObjectPackage->BinaryDataObject[] = $this->getBinaryDataObject(
                            $filePath,
                            $_SESSION['collections'][1]['table'] . '_'.  $attachment->res_id
                        );
                        $j++;
                    } else {
                        $messageObject->DataObjectPackage->BinaryDataObject[] = $this->getBinaryDataObject(
                            $filePath,
                            $_SESSION['collections'][1]['table']. '_'.  $attachment->res_id
                        );
                    }
                }
            }

            if ($notes) {
                foreach ($notes as $note) {
                    $id = 'note_'.$note->id;
                    $filePath = $_SESSION['config']['tmppath']. DIRECTORY_SEPARATOR. $id. '.pdf';

                    $this->abstractMessage->createPDF($id, $note->note_text);
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

                    $this->abstractMessage->createPDF($id, $data);
                    $messageObject->DataObjectPackage->BinaryDataObject[] = $this->getBinaryDataObject($filePath, $id);
                }
            }

            $format = 'Y-m-d H:i:s.u';
            $creationDate = DateTime::createFromFormat($format, $letterbox->creation_date);
            if ($startDate == '') {
                $startDate = $creationDate;
            } elseif ( date_diff($startDate, $creationDate) > 0 ) {
                $startDate = $creationDate;
            }

            $modificationDate = DateTime::createFromFormat($format, $letterbox->modification_date);
            if ($endDate == '') {
                $endDate = $modificationDate;
            } elseif ( date_diff($endDate, $modificationDate) < 0) {
                $endDate = $modificationDate;
            }

            $i++;
        }

        $originator = "";
        foreach ($messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit as $archiveUnit) {
            if (!empty($archiveUnit->Content->OriginatingAgency->Identifier->value)) {
                $originator = $archiveUnit->Content->OriginatingAgency->Identifier->value;
                break;
            }
        }

        if (!empty($originator)) {
            $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->OriginatingAgency = new stdClass();
            $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->OriginatingAgency->Identifier = new stdClass();
            $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->OriginatingAgency->Identifier->value = $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->ArchiveUnit[0]->Content->OriginatingAgency->Identifier->value;
        } else {
            $_SESSION['error'] = _ERROR_ORIGINATOR_EMPTY;
        }

        $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->StartDate = $startDate->format('Y-m-d');
        $messageObject->DataObjectPackage->DescriptiveMetadata->ArchiveUnit[0]->Content->EndDate = $endDate->format('Y-m-d');

        $messageSaved = $this->saveMessage($messageObject);

        foreach ($listResId as $resId) {
            $this->db->insertUnitIdentifier($messageSaved['messageId'], "res_letterbox", $resId);
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

        $data->MessageIdentifier                     = new stdClass();
        $data->MessageIdentifier->value              = $messageObject->MessageIdentifier->value;

        $data->TransferringAgency                    = new stdClass();
        $data->TransferringAgency->Identifier        = new stdClass();
        $data->TransferringAgency->Identifier->value = $messageObject->TransferringAgency->Identifier->value;

        $data->ArchivalAgency                        = new stdClass();
        $data->ArchivalAgency->Identifier            = new stdClass();
        $data->ArchivalAgency->Identifier->value     = $messageObject->ArchivalAgency->Identifier->value;

        $data->ArchivalAgreement                     = new stdClass();
        $data->ArchivalAgreement->value              = $messageObject->ArchivalAgreement->value;

        $data->ReplyCode                             = $messageObject->ReplyCode;

        $aArgs                                       = [];
        $aArgs['fullMessageObject']                  = $messageObject;
        $aArgs['SenderOrgNAme']                      = "";
        $aArgs['RecipientOrgNAme']                   = "";

        $messageId = $this->db->insertMessage($data, "ArchiveTransfer", $aArgs);

        return $messageId;
    }

    private function initMessage($messageObject)
    {

        $this->directoryMessage = (string) $this->xml->CONFIG->directoryMessage;

        if (!$this->directoryMessage || !is_dir($this->directoryMessage)) {
            $_SESSION['error'] .= _DIRECTORY_MESSAGE_REQUIRED;
            return;
        }

        $date = new DateTime;
        $messageObject->Date = $date->format(DateTime::ATOM);
        $messageObject->MessageIdentifier = new stdClass();
        $messageObject->MessageIdentifier->value = $_SESSION['user']['UserId'] . "-" . date('Ymd-His');

        $messageObject->TransferringAgency = new stdClass();
        $messageObject->TransferringAgency->Identifier = new stdClass();

        $messageObject->ArchivalAgency = new stdClass();
        $messageObject->ArchivalAgency->Identifier = new stdClass();

        $messageObject->ArchivalAgreement = new stdClass();

        foreach ($_SESSION['user']['entities'] as $entity) {
            $res = array_key_exists($entity['ENTITY_ID'], $this->entities);
            if ($res === false) {
                $this->entities[$entity['ENTITY_ID']] = $entity = $this->db->getEntity($entity['ENTITY_ID']);
            } else {
                $entity = $this->entities[$entity['ENTITY_ID']];
            }

            if ($entity) {
                if (!(string) $this->xml->CONFIG->senderOrgRegNumber) {
                    $_SESSION['error'] .= _TRANSFERRING_AGENCY_SIREN_REQUIRED;
                }

                if (!$entity->archival_agency) {
                    $_SESSION['error'] .= _ARCHIVAL_AGENCY_SIREN_REQUIRED;
                }

                if (!$entity->archival_agreement) {
                    $_SESSION['error'] .= _ARCHIVAL_AGREEMENT_REQUIRED;
                }

                if (!empty($_SESSION['error'])) {
                    return;
                }

                $messageObject->TransferringAgency->Identifier->value = (string) $this->xml->CONFIG->senderOrgRegNumber;
                $messageObject->ArchivalAgency->Identifier->value = $entity->archival_agency;
                $messageObject->ArchivalAgreement->value = $entity->archival_agreement;
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

    private function getArchiveUnit(
        $type,
        $object = null,
        $attachments = null,
        $archiveUnitId = null,
        $dataObjectReferenceId = null,
        $relatedObjectReference = null
    ) {
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

            $archiveUnit->Management = $this->getManagement($object);
        } else {
            $archiveUnit->Content = $this->getContent($type);
            $archiveUnit->Management = $this->getManagement();
        }


        if ($dataObjectReferenceId) {
            $archiveUnit->DataObjectReference = new stdClass();
            if ($type == 'File') {
                $archiveUnit->DataObjectReference->DataObjectReferenceId = $_SESSION['collections'][0]['table'] . '_' .$dataObjectReferenceId;
            } elseif ($type == 'Note') {
                $archiveUnit->DataObjectReference->DataObjectReferenceId = 'note_' .$dataObjectReferenceId;
            } elseif ($type == 'Email') {
                $archiveUnit->DataObjectReference->DataObjectReferenceId = 'email_' .$dataObjectReferenceId;
            } else {
                $archiveUnit->DataObjectReference->DataObjectReferenceId = $_SESSION['collections'][1]['table'] . '_' .$dataObjectReferenceId;
            }

        }

        $archiveUnit->ArchiveUnit = [];
        if ($attachments) {
            $i = 1;
            foreach ($attachments as $attachment) {
                if ($attachment->res_id_master == $object->res_id) {
                    if ($attachment->attachment_type != "signed_response") {
                        $archiveUnit->ArchiveUnit[] = $this->getArchiveUnit(
                            "Item",
                            $attachment,
                            null,
                            $archiveUnitId. '_attachment_' . $i,
                            $attachment->res_id
                        );
                    }
                }
                $i++;
            }
        }

        if ($object->res_id) {
            if ($type != 'Note' && $type != 'Email') {
                $notes = $this->db->getNotes($object->res_id);
                if ($notes) {
                    $i = 1;
                    foreach ($notes as $note) {
                        $note->title = 'Note n° ' . $note->id;
                        $archiveUnit->ArchiveUnit[] = $this->getArchiveUnit(
                            "Note",
                            $note,
                            null,
                            $archiveUnitId . '_note_' . $i,
                            $note->id
                        );
                        $i++;
                    }
                }
            }

            if ($type != 'Email' && $type != 'Note') {
                $emails = $this->db->getMails($object->res_id);
                if ($emails) {
                    $i = 1;
                    foreach ($emails as $email) {
                        $email->title = 'Email n° ' . $email->email_id;
                        $archiveUnit->ArchiveUnit[] = $this->getArchiveUnit(
                            "Email",
                            $email,
                            null,
                            $archiveUnitId . '_email_' . $i,
                            $email->email_id
                        );
                        $i++;
                    }
                }
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
            case 'RecordGrp':
                $content->DescriptionLevel = $type;
                $content->Title = [];
                $content->DocumentType = 'Dossier';

                return $content;
                break;
            case 'File':
                $content->DescriptionLevel = $type;

                $sentDate = new DateTime($object->doc_date);
                $receivedDate = new DateTime($object->admission_date);
                $acquiredDate = new DateTime($object->creaction_date);
                $content->SentDate = $sentDate->format(DateTime::ATOM);
                $content->ReceivedDate = $receivedDate->format(DateTime::ATOM);
                $content->AcquiredDate = $acquiredDate->format(DateTime::ATOM);

                $content->Addressee = [];
                $content->Keyword = [];

                $keyword = $addressee = $entity = "";

                if ($object->destination) {
                    $res = array_key_exists($object->destination, $this->entities);
                    if ($res === false) {
                        $this->entities[$object->destination] = $entity = $this->db->getEntity($object->destination);
                    } else {
                        $entity = $this->entities[$object->destination];
                    }
                }

                if ($object->exp_contact_id) {
                    $contact = $this->db->getContact($object->exp_contact_id);
                    $keyword = $this->getKeyword($contact);
                    $addressee = $this->getAddresse($entity, "entity");
                } elseif ($object->dest_contact_id) {
                    $contact = $this->db->getContact($object->dest_contact_id);
                    $addressee = $this->getAddresse($contact);
                    $keyword = $this->getKeyword($entity, "entity");
                } elseif ($object->exp_user_id) {
                    $user = $this->db->getUserInformation($object->exp_user_id);
                    $keyword = $this->getKeyword($user);
                    $addressee = $this->getAddresse($entity, "entity");
                }

                if (!empty($keyword)) {
                    $content->Keyword[] = $keyword;
                }

                if (!empty($addressee)) {
                    $content->Addressee[] = $addressee;
                }

                $content->Source = $_SESSION['mail_nature'][$object->nature_id];

                $content->DocumentType = $object->type_label;
                $content->OriginatingAgencyArchiveUnitIdentifier = $object->alt_identifier;
                $content->OriginatingSystemId = $object->res_id;

                $content->Title = [];
                $content->Title[] = $object->subject;
                break;
            case 'Item':
            case 'Attachment':
            case 'Response':
            case 'Note':
            case 'Email':
                $content->DescriptionLevel = "Item";
                $content->Title = [];
                $content->Title[] = $object->title;

                if ($type == "Item") {
                    $content->DocumentType = "Pièce jointe";
                    $date = new DateTime($object->creation_date);
                    $content->CreatedDate = $date->format('Y-m-d');
                } elseif ($type == "Note") {
                    $content->DocumentType = "Note";
                    $date = new DateTime($object->creation_date);
                    $content->CreatedDate = $date->format('Y-m-d');
                } elseif ($type == "Email") {
                    $content->DocumentType = "Courriel";
                    $date = new DateTime($object->creation_date);
                    $content->CreatedDate = $date->format('Y-m-d');
                } elseif ($type == "Response") {
                    $content->DocumentType = "Réponse";
                    $date = new DateTime($object->creation_date);
                    $content->CreatedDate = $date->format('Y-m-d');
                }

                break;
        }

        if (isset($relatedObjectReference)) {
            $content->RelatedObjectReference = new stdClass();
            $content->RelatedObjectReference->References = [];

            foreach ($relatedObjectReference as $key => $value) {
                $reference = new stdClass();
                if ($value) {
                    $reference->ArchiveUnitRefId = 'letterbox_' . $key;
                    $content->RelatedObjectReference->References[] = $reference;
                } else {
                    $destination = $this->db->getDestinationLetter($key);
                    if (isset($destination)) {
                        $res = array_key_exists($destination, $this->entities);
                        if ($res === false) {
                            $this->entities[$destination] = $entity = $this->db->getEntity($destination);
                        } else {
                            $entity = $this->entities[$destination];
                        }

                        $reference->RepositoryArchiveUnitPID = 'originator:' . $entity->business_id . ':' . $key;
                        $content->RelatedObjectReference->References[] = $reference;
                    }
                }
            }

        }

        if (isset($object->destination)) {
            $content->OriginatingAgency = new stdClass();
            $content->OriginatingAgency->Identifier = new stdClass();

            $res = array_key_exists($object->destination, $this->entities);
            if ($res === false) {
                $this->entities[$object->destination] = $entity = $this->db->getEntity($object->destination);
            } else {
                $entity = $this->entities[$object->destination];
            }
            $content->OriginatingAgency->Identifier->value = $entity->business_id;

            if (empty($content->OriginatingAgency->Identifier->value)) {
                unset($content->OriginatingAgency);
            }
        }

        if (isset($object->res_id)) {
            $content->CustodialHistory = new stdClass();
            $content->CustodialHistory->CustodialHistoryItem = [];

            $histories = $this->db->getHistory($_SESSION['collections'][0]['view'], $object->res_id);
            foreach ($histories as $history) {
                if ($history->event_type != 'VIEW') {
                    $content->CustodialHistory->CustodialHistoryItem[] = $this->getCustodialHistoryItem($history);
                }
            }

            if (count($content->CustodialHistory->CustodialHistoryItem) == 0) {
                unset($content->CustodialHistory);
            }
        }

        return $content;
    }

    private function getManagement($letterbox = null)
    {
        $management = new stdClass();

        if ($letterbox && $letterbox->type_id != 0) {
            $docTypes = $this->db->getDocTypes($letterbox->type_id);

            $management->AppraisalRule = new stdClass();
            $management->AppraisalRule->Rule = new stdClass();
            $management->AppraisalRule->Rule->value = $docTypes->retention_rule;
            $management->AppraisalRule->StartDate = date("Y-m-d");
            if ($docTypes->retention_final_disposition == "conservation") {
                $management->AppraisalRule->FinalAction = "Keep";
            } else {
                $management->AppraisalRule->FinalAction = "Destroy";
            }
        }

        if ((string) $this->xml->CONFIG->accessRuleCode) {
            $management->AccessRule = new stdClass();
            $management->AccessRule->Rule = new stdClass();
            $management->AccessRule->Rule->value = (string)$this->xml->CONFIG->accessRuleCode;
            $management->AccessRule->StartDate = date("Y-m-d");
        }

        return $management;
    }

    private function getBinaryDataObject($filePath, $id)
    {
        $binaryDataObject = new stdClass();

        $pathInfo = pathinfo($filePath);

        if ($id && $id != $pathInfo['filename']) {
            $filename = $pathInfo['filename'] . '_' . $id . '.' . $pathInfo['extension'];
        } else {
            $filename = $pathInfo['filename'] . '_' . rand() . '.' . $pathInfo['extension'];
        }

        $binaryDataObject->id = $id;
        $binaryDataObject->Uri = $filePath;
        $binaryDataObject->MessageDigest = new stdClass();
        $binaryDataObject->MessageDigest->value = hash_file('sha256', $filePath);
        $binaryDataObject->MessageDigest->algorithm = "sha256";
        $binaryDataObject->Size = filesize($filePath);


        $binaryDataObject->Attachment = new stdClass();
        $binaryDataObject->Attachment->filename = $filename;

        $binaryDataObject->FileInfo = new stdClass();
        $binaryDataObject->FileInfo->Filename = $filename;

        $binaryDataObject->FormatIdentification = new stdClass();
        $binaryDataObject->FormatIdentification->MimeType = mime_content_type($filePath);

        return $binaryDataObject;
    }

    private function getKeyword($informations, $type = null)
    {
        $keyword = new stdClass();
        $keyword->KeywordContent = new stdClass();

        if ($type == "entity") {
            $keyword->KeywordType = "corpname";
            $keyword->KeywordContent->value = $informations->business_id;
        } elseif ($informations->is_corporate_person == "Y") {
            $keyword->KeywordType = "corpname";
            $keyword->KeywordContent->value = $informations->society;
        } else {
            $keyword->KeywordType = "persname";
            $keyword->KeywordContent->value = $informations->lastname . " " . $informations->firstname;
        }

        if (empty($keyword->KeywordContent->value)) {
            return null;
        }

        return $keyword;
    }

    private function getAddresse($informations, $type = null)
    {
        $addressee = new stdClass();
        if ($type == "entity") {
            $addressee->Corpname = $informations->entity_label;
            $addressee->Identifier = $informations->business_id;
        } elseif ($informations->is_corporate_person == "Y") {
            $addressee->Corpname = $informations->society;
            $addressee->Identifier = $informations->contact_id;
        } else {
            $addressee->FirstName = $informations->firstname;
            $addressee->BirthName = $informations->lastname;
        }

        if ((empty($addressee->Identifier) || empty($addressee->Corpname)) && (empty($addressee->FirstName) || empty($addressee->BirthName))) {
            return null;
        }

        return $addressee;
    }

    private function getCustodialHistoryItem($history)
    {
        $date = new DateTime($history->event_date);

        $custodialHistoryItem = new stdClass();
        $custodialHistoryItem->value = $history->info;
        $custodialHistoryItem->when = $date->format('Y-m-d');

        return $custodialHistoryItem;
    }

    private function getEntity($entityId, $param)
    {
        $res = array_key_exists($entityId, $this->entities);
        if ($res === false) {
            $this->entities[$entityId] = $entity = $this->db->getEntity($entityId);
        } else {
            $entity = $this->entities[$entityId];
        }

        if (!$entity) {
            return false;
        }

        if (!$entity->business_id) {
            $businessId = $this->getEntityParent(
                $entity->parent_entity_id,
                'business_id'
            );

            if (!$businessId) {
                return false;
            }

            $entity->business_id = $businessId;
        }

        if (!$entity->archival_agreement) {
            $archivalAgreement = $this->getEntityParent(
                $entity->parent_entity_id,
                'archival_agreement'
            );

            if (!$archivalAgreement) {
                return false;
            }

            $entity->archival_agreement = $archivalAgreement;
        }

        if (!$entity->archival_agency) {
            $archivalAgency = $this->getEntityParent(
                $entity->parent_entity_id,
                'archival_agency'
            );

            if (!$archivalAgency) {
                return false;
            }

            $entity->archival_agency = $archivalAgency;
        }

        return $entity;
    }

    private function getEntityParent($parentId, $param)
    {
        $res = array_key_exists($parentId, $this->entities);
        if ($res === false) {
            $this->entities[$parentId] = $entity = $this->db->getEntity($parentId);
        } else {
            $entity = $this->entities[$parentId];
        }

        if (!$entity) {
            return false;
        }

        $res = false;

        if ($param == 'business_id') {
            if (!$entity->business_id) {
                $res = $this->getEntityParent(
                    $entity->parent_entity_id,
                    'business_id'
                );
            } else {
                $res = $entity->business_id;
            }
        }

        if ($param == 'archival_agreement') {
            if (!$entity->archival_agreement) {
                $res = $this->getEntityParent(
                    $entity->parent_entity_id,
                    'archival_agreement'
                );
            } else {
                $res = $entity->archival_agreement;
            }
        }

        if ($param == 'archival_agency') {
            if (!$entity->archival_agency) {
                $res = $this->getEntityParent(
                    $entity->parent_entity_id,
                    'archival_agency'
                );
            } else {
                $res = $entity->archival_agency;
            }
        }

        return $res;
    }
}
