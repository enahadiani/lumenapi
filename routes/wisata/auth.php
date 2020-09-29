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
    $router->post('login', 'AuthController@loginToko');
    $router->get('hash-pass', 'AuthController@hashPasswordToko');
    $router->get('cek-db', function () {
        
        $sql = DB::connection('tokoaws')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['data'=>$row], 200);    
        
        return $result;
        
    });
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('wisata/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('wisata/'.$filename); 
});


$router->group(['middleware' => 'auth:toko'], function () use ($router) {
    
    $router->get('profile', 'AdminTokoController@profile');
    $router->get('users/{id}', 'AdminTokoController@singleUser');
    $router->get('users', 'AdminTokoController@allUsers');
    $router->get('cek-payload', 'AdminTokoController@cekPayload');

    
    $router->post('update-password', 'AdminTokoController@updatePassword');
    $router->post('update-foto', 'AdminTokoController@updatePhoto');
    $router->post('update-background', 'AdminTokoController@updateBackground');

    //Menu
    $router->get('menu/{kode_klp}', 'Wisata\MenuController@show');

    
    $router->post('notif-pusher', 'Wisata\NotifController@sendPusher');
    $router->get('notif-pusher', 'Wisata\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Wisata\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminTokoController@searchForm');
    $router->get('search-form-list', 'AdminTokoController@searchFormList');

});



?>