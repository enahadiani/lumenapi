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
    
    //Jamaah
    $router->get('jamaah','Dago\JamaahController@index');
    $router->post('jamaah','Dago\JamaahController@store');
    $router->get('jamaah-detail','Dago\JamaahController@edit');
    $router->get('jamaah-detail-id','Dago\JamaahController@editById');
    $router->post('jamaah-ubah','Dago\JamaahController@update');
    $router->delete('jamaah','Dago\JamaahController@destroy');
    $router->get('cek-ktp','Dago\JamaahController@cekKTPChange');

    //Registrasi
    $router->get('registrasi','Dago\RegistrasiController@index');
    $router->post('registrasi','Dago\RegistrasiController@store');
    $router->get('registrasi-detail','Dago\RegistrasiController@edit');
    $router->put('registrasi','Dago\RegistrasiController@update');
    $router->delete('registrasi','Dago\RegistrasiController@destroy');
    $router->get('jadwal-detail','Dago\JadwalController@show');
    $router->get('jadwal-detail2','Dago\JadwalController@showJadwal');
    $router->get('biaya-tambahan','Dago\RegistrasiController@getBiayaTambahan');
    $router->get('biaya-dokumen','Dago\RegistrasiController@getBiayaDokumen');
    $router->get('pp','Dago\RegistrasiController@getPP');
    $router->get('harga','Dago\RegistrasiController@getHarga');
    $router->get('quota','Dago\RegistrasiController@getQuota');
    $router->get('harga-room','Dago\RegistrasiController@getHargaRoom');
    $router->get('no-marketing','Dago\RegistrasiController@getNoMarketing');
    $router->get('registrasi-preview','Dago\RegistrasiController@getPreview');

    //Registrasi Group
    $router->get('registrasi-group','Dago\RegistrasiGroupController@getGroup');
    $router->post('registrasi-group','Dago\RegistrasiGroupController@store');
    
    //Pembayaran
    $router->get('pembayaran','Dago\PembayaranController@getRegistrasi');
    $router->get('pembayaran-history','Dago\PembayaranController@index');
    $router->post('pembayaran','Dago\PembayaranController@store');
    $router->get('pembayaran-detail','Dago\PembayaranController@show');
    $router->get('pembayaran-edit','Dago\PembayaranController@edit');
    $router->put('pembayaran','Dago\PembayaranController@update');
    $router->delete('pembayaran','Dago\PembayaranController@destroy');
    $router->get('pembayaran-rekbank','Dago\PembayaranController@getRekBank');
    $router->get('pembayaran-preview','Dago\PembayaranController@getPreview');
    $router->get('pembayaran-kurs','Dago\PembayaranController@getKurs');

    //Closing Jadwal
    $router->get('closing-jadwal-reg','Dago\ClosingJadwalController@getRegistrasi');
    $router->get('closing-jadwal','Dago\ClosingJadwalController@index');
    $router->post('closing-jadwal','Dago\ClosingJadwalController@store');
    $router->get('closing-jadwal-detail','Dago\ClosingJadwalController@show');
    $router->put('closing-jadwal','Dago\ClosingJadwalController@update');
    $router->delete('closing-jadwal','Dago\ClosingJadwalController@destroy');

    //NON CASH
    $router->get('noncash','Dago\PembayaranNonCashController@getRegistrasi');
    $router->post('noncash','Dago\PembayaranNonCashController@store');
    $router->get('noncash-detail','Dago\PembayaranNonCashController@show');
    $router->get('noncash-rekbank','Dago\PembayaranNonCashController@getRekBank');

    //Verifikasi
    $router->get('verifikasi','Dago\VerifikasiController@index');
    $router->get('verifikasi-edit','Dago\VerifikasiController@edit');
    $router->put('verifikasi','Dago\VerifikasiController@update');
    $router->get('verifikasi-histori','Dago\VerifikasiController@histori');
    $router->delete('verifikasi','Dago\VerifikasiController@destroy');

    //UploadDok
    $router->get('upload-dok','Dago\UploadDokController@index');
    $router->get('upload-dok-detail','Dago\UploadDokController@show');
    $router->post('upload-dok','Dago\UploadDokController@store');
    $router->delete('upload-dok','Dago\UploadDokController@destroy');

    //Pembayaran Group
    
    $router->get('pembayaran-group','Dago\PembayaranGroupController@index');
    $router->get('pembayaran-group-nobukti','Dago\PembayaranGroupController@getNoBukti');
    $router->get('pembayaran-group-reg','Dago\PembayaranGroupController@getRegistrasi');
    $router->get('pembayaran-group-det','Dago\PembayaranGroupController@getDetailBiaya');
    $router->post('pembayaran-group-det','Dago\PembayaranGroupController@simpanDetTmp');
    $router->post('pembayaran-group-det2','Dago\PembayaranGroupController@simpanDetTmp2');
    $router->delete('pembayaran-group-det','Dago\PembayaranGroupController@destroyDetTmp');
    $router->post('pembayaran-group','Dago\PembayaranGroupController@store');
    $router->get('pembayaran-group-edit','Dago\PembayaranGroupController@edit');
    $router->put('pembayaran-group','Dago\PembayaranGroupController@update');
    $router->delete('pembayaran-group','Dago\PembayaranGroupController@destroy');
    $router->get('jamaah-group','Dago\PembayaranGroupController@getJamaahGroup');
});



?>