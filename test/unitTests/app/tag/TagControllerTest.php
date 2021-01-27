<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

use PHPUnit\Framework\TestCase;

class TagControllerTest extends TestCase
{
    private static $id = null;

    public function testCreate()
    {
        $tagController = new \Tag\controllers\TagController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'label'    => 'TEST_LABEL'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $tagController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        self::$id = $responseBody->id;

        $statusCode = $response->getStatusCode();

        $this->assertIsInt(self::$id);
        $this->assertSame(200, $statusCode);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $tagController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);

        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsInt($responseBody->id);
        $this->assertSame(self::$id, $responseBody->id);
        $this->assertIsString($responseBody->label);
        $this->assertSame('TEST_LABEL', $responseBody->label);

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'label'    => ''
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $tagController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $statusCode = $response->getStatusCode();

        $this->assertSame(400, $statusCode);

        $this->assertIsString($responseBody->errors);
        $this->assertSame('Body label is empty or not a string', $responseBody->errors);
    }

    public function testGetById()
    {
        $tagController = new \Tag\controllers\TagController();

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $tagController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);

        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsInt($responseBody->id);
        $this->assertIsString($responseBody->label);

        //  READ fail
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $tagController->getById($request, new \Slim\Http\Response(), ['id' => 'test']);

        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsString($responseBody->errors);
        $this->assertSame('Route id must be an integer val', $responseBody->errors);
    }

    public function testUpdate()
    {
        $tagController = new \Tag\controllers\TagController();

        //  Update working
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'label'    => 'TEST_LABEL_2'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $tagController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);

        $this->assertSame(204, $response->getStatusCode());

        // Update fail
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'label'    => ''
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $tagController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);

        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsString($responseBody->errors);
        $this->assertSame('Body label is empty or not a string', $responseBody->errors);

        //  Update fail
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $tagController->getById($request, new \Slim\Http\Response(), ['id' => 'test']);

        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsString($responseBody->errors);
        $this->assertSame('Route id must be an integer val', $responseBody->errors);
    }

    public function testDelete()
    {
        //  DELETE
        $environment  = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request      = \Slim\Http\Request::createFromEnvironment($environment);

        $tagController = new \Tag\controllers\TagController();
        $response         = $tagController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);

        $this->assertSame(204, $response->getStatusCode());

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $tagController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);

        $this->assertSame(404, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsString($responseBody->errors);
        $this->assertSame('id not found', $responseBody->errors);

        // FAIL DELETE
        $tagController = new \Tag\controllers\TagController();
        $response         = $tagController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody     = json_decode((string)$response->getBody());

        $this->assertSame('Tag does not exist', $responseBody->errors);
        $this->assertSame(400, $response->getStatusCode());

        //  DELETE fail
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $tagController->getById($request, new \Slim\Http\Response(), ['id' => 'test']);

        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsString($responseBody->errors);
        $this->assertSame('Route id must be an integer val', $responseBody->errors);
    }

    public function testGet()
    {
        $tagController = new \Tag\controllers\TagController();

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $tagController->get($request, new \Slim\Http\Response(), ['id' => self::$id]);

        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->tags);
        $this->assertNotEmpty($responseBody->tags);

        $tags = $responseBody->tags;

        foreach ($tags as $value) {
            $this->assertIsInt($value->id);
            $this->assertIsString($value->label);
        }
    }
}
