<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
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
    
    // $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->get('hashPass', 'AuthController@hashPassword');
    $router->get('db1', function () {
        
        $sql = DB::connection('sqlsrv')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });

    //approval dev
    $router->post('apv_login', 'AuthController@loginAdmin');
    $router->get('apv_hash_pass', 'AuthController@hashPasswordAdmin');
});

$router->group(['middleware' => 'auth:user'], function () use ($router) {

    
    $router->get('upload', 'UploadController@upload');
    $router->post('upload', 'UploadController@proses_upload');
    $router->get('upload/{file}', 'UploadController@show');
    
    $router->get('profile', 'UserController@profile');
    $router->get('users/{id}', 'UserController@singleUser');
    $router->get('users', 'UserController@allUsers');
    $router->get('cekPayload', 'UserController@cekPayload');

    $router->get('aju', function () {
        if($data =  Auth::guard('user')->user()){
            $nik= $data->nik;
            //Pengajuan
            $sql = DB::connection('sqlsrv')->select("select menu_mobile from hakakses where nik='$nik' ");
            $row = json_decode(json_encode($sql),true);
            switch($row[0]["menu_mobile"]){
                case 'APPSM' :
                    $result = app('App\Http\Controllers\Approval\ApprovalController')->pengajuan();
                break;
                case 'APPFIN' :
                    $result = app('App\Http\Controllers\Approval\ApprovalController')->pengajuanfinal();
                break;
                case 'APPDIR' :
                    $result = app('App\Http\Controllers\Approval\ApprovalController')->pengajuandir();
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

    

    // $router->get('ajusm', 'Approval\ApprovalController@pengajuan'); 
    // $router->get('ajufin', 'Approval\ApprovalController@pengajuanfinal');
    // $router->get('ajudir', 'Approval\ApprovalController@pengajuandir');

    $router->get('aju_history/{jenis}', 'Approval\ApprovalController@ajuHistory');

    $router->get('ajudet/{no_aju}', 'Approval\ApprovalController@detail');
    $router->get('ajurek/{no_aju}', 'Approval\ApprovalController@rekening');
    $router->get('ajujurnal/{no_aju}', 'Approval\ApprovalController@jurnal');

    //Approval 

    // $router->post('appsm', 'Approval\ApprovalController@approvalSM');
    // $router->post('appfin', 'Approval\ApprovalController@approvalFinal');
    // $router->post('appdir', 'Approval\ApprovalController@approvalDir');

    $router->post('app', function (Request $request) {
        if($data =  Auth::guard('user')->user()){
            $nik= $data->nik;
            //Pengajuan
            $sql = DB::connection('sqlsrv')->select("select menu_mobile from hakakses where nik='$nik' ");
            $row = json_decode(json_encode($sql),true);
            switch($row[0]["menu_mobile"]){
                case 'APPSM' :
                    $result = app('App\Http\Controllers\Approval\ApprovalController')->approvalSM($request);
                break;
                case 'APPFIN' :
                    $result = app('App\Http\Controllers\Approval\ApprovalController')->approvalFinal($request);
                break;
                case 'APPDIR' :
                    $result = app('App\Http\Controllers\Approval\ApprovalController')->approvalDir($request);
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

    $router->get('user_device','UserDeviceController@index');
    $router->get('user_device/{nik}','UserDeviceController@show');
    $router->post('user_device','UserDeviceController@store');
    $router->put('user_device/{nik}','UserDeviceController@update');
    $router->delete('user_device/{nik}','UserDeviceController@destroy');

});

$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    
    $router->get('apv_profile', 'AdminController@profile');
    $router->get('apv_users/{id}', 'AdminController@singleUser');
    $router->get('apv_users', 'AdminController@allUsers');
    $router->get('apv_cek_payload', 'AdminController@cekPayload');

    //Master Karyawan
    $router->get('apv_karyawan','Approval\KaryawanController@index');
    $router->get('apv_karyawan/{nik}','Approval\KaryawanController@show');
    $router->post('apv_karyawan','Approval\KaryawanController@store');
    $router->put('apv_karyawan/{nik}','Approval\KaryawanController@update');
    $router->delete('apv_karyawan/{nik}','Approval\KaryawanController@destroy');

    //Master Jabatan
    $router->get('apv_jabatan','Approval\JabatanController@index');
    $router->get('apv_jabatan/{kode_jab}','Approval\JabatanController@show');
    $router->post('apv_jabatan','Approval\JabatanController@store');
    $router->put('apv_jabatan/{kode_jab}','Approval\JabatanController@update');
    $router->delete('apv_jabatan/{kode_jab}','Approval\JabatanController@destroy');

    //Master Unit
    $router->get('apv_unit','Approval\UnitController@index');
    $router->get('apv_unit/{kode_pp}','Approval\UnitController@show');
    $router->post('apv_unit','Approval\UnitController@store');
    $router->put('apv_unit/{kode_pp}','Approval\UnitController@update');
    $router->delete('apv_unit/{kode_pp}','Approval\UnitController@destroy');

    //Master Role
    $router->get('apv_role','Approval\RoleController@index');
    $router->get('apv_role/{kode_role}','Approval\RoleController@show');
    $router->post('apv_role','Approval\RoleController@store');
    $router->put('apv_role/{kode_role}','Approval\RoleController@update');
    $router->delete('apv_role/{kode_role}','Approval\RoleController@destroy');

    //Master Hakakses
    $router->get('apv_hakakses','Approval\HakaksesController@index');
    $router->get('apv_hakakses/{nik}','Approval\HakaksesController@show');
    $router->post('apv_hakakses','Approval\HakaksesController@store');
    $router->put('apv_hakakses/{nik}','Approval\HakaksesController@update');
    $router->delete('apv_hakakses/{nik}','Approval\HakaksesController@destroy');

    //Justifikasi Kebutuhan
    $router->get('apv_juskeb_aju','Approval\JuskebController@index');
    $router->get('apv_juskeb_aju/{no_juskeb}','Approval\JuskebController@show');
    $router->post('apv_juskeb_aju','Approval\JuskebController@store');
    $router->put('apv_juskeb_aju/{no_juskeb}','Approval\JuskebController@update');
    $router->delete('apv_juskeb_aju/{no_juskeb}','Approval\JuskebController@destroy');

    // Verifikasi
    $router->get('apv_verifikasi','Approval\VerifikasiController@index');
    $router->get('apv_verifikasi/{no_ver}','Approval\VerifikasiController@show');
    $router->post('apv_verifikasi','Approval\VerifikasiController@store');
    $router->put('apv_verifikasi/{no_ver}','Approval\VerifikasiController@update');
    $router->delete('apv_verifikasi/{no_ver}','Approval\VerifikasiController@destroy');

    //Approval Justifikasi Kebutuhan
    $router->get('apv_juskeb_app','Approval\JuskebApprovalController@index');
    $router->get('apv_juskeb_app/{no_app}','Approval\JuskebApprovalController@show');
    $router->post('apv_juskeb_app','Approval\JuskebApprovalController@store');
    $router->put('apv_juskeb_app/{no_app}','Approval\JuskebApprovalController@update');
    $router->delete('apv_juskeb_app/{no_app}','Approval\JuskebApprovalController@destroy');

    //Justifikasi Pengadaan
    $router->get('apv_juspo_aju','Approval\JuspoController@index');
    $router->get('apv_juspo_aju/{no_juspo}','Approval\JuspoController@show');
    $router->post('apv_juspo_aju','Approval\JuspoController@store');
    $router->put('apv_juspo_aju/{no_juspo}','Approval\JuspoController@update');
    $router->delete('apv_juspo_aju/{no_juspo}','Approval\JuspoController@destroy');

    //Approval Justifikasi Pengadaan
    $router->get('apv_juspo_app','Approval\JuspoApprovalController@index');
    $router->get('apv_juspo_app/{no_app}','Approval\JuspoApprovalController@show');
    $router->post('apv_juspo_app','Approval\JuspoApprovalController@store');
    $router->put('apv_juspo_app/{no_app}','Approval\JuspoApprovalController@update');
    $router->delete('apv_juspo_app/{no_app}','Approval\JuspoApprovalController@destroy');
});