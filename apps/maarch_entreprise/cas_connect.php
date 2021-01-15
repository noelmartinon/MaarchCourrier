<?php

include_once('apps/maarch_entreprise/tools/phpCAS/CAS.php');
require_once('core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_request.php');
require_once('core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_history.php');
require_once('core' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'class_core_tools.php');
$core = new core_tools();

/**** RECUPERATION DU FICHIER DE CONFIG ****/
if (file_exists($_SESSION['config']['corepath'] . 'custom' .
    DIRECTORY_SEPARATOR . $_SESSION['custom_override_id'] .
    DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR .
    $_SESSION['config']['app_id'] . DIRECTORY_SEPARATOR . 'xml' .
    DIRECTORY_SEPARATOR . 'cas_config.xml')
) {
    $xmlPath = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
    . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'apps'
    . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
    . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'cas_config.xml';
} elseif (file_exists($_SESSION['config']['corepath'] . 'apps'
    . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
    . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR .
    'cas_config.xml')
) {
    $xmlPath = $_SESSION['config']['corepath'] . DIRECTORY_SEPARATOR . 'apps'
    . DIRECTORY_SEPARATOR . $_SESSION['config']['app_id']
    . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'cas_config.xml';
} else {
    echo _XML_FILE_NOT_EXISTS;
    exit;
}

$xmlconfig         = simplexml_load_file($xmlPath);
$loginRequestArray = array();
$loginRequestArray = $core->object2array($xmlconfig);

// Les paramètres du serveur CAS
$cas_serveur   = $loginRequestArray['WEB_CAS_URL'];
$cas_port      = $loginRequestArray['WEB_CAS_PORT'];
$cas_context   = $loginRequestArray['WEB_CAS_CONTEXT'];
$id_separator  = $loginRequestArray['ID_SEPARATOR'];
$certificate   = $loginRequestArray['PATH_CERTIFICATE'];

$_SESSION['cas_version']      = $loginRequestArray['CAS_VERSION'];
$_SESSION['cas_serveur']      = $cas_serveur;
$_SESSION['cas_port']         = $cas_port;
$_SESSION['cas_context']      = $cas_context;
$_SESSION['cas_certificate']  = $certificate;
$_SESSION['cas_id_separator'] = $id_separator;

phpCAS::setDebug();
phpCAS::setVerbose(true);

// Initialisation phpCAS
if ($loginRequestArray['CAS_VERSION'] == 'CAS_VERSION_3_0') {
    $changeSessionId = false;
} else {
    $changeSessionId = true;
}

phpCAS::client(constant($loginRequestArray['CAS_VERSION']), $cas_serveur, (int)$cas_port, $cas_context, $changeSessionId);

// Le certificat de l'autorité racine
if (!empty($certificate)) {
    phpCAS::setCasServerCACert($certificate);
} else {
    phpCAS::setNoCasServerValidation();
}

// L'authentification.
phpCAS::forceAuthentication();

if (in_array($loginRequestArray['CAS_VERSION'], ['CAS_VERSION_2_0', 'CAS_VERSION_3_0'])) {
    // Lecture identifiant utilisateur (courriel)
    $Id = phpCAS::getUser();
    echo 'Identifiant : ' . phpCAS::getUser();
    echo '<br/> phpCAS version : ' . phpCAS::getVersion();
    if (!empty($id_separator)) {
        $tmpId = explode($id_separator, $Id);
        $userId = $tmpId[0];
    } else {
        $userId = $Id;
    }
} elseif ($loginRequestArray['CAS_VERSION'] == 'SAML_VERSION_1_1') {
    // $attrSAML = phpCAS::getAttributes();
    echo _CAS_SAML_NOT_SUPPORTED;
    exit;
} else {
    echo _PROTOCOL_NOT_SUPPORTED;
    exit;
}

$db    = new Database();
$query = "SELECT user_id FROM users WHERE lower(user_id) = lower(?)";
$stmt  = $db->query($query, array($userId));

if ($stmt->rowCount() == 0) {
    echo '<br>' . _USER_NOT_EXIST;
    exit;
}

$loginArray['password'] = 'maarch';

$protocol = 'http://';
if ((int)$cas_port == 443) {
    $protocol = 'https://';
}

$_SESSION['web_cas_url'] = $protocol. $cas_serveur . $cas_context .'/logout';

/**** CONNECTION A MAARCH ****/
$trace = new history();
header("location: log.php");

//Traces fonctionnelles
$trace->add(
    "users",
    $userId,
    "LOGIN",
    "userlogin",
    _CONNECTION_CAS_OK,
    $_SESSION['config']['databasetype'],
    "ADMIN",
    false, 'ok', 'DEBUG', $userId
);

exit();
