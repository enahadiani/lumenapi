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
    //tanggal server
    $router->get('getTglServer','Ypt\FilterController@getTglServer');     
    //periode input
    $router->get('getPerInput','Ypt\FilterController@getPerInput');     

    //ADMIN
    //Menu
    $router->get('menu','Ypt\MenuController@index');
    $router->post('menu','Ypt\MenuController@store');
    $router->put('menu','Ypt\MenuController@update');
    $router->delete('menu','Ypt\MenuController@destroy');
    $router->get('menu-klp','Ypt\MenuController@getKlp');
    $router->post('menu-move','Ypt\MenuController@simpanMove');

    //Akses User
    $router->get('akses-user','Ypt\HakaksesController@index');
    $router->post('akses-user','Ypt\HakaksesController@store');
    $router->get('akses-user-detail','Ypt\HakaksesController@show');
    $router->put('akses-user','Ypt\HakaksesController@update');
    $router->delete('akses-user','Ypt\HakaksesController@destroy');
    $router->get('akses-user-menu','Ypt\HakaksesController@getMenu');
    
    //Form
    $router->get('form','Ypt\FormController@index');
    $router->post('form','Ypt\FormController@store');
    $router->put('form','Ypt\FormController@update');
    $router->delete('form','Ypt\FormController@destroy');

    //Karyawan
    $router->get('karyawan','Ypt\KaryawanController@index');
    $router->post('karyawan','Ypt\KaryawanController@store');
    $router->get('karyawan-detail','Ypt\KaryawanController@show');
    $router->post('karyawan-ubah','Ypt\KaryawanController@update');
    $router->delete('karyawan','Ypt\KaryawanController@destroy');

    //Kelompok Menu
    $router->get('menu-klp','Ypt\KelompokMenuController@index');
    $router->post('menu-klp','Ypt\KelompokMenuController@store');
    $router->put('menu-klp','Ypt\KelompokMenuController@update');
    $router->delete('menu-klp','Ypt\KelompokMenuController@destroy');

    //Unit
    $router->get('unit','Ypt\UnitController@index');
    $router->post('unit','Ypt\UnitController@store');
    $router->put('unit','Ypt\UnitController@update');
    $router->delete('unit','Ypt\UnitController@destroy');

    
    //fs
    $router->get('listFSAktif','Ypt\FSController@listFSAktif');         
    $router->get('cariFSAktif','Ypt\FSController@cariFSAktif');
    $router->get('fs','Ypt\FSController@index');
    $router->post('fs','Ypt\FSController@store');
    $router->put('fs','Ypt\FSController@update');
    $router->delete('fs','Ypt\FSController@destroy'); 

    //Video
    
    $router->get('video','Ypt\VideoController@index');
    $router->post('video','Ypt\VideoController@store');
    $router->put('video','Ypt\VideoController@update');
    $router->delete('video','Ypt\VideoController@destroy');

    
    $router->get('konten','Ypt\KontenController@index');
    $router->get('konten-edit','Ypt\KontenController@show');
    $router->post('konten','Ypt\KontenController@store');
    $router->post('konten-edit','Ypt\KontenController@update');
    $router->delete('konten','Ypt\KontenController@destroy');

    $router->get('dok-jenis','Ypt\KontenController@getJenis');
    $router->post('konten-dok','Ypt\KontenController@storeDokTmp');
    $router->delete('konten-dok','Ypt\KontenController@destroyDok');
    $router->delete('konten-dok-tmp','Ypt\KontenController@destroyDokTmp');
    
    $router->get('kategori-konten','Ypt\KontenController@getKonten');

});



?>