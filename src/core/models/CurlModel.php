<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Curl Model
 * @author dev@maarch.org
 */

namespace SrcCore\models;

use SrcCore\controllers\LogsController;

class CurlModel
{
    public static function exec(array $aArgs)
    {
        ValidatorModel::stringType($aArgs, ['curlCallId']);
        ValidatorModel::arrayType($aArgs, ['bodyData']);
        ValidatorModel::boolType($aArgs, ['noAuth', 'multipleObject']);

        if (!empty($aArgs['curlCallId'])) {
            $curlConfig = CurlModel::getConfigByCallId(['curlCallId' => $aArgs['curlCallId']]);
        } else {
            $curlConfig['url']      = $aArgs['url'];
            $curlConfig['user']     = $aArgs['user'];
            $curlConfig['password'] = $aArgs['password'];
            $curlConfig['method']   = $aArgs['method'];
        }

        $opts = [
            CURLOPT_URL => $curlConfig['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        if (empty($aArgs['multipleObject'])) {
            $opts[CURLOPT_HTTPHEADER][] = 'accept:application/json';
            $opts[CURLOPT_HTTPHEADER][] = 'content-type:application/json';
        }

        if (empty($aArgs['noAuth']) && !empty($curlConfig['user']) && !empty($curlConfig['password'])) {
            $opts[CURLOPT_HTTPHEADER][] = 'Authorization: Basic ' . base64_encode($curlConfig['user']. ':' .$curlConfig['password']);
        }

        if (!empty($curlConfig['header'])) {
            if (empty($opts[CURLOPT_HTTPHEADER])) {
                $opts[CURLOPT_HTTPHEADER] = [];
            }
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $curlConfig['header']);
        }

        if ($curlConfig['method'] == 'POST' || $curlConfig['method'] == 'PUT') {
            if (is_array($aArgs['bodyData']) && !empty($aArgs['bodyData']) && $aArgs['multipleObject']) {
                $bodyData = [];
                foreach ($aArgs['bodyData'] as $key => $value) {
                    if (is_object($value)) {
                        $bodyData[$key] = $value;
                    } else {
                        $bodyData[$key] = json_encode($value);
                    }
                }
            } else {
                $bodyData = json_encode($aArgs['bodyData']);
            }
            $opts[CURLOPT_POSTFIELDS] = $bodyData;
        }
        if ($curlConfig['method'] == 'POST' && empty($aArgs['multipleObject'])) {
            $opts[CURLOPT_POST] = true;
        } elseif ($curlConfig['method'] == 'PUT' || $curlConfig['method'] == 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = $curlConfig['method'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $rawResponse = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'curl',
            'level'     => 'DEBUG',
            'tableName' => '',
            'recordId'  => 'Body : ' . json_encode($bodyData),
            'eventType' => 'Exec Curl : ' . $curlConfig['url'],
            'eventId'   => $rawResponse
        ]);

        if (!empty($error)) {
            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'curl',
                'level'     => 'ERROR',
                'tableName' => '',
                'recordId'  => '',
                'eventType' => 'Error Exec Curl : ' . $error,
                'eventId'   => $rawResponse
            ]);
            return ['error' => $error];
        }

        return json_decode($rawResponse, true);
    }

    public static function execSOAP(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['xmlPostString', 'url']);
        ValidatorModel::stringType($aArgs, ['xmlPostString', 'url', 'soapAction']);
        ValidatorModel::arrayType($aArgs, ['options']);

        $opts = [
            CURLOPT_URL             => $aArgs['url'],
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $aArgs['xmlPostString'],
            CURLOPT_HTTPHEADER      => [
                'content-type:text/xml;charset="utf-8"',
                'accept:text/xml',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Content-length: ' . strlen($aArgs['xmlPostString']),
            ],
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_SSL_VERIFYPEER  => false
        ];

        if (!empty($aArgs['soapAction'])) {
            $opts[CURLOPT_HTTPHEADER][] = "SOAPAction: \"{$aArgs['soapAction']}\"";
        }
        if (!empty($aArgs['options'])) {
            foreach ($aArgs['options'] as $key => $option) {
                $opts[$key] = $option;
            }
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $rawResponse = curl_exec($curl);
        $error = curl_error($curl);
        $infos = curl_getinfo($curl);

        $cookies = [];
        if (!empty($aArgs['options'][CURLOPT_HEADER])) {
            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $rawResponse, $matches);
            foreach ($matches[1] as $item) {
                $cookie = explode("=", $item);
                $cookies = array_merge($cookies, [$cookie[0] => $cookie[1]]);
            }
            $rawResponse = substr($rawResponse, $infos['header_size']);
        } elseif (!empty($aArgs['delete_header'])) { // Delete header for iparapheur
            $body = strstr($rawResponse, '<?xml'); // put the header ahead
            if (empty($body)) {
                $body = explode(PHP_EOL, $rawResponse);
                // we remove the 4 starting item of the array (header)
                for ($i=0; $i < 5; $i++) {
                    array_shift($body);
                }
                // and the last item (footer)
                array_pop($body);
                $body = join('', $body);
            }
            $pattern = '/--uuid:[0-9a-f-]+--/';                  // And also the footer
            $rawResponse = preg_replace($pattern, '', $body);
        }

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'curl',
            'level'     => 'DEBUG',
            'tableName' => '',
            'recordId'  => '',
            'eventType' => 'Exec Curl : ' . $aArgs['url'],
            'eventId'   => $rawResponse
        ]);

        if (!empty($error)) {
            LogsController::add([
                'isTech'    => true,
                'moduleId'  => 'curl',
                'level'     => 'ERROR',
                'tableName' => '',
                'recordId'  => '',
                'eventType' => 'Error Exec Curl : ' . $error,
                'eventId'   => $rawResponse
            ]);
        }

        return ['response' => simplexml_load_string($rawResponse), 'infos' => $infos, 'cookies' => $cookies, 'raw' => $rawResponse, 'error' => $error];
    }

    public static function getConfigByCallId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['curlCallId']);
        ValidatorModel::stringType($aArgs, ['curlCallId']);

        $curlConfig = [];

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/curlCall.xml']);
        if ($loadedXml) {
            $curlConfig['user']     = (string)$loadedXml->user;
            $curlConfig['password'] = (string)$loadedXml->password;
            $curlConfig['apiKey']   = (string)$loadedXml->apiKey;
            $curlConfig['appName']  = (string)$loadedXml->appName;
            foreach ($loadedXml->call as $call) {
                if ((string)$call->id == $aArgs['curlCallId']) {
                    $curlConfig['url']      = (string)$call->url;
                    $curlConfig['method']   = strtoupper((string)$call->method);
                    if (!empty($call->file)) {
                        $curlConfig['file'] = (string)$call->file;
                    }
                    if (!empty($call->header)) {
                        $curlConfig['header'] = [];
                        foreach ($call->header as $data) {
                            $curlConfig['header'][] = (string)$data;
                        }
                    }
                    if (!empty($call->sendInObject)) {
                        $curlConfig['inObject'] = true;
                        foreach ($call->sendInObject as $object) {
                            $tmpdata = [];
                            if (!empty($object->data)) {
                                foreach ($object->data as $data) {
                                    $tmpdata[(string)$data->key] = (string)$data->value;
                                }
                            }
                            $tmpRawData = [];
                            if (!empty($object->rawData)) {
                                foreach ($object->rawData as $data) {
                                    $tmpRawData[(string)$data->key] = (string)$data->value;
                                }
                            }
                            $curlConfig['objects'][] = [
                                'name'      => (string)$object->objectName,
                                'data'      => $tmpdata,
                                'rawData'   => $tmpRawData
                            ];
                        }
                    }
                    if (!empty($call->data)) {
                        $curlConfig['data'] = [];
                        foreach ($call->data as $data) {
                            $curlConfig['data'][(string)$data->key] = (string)$data->value;
                        }
                    }
                    if (!empty($call->rawData)) {
                        $curlConfig['rawData'] = [];
                        foreach ($call->rawData as $data) {
                            $curlConfig['rawData'][(string)$data->key] = (string)$data->value;
                        }
                    }
                    if (!empty($call->return)) {
                        $curlConfig['return']['key'] = (string)$call->return->key;
                        $curlConfig['return']['value'] = (string)$call->return->value;
                    }
                }
            }
        }

        return $curlConfig;
    }

    public static function execSimple(array $args)
    {
        ValidatorModel::notEmpty($args, ['url', 'method']);
        ValidatorModel::stringType($args, ['url', 'method', 'user', 'password']);
        ValidatorModel::arrayType($args, ['headers', 'queryParams', 'basicAuth', 'bearerAuth']);

        $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_SSL_VERIFYPEER => false];

        //Headers
        if (!empty($args['headers'])) {
            $opts[CURLOPT_HTTPHEADER] = $args['headers'];
        }
        //Auth
        if (!empty($args['basicAuth'])) {
            $opts[CURLOPT_HTTPHEADER][] = 'Authorization: Basic ' . base64_encode($args['basicAuth']['user']. ':' .$args['basicAuth']['password']);
        }
        if (!empty($args['bearerAuth'])) {
            $opts[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $args['bearerAuth']['token'];
        }

        //QueryParams
        if (!empty($args['queryParams'])) {
            $args['url'] .= '?';
            $i = 0;
            foreach ($args['queryParams'] as $queryKey => $queryParam) {
                if ($i > 0) {
                    $args['url'] .= '&';
                }
                $args['url'] .= "{$queryKey}={$queryParam}";
                ++$i;
            }
        }

        //Body
        if (!empty($args['body'])) {
            $opts[CURLOPT_POSTFIELDS] = $args['body'];
        }
        //MultipartBody
        if (!empty($args['multipartBody'])) {
            $boundary = uniqid();
            $postData = CurlModel::createMultipartFormData(['boundary' => $boundary, 'body' => $args['multipartBody']]);
            $opts[CURLOPT_HTTPHEADER][] = "Content-Type: multipart/form-data; boundary=-------------{$boundary}";
            $opts[CURLOPT_POSTFIELDS] = $postData;
        }
        //Method
        if ($args['method'] == 'POST') {
            $opts[CURLOPT_POST] = true;
        } elseif ($args['method'] == 'PUT' || $args['method'] == 'PATCH' || $args['method'] == 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = $args['method'];
        }

        //Url
        $opts[CURLOPT_URL] = $args['url'];

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $rawResponse = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $errors = curl_error($curl);
        curl_close($curl);

        $headers = substr($rawResponse, 0, $headerSize);
        $headers = explode("\r\n", $headers);
        $response = substr($rawResponse, $headerSize);

        LogsController::add([
            'isTech'    => true,
            'moduleId'  => 'curl',
            'level'     => 'DEBUG',
            'tableName' => 'curl',
            'recordId'  => 'execSimple',
            'eventType' => "Url : {$args['url']} HttpCode : {$code} Errors : {$errors}",
            'eventId'   => "Response : {$response}"
        ]);

        return ['code' => $code, 'headers' => $headers, 'response' => json_decode($response, true), 'errors' => $errors];
    }

    private static function createMultipartFormData(array $args)
    {
        ValidatorModel::notEmpty($args, ['boundary', 'body']);
        ValidatorModel::stringType($args, ['boundary']);
        ValidatorModel::arrayType($args, ['body']);

        $delimiter = "-------------{$args['boundary']}";

        $postData = '';
        foreach ($args['body'] as $key => $value) {
            $postData .= "--{$delimiter}\r\n";
            $postData .= "Content-Disposition: form-data; name=\"{$key}\"\r\n";
            $postData .= "\r\n";
            $postData .= "{$value}\r\n";
        }
        $postData .= "--{$delimiter}--\r\n";

        return $postData;
    }

    public static function isEnabled(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['curlCallId']);
        ValidatorModel::stringType($aArgs, ['curlCallId']);

        $loadedXml = CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/curlCall.xml']);
        if ($loadedXml) {
            foreach ($loadedXml->call as $call) {
                if ((string)$call->id == $aArgs['curlCallId']) {
                    if (!empty((string)$call->enabled) && (string)$call->enabled == 'true') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function makeCurlFile(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['path']);
        ValidatorModel::stringType($aArgs, ['path']);

        $mime = mime_content_type($aArgs['path']);
        $info = pathinfo($aArgs['path']);
        $name = $info['basename'];
        $output = new \CURLFile($aArgs['path'], $mime, $name);

        return $output;
    }
}
