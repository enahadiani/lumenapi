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
    $router->post('login', 'AuthController@loginDago');
    $router->get('hash_pass', 'AuthController@hashPasswordDago');
    $router->get('hash_by_nik/{db}/{table}/{nik}','AuthController@hashPasswordByNIK');
    $router->get('hash_pass_dago','AuthController@hashPassDago');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('dago/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('dago/'.$filename); 
});

$router->group(['middleware' => 'auth:dago'], function () use ($router) {
    $router->get('profile', 'AdminDagoController@profile');
    $router->post('update_password', 'AdminDagoController@updatePassword');
    $router->get('users/{id}', 'AdminDagoController@singleUser');
    $router->get('users', 'AdminDagoController@allUsers');
    $router->get('cek_payload', 'AdminDagoController@cekPayload');
    //Menu
    $router->get('menu/{kode_klp}', 'Gl\MenuController@show');
});

?>