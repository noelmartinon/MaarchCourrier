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

$color                        = 'LightYellow';
$multi_sessions_address_id    = $_SESSION['adresses']['addressid'];
$user_ids                     = array();
$address_ids                  = array();
$arrContact                   = array();

if (is_array($multi_sessions_address_id) && count($multi_sessions_address_id) > 0) {
    for ($imulti = 0; $imulti <= count($multi_sessions_address_id); ++$imulti) {
        if (is_numeric($multi_sessions_address_id[$imulti])) {
            array_push($address_ids, $multi_sessions_address_id[$imulti]);
        } else {
            array_push($user_ids, "'".$multi_sessions_address_id[$imulti]."'");
        }
    }

    if (!empty($address_ids)) {
        $addresses = implode(' ,', $address_ids);
        $request_contact = 'ca_id not in ('.$addresses.')';
    } else {
        $request_contact = '';
    }

    if (!empty($user_ids)) {
        $users = implode(' ,', $user_ids);
        $request_user = 'user_id not in ('.$users.')';
    } else {
        $request_user = '';
    }
} else {
    $request_user = '';
    $request_contact = '';
}

/********************* V2 CONTACTS *****************************/
  
$searchItems = explode(' ', $_REQUEST['Input']);

$fields = ['contact_firstname', 'contact_lastname', 'firstname', 'lastname', 'society', 'society_short', 'address_num', 'address_street', 'address_town', 'address_postal_code'];
foreach ($fields as $key => $field) {
    $fields[$key] = "translate({$field}, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ', 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')";
    $fields[$key] .= "ilike translate(?, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ', 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')";
}
$fields = implode(' OR ', $fields);
$fields = "($fields)";

$where = [];
$requestData = [];
foreach ($searchItems as $keyItem => $item) {
    if (strlen($item) >= 2) {
        $where[] = $fields;
        $isIncluded = false;
        foreach ($searchItems as $key => $value) {
            if ($keyItem == $key) {
                continue;
            }
            if (strpos($value, $item) === 0) {
                $isIncluded = true;
            }
        }
        for ($i = 0; $i < 10; $i++) {
            $requestData[] = ($isIncluded ? "%{$item}" : "%{$item}%");
        }
    }
}

if (!empty($request_contact)) {
    $where = array_merge($where, [$request_contact]);
}

$where[] = '(enabled = \'Y\')';

$contacts = \Contact\models\ContactModel::getOnView([
    'select'    => ['*'],
    'where'     => $where,
    'data'      => $requestData,
    'orderBy'   => ["is_corporate_person DESC", "case is_corporate_person when 'Y' then (society, lastname) else (contact_lastname, society) end"],
    'limit'     => 10
]);

foreach ($contacts as $contact) {
    $containResult = true;

    if ($contact['is_corporate_person'] == 'N') {
        $contact_icon = "<i class='fa fa-user fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='"._INDIVIDUAL."'></i>";
        if (!empty($contact['society'])) {
            $arr_contact_info = array($contact['contact_firstname'], $contact['contact_lastname'], '('.$contact['society'].')');
        } else {
            $arr_contact_info = array($contact['contact_firstname'], $contact['contact_lastname']);
        }
    } else {
        $contact_icon = "<i class='fa fa-building fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='"._IS_CORPORATE_PERSON."'></i>";
        $arr_contact_info = array($contact['society']);
    }

    $contact_info = implode(' ', $arr_contact_info);
    $address = '';

    if ((!empty($contact['address_street']) || !empty($contact['lastname'])) && $contact['is_private'] != 'Y') {
        if ($contact['is_corporate_person'] == 'N') {
            $arr_address = array($contact['address_num'], $contact['address_street'], $contact['address_postal_code'], $contact['address_town']);
        } else {
            $arr_address = array($contact['firstname'], $contact['lastname'].',', $contact['address_num'], $contact['address_street'], $contact['address_postal_code'], $contact['address_town']);
        }
        $address = implode(' ', $arr_address);
    } elseif ($contact['is_private'] == 'Y') {
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
    $address      = utf8_decode($address);
    $address      = utf8_encode(strtr($address, utf8_decode($a), $b));

    foreach ($searchItems as $keyVal) {
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
    $color = 'LightYellow';

    $rate = \Contact\controllers\ContactController::getFillingRate(['contact' => $contact]);
    if (!empty($rate)) {
        $color = $rate['color'];
    }

    $arrContactTmp = "<li id='" . $contact['contact_id'] . ',' . $contact['ca_id'] . "' style='font-size:12px;background-color:$color;'>" . $contact_icon . ' '
        . '<span style="display:table-cell;vertical-align:middle;">' . $contact_info . '</span>'
        . '<div style="font-size:9px;font-style:italic;"> - ' . $address . '</div>'
        . '</li>';

    $arrContact[] = $arrContactTmp;

    $aAlreadyCatch[$contact['contact_id'] . ',' . $contact['ca_id']] = 'added';
}

// -----------------------------------------------------------------------------//

$requestData = \SrcCore\controllers\AutoCompleteController::getDataForRequest([
    'search'        => $_REQUEST['Input'],
    'fields'        => '(firstname ilike ? OR lastname ilike ?)',
    'where'         => ['enabled = ?', 'status != ?', 'user_id not in (?)'],
    'data'          => ['Y', 'DEL', ['superadmin']],
    'fieldsNumber'  => 2,
]);

if (!empty($request_user)) {
    $requestData['where'] = array_merge($requestData['where'], [$request_user]);
}

$users = \User\models\UserModel::get([
    'select'    => ['id', 'user_id', 'firstname', 'lastname'],
    'where'     => $requestData['where'],
    'data'      => $requestData['data'],
    'orderBy'   => ['lastname'],
    'limit'     => 10
]);

foreach ($users as $user) {
    $containResult = true;

    $arr_contact_info = array($user['firstname'], $user['lastname']);
    $contact_icon = "<i class='fa fa-user fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='"._USER."'></i>";

    $contact_info = implode(' ', $arr_contact_info);

    //Highlight
    foreach ($searchItems as $keyVal) {
        $contact_info = preg_replace_callback(
            '/'.$keyVal.'/i',
            function ($matches) {
                return '<b>'.$matches[0].'</b>';
            },
            $contact_info
        );
    }

    $color = 'LightYellow';
    $arrContact[] = "<li id='".$user['user_id'].", ' style='font-size:12px;background-color:$color;'>".$contact_icon.' '
                        .'<span style="display:table-cell;vertical-align:middle;">'.$contact_info.'</span>'
                    .'</li>';
}


/*********************  END V2 CONTACTS **************************/

// ----------------------------------------------------------

//Third, contacts groups
if ($_REQUEST['multiContact'] == "true") {
    $contactRequest = "lower(translate(label,'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr')) LIKE lower(translate('%".$_REQUEST['Input']."%','ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ','aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr'))";
    $id = \User\models\UserModel::getByLogin(['login' => $_SESSION['user']['UserId'], 'select' => ['id']]);
    $contactsGroups = \Contact\models\ContactGroupModel::get([
        'where' => [$contactRequest, '(public IS TRUE OR ? = owner)'],
        'orderBy' => ['label ASC'],
        'data' => [$id['id']]
    ]);
    $nb_total = $nb_total + count($contactsGroups);
    foreach ($contactsGroups as $contactGroup) {
        $containResult = true;

        $contactIcon = "<i class='fa fa-users fa-1x' style='padding:5px;display:table-cell;vertical-align:middle;' title='" . _CONTACTS_GROUP . "'></i>";

        //Highlight
        foreach ($searchItems as $keyVal) {
            $contactGroup['label'] = preg_replace_callback(
                '/' . $keyVal . '/i',
                function ($matches) {
                    return '<b>' . $matches[0] . '</b>';
                },
                $contactGroup['label']
            );
        }
        $arrContact[] = "<li id='" . $contactGroup['id'] . ", ' style='font-size:12px;background-color:$color;'>" . $contactIcon . ' '
            . '<span style="display:table-cell;vertical-align:middle;">' . $contactGroup['label'] . '</span>'
            . '</li>';

        ++$itRes;
    }
}

if ($containResult) {
    echo '<ul id="autocomplete_contacts_ul">';
    echo implode(array_unique($arrContact));
    echo '</ul>';
} else {
    echo '<ul id="autocomplete_contacts_ul">';
    echo "<li align='left' style='background-color:LemonChiffon;text-align:center;color:grey;font-style:italic;' title=\""._NO_RESULTS_AUTOCOMPLETE_CONTACT_INFO.'" >'._NO_RESULTS.'</li>';
    echo '</ul>';
}

exit();
