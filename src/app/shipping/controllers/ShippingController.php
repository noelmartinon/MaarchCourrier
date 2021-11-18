<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Shipping Controller
* @author dev@maarch.org
*/

namespace Shipping\controllers;

use Attachment\models\AttachmentModel;
use Entity\models\EntityModel;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Shipping\models\ShippingModel;
use Shipping\models\ShippingTemplateModel;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\LogsController;
use User\models\UserModel;
use Action\models\ActionModel;
use Docserver\controllers\DocserverController;
use Status\models\StatusModel;
use Docserver\models\DocserverModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\PasswordModel;

class ShippingController
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

    public function getByResId(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

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

        $shippingsModel = ShippingModel::get([
            'select' => ['*'],
            'where'  => [$where],
            'data'   => $data
        ]);

        $shippings = [];

        foreach ($shippingsModel as $shipping) {
            $recipientEntityLabel = EntityModel::getById(['id' => $shipping['recipient_entity_id'], 'select' => ['entity_label']]);
            $recipientEntityLabel = $recipientEntityLabel['entity_label'];
            $recipients = json_decode($shipping['recipients'], true);
            $contacts = [];
            foreach ($recipients as $recipient) {
                $contacts[] = ['company' => $recipient[1], 'contactLabel' => $recipient[2]];
            }

            $shippings[] = [
                'id'                    => $shipping['id'],
                'documentId'            => $shipping['document_id'],
                'documentType'          => $shipping['document_type'],
                'userId'                => $shipping['user_id'],
                'userLabel'             => UserModel::getLabelledUserById(['id' => $shipping['user_id']]),
                'fee'                   => $shipping['fee'],
                'creationDate'          => $shipping['creation_date'],
                'recipientEntityId'     => $shipping['recipient_entity_id'],
                'recipientEntityLabel'  => $recipientEntityLabel,
                'recipients'            => $contacts
            ];
        }

        return $response->withJson($shippings);
    }

    public function receiveNotification(Request $request, Response $response, array $args)
    {
        $mailevaConfig = CoreConfigModel::getMailevaConfiguration();
        $error = null;
        if (empty($mailevaConfig)) {
            return ShippingController::logAndReturnError($response, 400, 'Maileva configuration does not exist');
        } elseif (!$mailevaConfig['enabled']) {
            return ShippingController::logAndReturnError($response, 400, 'Maileva configuration is disabled');
        }
        $shippingApiDomainName = $mailevaConfig['uri'];
        $shippingApiDomainName = str_replace(['http://', 'https://'], '', $shippingApiDomainName);
        $shippingApiDomainName = rtrim($shippingApiDomainName, '/');

        $actions = ActionModel::get([
            'select' => ['parameters'],
            'where'  => ['component = ?'],
            'data'   => ['sendShippingAction'],
            'limit'  => 1
        ]);
        if (empty($actions)) {
            return ShippingController::logAndReturnError($response, 400, 'No Maileva action available');
        }
        $actionParameters = json_decode($actions[0]['parameters'], true);
        $actionParameters = [
            'intermediateStatus' => $actionParameters['intermediateStatus'] ?? null,
            'errorStatus'        => $actionParameters['errorStatus'] ?? null,
            'finalStatus'        => $actionParameters['finalStatus'] ?? null
        ];
        if (!Validator::each(Validator::arrayType())->validate($actionParameters)) {
            return ShippingController::logAndReturnError($response, 400, 'Maileva action parameters are not arrays');
        } elseif (
                !Validator::stringType()->length(1, 10)->validate($actionParameters['intermediateStatus']['actionStatus'])
                || ($actionParameters['intermediateStatus']['actionStatus'] !== '_NOSTATUS_'
                && empty(StatusModel::getById(['id' => $actionParameters['intermediateStatus']['actionStatus'], 'select' => ['id']])))
                ) {
            return ShippingController::logAndReturnError($response, 400, 'Maileva action actionStatus is invalid for intermediateStatus');
        } elseif (
                !Validator::stringType()->length(1, 10)->validate($actionParameters['errorStatus']['actionStatus'])
                || ($actionParameters['errorStatus']['actionStatus'] !== '_NOSTATUS_'
                && empty(StatusModel::getById(['id' => $actionParameters['errorStatus']['actionStatus'], 'select' => ['id']])))
                ) {
            return ShippingController::logAndReturnError($response, 400, 'Maileva action actionStatus is invalid for errorStatus');
        } elseif (
                !Validator::stringType()->length(1, 10)->validate($actionParameters['finalStatus']['actionStatus'])
                || ($actionParameters['finalStatus']['actionStatus'] !== '_NOSTATUS_'
                && empty(StatusModel::getById(['id' => $actionParameters['finalStatus']['actionStatus'], 'select' => ['id']])))
                ) {
            return ShippingController::logAndReturnError($response, 400, 'Maileva action actionStatus is invalid for finalStatus');
        } elseif (!Validator::each(Validator::in(ShippingController::MAILEVA_EVENT_TYPES))->validate($actionParameters['intermediateStatus']['mailevaStatus'])) {
            return ShippingController::logAndReturnError($response, 400, 'Maileva action mailevaStatus is invalid for intermediateStatus');
        } elseif (!Validator::each(Validator::in(ShippingController::MAILEVA_EVENT_TYPES))->validate($actionParameters['errorStatus']['mailevaStatus'])) {
            return ShippingController::logAndReturnError($response, 400, 'Maileva action mailevaStatus is invalid for errorStatus');
        } elseif (!Validator::each(Validator::in(ShippingController::MAILEVA_EVENT_TYPES))->validate($actionParameters['finalStatus']['mailevaStatus'])) {
            return ShippingController::logAndReturnError($response, 400, 'Maileva action mailevaStatus is invalid for finalStatus');
        }

        $body = $request->getParsedBody();
        $error = null;
        if (!Validator::equals($shippingApiDomainName)->validate($body['source'])) {
            $error = 'Body source is different from the saved one';
        } elseif (!Validator::stringType()->length(1, 256)->validate($body['user_id'])) {
            $error = 'Body user_id is empty, too long, or not a string';
        } elseif (!Validator::stringType()->length(1, 256)->validate($body['client_id'])) {
            $error = 'Body client_id is empty, too long, or not a string';
        } elseif (!Validator::stringType()->in(ShippingController::MAILEVA_EVENT_TYPES)->validate($body['event_type'])) {
            $error = 'Body event_type is not an allowed value';
        } elseif (!Validator::stringType()->in(ShippingController::MAILEVA_RESOURCE_TYPES)->validate($body['resource_type'])) {
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
            return ShippingController::logAndReturnError($response, 400, $error);
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
            return ShippingController::logAndReturnError($response, 400, 'User has no primary entity');
        }
        $shippingTemplates = ShippingTemplateModel::getByEntities([
            'entities' => [(string) $primaryEntity['id']],
            'select'   => ['account']
        ]);
        $noMatchingTemplate = true;
        foreach ($shippingTemplates as $shippingTemplate) {
            $shippingTemplateAccount = json_decode($shippingTemplate['account'], true);
            if (Validator::equals($shippingTemplateAccount['id'])->validate($body['clientId'])) {
                $noMatchingTemplate = false;
                break;
            }
        }
        if ($noMatchingTemplate) {
            return ShippingController::logAndReturnError($response, 400, 'Body clientId does not match any shipping template for this user');
        }

        if ($body['eventType'] == 'ON_ACKNOWLEDGEMENT_OF_RECEIPT_RECEIVED') {
            $shipping = ShippingModel::getByRecipientId([
                'select'      => ['id', 'sending_id', 'document_id', 'history', 'recipients', 'attachments'],
                'recipientId' => $body['resourceId']
            ]);
            if (empty($shipping[0])) {
                return ShippingController::logAndReturnError($response, 400, 'Body resource_id does not match any shipping recipient');
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
                'select' => ['id', 'sending_id', 'document_id', 'history', 'recipients', 'attachments'],
                'where'  => ['sending_id = ?'],
                'data'   => [$body['resourceId']]
            ]);
            if (empty($shipping[0])) {
                return ShippingController::logAndReturnError($response, 400, 'Body resource_id does not match any shipping');
            }
            $shipping = $shipping[0];
            $shipping['recipients'] = json_decode($shipping['recipients'], true);
        }
        $resId = $shipping['document_id'];
        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['id']])) {
            return ShippingController::logAndReturnError($response, 403, 'Document out of perimeter');
        }
        $shipping['attachments'] = json_decode($shipping['attachments'], true);
        $shipping['history'] = json_decode($shipping['history'], true);

        $actionStatus = null;
        foreach ($actionParameters as $phaseStatuses) {
            if (in_array($body['eventType'], $phaseStatuses['mailevaStatus'])) {
                if ($phaseStatuses['actionStatus'] == '_NOSTATUS_') {
                    break;
                }
                $actionStatus = $phaseStatuses['actionStatus'];
                ResModel::update([
                    'set'   => ['status' => $actionStatus],
                    'where' => ['res_id = ?'],
                    'data'  => [$resId]
                ]);
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
            $authToken = ShippingController::getMailevaAuthToken($mailevaConfig, $shippingTemplateAccount);
            if (!empty($authToken['errors'])) {
                return ShippingController::logAndReturnError($response, 400, $authToken['errors']);
            }
            $curlResponse = CurlModel::exec([
                'method'     => 'GET',
                'url'        => $body['resourceLocation'] . '/download_deposit_proof',
                'bearerAuth' => ['token' => $authToken],
                'headers'    => ['Accept: application/zip']
            ]);
            if ($curlResponse['code'] < 200 || $curlResponse['code'] >= 300) {
                return ShippingController::logAndReturnError($response, 400, 'deposit proof failed to download for sending ' . json_encode(['maarchShippingId' => $shipping['id'], 'mailevaSendingId' => $body['resourceId']]));
            }
            $storage = DocserverController::storeResourceOnDocServer([
                'collId'          => 'attachments_coll',
                'docserverTypeId' => 'DOC',
                'encodedResource' => base64_encode($curlResponse['response']),
                'format'          => 'zip'
            ]);
            if (!empty($storage['errors'])) {
                return ShippingController::logAndReturnError($response, 500, 'could not save deposit proof to docserver');
            }
            $storage['shipping_attachment_type'] = 'DEPOSIT_PROOF';
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
                $authToken = ShippingController::getMailevaAuthToken($mailevaConfig, $shippingTemplateAccount);
                if (!empty($authToken['errors'])) {
                    return ShippingController::logAndReturnError($response, 400, $authToken['errors']);
                }
            }
            $curlResponse = CurlModel::exec([
                'method'     => 'GET',
                'url'        => $mailevaConfig['uri'] . $recipient['acknowledgement_of_receipt_url'],
                'bearerAuth' => ['token' => $authToken],
                'headers'    => ['Accept: application/zip']
            ]);
            if ($curlResponse['code'] < 200 || $curlResponse['code'] >= 300) {
                return ShippingController::logAndReturnError($response, 400, 'acknowledgement of receipt failed to download for sending ' . json_encode(['maarchShippingId' => $shipping['id'], 'mailevaSendingId' => $body['resourceId'], 'recipientId' => $recipient['id']]));
            }
            $storage = DocserverController::storeResourceOnDocServer([
                'collId'          => 'attachments_coll',
                'docserverTypeId' => 'DOC',
                'encodedResource' => base64_encode($curlResponse['response']),
                'format'          => 'zip'
            ]);
            if (!empty($storage['errors'])) {
                return ShippingController::logAndReturnError($response, 500, 'could not save acknowledgement of receipt to docserver');
            }
            $storage['shipping_attachment_type'] = 'ACKNOWLEDGEMENT_OF_RECEIPT';
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

        return $response->withStatus(204);
    }

    public function getShippingAttachmentsList(Request $request, Response $response, array $args)
    {
        $shipping = ShippingModel::get([
            'select' => ['id', 'document_id', 'attachments'],
            'where'  => ['id = ?'],
            'data'   => [$args['shippingId']]
        ]);
        if (empty($shipping[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'no shipping with this id']);
        }
        $shipping = $shipping[0];
        $shipping['attachments'] = json_decode($shipping['attachments'], true);

        if (!ResController::hasRightByResId(['resId' => [$shipping['document_id']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $attachments = [];
        foreach ($shipping['attachments'] as $key => $attachment) {
            if (empty($attachments[$attachment['shipping_attachment_type']])) {
                $attachments[$attachment['shipping_attachment_type']] = [];
            }
            $attachments[$attachment['shipping_attachment_type']][] = [
                'id'           => $key,
                'resourceType' => $attachment['resource_type'] ?? null,
                'resourceId'   => $attachment['resource_id'] ?? null,
                'label'        => $attachment['label'] ?? null,
                'date'         => $attachment['date'] ?? null
            ];
        }
        return $response->withStatus(200)->withJson(['attachments' => $attachments]);
    }

    public function getShippingAttachment(Request $request, Response $response, array $args)
    {
        $shipping = ShippingModel::get([
            'select' => ['id', 'document_id', 'attachments'],
            'where'  => ['id = ?'],
            'data'   => [$args['shippingId']]
        ]);
        if (empty($shipping[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'no shipping with this id']);
        }
        $shipping = $shipping[0];
        $shipping['attachments'] = json_decode($shipping['attachments'], true);

        if (!ResController::hasRightByResId(['resId' => [$shipping['document_id']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        if (empty($shipping['attachments'][$args['attachmentId']])) {
            return $response->withStatus(400)->withJson(['errors' => 'no shipping attachment with this id']);
        }

        $attachment = $shipping['attachments'][$args['attachmentId']];

        $docserver = DocserverModel::get([
            'select' => ['path_template'],
            'where'  => ['docserver_id = ?'],
            'data'   => [$attachment['docserver_id']]
        ]);
        if (empty($docserver[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'Docserver not found']);
        }
        $docserver = $docserver[0];
        $filepath = $docserver['path_template'] . $attachment['directory'] . $attachment['file_destination_name'];
        $extension = explode('.', $filepath);
        $extension = array_pop($extension);
        $filename = $attachment['shipping_attachment_type'] . '_' . $shipping['id'] . '_' . $args['attachmentId'] . '.' . $extension;

        $fileContent = file_get_contents($filepath);
        if (empty($fileContent)) {
            return $response->withStatus(400)->withJson(['errors' => 'file does not exist or is unreadable']);
        }
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $response->write($fileContent);
        $response = $response->withAddedheader('Content-Type', $mimeType);

        return $response->withStatus(200)->withHeader('Content-Disposition', 'attachment; filename=' . $filename);
    }

    public function getHistory(Request $request, Response $response, array $args) {
        $shipping = ShippingModel::get([
            'select' => ['id', 'document_id', 'history'],
            'where'  => ['id = ?'],
            'data'   => [$args['shippingId']]
        ]);
        if (empty($shipping[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'no shipping with this id']);
        }
        $shipping = $shipping[0];
        $shipping['history'] = json_decode($shipping['history'], true);

        if (!ResController::hasRightByResId(['resId' => [$shipping['document_id']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        return $response->withStatus(200)->withJson($shipping['history']);
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
