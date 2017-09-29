<?php
/*
 *
 *    Copyright 2013 Maarch
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

if (!defined("_FILEPLAN"))  
	define("_FILEPLAN","File plan");
if (!defined("_FILEPLAN_SHORT"))  
	define("_FILEPLAN_SHORT","File plan");
if (!defined("_PERSONNAL_FILEPLAN"))  
	define("_PERSONNAL_FILEPLAN","Personal file plan");
if (!defined("_FILEPLAN_COMMENT"))  
	define("_FILEPLAN_COMMENT","organizational file plan");
if (!defined("_PUT_DOC_IN_FILEPLAN"))  
	define("_PUT_DOC_IN_FILEPLAN","Classify documents");
if (!defined("_PUT_DOC_IN_FILEPLAN_COMMENT"))  
	define("_PUT_DOC_IN_FILEPLAN_COMMENT","Classify documents in the organizational file plan");
if (!defined("_ADMIN_MODULE_FILEPLAN"))
    define("_ADMIN_MODULE_FILEPLAN", "organizational file plan");
if (!defined("_ADMIN_MODULE_FILEPLAN_DESC"))
    define("_ADMIN_MODULE_FILEPLAN_DESC", "organizational file plan management (Allows to manage specific file plans to an entity).");

/*FILE PLAN MANAGEMENT*/	
if (!defined("_ADD_FILEPLAN"))
    define("_ADD_FILEPLAN","Create a file plan");
if (!defined("_CREATE_YOUR_PERSONNAL_FILEPLAN"))
    define("_CREATE_YOUR_PERSONNAL_FILEPLAN","Welcome to your personal file plan management.<br />Create a personal file plan to classify and find faster your documents.");
if (!defined("_FILEPLAN_NOT_EXISTS"))
    define("_FILEPLAN_NOT_EXISTS","This file plan does not exist");	
if (!defined("_FILEPLAN_ID"))
    define("_FILEPLAN_ID","File plan ID");	
if (!defined("_FILEPLAN_NAME"))
    define("_FILEPLAN_NAME","Name of the file plan");
if (!defined("_IS_SERIAL_ID"))
    define("_IS_SERIAL_ID","Automatic ID");		
if (!defined("_CHANGE_DEFAULT_FILEPLAN_NAME"))
    define("_CHANGE_DEFAULT_FILEPLAN_NAME","You can modify the file plan default name");
if (!defined("_FILEPLAN_ADDED"))
    define("_FILEPLAN_ADDED","File plan created");
if (!defined("_DUPLICATE_FILEPLAN"))
    define("_DUPLICATE_FILEPLAN","duplicate a file plan");
if (!defined("_MANAGE_FILEPLAN_SHORT"))
    define("_MANAGE_FILEPLAN_SHORT", "Manage");
if (!defined("_MANAGE_PERSONNAL_FILEPLAN"))
    define("_MANAGE_PERSONNAL_FILEPLAN", "Manage my personal file plan");
if (!defined("_MANAGE_FILEPLAN"))
    define("_MANAGE_FILEPLAN", "Manage the file plan");
if (!defined("_VIEW_FILEPLAN"))
    define("_VIEW_FILEPLAN", "Show file plan(s)");
if (!defined("_FILEPLAN_POSITIONS"))
    define("_FILEPLAN_POSITIONS", "Element(s)");
if (!defined("_DOC_IN_FILEPLAN"))
    define("_DOC_IN_FILEPLAN", "Documents");
if (!defined("_DELETE_FILEPLAN"))
    define("_DELETE_FILEPLAN"," Delete the file plan");
if (!defined("_DELETE_FILEPLAN_SHORT"))
    define("_DELETE_FILEPLAN_SHORT","Delete");	
if (!defined("_FILEPLAN_DELETED"))
    define("_FILEPLAN_DELETED", "File plan deleted");	
if (!defined("_EDIT_FILEPLAN"))
    define("_EDIT_FILEPLAN","Modify the file plan");	
if (!defined("_EDIT_FILEPLAN_SHORT"))
    define("_EDIT_FILEPLAN_SHORT","Modify");
if (!defined("_FILEPLAN_UPDATED"))
    define("_FILEPLAN_UPDATED", "Modified file plan");
if (!defined("_ENABLE_FILEPLAN"))
    define("_ENABLE_FILEPLAN", "Unlock");
if (!defined("_FILEPLAN_ENABLED"))
    define("_FILEPLAN_ENABLED", "Unlocked file plan");
if (!defined("_DISABLE_FILEPLAN"))
    define("_DISABLE_FILEPLAN", "Lock");    
if (!defined("_FILEPLAN_DISABLED"))
    define("_FILEPLAN_DISABLED", "Locked file plan");
if (!defined("_REMOVE_FILEPLAN_INFOS_1"))
    define("_REMOVE_FILEPLAN_INFOS_1", "Are you sure to want to delete the following file plan and remove linked files ?");
if (!defined("_REMOVE_FILEPLAN_INFOS_2"))
    define("_REMOVE_FILEPLAN_INFOS_2", "No document will be deleted.");
if (!defined("_CHOOSE_FILEPLAN"))
    define("_CHOOSE_FILEPLAN", "Please select a file plan");
	
/*LIST*/
if (!defined("_POSITION_PATH"))
    define("_POSITION_PATH", "Path");
if (!defined("_NO_DOC_IN_POSITION"))
    define("_NO_DOC_IN_POSITION", "None of the documents");
	
/*FORM*/
if (!defined("_POSITION_ID"))
    define("_POSITION_ID","Position ID");
if (!defined("_POSITION_NAME"))
    define("_POSITION_NAME","Name of the position");
if (!defined("_NEST_POSITION_UNDER"))
    define("_NEST_POSITION_UNDER","Position under");
if (!defined("_POSITION_PARENT"))
    define("_POSITION_PARENT","Relative position");
if (!defined("_CHOOSE_ONE_POSITION"))
    define("_CHOOSE_ONE_POSITION", "Please select a position at least");
if (!defined("_CHOOSE_PARENT_POSITION"))
    define("_CHOOSE_PARENT_POSITION", "Please select a relative position");
	
/*ACTIONS*/
	//ADD
if (!defined("_NEW_POSITION"))
    define("_NEW_POSITION","New position");
if (!defined("_ADD_POSITION"))
    define("_ADD_POSITION", "Create");
if (!defined("_POSITION_ADDED"))
    define("_POSITION_ADDED", "Added position");
	//EDIT
if (!defined("_EDIT_POSITION_SHORT"))
    define("_EDIT_POSITION_SHORT", "Modify");
if (!defined("_EDIT_POSITION"))
    define("_EDIT_POSITION", "Modified la position");
if (!defined("_POSITION_UPDATED"))
    define("_POSITION_UPDATED", "Modified position");
	//ENABLE/DISABLE
if (!defined("_ENABLE_POSITION"))
    define("_ENABLE_POSITION", "Unlock");
if (!defined("_POSITION_ENABLED"))
    define("_POSITION_ENABLED", "Unlocked position");
if (!defined("_DISABLE_POSITION"))
    define("_DISABLE_POSITION", "Unlock");    
if (!defined("_POSITION_DISABLED"))
    define("_POSITION_DISABLED", "Locked position");
	//REMOVE
if (!defined("_REMOVE_POSITION"))
    define("_REMOVE_POSITION", "Delete the position");
if (!defined("_REMOVE_POSITIONS"))
    define("_REMOVE_POSITIONS", "Delete the positions");
if (!defined("_REALLY_REMOVE_POSITION"))
    define("_REALLY_REMOVE_POSITION", "Are you sure to want to delete the position ?");
if (!defined("_REMOVE_POSITIONS_INFOS_1"))
    define("_REMOVE_POSITIONS_INFOS_1", "Do you want to delete the following positions et remove all the linked documents ?");
if (!defined("_REMOVE_POSITIONS_INFOS_2"))
    define("_REMOVE_POSITIONS_INFOS_2", "None of the document will be deleted.");
if (!defined("_DELETE_POSITION"))
    define("_DELETE_POSITION", "Delete");
if (!defined("_POSITION_REMOVED"))
    define("_POSITION_REMOVED", "Deleted position");
	//SET
if (!defined("_SET_DOC_TO_POSITION"))
    define("_SET_DOC_TO_POSITION", "Classify a document(s)");
if (!defined("_DOC_ADDED_TO_POSITION"))
    define("_DOC_ADDED_TO_POSITION", "Documents added to position");
if (!defined("_REMOVED_DOC_FROM_POSITION"))
    define("_REMOVED_DOC_FROM_POSITION", " Remove the document from the position");
if (!defined("_REALLY_REMOVE_DOC_FROM_POSITION"))
    define("_REALLY_REMOVE_DOC_FROM_POSITION", "Are you sure to want to remove the document from the position?");
if (!defined("_DOC_REMOVED_FROM_POSITION"))
    define("_DOC_REMOVED_FROM_POSITION", "Removed document from the position");
	//ERROR
if (!defined("_ERROR_DURING_POSITION_ID_GENERATION"))
    define("_ERROR_DURING_POSITION_ID_GENERATION", "Error during the position ID generation. (database error)");
if (!defined("_POSITION_NOT_EXISTS"))
    define("_POSITION_NOT_EXISTS", "This position does not exist");
if (!defined("_POSITION_ALREADY_EXISTS"))
    define("_POSITION_ALREADY_EXISTS", "This position ID already exists");
