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

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('dago/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('dago/'.$filename); 
});

$router->group(['middleware' => 'auth:dago'], function () use ($router) {
    //Pekerjaan
    $router->get('pekerjaan','Dago\PekerjaanController@index');
    $router->post('pekerjaan','Dago\PekerjaanController@store');
    $router->put('pekerjaan','Dago\PekerjaanController@update');
    $router->delete('pekerjaan','Dago\PekerjaanController@destroy');

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
    $router->get('produk','Dago\JenisProdukController@index');
    $router->post('produk','Dago\JenisProdukController@store');
    $router->put('produk','Dago\JenisProdukController@update');
    $router->delete('produk','Dago\JenisProdukController@destroy');
    $router->get('akun-piutang','Dago\JenisProdukController@getAkunPiutang');
    $router->get('akun-pdd','Dago\JenisProdukController@getAkunPDD');

    //Jenis Produk
    $router->get('produk','Dago\JenisProdukController@index');
    $router->post('produk','Dago\JenisProdukController@store');
    $router->put('produk','Dago\JenisProdukController@update');
    $router->delete('produk','Dago\JenisProdukController@destroy');

    //Dokumen
    $router->get('masterdokumen','Dago\DokumenController@index');
    $router->post('masterdokumen','Dago\DokumenController@store');
    $router->put('masterdokumen','Dago\DokumenController@update');
    $router->delete('masterdokumen','Dago\DokumenController@destroy');

    //Paket
    $router->get('paket','Dago\PaketController@index');
    $router->post('paket','Dago\PaketController@store');
    $router->get('paket-detail','Dago\PaketController@edit');
    $router->put('paket','Dago\PaketController@update');
    $router->delete('paket','Dago\PaketController@destroy');

    //Jadwal
    $router->get('jadwal','Dago\JadwalController@index');
    $router->get('jadwal-detail','Dago\JadwalController@show');
    $router->put('jadwal','Dago\JadwalController@update');

    //Kurs
    $router->get('kurs','Dago\KursController@index');
    $router->post('kurs','Dago\KursController@store');
    $router->put('kurs','Dago\KursController@update');
    $router->delete('kurs','Dago\KursController@destroy');

    //ADMIN
    //Menu
    $router->get('menu','Dago\MenuController@index');
    $router->post('menu','Dago\MenuController@store');
    $router->put('menu','Dago\MenuController@update');
    $router->delete('menu','Dago\MenuController@destroy');
    $router->get('menu-klp','Dago\MenuController@getKlp');
    $router->post('menu-move','Dago\MenuController@simpanMove');

    //Akses User
    $router->get('akses-user','Dago\HakaksesController@index');
    $router->post('akses-user','Dago\HakaksesController@store');
    $router->get('akses-user-detail','Dago\HakaksesController@show');
    $router->put('akses-user','Dago\HakaksesController@update');
    $router->delete('akses-user','Dago\HakaksesController@destroy');
    $router->get('akses-user-menu','Dago\HakaksesController@getMenu');
    
    //Form
    $router->get('form','Dago\FormController@index');
    $router->post('form','Dago\FormController@store');
    $router->put('form','Dago\FormController@update');
    $router->delete('form','Dago\FormController@destroy');

    //Karyawan
    $router->get('karyawan','Dago\KaryawanController@index');
    $router->post('karyawan','Dago\KaryawanController@store');
    $router->get('karyawan-detail','Dago\KaryawanController@show');
    $router->post('karyawan-ubah','Dago\KaryawanController@update');
    $router->delete('karyawan','Dago\KaryawanController@destroy');

    //Kelompok Menu
    $router->get('menu-klp','Dago\KelompokMenuController@index');
    $router->post('menu-klp','Dago\KelompokMenuController@store');
    $router->put('menu-klp','Dago\KelompokMenuController@update');
    $router->delete('menu-klp','Dago\KelompokMenuController@destroy');

    //Unit
    $router->get('unit','Dago\UnitController@index');
    $router->post('unit','Dago\UnitController@store');
    $router->put('unit','Dago\UnitController@update');
    $router->delete('unit','Dago\UnitController@destroy');

});



?>