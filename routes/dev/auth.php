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
    $router->post('login', 'AuthController@loginAdmin');
    $router->get('hashPass', 'AuthController@hashPasswordAdmin');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('dev/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('dev/'.$filename); 
});


$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    
    $router->get('profile', 'AdminController@profile');
    $router->get('users/{id}', 'AdminController@singleUser');
    $router->get('users', 'AdminController@allUsers');
    $router->get('cekPayload', 'AdminController@cekPayload');
    
    //Menu
    $router->get('menu/{kode_klp}', 'Gl\MenuController@show');

    $router->post('update-password', 'AdminController@updatePassword');
    $router->post('update-foto', 'AdminController@updateFoto');
    $router->post('update-background', 'AdminController@updateBackground');

    $router->post('notif-pusher', 'Dev\NotifController@sendPusher');
    $router->get('notif-pusher', 'Dev\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Dev\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminController@searchForm');
    $router->get('search-form-list', 'AdminController@searchFormList');

});



?>