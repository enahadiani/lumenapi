<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
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

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    //filter laporan
    $router->get('gl_periode','Gl\FilterController@getGlPeriode');
    $router->get('gl_modul','Gl\FilterController@getGlModul');
    $router->get('gl_bukti','Gl\FilterController@getGlBukti');
    $router->get('gl_akun','Gl\FilterController@getGlAkun');
    $router->get('gl_fs','Gl\FilterController@getGlFs');

    //konten laporan
    $router->get('gl_rpt_jurnal','Gl\LaporanController@getGlRptJurnal');
    $router->get('gl_rpt_jurnal_form','Gl\LaporanController@getGlRptJurnalForm');
    $router->get('gl_rpt_buku_besar','Gl\LaporanController@getGlRptBukuBesar');
    $router->get('gl_rpt_neraca_lajur','Gl\LaporanController@getGlRptNeracaLajur');
    $router->get('gl_rpt_neraca','Gl\LaporanController@getGlRptNeraca');
    $router->get('gl_rpt_laba_rugi','Gl\LaporanController@getGlRptLabaRugi');
});
