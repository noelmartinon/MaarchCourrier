<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Tile Controller
 * @author dev@maarch.org
 */

namespace Home\controllers;

use History\controllers\HistoryController;
use Home\models\TileModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;

class TileController
{
    const TYPES = ['lastViewMails', 'basket', 'savedQuery', 'followedMails', 'folder', 'externalSignatureBook', 'shortcut'];
    const VIEWS = ['list', 'resume', 'chart'];

    public function get(Request $request, Response $response)
    {
        $tiles = TileModel::get([
            'select'    => ['*'],
            'where'     => ['user_id = ?'],
            'data'      => [$GLOBALS['id']]
        ]);

        return $response->withJson(['tiles' => $tiles]);
    }

    public function create(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (empty($body)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body is empty']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['type'] ?? null) || !in_array($body['type'], TileController::TYPES)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body type is empty, not a string or not valid']);
        } elseif (!Validator::stringType()->notEmpty()->validate($body['view'] ?? null) || !in_array($body['view'], TileController::VIEWS)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body view is empty, not a string or not valid']);
        } elseif (!Validator::intVal()->notEmpty()->validate($body['position'] ?? null)) {
            return $response->withStatus(400)->withJson(['errors' => 'Body position is empty or not an integer']);
        }

        $tiles = TileModel::get([
            'select'    => [1],
            'where'     => ['user_id = ?'],
            'data'      => [$GLOBALS['id']]
        ]);
        if (count($tiles) >= 6) {
            return $response->withStatus(400)->withJson(['errors' => 'Too many tiles (limited to 6)']);
        }

        $id = TileModel::create([
            'user_id'       => $GLOBALS['id'],
            'type'          => $body['type'],
            'view'          => $body['view'],
            'position'      => $body['position'],
            'parameters'    => empty($body['parameters']) ? '{}' : json_encode($body['parameters'])
        ]);

        HistoryController::add([
            'tableName'    => 'tiles',
            'recordId'     => $GLOBALS['id'],
            'eventType'    => 'ADD',
            'eventId'      => 'tileCreation',
            'info'         => 'tile creation'
        ]);

        return $response->withJson(['id' => $id]);
    }
}
