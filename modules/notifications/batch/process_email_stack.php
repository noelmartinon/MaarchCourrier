<?php

/******************************************************************************/
/* begin */
// load the config and prepare to process
include('load_process_email_stack.php');
$state = 'LOAD_EMAILS';
while ($state <> 'END') {
    if (isset($GLOBALS['logger'])) {
        $GLOBALS['logger']->write('STATE:' . $state, 'INFO');
    }
    switch ($state) {
        
    /**********************************************************************/
    /*                          LOAD_NOTIFS                               */
    /* List the stack to proceed                                          */
    /**********************************************************************/
    case 'LOAD_EMAILS' :
        $query = "SELECT count(1) as count FROM " . _NOTIF_EMAIL_STACK_TABLE_NAME
            . " WHERE exec_date is NULL";
        $stmt = Bt_doQuery($GLOBALS['db'], $query, array());
        $totalEmailsToProcess = $stmt->fetchObject()->count;
        $query = "SELECT * FROM " . _NOTIF_EMAIL_STACK_TABLE_NAME
            . " WHERE exec_date is NULL";
        $stmt = Bt_doQuery($GLOBALS['db'], $query, array());
       // $totalEmailsToProcess = $stmt->rowCount();
        $currentEmail = 0;
        if ($totalEmailsToProcess === 0) {
            Bt_exitBatch(0, 'No notification to send');
        }
        $GLOBALS['logger']->write($totalEmailsToProcess . ' notification(s) to send.', 'INFO');
        $GLOBALS['emails'] = array();
        while ($emailRecordset = $stmt->fetchObject()) {
            $GLOBALS['emails'][] = $emailRecordset;
        }
        $state = 'SEND_AN_EMAIL';
        $err = 0;
        $errTxt = '';
        break;
        
    /**********************************************************************/
    /*                          SEND_AN_EMAIL                                 */
    /* Load parameters and send an e-mail                                    */
    /**********************************************************************/
    case 'SEND_AN_EMAIL' :
        if($currentEmail < $totalEmailsToProcess) {
            $email = $GLOBALS['emails'][$currentEmail];
            $GLOBALS['mailer'] = new htmlMimeMail();
            $GLOBALS['mailer']->setSMTPParams(
                $host = (string)$mailerParams->smtp_host, 
                $port = (string)$mailerParams->smtp_port,
                $helo = (string)$mailerParams->domains,
                $auth = filter_var($mailerParams->smtp_auth, FILTER_VALIDATE_BOOLEAN),
                $user = (string)$mailerParams->smtp_user,
                $pass = (string)$mailerParams->smtp_password
                );
            $GLOBALS['logger']->write("sending notification ".($currentEmail+1)."/".$totalEmailsToProcess." (TO => " . $email->recipient.', SUBJECT => '.$email->subject.' ) ...', 'INFO');
            //--> Set the return path
            $email->html_body = str_replace('#and#', '&', $email->html_body);
            $email->html_body = str_replace("\''", "'", $email->html_body);
            $email->html_body = str_replace("\'", "'", $email->html_body);
            $email->html_body = str_replace("''", "'", $email->html_body);

            if (((string)$mailerParams->type) == 'smtp') {
                if (empty($email->sender) && !empty((string)$mailerParams->mailfrom)) {
                    $email->sender = (string)$mailerParams->mailfrom;
                }

                if (substr($email->sender, 0, 1) != '<') {
                    $email->sender = '<' . $email->sender;
                }
                if (substr($email->sender, -1) != '>') {
                    $email->sender .= '>';
                }
            }

            if ($email->sender <> '<>') {
                $GLOBALS['mailer']->setReturnPath($email->sender);
                $GLOBALS['mailer']->setFrom($email->sender);
            }
            //$GLOBALS['logger']->write("Subject : " . $email->subject, 'INFO');
            $GLOBALS['mailer']->setSubject($email->subject);
            $GLOBALS['mailer']->setHtml($email->html_body);
            $GLOBALS['mailer']->setTextCharset((string)$email->charset);
            $GLOBALS['mailer']->setHtmlCharset((string)$email->charset);
            $GLOBALS['mailer']->setHeadCharset((string)$email->charset);
            
            if($email->attachments != '') {
                $attachments = explode(',', $email->attachments);
                foreach($attachments as $num => $attachment) {
                    if(is_file($attachment)) {
                    $ext = strrchr($attachment, '.');
                    $name = str_pad(($num + 1), 4, '0', STR_PAD_LEFT) . $ext;
                    $ctype = '';
                    switch($ext) {
                        case '.pdf':
                            $ctype = 'application/pdf';
                            break;
                    }
                    $file_content = $GLOBALS['mailer']->getFile($attachment);
                    $GLOBALS['mailer']->addAttachment($file_content, $name, $ctype); 
                    }
                }
            }
            $return = $GLOBALS['mailer']->send(array($email->recipient), (string)$mailerParams->type);

            // if($return == 1) {
            if( ($return == 1 && ((string)$mailerParams->type == "smtp" || (string)$mailerParams->type == "mail" )) || ($return == 0 && (string)$mailerParams->type == "sendmail")) {
                $exec_result = 'SENT';
                $GLOBALS['logger']->write("notification sent", 'INFO');
                
            } else {
                //$GLOBALS['logger']->write("Errors when sending message through SMTP :" . implode(', ', $GLOBALS['mailer']->errors), 'ERROR');
                $err++;
                $GLOBALS['logger']->write("SENDING EMAIL ERROR ! (" . $return[0].")", 'ERROR');
                $errTxt = ' (Last Error : '.$return[0].')';
                $exec_result = 'FAILED';
                $GLOBALS['exitCode'] = 108;
            }   
            $query = "UPDATE " . _NOTIF_EMAIL_STACK_TABLE_NAME 
                . " SET exec_date = CURRENT_TIMESTAMP, exec_result = ? "
                . " WHERE email_stack_sid = ?";
            Bt_doQuery($GLOBALS['db'], $query, array($exec_result, $email->email_stack_sid));
            $currentEmail++;
            $state = 'SEND_AN_EMAIL';
        } else {
            $state = 'END';
        }
        break;
    }
}
$emailSent = $totalEmailsToProcess - $err;

$GLOBALS['logger']->write($emailSent.' notification(s) sent', 'INFO');
$GLOBALS['logger']->write('End of process', 'INFO');

Bt_logInDataBase(
    $totalEmailsToProcess, $err, $emailSent.' notification(s) sent'.$errTxt
);
Bt_updateWorkBatch();

unlink($GLOBALS['lckFile']);
exit($GLOBALS['exitCode']);
?>
