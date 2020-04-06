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

    $router->post('login', 'AuthController@loginAdmin');
    $router->get('hashPass', 'AuthController@hashPasswordAdmin');
    $router->get('db2', function () {
        
        $sql = DB::connection('sqlsrv2')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
});

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('profile', 'AdminController@profile');
    $router->get('users/{id}', 'AdminController@singleUser');
    $router->get('users', 'AdminController@allUsers');
    $router->get('cekPayload', 'AdminController@cekPayload');
    
    //Menu
    $router->get('menu/{kode_klp}', 'Gl\MenuController@show');
    
    //FS
    $router->get('fs','Gl\FsController@index');
    $router->get('fs/{id}','Gl\FsController@show');
    $router->post('fs','Gl\FsController@store');
    $router->put('fs/{id}','Gl\FsController@update');
    $router->delete('fs/{id}','Gl\FsController@destroy');
    
    //MASAKUN
    $router->get('masakun','Gl\MasakunController@index');
    $router->get('masakun/{id}','Gl\MasakunController@show');
    $router->post('masakun','Gl\MasakunController@store');
    $router->put('masakun/{id}','Gl\MasakunController@update');
    $router->delete('masakun/{id}','Gl\MasakunController@destroy');
    
    $router->get('currency','Gl\MasakunController@getCurrency');
    $router->get('modul','Gl\MasakunController@getModul');
    $router->get('flag_akun','Gl\MasakunController@getFlagAkun');
    $router->get('neraca/{kode_fs}','Gl\MasakunController@getNeraca');
    $router->get('fsgar','Gl\MasakunController@getFSGar');
    $router->get('neracagar/{kode_fs}','Gl\MasakunController@getNeracaGar');
});
