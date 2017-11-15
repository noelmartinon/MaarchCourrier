<?php

$confirm = true;

$etapes = ['send'];

function manage_send($aId)
{
    $result = '';

    foreach ($aId as $resId) {
        $document = \Core\Models\ResModel::getById(['resId' => $resId, 'select' => ['res_id', 'format', 'path', 'filename']]);
        $docserver = \Core\Models\DocserverModel::getByCollId(['collId' => 'letterbox_coll', 'priority' => true, 'select' => ['path_template']]);

        $file = file_get_contents($docserver['path_template'] . str_replace('#', '/', $document['path']) . $document['filename']);
        $encodedFile = base64_encode($file);

        $bodyParams = [
            'resId'         => $document['res_id'],
            'encodedFile'   => $encodedFile,
            'fileFormat'    => $document['format']
        ];
        $response = \Core\Models\CurlModel::exec(['curlCallId' => 'sendFile', 'bodyData' => $bodyParams]);

        if (!empty($response['publikId'])) {
            \Core\Models\ResModel::update(['res_id' => $document['res_id'], 'data' => ['custom_t1' => $response['publikId']]]);
        }
        $result .= $resId . '#';
    }

    return ['result' => $result, 'history_msg' => ''];
}
