<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   sendToExternalSignatureBok
*
* @author  dev <dev@maarch.org>
* @ingroup visa
*/

$confirm    = true;
$frm_width  = '400px';
$frm_height = 'auto';
$warnMsg    = '';

$etapes = ['form'];

$config = getXml();
if (!empty($config) && $config['id'] != 'maarchParapheur') {
    $hasAttachmentError = hasAttachmentError();
    $error_visa_workflow_signature_book = $hasAttachmentError['error'];
}

function get_form_txt($values, $path_manage_action, $id_action, $table, $module, $coll_id, $mode)
{
    $db = new Database();
    $values_str = '';
    if (empty($_SESSION['stockCheckbox'])) {
        for ($i=0; $i<count($values); $i++) {
            $values_str .= $values[$i].', ';
        }
    } else {
        for ($i=0; $i<count($_SESSION['stockCheckbox']); $i++) {
            $values_str .= $_SESSION['stockCheckbox'][$i].', ';
        }
    }
    $values_str = preg_replace('/, $/', '', $values_str);

    $config = getXml();

    $labelAction = '';
    if ($id_action <> '') {
        $stmt = $db->query("SELECT label_action FROM actions WHERE id = ?", array($id_action));
        $resAction = $stmt->fetchObject();
        $labelAction = functions::show_string($resAction->label_action);
    }

    $html = '<div id="frm_error_'.$id_action.'" class="error"></div>';
    $html .= '<h2 class="title">'._ACTION_CONFIRM. '<br>' . $labelAction . '</h2>';

    $html .= '<form name="sendToExternalSB" id="sendToExternalSB" method="post" class="forms" action="#">';
    $html .= '<input type="hidden" name="chosen_action" id="chosen_action" value="end_action" />';
    $htmlModal = '';
    if (!empty($config)) {
        if ($config['id'] == 'ixbus') {
            include_once 'modules/visa/class/IxbusController.php';

            $htmlModal = IxbusController::getModal($config);
        } elseif ($config['id'] == 'iParapheur') {
            include_once 'modules/visa/class/IParapheurController.php';

            $htmlModal = IParapheurController::getModal($config);
        } elseif ($config['id'] == 'fastParapheur') {
            include_once 'modules/visa/class/FastParapheurController.php';

            $htmlModal = FastParapheurController::getModal($config);
        } elseif ($config['id'] == 'maarchParapheur') {
            include_once 'modules/visa/class/MaarchParapheurController.php';

            $htmlModal = MaarchParapheurController::getInitializeDatas($config);

            if (empty($htmlModal['error'])) {
                $aUsersInMP = [];
                foreach ($htmlModal['users'] as $value) {
                    $aUsersInMP[] = $value['id'];
                }
                $documentIds = explode(", ", $values_str);
                foreach ($documentIds as $resId) {
                    $listinstances = \Entity\models\ListInstanceModel::getVisaCircuitByResId(['select' => ['external_id', 'firstname', 'lastname'], 'id' => $resId]);
                    if (empty($listinstances)) {
                        $htmlModal['error'] = _EMPTY_VISA_WORKFLOW;
                        break;
                    }
    
                    foreach ($listinstances as $user) {
                        $externalId = json_decode($user['external_id'], true);
                        if (!in_array($externalId['maarchParapheur'], $aUsersInMP)) {
                            $htmlModal['error'] = _EMPTY_VISA_WORKFLOW;
                            break 2;
                        }
                    }
                }
                $htmlModal = '';
            }
        }

        if (!empty($htmlModal['error'])) {
            $error = $htmlModal['error'];
        } else {
            $html .= $htmlModal;
        }
    } else {
        $error = _FILE . ' modules/visa/xml/remoteSignatoryBooks.xml' . ' ' . _NOT_EXISTS;
    }

    $html .='<div align="center">';
    if (empty($error)) {
        $html .=' <input type="button" name="validate" id="validate" value="'._VALIDATE.'" class="button" ' .
                'onclick="valid_action_form(\'sendToExternalSB\', \'' . $path_manage_action .
                '\', \'' . $id_action . '\', \'' . $values_str . '\', \'res_letterbox\', \'null\', \'letterbox_coll\', \'' .
                $mode . '\');" />&nbsp;';
    } else {
        $html .= '<br>' . $error . '<br><br>';
    }
    $html .='<input type="button" name="cancel" id="cancel" class="button" value="'._CANCEL.'" onclick="pile_actions.action_pop();destroyModal(\'modal_'.$id_action.'\');"/>';

    $html .='</div>';
    $html .='</form>';

    return addslashes($html);
}

function check_form($form_id, $values)
{
    $_SESSION['action_error'] = '';
    $config = getXml();

    if (!empty($config)) {
        if ($config['id'] == 'ixbus') {
            include_once 'modules/visa/class/IxbusController.php';

            $loginIxbus    = get_value_fields($values, 'loginIxbus');
            $passwordIxbus = get_value_fields($values, 'passwordIxbus');

            $userInfo  = IxbusController::getInfoUtilisateur(['config' => $config, 'login' => $loginIxbus, 'password' => $passwordIxbus]);

            if (!empty($userInfo->Identifiant)) {
                return true;
            } else {
                $_SESSION['action_error'] = _WRONG_ID_PASSWORD_IXBUS;
                return false;
            }
        }
        if ($config['id'] == 'maarchParapheur') {
            $hasAttachmentError = hasAttachmentError();
            if (!empty($_SESSION['stockCheckbox'])) {
                $aResources = $_SESSION['stockCheckbox'];
            } else {
                $aResources = [$_SESSION['doc_id']];
            }

            if ($hasAttachmentError['error']) {
                if (!empty($_SESSION['stockCheckbox'])) {
                    $_SESSION['action_error'] = _MAIL_HAS_NO_RESPONSE_PROJECT . ' : ' . implode(",", $hasAttachmentError['resList']);
                } else {
                    $_SESSION['action_error'] = _NO_RESPONSE_PROJECT_VISA;
                }
                return false;
            } else {
                foreach ($aResources as $resId) {
                    $attachments = \Attachment\models\AttachmentModel::getOnView([
                        'select'    => [
                            'res_id', 'res_id_version', 'title', 'identifier', 'attachment_type',
                            'status', 'typist', 'docserver_id', 'path', 'filename', 'creation_date',
                            'validation_date', 'relation', 'attachment_id_master'
                        ],
                        'where'     => ["res_id_master = ?", "attachment_type not in (?)", "status not in ('DEL', 'OBS', 'FRZ', 'TMP')", "in_signature_book = 'true'"],
                        'data'      => [$resId, ['converted_pdf', 'print_folder', 'signed_response']]
                    ]);

                    foreach ($attachments as $value) {
                        if (!empty($value['res_id'])) {
                            $resIdAttachment  = $value['res_id'];
                            $collId = 'attachments_coll';
                            $is_version = false;
                        } else {
                            $resIdAttachment  = $value['res_id_version'];
                            $collId = 'attachments_version_coll';
                            $is_version = true;
                        }
                    
                        $adrInfo       = \Convert\controllers\ConvertPdfController::getConvertedPdfById(['resId' => $resIdAttachment, 'collId' => $collId, 'isVersion' => $is_version]);
                        $docserverInfo = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $adrInfo['docserver_id']]);
                        $filePath      = $docserverInfo['path_template'] . str_replace('#', '/', $adrInfo['path']) . $adrInfo['filename'];
                        if (!is_file($filePath)) {
                            $_SESSION['action_error'] = _FILE_MISSING . ' : ' . $filePath;
                            return false;
                        }
                    }
                }
            }
        }
    }

    return true;
}

function manage_form($arr_id, $history, $id_action, $label_action, $status, $coll_id, $table, $values_form)
{
    $result = '';
    $config = getXml();

    require_once "modules/visa/class/class_modules_tools.php";
    $circuit_visa = new visa();
    $db           = new Database();
    $coll_id      = $_SESSION['current_basket']['coll_id'];
    $message      = '';

    foreach ($arr_id as $res_id) {
        $result .= $res_id.'#';
        \Attachment\controllers\AttachmentController::generateAttachForMailing(['resIdMaster' => $res_id, 'userId' => $_SESSION['user']['UserId']]);
        
        if (!empty($config)) {
            if ($config['id'] == 'ixbus') {
                include_once 'modules/visa/class/IxbusController.php';

                $loginIxbus         = get_value_fields($values_form, 'loginIxbus');
                $passwordIxbus      = get_value_fields($values_form, 'passwordIxbus');
                $nature             = get_value_fields($values_form, 'nature');
                $messageModel       = get_value_fields($values_form, 'messageModel');
                $manSignature       = get_value_fields($values_form, 'mansignature');
                $attachmentToFreeze = IxbusController::sendDatas([
                    'config'        => $config, 'resIdMaster' => $res_id,
                    'loginIxbus'    => $loginIxbus,
                    'passwordIxbus' => $passwordIxbus,
                    'classeurName'  => $nature,
                    'messageModel'  => $messageModel,
                    'manSignature'  => $manSignature
                ]);
            } elseif ($config['id'] == 'iParapheur') {
                include_once 'modules/visa/class/IParapheurController.php';
                $attachmentToFreeze = IParapheurController::sendDatas(['config' => $config, 'resIdMaster' => $res_id]);
            } elseif ($config['id'] == 'fastParapheur') {
                include_once 'modules/visa/class/FastParapheurController.php';
                $sendedInfo = FastParapheurController::sendDatas(['config' => $config, 'resIdMaster' => $res_id]);
                if (!empty($sendedInfo['error'])) {
                    var_dump($sendedInfo['error']);
                    exit;
                } else {
                    $attachmentToFreeze = $sendedInfo['sended'];
                }
            } elseif ($config['id'] == 'maarchParapheur') {
                $listinstances = \Entity\models\ListInstanceModel::getVisaCircuitByResId(['select' => ['external_id', 'users.user_id', 'requested_signature'], 'id' => $res_id]);
                if (empty($listinstances)) {
                    var_dump('No visa workflow');
                }
    
                $workflow = [];
                foreach ($listinstances as $user) {
                    $externalId = json_decode($user['external_id'], true);
                    if (empty($externalId['maarchParapheur'])) {
                        return ['error' => 'Some users do not exist in Maarch Parapheur'];
                    }
                    $workflow[] = ['externalId' => $externalId['maarchParapheur'], 'action' => ($user['requested_signature'] ? 'sign' : 'visa')];
                }

                $sendedInfo = \ExternalSignatoryBook\controllers\MaarchParapheurController::sendDatas([
                    'config'      => $config,
                    'resIdMaster' => $res_id,
                    'objectSent'  => 'attachment',
                    'userId'      => $_SESSION['user']['UserId'],
                    'steps'       => $workflow,
                ]);
                if (!empty($sendedInfo['error'])) {
                    var_dump($sendedInfo['error']);
                    exit;
                } else {
                    $attachmentToFreeze = $sendedInfo['sended'];
                }

                $message = $sendedInfo['historyInfos'];
            }
        }

        if (!empty($attachmentToFreeze)) {
            if (!empty($attachmentToFreeze['attachments_coll'])) {
                foreach ($attachmentToFreeze['attachments_coll'] as $resId => $externalId) {
                    \Attachment\models\AttachmentModel::freezeAttachment([
                        'resId' => $resId,
                        'table' => 'res_attachments',
                        'externalId' => $externalId
                    ]);
                }
            }
            if (!empty($attachmentToFreeze['attachments_version_coll'])) {
                foreach ($attachmentToFreeze['attachments_version_coll'] as $resId => $externalId) {
                    \Attachment\models\AttachmentModel::freezeAttachment([
                        'resId' => $resId,
                        'table' => 'res_version_attachments',
                        'externalId' => $externalId
                    ]);
                }
            }
        }
    }

    return ['result' => $result, 'history_msg' => $message];
}

function getXml()
{
    if (file_exists("custom/{$_SESSION['custom_override_id']}/modules/visa/xml/remoteSignatoryBooks.xml")) {
        $path = "custom/{$_SESSION['custom_override_id']}/modules/visa/xml/remoteSignatoryBooks.xml";
    } else {
        $path = 'modules/visa/xml/remoteSignatoryBooks.xml';
    }

    $config = [];
    if (file_exists($path)) {
        $loadedXml = simplexml_load_file($path);
        if ($loadedXml) {
            $config['id'] = (string)$loadedXml->signatoryBookEnabled;
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == $config['id']) {
                    $config['data'] = (array)$value;
                }
            }
        }
    }

    return $config;
}

 /**
 * Get the value of a given field in the values returned by the form
 *
 * @param $values Array Values of the form to check
 * @param $field String the field
 * @return String the value, false if the field is not found
 **/
function get_value_fields($values, $field)
{
    for ($i=0; $i<count($values); $i++) {
        if ($values[$i]['ID'] == $field) {
            return  $values[$i]['VALUE'];
        }
    }
    return false;
}

function hasAttachmentError()
{
    if (!empty($_SESSION['stockCheckbox'])) {
        $resIds = $_SESSION['stockCheckbox'];
    } else {
        $resIds = [$_SESSION['doc_id']];
    }

    $noAttachment = [];
    foreach ($resIds as $resId) {
        $attachments = \Attachment\models\AttachmentModel::getOnView([
            'select'    => [
                'count(1) as nb'
            ],
            'where'     => ["res_id_master = ?", "attachment_type not in (?)", "status not in ('DEL', 'OBS', 'FRZ', 'TMP')", "in_signature_book = 'true'"],
            'data'      => [$resId, ['converted_pdf', 'print_folder', 'signed_response']]
        ]);
        if ($attachments[0]['nb'] == 0) {
            $noAttachmentsResource = \Resource\models\ResModel::getExtById(['resId' => $resId, 'select' => ['alt_identifier']]);
            $noAttachment[] = $noAttachmentsResource['alt_identifier'];
        }
    }

    if (!empty($noAttachment)) {
        $errorVisaWorkflowSignatureBook = true;
    } else {
        $errorVisaWorkflowSignatureBook = false;
    }

    return ['error' => $errorVisaWorkflowSignatureBook, 'resList' => $noAttachment];
}
