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

    public static function getApplicationVersion()
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/applicationVersion.xml']);

        if ($loadedXml) {
            return [
                'applicationVersion'       =>  (string) $loadedXml->majorVersion,
                'applicationMinorVersion'  =>  (string) $loadedXml->minorVersion,
            ];
        }

        return [];
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

    /**
     * Get the timezone
     *
     * @return string
     */
    public static function getTimezone()
    {
        $timezone = 'Europe/Paris';

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);

        if ($loadedXml) {
            if (!empty((string)$loadedXml->CONFIG->timezone)) {
                $timezone = (string)$loadedXml->CONFIG->timezone;
            }
        }

        return $timezone;
    }

    /**
     * Get the tmp dir
     *
     * @return string
     */
    public static function getTmpPath()
    {
        if (isset($_SERVER['MAARCH_TMP_DIR'])) {
            $tmpDir = $_SERVER['MAARCH_TMP_DIR'];
        } elseif (isset($_SERVER['REDIRECT_MAARCH_TMP_DIR'])) {
            $tmpDir = $_SERVER['REDIRECT_MAARCH_TMP_DIR'];
        } else {
            $tmpDir = sys_get_temp_dir();
        }

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755);
        }

        return $tmpDir . '/';
    }

    public static function getLoggingMethod()
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/login_method.xml']);

        $loggingMethod = [];
        if ($loadedXml) {
            foreach ($loadedXml->METHOD as $value) {
                if ((string)$value->ENABLED == 'true') {
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

    public static function getFavIcon(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['path']);
        ValidatorModel::stringType($aArgs, ['path']);

        $customId = CoreConfigModel::getCustomId();

        if (file_exists("custom/{$customId}/{$aArgs['path']}")) {
            $path = "custom/{$customId}/{$aArgs['path']}";
        } else {
            $path = $aArgs['path'];
        }

        return $path;
    }

    public static function initAngularStructure()
    {
        $lang = CoreConfigModel::getLanguage();
        $appName = CoreConfigModel::getApplicationName();
        $favIconPath = CoreConfigModel::getFavIcon(["path" => "apps/maarch_entreprise/img/logo_only.svg"]);

        $structure = '<!doctype html>';
        $structure .= "<html lang='{$lang}'>";
        $structure .= '<head>';
        $structure .= "<meta charset='utf-8'>";
        $structure .= "<title>{$appName}</title>";
        $structure .= "<link rel='icon' href='../../{$favIconPath}' />";

        /* CSS PARTS */
        $structure .= '<link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.css" media="screen" />';
        $structure .= '<link rel="stylesheet" href="css/font-awesome-maarch/css/font-maarch.css" media="screen" />';

        $structure .= '</head>';

        /* SCRIPS PARTS */
        $structure .= "<script src='../../node_modules/jquery/dist/jquery.min.js'></script>";
        $structure .= "<script src='../../node_modules/core-js/client/shim.js'></script>";
        $structure .= "<script src='../../node_modules/zone.js/dist/zone.min.js'></script>";
        $structure .= "<script src='../../node_modules/bootstrap/dist/js/bootstrap.min.js'></script>";
        $structure .= "<script src='../../node_modules/chart.js/Chart.min.js'></script>";
        $structure .= "<script src='../../node_modules/tinymce/tinymce.min.js'></script>";
        $structure .= "<script src='../../node_modules/jquery.nicescroll/jquery.nicescroll.min.js'></script>";
        $structure .= "<script src='../../node_modules/tooltipster/dist/js/tooltipster.bundle.min.js'></script>";
        $structure .= "<script src='../../node_modules/jquery-typeahead/dist/jquery.typeahead.min.js'></script> ";
        $structure .= "<script src='../../node_modules/chosen-js/chosen.jquery.min.js'></script>";
        $structure .= "<script src='../../node_modules/jstree-bootstrap-theme/dist/jstree.js'></script>";
        $structure .= "<script src='js/angularFunctions.js'></script>";

        /* AUTO DISCONNECT */
        $structure .= "<script>checkCookieAuth();</script>";
        
        $structure .= '<body>';
        $structure .= '</body>';
        $structure .= '</html>';

        return $structure;
    }
}
