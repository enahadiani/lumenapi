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
   
    //Kecamatan
    $router->get('camat','Wisata\CamatController@index');
    $router->post('camat','Wisata\CamatController@store');
    $router->put('camat','Wisata\CamatController@update');
    $router->delete('camat','Wisata\CamatController@destroy');    

    //Bidang
    $router->get('bidang','Wisata\BidangController@index');
    $router->post('bidang','Wisata\BidangController@store');
    $router->put('bidang','Wisata\BidangController@update');
    $router->delete('bidang','Wisata\BidangController@destroy');    

    //Jenis
    $router->get('jenis','Wisata\JenisController@index');
    $router->get('getBidang','Wisata\JenisController@getBidang');
    $router->post('jenis','Wisata\JenisController@store');
    $router->put('jenis','Wisata\JenisController@update');
    $router->delete('jenis','Wisata\JenisController@destroy');    

    //SubJenis
    $router->get('subjenis','Wisata\SubjenisController@index');
    $router->get('getJenis','Wisata\SubjenisController@getJenis');
    $router->post('subjenis','Wisata\SubjenisController@store');
    $router->put('subjenis','Wisata\SubjenisController@update');
    $router->delete('subjenis','Wisata\SubjenisController@destroy');    

    //Mitra
    $router->get('mitra','Wisata\MitraController@index');
    $router->get('mitrabid','Wisata\MitraController@edit');
    $router->get('getCamat','Wisata\MitraController@getCamat');
    $router->post('mitra','Wisata\MitraController@store');
    $router->post('mitraupdate','Wisata\MitraController@update');
    $router->delete('mitra','Wisata\MitraController@destroy');    

    //Kunjungan
    $router->get('getMitra','Wisata\KunjController@getMitra');
    $router->get('getMitraSub/{kode_mitra}','Wisata\KunjController@getMitraSub');
    $router->get('getTahunList','Wisata\KunjController@getTahunList');
    $router->get('getTglServer','Wisata\KunjController@getTglServer');
    $router->get('getJumTgl/{tahun}/{bulan}','Wisata\KunjController@getJumTgl');
    $router->get('kunj','Wisata\KunjController@index');
    $router->get('getEdit','Wisata\KunjController@edit');
    $router->post('kunj','Wisata\KunjController@store');
    $router->put('kunj','Wisata\KunjController@update');
    $router->delete('kunj','Wisata\KunjController@destroy');    

});



?>