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
    //Mutasi Barang//
    $router->get('generate-mutasi','Toko\MutasiController@handleNoBukti');
    $router->get('filter-barang-mutasi','Toko\FilterController@getFilterBarangMutasi');
    $router->get('filter-bukti-mutasi-kirim','Toko\FilterController@getFilterBuktiMutasiKirim');
    $router->get('filter-bukti-mutasi-terima','Toko\FilterController@getFilterBuktiMutasiTerima');
    $router->get('barang-mutasi-detail','Toko\MutasiController@getDetailBarangMutasi');
    $router->get('barang-mutasi-kirim','Toko\MutasiController@getDataBarangMutasiKirim');
    $router->get('mutasi-terima','Toko\MutasiController@getDataMutasiTerima');
    $router->get('mutasi-kirim','Toko\MutasiController@getDataMutasiKirim');
    $router->get('mutasi-detail','Toko\MutasiController@getMutasiDetail');
    $router->post('mutasi-barang','Toko\MutasiController@store');
    $router->put('mutasi-barang','Toko\MutasiController@update');
    $router->delete('mutasi-barang','Toko\MutasiController@destroy');
    //Penjualan (POS)
    $router->get('penjualan-open','Toko\PenjualanController@getNoOpen');
    $router->post('penjualan','Toko\PenjualanController@store');
    $router->get('penjualan-nota','Toko\PenjualanController@getNota');
    $router->get('penjualan-bonus','Toko\PenjualanController@cekBonus');

    //Open Kasir
    $router->get('open-kasir','Toko\OpenKasirController@index');
    $router->post('open-kasir','Toko\OpenKasirController@store');
    $router->put('open-kasir','Toko\OpenKasirController@update');
    $router->delete('open-kasir','Toko\OpenKasirController@destroy');

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
    $router->delete('retur-beli','Toko\ReturPembelianController@destroy');
    $router->get('retur-beli-barang','Toko\ReturPembelianController@getBarang');

    //Stok Opname
    $router->get('stok-opname', 'Toko\StockOpnameController@index');
    $router->get('stok-opname-exec', 'Toko\StockOpnameController@execSP');
    $router->get('stok-opname-load', 'Toko\StockOpnameController@load');
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
    $router->get('jurnal-detail','Toko\JurnalController@show');
    $router->post('jurnal','Toko\JurnalController@store');
    $router->post('jurnal-ubah','Toko\JurnalController@update');
    $router->delete('jurnal','Toko\JurnalController@destroy');
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

    $router->get('sync-retur-beli','Toko\Sync2Controller@getSyncReturBeli');
    $router->get('sync-retur-beli-detail','Toko\Sync2Controller@getSyncReturBeliDetail');
    $router->get('load-sync-retur-beli','Toko\Sync2Controller@loadSyncReturBeli');

    $router->post('posting-jurnal','Toko\PostingController@loadData');
    $router->get('modul2','Toko\PostingController@getModul');
    $router->post('posting','Toko\PostingController@store');

    $router->post('unposting-jurnal','Toko\UnPostingController@loadData');
    $router->post('unposting','Toko\UnPostingController@store');

    //Data Aktiva Tetap
    $router->get('aktap-klpakun','Toko\AktapController@getKlpAkun');
    $router->post('aktap','Toko\AktapController@store');

    //Data Penyusutan
    $router->get('susut-drk','Toko\PenyusutanController@getDRK');
    $router->post('susut','Toko\PenyusutanController@store');

    // Jurnal Dok 
    $router->get('jurnal-dok','Toko\JurnalDokController@show');
    $router->post('jurnal-dok','Toko\JurnalDokController@store');
    $router->delete('jurnal-dok','Toko\JurnalDokController@destroy');

    //Closing Periode
    $router->get('closing-periode','Toko\ClosingPeriodeController@show');
    $router->post('closing-periode','Toko\ClosingPeriodeController@store');

    //Jurnal Penutup
    $router->get('jurnal-penutup-list','Toko\JurnalPenutupController@index');
    $router->get('jurnal-penutup','Toko\JurnalPenutupController@getDataAwal');
    $router->post('jurnal-penutup','Toko\JurnalPenutupController@store');

    // UPLOAD SAWAL
    $router->get('sawal','Toko\SawalController@index');
    $router->post('sawal','Toko\SawalController@store');
    $router->post('sawal-import','Toko\SawalController@importExcel');
    $router->get('sawal-tmp','Toko\SawalController@getSawalTmp');

    // UPLOAD JURNAL
    $router->get('jurnal-upload','Toko\JurnalUploadController@index');
    $router->post('jurnal-upload','Toko\JurnalUploadController@store');
    $router->post('jurnal-upload-import','Toko\JurnalUploadController@importExcel');
    $router->get('jurnal-upload-tmp','Toko\JurnalUploadController@getJurnalUploadTmp');

    // KAS BANK
    $router->get('kas-bank','Toko\KasBankController@index');
    $router->get('kas-bank-detail','Toko\KasBankController@show');
    $router->post('kas-bank','Toko\KasBankController@store');
    $router->put('kas-bank','Toko\KasBankController@update');
    $router->delete('kas-bank','Toko\KasBankController@destroy');
    $router->post('kas-bank-import-excel','Toko\KasBankController@importExcel');
    $router->get('kas-bank-tmp','Toko\KasBankController@getDataTmp');

    $router->get('kas-bank-dok','Toko\KasBankDokController@show');
    $router->post('kas-bank-dok','Toko\KasBankDokController@store');
    $router->delete('kas-bank-dok','Toko\KasBankDokController@destroy');

    // ANGGARAN
    $router->get('tahun','Toko\AnggaranController@getTahun'); 
    $router->get('anggaran','Toko\AnggaranController@index');          
    $router->post('anggaran-upload','Toko\AnggaranController@importExcel'); 
    $router->get('anggaran-load','Toko\AnggaranController@loadAnggaran');    
    $router->post('anggaran','Toko\AnggaranController@store');  


});

$router->get('anggaran-export','Toko\AnggaranController@export');    

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    
    $router->get('load-sync-master','Toko\Sync2Controller@loadSyncMaster');
    $router->post('sync-pnj','Toko\Sync2Controller@syncPnj');
    $router->post('sync-pmb','Toko\Sync2Controller@syncPmb');
    $router->post('sync-retur-beli','Toko\Sync2Controller@syncReturBeli');
});

$router->get('export', 'Toko\JurnalController@export');
$router->get('sawal-export', 'Toko\SawalController@export');
$router->get('jurnal-upload-export', 'Toko\JurnalUploadController@export');



?>