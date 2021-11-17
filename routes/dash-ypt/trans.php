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
    $router->get('periode','DashYpt\TransferDataController@getPeriode');     
    $router->post('transfer-data','DashYpt\TransferDataController@store'); 
    
    $router->get('setting-grafik','DashYpt\SettingGrafikController@index');     
    $router->get('setting-grafik-detail','DashYpt\SettingGrafikController@show'); 
    $router->post('setting-grafik','DashYpt\SettingGrafikController@store');
    $router->put('setting-grafik','DashYpt\SettingGrafikController@update');    
    $router->delete('setting-grafik','DashYpt\SettingGrafikController@destroy');   
    $router->get('setting-grafik-neraca','DashYpt\SettingGrafikController@getNeraca');    
    $router->get('setting-grafik-klp','DashYpt\SettingGrafikController@getKlp');

    // RASIO
    $router->get('setting-rasio','DashYpt\SettingRasioController@index');     
    $router->get('setting-rasio-detail','DashYpt\SettingRasioController@show'); 
    $router->post('setting-rasio','DashYpt\SettingRasioController@store');
    $router->put('setting-rasio','DashYpt\SettingRasioController@update');    
    $router->delete('setting-rasio','DashYpt\SettingRasioController@destroy');   
    $router->get('setting-rasio-neraca','DashYpt\SettingRasioController@getNeraca');    
    $router->get('setting-rasio-klp','DashYpt\SettingRasioController@getKlp');

    // DATA PENDUKUNG
    $router->get('pendukung','DashYpt\PendukungController@index');     
    $router->get('pendukung-detail','DashYpt\PendukungController@show'); 
    $router->post('pendukung','DashYpt\PendukungController@store');
    $router->put('pendukung','DashYpt\PendukungController@update');    
    $router->delete('pendukung','DashYpt\PendukungController@destroy');   
    $router->get('pendukung-neraca','DashYpt\PendukungController@getNeraca');  
});





?>