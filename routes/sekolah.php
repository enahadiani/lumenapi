<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);

$router->group(['middleware' => 'cors'], function () use ($router) {
    $router->post('login', 'AuthController@loginTarbak');
    $router->get('hash-pass', 'AuthController@hashPasswordTarbak');
    $router->get('hash-pass-costum/{db}/{table}/{top}/{kode_pp}', 'AuthController@hashPasswordCostum');
    $router->get('hash-pass-nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
    
});


$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('sekolah/'.$filename)) {
        $success['message'] = 'Dokumen tidak tersedia!';
        $success['status'] = false;
    }
    return Storage::disk('s3')->response('sekolah/'.$filename); 
});

$router->get('penilaian-export','Sekolah\PenilaianController@export');

$router->group(['middleware' => 'auth:tarbak'], function () use ($router) {

    $router->get('profile', 'AdminTarbakController@profile');
    $router->get('users/{id}', 'AdminTarbakController@singleUser');
    $router->get('users', 'AdminTarbakController@allUsers');
    $router->get('cek-payload', 'AdminTarbakController@cekPayload');

    $router->get('menu/{kode_klp}', 'Sekolah\MenuController@show');
    
    $router->post('update-password', 'AdminTarbakController@updatePassword');
    $router->post('update-foto', 'AdminTarbakController@updatePhoto');
    $router->post('update-background', 'AdminTarbakController@updateBackground');
    
    $router->post('notif-pusher', 'Sekolah\NotifController@sendPusher');
    $router->get('notif-pusher', 'Sekolah\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Sekolah\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminTarbakController@searchForm');
    $router->get('search-form-list', 'AdminTarbakController@searchFormList');

    //Tahun Ajaran
    $router->get('pp','Sekolah\TahunAjaranController@getPP');
    $router->get('tahun-ajaran-all','Sekolah\TahunAjaranController@index');
    $router->get('tahun-ajaran','Sekolah\TahunAjaranController@show');
    $router->post('tahun-ajaran','Sekolah\TahunAjaranController@store');
    $router->put('tahun-ajaran','Sekolah\TahunAjaranController@update');
    $router->delete('tahun-ajaran','Sekolah\TahunAjaranController@destroy');

    //Angkatan
    $router->get('tingkat','Sekolah\AngkatanController@getTingkat');
    $router->get('angkatan-all','Sekolah\AngkatanController@index');
    $router->get('angkatan','Sekolah\AngkatanController@show');
    $router->post('angkatan','Sekolah\AngkatanController@store');
    $router->put('angkatan','Sekolah\AngkatanController@update');
    $router->delete('angkatan','Sekolah\AngkatanController@destroy');

    //Jurusan
    $router->get('jurusan-all','Sekolah\JurusanController@index');
    $router->get('jurusan','Sekolah\JurusanController@show');
    $router->post('jurusan','Sekolah\JurusanController@store');
    $router->put('jurusan','Sekolah\JurusanController@update');
    $router->delete('jurusan','Sekolah\JurusanController@destroy');

    //Kelas
    $router->get('kelas-all','Sekolah\KelasController@index');
    $router->get('kelas','Sekolah\KelasController@show');
    $router->post('kelas','Sekolah\KelasController@store');
    $router->put('kelas','Sekolah\KelasController@update');
    $router->delete('kelas','Sekolah\KelasController@destroy');

    //Status Siswa
    $router->get('status-siswa-all','Sekolah\StatusSiswaController@index');
    $router->get('status-siswa','Sekolah\StatusSiswaController@show');
    $router->post('status-siswa','Sekolah\StatusSiswaController@store');
    $router->put('status-siswa','Sekolah\StatusSiswaController@update');
    $router->delete('status-siswa','Sekolah\StatusSiswaController@destroy');

    //Slot Jam Belajar
    $router->get('slot-all','Sekolah\SlotController@index');
    $router->get('slot','Sekolah\SlotController@show');
    $router->post('slot','Sekolah\SlotController@store');
    $router->put('slot','Sekolah\SlotController@update');
    $router->delete('slot','Sekolah\SlotController@destroy');

    //Slot Jenis Penilaian
    $router->get('jenis-nilai-all','Sekolah\JenisPenilaianController@index');
    $router->get('jenis-nilai','Sekolah\JenisPenilaianController@show');
    $router->post('jenis-nilai','Sekolah\JenisPenilaianController@store');
    $router->put('jenis-nilai','Sekolah\JenisPenilaianController@update');
    $router->delete('jenis-nilai','Sekolah\JenisPenilaianController@destroy');

    //Status Guru
    $router->get('status-guru-all','Sekolah\StatusGuruController@index');
    $router->get('status-guru','Sekolah\StatusGuruController@show');
    $router->post('status-guru','Sekolah\StatusGuruController@store');
    $router->put('status-guru','Sekolah\StatusGuruController@update');
    $router->delete('status-guru','Sekolah\StatusGuruController@destroy');

    //Mata Pelajaran
    $router->get('mata-pelajaran-all','Sekolah\MataPelajaranController@index');
    $router->get('mata-pelajaran','Sekolah\MataPelajaranController@show');
    $router->post('mata-pelajaran','Sekolah\MataPelajaranController@store');
    $router->put('mata-pelajaran','Sekolah\MataPelajaranController@update');
    $router->delete('mata-pelajaran','Sekolah\MataPelajaranController@destroy');

    //KKM
    $router->get('kkm-all','Sekolah\KkmController@index');
    $router->get('kkm','Sekolah\KkmController@show');
    $router->post('kkm','Sekolah\KkmController@store');
    $router->put('kkm','Sekolah\KkmController@update');
    $router->delete('kkm','Sekolah\KkmController@destroy');

    //Guru Matpel
    $router->get('guru-nik','Sekolah\GuruMatpelController@getNIKGuru');
    $router->get('guru-matpel-all','Sekolah\GuruMatpelController@index');
    $router->get('guru-matpel','Sekolah\GuruMatpelController@show');
    $router->post('guru-matpel','Sekolah\GuruMatpelController@store');
    $router->put('guru-matpel','Sekolah\GuruMatpelController@update');
    $router->delete('guru-matpel','Sekolah\GuruMatpelController@destroy');

    //Kalender Akademik
    $router->get('kalender-akad-all','Sekolah\KalenderAkadController@index');
    $router->get('kalender-akad','Sekolah\KalenderAkadController@show');
    $router->post('kalender-akad','Sekolah\KalenderAkadController@store');
    $router->put('kalender-akad','Sekolah\KalenderAkadController@update');
    $router->delete('kalender-akad','Sekolah\KalenderAkadController@destroy');

    //Jadwal Harian
    $router->get('jadwal-harian-all','Sekolah\JadwalHarianController@index');
    $router->get('jadwal-harian','Sekolah\JadwalHarianController@loadJadwal');
    $router->post('jadwal-harian','Sekolah\JadwalHarianController@store');
    $router->delete('jadwal-harian','Sekolah\JadwalHarianController@destroy');
    $router->get('jadwal-load','Sekolah\JadwalHarianController@loadJadwal');

     //Jadwal Ujian
    $router->get('jadwal-ujian-all','Sekolah\JadwalUjianController@index');
    $router->get('jadwal-ujian','Sekolah\JadwalUjianController@show');
    $router->post('jadwal-ujian','Sekolah\JadwalUjianController@store');
    $router->put('jadwal-ujian','Sekolah\JadwalUjianController@update');
    $router->delete('jadwal-ujian','Sekolah\JadwalUjianController@destroy');
    
    //Hari
    $router->get('hari-all','Sekolah\HariController@index');
    $router->get('hari','Sekolah\HariController@show');
    $router->post('hari','Sekolah\HariController@store');
    $router->put('hari','Sekolah\HariController@update');
    $router->delete('hari','Sekolah\HariController@destroy');

    //Siswa
    $router->get('siswa-all','Sekolah\SiswaController@index');
    $router->get('siswa','Sekolah\SiswaController@show');
    $router->get('siswa-param','Sekolah\SiswaController@getParam');
    $router->get('siswa-periode','Sekolah\SiswaController@getPeriodeParam');
    $router->post('siswa','Sekolah\SiswaController@store');
    $router->put('siswa','Sekolah\SiswaController@update');
    $router->delete('siswa','Sekolah\SiswaController@destroy');

    
    //KD
    $router->get('kd-all','Sekolah\KdController@index');
    $router->get('kd','Sekolah\KdController@show');
    $router->post('kd','Sekolah\KdController@store');
    $router->put('kd','Sekolah\KdController@update');
    $router->delete('kd','Sekolah\KdController@destroy');

    //Presensi
    $router->get('presensi-all','Sekolah\PresensiController@index');
    $router->get('presensi','Sekolah\PresensiController@show');
    $router->get('presensi-load','Sekolah\PresensiController@loadPresensi');
    $router->post('presensi','Sekolah\PresensiController@store');
    $router->put('presensi','Sekolah\PresensiController@update');
    $router->delete('presensi','Sekolah\PresensiController@destroy');

    //Penilaian
    $router->get('penilaian-all','Sekolah\PenilaianController@index');
    $router->get('penilaian','Sekolah\PenilaianController@show');
    $router->get('penilaian-load','Sekolah\PenilaianController@loadSiswa');
    $router->post('penilaian','Sekolah\PenilaianController@store');
    $router->put('penilaian','Sekolah\PenilaianController@update');
    $router->delete('penilaian','Sekolah\PenilaianController@destroy');
    $router->get('penilaian-ke','Sekolah\PenilaianController@getPenilaianKe');
    
    $router->post('import-excel','Sekolah\PenilaianController@importExcel');
    $router->get('nilai-tmp','Sekolah\PenilaianController@getNilaiTmp');
    $router->get('penilaian-dok','Sekolah\PenilaianController@showDokUpload');
    $router->post('penilaian-dok','Sekolah\PenilaianController@storeDokumen');
    $router->delete('penilaian-dok','Sekolah\PenilaianController@deleteDokumen');
    
});