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
    $router->get('filter-periodever','Bdh\FilterController@DataPeriodeVerifikasi');
    $router->get('filter-periodespb','Bdh\FilterController@DataPeriodeSPB');
    $router->get('filter-periodebayar','Bdh\FilterController@DataPeriodeBayar');
    $router->get('filter-periodepb','Bdh\FilterController@DataPeriodePB');
    $router->get('filter-periodepanjar','Bdh\FilterController@DataPeriodePanjar');
    $router->get('filter-periode','Bdh\FilterController@DataPeriode');
    $router->get('filter-nik','Bdh\FilterController@DataNIK');
    
    $router->get('filter-nover','Bdh\FilterController@DataNoBuktiVerifikasi');
    $router->get('filter-nospb','Bdh\FilterController@DataNoBuktiSPB');
    $router->get('filter-nobayar','Bdh\FilterController@DataNoBuktiBayar');
    $router->get('filter-nojurfinalpertanggungpanjar','Bdh\FilterController@DataNoBuktiJurnalFinalPertanggungPanjar');
    $router->get('filter-nopb','Bdh\FilterController@DataNoBuktiPB');
    $router->get('filter-nopanjar','Bdh\FilterController@DataNoBuktiPanjar');

    $router->post('lap-verifikasi','Bdh\LaporanController@DataVerifikasi');
    $router->post('lap-spb','Bdh\LaporanController@DataSPB');
    $router->post('lap-bayar','Bdh\LaporanController@DataPembayaran');
    $router->post('lap-transbank','Bdh\LaporanController@DataTransferBank');
    $router->post('lap-jurfinalpertanggungpanjar','Bdh\LaporanController@DataJurnalFinalPertanggungPanjar');
    
    $router->post('lap-pb','Bdh\LaporanBebanController@DataPB');
    $router->post('lap-posisipertanggungpb','Bdh\LaporanBebanController@DataPosisiPertanggunganPB');
    
    $router->post('lap-panjar','Bdh\LaporanPanjarController@DataPanjar');
    $router->post('lap-cairpanjar','Bdh\LaporanPanjarController@DataPencairanPanjar');
    $router->post('lap-posisiajupanjar','Bdh\LaporanPanjarController@DataPosisiAjuPanjar');
    $router->post('lap-tanggungpanjar','Bdh\LaporanPanjarController@DataTanggungPanjar');
    $router->post('lap-posisitanggungpanjar','Bdh\LaporanPanjarController@DataPosisiTanggungPanjar');
    $router->post('lap-saldopanjar','Bdh\LaporanPanjarController@DataSaldoPanjar');
    
    $router->post('lap-bukaif','Bdh\LaporanImprestFundController@DataPembukaanIF');
    $router->post('lap-imburseif','Bdh\LaporanImprestFundController@DataImburseIF');
    $router->post('lap-posisiimburseif','Bdh\LaporanImprestFundController@DataPosisiImburseIF');
    $router->post('lap-kartuif','Bdh\LaporanImprestFundController@DataKartuIF');

});



?>