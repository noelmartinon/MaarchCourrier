<?php

/******************************************************************************/

// load the config and prepare to process
include('load_process_emails.php');

require '../../../vendor/autoload.php';

new \SrcCore\models\DatabasePDO([
    'server'   => $_SESSION['config']['databaseserver'],
    'port'     => $_SESSION['config']['databaseserverport'],
    'user'     => $_SESSION['config']['databaseuser'],
    'password' => $_SESSION['config']['databasepassword'],
    'name'     => $_SESSION['config']['databasename'],
    'type'     => $_SESSION['config']['databasetype'],
]);


/* begin */
$state = 'LOAD_EMAILS';
while ($state <> 'END') {
    if (isset($GLOBALS['logger'])) {
        $GLOBALS['logger']->write('STATE:' . $state, 'INFO');
    }
    switch ($state) {
        
    /**********************************************************************/
    /*                          LOAD_EMAILS 							  */
    /* List the records to proceed       								  */
    /**********************************************************************/
    case 'LOAD_EMAILS':
        $query = "SELECT * FROM " . EMAILS_TABLE
            . " WHERE email_status = 'W' and send_date is NULL";
        $stmt = Bt_doQuery($GLOBALS['db'], $query);
        $totalEmailsToProcess = $stmt->rowCount();
        $currentEmail = 0;
        if ($totalEmailsToProcess === 0) {
            Bt_exitBatch(0, 'No e-mails to process');
        }
        $GLOBALS['logger']->write($totalEmailsToProcess . ' e-mails to proceed.', 'INFO');
        $GLOBALS['emails'] = array();
        while ($emailRecordset = $stmt->fetchObject()) {
            $GLOBALS['emails'][] = $emailRecordset;
        }
        $state = 'SEND_AN_EMAIL';
        $err = 0;
        $errTxt = '';
    break;
        
    /**********************************************************************/
    /*                          SEND_AN_EMAIL	 		          	          */
    /* Load parameters and send an e-mail                                    */
    /**********************************************************************/
    case 'SEND_AN_EMAIL':
        if ($currentEmail < $totalEmailsToProcess) {
            $email = $GLOBALS['emails'][$currentEmail];
            //var_dump($email);exit;
            $GLOBALS['mailer'] = new htmlMimeMail();
            $GLOBALS['mailer']->setSMTPParams(
                $host = (string)$mailerParams->smtp_host,
                $port = (string)$mailerParams->smtp_port,
                $helo = (string)$mailerParams->domains,
                $auth = filter_var($mailerParams->smtp_auth, FILTER_VALIDATE_BOOLEAN),
                $user = (string)$mailerParams->smtp_user,
                $pass = (string)$mailerParams->smtp_password
                );
            $mailfrom_generic = (string)$mailerParams->mailfrom;
            
            //Composing email
            //--> Set from
            $userInfo = $users->get_user($email->user_id);
            //var_dump($userInfo);
            //echo 'userInfo : ' . $userInfo['mail'] . '==' . ' sender_email : ' . $email->sender_email . PHP_EOL;
            if ($userInfo['mail'] == $email->sender_email) {
                if (!empty($mailfrom_generic)) {
                    $GLOBALS['logger']->write('process e-mail '.($currentEmail+1)."/".$totalEmailsToProcess.' (FROM => '.$userInfo['firstname'].' '.$userInfo['lastname'].' <'.$mailfrom_generic.'>'.', TO => '.$email->to_list.', SUBJECT => '.$email->email_object.', CC =>'.$email->cc_list.', CCI => '.$email->cci_list.') ...', 'INFO');

                    $GLOBALS['mailer']->setFrom($userInfo['firstname'].' '
                        . $userInfo['lastname'].' <'.$mailfrom_generic.'> ');
                    $emailFrom = $mailfrom_generic;
                    $email->email_body = 'Courriel envoyé par : ' . $userInfo['firstname'].' '
                        . $userInfo['lastname'] . ' ' . $email->sender_email . ' ' .  '.<br/><br/>' . $email->email_body;
                } else {
                    $GLOBALS['logger']->write('process e-mail '.($currentEmail+1)."/".$totalEmailsToProcess.' (FROM => '.$userInfo['firstname'].' '.$userInfo['lastname'].' <'.$email->sender_email.'>'.', TO => '.$email->to_list.', SUBJECT => '.$email->email_object.', CC =>'.$email->cc_list.', CCI => '.$email->cci_list.') ...', 'INFO');

                    $GLOBALS['mailer']->setFrom($userInfo['firstname'].' '
                        . $userInfo['lastname'].' <'.$email->sender_email.'> ');
                    $emailFrom = $email->sender_email;
                }
                $GLOBALS['mailer']->setReplyTo($email->sender_email);
            } else {
                if (!empty($mailfrom_generic)) {
                    $mailsEntities = $sendmail_tools->getAttachedEntitiesMails();
                    $entityShortLabel = substr($mailsEntities[$email->sender_email], 0, strrpos($mailsEntities[$email->sender_email], "("));
                        
                    $GLOBALS['mailer']->setFrom($entityShortLabel . ' <' . $mailfrom_generic. '> ');
                    $emailFrom = $mailfrom_generic;
                    $email->email_body = 'Courriel envoyé par : ' . $entityShortLabel . ' ' . $sendmail_tools->explodeSenderEmail($email->sender_email) . ' ' .  '.<br/><br/>' . $email->email_body;
                } else {
                    $mailsEntities = $sendmail_tools->getAttachedEntitiesMails();
                    $entityShortLabel = substr($mailsEntities[$email->sender_email], 0, strrpos($mailsEntities[$email->sender_email], "("));

                    $GLOBALS['mailer']->setFrom($entityShortLabel . ' <' . $sendmail_tools->explodeSenderEmail($email->sender_email) . '> ');
                    $emailFrom = $sendmail_tools->explodeSenderEmail($email->sender_email);
                }
                $GLOBALS['mailer']->setReplyTo($sendmail_tools->explodeSenderEmail($email->sender_email));
            }

            //echo $email->email_body . PHP_EOL;exit;

            if (!empty($email->cc_list)) {
                $GLOBALS['logger']->write("Copy e-mail to : " . $email->cc_list, 'INFO');
            }
            if (!empty($email->cci_list)) {
                $GLOBALS['logger']->write("Copy invisible e-mail to : " . $email->cci_list, 'INFO');
            }
            
            //--> Set the return path
            if (!empty($mailfrom_generic)) {
                $GLOBALS['mailer']->setReturnPath(
                    $userInfo['firstname'] . ' ' .  $userInfo['lastname'] . ' <' . $mailfrom_generic . '> '
                );
            } else {
                $GLOBALS['mailer']->setReturnPath($userInfo['mail']);
            }

            //--> To
            $to = array();
            $to = explode(',', $email->to_list);
            //--> Cc
            if (!empty($email->cc_list)) {
                $GLOBALS['mailer']->setCc($email->cc_list);
            }
            //--> Cci
            if (!empty($email->cci_list)) {
                $GLOBALS['mailer']->setBcc($email->cci_list);
            }
            //--> Set subject
            $GLOBALS['mailer']->setSubject($email->email_object);
            //--> Set body: Is Html/raw text ?
            if ($email->is_html == 'Y') {
                $body = str_replace('###', ';', $email->email_body);
                $body = str_replace('___', '--', $body);
                $body = $sendmail_tools->rawToHtml($body);
                $body = "<html><body>".$body."<br></body></html>";

                $dom = new DOMDocument();
                @$dom->loadHTML($body);  // Using @ to hide any parse warning sometimes resulting from markup errors
                $dom->preserveWhiteSpace = false;
                // Here we strip all the img tags in the document
                $images = $dom->getElementsByTagName('img');

                foreach ($images as $key => $tag) {
                    $base64_string = $tag->getAttribute('src');
                    $image = explode(',', $base64_string);
                    if (base64_encode(base64_decode($image[1], true)) === $image[1]) {
                        $imageData = base64_decode($image[1]);

                        $finfo     = finfo_open();
                        $mime_type = finfo_buffer($finfo, $imageData, FILEINFO_MIME_TYPE);
                        $mime_type = explode("/", $mime_type);

                        $nbAttachment = $key+1;
                        $filename     = $nbAttachment."_attachment.".$mime_type[1];
                        file_put_contents($GLOBALS['TmpDirectory']."/".$filename, $imageData);

                        $body = str_replace($base64_string, basename($filename), $body);
                    }
                }

                $GLOBALS['mailer']->setHtml($body, "", $GLOBALS['TmpDirectory']);
            } else {
                $body = $sendmail_tools->htmlToRaw($email->email_body);
                $GLOBALS['mailer']->setText($body);
            }
            //--> Set charset
            $GLOBALS['mailer']->setTextCharset($GLOBALS['charset']);
            $GLOBALS['mailer']->setHtmlCharset($GLOBALS['charset']);
            $GLOBALS['mailer']->setHeadCharset($GLOBALS['charset']);
            
            //--> Set attachments

            //zip M2M
            if ($email->message_exchange_id) {
                $GLOBALS['logger']->write("set zip on message: " . $email->message_exchange_id, 'INFO');

                //Get uri zip
                $query = "SELECT * FROM message_exchange WHERE message_id = ?";
                $smtp = $stmt = Bt_doQuery($GLOBALS['db'], $query, array($email->message_exchange_id));
                $messageExchange = $smtp->fetchObject();

                $docserver     = \Docserver\models\DocserverModel::getById(['id' => $messageExchange->docserver_id]);
                $docserverType = \Docserver\models\DocserverTypeModel::getById(['id' => $docserver['docserver_type_id']]);

                $pathDirectory = str_replace('#', DIRECTORY_SEPARATOR, $messageExchange->path);
                $filePath      = $docserver['path_template'] . $pathDirectory . $messageExchange->filename;
                $fingerprint   = \SrcCore\controllers\StoreController::getFingerPrint([
                    'filePath' => $filePath,
                    'mode'     => $docserverType['fingerprint_mode'],
                ]);

                if ($fingerprint != $messageExchange->fingerprint) {
                    $GLOBALS['logger']->write(_PB_WITH_FINGERPRINT_OF_DOCUMENT.'. ResId master : ' . $email->res_id, 'ERROR');
                }

                //Get file content
                if (is_file($filePath)) {
                    //Filename
                    $resFilename = $sendmail_tools->createFilename($messageExchange->reference, 'zip');
                    $GLOBALS['logger']->write("set attachment filename : " . $resFilename, 'INFO');

                    //File content
                    $file_content = $GLOBALS['mailer']->getFile($filePath);
                    //Add file
                    $GLOBALS['mailer']->addAttachment($file_content, $resFilename);
                }
            } else {
                //Res master
                if ($email->is_res_master_attached == 'Y') {
                    $GLOBALS['logger']->write("set attachment on res master : " . $email->res_id, 'INFO');

                    //Get file from docserver
                    $resFile = $sendmail_tools->getResource($GLOBALS['collections'], $email->coll_id, $email->res_id);
                    //Get file content
                    if (is_file($resFile['file_path'])) {
                        //Filename
                        $resFilename = $sendmail_tools->createFilename($resFile['label'], $resFile['ext']);
                        $GLOBALS['logger']->write("set attachment filename : " . $resFilename, 'INFO');

                        //File content
                        $file_content = $GLOBALS['mailer']->getFile($resFile['file_path']);
                        //Add file
                        $GLOBALS['mailer']->addAttachment($file_content, $resFilename, $resFile['mime_type']);
                    }
                }
                
                //Other version of the document
                if (!empty($email->res_version_id_list)) {
                    $version = explode(',', $email->res_version_id_list);
                    foreach ($version as $version_id) {
                        $GLOBALS['logger']->write("set attachment for version : " . $version_id, 'INFO');
                        $versionFile = $sendmail_tools->getVersion(
                                $GLOBALS['collections'],
                                $email->coll_id,
                                $email->res_id,
                                $version_id
                            );
                        if (is_file($versionFile['file_path'])) {
                            //Filename
                            $versionFilename = $sendmail_tools->createFilename($versionFile['label'], $versionFile['ext']);
                            $GLOBALS['logger']->write("set attachment filename for version : " . $versionFilename, 'INFO');

                            //File content
                            $file_content = $GLOBALS['mailer']->getFile($versionFile['file_path']);
                            //Add file
                            $GLOBALS['mailer']->addAttachment($file_content, $versionFilename, $versionFile['mime_type']);
                        }
                    }
                }
                
                //Res attachment
                if (!empty($email->res_attachment_id_list)) {
                    $attachments = explode(',', $email->res_attachment_id_list);
                    foreach ($attachments as $attachment_id) {
                        $GLOBALS['logger']->write("set attachment on res attachment : " . $attachment_id, 'INFO');
                        $attachmentFile = $sendmail_tools->getAttachment(
                                $email->coll_id,
                                $email->res_id,
                                $attachment_id
                            );
                        if (is_file($attachmentFile['file_path'])) {
                            //Filename
                            $attachmentFilename = $sendmail_tools->createFilename($attachmentFile['label'], $attachmentFile['ext']);
                            $GLOBALS['logger']->write("set attachment filename : " . $attachmentFilename, 'INFO');

                            //File content
                            $file_content = $GLOBALS['mailer']->getFile($attachmentFile['file_path']);
                            //Add file
                            $GLOBALS['mailer']->addAttachment($file_content, $attachmentFilename, $attachmentFile['mime_type']);
                        }
                    }
                }

                //Res version attachment
                if (!empty($email->res_version_att_id_list)) {
                    $attachments = explode(',', $email->res_version_att_id_list);
                    foreach ($attachments as $attachment_id) {
                        $GLOBALS['logger']->write("set attachment version on res attachment : " . $attachment_id, 'INFO');
                        $attachmentFile = $sendmail_tools->getAttachment(
                                $email->coll_id,
                                $email->res_id,
                                $attachment_id,
                                true
                            );
                        if (is_file($attachmentFile['file_path'])) {
                            //Filename
                            $attachmentFilename = $sendmail_tools->createFilename($attachmentFile['label'], $attachmentFile['ext']);
                            $GLOBALS['logger']->write("set attachment version filename : " . $attachmentFilename, 'INFO');

                            //File content
                            $file_content = $GLOBALS['mailer']->getFile($attachmentFile['file_path']);
                            //Add file
                            $GLOBALS['mailer']->addAttachment($file_content, $attachmentFilename, $attachmentFile['mime_type']);
                        }
                    }
                }
                
                //Notes
                if (!empty($email->note_id_list)) {
                    $notes = explode(',', $email->note_id_list);
                    $noteFile = $sendmail_tools->createNotesFile($email->coll_id, $email->res_id, $notes);
                    if (is_file($noteFile['file_path'])) {
                        //File content
                        $file_content = $GLOBALS['mailer']->getFile($noteFile['file_path']);
                        //Add file
                        $GLOBALS['mailer']->addAttachment($file_content, $noteFile['filename'], $noteFile['mime_type']);
                    }
                }
            }

            //Now send the mail
            $GLOBALS['logger']->write("sending e-mail ...", 'INFO');
            $return = $GLOBALS['mailer']->send($to, (string)$mailerParams->type);

            if (($return == 1 && ((string)$mailerParams->type == "smtp" || (string)$mailerParams->type == "mail")) || ($return == 0 && (string)$mailerParams->type == "sendmail")) {
                $exec_result = 'S';
                $GLOBALS['logger']->write("e-mail sent.", 'INFO');
            } else {
                //$GLOBALS['logger']->write("Errors when sending message through SMTP :" . implode(', ', $GLOBALS['mailer']->errors), 'ERROR');
                $GLOBALS['logger']->write("SENDING EMAIL ERROR ! (" . $return[0].")", 'ERROR');
                $GLOBALS['logger']->write("e-mail not sent !", 'ERROR');
                $exec_result = 'E';
                $err++;
                $errTxt = ' (Last Error : '.$return[0].')';

                $query = "INSERT INTO notif_email_stack (sender, recipient, subject, html_body, charset, module) VALUES (?, ?, ?, ?, ?, 'notifications')";

                $html = "Message automatique : <br><br>
                		Le courriel avec l'identifiant ".$email->email_id." dans la table 'sendmail' n'a pas été envoyé. <br>
                		Pour plus d'informations, regardez les logs dans le fichier ".$GLOBALS['maarchDirectory']."/modules/sendmail/batch/".$logFile."<br><br>
                		Répertoire d'installation de l'application : ".$GLOBALS['maarchDirectory']."<br>
                		Fichier de configuration de sendmail : " . $GLOBALS['configFile'];

                $queryMlb = "SELECT alt_identifier FROM mlb_coll_ext WHERE res_id = ? ";
                $stmt = Bt_doQuery($GLOBALS['db'], $queryMlb, array($email->res_id));
                $mlbRecordSet = $stmt->fetchObject();

                $html .= '<br><br>Le courriel a été envoyé depuis le courrier dont le numéro chrono est : ' . $mlbRecordSet->alt_identifier;

                $adminMails = explode(',', $GLOBALS['adminmail']);
                if (!empty($adminMails)) {
                    foreach ($adminMails as $recipient) {
                        if (!empty($recipient)) {
                            Bt_doQuery($GLOBALS['db'], $query, array($emailFrom, $recipient, $GLOBALS['subjectmail'], $html, $GLOBALS['charset']));
                        }
                    }
                }

                if (!empty($userInfo['mail'])) {
                    if (strlen($email->email_object) >= 100) {
                        $objectToSend = mb_substr($email->email_object, 0, 100);
                        $objectToSend = substr($objectToSend, 0, strrpos($objectToSend, ' ')).'...';
                    } else {
                        $objectToSend = $email->email_object;
                    }

                    $bodyMailError = "Message automatique : <br><br>
            						 Votre envoi de courriel dont l'objet est \"". $objectToSend . "\" avec le numéro chrono \"" . $mlbRecordSet->alt_identifier . "\" n'a pas été envoyé. Veuillez réessayer ou contacter votre administreur.";
                    Bt_doQuery($GLOBALS['db'], $query, array($emailFrom, $userInfo['mail'], $GLOBALS['subjectmail'], $bodyMailError, $GLOBALS['charset']));
                }
            }
            //Update emails table
            $query = "UPDATE " . EMAILS_TABLE
                . " SET send_date = CURRENT_TIMESTAMP "
                . ", email_status = ? "
                . " WHERE email_id = ? ";
            $stmt = Bt_doQuery($GLOBALS['db'], $query, array($exec_result, $email->email_id));

            if ($email->message_exchange_id) {
                //Update message table
                $query = "UPDATE message_exchange"
                    . " SET status = ? "
                    . " WHERE message_id = ? ";
                $stmt = Bt_doQuery($GLOBALS['db'], $query, array($exec_result, $email->message_exchange_id));
            }
            $currentEmail++;
            $state = 'SEND_AN_EMAIL';
        } else {
            $state = 'END';
        }
        break;
    }
}

$emailSent = $totalEmailsToProcess - $err;

$GLOBALS['logger']->write($emailSent.' email(s) sent', 'INFO');
$GLOBALS['logger']->write('end of process', 'INFO');

Bt_logInDataBase(
    $totalEmailsToProcess, $err, $emailSent.' email(s) sent'.$errTxt
);
Bt_updateWorkBatch();

//clean tmp directory
echo "clean tmp path ....\n";
array_map('unlink', glob($_SESSION['config']['tmppath']."/*"));

//unlink($GLOBALS['lckFile']);
exit($GLOBALS['exitCode']);
