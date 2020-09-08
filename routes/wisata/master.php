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

    //Mitra
    $router->get('mitra','Wisata\MitraController@index');
    $router->get('mitrabid','Wisata\MitraController@edit');
    $router->post('mitra','Wisata\MitraController@store');
    $router->put('mitra','Wisata\MitraController@update');
    $router->delete('mitra','Wisata\MitraController@destroy');    

    //Kunjungan
    $router->get('getMitra','Wisata\KunjController@getMitra');
    $router->get('getMitraBid/{kode_mitra}','Wisata\KunjController@getMitraBid');
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