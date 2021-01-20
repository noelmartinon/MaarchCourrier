<?php

require '../../vendor/autoload.php';

chdir('../..');

$customs = scandir('custom');

$migrated = 0;
foreach ($customs as $customId) {
    if (in_array($customId, ['custom.json', 'custom.xml', '.', '..'])) {
        continue;
    }
    
    $GLOBALS['customId'] = $customId;
    $configPath = 'modules/visa/xml/remoteSignatoryBooks.xml';
    if (!empty($customId) && is_file("custom/{$customId}/{$configPath}")) {
        $path = "custom/{$customId}/{$configPath}";
    } else {
        $path = $configPath;
    }

    $loadedXml = \SrcCore\models\CoreConfigModel::getXmlLoaded(['path' => $path]);
    if ($loadedXml) {
        $i = 0;
        foreach ($loadedXml->signatoryBook as $item) {
            if ((string)$item->id == 'ixbus') {
                $loadedXml->signatoryBook[$i]->tokenAPI = 'tokenAPI';
                unset($loadedXml->signatoryBook[$i]->userId);
                unset($loadedXml->signatoryBook[$i]->password);
                unset($loadedXml->signatoryBook[$i]->organizationId);
                unset($loadedXml->signatoryBook[$i]->ixbusIdEtatRefused);
                unset($loadedXml->signatoryBook[$i]->ixbusIdEtatValidated);
            }
            ++$i;
        }

        $res = formatXml($loadedXml);
        $fp  = fopen($path, "w+");
        if ($fp) {
            fwrite($fp, $res);
        }


        $migrated++;
    }
}

printf("Migration configuration ixbus : {$migrated} configuration migrée(s).\n");

function formatXml($simpleXMLElement)
{
    $xmlDocument = new DOMDocument('1.0');
    $xmlDocument->preserveWhiteSpace = false;
    $xmlDocument->formatOutput = true;
    $xmlDocument->loadXML($simpleXMLElement->asXML());

    return $xmlDocument->saveXML();
}
