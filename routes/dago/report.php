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

$router->group(['middleware' => 'auth:dago'], function () use ($router) {
    //Filter Laporan
    $router->get('filter-periode','Dago\FilterController@getFilterPeriode');
    $router->get('filter-paket','Dago\FilterController@getFilterPaket');
    $router->get('filter-jadwal','Dago\FilterController@getFilterJadwal');
    $router->get('filter-noreg','Dago\FilterController@getFilterNoReg');
    $router->get('filter-peserta','Dago\FilterController@getFilterPeserta');
    $router->get('filter-kwitansi','Dago\FilterController@getFilterKwitansi');
    $router->get('filter-jk','Dago\FilterController@getFilterJK');
    $router->get('filter-terima','Dago\FilterController@getFilterTerima');
    $router->get('filter-periode-bayar','Dago\FilterController@getFilterPeriodeBayar');

    // FILTER VERSI 2
    $router->get('filter2-periode','Dago\FilterController@getFilter2Periode');
    $router->get('filter2-paket','Dago\FilterController@getFilter2Paket');
    $router->get('filter2-jadwal','Dago\FilterController@getFilter2Jadwal');
    $router->get('filter2-noreg','Dago\FilterController@getFilter2NoReg');
    $router->get('filter2-peserta','Dago\FilterController@getFilter2Peserta');
    $router->get('filter2-kwitansi','Dago\FilterController@getFilter2Kwitansi');
    $router->get('filter2-jk','Dago\FilterController@getFilter2JK');
    $router->get('filter2-status','Dago\FilterController@getFilter2Status');
    $router->get('filter2-terima','Dago\FilterController@getFilter2Terima');
    $router->get('filter2-periode-bayar','Dago\FilterController@getFilter2PeriodeBayar');


    //Pihak ketiga
   
    //Laporan
    $router->get('lap-mku-operasional','Dago\LapInternalController@getMkuOperasional');
    $router->get('lap-mku-keuangan','Dago\LapInternalController@getMkuKeuangan');
    $router->get('lap-paket','Dago\LapInternalController@getPaket');
    $router->get('lap-dokumen','Dago\LapInternalController@getDokumen');
    $router->get('lap-jamaah','Dago\LapInternalController@getJamaah');

    $router->get('lap-form-registrasi','Dago\LapInternalController@getFormRegistrasi');
    $router->get('lap-registrasi','Dago\LapInternalController@getRegistrasi');
    $router->get('lap-pembayaran','Dago\LapInternalController@getPembayaran');
    $router->get('lap-rekap-saldo','Dago\LapInternalController@getRekapSaldo');
    $router->get('lap-detail-saldo','Dago\LapInternalController@getDetailSaldo');
    $router->get('lap-detail-tagihan','Dago\LapInternalController@getDetailTagihan');
    $router->get('lap-detail-bayar','Dago\LapInternalController@getDetailBayar');
    $router->get('lap-kartu-pembayaran','Dago\LapInternalController@getKartuPembayaran');
    $router->get('lap-terima','Dago\LapInternalController@getTandaTerima');
    $router->get('lap-jurnal','Dago\LapInternalController@getJurnal');

    // LAPORAN VERSI 2

    $router->get('lap2-mku-operasional','Dago\LapInternal2Controller@getMkuOperasional');
    $router->get('lap2-mku-keuangan','Dago\LapInternal2Controller@getMkuKeuangan');
    $router->get('lap2-paket','Dago\LapInternal2Controller@getPaket');
    $router->get('lap2-dokumen','Dago\LapInternal2Controller@getDokumen');
    $router->get('lap2-jamaah','Dago\LapInternal2Controller@getJamaah');

    $router->get('lap2-form-registrasi','Dago\LapInternal2Controller@getFormRegistrasi');
    $router->get('lap2-registrasi','Dago\LapInternal2Controller@getRegistrasi');
    $router->get('lap2-pembayaran','Dago\LapInternal2Controller@getPembayaran');
    $router->get('lap2-rekap-saldo','Dago\LapInternal2Controller@getRekapSaldo');
    $router->get('lap2-detail-saldo','Dago\LapInternal2Controller@getDetailSaldo');
    $router->get('lap2-detail-tagihan','Dago\LapInternal2Controller@getDetailTagihan');
    $router->get('lap2-detail-bayar','Dago\LapInternal2Controller@getDetailBayar');
    $router->get('lap2-kartu-pembayaran','Dago\LapInternal2Controller@getKartuPembayaran');
    $router->get('lap2-terima','Dago\LapInternal2Controller@getTandaTerima');
    $router->get('lap2-jurnal','Dago\LapInternal2Controller@getJurnal');



});



?>