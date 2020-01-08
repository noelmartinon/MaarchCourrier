<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
 * @brief Attachment Model Abstract
 * @author dev@maarch.org
 */

namespace Attachment\models;

use SrcCore\models\CoreConfigModel;
use SrcCore\models\DatabaseModel;
use SrcCore\models\ValidatorModel;

abstract class AttachmentModelAbstract
{
    public static function getOnView(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['select']);
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data', 'orderBy', 'groupBy']);
        ValidatorModel::intType($aArgs, ['limit']);

        $aAttachments = DatabaseModel::select([
            'select'    => $aArgs['select'],
            'table'     => ['res_view_attachments'],
            'where'     => empty($aArgs['where']) ? [] : $aArgs['where'],
            'data'      => empty($aArgs['data']) ? [] : $aArgs['data'],
            'groupBy'   => empty($aArgs['groupBy']) ? [] : $aArgs['groupBy'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
            'limit'     => empty($aArgs['limit']) ? 0 : $aArgs['limit']
        ]);

        return $aAttachments;
    }

    public static function getById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::boolType($aArgs, ['isVersion']);
        ValidatorModel::arrayType($aArgs, ['select']);

        if (!empty($aArgs['isVersion'])) {
            $table = 'res_version_attachments';
        } else {
            $table = 'res_attachments';
        }

        $aAttachment = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => [$table],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        if (empty($aAttachment[0])) {
            return [];
        }

        return $aAttachment[0];
    }

    public static function getAttachmentToSend(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['ids']);
        ValidatorModel::arrayType($aArgs, ['select', 'orderBy', 'ids']);

        $aAttachments = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['max(relation) as relation', 'res_id_master', 'title', 'res_id', 'res_id_version', 'identifier', 'dest_address_id'] : $aArgs['select'],
            'table'     => ['res_view_attachments'],
            'where'     => ['res_id_master in (?)', 'status not in (?)', 'attachment_type not in (?)', 'in_send_attach = TRUE'],
            'data'      => [$aArgs['ids'], ['OBS', 'DEL', 'TMP', 'FRZ'], 'print_folder'],
            'groupBy'   => ['res_id_master', 'title', 'res_id', 'res_id_version', 'identifier', 'dest_address_id'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy']
        ]);

        return $aAttachments;
    }

    public static function getListByResIdMaster(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['resId', 'login']);
        ValidatorModel::intVal($aArgs, ['resId']);
        ValidatorModel::stringType($aArgs, ['login']);
        ValidatorModel::arrayType($aArgs, ['excludeAttachmentTypes']);

        $aAttachments = DatabaseModel::select([
            'select'    => [
                'res_id', 'res_id_version', 'res_view_attachments.identifier', 'title', 'format', 'creation_date',
                'doc_date as update_date', 'validation_date as return_date', 'effective_date as real_return_date',
                'u.firstname as firstname_updated', 'u.lastname as lastname_updated', 'relation', 'docserver_id', 'path',
                'filename', 'fingerprint', 'filesize', 'label_status as status', 'res_view_attachments.status as status_id', 'attachment_type', 'dest_contact_id',
                'dest_address_id', 'ut.firstname as firstname_typist', 'ut.lastname as lastname_typist', 'in_signature_book', 'in_send_attach'
            ],
            'table'     => ['res_view_attachments', 'users ut', 'status', 'users u'],
            'left_join' => ['res_view_attachments.typist = ut.user_id', 'res_view_attachments.status = status.id', 'res_view_attachments.updated_by = u.user_id'],
            'where'     => ['res_id_master = ?', 'res_view_attachments.status not in (?)', 'attachment_type not in (?)', '((res_view_attachments.status = ? AND typist = ?) OR res_view_attachments.status != ?)'],
            'data'      => [$aArgs['resId'], ['OBS', 'DEL'], $aArgs['excludeAttachmentTypes'], 'TMP', $aArgs['login'], 'TMP'],
            'order_by'  => empty($aArgs['orderBy']) ? [] : $aArgs['orderBy'],
        ]);

        return $aAttachments;
    }

    public static function create(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['format', 'typist', 'creation_date', 'docserver_id', 'path', 'filename', 'fingerprint', 'filesize', 'status']);
        ValidatorModel::stringType($aArgs, ['format', 'typist', 'creation_date', 'docserver_id', 'path', 'filename', 'fingerprint', 'status']);
        ValidatorModel::intVal($aArgs, ['filesize']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'res_attachment_res_id_seq']);
        $aArgs['res_id'] = $nextSequenceId;

        DatabaseModel::insert([
            'table'         => 'res_attachments',
            'columnsValues' => $aArgs
        ]);

        return $nextSequenceId;
    }

    public static function createVersion(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['format', 'typist', 'creation_date', 'docserver_id', 'path', 'filename', 'fingerprint', 'filesize', 'status']);
        ValidatorModel::stringType($aArgs, ['format', 'typist', 'creation_date', 'docserver_id', 'path', 'filename', 'fingerprint', 'status']);
        ValidatorModel::intVal($aArgs, ['filesize']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'res_id_version_attachments_seq']);
        $aArgs['res_id'] = $nextSequenceId;

        DatabaseModel::insert([
            'table'         => 'res_version_attachments',
            'columnsValues' => $aArgs
        ]);

        return $nextSequenceId;
    }

    public static function update(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['set', 'where', 'data']);
        ValidatorModel::arrayType($aArgs, ['set', 'where', 'data']);
        ValidatorModel::boolType($aArgs, ['isVersion']);

        if (!empty($aArgs['isVersion'])) {
            $table = 'res_version_attachments';
        } else {
            $table = 'res_attachments';
        }

        DatabaseModel::update([
            'table' => $table,
            'set'   => $aArgs['set'],
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }

    public static function getAttachmentsTypesByXML()
    {
        $attachmentTypes = [];

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/entreprise.xml']);
        if ($loadedXml) {
            $attachmentTypesXML = $loadedXml->attachment_types;
            if (count($attachmentTypesXML) > 0) {
                foreach ($attachmentTypesXML->type as $value) {
                    $label = defined((string) $value->label) ? constant((string) $value->label) : (string) $value->label;
                    $attachmentTypes[(string) $value->id] = [
                        'label'     => $label,
                        'icon'      => (string)$value['icon'],
                        'sign'      => (empty($value['sign']) || (string)$value['sign'] == 'true') ? true : false,
                        'chrono'    => (empty($value['with_chrono']) || (string)$value['with_chrono'] == 'true') ? true : false,
                        'show'      => (empty($value->attributes()->show) || (string)$value->attributes()->show == 'true') ? true : false
                    ];
                }
            }
        }

        return $attachmentTypes;
    }

    public static function unsignAttachment(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['table', 'resId']);
        ValidatorModel::stringType($aArgs, ['table']);
        ValidatorModel::intVal($aArgs, ['resId']);

        DatabaseModel::update([
            'table'     => $aArgs['table'],
            'set'       => ['status' => 'A_TRA', 'signatory_user_serial_id' => null],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        DatabaseModel::update([
            'table'     => 'res_attachments',
            'set'       => ['status' => 'DEL'],
            'where'     => ['origin = ?', 'status != ?'],
            'data'      => ["{$aArgs['resId']},{$aArgs['table']}", 'DEL']
        ]);

        return true;
    }

    public static function freezeAttachment(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['table', 'resId', 'externalId']);
        ValidatorModel::intType($aArgs, ['resId']);

        $aAttachment = DatabaseModel::select([
            'select'    => ['external_id'],
            'table'     => [$aArgs['table']],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']],
        ]);

        $externalId = json_decode($aAttachment[0]['external_id'], true);
        $externalId['signatureBookId'] = empty($aArgs['externalId']) ? null : $aArgs['externalId'];

        DatabaseModel::update([
            'table'     => $aArgs['table'],
            'set'       => ['status' => 'FRZ', 'external_id' => json_encode($externalId)],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['resId']]
        ]);

        return true;
    }

    public static function setInSignatureBook(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::boolType($aArgs, ['inSignatureBook', 'isVersion']);

        if ($aArgs['isVersion'] == true) {
            $table = 'res_version_attachments';
        } else {
            $table = 'res_attachments';
        }
        if ($aArgs['inSignatureBook']) {
            $aArgs['inSignatureBook'] =  'true';
        } else {
            $aArgs['inSignatureBook'] =  'false';
        }

        DatabaseModel::update([
            'table'     => $table,
            'set'       => [
                'in_signature_book'   => $aArgs['inSignatureBook']
            ],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        return true;
    }

    public static function setInSendAttachment(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::boolType($aArgs, ['inSendAttachment', 'isVersion']);

        if ($aArgs['isVersion'] == true) {
            $table = 'res_version_attachments';
        } else {
            $table = 'res_attachments';
        }
        if ($aArgs['inSendAttachment']) {
            $aArgs['inSendAttachment'] =  'true';
        } else {
            $aArgs['inSendAttachment'] =  'false';
        }

        DatabaseModel::update([
            'table'     => $table,
            'set'       => [
                'in_send_attach'   => $aArgs['inSendAttachment']
            ],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        return true;
    }

    public static function hasAttachmentsSignedForUserById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id', 'user_serial_id', 'isVersion']);
        ValidatorModel::intVal($aArgs, ['id', 'user_serial_id']);
        ValidatorModel::stringType($aArgs, ['isVersion']);

        if ($aArgs['isVersion'] == 'true') {
            $table = 'res_version_attachments';
        } else {
            $table = 'res_attachments';
        }

        $attachment = DatabaseModel::select([
            'select'    => ['res_id_master'],
            'table'     => [$table],
            'where'     => ['res_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        $attachments = DatabaseModel::select([
            'select'    => ['res_id_master'],
            'table'     => ['res_view_attachments'],
            'where'     => ['res_id_master = ?', 'signatory_user_serial_id = ?'],
            'data'      => [$attachment[0]['res_id_master'], $aArgs['user_serial_id']],
        ]);

        if (empty($attachments)) {
            return false;
        }

        return true;
    }
}
