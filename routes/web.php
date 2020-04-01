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

});

$router->group(['middleware' => 'auth','prefix' => 'api'], function () use ($router) {
    // Matches "/api/profile
    $router->get('profile', 'UserController@profile');

    // Matches "/api/users/1 
    //get one user by id
    $router->get('users/{id}', 'UserController@singleUser');

    // Matches "/api/users
    $router->get('users', 'UserController@allUsers');
    
    //Pengajuan
    $router->get('ajusm', 'Approval\ApprovalController@pengajuan');
    $router->get('ajufin', 'Approval\ApprovalController@pengajuanfinal');
    $router->get('ajudir', 'Approval\ApprovalController@pengajuandir');
    $router->get('ajuhistory', 'Approval\ApprovalController@pengajuanhistory');

    $router->get('ajudet/{no_aju}', 'Approval\ApprovalController@detail');
    $router->get('ajurek/{no_aju}', 'Approval\ApprovalController@rekening');
    $router->get('ajujurnal/{no_aju}', 'Approval\ApprovalController@jurnal');

    //Approval 
    
    $router->post('appsm', 'Approval\ApprovalController@approvalSM');
    $router->post('appfin', 'Approval\ApprovalController@approvalFinal');
    $router->post('appdir', 'Approval\ApprovalController@approvalDir');

    //Menu
    
    $router->get('menu/{kode_klp}', 'Gl\MenuController@show');

    //FS
    
    $router->get('fs','Gl\FsController@index');
    $router->get('fs/{id}','Gl\FsController@show');
    $router->post('fs','Gl\FsController@store');
    $router->put('fs/{id}','Gl\FsController@update');
    $router->delete('fs/{id}','Gl\FsController@destroy');

});