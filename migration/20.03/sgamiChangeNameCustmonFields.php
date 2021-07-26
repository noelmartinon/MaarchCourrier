<?php

require '../../vendor/autoload.php';

chdir('../..');

$customs =  scandir('custom');

$base = "apps/maarch_entreprise/xml/index_letterbox.xml";
if (!file_exists($base)) {
    printf("Verifier si index_letterbox.xml existe a ce chemin : /n apps/maarch_entreprise/xml/index_letterbox.xml");
    exit;
}

foreach ($customs as $custom) {
    if ($custom == 'custom.xml' || $custom == '.' || $custom == '..') {
        continue;
    }

    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $custom]);
    
    $nbCustomField = 0;
    $newXml = [];
    $path = "custom/{$custom}/apps/maarch_entreprise/xml/index_letterbox.xml";

    if ( file_exists($path)) {
        if (!is_readable($path) || !is_writable($path)) {
            continue;
        }

        $baseXml = simplexml_load_file($base);
        $customXml = simplexml_load_file($path);

        $nbCustom = 0;

        $customXml->asXML("custom/{$custom}/apps/maarch_entreprise/xml/index_letterbox_backup.xml");
        if ($baseXml) {           
            for($cptBaseXml=0; $cptBaseXml < count($baseXml) ; $cptBaseXml++) {
                
                for($cptCustomXml=0; $cptCustomXml < count($customXml) ; $cptCustomXml++) {

                    if((string)$baseXml->INDEX[$cptBaseXml]->column == (string) $customXml->INDEX[$cptCustomXml]->column) {
                        $domToChange = dom_import_simplexml($baseXml->INDEX[$cptBaseXml] );
                        $domReplace  = dom_import_simplexml($customXml->INDEX[$cptCustomXml]);
                        $nodeImport  = $domToChange->ownerDocument->importNode($domReplace, TRUE);
                        $domToChange->parentNode->replaceChild($nodeImport, $domToChange);
                      
                        $nbCustomField++;
                        $cptCustomXml = count($customXml)+1;
                    }                    
                }
                $nbCustom++;
            }
        }
        $baseXml->asXML("custom/{$custom}/apps/maarch_entreprise/xml/index_letterbox.xml");
        printf("Migration Champs Custom (CUSTOM {$custom}) : " . $nbCustomField . " ont été modifié dans index_letterbox.\n");
    }else{
        $baseXml->asXML("custom/{$custom}/apps/maarch_entreprise/xml/index_letterbox.xml");
    }
}