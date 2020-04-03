<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class ContactCustomFieldControllerTest extends TestCase
{
    private static $id = null;

    public function testCreate()
    {
        $contactCustomFieldController = new \Contact\controllers\ContactCustomFieldController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $args = [
            'label'     => 'mon custom',
            'type'      => 'select',
            'values'    => ['one', 'two']
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);

        $response     = $contactCustomFieldController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsInt($responseBody['id']);

        self::$id = $responseBody['id'];

        //  Errors
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $args = [
            'label'     => 'mon custom',
            'type'      => 'select',
            'values'    => ['one', 'two']
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);

        $response     = $contactCustomFieldController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Custom field with this label already exists', $responseBody['errors']);
    }

    public function testReadList()
    {
        $contactCustomFieldController = new \Contact\controllers\ContactCustomFieldController();

        $environment  = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request      = \Slim\Http\Request::createFromEnvironment($environment);

        $response         = $contactCustomFieldController->get($request, new \Slim\Http\Response());
        $responseBody     = json_decode((string)$response->getBody(), true);

        $this->assertNotNull($responseBody['customFields']);
    }

    public function testUpdate()
    {
        $contactCustomFieldController = new \Contact\controllers\ContactCustomFieldController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $args = [
            'label'     => 'mon custom22',
            'values'    => ['one', 'two', 'trois']
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);

        $response     = $contactCustomFieldController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        //  Errors
        unset($args['label']);
        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);

        $response     = $contactCustomFieldController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Body label is empty or not a string', $responseBody['errors']);
    }

    public function testDelete()
    {
        $contactCustomFieldController = new \Contact\controllers\ContactCustomFieldController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);


        $response     = $contactCustomFieldController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());
    }
}