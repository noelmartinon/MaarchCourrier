<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
 * @brief Check all replies in record management system
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

// Globals variables definition
$GLOBALS['batchName']    = 'endOfLifeCycle';
$GLOBALS['wb']           = '';
$totalProcessedResources = 0;

// Load tools
include('batch_tools.php');

$options = getopt("c:", ["config:"]);
if (empty($options['c']) && empty($options['config'])) {
    print("Configuration file missing\n");
    exit(101);
} elseif (!empty($options['c']) && empty($options['config'])) {
    $options['config'] = $options['c'];
    unset($options['c']);
}

$txt = '';
foreach (array_keys($options) as $key) {
    if (isset($options[$key]) && $options[$key] == false) {
        $txt .= $key . '=false,';
    } else {
        $txt .= $key . '=' . $options[$key] . ',';
    }
}
print($txt . "\n");
$GLOBALS['configFile'] = $options['config'];

print("Load json config file:" . $GLOBALS['configFile'] . "\n");
// Tests existence of config file
if (!file_exists($GLOBALS['configFile'])) {
    print(
        "Configuration file " . $GLOBALS['configFile']
        . " does not exist\n"
    );
    exit(102);
}

$file = file_get_contents($GLOBALS['configFile']);
$file = json_decode($file, true);

if (empty($file)) {
    print("Error on loading config file:" . $GLOBALS['configFile'] . "\n");
    exit(103);
}

// Load config
$config = $file['config'];
$GLOBALS['MaarchDirectory'] = $config['maarchDirectory'];
$GLOBALS['customId']        = $config['customID'];
$GLOBALS['applicationUrl']  = $config['maarchUrl'];
$GLOBALS['batchDirectory']  = $GLOBALS['MaarchDirectory'] . 'bin/exportSeda';

$config = $file['exportSeda'];
$GLOBALS['statusMailToPurge'] = $config['statusMailToPurge'];

chdir($GLOBALS['MaarchDirectory']);

set_include_path(get_include_path() . PATH_SEPARATOR . $GLOBALS['MaarchDirectory']);

try {
    Bt_myInclude($GLOBALS['MaarchDirectory'] . 'vendor/autoload.php');
} catch (IncludeFileError $e) {
    Bt_writeLog(['level' => 'ERROR', 'message' => 'Problem with the php include path:' .$e .' '. get_include_path()]);
    exit();
}

\SrcCore\models\DatabasePDO::reset();
new \SrcCore\models\DatabasePDO(['customId' => $GLOBALS['customId']]);

$GLOBALS['errorLckFile'] = $GLOBALS['batchDirectory'] . DIRECTORY_SEPARATOR . $GLOBALS['batchName'] .'_error.lck';
$GLOBALS['lckFile']      = $GLOBALS['batchDirectory'] . DIRECTORY_SEPARATOR . $GLOBALS['batchName'] . '.lck';

if (file_exists($GLOBALS['errorLckFile'])) {
    Bt_writeLog(['level' => 'ERROR', 'message' => 'Error persists, please solve this before launching a new batch']);
    exit(13);
}

Bt_getWorkBatch();
Bt_writeLog(['level' => 'INFO', 'message' => 'Retrieve mail to purge']);

$wherePurge = ['retention_frozen = ?', 'duration_current_use is not null', 'creation_date + interval \'1 day\' * duration_current_use < CURRENT_TIMESTAMP'];
$dataPurge  = ['false'];

if (!empty($GLOBALS['statusMailToPurge'])) {
    $wherePurge[] = 'status = ?';
    $dataPurge[]  = $GLOBALS['statusMailToPurge'];
}

$tmpWhere = ['binding = null and action_current_use = ?'];
$tmpData  = ['delete'];

$bindingDocument = \Parameter\models\ParameterModel::getById(['select' => ['param_value_string'], 'id' => 'bindingDocumentFinalAction']);
if ($bindingDocument == 'delete') {
    $tmpWhere[] = 'binding = ?';
    $tmpData[]  = 'true';
}
$nonBindingDocument = \Parameter\models\ParameterModel::getById(['select' => ['param_value_string'], 'id' => 'nonBindingDocumentFinalAction']);
if ($nonBindingDocument == 'delete') {
    $tmpWhere[] = 'binding = ?';
    $tmpData[]  = 'false';
}

$replies = \Attachment\models\AttachmentModel::get([
    'select' => ['res_id', 'res_id_master', 'path', 'filename', 'docserver_id', 'fingerprint'],
    'where'  => ['attachment_type = ?', 'status = ?'],
    'data'   => ['reply_record_management', 'TRA']
]);

$resIdMaster = [];
foreach ($replies as $reply) {
    $docserver = \Docserver\models\DocserverModel::getByDocserverId(['docserverId' => $reply['docserver_id'], 'select' => ['path_template', 'docserver_type_id']]);
    if (empty($docserver['path_template']) || !file_exists($docserver['path_template'])) {
        Bt_writeLog(['level' => 'WARNING', 'message' => 'Docserver does not exists (' . $reply['docserver_id'] . ') for attachment res_id : ' . $reply['res_id']]);
        continue;
    }

    $pathToDocument = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $reply['path']) . $reply['filename'];
    if (!is_file($pathToDocument)) {
        Bt_writeLog(['level' => 'WARNING', 'message' => 'File does not exists (' . $pathToDocument . ') for attachment res_id : ' . $reply['res_id']]);
        continue;
    }
    
    $replyXml = @simplexml_load_file($pathToDocument);
    if (empty($replyXml)) {
        Bt_writeLog(['level' => 'WARNING', 'message' => 'Reply is not readable for attachment res_id : ' . $reply['res_id']]);
        continue;
    }

    $messageExchange = \MessageExchange\models\MessageExchangeModel::getMessageByReference(['select' => ['message_id'], 'reference' => (string)$replyXml->MessageReceivedIdentifier]);
    if (empty($messageExchange)) {
        Bt_writeLog(['level' => 'WARNING', 'message' => 'Reply is not readable for this reference : ' . (string)$replyXml->MessageReceivedIdentifier]);
        continue;
    }

    $unitIdentifier = \MessageExchange\models\MessageExchangeModel::getUnitIdentifierByResId(['select' => ['message_id'], 'resId' => $args['resId']]);
    if ($unitIdentifier['message_id'] != $messageExchange['message_id']) {
        Bt_writeLog(['level' => 'WARNING', 'message' => 'Wrong reply for attachment res_id : ' . $reply['res_id']]);
        continue;
    }

    $resIdMaster[] = $reply['res_id_master'];
}

if (!empty($resIdMaster)) {
    $tmpWhere[] = 'res_id in (?)';
    $tmpData[]  = $resIdMaster;
}

$wherePurge[] = '(' . implode(") or (", $tmpWhere) . ')';
$dataPurge    = array_merge($dataPurge, $tmpData);

$resources = \SrcCore\models\DatabaseModel::select([
    'select'    => ['res_id'],
    'table'     => ['res_letterbox r', 'doctypes d'],
    'left_join' => ['r.type_id = d.type_id'],
    'where'     => $wherePurge,
    'data'      => $dataPurge
]);
$resources = array_column($resources, 'res_id');

Bt_purgeAll(['resources' => $resources]);

Bt_writeLog(['level' => 'INFO', 'message' => 'End of process']);
Bt_writeLog(['level' => 'INFO', 'message' => $nbMailsPurge.' document(s) retrieved']);

Bt_logInDataBase($nbMailsPurge, $err, $nbMailsPurge.' mail(s) purge');
Bt_updateWorkBatch();

exit($GLOBALS['exitCode']);
