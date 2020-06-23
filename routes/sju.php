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
    
    $router->post('login', 'AuthController@loginSju');
    $router->get('hash_pass', 'AuthController@hashPasswordSju');
    $router->get('hash_pass_nik/{db}/{nik}', 'AuthController@hashPasswordByNIK');
    $router->get('db', function () {
        
        $sql = DB::connection('sqlsrvsjusju')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
});


$router->group(['middleware' => 'auth:sju'], function () use ($router) {

    $router->get('profile', 'AdminSjuController@profile');
    $router->get('users/{id}', 'AdminSjuController@singleUser');
    $router->get('users', 'AdminSjuController@allUsers');
    $router->get('cekPayload', 'AdminSjuController@cekPayload');

    $router->get('aju', function (Request $request) {
        if($data =  Auth::guard('sju')->user()){
            $nik= $data->nik;
            //Pengajuan
            $sql = DB::connection('sqlsrvsju')->select("select menu_mobile from hakakses where nik='$nik' ");
            $row = json_decode(json_encode($sql),true);
            switch($row[0]["menu_mobile"]){
                case 'APPSM' :
                    $result = app('App\Http\Controllers\Sju\ApprovalController')->pengajuan($request);
                break;
                case 'APPFIN' :
                    $result = app('App\Http\Controllers\Sju\ApprovalController')->pengajuanfinal($request);
                break;
                case 'APPDIR' :
                    $result = app('App\Http\Controllers\Sju\ApprovalController')->pengajuandir($request);
                break;
                case 'APPKUG' :
                    $result = app('App\Http\Controllers\Sju\ApprovalController')->pengajuankug($request);
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


    $router->get('ajudet/{no_aju}', 'Sju\ApprovalController@detail');
    $router->get('ajurek/{no_aju}', 'Sju\ApprovalController@rekening');
    $router->get('ajujurnal/{no_aju}', 'Sju\ApprovalController@jurnal');
    $router->get('aju_history/{jenis}', 'Sju\ApprovalController@ajuHistory');
    $router->get('ajudet_history/{no_aju}', 'Sju\ApprovalController@ajuDetailHistory');
    $router->get('ajudet_dok/{no_aju}', 'Sju\ApprovalController@ajuDetailDok');
    $router->get('ajudet_approval/{no_aju}', 'Sju\ApprovalController@ajuDetailApproval');

    $router->post('app', function (Request $request) {
        if($data =  Auth::guard('sju')->user()){
            $nik= $data->nik;
            //Pengajuan
            $sql = DB::connection('sqlsrvsju')->select("select menu_mobile from hakakses where nik='$nik' ");
            $row = json_decode(json_encode($sql),true);
            switch($row[0]["menu_mobile"]){
                case 'APPSM' :
                    $result = app('App\Http\Controllers\Sju\ApprovalController')->approvalSM($request);
                break;
                case 'APPFIN' :
                    $result = app('App\Http\Controllers\Sju\ApprovalController')->approvalFinal($request);
                break;
                case 'APPKUG' :
                    $result = app('App\Http\Controllers\Sju\ApprovalController')->approvalKug($request);
                break;
                case 'APPDIR' :
                    $result = app('App\Http\Controllers\Sju\ApprovalController')->approvalDir($request);
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
    $router->get('periode_aju', 'Sju\ApprovalController@getPeriodeAju');
    
});