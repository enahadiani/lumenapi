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


$router->group(['middleware' => 'cors'], function () use ($router) {
    //approval dev
    $router->post('login', 'AuthController@loginDago');
    $router->get('hash_pass', 'AuthController@hashPasswordDago');
    $router->get('storage/{filename}', function ($filename)
    {
        if (!Storage::disk('s3')->exists('dago/'.$filename)) {
            abort(404);
        }
        return Storage::disk('s3')->response('dago/'.$filename); 
    });
});

$router->group(['middleware' => 'auth:dago'], function () use ($router) {
    
    //Pekerjaan
    $router->get('pekerjaan','Dago\PekerjaanController@index');
    $router->post('pekerjaan','Dago\PekerjaanController@store');
    $router->put('pekerjaan','Dago\PekerjaanController@update');
    $router->delete('pekerjaan','Dago\PekerjaanController@destroy');

    //Dokumen
    $router->get('dokumen','Dago\DokumenController@index');
    $router->post('dokumen','Dago\DokumenController@store');
    $router->put('dokumen','Dago\DokumenController@update');
    $router->delete('dokumen','Dago\DokumenController@destroy');

    //Jenis Harga
    $router->get('jenis-harga','Dago\JenisHargaController@index');
    $router->post('jenis-harga','Dago\JenisHargaController@store');
    $router->put('jenis-harga','Dago\JenisHargaController@update');
    $router->delete('jenis-harga','Dago\JenisHargaController@destroy');

    //Type Room
    $router->get('type-room','Dago\TypeRoomController@index');
    $router->post('type-room','Dago\TypeRoomController@store');
    $router->put('type-room','Dago\TypeRoomController@update');
    $router->delete('type-room','Dago\TypeRoomController@destroy');

    //Biaya Wajib
    $router->get('biaya','Dago\BiayaController@index');
    $router->post('biaya','Dago\BiayaController@store');
    $router->put('biaya','Dago\BiayaController@update');
    $router->delete('biaya','Dago\BiayaController@destroy');
    $router->get('akun-pendapatan','Dago\BiayaController@getAkunPDPT');

    //Marketing
    $router->get('marketing','Dago\MarketingController@index');
    $router->post('marketing','Dago\MarketingController@store');
    $router->put('marketing','Dago\MarketingController@update');
    $router->delete('marketing','Dago\MarketingController@destroy');

    //Agen
    $router->get('agen','Dago\AgenController@index');
    $router->post('agen','Dago\AgenController@store');
    $router->put('agen','Dago\AgenController@update');
    $router->delete('agen','Dago\AgenController@destroy');

    //Jenis Produk
    $router->get('produk','Dago\ProdukController@index');
    $router->post('produk','Dago\ProdukController@store');
    $router->put('produk','Dago\ProdukController@update');
    $router->delete('produk','Dago\ProdukController@destroy');
    $router->get('akun-piutang','Dago\ProdukController@getAkunPiutang');
    $router->get('akun-pdd','Dago\ProdukController@getAkunPDD');

    //Paket
    $router->get('paket','Dago\PaketController@index');
    $router->post('paket','Dago\PaketController@store');
    $router->get('paket-detail','Dago\PaketController@edit');
    $router->put('paket','Dago\PaketController@update');
    $router->delete('paket','Dago\PaketController@destroy');

    //Jadwal
    $router->get('jadwal','Dago\PaketController@index');
    $router->post('ubah-jadwal','Dago\PaketController@store');


});



?>