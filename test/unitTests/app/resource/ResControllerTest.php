<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class ResControllerTest extends TestCase
{
    private static $id = null;

    public function testCreate()
    {
        $GLOBALS['login'] = 'cchaplin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $resController = new \Resource\controllers\ResController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $aArgs = [
            'modelId'       => 1,
            'status'        => 'NEW',
            'encodedFile'   => $encodedFile,
            'format'        => 'txt',
            'confidentiality'   => false,
            'documentDate'  => '2019-01-01 17:18:47',
            'arrivalDate'   => '2019-01-01 17:18:47',
            'processLimitDate'  => '2029-01-01',
            'doctype'       => 102,
            'destination'   => 15,
            'initiator'     => 15,
            'subject'       => 'Breaking News : Superman is alive - PHP unit',
            'typist'        => 19,
            'priority'      => 'poiuytre1357nbvc',
            'senders'       => [['type' => 'contact', 'id' => 1], ['type' => 'user', 'id' => 21], ['type' => 'entity', 'id' => 1]],
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        self::$id = $responseBody->resId;
        $this->assertIsInt(self::$id);

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id, 'select' => ['*']]);

        $this->assertIsArray($res);

        $this->assertSame('Breaking News : Superman is alive - PHP unit', $res['subject']);
        $this->assertSame(102, $res['type_id']);
        $this->assertSame('txt', $res['format']);
        $this->assertSame('NEW', $res['status']);
        $this->assertSame(19, $res['typist']);
        $this->assertNotNull($res['destination']);
        $this->assertNotNull($res['initiator']);

        //  ERROR
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $aArgs = [
            'status'        => 'NEW',
            'encodedFile'   => $encodedFile,
            'format'        => 'txt',
            'confidentiality'   => false,
            'documentDate'  => '2019-01-01 17:18:47',
            'arrivalDate'   => '2019-01-01 17:18:47',
            'processLimitDate'  => '2029-01-01',
            'doctype'       => 102,
            'destination'   => 15,
            'initiator'     => 15,
            'subject'       => 'Breaking News : Superman is alive - PHP unit',
            'typist'        => 19,
            'priority'      => 'poiuytre1357nbvc',
            'tags'          => [1, 2],
            'folders'       => [1, 2],
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Body modelId is empty or not an integer', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetById()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $resController->getById($request, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->resId);
        $this->assertSame(1, $responseBody->modelId);
        $this->assertSame('incoming', $responseBody->categoryId);
        $this->assertEmpty($responseBody->chrono);
        $this->assertSame('NEW', $responseBody->status);
        $this->assertEmpty($responseBody->closingDate);
        $this->assertNotEmpty($responseBody->creationDate);
        $this->assertNotEmpty($responseBody->modificationDate);
        $this->assertIsBool($responseBody->integrations->inShipping);
        $this->assertIsBool($responseBody->integrations->inSignatureBook);
        $this->assertSame('Breaking News : Superman is alive - PHP unit', $responseBody->subject);
        $this->assertSame('2029-01-01 00:00:00', $responseBody->processLimitDate);
        $this->assertSame('poiuytre1357nbvc', $responseBody->priority);
        $this->assertSame(102, $responseBody->doctype);
        $this->assertSame(15, $responseBody->destination);
        $this->assertSame('2019-01-01 17:18:47', $responseBody->documentDate);
        $this->assertSame('2019-01-01 17:18:47', $responseBody->arrivalDate);
        $this->assertNotEmpty($responseBody->destinationLabel);
        $this->assertSame("Nouveau courrier pour le service", $responseBody->statusLabel);
        $this->assertIsBool($responseBody->statusAlterable);
        $this->assertSame('Normal', $responseBody->priorityLabel);
        $this->assertSame('#009dc5', $responseBody->priorityColor);
        $this->assertIsArray($responseBody->senders);
        $this->assertIsArray($responseBody->customFields);
        $this->assertIsArray($responseBody->folders);
        foreach ($responseBody->folders as $value) {
            $this->assertIsInt($value);
        }
        $this->assertIsArray($responseBody->tags);
        foreach ($responseBody->tags as $value) {
            $this->assertIsInt($value);
        }
        
        // ERROR
        $GLOBALS['login'] = 'cchaplin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $resController->getById($request, new \Slim\Http\Response(), ['resId' => 123748]);
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Document out of perimeter', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        // LIGHT
        $aArgs = [
            'light'  => true
        ];
        $fullRequest = $request->withQueryParams($aArgs);
        $response     = $resController->getById($fullRequest, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->resId);
        $this->assertSame(1, $responseBody->modelId);
        $this->assertSame('incoming', $responseBody->categoryId);
        $this->assertEmpty($responseBody->chrono);
        $this->assertSame('NEW', $responseBody->status);
        $this->assertEmpty($responseBody->closingDate);
        $this->assertNotEmpty($responseBody->creationDate);
        $this->assertNotEmpty($responseBody->modificationDate);
        $this->assertIsBool($responseBody->integrations->inShipping);
        $this->assertIsBool($responseBody->integrations->inSignatureBook);
        $this->assertSame('Breaking News : Superman is alive - PHP unit', $responseBody->subject);
        $this->assertSame('2029-01-01 00:00:00', $responseBody->processLimitDate);
        $this->assertSame('poiuytre1357nbvc', $responseBody->priority);
    }

    public function testUpdate()
    {
        $resController = new \Resource\controllers\ResController();

        // UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'modelId'       => 1,
            'status'        => 'NEW',
            'confidentiality'   => true,
            'documentDate'  => '2019-01-01 17:18:47',
            'arrivalDate'   => '2019-01-01 17:18:47',
            'processLimitDate'  => '2030-01-01',
            'doctype'       => 102,
            'destination'   => 15,
            'initiator'     => 15,
            'subject'       => 'Breaking News : Superman is alive - PHP unit',
            'typist'        => 19,
            'priority'      => 'poiuytre1357nbvc',
            'senders'       => [['type' => 'contact', 'id' => 1], ['type' => 'user', 'id' => 21], ['type' => 'entity', 'id' => 1]],
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->update($fullRequest, new \Slim\Http\Response(), ['resId' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id, 'select' => ['*']]);

        $this->assertIsArray($res);

        $this->assertSame('Breaking News : Superman is alive - PHP unit', $res['subject']);
        $this->assertSame(102, $res['type_id']);
        $this->assertSame('txt', $res['format']);
        $this->assertSame('NEW', $res['status']);
        $this->assertSame(19, $res['typist']);
        $this->assertNotNull($res['destination']);
        $this->assertNotNull($res['initiator']);
        $this->assertSame('Y', $res['confidentiality']);
        $this->assertSame('2030-01-01 00:00:00', $res['process_limit_date']);

        //  ERROR
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $aArgs = [
            'status'        => 'NEW',
            'encodedFile'   => $encodedFile,
            'format'        => 'txt',
            'confidentiality'   => false,
            'documentDate'  => '2019-01-01 17:18:47',
            'arrivalDate'   => '2019-01-01 17:18:47',
            'processLimitDate'  => '2029-01-01',
            'destination'   => 15,
            'initiator'     => 15,
            'subject'       => 'Breaking News : Superman is alive - PHP unit',
            'typist'        => 19,
            'priority'      => 'poiuytre1357nbvc',
            'tags'          => [1, 2],
            'folders'       => [1, 2],
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->update($fullRequest, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Body doctype is empty or not an integer', $responseBody->errors);
    }
      
    public function testGetOriginalContent()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $resController->getOriginalFileContent($request, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(null, $responseBody);

        // GET FILE CONTENT
        $response     = $resController->getFileContent($request, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(null, $responseBody);

        // GET FILE CONTENT
        $aArgs = [
            'mode'  => 'base64'
        ];
        $fullRequest = $request->withQueryParams($aArgs);
        $response     = $resController->getFileContent($fullRequest, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->encodedDocument);
        $this->assertSame('txt', $responseBody->originalFormat);
        $this->assertNotEmpty($responseBody->originalCreatorId);

        // ERROR
        $response     = $resController->getFileContent($request, new \Slim\Http\Response(), ['resId' => -2]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Document does not exist', $responseBody->errors);
    }

    public function testGetThumbnailContent()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $resController->getThumbnailContent($request, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(null, $responseBody);

        $response     = $resController->getThumbnailContent($request, new \Slim\Http\Response(), ['resId' => -2]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Document does not exist', $responseBody->errors);
    }

    public function testGetCategories()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $resController->getCategories($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->categories);

        foreach ($responseBody->categories as $value) {
            $this->assertNotEmpty($value->id);
            $this->assertNotEmpty($value->label);
        }
    }

    public function testIsAllowedForCurrentUser()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $resController->isAllowedForCurrentUser($request, new \Slim\Http\Response(), ['resId' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(true, $responseBody->isAllowed);

        // NOT ALLOWED
        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $resController->isAllowedForCurrentUser($request, new \Slim\Http\Response(), ['resId' => 123456]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(false, $responseBody->isAllowed);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testSetInIntegrations()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'resources'   => [self::$id],
            'integrations' => ['inSignatureBook' => true, 'inShipping' => true]
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
        $response     = $resController->setInIntegrations($fullRequest, new \Slim\Http\Response());

        $this->assertSame(204, $response->getStatusCode());

        // ERROR
        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $aArgs = [
            'resources'   => [12345],
            'integrations' => ['inSignatureBook' => true, 'inShipping' => true]
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
        $response     = $resController->setInIntegrations($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Document out of perimeter', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testUpdateStatus()
    {
        $resController = new \Resource\controllers\ResController();

        //  UPDATE STATUS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'resId'         => [self::$id],
            'status'        => 'EVIS'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->updateStatus($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('success', $responseBody->success);

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id, 'select' => ['*']]);
        $this->assertIsArray($res);
        $this->assertSame('EVIS', $res['status']);

        //  UPDATE WITHOUT STATUS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'resId'         => [self::$id]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->updateStatus($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('success', $responseBody->success);

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id, 'select' => ['*']]);
        $this->assertIsArray($res);
        $this->assertSame('COU', $res['status']);
    }

    public function testUpdateExternalInfos()
    {
        $resController = new \Resource\controllers\ResController();

        //  UPDATE STATUS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        //ALL OK
        $aArgs = [
                'externalInfos' => [
                    [
                        'res_id'        => self::$id,
                        'external_id'   => "BB981212IIYZ",
                        'external_link' => "https://publik.nancy.fr/res/BB981212BB65"
                    ]
                ],
                'status'        => "GRCSENT"
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame('success', $responseBody->success);

        // EXTERNAL INFOS EMPTY AND RES ID IS NOT INTEGER
        $aArgs = [
            'externalInfos' => [
                    [
                        'res_id'        => "res_id",
                        'external_id'   => "",
                        'external_link' => ""
                    ]
                ],
            'status'        => "GRCSENT"

        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame('Bad Request: invalid res_id', $responseBody->errors);

        // DOCUMENT DOES NOT EXIST
        $aArgs = [
            'externalInfos' => [
                        [
                            'res_id'        => 123456789,
                            'external_id'   => "BB981212IIYZ",
                            'external_link' => "https://publik.nancy.fr/res/BB981212BB65"
                        ]
                    ],
            'status'        => 'GRCSENT'
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame(_DOCUMENT_NOT_FOUND, $responseBody->errors);

        //MISSING STATUS
        $aArgs = [
                'externalInfos' => [
                    [
                        'res_id'        => self::$id,
                        'external_id'   => "BB981212IIYZ",
                        'external_link' => "https://publik.nancy.fr/res/BB981212BB65"
                    ]
                ],
                'status'        => null
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame('Bad Request : status is empty', $responseBody->errors);

        //MISSING EXTERNAL INFOS
        $aArgs = [
            'externalInfos' => null,
            'status'        => "GRCSENT"
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response = $resController->updateExternalInfos($fullRequest, new \Slim\Http\Response());

        $responseBody = json_decode((string) $response->getBody());

        $this->assertSame('Bad Request : externalInfos is empty', $responseBody->errors);
    }

    public function testGetList()
    {
        $resController = new \Resource\controllers\ResController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'select'        => 'res_id',
            'clause'        => '1=1',
            'withFile'      => true,
            'orderBy'       => ['res_id'],
            'limit'         => 1
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->getList($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $arr_res = $responseBody->resources;
        $this->assertNotNull($arr_res[0]->fileBase64Content);
        $this->assertIsInt($arr_res[0]->res_id);

        $aArgs = [
            'select'        => 'res_id',
            'clause'        => '1=1',
            'withFile'      => false,
            'orderBy'       => ['res_id'],
            'limit'         => 1
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->getList($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $arr_res = $responseBody->resources;
        $this->assertSame(null, $arr_res[0]->fileBase64Content);
        $this->assertIsInt($arr_res[0]->res_id);

        $aArgs = [
            'select'        => '',
            'clause'        => '1=1',
            'withFile'      => false,
            'orderBy'       => ['res_id'],
            'limit'         => 1
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->getList($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame("Bad Request: select is not valid", $responseBody->errors);

        $aArgs = [
            'select'        => 'res_id',
            'clause'        => '',
            'withFile'      => false,
            'orderBy'       => ['res_id'],
            'limit'         => 1
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->getList($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame("Bad Request: clause is not valid", $responseBody->errors);
    }

    public function testDelete()
    {
        //  DELETE
        \Resource\models\ResModel::update(['set' => ['status' => 'DEL'], 'where' => ['res_id = ?'], 'data' => [self::$id]]);

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id, 'select' => ['*']]);
        $this->assertIsArray($res);
        $this->assertSame('DEL', $res['status']);
    }

    public function testCreateMultipleDocument()
    {
        $GLOBALS['login'] = 'cchaplin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $resController = new \Resource\controllers\ResController();

        $aNewDocument = [
            1 => [
                102,
                'poiuytre1357nbvc',
                'NEW'
            ],
            2 => [
                103,
                'poiuytre1379nbvc',
                'COU'
            ],
            3 => [
                104,
                'poiuytre1391nbvc',
                'ENVDONE'
            ]
        ];

        $entity = \Entity\models\EntityModel::getByEntityId(['entityId' => 'PJS', 'select' => ['id']]);
        $this->assertIsInt($entity['id']);

        foreach ($aNewDocument as $key => $value) {
            $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
            $request        = \Slim\Http\Request::createFromEnvironment($environment);

            $fileContent = file_get_contents('test/unitTests/samples/test.txt');
            $encodedFile = base64_encode($fileContent);
            $aArgs = [
                'modelId'       => 1,
                'status'        => $value[2],
                'encodedFile'   => $encodedFile,
                'format'        => 'txt',
                'confidentiality'   => false,
                'documentDate'  => '2019-01-01 17:18:47',
                'arrivalDate'   => '2019-01-01 17:18:47',
                'doctype'       => $value[0],
                'destination'   => $entity['id'],
                'initiator'     => $entity['id'],
                'subject'       => $key .' Breaking News : 12345 Superman is alive - PHP unit',
                'typist'        => 19,
                'priority'      => $value[1],
                'diffusionList' => [['id' => 19, 'type' => 'user', 'mode' => 'dest'], ['id' => 20, 'type' => 'user', 'mode' => 'cc']],
                'senders'       => [['type' => 'contact', 'id' => 1], ['type' => 'user', 'id' => 21], ['type' => 'entity', 'id' => 1]],
                'recipients'    => [['type' => 'contact', 'id' => 2], ['type' => 'user', 'id' => 19], ['type' => 'entity', 'id' => 2]],
                'tags'          => [1, 2],
                'folders'       => [1, 2],
                'customFields'  => [4 => 'référence externe']
            ];

            $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
            $response     = $resController->create($fullRequest, new \Slim\Http\Response());
            $responseBody = json_decode((string)$response->getBody());
            $newId = $responseBody->resId;
            $this->assertIsInt($newId);
            $GLOBALS['resources'][] = $newId;
        }

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }
}
