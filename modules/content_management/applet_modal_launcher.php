<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   applet_modal_launcher
* @author  dev <dev@maarch.org>
* @ingroup content_management
*/

require_once 'core/class/class_core_tools.php';
$core_tools = new core_tools();

// sessions use for temporary backup
/*if (!isset($_SESSION['attachmentInfo'])) {
    $_SESSION['attachmentInfo'] = array();
}*/
$attachNum = $_REQUEST['uniqueId'];
$_SESSION['attachmentInfo']['attachNum'] = $attachNum;
$_SESSION['attachmentInfo'][$attachNum]['title'] = $_REQUEST['titleAttachment'];
$_SESSION['attachmentInfo'][$attachNum]['chrono'] = $_REQUEST['chronoAttachment'];
$_SESSION['attachmentInfo'][$attachNum]['type'] = $_REQUEST['attachType'];
$_SESSION['attachmentInfo'][$attachNum]['contactId'] = $_REQUEST['contactId'];
$_SESSION['attachmentInfo'][$attachNum]['addressId'] = $_REQUEST['addressId'];
$_SESSION['attachmentInfo'][$attachNum]['back_date'] = $_REQUEST['back_date'];
$_SESSION['attachmentInfo'][$attachNum]['backDateStatus'] = $_REQUEST['backDateStatus'];


if (isset($_REQUEST['attachType']) && $_REQUEST['attachType'] == 'outgoing_mail'){
	$objType = 'outgoingMail';
}
else {
    $objType = $_REQUEST['objectType'];
}

if (
    file_exists(
        $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
        . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
        . DIRECTORY_SEPARATOR . 'content_management' . DIRECTORY_SEPARATOR . 'applet_launcher.php'
    )
) {
    $path = 'custom/'. $_SESSION['custom_override_id'] .'/modules/content_management/applet_launcher.php';
} else {
    $path = 'modules/content_management/applet_launcher.php';
}


$content = '<style type="text/css">html{overflow:hidden}</style>'
    . '<body>'
        . '<div id="container">'
            . '<div id="content">'
                . '<div class="error" id="divError" name="divError"></div>'
                . '<script language="javascript">'
                    . 'loadApplet(\'' 
                        . $_SESSION['config']['coreurl'] . '' . $path
                        . '?objectType=attachment&objectId=' 
                        . $_REQUEST['objectId']
                        . '&objectType='
                        . $objType
                        . '&objectTable='
                        . $_REQUEST['objectTable']
                        . '&uniqueId='
                        . $_REQUEST['uniqueId']
                        . '&resMaster='
                        . $_REQUEST['resMaster']
                        . '&contactId='
                        . $_REQUEST['contactId']
                        . '&addressId='
                        . $_REQUEST['addressId']
                        . '&chronoAttachment='
                        . $_REQUEST['chronoAttachment']
                        . '&custom_override_id='
                        . $_SESSION['custom_override_id']
                        . '\');'
                . '</script>'
                . '<style type="text/css">#CMApplet{width: 100%;height: 100%;text-align: center;padding: 0px;margin: 0px;padding-top: 10px;}</style>'
            . '</div>'
        . '</div>'
    . '</body>';


function _parse($text) {
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "\n", $text);
    $text = str_replace("\n", "\\n ", $text);
    return $text;
}

$status = 0;
$error = $_SESSION['error'];
$js = '';

echo "{status : " . $status . ", content : '" . addslashes(_parse($content)) . "', error : '" . addslashes($error) . "', exec_js : '".addslashes($js)."'}";
exit ();
