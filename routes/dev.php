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
    $router->post('login', 'AuthController@loginAdmin');
    $router->get('hashPass', 'AuthController@hashPasswordAdmin');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('dev/'.$filename)) {
        abort(404);
    }
    return Storage::disk('s3')->response('dev/'.$filename); 
});


$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    
    $router->get('profile', 'AdminController@profile');
    $router->get('users/{id}', 'AdminController@singleUser');
    $router->get('users', 'AdminController@allUsers');
    $router->get('cekPayload', 'AdminController@cekPayload');
    
    //Kelompok Menu
    $router->get('menu-klp', 'Dev\KelompokMenuController@index');
    $router->post('menu-klp', 'Dev\KelompokMenuController@store');
    $router->put('menu-klp', 'Dev\KelompokMenuController@update');
    $router->delete('menu-klp', 'Dev\KelompokMenuController@destroy');

    //Menu
    $router->get('menu', 'Dev\MenuController@index');
    $router->post('menu', 'Dev\MenuController@store');
    $router->put('menu', 'Dev\MenuController@update');
    $router->delete('menu', 'Dev\MenuController@destroy');
    $router->post('menu-move', 'Dev\MenuController@simpanMove');

    //Form
    $router->get('form', 'Dev\FormController@index');
    $router->post('form', 'Dev\FormController@store');
    $router->put('form', 'Dev\FormController@update');
    $router->delete('form', 'Dev\FormController@destroy');
    
    //Menu
    $router->get('menu/{kode_klp}', 'Gl\MenuController@show');

    $router->post('update-password', 'AdminController@updatePassword');
    $router->post('update-foto', 'AdminController@updateFoto');
    $router->post('update-background', 'AdminController@updateBackground');

    $router->post('notif-pusher', 'Dev\NotifController@sendPusher');
    $router->get('notif-pusher', 'Dev\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Dev\NotifController@updateStatusRead');

    $router->post('search-form', 'AdminController@searchForm');
    $router->get('search-form-list', 'AdminController@searchFormList');

    $router->get('jenis','Dev\JenisController@index');
    $router->post('jenis','Dev\JenisController@store');
    $router->put('jenis','Dev\JenisController@update');
    $router->delete('jenis','Dev\JenisController@destroy');    
    
    $router->get('jurusan','Dev\JurusanController@index');
    $router->post('jurusan','Dev\JurusanController@store');
    $router->put('jurusan','Dev\JurusanController@update');
    $router->delete('jurusan','Dev\JurusanController@destroy');    
    
    $router->get('siswa','Dev\SiswaController@index');
    $router->post('siswa','Dev\SiswaController@store');
    $router->put('siswa','Dev\SiswaController@update');
    $router->delete('siswa','Dev\SiswaController@destroy');   

    $router->get('tagihan','Dev\TagihanController@index');
    $router->get('tagihan-detail','Dev\TagihanController@show');
    $router->post('tagihan','Dev\TagihanController@store');
    $router->put('tagihan','Dev\TagihanController@update');
    $router->delete('tagihan','Dev\TagihanController@destroy');
    $router->get('tagihan-load','Dev\TagihanController@load');
    
    $router->get('bayar','Dev\BayarController@index');
    $router->get('bayar-detail','Dev\BayarController@show');
    $router->post('bayar','Dev\BayarController@store');
    $router->put('bayar','Dev\BayarController@update');
    $router->delete('bayar','Dev\BayarController@destroy');    

    $router->get('filter-lokasi','Dev\FilterController@getFilterLokasi');
    $router->get('filter-periode','Dev\FilterController@getFilterPeriode');
    $router->get('filter-nim','Dev\FilterController@getFilterNIM');
    $router->get('filter-jurusan','Dev\FilterController@getFilterJurusan');
    $router->get('filter-tagihan','Dev\FilterController@getFilterTagihan');
    $router->get('filter-bayar','Dev\FilterController@getFilterBayar');

    $router->get('lap-siswa','Dev\LaporanController@getLapSiswa');
    $router->get('lap-tagihan','Dev\LaporanController@getLapTagihan');
    $router->get('lap-saldo-tagihan','Dev\LaporanController@getLapSaldo');
    $router->get('lap-bayar','Dev\LaporanController@getLapBayar');
});



?>