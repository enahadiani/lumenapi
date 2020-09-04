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
   
    //Bidang
    $router->get('bidang','Wisata\BidangController@index');
    $router->post('bidang','Wisata\BidangController@store');
    $router->put('bidang','Wisata\BidangController@update');
    $router->delete('bidang','Wisata\BidangController@destroy');    

    //Mitra
    $router->get('mitra','Wisata\MitraController@index');
    $router->get('mitrabid','Wisata\MitraController@edit');
    $router->post('mitra','Wisata\MitraController@store');
    $router->put('mitra','Wisata\MitraController@update');
    $router->delete('mitra','Wisata\MitraController@destroy');    

});



?>