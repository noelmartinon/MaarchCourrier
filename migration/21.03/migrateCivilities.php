<?php

require '../../vendor/autoload.php';

chdir('../..');

$customs =  scandir('custom');
foreach ($customs as $custom) {
    if (in_array($custom, ['custom.json', 'custom.xml', '.', '..'])) {
        continue;
    }

    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $custom]);

    $migrated = 0;

    if (is_file("custom/{$custom}/apps/maarch_entreprise/xml/entreprise.xml")) {
        $path = "custom/{$customId}/apps/maarch_entreprise/xml/entreprise.xml";
    } else {
        $path = 'apps/maarch_entreprise/xml/entreprise.xml';
    }

    $loadedXml = simplexml_load_file($path);
    if (!empty($loadedXml)) {
        $result = $loadedXml->xpath('/ROOT/titles');
        foreach ($result as $title) {
            foreach ($title as $value) {
                if (!empty((string) $value->id) && !empty((string)$value->label)) {
                    $id = \Contact\models\ContactCivilityModel::create([
                        'label'         => (string)$value->label,
                        'abbreviation'  => (string)$value->abbreviation ?? ''
                    ]);

                    \Contact\models\ContactModel::update([
                        'set'   => ['civility_tmp' => $id],
                        'where' => ['civility = ?'],
                        'data'  => [(string)$value->id]
                    ]);
                    ++$migrated;
                }
            }
        }
    }

    printf("Migration civilités (CUSTOM {$custom}) : " . $migrated . " civilité(s) migrée(s).\n");
}
