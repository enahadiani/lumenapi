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
    
    //Jamaah
    $router->get('jamaah','Dago\JamaahController@index');
    $router->post('jamaah','Dago\JamaahController@store');
    $router->get('jamaah-detail','Dago\JamaahController@edit');
    $router->get('jamaah-detail-id','Dago\JamaahController@editById');
    $router->post('jamaah-ubah','Dago\JamaahController@update');
    $router->delete('jamaah','Dago\JamaahController@destroy');

    //Registrasi
    $router->get('registrasi','Dago\RegistrasiController@index');
    $router->post('registrasi','Dago\RegistrasiController@store');
    $router->get('registrasi-detail','Dago\RegistrasiController@edit');
    $router->put('registrasi','Dago\RegistrasiController@update');
    $router->delete('registrasi','Dago\RegistrasiController@destroy');
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

    //UploadDok
    $router->get('upload-dok','Dago\UploadDokController@index');
    $router->get('upload-dok-detail','Dago\UploadDokController@show');
    $router->post('upload-dok','Dago\UploadDokController@store');
});



?>