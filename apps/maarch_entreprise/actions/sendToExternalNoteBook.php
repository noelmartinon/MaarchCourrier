<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   sendToExternalNote
*
* @author  dev <dev@maarch.org>
* @ingroup visa
*/

$confirm    = true;
$frm_width  = '400px';
$frm_height = 'auto';
$warnMsg    = '';

$etapes = ['form'];

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
    $html .= '<h2 class="title">' . $labelAction . '</h2>';

    $html .= '<form name="sendToExternalSB" id="sendToExternalSB" method="post" class="forms" action="#">';
    $html .= '<input type="hidden" name="chosen_action" id="chosen_action" value="end_action" />';

    if (!empty($config)) {
        include_once 'modules/visa/class/MaarchParapheurController.php';

        $initializeDatas = MaarchParapheurController::getInitializeDatas($config);
        if (!empty($initializeDatas['error'])) {
            $error = $initializeDatas['error'];
        } else {
            $html .= '<label for="processingUser">' . _USER_MAARCH_PARAPHEUR . '</label><select name="processingUser" id="processingUser">';
            if (!empty($initializeDatas['users'])) {
                foreach ($initializeDatas['users'] as $value) {
                    $html .= '<option value="';
                    $html .= $value['id'];
                    $html .= '">';
                    $html .= $value['firstname'] . ' ' . $value['lastname'];
                    $html .= '</option>';
                }
            }
            $html .= '</select><br /><br /><br /><br />';
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

    if (!empty($_SESSION['stockCheckbox'])) {
        $aResources = $_SESSION['stockCheckbox'];
    } else {
        $aResources = [$_SESSION['doc_id']];
    }

    foreach ($aResources as $resId) {
        $document = \Resource\models\ResModel::getById(['select' => ['docserver_id', 'path', 'filename'], 'resId' => $resId]);
        $extDocument = \Resource\models\ResModel::getExtById(['select' => ['category_id', 'alt_identifier'], 'resId' => $resId]);
        if (empty($document) || empty($extDocument)) {
            $_SESSION['action_error'] = 'Document does not exists : ' . $resId;
            return false;
        }

        $convertedDocument = Convert\controllers\ConvertPdfController::getConvertedPdfById(['resId' => $resId, 'collId' => 'letterbox_coll', 'isVersion' => false]);

        if (empty($convertedDocument['errors'])) {
            $documentTodisplay        = $convertedDocument;
            $document['docserver_id'] = $documentTodisplay['docserver_id'];
            $document['path']         = $documentTodisplay['path'];
            $document['filename']     = $documentTodisplay['filename'];
        }

        if ($extDocument['category_id'] == 'outgoing') {
            $attachment = \Attachment\models\AttachmentModel::getOnView([
                'select'    => ['res_id', 'res_id_version', 'docserver_id', 'path', 'filename'],
                'where'     => ['res_id_master = ?', 'attachment_type = ?', 'status not in (?)'],
                'data'      => [$resId, 'outgoing_mail', ['DEL', 'OBS', 'FRZ']],
                'limit'     => 1
            ]);
            if (!empty($attachment[0])) {
                $attachmentTodisplay = $attachment[0];
                $id                  = (empty($attachmentTodisplay['res_id']) ? $attachmentTodisplay['res_id_version'] : $attachmentTodisplay['res_id']);
                $isVersion           = empty($attachmentTodisplay['res_id']);
                if ($isVersion) {
                    $collId = "attachments_version_coll";
                } else {
                    $collId = "attachments_coll";
                }
                $convertedDocument = \Convert\controllers\ConvertPdfController::getConvertedPdfById(['resId' => $id, 'collId' => $collId, 'isVersion' => $isVersion]);
                if (empty($convertedDocument['errors'])) {
                    $attachmentTodisplay = $convertedDocument;
                }
                $document['docserver_id'] = $attachmentTodisplay['docserver_id'];
                $document['path']         = $attachmentTodisplay['path'];
                $document['filename']     = $attachmentTodisplay['filename'];
            }
        }

        $docserver = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $document['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
        if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
            $_SESSION['action_error'] = 'Problem with docserver : ' . $document['docserver_id'];
            return false;
        }

        $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $document['path']) . $document['filename'];
        if (!is_file($pathToDocument)) {
            $_SESSION['action_error'] = _FILE_MISSING . ' : ' . $pathToDocument;
            return false;
        }
    }

    return true;
}

function manage_form($arr_id, $history, $id_action, $label_action, $status, $coll_id, $table, $values_form)
{
    $result = '';
    $config = getXml();

    $coll_id = $_SESSION['current_basket']['coll_id'];
    $message = '';

    foreach ($arr_id as $res_id) {
        $result .= $res_id.'#';
        
        if (!empty($config)) {
            $processingUser     = get_value_fields($values_form, 'processingUser');
            $processingUserInfo = \ExternalSignatoryBook\controllers\MaarchParapheurController::getUserById(['config' => $config, 'id' => $processingUser]);
            $sendedInfo         = \ExternalSignatoryBook\controllers\MaarchParapheurController::sendDatas([
                'config'             => $config,
                'resIdMaster'        => $res_id,
                'processingUser'     => $processingUser,
                'objectSent'         => 'mail',
                'userId'             => $_SESSION['user']['UserId']
            ]);

            if (!empty($sendedInfo['error'])) {
                var_dump($sendedInfo['error']);
                exit;
            } else {
                $attachmentToFreeze = $sendedInfo['sended'];
            }

            $message = ' (à ' . $processingUserInfo['firstname'] . ' ' . $processingUserInfo['lastname'] . ')';
        }

        if (!empty($attachmentToFreeze)) {
            if (!empty($attachmentToFreeze['letterbox_coll'])) {
                \Resource\models\ResModel::update([
                    'set' => ['external_signatory_book_id' => $attachmentToFreeze['letterbox_coll'][$res_id]],
                    'where' => ['res_id = ?'],
                    'data' => [$res_id]
                ]);
            }
            if (!empty($attachmentToFreeze['attachments_coll'])) {
                foreach ($attachmentToFreeze['attachments_coll'] as $resId => $externalId) {
                    \Attachment\models\AttachmentModel::freezeAttachment([
                        'resId' => (int)$resId,
                        'table' => 'res_attachments',
                        'externalId' => $externalId
                    ]);
                }
            }
            if (!empty($attachmentToFreeze['attachments_version_coll'])) {
                foreach ($attachmentToFreeze['attachments_version_coll'] as $resId => $externalId) {
                    \Attachment\models\AttachmentModel::freezeAttachment([
                        'resId' => (int)$resId,
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
            $config['id'] = 'maarchParapheur';
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
