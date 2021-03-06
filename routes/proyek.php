<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});



//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);

$router->group(['middleware' => 'cors'], function () use ($router) {
    //approval dev
    $router->post('login', 'AuthController@loginAdminYpt');
    $router->get('hash_pass', 'AuthController@hashPasswordAdminYpt');
});

$router->group(['middleware' => 'auth:ypt'], function () use ($router) {
    $router->get('tagihan','Proyek\TagihanController@getTagihan');
    $router->get('tagihan_detail','Proyek\TagihanController@getTagihanDetail');
    $router->get('tagihan_dok','Proyek\TagihanController@getTagihanDok');
});
