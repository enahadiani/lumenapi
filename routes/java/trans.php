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


$router->group(['middleware' => 'auth:toko'], function () use ($router) {
    // Helper 
    $router->get('customer','Java\ProyekController@getCustomer');
    $router->get('vendor','Java\BiayaProyekController@getVendor');
    $router->get('proyek-check','Java\ProyekController@checkProyek');
    $router->get('kontrak-check','Java\VendorController@checkKontrak');
    $router->get('proyek-rab-cbbl','Java\RabProyekController@getProyek');
    $router->get('proyek-biaya-cbbl','Java\BiayaProyekController@getProyek');
    $router->get('tagihan-proyek-cbbl','Java\TagihanController@getProyek');

    //Proyek
    $router->get('proyek','Java\ProyekController@index');
    $router->post('proyek','Java\ProyekController@store');
    $router->put('proyek','Java\ProyekController@update');
    $router->delete('proyek','Java\ProyekController@destroy');

    // Anggaran Proyek
    $router->get('rab-proyek','Java\RabProyekController@index');
    $router->post('rab-proyek','Java\RabProyekController@store');
    $router->put('rab-proyek','Java\RabProyekController@update');
    $router->delete('rab-proyek','Java\RabProyekController@destroy');

    //Biaya Proyek
    $router->get('biaya-proyek','Java\BiayaProyekController@index');
    $router->post('biaya-proyek','Java\BiayaProyekController@store');
    $router->put('biaya-proyek','Java\BiayaProyekController@update');
    $router->delete('biaya-proyek','Java\BiayaProyekController@destroy');

    //Tagihan Proyek
    $router->get('tagihan-proyek','Java\TagihanProyekController@index');
    $router->post('tagihan-proyek','Java\TagihanProyekController@store');
    $router->put('tagihan-proyek','Java\TagihanProyekController@update');
    $router->delete('tagihan-proyek','Java\TagihanProyekController@destroy');

});



?>