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

    //hrkaryawan
    $router->get('cariNik','Yakes\HrKaryawanController@cariStsEdu');
    $router->get('hrKaryawan','Yakes\HrKaryawanController@index');
    $router->post('hrKaryawan','Yakes\HrKaryawanController@store');
    $router->put('hrKaryawan','Yakes\HrKaryawanController@update');
    $router->delete('hrKaryawan','Yakes\HrKaryawanController@destroy'); 

    //dashbord akun
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
    
});



?>