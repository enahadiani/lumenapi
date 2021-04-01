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


$router->group(['middleware' => 'auth:toko'], function () use ($router) {
    //Produk
    $router->get('produk','JavaAdm\ProdukController@index');
    $router->post('produk','JavaAdm\ProdukController@store');
    $router->post('produk-ubah','JavaAdm\ProdukController@update');
    $router->delete('produk','JavaAdm\ProdukController@destroy');

    //Project
    $router->get('project','JavaAdm\ProjectController@index');
    $router->post('project','JavaAdm\ProjectController@store');
    $router->post('project-ubah','JavaAdm\ProjectController@update');
    $router->delete('project','JavaAdm\ProjectController@destroy');

    // Profile
    $router->post('profile','JavaAdm\ProfileController@store');
    $router->get('profile','JavaAdm\ProfileController@index');

    //Team
    $router->get('team','JavaAdm\TeamController@index');
    $router->post('team','JavaAdm\TeamController@store');
    $router->post('team-ubah','JavaAdm\TeamController@update');
    $router->delete('team','JavaAdm\TeamController@destroy');

});



?>