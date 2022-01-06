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


$router->group(['middleware' => 'auth:siaga'], function () use ($router) {
     //Master Karyawan
     $router->get('karyawan','Siaga\KaryawanController@index');
     $router->get('karyawan/{nik}','Siaga\KaryawanController@show');
     $router->post('karyawan','Siaga\KaryawanController@store');
     $router->post('karyawan/{nik}','Siaga\KaryawanController@update');
     $router->delete('karyawan/{nik}','Siaga\KaryawanController@destroy');
     $router->get('karyawan-nik','Siaga\KaryawanController@getGrKaryawan');
 
     //Master Jabatan
     $router->get('jabatan','Siaga\JabatanController@index');
     $router->get('jabatan/{kode_jab}','Siaga\JabatanController@show');
     $router->post('jabatan','Siaga\JabatanController@store');
     $router->put('jabatan/{kode_jab}','Siaga\JabatanController@update');
     $router->delete('jabatan/{kode_jab}','Siaga\JabatanController@destroy');
 
     //Master Unit
     $router->get('unit','Siaga\UnitController@index');
     $router->get('unit/{kode_pp}','Siaga\UnitController@show');
     $router->post('unit','Siaga\UnitController@store');
     $router->put('unit/{kode_pp}','Siaga\UnitController@update');
     $router->delete('unit/{kode_pp}','Siaga\UnitController@destroy');
 
     //Master Role
     $router->get('role','Siaga\RoleController@index');
     $router->get('role/{kode_role}','Siaga\RoleController@show');
     $router->post('role','Siaga\RoleController@store');
     $router->put('role/{kode_role}','Siaga\RoleController@update');
     $router->delete('role/{kode_role}','Siaga\RoleController@destroy');
 
     //Master Hakakses
     $router->get('hakakses','Siaga\HakaksesController@index');
     $router->get('hakakses/{nik}','Siaga\HakaksesController@show');
     $router->post('hakakses','Siaga\HakaksesController@store');
     $router->put('hakakses/{nik}','Siaga\HakaksesController@update');
     $router->delete('hakakses/{nik}','Siaga\HakaksesController@destroy');
     $router->get('form','Siaga\HakaksesController@getForm');
     $router->get('menu','Siaga\HakaksesController@getMenu');

    $router->get('filter-pp','Siaga\FilterController@getFilterPP');
    $router->get('filter-kota','Siaga\FilterController@getFilterKota');
    $router->get('filter-nobukti','Siaga\FilterController@getFilterNoBukti');
    $router->get('filter-nodokumen','Siaga\FilterController@getFilterNoDokumen');
 
});



?>