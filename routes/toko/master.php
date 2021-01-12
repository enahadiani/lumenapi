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
    $router->get('cust','Toko\CustomerController@index');
    $router->post('cust','Toko\CustomerController@store');
    $router->put('cust','Toko\CustomerController@update');
    $router->delete('cust','Toko\CustomerController@destroy');
    $router->get('cust-akun','Toko\CustomerController@getAkun');

    //Vendor
    $router->get('vendor','Toko\VendorController@index');
    $router->post('vendor','Toko\VendorController@store');
    $router->put('vendor','Toko\VendorController@update');
    $router->delete('vendor','Toko\VendorController@destroy');
    $router->get('vendor-akun','Toko\VendorController@getAkun');

    //Gudang
    $router->get('gudang','Toko\GudangController@index');
    $router->post('gudang','Toko\GudangController@store');
    $router->put('gudang','Toko\GudangController@update');
    $router->delete('gudang','Toko\GudangController@destroy');
    $router->get('gudang-nik','Toko\GudangController@getNIK');
    $router->get('gudang-pp','Toko\GudangController@getPP');

    //Klp Barang
    $router->get('barang-klp','Toko\BarangKlpController@index');
    $router->post('barang-klp','Toko\BarangKlpController@store');
    $router->put('barang-klp','Toko\BarangKlpController@update');
    $router->delete('barang-klp','Toko\BarangKlpController@destroy');
    $router->get('barang-klp-persediaan','Toko\BarangKlpController@getPers');
    $router->get('barang-klp-pendapatan','Toko\BarangKlpController@getPdpt');
    $router->get('barang-klp-hpp','Toko\BarangKlpController@getHpp');

    //Satuan Barang
    $router->get('barang-satuan','Toko\SatuanController@index');
    $router->post('barang-satuan','Toko\SatuanController@store');
    $router->put('barang-satuan','Toko\SatuanController@update');
    $router->delete('barang-satuan','Toko\SatuanController@destroy');

    //Barang
    $router->get('barang','Toko\BarangController@index');
    $router->post('barang','Toko\BarangController@store');
    $router->post('barang-ubah','Toko\BarangController@update');
    $router->delete('barang','Toko\BarangController@destroy');

    //Bonus
    $router->get('bonus','Toko\BonusController@index');
    $router->post('bonus','Toko\BonusController@store');
    $router->put('bonus','Toko\BonusController@update');
    $router->delete('bonus','Toko\BonusController@destroy');

    //Jasa Kirim
    $router->get('jasa-kirim','Toko\JasaKirimController@index');
    $router->post('jasa-kirim','Toko\JasaKirimController@store');
    $router->put('jasa-kirim','Toko\JasaKirimController@update');
    $router->delete('jasa-kirim','Toko\JasaKirimController@destroy');

    //Customer OL
    $router->get('cust-ol','Toko\CustomerOLController@index');
    $router->post('cust-ol','Toko\CustomerOLController@store');
    $router->put('cust-ol','Toko\CustomerOLController@update');
    $router->delete('cust-ol','Toko\CustomerOLController@destroy');

    //ADMIN
    //Menu
    $router->get('menu','Toko\MenuController@index');
    $router->post('menu','Toko\MenuController@store');
    $router->put('menu','Toko\MenuController@update');
    $router->delete('menu','Toko\MenuController@destroy');
    $router->get('menu-klp','Toko\MenuController@getKlp');
    $router->post('menu-move','Toko\MenuController@simpanMove');

    //Akses User
    $router->get('akses-user','Toko\HakaksesController@index');
    $router->post('akses-user','Toko\HakaksesController@store');
    $router->get('akses-user-detail','Toko\HakaksesController@show');
    $router->put('akses-user','Toko\HakaksesController@update');
    $router->delete('akses-user','Toko\HakaksesController@destroy');
    $router->get('akses-user-menu','Toko\HakaksesController@getMenu');
    
    //Form
    $router->get('form','Toko\FormController@index');
    $router->post('form','Toko\FormController@store');
    $router->put('form','Toko\FormController@update');
    $router->delete('form','Toko\FormController@destroy');

    //Karyawan
    $router->get('karyawan','Toko\KaryawanController@index');
    $router->post('karyawan','Toko\KaryawanController@store');
    $router->get('karyawan-detail','Toko\KaryawanController@show');
    $router->post('karyawan-ubah','Toko\KaryawanController@update');
    $router->delete('karyawan','Toko\KaryawanController@destroy');

    //Kelompok Menu
    $router->get('menu-klp','Toko\KelompokMenuController@index');
    $router->post('menu-klp','Toko\KelompokMenuController@store');
    $router->put('menu-klp','Toko\KelompokMenuController@update');
    $router->delete('menu-klp','Toko\KelompokMenuController@destroy');

    //Unit
    $router->get('unit','Toko\UnitController@index');
    $router->post('unit','Toko\UnitController@store');
    $router->put('unit','Toko\UnitController@update');
    $router->delete('unit','Toko\UnitController@destroy');

    //Master Masakun
    $router->get('masakun','Toko\MasakunController@index');
    $router->get('masakun-detail','Toko\MasakunController@show');
    $router->post('masakun','Toko\MasakunController@store');
    $router->put('masakun','Toko\MasakunController@update');
    $router->delete('masakun','Toko\MasakunController@destroy');
    $router->get('masakun-curr','Toko\MasakunController@getCurrency');
    $router->get('masakun-modul','Toko\MasakunController@getModul');

    //Master Ref Trans
    $router->get('reftrans-kode','Toko\ReferensiTransController@generateKodeByJenis');
    $router->get('reftrans','Toko\ReferensiTransController@index');
    $router->get('reftrans-detail','Toko\ReferensiTransController@show');
    $router->post('reftrans','Toko\ReferensiTransController@store');
    $router->put('reftrans','Toko\ReferensiTransController@update');
    $router->delete('reftrans','Toko\ReferensiTransController@destroy');

    
    //Format Laporan
    $router->get('format-laporan','Toko\FormatLaporanController@show');
    $router->post('format-laporan','Toko\FormatLaporanController@store');
    $router->put('format-laporan','Toko\FormatLaporanController@update');
    $router->delete('format-laporan','Toko\FormatLaporanController@destroy');
    $router->get('format-laporan-versi','Toko\FormatLaporanController@getVersi');
    $router->get('format-laporan-tipe','Toko\FormatLaporanController@getTipe');
    $router->get('format-laporan-relakun','Toko\FormatLaporanController@getRelakun');
    $router->post('format-laporan-relasi','Toko\FormatLaporanController@simpanRelasi');
    $router->post('format-laporan-move','Toko\FormatLaporanController@simpanMove');

    
    //fs
    $router->get('listFSAktif','Toko\FSController@listFSAktif');         
    $router->get('cariFSAktif','Toko\FSController@cariFSAktif');
    $router->get('fs','Toko\FSController@index');
    $router->post('fs','Toko\FSController@store');
    $router->put('fs','Toko\FSController@update');
    $router->delete('fs','Toko\FSController@destroy'); 

    //flagakun
    $router->get('cariFlag','Toko\FlagAkunController@cariFlag');
    $router->get('flagakun','Toko\FlagAkunController@index');
    $router->post('flagakun','Toko\FlagAkunController@store');
    $router->put('flagakun','Toko\FlagAkunController@update');
    $router->delete('flagakun','Toko\FlagAkunController@destroy'); 
    
    //flagrelasi
    $router->get('flagrelasi','Toko\FlagRelasiController@getFlag');
    $router->get('flagrelasi/{kode_flag}','Toko\FlagRelasiController@getAkunFlag');
    $router->get('flagrelasi-akun','Toko\FlagRelasiController@getAkun');    
    $router->get('flagrelasi-cari','Toko\FlagRelasiController@cariAkunFlag');    
    $router->put('flagrelasi','Toko\FlagRelasiController@update');
    $router->delete('flagrelasi','Toko\FlagRelasiController@destroy'); 

    // ASET
    //Data Akun Aktiva Tetap
    $router->get('fa-klpakun','Toko\FaKlpAkunController@index');
    $router->post('fa-klpakun','Toko\FaKlpAkunController@store');
    $router->put('fa-klpakun','Toko\FaKlpAkunController@update');
    $router->delete('fa-klpakun','Toko\FaKlpAkunController@destroy');

    //Data Kelompok Barang
    $router->get('klp-barang','Toko\KlpBarangController@index');
    $router->post('klp-barang','Toko\KlpBarangController@store');
    $router->put('klp-barang','Toko\KlpBarangController@update');
    $router->delete('klp-barang','Toko\KlpBarangController@destroy');

    //Data Periode Aktif
    $router->get('periode-aktif','Toko\PeriodeAktifController@index');
    $router->post('periode-aktif','Toko\PeriodeAktifController@store');


});



?>