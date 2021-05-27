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
    $router->post('login', 'AuthController@loginBangtel');
    $router->get('hash-pass', 'AuthController@hashPasswordBangtel');
    $router->get('cek-db', function () {
        
        $sql = DB::connection('dbbangtelindo')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['data'=>$row], 200);    
        
        return $result;
        
    });
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('bangtel/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('bangtel/'.$filename); 
});


$router->group(['middleware' => 'auth:bangtel'], function () use ($router) {
    
    $router->get('profile', 'AdminBangtelController@profile');
    $router->get('users/{id}', 'AdminBangtelController@singleUser');
    $router->get('users', 'AdminBangtelController@allUsers');
    $router->get('cek-payload', 'AdminBangtelController@cekPayload');

    
    $router->post('update-password', 'AdminBangtelController@updatePassword');
    $router->post('update-foto', 'AdminBangtelController@updatePhoto');
    $router->post('update-background', 'AdminBangtelController@updateBackground');

    //Menu
    $router->get('menu/{kode_klp}', 'Bangtel\MenuController@show');

    
    $router->post('notif-pusher', 'Bangtel\NotifController@sendPusher');
    $router->get('notif-pusher', 'Bangtel\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Bangtel\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminBangtelController@searchForm');
    $router->get('search-form-list', 'AdminBangtelController@searchFormList');

    $router->post('report-error', 'AdminBangtelController@reportError');

    $router->get('periode', 'Bangtel\DashboardController@getPeriode');
    $router->get('pp', 'Bangtel\DashboardController@getPP');
    $router->get('project-box', 'Bangtel\DashboardController@getBoxProject');

});



?>