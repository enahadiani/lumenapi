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
    $router->get('ptg-beban-nobukti','Bdh\PtgBebanController@generateNo');
    $router->get('ptg-beban','Bdh\PtgBebanController@index');
    $router->get('ptg-beban-detail','Bdh\PtgBebanController@show');
    $router->post('ptg-beban','Bdh\PtgBebanController@store');
    $router->post('ptg-beban-ubah','Bdh\PtgBebanController@update');
    $router->delete('ptg-beban','Bdh\PtgBebanController@destroy');

    $router->get('ptg-beban-pp','Bdh\PtgBebanController@getPP');
    $router->get('ptg-beban-akun','Bdh\PtgBebanController@getAkun');
    $router->get('ptg-beban-drk','Bdh\PtgBebanController@getDRK');
    $router->get('nik-buat','Bdh\PtgBebanController@getNIKBuat');
    $router->get('nik-tahu','Bdh\PtgBebanController@getNIKTahu');
    $router->get('nik-ver','Bdh\PtgBebanController@getNIKVer');
    $router->get('ptg-beban-budget','Bdh\PtgBebanController@cekBudget');
});

?>