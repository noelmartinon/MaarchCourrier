<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
 * @brief Retrieve signed mail from external signatory book
 * @author dev@maarch.org
 */

/**
* @brief  Class to include the file error
*
*/
class IncludeFileError extends Exception
{
    public function __construct($file)
    {
        $this->file = $file;
        parent :: __construct('Include File \'$file\' is missing!', 1);
    }
}

try {
    include('Maarch_CLITools/ArgsParser.php');
    include('LoggerLog4php.php');
    include('Maarch_CLITools/FileHandler.php');
    include('Maarch_CLITools/ConsoleHandler.php');
} catch (IncludeFileError $e) {
    echo 'Maarch_CLITools required ! \n (pear.maarch.org)\n';
    exit(106);
}

// Globals variables definition
$GLOBALS['batchName']    = 'retrieveMailsFromSignatoryBook';
$GLOBALS['wb']           = '';
$totalProcessedResources = 0;
$batchDirectory          = '';
$log4PhpEnabled          = false;

// Open Logger
$GLOBALS['logger'] = new Logger4Php();
$GLOBALS['logger']->set_threshold_level('INFO');

$logFile = 'logs' . DIRECTORY_SEPARATOR . date('Y-m-d_H-i-s') . '.log';

$file = new FileHandler($logFile);
$GLOBALS['logger']->add_handler($file);

// Load tools
include('batch_tools.php');

// Defines scripts arguments
$argsparser = new ArgsParser();
// The config file
$argsparser->add_arg(
    'config',
    array(
        'short' => 'c',
        'long' => 'config',
        'mandatory' => true,
        'help' => 'Config file path is mandatory.',
    )
);

// Parsing script options
try {
    $options = $argsparser->parse_args($GLOBALS['argv']);
    // If option = help then options = false and the script continues ...
    if ($options == false) {
        exit(0);
    }
} catch (MissingArgumentError $e) {
    if ($e->arg_name == 'config') {
        $GLOBALS['logger']->write('Configuration file missing', 'ERROR', 101);
        exit(101);
    }
}

$txt = '';
foreach (array_keys($options) as $key) {
    if (isset($options[$key]) && $options[$key] == false) {
        $txt .= $key . '=false,';
    } else {
        $txt .= $key . '=' . $options[$key] . ',';
    }
}
$GLOBALS['logger']->write($txt, 'DEBUG');
$GLOBALS['configFile'] = $options['config'];
// Loading config file
$GLOBALS['logger']->write(
    'Load xml config file:' . $GLOBALS['configFile'],
    'INFO'
);
// Tests existence of config file
if (!file_exists($GLOBALS['configFile'])) {
    $GLOBALS['logger']->write(
        'Configuration file ' . $GLOBALS['configFile']
        . ' does not exist',
        'ERROR',
        102
    );
    echo "\nConfiguration file " . $GLOBALS['configFile'] . " does not exist ! \nThe batch cannot be launched !\n\n";
    exit(102);
}

$xmlconfig = simplexml_load_file($GLOBALS['configFile']);

if ($xmlconfig == false) {
    $GLOBALS['logger']->write(
        'Error on loading config file:'
        . $GLOBALS['configFile'],
        'ERROR',
        103
    );
    exit(103);
}

// Load config
$config = $xmlconfig->CONFIG;
$GLOBALS['MaarchDirectory']   = $_SESSION['config']['corepath'] = (string)$config->MaarchDirectory;
$_SESSION['config']['app_id'] = 'maarch_entreprise';
$GLOBALS['CustomId']          = $_SESSION['custom_override_id'] = (string)$config->CustomId;
$GLOBALS['applicationUrl']    = (string)$config->applicationUrl;
$GLOBALS['userWS']            = (string)$config->userWS;
$GLOBALS['passwordWS']        = (string)$config->passwordWS;
$GLOBALS['batchDirectory']    = $GLOBALS['MaarchDirectory'] . 'modules' . DIRECTORY_SEPARATOR . 'visa' . DIRECTORY_SEPARATOR . 'batch';
$validatedStatus              = (string)$config->validatedStatus;
$refusedStatus                = (string)$config->refusedStatus;

set_include_path(get_include_path() . PATH_SEPARATOR . $GLOBALS['MaarchDirectory']);

//log4php params
$log4phpParams = $xmlconfig->LOG4PHP;
if ((string) $log4phpParams->enabled == 'true') {
    $GLOBALS['logger']->set_log4PhpLibrary(
        $GLOBALS['MaarchDirectory'] . 'apps/maarch_entreprise/tools/log4php/Logger.php'
    );
    $GLOBALS['logger']->set_log4PhpLogger((string) $log4phpParams->Log4PhpLogger);
    $GLOBALS['logger']->set_log4PhpBusinessCode((string) $log4phpParams->Log4PhpBusinessCode);
    $GLOBALS['logger']->set_log4PhpConfigPath((string) $log4phpParams->Log4PhpConfigPath);
    $GLOBALS['logger']->set_log4PhpBatchName($GLOBALS['batchName']);
} else {
    echo "\n/!\ WARNING /!\ LOG4PHP is disabled ! Informations of batch process will not show !\n\n";
}

try {
    Bt_myInclude($GLOBALS['MaarchDirectory'] . 'vendor/autoload.php');

    // On regarde la configuration du parapheur
    if (file_exists($GLOBALS['MaarchDirectory'] . "custom/".$GLOBALS['CustomId']."/modules/visa/xml/remoteSignatoryBooks.xml")) {
        $path = $GLOBALS['MaarchDirectory'] . "custom/".$GLOBALS['CustomId']."/modules/visa/xml/remoteSignatoryBooks.xml";
    } else {
        $path = $GLOBALS['MaarchDirectory'] . 'modules/visa/xml/remoteSignatoryBooks.xml';
    }

    $configRemoteSignatoryBook = [];
    if (file_exists($path)) {
        $loadedXml = simplexml_load_file($path);
        if ($loadedXml) {
            $configRemoteSignatoryBook['id'] = (string)$loadedXml->signatoryBookEnabled;
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == $configRemoteSignatoryBook['id']) {
                    $configRemoteSignatoryBook['data'] = (array)$value;
                }
            }
        }
    } else {
        $GLOBALS['logger']->write($path . ' does not exist', 'ERROR', 102);
        echo "\nConfiguration file ".$path." does not exist ! \nThe batch cannot be launched !\n\n";
        exit(102);
    }

    if (!empty($configRemoteSignatoryBook)) {
        if ($configRemoteSignatoryBook['id'] == 'ixbus') {
            $signatoryBook = "/modules/visa/class/IxbusController.php";
        } elseif ($configRemoteSignatoryBook['id'] == 'iParapheur') {
            $signatoryBook = "/modules/visa/class/IParapheurController.php";
        } elseif ($configRemoteSignatoryBook['id'] == 'fastParapheur') {
            $signatoryBook = "/modules/visa/class/FastParapheurController.php";
        }
    } else {
        $GLOBALS['logger']->write('no signatory book enabled', 'ERROR', 102);
        echo "\nNo signatory book enabled ! \nThe batch cannot be launched !\n\n";
        exit(102);
    }

    // On inclut la classe du parapheur activé
    if (file_exists($GLOBALS['MaarchDirectory'] . 'custom/' . $GLOBALS['CustomId'] . $signatoryBook)) {
        $classToInclude = $GLOBALS['MaarchDirectory'] . 'custom/' . $GLOBALS['CustomId'] . $signatoryBook;
    } elseif (file_exists($GLOBALS['MaarchDirectory'] . $signatoryBook)) {
        $classToInclude = $GLOBALS['MaarchDirectory'] . $signatoryBook;
    } else {
        $GLOBALS['logger']->write('No class detected', 'ERROR', 102);
        echo "\nNo class detected ! \nThe batch cannot be launched !\n\n";
        exit(102);
    }
    Bt_myInclude($classToInclude);
} catch (IncludeFileError $e) {
    $GLOBALS['logger']->write(
        'Problem with the php include path:' .$e .' '. get_include_path(),
        'ERROR'
    );
    exit();
}

if (!empty($GLOBALS['db'])) {
    $GLOBALS['db']->reset();
}

$GLOBALS['db'] = new \SrcCore\models\DatabasePDO(['customId' => $GLOBALS['CustomId']]);

$GLOBALS['errorLckFile'] = $GLOBALS['batchDirectory'] . DIRECTORY_SEPARATOR . $GLOBALS['batchName'] .'_error.lck';
$GLOBALS['lckFile'] = $GLOBALS['batchDirectory'] . DIRECTORY_SEPARATOR . $GLOBALS['batchName'] . '.lck';

if (file_exists($GLOBALS['errorLckFile'])) {
    $GLOBALS['logger']->write(
        'Error persists, please solve this before launching a new batch',
        'ERROR',
        13
    );
    exit(13);
}

Bt_getWorkBatch();

$GLOBALS['logger']->write('Retrieve mails sent to remote signatory book', 'INFO');
$query = "SELECT res_id, res_id_version, external_id, format, res_id_master, title, identifier, type_id, attachment_type, dest_contact_id, dest_address_id, dest_user, typist 
        FROM res_view_attachments WHERE status = 'FRZ' AND external_id IS NOT NULL AND external_id <> ''";
$stmt = $GLOBALS['db']->query($query, []);
    
$idsToRetrieve = ['noVersion' => [], 'isVersion' => []];

while ($reqResult = $stmt->fetchObject()) {
    if (!empty($reqResult->res_id)) {
        $idsToRetrieve['noVersion'][$reqResult->res_id] = $reqResult;
    } else {
        $idsToRetrieve['isVersion'][$reqResult->res_id_version] = $reqResult;
    }
}

// On récupère les pj signés dans le parapheur distant
$GLOBALS['logger']->write('Retrieve signed mails from remote signatory book', 'INFO');
if ($configRemoteSignatoryBook['id'] == 'ixbus') {
    $retrievedMails = IxbusController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve]);
} elseif ($configRemoteSignatoryBook['id'] == 'iParapheur') {
    $retrievedMails = IParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve]);
} elseif ($configRemoteSignatoryBook['id'] == 'fastParapheur') {
    $retrievedMails = FastParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve]);
}

// On dégele les pj et on créé une nouvelle ligne si le document a été signé
foreach ($retrievedMails['isVersion'] as $resId => $value) {
    $GLOBALS['logger']->write('Update version attachment', 'INFO');
    if ($value->status == 'validated') {
        Bt_createAttachment([
            'res_id_master'   => $value->res_id_master,
            'title'           => $value->title,
            'identifier'      => $value->identifier,
            'type_id'         => $value->type_id,
            'dest_contact_id' => $value->dest_contact_id,
            'dest_address_id' => $value->dest_address_id,
            'dest_user'       => $value->dest_user,
            'typist'          => $value->typist,
            'noteContent'     => $value->noteContent
        ]);

        $GLOBALS['db']->query("UPDATE res_version_attachments set status = 'TRA' WHERE res_id = ?", [$resId]);
        Bt_processVisaWorkflow(['res_id_master' => $value->res_id_master, 'validatedStatus' => $validatedStatus]);

        $historyInfo = 'La signature de la pièce jointe '.$resId.' (res_version_attachments) a été validée dans le parapheur externe';
        Bt_history([
            'table_name' => 'res_version_attachments',
            'record_id'  => $resId,
            'info'       => $historyInfo,
            'event_type' => 'UP',
            'event_id'   => 'attachup'
        ]);
        Bt_history([
            'table_name' => 'res_letterbox',
            'record_id'  => $value->res_id_master,
            'info'       => $historyInfo,
            'event_type' => 'ACTION#1',
            'event_id'   => '1'
        ]);
    } elseif ($value->status == 'refused') {
        Bt_refusedSignedMail([
            'tableAttachment' => 'res_version_attachments',
            'resIdAttachment' => $resId,
            'refusedStatus'   => $refusedStatus,
            'resIdMaster'     => $value->res_id_master,
            'noteContent'     => $value->noteContent
        ]);
    }
}

foreach ($retrievedMails['noVersion'] as $resId => $value) {
    $GLOBALS['logger']->write('Update attachment', 'INFO');
    if ($value->status == 'validated') {
        Bt_createAttachment([
            'res_id_master'   => $value->res_id_master,
            'title'           => $value->title,
            'identifier'      => $value->identifier,
            'type_id'         => $value->type_id,
            'dest_contact_id' => $value->dest_contact_id,
            'dest_address_id' => $value->dest_address_id,
            'dest_user'       => $value->dest_user,
            'typist'          => $value->typist,
            'format'          => $value->format,
            'encodedFile'     => $value->encodedFile,
            'noteContent'     => $value->noteContent
        ]);

        $GLOBALS['db']->query("UPDATE res_attachments SET status = 'TRA' WHERE res_id = ?", [$resId]);
        Bt_processVisaWorkflow(['res_id_master' => $value->res_id_master, 'validatedStatus' => $validatedStatus]);

        $historyInfo = 'La signature de la pièce jointe '.$resId.' (res_attachments) a été validée dans le parapheur externe';
        Bt_history([
            'table_name' => 'res_attachments',
            'record_id'  => $resId,
            'info'       => $historyInfo,
            'event_type' => 'UP',
            'event_id'   => 'attachup'
        ]);
        Bt_history([
            'table_name' => 'res_letterbox',
            'record_id'  => $value->res_id_master,
            'info'       => $historyInfo,
            'event_type' => 'ACTION#1',
            'event_id'   => '1'
        ]);
    } elseif ($value->status == 'refused') {
        Bt_refusedSignedMail([
            'tableAttachment' => 'res_attachments',
            'resIdAttachment' => $resId,
            'refusedStatus'   => $refusedStatus,
            'resIdMaster'     => $value->res_id_master,
            'noteContent'     => $value->noteContent
        ]);
    }
}

$GLOBALS['logger']->write('end of process', 'INFO');
$nbMailsRetrieved = count($retrievedMails['noVersion']) + count($retrievedMails['isVersion']);
$GLOBALS['logger']->write($nbMailsRetrieved.' mail(s) retrieved', 'INFO');

Bt_logInDataBase(
    $nbMailsRetrieved,
    $err,
    $nbMailsRetrieved.' mail(s) retrieved'
);
Bt_updateWorkBatch();

exit($GLOBALS['exitCode']);
