<?php
/*
 *
 *  Copyright 2008-2015 Maarch
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


if (!defined("_MEP_VERSION")) define("_MEP_VERSION", "Maarch v17.06");

//if (!defined("_ID_TO_DISPAY")) define("_ID_TO_DISPAY", "res_id"); // value res_id || chrono_number
if (!defined("_ID_TO_DISPLAY")) define("_ID_TO_DISPLAY", "res_id"); // value res_id || chrono_number
/************** Administration **************/
if (!defined("_SVR")) define("_SVR", "SVR");
if (!defined("_SVA")) define("_SVA", "SVA");
if (!defined("_ADDED")) define("_ADDED", "added");
if (!defined("_UPDATED")) define("_UPDATED", "updated");
if (!defined("_DELETED")) define("_DELETED", "deleted");
if (!defined("_PLEASE_CHOOSE_AN_ATTACHMENT")) define("_PLEASE_CHOOSE_AN_ATTACHMENT", "Please add an attachment.");
if (!defined("_ADD_ATTACHMENT_TO_SEND_TO_CONTACT")) define("_ADD_ATTACHMENT_TO_SEND_TO_CONTACT", "Please add an attachment before sending this mail to the contact.");
if (!defined("_SEND_TO_CONTACT_WITH_MANDATORY_ATTACHMENT")) define("_SEND_TO_CONTACT_WITH_MANDATORY_ATTACHMENT", "Send to the contact with a mandatory attachment");
if (!defined("_SEND_ATTACHMENTS_TO_CONTACT")) define("_SEND_ATTACHMENTS_TO_CONTACT", "Send to the contact");
if (!defined("_PROCESSING_MODE")) define("_PROCESSING_MODE", "Handling mode");
if (!defined("_VIEW_LAST_COURRIERS")) define("_VIEW_LAST_COURRIERS", "My last mails/documents");
if (!defined("_ADMIN_USERS"))    define("_ADMIN_USERS", "Users");
if (!defined("_ADMIN_DOCSERVERS"))    define("_ADMIN_DOCSERVERS", "Storage zones");
if (!defined("_ADMIN_USERS_DESC"))    define("_ADMIN_USERS_DESC", "Add, suspend or modify users profiles. Place the users in their affiliation groups and define their primary group.");
if (!defined("_ADMIN_DOCSERVERS_DESC"))    define("_ADMIN_DOCSERVERS_DESC", "Add, suspend or modify storage zones. Put the storage zones by kind of affiliations and define their primary group.");
if (!defined("_ADMIN_GROUPS"))    define("_ADMIN_GROUPS", "users groups");
if (!defined("_ADMIN_GROUPS_DESC"))    define("_ADMIN_GROUPS_DESC", "Add, suspend or modify users groups. Set privileges or authorization to access resources.");
if (!defined("_ADMIN_ARCHITECTURE"))    define("_ADMIN_ARCHITECTURE", "File plan");
if (!defined("_ADMIN_ARCHITECTURE_DESC"))    define("_ADMIN_ARCHITECTURE_DESC", "Define the intern layout of a file (file/ sub-file/ document type). For each, define the index list to enter, and their mandatory character for the file completeness.");
if (!defined("_VIEW_HISTORY"))    define("_VIEW_HISTORY", "History");
if (!defined("_VIEW_HISTORY_BATCH"))    define("_VIEW_HISTORY_BATCH", "Batch history");
if (!defined("_VIEW_HISTORY_DESC"))    define("_VIEW_HISTORY_DESC", "Read the events history linked to the utilisation of Maarch GED.");
if (!defined("_VIEW_FULL_HISTORY_DESC"))    define("_VIEW_FULL_HISTORY_DESC", "Read the full events history linked to the utilisation of Maarch GED.");
if (!defined("_VIEW_HISTORY_BATCH_DESC"))    define("_VIEW_HISTORY_BATCH_DESC", "Read batch history");
if (!defined("_ADMIN_MODULES"))    define("_ADMIN_MODULES", "Manage the modules");
if (!defined("_ADMIN_SERVICE"))    define("_ADMIN_SERVICE", "Administration department");
if (!defined("_XML_PARAM_SERVICE_DESC"))    define("_XML_PARAM_SERVICE_DESC", "Configuration XML departments view");
if (!defined("_XML_PARAM_SERVICE"))    define("_XML_PARAM_SERVICE", "Configuration XML departments view");
if (!defined("_MODULES_SERVICES"))    define("_MODULES_SERVICES", "Departments defined by the modules");
if (!defined("_APPS_SERVICES"))    define("_APPS_SERVICES", "Departments defined by the application");
if (!defined("_ADMIN_STATUS_DESC"))    define("_ADMIN_STATUS_DESC", "Create or modify status.");
if (!defined("_ADMIN_ACTIONS_DESC"))    define("_ADMIN_ACTIONS_DESC", "Create or modify actions.");
if (!defined("_ADMIN_SERVICES_UNKNOWN"))    define("_ADMIN_SERVICES_UNKNOWN", "Unknown administration department");
if (!defined("_NO_RIGHTS_ON"))    define("_NO_RIGHTS_ON", "No right on");
if (!defined("_NO_LABEL_FOUND"))    define("_NO_LABEL_FOUND", "No found label for this department");

if (!defined("_FOLDERTYPES_LIST"))    define("_FOLDERTYPES_LIST", "folder types list");
if (!defined("_SELECTED_FOLDERTYPES"))    define("_SELECTED_FOLDERTYPES", "Selected folder types");
if (!defined("_FOLDERTYPE_ADDED"))    define("_FOLDERTYPE_ADDED", "Added new file");
if (!defined("_FOLDERTYPE_DELETION"))    define("_FOLDERTYPE_DELETION", "Deleted folder");
if (!defined("_VERSION_BASE_AND_XML_BASEVERSION_NOT_MATCH"))    define("_VERSION_BASE_AND_XML_BASEVERSION_NOT_MATCH", "Warning : Maarch's datas model must be updated...");


/*********************** communs ***********************************/
if (!defined("_MODE"))    define("_MODE", "Mode");
/************** Lists **************/
if (!defined("_GO_TO_PAGE"))    define("_GO_TO_PAGE", "Go on page");
if (!defined("_NEXT"))    define("_NEXT", "Next");
if (!defined("_PREVIOUS"))    define("_PREVIOUS", "Previous");
if (!defined("_ALPHABETICAL_LIST"))    define("_ALPHABETICAL_LIST", "Alphabetical list");
if (!defined("_ASC_SORT"))    define("_ASC_SORT", "Ancestor sorting");
if (!defined("_DESC_SORT"))    define("_DESC_SORT", "Descendent sorting");
if (!defined("_ACCESS_LIST_STANDARD"))    define("_ACCESS_LIST_STANDARD", " Simple lists display");
if (!defined("_ACCESS_LIST_EXTEND"))    define("_ACCESS_LIST_EXTEND", " Extended lists display");
if (!defined("_DISPLAY"))    define("_DISPLAY", "Display ");

/************** Actions **************/
if (!defined("_DELETE"))    define("_DELETE", "Delete");
if (!defined("_ADD"))    define("_ADD", "Add");
if (!defined("_REMOVE"))    define("_REMOVE", "Remove");
if (!defined("_MODIFY"))    define("_MODIFY", "Modify");
if (!defined("_SUSPEND"))    define("_SUSPEND", "Suspend");
if (!defined("_AUTHORIZE"))    define("_AUTHORIZE", "Authorize");
if (!defined("_CHOOSE"))    define("_CHOOSE", "Choose");
if (!defined("_SEND"))    define("_SEND", "Send");
if (!defined("_SEARCH"))    define("_SEARCH", "Search");
if (!defined("_RESET"))    define("_RESET", "Reset");
if (!defined("_VALIDATE"))    define("_VALIDATE", "Validate");
if (!defined("_CANCEL"))    define("_CANCEL", "Cancel");
if (!defined("_ADDITION"))    define("_ADDITION", "Addition");
if (!defined("_MODIFICATION"))    define("_MODIFICATION", "Modification");
if (!defined("_DIFFUSION"))    define("_DIFFUSION", "Diffusion");
if (!defined("_DELETION"))    define("_DELETION", "Deletion");
if (!defined("_SUSPENSION"))    define("_SUSPENSION", "Suspension");
if (!defined("_VALIDATION"))    define("_VALIDATION", "Validation");
if (!defined("_REDIRECTION"))    define("_REDIRECTION", "Redirection");
if (!defined("_DUPLICATION"))    define("_DUPLICATION", "Duplication");
if (!defined("_PROPOSITION"))    define("_PROPOSITION", "Proposition");
if (!defined("_ERR"))    define("_ERR", "Error");
if (!defined("_CLOSE"))    define("_CLOSE", "Close");
if (!defined("_CLOSE_WINDOW"))    define("_CLOSE_WINDOW", "Close the window");
if (!defined("_DIFFUSE"))    define("_DIFFUSE", "Diffuse");
if (!defined("_DOWN"))    define("_DOWN", "Go down");
if (!defined("_UP"))    define("_UP", "Go up");
if (!defined("_REDIRECT"))    define("_REDIRECT", "Redirect");
if (!defined("_DELETED"))    define("_DELETED", "Deleted");
if (!defined("_CONTINUE"))    define("_CONTINUE", "Continue");
if (!defined("_VIEW"))    define("_VIEW","View");
if (!defined("_CHOOSE_ACTION"))    define("_CHOOSE_ACTION", "Choose an action");
if (!defined("_ACTIONS"))    define("_ACTIONS", "Action(s)");
if (!defined("_ACTION_PAGE"))    define("_ACTION_PAGE", "Action result page");
if (!defined("_DO_NOT_MODIFY_UNLESS_EXPERT"))    define("_DO_NOT_MODIFY_UNLESS_EXPERT", " Do not modify this section, except if you know what you're doing. A wrong setting can lead on application dysfunctions!");
if (!defined("_INFOS_ACTIONS"))    define("_INFOS_ACTIONS", "You have to choose one status and/or one script at least.");
if (!defined("_SAVE_CONFIRM"))    define("_SAVE_CONFIRM", "Record confirmation");
if (!defined("_SAVED_ALREADY_EXIST"))    define("_SAVED_ALREADY_EXIST", "Recording already exists");
if (!defined("_OK_FOR_CONFIRM"))    define("_OK_FOR_CONFIRM", "Do you confirm the recording?");
if (!defined("_INCLUDE_SUB_ENTITIES")) define("_INCLUDE_SUB_ENTITIES","Include sub-entities");
if (!defined("_GRAPHICS_REPORTS")) define("_GRAPHICS_REPORTS","Graphics mode enabled");

/************** Forms And lists **************/
if (!defined("_ID"))    define("_ID", "ID");
if (!defined("_PASSWORD"))    define("_PASSWORD", "Password");
if (!defined("_GROUP"))    define("_GROUP", "Group");
if (!defined("_USER"))    define("_USER", "User");
if (!defined("_SENDER"))    define("_SENDER", "Sender");
if (!defined("_DESC"))    define("_DESC", "Description");
if (!defined("_LASTNAME"))    define("_LASTNAME", "Last name");
if (!defined("_THE_LASTNAME"))    define("_THE_LASTNAME", "The last name");
if (!defined("_THE_FIRSTNAME"))    define("_THE_FIRSTNAME", "The first name");
if (!defined("_THE_ID"))    define("_THE_ID", "The ID");
if (!defined("_FIRSTNAME"))    define("_FIRSTNAME", "First name");
if (!defined("_INITIALS"))
    define("_INITIALS", "Initials");
if (!defined("_STATUS"))    define("_STATUS", "Status");
if (!defined("_DEPARTMENT"))    define("_DEPARTMENT", "Department");
if (!defined("_FUNCTION"))    define("_FUNCTION", "Function");
if (!defined("_NUMBER")) define("_NUMBER", "Number");
if (!defined("_PHONE_NUMBER"))    define("_PHONE_NUMBER", "Phone number");
if (!defined("_MAIL"))    define("_MAIL", "Mail");
if (!defined("_EMAIL")) define("_EMAIL", "Email");
if (!defined("_DOCTYPE"))    define("_DOCTYPE", "Document type");
if (!defined("_DOCTYPES_MAIL"))    define("_DOCTYPES_MAIL", "Mail type");
if (!defined("_TYPE"))    define("_TYPE", "Type");
if (!defined("_WARNING_MESSAGE_DEL_TYPE"))
    define("_WARNING_MESSAGE_DEL_TYPE", "Warning :<br> The deletion of a document type leads to documents reallocation to a new type.");
if (!defined("_WARNING_MESSAGE_DEL_GROUP"))
    define("_WARNING_MESSAGE_DEL_GROUP", "Warning :<br>There are users associated to this group. Choose a replacement group:");
if (!defined("_WARNING_MESSAGE_DEL_USER"))
    define("_WARNING_MESSAGE_DEL_USER", "Warning :<br> There are diffusion lists associated to this user. Choose a replacement user:");
if (!defined("_INFO_MESSAGE_UPDATE_USER"))
    define("_INFO_MESSAGE_UPDATE_USER", "In order to keep confidentiality of documents in progress of current user's entity, you can replace him with another user");
if (!defined("_WARNING_MESSAGE_UPDATE_USER"))
    define("_WARNING_MESSAGE_UPDATE_USER", "If you choose \"NO REPLACEMENT\" and current user is in dest, he can't be removed !");
if (!defined("_MESSAGE_REAFFECT_USER_LISTMODEL"))
    define("_MESSAGE_REAFFECT_USER_LISTMODEL", "Include user reassignment in list models");
if (!defined("_USERS_IN_GROUPS"))
    define("_USERS_IN_GROUPS", "Users in the group");
if (!defined("_LISTE_DIFFUSION_IN_USER")) define("_LISTE_DIFFUSION_IN_USER", "Diffusion list(s) for the user");
if (!defined("_NO_REPLACEMENT"))    define("_NO_REPLACEMENT", "NO REPLACEMENT");
if (!defined("_DOCS_IN_DOCTYPES"))    define("_DOCS_IN_DOCTYPES", "Document(s) from this type");
if (!defined("_CHOOSE_REPLACEMENT_DOCTYPES"))    define("_CHOOSE_REPLACEMENT_DOCTYPES", "Choose a replacement document type");
if (!defined("_SELECT_ALL"))    define("_SELECT_ALL", "Select all");
if (!defined("_DATE"))    define("_DATE", "Date");
if (!defined("_ACTION"))    define("_ACTION", "Action");
if (!defined("_COMMENTS"))    define("_COMMENTS", "Comments");
if (!defined("_ENABLED"))    define("_ENABLED", "Authorized");
if (!defined("_DISABLED"))    define("_DISABLED", "Suspended");
if (!defined("_NOT_ENABLED"))    define("_NOT_ENABLED", "Suspended");
if (!defined("_RESSOURCES_COLLECTION"))    define("_RESSOURCES_COLLECTION","Documentary collection");
if (!defined("_RECIPIENT"))    define("_RECIPIENT", "Recipient");
if (!defined("_START"))    define("_START", "Start");
if (!defined("_END"))    define("_END", "End");

if (!defined("_KEYWORD"))    define("_KEYWORD", "Keyword");

if (!defined("_SYSTEM_PARAMETERS"))    define("_SYSTEM_PARAMETERS", "System parameters ");

if (!defined("_NO_KEYWORD"))    define("_NO_KEYWORD", "No keyword");

if (!defined("_TO_VALIDATE"))    define("_TO_VALIDATE", "To validate");

if (!defined("_INDEXING"))    define("_INDEXING", "Indexing");

if (!defined("_QUALIFY"))    define("_QUALIFY", "Qualify - title");


/************** Messages pop up **************/
if (!defined("_REALLY_SUSPEND")) define("_REALLY_SUSPEND", "Do you really want to suspend ");
if (!defined("_REALLY_AUTHORIZE")) define("_REALLY_AUTHORIZE", "Do you really want to authorize ");
if (!defined("_REALLY_DELETE")) define("_REALLY_DELETE", "Do you really want to deleted ");
if (!defined("_REALLY_CONTINUE")) define("_REALLY_CONTINUE", "Really continue ");
if (!defined("_DEFINITIVE_ACTION")) define("_DEFINITIVE_ACTION", "This action is definitive");
if (!defined("_AND")) define("_AND", " and ");
if (!defined("_PLEASE_CHECK_LISTDIFF")) define("_PLEASE_CHECK_LISTDIFF", " is on the diffusion list(s). Please change the recipient in the diffusion list(s) of");
if (!defined("_THE_USER_JS")) define("_THE_USER_JS", "The user ");


/************** Divers **************/
if (!defined("_YES")) define("_YES", "Yes");
if (!defined("_NO")) define("_NO", "No");
if (!defined("_UNKNOWN")) define("_UNKNOWN", "Unknown");
if (!defined("_SINCE")) define("_SINCE","Since");
if (!defined("_FOR")) define("_FOR","To");
if (!defined("_HELLO")) define("_HELLO","Hello");
if (!defined("_OBJECT")) define("_OBJECT","Object");
if (!defined("_BACK")) define("_BACK","Back");
if (!defined("_FORMAT")) define("_FORMAT","Format");
if (!defined("_SIZE")) define("_SIZE","Size");
if (!defined("_DOC")) define("_DOC", "Document ");
if (!defined("_THE_DOC")) define("_THE_DOC", "The document");
if (!defined("_BYTES")) define("_BYTES", "Bytes");
if (!defined("_OR")) define("_OR", "Or");
if (!defined("_NOT_AVAILABLE")) define("_NOT_AVAILABLE", "Unavailable");
if (!defined("_SELECTION")) define("_SELECTION", "Selection");
if (!defined("_AND")) define("_AND", " And ");
if (!defined("_FILE")) define("_FILE","File");
if (!defined("_UNTIL")) define("_UNTIL", "To the");
if (!defined("_ALL")) define("_ALL", "All");

//class functions
if (!defined("_SECOND")) define("_SECOND", "second");
if (!defined("_SECONDS")) define("_SECONDS", "seconds");
if (!defined("_PAGE_GENERATED_IN")) define("_PAGE_GENERATED_IN", "Generated page in");
if (!defined("_IS_EMPTY")) define("_IS_EMPTY", "is empty");
if (!defined("_MUST_MAKE_AT_LEAST")) define("_MUST_MAKE_AT_LEAST", "Must do at least");
if (!defined("_CHARACTER")) define("_CHARACTER", "character");
if (!defined("_CHARACTERS")) define("_CHARACTERS", "characters");
if (!defined("MUST_BE_LESS_THAN")) define("MUST_BE_LESS_THAN", "Must be less than");
if (!defined("_WRONG_FORMAT")) define("_WRONG_FORMAT", "Isn't on the right format");
if (!defined("_WELCOME")) define("_WELCOME", "Welcome to Maarch !");
if (!defined("_WELCOME_TITLE")) define("_WELCOME_TITLE", "Home");
if (!defined("_HELP")) define("_HELP", "Help");
if (!defined("_SEARCH_ADV_SHORT")) define("_SEARCH_ADV_SHORT", "Advanced search");
if (!defined("_SEARCH_SCOPE")) define("_SEARCH_SCOPE", "Search impact");
if (!defined("_SEARCH_SCOPE_HELP")) define("_SEARCH_SCOPE_HELP", "Expand the search to baskets; authorizes the actions if a specific basket is selected");
if (!defined("_RESULTS")) define("_RESULTS", "Result(s)");
if (!defined("_USERS_LIST_SHORT")) define("_USERS_LIST_SHORT", "Users list");
if (!defined("_MODELS_LIST_SHORT")) define("_MODELS_LIST_SHORT", "Models list");
if (!defined("_GROUPS_LIST_SHORT")) define("_GROUPS_LIST_SHORT", "Groups list");
if (!defined("_DEPARTMENTS_LIST_SHORT")) define("_DEPARTMENTS_LIST_SHORT", "Departments list");
if (!defined("_BITMASK")) define("_BITMASK", "Parameter Bit mask");
if (!defined("_DOCTYPES_LIST_SHORT")) define("_DOCTYPES_LIST_SHORT", "Types list");
if (!defined("_BAD_MONTH_FORMAT")) define("_BAD_MONTH_FORMAT", "The month is incorrect");
if (!defined("_BAD_DAY_FORMAT")) define("_BAD_DAY_FORMAT", "The day is incorrect");
if (!defined("_BAD_YEAR_FORMAT")) define("_BAD_YEAR_FORMAT", "The year is incorrect");
if (!defined("_BAD_FEBRUARY")) define("_BAD_FEBRUARY", "February can only contain 29 days maximum");
if (!defined("_CHAPTER_SHORT")) define("_CHAPTER_SHORT", "Chap ");
if (!defined("_PROCESS_SHORT")) define("_PROCESS_SHORT", "Processing");
if (!defined("_CARD")) define("_CARD", "Card");

/************************* First login ***********************************/
if (!defined("_MODIFICATION_PSW")) define("_MODIFICATION_PSW", "Password modification");
if (!defined("_YOUR_FIRST_CONNEXION")) define("_YOUR_FIRST_CONNEXION", " Welcome on Maarch !<br/> This is your first connection");
if (!defined("_PLEASE_CHANGE_PSW")) define("_PLEASE_CHANGE_PSW", " Please define your password");
if (!defined("_ASKED_ONLY_ONCE")) define("_ASKED_ONLY_ONCE", "This will be asked once");
if (!defined("_PSW_REINI")) define("_PSW_REINI", "Your password has been rebooted. Please define a new one.");
if (!defined("_FIRST_CONN")) define("_FIRST_CONN", "First connection");
if (!defined("_LOGIN")) define("_LOGIN", "Connection");
if (!defined("_RELOGIN")) define("_RELOGIN", "Re connection");
if (!defined("_RA_CODE")) define("_RA_CODE", "Complementary access code");

/*************************  index  page***********************************/
if (!defined("_LOGO_ALT")) define("_LOGO_ALT", "Back to home");
if (!defined("_LOGOUT")) define("_LOGOUT", "Disconnection");
if (!defined("_MENU")) define("_MENU", "Menu");
if (!defined("_ADMIN")) define("_ADMIN", "Administration");
if (!defined("_SUMMARY")) define("_SUMMARY", "Summary");
if (!defined("_MANAGE_REL_MODEL")) define("_MANAGE_REL_MODEL", "Manage the reminder model");
if (!defined("_MANAGE_DOCTYPES")) define("_MANAGE_DOCTYPES", "Manage the document types");
if (!defined("_MANAGE_DOCTYPES_DESC")) define("_MANAGE_DOCTYPES_DESC", "Administer the document types. Document types are linked to a documentary collection. For each type, you can define the indices to enter and those which are mandatory.");
if (!defined("_VIEW_HISTORY2")) define("_VIEW_HISTORY2", "History view");
if (!defined("_VIEW_HISTORY_BATCH2")) define("_VIEW_HISTORY_BATCH2", "Batch history view");
if (!defined("_INDEX_FILE")) define("_INDEX_FILE", "Index a folder");
if (!defined("_WORDING")) define("_WORDING", "Wording");
if (!defined("_COLLECTION")) define("_COLLECTION", "Collection");
if (!defined("_VIEW_TREE_DOCTYPES")) define("_VIEW_TREE_DOCTYPES", "File plan tree view");
if (!defined("_VIEW_TREE_DOCTYPES_DESC")) define("_VIEW_TREE_DOCTYPES_DESC", "See file plan tree view (Folders types, folder, sub-folder, and document types)");

/************************* Administration ***********************************/

/**************Summary**************/
if (!defined("_MANAGE_GROUPS_APP")) define("_MANAGE_GROUPS_APP", "Manage application groups");
if (!defined("_MANAGE_USERS_APP")) define("_MANAGE_USERS_APP", "Manage application users");
if (!defined("_MANAGE_DOCTYPES_APP")) define("_MANAGE_DOCTYPES_APP", "Manage application document types");
if (!defined("_MANAGE_ARCHI_APP")) define("_MANAGE_ARCHI_APP", "Manage application doc types structure");
if (!defined("_HISTORY_EXPLANATION")) define("_HISTORY_EXPLANATION", "Watch on modifications, deletions and additions in the application");
if (!defined("_ARCHI_EXP")) define("_ARCHI_EXP", "Folders, sub-folders, document types");


/************** Groups : List + Forms **************/

if (!defined("_GROUPS_LIST")) define("_GROUPS_LIST", "Groups list");
if (!defined("_ADMIN_GROUP")) define("_ADMIN_GROUP", "Administration group");
if (!defined("_ADD_GROUP")) define("_ADD_GROUP", "Add a group");
if (!defined("_ALL_GROUPS")) define("_ALL_GROUPS", "All groups");
if (!defined("_GROUPS")) define("_GROUPS", "groups");

if (!defined("_GROUP_ADDITION")) define("_GROUP_ADDITION", "group's addition");
if (!defined("_GROUP_MODIFICATION")) define("_GROUP_MODIFICATION", "Group modification");
if (!defined("_SEE_GROUP_MEMBERS")) define("_SEE_GROUP_MEMBERS", "See this group's diffusion list");
if (!defined("_SEE_DOCSERVERS_")) define("_SEE_DOCSERVERS_", "See the doc servers list of this type");
if (!defined("_SEE_DOCSERVERS_LOCATION")) define("_SEE_DOCSERVERS_LOCATION", "See the doc servers of this place");
if (!defined("_OTHER_RIGHTS")) define("_OTHER_RIGHTS", "Other rights");
if (!defined("_MODIFY_GROUP")) define("_MODIFY_GROUP", "Accept changes");
if (!defined("_THE_GROUP")) define("_THE_GROUP", "The group");
if (!defined("_HAS_NO_SECURITY")) define("_HAS_NO_SECURITY", "Has no defined security");

if (!defined("_DEFINE_A_GRANT")) define("_DEFINE_A_GRANT", "Define one access at least");
if (!defined("_MANAGE_RIGHTS")) define("_MANAGE_RIGHTS", "This group has access to the following resources");
if (!defined("_TABLE")) define("_TABLE", "Table");
if (!defined("_WHERE_CLAUSE")) define("_WHERE_CLAUSE", "Where clause");
if (!defined("_INSERT")) define("_INSERT", "Insert");
if (!defined("_UPDATE")) define("_UPDATE", "Update");
if (!defined("_REMOVE_ACCESS")) define("_REMOVE_ACCESS", "remove access");
if (!defined("_MODIFY_ACCESS")) define("_MODIFY_ACCESS", "Modify access");
if (!defined("_UPDATE_RIGHTS")) define("_UPDATE_RIGHTS", "Rights update");
if (!defined("_ADD_GRANT")) define("_ADD_GRANT", "Add access");
if (!defined("_UP_GRANT")) define("_UP_GRANT", "Modify access");
if (!defined("_USERS_LIST_IN_GROUP")) define("_USERS_LIST_IN_GROUP", "Users list of the group");

if (!defined("_CHOOSE_GROUP_ADMIN")) define("_CHOOSE_GROUP_ADMIN", "Choose a group");

/************** Users : Lists + Forms **************/

if (!defined("_USERS_LIST")) define("_USERS_LIST", "Users list");
if (!defined("_ADD_USER")) define("_ADD_USER", "Add a user");
if (!defined("_ALL_USERS")) define("_ALL_USERS", "All the users");
if (!defined("_USERS")) define("_USERS", "users");
if (!defined("_USER_ADDITION")) define("_USER_ADDITION", "Addition of one user");
if (!defined("_USER_MODIFICATION")) define("_USER_MODIFICATION", "Modification of one user");
if (!defined("_MODIFY_USER")) define("_MODIFY_USER", "Modify the user");

if (!defined("_NOTES")) define("_NOTES", "Notes");
if (!defined("_NOTE1")) define("_NOTE1", "The mandatory fields are marked by a red asterisk ");
if (!defined("_NOTE2")) define("_NOTE2", "The primary group is mandatory");
if (!defined("_NOTE3")) define("_NOTE3", "The first selected group will be the primary group");
if (!defined("_USER_GROUPS_TITLE")) define("_USER_GROUPS_TITLE", "The user belongs to the following groups");
if (!defined("_USER_ENTITIES_TITLE")) define("_USER_ENTITIES_TITLE", "The user belongs to the following departments");
if (!defined("_DELETE_GROUPS")) define("_DELETE_GROUPS", "Delete the group(s)");
if (!defined("_ADD_TO_GROUP")) define("_ADD_TO_GROUP", "Add to a group");
if (!defined("_CHOOSE_PRIMARY_GROUP")) define("_CHOOSE_PRIMARY_GROUP", "Choose as primary group");
if (!defined("_USER_BELONGS_NO_GROUP")) define("_USER_BELONGS_NO_GROUP", "The user doesn't belong to any group");
if (!defined("_USER_BELONGS_NO_ENTITY")) define("_USER_BELONGS_NO_ENTITY", "The user doesn't belong to any department");
if (!defined("_CHOOSE_ONE_GROUP")) define("_CHOOSE_ONE_GROUP", "Choose one group at least");
if (!defined("_CHOOSE_GROUP")) define("_CHOOSE_GROUP", "Choose a group");
if (!defined("_ROLE")) define("_ROLE", "Role");

if (!defined("_THE_PSW")) define("_THE_PSW", "The password");
if (!defined("_THE_PSW_VALIDATION")) define("_THE_PSW_VALIDATION", "The password validation");
if (!defined("_MODIFICATION_PSW_SNTE")) define("_MODIFICATION_PSW_SNTE", "To modify your password, please confirm it.");
if (!defined("_USER_ACCESS_DEPARTMENT")) define("_USER_ACCESS_DEPARTMENT", "The user has access to the following departments");
if (!defined("_FIRST_PSW")) define("_FIRST_PSW", "The new password ");
if (!defined("_SECOND_PSW")) define("_SECOND_PSW", "The second password ");

if (!defined("_PASSWORD_MODIFICATION")) define("_PASSWORD_MODIFICATION", "Password change");
if (!defined("_PASSWORD_FOR_USER")) define("_PASSWORD_FOR_USER", "The password for the user");
if (!defined("_HAS_BEEN_RESET")) define("_HAS_BEEN_RESET", "Has been reset");
if (!defined("_NEW_PASW_IS")) define("_NEW_PASW_IS", "The new password is ");
if (!defined("_DURING_NEXT_CONNEXION")) define("_DURING_NEXT_CONNEXION", "During the next connection");
if (!defined("_MUST_CHANGE_PSW")) define("_MUST_CHANGE_PSW", "Has to modify her/his password");

if (!defined("_NEW_PASSWORD_USER")) define("_NEW_PASSWORD_USER", "User's password reset");
if (!defined("_PASSWORD_NOT_CHANGED"))    define("_PASSWORD_NOT_CHANGED", "Problem during the password modification");
if (!defined("_ALREADY_CREATED_AND_DELETED")) define("_ALREADY_CREATED_AND_DELETED", "The requested user has been deleted. Click on reactivate at the left top to add him/her ");
if (!defined("_REACTIVATE")) define("_REACTIVATE", "Reactivate");

/************** Documents types : Lists + Forms **************/

if (!defined("_DOCTYPES_LIST")) define("_DOCTYPES_LIST", "Document types list");
if (!defined("_ADD_DOCTYPE")) define("_ADD_DOCTYPE", "Add a type");
if (!defined("_ALL_DOCTYPES")) define("_ALL_DOCTYPES", "All the types");
if (!defined("_TYPES")) define("_TYPES", "Types");

if (!defined("_DOCTYPE_MODIFICATION")) define("_DOCTYPE_MODIFICATION", "Document type modification");
if (!defined("_DOCTYPE_CREATION")) define("_DOCTYPE_CREATION", "Document type creation");

if (!defined("_MODIFY_DOCTYPE")) define("_MODIFY_DOCTYPE", "Validate the changes");
if (!defined("_ATTACH_SUBFOLDER")) define("_ATTACH_SUBFOLDER", "Attached to the sub-folder");
if (!defined("_CHOOSE_SUBFOLDER")) define("_CHOOSE_SUBFOLDER", "Choose a sub-folder");
if (!defined("_MANDATORY_FOR_COMPLETE")) define("_MANDATORY_FOR_COMPLETE", "Mandatory to the hiring folder completeness");
if (!defined("_MORE_THAN_ONE")) define("_MORE_THAN_ONE", "Repetitive piece");
if (!defined("_MANDATORY_FIELDS_IN_INDEX")) define("_MANDATORY_FIELDS_IN_INDEX", "Mandatory fields for indexation");
if (!defined("_DATE_END_DETACH_TIME")) define("_DATE_END_DETACH_TIME", "End date of the secondment period");
if (!defined("_START_DATE")) define("_START_DATE", "Start Date");
if (!defined("_START_DATE_PROBATION")) define("_START_DATE_PROBATION", "Start date of the probationary period");
if (!defined("_END_DATE")) define("_END_DATE", "End date");
if (!defined("_END_DATE_PROBATION")) define("_END_DATE_PROBATION", "End date of the probationary period");
if (!defined("_START_DATE_TRIAL")) define("_START_DATE_TRIAL", "Start date of the trial period");
if (!defined("_START_DATE_MISSION")) define("_START_DATE_MISSION", "Mission start date");
if (!defined("_END_DATE_TRIAL")) define("_END_DATE_TRIAL", "End date of the trial period");
if (!defined("_END_DATE_MISSION")) define("_END_DATE_MISSION", "Mission end date");
if (!defined("_EVENT_DATE")) define("_EVENT_DATE", "Event date");
if (!defined("_VISIT_DATE")) define("_VISIT_DATE", "Visit date ");
if (!defined("_CHANGE_DATE")) define("_CHANGE_DATE", "Change date");
if (!defined("_DOCTYPES_LIST2")) define("_DOCTYPES_LIST2", "Document types list");

if (!defined("_INDEX_FOR_DOCTYPES")) define("_INDEX_FOR_DOCTYPES", "Possible index for the document types");
if (!defined("_FIELD")) define("_FIELD", "Field");
if (!defined("_USED")) define("_USED", "Used");
if (!defined("_MANDATORY")) define("_MANDATORY", "Mandatory");
if (!defined("_ITERATIVE")) define("_ITERATIVE", "Repetitive");
if (!defined("_NATURE_FIELD")) define("_NATURE_FIELD", "Nature field");
if (!defined("_TYPE_FIELD")) define("_TYPE_FIELD", "Field type");
if (!defined("_DB_COLUMN")) define("_DB_COLUMN", "Database column");
if (!defined("_FIELD_VALUES")) define("_FIELD_VALUES", "Values");

if (!defined("_MASTER_TYPE")) define("_MASTER_TYPE", "Master type");

/************** structures : List + Form**************/
if (!defined("_STRUCTURE_LIST")) define("_STRUCTURE_LIST", "Sub-folders list");
if (!defined("_STRUCTURES")) define("_STRUCTURES", "sub-folder(s)");
if (!defined("_STRUCTURE")) define("_STRUCTURE", "Sub-folder");
if (!defined("_ALL_STRUCTURES")) define("_ALL_STRUCTURES", "All the sub-folders");

if (!defined("_THE_STRUCTURE")) define("_THE_STRUCTURE", " The sub-folder");
if (!defined("_STRUCTURE_MODIF")) define("_STRUCTURE_MODIF", "Sub-folder modification");
if (!defined("_ID_STRUCTURE_PB")) define("_ID_STRUCTURE_PB", "There is a problem with the sub-folder ID");
if (!defined("_NEW_STRUCTURE_ADDED")) define("_NEW_STRUCTURE_ADDED", "Add a new sub-folder");
if (!defined("_NEW_STRUCTURE")) define("_NEW_STRUCTURE", "New sub-folder");
if (!defined("_DESC_STRUCTURE_MISSING")) define("_DESC_STRUCTURE_MISSING", "It's missing sub-folder description");
if (!defined("_STRUCTURE_DEL")) define("_STRUCTURE_DEL", "Sub-folder deletion");
if (!defined("_DELETED_STRUCTURE")) define("_DELETED_STRUCTURE", "Deleted sub-folder");
if (! defined("_FONT_COLOR"))    define("_FONT_COLOR", "Font colour");
if (! defined("_FONT_SIZE"))    define("_FONT_SIZE", "Font size");
if (! defined("_CSS_STYLE"))    define("_CSS_STYLE", "Style");
if (! defined("_CHOOSE_STYLE"))    define("_CHOOSE_STYLE", "Choose a style");
if (! defined("_DEFAULT_STYLE"))    define("_DEFAULT_STYLE", "Default style");

/********************** colors style ***************************/
if (! defined("_BLACK"))    define("_BLACK", "Black");
if (! defined("_BEIGE"))    define("_BEIGE", "Beige");
if (! defined("_BLUE"))    define("_BLUE", "Blue");
if (! defined("_BLUE_BOLD"))    define("_BLUE_BOLD", "Blue (bold)");
if (! defined("_GREY"))    define("_GREY", "Grey");
if (! defined("_YELLOW"))    define("_YELLOW", "Yellow");
if (! defined("_BROWN"))    define("_BROWN", "Brown");
if (! defined("_BLACK_BOLD"))    define("_BLACK_BOLD", "Black (bold)");
if (! defined("_ORANGE"))    define("_ORANGE", "Orange");
if (! defined("_ORANGE_BOLD"))    define("_ORANGE_BOLD", "Orange (bold)");
if (! defined("_PINK"))    define("_PINK", "Pink");
if (! defined("_RED"))    define("_RED", "Red");
if (! defined("_GREEN"))    define("_GREEN", "Green");
if (! defined("_PURPLE"))    define("_PURPLE", "Purple");

/************** Sub-folders : List + Form**************/
if (!defined("_SUBFOLDER_LIST")) define("_SUBFOLDER_LIST", "Sub-folder list");
if (!defined("_SUBFOLDERS")) define("_SUBFOLDERS", "Sub-folder(s)");
if (!defined("_ALL_SUBFOLDERS")) define("_ALL_SUBFOLDERS", "All the sub-folders");
if (!defined("_SUBFOLDER")) define("_SUBFOLDER", "sub-folder");

if (!defined("_ADD_SUBFOLDER")) define("_ADD_SUBFOLDER", "Add a new sub-folder");
if (!defined("_THE_SUBFOLDER")) define("_THE_SUBFOLDER", "The new sub-folder");
if (!defined("_SUBFOLDER_MODIF")) define("_SUBFOLDER_MODIF", "Sub-folder modification");
if (!defined("_SUBFOLDER_CREATION")) define("_SUBFOLDER_CREATION", "Sub-folder creation");
if (!defined("_SUBFOLDER_ID_PB")) define("_SUBFOLDER_ID_PB", "There is a problem with the sub-folder ID");
if (!defined("_SUBFOLDER_ADDED")) define("_SUBFOLDER_ADDED", "New sub-folder addition");
if (!defined("_NEW_SUBFOLDER")) define("_NEW_SUBFOLDER", "New sub-folder");
if (!defined("_STRUCTURE_MANDATORY")) define("_STRUCTURE_MANDATORY", "The sub-folder is mandatory");
if (!defined("_SUBFOLDER_DESC_MISSING")) define("_SUBFOLDER_DESC_MISSING", "It's missing the sub-folder description");

if (!defined("_ATTACH_STRUCTURE")) define("_ATTACH_STRUCTURE", "linked to a sub-folder");
if (!defined("_CHOOSE_STRUCTURE")) define("_CHOOSE_STRUCTURE", "Choose a sub-folder");

if (!defined("_DEL_SUBFOLDER")) define("_DEL_SUBFOLDER", "Sub-folder deletion");
if (!defined("_SUBFOLDER_DELETED")) define("_SUBFOLDER_DELETED", "Deleted sub-folder");


/************** Status **************/

if (!defined("_STATUS_LIST")) define("_STATUS_LIST", "Status list");
if (!defined("_ADD_STATUS")) define("_ADD_STATUS", "Add a new status");
if (!defined("_ALL_STATUS")) define("_ALL_STATUS", "All the status");
if (!defined("_STATUS_PLUR")) define("_STATUS_PLUR", "Status");
if (!defined("_STATUS_SING")) define("_STATUS_SING", "status");

if (!defined("_TO_PROCESS")) define("_TO_PROCESS","To handle");
if (!defined("_IN_PROGRESS")) define("_IN_PROGRESS","In progress");
if (!defined("_FIRST_WARNING")) define("_FIRST_WARNING","1st reminder");
if (!defined("_SECOND_WARNING")) define("_SECOND_WARNING","2nd reminder");
if (!defined("_CLOSED")) define("_CLOSED","Closed");
if (!defined("_NEW")) define("_NEW","New");
if (!defined("_LATE")) define("_LATE", "Late");

if (!defined("_STATUS_DELETED")) define("_STATUS_DELETED", "Status deletion");
if (!defined("_DEL_STATUS")) define("_DEL_STATUS", "Deleted status");
if (!defined("_MODIFY_STATUS")) define("_MODIFY_STATUS", "Status modification");
if (!defined("_STATUS_ADDED")) define("_STATUS_ADDED","New status addition");
if (!defined("_STATUS_MODIFIED")) define("_STATUS_MODIFIED","Status modification");
if (!defined("_NEW_STATUS")) define("_NEW_STATUS", "New status");
if (!defined("_IS_SYSTEM")) define("_IS_SYSTEM", "System");
if (!defined("_CAN_BE_SEARCHED")) define("_CAN_BE_SEARCHED", "Search");
if (!defined("_CAN_BE_MODIFIED")) define("_CAN_BE_MODIFIED", "Index modification");
if (!defined("_THE_STATUS")) define("_THE_STATUS", "The status ");
if (!defined("_ADMIN_STATUS")) define("_ADMIN_STATUS", "Status");
if (!defined("_IMG_RELATED")) define("_IMG_RELATED", "Associated picture");


/************* Actions **************/

if (!defined("_ACTION_LIST")) define("_ACTION_LIST", "Actions list");
if (!defined("_ADD_ACTION")) define("_ADD_ACTION", "Add a new action");
if (!defined("_ALL_ACTIONS")) define("_ALL_ACTIONS", "All the actions");
if (!defined("_ACTION_HISTORY")) define("_ACTION_HISTORY", "Track the action");

if (!defined("_ACTION_DELETED")) define("_ACTION_DELETED", "Action deletion");
if (!defined("_DEL_ACTION")) define("_DEL_ACTION", "Deleted action");
if (!defined("_MODIFY_ACTION")) define("_MODIFY_ACTION", "Action modification");
if (!defined("_ACTION_ADDED")) define("_ACTION_ADDED","New action addition");
if (!defined("_ACTION_MODIFIED")) define("_ACTION_MODIFIED","Action modification");
if (!defined("_NEW_ACTION")) define("_NEW_ACTION", "New action");
if (!defined("_THE_ACTION")) define("_THE_ACTION", "The action ");
if (!defined("_ADMIN_ACTIONS")) define("_ADMIN_ACTIONS", "Actions");


/************** History **************/
if (!defined("_HISTORY_TITLE")) define("_HISTORY_TITLE", "Events history");
if (!defined("_HISTORY_BATCH_TITLE")) define("_HISTORY_BATCH_TITLE", "Batch events history");
if (!defined("_HISTORY")) define("_HISTORY", "History");
if (!defined("_HISTORY_BATCH")) define("_HISTORY_BATCH", "Batch history");
if (!defined("_BATCH_NAME")) define("_BATCH_NAME", "Batch name");
if (!defined("_CHOOSE_BATCH")) define("_CHOOSE_BATCH", "Choose a batch");
if (!defined("_BATCH_ID")) define("_BATCH_ID", "Batch ID");
if (!defined("_TOTAL_PROCESSED")) define("_TOTAL_PROCESSED", "Processed documents");
if (!defined("_TOTAL_ERRORS")) define("_TOTAL_ERRORS", "Documents on error");
if (!defined("_ONLY_ERRORS")) define("_ONLY_ERRORS", "Only with errors");
if (!defined("_INFOS")) define("_INFOS", "Information");

/************** Manage structure **************/
if (!defined("_ADMIN_ARCHI")) define("_ADMIN_ARCHI", "File plan administration");
if (!defined("_MANAGE_STRUCTURE")) define("_MANAGE_STRUCTURE", "Manage the sub-folders");
if (!defined("_MANAGE_STRUCTURE_DESC")) define("_MANAGE_STRUCTURE_DESC", "Administer the folders. Those ones are the higher element of the fileplan. If the module Folder is connected, you can associate a folder type to a file plan.");
if (!defined("_MANAGE_SUBFOLDER")) define("_MANAGE_SUBFOLDER", "Manage the sub-folders");
if (!defined("_MANAGE_SUBFOLDER_DESC")) define("_MANAGE_SUBFOLDER_DESC", "Manage the sub-folders inside the folders.");
if (!defined("_ARCHITECTURE")) define("_ARCHITECTURE", "File plan");

/************************* Errors messages ***********************************/
if (!defined("_MORE_INFOS")) define("_MORE_INFOS", "For more information, please contact your administrator ");
if (!defined("_ALREADY_EXISTS")) define("_ALREADY_EXISTS", "Already exists !");
if (!defined("_DOCSERVER_ERROR")) define("_DOCSERVER_ERROR", "Error with the doc server");
if (!defined("_NO_AVAILABLE_DOCSERVER")) define("_NO_AVAILABLE_DOCSERVER", "No available doc server");
if (!defined("_NOT_ENOUGH_DISK_SPACE")) define("_NOT_ENOUGH_DISK_SPACE", "There is no space enough on the server");

// class usergroups
if (!defined("_NO_GROUP")) define("_NO_GROUP", "The group doesn't exist !");
if (!defined("_NO_SECURITY_AND_NO_SERVICES")) define("_NO_SECURITY_AND_NO_SERVICES", "Has no defined security and no services");
if (!defined("_GROUP_ADDED")) define("_GROUP_ADDED", "Added new group");
if (!defined("_SYNTAX_ERROR_WHERE_CLAUSE")) define("_SYNTAX_ERROR_WHERE_CLAUSE", "Syntax error in the where clause");
if (!defined("_GROUP_UPDATED")) define("_GROUP_UPDATED", "Modified group");
if (!defined("_AUTORIZED_GROUP")) define("_AUTORIZED_GROUP", "Authorized group");
if (!defined("_SUSPENDED_GROUP")) define("_SUSPENDED_GROUP", "Suspended group");
if (!defined("_DELETED_GROUP")) define("_DELETED_GROUP", "Deleted group");
if (!defined("_GROUP_UPDATE")) define("_GROUP_UPDATE", "Modification group;");
if (!defined("_GROUP_AUTORIZATION")) define("_GROUP_AUTORIZATION", "group authorization");
if (!defined("_GROUP_SUSPENSION")) define("_GROUP_SUSPENSION", "Group suspension");
if (!defined("_GROUP_DELETION")) define("_GROUP_DELETION", "Group deletion");
if (!defined("_GROUP_DESC")) define("_GROUP_DESC", "The group description ");
if (!defined("_GROUP_ID")) define("_GROUP_ID", "The group ID");
if (!defined("_EXPORT_RIGHT")) define("_EXPORT_RIGHT", "Export rights");

//class users
if (!defined("_USER_NO_GROUP")) define("_USER_NO_GROUP", "You don't belong to any group");
if (!defined("_SUSPENDED_ACCOUNT")) define("_SUSPENDED_ACCOUNT", "Your user account has been suspended");
if (!defined("_BAD_LOGIN_OR_PSW")) define("_BAD_LOGIN_OR_PSW", "Wrong user name or wrong password");
if (!defined("_AUTORIZED_USER")) define("_AUTORIZED_USER", "Authorized user");
if (!defined("_SUSPENDED_USER")) define("_SUSPENDED_USER", "Suspended user");
if (!defined("_DELETED_USER")) define("_DELETED_USER", "Deleted user");
if (!defined("_USER_DELETION")) define("_USER_DELETION", "User deletion");
if (!defined("_USER_AUTORIZATION")) define("_USER_AUTORIZATION", "User authorization");
if (!defined("_USER_SUSPENSION")) define("_USER_SUSPENSION", "User suspension");
if (!defined("_USER_UPDATED")) define("_USER_UPDATED", "Modified user");
if (!defined("_USER_UPDATE")) define("_USER_UPDATE", "User modification");
if (!defined("_USER_ADDED")) define("_USER_ADDED", "New added user");
if (!defined("_NO_PRIMARY_GROUP")) define("_NO_PRIMARY_GROUP", "No primary group is selected !");
if (!defined("_THE_USER")) define("_THE_USER", "The user ");
if (!defined("_USER_ID")) define("_USER_ID", "user ID");
if (!defined("_MY_INFO")) define("_MY_INFO", "My profile");


//class types
if (!defined("_UNKNOWN_PARAM")) define("_UNKNOWN_PARAM", "unknown configuration");
if (!defined("_DOCTYPE_UPDATED")) define("_DOCTYPE_UPDATED", " Modified document type");
if (!defined("_DOCTYPE_UPDATE")) define("_DOCTYPE_UPDATE", " Document type modification");
if (!defined("_DOCTYPE_ADDED")) define("_DOCTYPE_ADDED", "New added document type");
if (!defined("_DELETED_DOCTYPE")) define("_DELETED_DOCTYPE", " Deleted document type");
if (!defined("_DOCTYPE_DELETION")) define("_DOCTYPE_DELETION", " document type deletion");
if (!defined("_THE_DOCTYPE")) define("_THE_DOCTYPE", " the document type ");
if (!defined("_THE_WORDING")) define("_THE_WORDING", " The wording ");
if (!defined("_THE_TABLE")) define("_THE_TABLE", " The table ");
if (!defined("_PIECE_TYPE")) define("_PIECE_TYPE", "Piece type");
//class db
if (!defined("_CONNEXION_ERROR")) define("_CONNEXION_ERROR", "Connection error");
if (!defined("_SELECTION_BASE_ERROR")) define("_SELECTION_BASE_ERROR", "Error at the base selection");
if (!defined("_QUERY_ERROR")) define("_QUERY_ERROR", "Error at the request");
if (!defined("_CLOSE_CONNEXION_ERROR")) define("_CLOSE_CONNEXION_ERROR", "Error on the connection closing");
if (!defined("_ERROR_NUM")) define("_ERROR_NUM", "Error number");
if (!defined("_HAS_JUST_OCCURED")) define("_HAS_JUST_OCCURED", "has just occurred");
if (!defined("_MESSAGE")) define("_MESSAGE", "Message");
if (!defined("_QUERY")) define("_QUERY", "Request");
if (!defined("_LAST_QUERY")) define("_LAST_QUERY", "Last request");

//Autres
if (!defined("_NO_GROUP_SELECTED")) define("_NO_GROUP_SELECTED", "No selected group");
if (!defined("_NOW_LOG_OUT")) define("_NOW_LOG_OUT", "You are now disconnected");
if (!defined("_DOC_NOT_FOUND")) define("_DOC_NOT_FOUND", "Lost document");
if (!defined("_DOUBLED_DOC")) define("_DOUBLED_DOC", "Problem of doubles");
if (!defined("_NO_DOC_OR_NO_RIGHTS")) define("_NO_DOC_OR_NO_RIGHTS", "This document doesn't exist any more, or you have no rights to access");
if (!defined("_INEXPLICABLE_ERROR")) define("_INEXPLICABLE_ERROR", "an unexplainable error occurred");
if (!defined("_TRY_AGAIN_SOON")) define("_TRY_AGAIN_SOON", "Please try again in a few moments");
if (!defined("_NO_OTHER_RECIPIENT")) define("_NO_OTHER_RECIPIENT", "There is no other recipient fro this mail");
if (!defined("_WAITING_INTEGER")) define("_WAITING_INTEGER", "Expected integer");
if (!defined("_WAITING_FLOAT")) define("_WAITING_FLOAT", "Expected floating number");

if (!defined("_DEFINE")) define("_DEFINE", "Clarify");
if (!defined("_NUM")) define("_NUM", "Number");
if (!defined("_ROAD")) define("_ROAD", "Road");
if (!defined("_POSTAL_CODE")) define("_POSTAL_CODE","Postal code");
if (!defined("_CITY")) define("_CITY", "City");

if (!defined("_CHOOSE_USER")) define("_CHOOSE_USER", "You have to choose an user");
if (!defined("_CHOOSE_USER2")) define("_CHOOSE_USER2", "Choose an user");
if (!defined("_NUM2")) define("_NUM2", "Number");
if (!defined("_UNDEFINED")) define("_UNDEFINED", "Unknown.");
if (!defined("_CONSULT_EXTRACTION")) define("_CONSULT_EXTRACTION", "You can consult here the extracted files");
if (!defined("_SERVICE")) define("_SERVICE", "Service");
if (!defined("_AVAILABLE_SERVICES")) define("_AVAILABLE_SERVICES", "Available services");

// Months
if (!defined("_JANUARY")) define("_JANUARY", "January");
if (!defined("_FEBRUARY")) define("_FEBRUARY", "February");
if (!defined("_MARCH")) define("_MARCH", "March");
if (!defined("_APRIL")) define("_APRIL", "April");
if (!defined("_MAY")) define("_MAY", "May");
if (!defined("_JUNE")) define("_JUNE", "June");
if (!defined("_JULY")) define("_JULY", "July");
if (!defined("_AUGUST")) define("_AUGUST", "August");
if (!defined("_SEPTEMBER")) define("_SEPTEMBER", "September");
if (!defined("_OCTOBER")) define("_OCTOBER", "October");
if (!defined("_NOVEMBER")) define("_NOVEMBER", "November");
if (!defined("_DECEMBER")) define("_DECEMBER", "December");



if (!defined("_NOW_LOGOUT")) define("_NOW_LOGOUT", "You are now disconnected");

if (!defined("_WELCOME2")) define("_WELCOME2", "Welcome");

if (!defined("_CONTRACT_HISTORY")) define("_CONTRACT_HISTORY", "Contracts history");

if (!defined("_CLICK_CALENDAR")) define("_CLICK_CALENDAR", "Click To choose a date");
if (!defined("_MODULES")) define("_MODULES", "Modules");
if (!defined("_CHOOSE_MODULE")) define("_CHOOSE_MODULE", "Choose a module");
if (!defined("_FOLDER")) define("_FOLDER", "Folder");
if (!defined("_INDEX")) define("_INDEX", "Index");

//COLLECTIONS
if (!defined("_MAILS")) define("_MAILS", "Mails");
if (!defined("_DOCUMENTS")) define("_DOCUMENTS", "Loans");
if (!defined("_INVOICES")) define("_INVOICES", "Bills");
if (!defined("_SAMPLE")) define("_SAMPLE", "Collection of example");
if (!defined("_CHOOSE_COLLECTION")) define("_CHOOSE_COLLECTION", "Choose a collection");
if (!defined("_COLLECTION")) define("_COLLECTION", "Collection");
if (!defined("_EVENT")) define("_EVENT", "Event");
if (!defined("_LINK")) define("_LINK", "Link");

if (!defined("_FILING")) define("_FILING", "Types");

if (!defined("_CHOOSE_DIFFUSION_LIST")) define("_CHOOSE_DIFFUSION_LIST", "Choose a diffusion list");
if (!defined("_DIFF_LIST_HISTORY")) define("_DIFF_LIST_HISTORY", "Diffusion history");
if (!defined("_DIFF_LIST_VISA_HISTORY")) define("_DIFF_LIST_VISA_HISTORY", "Visa flow history");
if (!defined("_DIFF_LIST_AVIS_HISTORY")) define("_DIFF_LIST_AVIS_HISTORY", "recommendation flow history");


if (!defined("_MODIFY_BY")) define("_MODIFY_BY", "Modified by");
if (!defined("_DIFFLIST_NEVER_MODIFIED")) define("_DIFFLIST_NEVER_MODIFIED", "The diffusion list has never been modified");

//BIT MASK
if (!defined("_BITMASK_VALUE_ALREADY_EXIST")) define("_BITMASK_VALUE_ALREADY_EXIST", "Bit mask is already used in an other type");

if (!defined("_ASSISTANT_MODE")) define("_ASSISTANT_MODE", "Assistant mode");
if (!defined("_EDIT_WITH_ASSISTANT")) define("_EDIT_WITH_ASSISTANT", "Click here to edit the where clause with the assistant mode");
if (!defined("_VALID_THE_WHERE_CLAUSE")) define("_VALID_THE_WHERE_CLAUSE", "Click here to VALIDATE the where clause");
if (!defined("_DELETE_SHORT")) define("_DELETE_SHORT", "deletion");
if (!defined("_CHOOSE_ANOTHER_SUBFOLDER")) define("_CHOOSE_ANOTHER_SUBFOLDER", "Choose an other sub-folder");
if (!defined("_DOCUMENTS_EXISTS_FOR_COLLECTION")) define("_DOCUMENTS_EXISTS_FOR_COLLECTION", "Documents exist for the collection");
if (!defined("_MUST_CHOOSE_COLLECTION_FIRST")) define("_MUST_CHOOSE_COLLECTION_FIRST", "You have to choose a collection");
if (!defined("_CANTCHANGECOLL")) define("_CANTCHANGECOLL", "You can not change the collection");
if (!defined("_DOCUMENTS_EXISTS_FOR_COUPLE_FOLDER_TYPE_COLLECTION")) define("_DOCUMENTS_EXISTS_FOR_COUPLE_FOLDER_TYPE_COLLECTION", "documents exist for the folder type / collection pair");

if (!defined("_NO_RIGHT")) define("_NO_RIGHT", "Error");
if (!defined("_NO_RIGHT_TXT")) define("_NO_RIGHT_TXT", "You tried to access to a document in whom you have no right, or the document doesn't exist...");
if (!defined("_NUM_GED")) define("_NUM_GED", "EDM number");

///// Manage action error
if (!defined("_AJAX_PARAM_ERROR")) define("_AJAX_PARAM_ERROR", "Ajax parameters Error");
if (!defined("_ACTION_CONFIRM")) define("_ACTION_CONFIRM", "Do you want to make the following action ? : ");
if (!defined("_ADD_ATTACHMENT_OR_NOTE")) define("_ADD_ATTACHMENT_OR_NOTE", "Add an attachment or a note pour this mail/ those mails");
if (!defined("_CLOSE_MAIL_WITH_ATTACHMENT")) define("_CLOSE_MAIL_WITH_ATTACHMENT", "Closing with attachment");
if (!defined("_ACTION_NOT_IN_DB")) define("_ACTION_NOT_IN_DB", "Action no recorded on base");
if (!defined("_ERROR_PARAM_ACTION")) define("_ERROR_PARAM_ACTION", "Configuration error of the action");
if (!defined("_SQL_ERROR")) define("_SQL_ERROR", "SQL error");
if (!defined("_ACTION_DONE")) define("_ACTION_DONE", "Done action");
if (!defined("_ACTION_PAGE_MISSING")) define("_ACTION_PAGE_MISSING", "Result page of the missing action");
if (!defined("_ERROR_SCRIPT")) define("_ERROR_SCRIPT", "Results page of the action : error in the script or missing function");
if (!defined("_SERVER_ERROR")) define("_SERVER_ERROR", "Server error");
if (!defined("_CHOOSE_ONE_DOC")) define("_CHOOSE_ONE_DOC", "Choose a document at least");
if (!defined("_CHOOSE_ONE_OBJECT")) define("_CHOOSE_ONE_OBJECT", "Choose an element at least");

if (!defined("_CLICK_LINE_TO_CHECK_INVOICE")) define("_CLICK_LINE_TO_CHECK_INVOICE", "Click on a lign to check a bill");
if (!defined("_FOUND_INVOICES")) define("_FOUND_INVOICES", " Found bill(s)");
if (!defined("_SIMPLE_CONFIRM")) define("_SIMPLE_CONFIRM", "Simple confirmation");
if (!defined("_CHECK_INVOICE")) define("_CHECK_INVOICE", "Check bill");

if (!defined("_REDIRECT_TO")) define("_REDIRECT_TO", "Redirected to");
if (!defined("_NO_STRUCTURE_ATTACHED")) define("_NO_STRUCTURE_ATTACHED", "This document type isn't attached to any folder");

///// Credits
if (!defined("_MAARCH_CREDITS")) define("_MAARCH_CREDITS", "About Maarch ");
if (!defined("_MAARCH_LICENCE")) define("_MAARCH_LICENCE", "Maarch is circulated under the terms of");
if (!defined("_OFFICIAL_WEBSITE")) define("_OFFICIAL_WEBSITE", "Official website");
if (!defined("_COMMUNITY")) define("_COMMUNITY", "Community");
if (!defined("_DOCUMENTATION")) define("_DOCUMENTATION", "Documentation");
if (!defined("_THANKS_TO_EXT_DEV")) define("_THANKS_TO_EXT_DEV", "Maarch depend upon some external components. A thank-you to their developers !");
if (!defined("_EXTERNAL_COMPONENTS")) define("_EXTERNAL_COMPONENTS", "External components");
if (!defined("_THANKS_TO_COMMUNITY")) define("_THANKS_TO_COMMUNITY", "And all the Maarch's community !");

if (!defined("_PROCESSING_DATE")) define("_PROCESSING_DATE", "Processing deadline");
if (!defined("_PROCESS_NUM")) define("_PROCESS_NUM","Processing of the mail number ...");
if (!defined("_PROCESS_LIMIT_DATE")) define("_PROCESS_LIMIT_DATE", "Processing deadline");
if (!defined("_LATE_PROCESS")) define("_LATE_PROCESS", "Late");
if (!defined("_PROCESS_DELAY")) define("_PROCESS_DELAY", "Processing period (in days)");
if (!defined("_ALARM1_DELAY")) define("_ALARM1_DELAY", "Reminder period 1 (days) before term");
if (!defined("_ALARM2_DELAY")) define("_ALARM2_DELAY", "Reminder period 2 (days) after term");
if (!defined("_CATEGORY")) define("_CATEGORY", "Category");
if (!defined("_CHOOSE_CATEGORY")) define("_CHOOSE_CATEGORY", "Choose a category");
if (!defined("_RECEIVING_DATE")) define("_RECEIVING_DATE", "Arrival date");
if (!defined("_SUBJECT")) define("_SUBJECT", "Object");
if (!defined("_AUTHOR")) define("_AUTHOR", "Author");
if (!defined("_AUTHOR_DOC")) define("_AUTHOR_DOC", "Document author");
if (!defined("_DOCTYPE_MAIL")) define("_DOCTYPE_MAIL", "Mail type");
if (!defined("_PROCESS_LIMIT_DATE_USE")) define("_PROCESS_LIMIT_DATE_USE", "Activate the deadline");
if (!defined("_DEPARTMENT_DEST")) define("_DEPARTMENT_DEST", "Processing department");
if (!defined("_DEPARTMENT_EXP")) define("_DEPARTMENT_EXP", "Sender department");

// Mail Categories
if (!defined("_INCOMING")) define("_INCOMING", "Arrival mail");
if (!defined("_OUTGOING")) define("_OUTGOING", "Departure mail");
if (!defined("_INTERNAL")) define("_INTERNAL", "Internal Mail");
if (!defined("_MARKET_DOCUMENT")) define("_MARKET_DOCUMENT", "Document of sub-folder");
if (!defined("_EMPTY")) define("_EMPTY", "Empty");

// Mail Natures
if (!defined("_CHOOSE_NATURE")) define("_CHOOSE_NATURE", "Choose");
if (!defined("_SIMPLE_MAIL")) define("_SIMPLE_MAIL", "Simple mail");
if (!defined("_EMAIL")) define("_EMAIL", "Email");
if (!defined("_FAX")) define("_FAX", "Fax");
if (!defined("_CHRONOPOST")) define("_CHRONOPOST", "Chronopost");
if (!defined("_FEDEX")) define("_FEDEX", "Fedex");
if (!defined("_REGISTERED_MAIL")) define("_REGISTERED_MAIL", "registered letter with recorded delivery");
if (!defined("_COURIER")) define("_COURIER", "Courier");
if (!defined("_OTHER")) define("_OTHER", "Other");

//Priorities
if (!defined("_NORMAL")) define("_NORMAL", "Normal");
if (!defined("_VERY_HIGH")) define("_VERY_HIGH", "Very urgent");
if (!defined("_HIGH")) define("_HIGH", "Urgent");
if (!defined("_LOW")) define("_LOW", "Low");
if (!defined("_VERY_LOW")) define("_VERY_LOW", "Very low");


if (!defined("_INDEXING_MLB")) define("_INDEXING_MLB", "Save a mail/document");
if (!defined("_ADV_SEARCH_MLB")) define("_ADV_SEARCH_MLB", "Search");
if (!defined("_ADV_SEARCH_INVOICES")) define("_ADV_SEARCH_INVOICES", "[cold] Search a client's bill");

if (!defined("_ADV_SEARCH_TITLE")) define("_ADV_SEARCH_TITLE", "Advanced search of document");
if (!defined("_MAIL_OBJECT")) define("_MAIL_OBJECT", "Mail object");

if (!defined("_N_GED")) define("_N_GED","EDM number ");
if (!defined("_GED_NUM")) define("_GED_NUM", "EDM N#");
if (!defined("_GED_DOC")) define("_GED_DOC", "EDM document");
if (!defined("_CHOOSE_TYPE_MAIL")) define("_CHOOSE_TYPE_MAIL","Choose a mail type");

if (!defined("_REG_DATE")) define("_REG_DATE","Saving date");
if (!defined("_PROCESS_DATE")) define("_PROCESS_DATE","Processing date");
if (!defined("_CHOOSE_STATUS")) define("_CHOOSE_STATUS","Choose a status");
if (!defined("_PROCESS_RECEIPT")) define("_PROCESS_RECEIPT","Recipient(s) for processing");
if (!defined("_CHOOSE_RECEIPT")) define("_CHOOSE_RECEIPT","Choose a recipient");
if (!defined("_TO_CC")) define("_TO_CC","On copy");
if (!defined("_ADD_CC")) define("_ADD_CC","On copy");
if (!defined("_ADD_COPIES")) define("_ADD_COPIES","Add persons on copy");

//Circuits de visa
if (!defined("_TO_SIGN")) define("_TO_SIGN","For signature");
if (!defined("_VISA_USER"))    define("_VISA_USER", "For visa");

//Circuits d'avis
if (!defined("_TO_VIEW")) define("_TO_VIEW","For recommendation");
if (!defined("_TO_SHARED_VIEW")) define("_TO_SHARED_VIEW","For shared recommendation");
if (!defined("_FOLLOWED_INFO")) define("_FOLLOWED_INFO","For the follow-up information");

if (!defined("_PROCESS_NOTES")) define("_PROCESS_NOTES","Processing notes");
if (!defined("_DIRECT_CONTACT")) define("_DIRECT_CONTACT","Direct initial contact");
if (!defined("_NO_ANSWER")) define("_NO_ANSWER","No response");
if (!defined("_ANSWER")) define("_ANSWER","Response");
if (!defined("_DETAILS")) define("_DETAILS", "Detailed sheet");
if (!defined("_DOWNLOAD")) define("_DOWNLOAD", "Download the mail");
if (!defined("_SEARCH_RESULTS")) define("_SEARCH_RESULTS", "Search result");

if (!defined("_THE_SEARCH")) define("_THE_SEARCH", "The search");
if (!defined("_CHOOSE_TABLE")) define("_CHOOSE_TABLE", "Choose a collection");
if (!defined("_SEARCH_COPY_MAIL")) define("_SEARCH_COPY_MAIL","Search in my mail on copy");
if (!defined("_MAIL_PRIORITY")) define("_MAIL_PRIORITY", "Mail priority");
if (!defined("_CHOOSE_PRIORITY")) define("_CHOOSE_PRIORITY", "Choose a priority");
if (!defined("_ADD_PARAMETERS")) define("_ADD_PARAMETERS", "Add criteria");
if (!defined("_CHOOSE_PARAMETERS")) define("_CHOOSE_PARAMETERS", "Choose your criteria");
if (!defined("_CHOOSE_ENTITES_SEARCH_TITLE")) define("_CHOOSE_ENTITES_SEARCH_TITLE", "Add the desired department(s) to limit the search");
if (!defined("_CHOOSE_DOCTYPES_SEARCH_TITLE")) define("_CHOOSE_DOCTYPES_SEARCH_TITLE", "Add the desired document type(s) to limit the search");
if (!defined("_CHOOSE_DOCTYPES_MAIL_SEARCH_TITLE")) define("_CHOOSE_DOCTYPES_MAIL_SEARCH_TITLE", "Add the desired mail type(s) to limit the search");
if (!defined("_DESTINATION_SEARCH")) define("_DESTINATION_SEARCH", "Assigned department(s)");
if (!defined("_ADD_PARAMETERS_HELP")) define("_ADD_PARAMETERS_HELP", "To slim the result, you can add criteria to your search... ");
if (!defined("_MAIL_OBJECT_HELP")) define("_MAIL_OBJECT_HELP", "Please enter a word or a words group of the mail object");
if (!defined("_N_GED_HELP")) define("_N_GED_HELP", "");
if (!defined("_CHOOSE_RECIPIENT_SEARCH_TITLE")) define("_CHOOSE_RECIPIENT_SEARCH_TITLE", "Add the recipient(s) to limit the search");
if (!defined("_MULTI_FIELD")) define("_MULTI_FIELD","Multi-fields");
if (!defined("_MULTI_FIELD_HELP")) define("_MULTI_FIELD_HELP","Object, description, title, EDM number, Chrono number, processing notes...");
if (!defined("_SAVE_QUERY")) define("_SAVE_QUERY", "Save my search");
if (!defined("_SAVE_QUERY_TITLE")) define("_SAVE_QUERY_TITLE", "Search saving");
if (!defined("_QUERY_NAME")) define("_QUERY_NAME", "Name of my search");
if (!defined("_QUERY_SAVED")) define("_QUERY_SAVED", "Saved search");




if (!defined("_LOAD_QUERY")) define("_LOAD_QUERY", "Load the search");
if (!defined("_DELETE_QUERY")) define("_DELETE_QUERY", "Delete the search");
if (!defined("_CHOOSE_SEARCH")) define("_CHOOSE_SEARCH", "Choose a search");
if (!defined("_THIS_SEARCH")) define("_THIS_SEARCH", "This search");
if (!defined("_MY_SEARCHES")) define("_MY_SEARCHES", "My searches");
if (!defined("_CLEAR_SEARCH")) define("_CLEAR_SEARCH", "Erase the criteria");
if (!defined("_CHOOSE_STATUS_SEARCH_TITLE")) define("_CHOOSE_STATUS_SEARCH_TITLE", "Add the desired status(es)to limit the search");
if (!defined("_ERROR_IE_SEARCH")) define("_ERROR_IE_SEARCH", "This element is already defined !");

if (!defined("_DEST_USER")) define("_DEST_USER","Recipient");
if (!defined("_DOCTYPES")) define("_DOCTYPES","Document type(s)");
if (!defined("_DOCTYPE_INDEXES")) define("_DOCTYPE_INDEXES", "Document type index");
if (!defined("_MAIL_NATURE")) define("_MAIL_NATURE", " Nature of the sending");
if (!defined("_CHOOSE_MAIL_NATURE")) define("_CHOOSE_MAIL_NATURE", "Choose the nature of the sending");
if (!defined("_ERROR_DOCTYPE")) define("_ERROR_DOCTYPE", "Invalid document type");
if (!defined("_ADMISSION_DATE")) define("_ADMISSION_DATE", "Arrival date");
if (!defined("_FOUND_DOC")) define("_FOUND_DOC", "Found document(s)");
if (!defined("_FOUND_LOGS")) define("_FOUND_LOGS", "Found logs file(s)");
if (!defined("_PROCESS")) define("_PROCESS", "Processing ");
if (!defined("_DOC_NUM")) define("_DOC_NUM", "document n# ");
if (!defined("_LETTER_NUM")) define("_LETTER_NUM", "Mail # ");
if (!defined("_GENERAL_INFO")) define("_GENERAL_INFO", "General informations");
if (!defined("_ON_DOC_NUM")) define("_ON_DOC_NUM", " on the document number");
if (!defined("_PRIORITY")) define("_PRIORITY", "Priority");
if (!defined("_MAIL_DATE")) define("_MAIL_DATE", "Mail date");
if (!defined("_DOC_HISTORY")) define("_DOC_HISTORY", "History");
if (!defined("_DONE_ANSWERS")) define("_DONE_ANSWERS","Done responses");
if (!defined("_MUST_DEFINE_ANSWER_TYPE")) define("_MUST_DEFINE_ANSWER_TYPE", "You have to clarify the response type");
if (!defined("_MUST_CHECK_ONE_BOX")) define("_MUST_CHECK_ONE_BOX", "You have to tick one case at least");
if (!defined("_ANSWER_TYPE")) define("_ANSWER_TYPE","Response type(s)");

if (!defined("_INDEXATION_TITLE")) define("_INDEXATION_TITLE", "Indexation of a document");
if (!defined("_CHOOSE_FILE")) define("_CHOOSE_FILE", "Choose the file");
if (!defined("_CHOOSE_TYPE")) define("_CHOOSE_TYPE", "Choose a type");

if (!defined("_FILE_LOADED_BUT_NOT_VISIBLE")) define("_FILE_LOADED_BUT_NOT_VISIBLE", "The file is loaded and ready to be saved on the server.<br/>");
if (!defined("_ONLY_FILETYPES_AUTHORISED")) define("_ONLY_FILETYPES_AUTHORISED", "Only the following files can be displayed in this window");
if (!defined("_PROBLEM_LOADING_FILE_TMP_DIR")) define("_PROBLEM_LOADING_FILE_TMP_DIR", "problem during the file loading on the temporary folder of the server");
if (!defined("_NO_FILE_SELECTED")) define("_NO_FILE_SELECTED", "No loaded file");
if (!defined("_DOWNLOADED_FILE")) define("_DOWNLOADED_FILE", "Loaded file");
if (!defined("_WRONG_FILE_TYPE")) define("_WRONG_FILE_TYPE", "This kind of file is not allowed");

if (!defined("_LETTERBOX")) define("_LETTERBOX", "Mails collection");
if (!defined("_ATTACHMENTS_COLL")) define("_ATTACHMENTS_COLL", "Attachments collection");
if (!defined("_ATTACHMENTS_VERS_COLL")) define("_ATTACHMENTS_VERS_COLL", "Collection of attachments versions");
if (!defined("_APA_COLL")) define("_APA_COLL", "Collection of the physical archiving");
if (!defined("_REDIRECT_TO_ACTION")) define("_REDIRECT_TO_ACTION", "Redirect to an action");
if (!defined("_DOCUMENTS_LIST")) define("_DOCUMENTS_LIST", "List");
if (!defined("_LOGS_LIST")) define("_LOGS_LIST", "Logs list");


/********* Contacts ************/
if (!defined("_ADMIN_CONTACTS")) define("_ADMIN_CONTACTS", "Contacts");
if (!defined("_ADMIN_CONTACTS_DESC")) define("_ADMIN_CONTACTS_DESC", "Administration of contacts");
if (!defined("_CONTACTS_LIST")) define("_CONTACTS_LIST", "Contacts list");
if (!defined("_CONTACT_ADDITION")) define("_CONTACT_ADDITION", "Add contact");
if (!defined("_CONTACTS")) define("_CONTACTS", "contact(s)");
if (!defined("_CONTACT")) define("_CONTACT", "Contact");
if (!defined("_NEW_CONTACT")) define("_NEW_CONTACT", "New contact");
if (!defined("_ALL_CONTACTS")) define("_ALL_CONTACTS", "All the contacts");
if (!defined("_ADD_CONTACT")) define("_ADD_CONTACT", "Contact addition");
if (!defined("_ADD_NEW_CONTACT")) define("_ADD_NEW_CONTACT", "Add a new contact");
if (!defined("_UPDATE_CONTACT")) define("_UPDATE_CONTACT", "Contacts modification");
if (!defined("_PHONE")) define("_PHONE", "Phone");
if (!defined("_ADDRESS")) define("_ADDRESS", "Address");
if (!defined("_STREET")) define("_STREET", "Street");
if (!defined("_COMPLEMENT")) define("_COMPLEMENT", "Complement");
if (!defined("_TOWN")) define("_TOWN", "City");
if (!defined("_COUNTRY")) define("_COUNTRY", "Country");
if (!defined("_SOCIETY")) define("_SOCIETY", "Company");
if (!defined("_COMP")) define("_COMP", "Others");
if (!defined("_COMP_DATA")) define("_COMP_DATA", "Further informations");
if (!defined("_CONTACT_ADDED")) define("_CONTACT_ADDED", "Added contact");
if (!defined("_CONTACT_MODIFIED")) define("_CONTACT_MODIFIED", "Modified contact");
if (!defined("_CONTACT_DELETED")) define("_CONTACT_DELETED", "Deleted contact");
if (!defined("_MODIFY_CONTACT")) define("_MODIFY_CONTACT", "Modify a contact");
if (!defined("_IS_CORPORATE_PERSON")) define("_IS_CORPORATE_PERSON", "Corporate body");
if (!defined("_INDIVIDUAL")) define("_INDIVIDUAL", "Private individual");
if (!defined("_CONTACT_TARGET")) define("_CONTACT_TARGET", "For what contact is that possible to use this type?");
if (!defined("_CONTACT_TARGET_LIST")) define("_CONTACT_TARGET_LIST", "Target of the contact type");
if (!defined("_CONTACT_TYPE_CREATION")) define("_CONTACT_TYPE_CREATION", "Is that possible to create a contact of this type out of the administration panel ?");
if (!defined("_IS_PRIVATE")) define("_IS_PRIVATE", "Private informations");
if (!defined("_TITLE2")) define("_TITLE2", "Title");
if (!defined("_WARNING_MESSAGE_DEL_CONTACT"))  define("_WARNING_MESSAGE_DEL_CONTACT", "Warning :<br> The contact's deletion leads to the reallocation of documents and mails to a new contact.");
if (!defined("_CONTACT_DELETION"))  define("_CONTACT_DELETION", "Contact deletion");
if (!defined("_CONTACT_REAFFECT"))  define("_CONTACT_REAFFECT", "Reallocation of documents and mails");
if (!defined("_UPDATE_CONTACTS")) define("_UPDATE_CONTACTS", "Contact update from indexation/ title");
if (!defined("_CONTACT_TYPE")) define("_CONTACT_TYPE", "Contact type");
if (!defined("_MULTI_EXTERNAL")) define("_MULTI_EXTERNAL", "Multi external");
if (!defined("_MULTI_CONTACT")) define("_MULTI_CONTACT", "Multi contacts");
if (!defined("_SINGLE_CONTACT")) define("_SINGLE_CONTACT", "Mono contact");
if (!defined("_SHOW_MULTI_CONTACT")) define("_SHOW_MULTI_CONTACT", "See the contacts");
if (!defined("_STRUCTURE_ORGANISM")) define("_STRUCTURE_ORGANISM", "Structure");
if (!defined("_TYPE_OF_THE_CONTACT")) define("_TYPE_OF_THE_CONTACT", "What is the contact type ?");
if (!defined("_WRITE_IN_UPPER")) define("_WRITE_IN_UPPER", "Enter on capital letters");
if (!defined("_EXAMPLE_PURPOSE")) define("_EXAMPLE_PURPOSE", "Example : General management/Domicile");
if (!defined("_EXAMPLE_SELECT_CONTACT_TYPE")) define("_EXAMPLE_SELECT_CONTACT_TYPE", "");
if (!defined("_HELP_SELECT_CONTACT_CREATED")) define("_HELP_SELECT_CONTACT_CREATED", "");

if (!defined("_MANAGE_DUPLICATES"))  define("_MANAGE_DUPLICATES", "Duplications management");
if (!defined("_DUPLICATES_BY_SOCIETY"))  define("_DUPLICATES_BY_SOCIETY", "Duplications by company / organisation");
if (!defined("_DUPLICATES_BY_NAME"))  define("_DUPLICATES_BY_NAME", "Duplications by name/first name");
if (!defined("_IS_ATTACHED_TO_DOC"))  define("_IS_ATTACHED_TO_DOC", "Attached to documents ?");
if (!defined("_RES_ATTACHED"))  define("_RES_ATTACHED", "Attached documents");
if (!defined("_SELECT_CONTACT_TO_REPLACE"))  define("_SELECT_CONTACT_TO_REPLACE", "Select the contact and the address to replace");
if (!defined("_ARE_YOU_SURE_TO_DELETE_CONTACT"))  define("_ARE_YOU_SURE_TO_DELETE_CONTACT", "Are you sure to delete this contact ?");
if (!defined("_CONTACT_CHECK"))  define("_CONTACT_CHECK", "A recently recorded mail at least is affected to the same contact.");
if (!defined("_NO_SOCIETY_DUPLICATES"))  define("_NO_SOCIETY_DUPLICATES", "No duplication by organization/ company");
if (!defined("_NO_NAME_DUPLICATES"))  define("_NO_NAME_DUPLICATES", "No duplication by name/ first name");

if (!defined("_YOU_MUST_SELECT_CONTACT")) define("_YOU_MUST_SELECT_CONTACT", "You have to select a contact ");
if (!defined("_DOC_SENDED_BY_CONTACT")) define("_DOC_SENDED_BY_CONTACT", "<b>Documents and/or mails sent by this contact</b>");
if (!defined("_CONTACT_INFO")) define("_CONTACT_INFO", "Contact card");
if (!defined("_SHIPPER")) define("_SHIPPER", "Sender");
if (!defined("_DEST")) define("_DEST", "Recipient");
if (!defined("_THIRD_DEST"))
    define("_THIRD_DEST", "Third party beneficiary");
if (!defined("_INTERNAL2")) define("_INTERNAL2", "Internal");
if (!defined("_EXTERNAL")) define("_EXTERNAL", "External");
if (!defined("_CHOOSE_SHIPPER")) define("_CHOOSE_SHIPPER", "Choose a sender");
if (!defined("_CHOOSE_DEST")) define("_CHOOSE_DEST", "Choose a recipient");
if (!defined("_DOC_DATE")) define("_DOC_DATE", "Document date");
if (!defined("_CONTACT_CARD")) define("_CONTACT_CARD", "Contact card");
if (!defined("_CREATE_CONTACT")) define("_CREATE_CONTACT", "Add a contact or an address");
if (!defined("_USE_AUTOCOMPLETION")) define("_USE_AUTOCOMPLETION", "Use the auto-complete option");

if (!defined("_USER_DATA")) define("_USER_DATA", "User card");
if (!defined("_SHIPPER_TYPE")) define("_SHIPPER_TYPE", "Sender type");
if (!defined("_DEST_TYPE")) define("_DEST_TYPE", "Recipient type");
if (!defined("_VALIDATE_MAIL")) define("_VALIDATE_MAIL", "Mail validation");
if (!defined("_LETTER_INFO")) define("_LETTER_INFO","Information on the mail");
if (!defined("_DATE_START")) define("_DATE_START","Arrival date");
if (!defined("_LIMIT_DATE_PROCESS")) define("_LIMIT_DATE_PROCESS","Processing deadline");

if (!defined("_MANAGE_CONTACT_TYPES_DESC")) define("_MANAGE_CONTACT_TYPES_DESC","Contact types management");
if (!defined("_MANAGE_CONTACT_TYPES")) define("_MANAGE_CONTACT_TYPES","Contact types management <br/>(Level 1)");

if (!defined("_MANAGE_CONTACT_PURPOSES_DESC")) define("_MANAGE_CONTACT_PURPOSES_DESC","Denomination management");
if (!defined("_MANAGE_CONTACT_PURPOSES")) define("_MANAGE_CONTACT_PURPOSES","Denomination management <br/>(Level 3)");

if (!defined("_MANAGE_CONTACTS_DESC")) define("_MANAGE_CONTACTS_DESC","Contacts management");
if (!defined("_MANAGE_CONTACTS")) define("_MANAGE_CONTACTS","Contacts management <br/>(Level 2)");

if (!defined("_SEE_ALL_ADDRESSES")) define("_SEE_ALL_ADDRESSES","See all addresses");

if (!defined("_MANAGE_CONTACT_ADDRESSES_LIST_DESC")) define("_MANAGE_CONTACT_ADDRESSES_LIST_DESC","Addresses management");
if (!defined("_MANAGE_CONTACT_ADDRESSES_LIST")) define("_MANAGE_CONTACT_ADDRESSES_LIST","Addresses management");

if (!defined("_VIEW_TREE_CONTACTS_DESC")) define("_VIEW_TREE_CONTACTS_DESC","Contacts tree view");
if (!defined("_VIEW_TREE_CONTACTS")) define("_VIEW_TREE_CONTACTS","Contacts tree view");

if (!defined("_ADDRESSES_LIST")) define("_ADDRESSES_LIST","Addresses list");
if (!defined("_SEARCH_ADDRESSES")) define("_SEARCH_ADDRESSES","Search Name/Address");

if (!defined("_CONTACT_TYPES_LIST")) define("_CONTACT_TYPES_LIST","List of contact types");
if (!defined("_DESC_CONTACT_TYPES")) define("_DESC_CONTACT_TYPES","Contact type");
if (!defined("_NEW_CONTACT_TYPE_ADDED")) define("_NEW_CONTACT_TYPE_ADDED","Addition of a new contact type");
if (!defined("_ALL_CONTACT_TYPES")) define("_ALL_CONTACT_TYPES","All the types");
if (!defined("_CONTACT_TYPE")) define("_CONTACT_TYPE","Contact types");
if (!defined("_THIS_CONTACT_TYPE")) define("_THIS_CONTACT_TYPE", "This type of contact");
if (!defined("_CONTACT_TYPE_MISSING")) define("_CONTACT_TYPE_MISSING","contact type is missing");
if (!defined("_CONTACT_TYPES")) define("_CONTACT_TYPES","Contact type(s)");
if (!defined("_A_CONTACT_TYPE")) define("_A_CONTACT_TYPE","A contact type");
if (!defined("_NEW_CONTACT_TYPE")) define("_NEW_CONTACT_TYPE","New contact type");
if (!defined("_CONTACT_TYPE_MODIF")) define("_CONTACT_TYPE_MODIF","Contact type modification");
if (!defined("_ID_CONTACT_TYPE_PB")) define("_ID_CONTACT_TYPE_PB","There is a issue with the contact type ID");
if (!defined("_THE_CONTACT_TYPE")) define("_THE_CONTACT_TYPE","The contact type");
if (!defined("_CONTACT_TYPE_DEL")) define("_CONTACT_TYPE_DEL","Contact type deletion");
if (!defined("_DELETED_CONTACT_TYPE")) define("_DELETED_CONTACT_TYPE","Deleted contact type");
if (!defined("_WARNING_MESSAGE_DEL_CONTACT_TYPE")) define("_WARNING_MESSAGE_DEL_CONTACT_TYPE","Warning : the contact type deletion leads to contacts reallocation to a new contact type.;");
if (!defined("_CONTACT_TYPE_REAFFECT")) define("_CONTACT_TYPE_REAFFECT","Contacts reallocation");
if (!defined("_ALL")) define("_ALL","All");
if (!defined("_CONTACT_ALREADY_CREATED")) define("_CONTACT_ALREADY_CREATED","Contacts already existing");
if (!defined("_CONTACT_ALREADY_CREATED_INFORMATION")) define("_CONTACT_ALREADY_CREATED_INFORMATION","(for information)");

if (!defined("_CONTACT_PURPOSES_LIST")) define("_CONTACT_PURPOSES_LIST","Denomination list");
if (!defined("_DESC_CONTACT_PURPOSES")) define("_DESC_CONTACT_PURPOSES","Denomination");
if (!defined("_NEW_CONTACT_PURPOSE_ADDED")) define("_NEW_CONTACT_PURPOSE_ADDED","Addition of a new denomination");
if (!defined("_ALL_CONTACT_PURPOSES")) define("_ALL_CONTACT_PURPOSES","All");
if (!defined("_CONTACT_PURPOSE")) define("_CONTACT_PURPOSE","Denomination");
if (!defined("_THIS_CONTACT_PURPOSE")) define("_THIS_CONTACT_PURPOSE","This denomination");
if (!defined("_CONTACT_PURPOSE_MISSING")) define("_CONTACT_PURPOSE_MISSING","Denomination is missing");
if (!defined("_CONTACT_PURPOSES")) define("_CONTACT_PURPOSES","naming(s)");
if (!defined("_A_CONTACT_PURPOSE")) define("_A_CONTACT_PURPOSE","a naming");
if (!defined("_NEW_CONTACT_PURPOSE")) define("_NEW_CONTACT_PURPOSE","New denomination");
if (!defined("_CONTACT_PURPOSE_MODIF")) define("_CONTACT_PURPOSE_MODIF","Denomination modification");
if (!defined("_ID_CONTACT_PURPOSE_PB")) define("_ID_CONTACT_PURPOSE_PB","There is an issue with a denomination ID");
if (!defined("_THE_CONTACT_PURPOSE")) define("_THE_CONTACT_PURPOSE","The denomination");
if (!defined("_CONTACT_PURPOSE_DEL")) define("_CONTACT_PURPOSE_DEL","Deletion of a denomination");
if (!defined("_DELETED_CONTACT_PURPOSE")) define("_DELETED_CONTACT_PURPOSE","Deleted denomination");
if (!defined("_CONTACT_PURPOSE_REAFFECT")) define("_CONTACT_PURPOSE_REAFFECT","Addresses reallocation");
if (!defined("_WARNING_MESSAGE_DEL_CONTACT_PURPOSE")) define("_WARNING_MESSAGE_DEL_CONTACT_PURPOSE","Warning : The denomination deletion leads to addresses reallocation to a new denomination.");
if (!defined("_CONTACT_PURPOSE_WILL_BE_CREATED")) define("_CONTACT_PURPOSE_WILL_BE_CREATED","This denomination doesn't exist. It will automatically be created.");

if (!defined("_SEARCH_CONTACTS")) define("_SEARCH_CONTACTS","Search a contact");

if (!defined("_YOU_SHOULD_ADD_AN_ADDRESS")) define("_YOU_SHOULD_ADD_AN_ADDRESS","After validation, do not forget to add an address to this contact");
if (!defined("_ADDRESSES")) define("_ADDRESSES","Address");
if (!defined("_ADDRESSES_MAJ")) define("_ADDRESSES_MAJ","Address");
if (!defined("_DOC_S")) define("_DOC_S","document(s)");
if (!defined("_CONTACTS_CONFIRMATION")) define("_CONTACTS_CONFIRMATION","Creation confirmation");
if (!defined("_YOUR_CONTACT_LOOKS_LIKE_ANOTHER")) define("_YOUR_CONTACT_LOOKS_LIKE_ANOTHER","<b> Your contact seems to be the same as one or more contacts already existing :</b>");
if (!defined("_CONFIRM_CREATE_CONTACT")) define("_CONFIRM_CREATE_CONTACT"," Do you confirm the creation of your contact ?");
if (!defined("_CONFIRM_EDIT_CONTACT")) define("_CONFIRM_EDIT_CONTACT","Do you confirm the validation of your contact ? ?");
if (!defined("_CONTACTS_CONFIRMATION_MODIFICATION")) define("_CONTACTS_CONFIRMATION_MODIFICATION","Confirmation of the modification");

if (!defined("_CREATE_BY")) define("_CREATE_BY","Created by");
if (!defined("_SOCIETY_SHORT")) define("_SOCIETY_SHORT","Companie acronym");
if (!defined("_CHOOSE_CONTACT_TYPES")) define("_CHOOSE_CONTACT_TYPES","Choose the contact type");
if (!defined("_ORGANISM")) define("_ORGANISM","Organization");

if (!defined("_NEW_CONTACT_ADDRESS")) define("_NEW_CONTACT_ADDRESS","Add a new address");
if (!defined("_A_CONTACT_ADDRESS")) define("_A_CONTACT_ADDRESS","an address");
if (!defined("_ALL_CONTACT_ADDRESSES")) define("_ALL_CONTACT_ADDRESSES","All the addresses");
if (!defined("_THE_CONTACT")) define("_THE_CONTACT","The contact");
if (!defined("_CONTACT_ADDRESSES_ASSOCIATED")) define("_CONTACT_ADDRESSES_ASSOCIATED","Addresses associated to this contact");
if (!defined("_VIEW_CONTACT")) define("_VIEW_CONTACT","View of the contact");
if (!defined("_VIEW_CONTACTS")) define("_VIEW_CONTACTS","See the contacts");
if (!defined("_VIEW_ADDRESS")) define("_VIEW_ADDRESS","Address view");
if (!defined("_TREE_INFO")) define("_TREE_INFO","(Contact type/Contacts/Addresses)");
if (!defined("_CONFIDENTIAL_ADDRESS")) define("_CONFIDENTIAL_ADDRESS","Confidential contact details");
if (!defined("_MAIN_ADDRESS")) define("_MAIN_ADDRESS","Main address");

if (!defined("_MANAGE_CONTACT_ADDRESSES")) define("_MANAGE_CONTACT_ADDRESSES","<h2>Manage associated addresses</h2>");
if (!defined("_MANAGE_CONTACT_ADDRESSES_IMG")) define("_MANAGE_CONTACT_ADDRESSES_IMG","Manage associated addresses");
if (!defined("_DEPARTEMENT")) define("_DEPARTEMENT","Department");
if (!defined("_ADDITION_ADDRESS")) define("_ADDITION_ADDRESS","Add an address");
if (!defined("_THE_ADDRESS")) define("_THE_ADDRESS","Address");
if (!defined("_MODIFY_ADDRESS")) define("_MODIFY_ADDRESS","Address modification");
if (!defined("_CHOOSE_CONTACT_PURPOSES")) define("_CHOOSE_CONTACT_PURPOSES","Choose a denomination");
if (!defined("_WEBSITE")) define("_WEBSITE","Website");
if (!defined("_OCCUPANCY")) define("_OCCUPANCY","Floor, office, door");
if (!defined("_ADDRESS_ADDED")) define("_ADDRESS_ADDED","Added address");
if (!defined("_ADD_ADDRESS")) define("_ADD_ADDRESS","Add an address");
if (!defined("_EDIT_ADDRESS")) define("_EDIT_ADDRESS","Modify the address");
if (!defined("_ADDRESS_EDITED")) define("_ADDRESS_EDITED","Modified address");
if (!defined("_DELETED_ADDRESS")) define("_DELETED_ADDRESS","Deleted address");
if (!defined("_ADDRESS_DEL")) define("_ADDRESS_DEL","deletion of an address");
if (!defined("_WARNING_MESSAGE_DEL_CONTACT_ADDRESS")) define("_WARNING_MESSAGE_DEL_CONTACT_ADDRESS","Warning : the address deletion leads to the documents reallocation to a new address.");
if (!defined("_CONTACT_ADDRESS_REAFFECT")) define("_CONTACT_ADDRESS_REAFFECT","Documents reallocation");
if (!defined("_NEW_ADDRESS")) define("_NEW_ADDRESS","New address");
if (!defined("_CHOOSE_CONTACT_ADDRESS")) define("_CHOOSE_CONTACT_ADDRESS","Choose an address");
if (!defined("_USE")) define("_USE","Use");
if (!defined("_HELP_PRIVATE")) define("_HELP_PRIVATE","<i> The marked fields by <span class=\"blue_asterisk\">*</span> are hidden in the contact card if the address is confidential</i>");

if (!defined("_SALUTATION")) define("_SALUTATION","Greeting");
if (!defined("_SALUTATION_HEADER")) define("_SALUTATION_HEADER","At the beginning");
if (!defined("_SALUTATION_FOOTER")) define("_SALUTATION_FOOTER","At the end");

if (!defined("_BACK_TO_RESULTS_LIST")) define("_BACK_TO_RESULTS_LIST","Results list return");
if (!defined("_ADD_ADDRESS_TO_CONTACT")) define("_ADD_ADDRESS_TO_CONTACT","Add a new address to an existing contact");
if (!defined("_ADD_ADDRESS_TO_CONTACT_DESC")) define("_ADD_ADDRESS_TO_CONTACT_DESC","This part is used to add an address to an existing contact.");
if (!defined("_WHICH_CONTACT")) define("_WHICH_CONTACT","To which contact do you want to add an address?");
if (!defined("_CHOOSE_THIS_CONTACT")) define("_CHOOSE_THIS_CONTACT","Choose this contact");
if (!defined("_CHOOSE_A_CONTACT")) define("_CHOOSE_A_CONTACT","Choose a contact");

if (!defined("_CREATE_CONTACTS")) define("_CREATE_CONTACTS","All the contacts");
if (!defined("_LINKED_CONTACT")) define("_LINKED_CONTACT","Linked to a contact");

//// INDEXING SEARCHING
if (!defined("_NO_COLLECTION_ACCESS_FOR_THIS_USER")) define("_NO_COLLECTION_ACCESS_FOR_THIS_USER", "No access to the documentary collections for this user");
if (!defined("_CREATION_DATE")) define("_CREATION_DATE", "Creation date");
if (!defined("_MODIFICATION_DATE")) define("_MODIFICATION_DATE", "modification date");
if (!defined("_NO_RESULTS")) define("_NO_RESULTS", "No results");
if (!defined("_FOUND_DOCS")) define("_FOUND_DOCS", "Found document(s)");
if (!defined("_MY_CONTACTS")) define("_MY_CONTACTS", "Create contacts from indexation/title");
if (!defined("_MY_CONTACTS_MENU")) define("_MY_CONTACTS_MENU", "My contacts");
if (!defined("_MAARCH_INFO")) define("_MAARCH_INFO", "Contact us");
if (!defined("_MY_COLLEAGUES")) define("_MY_COLLEAGUES", "My colleagues");
if (!defined("_DETAILLED_PROPERTIES")) define("_DETAILLED_PROPERTIES", "Detailed properties");
if (!defined("_PROPERTIES")) define("_PROPERTIES", "Details");
if (!defined("_VIEW_DOC_NUM")) define("_VIEW_DOC_NUM", "View on the document number");
if (!defined("_VIEW_DETAILS_NUM")) define("_VIEW_DETAILS_NUM", "View of the detailed card of the document number");
if (!defined("_TO")) define("_TO", "To");
if (!defined("_FILE_PROPERTIES")) define("_FILE_PROPERTIES", "File properties");
if (!defined("_FILE_DATA")) define("_FILE_DATA", "Information on the document");
if (!defined("_VIEW_DOC")) define("_VIEW_DOC", "See the document");
if (!defined("_TYPIST")) define("_TYPIST", "Operator");
if (!defined("_LOT")) define("_LOT", "Batch");
if (!defined("_ARBOX")) define("_ARBOX", "Box");
if (!defined("_ARBOXES")) define("_ARBOXES", "Boxes");
if (!defined("_ARBATCHES")) define("_ARBATCHES", "Batches");
if (!defined("_CHOOSE_BOXES_SEARCH_TITLE")) define("_CHOOSE_BOXES_SEARCH_TITLE", "Select the box(es) to reduce the search");
if (!defined("_PAGECOUNT")) define("_PAGECOUNT", "Nb pages");
if (!defined("_ISPAPER")) define("_ISPAPER", "Paper");
if (!defined("_SCANDATE")) define("_SCANDATE", "Digitization date");
if (!defined("_SCANUSER")) define("_SCANUSER", "Scan user");
if (!defined("_SCANLOCATION")) define("_SCANLOCATION", "Digitization location");
if (!defined("_SCANWKSATION")) define("_SCANWKSATION", "Digitization station");
if (!defined("_SCANBATCH")) define("_SCANBATCH", "Digitization batch");
if (!defined("_SOURCE")) define("_SOURCE", "Source");
if (!defined("_DOCLANGUAGE")) define("_DOCLANGUAGE", "Document language");
if (!defined("_MAILDATE")) define("_MAILDATE", "Mail date");
if (!defined("_MD5")) define("_MD5", "Digital print");
if (!defined("_WORK_BATCH")) define("_WORK_BATCH", "Loading batch");
if (!defined("_DONE")) define("_DONE","Made actions");
if (!defined("_ANSWER_TYPES_DONE")) define("_ANSWER_TYPES_DONE", "Type(s) of made responses");
if (!defined("_CLOSING_DATE")) define("_CLOSING_DATE", "Closing date");
if (!defined("_FULLTEXT")) define("_FULLTEXT", "Full text");
if (!defined("_FULLTEXT_HELP")) define("_FULLTEXT_HELP", "Search in the mails content");
if (!defined("_FULLTEXT_ERROR")) define("_FULLTEXT_ERROR", "Invalid enters for the full text search. If you put the sign "*", it has to be three characters at least before ans no signs like ,':!+");
if (!defined("_FILE_NOT_SEND")) define("_FILE_NOT_SEND", "The file wasn't sent");
if (!defined("_TRY_AGAIN")) define("_TRY_AGAIN", "Please, try again");
if (!defined("_INDEX_UPDATED")) define("_INDEX_UPDATED", "Updated index");
if (!defined("_DOC_DELETED")) define("_DOC_DELETED", "Deleted document");
if (!defined("_UPDATE_DOC_STATUS")) define("_UPDATE_DOC_STATUS", "Updated document status");

if (!defined("_DOCTYPE_MANDATORY")) define("_DOCTYPE_MANDATORY", "The doc type is mandatory");
if (! defined("_CHECK_FORM_OK"))    define("_CHECK_FORM_OK", "Form check OK");
if (! defined("_MISSING_FORMAT"))    define("_MISSING_FORMAT", " The format is missing");
if (! defined("_ERROR_RES_ID"))    define("_ERROR_RES_ID", "Problem during the res_id calculation");
if (! defined("_NEW_DOC_ADDED"))    define("_NEW_DOC_ADDED", "New recorded document");
if (! defined("_STATUS_UPDATED"))    define("_STATUS_UPDATED", "Updated status");

if (!defined("_QUICKLAUNCH")) define("_QUICKLAUNCH", "Short cut");
if (!defined("_SHOW_DETAILS_DOC")) define("_SHOW_DETAILS_DOC", "See the document details");
if (!defined("_VISIBLEBY")) define("_VISIBLEBY", "Visible by");
if (!defined("_VIEW_DOC_FULL")) define("_VIEW_DOC_FULL", "see the document");
if (!defined("_DETAILS_DOC_FULL")) define("_DETAILS_DOC_FULL", "See the document card");
if (!defined("_IDENTIFIER")) define("_IDENTIFIER", "Reference");
if (!defined("_CHRONO_NUMBER")) define("_CHRONO_NUMBER", "Chrono number");
if (!defined("_ATTACHMENT_TYPE")) define("_ATTACHMENT_TYPE", "Attachment type");
if (!defined("_NO_CHRONO_NUMBER_DEFINED")) define("_NO_CHRONO_NUMBER_DEFINED", "The chrono number is not defined");
if (!defined("_FOR_CONTACT_C")) define("_FOR_CONTACT_C", "For: ");
if (!defined("_TO_CONTACT_C")) define("_TO_CONTACT_C", "From : ");
if (!defined("_CASE_NUMBER_ERROR")) define ("_CASE_NUMBER_ERROR", "Case number is not on the right format: Integer is expected");
if (!defined("_NUMERO_GED")) define ("_NUMERO_GED", "EDM number is not on the right format : integer is expected");

if (!defined("_APPS_COMMENT")) define("_APPS_COMMENT", "Maarch application");
if (!defined("_CORE_COMMENT")) define("_CORE_COMMENT", "Framework core");
if (!defined("_CLEAR_FORM")) define("_CLEAR_FORM", "Clear the form");

if (!defined("_MAX_SIZE_UPLOAD_REACHED")) define("_MAX_SIZE_UPLOAD_REACHED", "File maximum size is exceeded");
if (!defined("_NOT_ALLOWED")) define("_NOT_ALLOWED", "Forbidden");
if (!defined("_CHOOSE_TITLE")) define("_CHOOSE_TITLE", "Choose a title");
if (!defined("_INDEXING_STATUSES")) define("_INDEXING_STATUSES", "Index toward the status");
if (!defined("_UNCHANGED")) define("_UNCHANGED", "Unchanged");
if (!defined("_LOAD_STATUSES_SESSION")) define("_LOAD_STATUSES_SESSION", "Load Status session");
if (!defined("_PARAM_AVAILABLE_STATUS_ON_GROUP_BASKETS")) define("_PARAM_AVAILABLE_STATUS_ON_GROUP_BASKETS", "Indexation status configuration");

/************** Reports ***************/
if (!defined("_USERS_LOGS")) define("_USERS_LOGS", "Access list to the application by agent");
if (!defined("_USERS_LOGS_DESC")) define("_USERS_LOGS_DESC", "Access list to the application by agent");
if (!defined("_PROCESS_DELAY_REPORT")) define("_PROCESS_DELAY_REPORT", "Average process period by mail type");
if (!defined("_PROCESS_DELAY_REPORT_DESC")) define("_PROCESS_DELAY_REPORT_DESC", "Average process period by mail type");
if (!defined("_MAIL_TYPOLOGY_REPORT")) define("_MAIL_TYPOLOGY_REPORT", "Mails typology by period");
if (!defined("_MAIL_TYPOLOGY_REPORT_DESC")) define("_MAIL_TYPOLOGY_REPORT_DESC", "Mails typology by period");
if (!defined("_MAIL_VOL_BY_CAT_REPORT")) define("_MAIL_VOL_BY_CAT_REPORT", "Mails volume by category, by period");
if (!defined("_MAIL_VOL_BY_CAT_REPORT_DESC")) define("_MAIL_VOL_BY_CAT_REPORT_DESC", "Mails volume by category, by period");
if (!defined("_SHOW_FORM_RESULT")) define("_SHOW_FORM_RESULT", "Display the result in the form of ");
if (!defined("_GRAPH")) define("_GRAPH", "Graph");
if (!defined("_ARRAY")) define("_ARRAY", "Table");
if (!defined("_SHOW_YEAR_GRAPH")) define("_SHOW_YEAR_GRAPH", "Display the result for the year");
if (!defined("_SHOW_GRAPH_MONTH")) define("_SHOW_GRAPH_MONTH", "Display the result for the months of");
if (!defined("_OF_THIS_YEAR")) define("_OF_THIS_YEAR", "Of this year");
if (!defined("_NB_MAILS1")) define("_NB_MAILS1", "Number of recorded mails");
if (!defined("_FOR_YEAR")) define("_FOR_YEAR", "For the year");
if (!defined("_FOR_MONTH")) define("_FOR_MONTH", "For the months of");
if (!defined("_N_DAYS")) define("_N_DAYS","Number of days");

/******************** Specific ************/
if (!defined("_PROJECT")) define("_PROJECT", "Folder");
if (!defined("_MARKET")) define("_MARKET", "Sub-folder");
if (!defined("_SEARCH_CUSTOMER")) define("_SEARCH_CUSTOMER", "Consultation folders/ sub-folders");
if (!defined("_SEARCH_CUSTOMER_TITLE")) define("_SEARCH_CUSTOMER_TITLE", "Search folders / sub-folders");
if (!defined("_TO_SEARCH_DEFINE_A_SEARCH_ADV")) define("_TO_SEARCH_DEFINE_A_SEARCH_ADV", "To start a search, you have to enter a folder number or a folder name or a sub-folder name");
if (!defined("_DAYS")) define("_DAYS", "Days");
if (!defined("_LAST_DAY")) define("_LAST_DAY", "Last day");
if (!defined("_CONTACT_NAME")) define("_CONTACT_NAME", " Contact's bill");
if (!defined("_AMOUNT")) define("_AMOUNT", "Bill amount");
if (!defined("_CUSTOMER")) define("_CUSTOMER", "Bill client");
if (!defined("_PO_NUMBER")) define("_PO_NUMBER", "Bill purchase order");
if (!defined("_INVOICE_NUMBER")) define("_INVOICE_NUMBER", "Bill number");


/******************** fulltext search Helper ************/
if (!defined("_HELP_GLOBAL_SEARCH")) define("_HELP_GLOBAL_SEARCH", "Search on the object, the title, the description, the document content or the EDM number");
if (!defined("_HELP_FULLTEXT_SEARCH")) define("_HELP_FULLTEXT_SEARCH", "Help on the full text search");
if (!defined("_GLOBAL_SEARCH")) define("_GLOBAL_SEARCH", "Global search");
if (!defined("_TIPS_FULLTEXT")) define("_TIPS_FULLTEXT", "Search tips");

if (!defined("_TIPS_KEYWORD1")) define("_TIPS_KEYWORD1", "To do a search with joker on severals characters");
if (!defined("_TIPS_KEYWORD2")) define("_TIPS_KEYWORD2", "To do a search on a words group, a sentence");

if (!defined("_TIPS_KEYWORD3")) define("_TIPS_KEYWORD3", "To do an approximate search");

if (!defined("_HELP_FULLTEXT_SEARCH_EXEMPLE1")) define("_HELP_FULLTEXT_SEARCH_EXEMPLE1", "<b>motor*</b> finds motorway and motor vehicle ");
if (!defined("_HELP_FULLTEXT_SEARCH_EXEMPLE2")) define("_HELP_FULLTEXT_SEARCH_EXEMPLE2", "<b>highway</b> finds the entire expression highway
                                                        <p> <b>without inverted comma</b> the search finds documents containing the words <b>high<BIG> and </BIG>way</b></p>
                                                        <p> Do not use  hyphens ! To search words as sub-prefecture, please enter the words <b>sub préfecture</b> separated by a space. ");
if (!defined("_HELP_FULLTEXT_SEARCH_EXEMPLE3")) define("_HELP_FULLTEXT_SEARCH_EXEMPLE3", "fast~ finds fest, fast");
if (!defined("_TIPS_FULLTEXT_TEXT")) define("_TIPS_FULLTEXT_TEXT", "The search can be done on numbers");
if (!defined("_CLOSE_MAIL")) define("_CLOSE_MAIL", "Close a mail");

/******************** Keywords Helper ************/
if (!defined("_HELP_KEYWORD0")) define("_HELP_KEYWORD0", "Connected user's ID");
if (!defined("_HELP_BY_CORE")) define("_HELP_BY_CORE", "Maarch Core keywords");

if (!defined("_FIRSTNAME_UPPERCASE")) define("_FIRSTNAME_UPPERCASE", "Firstname");
if (!defined("_TITLE_STATS_USER_LOG")) define("_TITLE_STATS_USER_LOG", "Application access");

if (!defined("_DELETE_DOC")) define("_DELETE_DOC", "Delete this document");
if (!defined("_THIS_DOC")) define("_THIS_DOC", "This document");
if (!defined("_MODIFY_DOC")) define("_MODIFY_DOC", "Modify informations");
if (!defined("_BACK_TO_WELCOME")) define("_BACK_TO_WELCOME", "Back to the home page");
if (!defined("_CLOSE_MAIL")) define("_CLOSE_MAIL", "Close a mail");


/************** Réouverture courrier **************/
if (!defined("_MAIL_SENTENCE2")) define("_MAIL_SENTENCE2", "Entering the chronopost number or the EDM number of the document, you will modify the mail status.");
if (!defined("_MAIL_SENTENCE3")) define("_MAIL_SENTENCE3", "This function has to purpose to change the status of the mail.");
if (!defined("_MAIL_SENTENCE4")) define("_MAIL_SENTENCE4", "The mail will be available in the users' basket to whom he was affected according to the status that you have defined.");
if (!defined("_MAIL_SENTENCE5")) define("_MAIL_SENTENCE5", "of home/ welcome after the mail re-opening.");
if (!defined("_ENTER_DOC_ID")) define("_ENTER_DOC_ID", " or the EDM number of the document");
if (!defined("_ENTER_REF_ID")) define("_ENTER_REF_ID", "Enter a chronopost number");
if (!defined("_ENTER_REF_ID_OR_GED_ID")) define("_ENTER_REF_ID_OR_GED_ID", "Please enter the chrono number or the EDM number of the document");
if (!defined("_REF_ID")) define("_REF_ID", "n° chrono");
if (!defined("_GED_ID")) define("_GED_ID", "EDM number");
if (!defined("_TO_KNOW_ID")) define("_TO_KNOW_ID", "To know the document ID, do a search or ask to the operator");

if (!defined("_REOPEN_MAIL")) define("_REOPEN_MAIL", "Change of status mail");
if (!defined("_REOPEN_THIS_MAIL")) define("_REOPEN_THIS_MAIL", "Mail re opening");
if (!defined("_MODIFICATION_FROM_THIS_MAIL")) define("_MODIFICATION_FROM_THIS_MAIL", "Mail modification");
if (!defined("_MODIFICATION_OF_THE_STATUS_FROM_THIS_MAIL")) define("_MODIFICATION_OF_THE_STATUS_FROM_THIS_MAIL", "Modification of the mail status by the administration. Passage to the status ");
if (!defined("_OWNER")) define("_OWNER", "Owner");
if (!defined("_CONTACT_OWNER_COMMENT")) define("_CONTACT_OWNER_COMMENT", "Leave space to make this contact public.");

if (!defined("_OPT_INDEXES")) define("_OPT_INDEXES", "further information");
if (!defined("_COMPLEMENTARY_OPT"))
    define("_COMPLEMENTARY_OPT", "Further options");
if (!defined("_NUM_BETWEEN")) define("_NUM_BETWEEN", "Between");
if (!defined("_MUST_CORRECT_ERRORS")) define("_MUST_CORRECT_ERRORS", "You have to correct the following errors ");
if (!defined("_CLICK_HERE_TO_CORRECT")) define("_CLICK_HERE_TO_CORRECT", "Click here to correct them");

if (!defined("_FILETYPE")) define("_FILETYPE", "File type");
if (!defined("_WARNING")) define("_WARNING", "Warning ");
if (!defined("_STRING")) define("_STRING", "Character channel");
if (!defined("_INTEGER")) define("_INTEGER", "Integer");
if (!defined("_FLOAT")) define("_FLOAT", "Floating");
if (!defined("_CUSTOM_T1")) define("_CUSTOM_T1", "Text field 1");
if (!defined("_CUSTOM_T2")) define("_CUSTOM_T2", "Text field 2");
if (!defined("_CUSTOM_D1")) define("_CUSTOM_D1", "Date field");
if (!defined("_CUSTOM_N1")) define("_CUSTOM_N1", "Integer field");
if (!defined("_CUSTOM_F1")) define("_CUSTOM_F1", "Floating field");

if (!defined("_ITEM_NOT_IN_LIST")) define("_ITEM_NOT_IN_LIST", "Missing element from the allowed values list");
if (!defined("_PB_WITH_FINGERPRINT_OF_DOCUMENT")) define("_PB_WITH_FINGERPRINT_OF_DOCUMENT", "The initial digital print of the document doesn't correspond with the one of the reference document");
if (!defined("_MISSING")) define("_MISSING", "Missing");
if (!defined("_NATURE")) define("_NATURE", "Nature");
if (!defined("_NO_DEFINED_TREES")) define("_NO_DEFINED_TREES", "No defined tree");

if (!defined("_IF_CHECKS_MANDATORY_MUST_CHECK_USE")) define("_IF_CHECKS_MANDATORY_MUST_CHECK_USE", "if you click on a mandatory field, you alsohave to tick on the used case");
if (!defined("_SEARCH_DOC")) define("_SEARCH_DOC", "Search a document");
if (!defined("_DOCSERVER_COPY_ERROR")) define("_DOCSERVER_COPY_ERROR", " Error during the copy on the docserver");
if (!defined("_MAKE_NEW_SEARCH")) define("_MAKE_NEW_SEARCH", "Do a new search");
if (!defined("_NO_PAGE")) define("_NO_PAGE", "No page");
if (!defined("_VALIDATE_QUALIF")) define("_VALIDATE_QUALIF", "Validation / certification");
if (!defined("_DB_CONNEXION_ERROR")) define("_DB_CONNEXION_ERROR", "Connection error to the database");
if (!defined("_DATABASE_SERVER")) define("_DATABASE_SERVER", "Database server");
if (!defined("_DB_PORT")) define("_DB_PORT", "Port");
if (!defined("_DB_TYPE")) define("_DB_TYPE", "Type");
if (!defined("_DB_USER")) define("_DB_USER", "User");
if (!defined("_DATABASE")) define("_DATABASE", "Base");
if (!defined("_TREE_ROOT")) define("_TREE_ROOT", "Root");
if (!defined("_MODE")) define("_MODE", "Mode");
if (!defined("_TITLE_STATS_CHOICE_PERIOD"))  define("_TITLE_STATS_CHOICE_PERIOD","For a period");


/******************** Authentification method  ************/

if (!defined("_STANDARD_LOGIN")) define("_STANDARD_LOGIN", "Maarch's authentication");
if (!defined("_CAS_LOGIN")) define("_CAS_LOGIN", "CAS authentication");
if (!defined("_ACTIVEX_LOGIN")) define("_ACTIVEX_LOGIN", "Authentification Ms Internet Explorer - ActiveX");
if (!defined("_HOW_CAN_I_LOGIN")) define("_HOW_CAN_I_LOGIN", "I can not log on...");
if (!defined("_CONNECT")) define("_CONNECT", "To log on");
if (!defined("_LOGIN_MODE")) define("_LOGIN_MODE", "Type of authentication");
if (!defined("_SSO_LOGIN")) define("_SSO_LOGIN", "Login via SSO");
if (!defined("_LDAP")) define("_LDAP", "LDAP directory ");


/******** Admin groups **********/

if (!defined("_WHERE_CLAUSE_TARGET")) define("_WHERE_CLAUSE_TARGET", "Where clause target");
if (!defined("_WHERE_TARGET")) define("_WHERE_TARGET", "Target");
if (!defined("_CLASS_SCHEME")) define("_CLASS_SCHEME", "File plan");
if (!defined("_DOCS")) define("_DOCS", "Documents");
if (!defined("_GO_MANAGE_USER")) define("_GO_MANAGE_USER", "Modify");
if (!defined("_GO_MANAGE_DOCSERVER")) define("_GO_MANAGE_DOCSERVER", "Modify");
if (!defined("_TASKS")) define("_TASKS", "Available actions on the documents");
if (!defined("_PERIOD")) define("_PERIOD", "Period");
if (!defined("_COMMENTS_MANDATORY")) define("_COMMENTS_MANDATORY", "Mandatory description");

/******* Security Bitmask label ********/

if (!defined("_ADD_RECORD_LABEL")) define ("_ADD_RECORD_LABEL","Add a document");
if (!defined("_DATA_MODIFICATION_LABEL")) define ("_DATA_MODIFICATION_LABEL","Modify");
if (!defined("_DELETE_RECORD_LABEL")) define ("_DELETE_RECORD_LABEL","Delete");
if (!defined("_VIEW_LOG_LABEL")) define ("_VIEW_LOG_LABEL","See the logs");


if (!defined("_PLUS")) define("_PLUS", "Plus");
if (!defined("_MINUS")) define("_MINUS", "Less");


/*********ADMIN DOCSERVERS**********************/
if (!defined("_MANAGE_DOCSERVERS"))  define("_MANAGE_DOCSERVERS", "Manage the storage zone ");
if (!defined("_MANAGE_DOCSERVERS_DESC"))  define("_MANAGE_DOCSERVERS_DESC", "Add, modify, delete storage zone ");
if (!defined("_MANAGE_DOCSERVERS_LOCATIONS"))  define("_MANAGE_DOCSERVERS_LOCATIONS", "Manage the locations of documents storage ");
if (!defined("_MANAGE_DOCSERVERS_LOCATIONS_DESC"))  define("_MANAGE_DOCSERVERS_LOCATIONS_DESC", "Add, delete, modify the locations of documents storage ");
if (!defined("_MANAGE_DOCSERVER_TYPES"))  define("_MANAGE_DOCSERVER_TYPES", "Manage the types of storage zone ");
if (!defined("_MANAGE_DOCSERVER_TYPES_DESC"))  define("_MANAGE_DOCSERVER_TYPES_DESC", "Add, modify, delete the types of storage zones ");
if (!defined("_ADMIN_DOCSERVERS"))  define("_ADMIN_DOCSERVERS", " Administration of storage zones");
if (!defined("_ADMIN_DOCSERVERS_DESC"))  define("_ADMIN_DOCSERVERS_DESC", " Add, modify, delete storage zones");
if (!defined("_DOCSERVER_ID"))  define("_DOCSERVER_ID", "Document server ID");

/**********DOCSERVERS****************/
if (!defined("_YOU_CANNOT_DELETE")) define("_YOU_CANNOT_DELETE", "Impossible deletion");
if (!defined("_UNKNOWN")) define("_UNKNOWN", "Unknown");
if (!defined("_UNKOWN")) define("_UNKOWN", "is unknown");
if (!defined("_YOU_CANNOT_DISABLE")) define("_YOU_CANNOT_DISABLE", "Impossible suspension");
if (!defined("_DOCSERVER_TYPE_DISABLED")) define("_DOCSERVER_TYPE_DISABLED", "Suspended type of storage zone");
if (!defined("_SIZE_LIMIT_UNAPPROACHABLE")) define("_SIZE_LIMIT_UNAPPROACHABLE", "Size of inaccessible limit");
if (!defined("_DOCSERVER_TYPE_ENABLED")) define("_DOCSERVER_TYPE_ENABLED", "Type of active storage zone");
if (!defined("_SIZE_LIMIT_LESS_THAN_ACTUAL_SIZE")) define("_SIZE_LIMIT_LESS_THAN_ACTUAL_SIZE", "Limit size inferior to the current size");
if (!defined("_THE_DOCSERVER_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS")) define("_THE_DOCSERVER_DOES_NOT_HAVE_THE_ADEQUATE_RIGHTS", "This storage zone has not the necessary rights...");
if (!defined("_DOCSERVER_DISABLED")) define("_DOCSERVER_DISABLED", "Suspended storage zone");
if (!defined("_DOCSERVER_ENABLED")) define("_DOCSERVER_ENABLED", "Active storage zone");
if (!defined("_ALREADY_EXISTS_FOR_THIS_TYPE_OF_DOCSERVER")) define("_ALREADY_EXISTS_FOR_THIS_TYPE_OF_DOCSERVER", "Already exists for this type");
if (!defined("_DOCSERVER_LOCATION_ENABLED")) define("_DOCSERVER_LOCATION_ENABLED", "The storage location is active");
if (!defined("_LINK_EXISTS"))    define("_LINK_EXISTS", "A link with an other object exists");


/***************DOCSERVERS TYPES*************************************/
if (!defined("_DOCSERVER_TYPE_ID"))  define("_DOCSERVER_TYPE_ID", "ID of the type of storage zone ");
if (!defined("_DOCSERVER_TYPE"))  define("_DOCSERVER_TYPE", "Zone type ");
if (!defined("_DOCSERVER_TYPES_LIST"))  define("_DOCSERVER_TYPES_LIST", "List of the type of storage zone");
if (!defined("_ALL_DOCSERVER_TYPES"))  define("_ALL_DOCSERVER_TYPES", "Show all");
if (!defined("_DOCSERVER_TYPE_LABEL"))  define("_DOCSERVER_TYPE_LABEL", "Type wording of the storage zone ");
if (!defined("_DOCSERVER_TYPES"))  define("_DOCSERVER_TYPES", "Type(s) of storage zone ");
if (!defined("_IS_CONTAINER"))  define("_IS_CONTAINER", "Container");
if (!defined("_IS_COMPRESSED"))  define("_IS_COMPRESSED", "Compressed");
if (!defined("_IS_META"))  define("_IS_META", "Contains the metadatas");
if (!defined("_IS_LOGGED"))  define("_IS_LOGGED", "Contains the logs");
if (!defined("_IS_SIGNED"))  define("_IS_SIGNED", "Contains a print");
if (!defined("_COMPRESS_MODE"))  define("_COMPRESS_MODE", "Compression mode");
if (!defined("_META_TEMPLATE"))  define("_META_TEMPLATE", "Metadatas template");
if (!defined("_LOG_TEMPLATE"))  define("_LOG_TEMPLATE", "Log model");
if (!defined("_FINGERPRINT_MODE"))  define("_FINGERPRINT_MODE", "Calculation algorithm of print");
if (!defined("_CONTAINER_MAX_NUMBER"))  define("_CONTAINER_MAX_NUMBER", "Largest size of the container");
if (!defined("_DOCSERVER_TYPE_MODIFICATION"))  define("_DOCSERVER_TYPE_MODIFICATION", "Modification of the type of storage zone ");
if (!defined("_DOCSERVER_TYPE_ADDITION"))  define("_DOCSERVER_TYPE_ADDITION", "Add a type of storage zone");
if (!defined("_DOCSERVER_TYPE_ADDED"))  define("_DOCSERVER_TYPE_ADDED", "Added type of storage zone");
if (!defined("_DOCSERVER_TYPE_UPDATED"))  define("_DOCSERVER_TYPE_UPDATED", "Updated type of storage zone ");
if (!defined("_DOCSERVER_TYPE_DELETED"))  define("_DOCSERVER_TYPE_DELETED", "Deleted type of storage zone ");
if (!defined("_NOT_CONTAINER"))  define("_NOT_CONTAINER", "Not a container");
if (!defined("_CONTAINER"))  define("_CONTAINER", "A container");
if (!defined("_NOT_COMPRESSED"))  define("_NOT_COMPRESSED", "No compressed");
if (!defined("_COMPRESSED"))  define("_COMPRESSED", "Compressed");
if (!defined("_COMPRESSION_MODE"))  define("_COMPRESSION_MODE", "Compression mode");
if (!defined("_GZIP_COMPRESSION_MODE"))  define("_GZIP_COMPRESSION_MODE", "GZIP Compression mode (tar.gz) is only available for the reading");

/***************DOCSERVERS*********************************/
if (!defined("_DOCSERVERS"))  define("_DOCSERVERS", "Storage zone(s) ");
if (!defined("_DEVICE_LABEL"))  define("_DEVICE_LABEL", "Device wording ");
if (!defined("_SIZE_FORMAT"))  define("_SIZE_FORMAT", "Size format");
if (!defined("_SIZE_LIMIT"))  define("_SIZE_LIMIT", "Maximal size ");
if (!defined("_ACTUAL_SIZE"))  define("_ACTUAL_SIZE", "Current size");
if (!defined("_COLL_ID"))  define("_COLL_ID", "Collection ID");
if (!defined("_PATH_TEMPLATE"))  define("_PATH_TEMPLATE", "File path");
if (!defined("_ADR_PRIORITY"))  define("_ADR_PRIORITY", "Sequence priority of the storage zone");
if (!defined("_IS_READONLY"))  define("_IS_READONLY", "Reading only allowed");
if (!defined("_PERCENTAGE_FULL"))  define("_PERCENTAGE_FULL", "Percentage of filling");
if (!defined("_PATH_OF_DOCSERVER_UNAPPROACHABLE"))  define("_PATH_OF_DOCSERVER_UNAPPROACHABLE", "Inaccessible path ");
if (!defined("_ALL_DOCSERVERS"))  define("_ALL_DOCSERVERS", "Show all ");
if (!defined("_DOCSERVER"))  define("_DOCSERVER", "A docserver");
if (!defined("_DOCSERVER_MODIFICATION"))  define("_DOCSERVER_MODIFICATION", "Modification of the storage zone");
if (!defined("_DOCSERVER_ADDITION"))  define("_DOCSERVER_ADDITION", "Add a storage zone");
if (!defined("_DOCSERVER_UPDATED"))  define("_DOCSERVER_UPDATED", "Updated storage zone");
if (!defined("_DOCSERVER_DELETED"))  define("_DOCSERVER_DELETED", "Deleted storage zone");
if (!defined("_DOCSERVER_ADDED"))  define("_DOCSERVER_ADDED", "Added storage zone");
if (!defined("_DOCSERVERS_LIST"))  define("_DOCSERVERS_LIST", "List of storage zone ");
if (!defined("_GB"))  define("_GB", "Gigabyte ");
if (!defined("_TB"))  define("_TB", "Terabyte ");
if (!defined("_MB"))  define("_MB", "Megabyte ");
if (!defined("_SIZE_LIMIT_NUMBER")) define("_SIZE_LIMIT_NUMBER", "Limit size");
if (!defined("_DOCSERVER_ATTACHED_TO_RES_X")) define("_DOCSERVER_ATTACHED_TO_RES_X", "Documents are stored on this storage space");

/************DOCSERVER LOCATIONS******************************/
if (!defined("_DOCSERVER_LOCATION_ID"))  define("_DOCSERVER_LOCATION_ID", "ID of the storage location ");
if (!defined("_DOCSERVER_LOCATIONS"))  define("_DOCSERVER_LOCATIONS", "Storage location(s) ");
if (!defined("_IPV4"))  define("_IPV4", "IPv4 Address");
if (!defined("_IPV6"))  define("_IPV6", "IPv6 Address");
if (!defined("_NET_DOMAIN"))  define("_NET_DOMAIN", "Domain");
if (!defined("_MASK"))  define("_MASK", "Mask");
if (!defined("_NET_LINK"))  define("_NET_LINK", "Front end URL");
if (!defined("_DOCSERVER_LOCATION_ADDITION"))  define("_DOCSERVER_LOCATION_ADDITION", "Add a storage location ");
if (!defined("_DOCSERVER_LOCATION_MODIFICATION"))  define("_DOCSERVER_LOCATION_MODIFICATION", "Modification of the storage location");
if (!defined("_ALL_DOCSERVER_LOCATIONS"))  define("_ALL_DOCSERVER_LOCATIONS", "Show all");
if (!defined("_DOCSERVER_LOCATIONS_LIST"))  define("_DOCSERVER_LOCATIONS_LIST", "List of storage locations");
if (!defined("_DOCSERVER_LOCATION"))  define("_DOCSERVER_LOCATION", "A storage location");
if (!defined("_DOCSERVER_LOCATION_UPDATED"))  define("_DOCSERVER_LOCATION_UPDATED", "Updated storage location");
if (!defined("_DOCSERVER_LOCATION_ADDED"))  define("_DOCSERVER_LOCATION_ADDED", "Added storage location");
if (!defined("_DOCSERVER_LOCATION_DELETED"))  define("_DOCSERVER_LOCATION_DELETED", "Deleted storage location");
if (!defined("_DOCSERVER_LOCATION_DISABLED"))  define("_DOCSERVER_LOCATION_DISABLED", "Deactivated storage location");
if (!defined("_DOCSERVER_LOCATION_ENABLED"))  define("_DOCSERVER_LOCATION_ENABLED", "Activated storage location");
if (!defined("_IP_V4_ADRESS_NOT_VALID")) define("_IP_V4_ADRESS_NOT_VALID", "Inaccessible IPV4 address");
if (!defined("_IP_V4_FORMAT_NOT_VALID")) define("_IP_V4_FORMAT_NOT_VALID", "IPV4 address wrong format");
if (!defined("_IP_V6_NOT_VALID")) define("_IP_V6_NOT_VALID", "IPV6 address wrong format");
if (!defined("_MASK_NOT_VALID")) define("_MASK_NOT_VALID", "Invalidated mask");


/************FAILOVER******************************/
if (!defined("_FAILOVER"))  define("_FAILOVER", "Resumption on error");
if (!defined("_FILE_NOT_EXISTS_ON_THE_SERVER"))  define("_FILE_NOT_EXISTS_ON_THE_SERVER", "The file doesn't exist on the docserver");
if (!defined("_NO_RIGHT_ON_RESOURCE_OR_RESOURCE_NOT_EXISTS"))  define("_NO_RIGHT_ON_RESOURCE_OR_RESOURCE_NOT_EXISTS", "No right on the asked resource or it is not available");

if (!defined("_PROCESS_DELAY"))  define("_PROCESS_DELAY", "Processing period");
if (!defined("_ALERT_DELAY_1"))  define("_ALERT_DELAY_1", "1st alert period");
if (!defined("_ALERT_DELAY_2"))  define("_ALERT_DELAY_2", "2nd alert period");

if (!defined("_ERROR_PARAMETERS_FUNCTION"))  define("_ERROR_PARAMETERS_FUNCTION", "Error of configuration...");
if (!defined("_SYNTAX_OK"))  define("_SYNTAX_OK", "Syntax OK");

/************TECHNICAL INFOS******************************/
if (!defined("_TECHNICAL_INFORMATIONS"))  define("_TECHNICAL_INFORMATIONS", "Technical information");
if (!defined("_VIEW_TECHNICAL_INFORMATIONS"))  define("_VIEW_TECHNICAL_INFORMATIONS", "See technical information");
if (!defined("_SOURCE_FILE_PROPERTIES")) define("_SOURCE_FILE_PROPERTIES", "Properties of source file");
if (!defined("_FINGERPRINT"))  define("_FINGERPRINT", "Digital print");
if (!defined("_OFFSET"))  define("_OFFSET", "Offset");
if (!defined("_SETUP"))  define("_SETUP", "Set up");

if (!defined("_PARAM_MLB_DOCTYPES"))    define("_PARAM_MLB_DOCTYPES", "Documents types configuration ");
if (!defined("_PARAM_MLB_DOCTYPES_DESC"))    define("_PARAM_MLB_DOCTYPES_DESC", "Doctypes configuration ");
if (!defined("_WELCOME_TEXT_LOAD"))    define("_WELCOME_TEXT_LOAD", "Text loading of the home page");
if (!defined("_REOPEN_MAIL_DESC"))    define("_REOPEN_MAIL_DESC", "Mail re-opening");
if (!defined("_WRONG_FUNCTION_OR_WRONG_PARAMETERS"))    define("_WRONG_FUNCTION_OR_WRONG_PARAMETERS", "Wrong call or wrong configuration");
if (!defined("_INDEXING_INSERT_ERROR"))    define("_INDEXING_INSERT_ERROR", "Indexation : error during the insertion");
if (!defined("_LOGIN_HISTORY"))    define("_LOGIN_HISTORY", "User's connection");
if (!defined("_LOGOUT_HISTORY"))    define("_LOGOUT_HISTORY", "User's disconnection");
if (!defined("_TO_MASTER_DOCUMENT"))    define("_TO_MASTER_DOCUMENT", "To master document number");

//print details
if (!defined("_DETAILS_PRINT"))    define("_DETAILS_PRINT", "Connection card number");
if (!defined("_NOTES_1"))    define("_NOTES_1", "Example Department notes 1");
if (!defined("_NOTES_2"))    define("_NOTES_2", "Example Department notes 2");
if (!defined("_NOTES_3"))    define("_NOTES_3", "Example Department notes 3");
if (!defined("_WHERE_CLAUSE_NOT_SECURE"))    define("_WHERE_CLAUSE_NOT_SECURE","No secured where clause");
if (!defined("_SQL_QUERY_NOT_SECURE"))    define("_SQL_QUERY_NOT_SECURE","SQL request is not secured");

//service to put doc on validation from details page
if (!defined("_PUT_DOC_ON_VALIDATION_FROM_DETAILS"))    define("_PUT_DOC_ON_VALIDATION_FROM_DETAILS","Send the document for validation from the detailed page");
if (!defined("_PUT_DOC_ON_VALIDATION"))    define("_PUT_DOC_ON_VALIDATION","Send the document for validation");
if (!defined("_REALLY_PUT_DOC_ON_VALIDATION"))    define("_REALLY_PUT_DOC_ON_VALIDATION","Confirm the sending for validation");

/*******************************************************************************
 * RA_CODE
*******************************************************************************/
if (!defined("_ASK_RA_CODE_1"))    define("_ASK_RA_CODE_1", "A mail will be sent to the address: ");
if (!defined("_ASK_RA_CODE_2"))    define("_ASK_RA_CODE_2", "Once the code be known, please try again your connection attempt.");
if (!defined("_CONFIRM_ASK_RA_CODE_1"))    define("_CONFIRM_ASK_RA_CODE_1", "Good morning, ");
if (!defined("_CONFIRM_ASK_RA_CODE_2"))    define("_CONFIRM_ASK_RA_CODE_2", "Your distant connection code for Maarch application is : ");
if (!defined("_CONFIRM_ASK_RA_CODE_3"))    define("_CONFIRM_ASK_RA_CODE_3", "This code remain valid until ");
if (!defined("_CONFIRM_ASK_RA_CODE_4"))    define("_CONFIRM_ASK_RA_CODE_4", "To log on, ");
if (!defined("_CONFIRM_ASK_RA_CODE_5"))    define("_CONFIRM_ASK_RA_CODE_5", "click here");
if (!defined("_CONFIRM_ASK_RA_CODE_6"))    define("_CONFIRM_ASK_RA_CODE_6", "Your Maarch connection code");
if (!defined("_CONFIRM_ASK_RA_CODE_7"))    define("_CONFIRM_ASK_RA_CODE_7", "A mail has been sent to your email address");
if (!defined("_CONFIRM_ASK_RA_CODE_8"))    define("_CONFIRM_ASK_RA_CODE_8", "Reconnection attempt");
if (!defined("_TRYING_TO_CONNECT_FROM_NOT_ALLOWED_IP"))    define("_TRYING_TO_CONNECT_FROM_NOT_ALLOWED_IP", "you attempt to connect from a no identified place.");
if (!defined("_PLEASE_ENTER_YOUR_RA_CODE"))    define("_PLEASE_ENTER_YOUR_RA_CODE", "Please enter the further access code.");
if (!defined("_ASK_AN_RA_CODE"))    define("_ASK_AN_RA_CODE", "Ask an access code");
if (!defined("_RA_CODE_1"))    define("_RA_CODE_1", "Further code");
if (!defined("_CAN_T_CONNECT_WITH_THIS_IP"))    define("_CAN_T_CONNECT_WITH_THIS_IP", "You cannot connect from a no identified place.");


if (!defined("_LOADING_INFORMATIONS"))    define("_LOADING_INFORMATIONS", "Information loading");
if (!defined("_BY"))    define("_BY", "by");
if (!defined("_REVERSE_CHECK"))    define("_REVERSE_CHECK", "Reverse the selection");
if (!defined("_CHECK_ALL"))    define("_CHECK_ALL", "Tick all");
if (!defined("_UNCHECK_ALL"))    define("_UNCHECK_ALL", "/ untick");

//EXPORT
if (!defined("_EXPORT_LIST"))    define("_EXPORT_LIST", "Export");

/******************** Action put in copy ************/
if (!defined("_ADD_LINKS"))    define("_ADD_LINKS", "Add connection");
if (!defined("_PUT_IN_COPY"))    define("_PUT_IN_COPY", "Add on copy");
if (!defined("_POWERED_BY"))    define("_POWERED_BY", "Powered by Maarch&trade;.");
if (!defined("_LINK_TO_DOC"))    define("_LINK_TO_DOC", "link to a document");
if (!defined("_LINK_REFERENCE"))    define("_LINK_REFERENCE", "To link, you have to choose an existing document");
if (!defined("_LINKED_TO"))    define("_LINKED_TO", "Linked to a document ");
if (!defined("_NOW_LINK_WITH_THIS_ONE"))    define("_NOW_LINK_WITH_THIS_ONE", " is now linked to this document");
if (!defined("_ARE_NOW_LINK_WITH_THIS_ONE"))    define("_ARE_NOW_LINK_WITH_THIS_ONE", " are now linked to this document");
if (!defined("_ARE_NOW_LINK_WITH_MANY_DOCUMENTS"))    define("_ARE_NOW_LINK_WITH_MANY_DOCUMENTS", " are link to many documents");
if (!defined("_LINK_TAB"))    define("_LINK_TAB", "Connections");
if (!defined("_LINK_DESC_FOR"))    define("_LINK_DESC_FOR", "Document(s) linked to this documents");
if (!defined("_LINK_ASC_FOR"))    define("_LINK_ASC_FOR", "Document(s) which are linked to this document");
if (!defined("_ADD_A_LINK"))    define("_ADD_A_LINK", "Add a connection");
if (!defined("_LINK_ACTION"))    define("_LINK_ACTION", "Link");
if (!defined("_LINK_ALREADY_EXISTS"))    define("_LINK_ALREADY_EXISTS", "This connection already exists");
if (!defined("_THE_DOCUMENT_LINK"))    define("_THE_DOCUMENT_LINK", "The document ");
if (!defined("_THE_DOCUMENTS_LINK"))    define("_THE_DOCUMENTS_LINK", "The documents ");
if (!defined("_LINK_TO_THE_DOCUMENT"))    define("_LINK_TO_THE_DOCUMENT", "The document link ");
if (!defined("_NO_LINK_WITH_THIS_ONE"))    define("_NO_LINK_WITH_THIS_ONE", "is no more linked to this one");
if (!defined("_LINK_DELETED"))    define("_LINK_DELETED", "was deleted");

/******************** Versions ************/
if (!defined("_VERSIONS"))    define("_VERSIONS", "Versions");
if (!defined("_CREATE_NEW_VERSION"))    define("_CREATE_NEW_VERSION", "Create a new version of the document");
if (!defined("_CONTENT_MANAGEMENT_COMMENT"))    define("_CONTENT_MANAGEMENT_COMMENT", "Management of document versions");
if (!defined("_VIEW_VERSIONS"))    define("_VIEW_VERSIONS", "See the documents versions");
if (!defined("_ADD_NEW_VERSION"))    define("_ADD_NEW_VERSION", "Add a new version of the document");
if (!defined("_VIEW_ORIGINAL"))    define("_VIEW_ORIGINAL", "See the original document");
if (!defined("_PJ"))    define("_PJ", "Attachments");

/******************** Liste avec réponses ************/
if (!defined("_CONSULT"))    define("_CONSULT", "Consult");
if (!defined("_DOCUMENTS_LIST_WITH_ATTACHMENTS"))    define("_DOCUMENTS_LIST_WITH_ATTACHMENTS", "List with filters and responses");
if (!defined("_DOCUMENTS_LIST_BY_MODIFICATION"))
    define("_DOCUMENTS_LIST_BY_MODIFICATION", "Filtered list by modification date");
if (!defined("_QUALIFY_FIRST"))    define("_QUALIFY_FIRST", "the detailed card is empty because the document has to be certified");

/******************** persistent mode ************/
if (!defined("_SET_PERSISTENT_MODE_ON"))    define("_SET_PERSISTENT_MODE_ON", "Activate the persistence");
if (!defined("_SET_PERSISTENT_MODE_OFF"))    define("_SET_PERSISTENT_MODE_OFF", "Deactivate the persistence");

/************************ Lists ************************/
if (!defined("_ADMIN_LISTS"))                       define("_ADMIN_LISTS", "Lists management");
if (!defined("_ADMIN_LISTS_DESC"))                  define("_ADMIN_LISTS_DESC", "Define results lists.");
if (!defined("_LISTS_LIST"))                        define("_LISTS_LIST", "Lists");
if (!defined("_LISTS_COMMENT"))                     define("_LISTS_COMMENT", "Lists management");
if (!defined("_LOCK_LIST"))                         define("_LOCK_LIST", "Lock list");
if (!defined("_LOCKED"))                            define("_LOCKED", "Locked");
if (!defined("_PRINCIPAL_LIST"))                    define("_PRINCIPAL_LIST", "Main list");
if (!defined("_SUBLIST"))                           define("_SUBLIST", "Sub-list");
if (!defined("_TOGGLE"))                            define("_TOGGLE", "Show / Mask");
if (!defined("_HELP_LIST_KEYWORDS"))                define("_HELP_LIST_KEYWORDS", "Help on locking clause");
if (!defined("_HELP_LIST_KEYWORD1"))                define("_HELP_LIST_KEYWORD1", "<b>The operator of comparaison</b> allow to compare two values: a == b :Egal, a <> b ou a != b :Different, a < b : Smaller than, a > b : Bigger.");
if (!defined("_HELP_LIST_KEYWORD2"))                define("_HELP_LIST_KEYWORD2", "<b>The logical operators</b>: a && b: AND ( And )	TRUE if a AND b are true, a || b OR ( Or )	True if a OR b is true.");
if (!defined("_HELP_LIST_KEYWORD_EXEMPLE_TITLE"))   define("_HELP_LIST_KEYWORD_EXEMPLE_TITLE", "Locking condition of the list/sub-list ligns.<br><br>The parameter addition<b>@@nom_du_champ@@</b> allows to refer to the value of the criteria field value. It's possible to insert many different @@nom_du_champ@@ in the déclaration.");
if (!defined("_HELP_LIST_KEYWORD_EXEMPLE"))         define("_HELP_LIST_KEYWORD_EXEMPLE", "Ex: @@status@@ <> 'NEW' || '@@type_id@@ <> '10'<br><br>Ex: (@@doctype_secon_level =='50' && @@dest_user@@=='bblier\") || doctype_secon_level == '10'");
if (!defined("_SYNTAX_ERROR_LOCK_CLAUSE"))          define("_SYNTAX_ERROR_LOCK_CLAUSE", "Error in the syntax of the locking clause");
if (!defined("_DOCUMENTS_LIST_WITH_FILTERS"))       define("_DOCUMENTS_LIST_WITH_FILTERS", "List with filters"); //liste
if (!defined("_DOCUMENTS_LIST_WITH_ATTACHMENTS"))   define("_DOCUMENTS_LIST_WITH_ATTACHMENTS", "Lists with filters and responses"); //liste
if (!defined("_DOCUMENTS_LIST_COPIES"))             define("_DOCUMENTS_LIST_COPIES", "List of copies"); //liste + template
if (!defined("_DOCUMENTS_LIST_EXTEND"))             define("_DOCUMENTS_LIST_EXTEND", "Expanded list"); //liste + template
if (!defined("_DOCUMENTS_LIST"))                    define("_DOCUMENTS_LIST", "Simple list"); //template
if (!defined("_CASES_LIST"))                        define("_CASES_LIST", "Cases list"); //template
if (!defined("_DOCUMENTS_LIST_SEARCH"))             define("_DOCUMENTS_LIST_SEARCH", "Expanded list"); //template
if (!defined("_CLICK_ICON_TO_TOGGLE"))              define("_CLICK_ICON_TO_TOGGLE", "Click on the icon fo show/ mask ");
if (!defined("_SHOW"))                              define("_SHOW", "Display");
if (!defined("_LINES"))                             define("_LINES", " ligns");
if (!defined("_NO_TEMPLATE_FILE_AVAILABLE"))        define("_NO_TEMPLATE_FILE_AVAILABLE", "Unavailable template");

if (!defined("_GED"))    define("_GED", "EDM number");

//EMAIL INDEXES
if (!defined("_EMAIL_FROM_ADDRESS")) define("_EMAIL_FROM_ADDRESS", "Email from");
if (!defined("_EMAIL_TO_ADDRESS")) define("_EMAIL_TO_ADDRESS", "Email for");
if (!defined("_EMAIL_CC_ADDRESS")) define("_EMAIL_CC_ADDRESS", "Email copie");
if (!defined("_EMAIL_ID")) define("_EMAIL_ID", "Email ID");
if (!defined("_EMAIL_ACCOUNT")) define("_EMAIL_ACCOUNT", "Email account");
if (!defined("_HELP_KEYWORD_EMAIL")) define("_HELP_KEYWORD_EMAIL", "Email of the connected user");

if (!defined("_INITIATOR")) define("_INITIATOR", "Initiating department");

if (!defined("_QUALIF_BUSINESS")) define("_QUALIF_BUSINESS", "Documents certification of the business collection");
if (!defined("_PROCESS_BUSINESS")) define("_PROCESS_BUSINESS", "Document processing of the business collection");
if (!defined("_BUSINESS_LIST")) define("_BUSINESS_LIST", "List of business documents");

if (!defined("_INDEXING_BUSINESS")) define("_INDEXING_BUSINESS", "[business] Save a document");
if (!defined("_ADV_SEARCH_BUSINESS")) define("_ADV_SEARCH_BUSINESS", "[business] Search a document");

if (!defined("_DEPARTMENT_OWNER")) define("_DEPARTMENT_OWNER", "Owner department");

/********************Parameters **************/
if (!defined("_PARAMETERS")) define("_PARAMETERS", "Configuration(s)");
if (!defined("_PARAMETER")) define("_PARAMETER", "Configuration");
if (!defined("_PARAMETER_S")) define("_PARAMETER_S", "Configuration(s)");
if (!defined("_ALL_PARAMETERS")) define("_ALL_PARAMETERS", "All");
if (!defined("_ADMIN_PARAMETERS")) define("_ADMIN_PARAMETERS", "Manage the configurations");
if (!defined("_ADMIN_PRIORITIES"))
    define("_ADMIN_PRIORITIES", "Priorities");
if (!defined("_PRIORITY_TITLE"))
    define("_PRIORITY_TITLE", "Priority name");
if (!defined("_PRIORITY_DAYS"))
    define("_PRIORITY_DAYS", "Processing deadline in days");
if (!defined("_PRIORITIES_UPDATED"))
    define("_PRIORITIES_UPDATED", "Updated priority");
if (!defined("_PRIORITIES_ERROR"))
    define("_PRIORITIES_ERROR", "An error occured, please check on the informed configurations");
if (!defined("_PRIORITIES_ERROR_TAKEN"))
    define("_PRIORITIES_ERROR_TAKEN", "Cancelled deletion, this priority is used");
if (!defined("_WORKING_DAYS"))
    define("_WORKING_DAYS", "Working days");
if (!defined("_CALENDAR_DAYS"))
    define("_CALENDAR_DAYS", "Calendar days");

if (!defined("_ADD_PARAMETER")) define("_ADD_PARAMETER", "New configuration");
if (!defined("_VALUE")) define("_VALUE", "Value");
if (!defined("_STRING")) define("_STRING", "Characters string");
if (!defined("_INT")) define("_INT", "Integer");
if (!defined("_DATE")) define("_DATE", "Date");
if (!defined("_ID_IS_MANDATORY")) define("_ID_IS_MANDATORY", "Mandatory ID");
if (!defined("_INVALID_PARAMETER_ID")) define("_INVALID_PARAMETER_ID", "Invalid ID (Only the characters like A-Z, a-z, 0-9 and _ are allowed");
if (!defined("_VALUE_IS_MANDATORY")) define("_VALUE_IS_MANDATORY", "Mandatory value");

if (!defined("_GLOBAL_SEARCH_BUSINESS")) define("_GLOBAL_SEARCH_BUSINESS", "Global search of documents");
if (!defined("_QUICK_SEARCH")) define("_QUICK_SEARCH", "Quick search");

if (!defined("_FROM_WS")) define("_FROM_WS", "From a website");
if (!defined("_PROCESS_WORKFLOW")) define("_PROCESS_WORKFLOW", "Process workflow");
if (!defined("_PROCESS_STEP")) define("_PROCESS_STEP", "Process the step");
if (!defined("_DOCUMENT")) define("_DOCUMENT", "document");


/*************** business search adv **************/
if (!defined("_CATEGORY_HELP")) define("_CATEGORY_HELP", "Modify the category will modify the search form");
if (!defined("_CONTACT_HELP")) define("_CONTACT_HELP", "Field with autocomplete option, the contact type depend on the choosen category");
if (!defined("_SUBJECT_HELP")) define("_SUBJECT_HELP", "Document subject");

if (!defined("_NOT_EXISTS")) define("_NOT_EXISTS", "doesn't exist");

/*************** FOLDER **************/
if (!defined("_CONFIRM_FOLDER_STATUS")) define("_CONFIRM_FOLDER_STATUS", "[folder] Confirm the folder status");
if (!defined("_REDIRECT_FOLDER")) define("_REDIRECT_FOLDER", "[folder] Redirect the folder");

//***Business Collection***/

if (!defined("_CHOOSE_TYPE")) define("_CHOOSE_TYPE", "Choose a type");
if (!defined("_DEPARTMENT_OWNER")) define("_DEPARTMENT_OWNER", "Department membership");
if (!defined("_FOLDER")) define("_FOLDER", "Serial folder");
if (!defined("_ORGANIC_FP")) define("_ORGANIC_FP", "Organic ranking");
if (!defined("_BOX_ID")) define("_BOX_ID", "Archiving box");
if (!defined("_ITEM_FOLDER")) define("_ITEM_FOLDER", "Organic ranking");

//choose status on valid
if (!defined("_CHOOSE_CURRENT_STATUS")) define("_CHOOSE_CURRENT_STATUS", "Keep the current status");

//PRINT
if (!defined("_PRINT_DETAILS_SERVICE")) define("_PRINT_DETAILS_SERVICE", "Print the liaison sheet from the detail sheet");
if (!defined("_PRINT_DETAILS")) define("_PRINT_DETAILS", "Print liaison sheet");
if (!defined("_PRINT_DOC_DETAILS_FROM_LIST")) define("_PRINT_DOC_DETAILS_FROM_LIST", "Print the liaison sheets from the results lists");
if (!defined("_PRINT_LIST")) define("_PRINT_LIST", "Print the list");
if (!defined("_PRINT_CATEGORY")) define("_PRINT_CATEGORY", "Category");
if (!defined("_PRINT_DOC_DATE")) define("_PRINT_DOC_DATE", "Document date");
if (!defined("_PRINT_PROCESS_LIMIT_DATE")) define("_PRINT_PROCESS_LIMIT_DATE", "Processing deadline");
if (!defined("_PRINT_PRIORITY")) define("_PRINT_PRIORITY", "Priority");
if (!defined("_PRINT_CONTACT")) define("_PRINT_CONTACT", "CONTACT");
if (!defined("_PRINT_SUBJECT")) define("_PRINT_SUBJECT", "OBJECT");
if (!defined("_PRINT_DATE")) define("_PRINT_DATE", "Print date");
if (!defined("_PRINT_FOLDER")) define("_PRINT_FOLDER", "Folder");
if (!defined("_PRINT_ARBOX")) define("_PRINT_ARBOX", "Archiving box");
if (!defined("_PRINT_STATUS")) define("_PRINT_STATUS", "Status");
if (!defined("_PRINT_ALT_IDENTIFIER")) define("_PRINT_ALT_IDENTIFIER", "Chrono number");
if (!defined("_PRINTED_FILE_NUMBER")) define("_PRINTED_FILE_NUMBER", "Liaison sheet");
if (!defined("_CREATED_ON")) define("_CREATED_ON", "Created on");
if (!defined("_INFORMATIONS_OF_THE_DOCUMENT")) define("_INFORMATIONS_OF_THE_DOCUMENT", "Datas on the document");
if (!defined("_PRINT_ADMISSION_DATE")) define("_PRINT_ADMISSION_DATE", "Arrival date");
if (!defined("_PRINT_TYPIST")) define("_PRINT_TYPIST", "Operator");
if (!defined("_PRINT_FREE_NOTES")) define("_PRINT_FREE_NOTES", "FREE NOTES");
if (!defined("_PRINT_COPIES")) define("_PRINT_COPIES", "COPIES");
if (!defined("_PRINT_NOTES")) define("_PRINT_NOTES", "NOTES");
if (!defined("_PRINT_PROCESS_ENTITY")) define("_PRINT_PROCESS_ENTITY", "Processing department");
if (!defined("_PRINT_PRIVATE_NOTE")) define("_PRINT_PRIVATE_NOTE", "Private note");
if (!defined("_PRINT_THE")) define("_PRINT_THE", "The");

//MULTICONTACTS
if (!defined("_MULTI")) define("_MULTI", "Multi");
if (!defined("_MULTI_CONTACTS")) define("_MULTI_CONTACTS", "Multiple contacts");
if (!defined("_CONTACT_EXTERNAL")) define("_CONTACT_EXTERNAL", "External contact");
if (!defined("_CONTACT_INTERNAL")) define("_CONTACT_INTERNAL", "Internal contact");

//DocLocker
if (!defined("_DOC_LOCKER_RES_ID")) define("_DOC_LOCKER_RES_ID", "you cannot open the document number");
if (!defined("_DOC_LOCKER_USER")) define("_DOC_LOCKER_USER", ", is already handling by :");

//RECOMMANDE
if (!defined("_MONITORING_NUMBER")) define("_MONITORING_NUMBER", "Monitoring number");

//EXPORT CONTACT
if (!defined("_EXPORT_CONTACT")) define("_EXPORT_CONTACT", "Export the contacts");

//INDEXATION WITHOUT FILE
if (!defined("_WITHOUT_FILE")) define("_WITHOUT_FILE", "Without file");

//ASSOCIATION ACTION / CATEGORY
if (!defined("_ASSOCIATED_CATEGORY")) define("_ASSOCIATED_CATEGORY", "Associated category");
if (!defined("_NO_CATEGORY_ASSOCIATED")) define("_NO_CATEGORY_ASSOCIATED", "Do not associate a category");
if (!defined("_CHOOSE_CATEGORY_ASSOCIATION")) define("_CHOOSE_CATEGORY_ASSOCIATION", "Choose one or severals associated categories");
if (!defined("_CHOOSE_CATEGORY_ASSOCIATION_HELP")) define("_CHOOSE_CATEGORY_ASSOCIATION_HELP", "If there is no selected category, then the action is available for all categories");

//SERVICE VIEW DOC HISTORY
if (!defined("_VIEW_DOC_HISTORY")) define("_VIEW_DOC_HISTORY", "See global document history");
if (!defined("_VIEW_FULL_HISTORY"))    define("_VIEW_FULL_HISTORY", "See Full document history");

//ONLY ALPHANUM
if (!defined("_ONLY_ALPHANUM")) define("_ONLY_ALPHANUM", "Only alphanumeric characters are accepted");
if (!defined("_ONLY_ALPHABETIC")) define("_ONLY_ALPHABETIC", "Only alphabetical characters are accepted");

if (!defined("_CLOSE_MAIL_AND_INDEX")) define("_CLOSE_MAIL_AND_INDEX", "Close a mail and launch the indexation");
if (!defined("_DOC_NOT_CLOSED")) define("_DOC_NOT_CLOSED", "This mail is not closed");

if (!defined("_SECURITY_MESSAGE")) define("_SECURITY_MESSAGE", "Security message");
if (!defined("_SECURITY_MESSAGE_DETAILS")) define("_SECURITY_MESSAGE_DETAILS", "XSS type resquest is not allowed");

if (!defined("_CHOOSE_ENTITY_SUBENTITIES")) define("_CHOOSE_ENTITY_SUBENTITIES", "Choose a department (+ sub-department(s))");

if(!defined("_TAG_ADMIN")) define("_TAG_ADMIN", "Keywords administration");

if(!defined("_REFERENCE_MAIL")) define("_REFERENCE_MAIL", "Mail's sender reference");

if(!defined("_OTHERS_INFORMATIONS")) define("_OTHERS_INFORMATIONS", "Other informations (signatories, orders, etc...)");

if(!defined("_ALL_HISTORY")) define("_ALL_HISTORY", "Entire history");

if (!defined("_DESCRIPTION")) define("_DESCRIPTION", "Description");

if (!defined("_MOVE_CONTACT_ADDRESS")) define("_MOVE_CONTACT_ADDRESS", "Address moving to an other contact");
if (!defined("_INFO_MOVE_CONTACT_ADDRESS")) define("_INFO_MOVE_CONTACT_ADDRESS", "this part is used if you want to move the address to a new contact. The documents (if there are ones) will stay attached to this same address.");

if (!defined("_MOVE")) define("_MOVE", "Move");
if (!defined("_DELETE_CONTACT_ADDRESS")) define("_DELETE_CONTACT_ADDRESS", "Delete the address");
if (!defined("_REALLY_MOVE")) define("_REALLY_MOVE", "Do you really want to move it ? ");

if (!defined("_ADDRESS_MOVED")) define("_ADDRESS_MOVED", "Moved address");

if (!defined("_SAVE_MODIFICATION")) define("_SAVE_MODIFICATION", "Record the modifications");

if (!defined("_CONFIDENTIALITY")) define("_CONFIDENTIALITY", "Confidential");
if (!defined("_CONFIDENTIAL")) define("_CONFIDENTIAL", "Confidential");

if (!defined("_SIGNATORY_NAME")) define("_SIGNATORY_NAME", "Signatory name");
if (!defined("_SIGNATORY_GROUP")) define("_SIGNATORY_GROUP", "Signatory group");

if (!defined("_FORMAT_PHONE")) define("_FORMAT_PHONE", "Phone format : 06 01 02 03 04");

if (!defined("_SIGNATURE")) define("_SIGNATURE","Signature");

// Actions parapheur
if (!defined("_SEND_MAIL"))    define("_SEND_MAIL", "Folder sent by email");
if (!defined("_IMPRIM_DOSSIER"))    define("_IMPRIM_DOSSIER", "Folder print");
if (!defined("_PROCEED_WORKFLOW"))    define("_PROCEED_WORKFLOW", "continue the visa flow");
if (!defined("_INTERRUPT_WORKFLOW"))
    define("_INTERRUPT_WORKFLOW", "Break the visa flow");
if (!defined("_REJECTION_WORKFLOW_REDACTOR"))
    define("_REJECTION_WORKFLOW_REDACTOR", "Visa rejection - back to the author");
if (!defined("_REJECTION_WORKFLOW_PREVIOUS"))
    define("_REJECTION_WORKFLOW_PREVIOUS", "Visa rejection - back to the previous author");
if (!defined("_VISA_MAIL"))    define("_VISA_MAIL", "Aim the mail");
if (!defined("_SEND_SIGNED_DOCS"))    define("_SEND_SIGNED_DOCS", "Pass signed responses");
if (!defined("_PREPARE_VISA"))    define("_PREPARE_VISA", "Prepare the visa flow");
if (!defined("_REDIRECTION_VISA_SIGN"))    define("_REDIRECTION_VISA_SIGN", "Redirection for signature");
if (!defined('_SEND_TO_VISA'))    define( '_SEND_TO_VISA', 'send for visa');

if (!defined("_MAIL_WILL_DISAPPEAR"))    define("_MAIL_WILL_DISAPPEAR", "This mail is out your area. You won't be able to access it then.");

//maarchIVS translate

if (!defined("_IVS_LENGTH_ID_BELOW_MIN_LENGTH")) define("_IVS_LENGTH_ID_BELOW_MIN_LENGTH", "the length is inferior than the minimum length");
if (!defined("_IVS_LENGTH_EXCEEDS_MAX_LENGTH")) define("_IVS_LENGTH_EXCEEDS_MAX_LENGTH", "the length is superior to the maximum length");
if (!defined("_IVS_LENGTH_NOT_ALLOWED")) define("_IVS_LENGTH_NOT_ALLOWED", "The lenght is not allowed");
if (!defined("_IVS_VALUE_NOT_ALLOWED")) define("_IVS_VALUE_NOT_ALLOWED", "the value is not allowed");
if (!defined("_IVS_FORMAT_NOT_ALLOWED")) define("_IVS_FORMAT_NOT_ALLOWED", "The format is not allowed");
if (!defined("_IVS_TOO_MANY_DIGITS")) define("_IVS_TOO_MANY_DIGITS", "Too many characters");
if (!defined("_IVS_TOO_MANY_DECIMAL_DIGITS")) define("_IVS_TOO_MANY_DECIMAL_DIGITS", "Too many decimal characters");

//control technical params
if (!defined("_CONTROL_PARAM_TECHNIC")) define("_CONTROL_PARAM_TECHNIC", "Control technical configurations");
if (!defined("_COMPONENT")) define("_COMPONENT", "Component");

if (!defined("_MARK_AS_READ")) define("_MARK_AS_READ", "Marked as read");

if (!defined("_USE_PREVIOUS_ADDRESS")) define("_USE_PREVIOUS_ADDRESS", "Re use an address");

if (!defined("_SEARCH_INDICATION"))    define("_SEARCH_INDICATION", " indicate that the search is done on  mails and attachments.");

if (!defined("_VISIBLE_BY"))    define("_VISIBLE_BY", "Is visible by");

// SEDA
if (!defined("_FINAL_DISPOSITION")) define("_FINAL_DISPOSITION","Final disposition");
if (!defined("_CHOOSE_FINAL_DISPOSITION")) define("_CHOOSE_FINAL_DISPOSITION","choose final disposition");
if (!defined("_DESTROY")) define("_DESTROY","Destroy");
if (!defined("_KEEP")) define("_KEEP","Keep");
if (!defined("_RETENTION_RULE")) define("_RETENTION_RULE","Retention rule");
if (!defined("_DURATION_CURRENT_USE")) define("_DURATION_CURRENT_USE","Duration current use");

if (!defined("_UNSELECT_ALL")) define("_UNSELECT_ALL","Unselect all");

/***** Profile *****/
if (!defined('_MANAGE_SIGNATURES'))
    define('_MANAGE_SIGNATURES', 'Manage my signatures');
if (!defined('_MY_GROUPS'))
    define('_MY_GROUPS', 'My Groups');
if (!defined('_PRIMARY_GROUP'))
    define('_PRIMARY_GROUP', 'Primary group');
if (!defined('_SECONDARY_GROUP'))
    define('_SECONDARY_GROUP', 'Secondary group');
if (!defined('_MY_ENTITIES'))
    define('_MY_ENTITIES', 'My Entities');
if (!defined('_PRIMARY_ENTITY'))
    define('_PRIMARY_ENTITY', 'Primary entity');
if (!defined('_SECONDARY_ENTITY'))
    define('_SECONDARY_ENTITY', 'Secondary entity');
if (!defined('_MY_INFORMATIONS'))
    define('_MY_INFORMATIONS', 'My Informations');
if (!defined('_DIGITAL_FINGERPRINT'))
    define('_DIGITAL_FINGERPRINT', 'Digital fingerprint');
if (!defined('_CHANGE_PSW'))
    define('_CHANGE_PSW', 'Change your password');
if (!defined('_CURRENT_PSW'))
    define('_CURRENT_PSW', 'Current password');
if (!defined('_NEW_PSW'))
    define('_NEW_PSW', 'New password');
if (!defined('_REENTER_PSW'))
    define('_REENTER_PSW', 'Enter the password again');
if (!defined('_UPDATED_PROFILE'))
    define('_UPDATED_PROFILE', 'Your profile has been updated');

if (!defined('_WRONG_PSW'))
    define('_WRONG_PSW', 'Wrong password');
if (!defined('_WRONG_SECOND_PSW'))
    define('_WRONG_SECOND_PSW', 'The second password isn\'t equivalent to the first password !');
if (!defined('_EMPTY_PSW_FORM'))
    define('_EMPTY_PSW_FORM', 'Password form is not complete');
if (!defined('_UPDATED_PASSWORD'))
    define('_UPDATED_PASSWORD', 'Your password has been updated');

if (!defined('_SB_SIGNATURES'))
    define('_SB_SIGNATURES', 'Signature Book signatures');
if (!defined('_NEW_SIGNATURE'))
    define('_NEW_SIGNATURE', 'New signature added');
if (!defined('_UPDATED_SIGNATURE'))
    define('_UPDATED_SIGNATURE', 'Signature updated');
if (!defined('_DELETED_SIGNATURE'))
    define('_DELETED_SIGNATURE', 'Signature deleted');
if (!defined('_DEFINE_NEW_SIGNATURE'))
    define('_DEFINE_NEW_SIGNATURE', 'New signature');
if (!defined('_SIGNATURE_LABEL'))
    define('_SIGNATURE_LABEL', 'Signature label');
if (!defined('_UPDATE_SIGNATURE'))
    define('_UPDATE_SIGNATURE', 'Update signature');
if (!defined('_DELETE_SIGNATURE'))
    define('_DELETE_SIGNATURE', 'Delete signature');
if (!defined('_CLICK_ON'))
    define('_CLICK_ON', 'Click on');
if (!defined('_TO_ADD_SIGNATURE'))
    define('_TO_ADD_SIGNATURE', 'to add a signature');
if (!defined('_TO_UPDATE_SIGNATURE'))
    define('_TO_UPDATE_SIGNATURE', 'to change uploaded image');

if (!defined('_EMAIL_SIGNATURES'))
    define('_EMAIL_SIGNATURES', 'Email signatures');
if (!defined('_EMPTY_EMAIL_SIGNATURE_FORM'))
    define('_EMPTY_EMAIL_SIGNATURE_FORM', 'Mail signature form is imcomplete');
if (!defined('_NEW_EMAIL_SIGNATURE'))
    define('_NEW_EMAIL_SIGNATURE', 'New email signature added');
if (!defined('_UPDATED_EMAIL_SIGNATURE'))
    define('_UPDATED_EMAIL_SIGNATURE', 'Mail signature updated');
if (!defined('_DELETED_EMAIL_SIGNATURE'))
    define('_DELETED_EMAIL_SIGNATURE', 'Mail signature deleted');

if (!defined('_UNDEFINED_USER'))
    define('_UNDEFINED_USER', 'Undefined user');
if (!defined('_ACTIVATE_ABSENCE'))
    define('_ACTIVATE_ABSENCE', 'Activate my absence');
if (!defined('_AUTO_LOGOUT_AFTER_BASKETS_REDIRECTIONS'))
    define('_AUTO_LOGOUT_AFTER_BASKETS_REDIRECTIONS', 'You are going to be automaticaly disconnected after your redirections');
/***** Profile *****/

/**** admin update control ****/
if (!defined('_ADMIN_UPDATE_CONTROL'))
    define('_ADMIN_UPDATE_CONTROL', 'Verify update');
if (!defined('_ADMIN_UPDATE_CONTROL_DESC'))
    define('_ADMIN_UPDATE_CONTROL_DESC', 'Verify update');
if (!defined('_YOUR_VERSION'))
    define('_YOUR_VERSION', 'Your version');
if (!defined('_AVAILABLE_VERSION_TO_UPDATE'))
    define('_AVAILABLE_VERSION_TO_UPDATE', 'Available versions');
if (!defined('_CLICK_HERE_TO_GO_TO_UPDATE_MANAGEMENT'))
    define('_CLICK_HERE_TO_GO_TO_UPDATE_MANAGEMENT', 'Click here to begin the update process');
if (!defined('_NEW_MAJOR_VERSION_AVAILABLE'))
    define('_NEW_MAJOR_VERSION_AVAILABLE', 'New major version available');
if (!defined('_BRANCH_VERSION'))
    define('_BRANCH_VERSION', 'Branch');
if (!defined('_TAG_VERSION'))
    define('_TAG_VERSION', 'Tag');
if (!defined('_CONNECT_YOU_IN_SUPERADMIN'))
    define('_CONNECT_YOU_IN_SUPERADMIN', 'You have to be connected with superadmin profile to process update');
if (!defined('_UPDATE_WELCOME'))
    define('_UPDATE_WELCOME', 'Update');
if (!defined('_UPDATE_WELCOME_INSTALL'))
    define('_UPDATE_WELCOME_INSTALL', 'Update process');
if (!defined('_UPDATE_DESC_INSTALL'))
    define('_UPDATE_DESC_INSTALL', 'Update MaarchCourrier (only minor version)');
if (!defined('_UPDATE_BACKUP'))
    define('_UPDATE_BACKUP', 'Backup');
if (!defined('_UPDATE_BACKUP_INFOS'))
    define('_UPDATE_BACKUP_INFOS', 'Backup your version');
if (!defined('_UPDATE_BACKUP_DETAILS'))
    define('_UPDATE_BACKUP_DETAILS', 'Backup your version, you can restore it if necessary');
if (!defined('_ACTUAL_VERSION_PATH'))
    define('_ACTUAL_VERSION_PATH', 'Path of your installation');
if (!defined('_UPDATE_BACKUP_PATH'))
    define('_UPDATE_BACKUP_PATH', 'Path of your backup');
if (!defined('_BACKUP_ACTUAL_VERSION'))
    define('_BACKUP_ACTUAL_VERSION', 'Backup your version');
if (!defined('_UPDATE_DOWNLOAD'))
    define('_UPDATE_DOWNLOAD', 'Download');
if (!defined('_LAST_RELEASE_INFOS'))
    define('_LAST_RELEASE_INFOS', 'Download last minor version');
if (!defined('_LAST_RELEASE_DETAILS'))
    define('_LAST_RELEASE_DETAILS', 'Available minor versions');
if (!defined('_CHOOSE_VERSION_TO_UPDATE'))
    define('_CHOOSE_VERSION_TO_UPDATE', 'Choose the version');
if (!defined('_DOWNLOAD_VERSION'))
    define('_DOWNLOAD_VERSION', 'Download the version');
if (!defined('_UPDATE_DEPLOY'))
    define('_UPDATE_DEPLOY', 'Deploiement');
if (!defined('_UPDATE_DEPLOY_INFOS'))
    define('_UPDATE_DEPLOY_INFOS', 'Deploiement of the downloaded version');
if (!defined('_UPDATE_DEPLOY_DETAILS'))
    define('_UPDATE_DEPLOY_DETAILS', 'Deploiement of the downloaded version');
if (!defined('_DEPLOY_VERSION'))
    define('_DEPLOY_VERSION', 'Deploy the version');
if (!defined('_UPDATE_END'))
    define('_UPDATE_END', 'Update sucessful');
if (!defined('_UPDATE_DESC_END'))
    define('_UPDATE_DESC_END', 'Update sucessful');
if (!defined('_NO_AVAILABLE_TAG_TO_UPDATE'))
    define('_NO_AVAILABLE_TAG_TO_UPDATE', 'No available tag to update');
