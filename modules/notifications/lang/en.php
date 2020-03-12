<?php
/*
 *
 *    Copyright 2008,2009 Maarch
 *
 *  This file is part of Maarch Framework.
 *
 *   Maarch Framework is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Maarch Framework is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *    along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
 */

//communs
if (!defined("_NOTIFICATIONS_ERROR"))
    define("_NOTIFICATIONS_ERROR", "Errorr on notification: ");
if (!defined("_NOTIF_ALREADY_EXIST"))
    define("_NOTIF_ALREADY_EXIST", "id already exist");
if (!defined("_NOTIF_DESCRIPTION_TOO_LONG"))
    define("_NOTIF_DESCRIPTION_TOO_LONG", "description too long");
if (!defined("_NOTIF_EVENT_TOO_LONG"))
    define("_NOTIF_EVENT_TOO_LONG", "event_id is too long");
if (!defined("_NOTIF_MODE_TOO_LONG"))
    define("_NOTIF_MODE_TOO_LONG", "notification_mode is too long ");
if (!defined("_NOTIF_TEMPLATE_NOT_A_INT"))
    define("_NOTIF_TEMPLATE_NOT_A_INT", "template_id not a int ");
if (!defined("_NOTIF_DIFFUSION_IS_A_INT"))
    define("_NOTIF_DIFFUSION_IS_A_INT", "diffusion_type is a int ");
if (!defined("_NOTIF_DIFFUSION_PROPERTIES_NOT_INT"))
    define("_NOTIF_DIFFUSION_PROPERTIES_NOT_INT", "diffusion_properties not a int ");
if (!defined("_DELETED_NOTIFICATION"))
    define("_DELETED_NOTIFICATION", "Notification DELETED");
if (!defined("_NOTIFICATIONS"))
    define("_NOTIFICATIONS", "Notifications");
if (!defined("_NOTIFS"))
    define("_NOTIFS", "Notifications");    
if (!defined("_NOTIF"))
    define("_NOTIF", "notif.");
if (!defined("_MAIL_TO_PROCESS"))
    define("_MAIL_TO_PROCESS", "Mail for processing");
if (!defined("_HELLO"))
    define("_HELLO","Hello");
if (!defined("_THE_MAIL_NUM"))
    define("_THE_MAIL_NUM", "Mail number");
if (!defined("_OF_TYPE"))
    define("_OF_TYPE", "Type of");
if (!defined("_OBJECT"))
    define("_OBJECT", "object");
if (!defined("_RECEIVED_THE"))
    define("_RECEIVED_THE", "Received the");
if (!defined("_SEND_BY"))
    define("_SEND_BY", "addressed by");
if (!defined("_MUST_BE_PROCESSED_BEFORE"))
    define("_MUST_BE_PROCESSED_BEFORE", "must be processed before");
if (!defined("_ACCESS_TO_MAIL_TO_PROCESS"))
    define("_ACCESS_TO_MAIL_TO_PROCESS", "Access to the mail for processing");
if (!defined("_PROCESS_MAIL"))
    define("_PROCESS_MAIL", "Mail process");
if (!defined("_USER"))
    define("_USER", "User");
if (!defined("_MAIL"))
    define("_MAIL", "Mail");
if (!defined("_WAS_SENT_TO"))
    define("_WAS_SENT_TO", "was handed to");
if (!defined("_SEE_MAIL"))
    define("_SEE_MAIL", "See this mail");
if (!defined("_NO_SENDED"))
    define("_NO_SENDED"," The mail was not sent");
if (!defined("_ACTIVATE_NOTIFICATION"))
    define("_ACTIVATE_NOTIFICATION","Activate notifications by mail");


// notifs.php
if (!defined("_MUST_SPECIFY_CONFIG_FILE"))
    define("_MUST_SPECIFY_CONFIG_FILE", "You have to specify the configuration file");
if (!defined("_DU"))
    define("_DU", "du");
if (!defined("_OF"))
    define("_OF", "of");
if (!defined("_TO"))
    define("_TO", "from ");
if (!defined("_FOR"))
    define("_FOR", " to ");
if (!defined("_LIST_OF_MAIL_TO_PROCESS"))
    define("_LIST_OF_MAIL_TO_PROCESS", "This is the list of new mails to process");
if (!defined("_MAIL_COPIES_TO"))
    define("_MAIL_COPIES_TO", "Mails for information on");
if (!defined("_MAIL_COPIES_LIST"))
    define("_MAIL_COPIES_LIST", "This is the list of mails copies whose you are recipient");
if (!defined("_WHO_MUST_PROCESS_BEFORE"))
    define("_WHO_MUST_PROCESS_BEFORE", "who have to process it before");
if (!defined("_ORIGINAL_PAPERS_ALREADY_SEND"))
    define("_ORIGINAL_PAPERS_ALREADY_SEND", "The paper originals have been addressed to you");
if (!defined("_WARNING"))
    define("_WARNING", "Warning");
if (!defined("_YOU_MUST_BE_LOGGED"))
    define("_YOU_MUST_BE_LOGGED", "You have to be identified on the software before tempting to access to mails");
if (!defined("_MAIL_TO_PROCESS_LIST"))
    define("_MAIL_TO_PROCESS_LIST","Mails list for processing");
if (!defined("_COPIES_MAIL_LIST"))
    define("_COPIES_MAIL_LIST", "Mails list for information");
if (!defined("_BY"))
    define("_BY", "by");
if (!defined("_DEPARTMENT"))
    define("_DEPARTMENT", "the department");
if (!defined("_"))
    define("_","");

//relance1.php
if (!defined("_FIRST_WARNING"))
    define("_FIRST_WARNING", "First reminder");
if (!defined("_FIRST_WARNING_TITLE"))
    define("_FIRST_WARNING_TITLE", "Reminder on mails");
if (!defined("_THIS_IS_FIRST_WARNING"))
    define("_THIS_IS_FIRST_WARNING", "This is the first reminder for the following mails");
if (!defined("_MUST_BE_ADDED_TO_PRIORITY"))
    define("_MUST_BE_ADDED_TO_PRIORITY", "Please add this or those mails to your urgent processes");
if (!defined("_TO_PROCESS"))
    define("_TO_PROCESS", "To process");

//relance2.php
if (!defined("_SECOND_WARNING"))
    define("_SECOND_WARNING", "Reminder on my mails");
if (!defined("_LATE_MAIL_TO_PROCESS"))
    define("_LATE_MAIL_TO_PROCESS", "Late mails to process");
if (!defined("_YOU_ARE_LATE"))
    define("_YOU_ARE_LATE", "You are late on the following process mails");
if (!defined("_WAS_TO_PROCESS_BEFORE"))
    define("_WAS_TO_PROCESS_BEFORE", "Was to process before");
if (!defined("_PROCESS_THIS_MAIL_QUICKLY"))
    define("_PROCESS_THIS_MAIL_QUICKLY", "Please edit a response under 48 hours");
if (!defined("_LATE"))
    define("_LATE", "Delay");
if (!defined("_MAIL_TO_CC"))
    define("_MAIL_TO_CC", "mails on copies");
if (!defined("_FOLLOWING_MAIL_ARE_LATE"))
    define("_FOLLOWING_MAIL_ARE_LATE", "The process of the following mails has been delayed");
if (!defined("_WHO_MUST_BE_PROCESSED_BEFORE"))
    define("_WHO_MUST_BE_PROCESSED_BEFORE", "who had to process it before");
if (!defined("_COPY_TITLE"))
    define("_COPY_TITLE", "On copy");

//notifications engine
if (!defined("_WRONG_FUNCTION_OR_WRONG_PARAMETERS"))
    define("_WRONG_FUNCTION_OR_WRONG_PARAMETERS","Wrong function or wrong setting");

//annotations
if (!defined("_NEW_NOTE_BY_MAIL"))
    define("_NEW_NOTE_BY_MAIL", "New annotation for the mail");
if (!defined("_HELLO_NOTE"))
    define("_HELLO_NOTE", "Hello, you have a new annotation for the mail");
if (!defined("_NOTE_BODY"))
    define("_NOTE_BODY", "The note is the next one: ");
if (!defined("_NOTE_DATE_DETAILS"))
    define("_NOTE_DATE_DETAILS", "On");
if (!defined("_LINK_TO_MAARCH"))
    define("_LINK_TO_MAARCH", "you can access to the mail from the link");

//v2.0
if (!defined("_ADMIN_NOTIFICATIONS"))
    define("_ADMIN_NOTIFICATIONS", "Notifications");
if (!defined("_MANAGE_NOTIFS"))
    define("_MANAGE_NOTIFS", "Manage the notifications");
if (!defined("_MANAGE_NOTIFS_DESC"))
    define("_MANAGE_NOTIFS_DESC", "Add or modify notifications to inform ");
if (!defined("_TEST_SENDMAIL"))
    define("_TEST_SENDMAIL", "Test the settings");
if (!defined("_TEST_SENDMAIL_DESC"))
    define("_TEST_SENDMAIL_DESC", "Verify the setting of the notification module setting");
if (!defined("_NOTIFS_LIST"))
    define("_NOTIFS_LIST", "Notifications list");
if (!defined("_THIS_NOTIF"))
    define("_THIS_NOTIF", "this notification");
if (!defined("_IS_UNKNOWN"))
    define("_IS_UNKNOWN", "is unknown");
if (!defined("_MODIFY_NOTIF"))
    define("_MODIFY_NOTIF", "Modify notification");
if (!defined("_ADD_NOTIF"))
    define("_ADD_NOTIF", "Add notification");
if (!defined("_NOTIFICATION_ID"))
    define("_NOTIFICATION_ID", "Notification ID");
if (!defined("_DIFFUSION_TYPE"))
    define("_DIFFUSION_TYPE", "Diffusion type");
if (!defined("_NOTIFICATION_MODE"))
    define("_NOTIFICATION_MODE", "Notification mode");
if (!defined("_RSS"))
    define("_RSS", "RSS feed");
if (!defined("_RSS_URL_TEMPLATE"))
    define("_RSS_URL_TEMPLATE", "Feed link");
if (!defined("_SYSTEM_NOTIF"))
    define("_SYSTEM_NOTIF", "System of notification");
if (!defined("_ATTACH_MAIL_FILE"))
    define("_ATTACH_MAIL_FILE", "Attach the file to the notification");
if (!defined("_NEVER"))
    define("_NEVER", "Never");
if (!defined("_NO_ATTACHMENT_WITH_NOTIFICATION"))
    define("_NO_ATTACHMENT_WITH_NOTIFICATION", "The files aren't attached to the notification for no users");

//List of require
if (!defined("_NOTIFICATIONS_LISTINSTANC_DIFF_TYPE"))
    define("_NOTIFICATIONS_LISTINSTANC_DIFF_TYPE", "Notification mails will be sent to all users from the diffusion list (main recipient and copies)");
if (!defined("_DIFFUSION_LIST"))
    define("_DIFFUSION_LIST", "Diffusion list");
if (!defined("_DEST_USER"))
    define("_DEST_USER", "Main recipient");
if (!defined("_NOTE_DEST_USER"))
    define("_NOTE_DEST_USER", "Main recipient of the annotated file");
if (!defined("_NOTIFICATIONS_DEST_ENTITY_DIFF_TYPE_WITH_STATUS"))
    define("_NOTIFICATIONS_DEST_ENTITY_DIFF_TYPE_WITH_STATUS", "Mailing to the main entity department with status:");
if (!defined("_NOTIFICATIONS_COPY_LIST_DIFF_TYPE"))
    define("_NOTIFICATIONS_COPY_LIST_DIFF_TYPE", "Mailing to users on file copy");
if (!defined("_COPYLIST"))
    define("_COPYLIST", "Users on copy");
if (!defined("_NOTE_COPY_LIST"))
    define("_NOTE_COPY_LIST", "Recipient on copy of the annotated file");
if (!defined("_NOTIFICATIONS_GROUP_DIFF_TYPE"))
    define("_NOTIFICATIONS_GROUP_DIFF_TYPE", "Mailing to users from specified group(s)");
if (!defined("_NOTIFICATIONS_ENTITY_DIFF_TYPE"))
    define("_NOTIFICATIONS_ENTITY_DIFF_TYPE", "Sending to users on specified entity(ies)");
if (!defined("_NOTIFICATIONS_USER_DIFF_TYPE"))
    define("_NOTIFICATIONS_USER_DIFF_TYPE", "Mailing to specified users");
if (!defined("_SELECT_EVENT_TYPE"))
    define("_SELECT_EVENT_TYPE", "-- Select event --");
if (!defined("_SELECT_TEMPLATE"))
    define("_SELECT_TEMPLATE", "-- Select the model --");
if (!defined("_SELECT_DIFFUSION_TYPE"))
    define("_SELECT_DIFFUSION_TYPE", "-- Select the diffusion --");
if (!defined("_NOTIF_ADDED"))
    define("_NOTIF_ADDED", "Added notification");
if (!defined("_NOTIF_DELETED"))
    define("_NOTIF_DELETED", "Deleted notification");
if (!defined("_NOTIF_MODIFIED"))
    define("_NOTIF_MODIFIED", "Modified notification");
if (!defined("_NOTIF_EMPTY"))
    define("_NOTIF_EMPTY", "Empty notification");
if (!defined("_ALL_NOTIFS"))
    define("_ALL_NOTIFS", "All");
if (!defined("_SYSTEM"))
    define("_SYSTEM", "System");
if (!defined("_ENTITY"))
    define("_ENTITY", "Entity");
if (!defined("_DEST_ENTITY"))
    define("_DEST_ENTITY", "Recipient entity");
if (!defined("_NOTIFICATIONS_CONTACT_DIFF_TYPE"))
    define("_NOTIFICATIONS_CONTACT_DIFF_TYPE", "Mailing to mail sender");
if (!defined("_SCHEDULE_NOTIFICATIONS"))
    define("_SCHEDULE_NOTIFICATIONS", "Schedule the notifications");
if (!defined("_HOUR"))
    define("_HOUR", "Hour");
if (!defined("_MINUTE"))
    define("_MINUTE", "Minute");
if (!defined("_DAY"))
    define("_DAY", "Day");
if (!defined("_WEEKDAY"))
    define("_WEEKDAY", "Weekday");
if (!defined("_NOTIF_DESCRIPTION"))
    define("_NOTIF_DESCRIPTION", "Notification description");
if (!defined("_CRONTAB_SAVED"))
    define("_CRONTAB_SAVED", "Scheduled tasks was saved");
if (!defined("_MONDAY"))
    define("_MONDAY", "Monday");
if (!defined("_TUESDAY"))
    define("_TUESDAY", "Tuesday");
if (!defined("_WEDNESDAY"))
    define("_WEDNESDAY", "Wednesday");
if (!defined("_THURSDAY"))
    define("_THURSDAY", "Thursday");
if (!defined("_FRIDAY"))
    define("_FRIDAY", "Friday");
if (!defined("_SATURDAY"))
    define("_SATURDAY", "Saturday");
if (!defined("_SUNDAY"))
    define("_SUNDAY", "Sunday");
if (!defined("_HELP_CRON"))
    define("_HELP_CRON", "This part allows to define when the notifications will be sent.
        <br/><br/>If you choose * in all drop-down box, the notification will be sent every minutes, 365 days per year.
        <br/><br/>Example of frequency : 
        <br/>
        <br/>14 30 * * * [mail] New mails to process : the notification will be sent every day at 2:30 pm. 
        <br/>9 30 * * Monday [mail] New mails to process : the notification will be sent every Monday at 9:30 am. ");
if (!defined("_CHOOSE_NOTIF"))
    define("_CHOOSE_NOTIF", "Choose a notification");
if (!defined("_NO_NOTIF"))
    define("_NO_NOTIF", "No scheduled notification");
if (!defined("_CREATE_NOTIF_SCRIPT"))
    define("_CREATE_NOTIF_SCRIPT", "Create the script");
if (!defined("_PB_CRON_COMMAND"))
    define("_PB_CRON_COMMAND", "A scheduled task wasn't correctly modified. Please start again.");

if (!defined("_DEST_USER_VISA"))
    define("_DEST_USER_VISA", "VISA mailing to the user who has to aim");

if (!defined("_DEST_USER_SIGN"))
    define("_DEST_USER_SIGN", "VISA mailing to the user who has to sign");

if (!defined("_NOTIFICATIONS_DEST_USER_VISA_DIFF_TYPE_WITH_STATUS"))
    define("_NOTIFICATIONS_DEST_USER_VISA_DIFF_TYPE_WITH_STATUS", "Mailing to the user who has to aim with the status");

if (!defined("_NOTIFICATIONS_DEST_USER_SIGN_DIFF_TYPE_WITH_STATUS"))
    define("_NOTIFICATIONS_DEST_USER_SIGN_DIFF_TYPE_WITH_STATUS", "Mailing to the user who has to sign with the status");
