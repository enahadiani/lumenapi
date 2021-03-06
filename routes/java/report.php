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


$router->group(['middleware' => 'auth:toko'], function () use ($router) {
    $router->get('filter-proyek','Java\FilterController@getNoBukti');

    $router->get('lap-kartu-proyek','Java\LaporanProyekController@getKartuProyek');     
    $router->get('lap-saldo-proyek','Java\LaporanProyekController@getSaldoProyek');     

});

?>