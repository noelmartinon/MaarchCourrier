<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;
use SrcCore\models\DatabaseModel;

class NoteControllerTest extends TestCase
{
    private static $noteId = null;

    public function testCreate()
    {
        //get last notes
        $getResId = DatabaseModel::select([
            'select'    => ['res_id'],
            'table'     => ['res_letterbox'],
            'where'     => ['subject = ?','status = ?'],
            'data'      => ['Breaking News : Superman is alive - PHP unit', 'DEL'],
            'order_by'  => ['res_id DESC']
        ]);

        $resID['resId'] = $getResId[0]['res_id'];

        $this->assertInternalType('int', $resID['resId']);

        $noteController = new \Note\controllers\NoteController();

        // CREATE WITH ALL DATA -> OK
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'note_text'         => "Test d'ajout d'une note par php unit",
            'entities_chosen'   => ['COU', 'CAB']
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $noteController->create($fullRequest, new \Slim\Http\Response(), $resID);
        $responseBody = json_decode((string)$response->getBody());

        self::$noteId = $responseBody->noteId;

        $this->assertInternalType('int', self::$noteId);

        //get notes
        $getNote = DatabaseModel::select([
            'select'    => ['id', 'user_id', 'identifier', 'note_text' ],
            'table'     => ['notes'],
            'where'     => ['id = ?'],
            'data'      => [self::$noteId]
        ]);

        //getEntities
        $getEntities = DatabaseModel::select([
            'select'    => ['item_id' ],
            'table'     => ['note_entities'],
            'where'     => ['note_id = ?'],
            'data'      => [self::$noteId]
        ]);
        
        $responseBody = $getNote[0];

        if(!empty($getEntities)) {
            $responseBody['entities'] = [];
            foreach ($getEntities as $key => $value) {
                $responseBody['entities'][$key] = $value['item_id'];
            }
        }      

        $this->assertSame(self::$noteId, $responseBody['id']);
        $this->assertSame($GLOBALS['userId'], $responseBody['user_id']);
        $this->assertSame("Test d'ajout d'une note par php unit", $responseBody['note_text']);
        $this->assertSame($resID['resId'], $responseBody['identifier']);
        $this->assertInternalType('array', $responseBody['entities']);
        $this->assertSame('COU', $responseBody['entities'][0]);
        $this->assertSame('CAB', $responseBody['entities'][1]);


        // CREATE WITHOUT ENTITIES -> OK
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'note_text'         => "Test d'ajout d'une note par php unit"
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $noteController->create($fullRequest, new \Slim\Http\Response(), $resID);
        $responseBody = json_decode((string)$response->getBody());

        self::$noteId = $responseBody->noteId;

        $this->assertInternalType('int', self::$noteId);

        //get notes
        $getNote = DatabaseModel::select([
            'select'    => ['id', 'user_id', 'identifier', 'note_text' ],
            'table'     => ['notes'],
            'where'     => ['id = ?'],
            'data'      => [self::$noteId]
        ]);

        $getEntities = DatabaseModel::select([
            'select'    => ['item_id' ],
            'table'     => ['note_entities'],
            'where'     => ['note_id = ?'],
            'data'      => [self::$noteId]
        ]);
        
        $responseBody = $getNote[0];

        $responseBody['entities'] = '';

        if(!empty($getEntities)) {
            foreach ($getEntities as $key => $value) {
                $responseBody['entities'][$key] = $value['item_id'];
            }
        }

        $this->assertSame(self::$noteId, $responseBody['id']);
        $this->assertSame($GLOBALS['userId'], $responseBody['user_id']);
        $this->assertSame("Test d'ajout d'une note par php unit", $responseBody['note_text']);
        $this->assertSame($resID['resId'], $responseBody['identifier']);
        $this->assertInternalType('string', $responseBody['entities']);
        $this->assertSame('', $responseBody['entities']);

        // CREATE WITH NOTE_TEXT MISSING -> NOT OK
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $aArgs = [
            'entities_chosen' => ["COU", "CAB"]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($aArgs, $request);

        $response     = $noteController->create($fullRequest, new \Slim\Http\Response(), $resID);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Data note_text is empty or not a string', $responseBody->errors);
    }
}
