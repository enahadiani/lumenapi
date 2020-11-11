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

});



?>