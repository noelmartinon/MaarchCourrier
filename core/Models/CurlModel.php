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
 * @ingroup core
 */

namespace Core\Models;

class CurlModel
{
    public static function exec(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['curlCallId']);
        ValidatorModel::stringType($aArgs, ['curlCallId']);
        ValidatorModel::arrayType($aArgs, ['bodyData']);
        ValidatorModel::boolType($aArgs, ['noAuth']);

        $curlConfig = CurlModel::getConfigByCallId(['curlCallId' => $aArgs['curlCallId']]);
        if (empty($curlConfig)) {
            return [];
        }

        $opts = [
            CURLOPT_URL => $curlConfig['url'],
            CURLOPT_HTTPHEADER => [
                'accept:application/json',
                'content-type:application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
        ];
        if (empty($aArgs['noAuth'])) {
            $opts[CURLOPT_HTTPHEADER][] = 'Authorization: Basic ' . base64_encode($curlConfig['user']. ':' .$curlConfig['password']);
        }

        if ($curlConfig['method'] == 'POST' || $curlConfig['method'] == 'PUT') {
            if (!empty($curlConfig['data'])) {
                $data = [];
                foreach ($curlConfig['data'] as $key => $value) {
                    $data[$key] = $aArgs['bodyData'][$value];
                }
                $opts[CURLOPT_POSTFIELDS] = json_encode($data);
            } else {
                $opts[CURLOPT_POSTFIELDS] = json_encode($aArgs['bodyData']);
            }
        }
        if ($curlConfig['method'] == 'POST') {
            $opts[CURLOPT_POST] = true;
        } elseif ($curlConfig['method'] == 'PUT' || $curlConfig['method'] == 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = $curlConfig['method'];
        }

        $curl = curl_init();
        curl_setopt_array($curl, $opts);
        $rawResponse = curl_exec($curl);

        return json_decode($rawResponse, true);
    }

    public static function getConfigByCallId(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['curlCallId']);
        ValidatorModel::stringType($aArgs, ['curlCallId']);

        $customId = CoreConfigModel::getCustomId();
        if (file_exists("custom/{$customId}/apps/maarch_entreprise/xml/curlCall.xml")) {
            $path = "custom/{$customId}/apps/maarch_entreprise/xml/curlCall.xml";
        } else {
            $path = 'apps/maarch_entreprise/xml/curlCall.xml';
        }

        $curlConfig = [];
        if (file_exists($path)) {
            $loadedXml = simplexml_load_file($path);

            $curlConfig['user']     = (string)$loadedXml->user;
            $curlConfig['password'] = (string)$loadedXml->password;
            foreach ($loadedXml->call as $call) {
                if ((string)$call->id == $aArgs['curlCallId']) {
                    $curlConfig['url']      = (string)$call->url;
                    $curlConfig['method']   = strtoupper((string)$call->method);
                    if (!empty($call->data)) {
                        $curlConfig['data'] = [];
                        foreach ($call->data as $data) {
                            $curlConfig['data'][(string)$data->key] = (string)$data->value;
                        }
                    }
                }
            }
        }

        return $curlConfig;
    }
}
