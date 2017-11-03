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

    public static function create(array $aArgs)
    {
        \Core\Models\ValidatorModel::notEmpty($aArgs, ['firstname', 'lastname', 'contactType', 'isCorporatePerson', 'email', 'userId', 'entityId']);
        \Core\Models\ValidatorModel::intVal($aArgs, ['contactType']);
        \Core\Models\ValidatorModel::stringType($aArgs, [
            'firstname', 'lastname', 'isCorporatePerson', 'email', 'society',
            'societyShort', 'title', 'function', 'otherData', 'userId', 'entityId'
        ]);

        $nextSequenceId = \Core\Models\DatabaseModel::getNextSequenceValue(['sequenceId' => 'contact_v2_id_seq']);

        \Core\Models\DatabaseModel::insert([
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
        \Core\Models\ValidatorModel::notEmpty($aArgs, ['contactId', 'contactPurposeId', 'userId', 'entityId', 'isPrivate']);
        \Core\Models\ValidatorModel::intVal($aArgs, ['contactId', 'contactPurposeId']);
        \Core\Models\ValidatorModel::stringType($aArgs, [
            'departement', 'addressFirstname', 'addressLastname', 'addressTitle', 'addressFunction', 'occupancy', 'addressNum', 'addressStreet', 'addressComplement',
            'addressTown', 'addressZip', 'addressCountry', 'phone', 'addressEmail', 'website', 'salutationHeader', 'salutationFooter', 'addressOtherData',
            'userId', 'entityId', 'isPrivate'
        ]);

        $nextSequenceId = \Core\Models\DatabaseModel::getNextSequenceValue(['sequenceId' => 'contact_addresses_id_seq']);

        \Core\Models\DatabaseModel::insert([
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
                'email'                 => $aArgs['addressEmail'],
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
}
