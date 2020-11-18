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


$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('jenis','Dev\JenisController@index');
    $router->post('jenis','Dev\JenisController@store');
    $router->put('jenis','Dev\JenisController@update');
    $router->delete('jenis','Dev\JenisController@destroy');    
    
    $router->get('jurusan','Dev\JurusanController@index');
    $router->post('jurusan','Dev\JurusanController@store');
    $router->put('jurusan','Dev\JurusanController@update');
    $router->delete('jurusan','Dev\JurusanController@destroy');    
    
    $router->get('siswa','Dev\SiswaController@index');
    $router->post('siswa','Dev\SiswaController@store');
    $router->put('siswa','Dev\SiswaController@update');
    $router->delete('siswa','Dev\SiswaController@destroy');    
    
});



?>