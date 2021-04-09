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
    private static $originalAttachmentId = null;
    private static $versionAttachmentId = null;

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
        self::$originalAttachmentId = $responseBody->id;
        $this->assertIsInt(self::$originalAttachmentId);

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
        $res = \Attachment\models\AttachmentModel::getById(['id' => self::$originalAttachmentId, 'select' => ['*']]);

        $this->assertIsArray($res);

        $this->assertSame($aArgs['title'], $res['title']);
        $this->assertSame($aArgs['type'], $res['attachment_type']);
        $this->assertSame('txt', $res['format']);
        $this->assertSame('A_TRA', $res['status']);
        $this->assertSame(23, (int)$res['typist']);
        $this->assertSame(1, $res['relation']);
        $this->assertSame($aArgs['chrono'], $res['identifier']);
        $this->assertNotNull($res['path']);
        $this->assertNotNull($res['filename']);
        $this->assertNotNull($res['docserver_id']);
        $this->assertNotNull($res['fingerprint']);
        $this->assertNotNull($res['filesize']);
        $this->assertNull($res['origin_id']);

        // Create version
        $aArgs = [
            'title'         => 'Nulle pierre ne peut être polie sans friction, nul homme ne peut parfaire son expérience sans épreuve.',
            'type'          => 'response_project',
            'chrono'        => 'MAARCH/2019D/24',
            'resIdMaster'   => 100,
            'encodedFile'   => $encodedFile,
            'format'        => 'txt',
            'originId'      => self::$originalAttachmentId
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $attachmentController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        self::$versionAttachmentId = $responseBody->id;
        $this->assertIsInt(self::$versionAttachmentId);
    }

    public function testGetById()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->getById($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($responseBody);

        $this->assertSame('Nulle pierre ne peut être polie sans friction, nul homme ne peut parfaire son expérience sans épreuve.', $responseBody['title']);
        $this->assertSame('response_project', $responseBody['type']);
        $this->assertSame('A_TRA', $responseBody['status']);
        $this->assertSame(2, $responseBody['relation']);

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $attachmentController->getById($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment out of perimeter', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testUpdate()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $aArgs = [
            'title'       => 'La plus chétive cabane renferme plus de vertus que les palais des rois.',
            'type'        => 'response_project',
            'encodedFile' => $encodedFile,
            'format'      => 'txt'
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $attachmentController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $this->assertSame(204, $response->getStatusCode());

        // CHECK ERROR EMPTY TYPE
        $environment = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request     = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgsFail   = $aArgs;
        unset($aArgsFail['type']);
        $fullRequest = \httpRequestCustom::addContentInBody($aArgsFail, $request);

        $response     = $attachmentController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $this->assertSame(400, $response->getStatusCode());
        $response = json_decode((string)$response->getBody(), true);

        $this->assertSame('Body type is empty or not a string', $response['errors']);

        $response     = $attachmentController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$originalAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment does not exist', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $response     = $attachmentController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId * 1000]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment does not exist', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $aArgs = [
            'title'       => 'La plus chétive cabane renferme plus de vertus que les palais des rois.',
            'type'        => 'response_project',
            'encodedFile' => $encodedFile
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $attachmentController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body format is empty or not a string', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $body = [];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body is not set or empty', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $body = ['type' => 'this_type_does_not_exist'];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $attachmentController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body type does not exist', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $attachmentController->update($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment out of perimeter', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        //  READ
        $response = $attachmentController->getById($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $res = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($res);

        $this->assertSame($aArgs['title'], $res['title']);
        $this->assertSame($aArgs['type'], $res['type']);
        $this->assertSame('A_TRA', $res['status']);
        $this->assertSame(2, $res['relation']);
    }

    public function testGetByResId()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->getByResId($request, new \Slim\Http\Response(), ['resId' => 100]);
        $this->assertSame(200, $response->getStatusCode());
        $response = json_decode((string)$response->getBody(), true);

        $this->assertNotNull($response['attachments']);
        $this->assertIsArray($response['attachments']);

        $this->assertIsBool($response['mailevaEnabled']);

        foreach ($response['attachments'] as $value) {
            if ($value['resId'] == self::$versionAttachmentId) {
                $userInfo = \User\models\UserModel::getByLogin(['login' => 'superadmin', 'select' => ['id']]);
                $this->assertSame('La plus chétive cabane renferme plus de vertus que les palais des rois.', $value['title']);
                $this->assertSame('response_project', $value['type']);
                $this->assertSame('A_TRA', $value['status']);
                $this->assertSame($userInfo['id'], (int)$value['typist']);
                $this->assertSame(2, $value['relation']);
                $this->assertSame('MAARCH/2019D/24', $value['chrono']);
                $this->assertNull($value['originId']);
                $this->assertNotNull($value['modificationDate']);
                $this->assertNotNull($value['modifiedBy']);
                $this->assertNotNull($value['typeLabel']);
                $this->assertIsBool($value['canConvert']);
                break;
            }
        }

        // ERROR
        $fullRequest = $request->withQueryParams(['limit' => 'not_an_integer']);
        $response = $attachmentController->getByResId($fullRequest, new \Slim\Http\Response(), ['resId' => 100]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Query limit is not an integer', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $GLOBALS['login'] = 'ddur';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $attachmentController->getByResId($request, new \Slim\Http\Response(), ['resId' => 123940595]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Document out of perimeter', $responseBody['errors']);
        $this->assertSame(403, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetThumbnailContent()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->getThumbnailContent($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $headers = $response->getHeaders();

        $this->assertSame('inline; filename=maarch.png', $headers['Content-Disposition'][0]);
        $this->assertSame('image/png', $headers['Content-Type'][0]);

        // ERROR
        $response = $attachmentController->getThumbnailContent($request, new \Slim\Http\Response(), ['id' => 123940595]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment not found', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $response = $attachmentController->getThumbnailContent($request, new \Slim\Http\Response(), ['id' => 'not_an_integer']);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Route id is not an integer', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $attachmentController->getThumbnailContent($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Document out of perimeter', $responseBody['errors']);
        $this->assertSame(403, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetThumbnailContentByPage()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->getThumbnailContentByPage($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode($response->getBody(), true);
        $this->assertNotEmpty($responseBody['fileContent']);
        $this->assertSame(1, $responseBody['pageCount']);
        $this->assertSame(200, $response->getStatusCode());

        // ERROR
        $response = $attachmentController->getThumbnailContentByPage($request, new \Slim\Http\Response(), ['id' => 123940595]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Document does not exist', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $response = $attachmentController->getThumbnailContentByPage($request, new \Slim\Http\Response(), ['id' => 'not_an_integer']);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('id param is not an integer', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $attachmentController->getThumbnailContentByPage($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Document out of perimeter', $responseBody['errors']);
        $this->assertSame(403, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetOriginalFileContent()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->getOriginalFileContent($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $headers = $response->getHeaders();
        $this->assertSame('attachment; filename=La plus chétive cabane renferme plus de vertus que les palais des rois._V2.txt', $headers['Content-Disposition'][0]);
        $this->assertSame('text/plain', $headers['Content-Type'][0]);
        $this->assertSame(200, $response->getStatusCode());

        $queryParams = [
            "mode" => "base64"
        ];
        $fullRequest = $request->withQueryParams($queryParams);
        $response = $attachmentController->getOriginalFileContent($fullRequest, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertNotEmpty($responseBody['encodedDocument']);
        $this->assertSame('txt', $responseBody['extension']);
        $this->assertSame('text/plain', $responseBody['mimeType']);
        $this->assertSame('La plus chétive cabane renferme plus de vertus que les palais des rois._V2.txt', $responseBody['filename']);
        $this->assertSame(200, $response->getStatusCode());

        // ERROR
        $response = $attachmentController->getOriginalFileContent($request, new \Slim\Http\Response(), ['id' => 123940595]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment not found', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $response = $attachmentController->getOriginalFileContent($request, new \Slim\Http\Response(), ['id' => 'not_an_integer']);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Route id is not an integer', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $attachmentController->getOriginalFileContent($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Document out of perimeter', $responseBody['errors']);
        $this->assertSame(403, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetFileContent()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        // GET
        $aArgs = [
            "mode" => "base64"
        ];
        $fullRequest = $request->withQueryParams($aArgs);
        $response = $attachmentController->getFileContent($fullRequest, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $response = json_decode((string)$response->getBody(), true);
        $this->assertSame('txt', $response['originalFormat']);
        $this->assertNotEmpty($response['encodedDocument']);

        // ERRORS
        $response = $attachmentController->getFileContent($request, new \Slim\Http\Response(), ['id' => 123940595]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment not found', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $response = $attachmentController->getFileContent($request, new \Slim\Http\Response(), ['id' => 'not_an_integer']);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Route id is not an integer', $responseBody['errors']);
        $this->assertSame(400, $response->getStatusCode());

        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $attachmentController->getFileContent($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Document out of perimeter', $responseBody['errors']);
        $this->assertSame(403, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetByChrono()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        // GET
        $aArgs = [
            "chrono" => "MAARCH/2019D/24"
        ];
        $fullRequest = $request->withQueryParams($aArgs);
        $response = $attachmentController->getByChrono($fullRequest, new \Slim\Http\Response());
        $response = json_decode((string)$response->getBody(), true);
        $this->assertIsInt($response['resId']);
        $this->assertIsInt($response['resIdMaster']);
        $this->assertSame('A_TRA', $response['status']);
        $this->assertSame('La plus chétive cabane renferme plus de vertus que les palais des rois.', $response['title']);

        //Error
        $fullRequest = $request->withQueryParams([]);
        $response = $attachmentController->getByChrono($fullRequest, new \Slim\Http\Response());
        $response = json_decode((string)$response->getBody(), true);
        $this->assertSame('Query chrono is not set', $response['errors']);

        //Error
        $aArgs = [
            "chrono" => "MAARCH/2019D/249888765"
        ];
        $fullRequest = $request->withQueryParams($aArgs);
        $response = $attachmentController->getByChrono($fullRequest, new \Slim\Http\Response());
        $response = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment does not exist', $response['errors']);

        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $queryParams = [
            "chrono" => "MAARCH/2019D/24"
        ];
        $fullRequest = $request->withQueryParams($queryParams);
        $response = $attachmentController->getByChrono($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment out of perimeter', $responseBody['errors']);
        $this->assertSame(403, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testEncodedDocument()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        // GET
        $response = $attachmentController::getEncodedDocument(['id' => self::$versionAttachmentId, 'original' => false]);
        $this->assertNotEmpty($response['encodedDocument']);
    }

    public function testMailing()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        // ERROR
        $response = $attachmentController->getMailingById($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $response = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment is not candidate to mailing', $response['errors']);

        // CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $fileContent = file_get_contents('modules/templates/templates/styles/AR_Masse_Simple.docx');
        $encodedFile = base64_encode($fileContent);

        $aArgs = [
            'title'         => 'Sujet de Mailing',
            'type'          => 'response_project',
            'chrono'        => 'MAARCH/2019D/38',
            'resIdMaster'   => 100,
            'encodedFile'   => $encodedFile,
            'format'        => 'docx',
            'status'        => 'SEND_MASS'
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $attachmentController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $mailingId = $responseBody->id;
        $this->assertIsInt($mailingId);

        // GET
        $response = $attachmentController->getMailingById($request, new \Slim\Http\Response(), ['id' => $mailingId]);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testSetInSignatureBook()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->setInSignatureBook($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $response = json_decode((string)$response->getBody(), true);
        $this->assertSame('success', $response['success']);

        // ERROR
        $response = $attachmentController->setInSignatureBook($request, new \Slim\Http\Response(), ['id' => 123940595]);
        $response = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment not found', $response['errors']);

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $attachmentController->setInSignatureBook($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Document out of perimeter', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testSetInSendAttachment()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->setInSendAttachment($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $response = json_decode((string)$response->getBody(), true);
        $this->assertSame('success', $response['success']);

        // ERROR
        $response = $attachmentController->setInSendAttachment($request, new \Slim\Http\Response(), ['id' => 123940595]);
        $response = json_decode((string)$response->getBody(), true);
        $this->assertSame('Attachment not found', $response['errors']);

        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response = $attachmentController->setInSendAttachment($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Document out of perimeter', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetAttachmentTypes()
    {
        $attachmentController = new \Attachment\controllers\AttachmentTypeController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->get($request, new \Slim\Http\Response());
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

    public function testDelete()
    {
        $attachmentController = new \Attachment\controllers\AttachmentController();

        //  DELETE
        $environment  = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request      = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $attachmentController->delete($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $this->assertSame(204, $response->getStatusCode());

        //  DELETE
        $environment  = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request      = \Slim\Http\Request::createFromEnvironment($environment);

        $response = $attachmentController->delete($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $res      = json_decode((string)$response->getBody(), true);
        $this->assertSame(400, $response->getStatusCode());

        $this->assertSame('Attachment does not exist', $res['errors']);

        //  READ
        $response = $attachmentController->getById($request, new \Slim\Http\Response(), ['id' => self::$versionAttachmentId]);
        $res = json_decode((string)$response->getBody(), true);
        $this->assertSame(400, $response->getStatusCode());

        $this->assertSame('Attachment does not exist', $res['errors']);
    }
}
