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
            return ['errors' => ['Type is not correct']];
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

        $status = $range['current_number'] + 1 > $range['range_end'] ? 'DEL' : 'OK';
        RegisteredNumberRangeModel::update([
            'set'   => ['current_number' => $range['current_number'] + 1, 'status' => $status],
            'where' => ['id = ?'],
            'data'  => [$range['id']]
        ]);

        $date = new \DateTime($resource['departure_date']);
        $date = $date->format('d/m/Y');

        RegisteredMailModel::create([
            'res_id'        => $args['resId'],
            'type'          => $args['data']['type'],
            'issuing_site'  => $args['data']['issuingSiteId'],
            'warranty'      => $args['data']['warranty'],
            'letter'        => empty($args['letter']) ? 'false' : 'true',
            'recipient'     => json_encode($args['data']['recipient']),
            'number'        => $range['current_number'],
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

        $status = $range['current_number'] + 1 > $range['range_end'] ? 'DEL' : 'OK';
        RegisteredNumberRangeModel::update([
            'set'   => ['current_number' => $range['current_number'] + 1, 'status' => $status],
            'where' => ['id = ?'],
            'data'  => [$range['id']]
        ]);

        $date = new \DateTime($resource['departure_date']);
        $date = $date->format('d/m/Y');

        RegisteredMailModel::create([
            'res_id'        => $args['resId'],
            'type'          => $args['data']['type'],
            'issuing_site'  => $args['data']['issuingSiteId'],
            'warranty'      => $args['data']['warranty'],
            'letter'        => empty($args['letter']) ? 'false' : 'true',
            'recipient'     => json_encode($args['data']['recipient']),
            'number'        => $range['current_number'],
            'reference'     => "{$date} - {$args['data']['reference']}",
            'generated'     => 'true',
        ]);

        return true;
    }

    public static function printRegisteredMail(array $args)
    {
        ValidatorModel::notEmpty($args, ['resId', 'data']);
        ValidatorModel::intVal($args, ['resId']);
        ValidatorModel::arrayType($args, ['data']);

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
        $encodedFileContent = RegisteredMailController::getRegisteredMailPDF([
            'type'      => $registeredMail['type'],
            'number'    => $registeredMail['number'],
            'warranty'  => $registeredMail['warranty'],
            'letter'    => $registeredMail['letter'],
            'reference' => $registeredMail['reference'],
            'recipient' => $registeredMail['recipient'],
            'sender'    => $sender
        ]);

        return ['encodedFileContent' => $encodedFileContent];
    }
}
