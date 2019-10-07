<?php
/******************************************************************************
 BATCH BASKET EVENT STACK

 Processes events from table event_stack

 1 - Add events for each basket with notif enabled

 2 - Scan event

 3 - Prepare e-mail and add to e-mail stack

******************************************************************************/

/* begin */
// load the config and prepare to process
include 'load_basket_event_stack.php';
$state = 'LOAD_NOTIFICATIONS';
while ($state != 'END') {
    if (isset($logger)) {
        $logger->write('STATE:'.$state, 'INFO');
    }
    switch ($state) {
        /**********************************************************************/
        /*                          LOAD_NOTIFICATIONS                        */
        /* Load notification defsidentified with notification id              */
        /**********************************************************************/
        case 'LOAD_NOTIFICATIONS':
            $logger->write('Loading configuration for notification id '.$notificationId, 'INFO');
            $notification = $notifications_controler->getByNotificationId($notificationId);
            if ($notification === false) {
                Bt_exitBatch(1, "Notification '".$notificationId."' not found");
            }
            if ($notification->is_enabled === 'N') {
                Bt_exitBatch(100, "Notification '".$notificationId."' is disabled");
            }
            //Attach Mode ?
            if (!empty($notification->attachfor_type) || $notification->attachfor_type != null) {
                $attachMode = true;
                $logger->write('The document will be attached for each recipient', 'INFO');
            } else {
                $attachMode = false;
            }
            $state = 'ADD_EVENTS';
            break;

        /**********************************************************************/
        /*                          LOAD_EVENTS                               */
        /* Checking if the stack has notifications to proceed                 */
        /**********************************************************************/
        case 'ADD_EVENTS':
            $db = new Database();
            $secCtrl = new SecurityControler();
            $entities = new entities();

            $stmt = $db->query("SELECT basket_id, basket_clause FROM baskets WHERE flag_notif = 'Y'");

            while ($line = $stmt->fetchObject()) {
                $logger->write('BASKET: '.$line->basket_id.' in progess ...', 'INFO');
                $exceptUsers[$line->basket_id] = [];
                $stmt2 = $db->query('SELECT group_id FROM groupbasket WHERE basket_id = ?', array($line->basket_id));

                $u = 1;
                while ($line2 = $stmt2->fetchObject()) {
                    if ($notification->diffusion_type == 'groups') {
                        $recipients = array();
                        $recipients = $diffusion_type_controler->getRecipients($notification, '');
                        $aRecipients = [];
                        foreach ($recipients as $itemRecipient) {
                            array_push($aRecipients, $itemRecipient->user_id);
                        }
                        if (empty($aRecipients)) {
                            $aRecipients = '0=1';
                        }
                        $stmt3 = $db->query("SELECT usergroup_content.user_id, users.id, users.status FROM usergroup_content, users WHERE group_id = ? and users.status in ('OK','ABS') and usergroup_content.user_id=users.user_id and users.user_id in (?)", array($line2->group_id, $aRecipients));
                    } else {
                        $stmt3 = $db->query("SELECT usergroup_content.user_id, users.id, users.status FROM usergroup_content, users WHERE group_id = ? and users.status in ('OK','ABS') and usergroup_content.user_id=users.user_id", array($line2->group_id));
                    }

                    $baskets_notif = array();
                    $rowCount3 = $stmt3->rowCount();
                    $logger->write('GROUP: '.$line2->group_id.' ... '.$rowCount3.' user(s) to notify', 'INFO');
                    $z = 1;
                    while ($line3 = $stmt3->fetchObject()) {
                        $real_user_id = '';
                        $whereClause = $secCtrl->process_security_where_clause($line->basket_clause, $line3->user_id);
                        $whereClause = $entities->process_where_clause($whereClause, $line3->user_id);
                        $user_id = $line3->user_id;
                        $group = \Group\models\GroupModel::getByGroupId(['select' => ['id'], 'groupId' => $line2->group_id]);
                        $query = 'SELECT actual_user_id FROM redirected_baskets WHERE owner_user_id = ? AND basket_id = ? AND group_id = ?';
                        $redirStmt = $db->query($query, array($line3->id, $line->basket_id, $group['id']));
                        $queryResult = $redirStmt->fetchObject();
                        if ($queryResult) {
                            $real_user_id = $user_id;
                            $user = \User\models\UserModel::getById(['id' => $queryResult->actual_user_id, 'select' => ['user_id']]);
                            $user_id = $user['user_id'];
                        }


                        $stmt4 = $db->query('SELECT res_id FROM res_view_letterbox '.$whereClause);
                        if (!empty($stmt4)) {
                            $userNbDoc = $stmt4->rowCount();
                            $logger->write($userNbDoc.' document(s) to process for '.$line3->user_id, 'INFO');
                            $i = 1;
                            $info = 'Notification ['.$line->basket_id.'] pour '.$line3->user_id;
                            if (!empty($real_user_id)) {
                                $stmt6 = $db->query('SELECT record_id FROM notif_event_stack WHERE event_info = ? and (user_id = ? OR user_id = ?)', array($info, $line3->user_id, $user_id));
                            } else {
                                $stmt6 = $db->query('SELECT record_id FROM notif_event_stack WHERE event_info = ? and user_id = ?', array($info, $line3->user_id));
                            }
                            $aRecordId = [];
                            while ($line6 = $stmt6->fetchObject()) {
                                $aRecordId[$line6->record_id] = $line6->record_id;
                            }
                            $queryValues = '';
                            while ($line4 = $stmt4->fetchObject()) {
                                echo 'DOCUMENT '.$i.'/'.$userNbDoc.' for USER '.$z.'/'.$rowCount3.' and GROUP '.$u.'/'.$stmt2->rowCount()."\n";
                                if (empty($aRecordId[$line4->res_id])) {
                                    $queryValues .= "('res_letterbox','500','".$line4->res_id."','".$user_id."','".$info."',CURRENT_DATE),";
                                    preg_match_all('#\[(\w+)]#', $info, $result);
                                    $basket_id = $result[1];
                                    if (!in_array($basket_id[0], $baskets_notif)) {
                                        $baskets_notif[] = $basket_id[0];
                                    }
                                }
                                ++$i;
                            }
                            if (!empty($queryValues)) {
                                $db->query('INSERT INTO notif_event_stack (table_name, notification_sid, record_id, user_id, event_info, event_date) VALUES '.substr($queryValues, 0, -1));
                            }
                        }
                        ++$z;
                    }
                    ++$u;
                }
            }
            $logger->write('Scanning events for notification sid '.$notification->notification_sid, 'INFO');
            $events = $events_controler->getEventsByNotificationSid('500');
            $totalEventsToProcess = count($events);
            $currentEvent = 0;
            if ($totalEventsToProcess === 0) {
                Bt_exitBatch(0, 'No event to process');
            }
            $logger->write($totalEventsToProcess.' event(s) to scan', 'INFO');
            $tmpNotifs = array();
            $state = 'SCAN_EVENT';
            break;

        /**********************************************************************/
        /*                  MERGE_EVENT                                       */
        /* Process event stack to get recipients                              */
        /**********************************************************************/
        case 'SCAN_EVENT':
            $i = 1;

            foreach ($events as $event) {
                $logger->write('scanning EVENT : '.$i.'/'.$totalEventsToProcess.' (BASKET => '.$basket_id[0].', DOCUMENT => '.$res_id.', RECIPIENT => '.$user_id.')', 'INFO');
                preg_match_all('#\[(\w+)]#', $event->event_info, $result);
                $basket_id = $result[1];
                //$logger->write("Basket => " .$basket_id[0], 'INFO');

                // Diffusion type specific res_id
                $res_id = false;
                if ($event->table_name == $coll_table || $event->table_name == $coll_view) {
                    $res_id = $event->record_id;
                } else {
                    $res_id = $diffusion_type_controler->getResId($notification, $event);
                }
                $event->res_id = $res_id;

                //$logger->write('Document => ' . $res_id, 'INFO');
                $user_id = $event->user_id;
                //$logger->write('Recipient => ' . $user_id, 'INFO');

                if (!isset($tmpNotifs[$user_id])) {
                    $query = 'SELECT * FROM users WHERE user_id = ?';
                    $arrayPDO = array($user_id);
                    $stmt = $db->query($query, $arrayPDO);
                    $tmpNotifs[$user_id]['recipient'] = $stmt->fetchObject();
                    //$tmpNotifs[$user_id]['recipient'] = $user_id;
                }
                preg_match_all('#\[(\w+)]#', $event->event_info, $result);
                $basket_id = $result[1];
                $tmpNotifs[$user_id]['baskets'][$basket_id[0]]['events'][] = $event;

                ++$i;
            }
            $totalNotificationsToProcess = count($tmpNotifs);
            $logger->write($totalNotificationsToProcess.' notifications to process', 'INFO');

        /**********************************************************************/
        /*                      FILL_EMAIL_STACK                              */
        /* Merge template and fill notif_email_stack                          */
        /**********************************************************************/
            $logger->write('STATE:MERGE NOTIF', 'INFO');
            $i = 1;
            foreach ($tmpNotifs as $user_id => $tmpNotif) {
                foreach ($tmpNotif['baskets'] as $key => $basket_list) {
                    $basketId = $key;
                    $stmt6 = $db->query('SELECT basket_name FROM baskets WHERE basket_id = ?', array($key));
                    $line6 = $stmt6->fetchObject();
                    $subject = $line6->basket_name;

                    // Merge template with data and style
                    $logger->write('generate e-mail '.$i.'/'.$totalNotificationsToProcess.' (TEMPLATE =>'.$notification->template_id.', SUBJECT => '.$subject.', RECIPIENT => '.$user_id.', DOCUMENT(S) => '.count($basket_list['events']), 'INFO');

                    //$logger->write('Merging template #' . $notification->template_id
                    //    . ' to basket '.$subject.' for user ' . $user_id . ' ('.count($basket_list['events']).' documents)', 'INFO');

                    $params = array(
                        'recipient' => $tmpNotif['recipient'],
                        'events' => $basket_list['events'],
                        'notification' => $notification,
                        'maarchUrl' => $maarchUrl,
                        'maarchApps' => $maarchApps,
                        'coll_id' => $coll_id,
                        'res_table' => $coll_table,
                        'res_view' => $coll_view,
                    );
                    $html = $templates_controler->merge($notification->template_id, $params, 'content');

                    if (strlen($html) === 0) {
                        foreach ($tmpNotif['events'] as $event) {
                            $events_controler->commitEvent($event->event_stack_sid, 'FAILED: Error when merging template');
                        }
                        Bt_exitBatch(8, 'Could not merge template with the data');
                    }

                    // Prepare e-mail for stack
                    $sender = (string) $mailerParams->mailfrom;
                    $recipient_mail = $tmpNotif['recipient']->mail;

                    if (!empty($recipient_mail)) {
                        $html = $func->protect_string_db($html, '', 'no');
                        $html = str_replace('&amp;', '&', $html);
                        $html = str_replace('&', '#and#', $html);

                        // Attachments
                        $attachments = array();
                        if ($attachMode) {
                            $logger->write('Adding attachments', 'INFO');
                            foreach ($basket_list['events'] as $event) {
                                // Check if event is related to document in collection
                                if ($event->res_id != '') {
                                    $query = 'SELECT '
                                        .'ds.path_template ,'
                                        .'mlb.path, '
                                        .'mlb.filename '
                                        .'FROM '.$coll_view.' mlb LEFT JOIN docservers ds ON mlb.docserver_id = ds.docserver_id '
                                        .'WHERE mlb.res_id = ?';
                                    $stmt = Bt_doQuery($db, $query, array($event->res_id));
                                    $path_parts = $stmt->fetchObject();
                                    $path = $path_parts->path_template.str_replace('#', '/', $path_parts->path).$path_parts->filename;
                                    $path = str_replace('//', '/', $path);
                                    $path = str_replace('\\', '/', $path);
                                    $attachments[] = $path;
                                }
                            }
                            $logger->write(count($attachments).' attachment(s) added', 'INFO');
                        }
                        if (in_array($user_id, $exceptUsers[$basketId])) {
                            $logger->write('Notification disabled for '.$user_id, 'WARNING');
                        } else {
                            $logger->write('... adding e-mail to email stack', 'INFO');
                            if ($_SESSION['config']['databasetype'] == 'ORACLE') {
                                $query = "DECLARE
                                        vString notif_email_stack.html_body%type;
                                        BEGIN
                                        vString := '".$html."';
                                        INSERT INTO "._NOTIF_EMAIL_STACK_TABLE_NAME."
                                        (sender, recipient, subject, html_body, charset, attachments, module) 
                                        VALUES (?, ?, ?, vString, ?, '".implode(',', $attachments)."', 'notifications');
                                        END;";
                                $arrayPDO = array($sender, $recipient_mail, $subject, $mailerParams->charset);
                            } else {
                                if (count($attachments) > 0) {
                                    $query = 'INSERT INTO '._NOTIF_EMAIL_STACK_TABLE_NAME
                                    .' (sender, recipient, subject, html_body, charset, attachments, module) '
                                    ."VALUES (?, ?, ?, ?, ?, '".implode(',', $attachments)."', 'notifications')";
                                } else {
                                    $query = 'INSERT INTO '._NOTIF_EMAIL_STACK_TABLE_NAME
                                    .' (sender, recipient, subject, html_body, charset, module) '
                                    ."VALUES (?, ?, ?, ?, ?, 'notifications')";
                                }
                                $arrayPDO = array($sender, $recipient_mail, $subject, $html, $mailerParams->charset);
                            }

                            $db->query($query, $arrayPDO);
                        }
                        foreach ($basket_list['events'] as $event) {
                            if (in_array($event->user_id, $exceptUsers[$basketId])) {
                                $events_controler->commitEvent($event->event_stack_sid, 'WARNING : Notification disabled for '.$event->user_id);
                            } else {
                                $events_controler->commitEvent($event->event_stack_sid, 'SUCCESS');
                            }
                        }
                    }
                }
                ++$i;
            }
            $state = 'END';
    }
}

//clean tmp directory
echo "clean tmp path ....\n";
array_map('unlink', glob($_SESSION['config']['tmppath'].'/*.html'));

$logger->write('End of process', 'INFO');
Bt_logInDataBase(
    $totalEventsToProcess,
    0,
    $totalNotificationsToProcess.' notification(s) processed without error'
);

//unlink($GLOBALS['lckFile']);
exit($GLOBALS['exitCode']);
