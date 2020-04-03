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

$router->group(['middleware' => 'cors','prefix' => 'api'], function () use ($router) {
    // $router->post('register', 'AuthController@register');
     // Matches "/api/login
    $router->post('login', 'AuthController@login');
    $router->post('loginAdmin', 'AuthController@loginAdmin');
    $router->get('hashPass', 'AuthController@hashPassword');
    $router->get('hashPassAdmin', 'AuthController@hashPasswordAdmin');
    $router->get('db1', function () {
        
        $sql = DB::connection('sqlsrv')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
    $router->get('db2', function () {
        
        $sql = DB::connection('sqlsrv2')->select("select * from hakakses ");
        $row = json_decode(json_encode($sql),true);
        
        $result = response()->json(['success'=>$row], 200);    
        
        return $result;
        
    });
});


$router->group(['middleware' => ['auth:user','cors'],'prefix' => 'api/approval'], function () use ($router) {
    // Matches "/api/profile
    $router->get('profile', 'UserController@profile');

    // Matches "/api/users/1 
    //get one user by id
    $router->get('users/{id}', 'UserController@singleUser');

    // Matches "/api/users
    $router->get('users', 'UserController@allUsers');
    
    // Matches "/api/cekPayload
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


});

$router->group(['middleware' => ['auth:admin','cors'],'prefix' => 'api/gl'], function () use ($router) {
     // Matches "/api/profile
     $router->get('profile', 'AdminController@profile');

     // Matches "/api/users/1 
     //get one user by id
     $router->get('users/{id}', 'AdminController@singleUser');
 
     // Matches "/api/users
     $router->get('users', 'AdminController@allUsers');
     
     // Matches "/api/cekPayload
     $router->get('cekPayload', 'AdminController@cekPayload');
    
    //Menu
    
    $router->get('menu/{kode_klp}', 'Gl\MenuController@show');

    //FS
    
    $router->get('fs','Gl\FsController@index');
    $router->get('fs/{id}','Gl\FsController@show');
    $router->post('fs','Gl\FsController@store');
    $router->put('fs/{id}','Gl\FsController@update');
    $router->delete('fs/{id}','Gl\FsController@destroy');

    //MASAKUN
    
    $router->get('masakun','Gl\MasakunController@index');
    $router->get('masakun/{id}','Gl\MasakunController@show');
    $router->post('masakun','Gl\MasakunController@store');
    $router->put('masakun/{id}','Gl\MasakunController@update');
    $router->delete('masakun/{id}','Gl\MasakunController@destroy');
    
    $router->get('currency','Gl\MasakunController@getCurrency');
    $router->get('modul','Gl\MasakunController@getModul');
    $router->get('flag_akun','Gl\MasakunController@getFlagAkun');
    $router->get('neraca/{kode_fs}','Gl\MasakunController@getNeraca');
    $router->get('fsgar','Gl\MasakunController@getFSGar');
    $router->get('neracagar/{kode_fs}','Gl\MasakunController@getNeracaGar');
});