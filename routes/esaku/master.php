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
$router->options('{all:.*}', ['middleware' => 'cors', function () {
    return response('');
}]);


$router->group(['middleware' => 'auth:toko'], function () use ($router) {
    //Customer
    $router->get('cust', 'Esaku\Inventori\CustomerController@index');
    $router->post('cust', 'Esaku\Inventori\CustomerController@store');
    $router->put('cust', 'Esaku\Inventori\CustomerController@update');
    $router->delete('cust', 'Esaku\Inventori\CustomerController@destroy');
    $router->get('cust-akun', 'Esaku\Inventori\CustomerController@getAkun');

    //Vendor
    $router->get('vendor', 'Esaku\Inventori\VendorController@index');
    $router->post('vendor', 'Esaku\Inventori\VendorController@store');
    $router->put('vendor', 'Esaku\Inventori\VendorController@update');
    $router->delete('vendor', 'Esaku\Inventori\VendorController@destroy');
    $router->get('vendor-akun', 'Esaku\Inventori\VendorController@getAkun');

    //Gudang
    $router->get('gudang', 'Esaku\Inventori\GudangController@index');
    $router->post('gudang', 'Esaku\Inventori\GudangController@store');
    $router->put('gudang', 'Esaku\Inventori\GudangController@update');
    $router->delete('gudang', 'Esaku\Inventori\GudangController@destroy');
    $router->get('gudang-nik', 'Esaku\Inventori\GudangController@getNIK');
    $router->get('gudang-pp', 'Esaku\Inventori\GudangController@getPP');

    //Klp Barang
    $router->get('barang-klp', 'Esaku\Inventori\BarangKlpController@index');
    $router->post('barang-klp', 'Esaku\Inventori\BarangKlpController@store');
    $router->put('barang-klp', 'Esaku\Inventori\BarangKlpController@update');
    $router->delete('barang-klp', 'Esaku\Inventori\BarangKlpController@destroy');
    $router->get('barang-klp-persediaan', 'Esaku\Inventori\BarangKlpController@getPers');
    $router->get('barang-klp-pendapatan', 'Esaku\Inventori\BarangKlpController@getPdpt');
    $router->get('barang-klp-hpp', 'Esaku\Inventori\BarangKlpController@getHpp');

    //Satuan Barang
    $router->get('barang-satuan', 'Esaku\Inventori\SatuanController@index');
    $router->post('barang-satuan', 'Esaku\Inventori\SatuanController@store');
    $router->put('barang-satuan', 'Esaku\Inventori\SatuanController@update');
    $router->delete('barang-satuan', 'Esaku\Inventori\SatuanController@destroy');

    //Barang
    $router->get('barang', 'Esaku\Inventori\BarangController@index');
    $router->post('barang', 'Esaku\Inventori\BarangController@store');
    $router->post('barang-ubah', 'Esaku\Inventori\BarangController@update');
    $router->delete('barang', 'Esaku\Inventori\BarangController@destroy');

    //Bonus
    $router->get('bonus', 'Esaku\Inventori\BonusController@index');
    $router->post('bonus', 'Esaku\Inventori\BonusController@store');
    $router->put('bonus', 'Esaku\Inventori\BonusController@update');
    $router->delete('bonus', 'Esaku\Inventori\BonusController@destroy');

    //Jasa Kirim
    $router->get('jasa-kirim', 'Esaku\Inventori\JasaKirimController@index');
    $router->post('jasa-kirim', 'Esaku\Inventori\JasaKirimController@store');
    $router->put('jasa-kirim', 'Esaku\Inventori\JasaKirimController@update');
    $router->delete('jasa-kirim', 'Esaku\Inventori\JasaKirimController@destroy');

    //Customer OL
    $router->get('cust-ol', 'Esaku\Inventori\CustomerOLController@index');
    $router->post('cust-ol', 'Esaku\Inventori\CustomerOLController@store');
    $router->put('cust-ol', 'Esaku\Inventori\CustomerOLController@update');
    $router->delete('cust-ol', 'Esaku\Inventori\CustomerOLController@destroy');



    //ADMIN SETTING
    //Menu
    $router->get('menu', 'Esaku\Setting\MenuController@index');
    $router->post('menu', 'Esaku\Setting\MenuController@store');
    $router->put('menu', 'Esaku\Setting\MenuController@update');
    $router->delete('menu', 'Esaku\Setting\MenuController@destroy');
    $router->get('menu-klp', 'Esaku\Setting\MenuController@getKlp');
    $router->post('menu-move', 'Esaku\Setting\MenuController@simpanMove');

    //Akses User
    $router->get('akses-user', 'Esaku\Setting\HakaksesController@index');
    $router->post('akses-user', 'Esaku\Setting\HakaksesController@store');
    $router->get('akses-user-detail', 'Esaku\Setting\HakaksesController@show');
    $router->put('akses-user', 'Esaku\Setting\HakaksesController@update');
    $router->delete('akses-user', 'Esaku\Setting\HakaksesController@destroy');
    $router->get('akses-user-menu', 'Esaku\Setting\HakaksesController@getMenu');

    //Form
    $router->get('form', 'Esaku\Setting\FormController@index');
    $router->post('form', 'Esaku\Setting\FormController@store');
    $router->put('form', 'Esaku\Setting\FormController@update');
    $router->delete('form', 'Esaku\Setting\FormController@destroy');

    //Karyawan
    $router->get('karyawan', 'Esaku\Setting\KaryawanController@index');
    $router->post('karyawan', 'Esaku\Setting\KaryawanController@store');
    $router->get('karyawan-detail', 'Esaku\Setting\KaryawanController@show');
    $router->post('karyawan-ubah', 'Esaku\Setting\KaryawanController@update');
    $router->delete('karyawan', 'Esaku\Setting\KaryawanController@destroy');

    //Kelompok Menu
    $router->get('menu-klp', 'Esaku\Setting\KelompokMenuController@index');
    $router->post('menu-klp', 'Esaku\Setting\KelompokMenuController@store');
    $router->put('menu-klp', 'Esaku\Setting\KelompokMenuController@update');
    $router->delete('menu-klp', 'Esaku\Setting\KelompokMenuController@destroy');

    //Unit
    $router->get('unit', 'Esaku\Setting\UnitController@index');
    $router->post('unit', 'Esaku\Setting\UnitController@store');
    $router->put('unit', 'Esaku\Setting\UnitController@update');
    $router->delete('unit', 'Esaku\Setting\UnitController@destroy');

    // GRAFIK
    $router->get('setting-grafik', 'Esaku\Setting\SettingGrafikController@index');
    $router->get('setting-grafik-detail', 'Esaku\Setting\SettingGrafikController@show');
    $router->post('setting-grafik', 'Esaku\Setting\SettingGrafikController@store');
    $router->put('setting-grafik', 'Esaku\Setting\SettingGrafikController@update');
    $router->delete('setting-grafik', 'Esaku\Setting\SettingGrafikController@destroy');
    $router->get('setting-grafik-neraca', 'Esaku\Setting\SettingGrafikController@getNeraca');
    $router->get('setting-grafik-klp', 'Esaku\Setting\SettingGrafikController@getKlp');

    // RASIO
    $router->get('setting-rasio', 'Esaku\Setting\SettingRasioController@index');
    $router->get('setting-rasio-detail', 'Esaku\Setting\SettingRasioController@show');
    $router->post('setting-rasio', 'Esaku\Setting\SettingRasioController@store');
    $router->put('setting-rasio', 'Esaku\Setting\SettingRasioController@update');
    $router->delete('setting-rasio', 'Esaku\Setting\SettingRasioController@destroy');
    $router->get('setting-rasio-neraca', 'Esaku\Setting\SettingRasioController@getNeraca');
    $router->get('setting-rasio-klp', 'Esaku\Setting\SettingRasioController@getKlp');

    // KEUANGAN
    //Master Masakun
    $router->get('masakun', 'Esaku\Keuangan\MasakunController@index');
    $router->get('masakun-detail', 'Esaku\Keuangan\MasakunController@show');
    $router->post('masakun', 'Esaku\Keuangan\MasakunController@store');
    $router->put('masakun', 'Esaku\Keuangan\MasakunController@update');
    $router->delete('masakun', 'Esaku\Keuangan\MasakunController@destroy');
    $router->get('masakun-curr', 'Esaku\Keuangan\MasakunController@getCurrency');
    $router->get('masakun-modul', 'Esaku\Keuangan\MasakunController@getModul');

    //Master Masakun Detail
    $router->get('msakundet', 'Esaku\Keuangan\MasakunDetailController@index');
    $router->get('msakundet-detail', 'Esaku\Keuangan\MasakunDetailController@show');
    $router->post('msakundet', 'Esaku\Keuangan\MasakunDetailController@store');
    $router->put('msakundet', 'Esaku\Keuangan\MasakunDetailController@update');
    $router->delete('msakundet', 'Esaku\Keuangan\MasakunDetailController@destroy');
    $router->get('msakundet-curr', 'Esaku\Keuangan\MasakunDetailController@getCurrency');
    $router->get('msakundet-modul', 'Esaku\Keuangan\MasakunDetailController@getModul');
    $router->get('msakundet-flag', 'Esaku\Keuangan\MasakunDetailController@getFlagAkun');
    $router->get('msakundet-neraca', 'Esaku\Keuangan\MasakunDetailController@getNeraca');

    //Format Laporan
    $router->get('format-laporan', 'Esaku\Keuangan\FormatLaporanController@show');
    $router->post('format-laporan', 'Esaku\Keuangan\FormatLaporanController@store');
    $router->put('format-laporan', 'Esaku\Keuangan\FormatLaporanController@update');
    $router->delete('format-laporan', 'Esaku\Keuangan\FormatLaporanController@destroy');
    $router->get('format-laporan-versi', 'Esaku\Keuangan\FormatLaporanController@getVersi');
    $router->get('format-laporan-tipe', 'Esaku\Keuangan\FormatLaporanController@getTipe');
    $router->get('format-laporan-relakun', 'Esaku\Keuangan\FormatLaporanController@getRelakun');
    $router->post('format-laporan-relasi', 'Esaku\Keuangan\FormatLaporanController@simpanRelasi');
    $router->post('format-laporan-move', 'Esaku\Keuangan\FormatLaporanController@simpanMove');


    //fs
    $router->get('listFSAktif', 'Esaku\Keuangan\FSController@listFSAktif');
    $router->get('cariFSAktif', 'Esaku\Keuangan\FSController@cariFSAktif');
    $router->get('fs', 'Esaku\Keuangan\FSController@index');
    $router->post('fs', 'Esaku\Keuangan\FSController@store');
    $router->put('fs', 'Esaku\Keuangan\FSController@update');
    $router->delete('fs', 'Esaku\Keuangan\FSController@destroy');

    //flagakun
    $router->get('cariFlag', 'Esaku\Keuangan\FlagAkunController@cariFlag');
    $router->get('flagakun', 'Esaku\Keuangan\FlagAkunController@index');
    $router->post('flagakun', 'Esaku\Keuangan\FlagAkunController@store');
    $router->put('flagakun', 'Esaku\Keuangan\FlagAkunController@update');
    $router->delete('flagakun', 'Esaku\Keuangan\FlagAkunController@destroy');

    //flagrelasi
    $router->get('flagrelasi', 'Esaku\Keuangan\FlagRelasiController@getFlag');
    $router->get('flagrelasi/{kode_flag}', 'Esaku\Keuangan\FlagRelasiController@getAkunFlag');
    $router->get('flagrelasi-akun', 'Esaku\Keuangan\FlagRelasiController@getAkun');
    $router->get('flagrelasi-cari', 'Esaku\Keuangan\FlagRelasiController@cariAkunFlag');
    $router->put('flagrelasi', 'Esaku\Keuangan\FlagRelasiController@update');
    $router->delete('flagrelasi', 'Esaku\Keuangan\FlagRelasiController@destroy');

    //Data Periode Aktif
    $router->get('periode-aktif', 'Esaku\Keuangan\PeriodeAktifController@index');
    $router->post('periode-aktif', 'Esaku\Keuangan\PeriodeAktifController@store');
    $router->put('periode-aktif', 'Esaku\Keuangan\PeriodeAktifController@update');
    $router->delete('periode-aktif', 'Esaku\Keuangan\PeriodeAktifController@destroy');
    $router->get('periode-aktif-periode', 'Esaku\Keuangan\PeriodeAktifController@getPeriode');

    //Data Dok Jenis
    $router->get('dok-jenis', 'Esaku\Keuangan\JenisDokController@index');
    $router->post('dok-jenis', 'Esaku\Keuangan\JenisDokController@store');
    $router->put('dok-jenis', 'Esaku\Keuangan\JenisDokController@update');
    $router->delete('dok-jenis', 'Esaku\Keuangan\JenisDokController@destroy');


    // ASET
    //Data Akun Aktiva Tetap
    $router->get('fa-klpakun', 'Esaku\Aktap\FaKlpAkunController@index');
    $router->post('fa-klpakun', 'Esaku\Aktap\FaKlpAkunController@store');
    $router->put('fa-klpakun', 'Esaku\Aktap\FaKlpAkunController@update');
    $router->delete('fa-klpakun', 'Esaku\Aktap\FaKlpAkunController@destroy');

    //Data Kelompok Barang
    $router->get('klp-barang', 'Esaku\Aktap\KlpBarangController@index');
    $router->post('klp-barang', 'Esaku\Aktap\KlpBarangController@store');
    $router->put('klp-barang', 'Esaku\Aktap\KlpBarangController@update');
    $router->delete('klp-barang', 'Esaku\Aktap\KlpBarangController@destroy');

    // KAS BANK
    //Master Ref Trans
    $router->get('reftrans-kode', 'Esaku\KasBank\ReferensiTransController@generateKodeByJenis');
    $router->get('reftrans', 'Esaku\KasBank\ReferensiTransController@index');
    $router->get('reftrans-detail', 'Esaku\KasBank\ReferensiTransController@show');
    $router->post('reftrans', 'Esaku\KasBank\ReferensiTransController@store');
    $router->put('reftrans', 'Esaku\KasBank\ReferensiTransController@update');
    $router->delete('reftrans', 'Esaku\KasBank\ReferensiTransController@destroy');

    // SIMPANAN
    $router->get('anggota', 'Esaku\Simpanan\AnggotaController@index');
    $router->post('anggota', 'Esaku\Simpanan\AnggotaController@store');
    $router->put('anggota', 'Esaku\Simpanan\AnggotaController@update');
    $router->delete('anggota', 'Esaku\Simpanan\AnggotaController@destroy');

    $router->get('jenis-simpanan', 'Esaku\Simpanan\JenisController@index');
    $router->get('akun-simpanan', 'Esaku\Simpanan\JenisController@getAkun');
    $router->post('jenis-simpanan', 'Esaku\Simpanan\JenisController@store');
    $router->put('jenis-simpanan', 'Esaku\Simpanan\JenisController@update');
    $router->delete('jenis-simpanan', 'Esaku\Simpanan\JenisController@destroy');

    $router->get('generate-nokartu', 'Esaku\Simpanan\KartuSimpController@generateNo');
    $router->get('kartu-simpanan', 'Esaku\Simpanan\KartuSimpController@index');
    $router->post('kartu-simpanan', 'Esaku\Simpanan\KartuSimpController@store');
    $router->put('kartu-simpanan', 'Esaku\Simpanan\KartuSimpController@update');
    $router->delete('kartu-simpanan', 'Esaku\Simpanan\KartuSimpController@destroy');

    // PIUTANG
    $router->get('piu-cust', 'Esaku\Piutang\CustomerController@index');
    $router->post('piu-cust', 'Esaku\Piutang\CustomerController@store');
    $router->put('piu-cust', 'Esaku\Piutang\CustomerController@update');
    $router->delete('piu-cust', 'Esaku\Piutang\CustomerController@destroy');
    $router->get('piu-cust-akun', 'Esaku\Piutang\CustomerController@getAkun');

    /*-------------------------------------------------------------------------
        BEGIN MODUL SDM
    --------------------------------------------------------------------------*/
    // MASTER DATA

    // Lokasi Kerja
    $router->get('sdm-lokers', 'Sdm\LokerController@index');
    $router->get('sdm-loker', 'Sdm\LokerController@show');
    $router->get('sdm-loker-bm', 'Sdm\LokerController@lokerByBm');
    $router->post('sdm-loker', 'Sdm\LokerController@save');
    $router->post('sdm-loker-update', 'Sdm\LokerController@update');
    $router->delete('sdm-loker', 'Sdm\LokerController@destroy');

    // Status Karyawan
    $router->get('sdm-statuss', 'Sdm\StatusController@index');
    $router->get('sdm-status', 'Sdm\StatusController@show');
    $router->post('sdm-status', 'Sdm\StatusController@save');
    $router->post('sdm-status-update', 'Sdm\StatusController@update');
    $router->delete('sdm-status', 'Sdm\StatusController@destroy');

    // Jabatan Karyawan
    $router->get('sdm-jabatans', 'Sdm\JabatanController@index');
    $router->get('sdm-jabatan', 'Sdm\JabatanController@show');
    $router->post('sdm-jabatan', 'Sdm\JabatanController@save');
    $router->post('sdm-jabatan-update', 'Sdm\JabatanController@update');
    $router->delete('sdm-jabatan', 'Sdm\JabatanController@destroy');

    // Golongan Karyawan
    $router->get('sdm-golongans', 'Sdm\GolonganController@index');
    $router->get('sdm-golongan', 'Sdm\GolonganController@show');
    $router->post('sdm-golongan', 'Sdm\GolonganController@save');
    $router->post('sdm-golongan-update', 'Sdm\GolonganController@update');
    $router->delete('sdm-golongan', 'Sdm\GolonganController@destroy');

    // Status Pajak Karyawan
    $router->get('sdm-pajaks', 'Sdm\StatusPajakController@index');
    $router->get('sdm-pajak', 'Sdm\StatusPajakController@show');
    $router->post('sdm-pajak', 'Sdm\StatusPajakController@save');
    $router->post('sdm-pajak-update', 'Sdm\StatusPajakController@update');
    $router->delete('sdm-pajak', 'Sdm\StatusPajakController@destroy');

    // Unit Karyawan
    $router->get('sdm-units', 'Sdm\UnitController@index');
    $router->get('sdm-unit', 'Sdm\UnitController@show');
    $router->post('sdm-unit', 'Sdm\UnitController@save');
    $router->post('sdm-unit-update', 'Sdm\UnitController@update');
    $router->delete('sdm-unit', 'Sdm\UnitController@destroy');

    // Profesi Karyawan
    $router->get('sdm-profesis', 'Sdm\ProfesiController@index');
    $router->get('sdm-profesi', 'Sdm\ProfesiController@show');
    $router->post('sdm-profesi', 'Sdm\ProfesiController@save');
    $router->post('sdm-profesi-update', 'Sdm\ProfesiController@update');
    $router->delete('sdm-profesi', 'Sdm\ProfesiController@destroy');

    // Agama Karyawan
    $router->get('sdm-agamas', 'Sdm\AgamaController@index');
    $router->get('sdm-agama', 'Sdm\AgamaController@show');
    $router->post('sdm-agama', 'Sdm\AgamaController@save');
    $router->post('sdm-agama-update', 'Sdm\AgamaController@update');
    $router->delete('sdm-agama', 'Sdm\AgamaController@destroy');

    // Jurusan Karyawan
    $router->get('sdm-jurusans', 'Sdm\JurusanController@index');
    $router->get('sdm-jurusan', 'Sdm\JurusanController@show');
    $router->post('sdm-jurusan', 'Sdm\JurusanController@save');
    $router->post('sdm-jurusan-update', 'Sdm\JurusanController@update');
    $router->delete('sdm-jurusan', 'Sdm\JurusanController@destroy');

    // Strata Karyawan
    $router->get('sdm-stratas', 'Sdm\StrataController@index');
    $router->get('sdm-strata', 'Sdm\StrataController@show');
    $router->post('sdm-strata', 'Sdm\StrataController@save');
    $router->post('sdm-strata-update', 'Sdm\StrataController@update');
    $router->delete('sdm-strata', 'Sdm\StrataController@destroy');

    //Master Data Area
    $router->get('sdm-area', 'Sdm\AreaController@index');
    $router->get('show-area', 'Sdm\AreaController@show');
    $router->post('sdm-area', 'Sdm\AreaController@store');
    $router->put('sdm-area', 'Sdm\AreaController@update');
    $router->delete('sdm-area', 'Sdm\AreaController@destroy');

    // Master Data FM
    $router->get('sdm-fm', 'Sdm\FmController@index');
    $router->get('sdm-fm-filter-area', 'Sdm\FmController@filterArea');
    $router->get('show-fm', 'Sdm\FmController@show');
    $router->post('sdm-fm', 'Sdm\FmController@store');
    $router->put('sdm-fm', 'Sdm\FmController@update');
    $router->delete('sdm-fm', 'Sdm\FmController@destroy');

    // Master Data BM
    $router->get('sdm-bm', 'Sdm\BmController@index');
    $router->get('sdm-bm-filter-fm', 'Sdm\BmController@filterFm');
    $router->get('show-bm', 'Sdm\BmController@show');
    $router->post('sdm-bm', 'Sdm\BmController@store');
    $router->put('sdm-bm', 'Sdm\BmController@update');
    $router->delete('sdm-bm', 'Sdm\BmController@destroy');

    // Master Data Witel
    $router->get('sdm-witel', 'Sdm\WitelController@index');
    $router->get('show-witel', 'Sdm\WitelController@show');
    $router->post('sdm-witel', 'Sdm\WitelController@store');
    $router->put('sdm-witel', 'Sdm\WitelController@update');
    $router->delete('sdm-witel', 'Sdm\WitelController@destroy');

    // Master Data Bank
    $router->get('sdm-bank', 'Sdm\BankController@index');
    $router->get('show-bank', 'Sdm\BankController@show');
    $router->post('sdm-bank', 'Sdm\BankController@store');
    $router->put('sdm-bank', 'Sdm\BankController@update');
    $router->delete('sdm-bank', 'Sdm\BankController@destroy');

    // Master Data KLIEN
    $router->get('sdm-klien', 'Sdm\KlienController@index');
    $router->get('show-klien', 'Sdm\KlienController@show');
    $router->post('sdm-klien', 'Sdm\KlienController@store');
    $router->put('sdm-klien', 'Sdm\KlienController@update');
    $router->delete('sdm-klien', 'Sdm\KlienController@destroy');

    // Master Data Gaji Param
    $router->get('sdm-gaji-param', 'Sdm\GajiParamController@index');
    $router->get('show-gaji-param', 'Sdm\GajiParamController@show');
    $router->post('sdm-gaji-param', 'Sdm\GajiParamController@store');
    $router->put('sdm-gaji-param', 'Sdm\GajiParamController@update');
    $router->delete('sdm-gaji-param', 'Sdm\GajiParamController@destroy');
    /*-------------------------------------------------------------------------
       END MODUL SDM
    --------------------------------------------------------------------------*/
});
