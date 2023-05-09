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
    $router->post('login', 'AuthController@loginItpln');
    $router->get('hash-pass-nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
});

$router->get('storage-file', function (Request $r)
{
    if (!Storage::disk('s3')->exists('dash-itpln/'.$r->filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('dash-itpln/'.$r->filename); 
});


$router->group(['middleware' => 'auth:itpln'], function () use ($router) {
    
    $router->get('profile', 'AdminItplnController@profile');
    $router->get('users/{id}', 'AdminItplnController@singleUser');
    $router->get('users', 'AdminItplnController@allUsers');
    $router->get('cekPayload', 'AdminItplnController@cekPayload');
    
    $router->post('update-password', 'AdminItplnController@updatePassword');
    $router->post('update-foto', 'AdminItplnController@updatePhoto');
    $router->post('update-background', 'AdminItplnController@updateBackground');
    $router->post('update-profile', 'AdminItplnController@updateDataPribadi');

    //Menu
    $router->get('menu/{kode_klp}', 'DashItpln\MenuController@show');
    
    $router->post('notif-pusher', 'DashItpln\NotifController@sendPusher');
    $router->get('notif-pusher', 'DashItpln\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'DashItpln\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminItplnController@searchForm');
    $router->get('search-form-list', 'AdminItplnController@searchFormList');

});



?>