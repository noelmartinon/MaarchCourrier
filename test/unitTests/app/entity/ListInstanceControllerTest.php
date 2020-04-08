<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class ListInstanceControllerTest extends TestCase
{
    private static $resourceId = null;

    public function testUpdateCircuits()
    {
        $GLOBALS['login'] = 'cchaplin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $resController = new \Resource\controllers\ResController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);


        $aArgs = [
            'modelId'           => 1,
            'status'            => 'NEW',
            'confidentiality'   => false,
            'documentDate'      => '2019-01-01 17:18:47',
            'arrivalDate'       => '2019-01-01 17:18:47',
            'processLimitDate'  => '2029-01-01',
            'doctype'           => 102,
            'destination'       => 15,
            'initiator'         => 15,
            'subject'           => 'Du matin au soir, ils disent du mal de la vie, et ils ne peuvent se résoudre à la quitter !',
            'typist'            => 19,
            'priority'          => 'poiuytre1357nbvc'
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $resController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        self::$resourceId = $responseBody['resId'];
        $this->assertIsInt(self::$resourceId);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $listInstanceController = new \Entity\controllers\ListInstanceController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'resources' => [
                [
                    'resId'  => self::$resourceId,
                    'listInstances' => [
                        ["item_id" => 17, "requested_signature" => false],
                        ["item_id" => 18, "requested_signature" => true]
                    ]
                ],
            ],
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $listInstanceController->updateCircuits($fullRequest, new \Slim\Http\Response(), ['type' => 'visaCircuit']);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testGetVisaCircuitByResId()
    {
        $listInstanceController = new \Entity\controllers\ListInstanceController();

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $listInstanceController->getVisaCircuitByResId($request, new \Slim\Http\Response(), ['resId' => self::$resourceId]);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody   = json_decode((string)$response->getBody(), true);

        $this->assertSame(17, $responseBody['circuit'][0]['item_id']);
        $this->assertSame('user', $responseBody['circuit'][0]['item_type']);
        $this->assertSame(false, $responseBody['circuit'][0]['requested_signature']);
        $this->assertNotEmpty($responseBody['circuit'][0]['labelToDisplay']);
        $this->assertSame(18, $responseBody['circuit'][1]['item_id']);
        $this->assertSame('user', $responseBody['circuit'][1]['item_type']);
        $this->assertSame(true, $responseBody['circuit'][1]['requested_signature']);
        $this->assertNotEmpty($responseBody['circuit'][1]['labelToDisplay']);

        \SrcCore\models\DatabaseModel::delete([
            'table' => 'res_letterbox',
            'where' => ['res_id = ?'],
            'data'  => [self::$resourceId]
        ]);
        \SrcCore\models\DatabaseModel::delete([
            'table' => 'listinstance',
            'where' => ['res_id = ?'],
            'data'  => [self::$resourceId]
        ]);
    }
}
