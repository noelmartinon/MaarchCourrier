<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 */

session_start();

require '../vendor/autoload.php';


use CMIS\Controllers\FrontController;
use CMIS\Utils\Router;
use CMIS\Utils\Utils;


session_destroy();

FrontController::initSession();


//TODO Remove in production
//FrontController::fakeAuth();

FrontController::login();

$router = new Router();
$router->setBasePath('/MaarchCourrier/cmis');

$router->map('GET', '/', function () {
    header('Location:atom');
});


$router->map('POST', '/[a:output]/query/?', function ($output) {
    FrontController::query($output);
}, 'query');


$router->map('POST', '/[a:output]/[*:route]', function ($output) {
    FrontController::create($output);
}, 'create');

/** @param $output string atom or browser */
$router->map('GET', '/[a:output]/?', function ($output) {
    FrontController::repository($output);
}, 'catalog');

$router->map('GET', '/atom/types', function () {
    Utils::renderXML('assets/atom/types.xml');
}, 'types');

$router->map('GET', '/[a:output]/descendants/?', function ($output) {
    FrontController::descendants($output);
}, 'descendants');

$router->map('GET', '/[a:output]/id/?', function ($output) {
    FrontController::id($output);
}, 'id');

$router->map('GET', '/[a:output]/path/?', function ($output) {
    FrontController::path($output);
}, 'path');

$router->map('GET', '/[a:output]/children/?', function ($output) {
    FrontController::children($output);
}, 'children');


$router->map('GET', '/[a:output]/type/?', function ($output) {
    FrontController::type($output);
}, 'type');

//TODO Remove in production
Utils::log();

$match = $router->match();

if ($match && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}
