<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('sekolah/'.$filename)) {
        $success['message'] = 'Dokumen tidak tersedia!';
        $success['status'] = false;
    }
    return Storage::disk('s3')->response('sekolah/'.$filename); 
});

$router->group(['middleware' => 'cors'], function () use ($router) {
    $router->post('login-guru', 'AuthController@loginTarbak');
    $router->get('hash-pass-guru', 'AuthController@hashPasswordTarbak');

    $router->post('login-siswa', 'AuthController@loginSiswa');
    $router->get('hash-pass-siswa', 'AuthController@hashPasswordSiswa');
    $router->get('daftar-pp', 'AdminTarbakController@getDaftarPP');
    $router->get('hash-pass-bynik/{db}/{table}/{nik}','AuthController@hashPasswordByNIK');
    $router->get('hash-pass-costum/{db}/{table}/{top}/{kode_pp}','AuthController@hashPasswordCostum');
    
});

$router->group(['middleware' => 'auth:tarbak'], function () use ($router) {

    $router->get('profile-guru', 'AdminTarbakController@profileGuru');
    $router->get('users-guru/{id}', 'AdminTarbakController@singleUser');
    $router->get('users-guru', 'AdminTarbakController@allUsers');
    $router->get('cek-payload-guru', 'AdminTarbakController@cekPayload');

    $router->get('jadwal-sekarang', 'Sekolah\MobileController@getJadwalSekarang');
    $router->get('absen-total', 'Sekolah\MobileController@getAbsenTotal');
    $router->get('absen-edit', 'Sekolah\MobileController@getEditAbsen');
    $router->get('siswa-list', 'Sekolah\MobileController@getDaftarSiswa');
    $router->post('absen', 'Sekolah\MobileController@insertAbsen');
    $router->get('jadwal-guru', 'Sekolah\MobileController@getJadwalGuru');
});

$router->group(['middleware' => 'auth:siswa'], function () use ($router) {

    $router->get('profile-siswa', 'AdminSiswaController@profile');
    $router->get('users-siswa/{id}', 'AdminSiswaController@singleUser');
    $router->get('users-siswa', 'AdminSiswaController@allUsers');
    $router->get('cek-payload-siswa', 'AdminSiswaController@cekPayload');

    $router->get('absen', 'Sekolah\MobileController@getAbsen');
    $router->get('jadwal-siswa', 'Sekolah\MobileController@getJadwalSiswa');
    $router->get('kalender', 'Sekolah\MobileController@getKalender');
    $router->get('eskul', 'Sekolah\MobileController@getEskul');
    $router->get('kartu-piutang', 'Sekolah\MobileController@getPiutang');
    $router->get('kartu-pdd', 'Sekolah\MobileController@getPDD');
    $router->get('saldo-piutang', 'Sekolah\MobileController@getSaldoPiutang');
    $router->get('saldo-pdd', 'Sekolah\MobileController@getSaldoPDD');
    $router->get('riwayat', 'Sekolah\MobileController@getRiwayat');
    $router->get('piutang-detail', 'Sekolah\MobileController@getDetailPiu');
    $router->get('nilai', 'Sekolah\MobileController@getNilai');
    $router->get('prestasi', 'Sekolah\MobileController@getPrestasi');
    $router->get('raport', 'Sekolah\MobileController@getRaport');
    $router->get('mata-pelajaran', 'Sekolah\MobileController@getMatpel');
    $router->get('mata-pelajaran-detail', 'Sekolah\MobileController@getDetMatpel');

    $router->post('update-password', 'AdminSiswaController@updatePassword');
    $router->post('update-foto', 'AdminSiswaController@updatePhoto');


});