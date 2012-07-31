<?php
function updateObject($request, $object)
{
    foreach($object as $key => $value) {
        if (isset($request[$key]) && $request[$key] != $value) {
            $object->$key = $request[$key];
        }
    }
}

$errors = array();

require_once 'core/class/class_core_tools.php';
$coreTools = new core_tools();
$coreTools->load_lang();

require_once('core/tests/class/DataObjectController.php');
$DataObjectController = new DataObjectController();
$DataObjectController->loadXSD($_REQUEST['schemaPathAjax']);

if ($_REQUEST['modeAjax'] == 'update') {
    $dataObject = $DataObjectController->load($_SESSION['m_admin'][$_REQUEST['objectNameAjax']]);
} elseif ($_REQUEST['modeAjax'] == 'create') {
    $dataObject = $DataObjectController->createRoot(
        $_REQUEST['objectNameAjax']
    );
}
//exit($dataObject->show());
updateObject($_REQUEST, $dataObject);    

$validateObject = $DataObjectController->validate(
    $dataObject
);

if ($validateObject) {
    try {
        $DataObjectController->save(
            $dataObject
        );
        $return['status'] = 1;
    } catch(maarch\Exception $e) {
        $return['status'] = 0;
        $return['alert']  = $e->getMessage();
    }
} else {
    /*
    foreach($DataObjectController->getMessages() as $error) {
        $errors[] = $error->message;
    }
    */
    exit($dataObject->asXml());
}

if ($return['status'] == 0) {
    $failFields = array();
    $messages = '<br /><br /><table cellspacing="0" cellpadding="5" width="70%" align="center">';
    for ($i=0; $i<count($errors); $i++) {
        $fail = explode('\'', $errors[$i]);
        array_push($failFields, $fail[1]);
        $messages .= '<tr>';
            $messages .= '<td style="text-align: left;">';
                $messages .= '<b>';
                    $messages .= $errors[$i];
                $messages .= '</b>';
            $messages .= '</td>';
        $messages .= '</tr>'; 
    }
    $messages .= '</table><br />';
    
    $return['status']   = 0;
    $return['failFields'] = $failFields;
    $return['messages'] = $messages;
}

echo json_encode($return);

