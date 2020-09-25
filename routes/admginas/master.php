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


$router->group(['middleware' => 'auth:admginas'], function () use ($router) {
  
    //Konten
    $router->get('konten','AdmGinas\KontenController@index');
    $router->post('konten','AdmGinas\KontenController@store');
    $router->put('konten','AdmGinas\KontenController@update');
    $router->delete('konten','AdmGinas\KontenController@destroy');
    $router->get('konten-header','AdmGinas\KontenController@getHeader');
    $router->get('konten-klp','AdmGinas\KontenController@getKlp');
    $router->get('konten-kategori','AdmGinas\KontenController@getKategori');

    //Konten Galeri
    $router->get('kategori-galeri','AdmGinas\KategoriGaleriController@index');
    $router->post('kategori-galeri','AdmGinas\KategoriGaleriController@store');
    $router->put('kategori-galeri','AdmGinas\KategoriGaleriController@update');
    $router->delete('kategori-galeri','AdmGinas\KategoriGaleriController@destroy');
    
    //Kontak
    $router->get('kontak','AdmGinas\KontakController@index');
    $router->post('kontak','AdmGinas\KontakController@store');
    $router->put('kontak','AdmGinas\KontakController@update');
    $router->delete('kontak','AdmGinas\KontakController@destroy');

});



?>