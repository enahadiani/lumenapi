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
    $router->post('login', 'AuthController@loginAdmin');
    $router->get('hash_pass', 'AuthController@hashPasswordAdmin');
    $router->get('hash_by_nik','AuthController@hashPasswordByNIK');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('dago/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('dago/'.$filename); 
});

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('profile', 'AdminController@profile');
    $router->get('users/{id}', 'AdminController@singleUser');
    $router->get('users', 'AdminController@allUsers');
    $router->get('cek_payload', 'AdminController@cekPayload');
    //Menu
    $router->get('menu/{kode_klp}', 'Gl\MenuController@show');
});

?>