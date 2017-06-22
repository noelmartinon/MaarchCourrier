<?php
/*
*   Copyright 2008-2013 Maarch
*
*   This file is part of Maarch Framework.
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
*   along with Maarch Framework.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief  manage contacts duplicates
*
*
* @file
* @author Laurent Giovannoni <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup admin
*/
require_once 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
    . DIRECTORY_SEPARATOR  . 'class' . DIRECTORY_SEPARATOR 
    . 'class_business_app_tools.php';
$admin = new core_tools();
$admin->test_admin('admin_contacts', 'apps');
$func = new functions();
$db = new Database();

$business = new business_app_tools();

$_SESSION['m_admin'] = array();
/****************Management of the location bar  ************/
$init = false;
if (isset($_REQUEST['reinit']) && $_REQUEST['reinit'] == "true") {
    $init = true;
}
$level = "";
if (
    isset($_REQUEST['level'])
    && (
        $_REQUEST['level'] == 2
        || $_REQUEST['level'] == 3
        || $_REQUEST['level'] == 4
        || $_REQUEST['level'] == 1
    )
) {
    $level = $_REQUEST['level'];
}
$page_path = $_SESSION['config']['businessappurl']
    . 'index.php?page=manage_duplicates&admin=contacts';
$page_label = _MANAGE_DUPLICATES;
$page_id = "manage_duplicates";
$admin->manage_location_bar($page_path, $page_label, $page_id, $init, $level);
/***********************************************************/
$color = array('#254e7b','#5584b1','#85c1e5','#a2adc3');
function randomColor($lastColor)
{
    if ($lastColor <> 0) {
        $val = rand(0, $lastColor - 1);
    } else {
        $val = 3;
    }
    return $val;
}

echo '<h1><i class="fa fa-magic fa-2x"></i>&nbsp;' 
        . _MANAGE_DUPLICATES 
    . '</h1>';

echo '<div id="inner_content">';
echo '<div class="block" style="text-align:left;">';
//TODO: ENABLE THIS FUNCTION FOR ALL COLLECTION USING CONTACTS

//update NULL to ''
$db->query("UPDATE contacts_v2 SET user_id='' WHERE user_id IS NULL");

//duplicates by society
$selectDuplicatesBySociety = "SELECT contact_id, society, lower(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ')) as society_comp from contacts_v2 
WHERE is_corporate_person = 'Y' AND lower(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ')) in (
SELECT lower(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ')) FROM contacts_v2 GROUP BY lower(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr '))
HAVING Count(lower(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr '))) > 1 and lower(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ')) <> '' ) 
order by lower(society),contact_id";

$htmlTabSoc = '<form name="manage_duplicate_society" action="#" onsubmit="return linkDuplicate(\'manage_duplicate_society\')" method="post">';
$htmlTabSoc .= '<table style="width:100%;" id="duplicates_society">';
$htmlTabSoc .= '<CAPTION>' . _DUPLICATES_BY_SOCIETY . '</CAPTION>';
$htmlTabSoc .= '<thead style="display:block;">';
$htmlTabSoc .= '<tr style="display:table;width:100%;">';

$htmlTabSoc .= '<th style="width:7%;">&nbsp;</th>';
$htmlTabSoc .= '<th style="width:8%;">' . _ID . '</th>';
$htmlTabSoc .= '<th style="width:30%;">' . _STRUCTURE_ORGANISM . '</th>';
$htmlTabSoc .= '<th style="width:40%;">' . _ADDRESS . '</th>';
$htmlTabSoc .= '<th style="width:10%;">' . _ADDRESS_NB. '</th>';
$htmlTabSoc .= '<th style="width:5%;">&nbsp;</th>';
$htmlTabSoc .= '</tr>';
$htmlTabSoc .= '</thead>';
$htmlTabSoc .= '<tbody style="width:100%;display:block;height: 400px;overflow-y: auto;overflow-x: hidden;color:white;">';
$tabSoc = array();
$socCompare = '';
$colorToUse = '';
$colorNumber = '2';
$stmt = $db->query($selectDuplicatesBySociety);

$cptSoc = 0;
$i = 0;
while($lineDoublSoc = $stmt->fetchObject()) {
    $stmt2 = $db->query("SELECT id,firstname,lastname,address_num,address_street,address_postal_code,address_town,email FROM contact_addresses WHERE contact_id = ?", array($lineDoublSoc->contact_id));
    $res = $stmt2->fetchObject();

    $nb_addresses = $stmt2->rowCount();
    $arr_address = array($res->address_num,$res->address_street,$res->address_postal_code,$res->address_town);
    $tmp_address = implode(' ', $arr_address);

    if(!empty($res->firstname) || !empty($res->lastname) ){
        $arr_address = array($res->firstname.' '.$res->lastname,$tmp_address);
        $address = implode(', ', $arr_address);
    }else{
        $address = $tmp_address;
    }

    if(!empty($res->email)){
        $address .= ' ('.$res->email.')';
    }

    if (empty(trim($address))) {
        $address = '<i style="color:red;">adresse vide ...</i>';
    }

    if ($lineDoublSoc->contact_id <> ''/* && $result_address->id <> '' */) {
        $cptSoc++;

        if ($socCompare == $lineDoublSoc->society_comp) {
            //echo 'doublon<br>';
        } else {
            $i++;
            //echo 'new doublon<br>';
            $class = 'duplicate_'.$i;
            if ($colorToUse == 'LightYellow'){
                $colorToUse = 'white';
            }else{
                $colorToUse = 'LightYellow';
            }
        }
        $socCompare = $lineDoublSoc->society_comp;
        $htmlTabSoc .= '<tr style="background-color: '.$colorToUse.';display:table;width:100%;color:#666;" id="tr_' . $lineDoublSoc->contact_id . '">';
        $htmlTabSoc .= '<td style="width:7%;"><input type="checkbox" class="'.$class.'" title="contact à fusionner" id="fusion_id_'.$lineDoublSoc->contact_id.'" name="slave_'.$class.'" value="'.$lineDoublSoc->contact_id.'" onclick="checkOthersDuplicate(\'manage_duplicate_society\',\'slave_'.$class.'\',\'master_'.$class.'\');" /> <input type="radio" title="contact maître servant de fusion" id="master_fusion_id_'.$lineDoublSoc->contact_id.'" name="master_'.$class.'" value="'.$lineDoublSoc->contact_id.'"/><input type="hidden" id="master_address_fusion_id_'.$lineDoublSoc->contact_id.'" name="master_address_'.$class.'" value="'.$res->id.'"/> <input type="checkbox" class="'.$class.'" id="delete_contact_id_'.$res->id.'" name="delete_address" title="supprimer l\'adresse" value="'.$res->id.'"/></td>';
        $htmlTabSoc .= '<td style="width:8%;">' . $lineDoublSoc->contact_id . '</td>';
        $htmlTabSoc .= '<td style="width:30%;">' . $lineDoublSoc->society . '</td>';
        $htmlTabSoc .= '<td style="width:40%;">' . $address . '</td>';
        $htmlTabSoc .= '<td style="width:10%;">' . $nb_addresses.' adresse(s) au total ...</td>';
        $htmlTabSoc .= '<td style="width:5%;"><i onclick="loadDocList('
            . $lineDoublSoc->contact_id . ');" class="fa fa-search fa-2x" title="'._IS_ATTACHED_TO_DOC.'" title="'
            . _IS_ATTACHED_TO_DOC . '" style="cursor: pointer;"></i></td>';
        $htmlTabSoc .= '</tr>';
        $htmlTabSoc .= '<tr id="docList_' . $lineDoublSoc->contact_id
            . '" name="docList_' . $lineDoublSoc->contact_id
            . '" style="display: none; width:100%;border-bottom: solid 1px black; '
            . 'background-color: #FFF;">';
        $htmlTabSoc .= '<td style="background-color: white;" colspan="9">';
        $htmlTabSoc .= '<div id="divDocList_' . $lineDoublSoc->contact_id
            . '" align="center" style="color: grey;" width="100%">';
        $htmlTabSoc .= '<img width="10%" style="background-color: white; '
            . 'margin: 0; padding: 0;" src="static.php?filename=loading_big.gif">';
        $htmlTabSoc .= '</div>';
        $htmlTabSoc .= '</td>';
        $htmlTabSoc .= '</tr>';
    }
    $comp_society = $lineDoublSoc->contact_id;
}
$htmlTabSoc .= '</tbody>';
//$func->show_array($tabSoc);
$htmlTabSoc .= '</table>';
$htmlTabSoc .= '<style>#duplicates_society tbody tr:hover{opacity:0.5 !important;}</style>';
$htmlTabSoc .= '<input type="submit" value="fusionner!" class="button"/>';
$htmlTabSoc .= '</form>';
if ($cptSoc == 0) {
    echo _NO_SOCIETY_DUPLICATES . '<br>';
} else {
    echo $htmlTabSoc;
}
?><br><?php
/***********************************************************************/
//duplicates by name
$selectDuplicatesByName = "SELECT contact_id, lower(translate(lastname||' '||firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ')) as lastname_firstname, society, society_short,is_corporate_person, lastname, firstname, title 
from contacts_v2 
WHERE lower(translate(lastname||' '||firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ')) in (
SELECT lower(translate(lastname||' '||firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ')) as lastname_firstname FROM contacts_v2 GROUP BY lastname_firstname 
HAVING Count(lower(translate(lastname||' '||firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr '))) > 1 and lower(translate(lastname||' '||firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ')) <> ' ') 
order by lower(translate(lastname||' '||firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ-','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr ')),contact_id";
$htmlTabName = '<form name="manage_duplicate_person" action="#" onsubmit="return linkDuplicate(\'manage_duplicate_person\')" method="post">';
$htmlTabName .= '<table style="width:100%;">';
$htmlTabName .= '<CAPTION>' . _DUPLICATES_BY_NAME . '</CAPTION>';
$htmlTabName .= '<thead style="display:block;">';
$htmlTabName .= '<tr style="display:table;width:100%;">';
$htmlTabName .= '<th style="width:10%;">&nbsp;</th>';
$htmlTabName .= '<th style="width:5%;">' . _ID . '</th>';
$htmlTabName .= '<th style="width:10%;">' . _TITLE2 . '</th>';
$htmlTabName .= '<th style="width:15%;">' . _LASTNAME . '</th>';
$htmlTabName .= '<th style="width:15%;">' . _FIRSTNAME . '</th>';
$htmlTabName .= '<th style="width:30%;">' . _ADDRESS.' </th>';
$htmlTabName .= '<th style="width:10%;">' . _ADDRESS_NB.' </th>';
$htmlTabName .= '<th style="width:5%;">&nbsp;</th>';
$htmlTabName .= '</tr>';
$htmlTabName .= '</thead>';
$htmlTabName .= '<tbody style="width:100%;display:block;height: 400px;overflow-y: auto;overflow-x: hidden;color:white;">';
$tabName = array();
$nameCompare = '';
$colorToUse = '';
$colorNumber = '2';
$stmt = $db->query($selectDuplicatesByName);
$cptName = 0;
$i++;
while($lineDoublName = $stmt->fetchObject()) {
    $stmt2 = $db->query("SELECT id,address_num,address_street,address_postal_code,address_town,email FROM contact_addresses WHERE contact_id = ?", array($lineDoublName->contact_id));
    $res = $stmt2->fetchObject();

    $nb_addresses = $stmt2->rowCount();
    $arr_address = array($res->address_num,$res->address_street,$res->address_postal_code,$res->address_town);
    $tmp_address = implode(' ', $arr_address);

    $address = $tmp_address;

    if(!empty($res->email)){
        $address .= ' ('.$res->email.')';
    }

    if (empty(trim($address))) {
        $address = '<i style="color:red;">adresse vide ...</i>';
    }
    if ($lineDoublName->contact_id <> '') {
        $cptName++;

        if ($nameCompare == $lineDoublName->lastname_firstname) {
            //echo 'doublon<br>';
        } else {
            //echo 'new doublon<br>';
            $i++;
            $class = 'duplicate_person_'.$i;
            if ($colorToUse == 'LightYellow'){
                $colorToUse = 'white';
            }else{
                $colorToUse = 'LightYellow';
            }
        }

        $corporatePeople = ($lineDoublName->is_corporate_person == "Y")? _YES : _NO;

        $nameCompare = $lineDoublName->lastname_firstname;
        $htmlTabName .= '<tr style="background-color: '.$colorToUse.';display:table;width:100%;color:#666;" id="tr_' . $lineDoublName->contact_id . '">';
        $htmlTabName .= '<td style="width:10%;"><input type="checkbox" class="'.$class.'" id="fusion_id_'.$lineDoublName->contact_id.'" name="slave_'.$class.'" title="contact à fusionner" value="'.$lineDoublName->contact_id.'" onclick="checkOthersDuplicate(\'manage_duplicate_person\',\'slave_'.$class.'\',\'master_'.$class.'\');"/> <input type="radio" id="master_fusion_id_'.$lineDoublName->contact_id.'" name="master_'.$class.'" title="contact maître servant de fusion" value="'.$lineDoublName->contact_id.'"/> <input type="hidden" id="master_address_fusion_id_'.$lineDoublName->contact_id.'" name="master_address_'.$class.'" value="'.$res->id.'"/> <input type="checkbox" class="'.$class.'" id="delete_contact_id_'.$res->id.'" name="delete_address" title="supprimer l\'adresse" value="'.$res->id.'"/></td>';
        $htmlTabName .= '<td style="width:5%;">' . $lineDoublName->contact_id . '</td>';
        $htmlTabName .= '<td style="width:10%;">' . $business->get_label_title($lineDoublName->title) . '</td>';
        $htmlTabName .= '<td style="width:15%;">' . $lineDoublName->lastname . '</td>';
        $htmlTabName .= '<td style="width:15%;">' . $lineDoublName->firstname . '</td>';
        $htmlTabName .= '<td style="width:30%;">' . $address . '</td>';
        $htmlTabName .= '<td style="width:10%;">' . $nb_addresses.' adresse(s) au total ...</td>';
        $htmlTabName .= '<td style="width:5%;"><i onclick="loadDocList('
            . $lineDoublName->contact_id . ');" class="fa fa-search fa-2x" title="'._IS_ATTACHED_TO_DOC.'" title="'
            . _IS_ATTACHED_TO_DOC . '" style="cursor: pointer;"></i></td>';
        $htmlTabName .= '</tr>';
        $htmlTabName .= '<tr id="deleteContactDiv_' . $lineDoublName->contact_id
            . '" name="deleteContactDiv_' . $lineDoublName->contact_id
            . '" style="display: none; border-bottom: solid 1px black; '
            . 'background-color: #FFF;">';
        $htmlTabName .= '<td style="background-color: white;" colspan="10">';
        $htmlTabName .= '<div id="divDeleteContact_' . $lineDoublName->contact_id
            . '" align="center" style="color: grey;" width="100%">';
        $htmlTabName .= '<img width="10%" style="background-color: white; '
            . 'margin: 0; padding: 0;" src="static.php?filename=loading_big.gif">';
        $htmlTabName .= '</div>';
        $htmlTabName .= '</td>';
        $htmlTabName .= '</tr>';
        $htmlTabName .= '<tr id="docList_' . $lineDoublName->contact_id
            . '" name="docList_' . $lineDoublName->contact_id
            . '" style="display: none; border-bottom: solid 1px black; '
            . 'background-color: #FFF;">';
        $htmlTabName .= '<td style="background-color: white;" colspan="10">';
        $htmlTabName .= '<div id="divDocList_' . $lineDoublName->contact_id
            . '" align="center" style="color: grey;" width="100%">';
        $htmlTabName .= '<img width="10%" style="background-color: white; '
            . 'margin: 0; padding: 0;" src="static.php?filename=loading_big.gif">';
        $htmlTabName .= '</div>';
        $htmlTabName .= '</td>';
        $htmlTabName .= '</tr>';
    }
}
$htmlTabName .= '</tbody>';
//$func->show_array($tabName);
$htmlTabName .= '</table>';
$htmlTabName .= '<input type="submit" value="fusionner!" class="button"/>';
$htmlTabName .= '</form>';
if ($cptName == 0) {
    echo '<hr/>'; 
    echo _NO_NAME_DUPLICATES . '<br>';
} else {
    echo $htmlTabName;
}
echo '</div>';
echo '</div>';