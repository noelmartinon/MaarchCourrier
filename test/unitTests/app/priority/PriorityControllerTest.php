<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

use PHPUnit\Framework\TestCase;

class PriorityControllerTest extends TestCase
{
    private static $id = null;

    public function testCreate()
    {
        $priorityController = new \Priority\controllers\PriorityController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'label'             => 'TEST-OVER-URGENT',
            'color'             => '#ffffff',
            'delays'            => '72',
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $priorityController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        self::$id = $responseBody->priority;

        $this->assertIsString(self::$id);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $priorityController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->priority->id);
        $this->assertSame('TEST-OVER-URGENT', $responseBody->priority->label);
        $this->assertSame('#ffffff', $responseBody->priority->color);
        $this->assertSame(72, $responseBody->priority->delays);
    }

    public function testGet()
    {
        $priorityController = new \Priority\controllers\PriorityController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $priorityController->get($request, new \Slim\Http\Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->priorities);
        $this->assertNotNull($responseBody->priorities);
    }

    public function testUpdate()
    {
        $priorityController = new \Priority\controllers\PriorityController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'label'             => 'TEST-OVER-URGENT-UPDATED',
            'color'             => '#f2f2f2',
            'delays'            => '64',
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $priorityController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('success', $responseBody->success);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $priorityController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->priority->id);
        $this->assertSame('TEST-OVER-URGENT-UPDATED', $responseBody->priority->label);
        $this->assertSame('#f2f2f2', $responseBody->priority->color);
        $this->assertSame(64, $responseBody->priority->delays);
    }

    public function testDelete()
    {
        $priorityController = new \Priority\controllers\PriorityController();

        //  DELETE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $priorityController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->priorities);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $priorityController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame('Priority not found', $responseBody->errors);
    }

    public function testGetSorted()
    {
        $priorityController = new \Priority\controllers\PriorityController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $priorityController->getSorted($request, new \Slim\Http\Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->priorities);
        $this->assertNotEmpty($responseBody->priorities);
        
        foreach ($responseBody->priorities as $value) {
            $this->assertNotEmpty($value->id);
            $this->assertNotEmpty($value->label);
        }
    }
}
