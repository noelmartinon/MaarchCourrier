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

use AcknowledgementReceipt\models\AcknowledgementReceiptModel;
use Contact\models\ContactModel;
use ContentManagement\controllers\MergeController;
use Convert\controllers\ConvertPdfController;
use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use Doctype\models\DoctypeExtModel;
use Email\controllers\EmailController;
use Entity\models\EntityModel;
use Resource\models\ResModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;
use Template\models\TemplateModel;
use User\models\UserModel;


trait AcknowledgementReceiptTrait
{
    public static function createAcknowledgementReceipts(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $ext = ResModel::getExtById(['select' => ['category_id', 'address_id', 'is_multicontacts'], 'resId' => $aArgs['resId']]);
        if (empty($ext) || $ext['category_id'] != 'incoming') {
            return [];
        }

        $contactsToProcess = [];
        if ($ext['is_multicontacts'] == 'Y') {
            $multiContacts = DatabaseModel::select([
                'select'    => ['address_id'],
                'table'     => ['contacts_res'],
                'where'     => ['res_id = ?', 'mode = ?', 'address_id != ?'],
                'data'      => [$aArgs['resId'], 'multi', 0]
            ]);
            foreach ($multiContacts as $multiContact) {
                $contactsToProcess[] = $multiContact['address_id'];
            }
        } else {
            $contactsToProcess[] = $ext['address_id'];
        }

        foreach ($contactsToProcess as $contactToProcess) {
            if (empty($contactToProcess)) {
                return [];
            }
        }

        $resource = ResModel::getById(['select' => ['type_id', 'destination', 'subject'], 'resId' => $aArgs['resId']]);
        $doctype = DoctypeExtModel::getById(['id' => $resource['type_id'], 'select' => ['process_mode']]);

        if ($doctype['process_mode'] == 'SVA') {
            $templateAttachmentType = 'sva';
        } elseif ($doctype['process_mode'] == 'SVR') {
            $templateAttachmentType = 'svr';
        } else {
            $templateAttachmentType = 'simple';
        }
        $template = TemplateModel::getWithAssociation([
            'select'    => ['template_content', 'template_path', 'template_file_name'],
            'where'     => ['templates.template_id = templates_association.template_id', 'template_target = ?', 'template_attachment_type = ?', 'value_field = ?'],
            'data'      => ['acknowledgementReceipt', $templateAttachmentType, $resource['destination']]
        ]);
        if (empty($template[0])) {
            return [];
        }

        $docserver = DocserverModel::getByDocserverId(['docserverId' => 'TEMPLATES', 'select' => ['path_template']]);
        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $template[0]['template_path']) . $template[0]['template_file_name'];

        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id', 'mail']]);
        $ids = [];
        $errors = [];
        $emailsToSend = [];
        DatabaseModel::beginTransaction();
        foreach ($contactsToProcess as $contactToProcess) {
            $contact = ContactModel::getByAddressId(['addressId' => $contactToProcess, 'select' => ['email', 'address_street', 'address_town', 'address_postal_code']]);

            if (empty($contact['email']) && (empty($contact['address_street']) || empty($contact['address_town']) || empty($contact['address_postal_code']))) {
                DatabaseModel::rollbackTransaction();
                return [];
            }

            if (!empty($contact['email'])) {
                if (empty($template[0]['template_content'])) {
                    DatabaseModel::rollbackTransaction();
                    return [];
                }
                $mergedDocument = MergeController::mergeDocument([
                    'content'   => $template[0]['template_content'],
                    'data'      => ['resId' => $aArgs['resId'], 'contactAddressId' => $contactToProcess, 'userId' => $currentUser['id']]
                ]);
                $format = 'html';
            } else {
                if (!file_exists($pathToDocument) || !is_file($pathToDocument)) {
                    DatabaseModel::rollbackTransaction();
                    return [];
                }
                $mergedDocument = MergeController::mergeDocument([
                    'path'  => $pathToDocument,
                    'data'  => ['resId' => $aArgs['resId'], 'contactAddressId' => $contactToProcess, 'userId' => $currentUser['id']]
                ]);
                $mergedDocument['encodedDocument'] = ConvertPdfController::convertFromEncodedResource(['encodedResource' => $mergedDocument['encodedDocument']]);
                $format = 'pdf';

                if (!empty($mergedDocument['encodedDocument']['errors'])) {
                    DatabaseModel::rollbackTransaction();
                    return [];
                }
            }

            $storeResult = DocserverController::storeResourceOnDocServer([
                'collId'            => 'letterbox_coll',
                'docserverTypeId'   => 'ACKNOWLEDGEMENT_RECEIPTS',
                'encodedResource'   => $mergedDocument['encodedDocument'],
                'format'            => $format
            ]);
            if (!empty($storeResult['errors'])) {
                DatabaseModel::rollbackTransaction();
                $errors[] = $storeResult['errors'];
                return ['errors' => $errors];
            }

            $id = AcknowledgementReceiptModel::create([
                'resId'             => $aArgs['resId'],
                'type'              => $templateAttachmentType,
                'format'            => $format,
                'userId'            => $currentUser['id'],
                'contactAddressId'  => $contactToProcess,
                'docserverId'       => 'ACKNOWLEDGEMENT_RECEIPTS',
                'path'              => $storeResult['directory'],
                'filename'          => $storeResult['file_destination_name'],
                'fingerprint'       => $storeResult['fingerPrint']
            ]);

            if (!empty($contact['email'])) {
                $emailsToSend[] = ['id' => $id, 'email' => $contact['email'], 'encodedHtml' => $mergedDocument['encodedDocument']];
            }
            if ($format == 'pdf') {
                $ids[] = $id;
            }
        }
        DatabaseModel::commitTransaction();

        if (!empty($emailsToSend)) {
            $entity = EntityModel::getByEntityId(['entityId' => $resource['destination'], 'select' => ['email', 'id']]);
        }
        foreach ($emailsToSend as $email) {
            $isSent = EmailController::createEmail([
                'userId'    => $currentUser['id'],
                'data'      => [
                    'sender'        => empty($entity['email']) ? ['email' => $currentUser['mail']] : ['email' => $entity['email'], 'entityId' => $entity['id']],
                    'recipients'    => [$email['email']],
                    'object'        => '[AR] ' . (empty($resource['subject']) ? '' : substr($resource['subject'], 0, 100)),
                    'body'          => base64_decode($email['encodedHtml']),
                    'document'      => ['id' => $aArgs['resId'], 'isLinked' => false, 'original' => true],
                    'isHtml'        => true,
                    'status'        => 'TO_SEND'
                ],
                'options'   => [
                    'acknowledgementReceiptId' => $email['id']
                ]
            ]);

            if (!empty($isSent['errors'])) {
                $errors[] = "Send Email error AR {$email['id']}: {$isSent['errors']}";
            }
        }

        return ['data' => $ids, 'errors' => $errors];
    }
}
