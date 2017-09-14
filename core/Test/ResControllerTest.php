<?php

namespace MaarchTest;

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

require_once __DIR__.'/define.php';

class ResControllerTest extends \PHPUnit_Framework_TestCase
{

    public function testPrepareStorage()
    {
        $action = new \Core\Controllers\ResController();

        $data = [];

        array_push(
            $data,
            array(
                'column' => 'custom_t1',
                'value' => 'TEST',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'custom_t10',
                'value' => 'lgi@maarch.org',
                'type' => 'string',
            )
        );

        $aArgs = [
            'data'        => $data,
            'docserverId' => 'FASTHD_MAN',
            'status'      => 'new',
            'fileFormat'  => 'pdf',
        ];

        $response = $action->prepareStorage($aArgs);
        
        $this->assertArrayHasKey('column', $response[0]);
    }

    public function testStoreResource()
    {
        $action = new \Core\Controllers\ResController();

        $path = $_SESSION['config']['tmppath'] . '/test/';

        if (!is_dir($path)) {
            mkdir($path);
        }

        $fileSource = 'test_source.txt';

        $fp = fopen($path . $fileSource, 'a');
        fwrite($fp, 'a unit test');
        fclose($fp);

        $fileContent = file_get_contents($path . $fileSource, FILE_BINARY);
        $encodedFile = base64_encode($fileContent);
        
        $data = [];

        array_push(
            $data,
            array(
                'column' => 'subject',
                'value' => 'UNIT TEST',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'type_id',
                'value' => 110,
                'type' => 'integer',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'custom_t1',
                'value' => 'TEST',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'custom_t10',
                'value' => 'lgi@maarch.org',
                'type' => 'string',
            )
        );

        $aArgs = [
            'encodedFile'   => $encodedFile,
            'data'          => $data,
            'collId'        => 'letterbox_coll',
            'table'         => 'res_letterbox',
            'fileFormat'    => 'txt',
            'status'        => 'new',
        ];

        $response = $action->storeResource($aArgs);
        
        $this->assertGreaterThanOrEqual(0, $response[0]);
    }

    public function testDelete()
    {
        $action = new \Core\Controllers\ResController();

        $environment = \Slim\Http\Environment::mock(
            [
                'REQUEST_METHOD' => 'DELETE',
            ]
        );

        $resId = \Core\Models\ResModel::getLastId(['select' => 'res_id']);

        $aArgs = [
            'id'=> $resId[0]['res_id']
        ];

        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = new \Slim\Http\Response();
        $response = $action->delete($request, $response, $aArgs);
        
        $this->assertSame((string)$response->getBody(), '[true]');
    }

    public function testCreate()
    {
        $action = new \Core\Controllers\ResController();

        $environment = \Slim\Http\Environment::mock(
            [
                'REQUEST_METHOD' => 'POST',
            ]
        );

        $path = $_SESSION['config']['tmppath'] . '/test/';

        if (!is_dir($path)) {
            mkdir($path);
        }

        $fileSource = 'test_source.txt';

        $fp = fopen($path . $fileSource, 'a');
        fwrite($fp, 'a unit test');
        fclose($fp);

        $fileContent = file_get_contents($path . $fileSource, FILE_BINARY);
        $encodedFile = base64_encode($fileContent);
        //echo $encodedFile . PHP_EOL;exit;
        
        $data = [];

        array_push(
            $data,
            array(
                'column' => 'subject',
                'value' => 'UNIT TEST from slim',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'type_id',
                'value' => 110,
                'type' => 'integer',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'custom_t1',
                'value' => 'TEST',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'custom_t10',
                'value' => 'lgi@maarch.org',
                'type' => 'string',
            )
        );
        //print_r(json_encode($data));
        //sample in json : [{"column":"subject","value":"UNIT T
        //EST from slim","type":"string"},{"column":"type_id","value":110,"type":"integer"},
        //{"column":"custom_t1","value":"TES
        //T","type":"string"},{"column":"custom_t10","value":"lgi@maarch.org","type":"string"}]

        $aArgs = [
            'encodedFile'   => $encodedFile,
            'data'          => $data,
            'collId'        => 'letterbox_coll',
            'table'         => 'res_letterbox',
            'fileFormat'    => 'txt',
            'status'        => 'new',
        ];

        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = new \Slim\Http\Response();
        $response = $action->create($request, $response, $aArgs);
        //print_r($response);exit;
        $this->assertGreaterThan(1, json_decode($response->getBody())->resId);
    }

    public function testDeleteRes()
    {
        $action = new \Core\Controllers\ResController();

        $resId = \Core\Models\ResModel::getLastId(['select' => 'res_id']);

        $aArgs = [
            'id'=> $resId[0]['res_id']
        ];

        $response = $action->deleteRes($aArgs);

        $this->assertTrue($response);
    }

    public function testUpdate()
    {
        $action = new \Core\Controllers\ResController();

        $environment = \Slim\Http\Environment::mock(
            [
                'REQUEST_METHOD' => 'POST',
            ]
        );
        
        $data = [];

        array_push(
            $data,
            array(
                'column' => 'status',
                'value' => 'NEW',
                'type' => 'string',
            )
        );

        $resId = \Core\Models\ResModel::getLastId(['select' => 'res_id']);

        $aArgs = [
            'table'         => 'res_letterbox',
            'res_id'        => $resId[0]['res_id'],
            'data'          => $data,
        ];

        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = new \Slim\Http\Response();
        $response = $action->update($request, $response, $aArgs);
        //print_r($response);exit;
        $this->assertGreaterThan(1, json_decode($response->getBody())[0]);
    }

    public function testUpdateResource()
    {
        $action = new \Core\Controllers\ResController();
        
        $data = [];

        array_push(
            $data,
            array(
                'column' => 'status',
                'value' => 'NEW',
                'type' => 'string',
            )
        );

        $resId = \Core\Models\ResModel::getLastId(['select' => 'res_id']);

        $aArgs = [
            'table'         => 'res_letterbox',
            'res_id'        => $resId[0]['res_id'],
            'data'          => $data,
        ];

        $response = $action->updateResource($aArgs);
        //print_r($response);exit;
        $this->assertGreaterThan(1, $response);
    }
}
