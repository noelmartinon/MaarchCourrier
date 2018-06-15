<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   autocomplete_contacts
*
* @author  dev <dev@maarch.org>
* @ingroup indexing_searching
*/
require_once 'core/class/class_request.php';

$req = new request();
$db = new Database();

if (empty($_REQUEST['table'])) {
    exit();
}
$table = $_REQUEST['table'];
$_SESSION['is_multi_contact'] = 'OK';
$color = 'LightYellow';
$multi_sessions_address_id = $_SESSION['adresses']['addressid'];
$user_ids = array();
$address_ids = array();

if ($_SESSION['is_multi_contact'] == 'OK') {
    if (count($multi_sessions_address_id) > 0) {
        for ($imulti = 0; $imulti <= count($multi_sessions_address_id); ++$imulti) {
            if (is_numeric($multi_sessions_address_id[$imulti])) {
                array_push($address_ids, $multi_sessions_address_id[$imulti]);
            } else {
                array_push($user_ids, "'".$multi_sessions_address_id[$imulti]."'");
            }
        }

        if (!empty($address_ids)) {
            $addresses = implode(' ,', $address_ids);
            $request_contact = ' and ca_id not in ('.$addresses.')';
        } else {
            $request_contact = '';
        }

        if (!empty($user_ids)) {
            $users = implode(' ,', $user_ids);
            $request_user = ' and user_id not in ('.$users.')';
        } else {
            $request_user = '';
        }
    } else {
        $request_user = '';
        $request_contact = '';
    }

    // Order of select elements (# is for parallel check)
    $columnTarget = array(
        'lastname',
        'contact_lastname',
        'firstname#lastname',
        'lastname#firstname',
        'contact_firstname#contact_lastname',
        'contact_lastname#contact_firstname',
        'society',
        'society_short',
        'address_num#address_street#address_postal_code#address_town',
    );

    // Number of display lines
    $maxResult = 30;

    // Filter of common link word
    $input = str_replace(' et ', ' ', $_REQUEST['Input']);
    $input = str_replace(' de ', ' ', $input);
    $input = str_replace(' la ', ' ', $input);
    $input = str_replace(' sur ', ' ', $input);
    $input = str_replace(' sous ', ' ', $input);
    $keyList = explode(' ', $input);

    $containResult = false;
    $nb_total = 0;
    // First, internal user
    $contactRequest = "lower(translate(firstname || ' ' || lastname,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE lower(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))";

    $query = 'SELECT * FROM users WHERE '.$contactRequest.$request_user;
    $arrayPDO = array('%'.$_REQUEST['Input'].'%');
    $stmt = $db->query($query, $arrayPDO);
    $nb_total = $nb_total + $stmt->rowCount();
    $aAlreadyCatch = [];
    $itRes = 0;
    while ($res = $stmt->fetchObject()) {
        if ($itRes > $maxResult) {
            break;
        }
        $containResult = true;

        $arr_contact_info = array($res->firstname, $res->lastname);
        $contact_icon = "<i class='fa fa-users fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='"._USER."'></i>";

        $contact_info = implode(' ', $arr_contact_info);

        //Highlight
        foreach ($keyList as $keyVal) {
            $contact_info = preg_replace_callback(
                '/'.$keyVal.'/i',
                function ($matches) {
                    return '<b>'.$matches[0].'</b>';
                },
                $contact_info
            );
        }
        $arrContact[] = "<li id='".$res->user_id.", ' style='font-size:12px;background-color:$color;'>".$contact_icon.' '
        .'<span style="display:table-cell;vertical-align:middle;">'.$contact_info.'</span>'
    .'</li>';

        ++$itRes;
    }

    // Second, other criteria define $columnTarget
    foreach ($columnTarget as $column) {
        $contactRequest = '';
        $contactSubRequest = '';

        $columnSql = str_replace('#', "|| ' ' ||", $column);

        $contactRequest = 'lower(translate('.$columnSql.",'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE lower(translate(?,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))";
        $orderStr = ' ORDER by '.$columnSql.' ASC';
        $query = 'SELECT * FROM view_contacts WHERE '.$contactRequest.$request_contact.$orderStr;
        $arrayPDO = array('%'.$_REQUEST['Input'].'%');
        $stmt = $db->query($query, $arrayPDO);
        $nb_total = $nb_total + $stmt->rowCount();
        $aAlreadyCatch = [];
        while ($res = $stmt->fetchObject()) {
            if ($itRes > $maxResult) {
                break;
            }
            $containResult = true;

            if ($res->is_corporate_person == 'N') {
                $contact_icon = "<i class='fa fa-user fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='"._INDIVIDUAL."'></i>";
                if (!empty($res->society)) {
                    $arr_contact_info = array($res->contact_firstname, $res->contact_lastname, '('.$res->society.')');
                } else {
                    $arr_contact_info = array($res->contact_firstname, $res->contact_lastname);
                }
            } else {
                $contact_icon = "<i class='fa fa-building fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='"._IS_CORPORATE_PERSON."'></i>";
                $arr_contact_info = array($res->society);
            }

            $contact_info = implode(' ', $arr_contact_info);
            $address = '';

            if ((!empty($res->address_street) || !empty($res->lastname)) && $res->is_private != 'Y') {
                if ($res->is_corporate_person == 'N') {
                    $arr_address = array($res->address_num, $res->address_street, $res->address_postal_code, $res->address_town);
                } else {
                    $arr_address = array($res->firstname, $res->lastname.',', $res->address_num, $res->address_street, $res->address_postal_code, $res->address_town);
                }
                $address = implode(' ', $arr_address);
            } elseif ($res->is_private == 'Y') {
                $address = _CONFIDENTIAL_ADDRESS;
            } else {
                $address = _NO_ADDRESS_GIVEN;
            }

            //Highlight
            $a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ'
                .'ßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
            $b = 'aaaaaaaceeeeiiiidnoooooouuuuy'
                .'bsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
            $contact_info = utf8_decode($contact_info);
            $contact_info = utf8_encode(strtr($contact_info, utf8_decode($a), $b));
            $address = utf8_decode($address);
            $address = utf8_encode(strtr($address, utf8_decode($a), $b));

            foreach ($keyList as $keyVal) {
                $keyVal = utf8_decode($keyVal);
                $keyVal = utf8_encode(strtr($keyVal, utf8_decode($a), $b));

                $contact_info = preg_replace_callback(
                    '/'.$keyVal.'/i',
                    function ($matches) {
                        return '<b>'.$matches[0].'</b>';
                    },
                    $contact_info
                );
                $address = preg_replace_callback(
                    '/'.$keyVal.'/i',
                    function ($matches) {
                        return '<b>'.$matches[0].'</b>';
                    },
                    $address
                );
            }
            $arrContact[] = "<li id='".$res->contact_id.','.$res->ca_id."' style='font-size:12px;background-color:$color;'>".$contact_icon.' '
            .'<span style="display:table-cell;vertical-align:middle;">'.$contact_info.'</span>'
            .'<div style="font-size:9px;font-style:italic;"> - '.$address.'</div>'
        .'</li>';
            $aAlreadyCatch[$res->contact_id.','.$res->ca_id] = 'added';
            ++$itRes;
        }
    }
    if ($containResult) {
        echo '<ul id="autocomplete_contacts_ul">';
        echo implode(array_unique($arrContact));
        echo '</ul>';
        if ($maxResult < $nb_total) {
            echo "<p align='right' style='background-color:LemonChiffon;font-size:9px;font-style:italic;padding-right:5px;' >...".$nb_total.' '._CONTACTS.' ('.$maxResult.' '._DISPLAYED.')</p>';
        }
    } else {
        echo '<ul id="autocomplete_contacts_ul">';
        echo "<li align='left' style='background-color:LemonChiffon;text-align:center;color:grey;font-style:italic;' title=\""._NO_RESULTS_AUTOCOMPLETE_CONTACT_INFO.'" >'._NO_RESULTS.'</li>';
        echo '</ul>';
    }

    exit();
}

//$_SESSION['is_multi_contact'] = '';
