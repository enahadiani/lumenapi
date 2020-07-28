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


$router->group(['middleware' => 'cors'], function () use ($router) {
    //approval dev
    $router->post('login', 'AuthController@loginAdminSilo');
    $router->get('hash-pass', 'AuthController@hashPasswordAdminSilo');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('apv/'.$filename)) {
        $success['message'] = 'Dokumen tidak tersedia!';
        $success['status'] = false;
        response()->json(['success'=>$success], 200); 
    }
    return Storage::disk('s3')->response('apv/'.$filename); 
});


$router->group(['middleware' => 'auth:silo'], function () use ($router) {
    
    $router->get('profile', 'AdminSiloController@profileMobileApv');

    $router->get('aju', function (Request $request) {
        if($data =  Auth::guard('silo')->user()){
            $nik= $data->nik;
            $kode_menu = $data->kode_klp_menu;
            switch($kode_menu){
                case 'APV-SM' :
                    $result = app('App\Http\Controllers\Apv\JuskebApprovalController')->getPengajuan();
                break;
                case 'APV-PUSAT' :
                    $result = app('App\Http\Controllers\Apv\JuspoApprovalController')->getPengajuan();
                break;
                default :
                $success['status'] = false;
                $success['message'] = "Akses menu tidak tersedia !";
                $result = response()->json(['success'=>$success], 200);    
                break;
            }
            return $result;
        }
    });

    $router->get('detail/{no_aju}', function ($no_aju) {
        if($data =  Auth::guard('silo')->user()){
            $nik= $data->nik;
            $kode_menu = $data->kode_klp_menu;
            switch($kode_menu){
                case 'APV-SM' :
                    $result = app('App\Http\Controllers\Apv\JuskebApprovalController')->show($no_aju);
                break;
                case 'APV-PUSAT' :
                    $result = app('App\Http\Controllers\Apv\JuspoApprovalController')->show($no_aju);
                break;
                default :
                $success['status'] = false;
                $success['message'] = "Akses menu tidak tersedia !";
                $result = response()->json(['success'=>$success], 200);    
                break;
            }
            return $result;
        }
    });

    $router->get('histori', function () {
        if($data =  Auth::guard('silo')->user()){
            $nik= $data->nik;
            $kode_menu = $data->kode_klp_menu;
            switch($kode_menu){
                case 'APV-SM' :
                    $result = app('App\Http\Controllers\Apv\JuskebApprovalController')->index();
                break;
                case 'APV-PUSAT' :
                    $result = app('App\Http\Controllers\Apv\JuspoApprovalController')->index();
                break;
                default :
                $success['status'] = false;
                $success['message'] = "Akses menu tidak tersedia !";
                $result = response()->json(['success'=>$success], 200);    
                break;
            }
            return $result;
        }
    });

    $router->post('app', function (Request $request) {
        if($data =  Auth::guard('silo')->user()){
            $nik= $data->nik;
            $kode_menu = $data->kode_klp_menu;
            switch($kode_menu){
                case 'APV-SM' :
                    $result = app('App\Http\Controllers\Apv\JuskebApprovalController')->store($request);
                break;
                case 'APV-PUSAT' :
                    $result = app('App\Http\Controllers\Apv\JuspoApprovalController')->store($request);
                break;
                default :
                $success['status'] = false;
                $success['message'] = "Akses menu tidak tersedia !";
                $result = response()->json(['success'=>$success], 200);    
                break;
            }
            return $result;
        }
    });
    
});



?>