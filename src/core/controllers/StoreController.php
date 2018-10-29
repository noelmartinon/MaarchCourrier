<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Store Controller
 * @author dev@maarch.org
 * @ingroup core
 */

namespace SrcCore\controllers;

use Attachment\models\AttachmentModel;
use Contact\models\ContactModel;
use Docserver\controllers\DocserverController;
use Docserver\models\DocserverModel;
use Docserver\models\DocserverTypeModel;
use Resource\models\ChronoModel;
use SrcCore\models\ValidatorModel;
use Entity\models\EntityModel;
use Resource\models\ResModel;
use Resource\models\ResExtModel;
use SrcCore\models\CoreConfigModel;
use User\models\UserModel;

class StoreController
{
    public static function storeResource(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['encodedFile', 'format', 'status', 'type_id', 'category_id']);
        ValidatorModel::stringType($aArgs, ['format', 'status']);

        $mlbColumns = [
            'category_id', 'exp_contact_id', 'exp_user_id', 'dest_contact_id', 'dest_user_id',
            'nature_id', 'alt_identifier', 'admission_date', 'process_limit_date', 'recommendation_limit_date', 'closing_date', 'address_id'
        ];
        try {
            foreach ($aArgs as $column => $value) {
                if (empty($value)) {
                    unset($aArgs[$column]);
                }
            }
            $fileContent    = base64_decode(str_replace(['-', '_'], ['+', '/'], $aArgs['encodedFile']));
            $fileName       = 'tmp_file_' . rand() . '.' . $aArgs['format'];
            $tmpFilepath    = CoreConfigModel::getTmpPath() . $fileName;

            $file = fopen($tmpFilepath, 'w');
            fwrite($file, $fileContent);
            fclose($file);

            $storeResult = StoreController::storeResourceOnDocServer([
                'collId'            => 'letterbox_coll',
                'docserverTypeId'   => 'DOC',
                'fileInfos'         => [
                    'tmpDir'            => CoreConfigModel::getTmpPath(),
                    'tmpFileName'       => $fileName
                ]
            ]);
            if (!empty($storeResult['errors'])) {
                return ['errors' => '[storeResource] ' . $storeResult['errors']];
            }
            unlink($tmpFilepath);
            unset($aArgs['encodedFile']);

            $data = [
                'docserver_id'  => $storeResult['docserver_id'],
                'filename'      => $storeResult['file_destination_name'],
                'filesize'      => $storeResult['fileSize'],
                'path'          => $storeResult['destination_dir'],
                'fingerprint'   => $storeResult['fingerPrint']
            ];
            $data = array_merge($aArgs, $data);
            $data = StoreController::prepareStorage($data);

            $dataMlb = [];
            foreach ($data as $key => $value) {
                if (in_array($key, $mlbColumns)) {
                    $dataMlb[$key] = $value;
                    unset($data[$key]);
                }
            }
            $resId = ResModel::create($data);

            $dataMlb['res_id'] = $resId;
            ResModel::createExt($dataMlb);

            return $resId;
        } catch (\Exception $e) {
            return ['errors' => '[storeResource] ' . $e->getMessage()];
        }
    }

    public static function storeResourceRes(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['encodedFile', 'data', 'collId', 'table', 'fileFormat', 'status']);
        ValidatorModel::stringType($aArgs, ['collId', 'table', 'fileFormat', 'status']);
        ValidatorModel::arrayType($aArgs, ['data']);

        if (!in_array($aArgs['table'], ['res_letterbox', 'res_attachments'])) {
            return ['errors' => '[storeResource] Table not valid'];
        }

        try {
            $fileContent    = base64_decode(str_replace(['-', '_'], ['+', '/'], $aArgs['encodedFile']));
            $fileName       = 'tmp_file_' . rand() . '.' . $aArgs['fileFormat'];
            $tmpFilepath    = CoreConfigModel::getTmpPath() . $fileName;

            $file = fopen($tmpFilepath, 'w');
            fwrite($file, $fileContent);
            fclose($file);

            $storeResult = StoreController::storeResourceOnDocServer([
                'collId'    => $aArgs['collId'],
                'fileInfos' => [
                    'tmpDir'        => CoreConfigModel::getTmpPath(),
                    'size'          => filesize($tmpFilepath),
                    'format'        => $aArgs['fileFormat'],
                    'tmpFileName'   => $fileName,
                ]
            ]);
            if (!empty($storeResult['errors'])) {
                return ['errors' => '[storeResource] ' . $storeResult['errors']];
            }
            unlink($tmpFilepath);

            $data = StoreController::prepareStorageRes([
                'data'          => $aArgs['data'],
                'docserverId'   => $storeResult['docserver_id'],
                'status'        => $aArgs['status'],
                'fileName'      => $storeResult['file_destination_name'],
                'fileFormat'    => $aArgs['fileFormat'],
                'fileSize'      => $storeResult['fileSize'],
                'path'          => $storeResult['destination_dir'],
                'fingerPrint'   => $storeResult['fingerPrint']
            ]);

            $resId = false;
            if ($aArgs['table'] == 'res_letterbox') {
                $resId = ResModel::create($data);
            } elseif ($aArgs['table'] == 'res_attachments') {
                $resId = AttachmentModel::create($data);
            }

            return $resId;
        } catch (\Exception $e) {
            return ['errors' => '[storeResource] ' . $e->getMessage()];
        }
    }

    public static function storeResourceOnDocServer(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['collId', 'fileInfos']);
        ValidatorModel::arrayType($aArgs, ['fileInfos']);
        ValidatorModel::stringType($aArgs, ['collId', 'docserverTypeId']);
        ValidatorModel::notEmpty($aArgs['fileInfos'], ['tmpDir', 'tmpFileName']);
        ValidatorModel::stringType($aArgs['fileInfos'], ['tmpDir', 'tmpFileName']);

        if (!is_dir($aArgs['fileInfos']['tmpDir'])) {
            return ['errors' => '[storeRessourceOnDocserver] FileInfos.tmpDir does not exist'];
        }
        if (!file_exists($aArgs['fileInfos']['tmpDir'] . $aArgs['fileInfos']['tmpFileName'])) {
            return ['errors' => '[storeRessourceOnDocserver] FileInfos.tmpFileName does not exist '
            . $aArgs['fileInfos']['tmpDir'] . $aArgs['fileInfos']['tmpFileName']];
        }

        $aArgs['docserverTypeId'] = empty($aArgs['docserverTypeId']) ? 'DOC' : $aArgs['docserverTypeId'];
        $docserver = DocserverModel::getDocserverToInsert(['collId' => $aArgs['collId'], 'typeId' => $aArgs['docserverTypeId']]);
        if (empty($docserver)) {
            return ['errors' => '[storeRessourceOnDocserver] No available Docserver'];
        }

        $pathOnDocserver = DocserverController::createPathOnDocServer(['path' => $docserver['path_template']]);
        if (!empty($pathOnDocserver['errors'])) {
            return ['errors' => '[storeRessourceOnDocserver] ' . $pathOnDocserver['errors']];
        }

        $docinfo = DocserverController::getNextFileNameInDocServer(['pathOnDocserver' => $pathOnDocserver['pathToDocServer']]);
        if (!empty($docinfo['errors'])) {
            return ['errors' => '[storeRessourceOnDocserver] ' . $docinfo['errors']];
        }
        $pathInfoOnTmp = pathinfo($aArgs['fileInfos']['tmpDir'] . $aArgs['fileInfos']['tmpFileName']);
        $docinfo['fileDestinationName'] .= '.' . strtolower($pathInfoOnTmp['extension']);

        $docserverTypeObject = DocserverTypeModel::getById(['id' => $docserver['docserver_type_id']]);
        $copyResult = DocserverController::copyOnDocServer([
            'sourceFilePath'             => $aArgs['fileInfos']['tmpDir'] . $aArgs['fileInfos']['tmpFileName'],
            'destinationDir'             => $docinfo['destinationDir'],
            'fileDestinationName'        => $docinfo['fileDestinationName'],
            'docserverSourceFingerprint' => $docserverTypeObject['fingerprint_mode'],
        ]);
        if (!empty($copyResult['errors'])) {
            return ['errors' => '[storeRessourceOnDocserver] ' . $copyResult['errors']];
        }

        $destinationDir = substr($copyResult['copyOnDocserver']['destinationDir'], strlen($docserver['path_template'])) . '/';
        $destinationDir = str_replace(DIRECTORY_SEPARATOR, '#', $destinationDir);

        DocserverModel::update([
            'docserver_id'          => $docserver['docserver_id'],
            'actual_size_number'    => $docserver['actual_size_number'] + $aArgs['fileInfos']['size']
        ]);

        return [
            'path_template'         => $docserver['path_template'],
            'destination_dir'       => $destinationDir,
            'docserver_id'          => $docserver['docserver_id'],
            'file_destination_name' => $copyResult['copyOnDocserver']['fileDestinationName'],
            'fileSize'              => $copyResult['copyOnDocserver']['fileSize'],
            'fingerPrint'           => StoreController::getFingerPrint([
                'filePath'  => $docinfo['destinationDir'] . $docinfo['fileDestinationName'],
                'mode'      => $docserverTypeObject['fingerprint_mode']
            ])
        ];
    }

    public static function controlFingerPrint(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['pathInit', 'pathTarget']);
        ValidatorModel::stringType($aArgs, ['pathInit', 'pathTarget', 'fingerprintMode']);

        if (!file_exists($aArgs['pathInit'])) {
            return ['errors' => '[controlFingerprint] PathInit does not exist'];
        }
        if (!file_exists($aArgs['pathTarget'])) {
            return ['errors' => '[controlFingerprint] PathTarget does not exist'];
        }

        $fingerprint1 = StoreController::getFingerPrint(['filePath' => $aArgs['pathInit'], 'mode' => $aArgs['fingerprintMode']]);
        $fingerprint2 = StoreController::getFingerPrint(['filePath' => $aArgs['pathTarget'], 'mode' => $aArgs['fingerprintMode']]);

        if ($fingerprint1 != $fingerprint2) {
            return ['errors' => '[controlFingerprint] Fingerprints do not match: ' . $aArgs['pathInit'] . ' and ' . $aArgs['pathTarget']];
        }

        return true;
    }

    public static function getFingerPrint(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['filePath']);
        ValidatorModel::stringType($aArgs, ['filePath', 'mode']);

        if (empty($aArgs['mode']) || $aArgs['mode'] == 'NONE') {
            return '0';
        }

        return hash_file(strtolower($aArgs['mode']), $aArgs['filePath']);
    }

    public static function prepareStorage(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['docserver_id', 'filename', 'format', 'filesize', 'path', 'fingerprint', 'status']);
        ValidatorModel::stringType($aArgs, ['docserver_id', 'filename', 'format', 'path', 'fingerprint', 'status']);
        ValidatorModel::intVal($aArgs, ['filesize']);

        if (empty($aArgs['typist'])) {
            $aArgs['typist'] = 'auto';
        }

        unset($aArgs['alt_identifier']);
        if (!empty($aArgs['chrono'])) {
            $aArgs['alt_identifier'] = ChronoModel::getChrono(['id' => $aArgs['category_id'], 'entityId' => $aArgs['destination'], 'typeId' => $aArgs['type_id']]);
        }
        unset($aArgs['chrono']);

        if (empty($aArgs['process_limit_date'])) {
            $processLimitDate = ResModel::getStoredProcessLimitDate(['typeId' => $aArgs['type_id'], 'admissionDate' => $aArgs['admission_date']]);
            $aArgs['process_limit_date'] = $processLimitDate;
        }

        if (!empty($aArgs['exp_contact_id']) && !is_numeric($aArgs['exp_contact_id'])) {
            $mail = explode('<', str_replace('>', '', $aArgs['exp_contact_id']));
            $contact = ContactModel::getByEmail(['email' => $mail[count($mail) - 1], 'select' => ['contacts_v2.contact_id']]);
            if (!empty($contact['contact_id'])) {
                $aArgs['exp_contact_id'] = $contact['contact_id'];
            } else {
                $aArgs['exp_contact_id'] = 0;
            }
        }
        
        if (!empty($aArgs['address_id']) && !is_numeric($aArgs['address_id'])) {
            $mail = explode('<', str_replace('>', '', $aArgs['address_id']));
            $contact = ContactModel::getByEmail(['email' => $mail[count($mail) - 1], 'select' => ['contact_addresses.id']]);
            if (!empty($contact['id'])) {
                $aArgs['address_id'] = $contact['id'];
            } else {
                $aArgs['address_id'] = 0;
            }
        }

        $aArgs['creation_date'] = 'CURRENT_TIMESTAMP';

        return $aArgs;
    }

    public static function prepareStorageRes(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['data', 'docserverId', 'status', 'fileName', 'fileFormat', 'fileSize', 'path', 'fingerPrint']);
        ValidatorModel::stringType($aArgs, ['docserverId', 'status', 'fileName', 'fileFormat', 'path', 'fingerPrint']);
        ValidatorModel::arrayType($aArgs, ['data']);
        ValidatorModel::intVal($aArgs, ['fileSize']);

        $statusFound        = false;
        $typistFound        = false;
        $typeIdFound        = false;
        $toAddressFound     = false;
        $userPrimaryEntity  = false;

        foreach ($aArgs['data'] as $key => $value) {
            $aArgs['data'][$key]['column'] = strtolower($value['column']);
        }

        foreach ($aArgs['data'] as $key => $value) {
            if (strtolower($value['type']) == 'integer' || strtolower($value['type']) == 'float') {
                if (empty($value['value'])) {
                    $aArgs['data'][$key]['value'] = '0';
                }
            } elseif (strtolower($value['type']) == 'string') {
                $aArgs['data'][$key]['value'] = str_replace(';', '', $value['value']);
                $aArgs['data'][$key]['value'] = str_replace('--', '', $value['value']);
            }

            if ($value['column'] == 'status') {
                $statusFound = true;
            } elseif ($value['column'] == 'typist') {
                $typistFound = true;
            } elseif ($value['column'] == 'type_id') {
                $typeIdFound = true;
            } elseif ($value['column'] == 'custom_t10') {
                $theString = str_replace('>', '', $value['value']);
                $mail = explode("<", $theString);
                $user =  UserModel::getByEmail(['mail' => $mail[count($mail) -1], 'select' => ['user_id']]);
                if (!empty($user[0]['user_id'])) {
                    $toAddressFound = true;
                    $destUser = $user[0]['user_id'];
                    $entity = EntityModel::getByUserId(['userId' => $destUser, 'select' => ['entity_id']]);
                    if (!empty($entity[0]['entity_id'])) {
                        $userEntity = $entity[0]['entity_id'];
                        $userPrimaryEntity = true;
                    }
                } else {
                    $entity = EntityModel::getByEmail(['email' => $mail[count($mail) -1], 'select' => ['entity_id']]);
                    if (!empty($entity[0]['entity_id'])) {
                        $userPrimaryEntity = true;
                    }
                }
            }
        }

        $destUser   = empty($destUser) ? '' : $destUser;
        $userEntity = empty($userEntity) ? '' : $userEntity;

        if (!$typistFound && !$toAddressFound) {
            $aArgs['data'][] = [
                'column'    => 'typist',
                'value'     => 'auto',
                'type'      => 'string'
            ];
        }
        if (!$typeIdFound) {
            $aArgs['data'][] = [
                'column'    => 'type_id',
                'value'     => '10',
                'type'      => 'string'
            ];
        }
        if (!$statusFound) {
            $aArgs['data'][] = [
                'column'    => 'status',
                'value'     => $aArgs['status'],
                'type'      => 'string'
            ];
        }
        if ($toAddressFound) {
            $aArgs['data'][] = [
                'column'    => 'dest_user',
                'value'     => $destUser,
                'type'      => 'string'
            ];
            if (!$typistFound) {
                $aArgs['data'][] = [
                    'column'    => 'typist',
                    'value'     => $destUser,
                    'type'      => 'string'
                ];
            }
        }
        if ($userPrimaryEntity) {
            $destinationFound = false;
            $initiatorFound = false;
            foreach ($aArgs['data'] as $key => $value) {
                if ($value['column'] == 'destination') {
                    if (empty($value['value'])) {
                        $aArgs['data'][$key]['value'] = $userEntity;
                    }
                    $destinationFound = true;
                } elseif ($value['column'] == 'initiator') {
                    if (empty($value['value'])) {
                        $aArgs['data'][$key]['value'] = $userEntity;
                    }
                    $initiatorFound = true;
                }
            }
            if (!$destinationFound) {
                $aArgs['data'][] = [
                    'column'    => 'destination',
                    'value'     => $userEntity,
                    'type'      => 'string'
                ];
            }
            if (!$initiatorFound) {
                $aArgs['data'][] = [
                    'column'    => 'initiator',
                    'value'     => $userEntity,
                    'type'      => 'string'
                ];
            }
        }

        $aArgs['data'][] = [
            'column'    => 'offset_doc',
            'value'     => '',
            'type'      => 'string'
        ];
        $aArgs['data'][] = [
            'column'    => 'logical_adr',
            'value'     => '',
            'type'      => 'string'
        ];
        $aArgs['data'][] = [
            'column'    => 'docserver_id',
            'value'     => $aArgs['docserverId'],
            'type'      => 'string'
        ];
        $aArgs['data'][] = [
            'column'    => 'creation_date',
            'value'     => 'CURRENT_TIMESTAMP',
            'type'      => 'function'
        ];
        $aArgs['data'][] = [
            'column'    => 'path',
            'value'     => $aArgs['path'],
            'type'      => 'string'
        ];
        $aArgs['data'][] = [
            'column'    => 'fingerprint',
            'value'     => $aArgs['fingerPrint'],
            'type'      => 'string'
        ];
        $aArgs['data'][] = [
            'column'    => 'filename',
            'value'     => $aArgs['fileName'],
            'type'      => 'string'
        ];
        $aArgs['data'][] = [
            'column'    => 'format',
            'value'     => $aArgs['fileFormat'],
            'type'      => 'string'
        ];
        $aArgs['data'][] = [
            'column'    => 'filesize',
            'value'     => $aArgs['fileSize'],
            'type'      => 'int'
        ];

        $formatedData = [];
        foreach ($aArgs['data'] as $value) {
            $formatedData[$value['column']] = $value['value'];
        }

        return $formatedData;
    }

    public static function prepareExtStorage(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['data', 'resId']);
        ValidatorModel::arrayType($aArgs, ['data']);
        ValidatorModel::intVal($aArgs, ['resId']);

        $processLimitDateFound  = false;
        $admissionDate          = null;

        foreach ($aArgs['data'] as $key => $value) {
            $aArgs['data'][$key]['column'] = strtolower($value['column']);
        }

        foreach ($aArgs['data'] as $value) {
            if ($value['column'] == 'process_limit_date') {
                $processLimitDateFound = true;
            }
            if ($value['column'] == 'category_id') {
                $categoryId = $value['value'];
            }
            if ($value['column'] == 'admission_date') {
                $admissionDate = $value['value'];
            }
        }

        if (!$processLimitDateFound) {
            $processLimitDate = ResModel::getStoredProcessLimitDate(['resId' => $aArgs['resId'], 'admissionDate' => $admissionDate]);

            $aArgs['data'][] = [
                'column'    => 'process_limit_date',
                'value'     => $processLimitDate,
                'type'      => 'date'
            ];
        }

        foreach ($aArgs['data'] as $key => $value) {
            if (strtolower($value['type']) == 'integer' || strtolower($value['type']) == 'float') {
                if ($value['value'] == '') {
                    $aArgs['data'][$key]['value'] = '0';
                }
                $aArgs['data'][$key]['value'] = str_replace(',', '.', $value['value']);
            }
            if ($value['column'] == 'alt_identifier' && empty($value['value']) && !empty($categoryId)) {
                $document = ResModel::getById(['resId' => $aArgs['resId'], 'select' => ['destination, type_id']]);
                $aArgs['data'][$key]['value'] = ChronoModel::getChrono(['id' => $categoryId, 'entityId' => $document['destination'], 'typeId' => $document['type_id']]);
            } elseif ($value['column'] == 'exp_contact_id' && !empty($value['value']) && !is_numeric($value['value'])) {
                $mail = explode('<', str_replace('>', '', $value['value']));
                $contact = ContactModel::getByEmail(['email' => $mail[count($mail) - 1], 'select' => ['contacts_v2.contact_id']]);
                if (!empty($contact['contact_id'])) {
                    $aArgs['data'][$key]['value'] = $contact['contact_id'];
                } else {
                    $aArgs['data'][$key]['value'] = 0;
                }
            } elseif ($value['column'] == 'address_id' && !empty($value['value']) && !is_numeric($value['value'])) {
                $mail = explode('<', str_replace('>', '', $value['value']));
                $contact = ContactModel::getByEmail(['email' => $mail[count($mail) - 1], 'select' => ['contact_addresses.id']]);
                if (!empty($contact['id'])) {
                    $aArgs['data'][$key]['value'] = $contact['ca_id'];
                } else {
                    $aArgs['data'][$key]['value'] = 0;
                }
            }
        }

        $formatedData = [];
        foreach ($aArgs['data'] as $value) {
            $formatedData[$value['column']] = $value['value'];
        }
        $formatedData['res_id'] = $aArgs['resId'];

        return $formatedData;
    }
}
