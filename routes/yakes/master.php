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
    //masakun
    $router->get('masakun','Yakes\MasakunController@index');
    $router->post('masakun','Yakes\MasakunController@store');
    $router->put('masakun','Yakes\MasakunController@update');
    $router->delete('masakun','Yakes\MasakunController@destroy'); 

    //fs
    $router->get('fs','Yakes\FSController@index');
    $router->post('fs','Yakes\FSController@store');
    $router->put('fs','Yakes\FSController@update');
    $router->delete('fs','Yakes\FSController@destroy'); 

    //flagakun
    $router->get('flagakun','Yakes\FlagAkunController@index');
    $router->post('flagakun','Yakes\FlagAkunController@store');
    $router->put('flagakun','Yakes\FlagAkunController@update');
    $router->delete('flagakun','Yakes\FlagAkunController@destroy'); 

    //flagrelasi
    $router->get('getFlag','Yakes\FlagRelasiController@getFlag');
    $router->get('getAkunFlag/{kode_flag}','Yakes\FlagRelasiController@getAkunFlag');
    $router->get('getAkun','Yakes\FlagRelasiController@getAkun');    
    $router->get('viewAkun','Yakes\FlagRelasiController@viewAkun');    
    $router->put('flagrelasi','Yakes\FlagRelasiController@update');
    $router->delete('flagrelasi','Yakes\FlagRelasiController@destroy'); 

    //jurnal
    $router->post('jurnal','Yakes\JurSesuaiController@store');
    $router->put('jurnal','Yakes\JurSesuaiController@update');    
    $router->delete('jurnal','Yakes\JurSesuaiController@destroy');     
    $router->get('getNoBukti','Yakes\JurSesuaiController@getNoBukti');         
    $router->get('getAkun','Yakes\JurSesuaiController@getAkun');         
    $router->get('getPP','Yakes\JurSesuaiController@getPP');         
    $router->get('getTglServer','Yakes\JurSesuaiController@getTglServer');     
    $router->get('index','Yakes\JurSesuaiController@index');     
    $router->get('getBuktiDetail','Yakes\JurSesuaiController@getBuktiDetail');             
    
});



?>