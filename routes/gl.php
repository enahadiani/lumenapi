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


$router->group(['middleware' => 'cors'], function () use ($router) {

    $router->post('login', 'AuthController@loginAdmin');
    $router->get('hashPass', 'AuthController@hashPasswordAdmin');
    $router->get('db2', function () {
        
        $sql = DB::connection('sqlsrv2')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
});

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    $router->get('upload', 'UploadController@upload');
    $router->post('upload', 'UploadController@proses_upload');
    $router->get('upload/{file}', 'UploadController@show');
    
    $router->get('profile', 'AdminController@profile');
    $router->get('users/{id}', 'AdminController@singleUser');
    $router->get('users', 'AdminController@allUsers');
    $router->get('cekPayload', 'AdminController@cekPayload');
    
    //Menu
    $router->get('menu/{kode_klp}', 'Gl\MenuController@show');
    
    //FS
    $router->get('fs','Gl\FsController@index');
    $router->get('fs/{id}','Gl\FsController@show');
    $router->post('fs','Gl\FsController@store');
    $router->put('fs/{id}','Gl\FsController@update');
    $router->delete('fs/{id}','Gl\FsController@destroy');

    //lokasi
    $router->get('lokasi','Gl\LokasiController@index');
    $router->get('lokasi/{kode_lokasi}','Gl\LokasiController@show');
    $router->post('lokasi','Gl\LokasiController@store');
    $router->put('lokasi/{kode_lokasi}','Gl\LokasiController@update');
    $router->delete('lokasi/{kode_lokasi}','Gl\LokasiController@destroy');


    //PP
    $router->get('pp','Gl\PpController@index');
    $router->get('pp/{kode_pp}','Gl\PpController@show');
    $router->post('pp','Gl\PpController@store');
    $router->put('pp/{kode_pp}','Gl\PpController@update');
    $router->delete('pp/{kode_pp}','Gl\PpController@destroy');
    
    //MASAKUN
    $router->get('masakun','Gl\MasakunController@index');
    $router->get('masakun/{kode_akun}','Gl\MasakunController@show');
    $router->post('masakun','Gl\MasakunController@store');
    $router->put('masakun/{kode_akun}','Gl\MasakunController@update');
    $router->delete('masakun/{kode_akun}','Gl\MasakunController@destroy');
    
    $router->get('currency','Gl\MasakunController@getCurrency');
    $router->get('modul','Gl\MasakunController@getModul');
    $router->get('flag_akun','Gl\MasakunController@getFlagAkun');
    $router->get('neraca/{kode_fs}','Gl\MasakunController@getNeraca');
    $router->get('fsgar','Gl\MasakunController@getFSGar');
    $router->get('neracagar/{kode_fs}','Gl\MasakunController@getNeracaGar');

    $router->get('user-device','UserDeviceController@index');
    $router->get('user-device/{nik}','UserDeviceController@show');
    $router->post('user-device','UserDeviceController@store');
    $router->put('user-device/{nik}','UserDeviceController@update');
    $router->delete('user-device/{nik}','UserDeviceController@destroy');

    $router->get('jurnal','Gl\JurnalController@index');
    $router->get('jurnal/{no_bukti}','Gl\JurnalController@show');
    $router->post('jurnal','Gl\JurnalController@store');
    $router->put('jurnal','Gl\JurnalController@update');
    $router->delete('jurnal/{no_bukti}','Gl\JurnalController@destroy');
    $router->get('pp-list','Gl\JurnalController@getPP');
    $router->get('akun','Gl\JurnalController@getAkun');
    $router->get('nikperiksa','Gl\JurnalController@getNIKPeriksa');
    $router->get('nikperiksa/{nik}','Gl\JurnalController@getNIKPeriksaByNIK');
    $router->get('jurnal-periode','Gl\JurnalController@getPeriodeJurnal');

    $router->post('loadData','Gl\PostingController@loadData');
    $router->get('modul2','Gl\PostingController@getModul');
    $router->post('posting','Gl\PostingController@store');

    //Format Laporan
    $router->get('format-laporan','Gl\JurnalController@show');
    $router->post('format-laporan','Gl\JurnalController@store');
    $router->put('format-laporan','Gl\JurnalController@update');
    $router->delete('format-laporan','Gl\JurnalController@destroy');
    $router->get('format-laporan-versi','Gl\JurnalController@getVersi');
    $router->get('format-laporan-tipe','Gl\JurnalController@getTipe');
    $router->get('format-laporan-relakun','Gl\JurnalController@getRelakun');
    $router->post('format-laporan-relasi','Gl\JurnalController@simpanRelasi');
    $router->post('format-laporan-move','Gl\JurnalController@simpanMove');
});
