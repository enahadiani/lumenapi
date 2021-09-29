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
   
    //Data Dok Jenis
    $router->get('dok-jenis','Bdh\JenisDokController@index');
    $router->post('dok-jenis','Bdh\JenisDokController@store');
    $router->put('dok-jenis','Bdh\JenisDokController@update');
    $router->delete('dok-jenis','Bdh\JenisDokController@destroy'); 
});



?>