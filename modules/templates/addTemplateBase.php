<?php 
require 'modules/templates/class/templates_controler.php';
$templatesControler = new templates_controler();
if (!in_array($_REQUEST['fileMimeType'],array('application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-excel','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml‌​.slideshow','application/vnd.oasis.opendocument.text','application/vnd.oasis.opendocument.presentation','application/vnd.oasis.opendocument.spreadsheet'))) {
    echo "{\"status\":1,\"error_txt\":\""._EXTENSION_NOT_ALLOWED."\"}";
    exit();
}
if (isset($_REQUEST['fileContent'])) {
    $filename = htmlentities($_REQUEST['fileName'], ENT_NOQUOTES, 'UTF-8');
    
    $filename = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $filename);
    $filename = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $filename); // pour les ligatures e.g. '&oelig;'
    $filename = preg_replace('#[^\w]+#', '_', $filename); // supprime les autres caractères
    $fileContent = base64_decode($_REQUEST['fileContent']);

    if ($_REQUEST['saveTemplateBase'] == "yes") {

        if (is_dir('custom/' . $_SESSION['custom_override_id'] . '/modules/templates/templates/styles')) {
            $dir = 'custom/' . $_SESSION['custom_override_id'] . '/modules/templates/templates/styles';
        } else {
            $dir = 'modules/templates/templates/styles/';
        }
    
        $fileNameOnTmp = $filename . '.' . strtolower(
            $templatesControler->extractFileExt($_REQUEST['fileName'])
            );
        $filePathOnTmp = $dir . DIRECTORY_SEPARATOR . $fileNameOnTmp;
        $inF = fopen($filePathOnTmp, 'w');
        fwrite($inF, $fileContent);
        fclose($inF);
    }
    
    $fileNameOnTmp ='cm_tmp_file_' . $_SESSION['user']['UserId'] . '_' . rand() . '.' . strtolower(
        $templatesControler->extractFileExt($_REQUEST['fileName'])
        );
    $filePathOnTmp = $_SESSION['config']['tmppath'] . DIRECTORY_SEPARATOR . $fileNameOnTmp;
    $inF = fopen($filePathOnTmp, 'w');
    fwrite($inF, $fileContent);
    fclose($inF);
    $_SESSION['m_admin']['templates']['current_style'] = $filePathOnTmp;
    $_SESSION['m_admin']['templates']['template_style'] = $filename;
    echo "{\"status\" : 0}";
} else {
    echo "{\"status\" : 1}";
}
exit;
