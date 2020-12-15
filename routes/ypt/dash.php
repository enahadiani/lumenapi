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
  
	$router->get('periode', 'Ypt\DashboardController@getPeriode');
	
	
    //PAGE 1
    $router->get('pencapaianYoY/{periode}', 'Ypt\DashboardController@pencapaianYoY');
    $router->get('rkaVSReal/{periode}', 'Ypt\DashboardController@rkaVSReal');
    $router->get('growthRKA/{periode}', 'Ypt\DashboardController@growthRKA');
    $router->get('growthReal/{periode}', 'Ypt\DashboardController@growthReal');


    //PAGE 2
    
    $router->get('komposisiPdpt/{periode}', 'Ypt\DashboardController@komposisiPdpt');
    $router->get('rkaVSRealPdpt/{periode}', 'Ypt\DashboardController@rkaVSRealPdpt');
    $router->get('totalPdpt/{periode}', 'Ypt\DashboardController@totalPdpt');


    //PAGE 2
    
    $router->get('komposisiBeban/{periode}', 'Ypt\DashboardController@komposisiBeban');
    $router->get('rkaVSRealBeban/{periode}', 'Ypt\DashboardController@rkaVSRealBeban');
    $router->get('totalBeban/{periode}', 'Ypt\DashboardController@totalBeban');

    //PAGE 4 Detail Pendapatan
    
    $router->get('pdptFakultas/{periode}/{kode_neraca}', 'Ypt\DashboardController@pdptFakultas');
    $router->get('detailPdpt/{periode}/{kode_neraca}', 'Ypt\DashboardController@detailPdpt');

    
    $router->get('pdptJurusan/{periode}/{kode_neraca}/{kode_bidang}', 'Ypt\DashboardController@pdptJurusan');
    $router->get('detailPdptJurusan/{periode}/{kode_neraca}/{kode_bidang}/{tahun}', 'Ypt\DashboardController@detailPdptJurusan');

    //PAGE 5 Detail Beban
    
    $router->get('bebanFakultas/{periode}/{kode_neraca}', 'Ypt\DashboardController@bebanFakultas');
    $router->get('detailBeban/{periode}/{kode_neraca}', 'Ypt\DashboardController@detailBeban');

    
    $router->get('bebanJurusan/{periode}/{kode_neraca}/{kode_bidang}', 'Ypt\DashboardController@bebanJurusan');
    $router->get('detailBebanJurusan/{periode}/{kode_neraca}/{kode_bidang}/{tahun}', 'Ypt\DashboardController@detailBebanJurusan');

    $router->get('user_device','UserDeviceController@index');
    $router->get('user_device/{nik}','UserDeviceController@show');
    $router->post('user_device','UserDeviceController@store');
    $router->put('user_device/{nik}','UserDeviceController@update');
    $router->delete('user_device/{nik}','UserDeviceController@destroy');
    
    $router->get('rka','Ypt\DashboardController@getBCRKA');
    $router->get('growth-rka','Ypt\DashboardController@getBCGrowthRKA');
    $router->get('tuition','Ypt\DashboardController@getBCTuition');
    $router->get('growth-tuition','Ypt\DashboardController@getBCGrowthTuition');
    $router->get('rka-persen','Ypt\DashboardController@getBCRKAPersen');
    $router->get('tuition-persen','Ypt\DashboardController@getBCTuitionPersen');
    
    $router->post('notif-pusher', 'Ypt\NotifController@sendPusher');
    $router->get('notif-pusher', 'Ypt\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Ypt\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminYptKugController@searchForm');
    $router->get('search-form-list', 'AdminYptKugController@searchFormList');

    $router->get('periode', 'Ypt\DashboardController@getPeriode');
    
    $router->get('komponen-investasi','Ypt\DashboardController@komponenInvestasi');
    $router->get('rka-real-investasi','Ypt\DashboardController@rkaVSRealInvestasi');
    $router->get('penyerapan-investasi','Ypt\DashboardController@penyerapanInvestasi');

    // Management System
    $router->get('profit-loss','Ypt\DashboardController@profitLoss');
    $router->get('fx-position','Ypt\DashboardController@fxPosition');
    $router->get('penyerapan-beban','Ypt\DashboardController@penyerapanBeban');
    $router->get('debt','Ypt\DashboardController@debt');
    $router->get('kelola-keuangan','Ypt\DashboardController@kelolaKeuangan');
    $router->get('penjualan-pin','Ypt\DashboardController@penjualanPin');

    
    $router->get('ms-pendapatan','Ypt\DashboardController@msPendapatan');
    $router->get('ms-pendapatan-klp','Ypt\DashboardController@msPendapatanKlp');

    $router->get('ms-beban','Ypt\DashboardController@msBeban');
    $router->get('ms-beban-klp','Ypt\DashboardController@msBebanKlp');


});



?>