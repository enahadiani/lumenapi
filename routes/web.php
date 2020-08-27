<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use Milon\Barcode\Facades\DNS1DFacade as DNS1D;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;

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

    $router->post('mail', 'MailController@send');
    $router->get('tes/{nik}','Gl\PostingController@tes');
});


$router->get('users/export', 'UserController@export');
$router->get('users/exportpdf', 'UserController@exportpdf');

$router->get('routes', ['middleware' => 'cors', function() use ($router) {
    $data = $router->getRoutes();
    return view('routes', ['routes' => $data, 'modul'=>'all']);
}]);

$router->get('routes/{modul}', ['middleware' => 'cors', function($modul) use ($router) {
    $data = $router->getRoutes();
    return view('routes', ['routes' => $data, 'modul'=>$modul]);
}]);

$router->get('auth/facebook/login', 'LoginSocialiteController@redirectToProvider');
$router->get('auth/facebook/callback', 'LoginSocialiteController@handleProviderCallback');

$router->post('send_notif_fcm', 'NotifController@sendNotif');


$router->get('pusher/{title}/{message}/{id}', function ($title,$message,$id) {
    event(new \App\Events\NotifApv($title,$message,$id));

    return "Event has been sent!";
});

$router->post('import-csv', function (Request $request) {
   
    if($request->hasfile('file')){
        $file = $request->file('file');
        
        $nama_foto = uniqid()."_".$file->getClientOriginalName();
        // $picName = uniqid() . '_' . $picName;
        $foto = $nama_foto;
        Storage::disk('local')->put($foto,file_get_contents($file));
        $file = fopen(Storage::disk('local')->path($foto), "r");
        $all_data = array();
        $header_data = array();
        $column = array('trans_date','value_date','ref_no','cek_no','deskription','debit','kredit','balance');
        $i=0;
        while ( ($data = fgetcsv($file, 1000, ",")) !==FALSE )
        {
            if($i == 0){
                $header_data["periode"] = $data[1];
                $header_data["release_date"] = $data[7];
            }
            else if($i == 1){
                $header_data["account_no"] = $data[1];
                $header_data["opening_balance"] = $data[7];
            }
            if($i == 2){
                $header_data["account_name"] = $data[1];
                $header_data["closing_balance"] = $data[7];
            }
            if($i == 3){
                $header_data["debit"] = $data[1];
                $header_data["total_debit"] = $data[2];
                $header_data["legder_balance"] = $data[7];
            }
            if($i == 4){
                $header_data["credit"] = $data[1];
                $header_data["total_credit"] = $data[2];
                $header_data["available_balance"] = $data[7];
            }
            else if($i >= 10){
                $row_data = array();

                for($a=0;$a<8;$a++){
                    
                    if(isset($data[$a])){
                        if($a == 2){
                            $row_data[$column[$a]] = str_replace("'","",$data[$a]);
                        }else if($a == 4){
                            $row_data[$column[$a]] = trim($data[$a]);
                        }
                        else{
                            $row_data[$column[$a]] = $data[$a];
                        }
                    }
                }
                $all_data[] = $row_data;
            }
            $i++;
        }
        fclose($file);
        Storage::disk('local')->delete($foto);
        $success['header_data'] = $header_data;
        $success['trans_data'] = $all_data;
        $success['status'] = true;
        return response()->json($success, 200);
    }else{
        
        $success['data'] = [];
        $success['status'] = false;
        return response()->json($success, 200);
    }

});



