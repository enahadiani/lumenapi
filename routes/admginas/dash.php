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
    
    $router->get('top-selling','Toko\DashboardController@getTopSelling');
    $router->get('ctg-selling','Toko\DashboardController@getSellingCtg');
    $router->get('top-vendor','Toko\DashboardController@getTopVendor');
    $router->get('data-box','Toko\DashboardController@getDataBox');
    $router->get('buku-besar','Toko\DashboardController@getBukuBesar');
    $router->get('income-chart','Toko\DashboardController@getIncomeChart');
    $router->get('netprofit-chart','Toko\DashboardController@getNetProfitChart');
    $router->get('cogs-chart','Toko\DashboardController@getCOGSChart');
    $router->get('penjualan','Toko\DashboardController@getLapPnj');
    $router->get('vendor','Toko\DashboardController@getLapVendor');
    $router->get('jurnal','Toko\DashboardController@getJurnal');
});



?>