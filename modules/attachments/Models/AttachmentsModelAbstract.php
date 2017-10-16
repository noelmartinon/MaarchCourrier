<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

namespace Attachments\Models;

use Core\Models\DatabaseModel;
use Core\Models\ValidatorModel;

require_once 'apps/maarch_entreprise/services/Table.php';

class AttachmentsModelAbstract extends \Apps_Table_Service
{
    public static function getById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['isVersion']);

        if ($aArgs['isVersion'] == 'true') {
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

    public static function getAttachmentsTypesByXML()
    {
        if (file_exists('custom/' .$_SESSION['custom_override_id']. '/apps/maarch_entreprise/xml/entreprise.xml')) {
            $path = 'custom/' .$_SESSION['custom_override_id']. '/apps/maarch_entreprise/xml/entreprise.xml';
        } else {
            $path = 'apps/maarch_entreprise/xml/entreprise.xml';
        }

        $xmlfile = simplexml_load_file($path);
        $attachmentTypes = [];
        $attachmentTypesXML = $xmlfile->attachment_types;
        if (count($attachmentTypesXML) > 0) {
            foreach ($attachmentTypesXML->type as $value) {
                $label = defined((string) $value->label) ? constant((string) $value->label) : (string) $value->label;
                $attachmentTypes[(string) $value->id] = [
                    'label' => $label,
                    'icon' => (string)$value['icon'],
                    'sign' => (empty($value['sign']) || (string)$value['sign'] == 'true') ? true : false
                ];
            }
        }

        return $attachmentTypes;
    }

    public static function getAttachmentsWithOptions(array $aArgs = [])
    {
        static::checkRequired($aArgs, ['where', 'data']);
        static::checkArray($aArgs, ['where', 'data']);


        $select = [
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['res_view_attachments'],
            'where'     => $aArgs['where'],
            'data'      => $aArgs['data'],
        ];
        if (!empty($aArgs['orderBy'])) {
            $select['order_by'] = $aArgs['orderBy'];
        }

        $aReturn = static::select($select);

        return $aReturn;
    }

    public static function setInSignatureBook(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['isVersion']);
        ValidatorModel::boolType($aArgs, ['inSignatureBook']);

        if ($aArgs['isVersion'] == 'true') {
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
}
