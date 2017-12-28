<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
* @brief Contains the docservers_controler Object
* (herits of the BaseObject class)
*
* @file
* @author Loïc Vinet - Maarch
* @date $date$
* @version $Revision$
* @ingroup core
*/

//Loads the required class
try {
    require_once 'modules/notifications/class/events.php';
    require_once 'modules/notifications/notifications_tables_definition.php';
    require_once 'core/class/ObjectControlerAbstract.php';
    require_once 'core/class/class_db_pdo.php';
} catch (Exception $e) {
    functions::xecho($e->getMessage()) . ' // ';
}

/**
 * Class for controling docservers objects from database
 */
abstract class events_controler_Abstract extends ObjectControler
{
    public function getEventsByNotificationSid($notification_sid)
    {
        $query = "SELECT * FROM " . _NOTIF_EVENT_STACK_TABLE_NAME
            . " WHERE exec_date is NULL "
            . " AND notification_sid = ?";
        $dbConn = new Database();
        $stmt = $dbConn->query($query, array($notification_sid));
        $events = array();
        while ($eventRecordset = $stmt->fetchObject()) {
            $events[] = $eventRecordset;
        }
        return $events;
    }
    
  
    public function wildcard_match($pattern, $str)
    {
        $pattern = '/^' . str_replace(array('%', '\*', '\?', '\[', '\]'), array('.*', '.*', '.', '[', ']+'), preg_quote($pattern)) . '$/is';
        $result = preg_match($pattern, $str);
        return $result;
    }
    
    public function fill_event_stack($event_id, $table_name, $record_id, $user, $info)
    {
        if ($record_id == '') {
            return;
        }
        
        $query = "SELECT * "
            ."FROM " . _NOTIFICATIONS_TABLE_NAME
            ." WHERE is_enabled = 'Y'";
        $dbConn = new Database();
        $stmt = $dbConn->query($query);
        if ($stmt->rowCount() === 0) {
            return;
        }
        
        while ($notification = $stmt->fetchObject()) {
            $event_ids = explode(',', $notification->event_id);
            if ($event_id == $notification->event_id
                || $this->wildcard_match($notification->event_id, $event_id)
                || in_array($event_id, $event_ids)) {
                $notifications[] = $notification;
            }
        }
        if (empty($notifications)) {
            return;
        }
        foreach ($notifications as $notification) {
            $dbConn->query(
                "INSERT INTO "
                    ._NOTIF_EVENT_STACK_TABLE_NAME." ("
                        ."notification_sid, "
                        ."table_name, "
                        ."record_id, "
                        ."user_id, "
                        ."event_info, "
                        ."event_date"
                    .") "
                ."VALUES(?, "
                    ."?, "
                    ."?, "
                    ."?, "
                    ."?, CURRENT_TIMESTAMP)",
                array(
                    $notification->notification_sid,
                    $table_name,
                    $record_id,
                    $user,
                    $info
                )
            );
        }
    }
    
    public function commitEvent($eventId, $result)
    {
        $dbConn = new Database();
        $query = "UPDATE " . _NOTIF_EVENT_STACK_TABLE_NAME
            . " SET exec_date = CURRENT_TIMESTAMP, exec_result = ?"
            . " WHERE event_stack_sid = ?";
        $dbConn->query($query, array($result, $eventId));
    }
}
