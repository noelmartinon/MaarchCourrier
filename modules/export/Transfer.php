<?php

require_once __DIR__ . '/AdapterMaarchRM.php';
require_once __DIR__ . '/AdapterMaarchCourrier.php';
class Transfer{
    public function __construct(){}

    public function transfer($target, $reference, $communicationType = 'url') {
        $adapter = '';
        $res['status'] = 0;
        $res['content'] = '';

        if ($target == 'maarchrm') {
            $adapter = new AdapterMaarchRM();
        } elseif ($target == 'maarchcourrier' ) {
            $adapter = new AdapterMaarchCourrier();
        } else {
            $_SESSION['error'] = _UNKNOWN_TARGET;
            return false;
        }

        $param = $adapter->getInformations($reference); // [0] = url, [1] = header, [2] = cookie, [3] = data

        try {
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $param[0]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $param[1]);
            curl_setopt($curl, CURLOPT_COOKIE, $param[2]);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $param[3]);

            $exec = curl_exec($curl);
            $data = json_decode($exec);

            if (!$data) {
                $res['status'] = 1;
                $res['content'] = curl_error($curl);
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