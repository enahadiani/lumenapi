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
    if (!Storage::disk('s3')->exists('toko/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('toko/'.$filename); 
});


$router->group(['middleware' => 'auth:toko'], function () use ($router) {
    
    $router->get('profile', 'AdminTokoController@profile2');
    $router->get('users/{id}', 'AdminTokoController@singleUser');
    $router->get('users', 'AdminTokoController@allUsers');
    $router->get('cek-payload', 'AdminTokoController@cekPayload');
    //Menu
    $router->get('menu/{kode_klp}', 'Toko\MenuController@show');

});



?>