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

    //Pihak ketiga
   
    //Laporan
    $router->get('lap-mku-operasional','Dago\LaporanController@getMkuOperasional');
    $router->get('lap-mku-keuangan','Dago\LaporanController@getMkuKeuangan');
    $router->get('lap-paket','Dago\LaporanController@getPaket');
    $router->get('lap-dokumen','Dago\LaporanController@getDokumen');
    $router->get('lap-jamaah','Dago\LaporanController@getJamaah');

    $router->get('lap-form-registrasi','Dago\LaporanController@getFormRegistrasi');
    $router->get('lap-registrasi','Dago\LaporanController@getRegistrasi');
    $router->get('lap-pembayaran','Dago\LaporanController@getPembayaran');
    $router->get('lap-rekap-saldo','Dago\LaporanController@getRekapSaldo');
    $router->get('lap-kartu-pembayaran','Dago\LaporanController@getKartuPembayaran');

});



?>