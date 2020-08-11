<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Resource Controller
* @author dev@maarch.org
*/

namespace Resource\controllers;

use AcknowledgementReceipt\models\AcknowledgementReceiptModel;
use Action\models\ActionModel;
use Attachment\models\AttachmentModel;
use Basket\models\BasketModel;
use Basket\models\GroupBasketModel;
use Basket\models\RedirectBasketModel;
use Convert\controllers\ConvertPdfController;
use Convert\controllers\ConvertThumbnailController;
use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Email\models\EmailModel;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use Folder\models\FolderModel;
use Folder\models\ResourceFolderModel;
use Group\controllers\GroupController;
use Group\controllers\PrivilegeController;
use Group\models\GroupModel;
use History\controllers\HistoryController;
use IndexingModel\models\IndexingModelFieldModel;
use MessageExchange\models\MessageExchangeModel;
use Note\models\NoteModel;
use Priority\models\PriorityModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Resource\models\UserFollowedResourceModel;
use Respect\Validation\Validator;
use Shipping\models\ShippingModel;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;
use Status\models\StatusModel;
use Tag\models\ResourceTagModel;
use User\controllers\UserController;
use User\models\UserModel;

class ResController extends ResourceControlController
{
    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::canIndex(['userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        $body = StoreController::setDisabledFields($body);

        $control = ResourceControlController::controlResource(['body' => $body]);
        if (!empty($control['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
        }

        $resId = StoreController::storeResource($body);
        if (empty($resId) || !empty($resId['errors'])) {
            return $response->withStatus(500)->withJson(['errors' => '[ResController create] ' . $resId['errors']]);
        }

        ResController::createAdjacentData(['body' => $body, 'resId' => $resId]);

        if (!empty($body['followed'])) {
            UserFollowedResourceModel::create([
                'userId'    => $GLOBALS['id'],
                'resId'     => $resId
            ]);
        }

        if (!empty($body['encodedFile'])) {
            ConvertPdfController::convert([
                'resId'     => $resId,
                'collId'    => 'letterbox_coll',
                'version'   => 1
            ]);

            $customId = CoreConfigModel::getCustomId();
            $customId = empty($customId) ? 'null' : $customId;
            exec("php src/app/convert/scripts/FullTextScript.php --customId {$customId} --resId {$resId} --collId letterbox_coll --userId {$GLOBALS['id']} > /dev/null &");
        }

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $resId,
            'eventType' => 'ADD',
            'info'      => _DOC_ADDED,
            'moduleId'  => 'resource',
            'eventId'   => 'resourceCreation',
        ]);

        return $response->withJson(['resId' => $resId]);
    }

    public function getById(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $queryParams = $request->getQueryParams();

        $select = ['model_id', 'category_id', 'priority', 'status', 'subject', 'alt_identifier', 'process_limit_date', 'closing_date', 'creation_date', 'modification_date', 'integrations'];
        if (empty($queryParams['light'])) {
            $select = array_merge($select, ['type_id', 'typist', 'destination', 'initiator', 'confidentiality', 'doc_date', 'admission_date', 'departure_date', 'barcode', 'custom_fields']);
        }

        $document = ResModel::getById([
            'select'    => $select,
            'resId'     => $args['resId']
        ]);
        if (empty($document)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        $unchangeableData = [
            'resId'             => (int)$args['resId'],
            'modelId'           => $document['model_id'],
            'categoryId'        => $document['category_id'],
            'chrono'            => $document['alt_identifier'],
            'status'            => $document['status'],
            'closingDate'       => $document['closing_date'],
            'creationDate'      => $document['creation_date'],
            'modificationDate'  => $document['modification_date'],
            'integrations'      => json_decode($document['integrations'], true)
        ];
        $formattedData = [
            'subject'           => $document['subject'],
            'processLimitDate'  => $document['process_limit_date'],
            'priority'          => $document['priority']
        ];
        if (empty($queryParams['light'])) {
            $formattedData = array_merge($formattedData, [
                'doctype'           => $document['type_id'],
                'typist'            => $document['typist'],
                'typistLabel'       => UserModel::getLabelledUserById(['id' => $document['typist']]),
                'destination'       => $document['destination'],
                'initiator'         => $document['initiator'],
                'confidentiality'   => $document['confidentiality'] == 'Y',
                'documentDate'      => $document['doc_date'],
                'arrivalDate'       => $document['admission_date'],
                'departureDate'     => $document['departure_date'],
                'barcode'           => $document['barcode']
            ]);
        }

        $modelFields = IndexingModelFieldModel::get([
            'select'    => ['identifier'],
            'where'     => ['model_id = ?'],
            'data'      => [$document['model_id']]
        ]);
        $modelFields = array_column($modelFields, 'identifier');

        foreach ($formattedData as $key => $data) {
            if (!in_array($key, $modelFields)) {
                unset($formattedData[$key]);
            }
        }
        $formattedData = array_merge($unchangeableData, $formattedData);

        if (!empty($formattedData['destination'])) {
            $entity = EntityModel::getByEntityId(['entityId' => $formattedData['destination'], 'select' => ['entity_label', 'id']]);
            $formattedData['destination'] = $entity['id'];
            $formattedData['destinationLabel'] = $entity['entity_label'];
        }
        if (!empty($formattedData['initiator'])) {
            $entity = EntityModel::getByEntityId(['entityId' => $formattedData['initiator'], 'select' => ['entity_label', 'id']]);
            $formattedData['initiator'] = $entity['id'];
            $formattedData['initiatorLabel'] = $entity['entity_label'];
        }
        if (!empty($formattedData['status'])) {
            $status = StatusModel::getById(['id' => $formattedData['status'], 'select' => ['label_status', 'can_be_modified']]);
            $formattedData['statusLabel'] = $status['label_status'];
            $formattedData['statusAlterable'] = $status['can_be_modified'] == 'Y';
        }
        if (!empty($formattedData['priority'])) {
            $priority = PriorityModel::getById(['id' => $formattedData['priority'], 'select' => ['label', 'color']]);
            $formattedData['priorityLabel'] = $priority['label'];
            $formattedData['priorityColor'] = $priority['color'];
        }

        if (in_array('senders', $modelFields)) {
            $formattedData['senders'] = ResourceContactModel::get([
                'select'    => ['item_id as id', 'type'],
                'where'     => ['res_id = ?', 'mode = ?'],
                'data'      => [$args['resId'], 'sender']
            ]);
        }
        if (in_array('recipients', $modelFields)) {
            $formattedData['recipients'] = ResourceContactModel::get([
                'select'    => ['item_id as id', 'type'],
                'where'     => ['res_id = ?', 'mode = ?'],
                'data'      => [$args['resId'], 'recipient']
            ]);
        }

        if (empty($queryParams['light'])) {
            $formattedData['customFields'] = !empty($document['custom_fields']) ? json_decode($document['custom_fields'], true) : [];

            $entities = EntityModel::getWithUserEntities([
                'select' => ['entities.id'],
                'where'  => ['user_id = ?'],
                'data'   => [$GLOBALS['id']]
            ]);
            $entities = array_column($entities, 'id');
            $folders = [];
            if (!empty($entities)) {
                $folders = FolderModel::getWithEntitiesAndResources([
                    'select'    => ['distinct(resources_folders.folder_id)'],
                    'where'     => ['resources_folders.res_id = ?', '(entities_folders.entity_id in (?) OR folders.user_id = ? OR keyword = ?)'],
                    'data'      => [$args['resId'], $entities, $GLOBALS['id'], 'ALL_ENTITIES']
                ]);
            }
            $formattedData['folders'] = array_column($folders, 'folder_id');

            $tags = ResourceTagModel::get(['select' => ['tag_id'], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);
            $formattedData['tags'] = array_column($tags, 'tag_id');
        } else {
            $followed = UserFollowedResourceModel::get([
                'select'    => [1],
                'where'     => ['user_id = ?', 'res_id = ?'],
                'data'      => [$GLOBALS['id'], $args['resId']]
            ]);
            $formattedData['followed'] = !empty($followed);
        }

        return $response->withJson($formattedData);
    }

    public function update(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route resId is not an integer']);
        } elseif (!PrivilegeController::canUpdateResource(['userId' => $GLOBALS['id'], 'resId' => $args['resId']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        $body = StoreController::setDisabledFields($body);
        
        $queryParams = $request->getQueryParams();

        $onlyDocument = !empty($queryParams['onlyDocument']);

        unset($body['destination']);
        unset($body['diffusionList']);
        $control = ResourceControlController::controlUpdateResource(['body' => $body, 'resId' => $args['resId'], 'onlyDocument' => $onlyDocument]);
        if (!empty($control['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
        }

        $resource = ResModel::getById(['resId' => $args['resId'], 'select' => ['alt_identifier', 'filename', 'docserver_id', 'path', 'fingerprint', 'version', 'model_id', 'custom_fields']]);

        if (!empty($body['modelId']) && $resource['model_id'] != $body['modelId']) {
            $resourceModelFields = IndexingModelFieldModel::get([
                'select' => ['identifier'],
                'where'  => ['model_id = ?'],
                'data'   => [$resource['model_id']]
            ]);
            $resourceModelFields = array_column($resourceModelFields, 'identifier');

            $newModelFields = IndexingModelFieldModel::get([
                'select' => ['identifier'],
                'where'  => ['model_id = ?'],
                'data'   => [$body['modelId']]
            ]);
            $newModelFields = array_column($newModelFields, 'identifier');

            ResController::resetResourceFields(['oldFieldList' => $resourceModelFields, 'newFieldList' => $newModelFields, 'resId' => $args['resId']]);
        }

        if (!empty($resource['filename']) && !empty($body['encodedFile'])) {
            AdrModel::createDocumentAdr([
                'resId'         => $args['resId'],
                'type'          => 'DOC',
                'docserverId'   => $resource['docserver_id'],
                'path'          => $resource['path'],
                'filename'      => $resource['filename'],
                'version'       => $resource['version'],
                'fingerprint'   => $resource['fingerprint']
            ]);
        }

        if ($onlyDocument) {
            $body = [
                'encodedFile'   => $body['encodedFile'],
                'format'        => $body['format']
            ];
        }
        $body['resId'] = $args['resId'];
        $resId = StoreController::storeResource($body);
        if (empty($resId) || !empty($resId['errors'])) {
            return $response->withStatus(500)->withJson(['errors' => '[ResController update] ' . $resId['errors']]);
        }

        if (!$onlyDocument) {
            ResController::updateAdjacentData(['body' => $body, 'resId' => $args['resId']]);
        }

        if (!empty($body['encodedFile'])) {
            ConvertPdfController::convert([
                'resId'     => $args['resId'],
                'collId'    => 'letterbox_coll',
                'version'   => $resource['version'] + 1
            ]);

            $customId = CoreConfigModel::getCustomId();
            $customId = empty($customId) ? 'null' : $customId;
            exec("php src/app/convert/scripts/FullTextScript.php --customId {$customId} --resId {$args['resId']} --collId letterbox_coll --userId {$GLOBALS['id']} > /dev/null &");

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $args['resId'],
                'eventType' => 'UP',
                'info'      => _FILE_UPDATED . " : {$resource['alt_identifier']}",
                'moduleId'  => 'resource',
                'eventId'   => 'fileModification'
            ]);
        }

        if (!$onlyDocument) {
            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $args['resId'],
                'eventType' => 'UP',
                'info'      => _DOC_UPDATED . " : {$resource['alt_identifier']}",
                'moduleId'  => 'resource',
                'eventId'   => 'resourceModification'
            ]);
        }

        return $response->withStatus(204);
    }

    public function updateStatus(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (empty($data['status'])) {
            $data['status'] = 'COU';
        }
        if (empty(StatusModel::getById(['id' => $data['status']]))) {
            return $response->withStatus(400)->withJson(['errors' => _STATUS_NOT_FOUND]);
        }
        if (empty($data['historyMessage'])) {
            $data['historyMessage'] = _UPDATE_STATUS;
        }

        $check = Validator::arrayType()->notEmpty()->validate($data['chrono']) || Validator::arrayType()->notEmpty()->validate($data['resId']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['status']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['historyMessage']);
        if (!$check) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        $closedActions = ActionModel::get([
            'select' => ['distinct id_status'],
            'where'  => ['component in (?)'],
            'data'   => [['closeMailAction', 'closeMailWithAttachmentsOrNotesAction', 'closeAndIndexAction']]
        ]);
        $closedStatus  = array_column($closedActions, 'id_status');
        $closingDate   = in_array($data['status'], $closedStatus) ? 'CURRENT_TIMESTAMP' : null;

        $identifiers = !empty($data['chrono']) ? $data['chrono'] : $data['resId'];
        foreach ($identifiers as $id) {
            if (!empty($data['chrono'])) {
                $document = ResModel::getByAltIdentifier(['altIdentifier' => $id, 'select' => ['res_id']]);
            } else {
                $document = ResModel::getById(['resId' => $id, 'select' => ['res_id']]);
            }
            if (empty($document)) {
                return $response->withStatus(400)->withJson(['errors' => _DOCUMENT_NOT_FOUND]);
            }
            if (!ResController::hasRightByResId(['resId' => [$document['res_id']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }

            if ($closingDate == null) {
                ResModel::update(['set' => ['status' => $data['status'], 'closing_date' => $closingDate], 'where' => ['res_id = ?'], 'data' => [$document['res_id']]]);
            } else {
                ResModel::update(['set' => ['status' => $data['status'], 'closing_date' => $closingDate], 'where' => ['res_id = ?', 'closing_date is null'], 'data' => [$document['res_id']]]);
            }

            HistoryController::add([
                'tableName' => 'res_letterbox',
                'recordId'  => $document['res_id'],
                'eventType' => 'UP',
                'info'      => $data['historyMessage'],
                'moduleId'  => 'resource',
                'eventId'   => 'resup',
            ]);
        }

        return $response->withJson(['success' => 'success']);
    }

    public function getFileContent(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $document = ResModel::getById(['select' => ['filename', 'format', 'typist'], 'resId' => $aArgs['resId']]);
        if (empty($document)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        } elseif (empty($document['filename'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Document has no file']);
        }
        $originalFormat = $document['format'];
        $creatorId = $document['typist'];

        $convertedDocument = ConvertPdfController::getConvertedPdfById(['resId' => $aArgs['resId'], 'collId' => 'letterbox_coll']);
        if (!empty($convertedDocument['errors'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Conversion error : ' . $convertedDocument['errors']]);
        }

        $document = $convertedDocument;

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if ($document['fingerprint'] != $fingerprint) {
            return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
        }

        $fileContent = WatermarkController::watermarkResource(['resId' => $aArgs['resId'], 'path' => $pathToDocument]);

        if (empty($fileContent)) {
            $fileContent = file_get_contents($pathToDocument);
        }
        if ($fileContent === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $aArgs['resId'],
            'eventType' => 'VIEW',
            'info'      => _DOC_DISPLAYING . " : {$aArgs['resId']}",
            'moduleId'  => 'resource',
            'eventId'   => 'resview',
        ]);

        $data = $request->getQueryParams();

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);

        if ($data['mode'] == 'base64') {
            return $response->withJson(['encodedDocument' => base64_encode($fileContent), 'originalFormat' => $originalFormat, 'mimeType' => $mimeType,'originalCreatorId' => $creatorId]);
        } else {
            $pathInfo = pathinfo($pathToDocument);

            $response->write($fileContent);
            $contentDisposition = $data['mode'] == 'view' ? 'inline' : 'attachment';
            $response = $response->withAddedHeader('Content-Disposition', "{$contentDisposition}; filename=maarch.{$pathInfo['extension']}");
            return $response->withHeader('Content-Type', $mimeType);
        }
    }

    public function getVersionsInformations(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $docVersions = [];
        $pdfVersions = [];
        $signedVersions = [];
        $noteVersions = [];
        $resource = ResModel::getById(['resId' => $args['resId'], 'select' => ['version', 'filename', 'format']]);
        if (empty($resource['filename'])) {
            return $response->withJson(['DOC' => $docVersions, 'PDF' => $pdfVersions, 'SIGN' => $signedVersions, 'NOTE' => $noteVersions]);
        }

        $canConvert = ConvertPdfController::canConvert(['extension' => $resource['format']]);
        
        $convertedDocuments = AdrModel::getDocuments([
            'select'    => ['type', 'version'],
            'where'     => ['res_id = ?', 'type in (?)'],
            'data'      => [$args['resId'], ['DOC', 'PDF', 'SIGN', 'NOTE']],
            'orderBy'   => ['version ASC']
        ]);
        if (empty($convertedDocuments)) {
            return $response->withJson(['DOC' => [$resource['version']], 'PDF' => $pdfVersions, 'SIGN' => $signedVersions, 'NOTE' => $noteVersions, 'convert' => $canConvert]);
        }

        foreach ($convertedDocuments as $convertedDocument) {
            if ($convertedDocument['type'] == 'DOC') {
                $docVersions[] = $convertedDocument['version'];
            } elseif ($convertedDocument['type'] == 'PDF') {
                $pdfVersions[] = $convertedDocument['version'];
            } elseif ($convertedDocument['type'] == 'SIGN') {
                $signedVersions[] = $convertedDocument['version'];
            } elseif ($convertedDocument['type'] == 'NOTE') {
                $noteVersions[] = $convertedDocument['version'];
            }
        }
        $docVersions[] = $resource['version'];

        return $response->withJson(['DOC' => $docVersions, 'PDF' => $pdfVersions, 'SIGN' => $signedVersions, 'NOTE' => $noteVersions, 'convert' => $canConvert]);
    }

    public function getVersionFileContent(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $resource = ResModel::getById(['resId' => $args['resId'], 'select' => ['version', 'filename']]);
        if (empty($resource['filename'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Document has no file']);
        } elseif (!Validator::intVal()->validate($args['version']) || $args['version'] > $resource['version'] || $args['version'] < 1) {
            return $response->withStatus(400)->withJson(['errors' => 'Incorrect version']);
        }

        $queryParams = $request->getQueryParams();

        $type = 'PDF';
        if (!empty($queryParams['type']) && in_array($queryParams['type'], ['PDF', 'SIGN', 'NOTE'])) {
            $type = $queryParams['type'];
        }

        if ($type == 'NOTE' && !PrivilegeController::hasPrivilege(['privilegeId' => 'view_documents_with_notes', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $convertedDocument = AdrModel::getDocuments([
            'select'    => ['id', 'docserver_id', 'path', 'filename', 'fingerprint'],
            'where'     => ['res_id = ?', 'type = ?', 'version = ?'],
            'data'      => [$args['resId'], $type, $args['version']]
        ]);

        if (empty($convertedDocument[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'Type has no file']);
        }
        $convertedDocument = $convertedDocument[0];

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $convertedDocument['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedDocument['path']) . $convertedDocument['filename'];
        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (empty($convertedDocument['fingerprint'])) {
            AdrModel::updateDocumentAdr(['set' => ['fingerprint' => $fingerprint], 'where' => ['id = ?'], 'data' => [$convertedDocument['id']]]);
            $convertedDocument['fingerprint'] = $fingerprint;
        }
        if ($convertedDocument['fingerprint'] != $fingerprint) {
            return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
        }

        $fileContent = WatermarkController::watermarkResource(['resId' => $args['resId'], 'path' => $pathToDocument]);
        if (empty($fileContent)) {
            $fileContent = file_get_contents($pathToDocument);
        }
        if ($fileContent === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        return $response->withJson(['encodedDocument' => base64_encode($fileContent)]);
    }

    public function getOriginalFileContent(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename', 'category_id', 'version', 'fingerprint'], 'resId' => $args['resId']]);
        if (empty($document)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        if (empty($document['filename'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Document has no file']);
        }

        $convertedDocument = AdrModel::getDocuments([
            'select'    => ['docserver_id', 'path', 'filename', 'fingerprint'],
            'where'     => ['res_id = ?', 'type = ?', 'version = ?'],
            'data'      => [$args['resId'], 'SIGN', $document['version']],
            'limit'     => 1
        ]);
        $document = $convertedDocument[0] ?? $document;

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!file_exists($pathToDocument)) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (empty($convertedDocument) && empty($document['fingerprint'])) {
            ResModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);
            $document['fingerprint'] = $fingerprint;
        }

        if ($document['fingerprint'] != $fingerprint) {
            return $response->withStatus(400)->withJson(['errors' => 'Fingerprints do not match']);
        }

        $fileContent = file_get_contents($pathToDocument);
        if ($fileContent === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Document not found on docserver']);
        }


        HistoryController::add([
            'tableName' => 'res_letterbox',
            'recordId'  => $args['resId'],
            'eventType' => 'VIEW',
            'info'      => _DOC_DISPLAYING . " : {$args['resId']}",
            'moduleId'  => 'resource',
            'eventId'   => 'resview',
        ]);

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $pathInfo = pathinfo($pathToDocument);
        $data = $request->getQueryParams();

        if ($data['mode'] == 'base64') {
            return $response->withJson(['encodedDocument' => base64_encode($fileContent), 'extension' => $pathInfo['extension'], 'mimeType' => $mimeType]);
        } else {
            $response->write($fileContent);
            $response = $response->withAddedHeader('Content-Disposition', "attachment; filename=maarch.{$pathInfo['extension']}");
            return $response->withHeader('Content-Type', $mimeType);
        }
    }

    public function getThumbnailContent(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId'])) {
            return $response->withStatus(403)->withJson(['errors' => 'resId param is not an integer']);
        }

        $pathToThumbnail = 'dist/assets/noThumbnail.png';

        $document = ResModel::getById(['select' => ['filename', 'version'], 'resId' => $args['resId']]);
        if (empty($document)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        if (!empty($document['filename']) && ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            $tnlAdr = AdrModel::getDocuments(['select' => ['docserver_id', 'path', 'filename'], 'where' => ['res_id = ?', 'type = ?', 'version = ?'], 'data' => [$args['resId'], 'TNL', $document['version']]]);
            if (empty($tnlAdr[0])) {
                $control = ConvertThumbnailController::convert(['type' => 'resource', 'resId' => $args['resId']]);
                if (!empty($control['errors'])) {
                    return $response->withStatus(400)->withJson(['errors' => $control['errors']]);
                }
                $tnlAdr = AdrModel::getDocuments(['select' => ['docserver_id', 'path', 'filename'], 'where' => ['res_id = ?', 'type = ?', 'version = ?'], 'data' => [$args['resId'], 'TNL', $document['version']]]);
            }

            if (!empty($tnlAdr[0])) {
                $docserver = DocserverModel::getByDocserverId(['docserverId' => $tnlAdr[0]['docserver_id'], 'select' => ['path_template']]);
                if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Docserver does not exist']);
                }

                $pathToThumbnail = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $tnlAdr[0]['path']) . $tnlAdr[0]['filename'];
            }
        }

        $fileContent = file_get_contents($pathToThumbnail);
        if ($fileContent === false) {
            return $response->withStatus(404)->withJson(['errors' => 'Thumbnail not found on docserver']);
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $pathInfo = pathinfo($pathToThumbnail);

        $response->write($fileContent);
        $response = $response->withAddedHeader('Content-Disposition', "inline; filename=maarch.{$pathInfo['extension']}");

        return $response->withHeader('Content-Type', $mimeType);
    }

    public function getItems(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $document = ResModel::getById([
            'select'    => ['linked_resources'],
            'resId'     => $args['resId']
        ]);
        if (empty($document)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        $linkedResources = json_decode($document['linked_resources'], true);
        if (!empty($linkedResources)) {
            $linkedResources = ResController::getAuthorizedResources(['resources' => $linkedResources, 'userId' => $GLOBALS['id']]);
        }
        $formattedData['linkedResources'] = count($linkedResources);

        $attachments = AttachmentModel::get(['select' => ['count(1)'], 'where' => ['res_id_master = ?', 'status in (?)'], 'data' => [$args['resId'], ['TRA', 'A_TRA', 'FRZ']]]);
        $formattedData['attachments'] = $attachments[0]['count'];

        $formattedData['diffusionList'] = 0;
        $formattedData['visaCircuit'] = 0;
        $formattedData['opinionCircuit'] = 0;
        $listInstanceItems = ListInstanceModel::get(['select' => ['count(1)', 'difflist_type'], 'where' => ['res_id = ?'], 'data' => [$args['resId']], 'groupBy' => ['difflist_type']]);
        foreach ($listInstanceItems as $item) {
            $type = $item['difflist_type'] == 'entity_id' ? 'diffusionList' : ($item['difflist_type'] == 'VISA_CIRCUIT' ? 'visaCircuit' : 'opinionCircuit');
            $formattedData[$type] = $item['count'];
        }

        $formattedData['notes'] = NoteModel::countByResId(['resId' => $args['resId'], 'userId' => $GLOBALS['id'], 'login' => $GLOBALS['login']]);

        $emails = EmailModel::get(['select' => ['count(1)'], 'where' => ["document->>'id' = ?", "(status != 'DRAFT' or (status = 'DRAFT' and user_id = ?))"], 'data' => [$args['resId'], $GLOBALS['id']]]);
        $acknowledgementReceipts = AcknowledgementReceiptModel::get([
            'select' => ['count(1)'],
            'where'  => ['res_id = ?'],
            'data'   => [$args['resId']]
        ]);
        $messageExchanges = MessageExchangeModel::get([
            'select' => ['count(1)'],
            'where'  => ['res_id_master = ?'],
            'data'   => [$args['resId']]
        ]);
        $attachments = AttachmentModel::get([
            'select' => ['res_id'],
            'where'  => ['res_id_master = ?'],
            'data'   => [$args['resId']]
        ]);
        $attachments = array_column($attachments, 'res_id');

        $where = '(document_id = ? and document_type = ?)';
        $data  = [$args['resId'], 'resource'];
        if (!empty($attachments)) {
            $where .= ' or (document_id in (?) and document_type = ?)';
            $data[] = $attachments;
            $data[] = 'attachment';
        }
        $shippings = ShippingModel::get([
            'select' => ['count(1)'],
            'where'  => [$where],
            'data'   => $data
        ]);

        $formattedData['emails'] = $emails[0]['count'] + $acknowledgementReceipts[0]['count'] + $messageExchanges[0]['count'] + $shippings[0]['count'];

        return $response->withJson($formattedData);
    }

    public function getCategories(Request $request, Response $response)
    {
        return $response->withJson(['categories' => ResModel::getCategories()]);
    }

    public function isAllowedForCurrentUser(Request $request, Response $response, array $aArgs)
    {
        if (!Validator::intVal()->validate($aArgs['resId']) || !ResController::hasRightByResId(['resId' => [$aArgs['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withJson(['isAllowed' => false]);
        }

        return $response->withJson(['isAllowed' => true]);
    }

    public function updateExternalInfos(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (empty($data['externalInfos'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request : externalInfos is empty']);
        }
        if (empty($data['status'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request : status is empty']);
        }

        foreach ($data['externalInfos'] as $mail) {
            if (!Validator::intType()->validate($mail['res_id'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: invalid res_id']);
            }
            if (!Validator::StringType()->notEmpty()->validate($mail['external_id'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: invalid external_id for element : '.$mail['res_id']]);
            }
        }

        foreach ($data['externalInfos'] as $mail) {
            $document = ResModel::getById(['resId' => $mail['res_id'], 'select' => ['res_id', 'external_id']]);
            if (empty($document)) {
                return $response->withStatus(400)->withJson(['errors' => _DOCUMENT_NOT_FOUND]);
            }
            if (!ResController::hasRightByResId(['resId' => [$document['res_id']], 'userId' => $GLOBALS['id']])) {
                return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
            }
            $externalId = json_decode($document['external_id'], true);
            $externalId['publikId'] = $mail['external_id'];
            ResModel::update(['set' => ['external_id' => json_encode($externalId), 'status' => $data['status']], 'where' => ['res_id = ?'], 'data' => [$document['res_id']]]);
        }

        return $response->withJson(['success' => 'success']);
    }

    public static function setInIntegrations(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (empty($body['resources']) || !Validator::arrayType()->validate($body['resources'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body param resources is missing']);
        }
        if (!ResController::hasRightByResId(['resId' => $body['resources'], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        if (empty($body['integrations']) || !Validator::arrayType()->validate($body['integrations'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body param integrations is missing or not an array']);
        }

        $documents = ResModel::get([
            'select' => ['alt_identifier', 'res_id'],
            'where'  => ['res_id in (?)'],
            'data'   => [$body['resources']]
        ]);
        $documents = array_column($documents, 'alt_identifier', 'res_id');

        if (isset($body['integrations']['inSignatureBook']) && Validator::boolType()->validate($body['integrations']['inSignatureBook'])) {
            $inSignatureBook = $body['integrations']['inSignatureBook'] ? 'true' : 'false';

            ResModel::update([
                'postSet'   => [
                    'integrations' => "jsonb_set(integrations, '{inSignatureBook}', '".$inSignatureBook."')",
                ],
                'where' => ['res_id in (?)'],
                'data'  => [$body['resources']]
            ]);

            $info = $body['integrations']['inSignatureBook'] ? _DOC_ADD_TO_SIGNATORY_BOOK : _DOC_REMOVE_FROM_SIGNATORY_BOOK;
            foreach ($body['resources'] as $resId) {
                HistoryController::add([
                    'tableName' => 'res_letterbox',
                    'recordId'  => $resId,
                    'eventType' => 'UP',
                    'info'      => $info . " : " . $documents[$resId],
                    'moduleId'  => 'resource',
                    'eventId'   => 'resourceModification',
                ]);
            }
        }

        if (isset($body['integrations']['inShipping']) && Validator::boolType()->validate($body['integrations']['inShipping'])) {
            $inShipping = $body['integrations']['inShipping'] ? 'true' : 'false';

            ResModel::update([
                'postSet'   => [
                    'integrations' => "jsonb_set(integrations, '{inShipping}', '".$inShipping."')",
                ],
                'where' => ['res_id in (?)'],
                'data'  => [$body['resources']]
            ]);

            $info = $body['integrations']['inShipping'] ? _DOC_ADD_TO_MAILEVA : _DOC_REMOVE_FROM_MAILEVA;
            foreach ($body['resources'] as $resId) {
                HistoryController::add([
                    'tableName' => 'res_letterbox',
                    'recordId'  => $resId,
                    'eventType' => 'UP',
                    'info'      => $info . " : " . $documents[$resId],
                    'moduleId'  => 'resource',
                    'eventId'   => 'resourceModification',
                ]);
            }
        }

        return $response->withStatus(204);
    }

    public function getField(Request $request, Response $response, array $args)
    {
        if (!ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $authorizedFields = ['destination', 'status', 'externalId'];
        if (!in_array($args['fieldId'], $authorizedFields)) {
            return $response->withStatus(403)->withJson(['errors' => 'Field out of perimeter']);
        }
        $mapping = [
            'destination'   => 'destination',
            'status'        => 'status',
            'externalId'    => 'external_id'
        ];

        $resource = ResModel::getById([
            'select'    => [$mapping[$args['fieldId']]],
            'resId'     => $args['resId']
        ]);
        if (empty($resource)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document does not exist']);
        }

        $queryParams = $request->getQueryParams();
        if ($args['fieldId'] == 'destination' && !empty($queryParams['alt']) && !empty($resource['destination'])) {
            $entity = EntityModel::getByEntityId(['entityId' => $resource['destination'], 'select' => ['id']]);
            $resource['destination'] = $entity['id'];
        } elseif ($args['fieldId'] == 'externalId') {
            $resource['externalId'] = json_decode($resource['external_id'], true);
        }

        return $response->withJson(['field' => $resource[$args['fieldId']]]);
    }

    public static function getEncodedDocument(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::boolType($aArgs, ['original']);

        $document = ResModel::getById(['select' => ['docserver_id', 'path', 'filename', 'subject', 'fingerprint'], 'resId' => $aArgs['resId']]);

        if (empty($aArgs['original'])) {
            $convertedDocument = ConvertPdfController::getConvertedPdfById(['resId' => $aArgs['resId'], 'collId' => 'letterbox_coll']);

            if (empty($convertedDocument['errors'])) {
                $document['docserver_id'] = $convertedDocument['docserver_id'];
                $document['path'] = $convertedDocument['path'];
                $document['filename'] = $convertedDocument['filename'];
                $document['fingerprint'] = $convertedDocument['fingerprint'];
            }
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            return ['errors' => 'Docserver does not exist'];
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!file_exists($pathToDocument)) {
            return ['errors' => 'Document not found on docserver'];
        }

        $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
        $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
        if (empty($convertedDocument) && empty($document['fingerprint'])) {
            ResModel::update(['set' => ['fingerprint' => $fingerprint], 'where' => ['res_id = ?'], 'data' => [$aArgs['resId']]]);
            $document['fingerprint'] = $fingerprint;
        }
        if ($document['fingerprint'] != $fingerprint) {
            return ['errors' => 'Fingerprints do not match'];
        }

        $fileContent = file_get_contents($pathToDocument);
        if ($fileContent === false) {
            return ['errors' => 'Document not found on docserver'];
        }

        $encodedDocument = base64_encode($fileContent);

        if (!empty($document['subject'])) {
            $document['subject'] = preg_replace(utf8_decode('@[\\/:*?"<>|]@i'), '_', substr($document['subject'], 0, 30));
        }

        $pathInfo = pathinfo($pathToDocument);
        $fileName = (empty($document['subject']) ? 'document' : $document['subject']) . ".{$pathInfo['extension']}";

        return ['encodedDocument' => $encodedDocument, 'fileName' => $fileName];
    }

    public static function hasRightByResId(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'userId']);
        ValidatorModel::intVal($args, ['userId']);
        ValidatorModel::arrayType($args, ['resId']);

        $resources = array_unique($args['resId']);

        $authorizedResources = ResController::getAuthorizedResources(['resources' => $resources, 'userId' => $args['userId']]);

        return count($authorizedResources) == count($resources);
    }

    public static function getAuthorizedResources(array $args)
    {
        ValidatorModel::notEmpty($args, ['resources', 'userId']);
        ValidatorModel::intVal($args, ['userId']);
        ValidatorModel::arrayType($args, ['resources']);


        $user = UserModel::getById(['id' => $args['userId'], 'select' => ['user_id']]);
        if (UserController::isRoot(['id' => $args['userId']])) {
            return $args['resources'];
        }

        $folder = PrivilegeController::hasPrivilege(['privilegeId' => 'include_folders_and_followed_resources_perimeter', 'userId' => $args['userId']]);

        $data = [$args['resources']];
        if ($folder) {
            $whereClause = '(res_id in (select res_id from users_followed_resources where user_id = ?))';
            $data[] = $args['userId'];

            $entities = UserModel::getEntitiesById(['id' => $args['userId'], 'select' => ['entities.id']]);
            $entities = array_column($entities, 'id');
            if (empty($entities)) {
                $entities = [0];
            }

            $foldersClause = 'res_id in (select res_id from folders LEFT JOIN entities_folders ON folders.id = entities_folders.folder_id LEFT JOIN resources_folders ON folders.id = resources_folders.folder_id ';
            $foldersClause .= "WHERE entities_folders.entity_id in (?) OR folders.user_id = ? OR keyword = 'ALL_ENTITIES')";
            $whereClause .= " OR ({$foldersClause})";
            $data[] = $entities;
            $data[] = $args['userId'];
        } else {
            $whereClause = '1=0';
        }

        $groups = UserModel::getGroupsByLogin(['login' => $user['user_id'], 'select' => ['where_clause']]);
        $groupsClause = '';
        foreach ($groups as $key => $group) {
            if (!empty($group['where_clause'])) {
                $groupClause = PreparedClauseController::getPreparedClause(['clause' => $group['where_clause'], 'login' => $user['user_id']]);
                if ($key > 0) {
                    $groupsClause .= ' or ';
                }
                $groupsClause .= "({$groupClause})";
            }
        }
        if (!empty($groupsClause)) {
            $whereClause .= " OR ({$groupsClause})";
        }

        $baskets = BasketModel::getBasketsByLogin(['login' => $user['user_id']]);
        $basketsClause = '';
        foreach ($baskets as $basket) {
            if (!empty($basket['basket_clause'])) {
                $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $user['user_id']]);
                if (!empty($basketsClause)) {
                    $basketsClause .= ' or ';
                }
                $basketsClause .= "({$basketClause})";
            }
        }
        $assignedBaskets = RedirectBasketModel::getAssignedBasketsByUserId(['userId' => $args['userId']]);
        foreach ($assignedBaskets as $basket) {
            if (!empty($basket['basket_clause'])) {
                $basketOwner = UserModel::getById(['id' => $basket['owner_user_id'], 'select' => ['user_id']]);
                $basketClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $basketOwner['user_id']]);
                if (!empty($basketsClause)) {
                    $basketsClause .= ' or ';
                }
                $basketsClause .= "({$basketClause})";
            }
        }
        if (!empty($basketsClause)) {
            $whereClause .= " OR ({$basketsClause})";
        }

        try {
            $res = ResModel::getOnView(['select' => ['res_id'], 'where' => ['res_id in (?)', "({$whereClause})"], 'data' => $data]);
            return array_column($res, 'res_id');
        } catch (\Exception $e) {
            return [];
        }
    }

    private static function createAdjacentData(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'body']);
        ValidatorModel::intVal($args, ['resId']);
        ValidatorModel::arrayType($args, ['body']);

        $body = $args['body'];

        if (!empty($body['diffusionList'])) {
            foreach ($body['diffusionList'] as $diffusion) {
                if ($diffusion['mode'] == 'dest') {
                    ResModel::update(['set' => ['dest_user' => $diffusion['id']], 'where' => ['res_id = ?'], 'data' => [$args['resId']]]);
                }
                ListInstanceModel::create([
                    'res_id'            => $args['resId'],
                    'sequence'          => 0,
                    'item_id'           => $diffusion['id'],
                    'item_type'         => $diffusion['type'] == 'user' ? 'user_id' : 'entity_id',
                    'item_mode'         => $diffusion['mode'],
                    'added_by_user'     => $GLOBALS['id'],
                    'difflist_type'     => 'entity_id'
                ]);
            }
        }
        if (!empty($body['folders'])) {
            foreach ($body['folders'] as $folder) {
                ResourceFolderModel::create(['res_id' => $args['resId'], 'folder_id' => $folder]);
            }
        }
        if (!empty($body['tags'])) {
            foreach ($body['tags'] as $tag) {
                ResourceTagModel::create(['res_id' => $args['resId'], 'tag_id' => $tag]);
            }
        }
        if (!empty($body['senders'])) {
            foreach ($body['senders'] as $sender) {
                ResourceContactModel::create(['res_id' => $args['resId'], 'item_id' => $sender['id'], 'type' => $sender['type'], 'mode' => 'sender']);
            }
        }
        if (!empty($body['recipients'])) {
            foreach ($body['recipients'] as $recipient) {
                ResourceContactModel::create(['res_id' => $args['resId'], 'item_id' => $recipient['id'], 'type' => $recipient['type'], 'mode' => 'recipient']);
            }
        }

        return true;
    }

    private static function updateAdjacentData(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'body']);
        ValidatorModel::intVal($args, ['resId']);
        ValidatorModel::arrayType($args, ['body']);

        $body = $args['body'];

        $entities = EntityModel::getWithUserEntities([
            'select' => ['entities.id'],
            'where'  => ['user_id = ?'],
            'data'   => [$GLOBALS['id']]
        ]);
        $entities = array_column($entities, 'id');
        if (empty($entities)) {
            $entities = [0];
        }
        $idToDelete = FolderModel::getWithEntitiesAndResources([
            'select'    => ['resources_folders.id'],
            'where'     => ['resources_folders.res_id = ?', '(entities_folders.entity_id in (?) OR folders.user_id = ? OR keyword = ?)'],
            'data'      => [$args['resId'], $entities, $GLOBALS['id'], 'ALL_ENTITIES']
        ]);
        $idToDelete = array_column($idToDelete, 'id');
        if (!empty($idToDelete)) {
            ResourceFolderModel::delete(['where' => ['id in (?)'], 'data' => [$idToDelete]]);
        }
        if (!empty($body['folders'])) {
            foreach ($body['folders'] as $folder) {
                ResourceFolderModel::create(['res_id' => $args['resId'], 'folder_id' => $folder]);
            }
        }
        ResourceTagModel::delete(['where' => ['res_id = ?'], 'data' => [$args['resId']]]);
        if (!empty($body['tags'])) {
            foreach ($body['tags'] as $tag) {
                ResourceTagModel::create(['res_id' => $args['resId'], 'tag_id' => $tag]);
            }
        }
        ResourceContactModel::delete(['where' => ['res_id = ?'], 'data' => [$args['resId']]]);
        if (!empty($body['senders'])) {
            foreach ($body['senders'] as $sender) {
                ResourceContactModel::create(['res_id' => $args['resId'], 'item_id' => $sender['id'], 'type' => $sender['type'], 'mode' => 'sender']);
            }
        }
        if (!empty($body['recipients'])) {
            foreach ($body['recipients'] as $recipient) {
                ResourceContactModel::create(['res_id' => $args['resId'], 'item_id' => $recipient['id'], 'type' => $recipient['type'], 'mode' => 'recipient']);
            }
        }

        return true;
    }

    public function getList(Request $request, Response $response)
    {
        $data = $request->getParams();

        if (!Validator::stringType()->notEmpty()->validate($data['select'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request: select is not valid']);
        }
        if (!Validator::stringType()->notEmpty()->validate($data['clause'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request: clause is not valid']);
        }
        if (!empty($data['withFile'])) {
            if (!Validator::boolType()->validate($data['withFile'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: withFile parameter is not a boolean']);
            }
        }

        if (!empty($data['orderBy'])) {
            if (!Validator::arrayType()->notEmpty()->validate($data['orderBy'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: orderBy parameter not valid']);
            }
        }

        if (!empty($data['limit'])) {
            if (!Validator::intType()->validate($data['limit'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Bad Request: limit parameter not valid']);
            }
        }
        $select = explode(',', $data['select']);

        $sve_start_date = false;
        $keySve = array_search('sve_start_date', array_map('trim', $select));
        if ($keySve !== false) {
            unset($select[$keySve]);
            $sve_start_date = true;
        }

        if ($sve_start_date && empty($select)) {
            $select[] = 'res_id';
        }

        if (!PreparedClauseController::isRequestValid(['select' => $select, 'clause' => $data['clause'], 'orderBy' => $data['orderBy'], 'limit' => $data['limit'], 'userId' => $GLOBALS['login']])) {
            return $response->withStatus(400)->withJson(['errors' => _INVALID_REQUEST]);
        }

        $where = [$data['clause']];
        if (!UserController::isRoot(['id' => $GLOBALS['id']])) {
            $groupsClause = GroupController::getGroupsClause(['userId' => $GLOBALS['login']]);
            if (empty($groupsClause)) {
                return $response->withStatus(400)->withJson(['errors' => 'User has no groups']);
            }
            $where[] = "({$groupsClause})";
        }

        if ($data['withFile'] === true) {
            $select[] = 'res_id';
        }

        $resources = ResModel::getOnView(['select' => $select, 'where' => $where, 'orderBy' => $data['orderBy'], 'limit' => $data['limit']]);
        if (!empty($resources) && $data['withFile'] === true) {
            foreach ($resources as $key => $res) {
                $document = ResModel::getById(['resId' => $res['res_id'], 'select' => ['path', 'filename', 'docserver_id']]);
                if (!empty($document['docserver_id'])) {
                    $docserver = DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
                    if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
                        $resources[$key]['fileBase64Content'] = null;
                    }
                    $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
                    if (!file_exists($pathToDocument)) {
                        $resources[$key]['fileBase64Content'] = null;
                    }
                    $file = file_get_contents($pathToDocument);
                    $base64Content = base64_encode($file);
                    $resources[$key]['fileBase64Content'] = $base64Content;
                } else {
                    $resources[$key]['fileBase64Content'] = null;
                }
            }
        }
        if (!empty($resources) && $sve_start_date) {
            $aResId = array_column($resources, 'res_id');
            $aSveStartDate = AcknowledgementReceiptModel::getByResIds([
                'select'  => ['res_id', 'min(send_date) as send_date'],
                'resIds'  => $aResId,
                'where'   => ['send_date IS NOT NULL', 'send_date != \'\''],
                'groupBy' => ['res_id']
            ]);
            foreach ($resources as $key => $res) {
                $resources[$key]['sve_start_date'] = null;
                foreach ($aSveStartDate as $valueSveStartDate) {
                    if ($res['res_id'] == $valueSveStartDate['res_id']) {
                        $resources[$key]['sve_start_date'] = $valueSveStartDate['send_date'];
                        break;
                    }
                }
            }
        }

        return $response->withJson(['resources' => $resources, 'count' => count($resources)]);
    }

    public function getProcessingData(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['groupId'])) {
            return $response->withStatus(403)->withJson(['errors' => 'groupId param is not an integer']);
        }
        if (!Validator::intVal()->validate($args['userId'])) {
            return $response->withStatus(403)->withJson(['errors' => 'userId param is not an integer']);
        }
        if (!Validator::intVal()->validate($args['basketId'])) {
            return $response->withStatus(403)->withJson(['errors' => 'basketId param is not an integer']);
        }
        if (!Validator::intVal()->validate($args['resId'])) {
            return $response->withStatus(403)->withJson(['errors' => 'resId param is not an integer']);
        }

        $control = ResourceListController::listControl(['groupId' => $args['groupId'], 'userId' => $args['userId'], 'basketId' => $args['basketId'], 'currentUserId' => $GLOBALS['id']]);
        if (!empty($control['errors'])) {
            return $response->withStatus($control['code'])->withJson(['errors' => $control['errors']]);
        }

        $basket = BasketModel::getById(['id' => $args['basketId'], 'select' => ['basket_id']]);
        $group = GroupModel::getById(['id' => $args['groupId'], 'select' => ['group_id']]);

        $groupBasket = GroupBasketModel::get(['select' => ['list_event_data'], 'where' => ['basket_id = ?', 'group_id = ?'], 'data' => [$basket['basket_id'], $group['group_id']]]);

        if (empty($groupBasket[0]['list_event_data'])) {
            return $response->withJson(['listEventData' => null]);
        }

        $listEventData = json_decode($groupBasket[0]['list_event_data'], true);

        $resource = ResModel::getById(['resId' => $args['resId'], 'select' => ['status']]);
        if (empty($resource['status'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Status does not exist']);
        }
        $status = StatusModel::getById(['id' => $resource['status'], 'select' => ['can_be_modified']]);
        if ($status['can_be_modified'] != 'Y') {
            $listEventData['canUpdate'] = false;
        }

        return $response->withJson(['listEventData' => $listEventData]);
    }

    public function getResourceFileInformation(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) ||  !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $resource = ResModel::getById([
            'resId'  => $args['resId'],
            'select' => ['format', 'fingerprint', 'filesize', 'fulltext_result']
        ]);

        $allowedFiles = StoreController::getAllowedFiles();
        $allowedFiles = array_column($allowedFiles, 'canConvert', 'extension');

        $format = strtoupper($resource['format']);
        $resource['canConvert'] = !empty($allowedFiles[$format]);

        return $response->withJson(['information' => $resource]);
    }

    public static function resetResourceFields(array $args)
    {
        ValidatorModel::notEmpty($args, ['oldFieldList', 'newFieldList']);
        ValidatorModel::arrayType($args, ['oldFieldList', 'newFieldList']);
        ValidatorModel::intVal($args, ['resId', 'modelId']);

        if (empty($args['resId']) && empty($args['modelId'])) {
            return false;
        }

        $oldFieldList = $args['oldFieldList'];
        $newModelFields = $args['newFieldList'];

        // Set res_letterbox fields to null
        $set = [];
        $setToNull = [
            'confidentiality'    => 'confidentiality',
            'admission_date'     => 'arrivalDate',
            'departure_date'     => 'departureDate',
            'doc_date'           => 'documentDate',
            'process_limit_date' => 'processLimitDate',
            'initiator'          => 'initiator',
            'destination'        => 'destination',
            'priority'           => 'priority'
        ];
        foreach ($setToNull as $key => $field) {
            if (in_array($field, $oldFieldList) && !in_array($field, $newModelFields)) {
                $set[$key] = null;
            }
        }

        // Checking if model has a custom field. If yes, the customs are reset in the update, if not, we set it to an empty JSON object
        $newModelHasCustomFields = false;
        foreach ($newModelFields as $newModelField) {
            if (strpos($newModelField, 'indexingCustomField_') !== false) {
                $newModelHasCustomFields = true;
                break;
            }
        }

        $oldModelHasCustomFields = false;
        foreach ($oldFieldList as $oldModelField) {
            if (strpos($oldModelField, 'indexingCustomField_') !== false) {
                $oldModelHasCustomFields = true;
                break;
            }
        }

        $postSet = [];
        if ($oldModelHasCustomFields && !$newModelHasCustomFields) {
            $set['custom_fields'] = '{}';
        } else {
            $customFieldsToDelete = array_diff($oldFieldList, $newModelFields);
            $customFieldsToDelete = array_filter($customFieldsToDelete, function ($field) {
                return strpos($field, 'indexingCustomField_') !== false;
            });
            $customFieldsToDelete = array_map(function ($field) {
                return explode('_', $field)[1];
            }, $customFieldsToDelete);

            $postSet['custom_fields'] = 'custom_fields ';
            foreach ($customFieldsToDelete as $item) {
                $postSet['custom_fields'] .= " - '$item'";
            }
        }

        if (!empty($set) || !empty($postSet)) {
            $where = [];
            $data = [];
            if (!empty($args['resId'])) {
                $where = ['res_id = ?'];
                $data = [$args['resId']];
            } elseif (!empty($args['modelId'])) {
                $where = ['model_id = ?'];
                $data = [$args['modelId']];
            }
            ResModel::update(['set' => $set, 'postSet' => $postSet, 'where' => $where, 'data' => $data]);
        }

        return true;
    }
}
