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

class CoreConfigModel
{
    public static function getCustomId()
    {
        static $customId;

        if ($customId !== null) {
            return $customId;
        }

        if (!file_exists('custom/custom.xml') || empty($_SERVER['SCRIPT_NAME']) || empty($_SERVER['SERVER_ADDR'])) {
            $customId = '';
            return $customId;
        }

        $explodeUrl = explode('/', $_SERVER['SCRIPT_NAME']);

        if (strpos($_SERVER['SCRIPT_NAME'], 'apps/maarch_entreprise') === false) {
            $path = $explodeUrl[count($explodeUrl) - 3];
        } else {
            $path = $explodeUrl[count($explodeUrl) - 4];
        }

        $xmlfile = simplexml_load_file('custom/custom.xml');
        foreach ($xmlfile->custom as $value) {
            if (!empty($value->path) && $value->path == $path) {
                $customId = (string)$value->custom_id;
                return $customId;
            } elseif ($value->ip == $_SERVER['SERVER_ADDR']) {
                $customId = (string)$value->custom_id;
                return $customId;
            } elseif ($value->external_domain == $_SERVER['HTTP_HOST'] || $value->domain == $_SERVER['HTTP_HOST']) {
                $customId = (string)$value->custom_id;
                return $customId;
            }
        }

        $customId = '';
        return $customId;
    }

    public static function getApplicationName()
    {
        static $applicationName;

        if ($applicationName !== null) {
            return $applicationName;
        }

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);

        if ($loadedXml) {
            $applicationName = (string)$loadedXml->CONFIG->applicationname;
            return $applicationName;
        }

        $applicationName = 'Maarch Courrier';
        return $applicationName;
    }

    public static function getApplicationVersion()
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/applicationVersion.xml']);

        if (empty($loadedXml)) {
            return '';
        }

        return (string)$loadedXml->version;
    }

    public static function getLanguage()
    {
        $availableLanguages = ['en', 'fr', 'nl'];

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/config.xml']);

        if ($loadedXml) {
            $lang = (string)$loadedXml->CONFIG->lang;
            if (in_array($lang, $availableLanguages)) {
                return $lang;
            }
        }

        return 'en';
    }

    public static function getCustomLanguage($aArgs = [])
    {
        $customId = CoreConfigModel::getCustomId();
        if (file_exists('custom/' . $customId . '/lang/lang-'.$aArgs['lang'].'.ts')) {
            $fileContent = file_get_contents('custom/' . $customId . '/lang/lang-'.$aArgs['lang'].'.ts');
            $fileContent = str_replace("\n", "", $fileContent);

            $strpos = strpos($fileContent, "=");
            $substr = substr(trim($fileContent), $strpos + 2, -1);

            $trimmed = rtrim($substr, ',}');
            $trimmed .= '}';
            $decode = json_decode($trimmed);

            return $decode;
        }

        return '';
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

    /**
     * Get the Encrypt Key
     *
     * @return string
     */
    public static function getEncryptKey()
    {
        if (isset($_SERVER['MAARCH_ENCRYPT_KEY'])) {
            $enc_key = $_SERVER['MAARCH_ENCRYPT_KEY'];
        } elseif (isset($_SERVER['REDIRECT_MAARCH_ENCRYPT_KEY'])) {
            $enc_key = $_SERVER['REDIRECT_MAARCH_ENCRYPT_KEY'];
        } else {
            $enc_key = "Security Key Maarch Courrier #2008";
        }

        return $enc_key;
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

    public static function getMailevaConfiguration()
    {
        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/mailevaConfig.xml']);

        $mailevaConfig = [];
        if ($loadedXml) {
            $mailevaConfig['enabled']       = filter_var((string)$loadedXml->ENABLED, FILTER_VALIDATE_BOOLEAN);
            $mailevaConfig['connectionUri'] = (string)$loadedXml->CONNECTION_URI;
            $mailevaConfig['uri']           = (string)$loadedXml->URI;
            $mailevaConfig['clientId']      = (string)$loadedXml->CLIENT_ID;
            $mailevaConfig['clientSecret']  = (string)$loadedXml->CLIENT_SECRET;
        }

        return $mailevaConfig;
    }

    public static function getXmlLoaded(array $args)
    {
        ValidatorModel::notEmpty($args, ['path']);
        ValidatorModel::stringType($args, ['path']);

        $customId = CoreConfigModel::getCustomId();

        if (file_exists("custom/{$customId}/{$args['path']}")) {
            $path = "custom/{$customId}/{$args['path']}";
        } else {
            $path = $args['path'];
        }

        $xmlfile = null;
        if (file_exists($path)) {
            $xmlfile = simplexml_load_file($path);
        }

        return $xmlfile;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function initAngularStructure()
    {
        $lang = CoreConfigModel::getLanguage();
        $appName = CoreConfigModel::getApplicationName();

        $structure = '<!doctype html>';
        $structure .= "<html lang='{$lang}'>";
        $structure .= '<head>';
        $structure .= "<meta charset='utf-8'>";
        $structure .= "<title>{$appName}</title>";
        $structure .= "<link rel='icon' type=\"image/svg+xml\" href='static.php?filename=logo_only.svg' />";

        /* CSS PARTS */
        $structure .= '<link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.css" media="screen" />';
        $structure .= '<link rel="stylesheet" href="css/font-awesome-maarch/css/font-maarch.css" media="screen" />';
        $structure .= '<link rel="stylesheet" href="../../node_modules/jstree-bootstrap-theme/dist/themes/proton/style.min.css" media="screen" />';

        $structure .= '</head>';

        /* SCRIPS PARTS */
        $structure .= "<script src='../../node_modules/jquery/dist/jquery.min.js'></script>";
        $structure .= "<script src='../../node_modules/zone.js/dist/zone.min.js'></script>";
        $structure .= "<script src='../../node_modules/bootstrap/dist/js/bootstrap.min.js'></script>";
        $structure .= "<script src='../../node_modules/tinymce/tinymce.min.js'></script>";
        $structure .= "<script src='../../node_modules/jquery.nicescroll/dist/jquery.nicescroll.min.js'></script>";
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

    /**
     * Database Unique Id Function
     *
     * @return string $uniqueId
     */
    public static function uniqueId()
    {
        $parts = explode('.', microtime(true));
        $sec = $parts[0];
        if (!isset($parts[1])) {
            $msec = 0;
        } else {
            $msec = $parts[1];
        }

        $uniqueId = str_pad(base_convert($sec, 10, 36), 6, '0', STR_PAD_LEFT);
        $uniqueId .= str_pad(base_convert($msec, 10, 16), 4, '0', STR_PAD_LEFT);
        $uniqueId .= str_pad(base_convert(mt_rand(), 10, 36), 6, '0', STR_PAD_LEFT);

        return $uniqueId;
    }
}
