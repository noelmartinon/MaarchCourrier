<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Template Model Abstract
* @author dev@maarch.org
* @ingroup templates
*/

namespace Templates\Models;

use Core\Models\DatabaseModel;
use Core\Models\ValidatorModel;

class TemplateModelAbstract
{
    public static function getById(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::intVal($aArgs, ['id']);

        $aTemplate = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['templates'],
            'where'     => ['template_id = ?'],
            'data'      => [$aArgs['id']],
        ]);

        if (empty($aTemplate[0])) {
            return [];
        }

        return $aTemplate[0];
    }

    public static function create(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['template_label']);
        ValidatorModel::stringType($aArgs, ['template_label']);

        $nextSequenceId = DatabaseModel::getNextSequenceValue(['sequenceId' => 'templates_seq']);

        DatabaseModel::insert(
            [
                'table'         => 'templates',
                'columnsValues' => [
                    'template_id'               => $nextSequenceId,
                    'template_label'            => $aArgs['template_label'],
                    'template_comment'          => $aArgs['template_comment'],
                    'template_content'          => $aArgs['template_content'],
                    'template_type'             => $aArgs['template_type'],
                    'template_style'            => $aArgs['template_style'],
                    'template_datasource'       => $aArgs['template_datasource'],
                    'template_target'           => $aArgs['template_target'],
                    'template_attachment_type'  => $aArgs['template_attachment_type'],
                    'template_path'             => $aArgs['template_path'],
                    'template_file_name'        => $aArgs['template_file_name'],
                ]
            ]
        );

        return $nextSequenceId;
    }
}
