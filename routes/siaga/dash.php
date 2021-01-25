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

$router->group(['middleware' => 'auth:siaga'], function () use ($router) {
    
    $router->get('summary','Siaga\DashboardController@getSummary');
    $router->get('dept','Siaga\DashboardController@getDept');
    $router->get('periode','Siaga\DashboardController@getPeriode');
    
});



?>