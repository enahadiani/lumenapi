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
    
    // $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->get('hashPass', 'AuthController@hashPassword');
    $router->get('db1', function () {
        
        $sql = DB::connection('sqlsrv')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
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

    $router->get('ajuhistory', 'Approval\ApprovalController@pengajuanhistory');

    $router->get('ajudet/{no_aju}', 'Approval\ApprovalController@detail');
    $router->get('ajurek/{no_aju}', 'Approval\ApprovalController@rekening');
    $router->get('ajujurnal/{no_aju}', 'Approval\ApprovalController@jurnal');

    //Approval 

    // $router->post('appsm', 'Approval\ApprovalController@approvalSM');
    // $router->post('appfin', 'Approval\ApprovalController@approvalFinal');
    // $router->post('appdir', 'Approval\ApprovalController@approvalDir');

    $router->post('app', function () {
        if($data =  Auth::guard('user')->user()){
            $nik= $data->nik;
            //Pengajuan
            $sql = DB::connection('sqlsrv')->select("select menu_mobile from hakakses where nik='$nik' ");
            $row = json_decode(json_encode($sql),true);
            switch($row[0]["menu_mobile"]){
                case 'APPSM' :
                    $result = app('App\Http\Controllers\Approval\ApprovalController')->approvalSM();
                break;
                case 'APPFIN' :
                    $result = app('App\Http\Controllers\Approval\ApprovalController')->approvalFinal();
                break;
                case 'APPDIR' :
                    $result = app('App\Http\Controllers\Approval\ApprovalController')->approvalDir();
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