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

});



?>