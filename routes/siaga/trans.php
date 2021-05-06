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
    
});



?>