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
    $router->post('login', 'AuthController@loginTs');
    $router->get('hash-pass', 'AuthController@hashPasswordTs');
    $router->get('hash-pass-costum/{db}/{table}/{top}/{kode_pp}', 'AuthController@hashPasswordCostum');
    $router->get('hash-pass-nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');

});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('ts/'.$filename)) {
        $success['message'] = 'Dokumen tidak tersedia!';
        $success['status'] = false;
    }
    return Storage::disk('s3')->response('ts/'.$filename); 
});


$router->group(['middleware' => 'auth:ts'], function () use ($router) {

    $router->get('profile', 'AdminTsController@profile');
    $router->get('users/{id}', 'AdminTsController@singleUser');
    $router->get('users', 'AdminTsController@allUsers');
    $router->get('cek-payload', 'AdminTsController@cekPayload');

    $router->get('menu/{kode_klp}', 'Ts\MenuController@show');
    
    $router->post('update-password', 'AdminTsController@updatePassword');
    $router->post('update-foto', 'AdminTsController@updatePhoto');
    $router->post('update-background', 'AdminTsController@updateBackground');
    
    $router->post('notif-pusher', 'Ts\NotifController@sendPusher');
    $router->get('notif-pusher', 'Ts\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Ts\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminTsController@searchForm');
    $router->get('search-form-list', 'AdminTsController@searchFormList');

    //Tahun Ajaran
    $router->get('pp','Ts\TahunAjaranController@getPP');
    $router->get('tahun-ajaran-all','Ts\TahunAjaranController@index');
    
    $router->get('kartu-piutang','Ts\DashSiswaController@getKartuPiutang');
    $router->get('kartu-pdd','Ts\DashSiswaController@getKartuPDD');
    $router->get('dash-siswa-profile','Ts\DashSiswaController@getProfile');    
    $router->post('send-email','EmailController@send');
    
});