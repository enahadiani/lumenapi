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
    //Justifikasi Kebutuhan
    $router->get('juskeb', 'Sukka\PengajuanJuskebController@index');
    $router->get('juskeb-detail', 'Sukka\PengajuanJuskebController@show');
    $router->get('juskeb-pp', 'Sukka\PengajuanJuskebController@getPP');
    $router->get('juskeb-app-flow', 'Sukka\PengajuanJuskebController@getAppFlow');
    $router->get('juskeb-jenis', 'Sukka\PengajuanJuskebController@getJenis');
    $router->post('juskeb', 'Sukka\PengajuanJuskebController@store');
    $router->put('juskeb', 'Sukka\PengajuanJuskebController@update');
    $router->delete('juskeb', 'Sukka\PengajuanJuskebController@destroy');
});



?>