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
    $router->post('login', 'AuthController@loginYptKug');
    $router->get('hash_pass', 'AuthController@hashPasswordYptKug');
    $router->get('hash_pass_nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
   
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('bdh/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('bdh/'.$filename); 
});


$router->group(['middleware' => 'auth:yptkug'], function () use ($router) {
    
    $router->get('profile', 'AdminYptKugController@profile_simlog');
    $router->get('users/{id}', 'AdminYptKugController@singleUser');
    $router->get('users', 'AdminYptKugController@allUsers');
    $router->get('cekPayload', 'AdminYptKugController@cekPayload');
    
    $router->post('update-password', 'AdminYptKugController@updatePassword');
    $router->post('update-foto', 'AdminYptKugController@updatePhoto');
    $router->post('update-background', 'AdminYptKugController@updateBackground');
    $router->post('update-profile', 'AdminYptKugController@updateDataPribadi');

    //Menu
    $router->get('menu/{kode_klp}', 'Bdh\MenuController@show');
    
    $router->post('notif-pusher', 'Bdh\NotifController@sendPusher');
    $router->get('notif-pusher', 'Bdh\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Bdh\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminYptKugController@searchForm');
    $router->get('search-form-list', 'AdminYptKugController@searchFormList');

});



?>