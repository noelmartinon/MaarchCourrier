<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class AttachmentTypeControllerTest extends TestCase
{
    private static $id = null;

    public function testGetAttachmentTypes()
    {
        $attachmentTypeController = new \Attachment\controllers\AttachmentTypeController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentTypeController->get($request, new \Slim\Http\Response());
        $response = json_decode((string)$response->getBody(), true);

        $this->assertNotNull($response['attachmentsTypes']);
        $this->assertIsArray($response['attachmentsTypes']);

        foreach ($response['attachmentsTypes'] as $value) {
            $this->assertNotNull($value['label']);
            $this->assertIsBool($value['signable']);
            $this->assertIsBool($value['chrono']);
            $this->assertIsBool($value['emailLink']);
            $this->assertIsBool($value['visible']);
        }
    }

    public function testCreate()
    {
        $attachmentTypeController = new \Attachment\controllers\AttachmentTypeController();

        //  CREATE SUCCESS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $body = [
            'typeId' => 'type_test',
            'label' => 'Type Test TU'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentTypeController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsInt($responseBody['id']);
        self::$id = $responseBody['id'];

        // ERRORS
        $body = [];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentTypeController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body is not set or empty', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $body = ['label' => 'Type TU'];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentTypeController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body typeId is empty or not a string', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $body = ['typeId' => 'type_test'];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentTypeController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body label is empty or not a string', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $body = [
            'typeId' => 'type_test',
            'label' => 'Type Test TU 2'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentTypeController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body typeId is already used by another type', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $attachmentTypeController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Service forbidden', $responseBody['errors']);
        $this->assertSame(403, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testUpdate()
    {
        $attachmentTypeController = new \Attachment\controllers\AttachmentTypeController();

        //  UPDATE SUCCESS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $body = [
            'typeId'            => 'type_test_up',
            'label'             => 'Type Test TU UP',
            'visible'           => true,
            'emailLink'         => false,
            'signable'          => true,
            'chrono'            => false,
            'versionEnabled'    => true,
            'newVersionDefault' => false,
            'icon'              => 'TU'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentTypeController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        // ERRORS
        $body = [];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentTypeController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body is not set or empty', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $body = ['typeId' => 'type_test'];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentTypeController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body label is empty or not a string', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $body = ['label' => 'Type Test TU UP'];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentTypeController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id * 1000]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment type does not exist', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $attachmentTypeController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Service forbidden', $responseBody['errors']);
        $this->assertSame(403, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetById()
    {
        $attachmentTypeController = new \Attachment\controllers\AttachmentTypeController();

        //  GET SUCCESS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $attachmentTypeController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame(self::$id, $responseBody['id']);
        $this->assertSame('type_test', $responseBody['typeId']);
        $this->assertSame('Type Test TU UP', $responseBody['label']);
        $this->assertSame(true, $responseBody['visible']);
        $this->assertSame(false, $responseBody['emailLink']);
        $this->assertSame(true, $responseBody['signable']);
        $this->assertSame(false, $responseBody['chrono']);
        $this->assertSame(true, $responseBody['versionEnabled']);
        $this->assertSame(false, $responseBody['newVersionDefault']);
        $this->assertSame('TU', $responseBody['icon']);

        $response     = $attachmentTypeController->getById($request, new \Slim\Http\Response(), ['id' => self::$id * 1000]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment type does not exist', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDelete()
    {
        $attachmentTypeController = new \Attachment\controllers\AttachmentTypeController();

        //  DELETE SUCCESS
        $environment  = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request      = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $attachmentTypeController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        //  DELETE ERRORS
        $response     = $attachmentTypeController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment type does not exist', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $responseProjectType = \Attachment\models\AttachmentTypeModel::getByTypeId(['typeId' => 'response_project', 'select' => ['id']]);

        $response     = $attachmentTypeController->delete($request, new \Slim\Http\Response(), ['id' => $responseProjectType['id']]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Type is used in attachments', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $attachmentTypeController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Service forbidden', $responseBody['errors']);
        $this->assertSame(403, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        //  READ
        $response = $attachmentTypeController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $res = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment type does not exist', $res['errors']);
        $this->assertSame(400, $response->getStatusCode());
    }
}
