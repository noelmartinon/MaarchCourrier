<?php

use Docserver\models\DocserverModel;
use SrcCore\models\CoreConfigModel;
use Template\models\TemplateModel;

require '../../vendor/autoload.php';

include_once('../../vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php');

const OFFICE_EXTENSIONS = ['odt', 'ods', 'odp', 'xlsx', 'pptx', 'docx', 'odf', 'doc'];

const DATA_TO_REPLACE = [
    'res_letterbox.admission_date'     => '[res_letterbox.admission_date;frm=dd/mm/yyyy]',
    'res_letterbox.doc_date'           => '[res_letterbox.doc_date;frm=dd/mm/yyyy]',
    'res_letterbox.process_limit_date' => '[res_letterbox.process_limit_date;frm=dd/mm/yyyy]',
    'res_letterbox.closing_date'       => '[res_letterbox.closing_date;frm=dd/mm/yyyy]',
    'res_letterbox.creation_date'      => '[res_letterbox.creation_date;frm=dd/mm/yyyy]',
    'res_letterbox.departure_date'     => '[res_letterbox.departure_date;frm=dd/mm/yyyy]',
    'res_letterbox.opinion_limit_date' => '[res_letterbox.opinion_limit_date;frm=dd/mm/yyyy]',
];

chdir('../..');

$customs = scandir('custom');

foreach ($customs as $custom) {
    if (in_array($custom, ['custom.json', 'custom.xml', '.', '..'])) {
        continue;
    }

    \SrcCore\models\DatabasePDO::reset();
    new \SrcCore\models\DatabasePDO(['customId' => $custom]);

    $migrated    = 0;
    $nonMigrated = 0;

    $docserver     = DocserverModel::getByDocserverId(['docserverId' => 'TEMPLATES']);
    $templatesPath = $docserver['path_template'];
    $templates     = TemplateModel::get();

    $tmpPath = CoreConfigModel::getTmpPath();

    foreach ($templates as $template) {
        if ($template['template_type'] == 'HTML' || $template['template_type'] == 'TXT' || $template['template_type'] == 'OFFICE_HTML') {
            $content = $template['template_content'];

            $newContent = $content;
            foreach (DATA_TO_REPLACE as $key => $value) {
                $newContent = str_replace('[' . $key . ']', $value, $newContent);
            }

            if ($content != $newContent) {
                TemplateModel::update([
                    'set'   => [
                        'template_content' => $newContent
                    ],
                    'where' => ['template_id = ?'],
                    'data'  => [$template['template_id']]
                ]);
                $migrated++;
            } else {
                $nonMigrated++;
            }
        }
        if ($template['template_type'] == 'OFFICE' || $template['template_type'] == 'OFFICE_HTML') {
            if (empty($template['template_file_name']) || empty($template['template_path'])) {
                $nonMigrated++;
                echo "Erreur lors de la migration du modèle : le modèle n'a pas de fichier : {$template['template_id']}\n";
                continue;
            }

            $path = str_replace('#', '/', $template['template_path']);

            $pathToDocument = $templatesPath . $path . $template['template_file_name'];

            $pathInfo = pathinfo($pathToDocument);
            $extension = $pathInfo['extension'];

            if (!in_array($extension, OFFICE_EXTENSIONS)) {
                $nonMigrated++;
                echo "Erreur lors de la migration du modèle : le document n'est pas un document fusionnable : $pathToDocument\n";
                continue;
            }

            if (!is_writable($pathToDocument) || !is_readable($pathToDocument)) {
                $nonMigrated++;
                echo "Erreur lors de la migration du modèle : droits d'accès insuffisants : $pathToDocument\n";
                continue;
            }

            $tbs = new clsTinyButStrong();
            $tbs->NoErr = true;
            $tbs->Protect = false;
            $tbs->PlugIn(TBS_INSTALL, OPENTBS_PLUGIN);

            $tbs->LoadTemplate($pathToDocument, OPENTBS_ALREADY_UTF8);

            $pages = 1;
            if ($extension == 'xlsx') {
                $pages = $tbs->PlugIn(OPENTBS_COUNT_SHEETS);
            }

            for ($i = 0; $i < $pages; ++$i) {
                if ($extension == 'xlsx') {
                    $tbs->PlugIn(OPENTBS_SELECT_SHEET, $i + 1);
                }

                $tbs->ReplaceFields(DATA_TO_REPLACE);
            }

            if (in_array($extension, OFFICE_EXTENSIONS)) {
                $tbs->Show(OPENTBS_STRING);
            } else {
                $tbs->Show(TBS_NOTHING);
            }

            $content = base64_encode($tbs->Source);

            $result = file_put_contents($pathToDocument, base64_decode($content));
            if ($result !== false) {
                $migrated++;
            } else {
                echo "Erreur lors de la migration du modèle : $pathToDocument\n";
                $nonMigrated++;
            }
        }
    }

    printf("Migration de Modèles de documents (CUSTOM {$custom}) : " . $migrated . " Modèle(s) migré(s), $nonMigrated non migré(s).\n");
}
