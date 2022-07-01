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
    $router->get('dataof-modul','Siaga\DashboardController@getDataOfModul');
    $router->get('data-other','Siaga\DashboardController@getDataOther');
    
    $router->get('data-fp-default-filter','Siaga\DashboardFPController@getDefaultFilter');
    $router->get('data-fp-box','Siaga\DashboardFPController@getDataBox');
    $router->get('data-fp-kontribusi-filter','Siaga\DashboardFPController@getFilterKontribusi');
    $router->get('data-fp-kontribusi','Siaga\DashboardFPController@getKontribusi');
    $router->get('data-fp-per-bulan','Siaga\DashboardFPController@getFPBulan');
    $router->get('data-fp-margin','Siaga\DashboardFPController@getMargin');
    
    $router->get('data-pend-box','Siaga\DashboardPendController@getDataBox');
    $router->get('data-pend-kontribusi','Siaga\DashboardPendController@getKontribusi');
    $router->get('data-pend-ytdvsyoy','Siaga\DashboardPendController@getYTDvsYoY');
    $router->get('data-pend-per-bulan','Siaga\DashboardPendController@getPendBulan');
    $router->get('data-pend-rkavsreal','Siaga\DashboardPendController@getRKAvsReal');
    
});



?>