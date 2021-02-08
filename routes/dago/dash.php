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

$router->group(['middleware' => 'auth:dago'], function () use ($router) {
    $router->get('data-box','Dago\DashboardController@getDataBox');
    $router->get('top-agen','Dago\DashboardController@getTopAgen');
    $router->get('reg-harian','Dago\DashboardController@getRegHarian');
    $router->get('kuota-paket','Dago\DashboardController@getKuotaPaket');
    $router->get('kartu','Dago\DashboardController@getKartu');
    $router->get('dokumen','Dago\DashboardController@getDokumen');

});



?>