<?php
/**
 *​ ​Copyright​ ​Maarch​ since ​2008​ under licence ​GPLv3.
 *​ ​See​ LICENCE​.​txt file at the root folder ​for​ more details.
 *​ ​This​ file ​is​ part of ​Maarch​ software.
 *
 */

$core = new core_tools();
$core->test_user();
$db = new Database();

// Retrieve the parent res_id (the document which receive the attachment) and the res_id of the attachment we will inject
$parentResId = $_SESSION['stockCheckbox'];
$childResId = $_SESSION['doc_id'];

// Retrieve the data of the form (title, chrono number, recipient etc...)
$formValues = get_values_in_array($_REQUEST['form_values']);
$tabFormValues = array();

// NCH01 new modifs
$allowValues = array('title', 'chrono_number', 'contactid','addressid','close_incoming_mail', 'attachment_type', 'back_date');
foreach($formValues as $tmpTab){
    if(in_array($tmpTab['ID'], $allowValues)){
        if($tmpTab['ID'] == 'chrono_number') // Check if the identifier is empty. if true, set it at NULL
            if(empty($tmpTab['VALUE'])) $tmpTab ['VALUE'] = NULL;
        if(trim($tmpTab['VALUE']) != '') // Case of some empty value, that cause some errors
            $tabFormValues[$tmpTab['ID']] = $tmpTab['VALUE'];
    }
}
// END NCH01 new modifs

$_SESSION['modules_loaded']['attachments']['reconciliation']['tabFormValues'] = $tabFormValues;    // declare SESSION var, used in remove_letterbox

// Remove chrono number depends on attachment type ("with chrono" param) // new modifs
if($_SESSION['attachment_types_with_chrono'][$tabFormValues['attachment_type']] == 'false'){
    $tabFormValues['chrono_number'] = NULL;
}

// Retrieve the informations of the newly scanned document (the one to attach as an attachment)
$queryChildInfos = \Resource\models\ResModel::getById(['resId' => $childResId]);

$aArgs['data'] = array();
foreach ($queryChildInfos as $key => $value) {
    if ($value != ''
        && $key != 'modification_date'
        && $key != 'tablename'
        && $key != 'locker_user_id'
        && $key != 'locker_time'
        && $key != 'confidentiality'
        && substr($key, 0, 7) != 'custom_') {
        if (is_numeric($value)) {
            array_push(
                $aArgs['data'],
                array(
                    'column' => $key,
                    'value' => $value,
                    'type' => 'integer',
                )
            );
        } else {
            array_push(
                $aArgs['data'],
                array(
                    'column' => $key,
                    'value' => $value,
                    'type' => 'string',
                )
            );
        }
    }
}

// The column 'relation' need to be set at 1. Otherwise, the suppression of the attachment isn't possible
array_push(
    $aArgs['data'],
    array(
        'column' => 'relation',
        'value' => 1,
        'type' => 'integer',
    )
);

// The status need to be TRA
array_push(
    $aArgs['data'],
    array(
        'column' => 'status',
        'value' => 'TRA',
        'type' => 'string',
    )
);

// Attachment type
array_push(
    $aArgs['data'],
    array(
        'column' => 'attachment_type',
        'value' => $tabFormValues['attachment_type'], // NEW MODIFS
        'type' => 'string',
    )
);

// The title is retrieve from the validate page
array_push(
    $aArgs['data'],
    array(
        'column' => 'title',
        'value' => $tabFormValues['title'],
        'type' => 'string',
    )
);

// Same for chrono number
if (isset($tabFormValues['chrono_number'])) {
    array_push(
        $aArgs['data'],
        array(
            'column' => 'identifier',
            'value' => $tabFormValues['chrono_number'],
            'type' => 'string',
        )
    );
}

// Same for recipient informations
if (isset($tabFormValues['addressid'])) {
    array_push(
        $aArgs['data'],
        array(
            'column' => 'dest_address_id',
            'value' => $tabFormValues['addressid'],
            'type' => 'integer',
        )
    );
}
if (is_numeric($tabFormValues['contactid'])) { // usefull to avoid user contact id (e.g : bblier instead of 1)
    array_push(
        $aArgs['data'],
        array(
            'column' => 'dest_contact_id',
            'value' => $tabFormValues['contactid'],
            'type' => 'integer',
        )
    );
}
//collId's
$aArgs['collId'] = 'letterbox_coll';
$aArgs['collIdMaster'] = 'letterbox_coll';

//table
$aArgs['table'] = 'res_attachments';

//fileFormat
for ($i = 0; $i <= count($aArgs['data']); $i++) {
    if ($aArgs['data'][$i]['column'] == 'format') {
        if ($aArgs['data'][$i]['value'] != null) {
            $aArgs['fileFormat'] = $aArgs['data'][$i]['value'];
        }
    }
    if ($aArgs['data'][$i]['column'] == 'creation_date') {
        $aArgs['data'][$i]['value'] = $db->current_datetime();
    }
    if ($aArgs['data'][$i]['column'] == 'path') {
        if ($aArgs['data'][$i]['value'] != null) {
            $aArgs['path'] = $aArgs['data'][$i]['value'];
        }
    }
    if ($aArgs['data'][$i]['column'] == 'filename') {
        if ($aArgs['data'][$i]['value'] != null) {
            $aArgs['filename'] = $aArgs['data'][$i]['value'];
        }
    }

    if ($aArgs['data'][$i]['column'] == 'docserver_id') {
        // Retrieve the PATH TEMPLATE
        $docserverPath = \Docserver\models\DocserverModel::getByDocserverId([
            'select'        => ['path_template'],
            'docserverId'   => $aArgs['data'][$i]['value']
        ]);

        $aArgs['docserverPath'] = $docserverPath['path_template'];
        $aArgs['docserverId'] = $aArgs['data'][$i]['value'];
    }
}

$file = file_get_contents($aArgs['docserverPath'] . str_replace('#', '/', $aArgs['path']) . $aArgs['filename']);
$aArgs['encodedFile'] = base64_encode($file);
$aArgs['status']      = 'TRA';

// Add offset to empty (loadIntoDb function needed it)

array_push(
    $aArgs['data'],
    array(
        'column' => 'offset_doc',
        'value' => '',
        'type' => 'integer',
    )
);

array_push(
    $aArgs['data'],
    array(
        'column' => 'typist',
        'value' => $_SESSION['user']['UserId'],
        'type' => 'string',
    )
);

array_push(
    $aArgs['data'],
    array(
        'column' => 'coll_id',
        'value' => 'letterbox_coll',
        'type' => 'string',
    )
);

array_push(
    $aArgs['data'],
    array(
        'column' => 'res_id_master',
        'value' => $aArgs['resIdMaster'],
        'type' => 'string',
    )
);

// res_attachment insertion
if (count($parentResId) == 1) {
    array_push(
        $aArgs['data'],
        array(
            'column' => 'res_id_master',
            'value' => $parentResId[0],
            'type' => 'string',
        )
    );
    $insertResAttach = \Resource\controllers\StoreController::storeResourceRes($aArgs);
} else {
    for ($i = 0; $i < count($parentResId); $i++) {
        array_push(
            $aArgs['data'],
            array(
                'column' => 'res_id_master',
                'value' => $parentResId[$i],
                'type' => 'string',
            )
        );
        $insertResAttach = \Resource\controllers\StoreController::storeResourceRes($aArgs);
    }
}
unset($_SESSION['save_chrono_number']); // Usefull to avoid duplicate chrono number