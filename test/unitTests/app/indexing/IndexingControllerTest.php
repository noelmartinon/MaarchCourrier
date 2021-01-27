<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class IndexingControllerTest extends TestCase
{
    public function testGetIndexingActions()
    {
        $GLOBALS['userId'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $indexingController = new \Resource\controllers\IndexingController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $indexingController->getIndexingActions($request, new \Slim\Http\Response(), ['groupId' => 2]);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->actions);
        foreach ($responseBody->actions as $action) {
            $this->assertNotEmpty($action->id);
            $this->assertIsInt($action->id);
            $this->assertNotEmpty($action->label);
            $this->assertNotEmpty($action->component);
        }

        //ERROR
        $response = $indexingController->getIndexingActions($request, new \Slim\Http\Response(), ['groupId' => 99999]);
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('This user is not in this group', $responseBody->errors);

        $GLOBALS['userId'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetIndexingEntities()
    {
        $GLOBALS['userId'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $indexingController = new \Resource\controllers\IndexingController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $indexingController->getIndexingEntities($request, new \Slim\Http\Response(), ['groupId' => 2]);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->entities);
        foreach ($responseBody->entities as $entity) {
            $this->assertNotEmpty($entity->id);
            $this->assertIsInt($entity->id);
            $this->assertNotEmpty($entity->entity_label);
            $this->assertNotEmpty($entity->entity_id);
        }

        //ERROR
        $response = $indexingController->getIndexingActions($request, new \Slim\Http\Response(), ['groupId' => 99999]);
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('This user is not in this group', $responseBody->errors);

        $GLOBALS['userId'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['userId'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }
}
