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
    
    $router->post('login', 'AuthController@loginRtrw');
    $router->get('hash_pass', 'AuthController@hashPasswordRtrw');
    $router->get('db', function () {
        
        $sql = DB::connection('sqlsrvrtrw')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
});

$router->group(['middleware' => 'auth:rtrw'], function () use ($router) {

    $router->get('profile', 'AdminRtrwController@profile');
    $router->get('users/{id}', 'AdminRtrwController@singleUser');
    $router->get('users', 'AdminRtrwController@allUsers');
    $router->get('cek_payload', 'AdminRtrwController@cekPayload');

});