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
    //Filter Laporan
    $router->get('filter-periode','Dago\FilterController@getFilterPeriode');
    $router->get('filter-paket','Dago\FilterController@getFilterPaket');
    $router->get('filter-jadwal','Dago\FilterController@getFilterJadwal');
    $router->get('filter-noreg','Dago\FilterController@getFilterNoReg');
    $router->get('filter-peserta','Dago\FilterController@getFilterPeserta');
    $router->get('filter-kwitansi','Dago\FilterController@getFilterKwitansi');
    $router->get('filter-jk','Dago\FilterController@getFilterJK');

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
    $router->get('lap-kartu-pembayaran','Dago\LapInternalController@getKartuPembayaran');

});



?>