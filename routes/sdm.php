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

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('sdm/'.$filename)) {
        $success['message'] = 'Dokumen tidak tersedia!';
        $success['status'] = false;
    }
    return Storage::disk('s3')->response('sdm/'.$filename); 
});

$router->group(['middleware' => 'cors'], function () use ($router) {
    
    $router->post('login', 'AuthController@loginTarbak');
    $router->get('hash-pass', 'AuthController@hashPasswordTarbak');
    $router->get('hash-pass-nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
    
});

$router->group(['middleware' => 'auth:tarbak'], function () use ($router) {

    $router->get('profile', 'AdminTarbakController@profile');
    $router->get('users/{id}', 'AdminTarbakController@singleUser');
    $router->get('users', 'AdminTarbakController@allUsers');
    $router->get('cekPayload', 'AdminTarbakController@cekPayload');
    
    $router->post('update-password', 'AdminTarbakController@updatePassword');
    $router->post('update-foto', 'AdminTarbakController@updatePhoto');
    $router->post('update-background', 'AdminTarbakController@updateBackground');

    $router->get('menu/{kode_klp}', 'Sdm\MenuController@show');
    $router->get('data-pribadi', 'Sdm\DataPribadiController@index');
    $router->get('agama', 'Sdm\DataPribadiController@getAgama');
    $router->get('profesi', 'Sdm\DataPribadiController@getProfesi');
    $router->get('strata', 'Sdm\DataPribadiController@getStrata');
    $router->get('status-pajak', 'Sdm\DataPribadiController@getStatusPajak');
    $router->post('data-pribadi-edit', 'Sdm\DataPribadiController@update');

    $router->get('dinas','Sdm\DinasController@index');
    $router->post('dinas','Sdm\DinasController@store');
    $router->put('dinas','Sdm\DinasController@update');
    $router->delete('dinas','Sdm\DinasController@destroy');

    $router->get('keluarga','Sdm\KeluargaController@index');
    $router->get('keluarga-edit','Sdm\KeluargaController@show');
    $router->post('keluarga','Sdm\KeluargaController@store');
    $router->post('keluarga-edit','Sdm\KeluargaController@update');
    $router->delete('keluarga','Sdm\KeluargaController@destroy');

    $router->get('kepegawaian','Sdm\KepegawaianController@index');
    $router->post('kepegawaian-edit','Sdm\KepegawaianController@update');
    $router->get('sdm','Sdm\KepegawaianController@getSDM');
    $router->get('jabatan','Sdm\KepegawaianController@getJabatan');
    $router->get('golongan','Sdm\KepegawaianController@getGolongan');
    $router->get('unit','Sdm\KepegawaianController@getUnit');
    $router->get('pp','Sdm\KepegawaianController@getPP');
    $router->get('loker','Sdm\KepegawaianController@getLoker');

    $router->get('pelatihan','Sdm\PelatihanController@index');
    $router->get('pelatihan-edit','Sdm\PelatihanController@show');
    $router->post('pelatihan','Sdm\PelatihanController@store');
    $router->post('pelatihan-edit','Sdm\PelatihanController@update');
    $router->delete('pelatihan','Sdm\PelatihanController@destroy');
    
    $router->get('pendidikan','Sdm\PendidikanController@index');
    $router->get('pendidikan-edit','Sdm\PendidikanController@show');
    $router->post('pendidikan','Sdm\PendidikanController@store');
    $router->post('pendidikan-edit','Sdm\PendidikanController@update');
    $router->delete('pendidikan','Sdm\PendidikanController@destroy');
    
    $router->get('penghargaan','Sdm\PenghargaanController@index');
    $router->get('penghargaan-edit','Sdm\PenghargaanController@show');
    $router->post('penghargaan','Sdm\PenghargaanController@store');
    $router->post('penghargaan-edit','Sdm\PenghargaanController@update');
    $router->delete('penghargaan','Sdm\PenghargaanController@destroy');
    
    $router->get('sanksi','Sdm\SanksiController@index');
    $router->post('sanksi','Sdm\SanksiController@store');
    $router->put('sanksi','Sdm\SanksiController@update');
    $router->delete('sanksi','Sdm\SanksiController@destroy');

    

});