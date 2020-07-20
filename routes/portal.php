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

$router->get('storage2/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('rtrw/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->url('rtrw/'.$filename); 
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
    $router->put('blok','Rtrw\BlokController@update');
    $router->delete('blok','Rtrw\BlokController@destroy');

    //Master PP
    $router->get('pp','Rtrw\PpController@index');
    $router->post('pp','Rtrw\PpController@store');
    $router->put('pp','Rtrw\PpController@update');
    $router->delete('pp','Rtrw\PpController@destroy');

    //Master Perlu
    $router->get('perlu','Rtrw\KeperluanController@index');
    $router->post('perlu','Rtrw\KeperluanController@store');
    $router->put('perlu','Rtrw\KeperluanController@update');
    $router->delete('perlu','Rtrw\KeperluanController@destroy');

    //Master Rumah
    $router->get('rumah','Rtrw\RumahController@index');
    $router->post('rumah','Rtrw\RumahController@store');
    $router->put('rumah','Rtrw\RumahController@update');
    $router->delete('rumah','Rtrw\RumahController@destroy');

    //Master Warga
    $router->get('warga','Rtrw\WargaController@show');
    $router->post('warga','Rtrw\WargaController@store');
    $router->post('warga-ubah','Rtrw\WargaController@update');
    $router->delete('warga','Rtrw\WargaController@destroy');

    //Tamu
    $router->get('tamu-masuk','Rtrw\TamuController@index');
    $router->post('tamu-masuk','Rtrw\TamuController@store');
    $router->post('tamu-keluar','Rtrw\TamuController@update');

    //Akses Form
    $router->get('akses-form','Rtrw\AksesFormController@index');
    $router->post('akses-form','Rtrw\AksesFormController@store');

    //Paket Titip
    $router->get('paket','Rtrw\PaketController@index');
    $router->post('paket','Rtrw\PaketController@store');
    $router->put('paket','Rtrw\PaketController@update');

    
    $router->get('satpam-aktif','Rtrw\SatpamController@show');
    $router->post('send-notif-fcm', 'Rtrw\NotifController@sendNotif');
    $router->post('cek-request', 'Rtrw\NotifController@tes');

});

$router->get('qrcode', function () {
    // $image = QrCode::size(300)->generate('Hello Ena!');
    $output_file = 'qr-code-' . uniqid() . '.png';
    // // Storage::disk('s3')->put('rtrw/'.$output_file, $image);
    // // return url("api/portal/storage/").$output_file;
    // return response($image)->header('Content-type','image/png');
    // $image = QrCode::size(250)
    //     ->backgroundColor(255, 255, 204)
    //     ->generate('MyNotePaper');
    $image = QrCode::format('png')
                        //  ->merge('uploads/1586243456_Winter.jpg', 0.5, true)
                         ->size(300)->errorCorrection('H')
                         ->generate('A simple example of QR code!');
    Storage::disk('local')->put('qr-code/'.$output_file, $image);
    return response($image)->header('Content-type','image/png');
});

$router->get('konversi-waktu/{waktu}','Rtrw\TamuController@waktu');
?>