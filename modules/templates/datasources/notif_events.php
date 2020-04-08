<?php

/*
* @requires vars
* 	$notification 	= notification object
*   $recipient		= user recipient object
*	$events			= array of notif_event objects
*	$maarchUrl
*	$coll_id
*   $res_table
*   $res_view
*
* @return datasources
    [notification]	= one notification array
    [recipient]		= one user recipient array
    [events]		= array of events arrays
*/

$datasources['notification'][0] = (array)$notification;
$datasources['recipient'][0]    = (array)$recipient;
$datasources['events']          = [];

foreach ($events as $event) {
    $datasources['events'][] = (array)$event;
}
