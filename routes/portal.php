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
    //approval dev
    $router->post('login', 'AuthController@loginSatpam');
});


$router->group(['middleware' => 'jwt.portal'], function () use ($router) {
    $router->get('profile', 'AdminSatpamController@profile');
    // $router->get('users/{id}', 'AdminSatpamController@singleUser');
    // $router->get('users', 'AdminSatpamController@allUsers');
    // $router->get('cek-payload', 'AdminSatpamController@cekPayload');
});



?>