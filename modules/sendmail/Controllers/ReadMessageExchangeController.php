<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Read Message Exchange Controller
* @author dev@maarch.org
* @ingroup core
*/

require_once 'apps/maarch_entreprise/Models/ContactsModel.php';
require_once 'apps/maarch_entreprise/Models/ResModel.php';
require_once 'modules/export_seda/RequestSeda.php';

class ReadMessageExchangeController
{
    public static function getMessageExchange($aArgs = [])
    {
        $errors = self::control($aArgs);

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $RequestSeda         = new RequestSeda();
        $messageExchangeData = $RequestSeda->getMessageByIdentifier($aArgs['id']);
        $unitIdentifierData  = $RequestSeda->getUnitIdentifierByMessageId($aArgs['id']);
        $messageExchangeData = json_decode($messageExchangeData->data);

        $aDataForm = [];
        $TransferringAgencyMetaData = $messageExchangeData->TransferringAgency->OrganizationDescriptiveMetadata;
        $aDataForm['from']          = $TransferringAgencyMetaData->Contact[0]->PersonName . ' (' . $TransferringAgencyMetaData->Name . ')';

        $ArchivalAgency                 = $messageExchangeData->ArchivalAgency;
        $ArchivalAgencyMetaData         = $ArchivalAgency->OrganizationDescriptiveMetadata;
        $aDataForm['communicationType'] = $ArchivalAgencyMetaData->Communication[0]->value . ' (' . $ArchivalAgencyMetaData->Communication[0]->Channel . ')';
        $aDataForm['contactInfo']       = $ArchivalAgencyMetaData->Name . ' - <b>' . $ArchivalAgency->Identifier->value . '</b> - ' . $ArchivalAgencyMetaData->Contact[0]->PersonName;

        $addressInfo = $ArchivalAgencyMetaData->Contact[0]->Address[0]->PostOfficeBox . ' ' . $ArchivalAgencyMetaData->Contact[0]->Address[0]->StreetName . ' ' . $ArchivalAgencyMetaData->Contact[0]->Address[0]->Postcode . ' ' . $ArchivalAgencyMetaData->Contact[0]->Address[0]->CityName . ' ' . $ArchivalAgencyMetaData->Contact[0]->Address[0]->Country;

        $aDataForm['contactInfo'] .= ', ' . $addressInfo;
        $aDataForm['body']        = $messageExchangeData->Comment[0]->value;
        $aDataForm['isHtml']      = 'N';
        $aDataForm['object']      = $messageExchangeData->DataObjectPackage->DescriptiveMetadata->mail_1->Content->Title[0];

        $aDataForm['attachments'] = [];
        $aDataForm['attachments_version'] = [];
        foreach ($unitIdentifierData as $value) {
            if ($value->tablename == 'res_attachments') {
                $aDataForm['attachments'][] = $value->res_id;
            }
            if ($value->tablename == 'res_version_attachments') {
                $aDataForm['attachments_version'][] = $value->res_id;
            }
            if ($value->tablename == 'res_letterbox') {
                $aDataForm['resMasterAttached'] = 'Y';
            }
            if ($value->disposition == 'body') {
                $aDataForm['disposition'] = $value;
            }
        }

        return $aDataForm;
    }

    protected function control($aArgs = [])
    {
        $errors = [];

        if (empty($aArgs['id'])) {
            array_push($errors, 'wrong format for id');
        }

        return $errors;
    }
}
