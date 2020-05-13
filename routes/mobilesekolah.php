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

$router->group(['middleware' => 'cors'], function () use ($router) {
    $router->post('login_guru', 'AuthController@loginTarbak');
    $router->get('hash_pass_guru', 'AuthController@hashPasswordTarbak');

    $router->post('login_siswa', 'AuthController@loginSiswa');
    $router->get('hash_pass_siswa', 'AuthController@hashPasswordSiswa');
});

$router->group(['middleware' => 'auth:tarbak'], function () use ($router) {

    $router->get('profile_guru', 'AdminTarbakController@profileGuru');
    $router->get('users_guru/{id}', 'AdminTarbakController@singleUser');
    $router->get('users_guru', 'AdminTarbakController@allUsers');
    $router->get('cek_payload_guru', 'AdminTarbakController@cekPayload');

    $router->get('jadwal_sekarang', 'Sekolah\MobileController@getJadwalSekarang');
    $router->get('absen_total', 'Sekolah\MobileController@getAbsenTotal');
    $router->get('absen_edit', 'Sekolah\MobileController@getEditAbsen');
    $router->get('siswa_list', 'Sekolah\MobileController@getDaftarSiswa');
    $router->post('absen', 'Sekolah\MobileController@insertAbsen');
    $router->get('jadwal_guru', 'Sekolah\MobileController@getJadwalGuru');
});

$router->group(['middleware' => 'auth:siswa'], function () use ($router) {

    $router->get('profile_siswa', 'AdminSiswaController@profile');
    $router->get('users_siswa/{id}', 'AdminSiswaController@singleUser');
    $router->get('users_siswa', 'AdminSiswaController@allUsers');
    $router->get('cek_payload_siswa', 'AdminSiswaController@cekPayload');

    $router->get('absen', 'Sekolah\MobileController@getAbsen');
    $router->get('jadwal_siswa', 'Sekolah\MobileController@getJadwalSiswa');
    $router->get('kalender', 'Sekolah\MobileController@getKalender');
    $router->get('eskul', 'Sekolah\MobileController@getEskul');
    $router->get('kartu_piutang', 'Sekolah\MobileController@getPiutang');
    $router->get('kartu_pdd', 'Sekolah\MobileController@getPDD');
    $router->get('saldo_piutang', 'Sekolah\MobileController@getSaldoPiutang');
    $router->get('saldo_pdd', 'Sekolah\MobileController@getSaldoPDD');
    $router->get('riwayat', 'Sekolah\MobileController@getRiwayat');
    $router->get('piutang_detail', 'Sekolah\MobileController@getDetailPiu');
    $router->get('nilai', 'Sekolah\MobileController@getNilai');
    $router->get('prestasi', 'Sekolah\MobileController@getPrestasi');
    $router->get('raport', 'Sekolah\MobileController@getRaport');


});