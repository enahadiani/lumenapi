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
    $router->get('filter-pp','Yakes\FilterController@getFilterPp');

    //Laporan lokasi
    $router->get('lap-nrclajur','Yakes\LaporanController@getNrcLajur');
    $router->get('lap-nrclajur-grid','Yakes\LaporanController@getNrcLajurGrid');
    $router->get('lap-jurnal','Yakes\LaporanController@getJurnal');
    $router->get('lap-buktijurnal','Yakes\LaporanController@getBuktiJurnal');
    $router->get('lap-bukubesar','Yakes\LaporanController@getBukuBesar');
    $router->get('lap-neraca','Yakes\LaporanController@getNeraca');
    $router->get('lap-neraca-jamkespen','Yakes\LaporanController@getNeracaJamkespen');
    $router->get('lap-labarugi','Yakes\LaporanController@getLabaRugi');

    //Laporan pp
    $router->get('lap-nrclajur-pp','Yakes\LaporanController@getNrcLajurPp');
    $router->get('lap-neraca-pp','Yakes\LaporanController@getNeracaPp');
    $router->get('lap-labarugi-pp','Yakes\LaporanController@getLabaRugiPp');

    //Laporan jejer
    $router->get('lap-nrclajur-jejer','Yakes\LaporanController@getNrcLajurJejer');
    $router->get('lap-neraca-jejer','Yakes\LaporanController@getNeracaJejer');
    $router->get('lap-labarugi-jejer','Yakes\LaporanController@getLabaRugiJejer');

    //email
    $router->post('send-laporan','Yakes\LaporanController@sendMail');

});



?>