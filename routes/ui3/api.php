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


$router->group(['middleware' => 'cors'], function () use ($router) {
    $router->post('login', 'AuthController@loginUi3');
    $router->get('hash-pass-nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
});

$router->group(['middleware' => 'auth:ui3'], function () use ($router) {
    $router->post('aktiva-tetap', 'Ui3\AktapController@store');

});



?>