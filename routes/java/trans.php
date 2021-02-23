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
    $router->get('proyek-check','Java\ProyekController@checkProyek');
    $router->get('kontrak-check','Java\VendorController@checkKontrak');

    //Proyek
    $router->get('proyek','Java\ProyekController@index');
    $router->post('proyek','Java\ProyekController@store');
    $router->put('proyek','Java\ProyekController@update');
    $router->delete('proyek','Java\ProyekController@destroy');

});



?>