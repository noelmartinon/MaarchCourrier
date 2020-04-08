<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class UserControllerTest extends TestCase
{
    private static $id = null;
    private static $idEmailSignature = null;
    private static $redirectId = null;


    public function testGet()
    {
        $userController = new \User\controllers\UserController();

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $userController->get($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->users);
        $this->assertNotEmpty($responseBody->users);

        foreach ($responseBody->users as $value) {
            $this->assertNotNull($value->id);
            $this->assertIsInt($value->id);
            $this->assertNotNull($value->user_id);
            $this->assertNotNull($value->firstname);
            $this->assertNotNull($value->lastname);
            $this->assertNotNull($value->status);
            $this->assertNotNull($value->mail);
            $this->assertNotNull($value->loginmode);
        }
    }

    public function testCreate()
    {
        $userController = new \User\controllers\UserController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'userId'    => 'test-ckent',
            'firstname' => 'TEST-CLARK',
            'lastname'  => 'TEST-KENT'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        self::$id = $responseBody->id;

        $this->assertIsInt(self::$id);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertSame('test-ckent', $responseBody->user_id);
        $this->assertSame('TEST-CLARK', $responseBody->firstname);
        $this->assertSame('TEST-KENT', $responseBody->lastname);
        $this->assertSame('OK', $responseBody->status);
        $this->assertSame(null, $responseBody->phone);
        $this->assertSame(null, $responseBody->mail);
        $this->assertSame(null, $responseBody->initials);
    }

    public function testUpdate()
    {
        $userController = new \User\controllers\UserController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'user_id'    => 'test-ckent',
            'firstname' => 'TEST-CLARK2',
            'lastname'  => 'TEST-KENT2',
            'mail'      => 'ck@dailyP.com',
            'phone'     => '0122334455',
            'initials'  => 'CK'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());


        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertSame('test-ckent', $responseBody->user_id);
        $this->assertSame('TEST-CLARK2', $responseBody->firstname);
        $this->assertSame('TEST-KENT2', $responseBody->lastname);
        $this->assertSame('OK', $responseBody->status);
        $this->assertSame('0122334455', $responseBody->phone);
        $this->assertSame('ck@dailyP.com', $responseBody->mail);
        $this->assertSame('CK', $responseBody->initials);
    }

    public function testAddGroup()
    {
        $userController = new \User\controllers\UserController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'groupId'   => 'AGENT',
            'role'      => 'Douche'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->addGroup($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->groups);
        $this->assertIsArray($responseBody->baskets);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertIsArray($responseBody->groups);
        $this->assertSame('AGENT', $responseBody->groups[0]->group_id);
        $this->assertSame('Douche', $responseBody->groups[0]->role);
    }

    public function testUpdateGroup()
    {
        $userController = new \User\controllers\UserController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'role'      => 'role updated'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updateGroup($fullRequest, new \Slim\Http\Response(), ['id' => self::$id, 'groupId' => 'AGENT']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('success', $responseBody->success);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertIsArray($responseBody->groups);
        $this->assertSame('AGENT', $responseBody->groups[0]->group_id);
        $this->assertSame('role updated', $responseBody->groups[0]->role);
    }

    public function testDeleteGroup()
    {
        $userController = new \User\controllers\UserController();

        //  DELETE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->deleteGroup($request, new \Slim\Http\Response(), ['id' => self::$id, 'groupId' => 'AGENT']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->groups);
        $this->assertEmpty($responseBody->groups);
        $this->assertIsArray($responseBody->baskets);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertIsArray($responseBody->groups);
        $this->assertEmpty($responseBody->groups);
    }

    public function testAddEntity()
    {
        $userController = new \User\controllers\UserController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'entityId'  => 'DGS',
            'role'      => 'Warrior'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->addEntity($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);
        $this->assertIsArray($responseBody->allEntities);

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'entityId'  => 'FIN',
            'role'      => 'Hunter'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->addEntity($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);
        $this->assertIsArray($responseBody->allEntities);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertIsArray($responseBody->entities);
        $this->assertSame('DGS', $responseBody->entities[0]->entity_id);
        $this->assertSame('Warrior', $responseBody->entities[0]->user_role);
        $this->assertSame('Y', $responseBody->entities[0]->primary_entity);
        $this->assertSame('FIN', $responseBody->entities[1]->entity_id);
        $this->assertSame('Hunter', $responseBody->entities[1]->user_role);
        $this->assertSame('N', $responseBody->entities[1]->primary_entity);
    }

    public function testUpdateEntity()
    {
        $userController = new \User\controllers\UserController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'user_role'      => 'Rogue'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updateEntity($fullRequest, new \Slim\Http\Response(), ['id' => self::$id, 'entityId' => 'DGS']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('success', $responseBody->success);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertIsArray($responseBody->entities);
        $this->assertSame('DGS', $responseBody->entities[0]->entity_id);
        $this->assertSame('Rogue', $responseBody->entities[0]->user_role);
        $this->assertSame('Y', $responseBody->entities[0]->primary_entity);
    }

    public function testGetUsersById()
    {
        $entityController = new \Entity\controllers\EntityController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $entityInfo     = \Entity\models\EntityModel::getByEntityId(['entityId' => 'DGS', 'select' => ['id']]);
        $response       = $entityController->getById($request, new \Slim\Http\Response(), ['id' => $entityInfo['id']]);
        $responseBody   = json_decode((string)$response->getBody());
        $entitySerialId = $responseBody->id;

        $response     = $entityController->getUsersById($request, new \Slim\Http\Response(), ['id' => $entitySerialId]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotNull($responseBody->users);

        $found = false;
        foreach ($responseBody->users as $value) {
            $this->assertNotNull($value->id);
            $this->assertIsInt($value->id);
            $this->assertNotNull($value->user_id);
            $this->assertNotNull($value->firstname);
            $this->assertNotNull($value->lastname);
            $this->assertNotNull($value->labelToDisplay);
            $this->assertNotNull($value->descriptionToDisplay);

            if ($value->id == self::$id) {
                $this->assertSame('test-ckent', $value->user_id);
                $this->assertSame('TEST-CLARK2', $value->firstname);
                $this->assertSame('TEST-KENT2', $value->lastname);
                $this->assertSame($value->firstname . ' ' . $value->lastname, $value->labelToDisplay);
                $found = true;
            }
        }

        $this->assertSame(true, $found);

        //ERROR
        $response     = $entityController->getUsersById($request, new \Slim\Http\Response(), ['id' => 99989]);
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Entity not found', $responseBody->errors);
    }

    public function testIsDeletable()
    {
        $userController = new \User\controllers\UserController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $userController->isDeletable($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(true, $responseBody->isDeletable);
        $this->assertIsArray($responseBody->listTemplates);
        $this->assertEmpty($responseBody->listTemplates);
        $this->assertIsArray($responseBody->listInstances);
        $this->assertEmpty($responseBody->listInstances);
    }

    public function testIsEntityDeletable()
    {
        $userController = new \User\controllers\UserController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $userController->isEntityDeletable($request, new \Slim\Http\Response(), ['id' => self::$id, 'entityId' => 'DGS']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(false, $responseBody->hasConfidentialityInstances);
        $this->assertSame(false, $responseBody->hasListTemplates);
    }

    public function testUpdatePrimaryEntity()
    {
        $userController = new \User\controllers\UserController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $userController->updatePrimaryEntity($request, new \Slim\Http\Response(), ['id' => self::$id, 'entityId' => 'FIN']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertIsArray($responseBody->entities);
        $this->assertSame('FIN', $responseBody->entities[0]->entity_id);
        $this->assertSame('Hunter', $responseBody->entities[0]->user_role);
        $this->assertSame('Y', $responseBody->entities[0]->primary_entity);
        $this->assertSame('DGS', $responseBody->entities[1]->entity_id);
        $this->assertSame('Rogue', $responseBody->entities[1]->user_role);
        $this->assertSame('N', $responseBody->entities[1]->primary_entity);
    }

    public function testDeleteEntity()
    {
        $userController = new \User\controllers\UserController();

        //  DELETE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->deleteEntity($request, new \Slim\Http\Response(), ['id' => self::$id, 'entityId' => 'FIN']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);
        $this->assertIsArray($responseBody->allEntities);

        //  DELETE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->deleteEntity($request, new \Slim\Http\Response(), ['id' => self::$id, 'entityId' => 'DGS']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);
        $this->assertEmpty($responseBody->entities);
        $this->assertIsArray($responseBody->allEntities);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertIsArray($responseBody->entities);
        $this->assertEmpty($responseBody->entities);
    }

    public function testGetStatusByUserId()
    {
        $userController = new \User\controllers\UserController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getStatusByUserId($request, new \Slim\Http\Response(), ['userId' => 'test-ckent']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('OK', $responseBody->status);
    }

    public function testUpdateStatus()
    {
        $userController = new \User\controllers\UserController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'status'    => 'ABS'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updateStatus($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('ABS', $responseBody->user->status);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertSame('ABS', $responseBody->status);
    }

    public function testGetStatusByUserIdAfterUpdate()
    {
        $userController = new \User\controllers\UserController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getStatusByUserId($request, new \Slim\Http\Response(), ['userId' => 'test-ckent']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('ABS', $responseBody->status);
    }

    public function testRead()
    {
        $userController = new \User\controllers\UserController();
        $parameterController = new \Parameter\controllers\ParameterController();
        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'description'           => 'User quota',
            'param_value_int'       => 0
        ];
        $fullRequest    = \httpRequestCustom::addContentInBody($aArgs, $request);
        $parameterController->update($fullRequest, new \Slim\Http\Response(), ['id' => 'user_quota']);

        // READ in case of deactivated user_quota
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $userController->get($request, new \Slim\Http\Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertNotNull($responseBody->users);
        $this->assertNull($responseBody->quota->userQuota);

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'description'           => 'User quota',
            'param_value_int'       => 20
        ];
        $fullRequest    = \httpRequestCustom::addContentInBody($aArgs, $request);
        $parameterController->update($fullRequest, new \Slim\Http\Response(), ['id' => 'user_quota']);

        // READ in case of enabled user_quotat
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $userController->get($request, new \Slim\Http\Response());
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertNotNull($responseBody->users);
        $this->assertNotNull($responseBody->quota);
        $this->assertSame(20, $responseBody->quota->userQuota);
        $this->assertNotNull($responseBody->quota->actives);
        $this->assertIsInt($responseBody->quota->inactives);

        $aArgs = [
            'description'           => 'User quota',
            'param_value_int'       => 0
        ];
        $fullRequest    = \httpRequestCustom::addContentInBody($aArgs, $request);
        $parameterController->update($fullRequest, new \Slim\Http\Response(), ['id' => 'user_quota']);
    }

    public function testCreateEmailSignature()
    {
        $userController = new \User\controllers\UserController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'title'    => 'Titre email signature TU 12345',
            'htmlBody' => '<p>Body Email Signature</p>'
        ];
        $fullRequest    = \httpRequestCustom::addContentInBody($aArgs, $request);
        $response = $userController->createCurrentUserEmailSignature($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertNotEmpty($responseBody->emailSignatures);

        $titleEmailSignature = '';
        $htmlBodyEmailSignature = '';
        foreach ($responseBody->emailSignatures as $value) {
            if ($value->title == 'Titre email signature TU 12345') {
                self::$idEmailSignature = $value->id;
                $titleEmailSignature    = $value->title;
                $htmlBodyEmailSignature = $value->html_body;
            }
        }
        $this->assertNotEmpty(self::$idEmailSignature);
        $this->assertIsInt(self::$idEmailSignature);
        $this->assertSame('Titre email signature TU 12345', $titleEmailSignature);
        $this->assertSame('<p>Body Email Signature</p>', $htmlBodyEmailSignature);

        // ERROR
        $aArgs = [
            'title'    => '',
            'htmlBody' => ''
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->createCurrentUserEmailSignature($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Bad Request', $responseBody->errors);
    }

    public function testUpdateEmailSignature()
    {
        $userController = new \User\controllers\UserController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'title'    => 'Titre email signature TU 12345 UPDATE',
            'htmlBody' => '<p>Body Email Signature UPDATE</p>'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updateCurrentUserEmailSignature($fullRequest, new \Slim\Http\Response(), ['id' => self::$idEmailSignature]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->emailSignature);
        $this->assertNotEmpty($responseBody->emailSignature->id);
        $this->assertIsInt($responseBody->emailSignature->id);
        $this->assertSame('Titre email signature TU 12345 UPDATE', $responseBody->emailSignature->title);
        $this->assertSame('<p>Body Email Signature UPDATE</p>', $responseBody->emailSignature->html_body);

        // ERROR
        $aArgs = [
            'title'    => '',
            'htmlBody' => ''
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updateCurrentUserEmailSignature($fullRequest, new \Slim\Http\Response(), ['id' => self::$idEmailSignature]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Bad Request', $responseBody->errors);
    }

    public function testDeleteEmailSignature()
    {
        $userController = new \User\controllers\UserController();

        //  DELETE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $userController->deleteCurrentUserEmailSignature($request, new \Slim\Http\Response(), ['id' => self::$idEmailSignature]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertNotNull($responseBody->emailSignatures);

        $titleEmailSignature = '';
        $htmlBodyEmailSignature = '';
        foreach ($responseBody->emailSignatures as $value) {
            if ($value->title == 'Titre email signature TU 12345 UPDATE') {
                // Check If Signature Really Deleted
                $titleEmailSignature    = $value->title;
                $htmlBodyEmailSignature = $value->html_body;
            }
        }
        $this->assertSame('', $titleEmailSignature);
        $this->assertSame('', $htmlBodyEmailSignature);
    }

    public function testDelete()
    {
        $userController = new \User\controllers\UserController();

        //  DELETE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $userController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $userController->getDetailledById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->id);
        $this->assertSame('test-ckent', $responseBody->user_id);
        $this->assertSame('TEST-CLARK2', $responseBody->firstname);
        $this->assertSame('TEST-KENT2', $responseBody->lastname);
        $this->assertSame('DEL', $responseBody->status);
        $this->assertSame('0122334455', $responseBody->phone);
        $this->assertSame('ck@dailyP.com', $responseBody->mail);
        $this->assertSame('CK', $responseBody->initials);

        //  REAL DELETE
        \SrcCore\models\DatabaseModel::delete([
            'table' => 'users',
            'where' => ['id = ?'],
            'data'  => [self::$id]
        ]);
    }

    public function testPasswordManagement()
    {
        $userController = new \User\controllers\UserController();

        $user = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);

        //  UPDATE PASSWORD
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'currentPassword'   => 'superadmin',
            'newPassword'       => 'hcraam',
            'reNewPassword'     => 'hcraam'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updatePassword($fullRequest, new \Slim\Http\Response(), ['id' => $user['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('success', $responseBody->success);

        $checkPassword = \SrcCore\models\AuthenticationModel::authentication(['login' => $GLOBALS['login'], 'password' => 'hcraam']);

        $this->assertSame(true, $checkPassword);

        //  UPDATE PASSWORD
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'currentPassword'   => 'hcraam',
            'newPassword'       => 'superadmin',
            'reNewPassword'     => 'superadmin'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updatePassword($fullRequest, new \Slim\Http\Response(), ['id' => $user['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('success', $responseBody->success);

        $checkPassword = \SrcCore\models\AuthenticationModel::authentication(['login' => $GLOBALS['login'], 'password' => 'superadmin']);

        $this->assertSame(true, $checkPassword);
    }

    public function testUpdateProfile()
    {
        $userController = new \User\controllers\UserController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'firstname'     => 'Wonder',
            'lastname'      => 'User',
            'mail'          => 'dev@maarch.org',
            'initials'      => 'SU'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updateProfile($fullRequest, new \Slim\Http\Response());
        $this->assertSame(204, $response->getStatusCode());


        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getProfile($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('superadmin', $responseBody['user_id']);
        $this->assertSame('Wonder', $responseBody['firstname']);
        $this->assertSame('User', $responseBody['lastname']);
        $this->assertSame('dev@maarch.org', $responseBody['mail']);
        $this->assertSame('SU', $responseBody['initials']);
        $this->assertSame('onlyoffice', $responseBody['preferences']['documentEdition']);


        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'firstname'     => 'Super',
            'lastname'      => 'ADMIN',
            'mail'          => 'dev@maarch.org',
            'initials'      => 'SU'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updateProfile($fullRequest, new \Slim\Http\Response());
        $this->assertSame(204, $response->getStatusCode());


        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response     = $userController->getProfile($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('superadmin', $responseBody->user_id);
        $this->assertSame('Super', $responseBody->firstname);
        $this->assertSame('ADMIN', $responseBody->lastname);
        $this->assertSame('dev@maarch.org', $responseBody->mail);
        $this->assertSame('SU', $responseBody->initials);

        //  ERRORS
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            'firstname'     => 'Super',
            'lastname'      => 'ADMIN',
            'initials'      => 'SU'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $userController->updateProfile($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body mail is empty or not a valid email', $responseBody['errors']);
    }

    public function testSetRedirectedBasket()
    {
        $userController = new \User\controllers\UserController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $aArgs = [
            [
                'actual_user_id'    =>  21,
                'basket_id'         =>  'MyBasket',
                'group_id'          =>  2
            ]
        ];

        $user_id = \User\models\UserModel::getByLogin(['login' => 'bbain', 'select' => ['id']]);
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
        $response     = $userController->setRedirectedBaskets($fullRequest, new \Slim\Http\Response(), ['id' => $user_id['id']]);
        $responseBody = json_decode((string)$response->getBody());
        
        $this->assertNotNull($responseBody->baskets);
        $this->assertNotNull($responseBody->redirectedBaskets);
        foreach ($responseBody->redirectedBaskets as $redirectedBasket) {
            if ($redirectedBasket->actual_user_id == 21 && $redirectedBasket->basket_id == 'MyBasket' && $redirectedBasket->group_id == 2) {
                self::$redirectId = $redirectedBasket->id;
            }
        }
        $this->assertNotNull(self::$redirectId);
        $this->assertIsInt(self::$redirectId);

        $aArgs = [
            [
                'newUser'       =>  null,
                'basketId'      =>  'MyBasket',
                'basketOwner'   =>  'bbain',
                'virtual'       =>  'Y'
            ],
            [
                'newUser'       =>  'bblier',
                'basketId'      =>  'EenvBasket',
                'basketOwner'   =>  'bbain',
                'virtual'       =>  'Y'
            ]
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
        $response     = $userController->setRedirectedBaskets($fullRequest, new \Slim\Http\Response(), ['id' => $user_id['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Some data are empty', $responseBody->errors);

        $aArgs = [
            [
                'actual_user_id'    =>  -1,
                'basket_id'         =>  'MyBasket',
                'group_id'          =>  2
            ]
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);
        $response     = $userController->setRedirectedBaskets($fullRequest, new \Slim\Http\Response(), ['id' => $user_id['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('User not found', $responseBody->errors);
    }

    public function testDeleteRedirectedBaskets()
    {
        $userController = new \User\controllers\UserController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $user_id = \User\models\UserModel::getByLogin(['login' => 'bbain', 'select' => ['id']]);
       
        //DELETE MANY WITH ONE ON ERROR
        $aArgs = [
            'redirectedBasketIds' => [ self::$redirectId, -1 ]
        ];

        $fullRequest = $request->withQueryParams($aArgs);

        $response     = $userController->deleteRedirectedBasket($fullRequest, new \Slim\Http\Response(), ['id' => $user_id['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Redirected basket out of perimeter', $responseBody->errors);

        //DELETE OK
        $aArgs = [
            'redirectedBasketIds' => [ self::$redirectId ]
        ];

        $fullRequest = $request->withQueryParams($aArgs);

        $response  = $userController->deleteRedirectedBasket($fullRequest, new \Slim\Http\Response(), ['id' => $user_id['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotNull($responseBody->baskets);

        //DELETE NOT OK
        $aArgs = [
            'redirectedBasketIds' => [ -1 ]
        ];

        $fullRequest = $request->withQueryParams($aArgs);

        $response     = $userController->deleteRedirectedBasket($fullRequest, new \Slim\Http\Response(), ['id' => $user_id['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Redirected basket out of perimeter', $responseBody->errors);
    }
}
