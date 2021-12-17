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
$router->options('{all:.*}', ['middleware' => 'cors', function () {
    return response('');
}]);

$router->group(['middleware' => 'auth:toko'], function () use ($router) {

    $router->get('top-selling', 'Esaku\Dashboard\DashboardController@getTopSelling');
    $router->get('ctg-selling', 'Esaku\Dashboard\DashboardController@getSellingCtg');
    $router->get('top-vendor', 'Esaku\Dashboard\DashboardController@getTopVendor');
    $router->get('data-box', 'Esaku\Dashboard\DashboardController@getDataBox');
    $router->get('buku-besar', 'Esaku\Dashboard\DashboardController@getBukuBesar');
    $router->get('income-chart', 'Esaku\Dashboard\DashboardController@getIncomeChart');
    $router->get('netprofit-chart', 'Esaku\Dashboard\DashboardController@getNetProfitChart');
    $router->get('cogs-chart', 'Esaku\Dashboard\DashboardController@getCOGSChart');
    $router->get('penjualan', 'Esaku\Dashboard\DashboardController@getLapPnj');
    $router->get('vendor', 'Esaku\Dashboard\DashboardController@getLapVendor');
    $router->get('jurnal', 'Esaku\Dashboard\DashboardController@getJurnal');

    $router->get('sdm-box-pegawai', 'Sdm\DashboardBoxController@getPegawai');
    $router->get('sdm-box-sehat', 'Sdm\DashboardBoxController@getBPJSSehat');
    $router->get('sdm-box-kerja', 'Sdm\DashboardBoxController@getBPJSKerja');
    $router->get('sdm-box-client', 'Sdm\DashboardBoxController@getClient');
    $router->post('sdm-box-client-market', 'Sdm\DashboardBoxController@getMarketClient');
    $router->get('sdm-box-total-client', 'Sdm\DashboardBoxController@getTotalClient');
    $router->get('sdm-box-gender', 'Sdm\DashboardBoxController@getJumlahJenisKelamin');

    $router->get('sdm-chart-pendidikan', 'Sdm\DashboardChartController@getPendidikan');
    $router->get('sdm-chart-unitp', 'Sdm\DashboardChartController@getUnitPie');
    $router->get('sdm-chart-unitc', 'Sdm\DashboardChartController@getUnitCol');
    $router->get('sdm-chart-umur', 'Sdm\DashboardChartController@getKelompokUmur');
    $router->get('sdm-chart-gaji', 'Sdm\DashboardChartController@getKelompokGaji');

    $router->get('sdm-detail-pegawai', 'Sdm\DashboardDetailPegawaiController@getDataPegawai');
    $router->get('sdm-detail-cv', 'Sdm\DashboardDetailPegawaiController@getDataPegawaiDetail');

    $router->get('sdm-detail-bpjs-sehat-komposisi', 'Sdm\DashboardDetailBPJSController@getKomposisiBPJSSehat');
    $router->get('sdm-detail-bpjs-kerja-komposisi', 'Sdm\DashboardDetailBPJSController@getKomposisiBPJSKerja');

    $router->get('sdm-detail-bpjs-sehat-register', 'Sdm\DashboardDetailBPJSController@getDataBPJSSehatRegister');
    $router->get('sdm-detail-bpjs-sehat-unregister', 'Sdm\DashboardDetailBPJSController@getDataBPJSSehatUnRegister');
    $router->get('sdm-detail-bpjs-kerja-register', 'Sdm\DashboardDetailBPJSController@getDataBPJSKerjaRegister');
    $router->get('sdm-detail-bpjs-kerja-unregister', 'Sdm\DashboardDetailBPJSController@getDataBPJSKerjaUnRegister');

    $router->get('sdm-detail-client-pie', 'Sdm\DashboardDetailClientController@getDataClientPie');
    $router->get('sdm-detail-client-data', 'Sdm\DashboardDetailClientController@getDataClient');
});
