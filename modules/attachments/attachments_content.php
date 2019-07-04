<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   attachments_content
*
* @author  dev <dev@maarch.org>
* @ingroup attachments
*/
require_once 'core/class/class_security.php';
require_once 'core/class/class_request.php';
require_once 'core/class/class_resource.php';
require_once 'apps'.DIRECTORY_SEPARATOR.$_SESSION['config']['app_id']
    .DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR
    .'class_indexing_searching_app.php';
require_once 'core/class/docservers_controler.php';
require_once 'modules/attachments/attachments_tables.php';
require_once 'core/class/class_history.php';
require_once 'modules/attachments/class/attachments_controler.php';

$core = new core_tools();
$core->load_lang();
$sec = new security();
$func = new functions();
$db = new Database();
$req = new request();
$docserverControler = new docservers_controler();
$ac = new attachments_controler();

$_SESSION['error'] = '';

$status = 0;
$error = $content = $js = $parameters = '';
$_SESSION['cm_applet'][$_SESSION['user']['UserId']] = [];

function _parse($text)
{
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "\n", $text);
    $text = str_replace("\n", '\\n ', $text);

    return $text;
}

if (!empty($_REQUEST['docId'])) {
    $_SESSION['doc_id'] = $_REQUEST['docId'];
}

//BEGIN SAVE ATTACHMENT VALIDATE BUTTTON
if (isset($_POST['add']) && $_POST['add']) {
    //CHECK FORM ERRORS
    if ((count($_SESSION['upfile']) - 1) != count($_REQUEST['attachNum'])) {
        $error = _MODEL_NOT_EDITED;
        $status = 1;
    } else {
        for ($numAttach = 0; $numAttach < count($_SESSION['upfile']) - 1; ++$numAttach) {
            //EMPTY ATTACHMENTS FILE ?
            if (empty($_SESSION['upfile'][$numAttach]['tmp_name'])) {
                $error = _FILE_MISSING;
            } elseif ($_SESSION['upfile'][$numAttach]['size'] == 0) {
                $error = _FILE_EMPTY;
            }
            //MAX SIZE FILE ?
            if ($_SESSION['upfile'][$numAttach]['error'] == 1) {
                $filesize = $func->return_bytes(ini_get('upload_max_filesize'));
                $error = _ERROR_FILE_UPLOAD_MAX.'('.round($filesize / 1024, 2).'Ko Max)';
            }

            //EMPTY ATTACHMENT TYPE ?
            $attachment_types = '';
            if (!isset($_REQUEST['attachment_types'][$numAttach]) || empty($_REQUEST['attachment_types'][$numAttach])) {
                $error = _ATTACHMENT_TYPES.' '._MANDATORY;
            } else {
                $attachment_types = $func->protect_string_db($_REQUEST['attachment_types'][$numAttach]);
            }

            //EMPTY TITLE ?
            $title = '';
            if (!isset($_REQUEST['title'][$numAttach]) || empty($_REQUEST['title'][$numAttach])) {
                $error = _OBJECT.' '._MANDATORY;
            } else {
                $title = $_REQUEST['title'][$numAttach];
                $title = str_replace('&#039;', "'", $title);
            }

            //PROCESS ATTACHMENT
            if (empty($error)) {
                $back_date = $_REQUEST['back_date'][$numAttach];
                $contactidAttach = $_REQUEST['contactidAttach'][$numAttach];
                $addressidAttach = $_REQUEST['addressidAttach'][$numAttach];
                $chrono = $_REQUEST['chrono'][$numAttach];
                $attachStatus = $_REQUEST['effectiveDateStatus'][$numAttach];

                require_once 'core/docservers_tools.php';
                $arrayIsAllowed = array();
                $arrayIsAllowed = Ds_isFileTypeAllowed(
                    $_SESSION['config']['tmppath'].$_SESSION['upfile'][$numAttach]['fileNameOnTmp']
                );
                if ($arrayIsAllowed['status'] == false) {
                    $error = _WRONG_FILE_TYPE.' '.$arrayIsAllowed['mime_type'];
                    $_SESSION['upfile'] = array();
                } else {
                    if (!isset($_SESSION['collection_id_choice'])
                        || empty($_SESSION['collection_id_choice'])
                    ) {
                        $_SESSION['collection_id_choice'] = $_SESSION['user']['collections'][0];
                    }

                    //CHECK DOCSERVER FOR ATTACHMENT
                    $docserver = $docserverControler->getDocserverToInsert(
                        $_SESSION['collection_id_choice']
                    );
                    if (empty($docserver)) {
                        $error = _DOCSERVER_ERROR.' : '._NO_AVAILABLE_DOCSERVER.'. '._MORE_INFOS;
                        $location = '';
                    } else {
                        //CHECK DOCSERVER SPACE
                        $newSize = $docserverControler->checkSize(
                            $docserver,
                            $_SESSION['upfile'][$numAttach]['size']
                        );
                        if ($newSize == 0) {
                            $error = _DOCSERVER_ERROR.' : '._NOT_ENOUGH_DISK_SPACE.'. '._MORE_INFOS; ?>
                            <script type="text/javascript">
                                var eleframe1 =  window.parent.top.document.getElementById('list_attach');
                                eleframe1.location.href = '<?php
                            echo $_SESSION['config']['businessappurl']; ?>index.php?display=true&module=attachments&page=frame_list_attachments&attach_type_exclude=converted_pdf,print_folder&mode=normal&load';
                            </script>
                            <?php
                            exit();
                        } else {
                            //GET FILE INFOS
                            $path_parts = pathinfo($_SESSION['upfile'][$numAttach]['fileNameOnTmp']);
                            $fileInfos = array(
                                'tmpDir' => $_SESSION['config']['tmppath'],
                                'size' => $_SESSION['upfile'][$numAttach]['size'],
                                'format' => $path_parts['extension'],
                                'tmpFileName' => $_SESSION['upfile'][$numAttach]['fileNameOnTmp'],
                            );
                            if ($contactidAttach != 'mailing') {

                                //SAVE FILE ON DOCSERVER
                                $storeResult = array();
                                $storeResult = $docserverControler->storeResourceOnDocserver(
                                    $_SESSION['collection_id_choice'],
                                    $fileInfos
                                );
                                if ($attachment_types == 'outgoing_mail' && strpos($fileInfos['format'], 'xl') === false && strpos($fileInfos['format'], 'ppt') === false) {
                                    $_SESSION['upfile'][$numAttach]['outgoingMail'] = true;
                                }
                            }

                            if (isset($storeResult['error']) && $storeResult['error'] != '') {
                                $error = $storeResult['error'];
                            } else {
                                $resAttach = new resource();
                                $_SESSION['data'] = array();
                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'typist',
                                        'value' => $_SESSION['user']['UserId'],
                                        'type' => 'string',
                                    )
                                );
                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'format',
                                        'value' => $fileInfos['format'],
                                        'type' => 'string',
                                    )
                                );
                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'res_id_master',
                                        'value' => $_SESSION['doc_id'],
                                        'type' => 'integer',
                                    )
                                );
                                if ($contactidAttach != 'mailing') {
                                    array_push(
                                        $_SESSION['data'],
                                        array(
                                            'column' => 'docserver_id',
                                            'value' => $storeResult['docserver_id'],
                                            'type' => 'string',
                                        )
                                    );
                                    if (isset($contactidAttach) && $contactidAttach != '' && is_numeric($contactidAttach)) {
                                        array_push(
                                            $_SESSION['data'],
                                            array(
                                                'column' => 'dest_contact_id',
                                                'value' => $contactidAttach,
                                                'type' => 'integer',
                                            )
                                        );
                                    } elseif (isset($contactidAttach) && $contactidAttach != '' && !is_numeric($contactidAttach)) {
                                        $_SESSION['data'][] = [
                                            'column' => 'dest_user',
                                            'value' => $contactidAttach,
                                            'type' => 'string',
                                        ];
                                    }

                                    if (isset($addressidAttach) && $addressidAttach != '' && is_numeric($addressidAttach)) {
                                        array_push(
                                            $_SESSION['data'],
                                            array(
                                                'column' => 'dest_address_id',
                                                'value' => $addressidAttach,
                                                'type' => 'integer',
                                            )
                                        );
                                    }
                                }
                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'status',
                                        'value' => $attachStatus,
                                        'type' => 'string',
                                    )
                                );
                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'offset_doc',
                                        'value' => ' ',
                                        'type' => 'string',
                                    )
                                );
                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'title',
                                        'value' => $title,
                                        'type' => 'string',
                                    )
                                );
                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'attachment_type',
                                        'value' => $attachment_types,
                                        'type' => 'string',
                                    )
                                );
                                //GET SIGN PROPERTY FOR CURRENT ATTACHMENT TYPE
                                $attachmentTypesList = \Attachment\models\AttachmentModel::getAttachmentsTypesByXML();
                                foreach ($attachmentTypesList as $keyAttachment => $valueAttachment) {
                                    if ($keyAttachment == $attachment_types && $valueAttachment['sign']) {
                                        array_push(
                                            $_SESSION['data'],
                                            array(
                                                'column' => 'in_signature_book',
                                                'value' => 1,
                                                'type' => 'bool',
                                            )
                                        );
                                    }
                                }

                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'coll_id',
                                        'value' => $_SESSION['collection_id_choice'],
                                        'type' => 'string',
                                    )
                                );
                                if (isset($back_date) && $back_date != '') {
                                    array_push(
                                        $_SESSION['data'],
                                        array(
                                            'column' => 'validation_date',
                                            'value' => $func->format_date_db($back_date),
                                            'type' => 'date',
                                        )
                                    );
                                }

                                if (!empty($chrono)) {
                                    array_push(
                                        $_SESSION['data'],
                                        array(
                                            'column' => 'identifier',
                                            'value' => $chrono,
                                            'type' => 'string',
                                        )
                                    );
                                }
                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'type_id',
                                        'value' => 0,
                                        'type' => 'int',
                                    )
                                );

                                array_push(
                                    $_SESSION['data'],
                                    array(
                                        'column' => 'relation',
                                        'value' => 1,
                                        'type' => 'int',
                                    )
                                );

                                if ($contactidAttach == 'mailing') {
                                    if (empty($_REQUEST['mailing'])) {
                                        $fileInfos = [
                                            'tmpDir'        => $_SESSION['config']['tmppath'],
                                            'size'          => $_SESSION['upfile'][$numAttach]['size'],
                                            'format'        => $path_parts['extension'],
                                            'tmpFileName'   => $_SESSION['upfile'][$numAttach]['fileNameOnTmp'],
                                        ];
                                        $storeResult = $docserverControler->storeResourceOnDocserver($_SESSION['collection_id_choice'], $fileInfos);

                                        foreach ($_SESSION['data'] as $dataKey => $dataValue) {
                                            if (in_array($dataValue['column'], ['status'])) {
                                                unset($_SESSION['data'][$dataKey]);
                                            }
                                        }
                                        $_SESSION['data'][] = [
                                            'column' => 'status',
                                            'value' => 'SEND_MASS',
                                            'type' => 'string'
                                        ];
                                        $_SESSION['data'][] = [
                                            'column' => 'docserver_id',
                                            'value' => $storeResult['docserver_id'],
                                            'type' => 'string'
                                        ];
                                        $_SESSION['data'][] = [
                                            'column' => 'dest_contact_id',
                                            'value' => null,
                                            'type' => 'integer'
                                        ];
                                        $_SESSION['data'][] = [
                                            'column' => 'dest_address_id',
                                            'value' => null,
                                            'type' => 'integer'
                                        ];

                                        $id = $resAttach->load_into_db(
                                            'res_attachments',
                                            $storeResult['destination_dir'],
                                            $storeResult['file_destination_name'],
                                            $storeResult['path_template'],
                                            $storeResult['docserver_id'],
                                            $_SESSION['data'],
                                            $_SESSION['config']['databasetype']
                                        );
                                    } else {
                                        $contactsForMailing = \SrcCore\models\DatabaseModel::select([
                                            'select'    => ['*'],
                                            'table'     => ['contacts_res'],
                                            'where'     => ['res_id = ?', 'address_id != ?'],
                                            'data'      => [$_SESSION['doc_id'], 0]
                                        ]);
                                        foreach ($contactsForMailing as $key => $contactForMailing) {
                                            $chronoPubli = $chrono.'-'.chr(ord('A')+$key);
                                            $pathToAttachmentToCopy = $_SESSION['config']['tmppath'] . $_SESSION['upfile'][$numAttach]['fileNameOnTmp'];
                                            $params = [
                                                'userId' => $_SESSION['user']['UserId'],
                                                'res_id' => $_SESSION['doc_id'],
                                                'coll_id' => 'letterbox_coll',
                                                'res_view' => 'res_view_attachments',
                                                'res_table' => 'res_attachments',
                                                'res_contact_id' => $contactForMailing['contact_id'],
                                                'res_address_id' => $contactForMailing['address_id'],
                                                'pathToAttachment' => $pathToAttachmentToCopy,
                                                'chronoAttachment' => $chronoPubli,
                                            ];

                                            $filePathOnTmp = \Template\controllers\TemplateController::mergeDatasource($params);

                                            $fileInfos = [
                                                'tmpDir'        => $_SESSION['config']['tmppath'],
                                                'size'          => $_SESSION['upfile'][$numAttach]['size'],
                                                'format'        => $path_parts['extension'],
                                                'tmpFileName'   => str_replace($_SESSION['config']['tmppath'], '', $filePathOnTmp)
                                            ];

                                            $storeResult = $docserverControler->storeResourceOnDocserver($_SESSION['collection_id_choice'], $fileInfos);
                                            foreach ($_SESSION['data'] as $dataKey => $dataValue) {
                                                if (in_array($dataValue['column'], ['docserver_id', 'dest_contact_id', 'dest_address_id', 'identifier'])) {
                                                    unset($_SESSION['data'][$dataKey]);
                                                }
                                            }
                                            $_SESSION['data'][] = [
                                                'column' => 'docserver_id',
                                                'value' => $storeResult['docserver_id'],
                                                'type' => 'string'
                                            ];
                                            $_SESSION['data'][] = [
                                                'column' => 'dest_contact_id',
                                                'value' => $contactForMailing['contact_id'],
                                                'type' => 'integer'
                                            ];
                                            $_SESSION['data'][] = [
                                                'column' => 'dest_address_id',
                                                'value' => $contactForMailing['address_id'],
                                                'type' => 'integer'
                                            ];

                                            $_SESSION['data'][] = [
                                                'column' => 'identifier',
                                                'value' => $chronoPubli,
                                                'type' => 'string'
                                            ];

                                            $id = $resAttach->load_into_db(
                                                'res_attachments',
                                                $storeResult['destination_dir'],
                                                $storeResult['file_destination_name'],
                                                $storeResult['path_template'],
                                                $storeResult['docserver_id'],
                                                $_SESSION['data'],
                                                $_SESSION['config']['databasetype']
                                            );
                                        }
                                    }

                                } else {
                                    //SAVE META DATAS IN DB
                                    $id = $resAttach->load_into_db(
                                        RES_ATTACHMENTS_TABLE,
                                        $storeResult['destination_dir'],
                                        $storeResult['file_destination_name'],
                                        $storeResult['path_template'],
                                        $storeResult['docserver_id'],
                                        $_SESSION['data'],
                                        $_SESSION['config']['databasetype']
                                    );

                                    if (\SrcCore\models\CurlModel::isEnabled(['curlCallId' => 'sendAttachmentToExternalApplication'])) {
                                        $bodyData = [];
                                        $config = \SrcCore\models\CurlModel::getConfigByCallId(['curlCallId' => 'sendAttachmentToExternalApplication']);
                                        $configResource = \SrcCore\models\CurlModel::getConfigByCallId(['curlCallId' => 'sendResourceToExternalApplication']);

                                        $resource = \Resource\models\ResModel::getOnView(['select' => ['external_id', 'address_id'], 'where' => ['res_id = ?'], 'data' => [$_SESSION['doc_id']]]);
                                        $externalId = json_decode($resource[0]['external_id'], true);

                                        if (!empty($externalId['localeoId']) && !empty($resource[0]['address_id'])) {
                                            if (!empty($config['inObject'])) {
                                                $multipleObject = true;

                                                foreach ($config['objects'] as $object) {
                                                    $select = [];
                                                    $tmpBodyData = [];
                                                    if ($object['name'] != 'citoyen') {
                                                        foreach ($object['rawData'] as $value) {
                                                            if ($value != 'localeoId') {
                                                                $select[] = $value;
                                                            }
                                                        }
                                                    }

                                                    if (!empty($select)) {
                                                        $document = \Attachment\models\AttachmentModel::getOnView(['select' => $select, 'where' => ['res_id = ?'], 'data' => [$id]]);
                                                    }
                                                    if ($object['name'] == 'citoyen') {
                                                        $contact = \Contact\models\ContactModel::getOnView(['select' => ['external_id', 'ca_id'], 'where' => ['ca_id = ?'], 'data' => [$resource[0]['address_id']]]);
                                                        $externalId = json_decode($contact[0]['external_id'], true);
                                                    }
                                                    foreach ($object['rawData'] as $key => $value) {
                                                        if ($object['name'] == 'citoyen') {
                                                            if ($value == 'external_id') {
                                                                $tmpBodyData[$key] = $externalId['localeoId'];
                                                            } elseif ($value == 'address_id') {
                                                                $tmpBodyData[$key] = $contact[0]['ca_id'];
                                                            } else {
                                                                $tmpBodyData[$key] = $contact[0][$value];
                                                            }
                                                        } else {
                                                            if ($value == 'localeoId') {
                                                                $tmpBodyData[$key] = $externalId['localeoId'];
                                                            } else {
                                                                $tmpBodyData[$key] = $document[0][$value];
                                                            }
                                                        }
                                                    }


                                                    if (!empty($object['data'])) {
                                                        $tmpBodyData = array_merge($tmpBodyData, $object['data']);
                                                    }

                                                    $bodyData[$object['name']] = $tmpBodyData;
                                                }

                                                if (!empty($config['file'])) {
                                                    $docserver = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $storeResult['docserver_id'], 'select' => ['path_template']]);
                                                    $bodyData[$config['file']] = \SrcCore\models\CurlModel::makeCurlFile(['path' => $docserver['path_template'] . str_replace('#', '/', $storeResult['destination_dir']) . $storeResult['file_destination_name']]);
                                                }
                                            }

                                            $response = \SrcCore\models\CurlModel::exec(['curlCallId' => 'sendAttachmentToExternalApplication', 'bodyData' => $bodyData, 'multipleObject' => $multipleObject, 'noAuth' => true]);
                                        }
                                    }
                                }

                                //copie de la version PDF de la pièce si mode de conversion sur le client
                                if ($_SESSION['upfile'][$numAttach]['fileNamePdfOnTmp'] != '' && empty($templateOffice)) {
                                    //case onlyConvert
                                    $query = "select template_id from templates where template_type = 'OFFICE' and template_target = 'attachments'";
                                    $stmt = $db->query($query);
                                    $templateOffice = $stmt->fetchObject()->template_id;
                                }
                                if ($_SESSION['upfile'][$numAttach]['fileNamePdfOnTmp'] != '' && isset($templateOffice) && empty($_REQUEST['mailing'])) {
                                    $_SESSION['new_id'] = $id;

                                    $resource = file_get_contents($_SESSION['config']['tmppath'].$_SESSION['upfile'][$numAttach]['fileNamePdfOnTmp']);
                                    $pathInfo = pathinfo($_SESSION['config']['tmppath'].$_SESSION['upfile'][$numAttach]['fileNamePdfOnTmp']);
                                    $storeResult = \Docserver\controllers\DocserverController::storeResourceOnDocServer([
                                        'collId'            => 'attachments_coll',
                                        'docserverTypeId'   => 'CONVERT',
                                        'encodedResource'   => base64_encode($resource),
                                        'format'            => $pathInfo['extension']
                                    ]);

                                    $result = \Convert\models\AdrModel::createAttachAdr([
                                        'resId'         => $id,
                                        'isVersion'     => false,
                                        'type'          => 'PDF',
                                        'docserverId'   => $storeResult['docserver_id'],
                                        'path'          => $storeResult['destination_dir'],
                                        'filename'      => $storeResult['file_destination_name'],
                                        'fingerprint'      => $storeResult['fingerPrint'],
                                    ]);

                                    unset($_SESSION['upfile'][$attachNum]['fileNamePdfOnTmp']);
                                }

                                if ($id == false) {
                                    $error = $resAttach->get_error();
                                } else {
                                    // Delete temporary backup
                                    $stmt = $db->query(
                                        "SELECT docserver_id, path, filename FROM res_attachments WHERE res_id = ? AND status = 'TMP' AND typist = ?",
                                        [$_SESSION['attachmentInfo'][$numAttach]['inProgressResId'], $_SESSION['user']['UserId']]
                                    );
                                    if ($stmt->rowCount() !== 0) {
                                        $line           = $stmt->fetchObject();
                                        $stmt = $db->query("SELECT path_template FROM docservers WHERE docserver_id = ?", array($line->docserver_id));
                                        $lineDoc   = $stmt->fetchObject();
                                        $file      = $lineDoc->path_template . $line->path . $line->filename;
                                        $file      = str_replace("#", DIRECTORY_SEPARATOR, $file);
                                        unlink($file);

                                        $db->query(
                                            "DELETE FROM res_attachments WHERE res_id = ? and status = 'TMP' and typist = ?",
                                            array($_SESSION['attachmentInfo'][$numAttach]['inProgressResId'], $_SESSION['user']['UserId'])
                                        );
                                    }

                                    if ($_SESSION['history']['attachadd'] == 'true') {
                                        $hist = new history();
                                        $view = $sec->retrieve_view_from_coll_id(
                                            $_SESSION['collection_id_choice']
                                        );
                                        $hist->add(
                                            $view,
                                            $_SESSION['doc_id'],
                                            'ADD',
                                            'attachadd',
                                            ucfirst(_DOC_NUM).$id.' '
                                            ._NEW_ATTACH_ADDED.' '._TO_MASTER_DOCUMENT
                                            .$_SESSION['doc_id'],
                                            $_SESSION['config']['databasetype'],
                                            'apps'
                                        );
                                        $content = _NEW_ATTACH_ADDED;
                                        $hist->add(
                                            RES_ATTACHMENTS_TABLE,
                                            $id,
                                            'ADD',
                                            'attachadd',
                                            $content.' ('.$title
                                            .') ',
                                            $_SESSION['config']['databasetype'],
                                            'attachments'
                                        );
                                    }
                                }
                            }
                        }
                    }
                    //IF SUCCESS EXTRA JS FOR UPDATE TABS
                    if (empty($error) || $content == _NEW_ATTACH_ADDED) {
                        $new_nb_attach = 0;
                        $stmt = $db->query('select res_id from '
                            .$_SESSION['tablename']['attach_res_attachments']
                            ." where status <> 'DEL' and attachment_type <> 'converted_pdf' and attachment_type <> 'print_folder' and res_id_master = ?", array($_SESSION['doc_id']));
                        if ($stmt->rowCount() > 0) {
                            $new_nb_attach = $stmt->rowCount();
                        }
                        if (isset($_REQUEST['fromDetail']) && $_REQUEST['fromDetail'] == 'create') {
                            //Redirection vers bannette MyBasket s'il s'agit d'un courrier spontané et que l'utilisateur connecté est le destinataire du courrier
                            if (isset($_SESSION['upfile'][$attachNum]['outgoingMail']) && $_SESSION['upfile'][$attachNum]['outgoingMail'] && $_SESSION['user']['UserId'] == $_SESSION['details']['diff_list']['dest']['users'][0]['user_id']) {
                                $js .= "window.parent.top.location.href = 'index.php?page=view_baskets&module=basket&baskets=MyBasket&resid=".$_SESSION['doc_id']."&directLinkToAction';";
                            } else {
                                if ($attachment_types == 'response_project' || $attachment_types == 'outgoing_mail' || $attachment_types == 'signed_response' || $attachment_types == 'aihp') {
                                    $js .= '$j(\'#responses_tab\').click();loadSpecificTab(\'uniqueDetailsIframe\',\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=frame_list_attachments&view_only=true&load&fromDetail=response&attach_type=response_project,outgoing_mail_signed,signed_response,outgoing_mail,aihp\');';
                                } else {
                                    $js .= '$j(\'#attachments_tab\').click();loadSpecificTab(\'uniqueDetailsIframe\',\''.$_SESSION['config']['businessappurl'].'index.php?display=true&page=show_attachments_details_tab&module=attachments&resId='.$_SESSION['doc_id'].'&collId=letterbox_coll&fromDetail=attachments&attach_type_exclude=response_project,signed_response,outgoing_mail_signed,converted_pdf,outgoing_mail,print_folder,aihp\');';
                                }
                            }
                        } else {
                            $js .= 'var eleframe1 =  window.parent.top.document.getElementById(\'list_attach\');';
                            $js .= 'eleframe1.src = \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=frame_list_attachments&attach_type_exclude=converted_pdf,print_folder&load\';';
                        }
                    } else {
                        $status = 1;
                    }
                }
            } else {
                $status = 1;
            }
        }
    }

    if (!isset($_SESSION['new_id'])) {
        $_SESSION['new_id'] = 0;
    }

    echo '{"status" : "'.$status.'", "content" : "'.addslashes(_parse($content)).'", "error" : "'.addslashes($error).'", "majFrameId" : "'.functions::xssafe($_SESSION['new_id']).'", "exec_js" : "'.addslashes($js).'", "type" : "'.$attachment_types.'"}';
    //RAZ SESSIONS FILE
    if (empty($error)) {
        unset($_SESSION['new_id']);
        unset($_SESSION['attachmentInfo']);
        unset($_SESSION['transmissionContacts']);
        unset($_SESSION['resIdVersionAttachment']);
        unset($_SESSION['targetAttachment']);
    }
    exit();

//BEGIN EDIT ATTACHMENT VALIDATE BUTTTON
} elseif (isset($_POST['edit']) && $_POST['edit']) {
    $resAttach = new resource();
    $status = 0;
    $error = '';

    //EMPTY TITLE ?
    $title = '';
    if (!isset($_REQUEST['title'][0]) || empty($_REQUEST['title'][0])) {
        $error .= _OBJECT.' '._MANDATORY.'. ';
    } else {
        $title = $_REQUEST['title'][0];
        $title = str_replace('&#039;', "'", $title);
    }

    //CURRENT ATTACHMENT IS A VERSION OF OLD ATTACHMENT ?
    if ((int) $_REQUEST['relation'] > 1) {
        $column_res = 'res_id_version';
    } else {
        $column_res = 'res_id';
    }

    //IS NEW VERSION ?
    if ($_REQUEST['new_version'] == 'yes') {
        $is_new_version = true;

        //RETRIEVE PREVIOUS ATTACHMENT
        $stmt = $db->query('SELECT res_id, res_id_version, attachment_type, identifier, relation, attachment_id_master, status 
                            FROM res_view_attachments
                            WHERE '.$column_res.' = ? and res_id_master = ?
                            ORDER BY relation desc', array($_REQUEST['res_id'], $_SESSION['doc_id']));
        $previous_attachment = $stmt->fetchObject();

        $path_parts = pathinfo($_SESSION['upfile'][0]['fileNameOnTmp']);
        $fileInfos = array(
            'tmpDir' => $_SESSION['config']['tmppath'],
            'size' => $_SESSION['upfile'][0]['size'],
            'format' => $path_parts['extension'],
            'tmpFileName' => $_SESSION['upfile'][0]['fileNameOnTmp'],
        );
        $storeResult = array();

        $storeResult = $docserverControler->storeResourceOnDocserver(
            $_SESSION['collection_id_choice'],
            $fileInfos
        );

        if (isset($storeResult['error']) && $storeResult['error'] != '') {
            $error = $storeResult['error'];
        }
        //SET META DATAS OF NEW ATTACHMENT
        if (empty($error)) {
            $_SESSION['data'] = array();
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'typist',
                    'value' => $_SESSION['user']['UserId'],
                    'type' => 'string',
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'format',
                    'value' => $fileInfos['format'],
                    'type' => 'string',
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'docserver_id',
                    'value' => $storeResult['docserver_id'],
                    'type' => 'string',
                )
            );
            if (!empty($_REQUEST['effectiveDateStatus'][0])) {
                $_SESSION['data'][] = [
                    'column' => 'status',
                    'value' => $_REQUEST['effectiveDateStatus'][0],
                    'type' => 'string',
                ];
            } else {
                array_push(
                    $_SESSION['data'],
                    array(
                        'column' => 'status',
                        'value' => $previous_attachment->status,
                        'type' => 'string',
                    )
                );
            }
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'offset_doc',
                    'value' => ' ',
                    'type' => 'string',
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'title',
                    'value' => $title,
                    'type' => 'string',
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'attachment_type',
                    'value' => $_REQUEST['attachment_types'][0],
                    'type' => 'string',
                )
            );
            $attachmentTypesList = \Attachment\models\AttachmentModel::getAttachmentsTypesByXML();
            foreach ($attachmentTypesList as $keyAttachment => $valueAttachment) {
                if ($keyAttachment == $previous_attachment->attachment_type && $valueAttachment['sign']) {
                    array_push(
                        $_SESSION['data'],
                        array(
                            'column' => 'in_signature_book',
                            'value' => 1,
                            'type' => 'bool',
                        )
                    );
                }
            }
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'coll_id',
                    'value' => $_SESSION['collection_id_choice'],
                    'type' => 'string',
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'res_id_master',
                    'value' => $_SESSION['doc_id'],
                    'type' => 'integer',
                )
            );
            if ((int) $previous_attachment->attachment_id_master == 0) {
                array_push(
                    $_SESSION['data'],
                    array(
                        'column' => 'attachment_id_master',
                        'value' => $_REQUEST['res_id'],
                        'type' => 'integer',
                    )
                );
            } else {
                array_push(
                    $_SESSION['data'],
                    array(
                        'column' => 'attachment_id_master',
                        'value' => (int) $previous_attachment->attachment_id_master,
                        'type' => 'integer',
                    )
                );
            }
            if (isset($_REQUEST['back_date'][0]) && $_REQUEST['back_date'][0] != '') {
                array_push(
                    $_SESSION['data'],
                    array(
                        'column' => 'validation_date',
                        'value' => $func->format_date_db($_REQUEST['back_date'][0]),
                        'type' => 'date',
                    )
                );
            }

            if (!empty($_REQUEST['effectiveDate'][0])) {
                $_SESSION['data'][] = [
                    'column' => 'effective_date',
                    'value' => $func->format_date_db($_REQUEST['effectiveDate'][0]),
                    'type' => 'date',
                ];
            }

            if (isset($_REQUEST['contactidAttach'][0]) && $_REQUEST['contactidAttach'][0] != '' && is_numeric($_REQUEST['contactidAttach'][0])) {
                $_SESSION['data'][] = [
                        'column' => 'dest_contact_id',
                        'value' => $_REQUEST['contactidAttach'][0],
                        'type' => 'integer',
                    ];
            } elseif (isset($_REQUEST['contactidAttach'][0]) && $_REQUEST['contactidAttach'][0] != '' && !is_numeric($_REQUEST['contactidAttach'][0])) {
                $_SESSION['data'][] = [
                        'column' => 'dest_user',
                        'value' => $_REQUEST['contactidAttach'][0],
                        'type' => 'string',
                    ];
            }

            if (isset($_REQUEST['addressidAttach'][0]) && $_REQUEST['addressidAttach'][0] != '') {
                array_push(
                    $_SESSION['data'],
                    array(
                        'column' => 'dest_address_id',
                        'value' => $_REQUEST['addressidAttach'][0],
                        'type' => 'integer',
                    )
                );
            }
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'identifier',
                    'value' => $_REQUEST['chrono'][0],
                    'type' => 'string',
                )
            );
            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'type_id',
                    'value' => 0,
                    'type' => 'int',
                )
            );

            $relation = (int) $previous_attachment->relation;
            ++$relation;

            array_push(
                $_SESSION['data'],
                array(
                    'column' => 'relation',
                    'value' => $relation,
                    'type' => 'int',
                )
            );

            //SAVE META DATAS IN DB
            $id = $resAttach->load_into_db(
                'res_version_attachments',
                $storeResult['destination_dir'],
                $storeResult['file_destination_name'],
                $storeResult['path_template'],
                $storeResult['docserver_id'],
                $_SESSION['data'],
                $_SESSION['config']['databasetype']
            );

            //DEPRECATED OLD ATTACHMENT FILE
            if ((int) $_REQUEST['relation'] == 1) {
                $stmt = $db->query("UPDATE res_attachments SET status = 'OBS' WHERE res_id = ?", array($previous_attachment->res_id));
            } else {
                $stmt = $db->query("UPDATE res_version_attachments SET status = 'OBS' WHERE res_id = ?", array($previous_attachment->res_id_version));
            }
        }
    } else {
        $is_new_version = false;
        $set_update = '';
        $arrayPDO = array();

        //CREATE SQL UPDATE
        $set_update = ' title = :title';
        $arrayPDO = array_merge($arrayPDO, array(':title' => $title));
        if (!empty($_REQUEST['attachment_types'][0])) {
            $set_update .= ", attachment_type = :attachmentType";
            $arrayPDO = array_merge($arrayPDO, array(':attachmentType' => $_REQUEST['attachment_types'][0]));
        }
        if (!empty($_REQUEST['chrono'][0])) {
            $set_update .= ", identifier = :identifier";
            $arrayPDO = array_merge($arrayPDO, array(':identifier' => $_REQUEST['chrono'][0]));
        }
        if (isset($_REQUEST['back_date'][0]) && $_REQUEST['back_date'][0] != '') {
            $set_update .= ", validation_date = '".$req->format_date_db($_REQUEST['back_date'][0])."'";
        } else {
            $set_update .= ', validation_date = null';
        }
        if (!empty($_REQUEST['effectiveDate'][0])) {
            $set_update .= ", effective_date = '".$req->format_date_db($_REQUEST['effectiveDate'][0])."'";
        }
        if (isset($_REQUEST['contactidAttach'][0]) && $_REQUEST['contactidAttach'][0] != '' && is_numeric($_REQUEST['contactidAttach'][0])) {
            $set_update .= ', dest_user = null, dest_contact_id = '.$_REQUEST['contactidAttach'][0].', dest_address_id = '.$_REQUEST['addressidAttach'][0];
        } elseif (isset($_REQUEST['contactidAttach'][0]) && $_REQUEST['contactidAttach'][0] != '' && !is_numeric($_REQUEST['contactidAttach'][0])) {
            $set_update .= ", dest_user = '".$_REQUEST['contactidAttach'][0]."', dest_contact_id = null, dest_address_id = null";
        } else {
            $set_update .= ', dest_user = null, dest_contact_id = null, dest_address_id = null';
        }
        if ((int) $_REQUEST['relation'] > 1) {
            $column_res = 'res_id_version';
        } else {
            $column_res = 'res_id';
        }

        //IF FILE IS EDITED
        if ($_SESSION['upfile'][0]['upAttachment'] != false) {
            //RETRIEVE CURRENT ATTACHMENT FILE
            $stmt = $db->query('SELECT fingerprint, docserver_id, status FROM res_view_attachments WHERE '.$column_res." = ? and res_id_master = ? and status <> 'OBS'", array($_REQUEST['res_id'], $_SESSION['doc_id']));
            $res = $stmt->fetchObject();

            require_once 'core/class/docserver_types_controler.php';
            require_once 'core/docservers_tools.php';
            $docserverTypeControler = new docserver_types_controler();
            $docserver              = $docserverControler->get($res->docserver_id);
            $docserverTypeObject    = $docserverTypeControler->get($docserver->docserver_type_id);
            
            //HASH OLD AND NEW ATTACHMENT FILE
            $NewHash = Ds_doFingerprint($_SESSION['upfile'][0]['tmp_name'], $docserverTypeObject->fingerprint_mode);
            $OriginalHash = $res->fingerprint;

            //SAVE NEW ATTACHMENT FILE (IF <> HASH)
            if ($OriginalHash != $NewHash) {
                $path_parts = pathinfo($_SESSION['upfile'][0]['fileNameOnTmp']);
                $fileInfos = array(
                    'tmpDir' => $_SESSION['config']['tmppath'],
                    'size' => $_SESSION['upfile'][0]['size'],
                    'format' => $path_parts['extension'],
                    'tmpFileName' => $_SESSION['upfile'][0]['fileNameOnTmp'],
                );
                $storeResult = array();
                $storeResult = $docserverControler->storeResourceOnDocserver(
                    $_SESSION['collection_id_choice'],
                    $fileInfos
                );
                if (isset($storeResult['error']) && $storeResult['error'] != '') {
                    $error = $storeResult['error'];
                } else {
                    $filetmp = $storeResult['path_template'];
                    $tmp = $storeResult['destination_dir'];
                    $tmp = str_replace('#', DIRECTORY_SEPARATOR, $tmp);
                    $filetmp .= $tmp;
                    $filetmp .= $storeResult['file_destination_name'];
                    $docserverTypeControler = new docserver_types_controler();
                    $docserver = $docserverControler->get($storeResult['docserver_id']);
                    $docserverTypeObject = $docserverTypeControler->get($docserver->docserver_type_id);
                    $fingerprint = Ds_doFingerprint($filetmp, $docserverTypeObject->fingerprint_mode);
                    $filesize = filesize($filetmp);

                    //CREATE SQL UPDATE (FOR METAS DATA FILE)
                    $set_update .= ', fingerprint = :fingerprint';
                    $set_update .= ', filesize = :filesize';
                    $set_update .= ', path = :path';
                    $set_update .= ', filename = :filename';
                    $set_update .= ', docserver_id = :docserver_id';
                    $arrayPDO = array_merge(
                        $arrayPDO,
                        array(':fingerprint' => $fingerprint,
                                ':filesize' => $filesize,
                                ':path' => $storeResult['destination_dir'],
                                ':filename' => $storeResult['file_destination_name'],
                                ':docserver_id' => $storeResult['docserver_id'], )
                        );
                }
            }
        }

        $set_update .= ', doc_date = '.$req->current_datetime().', updated_by = :updated_by';
        $arrayPDO = array_merge($arrayPDO, array(':updated_by' => $_SESSION['user']['UserId']));
        if (!empty($_REQUEST['effectiveDateStatus'])) {
            $set_update .= ', status = :effectiveStatus';
            $arrayPDO = array_merge($arrayPDO, array(':effectiveStatus' => $_REQUEST['effectiveDateStatus'][0]));
        } elseif ($res->status == 'TMP') {
            $set_update .= ", status = 'A_TRA'";
        }
        $arrayPDO = array_merge($arrayPDO, array(':res_id' => $_REQUEST['res_id']));

        //UPDATE QUERY
        if ((int) $_REQUEST['relation'] == 1) {
            $stmt = $db->query('UPDATE res_attachments SET '.$set_update.' WHERE res_id = :res_id', $arrayPDO);
            if ($_SESSION['upfile'][0]['fileNamePdfOnTmp'] != '' && $_SESSION['upfile'][0]['upAttachment'] != false) {
                \Convert\models\AdrModel::deleteAttachAdr([
                    'resId'         => $_REQUEST['res_id'],
                    'isVersion'     => false
                ]);
            }
        } else {
            $stmt = $db->query('UPDATE res_version_attachments SET '.$set_update.' WHERE res_id = :res_id', $arrayPDO);
            if ($_SESSION['upfile'][0]['fileNamePdfOnTmp'] != '' && $_SESSION['upfile'][0]['upAttachment'] != false) {
                \Convert\models\AdrModel::deleteAttachAdr([
                    'resId'         => $_REQUEST['res_id'],
                    'isVersion'     => true
                ]);
            }
        }
    }
    if (!empty($_REQUEST['mailing'])) {
        $select = [
            'typist', 'format', 'status', 'title', 'attachment_type', 'in_signature_book', 'res_id_master',
            'validation_date', 'effective_date', 'identifier', 'docserver_id', 'path', 'filename'
        ];
        if ($is_new_version || $_REQUEST['relation'] != 1) {
            if ($_REQUEST['relation'] != 1) {
                $id = $_REQUEST['res_id'];
            }
            $attachmentToProcess = \Attachment\models\AttachmentModel::getById([
                'select'    => $select,
                'id'        => $id,
                'isVersion' => true
            ]);
            \Attachment\models\AttachmentModel::update([
                'set'       => ['status' => 'DEL'],
                'where'     => ['res_id = ?'],
                'data'      => [$id],
                'isVersion' => true
            ]);
        } else {
            $attachmentToProcess = \Attachment\models\AttachmentModel::getById([
                'select'    => $select,
                'id'        => $_REQUEST['res_id'],
                'isVersion' => false
            ]);
            \Attachment\models\AttachmentModel::update([
                'set'       => ['status' => 'DEL'],
                'where'     => ['res_id = ?'],
                'data'      => [$_REQUEST['res_id']],
                'isVersion' => false
            ]);
        }

        $_SESSION['data'] = [];
        $_SESSION['data'][] = [
            'column'    => 'typist',
            'value'     => $attachmentToProcess['typist'],
            'type'      => 'string'
        ];
        $_SESSION['data'][] = [
            'column'    => 'format',
            'value'     => $attachmentToProcess['format'],
            'type'      => 'string'
        ];
        $_SESSION['data'][] = [
            'column'    => 'status',
            'value'     => 'A_TRA',
            'type'      => 'string'
        ];
        $_SESSION['data'][] = [
            'column'    => 'title',
            'value'     => $attachmentToProcess['title'],
            'type'      => 'string'
        ];
        $_SESSION['data'][] = [
            'column'    => 'attachment_type',
            'value'     => $attachmentToProcess['attachment_type'],
            'type'      => 'string'
        ];
        $_SESSION['data'][] = [
            'column'    => 'coll_id',
            'value'     => 'letterbox_coll',
            'type'      => 'string'
        ];
        $_SESSION['data'][] = [
            'column'    => 'res_id_master',
            'value'     => $attachmentToProcess['res_id_master'],
            'type'      => 'integer'
        ];
        $_SESSION['data'][] = [
            'column'    => 'type_id',
            'value'     => 0,
            'type'      => 'integer'
        ];
        $_SESSION['data'][] = [
            'column'    => 'relation',
            'value'     => 1,
            'type'      => 'integer'
        ];
        if (!empty($attachmentToProcess['validation_date'])) {
            $_SESSION['data'][] = [
                'column'    => 'validation_date',
                'value'     => $attachmentToProcess['validation_date'],
                'type'      => 'date'
            ];
        }
        if (!empty($attachmentToProcess['effective_date'])) {
            $_SESSION['data'][] = [
                'column'    => 'effective_date',
                'value'     => $attachmentToProcess['effective_date'],
                'type'      => 'date'
            ];
        }
        if (!empty($attachmentToProcess['in_signature_book'])) {
            $_SESSION['data'][] = [
                'column'    => 'in_signature_book',
                'value'     => 1,
                'type'      => 'bool'
            ];
        }

        $contactsForMailing = \SrcCore\models\DatabaseModel::select([
            'select'    => ['*'],
            'table'     => ['contacts_res'],
            'where'     => ['res_id = ?', 'address_id != ?'],
            'data'      => [$_SESSION['doc_id'], 0]
        ]);
        $docserver = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $attachmentToProcess['docserver_id'], 'select' => ['path_template']]);
        $pathToAttachmentToCopy = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $attachmentToProcess['path']) . $attachmentToProcess['filename'];

        foreach ($contactsForMailing as $key => $contactForMailing) {
            $chronoPubli = $attachmentToProcess['identifier'] . '-' . chr(ord('A') + $key);
            $params = [
                'userId' => $_SESSION['user']['UserId'],
                'res_id' => $_SESSION['doc_id'],
                'coll_id' => 'letterbox_coll',
                'res_view' => 'res_view_attachments',
                'res_table' => 'res_attachments',
                'res_contact_id' => $contactForMailing['contact_id'],
                'res_address_id' => $contactForMailing['address_id'],
                'pathToAttachment' => $pathToAttachmentToCopy,
                'chronoAttachment' => $chronoPubli,
            ];

            $filePathOnTmp = \Template\controllers\TemplateController::mergeDatasource($params);

            $fileInfos = [
                'tmpDir'        => $_SESSION['config']['tmppath'],
                'size'          => filesize($filePathOnTmp),
                'format'        => $attachmentToProcess['format'],
                'tmpFileName'   => str_replace($_SESSION['config']['tmppath'], '', $filePathOnTmp)
            ];

            $storeResult = $docserverControler->storeResourceOnDocserver($_SESSION['collection_id_choice'], $fileInfos);
            foreach ($_SESSION['data'] as $dataKey => $dataValue) {
                if (in_array($dataValue['column'], ['docserver_id', 'dest_contact_id', 'dest_address_id', 'identifier'])) {
                    unset($_SESSION['data'][$dataKey]);
                }
            }
            $_SESSION['data'][] = [
                'column' => 'docserver_id',
                'value' => $storeResult['docserver_id'],
                'type' => 'string'
            ];
            $_SESSION['data'][] = [
                'column' => 'dest_contact_id',
                'value' => $contactForMailing['contact_id'],
                'type' => 'integer'
            ];
            $_SESSION['data'][] = [
                'column' => 'dest_address_id',
                'value' => $contactForMailing['address_id'],
                'type' => 'integer'
            ];
            $_SESSION['data'][] = [
                'column' => 'identifier',
                'value' => $chronoPubli,
                'type' => 'string'
            ];

            $id = $resAttach->load_into_db(
                'res_attachments',
                $storeResult['destination_dir'],
                $storeResult['file_destination_name'],
                $storeResult['path_template'],
                $storeResult['docserver_id'],
                $_SESSION['data'],
                $_SESSION['config']['databasetype']
            );
        }
    }

    //copie de la version PDF de la pièce si mode de conversion sur le client
    if ($_SESSION['upfile'][0]['fileNamePdfOnTmp'] != '' && empty($error) && $_SESSION['upfile'][0]['upAttachment'] != false && empty($_REQUEST['mailing'])) {
        if ($id != null) {
            $_SESSION['new_id'] = $id;
        } else {
            $_SESSION['new_id'] = $_REQUEST['res_id'];
        }
        

        if ((int) $_REQUEST['relation'] == 1 && $is_new_version == false) {
            $targetCollId = 'attachments_coll';
            $targetAdrVersion = false;
        } else {
            $targetCollId = 'attachments_version_coll';
            $targetAdrVersion = true;
        }

        $resource = file_get_contents($_SESSION['config']['tmppath'] . $_SESSION['upfile'][0]['fileNamePdfOnTmp']);
        $pathInfo = pathinfo($_SESSION['config']['tmppath'] . $_SESSION['upfile'][0]['fileNamePdfOnTmp']);
        $storeResult = \Docserver\controllers\DocserverController::storeResourceOnDocServer([
            'collId'            => $targetCollId,
            'docserverTypeId'   => 'CONVERT',
            'encodedResource'   => base64_encode($resource),
            'format'            => $pathInfo['extension']
        ]);

        $result = \Convert\models\AdrModel::createAttachAdr([
            'resId'         => $_SESSION['new_id'],
            'isVersion'     => $targetAdrVersion,
            'type'          => 'PDF',
            'docserverId'   => $storeResult['docserver_id'],
            'path'          => $storeResult['destination_dir'],
            'filename'      => $storeResult['file_destination_name'],
            'fingerprint'   => $storeResult['fingerPrint'],
        ]);
    }

    if (empty($error)) {
        //DELETE TEMPORARY BACKUP
        $stmt = $db->query("SELECT attachment_id_master 
                            FROM res_version_attachments
                            WHERE res_id = ? and status = 'TMP' and res_id_master = ?", array($_SESSION['attachmentInfo']['inProgressResId'], $_SESSION['doc_id']));
        $previous_attachment = $stmt->fetchObject();

        $db->query("DELETE FROM res_version_attachments WHERE attachment_id_master = ? and status = 'TMP'", array($previous_attachment->attachment_id_master));

        //ADD ACTION IN HISTORY
        if ($_SESSION['history']['attachup'] == 'true') {
            $hist = new history();
            $view = $sec->retrieve_view_from_coll_id(
                $_SESSION['collection_id_choice']
            );
            $hist->add(
                $view,
                $_SESSION['doc_id'],
                'UP',
                'attachup',
                ucfirst(_DOC_NUM).$id.' '
                ._ATTACH_UPDATED,
                $_SESSION['config']['databasetype'],
                'apps'
            );
            $content = _ATTACH_UPDATED;
            $hist->add(
                RES_ATTACHMENTS_TABLE,
                $id,
                'UP',
                'attachup',
                $_SESSION['info'].' ('.$title
                .') ',
                $_SESSION['config']['databasetype'],
                'attachments'
            );
        }

        //EXTRAS JS FOR TABS
        if (isset($_REQUEST['fromDetail']) && $_REQUEST['fromDetail'] == 'attachments') {
            $js .= 'eleframe1 =  parent.document.getElementsByName(\'uniqueDetailsIframe\');';
            $js .= 'eleframe1[0].src = \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=show_attachments_details_tab&load';
            $js .= '&attach_type_exclude=response_project,signed_response,outgoing_mail_signed,converted_pdf,outgoing_mail,print_folder,aihp&fromDetail=attachments&collId=letterbox_coll&resId='.$_SESSION['doc_id'];
        } elseif (isset($_REQUEST['fromDetail']) && $_REQUEST['fromDetail'] == 'response') {
            $js .= 'eleframe1 =  parent.document.getElementsByName(\'uniqueDetailsIframe\');';
            $js .= 'eleframe1[0].src = \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=show_attachments_details_tab&load';
            $js .= '&attach_type=response_project,outgoing_mail_signed,signed_response,outgoing_mail,aihp&fromDetail=response&collId=letterbox_coll&resId='.$_SESSION['doc_id'];
        } else {
            $js .= 'var eleframe1 =  parent.document.getElementsByName(\'list_attach\');';
            $js .= 'eleframe1[0].src = \''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=frame_list_attachments&attach_type_exclude=converted_pdf,print_folder&load';
        }
        $js .= '\';';

        //RAZ SESSIONS
        if (!isset($_SESSION['new_id'])) {
            $_SESSION['new_id'] = 0;
        }
        unset($_SESSION['upfile'][0]['fileNamePdfOnTmp']);
        unset($_SESSION['new_id']);
        unset($_SESSION['attachmentInfo']);
        unset($_SESSION['resIdVersionAttachment']);
        unset($_SESSION['targetAttachment']);
    }

    echo '{"status" : "'.$status.'", "content" : "'.addslashes(_parse($content)).'", "title" : "'.addslashes($title).'", "isVersion" : "'.$isVersion.'", "error" : "'.addslashes($error).'", "majFrameId" : "'.$_SESSION['new_id'].'", "exec_js" : "'.addslashes($js).'", "cur_id" : "'.$_REQUEST['res_id'].'"}';

    exit();
}

//INITIALIZE EDIT MODE
if (isset($_REQUEST['id'])) {
    $mode = 'edit';
    $title = _MODIFY_ANSWER;
    $_SESSION['targetAttachment'] = 'edit';
    $resId = $_REQUEST['id'];
    unset($_SESSION['upfile']);

    //GET DATAS attachment
    $infoAttach = (object) $ac->getAttachmentInfos($resId);
    if (!file_exists($infoAttach->pathfile)) {
        $status = 1;
        $error = _FILE_NOT_EXISTS_ON_THE_SERVER;

        echo '{"status" : "'.$status.'", "content" : "'.addslashes(_parse($content)).'", "title" : "'.addslashes($title).'", "isVersion" : "'.$isVersion.'", "error" : "'.addslashes($error).'", "majFrameId" : "'.$_SESSION['new_id'].'", "exec_js" : "'.addslashes($js).'", "cur_id" : "'.$_REQUEST['res_id'].'"}';
        exit;
    } else {
        //RETRIEVE ATTACHMENT FILE
        $_SESSION['upfile'][0]['upAttachment'] = false;
        $_SESSION['upfile'][0]['size'] = filesize($infoAttach->pathfile);
        $_SESSION['upfile'][0]['format'] = $infoAttach->format;
        $_SESSION['upfile'][0]['fileNamePdfOnTmp'] = $infoAttach->pathfile_pdf;

        $viewResourceArr = $docserverControler->viewResource(
            $resId,
            $infoAttach->target_table_origin,
            'adr_attachments',
            false
        );
        $_SESSION['upfile'][0]['fileNameOnTmp'] = str_replace($viewResourceArr['tmp_path'].DIRECTORY_SEPARATOR, '', $viewResourceArr['file_path']);
    }
} else {
    //INITIALIZE ADD MODE
    $mode = 'add';
    $title = _ATTACH_ANSWER;

    $_SESSION['targetAttachment'] = 'add';
    unset($_SESSION['upfile']);
    unset($_SESSION['transmissionContacts']);

    //GET DATAS attachment
    $infoAttach = (object) $ac->initAttachmentInfos($_SESSION['doc_id']);

    //On recherche le type de document attaché à ce courrier
}
    $stmt = $db->query('SELECT r.type_id, r.creation_date, m.category_id FROM res_letterbox r INNER JOIN mlb_coll_ext m ON m.res_id = r.res_id WHERE r.res_id = ?', array($_SESSION['doc_id']));
    $mail_doctype = $stmt->fetchObject();
    $type_id = $mail_doctype->type_id;
    $category_id = $mail_doctype->category_id;
    $dataForDate = $mail_doctype->creation_date;
    //On recherche le sve_type
    $stmt = $db->query('SELECT * FROM mlb_doctype_ext WHERE type_id = ?', array($type_id));
    $sve = $stmt->fetchObject();
    $sve_type = $sve->process_mode;
    //On met tous les attachments ayant le type_sve attaché au courrier dans un tableau
    $attachments_types_for_process = array();
    foreach ($_SESSION['attachment_types_with_process'] as $key => $value) {
        if ($sve_type == $value or $value == '') {
            $attachments_types_for_process[$key] = $_SESSION['attachment_types'][$key];
        }
    }

//BEGIN FORM ATTACHMENT
//INITIALIZE
unset($_SESSION['adresses']);
unset($_SESSION['cm_applet'][$_SESSION['user']['UserId']]);
$objectTable = 'res_letterbox';

//BEDING HEADER
$content .= '<h2>&nbsp;'.$title;

//multicontact

if (!empty($infoAttach->multi_contact)) {
    $content .= ' pour le contact : <select style="background-color: #FFF;border: 1px solid #999;color: #666;text-align: left;" id="selectContactIdRes" onchange="loadSelectedContact()">';

    $content .= '<option value="mailing">Publipostage</option>';
    foreach ($infoAttach->multi_contact as $key => $value) {
        $content .= '<option value="'.$value['contact_id'].'#'.$value['address_id'].'#'.$value['format_contact'].'">'.$value['format_contact'].'</option>';
    }
    
    $content .= '</select>';
    $content .= '<script>$j("#selectContactIdRes", window.top.document).change();</script>';
}
$content .= '</h2>';
//END HEADER

//BEGIN FORM
$content .= '<form enctype="multipart/form-data" method="post" name="formAttachment" id="formAttachment" action="#" class="forms" style="width:35%;float:left;margin-left:-5px;background-color:#F2F2F2">';
$content .= '<div class="transmissionDiv" id="addAttach1">';

    if ($infoAttach->status != 'SEND_MASS') {
        $hideMailing = 'display:none;';
    }

    $content .= '<div id="mailingInfo" style="'.$hideMailing.'background: #F8BB30;border-radius: 5px;padding: 10px;">'._MAILING_INFO_1.'<ul style="padding-left: 30px;"><li style="list-style: initial;padding: 5px;">'._MAILING_INFO_2.'</li><li style="list-style: initial;padding: 5px;">'._MAILING_INFO_3.'</li><li style="list-style: initial;padding: 5px;">'._MAILING_INFO_4.'</li></div>';
    $content .= '<hr style="width:85%;margin-left:0px">';
    $content .= '<input type="hidden" id="category_id" value="outgoing"/>';

    if ($mode == 'edit') {
        $content .= '<input type="hidden" name="res_id" id="res_id" value="'.$_REQUEST['id'].'"/>';
        $content .= '<input type="hidden" name="relation" id="relation" value="'.$_REQUEST['relation'].'"/>';
        $_SESSION['relationAttachment'] = $_REQUEST['relation'];
        $_SESSION['resIdVersionAttachment'] = $_REQUEST['id'];
    }
    $content .= '<input type="hidden" name="fromDetail" id="fromDetail" value="'.$_REQUEST['fromDetail'].'"/>';

    //ATTACHMENT TYPE
    $content .= '<p>';
    $content .= '<label>'._ATTACHMENT_TYPES.'</label>';
    if ($mode == 'add') {
        $content .= '<select name="attachment_types[]" id="attachment_types" onchange="affiche_chrono(this);select_template(\''.$_SESSION['config']['businessappurl']
        .'index.php?display=true&module=templates&page='
        .'select_templates\', this);"/>';
        $content .= '<option value="">'._CHOOSE_ATTACHMENT_TYPE.'</option>';
        foreach (array_keys($attachments_types_for_process) as $attachmentType) {
            if (empty($_SESSION['attachment_types_get_chrono'][$attachmentType][0])) {
                $_SESSION['attachment_types_get_chrono'][$attachmentType] = '';
            }
            if (empty($_SESSION['attachment_types_with_delay'][$attachmentType][0])) {
                $_SESSION['attachment_types_with_delay'][$attachmentType] = '';
            }
            if ($_SESSION['attachment_types_show'][$attachmentType] == 'true') {
                $content .= '<option value="'.$attachmentType.'" width_delay="'.$_SESSION['attachment_types_with_delay'][$attachmentType].'" with_chrono = "'.$_SESSION['attachment_types_with_chrono'][$attachmentType].'" get_chrono = "'.$_SESSION['attachment_types_get_chrono'][$attachmentType].'"';

                if (isset($_GET['cat']) && $_GET['cat'] == 'outgoing' && $attachmentType == 'outgoing_mail') {
                    $content .= ' selected = "selected"';
                    $content .= '<script>$("attachment_types").onchange();</script>';
                }
                $content .= '>';
                $content .= $_SESSION['attachment_types'][$attachmentType];
                $content .= '</option>';
            }
        }
        $content .= '</select>&nbsp;<span class="red_asterisk" id="attachment_types_mandatory"><i class="fa fa-star"></i></span>';
    } else {
        $content .= '<select name="attachment_types[]" id="attachment_types" onchange="affiche_chrono(this);select_template(\''.$_SESSION['config']['businessappurl']
            .'index.php?display=true&module=templates&page=select_templates\', this, \'edit\');"/>';
        $content .= '<option value="">'._CHOOSE_ATTACHMENT_TYPE.'</option>';
        foreach (array_keys($attachments_types_for_process) as $attachmentType) {
            if (empty($_SESSION['attachment_types_get_chrono'][$attachmentType][0])) {
                $_SESSION['attachment_types_get_chrono'][$attachmentType] = '';
            }
            if (empty($_SESSION['attachment_types_with_delay'][$attachmentType][0])) {
                $_SESSION['attachment_types_with_delay'][$attachmentType] = '';
            }
            if ($_SESSION['attachment_types_show'][$attachmentType] == 'true') {
                $content .= '<option value="'.$attachmentType.'" width_delay="'.$_SESSION['attachment_types_with_delay'][$attachmentType].'" with_chrono = "'.$_SESSION['attachment_types_with_chrono'][$attachmentType].'" get_chrono = "'.$_SESSION['attachment_types_get_chrono'][$attachmentType].'"';

                if ($_SESSION['attachment_types'][$attachmentType] == $_SESSION['attachment_types'][$infoAttach->attachment_type]) {
                    $content .= ' selected = "selected"';
                }
                $content .= '>';
                $content .= $_SESSION['attachment_types'][$attachmentType];
                $content .= '</option>';
            }
        }
        $content .= '</select>&nbsp;<span class="red_asterisk" id="attachment_types_mandatory"><i class="fa fa-star"></i></span>';

//        $content .= '<input type="text" name="attachment_types_show[]" id="attachment_types_show" value="'.$_SESSION['attachment_types'][$infoAttach->attachment_type].'" disabled class="readonly"/>';
        $content .= '<input type="hidden" name="attachment_types[]" id="attachment_types" value="'.$infoAttach->attachment_type.'" readonly class="readonly"/>';
    }
    $content .= '</p>';

    //PJ CHRONO
    $content .= '<p>';
    $content .= '<label id="chrono_label" name="chrono_label[]" style="display:none">'._CHRONO_NUMBER.'</label>';
    $content .= '<input type="text" name="chrono_display[]" id="chrono_display" value="'.$infoAttach->identifier.'"  style="display:none" disabled class="readonly"/>';
    if ($mode == 'add') {
        $content .= '<select name="get_chrono_display[]" id="get_chrono_display" style="display:none" onchange="$(\'chrono\').value=this.options[this.selectedIndex].value"/>';
    } elseif (!empty($infoAttach->identifier)) {
        $js .= '$j("#chrono_label,#chrono_display", window.top.document).show();';
    }
    $content .= '<input type="hidden" name="chrono[]" id="chrono" value="'.$infoAttach->identifier.'"/>';
    $content .= '</p>';

    $content .= '<p style="text-align:left;margin-left:74.5%;"></p>';
    //FILE
    if ($mode == 'add') {
        $content .= '<p>';
        $content .= '<label id="file_label">'._FILE.' <span id="templateOfficeTool"><i class="fa fa-paperclip fa-lg" title="'._LOADED_FILE.'" style="cursor:pointer;" id="attachment_type_icon" onclick="$j(\'#add\').css(\'display\', \'inline\');$(\'attachment_type_icon\').setStyle({color: \'#135F7F\'});$(\'attachment_type_icon2\').setStyle({color: \'#666\'});$(\'templateOffice\').setStyle({display: \'none\'});$(\'templateOffice\').disabled=true;$(\'templateOffice_edit\').setStyle({display: \'none\'});$(\'choose_file\').setStyle({display: \'inline-block\'});document.getElementById(\'choose_file\').contentDocument.getElementById(\'file\').click();displayAddMailing()"></i> <i class="fa fa-file-alt fa-lg" title="'._GENERATED_FILE.'" style="cursor:pointer;color:#135F7F;" id="attachment_type_icon2" onclick="$(\'attachment_type_icon2\').setStyle({color: \'#135F7F\'});$(\'attachment_type_icon\').setStyle({color: \'#666\'});$(\'templateOffice\').setStyle({display: \'inline-block\'});$(\'templateOffice\').disabled=false;$(\'choose_file\').setStyle({display: \'none\'});"></i></span></label>';
        $content .= '<select name="templateOffice[]" id="templateOffice" style="display:inline-block;" onchange="showEditButton(this);">';
        $content .= '<option value="">'._CHOOSE_MODEL.'</option>';

        $content .= '</select>';
        $content .= ' <input type="button" value="';
        $content .= _EDIT_MODEL;
        $content .= '" name="templateOffice_edit[]" id="templateOffice_edit" style="display:none;margin-top: 0" class="button" '
            .'onclick="$j(\'#addMailing\').hide();$j(\'#add\').css(\'display\',\'inline\');showAppletLauncher(this, \''.$_SESSION['doc_id'].'\',\''.$objectTable.'\',\'attachmentVersion\',\'attachment\');$(\'add\').value=\'Edition en cours ...\';editingDoc(this,\''.$_SESSION['user']['UserId'].'\');$(\'add\').disabled=\'disabled\';$(\'add\').style.opacity=\'0.5\';this.hide();$j(\'#\'+this.id).parent().find(\'[name=templateOffice\\\\[\\\\]]\').css(\'width\',\'206px\');"/>';
        $content .= '<iframe style="display:none; width:210px" name="choose_file" id="choose_file" frameborder="0" scrolling="no" height="25" src="'.$_SESSION['config']['businessappurl']
            .'index.php?display=true&module=attachments&page=choose_attachment"></iframe>';

        $content .= '&nbsp;<span class="red_asterisk" id="templateOffice_mandatory"><i class="fa fa-star"></i></span>';
        $content .= '</p>';
    }

    if (isset($statusEditAttachment) && $statusEditAttachment == 'TMP') {
        $content .= '<p align="middle"><span style="color:green">'._RETRIEVE_BACK_UP.'</span></p>';
    }

    //PJ SUBJECT
    $content .= '<p>';
    $content .= '<label>'._OBJECT.'</label>';
    $content .= "<input type='text' name='title[]' id='title' maxlength='250' value=\"".htmlentities(substr($infoAttach->title, 0, 250)).'"/> ';
    $content .= '&nbsp;<span class="red_asterisk" id="templateOffice_mandatory"><i class="fa fa-star"></i></span>';
    $content .= '</p>';

    //BACK DATE
    if ($mode == 'add' || ($mode == 'edit' && (($infoAttach->attachment_type == 'transmission' && $infoAttach->status != 'NO_RTURN') || $infoAttach->attachment_type != 'transmission'))) {
        $content .= '<p>';
        $content .= '<label>'._BACK_DATE.'</label>';
        $content .= "<input type='text' name='back_date[]' id='back_date' onClick='showCalender(this);' onfocus='checkBackDate(this);' onchange='checkBackDate(this);' value='{$req->format_date_db($infoAttach->validation_date)}'/>";
        if ($mode == 'add') {
            $content .= '<select name="effectiveDateStatus[]" id="effectiveDateStatus" style="display:none;margin-left: 20px;width: 105px" onchange="checkEffectiveDateStatus(this);"/>';
            $content .= '<option value="A_TRA">A traiter</option>';
            $content .= '</select>';
        }
        $content .= '</p>';
    }

    //EFFECTIVE BACK DATE (for transmission)
    if ($mode == 'edit' && $infoAttach->attachment_type == 'transmission') {
        $content .= '<p>';
        $content .= '<label>'._EFFECTIVE_DATE.'</label>';
        $content .= "<input type='text' name='effectiveDate[]' id='effectiveDate' onblur='setRturnForEffectiveDate();' onClick='showCalender(this);' onfocus='checkBackDate(this);' onchange='checkBackDate(this);' style='width: 75px' value='{$req->format_date_db($infoAttach->effective_date)}'";
        if ($infoAttach->status == 'NO_RTURN') {
            $content .= ' disabled="disabled" class="readonly"';
        }
        $content .= '/>';
        $content .= '<select name="effectiveDateStatus[]" id="effectiveDateStatus" style="margin-left: 20px;width: 105px" />';

        if ($infoAttach->status == 'EXP_RTURN' || $infoAttach->status == 'RTURN') {
            $content .= '<option value="EXP_RTURN">Attente retour</option>';
            if ($infoAttach->status == 'RTURN') {
                $content .= '<option selected="selected" value="RTURN">Retourné</option>';
            } else {
                $content .= '<option value="RTURN">Retourné</option>';
            }
        } else {
            $content .= '<option selected="selected" value="NO_RTURN">Pas de retour</option>';
        }

        $content .= '</select>';
        $content .= '</p>';
    }
    $content .= "<input type='hidden' name='dataCreationDate' id='dataCreationDate' value='{$dataForDate}' />";

    //CONTACT
    if ($infoAttach->status != 'SEND_MASS') {
        $content .= '<div id="contactDiv" style="margin-bottom:10px;">';
        $content .= '<label>'._DEST_USER_PJ;
        if ($core->test_admin('my_contacts', 'apps', false)) {
            $content .= ' <a id="create_multi_contact" title="'._CREATE_CONTACT
                    .'" onclick="new Effect.toggle(\'create_contact_div_attach\', '
                    .'\'blind\', {delay:0.2});return false;" '
                    .'style="display:inline;" ><i class="fa fa-pencil-alt fa-lg" title="'._CREATE_CONTACT.'"></i></a>';
        }
        $content .= '</label>';
        $content .= '<span style="position:relative;"><input type="text" name="contact_attach[]" onblur="display_contact_card(\'visible\', \'contact_card_attach\');" onkeyup="erase_contact_external_id(\'contact_attach\', \'contactidAttach\');erase_contact_external_id(\'contact_attach\', \'addressidAttach\');" id="contact_attach" onchange="saveContactToSession(this);" value="';
        $content .= $infoAttach->contact_show;
        $content .= '"/><div id="show_contacts_attach" class="autocomplete autocompleteIndex" style="width: 100%;left: 0px;"></div><div class="autocomplete autocompleteIndex" id="searching_autocomplete" style="display: none;text-align:left;padding:5px;width: 100%;left: 0px;"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i> chargement ...</div></span>';
        $content .= '<a id="contact_card_attach" name="contact_card_attach" title="'._CONTACT_CARD.'" onclick="showContactInfo(this,document.getElementById(\'contactidAttach\'),document.getElementById(\'addressidAttach\'));" style="cursor:pointer;"> <i class="fa fa-book fa-lg"></i></a>';
        $content .= '</div>';
        $content .= "<input type='hidden' id='contactidAttach' name='contactidAttach[]' value='{$infoAttach->contact_id}' />";
        $content .= "<input type='hidden' id='addressidAttach' name='addressidAttach[]' value='{$infoAttach->address_id}' />";

        $canCreateContact = $core->test_admin('my_contacts', 'apps', false);
        if (!$canCreateContact) {
            $canCreateContact = 0;
        }
    }
    

    if ($mode == 'add' && $_GET['cat'] != 'outgoing') {
        $content .= '<p>';

        $content .= '<div id="newAttachDiv" style="float: left">';

        //ADD ATTACH OTHER ATTACHEMENT
        $content .= '<input type="button" class="button readonly" id="newAttachButton" value="'._NEW_ATTACH_ADDED.'" title="'._NEW_ATTACH_ADDED.'" onclick="addNewAttach();" disabled="disabled"></input>';
        $content .= '&nbsp;';
        //DEL ATTACH (HIDDEN DEFAULT)
        $content .= '<input type="button" class="button readonly" style="background:#d14836;color:white;display:none;" name="delAttachButton[]" id="delAttachButton" value="'._DELETE.'" title="'._DELETE.'" disabled="disabled"></input>';

        $content .= '</div>';

        $content .= '</p>';
    }

    $content .= "<input type='hidden' id='attachNum' name='attachNum[]' value='0' />";

    //NEW VERSION IN EDIT MODE
    if ($mode == 'edit' && ($infoAttach->status != 'TMP' || ($infoAttach->status == 'TMP' && $infoAttach->relation > 1))) {
        $content .= '<p>';
        $content .= '<label>'._CREATE_NEW_ATTACHMENT_VERSION.'</label>';
        $content .= '<input type="radio" name="new_version" id="new_version_yes" value="yes" onclick="';
        if (!in_array($infoAttach->format, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $content .= '$j(\'#edit\').css(\'visibility\',\'hidden\');';
        }
        $content .= '$j(\'#editModel\').css(\'display\',\'inline-block\');$j(\'#editMailing\').hide();"/>'._YES;
        $content .= '&nbsp;&nbsp;';
        $content .= '<input type="radio" name="new_version" id="new_version_no" checked value="no" onclick="$j(\'#edit\').css(\'visibility\',\'visible\');"/>'._NO;
        $content .= '</p>';
    }
$content .= '</div>';

$content .= '<div id="transmission"></div>';
    $content .= '<p class="buttons">';
        //EDIT MODEL BUTTON
        if ($mode == 'edit' && !in_array($infoAttach->format, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $content .= '<input type="button" value="';
            $content .= _EDIT_MODEL;
            $content .= '" name="editModel" id="editModel" class="button" onclick="$j(\'#editMailing\').hide();$(\'edit\').style.visibility=\'visible\';showAppletLauncher(this, \''.$_SESSION['doc_id'].'\',\''.$objectTable.'\',\'attachmentUpVersion\',\'attachment\');$(\'edit\').value=\'Edition en cours ...\';editingDoc(this,\''.$_SESSION['user']['UserId'].'\');$(\'edit\').disabled=\'disabled\';$(\'edit\').style.opacity=\'0.5\';this.hide();"/>';
        }

    if (!isset($_REQUEST['id']) || (isset($_REQUEST['id']) && $infoAttach->status == 'SEND_MASS')) {
        $content .= '&nbsp;';
        $content .= '&nbsp;';

        $content .= '<input type="button" value="';
        $content .= 'Publipostage';
        if (isset($_REQUEST['id'])) {
            $content .= '" name="editMailing" id="editMailing" class="button" onclick="if(confirm(\'' . _MAILING_CONFIRMATION .'\')){ValidAttachmentsForm(\''.$_SESSION['config']['businessappurl'];
        } else {
            $content .= '" name="addMailing" id="addMailing" class="button" style="display:none;" onclick="if(confirm(\'' . _MAILING_CONFIRMATION .'\')){simpleAjax(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=unsetReservedChronoNumber\');ValidAttachmentsForm(\''.$_SESSION['config']['businessappurl'];
        }

        $content .= 'index.php?display=true&module=attachments&page=attachments_content&mailing=true\', \'formAttachment\'';

        //SIGNATURE BOOK
        if (!empty($_REQUEST['docId'])) {
            $content .= ", '{$mode}'";
        }

        $content .= ');simpleAjax(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=unsetTemporarySaved&mode='.$mode.'\')}"/>';
    }
        $content .= '&nbsp;';
        $content .= '&nbsp;';

        //VALIDATE BUTTON
        $content .= '<input type="button" value="';
        $content .= _VALIDATE;
        if (isset($_REQUEST['id'])) {
            $content .= '" name="edit" id="edit" class="button" onclick="ValidAttachmentsForm(\''.$_SESSION['config']['businessappurl'];
        } else {
            $content .= '" name="add" id="add" class="button" onclick="simpleAjax(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=unsetReservedChronoNumber\');ValidAttachmentsForm(\''.$_SESSION['config']['businessappurl'];
        }
        $content .= 'index.php?display=true&module=attachments&page=attachments_content\', \'formAttachment\'';

        //SIGNATURE BOOK
        if (!empty($_REQUEST['docId'])) {
            $content .= ", '{$mode}'";
        }

        $content .= ');simpleAjax(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=unsetTemporarySaved&mode='.$mode.'\')"/>';

        $content .= '&nbsp;';
        $content .= '&nbsp;';
        $content .= '<label>&nbsp;</label>';

        //CANCEL BUTTON
        $content .= '<input id="cancelpj" type="button" value="';
        $content .= _CANCEL;
        $content .= '" name="cancel" class="button"  onclick="simpleAjax(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=unsetReservedChronoNumber\');';
        $content .= 'simpleAjax(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=attachments&page=unsetTemporarySaved&mode='.$mode.'\');destroyModal(\'form_attachments\');"/>';

    $content .= '</p>';
$content .= '</form>';

//EXTRA JS
$content .= '<script>launch_autocompleter2_contacts_v2("'.$_SESSION['config']['businessappurl'].'index.php?display=true&dir=indexing_searching&page=autocomplete_contacts", "contact_attach", "show_contacts_attach", "", "contactidAttach", "addressidAttach")</script>';
$content .= '<script>display_contact_card(\'visible\', \'contact_card_attach\');</script>';

//IFRAME CREATE CONTACT
if ($core->test_admin('my_contacts', 'apps', false)) {
    $content .= '<div id="create_contact_div_attach" style="display:none;float:right;width:65%;background-color:#F2F2F2">';
    $content .= '<iframe width="100%" height="550" src="'.$_SESSION['config']['businessappurl']
                .'index.php?display=false&dir=my_contacts&page=create_contact_iframe&fromAttachmentContact=Y&transmissionInput=0" name="contact_iframe_attach" id="contact_iframe_attach"'
                .' scrolling="auto" frameborder="0" style="display:block;">'
                .'</iframe>';
    $content .= '</div>';
}
//IFRAME INFO CONTACT
$content .= '<div id="info_contact_div_attach" style="display:none;float:right;width:65%;background-color:#F2F2F2">';
    $content .= '<iframe width="100%" height="800" name="contact_card_attach_iframe" id="contact_card_attach_iframe"'
            .' scrolling="auto" frameborder="0" style="display:block;">'
            .'</iframe>';
$content .= '</div>';

$content .= '<div style="float: right; width: 65%">';

//TABS
$content .= '<div id="menuOnglet">';
    $content .= '<ul id="ongletAttachement" style="cursor:pointer;width:auto;">';
        $content .= '<li id="MainDocument" onclick="activePjTab(this)"><span> '._MAIN_DOCUMENT.' </span></li>';
        if ($mode == 'edit') {
            $content .= '<li id="PjDocument_0" onclick="activePjTab(this)"><span> PJ n°1 </span></li>';
        }
    $content .= '</ul>';
$content .= '</div>';

// ATTACHMENT IFRAME
if ($mode == 'edit') {
    $srcAttachment = '../../rest/res/'.functions::xssafe($_SESSION['doc_id']).'/attachments/'.$_REQUEST['id'] . '/content';
    $content .= '<iframe src="'.$srcAttachment.'" name="iframePjDocument_0" id="iframePjDocument_0" scrolling="auto" frameborder="0" style="width:100% !important;height:85vh;display:none" onmouseover="this.focus()"></iframe>';
}

// MAIN DOCUMENT IFRAME
$content .= '<iframe src="../../rest/res/'.functions::xssafe($_SESSION['doc_id']).'/content" name="iframeMainDocument" id="iframeMainDocument" scrolling="auto" frameborder="0" style="width:100% !important;height:85vh;display:none" onmouseover="this.focus()"></iframe>';

$content .= '</div>';

if ($mode == 'add') {
    $js .= 'setTimeout(function(){window.parent.document.getElementById(\'MainDocument\').click()}, 1000);';
} else {
    $js .= 'setTimeout(function(){window.top.document.getElementById(\'PjDocument_0\').click()}, 1000);';
}

echo '{status : '.$status.", content : '".addslashes(_parse($content))."', error : '".addslashes($error)."', exec_js : '".addslashes($js)."'}";
exit();
