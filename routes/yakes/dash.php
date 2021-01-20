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

$router->group(['middleware' => 'auth:yakes'], function () use ($router) {
  
//hr dashborad
    //stsorganik
    $router->get('cariStsOrganik','Yakes\StsOrganikController@cariStsOrganik');
    $router->get('stsOrganik','Yakes\StsOrganikController@index');
    $router->post('stsOrganik','Yakes\StsOrganikController@store');
    $router->put('stsOrganik','Yakes\StsOrganikController@update');
    $router->delete('stsOrganik','Yakes\StsOrganikController@destroy'); 

    //stsmedis
    $router->get('cariStsMedis','Yakes\StsMedisController@cariStsMedis');
    $router->get('stsMedis','Yakes\StsMedisController@index');
    $router->post('stsMedis','Yakes\StsMedisController@store');
    $router->put('stsMedis','Yakes\StsMedisController@update');
    $router->delete('stsMedis','Yakes\StsOrganikController@destroy'); 

    //stsedu
    $router->get('cariStsEdu','Yakes\StsEduController@cariStsEdu');
    $router->get('stsEdu','Yakes\StsEduController@index');
    $router->post('stsEdu','Yakes\StsEduController@store');
    $router->put('stsEdu','Yakes\StsEduController@update');
    $router->delete('stsEdu','Yakes\StsEduController@destroy');
    
    //demografi
    $router->get('cariDemog','Yakes\DemogController@cariDemog');
    $router->get('demog','Yakes\DemogController@index');
    $router->post('demog','Yakes\DemogController@store');
    $router->put('demog','Yakes\DemogController@update');
    $router->delete('demog','Yakes\DemogController@destroy');

    //dashbord akun    
    $router->get('dataKunjLayanan','Yakes\DashAkunController@dataKunjLayanan');
    $router->get('dataKunjTotal','Yakes\DashAkunController@dataKunjTotal');
    $router->get('dataClaimant','Yakes\DashAkunController@dataClaimant');
    $router->get('dataBPCCtotal','Yakes\DashAkunController@dataBPCCtotal');
    $router->get('dataBPCClayanan','Yakes\DashAkunController@dataBPCClayanan');
    $router->get('dataBebanYtd','Yakes\DashAkunController@dataBebanYtd');
    $router->get('dataBPytd','Yakes\DashAkunController@dataBPytd');
    $router->get('dataCCytd','Yakes\DashAkunController@dataCCytd');
    $router->get('getFilterTahunDash','Yakes\FilterController@getFilterTahunDash');
    $router->get('dataBeban','Yakes\DashAkunController@dataBeban');
    $router->get('dataPdpt','Yakes\DashAkunController@dataPdpt');

    //dashbord SDM
    $router->get('dataOrganik','Yakes\DashSDMController@dataOrganik');
    $router->get('dataDemog','Yakes\DashSDMController@dataDemog');
    $router->get('dataGender','Yakes\DashSDMController@dataGender');
    $router->get('dataMedis','Yakes\DashSDMController@dataMedis');
    $router->get('dataDokter','Yakes\DashSDMController@dataDokter');
    $router->get('dataEdu','Yakes\DashSDMController@dataEdu');    

    //dashbord bpjs
    $router->get('dash-bpjs-kapitasiregdetail','Yakes\DashBPJSController@dataKapitasiRegDetail');
    $router->get('dash-bpjs-kapitasireg','Yakes\DashBPJSController@dataKapitasiRegional');
    $router->get('dataClaim','Yakes\DashBPJSController@dataClaim');
    $router->get('dataKapitasi','Yakes\DashBPJSController@dataKapitasi');    
    $router->get('dataPremiLokasi','Yakes\DashBPJSController@dataPremiLokasi');
    $router->get('dataBPCCLokasi','Yakes\DashBPJSController@dataBPCCLokasi');
    $router->get('dataKapitasiLokasi','Yakes\DashBPJSController@dataKapitasiLokasi');
    $router->get('dataClaimLokasi','Yakes\DashBPJSController@dataClaimLokasi');

    $router->get('rasio','Yakes\DashRasioController@dataRasio');
    $router->get('klp-rasio','Yakes\DashRasioController@klpRasio');

    // Dashboard Investasi
    // FILTER
    
    $router->get('param-default','Yakes\DashInvesController@getParamDefault');
    $router->get('filter-plan','Yakes\DashInvesController@getFilterPlan');
    $router->get('filter-klp','Yakes\DashInvesController@getFilterKlp'); //komposisi
    $router->get('filter-kolom','Yakes\DashInvesController@getFilKolom');
    $router->post('filter-kolom','Yakes\DashInvesController@simpanFilterKolom');
    $router->post('update-tgl','Yakes\DashInvesController@updateTgl');
    $router->post('update-param','Yakes\DashInvesController@updateParam');
    // GLOBAL MARKET
    $router->get('global-market','Yakes\DashInvesController@getKatalis');
     
    // PERGERAKAN BINDO & JCI
    $router->get('chart-index','Yakes\DashInvesController@getBMark');
    $router->get('global-index','Yakes\DashInvesController@getGlobalIndex');
    $router->get('bond-index','Yakes\DashInvesController@getBondIndex');

    // ALOKASI ASET
    $router->get('persen-aset','Yakes\DashInvesController@getPersenAset');
    $router->get('table-alokasi','Yakes\DashInvesController@getTableAlokasi'); 
    $router->get('roi-kkp','Yakes\DashInvesController@getRoiKkp');

    // REALISASI INVEST
    $router->get('portofolio','Yakes\DashInvesController@getPortofolio'); 
    $router->get('persen','Yakes\DashInvesController@getBatasAlokasi'); 
    $router->get('aset-chart','Yakes\DashInvesController@getAset'); 

    // ROI
    $router->get('table-real-hasil','Yakes\DashInvesController@getRealHasil');  
    $router->get('table-roi','Yakes\DashInvesController@getROIReal'); 

    // PLAN ASET
    $router->get('plan-aset','Yakes\DashInvesController@getPlanAset');

    // KINERJA
    $router->get('kinerja-plan-aset','Yakes\DashInvesController@getKinerja');
    $router->get('etf','Yakes\DashInvesController@getKinerjaETF');
    $router->get('bindo','Yakes\DashInvesController@getKinerjaBindo');
    $router->get('bmark','Yakes\DashInvesController@getKinerjaBMark');

    // CASHOUT
    $router->get('cash-out','Yakes\DashInvesController@getCashOut');

    // Portofolio Fix Income
    $router->get('table-portofolio','Yakes\DashInvesController@getTableObli');
    $router->get('komposisi','Yakes\DashInvesController@getKomposisi');
    $router->get('rating','Yakes\DashInvesController@getRating');

    // TENOR
    $router->get('komposisi','Yakes\DashInvesController@getKomposisiTenor');
    $router->get('tenor','Yakes\DashInvesController@getTenor');
    
});



?>