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

    if (file_exists($path)) {
        $loadedXml = simplexml_load_file($path);
        if ($loadedXml) {
            $configRemoteSignatoryBook       = [];
            $configRemoteNoteBook            = ['id' => 'maarchParapheur'];
            $configRemoteSignatoryBook['id'] = (string)$loadedXml->signatoryBookEnabled;
            foreach ($loadedXml->signatoryBook as $value) {
                if ($value->id == $configRemoteSignatoryBook['id']) {
                    $configRemoteSignatoryBook['data'] = (array)$value;
                }
                if ($value->id == $configRemoteNoteBook['id']) {
                    $configRemoteNoteBook['data'] = (array)$value;
                }
            }
        } else {
            $GLOBALS['logger']->write($path . ' can not be loaded', 'ERROR', 102);
            echo "\nConfiguration file ".$path." can not be loaded ! \nThe batch cannot be launched !\n\n";
            exit(102);
        }
    } else {
        $GLOBALS['logger']->write($path . ' does not exist', 'ERROR', 102);
        echo "\nConfiguration file ".$path." does not exist ! \nThe batch cannot be launched !\n\n";
        exit(102);
    }

    if (empty($configRemoteSignatoryBook)) {
        $GLOBALS['logger']->write('no signatory book enabled', 'ERROR', 102);
        echo "\nNo signatory book enabled ! \nThe batch cannot be launched !\n\n";
        exit(102);
    }

    // On inclut la classe du parapheur activé
    if (!in_array($configRemoteSignatoryBook['id'], ['maarchParapheur', 'xParaph', 'fastParapheur', 'iParapheur', 'ixbus'])) {
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
$GLOBALS['lckFile']      = $GLOBALS['batchDirectory'] . DIRECTORY_SEPARATOR . $GLOBALS['batchName'] . '.lck';

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
$query = "SELECT res_id, external_id->>'signatureBookId' as external_id, external_id->>'xparaphDepot' as xparaphdepot, format, res_id_master, title, identifier, attachment_type, recipient_id, recipient_type, typist, origin_id, relation 
        FROM res_attachments WHERE status = 'FRZ' AND external_id->>'signatureBookId' IS NOT NULL AND external_id->>'signatureBookId' <> ''";
$stmt = $GLOBALS['db']->query($query, []);
    
$idsToRetrieve = ['noVersion' => [], 'resLetterbox' => []];

while ($reqResult = $stmt->fetchObject()) {
    $idsToRetrieve['noVersion'][$reqResult->res_id] = $reqResult;
}

// On récupère les pj signés dans le parapheur distant
$GLOBALS['logger']->write('Retrieve signed/annotated documents from remote signatory book', 'INFO');
if ($configRemoteSignatoryBook['id'] == 'ixbus') {
    $retrievedMails = \ExternalSignatoryBook\controllers\IxbusController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve, 'version' => 'noVersion']);
} elseif ($configRemoteSignatoryBook['id'] == 'iParapheur') {
    $retrievedMails = \ExternalSignatoryBook\controllers\IParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve, 'version' => 'noVersion']);
} elseif ($configRemoteSignatoryBook['id'] == 'fastParapheur') {
    $retrievedMails = \ExternalSignatoryBook\controllers\FastParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve, 'version' => 'noVersion']);
} elseif ($configRemoteSignatoryBook['id'] == 'maarchParapheur') {
    $retrievedMails = \ExternalSignatoryBook\controllers\MaarchParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve, 'version' => 'noVersion']);
} elseif ($configRemoteSignatoryBook['id'] == 'xParaph') {
    $retrievedMails = \ExternalSignatoryBook\controllers\XParaphController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve, 'version' => 'noVersion']);
}

$GLOBALS['logger']->write('Retrieve mails sent to remote signatory book', 'INFO');
$query = "SELECT res_id, external_id->>'signatureBookId' as external_id, subject, typist, version 
        FROM res_letterbox WHERE external_id->>'signatureBookId' IS NOT NULL AND external_id->>'signatureBookId' <> ''";
$stmt = $GLOBALS['db']->query($query, []);

while ($reqResult = $stmt->fetchObject()) {
    $idsToRetrieve['resLetterbox'][$reqResult->res_id] = $reqResult;
}
if (!empty($idsToRetrieve['resLetterbox'])) {
    if ($configRemoteSignatoryBook['id'] == 'maarchParapheur') {
        $retrievedLetterboxMails = \ExternalSignatoryBook\controllers\MaarchParapheurController::retrieveSignedMails(['config' => $configRemoteNoteBook, 'idsToRetrieve' => $idsToRetrieve, 'version' => 'resLetterbox']);
    } elseif ($configRemoteSignatoryBook['id'] == 'fastParapheur') {
        $retrievedLetterboxMails = \ExternalSignatoryBook\controllers\FastParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve, 'version' => 'resLetterbox']);
    } elseif ($configRemoteSignatoryBook['id'] == 'iParapheur') {
        $retrievedLetterboxMails = \ExternalSignatoryBook\controllers\IParapheurController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve, 'version' => 'resLetterbox']);
    } elseif ($configRemoteSignatoryBook['id'] == 'ixbus') {
        $retrievedLetterboxMails = \ExternalSignatoryBook\controllers\IxbusController::retrieveSignedMails(['config' => $configRemoteSignatoryBook, 'idsToRetrieve' => $idsToRetrieve, 'version' => 'resLetterbox']);
    }
    $retrievedMails['resLetterbox'] = $retrievedLetterboxMails['resLetterbox'];
}

if (!empty($retrievedMails['error'])) {
    $GLOBALS['logger']->write($retrievedMails['error'], 'ERROR');
    exit;
}

// On dégele les pj et on créé une nouvelle ligne si le document a été signé
$nbMailsRetrieved = 0;
foreach ($retrievedMails['noVersion'] as $resId => $value) {
    $GLOBALS['logger']->write('Update res_attachments : ' . $resId . '. ExternalId : ' . $value->external_id, 'INFO');

    if (!empty($value->log)) {
        $GLOBALS['logger']->write('Create log Attachment', 'INFO');
        Bt_createAttachment([
            'resIdMaster'       => $value->res_id_master,
            'title'             => '[xParaph Log] ' . $value->title,
            'chrono'            => $value->identifier,
            'recipientId'       => $value->recipient_id,
            'recipientType'     => $value->recipient_type,
            'typist'            => $value->typist,
            'format'            => 'xml',
            'type'              => $value->attachment_type,
            'inSignatureBook'   => false,
            'encodedFile'       => $value->log,
            'status'            => 'TRA'
        ]);
    }
    $additionalHistoryInfo = '';
    if (!empty($value->workflowInfo)) {
        $additionalHistoryInfo =  ' : ' . $value->workflowInfo;
    }

    if ($value->status == 'validated') {
        if (!empty($value->encodedFile)) {
            \SrcCore\models\DatabaseModel::delete([
                'table' => 'res_attachments',
                'where' => ['res_id_master = ?', 'status = ?', 'relation = ?', 'origin = ?'],
                'data'  => [$value->res_id_master, 'SIGN', $value->relation, $value->res_id . ',res_attachments']
            ]);

            $GLOBALS['logger']->write('Create validated Attachment', 'INFO');
            Bt_createAttachment([
                'resIdMaster'     => $value->res_id_master,
                'title'           => $value->title,
                'chrono'          => $value->identifier,
                'recipientId'     => $value->recipient_id,
                'recipientType'   => $value->recipient_type,
                'typist'          => $value->typist,
                'format'          => $value->format,
                'type'            => 'signed_response',
                'status'          => 'TRA',
                'encodedFile'     => $value->encodedFile,
                'inSignatureBook' => true,
                'originId'        => $resId
            ]);
        }

        $GLOBALS['logger']->write('Document validated', 'INFO');
        $GLOBALS['db']->query("UPDATE res_attachments SET status = 'SIGN', external_id = external_id - 'signatureBookId', in_signature_book = 'false' WHERE res_id = ?", [$resId]);
        if (!empty($value->onlyVisa) && $value->onlyVisa) {
            $status = $validatedStatusOnlyVisa;
        } else {
            $status = $validatedStatus;
        }
        Bt_validatedMail(['status' => $status, 'resId' => $value->res_id_master]);

        $historyInfo = 'La signature de la pièce jointe '.$resId.' (res_attachments) a été validée dans le parapheur externe' . $additionalHistoryInfo;
    } elseif ($value->status == 'refused') {
        if (!empty($value->encodedFile)) {
            $GLOBALS['logger']->write('Create refused Attachment', 'INFO');
            Bt_createAttachment([
                'resIdMaster'     => $value->res_id_master,
                'title'           => '[REFUSE] ' . $value->title,
                'chrono'          => $value->identifier,
                'recipientId'     => $value->recipient_id,
                'recipientType'   => $value->recipient_type,
                'typist'          => $value->typist,
                'format'          => $value->format,
                'type'            => $value->attachment_type,
                'status'          => 'A_TRA',
                'encodedFile'     => $value->encodedFile,
                'inSignatureBook' => false
            ]);
        }
        $GLOBALS['logger']->write('Document refused', 'INFO');
        $GLOBALS['db']->query('UPDATE listinstance SET process_date = NULL WHERE res_id = ? AND difflist_type = ?', [$value->res_id_master, 'VISA_CIRCUIT']);
        $GLOBALS['db']->query("UPDATE res_attachments SET status = 'A_TRA', external_id = external_id - 'signatureBookId' WHERE res_id = ?", [$resId]);
        $GLOBALS['db']->query("UPDATE res_letterbox SET status = '" . $refusedStatus . "' WHERE res_id = ?", [$value->res_id_master]);
    
        $historyInfo = 'La signature de la pièce jointe '.$resId.' (res_attachments) a été refusée dans le parapheur externe' . $additionalHistoryInfo;
    }
    if (in_array($value->status, ['validated', 'refused'])) {
        Bt_createNote(['creatorId' => $value->noteCreatorId, 'creatorName' => $value->noteCreatorName, 'content' => $value->noteContent, 'resId' => $value->res_id_master]);
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
        $nbMailsRetrieved++;
    }
}

foreach ($retrievedMails['resLetterbox'] as $resId => $value) {
    $GLOBALS['logger']->write('Update res_letterbox : ' . $resId . '. SignatoryBookId : ' . $value->external_id, 'INFO');

    if (!empty($value->encodedFile)) {
        $GLOBALS['logger']->write('Create document in res_letterbox', 'INFO');
        if ($value->status =='validated') {
            $typeToDelete = ['SIGN', 'TNL'];
        } else {
            $typeToDelete = ['NOTE'];
        }
        \SrcCore\models\DatabaseModel::delete([
            'table' => 'adr_letterbox',
            'where' => ['res_id = ?', 'type in (?)', 'version = ?'],
            'data'  => [$resId, $typeToDelete, $value->version]
        ]);

        $storeResult = \Docserver\controllers\DocserverController::storeResourceOnDocServer([
            'collId'          => 'letterbox_coll',
            'docserverTypeId' => 'DOC',
            'encodedResource' => $value->encodedFile,
            'format'          => 'pdf'
        ]);
        \SrcCore\models\DatabaseModel::insert([
            'table'         => 'adr_letterbox',
            'columnsValues' => [
                'res_id'       => $resId,
                'type'         => in_array($value->status, ['refused', 'refusedNote', 'validatedNote']) ? 'NOTE' : 'SIGN',
                'docserver_id' => $storeResult['docserver_id'],
                'path'         => $storeResult['destination_dir'],
                'filename'     => $storeResult['file_destination_name'],
                'version'      => $value->version,
                'fingerprint'  => empty($storeResult['fingerPrint']) ? null : $storeResult['fingerPrint']
            ]
        ]);
    }
    if (in_array($value->status, ['validatedNote', 'validated', 'refusedNote', 'refused'])) {
        $additionalHistoryInfo = '';
        if (!empty($value->workflowInfo)) {
            $additionalHistoryInfo =  ' : ' . $value->workflowInfo;
        }
        if (in_array($value->status, ['validatedNote', 'validated'])) {
            $GLOBALS['logger']->write('Document validated', 'INFO');
            $status = $validatedStatus;
            if ($value->status == 'validatedNote') {
                $status = $validatedStatusAnnot;
            }
            $history = 'Le document '.$resId.' (res_letterbox) a été validé dans le parapheur externe' . $additionalHistoryInfo;
        } elseif (in_array($value->status, ['refusedNote', 'refused'])) {
            $GLOBALS['logger']->write('Document refused', 'INFO');
            $status = $refusedStatus;
            if ($value->status == 'refusedNote') {
                $status = $refusedStatusAnnot;
            }
            $history = 'Le document '.$resId.' (res_letterbox) a été refusé dans le parapheur externe' . $additionalHistoryInfo;
        }
        Bt_history([
            'table_name' => 'res_letterbox',
            'record_id'  => $resId,
            'info'       => $history,
            'event_type' => 'ACTION#1',
            'event_id'   => '1'
        ]);
        Bt_createNote(['creatorId' => $value->noteCreatorId, 'creatorName' => $value->noteCreatorName, 'content' => $value->noteContent, 'resId' => $resId]);
        $GLOBALS['db']->query("UPDATE res_letterbox SET status = ?, external_id = external_id - 'signatureBookId' WHERE res_id = ?", [$status, $resId]);
        $nbMailsRetrieved++;
    }
}

$GLOBALS['logger']->write('End of process', 'INFO');
$GLOBALS['logger']->write($nbMailsRetrieved.' document(s) retrieved', 'INFO');

Bt_logInDataBase(
    $nbMailsRetrieved,
    $err,
    $nbMailsRetrieved.' mail(s) retrieved from signatory book'
);
Bt_updateWorkBatch();

exit($GLOBALS['exitCode']);
