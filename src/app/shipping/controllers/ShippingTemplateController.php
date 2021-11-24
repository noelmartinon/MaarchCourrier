<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ShippingTemplateController
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace Shipping\controllers;

use Convert\controllers\ConvertPdfController;
use Docserver\models\DocserverModel;
use Entity\models\EntityModel;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CurlModel;
use Resource\models\ResModel;
use Resource\controllers\ResController;
use Attachment\models\AttachmentModel;
use Shipping\models\ShippingTemplateModel;
use Shipping\models\ShippingModel;
use Action\models\ActionModel;
use Docserver\controllers\DocserverController;
use Status\models\StatusModel;
use User\models\UserModel;
use SrcCore\controllers\LogsController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\ValidatorModel;

class ShippingTemplateController
{
    public const MAILEVA_EVENT_TYPES = [
        'ON_STATUS_ACCEPTED',
        'ON_STATUS_REJECTED',
        'ON_STATUS_PROCESSED',
        'ON_DEPOSIT_PROOF_RECEIVED',
        'ON_ACKNOWLEDGEMENT_OF_RECEIPT_RECEIVED',
        'ON_STATUS_ARCHIVED'
    ];

    public const MAILEVA_RESOURCE_TYPES = [
        'mail/v2/sendings',
        'registered_mail/v2/sendings',
        'simple_registered_mail/v1/sendings',
        'lel/v2/sendings',
        'lrc/v1/sendings',
        'registered_mail/v2/recipients',
        'simple_registered_mail/v1/recipients'
    ];

    public function get(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_shippings', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        return $response->withJson(['shippings' => ShippingTemplateModel::get(['select' => ['id', 'label', 'description', 'options', 'fee', 'entities', "account->>'id' as accountid"]])]);
    }

    public function getById(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_shippings', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->validate($aArgs['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'id is not an integer']);
        }

        $shippingInfo = ShippingTemplateModel::getById(['id' => $aArgs['id']]);
        if (empty($shippingInfo)) {
            return $response->withStatus(400)->withJson(['errors' => 'Shipping does not exist']);
        }
        
        $shippingInfo['account'] = json_decode($shippingInfo['account'], true);
        $shippingInfo['account']['password'] = '';
        $shippingInfo['options']  = json_decode($shippingInfo['options'], true);
        $shippingInfo['fee']      = json_decode($shippingInfo['fee'], true);
        $shippingInfo['entities'] = json_decode($shippingInfo['entities'], true);
        $subscriptions = json_decode($shippingInfo['subscriptions'], true);
        unset($shippingInfo['subscriptions']);
        $shippingInfo['subscribed'] = !empty($subscriptions);

        $allEntities = EntityModel::get([
            'select'    => ['e1.id', 'e1.entity_id', 'e1.entity_label', 'e2.id as parent_id'],
            'table'     => ['entities e1', 'entities e2'],
            'left_join' => ['e1.parent_entity_id = e2.entity_id'],
            'where'     => ['e1.enabled = ?'],
            'data'      => ['Y']
        ]);

        foreach ($allEntities as $key => $value) {
            $allEntities[$key]['id'] = $value['id'];
            if (empty($value['parent_id'])) {
                $allEntities[$key]['parent'] = '#';
                $allEntities[$key]['icon']   = "fa fa-building";
            } else {
                $allEntities[$key]['parent'] = $value['parent_id'];
                $allEntities[$key]['icon']   = "fa fa-sitemap";
            }
            $allEntities[$key]['state']['opened'] = false;
            $allEntities[$key]['allowed']         = true;
            if (in_array($value['id'], $shippingInfo['entities'])) {
                $allEntities[$key]['state']['opened']   = true;
                $allEntities[$key]['state']['selected'] = true;
            }

            $allEntities[$key]['text'] = $value['entity_label'];
        }

        return $response->withJson(['shipping' => $shippingInfo, 'entities' => $allEntities]);
    }

    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_shippings', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        
        $errors = ShippingTemplateController::checkData($body, 'create');
        if (!empty($errors)) {
            return $response->withStatus(400)->withJson(['errors' => $errors]);
        }

        if (!empty($body['account']['password'])) {
            $body['account']['password'] = PasswordModel::encrypt(['password' => $body['account']['password']]);
        }

        $body['options']  = json_encode($body['options']);
        $body['fee']      = json_encode($body['fee']);
        foreach ($body['entities'] as $key => $entity) {
            $body['entities'][$key] = (string)$entity;
        }
        $body['entities'] = json_encode($body['entities']);
        $body['account']  = json_encode($body['account']);

        $id = ShippingTemplateModel::create([
            'label'       => $body['label'],
            'description' => $body['description'],
            'options'     => $body['options'],
            'fee'         => $body['fee'],
            'entities'    => $body['entities'],
            'account'     => $body['account']
        ]);

        HistoryController::add([
            'tableName' => 'shipping_templates',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'eventId'   => 'shippingadd',
            'info'      => _MAILEVA_ADDED . ' : ' . $body['label']
        ]);

        return $response->withJson(['shippingId' => $id]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_shippings', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();
        $body['id'] = $aArgs['id'];

        $errors = ShippingTemplateController::checkData($body, 'update');
        if (!empty($errors)) {
            return $response->withStatus(500)->withJson(['errors' => $errors]);
        }

        if (!empty($body['account']['password'])) {
            $body['account']['password'] = PasswordModel::encrypt(['password' => $body['account']['password']]);
        } else {
            $shippingInfo = ShippingTemplateModel::getById(['id' => $aArgs['id'], 'select' => ['account']]);
            $shippingInfo['account'] = json_decode($shippingInfo['account'], true);
            $body['account']['password'] = $shippingInfo['account']['password'];
        }
        $shippingInfo = ShippingTemplateModel::getById(['id' => $aArgs['id'], 'select' => ['subscriptions']]);
        $body['subscriptions'] = json_decode($shippingInfo['subscriptions']) ?? [];
        unset($shippingInfo);

        $body['options']  = json_encode($body['options']);
        $body['fee']      = json_encode($body['fee']);
        foreach ($body['entities'] as $key => $entity) {
            $body['entities'][$key] = (string)$entity;
        }
        $body['entities'] = json_encode($body['entities']);
        $body['account']  = json_encode($body['account']);
        if (!!$body['subscribed'] && empty($body['subscriptions'])) {
            $subscriptions = ShippingTemplateController::subscribeToNotifications($body);
            if (!empty($subscriptions['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => $subscriptions['errors']]);
            }
            $body['subscriptions'] = $subscriptions['subscriptions'];
        } elseif (!$body['subscribed']) {
            $subscriptions = ShippingTemplateController::unsubscribeFromNotifications($body);
            if (!empty($subscriptions['errors'])) {
                return $response->withStatus(400)->withJson(['errors' => $subscriptions['errors']]);
            }
            $body['subscriptions'] = $subscriptions['subscriptions'];
        }
        unset($body['subscribed']);
        $body['subscriptions'] = json_encode($body['subscriptions']);

        ShippingTemplateModel::update($body);

        HistoryController::add([
            'tableName' => 'shipping_templates',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'eventId'   => 'shippingup',
            'info'      => _MAILEVA_UPDATED. ' : ' . $body['label']
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function delete(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_shippings', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        if (!Validator::intVal()->validate($aArgs['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'id is not an integer']);
        }

        $shippingInfo = ShippingTemplateModel::getById(['id' => $aArgs['id'], 'select' => ['label']]);
        if (empty($shippingInfo)) {
            return $response->withStatus(400)->withJson(['errors' => 'Shipping does not exist']);
        }

        ShippingTemplateModel::delete(['id' => $aArgs['id']]);

        HistoryController::add([
            'tableName' => 'shipping_templates',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'eventId'   => 'shippingdel',
            'info'      => _MAILEVA_DELETED. ' : ' . $shippingInfo['label']
        ]);

        $shippings = ShippingTemplateModel::get(['select' => ['id', 'label', 'description', 'options', 'fee', 'entities']]);
        return $response->withJson(['shippings' => $shippings]);
    }

    protected static function checkData($aArgs, $mode)
    {
        $errors = [];

        if ($mode == 'update') {
            if (!Validator::intVal()->validate($aArgs['id'])) {
                $errors[] = 'Id is not a numeric';
            } else {
                $shippingInfo = ShippingTemplateModel::getById(['id' => $aArgs['id']]);
            }
            if (empty($shippingInfo)) {
                $errors[] = 'Shipping does not exist';
            }
        } else {
            if (!empty($aArgs['account'])) {
                if (!Validator::notEmpty()->validate($aArgs['account']['id']) || !Validator::notEmpty()->validate($aArgs['account']['password'])) {
                    $errors[] = 'account id or password is empty';
                }
            }
        }
           
        if (!Validator::notEmpty()->validate($aArgs['label']) ||
            !Validator::length(1, 64)->validate($aArgs['label'])) {
            $errors[] = 'label is empty or too long';
        }
        if (!Validator::notEmpty()->validate($aArgs['description']) ||
            !Validator::length(1, 255)->validate($aArgs['description'])) {
            $errors[] = 'description is empty or too long';
        }

        if (!empty($aArgs['entities'])) {
            if (!Validator::arrayType()->validate($aArgs['entities'])) {
                $errors[] = 'entities must be an array';
            }
            foreach ($aArgs['entities'] as $entity) {
                $info = EntityModel::getById(['id' => $entity, 'select' => ['id']]);
                if (empty($info)) {
                    $errors[] = $entity . ' does not exists';
                }
            }
        }

        if (!empty($aArgs['fee'])) {
            foreach ($aArgs['fee'] as $value) {
                if (!empty($value) && !Validator::floatVal()->positive()->validate($value)) {
                    $errors[] = 'fee must be an array with positive values';
                }
            }
        }

        return $errors;
    }

    public function initShipping(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_shippings', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $allEntities = EntityModel::get([
            'select'    => ['e1.id', 'e1.entity_id', 'e1.entity_label', 'e2.id as parent_id'],
            'table'     => ['entities e1', 'entities e2'],
            'left_join' => ['e1.parent_entity_id = e2.entity_id'],
            'where'     => ['e1.enabled = ?'],
            'data'      => ['Y']
        ]);

        foreach ($allEntities as $key => $value) {
            $allEntities[$key]['id'] = (string)$value['id'];
            if (empty($value['parent_id'])) {
                $allEntities[$key]['parent'] = '#';
                $allEntities[$key]['icon']   = "fa fa-building";
            } else {
                $allEntities[$key]['parent'] = (string)$value['parent_id'];
                $allEntities[$key]['icon']   = "fa fa-sitemap";
            }

            $allEntities[$key]['allowed']           = true;
            $allEntities[$key]['state']['opened']   = true;

            $allEntities[$key]['text'] = $value['entity_label'];
        }

        return $response->withJson([
            'entities' => $allEntities,
        ]);
    }

    public static function calculShippingFee(array $aArgs)
    {
        $fee = 0;
        foreach ($aArgs['resources'] as $value) {
            $resourceId = $value['res_id'];

            $collId = $value['type'] == 'attachment' ? 'attachments_coll' : 'letterbox_coll';

            $convertedResource = ConvertPdfController::getConvertedPdfById(['resId' => $resourceId, 'collId' => $collId]);
            $docserver         = DocserverModel::getByDocserverId(['docserverId' => $convertedResource['docserver_id'], 'select' => ['path_template']]);
            $pathToDocument    = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedResource['path']) . $convertedResource['filename'];

            $img = new \Imagick();
            $img->pingImage($pathToDocument);
            $pageCount = $img->getNumberImages();

            $attachmentFee = ($pageCount > 1) ? ($pageCount - 1) * $aArgs['fee']['nextPagePrice'] : 0 ;
            $fee = $fee + $attachmentFee + $aArgs['fee']['firstPagePrice'] + $aArgs['fee']['postagePrice'];
        }

        return $fee;
    }

    private static function subscribeToNotifications(array $shippingTemplate)
    {
        if (empty($shippingTemplate)) {
            return ['errors' => 'shipping template is empty'];
        }
        $mailevaConfig = CoreConfigModel::getMailevaConfiguration();
        if (empty($mailevaConfig)) {
            return ['errors' => 'Maileva configuration does not exist'];
        } elseif (!$mailevaConfig['enabled']) {
            return ['errors' => 'Maileva configuration is disabled'];
        }
        $authToken = ShippingTemplateController::getMailevaAuthToken($mailevaConfig, json_decode($shippingTemplate['account'], true));
        if (!empty($authToken['errors'])) {
            return ['errors' => $authToken['errors']];
        }
        $configFile = CoreConfigModel::getJsonLoaded(['path' => 'apps/maarch_entreprise/xml/config.json']);
        $maarchUrl = $configFile['config']['maarchUrl'] ?? null;
        if (empty($maarchUrl)) {
            return ['errors' => 'maarchUrl is not configured'];
        }
        $subscriptions = [];
        foreach (ShippingTemplateController::MAILEVA_EVENT_TYPES as $eventType) {
            foreach (ShippingTemplateController::MAILEVA_RESOURCE_TYPES as $resourceType) {
                $curlResponse = CurlModel::exec([
                    'method'     => 'POST',
                    'url'        => $mailevaConfig['uri'] . '/subscriptions',
                    'bearerAuth' => ['token' => $authToken],
                    'body'       => json_encode([
                        'event_type'    => $eventType,
                        'resource_type' => $resourceType,
                        'callback_url'  => $maarchUrl . '/rest/administration/shippings/' . $shippingTemplate['id'] . '/notifications'
                    ])
                ]);
                if ($curlResponse['code'] != 201) {
                    return ['errors' => $curlResponse['response']['errors']];
                }

                $subscriptionId = $curlResponse['response']['subscription_id'] ?? null;
                if (!empty($subscriptionId)) {
                    $subscriptions[] = $subscriptionId;
                }
            }
        }
        $subscriptions = array_values(array_unique(array_merge($shippingTemplate['subscriptions'], $subscriptions))) ?? [];

        return ['subscriptions' => $subscriptions];
    }

    private static function unsubscribeFromNotifications(array $shippingTemplate)
    {
        if (empty($shippingTemplate)) {
            return ['errors' => 'shipping template is empty'];
        }
        $mailevaConfig = CoreConfigModel::getMailevaConfiguration();
        if (empty($mailevaConfig)) {
            return ['errors' => 'Maileva configuration does not exist'];
        } elseif (!$mailevaConfig['enabled']) {
            return ['errors' => 'Maileva configuration is disabled'];
        }
        $authToken = ShippingTemplateController::getMailevaAuthToken($mailevaConfig, json_decode($shippingTemplate['account'], true));
        if (!empty($authToken['errors'])) {
            return ['errors' => $authToken['errors']];
        }
        foreach ($shippingTemplate['subscriptions'] as $subscriptionId) {
            $curlResponse = CurlModel::exec([
                'method'     => 'DELETE',
                'url'        => $mailevaConfig['uri'] . '/subscriptions/' . $subscriptionId,
                'bearerAuth' => ['token' => $authToken]
            ]);
            if ($curlResponse['code'] != 204) {
                return ['errors' => $curlResponse['response']['errors']];
            }
        }

        return ['subscriptions' => []];
    }

    public function receiveNotification(Request $request, Response $response)
    {
        $mailevaConfig = CoreConfigModel::getMailevaConfiguration();
        if (empty($mailevaConfig)) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Maileva configuration does not exist');
        } elseif (!$mailevaConfig['enabled']) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Maileva configuration is disabled');
        }
        $shippingApiDomainName = $mailevaConfig['uri'];
        $shippingApiDomainName = str_replace(['http://', 'https://'], '', $shippingApiDomainName);
        $shippingApiDomainName = rtrim($shippingApiDomainName, '/');

        $body = $request->getParsedBody();
        $error = null;
        if (!Validator::equals($shippingApiDomainName)->validate($body['source'])) {
            $error = 'Body source is different from the saved one';
        } elseif (!Validator::stringType()->length(1, 256)->validate($body['user_id'])) {
            $error = 'Body user_id is empty, too long, or not a string';
        } elseif (!Validator::stringType()->length(1, 256)->validate($body['client_id'])) {
            $error = 'Body client_id is empty, too long, or not a string';
        } elseif (!Validator::stringType()->in(ShippingTemplateController::MAILEVA_EVENT_TYPES)->validate($body['event_type'])) {
            $error = 'Body event_type is not an allowed value';
        } elseif (!Validator::stringType()->in(ShippingTemplateController::MAILEVA_RESOURCE_TYPES)->validate($body['resource_type'])) {
            $error = 'Body resource_type is not an allowed value';
        } elseif (!Validator::date()->validate($body['event_date'])) {
            $error = 'Body event_date is not a valid date';
        } elseif (!Validator::equals('FR')->validate($body['event_location'])) {
            $error = 'Body event_location is not FR';
        } elseif (!Validator::stringType()->length(1, 256)->validate($body['resource_id'])) {
            $error = 'Body resource_id is empty, too long, or not a string';
        } elseif (!Validator::url()->validate($body['resource_location'])) {
            $error = 'Body resource_location is not a valid url';
        }
        if (!empty($error)) {
            return ShippingTemplateController::logAndReturnError($response, 400, $error);
        }
        $body = [
            'source'           => $body['source'],
            'userId'           => $body['user_id'],
            'clientId'         => $body['client_id'],
            'eventType'        => $body['event_type'],
            'resourceType'     => $body['resource_type'],
            'resourceId'       => $body['resource_id'],
            'eventDate'        => $body['event_date'],
            'eventLocation'    => $body['event_location'],
            'resourceLocation' => $body['resource_location']
        ];

        $primaryEntity = UserModel::getPrimaryEntityById([
            'id'     => $GLOBALS['id'],
            'select' => ['entities.id']
        ]);
        if (empty($primaryEntity) || !Validator::intType()->validate($primaryEntity['id'])) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'User has no primary entity');
        }
        $shippingTemplates = ShippingTemplateModel::getByEntities([
            'entities' => [(string) $primaryEntity['id']],
            'select'   => ['account']
        ]);
        foreach ($shippingTemplates as $shippingTemplate) {
            $shippingTemplateAccount = json_decode($shippingTemplate['account'], true);
            if (Validator::equals($shippingTemplateAccount['id'])->validate($body['clientId'])) {
                break;
            }
        }
        if (empty($shippingTemplateAccount)) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Body clientId does not match any shipping template for this user');
        }

        if ($body['eventType'] == 'ON_ACKNOWLEDGEMENT_OF_RECEIPT_RECEIVED') {
            $shipping = ShippingModel::getByRecipientId([
                'select'      => ['id', 'sending_id', 'document_id', 'document_type', 'history', 'recipients', 'attachments', 'action_id'],
                'recipientId' => $body['resourceId']
            ]);
            if (empty($shipping[0])) {
                return ShippingTemplateController::logAndReturnError($response, 400, 'Body resource_id does not match any shipping recipient');
            }
            $shipping = $shipping[0];
            $shipping['recipients'] = json_decode($shipping['recipients'], true);
            foreach ($shipping['recipients'] as $recipientValue) {
                if ($recipientValue['recipientId'] == $body['resourceId']) {
                    $recipient = $recipientValue;
                    break;
                }
            }
        } else {
            $shipping = ShippingModel::get([
                'select' => ['id', 'sending_id', 'document_id', 'document_type', 'history', 'recipients', 'attachments', 'action_id'],
                'where'  => ['sending_id = ?'],
                'data'   => [$body['resourceId']]
            ]);
            if (empty($shipping[0])) {
                return ShippingTemplateController::logAndReturnError($response, 400, 'Body resource_id does not match any shipping');
            }
            $shipping = $shipping[0];
            $shipping['recipients'] = json_decode($shipping['recipients'], true);
        }
        $shipping['attachments'] = json_decode($shipping['attachments'], true);
        $shipping['history'] = json_decode($shipping['history'], true);

        $resId = $shipping['document_id'];
        if ($shipping['document_type'] == 'attachment') {
            $referencedAttachment = AttachmentModel::getById([
                'id'     => $shipping['document_id'],
                'select' => ['res_id', 'res_id_master']
            ]);
            if (empty($referencedAttachment)) {
                return ShippingTemplateController::logAndReturnError($response, 400, 'Body document_id does not match any attachment');
            }
            $resId = $referencedAttachment['res_id_master'];
        }
        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $actions = ActionModel::get([
            'select' => ['parameters'],
            'where'  => ['component = ?', 'id = ?'],
            'data'   => ['sendShippingAction', $shipping['action_id']],
            'limit'  => 1
        ]);
        if (empty($actions)) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'No Maileva action available');
        }
        $actionParameters = json_decode($actions[0]['parameters'], true);
        $actionParameters = [
            'intermediateStatus' => $actionParameters['intermediateStatus'] ?? null,
            'errorStatus'        => $actionParameters['errorStatus'] ?? null,
            'finalStatus'        => $actionParameters['finalStatus'] ?? null
        ];
        if (!Validator::each(Validator::arrayType())->validate($actionParameters)) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Maileva action parameters are not arrays');
        } elseif (
                !Validator::stringType()->length(1, 10)->validate($actionParameters['intermediateStatus']['actionStatus'])
                || ($actionParameters['intermediateStatus']['actionStatus'] !== '_NOSTATUS_'
                && empty(StatusModel::getById(['id' => $actionParameters['intermediateStatus']['actionStatus'], 'select' => ['id']])))
                ) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Maileva action actionStatus is invalid for intermediateStatus');
        } elseif (
                !Validator::stringType()->length(1, 10)->validate($actionParameters['errorStatus']['actionStatus'])
                || ($actionParameters['errorStatus']['actionStatus'] !== '_NOSTATUS_'
                && empty(StatusModel::getById(['id' => $actionParameters['errorStatus']['actionStatus'], 'select' => ['id']])))
                ) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Maileva action actionStatus is invalid for errorStatus');
        } elseif (
                !Validator::stringType()->length(1, 10)->validate($actionParameters['finalStatus']['actionStatus'])
                || ($actionParameters['finalStatus']['actionStatus'] !== '_NOSTATUS_'
                && empty(StatusModel::getById(['id' => $actionParameters['finalStatus']['actionStatus'], 'select' => ['id']])))
                ) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Maileva action actionStatus is invalid for finalStatus');
        } elseif (!Validator::each(Validator::in(ShippingTemplateController::MAILEVA_EVENT_TYPES))->validate($actionParameters['intermediateStatus']['mailevaStatus'])) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Maileva action mailevaStatus is invalid for intermediateStatus');
        } elseif (!Validator::each(Validator::in(ShippingTemplateController::MAILEVA_EVENT_TYPES))->validate($actionParameters['errorStatus']['mailevaStatus'])) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Maileva action mailevaStatus is invalid for errorStatus');
        } elseif (!Validator::each(Validator::in(ShippingTemplateController::MAILEVA_EVENT_TYPES))->validate($actionParameters['finalStatus']['mailevaStatus'])) {
            return ShippingTemplateController::logAndReturnError($response, 400, 'Maileva action mailevaStatus is invalid for finalStatus');
        }

        $actionStatus = null;
        foreach ($actionParameters as $phaseStatuses) {
            if (in_array($body['eventType'], $phaseStatuses['mailevaStatus'])) {
                if ($phaseStatuses['actionStatus'] == '_NOSTATUS_') {
                    break;
                }
                $actionStatus = $phaseStatuses['actionStatus'];
                break;
            }
        }

        $shipping['history'][] = [
            'eventType'    => $body['eventType'],
            'eventDate'    => $body['eventDate'],
            'resourceId'   => $body['resourceId'],
            'resourceType' => $body['resourceType'],
            'status'       => $actionStatus
        ];
        ShippingModel::update([
            'set'   => ['history' => json_encode($shipping['history'])],
            'where' => ['id = ?'],
            'data'  => [$shipping['id']]
        ]);

        if ($body['eventType'] == 'ON_DEPOSIT_PROOF_RECEIVED') {
            $authToken = ShippingTemplateController::getMailevaAuthToken($mailevaConfig, $shippingTemplateAccount);
            if (!empty($authToken['errors'])) {
                return ShippingTemplateController::logAndReturnError($response, 400, $authToken['errors']);
            }
            $curlResponse = CurlModel::exec([
                'method'     => 'GET',
                'url'        => $body['resourceLocation'] . '/download_deposit_proof',
                'bearerAuth' => ['token' => $authToken],
                'headers'    => ['Accept: application/zip']
            ]);
            if ($curlResponse['code'] != 200) {
                return ShippingTemplateController::logAndReturnError($response, 400, 'deposit proof failed to download for sending ' . json_encode(['maarchShippingId' => $shipping['id'], 'mailevaSendingId' => $body['resourceId']]));
            }
            // TODO add system attachment type as in summary sheet
            $storage = DocserverController::storeResourceOnDocServer([
                'collId'          => 'attachments_coll',
                'docserverTypeId' => 'DOC',
                'encodedResource' => base64_encode($curlResponse['response']),
                'format'          => 'zip'
            ]);
            if (!empty($storage['errors'])) {
                return ShippingTemplateController::logAndReturnError($response, 500, 'could not save deposit proof to docserver');
            }
            $storage['shipping_attachment_type'] = 'depositProof';
            $storage['resource_type']            = $body['resourceType'];
            $storage['resource_id']              = $body['resourceId'];
            $storage['date']                     = $body['eventDate'];
            $shipping['attachments'][] = $storage;
            ShippingModel::update([
                'set'   => ['attachments' => json_encode($shipping['attachments'])],
                'where' => ['id = ?'],
                'data'  => [$shipping['id']]
            ]);
        }

        if ($body['eventType'] == 'ON_ACKNOWLEDGEMENT_OF_RECEIPT_RECEIVED') {
            if (empty($authToken)) {
                $authToken = ShippingTemplateController::getMailevaAuthToken($mailevaConfig, $shippingTemplateAccount);
                if (!empty($authToken['errors'])) {
                    return ShippingTemplateController::logAndReturnError($response, 400, $authToken['errors']);
                }
            }
            $curlResponse = CurlModel::exec([
                'method'     => 'GET',
                'url'        => $mailevaConfig['uri'] . $recipient['acknowledgement_of_receipt_url'],
                'bearerAuth' => ['token' => $authToken],
                'headers'    => ['Accept: application/zip']
            ]);
            if ($curlResponse['code'] != 200) {
                return ShippingTemplateController::logAndReturnError($response, 400, 'acknowledgement of receipt failed to download for sending ' . json_encode(['maarchShippingId' => $shipping['id'], 'mailevaSendingId' => $body['resourceId'], 'recipientId' => $recipient['id']]));
            }
            $storage = DocserverController::storeResourceOnDocServer([
                'collId'          => 'attachments_coll',
                'docserverTypeId' => 'DOC',
                'encodedResource' => base64_encode($curlResponse['response']),
                'format'          => 'zip'
            ]);
            if (!empty($storage['errors'])) {
                return ShippingTemplateController::logAndReturnError($response, 500, 'could not save acknowledgement of receipt to docserver');
            }
            $storage['shipping_attachment_type'] = 'acknowledgementOfReceipt';
            $storage['resource_type']            = $body['resourceType'];
            $storage['resource_id']              = $body['resourceId'];
            $storage['date']                     = $body['eventDate'];
            $storage['label']                    = trim($recipient['firstname'] . ' ' . $recipient['lastname']);
            if (empty($storage['label'])) {
                $storage['label'] = $recipient['company'];
            } elseif (!empty($recipient['company'])) {
                $storage['label'] .= ' (' . $recipient['company'] . ')';
            }
            $storage['label'] = trim($storage['label']) ?? null;
            $shipping['attachments'][] = $storage;
            ShippingModel::update([
                'set'   => ['attachments' => json_encode($shipping['attachments'])],
                'where' => ['id = ?'],
                'data'  => [$shipping['id']]
            ]);
        }

        ResModel::update([
            'set'   => ['status' => $actionStatus],
            'where' => ['res_id = ?'],
            'data'  => [$resId]
        ]);

        return $response->withStatus(204);
    }

    private static function logAndReturnError(Response $response, int $httpStatusCode, string $error)
    {
        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'shipping',
            'level'     => 'ERROR',
            'tableName' => '',
            'recordId'  => '',
            'eventType' => 'Shipping webhook error: ' . $error,
            'eventId'   => 'Shipping webhook error'
        ]);
        return $response->withStatus($httpStatusCode)->withJson(['errors' => $error]);
    }

    private static function getMailevaAuthToken(array $mailevaConfig, array $shippingTemplateAccount) {
        $curlAuth = CurlModel::exec([
            'url'           => $mailevaConfig['connectionUri'] . '/authentication/oauth2/token',
            'basicAuth'     => ['user' => $mailevaConfig['clientId'], 'password' => $mailevaConfig['clientSecret']],
            'headers'       => ['Content-Type: application/x-www-form-urlencoded'],
            'method'        => 'POST',
            'queryParams'   => [
                'grant_type'    => 'password',
                'username'      => $shippingTemplateAccount['id'],
                'password'      => PasswordModel::decrypt(['cryptedPassword' => $shippingTemplateAccount['password']])
            ]
        ]);
        if ($curlAuth['code'] != 200) {
            return ['errors' => 'Maileva authentication failed'];
        }
        $token = $curlAuth['response']['access_token'];
        return $token;
    }
}
