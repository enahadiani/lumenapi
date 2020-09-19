<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Storage; 
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
    if (!Storage::disk('s3')->exists('aset/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('aset/'.$filename); 
    // $url = 'https://'. env('AWS_BUCKET') .'.s3-'. env('AWS_DEFAULT_REGION') .'.amazonaws.com/images/';
    // return $url . $this->avatar;
});

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('profile', 'AdminController@profile');
    $router->get('users/{id}', 'AdminController@singleUser');
    $router->get('users', 'AdminController@allUsers');
    $router->get('cek-payload', 'AdminController@cekPayload');
    $router->post('profile-ubah', 'AdminController@updateProfile');
    $router->post('ubah-foto', 'AdminController@updatePhoto');
    
    $router->get('gedung','Aset\AsetController@getGedung');
    $router->get('ruangan','Aset\AsetController@getRuangan');
    $router->get('barang','Aset\AsetController@getBarang');
    $router->get('aju_daftar','Aset\AsetController@getDaftarPengajuan');
    $router->get('barang-detail','Aset\AsetController@getDetailBarang');
    $router->get('barang-daftar','Aset\AsetController@getDaftarBarang');
    $router->get('aset','Aset\AsetController@getDataAset');
    $router->get('perbaikan','Aset\AsetController@getPerbaikan');
    $router->get('perbaikan-detail','Aset\AsetController@getDetailPerbaikan');
    $router->get('inventaris-berjalan','Aset\AsetController@getInventarisBerjalan');
    $router->get('inventaris-lengkap','Aset\AsetController@getInventarisLengkap');
    $router->get('lokasi','Aset\AsetController@getLokasi');
    $router->get('aset-daftar','Aset\AsetController@getDaftarAset');
    $router->post('inventaris','Aset\AsetController@simpanInventaris');
    $router->post('ubah-gambar-aset','Aset\AsetController@ubahGambarAset');
    $router->post('upload-dok','Aset\AsetController@uploadDok');
    $router->delete('delete-dok/{no_bukti}/{no_urut}','Aset\AsetController@hapusDok');
    $router->delete('delete-dok-lahan/{no_bukti}/{no_urut}','Aset\AsetController@hapusDokLahan');
    $router->delete('delete-dok-gedung/{no_bukti}/{no_urut}','Aset\AsetController@hapusDokGedung');
    $router->post('upload-dok-single','Aset\AsetController@uploadDokSingle');
    $router->get('aset-detail-upload','Aset\AsetController@getDetailUpload');
    $router->post('upload-dok-lahan','Aset\AsetController@uploadDokLahan');
    $router->post('upload-dok-gedung','Aset\AsetController@uploadDokGedung');

    $router->get('user_device','UserDeviceController@index');
    $router->get('user_device/{nik}','UserDeviceController@show');
    $router->post('user_device','UserDeviceController@store');
    $router->put('user_device/{nik}','UserDeviceController@update');
    $router->delete('user_device/{nik}','UserDeviceController@destroy');
});
