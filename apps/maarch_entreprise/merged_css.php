<?php
include_once('../../core/init.php');
function compress($buffer)
{
    /* remove comments */
    $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
    /* remove tabs, spaces, newlines, etc. */
    $buffer = str_replace(
        array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer
    );
    $buffer = preg_replace('! ?([:}{,;]) ?!', '$1', $buffer);
    return $buffer;
}

$date = mktime(0, 0, 0, date("m") + 2 , date("d"), date("Y"));
$date = date("D, d M Y H:i:s", $date);
$time = 30 * 12 * 60 * 60;
header("Pragma: public");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
//header("Expires: ".$date." GMT");
//header("Cache-Control: max-age=".$time.", must-revalidate");
header('Content-type: text/css; charset=utf-8');

ob_start("compress");

include 'apps/' . $_SESSION['config']['app_id'] . '/css/styles.css';
foreach (array_keys($_SESSION['modules_loaded']) as $value) {
    if (file_exists(
        $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
        . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
        . DIRECTORY_SEPARATOR . $_SESSION['modules_loaded'][$value]['name']
        . DIRECTORY_SEPARATOR . "css" . DIRECTORY_SEPARATOR . "module.css"
    ) || file_exists(
        $_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR
        . $_SESSION['modules_loaded'][$value]['name'] . DIRECTORY_SEPARATOR
        . "css" . DIRECTORY_SEPARATOR . "module.css"
    )
    ) {
        include 'modules/' . $_SESSION['modules_loaded'][$value]['name']
            . '/css/module.css';
    }
}

include_once 'apps/' . $_SESSION['config']['app_id'] . '/css/doctype_levels.css';
include_once 'apps/' . $_SESSION['config']['app_id'] . '/css/chosen.min.css';
readfile('node_modules/tooltipster/dist/css/tooltipster.bundle.min.css');
readfile('node_modules/jquery-typeahead/dist/jquery.typeahead.min.css');
readfile('node_modules/photoswipe/dist/photoswipe.css');
readfile('node_modules/photoswipe/dist/default-skin/default-skin.css');
readfile('apps/maarch_entreprise/css/photoswipe_custom.css');

ob_end_flush();

