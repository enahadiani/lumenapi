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
    $router->get('gl_filter_lokasi','Gl\FilterController@getGlFilterLokasi');
    $router->get('gl_filter_periode','Gl\FilterController@getGlFilterPeriode');
    $router->get('gl_filter_modul','Gl\FilterController@getGlFilterModul');
    $router->get('gl_filter_bukti','Gl\FilterController@getGlFilterBukti');
    $router->get('gl_filter_akun','Gl\FilterController@getGlFilterAkun');
    $router->get('gl_filter_fs','Gl\FilterController@getGlFilterFs');

    //konten laporan
    $router->get('gl_report_jurnal','Gl\LaporanController@getGlReportJurnal');
    $router->get('gl_report_jurnal_form','Gl\LaporanController@getGlReportJurnalForm');
    $router->get('gl_report_buku_besar','Gl\LaporanController@getGlReportBukuBesar');
    $router->get('gl_report_neraca_lajur','Gl\LaporanController@getGlReportNeracaLajur');
    $router->get('gl_report_neraca','Gl\LaporanController@getGlReportNeraca');
    $router->get('gl_report_laba_rugi','Gl\LaporanController@getGlReportLabaRugi');
});
