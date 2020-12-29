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


$router->get('anggaran-export','Yakes\AnggaranController@export');    

$router->group(['middleware' => 'auth:yakes'], function () use ($router) {
    //jurnal
    $router->post('jurnal','Yakes\JurSesuaiController@store');
    $router->put('jurnal','Yakes\JurSesuaiController@update');    
    $router->delete('jurnal','Yakes\JurSesuaiController@destroy');     
    $router->get('getNoBukti','Yakes\JurSesuaiController@getNoBukti');                 
    $router->get('index','Yakes\JurSesuaiController@index');     
    $router->get('getBuktiDetail','Yakes\JurSesuaiController@getBuktiDetail'); 
            
    $router->get('periode','Yakes\TransferDataController@getPeriode');     
    $router->post('transfer-data','Yakes\TransferDataController@store'); 

    $router->get('tahun','Yakes\AnggaranController@getTahun'); 
    $router->get('anggaran','Yakes\AnggaranController@index');          
    $router->post('anggaran-upload','Yakes\AnggaranController@importExcel'); 
    $router->get('anggaran-load','Yakes\AnggaranController@loadAnggaran');    
    $router->post('anggaran','Yakes\AnggaranController@store');    

    $router->post('sync-glitem','Yakes\GlitemController@store');   
    $router->post('upload-glitem','Yakes\GlitemController@importExcel');    
    $router->post('execute-glitem','Yakes\GlitemController@executeSQL');    

    //hrkaryawan
    $router->get('cariNik','Yakes\HrKaryawanController@cariStsEdu');
    $router->get('hrKaryawan','Yakes\HrKaryawanController@index');
    $router->post('hrKaryawan','Yakes\HrKaryawanController@store');
    $router->put('hrKaryawan','Yakes\HrKaryawanController@update');
    $router->delete('hrKaryawan','Yakes\HrKaryawanController@destroy'); 
    
    $router->post('hrKaryawan-import','Yakes\HrKaryawanController@importExcel');
    $router->get('hrKaryawan-tmp','Yakes\HrKaryawanController@getKaryawanTmp');

    
    //dash Peserta
    $router->post('dashPeserta','Yakes\PesertaController@store');
    $router->post('dashPeserta-import','Yakes\PesertaController@importExcel');
    $router->get('dashPeserta-tmp','Yakes\PesertaController@getPesertaTmp');

    
    //dash Kunjungan
    $router->post('dashKunjungan','Yakes\KunjunganController@store');
    $router->post('dashKunjungan-import','Yakes\KunjunganController@importExcel');
    $router->get('dashKunjungan-tmp','Yakes\KunjunganController@getKunjunganTmp');

    //dash Top Six
    $router->post('dashTopSix','Yakes\TopSixController@store');
    $router->post('dashTopSix-import','Yakes\TopSixController@importExcel');
    $router->get('dashTopSix-tmp','Yakes\TopSixController@getTopSixTmp');

    $router->post('dashSDMCulture','Yakes\SDMCultureController@store');
    $router->post('dashSDMCulture-import','Yakes\SDMCultureController@importExcel');
    $router->get('dashSDMCulture-tmp','Yakes\SDMCultureController@getSDMCultureTmp');

    $router->post('dashKontrakManage','Yakes\KontrakManagemenController@store');
    $router->post('dashKontrakManage-import','Yakes\KontrakManagemenController@importExcel');
    $router->get('dashKontrakManage-tmp','Yakes\KontrakManagemenController@getKontrakManagemenTmp');

    $router->post('dashBinaSehat','Yakes\BinaSehatController@store');
    $router->post('dashBinaSehat-import','Yakes\BinaSehatController@importExcel');
    $router->get('dashBinaSehat-tmp','Yakes\BinaSehatController@getBinaSehatTmp');

    $router->get('setting-grafik','Yakes\SettingGrafikController@index');     
    $router->get('setting-grafik-detail','Yakes\SettingGrafikController@show'); 
    $router->post('setting-grafik','Yakes\SettingGrafikController@store');
    $router->put('setting-grafik','Yakes\SettingGrafikController@update');    
    $router->delete('setting-grafik','Yakes\SettingGrafikController@destroy');   
    $router->get('setting-grafik-neraca','Yakes\SettingGrafikController@getNeraca');    
    $router->get('setting-grafik-klp','Yakes\SettingGrafikController@getKlp');
    
    $router->get('setting-rasio','Yakes\SettingRasioController@index');     
    $router->get('setting-rasio-detail','Yakes\SettingRasioController@show'); 
    $router->post('setting-rasio','Yakes\SettingRasioController@store');
    $router->put('setting-rasio','Yakes\SettingRasioController@update');    
    $router->delete('setting-rasio','Yakes\SettingRasioController@destroy');   
    $router->get('setting-rasio-neraca','Yakes\SettingRasioController@getNeraca');    
    $router->get('setting-rasio-klp','Yakes\SettingRasioController@getKlp');

    $router->post('arus-kas','Yakes\ArusKasController@store');
    $router->post('arus-kas-import','Yakes\ArusKasController@importExcel');
    $router->get('arus-kas-tmp','Yakes\ArusKasController@getArusKasTmp');

});

$router->get('hrKaryawan-export','Yakes\HrKaryawanController@export');
$router->get('dashPeserta-export','Yakes\PesertaController@export');
$router->get('dashKunjungan-export','Yakes\KunjunganController@export');
$router->get('dashTopSix-export','Yakes\TopSixController@export');
$router->get('dashSDMCulture-export','Yakes\SDMCultureController@export');
$router->get('dashKontrakManage-export','Yakes\KontrakManagemenController@export');
$router->get('dashBinaSehat-export','Yakes\BinaSehatController@export');
$router->get('arus-kas-export','Yakes\ArusKasController@export');




?>