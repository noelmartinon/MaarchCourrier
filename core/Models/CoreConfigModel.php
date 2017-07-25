<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Core Config Model
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Models;

//This model is not customizable
class CoreConfigModel
{
    public static function getCustomId()
    {
        if (!file_exists('custom/custom.xml')) {
            return '';
        }

        $explodeUrl = explode('/', $_SERVER['SCRIPT_NAME']);
        $path = $explodeUrl[count($explodeUrl) - 3];

        $xmlfile = simplexml_load_file('custom/custom.xml');
        foreach ($xmlfile->custom as $value) {
            if (!empty($value->path) && $value->path == $path) {
                return (string)$value->custom_id;
            } elseif($value->ip == $_SERVER['SERVER_ADDR']) {
                return (string)$value->custom_id;
            } else if ($value->external_domain == $_SERVER['HTTP_HOST'] || $value->domain == $_SERVER['HTTP_HOST']) {
                return (string)$value->custom_id;
            }
        }

        return '';
    }

    public static function getApplicationName()
    {
        $customId = CoreConfigModel::getCustomId();

        if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/config.xml")) {
            $path = "custom/{$customId}/apps/maarch_entreprise/xml/config.xml";
        } else {
            $path = 'apps/maarch_entreprise/xml/config.xml';
        }

        if (file_exists($path)) {
            $loadedXml = simplexml_load_file($path);
            if ($loadedXml) {
                return (string)$loadedXml->CONFIG->applicationname;
            }
        }

        return 'Maarch Courrier';
    }

    public static function getLanguage()
    {
        $availableLanguages = ['en', 'fr'];
        $customId = CoreConfigModel::getCustomId();

        if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/config.xml")) {
            $path = "custom/{$customId}/apps/maarch_entreprise/xml/config.xml";
        } else {
            $path = 'apps/maarch_entreprise/xml/config.xml';
        }

        if (file_exists($path)) {
            $loadedXml = simplexml_load_file($path);
            if ($loadedXml) {
                $lang = (string)$loadedXml->CONFIG->lang;
                if (in_array($lang, $availableLanguages)) {
                    return $lang;
                }
            }
        }

        return 'en';
    }
}
