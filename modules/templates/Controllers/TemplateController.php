<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Template Controller
* @author dev@maarch.org
* @ingroup templates
*/

namespace Templates\Controllers;

use Core\Models\DocserverModel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Templates\Models\TemplateModel;

include_once 'core/docservers_tools.php';
include_once 'core/class/docservers_controler.php';

class TemplateController
{
    public function duplicate(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        $template = TemplateModel::getById(['id' => $aArgs['id']]);

        if (empty($template)) {
            return $response->withStatus(400)->withJson(['errors' => 'Template not found']);
        }

        if ($template['template_type'] == 'OFFICE') {
            $docserver = DocserverModel::getByTypeId(['docserver_type_id' => 'TEMPLATES', 'select' => ['path_template']]);

            $pathOnDocserver = Ds_createPathOnDocServer($docserver[0]['path_template']);
            $docserverClass = new \docservers_controler();
            $docinfo = $docserverClass->getNextFileNameInDocserver($pathOnDocserver['destinationDir']);
            $docinfo['fileDestinationName'] .=  '.' . explode('.', $template['template_file_name'])[1];

            $pathToDocumentToCopy = $docserver[0]['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $template['template_path']) . $template['template_file_name'];
            $copy = Ds_copyOnDocserver($pathToDocumentToCopy, $docinfo);

            $template['template_path'] = str_replace(str_replace(DIRECTORY_SEPARATOR, '#', $docserver[0]['path_template']), '', $copy['destinationDir']);
            $template['template_file_name'] = $copy['fileDestinationName'];
        }

        $template['template_label'] = 'Copie de ' . $template['template_label'];

        $templateId = TemplateModel::create($template);

        return $response->withJson(['id' => $templateId]);
    }
}
