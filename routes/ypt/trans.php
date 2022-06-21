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
  
            
    $router->get('periode','Ypt\TransferDataController@getPeriode');     
    $router->post('transfer-data','Ypt\TransferDataController@store'); 

    $router->get('setting-grafik','Ypt\SettingGrafikController@index');     
    $router->get('setting-grafik-detail','Ypt\SettingGrafikController@show'); 
    $router->post('setting-grafik','Ypt\SettingGrafikController@store');
    $router->put('setting-grafik','Ypt\SettingGrafikController@update');    
    $router->delete('setting-grafik','Ypt\SettingGrafikController@destroy');   
    $router->get('setting-grafik-neraca','Ypt\SettingGrafikController@getNeraca');    
    $router->get('setting-grafik-klp','Ypt\SettingGrafikController@getKlp');
    
    $router->get('hash-pass-menu-lokasi','Ypt\HashPasswordController@getLokasi');
    $router->get('hash-pass-menu','Ypt\HashPasswordController@index');
    $router->get('hash-pass-menu-batch','Ypt\HashPasswordController@getBatch');
   

});





?>