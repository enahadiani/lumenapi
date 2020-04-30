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
    //approval dev
    $router->post('login', 'AuthController@loginAdmin');
    $router->get('hash_pass', 'AuthController@hashPasswordAdmin');
});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('local')->exists($filename)) {
        abort(404);
    }
    return Storage::disk('local')->response($filename); 
});


$router->group(['middleware' => 'auth:admin'], function () use ($router) {
    
    $router->get('profile', 'AdminController@profile2');
    $router->get('users/{id}', 'AdminController@singleUser');
    $router->get('users', 'AdminController@allUsers');
    $router->get('cek_payload', 'AdminController@cekPayload');

    //Master Karyawan
    $router->get('karyawan','Apv\KaryawanController@index');
    $router->get('karyawan/{nik}','Apv\KaryawanController@show');
    $router->post('karyawan','Apv\KaryawanController@store');
    $router->post('karyawan/{nik}','Apv\KaryawanController@update');
    $router->delete('karyawan/{nik}','Apv\KaryawanController@destroy');

    //Master Jabatan
    $router->get('jabatan','Apv\JabatanController@index');
    $router->get('jabatan/{kode_jab}','Apv\JabatanController@show');
    $router->post('jabatan','Apv\JabatanController@store');
    $router->put('jabatan/{kode_jab}','Apv\JabatanController@update');
    $router->delete('jabatan/{kode_jab}','Apv\JabatanController@destroy');

    //Master Unit
    $router->get('unit','Apv\UnitController@index');
    $router->get('unit/{kode_pp}','Apv\UnitController@show');
    $router->post('unit','Apv\UnitController@store');
    $router->put('unit/{kode_pp}','Apv\UnitController@update');
    $router->delete('unit/{kode_pp}','Apv\UnitController@destroy');

    //Master Role
    $router->get('role','Apv\RoleController@index');
    $router->get('role/{kode_role}','Apv\RoleController@show');
    $router->post('role','Apv\RoleController@store');
    $router->put('role/{kode_role}','Apv\RoleController@update');
    $router->delete('role/{kode_role}','Apv\RoleController@destroy');

    //Master Hakakses
    $router->get('hakakses','Apv\HakaksesController@index');
    $router->get('hakakses/{nik}','Apv\HakaksesController@show');
    $router->post('hakakses','Apv\HakaksesController@store');
    $router->put('hakakses/{nik}','Apv\HakaksesController@update');
    $router->delete('hakakses/{nik}','Apv\HakaksesController@destroy');
    $router->get('form','Apv\HakaksesController@getForm');
    $router->get('menu','Apv\HakaksesController@getMenu');

    //Justifikasi Kebutuhan
    $router->get('juskeb','Apv\JuskebController@index');
    $router->get('juskeb/{no_bukti}','Apv\JuskebController@show');
    $router->post('juskeb','Apv\JuskebController@store');
    $router->post('juskeb/{no_bukti}','Apv\JuskebController@update');
    $router->delete('juskeb/{no_bukti}','Apv\JuskebController@destroy');
    $router->get('juskeb_history/{no_bukti}','Apv\JuskebController@getHistory');
    $router->get('juskeb_preview/{no_bukti}','Apv\JuskebController@getPreview');

    // Verifikasi
    $router->get('verifikasi','Apv\VerifikasiController@index');
    $router->get('verifikasi/{no_aju}','Apv\VerifikasiController@show');
    $router->post('verifikasi','Apv\VerifikasiController@store');
    $router->get('verifikasi_status','Apv\VerifikasiController@getStatus');

    //Approval Justifikasi Kebutuhan
    $router->get('juskeb_app','Apv\JuskebApprovalController@index');
    $router->get('juskeb_aju','Apv\JUskebApprovalController@getPengajuan');
    $router->get('juskeb_app/{no_app}','Apv\JuskebApprovalController@show');
    $router->post('juskeb_app','Apv\JuskebApprovalController@store');
    $router->get('juskeb_app_status','Apv\JuskebApprovalController@getStatus');

    //Justifikasi Pengadaan
    $router->get('juspo','Apv\JuspoController@index');
    $router->get('juspo/{no_juspo}','Apv\JuspoController@show');
    $router->post('juspo','Apv\JuspoController@store');
    $router->put('juspo/{no_juspo}','Apv\JuspoController@update');
    $router->delete('juspo/{no_juspo}','Apv\JuspoController@destroy');

    //Approval Justifikasi Pengadaan
    $router->get('juspo_app','Apv\JuspoApprovalController@index');
    $router->get('juspo_app/{no_app}','Apv\JuspoApprovalController@show');
    $router->post('juspo_app','Apv\JuspoApprovalController@store');
    $router->put('juspo_app/{no_app}','Apv\JuspoApprovalController@update');
    $router->delete('juspo_app/{no_app}','Apv\JuspoApprovalController@destroy');
});

?>