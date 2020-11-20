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

    //Galeri
    $router->get('galeri','AdmGinas\GaleriController@index');
    $router->post('galeri','AdmGinas\GaleriController@store');
    $router->post('galeri-ubah','AdmGinas\GaleriController@update');
    $router->delete('galeri','AdmGinas\GaleriController@destroy');

    //Menu
    $router->get('menu-web','AdmGinas\MenuWebController@index');
    $router->post('menu-web','AdmGinas\MenuWebController@store');
    $router->put('menu-web','AdmGinas\MenuWebController@update');
    $router->delete('menu-web','AdmGinas\MenuWebController@destroy');
    $router->get('menu-web-form','AdmGinas\MenuWebController@getForm');
    $router->post('menu-web-move','AdmGinas\MenuWebController@simpanMove');

    // Banner 
    $router->get('banner','AdmGinas\BannerController@show');
    $router->post('banner','AdmGinas\BannerController@store');
    // $router->post('banner-ubah','AdmGinas\BannerController@update');
    // $router->delete('banner','AdmGinas\BannerController@destroy');

    //Klien
    $router->get('klien','AdmGinas\KlienController@index');
    $router->get('klien-show','AdmGinas\KlienController@show');
    $router->post('klien','AdmGinas\KlienController@store');
    $router->post('klien-ubah','AdmGinas\KlienController@update');
    // $router->delete('banner','AdmGinas\KlienController@destroy');

    //Review Klien
    $router->get('review','AdmGinas\ReviewKlienController@index');
    $router->get('review-show','AdmGinas\ReviewKlienController@show');
    $router->post('review','AdmGinas\ReviewKlienController@store');
    $router->post('review-ubah','AdmGinas\ReviewKlienController@update');

    //Profil Perusahaan
    $router->get('profil','AdmGinas\ProfilPerusahaanController@show');
    $router->post('profil','AdmGinas\ProfilPerusahaanController@store');

    //Info
    $router->get('info','AdmGinas\InfoController@index');
    $router->get('info-show','AdmGinas\InfoController@show');
    $router->post('info-simpan','AdmGinas\InfoController@store');
    $router->post('info-ubah','AdmGinas\InfoController@update');
});
    $router->get('banner-web','AdmGinas\BannerController@show');
    $router->get('review-web','AdmGinas\ReviewKlienController@showReview');
    $router->get('klien-web','AdmGinas\KlienController@showKlien');
    $router->get('perusahaan-web','AdmGinas\ProfilPerusahaanController@getDataPerusahaanVMD');
    $router->get('info-3-web','AdmGinas\InfoController@getTop3Info');
    $router->get('info-detail-web','AdmGinas\InfoController@getInfoDetail');


?>