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
    
    $router->get('top-selling','Esaku\Dashboard\DashboardController@getTopSelling');
    $router->get('ctg-selling','Esaku\Dashboard\DashboardController@getSellingCtg');
    $router->get('top-vendor','Esaku\Dashboard\DashboardController@getTopVendor');
    $router->get('data-box','Esaku\Dashboard\DashboardController@getDataBox');
    $router->get('buku-besar','Esaku\Dashboard\DashboardController@getBukuBesar');
    $router->get('income-chart','Esaku\Dashboard\DashboardController@getIncomeChart');
    $router->get('netprofit-chart','Esaku\Dashboard\DashboardController@getNetProfitChart');
    $router->get('cogs-chart','Esaku\Dashboard\DashboardController@getCOGSChart');
    $router->get('penjualan','Esaku\Dashboard\DashboardController@getLapPnj');
    $router->get('vendor','Esaku\Dashboard\DashboardController@getLapVendor');
    $router->get('jurnal','Esaku\Dashboard\DashboardController@getJurnal');
    
    $router->get('sdm-dash','Sdm\DashboardController@getDataDashboard');
    $router->get('sdm-karyawan','Sdm\DashboardController@getDataKaryawan');
});



?>