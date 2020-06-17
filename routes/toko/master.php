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

    //ADMIN
    //Menu
    $router->get('menu','Toko\MenuController@index');
    $router->post('menu','Toko\MenuController@store');
    $router->put('menu','Toko\MenuController@update');
    $router->delete('menu','Toko\MenuController@destroy');
    $router->get('menu-klp','Toko\MenuController@getKlp');

    //Akses User
    $router->get('akses-user','Toko\HakaksesController@index');
    $router->post('akses-user','Toko\HakaksesController@store');
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

});



?>