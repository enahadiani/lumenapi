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
$router->options('{all:.*}', ['middleware' => ['cors','XSS'], function() {
    return response('');
}]);


$router->group(['middleware' => ['cors','XSS']], function () use ($router) {
    $router->post('login', 'AuthController@loginSimkug');
    $router->get('hash-pass-nik/{nik}', 'AuthController@hashPasswordByNIK');
    $router->get('hash-pass-costum-top-param/{param}/{value}/{top}', 'AuthController@hashPasswordCostumTopParam');
    $router->get('hash-pass-costum-top-param-lok/{param}/{value}/{top}/{lok}', 'AuthController@hashPasswordCostumTopParamLok');
    
    $router->post('log-user', 'AuthController@addLogUser');
    $router->post('log-form-akses', 'AuthController@addLogFormAkses');
    $router->post('log-user-out', 'AuthController@addLogUserOut');
   
});

$router->group(['middleware' => ['XSS']], function () use ($router) {

    $router->get('storage/{filename}', function($filename)
    {
        if (!Storage::disk('s3')->exists('simkug/'.$filename)) {
            abort(404);
        }
        ob_end_clean();
        return Storage::disk('s3')->response('simkug/'.$filename); 
    });
});

$router->group(['middleware' => 'oauth.civitax'], function () use ($router) {
    $router->post('login-username', 'AuthController@loginUsernameOnly');
});

$router->group(['middleware' => ['auth:simkug']], function () use ($router) {
    
    $router->get('profile', 'AdminSimkugController@profile');
    $router->get('users/{id}', 'AdminSimkugController@singleUser');
    $router->get('users', 'AdminSimkugController@allUsers');
    $router->get('cekPayload', 'AdminSimkugController@cekPayload');
    
    $router->post('update-password', 'AdminSimkugController@updatePassword');
    $router->post('update-foto', 'AdminSimkugController@updatePhoto');
    $router->post('update-background', 'AdminSimkugController@updateBackground');
    $router->post('update-profile', 'AdminSimkugController@updateDataPribadi');

    //Menu
    $router->get('menu/{kode_klp}', 'Simkug\Setting\MenuController@show');
    
    $router->post('notif-pusher', 'Simkug\Setting\NotifController@sendPusher');
    $router->get('notif-pusher', 'Simkug\Setting\NotifController@getNotifPusher');
    
    $router->get('notif-approval', 'Simkug\Setting\NotifController@getNotifPusher');
    $router->post('notif-approval', 'Simkug\Setting\NotifController@sendNotifApproval');
    $router->put('notif-approval-status', 'Simkug\Setting\NotifController@updateStatusReadMobile');

    $router->put('notif-update-status', 'Simkug\Setting\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminSimkugController@searchForm');
    $router->get('search-form-list', 'AdminSimkugController@searchFormList');

    //ADMIN SETTING
    //Menu
    $router->get('menu','Simkug\Setting\MenuController@index');
    $router->post('menu','Simkug\Setting\MenuController@store');
    $router->put('menu','Simkug\Setting\MenuController@update');
    $router->delete('menu','Simkug\Setting\MenuController@destroy');
    $router->get('menu-klp','Simkug\Setting\MenuController@getKlp');
    $router->post('menu-move','Simkug\Setting\MenuController@simpanMove');

    //Akses User
    $router->get('akses-user','Simkug\Setting\HakaksesController@index');
    $router->post('akses-user','Simkug\Setting\HakaksesController@store');
    $router->get('akses-user-detail','Simkug\Setting\HakaksesController@show');
    $router->put('akses-user','Simkug\Setting\HakaksesController@update');
    $router->delete('akses-user','Simkug\Setting\HakaksesController@destroy');
    $router->get('akses-user-menu','Simkug\Setting\HakaksesController@getMenu');
    $router->get('akses-user-karyawan','Simkug\Setting\HakaksesController@getKaryawan');
    
    //Form
    $router->get('form','Simkug\Setting\FormController@index');
    $router->post('form','Simkug\Setting\FormController@store');
    $router->put('form','Simkug\Setting\FormController@update');
    $router->delete('form','Simkug\Setting\FormController@destroy');

    //Karyawan
    $router->get('karyawan','Simkug\Setting\KaryawanController@index');
    $router->get('karyawan-lokasi','Simkug\Setting\KaryawanController@getLokasi');
    $router->post('karyawan','Simkug\Setting\KaryawanController@store');
    $router->get('karyawan-detail','Simkug\Setting\KaryawanController@show');
    $router->post('karyawan-ubah','Simkug\Setting\KaryawanController@update');
    $router->delete('karyawan','Simkug\Setting\KaryawanController@destroy');

    //Kelompok Menu
    $router->get('menu-klp','Simkug\Setting\KelompokMenuController@index');
    $router->post('menu-klp','Simkug\Setting\KelompokMenuController@store');
    $router->put('menu-klp','Simkug\Setting\KelompokMenuController@update');
    $router->delete('menu-klp','Simkug\Setting\KelompokMenuController@destroy');

    //Unit
    $router->get('unit','Simkug\Setting\UnitController@index');
    $router->post('unit','Simkug\Setting\UnitController@store');
    $router->put('unit','Simkug\Setting\UnitController@update');
    $router->delete('unit','Simkug\Setting\UnitController@destroy');
    
    $router->get('hash-pass-menu-lokasi','Simkug\HashPasswordController@getLokasi');
    $router->get('hash-pass-menu','Simkug\HashPasswordController@index');
    $router->get('hash-pass-menu-batch','Simkug\HashPasswordController@getBatch');

});



?>