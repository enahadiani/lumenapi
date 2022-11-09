<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);

$router->group(['middleware' => 'cors'], function () use ($router) {
    $router->post('login', 'AuthController@loginSju');
});

$router->group(['middleware' => 'auth:sju'], function () use ($router) {
    $router->get('data-cob', 'Sju\ApiController@getDataCOB'); 
    $router->get('data-tertanggung', 'Sju\ApiController@getDataTertanggung'); 
    $router->get('data-penanggung', 'Sju\ApiController@getDataPenanggung'); 

    $router->get('polis', 'Sju\ApiController@getDataPolis'); 

});