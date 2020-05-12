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
    
    $router->post('login', 'AuthController@loginTarbak');
    $router->get('hash_pass', 'AuthController@hashPasswordTarbak');
});

$router->group(['middleware' => 'auth:tarbak'], function () use ($router) {

    $router->get('profile', 'AdminTarbakController@profile');
    $router->get('users/{id}', 'AdminTarbakController@singleUser');
    $router->get('users', 'AdminTarbakController@allUsers');
    $router->get('cek_payload', 'AdminTarbakController@cekPayload');

    //Tahun Ajaran
    $router->get('pp','Sekolah\TahunAjaranController@getPP');
    $router->get('tahun_ajaran_all','Sekolah\TahunAjaranController@index');
    $router->get('tahun_ajaran','Sekolah\TahunAjaranController@show');
    $router->post('tahun_ajaran','Sekolah\TahunAjaranController@store');
    $router->put('tahun_ajaran','Sekolah\TahunAjaranController@update');
    $router->delete('tahun_ajaran','Sekolah\TahunAjaranController@destroy');

    //Angkatan
    $router->get('tingkat','Sekolah\AngkatanController@getTingkat');
    $router->get('angkatan_all','Sekolah\AngkatanController@index');
    $router->get('angkatan','Sekolah\AngkatanController@show');
    $router->post('angkatan','Sekolah\AngkatanController@store');
    $router->put('angkatan','Sekolah\AngkatanController@update');
    $router->delete('angkatan','Sekolah\AngkatanController@destroy');

    //Jurusan
    $router->get('jurusan_all','Sekolah\JurusanController@index');
    $router->get('jurusan','Sekolah\JurusanController@show');
    $router->post('jurusan','Sekolah\JurusanController@store');
    $router->put('jurusan','Sekolah\JurusanController@update');
    $router->delete('jurusan','Sekolah\JurusanController@destroy');

    //Kelas
    $router->get('kelas_all','Sekolah\KelasController@index');
    $router->get('kelas','Sekolah\KelasController@show');
    $router->post('kelas','Sekolah\KelasController@store');
    $router->put('kelas','Sekolah\KelasController@update');
    $router->delete('kelas','Sekolah\KelasController@destroy');

    //Status Siswa
    $router->get('status_siswa_all','Sekolah\StatusSiswaController@index');
    $router->get('status_siswa','Sekolah\StatusSiswaController@show');
    $router->post('status_siswa','Sekolah\StatusSiswaController@store');
    $router->put('status_siswa','Sekolah\StatusSiswaController@update');
    $router->delete('status_siswa','Sekolah\StatusSiswaController@destroy');

    //Slot Jam Belajar
    $router->get('slot_all','Sekolah\SlotController@index');
    $router->get('slot','Sekolah\SlotController@show');
    $router->post('slot','Sekolah\SlotController@store');
    $router->put('slot','Sekolah\SlotController@update');
    $router->delete('slot','Sekolah\SlotController@destroy');

    //Slot Jenis Penilaian
    $router->get('jenis_nilai_all','Sekolah\JenisPenilaianController@index');
    $router->get('jenis_nilai','Sekolah\JenisPenilaianController@show');
    $router->post('jenis_nilai','Sekolah\JenisPenilaianController@store');
    $router->put('jenis_nilai','Sekolah\JenisPenilaianController@update');
    $router->delete('jenis_nilai','Sekolah\JenisPenilaianController@destroy');

    //Status Guru
    $router->get('status_guru_all','Sekolah\StatusGuruController@index');
    $router->get('status_guru','Sekolah\StatusGuruController@show');
    $router->post('status_guru','Sekolah\StatusGuruController@store');
    $router->put('status_guru','Sekolah\StatusGuruController@update');
    $router->delete('status_guru','Sekolah\StatusGuruController@destroy');





});