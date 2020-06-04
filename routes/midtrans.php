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

    $router->post('login', 'AuthController@loginAdmin');
    $router->get('hashPass', 'AuthController@hashPasswordAdmin');
});

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('profile', 'AdminController@profile');
    $router->get('users/{id}', 'AdminController@singleUser');
    $router->get('users', 'AdminController@allUsers');
    $router->get('cekPayload', 'AdminController@cekPayload');
    
    //Menu
    $router->get('menu/{kode_klp}', 'Gl\MenuController@show');
    
    //Donasi
    $router->get('donasi','Midtrans\DonasiController@index');
    $router->get('donasi-kode','Midtrans\DonasiController@getKode');
    $router->get('donasi/{no_bukti}','Midtrans\DonasiController@show');
    $router->post('donasi','Midtrans\DonasiController@store');
    
});

$router->put('donasi/{no_bukti}/{sts_bayar}','Midtrans\DonasiController@ubahStatus');