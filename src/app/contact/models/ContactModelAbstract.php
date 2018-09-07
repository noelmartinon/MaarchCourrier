<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Contact Model
* @author dev@maarch.org
*/

namespace Contact\models;

use Resource\models\ResModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

class ContactModelAbstract
{
    public static function getById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        $aContact = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => empty($aArgs['table']) ? ['contacts_v2'] : $aArgs['table'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        if (empty($aContact[0])) {
            return [];
        }

        return $aContact[0];
    }

    public static function create(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['contactType', 'isCorporatePerson', 'userId', 'entityId']);
        ValidatorModel::intVal($aArgs, ['contactType']);
        ValidatorModel::stringType($aArgs, [
            'firstname', 'lastname', 'isCorporatePerson', 'society',
            'societyShort', 'title', 'function', 'otherData', 'userId', 'entityId'
        ]);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'contact_v2_id_seq']);

        DatabaseModel::insert([
            'table'         => 'contacts_v2',
            'columnsValues' => [
                'contact_id'            => $nextSequenceId,
                'contact_type'          => $aArgs['contactType'],
                'is_corporate_person'   => $aArgs['isCorporatePerson'],
                'society'               => $aArgs['society'],
                'society_short'         => $aArgs['societyShort'],
                'firstname'             => $aArgs['firstname'],
                'lastname'              => $aArgs['lastname'],
                'title'                 => $aArgs['title'],
                'function'              => $aArgs['function'],
                'other_data'            => $aArgs['otherData'],
                'user_id'               => $aArgs['userId'],
                'entity_id'             => $aArgs['entityId'],
                'creation_date'         => 'CURRENT_TIMESTAMP',
                'enabled'               => 'Y'

            ]
        ]);

        return $nextSequenceId;
    }

    public static function createAddress(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['contactId', 'contactPurposeId', 'userId', 'entityId', 'isPrivate', 'email']);
        ValidatorModel::intVal($aArgs, ['contactId', 'contactPurposeId']);
        ValidatorModel::stringType($aArgs, [
            'departement', 'addressFirstname', 'addressLastname', 'addressTitle', 'addressFunction', 'occupancy', 'addressNum', 'addressStreet', 'addressComplement',
            'addressTown', 'addressZip', 'addressCountry', 'phone', 'email', 'website', 'salutationHeader', 'salutationFooter', 'addressOtherData',
            'userId', 'entityId', 'isPrivate'
        ]);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'contact_addresses_id_seq']);

        DatabaseModel::insert([
            'table'         => 'contact_addresses',
            'columnsValues' => [
                'id'                    => $nextSequenceId,
                'contact_id'            => $aArgs['contactId'],
                'contact_purpose_id'    => $aArgs['contactPurposeId'],
                'departement'           => $aArgs['departement'],
                'firstname'             => $aArgs['addressFirstname'],
                'lastname'              => $aArgs['addressLastname'],
                'title'                 => $aArgs['addressTitle'],
                'function'              => $aArgs['addressFunction'],
                'occupancy'             => $aArgs['occupancy'],
                'address_num'           => $aArgs['addressNum'],
                'address_street'        => $aArgs['addressStreet'],
                'address_complement'    => $aArgs['addressComplement'],
                'address_town'          => $aArgs['addressTown'],
                'address_postal_code'   => $aArgs['addressZip'],
                'address_country'       => $aArgs['addressCountry'],
                'phone'                 => $aArgs['phone'],
                'email'                 => $aArgs['email'],
                'website'               => $aArgs['website'],
                'salutation_header'     => $aArgs['salutationHeader'],
                'salutation_footer'     => $aArgs['salutationFooter'],
                'other_data'            => $aArgs['otherData'],
                'user_id'               => $aArgs['userId'],
                'entity_id'             => $aArgs['entityId'],
                'is_private'            => $aArgs['isPrivate'],
                'enabled'               => 'Y'

            ]
        ]);

        return $nextSequenceId;
    }

    public static function getFullAddressById(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['addressId']);
        ValidatorModel::intVal($aArgs, ['addressId']);

        $aReturn = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['view_contacts'],
            'where'     => ['ca_id = ?'],
            'data'      => [$aArgs['addressId']],
        ]);

        return $aReturn;
    }

    public static function getContactFullLabel(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['addressId']);
        ValidatorModel::intVal($aArgs, ['addressId']);

        $fullAddress = self::getFullAddressById($aArgs);
        $fullAddress = $fullAddress[0];

        if ($fullAddress['is_corporate_person'] == 'Y') {
            $contactName = strtoupper($fullAddress['society']) . ' ' ;
            if (!empty($fullAddress['society_short'])) {
                $contactName .= '('.$fullAddress['society_short'].') ';
            }
        } else {
            $contactName = strtoupper($fullAddress['contact_lastname']) . ' ' . $fullAddress['contact_firstname'] . ' ';
            if (!empty($fullAddress['society'])) {
                $contactName .= '(' . $fullAddress['society'] . ') ';
            }
        }
        if (!empty($fullAddress['external_contact_id'])) {
            $contactName .= ' - <b>' . $fullAddress['external_contact_id'] . '</b> ';
        }
        if ($fullAddress['is_private'] == 'Y') {
            $contactName .= '('._CONFIDENTIAL_ADDRESS.')';
        } else {
            $contactName .= '- ' . $fullAddress['contact_purpose_label'] . ' : ';
            if (!empty($fullAddress['lastname']) || !empty($fullAddress['firstname'])) {
                $contactName .= $fullAddress['lastname'] . ' ' . $fullAddress['firstname'] . ' ';
            }
            if (!empty($fullAddress['address_num']) || !empty($fullAddress['address_street']) || !empty($fullAddress['address_postal_code']) || !empty($fullAddress['address_town'])) {
                $contactName .= ', '.$fullAddress['address_num'] .' ' . $fullAddress['address_street'] .' ' . $fullAddress['address_postal_code'] .' ' . strtoupper($fullAddress['address_town']);
            }
        }

        return $contactName;
    }

    public static function getContactCommunication(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['contactId']);
        ValidatorModel::intVal($aArgs, ['contactId']);

        $aReturn = DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['contact_communication'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['contactId']],
        ]);

        if (empty($aReturn)) {
            return "";
        } else {
            $aReturn[0]['value'] = trim(trim($aReturn[0]['value']), '/');
            return $aReturn[0];
        }
    }

    public static function getContactIdByCommunicationValue(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['communicationValue']);

        $aReturn = DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['contact_communication'],
            'where'     => ['value = ?'],
            'data'      => [$aArgs['communicationValue']],
        ]);

        if (empty($aReturn)) {
            return "";
        } else {
            return $aReturn[0];
        }
    }

    public static function getAddressByExternalContactId(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['externalContactId']);
        $aReturn = DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['view_contacts'],
            'where'     => ['external_contact_id = ?'],
            'data'      => [$aArgs['externalContactId']],
        ]);

        if (empty($aReturn)) {
            return "";
        } else {
            return $aReturn[0];
        }
    }

    public static function createContactCommunication(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['contactId', 'type', 'value']);
        ValidatorModel::intVal($aArgs, ['contactId']);

        $aReturn = DatabaseModel::insert([
            'table' => 'contact_communication',
            'columnsValues' => [
                'contact_id' => $aArgs['contactId'],
                'type'       => $aArgs['type'],
                'value'      => trim(trim($aArgs['value']), '/')
            ]
        ]);

        return $aReturn;
    }

    public static function getLabelledContactWithAddress(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['contactId', 'addressId']);
        ValidatorModel::intVal($aArgs, ['contactId', 'addressId']);

        $rawContact = ContactModel::getByAddressId(['addressId' => $aArgs['addressId'], 'select' => ['firstname', 'lastname']]);

        $labelledContact = '';
        if (!empty($rawContact)) {
            if (empty($rawContact['firstname']) && empty($rawContact['lastname'])) {
                $rawContact = ContactModel::getById(['id' => $aArgs['contactId'], 'select' => ['firstname', 'lastname']]);
            }
            $labelledContact = $rawContact['firstname']. ' ' .$rawContact['lastname'];
        }

        return $labelledContact;
    }

    public static function getByEmail(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['email']);
        ValidatorModel::stringType($aArgs, ['email']);
        ValidatorModel::arrayType($aArgs, ['select']);

        $aContacts = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['contact_addresses, contacts_v2'],
            'where'     => ['email = ?', 'contact_addresses.enabled = ?', 'contact_addresses.contact_id = contacts_v2.contact_id'],
            'data'      => [$aArgs['email'], 'Y'],
            'order_by'  => ['creation_date'],
        ]);

        if (empty($aContacts[0])) {
            return [];
        }

        return $aContacts[0];
    }

    public static function purgeContact($aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        $firstCount = ResModel::getOnView([
            'select'    => ['count(*) as count'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        $secondCount = DatabaseModel::select([
            'select'    => ['count(*) as count'],
            'table'     => ['contacts_res'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        if ($firstCount[0]['count'] < 1 && $secondCount[0]['count'] < 1) {
            DatabaseModel::delete([
                'table' => 'contact_addresses',
                'where' => ['contact_id = ?'],
                'data'  => [$aArgs['id']]
            ]);
            DatabaseModel::delete([
                'table' => 'contacts_v2',
                'where' => ['contact_id = ?'],
                'data'  => [$aArgs['id']]
            ]);
        }
    }

    public static function getByAddressId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['addressId']);
        ValidatorModel::intVal($aArgs, ['addressId']);

        $aContact = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['contact_addresses'],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['addressId']],
        ]);

        if (empty($aContact[0])) {
            return [];
        }

        return $aContact[0];
    }

    public static function getCommunicationByContactId(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['contactId']);
        ValidatorModel::stringType($aArgs, ['contactId']);

        $aReturn = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['contact_communication'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['contactId']],
        ]);

        return $aReturn[0];
    }

    public static function CreateContactM2M(array $aArgs = [])
    {
        ValidatorModel::notEmpty($aArgs, ['data', 'contactCommunication']);

        $currentContactId    = "0";
        $currentAddressId    = "0";
        $formatedDataContact = [];
        $formatedDataAddress = [];

        foreach ($aArgs['data'] as $key => $value) {
            // On regarde si le contact existe déjà
            if (strtoupper($value['column']) == strtoupper('external_contact_id') && ($value['value'] <> "" || $value['value'] <> null)) {
                try {
                    $res = DatabaseModel::select([
                        'select' => ['contact_id', 'ca_id'],
                        'table'  => ['view_contacts'],
                        'where'  => ['external_contact_id = ?', 'enabled = ?'],
                        'data'   => [$value['value'], 'Y'],
                    ]);

                    $res = $res[0];
                    if (!empty($res['ca_id'])) {
                        $contact_exists   = true;
                        $currentContactId = $res['contact_id'];
                        $currentAddressId = $res['ca_id'];
                    } else {
                        $contact_exists = false;
                    }
                } catch (\Exception $e) {
                    $returnResArray = [
                        'returnCode'  => (int) -1,
                        'contactId'   => '',
                        'addressId'   => '',
                        'contactInfo' => '',
                        'error'       => 'unknown error: ' . $e->getMessage()
                    ];
                    return $returnResArray;
                }
            }

            $aArgs['data'][$key]['column'] = strtolower($value['column']);

            if ($value['table'] == "contacts_v2") {
                $formatedDataContact[$value['column']] = $value['value'];
            } elseif ($value['table'] == "contact_addresses") {
                $formatedDataAddress[$value['column']] = $value['value'];
            }
        }

        // Si le contact n'existe pas, on le créé
        if (!$contact_exists) {
            $contactInfo = self::getContactIdByCommunicationValue(['communicationValue' => $aArgs['contactCommunication']]);
            if (!empty($contactInfo)) {
                $currentContactId = $contactInfo['contact_id'];
            } else {
                try {
                    $currentContactId                     = DatabaseModel::getNextSequenceValue(['sequenceId' => 'contact_v2_id_seq']);
                    $formatedDataContact['user_id']       = 'superadmin';
                    $formatedDataContact['entity_id']     = 'SUPERADMIN';
                    $formatedDataContact['creation_date'] = 'CURRENT_TIMESTAMP';
                    $formatedDataContact['contact_id']    = $currentContactId;

                    DatabaseModel::insert([
                        'table'         => 'contacts_v2',
                        'columnsValues' => $formatedDataContact
                    ]);
                } catch (\Exception $e) {
                    $returnResArray = array(
                        'returnCode'  => (int) -1,
                        'contactId'   => 'ERROR',
                        'addressId'   => 'ERROR',
                        'contactInfo' => '',
                        'error'       => 'contact creation error : '. $e->getMessage(),
                    );
                    
                    return $returnResArray;
                }
            }
            try {
                $currentAddressId                  = DatabaseModel::getNextSequenceValue(['sequenceId' => 'contact_addresses_id_seq']);
                $formatedDataAddress['user_id']    = 'superadmin';
                $formatedDataAddress['entity_id']  = 'SUPERADMIN';
                $formatedDataAddress['contact_id'] = $currentContactId;
                $formatedDataAddress['id']         = $currentAddressId;

                DatabaseModel::insert([
                        'table'         => 'contact_addresses',
                        'columnsValues' => $formatedDataAddress
                    ]);
            } catch (\Exception $e) {
                $returnResArray = array(
                    'returnCode'  => (int) -1,
                    'contactId'   => $currentContactId,
                    'addressId'   => 'ERROR',
                    'contactInfo' => '',
                    'error'       => 'address creation error : '. $e->getMessage(),
                );
                
                return $returnResArray;
            }
            $returnResArray = array(
                'returnCode'  => (int) 0,
                'contactId'   => $currentContactId,
                'addressId'   => $currentAddressId,
                'contactInfo' => 'contact created and attached to doc ... ',
                'error'       => '',
            );
            
            return $returnResArray;
        } else {
            $returnResArray = array(
                'returnCode'  => (int) 0,
                'contactId'   => $currentContactId,
                'addressId'   => $currentAddressId,
                'contactInfo' => 'contact already exist, attached to doc ... ',
                'error'       => '',
            );
            
            return $returnResArray;
        }
    }
}
