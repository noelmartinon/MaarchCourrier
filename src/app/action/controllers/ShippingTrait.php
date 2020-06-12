<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   AcknowledgementReceiptTrait
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace Action\controllers;

use Attachment\models\AttachmentModel;
use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Convert\controllers\ConvertPdfController;
use Convert\models\AdrModel;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Entity\models\EntityModel;
use Resource\controllers\StoreController;
use Resource\models\ResModel;
use Shipping\controllers\ShippingTemplateController;
use Shipping\models\ShippingModel;
use Shipping\models\ShippingTemplateModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\CurlModel;
use SrcCore\models\PasswordModel;
use SrcCore\models\ValidatorModel;
use User\models\UserModel;


trait ShippingTrait
{
    public static function createMailevaShippings(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);
        ValidatorModel::arrayType($args, ['data']);

        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $resource = ResModel::getById(['select' => ['destination'], 'resId' => $args['resId']]);
        $recipientEntity = EntityModel::getByEntityId(['select' => ['id'], 'entityId' => $resource['destination']]);

        $mailevaConfig = CoreConfigModel::getMailevaConfiguration();
        if (empty($mailevaConfig)) {
            return ['errors' => ['Maileva configuration does not exist']];
        }
        $shippingTemplate = ShippingTemplateModel::getById(['id' => $args['data']['shippingTemplateId']]);
        if (empty($shippingTemplate)) {
            return ['errors' => ['Shipping template does not exist']];
        }
        $shippingTemplate['options'] = json_decode($shippingTemplate['options'], true);
        $shippingTemplate['account'] = json_decode($shippingTemplate['account'], true);
        $shippingTemplate['fee'] = json_decode($shippingTemplate['fee'], true);

        $attachments = AttachmentModel::getOnView([
            'select'    => ['res_id', 'res_id_version', 'title', 'dest_address_id', 'external_id'],
            'where'     => ['res_id_master = ?', 'in_send_attach = ?', 'status not in (?)', 'attachment_type not in (?)'],
            'data'      => [$args['resId'], true, ['OBS', 'DEL', 'TMP', 'FRZ'], ['print_folder']]
        ]);
        if (empty($attachments)) {
            return true;
        }

        $contacts = [];
        foreach ($attachments as $attachment) {
            if (!empty($attachment['res_id'])) {
                $isVersion = false;
                $attachmentId = $attachment['res_id'];
            } else {
                $isVersion = true;
                $attachmentId = $attachment['res_id_version'];
            }

            $convertedDocument = AdrModel::getConvertedDocumentById([
                'select'    => ['docserver_id','path', 'filename', 'fingerprint'],
                'resId'     => $attachmentId,
                'collId'    => 'attachments_coll',
                'type'      => 'PDF',
                'isVersion' => $isVersion
            ]);
            if (empty($convertedDocument)) {
                return ['errors' => ['No conversion for attachment']];
            }
            if (empty($attachment['dest_address_id'])) {
                return ['errors' => ['Contact is empty for attachment']];
            }
            $contact = ContactModel::getOnView(['select' => ['*'], 'where' => ['ca_id = ?'], 'data' => [$attachment['dest_address_id']]]);
            if (empty($contact[0])) {
                return ['errors' => ['Contact does not exist for attachment']];
            }
            if (!empty($contact[0]['address_country']) && strtoupper(trim($contact[0]['address_country'])) != 'FRANCE') {
                return ['errors' => ['Contact country is not France']];
            }
            $afnorAddress = ContactController::getContactAfnor($contact[0]);
            if ((empty($afnorAddress[1]) && empty($afnorAddress[2])) || empty($afnorAddress[6]) || !preg_match("/^\d{5}\s/", $afnorAddress[6])) {
                return ['errors' => ['Contact is not fill enough for attachment']];
            }
            $contacts[] = $afnorAddress;
        }

        $curlAuth = CurlModel::execSimple([
            'url'           => $mailevaConfig['connectionUri'] . '/authentication/oauth2/token',
            'basicAuth'     => ['user' => $mailevaConfig['clientId'], 'password' => $mailevaConfig['clientSecret']],
            'headers'       => ['Content-Type: application/x-www-form-urlencoded'],
            'method'        => 'POST',
            'queryParams'   => [
                'grant_type'    => 'password',
                'username'      => $shippingTemplate['account']['id'],
                'password'      => PasswordModel::decrypt(['cryptedPassword' => $shippingTemplate['account']['password']])
            ]
        ]);
        if ($curlAuth['code'] != 200) {
            return ['errors' => ['Maileva authentication failed']];
        }
        $token = $curlAuth['response']['access_token'];

        $errors = [];
        foreach ($attachments as $key => $attachment) {
            $sendingName = CoreConfigModel::uniqueId();
            if (!empty($attachment['res_id'])) {
                $isVersion = false;
                $attachmentId = $attachment['res_id'];
            } else {
                $isVersion = true;
                $attachmentId = $attachment['res_id_version'];
            }

            $createSending = CurlModel::execSimple([
                'url'           => $mailevaConfig['uri'] . '/mail/v1/sendings',
                'bearerAuth'    => ['token' => $token],
                'headers'       => ['Content-Type: application/json'],
                'method'        => 'POST',
                'body'          => json_encode(['name' => $sendingName])
            ]);
            if ($createSending['code'] != 201) {
                $errors[] = "Maileva sending creation failed for attachment {$attachmentId}";
                continue;
            }
            foreach ($createSending['headers'] as $header) {
                if (strpos($header, 'Location:') !== false) {
                    $sendingId = strrchr($header, '/');
                    $sendingId = substr($sendingId, 1);
                    break;
                }
            }
            if (empty($sendingId)) {
                $errors[] = "Maileva sending id not found for attachment {$attachmentId}";
                continue;
            }

            $convertedDocument = ConvertPdfController::getConvertedPdfById(['resId' => $attachmentId, 'collId' => 'attachments_coll', 'isVersion' => $isVersion]);
            $docserver = DocserverModel::getByDocserverId(['docserverId' => $convertedDocument['docserver_id'], 'select' => ['path_template']]);
            if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
                $errors[] = "Docserver does not exist for attachment {$attachmentId}";
                continue;
            }
            $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $convertedDocument['path']) . $convertedDocument['filename'];
            if (!file_exists($pathToDocument) || !is_file($pathToDocument)) {
                $errors[] = "Document not found on docserver for attachment {$attachmentId}";
                continue;
            }

            $docserverType = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id'], 'select' => ['fingerprint_mode']]);
            $fingerprint = StoreController::getFingerPrint(['filePath' => $pathToDocument, 'mode' => $docserverType['fingerprint_mode']]);
            if ($convertedDocument['fingerprint'] != $fingerprint) {
                $errors[] = "Fingerprints do not match for attachment {$attachmentId}";
                continue;
            }

            $createDocument = CurlModel::execSimple([
                'url'           => $mailevaConfig['uri'] . "/mail/v1/sendings/{$sendingId}/documents",
                'bearerAuth'    => ['token' => $token],
                'method'        => 'POST',
                'multipartBody' => ['document' => file_get_contents($pathToDocument), 'metadata' => json_encode(['priority' => 0, 'name' => $attachment['title']])]
            ]);
            if ($createDocument['code'] != 201) {
                $errors[] = "Maileva document creation failed for attachment {$attachmentId}";
                continue;
            }

            $createRecipient = CurlModel::execSimple([
                'url'           => $mailevaConfig['uri'] . "/mail/v1/sendings/{$sendingId}/recipients",
                'bearerAuth'    => ['token' => $token],
                'headers'       => ['Content-Type: application/json'],
                'method'        => 'POST',
                'body'          => json_encode([
                    "address_line_1"    => $contacts[$key][1],
                    "address_line_2"    => $contacts[$key][2],
                    "address_line_3"    => $contacts[$key][3],
                    "address_line_4"    => $contacts[$key][4],
                    "address_line_5"    => $contacts[$key][5],
                    "address_line_6"    => $contacts[$key][6],
                    "country_code"      => 'FR'
                ]),
            ]);
            if ($createRecipient['code'] != 201) {
                $errors[] = "Maileva recipient creation failed for attachment {$attachmentId}";
                continue;
            }

            $setOptions = CurlModel::execSimple([
                'url'           => $mailevaConfig['uri'] . "/mail/v1/sendings/{$sendingId}/options",
                'bearerAuth'    => ['token' => $token],
                'headers'       => ['Content-Type: application/json'],
                'method'        => 'PATCH',
                'body'          => json_encode([
                    'postage_type'              => strtoupper($shippingTemplate['options']['sendMode']),
                    'color_printing'            => in_array('color', $shippingTemplate['options']['shapingOptions']),
                    'duplex_printing'           => in_array('duplexPrinting', $shippingTemplate['options']['shapingOptions']),
                    'optional_address_sheet'    => in_array('addressPage', $shippingTemplate['options']['shapingOptions'])
                ]),
            ]);
            if ($setOptions['code'] != 200) {
                $errors[] = "Maileva options modification failed for attachment {$attachmentId}";
                continue;
            }

            $submit = CurlModel::execSimple([
                'url'           => $mailevaConfig['uri'] . "/mail/v1/sendings/{$sendingId}/submit",
                'bearerAuth'    => ['token' => $token],
                'headers'       => ['Content-Type: application/json'],
                'method'        => 'POST'
            ]);
            if ($submit['code'] != 200) {
                $errors[] = "Maileva submit failed for attachment {$attachmentId}";
                continue;
            }

            $externalId = json_decode($attachment['external_id'], true);
            $externalId['mailevaSendingId'] = $sendingId;
            AttachmentModel::update(['isVersion' => $isVersion, 'set' => ['external_id' => json_encode($externalId)], 'where' => ['res_id = ?'], 'data' => [$attachmentId]]);

            $fee = ShippingTemplateController::calculShippingFee([
                'fee'       => $shippingTemplate['fee'],
                'resources' => [$attachment]
            ]);

            ShippingModel::create([
                'userId'            => $currentUser['id'],
                'attachmentId'      => $attachmentId,
                'isVersion'         => $isVersion,
                'options'           => json_encode($shippingTemplate['options']),
                'fee'               => $fee,
                'recipientEntityId' => $recipientEntity['id'],
                'accountId'         => $shippingTemplate['account']['id']
            ]);
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return true;
    }
}
