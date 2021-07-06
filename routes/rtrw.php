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
    
    $router->post('login', 'AuthController@loginRtrw');
    $router->get('hash_pass', 'AuthController@hashPasswordRtrw');
    $router->get('hash_pass_user/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
    $router->get('db', function () {
        
        $sql = DB::connection('sqlsrvrtrw')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });

    
    $router->post('login_warga', 'AuthController@loginWarga');
    $router->post('hash_pass_warga', 'AuthController@hashPassWarga');
    $router->post('hash_pass_perwarga', 'Rtrw\WargaController@hashPassPerWarga');
});

$router->group(['middleware' => 'auth:rtrw'], function () use ($router) {

    $router->get('profile', 'AdminRtrwController@profile');
    $router->get('users/{id}', 'AdminRtrwController@singleUser');
    $router->get('users', 'AdminRtrwController@allUsers');
    $router->get('cek_payload', 'AdminRtrwController@cekPayload');

    $router->get('menu', 'Rtrw\RtrwController@getMenu');
    $router->get('menu2', 'Rtrw\RtrwController@getMenu2');
    $router->get('menu-web/{kode_klp}', 'AdminRtrwController@getMenu');
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
    $router->post('warga-ubah-user','Rtrw\WargaController@updatePerUser');
    $router->delete('warga','Rtrw\WargaController@destroy'); 

    //Master Masakun
    $router->get('masakun','Rtrw\MasakunController@index');
    $router->get('masakun-detail','Rtrw\MasakunController@show');
    $router->post('masakun','Rtrw\MasakunController@store');
    $router->put('masakun','Rtrw\MasakunController@update');
    $router->delete('masakun','Rtrw\MasakunController@destroy');
    $router->get('masakun-curr','Rtrw\MasakunController@getCurrency');
    $router->get('masakun-modul','Rtrw\MasakunController@getModul');

    //Master Relakun
    $router->get('relakun-pp','Rtrw\RelakunPpController@index');
    $router->get('relakun-pp-detail','Rtrw\RelakunPpController@show');
    $router->post('relakun-pp','Rtrw\RelakunPpController@store');
    $router->put('relakun-pp','Rtrw\RelakunPpController@update');
    $router->delete('relakun-pp','Rtrw\RelakunPpController@destroy');

    //Master Ref Trans
    $router->get('reftrans-kode','Rtrw\ReferensiTransController@generateKodeByJenis');
    $router->get('reftrans','Rtrw\ReferensiTransController@index');
    $router->get('reftrans-detail','Rtrw\ReferensiTransController@show');
    $router->post('reftrans','Rtrw\ReferensiTransController@store');
    $router->put('reftrans','Rtrw\ReferensiTransController@update');
    $router->delete('reftrans','Rtrw\ReferensiTransController@destroy');

    //Generate Iuran
    $router->get('generate-iuran','Rtrw\GenerateIuranController@index');
    $router->post('generate-iuran','Rtrw\GenerateIuranController@store');
    $router->get('jenis-iuran','Rtrw\GenerateIuranController@getJenis');
    $router->get('pp-login','Rtrw\GenerateIuranController@getPPLogin');
    $router->get('generate-detail','Rtrw\GenerateIuranController@getDetail');
    
    //Setting saldo awal
    $router->get('setting-saldo-awal','Rtrw\SettingSaldoController@index');
    $router->get('setting-saldo-awal-detail','Rtrw\SettingSaldoController@show');
    $router->post('setting-saldo-awal','Rtrw\SettingSaldoController@store');
    $router->put('setting-saldo-awal','Rtrw\SettingSaldoController@update');
    $router->delete('setting-saldo-awal','Rtrw\SettingSaldoController@destroy');
    $router->get('setting-saldo-tahun','Rtrw\SettingSaldoController@getTahun');

    //Lokasi
    $router->get('lokasi','Rtrw\LokasiController@index');
    $router->get('lokasi-detail','Rtrw\LokasiController@show');
    $router->post('lokasi','Rtrw\LokasiController@store');
    $router->post('lokasi-ubah','Rtrw\LokasiController@update');
    $router->delete('lokasi','Rtrw\LokasiController@destroy');
    
    $router->post('upload-warga','Rtrw\WargaController@uploadWarga');
    
    $router->post('upload-bukti-bayar', 'Rtrw\RtrwController@uploadBuktiBayar');

    
    
    //Provinsi
    $router->get('provinsi','Rtrw\ProvinsiController@index');
    $router->post('provinsi','Rtrw\ProvinsiController@store');
    $router->put('provinsi','Rtrw\ProvinsiController@update');
    $router->delete('provinsi','Rtrw\ProvinsiController@destroy');

});

$router->group(['middleware' => 'auth:warga'], function () use ($router) {

    $router->get('profile_warga', 'AdminWargaController@profile');
    $router->get('users_warga/{id}', 'AdminWargaController@singleUser');
    $router->get('users_warga', 'AdminWargaController@allUsers');
    $router->get('cek_payload_warga', 'AdminWargaController@cekPayload');

    $router->post('ubah_profile', 'Rtrw\WargaController@updatePerUser');

    $router->get('filter_tahun_wr', 'Rtrw\RtrwController@getTahun');
    $router->get('filter_bulan_wr', 'Rtrw\RtrwController@getBulan');
    $router->get('filter_tahun_bill_wr', 'Rtrw\RtrwController@getTahunBill');
    $router->get('filter_periode_setor_wr', 'Rtrw\RtrwController@getPeriodeSetor');
    $router->get('filter_akun_wr', 'Rtrw\RtrwController@getAkun');
    $router->get('filter_blok_wr', 'Rtrw\RtrwController@getBlok');
    $router->get('filter_ref_akun_wr', 'Rtrw\RtrwController@getRefAkun');
    
    $router->get('rekap_rw_wr', 'Rtrw\RtrwController@getRekapRw');
    $router->get('rekap_rw_detail_wr', 'Rtrw\RtrwController@getDetailRekapRw');
    $router->get('rekap_rw_bulan_wr', 'Rtrw\RtrwController@getRekapBulananRw');
    $router->get('rekap_rw_bulan_detail_wr', 'Rtrw\RtrwController@getDetailRekapBulananRw');
    $router->get('riwayat_trans_wr', 'Rtrw\RtrwController@getRiwayatTrans');
    $router->get('riwayat_trans_detail_wr', 'Rtrw\RtrwController@getRiwayatTransDetail');
    $router->get('riwayat_iuran_wr', 'Rtrw\RtrwController@getRiwayatIuran');
    $router->get('iuran_detail_wr', 'Rtrw\RtrwController@getDetailIuran');
    $router->get('kartu_iuran_wr', 'Rtrw\RtrwController@getKartuIuran');

    $router->post('ubah_password_wr', 'Rtrw\RtrwController@ubahPassword');
    $router->post('simpan_kas_wr', 'Rtrw\RtrwController@simpanKas');

    $router->get('bayar_iuran_wr', 'Rtrw\RtrwController@getBayarIuran');
    $router->post('simpan_iuran_wr', 'Rtrw\RtrwController@simpanIuran');
    $router->get('bayar_iuran_rw_wr', 'Rtrw\RtrwController@getBayarIuranRw');
    $router->post('simpan_iuran_rw_wr', 'Rtrw\RtrwController@simpanIuranRw');
    $router->get('bayar_detail_wr', 'Rtrw\RtrwController@getDetailBayar');
    $router->get('bayar_detail_rw_wr', 'Rtrw\RtrwController@getDetailBayarRw');
    $router->get('setoran_wr', 'Rtrw\RtrwController@getSetoran');
    $router->post('simpan_setoran_wr', 'Rtrw\RtrwController@simpanSetoran');

    $router->get('rekap_setoran_wr', 'Rtrw\RtrwController@getRekapSetoran');
    $router->get('rekap_setoran_detail_wr', 'Rtrw\RtrwController@getDetailRekapSetoran');
    $router->get('notif_wr', 'Rtrw\NotifController@getInfo');
    $router->get('notif_wr_all', 'Rtrw\NotifController@getNotif');
    
});