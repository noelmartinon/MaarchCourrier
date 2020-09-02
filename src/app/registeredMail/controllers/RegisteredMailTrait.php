<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   AcknowledgementReceiptTrait
* @author  dev <dev@maarch.org>
* @ingroup core
*/

namespace RegisteredMail\controllers;

use Contact\controllers\ContactController;
use Contact\models\ContactModel;
use Parameter\models\ParameterModel;
use RegisteredMail\models\IssuingSiteModel;
use RegisteredMail\models\RegisteredMailModel;
use RegisteredMail\models\RegisteredNumberRangeModel;
use Resource\models\ResModel;
use setasign\Fpdi\Tcpdf\Fpdi;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\ValidatorModel;

trait RegisteredMailTrait
{
    public static function saveRegisteredMail(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'data']);
        ValidatorModel::intVal($args, ['resId']);
        ValidatorModel::arrayType($args, ['data']);

        $resource = ResModel::getById(['select' => ['departure_date'], 'resId' => $args['resId']]);
        if (empty($resource['departure_date'])) {
            return ['errors' => ['Departure date is empty']];
        }

        if (!in_array($args['data']['type'], ['2D', '2C', 'RW'])) {
            return ['errors' => ['Type is not correct']];
        } elseif (!in_array($args['data']['warranty'], ['R1', 'R2', 'R3'])) {
            return ['errors' => ['Warranty is not correct']];
        } elseif ($args['data']['type'] == 'RW' && $args['data']['warranty'] == 'R3') {
            return ['errors' => ['R3 warranty is not allowed for type RW']];
        }

        $issuingSite = IssuingSiteModel::getById(['id' => $args['data']['issuingSiteId'], 'select' => [1]]);
        if (empty($issuingSite)) {
            return ['errors' => ['Issuing site does not exist']];
        }

        $range = RegisteredNumberRangeModel::get([
            'select'    => ['id', 'range_end', 'current_number'],
            'where'     => ['type = ?', 'site_id = ?', 'status = ?'],
            'data'      => [$args['data']['type'], $args['data']['issuingSiteId'], 'OK']
        ]);
        if (empty($range)) {
            return ['errors' => ['No range found']];
        }

        $status = $range[0]['current_number'] + 1 > $range[0]['range_end'] ? 'DEL' : 'OK';
        RegisteredNumberRangeModel::update([
            'set'   => ['current_number' => $range[0]['current_number'] + 1, 'status' => $status],
            'where' => ['id = ?'],
            'data'  => [$range[0]['id']]
        ]);

        $date = new \DateTime($resource['departure_date']);
        $date = $date->format('d/m/Y');

        RegisteredMailModel::create([
            'res_id'        => $args['resId'],
            'type'          => $args['data']['type'],
            'issuing_site'  => $args['data']['issuingSiteId'],
            'warranty'      => $args['data']['warranty'],
            'letter'        => empty($args['data']['letter']) ? 'false' : 'true',
            'recipient'     => json_encode($args['data']['recipient']),
            'number'        => $range[0]['current_number'],
            'reference'     => "{$date} - {$args['data']['reference']}",
            'generated'     => 'false',
        ]);

        return true;
    }

    public static function saveAndPrintRegisteredMail(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'data']);
        ValidatorModel::intVal($args, ['resId']);
        ValidatorModel::arrayType($args, ['data']);

        $resource = ResModel::getById(['select' => ['departure_date'], 'resId' => $args['resId']]);
        if (empty($resource['departure_date'])) {
            return ['errors' => ['Departure date is empty']];
        }

        if (!in_array($args['data']['type'], ['2D', '2C', 'RW'])) {
            return ['errors' => ['Type is not correct']];
        } elseif (!in_array($args['data']['warranty'], ['R1', 'R2', 'R3'])) {
            return ['errors' => ['warranty is not correct']];
        } elseif ($args['data']['type'] == 'RW' && $args['data']['warranty'] == 'R3') {
            return ['errors' => ['R3 warranty is not allowed for type RW']];
        } elseif (empty($args['data']['recipient']) || empty($args['data']['issuingSiteId'])) {
            return ['errors' => ['recipient or issuingSiteId is missing to print registered mail']];
        } elseif ((empty($args['data']['recipient']['company']) && (empty($args['data']['recipient']['lastname']) || empty($args['data']['recipient']['firstname']))) || empty($args['data']['recipient']['addressStreet']) || empty($args['data']['recipient']['addressPostcode']) || empty($args['data']['recipient']['addressTown']) || empty($args['data']['recipient']['addressCountry'])) {
            return ['errors' => ['company and firstname/lastname, or addressStreet, addressPostcode, addressTown or addressCountry is empty in Recipient']];
        }

        $issuingSite = IssuingSiteModel::getById([
            'id'        => $args['data']['issuingSiteId'],
            'select'    => ['post_office_label', 'address_number', 'address_street', 'address_additional1', 'address_additional2', 'address_postcode', 'address_town', 'address_country']
        ]);
        if (empty($issuingSite)) {
            return ['errors' => ['Issuing site does not exist']];
        }

        $range = RegisteredNumberRangeModel::get([
            'select'    => ['id', 'range_end', 'current_number'],
            'where'     => ['type = ?', 'site_id = ?', 'status = ?'],
            'data'      => [$args['data']['type'], $args['data']['issuingSiteId'], 'OK']
        ]);
        if (empty($range)) {
            return ['errors' => ['No range found']];
        }

        $status = $range[0]['current_number'] + 1 > $range[0]['range_end'] ? 'DEL' : 'OK';
        RegisteredNumberRangeModel::update([
            'set'   => ['current_number' => $range[0]['current_number'] + 1, 'status' => $status],
            'where' => ['id = ?'],
            'data'  => [$range[0]['id']]
        ]);

        $date = new \DateTime($resource['departure_date']);
        $date = $date->format('d/m/Y');

        RegisteredMailModel::create([
            'res_id'        => $args['resId'],
            'type'          => $args['data']['type'],
            'issuing_site'  => $args['data']['issuingSiteId'],
            'warranty'      => $args['data']['warranty'],
            'letter'        => empty($args['data']['letter']) ? 'false' : 'true',
            'recipient'     => json_encode($args['data']['recipient']),
            'number'        => $range[0]['current_number'],
            'reference'     => "{$date} - {$args['data']['reference']}",
            'generated'     => 'true',
        ]);

        $sender = ContactController::getContactAfnor([
            'company'               => $issuingSite['post_office_label'],
            'address_number'        => $issuingSite['address_number'],
            'address_street'        => $issuingSite['address_street'],
            'address_additional1'   => $issuingSite['address_additional1'],
            'address_additional2'   => $issuingSite['address_additional2'],
            'address_postcode'      => $issuingSite['address_postcode'],
            'address_town'          => $issuingSite['address_town'],
            'address_country'       => $issuingSite['address_country']
        ]);

        $recipient = ContactController::getContactAfnor([
            'company'               => $args['data']['recipient']['company'],
            'civility'              => ContactModel::getCivilityId(['civilityLabel' => $args['data']['recipient']['civility']]),
            'firstname'             => $args['data']['recipient']['firstname'],
            'lastname'              => $args['data']['recipient']['lastname'],
            'address_number'        => $args['data']['recipient']['addressNumber'],
            'address_street'        => $args['data']['recipient']['addressStreet'],
            'address_additional1'   => $args['data']['recipient']['addressAdditional1'],
            'address_additional2'   => $args['data']['recipient']['addressAdditional2'],
            'address_postcode'      => $args['data']['recipient']['addressPostcode'],
            'address_town'          => $args['data']['recipient']['addressTown'],
            'address_country'       => $args['data']['recipient']['addressCountry']
        ]);
        $registeredMailPDF = RegisteredMailController::getRegisteredMailPDF([
            'type'      => $args['data']['type'],
            'number'    => $range[0]['current_number'],
            'warranty'  => $args['data']['warranty'],
            'letter'    => !empty($args['data']['letter']),
            'reference' => "{$date} - {$args['data']['reference']}",
            'recipient' => $recipient,
            'sender'    => $sender
        ]);

        return ['data' => base64_encode($registeredMailPDF['fileContent'])];
    }

    public static function printRegisteredMail(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);

        static $data;

        $registeredMail = RegisteredMailModel::getByResId(['select' => ['issuing_site', 'type', 'number', 'warranty', 'letter', 'recipient', 'reference'], 'resId' => $args['resId']]);
        $recipient = json_decode($registeredMail['recipient'], true);
        if (empty($registeredMail)) {
            return ['errors' => ['No registered mail for this resource']];
        } elseif (empty($recipient) || empty($registeredMail['issuing_site']) || empty($registeredMail['type']) || empty($registeredMail['number']) || empty($registeredMail['warranty'])) {
            return ['errors' => ['recipient, issuing_site, type, number or warranty is missing to print registered mail']];
        } elseif ((empty($recipient['company']) && (empty($recipient['lastname']) || empty($recipient['firstname']))) || empty($recipient['addressStreet']) || empty($recipient['addressPostcode']) || empty($recipient['addressTown']) || empty($recipient['addressCountry'])) {
            return ['errors' => ['company and firstname/lastname, or addressStreet, addressPostcode, addressTown or addressCountry is empty in Recipient']];
        }

        RegisteredMailModel::update([
            'set'   => ['generated' => 'true'],
            'where' => ['res_id = ?'],
            'data'  => [$args['resId']]
        ]);

        $issuingSite = IssuingSiteModel::getById([
            'id'        => $registeredMail['issuing_site'],
            'select'    => ['post_office_label', 'address_number', 'address_street', 'address_additional1', 'address_additional2', 'address_postcode', 'address_town', 'address_country']
        ]);

        $sender = ContactController::getContactAfnor([
            'company'               => $issuingSite['post_office_label'],
            'address_number'        => $issuingSite['address_number'],
            'address_street'        => $issuingSite['address_street'],
            'address_additional1'   => $issuingSite['address_additional1'],
            'address_additional2'   => $issuingSite['address_additional2'],
            'address_postcode'      => $issuingSite['address_postcode'],
            'address_town'          => $issuingSite['address_town'],
            'address_country'       => $issuingSite['address_country']
        ]);

        $recipient = ContactController::getContactAfnor([
            'company'               => $recipient['company'],
            'civility'              => ContactModel::getCivilityId(['civilityLabel' => $recipient['civility']]),
            'firstname'             => $recipient['firstname'],
            'lastname'              => $recipient['lastname'],
            'address_number'        => $recipient['addressNumber'],
            'address_street'        => $recipient['addressStreet'],
            'address_additional1'   => $recipient['addressAdditional1'],
            'address_additional2'   => $recipient['addressAdditional2'],
            'address_postcode'      => $recipient['addressPostcode'],
            'address_town'          => $recipient['addressTown'],
            'address_country'       => $recipient['addressCountry']
        ]);

        $registeredMailPDF = RegisteredMailController::getRegisteredMailPDF([
            'type'      => $registeredMail['type'],
            'number'    => $registeredMail['number'],
            'warranty'  => $registeredMail['warranty'],
            'letter'    => $registeredMail['letter'],
            'reference' => $registeredMail['reference'],
            'recipient' => $recipient,
            'sender'    => $sender
        ]);

        if ($data === null) {
            $data = [
                '2D' => null,
                '2C' => null,
                'RW' => null
            ];
        }

        if (empty($data[$registeredMail['type']])) {
            $data[$registeredMail['type']] = base64_encode($registeredMailPDF['fileContent']);
        } else {
            $concatPdf = new Fpdi('P', 'pt');
            $concatPdf->setPrintHeader(false);
            $concatPdf->setPrintFooter(false);
            $tmpPath = CoreConfigModel::getTmpPath();

            $firstFile = $tmpPath . 'registeredMail_first_file' . rand() . '.pdf';
            file_put_contents($firstFile, base64_decode($data[$registeredMail['type']]));
            $pageCount = $concatPdf->setSourceFile($firstFile);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $pageId = $concatPdf->ImportPage($pageNo);
                $s = $concatPdf->getTemplatesize($pageId);
                $concatPdf->AddPage($s['orientation'], $s);
                $concatPdf->useImportedPage($pageId);
            }

            $secondFile = $tmpPath . 'registeredMail_second_file' . rand() . '.pdf';
            file_put_contents($secondFile, $registeredMailPDF['fileContent']);
            $concatPdf->setSourceFile($secondFile);
            $pageId = $concatPdf->ImportPage(1);
            $s = $concatPdf->getTemplatesize($pageId);
            $concatPdf->AddPage($s['orientation'], $s);
            $concatPdf->useImportedPage($pageId);

            $fileContent = $concatPdf->Output('', 'S');

            $data[$registeredMail['type']] = base64_encode($fileContent);
            unlink($firstFile);
            unlink($secondFile);
        }

        return ['data' => $data];
    }

    public static function printDepositList(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);

        static $processedResources;
        static $filesByType;
        static $currentDepositId;
        static $registeredMailsIdsByType;
        static $processedTypes;

        if ($filesByType === null) {
            $filesByType = [
                '2D' => null,
                '2C' => null,
                'RW' => null
            ];
        }
        if ($registeredMailsIdsByType === null) {
            $registeredMailsIdsByType = [
                '2D' => [],
                '2C' => [],
                'RW' => []
            ];
        }
        if ($processedResources === null) {
            $processedResources = [];
        }
        if ($processedTypes === null) {
            $processedTypes = [];
        }

        if (in_array($args['resId'], $processedResources)) {
            return [];
        }

        $registeredMail = RegisteredMailModel::getWithResources([
            'select' => ['issuing_site', 'type', 'number', 'warranty', 'recipient', 'generated', 'departure_date', 'deposit_id'],
            'where'  => ['res_letterbox.res_id = ?'],
            'data'   => [$args['resId']]
        ]);
        if (empty($registeredMail[0])) {
            return ['errors' => ['No registered mail for this resource']];
        }
        $registeredMail = $registeredMail[0];

        if (!$registeredMail['generated']) {
            return ['errors' => ['Registered mail not generated for this resource']];
        }

        $site = IssuingSiteModel::getById(['id' => $registeredMail['issuing_site']]);

        $range = RegisteredNumberRangeModel::get([
            'where' => ['site_id = ?', 'type = ?', 'range_start <= ?', 'range_end >= ?'],
            'data'  => [$registeredMail['issuing_site'], $registeredMail['type'], $registeredMail['number'], $registeredMail['number']]
        ]);
        if (empty($range[0])) {
            return ['errors' => ['No range found']];
        }
        $range = $range[0];

        if (empty($registeredMail['deposit_id'])) {
            $registeredMails = RegisteredMailModel::getWithResources([
                'select'  => ['number', 'warranty', 'reference', 'recipient', 'res_letterbox.res_id'],
                'where'   => ['type = ?', 'issuing_site = ?', 'departure_date = ?', 'generated = ?'],
                'data'    => [$registeredMail['type'], $registeredMail['issuing_site'], $registeredMail['departure_date'], true],
                'orderBy' => ['number']
            ]);

            if (empty($currentDepositId) || !in_array($registeredMail['type'], $processedTypes)) {
                $lastDepositId = ParameterModel::getById(['id' => 'last_deposit_id', 'select' => ['param_value_int']]);
                $currentDepositId = $lastDepositId['param_value_int'] + 1;
                ParameterModel::update(['id' => 'last_deposit_id', 'param_value_int' => $currentDepositId]);
            }
        } else {
            $registeredMails = RegisteredMailModel::getWithResources([
                'select'  => ['number', 'warranty', 'reference', 'recipient', 'res_letterbox.res_id'],
                'where'   => ['deposit_id = ?'],
                'data'    => [$registeredMail['deposit_id']],
                'orderBy' => ['number']
            ]);
        }

        $resultPDF = RegisteredMailController::getDepositListPdf([
            'site'            => [
                'label'              => $site['label'],
                'postOfficeLabel'    => $site['post_office_label'],
                'accountNumber'      => $site['account_number'],
                'addressNumber'      => $site['address_number'],
                'addressStreet'      => $site['address_street'],
                'addressAdditional1' => $site['address_additional1'],
                'addressAdditional2' => $site['address_additional2'],
                'addressPostcode'    => $site['address_postcode'],
                'addressTown'        => $site['address_town'],
                'addressCountry'     => $site['address_country'],
            ],
            'type'            => $registeredMail['type'],
            'trackingNumber'  => $range['tracking_account_number'],
            'departureDate'   => $registeredMail['departure_date'],
            'registeredMails' => $registeredMails
        ]);

        $resIds = array_column($registeredMails, 'res_id');
        $processedResources = array_merge($processedResources, $resIds);
        $registeredMailsIdsByType[$registeredMail['type']] = $resIds;

        $filesByType[$registeredMail['type']] = base64_encode($resultPDF['fileContent']);

        if (!empty($currentDepositId)) {
            foreach ($registeredMailsIdsByType as $type => $ids) {
                if (!empty($ids) && !in_array($type, $processedTypes)) {
                    RegisteredMailModel::update([
                        'set'   => ['deposit_id' => $currentDepositId],
                        'where' => ['res_id in (?)'],
                        'data'  => [$ids]
                    ]);
                }
            }
        }
        $processedTypes[] = $registeredMail['type'];

        $finalFile = null;
        foreach ($filesByType as $type => $file) {
            if (empty($file)) {
                continue;
            }
            if (empty($finalFile)) {
                $finalFile = $file;
                continue;
            }

            $concatPdf = new Fpdi('P', 'pt');
            $concatPdf->setPrintHeader(false);
            $concatPdf->setPrintFooter(false);
            $tmpPath = CoreConfigModel::getTmpPath();

            $firstFile = $tmpPath . 'depositList_first_file' . rand() . '.pdf';
            file_put_contents($firstFile, base64_decode($finalFile));
            $pageCount = $concatPdf->setSourceFile($firstFile);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $pageId = $concatPdf->ImportPage($pageNo);
                $s = $concatPdf->getTemplatesize($pageId);
                $concatPdf->AddPage($s['orientation'], $s);
                $concatPdf->useImportedPage($pageId);
            }

            $secondFile = $tmpPath . 'depositList_second_file' . rand() . '.pdf';
            file_put_contents($secondFile, base64_decode($file));
            $concatPdf->setSourceFile($secondFile);
            $pageId = $concatPdf->ImportPage(1);
            $s = $concatPdf->getTemplatesize($pageId);
            $concatPdf->AddPage($s['orientation'], $s);
            $concatPdf->useImportedPage($pageId);

            $fileContent = $concatPdf->Output('', 'S');

            $finalFile = base64_encode($fileContent);
            unlink($firstFile);
            unlink($secondFile);
        }

        return ['data' => ['encodedFile' => $finalFile]];
    }
}
