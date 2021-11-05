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
    $router->get('sdm-karyawan-detail','Sdm\DashboardController@getDataKaryawanDetail');
    $router->get('sdm-client','Sdm\DashboardController@getKomposisiClient');
    $router->get('sdm-list-client','Sdm\DashboardController@getDataKomposisiClient');
    $router->get('sdm-bpjs-sehat','Sdm\DashboardController@getDataBPJSKesehatan');
    $router->get('sdm-bpjs-kerja','Sdm\DashboardController@getDataBPJSTenagaKerja');
    $router->get('sdm-umur','Sdm\DashboardController@getDataUmur');
    $router->get('sdm-gaji','Sdm\DashboardController@getDataGaji');
    $router->get('sdm-searchbpjs-sehat','Sdm\DashboardController@getListBPJSKesehatanTerdaftar');
    $router->get('sdm-searchnonbpjs-sehat','Sdm\DashboardController@getListBPJSKesehatanNonTerdaftar');
    $router->get('sdm-searchbpjs-kerja','Sdm\DashboardController@getListBPJSKetenagaanTerdaftar');
    $router->get('sdm-searchnonbpjs-kerja','Sdm\DashboardController@getListBPJSKetenagaanNonTerdaftar');

    $router->get('sdm-box-pegawai','Sdm\DashboardBoxController@getPegawai');
    $router->get('sdm-box-sehat','Sdm\DashboardBoxController@getBPJSSehat');
    $router->get('sdm-box-kerja','Sdm\DashboardBoxController@getBPJSKerja');
    $router->get('sdm-box-client','Sdm\DashboardBoxController@getClient');
    $router->get('sdm-box-gender','Sdm\DashboardBoxController@getJumlahJenisKelamin');
    
    $router->get('sdm-chart-pendidikan','Sdm\DashboardChartController@getPendidikan');
    $router->get('sdm-chart-unitp','Sdm\DashboardChartController@getUnitPie');
    $router->get('sdm-chart-unitc','Sdm\DashboardChartController@getUnitCol');
    $router->get('sdm-chart-umur','Sdm\DashboardChartController@getKelompokUmur');
    $router->get('sdm-chart-gaji','Sdm\DashboardChartController@getKelompokGaji');

});



?>