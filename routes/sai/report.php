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


$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('filter-periode','Sai\FilterController@getFilterPeriode');
    $router->get('filter-cust','Sai\FilterController@getFilterCust');
    $router->get('filter-kontrak','Sai\FilterController@getFilterKontrak');
    $router->get('filter-bukti','Sai\FilterController@getFilterNoBukti');

    $router->get('lap-tagihan','Sai\LaporanController@getReportTagihan'); 
    $router->get('lap-kuitansi','Sai\LaporanController@getReportKuitansi');  
    $router->get('lap-tagihan-detail','Sai\LaporanController@getReportTagihanDetail');    

});



?>