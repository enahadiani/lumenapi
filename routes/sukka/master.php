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


$router->group(['middleware' => 'auth:yptkug'], function () use ($router) {
    //Menu Setting
    $router->get('menu','Sukka\MenuController@index');
    $router->post('menu','Sukka\MenuController@store');
    $router->put('menu','Sukka\MenuController@update');
    $router->delete('menu','Sukka\MenuController@destroy');
    $router->get('menu-klp','Sukka\MenuController@getKlp');
    $router->post('menu-move','Sukka\MenuController@simpanMove');

    //Kelompok Menu
    $router->get('menu-klp','Sukka\KelompokMenuController@index');
    $router->post('menu-klp','Sukka\KelompokMenuController@store');
    $router->put('menu-klp','Sukka\KelompokMenuController@update');
    $router->delete('menu-klp','Sukka\KelompokMenuController@destroy');

     //Master Karyawan
     $router->get('karyawan','Sukka\KaryawanController@index');
     $router->get('karyawan/{nik}','Sukka\KaryawanController@show');
     $router->post('karyawan','Sukka\KaryawanController@store');
     $router->post('karyawan/{nik}','Sukka\KaryawanController@update');
     $router->delete('karyawan/{nik}','Sukka\KaryawanController@destroy');
     $router->get('karyawan-nik','Sukka\KaryawanController@getGrKaryawan');
 
     //Master Jabatan
     $router->get('jabatan','Sukka\JabatanController@index');
     $router->get('jabatan/{kode_jab}','Sukka\JabatanController@show');
     $router->post('jabatan','Sukka\JabatanController@store');
     $router->put('jabatan/{kode_jab}','Sukka\JabatanController@update');
     $router->delete('jabatan/{kode_jab}','Sukka\JabatanController@destroy');
 
     //Master Unit
     $router->get('unit','Sukka\UnitController@index');
     $router->get('unit/{kode_pp}','Sukka\UnitController@show');
     $router->post('unit','Sukka\UnitController@store');
     $router->put('unit/{kode_pp}','Sukka\UnitController@update');
     $router->delete('unit/{kode_pp}','Sukka\UnitController@destroy');
 
     //Master Role
     $router->get('role','Sukka\RoleController@index');
     $router->get('role/{kode_role}','Sukka\RoleController@show');
     $router->post('role','Sukka\RoleController@store');
     $router->put('role/{kode_role}','Sukka\RoleController@update');
     $router->delete('role/{kode_role}','Sukka\RoleController@destroy');
 
     //Master Hakakses
     $router->get('hakakses','Sukka\HakaksesController@index');
     $router->get('hakakses/{nik}','Sukka\HakaksesController@show');
     $router->post('hakakses','Sukka\HakaksesController@store');
     $router->put('hakakses/{nik}','Sukka\HakaksesController@update');
     $router->delete('hakakses/{nik}','Sukka\HakaksesController@destroy');
     
    //Form
    $router->get('form','Sukka\FormController@index');
    $router->post('form','Sukka\FormController@store');
    $router->put('form','Sukka\FormController@update');
    $router->delete('form','Sukka\FormController@destroy');

    $router->get('filter-pp','Sukka\FilterController@getFilterPP');
    $router->get('filter-kota','Sukka\FilterController@getFilterKota');
    $router->get('filter-nobukti','Sukka\FilterController@getFilterNoBukti');
    $router->get('filter-nodokumen','Sukka\FilterController@getFilterNoDokumen');
 
});



?>