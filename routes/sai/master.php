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
    $router->get('konten-ktg-detail','Sai\KontenKtgController@show');
    $router->post('konten-ktg','Sai\KontenKtgController@store');
    $router->put('konten-ktg','Sai\KontenKtgController@update');
    $router->delete('konten-ktg','Sai\KontenKtgController@destroy');

    $router->get('galeri','Sai\GaleriController@index');
    $router->post('galeri','Sai\GaleriController@store');
    $router->put('galeri','Sai\GaleriController@update');
    $router->delete('galeri','Sai\GaleriController@destroy');
});



?>