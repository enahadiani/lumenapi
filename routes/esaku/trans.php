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
    //Mutasi Barang//
    $router->get('generate-mutasi', 'Esaku\Inventori\MutasiController@handleNoBukti');
    $router->get('filter-barang-mutasi', 'Esaku\Inventori\FilterController@getFilterBarangMutasi');
    $router->get('filter-bukti-mutasi-kirim', 'Esaku\Inventori\FilterController@getFilterBuktiMutasiKirim');
    $router->get('filter-bukti-mutasi-terima', 'Esaku\Inventori\FilterController@getFilterBuktiMutasiTerima');
    $router->get('barang-mutasi-detail', 'Esaku\Inventori\MutasiController@getDetailBarangMutasi');
    $router->get('barang-mutasi-kirim', 'Esaku\Inventori\MutasiController@getDataBarangMutasiKirim');
    $router->get('mutasi-terima', 'Esaku\Inventori\MutasiController@getDataMutasiTerima');
    $router->get('mutasi-kirim', 'Esaku\Inventori\MutasiController@getDataMutasiKirim');
    $router->get('mutasi-detail', 'Esaku\Inventori\MutasiController@getMutasiDetail');
    $router->post('mutasi-barang', 'Esaku\Inventori\MutasiController@store');
    $router->put('mutasi-barang', 'Esaku\Inventori\MutasiController@update');
    $router->delete('mutasi-barang', 'Esaku\Inventori\MutasiController@destroy');
    //Penjualan (POS)
    $router->get('penjualan-open', 'Esaku\Inventori\PenjualanController@getNoOpen');
    $router->post('penjualan', 'Esaku\Inventori\PenjualanController@store');
    $router->post('penjualan-ubah', 'Esaku\Inventori\PenjualanController@update');
    $router->get('penjualan-nota', 'Esaku\Inventori\PenjualanController@getNota');
    $router->get('penjualan-bonus', 'Esaku\Inventori\PenjualanController@cekBonus');

    //Open Kasir
    $router->get('open-kasir', 'Esaku\Inventori\OpenKasirController@index');
    $router->post('open-kasir', 'Esaku\Inventori\OpenKasirController@store');
    $router->put('open-kasir', 'Esaku\Inventori\OpenKasirController@update');
    $router->delete('open-kasir', 'Esaku\Inventori\OpenKasirController@destroy');

    //Close Kasir
    $router->get('close-kasir-new', 'Esaku\Inventori\CloseKasirController@getOpenKasir');
    $router->get('close-kasir-finish', 'Esaku\Inventori\CloseKasirController@index');
    $router->get('close-kasir-detail', 'Esaku\Inventori\CloseKasirController@show');
    $router->post('close-kasir', 'Esaku\Inventori\CloseKasirController@store');

    //Pembelian
    $router->get('pembelian', 'Esaku\Inventori\PembelianController@index');
    $router->get('pembelian-detail', 'Esaku\Inventori\PembelianController@show');
    $router->get('pembelian-detail-tmp', 'Esaku\Inventori\PembelianController@showTmp');
    $router->post('pembelian', 'Esaku\Inventori\PembelianController@store');
    $router->post('pembelian-detail', 'Esaku\Inventori\PembelianController@storeDetail');
    $router->post('pembelian-detail-ubah', 'Esaku\Inventori\PembelianController@updateDetail');
    $router->put('pembelian', 'Esaku\Inventori\PembelianController@update');
    $router->delete('pembelian', 'Esaku\Inventori\PembelianController@destroy');
    $router->get('pembelian-nota', 'Esaku\Inventori\PembelianController@getNota');
    $router->get('pembelian-barang', 'Esaku\Inventori\PembelianController@getBarang');
    $router->delete('pembelian-detail-tmp', 'Esaku\Inventori\PembelianController@clearTmp');
    $router->delete('pembelian-detail', 'Esaku\Inventori\PembelianController@destroyDetail');


    //Pembelian3
    $router->get('pembelian3', 'Esaku\Inventori\Pembelian3Controller@index');
    $router->get('pembelian3-detail', 'Esaku\Inventori\Pembelian3Controller@show');
    $router->post('pembelian3', 'Esaku\Inventori\Pembelian3Controller@store');
    $router->put('pembelian3', 'Esaku\Inventori\Pembelian3Controller@update');
    $router->delete('pembelian3', 'Esaku\Inventori\Pembelian3Controller@destroy');
    $router->get('pembelian3-nota', 'Esaku\Inventori\Pembelian3Controller@getNota');
    $router->get('pembelian3-barang', 'Esaku\Inventori\Pembelian3Controller@getBarang');
    
    //Pembelian Baru
    $router->get('pembelian-baru', 'Esaku\Inventori\PembelianBaruController@index');
    $router->get('pembelian-baru-detail', 'Esaku\Inventori\PembelianBaruController@show');
    $router->post('pembelian-baru', 'Esaku\Inventori\PembelianBaruController@store');
    $router->put('pembelian-baru', 'Esaku\Inventori\PembelianBaruController@update');
    $router->delete('pembelian-baru', 'Esaku\Inventori\PembelianBaruController@destroy');
    $router->get('pembelian-baru-nota', 'Esaku\Inventori\PembelianBaruController@getNota');
    $router->get('pembelian-baru-barang', 'Esaku\Inventori\PembelianBaruController@getBarang');

    //Retur Pembelian
    $router->get('retur-beli-new', 'Esaku\Inventori\ReturPembelianController@getNew');
    $router->get('retur-beli-finish', 'Esaku\Inventori\ReturPembelianController@index');
    $router->get('retur-beli-detail', 'Esaku\Inventori\ReturPembelianController@show');
    $router->post('retur-beli', 'Esaku\Inventori\ReturPembelianController@store');
    $router->delete('retur-beli', 'Esaku\Inventori\ReturPembelianController@destroy');
    $router->get('retur-beli-barang', 'Esaku\Inventori\ReturPembelianController@getBarang');

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

    //Daftar Penjualan
    $router->get('daftar-penjualan', 'Esaku\Inventori\DaftarPenjualanController@index');
    $router->get('daftar-penjualandetail', 'Esaku\Inventori\DaftarPenjualanController@show');

    //Penjualan (OL pesan)
    $router->post('penjualan-langsung', 'Esaku\Inventori\PenjualanLangsungController@store');
    $router->get('penjualan-langsung-nota', 'Esaku\Inventori\PenjualanLangsungController@getNota');
    $router->get('penjualan-langsung-bonus', 'Esaku\Inventori\PenjualanLangsungController@cekBonus');
    $router->get('provinsi', 'Esaku\Inventori\PenjualanLangsungController@getProvinsi');
    $router->get('kota', 'Esaku\Inventori\PenjualanLangsungController@getKota');
    $router->get('kecamatan', 'Esaku\Inventori\PenjualanLangsungController@getKecamatan');
    $router->get('nilai-ongkir', 'Esaku\Inventori\PenjualanLangsungController@getService');

    //Barcode pesan
    $router->get('barcode-load', 'Esaku\Inventori\BarcodeController@loadData');
    $router->get('periode', 'Esaku\Inventori\BarcodeController@getPeriode');
    $router->post('barcode', 'Esaku\Inventori\BarcodeController@store');


    $router->get('sync-master', 'Esaku\Inventori\Sync2Controller@getSyncMaster');
    $router->post('sync-master', 'Esaku\Inventori\Sync2Controller@syncMaster');

    $router->get('sync-pnj', 'Esaku\Inventori\Sync2Controller@getSyncPnj');
    $router->get('sync-pnj-detail', 'Esaku\Inventori\Sync2Controller@getSyncPnjDetail');
    $router->get('load-sync-pnj', 'Esaku\Inventori\Sync2Controller@loadSyncPnj');
    $router->post('sync-pnj', 'Esaku\Inventori\Sync2Controller@syncPnj');

    $router->get('sync-pmb', 'Esaku\Inventori\Sync2Controller@getSyncPmb');
    $router->get('sync-pmb-detail', 'Esaku\Inventori\Sync2Controller@getSyncPmbDetail');
    $router->get('load-sync-pmb', 'Esaku\Inventori\Sync2Controller@loadSyncPmb');
    $router->post('sync-pmb', 'Esaku\Inventori\Sync2Controller@syncPmb');

    $router->get('sync-retur-beli', 'Esaku\Inventori\Sync2Controller@getSyncReturBeli');
    $router->get('sync-retur-beli-detail', 'Esaku\Inventori\Sync2Controller@getSyncReturBeliDetail');
    $router->get('load-sync-retur-beli', 'Esaku\Inventori\Sync2Controller@loadSyncReturBeli');
    $router->post('sync-retur-beli', 'Esaku\Inventori\Sync2Controller@syncReturBeli');

    // KEUANGAN
    $router->get('jurnal', 'Esaku\Keuangan\JurnalController@index');
    $router->get('jurnal-detail', 'Esaku\Keuangan\JurnalController@show');
    $router->post('jurnal', 'Esaku\Keuangan\JurnalController@store');
    $router->post('jurnal-ubah', 'Esaku\Keuangan\JurnalController@update');
    $router->delete('jurnal', 'Esaku\Keuangan\JurnalController@destroy');
    $router->get('pp-list', 'Esaku\Keuangan\JurnalController@getPP');
    $router->get('akun', 'Esaku\Keuangan\JurnalController@getAkun');
    $router->get('nikperiksa', 'Esaku\Keuangan\JurnalController@getNIKPeriksa');
    $router->get('nikperiksa/{nik}', 'Esaku\Keuangan\JurnalController@getNIKPeriksaByNIK');
    $router->get('jurnal-periode', 'Esaku\Keuangan\JurnalController@getPeriodeJurnal');
    $router->post('import-excel', 'Esaku\Keuangan\JurnalController@importExcel');
    $router->get('jurnal-tmp', 'Esaku\Keuangan\JurnalController@getJurnalTmp');

    $router->post('posting-jurnal', 'Esaku\Keuangan\PostingController@loadData');
    $router->get('modul2', 'Esaku\Keuangan\PostingController@getModul');
    $router->post('posting', 'Esaku\Keuangan\PostingController@store');

    $router->post('unposting-jurnal', 'Esaku\Keuangan\UnPostingController@loadData');
    $router->post('unposting', 'Esaku\Keuangan\UnPostingController@store');

    // Jurnal Dok
    $router->get('jurnal-dok', 'Esaku\Keuangan\JurnalDokController@show');
    $router->post('jurnal-dok', 'Esaku\Keuangan\JurnalDokController@store');
    $router->delete('jurnal-dok', 'Esaku\Keuangan\JurnalDokController@destroy');

    //Closing Periode
    $router->get('closing-periode', 'Esaku\Keuangan\ClosingPeriodeController@show');
    $router->post('closing-periode', 'Esaku\Keuangan\ClosingPeriodeController@store');

    //Jurnal Penutup
    $router->get('jurnal-penutup-list', 'Esaku\Keuangan\JurnalPenutupController@index');
    $router->get('jurnal-penutup', 'Esaku\Keuangan\JurnalPenutupController@getDataAwal');
    $router->post('jurnal-penutup', 'Esaku\Keuangan\JurnalPenutupController@store');

    // UPLOAD SAWAL
    $router->get('sawal', 'Esaku\Keuangan\SawalController@index');
    $router->post('sawal', 'Esaku\Keuangan\SawalController@store');
    $router->post('sawal-import', 'Esaku\Keuangan\SawalController@importExcel');
    $router->get('sawal-tmp', 'Esaku\Keuangan\SawalController@getSawalTmp');

    // UPLOAD JURNAL
    $router->get('jurnal-upload', 'Esaku\Keuangan\JurnalUploadController@index');
    $router->post('jurnal-upload', 'Esaku\Keuangan\JurnalUploadController@store');
    $router->post('jurnal-upload-import', 'Esaku\Keuangan\JurnalUploadController@importExcel');
    $router->get('jurnal-upload-tmp', 'Esaku\Keuangan\JurnalUploadController@getJurnalUploadTmp');

    // UPLOAD AKUN
    $router->get('akun', 'Esaku\Keuangan\AkunController@index');
    $router->post('akun', 'Esaku\Keuangan\AkunController@store');
    $router->post('akun-import', 'Esaku\Keuangan\AkunController@importExcel');
    $router->get('akun-tmp', 'Esaku\Keuangan\AkunController@getAkunTmp');


    // AKTAP
    //Data Aktiva Tetap
    $router->get('aktap-pp', 'Esaku\Aktap\AktapController@getPP');
    $router->get('aktap-klpakun', 'Esaku\Aktap\AktapController@getKlpAkun');
    $router->post('aktap', 'Esaku\Aktap\AktapController@store');

    $router->get('aktap', 'Esaku\Aktap\AktapController@getAktap');
    $router->get('aktap-detail', 'Esaku\Aktap\AktapController@show');
    $router->put('aktap', 'Esaku\Aktap\AktapController@update');
    $router->delete('aktap', 'Esaku\Aktap\AktapController@destroy');

    //Data Penyusutan
    $router->get('susut-hitung', 'Esaku\Aktap\PenyusutanController@hitungPenyusutan');
    $router->post('susut', 'Esaku\Aktap\PenyusutanController@store');

    //Data Percepatan
    $router->get('susutcpt-noaktap', 'Esaku\Aktap\PercepatanController@getNoAktap');
    $router->get('susutcpt-load', 'Esaku\Aktap\PercepatanController@loadDataAktap');
    $router->post('susutcpt', 'Esaku\Aktap\PercepatanController@store');

    //Data Pembatalan
    $router->get('susut-batal-noaktap', 'Esaku\Aktap\PembatalanController@getNoAktap');
    $router->get('susut-batal-load', 'Esaku\Aktap\PembatalanController@loadDataAktap');
    $router->post('susut-batal', 'Esaku\Aktap\PembatalanController@store');

    // Penghapusan
    $router->get('hapus-aktap', 'Esaku\Aktap\PenghapusanController@index');
    $router->get('hapus-aktap-detail', 'Esaku\Aktap\PenghapusanController@show');
    $router->post('hapus-aktap', 'Esaku\Aktap\PenghapusanController@store');
    $router->put('hapus-aktap-ubah', 'Esaku\Aktap\PenghapusanController@update');
    $router->delete('hapus-aktap', 'Esaku\Aktap\PenghapusanController@destroy');
    $router->get('hapus-aktap-noaktap', 'Esaku\Aktap\PenghapusanController@getAktap');
    $router->get('hapus-aktap-akun', 'Esaku\Aktap\PenghapusanController@getAkunBeban');
    $router->get('hapus-aktap-load', 'Esaku\Aktap\PenghapusanController@loadData');

    // KASBANK
    //PEMASUKAN PENGELUARAN PINBOOK
    $router->get('kbdual', 'Esaku\KasBank\KasBankDualController@index');
    $router->get('kbdual-detail', 'Esaku\KasBank\KasBankDualController@show');
    $router->post('kbdual', 'Esaku\KasBank\KasBankDualController@store');
    $router->put('kbdual', 'Esaku\KasBank\KasBankDualController@update');
    $router->delete('kbdual', 'Esaku\KasBank\KasBankDualController@destroy');

    // KAS BANK
    $router->get('kas-bank', 'Esaku\KasBank\KasBankController@index');
    $router->get('kas-bank-detail', 'Esaku\KasBank\KasBankController@show');
    $router->post('kas-bank', 'Esaku\KasBank\KasBankController@store');
    $router->put('kas-bank', 'Esaku\KasBank\KasBankController@update');
    $router->delete('kas-bank', 'Esaku\KasBank\KasBankController@destroy');
    $router->post('kas-bank-import-excel', 'Esaku\KasBank\KasBankController@importExcel');
    $router->get('kas-bank-tmp', 'Esaku\KasBank\KasBankController@getDataTmp');

    // UANG MASUK

    $router->get('uang-masuk', 'Esaku\KasBank\UangMasukController@index');
    $router->get('uang-masuk-detail', 'Esaku\KasBank\UangMasukController@show');
    $router->post('uang-masuk', 'Esaku\KasBank\UangMasukController@store');
    $router->post('uang-masuk-ubah', 'Esaku\KasBank\UangMasukController@update');
    $router->delete('uang-masuk', 'Esaku\KasBank\UangMasukController@destroy');
    $router->post('uang-masuk-import-excel', 'Esaku\KasBank\UangMasukController@importExcel');
    $router->get('uang-masuk-tmp', 'Esaku\KasBank\UangMasukController@getDataTmp');

    $router->get('uang-keluar', 'Esaku\KasBank\UangKeluarController@index');
    $router->get('uang-keluar-detail', 'Esaku\KasBank\UangKeluarController@show');
    $router->post('uang-keluar', 'Esaku\KasBank\UangKeluarController@store');
    $router->post('uang-keluar-ubah', 'Esaku\KasBank\UangKeluarController@update');
    $router->delete('uang-keluar', 'Esaku\KasBank\UangKeluarController@destroy');
    $router->post('uang-keluar-import-excel', 'Esaku\KasBank\UangKeluarController@importExcel');
    $router->get('uang-keluar-tmp', 'Esaku\KasBank\UangKeluarController@getDataTmp');

    $router->get('terima-dari', 'Esaku\KasBank\UangMasukController@getTerimaDari');
    $router->get('akun-terima', 'Esaku\KasBank\UangMasukController@getAkunTerima');

    $router->get('kas-bank-dok', 'Esaku\KasBank\KasBankDokController@show');
    $router->post('kas-bank-dok', 'Esaku\KasBank\KasBankDokController@store');
    $router->delete('kas-bank-dok', 'Esaku\KasBank\KasBankDokController@destroy');

    // ANGGARAN
    $router->get('tahun', 'Esaku\Anggaran\AnggaranController@getTahun');
    $router->get('anggaran', 'Esaku\Anggaran\AnggaranController@index');
    $router->post('anggaran-upload', 'Esaku\Anggaran\AnggaranController@importExcel');
    $router->get('anggaran-load', 'Esaku\Anggaran\AnggaranController@loadAnggaran');
    $router->post('anggaran', 'Esaku\Anggaran\AnggaranController@store');

    // RRA AJU
    $router->get('rra-agg-saldo', 'Esaku\Anggaran\PengajuanRRAController@getSaldo');
    $router->get('rra-agg', 'Esaku\Anggaran\PengajuanRRAController@index');
    $router->get('rra-agg-detail', 'Esaku\Anggaran\PengajuanRRAController@show');
    $router->post('rra-agg', 'Esaku\Anggaran\PengajuanRRAController@store');
    $router->put('rra-agg', 'Esaku\Anggaran\PengajuanRRAController@update');
    $router->delete('rra-agg', 'Esaku\Anggaran\PengajuanRRAController@destroy');
    $router->get('rra-nik-app', 'Esaku\Anggaran\PengajuanRRAController@getNIKApp');
    $router->get('rra-pp-terima', 'Esaku\Anggaran\PengajuanRRAController@getPPTerima');
    $router->get('rra-akun-terima', 'Esaku\Anggaran\PengajuanRRAController@getAkunTerima');
    $router->get('rra-mta', 'Esaku\Anggaran\PengajuanRRAController@getAkun');
    $router->get('rra-pp', 'Esaku\Anggaran\PengajuanRRAController@getPP');

    $router->get('rra-app-aju', 'Esaku\Anggaran\ApprovalController@getAju');
    $router->get('rra-app-ajudet', 'Esaku\Anggaran\ApprovalController@getAjuDet');
    $router->post('rra-app', 'Esaku\Anggaran\ApprovalController@store');

    $router->post('send-whatsapp', 'Esaku\Setting\WAController@sendMessage');
    $router->get('msg-whatsapp', 'Esaku\Setting\WAController@Messages');
    $router->post('pooling', 'Esaku\Setting\WAController@storePooling');
    $router->post('jurnal-notifikasi', 'Esaku\Keuangan\JurnalController@sendNotifikasi');

    // AKRU SIMPANAN
    $router->get('akru-simp', 'Esaku\Simpanan\AkruSimpController@index');
    $router->get('akru-simp-detail', 'Esaku\Simpanan\AkruSimpController@show');
    $router->post('akru-simp', 'Esaku\Simpanan\AkruSimpController@store');
    $router->put('akru-simp', 'Esaku\Simpanan\AkruSimpController@update');
    $router->delete('akru-simp', 'Esaku\Simpanan\AkruSimpController@destroy');
    $router->get('akru-simp-jurnal', 'Esaku\Simpanan\AkruSimpController@getDaftarJurnal');
    $router->get('akru-simp-kartu', 'Esaku\Simpanan\AkruSimpController@getDaftarKartu');

    // REVERSE AKRU SIMPANAN
    $router->get('reverse-akru-simp', 'Esaku\Simpanan\ReverseAkruController@index');
    $router->get('reverse-akru-simp-detail', 'Esaku\Simpanan\ReverseAkruController@show');
    $router->post('reverse-akru-simp', 'Esaku\Simpanan\ReverseAkruController@store');
    $router->put('reverse-akru-simp', 'Esaku\Simpanan\ReverseAkruController@update');
    $router->delete('reverse-akru-simp', 'Esaku\Simpanan\ReverseAkruController@destroy');
    $router->get('reverse-akru-simp-agg', 'Esaku\Simpanan\ReverseAkruController@getAnggota');
    $router->get('reverse-akru-simp-nokartu', 'Esaku\Simpanan\ReverseAkruController@getNoKartu');
    $router->get('reverse-akru-simp-listakru', 'Esaku\Simpanan\ReverseAkruController@getDaftarAkru');

    // PENERIMAAN SIMPANAN
    $router->get('terima-simp', 'Esaku\Simpanan\PenerimaanTunaiController@index');
    $router->get('terima-simp-detail', 'Esaku\Simpanan\PenerimaanTunaiController@show');
    $router->post('terima-simp', 'Esaku\Simpanan\PenerimaanTunaiController@store');
    $router->put('terima-simp', 'Esaku\Simpanan\PenerimaanTunaiController@update');
    $router->delete('terima-simp', 'Esaku\Simpanan\PenerimaanTunaiController@destroy');
    $router->get('terima-simp-akunkas', 'Esaku\Simpanan\PenerimaanTunaiController@getAkunKas');
    $router->get('terima-simp-tagihan', 'Esaku\Simpanan\PenerimaanTunaiController@getTagihan');

    // PENERIMAAN SIMPANAN PGAJI UPLOAD
    $router->get('terima-simp-upload-akunkas', 'Esaku\Simpanan\PenerimaanUploadController@getAkunKasTitip');
    $router->get('terima-simp-upload-tagihan', 'Esaku\Simpanan\PenerimaanUploadController@getTagihan');
    $router->post('terima-simp-upload-import', 'Esaku\Simpanan\PenerimaanUploadController@importExcel');
    $router->get('terima-simp-upload-tmp', 'Esaku\Simpanan\PenerimaanUploadController@getTmp');
    $router->post('terima-simp-upload', 'Esaku\Simpanan\PenerimaanUploadController@store');

    $router->get('terima-simp-upload-nobukti', 'Esaku\Simpanan\PenerimaanUploadController@getNoBukti');
    $router->get('terima-simp-upload-loadhapus', 'Esaku\Simpanan\PenerimaanUploadController@loadDataHapus');
    $router->delete('terima-simp-upload', 'Esaku\Simpanan\PenerimaanUploadController@destroy');

    // PENARIKAN SIMPANAN
    $router->get('tarik-simp', 'Esaku\Simpanan\PenarikanController@index');
    $router->get('tarik-simp-detail', 'Esaku\Simpanan\PenarikanController@show');
    $router->post('tarik-simp', 'Esaku\Simpanan\PenarikanController@store');
    $router->put('tarik-simp', 'Esaku\Simpanan\PenarikanController@update');
    $router->delete('tarik-simp', 'Esaku\Simpanan\PenarikanController@destroy');
    $router->get('tarik-simp-akunkas', 'Esaku\Simpanan\PenarikanController@getAkunKas');
    $router->get('tarik-simp-simpanan', 'Esaku\Simpanan\PenarikanController@getSimpanan');

    // PIUTANG
    $router->get('piu-akru-nikapp', 'Esaku\Piutang\PengakuanController@getNIKApp');
    $router->get('piu-akru-akunpiu', 'Esaku\Piutang\PengakuanController@getAkunPiutang');
    $router->get('piu-akru-pp', 'Esaku\Piutang\PengakuanController@getPP');
    $router->get('piu-akru-akun', 'Esaku\Piutang\PengakuanController@getAkun');
    $router->get('piu-akru', 'Esaku\Piutang\PengakuanController@index');
    $router->get('piu-akru-detail', 'Esaku\Piutang\PengakuanController@show');
    $router->post('piu-akru', 'Esaku\Piutang\PengakuanController@store');
    $router->put('piu-akru', 'Esaku\Piutang\PengakuanController@update');
    $router->delete('piu-akru', 'Esaku\Piutang\PengakuanController@destroy');

    $router->get('piu-lunas-multi-akunkb', 'Esaku\Piutang\PelunasanMultiController@getAkunKasBank');
    $router->get('piu-lunas-multi-pp', 'Esaku\Piutang\PelunasanMultiController@getPP');
    $router->get('piu-lunas-multi-akun', 'Esaku\Piutang\PelunasanMultiController@getAkun');
    $router->get('piu-lunas-multi-load', 'Esaku\Piutang\PelunasanMultiController@loadPiutang');
    $router->get('piu-lunas-multi', 'Esaku\Piutang\PelunasanMultiController@index');
    $router->get('piu-lunas-multi-detail', 'Esaku\Piutang\PelunasanMultiController@show');
    $router->post('piu-lunas-multi', 'Esaku\Piutang\PelunasanMultiController@store');
    $router->put('piu-lunas-multi', 'Esaku\Piutang\PelunasanMultiController@update');
    $router->delete('piu-lunas-multi', 'Esaku\Piutang\PelunasanMultiController@destroy');

    $router->get('piu-lunas-nikapp', 'Esaku\Piutang\PelunasanController@getNIKApp');
    $router->get('piu-lunas-pp', 'Esaku\Piutang\PelunasanController@getPP');
    $router->get('piu-lunas-akun', 'Esaku\Piutang\PelunasanController@getAkun');
    $router->get('piu-lunas-nopiutang', 'Esaku\Piutang\PelunasanController@getPiutang');
    $router->get('piu-lunas-piu-det', 'Esaku\Piutang\PelunasanController@getDetailPiutang');
    $router->get('piu-lunas', 'Esaku\Piutang\PelunasanController@index');
    $router->get('piu-lunas-detail', 'Esaku\Piutang\PelunasanController@show');
    $router->post('piu-lunas', 'Esaku\Piutang\PelunasanController@store');
    $router->put('piu-lunas', 'Esaku\Piutang\PelunasanController@update');
    $router->delete('piu-lunas', 'Esaku\Piutang\PelunasanController@destroy');

    // SDM
    // Karyawan
    $router->get('sdm-karyawans', 'Sdm\KepegawaianController@index');
    $router->get('sdm-karyawan', 'Sdm\KepegawaianController@show');
    $router->post('sdm-karyawan', 'Sdm\KepegawaianController@save');
    $router->post('sdm-karyawan-update', 'Sdm\KepegawaianController@update');
    $router->delete('sdm-karyawan', 'Sdm\KepegawaianController@destroy');

    $router->get('v2/sdm-karyawans', 'Sdm\KepegawaianV2Controller@index');
    $router->get('v2/sdm-karyawan', 'Sdm\KepegawaianV2Controller@show');
    $router->post('v2/sdm-karyawan', 'Sdm\KepegawaianV2Controller@save');
    $router->post('v2/sdm-karyawan-update', 'Sdm\KepegawaianV2Controller@update');
    $router->delete('v2/sdm-karyawan', 'Sdm\KepegawaianV2Controller@destroy');

    $router->get('v3/sdm-karyawans', 'Sdm\KepegawaianV3Controller@index');
    $router->get('v3/sdm-karyawan', 'Sdm\KepegawaianV3Controller@show');
    $router->post('v3/sdm-karyawan', 'Sdm\KepegawaianV3Controller@save');
    $router->post('v3/sdm-karyawan-update', 'Sdm\KepegawaianV3Controller@update');
    $router->delete('v3/sdm-karyawan', 'Sdm\KepegawaianV3Controller@destroy');

    // KONTRAK/UPDATE KARYAWAN (TRX)
    $router->get('v3/sdm-kontraks', 'Sdm\KepegawaianV3Controller@get_kontrak');
    $router->get('v3/sdm-kontrak', 'Sdm\KepegawaianV3Controller@show_kontrak');
    $router->post('v3/sdm-kontrak', 'Sdm\KepegawaianV3Controller@save_kontrak');
    $router->post('v3/sdm-kontrak-update', 'Sdm\KepegawaianV3Controller@update_kontrak');

    // STATUS KEPEgAWAIAN
    $router->get('v3/sdm-status', 'Sdm\KepegawaianV3Controller@get_status');

    // GAJI PARAM (TRX)
    $router->get('v3/sdm-gaji-param', 'Sdm\KepegawaianV3Controller@show_gaji');
    $router->post('v3/sdm-gaji-param', 'Sdm\KepegawaianV3Controller@save_gaji');

    // UPLOAD KARYAWAN
    $router->post('sdm-karyawan-simpan', 'Sdm\UploadPegawaiController@store');
    $router->post('sdm-karyawan-import', 'Sdm\UploadPegawaiController@importXLS');
    $router->get('sdm-karyawan-tmp', 'Sdm\UploadPegawaiController@dataTMP');

    // Keluarga
    $router->get('sdm-keluargas', 'Sdm\KeluargaController@index');
    $router->get('sdm-keluarga', 'Sdm\KeluargaController@show');
    $router->post('sdm-keluarga', 'Sdm\KeluargaController@save');
    $router->post('sdm-keluarga-update', 'Sdm\KeluargaController@update');
    $router->delete('sdm-keluarga', 'Sdm\KeluargaController@destroy');

    $router->get('sdm-adm-keluargas', 'Sdm\KeluargaAdmController@index');
    $router->get('sdm-adm-keluarga', 'Sdm\KeluargaAdmController@show');
    $router->post('sdm-adm-keluarga', 'Sdm\KeluargaAdmController@save');
    $router->post('sdm-adm-keluarga-update', 'Sdm\KeluargaAdmController@update');
    $router->delete('sdm-adm-keluarga', 'Sdm\KeluargaAdmController@destroy');

    // Kedinasan
    $router->get('sdm-dinass', 'Sdm\DinasController@index');
    $router->get('sdm-dinas', 'Sdm\DinasController@show');
    $router->post('sdm-dinas', 'Sdm\DinasController@save');
    $router->post('sdm-dinas-update', 'Sdm\DinasController@update');
    $router->delete('sdm-dinas', 'Sdm\DinasController@destroy');

    $router->get('sdm-adm-dinass', 'Sdm\DinasAdmController@index');
    $router->get('sdm-adm-dinas', 'Sdm\DinasAdmController@show');
    $router->post('sdm-adm-dinas', 'Sdm\DinasAdmController@save');
    $router->post('sdm-adm-dinas-update', 'Sdm\DinasAdmController@update');
    $router->delete('sdm-adm-dinas', 'Sdm\DinasAdmController@destroy');

    // Pendidikan
    $router->get('sdm-pendidikans', 'Sdm\PendidikanController@index');
    $router->get('sdm-pendidikan', 'Sdm\PendidikanController@show');
    $router->post('sdm-pendidikan', 'Sdm\PendidikanController@save');
    $router->post('sdm-pendidikan-update', 'Sdm\PendidikanController@update');
    $router->delete('sdm-pendidikan', 'Sdm\PendidikanController@destroy');

    $router->get('sdm-adm-pendidikans', 'Sdm\PendidikanAdmController@index');
    $router->get('sdm-adm-pendidikan', 'Sdm\PendidikanAdmController@show');
    $router->post('sdm-adm-pendidikan', 'Sdm\PendidikanAdmController@save');
    $router->post('sdm-adm-pendidikan-update', 'Sdm\PendidikanAdmController@update');
    $router->delete('sdm-adm-pendidikan', 'Sdm\PendidikanAdmController@destroy');

    // Pelatihan
    $router->get('sdm-pelatihans', 'Sdm\PelatihanController@index');
    $router->get('sdm-pelatihan', 'Sdm\PelatihanController@show');
    $router->post('sdm-pelatihan', 'Sdm\PelatihanController@save');
    $router->post('sdm-pelatihan-update', 'Sdm\PelatihanController@update');
    $router->delete('sdm-pelatihan', 'Sdm\PelatihanController@destroy');

    $router->get('sdm-adm-pelatihans', 'Sdm\PelatihanAdmController@index');
    $router->get('sdm-adm-pelatihan', 'Sdm\PelatihanAdmController@show');
    $router->post('sdm-adm-pelatihan', 'Sdm\PelatihanAdmController@save');
    $router->post('sdm-adm-pelatihan-update', 'Sdm\PelatihanAdmController@update');
    $router->delete('sdm-adm-pelatihan', 'Sdm\PelatihanAdmController@destroy');

    // Penghargaan
    $router->get('sdm-penghargaans', 'Sdm\PenghargaanController@index');
    $router->get('sdm-penghargaan', 'Sdm\PenghargaanController@show');
    $router->post('sdm-penghargaan', 'Sdm\PenghargaanController@save');
    $router->post('sdm-penghargaan-update', 'Sdm\PenghargaanController@update');
    $router->delete('sdm-penghargaan', 'Sdm\PenghargaanController@destroy');

    $router->get('sdm-adm-penghargaans', 'Sdm\PenghargaanAdmController@index');
    $router->get('sdm-adm-penghargaan', 'Sdm\PenghargaanAdmController@show');
    $router->post('sdm-adm-penghargaan', 'Sdm\PenghargaanAdmController@save');
    $router->post('sdm-adm-penghargaan-update', 'Sdm\PenghargaanAdmController@update');
    $router->delete('sdm-adm-penghargaan', 'Sdm\PenghargaanAdmController@destroy');

    // Sanksi
    $router->get('sdm-sanksis', 'Sdm\SanksiController@index');
    $router->get('sdm-sanksi', 'Sdm\SanksiController@show');
    $router->post('sdm-sanksi', 'Sdm\SanksiController@save');
    $router->post('sdm-sanksi-update', 'Sdm\SanksiController@update');
    $router->delete('sdm-sanksi', 'Sdm\SanksiController@destroy');

    $router->get('sdm-adm-sanksis', 'Sdm\SanksiAdmController@index');
    $router->get('sdm-adm-sanksi', 'Sdm\SanksiAdmController@show');
    $router->post('sdm-adm-sanksi', 'Sdm\SanksiAdmController@save');
    $router->post('sdm-adm-sanksi-update', 'Sdm\SanksiAdmController@update');
    $router->delete('sdm-adm-sanksi', 'Sdm\SanksiAdmController@destroy');

    $router->get('inv-hitung-hpp', 'Esaku\Inventori\HppController@index');
    $router->get('inv-hitung-hpp-detail', 'Esaku\Inventori\HppController@show');
    $router->get('inv-hitung-hpp-load', 'Esaku\Inventori\HppController@loadData');
    $router->post('inv-hitung-hpp', 'Esaku\Inventori\HppController@store');
    $router->post('inv-hitung-hpp-update', 'Esaku\Inventori\HppController@update');
    $router->delete('inv-hitung-hpp', 'Esaku\Inventori\HppController@destroy');

    //Retur Penjualan
    $router->get('retur-jual-bukti', 'Esaku\Inventori\ReturPenjualanController@getPenjualan');
    $router->get('retur-jual-detail', 'Esaku\Inventori\ReturPenjualanController@show');
    $router->post('retur-jual', 'Esaku\Inventori\ReturPenjualanController@store');
});

$router->post('select-query', 'Esaku\Inventori\Sync2Controller@selectQuery');
$router->get('anggaran-export', 'Esaku\Anggaran\AnggaranController@export');

$router->group(['middleware' => 'auth:admin'], function () use ($router) {

    // $router->get('load-sync-master','Esaku\Inventori\Sync2Controller@loadSyncMaster');
    // $router->post('sync-pnj','Esaku\Inventori\Sync2Controller@syncPnj');
    // $router->post('sync-pmb','Esaku\Inventori\Sync2Controller@syncPmb');
    // $router->post('sync-retur-beli','Esaku\Inventori\Sync2Controller@syncReturBeli');
});

$router->get('export', 'Esaku\Keuangan\JurnalController@export');
$router->get('sawal-export', 'Esaku\Keuangan\SawalController@export');
$router->get('jurnal-upload-export', 'Esaku\Keuangan\JurnalUploadController@export');
$router->get('akun-export', 'Esaku\Keuangan\AkunController@export');
$router->get('terima-simp-upload-export', 'Esaku\Simpanan\PenerimaanUploadController@export');

// export template excel SDM -Data Karaywan
$router->get('sdm-export', 'Sdm\UploadPegawaiController@exportXLS');
