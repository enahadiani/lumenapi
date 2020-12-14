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
    //Filter Laporan
    $router->get('filter-akun','Ypt\FilterController@getFilterAkun');
    $router->get('filter-periode-keu','Ypt\FilterController@getFilterPeriodeKeuangan');
    $router->get('filter-fs','Ypt\FilterController@getFilterFS');
    $router->get('filter-level','Ypt\FilterController@getFilterLevel');
    $router->get('filter-format','Ypt\FilterController@getFilterFormat');
    $router->get('filter-sumju','Ypt\FilterController@getFilterYaTidak');
    $router->get('filter-modul','Ypt\FilterController@getFilterModul');
    $router->get('filter-bukti-jurnal','Ypt\FilterController@getFilterBuktiJurnal');
    $router->get('filter-mutasi','Ypt\FilterController@getFilterYaTidak');
    $router->get('filter-pp','Ypt\FilterController@getFilterPp');

    //Laporan lokasi
    $router->get('lap-nrclajur','Ypt\LaporanController@getNrcLajur');
    $router->get('lap-nrclajur-grid','Ypt\LaporanController@getNrcLajurGrid');
    $router->get('lap-jurnal','Ypt\LaporanController@getJurnal');
    $router->get('lap-buktijurnal','Ypt\LaporanController@getBuktiJurnal');
    $router->get('lap-bukubesar','Ypt\LaporanController@getBukuBesar');
    $router->get('lap-neraca','Ypt\LaporanController@getNeraca');
    $router->get('lap-neraca-jamkespen','Ypt\LaporanController@getNeracaJamkespen');
    $router->get('lap-labarugi','Ypt\LaporanController@getLabaRugi');
    $router->get('lap-perubahan-aset-neto','Ypt\LaporanController@getPerubahanAsetNeto');
    $router->get('lap-aset-neto','Ypt\LaporanController@getAsetNeto');
    $router->get('lap-arus-kas','Ypt\LaporanController@getArusKas');

    //Laporan pp
    $router->get('lap-nrclajur-pp','Ypt\LaporanController@getNrcLajurPp');
    $router->get('lap-neraca-pp','Ypt\LaporanController@getNeracaPp');
    $router->get('lap-labarugi-pp','Ypt\LaporanController@getLabaRugiPp');

    //Laporan jejer
    $router->get('lap-nrclajur-jejer','Ypt\LaporanController@getNrcLajurJejer');
    $router->get('lap-neraca-jejer','Ypt\LaporanController@getNeracaJejer');
    $router->get('lap-labarugi-jejer','Ypt\LaporanController@getLabaRugiJejer');

    //email
    $router->post('send-laporan','Ypt\LaporanController@sendMail');

});



?>