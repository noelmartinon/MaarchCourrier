<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Chrono Model
 * @author dev@maarch.org
 * @ingroup core
 */

namespace Core\Models;

class ChronoModel
{
    public static function getChrono(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['id']);
        ValidatorModel::stringType($aArgs, ['id', 'entityId']);
        ValidatorModel::intVal($aArgs, ['typeId', 'resId']);

        $customId = CoreConfigModel::getCustomId();
        if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/chrono.xml")) {
            $path = "custom/{$customId}/apps/maarch_entreprise/xml/chrono.xml";
        } else {
            $path = 'apps/maarch_entreprise/xml/chrono.xml';
        }

        $elements = [];
        if (file_exists($path)) {
            $loadedXml = simplexml_load_file($path);

            foreach ($loadedXml->CHRONO as $chrono) {
                if ($chrono->id == $aArgs['id']) {
                    $separator = (string)$chrono->separator;
                    foreach ($chrono->ELEMENT as $chronoElement) {
                        $elements[] = [
                            'type'  => (string)$chronoElement->type,
                            'value' => (string)$chronoElement->value
                        ];
                    }
                }
            }
        }

        foreach ($elements as $key => $value) {
            if (!empty($value['type'])) {
                if ($value['type'] == 'date') {
                    if ($value['value'] == 'year') {
                        $elements[$key]['value'] = date('Y');
                    } else if ($value['value'] == 'month') {
                        $elements[$key]['value'] = date('m');
                    } else if ($value['value'] == 'day') {
                        $elements[$key]['value'] = date('d');
                    } else if ($value['value'] == 'full_date') {
                        $elements[$key]['value'] = date('dmY');
                    }
                } elseif ($value['type'] == 'maarch_var') {
                    if ($value['value'] == "entity_id") {
                        $elements[$key]['value'] = $aArgs['entityId'];
                    } else if ($value['value'] == 'type_id') {
                        $elements[$key]['value'] = $aArgs['typeId'];
                    }
                } elseif ($value['TYPE'] == 'maarch_functions') {
                    if ($value['value'] == 'chr_global') {
                        $elements[$key]['value'] = ChronoModel::getChronoGlobal();
                    } else if ($value['value'] == 'chr_by_entity') {
                        $elements[$key]['value'] = ChronoModel::getChronoEntity($aArgs['entityId']);
                    } else if ($value['value'] == 'chr_by_category') {
                        $elements[$key]['value'] = ChronoModel::getChronoCategory($aArgs['id']);
                    } else if ($value['value'] == 'category_char') {
                        $elements[$key]['value'] = ChronoModel::getChronoCategoryChar($aArgs['id']);
                    } else if ($value['value'] == 'chr_by_folder') {
                        $elements[$key]['value'] = ChronoModel::getChronoFolder($aArgs['folderId']);
                    } else if ($value['value'] == 'chr_by_res_id') {
                        $elements[$key]['value'] = $aArgs['resId'];
                    }
                }
            }
        }

        if (empty($separator)) {
            $separator = '/';
        }
        $chrono = $separator . implode($separator, $elements);

        return $chrono;
    }

    public static function getChronoGlobal()
    {
        $chronoId = 'chrono_global_' . date('Y');

        $parameter = ParameterModel::getById(['id' => $chronoId, 'select' => ['param_value_int']]);

        if (empty($parameter)) {
            ParameterModel::create(['id' => $chronoId, 'param_value_int' => 1]);
            $chrono = 1;
        } else {
            $chrono = $parameter['param_value_int'];
        }

        ParameterModel::update(['id' => $chronoId, 'param_value_int' => $chrono + 1]);

        return $chrono;
    }

    public static function getChronoEntity($entityId)
    {
        $chronoId = "chrono_{$entityId}_" . date('Y');

        $parameter = ParameterModel::getById(['id' => $chronoId, 'select' => ['param_value_int']]);

        if (empty($parameter)) {
            ParameterModel::create(['id' => $chronoId, 'param_value_int' => 1]);
            $chrono = 1;
        } else {
            $chrono = $parameter['param_value_int'];
        }

        ParameterModel::update(['id' => $chronoId, 'param_value_int' => $chrono + 1]);

        return $entityId . "/" . $chrono;
    }

    public static function getChronoFolder($folderId)
    {
        $chronoId = "chrono_folder_{$folderId}";

        $parameter = ParameterModel::getById(['id' => $chronoId, 'select' => ['param_value_int']]);

        if (empty($parameter)) {
            ParameterModel::create(['id' => $chronoId, 'param_value_int' => 1]);
            $chrono = 1;
        } else {
            $chrono = $parameter['param_value_int'];
        }

        ParameterModel::update(['id' => $chronoId, 'param_value_int' => $chrono + 1]);

        return $chrono;
    }

    public static function getChronoCategory($categoryId)
    {
        $chronoId = "chrono_{$categoryId}_" . date('Y');

        $parameter = ParameterModel::getById(['id' => $chronoId, 'select' => ['param_value_int']]);

        if (empty($parameter)) {
            ParameterModel::create(['id' => $chronoId, 'param_value_int' => 1]);
            $chrono = 1;
        } else {
            $chrono = $parameter['param_value_int'];
        }

        ParameterModel::update(['id' => $chronoId, 'param_value_int' => $chrono + 1]);

        return "/" . $chrono;
    }

    public static function getChronoCategoryChar($categoryId)
    {
        if ($categoryId == 'incoming') {
            return 'A';
        } else if ($categoryId == 'outgoing') {
            return 'D';
        } else {
            return '';
        }
    }
}