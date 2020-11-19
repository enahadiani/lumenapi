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
    
    $router->get('filter-lokasi','Dev\FilterController@getFilterLokasi');
    $router->get('filter-nim','Dev\FilterController@getFilterNIM');
    $router->get('filter-jurusan','Dev\FilterController@getFilterJurusan');
    $router->get('filter-tagihan','Dev\FilterController@getFilterTagihan');
    $router->get('filter-bayar','Dev\FilterController@getFilterBayar');

    $router->get('lap-siswa','Dev\LaporanController@getLapSiswa');
    $router->get('lap-tagihan','Dev\LaporanController@getLapTagihan');
    $router->get('lap-saldo-tagihan','Dev\LaporanController@getLapSaldo');
    $router->get('lap-bayar','Dev\LaporanController@getLapBayar');

});



?>