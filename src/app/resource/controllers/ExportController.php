<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Export Controller
* @author dev@maarch.org
*/

namespace Resource\controllers;

use Attachment\models\AttachmentModel;
use Basket\models\BasketModel;
use Contact\models\ContactModel;
use Entity\models\EntityModel;
use Entity\models\ListInstanceModel;
use Resource\models\ExportTemplateModel;
use Resource\models\ResModel;
use Resource\models\ResourceContactModel;
use Resource\models\ResourceListModel;
use Respect\Validation\Validator;
use setasign\Fpdi\Tcpdf\Fpdi;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\AutoCompleteController;
use SrcCore\controllers\PreparedClauseController;
use SrcCore\models\DatabaseModel;
use SrcCore\models\TextFormatModel;
use SrcCore\models\ValidatorModel;
use Tag\models\TagModel;
use User\models\UserModel;

require_once 'core/class/Url.php';

class ExportController
{
    public function getExportTemplates(Request $request, Response $response)
    {
        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);

        $rawTemplates = ExportTemplateModel::getByUserId(['userId' => $currentUser['id']]);

        $templates = ['pdf' => ['data' => []], 'csv' => ['data' => []]];
        foreach ($rawTemplates as $rawTemplate) {
            if ($rawTemplate['format'] == 'pdf') {
                $templates['pdf'] = ['data' => (array)json_decode($rawTemplate['data'])];
            } elseif ($rawTemplate['format'] == 'csv') {
                $templates['csv'] = ['delimiter' => $rawTemplate['delimiter'], 'data' => (array)json_decode($rawTemplate['data'])];
            }
        }

        return $response->withJson(['templates' => $templates]);
    }

    public function updateExport(Request $request, Response $response, array $aArgs)
    {
        set_time_limit(240);
        $currentUser = UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);

        $errors = ResourceListController::listControl(['groupId' => $aArgs['groupId'], 'userId' => $aArgs['userId'], 'basketId' => $aArgs['basketId'], 'currentUserId' => $currentUser['id']]);
        if (!empty($errors['errors'])) {
            return $response->withStatus($errors['code'])->withJson(['errors' => $errors['errors']]);
        }

        $body = $request->getParsedBody();

        if (!Validator::stringType()->notEmpty()->validate($body['format']) || !in_array($body['format'], ['pdf', 'csv'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Data format is empty or not a string between [\'pdf\', \'csv\']']);
        } elseif ($body['format'] == 'csv' && (!Validator::stringType()->notEmpty()->validate($body['delimiter']) || !in_array($body['delimiter'], [',', ';', 'TAB']))) {
            return $response->withStatus(400)->withJson(['errors' => 'Delimiter is empty or not a string between [\',\', \';\', \'TAB\']']);
        } elseif (!Validator::arrayType()->notEmpty()->validate($body['data'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Data data is empty or not an array']);
        } elseif (!Validator::arrayType()->notEmpty()->validate($body['resources'])) {
            return $response->withStatus(403)->withJson(['errors' => 'Data resources is empty or not an array']);
        }

        foreach ($body['data'] as $value) {
            if (!isset($value['value']) || !Validator::stringType()->notEmpty()->validate($value['label']) || !Validator::boolType()->validate($value['isFunction'])) {
                return $response->withStatus(400)->withJson(['errors' => 'One data is not set well']);
            }
        }

        $basket = BasketModel::getById(['id' => $aArgs['basketId'], 'select' => ['basket_clause', 'basket_res_order', 'basket_name']]);
        $user   = UserModel::getById(['id' => $aArgs['userId'], 'select' => ['user_id']]);

        $whereClause = PreparedClauseController::getPreparedClause(['clause' => $basket['basket_clause'], 'login' => $user['user_id']]);
        $rawResourcesInBasket = ResModel::getOnView([
            'select'    => ['res_id'],
            'where'     => [$whereClause, 'res_view_letterbox.res_id in (?)'],
            'data'      => [$body['resources']]
        ]);
        $allResourcesInBasket = [];
        foreach ($rawResourcesInBasket as $resource) {
            $allResourcesInBasket[] = $resource['res_id'];
        }

        $order = 'CASE res_view_letterbox.res_id ';
        foreach ($body['resources'] as $key => $resId) {
            if (!in_array($resId, $allResourcesInBasket)) {
                return $response->withStatus(403)->withJson(['errors' => 'Resources out of perimeter']);
            }
            $order .= "WHEN {$resId} THEN {$key} ";
        }
        $order .= 'END';

        $template = ExportTemplateModel::get(['select' => [1], 'where' => ['user_id = ?', 'format = ?'], 'data' => [$currentUser['id'], $body['format']]]);
        if (empty($template)) {
            ExportTemplateModel::create([
                'userId'    => $currentUser['id'],
                'format'    => $body['format'],
                'delimiter' => empty($body['delimiter']) ? null : $body['delimiter'],
                'data'      => json_encode($body['data'])
            ]);
        } else {
            ExportTemplateModel::update([
                'set'   => [
                    'delimiter' => empty($body['delimiter']) ? null : $body['delimiter'],
                    'data'      => json_encode($body['data'])
                ],
                'where' => ['user_id = ?', 'format = ?'],
                'data'  => [$currentUser['id'], $body['format']]
            ]);
        }

        $select           = ['res_id'];
        $tableFunction    = [];
        $leftJoinFunction = [];
        $csvHead          = [];
        foreach ($body['data'] as $value) {
            $csvHead[] = $value['label'];
            if (empty($value['value'])) {
                continue;
            }
            if ($value['isFunction']) {
                if ($value['value'] == 'getStatus') {
                    $select[] = 'status.label_status AS "status.label_status"';
                    $tableFunction[] = 'status';
                    $leftJoinFunction[] = 'res_view_letterbox.status = status.id';
                } elseif ($value['value'] == 'getPriority') {
                    $select[] = 'priorities.label AS "priorities.label"';
                    $tableFunction[] = 'priorities';
                    $leftJoinFunction[] = 'res_view_letterbox.priority = priorities.id';
                } elseif ($value['value'] == 'getParentFolder') {
                    $select[] = 'folders.folder_name AS "folders.folder_name"';
                    $tableFunction[] = 'folders';
                    $leftJoinFunction[] = 'res_view_letterbox.fold_parent_id = folders.folders_system_id';
                } elseif ($value['value'] == 'getCategory') {
                    $select[] = 'res_view_letterbox.category_id';
                } elseif ($value['value'] == 'getNature') {
                    $select[] = 'res_view_letterbox.nature_id';
                } elseif ($value['value'] == 'getInitiatorEntity') {
                    $select[] = 'enone.short_label AS "enone.short_label"';
                    $tableFunction[] = 'entities enone';
                    $leftJoinFunction[] = 'res_view_letterbox.initiator = enone.entity_id';
                } elseif ($value['value'] == 'getDestinationEntity') {
                    $select[] = 'entwo.short_label AS "entwo.short_label"';
                    $tableFunction[] = 'entities entwo';
                    $leftJoinFunction[] = 'res_view_letterbox.destination = entwo.entity_id';
                } elseif ($value['value'] == 'getDestinationEntityType') {
                    $select[] = 'enthree.entity_type AS "enthree.entity_type"';
                    $tableFunction[] = 'entities enthree';
                    $leftJoinFunction[] = 'res_view_letterbox.destination = enthree.entity_id';
                } elseif ($value['value'] == 'getTypist') {
                    $select[] = 'res_view_letterbox.typist';
                } elseif ($value['value'] == 'getAssignee') {
                    $select[] = 'res_view_letterbox.dest_user';
                } elseif ($value['value'] == 'getDepartment') {
                    $select[] = 'res_view_letterbox.department_number_id';
                }
            } else {
                $select[] = "res_view_letterbox.{$value['value']}";
            }
        }

        $aChunkedResources = array_chunk($body['resources'], 10000);
        $resources = [];
        foreach ($aChunkedResources as $chunkedResource) {
            $resourcesTmp = ResourceListModel::getOnView([
                'select'    => $select,
                'table'     => $tableFunction,
                'leftJoin'  => $leftJoinFunction,
                'where'     => ['res_view_letterbox.res_id in (?)'],
                'data'      => [$chunkedResource],
                'orderBy'   => [$order]
            ]);
            $resources = array_merge($resources, $resourcesTmp);
        }

        if ($body['format'] == 'csv') {
            $file = ExportController::getCsv(['delimiter' => $body['delimiter'], 'data' => $body['data'], 'resources' => $resources, 'chunkedResIds' => $aChunkedResources]);
            $response->write(stream_get_contents($file));
            $response = $response->withAddedHeader('Content-Disposition', 'attachment; filename=export_maarch.csv');
            $contentType = 'application/vnd.ms-excel';
            fclose($file);
        } else {
            $pdf = ExportController::getPdf(['data' => $body['data'], 'resources' => $resources, 'chunkedResIds' => $aChunkedResources]);

            $fileContent    = $pdf->Output('', 'S');
            $finfo          = new \finfo(FILEINFO_MIME_TYPE);
            $contentType    = $finfo->buffer($fileContent);

            $response->write($fileContent);
            $response = $response->withAddedHeader('Content-Disposition', "inline; filename=maarch.pdf");
        }

        return $response->withHeader('Content-Type', $contentType);
    }

    private static function getCsv(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['delimiter', 'data', 'resources', 'chunkedResIds']);
        ValidatorModel::stringType($aArgs, ['delimiter']);
        ValidatorModel::arrayType($aArgs, ['data', 'resources', 'chunkedResIds']);

        $file = fopen('php://temp', 'w');
        $delimiter = ($aArgs['delimiter'] == 'TAB' ? "\t" : $aArgs['delimiter']);

        $csvHead = [];
        foreach ($aArgs['data'] as $value) {
            $decoded = utf8_decode($value['label']);
            $csvHead[] = $decoded;
        }

        fputcsv($file, $csvHead, $delimiter);

        foreach ($aArgs['resources'] as $resource) {
            $csvContent = [];
            foreach ($aArgs['data'] as $value) {
                if (empty($value['value'])) {
                    $csvContent[] = '';
                    continue;
                }
                if ($value['isFunction']) {
                    if ($value['value'] == 'getStatus') {
                        $csvContent[] = $resource['status.label_status'];
                    } elseif ($value['value'] == 'getPriority') {
                        $csvContent[] = $resource['priorities.label'];
                    } elseif ($value['value'] == 'getCopies') {
                        $copies       = ExportController::getCopies(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        $csvContent[] = empty($copies[$resource['res_id']]) ? '' : $copies[$resource['res_id']];
                    } elseif ($value['value'] == 'getDetailLink') {
                        $csvContent[] = str_replace('rest/', "apps/maarch_entreprise/index.php?page=details&dir=indexing_searching&id={$resource['res_id']}", \Url::coreurl());
                    } elseif ($value['value'] == 'getParentFolder') {
                        $csvContent[] = $resource['folders.folder_name'];
                    } elseif ($value['value'] == 'getCategory') {
                        $csvContent[] = ResModel::getCategoryLabel(['categoryId' => $resource['category_id']]);
                    } elseif ($value['value'] == 'getNature') {
                        $csvContent[] = ResModel::getNatureLabel(['natureId' => $resource['nature_id']]);
                    } elseif ($value['value'] == 'getInitiatorEntity') {
                        $csvContent[] = $resource['enone.short_label'];
                    } elseif ($value['value'] == 'getDestinationEntity') {
                        $csvContent[] = $resource['entwo.short_label'];
                    } elseif ($value['value'] == 'getDestinationEntityType') {
                        $csvContent[] = $resource['enthree.entity_type'];
                    } elseif ($value['value'] == 'getSenders') {
                        $senders = ExportController::getSenders(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        $aSenders = empty($senders[$resource['res_id']]) ? [] : $senders[$resource['res_id']];
                        $csvContent[] = implode("\n\n", $aSenders);
                    } elseif ($value['value'] == 'getRecipients') {
                        $recipients = ExportController::getRecipients(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        $aRecipients = empty($recipients[$resource['res_id']]) ? [] : $recipients[$resource['res_id']];
                        $csvContent[] = implode("\n\n", $aRecipients);
                    } elseif ($value['value'] == 'getTypist') {
                        $csvContent[] = UserModel::getLabelledUserById(['login' => $resource['typist']]);
                    } elseif ($value['value'] == 'getAssignee') {
                        $csvContent[] = UserModel::getLabelledUserById(['login' => $resource['dest_user']]);
                    } elseif ($value['value'] == 'getTags') {
                        $tags = ExportController::getTags(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        $csvContent[] = empty($tags[$resource['res_id']]) ? '' : $tags[$resource['res_id']];
                    } elseif ($value['value'] == 'getSignatories') {
                        $signatories[] = ExportController::getSignatories(['chunkedResIds' => $aArgs['chunkedResIds']]);

                        $sign = $signatories[$resource['res_id']];
                        if (empty($sign)) {
                            $sign = '';
                        } else if (is_array($sign)) {
                            $sign = implode(',', $sign);

                            if ($sign == 'empty') {
                                $sign = '';
                            }
                        }
                        $csvContent[] = $sign;
                    } elseif ($value['value'] == 'getSignatureDates') {
                        $signatureDates[] = ExportController::getSignatureDates(['chunkedResIds' => $aArgs['chunkedResIds']]);

                        $sign = $signatureDates[$resource['res_id']];
                        if (empty($sign)) {
                            $sign = '';
                        } else if (is_array($sign)) {
                            $sign = implode(',', $sign);

                            if ($sign == 'empty') {
                                $sign = '';
                            }
                        }

                        $csvContent[] = $sign;
                    } elseif ($value['value'] == 'getDepartment') {
                        if (!empty($resource['department_number_id'])) {
                            $csvContent[] = $resource['department_number_id'] . ' - ' . DepartmentController::getById(['id' => $resource['department_number_id']]);
                        } else {
                            $csvContent[] = '';
                        }
                    }
                } else {
                    $allDates = ['doc_date', 'departure_date', 'admission_date', 'process_limit_date', 'opinion_limit_date', 'closing_date'];
                    if (in_array($value['value'], $allDates)) {
                        $csvContent[] = TextFormatModel::formatDate($resource[$value['value']]);
                    } else {
                        $csvContent[] = $resource[$value['value']];
                    }
                }
            }

            // Decode UTF-8 strings to ISO-8859-1
            for ($i = 0; $i < count($csvContent); $i++) {
                if (is_array($csvContent[$i])) {
                    for ($j = 0; $j < count($csvContent[$i]); $j++) {
                        $csvContent[$i][$j] = utf8_decode($csvContent[$i][$j]);
                    }
                } else {
                    $csvContent[$i] = utf8_decode($csvContent[$i]);
                }
            }

            fputcsv($file, $csvContent, $delimiter);
        }

        rewind($file);

        return $file;
    }

    private static function getPdf(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['data', 'resources', 'chunkedResIds']);
        ValidatorModel::arrayType($aArgs, ['data', 'resources', 'chunkedResIds']);

        $columnsNumber = count($aArgs['data']);
        $orientation = 'P';
        if ($columnsNumber > 5) {
            $orientation = 'L';
        }

        $pdf = new Fpdi($orientation, 'pt');
        $pdf->setPrintHeader(false);

        $pdf->AddPage();
        $dimensions     = $pdf->getPageDimensions();
        $widthNoMargins = $dimensions['w'] - $dimensions['rm'] - $dimensions['lm'];
        $bottomHeight   = $dimensions['h'] - $dimensions['bm'];

        $labels = [];
        foreach ($aArgs['data'] as $value) {
            $labels[] = $value['label'];
        }

        $pdf->SetFont('', 'B', 12);
        $labelHeight = ExportController::getMaximumHeight($pdf, ['data' => $labels, 'width' => $widthNoMargins / $columnsNumber]);
        $pdf->SetFillColor(230, 230, 230);
        foreach ($aArgs['data'] as $value) {
            $pdf->MultiCell($widthNoMargins / $columnsNumber, $labelHeight, $value['label'], 1, 'L', true, 0);
        }

        $pdf->SetY($pdf->GetY() + $labelHeight);
        $pdf->SetFont('', '', 10);

        foreach ($aArgs['resources'] as $resource) {
            $content = [];
            foreach ($aArgs['data'] as $value) {
                if (empty($value['value'])) {
                    $content[] = '';
                    continue;
                }
                if ($value['isFunction']) {
                    if ($value['value'] == 'getStatus') {
                        $content[] = $resource['status.label_status'];
                    } elseif ($value['value'] == 'getPriority') {
                        $content[] = $resource['priorities.label'];
                    } elseif ($value['value'] == 'getCopies') {
                        $copies    = ExportController::getCopies(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        $content[] = empty($copies[$resource['res_id']]) ? '' : $copies[$resource['res_id']];
                    } elseif ($value['value'] == 'getDetailLink') {
                        $content[] = str_replace('rest/', "apps/maarch_entreprise/index.php?page=details&dir=indexing_searching&id={$resource['res_id']}", \Url::coreurl());
                    } elseif ($value['value'] == 'getParentFolder') {
                        $content[] = $resource['folders.folder_name'];
                    } elseif ($value['value'] == 'getCategory') {
                        $content[] = ResModel::getCategoryLabel(['categoryId' => $resource['category_id']]);
                    } elseif ($value['value'] == 'getNature') {
                        $content[] = ResModel::getNatureLabel(['natureId' => $resource['nature_id']]);
                    } elseif ($value['value'] == 'getInitiatorEntity') {
                        $content[] = $resource['enone.short_label'];
                    } elseif ($value['value'] == 'getDestinationEntity') {
                        $content[] = $resource['entwo.short_label'];
                    } elseif ($value['value'] == 'getDestinationEntityType') {
                        $content[] = $resource['enthree.entity_type'];
                    } elseif ($value['value'] == 'getSenders') {
                        $senders = ExportController::getSenders(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        if (!empty($senders[$resource['res_id']]) && count($senders[$resource['res_id']]) > 2) {
                            $content[] = count($senders[$resource['res_id']]) . ' ' . _CONTACTS;
                        } else {
                            $aSenders = empty($senders[$resource['res_id']]) ? [] : $senders[$resource['res_id']];
                            $content[] = implode("\n\n", $aSenders);
                        }
                    } elseif ($value['value'] == 'getRecipients') {
                        $recipients = ExportController::getRecipients(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        if (!empty($recipients[$resource['res_id']]) && count($recipients[$resource['res_id']]) > 2) {
                            $content[] = count($recipients[$resource['res_id']]) . ' ' . _CONTACTS;
                        } else {
                            $aRecipients = empty($recipients[$resource['res_id']]) ? [] : $recipients[$resource['res_id']];
                            $content[] = implode("\n\n", $aRecipients);
                        }
                    } elseif ($value['value'] == 'getTypist') {
                        $content[] = UserModel::getLabelledUserById(['login' => $resource['typist']]);
                    } elseif ($value['value'] == 'getAssignee') {
                        $content[] = UserModel::getLabelledUserById(['login' => $resource['dest_user']]);
                    } elseif ($value['value'] == 'getTags') {
                        $tags = ExportController::getTags(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        $content[] = empty($tags[$resource['res_id']]) ? '' : $tags[$resource['res_id']];
                    } elseif ($value['value'] == 'getSignatories') {
                        $signatories[] = ExportController::getSignatories(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        $content[] = empty($signatories[$resource['res_id']]) ? '' : $signatories[$resource['res_id']];
                    } elseif ($value['value'] == 'getSignatureDates') {
                        $signatureDates[] = ExportController::getSignatureDates(['chunkedResIds' => $aArgs['chunkedResIds']]);
                        $content[]        = empty($signatureDates[$resource['res_id']]) ? '' : $signatureDates[$resource['res_id']];
                    } elseif ($value['value'] == 'getDepartment') {
                        if (!empty($resource['department_number_id'])) {
                            $content[] = $resource['department_number_id'] . ' - ' . DepartmentController::getById(['id' => $resource['department_number_id']]);
                        } else {
                            $content[] = '';
                        }
                    }
                } else {
                    $allDates = ['doc_date', 'departure_date', 'admission_date', 'process_limit_date', 'opinion_limit_date', 'closing_date'];
                    if (in_array($value['value'], $allDates)) {
                        $content[] = TextFormatModel::formatDate($resource[$value['value']]);
                    } else {
                        $content[] = $resource[$value['value']];
                    }
                }
            }
            if (!empty($contentHeight)) {
                $pdf->SetY($pdf->GetY() + $contentHeight);
            }
            $contentHeight = ExportController::getMaximumHeight($pdf, ['data' => $content, 'width' => $widthNoMargins / $columnsNumber]);
            if (($pdf->GetY() + $contentHeight) > $bottomHeight) {
                $pdf->AddPage();
            }
            foreach ($content as $value) {
                $pdf->MultiCell($widthNoMargins / $columnsNumber, $contentHeight, $value, 1, 'L', false, 0);
            }
        }

        return $pdf;
    }

    private static function getCopies(array $args)
    {
        ValidatorModel::notEmpty($args, ['chunkedResIds']);
        ValidatorModel::arrayType($args, ['chunkedResIds']);

        static $aCopies = [];
        if (!empty($aCopies)) {
            return $aCopies;
        }

        foreach ($args['chunkedResIds'] as $resIds) {
            $listInstances = ListInstanceModel::get([
                'select'    => ['item_id', 'item_type', 'res_id'],
                'where'     => ['res_id in (?)', 'difflist_type = ?', 'item_mode = ?'],
                'data'      => [$resIds, 'entity_id', 'cc'],
                'order_by'  => ['res_id']
            ]);

            $resId = '';
            $copies = '';
            if (!empty($listInstances)) {
                foreach ($listInstances as $key => $listInstance) {
                    if ($key != 0 && $resId == $listInstance['res_id']) {
                        $copies .= "\n";
                    } elseif ($key != 0 && $resId != $listInstance['res_id']) {
                        $aCopies[$resId] = $copies;
                        $copies = '';
                    } else {
                        $copies = '';
                    }
                    if ($listInstance['item_type'] == 'user_id') {
                        $copies .= UserModel::getLabelledUserById(['login' => $listInstance['item_id']]);
                    } elseif ($listInstance['item_type'] == 'entity_id') {
                        $entity = EntityModel::getByEntityId(['entityId' => $listInstance['item_id'], 'select' => ['short_label']]);
                        $copies .= $entity['short_label'];
                    }
                    $resId = $listInstance['res_id'];
                }
                $aCopies[$resId] = $copies;
            }
        }

        if (empty($aCopies)) {
            $aCopies = ['empty'];
        }
        return $aCopies;
    }

    private static function getTags(array $args)
    {
        ValidatorModel::notEmpty($args, ['chunkedResIds']);
        ValidatorModel::arrayType($args, ['chunkedResIds']);

        static $aTags = [];
        if (!empty($aTags)) {
            return $aTags;
        }

        foreach ($args['chunkedResIds'] as $resIds) {
            $tagsRes = TagModel::getTagRes([
                'select'    => ['tag_id', 'res_id'],
                'where'     => ['res_id in (?)'],
                'data'      => [$resIds],
                'order_by'  => ['res_id']
            ]);

            if (!empty($tagsRes)) {
                $resId = '';
                foreach ($tagsRes as $key => $value) {
                    if ($key != 0 && $resId == $value['res_id']) {
                        $tags .= "\n";
                    } elseif ($key != 0 && $resId != $value['res_id']) {
                        $aTags[$resId] = $tags;
                        $tags = '';
                    } else {
                        $tags = '';
                    }
                    $tag = TagModel::getById(['id' => $value['tag_id'], 'select' => ['tag_label']]);
                    $tags .= $tag['tag_label'];
                    $resId = $value['res_id'];
                }
                $aTags[$value['res_id']] = $tags;
            }
        }

        if (empty($aTags)) {
            $aTags = ['empty'];
        }
        return $aTags;
    }

    private static function getSignatories(array $args)
    {
        ValidatorModel::notEmpty($args, ['chunkedResIds']);
        ValidatorModel::arrayType($args, ['chunkedResIds']);

        static $aSignatories = [];
        if (!empty($aSignatories)) {
            return $aSignatories;
        }

        foreach ($args['chunkedResIds'] as $resIds) {
            $listInstances = ListInstanceModel::get([
                'select'    => ['item_id', 'res_id'],
                'where'     => ['res_id in (?)', 'item_type = ?', 'signatory = ?'],
                'data'      => [$resIds, 'user_id', true],
                'order_by'   => ['res_id']
            ]);

            if (!empty($listInstances)) {
                $resId = '';
                foreach ($listInstances as $key => $listInstance) {
                    if ($key != 0 && $resId == $listInstance['res_id']) {
                        $signatories .= "\n";
                    } elseif ($key != 0 && $resId != $listInstance['res_id']) {
                        $aSignatories[$resId] = $signatories;
                        $signatories = '';
                    } else {
                        $signatories = '';
                    }
                    $user = UserModel::getByLogin(['login' => $listInstance['item_id'], 'select' => ['firstname', 'lastname']]);
                    $signatories .= "{$user['firstname']} {$user['lastname']}";
                    $resId = $listInstance['res_id'];
                }
                $aSignatories[$listInstance['res_id']] = $signatories;
            }
        }

        if (empty($aSignatories)) {
            $aSignatories = ['empty'];
        }
        return $aSignatories;
    }

    private static function getSignatureDates(array $args)
    {
        ValidatorModel::notEmpty($args, ['chunkedResIds']);
        ValidatorModel::arrayType($args, ['chunkedResIds']);

        static $aSignatureDates = [];
        if (!empty($aSignatureDates)) {
            return $aSignatureDates;
        }

        foreach ($args['chunkedResIds'] as $resIds) {
            $attachments = AttachmentModel::getOnView([
                'select'    => ['creation_date', 'res_id'],
                'where'     => ['res_id in (?)', 'attachment_type = ?', 'status = ?'],
                'data'      => [$resIds, 'signed_response', 'TRA'],
                'order_by'  => ['res_id']
            ]);

            if (!empty($attachments)) {
                $resId = '';
                foreach ($attachments as $key => $attachment) {
                    if ($key != 0 && $resId == $attachment['res_id']) {
                        $dates .= "\n";
                    } elseif ($key != 0 && $resId != $attachment['res_id']) {
                        $aSignatureDates[$resId] = $dates;
                        $dates = '';
                    } else {
                        $dates = '';
                    }
                    $date  = new \DateTime($attachment['creation_date']);
                    $dates .= $date->format('d-m-Y H:i');
                    $resId = $attachment['res_id'];
                }
                $aSignatureDates[$attachment['res_id']] = $dates;
            }
        }

        if (empty($aSignatureDates)) {
            $aSignatureDates = ['empty'];
        }
        return $aSignatureDates;
    }

    private static function getSenders(array $args)
    {
        ValidatorModel::notEmpty($args, ['chunkedResIds']);
        ValidatorModel::arrayType($args, ['chunkedResIds']);

        static $aSenders = [];
        if (!empty($aSenders)) {
            return $aSenders;
        }

        foreach ($args['chunkedResIds'] as $resIds) {
            $exts = ResModel::getExt([
                'select' => ['category_id', 'address_id', 'exp_user_id', 'dest_user_id', 'is_multicontacts', 'res_id'],
                'where' => ['res_id in (?)'],
                'data' => [$resIds]
            ]);

            if (!empty($exts)) {
                $resId   = '';
                $senders = [];
                foreach ($exts as $key => $ext) {
                    if ($key != 0 && $resId != $ext['res_id']) {
                        $aSenders[$resId] = $senders;
                        $senders = [];
                    }
                    if (!empty($ext)) {
                        if ($ext['category_id'] == 'outgoing') {
                            $resourcesContacts = ResourceContactModel::getFormattedByResId(['resId' => $ext['res_id']]);
                            foreach ($resourcesContacts as $resourcesContact) {
                                $senders[] = $resourcesContact['format'];
                            }
                        } else {
                            $rawContacts = [];
                            if ($ext['is_multicontacts'] == 'Y') {
                                $multiContacts = DatabaseModel::select([
                                    'select'    => ['contact_id', 'address_id'],
                                    'table'     => ['contacts_res'],
                                    'where'     => ['res_id = ?', 'mode = ?'],
                                    'data'      => [$ext['res_id'], 'multi']
                                ]);
                                foreach ($multiContacts as $multiContact) {
                                    $rawContacts[] = [
                                        'login'         => $multiContact['contact_id'],
                                        'address_id'    => $multiContact['address_id'],
                                    ];
                                }
                            } else {
                                $rawContacts[] = [
                                    'login'         => $ext['dest_user_id'],
                                    'address_id'    => $ext['address_id'],
                                ];
                            }
                            foreach ($rawContacts as $rawContact) {
                                if (!empty($rawContact['address_id'])) {
                                    $contact = ContactModel::getOnView([
                                        'select' => [
                                            'is_corporate_person', 'lastname', 'firstname', 'address_num', 'address_street', 'address_town', 'address_postal_code',
                                            'ca_id', 'society', 'contact_firstname', 'contact_lastname', 'address_country'
                                        ],
                                        'where'     => ['ca_id = ?'],
                                        'data'      => [$rawContact['address_id']]
                                    ]);
                                    if (isset($contact[0])) {
                                        $contact = AutoCompleteController::getFormattedContact(['contact' => $contact[0]]);
                                        $senders[] = $contact['contact']['otherInfo'];
                                    }
                                } else {
                                    $senders[] = UserModel::getLabelledUserById(['login' => $rawContact['login']]);
                                }
                            }
                        }
                        $resId = $ext['res_id'];
                    }
                }
                $aSenders[$resId] = $senders;
            }
        }

        if (empty($aSenders)) {
            $aSenders = ['empty'];
        }

        return $aSenders;
    }

    private static function getRecipients(array $args)
    {
        ValidatorModel::notEmpty($args, ['chunkedResIds']);
        ValidatorModel::arrayType($args, ['chunkedResIds']);

        static $aRecipients = [];
        if (!empty($aRecipients)) {
            return $aRecipients;
        }

        foreach ($args['chunkedResIds'] as $resIds) {
            $exts = ResModel::getExt([
                'select' => ['category_id', 'address_id', 'exp_user_id', 'dest_user_id', 'is_multicontacts', 'res_id'],
                'where' => ['res_id in (?)'],
                'data' => [$resIds]
            ]);

            if (!empty($exts)) {
                $resId      = '';
                $recipients = [];
                foreach ($exts as $key => $ext) {
                    if ($key != 0 && $resId != $ext['res_id']) {
                        $aRecipients[$resId] = $recipients;
                        $recipients = [];
                    }
                    if (!empty($ext)) {
                        if ($ext['category_id'] == 'outgoing') {
                            $rawContacts = [];
                            if ($ext['is_multicontacts'] == 'Y') {
                                $multiContacts = DatabaseModel::select([
                                    'select'    => ['contact_id', 'address_id'],
                                    'table'     => ['contacts_res'],
                                    'where'     => ['res_id = ?', 'mode = ?'],
                                    'data'      => [$ext['res_id'], 'multi']
                                ]);
                                foreach ($multiContacts as $multiContact) {
                                    $rawContacts[] = [
                                        'login'         => $multiContact['contact_id'],
                                        'address_id'    => $multiContact['address_id'],
                                    ];
                                }
                            } else {
                                $rawContacts[] = [
                                    'login'         => $ext['dest_user_id'],
                                    'address_id'    => $ext['address_id'],
                                ];
                            }
                            foreach ($rawContacts as $rawContact) {
                                if (!empty($rawContact['address_id'])) {
                                    $contact = ContactModel::getOnView([
                                        'select' => [
                                            'is_corporate_person', 'lastname', 'firstname', 'address_num', 'address_street', 'address_town', 'address_postal_code',
                                            'ca_id', 'society', 'contact_firstname', 'contact_lastname', 'address_country'
                                        ],
                                        'where'     => ['ca_id = ?'],
                                        'data'      => [$rawContact['address_id']]
                                    ]);
                                    if (isset($contact[0])) {
                                        $contact = AutoCompleteController::getFormattedContact(['contact' => $contact[0]]);
                                        $recipients[] = $contact['contact']['otherInfo'];
                                    }
                                } else {
                                    $recipients[] = UserModel::getLabelledUserById(['login' => $rawContact['login']]);
                                }
                            }
                        } else {
                            $resourcesContacts = ResourceContactModel::getFormattedByResId(['resId' => $ext['res_id']]);
                            foreach ($resourcesContacts as $resourcesContact) {
                                $recipients[] = $resourcesContact['format'];
                            }
                        }
                        $resId = $ext['res_id'];
                    }
                }
                $aRecipients[$resId] = $recipients;
            }
        }

        if (empty($aRecipients)) {
            $aRecipients = ['empty'];
        }

        return $aRecipients;
    }

    private static function getMaximumHeight(Fpdi $pdf, array $args)
    {
        ValidatorModel::notEmpty($args, ['data', 'width']);
        ValidatorModel::arrayType($args, ['data']);

        $maxHeight = 1;
        if (!is_numeric($args['width'])) {
            return $maxHeight;
        }
        foreach ($args['data'] as $value) {
            $height = $pdf->getStringHeight($args['width'], $value);
            if ($height > $maxHeight) {
                $maxHeight = $height;
            }
        }

        return $maxHeight + 2;
    }
}
