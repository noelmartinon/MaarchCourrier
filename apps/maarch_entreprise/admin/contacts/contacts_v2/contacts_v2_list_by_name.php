<?php
/*
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

/**
* @brief List of users for autocompletion
*
*
* @file
* @author  Laurent Giovannoni <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup admin
*/

require_once("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_request.php");
require_once("apps".DIRECTORY_SEPARATOR."maarch_entreprise".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_contacts_v2.php");
$contact = new contacts_v2();
$db = new Database();

$listArray = array();

$Input = $_REQUEST['what'];
$boldInput = strtoupper($Input);
$ucwordsInput = ucwords($Input);
$Input = $Input . ' ' . $boldInput . ' ' . $ucwordsInput;

$args = explode(' ', $Input);
$args[] = $Input;
$args_bold = array();
foreach ($args as $key => $value) {
    $args_bold[$key] = '<b>'. $value . '</b>';
}
echo "<ul id=\"autocomplete_contacts_ul\">";
//STEP 1 : search with lastname (physical contact)
    $query = "SELECT contact_id, contact_type, society, lastname, firstname, function, contact_id, is_corporate_person, society_short FROM contacts_v2 WHERE is_corporate_person = 'N' AND enabled = 'Y' AND enabled = 'Y' ";
    $query.= "AND (LOWER(translate(lastname || ' ' || firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))";
    $query.= "LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')))";
    $query.= "OR (LOWER(translate(firstname || ' ' || lastname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))";
    $query.= "LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')))";
    $query.= "OR (LOWER(translate(function,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))";
    $query.= "LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')))";
    $query.= "OR (LOWER(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))";
    $query.= "LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')))";
    $query.= "OR (LOWER(translate(society_short,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))";
    $query.= "LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')))";
    $query.= "ORDER BY lastname,firstname ASC";
    $arrayPDO = array('%'.$_REQUEST['what'].'%','%'.$_REQUEST['what'].'%','%'.$_REQUEST['what'].'%','%'.$_REQUEST['what'].'%','%'.$_REQUEST['what'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step1 = $stmt->rowCount();

    $m = 30;
    if ($nb_step1 >= $m) $l = $m;
    else $l = $nb_step1;

    $found = false;

    for ($i=0; $i<$l; $i++) {

        $res = $stmt->fetchObject();

        $isSociety = false;
        if(!empty($res->society)){
            if($res->firstname == null && $res->lastname == null){
                $arr_contact_info = array($res->firstname,$res->lastname,$res->society);
                $isSociety = true;
            } else {
                $arr_contact_info = array($res->firstname,$res->lastname,'('.$res->society.')');
                $contact_info = implode(' ', $arr_contact_info);
            }
        }else{
            $arr_contact_info = array($res->firstname,$res->lastname);
            $contact_info = implode(' ', $arr_contact_info);
        }

        if ($i%2==1) $color = 'LightYellow';
        else $color = 'white';

        if($isSociety){
            echo "<li id='".$res->contact_id."' style='font-size:12px;background-color:$color;'><i class='fa fa-building fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='structure'></i> "
                . '<span style="display:table-cell;vertical-align:middle;">'. str_replace($args, $args_bold, $res->society) .'</span>'
            ."</li>";
        } else {
            echo "<li id='".$res->contact_id."' style='font-size:12px;background-color:$color;'><i class='fa fa-user fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='personne physique'></i> "
                . '<span style="display:table-cell;vertical-align:middle;">' . str_replace($args, $args_bold, $contact_info) . '</span>'
            ."</li>";
        }
    }

    //STEP 2 : search with society(physical contact)
    /*$query = "SELECT contact_id,firstname,lastname,society FROM contacts_v2 WHERE is_corporate_person = 'N' AND enabled = 'Y' AND (LOWER(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) OR LOWER(translate(society_short,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))) ORDER BY society,lastname,firstname ASC";
    $arrayPDO = array('%'.$_REQUEST['what'].'%','%'.$_REQUEST['what'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step3 = $stmt->rowCount();

    $m = 30;
    if ($nb_step3 >= $m) $l = $m;
    else $l = $nb_step3;

    $found = false;

    for ($i=0; $i<$l; $i++) {

        $res = $stmt->fetchObject();

        if(!empty($res->society)){
            $arr_contact_info = array($res->firstname,$res->lastname,'('.$res->society.')');
        }else{
            $arr_contact_info = array($res->firstname,$res->lastname);
        }
        $contact_info = implode(' ', $arr_contact_info);

        if ($i%2==1) $color = 'LightYellow';
        else $color = 'white';

        echo "<li id='".$res->contact_id."' style='font-size:12px;background-color:$color;'><i class='fa fa-user fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='personne physique'></i> "
                . '<span style="display:table-cell;vertical-align:middle;">' . str_replace($args, $args_bold, $contact_info) . '</span>'
            ."</li>";
    }*/
    ///////////////////////

    //STEP 3 : search with society(corporate contact)
    /*$query = "SELECT contact_id, society FROM contacts_v2 WHERE is_corporate_person = 'Y' AND enabled = 'Y' AND (LOWER(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) OR LOWER(translate(society_short,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))) ORDER BY society ASC";
    $arrayPDO = array('%'.$_REQUEST['what'].'%','%'.$_REQUEST['what'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step4 = $stmt->rowCount();

    $m = 30;
    if ($nb_step4 >= $m) $l = $m;
    else $l = $nb_step4;

    $found = false;

    for ($i=0; $i<$l; $i++) {

        $res = $stmt->fetchObject();

        if ($i%2==1) $color = 'LightYellow';
        else $color = 'white';
        echo "<li id='".$res->contact_id."' style='font-size:12px;background-color:$color;'><i class='fa fa-building fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='structure'></i> "
                . '<span style="display:table-cell;vertical-align:middle;">'. str_replace($args, $args_bold, $res->society) .'</span>'
            ."</li>";
    }*/
    ///////////////////////

    //STEP 4 : search with other informations (physical contact)
    /*$query = "SELECT contact_id,firstname,lastname,function FROM contacts_v2 WHERE is_corporate_person = 'N' AND enabled = 'Y'"
            ." AND (LOWER(translate(firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))"
            ." OR LOWER(translate(function,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')))  ORDER BY society,lastname,firstname ASC";
    $arrayPDO = array('%'.$_REQUEST['what'].'%','%'.$_REQUEST['what'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step5 = $stmt->rowCount();

    $m = 30;
    if ($nb_step5 >= $m) $l = $m;
    else $l = $nb_step5;

    $found = false;

    for ($i=0; $i<$l; $i++) {

        $res = $stmt->fetchObject();

        if(!empty($res->society)){
            $arr_contact_info = array($res->firstname,$res->lastname,'('.$res->society.')');
        }else{
            $arr_contact_info = array($res->firstname,$res->lastname);
        }
        $contact_info = implode(' ', $arr_contact_info);

        if ($i%2==1) $color = 'LightYellow';
        else $color = 'white';
        echo "<li id='".$res->contact_id."' style='font-size:12px;background-color:$color;' title=''><i class='fa fa-user fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='personne physique'></i> "
                . '<span style="display:table-cell;vertical-align:middle;">' . str_replace($args, $args_bold, $contact_info) . '</span>'
            ."</li>";
    }*/


    ///////////////////////

    if($nb_step1 == 0 && $nb_step2 == 0 && $nb_step3 == 0 && $nb_step4 == 0 && $nb_step5 == 0 && $nb_step6 == 0) echo "<li></li>";
    echo "</ul>";
    if($nb_step1 == 0 && $nb_step2 == 0 && $nb_step3 == 0 && $nb_step4 == 0 && $nb_step5 == 0 && $nb_step6 == 0){
        $noResultContacts = true;
    }

    $nb_total = $nb_step1+$nb_step2+$nb_step3+$nb_step4+$nb_step5+$nb_step6;
    if ($nb_step1 > $m || $nb_step2 > $m || $nb_step3 > $m || $nb_step4 > $m || $nb_step5 > $m || $nb_step6 > $m) echo "<p align='right' style='background-color:LemonChiffon;font-size:9px;font-style:italic;padding-right:5px;padding-bottom:0px;' title=\"La liste n'a pas pu être affichée intégralement, veuillez compléter votre recherche.\" >...".$nb_total." résulats au total</p>";

    if($noResultContacts){
        echo "<p align='left' style='background-color:LemonChiffon;text-align:center;color:grey;font-style:italic;padding-bottom:0px;' title=\"Aucun résultat trouvé, veuillez compléter votre recherche.\" >Aucun résultat trouvé.</p>";
    }
