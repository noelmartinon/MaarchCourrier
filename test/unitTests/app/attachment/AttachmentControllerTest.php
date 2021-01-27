<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class AttachmentControllerTest extends TestCase
{
    private static $id = null;

    public function testCreate()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $aArgs = [
            'title'         => 'Nulle pierre ne peut être polie sans friction, nul homme ne peut parfaire son expérience sans épreuve.',
            'type'          => 'response_project',
            'chrono'        => 'MAARCH/2019D/24',
            'resIdMaster'   => 100,
            'encodedFile'   => $encodedFile,
            'format'        => 'txt',
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $attachmentController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        self::$id = $responseBody->id;
        $this->assertIsInt(self::$id);

        // CHECK ERROR EMPTY TYPE
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request     = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgsFail   = $aArgs;
        unset($aArgsFail['type']);
        $fullRequest = \httpRequestCustom::addContentInBody($aArgsFail, $request);
        $response = $attachmentController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());
        $response = json_decode((string)$response->getBody(), true);

        $this->assertSame('Body type is empty or not a string', $response['errors']);

        //  READ
        $res = \Attachment\models\AttachmentModel::getById(['id' => self::$id, 'select' => ['*']]);

        $this->assertIsArray($res);

        $this->assertSame($aArgs['title'], $res['title']);
        $this->assertSame($aArgs['type'], $res['attachment_type']);
        $this->assertSame('txt', $res['format']);
        $this->assertSame('A_TRA', $res['status']);
        $this->assertSame('superadmin', $res['typist']);
        $this->assertSame(1, $res['relation']);
        $this->assertSame($aArgs['chrono'], $res['identifier']);
        $this->assertNotNull($res['path']);
        $this->assertNotNull($res['filename']);
        $this->assertNotNull($res['docserver_id']);
        $this->assertNotNull($res['fingerprint']);
        $this->assertNotNull($res['filesize']);
        $this->assertNull($res['origin_id']);
    }

    public function testUpdate()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'title' => 'La plus chétive cabane renferme plus de vertus que les palais des rois.',
            'type'  => 'response_project',
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $attachmentController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        // CHECK ERROR EMPTY TYPE
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request     = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgsFail   = $aArgs;
        unset($aArgsFail['type']);
        $fullRequest = \httpRequestCustom::addContentInBody($aArgsFail, $request);

        $response     = $attachmentController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(400, $response->getStatusCode());
        $response = json_decode((string)$response->getBody(), true);

        $this->assertSame('Body type is empty or not a string', $response['errors']);

        //  READ
        $response = $attachmentController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $res = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($res);

        $this->assertSame($aArgs['title'], $res['title']);
        $this->assertSame($aArgs['type'], $res['type']);
        $this->assertSame('A_TRA', $res['status']);
        $this->assertSame(1, $res['relation']);
    }

    public function testDelete()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        //  DELETE
        $environment  = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request      = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $attachmentController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        //  DELETE
        $environment  = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request      = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $res      = json_decode((string)$response->getBody(), true);
        $this->assertSame(400, $response->getStatusCode());

        $this->assertSame('Attachment does not exist', $res['errors']);

        //  READ
        $response = $attachmentController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $res = json_decode((string)$response->getBody(), true);
        $this->assertSame(400, $response->getStatusCode());

        $this->assertSame('Attachment does not exist', $res['errors']);
    }
}
