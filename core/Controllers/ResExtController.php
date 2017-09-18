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
use Core\Models\ResExtModel;

require_once 'core/class/class_db_pdo.php';

class ResExtController
{
    public function create(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        if(empty($aArgs)) {
            
            $aArgs = $request->getQueryParams();

            $aArgs['data'] = json_decode($aArgs['data']);

            $aArgs['data'] = $this->object2array($aArgs['data']);

            //FIX pb if data has parent
            if (isset($aArgs['data']['data'])) {
                $aArgs['data'] = $aArgs['data']['data'];
            }
        }

        //*****************************************************************************************
        //LOG ONLY LOG FOR DEBUG
        // $file = fopen('storeResourceLogs.log', a);
        // fwrite($file, '[' . date('Y-m-d H:i:s') . '] new request' . PHP_EOL);
        // foreach ($aArgs as $key => $value) {
        //     if ($key <> 'encodedFile') {
        //         fwrite($file, '[' . date('Y-m-d H:i:s') . '] ' . $key . ' : ' . $value . PHP_EOL);
        //     }
        // }
        // fclose($file);
        // ob_flush();
        // ob_start();
        // print_r($aArgs['data']);
        // file_put_contents("storeResourceLogs.log", ob_get_flush());
        //END LOG FOR DEBUG ONLY
        //*****************************************************************************************

        $return = $this->storeExtResource($aArgs);

        if ($return['errors']) {
            return $response
                ->withStatus(500)
                ->withJson(
                    ['errors' => _NOT_CREATE . ' ' . $return['errors']]
                );
        }
        
        //standardize ws response for MaarchCapture
        $wsReturn['status'] = $return;
        
        return $response->withJson($wsReturn);
    }

    public function delete(RequestInterface $request, ResponseInterface $response, $aArgs)
    {
        if (isset($aArgs['id'])) {
            $obj = $this->deleteExtRes([
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

    /**
     * Deletes ext resource on database.
     * @param  $resId  integer
     * @param  $table  string
     * @return res_id
     */
    public function deleteExtRes($aArgs)
    {
        if (isset($aArgs['id'])) {
            $obj = ResExtModel::delete([
                'id' => $aArgs['id']
            ]);
        } else {
            return false;
        }
        
        $datas = $obj;

        return $datas;
    }

    /**
     * Store ext resource on database.
     * @param  $resId  integer
     * @param  $data array
     * @param  $table  string
     * @return res_id
     */
    public function storeExtResource($aArgs)
    {
        if (empty($aArgs['resId'])) {
            return ['errors' => 'resId ' . _EMPTY];
        }

        if (empty($aArgs['data'])) {
            return ['errors' => 'data ' . _EMPTY];
        }

        if (empty($aArgs['table'])) {
            return ['errors' => 'table ' . _EMPTY];
        }

        if (empty($aArgs['resTable'])) {
            return ['errors' => 'resTable ' . _EMPTY];
        }

        $resDetails = ResExtModel::getById([
            'resId'  => $aArgs['resId'],
            'table'  => $aArgs['resTable'],
            'select' => ['res_id']
        ]);
        
        if (empty($resDetails[0]['res_id'])) {
            return ['errors' => 'res_id ' . _OF . ' ' . $aArgs['resTable'] . ' ' . _NOT_EXISTS];
        }

        $resDetails = ResExtModel::getById([
            'resId' => $aArgs['resId'],
            'table' => $aArgs['table'],
            'select' => ['res_id']
        ]);
        
        if ($resDetails[0]['res_id'] > 0) {
            return ['errors' => 'res_id ' . _OF . ' '  . $aArgs['table'] . ' ' . _EXISTS];
        }
        
        $prepareData = $this->prepareStorageExt($aArgs);
        
        $resExtInsert = ResExtModel::create([
            'table' => 'mlb_coll_ext',
            'data'  => $prepareData
        ]);

        return true;
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

    /**
     * Prepares storage on database for resExt.
     * @param  $data array
     * @return array $data
     */
    public function prepareStorageExt($aArgs)
    {
        if (empty($aArgs['resId'])) {
            return ['errors' => 'resId ' . _EMPTY];
        }
        if (empty($aArgs['data'])) {
            return ['errors' => 'data ' . _EMPTY];
        }
        if (empty($aArgs['table'])) {
            return ['errors' => 'table ' . _EMPTY];
        }
        $queryExtFields = '(';
        $queryExtValues = '(';
        $queryExtValuesFinal = '(';
        $parameters = array();
        $findProcessLimitDate = false;
        $findProcessNotes = false;
        $delayProcessNotes = 0;

        $resId = $aArgs['resId'];
        $table = $aArgs['table'];
        $data = $aArgs['data'];
        $countD = count($data);
        for ($i = 0; $i < $countD; $i++) {
            if ($data[$i]['column'] == 'process_limit_date') {
                $findProcessLimitDate = true;
            }
            if ($data[$i]['column'] == 'process_notes') {
                $findProcessNotes = true;
                $don = explode(',', $data[$i]['value']);
                $delayProcessNotes = $don['0'];
                $calendarType = $don['1'];
            }
        }
        
        if ($table == 'mlb_coll_ext') {
            $ResExtModel = new ResExtModel();
            if ($delayProcessNotes > 0) {
                $processLimitDate = $ResExtModel->retrieveProcessLimitDate([
                    'resId' => $resId,
                    'delayProcessNotes' => $delayProcessNotes,
                    'calendarType' => $calendarType,
                ]);
            } else {
                $processLimitDate = $ResExtModel->retrieveProcessLimitDate([
                    'resId' => $resId
                ]);
            }
        }

        if (!$findProcessLimitDate && $processLimitDate <> '') {
            array_push(
                $data,
                array(
                'column' => 'process_limit_date',
                'value' => $processLimitDate,
                'type' => 'date',
                )
            );
        }
        require_once 'apps/maarch_entreprise/class/class_chrono.php';
        $chronoX = new \chrono();
        require_once 'apps/maarch_entreprise/Models/ContactsModel.php';
        $contacts = new \ContactsModel();
        for ($i=0; $i<count($data); $i++) {
            if (strtoupper($data[$i]['type']) == 'INTEGER' ||
                strtoupper($data[$i]['type']) == 'FLOAT'
            ) {
                if ($data[$i]['value'] == '') {
                    $data[$i]['value'] = '0';
                }
                $data[$i]['value'] = str_replace(',', '.', $data[$i]['value']);
            }
            if (strtoupper($data[$i]['column']) == strtoupper('category_id')) {
                $categoryId = $data[$i]['value'];
            }
            if (strtoupper($data[$i]['column']) == strtoupper('alt_identifier') &&
                $data[$i]['value'] == ""
            ) {
                if ($table == 'mlb_coll_ext') {
                    $resDetails = ResExtModel::getById([
                        'resId' => $resId,
                        'table' => 'res_letterbox',
                        'select' => ['destination, type_id']
                    ]);
                    $myVars = array(
                        'entity_id' => $resDetails[0]['destination'],
                        'type_id' => $resDetails[0]['type_id'],
                        'category_id' => $categoryId,
                        'folder_id' => "",
                    );
                    $myChrono = $chronoX->generate_chrono($categoryId, $myVars, 'false');
                    $data[$i]['value'] = $myChrono;
                }
            }
            if (strtoupper($data[$i]['column']) == strtoupper('exp_contact_id') &&
                $data[$i]['value'] <> "" &&
                !is_numeric($data[$i]['value'])
            ) {
                $theString = str_replace(">", "", $data[$i]['value']);
                $mail = explode("<", $theString);
                $contactDetails =$contacts->getByEmail([
                    'email' => $mail[count($mail) -1],
                    'select' => ['contact_id']
                ]);
                if ($contactDetails[0]['contact_id'] <> "") {
                    $data[$i]['value'] = $contactDetails[0]['contact_id'];
                } else {
                    $data[$i]['value'] = 0;
                }
                $data[$i]['type'] = 'integer';
            }
            if (strtoupper($data[$i]['column']) == strtoupper('address_id') &&
                $data[$i]['value'] <> "" &&
                !is_numeric($data[$i]['value'])
            ) {
                $theString = str_replace(">", "", $data[$i]['value']);
                $mail = explode("<", $theString);
                $contactDetails =$contacts->getByEmail([
                    'email' => $mail[count($mail) -1],
                    'select' => ['ca_id']
                ]);
                if ($contactDetails[0]['ca_id'] <> "") {
                    $data[$i]['value'] = $contactDetails[0]['ca_id'];
                } else {
                    $data[$i]['value'] = 0;
                }
                $data[$i]['type'] = 'integer';
            }
            //COLUMN
            $data[$i]['column'] = strtolower($data[$i]['column']);
            //VALUE
            $parameters[$data[$i]['column']] = $data[$i]['value'];
        }
        $parameters['res_id'] = $resId;

        return $parameters;
    }
}
