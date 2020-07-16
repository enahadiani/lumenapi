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
    //Filter Laporan
    $router->get('filter-periode','Toko\FilterController@getFilterPeriode');
    $router->get('filter-nik','Toko\FilterController@getFilterNIK');
    $router->get('filter-tanggal','Toko\FilterController@getFilterTanggal');
    $router->get('filter-bukti','Toko\FilterController@getFilterNoBukti');
    $router->get('filter-barang','Toko\FilterController@getFilterBarang');
    $router->get('filter-periode-close','Toko\FilterController@getFilterPeriodeClose');
    $router->get('filter-nik-close','Toko\FilterController@getFilterNIKClose');
    $router->get('filter-bukti-close','Toko\FilterController@getFilterNoBuktiClose');
    $router->get('filter-periode-pmb','Toko\FilterController@getFilterPeriodePmb');
    $router->get('filter-nik-pmb','Toko\FilterController@getFilterNIKPmb');
    $router->get('filter-bukti-pmb','Toko\FilterController@getFilterNoBuktiPmb');
    $router->get('filter-periode-retur','Toko\FilterController@getFilterPeriodeRetur');
    $router->get('filter-nik-retur','Toko\FilterController@getFilterNIKRetur');
    $router->get('filter-bukti-retur','Toko\FilterController@getFilterNoBuktiRetur');

    //Laporan
    $router->get('lap-barang','Toko\LaporanController@getReportBarang');
    $router->get('lap-closing','Toko\LaporanController@getReportClosing');
    $router->get('lap-penjualan','Toko\LaporanController@getReportPenjualan');
    $router->get('lap-pembelian','Toko\LaporanController@getReportPembelian');
    $router->get('lap-penjualan-harian','Toko\LaporanController@getReportPenjualanHarian');
    $router->get('lap-retur-beli','Toko\LaporanController@getReportReturBeli');

    $router->get('lap_kartu','Toko\LaporanController@getGlReportBukuBesar');
    $router->get('lap_saldo','Toko\LaporanController@getGlReportNeracaLajur');
    // $router->get('lap_laba_rugi','Toko\LaporanController@getGlReportLabaRugi');
    

});



?>