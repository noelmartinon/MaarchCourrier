<?php

require '../../../vendor/autoload.php';

/**
 * Optional : when migrating, you can give a path of a mapping file to migrate contacts' M2M communication_means
 *
 * Usage : php migrateM2MConfiguration.php --privateKeyMapping ./privateKeyMapping.json
 *
 * This mapping file maps a custom id to its private key, set in the vhost, like this :
 * {
 *   "custom1": "Private key of custom 1",
 *   "custom2": "Private key of custom 2",
 *              ...
 *   "customN": "Private key of custom N",
 * }
 *
 * We have to do this because we encrypt the password when migrating, which needs the private key
 *
 * Note that this is optional and the previous config format will still work
 *
 */

$privateKeyMapping = [];
$cmd = array_search('--privateKeyMapping', $argv);
if ($cmd > 0) {
    $privateKeyMappingPath = $argv[$cmd + 1];
    if (is_file($privateKeyMappingPath) && is_readable($privateKeyMappingPath)) {
        $privateKeyMapping = file_get_contents($privateKeyMappingPath);
        $privateKeyMapping = json_decode($privateKeyMapping, true);
    }
}

chdir('../../..');

$customs = scandir('custom');

foreach ($customs as $custom) {
    if (in_array($custom, ['custom.json', 'custom.xml', '.', '..'])) {
        continue;
    }

    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $custom]);
    $GLOBALS['customId'] = $custom;

    $xmlFile = null;
    $path = "custom/{$custom}/apps/maarch_entreprise/xml/m2m_config.xml";
    if (!file_exists($path)) {
        continue;
    }

    $xmlFile = simplexml_load_file($path);
    if ($xmlFile !== false && !empty($xmlFile->m2m_communication)) {
        $communicationMeans = explode(',', $xmlFile->m2m_communication);
        $fileUpdated = false;

        foreach ($communicationMeans as $key => $communicationMean) {
            if (!filter_var($communicationMean, FILTER_VALIDATE_URL)) {
                continue;
            }

            $split = splitUrl($communicationMean);
            if ($split === false) {
                continue;
            }

            $xmlFile->m2m_login = $split['login'];
            $xmlFile->m2m_password = $split['password'];
            $communicationMeans[$key] = $split['prefix'] . rtrim($split['url'], '/');
            $fileUpdated = true;
        }

        if ($fileUpdated) {
            $xmlFile->m2m_communication = implode(',', $communicationMeans);

            $res = formatXml($xmlFile);
            $fp = fopen($path, "w+");
            if ($fp) {
                fwrite($fp, $res);
            }

            printf("Migration de la configuration M2M (CUSTOM {$custom}) : configuration migrée.\n");
        }
    }

    if (empty($privateKeyMapping[$custom])) {
        continue;
    }
    $_SERVER['MAARCH_ENCRYPT_KEY'] = $privateKeyMapping[$custom];

    $contacts = \Contact\models\ContactModel::get([
        'select' => ['id', 'communication_means'],
        'where'  => ['communication_means is not null']
    ]);

    $migrated = 0;
    foreach ($contacts as $contact) {
        $communicationMeans = json_decode($contact['communication_means'], true);
        if (empty($communicationMeans['url']) || !empty($communicationMeans['login']) && !empty($communicationMeans['password'])) {
            continue;
        }

        $split = splitUrl($communicationMeans['url']);
        if ($split === false) {
            continue;
        }

        $split['password'] = \SrcCore\models\PasswordModel::encrypt(['password' => $split['password']]);
        $split['url'] = $split['prefix'] . rtrim($split['url'], '/');
        \Contact\models\ContactModel::update([
            'set'   => ['communication_means' => json_encode(['url' => $split['url'], 'login' => $split['login'], 'password' => $split['password']])],
            'where' => ['id = ?'],
            'data'  => [$contact['id']]
        ]);
        $migrated++;
    }
    unset($_SERVER['MAARCH_ENCRYPT_KEY']);
    printf("Migration de la configuration M2M des contacts (CUSTOM {$custom}) : $migrated contact(s) migrée.\n");
}

function splitUrl($url) {
    $prefix = '';
    if (strrpos($url, "http://") !== false) {
        $prefix = "http://";
    } elseif (strrpos($url, "https://") !== false) {
        $prefix = "https://";
    }

    $url = str_replace($prefix, '', $url);
    $url = explode('@', $url);
    if (count($url) !== 2) {
        return false;
    }
    $loginPassword = $url[0];
    $url = $url[1];

    $loginPassword = explode(':', $loginPassword);
    $login = $loginPassword[0];
    $password = $loginPassword[1];

    return [
        'url'      => $url,
        'login'    => $login,
        'password' => $password,
        'prefix'   => $prefix
    ];
}

function formatXml($simpleXMLElement) {
    $xmlDocument = new \DOMDocument('1.0');
    $xmlDocument->preserveWhiteSpace = false;
    $xmlDocument->formatOutput = true;
    $xmlDocument->loadXML($simpleXMLElement->asXML());

    return $xmlDocument->saveXML();
}
