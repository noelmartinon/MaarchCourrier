<?php

require '../../vendor/autoload.php';

chdir('../..');

// /!\ If nature label is set in a language file, only the 'fr' label will be migrated
require 'apps/maarch_entreprise/lang/fr.php';

const DEFAULT_NATURES = [
    'simple_mail',
    'email',
    'fax',
    'chronopost',
    'fedex',
    'registered_mail',
    'courier',
    'message_exchange',
    'other'
];

$migrated = 0;
$customs =  scandir('custom');
foreach ($customs as $custom) {
    if ($custom == 'custom.xml' || $custom == '.' || $custom == '..') {
        continue;
    }

    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $custom]);

    if (file_exists("custom/$custom/apps/maarch_entreprise/lang/fr.php")) {
        require "custom/$custom/apps/maarch_entreprise/lang/fr.php";
    }

    $xmlfile = null;
    $path = "custom/{$custom}/apps/maarch_entreprise/xml/entreprise.xml";
    if (!file_exists($path)) {
        continue;
    }
    if (!is_readable($path)) {
        printf("The file $path it is not readable or not writable.\n");
        continue;
    }

    $xmlfile = simplexml_load_file($path);

    if ($xmlfile) {
        if (!empty($xmlfile->mail_natures->nature)) {
            $natureCustomFieldId = 1; // Custom field 'Nature' is created with id 1 during migration
            $natureCustomField = \CustomField\models\CustomFieldModel::getById(['id' => $natureCustomFieldId]);
            $natureCustomField['values'] = json_decode($natureCustomField['values'], true);
            foreach ($xmlfile->mail_natures->nature as $nature) {
                if (in_array($nature->id, DEFAULT_NATURES)) {
                    continue;
                }
                $natureLabel = (string)$nature->label;
                if (!empty($natureLabel) && defined($natureLabel) && constant($natureLabel) != null) {
                    $natureLabel = constant($natureLabel);
                }
                $natureCustomField['values'][] = $natureLabel;

                $natureLabel = json_encode($natureLabel);
                $natureLabel = str_replace("'", "''", $natureLabel);

                \Resource\models\ResModel::update([
                    'postSet'   => ['custom_fields' => "jsonb_set(custom_fields, '{{$natureCustomFieldId}}', '{$natureLabel}')"],
                    'where'     => ['res_id in (select res_id from mlb_coll_ext where nature_id = ?)'],
                    'data'      => [(string)$nature->id]
                ]);
                $migrated++;
            }
            \CustomField\models\CustomFieldModel::update([
                'set'   => ['values' => json_encode($natureCustomField['values'])],
                'where' => ['id = ?'],
                'data'  => [$natureCustomFieldId]
            ]);
        }
    }
    printf("Migration nature personnalisé (CUSTOM {$custom}) : " . $migrated . " nature(s) personnalisé(s) migré(s).\n");
}

