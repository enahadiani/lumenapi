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
    $router->post('login', 'AuthController@loginTarbak');
    $router->get('hash_pass', 'AuthController@hashPasswordTarbak');
    $router->get('hash_pass_nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('dash-tarbak/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('dash-tarbak/'.$filename); 
});


$router->group(['middleware' => 'auth:tarbak'], function () use ($router) {
    
    $router->get('profile', 'AdminTarbakController@profileDash');
    $router->get('users/{id}', 'AdminTarbakController@singleUser');
    $router->get('users', 'AdminTarbakController@allUsers');
    $router->get('cekPayload', 'AdminTarbakController@cekPayload');
    
    $router->post('update-password', 'AdminTarbakController@updatePassword');
    $router->post('update-foto', 'AdminTarbakController@updatePhoto');
    $router->post('update-background', 'AdminTarbakController@updateBackground');
    $router->post('update-profile', 'AdminTarbakController@updateDataPribadi');

    //Menu
    $router->get('menu/{kode_klp}', 'DashTarbak\MenuController@show');
    
    $router->post('notif-pusher', 'DashTarbak\NotifController@sendPusher');
    $router->get('notif-pusher', 'DashTarbak\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'DashTarbak\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminTarbakController@searchForm');
    $router->get('search-form-list', 'AdminTarbakController@searchFormList');

});



?>