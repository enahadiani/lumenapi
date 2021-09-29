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
    // PERTANGGUNGAN BEBAN
    $router->get('ptg-beban-nobukti','Bdh\PtgBebanController@generateNo');
    $router->get('ptg-beban','Bdh\PtgBebanController@index');
    $router->get('ptg-beban-detail','Bdh\PtgBebanController@show');
    $router->post('ptg-beban','Bdh\PtgBebanController@store');
    $router->post('ptg-beban-ubah','Bdh\PtgBebanController@update');
    $router->delete('ptg-beban','Bdh\PtgBebanController@destroy');

    $router->get('ptg-beban-pp','Bdh\PtgBebanController@getPP');
    $router->get('ptg-beban-akun','Bdh\PtgBebanController@getAkun');
    $router->get('ptg-beban-drk','Bdh\PtgBebanController@getDRK');
    $router->get('nik-buat','Bdh\PtgBebanController@getNIKBuat');
    $router->get('nik-tahu','Bdh\PtgBebanController@getNIKTahu');
    $router->get('nik-ver','Bdh\PtgBebanController@getNIKVer');
    $router->get('ptg-beban-budget','Bdh\PtgBebanController@cekBudget');
    $router->get('ptg-beban-jenis-dok','Bdh\PtgBebanController@getJenisDokumen');

    // SERAH TERIMA DOK
    $router->get('serah-dok-pb','Bdh\SerahTerimaOnlineController@getPB');
    $router->get('serah-dok-detail','Bdh\SerahTerimaOnlineController@show');
    $router->post('serah-dok','Bdh\SerahTerimaOnlineController@store');
    $router->get('serah-dok-nik','Bdh\SerahTerimaOnlineController@getNIK');


    // VERIFIKASI DOKUMEN
    $router->get('ver-dok-nobukti','Bdh\VerDokController@generateNo');
    $router->get('ver-dok','Bdh\VerDokController@index');
    $router->get('ver-dok-detail','Bdh\VerDokController@show');
    $router->post('ver-dok','Bdh\VerDokController@store');
    $router->delete('ver-dok','Bdh\VerDokController@destroy');

    $router->get('ver-dok-pb','Bdh\VerDokController@getPB');

    // VERIFIKASI PAJAK
    $router->get('ver-pajak-nobukti','Bdh\VerPajakController@generateNo');
    $router->get('ver-pajak','Bdh\VerPajakController@index');
    $router->get('ver-pajak-detail','Bdh\VerPajakController@show');
    $router->post('ver-pajak','Bdh\VerPajakController@store');
    $router->post('ver-pajak-ubah','Bdh\VerPajakController@update');
    $router->delete('ver-pajak','Bdh\VerPajakController@destroy');

    $router->get('ver-pajak-pp','Bdh\VerPajakController@getPP');
    $router->get('ver-pajak-jenis-dok','Bdh\VerPajakController@getJenisDokumen');
    $router->get('ver-pajak-akun','Bdh\VerPajakController@getAkun');
    $router->get('ver-pajak-drk','Bdh\VerPajakController@getDRK');
    $router->get('ver-pajak-akun-gar','Bdh\VerPajakController@getAkunGar');

    // VERIFIKASI AKUN
    $router->get('ver-akun-nobukti','Bdh\VerAkunController@generateNo');
    $router->get('ver-akun','Bdh\VerAkunController@index');
    $router->get('ver-akun-detail','Bdh\VerAkunController@show');
    $router->post('ver-akun','Bdh\VerAkunController@store');
    $router->post('ver-akun-ubah','Bdh\VerAkunController@update');
    $router->delete('ver-akun','Bdh\VerAkunController@destroy');

    $router->get('ver-akun-pp','Bdh\VerAkunController@getPP');
    $router->get('ver-akun-jenis-dok','Bdh\VerAkunController@getJenisDokumen');
    $router->get('ver-akun-akun','Bdh\VerAkunController@getAkun');
    $router->get('ver-akun-drk','Bdh\VerAkunController@getDRK');
    $router->get('ver-akun-budget','Bdh\VerAkunController@cekBudget');
    
    $router->delete('hapus-dokumen','Bdh\VerAkunController@deleteDokPB');

    // SPB
    $router->get('spb-nobukti','Bdh\SPBController@generateNo');
    $router->get('spb-pb-list','Bdh\SPBController@getPBList');
    $router->get('spb','Bdh\SPBController@index');
    $router->get('spb-detail','Bdh\SPBController@show');
    $router->post('spb','Bdh\SPBController@store');
    $router->post('spb-ubah','Bdh\SPBController@update');
    $router->delete('spb','Bdh\SPBController@destroy');

    $router->get('spb-nik-bdh','Bdh\SPBController@getNIKBdh');
    $router->get('spb-nik-fiat','Bdh\SPBController@getNIKFiat');
    $router->get('spb-rek-transfer','Bdh\SPBController@getRekTransfer');
    $router->get('spb-tambah-pb','Bdh\SPBController@getPBTambah');

    // Bayar SPB
    $router->get('bayar-spb-nobukti','Bdh\BayarSPBController@generateNo');
    $router->get('bayar-spb-list','Bdh\BayarSPBController@getSPBList');
    $router->get('bayar-spb','Bdh\BayarSPBController@index');
    $router->get('bayar-spb-detail','Bdh\BayarSPBController@show');
    $router->post('bayar-spb','Bdh\BayarSPBController@store');
    $router->post('bayar-spb-ubah','Bdh\BayarSPBController@update');
    $router->delete('bayar-spb','Bdh\BayarSPBController@destroy');

    $router->get('bayar-spb-akun','Bdh\BayarSPBController@getAkun');
    $router->get('bayar-spb-pp','Bdh\BayarSPBController@getPP');
    $router->get('bayar-spb-rek-transfer','Bdh\BayarSPBController@getRekTransfer');
    $router->get('bayar-spb-akun-kasbank','Bdh\BayarSPBController@getAkunKasBank');

    // PENGAJUAN DROPING 
    $router->get('droping-aju-nobukti','Bdh\PengajuanDropingController@generateNo');
    $router->get('droping-aju','Bdh\PengajuanDropingController@index');
    $router->get('droping-aju-detail','Bdh\PengajuanDropingController@show');
    $router->post('droping-aju','Bdh\PengajuanDropingController@store');
    $router->post('droping-aju-ubah','Bdh\PengajuanDropingController@update');
    $router->delete('droping-aju','Bdh\PengajuanDropingController@destroy');

    $router->get('droping-aju-pp','Bdh\PengajuanDropingController@getPP');
    $router->get('droping-aju-akun','Bdh\PengajuanDropingController@getAkun');
    $router->get('droping-aju-budget','Bdh\PengajuanDropingController@cekBudget');
});

?>