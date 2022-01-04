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
	$router->get('db3', function () {
        
        $sql = DB::connection('sqlsrvyptkug')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('ypt/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('ypt/'.$filename); 
});

$router->get('storage2/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('telu/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('telu/'.$filename); 
});

$router->get('storage-tmp/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('telu/tmp_dok/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('telu/tmp_dok/'.$filename); 
});


$router->get('storage-file-telu/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('telu/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->file('telu/'.$filename); 
});


$router->group(['middleware' => 'auth:yptkug'], function () use ($router) {
    
    $router->get('profile', 'AdminYptKugController@profile');
    $router->get('users/{id}', 'AdminYptKugController@singleUser');
    $router->get('users', 'AdminYptKugController@allUsers');
    $router->get('cekPayload', 'AdminYptKugController@cekPayload');
    
    $router->post('update-password', 'AdminYptKugController@updatePassword');
    $router->post('update-foto', 'AdminYptKugController@updatePhoto');
    $router->post('update-background', 'AdminYptKugController@updateBackground');
    $router->post('update-profile', 'AdminYptKugController@updateDataPribadi');

    //Menu
    $router->get('upload', 'UploadController@upload');
    $router->post('upload', 'UploadController@proses_upload');
    $router->get('upload/{file}', 'UploadController@show');
    $router->get('menu/{kode_klp}', 'Dashboard\DashboardController@getMenu');
    $router->get('menu2/{kode_klp}', 'Dashboard\DashboardController@getMenu2');
    
    $router->post('notif-pusher', 'Ypt\NotifController@sendPusher');
    $router->get('notif-pusher', 'Ypt\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Ypt\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminYptKugController@searchForm');
    $router->get('search-form-list', 'AdminYptKugController@searchFormList');

});



?>