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
            return ['errors' => ['Type is not correct']];
        } elseif ($args['data']['type'] == 'RW' && $args['data']['warranty'] == 'R3') {
            return ['errors' => ['R3 warranty is not allowed for type RW']];
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
        $registeredMailPDF = RegisteredMailController::getRegisteredMailPDF([
            'type'      => $args['data']['type'],
            'number'    => $range[0]['current_number'],
            'warranty'  => $args['data']['warranty'],
            'letter'    => !empty($args['data']['letter']),
            'reference' => "{$date} - {$args['data']['reference']}",
            'recipient' => $args['data']['recipient'],
            'sender'    => $sender
        ]);

        return ['data' => base64_encode($registeredMailPDF['fileContent'])];
    }

    public static function printRegisteredMail(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);

        static $data;

        $registeredMail = RegisteredMailModel::getByResId(['select' => ['issuing_site', 'type', 'number', 'warranty', 'letter', 'recipient'], 'resId' => $args['resId']]);
        if (empty($registeredMail)) {
            return ['errors' => ['No registered mail for this resource']];
        }

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

        $registeredMail['recipient'] = json_decode($registeredMail['recipient'], true);
        $registeredMailPDF = RegisteredMailController::getRegisteredMailPDF([
            'type'      => $registeredMail['type'],
            'number'    => $registeredMail['number'],
            'warranty'  => $registeredMail['warranty'],
            'letter'    => $registeredMail['letter'],
            'reference' => $registeredMail['reference'],
            'recipient' => $registeredMail['recipient'],
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

    public static function printDepositSlip(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId']);
        ValidatorModel::intVal($args, ['resId']);

        static $processedResources;
        static $data;

        if ($data === null) {
            $data = [
                '2D' => null,
                '2C' => null,
                'RW' => null
            ];
        }
        if ($processedResources === null) {
            $processedResources = [];
        }

        if (in_array($args['resId'], $processedResources)) {
            return [];
        }

        $registeredMail = RegisteredMailModel::getWithResources([
            'select' => ['issuing_site', 'type', 'number', 'warranty', 'recipient', 'generated', 'departure_date'],
            'where'  => ['res_letterbox.res_id = ?'],
            'data'   => [$args['resId']]
        ]);
        if (empty($registeredMail)) {
            return ['errors' => ['No registered mail for this resource']];
        }

        if (!$registeredMail['generated']) {
            return ['errors' => ['Registered mail not generated for this resource']];
        }

        $site = IssuingSiteModel::getById(['id' => $registeredMail['issuing_site']]);

        $range = RegisteredNumberRangeModel::get([
            'where' => ['site_id = ?', 'type = ?'],
            'data'  => [$registeredMail['issuing_site'], $registeredMail['type']]
        ]);

        $registeredMails = RegisteredMailModel::getWithResources([
            'select' => ['number', 'warranty', 'reference', 'recipient', 'res_letterbox.res_id'],
            'where'  => ['type = ?', 'issuing_site = ?', 'departure_date = ?', 'generated = ?'],
            'data'   => [$registeredMail['type'], $registeredMail['issuing_site'], $registeredMail['departure_date'], true]
        ]);

        $args = [
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
        ];

        $resultPDF = RegisteredMailController::getDepositSlipPdf($args);

        $resIds = array_column($registeredMails, 'res_id');

        $processedResources = array_merge($processedResources, $resIds);

        $data[$registeredMail['type']] = $resultPDF['encodedFileContent'];

        return ['data' => $data];
    }
}
