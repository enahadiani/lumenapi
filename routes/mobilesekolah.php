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

    $router->get('jadwal_sekarang', 'MobileController@getJadwalSekarang');
    $router->get('absen_total', 'MobileController@getAbsenTotal');
    $router->get('absen_edit', 'MobileController@getEditAbsen');
    $router->get('siswa_list', 'MobileController@getDaftarSiswa');
    $router->post('absen', 'MobileController@insertAbsen');
    $router->get('jadwal_guru', 'MobileController@getJadwalGuru');
});

$router->group(['middleware' => 'auth:siswa'], function () use ($router) {

    $router->get('profile_siswa', 'AdminSiswaController@profile');
    $router->get('users_siswa/{id}', 'AdminSiswaController@singleUser');
    $router->get('users_siswa', 'AdminSiswaController@allUsers');
    $router->get('cek_payload_siswa', 'AdminSiswaController@cekPayload');

    $router->get('absen', 'MobileController@getAbsen');
    $router->get('jadwal_siswa', 'MobileController@getJadwalSiswa');
    $router->get('kalender', 'MobileController@getKalender');
    $router->get('eskul', 'MobileController@getEskul');
    $router->get('kartu_piutang', 'MobileController@getPiutang');
    $router->get('kartu_pdd', 'MobileController@getPDD');
    $router->get('saldo_piutang', 'MobileController@getSaldoPiutang');
    $router->get('saldo_pdd', 'MobileController@getSaldoPDD');
    $router->get('riwayat', 'MobileController@getRiwayat');
    $router->get('piutang_detail', 'MobileController@getDetailPiu');
    $router->get('nilai', 'MobileController@getNilai');
    $router->get('prestasi', 'MobileController@getPrestasi');
    $router->get('raport', 'MobileController@getRaport');


});