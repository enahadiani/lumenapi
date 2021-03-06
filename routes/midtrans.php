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

$router->group(['middleware' => 'auth:ts'], function () use ($router) {
    
    //Bayar Tagihan Siswa
    $router->get('sis-midtrans','Midtrans\BayarController@index');
    $router->get('sis-midtrans-kode','Midtrans\BayarController@getKode');
    $router->get('sis-midtrans/{no_bukti}','Midtrans\BayarController@show');
    $router->post('sis-midtrans','Midtrans\BayarController@store');

    $router->get('sis-midtrans-status','Midtrans\BayarController@getStatusTransaksi');
    $router->post('sis-midtrans-cancel','Midtrans\BayarController@cancelTransaksi');
    $router->get('sis-midtrans-pending','Ts\DashSiswaController@getTransaksiPending');
});

$router->post('sis-midtrans/charge','Midtrans\BayarController@getSnapToken');
$router->put('sis-midtrans/{no_bukti}/{sts_bayar}','Midtrans\BayarController@ubahStatus');
$router->put('donasi/{no_bukti}/{sts_bayar}','Midtrans\DonasiController@ubahStatus');
$router->get('sis-midtrans-status2','Midtrans\BayarController@getStatusTransaksi');