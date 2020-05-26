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
    $router->get('hash_pass_costum/{db}/{table}/{top}/{kode_pp}', 'AuthController@hashPasswordCostum');
    $router->get('hash_pass_nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
    
});

$router->group(['middleware' => 'auth:tarbak'], function () use ($router) {

    $router->get('profile', 'AdminTarbakController@profile');
    $router->get('users/{id}', 'AdminTarbakController@singleUser');
    $router->get('users', 'AdminTarbakController@allUsers');
    $router->get('cek_payload', 'AdminTarbakController@cekPayload');

    $router->get('menu/{kode_klp}', 'Sekolah\MenuController@show');

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

    //Mata Pelajaran
    $router->get('mata_pelajaran_all','Sekolah\MataPelajaranController@index');
    $router->get('mata_pelajaran','Sekolah\MataPelajaranController@show');
    $router->post('mata_pelajaran','Sekolah\MataPelajaranController@store');
    $router->put('mata_pelajaran','Sekolah\MataPelajaranController@update');
    $router->delete('mata_pelajaran','Sekolah\MataPelajaranController@destroy');

    //KKM
    $router->get('kkm_all','Sekolah\KkmController@index');
    $router->get('kkm','Sekolah\KkmController@show');
    $router->post('kkm','Sekolah\KkmController@store');
    $router->put('kkm','Sekolah\KkmController@update');
    $router->delete('kkm','Sekolah\KkmController@destroy');

    //Guru Matpel
    $router->get('guru_nik','Sekolah\GuruMatpelController@getNIKGuru');
    $router->get('guru_matpel_all','Sekolah\GuruMatpelController@index');
    $router->get('guru_matpel','Sekolah\GuruMatpelController@show');
    $router->post('guru_matpel','Sekolah\GuruMatpelController@store');
    $router->put('guru_matpel','Sekolah\GuruMatpelController@update');
    $router->delete('guru_matpel','Sekolah\GuruMatpelController@destroy');

    //Kalender Akademik
    $router->get('kalender_akad_all','Sekolah\KalenderAkadController@index');
    $router->get('kalender_akad','Sekolah\KalenderAkadController@show');
    $router->post('kalender_akad','Sekolah\KalenderAkadController@store');
    $router->put('kalender_akad','Sekolah\KalenderAkadController@update');
    $router->delete('kalender_akad','Sekolah\KalenderAkadController@destroy');

    //Jadwal Harian
    $router->get('jadwal_harian_all','Sekolah\JadwalHarianController@index');
    $router->get('jadwal_harian','Sekolah\JadwalHarianController@loadData');
    $router->post('jadwal_harian','Sekolah\JadwalHarianController@store');
    $router->delete('jadwal_harian','Sekolah\JadwalHarianController@destroy');

     //Jadwal Ujian
    $router->get('jadwal_ujian_all','Sekolah\JadwalUjianController@index');
    $router->get('jadwal_ujian','Sekolah\JadwalUjianController@show');
    $router->post('jadwal_ujian','Sekolah\JadwalUjianController@store');
    $router->put('jadwal_ujian','Sekolah\JadwalUjianController@update');
    $router->delete('jadwal_ujian','Sekolah\JadwalUjianController@destroy');
    
    //Hari
    $router->get('hari_all','Sekolah\HariController@index');
    $router->get('hari','Sekolah\HariController@show');
    $router->post('hari','Sekolah\HariController@store');
    $router->put('hari','Sekolah\HariController@update');
    $router->delete('hari','Sekolah\HariController@destroy');

    //Siswa
    $router->get('siswa_all','Sekolah\SiswaController@index');
    $router->get('siswa','Sekolah\SiswaController@show');
    $router->get('siswa_param','Sekolah\SiswaController@getParam');
    $router->get('siswa_jurusan','Sekolah\SiswaController@getJurusanTingkat');
    $router->post('siswa','Sekolah\SiswaController@store');
    $router->put('siswa','Sekolah\SiswaController@update');
    $router->delete('siswa','Sekolah\SiswaController@destroy');

    //Presensi
    $router->get('presensi_all','Sekolah\PresensiController@index');
    $router->get('presensi','Sekolah\PresensiController@show');
    $router->get('presensi_load','Sekolah\PresensiController@loadPresensi');
    $router->post('presensi','Sekolah\PresensiController@store');
    $router->put('presensi','Sekolah\PresensiController@update');
    $router->delete('presensi','Sekolah\PresensiController@destroy');

});