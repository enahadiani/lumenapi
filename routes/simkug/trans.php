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
$router->options('{all:.*}', ['middleware' => ['cors','XSS'], function() {
    return response('');
}]);

$router->group(['middleware' => ['auth:simkug','XSS']], function () use ($router) {
    $router->get('serah-dok-akses-form', 'Simkug\SerahTerimaDokumenController@cekFormAkses');
    $router->get('serah-dok-load', 'Simkug\SerahTerimaDokumenController@loadData');
    $router->get('serah-dok-penerima', 'Simkug\SerahTerimaDokumenController@getNIKTerima');
    $router->post('serah-dok', 'Simkug\SerahTerimaDokumenController@store');

    $router->get('serah-dok-revisi-akses-form', 'Simkug\SerahTerimaRevisiVerDokController@cekFormAkses');
    $router->get('serah-dok-revisi-load', 'Simkug\SerahTerimaRevisiVerDokController@loadData');
    $router->post('serah-dok-revisi', 'Simkug\SerahTerimaRevisiVerDokController@store');
});





?>