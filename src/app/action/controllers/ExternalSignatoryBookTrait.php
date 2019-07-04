<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   ExternalSignatoryBookTrait
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace Action\controllers;

use Attachment\models\AttachmentModel;
use ExternalSignatoryBook\controllers\MaarchParapheurController;
use ExternalSignatoryBook\controllers\XParaphController;
use Resource\models\ResModel;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;

trait ExternalSignatoryBookTrait
{
    public static function sendExternalSignatoryBookAction(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'modules/visa/xml/remoteSignatoryBooks.xml']);
        $config = [];

        if (!empty($loadedXml)) {
            $config['id'] = (string)$loadedXml->signatoryBookEnabled;
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == $config['id']) {
                    $config['data'] = (array)$value;
                    break;
                }
            }

            if ($config['id'] == 'ixbus') {
                // TODO
            } elseif ($config['id'] == 'iParapheur') {
                // TODO
            } elseif ($config['id'] == 'fastParapheur') {
                // TODO
            } elseif ($config['id'] == 'maarchParapheur') {
                $attachments = AttachmentModel::getOnView([
                    'select'    => [
                        'count(1) as nb'
                    ],
                    'where'     => ["res_id_master = ?", "attachment_type not in (?)", "status not in ('DEL', 'OBS', 'FRZ', 'TMP', 'SEND_MASS')", "in_signature_book = 'true'"],
                    'data'      => [$args['resId'], ['converted_pdf', 'print_folder', 'signed_response']]
                ]);
                if ($attachments[0]['nb'] == 0 && $args['data']['objectSent'] == 'attachment') {
                    $noAttachmentsResource = ResModel::getExtById(['resId' => $args['resId'], 'select' => ['alt_identifier']]);
                    return ['errors' => ['No attachment for this mail : ' . $noAttachmentsResource['alt_identifier']]];
                }

                $sendedInfo = MaarchParapheurController::sendDatas([
                    'config'      => $config,
                    'resIdMaster' => $args['resId'],
                    'objectSent'  => 'attachment',
                    'userId'      => $GLOBALS['userId'],
                    'steps'       => $args['data']['steps'],
                ]);
                if (!empty($sendedInfo['error'])) {
                    return ['errors' => [$sendedInfo['error']]];
                } else {
                    $attachmentToFreeze = $sendedInfo['sended'];
                }

                $historyInfo = $sendedInfo['historyInfos'];
            } elseif ($config['id'] == 'xParaph') {
                $attachments = AttachmentModel::getOnView([
                    'select'    => [
                        'count(1) as nb'
                    ],
                    'where'     => ["res_id_master = ?", "attachment_type not in (?)", "status not in ('DEL', 'OBS', 'FRZ', 'TMP', 'SEND_MASS')", "in_signature_book = 'true'"],
                    'data'      => [$args['resId'], ['converted_pdf', 'print_folder', 'signed_response']]
                ]);
                if ($attachments[0]['nb'] == 0) {
                    $noAttachmentsResource = ResModel::getExtById(['resId' => $args['resId'], 'select' => ['alt_identifier']]);
                    return ['errors' => ['No attachment for this mail : ' . $noAttachmentsResource['alt_identifier']]];
                }

                $sendedInfo = XParaphController::sendDatas([
                    'config'      => $config,
                    'resIdMaster' => $args['resId'],
                    'info'        => $args['data']['info'],
                    'steps'       => $args['data']['steps'],
                ]);
                if (!empty($sendedInfo['error'])) {
                    return ['errors' => [$sendedInfo['error']]];
                } else {
                    $attachmentToFreeze = $sendedInfo['sended'];
                }
            }
        }

        if (!empty($attachmentToFreeze)) {
            if (!empty($attachmentToFreeze['letterbox_coll'])) {
                ResModel::update([
                    'set' => ['external_signatory_book_id' => $attachmentToFreeze['letterbox_coll'][$args['resId']]],
                    'where' => ['res_id = ?'],
                    'data' => [$args['resId']]
                ]);
            } else {
                if (!empty($attachmentToFreeze['attachments_coll'])) {
                    foreach ($attachmentToFreeze['attachments_coll'] as $resId => $externalId) {
                        AttachmentModel::freezeAttachment([
                            'resId' => $resId,
                            'table' => 'res_attachments',
                            'externalId' => $externalId
                        ]);
                    }
                }
                if (!empty($attachmentToFreeze['attachments_version_coll'])) {
                    foreach ($attachmentToFreeze['attachments_version_coll'] as $resId => $externalId) {
                        AttachmentModel::freezeAttachment([
                            'resId' => $resId,
                            'table' => 'res_version_attachments',
                            'externalId' => $externalId
                        ]);
                    }
                }
            }
        }

        return ['history' => $historyInfo];
    }
}
