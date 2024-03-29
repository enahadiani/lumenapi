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
    $router->get('filter-periode-pnj','Esaku\Keuangan\FilterController@getFilterPeriode');
    $router->get('filter-nik','Esaku\Keuangan\FilterController@getFilterNIK');
    $router->get('filter-tanggal','Esaku\Keuangan\FilterController@getFilterTanggal');
    $router->get('filter-bukti','Esaku\Keuangan\FilterController@getFilterNoBukti');
    $router->get('filter-barang','Esaku\Keuangan\FilterController@getFilterBarang');
    $router->get('filter-bukti-faktur','Esaku\Keuangan\FilterController@getFilterNoBuktiFaktur');
    $router->get('filter-tanggal-close','Esaku\Keuangan\FilterController@getFilterTanggalClose');
    $router->get('filter-periode-close','Esaku\Keuangan\FilterController@getFilterPeriodeClose');
    $router->get('filter-nik-close','Esaku\Keuangan\FilterController@getFilterNIKClose');
    $router->get('filter-bukti-close','Esaku\Keuangan\FilterController@getFilterNoBuktiClose');
    $router->get('filter-periode-pmb','Esaku\Keuangan\FilterController@getFilterPeriodePmb');
    $router->get('filter-nik-pmb','Esaku\Keuangan\FilterController@getFilterNIKPmb');
    $router->get('filter-gudang-pmb','Esaku\Keuangan\FilterController@getFilterGudangPmb');
    $router->get('filter-bukti-pmb','Esaku\Keuangan\FilterController@getFilterNoBuktiPmb');
    $router->get('filter-periode-retur','Esaku\Keuangan\FilterController@getFilterPeriodeRetur');
    $router->get('filter-nik-retur','Esaku\Keuangan\FilterController@getFilterNIKRetur');
    $router->get('filter-bukti-retur','Esaku\Keuangan\FilterController@getFilterNoBuktiRetur');
    $router->get('filter-bukti-retur-jual','Esaku\Inventori\FilterController@getFilterNoBuktiReturJual');
    $router->get('filter-nik-retur-jual','Esaku\Inventori\FilterController@getFilterNIKRetur');
    $router->get('filter-periode-retur-jual','Esaku\Inventori\FilterController@getFilterPeriodeReturJual');
    $router->get('filter-akun','Esaku\Keuangan\FilterController@getFilterAkun');
    $router->get('filter-periode-keu','Esaku\Keuangan\FilterController@getFilterPeriodeKeuangan');
    $router->get('filter-fs','Esaku\Keuangan\FilterController@getFilterFS');
    $router->get('filter-level','Esaku\Keuangan\FilterController@getFilterLevel');
    $router->get('filter-format','Esaku\Keuangan\FilterController@getFilterFormat');
    $router->get('filter-sumju','Esaku\Keuangan\FilterController@getFilterYaTidak');
    $router->get('filter-modul','Esaku\Keuangan\FilterController@getFilterModul');
    $router->get('filter-bukti-jurnal','Esaku\Keuangan\FilterController@getFilterBuktiJurnal');
    $router->get('filter-mutasi','Esaku\Keuangan\FilterController@getFilterYaTidak');
    $router->get('filter-gudang','Esaku\Keuangan\FilterController@getFilterGudang');
    $router->get('filter-barang-klp','Esaku\Keuangan\FilterController@getFilterKlpBarang');
    $router->get('filter-tahun-keu','Esaku\Keuangan\FilterController@getFilterTahun');
    $router->get('filter-bukti-mutasi','Esaku\Keuangan\FilterController@getFilterBuktiMutasi');
    $router->get('filter-bukti-kontrol-mutasi','Esaku\Keuangan\FilterController@getFilterBuktiKontrolMutasi');
    $router->get('filter-pp-keu','Esaku\Keuangan\FilterController@getFilterPP');
    $router->get('filter-vendor','Esaku\Inventori\FilterController@getFilterVendor');
    $router->get('filter-barang-hpp','Esaku\Keuangan\FilterController@getFilterBarangHpp');


    $router->get('filter-periode-jualstr','Esaku\Inventori\FilterController@getPeriodeJualSetor');
    $router->get('filter-tahun-jualstr','Esaku\Inventori\FilterController@getTahunJualSetor');
    $router->get('filter-nik-jualstr','Esaku\Inventori\FilterController@getNikJualSetor');
    $router->get('filter-gudang-jualstr','Esaku\Inventori\FilterController@getFilterGudangJualSetor');

    $router->get('filter-tanggal-pmb','Esaku\Keuangan\FilterController@getFilterTanggalPmb');

    
    $router->get('filter-lokasi','Esaku\Inventori\FilterController@getFilterLokasi');
    $router->get('filter-default','Esaku\Inventori\FilterController@getFilterDefault');
    //Laporan
    $router->get('lap-faktur-pnj','Esaku\Inventori\LaporanController@getReportFakturPnj');
    $router->get('lap-barang','Esaku\Inventori\LaporanController@getReportBarang');
    $router->get('lap-closing','Esaku\Inventori\LaporanController@getReportClosing');
    $router->get('lap-penjualan','Esaku\Inventori\LaporanController@getReportPenjualan');
    $router->get('lap-pembelian','Esaku\Inventori\LaporanController@getReportPembelian');
    $router->get('lap-penjualan-harian','Esaku\Inventori\LaporanController@getReportPenjualanHarian');
    $router->get('lap-penjualan-harian-v2','Esaku\Inventori\LaporanController@getReportPenjualanHarianV2');
    $router->get('lap-retur-beli','Esaku\Inventori\LaporanController@getReportReturBeli');
    $router->get('lap-retur-jual','Esaku\Inventori\LaporanController@getReportReturJual');
    $router->get('lap-kartu-stok','Esaku\Inventori\LaporanController@getKartuStok');
    $router->get('lap-stok','Esaku\Inventori\LaporanController@getStok');
    $router->get('lap-posisi-stok','Esaku\Inventori\LaporanController@getPosisiStok');
    $router->get('lap-saldo-hutang','Esaku\Inventori\LaporanController@getSaldoHutang');
    $router->get('lap-saldo-stok','Esaku\Inventori\LaporanController@getLapSaldoStok');
    $router->get('lap-nota-jual','Esaku\Inventori\LaporanController@getNotaPnj');
    $router->get('lap-rekap-jual','Esaku\Inventori\LaporanController@getReportRekapPenjualan');
    $router->get('lap-rekap-jualstr','Esaku\Inventori\LaporanController@getRekapJualSetor');
    $router->get('lap-rekap-jual-perbrg','Esaku\Inventori\LaporanController@getRekapJualBarang');
    $router->get('lap-rekap-beli-perbrg','Esaku\Inventori\LaporanController@getRekapBeliBarang');
    $router->get('lap-stock-opname','Esaku\Inventori\LaporanController@getRekapStockOpname');


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

    // SIMPANAN
    
    $router->get('filter-simp-periode','Esaku\Simpanan\FilterController@getFilterPeriode');
    $router->get('filter-simp-anggota','Esaku\Simpanan\FilterController@getFilterAnggota');
    $router->get('filter-simp-nokartu','Esaku\Simpanan\FilterController@getFilterNoKartu');
    $router->get('filter-simp-noakru','Esaku\Simpanan\FilterController@getFilterNoAkru');
    $router->get('filter-simp-nobayar','Esaku\Simpanan\FilterController@getFilterNoBayar');
    $router->get('filter-simp-nobatal','Esaku\Simpanan\FilterController@getFilterNoBatal');

    $router->get('lap-simp-anggota','Esaku\Simpanan\LaporanController@getAnggota');
    $router->get('lap-simp-simpanan','Esaku\Simpanan\LaporanController@getSimpanan');
    $router->get('lap-simp-saldo','Esaku\Simpanan\LaporanController@getSaldoSimpanan');
    $router->get('lap-simp-akru','Esaku\Simpanan\LaporanController@getAkruSimpanan');
    $router->get('lap-simp-bayar','Esaku\Simpanan\LaporanController@getBayarSimpanan');
    $router->get('lap-simp-batal','Esaku\Simpanan\LaporanController@getBatalSimpanan');
    $router->get('lap-simp-rekap','Esaku\Simpanan\LaporanController@getRekapSimpanan');

    //ANGGARAN
    $router->get('filter-agg-tahun','Esaku\Anggaran\FilterController@getFilterTahun');
    $router->get('filter-agg-akun','Esaku\Anggaran\FilterController@getFilterAkun');
    $router->get('filter-agg-pp','Esaku\Anggaran\FilterController@getFilterPP');
    $router->get('filter-agg-jenis','Esaku\Anggaran\FilterController@getFilterJenis');
    $router->get('filter-agg-periodik','Esaku\Anggaran\FilterController@getFilterPeriodikLap');
    $router->get('filter-agg-status','Esaku\Anggaran\FilterController@getFilterStatusAgg');
    $router->get('filter-agg-periode','Esaku\Anggaran\FilterController@getFilterPeriode');
    $router->get('filter-agg-jenisagg','Esaku\Anggaran\FilterController@getFilterJenisAgg');
    $router->get('filter-agg-jenisrra','Esaku\Anggaran\FilterController@getFilterJenisRRA');
    $router->get('filter-agg-modul','Esaku\Anggaran\FilterController@getFilterModul');

    $router->get('lap-agg-anggaran','Esaku\Anggaran\LaporanController@getAnggaran');
    $router->get('lap-agg-real','Esaku\Anggaran\LaporanController@getRealAnggaran');
    $router->get('lap-agg-capai','Esaku\Anggaran\LaporanController@getCapaiAnggaran');
    $router->get('lap-agg-kartu','Esaku\Anggaran\LaporanController@getKartuAnggaran');
    $router->get('lap-agg-labarugi','Esaku\Anggaran\LaporanController@getLabaRugiAgg');
    $router->get('lap-agg-labarugi-detail','Esaku\Anggaran\LaporanController@getLabaRugiAggDetail');
    $router->get('lap-agg-labarugi-unit','Esaku\Anggaran\LaporanController@getLabaRugiAggUnit');
    $router->get('lap-agg-labarugi-unit-detail','Esaku\Anggaran\LaporanController@getLabaRugiAggUnitDetail');
    $router->get('lap-agg-aju-rra','Esaku\Anggaran\LaporanController@getPengajuanRRA');
    $router->get('lap-agg-app-rra','Esaku\Anggaran\LaporanController@getApprovalRRA');
    $router->get('lap-agg-posisi-rra','Esaku\Anggaran\LaporanController@getPosisiRRA');

    
    // LAPORAN PIUTANG
    //ANGGARAN
    $router->get('filter-piu-periode','Esaku\Piutang\FilterController@getFilterPeriode');
    $router->get('filter-piu-akun','Esaku\Piutang\FilterController@getFilterAkunPiutang');
    $router->get('filter-piu-cust','Esaku\Piutang\FilterController@getFilterCustomer');
    $router->get('filter-piu-nopiutang','Esaku\Piutang\FilterController@getFilterNoPiutang');
    $router->get('filter-piu-nobayar','Esaku\Piutang\FilterController@getFilterNoJurnal');

    $router->get('lap-piu-cust','Esaku\Piutang\LaporanController@getCustomer');
    $router->get('lap-piu-pengakuan','Esaku\Piutang\LaporanController@getPengakuan');
    $router->get('lap-piu-saldo','Esaku\Piutang\LaporanController@getSaldo');
    $router->get('lap-piu-kartu','Esaku\Piutang\LaporanController@getKartuPiutang');
    $router->get('lap-piu-aging','Esaku\Piutang\LaporanController@getAging');
    $router->get('lap-piu-jurnalakru','Esaku\Piutang\LaporanController@getPiutangJurnal');
    $router->get('lap-piu-jurnalbayar','Esaku\Piutang\LaporanController@getPiutangKas');

    // LAPORAN SDM
    // KARYAWAN
    $router->get('sdm-lap-karyawan','Sdm\LaporanController@getKaryawan');
    $router->get('sdm-lap-karyawanCv','Sdm\LaporanController@getCVKaryawan');

    

});



?>