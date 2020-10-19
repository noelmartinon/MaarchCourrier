<?php

require '../../vendor/autoload.php';

chdir('../..');

$nonReadableFiles = [];
$migrated = 0;
$customs =  scandir('custom');

foreach ($customs as $custom) {
    if (in_array($custom, ['custom.json', 'custom.xml', '.', '..'])) {
        continue;
    }

    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $custom]);
    $GLOBALS['customId'] = $custom;

    $configPath           = "custom/{$custom}/apps/maarch_entreprise/xml/config.json";
    $exportSedaConfigPath = "modules/export_seda/xml/config.xml";
    if (file_exists($configPath)) {
        if (!is_readable($configPath) || !is_writable($configPath)) {
            $nonReadableFiles[] = $configPath;
            continue;
        }
        
        if (is_readable("custom/{$custom}/{$exportSedaConfigPath}") && is_writable("custom/{$custom}/{$exportSedaConfigPath}")) {
            $exportSedaFilePath = "custom/{$custom}/{$exportSedaConfigPath}";
        } elseif (is_readable($exportSedaConfigPath) && is_writable($exportSedaConfigPath)) {
            $exportSedaFilePath = $exportSedaConfigPath;
        } else {
            printf("Aucun fichier de configuration de export seda trouvé pour le custom {$custom}\n");
            continue;
        }

        $loadedExportSedaXml = simplexml_load_file($exportSedaFilePath);
        if ($loadedExportSedaXml) {
            $file = file_get_contents($configPath);
            $file = json_decode($file, true);

            $exportSeda = [];
            $exportSeda['sae']                 = 'MaarchRM';
            $exportSeda['token']               = (string)$loadedExportSedaXml->CONFIG->token;
            $exportSeda['urlSAEService']       = (string)$loadedExportSedaXml->CONFIG->urlSAEService;
            $exportSeda['senderOrgRegNumber']  = (string)$loadedExportSedaXml->CONFIG->senderOrgRegNumber;
            $exportSeda['accessRuleCode']      = (string)$loadedExportSedaXml->CONFIG->accessRuleCode;
            $exportSeda['certificateSSL']      = (string)$loadedExportSedaXml->CONFIG->certificateSSL;
            $exportSeda['userAgent']           = (string)$loadedExportSedaXml->CONFIG->userAgent;
            $exportSeda['statusReplyReceived'] = 'REPLY_SEDA';
            $exportSeda['M2M']['gec']          = 'maarch_courrier';
            $file['exportSeda'] = $exportSeda;

            $fp = fopen($configPath, 'w+');
            fwrite($fp, json_encode($file, JSON_PRETTY_PRINT));
            fclose($fp);

            $migrated++;
        }
    }
}

foreach ($nonReadableFiles as $file) {
    printf("The file %s it is not readable or not writable.\n", $file);
}

printf($migrated . " custom(s) avec config.xml (export seda) trouvé(s) et migré(s).\n");
