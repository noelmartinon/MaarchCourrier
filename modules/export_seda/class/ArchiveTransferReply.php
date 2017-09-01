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

require_once __DIR__ . DIRECTORY_SEPARATOR .'../DOMTemplateProcessor.php';
require_once __DIR__ . '/AbstractMessage.php';

class ArchiveTransferReply {

    private $db;
    public function __construct()
    {
        $this->db = new RequestSeda();
    }

    public function receive($data, $resIds)
    {
        $messageObject = $this->getMessageObject($data);
        $abstractMessage = new AbstractMessage();
        //$this->db->insertMessage($data, "ArchiveTransferReply");
        $abstractMessage->saveXml($messageObject,"ArchiveTransferReply", ".xml");

        foreach ($resIds as $resId) {
            $abstractMessage->addAttachment($messageObject->MessageIdentifier->value, $resId, $messageObject->MessageIdentifier->value.".xml", "xml", "Réponse au transfert",2);
        }
    }

    private function getMessageObject($data)
    {
        $messageObject = new stdClass();

        $messageObject->Comment = $data->comment;
        $messageObject->Date = $data->date;
        $messageObject->MessageIdentifier =  new stdClass();
        $messageObject->MessageIdentifier->value = $data->messageIdentifier->value;

        $messageObject->MessageRequestIdentifier =  new stdClass();
        $messageObject->MessageRequestIdentifier->value = $data->messageRequestIdentifier->value;

        $messageObject->ReplyCode = $data->replyCode->value . ' : ' . $data->replyCode->name;

        $messageObject->ArchivalAgency = $this->getOrganisation($data->archivalAgency);
        $messageObject->TransferringAgency = $this->getOrganisation($data->transferringAgency);

        return $messageObject;
    }

    private function getOrganisation($data)
    {
        $organisationObject = new stdClass();
        $organisationObject->Identifier = new stdClass();
        $organisationObject->Identifier->value = $data->id;

        $organisationObject->OrganizationDescriptiveMetadata = new stdClass();
        $organisationObject->OrganizationDescriptiveMetadata->Name = $data->name;
        $organisationObject->OrganizationDescriptiveMetadata->LegalClassification = $data->legalClassification;

        if ($data->address) {
            $organisationObject->OrganizationDescriptiveMetadata->Address = $this->getAddress($data->address);
        }

        if ($data->communication) {
            $organisationObject->OrganizationDescriptiveMetadata->Communication = $this->getCommunication($data->communication);
        }

        if ($data->contact) {
            $organisationObject->OrganizationDescriptiveMetadata->Contact = $this->getContact($data->contact);
        }

        return $organisationObject;
    }

    private function getContact($data)
    {
        $listContact = [];
        foreach ($data as $contact) {
            $tmpContact =  new stdClass();
            $tmpContact->DepartmentName = $contact->departmentName;
            $tmpContact->PersonName = $contact->personName;

            if ($contact->address){
                $tmpContact->Address = [];
                $tmpContact->Address = $this->getAddress($contact->address);
            }

            if ($contact->communication) {
                $tmpContact->Communication = [];
                $tmpContact->Communication = $this->getCommunication($contact->communication);
            }
            $listContact[] = $tmpContact;
        }
        return $listContact;
    }

    private function getAddress($data)
    {
        $listAddress = [];
        foreach ($data as $address) {
            $tmpAddress = new stdClass();
            $tmpAddress->CityName = $address->cityName;
            $tmpAddress->Country = $address->country;
            $tmpAddress->PostCode = $address->postCode;
            $tmpAddress->StreetName = $address->streetName;

            $listAddress[] = $tmpAddress;
        }
        return $listAddress;
    }

    private function getCommunication($data)
    {
        $listCommunication = [];
        foreach ($data as $communication) {
            $tmpCommunication = new stdClass();
            $tmpCommunication->Channel = $communication->channel;

            if ($communication->completeNumber) {
                $tmpCommunication->value = $communication->completeNumber;
            } else {
                $tmpCommunication->value = $communication->URIID;
            }
            $listCommunication[] = $tmpCommunication;
        }
        return $listCommunication;
    }
}