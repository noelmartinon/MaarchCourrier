<?php
/*
*    Copyright 2008-2014 Maarch
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
* @brief  export contacts
*
*
* @file
* @author <dev@maarch.org>
* @date $date$
* @version $Revision$
* @ingroup admin
*/

require_once("core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_request.php");
require_once("apps".DIRECTORY_SEPARATOR.$_SESSION['config']['app_id'].DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_contacts_v2.php");
require_once 'apps' . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR  . 'class' . DIRECTORY_SEPARATOR . 'class_business_app_tools.php';
$business = new business_app_tools();
$contact = new contacts_v2();
$core_tools = new core_tools();
$core_tools->load_lang();

$request= new request;
$tab_export = $request->PDOselect(
    $_SESSION['export_admin_list']['select'],
    $_SESSION['export_admin_list']['where'],
    $_SESSION['export_admin_list']['aPDO'],
    $_SESSION['export_admin_list']['order'],
    $_SESSION['config']['databasetype'],
    21790
);
unset($_SESSION['export_admin_list']);

$fp = fopen($_SESSION['config']['tmppath'].'contact_list'.$_SESSION['user']['UserId'].'.csv', 'w');

$list_row = array();
$list_address = array();
$list_header = array();
$db = new Database();

$header_done = false;

$nb_colum = count($tab_export[0]);

for ($i_export=0;$i_export<count($tab_export);$i_export++) {
    for ($j_export=0;$j_export<$nb_colum;$j_export++) {
        if ($tab_export[$i_export][$j_export]['column'] <> _ID) {
            if ($i_export==0) {
                array_push($list_header, mb_strtoupper($tab_export[$i_export][$j_export]['column'], 'UTF-8'));
            }
            if ($tab_export[$i_export][$j_export]['column'] == _CONTACT_TYPE) {
                array_push($list_row, $contact->get_label_contact(($tab_export[$i_export][$j_export]['value']), $_SESSION['tablename']['contact_types']));
            } elseif ($tab_export[$i_export][$j_export]['column'] == _IS_CORPORATE_PERSON) {
                if ($tab_export[$i_export][$j_export]['value'] == 'Y') {
                    array_push($list_row, _YES);
                } else {
                    array_push($list_row, _NO);
                }
            } else {
                array_push($list_row, html_entity_decode($tab_export[$i_export][$j_export]['value'], ENT_QUOTES));
            }
        }
    }

    $stmt = $db->query("SELECT * FROM contact_addresses WHERE contact_id = ? ", array($tab_export[$i_export][0]['value']));

    if ($stmt->rowCount()>0) {
        while ($address = $stmt->fetchObject()) {
            if ($i_export==0) {
                array_push($list_header, mb_strtoupper(html_entity_decode(_CONTACT_PURPOSE), 'UTF-8'));
                array_push($list_header, mb_strtoupper(html_entity_decode(_SERVICE), 'UTF-8'));
                array_push($list_header, mb_strtoupper(html_entity_decode(_FIRSTNAME), 'UTF-8'));
                array_push($list_header, mb_strtoupper(_LASTNAME, 'UTF-8'));
                array_push($list_header, mb_strtoupper(html_entity_decode(_TITLE2), 'UTF-8'));
                array_push($list_header, mb_strtoupper(_FUNCTION, 'UTF-8'));
                array_push($list_header, mb_strtoupper(_OCCUPANCY, 'UTF-8'));
                array_push($list_header, mb_strtoupper(html_entity_decode(_NUM), 'UTF-8'));
                array_push($list_header, mb_strtoupper(_STREET, 'UTF-8'));
                array_push($list_header, mb_strtoupper(html_entity_decode(_COMPLEMENT), 'UTF-8'));
                array_push($list_header, mb_strtoupper(_TOWN, 'UTF-8'));
                array_push($list_header, mb_strtoupper(_COUNTRY, 'UTF-8'));
                array_push($list_header, mb_strtoupper(html_entity_decode(_PHONE), 'UTF-8'));
                array_push($list_header, mb_strtoupper(_MAIL, 'UTF-8'));
            }

            $list_address = $list_row;
            array_push($list_address, $contact->get_label_contact($address->contact_purpose_id, $_SESSION['tablename']['contact_purposes']));
            array_push($list_address, $address->departement);
            array_push($list_address, $address->firstname);
            array_push($list_address, $address->lastname);
            array_push($list_address, $business->get_label_title($address->title));
            array_push($list_address, $address->function);
            array_push($list_address, $address->occupancy);
            array_push($list_address, $address->address_num);
            array_push($list_address, $address->address_street);
            array_push($list_address, $address->address_complement);
            array_push($list_address, $address->address_town);
            array_push($list_address, $address->address_country);
            array_push($list_address, $address->phone);
            array_push($list_address, $address->email);

            if ($i_export==0 && $header_done == false) {
                fputcsv($fp, $list_header, ';', '"');
                $header_done = true;
            }
            fputcsv($fp, $list_address, ';', '"');
            $list_address = array();
        }
    } else {
        if ($i_export==0) {
            fputcsv($fp, $list_header, ';', '"');
        }
        fputcsv($fp, $list_row, ';', '"');
    }

    $list_row = array();
}

fclose($fp);
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: inline; filename=contact_list'.$_SESSION['user']['UserId'].'.csv;');
readfile($_SESSION['config']['tmppath'].'contact_list'.$_SESSION['user']['UserId'].'.csv');
exit;
