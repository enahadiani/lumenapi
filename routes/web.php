<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
    // $router->post('register', 'AuthController@register');
     // Matches "/api/login
    $router->post('login', 'AuthController@login');

    // Matches "/api/profile
    $router->get('profile', 'UserController@profile');

    // Matches "/api/users/1 
    //get one user by id
    $router->get('users/{id}', 'UserController@singleUser');

    // Matches "/api/users
    $router->get('users', 'UserController@allUsers');
    
    //Pengajuan
    $router->get('aju', 'apv\ApvController@pengajuan');
    $router->get('ajudet/{no_aju}', 'apv\ApvController@detail');
    $router->get('ajurek/{no_bukti}', 'apv\ApvController@rekening');
    $router->get('ajujurnal/{no_aju}', 'apv\ApvController@jurnal');

    //Approval SM
    
    $router->get('appsm', 'apv\ApvController@appSM');


});