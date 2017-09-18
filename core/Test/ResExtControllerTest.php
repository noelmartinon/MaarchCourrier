<?php

namespace MaarchTest;

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

require_once __DIR__.'/define.php';

class ResExtControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $resId = $this->createResArtefact();

        $action = new \Core\Controllers\ResExtController();

        $environment = \Slim\Http\Environment::mock(
            [
                'REQUEST_METHOD' => 'POST',
            ]
        );
        
        $data = [];

        array_push(
            $data,
            array(
                'column' => 'process_limit_date',
                'value' => '29/03/2017',
                'type' => 'date',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'process_notes',
                'value' => '50,workingDay',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'category_id',
                'value' => 'incoming',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'alt_identifier',
                'value' => '',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'exp_contact_id',
                'value' => 'jeanlouis.ercolani@maarch.org',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'address_id',
                'value' => 'jeanlouis.ercolani@maarch.org',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'nature_id',
                'value' => 'simple_mail',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'admission_date',
                'value' => date('d/m/Y'),
                'type' => 'date',
            )
        );

        $aArgs = [
            'resId'    => $resId,
            'data'     => $data,
            'table'    => 'mlb_coll_ext',
            'resTable' => 'res_letterbox',
        ];

        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = new \Slim\Http\Response();
        $response = $action->create($request, $response, $aArgs);
        
        $this->assertTrue(json_decode($response->getBody())->status);

        $this->deleteResArtefact($resId);
    }

    public function testDelete()
    {
        $action = new \Core\Controllers\ResExtController();

        $environment = \Slim\Http\Environment::mock(
            [
                'REQUEST_METHOD' => 'DELETE',
            ]
        );

        $resId = \Core\Models\ResExtModel::getLastId(['select' => 'res_id']);

        $aArgs = [
            'id'=> $resId[0]['res_id']
        ];

        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = new \Slim\Http\Response();
        $response = $action->delete($request, $response, $aArgs);
        
        $this->assertSame((string)$response->getBody(), '[true]');
    }

    public function testPrepareStorageExt()
    {
        $action = new \Core\Controllers\ResExtController();

        $data = [];

        array_push(
            $data,
            array(
                'column' => 'process_limit_date',
                'value' => '29/03/2017',
                'type' => 'date',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'process_notes',
                'value' => '50,workingDay',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'category_id',
                'value' => 'incoming',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'alt_identifier',
                'value' => '',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'exp_contact_id',
                'value' => 'jeanlouis.ercolani@maarch.org',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'address_id',
                'value' => 'jeanlouis.ercolani@maarch.org',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'nature_id',
                'value' => 'simple_mail',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'admission_date',
                'value' => date('d/m/Y'),
                'type' => 'date',
            )
        );

        $aArgs = [
            'resId' => 100,
            'data'  => $data,
            'table' => 'mlb_coll_ext',
        ];

        $response = $action->prepareStorageExt($aArgs);

        $this->assertArrayHasKey('res_id', $response);
    }

    public function testStoreExtResource()
    {
        $resId = $this->createResArtefact();

        $action = new \Core\Controllers\ResExtController();
        
        $data = [];

        array_push(
            $data,
            array(
                'column' => 'process_limit_date',
                'value' => '29/03/2017',
                'type' => 'date',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'process_notes',
                'value' => '50,workingDay',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'category_id',
                'value' => 'incoming',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'alt_identifier',
                'value' => '',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'exp_contact_id',
                'value' => 'jeanlouis.ercolani@maarch.org',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'address_id',
                'value' => 'jeanlouis.ercolani@maarch.org',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'nature_id',
                'value' => 'simple_mail',
                'type' => 'string',
            )
        );

        array_push(
            $data,
            array(
                'column' => 'admission_date',
                'value' => date('d/m/Y'),
                'type' => 'date',
            )
        );

        $aArgs = [
            'resId'    => $resId,
            'data'     => $data,
            'table'    => 'mlb_coll_ext',
            'resTable' => 'res_letterbox',
        ];

        $response = $action->storeExtResource($aArgs);
        
        $this->assertTrue($response);

        $this->deleteResArtefact($resId);
    }

    public function testDeleteExtRes()
    {
        $action = new \Core\Controllers\ResExtController();

        $resId = \Core\Models\ResExtModel::getLastId(['select' => 'res_id']);

        $aArgs = [
            'id'=> $resId[0]['res_id']
        ];

        $response = $action->deleteExtRes($aArgs);

        $this->assertTrue($response);
    }

    public function createResArtefact()
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

        return $response[0];
    }

    public function deleteResArtefact($resId)
    {
        $action = new \Core\Controllers\ResController();

        $aArgs = [
            'id' => $resId
        ];

        $response = $action->deleteRes($aArgs);
    }
}
