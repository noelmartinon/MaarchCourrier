<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Extraction of information on PDF / HTML / TXT with lucene functions of Zend Framework
 * @author dev@maarch.org
 * @ingroup full_text
 *
 * A user exit code is stored in fulltext_result column of the document in
 * "res_letterbox" or  "res_attachments" or "res_version_attachments":
 *   1 : Full Text extraction successfull
 *  -1 : No file found
 *  -2 : File extension not allowed for lucene
 *   2 : no result for this extraction
 *
 *  You can use "--debugMode" in command to display more process informations (/!\ log might be heavy !)
 *  You can use "--noLimit" in command to process all documents in one batch (be carefull !)
 *  You can use "--limit {n}" in command to process {n} documents in one batch
 *  You can use "--failed" in command to re analyse documents with -1 result
 */

try {
    include('Maarch_CLITools/ArgsParser.php');
    include('LoggerLog4php.php');
    include('Maarch_CLITools/FileHandler.php');
    include('Maarch_CLITools/ConsoleHandler.php');
} catch (IncludeFileError $e) {
    echo 'Maarch_CLITools required ! \n (pear.maarch.org)\n';
    exit(106);
}

// Load tools
$_ENV['batchName'] = 'fulltext';
$_ENV['wb'] = '';
$_ENV['lckFile'] = '';
$_ENV['totalProcessedResources'] = 0;

include('batch_tools.php');

// Open Logger
$_ENV['logger'] = new Logger4Php();

$logLevel = array_search('--debugMode', $argv);
if ($logLevel > 0) {
    $_ENV['logger']->set_threshold_level('DEBUG');
} else {
    $_ENV['logger']->set_threshold_level('INFO');
}

// global vars of the program
/**
 * Name of the config (usefull for multi instance)
 */
$_ENV['config_name'] = "";
/**
 * Path to the log file
 */
$_ENV['log'] = "";
/**
 * User exit of the program, contains 1 if any problem appears
 */
$_ENV['ErrorLevel'] = 0;

/**
 * Managing of errors
 * @param  $errMsg string text of the error
 */
function errorHandler($errMsg)
{
    $_ENV['logger']->write($errMsg, 'ERROR');
    $_ENV['ErrorLevel'] = 1;
}

/**
 * Check if a folder is empty
 * @param  $dir string path of the directory to chek
 * @return boolean true if the directory exists
 */
function isDirEmpty($dir)
{
    $dir = opendir($dir);
    $isEmpty = true;
    while (($entry = readdir($dir)) !== false) {
        if ($entry !== '.' && $entry !== '..') {
            $isEmpty = false;
            break;
        }
    }
    closedir($dir);
    return $isEmpty;
}

/**
 * Launch the lucene engine if it's a pdf / html / txt file
 * @param  $pathToFile string path of the file to index
 * @param  $indexFileDirectory string directory of the lucene index
 * @param  $format string format of the document to index
 * @param  $id integer id of the document to index
 * @return integer  user exit code stored in fulltext_result column of the
 * document in "res_letterbox" or  "res_attachments" or "res_version_attachments"
 */
function indexFullText($pathToFile, $indexFileDirectory, $format, $Id)
{
    $result = -1;

    $fh = @fopen($pathToFile, 'r');

    if (!$fh && strstr(error_get_last()['message'], 'Permission denied') !== false) {
        errorHandler("{$pathToFile} permission denied!");
    } elseif (!$fh && strstr(error_get_last()['message'], 'No such file or directory') !== false) {
        errorHandler("{$pathToFile} not found!");
    } else {
        fclose($fh);
        switch (strtoupper($format)) {
            case "PDF":
                $result = prepareIndexFullTextPdf($pathToFile, $indexFileDirectory, $Id);
                break;
            case "HTML":
                libxml_use_internal_errors(true);
                $result = prepareIndexFullTextHtml($pathToFile, $indexFileDirectory, $Id);
                break;
            case "MAARCH":
                $result = prepareIndexFullTextHtml($pathToFile, $indexFileDirectory, $Id);
                break;
            case "TXT":
                $result = prepareIndexFullTextTxt($pathToFile, $indexFileDirectory, $Id);
                break;
            default:
                $_ENV['logger']->write(strtoupper($format) . " not allowed for lucene");
                $result = -2;
        }
    }
    return $result;
}

/**
 * Remove word less than two chars and extra white spaces
 * @param  $fileContent text content

 * @return string cleaned text content
 *
 */
function cleanFileContent($fileContent)
{
    $func = new functions();

    $fileContent = $func->normalize($fileContent);

    $fileContent = preg_replace('/[[:cntrl:]]/', ' ', $fileContent);
    

    $fileContent = trim(preg_replace('/[[:punct:]]/', ' ', $fileContent));
    
    

    $tmpArrFileContent = explode(' ', $fileContent);
    $arrFileContent = [];
    
    foreach ($tmpArrFileContent as $key => $value) {
        if (strlen($value) > 2) {
            array_push($arrFileContent, $value);
        }
    }

    $fileContent = implode(' ', $arrFileContent);

    return $fileContent;
}

function prepareIndexFullTextPdf($pathToFile, $indexFileDirectory, $Id)
{
    if (is_file($pathToFile)) {
        $tmpFile = $_ENV["base_directory"] . "tmp"
            . DIRECTORY_SEPARATOR . basename($pathToFile) . ".ftx";

        $_ENV['logger']->write("pdftotext " . escapeshellarg($pathToFile) . " " . escapeshellarg($tmpFile), 'DEBUG');
        $resultExtraction = exec("pdftotext " . escapeshellarg($pathToFile)
            . " " . escapeshellarg($tmpFile).' 2>&1', $output, $return);

        if (in_array("Syntax Error: Couldn't read xref table", $output)) {
            errorHandler("{$pathToFile} is corrupted !");
            $result = 2;
        } else if (!empty($output)) {
            errorHandler("Extract text of document failed : " . implode(' ', $output));
            $result = 2;
        } else {
            $fileContent = readFileF($tmpFile);
            $fileContent = cleanFileContent($fileContent);
    
    
            $_ENV['logger']->write("content file : " . $fileContent, 'DEBUG');
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }
    
            if (empty($fileContent)) {
                $_ENV['logger']->write("it is not an OCR pdf");
                $result = 2;
            } else {
                $result = launchIndexFullText($fileContent, $indexFileDirectory, $Id);
            }
        }
    } else {
        errorHandler("{$pathToFile} not found !");
        $result = -1;
    }
    return $result;
}

function prepareIndexFullTextHtml($pathToFile, $indexFileDirectory, $Id)
{
    if (is_file($pathToFile)) {
        $fileContent = trim(readFileF($pathToFile));

        $fileContent = convert_html_to_text($fileContent);

        $fileContent = cleanFileContent($fileContent);

        $_ENV['logger']->write("content file : " . $fileContent, 'DEBUG');
        $result = launchIndexFullText($fileContent, $indexFileDirectory, $Id);
    } else {
        $result = -1;
    }
    return $result;
}

function prepareIndexFullTextTxt($pathToFile, $indexFileDirectory, $Id)
{
    if (is_file($pathToFile)) {
        $fileContent = trim(readFileF($pathToFile));
        $fileContent = cleanFileContent($fileContent);
        $_ENV['logger']->write("content file : " . $fileContent, 'DEBUG');
        $result = launchIndexFullText($fileContent, $indexFileDirectory, $Id);
    } else {
        $result = -1;
    }
    return $result;
}

/**
 * Retrieve the text of a pdftext and launch the lucene engine
 * @param  $pathToFile string path of the file to index
 * @param  $indexFileDirectory string directory of the lucene index
 * @param  $id integer id of the document to index
 * @return integer user exit code is stored in fulltext_result column of the
 * document in "res_x"
 */
function launchIndexFullText($fileContent, $tempIndexFileDirectory, $Id) // $IndexFileDirectory is replace by tempIndexFileDirectory
{
    $indexFileDirectory = (string)$tempIndexFileDirectory; // with version 1.12, we need a string, not an XML element
    $result = -1;
    $luceneErr = false;
    if (strlen($fileContent) > 20) {
        if (!is_dir($indexFileDirectory)) {
            $_ENV['logger']->write($indexFileDirectory . " will be created !", "DEBUG");
            try {
                $index = @Zend_Search_Lucene::create($indexFileDirectory);
            } catch (Exception $e) {
                $luceneErr = true;
                errorHandler($e->getMessage());
            }
        } else {
            if (isDirEmpty($indexFileDirectory)) {
                $_ENV['logger']->write('lucene files in ' . $indexFileDirectory . "  will be created !", "DEBUG");
                try {
                    $index = @Zend_Search_Lucene::create($indexFileDirectory);
                } catch (Exception $e) {
                    $luceneErr = true;
                    errorHandler($e->getMessage());
                }
            } else {
                $_ENV['logger']->write('open lucene files in ' . $indexFileDirectory, "DEBUG");
                try {
                    $index = @Zend_Search_Lucene::open($indexFileDirectory);
                } catch (Exception $e) {
                    $luceneErr = true;
                    errorHandler($e->getMessage());
                }
            }
        }
        if ($luceneErr === false) {
            try {
                $index->setFormatVersion(Zend_Search_Lucene::FORMAT_2_3); // we set the lucene format to 2.3
                Zend_Search_Lucene_Analysis_Analyzer::setDefault(
                    new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive() // we need utf8 for accents
                );
                $term = new Zend_Search_Lucene_Index_Term($Id, 'Id');
                foreach ($index->termDocs($term) as $id) {
                    $index->delete($id);
                }
                $doc = new Zend_Search_Lucene_Document();
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed('Id', $Id));
                $doc->addField(Zend_Search_Lucene_Field::UnStored(
                    'contents',
                    $fileContent
                ));
                $index->addDocument($doc);
                $index->commit();
                $_ENV['logger']->write('link res_id to lucene indexes', "DEBUG");

                $result = 1;
            } catch (Exception $e) {
                errorHandler($e->getMessage());
            }
        }
    } else {
        $result = 2;
        $_ENV['logger']->write('Content has too few characters (<20)');
    }
    return $result;
}

/**
 * Read a txt file
 * @param  $file string path of the file to read
 * @return string contents of the file
 */
function readFileF($file)
{
    $result = "";
    if (is_file($file)) {
        $fp = fopen($file, "r");
        $result = fread($fp, filesize($file));
        fclose($fp);
    }
    return $result;
}

// Begin
date_default_timezone_set('Europe/Paris');

if ($argc < 2) {
    echo "You must specify the configuration file " . $argc . "\n\n";
    exit;
}

$conf = $argv[1];
$xmlconfig = @simplexml_load_file($conf);

if ($xmlconfig == false) {
    echo "\nError on loading config file: " . $conf . "\n\n";
    exit(103);
}

foreach ($xmlconfig->CONFIG as $CONFIG) {
    $_ENV['config_name'] = $CONFIG->CONFIG_NAME;
    $maarch_directory = $CONFIG->MAARCH_DIRECTORY;
    $_ENV['maarch_directory'] = $maarch_directory;
    $_ENV['base_directory'] = $_ENV['maarch_directory'] . '/modules/full_text/';
    $indexFileDirectory = $CONFIG->INDEX_FILE_DIRECTORY;
    $_ENV['tablename'] = $CONFIG->TABLE_NAME;
    $_ENV['max_batch_size'] = $CONFIG->MAX_BATCH_SIZE;
}

$_ENV['maarch_tools_path'] = $_ENV['maarch_directory'] . '/apps/maarch_entreprise/tools/';

if (!file_exists('log/'.$_ENV['tablename']. '/')) {
    mkdir('log/'.$_ENV['tablename']. '/');
}

$_ENV['logFile'] = 'log/'.$_ENV['tablename']. '/' .$_ENV['config_name'].'_'. date('Y-m-d_H-i-s');

$file = new FileHandler($_ENV['logFile'] . '.log');
$_ENV['logger']->add_handler($file);

if (array_search('--noLimit', $argv) > 0) {
    $limit = '';
} else if (array_search('--limit', $argv) > 0) {
    $cmdLimit = array_search('--limit', $argv);
    $limit = ' LIMIT ' . $argv[$cmdLimit+1];
} else {
    $limit = ' LIMIT ' . $_ENV['max_batch_size'];
}

$log4phpParams = $xmlconfig->LOG4PHP;
if ((string)$log4phpParams->enabled == 'true') {
    $_ENV['logger']->set_log4PhpLibrary(
        $_ENV['maarch_directory'] . 'apps/maarch_entreprise/tools/log4php/Logger.php'
    );
    $_ENV['logger']->set_log4PhpLogger((string)$log4phpParams->Log4PhpLogger);
    $_ENV['logger']->set_log4PhpBusinessCode((string)$log4phpParams->Log4PhpBusinessCode);
    $_ENV['logger']->set_log4PhpConfigPath((string)$log4phpParams->Log4PhpConfigPath);
    $_ENV['logger']->set_log4PhpBatchName('full_text');
}

$_ENV['logger']->write("Loading the xml config file : " . $conf, 'DEBUG');

$_ENV['logger']->write("Config name : " . $_ENV['config_name']);

$_ENV['logger']->write("Full text engine launched for table : " . $_ENV['tablename']);

if (array_search('--failed', $argv) > 0) {
    $_ENV['logger']->write("RE ANALYZE FAILED DOCUMENTS MODE");
    $fulltextTarget = "fulltext_result = '-1'";
} else {
    $fulltextTarget = "(fulltext_result IN ('0', '') or fulltext_result is null)";
}

require("../../core/class/class_functions.php");
require("../../core/class/class_db_pdo.php");

// Storing text in lucene index
set_include_path($_ENV['maarch_tools_path'] . DIRECTORY_SEPARATOR
    . PATH_SEPARATOR . get_include_path());
require_once('Zend/Search/Lucene.php');
include_once('html2text/html2text.php');

$_ENV['db'] = new Database($conf);

Bt_getWorkBatch();

$docServers = "SELECT docserver_id, path_template FROM docservers";

$stmt = $_ENV['db']->query($docServers);

$err = 0;
$errInfo = '';

while ($queryResult = $stmt->fetch(PDO::FETCH_NUM)) {
    $pathToDocServer[$queryResult[0]] = $queryResult[1];
}
if ($_ENV['tablename'] == 'res_attachments' || $_ENV['tablename'] == 'res_version_attachments') {
    $_ENV['logger']->write("docServers " . $_ENV['tablename'] . " found !", 'DEBUG');

    $queryIndexFullText = "SELECT res_id, docserver_id, path, filename, format FROM "
        . $_ENV['tablename'] . " WHERE ".$fulltextTarget
        . " AND lower(format) = 'pdf' AND attachment_type <> 'print_folder' AND status NOT IN ('DEL','OBS','TMP')"
        . " GROUP BY res_id ORDER BY res_id ASC" . $limit;
} else {
    $_ENV['logger']->write("docServers " . $_ENV['tablename'] . " found !", 'DEBUG');

    $queryIndexFullText = "SELECT res_id, docserver_id, path, filename, format FROM "
        . $_ENV['tablename'] . " WHERE ".$fulltextTarget
        . " AND (source <> 'with_empty_file' or source is null) AND status NOT IN ('DEL')"
        . " GROUP BY res_id ORDER BY res_id ASC" . $limit;
}

$_ENV['logger']->write($queryIndexFullText, 'DEBUG');

$stmt = $_ENV['db']->query($queryIndexFullText);
$queryResult = $stmt->fetchAll();

$nbResToFulltext = count($queryResult);

if ($nbResToFulltext === 0) {
    Bt_exitBatch(0, 'No document to process');
} else {
    $_ENV['logger']->write($nbResToFulltext . ' document(s) to proceed');
}

$converted_doc = 0;

foreach ($queryResult as $docNum => $data) {
    $pathToFile = $pathToDocServer[$data["docserver_id"]]
        . str_replace("#", DIRECTORY_SEPARATOR, $data["path"])
        . DIRECTORY_SEPARATOR . $data["filename"];

    if (!empty(pathinfo($pathToFile, PATHINFO_EXTENSION))) {
        $extension = pathinfo($pathToFile, PATHINFO_EXTENSION);
    } else {
        $extension = $data["format"];
    }
    

    $_ENV['logger']->write("processing of document " . ($docNum + 1) . "/" . $nbResToFulltext . " (RES_ID => " . $data["res_id"] . ", FORMAT => " . $extension . ", FILE => " . $pathToFile . ")");

    $result = indexFullText(
        $pathToFile,
        $indexFileDirectory,
        $extension,
        $data["res_id"]
    );

    $_ENV['logger']->write("result : " . $result);

    $updateDoc = "UPDATE " . $_ENV['tablename'] . " SET fulltext_result = ? WHERE res_id = ?";

    $_ENV['logger']->write("UPDATE " . $_ENV['tablename'] . " SET fulltext_result = '" . $result . "' WHERE res_id = '" . $data["res_id"] . "'", 'DEBUG');

    $_ENV['db']->query($updateDoc, array($result, $data["res_id"]));

    if ($result == 1) {
        $converted_doc++;
    }
}

$_ENV['logger']->write($converted_doc . ' document(s) with fulltext');

Bt_logInDataBase(
    $docNum+1,
    $err,
    $converted_doc . ' document(s) with fulltext' . $errInfo
);

Bt_updateWorkBatch();

$_ENV['logger']->write('Optimize Lucene index');
try {
    $index = @Zend_Search_Lucene::open((string)$indexFileDirectory);
    $index->optimize();
} catch (Exception $e) {
    $luceneErr = true;
    errorHandler($e->getMessage());
}


$_ENV['logger']->write("End of application !");

if ($_ENV['ErrorLevel'] > 0) {
    rename($_ENV['logFile'] . ".log", $_ENV['logFile'] . "_ERR.log");
}
exit($_ENV['ErrorLevel']);
