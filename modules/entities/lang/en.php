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

/*************************** Entites management *****************/
if (!defined('_ADD_ENTITY')) {
    define('_ADD_ENTITY', 'Added department');
}
if (!defined('_ENTITY_ADDITION')) {
    define('_ENTITY_ADDITION', 'Department addition');
}
if (!defined('_ENTITY_MODIFICATION')) {
    define('_ENTITY_MODIFICATION', 'Department modification');
}
if (!defined('_ENTITY_AUTORIZATION')) {
    define('_ENTITY_AUTORIZATION', 'Department authorization');
}
if (!defined('_ENTITY_SUSPENSION')) {
    define('_ENTITY_SUSPENSION', 'Department suspension');
}
if (!defined('_ENTITY_DELETION')) {
    define('_ENTITY_DELETION', 'Department deletion');
}
if (!defined('_ENTITY_DELETED')) {
    define('_ENTITY_DELETED', 'Deleted department');
}
if (!defined('_ENTITY_UPDATED')) {
    define('_ENTITY_UPDATED', ' Modified department');
}
if (!defined('_ENTITY_AUTORIZED')) {
    define('_ENTITY_AUTORIZED', 'Authorized department');
}
if (!defined('_ENTITY_SUSPENDED')) {
    define('_ENTITY_SUSPENDED', 'Suspended department');
}
if (!defined('_ENTITY')) {
    define('_ENTITY', 'Department');
}
if (!defined('_ENTITIES')) {
    define('_ENTITIES', 'Departments');
}
if (!defined('_ENTITIES_COMMENT')) {
    define('_ENTITIES_COMMENT', 'Departments');
}
if (!defined('_ALL_ENTITIES')) {
    define('_ALL_ENTITIES', 'All the departments');
}
if (!defined('_ENTITIES_LIST')) {
    define('_ENTITIES_LIST', 'Departments list');
}
if (!defined('_MANAGE_ENTITIES')) {
    define('_MANAGE_ENTITIES', 'Manage the departments');
}
if (!defined('_ENTITY_MISSING')) {
    define('_ENTITY_MISSING', "This department doesn't exist");
}
if (!defined('_ENTITY_TREE')) {
    define('_ENTITY_TREE', 'Departments tree view');
}
if (!defined('_ENTITY_TREE_DESC')) {
    define('_ENTITY_TREE_DESC', 'See departments tree view');
}
if (!defined('_ENTITY_HAVE_CHILD')) {
    define('_ENTITY_HAVE_CHILD', 'This department owns sub-departments');
}
if (!defined('_ENTITY_IS_RELATED')) {
    define('_ENTITY_IS_RELATED', 'This department is linked to users');
}
if (!defined('_TYPE')) {
    define('_TYPE', 'Type');
}

/*************************** Users - Entites management *****************/
if (!defined('_ENTITY_USER_DESC')) {
    define('_ENTITY_USER_DESC', 'Connect departments and users');
}
if (!defined('_ENTITIES_USERS')) {
    define('_ENTITIES_USERS', 'Departments - users relationship');
}
if (!defined('_ENTITIES_USERS_LIST')) {
    define('_ENTITIES_USERS_LIST', 'Users list');
}
if (!defined('_USER_ENTITIES_TITLE')) {
    define('_USER_ENTITIES_TITLE', 'The user belongs to the following departments');
}
if (!defined('_USER_ENTITIES_ADDITION')) {
    define('_USER_ENTITIES_ADDITION', 'User- Departments connections');
}
if (!defined('_USER_BELONGS_NO_ENTITY')) {
    define('_USER_BELONGS_NO_ENTITY', "The user doesn't belong to any department");
}
if (!defined('_CHOOSE_ONE_ENTITY')) {
    define('_CHOOSE_ONE_ENTITY', 'Choose a department at least');
}
if (!defined('_CHOOSE_ENTITY')) {
    define('_CHOOSE_ENTITY', 'Choose a department');
}
if (!defined('_CHOOSE_PRIMARY_ENTITY')) {
    define('_CHOOSE_PRIMARY_ENTITY', 'Choose as primary department');
}
if (!defined('_PRIMARY_ENTITY')) {
    define('_PRIMARY_ENTITY', 'Primary department');
}
if (!defined('_DELETE_ENTITY')) {
    define('_DELETE_ENTITY', 'Delete department(s)');
}
if (!defined('USER_ADD_ENTITY')) {
    define('USER_ADD_ENTITY', 'Add a department');
}
if (!defined('_ADD_TO_ENTITY')) {
    define('_ADD_TO_ENTITY', 'Add to a department');
}
if (!defined('_NO_ENTITY_SELECTED')) {
    define('_NO_ENTITY_SELECTED', 'No selected entity');
}
if (!defined('_NO_PRIMARY_ENTITY')) {
    define('_NO_PRIMARY_ENTITY', 'The primary department is mandatory');
}
if (!defined('_NO_ENTITIES_DEFINED_FOR_YOU')) {
    define('_NO_ENTITIES_DEFINED_FOR_YOU', ' No defined entity for this user');
}
if (!defined('_LABEL_MISSING')) {
    define('_LABEL_MISSING', 'The department name is missing');
}
if (!defined('_SHORT_LABEL_MISSING')) {
    define('_SHORT_LABEL_MISSING', 'The department short name is missing');
}
if (!defined('_ID_MISSING')) {
    define('_ID_MISSING', 'The department ID is missing');
}
if (!defined('_TYPE_MISSING')) {
    define('_TYPE_MISSING', 'The department type is mandatory');
}
if (!defined('_PARENT_MISSING')) {
    define('_PARENT_MISSING', 'The relative department is mandatory');
}
if (!defined('_ENTITY_UNKNOWN')) {
    define('_ENTITY_UNKNOWN', 'Unknown department');
}

/*************************** Entites form *****************/
if (!defined('_ENTITY_LABEL')) {
    define('_ENTITY_LABEL', 'Name');
}
if (!defined('_SHORT_LABEL')) {
    define('_SHORT_LABEL', 'Short name');
}
if (!defined('_ENTITY_FULL_NAME')) {
    define('_ENTITY_FULL_NAME', 'Full name');
}
if (!defined('_ENTITY_ADR_1')) {
    define('_ENTITY_ADR_1', 'Address 1');
}
if (!defined('_ENTITY_ADR_2')) {
    define('_ENTITY_ADR_2', 'Address 2');
}
if (!defined('_ENTITY_ADR_3')) {
    define('_ENTITY_ADR_3', 'Address 3');
}
if (!defined('_ENTITY_ZIPCODE')) {
    define('_ENTITY_ZIPCODE', 'Postal code');
}
if (!defined('_ENTITY_CITY')) {
    define('_ENTITY_CITY', 'City');
}
if (!defined('_ENTITY_COUNTRY')) {
    define('_ENTITY_COUNTRY', ' Country');
}
if (!defined('_ENTITY_EMAIL')) {
    define('_ENTITY_EMAIL', 'E-mail');
}
if (!defined('_ENTITY_BUSINESS')) {
    define('_ENTITY_BUSINESS', 'SIRET Number');
}
if (!defined('_ENTITY_PARENT')) {
    define('_ENTITY_PARENT', 'Relative department');
}
if (!defined('_CHOOSE_ENTITY_PARENT')) {
    define('_CHOOSE_ENTITY_PARENT', 'Choose the relative department');
}
if (!defined('_CHOOSE_FILTER_ENTITY')) {
    define('_CHOOSE_FILTER_ENTITY', 'Filter by department');
}
if (!defined('_CHOOSE_ENTITY_TYPE')) {
    define('_CHOOSE_ENTITY_TYPE', 'Choose the department type');
}
if (!defined('_ENTITY_TYPE')) {
    define('_ENTITY_TYPE', 'Department type');
}
if (!defined('_TO_USERS_OF_ENTITIES')) {
    define('_TO_USERS_OF_ENTITIES', 'To departments users');
}
if (!defined('_ALL_ENTITIES')) {
    define('_ALL_ENTITIES', 'All the departments');
}
if (!defined('_ENTITIES_JUST_BELOW')) {
    define('_ENTITIES_JUST_BELOW', 'Immediatly lower than the primary department');
}
if (!defined('_ALL_ENTITIES_BELOW')) {
    define('_ALL_ENTITIES_BELOW', 'Lower than the primary department');
}
if (!defined('_ENTITIES_JUST_UP')) {
    define('_ENTITIES_JUST_UP', 'Immediatly higher than the primary department');
}
if (!defined('_ENTITIES_BELOW')) {
    define('_ENTITIES_BELOW', 'Lower than all my departments');
}
if (!defined('_MY_ENTITIES')) {
    define('_MY_ENTITIES', "All user's departments");
}
if (!defined('_MY_PRIMARY_ENTITY')) {
    define('_MY_PRIMARY_ENTITY', 'Primary department');
}
if (!defined('_SAME_LEVEL_ENTITIES')) {
    define('_SAME_LEVEL_ENTITIES', "Same level than the primary department // Même niveau de l'entité primaire");
}
if (!defined('_INDEXING_ENTITIES')) {
    define('_INDEXING_ENTITIES', 'Index for departments');
}
if (!defined('_SEARCH_DIFF_LIST')) {
    define('_SEARCH_DIFF_LIST', 'Search a department or an user');
}
if (!defined('_ADD_CC')) {
    define('_ADD_CC', 'Add on copy');
}
if (!defined('_TO_DEST')) {
    define('_TO_DEST', 'Recipient');
}
if (!defined('_NO_DIFF_LIST_ASSOCIATED')) {
    define('_NO_DIFF_LIST_ASSOCIATED', 'No diffusion list');
}
if (!defined('_PRINCIPAL_RECIPIENT')) {
    define('_PRINCIPAL_RECIPIENT', 'Main recipient');
}
if (!defined('_UPDATE_LIST_DIFF_IN_DETAILS')) {
    define('_UPDATE_LIST_DIFF_IN_DETAILS', 'Update diffusion list from the detailed page');
}
if (!defined('_UPDATE_LIST_DIFF')) {
    define('_UPDATE_LIST_DIFF', 'Modify diffusion list');
}
if (!defined('_DIFF_LIST_COPY')) {
    define('_DIFF_LIST_COPY', 'Diffusion list');
}
if (!defined('_DIFF_LIST')) {
    define('_DIFF_LIST', 'Diffusion list');
}
if (!defined('_NO_USER')) {
    define('_NO_USER', 'No user');
}
if (!defined('_MUST_CHOOSE_DEST')) {
    define('_MUST_CHOOSE_DEST', 'You have to select one recipient at least');
}
if (!defined('_ENTITIES__DEL')) {
    define('_ENTITIES__DEL', 'Deletion');
}
if (!defined('_ENTITY_DELETION')) {
    define('_ENTITY_DELETION', 'Department deletion');
}
if (!defined('_THERE_ARE_NOW')) {
    define('_THERE_ARE_NOW', 'Now, there are ...');
}
if (!defined('_DOC_IN_THE_DEPARTMENT')) {
    define('_DOC_IN_THE_DEPARTMENT', 'Associated mail(s) to the department');
}
if (!defined('_DEL_AND_REAFFECT')) {
    define('_DEL_AND_REAFFECT', 'Delete and reassign');
}
if (!defined('_THE_ENTITY')) {
    define('_THE_ENTITY', 'The department');
}
if (!defined('_USERS_LINKED_TO')) {
    define('_USERS_LINKED_TO', 'user(s) associated to the department');
}
if (!defined('_ENTITY_MANDATORY_FOR_REDIRECTION')) {
    define('_ENTITY_MANDATORY_FOR_REDIRECTION', 'Mandatory department for the reassigment');
}
if (!defined('_WARNING_MESSAGE_DEL_ENTITY')) {
    define('_WARNING_MESSAGE_DEL_ENTITY', 'Warning :<br> The department deletion leads to mails and users reassigment to a new department. It also reassigns mails on pending handling, the diffusion list templates and response templates toward a replacement department.');
}

/******************** Keywords Helper ************/
if (!defined('_HELP_KEYWORD1')) {
    define('_HELP_KEYWORD1', "All the departments attached to a connected user. Doesn't include subtitles");
}
if (!defined('_HELP_KEYWORD2')) {
    define('_HELP_KEYWORD2', "Connected user's primary department");
}
if (!defined('_HELP_KEYWORD3')) {
    define('_HELP_KEYWORD3', 'Sub-department from the arguments list, which might be @my_entities or @my_primary_entity');
}
if (!defined('_HELP_KEYWORD4')) {
    define('_HELP_KEYWORD4', 'Relative department to the department on argument');
}
if (!defined('_HELP_KEYWORD5')) {
    define('_HELP_KEYWORD5', 'All departments on the same level as the department on arguments');
}
if (!defined('_HELP_KEYWORD6')) {
    define('_HELP_KEYWORD6', 'All the departments (actives)');
}
if (!defined('_HELP_KEYWORD7')) {
    define('_HELP_KEYWORD7', 'Immediate sub-departments (n-1) from the departments given on argument');
}
if (!defined('_HELP_KEYWORD8')) {
    define('_HELP_KEYWORD8', 'Forefather department to the one that is given to the level asked on second argument (or the first one - root- if there is no argument 2');
}
if (!defined('_HELP_KEYWORD9')) {
    define('_HELP_KEYWORD9', 'All departments of the type put on argument');
}
if (!defined('_HELP_KEYWORDS')) {
    define('_HELP_KEYWORDS', 'Help on the keywords');
}
if (!defined('_HELP_KEYWORD_EXEMPLE_TITLE')) {
    define('_HELP_KEYWORD_EXEMPLE_TITLE', "Example in the goup security definition (where clause) : access on the resources about the connected user's main belongings department, or the subdepartments of this department.");
}
if (!defined('_HELP_KEYWORD_EXEMPLE')) {
    define('_HELP_KEYWORD_EXEMPLE', 'where_clause : (DESTINATION = @my_primary_entity or DESTINATION in (@subentities[@my_primary_entity]))');
}
if (!defined('_HELP_BY_ENTITY')) {
    define('_HELP_BY_ENTITY', 'Keywords of the department module');
}
if (!defined('_BASKET_REDIRECTIONS_OCCURS_LINKED_TO')) {
    define('_BASKET_REDIRECTIONS_OCCURS_LINKED_TO', 'Number of basket redirection associated to the department');
}
if (!defined('_TEMPLATES_LINKED_TO')) {
    define('_TEMPLATES_LINKED_TO', 'Response template(s) associated to a department');
}
if (!defined('_LISTISTANCES_OCCURS_LINKED_TO')) {
    define('_LISTISTANCES_OCCURS_LINKED_TO', 'Number of mails to handle or on copy associated to a department');
}
if (!defined('_LISTMODELS_OCCURS_LINKED_TO')) {
    define('_LISTMODELS_OCCURS_LINKED_TO', 'Diffusion template associated to the department');
}
if (!defined('_CHOOSE_REPLACEMENT_DEPARTMENT')) {
    define('_CHOOSE_REPLACEMENT_DEPARTMENT', 'Choose a replacing department');
}
if (!defined('_WRONG_DATE_FORMAT')) {
    define('_WRONG_DATE_FORMAT', 'Wrong date format');
}
/******************** Action put in copy ************/
if (!defined('_ALL_LIST')) {
    define('_ALL_LIST', 'Display all the list');
}

 /******************** Listinstance roles ***********/
if (!defined('_DEST_OR_COPY')) {
    define('_DEST_OR_COPY', 'Recipient');
}
if (!defined('_SUBMIT')) {
    define('_SUBMIT', 'Validate');
}
if (!defined('_CANCEL')) {
    define('_CANCEL', 'Cancel');
}
if (!defined('_DIFFLIST_TYPE_ROLES')) {
    define('_DIFFLIST_TYPE_ROLES', 'Available roles');
}
if (!defined('_NO_AVAILABLE_ROLE')) {
    define('_NO_AVAILABLE_ROLE', 'No available role');
}

 /******************** Difflist types ***********/
if (!defined('_ALL_DIFFLIST_TYPES')) {
    define('_ALL_DIFFLIST_TYPES', 'All the types');
}
if (!defined('_DIFFLIST_TYPES_DESC')) {
    define('_DIFFLIST_TYPES_DESC', 'Types of diffusion lists');
}
if (!defined('_DIFFLIST_TYPES')) {
    define('_DIFFLIST_TYPES', 'Types of diffusion lists');
}
if (!defined('_DIFFLIST_TYPE')) {
    define('_DIFFLIST_TYPE', 'Type(s) of list');
}
if (!defined('_ADD_DIFFLIST_TYPE')) {
    define('_ADD_DIFFLIST_TYPE', 'Add a type');
}
if (!defined('_DIFFLIST_TYPE_ID')) {
    define('_DIFFLIST_TYPE_ID', 'ID');
}
if (!defined('_DIFFLIST_TYPE_LABEL')) {
    define('_DIFFLIST_TYPE_LABEL', 'Description');
}
if (!defined('_ALLOW_ENTITIES')) {
    define('_ALLOW_ENTITIES', 'Authorize the departments');
}

 /******************** Listmodels ***********/
if (!defined('_ALL_LISTMODELS')) {
    define('_ALL_LISTMODELS', 'All the lists');
}
if (!defined('_LISTMODELS_DESC')) {
    define('_LISTMODELS_DESC', 'Diffusion list templates of mails and folders');
}
if (!defined('_LISTMODELS')) {
    define('_LISTMODELS', 'Diffusion list templates');
}
if (!defined('_MANAGE_LISTMODELS_DESC')) {
    define('_MANAGE_LISTMODELS_DESC', 'Manage avis and visa circuit models, which can be used in mail.');
}
if (!defined('_LISTMODEL')) {
    define('_LISTMODEL', 'List template(s)');
}
if (!defined('_ADD_LISTMODEL')) {
    define('_ADD_LISTMODEL', 'New template');
}
if (!defined('_ADMIN_LISTMODEL')) {
    define('_ADMIN_LISTMODEL', 'Diffusion list template');
}
if (!defined('_ADMIN_LISTMODEL_TITLE')) {
    define('_ADMIN_LISTMODEL_TITLE', ' List template identification:');
}
if (!defined('_OBJECT_TYPE')) {
    define('_OBJECT_TYPE', 'List template type');
}
if (!defined('_SELECT_OBJECT_TYPE')) {
    define('_SELECT_OBJECT_TYPE', 'Select a type...');
}
if (!defined('_SELECT_OBJECT_ID')) {
    define('_SELECT_OBJECT_ID', 'Select a link...');
}
if (!defined('_USER_DEFINED_ID')) {
    define('_USER_DEFINED_ID', 'Free');
}
if (!defined('_ALL_OBJECTS_ARE_LINKED')) {
    define('_ALL_OBJECTS_ARE_LINKED', 'All the lists are already definied');
}
if (!defined('_SELECT_OBJECT_TYPE_AND_ID')) {
    define('_SELECT_OBJECT_TYPE_AND_ID', 'You have to specify a list type and an ID!');
}
if (!defined('_SAVE_LISTMODEL')) {
    define('_SAVE_LISTMODEL', 'Validate');
}
if (!defined('_CONFIRM_LISTMODEL_SAVE')) {
    define('_CONFIRM_LISTMODEL_SAVE', 'Save the liste ?');
}

if (!defined('_ENTER_DESCRIPTION')) {
    define('_ENTER_DESCRIPTION', 'Mandatory description');
}
if (!defined('_ENTER_TITLE')) {
    define('_ENTER_TITLE', 'Mandatory title');
}
if (!defined('_ADMIN_DIFFLIST_TYPES')) {
    define('_ADMIN_DIFFLIST_TYPES', 'Diffusion list types (Administration)');
}
if (!defined('_ADMIN_DIFFLIST_TYPES_DESC')) {
    define('_ADMIN_DIFFLIST_TYPES_DESC', 'Administer the different types of diffusion list');
}
if (!defined('_ADMIN_LISTMODELS')) {
    define('_ADMIN_LISTMODELS', 'Diffusion templates (Administration)');
}
if (!defined('_ADMIN_LISTMODELS_DESC')) {
    define('_ADMIN_LISTMODELS_DESC', 'Administer the different diffusion templates');
}

/******************** RM ENTITIES ************/
if (!defined('_STANDARD')) {
    define('_STANDARD', 'Standard');
}

if (!defined('_VISIBLE')) {
    define('_VISIBLE', 'Active');
}
if (!defined('_NOT_VISIBLE')) {
    define('_NOT_VISIBLE', 'Not active');
}

/******** NEW WF ************/
if (!defined('_TARGET_STATUS')) {
    define('_TARGET_STATUS', 'Final status on the step validation');
}
if (!defined('_TARGET_ROLE')) {
    define('_TARGET_ROLE', 'Rôle à faire avancer dans le workflow');
}
if (!defined('_NO_FILTER')) {
    define('_NO_FILTER', 'Remove filters');
}

if (!defined('_AUTO_FILTER')) {
    define('_AUTO_FILTER', 'Suggested list');
}

if (!defined('_REDIRECT_NOTE')) {
    define('_REDIRECT_NOTE', 'Redirection reason (optional)');
}

if (!defined('_STORE_DIFF_LIST')) {
    define('_STORE_DIFF_LIST', 'Record the diffusion list');
}

if (!defined('_DIFF_LIST_STORED')) {
    define('_DIFF_LIST_STORED', 'Recorded diffusion list');
}

/////////////print_sep
if (!defined('_PRINT_SEPS')) {
    define('_PRINT_SEPS', 'Printing separators');
}

if (!defined('_CHOOSE_ENTITIES')) {
    define('_CHOOSE_ENTITIES', 'Choose the departments');
}
if (!defined('_DEL_USER_LISTDIFF')) {
    define('_DEL_USER_LISTDIFF', 'Remove the user from the diffusion list');
}

if (!defined('_DEL_ENTITY_LISTDIFF')) {
    define('_DEL_ENTITY_LISTDIFF', 'Remove the department from the diffusion list');
}

if (!defined('_ADD_USER_LISTDIFF')) {
    define('_ADD_USER_LISTDIFF', 'Add the user to the diffusion list');
}

if (!defined('_ADD_ENTITY_LISTDIFF')) {
    define('_ADD_ENTITY_LISTDIFF', 'Add the department to the diffusion list');
}

// SEDA
if (!defined('_ARCHIVAL_AGREEMENT')) {
    define('_ARCHIVAL_AGREEMENT', 'Archival agreement');
}
if (!defined('_ARCHIVAL_AGENCY')) {
    define('_ARCHIVAL_AGENCY', 'Archival agency');
}

if (!defined('_GO_TO_CC')) {
    define('_GO_TO_CC', 'Put the user in copy');
}
if (!defined('_GO_TO_DEST')) {
    define('_GO_TO_DEST', 'Put the user in recipient');
}
if (!defined('_UP_USER_ONE_ROW')) {
    define('_UP_USER_ONE_ROW', 'Move up the user for a row');
}
if (!defined('_DOWN_USER_ONE_ROW')) {
    define('_DOWN_USER_ONE_ROW', 'Move down the user for a row');
}
if (!defined('_UP_ENTITY_ONE_ROW')) {
    define('_UP_ENTITY_ONE_ROW', 'Move up the entity for a row');
}
if (!defined('_DOWN_ENTITY_ONE_ROW')) {
    define('_DOWN_ENTITY_ONE_ROW', 'Move down the entity for a row');
}

if (!defined('_TYPE_ID_HISTORY')) {
    define('_TYPE_ID_HISTORY', "Historic identifier");
}
if (!defined('_RES_ID')) {
    define('_RES_ID', "RES ID");
}
if (!defined('_UPDATED_BY_USER')) {
    define('_UPDATED_BY_USER', "Updated by");
}
