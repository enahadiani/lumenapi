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


$router->group(['middleware' => 'auth:toko'], function () use ($router) {
   //kunjungan
//    $router->post('kunj','Yakes\JurSesuaiController@store');
//    $router->put('kunj','Yakes\JurSesuaiController@update');    
//    $router->delete('kunj','Yakes\JurSesuaiController@destroy');     
//    $router->get('getNoBukti','Yakes\JurSesuaiController@getNoBukti');                 
//    $router->get('index','Yakes\JurSesuaiController@index');        
});



?>