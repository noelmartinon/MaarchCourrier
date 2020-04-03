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
 */

namespace Template\controllers;

use ContentManagement\controllers\MergeController;
use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use Group\controllers\PrivilegeController;
use History\controllers\HistoryController;
use Resource\controllers\ResController;
use Resource\models\ResModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;
use Template\models\TemplateAssociationModel;
use Template\models\TemplateModel;
use Attachment\models\AttachmentModel;
use Entity\models\EntityModel;
use User\models\UserModel;

class TemplateController
{
    const AUTHORIZED_MIMETYPES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml‌.slideshow',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.spreadsheet'
    ];

    public function get(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_templates', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $templates = TemplateModel::get();

        return $response->withJson(['templates' => $templates]);
    }

    public function getDetailledById(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_templates', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $template = TemplateModel::getById(['id' => $aArgs['id']]);
        if (empty($template)) {
            return $response->withStatus(400)->withJson(['errors' => 'Template does not exist']);
        }

        $rawLinkedEntities = TemplateAssociationModel::get(['select' => ['value_field'], 'where' => ['template_id = ?'], 'data' => [$template['template_id']]]);
        $linkedEntities = [];
        foreach ($rawLinkedEntities as $rawLinkedEntity) {
            $linkedEntities[] = $rawLinkedEntity['value_field'];
        }
        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => 'superadmin']);
        foreach ($entities as $key => $entity) {
            $entities[$key]['state']['selected'] = false;
            if (in_array($entity['id'], $linkedEntities)) {
                $entities[$key]['state']['selected'] = true;
            }
        }

        $attachmentModelsTmp = AttachmentModel::getAttachmentsTypesByXML();
        $attachmentTypes = [];
        foreach ($attachmentModelsTmp as $key => $value) {
            if ($value['show']) {
                $attachmentTypes[] = [
                    'label' => $value['label'],
                    'id'    => $key
                ];
            }
        }

        return $response->withJson([
            'template'          => $template,
            'templatesModels'   => TemplateModel::getModels(),
            'attachmentTypes'   => $attachmentTypes,
            'datasources'       => TemplateModel::getDatasources(),
            'entities'          => $entities
        ]);
    }

    public function create(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_templates', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $data = $request->getParams();
        if (!TemplateController::checkData(['data' => $data])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if ($data['template_type'] == 'OFFICE_HTML' && !$data['jnlpUniqueId'] && !$data['uploadedFile'] && !$data['template_content']) {
            return $response->withStatus(400)->withJson(['errors' => 'You must complete at least one of the two templates']);
        }

        if ($data['template_target'] == 'acknowledgementReceipt' && !empty($data['entities'])) {
            $checkEntities = TemplateModel::checkEntities(['data' => $data]);
            
            if (!empty($checkEntities)) {
                return $response->withJson(['checkEntities' => $checkEntities]);
            }
        }

        if ($data['template_type'] == 'OFFICE' || ($data['template_type'] == 'OFFICE_HTML' && ($data['jnlpUniqueId'] || $data['uploadedFile']))) {
            if (empty($data['jnlpUniqueId']) && empty($data['uploadedFile'])) {
                return $response->withStatus(400)->withJson(['errors' => 'Template file is missing']);
            }
            if (!empty($data['jnlpUniqueId'])) {
                if (!Validator::stringType()->notEmpty()->validate($data['template_style'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Template style is missing']);
                }
                $explodeStyle = explode(':', $data['template_style']);
                $fileOnTmp = "tmp_file_{$GLOBALS['id']}_{$data['jnlpUniqueId']}." . strtolower($explodeStyle[0]);
            } else {
                if (empty($data['uploadedFile']['base64']) || empty($data['uploadedFile']['name'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Uploaded file is missing']);
                }
                $fileContent = base64_decode($data['uploadedFile']['base64']);
                $finfo    = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($fileContent);
                if (!in_array($mimeType, self::AUTHORIZED_MIMETYPES)) {
                    return $response->withStatus(400)->withJson(['errors' => _WRONG_FILE_TYPE]);
                }

                $fileOnTmp = rand() . $data['uploadedFile']['name'];
                $file = fopen(CoreConfigModel::getTmpPath() . $fileOnTmp, 'w');
                fwrite($file, $fileContent);
                fclose($file);
            }

            $resource = file_get_contents(CoreConfigModel::getTmpPath() . $fileOnTmp);
            $pathInfo = pathinfo(CoreConfigModel::getTmpPath() . $fileOnTmp);
            $storeResult = DocserverController::storeResourceOnDocServer([
                'collId'            => 'templates',
                'docserverTypeId'   => 'TEMPLATES',
                'encodedResource'   => base64_encode($resource),
                'format'            => $pathInfo['extension']
            ]);
            if (!empty($storeResult['errors'])) {
                return $response->withStatus(500)->withJson(['errors' => '[storeResource] ' . $storeResult['errors']]);
            }

            $data['template_path'] = $storeResult['destination_dir'];
            $data['template_file_name'] = $storeResult['file_destination_name'];
        }

        $id = TemplateModel::create($data);
        if (!empty($data['entities']) && is_array($data['entities'])) {
            foreach ($data['entities'] as $entity) {
                TemplateAssociationModel::create(['templateId' => $id, 'entityId' => $entity]);
            }
        }

        HistoryController::add([
            'tableName' => 'templates',
            'recordId'  => $id,
            'eventType' => 'ADD',
            'info'      => _TEMPLATE_ADDED . " : {$data['template_label']}",
            'moduleId'  => 'template',
            'eventId'   => 'templateCreation',
        ]);

        return $response->withJson(['template' => $id]);
    }

    public function update(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_templates', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $template = TemplateModel::getById(['select' => ['template_style', 'template_file_name', 'template_type', 'template_target'], 'id' => $aArgs['id']]);
        if (empty($template)) {
            return $response->withStatus(400)->withJson(['errors' => 'Template does not exist']);
        }

        $data = $request->getParams();
        $data['template_type'] = $template['template_type'];
        $data['template_target'] = $template['template_target'];
        $data['template_id'] = $aArgs['id'];

        if (!TemplateController::checkData(['data' => $data])) {
            return $response->withStatus(400)->withJson(['errors' => 'Bad Request']);
        }

        if ($data['template_type'] == 'OFFICE_HTML' && empty($data['jnlpUniqueId']) && empty($data['uploadedFile']) && empty($data['template_content']) && empty($template['template_file_name'])) {
            return $response->withStatus(400)->withJson(['errors' => 'You must complete at least one of the two templates']);
        }

        if ($data['template_target'] == 'acknowledgementReceipt' && !empty($data['entities'])) {
            $checkEntities = TemplateModel::checkEntities(['data' => $data]);
            
            if (!empty($checkEntities)) {
                return $response->withJson(['checkEntities' => $checkEntities]);
            }
        }

        if (($data['template_type'] == 'OFFICE' || $data['template_type'] == 'OFFICE_HTML') && (!empty($data['jnlpUniqueId']) || !empty($data['uploadedFile']))) {
            if (!empty($data['jnlpUniqueId'])) {
                if (!empty($data['template_style'])) {
                    $explodeStyle = explode(':', $data['template_style']);
                    $fileOnTmp = "tmp_file_{$GLOBALS['id']}_{$data['jnlpUniqueId']}." . strtolower($explodeStyle[0]);
                } elseif (!empty($data['template_file_name'])) {
                    $explodeStyle = explode('.', $data['template_file_name']);
                    $fileOnTmp = "tmp_file_{$GLOBALS['id']}_{$data['jnlpUniqueId']}." . strtolower($explodeStyle[count($explodeStyle) - 1]);
                }
            } else {
                if (empty($data['uploadedFile']['base64']) || empty($data['uploadedFile']['name'])) {
                    return $response->withStatus(400)->withJson(['errors' => 'Uploaded file is missing']);
                }
                $fileContent = base64_decode($data['uploadedFile']['base64']);
                $finfo    = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($fileContent);
                if (!in_array($mimeType, self::AUTHORIZED_MIMETYPES)) {
                    return $response->withStatus(400)->withJson(['errors' => _WRONG_FILE_TYPE]);
                }

                $fileOnTmp = rand() . $data['uploadedFile']['name'];
                $file = fopen(CoreConfigModel::getTmpPath() . $fileOnTmp, 'w');
                fwrite($file, $fileContent);
                fclose($file);
            }

            $resource = file_get_contents(CoreConfigModel::getTmpPath() . $fileOnTmp);
            $pathInfo = pathinfo(CoreConfigModel::getTmpPath() . $fileOnTmp);
            $storeResult = DocserverController::storeResourceOnDocServer([
                'collId'            => 'templates',
                'docserverTypeId'   => 'TEMPLATES',
                'encodedResource'   => base64_encode($resource),
                'format'            => $pathInfo['extension']
            ]);
            if (!empty($storeResult['errors'])) {
                return $response->withStatus(500)->withJson(['errors' => '[storeResource] ' . $storeResult['errors']]);
            }

            $data['template_path'] = $storeResult['destination_dir'];
            $data['template_file_name'] = $storeResult['file_destination_name'];
        }

        TemplateAssociationModel::delete(['where' => ['template_id = ?'], 'data' => [$aArgs['id']]]);
        if (!empty($data['entities']) && is_array($data['entities'])) {
            foreach ($data['entities'] as $entity) {
                TemplateAssociationModel::create(['templateId' => $aArgs['id'], 'entityId' => $entity]);
            }
        }
        unset($data['uploadedFile']);
        unset($data['jnlpUniqueId']);
        unset($data['entities']);
        TemplateModel::update(['set' => $data, 'where' => ['template_id = ?'], 'data' => [$aArgs['id']]]);

        HistoryController::add([
            'tableName' => 'templates',
            'recordId'  => $aArgs['id'],
            'eventType' => 'UP',
            'info'      => _TEMPLATE_UPDATED . " : {$data['template_label']}",
            'moduleId'  => 'template',
            'eventId'   => 'templateModification',
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function delete(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_templates', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $template = TemplateModel::getById(['select' => ['template_label'], 'id' => $aArgs['id']]);
        if (empty($template)) {
            return $response->withStatus(400)->withJson(['errors' => 'Template does not exist']);
        }

        TemplateModel::delete(['where' => ['template_id = ?'], 'data' => [$aArgs['id']]]);
        TemplateAssociationModel::delete(['where' => ['template_id = ?'], 'data' => [$aArgs['id']]]);

        HistoryController::add([
            'tableName' => 'templates',
            'recordId'  => $aArgs['id'],
            'eventType' => 'DEL',
            'info'      => _TEMPLATE_DELETED . " : {$template['template_label']}",
            'moduleId'  => 'template',
            'eventId'   => 'templateSuppression',
        ]);

        return $response->withJson(['success' => 'success']);
    }

    public function duplicate(Request $request, Response $response, array $aArgs)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_templates', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $template = TemplateModel::getById(['id' => $aArgs['id']]);

        if (empty($template)) {
            return $response->withStatus(400)->withJson(['errors' => 'Template not found']);
        }

        if ($template['template_target'] == 'acknowledgementReceipt') {
            return $response->withStatus(400)->withJson(['errors' => 'Forbidden duplication']);
        }

        if ($template['template_type'] == 'OFFICE') {
            $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);

            $pathOnDocserver = DocserverController::createPathOnDocServer(['path' => $docserver['path_template']]);
            $docinfo = DocserverController::getNextFileNameInDocServer(['pathOnDocserver' => $pathOnDocserver['pathToDocServer']]);
            $docinfo['fileDestinationName'] .=  '.' . explode('.', $template['template_file_name'])[1];

            $pathToDocumentToCopy = $docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $template['template_path']) . $template['template_file_name'];
            $resource = file_get_contents($pathToDocumentToCopy);

            $copyResult = DocserverController::copyOnDocServer([
                'encodedResource'       => base64_encode($resource),
                'destinationDir'        => $docinfo['destinationDir'],
                'fileDestinationName'   => $docinfo['fileDestinationName']
            ]);
            if (!empty($copyResult['errors'])) {
                return $response->withStatus(500)->withJson(['errors' => 'Template duplication failed : ' . $copyResult['errors']]);
            }
            $template['template_path'] = str_replace($docserver['path_template'], '', $docinfo['destinationDir']);
            $template['template_file_name'] = $docinfo['fileDestinationName'];
        }

        $template['template_label'] = 'Copie de ' . $template['template_label'];

        $templateId = TemplateModel::create($template);

        return $response->withJson(['id' => $templateId]);
    }

    public function initTemplates(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_templates', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $attachmentModelsTmp = AttachmentModel::getAttachmentsTypesByXML();
        $attachmentTypes = [];
        foreach ($attachmentModelsTmp as $key => $value) {
            if ($value['show']) {
                $attachmentTypes[] = [
                    'label' => $value['label'],
                    'id'    => $key
                ];
            }
        }

        $entities = EntityModel::getAllowedEntitiesByUserId(['userId' => 'superadmin']);
        foreach ($entities as $key => $entity) {
            $entities[$key]['state']['selected'] = false;
        }

        return $response->withJson([
            'templatesModels' => TemplateModel::getModels(),
            'attachmentTypes' => $attachmentTypes,
            'datasources'     => TemplateModel::getDatasources(),
            'entities'        => $entities,
        ]);
    }

    public function getByResId(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route resId is not an integer']);
        }
        if (!ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $resource = ResModel::getById(['resId' => $args['resId'], 'select' => ['destination']]);
        if (!empty($resource['destination'])) {
            $entities = [$resource['destination']];
        } else {
            $entities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['users_entities.entity_id']]);
            $entities = array_column($entities, 'entity_id');
            if (empty($entities)) {
                $entities = [0];
            }
        }
        $where = ['(templates_association.value_field in (?) OR templates_association.template_id IS NULL)', 'templates.template_type = ?', 'templates.template_target = ?'];
        $data = [$entities, 'OFFICE', 'attachments'];

        $queryParams = $request->getQueryParams();

        if (!empty($queryParams['attachmentType'])) {
            $where[] = 'templates.template_attachment_type in (?)';
            $data[] = explode(',', $queryParams['attachmentType']);
        }
        
        $templates = TemplateModel::getWithAssociation([
            'select'    => ['DISTINCT(templates.template_id)', 'templates.template_label', 'templates.template_file_name', 'templates.template_path', 'templates.template_attachment_type'],
            'where'     => $where,
            'data'      => $data,
            'orderBy'   => ['templates.template_label']
        ]);

        $docserver = DocserverModel::getCurrentDocserver(['typeId' => 'TEMPLATES', 'collId' => 'templates', 'select' => ['path_template']]);
        foreach ($templates as $key => $template) {
            $explodeFile = explode('.', $template['template_file_name']);
            $ext = $explodeFile[count($explodeFile) - 1];
            $exists = is_file($docserver['path_template'] . str_replace('#', DIRECTORY_SEPARATOR, $template['template_path']) . $template['template_file_name']);

            $templates[$key] = [
                'id'                => $template['template_id'],
                'label'             => $template['template_label'],
                'extension'         => $ext,
                'exists'            => $exists,
                'attachmentType'    => $template['template_attachment_type']
            ];
        }

        return $response->withJson(['templates' => $templates]);
    }

    public function getEmailTemplatesByResId(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['resId']) || !ResController::hasRightByResId(['resId' => [$args['resId']], 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Document out of perimeter']);
        }

        $resource = ResModel::getById(['resId' => $args['resId'], 'select' => ['destination']]);
        if (!empty($resource['destination'])) {
            $entities = [$resource['destination']];
        } else {
            $entities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['users_entities.entity_id']]);
            $entities = array_column($entities, 'entity_id');
            if (empty($entities)) {
                $entities = [0];
            }
        }
        $where = ['(templates_association.value_field in (?) OR templates_association.template_id IS NULL)', 'templates.template_type = ?', 'templates.template_target = ?'];
        $data = [$entities, 'HTML', 'sendmail'];

        $templates = TemplateModel::getWithAssociation([
            'select'    => ['DISTINCT(templates.template_id)', 'templates.template_label'],
            'where'     => $where,
            'data'      => $data,
            'orderBy'   => ['templates.template_label']
        ]);

        foreach ($templates as $key => $template) {
            $templates[$key] = [
                'id'                => $template['template_id'],
                'label'             => $template['template_label']
            ];
        }

        return $response->withJson(['templates' => $templates]);
    }

    public static function mergeEmailTemplate(Request $request, Response $response, array $args)
    {
        if (!Validator::intVal()->validate($args['id'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Route param id is not an integer']);
        }

        $entities = UserModel::getEntitiesById(['id' => $GLOBALS['id'], 'select' => ['users_entities.entity_id']]);
        $entities = array_column($entities, 'entity_id');
        if (empty($entities)) {
            $entities = [0];
        }

        $templates = TemplateModel::getWithAssociation([
            'select'  => ['DISTINCT(templates.template_id)', 'templates.template_content'],
            'where'   => ['(templates_association.value_field in (?) OR templates_association.template_id IS NULL)', 'templates.template_type = ?', 'templates.template_target = ?', 'templates.template_id = ?'],
            'data'    => [$entities, 'HTML', 'sendmail', $args['id']],
            'orderBy' => ['templates.template_id']
        ]);

        if (empty($templates[0])) {
            return $response->withStatus(400)->withJson(['errors' => 'Template does not exist']);
        }
        $template = $templates[0];
        if (empty($template['template_content'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Template has no content']);
        }

        $body = $request->getParsedBody();

        if (!Validator::intVal()->validate($body['data']['resId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body param resId is missing']);
        }

        $dataToMerge = ['userId' => $GLOBALS['id']];
        if (!empty($body['data']) && is_array($body['data'])) {
            $dataToMerge = array_merge($dataToMerge, $body['data']);
        }
        $mergedDocument = MergeController::mergeDocument([
            'content' => $template['template_content'],
            'data'    => $dataToMerge
        ]);
        $fileContent = base64_decode($mergedDocument['encodedDocument']);

        return $response->withJson(['mergedDocument' => $fileContent]);
    }

    private static function checkData(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['data']);
        ValidatorModel::arrayType($aArgs, ['data']);

        $availableTypes = ['HTML', 'TXT', 'OFFICE', 'OFFICE_HTML'];
        $data = $aArgs['data'];

        $check = Validator::stringType()->notEmpty()->validate($data['template_label']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['template_comment']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['template_target']);
        $check = $check && Validator::stringType()->notEmpty()->validate($data['template_type']) && in_array($data['template_type'], $availableTypes);

        if ($data['template_type'] == 'HTML' || $data['template_type'] == 'TXT') {
            $check = $check && Validator::stringType()->notEmpty()->validate($data['template_content']);
        }

        if ($data['template_type'] == 'OFFICE_HTML') {
            $check = $check && Validator::stringType()->validate($data['template_content']);
            $check = $check && Validator::stringType()->notEmpty()->validate($data['template_attachment_type']);
        }

        if (!empty($data['entities'])) {
            $check = $check && Validator::arrayType()->validate($data['entities']);
        }

        return $check;
    }
}
