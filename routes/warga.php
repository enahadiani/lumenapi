<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
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

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('rtrw/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('rtrw/'.$filename); 
});

$router->group(['middleware' => 'cors'], function () use ($router) {
    
    $router->post('login', 'AuthController@loginWarga');
    $router->post('hash-pass', 'AuthController@hashPassWarga');
    $router->post('hash-pass_perwarga', 'Rtrw\WargaController@hashPassPerWarga');
});

$router->group(['middleware' => 'auth:warga'], function () use ($router) {

    $router->get('profile', 'AdminWargaController@profile');
    $router->get('users/{id}', 'AdminWargaController@singleUser');
    $router->get('users', 'AdminWargaController@allUsers');
    $router->get('cek-payload', 'AdminWargaController@cekPayload');

    $router->post('ubah-profile', 'Rtrw\WargaController@updatePerUser');

    $router->get('filter-tahun', 'Rtrw\RtrwController@getTahun');
    $router->get('filter-bulan', 'Rtrw\RtrwController@getBulan');
    $router->get('filter-tahun-bill', 'Rtrw\RtrwController@getTahunBill');
    $router->get('filter-bulan-bill', 'Rtrw\RtrwController@getPeriodeBill');
    $router->get('filter-periode-setor', 'Rtrw\RtrwController@getPeriodeSetor');
    $router->get('filter-akun', 'Rtrw\RtrwController@getAkun');
    $router->get('filter-blok', 'Rtrw\RtrwController@getBlok');
    $router->get('filter-ref-akun', 'Rtrw\RtrwController@getRefAkun');
    
    $router->get('rekap-rw', 'Rtrw\RtrwController@getRekapRw');
    $router->get('rekap-rw-detail', 'Rtrw\RtrwController@getDetailRekapRw');
    $router->get('rekap-rw-bulan', 'Rtrw\RtrwController@getRekapBulananRw');
    $router->get('rekap-rw-bulan-detail', 'Rtrw\RtrwController@getDetailRekapBulananRw');
    $router->get('riwayat-trans', 'Rtrw\RtrwController@getRiwayatTrans');
    $router->get('riwayat-trans-detail', 'Rtrw\RtrwController@getRiwayatTransDetail');
    $router->get('riwayat-iuran', 'Rtrw\RtrwController@getRiwayatIuran');
    $router->get('iuran-detail', 'Rtrw\RtrwController@getDetailIuran');
    $router->get('kartu-iuran', 'Rtrw\RtrwController@getKartuIuran');

    $router->post('ubah-password', 'Rtrw\RtrwController@ubahPassword');
    $router->post('simpan-kas', 'Rtrw\RtrwController@simpanKas');

    $router->get('bayar-iuran', 'Rtrw\RtrwController@getBayarIuran');
    $router->post('simpan-iuran', 'Rtrw\RtrwController@simpanIuran');
    $router->get('bayar-iuran-rw', 'Rtrw\RtrwController@getBayarIuranRw');
    $router->post('simpan-iuran-rw', 'Rtrw\RtrwController@simpanIuranRw');
    $router->get('bayar-detail', 'Rtrw\RtrwController@getDetailBayar');
    $router->get('bayar-detail-rw', 'Rtrw\RtrwController@getDetailBayarRw');
    $router->get('setoran', 'Rtrw\RtrwController@getSetoran');
    $router->post('simpan-setoran', 'Rtrw\RtrwController@simpanSetoran');

    $router->get('rekap-setoran', 'Rtrw\RtrwController@getRekapSetoran');
    $router->get('rekap-setoran-detail', 'Rtrw\RtrwController@getDetailRekapSetoran');
    $router->get('info', 'Rtrw\NotifController@getInfo');
    $router->get('notif-all', 'Rtrw\NotifController@getNotif');
  
});