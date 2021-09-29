<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   Multigest Controller
 * @author  dev@maarch.org
 */

namespace Multigest\controllers;

use Attachment\models\AttachmentModel;
use Attachment\models\AttachmentTypeModel;
use Configuration\models\ConfigurationModel;
use Contact\controllers\ContactCivilityController;
use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Docserver\models\DocserverModel;
use Doctype\models\DoctypeModel;
use Doctype\models\SecondLevelModel;
use Entity\models\EntityModel;
use Group\controllers\PrivilegeController;
use Priority\models\PriorityModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CurlModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\ValidatorModel;
use SrcCore\models\CoreConfigModel;
use User\models\UserModel;

class MultigestController
{
    public function getConfiguration(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_multigest', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_multigest']);
        if (empty($configuration)) {
            return $response->withJson(['configuration' => null]);
        }

        $configuration = json_decode($configuration['value'], true);

        return $response->withJson(['configuration' => $configuration]);
    }

    public function updateConfiguration(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_multigest', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['uri'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body uri is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['login'])) {
        }

        $value = json_encode([
            'uri' => trim($body['uri'])
        ]);

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_multigest']);
        if (empty($configuration)) {
            ConfigurationModel::create(['privilege' => 'admin_multigest', 'value' => $value]);
        } else {
            ConfigurationModel::update(['set' => ['value' => $value], 'where' => ['privilege = ?'], 'data' => ['admin_multigest']]);
        }

        return $response->withStatus(204);
    }

    public function getAccounts(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_multigest', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entities = EntityModel::get(['select' => ['external_id', 'short_label'], 'where' => ["external_id->>'multigest' is not null"]]);

        $accounts = [];
        $alreadyAdded = [];
        foreach ($entities as $entity) {
            $externalId = json_decode($entity['external_id'], true);
            if (!in_array($externalId['multigest']['id'], $alreadyAdded)) {
                $accounts[] = [
                    'id'            => $externalId['multigest']['id'],
                    'label'         => $externalId['multigest']['label'],
                    'login'         => $externalId['multigest']['login'],
                    'sasId'         => $externalId['multigest']['sasId'],
                    'entitiesLabel' => [$entity['short_label']]
                ];
                $alreadyAdded[] = $externalId['multigest']['id'];
            } else {
                foreach ($accounts as $key => $value) {
                    if ($value['id'] == $externalId['multigest']['id']) {
                        $accounts[$key]['entitiesLabel'][] = $entity['short_label'];
                    }
                }
            }
        }

        return $response->withJson(['accounts' => $accounts]);
    }

    public function getAvailableEntities(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_multigest', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entities = EntityModel::get(['select' => ['id'], 'where' => ["external_id->>'multigest' is null"]]);

        $availableEntities = array_column($entities, 'id');

        return $response->withJson(['availableEntities' => $availableEntities]);
    }

    public function createAccount(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_multigest', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['login'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body login is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['password'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body password is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['sasId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body sasId is empty or not a string']);
        } elseif (!Validator::arrayType()->notEmpty()->validate($body['entities'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body entities is empty or not an array']);
        }

        foreach ($body['entities'] as $entity) {
            if (!Validator::intVal()->notEmpty()->validate($entity)) {
                return $response->withStatus(400)->withJson(['errors' => 'Body entities contains non integer values']);
            }
        }
        $entities = EntityModel::get(['select' => ['id'], 'where' => ['id in (?)'], 'data' => [$body['entities']]]);
        if (count($entities) != count($body['entities'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Some entities do not exist']);
        }

        $id = CoreConfigModel::uniqueId();
        $account = [
            'id'       => $id,
            'label'    => $body['label'],
            'login'    => $body['login'],
            'password' => PasswordModel::encrypt(['password' => $body['password']]),
            'sasId'    => $body['sasId']
        ];
        $account = json_encode($account);

        EntityModel::update([
            'postSet' => ['external_id' => "jsonb_set(coalesce(external_id, '{}'::jsonb), '{multigest}', '{$account}')"],
            'where'   => ['id in (?)'],
            'data'    => [$body['entities']]
        ]);

        return $response->withStatus(204);
    }

    public function getAccountById(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_multigest', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $entities = EntityModel::get(['select' => ['external_id', 'id'], 'where' => ["external_id->'multigest'->>'id' = ?"], 'data' => [$args['id']]]);
        if (empty($entities[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'Account not found']);
        }

        $externalId = json_decode($entities[0]['external_id'], true);
        $account = [
            'id'        => $externalId['multigest']['id'],
            'label'     => $externalId['multigest']['label'],
            'login'     => $externalId['multigest']['login'],
            'sasId'     => $externalId['multigest']['sasId'],
            'entities'  => []
        ];

        foreach ($entities as $entity) {
            $account['entities'][] = $entity['id'];
        }

        return $response->withJson($account);
    }

    public function updateAccount(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_multigest', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['label'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body label is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['login'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body login is empty or not a string']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['sasId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body sasId is empty or not a string']);
        } elseif (!Validator::arrayType()->notEmpty()->validate($body['entities'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body entities is empty or not an array']);
        }

        $accounts = EntityModel::get(['select' => ['external_id', 'id'], 'where' => ["external_id->'multigest'->>'id' = ?"], 'data' => [$args['id']]]);
        if (empty($accounts[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'Account not found']);
        }

        foreach ($body['entities'] as $entity) {
            if (!Validator::intVal()->notEmpty()->validate($entity)) {
                return $response->withStatus(400)->withJson(['errors' => 'Body entities contains non integer values']);
            }
        }
        $entities = EntityModel::get(['select' => ['id'], 'where' => ['id in (?)'], 'data' => [$body['entities']]]);
        if (count($entities) != count($body['entities'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Some entities do not exist']);
        }

        $externalId = json_decode($accounts[0]['external_id'], true);
        $account = [
            'id'        => $args['id'],
            'label'     => $body['label'],
            'login'     => $body['login'],
            'password'  => empty($body['password']) ? $externalId['multigest']['password'] : PasswordModel::encrypt(['password' => $body['password']]),
            'sasId'    => $body['sasId']
        ];
        $account = json_encode($account);

        EntityModel::update([
            'set'   => ['external_id' => "{}"],
            'where' => ['id in (?)', 'external_id = ?'],
            'data'  => [$body['entities'], 'null']
        ]);

        EntityModel::update([
            'postSet'   => ['external_id' => "jsonb_set(coalesce(external_id, '{}'::jsonb), '{multigest}', '{$account}')"],
            'where'     => ['id in (?)'],
            'data'      => [$body['entities']]
        ]);

        $previousEntities = array_column($accounts, 'id');
        $entitiesToRemove = array_diff($previousEntities, $body['entities']);
        if (!empty($entitiesToRemove)) {
            EntityModel::update([
                'postSet'   => ['external_id' => "external_id - 'multigest'"],
                'where'     => ['id in (?)'],
                'data'      => [$entitiesToRemove]
            ]);
        }

        return $response->withStatus(204);
    }

    public function deleteAccount(Request $request, Response $response, array $args)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_multigest', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $accounts = EntityModel::get(['select' => ['external_id', 'id'], 'where' => ["external_id->'multigest'->>'id' = ?"], 'data' => [$args['id']]]);
        if (empty($accounts[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'Account not found']);
        }

        $entitiesToRemove = array_column($accounts, 'id');
        EntityModel::update([
            'postSet'   => ['external_id' => "external_id - 'multigest'"],
            'where'     => ['id in (?)'],
            'data'      => [$entitiesToRemove]
        ]);

        return $response->withStatus(204);
    }

    public function checkAccount(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_multigest', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['login'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body login is empty or not a string']);
        }

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_multigest']);
        if (empty($configuration)) {
            return $response->withStatus(400)->withJson(['errors' => 'Multigest configuration is not enabled']);
        }
        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration['uri'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Multigest configuration URI is empty']);
        }
        $multigestUri = rtrim($configuration['uri'], '/');

        $soapClient = new \SoapClient($multigestUri);
        $result = (int) $soapClient->GedTestExistenceUtilisateur($body['login']);
        if ($result !== 0) {
            return $response->withStatus(400)->withJson(['errors' => 'MultiGest user '.$body['login'].' does not exist']);
        }

        return $response->withStatus(204);
    }

    // TODO / WIP
    public static function sendResource(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'userId']);
        ValidatorModel::intVal($args, ['resId', 'userId']);

        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_multigest']);
        if (empty($configuration)) {
            return ['errors' => 'Multigest configuration is not enabled'];
        }

        $configuration = json_decode($configuration['value'], true);
        if (empty($configuration['uri'])) {
            return ['errors' => 'Multigest configuration URI is empty'];
        }
        $multigestUri = rtrim($configuration['uri'], '/');

        $userPrimaryEntity = UserModel::getPrimaryEntityById([
            'id'     => $args['userId'],
            'select' => ['entities.id', 'entities.external_id']
        ]);

        if (empty($userPrimaryEntity)) {
            return ['errors' => 'User has no primary entity'];
        }
        $entityConfiguration = json_decode($userPrimaryEntity['external_id'], true);
        if (empty($entityConfiguration['multigest'])) {
            return ['errors' => 'Entity has no associated Multigest account'];
        }
        $entityConfiguration = [
            'login' => $entityConfiguration['multigest']['login'] ?? '',
            'sasId' => $entityConfiguration['multigest']['sasId'] ?? ''
        ];

        $document = ResModel::getById([
            'select' => [
                'filename', 'subject', 'alt_identifier', 'external_id', 'type_id', 'priority', 'fingerprint', 'custom_fields', 'dest_user',
                'creation_date', 'modification_date', 'doc_date', 'destination', 'initiator', 'process_limit_date', 'closing_date', 'docserver_id', 'path', 'filename'
            ],
            'resId'  => $args['resId']
        ]);
        if (empty($document)) {
            return ['errors' => 'Document does not exist'];
        } elseif (empty($document['filename'])) {
            return ['errors' => 'Document has no file'];
        } elseif (empty($document['alt_identifier'])) {
            return ['errors' => 'Document has no chrono'];
        }
        $document['subject'] = str_replace([':', '*', '\'', '"', '>', '<'], ' ', $document['subject']);

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => 'Docserver does not exist'];
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!is_file($pathToDocument)) {
            return ['errors' => 'Document not found on docserver'];
        }

        $fileContent = file_get_contents($pathToDocument);
        if ($fileContent === false) {
            return ['errors' => 'Document not found on docserver'];
        }

        $fileExtension = explode('.', $document['filename']);
        $fileExtension = array_pop($fileExtension);

        $multigestParameters = CoreConfigModel::getJsonLoaded(['path' => 'config/multigest.json']);
        if (empty($multigestParameters)) {
            return ['errors' => 'Multigest mapping file does not exist'];
        }
        if (empty($multigestParameters['mapping']['document'])) {
            return ['errors' => 'Multigest mapping for document is empty'];
        }
        $metadataFields = '';
        $metadataValues = '';
        $keyMetadataField = '';
        $keyMetadataValue = '';
        foreach ($multigestParameters['mapping']['document'] as $multigestField => $maarchField) {
            if (empty($multigestField) || empty($maarchField)) {
                continue;
            }
            $nextField = '';
            $nextValue = '';

            if (isset($document[$maarchField])) {
                $nextField = str_replace('|', '-', $multigestField);
                if (strpos($maarchField, '_date') !== false) {
                    $document[$maarchField] = substr($document[$maarchField], 0, 10);
                }
                $nextValue = str_replace('|', '-', $document[$maarchField]);
            }

            if (empty($nextValue)) {
                continue;
            }

            if (empty($keyMetadataField)) {
                $keyMetadataField = $nextField;
            }
            if (empty($keyMetadataValue)) {
                $keyMetadataValue = $nextValue;
            }
            if (!empty($metadataFields)) {
                $metadataFields .= '|';
            }
            $metadataFields .= $nextField;
            if (!empty($metadataValues)) {
                $metadataValues .= '|';
            }
            $metadataValues .= $nextValue;
        }
        if (empty($metadataFields) || empty($metadataValues)) {
            return ['errors' => 'No valid metadata from Multigest mapping'];
        }

        //return [$metadataFields, $metadataValues];

        $soapClient = new \SoapClient($multigestUri, ['trace' => true]);

        $result = [];
        $soapClient->GedSetModeUid(1);
        $soapClient->GedChampReset();
        $soapClient->GedAddMultiChampRequete($metadataFields, $metadataValues);
        $result[] = $soapClient->GedDossierCreate($entityConfiguration['sasId'], $entityConfiguration['login'], 0);
        $soapClient->GedChampReset();
        $soapClient->GedAddChampRecherche($keyMetadataField, $keyMetadataValue);
        $result[] = $soapClient->GedDossierExist($entityConfiguration['sasId'], $entityConfiguration['login']);
        $result[] = $soapClient->GedImporterDocumentStream(
            $entityConfiguration['sasId'],
            $entityConfiguration['login'],
            base64_encode($fileContent),
            '',
            '',
            '',
            $fileExtension,
            0,
            0,
            0,
            '',
            '',
            '',
            '',
            -1
        );

        return $result;

        $properties = [];
        if (!empty($multigestParameters['mapping']['document'])) {
            $resourceContacts = ResourceContactModel::get([
                'where'     => ['res_id = ?', 'mode = ?'],
                'data'      => [$args['resId'], 'sender']
            ]);
            $rawContacts = [];
            foreach ($resourceContacts as $resourceContact) {
                if ($resourceContact['type'] == 'contact') {
                    $rawContacts[] = ContactModel::getById([
                        'select'    => ['*'],
                        'id'        => $resourceContact['item_id']
                    ]);
                }
            }

            // TODO export mapping logic to another function ?
            foreach ($multigestParameters['mapping']['document'] as $key => $multigestParameter) {
                if ($multigestParameter == 'multigestLogin') {
                    $properties[$key] = $entityInformations['multigest']['login'];
                } elseif ($multigestParameter == 'doctypeLabel') {
                    if (!empty($document['type_id'])) {
                        $doctype = DoctypeModel::getById(['select' => ['description'], 'id' => $document['type_id']]);
                    }
                    $properties[$key] = $doctype['description'] ?? '';
                } elseif ($multigestParameter == 'priorityLabel') {
                    if (!empty($document['priority'])) {
                        $priority = PriorityModel::getById(['select' => ['label'], 'id' => $document['priority']]);
                        $properties[$key] = $priority['label'];
                    }
                } elseif ($multigestParameter == 'destinationLabel') {
                    if (!empty($document['destination'])) {
                        $destination = EntityModel::getByEntityId(['entityId' => $document['destination'], 'select' => ['entity_label']]);
                        $properties[$key] = $destination['entity_label'];
                    }
                } elseif ($multigestParameter == 'initiatorLabel') {
                    if (!empty($document['initiator'])) {
                        $initiator = EntityModel::getByEntityId(['entityId' => $document['initiator'], 'select' => ['entity_label']]);
                        $properties[$key] = $initiator['entity_label'];
                    }
                } elseif ($multigestParameter == 'destUserLabel') {
                    if (!empty($document['dest_user'])) {
                        $properties[$key] = UserModel::getLabelledUserById(['id' => $document['dest_user']]);
                    }
                } elseif (strpos($multigestParameter, 'senderCompany_') !== false) {
                    $contactNb = explode('_', $multigestParameter)[1];
                    $properties[$key] = $rawContacts[$contactNb]['company'] ?? '';
                } elseif (strpos($multigestParameter, 'senderCivility_') !== false) {
                    $contactNb = explode('_', $multigestParameter)[1];
                    $civility = null;
                    if (!empty($rawContacts[$contactNb]['civility'])) {
                        $civility = ContactCivilityController::getLabelById(['id' => $rawContacts[$contactNb]['civility']]);
                    }
                    $properties[$key] = $civility ?? '';
                } elseif (strpos($multigestParameter, 'senderFirstname_') !== false) {
                    $contactNb = explode('_', $multigestParameter)[1];
                    $properties[$key] = $rawContacts[$contactNb]['firstname'] ?? '';
                } elseif (strpos($multigestParameter, 'senderLastname_') !== false) {
                    $contactNb = explode('_', $multigestParameter)[1];
                    $properties[$key] = $rawContacts[$contactNb]['lastname'] ?? '';
                } elseif (strpos($multigestParameter, 'senderFunction_') !== false) {
                    $contactNb = explode('_', $multigestParameter)[1];
                    $properties[$key] = $rawContacts[$contactNb]['function'] ?? '';
                } elseif (strpos($multigestParameter, 'senderAddress_') !== false) {
                    $contactNb = explode('_', $multigestParameter)[1];
                    if (!empty($rawContacts[$contactNb])) {
                        $contactToDisplay = ContactController::getFormattedContactWithAddress(['contact' => $rawContacts[$contactNb]]);
                    }
                    $properties[$key] = $contactToDisplay['contact']['address'] ?? '';
                } elseif ($multigestParameter == 'doctypeSecondLevelLabel') {
                    if (!empty($document['type_id'])) {
                        $doctype = DoctypeModel::getById(['select' => ['doctypes_second_level_id'], 'id' => $document['type_id']]);
                        $doctypeSecondLevel = SecondLevelModel::getById(['id' => $doctype['doctypes_second_level_id'], 'select' => ['doctypes_second_level_label']]);
                    }
                    $properties[$key] = $doctypeSecondLevel['doctypes_second_level_label'] ?? '';
                } elseif (strpos($multigestParameter, 'customField_') !== false) {
                    $customId = explode('_', $multigestParameter)[1];
                    $customValue = json_decode($document['custom_fields'], true);
                    $properties[$key] = (!empty($customValue[$customId]) && is_string($customValue[$customId])) ? $customValue[$customId] : '';
                } elseif ($multigestParameter == 'currentDate') {
                    $date = new \DateTime();
                    $properties[$key] = $date->format('d-m-Y H:i');
                } else {
                    $properties[$key] = $document[$multigestParameter];
                }
            }
        }

        $body = [
            'properties' => $properties,
        ];
        $curlResponse = CurlModel::exec([
            'url'           => "{$multigestUri}/multigest/versions/1/nodes/{$documentId}",
            'basicAuth'     => ['user' => $entityInformations['multigest']['login'], 'password' => $entityInformations['multigest']['password']],
            'headers'       => ['content-type:application/json', 'Accept: application/json'],
            'method'        => 'PUT',
            'body'          => json_encode($body)
        ]);
        if ($curlResponse['code'] != 200) {
            return ['errors' => "Update resource {$args['resId']} failed : " . json_encode($curlResponse['response'])];
        }

        $externalId = json_decode($document['external_id'], true);
        $externalId['multigestId'] = $documentId;
        ResModel::update(['set' => ['external_id' => json_encode($externalId)], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);

        $attachments = AttachmentModel::get([
            'select'    => ['res_id', 'title', 'identifier', 'external_id', 'docserver_id', 'path', 'filename', 'format', 'attachment_type'],
            'where'     => ['res_id_master = ?', 'attachment_type not in (?)', 'status not in (?)'],
            'data'      => [$args['resId'], ['signed_response'], ['DEL', 'OBS']]
        ]);
        $firstAttachment = true;
        $attachmentsTitlesSent = [];
        foreach ($attachments as $attachment) {
            $adrInfo = [
                'docserver_id'  => $attachment['docserver_id'],
                'path'          => $attachment['path'],
                'filename'      => $attachment['filename']
            ];
            if (empty($adrInfo['docserver_id'])) {
                continue;
            }
            $docserver = DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
            if (empty($docserver['path_template'])) {
                continue;
            }
            $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $adrInfo['path']) . $adrInfo['filename'];
            if (!is_file($pathToDocument)) {
                continue;
            }
            $fileContent = file_get_contents($pathToDocument);
            if ($fileContent === false) {
                continue;
            }

            if ($firstAttachment) {
                $curlResponse = CurlModel::exec([
                    'url'           => "{$multigestUri}/multigest/versions/1/nodes/{$resourceFolderId}/children",
                    'basicAuth'     => ['user' => $entityInformations['multigest']['login'], 'password' => $entityInformations['multigest']['password']],
                    'headers'       => ['content-type:application/json', 'Accept: application/json'],
                    'method'        => 'POST',
                    'body'          => json_encode(['name' => 'Pièces jointes', 'nodeType' => 'cm:folder'])
                ]);
                if ($curlResponse['code'] != 201) {
                    return ['errors' => "Create folder 'Pièces jointes' failed : " . json_encode($curlResponse['response'])];
                }
                $attachmentsFolderId = $curlResponse['response']['entry']['id'];
            }

            if (empty($attachmentsFolderId)) {
                continue;
            }
            $firstAttachment = false;
            if (in_array($attachment['title'], $attachmentsTitlesSent)) {
                $i = 1;
                $newTitle = "{$attachment['title']}_{$i}";
                while (in_array($newTitle, $attachmentsTitlesSent)) {
                    $newTitle = "{$attachment['title']}_{$i}";
                    ++$i;
                }
                $attachment['title'] = $newTitle;
            }
            $multipartBody = [
                'filedata' => ['isFile' => true, 'filename' => $attachment['title'], 'content' => $fileContent],
            ];
            $curlResponse = CurlModel::exec([
                'url'           => "{$multigestUri}/multigest/versions/1/nodes/{$attachmentsFolderId}/children",
                'basicAuth'     => ['user' => $entityInformations['multigest']['login'], 'password' => $entityInformations['multigest']['password']],
                'method'        => 'POST',
                'multipartBody' => $multipartBody
            ]);
            if ($curlResponse['code'] != 201) {
                return ['errors' => "Send attachment {$attachment['res_id']} failed : " . json_encode($curlResponse['response'])];
            }

            $attachmentId = $curlResponse['response']['entry']['id'];

            $properties = [];
            if (!empty($multigestParameters['mapping']['attachment'])) {
                foreach ($multigestParameters['mapping']['attachment'] as $key => $multigestParameter) {
                    if ($multigestParameter == 'typeLabel') {
                        $attachmentType = AttachmentTypeModel::getByTypeId(['select' => ['label'], 'typeId' => $attachment['attachment_type']]);
                        $properties[$key] = $attachmentType['label'] ?? '';
                    } else {
                        $properties[$key] = $attachment[$multigestParameter];
                    }
                }
            }

            $body = [
                'properties' => $properties,
            ];
            $curlResponse = CurlModel::exec([
                'url'           => "{$multigestUri}/multigest/versions/1/nodes/{$attachmentId}",
                'basicAuth'     => ['user' => $entityInformations['multigest']['login'], 'password' => $entityInformations['multigest']['password']],
                'headers'       => ['content-type:application/json', 'Accept: application/json'],
                'method'        => 'PUT',
                'body'          => json_encode($body)
            ]);
            if ($curlResponse['code'] != 200) {
                return ['errors' => "Update attachment {$attachment['res_id']} failed : " . json_encode($curlResponse['response'])];
            }

            $attachmentsTitlesSent[] = $attachment['title'];

            $externalId = json_decode($attachment['external_id'], true);
            $externalId['multigestId'] = $attachmentId;
            AttachmentModel::update(['set' => ['external_id' => json_encode($externalId)], 'where' => ['res_id = ?'], 'data' => [$attachment['res_id']]]);
        }

        if (!empty($multigestParameters['mapping']['folderModification'])) {
            $body = [
                'properties' => $multigestParameters['mapping']['folderModification'],
            ];
            $curlResponse = CurlModel::exec([
                'url'           => "{$multigestUri}/multigest/versions/1/nodes/{$resourceFolderId}",
                'basicAuth'     => ['user' => $entityInformations['multigest']['login'], 'password' => $entityInformations['multigest']['password']],
                'headers'       => ['content-type:application/json', 'Accept: application/json'],
                'method'        => 'PUT',
                'body'          => json_encode($body)
            ]);
            if ($curlResponse['code'] != 200) {
                return ['errors' => "Update multigest folder {$resourceFolderId} failed : " . json_encode($curlResponse['response'])];
            }
        }

        $message = empty($args['folderName']) ? " (envoyé au dossier {$args['folderId']})" : " (envoyé au dossier {$args['folderName']})";
        return ['history' => $message];
    }
}
