<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);

$router->group(['middleware' => 'cors'], function () use ($router) {
    
    $router->post('login', 'AuthController@loginTarbak');
    $router->get('hash_pass', 'AuthController@hashPasswordTarbak');
});

$router->group(['middleware' => 'auth:tarbak'], function () use ($router) {

    $router->get('profile', 'AdminTarbakController@profile');
    $router->get('users/{id}', 'AdminTarbakController@singleUser');
    $router->get('users', 'AdminTarbakController@allUsers');
    $router->get('cek_payload', 'AdminTarbakController@cekPayload');

    //Tahun Ajaran
    $router->get('pp','Sekolah\TahunAjaranController@getPP');
    $router->get('tahun_ajaran','Sekolah\TahunAjaranController@index');
    $router->get('tahun_ajaran/{kode_ta}','Sekolah\TahunAjaranController@show');
    $router->post('tahun_ajaran','Sekolah\TahunAjaranController@store');
    $router->put('tahun_ajaran/{kode_ta}','Sekolah\TahunAjaranController@update');
    $router->delete('tahun_ajaran/{kode_ta}','Sekolah\TahunAjaranController@destroy');




});