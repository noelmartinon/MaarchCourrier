<?php

/*
*   Copyright 2008-2017 Maarch
*
*   This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with Maarch Framework. If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief Contains the functions to manage content_management directory and expiration
*
* @file
* @author Laurent Giovannoni
* @date $date$
* @version $Revision$
* @ingroup content_management
*/

require_once 'core/class/class_functions.php';
require_once 'core/class/class_db_pdo.php';
require_once 'core/class/docservers_controler.php';
require_once 'core/class/class_security.php';
require_once 'core/core_tables.php';

abstract class content_management_tools_Abstract
{
    //Parameters
    protected $extensions_xml_path = 'xml/extensions.xml';
    protected $programs_xml_path = 'xml/programs.xml';
    protected $parameter_id  = 'content_management_reservation';
    protected $templateMasterPath = 'modules/templates/templates_src/';
    //Variables
    protected $db;

    public function __construct()
    {
        if (!isset($_SESSION) OR count($_SESSION) == 0)
            return null;

        $this->db = new Database();
        //TODO: PUT IT AN CONFIG FILE WITH 30
        $_SESSION['config']['content_management_reserved_time'] = 30;
        if (!is_dir('modules/content_management/tmp/')) {
            mkdir('modules/content_management/tmp/');
        }
    }
    
    public function getCmParameters()
    {
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom/'
            . $_SESSION['custom_override_id'] 
            . '/modules/content_management/xml/content_management_features.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom/'
                . $_SESSION['custom_override_id'] 
                . '/modules/content_management/xml/content_management_features.xml';
        } else {
            $path = $_SESSION['config']['corepath'] 
                . 'modules/content_management/xml/content_management_features.xml';
        }
        $cMFeatures = array();
        if (file_exists($path)) {
            $func = new functions();
            $cMFeatures = $func->object2array(
                simplexml_load_file($path)
            );
        } else {
            $cMFeatures['CONFIG']['psExecMode'] = 'KO';
            $cMFeatures['CONFIG']['userMaarchOnClient'] = '';
            $cMFeatures['CONFIG']['userPwdMaarchOnClient'] = '';
        }
        return $cMFeatures;
    }

    /**
    * Returns who reserved the resource
    *
    * @param  string $objectTable res table, attachment table, model table, ...
    * @param  bigint $objectId id of the object res_id, model_id, ...
    * @return array the user who reserved the resource, else false
    */
    public function isReservedBy($objectTable, $objectId)
    {
        $timeLimit = $this->computeTimeLimit();
        $charTofind = $this->parameter_id . '#%#' . $objectTable . '#' . $objectId;

        $query = "select id from " . PARAM_TABLE . " where id like (?) and param_value_int > ?";

        $stmt = $this->db->query($query, array($charTofind, $timeLimit));
        
        if ($res = $stmt->fetchObject()) {

            $arrayUser = array();
            $arrayUser = explode("#", $res->id);
            if ($arrayUser[1] <> '') {
                $query = "select user_id, lastname, firstname "
                    . "from " . USERS_TABLE . " where user_id = ? and enabled = 'Y'";
                
                $stmt = $this->db->query($query, array($arrayUser[1]));
                
                $arrayReturn = array();
                if ($resUser = $stmt->fetchObject()) {
                    $arrayReturn['fullname'] = $resUser->firstname . ' '
                        . $resUser->lastname;
                    $arrayReturn['user_id'] = $resUser->user_id;
                } else {
                    $arrayReturn['fullname'] = 'empty';
                }
                $arrayReturn['status'] = 'ok';
                return $arrayReturn;
            } else {
                $arrayReturn['status'] = 'ko';
            }
        } else {
            $arrayReturn['status'] = 'ko';
        }
        return $arrayReturn;
    }

    /**
    * Close the content_management reservation
    *
    * @param string $CMId content_management id
    * @return nothing
    */
    public function closeReservation($CMId)
    {
        $query = "delete from " . PARAM_TABLE
            . " where id = ?";
        $stmt = $this->db->query($query, array($CMId));
    }

    /**
    * Update the expiration date of the content_management reservation for the connected user
    *
    * @param  string $CMId the content_management id
    * @param  string $userId the content_management id
    * @return nothing
    */
    public function updateExpiryDate($CMId, $userId)
    {
        $timeLimit = $this->computeTimeLimit() + (
            $_SESSION['config']['content_management_reserved_time'] * 60
        );
        $charTofind = $this->parameter_id . '#' . $userId . '%';
        $query = "update " . PARAM_TABLE
               . " set param_value_int = ? "
               . " where id like ?"
               . " and param_value_string = ?";
        $stmt = $this->db->query(
            $query, 
            array($timeLimit, $charTofind, $CMId)
        );
    }

    /**
    * Reserved the object for content_management
    * Add an expiration date of the content_management reservation for the connected user
    *
    * @param  string $objectTable the res table
    * @param  string $objectId the res_id
    * @param  string $CMId the content_management id
    * @param  string $userId the content_management id
    * @return string the reservation id
    */
    public function reserveObject($objectTable, $objectId, $userId)
    {
        $timeLimit = $this->computeTimeLimit() + (
            $_SESSION['config']['content_management_reserved_time'] * 60
        );
        //If exists Delete
        $charTofind = $this->parameter_id . '#' . $userId . '#' . $objectTable
                    . '#' . $objectId;
        $query = "delete from " . PARAM_TABLE
               . " where id = ?";
        $stmt = $this->db->query($query, array($charTofind));
        $query = "insert into " . PARAM_TABLE
               . " (id, param_value_int)"
               . " values(?, ?)";
        $stmt = $this->db->query($query, array($charTofind, $timeLimit));
        return $charTofind;
    }

    /**
    * Delete the resource in the tmp content_management dir if necessary
    *
    * @return nothing
    */
    public function deleteExpiredCM()
    {
        $timeLimit = $this->computeTimeLimit();
        $query = "delete from " . PARAM_TABLE
            . " where param_value_int < ? "
            . " and id like ? ";
        $stmt = $this->db->query($query, array($timeLimit, $this->parameter_id . '%'));
    }
    
    /**
    * Delete the resource for the disconnected user
    *
    * @return nothing
    */
    public function deleteUserCM()
    {
        $query = "delete from " . PARAM_TABLE
            . " where id like ?";
        $stmt = $this->db->query($query, array('content_management_reservation#' 
            . $_SESSION['user']['UserId'] . '%')
        );
    }

    /**
    * Delete the content_management tmp if necessary
    *
    * @param string $dir path to the tmp dir
    * @return nothing
    */
    protected function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir) || is_link($dir)) return unlink($dir);
            foreach (scandir($dir) as $item) {
                if ($item == '.' || $item == '..') continue;
                if (!$this->deleteDirectory($dir . "/" . $item)) {
                    chmod($dir . "/" . $item, 0777);
                    if (!$this->deleteDirectory($dir . "/" . $item)) return false;
                };
            }
            return rmdir($dir);
    }

    /**
    * Returns time before expiration of the content_management reservation
    *
    * @param  string $CMId the content_management id
    * @return bigint the time in secon before expiration
    */
    public function timeBeforeExpiration($CMId)
    {
        $now = $this->computeTimeLimit();
        $charTofind = $this->parameter_id . '%';
        $query = "select param_value_int as time"
               . " from " . PARAM_TABLE
               . " where id like ?"
               . " and param_value_string = ?";
        $stmt = $this->db->query($query, array($charTofind, $CMId));
        if ($res = $stmt->fetchObject()) {
            $secBeforeExpiration = $res->time - $now;
            if ($secBeforeExpiration < 0)  {
                return 0;
            } else {
                return $secBeforeExpiration;
            }
        } else {
            return 0;
        }
    }

    /**
    * Returns the program to update the resource with content_management
    *
    * @param  string $mimeType mime type of the resource
    * @return array the program and status ok if mime type allowed for content_management
    */
    public function isMimeTypeAllowedForCM($mimeType, $ext) {
        $typeState = 'ko';
        $programPath = '';
        if ($mimeType <> '' && $ext <> '') {
            $path = $_SESSION['config']['corepath'] . 'custom/'
                  . $_SESSION['custom_override_id'] . '/apps/'
                  . $_SESSION['config']['app_id'] . '/xml/extensions.xml';
            if (!file_exists($path)) {
                $path =  $_SESSION['config']['corepath'] . '/apps/'
                      . $_SESSION['config']['app_id'] . '/xml/extensions.xml';
            }
            $xmlconfig = simplexml_load_file($path);
            $extList = array();
            $i = 0;
            foreach ($xmlconfig->FORMAT as $FORMAT) {
                $extList[$i] = array(
                    'name' => (string) $FORMAT->name,
                    'mime' => (string) $FORMAT->mime,
                    'web_dav_update' => (string) $FORMAT->web_dav_update,
                    'default_program' => (string) $FORMAT->default_program,
                );
                $i++;
            }
            for ($i=0;$i<count($extList);$i++) {
                if (
                    $extList[$i]['mime'] == $mimeType
                    && strtolower($extList[$i]['name']) == strtolower($ext)
                    && strtolower($extList[$i]['web_dav_update']) == 'true'
                ) {
                    $typeState = 'ok';
                    $programPath = $extList[$i]['default_program'];
                    break;
                }
            }
        }
        $arrayReturn = array(
            'status' => $typeState,
            'programPath' => $programPath,
        );
        return $arrayReturn;
    }

    /**
    * Compute the time limit for a content_management session
    *
    * @return string the time limit in timestamp
    */
    public function computeTimeLimit()
    {
        $timeLimit = mktime(
            date('H'),
            date('i'),
            date('s'),
            date('m'),
            date('d'),
            date('Y')
        );
        return $timeLimit;
    }

    /**
    * Generate JLNP file to launch the JNLP
    *
    * 
    */
    public function generateJNLP(
        $jar_url,
        $maarchcm_url,
        $objectType,
        $objectTable,
        $objectId,
        $uniqueId,
        $cookieKey,
        $user,
        $clientSideCookies,
        $convertPdf = "false",
        $onlyConvert = "false"
    ) {
        
        $docXML = new DomDocument('1.0', "UTF-8");

        //create unique id for APPLET
        $uid_applet_name = $_SESSION['user']['UserId'].'_maarchCM_'.rand();

        $jnlp_name = $uid_applet_name.'.jnlp';

        $loadedXml = \SrcCore\models\CoreConfigModel::getXmlLoaded(['path' => 'modules/content_management/xml/config.xml']);

        $jar_path = $jar_url;
        if ($loadedXml && !empty((string) $loadedXml->CONFIG[0]->jar_path)) {
            $jar_path = (string) $loadedXml->CONFIG[0]->jar_path;
        }

        $hashFile = 0;
        if ($objectType == 'attachmentUpVersion') {
            $dbAttachment = new Database();
            $query = "SELECT relation, docserver_id, path, filename, format 
            FROM res_view_attachments 
            WHERE (res_id = ? OR res_id_version = ?) AND res_id_master = ? ORDER BY relation desc";

            $stmt = $dbAttachment->query($query, array($objectId, $objectId, $_SESSION['doc_id']));

            if ($stmt->rowCount() > 0) {
                $line = $stmt->fetchObject();
                $docserver = $line->docserver_id;
                $path = $line->path;
                $filename = $line->filename;
                $query = "select path_template from docservers where docserver_id = ?";
                $stmt = $dbAttachment->query($query, array($docserver));
                $lineDoc = $stmt->fetchObject();
                $docserver = $lineDoc->path_template;
                $fileOnDs = $docserver . $path . $filename;
                $fileOnDs = str_replace('#', DIRECTORY_SEPARATOR, $fileOnDs);
                $hashFile = hash_file('md5', $fileOnDs);
            }
        }

        if ($_SESSION['config']['debug']) {
            $inF = fopen(
                $_SESSION['config']['tmppath'] . 'log_jnlp_' . $_SESSION['user']['UserId'] . '.log',
                'a'
            );
            fwrite(
                $inF, 
                '------------------' . PHP_EOL
                . 'CREATE JNLP------------------'
                . $_SERVER['SERVER_NAME'] . ' ' . $_SESSION['user']['UserId'] . ' ' . date('D, j M Y H:i:s O') .PHP_EOL
            );
            fwrite($inF, '|||||||||||||||||SERVER DETAILS BEGIN FOR CREATE JNLP|||||||||||||||||' . PHP_EOL);
            foreach($_SERVER as $key => $value) {
                fwrite($inF, $key . " : " . $value . PHP_EOL);
            }
            fwrite($inF, '|||||||||||||||||SERVER DETAILS END FOR CREATE JNLP|||||||||||||||||' . PHP_EOL);
            fwrite($inF, "jar_url : " . $jar_url . PHP_EOL);
            fwrite($inF, "jar_path : " . $jar_path . PHP_EOL);
            fwrite($inF, "jnlp_name : " . $jnlp_name . PHP_EOL);
            fwrite($inF, "maarchcm_url : " . $maarchcm_url . PHP_EOL);
            fwrite($inF, "objectType : " . $objectType . PHP_EOL);
            fwrite($inF, "objectTable : " . $objectTable . PHP_EOL);
            fwrite($inF, "objectId : " . $objectId . PHP_EOL);
            fwrite($inF, "uniqueId : " . $uniqueId . PHP_EOL);
            fwrite($inF, "cookieKey : " . $cookieKey . PHP_EOL);
            fwrite($inF, "idApplet : " . $idApplet . PHP_EOL);
            fwrite($inF, "clientSideCookies : " . $clientSideCookies . PHP_EOL);
            fwrite($inF, "user : " . $user . PHP_EOL);
            fwrite($inF, "convertPdf : " . $convertPdf . PHP_EOL);
            fwrite($inF, "onlyConvert : " . $onlyConvert . PHP_EOL);
            $listArguments = '?url=' . urlencode($maarchcm_url)
                . '&objectType=' . $objectType
                . '&objectTable=' . $objectTable
                . '&objectId=' . $objectId
                . '&uniqueId=' . $uniqueId
                . '&cookie=' . $cookieKey
                . '&clientSideCookies=' . $clientSideCookies
                . '&idApplet=' . $uid_applet_name
                . '&userMaarch=' . $user
                . '&convertPdf=' . $convertPdf
                . '&onlyConvert=' . $onlyConvert;
            fwrite($inF, "listArguments : " . $listArguments . PHP_EOL);
            fclose($inF);
        }
        
        $jnlp_balise=$docXML->createElement("jnlp");
        $jnlp_attribute1 = $docXML->createAttribute('spec'); 
        $jnlp_attribute1->value = '6.0+';
        $jnlp_balise->appendChild($jnlp_attribute1); 
        $jnlp_attribute2 = $docXML->createAttribute('codebase'); 
        $jnlp_attribute2->value = $jar_url.'/apps/maarch_entreprise/tmp/';
        $jnlp_balise->appendChild($jnlp_attribute2);

        $info_balise=$docXML->createElement("information");

        $title_balise=$docXML->createElement("title","Editeur de modèle de document");

        $vendor_balise=$docXML->createElement("vendor","MAARCH");

        $homepage_balise=$docXML->createElement("homepage");
        $homepage_attribute = $docXML->createAttribute('href');
        $homepage_attribute->value = 'http://maarch.com';
        $homepage_balise->appendChild($homepage_attribute);

        $desc_balise=$docXML->createElement("description","Génère votre document avec méta-données associées au courrier grâce à des champs de fusion.");
        
        $descshort_balise=$docXML->createElement("description","Génère votre document avec méta-données.");
        $descshort_attribute = $docXML->createAttribute('kind');
        $descshort_attribute->value = 'short';
        $descshort_balise->appendChild($descshort_attribute);

        $offline_balise=$docXML->createElement("offline-allowed");

        $security_balise=$docXML->createElement("security");

        $permission_balise=$docXML->createElement("all-permissions");

        $resources_balise=$docXML->createElement("resources");

        $j2se_balise=$docXML->createElement("j2se");
        $j2se_attribute = $docXML->createAttribute('version');
        $j2se_attribute->value = '1.6+';
        $j2se_balise->appendChild($j2se_attribute);

        $jar_balise=$docXML->createElement("jar");
        $jar_attribute = $docXML->createAttribute('href');
        $jar_attribute->value = $jar_path.'/modules/content_management/dist/maarchCM.jar';
        $jar_balise->appendChild($jar_attribute);
        $jar_attribute = $docXML->createAttribute('main');
        $jar_attribute->value = 'true';
        $jar_balise->appendChild($jar_attribute);

        //begin ext libs
        $jar_balise_1=$docXML->createElement("jar");
        $jar_attribute = $docXML->createAttribute('href');
        $jar_attribute->value = $jar_path.'/modules/content_management/dist/lib/httpclient-4.5.2.jar';
        $jar_balise_1->appendChild($jar_attribute);

        $jar_balise_2=$docXML->createElement("jar");
        $jar_attribute = $docXML->createAttribute('href');
        $jar_attribute->value = $jar_path.'/modules/content_management/dist/lib/httpclient-cache-4.5.2.jar';
        $jar_balise_2->appendChild($jar_attribute);

        $jar_balise_3=$docXML->createElement("jar");
        $jar_attribute = $docXML->createAttribute('href');
        $jar_attribute->value = $jar_path.'/modules/content_management/dist/lib/httpclient-win-4.5.2.jar';
        $jar_balise_3->appendChild($jar_attribute);

        $jar_balise_4=$docXML->createElement("jar");
        $jar_attribute = $docXML->createAttribute('href');
        $jar_attribute->value = $jar_path.'/modules/content_management/dist/lib/httpcore-4.4.4.jar';
        $jar_balise_4->appendChild($jar_attribute);

        $jar_balise_5=$docXML->createElement("jar");
        $jar_attribute = $docXML->createAttribute('href');
        $jar_attribute->value = $jar_path.'/modules/content_management/dist/lib/plugin.jar';
        $jar_balise_5->appendChild($jar_attribute);

        $jar_balise_6=$docXML->createElement("jar");
        $jar_attribute = $docXML->createAttribute('href');
        $jar_attribute->value = $jar_path.'/modules/content_management/dist/lib/commons-logging-1.2.jar';
        $jar_balise_6->appendChild($jar_attribute);
        //end ext libs

        //$applet_balise=$docXML->createElement("applet-desc");
        $applet_balise=$docXML->createElement("application-desc");
        $applet_attribute1 = $docXML->createAttribute('main-class');
        $applet_attribute1->value = 'com.maarch.MaarchCM';
        $applet_balise->appendChild($applet_attribute1);

        //arguments
        $param1_balise = $docXML->createElement("argument", $maarchcm_url);

        if (empty($objectType)) {
            $objectType = 'empty';
        }
        $param2_balise=$docXML->createElement("argument", htmlentities($objectType));

        if (empty($objectTable)) {
            $objectTable = 'empty';
        }
        $param3_balise=$docXML->createElement("argument", htmlentities($objectTable));

        if (empty($objectId)) {
            $objectId = 'empty';
        }
        $param4_balise=$docXML->createElement("argument", htmlentities($objectId));

        if ($uniqueId < 0) {
            $uniqueId = 'empty';
        }
        $param5_balise=$docXML->createElement("argument", htmlentities($uniqueId));

        if (empty($cookieKey)) {
            $cookieKey = 'empty';
        }
        $param6_balise=$docXML->createElement("argument", htmlentities($cookieKey));

        if (empty($clientSideCookies)) {
            $clientSideCookies = 'empty';
        }
        $param7_balise=$docXML->createElement("argument", htmlentities($clientSideCookies));

        if (empty($uid_applet_name)) {
            $uid_applet_name = 'empty';
        }
        $param8_balise=$docXML->createElement("argument", htmlentities($uid_applet_name));

        if (empty($user)) {
            $user = 'empty';
        }
        $param9_balise=$docXML->createElement("argument", htmlentities($user));

        if (empty($convertPdf)) {
            $convertPdf = 'false';
        }
        $param10_balise=$docXML->createElement("argument", htmlentities($convertPdf));

        if (empty($onlyConvert)) {
            $onlyConvert = 'false';
        }
        $param11_balise=$docXML->createElement("argument", htmlentities($onlyConvert));
        $param12_balise=$docXML->createElement("argument", $hashFile);


        $jnlp_balise->appendChild($info_balise); 
        $info_balise->appendChild($title_balise); 
        $info_balise->appendChild($vendor_balise); 
        $info_balise->appendChild($homepage_balise); 
        $info_balise->appendChild($desc_balise); 
        $info_balise->appendChild($descshort_balise); 
        $info_balise->appendChild($offline_balise); 

        $jnlp_balise->appendChild($security_balise); 
        $security_balise->appendChild($permission_balise); 

        $jnlp_balise->appendChild($resources_balise); 
        $resources_balise->appendChild($j2se_balise); 
        $resources_balise->appendChild($jar_balise); 
        $resources_balise->appendChild($jar_balise_1);
        $resources_balise->appendChild($jar_balise_2);
        $resources_balise->appendChild($jar_balise_3);
        $resources_balise->appendChild($jar_balise_4);
        $resources_balise->appendChild($jar_balise_5);
        $resources_balise->appendChild($jar_balise_6);

        $jnlp_balise->appendChild($applet_balise);
        $applet_balise->appendChild($param1_balise);
        $applet_balise->appendChild($param2_balise);
        $applet_balise->appendChild($param3_balise);
        $applet_balise->appendChild($param4_balise);
        $applet_balise->appendChild($param5_balise);
        $applet_balise->appendChild($param6_balise);
        $applet_balise->appendChild($param7_balise);
        $applet_balise->appendChild($param8_balise);
        $applet_balise->appendChild($param9_balise);
        $applet_balise->appendChild($param10_balise);
        $applet_balise->appendChild($param11_balise);
        $applet_balise->appendChild($param12_balise);

        $docXML->appendChild($jnlp_balise);  

        $filename = $_SESSION['config']['tmppath'].$jnlp_name;

        $docXML->save($filename); 

        $fp = fopen($_SESSION['config']['tmppath'].$uid_applet_name.".lck", 'w+');

        $_SESSION['cm_applet'][$_SESSION['user']['UserId']][$uid_applet_name]=$uid_applet_name.'.lck';

        $file = $jar_url."/apps/maarch_entreprise/tmp/".$jnlp_name;

        //echo '<a id="jnlp_file" href="'.$file.'" onclick="window.location.href=\''.$file.'\';self.close();"></a>';
        echo '<script>window.location.href=\''.$file.'\';if($(\'CMApplet\')) {destroyModal(\'CMApplet\');};if($(\'CMApplet\')) {destroyModal(\'CMApplet\');};</script>';
        /*echo '<a id="jnlp_file" href="'.$_SESSION['config']['businessappurl'].'index.php?page=get_jnlp_file&module=content_management&display=true&filename='.$_SESSION['user']['UserId'].'_maarchCM"></a>';
        echo '<script>setTimeout(function() {this.window.close();}, 5000);document.getElementById("jnlp_file").click();</script>';
        exit();*/
    }
}
