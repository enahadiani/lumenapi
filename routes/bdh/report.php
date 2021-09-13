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


$router->group(['middleware' => 'auth:yptkug'], function () use ($router) {
    $router->get('filter-periode','Bdh\FilterController@DataPeriode');
    $router->get('filter-nover','Bdh\FilterController@DataNoBuktiVerifikasi');

    $router->get('lap-verifikasi','Bdh\LaporanController@DataVerifikasi');

});



?>