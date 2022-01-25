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

$router->group(['middleware' => 'auth:tarbak'], function () use ($router) {

    $router->get('data-fp-box','DashTarbak\DashboardFPController@getDataBoxFirst');  
    $router->get('v2/data-fp-box','DashTarbak\DashboardFPV2Controller@getDataBoxFirst');  
    $router->get('data-fp-pdpt','DashTarbak\DashboardFPController@getDataBoxPdpt');  
    $router->get('v2/data-fp-pdpt','DashTarbak\DashboardFPV2Controller@getDataPdpt');  
    $router->get('data-fp-beban','DashTarbak\DashboardFPController@getDataBoxBeban');  
    $router->get('v2/data-fp-beban','DashTarbak\DashboardFPV2Controller@getDataBeban');  
    $router->get('data-fp-shu','DashTarbak\DashboardFPController@getDataBoxShu');  
    $router->get('v2/data-fp-shu','DashTarbak\DashboardFPV2Controller@getDataSHU');  
    $router->get('data-fp-or','DashTarbak\DashboardFPController@getDataBoxOr'); 
    $router->get('v2/data-fp-or','DashTarbak\DashboardFPV2Controller@getDataOR');  
    $router->get('data-fp-lr','DashTarbak\DashboardFPController@getDataBoxLabaRugi'); 
    $router->get('data-fp-pl','DashTarbak\DashboardFPController@getDataBoxPerformLembaga'); 

    $router->get('data-fp-detail-perform','DashTarbak\DashboardFPController@getDataPerformansiLembaga');  
    $router->get('data-fp-detail-lembaga','DashTarbak\DashboardFPController@getDataPerLembaga');  
    $router->get('data-fp-detail-kelompok','DashTarbak\DashboardFPController@getDataKelompokYoy');  
    $router->get('data-fp-detail-akun','DashTarbak\DashboardFPController@getDataKelompokAkun');  
    $router->get('data-fp-detail-or-5tahun','DashTarbak\DashboardFPController@getDataOR5Tahun');  

    $router->get('data-ccr-box','DashTarbak\DashboardCCRController@getDataBox'); 
    $router->get('data-ccr-top','DashTarbak\DashboardCCRController@getTopCCR');  
    $router->get('data-ccr-trend','DashTarbak\DashboardCCRController@getTrendCCR');  
    $router->get('data-ccr-trend-saldo','DashTarbak\DashboardCCRController@getTrendSaldoPiutang');
    $router->get('data-ccr-bidang','DashTarbak\DashboardCCRController@getBidang');  
    $router->get('data-ccr-umur-piutang','DashTarbak\DashboardCCRController@getUmurPiutang');
    
    $router->get('data-cf-box','DashTarbak\DashboardCFController@getDataBox');  
    $router->get('data-cf-chart-bulanan','DashTarbak\DashboardCFController@getCashFlowBulanan');
    $router->get('data-cf-soakhir','DashTarbak\DashboardCFController@getSoAkhirPerLembaga');  

    // INVEST
    $router->get('data-inves-box','DashTarbak\DashboardInvesController@getDataBox'); 
    $router->get('data-inves-serap-agg','DashTarbak\DashboardInvesController@getSerapAgg');  
    $router->get('data-inves-nilai-aset','DashTarbak\DashboardInvesController@getNilaiAsetChart');  
    $router->get('data-inves-agg-lembaga','DashTarbak\DashboardInvesController@getAggPerLembagaChart');  
    // END INVEST
    
    // RASIO
    $router->get('data-rasio-jenis','DashTarbak\DashboardRasioController@getKlpRasio');
    $router->get('data-rasio-lembaga','DashTarbak\DashboardRasioController@getLokasi');
    $router->get('data-rasio-ytd','DashTarbak\DashboardRasioController@getRasioYtd');
    $router->get('data-rasio-yoy','DashTarbak\DashboardRasioController@getRasioYoY');
    $router->get('data-rasio-tahun','DashTarbak\DashboardRasioController@getRasioTahun');

    // PIUTANG
    
    $router->get('data-piutang-box','DashTarbak\DashboardPiutangController@getDataBox'); 
    $router->get('data-piutang-top','DashTarbak\DashboardPiutangController@getTopPiutang'); 
    $router->get('data-piutang-bidang','DashTarbak\DashboardPiutangController@getBidang');  
    $router->get('data-piutang-komposisi','DashTarbak\DashboardPiutangController@getKomposisiPiutang');  
    $router->get('data-piutang-umur','DashTarbak\DashboardPiutangController@getUmurPiutang');  
    $router->get('data-piutang-saldo','DashTarbak\DashboardPiutangController@getTrendSaldoPiutang');  


});



?>