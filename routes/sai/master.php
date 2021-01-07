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

    $router->get('cust','Sai\CustomerController@index');
    $router->post('cust','Sai\CustomerController@store');
    $router->post('cust-ubah','Sai\CustomerController@update');
    $router->delete('cust','Sai\CustomerController@destroy');

    $router->get('proyek','Sai\ProyekController@index');
    $router->post('proyek','Sai\ProyekController@store');
    $router->put('proyek-ubah','Sai\ProyekController@update');
    $router->delete('proyek','Sai\ProyekController@destroy');

    $router->get('user','Sai\HakaksesController@index');
    $router->post('user','Sai\HakaksesController@store');
    $router->get('user-detail','Sai\HakaksesController@show');
    $router->put('user','Sai\HakaksesController@update');
    $router->delete('user','Sai\HakaksesController@destroy');
    $router->get('user-menu','Sai\HakaksesController@getMenu');

    $router->get('lampiran','Sai\LampiranController@index');
    $router->post('lampiran','Sai\LampiranController@store');
    $router->put('lampiran','Sai\LampiranController@update');
    $router->delete('lampiran','Sai\LampiranController@destroy');
    
    $router->get('modul','Sai\ModulController@index');
    $router->post('modul','Sai\ModulController@store');
    $router->put('modul','Sai\ModulController@update');
    $router->delete('modul','Sai\ModulController@destroy');

});



?>