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


 function setConfigNotification_batch_config_Xml($from, $to, $host, $user, $pass, $type, $port, $auth, $charset, $smtpSecure, $mailfrom, $smtpDomains)
 {
     $xmlconfig = simplexml_load_file(realpath('.').'/custom/cs_'.$_SESSION['config']['databasename'].'/bin/notification/config/config.xml');

     $CONFIG = $xmlconfig->CONFIG;
        
     $CONFIG->MaarchDirectory = realpath('.')."/";
     if ($_SERVER['REMOTE_ADDR'] == '::1') {
         $REMOTE_ADDR = 'localhost';
     } else {
         $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
     }
     $chemin = $REMOTE_ADDR . dirname($_SERVER['PHP_SELF']);
     $maarchUrl = rtrim($chemin, "install");
     $maarchUrl = $maarchUrl . 'cs_'.$_SESSION['config']['databasename'].'/';
     $CONFIG->MaarchUrl = $maarchUrl;

     $res = $xmlconfig->asXML();
     $fp = @fopen(realpath('.')."/custom/cs_".$_SESSION['config']['databasename']."/bin/notification/config/config.xml", "w+");
     if (!$fp) {
         return false;
         exit;
     }
     $write = fwrite($fp, $res);
     if (!$write) {
         return false;
         exit;
     }
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

        setConfigNotification_batch_config_Xml($from, $to, $host, $user, $pass, $_REQUEST['smtpType'], $port, $auth, $charset, $smtpSecure, $from, $_REQUEST['smtpDomains']);

        $return2['status'] = 2;
        $return2['text'] = _SMTP_OK;

        $jsonReturn = json_encode($return2);

        echo $jsonReturn;
        exit;
    }
} elseif ($_REQUEST['type'] == 'add') {
    setConfigSendmail_batch_config_Xml($from, $to, $host, $user, $pass, $_REQUEST['smtpType'], $port, $auth, $charset, $smtpSecure, $from, $_REQUEST['smtpDomains']);

    setConfigNotification_batch_config_Xml($from, $to, $host, $user, $pass, $_REQUEST['smtpType'], $port, $auth, $charset, $smtpSecure, $from, $_REQUEST['smtpDomains']);

    $return2['status'] = 2;
    $return2['text'] = _INFO_SMTP_OK;

    $jsonReturn = json_encode($return2);

    echo $jsonReturn;
    exit;
}
