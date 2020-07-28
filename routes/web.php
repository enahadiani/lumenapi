<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('local')->exists($filename)) {
        abort(404);
    }
    return Storage::disk('local')->response($filename); 
});

$router->get('barcode',function(){
    // echo DNS1D::getBarcodeHTML('4445645656', 'C39');
    Storage::disk('local')->put('test2.png',base64_decode(DNS1D::getBarcodePNG('04-PJL2007.00002', 'C39')));

    echo "<img src=''></img>";

});


