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


$router->group(['middleware' => 'auth:toko'], function () use ($router) {
    //Customer
    $router->get('cust','Esaku\Inventori\CustomerController@index');
    $router->post('cust','Esaku\Inventori\CustomerController@store');
    $router->put('cust','Esaku\Inventori\CustomerController@update');
    $router->delete('cust','Esaku\Inventori\CustomerController@destroy');
    $router->get('cust-akun','Esaku\Inventori\CustomerController@getAkun');

    //Vendor
    $router->get('vendor','Esaku\Inventori\VendorController@index');
    $router->post('vendor','Esaku\Inventori\VendorController@store');
    $router->put('vendor','Esaku\Inventori\VendorController@update');
    $router->delete('vendor','Esaku\Inventori\VendorController@destroy');
    $router->get('vendor-akun','Esaku\Inventori\VendorController@getAkun');

    //Gudang
    $router->get('gudang','Esaku\Inventori\GudangController@index');
    $router->post('gudang','Esaku\Inventori\GudangController@store');
    $router->put('gudang','Esaku\Inventori\GudangController@update');
    $router->delete('gudang','Esaku\Inventori\GudangController@destroy');
    $router->get('gudang-nik','Esaku\Inventori\GudangController@getNIK');
    $router->get('gudang-pp','Esaku\Inventori\GudangController@getPP');

    //Klp Barang
    $router->get('barang-klp','Esaku\Inventori\BarangKlpController@index');
    $router->post('barang-klp','Esaku\Inventori\BarangKlpController@store');
    $router->put('barang-klp','Esaku\Inventori\BarangKlpController@update');
    $router->delete('barang-klp','Esaku\Inventori\BarangKlpController@destroy');
    $router->get('barang-klp-persediaan','Esaku\Inventori\BarangKlpController@getPers');
    $router->get('barang-klp-pendapatan','Esaku\Inventori\BarangKlpController@getPdpt');
    $router->get('barang-klp-hpp','Esaku\Inventori\BarangKlpController@getHpp');

    //Satuan Barang
    $router->get('barang-satuan','Esaku\Inventori\SatuanController@index');
    $router->post('barang-satuan','Esaku\Inventori\SatuanController@store');
    $router->put('barang-satuan','Esaku\Inventori\SatuanController@update');
    $router->delete('barang-satuan','Esaku\Inventori\SatuanController@destroy');

    //Barang
    $router->get('barang','Esaku\Inventori\BarangController@index');
    $router->post('barang','Esaku\Inventori\BarangController@store');
    $router->post('barang-ubah','Esaku\Inventori\BarangController@update');
    $router->delete('barang','Esaku\Inventori\BarangController@destroy');

    //Bonus
    $router->get('bonus','Esaku\Inventori\BonusController@index');
    $router->post('bonus','Esaku\Inventori\BonusController@store');
    $router->put('bonus','Esaku\Inventori\BonusController@update');
    $router->delete('bonus','Esaku\Inventori\BonusController@destroy');

    //Jasa Kirim
    $router->get('jasa-kirim','Esaku\Inventori\JasaKirimController@index');
    $router->post('jasa-kirim','Esaku\Inventori\JasaKirimController@store');
    $router->put('jasa-kirim','Esaku\Inventori\JasaKirimController@update');
    $router->delete('jasa-kirim','Esaku\Inventori\JasaKirimController@destroy');

    //Customer OL
    $router->get('cust-ol','Esaku\Inventori\CustomerOLController@index');
    $router->post('cust-ol','Esaku\Inventori\CustomerOLController@store');
    $router->put('cust-ol','Esaku\Inventori\CustomerOLController@update');
    $router->delete('cust-ol','Esaku\Inventori\CustomerOLController@destroy');

    //Data Kelompok Barang
    $router->get('klp-barang','Esaku\Inventori\KlpBarangController@index');
    $router->post('klp-barang','Esaku\Inventori\KlpBarangController@store');
    $router->put('klp-barang','Esaku\Inventori\KlpBarangController@update');
    $router->delete('klp-barang','Esaku\Inventori\KlpBarangController@destroy');

    //ADMIN SETTING
    //Menu
    $router->get('menu','Esaku\Setting\MenuController@index');
    $router->post('menu','Esaku\Setting\MenuController@store');
    $router->put('menu','Esaku\Setting\MenuController@update');
    $router->delete('menu','Esaku\Setting\MenuController@destroy');
    $router->get('menu-klp','Esaku\Setting\MenuController@getKlp');
    $router->post('menu-move','Esaku\Setting\MenuController@simpanMove');

    //Akses User
    $router->get('akses-user','Esaku\Setting\HakaksesController@index');
    $router->post('akses-user','Esaku\Setting\HakaksesController@store');
    $router->get('akses-user-detail','Esaku\Setting\HakaksesController@show');
    $router->put('akses-user','Esaku\Setting\HakaksesController@update');
    $router->delete('akses-user','Esaku\Setting\HakaksesController@destroy');
    $router->get('akses-user-menu','Esaku\Setting\HakaksesController@getMenu');
    
    //Form
    $router->get('form','Esaku\Setting\FormController@index');
    $router->post('form','Esaku\Setting\FormController@store');
    $router->put('form','Esaku\Setting\FormController@update');
    $router->delete('form','Esaku\Setting\FormController@destroy');

    //Karyawan
    $router->get('karyawan','Esaku\Setting\KaryawanController@index');
    $router->post('karyawan','Esaku\Setting\KaryawanController@store');
    $router->get('karyawan-detail','Esaku\Setting\KaryawanController@show');
    $router->post('karyawan-ubah','Esaku\Setting\KaryawanController@update');
    $router->delete('karyawan','Esaku\Setting\KaryawanController@destroy');

    //Kelompok Menu
    $router->get('menu-klp','Esaku\Setting\KelompokMenuController@index');
    $router->post('menu-klp','Esaku\Setting\KelompokMenuController@store');
    $router->put('menu-klp','Esaku\Setting\KelompokMenuController@update');
    $router->delete('menu-klp','Esaku\Setting\KelompokMenuController@destroy');

    //Unit
    $router->get('unit','Esaku\Setting\UnitController@index');
    $router->post('unit','Esaku\Setting\UnitController@store');
    $router->put('unit','Esaku\Setting\UnitController@update');
    $router->delete('unit','Esaku\Setting\UnitController@destroy');
    
    // GRAFIK
    $router->get('setting-grafik','Esaku\Setting\SettingGrafikController@index');     
    $router->get('setting-grafik-detail','Esaku\Setting\SettingGrafikController@show'); 
    $router->post('setting-grafik','Esaku\Setting\SettingGrafikController@store');
    $router->put('setting-grafik','Esaku\Setting\SettingGrafikController@update');    
    $router->delete('setting-grafik','Esaku\Setting\SettingGrafikController@destroy');   
    $router->get('setting-grafik-neraca','Esaku\Setting\SettingGrafikController@getNeraca');    
    $router->get('setting-grafik-klp','Esaku\Setting\SettingGrafikController@getKlp');
    
    // RASIO
    $router->get('setting-rasio','Esaku\Setting\SettingRasioController@index');     
    $router->get('setting-rasio-detail','Esaku\Setting\SettingRasioController@show'); 
    $router->post('setting-rasio','Esaku\Setting\SettingRasioController@store');
    $router->put('setting-rasio','Esaku\Setting\SettingRasioController@update');    
    $router->delete('setting-rasio','Esaku\Setting\SettingRasioController@destroy');   
    $router->get('setting-rasio-neraca','Esaku\Setting\SettingRasioController@getNeraca');    
    $router->get('setting-rasio-klp','Esaku\Setting\SettingRasioController@getKlp');

    // KEUANGAN
    //Master Masakun
    $router->get('masakun','Esaku\Keuangan\MasakunController@index');
    $router->get('masakun-detail','Esaku\Keuangan\MasakunController@show');
    $router->post('masakun','Esaku\Keuangan\MasakunController@store');
    $router->put('masakun','Esaku\Keuangan\MasakunController@update');
    $router->delete('masakun','Esaku\Keuangan\MasakunController@destroy');
    $router->get('masakun-curr','Esaku\Keuangan\MasakunController@getCurrency');
    $router->get('masakun-modul','Esaku\Keuangan\MasakunController@getModul');

    //Master Masakun Detail
    $router->get('msakundet','Esaku\Keuangan\MasakunDetailController@index');
    $router->get('msakundet-detail','Esaku\Keuangan\MasakunDetailController@show');
    $router->post('msakundet','Esaku\Keuangan\MasakunDetailController@store');
    $router->put('msakundet','Esaku\Keuangan\MasakunDetailController@update');
    $router->delete('msakundet','Esaku\Keuangan\MasakunDetailController@destroy');
    $router->get('msakundet-curr','Esaku\Keuangan\MasakunDetailController@getCurrency');
    $router->get('msakundet-modul','Esaku\Keuangan\MasakunDetailController@getModul');
    $router->get('msakundet-flag','Esaku\Keuangan\MasakunDetailController@getFlagAkun');
    $router->get('msakundet-neraca','Esaku\Keuangan\MasakunDetailController@getNeraca');

    //Format Laporan
    $router->get('format-laporan','Esaku\Keuangan\FormatLaporanController@show');
    $router->post('format-laporan','Esaku\Keuangan\FormatLaporanController@store');
    $router->put('format-laporan','Esaku\Keuangan\FormatLaporanController@update');
    $router->delete('format-laporan','Esaku\Keuangan\FormatLaporanController@destroy');
    $router->get('format-laporan-versi','Esaku\Keuangan\FormatLaporanController@getVersi');
    $router->get('format-laporan-tipe','Esaku\Keuangan\FormatLaporanController@getTipe');
    $router->get('format-laporan-relakun','Esaku\Keuangan\FormatLaporanController@getRelakun');
    $router->post('format-laporan-relasi','Esaku\Keuangan\FormatLaporanController@simpanRelasi');
    $router->post('format-laporan-move','Esaku\Keuangan\FormatLaporanController@simpanMove');

    
    //fs
    $router->get('listFSAktif','Esaku\Keuangan\FSController@listFSAktif');         
    $router->get('cariFSAktif','Esaku\Keuangan\FSController@cariFSAktif');
    $router->get('fs','Esaku\Keuangan\FSController@index');
    $router->post('fs','Esaku\Keuangan\FSController@store');
    $router->put('fs','Esaku\Keuangan\FSController@update');
    $router->delete('fs','Esaku\Keuangan\FSController@destroy'); 

    //flagakun
    $router->get('cariFlag','Esaku\Keuangan\FlagAkunController@cariFlag');
    $router->get('flagakun','Esaku\Keuangan\FlagAkunController@index');
    $router->post('flagakun','Esaku\Keuangan\FlagAkunController@store');
    $router->put('flagakun','Esaku\Keuangan\FlagAkunController@update');
    $router->delete('flagakun','Esaku\Keuangan\FlagAkunController@destroy'); 
    
    //flagrelasi
    $router->get('flagrelasi','Esaku\Keuangan\FlagRelasiController@getFlag');
    $router->get('flagrelasi/{kode_flag}','Esaku\Keuangan\FlagRelasiController@getAkunFlag');
    $router->get('flagrelasi-akun','Esaku\Keuangan\FlagRelasiController@getAkun');    
    $router->get('flagrelasi-cari','Esaku\Keuangan\FlagRelasiController@cariAkunFlag');    
    $router->put('flagrelasi','Esaku\Keuangan\FlagRelasiController@update');
    $router->delete('flagrelasi','Esaku\Keuangan\FlagRelasiController@destroy'); 

    //Data Periode Aktif
    $router->get('periode-aktif','Esaku\Keuangan\PeriodeAktifController@index');
    $router->post('periode-aktif','Esaku\Keuangan\PeriodeAktifController@store');
    $router->put('periode-aktif','Esaku\Keuangan\PeriodeAktifController@update');
    $router->delete('periode-aktif','Esaku\Keuangan\PeriodeAktifController@destroy');
    $router->get('periode-aktif-periode','Esaku\Keuangan\PeriodeAktifController@getPeriode');

    //Data Dok Jenis
    $router->get('dok-jenis','Esaku\Keuangan\JenisDokController@index');
    $router->post('dok-jenis','Esaku\Keuangan\JenisDokController@store');
    $router->put('dok-jenis','Esaku\Keuangan\JenisDokController@update');
    $router->delete('dok-jenis','Esaku\Keuangan\JenisDokController@destroy');


    // ASET
    //Data Akun Aktiva Tetap
    $router->get('fa-klpakun','Esaku\Aktap\FaKlpAkunController@index');
    $router->post('fa-klpakun','Esaku\Aktap\FaKlpAkunController@store');
    $router->put('fa-klpakun','Esaku\Aktap\FaKlpAkunController@update');
    $router->delete('fa-klpakun','Esaku\Aktap\FaKlpAkunController@destroy');

    // KAS BANK
    //Master Ref Trans
    $router->get('reftrans-kode','Esaku\KasBank\ReferensiTransController@generateKodeByJenis');
    $router->get('reftrans','Esaku\KasBank\ReferensiTransController@index');
    $router->get('reftrans-detail','Esaku\KasBank\ReferensiTransController@show');
    $router->post('reftrans','Esaku\KasBank\ReferensiTransController@store');
    $router->put('reftrans','Esaku\KasBank\ReferensiTransController@update');
    $router->delete('reftrans','Esaku\KasBank\ReferensiTransController@destroy');


});



?>