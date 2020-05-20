<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

/**
 * @brief Contact Controller
 * @author dev@maarch.org
 */

namespace Contact\controllers;

use AcknowledgementReceipt\models\AcknowledgementReceiptModel;
use Attachment\models\AttachmentModel;
use Contact\models\ContactCustomFieldListModel;
use Contact\models\ContactFillingModel;
use Contact\models\ContactGroupModel;
use Contact\models\ContactModel;
use Contact\models\ContactParameterModel;
use Entity\models\EntityModel;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use MessageExchange\controllers\AnnuaryController;
use Parameter\models\ParameterModel;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\AutoCompleteController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\TextFormatModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;

class ContactController
{
    const MAPPING_FIELDS = [
        'civility'              => 'civility',
        'firstname'             => 'firstname',
        'lastname'              => 'lastname',
        'company'               => 'company',
        'department'            => 'department',
        'function'              => 'function',
        'addressNumber'         => 'address_number',
        'addressStreet'         => 'address_street',
        'addressAdditional1'    => 'address_additional1',
        'addressAdditional2'    => 'address_additional2',
        'addressPostcode'       => 'address_postcode',
        'addressTown'           => 'address_town',
        'addressCountry'        => 'address_country',
        'email'                 => 'email',
        'phone'                 => 'phone',
        'notes'                 => 'notes'
    ];

    public function get(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $queryParams = $request->getQueryParams();

        $queryParams['offset'] = (empty($queryParams['offset']) || !is_numeric($queryParams['offset']) ? 0 : (int)$queryParams['offset']);
        $queryParams['limit'] = (empty($queryParams['limit']) || !is_numeric($queryParams['limit']) ? 25 : (int)$queryParams['limit']);
        $order = !in_array($queryParams['order'], ['asc', 'desc']) ? '' : $queryParams['order'];
        $orderBy = !in_array($queryParams['orderBy'], ['firstname', 'lastname', 'company']) ? ['id'] : ["{$queryParams['orderBy']} {$order}", 'id'];

        if (!empty($queryParams['search'])) {
            $fields = ['firstname', 'lastname', 'company', 'address_number', 'address_street', 'address_additional1', 'address_additional2', 'address_postcode', 'address_town', 'address_country'];
            $fieldsNumber = count($fields);
            $fields = AutoCompleteController::getUnsensitiveFieldsForRequest(['fields' => $fields]);

            $requestData = AutoCompleteController::getDataForRequest([
                'search'        => $queryParams['search'],
                'fields'        => $fields,
                'where'         => [],
                'data'          => [],
                'fieldsNumber'  => $fieldsNumber
            ]);
        }

        $contacts = ContactModel::get([
            'select'    => [
                'id', 'firstname', 'lastname', 'company', 'address_number as "addressNumber"', 'address_street as "addressStreet"',
                'address_additional1 as "addressAdditional1"', 'address_additional2 as "addressAdditional2"', 'address_postcode as "addressPostcode"',
                'address_town as "addressTown"', 'address_country as "addressCountry"', 'enabled', 'count(1) OVER()'
            ],
            'where'     => $requestData['where'] ?? null,
            'data'      => $requestData['data'] ?? null,
            'orderBy'   => $orderBy,
            'offset'    => $queryParams['offset'],
            'limit'     => $queryParams['limit']
        ]);
        $count = $contacts[0]['count'] ?? 0;
        if (empty($contacts)) {
            return $response->withJson(['contacts' => $contacts, 'count' => $count]);
        }

        $contactIds = array_column($contacts, 'id');
        $contactsUsed = ContactController::isContactUsed(['ids' => $contactIds]);

        foreach ($contacts as $key => $contact) {
            unset($contacts[$key]['count']);
            $filling = ContactController::getFillingRate(['contactId' => $contact['id']]);

            $contacts[$key]['isUsed'] = $contactsUsed[$contact['id']];

            $contacts[$key]['filling'] = $filling;
        }
        if ($queryParams['orderBy'] == 'filling') {
            usort($contacts, function ($a, $b) {
                return $a['filling']['rate'] <=> $b['filling']['rate'];
            });
            if ($queryParams['order'] == 'desc') {
                $contacts = array_reverse($contacts);
            }
        }

        return $response->withJson(['contacts' => $contacts, 'count' => $count]);
    }

    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'create_contacts', 'userId' => $GLOBALS['id']])
            && !PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        $control = ContactController::controlContact(['body' => $body]);
        if (!empty($control['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
        }

        $currentUser = UserModel::getById(['id' => $GLOBALS['id'], 'select' => ['loginmode']]);
        if (!empty($body['email']) && $currentUser['loginmode'] == 'restMode') {
            $contact = ContactModel::get(['select' => ['id'], 'where' => ['email = ?'], 'data' => [$body['email']]]);
            if (!empty($contact[0]['id'])) {
                return $response->withJson(['id' => $contact[0]['id']]);
            }
        }

        if (!empty($body['communicationMeans'])) {
            if (filter_var($body['communicationMeans'], FILTER_VALIDATE_EMAIL)) {
                $body['communicationMeans'] = ['email' => $body['communicationMeans']];
            } elseif (filter_var($body['communicationMeans'], FILTER_VALIDATE_URL)) {
                $body['communicationMeans'] = ['url' => $body['communicationMeans']];
            } else {
                return $response->withStatus(400)->withJson(['errors' => _COMMUNICATION_MEANS_VALIDATOR]);
            }
        }

        $annuaryReturn = ContactController::addContactToM2MAnnuary(['body' => $body]);
        $body = $annuaryReturn['body'];

        if (!empty($body['externalId']) && is_array($body['externalId'])) {
            $externalId = json_encode($body['externalId']);
        } else {
            $externalId = '{}';
        }

        if (!empty($body['customFields'])) {
            foreach ($body['customFields'] as $key => $value) {
                $customField = ContactCustomFieldListModel::getById(['id' => $key, 'select' => ['type']]);
                if ($customField['type'] == 'date') {
                    $date = new \DateTime($value);
                    $value = $date->format('Y-m-d');
                    $body['customFields'][$key] = $value;
                }
            }
        }

        $id = ContactModel::create([
            'civility'              => $body['civility'] ?? null,
            'firstname'             => $body['firstname'] ?? null,
            'lastname'              => $body['lastname'] ?? null,
            'company'               => $body['company'] ?? null,
            'department'            => $body['department'] ?? null,
            'function'              => $body['function'] ?? null,
            'address_number'        => $body['addressNumber'] ?? null,
            'address_street'        => $body['addressStreet'] ?? null,
            'address_additional1'   => $body['addressAdditional1'] ?? null,
            'address_additional2'   => $body['addressAdditional2'] ?? null,
            'address_postcode'      => $body['addressPostcode'] ?? null,
            'address_town'          => $body['addressTown'] ?? null,
            'address_country'       => $body['addressCountry'] ?? null,
            'email'                 => $body['email'] ?? null,
            'phone'                 => $body['phone'] ?? null,
            'communication_means'   => !empty($body['communicationMeans']) ? json_encode($body['communicationMeans']) : null,
            'notes'                 => $body['notes'] ?? null,
            'creator'               => $GLOBALS['id'],
            'enabled'               => 'true',
            'custom_fields'         => !empty($body['customFields']) ? json_encode($body['customFields']) : null,
            'external_id'           => $externalId
        ]);

        $historyInfoContact = '';
        if (!empty($body['firstname']) || !empty($body['lastname'])) {
            $historyInfoContact .= $body['firstname'] . ' ' . $body['lastname'];
        }
        if (!empty($historyInfoContact) && !empty($body['company'])) {
            $historyInfoContact .= ' (' . $body['company'] . ')';
        } else {
            $historyInfoContact .= $body['company'];
        }

        HistoryController::add([
            'tableName' => 'contacts',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'info'      => _CONTACT_CREATION . " : " . trim($historyInfoContact),
            'moduleId'  => 'contact',
            'eventId'   => 'contactCreation',
        ]);

        return $response->withJson(['id' => $id, 'warning' => $annuaryReturn['warning']]);
    }

    public function getById(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not an integer']);
        }

        $rawContact = ContactModel::getById(['id' => $args['id'], 'select' => ['*']]);
        if (empty($rawContact)) {
            return $response->withStatus(400)->withJson(['errors' => 'Contact does not exist']);
        }

        $contact = [
            'id'                    => $rawContact['id'],
            'civility'              => null,
            'firstname'             => $rawContact['firstname'],
            'lastname'              => $rawContact['lastname'],
            'company'               => $rawContact['company'],
            'department'            => $rawContact['department'],
            'function'              => $rawContact['function'],
            'addressNumber'         => $rawContact['address_number'],
            'addressStreet'         => $rawContact['address_street'],
            'addressAdditional1'    => $rawContact['address_additional1'],
            'addressAdditional2'    => $rawContact['address_additional2'],
            'addressPostcode'       => $rawContact['address_postcode'],
            'addressTown'           => $rawContact['address_town'],
            'addressCountry'        => $rawContact['address_country'],
            'email'                 => $rawContact['email'],
            'phone'                 => $rawContact['phone'],
            'communicationMeans'    => null,
            'notes'                 => $rawContact['notes'],
            'creator'               => $rawContact['creator'],
            'creatorLabel'          => UserModel::getLabelledUserById(['id' => $rawContact['creator']]),
            'enabled'               => $rawContact['enabled'],
            'creationDate'          => $rawContact['creation_date'],
            'modificationDate'      => $rawContact['modification_date'],
            'customFields'          => !empty($rawContact['custom_fields']) ? json_decode($rawContact['custom_fields'], true) : null,
            'externalId'            => json_decode($rawContact['external_id'], true)
        ];

        if (!empty($rawContact['civility'])) {
            $civilities = ContactModel::getCivilities();
            $contact['civility'] = [
                'id'           => $rawContact['civility'],
                'label'        => $civilities[$rawContact['civility']]['label'],
                'abbreviation' => $civilities[$rawContact['civility']]['abbreviation']
            ];
        }
        if (!empty($rawContact['communication_means'])) {
            $communicationMeans = json_decode($rawContact['communication_means'], true);
            $contact['communicationMeans'] = $communicationMeans['url'] ?? $communicationMeans['email'];
        }

        $filling = ContactController::getFillingRate(['contactId' => $rawContact['id']]);
        $contact['fillingRate'] = empty($filling) ? null : $filling;

        return $response->withJson($contact);
    }

    public function update(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'update_contacts', 'userId' => $GLOBALS['id']])
            && !PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not an integer']);
        }

        $body = $request->getParsedBody();

        $control = ContactController::controlContact(['body' => $body]);
        if (!empty($control['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
        }

        $contact = ContactModel::getById(['id' => $args['id'], 'select' => [1]]);
        if (empty($contact)) {
            return $response->withStatus(400)->withJson(['errors' => 'Contact does not exist']);
        }

        if (!empty($body['communicationMeans'])) {
            if (filter_var($body['communicationMeans'], FILTER_VALIDATE_EMAIL)) {
                $body['communicationMeans'] = ['email' => $body['communicationMeans']];
            } elseif (filter_var($body['communicationMeans'], FILTER_VALIDATE_URL)) {
                $body['communicationMeans'] = ['url' => $body['communicationMeans']];
            }
        }

        $annuaryReturn = ContactController::addContactToM2MAnnuary(['body' => $body]);
        $body = $annuaryReturn['body'];

        if (!empty($body['externalId']) && is_array($body['externalId'])) {
            $externalId = json_encode($body['externalId']);
        } else {
            $externalId = '{}';
        }

        ContactModel::update([
            'set'   => [
                    'civility'              => $body['civility'] ?? null,
                    'firstname'             => $body['firstname'] ?? null,
                    'lastname'              => $body['lastname'] ?? null,
                    'company'               => $body['company'] ?? null,
                    'department'            => $body['department'] ?? null,
                    'function'              => $body['function'] ?? null,
                    'address_number'        => $body['addressNumber'] ?? null,
                    'address_street'        => $body['addressStreet'] ?? null,
                    'address_additional1'   => $body['addressAdditional1'] ?? null,
                    'address_additional2'   => $body['addressAdditional2'] ?? null,
                    'address_postcode'      => $body['addressPostcode'] ?? null,
                    'address_town'          => $body['addressTown'] ?? null,
                    'address_country'       => $body['addressCountry'] ?? null,
                    'email'                 => $body['email'] ?? null,
                    'phone'                 => $body['phone'] ?? null,
                    'communication_means'   => !empty($body['communicationMeans']) ? json_encode($body['communicationMeans']) : null,
                    'notes'                 => $body['notes'] ?? null,
                    'modification_date'     => 'CURRENT_TIMESTAMP',
                    'custom_fields'         => !empty($body['customFields']) ? json_encode($body['customFields']) : null,
                    'external_id'           => $externalId
                ],
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        $historyInfoContact = '';
        if (!empty($body['firstname']) || !empty($body['lastname'])) {
            $historyInfoContact .= $body['firstname'] . ' ' . $body['lastname'];
        }
        if (!empty($historyInfoContact) && !empty($body['company'])) {
            $historyInfoContact .= ' (' . $body['company'] . ')';
        } else {
            $historyInfoContact .= $body['company'];
        }

        HistoryController::add([
            'tableName' => 'contacts',
            'recordId'  => $args['id'],
            'eventType' => 'UP',
            'info'      => _CONTACT_MODIFICATION . " : " . trim($historyInfoContact),
            'moduleId'  => 'contact',
            'eventId'   => 'contactModification',
        ]);

        if (!empty($annuaryReturn['warning'])) {
            return $response->withJson(['warning' => $annuaryReturn['warning']]);
        }

        return $response->withStatus(204);
    }

    public function addContactToM2MAnnuary($args = [])
    {
        $warning = '';
        $body = $args['body'];
        if (!empty($body['externalId']['m2m']) && !empty($body['company']) && empty($body['externalId']['m2m_annuary_id'])) {
            if (empty($body['company']) || (empty($body['communicationMeans']['email']) && empty($body['communicationMeans']['url'])) || empty($body['department'])) {
                $control = AnnuaryController::getAnnuaries();
                if (!empty($control['annuaries'])) {
                    $warning = _CANNOT_SYNCHRONIZE_M2M_ANNUARY;
                }
            } else {
                $annuaryInfo = AnnuaryController::addContact([
                    'ouName'             => $body['company'],
                    'communicationValue' => $body['communicationMeans']['email'] ?? $body['communicationMeans']['url'],
                    'serviceName'        => $body['department'],
                    'm2mId'              => $body['externalId']['m2m']
                ]);
                if (!empty($annuaryInfo['errors'])) {
                    $warning = $annuaryInfo['errors'];
                } else {
                    $body['externalId']['m2m_annuary_id'] = $annuaryInfo['entryUUID'];
                }
            }
        }

        return ['body' => $body, 'warning' => $warning];
    }

    public function updateActivation(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not an integer']);
        }

        $contact = ContactModel::getById(['id' => $args['id'], 'select' => [1]]);
        if (empty($contact)) {
            return $response->withStatus(400)->withJson(['errors' => 'Contact does not exist']);
        }

        $body = $request->getParsedBody();

        ContactModel::update([
            'set'   => ['enabled' => empty($body['enabled']) ? 'false' : 'true'],
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        return $response->withStatus(204);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route id is not an integer']);
        }

        $contact = ContactModel::getById(['id' => $args['id'], 'select' => ['lastname', 'firstname', 'company']]);
        if (empty($contact)) {
            return $response->withStatus(400)->withJson(['errors' => 'Contact does not exist']);
        }

        $queryParams = $request->getQueryParams();

        if (!empty($queryParams['redirect'])) {
            if (!Validator::intVal()->validate($queryParams['redirect'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Query param redirect is not an integer']);
            } elseif ($queryParams['redirect'] == $args['id']) {
                return $response->withStatus(400)->withJson(['errors' => 'Cannot redirect to contact you are deleting']);
            }

            $contactRedirect = ContactModel::getById(['id' => $queryParams['redirect'], 'select' => [1]]);
            if (empty($contactRedirect)) {
                return $response->withStatus(400)->withJson(['errors' => 'Contact does not exist']);
            }

            $resourcesContacts = ResourceContactModel::get([
                'select' => ['res_id', 'mode'],
                'where'  => ['item_id = ?', "type = 'contact'"],
                'data'   => [$args['id']]
            ]);

            ResourceContactModel::update([
                'set'   => ['item_id' => $queryParams['redirect']],
                'where' => ['item_id = ?', 'type = ?'],
                'data'  => [$args['id'], 'contact']
            ]);

            // Delete duplicates if needed
            $toDelete = [];
            foreach ($resourcesContacts as $resourcesContact) {
                $resContact = ResourceContactModel::get([
                    'select'  => ['id'],
                    'where'   => ['res_id = ?', 'item_id = ?', 'mode = ?', "type = 'contact'"],
                    'data'    => [$resourcesContact['res_id'], $queryParams['redirect'], $resourcesContact['mode']],
                    'orderBy' => ['id desc']
                ]);
                if (count($resContact) > 1) {
                    $toDelete[] = $resContact[0]['id'];
                }
            }
            if (!empty($toDelete)) {
                ResourceContactModel::delete([
                    'where' => ['id in (?)'],
                    'data' => [$toDelete]
                ]);
            }

            AcknowledgementReceiptModel::update([
                'set'   => ['contact_id' => $queryParams['redirect']],
                'where' => ['contact_id = ?'],
                'data'  => [$args['id']]
            ]);

            AttachmentModel::update([
                'set'   => ['recipient_id' => $queryParams['redirect']],
                'where' => ['recipient_id = ?', "recipient_type = 'contact'"],
                'data'  => [$args['id']]
            ]);
        }

        AttachmentModel::update([
            'set'   => ['recipient_id' => null, 'recipient_type' => null],
            'where' => ['recipient_id = ?', "recipient_type = 'contact'"],
            'data'  => [$args['id']]
        ]);

        ResourceContactModel::delete([
            'where' => ['item_id = ?', "type = 'contact'"],
            'data'  => [$args['id']]
        ]);

        ContactModel::delete([
            'where' => ['id = ?'],
            'data'  => [$args['id']]
        ]);

        ContactGroupModel::deleteByContactId(['contactId' => $args['id']]);

        $historyInfoContact = '';
        if (!empty($contact['firstname']) || !empty($contact['lastname'])) {
            $historyInfoContact .= $contact['firstname'] . ' ' . $contact['lastname'];
        }
        if (!empty($historyInfoContact) && !empty($contact['company'])) {
            $historyInfoContact .= ' (' . $contact['company'] . ')';
        } else {
            $historyInfoContact .= $contact['company'];
        }

        HistoryController::add([
            'tableName' => 'contacts',
            'recordId'  => $args['id'],
            'eventType' => 'DEL',
            'info'      => _CONTACT_SUPPRESSION . " : " . trim($historyInfoContact),
            'moduleId'  => 'contact',
            'eventId'   => 'contactSuppression',
        ]);

        return $response->withStatus(204);
    }

    public function getContactsParameters(Request $request, Response $response)
    {
        $contactsFilling = ContactFillingModel::get();
        $contactParameters = ContactParameterModel::get([
            'select' => ['*'],
            'orderBy' => ['identifier=\'civility\' desc, identifier=\'firstname\' desc, identifier=\'lastname\' desc, identifier=\'company\' desc, identifier=\'department\' desc, 
            identifier=\'function\' desc, identifier=\'address_number\' desc, identifier=\'address_street\' desc, identifier=\'address_additional1\' desc, identifier=\'address_additional2\' desc, 
            identifier=\'address_postcode\' desc, identifier=\'address_town\' desc, identifier=\'address_country\' desc, identifier=\'email\' desc, identifier=\'phone\' desc']
        ]);
        foreach ($contactParameters as $key => $parameter) {
            if (strpos($parameter['identifier'], 'contactCustomField_') !== false) {
                $contactCustomId = str_replace("contactCustomField_", "", $parameter['identifier']);
                $customField = ContactCustomFieldListModel::getById(['select' => ['label'], 'id' => $contactCustomId]);
                $contactParameters[$key]['label'] = $customField['label'];
            } else {
                $contactParameters[$key]['label'] = null;
            }
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/m2m_config.xml']);

        $annuaryEnabled = true;
        if (!$loadedXml) {
            $annuaryEnabled = false;
        }
        if (empty($loadedXml->annuaries) || $loadedXml->annuaries->enabled == 'false') {
            $annuaryEnabled = false;
        }

        return $response->withJson(['contactsFilling' => $contactsFilling, 'contactsParameters' => $contactParameters, 'annuaryEnabled' => $annuaryEnabled]);
    }

    public function updateContactsParameters(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_contacts', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();
        $check = Validator::arrayType()->validate($data['contactsParameters']);
        $check = $check && Validator::arrayType()->validate($data['contactsFilling']);
        $check = $check && Validator::boolType()->validate($data['contactsFilling']['enable']);
        $check = $check && Validator::intVal()->notEmpty()->validate($data['contactsFilling']['first_threshold']) && $data['contactsFilling']['first_threshold'] > 0 && $data['contactsFilling']['first_threshold'] < 99;
        $check = $check && Validator::intVal()->notEmpty()->validate($data['contactsFilling']['second_threshold']) && $data['contactsFilling']['second_threshold'] > 1 && $data['contactsFilling']['second_threshold'] < 100;
        $check = $check && $data['contactsFilling']['first_threshold'] < $data['contactsFilling']['second_threshold'];
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        foreach ($data['contactsParameters'] as $contactParameter) {
            unset($contactParameter['label']);
            ContactParameterModel::update([
                'set'   => [
                    'mandatory'   => empty($contactParameter['mandatory']) ? 'false' : 'true',
                    'filling'     => empty($contactParameter['filling']) ? 'false' : 'true',
                    'searchable'  => empty($contactParameter['searchable']) ? 'false' : 'true',
                    'displayable' => empty($contactParameter['displayable']) ? 'false' : 'true',
                ],
                'where' => ['id = ?'],
                'data'  => [$contactParameter['id']]
            ]);
        }
        
        ContactFillingModel::update($data['contactsFilling']);

        return $response->withJson(['success' => 'success']);
    }

    public function getByResId(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $resource = ResModel::getById(['select' => ['res_id'], 'resId' => $args['resId']]);

        if (empty($resource)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document does not exist']);
        }

        $queryParams = $request->getQueryParams();

        $contacts = [];
        if ($queryParams['type'] == 'senders') {
            $contacts = ContactController::getParsedContacts(['resId' => $resource['res_id'], 'mode' => 'sender']);
        } elseif ($queryParams['type'] == 'recipients') {
            $contacts = ContactController::getParsedContacts(['resId' => $resource['res_id'], 'mode' => 'recipient']);
        }

        return $response->withJson(['contacts' => $contacts]);
    }

    public static function getLightFormattedContact(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->notEmpty()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Query params id is not an integer']);
        }

        if ($args['type'] == 'contact') {
            $contact = ContactModel::getById([
                'select'    => [
                    'firstname', 'lastname', 'company', 'address_number as "addressNumber"', 'address_street as "addressStreet"',
                    'address_postcode as "addressPostcode"', 'address_town as "addressTown"', 'address_country as "addressCountry"'],
                'id'        => $args['id']
            ]);
        } elseif ($args['type'] == 'user') {
            $contact = UserModel::getById(['id' => $args['id'], 'select' => ['firstname', 'lastname']]);
        } elseif ($args['type'] == 'entity') {
            $contact = EntityModel::getById(['id' => $args['id'], 'select' => ['entity_label as label']]);
        }

        if (empty($contact)) {
            return $response->withStatus(400)->withJson(['errors' => 'Contact does not exist']);
        }

        return $response->withJson(['contact' => $contact]);
    }

    public function getCivilities(Request $request, Response $response)
    {
        $civilities = ContactModel::getCivilities();

        return $response->withJson(['civilities' => $civilities]);
    }

    public static function getFormattedContactsForSearchV1(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $return = '';

        if (!isset($data['resId']) && !isset($data['mode'])) {
            $status = 1;
            $return .= '<td colspan="6" style="background-color: red;">';
            $return .= '<p style="padding: 10px; color: black;">';
            $return .= 'Erreur lors du chargement des contacts';
            $return .= '</p>';
            $return .= '</td>';

            return $response->withJson(['status' => $status, 'toShow' => $return]);
        }

        $status = 0;
        $return .= '<td>';
        $return .= '<div align="center">';
        $return .= '<table width="100%">';

        $resourceContacts = ResourceContactModel::get([
            'where' => ['res_id = ?', 'mode = ?'],
            'data'  => [$data['resId'], $data['mode']]
        ]);

        $mode = '';
        if ($data['mode'] == 'sender') {
            $mode = _SENDER;
        } elseif ($data['mode'] == 'recipient') {
            $mode = _RECIPIENT;
        }

        foreach ($resourceContacts as $resourceContact) {
            $return .= '<tr>';
            $return .= '<td style="background: transparent; border: 0px dashed rgb(200, 200, 200);">';

            $return .= '<div style="text-align: left; background-color: rgb(230, 230, 230); padding: 3px; margin-left: 20px; margin-top: -6px;">';

            if ($resourceContact['type'] == 'contact') {
                $contactRaw = ContactModel::getById([
                    'select' => ['*'],
                    'id'     => $resourceContact['item_id']
                ]);

                $contactToDisplay = ContactController::getFormattedContactWithAddress(['contact' => $contactRaw]);

                $return .= '<span style="font-size:10px;color:#135F7F;">' . $mode . '</span> - ';
                $return .= $contactToDisplay['contact']['otherInfo'];
            } elseif ($resourceContact['type'] == 'user') {
                $return .= '<span style="font-size:10px;color:#135F7F;">' . $mode . ' (interne)</span> - ';
                $return .= UserModel::getLabelledUserById(['id' => $resourceContact['item_id']]);
            } elseif ($resourceContact['type'] == 'entity') {
                $return .= '<span style="font-size:10px;color:#135F7F;">' . $mode . ' (interne)</span> - ';
                $entity = EntityModel::getById(['id' => $resourceContact['item_id'], 'select' => ['entity_label']]);
                $return .= $entity['entity_label'];
            }

            $return .= '</div>';

            $return .= '</td>';
            $return .= '</tr>';
        }

        $return .= '</table>';
        $return .= '<br />';
        $return .= '</div>';
        $return .= '</td>';

        return $response->withJson(['status' => $status, 'toShow' => $return]);
    }

    public static function getFillingRate(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['contactId']);
        ValidatorModel::intVal($aArgs, ['contactId']);

        $contactsFilling = ContactFillingModel::get();
        $contactsParameters = ContactParameterModel::get(['select' => ['identifier'], 'where' => ['filling = ?'], 'data' => ['true']]);

        if ($contactsFilling['enable'] && !empty($contactsParameters)) {
            $contactRaw = ContactModel::getById([
                'select'    => [
                    'civility', 'firstname', 'lastname', 'company', 'department', 'function', 'address_number as "addressNumber"', 'address_street as "addressStreet"',
                    'address_additional1 as "addressAdditional1"', 'address_additional2 as "addressAdditional2"', 'address_postcode as "addressPostcode"',
                    'address_town as "addressTown"', 'address_country as "addressCountry"', 'email', 'phone', 'custom_fields'
                ],
                'id'        => $aArgs['contactId']
            ]);
            $customFields = json_decode($contactRaw['custom_fields'], true);

            $percent = 0;
            foreach ($contactsParameters as $ratingColumn) {
                if (strpos($ratingColumn['identifier'], 'contactCustomField_') !== false && !empty($customFields[str_replace("contactCustomField_", "", $ratingColumn['identifier'])])) {
                    $percent++;
                } elseif (!empty($contactRaw[$ratingColumn['identifier']])) {
                    $percent++;
                }
            }
            $percent = $percent * 100 / count($contactsParameters);
            if ($percent <= $contactsFilling['first_threshold']) {
                $thresholdLevel = 'first';
            } elseif ($percent <= $contactsFilling['second_threshold']) {
                $thresholdLevel = 'second';
            } else {
                $thresholdLevel = 'third';
            }

            return ['rate' => round($percent, 2), 'thresholdLevel' => $thresholdLevel];
        }

        return [];
    }

    public static function getContactAfnor(array $args)
    {
        $afnorAddress = ['Afnor',
            '',
            '',
            '',
            '',
            '',
            ''
        ];

        if (!empty($args['company'])) {
            // Ligne 1
            $afnorAddress[1] = trim(substr($args['company'], 0, 38));
        }

        // Ligne 2
        if (!empty($args['civility']) || !empty($args['firstname']) || !empty($args['lastname'])) {
            $afnorAddress[2] = ContactController::controlLengthNameAfnor([
                'civility'      => $args['civility'],
                'fullName'      => $args['firstname'].' '.$args['lastname'],
                'strMaxLength'  => 38
            ]);
            $afnorAddress[2] = trim($afnorAddress[2]);
        }

        // Ligne 3
        if (!empty($args['address_additional1'])) {
            $afnorAddress[3] = trim(substr($args['address_additional1'], 0, 38));
        }

        // Ligne 4
        if (!empty($args['address_number'])) {
            $args['address_number'] = TextFormatModel::normalize(['string' => $args['address_number']]);
            $args['address_number'] = preg_replace('/[^\w]/s', ' ', $args['address_number']);
            $args['address_number'] = strtoupper($args['address_number']);
        }
        if (!empty($args['address_street'])) {
            $args['address_street'] = TextFormatModel::normalize(['string' => $args['address_street']]);
            $args['address_street'] = preg_replace('/[^\w]/s', ' ', $args['address_street']);
            $args['address_street'] = strtoupper($args['address_street']);
        }
        $afnorAddress[4] = trim(substr($args['address_number'].' '.$args['address_street'], 0, 38));

        // Ligne 5
        if (!empty($args['address_additional2'])) {
            $afnorAddress[5] = trim(substr($args['address_additional2'], 0, 38));
        }

        // Ligne 6
        $args['address_postcode'] = strtoupper($args['address_postcode']);
        $args['address_town'] = strtoupper($args['address_town']);
        $afnorAddress[6] = trim(substr($args['address_postcode'].' '.$args['address_town'], 0, 38));

        return $afnorAddress;
    }

    public static function controlLengthNameAfnor(array $args)
    {
        $aCivility = ContactModel::getCivilities();
        if (strlen($args['civility'].' '.$args['fullName']) > $args['strMaxLength']) {
            $args['civility'] = $aCivility[$args['civility']]['abbreviation'];
        } else {
            $args['civility'] = $aCivility[$args['civility']]['label'];
        }

        return substr($args['civility'].' '.$args['fullName'], 0, $args['strMaxLength']);
    }

    public function getAvailableDepartments(Request $request, Response $response)
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

        if (empty($departments)) {
            return $response->withJson(['departments' => []]);
        }

        sort($departments, SORT_NUMERIC);

        $defaultDepartment = ParameterModel::getById(['id' => 'defaultDepartment', 'select' => ['param_value_int']]);

        return $response->withJson(['departments' => $departments, 'default' => empty($defaultDepartment['param_value_int']) ? null : $defaultDepartment['param_value_int']]);
    }

    public static function getParsedContacts(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'mode']);
        ValidatorModel::intVal($args, ['resId']);
        ValidatorModel::stringType($args, ['mode']);

        $contacts = [];

        $resourceContacts = ResourceContactModel::get([
            'where'     => ['res_id = ?', 'mode = ?'],
            'data'      => [$args['resId'], $args['mode']]
        ]);

        foreach ($resourceContacts as $resourceContact) {
            $contact = [];
            if ($resourceContact['type'] == 'contact') {
                $contactRaw = ContactModel::getById([
                    'select'    => ['*'],
                    'id'        => $resourceContact['item_id']
                ]);

                $civilities = ContactModel::getCivilities();
                $xmlCivility = $civilities[$contactRaw['civility']];
                $civility = [
                    'id'           => $contactRaw['civility'],
                    'label'        => $xmlCivility['label'],
                    'abbreviation' => $xmlCivility['abbreviation']
                ];

                $contact = [
                    'type'               => 'contact',
                    'civility'           => $civility,
                    'firstname'          => $contactRaw['firstname'],
                    'lastname'           => $contactRaw['lastname'],
                    'company'            => $contactRaw['company'],
                    'department'         => $contactRaw['department'],
                    'function'           => $contactRaw['function'],
                    'addressNumber'      => $contactRaw['address_number'],
                    'addressStreet'      => $contactRaw['address_street'],
                    'addressAdditional1' => $contactRaw['address_additional1'],
                    'addressAdditional2' => $contactRaw['address_additional2'],
                    'addressPostcode'    => $contactRaw['address_postcode'],
                    'addressTown'        => $contactRaw['address_town'],
                    'addressCountry'     => $contactRaw['address_country'],
                    'email'              => $contactRaw['email'],
                    'phone'              => $contactRaw['phone'],
                    'communicationMeans' => null,
                    'notes'              => $contactRaw['notes'],
                    'creator'            => $contactRaw['creator'],
                    'creatorLabel'       => UserModel::getLabelledUserById(['id' => $contactRaw['creator']]),
                    'enabled'            => $contactRaw['enabled'],
                    'creationDate'       => $contactRaw['creation_date'],
                    'modificationDate'   => $contactRaw['modification_date'],
                    'customFields'       => !empty($contactRaw['custom_fields']) ? json_decode($contactRaw['custom_fields'], true) : null,
                    'externalId'         => json_decode($contactRaw['external_id'], true)
                ];

                if (!empty($contactRaw['communication_means'])) {
                    $communicationMeans = json_decode($contactRaw['communication_means'], true);
                    $contact['communicationMeans'] = $communicationMeans['url'] ?? $communicationMeans['email'];
                }

                $filling = ContactController::getFillingRate(['contactId' => $resourceContact['item_id']]);

                $contact['fillingRate'] = $filling;
            } elseif ($resourceContact['type'] == 'user') {
                $user = UserModel::getById(['id' => $resourceContact['item_id']]);

                $phone = '';
                if (!empty($phone) && ($user['id'] == $GLOBALS['id']
                        || PrivilegeController::hasPrivilege(['privilegeId' => 'view_personal_data', 'userId' => $GLOBALS['id']]))) {
                    $phone = $user['phone'];
                }

                $primaryEntity = UserModel::getPrimaryEntityById(['select' => ['entity_label'], 'id' => $user['id']]);

                $userEntities = UserModel::getNonPrimaryEntitiesById(['id' => $user['id']]);
                $userEntities = array_column($userEntities, 'entity_label');

                $nonPrimaryEntities = implode(', ', $userEntities);

                $contact = [
                    'type'               => 'user',
                    'firstname'          => $user['firstname'],
                    'lastname'           => $user['lastname'],
                    'company'            => null,
                    'department'         => $primaryEntity['entity_label'],
                    'function'           => null,
                    'addressNumber'      => null,
                    'addressStreet'      => null,
                    'addressAdditional1' => $nonPrimaryEntities,
                    'addressAdditional2' => null,
                    'addressPostcode'    => null,
                    'addressTown'        => null,
                    'addressCountry'     => null,
                    'email'              => $user['mail'],
                    'phone'              => $phone,
                    'communicationMeans' => null,
                    'notes'              => null,
                    'creator'            => null,
                    'creatorLabel'       => null,
                    'enabled'            => null,
                    'creationDate'       => null,
                    'modificationDate'   => null,
                    'customFields'       => null,
                    'externalId'         => null
                ];
            } elseif ($resourceContact['type'] == 'entity') {
                $entity = EntityModel::getById(['id' => $resourceContact['item_id'], 'select' => ['entity_label', 'email']]);

                $contact = [
                    'type'               => 'entity',
                    'firstname'          => null,
                    'lastname'           => $entity['entity_label'],
                    'company'            => null,
                    'department'         => null,
                    'function'           => null,
                    'addressNumber'      => null,
                    'addressStreet'      => null,
                    'addressAdditional1' => null,
                    'addressAdditional2' => null,
                    'addressPostcode'    => null,
                    'addressTown'        => null,
                    'addressCountry'     => null,
                    'email'              => $entity['email'],
                    'phone'              => null,
                    'communicationMeans' => null,
                    'notes'              => null,
                    'creator'            => null,
                    'creatorLabel'       => null,
                    'enabled'            => null,
                    'creationDate'       => null,
                    'modificationDate'   => null,
                    'customFields'       => null,
                    'externalId'         => null
                ];
            }

            $contacts[] = $contact;
        }

        return $contacts;
    }

    public static function getFormattedContacts(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'mode']);
        ValidatorModel::intVal($args, ['resId']);
        ValidatorModel::stringType($args, ['mode']);
        ValidatorModel::boolType($args, ['onlyContact']);

        $contacts = [];

        $resourceContacts = ResourceContactModel::get([
            'where'     => ['res_id = ?', 'mode = ?'],
            'data'      => [$args['resId'], $args['mode']]
        ]);

        foreach ($resourceContacts as $resourceContact) {
            $contact = '';
            if ($resourceContact['type'] == 'contact') {
                $contactRaw = ContactModel::getById([
                    'select'    => ['*'],
                    'id'        => $resourceContact['item_id']
                ]);

                if (isset($args['onlyContact']) && $args['onlyContact']) {
                    $contactToDisplay = ContactController::getFormattedOnlyContact(['contact' => $contactRaw]);
                } else {
                    $contactToDisplay = ContactController::getFormattedContactWithAddress(['contact' => $contactRaw]);
                }

                $contact = $contactToDisplay['contact']['otherInfo'];
            } elseif ($resourceContact['type'] == 'user') {
                $contact = UserModel::getLabelledUserById(['id' => $resourceContact['item_id']]);
            } elseif ($resourceContact['type'] == 'entity') {
                $entity = EntityModel::getById(['id' => $resourceContact['item_id'], 'select' => ['entity_label']]);
                $contact = $entity['entity_label'];
            }

            $contacts[] = $contact;
        }

        return $contacts;
    }

    private static function controlContact(array $args)
    {
        $body = $args['body'];

        if (empty($body)) {
            return ['errors' => 'Body is not set or empty'];
        } elseif (!Validator::stringType()->notEmpty()->validate($body['lastname']) && !Validator::stringType()->notEmpty()->validate($body['company'])) {
            return ['errors' => 'Body lastname or company is mandatory'];
        } elseif (!empty($body['email']) && !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            return ['errors' => 'Body email is not valid'];
        } elseif (!empty($body['phone']) && !preg_match("/\+?((|\ |\.|\(|\)|\-)?(\d)*)*\d$/", $body['phone'])) {
            return ['errors' => 'Body phone is not valid'];
        }
        
        $lengthFields = [
            'civility',
            'firstname',
            'lastname',
            'company',
            'department',
            'function',
            'addressNumber',
            'addressStreet',
            'addressPostcode',
            'addressTown',
            'addressCountry',
            'email',
            'phone'
        ];

        foreach ($lengthFields as $field) {
            if (!empty($body[$field]) && !Validator::stringType()->length(1, 256)->validate($body[$field])) {
                return ['errors' => "Body {$field} length is not valid (1..256)"];
            }
        }

        if (!empty($body['customFields'])) {
            if (!Validator::arrayType()->notEmpty()->validate($body['customFields'])) {
                return ['errors' => 'Body customFields is not an array'];
            }
            $customFields = ContactCustomFieldListModel::get(['select' => ['count(1)'], 'where' => ['id in (?)'], 'data' => [array_keys($body['customFields'])]]);
            if (count($body['customFields']) != $customFields[0]['count']) {
                return ['errors' => 'Body customFields : One or more custom fields do not exist'];
            }
        }

        $mandatoryParameters = ContactParameterModel::get(['select' => ['identifier'], 'where' => ['mandatory = ?', 'identifier not in (?)'], 'data' => [true, ['lastname', 'company']]]);
        foreach ($mandatoryParameters as $mandatoryParameter) {
            if (strpos($mandatoryParameter['identifier'], 'contactCustomField_') !== false) {
                $customId = explode('_', $mandatoryParameter['identifier'])[1];
                if (empty($body['customFields'][$customId])) {
                    return ['errors' => "Body customFields[{$customId}] is mandatory"];
                }
            } else {
                if (empty($body[$mandatoryParameter['identifier']])) {
                    return ['errors' => "Body {$mandatoryParameter['identifier']} is mandatory"];
                }
            }
        }

        if (!empty($body['externalId']['m2m'])) {
            $businessId = explode("/", $body['externalId']['m2m']);
            if (!AnnuaryController::isSiretNumber(['siret' => $businessId[0]])) {
                return ['errors' => _EXTERNALID_M2M_VALIDATOR];
            }
        }

        return true;
    }

    public static function getFormattedOnlyContact(array $args)
    {
        ValidatorModel::notEmpty($args, ['contact']);
        ValidatorModel::arrayType($args, ['contact']);

        $contactName = '';
        if (!empty($args['contact']['firstname'])) {
            $contactName .= $args['contact']['firstname'] . ' ';
        }
        if (!empty($args['contact']['lastname'])) {
            $contactName .= $args['contact']['lastname'] . ' ';
        }

        $company = '';
        if (!empty($args['contact']['company'])) {
            $company = $args['contact']['company'];

            if (!empty($contactName)) {
                $company = '(' . $company . ') ';
            }
        }

        $contactToDisplay = $contactName . $company;

        $contact = [
            'type'          => 'onlyContact',
            'id'            => $args['contact']['id'],
            'idToDisplay'   => $contactToDisplay,
            'otherInfo'     => $contactToDisplay,
            'rateColor'     => ''
        ];

        return ['contact' => $contact];
    }

    public static function getFormattedContactWithAddress(array $args)
    {
        ValidatorModel::notEmpty($args, ['contact']);
        ValidatorModel::arrayType($args, ['contact']);
        ValidatorModel::boolType($args, ['color']);

        if (!empty($args['color'])) {
            $rate = ContactController::getFillingRate(['contactId' => $args['contact']['id']]);
        }
        $thresholdLevel = empty($rate['thresholdLevel']) ? '' : $rate['thresholdLevel'];

        $address = '';

        if (!empty($args['contact']['address_number'])) {
            $address.= $args['contact']['address_number'] . ' ';
        }
        if (!empty($args['contact']['address_street'])) {
            $address.= $args['contact']['address_street'] . ' ';
        }
        if (!empty($args['contact']['address_postcode'])) {
            $address.= $args['contact']['address_postcode'] . ' ';
        }
        if (!empty($args['contact']['address_town'])) {
            $address.= $args['contact']['address_town'] . ' ';
        }
        if (!empty($args['contact']['address_country'])) {
            $address.= $args['contact']['address_country'];
        }

        $contactName = '';
        if (!empty($args['contact']['firstname'])) {
            $contactName .= $args['contact']['firstname'] . ' ';
        }
        if (!empty($args['contact']['lastname'])) {
            $contactName .= $args['contact']['lastname'] . ' ';
        }

        $company = '';
        if (!empty($args['contact']['company'])) {
            $company = $args['contact']['company'];

            if (!empty($contactName)) {
                $company = '(' . $company . ')';
            }
        }

        $contactToDisplay = trim($contactName . $company);

        $otherInfo = empty($address) ? "{$contactToDisplay}" : "{$contactToDisplay} - {$address}";
        $contact = [
            'type'          => 'contact',
            'id'            => $args['contact']['id'],
            'contact'       => $contactToDisplay,
            'address'       => $address,
            'idToDisplay'   => "{$contactToDisplay}<br/>{$address}",
            'otherInfo'     => $otherInfo,
            'thresholdLevel' => $thresholdLevel
        ];

        return ['contact' => $contact];
    }

    public static function getAutocompleteFormat(array $args)
    {
        ValidatorModel::notEmpty($args, ['id']);
        ValidatorModel::intVal($args, ['id']);

        $displayableParameters = ContactParameterModel::get(['select' => ['identifier'], 'where' => ['displayable = ?'], 'data' => [true]]);

        $displayableStdParameters = [];
        $displayableCstParameters = [];
        foreach ($displayableParameters as $displayableParameter) {
            if (strpos($displayableParameter['identifier'], 'contactCustomField_') !== false) {
                $displayableCstParameters[] = explode('_', $displayableParameter['identifier'])[1];
            } else {
                $displayableStdParameters[] = ContactController::MAPPING_FIELDS[$displayableParameter['identifier']];
            }
        }

        if (!empty($displayableCstParameters)) {
            $displayableStdParameters[] = 'custom_fields';
        }

        $rawContact = ContactModel::getById(['id' => $args['id'], 'select' => $displayableStdParameters]);
        $contact = ['type' => 'contact', 'id' => $args['id'], 'lastname' => $rawContact['lastname'], 'company' => $rawContact['company']];

        if (in_array('civility', $displayableStdParameters)) {
            $contact['civility'] = null;

            if (!empty($rawContact['civility'])) {
                $civilities = ContactModel::getCivilities();
                $contact['civility'] = [
                    'id'           => $rawContact['civility'],
                    'label'        => $civilities[$rawContact['civility']]['label'],
                    'abbreviation' => $civilities[$rawContact['civility']]['abbreviation']
                ];
            }
        }
        if (in_array('firstname', $displayableStdParameters)) {
            $contact['firstname'] = $rawContact['firstname'];
        } else {
            $contact['firstname'] = '';
        }
        if (in_array('department', $displayableStdParameters)) {
            $contact['department'] = $rawContact['department'];
        }
        if (in_array('function', $displayableStdParameters)) {
            $contact['function'] = $rawContact['function'];
        }
        if (in_array('address_number', $displayableStdParameters)) {
            $contact['addressNumber'] = $rawContact['address_number'];
        }
        if (in_array('address_street', $displayableStdParameters)) {
            $contact['addressStreet'] = $rawContact['address_street'];
        }
        if (in_array('address_additional1', $displayableStdParameters)) {
            $contact['addressAdditional1'] = $rawContact['address_additional1'];
        }
        if (in_array('address_additional2', $displayableStdParameters)) {
            $contact['addressAdditional2'] = $rawContact['address_additional2'];
        }
        if (in_array('address_postcode', $displayableStdParameters)) {
            $contact['addressPostcode'] = $rawContact['address_postcode'];
        }
        if (in_array('address_town', $displayableStdParameters)) {
            $contact['addressTown'] = $rawContact['address_town'];
        }
        if (in_array('address_country', $displayableStdParameters)) {
            $contact['addressCountry'] = $rawContact['address_country'];
        }
        if (in_array('email', $displayableStdParameters)) {
            $contact['email'] = $rawContact['email'];
        }
        if (in_array('phone', $displayableStdParameters)) {
            $contact['phone'] = $rawContact['phone'];
        }
        if (in_array('notes', $displayableStdParameters)) {
            $contact['notes'] = $rawContact['notes'];
        }

        if (!empty($displayableCstParameters)) {
            $contact['customFields'] = [];
            $customFields = json_decode($rawContact['custom_fields'], true);
            foreach ($displayableCstParameters as $value) {
                $contact['customFields'][$value] = $customFields[$value] ?? null;
            }
        }

        $fillingRate = ContactController::getFillingRate(['contactId' => $args['id']]);
        $contact['fillingRate'] = empty($fillingRate) ? null : $fillingRate;

        return $contact;
    }

    private static function isContactUsed(array $args)
    {
        ValidatorModel::notEmpty($args, ['ids']);
        ValidatorModel::arrayType($args, ['ids']);

        $contactsUsed = array_fill_keys($args['ids'], false);

        $inResources = ResourceContactModel::get([
            'select' => ['item_id'],
            'where'  => ['item_id in (?)', 'type = ?'],
            'data'   => [$args['ids'], 'contact']
        ]);
        $inResources = array_column($inResources, 'item_id');

        $inAcknowledgementReceipts = AcknowledgementReceiptModel::get([
            'select' => ['contact_id'],
            'where'  => ['contact_id in (?)'],
            'data'   => [$args['ids']]
        ]);
        $inAcknowledgementReceipts = array_column($inAcknowledgementReceipts, 'contact_id');

        $inAttachments = AttachmentModel::get([
            'select' => ['recipient_id'],
            'where'  => ['recipient_id in (?)', 'recipient_type = ?'],
            'data'   => [$args['ids'], 'contact']
        ]);
        $inAttachments = array_column($inAttachments, 'recipient_id');

        foreach ($contactsUsed as $id => $item) {
            $contactsUsed[$id] = in_array($id, $inResources) || in_array($id, $inAcknowledgementReceipts) || in_array($id, $inAttachments);
        }

        return $contactsUsed;
    }
}
