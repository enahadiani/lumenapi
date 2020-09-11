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


$router->group(['middleware' => 'auth:yakes'], function () use ($router) {
    //Filter Laporan
    $router->get('filter-akun','Yakes\FilterController@getFilterAkun');
    $router->get('filter-periode-keu','Yakes\FilterController@getFilterPeriodeKeuangan');
    $router->get('filter-fs','Yakes\FilterController@getFilterFS');
    $router->get('filter-level','Yakes\FilterController@getFilterLevel');
    $router->get('filter-format','Yakes\FilterController@getFilterFormat');
    $router->get('filter-sumju','Yakes\FilterController@getFilterYaTidak');
    $router->get('filter-modul','Yakes\FilterController@getFilterModul');
    $router->get('filter-bukti-jurnal','Yakes\FilterController@getFilterBuktiJurnal');
    $router->get('filter-mutasi','Yakes\FilterController@getFilterYaTidak');

    //Laporan
    $router->get('lap-nrclajur','Yakes\LaporanController@getNrcLajur');
    $router->get('lap-jurnal','Yakes\LaporanController@getJurnal');
    $router->get('lap-buktijurnal','Yakes\LaporanController@getBuktiJurnal');
    $router->get('lap-bukubesar','Yakes\LaporanController@getBukuBesar');
    $router->get('lap-neraca','Yakes\LaporanController@getNeraca');
    $router->get('lap-labarugi','Yakes\LaporanController@getLabaRugi');

    $router->post('send-laporan','Yakes\LaporanController@sendMail');

});



?>