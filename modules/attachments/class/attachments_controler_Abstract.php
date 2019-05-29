<?php
/*
*   Copyright 2008-2016 Maarch
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
*   along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief  Contains the controler of the attachment Object
*
*
* @file
* @author Laurent Giovannoni <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup attachments
*/


abstract class attachments_controler_Abstract
{
    #####################################
    ## Attachment on a resource
    #####################################
    //add datas to have the subject
    public function storeAttachmentResource($resId, $collId, $encodedContent, $fileFormat, $title)
    {
        require_once 'core/class/class_db_pdo.php';
        require_once 'core/class/class_request.php';
        require_once 'core/class/class_resource.php';
        require_once 'core/class/docservers_controler.php';
        require_once 'core/class/class_security.php';
        $sec = new security();
        $table = $sec->retrieve_table_from_coll($collId);
        $db = new Database();
        $query = 'select res_id from ' . $table . ' where res_id = ?';
        $stmt = $db->query($query, array($resId), true);
        if ($stmt->rowCount() == 0) {
            $returnCode = -2;
            $error .= 'res_id inexistant';
        } else {
            $fileContent = base64_decode($encodedContent);
            $tmpFileName = 'tmp_file_ws_'
                 . rand() . "_" . md5($fileContent)
                 . "." . strtolower($fileFormat);
            $Fnm = $_SESSION['config']['tmppath'] . $tmpFileName;
            $inF = fopen($Fnm, "w");
            fwrite($inF, $fileContent);
            fclose($inF);
            $docserverControler = new docservers_controler();
            $docserver = $docserverControler->getDocserverToInsert(
               $collId
            );
            if (empty($docserver)) {
                $returnCode = -3;
                $error = _DOCSERVER_ERROR . ' : '
                    . _NO_AVAILABLE_DOCSERVER . ". " . _MORE_INFOS . ".";
            } else {
                $newSize = $docserverControler->checkSize(
                    $docserver,
                    $_SESSION['upfile']['size']
                );
                if ($newSize == 0) {
                    $returnCode = -4;
                    $error = _DOCSERVER_ERROR . ' : '
                        . _NOT_ENOUGH_DISK_SPACE . ". " . _MORE_INFOS . ".";
                } else {
                    $fileInfos = array(
                        "tmpDir"      => $_SESSION['config']['tmppath'],
                        "size"        => filesize($Fnm),
                        "format"      => strtolower($fileFormat),
                        "tmpFileName" => $tmpFileName,
                    );
                    $storeResult = array();
                    $storeResult = $docserverControler->storeResourceOnDocserver(
                        $collId,
                        $fileInfos
                    );
                    if (isset($storeResult['error']) && $storeResult['error'] <> '') {
                        $returnCode = -5;
                        $error = $storeResult['error'];
                    } else {
                        unlink($Fnm);
                        $resAttach = new resource();
                        $_SESSION['data'] = array();
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "typist",
                                'value' => $_SESSION['user']['UserId'],
                                'type' => "string",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "format",
                                'value' => strtolower($fileFormat),
                                'type' => "string",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "docserver_id",
                                'value' => $storeResult['docserver_id'],
                                'type' => "string",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "status",
                                'value' => 'TRA',
                                'type' => "string",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "offset_doc",
                                'value' => ' ',
                                'type' => "string",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "title",
                                'value' => $title,
                                'type' => "string",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "coll_id",
                                'value' => $collId,
                                'type' => "string",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "res_id_master",
                                'value' => $resId,
                                'type' => "integer",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "type_id",
                                'value' => 0,
                                'type' => "integer",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "relation",
                                'value' => 1,
                                'type' => "integer",
                            )
                        );
                        array_push(
                            $_SESSION['data'],
                            array(
                                'column' => "attachment_type",
                                'value' => 'simple_attachment',
                                'type' => "string",
                            )
                        );
                        $id = $resAttach->load_into_db(
                            'res_attachments',
                            $storeResult['destination_dir'],
                            $storeResult['file_destination_name'],
                            $storeResult['path_template'],
                            $storeResult['docserver_id'],
                            $_SESSION['data'],
                            $_SESSION['config']['databasetype']
                        );
                        if ($id == false) {
                            $returnCode = -6;
                            $error = $resAttach->get_error();
                        } else {
                            $returnCode = 0;
                            if ($_SESSION['history']['attachadd'] == "true") {
                                $users = new history();
                                $view = $sec->retrieve_view_from_coll_id(
                                    $collId
                                );
                                $users->add(
                                    $view,
                                    $resId,
                                    "ADD",
                                    'attachadd',
                                    ucfirst(_DOC_NUM) . $id . ' '
                                    . _NEW_ATTACH_ADDED . ' ' . _TO_MASTER_DOCUMENT
                                    . $resId,
                                    $_SESSION['config']['databasetype'],
                                    'apps'
                                );
                                $users->add(
                                    RES_ATTACHMENTS_TABLE,
                                    $id,
                                    "ADD",
                                    'attachadd',
                                    _NEW_ATTACH_ADDED . " (" . $title
                                    . ") ",
                                    $_SESSION['config']['databasetype'],
                                    'attachments'
                                );
                            }
                        }
                    }
                }
            }
        }
        $returnArray = array(
            'returnCode' => (int) $returnCode,
            'resId' => $id,
            'error' => $error,
        );
        return $returnArray;
    }
    
    public function initAttachmentInfos($resId)
    {
        $db = new Database();
        $stmt = $db->query("SELECT subject, exp_contact_id, dest_contact_id, exp_user_id, address_id, dest_user_id, alt_identifier FROM res_view_letterbox WHERE res_id = ?", array($resId));
        $data_attachment = $stmt->fetchObject();

        $infos['title'] = $data_attachment->subject;

        if ($data_attachment->dest_contact_id <> "") {
            $infos['contact_id'] = $data_attachment->dest_contact_id;
            $infos['address_id'] = $data_attachment->address_id;

            $stmt = $db->query('SELECT is_corporate_person, is_private, contact_lastname, contact_firstname, society, society_short, address_num, address_street, address_town, lastname, firstname 
                                FROM view_contacts 
                                WHERE contact_id = ? and ca_id = ?', array($data_attachment->dest_contact_id,$data_attachment->address_id));
        } elseif ($data_attachment->exp_contact_id <> "") {
            $infos['contact_id'] = $data_attachment->exp_contact_id;
            $infos['address_id'] = $data_attachment->address_id;
            $stmt = $db->query('SELECT is_corporate_person, is_private, contact_lastname, contact_firstname, society, society_short, address_num, address_street, address_town, lastname, firstname 
                                FROM view_contacts 
                                WHERE contact_id = ? and ca_id = ?', array($data_attachment->exp_contact_id,$data_attachment->address_id));
        } elseif ($data_attachment->dest_user != '') {
            $infos['contact_id'] = $data_attachment->dest_user;
            $infos['address_id'] = $data_attachment->address_id;
            $stmt = $db->query('SELECT lastname, firstname FROM users WHERE user_id = ?', [$data_attachment->dest_user]);
        } elseif ($data_attachment->exp_user_id != '') {
            $infos['contact_id'] = $data_attachment->exp_user_id;
            $infos['address_id'] = $data_attachment->address_id;
            $stmt = $db->query('SELECT lastname, firstname FROM users WHERE user_id = ?', [$data_attachment->exp_user_id]);
        } elseif ($data_attachment->dest_user_id != '') {
            $infos['contact_id'] = $data_attachment->dest_user_id;
            $infos['address_id'] = $data_attachment->address_id;
            $stmt = $db->query('SELECT lastname, firstname FROM users WHERE user_id = ?', [$data_attachment->dest_user_id]);
        }
    
        if ($data_attachment->exp_contact_id <> '' || $data_attachment->dest_contact_id <> '') {
            $res = $stmt->fetchObject();
            if ($res->is_corporate_person == 'Y') {
                $data_contact = $res->society;
                if (!empty($res->society_short)) {
                    $data_contact .= ' ('.$res->society_short.')';
                }
                if (!empty($res->lastname) || !empty($res->firstname)) {
                    $data_contact .= ' - ' . $res->lastname . ' ' . $res->firstname;
                }
                $data_contact .= ', ';
            } else {
                $data_contact .= $res->contact_lastname . ' ' . $res->contact_firstname;
                if (!empty($res->society)) {
                    $data_contact .= ' (' .$res->society . ')';
                }
                $data_contact .= ', ';
            }
            if ($res->is_private == 'Y') {
                $data_contact .= '(' . _CONFIDENTIAL_ADDRESS . ')';
            } else {
                $data_contact .= $res->address_num . ' ' . $res->address_street . ' ' . strtoupper($res->address_town);
            }
            $infos['contact_show'] = $data_contact;
        } elseif ($data_attachment->exp_user_id != '' || $data_attachment->dest_user != '' || $data_attachment->dest_user_id != '') {
            $res = $stmt->fetchObject();
            if (!empty($res->lastname) || !empty($res->firstname)) {
                $data_contact .= $res->lastname . ' ' . $res->firstname;
            }
            $infos['contact_show'] = $data_contact;
        //si multicontact
        } else {
            $stmt = $db->query("SELECT cr.address_id, c.contact_id, c.is_corporate_person, c.society, c.society_short, c.firstname, c.lastname,ca.is_private,ca.address_street, ca.address_num, ca.address_town 
                                FROM contacts_res cr, contacts_v2 c, contact_addresses ca 
                                WHERE cr.res_id = ? and cast(c.contact_id as char) = cast(cr.contact_id as char) and ca.contact_id=c.contact_id and ca.id=cr.address_id", array($_SESSION['doc_id']));
            $i=0;
            while ($multi_contacts_attachment = $stmt->fetchObject()) {
                if (is_numeric($multi_contacts_attachment->contact_id)) {
                    $format_contact='';
                    $stmt2 = $db->query('SELECT is_corporate_person, is_private, contact_lastname, contact_firstname, society, society_short, address_num, address_street, address_town, lastname, firstname 
                                    FROM view_contacts 
                                    WHERE contact_id = ? and ca_id = ?', array($multi_contacts_attachment->contact_id,$multi_contacts_attachment->address_id));
        
                    $res = $stmt2->fetchObject();
                    if ($res->is_corporate_person == 'Y') {
                        $format_contact = $res->society;
                        if (!empty($res->society_short)) {
                            $format_contact .= ' ('.$res->society_short.')';
                        }
                        if (!empty($res->lastname) || !empty($res->firstname)) {
                            $format_contact .= ' - ' . $res->lastname . ' ' . $res->firstname;
                        }
                        $format_contact .= ', ';
                    } else {
                        $format_contact .= $res->contact_lastname . ' ' . $res->contact_firstname;
                        if (!empty($res->society)) {
                            $format_contact .= ' (' .$res->society . ')';
                        }
                        $format_contact .= ', ';
                    }
                    if ($res->is_private == 'Y') {
                        $format_contact .= '('._CONFIDENTIAL_ADDRESS.')';
                    } else {
                        $format_contact .= $res->address_num .' ' . $res->address_street .' ' . strtoupper($res->address_town);
                    }
                    $contacts[] = array(
                        'contact_id'     => $multi_contacts_attachment->contact_id,
                        'firstname'      => $multi_contacts_attachment->firstname,
                        'lastname'       => $multi_contacts_attachment->lastname,
                        'society'        => $multi_contacts_attachment->society,
                        'address_id'     => $multi_contacts_attachment->address_id,
                        'format_contact' => $format_contact
                    );
        
                    if ($i==0) {
                        $data_contact                    = $format_contact;
                        $data_attachment->exp_contact_id = $multi_contacts_attachment->contact_id;
                    }
                    $i++;
                }
            }
            $infos['multi_contact'] = $contacts;
        }
        
        return $infos;
    }
    
    public function getAttachmentInfos($resId)
    {
        $db = new Database();
        
        $stmt = $db->query(
            "SELECT * 
                FROM res_view_attachments 
                WHERE (res_id = ? OR res_id_version = ?) and res_id_master = ? ORDER BY relation desc",
            array($resId, $resId, $_SESSION['doc_id'])
        );
        
        $infos = array();
        if ($stmt->rowCount() == 0) {
            $_SESSION['error'] = _THE_DOC . " " . _EXISTS_OR_RIGHT . "&hellip;";
            header(
                "location: " . $_SESSION['config']['businessappurl']
                . "index.php"
            );
            exit();
        } else {
            $line = $stmt->fetchObject();
            $docserver = $line->docserver_id;
            $path = $line->path;
            $filename = $line->filename;
            $format = $line->format;
            $stmt = $db->query(
                "select path_template from docservers where docserver_id = ?",
                array($docserver)
            );
            
            $lineDoc = $stmt->fetchObject();
            $docserver = $lineDoc->path_template;
            $file = $docserver . $path . $filename;
            $file = str_replace("#", DIRECTORY_SEPARATOR, $file);
            $origin = explode(',', $line->origin);
            $target_table_origin = $origin[1];
            $res_id_origin = $origin[0];
            
            $file_pdf = str_replace(pathinfo($filename, PATHINFO_EXTENSION), 'pdf', $file);
            $infos['pathfile'] = $file;
            $infos['path'] = $path;
            $infos['format'] = $format;
            $infos['pathfile_pdf'] = $file_pdf;
            $infos['res_id_origin'] = $res_id_origin;
            $infos['target_table_origin'] = $target_table_origin;
            $infos['status'] = $line->status;
            $infos['attachment_type'] = $line->attachment_type;
            $infos['creation_date'] = $line->creation_date;
            $infos['type_id'] = $line->type_id;
            $infos['title'] = $line->title;
            $infos['typist'] = $line->typist;
            $infos['validation_date'] = $line->validation_date;
            $infos['effective_date'] = $line->effective_date;
            $infos['res_id_master'] = $line->res_id_master;
            $infos['identifier'] = $line->identifier;
            if (!empty($line->res_id_version)) {
                $infos['is_version'] =  true;
                if (empty($infos['target_table_origin'])) {
                    $infos['target_table_origin'] = 'res_version_attachments';
                }
            } else {
                $infos['is_version'] =  false;
                if (empty($infos['target_table_origin'])) {
                    $infos['target_table_origin'] = 'res_attachments';
                }
            }
            
            //contact
            if (!empty($line->dest_user)) {
                $stmt = $db->query(
                    "SELECT user_id,lastname,firstname 
                        FROM users 
                        WHERE user_id = ?",
                    array($line->dest_user)
                );
                $res = $stmt->fetchObject();
                $data_contact = $res->lastname . ' ' . $res->firstname;
                $infos['contact_id'] = $res->user_id;
                $infos['contact_show'] = $data_contact;
            } else {
                $stmt = $db->query(
                    "SELECT * 
                        FROM view_contacts 
                        WHERE contact_id = ? and ca_id = ?",
                    array($line->dest_contact_id,$line->dest_address_id)
                );
                $res = $stmt->fetchObject();
                if ($res->is_corporate_person == 'Y') {
                    $data_contact = $res->society;
                    if (!empty($res->society_short)) {
                        $data_contact .= ' ('.$res->society_short.')';
                    }
                    if (!empty($res->lastname) || !empty($res->firstname)) {
                        $data_contact .= ' - ' . $res->lastname . ' ' . $res->firstname;
                    }
                    $data_contact .= ', ';
                } else {
                    $data_contact .= $res->contact_lastname . ' ' . $res->contact_firstname;
                    if (!empty($res->society)) {
                        $data_contact .= ' (' .$res->society . ')';
                    }
                    $data_contact .= ', ';
                }
                if ($res->is_private == 'Y') {
                    $data_contact .= '(' . _CONFIDENTIAL_ADDRESS . ')';
                } else {
                    $data_contact .= $res->address_num . ' ' . $res->address_street . ' ' . strtoupper($res->address_town);
                }
                $infos['contact_id'] = $line->dest_contact_id;
                $infos['address_id'] = $line->dest_address_id;
                $infos['contact_show'] = $data_contact;
            }
        }
        return $infos;
    }
    
    public function getCorrespondingPdf($resId)
    {
        $infos = $this->getAttachmentInfos($resId);
        if ($infos['format'] == 'pdf') {
            return $resId;
        }
        $db2 = new Database();
        $result = 0;
        $stmt2 = $db2->query(
            "SELECT res_id
                FROM res_view_attachments 
                WHERE path = ? AND filename = ? and attachment_type = 'converted_pdf' ORDER BY relation desc",
                array($infos['path'],pathinfo($infos['pathfile_pdf'], PATHINFO_BASENAME))
        );
        $line = $stmt2->fetchObject();
        
        if ($line->res_id != 0) {
            $result = $line->res_id;
        }
        return $result;
    }

    /**
     * Remove temporary attachment file on docserver
     * @param   bigint $resIdAttachment id of the attachment resource
     * @param   bigint $resIdMaster id of the master document
     * @param   string $userId user id who created the temporary attachment
     * @return  boolean if ok, return true.
     */
    public function removeTemporaryAttachmentOnDocserver($resIdAttachment, $resIdMaster, $userId)
    {
        $db = new Database();
        $stmt = $db->query(
            "SELECT docserver_id, path, filename, fingerprint
                FROM res_view_attachments
                WHERE (res_id = ? OR res_id_version = ?) AND res_id_master = ? AND status = 'TMP' AND typist = ? ORDER BY relation desc",
            array($resIdAttachment, $resIdAttachment, $resIdMaster, $userId)
        );

        if ($stmt->rowCount() == 0) {
            $_SESSION['error'] = _NO_DOC_OR_NO_RIGHTS;
            return false;
        } else {
            $line           = $stmt->fetchObject();
            $docserverOld   = $line->docserver_id;
            $pathOld        = $line->path;
            $filenameOld    = $line->filename;
            $fingerprintOld = $line->fingerprint;

            $stmt = $db->query("SELECT path_template FROM " . _DOCSERVERS_TABLE_NAME . " WHERE docserver_id = ?", array($docserverOld));
            $lineDoc   = $stmt->fetchObject();
            $docserver = $lineDoc->path_template;
            $file      = $docserver . $pathOld . $filenameOld;
            $file      = str_replace("#", DIRECTORY_SEPARATOR, $file);

            require_once 'core/class/docservers_controler.php';
            require_once 'core/class/docserver_types_controler.php';
            $docserverControler = new docservers_controler();
            $docserverTypeControler = new docserver_types_controler();

            require_once 'core/docservers_tools.php';

            $docserver           = $docserverControler->get($docserverOld);
            $docserverTypeObject = $docserverTypeControler->get($docserver->docserver_type_id);
            $fingerprintOldFile  = Ds_doFingerprint($file, $docserverTypeObject->fingerprint_mode);
            if ($fingerprintOld == $fingerprintOldFile) {
                unlink($file);
            }
            return true;
        }
    }
}
