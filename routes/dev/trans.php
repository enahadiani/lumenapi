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


$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('tagihan','Dev\TagihanController@index');
    $router->get('tagihan-detail','Dev\TagihanController@show');
    $router->post('tagihan','Dev\TagihanController@store');
    $router->put('tagihan','Dev\TagihanController@update');
    $router->delete('tagihan','Dev\TagihanController@destroy');
    $router->get('tagihan-load','Dev\TagihanController@load');
    
    $router->get('bayar','Dev\BayarController@index');
    $router->get('bayar-detail','Dev\BayarController@show');
    $router->post('bayar','Dev\BayarController@store');
    $router->put('bayar','Dev\BayarController@update');
    $router->delete('bayar','Dev\BayarController@destroy');    
    

});




?>