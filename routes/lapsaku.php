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

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('lapdago','Gl\LaporanController@getLap');
    $router->get('periode','Gl\FilterController@getPeriode');
    $router->get('paket','Gl\FilterController@getPaket');
    $router->get('jadwal','Gl\FilterController@getJadwal');
    $router->get('reg','Gl\FilterController@getNoReg');
    $router->get('peserta','Gl\FilterController@getPeserta');

});
