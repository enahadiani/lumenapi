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
    
    $router->get('filter-pp','Sukka\FilterController@getFilterPP');
    $router->get('filter-lokasi','Sukka\FilterController@getFilterLokasi');
    $router->get('filter-periode-juskeb','Sukka\FilterController@getFilterPeriodeJuskeb');
    $router->get('filter-bukti-juskeb','Sukka\FilterController@getFilterBuktiJuskeb');
    $router->get('filter-default-juskeb','Sukka\FilterController@getFilterDefaultJuskeb');
    $router->get('filter-periode-rra','Sukka\FilterController@getFilterPeriodeRRA');
    $router->get('filter-bukti-rra','Sukka\FilterController@getFilterBuktiRRA');
    $router->get('filter-default-rra','Sukka\FilterController@getFilterDefaultRRA');

    $router->get('lap-aju-form','Sukka\LaporanController@getAjuForm');
    $router->get('lap-posisi-juskeb','Sukka\LaporanController@getPosisiJuskeb');
    $router->get('lap-history-app-juskeb','Sukka\LaporanController@getHistoryAppJuskeb');
    
    $router->get('lap-rra-form','Sukka\LaporanController@getRRAForm');
    $router->get('lap-posisi-rra','Sukka\LaporanController@getPosisiRRA');
    $router->get('lap-history-app-rra','Sukka\LaporanController@getHistoryAppRRA');
});



?>