<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Synchronization Script
 * @author dev@maarch.org
 */
libxml_use_internal_errors(true);
chdir('../..');

require 'vendor/autoload.php';

main($argv);

function main($argv)
{
    $customId = null;
    if (!empty($argv[1]) && $argv[1] == '--customId' && !empty($argv[2])) {
        $customId = $argv[2];
        $GLOBALS['customId'] = $customId;
    }
    $path = 'apps/maarch_entreprise/xml/config.json';
    if (!empty($customId)) {
        $path = "custom/{$customId}/apps/maarch_entreprise/xml/config.json";
    }
    $file = file_get_contents($path);
    $file = json_decode($file, true)['database'][0];
    if (empty($file['server'])) {
        writeLog(['message' => "[ERROR] Tag maarchUrl is missing in config.json"]);
        exit();
    }

    $dbconn = pg_connect('host='.$file['server'].' dbname='.$file['name'].' port='.$file['port'].' user='.$file['user'].' password='.$file['password']) 
    or die('Connexion impossible : ' . pg_last_error());

    $query = 'SELECT res_id,typist FROM res_letterbox';
    $result = pg_fetch_all(pg_query($query));
    $count = count($result);
    $i = 0;
    foreach ($result as $key => $value) {
        echo "res_id : ".$value['res_id'];
        updateExpediteur($value);
        updateDestinataire($value);
        $i++;
        echo " / avancement : $i sur $count".PHP_EOL;
    }
}

function updateExpediteur ($row) {
    $select = "SELECT  lastname || ' ' || firstname as expediteur FROM users WHERE id = ".$row['typist'];
    $res = pg_query($select) or die('Échec de la requête : ' . pg_last_error());
    $expe = pg_fetch_all($res)[0]['expediteur'];
    echo " / expediteur : ".$expe;
    $update = "UPDATE res_letterbox SET custom_fields = jsonb_set(custom_fields,'{\"20\"}', '".json_encode($expe)."') WHERE res_id = ".$row['res_id']." returning custom_fields;";
    $result = pg_query($update) or die('Échec de la requête : ' . pg_last_error());
}

function updateDestinataire ($row) {
    $select = "SELECT entity_by_res_id(".$row['res_id'].") as destinataire;";
    $res = pg_query($select) or die('Échec de la requête : ' . pg_last_error());
    $dest = pg_fetch_all($res)[0]['destinataire'];
    echo " / societé expéditrice : ".$dest;
    $update = "UPDATE res_letterbox SET custom_fields = jsonb_set(custom_fields,'{\"18\"}', '".json_encode($dest)."') WHERE res_id = ".$row['res_id']." returning custom_fields;";
    $result = pg_query($update) or die('Échec de la requête : ' . pg_last_error());
}