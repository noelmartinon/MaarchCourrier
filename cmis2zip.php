<?php

$rootPath = realpath('cmis');

$zip = new ZipArchive();
$zip->open('cmis.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

/** @var SplFileInfo[] $files */
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$zip->addEmptyDir('cmis');
foreach ($files as $name => $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        if ($file->getFilename() != 'access.log') {
            $zip->addFile($filePath, 'cmis/' . $relativePath);
        }
    }
}

$zip->addEmptyDir('cmis/logs');

$rootPath = realpath('modules/folder/Models');

/** @var SplFileInfo[] $files */
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$zip->addEmptyDir('modules/folder/Models');

foreach ($files as $name => $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        $zip->addFile($filePath, 'modules/folder/Models/' . $relativePath);
    }
}

$zip->close();
header("Location: cmis.zip");