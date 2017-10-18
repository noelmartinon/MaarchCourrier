<?php
//case of res -> new attachment


if (!file_exists($_SESSION['config']['tmppath'] . $_SESSION['upfile']['name'])) {
    $result = array('ERROR' => _THE_DOC . ' ' . $_SESSION['config']['tmppath'] . $_SESSION['upfile']['name'] . ' ' . _EXISTS_OR_RIGHT);
    createXML('ERROR', $result);
} else {
    $func = new functions();
    $fileExtension = $func->extractFileExt($_SESSION['config']['tmppath'] . $_SESSION['upfile']['name']);
    $filePathOnTmp = $_SESSION['config']['tmppath'] . $_SESSION['upfile']['name'];
    // $file = fopen('file.log', a);
    // fwrite($file, '[' . date('Y-m-d H:i:s') . '] ------BEGIN------- ' . PHP_EOL);
    // fwrite($file, '[' . date('Y-m-d H:i:s') . '] EXT ' . $fileExtension . PHP_EOL);
    // fwrite($file, '[' . date('Y-m-d H:i:s') . '] PATH ' . $filePathOnTmp . PHP_EOL);
    // fclose($file);
    // $result = array('ERROR' => _THE_DOC . ' ' . $_SESSION['config']['tmppath'] . $_SESSION['upfile']['name'] . ' ' . _EXISTS_OR_RIGHT);
    // createXML('ERROR', $result);
}
