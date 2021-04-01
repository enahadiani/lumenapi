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
    $router->post('login', 'AuthController@loginTs');
    $router->get('hash-pass', 'AuthController@hashPasswordTs');
    $router->get('hash-pass-costum-top/{db}/{table}/{kode_pp}/{top}', 'AuthController@hashPasswordCostumTop');
    $router->get('hash-pass-costum/{db}/{table}/{kode_pp}', 'AuthController@hashPasswordCostum');
    $router->get('hash-pass-nik/{db}/{table}/{nik}', 'AuthController@hashPasswordByNIK');

});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('ts/'.$filename)) {
        $success['message'] = 'Dokumen tidak tersedia!';
        $success['status'] = false;
    }
    return Storage::disk('s3')->response('ts/'.$filename); 
});


$router->group(['middleware' => 'auth:ts'], function () use ($router) {

    $router->get('profile', 'AdminTsController@profile');
    $router->get('users/{id}', 'AdminTsController@singleUser');
    $router->get('users', 'AdminTsController@allUsers');
    $router->get('cek-payload', 'AdminTsController@cekPayload');

    $router->get('menu/{kode_klp}', 'Ts\MenuController@show');
    
    $router->post('update-password', 'AdminTsController@updatePassword');
    $router->post('update-foto', 'AdminTsController@updatePhoto');
    $router->post('update-background', 'AdminTsController@updateBackground');
    
    $router->post('notif-pusher', 'Ts\NotifController@sendPusher');
    $router->get('notif-pusher', 'Ts\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Ts\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminTsController@searchForm');
    $router->get('search-form-list', 'AdminTsController@searchFormList');

    //Tahun Ajaran
    $router->get('pp','Ts\TahunAjaranController@getPP');
    $router->get('tahun-ajaran-all','Ts\TahunAjaranController@index');
    
    $router->get('kartu-piutang','Ts\DashSiswaController@getKartuPiutang');
    $router->get('kartu-piutang-detail','Ts\DashSiswaController@getKartuPiutangDetail');
    $router->get('kartu-pdd','Ts\DashSiswaController@getKartuPDD');
    $router->get('kartu-pdd-detail','Ts\DashSiswaController@getKartuPDDDetail');
    $router->get('dash-siswa-profile','Ts\DashSiswaController@getProfile');    
    $router->post('send-email','EmailController@send');

    $router->get('rincian-piutang','Ts\DashSiswaController@getRincianTagihan');
    $router->get('riwayat-trans','Ts\DashSiswaController@getRiwayatTransaksi');
    $router->get('tahun-ajaran','Ts\DashSiswaController@getTA');

    $router->get('periode','Ts\DashSiswaController@getPeriode');
    $router->get('detail-trans','Ts\DashSiswaController@getDetailTransaksi');
    $router->get('notif-mobile','Ts\PesanController@getNotif');
    
    $router->post('generate-priority','Ts\DashSiswaController@generatePriority');

    $router->get('notif-billing-periode','Ts\NotifBillingController@getPeriode');
    $router->get('notif-billing-nobill','Ts\NotifBillingController@getNoBill');
    $router->get('notif-billing','Ts\NotifBillingController@index');
    $router->post('notif-billing','Ts\NotifBillingController@store');
    $router->delete('notif-billing','Ts\NotifBillingController@destroy');

    $router->get('notif-pembayaran-periode','Ts\NotifPembayaranController@getPeriode');
    $router->get('notif-pembayaran-norekon','Ts\NotifPembayaranController@getNoRekon');
    $router->get('notif-pembayaran','Ts\NotifPembayaranController@index');
    $router->post('notif-pembayaran','Ts\NotifPembayaranController@store');
    $router->delete('notif-pembayaran','Ts\NotifPembayaranController@destroy');

    $router->get('notif-umum-siswa','Ts\PesanController@getSiswa');
    $router->get('notif-umum-kelas','Ts\PesanController@getKelas');
    $router->get('notif-umum-pp','Ts\PesanController@getPP');
    $router->get('notif-umum','Ts\PesanController@index');
    $router->get('notif-umum-detail','Ts\PesanController@show');
    $router->post('notif-umum','Ts\PesanController@store');
    $router->post('notif-umum-ubah','Ts\PesanController@update');
    $router->delete('notif-umum','Ts\PesanController@destroy');
    $router->delete('notif-umum-dok','Ts\PesanController@deleteDokumen');

    $router->put('update-status-read', 'Ts\PesanController@updateStatusReadMobile');
    
    
});