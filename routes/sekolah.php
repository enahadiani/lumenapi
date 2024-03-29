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
    $router->post('login', 'AuthController@loginSiswa2');
    $router->get('hash-pass', 'AuthController@hashPasswordSiswa');
    $router->get('hash-pass-costum/{db}/{table}/{top}/{kode_pp}', 'AuthController@hashPasswordCostum');
    $router->get('hash-pass-nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
    $router->get('hash-pass-costum2/{db}/{table}/{top}/{kode_pp}', 'AuthController@hashPasswordCostum2');
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
$router->get('kd-export','Sekolah\KdController@export');
$router->get('penilaian-multiph-export','Sekolah\PenilaianMultiPHController@export');

$router->group(['middleware' => 'auth:siswa'], function () use ($router) {

    $router->get('profile', 'AdminSiswaController@profile');
    $router->get('users/{id}', 'AdminSiswaController@singleUser');
    $router->get('users', 'AdminSiswaController@allUsers');
    $router->get('cek-payload', 'AdminSiswaController@cekPayload');

    $router->get('menu/{kode_klp}', 'Sekolah\MenuController@show');
    
    $router->post('update-password', 'AdminSiswaController@updatePassword');
    $router->post('update-foto', 'AdminSiswaController@updatePhoto');
    $router->post('update-background', 'AdminSiswaController@updateBackground');
    
    $router->post('notif-pusher', 'Sekolah\NotifController@sendPusher');
    $router->get('notif-pusher', 'Sekolah\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Sekolah\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminSiswaController@searchForm');
    $router->get('search-form-list', 'AdminSiswaController@searchFormList');

    //ADMIN SETTING
    //Menu
    $router->get('menu','Sekolah\MenuController@index');
    $router->post('menu','Sekolah\MenuController@store');
    $router->put('menu','Sekolah\MenuController@update');
    $router->delete('menu','Sekolah\MenuController@destroy');
    $router->get('menu-klp','Sekolah\MenuController@getKlp');
    $router->post('menu-move','Sekolah\MenuController@simpanMove');

    //Akses User
    $router->get('akses-user','Sekolah\HakaksesController@index');
    $router->post('akses-user','Sekolah\HakaksesController@store');
    $router->get('akses-user-detail','Sekolah\HakaksesController@show');
    $router->put('akses-user','Sekolah\HakaksesController@update');
    $router->delete('akses-user','Sekolah\HakaksesController@destroy');
    $router->get('akses-user-menu','Sekolah\HakaksesController@getMenu');
    
    //Form
    $router->get('form','Sekolah\FormController@index');
    $router->post('form','Sekolah\FormController@store');
    $router->put('form','Sekolah\FormController@update');
    $router->delete('form','Sekolah\FormController@destroy');

    //Karyawan
    $router->get('karyawan','Sekolah\KaryawanController@index');
    $router->post('karyawan','Sekolah\KaryawanController@store');
    $router->get('karyawan-detail','Sekolah\KaryawanController@show');
    $router->post('karyawan-ubah','Sekolah\KaryawanController@update');
    $router->delete('karyawan','Sekolah\KaryawanController@destroy');

    //Kelompok Menu
    $router->get('menu-klp','Sekolah\KelompokMenuController@index');
    $router->post('menu-klp','Sekolah\KelompokMenuController@store');
    $router->put('menu-klp','Sekolah\KelompokMenuController@update');
    $router->delete('menu-klp','Sekolah\KelompokMenuController@destroy');

    //Unit
    $router->get('unit','Sekolah\UnitController@index');
    $router->post('unit','Sekolah\UnitController@store');
    $router->put('unit','Sekolah\UnitController@update');
    $router->delete('unit','Sekolah\UnitController@destroy');
    
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

    // Kelas Khusus
    $router->get('kelas-khusus-all','Sekolah\KelasKhususController@index');
    $router->get('kelas-khusus','Sekolah\KelasKhususController@show');
    $router->post('kelas-khusus','Sekolah\KelasKhususController@store');
    $router->put('kelas-khusus','Sekolah\KelasKhususController@update');
    $router->delete('kelas-khusus','Sekolah\KelasKhususController@destroy');

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

    //Guru Matpel
    $router->get('guru-multi-kelas-all','Sekolah\GuruMultiKelasController@index');
    $router->get('multi-kelas','Sekolah\GuruMultiKelasController@getMultiKelas');
    $router->get('guru-multi-kelas','Sekolah\GuruMultiKelasController@show');
    $router->post('guru-multi-kelas','Sekolah\GuruMultiKelasController@store');
    $router->put('guru-multi-kelas','Sekolah\GuruMultiKelasController@update');
    $router->delete('guru-multi-kelas','Sekolah\GuruMultiKelasController@destroy');

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
    $router->post('import-kd','Sekolah\KdController@importExcel');
    $router->get('kd-tmp','Sekolah\KdController@getKdTmp');

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
    $router->get('penilaian-kd','Sekolah\PenilaianController@getKD');
    
    
    $router->get('penilaian-dok-all','Sekolah\PenilaianController@listUpload');
    $router->post('import-excel','Sekolah\PenilaianController@importExcel');
    $router->get('nilai-tmp','Sekolah\PenilaianController@getNilaiTmp');
    $router->get('penilaian-dok','Sekolah\PenilaianController@showDokUpload');
    $router->post('penilaian-dok','Sekolah\PenilaianController@storeDokumen');
    $router->delete('penilaian-dok','Sekolah\PenilaianController@deleteDokumen');
    $router->post('penilaian-dok-upload','Sekolah\PenilaianController@uploadDokSatuan');
    $router->get('penilaian-matpel','Sekolah\PenilaianController@getMatpel');
    $router->get('penilaian-kelas','Sekolah\PenilaianController@getKelas');
    $router->get('penilaian-siswa','Sekolah\PenilaianController@getSiswa');

    // PENILAIAN PH
    $router->get('penilaian-multiph-all','Sekolah\PenilaianMultiPHController@index');
    $router->get('penilaian-multiph','Sekolah\PenilaianMultiPHController@show');
    $router->get('penilaian-multiph-load','Sekolah\PenilaianMultiPHController@loadSiswa');
    $router->post('penilaian-multiph','Sekolah\PenilaianMultiPHController@store');
    $router->put('penilaian-multiph','Sekolah\PenilaianMultiPHController@update');
    $router->delete('penilaian-multiph','Sekolah\PenilaianMultiPHController@destroy');
    $router->get('penilaian-multiph-ke','Sekolah\PenilaianMultiPHController@getPenilaianKe');
    $router->get('penilaian-multiph-kd','Sekolah\PenilaianMultiPHController@getKD');
    
    $router->get('penilaian-multiph-dok-all','Sekolah\PenilaianMultiPHController@listUpload');
    $router->post('import-multiph-excel','Sekolah\PenilaianMultiPHController@importExcel');
    $router->get('nilai-multiph-tmp','Sekolah\PenilaianMultiPHController@getNilaiTmp');
    $router->get('penilaian-multiph-dok','Sekolah\PenilaianMultiPHController@showDokUpload');
    $router->post('penilaian-multiph-dok','Sekolah\PenilaianMultiPHController@storeDokumen');
    $router->delete('penilaian-multiph-dok','Sekolah\PenilaianMultiPHController@deleteDokumen');


    $router->get('pesan-all','Sekolah\PesanController@index');
    $router->get('pesan','Sekolah\PesanController@show');
    $router->post('pesan','Sekolah\PesanController@store');
    $router->post('pesan-ubah','Sekolah\PesanController@update');
    $router->delete('pesan','Sekolah\PesanController@destroy');
    $router->delete('pesan-dok','Sekolah\PesanController@deleteDokumen');
    $router->get('pesan-history','Sekolah\PesanController@historyPesan');
    $router->get('rata2-nilai','Sekolah\PesanController@rata2Nilai');
    $router->get('data-box','Sekolah\PesanController@getDataBox');
    
    $router->get('pesan-kelas','Sekolah\PesanController@getPesanKelas');
    $router->get('pesan-kelas-history','Sekolah\PesanController@getPesanKelasHistory');
    $router->get('pesan-kelas-detail','Sekolah\PesanController@getPesanKelasDetail');

    // DASHBOARD 
    $router->get('rata2-nilai-dashboard','Sekolah\DashboardController@rata2Nilai');
    $router->get('chart-kkm','Sekolah\DashboardController@jumDibawahKKM');

    // GURU
    $router->get('guru-all','Sekolah\GuruController@index');
    $router->get('guru','Sekolah\GuruController@show');
    $router->post('guru','Sekolah\GuruController@store');
    $router->put('guru','Sekolah\GuruController@update');
    $router->delete('guru','Sekolah\GuruController@destroy');

    // EKSKUL
    $router->get('ekskul-all','Sekolah\EkskulController@index');
    $router->get('ekskul','Sekolah\EkskulController@show');
    $router->post('ekskul','Sekolah\EkskulController@store');
    $router->put('ekskul','Sekolah\EkskulController@update');
    $router->delete('ekskul','Sekolah\EkskulController@destroy');
    
    $router->post('data-import','Sekolah\EkskulController@importExcel');
    $router->post('data-import-update','Sekolah\EkskulController@importExcelSiswa');

    //Siswa Matpel Khusus
    $router->get('sis-matpel-khusus-all','Sekolah\SisMatpelKhususController@index');
    $router->get('sis-matpel-khusus','Sekolah\SisMatpelKhususController@show');
    $router->post('sis-matpel-khusus','Sekolah\SisMatpelKhususController@store');
    $router->put('sis-matpel-khusus','Sekolah\SisMatpelKhususController@update');
    $router->delete('sis-matpel-khusus','Sekolah\SisMatpelKhususController@destroy');
    
    $router->get('filter-pp','Sekolah\FilterController@getFilterPP');
    $router->get('filter-ta','Sekolah\FilterController@getFilterTA');
    $router->get('filter-kelas','Sekolah\FilterController@getFilterKelas');
    $router->get('filter-matpel','Sekolah\FilterController@getFilterMatpel');
    $router->get('filter-guru','Sekolah\FilterController@getFilterGuru');
    $router->get('filter-semester','Sekolah\FilterController@getFilterSemester');
    $router->get('filter-tahunajar','Sekolah\FilterController@getFilterTahunAjaran');
    $router->get('filter-kelas-dash','Sekolah\FilterController@getFilterKelasDashboard');
    $router->get('filter-matpel-dash','Sekolah\FilterController@getFilterMatpelDashboard');
    
    $router->get('lap-nilai','Sekolah\LaporanController@getNilai');
    $router->get('lap-guru-kelas','Sekolah\LaporanController@getGuruKelas');
    $router->get('lap-guru-matpel','Sekolah\LaporanController@getGuruMatpel');
    $router->get('lap-siswa','Sekolah\LaporanController@getSiswa');
    $router->get('lap-kd','Sekolah\LaporanController@getKD');

    $router->post('notif','Sekolah\PenilaianController@sendNotif');
    
    $router->get('kartu-piutang','Sekolah\DashSiswaController@getKartuPiutang');
    $router->get('kartu-pdd','Sekolah\DashSiswaController@getKartuPDD');
    $router->get('dash-siswa-profile','Sekolah\DashSiswaController@getProfile');
    
    $router->get('dash-progress-nilai','Sekolah\DashboardController@progressNilai');
    $router->get('dash-komposisi-siswa','Sekolah\DashboardController@komposisiSiswa');
    $router->get('dash-chart-nilai','Sekolah\DashboardController@chartNilai');
    $router->get('dash-tingkat','Sekolah\DashboardController@getTingkat');

    $router->get('absen-kelas','Sekolah\AbsenKelasController@show');
    $router->post('absen-kelas','Sekolah\AbsenKelasController@store');

    // INPUT SISWA
    $router->post('siswa-simpan','Sekolah\SiswaInputController@save');
    $router->post('siswa-update','Sekolah\SiswaInputController@update');
    $router->get('siswa-edit','Sekolah\SiswaInputController@show');

    // UPLOAD SISWA
    $router->post('siswa-upload-simpan','Sekolah\UploadSiswaController@store');
    $router->post('siswa-upload-import','Sekolah\UploadSiswaController@importXLS');
    $router->get('siswa-upload-tmp','Sekolah\UploadSiswaController@dataTMP');

    // UPLOAD RAPORT
    $router->get('raport-dok-all','Sekolah\UploadRaportController@index');
    $router->get('raport-dok-siswa','Sekolah\UploadRaportController@loadSiswa');
    $router->post('raport-dok-siswa','Sekolah\UploadRaportController@storeDokumen');
    $router->post('raport-dok-siswa-edit','Sekolah\UploadRaportController@updateDokumen');
    $router->get('raport-dok-siswa-edit','Sekolah\UploadRaportController@showDokUpload');
    $router->delete('raport-dok-siswa','Sekolah\UploadRaportController@destroy');
    $router->delete('raport-dok-siswa-nis','Sekolah\UploadRaportController@deleteDokumen');
    
    
});

// UPLOAD SISWA
$router->get('siswa-export', 'Sekolah\UploadSiswaController@exportXLS');