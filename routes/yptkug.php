<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);

$router->group(['middleware' => 'cors'], function () use ($router) {
    $router->post('login', 'AuthController@loginYptKug');
});

$router->group(['middleware' => 'auth:yptkug'], function () use ($router) {
    //route report
    $router->get('lokasi','Dashboard\ReportController@getLokasi');
    $router->get('akun','Dashboard\ReportController@getAkun');
    $router->get('pp','Dashboard\ReportController@getPp');
    $router->get('drk','Dashboard\ReportController@getDrk');
    $router->get('periode-aktif','Dashboard\ReportController@getPeriodeAktif');
    $router->get('tb','Dashboard\ReportController@getTb');
    $router->get('anggaran','Dashboard\ReportController@getAnggaran');
    $router->get('anggaran-realisasi','Dashboard\ReportController@getAnggaranRealBulan');

    $router->get('kartu-piutang','Dashboard\ReportController@getKartuPiutang');
    $router->get('kartu-pdd','Dashboard\ReportController@getKartuPDD');

});