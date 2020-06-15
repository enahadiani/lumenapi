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
    //Penjualan (POS)
    $router->get('penjualan-open','Toko\PenjualanController@getNoOpen');
    $router->post('penjualan','Toko\PenjualanController@store');
    $router->get('penjualan-nota','Toko\PenjualanController@getNota');
    $router->get('penjualan-bonus','Toko\PenjualanController@cekBonus');

});



?>