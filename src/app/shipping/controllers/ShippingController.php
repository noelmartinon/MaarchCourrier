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
use Respect\Validation\Validator;
use Shipping\models\ShippingModel;
use Shipping\models\ShippingTemplateModel;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\LogsController;
use User\models\UserModel;
use SrcCore\models\CoreConfigModel;

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
        'lrc/v1/sendings'
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
        // get maileva config
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

        // get and validate body
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
        } elseif (!Validator::intVal()->notEmpty()->validate($body['resource_custom_id'])) {
            $error = 'Body resource_custom_id is empty or not an integer';
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
            'eventDate'        => $body['event_date'],
            'eventLocation'    => $body['event_location'],
            'resourceId'       => $body['resource_id'],
            'resourceLocation' => $body['resource_location'],
            'resourceCustomId' => $body['resource_custom_id']
        ];

        // check clientId
        $primaryEntity = UserModel::getPrimaryEntityById([
            'id'     => $GLOBALS['id'],
            'select' => ['entities.id']
        ]);
        if (empty($primaryEntity) || !Validator::intType()->validate($primaryEntity['id'])) {
            return ShippingController::logAndReturnError($response, 400, 'User has no primary entity');
        }
        $shippingTemplates = ShippingTemplateModel::getByEntities([
            'entities' => [(string) $primaryEntity['id']],
            'select'   => ["account->>'id' as account_id"]
        ]);
        $noMatchingTemplate = true;
        foreach ($shippingTemplates as $shippingTemplate) {
            if (Validator::equals($shippingTemplate['account_id'])->validate($body['clientId'])) {
                $noMatchingTemplate = false;
                break;
            }
        }
        if ($noMatchingTemplate) {
            return ShippingController::logAndReturnError($response, 400, 'Body clientId does not match any shipping template for this user');
        }

        // identify resource and check permissions
        $resId = $body['resourceCustomId'];
        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['id']])) {
            return ShippingController::logAndReturnError($response, 403, 'Document out of perimeter');
        }

        return $response->withStatus(418);
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
}
