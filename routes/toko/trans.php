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
    //Penjualan (POS)
    $router->get('penjualan-open','Toko\PenjualanController@getNoOpen');
    $router->post('penjualan','Toko\PenjualanController@store');
    $router->get('penjualan-nota','Toko\PenjualanController@getNota');
    $router->get('penjualan-bonus','Toko\PenjualanController@cekBonus');

    //Open Kasir
    $router->get('open-kasir','Toko\OpenKasirController@index');
    $router->post('open-kasir','Toko\OpenKasirController@store');
    $router->put('open-kasir','Toko\OpenKasirController@update');
    
    //Close Kasir
    $router->get('close-kasir-new','Toko\CloseKasirController@getOpenKasir');
    $router->get('close-kasir-finish','Toko\CloseKasirController@index');
    $router->get('close-kasir-detail','Toko\CloseKasirController@show');
    $router->post('close-kasir','Toko\CloseKasirController@store');

    //Pembelian
    $router->get('pembelian','Toko\PembelianController@index');
    $router->get('pembelian-detail','Toko\PembelianController@show');
    $router->post('pembelian','Toko\PembelianController@store');
    $router->put('pembelian','Toko\PembelianController@update');
    $router->delete('pembelian','Toko\PembelianController@destroy');
    $router->get('pembelian-nota','Toko\PembelianController@getNota');
    $router->get('pembelian-barang','Toko\PembelianController@getBarang');

    //Retur Pembelian
    $router->get('retur-beli-new','Toko\ReturPembelianController@getNew');
    $router->get('retur-beli-finish','Toko\ReturPembelianController@index');
    $router->get('retur-beli-detail','Toko\ReturPembelianController@show');
    $router->post('retur-beli','Toko\ReturPembelianController@store');
    $router->get('retur-beli-barang','Toko\ReturPembelianController@getBarang');

    //Stok Opname
    $router->get('stok-opname', 'Toko\StockOpnameController@index');
    $router->post('stok-opname-load', 'Toko\StockOpnameController@load');
    $router->post('upload-barang-fisik', 'Toko\StockOpnameController@importExcel');
    $router->post('stok-opname-rekon', 'Toko\StockOpnameController@simpanRekon');
    $router->post('stok-opname', 'Toko\StockOpnameController@store');
    $router->get('stok-opname-edit', 'Toko\StockOpnameController@show');
    $router->put('stok-opname', 'Toko\StockOpnameController@update');
    $router->delete('stok-opname', 'Toko\StockOpnameController@destroy');

    //PEMASUKAN PENGELUARAN PINBOOK
    $router->get('kbdual','Toko\KasBankDualController@index');
    $router->get('kbdual-detail','Toko\KasBankDualController@show');
    $router->post('kbdual','Toko\KasBankDualController@store');
    $router->put('kbdual','Toko\KasBankDualController@update');
    $router->delete('kbdual','Toko\KasBankDualController@destroy');

    //Penjualan (OL pesan)
    $router->post('penjualan-langsung','Toko\PenjualanLangsungController@store');
    $router->get('penjualan-langsung-nota','Toko\PenjualanLangsungController@getNota');
    $router->get('penjualan-langsung-bonus','Toko\PenjualanLangsungController@cekBonus');
    $router->get('provinsi','Toko\PenjualanLangsungController@getProvinsi');
    $router->get('kota','Toko\PenjualanLangsungController@getKota');
    $router->get('kecamatan','Toko\PenjualanLangsungController@getKecamatan');
    $router->get('nilai-ongkir','Toko\PenjualanLangsungController@getService');

    //Barcode pesan
    $router->get('barcode-load','Toko\BarcodeController@loadData');
    $router->get('periode','Toko\BarcodeController@getPeriode');
    $router->post('barcode','Toko\BarcodeController@store');

    $router->get('jurnal','Toko\JurnalController@index');
    $router->get('jurnal/{no_bukti}','Toko\JurnalController@show');
    $router->post('jurnal','Toko\JurnalController@store');
    $router->put('jurnal','Toko\JurnalController@update');
    $router->delete('jurnal/{no_bukti}','Toko\JurnalController@destroy');
    $router->get('pp-list','Toko\JurnalController@getPP');
    $router->get('akun','Toko\JurnalController@getAkun');
    $router->get('nikperiksa','Toko\JurnalController@getNIKPeriksa');
    $router->get('nikperiksa/{nik}','Toko\JurnalController@getNIKPeriksaByNIK');
    $router->get('jurnal-periode','Toko\JurnalController@getPeriodeJurnal');
    $router->post('import-excel','Toko\JurnalController@importExcel');
    $router->get('jurnal-tmp','Toko\JurnalController@getJurnalTmp');

    $router->get('sync-master','Toko\Sync2Controller@getSyncMaster');
    $router->post('sync-master','Toko\Sync2Controller@syncMaster');
    
    $router->get('sync-pnj','Toko\Sync2Controller@getSyncPnj');
    $router->get('sync-pnj-detail','Toko\Sync2Controller@getSyncPnjDetail');
    $router->get('load-sync-pnj','Toko\Sync2Controller@loadSyncPnj');
    
    $router->get('sync-pmb','Toko\Sync2Controller@getSyncPmb');
    $router->get('sync-pmb-detail','Toko\Sync2Controller@getSyncPmbDetail');
    $router->get('load-sync-pmb','Toko\Sync2Controller@loadSyncPmb');

});

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    
    $router->get('load-sync-master','Toko\Sync2Controller@loadSyncMaster');
    $router->post('sync-pnj','Toko\Sync2Controller@syncPnj');
    $router->post('sync-pmb','Toko\Sync2Controller@syncPmb');
});

$router->get('export', 'Toko\JurnalController@export');



?>