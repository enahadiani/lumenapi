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
    $router->get('juskeb-preview', 'Sukka\PengajuanJuskebController@getPreview');
    
    $router->post('send-email', 'Sukka\PengajuanJuskebController@sendNotifikasi');
    $router->get('tes-email', 'Sukka\PengajuanJuskebController@getEmailView');

    $router->get('app-juskeb','Sukka\ApprovalJuskebController@index');
    $router->get('app-juskeb-aju','Sukka\ApprovalJuskebController@getPengajuan');
    $router->get('app-juskeb-detail','Sukka\ApprovalJuskebController@show');
    $router->post('app-juskeb','Sukka\ApprovalJuskebController@store');
    $router->get('app-juskeb-status','Sukka\ApprovalJuskebController@getStatus');
    $router->get('app-juskeb-preview','Sukka\ApprovalJuskebController@getPreview');
    $router->post('app-juskeb-send-email', 'Sukka\PengajuanJuskebController@sendNotifikasi');

});



?>