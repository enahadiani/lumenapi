<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);


$router->group(['middleware' => 'cors'], function () use ($router) {
    //approval dev
    $router->post('login', 'AuthController@loginAdminYpt');
    $router->get('hash_pass', 'AuthController@hashPasswordAdminYpt');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('local')->exists($filename)) {
        abort(404);
    }
    return Storage::disk('local')->response($filename); 
});

$router->group(['middleware' => 'auth:ypt'], function () use ($router) {
    $router->get('gedung','Proyek\TagihanController@getGedung');
    $router->get('ruangan','Proyek\TagihanController@getRuangan');
    $router->get('barang','Proyek\TagihanController@getBarang');
    $router->get('aju_daftar','Proyek\TagihanController@getDaftarPengajuan');
    $router->get('barang_detail','Proyek\TagihanController@getDetailBarang');
    $router->get('barang_daftar','Proyek\TagihanController@getDaftarBarang');
    $router->get('aset','Proyek\TagihanController@getDataAset');
    $router->get('perbaikan','Proyek\TagihanController@getPerbaikan');
    $router->get('perbaikan_detail','Proyek\TagihanController@getDetailPerbaikan');
    $router->get('inventaris_berjalan','Proyek\TagihanController@getInventarisBerjalan');
    $router->get('inventaris_lengkap','Proyek\TagihanController@getInventarisLengkap');
    $router->get('lokasi','Proyek\TagihanController@getLokasi');
    $router->get('aset_daftar','Proyek\TagihanController@getDaftarAset');
    $router->post('inventaris','Proyek\TagihanController@simpanInventaris');
    $router->post('ubah_gambar_aset','Proyek\TagihanController@ubahGambarAset');
});
