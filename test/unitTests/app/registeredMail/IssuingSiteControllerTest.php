<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

use PHPUnit\Framework\TestCase;

class IssuingSiteControllerTest extends TestCase
{
    private static $id = null;

    public function testCreate()
    {
        $issuingSiteController = new \Recommended\controllers\IssuingSiteController();

        //  CREATE
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);

        $body = [
            'siteLabel'          => 'Scranton',
            'postOfficeLabel'    => 'Scranton Post Office',
            'accountNumber'      => 42,
            'addressName'        => 'Dunder Mifflin Scranton',
            'addressStreet'      => '1725',
            'ddressAdditional1'  => null,
            'addressAdditional2' => null,
            'addressPostcode'    => '18505',
            'addressTown'        => 'Scranton',
            'addressCountry'     => 'USA',
            'entities'           => [6]
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertIsInt($responseBody['id']);

        self::$id = $responseBody['id'];

        //  READ
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = $issuingSiteController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertNotEmpty($responseBody['site']);
        $this->assertSame(self::$id, $responseBody['site']['id']);
        $this->assertSame('Scranton', $responseBody['site']['siteLabel']);
        $this->assertSame('Scranton Post Office', $responseBody['site']['postOfficeLabel']);
        $this->assertSame('42', $responseBody['site']['accountNumber']);
        $this->assertSame('Dunder Mifflin Scranton', $responseBody['site']['addressName']);
        $this->assertSame('1725', $responseBody['site']['addressStreet']);
        $this->assertEmpty($responseBody['site']['ddressAdditional1']);
        $this->assertEmpty($responseBody['site']['ddressAdditional2']);
        $this->assertSame('18505', $responseBody['site']['addressPostcode']);
        $this->assertSame('Scranton', $responseBody['site']['addressTown']);
        $this->assertSame('USA', $responseBody['site']['addressCountry']);
        $this->assertNotEmpty($responseBody['site']['entities']);
        $this->assertSame(1, count($responseBody['site']['entities']));
        $this->assertSame(6, $responseBody['site']['entities'][0]);

        // fail
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);

        $body = [
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body siteLabel is empty or not a string', $responseBody['errors']);

        $body = [
            'siteLabel'          => 'Scranton',
            'postOfficeLabel'    => 'Scranton Post Office',
            'accountNumber'      => 42,
            'addressName'        => 'Dunder Mifflin Scranton',
            'addressStreet'      => '1725',
            'ddressAdditional1'  => null,
            'addressAdditional2' => null,
            'addressPostcode'    => '18505',
            'addressTown'        => 'Scranton',
            'addressCountry'     => 'USA',
            'entities'           => 'toto'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body entities is not an array', $responseBody['errors']);

        $body = [
            'siteLabel'          => 'Scranton',
            'postOfficeLabel'    => 'Scranton Post Office',
            'accountNumber'      => 42,
            'addressName'        => 'Dunder Mifflin Scranton',
            'addressStreet'      => '1725',
            'ddressAdditional1'  => null,
            'addressAdditional2' => null,
            'addressPostcode'    => '18505',
            'addressTown'        => 'Scranton',
            'addressCountry'     => 'USA',
            'entities'           => ['toto']
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body entities[0] is not an integer', $responseBody['errors']);

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $body = [
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(403, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Service forbidden', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGet()
    {
        $issuingSiteController = new \Recommended\controllers\IssuingSiteController();

        //  GET
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = $issuingSiteController->get($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody['sites']);
        $this->assertNotEmpty($responseBody['sites']);

        $this->assertIsArray($responseBody['sites'][0]);
        $this->assertNotEmpty($responseBody['sites'][0]);

        $this->assertSame(self::$id, $responseBody['sites'][0]['id']);
        $this->assertSame('Scranton', $responseBody['sites'][0]['siteLabel']);
        $this->assertSame('Scranton Post Office', $responseBody['sites'][0]['postOfficeLabel']);
        $this->assertSame('42', $responseBody['sites'][0]['accountNumber']);
        $this->assertSame('Dunder Mifflin Scranton', $responseBody['sites'][0]['addressName']);
        $this->assertSame('1725', $responseBody['sites'][0]['addressStreet']);
        $this->assertEmpty($responseBody['sites'][0]['ddressAdditional1']);
        $this->assertEmpty($responseBody['sites'][0]['ddressAdditional2']);
        $this->assertSame('18505', $responseBody['sites'][0]['addressPostcode']);
        $this->assertSame('Scranton', $responseBody['sites'][0]['addressTown']);
        $this->assertSame('USA', $responseBody['sites'][0]['addressCountry']);

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $issuingSiteController->get($request, new \Slim\Http\Response());
        $this->assertSame(403, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Service forbidden', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetById()
    {
        $issuingSiteController = new \Recommended\controllers\IssuingSiteController();

        //  GET
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = $issuingSiteController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody['site']);
        $this->assertNotEmpty($responseBody['site']);

        $this->assertSame(self::$id, $responseBody['site']['id']);
        $this->assertSame('Scranton', $responseBody['site']['siteLabel']);
        $this->assertSame('Scranton Post Office', $responseBody['site']['postOfficeLabel']);
        $this->assertSame('42', $responseBody['site']['accountNumber']);
        $this->assertSame('Dunder Mifflin Scranton', $responseBody['site']['addressName']);
        $this->assertSame('1725', $responseBody['site']['addressStreet']);
        $this->assertEmpty($responseBody['site']['ddressAdditional1']);
        $this->assertEmpty($responseBody['site']['ddressAdditional2']);
        $this->assertSame('18505', $responseBody['site']['addressPostcode']);
        $this->assertSame('Scranton', $responseBody['site']['addressTown']);
        $this->assertSame('USA', $responseBody['site']['addressCountry']);
        $this->assertNotEmpty($responseBody['site']['entities']);
        $this->assertSame(1, count($responseBody['site']['entities']));
        $this->assertSame(6, $responseBody['site']['entities'][0]);

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $issuingSiteController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(403, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Service forbidden', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testUpdate()
    {
        $issuingSiteController = new \Recommended\controllers\IssuingSiteController();

        //  UPDATE
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);

        $body = [
            'siteLabel'          => 'Scranton - UP',
            'postOfficeLabel'    => 'Scranton Post Office',
            'accountNumber'      => 42,
            'addressName'        => 'Dunder Mifflin Scranton',
            'addressStreet'      => '1725',
            'ddressAdditional1'  => null,
            'addressAdditional2' => null,
            'addressPostcode'    => '18505',
            'addressTown'        => 'Scranton',
            'addressCountry'     => 'USA',
            'entities'           => [6, 7]
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        //  READ
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = $issuingSiteController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertNotEmpty($responseBody['site']);
        $this->assertSame(self::$id, $responseBody['site']['id']);
        $this->assertSame('Scranton - UP', $responseBody['site']['siteLabel']);
        $this->assertSame('Scranton Post Office', $responseBody['site']['postOfficeLabel']);
        $this->assertSame('42', $responseBody['site']['accountNumber']);
        $this->assertSame('Dunder Mifflin Scranton', $responseBody['site']['addressName']);
        $this->assertSame('1725', $responseBody['site']['addressStreet']);
        $this->assertEmpty($responseBody['site']['ddressAdditional1']);
        $this->assertEmpty($responseBody['site']['ddressAdditional2']);
        $this->assertSame('18505', $responseBody['site']['addressPostcode']);
        $this->assertSame('Scranton', $responseBody['site']['addressTown']);
        $this->assertSame('USA', $responseBody['site']['addressCountry']);
        $this->assertNotEmpty($responseBody['site']['entities']);
        $this->assertSame(2, count($responseBody['site']['entities']));
        $this->assertSame(6, $responseBody['site']['entities'][0]);
        $this->assertSame(7, $responseBody['site']['entities'][1]);

        // fail
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);

        $body = [
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body siteLabel is empty or not a string', $responseBody['errors']);

        $body = [
            'siteLabel'          => 'Scranton',
            'postOfficeLabel'    => 'Scranton Post Office',
            'accountNumber'      => 42,
            'addressName'        => 'Dunder Mifflin Scranton',
            'addressStreet'      => '1725',
            'ddressAdditional1'  => null,
            'addressAdditional2' => null,
            'addressPostcode'    => '18505',
            'addressTown'        => 'Scranton',
            'addressCountry'     => 'USA',
            'entities'           => 'toto'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body entities is not an array', $responseBody['errors']);

        $body = [
            'siteLabel'          => 'Scranton',
            'postOfficeLabel'    => 'Scranton Post Office',
            'accountNumber'      => 42,
            'addressName'        => 'Dunder Mifflin Scranton',
            'addressStreet'      => '1725',
            'ddressAdditional1'  => null,
            'addressAdditional2' => null,
            'addressPostcode'    => '18505',
            'addressTown'        => 'Scranton',
            'addressCountry'     => 'USA',
            'entities'           => ['toto']
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body entities[0] is not an integer', $responseBody['errors']);

        $response = $issuingSiteController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id * 1000]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Issuing site not found', $responseBody['errors']);

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $body = [
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(403, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Service forbidden', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testDelete()
    {
        $issuingSiteController = new \Recommended\controllers\IssuingSiteController();

        //  DELETE
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $issuingSiteController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        $response = $issuingSiteController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        //  READ
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request = \Slim\Http\Request::createFromEnvironment($environment);
        $response = $issuingSiteController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Issuing site not found', $responseBody['errors']);

        // Fail
        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $body = [
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response = $issuingSiteController->delete($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(403, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Service forbidden', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }
}
