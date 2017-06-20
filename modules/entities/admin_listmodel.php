<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   admin_listmodel
* @author  dev <dev@maarch.org>
* @ingroup entities
*/

require_once 'modules/entities/class/class_manage_listdiff.php';
require_once 'core/class/usergroups_controler.php';
$core_tools = new core_tools();

$admin = new core_tools();
$admin->test_admin('admin_listmodels', 'entities');
 /****************Management of the location bar  ************/
$init = false;
if (isset($_REQUEST['reinit']) && $_REQUEST['reinit'] == "true") {
    $init = true;
}
$level = "";
if (isset($_REQUEST['level']) &&($_REQUEST['level'] == 2 || $_REQUEST['level'] == 3 || $_REQUEST['level'] == 4 || $_REQUEST['level'] == 1)) {
    $level = $_REQUEST['level'];
}
$page_path = $_SESSION['config']['businessappurl'].'index.php?page=amdin_listmodel&module=entities';
$page_label = _ADMIN_LISTMODEL;
$page_id = "amdin_listmodel";
$admin->manage_location_bar($page_path, $page_label, $page_id, $init, $level);

$difflist = new diffusion_list();

//all roles available
$roles = $difflist->list_difflist_roles();

//list difflist_types
$difflistTypes = $difflist->list_difflist_types();

$mode = $_REQUEST['mode'];

$objectType = trim(strtok($_REQUEST['id'], '|'));
$objectId = strtok('|');

//Load listmodel into session  
if (!isset($_SESSION['m_admin']['entity']['listmodel'])) {
    //Listmodel to be loaded (up action on list or reload in add mode)
    $_SESSION['m_admin']['entity']['difflist_type'] = $difflist->get_difflist_type($objectType);
    
    $_SESSION['m_admin']['entity']['listmodel'] = $difflist->get_listmodel($objectType, $objectId);

    $title =  $_SESSION['m_admin']['entity']['listmodel']['title'];
    $description =  $_SESSION['m_admin']['entity']['listmodel']['description'];
} else {
    //list already loaded and managed (reload after update of list)
    $objectType = $_SESSION['m_admin']['entity']['listmodel']['object_type'];
    $objectId = $_SESSION['m_admin']['entity']['listmodel']['object_id'];
    $title = $_SESSION['m_admin']['entity']['listmodel']['title'];
    $description =  $_SESSION['m_admin']['entity']['listmodel']['description'];
}
?>

<script type="text/javascript">
/**
 *  [OnChange ObjectType / onLoad]
 */
function listmodel_setObjectType() 
{
    var objectType = $('objectType').value;
    var objectType_info = $('objectType_info');
    new Ajax.Request(
        'index.php?display=true&module=entities&page=admin_listmodel_setObjectType',
        {
            method:'post',
            parameters: 
            { 
                objectType : objectType
            },
            onSuccess: function(answer){
                objectType_info.innerHTML = answer.responseText;
                objectType_info.style.display = 'block';
            }
        }
    );
}

function listmodel_setObjectId(objectId) 
{
    var mode = $('mode').value;
    
    var objectType = $('objectType').value;
    var objectId_input = $('objectId_input');

    new Ajax.Request(
        'index.php?display=true&module=entities&page=admin_listmodel_setObjectId',
        {
            method:'post',
            parameters: 
            { 
                mode : mode,
                objectType : objectType,
                objectId : objectId
            },
            onSuccess: function(answer){
                objectId_input.innerHTML = answer.responseText;
                objectId_input.style.display = 'block';
                if(objectId != ''){
                   new Chosen($('objectId'),{width: "300px", disable_search_threshold: 10, search_contains: true});
                }
            }
        }
    );

}
 
function listmodel_open()
{
    var main_error = $('main_error'); 
    
    // Validate form
    var valid = listmodel_validate();
    
    if(valid == false)
        return;
    
    // Open pop up 
    window.open(
        'index.php?display=true&module=entities&page=manage_listmodel',
        '',
        'scrollbars=yes,menubar=no,toolbar=no,status=no,resizable=yes,width=1024,height=650,location=no'
    );
                    
    
}

function listmodel_validate() {
    // Control input values
    var main_error = $('main_error'); 
       
    var mode = $('mode').value; 
    var objectType = $('objectType').value; 
    var objectId = $('objectId').value; 
    var title = $('title').value;
    var description = $('description').value; 
    
    main_error.innerHTML = "";
    
    new Ajax.Request(
        'index.php?display=true&module=entities&page=admin_listmodel_validateHeader',
        {
            method:'post',
            asynchronous:false,
            parameters: 
            { 
                mode : mode,
                objectType : objectType,
                objectId : objectId,
                title : title,
                description : description 
            },
            onSuccess: function(answer) {
                if(answer.responseText) {
                    main_error.innerHTML += answer.responseText;
                    this.valid = false;
                    main_error.style.display = 'table-cell';
                    Element.hide.delay(10, 'main_error');
                } else {
                    this.valid = true;
                }
            }
        }
    ); 
    return this.valid;
}

function listmodel_save()
{
    var mode = $('mode').value;  
    var objectType = $('objectType').value; 
    var objectId = $('objectId').value;
    var title = $('title').value; 
    var description = $('description').value; 
    
    
    // Validate form
    var valid = listmodel_validate();
    if(valid == false)
        return;
    
    // Check if type/id already used
    new Ajax.Request(
        'index.php?display=true&module=entities&page=admin_listmodel_save',
        {
            method:'post',
            parameters: 
            { 
                mode : mode,
                objectType : objectType,
                objectId : objectId,
                title : title,
                description : description
            },
            onSuccess: function(answer){
                if(answer.responseText)
                    main_error.innerHTML = answer.responseText;
                else {
                    goTo('index.php?module=entities&page=admin_listmodels');
                }
            }
        }
    ); 
}

function listmodel_del(
    objectType,
    objectId
) {    
    new Ajax.Request(
        'index.php?display=true&module=entities&page=admin_listmodel_save',
        {
            method:'post',
            parameters: 
            { 
                mode : 'del',
                objectType : objectType,
                objectId : objectId,
            },
            onSuccess: function(answer){
                if(answer.responseText)
                    main_error.innerHTML = answer.responseText;
                else {
                    goTo('index.php?module=entities&page=admin_listmodels');
                }
            }
        }
    ); 

}
</script>

<?php
$frm = '';
if ($mode != 'del') {
    //TITLE
    $frm .= '<h1><i class="fa fa-share-alt-square fa-2x"></i> '._ADMIN_LISTMODEL;
    if ($objectType) {
        $frm .= ' : '.$difflistTypes[$objectType];
    }
    if ($objectId) {
        $frm .= ' '.$objectId;
    }
    $frm .= '</h1>';

    $frm .= '<br/>';

    //RIGHT BOX
    $frm .= '<div id="listmodel_box" class="block" style="height:550px;">';
    $frm .= '<h2 class="tit">'._LINKED_DIFF_LIST.'</h2>';
    $difflist = $_SESSION['m_admin']['entity']['listmodel'];
    echo $frm;
    include_once 'modules/entities/difflist_display.php';
    $frm = '<p class="buttons" style="text-align:center;margin-top:5px;">';
    $frm .= '<input type="button" onclick="listmodel_open()" class="button" value="'._MODIFY_LIST.'"';
    $frm .= '</p>';
    $frm .= '</div>';

    //LEFT BOX
    $frm .= '<div class="block" style="float:left;width:65%;height:550px;">';
    $frm .= '<table style="margin:auto;">';
    $frm .= '<tr height="20px;">';
    $frm .= '<td>';
    $frm .= '<input type="hidden" id="mode" value="'.$_REQUEST['mode'].'" />';
    $frm .= '</td>';
    $frm .= '</tr>';
    $frm .= '<tr>';
    $frm .= '<td width="33%">';
    $frm .= '<label for="objectType" >'._OBJECT_TYPE.' : </label>';
    $frm .= '</td>';
    $frm .= '<td>';
    $frm .= '<select name="objectType" id="objectType" onChange="listmodel_setObjectType();listmodel_setObjectId();" style="width:300px;"';
    if ($mode == 'up') {
        $frm .= ' disabled="true" ';
    }
    $frm .= '>';
    $frm .= '<option value="" >'._SELECT_OBJECT_TYPE.'</option>';
    foreach ($difflistTypes as $difflistTypeId => $difflistTypeLabel) {
        $frm .= '<option value="'.$difflistTypeId.'"';
        if ($objectType == $difflistTypeId) {
            $frm .= ' selected="true" ';
        }
        $frm .= '>';
        $frm .= $difflistTypeLabel;
        $frm .= '</option>';
    }
    $frm .= '</select>';
    $frm .= '<script type="text/javascript">new Chosen($(\'objectType\'),{width: "300px", disable_search_threshold: 10, search_contains: true});</script>';
    $frm .= '</td>';
    $frm .= '</tr>';
    $frm .= '<tr>';
    $frm .= '<td>';
    $frm .= '<label for="objectId" >'._ID.' : </label>';
    $frm .= '</td>';
    $frm .= '<td>';
    $frm .= '<div id="objectId_input">';
    if ($mode == 'up') {
        $frm .= ' <input type="text" id="objectId" disabled="true" value="'.$objectId.'" />';
    } else {
        $frm .= '<script type="text/javascript">';
        $frm .= 'listmodel_setObjectId(\''.$objectId.'\');';
        $frm .= '</script>';
    }
    $frm .= '</div>';
    $frm .= '</td>';
    $frm .= '</tr>';
    $frm .= '<tr>';
    $frm .= '<td>';
    $frm .= '<label for="title" >'._TITLE.' : </label>';
    $frm .= '</td>';
    $frm .= '<td>';
    $frm .= '<textarea id="title" style="width:294px;">'.$title.'</textarea>';
    $frm .= '</td>';
    $frm .= '</tr>';
    $frm .= '<tr>';
    $frm .= '<td>';
    $frm .= '<label for="description"  >'._DESCRIPTION.' : </label>';
    $frm .= '</td>';
    $frm .= '<td>';
    $frm .= '<textarea id="description" style="width:294px;">'.$description.'</textarea>';
    $frm .= '</td>';
    $frm .= '</tr>';
    $frm .= '<tr>';
    $frm .= '<td>';
    $frm .= '<label for="objectType_info" >'._DIFFLIST_TYPE_ROLES.' : </label>';
    $frm .= '</td>';
    $frm .= '<td>';
    $frm .= '<span id="objectType_info">'.trim($_SESSION['m_admin']['entity']['difflist_type']->difflist_type_roles).'</span>';
    $frm .= '</td>';
    $frm .= '</tr>';
    $frm .= '</table>';
    $frm .= '<br/>';
    $frm .= '<br/>';
    $frm .= '<p class="buttons" style="text-align:center;">';
    if ($objectType && $objectId) {
        $frm .= '<input type="button" onclick="listmodel_save();" class="button" value="'._SAVE_LISTMODEL.'" />';
    }
    $frm .= ' <input type="button" onclick="goTo(\'index.php?module=entities&page=admin_listmodels\');" class="button" value="'._CANCEL.'" />';
    $frm .= '</p>';
    $frm .= '</div>';
    $frm .= '</div>';

    $frm .= '<div class="clearfix"></div>';
}

//DEL => REDIRECT TO AJAX SAVE
if ($_REQUEST['mode'] == 'del') {
    $frm .= '<script type="text/javascript">';
    $frm .= 'listmodel_del(\''.$objectType.'\',\''.$objectId.'\');';
    $frm .= '</script>';
}
    
echo $frm;