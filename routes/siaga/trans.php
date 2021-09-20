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

$router->group(['middleware' => 'auth:siaga'], function () use ($router) {
    
    $router->get('aju-vp','Siaga\ApprovalController@pengajuanVP');
    $router->get('aju-unit','Siaga\ApprovalController@pengajuanUnit');
    $router->get('aju-budget','Siaga\ApprovalController@pengajuanBudget');
    $router->get('aju-dir','Siaga\ApprovalController@pengajuanDir');

    $router->get('aju-detail','Siaga\ApprovalController@detail');

    $router->post('app-vp','Siaga\ApprovalController@approvalVP');
    $router->post('app-unit','Siaga\ApprovalController@approvalUnit');
    $router->post('app-budget','Siaga\ApprovalController@approvalBudget');
    $router->post('app-dir','Siaga\ApprovalController@approvalDir');
    
    //Approval 
    $router->get('app','Siaga\Approval2Controller@index');
    $router->get('app-aju','Siaga\Approval2Controller@getPengajuan');
    $router->get('app-detail','Siaga\Approval2Controller@show');
    $router->get('app-detail-history','Siaga\Approval2Controller@detailHistory');
    $router->post('app','Siaga\Approval2Controller@store');
    $router->get('app-status','Siaga\Approval2Controller@getStatus');
    $router->get('app-preview','Siaga\Approval2Controller@getPreview');

    
    //Approval 
    $router->get('app-spb','Siaga\ApprovalSPBController@index');
    $router->get('app-spb-aju','Siaga\ApprovalSPBController@getPengajuan');
    $router->get('app-spb-detail','Siaga\ApprovalSPBController@show');
    $router->post('app-spb','Siaga\ApprovalSPBController@store');
    $router->get('app-spb-status','Siaga\ApprovalSPBController@getStatus');
    $router->get('app-spb-preview','Siaga\ApprovalSPBController@getPreview');
    
    $router->post('send-email', 'Siaga\Approval2Controller@sendNotifikasi');
    $router->post('cek', 'Siaga\Approval2Controller@cek');
    $router->post('send-email-saku3', 'Siaga\Approval2Controller@sendEmailSaku3');
    
});



?>