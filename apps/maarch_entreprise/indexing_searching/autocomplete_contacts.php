<?php
/*
*   Copyright 2008, 2015 Maarch
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
* @brief Script used by an Ajax autocompleter object to get the contacts data (from users or contacts)
*
* @file autocomplete_contacts.php
* @author Claire Figueras <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup indexing_searching_mlb
*/
require_once('core/class/class_request.php');

$req = new request();
$db = new Database();

if (empty($_REQUEST['table'])) {
    exit();
}
$table = $_REQUEST['table'];
$_SESSION['is_multi_contact'] = 'OK';
$multi_sessions_address_id = $_SESSION['adresses']['addressid'];
$user_ids = array();
// $user_ids = '';
$address_ids = array();
// $address_ids = '';

if(count($multi_sessions_address_id) > 0){
    for ($imulti=0; $imulti <= count($multi_sessions_address_id); $imulti++) { 
        if (is_numeric($multi_sessions_address_id[$imulti])) {
            array_push($address_ids, $multi_sessions_address_id[$imulti]);
        } else {
            array_push($user_ids, "'".$multi_sessions_address_id[$imulti]."'");
        }
    }

    if (!empty($address_ids)) {
        $addresses = implode(' ,', $address_ids);
        $request_contact = " and ca_id not in (".$addresses.")";
    } else {
        $request_contact = ''; 
    }

    if (!empty($user_ids)) {
        $users = implode(' ,', $user_ids);
        $request_user = " and user_id not in (".$users.")";
    } else {
        $request_user = ''; 
    }
} else{
    $request_user = '';
    $request_contact = ''; 
}

if ($_SESSION['is_multi_contact'] == 'OK') {
    $noResultUsers = false;
    $noResultContacts = false;

    //USERS
    $select = array();
    $select[$_SESSION['tablename']['users']]= array('lastname', 'firstname', 'user_id');
    $where = " (lower(lastname) like lower(:input) "
        ."or lower(firstname) like lower(:input) "
        ."or user_id like :input) and (status = 'OK' or status = 'ABS') and enabled = 'Y'".$request_user;
    $other = 'order by lastname, firstname';
    $arrayPDO = array(":input" => "%".$_REQUEST['Input']."%");
    $res = $req->PDOselect($select, $where, $arrayPDO, $other, $_SESSION['config']['databasetype'], 11,false,"","","", false);

    echo "<ul id=\"autocomplete_contacts_ul\" title=\"utilisateur de l'application\">";
    for ($i=0; $i< min(count($res), 5)  ;$i++) {
        echo "<li id='".$res[$i][2]['value'].", '><i class='fa fa-users fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;'></i> <span style=\"display:table-cell;vertical-align:middle;\">".$req->show_string($res[$i][0]['value'])." ".$req->show_string($res[$i][1]['value'])."</span></li>";
    }
    if($i == 0){
        $noResultUsers = true;
    }

    //CONTACTS
   $arrayPDO = array();
   if ((isset($_REQUEST['contact_type']) && $_REQUEST['contact_type'] <> '') && $_SESSION['is_multi_contact'] =! 'OK') {
       $contactTypeRequest = " AND contact_type = ?";
       $arrayPDOtype = array($_REQUEST['contact_type']);
   }
   
    $Input = $_REQUEST['Input'];
    $boldInput = strtoupper($Input);
    $ucwordsInput = ucwords($Input);
    $Input = $Input . ' ' . $boldInput . ' ' . $ucwordsInput;
    
    $args = explode(' ', $Input);
    $args[] = $Input;
    $args_bold = array();
    foreach ($args as $key => $value) {
        $args_bold[$key] = '<b>'. $value . '</b>';
    }
    $num_args = count($args);
    if ($num_args == 0) return "<ul></ul>"; 
       
    $aAlreadyCatch = [];
    //STEP 1 : search with lastname (physical contact)
    $query = "SELECT contact_id,ca_id,contact_firstname, contact_lastname, society,address_num,address_street,is_private,CASE WHEN LOWER(translate(contact_lastname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) like LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) THEN contact_lastname END as trust_result FROM view_contacts WHERE is_corporate_person = 'N' AND contact_enabled = 'Y' AND enabled = 'Y' AND (LOWER(translate(contact_lastname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))) ORDER BY contact_lastname,contact_firstname ASC";
    $arrayPDO = array('%'.$_REQUEST['Input'].'%','%'.$_REQUEST['Input'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step1 = $stmt->rowCount();
    
    $m = 30;
    if ($nb_step1 >= $m) $l = $m;
    else $l = $nb_step1;

    for ($i=0; $i<$l; $i++) {

        $res = $stmt->fetchObject();

        if(!isset($aAlreadyCatch[$res->contact_id.",".$res->ca_id])){
            if($res->trust_result){
                $count_trust = strlen($res->trust_result);
                $count_input = strlen($_REQUEST['Input']);

                $confidence_index = round(($count_input*100)/$count_trust);
            }else{
                $confidence_index = '??';
            }

            if(!empty($res->society)){
                $arr_contact_info = array($res->contact_firstname,$res->contact_lastname,'('.$res->society.')');
            }else{
                $arr_contact_info = array($res->contact_firstname,$res->contact_lastname);
            }
            $contact_info = implode(' ', $arr_contact_info);

            $address = '';

            if(!empty($res->address_street) && $res->is_private != 'Y'){
                $arr_address = array($res->address_num,$res->address_street,$res->address_postal_code,$res->address_town);
                $address = implode(' ', $arr_address);
            }else if($res->is_private == 'Y'){
                $address = 'adresse confidentielle';
            }else{
                $address = 'aucune information sur l\'adresse';
            }

            if ($i%2==1) $color = 'LightYellow';
            else $color = 'white';

            echo "<li id='".$res->contact_id.",".$res->ca_id."' style='font-size:12px;background-color:$color;' title='confiance : ".$confidence_index."%'><i class='fa fa-user fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='personne physique'></i> "
                    . '<span style="display:table-cell;vertical-align:middle;">' . str_replace($args, $args_bold, $contact_info) . '</span>'
                    . '<div style="font-size:9px;font-style:italic;"> - ' .str_replace($args, $args_bold, $address).'</div>'
                ."</li>";
            $aAlreadyCatch[$res->contact_id.",".$res->ca_id] = 'added';
        }
    }

    //STEP 2 : search with lastname(corporate contact)
    $query = "SELECT contact_id,ca_id,firstname, lastname, society, address_num,address_street,address_postal_code,address_town,is_private,CASE WHEN LOWER(translate(lastname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) like LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) THEN lastname END as trust_result FROM view_contacts WHERE is_corporate_person = 'Y' AND contact_enabled = 'Y' AND enabled = 'Y' AND (LOWER(translate(lastname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))) ORDER BY lastname,firstname ASC";
    $arrayPDO = array('%'.$_REQUEST['Input'].'%','%'.$_REQUEST['Input'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step2 = $stmt->rowCount();
    
    $m = 30;
    if ($nb_step2 >= $m) $l = $m;
    else $l = $nb_step2;

    for ($i=0; $i<$l; $i++) {
     
        $res = $stmt->fetchObject();

        if(!isset($aAlreadyCatch[$res->contact_id.",".$res->ca_id])){
            $count_trust = strlen($res->trust_result);
            $count_input = strlen($_REQUEST['Input']);
            $confidence_index = round(($count_input*100)/$count_trust);

            $address = '';
            $arr_address = array($res->address_num,$res->address_street,$res->address_postal_code,$res->address_town);
            $tmp_address = implode(' ', $arr_address);

            if(!empty($res->firstname) || !empty($res->lastname) ){
                $arr_address = array($res->firstname.' '.$res->lastname,$tmp_address);
                $address = implode(', ', $arr_address);
            }else{
                $address = $tmp_address;
            }

            if ($i%2==1) $color = 'LightYellow';
            else $color = 'white';

            echo "<li id='".$res->contact_id.",".$res->ca_id."' style='font-size:12px;background-color:$color;' title='confiance : ".$confidence_index."%'><i class='fa fa-building fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='structure'></i> "
                    . '<span style="display:table-cell;vertical-align:middle;">'. str_replace($args, $args_bold, $res->society) .'</span>'
                    . '<div style="font-size:9px;font-style:italic;"> - ' .str_replace($args, $args_bold, $address).'</div>'
                ."</li>";
            $aAlreadyCatch[$res->contact_id.",".$res->ca_id] = 'added';
        }
    }

    //STEP 3 : search with society(physical contact)
    $query = "SELECT contact_id,ca_id,contact_firstname, contact_lastname, society, address_num,address_street,is_private, CASE WHEN LOWER(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) like LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) THEN society END as trust_result FROM view_contacts WHERE is_corporate_person = 'N' AND contact_enabled = 'Y' AND enabled = 'Y' AND (LOWER(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) OR LOWER(translate(society_short,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))) ORDER BY society,contact_lastname,contact_firstname ASC";
    $arrayPDO = array('%'.$_REQUEST['Input'].'%','%'.$_REQUEST['Input'].'%','%'.$_REQUEST['Input'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step3 = $stmt->rowCount();
    
    $m = 30;
    if ($nb_step3 >= $m) $l = $m;
    else $l = $nb_step3;

    for ($i=0; $i<$l; $i++) {

        $res = $stmt->fetchObject();

        if(!isset($aAlreadyCatch[$res->contact_id.",".$res->ca_id])){
            if($res->trust_result){
                $count_trust = strlen($res->trust_result);
                $count_input = strlen($_REQUEST['Input']);

                $confidence_index = round(($count_input*100)/$count_trust);
            }else{
                $confidence_index = '??';
            }

            if(!empty($res->society)){
                $arr_contact_info = array($res->contact_firstname,$res->contact_lastname,'('.$res->society.')');
            }else{
                $arr_contact_info = array($res->contact_firstname,$res->contact_lastname);
            }
            $contact_info = implode(' ', $arr_contact_info);

            $address = '';

            if(!empty($res->address_street) && $res->is_private != 'Y'){
                $arr_address = array($res->address_num,$res->address_street,$res->address_postal_code,$res->address_town);
                $address = implode(' ', $arr_address);
            }else if($res->is_private == 'Y'){
                $address = 'adresse confidentielle';
            }else{
                $address = 'aucune information sur l\'adresse';
            }

            if ($i%2==1) $color = 'LightYellow';
            else $color = 'white';

            echo "<li id='".$res->contact_id.",".$res->ca_id."' style='font-size:12px;background-color:$color;' title='confiance : ".$confidence_index."%'><i class='fa fa-user fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='personne physique'></i> "
                    . '<span style="display:table-cell;vertical-align:middle;">' . str_replace($args, $args_bold, $contact_info) . '</span>'
                    . '<div style="font-size:9px;font-style:italic;"> - ' .str_replace($args, $args_bold, $address).'</div>'
                ."</li>";
            $aAlreadyCatch[$res->contact_id.",".$res->ca_id] = 'added';
        }
    }
    ///////////////////////

    //STEP 4 : search with society(corporate contact)
    $query = "SELECT contact_id,ca_id,firstname, lastname, society, address_num,address_street,address_postal_code,address_town,is_private, CASE WHEN LOWER(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) like LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) THEN society END as trust_result FROM view_contacts WHERE is_corporate_person = 'Y' AND contact_enabled = 'Y' AND enabled = 'Y' AND (LOWER(translate(society,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) OR LOWER(translate(society_short,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))) ORDER BY society,lastname,firstname ASC";
    $arrayPDO = array('%'.$_REQUEST['Input'].'%','%'.$_REQUEST['Input'].'%','%'.$_REQUEST['Input'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step4 = $stmt->rowCount();
    
    $m = 30;
    if ($nb_step4 >= $m) $l = $m;
    else $l = $nb_step4;

    for ($i=0; $i<$l; $i++) {
     
        $res = $stmt->fetchObject();

        if(!isset($aAlreadyCatch[$res->contact_id.",".$res->ca_id])){
            if($res->trust_result){
                $count_trust = strlen($res->trust_result);
                $count_input = strlen($_REQUEST['Input']);

                $confidence_index = round(($count_input*100)/$count_trust);
            }else{
                $confidence_index = '??';
            }
            

            $address = '';
            $arr_address = array($res->address_num,$res->address_street,$res->address_postal_code,$res->address_town);
            $tmp_address = implode(' ', $arr_address);

            if(!empty($res->firstname) || !empty($res->lastname) ){
                $arr_address = array($res->firstname.' '.$res->lastname,$tmp_address);
                $address = implode(', ', $arr_address);
            }else{
                $address = $tmp_address;
            }

            if ($i%2==1) $color = 'LightYellow';
            else $color = 'white';

            echo "<li id='".$res->contact_id.",".$res->ca_id."' style='font-size:12px;background-color:$color;' title='confiance : ".$confidence_index."%'><i class='fa fa-building fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='structure'></i> "
                    . '<span style="display:table-cell;vertical-align:middle;">'. str_replace($args, $args_bold, $res->society) .'</span>'
                    . '<div style="font-size:9px;font-style:italic;"> - ' .str_replace($args, $args_bold, $address).'</div>'
                ."</li>";
            $aAlreadyCatch[$res->contact_id.",".$res->ca_id] = 'added';
        }
    }
    ///////////////////////

    //STEP 5 : search with other informations (physical contact)
    $query = "SELECT contact_id,ca_id,contact_firstname, contact_lastname, society, address_num,address_street,address_postal_code,address_town,is_private FROM view_contacts WHERE is_corporate_person = 'N' AND contact_enabled = 'Y' AND enabled = 'Y'"
                ." AND ((LOWER(translate(contact_firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))) OR (LOWER(translate(departement,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))))";
    $arrayPDO = array('%'.$_REQUEST['Input'].'%','%'.$_REQUEST['Input'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step5 = $stmt->rowCount();
    
    $m = 30;
    if ($nb_step5 >= $m) $l = $m;
    else $l = $nb_step5;

    for ($i=0; $i<$l; $i++) {

        $res = $stmt->fetchObject();

        if(!isset($aAlreadyCatch[$res->contact_id.",".$res->ca_id])){
            if(!empty($res->society)){
                $arr_contact_info = array($res->contact_firstname,$res->contact_lastname,'('.$res->society.')');
            }else{
                $arr_contact_info = array($res->contact_firstname,$res->contact_lastname);
            }
            $contact_info = implode(' ', $arr_contact_info);

            $address = '';

            if(!empty($res->address_street) && $res->is_private != 'Y'){
                $arr_address = array($res->address_num,$res->address_street,$res->address_postal_code,$res->address_town);
                $address = implode(' ', $arr_address);
            }else if($res->is_private == 'Y'){
                $address = 'adresse confidentielle';
            }else{
                $address = 'aucune information sur l\'adresse';
            }

            if ($i%2==1) $color = 'LightYellow';
            else $color = 'white';

            echo "<li id='".$res->contact_id.",".$res->ca_id."' style='font-size:12px;background-color:$color;' title=''><i class='fa fa-user fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='personne physique'></i> "
                    . '<span style="display:table-cell;vertical-align:middle;">' . str_replace($args, $args_bold, $contact_info) . '</span>'
                    . '<div style="font-size:9px;font-style:italic;"> - ' .str_replace($args, $args_bold, $address).'</div>'
                ."</li>";
            $aAlreadyCatch[$res->contact_id.",".$res->ca_id] = 'added';
        }
    }


    ///////////////////////

    //STEP 6 : search with other informations (corporate contact)
    $query = "SELECT contact_id,ca_id,firstname, lastname, society, address_num,address_street,address_postal_code,address_town,is_private FROM view_contacts WHERE is_corporate_person = 'Y' AND contact_enabled = 'Y' AND enabled = 'Y'"
                . " AND ((LOWER(translate(firstname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))) OR (LOWER(translate(departement,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE LOWER(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))))";
    $arrayPDO = array('%'.$_REQUEST['Input'].'%','%'.$_REQUEST['Input'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_step6 = $stmt->rowCount();
    
    $m = 30;
    if ($nb_step6 >= $m) $l = $m;
    else $l = $nb_step6;

    for ($i=0; $i<$l; $i++) {
     
        $res = $stmt->fetchObject();

        if(!isset($aAlreadyCatch[$res->contact_id.",".$res->ca_id])){
            $address = '';
            $arr_address = array($res->address_num,$res->address_street,$res->address_postal_code,$res->address_town);
            $tmp_address = implode(' ', $arr_address);

            if(!empty($res->firstname) || !empty($res->lastname) ){
                $arr_address = array($res->firstname.' '.$res->lastname,$tmp_address);
                $address = implode(', ', $arr_address);
            }else{
                $address = $tmp_address;
            }

            if ($i%2==1) $color = 'LightYellow';
            else $color = 'white';

            echo "<li id='".$res->contact_id.",".$res->ca_id."' style='font-size:12px;background-color:$color;' title=''><i class='fa fa-building fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='structure'></i> "
                    . '<span style="display:table-cell;vertical-align:middle;">'. str_replace($args, $args_bold, $res->society) .'</span>'
                    . '<div style="font-size:9px;font-style:italic;"> - ' .str_replace($args, $args_bold, $address).'</div>'
                ."</li>";
            $aAlreadyCatch[$res->contact_id.",".$res->ca_id] = 'added';
        }
    }
    if($nb_step1 == 0 && $nb_step2 == 0 && $nb_step3 == 0 && $nb_step4 == 0 && $nb_step5 == 0 && $nb_step6 == 0) echo "<li></li>";
    echo "</ul>";
    if($nb_step1 == 0 && $nb_step2 == 0 && $nb_step3 == 0 && $nb_step4 == 0 && $nb_step5 == 0 && $nb_step6 == 0){
        $noResultContacts = true;
    }

    $nb_total = $nb_step1+$nb_step2+$nb_step3+$nb_step4+$nb_step5+$nb_step6;
    if ($nb_step1 > $m || $nb_step2 > $m || $nb_step3 > $m || $nb_step4 > $m || $nb_step5 > $m || $nb_step6 > $m) echo "<p align='right' style='background-color:LemonChiffon;font-size:9px;font-style:italic;padding-right:5px;' title=\"La liste n'a pas pu être affichée intégralement, veuillez compléter votre recherche.\" >...".$nb_total." résulats au total</p>";

    if($noResultUsers && $noResultContacts){
        echo "<p align='left' style='background-color:LemonChiffon;text-align:center;color:grey;font-style:italic;' title=\"Aucun résultat trouvé, veuillez compléter votre recherche.\" >Aucun résultat trouvé.</p>";
    }

}

//$_SESSION['is_multi_contact'] = '';
