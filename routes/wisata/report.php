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
    $router->get('list-bulan','Wisata\LaporanController@getBulanList');
    $router->get('list-tahun','Wisata\LaporanController@getTahunList');
    
    $router->get('lap-bidang','Wisata\LaporanController@getReportBidang');
    $router->get('lap-mitra','Wisata\LaporanController@getReportMitra');
    $router->get('lap-kunjungan','Wisata\LaporanController@getReportKunjungan');
});



?>