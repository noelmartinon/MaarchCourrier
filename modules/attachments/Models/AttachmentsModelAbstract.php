<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

namespace Attachments\Models;

require_once 'apps/maarch_entreprise/services/Table.php';

class AttachmentsModelAbstract extends \Apps_Table_Service
{
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
}