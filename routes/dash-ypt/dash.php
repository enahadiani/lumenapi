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

$router->group(['middleware' => 'auth:yptkug'], function () use ($router) {

    $router->get('data-fp-box','DashYpt\DashboardFPController@getDataBoxFirst');  
    $router->get('v2/data-fp-box','DashYpt\DashboardFPV2Controller@getDataBoxFirst');  
    $router->get('data-fp-pdpt','DashYpt\DashboardFPController@getDataBoxPdpt');  
    $router->get('v2/data-fp-pdpt','DashYpt\DashboardFPV2Controller@getDataPdpt');  
    $router->get('data-fp-beban','DashYpt\DashboardFPController@getDataBoxBeban');  
    $router->get('v2/data-fp-beban','DashYpt\DashboardFPV2Controller@getDataBeban');  
    $router->get('data-fp-shu','DashYpt\DashboardFPController@getDataBoxShu');  
    $router->get('v2/data-fp-shu','DashYpt\DashboardFPV2Controller@getDataSHU');  
    $router->get('data-fp-or','DashYpt\DashboardFPController@getDataBoxOr'); 
    $router->get('v2/data-fp-or','DashYpt\DashboardFPV2Controller@getDataOR');  
    $router->get('data-fp-lr','DashYpt\DashboardFPController@getDataBoxLabaRugi'); 
    $router->get('data-fp-pl','DashYpt\DashboardFPController@getDataBoxPerformLembaga'); 

    $router->get('data-fp-unit-box','DashYpt\DashboardFPUnitController@getDataBoxFirst'); 
    $router->get('data-fp-unit-lr','DashYpt\DashboardFPUnitController@getLabaRugi'); 
    $router->get('data-fp-unit-kas','DashYpt\DashboardFPUnitController@getSaldoKasBank');  

    $router->get('data-fp-detail-perform','DashYpt\DashboardFPController@getDataPerformansiLembaga');  
    $router->get('data-fp-detail-lembaga','DashYpt\DashboardFPController@getDataPerLembaga');  
    $router->get('data-fp-detail-kelompok','DashYpt\DashboardFPController@getDataKelompokYoy');  
    $router->get('data-fp-detail-akun','DashYpt\DashboardFPController@getDataKelompokAkun');  
    $router->get('data-fp-detail-or-5tahun','DashYpt\DashboardFPController@getDataOR5Tahun');  

    $router->get('data-ccr-box','DashYpt\DashboardCCRController@getDataBox'); 
    $router->get('data-ccr-top','DashYpt\DashboardCCRController@getTopCCR');  
    $router->get('data-ccr-trend','DashYpt\DashboardCCRController@getTrendCCR');  
    $router->get('data-ccr-trend-saldo','DashYpt\DashboardCCRController@getTrendSaldoPiutang');
    $router->get('data-ccr-bidang','DashYpt\DashboardCCRController@getBidang');  
    $router->get('data-ccr-umur-piutang','DashYpt\DashboardCCRController@getUmurPiutang');

    $router->get('data-ccr-unit-box','DashYpt\DashboardCCRUnitController@getDataBox'); 
    $router->get('data-ccr-unit-trend','DashYpt\DashboardCCRUnitController@getTrendCCR');  
    $router->get('data-ccr-unit-trend-saldo','DashYpt\DashboardCCRUnitController@getTrendSaldoPiutang');
    $router->get('data-ccr-unit-umur-piutang','DashYpt\DashboardCCRUnitController@getUmurPiutang');
    
    $router->get('data-cf-box','DashYpt\DashboardCFController@getDataBox');  
    $router->get('data-cf-chart-bulanan','DashYpt\DashboardCFController@getCashFlowBulanan');
    $router->get('data-cf-soakhir','DashYpt\DashboardCFController@getSoAkhirPerLembaga');  

    // INVEST
    $router->get('data-inves-box','DashYpt\DashboardInvesController@getDataBox'); 
    $router->get('data-inves-serap-agg','DashYpt\DashboardInvesController@getSerapAgg');  
    $router->get('data-inves-nilai-aset','DashYpt\DashboardInvesController@getNilaiAsetChart');  
    $router->get('data-inves-agg-lembaga','DashYpt\DashboardInvesController@getAggPerLembagaChart');  
    // END INVEST
    
    // RASIO
    $router->get('data-rasio-jenis','DashYpt\DashboardRasioController@getKlpRasio');
    $router->get('data-rasio-lembaga','DashYpt\DashboardRasioController@getLokasi');
    $router->get('data-rasio-ytd','DashYpt\DashboardRasioController@getRasioYtd');
    $router->get('data-rasio-yoy','DashYpt\DashboardRasioController@getRasioYoY');
    $router->get('data-rasio-tahun','DashYpt\DashboardRasioController@getRasioTahun');

    // PIUTANG
    
    $router->get('data-piutang-box','DashYpt\DashboardPiutangController@getDataBox'); 
    $router->get('data-piutang-top','DashYpt\DashboardPiutangController@getTopPiutang'); 
    $router->get('data-piutang-bidang','DashYpt\DashboardPiutangController@getBidang');  
    $router->get('data-piutang-komposisi','DashYpt\DashboardPiutangController@getKomposisiPiutang');  
    $router->get('data-piutang-umur','DashYpt\DashboardPiutangController@getUmurPiutang');  
    $router->get('data-piutang-saldo','DashYpt\DashboardPiutangController@getTrendSaldoPiutang');  

    
    $router->get('data-piutang-unit-box','DashYpt\DashboardPiutangUnitController@getDataBox'); 
    $router->get('data-piutang-unit-komposisi','DashYpt\DashboardPiutangUnitController@getKomposisiPiutang');  
    $router->get('data-piutang-unit-umur','DashYpt\DashboardPiutangUnitController@getUmurPiutang');  
    $router->get('data-piutang-unit-saldo','DashYpt\DashboardPiutangUnitController@getTrendSaldoPiutang');  

    // DASH FINANCIAL TS
    $router->get('data-fp-ts-box','DashYpt\DashboardFPTsController@getDataBoxFirst');  
    $router->get('data-fp-ts-lr','DashYpt\DashboardFPTsController@getDataBoxLabaRugi'); 
    $router->get('data-fp-ts-pl','DashYpt\DashboardFPTsController@getDataBoxPerformLembaga'); 
    $router->get('data-fp-ts-pl-pp','DashYpt\DashboardFPTsController@getDataBoxPerformLembagaPP'); 

    $router->get('data-fp-ts-detail-perform','DashYpt\DashboardFPTsController@getDataPerformansiLembaga');  
    $router->get('data-fp-ts-detail-lembaga','DashYpt\DashboardFPTsController@getDataPerLembaga');  
    $router->get('data-fp-ts-detail-kelompok','DashYpt\DashboardFPTsController@getDataKelompokYoy');  
    $router->get('data-fp-ts-detail-akun','DashYpt\DashboardFPTsController@getDataKelompokAkun');  
    $router->get('data-fp-ts-detail-or-5tahun','DashYpt\DashboardFPTsController@getDataOR5Tahun');  



});



?>