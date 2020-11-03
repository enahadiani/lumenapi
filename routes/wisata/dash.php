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
    $router->get('data-kunjungan','Wisata\DashboardController@getDataKunjungan');
    $router->get('data-bidang','Wisata\DashboardController@getDataBidang');
    $router->get('data-mitra','Wisata\DashboardController@getDataMitra');
    $router->get('top-daerah','Wisata\DashboardController@getTopDaerah');
    $router->get('top-mitra','Wisata\DashboardController@getTopMitra');
    $router->get('kunjungan-tahunan','Wisata\DashboardController@getKunjunganTahunan');
    
});



?>