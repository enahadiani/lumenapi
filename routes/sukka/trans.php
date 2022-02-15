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

    $router->get('app-juskeb','Sukka\ApprovalJuskebController@index');
    $router->get('app-juskeb-aju','Sukka\ApprovalJuskebController@getPengajuan');
    $router->get('app-juskeb-detail','Sukka\ApprovalJuskebController@show');
    $router->post('app-juskeb','Sukka\ApprovalJuskebController@store');
    $router->get('app-juskeb-status','Sukka\ApprovalJuskebController@getStatus');
    $router->get('app-juskeb-preview','Sukka\ApprovalJuskebController@getPreview');
    $router->post('app-juskeb-send-email', 'Sukka\ApprovalJuskebController@sendNotifikasi');

     // PENGAJUAN RRA
     $router->get('aju-rra-nobukti','Sukka\PengajuanRRAController@generateNo');
     $router->get('aju-rra','Sukka\PengajuanRRAController@index');
     $router->get('aju-rra-detail','Sukka\PengajuanRRAController@show');
     $router->post('aju-rra','Sukka\PengajuanRRAController@store');
     $router->post('aju-rra-ubah','Sukka\PengajuanRRAController@update');
     $router->delete('aju-rra','Sukka\PengajuanRRAController@destroy');
     $router->delete('aju-rra-dok','Sukka\PengajuanRRAController@destroyDok');
     $router->get('aju-rra-flow', 'Sukka\PengajuanRRAController@getAppFlow');
 
     $router->get('aju-rra-lokasi','Sukka\PengajuanRRAController@getLokasi');
     $router->get('aju-rra-nik-app','Sukka\PengajuanRRAController@getNIKApp');
     $router->get('aju-rra-jenis-dok','Sukka\PengajuanRRAController@getJenisDokumen');
     $router->get('aju-rra-akun','Sukka\PengajuanRRAController@getAkun');
     $router->get('aju-rra-pp','Sukka\PengajuanRRAController@getPP');
     $router->get('aju-rra-drk','Sukka\PengajuanRRAController@getDRK');
     $router->get('aju-rra-drk-beri','Sukka\PengajuanRRAController@getDRKPemberi');
     $router->get('aju-rra-cek-budget','Sukka\PengajuanRRAController@cekBudget');
     $router->get('aju-rra-saldo','Sukka\PengajuanRRAController@getSaldo');
 
     $router->post('aju-rra-excel','Sukka\PengajuanRRAController@importExcel');
     $router->get('aju-rra-tmp','Sukka\PengajuanRRAController@getDataTmp');
     $router->get('aju-rra-preview', 'Sukka\PengajuanRRAController@getPreview');
     
     $router->post('aju-rra-send-email', 'Sukka\PengajuanRRAController@sendNotifikasi');
     $router->get('tes-email', 'Sukka\PengajuanRRAController@getEmailView');
 
    // APPROVAL RRA
    $router->get('app-rra','Sukka\ApprovalRRAController@index');
    $router->get('app-rra-aju','Sukka\ApprovalRRAController@getPengajuan');
    $router->get('app-rra-detail','Sukka\ApprovalRRAController@show');
    $router->post('app-rra','Sukka\ApprovalRRAController@store');
    $router->get('app-rra-status','Sukka\ApprovalRRAController@getStatus');
    $router->get('app-rra-preview','Sukka\ApprovalRRAController@getPreview');
    $router->post('app-rra-send-email', 'Sukka\ApprovalRRAController@sendNotifikasi');

});



?>