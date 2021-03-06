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
    $router->get('generate-mutasi','Esaku\Inventori\MutasiController@handleNoBukti');
    $router->get('filter-barang-mutasi','Esaku\Inventori\FilterController@getFilterBarangMutasi');
    $router->get('filter-bukti-mutasi-kirim','Esaku\Inventori\FilterController@getFilterBuktiMutasiKirim');
    $router->get('filter-bukti-mutasi-terima','Esaku\Inventori\FilterController@getFilterBuktiMutasiTerima');
    $router->get('barang-mutasi-detail','Esaku\Inventori\MutasiController@getDetailBarangMutasi');
    $router->get('barang-mutasi-kirim','Esaku\Inventori\MutasiController@getDataBarangMutasiKirim');
    $router->get('mutasi-terima','Esaku\Inventori\MutasiController@getDataMutasiTerima');
    $router->get('mutasi-kirim','Esaku\Inventori\MutasiController@getDataMutasiKirim');
    $router->get('mutasi-detail','Esaku\Inventori\MutasiController@getMutasiDetail');
    $router->post('mutasi-barang','Esaku\Inventori\MutasiController@store');
    $router->put('mutasi-barang','Esaku\Inventori\MutasiController@update');
    $router->delete('mutasi-barang','Esaku\Inventori\MutasiController@destroy');
    //Penjualan (POS)
    $router->get('penjualan-open','Esaku\Inventori\PenjualanController@getNoOpen');
    $router->post('penjualan','Esaku\Inventori\PenjualanController@store');
    $router->get('penjualan-nota','Esaku\Inventori\PenjualanController@getNota');
    $router->get('penjualan-bonus','Esaku\Inventori\PenjualanController@cekBonus');

    //Open Kasir
    $router->get('open-kasir','Esaku\Inventori\OpenKasirController@index');
    $router->post('open-kasir','Esaku\Inventori\OpenKasirController@store');
    $router->put('open-kasir','Esaku\Inventori\OpenKasirController@update');
    $router->delete('open-kasir','Esaku\Inventori\OpenKasirController@destroy');

    //Close Kasir
    $router->get('close-kasir-new','Esaku\Inventori\CloseKasirController@getOpenKasir');
    $router->get('close-kasir-finish','Esaku\Inventori\CloseKasirController@index');
    $router->get('close-kasir-detail','Esaku\Inventori\CloseKasirController@show');
    $router->post('close-kasir','Esaku\Inventori\CloseKasirController@store');

    //Pembelian
    $router->get('pembelian','Esaku\Inventori\PembelianController@index');
    $router->get('pembelian-detail','Esaku\Inventori\PembelianController@show');
    $router->post('pembelian','Esaku\Inventori\PembelianController@store');
    $router->put('pembelian','Esaku\Inventori\PembelianController@update');
    $router->delete('pembelian','Esaku\Inventori\PembelianController@destroy');
    $router->get('pembelian-nota','Esaku\Inventori\PembelianController@getNota');
    $router->get('pembelian-barang','Esaku\Inventori\PembelianController@getBarang');

    //Retur Pembelian
    $router->get('retur-beli-new','Esaku\Inventori\ReturPembelianController@getNew');
    $router->get('retur-beli-finish','Esaku\Inventori\ReturPembelianController@index');
    $router->get('retur-beli-detail','Esaku\Inventori\ReturPembelianController@show');
    $router->post('retur-beli','Esaku\Inventori\ReturPembelianController@store');
    $router->delete('retur-beli','Esaku\Inventori\ReturPembelianController@destroy');
    $router->get('retur-beli-barang','Esaku\Inventori\ReturPembelianController@getBarang');

    //Stok Opname
    $router->get('stok-opname', 'Esaku\Inventori\StockOpnameController@index');
    $router->get('stok-opname-exec', 'Esaku\Inventori\StockOpnameController@execSP');
    $router->get('stok-opname-load', 'Esaku\Inventori\StockOpnameController@load');
    $router->post('upload-barang-fisik', 'Esaku\Inventori\StockOpnameController@importExcel');
    $router->post('stok-opname-rekon', 'Esaku\Inventori\StockOpnameController@simpanRekon');
    $router->post('stok-opname', 'Esaku\Inventori\StockOpnameController@store');
    $router->get('stok-opname-edit', 'Esaku\Inventori\StockOpnameController@show');
    $router->put('stok-opname', 'Esaku\Inventori\StockOpnameController@update');
    $router->delete('stok-opname', 'Esaku\Inventori\StockOpnameController@destroy');

    //Penjualan (OL pesan)
    $router->post('penjualan-langsung','Esaku\Inventori\PenjualanLangsungController@store');
    $router->get('penjualan-langsung-nota','Esaku\Inventori\PenjualanLangsungController@getNota');
    $router->get('penjualan-langsung-bonus','Esaku\Inventori\PenjualanLangsungController@cekBonus');
    $router->get('provinsi','Esaku\Inventori\PenjualanLangsungController@getProvinsi');
    $router->get('kota','Esaku\Inventori\PenjualanLangsungController@getKota');
    $router->get('kecamatan','Esaku\Inventori\PenjualanLangsungController@getKecamatan');
    $router->get('nilai-ongkir','Esaku\Inventori\PenjualanLangsungController@getService');

    //Barcode pesan
    $router->get('barcode-load','Esaku\Inventori\BarcodeController@loadData');
    $router->get('periode','Esaku\Inventori\BarcodeController@getPeriode');
    $router->post('barcode','Esaku\Inventori\BarcodeController@store');

    
    $router->get('sync-master','Esaku\Inventori\Sync2Controller@getSyncMaster');
    $router->post('sync-master','Esaku\Inventori\Sync2Controller@syncMaster');
    
    $router->get('sync-pnj','Esaku\Inventori\Sync2Controller@getSyncPnj');
    $router->get('sync-pnj-detail','Esaku\Inventori\Sync2Controller@getSyncPnjDetail');
    $router->get('load-sync-pnj','Esaku\Inventori\Sync2Controller@loadSyncPnj');
    
    $router->get('sync-pmb','Esaku\Inventori\Sync2Controller@getSyncPmb');
    $router->get('sync-pmb-detail','Esaku\Inventori\Sync2Controller@getSyncPmbDetail');
    $router->get('load-sync-pmb','Esaku\Inventori\Sync2Controller@loadSyncPmb');

    $router->get('sync-retur-beli','Esaku\Inventori\Sync2Controller@getSyncReturBeli');
    $router->get('sync-retur-beli-detail','Esaku\Inventori\Sync2Controller@getSyncReturBeliDetail');
    $router->get('load-sync-retur-beli','Esaku\Inventori\Sync2Controller@loadSyncReturBeli');

    // KEUANGAN
    $router->get('jurnal','Esaku\Keuangan\JurnalController@index');
    $router->get('jurnal-detail','Esaku\Keuangan\JurnalController@show');
    $router->post('jurnal','Esaku\Keuangan\JurnalController@store');
    $router->post('jurnal-ubah','Esaku\Keuangan\JurnalController@update');
    $router->delete('jurnal','Esaku\Keuangan\JurnalController@destroy');
    $router->get('pp-list','Esaku\Keuangan\JurnalController@getPP');
    $router->get('akun','Esaku\Keuangan\JurnalController@getAkun');
    $router->get('nikperiksa','Esaku\Keuangan\JurnalController@getNIKPeriksa');
    $router->get('nikperiksa/{nik}','Esaku\Keuangan\JurnalController@getNIKPeriksaByNIK');
    $router->get('jurnal-periode','Esaku\Keuangan\JurnalController@getPeriodeJurnal');
    $router->post('import-excel','Esaku\Keuangan\JurnalController@importExcel');
    $router->get('jurnal-tmp','Esaku\Keuangan\JurnalController@getJurnalTmp');

    $router->post('posting-jurnal','Esaku\Keuangan\PostingController@loadData');
    $router->get('modul2','Esaku\Keuangan\PostingController@getModul');
    $router->post('posting','Esaku\Keuangan\PostingController@store');

    $router->post('unposting-jurnal','Esaku\Keuangan\UnPostingController@loadData');
    $router->post('unposting','Esaku\Keuangan\UnPostingController@store');

    // Jurnal Dok 
    $router->get('jurnal-dok','Esaku\Keuangan\JurnalDokController@show');
    $router->post('jurnal-dok','Esaku\Keuangan\JurnalDokController@store');
    $router->delete('jurnal-dok','Esaku\Keuangan\JurnalDokController@destroy');

    //Closing Periode
    $router->get('closing-periode','Esaku\Keuangan\ClosingPeriodeController@show');
    $router->post('closing-periode','Esaku\Keuangan\ClosingPeriodeController@store');

    //Jurnal Penutup
    $router->get('jurnal-penutup-list','Esaku\Keuangan\JurnalPenutupController@index');
    $router->get('jurnal-penutup','Esaku\Keuangan\JurnalPenutupController@getDataAwal');
    $router->post('jurnal-penutup','Esaku\Keuangan\JurnalPenutupController@store');

    // UPLOAD SAWAL
    $router->get('sawal','Esaku\Keuangan\SawalController@index');
    $router->post('sawal','Esaku\Keuangan\SawalController@store');
    $router->post('sawal-import','Esaku\Keuangan\SawalController@importExcel');
    $router->get('sawal-tmp','Esaku\Keuangan\SawalController@getSawalTmp');

    // UPLOAD JURNAL
    $router->get('jurnal-upload','Esaku\Keuangan\JurnalUploadController@index');
    $router->post('jurnal-upload','Esaku\Keuangan\JurnalUploadController@store');
    $router->post('jurnal-upload-import','Esaku\Keuangan\JurnalUploadController@importExcel');
    $router->get('jurnal-upload-tmp','Esaku\Keuangan\JurnalUploadController@getJurnalUploadTmp');

    // UPLOAD AKUN
    $router->get('akun','Esaku\Keuangan\AkunController@index');
    $router->post('akun','Esaku\Keuangan\AkunController@store');
    $router->post('akun-import','Esaku\Keuangan\AkunController@importExcel');
    $router->get('akun-tmp','Esaku\Keuangan\AkunController@getAkunTmp');


    // AKTAP
    //Data Aktiva Tetap
    $router->get('aktap-klpakun','Esaku\Aktap\AktapController@getKlpAkun');
    $router->post('aktap','Esaku\Aktap\AktapController@store');

    //Data Penyusutan
    $router->get('susut-drk','Esaku\Aktap\PenyusutanController@getDRK');
    $router->post('susut','Esaku\Aktap\PenyusutanController@store');

    // KASBANK
    //PEMASUKAN PENGELUARAN PINBOOK
    $router->get('kbdual','Esaku\KasBank\KasBankDualController@index');
    $router->get('kbdual-detail','Esaku\KasBank\KasBankDualController@show');
    $router->post('kbdual','Esaku\KasBank\KasBankDualController@store');
    $router->put('kbdual','Esaku\KasBank\KasBankDualController@update');
    $router->delete('kbdual','Esaku\KasBank\KasBankDualController@destroy');

    // KAS BANK
    $router->get('kas-bank','Esaku\KasBank\KasBankController@index');
    $router->get('kas-bank-detail','Esaku\KasBank\KasBankController@show');
    $router->post('kas-bank','Esaku\KasBank\KasBankController@store');
    $router->put('kas-bank','Esaku\KasBank\KasBankController@update');
    $router->delete('kas-bank','Esaku\KasBank\KasBankController@destroy');
    $router->post('kas-bank-import-excel','Esaku\KasBank\KasBankController@importExcel');
    $router->get('kas-bank-tmp','Esaku\KasBank\KasBankController@getDataTmp');

    // UANG MASUK
    
    $router->get('uang-masuk','Esaku\KasBank\UangMasukController@index');
    $router->get('uang-masuk-detail','Esaku\KasBank\UangMasukController@show');
    $router->post('uang-masuk','Esaku\KasBank\UangMasukController@store');
    $router->post('uang-masuk-ubah','Esaku\KasBank\UangMasukController@update');
    $router->delete('uang-masuk','Esaku\KasBank\UangMasukController@destroy');
    $router->post('uang-masuk-import-excel','Esaku\KasBank\UangMasukController@importExcel');
    $router->get('uang-masuk-tmp','Esaku\KasBank\UangMasukController@getDataTmp');
    
    $router->get('uang-keluar','Esaku\KasBank\UangKeluarController@index');
    $router->get('uang-keluar-detail','Esaku\KasBank\UangKeluarController@show');
    $router->post('uang-keluar','Esaku\KasBank\UangKeluarController@store');
    $router->post('uang-keluar-ubah','Esaku\KasBank\UangKeluarController@update');
    $router->delete('uang-keluar','Esaku\KasBank\UangKeluarController@destroy');
    $router->post('uang-keluar-import-excel','Esaku\KasBank\UangKeluarController@importExcel');
    $router->get('uang-keluar-tmp','Esaku\KasBank\UangKeluarController@getDataTmp');
    
    $router->get('terima-dari','Esaku\KasBank\UangMasukController@getTerimaDari');
    $router->get('akun-terima','Esaku\KasBank\UangMasukController@getAkunTerima');

    $router->get('kas-bank-dok','Esaku\KasBank\KasBankDokController@show');
    $router->post('kas-bank-dok','Esaku\KasBank\KasBankDokController@store');
    $router->delete('kas-bank-dok','Esaku\KasBank\KasBankDokController@destroy');

    // ANGGARAN
    $router->get('tahun','Esaku\Anggaran\AnggaranController@getTahun'); 
    $router->get('anggaran','Esaku\Anggaran\AnggaranController@index');          
    $router->post('anggaran-upload','Esaku\Anggaran\AnggaranController@importExcel'); 
    $router->get('anggaran-load','Esaku\Anggaran\AnggaranController@loadAnggaran');    
    $router->post('anggaran','Esaku\Anggaran\AnggaranController@store');  

    // RRA AJU
    $router->get('rra-agg-drk','Esaku\Anggaran\PengajuanRRAController@getDRK'); 
    $router->get('rra-agg-saldo','Esaku\Anggaran\PengajuanRRAController@getSaldo'); 
    $router->get('rra-agg','Esaku\Anggaran\PengajuanRRAController@index');          
    $router->post('rra-agg','Esaku\Anggaran\PengajuanRRAController@store'); 
    $router->put('rra-agg','Esaku\Anggaran\PengajuanRRAController@update');    
    $router->delete('rra-agg','Esaku\Anggaran\PengajuanRRAController@destroy'); 
    $router->get('rra-nik-app','Esaku\Anggaran\PengajuanRRAController@getNIKApp'); 
    $router->get('rra-pp-terima','Esaku\Anggaran\PengajuanRRAController@getPPTerima');
    $router->get('rra-akun-terima','Esaku\Anggaran\PengajuanRRAController@getAkunTerima');
    $router->get('rra-drk-terima','Esaku\Anggaran\PengajuanRRAController@getDRKTerima'); 

    $router->post('send-whatsapp','Esaku\Setting\WAController@sendMessage'); 
    $router->get('msg-whatsapp','Esaku\Setting\WAController@Messages'); 
    $router->post('pooling','Esaku\Setting\WAController@storePooling'); 
    $router->post('jurnal-notifikasi','Esaku\Keuangan\JurnalController@sendNotifikasi'); 

});

$router->get('anggaran-export','Esaku\Anggaran\AnggaranController@export');    

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    
    $router->get('load-sync-master','Esaku\Inventori\Sync2Controller@loadSyncMaster');
    $router->post('sync-pnj','Esaku\Inventori\Sync2Controller@syncPnj');
    $router->post('sync-pmb','Esaku\Inventori\Sync2Controller@syncPmb');
    $router->post('sync-retur-beli','Esaku\Inventori\Sync2Controller@syncReturBeli');
});

$router->get('export', 'Esaku\Keuangan\JurnalController@export');
$router->get('sawal-export', 'Esaku\Keuangan\SawalController@export');
$router->get('jurnal-upload-export', 'Esaku\Keuangan\JurnalUploadController@export');
$router->get('akun-export', 'Esaku\Keuangan\AkunController@export');



?>