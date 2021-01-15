<?php

require_once __DIR__ . '/AdapterMaarchRM.php';
require_once __DIR__ . '/AdapterMaarchCourrier.php';
class Transfer
{
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
    }

    public function transfer($target, $messageId, $type = null)
    {
        $adapter = '';
        $res['status'] = 0;
        $res['content'] = '';

        if ($target == 'maarchrm') {
            $adapter = new AdapterMaarchRM();
        } elseif ($target == 'maarchcourrier') {
            $adapter = new AdapterMaarchCourrier();
        } else {
            $res['status'] = 0;
            $res['content'] = _UNKNOWN_TARGET;
            return $res;
        }

        // [0] = url, [1] = header, [2] = cookie, [3] = data
        if ($type) {
            $param = $adapter->getInformations($messageId, $type);
        } else {
            $param = $adapter->getInformations($messageId);
        }

        try {
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $param[0]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $param[1]);
            curl_setopt($curl, CURLOPT_COOKIE, $param[2]);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $param[3]);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);

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

            $exec = curl_exec($curl);
            $data = json_decode($exec);

            if (!$data || !empty($data->errors) || !empty($data->error)) {
                $res['status'] = 1;
                $curlError = curl_error($curl);
                if ($curlError) {
                    $res['content'] = $curlError;
                } elseif (!empty($data->errors)) {
                    $res['content'] = $data->errors;
                } elseif (!empty($data->error)) {
                    $res['content'] = serialize($data->error);
                }
            } else {
                $res['content'] = $data;
            }
            curl_close($curl);
        } catch (Exception $e) {
            $_SESSION['error'] = _ERROR_CURL;
            return false;
        }

        return $res;
    }
}
