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
    $GLOBALS['customId'] = $custom;

    $language = \SrcCore\models\CoreConfigModel::getLanguage();
    if (file_exists("custom/{$custom}/src/core/lang/lang-{$language}.php")) {
        require_once("custom/{$custom}/src/core/lang/lang-{$language}.php");
    }
    require_once("src/core/lang/lang-{$language}.php");

    $migrated = 0;
    $attachmentTypesXml = [];

    $loadedXml = \SrcCore\models\CoreConfigModel::getXmlLoaded(['path' => 'apps/maarch_entreprise/xml/entreprise.xml']);
    if ($loadedXml) {
        $attachmentTypesXML = $loadedXml->attachment_types;
        if (count($attachmentTypesXML) > 0) {
            foreach ($attachmentTypesXML->type as $value) {
                $label = defined((string)$value->label) ? constant((string)$value->label) : (string)$value->label;
                $attachmentTypesXml[(string)$value->id] = [
                    'label'         => $label,
                    'icon'          => (string)$value['icon'],
                    'sign'          => (empty($value['sign']) || (string)$value['sign'] == 'true') ? 'true' : 'false',
                    'chrono'        => (empty($value['with_chrono']) || (string)$value['with_chrono'] == 'true') ? 'true' : 'false',
                    'attachInMail'  => (!empty($value['attach_in_mail']) && (string)$value['attach_in_mail'] == 'true') ? 'true' : 'false',
                    'show'          => (empty($value->attributes()->show) || (string)$value->attributes()->show == 'true') ? 'true' : 'false'
                ];
            }
        }
    }

    if (!empty($attachmentTypesXml)) {
        foreach ($attachmentTypesXml as $key => $typeXml) {
            $type = [
                'type_id'             => $key,
                'label'               => $typeXml['label'],
                'visible'             => $typeXml['show'],
                'email_link'          => $typeXml['attachInMail'],
                'signable'            => $typeXml['sign'],
                'icon'                => $typeXml['icon'],
                'chrono'              => $typeXml['chrono'],
                'version_enabled'     => 'true',
                'new_version_default' => 'true'
            ];

            \Attachment\models\AttachmentTypeModel::create($type);
            $migrated++;
        }
    }
    printf("Migration types de pièces jointes (CUSTOM {$custom}) : {$migrated} types migré(s).\n");
}
