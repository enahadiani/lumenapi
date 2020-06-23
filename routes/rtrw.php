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
    
    $router->post('login', 'AuthController@loginRtrw');
    $router->get('hash_pass', 'AuthController@hashPasswordRtrw');
    $router->get('hash_pass_user/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
    $router->get('db', function () {
        
        $sql = DB::connection('sqlsrvrtrw')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
});

$router->group(['middleware' => 'auth:rtrw'], function () use ($router) {

    $router->get('profile', 'AdminRtrwController@profile');
    $router->get('users/{id}', 'AdminRtrwController@singleUser');
    $router->get('users', 'AdminRtrwController@allUsers');
    $router->get('cek_payload', 'AdminRtrwController@cekPayload');

    $router->get('menu', 'Rtrw\RtrwController@getMenu');
    $router->get('menu2', 'Rtrw\RtrwController@getMenu2');
    $router->get('filter_tahun', 'Rtrw\RtrwController@getTahun');
    $router->get('filter_bulan', 'Rtrw\RtrwController@getBulan');
    $router->get('filter_tahun_bill', 'Rtrw\RtrwController@getTahunBill');
    $router->get('filter_periode_setor', 'Rtrw\RtrwController@getPeriodeSetor');
    $router->get('filter_akun', 'Rtrw\RtrwController@getAkun');
    $router->get('filter_blok', 'Rtrw\RtrwController@getBlok');
    $router->get('filter_ref_akun', 'Rtrw\RtrwController@getRefAkun');
    
    $router->get('rekap_rw', 'Rtrw\RtrwController@getRekapRw');
    $router->get('rekap_rw_detail', 'Rtrw\RtrwController@getDetailRekapRw');
    $router->get('rekap_rw_bulan', 'Rtrw\RtrwController@getRekapBulananRw');
    $router->get('rekap_rw_bulan_detail', 'Rtrw\RtrwController@getDetailRekapBulananRw');
    $router->get('riwayat_trans', 'Rtrw\RtrwController@getRiwayatTrans');
    $router->get('riwayat_trans_detail', 'Rtrw\RtrwController@getRiwayatTransDetail');
    $router->get('riwayat_iuran', 'Rtrw\RtrwController@getRiwayatIuran');
    $router->get('iuran_detail', 'Rtrw\RtrwController@getDetailIuran');
    $router->get('kartu_iuran', 'Rtrw\RtrwController@getKartuIuran');

    $router->post('ubah_password', 'Rtrw\RtrwController@ubahPassword');
    $router->post('simpan_kas', 'Rtrw\RtrwController@simpanKas');

    $router->get('bayar_iuran', 'Rtrw\RtrwController@getBayarIuran');
    $router->post('simpan_iuran', 'Rtrw\RtrwController@simpanIuran');
    $router->get('bayar_iuran_rw', 'Rtrw\RtrwController@getBayarIuranRw');
    $router->post('simpan_iuran_rw', 'Rtrw\RtrwController@simpanIuranRw');
    $router->get('bayar_detail', 'Rtrw\RtrwController@getDetailBayar');
    $router->get('bayar_detail_rw', 'Rtrw\RtrwController@getDetailBayarRw');
    $router->get('setoran', 'Rtrw\RtrwController@getSetoran');
    $router->post('simpan_setoran', 'Rtrw\RtrwController@simpanSetoran');

    $router->get('rekap_setoran', 'Rtrw\RtrwController@getRekapSetoran');
    $router->get('rekap_setoran_detail', 'Rtrw\RtrwController@getDetailRekapSetoran');

    //Master Satpam
    $router->get('satpam','Rtrw\SatpamController@index');
    $router->post('satpam','Rtrw\SatpamController@store');
    $router->post('satpam-ubah','Rtrw\SatpamController@update');
    $router->delete('satpam','Rtrw\SatpamController@destroy');
    $router->post('satpam-generate-qrcode','Rtrw\SatpamController@generateQrCode');

    //Master Blok
    $router->get('blok','Rtrw\BlokController@index');
    $router->post('blok','Rtrw\BlokController@store');
    $router->put('blok','Rtrw\BlokController@update');
    $router->delete('blok','Rtrw\BlokController@destroy');

    //Master PP
    $router->get('pp','Rtrw\PpController@index');
    $router->post('pp','Rtrw\PpController@store');
    $router->put('pp','Rtrw\PpController@update');
    $router->delete('pp','Rtrw\PpController@destroy');

    //Master Perlu
    $router->get('perlu','Rtrw\KeperluanController@index');
    $router->post('perlu','Rtrw\KeperluanController@store');
    $router->put('perlu','Rtrw\KeperluanController@update');
    $router->delete('perlu','Rtrw\KeperluanController@destroy');

    //Master Rumah
    $router->get('rumah','Rtrw\RumahController@index');
    $router->post('rumah','Rtrw\RumahController@store');
    $router->put('rumah','Rtrw\RumahController@update');
    $router->delete('rumah','Rtrw\RumahController@destroy');

    //Master Warga
    $router->get('warga-list','Rtrw\WargaController@index');
    $router->get('warga-detail','Rtrw\WargaController@getDetailWarga');
    $router->post('warga','Rtrw\WargaController@store');
    $router->post('warga-ubah','Rtrw\WargaController@update');
    $router->delete('warga','Rtrw\WargaController@destroy'); 

    // Midtrans Test
});

// $router->get('sai-midtrans','Midtrans\MidtransController@getSnapToken');