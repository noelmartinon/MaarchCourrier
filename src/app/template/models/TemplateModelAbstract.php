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
 */

namespace Template\models;

use SrcCore\models\ValidatorModel;
use SrcCore\models\DatabaseModel;

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

    public static function getByTarget(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['template_target']);

        $aTemplate = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['templates'],
            'where'     => ['template_target = ?'],
            'data'      => [$aArgs['template_target']],
        ]);

        return $aTemplate;
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

    public static function getAssociation(array $aArgs = [])
    {
        ValidatorModel::arrayType($aArgs, ['select', 'where', 'data']);

        $aTemplatesAssociation = DatabaseModel::select([
            'select'    => empty($aArgs['select']) ? ['*'] : $aArgs['select'],
            'table'     => ['templates_association'],
            'where'     => $aArgs['where'],
            'data'      => $aArgs['data']
        ]);

        return $aTemplatesAssociation;
    }

    public static function updateAssociation(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['set', 'where', 'data']);
        ValidatorModel::arrayType($aArgs, ['set', 'where', 'data']);

        DatabaseModel::update([
            'table' => 'templates_association',
            'set'   => $aArgs['set'],
            'where' => $aArgs['where'],
            'data'  => $aArgs['data']
        ]);

        return true;
    }
}
