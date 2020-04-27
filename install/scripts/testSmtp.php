<?php

 function setConfigSendmail_batch_config_Xml($from, $to, $host, $user, $pass, $type, $port, $auth, $charset, $smtpSecure, $mailfrom, $smtpDomains)
 {
     \SrcCore\models\DatabasePDO::reset();
     new \SrcCore\models\DatabasePDO(['customId' => 'cs_'.$_SESSION['config']['databasename']]);

     if (!empty($pass)) {
         $pass = \SrcCore\models\PasswordModel::encrypt(['password' => $pass]);
     }

     $data = [
         'type'     => $type,
         'host'     => $host,
         'port'     => $port,
         'user'     => $user,
         'password' => $pass,
         'auth'     => $auth == 1,
         'secure'   => 'ssl',
         'charset'  => 'utf-8'
     ];
     $data = json_encode($data);
     \Configuration\models\ConfigurationModel::update(['set' => ['value' => $data], 'where' => ['service = ?'], 'data' => ['admin_email_server']]);
 }

include($_SESSION['config']['corepath']
    . '/apps/maarch_entreprise/tools/mails/htmlMimeMail.php');


$GLOBALS['mailer'] = new htmlMimeMail();
$GLOBALS['mailer']->setSMTPParams(
    $host = $_REQUEST['smtpHost'],
    $port = $_REQUEST['smtpPort'],
    $helo = $_REQUEST['smtpDomains'],
    $auth = filter_var($_REQUEST['smtpAuth'], FILTER_VALIDATE_BOOLEAN),
    $user = $_REQUEST['smtpUser'],
    $pass = $_REQUEST['smtpPassword'],
    $from = $_REQUEST['smtpMailFrom']
);

$GLOBALS['mailer']->setFrom($from);
$GLOBALS['mailer']->setSubject("Test smtp Maarch");
$GLOBALS['mailer']->setHtml("Ceci est un email de test");
$GLOBALS['mailer']->setHtmlCharset('utf-8');
$GLOBALS['mailer']->setHeadCharset('utf-8');

if ($_REQUEST['type'] == 'test') {
    $return = $GLOBALS['mailer']->send(array($_REQUEST['smtpMailTo']), $_REQUEST['smtpType']);

    if ($return == false) {
        $return2['status'] = 2;
        $return2['text'] = _SMTP_ERROR;

        $jsonReturn = json_encode($return2);

        echo $jsonReturn;
        exit;
    } else {
        require_once 'install/class/Class_Install.php';
    
        setConfigSendmail_batch_config_Xml($from, $to, $host, $user, $pass, $_REQUEST['smtpType'], $port, $auth, $charset, $smtpSecure, $from, $_REQUEST['smtpDomains']);

        $return2['status'] = 2;
        $return2['text'] = _SMTP_OK;

        $jsonReturn = json_encode($return2);

        echo $jsonReturn;
        exit;
    }
} elseif ($_REQUEST['type'] == 'add') {
    setConfigSendmail_batch_config_Xml($from, $to, $host, $user, $pass, $_REQUEST['smtpType'], $port, $auth, $charset, $smtpSecure, $from, $_REQUEST['smtpDomains']);

    $return2['status'] = 2;
    $return2['text'] = _INFO_SMTP_OK;

    $jsonReturn = json_encode($return2);

    echo $jsonReturn;
    exit;
}
