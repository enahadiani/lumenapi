<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);

$router->group(['middleware' => 'cors'], function () use ($router) {
    
    $router->post('login', 'AuthController@loginYptKug');
    $router->get('hashPass', 'AuthController@hashPasswordYptKug');
});

$router->group(['middleware' => 'auth:yptkug'], function () use ($router) {

    $router->get('profile', 'AdminYptKugController@profile');
    $router->get('users/{id}', 'AdminYptKugController@singleUser');
    $router->get('users', 'AdminYptKugController@allUsers');
    $router->get('cekPayload', 'AdminYptKugController@cekPayload');

    $router->get('upload', 'UploadController@upload');
    $router->post('upload', 'UploadController@proses_upload');
    $router->get('upload/{file}', 'UploadController@show');

    $router->get('user_device','UserDeviceController@index');
    $router->get('user_device/{nik}','UserDeviceController@show');
    $router->post('user_device','UserDeviceController@store');
    $router->put('user_device/{nik}','UserDeviceController@update');
    $router->delete('user_device/{nik}','UserDeviceController@destroy');


    $router->get('periode-aktif', 'Sppd\SppdController@getPeriodeAktif');
    $router->get('akun', 'Sppd\SppdController@getAkun');
    $router->get('pp', 'Sppd\SppdController@getPP');
    $router->get('drk', 'Sppd\SppdController@getDrk');
    $router->get('budget', 'Sppd\SppdController@cekBudget');
    $router->post('budget', 'Sppd\SppdController@keepBudget');
    $router->delete('budget/{no_agenda}', 'Sppd\SppdController@releaseBudget');
    $router->post('agenda-kirim', 'Sppd\SppdController@kirimNoAgenda');
    $router->get('agenda-dok/{no_agenda}', 'Sppd\SppdController@getAgendaDok');
    $router->get('agenda-bayar/{no_agenda}', 'Sppd\SppdController@getAgendaBayar');

});