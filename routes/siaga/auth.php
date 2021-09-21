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
    $router->post('login', 'AuthController@loginSiaga');
    $router->get('hash-pass', 'AuthController@hashPasswordSiaga');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('siaga/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('siaga/'.$filename); 
});


$router->group(['middleware' => 'auth:siaga'], function () use ($router) {
    
    $router->get('profile', 'AdminSiagaController@profile');
    $router->get('users/{id}', 'AdminSiagaController@singleUser');
    $router->get('users', 'AdminSiagaController@allUsers');
    $router->get('cek-payload', 'AdminSiagaController@cekPayload');

    
    $router->post('update-password', 'AdminSiagaController@updatePassword');
    $router->post('update-foto', 'AdminSiagaController@updatePhoto');
    $router->post('update-background', 'AdminSiagaController@updateBackground');

    //Menu
    $router->get('menu/{kode_klp}', 'Siaga\MenuController@show');

    
    $router->post('notif-pusher', 'Siaga\NotifController@sendPusher');
    $router->get('notif-pusher', 'Siaga\NotifController@getNotifPusher');
    
    $router->get('notif-approval', 'Siaga\NotifController@getNotifPusher');
    $router->post('notif-approval', 'Siaga\NotifController@sendNotifApproval');
    $router->put('notif-update-status', 'Siaga\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminSiagaController@searchForm');
    $router->get('search-form-list', 'AdminSiagaController@searchFormList');

    $router->get('cek', 'Siaga\Approval2Controller@cek');
});



?>