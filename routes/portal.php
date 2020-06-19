<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use SimpleSoftwareIO\QrCode\Facade as QrCode;


$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);


$router->group(['middleware' => 'cors'], function () use ($router) {
    //approval dev
    $router->post('login', 'AuthController@loginSatpam');
    $router->get('hash_pass_table/{db}/{table}', 'AuthController@hashPassTable');
});


$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('rtrw/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('rtrw/'.$filename); 
});

$router->group(['middleware' => 'auth:satpam'], function () use ($router) {
    $router->get('logout', 'AuthController@logoutSatpam');
    $router->get('profile', 'AdminSatpamController@profile');
    $router->get('users/{id}', 'AdminSatpamController@singleUser');
    $router->get('users', 'AdminSatpamController@allUsers');
    $router->get('cek-payload', 'AdminSatpamController@cekPayload');

    //Master Satpam
    $router->get('satpam','Rtrw\SatpamController@index');
    $router->post('satpam','Rtrw\SatpamController@store');
    $router->post('satpam-ubah','Rtrw\SatpamController@update');
    $router->delete('satpam','Rtrw\SatpamController@destroy');
    $router->post('satpam-generate-qrcode','Rtrw\SatpamController@generateQrCode');

    //Master Blok
    $router->get('blok','Rtrw\BlokController@index');
    $router->post('blok','Rtrw\BlokController@store');
    $router->post('blok-ubah','Rtrw\BlokController@update');
    $router->delete('blok','Rtrw\BlokController@destroy');

});

// $router->get('qrcode', function () {
//     $image = QrCode::size(300)->generate('Hello Ena!');
//     $output_file = 'qr-code-' . uniqid() . '.png';
//     Storage::disk('s3')->put('rtrw/'.$output_file, $image);
//     return url("api/portal/storage/").$output_file;
// });


?>