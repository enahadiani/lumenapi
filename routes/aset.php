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
    $router->post('login', 'AuthController@loginAdmin');
    $router->get('hash_pass', 'AuthController@hashPasswordAdmin');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('local')->exists($filename)) {
        abort(404);
    }
    return Storage::disk('local')->response($filename); 
});

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('gedung','Aset\AsetController@getGedung');
    $router->get('ruangan','Aset\AsetController@getRuangan');
    $router->get('barang','Aset\AsetController@getBarang');
    $router->get('aju_daftar','Aset\AsetController@getDaftarPengajuan');
    $router->get('barang_detail','Aset\AsetController@getDetailBarang');
    $router->get('barang_daftar','Aset\AsetController@getDaftarBarang');
    $router->get('aset','Aset\AsetController@getDataAset');
    $router->get('perbaikan','Aset\AsetController@getPerbaikan');
    $router->get('perbaikan_detail','Aset\AsetController@getDetailPerbaikan');
    $router->get('inventaris_berjalan','Aset\AsetController@getInventarisBerjalan');
    $router->get('inventaris_lengkap','Aset\AsetController@getInventarisLengkap');
    $router->get('lokasi','Aset\AsetController@getLokasi');
    $router->get('aset_daftar','Aset\AsetController@getDaftarAset');
    $router->post('inventaris','Aset\AsetController@simpanInventaris');
    $router->post('ubah_gambar_aset','Aset\AsetController@ubahGambarAset');
});
