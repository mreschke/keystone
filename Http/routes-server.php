<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// Remember this routes-server.php is used in Lumen
// which uses nikic/FastRoute instead of Illuminate Router

$app->get('/', 'ServerController@index');

$app->get('/namespaces', 'ServerController@namespaces');

$app->get('/keys', 'ServerController@keys');

$app->get('/{key:[0-9A-Za-z:/\-_]+}', 'ServerController@get');