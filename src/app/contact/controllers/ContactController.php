<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Contact Controller
 *
 * @author dev@maarch.org
 */

namespace Contact\controllers;

use Contact\models\ContactFillingModel;
use Contact\models\ContactModel;
use Entity\models\EntityModel;
use Group\models\ServiceModel;
use Resource\models\ResModel;
use SrcCore\models\CoreConfigModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\DatabaseModel;
use SrcCore\models\TextFormatModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class ContactController
{
    public function create(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin']) &&
            !ServiceModel::hasService(['id' => 'my_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'use']) &&
            !ServiceModel::hasService(['id' => 'my_contacts_menu', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu']) &&
            !ServiceModel::hasService(['id' => 'create_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();

        $check = Validator::intVal()->notEmpty()->validate($data['contactType']);
        $check = $check && Validator::intVal()->notEmpty()->validate($data['contactPurposeId']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['isCorporatePerson']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['email']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if (empty($data['userId'])) {
            $data['userId'] = 'superadmin';
        }
        if (empty($data['entityId'])) {
            $data['entityId'] = 'SUPERADMIN';
        }
        if ($data['isCorporatePerson'] != 'Y') {
            $data['isCorporatePerson'] = 'N';
            if (!Validator::stringType()->notEmpty()->validate($data['lastname'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Body lastname is empty or not a string']);
            }
        } else {
            if (!Validator::stringType()->notEmpty()->validate($data['society'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Body society is empty or not a string']);
            }
            $data['addressFirstname'] = $data['firstname'];
            $data['addressLastname'] = $data['lastname'];
            $data['addressTitle'] = $data['title'];
            $data['addressFunction'] = $data['function'];
            unset($data['firstname'], $data['lastname'], $data['title'], $data['function']);
        }

        if (empty($data['isPrivate'])) {
            $data['isPrivate'] = 'N';
        } elseif ($data['isPrivate'] != 'N') {
            $data['isPrivate'] = 'Y';
        }

        $contact = ContactModel::getByEmail(['email' => $data['email'], 'select' => ['contacts_v2.contact_id', 'contact_addresses.id']]);
        if (!empty($contact['id'])) {
            return $response->withJson(['contactId' => $contact['contact_id'], 'addressId' => $contact['id']]);
        }

        $contactId = ContactModel::create($data);

        $data['contactId'] = $contactId;
        $data['external_id'] = empty($data['external_id']) ? '{}' : json_encode($data['external_id']);
        $addressId = ContactModel::createAddress($data);

        if (empty($contactId) || empty($addressId)) {
            return $response->withStatus(500)->withJson(['errors' => '[ContactController create] Contact creation has failed']);
        }

        return $response->withJson(['contactId' => $contactId, 'addressId' => $addressId]);
    }

    public function createAddress(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin']) &&
            !ServiceModel::hasService(['id' => 'my_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'use']) &&
            !ServiceModel::hasService(['id' => 'update_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'use']) &&
            !ServiceModel::hasService(['id' => 'my_contacts_menu', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu']) &&
            !ServiceModel::hasService(['id' => 'create_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $contact = ContactModel::getById(['id' => $aArgs['id'], 'select' => [1]]);
        if (empty($contact)) {
            return $response->withStatus(400)->withJson(['errors' => 'Contact does not exist']);
        }

        $data = $request->getParams();

        $check = Validator::intVal()->notEmpty()->validate($data['contactPurposeId']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['email']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if (empty($data['userId'])) {
            $data['userId'] = 'superadmin';
        }
        if (empty($data['entityId'])) {
            $data['entityId'] = 'SUPERADMIN';
        }
        $data['addressFirstname'] = $data['firstname'];
        $data['addressLastname'] = $data['lastname'];
        $data['addressTitle'] = $data['title'];
        $data['addressFunction'] = $data['function'];
        unset($data['firstname'], $data['lastname'], $data['title'], $data['function']);

        if (empty($data['isPrivate'])) {
            $data['isPrivate'] = 'N';
        } elseif ($data['isPrivate'] != 'N') {
            $data['isPrivate'] = 'Y';
        }
        $data['external_id'] = empty($data['external_id']) ? '{}' : json_encode($data['external_id']);

        $data['contactId'] = $aArgs['id'];
        $addressId = ContactModel::createAddress($data);

        return $response->withJson(['addressId' => $addressId]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin']) &&
            !ServiceModel::hasService(['id' => 'update_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'use']) &&
            !ServiceModel::hasService(['id' => 'my_contacts_menu', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu']) &&
            !ServiceModel::hasService(['id' => 'create_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $contact = ContactModel::getById(['id' => $aArgs['id'], 'select' => [1]]);
        if (empty($contact)) {
            return $response->withStatus(400)->withJson(['errors' => 'Contact does not exist']);
        }

        $data = $request->getParams();
        unset($data['contact_id'], $data['user_id']);

        ContactModel::update(['set' => $data, 'where' => ['contact_id = ?'], 'data' => [$aArgs['id']]]);

        return $response->withJson(['success' => 'success']);
    }

    public function updateAddress(Request $request, Response $response, array $aArgs)
    {
        if (!ServiceModel::hasService(['id' => 'admin_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin']) &&
            !ServiceModel::hasService(['id' => 'update_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'use']) &&
            !ServiceModel::hasService(['id' => 'my_contacts_menu', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu']) &&
            !ServiceModel::hasService(['id' => 'create_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'menu'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $contact = ContactModel::getById(['id' => $aArgs['id'], 'select' => [1]]);
        $address = ContactModel::getByAddressId(['addressId' => $aArgs['addressId'], 'select' => ['external_id']]);
        if (empty($contact) || empty($address)) {
            return $response->withStatus(400)->withJson(['errors' => 'Contact or address do not exist']);
        }

        $data = $request->getParams();
        unset($data['contact_id'], $data['id'], $data['user_id']);
        if (!empty($data['external_id'])) {
            $data['external_id'] =  array_merge((array)json_encode($address['external_id']), $data['external_id']);
        } else {
            $data['external_id'] = $address['external_id'];
        }

        ContactModel::updateAddress(['set' => $data, 'where' => ['contact_id = ?', 'id = ?'], 'data' => [$aArgs['id'], $aArgs['addressId']]]);

        return $response->withJson(['success' => 'success']);
    }

    public function getCommunicationByContactId(Request $request, Response $response, array $aArgs)
    {
        $contact = ContactModel::getCommunicationByContactId([
            'contactId' => $aArgs['contactId'],
        ]);

        return $response->withJson([$contact]);
    }

    public function getFilling(Request $request, Response $response)
    {
        $contactsFilling = ContactFillingModel::get();
        $contactsFilling['rating_columns'] = json_decode($contactsFilling['rating_columns']);

        return $response->withJson(['contactsFilling' => $contactsFilling]);
    }

    public function updateFilling(Request $request, Response $response)
    {
        if (!ServiceModel::hasService(['id' => 'admin_contacts', 'userId' => $GLOBALS['userId'], 'location' => 'apps', 'type' => 'admin'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();
        $check = Validator::boolType()->validate($data['enable']);
        $check = $check && Validator::arrayType()->validate($data['rating_columns']);
        $check = $check && Validator::intVal()->notEmpty()->validate($data['first_threshold']) && $data['first_threshold'] > 0 && $data['first_threshold'] < 99;
        $check = $check && Validator::intVal()->notEmpty()->validate($data['second_threshold']) && $data['second_threshold'] > 1 && $data['second_threshold'] < 100;
        $check = $check && $data['first_threshold'] < $data['second_threshold'];
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $data['rating_columns'] = json_encode($data['rating_columns']);

        ContactFillingModel::update($data);

        return $response->withJson(['success' => 'success']);
    }

    public function getContacts(Request $request, Response $response, array $args)
    {
        $ext = ResModel::getExtById(['resId' => $args['resId']]);

        if (empty($ext)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document does not exist']);
        }

        $queryParams = $request->getQueryParams();

        $contacts = [];
        if ($queryParams['type'] == 'senders') {
            if ($ext['category_id'] == 'outgoing') {
                $contacts = ContactController::getFormattedContacts(['resource' => $ext, 'tableMulti' => 'resource_contacts', 'columnRes' => null]);
            } else {
                $contacts = ContactController::getFormattedContacts(['resource' => $ext, 'tableMulti' => 'contacts_res', 'columnRes' => 'exp_user_id']);
            }
        } elseif ($queryParams['type'] == 'recipients') {
            if ($ext['category_id'] == 'outgoing') {
                $contacts = ContactController::getFormattedContacts(['resource' => $ext, 'tableMulti' => 'contact_res', 'columnRes' => 'exp_user_id']);
            } else {
                $contacts = ContactController::getFormattedContacts(['resource' => $ext, 'tableMulti' => 'resource_contacts', 'columnRes' => null]);
            }
        }

        return $response->withJson(['contacts' => $contacts]);
    }

    public static function getFillingRate(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['contact']);
        ValidatorModel::arrayType($aArgs, ['contact']);

        $contactsFilling = ContactFillingModel::get();
        $contactsFilling['rating_columns'] = json_decode($contactsFilling['rating_columns']);

        if ($contactsFilling['enable'] && !empty($contactsFilling['rating_columns'])) {
            if ($aArgs['contact']['is_corporate_person'] == 'N') {
                foreach ($contactsFilling['rating_columns'] as $key => $value) {
                    if (in_array($value, ['firstname', 'lastname', 'title', 'function'])) {

                        $contactsFilling['rating_columns'][$key] = 'contact_' . $value;
                    }
                }
            }
            $percent = 0;
            foreach ($contactsFilling['rating_columns'] as $ratingColumn) {
                if (!empty($aArgs['contact'][$ratingColumn])) {
                    $percent++;
                }
            }
            $percent = $percent * 100 / count($contactsFilling['rating_columns']);
            if ($percent <= $contactsFilling['first_threshold']) {
                $color = '#ff9e9e';
            } elseif ($percent <= $contactsFilling['second_threshold']) {
                $color = '#f6cd81';
            } else {
                $color = '#ccffcc';
            }

            return ['rate' => $percent, 'color' => $color];
        }

        return [];
    }

    public static function formatContactAddressAfnor(array $aArgs)
    {
        $formattedAddress = '';

        // Entete pour societe
        if ($aArgs['is_corporate_person'] == 'Y') {
            // Ligne 1
            $formattedAddress .= substr($aArgs['society'], 0, 38)."\n";

            // Ligne 2
            if (!empty($aArgs['title']) || !empty($aArgs['firstname']) || !empty($aArgs['lastname'])) {
                $formattedAddress .= ContactController::controlLengthNameAfnor([
                    'title' => $aArgs['title'],
                    'fullName' => $aArgs['firstname'].' '.$aArgs['lastname'],
                    'strMaxLength' => 38, ])."\n";
            }

            // Ligne 3
            if (!empty($aArgs['address_complement'])) {
                $formattedAddress .= substr($aArgs['address_complement'], 0, 38)."\n";
            }
        } else {
            // Ligne 1
            $formattedAddress .= ContactController::controlLengthNameAfnor([
                                    'title' => $aArgs['contact_title'],
                                    'fullName' => $aArgs['contact_firstname'].' '.$aArgs['contact_lastname'],
                                    'strMaxLength' => 38, ])."\n";

            // Ligne 2
            if (!empty($aArgs['occupancy'])) {
                $formattedAddress .= substr($aArgs['occupancy'], 0, 38)."\n";
            }

            // Ligne 3
            if (!empty($aArgs['address_complement'])) {
                $formattedAddress .= substr($aArgs['address_complement'], 0, 38)."\n";
            }
        }
        // Ligne 4
        if (!empty($aArgs['address_num'])) {
            $aArgs['address_num'] = TextFormatModel::normalize(['string' => $aArgs['address_num']]);
            $aArgs['address_num'] = preg_replace('/[^\w]/s', ' ', $aArgs['address_num']);
            $aArgs['address_num'] = strtoupper($aArgs['address_num']);
        }

        if (!empty($aArgs['address_street'])) {
            $aArgs['address_street'] = TextFormatModel::normalize(['string' => $aArgs['address_street']]);
            $aArgs['address_street'] = preg_replace('/[^\w]/s', ' ', $aArgs['address_street']);
            $aArgs['address_street'] = strtoupper($aArgs['address_street']);
        }

        $formattedAddress .= substr($aArgs['address_num'].' '.$aArgs['address_street'], 0, 38)."\n";

        // Ligne 5
        // $formattedAddress .= "\n";

        // Ligne 6
        $aArgs['address_postal_code'] = strtoupper($aArgs['address_postal_code']);
        $aArgs['address_town'] = strtoupper($aArgs['address_town']);
        $formattedAddress .= substr($aArgs['address_postal_code'].' '.$aArgs['address_town'], 0, 38);

        return $formattedAddress;
    }

    public static function getContactAfnor(array $aArgs)
    {
        $afnorAddress = ['Afnor',
            '',
            '',
            '',
            '',
            '',
            ''
        ];

        if ($aArgs['is_corporate_person'] == 'Y') {
            // Ligne 1
            $afnorAddress[1] = substr($aArgs['society'], 0, 38);

            // Ligne 2
            if (!empty($aArgs['title']) || !empty($aArgs['firstname']) || !empty($aArgs['lastname'])) {
                $afnorAddress[2] = ContactController::controlLengthNameAfnor([
                    'title'         => $aArgs['title'],
                    'fullName'      => $aArgs['firstname'].' '.$aArgs['lastname'],
                    'strMaxLength'  => 38
                ]);
            }
        } else {
            // Ligne 2
            if (!empty($aArgs['contact_title']) || !empty($aArgs['contact_firstname']) || !empty($aArgs['contact_lastname'])) {
                $afnorAddress[2] = ContactController::controlLengthNameAfnor([
                    'title'         => $aArgs['contact_title'],
                    'fullName'      => $aArgs['contact_firstname'].' '.$aArgs['contact_lastname'],
                    'strMaxLength'  => 38
                ]);
            }

        }
        // Ligne 3
        if (!empty($aArgs['address_complement'])) {
            $afnorAddress[3] = substr($aArgs['address_complement'], 0, 38);
        }

        // Ligne 4
        if (!empty($aArgs['address_num'])) {
            $aArgs['address_num'] = TextFormatModel::normalize(['string' => $aArgs['address_num']]);
            $aArgs['address_num'] = preg_replace('/[^\w]/s', ' ', $aArgs['address_num']);
            $aArgs['address_num'] = strtoupper($aArgs['address_num']);
        }
        if (!empty($aArgs['address_street'])) {
            $aArgs['address_street'] = TextFormatModel::normalize(['string' => $aArgs['address_street']]);
            $aArgs['address_street'] = preg_replace('/[^\w]/s', ' ', $aArgs['address_street']);
            $aArgs['address_street'] = strtoupper($aArgs['address_street']);
        }
        $afnorAddress[4] = substr($aArgs['address_num'].' '.$aArgs['address_street'], 0, 38);

        // Ligne 5
        $afnorAddress[5] = '';

        // Ligne 6
        $aArgs['address_postal_code'] = strtoupper($aArgs['address_postal_code']);
        $aArgs['address_town'] = strtoupper($aArgs['address_town']);
        $afnorAddress[6] = substr($aArgs['address_postal_code'].' '.$aArgs['address_town'], 0, 38);

        return $afnorAddress;
    }

    public static function controlLengthNameAfnor(array $aArgs)
    {
        $aCivility = ContactModel::getCivilities();
        if (strlen($aArgs['title'].' '.$aArgs['fullName']) > $aArgs['strMaxLength']) {
            $aArgs['title'] = $aCivility[$aArgs['title']]['abbreviation'];
        } else {
            $aArgs['title'] = $aCivility[$aArgs['title']]['label'];
        }

        return substr($aArgs['title'].' '.$aArgs['fullName'], 0, $aArgs['strMaxLength']);
    }

    public function availableReferential()
    {
        $customId = CoreConfigModel::getCustomId();

        $referentialDirectory = 'referential/ban/indexes';
        if (is_dir("custom/{$customId}/".$referentialDirectory)) {
            $customFilesDepartments = scandir("custom/{$customId}/".$referentialDirectory);
        }
        if (is_dir($referentialDirectory)) {
            $filesDepartments = scandir($referentialDirectory);
        }

        $departments = [];
        if (!empty($customFilesDepartments)) {
            foreach ($customFilesDepartments as $value) {
                if ($value != '.' && $value != '..' && is_writable("custom/{$customId}/".$referentialDirectory.'/'.$value)) {
                    $departments[] = $value;
                }
            }
        }
        if (!empty($filesDepartments)) {
            foreach ($filesDepartments as $value) {
                if ($value != '.' && $value != '..' && !in_array($value, $departments) && is_writable($referentialDirectory.'/'.$value)) {
                    $departments[] = $value;
                }
            }
        }

        if (!empty($departments)) {
            sort($departments, SORT_NUMERIC);

            return $departments;
        } else {
            return false;
        }
    }

    public static function getFormattedContacts(array $args)
    {
        ValidatorModel::notEmpty($args, ['resource', 'tableMulti']);
        ValidatorModel::arrayType($args, ['resource']);
        ValidatorModel::stringType($args, ['tableMulti', 'columnRes']);

        $resource = $args['resource'];

        $rawContacts = [];
        if ($resource['is_multicontacts'] == 'Y' || !isset($args['columnRes'])) {
            if ($args['tableMulti'] == 'contacts_res') {
                $multiContacts = DatabaseModel::select([
                    'select' => ['contact_id', 'address_id', 'mode'],
                    'table' => ['contacts_res'],
                    'where' => ['res_id = ?'],
                    'data' => [$resource['res_id']]
                ]);

                foreach ($multiContacts as $multiContact) {
                    $rawContacts[] = [
                        'login'         => $multiContact['contact_id'],
                        'address_id'    => $multiContact['address_id'],
                        'mode'          => $multiContact['mode']
                    ];
                }
            } elseif ($args['tableMulti'] == 'resource_contacts') {
                $multiContacts = DatabaseModel::select([
                    'select' => ['item_id', 'type', 'mode'],
                    'table' => ['resource_contacts'],
                    'where' => ['res_id = ?'],
                    'data' => [$resource['res_id']]
                ]);

                foreach ($multiContacts as $multiContact) {
                    $rawContacts[] = [
                        'user_id' => $multiContact['type'] == 'user' ? $multiContact['item_id'] : null,
                        'address_id' => $multiContact['type'] == 'contact' ? $multiContact['item_id'] : null,
                        'entity_id' => $multiContact['type'] == 'entity' ? $multiContact['item_id'] : null,
                    ];
                }
            }
        } else {
            $rawContacts[] = [
                'login'         => $resource[$args['columnRes']],
                'address_id'    => $resource['address_id'],
            ];
        }

        $contacts = [];

        foreach ($rawContacts as $rawContact) {
            if (!empty($rawContact['address_id'])) {
                $contactView = ContactModel::getOnView([
                    'select' => [
                        'is_corporate_person', 'lastname', 'firstname', 'address_num', 'address_street', 'address_complement',
                        'address_town', 'address_postal_code', 'address_country', 'ca_id', 'society', 'website', 'phone',
                        'contact_firstname', 'contact_lastname', 'address_country', 'email', 'function', 'contact_other_data',
                        'occupancy'
                    ],
                    'where'     => ['ca_id = ?'],
                    'data'      => [$rawContact['address_id']]
                ]);

                $contactView = $contactView[0];

                if (!empty($rawContact['mode']) && $rawContact['mode'] == 'third') {
                    $mode = 'third';
                } else {
                    $mode = $contactView['is_corporate_person'] == 'Y' ? 'corporate' : 'physical';
                }

                $contact = [
                    'mode'      => $mode,
                    'firstname' => $contactView['firstname'] ?? '',
                    'lastname'  => $contactView['lastname'] ?? '',
                    'email'     => $contactView['email'] ?? '',
                    'phone'     => $contactView['phone'] ?? '',
                    'society'   => $contactView['society'] ?? '',
                    'function'  => $contactView['function'] ?? '',
                    'num'       => $contactView['address_num'] ?? '',
                    'street'    => $contactView['address_street'] ?? '',
                    'complement'=> $contactView['address_complement'] ?? '',
                    'town'      => $contactView['address_town'] ?? '',
                    'postalCode'=> $contactView['address_postal_code'] ?? '',
                    'country'   => $contactView['address_country'] ?? '',
                    'otherData' => $contactView['contact_other_data'] ?? '',
                    'website'   => $contactView['website'] ?? '',
                    'occupancy' => $contactView['occupancy'] ?? '',
                    'department' => $contactView['departement'] ?? ''
                ];

                $filling = ContactController::getFillingRate(['contact' => $contact]);

                $contact['filling'] = $filling['color'];

                $contacts[] = $contact;
            } elseif (!empty($rawContact['login'] || !empty($rawContact['user_id']))) {
                if (!empty($rawContact['login'])) {
                    $user = UserModel::getByLowerLogin(['login' => $rawContact['login']]);
                } else {
                    $user = UserModel::getById(['id' => $rawContact['user_id']]);
                }

                $phone = '';
                if (!empty($phone) && ($user['id'] == $GLOBALS['id']
                        || ServiceModel::hasService(['id' => 'view_personal_data', 'userId' => $GLOBALS['userId']]))) {
                    $phone = $user['phone'];
                }

                $primaryEntity = UserModel::getPrimaryEntityById(['id' => $user['id']]);

                $userEntities = UserModel::getNonPrimaryEntitiesById(['id' => $user['id']]);
                $userEntities = array_column($userEntities, 'entity_label');

                $nonPrimaryEntities = implode(', ', $userEntities);

                $contact = [
                    'mode'      => 'internal',
                    'firstname' => $user['firstname'],
                    'lastname'  => $user['lastname'],
                    'email'     => $user['mail'],
                    'phone'     => $phone,
                    'society'   => '',
                    'function'  => '',
                    'num'       => '',
                    'street'    => '',
                    'complement'=> '',
                    'town'      => '',
                    'postalCode'=> '',
                    'country'   => '',
                    'otherData' => '',
                    'website'   => '',
                    'occupancy' => $nonPrimaryEntities,
                    'department' => $primaryEntity['entity_label']
                ];

                $filling = ContactController::getFillingRate(['contact' => $contact]);

                $contact['filling'] = $filling['color'];

                $contacts[] = $contact;
            } elseif (!empty($rawContact['entity_id'])) {
                $entity = EntityModel::getById(['id' => $rawContact['entity_id']]);

                $contact = [
                    'mode'      => 'entity',
                    'firstname' => '',
                    'lastname'  => $entity['entity_label'],
                    'email'     => $entity['email'],
                    'phone'     => '',
                    'society'   => '',
                    'function'  => '',
                    'num'       => '',
                    'street'    => '',
                    'complement'=> '',
                    'town'      => '',
                    'postalCode'=> '',
                    'country'   => '',
                    'otherData' => '',
                    'website'   => '',
                    'occupancy' => '',
                    'department' => ''
                ];

                $filling = ContactController::getFillingRate(['contact' => $contact]);

                $contact['filling'] = $filling['color'];

                $contacts[] = $contact;
            }
        }

        return $contacts;
    }
}
