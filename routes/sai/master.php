<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 


$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);


$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    //Konten Kategori
    $router->get('konten-ktg','Sai\KontenKtgController@index');
    $router->post('konten-ktg','Sai\KontenKtgController@store');
    $router->put('konten-ktg','Sai\KontenKtgController@update');
    $router->delete('konten-ktg','Sai\KontenKtgController@destroy');

    $router->get('galeri','Sai\GaleriController@index');
    $router->post('galeri','Sai\GaleriController@store');
    $router->post('galeri-ubah','Sai\GaleriController@update');
    $router->delete('galeri','Sai\GaleriController@destroy');

    $router->get('konten','Sai\KontenController@index');
    $router->post('konten','Sai\KontenController@store');
    $router->put('konten','Sai\KontenController@update');
    $router->post('konten-draft','Sai\KontenController@draftKonten');
    $router->put('konten-draft','Sai\KontenController@updateDraft');
    $router->post('konten-publish','Sai\KontenController@publishKonten');
    $router->delete('konten','Sai\KontenController@destroy');

    $router->get('karyawan','Sai\KaryawanController@index');
    $router->post('karyawan','Sai\KaryawanController@store');
    $router->post('karyawan-ubah','Sai\KaryawanController@update');
    $router->delete('karyawan','Sai\KaryawanController@destroy');

    $router->get('customer','Sai\CustomerController@index');
    $router->post('customer','Sai\CustomerController@store');
    $router->post('customer-ubah','Sai\CustomerController@update');
    $router->delete('customer','Sai\CustomerController@destroy');

});



?>