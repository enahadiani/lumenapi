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
    $router->get('filter-periode','Toko\FilterController@getFilterPeriode');
    $router->get('filter-nik','Toko\FilterController@getFilterNIK');
    $router->get('filter-tanggal','Toko\FilterController@getFilterTanggal');
    $router->get('filter-bukti','Toko\FilterController@getFilterNoBukti');
    $router->get('filter-barang','Toko\FilterController@getFilterBarang');
    $router->get('filter-periode-close','Toko\FilterController@getFilterPeriodeClose');
    $router->get('filter-nik-close','Toko\FilterController@getFilterNIKClose');
    $router->get('filter-bukti-close','Toko\FilterController@getFilterNoBuktiClose');
    $router->get('filter-periode-pmb','Toko\FilterController@getFilterPeriodePmb');
    $router->get('filter-nik-pmb','Toko\FilterController@getFilterNIKPmb');
    $router->get('filter-bukti-pmb','Toko\FilterController@getFilterNoBuktiPmb');
    $router->get('filter-periode-retur','Toko\FilterController@getFilterPeriodeRetur');
    $router->get('filter-nik-retur','Toko\FilterController@getFilterNIKRetur');
    $router->get('filter-bukti-retur','Toko\FilterController@getFilterNoBuktiRetur');
    $router->get('filter-akun','Toko\FilterController@getFilterAkun');
    $router->get('filter-periode-keu','Toko\FilterController@getFilterPeriodeKeuangan');
    $router->get('filter-fs','Toko\FilterController@getFilterFS');
    $router->get('filter-level','Toko\FilterController@getFilterLevel');
    $router->get('filter-format','Toko\FilterController@getFilterFormat');
    $router->get('filter-sumju','Toko\FilterController@getFilterYaTidak');
    $router->get('filter-modul','Toko\FilterController@getFilterModul');
    $router->get('filter-bukti-jurnal','Toko\FilterController@getFilterBuktiJurnal');
    $router->get('filter-mutasi','Toko\FilterController@getFilterYaTidak');
    $router->get('filter-gudang','Toko\FilterController@getFilterGudang');
    $router->get('filter-barang-klp','Toko\FilterController@getFilterKlpBarang');
    $router->get('filter-tahun-keu','Toko\FilterController@getFilterTahun');
    $router->get('filter-bukti-mutasi','Toko\FilterController@getFilterBuktiMutasi');
    $router->get('filter-pp-keu','Toko\FilterController@getFilterPP');

    //Laporan
    $router->get('lap-barang','Toko\LaporanController@getReportBarang');
    $router->get('lap-closing','Toko\LaporanController@getReportClosing');
    $router->get('lap-penjualan','Toko\LaporanController@getReportPenjualan');
    $router->get('lap-pembelian','Toko\LaporanController@getReportPembelian');
    $router->get('lap-penjualan-harian','Toko\LaporanController@getReportPenjualanHarian');
    $router->get('lap-retur-beli','Toko\LaporanController@getReportReturBeli');
    $router->get('lap-kartu-stok','Toko\LaporanController@getKartuStok');

    $router->get('lap_kartu','Toko\LaporanController@getGlReportBukuBesar');
    $router->get('lap_saldo','Toko\LaporanController@getGlReportNeracaLajur');
    $router->get('lap-nrclajur','Toko\LaporanController@getNrcLajur');
    $router->get('lap-jurnal','Toko\LaporanController@getJurnal');
    $router->get('lap-buktijurnal','Toko\LaporanController@getBuktiJurnal');
    $router->get('lap-bukubesar','Toko\LaporanController@getBukuBesar');
    $router->get('lap-neraca','Toko\LaporanController@getNeraca');
    $router->get('lap-labarugi','Toko\LaporanController@getLabaRugi');

    $router->get('lap-neraca-komparasi','Toko\LaporanKeuLanjutController@getNeracaKomparasi');
    $router->get('lap-labarugi-komparasi','Toko\LaporanKeuLanjutController@getLabaRugiKomparasi');
    $router->get('lap-coa','Toko\LaporanKeuLanjutController@getCOA');
    $router->get('lap-coa-struktur','Toko\LaporanKeuLanjutController@getCOAStruktur');
    $router->get('lap-nrclajur-bulan','Toko\LaporanKeuLanjutController@getNrcLajurBulan');
    $router->get('lap-labarugi-bulan','Toko\LaporanKeuLanjutController@getLabaRugiBulan');
    $router->get('lap-neraca-bulan','Toko\LaporanKeuLanjutController@getNeracaBulan');
    $router->get('lap-labarugi-unit','Toko\LaporanKeuLanjutController@getLabaRugiUnit');
    $router->get('lap-labarugi-unit-dc','Toko\LaporanKeuLanjutController@getLabaRugiUnitDC');
    
    $router->post('send-laporan','Toko\LaporanController@sendMail');
    $router->post('send-email','EmailController@send');


    // LAPORAN AKTIVA TETAP
    $router->get('filter-periode','Toko\FilterAktapController@getFilterPeriode');
    $router->get('filter-periode-susut','Toko\FilterAktapController@getFilterPeriodeSusut');
    $router->get('filter-klp-akun','Toko\FilterAktapController@getFilterKlpAkun');
    $router->get('filter-jenis','Toko\FilterAktapController@getFilterJenis');
    $router->get('filter-aset','Toko\FilterAktapController@getFilterAset');
    $router->get('filter-pp','Toko\FilterAktapController@getFilterPP');
    $router->get('filter-tahun','Toko\FilterAktapController@getFilterTahun');
    $router->get('filter-jenis-klp','Toko\FilterAktapController@getFilterJenisKlp');
    $router->get('filter-bukti-jurnal-susut','Toko\FilterAktapController@getFilterBuktiJurnalSusut');
    $router->get('filter-bukti-jurnal-wo','Toko\FilterAktapController@getFilterBuktiJurnalWO');

    $router->get('lap-data-aktap','Toko\LaporanAktapController@getAktap');
    $router->get('lap-kartu-aktap','Toko\LaporanAktapController@getKartuAktap');
    $router->get('lap-saldo-aktap','Toko\LaporanAktapController@getSaldoAktap');
    $router->get('lap-saldo-aktap-tahun','Toko\LaporanAktapController@getSaldoAktapTahun');
    $router->get('lap-jurnal-aktap','Toko\LaporanAktapController@getJurnalAktap');
    $router->get('lap-jurnal-wo','Toko\LaporanAktapController@getJurnalWO');
    $router->get('lap-saldo-aktap-bulan','Toko\LaporanAktapController@getSaldoAktapBln');

});



?>