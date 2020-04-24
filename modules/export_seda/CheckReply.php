<?php

require_once 'vendor/autoload.php';
require_once __DIR__ . '/RequestSeda.php';
require_once __DIR__ . '/class/AbstractMessage.php';
require_once __DIR__ . '/class/ArchiveTransferReply.php';

class CheckReply
{
    protected $token;
    protected $SAE;
    protected $db;
    protected $xml;

    public function __construct()
    {
        $getXml = false;
        $path = '';
        if (file_exists(
            $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
            . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'xml'
            . DIRECTORY_SEPARATOR . 'config.xml'
        )) {
            $path = $_SESSION['config']['corepath'] . 'custom' . DIRECTORY_SEPARATOR
                . $_SESSION['custom_override_id'] . DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . 'export_seda'. DIRECTORY_SEPARATOR . 'xml'
                . DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        } elseif (file_exists($_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'.  DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml')) {
            $path = $_SESSION['config']['corepath'] . 'modules' . DIRECTORY_SEPARATOR . 'export_seda'
                . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'config.xml';
            $getXml = true;
        }

        if ($getXml) {
            $this->xml = simplexml_load_file($path);
        }

        $this->token = (string) $this->xml->CONFIG->token;
        $tokenEncode = urlencode($this->token);
        $this->token = "LAABS-AUTH=". $tokenEncode;
        $this->urlService = (string) $this->xml->CONFIG->urlSAEService . "/medona/message/reference";
        $this->userAgent = (string) $this->xml->CONFIG->userAgent;
        if (empty($this->userAgent)) {
            $this->userAgent = 'maarchrestclient';
        }
        $this->db = new RequestSeda();

        if (!empty($_SESSION['custom_override_id'])) {
            new \SrcCore\models\DatabasePDO(['customId' => $_SESSION['custom_override_id']]);
        }
    }

    public function checkAll()
    {
        $abstractMessage = new AbstractMessage();

        $letters = $this->db->getLettersByStatus("ACK_SEDA");

        $unitIdentifiers = [];
        foreach ($letters as $letter) {
            $unitIdentifier = $this->db->getUnitIdentifierByResId($letter->res_id);
            $message = $this->db->getMessageByIdentifier($unitIdentifier->message_id);

            if (array_key_exists($message->reference, $unitIdentifiers)) {
                $unitIdentifiers[$message->reference] .= "," . $unitIdentifier->res_id;
            } else {
                $unitIdentifiers[$message->reference] = $unitIdentifier->res_id;
            }
        }

        foreach ($unitIdentifiers as $key => $value) {
            $messages = $this->getReply($key);

            if (!isset($messages->replyMessage)) {
                continue;
            }

            //créer message reply & sauvegarder xml
            $resIds = explode(',', $value);
            $data = json_decode($messages->replyMessage->data);

            $archiveTransferReply = new ArchiveTransferReply();
            $archiveTransferReply->receive($data, $resIds);
            $abstractMessage->changeStatus($key, 'REPLY_SEDA');
        }

        return true;
    }

    public function checkAttachment($resId)
    {
        $reply = $this->db->getReply($resId);
        if (!$reply) {
            $_SESSION['error'] = _ERROR_NO_REPLY . $resId;
            return false;
        }

        $tabDir = explode('#', $reply->path);

        $dir = '';
        for ($i = 0; $i < count($tabDir); $i++) {
            $dir .= $tabDir[$i] . DIRECTORY_SEPARATOR;
        }

        $docServer = $this->db->getDocServer($reply->docserver_id);
        $fileName = $docServer->path_template. DIRECTORY_SEPARATOR . $dir . $reply->filename;
        $xml = simplexml_load_file($fileName);

        if (!$xml) {
            $_SESSION['error'] = _ERROR_NO_XML_REPLY . $resId;
            return false;
        }

        $message = $this->db->getMessageByReference($xml->MessageRequestIdentifier);
        if (!$message) {
            $_SESSION['error'] = _ERROR_NO_REFERENCE_MESSAGE_REPLY . $resId;
            return false;
        }

        $unitIdentifier = $this->db->getUnitIdentifierByResId($resId);

        if ($unitIdentifier->message_id != $message->message_id) {
            $_SESSION['error'] = _ERROR_WRONG_REPLY . $resId;
            return false;
        }

        return $resId;
    }

    public function getReply($reference)
    {
        $header = [
            'accept:application/json',
            'content-type:application/json',
            'user-agent:'.$this->userAgent
        ];

        $refEncode = str_replace('.', '%2E', urlencode($reference));
        $url = $this->urlService .'?reference=' . $refEncode;

        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_COOKIE, $this->token);

            if (empty($this->xml->CONFIG->certificateSSL)) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            } else {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

                $certificateSSL = $this->xml->CONFIG->certificateSSL;
                if (is_file($certificateSSL)) {
                    $ext = ['.crt','.pem'];

                    $filenameExt = strrchr($certificateSSL, '.');
                    if (in_array($filenameExt, $ext)) {
                        curl_setopt($curl, CURLOPT_CAINFO, $certificateSSL);
                    } else {
                        $res['status'] = 1;
                        $res['content'] = _ERROR_EXTENSION_CERTIFICATE;
                        return $res;
                    }
                } elseif (is_dir($certificateSSL)) {
                    curl_setopt($curl, CURLOPT_CAPATH, $certificateSSL);
                } else {
                    $res['status'] = 1;
                    $res['content'] = _ERROR_UNKNOW_CERTIFICATE;
                    return $res;
                }
            }

            $data = json_decode(curl_exec($curl));

            curl_close($curl);

            return $data;
        } catch (Exception $e) {
            var_dump($e);
        }
    }
}
