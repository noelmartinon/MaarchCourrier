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

namespace Core\Controllers;

use Attachments\Models\AttachmentsModel;
use Core\Models\ChronoModel;
use Core\Models\CoreConfigModel;
use Core\Models\DocserverModel;
use Core\Models\DocserverTypeModel;
use Core\Models\ResExtModel;
use Core\Models\ResModel;
use Core\Models\UserModel;
use Core\Models\ValidatorModel;
use Entities\Models\EntitiesModel;

class StoreController
{
    public static function storeResource(array $aArgs)
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

            $data = StoreController::prepareStorage([
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
                $resId = AttachmentsModel::create($data);
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
        ValidatorModel::stringType($aArgs, ['collId']);
        ValidatorModel::notEmpty($aArgs['fileInfos'], ['tmpDir', 'size', 'format', 'tmpFileName']);
        ValidatorModel::stringType($aArgs['fileInfos'], ['tmpDir', 'format', 'tmpFileName']);
        ValidatorModel::intVal($aArgs['fileInfos'], ['size']);

        if (!is_dir($aArgs['fileInfos']['tmpDir'])) {
            return ['errors' => '[storeRessourceOnDocserver] FileInfos.tmpDir does not exist'];
        }
        if (!file_exists($aArgs['fileInfos']['tmpDir'] . $aArgs['fileInfos']['tmpFileName'])) {
            return ['errors' => '[storeRessourceOnDocserver] FileInfos.tmpFileName does not exist'];
        }

        $docserver = DocserverModel::getDocserverToInsert(['collId' => $aArgs['collId']])[0];
        if (empty($docserver)) {
            return ['errors' => '[storeRessourceOnDocserver] No available Docserver'];
        }

        $pathOnDocserver = StoreController::createPathOnDocServer(['path' => $docserver['path_template']]);
        if (!empty($pathOnDocserver['errors'])) {
            return ['errors' => '[storeRessourceOnDocserver] ' . $pathOnDocserver['errors']];
        }

        $docinfo = StoreController::getNextFileNameInDocServer(['pathOnDocserver' => $pathOnDocserver]);
        if (!empty($docinfo['errors'])) {
            return ['errors' => '[storeRessourceOnDocserver] ' . $docinfo['errors']];
        }
        $pathInfoOnTmp = pathinfo($aArgs['fileInfos']['tmpDir'] . $aArgs['fileInfos']['tmpFileName']);
        $docinfo['fileDestinationName'] .= '.' . strtolower($pathInfoOnTmp['extension']);

        $docserverTypeObject = DocserverTypeModel::getById(['docserver_type_id' => $docserver['docserver_type_id']])[0];
        $copyResult = StoreController::copyOnDocServer([
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

    public static function createPathOnDocServer(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['path']);
        ValidatorModel::stringType($aArgs, ['path']);

        if (!is_dir($aArgs['path'])) {
            return ['errors' => '[createPathOnDocServer] Path does not exist'];
        }

        error_reporting(0);
        umask(0022);

        $yearPath = $aArgs['path'] . date('Y') . '/';
        if (!is_dir($yearPath)) {
            mkdir($yearPath, 0770);
            if (DIRECTORY_SEPARATOR == '/' && !empty($GLOBALS['apacheUserAndGroup'])) {
                exec('chown ' . escapeshellarg($GLOBALS['apacheUserAndGroup']) . ' ' . escapeshellarg($yearPath));
            }
            umask(0022);
            chmod($yearPath, 0770);
        }

        $monthPath = $yearPath . date('m') . '/';
        if (!is_dir($monthPath)) {
            mkdir($monthPath, 0770);
            if (DIRECTORY_SEPARATOR == '/' && !empty($GLOBALS['apacheUserAndGroup'])) {
                exec('chown ' . escapeshellarg($GLOBALS['apacheUserAndGroup']) . ' ' . escapeshellarg($monthPath));
            }
            umask(0022);
            chmod($monthPath, 0770);
        }

        $pathToDS = $monthPath;
        if (!empty($GLOBALS['wb'])) {
            $pathToDS = "{$monthPath}BATCH/{$GLOBALS['wb']}/";
            if (!is_dir($pathToDS)) {
                mkdir($pathToDS, 0770, true);
                if (DIRECTORY_SEPARATOR == '/' && !empty($GLOBALS['apacheUserAndGroup'])) {
                    exec('chown ' . escapeshellarg($GLOBALS['apacheUserAndGroup']) . ' ' . escapeshellarg($monthPath));
                }
                umask(0022);
                chmod($monthPath, 0770);
            } else {
                return ['errors' => '[createPathOnDocServer] Folder alreay exists, workbatch already exist:' . $pathToDS];
            }
        }

        return $pathToDS;
    }

    public static function getNextFileNameInDocServer(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['pathOnDocserver']);
        ValidatorModel::stringType($aArgs, ['pathOnDocserver']);

        if (!is_dir($aArgs['pathOnDocserver'])) {
            return ['errors' => '[getNextFileNameInDocServer] PathOnDocserver does not exist'];
        }

        umask(0022);

        $aFiles = scandir($aArgs['pathOnDocserver']);
        array_shift($aFiles); // Remove . line
        array_shift($aFiles); // Remove .. line

        if (file_exists($aArgs['pathOnDocserver'] . '/package_information')) {
            unset($aFiles[array_search('package_information', $aFiles)]);
        }
        if (is_dir($aArgs['pathOnDocserver'] . '/BATCH')) {
            unset($aFiles[array_search('BATCH', $aFiles)]);
        }

        $filesNb = count($aFiles);
        if ($filesNb == 0) {
            $zeroOnePath = $aArgs['pathOnDocserver'] . '0001/';

            if (!mkdir($zeroOnePath, 0770)) {
                return ['errors' => '[getNextFileNameInDocServer] Directory creation failed: ' . $zeroOnePath];
            } else {
                if (DIRECTORY_SEPARATOR == '/' && !empty($GLOBALS['apacheUserAndGroup'])) {
                    exec('chown ' . escapeshellarg($GLOBALS['apacheUserAndGroup']) . ' ' . escapeshellarg($zeroOnePath));
                }
                umask(0022);
                chmod($zeroOnePath, 0770);

                return [
                    'destinationDir'        => $zeroOnePath,
                    'fileDestinationName'   => '0001_' . mt_rand(),
                ];
            }
        } else {
            $destinationDir = $aArgs['pathOnDocserver'] . str_pad(count($aFiles), 4, '0', STR_PAD_LEFT) . '/';
            $aFilesBis = scandir($aArgs['pathOnDocserver'] . strval(str_pad(count($aFiles), 4, '0', STR_PAD_LEFT)));
            array_shift($aFilesBis); // Remove . line
            array_shift($aFilesBis); // Remove .. line

            $filesNbBis = count($aFilesBis);
            if ($filesNbBis >= 1000) { //If number of files >= 1000 then creates a new subdirectory
                $zeroNumberPath = $aArgs['pathOnDocserver'] . str_pad($filesNb + 1, 4, '0', STR_PAD_LEFT) . '/';

                if (!mkdir($zeroNumberPath, 0770)) {
                    return ['errors' => '[getNextFileNameInDocServer] Directory creation failed: ' . $zeroNumberPath];
                } else {
                    if (DIRECTORY_SEPARATOR == '/' && !empty($GLOBALS['apacheUserAndGroup'])) {
                        exec('chown ' . escapeshellarg($GLOBALS['apacheUserAndGroup']) . ' ' . escapeshellarg($zeroNumberPath));
                    }
                    umask(0022);
                    chmod($zeroNumberPath, 0770);

                    return [
                        'destinationDir'        => $zeroNumberPath,
                        'fileDestinationName'   => '0001_' . mt_rand(),
                    ];
                }
            } else {
                $higher = $filesNbBis + 1;
                foreach ($aFilesBis as $value) {
                    $currentFileName = explode('.', $value);
                    if ($higher <= (int)$currentFileName[0]) {
                        $higher = (int)$currentFileName[0] + 1;
                    }
                }

                return [
                    'destinationDir'        => $destinationDir,
                    'fileDestinationName'   => str_pad($higher, 4, '0', STR_PAD_LEFT) . '_' . mt_rand(),
                ];
            }
        }
    }

    public static function copyOnDocServer(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['destinationDir', 'fileDestinationName', 'sourceFilePath']);
        ValidatorModel::stringType($aArgs, ['destinationDir', 'fileDestinationName', 'sourceFilePath']);

        if (file_exists($aArgs['destinationDir'] . $aArgs['fileDestinationName'])) {
            return ['errors' => '[copyOnDocserver] File already exists: ' . $aArgs['destinationDir'] . $aArgs['fileDestinationName']];
        }

        if (!file_exists($aArgs['sourceFilePath'])) {
            return ['errors' => '[copyOnDocserver] File does not exist'];
        }

        error_reporting(0);
        $aArgs['sourceFilePath'] = str_replace('\\\\', '\\', $aArgs['sourceFilePath']);

        if (!is_dir($aArgs['destinationDir'])) {
            mkdir($aArgs['destinationDir'], 0770, true);
            if (DIRECTORY_SEPARATOR == '/' && !empty($GLOBALS['apacheUserAndGroup'])) {
                exec('chown ' . escapeshellarg($GLOBALS['apacheUserAndGroup']) . ' ' . escapeshellarg($aArgs['destinationDir']));
            }
            umask(0022);
            chmod($aArgs['destinationDir'], 0770);
        }

        if (!copy($aArgs['sourceFilePath'], $aArgs['destinationDir'] . $aArgs['fileDestinationName'])) {
            return ['errors' => '[copyOnDocserver] Copy on the docserver failed'];
        }
        if (DIRECTORY_SEPARATOR == '/' && !empty($GLOBALS['apacheUserAndGroup'])) {
            exec('chown ' . escapeshellarg($GLOBALS['apacheUserAndGroup']) . ' ' . escapeshellarg($aArgs['destinationDir'] . $aArgs['fileDestinationName']));
        }
        umask(0022);
        chmod($aArgs['destinationDir'] . $aArgs['fileDestinationName'], 0770);

        $fingerprintControl = StoreController::controlFingerPrint([
            'pathInit'          => $aArgs['sourceFilePath'],
            'pathTarget'        => $aArgs['destinationDir'] . $aArgs['fileDestinationName'],
            'fingerprintMode'   => $aArgs['docserverSourceFingerprint'],
        ]);
        if (!empty($fingerprintControl['errors'])) {
            return ['errors' => '[copyOnDocserver] ' . $fingerprintControl['errors']];
        }

        if (!empty($GLOBALS['currentStep'])) { // For batch like life cycle
            $aArgs['destinationDir'] = str_replace($GLOBALS['docservers'][$GLOBALS['currentStep']]['docserver']['path_template'], '', $aArgs['destinationDir']);
        }
        $aArgs['destinationDir'] = str_replace(DIRECTORY_SEPARATOR, '#', $aArgs['destinationDir']);

        $dataToReturn = [
            'copyOnDocserver' =>
                [
                    'destinationDir'        => $aArgs['destinationDir'],
                    'fileDestinationName'   => $aArgs['fileDestinationName'],
                    'fileSize'              => filesize(str_replace('#', '/', $aArgs['destinationDir']) . $aArgs['fileDestinationName']),
                ]
        ];

        if (!empty($GLOBALS['TmpDirectory'])) {
            StoreController::directoryWasher(['path' => $GLOBALS['TmpDirectory']]);
        }

        return $dataToReturn;
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

    private static function directoryWasher(array $aArgs)
    {
        ValidatorModel::notEmpty($aArgs, ['path']);
        ValidatorModel::stringType($aArgs, ['path']);

        if (!is_dir($aArgs['path'])) {
            return ['errors' => '[directoryWasher] Path does not exist'];
        }

        $aFiles = scandir($aArgs['path']);
        foreach ($aFiles as $file) {
            if ($file != '.' && $file != '..') {
                if (filetype($aArgs['path'] . '/' . $file) == 'dir') {
                    StoreController::directoryWasher(['path' => $aArgs['path'] . '/' . $file]);
                } else {
                    unlink($aArgs['path'] . '/' . $file);
                }
            }
        }

        reset($aFiles);

        return true;
    }

    public static function prepareStorage(array $aArgs)
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
            } else if (strtolower($value['type']) == 'string') {
                $aArgs['data'][$key]['value'] = str_replace(';', '', $value['value']);
                $aArgs['data'][$key]['value'] = str_replace('--', '', $value['value']);
            }

            if ($value['column'] == 'status') {
                $statusFound = true;
            } else if ($value['column'] == 'typist') {
                $typistFound = true;
            } else if ($value['column'] == 'type_id') {
                $typeIdFound = true;
            } else if ($value['column'] == 'custom_t10') {
                $theString = str_replace('>', '', $value['value']);
                $mail = explode("<", $theString);
                $user =  UserModel::getByEmail(['mail' => $mail[count($mail) -1], 'select' => ['user_id']]);
                if (!empty($user[0]['user_id'])) {
                    $toAddressFound = true;
                    $destUser = $user[0]['user_id'];
                    $entity = EntitiesModel::getByUserId(['user_id' => $destUser, 'select' => ['entity_id']]);
                    if (!empty($entity[0]['entity_id'])) {
                        $userEntity = $entity[0]['entity_id'];
                        $userPrimaryEntity = true;
                    }
                } else {
                    $entity = EntitiesModel::getByEmail(['email' => $mail[count($mail) -1], 'select' => ['entity_id']]);
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
            $aArgs['data'][] = [
                'column'    => 'typist',
                'value'     => $destUser,
                'type'      => 'string'
            ];
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
                } else if ($value['column'] == 'initiator') {
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
        }

        if (!$processLimitDateFound) {
            $processLimitDate = ResExtModel::retrieveProcessLimitDate(['resId' => $aArgs['resId']]);

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
                $contact = \ContactsModel::getByEmail(['email' => $mail[count($mail) - 1], 'select' => ['contact_id']]);
                if (!empty($contact[0]['contact_id'])) {
                    $aArgs['data'][$key]['value'] = $contact[0]['contact_id'];
                } else {
                    $aArgs['data'][$key]['value'] = 0;
                }
            } elseif ($value['column'] == 'address_id' && !empty($value['value']) && !is_numeric($value['value'])) {
                $mail = explode('<', str_replace('>', '', $value['value']));
                $contact = \ContactsModel::getByEmail(['email' => $mail[count($mail) - 1], 'select' => ['ca_id']]);
                if (!empty($contact[0]['ca_id'])) {
                    $aArgs['data'][$key]['value'] = $contact[0]['ca_id'];
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
