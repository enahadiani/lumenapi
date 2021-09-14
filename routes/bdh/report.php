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
    $router->get('filter-periodever','Bdh\FilterController@DataPeriodeVerifikasi');
    $router->get('filter-periodespb','Bdh\FilterController@DataPeriodeSPB');
    $router->get('filter-periodebayar','Bdh\FilterController@DataPeriodeBayar');
    $router->get('filter-periodepb','Bdh\FilterController@DataPeriodePB');
    $router->get('filter-periode','Bdh\FilterController@DataPeriode');
    
    $router->get('filter-nover','Bdh\FilterController@DataNoBuktiVerifikasi');
    $router->get('filter-nospb','Bdh\FilterController@DataNoBuktiSPB');
    $router->get('filter-nobayar','Bdh\FilterController@DataNoBuktiBayar');
    $router->get('filter-nojurfinalpertanggungpanjar','Bdh\FilterController@DataNoBuktiJurnalFinalPertanggungPanjar');
    $router->get('filter-nopb','Bdh\FilterController@DataNoBuktiPB');

    $router->get('lap-verifikasi','Bdh\LaporanController@DataVerifikasi');
    $router->get('lap-spb','Bdh\LaporanController@DataSPB');
    $router->get('lap-bayar','Bdh\LaporanController@DataPembayaran');
    $router->get('lap-transbank','Bdh\LaporanController@DataTransferBank');
    $router->get('lap-jurfinalpertanggungpanjar','Bdh\LaporanController@DataJurnalFinalPertanggungPanjar');
    $router->get('lap-pb','Bdh\LaporanController@DataPB');

});



?>