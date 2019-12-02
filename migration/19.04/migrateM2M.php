<?php

require '../../vendor/autoload.php';

chdir('../..');

$nonReadableFiles = [];
$migrated = 0;
$customs =  scandir('custom');

foreach ($customs as $custom) {
    if ($custom == 'custom.xml' || $custom == '.' || $custom == '..') {
        continue;
    }

    $natures = [];
    $path = "custom/{$custom}/apps/maarch_entreprise/xml/m2m_config.xml";
    if (file_exists($path)) {
        if (!is_readable($path) || !is_writable($path)) {
            $nonReadableFiles[] = $path;
            continue;
        }
        $loadedXml = simplexml_load_file($path);

        if ($loadedXml) {
            $annuaries = $loadedXml->addChild('annuaries');
            $annuaries->addChild('enabled', 'false');
            $annuaries->addChild('organization', 'Service');
            $annuary = $annuaries->addChild('annuary');
            $annuary->addChild('uri', '1.1.1.1');
            $annuary->addChild('baseDN', 'base');
            $annuary->addChild('login', 'admin');
            $annuary->addChild('password', 'password');
            $annuary->addChild('ssl', 'false');

            $res = formatXml($loadedXml);
            $fp = fopen($path, "w+");
            if ($fp) {
                fwrite($fp, $res);
            }
            $migrated++;
        }
    }
}

foreach ($nonReadableFiles as $file) {
    printf("The file %s it is not readable or not writable.\n", $file);
}

printf($migrated . " custom(s) avec m2m_config.xml trouvé(s) et migré(s).\n");

function formatXml($simpleXMLElement)
{
    $xmlDocument = new DOMDocument('1.0');
    $xmlDocument->preserveWhiteSpace = false;
    $xmlDocument->formatOutput = true;
    $xmlDocument->loadXML($simpleXMLElement->asXML());

    return $xmlDocument->saveXML();
}
