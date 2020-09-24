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
    if (!Storage::disk('s3')->exists('telu/'.$filename)) {
        $success['message'] = 'Dokumen tidak tersedia!';
        $success['status'] = false;
    }
    return Storage::disk('s3')->response('telu/'.$filename); 
});

$router->group(['middleware' => 'cors'], function () use ($router) {
    
    $router->post('login', 'AuthController@loginYptKug');
    $router->get('hash_pass', 'AuthController@hashPasswordYptKug');
    $router->get('hash_pass_nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');
    $router->get('db3', function () {
        
        $sql = DB::connection('sqlsrvyptkug')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
});

$router->group(['middleware' => 'auth:yptkug'], function () use ($router) {

    $router->get('profile', 'AdminYptKugController@profile');
    $router->get('users/{id}', 'AdminYptKugController@singleUser');
    $router->get('users', 'AdminYptKugController@allUsers');
    $router->get('cekPayload', 'AdminYptKugController@cekPayload');
    
    $router->post('update-password', 'AdminYptKugController@updatePassword');
    $router->post('update-foto', 'AdminYptKugController@updatePhoto');
    $router->post('update-background', 'AdminYptKugController@updateBackground');

    $router->get('upload', 'UploadController@upload');
    $router->post('upload', 'UploadController@proses_upload');
    $router->get('upload/{file}', 'UploadController@show');
    $router->get('menu/{kode_klp}', 'Dashboard\DashboardController@getMenu');
    $router->get('menu2/{kode_klp}', 'Dashboard\DashboardController@getMenu2');

    //PAGE 1
    $router->get('pencapaianYoY/{periode}', 'Dashboard\DashboardController@pencapaianYoY');
    $router->get('rkaVSReal/{periode}', 'Dashboard\DashboardController@rkaVSReal');
    $router->get('growthRKA/{periode}', 'Dashboard\DashboardController@growthRKA');
    $router->get('growthReal/{periode}', 'Dashboard\DashboardController@growthReal');


    //PAGE 2
    
    $router->get('komposisiPdpt/{periode}', 'Dashboard\DashboardController@komposisiPdpt');
    $router->get('rkaVSRealPdpt/{periode}', 'Dashboard\DashboardController@rkaVSRealPdpt');
    $router->get('totalPdpt/{periode}', 'Dashboard\DashboardController@totalPdpt');


    //PAGE 2
    
    $router->get('komposisiBeban/{periode}', 'Dashboard\DashboardController@komposisiBeban');
    $router->get('rkaVSRealBeban/{periode}', 'Dashboard\DashboardController@rkaVSRealBeban');
    $router->get('totalBeban/{periode}', 'Dashboard\DashboardController@totalBeban');

    //PAGE 4 Detail Pendapatan
    
    $router->get('pdptFakultas/{periode}/{kode_neraca}', 'Dashboard\DashboardController@pdptFakultas');
    $router->get('detailPdpt/{periode}/{kode_neraca}', 'Dashboard\DashboardController@detailPdpt');

    
    $router->get('pdptJurusan/{periode}/{kode_neraca}/{kode_bidang}', 'Dashboard\DashboardController@pdptJurusan');
    $router->get('detailPdptJurusan/{periode}/{kode_neraca}/{kode_bidang}/{tahun}', 'Dashboard\DashboardController@detailPdptJurusan');

    //PAGE 5 Detail Beban
    
    $router->get('bebanFakultas/{periode}/{kode_neraca}', 'Dashboard\DashboardController@bebanFakultas');
    $router->get('detailBeban/{periode}/{kode_neraca}', 'Dashboard\DashboardController@detailBeban');

    
    $router->get('bebanJurusan/{periode}/{kode_neraca}/{kode_bidang}', 'Dashboard\DashboardController@bebanJurusan');
    $router->get('detailBebanJurusan/{periode}/{kode_neraca}/{kode_bidang}/{tahun}', 'Dashboard\DashboardController@detailBebanJurusan');

    $router->get('user_device','UserDeviceController@index');
    $router->get('user_device/{nik}','UserDeviceController@show');
    $router->post('user_device','UserDeviceController@store');
    $router->put('user_device/{nik}','UserDeviceController@update');
    $router->delete('user_device/{nik}','UserDeviceController@destroy');
    
    $router->get('rka','Dashboard\DashboardController@getBCRKA');
    $router->get('growth-rka','Dashboard\DashboardController@getBCGrowthRKA');
    $router->get('tuition','Dashboard\DashboardController@getBCTuition');
    $router->get('growth-tuition','Dashboard\DashboardController@getBCGrowthTuition');
    $router->get('rka-persen','Dashboard\DashboardController@getBCRKAPersen');
    
    $router->post('notif-pusher', 'Dashboard\NotifController@sendPusher');
    $router->get('notif-pusher', 'Dashboard\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Dashboard\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminYptKugController@searchForm');
    $router->get('search-form-list', 'AdminYptKugController@searchFormList');

    $router->get('periode', 'Dashboard\DashboardController@getPeriode');
    
    $router->get('komponen-investasi','Dashboard\DashboardController@komponenInvestasi');
    $router->get('rka-real-investasi','Dashboard\DashboardController@rkaVSRealInvestasi');
    $router->get('penyerapan-investasi','Dashboard\DashboardController@penyerapanInvestasi');
});