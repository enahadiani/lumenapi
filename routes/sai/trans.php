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

    $router->get('kontrak','Sai\KontrakController@index');
    $router->post('kontrak','Sai\KontrakController@store');
    $router->get('kontrak-detail','Sai\KontrakController@show');
    $router->post('kontrak-ubah','Sai\KontrakController@update');
    $router->delete('kontrak','Sai\KontrakController@destroy');

    $router->get('tagihan','Sai\TagihanController@index');
    $router->post('tagihan','Sai\TagihanController@store');
    $router->get('tagihan-detail','Sai\TagihanController@show');
    $router->post('tagihan-ubah','Sai\TagihanController@update');
    $router->delete('tagihan','Sai\TagihanController@destroy');

    $router->get('faktur-pajak','Sai\FakturPajakController@index');
    $router->post('faktur-pajak','Sai\FakturPajakController@store');
    $router->get('faktur-pajak-detail','Sai\FakturPajakController@show');
    $router->post('faktur-pajak-ubah','Sai\FakturPajakController@update');
    $router->delete('faktur-pajak','Sai\FakturPajakController@destroy');

    $router->get('pembayaran','Sai\PembayaranController@index');
    $router->post('pembayaran','Sai\PembayaranController@store');
    $router->get('pembayaran-detail','Sai\PembayaranController@show');
    $router->post('pembayaran-ubah','Sai\PembayaranController@update');
    $router->delete('pembayaran','Sai\PembayaranController@destroy');

    $router->get('tagihan-maintain','Sai\TagihanMaintainController@index');
    $router->get('tagihan-maintain-load','Sai\TagihanMaintainController@loadData');
    $router->post('tagihan-maintain','Sai\TagihanMaintainController@store');
    $router->get('tagihan-maintain-detail','Sai\TagihanMaintainController@show');
    $router->post('tagihan-maintain-ubah','Sai\TagihanMaintainController@update');
    $router->delete('tagihan-maintain','Sai\TagihanMaintainController@destroy');
});



?>