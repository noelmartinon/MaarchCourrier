<?php

$confirm = true;

$etapes = ['send'];

function manage_send($aId)
{
    $result = '';

    foreach ($aId as $resId) {
        $document = \Core\Models\ResModel::getById(['resId' => $aId[0], 'select' => ['custom_t1']]);

        $bodyParams = [
            'custom_t1' => $document['custom_t1'],
        ];
        \Core\Models\CurlModel::exec(['curlCallId' => 'sendData', 'bodyData' => $bodyParams]);

        $result .= $resId . '#';
    }

    return ['result' => $result, 'history_msg' => ''];
}
