<?php
/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

//communs
if (!defined('_NOTIFICATIONS_ERROR')) {
    define('_NOTIFICATIONS_ERROR', 'Fout bij melding:');
}
if (!defined('_NOTIF_ALREADY_EXIST')) {
    define('_NOTIF_ALREADY_EXIST', 'gebruikersnaam bestaat reeds');
}
if (!defined('_NOTIF_DESCRIPTION_TOO_LONG')) {
    define('_NOTIF_DESCRIPTION_TOO_LONG', 'beschrijving te lang');
}
if (!defined('_NOTIF_EVENT_TOO_LONG')) {
    define('_NOTIF_EVENT_TOO_LONG', 'Gebeurtenis in het verkeerde formaat');
}
if (!defined('_NOTIF_MODE_TOO_LONG')) {
    define('_NOTIF_MODE_TOO_LONG', 'Te lange modus');
}
if (!defined('_NOTIF_TEMPLATE_NOT_A_INT')) {
    define('_NOTIF_TEMPLATE_NOT_A_INT', 'gebruikersnaam van template is geen integer');
}
if (!defined('_NOTIF_DIFFUSION_IS_A_INT')) {
    define('_NOTIF_DIFFUSION_IS_A_INT', 'gebruikersnaam van verdelingstype is een integer');
}
if (!defined('_NOTIF_DIFFUSION_PROPERTIES_NOT_INT')) {
    define('_NOTIF_DIFFUSION_PROPERTIES_NOT_INT', 'gebruikersnaam van de verdelingseigenschap is geen integer');
}
if (!defined('_DELETED_NOTIFICATION')) {
    define('_DELETED_NOTIFICATION', 'Melding verwijderd');
}
if (!defined('_NOTIFICATIONS')) {
    define('_NOTIFICATIONS', 'Meldingen');
}
if (!defined('_NOTIFS')) {
    define('_NOTIFS', 'Meldingen');
}
if (!defined('_NOTIF')) {
    define('_NOTIF', 'meld.');
}
if (!defined('_MAIL_TO_PROCESS')) {
    define('_MAIL_TO_PROCESS', 'Brieven voor verwerking');
}
if (!defined('_HELLO')) {
    define('_HELLO', 'Hallo');
}
if (!defined('_THE_MAIL_NUM')) {
    define('_THE_MAIL_NUM', 'brief nr.');
}
if (!defined('_OF_TYPE')) {
    define('_OF_TYPE', 'van type');
}
if (!defined('_OBJECT')) {
    define('_OBJECT', 'onderwerp');
}
if (!defined('_RECEIVED_THE')) {
    define('_RECEIVED_THE', 'ontvangen op');
}
if (!defined('_SEND_BY')) {
    define('_SEND_BY', 'geadresseerd door');
}
if (!defined('_MUST_BE_PROCESSED_BEFORE')) {
    define('_MUST_BE_PROCESSED_BEFORE', 'moet verwerkt worden voor');
}
if (!defined('_ACCESS_TO_MAIL_TO_PROCESS')) {
    define('_ACCESS_TO_MAIL_TO_PROCESS', 'Toegang tot de brief hebben om deze te verwerken');
}
if (!defined('_PROCESS_MAIL')) {
    define('_PROCESS_MAIL', 'Verwerking brief');
}
if (!defined('_USER')) {
    define('_USER', 'Gebruiker');
}
if (!defined('_MAIL')) {
    define('_MAIL', 'Brief');
}
if (!defined('_WAS_SENT_TO')) {
    define('_WAS_SENT_TO', 'werd doorgegeven aan');
}
if (!defined('_SEE_MAIL')) {
    define('_SEE_MAIL', 'Deze brief bekijken');
}
if (!defined('_NO_SENDED')) {
    define('_NO_SENDED', 'De e-mail werd niet verzonden');
}
if (!defined('_ACTIVATE_NOTIFICATION')) {
    define('_ACTIVATE_NOTIFICATION', 'De berichten per mail inschakelen');
}
if (!defined('_MUST_SPECIFY_CONFIG_FILE')) {
    define('_MUST_SPECIFY_CONFIG_FILE', 'U moet het configuratiebestand specificeren');
}
if (!defined('_DU')) {
    define('_DU', 'van');
}
if (!defined('_OF')) {
    define('_OF', 'van');
}
if (!defined('_TO')) {
    define('_TO', 'vanwege');
}
if (!defined('_FOR')) {
    define('_FOR', 'Bestemd voor');
}
if (!defined('_LIST_OF_MAIL_TO_PROCESS')) {
    define('_LIST_OF_MAIL_TO_PROCESS', 'hierbij de lijst van de nieuwe te verwerken brieven');
}
if (!defined('_MAIL_COPIES_TO')) {
    define('_MAIL_COPIES_TO', 'Brieven ter informatie van');
}
if (!defined('_MAIL_COPIES_LIST')) {
    define('_MAIL_COPIES_LIST', 'Hierbij de lijst van de kopieën van de brieven waarvan u de bestemmeling bent');
}
if (!defined('_WHO_MUST_PROCESS_BEFORE')) {
    define('_WHO_MUST_PROCESS_BEFORE', 'die deze moet verwerken voor');
}
if (!defined('_ORIGINAL_PAPERS_ALREADY_SEND')) {
    define('_ORIGINAL_PAPERS_ALREADY_SEND', 'De originele papieren werden u opgestuurd');
}
if (!defined('_WARNING')) {
    define('_WARNING', 'Let op');
}
if (!defined('_YOU_MUST_BE_LOGGED')) {
    define('_YOU_MUST_BE_LOGGED', 'U moet in het softwareprogramma geïdentificeerd zijn vooraleer u tot de brieven toegang heeft');
}
if (!defined('_MAIL_TO_PROCESS_LIST')) {
    define('_MAIL_TO_PROCESS_LIST', 'Lijst van de brieven voor verwerking');
}
if (!defined('_COPIES_MAIL_LIST')) {
    define('_COPIES_MAIL_LIST', 'Lijst van de brieven ter informatie');
}
if (!defined('_BY')) {
    define('_BY', 'door');
}
if (!defined('_DEPARTMENT')) {
    define('_DEPARTMENT', 'de dienst');
}
if (!defined('_FIRST_WARNING')) {
    define('_FIRST_WARNING', 'Eerste herinnering');
}
if (!defined('_FIRST_WARNING_TITLE')) {
    define('_FIRST_WARNING_TITLE', 'Herinnering van de brieven');
}
if (!defined('_THIS_IS_FIRST_WARNING')) {
    define('_THIS_IS_FIRST_WARNING', 'Dit is de eerste herinnering voor de volgende brieven');
}
if (!defined('_MUST_BE_ADDED_TO_PRIORITY')) {
    define('_MUST_BE_ADDED_TO_PRIORITY', 'Gelieve deze brief of brieven aan uw prioritaire verwerkingen toe te voegen');
}
if (!defined('_TO_PROCESS')) {
    define('_TO_PROCESS', 'te verwerken');
}
if (!defined('_SECOND_WARNING')) {
    define('_SECOND_WARNING', 'Herinnering van mijn brieven');
}
if (!defined('_LATE_MAIL_TO_PROCESS')) {
    define('_LATE_MAIL_TO_PROCESS', 'Te behandelen laattijdige brieven');
}
if (!defined('_YOU_ARE_LATE')) {
    define('_YOU_ARE_LATE', 'U heeft een achterstand opgelopen bij de verwerking van de volgende brieven');
}
if (!defined('_WAS_TO_PROCESS_BEFORE')) {
    define('_WAS_TO_PROCESS_BEFORE', 'moest verwerkt worden voor');
}
if (!defined('_PROCESS_THIS_MAIL_QUICKLY')) {
    define('_PROCESS_THIS_MAIL_QUICKLY', 'Gelieve binnen 48 uur een antwoord op te stellen ');
}
if (!defined('_LATE')) {
    define('_LATE', 'Achterstand');
}
if (!defined('_MAIL_TO_CC')) {
    define('_MAIL_TO_CC', 'Brieven in kopie');
}
if (!defined('_FOLLOWING_MAIL_ARE_LATE')) {
    define('_FOLLOWING_MAIL_ARE_LATE', 'De verwerking van de volgende brieven heeft achterstand opgelopen');
}
if (!defined('_WHO_MUST_BE_PROCESSED_BEFORE')) {
    define('_WHO_MUST_BE_PROCESSED_BEFORE', 'die deze moest verwerken voor');
}
if (!defined('_COPY_TITLE')) {
    define('_COPY_TITLE', 'In kopie');
}
if (!defined('_WRONG_FUNCTION_OR_WRONG_PARAMETERS')) {
    define('_WRONG_FUNCTION_OR_WRONG_PARAMETERS', 'Foute functie of foute instelling');
}
if (!defined('_NEW_NOTE_BY_MAIL')) {
    define('_NEW_NOTE_BY_MAIL', 'Nieuwe opmerking voor de brief');
}
if (!defined('_HELLO_NOTE')) {
    define('_HELLO_NOTE', 'Hallo u heeft een nieuwe opmerking voor de brief');
}
if (!defined('_NOTE_BODY')) {
    define('_NOTE_BODY', 'De opmerking is de volgende:');
}
if (!defined('_NOTE_DATE_DETAILS')) {
    define('_NOTE_DATE_DETAILS', 'op');
}
if (!defined('_LINK_TO_MAARCH')) {
    define('_LINK_TO_MAARCH', 'U kunt toegang verkrijgen tot de brief via deze link');
}
if (!defined('_ADMIN_NOTIFICATIONS')) {
    define('_ADMIN_NOTIFICATIONS', 'Meldingen');
}
if (!defined('_MANAGE_NOTIFS')) {
    define('_MANAGE_NOTIFS', 'Meldingen beheren');
}
if (!defined('_MANAGE_NOTIFS_DESC')) {
    define('_MANAGE_NOTIFS_DESC', 'De te communiceren meldingen toevoegen of wijzigen');
}
if (!defined('_TEST_SENDMAIL')) {
    define('_TEST_SENDMAIL', 'De configuratie testen');
}
if (!defined('_TEST_SENDMAIL_DESC')) {
    define('_TEST_SENDMAIL_DESC', 'De instelling van de meldingsmodule controleren');
}
if (!defined('_NOTIFS_LIST')) {
    define('_NOTIFS_LIST', 'Lijst van de meldingen');
}
if (!defined('_THIS_NOTIF')) {
    define('_THIS_NOTIF', 'Deze melding');
}
if (!defined('_IS_UNKNOWN')) {
    define('_IS_UNKNOWN', 'is onbekend');
}
if (!defined('_MODIFY_NOTIF')) {
    define('_MODIFY_NOTIF', 'Melding wijzigen');
}
if (!defined('_ADD_NOTIF')) {
    define('_ADD_NOTIF', 'Melding toevoegen');
}
if (!defined('_NOTIFICATION_ID')) {
    define('_NOTIFICATION_ID', 'Gebruikersnaam van de melding');
}
if (!defined('_DIFFUSION_TYPE')) {
    define('_DIFFUSION_TYPE', 'Verdelingstype');
}
if (!defined('_NOTIFICATION_MODE')) {
    define('_NOTIFICATION_MODE', 'Meldingmodus');
}
if (!defined('_RSS')) {
    define('_RSS', 'RSS stream');
}
if (!defined('_RSS_URL_TEMPLATE')) {
    define('_RSS_URL_TEMPLATE', 'Link van de stream');
}
if (!defined('_SYSTEM_NOTIF')) {
    define('_SYSTEM_NOTIF', 'Melding systeem');
}
if (!defined('_ATTACH_MAIL_FILE')) {
    define('_ATTACH_MAIL_FILE', 'Het document bij de melding voegen');
}
if (!defined('_NEVER')) {
    define('_NEVER', 'Nooit');
}
if (!defined('_NO_ATTACHMENT_WITH_NOTIFICATION')) {
    define('_NO_ATTACHMENT_WITH_NOTIFICATION', 'De documenten worden voor geen enkele gebruiker bij de melding gevoegd');
}
if (!defined('_NOTIFICATIONS_LISTINSTANC_DIFF_TYPE')) {
    define('_NOTIFICATIONS_LISTINSTANC_DIFF_TYPE', 'De meldingse-mails worden aan alle gebruikers van de verdelingslijst verspreid (hoofdbestemmeling en kopie)');
}
if (!defined('_DIFFUSION_LIST')) {
    define('_DIFFUSION_LIST', 'Verdelingslijst');
}
if (!defined('_DEST_USER')) {
    define('_DEST_USER', 'Hoofdbestemmeling');
}
if (!defined('_NOTE_DEST_USER')) {
    define('_NOTE_DEST_USER', 'Hoofdbestemmeling van het brief met voetnoten');
}
if (!defined('_NOTIFICATIONS_DEST_ENTITY_DIFF_TYPE_WITH_STATUS')) {
    define('_NOTIFICATIONS_DEST_ENTITY_DIFF_TYPE_WITH_STATUS', 'Verzending naar de hoofdeenheid van het brief met de status(sen):');
}
if (!defined('_NOTIFICATIONS_COPY_LIST_DIFF_TYPE')) {
    define('_NOTIFICATIONS_COPY_LIST_DIFF_TYPE', 'Verzending naar de gebruikers in kopie van het brief');
}
if (!defined('_COPYLIST')) {
    define('_COPYLIST', 'Gebruikers in kopie');
}
if (!defined('_NOTE_COPY_LIST')) {
    define('_NOTE_COPY_LIST', 'Bestemmelingen in kopie van het brief met voetnoten');
}
if (!defined('_NOTIFICATIONS_GROUP_DIFF_TYPE')) {
    define('_NOTIFICATIONS_GROUP_DIFF_TYPE', 'Verzending naar de gebruikers van de gespecificeerde groep(en)');
}
if (!defined('_NOTIFICATIONS_ENTITY_DIFF_TYPE')) {
    define('_NOTIFICATIONS_ENTITY_DIFF_TYPE', 'Verzending naar de gebruikers gespecificeerde eenhe(i)d(en)');
}
if (!defined('_NOTIFICATIONS_USER_DIFF_TYPE')) {
    define('_NOTIFICATIONS_USER_DIFF_TYPE', 'Verzending naar de gespecificeerde gebruikers');
}
if (!defined('_SELECT_EVENT_TYPE')) {
    define('_SELECT_EVENT_TYPE', '-- De gebeurtenis selecteren --');
}
if (!defined('_SELECT_TEMPLATE')) {
    define('_SELECT_TEMPLATE', '-- Het model selecteren --');
}
if (!defined('_SELECT_DIFFUSION_TYPE')) {
    define('_SELECT_DIFFUSION_TYPE', '-- De verspreiding selecteren --');
}
if (!defined('_NOTIF_ADDED')) {
    define('_NOTIF_ADDED', 'Melding toegevoegd');
}
if (!defined('_NOTIF_DELETED')) {
    define('_NOTIF_DELETED', 'Melding verwijderd');
}
if (!defined('_NOTIF_MODIFIED')) {
    define('_NOTIF_MODIFIED', 'Melding gewijzigd');
}
if (!defined('_NOTIF_EMPTY')) {
    define('_NOTIF_EMPTY', 'Melding leeg');
}
if (!defined('_ALL_NOTIFS')) {
    define('_ALL_NOTIFS', 'Alle');
}
if (!defined('_SYSTEM')) {
    define('_SYSTEM', 'Systeem');
}
if (!defined('_ENTITY')) {
    define('_ENTITY', 'Eenheid');
}
if (!defined('_DEST_ENTITY')) {
    define('_DEST_ENTITY', 'Eenheid van bestemmeling');
}
if (!defined('_NOTIFICATIONS_CONTACT_DIFF_TYPE')) {
    define('_NOTIFICATIONS_CONTACT_DIFF_TYPE', 'Verzending naar de verzender van de brief');
}
if (!defined('_SCHEDULE_NOTIFICATIONS')) {
    define('_SCHEDULE_NOTIFICATIONS', 'De meldingen plannen');
}
if (!defined('_HOUR')) {
    define('_HOUR', 'Uur');
}
if (!defined('_MINUTE')) {
    define('_MINUTE', 'Minuut');
}
if (!defined('_DAY')) {
    define('_DAY', 'Dag');
}
if (!defined('_WEEKDAY')) {
    define('_WEEKDAY', 'Dag van de week');
}
if (!defined('_NOTIF_DESCRIPTION')) {
    define('_NOTIF_DESCRIPTION', 'Beschrijving van de melding');
}
if (!defined('_CRONTAB_SAVED')) {
    define('_CRONTAB_SAVED', 'De geplande taak werd gewijzigd');
}
if (!defined('_MONDAY')) {
    define('_MONDAY', 'Maandag');
}
if (!defined('_TUESDAY')) {
    define('_TUESDAY', 'Dinsdag');
}
if (!defined('_WEDNESDAY')) {
    define('_WEDNESDAY', 'Woensdag');
}
if (!defined('_THURSDAY')) {
    define('_THURSDAY', 'Donderdag');
}
if (!defined('_FRIDAY')) {
    define('_FRIDAY', 'Vrijdag');
}
if (!defined('_SATURDAY')) {
    define('_SATURDAY', 'Zaterdag');
}
if (!defined('_SUNDAY')) {
    define('_SUNDAY', 'Zondag');
}
if (!defined('_HELP_CRON')) {
    define('_HELP_CRON', 'In dit deel kan bepaald worden wanneer de meldingen verzonden zullen worden.<br/><br/>Indien u * kiest in alle dagen per jaar verzonden worden. <br/><br/>Voorbeeld van frequentie:<br/> <br/>14 30 * * * [brief] Nieuwe te behandelen brieven: De melding zal elke dag om 14u30 verzonden worden<br/>9 30 * * Maandag [brief] Nieuwe te behandelen brieven: De melding zal elke maandag om 9u30 verzonden worden');
}
if (!defined('_CHOOSE_NOTIF')) {
    define('_CHOOSE_NOTIF', 'Kies een melding');
}
if (!defined('_NO_NOTIF')) {
    define('_NO_NOTIF', 'Geen melding gepland');
}
if (!defined('_CREATE_NOTIF_SCRIPT')) {
    define('_CREATE_NOTIF_SCRIPT', 'Het script aanmaken');
}
if (!defined('_PB_CRON_COMMAND')) {
    define('_PB_CRON_COMMAND', 'Een geplande taak werd niet correct gewijzigd. Begin opnieuw.');
}
if (!defined('_DEST_USER_VISA')) {
    define('_DEST_USER_VISA', 'GOEDKEURING Verzending aan de gebruiker die moet goedkeuren');
}
if (!defined('_DEST_USER_SIGN')) {
    define('_DEST_USER_SIGN', 'GOEDKEURING Verzending aan de gebruiker die moet tekenen');
}
if (!defined('_NOTIFICATIONS_DEST_USER_VISA_DIFF_TYPE_WITH_STATUS')) {
    define('_NOTIFICATIONS_DEST_USER_VISA_DIFF_TYPE_WITH_STATUS', 'Verzending naar de gebruiker die moet goedkeuren met de status(sen):');
}
if (!defined('_NOTIFICATIONS_DEST_USER_SIGN_DIFF_TYPE_WITH_STATUS')) {
    define('_NOTIFICATIONS_DEST_USER_SIGN_DIFF_TYPE_WITH_STATUS', 'Verzending naar de gebruiker die moet tekenen met de status(sen):');
}
if (!defined('_DEPARTMENT')) {
    define('_DEPARTMENT', 'de eenheid');
}
if (!defined('_NOTE_BODY')) {
    define('_NOTE_BODY', 'De opmerking is de volgende: ');
}
