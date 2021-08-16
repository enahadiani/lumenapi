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
    $router->post('login', 'AuthController@loginGinas');
    $router->get('hash_pass', 'AuthController@hashPasswordGinas');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('ginas/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('ginas/'.$filename); 
});

$router->group(['middleware' => 'auth:ginas'], function () use ($router) {
    $router->get('profile', 'AdminTokoController@profile');
    $router->get('users/{id}', 'AdminTokoController@singleUser');
    $router->get('users', 'AdminTokoController@allUsers');
    $router->get('cek-payload', 'AdminTokoController@cekPayload');

    $router->get('sync-master', 'Esaku\Inventori\SyncController@syncMaster');
    $router->get('sync-pnj', 'Esaku\Inventori\SyncController@syncPnj');
    $router->get('sync-pmb', 'Esaku\Inventori\SyncController@syncPmb');
    $router->get('sync-retur-beli', 'Esaku\Inventori\SyncController@syncReturBeli');
    $router->post('exec-sql', 'Esaku\Inventori\SyncController@executeSQL');

});



?>