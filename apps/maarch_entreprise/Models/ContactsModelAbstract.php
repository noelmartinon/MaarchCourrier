<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Contacts Model
* @author dev@maarch.org
* @ingroup apps
*/

//namespace Apps\Models\Contacts;

require_once 'apps/maarch_entreprise/services/Table.php';

class ContactsModelAbstract extends Apps_Table_Service
{
    public static function getById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['id']);
        static::checkNumeric($aArgs, ['id']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['contacts_v2'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        return $aReturn;
    }

    public static function getWithAddress(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['contactId', 'addressId']);
        static::checkNumeric($aArgs, ['contactId', 'addressId']);


        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['contact_addresses'],
            'where'     => ['id = ?', 'contact_id = ?'],
            'data'      => [$aArgs['addressId'], $aArgs['contactId']],
        ]);

        return $aReturn;
    }

    public static function getFullAddressById(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['addressId']);
        static::checkNumeric($aArgs, ['addressId']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['view_contacts'],
            'where'     => ['ca_id = ?'],
            'data'      => [$aArgs['addressId']],
        ]);

        return $aReturn;
    }

    public static function getContactFullLabel(array $aArgs = []){
        static::checkRequired($aArgs, ['addressId']);
        static::checkNumeric($aArgs, ['addressId']);

        $fullAddress = self::getFullAddressById($aArgs);
        $fullAddress = $fullAddress[0];

        if ($fullAddress['is_corporate_person'] == 'Y') {
            $contactName = $fullAddress['society'] . ' ' ;
            if (!empty($fullAddress['society_short'])) {
                $contactName .= '('.$fullAddress['society_short'].') ';
            }
        } else {
            $contactName = $fullAddress['contact_lastname'] . ' ' . $fullAddress['contact_firstname'] . ' ';
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

    public static function getLabelContactPurpose(array $aArgs = []){
        static::checkRequired($aArgs, ['purposeId']);
        static::checkNumeric($aArgs, ['purposeId']);

        $aReturn = static::select([
            'select'    => ['label'],
            'table'     => ['contact_purposes'],
            'where'     => ['id = ?'],
            'data'      => [$aArgs['purposeId']],
        ]);
    }

    public static function getContactCommunication(array $aArgs = []){
        static::checkRequired($aArgs, ['contactId']);
        static::checkNumeric($aArgs, ['contactId']);

        $aReturn = static::select([
            'select'    => ['*'],
            'table'     => ['contact_communication'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['contactId']],
        ]);

        if($aArgs['allValues'] === true){
            return $aReturn[0];
        } else {
            if(empty($aReturn)){
                return "";
            } else {
                return $aReturn[0]['value'].' ('.$aReturn[0]['type'].')';
            }
        }
        
    }

    public static function createContactCommunication(array $aArgs = []){
        static::checkRequired($aArgs, ['contactId', 'type', 'value']);
        static::checkNumeric($aArgs, ['contactId']);

        $aReturn = static::insertInto([
            'contact_id' => $aArgs['contactId'],
            'type'       => $aArgs['type'],
            'value'      => $aArgs['value']
        ], 'contact_communication');

        return $aReturn;
        
    }

    public static function getLabelledContactWithAddress(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['contactId', 'addressId']);
        static::checkNumeric($aArgs, ['contactId', 'addressId']);


        $rawContact = self::getWithAddress(['contactId' => $aArgs['contactId'], 'addressId' => $aArgs['addressId'], 'select' => ['firstname', 'lastname']]);

        $labelledContact = '';
        if (!empty($rawContact[0])) {
            if (empty($rawContact[0]['firstname']) && empty($rawContact[0]['lastname'])) {
                $rawContact = self::getById(['id' => $aArgs['contactId'], 'select' => ['firstname', 'lastname']]);
            }
            $labelledContact = $rawContact[0]['firstname']. ' ' .$rawContact[0]['lastname'];
        }

        return $labelledContact;
    }

    public static function getByEmail(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['email']);
        static::checkString($aArgs, ['email']);

        $aReturn = static::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['view_contacts'],
            'where'     => ['email = ? and enabled = ?'],
            'data'      => [$aArgs['email'], 'Y'],
            'order_by'     => ['creation_date'],
        ]);

        return $aReturn;
    }

    public static function purgeContact($aArgs)
    {
        static::checkRequired($aArgs, ['id']);
        static::checkNumeric($aArgs, ['id']);

        $aReturn = static::select([
            'select'    => ['count(*)'],
            'table'     => ['res_view_letterbox'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['id']],
        ]);
        
        $aReturnBis = static::select([
            'select'    => ['count(*)'],
            'table'     => ['contacts_res'],
            'where'     => ['contact_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        if ($aReturn[0]['count'] < 1 && $aReturnBis[0]['count'] < 1) {
            $aDelete = static::deleteFrom([
                'table' => 'contact_addresses',
                'where' => ['contact_id = ?'],
                'data'  => [$aArgs['id']]
            ]);
            $aDelete = static::deleteFrom([
                'table' => 'contacts_v2',
                'where' => ['contact_id = ?'],
                'data'  => [$aArgs['id']]
            ]);
        }
    }


    public static function CreateContact($data){
        $func               = new functions();
        $data               = $func->object2array($data);
        $db                 = new Database();
        $queryContactFields = '(';
        $queryContactValues = '(';
        $queryAddressFields = '(';
        $queryAddressValues = '(';
        $iContact           = 0;
        $iAddress           = 0;
        $currentContactId   = "0";
        $currentAddressId   = "0";

        $enabled = "Y";
        foreach ($data as $key => $value) {
            if (strtoupper($value['table']) == strtoupper('contact_addresses') && strtoupper($value['column']) == strtoupper('enabled')) {
                $enabled = strtoupper($value['value']);
                break;
            }
        }

        $countData = count($data);

        for ($i=0;$i<$countData;$i++) {

            if(strtoupper($data[$i]['column']) == strtoupper('email') && ($data[$i]['value'] == "" || $data[$i]['value'] == null)){
                $returnResArray = array(
                    'returnCode'  => (int) 0,
                    'contactId'   => '',
                    'addressId'   => '',
                    'contactInfo' => 'No email attached to contact, skipped ...',
                    'error'       => '',
                ); 
                return $returnResArray;
            }

            if (strtoupper($data[$i]['column']) == strtoupper('email') && ($data[$i]['value'] <> "" || $data[$i]['value'] <> null)) {
                $theString = str_replace(">", "", $data[$i]['value']);
                $mail = explode("<", $theString);
                $mail[0] = trim($mail[0]);
                try {
                    $stmt = $db->query("SELECT contact_id, ca_id FROM view_contacts WHERE email = '" . $mail[0] . "' and enabled = '".$enabled."'");
                    $res = $stmt->fetchObject();
                    if ($res->ca_id <> "") {
                        $contact_exists = true;
                        $currentContactId = $res->contact_id;
                        $currentAddressId = $res->ca_id;
                    } else {
                        $contact_exists = false;
                    }
                } catch (Exception $e) {
                    $returnResArray = array(
                        'returnCode'  => (int) -1,
                        'contactId'   => '',
                        'addressId'   => '',
                        'contactInfo' => '',
                        'error'       => 'unknown error: ' . $e->getMessage(),
                    );  
                    return $returnResArray;
                }
                
            }

            $data[$i]['column'] = strtolower($data[$i]['column']);

            if ($data[$i]['table'] == "contacts_v2") {
                //COLUMN
                $queryContactFields .= $data[$i]['column'] . ',';
                //VALUE
                if ($data[$i]['type'] == 'string' || $data[$i]['type'] == 'date') {
                    $queryContactValues .= "'" . $data[$i]['value'] . "',";
                } else {
                    $queryContactValues .= $data[$i]['value'] . ",";
                }
            } else if ($data[$i]['table'] == "contact_addresses") {
                //COLUMN
                $queryAddressFields .= $data[$i]['column'] . ',';
                //VALUE
                if ($data[$i]['type'] == 'string' || $data[$i]['type'] == 'date') {
                    $queryAddressValues .= "'" . $data[$i]['value'] . "',";
                } else {
                    $queryAddressValues .= $data[$i]['value'] . ",";
                }
            }
        }

        $queryContactFields .= "user_id, entity_id, creation_date)";
        $queryContactValues .= "'superadmin', 'SUPERADMIN', current_timestamp)";

        if (!$contact_exists) {
            try {
                $queryContact = " INSERT INTO contacts_v2 " . $queryContactFields
                   . ' values ' . $queryContactValues ;

                $db->query($queryContact);

                $currentContactId = $db->lastInsertId('contact_v2_id_seq');

            } catch (Exception $e) {
                $returnResArray = array(
                    'returnCode'  => (int) -1,
                    'contactId'   => 'ERROR',
                    'addressId'   => 'ERROR',
                    'contactInfo' => '',
                    'error'       => 'contact creation error : '. $e->getMessage(),
                );
                
                return $returnResArray;
            }
            try {
                $queryAddressFields .= "contact_id, user_id, entity_id)";
                $queryAddressValues .=  $currentContactId . ", 'superadmin', 'SUPERADMIN')";

                $queryAddress = " INSERT INTO contact_addresses " . $queryAddressFields
                       . ' values ' . $queryAddressValues ;

                $db->query($queryAddress);
                $currentAddressId = $db->lastInsertId('contact_addresses_id_seq');
            } catch (Exception $e) {
                $returnResArray = array(
                    'returnCode'  => (int) -1,
                    'contactId'   => $currentContactId,
                    'addressId'   => 'ERROR',
                    'contactInfo' => '',
                    'error'       => 'address creation error : '. $e->getMessage(),
                );
                
                return $returnResArray;
            }
        }else{
            $returnResArray = array(
                'returnCode'  => (int) 0,
                'contactId'   => $currentContactId,
                'addressId'   => $currentAddressId,
                'contactInfo' => 'contact already exist, attached to doc ... '.$queryContactValues,
                'error'       => '',
            );
            
            return $returnResArray;
        }

        $returnResArray = array(
            'returnCode'  => (int) 0,
            'contactId'   => $currentContactId,
            'addressId'   => $currentAddressId,
            'contactInfo' => 'contact created and attached to doc ... '.$queryContactValues,
            'error'       => '',
        );
        
        return $returnResArray;
    }

}
