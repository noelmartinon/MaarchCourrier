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
$GLOBALS['MaarchDirectory']        = $_SESSION['config']['corepath'] = (string)$config->MaarchDirectory;
$_SESSION['config']['app_id']      = 'maarch_entreprise';
$GLOBALS['CustomId']               = $_SESSION['custom_override_id'] = (string)$config->CustomId;
$GLOBALS['applicationUrl']         = (string)$config->applicationUrl;
$GLOBALS['userWS']                 = (string)$config->userWS;
$GLOBALS['passwordWS']             = (string)$config->passwordWS;
$GLOBALS['batchDirectory']         = $GLOBALS['MaarchDirectory'] . 'modules' . DIRECTORY_SEPARATOR . 'visa' . DIRECTORY_SEPARATOR . 'batch';
$validatedStatus                   = (string)$config->validatedStatus;
$validatedStatusOnlyVisa           = (string)$config->validatedStatusOnlyVisa;
$refusedStatus                     = (string)$config->refusedStatus;
$validatedStatusAnnot              = (string)$config->validatedStatusAnnot;
$refusedStatusAnnot                = (string)$config->refusedStatusAnnot;

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
    $configRemoteNoteBook = ['id' => 'maarchParapheur'];
    if (file_exists($path)) {
        $loadedXml = simplexml_load_file($path);
        if ($loadedXml) {
            $configRemoteSignatoryBook['id'] = (string)$loadedXml->signatoryBookEnabled;
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == $configRemoteSignatoryBook['id']) {
                    $configRemoteSignatoryBook['data'] = (array)$value;
                }
                if ($value->id == $configRemoteNoteBook['id']) {
                    $configRemoteNoteBook['data'] = (array)$value;
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
    if (is_file($GLOBALS['MaarchDirectory'] . 'custom/' . $GLOBALS['CustomId'] . $signatoryBook)) {
        $classToInclude = $GLOBALS['MaarchDirectory'] . 'custom/' . $GLOBALS['CustomId'] . $signatoryBook;
        Bt_myInclude($classToInclude);
    } elseif (is_file($GLOBALS['MaarchDirectory'] . $signatoryBook)) {
        $classToInclude = $GLOBALS['MaarchDirectory'] . $signatoryBook;
        Bt_myInclude($classToInclude);
    } elseif (!in_array($configRemoteSignatoryBook['id'], ['maarchParapheur', 'xParaph'])) {
        $GLOBALS['logger']->write('No class detected', 'ERROR', 102);
        echo "\nNo class detected ! \nThe batch cannot be launched !\n\n";
        exit(102);
    }
} catch (IncludeFileError $e) {
    $GLOBALS['logger']->write(
        'Problem with the php include path:' .$e .' '. get_include_path(),
        'ERROR'
    );
    exit();
}

\SrcCore\models\DatabasePDO::reset();
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

$GLOBALS['logger']->write('Retrieve attachments sent to remote signatory book', 'INFO');
$query = "SELECT res_id, res_id_version, external_id->>'signatureBookId' as external_id, external_id->>'xparaphDepot' as xparaphdepot, format, res_id_master, title, identifier, type_id, attachment_type, dest_contact_id, dest_address_id, dest_user, typist, attachment_id_master, relation 
        FROM res_view_attachments WHERE status = 'FRZ' AND external_id->>'signatureBookId' IS NOT NULL AND external_id->>'signatureBookId' <> ''";
$stmt = $GLOBALS['db']->query($query, []);
    
$idsToRetrieve = ['noVersion' => [], 'isVersion' => [], 'noVersionOutgoing' => [], 'isVersionOutgoing' => [], 'resLetterbox' => []];

while ($reqResult = $stmt->fetchObject()) {
    if (!empty($reqResult->res_id) && $reqResult->attachment_type == 'outgoing_mail') {
        $idsToRetrieve['noVersionOutgoing'][$reqResult->res_id] = $reqResult;
    } elseif (!empty($reqResult->res_id_version) && $reqResult->attachment_type == 'outgoing_mail') {
        $idsToRetrieve['isVersionOutgoing'][$reqResult->res_id_version] = $reqResult;
    } elseif (!empty($reqResult->res_id)) {
        $idsToRetrieve['noVersion'][$reqResult->res_id] = $reqResult;
    } else {
        $idsToRetrieve['isVersion'][$reqResult->res_id_version] = $reqResult;
    }
}

// On récupère les pj signés dans le parapheur distant
$GLOBALS['logger']->write('Retrieve signed/annotated documents from remote signatory book', 'INFO');
if ($configRemoteSignatoryBook['id'] == 'ixbus') {
    $retrievedMails = IxbusController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve]);
} elseif ($configRemoteSignatoryBook['id'] == 'iParapheur') {
    $retrievedMails = IParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve]);
} elseif ($configRemoteSignatoryBook['id'] == 'fastParapheur') {
    $retrievedMails = FastParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve]);
} elseif ($configRemoteSignatoryBook['id'] == 'maarchParapheur') {
    $retrievedMails = \ExternalSignatoryBook\controllers\MaarchParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve]);
} elseif ($configRemoteSignatoryBook['id'] == 'xParaph') {
    $retrievedMails = \ExternalSignatoryBook\controllers\XParaphController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve]);
}

$GLOBALS['logger']->write('Retrieve mails sent to remote signatory book', 'INFO');
$query = "SELECT res_id, external_signatory_book_id as external_id, subject, typist 
        FROM res_letterbox WHERE external_signatory_book_id IS NOT NULL";
$stmt = $GLOBALS['db']->query($query, []);

while ($reqResult = $stmt->fetchObject()) {
    $idsToRetrieve['resLetterbox'][$reqResult->res_id] = $reqResult;
}
if (!empty($idsToRetrieve['resLetterbox'])) {
    $idsToRetrieve['noVersion'] = [];
    $idsToRetrieve['isVersion'] = [];
    $idsToRetrieve['noVersionOutgoing'] = [];
    $idsToRetrieve['isVersionOutgoing'] = [];
    $retrievedLetterboxMails = \ExternalSignatoryBook\controllers\MaarchParapheurController::retrieveSignedMails(['config' => $configRemoteNoteBook, 'idsToRetrieve' => $idsToRetrieve]);
    $retrievedMails['resLetterbox'] = $retrievedLetterboxMails['resLetterbox'];
}

if (!empty($retrievedMails['error'])) {
    $GLOBALS['logger']->write($retrievedMails['error'], 'ERROR');
    exit;
}

// Move outgoing attachment to visa workflow
$outgoingToVisa = ['noVersionOutgoing' => [], 'isVersionOutgoing' => []];
foreach (['noVersionOutgoing', 'isVersionOutgoing'] as $version) {
    foreach ($retrievedMails[$version] as $resId => $value) {
        if (in_array($value->status, ['validated', 'refused'])) {
            $moveToVersion = str_replace("Outgoing", "", $version);
            $retrievedMails[$moveToVersion][$resId] = $value;
            unset($retrievedMails[$version][$resId]);
        }
    }
}

// On dégele les pj et on créé une nouvelle ligne si le document a été signé
foreach ($retrievedMails['isVersion'] as $resId => $value) {
    $GLOBALS['logger']->write('Update res_version_attachments : ' . $resId . '. ExternalId : ' . $value->external_id, 'INFO');

    if (!empty($value->log)) {
        $GLOBALS['logger']->write('Create log Attachment', 'INFO');
        Bt_createAttachment([
            'res_id_master'     => $value->res_id_master,
            'title'             => '[xParaph Log] ' . $value->title,
            'identifier'        => $value->identifier,
            'type_id'           => $value->type_id,
            'dest_contact_id'   => $value->dest_contact_id,
            'dest_address_id'   => $value->dest_address_id,
            'dest_user'         => $value->dest_user,
            'typist'            => $value->typist,
            'format'            => 'xml',
            'attachment_type'   => $value->attachment_type,
            'relation'          => 1,
            'status'            => 'TRA',
            'encodedFile'       => $value->log,
            'in_signature_book' => 'false',
            'table'             => 'res_attachments'
        ]);
    }
    $additionalHistoryInfo = '';
    if (!empty($value->workflowInfo)) {
        $additionalHistoryInfo =  ' : ' . $value->workflowInfo;
    }

    if ($value->status == 'validated') {
        if (!empty($value->encodedFile)) {
            $GLOBALS['logger']->write('Create validated version Attachment', 'INFO');
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
                'attachment_type' => $value->attachment_type,
                'relation'        => $value->relation + 1,
                'attachment_id_master' => $value->attachment_id_master,
                'status'          => 'TRA',
                'encodedFile'     => $value->encodedFile,
                'table'           => 'res_version_attachments',
                'noteContent'     => $value->noteContent,
                'noteCreatorId'   => $value->noteCreatorId,
                'noteCreatorName' => $value->noteCreatorName
            ]);
        }
    
        $GLOBALS['logger']->write('Document validated', 'INFO');
        $GLOBALS['db']->query("UPDATE res_version_attachments set status = 'OBS', external_id = external_id - 'signatureBookId' WHERE res_id = ?", [$resId]);
        if (!empty($value->onlyVisa) && $value->onlyVisa) {
            $status = $validatedStatusOnlyVisa;
        } else {
            $status = $validatedStatus;
        }

        Bt_validatedMail(['status' => $status, 'resId' => $value->res_id_master]);
        $historyInfo = 'La signature de la pièce jointe '.$resId.' (res_version_attachments) a été validée dans le parapheur externe' . $additionalHistoryInfo;
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
        if (!empty($value->encodedFile)) {
            $GLOBALS['logger']->write('Create refused version Attachment', 'INFO');
            Bt_createAttachment([
                'res_id_master'   => $value->res_id_master,
                'title'           => '[REFUSE] ' . $value->title,
                'identifier'      => $value->identifier,
                'type_id'         => $value->type_id,
                'dest_contact_id' => $value->dest_contact_id,
                'dest_address_id' => $value->dest_address_id,
                'dest_user'       => $value->dest_user,
                'typist'          => $value->typist,
                'format'          => $value->format,
                'attachment_type' => $value->attachment_type,
                'status'          => 'A_TRA',
                'encodedFile'     => $value->encodedFile,
                'in_signature_book' => 'false',
                'table'           => 'res_attachments',
                'noteContent'     => $value->noteContent,
                'noteCreatorId'   => $value->noteCreatorId,
                'noteCreatorName' => $value->noteCreatorName
            ]);
            $value->noteContent = '';
        }
        $GLOBALS['logger']->write('Document refused', 'INFO');
        Bt_refusedSignedMail([
            'tableAttachment' => 'res_version_attachments',
            'resIdAttachment' => $resId,
            'refusedStatus'   => $refusedStatus,
            'resIdMaster'     => $value->res_id_master,
            'noteContent'     => $value->noteContent,
            'noteCreatorId'   => $value->noteCreatorId,
            'noteCreatorName' => $value->noteCreatorName,
            'additionalHistoryInfo' => $additionalHistoryInfo
        ]);
    }
}

foreach ($retrievedMails['noVersion'] as $resId => $value) {
    $GLOBALS['logger']->write('Update res_attachments : ' . $resId . '. ExternalId : ' . $value->external_id, 'INFO');

    if (!empty($value->log)) {
        $GLOBALS['logger']->write('Create log Attachment', 'INFO');
        Bt_createAttachment([
            'res_id_master'     => $value->res_id_master,
            'title'             => '[xParaph Log] ' . $value->title,
            'identifier'        => $value->identifier,
            'type_id'           => $value->type_id,
            'dest_contact_id'   => $value->dest_contact_id,
            'dest_address_id'   => $value->dest_address_id,
            'dest_user'         => $value->dest_user,
            'typist'            => $value->typist,
            'format'            => 'xml',
            'attachment_type'   => $value->attachment_type,
            'relation'          => 1,
            'status'            => 'TRA',
            'encodedFile'       => $value->log,
            'in_signature_book' => 'false',
            'table'             => 'res_attachments'
        ]);
    }
    $additionalHistoryInfo = '';
    if (!empty($value->workflowInfo)) {
        $additionalHistoryInfo =  ' : ' . $value->workflowInfo;
    }

    if ($value->status == 'validated') {
        if (!empty($value->encodedFile)) {
            $GLOBALS['logger']->write('Create validated Attachment', 'INFO');
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
                'attachment_type' => $value->attachment_type,
                'relation'        => $value->relation + 1,
                'attachment_id_master' => $resId,
                'status'          => 'TRA',
                'encodedFile'     => $value->encodedFile,
                'table'           => 'res_version_attachments',
                'noteContent'     => $value->noteContent,
                'noteCreatorId'   => $value->noteCreatorId,
                'noteCreatorName' => $value->noteCreatorName
            ]);
        }

        $GLOBALS['logger']->write('Document validated', 'INFO');
        $GLOBALS['db']->query("UPDATE res_attachments SET status = 'OBS', external_id = external_id - 'signatureBookId' WHERE res_id = ?", [$resId]);
        if (!empty($value->onlyVisa) && $value->onlyVisa) {
            $status = $validatedStatusOnlyVisa;
        } else {
            $status = $validatedStatus;
        }
        Bt_validatedMail(['status' => $status, 'resId' => $value->res_id_master]);

        $historyInfo = 'La signature de la pièce jointe '.$resId.' (res_attachments) a été validée dans le parapheur externe' . $additionalHistoryInfo;
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
        if (!empty($value->encodedFile)) {
            $GLOBALS['logger']->write('Create refused Attachment', 'INFO');
            Bt_createAttachment([
                'res_id_master'   => $value->res_id_master,
                'title'           => '[REFUSE] ' . $value->title,
                'identifier'      => $value->identifier,
                'type_id'         => $value->type_id,
                'dest_contact_id' => $value->dest_contact_id,
                'dest_address_id' => $value->dest_address_id,
                'dest_user'       => $value->dest_user,
                'typist'          => $value->typist,
                'format'          => $value->format,
                'attachment_type' => $value->attachment_type,
                'status'          => 'A_TRA',
                'encodedFile'     => $value->encodedFile,
                'in_signature_book' => 'false',
                'table'           => 'res_attachments',
                'noteContent'     => $value->noteContent,
                'noteCreatorId'   => $value->noteCreatorId,
                'noteCreatorName' => $value->noteCreatorName
            ]);
            $value->noteContent = '';
        }
        $GLOBALS['logger']->write('Document refused', 'INFO');
        Bt_refusedSignedMail([
            'tableAttachment' => 'res_attachments',
            'resIdAttachment' => $resId,
            'refusedStatus'   => $refusedStatus,
            'resIdMaster'     => $value->res_id_master,
            'noteContent'     => $value->noteContent,
            'noteCreatorId'   => $value->noteCreatorId,
            'noteCreatorName' => $value->noteCreatorName,
            'additionalHistoryInfo' => $additionalHistoryInfo
        ]);
    }
}

foreach ($retrievedMails['resLetterbox'] as $resId => $value) {
    $GLOBALS['logger']->write('Update res_letterbox : ' . $resId . '. ExternalSignatoryBookId : ' . $value->external_id, 'INFO');

    if (!empty($value->encodedFile)) {
        $GLOBALS['logger']->write('Create Attachment', 'INFO');
        Bt_createAttachment([
            'res_id_master'     => $value->res_id,
            'title'             => $value->subject,
            'typist'            => $value->typist,
            'format'            => $value->format,
            'encodedFile'       => $value->encodedFile,
            'noteContent'       => $value->noteContent,
            'noteCreatorId'     => $value->noteCreatorId,
            'noteCreatorName'   => $value->noteCreatorName,
            'in_signature_book' => 'false',
            'attachment_type'   => 'document_with_notes'
        ]);
    }
    $additionalHistoryInfo = '';
    if (!empty($value->workflowInfo)) {
        $additionalHistoryInfo =  ' : ' . $value->workflowInfo;
    }
    if ($value->status == 'validatedNote') {
        $GLOBALS['logger']->write('Document validated', 'INFO');
        Bt_validatedMail(['status' => $validatedStatusAnnot, 'resId' => $value->res_id]);

        Bt_history([
            'table_name' => 'res_letterbox',
            'record_id'  => $value->res_id,
            'info'       => 'Le document '.$resId.' (res_letterbox) a été validé dans le parapheur externe' . $additionalHistoryInfo,
            'event_type' => 'ACTION#1',
            'event_id'   => '1'
        ]);
    } elseif ($value->status == 'refusedNote') {
        $GLOBALS['logger']->write('Document refused', 'INFO');
        $GLOBALS['db']->query("UPDATE res_letterbox SET status = '" . $refusedStatusAnnot . "' WHERE res_id = ?", [$resId]);
    
        Bt_history([
            'table_name' => 'res_letterbox',
            'record_id'  => $resId,
            'info'       => 'Le document '.$resId.' (res_letterbox) a été refusé dans le parapheur externe' . $additionalHistoryInfo,
            'event_type' => 'ACTION#1',
            'event_id'   => '1'
        ]);
    }
    $GLOBALS['db']->query("UPDATE res_letterbox SET external_signatory_book_id = null WHERE res_id = ?", [$resId]);
}

foreach ($retrievedMails['noVersionOutgoing'] as $resId => $value) {
    $GLOBALS['logger']->write('Update res_attachment outgoing : ' . $resId . '. ExternalSignatoryBookId : ' . $value->external_id, 'INFO');

    if (!empty($value->encodedFile)) {
        $GLOBALS['logger']->write('Create Attachment', 'INFO');
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
            'attachment_type' => 'outgoing_mail',
            'status'          => 'A_TRA',
            'encodedFile'     => $value->encodedFile,
            'in_signature_book' => 'false',
            'table'           => 'res_attachments',
            'noteContent'     => $value->noteContent,
            'noteCreatorId'   => $value->noteCreatorId,
            'noteCreatorName' => $value->noteCreatorName
        ]);
    }
    $GLOBALS['db']->query("UPDATE res_attachments SET status = 'TRA', external_id = external_id - 'signatureBookId' WHERE res_id = ?", [$resId]);
    $additionalHistoryInfo = '';
    if (!empty($value->workflowInfo)) {
        $additionalHistoryInfo =  ' : ' . $value->workflowInfo;
    }
    $attachmentResIdMaster = \Attachment\models\AttachmentModel::getById(['select' => ['res_id_master'], 'id' => $resId, 'isVersion' => false]);
    if ($value->status == 'validatedNote') {
        $GLOBALS['logger']->write('Document validated', 'INFO');
        Bt_validatedMail(['status' => $validatedStatusAnnot, 'resId' => $attachmentResIdMaster['res_id_master']]);

        Bt_history([
            'table_name' => 'res_letterbox',
            'record_id'  => $attachmentResIdMaster['res_id_master'],
            'info'       => 'Le document '.$attachmentResIdMaster['res_id_master'].' (res_letterbox outgoing) a été validé dans l\'annotateur externe' . $additionalHistoryInfo,
            'event_type' => 'ACTION#1',
            'event_id'   => '1'
        ]);
    } elseif ($value->status == 'refusedNote') {
        $GLOBALS['logger']->write('Document refused', 'INFO');
        $GLOBALS['db']->query("UPDATE res_letterbox SET status = '" . $refusedStatusAnnot . "' WHERE res_id = ?", [$attachmentResIdMaster['res_id_master']]);
    
        Bt_history([
            'table_name' => 'res_letterbox',
            'record_id'  => $attachmentResIdMaster['res_id_master'],
            'info'       => 'Le document '.$attachmentResIdMaster['res_id_master'].' (res_letterbox outgoing) a été refusé dans l\'annotateur externe' . $additionalHistoryInfo,
            'event_type' => 'ACTION#1',
            'event_id'   => '1'
        ]);
    }
    $GLOBALS['db']->query("UPDATE res_letterbox SET external_signatory_book_id = null WHERE res_id = ?", [$attachmentResIdMaster['res_id_master']]);
}

foreach ($retrievedMails['isVersionOutgoing'] as $resId => $value) {
    $GLOBALS['logger']->write('Update res_version_attachment outgoing : ' . $resId . '. ExternalSignatoryBookId : ' . $value->external_id, 'INFO');

    if (!empty($value->encodedFile)) {
        $GLOBALS['logger']->write('Create Attachment', 'INFO');
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
            'attachment_type' => 'outgoing_mail',
            'status'          => 'A_TRA',
            'encodedFile'     => $value->encodedFile,
            'in_signature_book' => 'false',
            'table'           => 'res_attachments',
            'noteContent'     => $value->noteContent,
            'noteCreatorId'   => $value->noteCreatorId,
            'noteCreatorName' => $value->noteCreatorName
        ]);
    }
    $GLOBALS['db']->query("UPDATE res_version_attachments SET status = 'TRA', external_id = external_id - 'signatureBookId' WHERE res_id = ?", [$resId]);
    $additionalHistoryInfo = '';
    if (!empty($value->workflowInfo)) {
        $additionalHistoryInfo =  ' : ' . $value->workflowInfo;
    }
    $attachmentResIdMaster = \Attachment\models\AttachmentModel::getById(['select' => ['res_id_master'], 'id' => $resId, 'isVersion' => true]);
    if ($value->status == 'validatedNote') {
        $GLOBALS['logger']->write('Document validated', 'INFO');
        Bt_validatedMail(['status' => $validatedStatusAnnot, 'resId' => $attachmentResIdMaster['res_id_master']]);

        Bt_history([
            'table_name' => 'res_letterbox',
            'record_id'  => $attachmentResIdMaster['res_id_master'],
            'info'       => 'Le document '.$attachmentResIdMaster['res_id_master'].' (res_letterbox outgoing) a été validé dans l\'annotateur externe' . $additionalHistoryInfo,
            'event_type' => 'ACTION#1',
            'event_id'   => '1'
        ]);
    } elseif ($value->status == 'refusedNote') {
        $GLOBALS['logger']->write('Document refused', 'INFO');
        $GLOBALS['db']->query("UPDATE res_letterbox SET status = '" . $refusedStatusAnnot . "' WHERE res_id = ?", [$attachmentResIdMaster['res_id_master']]);
    
        Bt_history([
            'table_name' => 'res_letterbox',
            'record_id'  => $attachmentResIdMaster['res_id_master'],
            'info'       => 'Le document '.$attachmentResIdMaster['res_id_master'].' (res_letterbox outgoing) a été refusé dans l\'annotateur externe' . $additionalHistoryInfo,
            'event_type' => 'ACTION#1',
            'event_id'   => '1'
        ]);
    }
    $GLOBALS['db']->query("UPDATE res_letterbox SET external_signatory_book_id = null WHERE res_id = ?", [$attachmentResIdMaster['res_id_master']]);
}

$GLOBALS['logger']->write('End of process', 'INFO');
$nbMailsRetrieved = count($retrievedMails['noVersion']) + count($retrievedMails['isVersion']) + count($retrievedMails['resLetterbox']) + count($retrievedMails['noVersionOutgoing']) + count($retrievedMails['isVersionOutgoing']);
$GLOBALS['logger']->write($nbMailsRetrieved.' document(s) retrieved', 'INFO');

Bt_logInDataBase(
    $nbMailsRetrieved,
    $err,
    $nbMailsRetrieved.' mail(s) retrieved'
);
Bt_updateWorkBatch();

exit($GLOBALS['exitCode']);
