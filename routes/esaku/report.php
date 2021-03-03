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
    //Filter Laporan
    $router->get('filter-periode','Esaku\Inventori\FilterController@getFilterPeriode');
    $router->get('filter-nik','Esaku\Inventori\FilterController@getFilterNIK');
    $router->get('filter-tanggal','Esaku\Inventori\FilterController@getFilterTanggal');
    $router->get('filter-bukti','Esaku\Inventori\FilterController@getFilterNoBukti');
    $router->get('filter-barang','Esaku\Inventori\FilterController@getFilterBarang');
    $router->get('filter-periode-close','Esaku\Inventori\FilterController@getFilterPeriodeClose');
    $router->get('filter-nik-close','Esaku\Inventori\FilterController@getFilterNIKClose');
    $router->get('filter-bukti-close','Esaku\Inventori\FilterController@getFilterNoBuktiClose');
    $router->get('filter-periode-pmb','Esaku\Inventori\FilterController@getFilterPeriodePmb');
    $router->get('filter-nik-pmb','Esaku\Inventori\FilterController@getFilterNIKPmb');
    $router->get('filter-bukti-pmb','Esaku\Inventori\FilterController@getFilterNoBuktiPmb');
    $router->get('filter-periode-retur','Esaku\Inventori\FilterController@getFilterPeriodeRetur');
    $router->get('filter-nik-retur','Esaku\Inventori\FilterController@getFilterNIKRetur');
    $router->get('filter-bukti-retur','Esaku\Inventori\FilterController@getFilterNoBuktiRetur');
    $router->get('filter-akun','Esaku\Inventori\FilterController@getFilterAkun');
    $router->get('filter-periode-keu','Esaku\Inventori\FilterController@getFilterPeriodeKeuangan');
    $router->get('filter-fs','Esaku\Inventori\FilterController@getFilterFS');
    $router->get('filter-level','Esaku\Inventori\FilterController@getFilterLevel');
    $router->get('filter-format','Esaku\Inventori\FilterController@getFilterFormat');
    $router->get('filter-sumju','Esaku\Inventori\FilterController@getFilterYaTidak');
    $router->get('filter-modul','Esaku\Inventori\FilterController@getFilterModul');
    $router->get('filter-bukti-jurnal','Esaku\Inventori\FilterController@getFilterBuktiJurnal');
    $router->get('filter-mutasi','Esaku\Inventori\FilterController@getFilterYaTidak');
    $router->get('filter-gudang','Esaku\Inventori\FilterController@getFilterGudang');
    $router->get('filter-barang-klp','Esaku\Inventori\FilterController@getFilterKlpBarang');
    $router->get('filter-tahun-keu','Esaku\Inventori\FilterController@getFilterTahun');
    $router->get('filter-bukti-mutasi','Esaku\Inventori\FilterController@getFilterBuktiMutasi');
    $router->get('filter-bukti-kontrol-mutasi','Esaku\Inventori\FilterController@getFilterBuktiKontrolMutasi');
    $router->get('filter-pp-keu','Esaku\Inventori\FilterController@getFilterPP');

    //Laporan
    $router->get('lap-barang','Esaku\Inventori\LaporanController@getReportBarang');
    $router->get('lap-closing','Esaku\Inventori\LaporanController@getReportClosing');
    $router->get('lap-penjualan','Esaku\Inventori\LaporanController@getReportPenjualan');
    $router->get('lap-pembelian','Esaku\Inventori\LaporanController@getReportPembelian');
    $router->get('lap-penjualan-harian','Esaku\Inventori\LaporanController@getReportPenjualanHarian');
    $router->get('lap-retur-beli','Esaku\Inventori\LaporanController@getReportReturBeli');
    $router->get('lap-kartu-stok','Esaku\Inventori\LaporanController@getKartuStok');

    $router->get('lap_kartu','Esaku\Inventori\LaporanController@getGlReportBukuBesar');
    $router->get('lap_saldo','Esaku\Inventori\LaporanController@getGlReportNeracaLajur');
    $router->get('lap-nrclajur','Esaku\Inventori\LaporanController@getNrcLajur');
    $router->get('lap-jurnal','Esaku\Inventori\LaporanController@getJurnal');
    $router->get('lap-buktijurnal','Esaku\Inventori\LaporanController@getBuktiJurnal');
    $router->get('lap-bukubesar','Esaku\Inventori\LaporanController@getBukuBesar');
    $router->get('lap-neraca','Esaku\Inventori\LaporanController@getNeraca');
    $router->get('lap-labarugi','Esaku\Inventori\LaporanController@getLabaRugi');

    $router->get('lap-neraca-komparasi','Esaku\Keuangan\LaporanKeuLanjutController@getNeracaKomparasi');
    $router->get('lap-labarugi-komparasi','Esaku\Keuangan\LaporanKeuLanjutController@getLabaRugiKomparasi');
    $router->get('lap-coa','Esaku\Keuangan\LaporanKeuLanjutController@getCOA');
    $router->get('lap-coa-struktur','Esaku\Keuangan\LaporanKeuLanjutController@getCOAStruktur');
    $router->get('lap-nrclajur-bulan','Esaku\Keuangan\LaporanKeuLanjutController@getNrcLajurBulan');
    $router->get('lap-labarugi-bulan','Esaku\Keuangan\LaporanKeuLanjutController@getLabaRugiBulan');
    $router->get('lap-neraca-bulan','Esaku\Keuangan\LaporanKeuLanjutController@getNeracaBulan');
    $router->get('lap-labarugi-unit','Esaku\Keuangan\LaporanKeuLanjutController@getLabaRugiUnit');
    $router->get('lap-labarugi-unit-dc','Esaku\Keuangan\LaporanKeuLanjutController@getLabaRugiUnitDC');
    
    $router->post('send-laporan','Esaku\Inventori\LaporanController@sendMail');
    $router->post('send-email','EmailController@send');


    // LAPORAN AKTIVA TETAP
    $router->get('filter-periode','Esaku\Aktap\FilterAktapController@getFilterPeriode');
    $router->get('filter-periode-susut','Esaku\Aktap\FilterAktapController@getFilterPeriodeSusut');
    $router->get('filter-klp-akun','Esaku\Aktap\FilterAktapController@getFilterKlpAkun');
    $router->get('filter-jenis','Esaku\Aktap\FilterAktapController@getFilterJenis');
    $router->get('filter-aset','Esaku\Aktap\FilterAktapController@getFilterAset');
    $router->get('filter-pp','Esaku\Aktap\FilterAktapController@getFilterPP');
    $router->get('filter-tahun','Esaku\Aktap\FilterAktapController@getFilterTahun');
    $router->get('filter-jenis-klp','Esaku\Aktap\FilterAktapController@getFilterJenisKlp');
    $router->get('filter-bukti-jurnal-susut','Esaku\Aktap\FilterAktapController@getFilterBuktiJurnalSusut');
    $router->get('filter-bukti-jurnal-wo','Esaku\Aktap\FilterAktapController@getFilterBuktiJurnalWO');

    $router->get('lap-data-aktap','Esaku\Aktap\LaporanAktapController@getAktap');
    $router->get('lap-kartu-aktap','Esaku\Aktap\LaporanAktapController@getKartuAktap');
    $router->get('lap-saldo-aktap','Esaku\Aktap\LaporanAktapController@getSaldoAktap');
    $router->get('lap-saldo-aktap-tahun','Esaku\Aktap\LaporanAktapController@getSaldoAktapTahun');
    $router->get('lap-jurnal-aktap','Esaku\Aktap\LaporanAktapController@getJurnalAktap');
    $router->get('lap-jurnal-wo','Esaku\Aktap\LaporanAktapController@getJurnalWO');
    $router->get('lap-saldo-aktap-bulan','Esaku\Aktap\LaporanAktapController@getSaldoAktapBln');

    // KAS BANK
    
    $router->get('filter-periode-kb','Esaku\KasBank\FilterController@getFilterPeriodeKB');
    $router->get('filter-bukti-jurnal-kb','Esaku\KasBank\FilterController@getFilterBuktiJurnalKB');

    $router->get('lap-jurnal-kb','Esaku\KasBank\LaporanKasBankController@getJurnal');
    $router->get('lap-buktijurnal-kb','Esaku\KasBank\LaporanKasBankController@getBuktiJurnal');
    $router->get('lap-buku-kb','Esaku\KasBank\LaporanKasBankController@getBukuKas');
    $router->get('lap-saldo-kb','Esaku\KasBank\LaporanKasBankController@getSaldoKB');

});



?>