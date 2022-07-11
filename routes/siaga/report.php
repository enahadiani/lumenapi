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
    $router->get('filter-pp','Siaga\FilterController@getFilterPP');
    $router->get('filter-periode','Siaga\FilterController@getFilterPeriode');
    $router->get('filter-nobukti','Siaga\FilterController@getFilterNoBukti');
    $router->get('filter-nobukti-spb','Siaga\FilterController@getFilterNoBuktiSPB');

    $router->get('lap-posisi','Siaga\LaporanController@getPosisi');
    $router->get('lap-history-app','Siaga\LaporanController@getHistoryApp');
    $router->get('lap-aju-form','Siaga\LaporanController@getAjuForm');
    $router->get('lap-posisi-spb','Siaga\LaporanController@getPosisiSPB');
    $router->get('lap-history-app-spb','Siaga\LaporanController@getHistoryAppSPB');
    $router->get('lap-aju-form-spb','Siaga\LaporanController@getAjuFormSPB');


    //Report Laba-rugi Anggaran
    $router->get('lap-labarugi-agg','Siaga\LaporanController@getLabaRugiAgg');

    $router->get('filter-default-labarugi-agg','Siaga\FilterController@getFilterDefaultLabaRugiAgg');
    $router->get('filter-periode-keu','Siaga\FilterController@getFilterPeriodeKeuangan');
    $router->get('filter-fs','Siaga\FilterController@getFilterFS');
});

//tess


?>