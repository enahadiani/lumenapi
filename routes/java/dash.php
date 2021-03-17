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
    $router->get('periode','Java\DashboardController@getPeriode');
    
    $router->get('beban-unpaid','Java\DashboardController@getBebanUnpaid');
    $router->get('total-project','Java\DashboardController@getTotalProject');
    $router->get('anggaran-project','Java\DashboardController@getAnggaranProject');
    $router->get('tempo-project','Java\DashboardController@getTempoProject');

});



?>