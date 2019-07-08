<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

require_once 'core/init.php';
require_once 'install/class/Class_Install.php';

$Class_Install = new Install();
$listLang = $Class_Install->loadLang();

if (!empty($_REQUEST['myVar'])) {
    $path = $_REQUEST['myVar'];
} else {
    $return['status'] = 0;
    $return['text'] = _UPDATE_BACKUP_PATH_EMPTY;

    $jsonReturn = json_encode($return);

    echo $jsonReturn;
    exit;
}

$checkRoot = $Class_Install->checkPathRoot(
    $path
);

if ($checkRoot !== true) {
    $return['status'] = 0;
    $return['text'] = $checkRoot;

    $jsonReturn = json_encode($return);

    echo $jsonReturn;
    exit;
}

$path .= '/backupMaarchCourrier_' . date('Y-m-d');

if (!$Class_Install->createPath($path)) {
    $return['status'] = 0;
    $return['text'] = _CAN_NOT_CREATE_SUB_PATH;

    $jsonReturn = json_encode($return);

    echo $jsonReturn;
    exit;
}

if (!$Class_Install->copy_dir($_SESSION['config']['corepath'], $path . DIRECTORY_SEPARATOR, false, true, ['logs', 'log', 'tmp'])) {
    $return['status'] = 0;
    $return['text'] = _CAN_NOT_COPY_TO . ' : ' . $path . '<br>' . _CHECK_RIGHT_SOURCE_FOLDER;

    $jsonReturn = json_encode($return);

    echo $jsonReturn;
    exit;
}

$return['status'] = 1;
$return['text'] = '';

$jsonReturn = json_encode($return);

echo $jsonReturn;
exit;
