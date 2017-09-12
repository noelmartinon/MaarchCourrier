<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

/**
* @brief Resource Controller
* @author dev@maarch.org
* @ingroup core
*/

namespace Core\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Respect\Validation\Validator;
use Core\Models\DocserverModel;
use Core\Models\DocserverTypeModel;
use Core\Models\UserModel;
use Core\Models\ResModel;
use Core\Models\DoctypesModel;
use Entities\Models\EntitiesModel;
use Core\Controllers\DocserverController;
use Core\Controllers\DocserverToolsController;

require_once 'core/class/class_db_pdo.php';
require_once 'modules/notes/Models/NotesModel.php';

class ResController
{
    public function create(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        if (!empty($aArgs)) {
            $aArgs = $aArgs;
        } else {
            $aArgs = $request->getQueryParams();
            $aArgs['data'] = json_decode($aArgs['data']);
            $aArgs['data'] = $this->object2array($aArgs['data']);
        }

        $return = $this->storeResource($aArgs);

        if ($return['errors']) {
            return $response
                ->withStatus(500)
                ->withJson(
                    ['errors' => _NOT_CREATE . ' ' . $return['errors']]
                );
        }
        
        return $response->withJson($return);
    }

    public function delete(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        if (isset($aArgs['id'])) {
            $obj = $this->deleteRes([
                'id' => $aArgs['id']
            ]);
            if (!$obj) {
                return $response
                    ->withStatus(500)
                    ->withJson(['errors' => _NOT_DELETE]);
            }
        } else {
            return $response
                ->withStatus(500)
                ->withJson(['errors' => _NOT_DELETE]);
        }
        
        $datas = [
            $obj,
        ];

        return $response->withJson($datas);
    }

    public function isLock(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        return $response->withJson(ResModel::isLockForCurrentUser(['resId' => $aArgs['resId']]));
    }

    public function getNotesCountForCurrentUserById(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        return $response->withJson(\NotesModel::countForCurrentUserByResId(['resId' => $aArgs['resId']]));
    }

    /**
     * Deletes ext resource on database.
     * @param  $resId  integer
     * @param  $table  string
     * @return res_id
     */
    public function deleteRes($aArgs)
    {
        if (isset($aArgs['id'])) {
            $obj = ResModel::delete([
                'id' => $aArgs['id']
            ]);
        } else {
            return false;
        }
        
        $datas = $obj;

        return $datas;
    }

    /**
     * Store resource on database.
     * @param  $encodedFile  string
     * @param  $data array
     * @param  $collId  string
     * @param  $table  string
     * @param  $fileFormat  string
     * @param  $status  string
     * @return res_id
     */
    public function storeResource($aArgs)
    {
        if (empty($aArgs['encodedFile'])) {
            return ['errors' => 'encodedFile ' . _EMPTY];
        }

        if (empty($aArgs['data'])) {
            return ['errors' => 'data ' . _EMPTY];
        }

        if (empty($aArgs['collId'])) {
            return ['errors' => 'collId ' . _EMPTY];
        }

        if (empty($aArgs['table'])) {
            return ['errors' => 'table ' . _EMPTY];
        }

        if (empty($aArgs['fileFormat'])) {
            return ['errors' => 'fileFormat ' . _EMPTY];
        }

        if (empty($aArgs['status'])) {
            return ['errors' => 'status ' . _EMPTY];
        }
        $encodedFile = $aArgs['encodedFile'];
        $data = $aArgs['data'];
        $collId = $aArgs['collId'];
        $table = $aArgs['table'];
        $fileFormat = $aArgs['fileFormat'];
        $status = $aArgs['status'];

        try {
            $count = count($data);
            for ($i = 0; $i < $count; $i++) {
                $data[$i]['column'] = strtolower($data[$i]['column']);
            }
            
            $returnCode = 0;
            //copy sended file on tmp
            $fileContent = base64_decode($encodedFile);
            $random = rand();
            $fileName = 'tmp_file_' . $random . '.' . $fileFormat;
            $Fnm = $_SESSION['config']['tmppath'] . $fileName;
            $inF = fopen($Fnm, "w");
            fwrite($inF, $fileContent);
            fclose($inF);

            //store resource on docserver
            $ds = new DocserverController();
            $aArgs = [
                'collId' => $collId,
                'fileInfos' =>
                    [
                        'tmpDir'        => $_SESSION['config']['tmppath'],
                        'size'          => filesize($Fnm),
                        'format'        => $fileFormat,
                        'tmpFileName'   => $fileName,
                    ]
            ];
            
            $storeResult = array();
            $storeResult = $ds->storeResourceOnDocserver($aArgs);
            
            if (!empty($storeResult['errors'])) {
                return ['errors' => $storeResult['errors']];
            }

            //store resource metadata in database
            $aArgs = [
                'data'        => $data,
                'docserverId' => $storeResult['docserver_id'],
                'status'      => $status,
                'fileFormat'  => $fileFormat,
            ];
            
            $data = $this->prepareStorage($aArgs);
            
            unlink($Fnm);

            $aArgs = [
                'table'         => $table,
                'path'          => $storeResult['destination_dir'],
                'filename'      => $storeResult['file_destination_name'],
                'docserverPath' => $storeResult['path_template'],
                'docserverId'   => $storeResult['docserver_id'],
                'docserverPath' => $storeResult['path_template'],
                'data'          => $data,
            ];

            $this->loadIntoDb($aArgs);

            $resDetails = ResModel::getByPath([
                'docserverId' => $storeResult['docserver_id'],
                'path'        => $storeResult['destination_dir'],
                'filename'    => $storeResult['file_destination_name'],
                'table'       => $aArgs['table'],
                'select'      => ['res_id']
            ]);
            
            $resId = $resDetails[0]['res_id'];
            

            if (!is_numeric($resId)) {
                return ['errors' => 'Pb with SQL insertion : ' .$resId];
            }

            if ($resId == 0) {
                $resId = '';
            }

            return [$resId];
        } catch (Exception $e) {
            return ['errors' => 'unknown error' . $e->getMessage()];
        }
    }

    public function update(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        if (!empty($aArgs)) {
            $aArgs = $aArgs;
        } else {
            $aArgs = $request->getQueryParams();
            $aArgs['data'] = json_decode($aArgs['data']);
            $aArgs['data'] = $this->object2array($aArgs['data']);
        }

        $return = $this->updateResource($aArgs);

        if ($return) {
            $id = $aArgs['res_id'];
            $obj = ResModel::getById([
                'resId' => $id,
                'orderBy' => 'res_id'
            ]);
        } else {
            return $response
                ->withStatus(500)
                ->withJson(['errors' => _NOT_UPDATE]);
        }

        $datas = [
            $obj,
        ];

        return $response->withJson($datas);
    }

    public function updateResource($aArgs)
    {
        $data = $aArgs['data'];
        $prepareData = [];
        $countD = count($data);
        for ($i = 0; $i < $countD; $i++) {
            //COLUMN
            $data[$i]['column'] = strtolower($data[$i]['column']);
            //VALUE
            $prepareData[$data[$i]['column']] = $data[$i]['value'];
        }

        //print_r($prepareData);exit;
        $aArgs['data'] = $prepareData;

        $errors = [];

        $return = ResModel::update($aArgs);

        if ($return) {
            $id = $aArgs['res_id'];
            $obj = ResModel::getById([
                'resId' => $id,
                'orderBy' => 'res_id'
            ]);
        } else {
            return $response
                ->withStatus(500)
                ->withJson(['errors' => _NOT_UPDATE]);
        }

        $datas = [
            $obj,
        ];

        return $datas;
    }

    /**
    * Inserts the Resource Object data into the data base
    *
    * @param  $table string Resource table where to insert
    * @param  $path  string Resource path in the docserver
    * @param  $filename string Resource file name
    * @param  $docserverPath  string Docserver path
    * @param  $docserverId  string Docserver identifier
    * @param  boolean
    */
    public function loadIntoDb($aArgs)
    {
        $db = new \Database();
        if (empty($aArgs['table'])) {
            return ['errors' => 'table ' . _EMPTY];
        }

        if (empty($aArgs['path'])) {
            return ['errors' => 'path ' . _EMPTY];
        }

        if (empty($aArgs['filename'])) {
            return ['errors' => 'filename ' . _EMPTY];
        }

        if (empty($aArgs['docserverPath'])) {
            return ['errors' => 'docserverPath ' . _EMPTY];
        }

        if (empty($aArgs['docserverId'])) {
            return ['errors' => 'docserverId ' . _EMPTY];
        }

        if (empty($aArgs['data'])) {
            return ['errors' => 'data ' . _EMPTY];
        }
        $table = $aArgs['table'];
        $path = $aArgs['path'];
        $filename = $aArgs['filename'];
        $docserverPath = $aArgs['docserverPath'];
        $docserverId = $aArgs['docserverId'];
        $data = $aArgs['data'];

        $filetmp = $docserverPath;
        $tmp = $path;
        $tmp = str_replace('#', DIRECTORY_SEPARATOR, $tmp);
        $filetmp .= $tmp;
        $filetmp .= $filename;
        
        $docserver = DocserverModel::getById([
            'docserver_id' => $docserverId
        ]);
        $docserverType = DocserverTypeModel::getById([
            'docserver_type_id' => $docserver[0]['docserver_type_id']
        ]);

        $fingerprint = DocserverToolsController::doFingerprint(
            [
                'path'            => $filetmp,
                'fingerprintMode' => $docserverType[0]['fingerprint_mode'],
            ]
        );

        $filesize = filesize($filetmp);
        array_push(
            $data,
            array(
                'column' => "fingerprint",
                'value' => $fingerprint['fingerprint'],
                'type' => "string"
            )
        );
        array_push(
            $data,
            array(
                'column' => "filesize",
                'value' => $filesize,
                'type' => "int"
            )
        );
        array_push(
            $data,
            array(
                'column' => "path",
                'value' => $path,
                'type' => "string"
            )
        );
        array_push(
            $data,
            array(
                'column' => "filename",
                'value' => $filename,
                'type' => "string"
            )
        );
        array_push(
            $data,
            array(
                'column' => 'creation_date',
                'value' => $db->current_datetime(),
                'type' => "function"
            )
        );
        $testBasicFields = $this->checkBasicFields($data);

        if (!$testBasicFields['status']) {
            return ['error' => $testBasicFields['error']];
        } else {
            $prepareData = [];
            $countD = count($data);
            for ($i = 0; $i < $countD; $i++) {
                //COLUMN
                $data[$i]['column'] = strtolower($data[$i]['column']);
                //VALUE
                $prepareData[$data[$i]['column']] = $data[$i]['value'];
            }

            $resInsert = ResModel::create([
                'table' => 'res_letterbox',
                'data'  => $prepareData
            ]);

            return true;
        }
    }

    /**
    * Checks the mininum fields required for an insert into the database
    *
    * @param  $data array Array of the fields to insert into the database
    * @return error $error
    */
    private function checkBasicFields($data)
    {
        $error = '';
        $db = new \Database();
        $find_format = false;
        $find_typist = false;
        $find_creation_date = false;
        $find_docserver_id = false;
        $find_path = false;
        $find_filename = false;
        $find_offset = false;
        $find_logical_adr = false;
        $find_fingerprint = false;
        $find_filesize = false;
        $find_status = false;
        for ($i=0; $i < count($data); $i++) {
            if ($data[$i]['column'] == 'format') {
                $find_format = true;
                // must be tested in the file_index.php file (module = indexing_searching)
            } elseif ($data[$i]['column'] == 'typist') {
                $find_typist = true;
            } elseif ($data[$i]['column'] == 'creation_date') {
                $find_creation_date = true;
                if ($data[$i]['value'] <> $db->current_datetime()) {
                    $error .= _CREATION_DATE_ERROR;
                }
            } elseif ($data[$i]['column'] == 'docserver_id') {
                $find_docserver_id =  true;
            } elseif ($data[$i]['column'] == 'path') {
                $find_path = true;
                if (empty($data[$i]['value'])) {
                    $error .= _PATH_ERROR;
                }
            } elseif ($data[$i]['column'] == 'filename') {
                $find_filename = true;
                if (!preg_match(
                    "/^[\w-.]+.([a-zA-Z-0-9][a-zA-Z-0-9][a-zA-Z-0-9][a-zA-Z-0-9]?|maarch)$/",
                    $data[$i]['value']
                )
                ) {
                    $error .= _FILENAME_ERROR . ' ' . $data[$i]['value'] . '<br/>';
                }
            } elseif ($data[$i]['column'] == "offset_doc") {
                $find_offset = true;
            } elseif ($data[$i]['column'] == 'logical_adr') {
                $find_logical_adr = true;
            } elseif ($data[$i]['column'] == 'fingerprint') {
                $find_fingerprint  = true;
                if (!preg_match("/^[0-9A-Fa-f]+$/", $data[$i]['value'])) {
                    $error .= _FINGERPRINT_ERROR;
                }
            } elseif ($data[$i]['column'] == 'filesize') {
                $find_filesize = true;
                if ($data[$i]['value'] <= 0) {
                    $error .= _FILESIZE_ERROR;
                }
            } elseif ($data[$i]['column'] == 'status') {
                $find_status = true;
            }
        }

        if ($find_format == false) {
            $error .= _MISSING_FORMAT;
        }
        if ($find_typist == false) {
            $error .= _MISSING_TYPIST;
        }
        if ($find_creation_date == false) {
            $error .= _MISSING_CREATION_DATE;
        }
        if ($find_docserver_id == false) {
            $error .= _MISSING_DOCSERVER_ID;
        }
        if ($find_path == false) {
            $error .= _MISSING_PATH;
        }
        if ($find_filename == false) {
            $error .= _MISSING_FILENAME;
        }
        if ($find_offset == false) {
            $error .= _MISSING_OFFSET;
        }
        if ($find_logical_adr == false) {
            $error .= _MISSING_LOGICAL_ADR;
        }
        if ($find_fingerprint == false) {
            $error .= _MISSING_FINGERPRINT;
        }
        if ($find_filesize == false) {
            $error .= _MISSING_FILESIZE;
        }
        if ($find_status == false) {
            $error .= _MISSING_STATUS;
        }

        if (!empty($error)) {
            $status = false;
        } else {
            $status = true;
        }

        return [
            'status' => $status,
            'error'  => $error
        ];
    }

    /**
     * Prepares storage on database.
     * @param  $data array
     * @param  $docserverId string
     * @param  $status string
     * @param  $fileFormat string
     * @return array $data
     */
    public function prepareStorage($aArgs)
    {
        if (empty($aArgs['data'])) {
            return ['errors' => 'data ' . _EMPTY];
        }

        if (empty($aArgs['docserverId'])) {
            return ['errors' => 'docserverId ' . _EMPTY];
        }

        if (empty($aArgs['status'])) {
            return ['errors' => 'status ' . _EMPTY];
        }

        if (empty($aArgs['fileFormat'])) {
            return ['errors' => 'fileFormat ' . _EMPTY];
        }

        $statusFound = false;
        $typistFound = false;
        $typeIdFound = false;
        $toAddressFound = false;
        $userPrimaryEntity = false;
        $destinationFound = false;
        $initiatorFound = false;
        
        $data = $aArgs['data'];
        $docserverId = $aArgs['docserverId'];
        $status = $aArgs['status'];
        $fileFormat = $aArgs['fileFormat'];

        $userModel = new UserModel();
        $entityModel = new \Entities\Models\EntitiesModel();

        $countD = count($data);
        for ($i = 0; $i < $countD; $i++) {
            if (strtoupper($data[$i]['type']) == 'INTEGER' ||
                strtoupper($data[$i]['type']) == 'FLOAT'
            ) {
                if ($data[$i]['value'] == '') {
                    $data[$i]['value'] = '0';
                }
            }

            if (strtoupper($data[$i]['type']) == 'STRING') {
                $data[$i]['value'] = $data[$i]['value'];
                $data[$i]['value'] = str_replace(";", "", $data[$i]['value']);
                $data[$i]['value'] = str_replace("--", "", $data[$i]['value']);
            }

            if (strtoupper($data[$i]['column']) == strtoupper('status')) {
                $statusFound = true;
            }

            if (strtoupper($data[$i]['column']) == strtoupper('typist')) {
                $typistFound = true;
            }

            if (strtoupper($data[$i]['column']) == strtoupper('type_id')) {
                $typeIdFound = true;
            }

            if (strtoupper($data[$i]['column']) == strtoupper('custom_t10')) {
                $mail = array();
                $theString = str_replace(">", "", $data[$i]['value']);
                $mail = explode("<", $theString);
                $user = $userModel->getByEmail(['mail' => $mail[count($mail) -1]]);
                $userIdFound = $user[0]['user_id'];
                if (!empty($userIdFound)) {
                    $toAddressFound = true;
                    $destUser = $userIdFound;
                    $entity = $entityModel->getByUserId(['user_id' => $destUser]);
                    if (!empty($entity[0]['entity_id'])) {
                        $userEntity = $entity[0]['entity_id'];
                        $userPrimaryEntity = true;
                    }
                } else {
                    $entity = $entityModel->getByEmail(['email' => $mail[count($mail) -1]]);
                    if (!empty($entity[0]['entity_id'])) {
                        $userPrimaryEntity = true;
                    }
                }
            }
        }

        $countD = count($data);
        for ($i = 0; $i < $countD; $i++) {
            if ($data[$i]['column'] == 'type_id') {
                $docTypes = DoctypesModel::getByTypeId([
                    'type_id' => $aArgs['type_id']
                ]);

                if ($docTypes) {
                    $duc = $docTypes[0]['duration_current_use'];
                    if ($duc && is_int($duc) && $duc > 0) {
                        $date = new DateTime();
                        $date->add(new DateInterval('P' . $duc . 'M'));

                        array_push(
                            $data,
                            array(
                                'column' => 'date_current_use',
                                'value' => $date->format('Y-m-d H:m:s.u'),
                                'type' => "date"
                            )
                        );
                    }
                }
            }
        }

        if (!$typistFound && !$toAddressFound) {
            array_push(
                $data,
                array(
                    'column' => 'typist',
                    'value' => 'auto',
                    'type' => 'string',
                )
            );
        }

        if (!$typeIdFound) {
            array_push(
                $data,
                array(
                    'column' => 'type_id',
                    'value' => '10',
                    'type' => 'string',
                )
            );
        }
        
        if (!$statusFound) {
            array_push(
                $data,
                array(
                    'column' => 'status',
                    'value' => $status,
                    'type' => 'string',
                )
            );
        }
        
        if ($toAddressFound) {
            array_push(
                $data,
                array(
                    'column' => 'dest_user',
                    'value' => $destUser,
                    'type' => 'string',
                )
            );
            array_push(
                $data,
                array(
                    'column' => 'typist',
                    'value' => $destUser,
                    'type' => 'string',
                )
            );
        }
        
        if ($userPrimaryEntity) {
            for ($i = 0; $i < count($data); $i++) {
                if (strtoupper($data[$i]['column']) == strtoupper('destination')) {
                    if ($data[$i]['value'] == "") {
                        $data[$i]['value'] = $userEntity;
                    }
                    $destinationFound = true;
                    break;
                }
            }
            if (!$destinationFound) {
                array_push(
                    $data,
                    array(
                        'column' => 'destination',
                        'value' => $userEntity,
                        'type' => 'string',
                    )
                );
            }
        }
        
        if ($userPrimaryEntity) {
            for ($i = 0; $i<count($data); $i++) {
                if (strtoupper($data[$i]['column']) == strtoupper('initiator')) {
                    if ($data[$i]['value'] == "") {
                        $data[$i]['value'] = $userEntity;
                    }
                    $initiatorFound = true;
                    break;
                }
            }
            if (!$initiatorFound) {
                array_push(
                    $data,
                    array(
                        'column' => 'initiator',
                        'value' => $userEntity,
                        'type' => 'string',
                    )
                );
            }
        }
        array_push(
            $data,
            array(
                'column' => 'format',
                'value' => $fileFormat,
                'type' => 'string',
            )
        );
        array_push(
            $data,
            array(
                'column' => 'offset_doc',
                'value' => '',
                'type' => 'string',
            )
        );
        array_push(
            $data,
            array(
                'column' => 'logical_adr',
                'value' => '',
                'type' => 'string',
            )
        );
        array_push(
            $data,
            array(
                'column' => 'docserver_id',
                'value' => $docserverId,
                'type' => 'string',
            )
        );

        return $data;
    }

    /**
    * Convert an object to an array
    * @param  $object object to convert
    */
    private function object2array($object)
    {
        $return = null;
        if (is_array($object)) {
            foreach ($object as $key => $value) {
                $return[$key] = $this->object2array($value);
            }
        } else {
            if (is_object($object)) {
                $var = get_object_vars($object);
                if ($var) {
                    foreach ($var as $key => $value) {
                        $return[$key] = ($key && !$value) ? null : $this->object2array($value);
                    }
                } else {
                    return $object;
                }
            } else {
                return $object;
            }
        }
        return $return;
    }
}
