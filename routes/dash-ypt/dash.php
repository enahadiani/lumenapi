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

    $router->get('data-fp-box','DashYpt\DashboardFPController@getDataBoxFirst');  
    $router->get('data-fp-pdpt','DashYpt\DashboardFPController@getDataBoxPdpt');  
    $router->get('data-fp-beban','DashYpt\DashboardFPController@getDataBoxBeban');  
    $router->get('data-fp-shu','DashYpt\DashboardFPController@getDataBoxShu');  
    $router->get('data-fp-or','DashYpt\DashboardFPController@getDataBoxOr'); 
    $router->get('data-fp-lr','DashYpt\DashboardFPController@getDataBoxLabaRugi'); 
    $router->get('data-fp-pl','DashYpt\DashboardFPController@getDataBoxPerformLembaga'); 

    $router->get('data-fp-detail-perform','DashYpt\DashboardFPController@getDataPerformansiLembaga');  
    $router->get('data-fp-detail-lembaga','DashYpt\DashboardFPController@getDataPerLembaga');  
    $router->get('data-fp-detail-kelompok','DashYpt\DashboardFPController@getDataKelompokYoy');  
    $router->get('data-fp-detail-akun','DashYpt\DashboardFPController@getDataKelompokAkun');  

    $router->get('data-ccr-box','DashYpt\DashboardCCRController@getDataBox');  
    
    $router->get('data-cf-box','DashYpt\DashboardCFController@getDataBox');  
    $router->get('data-cf-chart-bulanan','DashYpt\DashboardCFController@getCashFlowBulanan');  


});



?>