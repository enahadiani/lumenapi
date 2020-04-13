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
    
    $router->post('login', 'AuthController@loginYpt');
    $router->get('hashPass', 'AuthController@hashPasswordYpt');
});

$router->group(['middleware' => 'auth:ypt'], function () use ($router) {

    $router->get('profile', 'AdminYptController@profile');
    $router->get('users/{id}', 'AdminYptController@singleUser');
    $router->get('users', 'AdminYptController@allUsers');
    $router->get('cekPayload', 'AdminYptController@cekPayload');

    $router->get('upload', 'UploadController@upload');
    $router->post('upload', 'UploadController@proses_upload');
    $router->get('upload/{file}', 'UploadController@show');

    $router->get('user_device','UserDeviceController@index');
    $router->get('user_device/{nik}','UserDeviceController@show');
    $router->post('user_device','UserDeviceController@store');
    $router->put('user_device/{nik}','UserDeviceController@update');
    $router->delete('user_device/{nik}','UserDeviceController@destroy');


    $router->get('periodeAktif', 'Sppd\SppdController@getPeriodeAktif');
    $router->get('akun', 'Sppd\SppdController@getAkun');
    $router->get('pp', 'Sppd\SppdController@getPP');
    $router->get('drk', 'Sppd\SppdController@getDrk');
    $router->get('budget', 'Sppd\SppdController@cekBudget');
    $router->post('budget', 'Sppd\SppdController@keepBudget');
    $router->delete('budget', 'Sppd\SppdController@releaseBudget');
    $router->post('kirimAgenda', 'Sppd\SppdController@kirimNoAgenda');
    $router->get('agendaDok/{no_aju}', 'Sppd\SppdController@getAgendaDok');
    $router->get('agendaBayar/{no_aju}', 'Sppd\SppdController@getAgendaBayar');

});