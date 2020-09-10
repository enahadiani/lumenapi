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
    $router->post('login', 'AuthController@loginYakes');
    $router->get('hash-pass', 'AuthController@hashPasswordYakes');
    $router->get('hash-pass-nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('yakes/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('yakes/'.$filename); 
});


$router->group(['middleware' => 'auth:yakes'], function () use ($router) {
    
    $router->get('profile', 'AdminYakesController@profile');
    $router->get('users/{id}', 'AdminYakesController@singleUser');
    $router->get('users', 'AdminYakesController@allUsers');
    $router->get('cek-payload', 'AdminYakesController@cekPayload');

    
    $router->post('update-password', 'AdminYakesController@updatePassword');
    $router->post('update-foto', 'AdminYakesController@updatePhoto');
    $router->post('update-background', 'AdminYakesController@updateBackground');

    //Menu
    $router->get('menu/{kode_klp}', 'Yakes\MenuController@show');

    
    $router->post('notif-pusher', 'Yakes\NotifController@sendPusher');
    $router->get('notif-pusher', 'Yakes\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Yakes\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminYakesController@searchForm');
    $router->get('search-form-list', 'AdminYakesController@searchFormList');

});



?>