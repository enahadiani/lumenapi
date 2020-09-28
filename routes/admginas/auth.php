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
    $router->post('login', 'AuthController@loginAdmGinas');
    $router->get('hash-pass', 'AuthController@hashPasswordAdmGinas');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('webginas/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('webginas/'.$filename); 
});


$router->group(['middleware' => 'auth:admginas'], function () use ($router) {
    
    $router->get('profile', 'AdminLabGinasController@profile');
    $router->get('users/{id}', 'AdminLabGinasController@singleUser');
    $router->get('users', 'AdminLabGinasController@allUsers');
    $router->get('cek-payload', 'AdminLabGinasController@cekPayload');

    
    $router->post('update-password', 'AdminLabGinasController@updatePassword');
    $router->post('update-foto', 'AdminLabGinasController@updatePhoto');
    $router->post('update-background', 'AdminLabGinasController@updateBackground');

    //Menu
    $router->get('menu/{kode_klp}', 'AdmGinas\MenuController@show');

    
    $router->post('notif-pusher', 'AdmGinas\NotifController@sendPusher');
    $router->get('notif-pusher', 'AdmGinas\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'AdmGinas\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminLabGinasController@searchForm');
    $router->get('search-form-list', 'AdminLabGinasController@searchFormList');

});



?>