<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Resource Contact Model
* @author dev@maarch.org
*/

namespace Resource\models;

use Contact\models\ContactModel;
use Entity\models\EntityModel;
use SrcCore\controllers\AutoCompleteController;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class ResourceContactModel
{
    public static function getByResId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $aContacts = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['resource_contacts'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']],
        ]);

        return $aContacts;
    }

    public static function getFormattedByResId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $aContacts = DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['resource_contacts'],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']],
        ]);

        foreach ($aContacts as $key => $aContact) {
            if ($aContact['type'] == 'user') {
                $aContacts[$key]['format'] = UserModel::getLabelledUserById(['id' => $aContact['item_id']]);
            } elseif ($aContact['type'] == 'contact') {
                $contact = ContactModel::getOnView([
                    'select' => [
                        'is_corporate_person', 'lastname', 'firstname', 'address_num', 'address_street', 'address_town', 'address_postal_code',
                        'ca_id', 'society', 'contact_firstname', 'contact_lastname'
                    ],
                    'where' => ['ca_id = ?'],
                    'data' => [$aContact['item_id']]
                ]);
                if (isset($contact[0])) {
                    $contact = AutoCompleteController::getFormattedContact(['contact' => $contact[0]]);
                    $aContacts[$key]['format'] = $contact['contact']['otherInfo'];
                }
                
            } elseif ($aContact['type'] == 'entity') {
                $entity = EntityModel::getById(['id' => $aContact['item_id'], 'select' => ['entity_label']]);
                $aContacts[$key]['format'] = $entity['entity_label'];
            }
        }

        return $aContacts;
    }

    public static function create(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['res_id', 'item_id', 'type', 'mode']);
        ValidatorModel::intVal($aArgs, ['res_id', 'item_id']);
        ValidatorModel::stringType($aArgs, ['type', 'mode']);

        DatabaseModel::insert([
            'table'         => 'resource_contacts',
            'columnsValues' => [
                'res_id'    => $aArgs['res_id'],
                'item_id'   => $aArgs['item_id'],
                'type'      => $aArgs['type'],
                'mode'      => $aArgs['mode']
            ]
        ]);

        return true;
    }

    public static function delete(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['where', 'data']);
        ValidatorModel::arrayType($aArgs, ['where', 'data']);

        DatabaseModel::delete([
            'table' => 'resource_contacts',
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }
}
