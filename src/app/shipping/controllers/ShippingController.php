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
use Slim\Http\Request;
use Slim\Http\Response;
use User\models\UserModel;
use Docserver\models\DocserverModel;

class ShippingController
{
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

    public function getShippingAttachmentsList(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['shippingId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route shippingId is not an integer']);
        }
        $shipping = ShippingModel::get([
            'select' => ['id', 'document_id', 'document_type', 'attachments'],
            'where'  => ['id = ?'],
            'data'   => [$args['shippingId']]
        ]);
        if (empty($shipping[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'No shipping with this id']);
        }
        $shipping = $shipping[0];
        $shipping['attachments'] = json_decode($shipping['attachments'], true);

        $resId = $shipping['document_id'];
        if ($shipping['document_type'] == 'attachment') {
            $referencedAttachment = AttachmentModel::getById([
                'id'     => $shipping['document_id'],
                'select' => ['res_id', 'res_id_master']
            ]);
            if (empty($referencedAttachment)) {
                return $response->withStatus(400)->withJson(['No attachment with this id']);
            }
            $resId = $referencedAttachment['res_id_master'];
        }
        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $attachments = [];
        foreach ($shipping['attachments'] as $key => $attachment) {
            $attachments[] = [
                'id'             => $key,
                'attachmentType' => $attachment['shipping_attachment_type'] ?? null,
                'resourceType'   => $attachment['resource_type'] ?? null,
                'resourceId'     => $attachment['resource_id'] ?? null,
                'label'          => $attachment['label'] ?? null,
                'date'           => $attachment['date'] ?? null
            ];
        }
        return $response->withJson(['attachments' => $attachments]);
    }

    // voir si possible de supprimer cette route au profit de GET/attachments/{id}
    public function getShippingAttachment(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['shippingId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route shippingId is not an integer']);
        }
        $shipping = ShippingModel::get([
            'select' => ['id', 'document_id', 'document_type', 'attachments'],
            'where'  => ['id = ?'],
            'data'   => [$args['shippingId']]
        ]);
        if (empty($shipping[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'No shipping with this id']);
        }
        $shipping = $shipping[0];
        $shipping['attachments'] = json_decode($shipping['attachments'], true);

        $resId = $shipping['document_id'];
        if ($shipping['document_type'] == 'attachment') {
            $referencedAttachment = AttachmentModel::getById([
                'id'     => $shipping['document_id'],
                'select' => ['res_id', 'res_id_master']
            ]);
            if (empty($referencedAttachment)) {
                return $response->withStatus(400)->withJson(['No attachment with this id']);
            }
            $resId = $referencedAttachment['res_id_master'];
        }
        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        if (empty($shipping['attachments'][$args['attachmentId']])) {
            return $response->withStatus(400)->withJson(['errors' => 'No shipping attachment with this id']);
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
            return $response->withStatus(400)->withJson(['errors' => 'File does not exist or is unreadable']);
        }
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        $response->write($fileContent);
        $response = $response->withAddedheader('Content-Type', $mimeType);

        return $response->withHeader('Content-Disposition', 'attachment; filename=' . $filename);
    }

    public function getHistory(Request $request, Response $response, array $args) {
        $shipping = ShippingModel::get([
            'select' => ['id', 'document_id', 'document_type', 'history'],
            'where'  => ['id = ?'],
            'data'   => [$args['shippingId']]
        ]);
        if (empty($shipping[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'No shipping with this id']);
        }
        $shipping = $shipping[0];
        $shipping['history'] = json_decode($shipping['history'], true);

        $resId = $shipping['document_id'];
        if ($shipping['document_type'] == 'attachment') {
            $referencedAttachment = AttachmentModel::getById([
                'id'     => $shipping['document_id'],
                'select' => ['res_id', 'res_id_master']
            ]);
            if (empty($referencedAttachment)) {
                return $response->withStatus(400)->withJson(['No attachment with this id']);
            }
            $resId = $referencedAttachment['res_id_master'];
        }
        if (!ResController::hasRightByResId(['resId' => [$resId], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        return $response->withJson(['history' => $shipping['history']]);
    }
}
