<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class ResourceListControllerTest extends TestCase
{
    public function testGet()
    {
        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
        $myBasket = \Basket\models\BasketModel::getByBasketId(['basketId' => 'MyBasket', 'select' => ['id']]);

        $resListController = new \Resource\controllers\ResourceListController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
        ];
        $fullRequest = $request->withQueryParams($aArgs);

        $response     = $resListController->get($fullRequest, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsInt($responseBody->count);
        $this->assertNotNull($responseBody->basketLabel);

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'limit'  => 2,
            'offset' => 1,
            'order'  => 'creation_date DESC'
        ];
        $fullRequest = $request->withQueryParams($aArgs);

        $response     = $resListController->get($fullRequest, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(2, count($responseBody->resources));

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
    
        $aArgs = [
                'order'            => 'priority DESC',
                'search'           => '2 Breaking News',
                'priorities'       => 'poiuytre1379nbvc,poiuytre1391nbvc',
                'categories'       => 'incoming',
                'statuses'         => 'COU',
                'entitiesChildren' => 'PJS'
            ];
        $fullRequest = $request->withQueryParams($aArgs);
    
        $response     = $resListController->get($fullRequest, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());
    
        $this->assertGreaterThanOrEqual(1, count($responseBody->resources));
        $this->assertNotNull($responseBody->resources[0]->priorityColor);
        $this->assertNotNull($responseBody->resources[0]->statusImage);
        $this->assertNotNull($responseBody->resources[0]->statusLabel);
        $this->assertIsInt($responseBody->resources[0]->resId);
        $this->assertSame('2 Breaking News : 12345 Superman is alive - PHP unit', $responseBody->resources[0]->subject);

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
    
        $aArgs = [
                'order'            => 'alt_identifier ASC',
                'search'           => '2 Breaking News',
                'priorities'       => 'poiuytre1379nbvc,poiuytre1391nbvc',
                'categories'       => 'incoming',
                'statuses'         => 'COU',
                'entities'         => 'PJS'
            ];
        $fullRequest = $request->withQueryParams($aArgs);
    
        $response     = $resListController->get($fullRequest, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());
    
        $this->assertGreaterThanOrEqual(1, count($responseBody->resources));

        //  ERRORS
        $response     = $resListController->get($request, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 777, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Group or basket does not exist', $responseBody->errors);

        $response     = $resListController->get($request, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => 9999]);
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Group or basket does not exist', $responseBody->errors);

        $response     = $resListController->get($request, new \Slim\Http\Response(), ['userId' => 777, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Basket out of perimeter', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetFilters()
    {
        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
        $myBasket = \Basket\models\BasketModel::getByBasketId(['basketId' => 'MyBasket', 'select' => ['id']]);

        $resListController = new \Resource\controllers\ResourceListController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
        ];
        $fullRequest = $request->withQueryParams($aArgs);

        $response     = $resListController->getFilters($fullRequest, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);
        $this->assertIsArray($responseBody->priorities);
        $this->assertIsArray($responseBody->categories);
        $this->assertIsArray($responseBody->statuses);
        $this->assertIsArray($responseBody->entitiesChildren);

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'search'   => 'Breaking News',
            'statuses' => 'NEW,COU'
        ];
        $fullRequest = $request->withQueryParams($aArgs);

        $response     = $resListController->getFilters($fullRequest, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertGreaterThanOrEqual(2, count($responseBody->priorities));
        $this->assertGreaterThanOrEqual(3, count($responseBody->statuses));

        foreach ([$responseBody->priorities, $responseBody->statuses] as $response) {
            foreach ($response as $value) {
                $this->assertNotNull($value->id);
                $this->assertNotNull($value->label);
                $this->assertIsInt($value->count);
            }
        }

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'priorities'       => 'poiuytre1379nbvc,poiuytre1391nbvc',
            'categories'       => 'incoming',
            'entitiesChildren' => 'PJS'
        ];
        $fullRequest = $request->withQueryParams($aArgs);

        $response     = $resListController->getFilters($fullRequest, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->entities);
        $this->assertIsArray($responseBody->priorities);
        $this->assertIsArray($responseBody->categories);
        $this->assertIsArray($responseBody->statuses);
        $this->assertIsArray($responseBody->entitiesChildren);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetActions()
    {
        $GLOBALS['login'] = 'bbain';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
        $myBasket = \Basket\models\BasketModel::getByBasketId(['basketId' => 'MyBasket', 'select' => ['id']]);

        $resListController = new \Resource\controllers\ResourceListController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $resListController->getActions($request, new \Slim\Http\Response(), ['userId' => 19, 'groupId' => 2, 'basketId' => $myBasket['id']]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsArray($responseBody->actions);
        $this->assertNotNull($responseBody->actions);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }
}
