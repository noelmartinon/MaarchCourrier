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

namespace SrcCore\models;

//This model is not customizable
class CoreConfigModel
{
    public static function getCustomId()
    {
        if (!file_exists('custom/custom.xml') || empty($_SERVER['SCRIPT_NAME']) || empty($_SERVER['SERVER_ADDR'])) {
            return '';
        }

        $explodeUrl = explode('/', $_SERVER['SCRIPT_NAME']);

        if (strpos($_SERVER['SCRIPT_NAME'], 'ws_server') !== false) {
            $path = $explodeUrl[count($explodeUrl) - 2];
        } elseif (strpos($_SERVER['SCRIPT_NAME'], 'apps/maarch_entreprise/smartphone') !== false) {
            $path = $explodeUrl[count($explodeUrl) - 5];
        } elseif (strpos($_SERVER['SCRIPT_NAME'], 'apps/maarch_entreprise') === false) {
            $path = $explodeUrl[count($explodeUrl) - 3];
        } else {
            $path = $explodeUrl[count($explodeUrl) - 4];
        }

        $xmlfile = simplexml_load_file('custom/custom.xml');
        foreach ($xmlfile->custom as $value) {
            if (!empty($value->path) && $value->path == $path) {
                return (string)$value->custom_id;
            } elseif ($value->ip == $_SERVER['SERVER_ADDR']) {
                return (string)$value->custom_id;
            } elseif ($value->external_domain == $_SERVER['HTTP_HOST'] || $value->domain == $_SERVER['HTTP_HOST']) {
                return (string)$value->custom_id;
            }
        }

        return '';
    }

    public static function getApplicationName()
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);

        if ($loadedXml) {
            return (string)$loadedXml->CONFIG->applicationname;
        }

        return 'Maarch Courrier';
    }

    public static function getLanguage()
    {
        $availableLanguages = ['en', 'fr'];

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);

        if ($loadedXml) {
            $lang = (string)$loadedXml->CONFIG->lang;
            if (in_array($lang, $availableLanguages)) {
                return $lang;
            }
        }

        return 'en';
    }

    public static function getTmpPath()
    {
        /*$tmpPath = str_replace('src/core/models', '', dirname(__FILE__));
        $tmpPath .= 'apps/maarch_entreprise/tmp/';*/
        //correction psur le chemin du repertoire image de la signature
        // $tmpPath renvoie comme chemin src/core/modelsapps/maarch_entreprise/tmp/
        $tmpPath = 'apps/maarch_entreprise/tmp/';
        return $tmpPath;
    }

    public static function getLoggingMethod()
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/login_method.xml']);

        $loggingMethod = [];
        if ($loadedXml) {
            foreach ($loadedXml->METHOD as $value) {
                if (!empty((string)$value->ENABLED) && (string)$value->ENABLED == "true") {
                    $loggingMethod['id']        = (string)$value->ID;
                    $loggingMethod['name']      = (string)$value->NAME;
                    $loggingMethod['script']    = (string)$value->SCRIPT;
                }
            }
        }

        return $loggingMethod;
    }

    public static function getOzwilloConfiguration(array $aArgs = [])
    {
        ValidatorModel::stringType($aArgs, ['customId']);

        if (empty($aArgs['customId'])) {
            $customId = CoreConfigModel::getCustomId();
        } else {
            $customId = $aArgs['customId'];
        }

        if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/ozwilloConfig.xml")) {
            $path = "custom/{$customId}/apps/maarch_entreprise/xml/ozwilloConfig.xml";
        } else {
            $path = 'apps/maarch_entreprise/xml/ozwilloConfig.xml';
        }

        $ozwilloConfig = [];
        if (file_exists($path)) {
            $loadedXml = simplexml_load_file($path);
            if ($loadedXml) {
                $ozwilloConfig['instanceUri']           = (string)$loadedXml->INSTANCE_URI;
                $ozwilloConfig['instantiationSecret']   = (string)$loadedXml->INSTANTIATION_SECRET;
                $ozwilloConfig['destructionSecret']     = (string)$loadedXml->DESTRUCTION_SECRET;
                $ozwilloConfig['uri']                   = (string)$loadedXml->URI;
                $ozwilloConfig['clientId']              = (string)$loadedXml->CLIENT_ID;
                $ozwilloConfig['clientSecret']          = (string)$loadedXml->CLIENT_SECRET;
                $ozwilloConfig['groupId']               = (string)$loadedXml->GROUP_ID;
                $ozwilloConfig['entityId']              = (string)$loadedXml->ENTITY_ID;
            }
        }

        return $ozwilloConfig;
    }

    public static function getLettersBoxCategories()
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);

        $categoriesTypes = [];
        $categories      = $loadedXml->COLLECTION->categories;
        if (count($categories) > 0) {
            foreach ($categories->category as $category) {
                $categoriesTmp = ['id' => (string)$category->id, 'label'=> constant((string)$category->label)];

                if ($category->id == (string)$categories->default_category) {
                    $categoriesTmp['default_category'] = true;
                } else {
                    $categoriesTmp['default_category'] = false;
                }
                $categoriesTypes[] = $categoriesTmp;
            }
        }

        return $categoriesTypes;
    }

    public static function getXmlLoaded(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['path']);
        ValidatorModel::stringType($aArgs, ['path']);

        $customId = CoreConfigModel::getCustomId();

        if (file_exists("custom/{$customId}/{$aArgs['path']}")) {
            $path = "custom/{$customId}/{$aArgs['path']}";
        } else {
            $path = $aArgs['path'];
        }

        $xmlfile = null;
        if (file_exists($path)) {
            $xmlfile = simplexml_load_file($path);
        }

        return $xmlfile;
    }
}
