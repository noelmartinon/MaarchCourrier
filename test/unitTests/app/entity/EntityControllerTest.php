<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class EntityControllerTest extends TestCase
{
    private static $id = null;

    public function testCreate()
    {
        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $entityController = new \Entity\controllers\EntityController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'entity_id'         => 'TEST-ENTITY123',
            'entity_label'      => 'TEST-ENTITY123-LABEL',
            'short_label'       => 'TEST-ENTITY123-SHORTLABEL',
            'entity_type'       => 'Service',
            'email'             => 'paris@isMagic.fr',
            'adrs_1'            => '1 rue du parc des princes',
            'zipcode'           => '75016',
            'city'              => 'PARIS',
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $entityController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);

        $entityInfo = \Entity\models\EntityModel::getByEntityId(['entityId' => 'TEST-ENTITY123', 'select' => ['id']]);
        self::$id = $entityInfo['id'];

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame('TEST-ENTITY123', $responseBody->entity_id);
        $this->assertSame('TEST-ENTITY123-LABEL', $responseBody->entity_label);
        $this->assertSame('TEST-ENTITY123-SHORTLABEL', $responseBody->short_label);
        $this->assertSame('Service', $responseBody->entity_type);
        $this->assertSame('Y', $responseBody->enabled);
        $this->assertSame(null, $responseBody->parent_entity_id);

        // ERRORS

        $response     = $entityController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame(_ENTITY_ID_ALREADY_EXISTS, $responseBody['errors']);

        unset($aArgs['entity_label']);
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
        $response     = $entityController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Bad Request', $responseBody['errors']);

        unset($aArgs['entity_id']);
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
        $response     = $entityController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Bad Request', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetUsersById()
    {
        $entityController = new \Entity\controllers\EntityController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $entityController->getUsersById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody['users']);
        $this->assertNotEmpty($responseBody['users']);
        $this->assertSame('bblier', $responseBody['users'][0]['user_id']);

        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $entityController->getUsersById($request, new \Slim\Http\Response(), ['id' => 99999999]);
        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Entity not found', $responseBody['errors']);
    }

    public function testUpdate()
    {
        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $entityController = new \Entity\controllers\EntityController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'entity_label'      => 'TEST-ENTITY123-LABEL',
            'short_label'       => 'TEST-ENTITY123-SHORTLABEL-UP',
            'entity_type'       => 'Direction',
            'email'             => 'paris@isMagic2.fr',
            'adrs_2'            => '2 rue des princes',
            'toto'              => 'toto'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $entityController->update($fullRequest, new \Slim\Http\Response(), ['id' => 'TEST-ENTITY123']);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame('TEST-ENTITY123', $responseBody->entity_id);
        $this->assertSame('TEST-ENTITY123-LABEL', $responseBody->entity_label);
        $this->assertSame('TEST-ENTITY123-SHORTLABEL-UP', $responseBody->short_label);
        $this->assertSame('Direction', $responseBody->entity_type);
        $this->assertSame('Y', $responseBody->enabled);
        $this->assertSame(null, $responseBody->parent_entity_id);

        $response     = $entityController->update($fullRequest, new \Slim\Http\Response(), ['id' => '12345678923456789']);
        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Entity not found', $responseBody['errors']);

        unset($aArgs['entity_label']);
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
        $response     = $entityController->update($fullRequest, new \Slim\Http\Response(), ['id' => 'TEST-ENTITY123']);
        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Bad Request', $responseBody['errors']);

        \User\models\UserEntityModel::deleteUserEntity(['id' => $GLOBALS['id'], 'entityId' => 'TEST-ENTITY123']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testUpdateStatus()
    {
        $entityController = new \Entity\controllers\EntityController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'method'            => 'disable'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $entityController->updateStatus($fullRequest, new \Slim\Http\Response(), ['id' => 'TEST-ENTITY123']);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('success', $responseBody->success);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame('TEST-ENTITY123', $responseBody->entity_id);
        $this->assertSame('N', $responseBody->enabled);

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'method'            => 'enable'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $entityController->updateStatus($fullRequest, new \Slim\Http\Response(), ['id' => 'TEST-ENTITY123']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('success', $responseBody->success);

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $entityController->updateStatus($fullRequest, new \Slim\Http\Response(), ['id' => 'TEST-9999999']);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Entity not found', $responseBody->errors);


        $fullRequest = \httpRequestCustom::addContentInBody([], $request);

        $response     = $entityController->updateStatus($fullRequest, new \Slim\Http\Response(), ['id' => 'TEST-ENTITY123']);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Bad Request', $responseBody['errors']);
    }

    public function testGet()
    {
        $entityController = new \Entity\controllers\EntityController();

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->get($request, new \Slim\Http\Response());
        $this->assertSame(200, $response->getStatusCode());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);
        $this->assertNotNull($responseBody->entities);
    }

    public function testGetDetailledById()
    {
        $entityController = new \Entity\controllers\EntityController();

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->getDetailledById($request, new \Slim\Http\Response(), ['id' => 'TEST-ENTITY123']);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame('TEST-ENTITY123', $responseBody->entity->entity_id);
        $this->assertSame('TEST-ENTITY123-LABEL', $responseBody->entity->entity_label);
        $this->assertSame('TEST-ENTITY123-SHORTLABEL-UP', $responseBody->entity->short_label);
        $this->assertSame('Direction', $responseBody->entity->entity_type);
        $this->assertSame('Y', $responseBody->entity->enabled);
        $this->assertSame('paris@isMagic2.fr', $responseBody->entity->email);
        $this->assertSame('1 rue du parc des princes', $responseBody->entity->adrs_1);
        $this->assertSame('2 rue des princes', $responseBody->entity->adrs_2);
        $this->assertSame(null, $responseBody->entity->adrs_3);
        $this->assertSame('75016', $responseBody->entity->zipcode);
        $this->assertSame('PARIS', $responseBody->entity->city);
        $this->assertSame(null, $responseBody->entity->parent_entity_id);
        $this->assertIsArray((array) $responseBody->entity->listTemplate);
        $this->assertIsArray($responseBody->entity->visaCircuit);
        $this->assertSame(false, $responseBody->entity->hasChildren);
        $this->assertSame(0, $responseBody->entity->documents);
        $this->assertIsArray($responseBody->entity->users);
        $this->assertIsArray($responseBody->entity->templates);
        $this->assertSame(0, $responseBody->entity->instances);
        $this->assertSame(0, $responseBody->entity->redirects);
    }

    public function testReassignEntity()
    {
        $entityController = new \Entity\controllers\EntityController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'entity_id'         => 'R2-D2',
            'entity_label'      => 'TEST-ENTITY123-LABEL',
            'short_label'       => 'TEST-ENTITY123-SHORTLABEL',
            'entity_type'       => 'Service',
            'email'             => 'paris@isMagic.fr',
            'adrs_1'            => '1 rue du parc des princes',
            'zipcode'           => '75016',
            'city'              => 'PARIS',
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $entityController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(200, $response->getStatusCode());

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->reassignEntity($request, new \Slim\Http\Response(), ['id' => 'R2-D2', 'newEntityId' => 'TEST-ENTITY123']);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody   = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody['entities']);

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->reassignEntity($request, new \Slim\Http\Response(), ['id' => 'R2-D29999999', 'newEntityId' => 'TEST-ENTITY123']);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody   = json_decode((string)$response->getBody(), true);

        $this->assertSame('Entity does not exist', $responseBody['errors']);
    }

    public function testDelete()
    {
        $entityController = new \Entity\controllers\EntityController();

        //  DELETE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->delete($request, new \Slim\Http\Response(), ['id' => 'TEST-ENTITY123']);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame('Entity not found', $responseBody->errors);
    }

    public function testGetTypes()
    {
        $entityController = new \Entity\controllers\EntityController();

        //  DELETE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $entityController->getTypes($request, new \Slim\Http\Response());
        $this->assertSame(200, $response->getStatusCode());

        $responseBody   = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody['types']);
        $this->assertNotEmpty($responseBody['types']);
    }
}
