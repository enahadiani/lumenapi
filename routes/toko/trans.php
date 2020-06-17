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
    //Penjualan (POS)
    $router->get('penjualan-open','Toko\PenjualanController@getNoOpen');
    $router->post('penjualan','Toko\PenjualanController@store');
    $router->get('penjualan-nota','Toko\PenjualanController@getNota');
    $router->get('penjualan-bonus','Toko\PenjualanController@cekBonus');

    //Open Kasir
    $router->get('open-kasir','Toko\OpenKasirController@index');
    $router->post('open-kasir','Toko\OpenKasirController@store');
    $router->put('open-kasir','Toko\OpenKasirController@update');
    
    //Close Kasir
    $router->get('close-kasir-new','Toko\CloseKasirController@getOpenKasir');
    $router->get('close-kasir-finish','Toko\CloseKasirController@index');
    $router->get('close-kasir-detail','Toko\CloseKasirController@show');
    $router->post('close-kasir','Toko\CloseKasirController@store');

    //Pembelian
    $router->get('pembelian','Toko\PembelianController@index');
    $router->get('pembelian-detail','Toko\PembelianController@show');
    $router->post('pembelian','Toko\PembelianController@store');
    $router->put('pembelian','Toko\PembelianController@update');
    $router->delete('pembelian','Toko\PembelianController@destroy');
    $router->get('pembelian-nota','Toko\PembelianController@getNota');
    $router->get('pembelian-barang','Toko\PembelianController@getBarang');

    //Retur Pembelian
    $router->get('retur-beli-new','Toko\ReturPembelianController@getNew');
    $router->get('retur-beli-finish','Toko\ReturPembelianController@index');
    $router->get('retur-beli-detail','Toko\ReturPembelianController@show');
    $router->post('retur-beli','Toko\ReturPembelianController@store');
    $router->get('retur-beli-barang','Toko\ReturPembelianController@getBarang');

    //Stok Opname
    $router->post('stok-opname-import-excel', 'Toko\StockOpnameController@importExcel');

});



?>