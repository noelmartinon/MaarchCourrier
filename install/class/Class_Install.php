<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*/

/**
 *
 * @file
 *
 * @author Laurent Giovannoni
 * @author Arnaud Veber
 * @date $date$
 *
 * @version $Revision$
 * @ingroup install
 */

//Loads the required class
try {
    require_once 'core/class/class_functions.php';
    require_once 'core/class/class_db.php';
    require_once 'install/class/Class_Merge.php';
    require_once 'core'.DIRECTORY_SEPARATOR.'class'
        .DIRECTORY_SEPARATOR.'class_security.php';
} catch (Exception $e) {
    functions::xecho($e->getMessage()).' // ';
}

class Install extends functions
{
    private $lang = 'en';

    private $docservers = array(
        array('FASTHD_AI', 'ai'),
        array('FASTHD_MAN', 'manual'),
        array('FASTHD_ATTACH', 'manual_attachments'),
        array('CONVERT_MLB', 'convert_mlb'),
        array('CONVERT_ATTACH', 'convert_attachments'),
        array('TNL_MLB', 'thumbnails_mlb'),
        array('TNL_ATTACH', 'thumbnails_attachments'),
        array('FULLTEXT_MLB', 'fulltext_mlb'),
        array('FULLTEXT_ATTACH', 'fulltext_attachments'),
        array('TEMPLATES', 'templates'),
        array('ARCHIVETRANSFER', 'archive_transfer'),
        array('ACKNOWLEDGEMENT_RECEIPTS', 'acknowledgment_receipts'),
    );

    public function __construct()
    {
        //merge css & js
        $Class_Merge = new Merge();
        //load lang
        $this->loadLang();
    }

    public function getLangList()
    {
        $langList = array();
        foreach (glob('install/lang/*.php') as $fileLangPath) {
            $langFile = str_replace('.php', '', end(explode('/', $fileLangPath)));
            array_push($langList, $langFile);
        }

        return $langList;
    }

    public function loadLang()
    {
        if (!isset($_SESSION['lang'])) {
            $this->lang = 'en';
        }
        $this->lang = $_SESSION['lang'];

        $langList = $this->getLangList();
        if (!in_array($this->lang, $langList)) {
            $this->lang = 'en';
        }

        require_once 'install/lang/'.$this->lang.'.php';
    }

    public function getActualLang()
    {
        return $this->lang;
    }

    public function checkPrerequisites(
        $is = false,
        $optional = false
    ) {
        if ($is) {
            return '<img src="img/green_light.png" width="20px"/>';
            exit;
        }
        if (!$optional) {
            return '<img src="img/red_light.png"  width="20px"/>';
            exit;
        }

        return '<img src="img/orange_light.png"  width="20px"/>';
    }

    public function checkAllNeededPrerequisites()
    {
        if (!$this->isPhpVersion()) {
            return false;
        }
        if (!$this->isUnoconvInstalled()) {
            return false;
        }
        if (!$this->isPhpRequirements('pdo_pgsql')) {
            return false;
        }
        if (!$this->isPhpRequirements('pgsql')) {
            return false;
        }
        if (!$this->isPhpRequirements('mbstring')) {
            return false;
        }
        if (!$this->isMaarchPathWritable()) {
            return false;
        }
        if (!$this->isDependenciesExist()) {
            return false;
        }
        if (!$this->isPhpRequirements('gd')) {
            return false;
        }
        if (!$this->isPearRequirements('System.php')) {
            return false;
        }
        if (!$this->isPhpRequirements('imagick')) {
            return false;
        }
        if (!$this->isIniDisplayErrorRequirements()) {
            return false;
        }
        if (!$this->isIniShortOpenTagRequirements()) {
            return false;
        }

        if (DIRECTORY_SEPARATOR != '/' && !$this->isPhpRequirements('fileinfo')) {
            return false;
        }

        return true;
    }

    public function isPhpVersion()
    {
        if (version_compare(PHP_VERSION, '7.2') < 0) {
            return false;
            exit;
        }

        return true;
    }

    public function isPhpRequirements($phpLibrary)
    {
        if (!@extension_loaded($phpLibrary)) {
            return false;
            exit;
        }

        return true;
    }

    public function isPearRequirements($pearLibrary)
    {
        $includePath = array();
        $includePath = explode(';', ini_get('include_path'));
        for ($i = 0; $i < count($includePath); ++$i) {
            if (file_exists($includePath[$i].'/'.$pearLibrary)) {
                return true;
                exit;
            }
        }
        $includePath = explode(':', ini_get('include_path'));
        for ($i = 0; $i < count($includePath); ++$i) {
            if (file_exists($includePath[$i].'/'.$pearLibrary)) {
                return true;
                exit;
            }
        }

        return false;
    }

    public function isIniErrorRepportingRequirements()
    {
        if (ini_get('error_reporting') != 22519) {
            return false;
        } else {
            return true;
        }
    }

    public function isIniDisplayErrorRequirements()
    {
        if (strtoupper(ini_get('display_errors')) == 'OFF') {
            return false;
        } else {
            return true;
        }
    }

    public function isIniShortOpenTagRequirements()
    {
        if (strtoupper(ini_get('short_open_tag')) == 'OFF') {
            return false;
        } else {
            return true;
        }
    }

    public function getProgress(
        $stepNb,
        $stepNbTotal
    ) {
        --$stepNb;
        --$stepNbTotal;
        if ($stepNb == 0) {
            return '';
            exit;
        }
        $return = '';
        $percentProgress = round(($stepNb / $stepNbTotal) * 100);
        $sizeProgress = round(($percentProgress * 910) / 100);

        $return .= '<div id="progressButton" style="width: '.$sizeProgress.'px;">';
        $return .= '<div align="center">';
        $return .= $percentProgress.'%';
        $return .= '</div>';
        $return .= '</div>';

        return $return;
    }

    public function setPreviousStep($previousStep)
    {
        $_SESSION['previousStep'] = $previousStep;
    }

    public function checkDatabaseParameters(
        $databaseserver,
        $databaseserverport,
        $databaseuser,
        $databasepassword,
        $databasetype
    ) {
        $connect = 'host='.$databaseserver.' ';
        $connect .= 'port='.$databaseserverport.' ';
        $connect .= 'user='.$databaseuser.' ';
        $connect .= 'password='.$databasepassword.' ';
        $connect .= 'dbname=postgres';

        if (!@pg_connect($connect)) {
            return false;
            exit;
        }

        pg_close();

        return true;
    }

    public function createCustom($databasename)
    {
        $customAlreadyExist = realpath('.').'/custom/cs_'.$databasename;
        if (file_exists($customAlreadyExist)) {
            //return false;
            if (is_dir(realpath('.')."/custom/cs_$databasename/apps/") && is_dir(realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise/") && is_dir(realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise/xml/")) {
            } elseif (is_dir(realpath('.')."/custom/cs_$databasename/apps/") && !is_dir(realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise/")) {
                $cheminCustomMaarchCourrierAppsMaarchEntreprise = realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise";
                mkdir($cheminCustomMaarchCourrierAppsMaarchEntreprise, 0755);

                $cheminCustomMaarchCourrierAppsMaarchEntrepriseXml = realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise/xml";
                mkdir($cheminCustomMaarchCourrierAppsMaarchEntrepriseXml, 0755);
            } elseif (is_dir(realpath('.')."/custom/cs_$databasename/apps/") && is_dir(realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise/") && !is_dir(realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise/xml/")) {
                $cheminCustomMaarchCourrierAppsMaarchEntrepriseXml = realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise/xml";
                mkdir($cheminCustomMaarchCourrierAppsMaarchEntrepriseXml, 0755);
            } elseif (!is_dir(realpath('.')."/custom/cs_$databasename/apps/")) {
                $cheminCustomMaarchCourrierApps = realpath('.')."/custom/cs_$databasename/apps";
                mkdir($cheminCustomMaarchCourrierApps, 0755);

                $cheminCustomMaarchCourrierAppsMaarchEntreprise = realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise";
                mkdir($cheminCustomMaarchCourrierAppsMaarchEntreprise, 0755);

                $cheminCustomMaarchCourrierAppsMaarchEntrepriseXml = realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise/xml";
                mkdir($cheminCustomMaarchCourrierAppsMaarchEntrepriseXml, 0755);
            }

            if (!is_dir(realpath('.')."/custom/cs_$databasename/modules/")) {
                $cheminCustomMaarchCourrierModules = realpath('.')."/custom/cs_$databasename/modules";
                mkdir($cheminCustomMaarchCourrierModules, 0755);

                /** Création répertoire notification dans le custom **/
                $cheminCustomMaarchCourrierModulesNotificationsConfig = realpath('.')."/custom/cs_$databasename/bin/notification/config";
                mkdir($cheminCustomMaarchCourrierModulesNotificationsConfig, 0755);

                $cheminCustomMaarchCourrierModulesNotificationsScripts = realpath('.')."/custom/cs_$databasename/bin/notification/scripts";
                mkdir($cheminCustomMaarchCourrierModulesNotificationsScripts, 0755);

                /** Création répertoire LDAP dans le custom **/
                $cheminCustomMaarchCourrierModulesLdap = realpath('.')."/custom/cs_$databasename/modules/ldap";
                mkdir($cheminCustomMaarchCourrierModulesLdap, 0755);

                $cheminCustomMaarchCourrierModulesLdapXml = realpath('.')."/custom/cs_$databasename/modules/ldap/xml";
                mkdir($cheminCustomMaarchCourrierModulesLdapXml, 0755);

                $cheminCustomMaarchCourrierModulesLdapScript = realpath('.')."/custom/cs_$databasename/modules/ldap/script";
                mkdir($cheminCustomMaarchCourrierModulesLdapScript, 0755);
            }

            if (is_dir(realpath('.')."/custom/cs_$databasename/modules/")) {

                /* Création répertoire notif dans le custom **/
                if (!is_dir(realpath('.')."/custom/cs_$databasename/bin/notification/config/")) {
                    $cheminCustomMaarchCourrierModulesNotificationsConfig = realpath('.')."/custom/cs_$databasename/bin/notification/config";
                    mkdir($cheminCustomMaarchCourrierModulesNotificationsConfig, 0755);
                }
                if (!is_dir(realpath('.')."/custom/cs_$databasename/bin/notification/scripts/")) {
                    $cheminCustomMaarchCourrierModulesNotificationsScripts = realpath('.')."/custom/cs_$databasename/bin/notification/scripts";
                    mkdir($cheminCustomMaarchCourrierModulesNotificationsScripts, 0755);
                }

                if (!is_dir(realpath('.')."/custom/cs_$databasename/modules/ldap/")) {
                    $cheminCustomMaarchCourrierModulesLdap = realpath('.')."/custom/cs_$databasename/modules/ldap";
                    mkdir($cheminCustomMaarchCourrierModulesLdap, 0755);
                }
                if (!is_dir(realpath('.')."/custom/cs_$databasename/modules/ldap/xml/")) {
                    $cheminCustomMaarchCourrierModulesLdapXml = realpath('.')."/custom/cs_$databasename/modules/ldap/xml";
                    mkdir($cheminCustomMaarchCourrierModulesLdapXml, 0755);
                }
                if (!is_dir(realpath('.')."/custom/cs_$databasename/modules/ldap/script/")) {
                    $cheminCustomMaarchCourrierModulesLdapScript = realpath('.')."/custom/cs_$databasename/modules/ldap/script";
                    mkdir($cheminCustomMaarchCourrierModulesLdapScript, 0755);
                }
            }

            //Création du lien symbolique sous linux
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
                $cmd = 'ln -s '.realpath('.')."/ cs_$databasename";
                exec($cmd);
            }/*elseif(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
                $cmd = "mklink cs_$databasename ".realpath('.');
                var_dump($cmd);
                var_dump(exec($cmd));
                exit;
                exec($cmd);
            }*/
        } else {
            $chemin = realpath('.');
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
                $needle = '/';
            } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $needle = '\\';
            }

            $pos = strripos($chemin, $needle);

            if ($pos === false) {
                //echo "Désolé, impossible de trouver ($needle) dans ($chemin)";
            } else {
                // echo "Félicitations !\n";
                // echo "Nous avons trouvé le dernier ($needle) dans ($chemin) à la position ($pos)";
            }

            $rest = substr($chemin, $pos + 1);    // contient le nom de l'appli (le nom du dossier où se situe l'appli)

            $filename = realpath('.').'/custom/custom.xml';
            if (file_exists($filename)) {
                $xmlCustom = simplexml_load_file(realpath('.').'/custom/custom.xml');
                $custom = $xmlCustom->addChild('custom');
                $custom->addChild('custom_id', 'cs_'.$databasename);
                $custom->addChild('ip');
                $custom->addChild('external_domain');
                $custom->addChild('domain');
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
                    $custom->addChild('path', 'cs_'.$databasename);
                } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $custom->addChild('path', $rest);
                }
                $res = $xmlCustom->asXML();
                $fp = @fopen(realpath('.').'/custom/custom.xml', 'w+');
                if (!$fp) {
                    return false;
                    exit;
                }
                $write = fwrite($fp, $res);
                if (!$write) {
                    return false;
                    exit;
                }
            }

            if (!file_exists($filename)) {
                $manip2 = fopen(realpath('.').'/custom/custom.xml', 'w+');
                $contenuXmlCustom = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
                $contenuXmlCustom .= "<root>\n";
                $contenuXmlCustom .= "\t<custom>\n";
                $contenuXmlCustom .= "\t\t<custom_id>cs_".$databasename."</custom_id>\n";
                $contenuXmlCustom .= "\t\t<ip></ip>\n";
                $contenuXmlCustom .= "\t\t<external_domain></external_domain>\n";
                $contenuXmlCustom .= "\t\t<domain></domain>\n";
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'LIN') {
                    $contenuXmlCustom .= "\t\t<path>cs_".$databasename."</path>\n";
                } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $contenuXmlCustom .= "\t\t<path>$rest</path>\n";
                }
                $contenuXmlCustom .= "\t</custom>\n";
                $contenuXmlCustom .= '</root>';

                fputs($manip2, $contenuXmlCustom);
                fclose($manip2);
            }

            $cheminCustomMaarchCourrier = realpath('.')."/custom/cs_$databasename";

            if (!@mkdir($cheminCustomMaarchCourrier, 0755)) {
                return false;
            }

            /**
            Création répertoire apps/maarch_entreprise dans le custom
             */
            $cheminCustomMaarchCourrierApps = realpath('.')."/custom/cs_$databasename/apps";
            mkdir($cheminCustomMaarchCourrierApps, 0755);

            $cheminCustomMaarchCourrierAppsMaarchEntreprise = realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise";
            mkdir($cheminCustomMaarchCourrierAppsMaarchEntreprise, 0755);

            $cheminCustomMaarchCourrierAppsMaarchEntrepriseXml = realpath('.')."/custom/cs_$databasename/apps/maarch_entreprise/xml";
            mkdir($cheminCustomMaarchCourrierAppsMaarchEntrepriseXml, 0755);

            /**
            Création répertoire modules dans le custom
             */
            $cheminCustomMaarchCourrierModules = realpath('.')."/custom/cs_$databasename/modules";
            mkdir($cheminCustomMaarchCourrierModules, 0755);

            /** Création répertoire notification dans le custom **/
            $cheminCustomMaarchCourrierModulesNotifications = realpath('.')."/custom/cs_$databasename/modules/notifications";
            mkdir($cheminCustomMaarchCourrierModulesNotifications, 0755);

            $cheminCustomMaarchCourrierModulesNotificationsBatch = realpath('.')."/custom/cs_$databasename/bin/notification";
            mkdir($cheminCustomMaarchCourrierModulesNotificationsBatch, 0755);

            $cheminCustomMaarchCourrierModulesNotificationsConfig = realpath('.')."/custom/cs_$databasename/bin/notification/config";
            mkdir($cheminCustomMaarchCourrierModulesNotificationsConfig, 0755);

            $cheminCustomMaarchCourrierModulesNotificationsScripts = realpath('.')."/custom/cs_$databasename/bin/notification/scripts";
            mkdir($cheminCustomMaarchCourrierModulesNotificationsScripts, 0755);

            /** Création répertoire LDAP dans le custom **/
            $cheminCustomMaarchCourrierModulesLdap = realpath('.')."/custom/cs_$databasename/modules/ldap";
            mkdir($cheminCustomMaarchCourrierModulesLdap, 0755);

            $cheminCustomMaarchCourrierModulesLdapXml = realpath('.')."/custom/cs_$databasename/modules/ldap/xml";
            mkdir($cheminCustomMaarchCourrierModulesLdapXml, 0755);

            $cheminCustomMaarchCourrierModulesLdapScript = realpath('.')."/custom/cs_$databasename/modules/ldap/script";
            mkdir($cheminCustomMaarchCourrierModulesLdapScript, 0755);

            //Création du lien symbolique
            $cmd = 'ln -s '.realpath('.')."/ cs_$databasename";
            exec($cmd);
        }

        return true;
    }

    public function verificationDatabase($databasename)
    {
        $connect = 'host='.$_SESSION['config']['databaseserver'].' ';
        $connect .= 'port='.$_SESSION['config']['databaseserverport'].' ';
        $connect .= 'user='.$_SESSION['config']['databaseuser'].' ';
        $connect .= 'password='.$_SESSION['config']['databasepassword'].' ';
        $connect .= 'dbname=postgres';

        if (!@pg_connect($connect)) {
            return false;
            exit;
        }

        $sqlCreateDatabase = "select datname from pg_database where datname = '".$databasename."'";

        $result = @pg_query($sqlCreateDatabase);
        if (!$result) {
            echo "Une erreur s'est produite.\n";
            exit;
        }

        while ($row = pg_fetch_row($result)) {
            if ($row[0]) {
                return false;
            }
        }

        return true;
    }

    public function verifCustom($databasename)
    {
        $customAlreadyExist = realpath('.').'/custom/cs_'.$databasename;
        if (file_exists($customAlreadyExist)) {
            return false;
        }
    }

    public function fillConfigOfAppAndModule($databasename)
    {
        $_SESSION['config']['databasename'] = $databasename;
        $connect = 'host='.$_SESSION['config']['databaseserver'].' ';
        $connect .= 'port='.$_SESSION['config']['databaseserverport'].' ';
        $connect .= 'user='.$_SESSION['config']['databaseuser'].' ';
        $connect .= 'password='.$_SESSION['config']['databasepassword'].' ';
        $connect .= 'dbname=postgres';

        if (!$this->setConfigXml()) {
            return false;
            exit;
        }

        if (!$this->setScriptNotificationSendmailSh()) {
            return false;
            exit;
        }

        if (!$this->setScriptNotificationNctNccAndAncSh()) {
            return false;
            exit;
        }

        if (!$this->setScriptNotificationBasketsSh()) {
            return false;
            exit;
        }

        if (!$this->setConfig_LDAP()) {
            return false;
            exit;
        }

        if (!$this->setScript_syn_LDAP_sh()) {
            return false;
            exit;
        }

        if (!$this->setConfig_batch_XmlNotifications()) {
            return false;
            exit;
        }

        if (!$this->setLog4php()) {
            return false;
            exit;
        }

        if (!$this->setConfigCron()) {
            return false;
            exit;
        }

        if (!$this->setRight()) {
            return false;
            exit;
        }

        return true;
    }

    public function createDatabase(
        $databasename
    ) {
        $connect = 'host='.$_SESSION['config']['databaseserver'].' ';
        $connect .= 'port='.$_SESSION['config']['databaseserverport'].' ';
        $connect .= 'user='.$_SESSION['config']['databaseuser'].' ';
        $connect .= 'password='.$_SESSION['config']['databasepassword'].' ';
        $connect .= 'dbname=postgres';
        if (!@pg_connect($connect)) {
            return false;
            exit;
        }

        $sqlCreateDatabase = 'CREATE DATABASE "'.$databasename.'"';
        $sqlCreateDatabase .= ' WITH TEMPLATE template0';
        $sqlCreateDatabase .= " ENCODING = 'UTF8'";

        $execute = pg_query($sqlCreateDatabase);
        if (!$execute) {
            return false;
            exit;
        }

        @pg_query('ALTER DATABASE "'.$databasename.'" SET DateStyle =iso, dmy');

        pg_close();

        $db = new Database();

        if (!$db) {
            return false;
            exit;
        }

        if (!$this->executeSQLScript('sql/structure.sql')) {
            return false;
            exit;
        }

        if (!$this->setConfigXml()) {
            return false;
            exit;
        }

        if (!$this->setScriptNotificationSendmailSh()) {
            return false;
            exit;
        }

        if (!$this->setScriptNotificationNctNccAndAncSh()) {
            return false;
            exit;
        }

        if (!$this->setScriptNotificationBasketsSh()) {
            return false;
            exit;
        }

        if (!$this->setConfig_LDAP()) {
            return false;
            exit;
        }

        if (!$this->setScript_syn_LDAP_sh()) {
            return false;
            exit;
        }

        if (!$this->setConfig_batch_XmlNotifications()) {
            return false;
            exit;
        }

        if (!$this->setLog4php()) {
            return false;
            exit;
        }

        if (!$this->setConfigCron()) {
            return false;
            exit;
        }

        if (!$this->setRight()) {
            return false;
            exit;
        }

        return true;
    }

    private function setRight()
    {
        exec('chmod -R 770 *');

        return true;
    }

    private function setConfigCron()
    {
        $output = shell_exec('crontab -l');
        $pathfile = realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/cron_'.$_SESSION['config']['databasename'];
        $file = fopen('custom/cs_'.$_SESSION['config']['databasename'].'/cron_'.$_SESSION['config']['databasename'], 'w+');
        fwrite($file, $output);
        $cron = '

####################################################################################
#                                                                                  #
#                                                                                  #
#                                       '.$_SESSION['config']['databasename'].'    #
#                                                                                  #
#                                                                                  #
####################################################################################


######################notification#################################################

0 10 * * *      '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/BASKETS.sh
0 12 * * *      '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/BASKETS.sh
0 15 * * *      '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/BASKETS.sh

15 10 * * *     '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/nct-ncc-and-anc.sh

30 10 * * *     '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/sendmail.sh
30 12 * * *     '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/sendmail.sh
30 15 * * *     '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/sendmail.sh

';
        fwrite($file, $cron);
        fclose($file);
        exec('cat ' . $pathfile . ' | crontab');

        $output = exec('crontab -l');
        return true;
    }

    private function setLog4php()
    {
        $xmlconfig = simplexml_load_file('apps/maarch_entreprise/xml/log4php.default.xml');
        $appender = $xmlconfig->appender;
        $param = $appender->param;
        $appender->param['value'] = realpath('.').'/fonctionnel.log';

        $appender = $xmlconfig->appender[1];
        $param = $appender->param;
        $appender->param['value'] = realpath('.').'/technique.log';

        $res = $xmlconfig->asXML();
        $fp = @fopen(realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/apps/maarch_entreprise/xml/log4php.xml', 'w+');
        if (!$fp) {
            return false;
            exit;
        }
        $write = fwrite($fp, $res);
        if (!$write) {
            return false;
            exit;
        }

        return true;
    }

    private function setConfigXml()
    {
        $xmlconfig = simplexml_load_file('apps/maarch_entreprise/xml/config.xml.default');

        $CONFIG = $xmlconfig->CONFIG;

        $CONFIG->databaseserver = $_SESSION['config']['databaseserver'];
        $CONFIG->databaseserverport = $_SESSION['config']['databaseserverport'];
        $CONFIG->databasename = $_SESSION['config']['databasename'];
        $CONFIG->databaseuser = $_SESSION['config']['databaseuser'];
        $CONFIG->databasepassword = $_SESSION['config']['databasepassword'];
        $CONFIG->lang = $_SESSION['lang'];
        $res = $xmlconfig->asXML();

        $fp = @fopen(realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/apps/maarch_entreprise/xml/config.xml', 'w+');
        if (!$fp) {
            return false;
            exit;
        }
        $write = fwrite($fp, $res);
        if (!$write) {
            return false;
            exit;
        }

        return true;
    }


    private function setConfig_batch_XmlNotifications()
    {
        $xmlconfig = simplexml_load_file('bin/notification/config/config.xml.default');

        $CONFIG = $xmlconfig->CONFIG;

        $chemin_core = realpath('.').'/core/';

        $CONFIG = $xmlconfig->CONFIG;
        $CONFIG->MaarchDirectory = realpath('.').'/';

        if ($_SERVER['SERVER_ADDR'] == '::1') {
            $SERVER_ADDR = 'localhost';
        } else {
            $SERVER_ADDR = $_SERVER['SERVER_ADDR'];
        }
        $chemin = $SERVER_ADDR.dirname($_SERVER['PHP_SELF'].'cs_'.$_SESSION['config']['databasename']);
        $maarchUrl = rtrim($chemin, 'install');
        $maarchUrl = $maarchUrl.'cs_'.$_SESSION['config']['databasename'].'/';
        $CONFIG->MaarchUrl    = $maarchUrl;
        $CONFIG->customID     = 'cs_'.$_SESSION['config']['databasename'];

        $res = $xmlconfig->asXML();
        $fp = @fopen(realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/config/config.xml', 'w+');
        if (!$fp) {
            return false;
            exit;
        }
        $write = fwrite($fp, $res);
        if (!$write) {
            return false;
            exit;
        }

        return true;
    }

    private function setConfig_LDAP()
    {
        $xmlconfig = simplexml_load_file('modules/ldap/xml/config.xml.default');
        $CONFIG_BASE = $xmlconfig->config_base;

        $CONFIG_BASE->databaseserver = $_SESSION['config']['databaseserver'];
        $CONFIG_BASE->databaseserverport = $_SESSION['config']['databaseserverport'];
        $CONFIG_BASE->databasename = $_SESSION['config']['databasename'];
        $CONFIG_BASE->databaseuser = $_SESSION['config']['databaseuser'];
        $CONFIG_BASE->databasepassword = $_SESSION['config']['databasepassword'];
        $res = $xmlconfig->asXML();
        $fp = @fopen(realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/modules/ldap/xml/config.xml', 'w+');
        if (!$fp) {
            var_dump('fp error');

            return false;
            exit;
        }
        $write = fwrite($fp, $res);
        if (!$write) {
            var_dump('write error');

            return false;
            exit;
        }

        return true;
    }

    private function setScript_syn_LDAP_sh()
    {
        $res = '#!/bin/bash';
        $res .= "\n";
        $res .= 'cd '.realpath('.').'/modules/ldap/script/';
        $res .= "\n\n";
        $res .= '#generation des fichiers xml';
        $res .= "\n";
        $res .= 'php '.realpath('.').'/modules/ldap/process_ldap_to_xml.php '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/modules/ldap/xml/config.xml';
        $res .= "\n\n";
        $res .= '#mise a jour bdd';
        $res .= "\n";
        $res .= 'php '.realpath('.').'/modules/ldap/process_entities_to_maarch.php '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/modules/ldap/xml/config.xml';
        $res .= "\n";
        $res .= 'php '.realpath('.').'/modules/ldap/process_users_to_maarch.php '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/modules/ldap/xml/config.xml';
        $res .= "\n";
        $res .= 'php '.realpath('.').'/modules/ldap/process_users_entities_to_maarch.php '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/modules/ldap/xml/config.xml';

        $fp = @fopen(realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/modules/ldap/script/syn_ldap.sh', 'w+');
        if (!$fp) {
            var_dump('false error dans setScript_syn_LDAP_sh()');

            return false;
            exit;
        }
        $write = fwrite($fp, $res);
        if (!$write) {
            return false;
            exit;
        }

        return true;
    }

    private function setScriptNotificationNctNccAndAncSh()
    {
        $res = '#!/bin/bash';
        $res .= "\n";
        $res .= "eventStackPath='".realpath('.')."/bin/notification/process_event_stack.php'";
        $res .= "\n";
        $res .= 'cd '.realpath('.').'/bin/notification/';
        $res .= "\n";
        $res .= '#php $eventStackPath -c '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/config/config.xml -n NCT';
        $res .= "\n";
        $res .= '#php $eventStackPath -c '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/config/config.xml -n NCC';
        $res .= "\n";
        $res .= 'php $eventStackPath -c '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/config/config.xml -n ANC';
        $res .= "\n";
        $res .= 'php $eventStackPath -c '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/config/config.xml -n AND';
        $res .= "\n";
        $res .= 'php $eventStackPath -c '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/config/config.xml -n RED';

        $fp = @fopen(realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/nct-ncc-and-anc.sh', 'w+');
        if (!$fp) {
            return false;
            exit;
        }
        $write = fwrite($fp, $res);
        if (!$write) {
            return false;
            exit;
        }

        return true;
    }

    private function setScriptNotificationBasketsSh()
    {
        $res = '#!/bin/bash';
        $res .= "\n";
        $res .= "eventStackPath='".realpath('.')."/bin/notification/basket_event_stack.php'";
        $res .= "\n";
        $res .= 'cd '.realpath('.').'/bin/notification/';
        $res .= "\n";
        $res .= 'php $eventStackPath -c '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/config/config.xml -n BASKETS';
        $res .= "\n";

        $fp = @fopen(realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/BASKETS.sh', 'w+');
        if (!$fp) {
            return false;
            exit;
        }
        $write = fwrite($fp, $res);
        if (!$write) {
            return false;
            exit;
        }

        return true;
    }

    private function setScriptNotificationSendmailSh()
    {
        $res = '#!/bin/bash';
        $res .= "\n";
        $res .= 'cd '.realpath('.').'/bin/notification/';
        $res .= "\n";
        $res .= "emailStackPath='".realpath('.')."/bin/notification/process_email_stack.php'";
        $res .= "\n";
        $res .= 'php $emailStackPath -c '.realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/config/config.xml';

        $fp = fopen(realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/scripts/sendmail.sh', 'w+');

        if (!$fp) {
            return false;
            exit;
        }
        $write = fwrite($fp, $res);
        if (!$write) {
            return false;
            exit;
        }

        return true;
    }

    public function getDataList()
    {
        $sqlList = array();
        foreach (glob('sql/data*.sql') as $fileSqlPath) {
            $sqlFile = str_replace('.sql', '', end(explode('/', $fileSqlPath)));
            array_push($sqlList, $sqlFile);
        }

        return $sqlList;
    }

    public function createData(
        $dataFile
    ) {
        $db = new Database();

        if (!$db) {
            return false;
            exit;
        }

        if (!$this->executeSQLScript($dataFile)) {
            return false;
            exit;
        }

        return true;
    }

    public function executeSQLScript($filePath)
    {
        $fileContent = fread(fopen($filePath, 'r'), filesize($filePath));
        $db = new Database();

        $execute = $db->query($fileContent, null, true, true);

        if (!$execute) {
            return false;
            exit;
        }

        return true;
    }

    /**
     * test if maarch path is writable.
     *
     * @return bool or error message
     */
    public function isMaarchPathWritable()
    {
        if (!is_writable('.')
                || !is_readable('.')
        ) {
            $error .= _THE_MAARCH_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS;
        } else {
            return true;
        }
    }

    /**
     * test if unoconv is installed.
     *
     * @return bool or error message
     */
    public function isUnoconvInstalled()
    {
        exec('whereis unoconv', $output, $return);
        $output = explode(':', $output[0]);

        if (empty($output[1])) {
            $error .= _UNOCONV_NOT_INSTALLED;
        } else {
            return true;
        }
    }

    /**
     * test if netcat or nmap is installed.
     *
     * @return bool or error message
     */
    public function isNetCatOrNmapInstalled()
    {
        exec('whereis netcat', $output, $return);
        $output = explode(':', $output[0]);

        exec('whereis nmap', $output2, $return2);
        $output2 = explode(':', $output2[0]);

        if (empty($output[1]) && empty($output2[1])) {
            $error .= _NETCAT_OR_NMAP_NOT_INSTALLED;
        } else {
            return true;
        }
    }

    /**
     * test if vendor and node_modules exist.
     *
     * @return bool or error message
     */
    public function isDependenciesExist()
    {
        if (file_exists('vendor/') && file_exists('node_modules/')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * test if path is read/write.
     *
     * @param $path string path
     *
     * @return bool or error message
     */
    public function checkPathRoot($path)
    {
        if (!is_dir($path)) {
            $error .= _PATH_UNAPPROACHABLE . ' ' . $path;
        } else {
            if (!is_writable($path)
                || !is_readable($path)
            ) {
                $error .= _THE_PATH_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS . ' ' . $path;
            }
        }
        if ($error != '') {
            $error .= '<br/>' . _CREATES_OR_UPDATES_RIGHT_ON_PATH;
            $error .= ' <b>' . dirname($path) . '</b>';
            return $error;
        } else {
            return true;
        }
    }

    /**
     * create the path.
     *
     * @param $path string path
     *
     * @return bool
     */
    public function createPath($path)
    {
        if (!is_dir($path)) {
            if (!mkdir($path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * create the docservers.
     *
     * @param $docserverPath string path to the docserver
     *
     * @return bool
     */
    public function createDocservers($docserverPath)
    {
        for ($i = 0; $i < count($this->docservers); ++$i) {
            if (!is_dir(
                $docserverPath.DIRECTORY_SEPARATOR
                    .$this->docservers[$i][1]
            )
            ) {
                if (!mkdir(
                    $docserverPath.DIRECTORY_SEPARATOR
                        .$this->docservers[$i][1]
                )
                ) {
                    return false;
                }
            }
        }

        //create indexes dir
        if (!is_dir(
            $docserverPath.DIRECTORY_SEPARATOR
                .'indexes'
        )
        ) {
            if (!mkdir(
                $docserverPath.DIRECTORY_SEPARATOR
                    .'indexes'
            )
            ) {
                return false;
            }
        }
        //create indexes dir for letterbox collection
        if (!is_dir(
            $docserverPath.DIRECTORY_SEPARATOR
                .'indexes'.DIRECTORY_SEPARATOR.'letterbox_coll'
        )
        ) {
            if (!mkdir(
                $docserverPath.DIRECTORY_SEPARATOR
                    .'indexes'.DIRECTORY_SEPARATOR.'letterbox_coll'
            )
            ) {
                return false;
            }
        }

        //copy template files
        $dir2copy = 'install'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'0000'.DIRECTORY_SEPARATOR;
        $dir_paste = $docserverPath.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'0000'.DIRECTORY_SEPARATOR;

        $this->copy_dir($dir2copy, $dir_paste);

        return true;
    }

    /**
     * update the docservers on DB.
     *
     * @param $docserverPath string path to the docserver
     *
     * @return nothing
     */
    public function updateDocserversDB($docserverPath)
    {
        $db = new Database();

        for ($i = 0; $i < count($this->docservers); ++$i) {
            $query = 'update docservers set path_template = ?'
                .' where docserver_id = ?';
            $db->query(
                $query,
                array(
                    $db->protect_string_db($docserverPath.DIRECTORY_SEPARATOR
                        .$this->docservers[$i][1].DIRECTORY_SEPARATOR),
                    $this->docservers[$i][0],
                )
            );
        }
    }

    public function setSuperadminPass($newPass)
    {
        $db = new Database();
        $sec = new security();

        $query = "UPDATE users SET password=? WHERE user_id='superadmin'";
        $db->query($query, array($sec->getPasswordHash($newPass)));
    }

    public function copy_dir($dir2copy, $dir_paste, $excludeExt = false, $excludeSymlink = false, $excludeDirectories = [])
    {
        // On vérifie si $dir2copy est un dossier
        if (is_dir($dir2copy)) {
            // Si oui, on l'ouvre
            if ($dh = opendir($dir2copy)) {
                $copyIt = true;
                // On liste les dossiers et fichiers de $dir2copy
                while (($file = readdir($dh)) !== false) {
                    $copyIt = true;
                    // Si le dossier dans lequel on veut coller n'existe pas, on le cree
                    if (!is_dir($dir_paste)) {
                        mkdir($dir_paste, 0777);
                    }
                    // S'il s'agit d'un dossier, on relance la fonction recursive
                    if ($excludeSymlink) {
                        if (is_dir($dir2copy.$file) && $file != '..' && $file != '.' && !is_link($dir2copy.$file) && !in_array($file, $excludeDirectories)) {
                            if (!$this->copy_dir($dir2copy.$file.'/', $dir_paste.$file.'/', $excludeExt, $excludeSymlink, $excludeDirectories)) {
                                return false;
                            }
                        } elseif ($file != '..' && $file != '.' && !is_link($dir2copy.$file) && !in_array($file, $excludeDirectories)) {
                            if (is_array($excludeExt) && count($excludeExt)>0) {
                                $copyIt = true;
                                foreach ($excludeExt as $key => $value) {
                                    if (strtolower($value) == strtolower(pathinfo($dir2copy.$file, PATHINFO_EXTENSION))) {
                                        $copyIt = false;
                                    }
                                }
                            }
                            if ($copyIt) {
                                if (!@copy($dir2copy.$file, $dir_paste.$file)) {
                                    return false;
                                }
                            }
                        }
                    } else {
                        if (is_dir($dir2copy.$file) && $file != '..' && $file != '.' && !in_array($file, $excludeDirectories)) {
                            if (!$this->copy_dir($dir2copy.$file.'/', $dir_paste.$file.'/', $excludeExt, $excludeSymlink, $excludeDirectories)) {
                                return false;
                            }
                        } elseif ($file != '..' && $file != '.' && !in_array($file, $excludeDirectories)) {
                            if (is_array($excludeExt) && count($excludeExt) > 0) {
                                $copyIt = true;
                                foreach ($excludeExt as $key => $value) {
                                    if (strtolower($value) == strtolower(pathinfo($dir2copy.$file, PATHINFO_EXTENSION))) {
                                        $copyIt = false;
                                    }
                                }
                            }
                            if ($copyIt) {
                                if (!@copy($dir2copy.$file, $dir_paste.$file)) {
                                    return false;
                                }
                            }
                        }
                    }
                }
                // On ferme $dir2copy
                closedir($dh);
            }
        }

        return true;
    }
}
