<?php

require_once 'core/class/class_security.php';

$security = new security();
$right = $security->test_right_doc('letterbox_coll', $_REQUEST['identifier']);

//REDIRECT IF NO RIGHT
if (!$right) {
    $_SESSION['error'] = _NO_RIGHT_TXT;
    echo "<script language=\"javascript\" type=\"text/javascript\">window.top.location.href='index.php';</script>";
    exit();
}

$user = \SrcCore\models\DatabaseModel::select([
    'select'    => ['id'],
    'table'     => ['users'],
    'where'     => ['user_id = ?'],
    'data'      => [$_SESSION['user']['UserId']],
    ]);

\SrcCore\models\DatabaseModel::insertMultiple([
    'table'         => 'acknowledgement_receipts',
    'columns'       => ['res_id', 'type', 'format', 'user_id', 'contact_address_id', 'creation_date', 'send_date', 'docserver_id', 'path', 'filename', 'fingerprint'],
    'values'        => [[$_REQUEST['identifier'], 'simple', 'html', $user[0]['id'], 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0, 0, 0, 0]]
]);
