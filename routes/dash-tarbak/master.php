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
    
    //fs
    $router->get('listFSAktif','DashTarbak\FSController@listFSAktif');         
    $router->get('cariFSAktif','DashTarbak\FSController@cariFSAktif');
    $router->get('fs','DashTarbak\FSController@index');
    $router->post('fs','DashTarbak\FSController@store');
    $router->put('fs','DashTarbak\FSController@update');
    $router->delete('fs','DashTarbak\FSController@destroy'); 
});



?>