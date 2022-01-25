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
    $router->get('periode','DashTarbak\TransferDataController@getPeriode');     
    $router->post('transfer-data','DashTarbak\TransferDataController@store'); 
    
    $router->get('setting-grafik','DashTarbak\SettingGrafikController@index');     
    $router->get('setting-grafik-detail','DashTarbak\SettingGrafikController@show'); 
    $router->post('setting-grafik','DashTarbak\SettingGrafikController@store');
    $router->put('setting-grafik','DashTarbak\SettingGrafikController@update');    
    $router->delete('setting-grafik','DashTarbak\SettingGrafikController@destroy');   
    $router->get('setting-grafik-neraca','DashTarbak\SettingGrafikController@getNeraca');    
    $router->get('setting-grafik-klp','DashTarbak\SettingGrafikController@getKlp');

    // RASIO
    $router->get('setting-rasio','DashTarbak\SettingRasioController@index');     
    $router->get('setting-rasio-detail','DashTarbak\SettingRasioController@show'); 
    $router->post('setting-rasio','DashTarbak\SettingRasioController@store');
    $router->put('setting-rasio','DashTarbak\SettingRasioController@update');    
    $router->delete('setting-rasio','DashTarbak\SettingRasioController@destroy');   
    $router->get('setting-rasio-neraca','DashTarbak\SettingRasioController@getNeraca');    
    $router->get('setting-rasio-klp','DashTarbak\SettingRasioController@getKlp');

    // DATA PENDUKUNG
    $router->get('pendukung','DashTarbak\PendukungController@index');     
    $router->get('pendukung-detail','DashTarbak\PendukungController@show'); 
    $router->post('pendukung','DashTarbak\PendukungController@store');
    $router->put('pendukung','DashTarbak\PendukungController@update');    
    $router->delete('pendukung','DashTarbak\PendukungController@destroy');   
    $router->get('pendukung-neraca','DashTarbak\PendukungController@getNeraca');  
});





?>