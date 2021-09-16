<?php

require '../../vendor/autoload.php';

chdir('../..');

$customs =  scandir('custom');

$tabEmail = [
    '_EMAIL_FROM_ADDRESS',
    '_EMAIL_TO_ADDRESS',
    '_EMAIL_CC_ADDRESS',
    '_EMAIL_ID',
    '_EMAIL_ACCOUNT'
];

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

    $fileFr = "apps/maarch_entreprise/lang/fr.php";
    $strFileFr = file_get_contents($fileFr , "r");

    $baseXml = simplexml_load_file($base);
    
    if ( file_exists($path)) {
        if (!is_readable($path) || !is_writable($path)) {
            continue;
        }

        
        $customXml = simplexml_load_file($path);

        $nbCustom = 0;

        $customXml->asXML("custom/{$custom}/apps/maarch_entreprise/xml/index_letterbox_backup.xml");

        $modifLabel = $customXml->xpath("//INDEX/label");

        if ($baseXml) {           
            for($cptBaseXml=0; $cptBaseXml < count($baseXml) ; $cptBaseXml++) {
                
                for($cptCustomXml=0; $cptCustomXml < count($customXml) ; $cptCustomXml++) {

                    if((string)$baseXml->INDEX[$cptBaseXml]->column == (string)$customXml->INDEX[$cptCustomXml]->column) {
                        
                        $result = array_search((string)$customXml->INDEX[$cptCustomXml]->label, $tabEmail);
                        if (false!==$result) {
                            

                            $machaine = $tabEmail[$result];

                            $chaineDebut = "define('$machaine', '";
                            $chaineFin = "');";

                            $sub  = substr($strFileFr, strpos($strFileFr,$chaineDebut)+strlen($chaineDebut),strlen($strFileFr));
                            $machainee = substr($sub,0,strpos($sub,$chaineFin));

                            dom_import_simplexml($modifLabel[$cptCustomXml])->nodeValue=$machainee;

                            
                            
                        }

                        $customXml->INDEX[$cptCustomXml]->addChild('enabled', 'true');

                        $domToChange = dom_import_simplexml($baseXml->INDEX[$cptBaseXml]);
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

