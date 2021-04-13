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
    $router->get('pencapaianYoY', 'Ypt\DashboardController@pencapaianYoY');
    $router->get('rkaVSReal', 'Ypt\DashboardController@rkaVSReal');
    $router->get('growthRKA', 'Ypt\DashboardController@growthRKA');
    $router->get('growthReal', 'Ypt\DashboardController@growthReal');


    //PAGE 2
    
    $router->get('komposisiPdpt', 'Ypt\DashboardController@komposisiPdpt');
    $router->get('rkaVSRealPdpt', 'Ypt\DashboardController@rkaVSRealPdpt');
    $router->get('rkaVSRealPdptRp', 'Ypt\DashboardController@rkaVSRealPdptRp');
    $router->get('totalPdpt/{periode}', 'Ypt\DashboardController@totalPdpt');


    //PAGE 2
    
    $router->get('komposisiBeban', 'Ypt\DashboardController@komposisiBeban');
    $router->get('rkaVSRealBeban', 'Ypt\DashboardController@rkaVSRealBeban');
    $router->get('rkaVSRealBebanRp', 'Ypt\DashboardController@rkaVSRealBebanRp');
    $router->get('totalBeban', 'Ypt\DashboardController@totalBeban');

    //PAGE 4 Detail Pendapatan
    
    $router->get('pdptFakultas', 'Ypt\DashboardController@pdptFakultas');
    $router->get('detailPdpt', 'Ypt\DashboardController@detailPdpt');
    $router->get('pdptFakultasNon', 'Ypt\DashboardController@pdptFakultasNon');
    $router->get('detailPdptNon', 'Ypt\DashboardController@detailPdptNon');

    
    $router->get('pdptJurusan', 'Ypt\DashboardController@pdptJurusan');
    $router->get('detailPdptJurusan', 'Ypt\DashboardController@detailPdptJurusan');

    //PAGE 5 Detail Beban
    
    $router->get('bebanFakultas', 'Ypt\DashboardController@bebanFakultas');
    $router->get('detailBeban', 'Ypt\DashboardController@detailBeban');
    
    $router->get('bebanFakultasNon', 'Ypt\DashboardController@bebanFakultasNon');
    $router->get('detailBebanNon', 'Ypt\DashboardController@detailBebanNon');
    
    $router->get('bebanJurusan', 'Ypt\DashboardController@bebanJurusan');
    $router->get('detailBebanJurusan', 'Ypt\DashboardController@detailBebanJurusan');

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
    $router->get('tahun', 'Ypt\DashboardController@getTahun');
    
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

    $router->get('ms-pengembangan-rka','Ypt\DashboardController@msPengembanganRKA');
    $router->get('ms-pengembangan-rka-dir','Ypt\DashboardController@msPengembanganRKADir');
    $router->get('ms-pengembangan-komposisi','Ypt\DashboardController@msPengembanganKomposisi');
    
    $router->get('laba-rugi-5tahun','Ypt\DashboardController@getLabaRugi5Tahun');
    $router->get('laba-rugi-5tahun-yoy','Ypt\DashboardController@getLabaRugi5TahunYoY');
    $router->get('pend-5tahun','Ypt\DashboardController@getPend5Tahun');
    $router->get('pend-5tahun-yoy','Ypt\DashboardController@getPend5TahunYoY');
    $router->get('pend-5tahun-tf','Ypt\DashboardController@getPend5TahunTF');
    $router->get('pend-5tahun-tf-yoy','Ypt\DashboardController@getPend5TahunTFYoY');
    $router->get('pend-5tahun-ntf','Ypt\DashboardController@getPend5TahunNTF');
    $router->get('pend-5tahun-ntf-yoy','Ypt\DashboardController@getPend5TahunNTFYoY');
    $router->get('pend-5tahun-komposisi','Ypt\DashboardController@getPend5TahunKomposisi');
    $router->get('pend-5tahun-komposisi-yoy','Ypt\DashboardController@getPend5TahunKomposisiYoY');
    $router->get('pend-5tahun-growth','Ypt\DashboardController@getPend5TahunGrowth');
    $router->get('pend-5tahun-growth-yoy','Ypt\DashboardController@getPend5TahunGrowthYoY');
    
    $router->get('beban-5tahun','Ypt\DashboardController@getBeban5Tahun');
    $router->get('beban-5tahun-yoy','Ypt\DashboardController@getBeban5TahunYoY');
    $router->get('beban-5tahun-sdm','Ypt\DashboardController@getBeban5TahunSDM');
    $router->get('beban-5tahun-sdm-yoy','Ypt\DashboardController@getBeban5TahunSDMYoY');
    $router->get('beban-5tahun-non-sdm','Ypt\DashboardController@getBeban5TahunNonSDM');
    $router->get('beban-5tahun-non-sdm-yoy','Ypt\DashboardController@getBeban5TahunNonSDMYoY');
    $router->get('beban-5tahun-komposisi','Ypt\DashboardController@getBeban5TahunKomposisi');
    $router->get('beban-5tahun-komposisi-yoy','Ypt\DashboardController@getBeban5TahunKomposisiYoY');
    $router->get('beban-5tahun-growth','Ypt\DashboardController@getBeban5TahunGrowth');
    $router->get('beban-5tahun-growth-yoy','Ypt\DashboardController@getBeban5TahunGrowthYoY');

    $router->get('shu-5tahun','Ypt\DashboardController@getSHU5Tahun');
    $router->get('shu-5tahun-yoy','Ypt\DashboardController@getSHU5TahunYoY');

    $router->get('ms-pend-capai','Ypt\DashboardController@getPendCapai');
    $router->get('ms-pend-capai-klp','Ypt\DashboardController@getPendCapaiKlp');
    
    $router->get('ms-beban-capai','Ypt\DashboardController@getBebanCapai');
    $router->get('ms-beban-capai-klp','Ypt\DashboardController@getBebanCapaiKlp');

    $router->get('ms-bank','Ypt\DashboardController@getDaftarBank');
    $router->get('ms-kasbank','Ypt\DashboardController@getMSKasBank');
    $router->get('ms-modal','Ypt\DashboardController@getMSModal');
    $router->get('ms-shu','Ypt\DashboardController@getSHUDetail');

    $router->get('ms-aset','Ypt\DashboardController@getMSAset');
    $router->get('ms-hutang','Ypt\DashboardController@getMSHutang');
    $router->get('video-list','Ypt\DashboardController@getListVideo');

    // DETAIL MANAGEMENT SYSTEM
    
    $router->get('ms-piutang','Ypt\DashboardController@getMSPiutang');
    $router->get('drill-fakultas', 'Ypt\DashboardController@dataDrillFakultas');
    $router->get('detail-drill-fakultas', 'Ypt\DashboardController@detailDrillFakultas');
    
    $router->get('drill-direktorat', 'Ypt\DashboardController@dataDrillDirektorat');
    $router->get('detail-drill-direktorat', 'Ypt\DashboardController@detailDrillDirektorat');
    
    $router->get('drill-pp', 'Ypt\DashboardController@dataDrillPP');
    $router->get('detail-drill-pp', 'Ypt\DashboardController@detailDrillPP');

    
    $router->get('financial-target', 'Ypt\DashboardController@getTarget');
    $router->post('note', 'Ypt\DashboardController@updateNoteTarget');


});



?>