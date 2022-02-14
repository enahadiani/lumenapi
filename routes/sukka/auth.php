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
    $router->post('login', 'AuthController@loginYptKug');
    $router->get('hash_pass', 'AuthController@hashPasswordYptKug');
    $router->get('hash_pass_nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('sukka/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('sukka/'.$filename); 
});


$router->group(['middleware' => 'auth:yptkug'], function () use ($router) {
    
    $router->get('profile', 'AdminSukkaController@profile');
    $router->get('users/{id}', 'AdminSukkaController@singleUser');
    $router->get('users', 'AdminSukkaController@allUsers');
    $router->get('cek-payload', 'AdminSukkaController@cekPayload');

    
    $router->post('update-password', 'AdminSukkaController@updatePassword');
    $router->post('update-foto', 'AdminSukkaController@updatePhoto');
    $router->post('update-background', 'AdminSukkaController@updateBackground');

    //Menu
    $router->get('menu/{kode_klp}', 'Sukka\MenuController@show');

    
    $router->post('notif-pusher', 'Sukka\NotifController@sendPusher');
    $router->get('notif-pusher', 'Sukka\NotifController@getNotifPusher');
    
    
    $router->post('notif-tes', 'Sukka\NotifController@tesSend');
    $router->get('notif-approval', 'Sukka\NotifController@getNotifPusher');
    $router->post('notif-approval', 'Sukka\NotifController@sendNotifApproval');
    $router->put('notif-approval-status', 'Sukka\NotifController@updateStatusReadMobile');

    $router->put('notif-update-status', 'Sukka\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminSukkaController@searchForm');
    $router->get('search-form-list', 'AdminSukkaController@searchFormList');
});



?>