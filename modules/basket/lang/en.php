<?php
/*
 *
 *    Copyright 2008-2015 Maarch
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

/*********************** SERVICES ***********************************/
if (!defined("_DIFFUSION_LIST"))
    define("_DIFFUSION_LIST", "Diffusion List");

//class basket
if (!defined("_BASKET"))
    define("_BASKET", "Basket");
if (!defined("_BASKETS_COMMENT"))
    define("_BASKETS_COMMENT", "Baskets");
if (!defined("_THE_BASKET"))
    define("_THE_BASKET", "The basket");
if (!defined("_THE_ID"))
    define("_THE_ID", "The ID ");
if (!defined("_THE_DESC"))
    define("_THE_DESC", "The description ");
if (!defined("_DELETED_BASKET"))
    define("_DELETED_BASKET", "Deleted basket");
if (!defined("_BASKET_DELETION"))
    define("_BASKET_DELETION", "Basket deletion");
if (!defined("_BASKET_AUTORIZATION"))
    define("_BASKET_AUTORIZATION", "Basket authorization");
if (!defined("_BASKET_SUSPENSION"))
    define("_BASKET_SUSPENSION", "Basket suspension");
if (!defined("_AUTORIZED_BASKET"))
    define("_AUTORIZED_BASKET", "Authorized basket");
if (!defined("_SUSPENDED_BASKET"))
    define("_SUSPENDED_BASKET", "Suspended basket");
if (!defined("_NO_BASKET_DEFINED_FOR_YOU"))
    define("_NO_BASKET_DEFINED_FOR_YOU", "No defined basket for this user");
if (!defined("_BASKET_VISIBLE"))
    define("_BASKET_VISIBLE", "Visible basket");
if (!defined("_BASKETS_LIST"))
    define("_BASKETS_LIST", "Baskets list");

/////// frame bannettes
if (!defined("_CHOOSE_BASKET"))
    define("_CHOOSE_BASKET", "Choose a basket");
if (!defined("_PROCESS_BASKET"))
    define("_PROCESS_BASKET", "Your mail for processing");
if (!defined("_VALIDATION_BASKET"))
    define("_VALIDATION_BASKET", "Your mail for validation");
if (!defined("_MANAGE_BASKETS"))
    define("_MANAGE_BASKETS", "Manage the baskets");
if (!defined("_MANAGE_BASKETS_APP"))
    define("_MANAGE_BASKETS_APP", "Manage the application baskets");

/************** Basket : List + Form**************/
if (!defined("_ALL_BASKETS"))
    define("_ALL_BASKETS", "All the baskets");
if (!defined("_BASKET_LIST"))
    define("_BASKET_LIST", "Baskets list");
if (!defined("_ADD_BASKET"))
    define("_ADD_BASKET", "Add a basket");
if (!defined("_BASKET_ADDITION"))
    define("_BASKET_ADDITION", "Basket addition");
if (!defined("_BASKET_MODIFICATION"))
    define("_BASKET_MODIFICATION", "Basket modification");
if (!defined("_BASKET_VIEW"))
    define("_BASKET_VIEW", "View on the table");
if (!defined("_MODIFY_BASKET"))
    define("_MODIFY_BASKET", "Modify the basket");
if (!defined("_ADD_A_NEW_BASKET"))
    define("_ADD_A_NEW_BASKET", "Create a new basket");
if (!defined("_ADD_A_GROUP_TO_BASKET"))
    define("_ADD_A_GROUP_TO_BASKET", "Associate a new group to the basket");
if (!defined("_DEL_GROUPS"))
    define("_DEL_GROUPS", "Delete group(s)");
if (!defined("_BASKET_NOT_USABLE"))
    define("_BASKET_NOT_USABLE", "No associated group (the basket is unusable for now)");
if (!defined("_ASSOCIATED_GROUP"))
    define("_ASSOCIATED_GROUP", "Groups list associated to the basket");
if (!defined("_TITLE_GROUP_BASKET"))
    define("_TITLE_GROUP_BASKET", "Associated the basket to a group");
if (!defined("_ADD_TO_BASKET"))
    define("_ADD_TO_BASKET", "Associate the basket");
if (!defined("_TO_THE_GROUP"))
    define("_TO_THE_GROUP", "To a group");
if (!defined("_ALLOWED_ACTIONS"))
    define("_ALLOWED_ACTIONS", "Authorized actions");
if (!defined("_SERVICES_BASKETS"))
    define("_SERVICES_BASKETS", "Services baskets");
if (!defined("_USERGROUPS_BASKETS"))
    define("_USERGROUPS_BASKETS", "Baskets in the users groups");
if (!defined("_BASKET_RESULT_PAGE"))
    define("_BASKET_RESULT_PAGE", "Results list");
if (!defined("_ADD_THIS_GROUP"))
    define("_ADD_THIS_GROUP", "Add the group");
if (!defined("_MODIFY_THIS_GROUP"))
    define("_MODIFY_THIS_GROUP", "Modify the group");
if (!defined("_DEFAULT_ACTION_LIST"))
    define("_DEFAULT_ACTION_LIST", "Default action on the lign");
if (!defined("_NO_ACTION_DEFINED"))
    define("_NO_ACTION_DEFINED", "No defined action");
//BASKETS
if (!defined("_WAITING_VAL_LIST"))
    define("_WAITING_VAL_LIST", "Validation files list on hold");
if (!defined("_WAITING_QUAL_LIST"))
    define("_WAITING_QUAL_LIST", "Files list on hold of title");
if (!defined("_WAITING_DISTRIB_LIST"))
    define("_WAITING_DISTRIB_LIST", "Mails list on hold of distribution");
if (!defined("_NO_REDIRECT_RIGHT"))
    define("_NO_REDIRECT_RIGHT", "You haven't the right to redirect in this basket");

//DIFFUSION LIST
if (!defined("_CHOOSE_DEPARTMENT_FIRST"))
    define("_CHOOSE_DEPARTMENT_FIRST", "You have to choose a service before being able to access to the diffusion list");
if (!defined("_NO_LIST_DEFINED__FOR_THIS_MAIL"))
    define("_NO_LIST_DEFINED__FOR_THIS_MAIL", "No list is defined for this mail");
if (!defined("_NO_LIST_DEFINED__FOR_THIS_DEPARTMENT"))
    define("_NO_LIST_DEFINED__FOR_THIS_DEPARTMENT", "No list is defined for this department");
if (!defined("_NO_LIST_DEFINED"))
    define("_NO_LIST_DEFINED", "No defined list");
if (!defined("_REDIRECT_MAIL"))
    define("_REDIRECT_MAIL", "Mail redirection");
if (!defined("_REDIRECT_TO_OTHER_DEP"))
    define("_REDIRECT_TO_OTHER_DEP", "To an other department");
if (!defined("_REDIRECT_TO_USER"))
    define("_REDIRECT_TO_USER", "To an user");
if (!defined("_LETTER_SERVICE_REDIRECT"))
    define("_LETTER_SERVICE_REDIRECT","Redirect to the emitter department");
if (!defined("_LETTER_SERVICE_REDIRECT_VALIDATION"))
    define("_LETTER_SERVICE_REDIRECT_VALIDATION","Do you really want to redirect to the emitter department");
if (!defined("_DOC_REDIRECT_TO_SENDER_ENTITY"))
    define("_DOC_REDIRECT_TO_SENDER_ENTITY", "Document redirected to emitter department");
if (!defined("_DOC_REDIRECT_TO_ENTITY"))
    define("_DOC_REDIRECT_TO_ENTITY", "Document redirected to department");
if (!defined("_DOC_REDIRECT_TO_USER"))
    define("_DOC_REDIRECT_TO_USER", "Document redirected to user");
if (!defined("_WELCOME_DIFF_LIST"))
    define("_WELCOME_DIFF_LIST", "Welcome to the tool of mail diffusion");
if (!defined("_START_DIFF_EXPLANATION"))
    define("_START_DIFF_EXPLANATION", "To start the diffusion, use the navigation by department or by user above");
if (!defined("_ADD_USER_TO_LIST_EXPLANATION"))
    define("_ADD_USER_TO_LIST_EXPLANATION", "To add an user on the diffusion list");
if (!defined("_REMOVE_USER_FROM_LIST_EXPLANATION"))
    define("_REMOVE_USER_FROM_LIST_EXPLANATION", "To remove the user from this diffusion list");
if (!defined("_TO_MODIFY_LIST_ORDER_EXPLANATION"))
    define("_TO_MODIFY_LIST_ORDER_EXPLANATION", "To modify the awarding order of a mail for users, please use the icons");
if (!defined("_AND"))
    define("_AND", " And " );
if (!defined("_LINKED_DIFF_LIST"))
    define("_LINKED_DIFF_LIST", "Associated diffusion list");
if (!defined("_NO_LINKED_DIFF_LIST"))
    define("_NO_LINKED_DIFF_LIST", "No associated list");
if (!defined("_CREATE_LIST"))
    define("_CREATE_LIST", "Create a diffusion list");
if (!defined("_MODIFY_LIST"))
    define("_MODIFY_LIST", "Modify the list");
if (!defined("_THE_ENTITY_DO_NOT_CONTAIN_DIFF_LIST"))
    define("_THE_ENTITY_DO_NOT_CONTAIN_DIFF_LIST", "The selected department has not any template of associated diffusion list");

//LIST MODEL
if (!defined("_MANAGE_MODEL_LIST_TITLE"))
    define("_MANAGE_MODEL_LIST_TITLE", "Creation / modification Diffusion List template");
if (!defined("_SORT_BY"))
    define("_SORT_BY", "Classify by");
if (!defined("_WELCOME_MODEL_LIST_TITLE"))
    define("_WELCOME_MODEL_LIST_TITLE", "Welcome in the creation tool of diffusion list template");
if (!defined("_MODEL_LIST_EXPLANATION1"))
    define("_MODEL_LIST_EXPLANATION1", "To start the creation, use the navigation by department or by user, above");
if (!defined("_VALID_LIST"))
    define("_VALID_LIST", "Validate the list");

//LIST
if (!defined("_COPY_LIST"))
    define("_COPY_LIST", "Mails list on copy");
if (!defined("_PROCESS_LIST"))
    define("_PROCESS_LIST", "Mails list to process");
if (!defined("_CLICK_LINE_TO_VIEW"))
    define("_CLICK_LINE_TO_VIEW", "Click on a lign to view");
if (!defined("_CLICK_LINE_TO_PROCESS"))
    define("_CLICK_LINE_TO_PROCESS", "Click on a lign to process");
if (!defined("_REDIRECT_TO_SENDER_ENTITY"))
    define("_REDIRECT_TO_SENDER_ENTITY", "Redirection toward sender department");
if (!defined("_CHOOSE_DEPARTMENT"))
    define("_CHOOSE_DEPARTMENT", "Choose a department");
if (!defined("_ENTITY_UPDATE"))
    define("_ENTITY_UPDATE", "Updated department");

// USER ABS
if (!defined("_MY_ABS"))
    define("_MY_ABS", "Manage my absences");
if (!defined("_MY_ABS_TXT"))
    define("_MY_ABS_TXT", "Allows to redirect your baskets in case of time off departure.");
if (!defined("_MY_ABS_REDIRECT"))
    define("_MY_ABS_REDIRECT", "your mails are currently redirected to");
if (!defined("_MY_ABS_DEL"))
    define("_MY_ABS_DEL", "To delete the redirection, click here to stop");
if (!defined("_ADMIN_ABS"))
    define("_ADMIN_ABS", "Manage the absences.");
if (!defined("_ADMIN_ABS_TXT"))
    define("_ADMIN_ABS_TXT", "Allows to redirect the user's mail on hold in case of time off departure.");
if (!defined("_ADMIN_ABS_REDIRECT"))
    define("_ADMIN_ABS_REDIRECT", "Absence redirection on progress.");
if (!defined("_ADMIN_ABS_FIRST_PART"))
    define("_ADMIN_ABS_FIRST_PART", "The mails of");
if (!defined("_ADMIN_ABS_SECOND_PART"))
    define("_ADMIN_ABS_SECOND_PART", "are currently redirected to ");
if (!defined("_ADMIN_ABS_THIRD_PART"))
    define("_ADMIN_ABS_THIRD_PART", "Click here to delete the redirection.");
if (!defined("_ACTIONS_DONE"))
    define("_ACTIONS_DONE", "Actions done on ");
if (!defined("_PROCESSED_MAIL"))
    define("_PROCESSED_MAIL", "Processed mails");
if (!defined("_INDEXED_MAIL"))
    define("_INDEXED_MAIL", "Indexed mails");
if (!defined("_REDIRECTED_MAIL"))
    define("_REDIRECTED_MAIL", "Redirected mails");
if (!defined("_PROCESS_MAIL_OF"))
    define("_PROCESS_MAIL_OF", "Mail to process from");
if (!defined("_MISSING"))
    define("_MISSING", "Absent");
if (!defined("_BACK_FROM_VACATION"))
    define("_BACK_FROM_VACATION", "Back from his/her absence");
if (!defined("_MISSING_CHOOSE"))
    define("_MISSING_CHOOSE"," Do you want to continue ?");
if (!defined("_CHOOSE_PERSON_TO_REDIRECT"))
    define("_CHOOSE_PERSON_TO_REDIRECT", "Choose the person toward who you would like to redirect this mail in the list below");
if (!defined("_TO_SELECT_USER"))
    define("_TO_SELECT_USER", "to select an user");
if (!defined("_DIFFUSION_DISTRIBUTION"))
    define("_DIFFUSION_DISTRIBUTION", "Diffusion and distribution of the mail");
if (!defined("_VALIDATED_ANSWERS"))
    define("_VALIDATED_ANSWERS", "DGS validated responses");
if (!defined("_REJECTED_ANSWERS"))
    define("_REJECTED_ANSWERS", "DGS rejected responses");
if (!defined("_MUST_HAVE_DIFF_LIST"))
    define("_MUST_HAVE_DIFF_LIST", "You have to define a diffusion list");
if (!defined("_ASSOCIATED_STATUS"))
    define("_ASSOCIATED_STATUS", "Associated status");
if (!defined("_SYSTEM_ACTION"))
    define("_SYSTEM_ACTION", "System action");
if (!defined("_ASSOCIATED_ACTIONS"))
    define("_ASSOCIATED_ACTIONS", "Possible actions on the result page");
if (!defined("_NO_ACTIONS_DEFINED"))
    define("_NO_ACTIONS_DEFINED", "No defined action");
if (!defined("_CONFIG"))
    define("_CONFIG", "(configure)");
if (!defined("_CONFIG_ACTION"))
    define("_CONFIG_ACTION", "Action configuration");
if (!defined("_WHERE_CLAUSE_ACTION_TEXT"))
    define("_WHERE_CLAUSE_ACTION_TEXT", "Define one condition of the action appearance in the page by a where clause (optional) : ");
if (!defined("_IN_ACTION"))
    define("_IN_ACTION", " in the action");
if (!defined("_TO_ENTITIES"))
    define("_TO_ENTITIES", "To departments");
if (!defined("_TO_USERGROUPS"))
    define("_TO_USERGROUPS", "To users groups");
if (!defined("_USE_IN_MASS"))
    define("_USE_IN_MASS", "Available action in the list");
if (!defined("_USE_ONE"))
    define("_USE_ONE", "Available action on the home page");
if (!defined("_MUST_CHOOSE_WHERE_USE_ACTION"))
    define("_MUST_CHOOSE_WHERE_USE_ACTION"," You have to define where you want to use the action ");
if (!defined("_MUST_CHOOSE_DEP"))
    define("_MUST_CHOOSE_DEP", "You have to select a department!");
if (!defined("_MUST_CHOOSE_USER"))
    define("_MUST_CHOOSE_USER", "You have to select an user!");
if (!defined("_REDIRECT_TO_DEP_OK"))
    define("_REDIRECT_TO_DEP_OK", "Redirection to a department is done");
if (!defined("_REDIRECT_TO_USER_OK"))
    define("_REDIRECT_TO_USER_OK", "Redirection to an user is done");
if (!defined("_SAVE_CHANGES"))
    define("_SAVE_CHANGES", "Save the modifications");
if (!defined("_VIEW_BASKETS"))
    define("_VIEW_BASKETS", "My baskets");
if (!defined("_VIEW_BASKETS_DESC"))
    define("_VIEW_BASKETS_DESC", "My baskets");
if (!defined("_VIEW_BASKETS_TITLE"))
    define("_VIEW_BASKETS_TITLE", "My baskets");
if (!defined("_INVOICE_LIST_TO_VAL"))
    define("_INVOICE_LIST_TO_VAL", "Bills to validate");
if (!defined("_POSTINDEXING_LIST"))
    define("_POSTINDEXING_LIST", "Documents to video-code");
if (!defined("_MY_BASKETS"))
    define("_MY_BASKETS", "My baskets");
if (!defined("_REDIRECT_MY_BASKETS"))
    define("_REDIRECT_MY_BASKETS", "Redirect the baskets");
if (!defined("_NAME"))
    define("_NAME", "Name");
if (!defined("_CHOOSE_USER_TO_REDIRECT"))
    define("_CHOOSE_USER_TO_REDIRECT", "You have to redirect one of the baskets at least to an user.");
if (!defined("_FORMAT_ERROR_ON_USER_FIELD"))
    define("_FORMAT_ERROR_ON_USER_FIELD", "A field isn't on the right format : Name, First Name (ID)");
if (!defined("_BASKETS_OWNER_MISSING"))
    define("_BASKETS_OWNER_MISSING", "The baskets owner isn't defined.");
if (!defined("_FORM_ERROR"))
    define("_FORM_ERROR", "Error on the form handover...");
if (!defined("_ABS_LOG_OUT"))
    define("_ABS_LOG_OUT", "If you connect again, absent mode will be cancelled.");
if (!defined("_ABS_USER"))
    define("_ABS_USER", "Absent user");
if (!defined("_ABSENCE"))
    define("_ABSENCE", "Absence");
if (!defined("_BASK_BACK"))
    define("_BASK_BACK", "Back");
if (!defined("_CANCEL_ABS"))
    define("_CANCEL_ABS", "Absence cancellation");
if (!defined("_REALLY_CANCEL_ABS"))
    define("_REALLY_CANCEL_ABS", "Do you really want to cancel the absence ?");
if (!defined("_ABS_MODE"))
    define("_ABS_MODE", "Absences management");
if (!defined("_REALLY_ABS_MODE"))
    define("_REALLY_ABS_MODE", "Do you really want to be on absent mode ?");
if (!defined("_DOCUMENTS_LIST_WITH_FILTERS"))
    define("_DOCUMENTS_LIST_WITH_FILTERS", "List with filters");
if (!defined("_AUTHORISED_ENTITIES"))
    define("_AUTHORISED_ENTITIES", "Authorized department list");
if (!defined("_ARCHIVE_LIST"))
    define("_ARCHIVE_LIST", "Archiving units list");
if (!defined("_COUNT_LIST"))
    define("_COUNT_LIST", "Copies list");
if (!defined("_FILTER_BY"))
    define("_FILTER_BY", "Filter by");
if (!defined("_OTHER_BASKETS"))
    define("_OTHER_BASKETS", "Others baskets");
if (!defined("_SPREAD_SEARCH_TO_BASKETS"))
    define("_SPREAD_SEARCH_TO_BASKETS", "Expand the search to baskets");
if (!defined("_BASKET_WELCOME_TXT1"))
    define("_BASKET_WELCOME_TXT1", "During your navigation on the baskets,");
if (!defined("_BASKET_WELCOME_TXT2"))
    define("_BASKET_WELCOME_TXT2", "Click, any time, on the list below <br/> to change of basket");
if (!defined("_VIEWED"))
    define("_VIEWED", "Saw?");
if (!defined("_SEE_BASKETS_RELATED"))
    define("_SEE_BASKETS_RELATED", "See the associated baskets");
if (!defined("_GO_MANAGE_BASKET"))
    define("_GO_MANAGE_BASKET", "Modify");

//NEW WF
if (!defined("_WF"))
    define("_WF", "Workflow");
if (!defined("_POSITION"))
    define("_POSITION", "Position");
if (!defined("_ADVANCE_TO"))
    define("_ADVANCE_TO", "Move toward");
if (!defined("_VALID_STEP"))
    define("_VALID_STEP", "Validate the step");
if (!defined("_BACK_TO"))
    define("_BACK_TO", "Back toward");
if (!defined("_FORWARD_IN_THE_WF"))
    define("_FORWARD_IN_THE_WF", "Move on in the WF");
if (!defined("_BACK_IN_THE_WF"))
    define("_BACK_IN_THE_WF", "Back in the workflow");
if (!defined("_ITS_NOT_MY_TURN_IN_THE_WF"))
    define("_ITS_NOT_MY_TURN_IN_THE_WF", "It's not my turn on the workflow");
if (!defined("_COMBINATED_ACTION"))
    define("_COMBINATED_ACTION", "Combined action");
if (!defined("_END_OF_THE_WF"))
    define("_END_OF_THE_WF", "Workflow end");
