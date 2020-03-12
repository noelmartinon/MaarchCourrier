<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   class_modules_tools_Abstract
* @author  dev <dev@maarch.org>
* @ingroup sendmail
*/

try {
    include_once "core/class/class_db.php";
    include_once "core/class/class_security.php";
} catch (Exception $e) {
    functions::xecho($e->getMessage()).' // ';
}


abstract class SendmailAbstract extends Database
{
    /**
     * Build Maarch module tables into sessions vars with a xml configuration
     * file
     */
    public function build_modules_tables()
    {
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . "modules"
            . DIRECTORY_SEPARATOR . "sendmail" . DIRECTORY_SEPARATOR . "xml"
            . DIRECTORY_SEPARATOR . "config.xml"
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom'
                . DIRECTORY_SEPARATOR . $_SESSION['custom_override_id']
                . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR
                . "sendmail" . DIRECTORY_SEPARATOR . "xml" . DIRECTORY_SEPARATOR
                . "config.xml";
        } else {
            $path = "modules" . DIRECTORY_SEPARATOR . "sendmail"
                . DIRECTORY_SEPARATOR . "xml" . DIRECTORY_SEPARATOR
                . "config.xml";
        }
        $xmlconfig = simplexml_load_file($path);
        $_SESSION['sendmail'] = array();

        //Lang file
        include_once 'modules' . DIRECTORY_SEPARATOR . 'sendmail'
            . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR
            . $_SESSION['config']['lang'] . '.php';

        //History
        $hist = $xmlconfig->HISTORY;
        $_SESSION['history']['mailadd'] = (string) $hist->mailadd;
        $_SESSION['history']['mailup'] = (string) $hist->mailup;
        $_SESSION['history']['maildel'] = (string) $hist->maildel;
    }

    public function htmlToRaw($text)
    {
        //
        $text = str_replace("<br>", "\n", $text);
        $text = str_replace("<br/>", "\n", $text);
        $text = str_replace("<br />", "\n", $text);
        $text = strip_tags($text);
        //
        return $text;
    }

    public function getResource($collectionArray, $coll_id, $res_id)
    {
        $viewResourceArr = array();

        for ($i=0; $i<count($collectionArray); $i++) {
            if ($collectionArray[$i]['id'] == $coll_id) {
                //Get table
                $table = $collectionArray[$i]['table'];
                //Get adress
                $adrTable = $collectionArray[$i]['adr'];
                //Get versions table
                $versionTable = $collectionArray[$i]['version_table'];
                break;
            }
        }

        if (!empty($res_id) && !empty($table) && !empty($adrTable)) {
            //docserver
            include_once 'core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR
                . 'docservers_controler.php';
            $docserverControler = new docservers_controler();
            //View resource controler
            $viewResourceArr = $docserverControler->viewResource(
                $res_id,
                $table,
                $adrTable,
                false
            );
            //Reajust some info
            if (strtoupper($viewResourceArr['ext']) == 'HTML'
                && $viewResourceArr['mime_type'] == "text/plain"
            ) {
                $viewResourceArr['mime_type'] = "text/html";
            }
            $db = new Database();
            $stmt = $db->query(
                "select res_id, description, subject, title, format, filesize from "
                . $table . " where res_id = ? and status <> 'DEL'",
                array($res_id)
            );
            $res = $stmt->fetchObject();
            $label = '';
            //Tile, or subject or description
            if (strlen(trim($res->title)) > 0) {
                $label = $res->title;
            } elseif (strlen(trim($res->subject)) > 0) {
                $label = $res->subject;
            } elseif (strlen(trim($res->description)) > 0) {
                $label = $res->description;
            }
            $viewResourceArr['label'] = $this->show_string($label);
        }

        return $viewResourceArr;
    }

    public function getAttachment($coll_id, $res_id_master, $res_attachment, $isVersion = false)
    {
        include_once 'modules/attachments/attachments_tables.php';
        include_once 'core/core_tables.php';
        include_once 'core/docservers_tools.php';

        $viewAttachmentArr = array();

        $db = new Database();
        $table = "res_attachments";
        $stmt = $db->query(
            "select description, subject, title, docserver_id, path, filename, format from "
            . $table . " where res_id = ? and res_id_master = ? ",
            array($res_attachment, $res_id_master)
        );
        if ($stmt->rowCount() > 0) {
            $line = $stmt->fetchObject();
            //Tile, or subject or description
            if (strlen(trim($line->title)) > 0) {
                $label = $line->title;
            } elseif (strlen(trim($line->subject)) > 0) {
                $label = $line->subject;
            } elseif (strlen(trim($line->description)) > 0) {
                $label = $line->description;
            }

            $docserver = $line->docserver_id;
            $path      = $line->path;
            $filename  = $line->filename;
            $format    = $line->format;
            $stmt = $db->query(
                "select path_template from " . _DOCSERVERS_TABLE_NAME
                . " where docserver_id = ? ",
                array($docserver)
            );
            //$db->show();
            $lineDoc = $stmt->fetchObject();
            $docserver = $lineDoc->path_template;
            $file = $docserver . $path . $filename;
            $file = str_replace("#", DIRECTORY_SEPARATOR, $file);
            if (file_exists($file)) {
                $mimeType = Ds_getMimeType($file);

                $fileNameOnTmp = 'tmp_file_' . rand()
                    . '.' . strtolower($format);
                $filePathOnTmp = $_SESSION['config']
                    ['tmppath'] . DIRECTORY_SEPARATOR
                    . $fileNameOnTmp;
                copy($file, $filePathOnTmp);

                $viewAttachmentArr = array(
                    'status'       => 'ok',
                    'label'        => $this->show_string($label),
                    'mime_type'    => $mimeType,
                    'ext'          => $format,
                    'file_content' => '',
                    'tmp_path'     => $_SESSION['config']['tmppath'],
                    'file_path'    => $filePathOnTmp,
                    'called_by_ws' => '',
                    'error'        => ''
                );
            } else {
                $viewAttachmentArr = array(
                    'status'       => 'ko',
                    'label'        => '',
                    'mime_type'    => '',
                    'ext'          => '',
                    'file_content' => '',
                    'tmp_path'     => '',
                    'file_path'    => '',
                    'called_by_ws' => '',
                    'error'        => _FILE_NOT_EXISTS_ON_THE_SERVER
                );
            }
        } else {
            $viewAttachmentArr = array(
                'status'       => 'ko',
                'label'        => '',
                'mime_type'    => '',
                'ext'          => '',
                'file_content' => '',
                'tmp_path'     => '',
                'file_path'    => '',
                'called_by_ws' => '',
                'error'        => _NO_RIGHT_ON_RESOURCE_OR_RESOURCE_NOT_EXISTS
            );
        }

        return $viewAttachmentArr;
    }

    public function createFilename($label, $extension)
    {
        $search = array(
            utf8_decode('@[éèêë]@i'), utf8_decode('@[ÊË]@i'), utf8_decode('@[àâä]@i'), utf8_decode('@[ÂÄ]@i'),
            utf8_decode('@[îï]@i'), utf8_decode('@[ÎÏ]@i'), utf8_decode('@[ûùü]@i'), utf8_decode('@[ÛÜ]@i'),
            utf8_decode('@[ôö]@i'), utf8_decode('@[ÔÖ]@i'), utf8_decode('@[ç]@i'), utf8_decode('@[^a-zA-Z0-9_-s.]@i'));

        $replace = array('e', 'E','a', 'A','i', 'I', 'u', 'U', 'o', 'O','c','_');

        $filename = preg_replace($search, $replace, utf8_decode(substr($label, 0, 30).".".$extension));

        return $filename;
    }

    public function getAttachedEntitiesMails($user_id="")
    {
        $db = new Database;
        $arrayEntitiesMails = array();

        if ($user_id <> "") {
            $stmt = $db->query(
                "SELECT e.short_label, e.email, e.entity_id FROM users_entities ue, entities e "
                . "WHERE ue.user_id = ? and ue.entity_id = e.entity_id and enabled = 'Y' order by e.short_label",
                array($user_id)
            );
        } else {
            $stmt = $db->query("SELECT short_label, email, entity_id FROM entities WHERE enabled = 'Y'");
        }

        $userEntities = array();
        while ($res = $stmt->fetchObject()) {
            $userEntities[]=$res->entity_id;
            if ($res->email <> "") {
                $arrayEntitiesMails[$res->entity_id.','.$res->email] = $res->short_label . " (". $res->email .")";
            }
        }

        $getXml = false;

        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'sendmail'
            . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'externalMailsEntities.xml'
        )
        ) {
            $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . 'sendmail'
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'externalMailsEntities.xml';
            $getXml = true;
        } elseif (file_exists($_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'sendmail'. 'xml' . DIRECTORY_SEPARATOR . 'externalMailsEntities.xml')) {
            $path = $_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'sendmail'
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'externalMailsEntities.xml';
            $getXml = true;
        }

        if ($getXml) {
            $xml = simplexml_load_file($path);

            if ($xml <> false) {
                include_once "modules/entities/class/class_manage_entities.php" ;
                $entities = new entity();

                foreach ($xml->externalEntityMail as $EntityMail) {
                    $shortLabelEntity = $entities->getentityshortlabel((string)$EntityMail->targetEntityId);
                    if (!in_array((string)$EntityMail->targetEntityId.",".(string)$EntityMail->EntityMail, array_keys($arrayEntitiesMails))
                        && (in_array((string)$EntityMail->targetEntityId, $userEntities) || (string)$EntityMail->targetEntityId == "")
                        && (string)$EntityMail->EntityMail <> ""
                    ) {
                        if ((string)$EntityMail->targetEntityId<>"") {
                            $EntityName = $shortLabelEntity;
                        } else {
                            $EntityName = (string)$EntityMail->defaultName;
                        }
                        $arrayEntitiesMails[(string)$EntityMail->targetEntityId.",".(string)$EntityMail->EntityMail] = $EntityName . " (" . (string)$EntityMail->EntityMail .")";
                    }
                }
            }
        }
        asort($arrayEntitiesMails);

        return $arrayEntitiesMails;
    }

    public function explodeSenderEmail($senderEmail)
    {
        if (strpos($senderEmail, ",") === false) {
            return $senderEmail;
        } else {
            $explode = explode(",", $senderEmail);
            return $explode[1];
        }
    }
}
