<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;
use Resource\models\UserFollowedResourceModel;

class UserFollowedResourceControllerTest extends TestCase
{
    private static $id = null;

    public function testCreate()
    {
        $GLOBALS['userId'] = 'cchaplin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
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
            'subject'       => 'Breaking News : Superman is alive - PHP unit FOLLOW / UNFOLLOW',
            'typist'        => 19,
            'priority'      => 'poiuytre1357nbvc',
            'follow'        => true
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        self::$id = $responseBody->resId;
        $this->assertIsInt(self::$id);

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id, 'select' => ['*']]);

        $this->assertIsArray($res);

        $this->assertSame('Breaking News : Superman is alive - PHP unit FOLLOW / UNFOLLOW', $res['subject']);
        $this->assertSame(102, $res['type_id']);
        $this->assertSame('txt', $res['format']);
        $this->assertSame('NEW', $res['status']);
        $this->assertSame(19, $res['typist']);
        $this->assertNotNull($res['destination']);
        $this->assertNotNull($res['initiator']);

        $GLOBALS['userId'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testUnFollow()
    {
        $GLOBALS['userId'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $usersFollowedResourcesController = new \Resource\controllers\UserFollowedResourceController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $args = [
            'resources' => [self::$id]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);

        $response     = $usersFollowedResourcesController->unFollow($fullRequest, new \Slim\Http\Response());

        $this->assertSame(204, $response->getStatusCode());

        $GLOBALS['userId'] = 'ccharles';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $usersFollowedResourcesController->unFollow($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Document out of perimeter', $responseBody->errors);

        $GLOBALS['userId'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testFollow()
    {
        $GLOBALS['userId'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $usersFollowedResourcesController = new \Resource\controllers\UserFollowedResourceController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $args = [
            'resources' => [self::$id]
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);

        $response     = $usersFollowedResourcesController->follow($fullRequest, new \Slim\Http\Response());

        $this->assertSame(204, $response->getStatusCode());

        $GLOBALS['userId'] = 'ccharles';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $usersFollowedResourcesController->follow($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Document out of perimeter', $responseBody->errors);

        $GLOBALS['userId'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetList()
    {
        $GLOBALS['userId'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $userFollowedResourceController = new \Resource\controllers\UserFollowedResourceController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $userFollowedResourceController->getFollowedResources($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsInt($responseBody->countResources);
        $this->assertSame(1, $responseBody->countResources);
        $this->assertSame(1, count($responseBody->resources));

        $this->assertGreaterThanOrEqual(1, count($responseBody->resources));
        $this->assertNotNull($responseBody->resources[0]->priorityColor);
        $this->assertNotNull($responseBody->resources[0]->statusImage);
        $this->assertNotNull($responseBody->resources[0]->statusLabel);
        $this->assertIsInt($responseBody->resources[0]->resId);
        $this->assertSame('Breaking News : Superman is alive - PHP unit FOLLOW / UNFOLLOW', $responseBody->resources[0]->subject);

        $GLOBALS['userId'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $userFollowedResourceController = new \Resource\controllers\UserFollowedResourceController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $userFollowedResourceController->getFollowedResources($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsInt($responseBody->countResources);
        $this->assertSame(0, $responseBody->countResources);

        $GLOBALS['userId'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testDelete()
    {
        $GLOBALS['userId'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        //  DELETE
        \Resource\models\ResModel::update(['set' => ['status' => 'DEL'], 'where' => ['res_id = ?'], 'data' => [self::$id]]);

        UserFollowedResourceModel::delete([
            'userId' => $GLOBALS['id'],
            'resId' => self::$id
        ]);

        $GLOBALS['userId'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        //  READ
        $res = \Resource\models\ResModel::getById(['resId' => self::$id, 'select' => ['*']]);
        $this->assertIsArray($res);
        $this->assertSame('DEL', $res['status']);
    }
}
