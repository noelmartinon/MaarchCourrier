<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

*
* @brief   ajaxIndexingModel
*
* @author  dev <dev@maarch.org>
* @ingroup apps
*/
$db = new Database();
$mode = $_REQUEST['mode'];

if ($mode == 'up') {
    $id = $_REQUEST['id'];
    $content = $_REQUEST['content'];
    $stmt = $db->query(
        'UPDATE indexingModels SET fields_content = ? WHERE id = ?', [$content, $id]);
    $result_txt = 'Modèle modifié';
} elseif ($mode == 'del') {
    $id = $_REQUEST['id'];

    $stmt = $db->query(
        'DELETE FROM indexingModels WHERE id = ?', [$id]
    );
    $result_txt = 'Modèle supprimé';
} elseif ($mode == 'get') {
    $id = $_REQUEST['id'];

    $stmt = $db->query(
        'select fields_content FROM indexingModels WHERE id=?', [$id]
    );

    $res = $stmt->fetchObject();
    $result_txt = $res->fields_content;
} else {
    $label = $_REQUEST['label'];
    $content = $_REQUEST['content'];
    $mode = $_REQUEST['mode'];

    $stmt = $db->query(
        'INSERT INTO indexingModels (label, fields_content) VALUES(?,?)', [$label, $content]
    );
    $id = $db->lastInsertId('indexingmodels_id_seq');

    $stmt = $db->query(
        'select id,label FROM indexingModels WHERE id=?', [$id]
    );

    $res = $stmt->fetchObject();
    $result = json_encode($res);

    $result_txt = 'Modèle ajouté';
}

echo '{"status" : 0,"result" : "'.addslashes($result).'","result_txt" : "'.addslashes($result_txt).'"}';
exit();
