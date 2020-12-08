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


$router->group(['middleware' => 'auth:yakes'], function () use ($router) {
    //tanggal server
    $router->get('getTglServer','Yakes\FilterController@getTglServer');     
    //periode input
    $router->get('getPerInput','Yakes\FilterController@getPerInput');     

    //masakun    
    $router->get('listAkunAktif','Yakes\MasakunController@listAkunAktif');         
    $router->get('cariAkunAktif','Yakes\MasakunController@cariAkunAktif');
    $router->get('masakun','Yakes\MasakunController@index');    
    $router->post('masakun','Yakes\MasakunController@store');
    $router->put('masakun','Yakes\MasakunController@update');
    $router->delete('masakun','Yakes\MasakunController@destroy'); 
    
    //pp
    $router->get('cariPPAktif','Yakes\PPController@cariPPAktif');
    $router->get('listPPAktif','Yakes\PPController@listPPAktif');         
    
    //fs
    $router->get('listFSAktif','Yakes\FSController@listFSAktif');         
    $router->get('cariFSAktif','Yakes\FSController@cariFSAktif');
    $router->get('fs','Yakes\FSController@index');
    $router->post('fs','Yakes\FSController@store');
    $router->put('fs','Yakes\FSController@update');
    $router->delete('fs','Yakes\FSController@destroy'); 

    //flagakun
    $router->get('cariFlag','Yakes\FlagAkunController@cariFlag');
    $router->get('flagakun','Yakes\FlagAkunController@index');
    $router->post('flagakun','Yakes\FlagAkunController@store');
    $router->put('flagakun','Yakes\FlagAkunController@update');
    $router->delete('flagakun','Yakes\FlagAkunController@destroy'); 
    
    //flagrelasi
    $router->get('getFlag','Yakes\FlagRelasiController@getFlag');
    $router->get('getAkunFlag/{kode_flag}','Yakes\FlagRelasiController@getAkunFlag');
    $router->get('getAkun','Yakes\FlagRelasiController@getAkun');    
    $router->get('cariAkunFlag','Yakes\FlagRelasiController@cariAkunFlag');    
    $router->put('flagrelasi','Yakes\FlagRelasiController@update');
    $router->delete('flagrelasi','Yakes\FlagRelasiController@destroy'); 

    //Format Laporan
    $router->get('format-laporan','Yakes\FormatLaporanController@show');
    $router->post('format-laporan','Yakes\FormatLaporanController@store');
    $router->put('format-laporan','Yakes\FormatLaporanController@update');
    $router->delete('format-laporan','Yakes\FormatLaporanController@destroy');
    $router->get('format-laporan-versi','Yakes\FormatLaporanController@getVersi');
    $router->get('format-laporan-tipe','Yakes\FormatLaporanController@getTipe');
    $router->get('format-laporan-relakun','Yakes\FormatLaporanController@getRelakun');
    $router->post('format-laporan-relasi','Yakes\FormatLaporanController@simpanRelasi');
    $router->post('format-laporan-move','Yakes\FormatLaporanController@simpanMove');

    //ADMIN
    //Menu
    $router->get('menu','Yakes\MenuController@index');
    $router->post('menu','Yakes\MenuController@store');
    $router->put('menu','Yakes\MenuController@update');
    $router->delete('menu','Yakes\MenuController@destroy');
    $router->get('menu-klp','Yakes\MenuController@getKlp');
    $router->post('menu-move','Yakes\MenuController@simpanMove');

    //Akses User
    $router->get('akses-user','Yakes\HakaksesController@index');
    $router->post('akses-user','Yakes\HakaksesController@store');
    $router->get('akses-user-detail','Yakes\HakaksesController@show');
    $router->put('akses-user','Yakes\HakaksesController@update');
    $router->delete('akses-user','Yakes\HakaksesController@destroy');
    $router->get('akses-user-menu','Yakes\HakaksesController@getMenu');
    
    //Form
    $router->get('form','Yakes\FormController@index');
    $router->post('form','Yakes\FormController@store');
    $router->put('form','Yakes\FormController@update');
    $router->delete('form','Yakes\FormController@destroy');

    //Karyawan
    $router->get('karyawan','Yakes\KaryawanController@index');
    $router->post('karyawan','Yakes\KaryawanController@store');
    $router->get('karyawan-detail','Yakes\KaryawanController@show');
    $router->post('karyawan-ubah','Yakes\KaryawanController@update');
    $router->delete('karyawan','Yakes\KaryawanController@destroy');

    //Kelompok Menu
    $router->get('menu-klp','Yakes\KelompokMenuController@index');
    $router->post('menu-klp','Yakes\KelompokMenuController@store');
    $router->put('menu-klp','Yakes\KelompokMenuController@update');
    $router->delete('menu-klp','Yakes\KelompokMenuController@destroy');

    //Unit
    $router->get('unit','Yakes\UnitController@index');
    $router->post('unit','Yakes\UnitController@store');
    $router->put('unit','Yakes\UnitController@update');
    $router->delete('unit','Yakes\UnitController@destroy');

});



?>