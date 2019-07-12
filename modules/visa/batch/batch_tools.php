<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
 * @brief API to manage batchs
 *
 * @file
 * @author <dev@maarch.org>
 * @date $date$
 * @version $Revision$
 */

/**
 * Execute a sql query
 *
 * @param object $dbConn connection object to the database
 * @param string $queryTxt path of the file to include
 * @param boolean $transaction for rollback if error
 * @return true if ok, exit if ko and rollback if necessary
 */
function Bt_doQuery($dbConn, $queryTxt, $param=array(), $transaction=false)
{
    if (count($param) > 0) {
        $stmt = $dbConn->query($queryTxt, $param);
    } else {
        $stmt = $dbConn->query($queryTxt);
    }

    if (!$stmt) {
        if ($transaction) {
            $GLOBALS['logger']->write('ROLLBACK', 'INFO');
            $dbConn->query('ROLLBACK');
        }
        Bt_exitBatch(
            104,
            'SQL Query error:' . $queryTxt
        );
    }
    $GLOBALS['logger']->write('SQL query:' . $queryTxt, 'DEBUG');
    return $stmt;
}

/**
 * Exit the batch with a return code, message in the log and
 * in the database if necessary
 *
 * @param int $returnCode code to exit (if > O error)
 * @param string $message message to the log and the DB
 * @return nothing exit the program
 */
function Bt_exitBatch($returnCode, $message='')
{
    if (file_exists($GLOBALS['lckFile'])) {
        unlink($GLOBALS['lckFile']);
    }
    if ($returnCode > 0) {
        $GLOBALS['totalProcessedResources']--;
        if ($GLOBALS['totalProcessedResources'] == -1) {
            $GLOBALS['totalProcessedResources'] = 0;
        }
        if ($returnCode < 100) {
            if (file_exists($GLOBALS['errorLckFile'])) {
                unlink($GLOBALS['errorLckFile']);
            }
            $semaphore = fopen($GLOBALS['errorLckFile'], "a");
            fwrite($semaphore, '1');
            fclose($semaphore);
        }
        $GLOBALS['logger']->write($message, 'ERROR', $returnCode);
        Bt_logInDataBase($GLOBALS['totalProcessedResources'], 1, $message.' (return code: '. $returnCode.')');
    } elseif ($message <> '') {
        $GLOBALS['logger']->write($message, 'INFO', $returnCode);
        Bt_logInDataBase($GLOBALS['totalProcessedResources'], 0, $message.' (return code: '. $returnCode.')');
    }
    Bt_updateWorkBatch();
    exit($returnCode);
}

/**
* Insert in the database the report of the batch
* @param long $totalProcessed total of resources processed in the batch
* @param long $totalErrors total of errors in the batch
* @param string $info message in db
*/
function Bt_logInDataBase($totalProcessed=0, $totalErrors=0, $info='')
{
    $query = "INSERT INTO history_batch (module_name, batch_id, event_date, "
           . "total_processed, total_errors, info) values(?, ?, CURRENT_TIMESTAMP, ?, ?, ?)";
    $arrayPDO = array($GLOBALS['batchName'], $GLOBALS['wb'], $totalProcessed, $totalErrors, substr(str_replace('\\', '\\\\', str_replace("'", "`", $info)), 0, 999));
    $GLOBALS['db']->query($query, $arrayPDO);
}


/**
* Insert in the database a line for history
*/
function Bt_history($aArgs = [])
{
    $query = "INSERT INTO history (table_name, record_id, event_type, "
           . "user_id, event_date, info, id_module, remote_ip, event_id) values(?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?)";
    $arrayPDO = array($aArgs['table_name'], $aArgs['record_id'], $aArgs['event_type'], 'superadmin', $aArgs['info'], 'visa', 'localhost', $aArgs['event_id']);
    $GLOBALS['db']->query($query, $arrayPDO);
}

/**
 * Get the batch if of the batch
 *
 * @return nothing
 */
function Bt_getWorkBatch()
{
    $req = "SELECT param_value_int FROM parameters WHERE id = ? ";
    $stmt = $GLOBALS['db']->query($req, array($GLOBALS['batchName']."_id"));
    
    while ($reqResult = $stmt->fetchObject()) {
        $GLOBALS['wb'] = $reqResult->param_value_int + 1;
    }
    if ($GLOBALS['wb'] == '') {
        $req = "INSERT INTO parameters(id, param_value_int) VALUES (?, 1)";
        $GLOBALS['db']->query($req, array($GLOBALS['batchName']."_id"));
        $GLOBALS['wb'] = 1;
    }
}

/**
 * Update the database with the new batch id of the batch
 *
 * @return nothing
 */
function Bt_updateWorkBatch()
{
    $req = "UPDATE parameters SET param_value_int = ? WHERE id = ?";
    $GLOBALS['db']->query($req, array($GLOBALS['wb'], $GLOBALS['batchName']."_id"));
}

/**
 * Include the file requested if exists
 *
 * @param string $file path of the file to include
 * @return nothing
 */
function Bt_myInclude($file)
{
    if (file_exists($file)) {
        include_once($file);
    } else {
        throw new IncludeFileError($file);
    }
}

function Bt_createAttachment($aArgs = [])
{
    if (!empty($aArgs['noteContent'])) {
        $creatorName = '';
        if (!empty($aArgs['noteCreatorId'])) {
            $creatorId = $aArgs['noteCreatorId'];
        } else {
            $creatorId = 'superadmin';
            $creatorName = $aArgs['noteCreatorName'] . ' : ';
        }
        $GLOBALS['db']->query(
            "INSERT INTO notes (identifier, user_id, creation_date, note_text) VALUES (?, ?, CURRENT_TIMESTAMP, ?)",
            [$aArgs['res_id_master'], $creatorId, $creatorName . $aArgs['noteContent']]
        );
    }

    if (!empty($aArgs['attachment_type'])) {
        $attachmentType = $aArgs['attachment_type'];
    } else {
        $attachmentType = 'signed_response';
    }

    if (!empty($aArgs['in_signature_book'])) {
        $inSignatureBook = $aArgs['in_signature_book'];
    } else {
        $inSignatureBook = 'true';
    }

    if (!empty($aArgs['table'])) {
        $table = $aArgs['table'];
    } else {
        $table = 'res_attachments';
    }

    if (!empty($aArgs['relation'])) {
        $relation = $aArgs['relation'];
    } else {
        $relation = 1;
    }

    if (!empty($aArgs['status'])) {
        $status = $aArgs['status'];
    } else {
        $status = 'TRA';
    }

    $dataValue = [];
    array_push($dataValue, ['column' => 'res_id_master',    'value' => $aArgs['res_id_master'],   'type' => 'integer']);
    array_push($dataValue, ['column' => 'title',            'value' => $aArgs['title'],           'type' => 'string']);
    array_push($dataValue, ['column' => 'identifier',       'value' => $aArgs['identifier'],      'type' => 'string']);
    array_push($dataValue, ['column' => 'type_id',          'value' => 1,                         'type' => 'integer']);
    array_push($dataValue, ['column' => 'dest_contact_id',  'value' => $aArgs['dest_contact_id'], 'type' => 'integer']);
    array_push($dataValue, ['column' => 'dest_address_id',  'value' => $aArgs['dest_address_id'], 'type' => 'integer']);
    array_push($dataValue, ['column' => 'dest_user',        'value' => $aArgs['dest_user'],       'type' => 'string']);
    array_push($dataValue, ['column' => 'typist',           'value' => $aArgs['typist'],          'type' => 'string']);
    array_push($dataValue, ['column' => 'attachment_type',  'value' => $attachmentType,           'type' => 'string']);
    array_push($dataValue, ['column' => 'coll_id',          'value' => 'letterbox_coll',          'type' => 'string']);
    array_push($dataValue, ['column' => 'relation',         'value' => $relation,                 'type' => 'integer']);
    array_push($dataValue, ['column' => 'in_signature_book','value' => $inSignatureBook,          'type' => 'bool']);

    if (!empty($aArgs['attachment_id_master'])) {
        array_push($dataValue, ['column' => 'attachment_id_master','value' => $aArgs['attachment_id_master'], 'type' => 'integer']);
    }

    $allDatas = [
        "encodedFile" => $aArgs['encodedFile'],
        "data"        => $dataValue,
        "collId"      => "letterbox_coll",
        "table"       => $table,
        "fileFormat"  => $aArgs['format'],
        "status"      => $status
    ];

    $opts = [
        CURLOPT_URL => $GLOBALS['applicationUrl'] . 'rest/res',
        CURLOPT_HTTPHEADER => [
            'accept:application/json',
            'content-type:application/json',
            'Authorization: Basic ' . base64_encode($GLOBALS['userWS']. ':' .$GLOBALS['passwordWS']),
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS => json_encode($allDatas),
        CURLOPT_POST => true
    ];

    $curl = curl_init();
    curl_setopt_array($curl, $opts);
    $rawResponse = curl_exec($curl);
    $error = curl_error($curl);
    if (!empty($error)) {
        $GLOBALS['logger']->write($error, 'ERROR');
        exit;
    }

    return json_decode($rawResponse, true);
}

function Bt_refusedSignedMail($aArgs = [])
{
    if (!empty($aArgs['noteContent'])) {
        $creatorName = '';
        if (!empty($aArgs['noteCreatorId'])) {
            $creatorId = $aArgs['noteCreatorId'];
        } else {
            $creatorId = 'superadmin';
            $creatorName = $aArgs['noteCreatorName'] . ' : ';
        }
        $GLOBALS['db']->query(
            "INSERT INTO notes (identifier, user_id, creation_date, note_text) VALUES (?, $creatorId, CURRENT_TIMESTAMP, ?)",
            [$aArgs['resIdMaster'], $creatorName . $aArgs['noteContent']]
        );
    }
    $GLOBALS['db']->query("UPDATE ".$aArgs['tableAttachment']." SET status = 'A_TRA', external_id = external_id - 'signatureBookId' WHERE res_id = ?", [$aArgs['resIdAttachment']]);
    $GLOBALS['db']->query('UPDATE listinstance SET process_date = NULL WHERE res_id = ? AND difflist_type = ?', [$aArgs['resIdMaster'], 'VISA_CIRCUIT']);
    
    $GLOBALS['db']->query("UPDATE res_letterbox SET status = '" . $aArgs['refusedStatus'] . "' WHERE res_id = ?", [$aArgs['resIdMaster']]);

    $historyInfo = 'La signature de la pièce jointe '.$aArgs['resIdAttachment'].' ('.$aArgs['tableAttachment'].') a été refusée dans le parapheur externe' . $aArgs['additionalHistoryInfo'];
    Bt_history([
        'table_name' => $aArgs['tableAttachment'],
        'record_id'  => $aArgs['resIdAttachment'],
        'info'       => $historyInfo,
        'event_type' => 'UP',
        'event_id'   => 'attachup'
    ]);

    Bt_history([
        'table_name' => 'res_letterbox',
        'record_id'  => $aArgs['resIdMaster'],
        'info'       => $historyInfo,
        'event_type' => 'ACTION#1',
        'event_id'   => '1'
    ]);
}

function Bt_validatedMail($aArgs = [])
{
    $req       = "SELECT count(1) as nbresult FROM res_view_attachments WHERE res_id_master = ? AND status = ?";
    $stmt      = $GLOBALS['db']->query($req, array($aArgs['resId'], 'FRZ'));
    $reqResult = $stmt->fetchObject();
    if ($reqResult->nbresult == 0) {
        $GLOBALS['db']->query('UPDATE res_letterbox SET status = ? WHERE res_id = ? ', [$aArgs['status'], $aArgs['resId']]);
    }
}
