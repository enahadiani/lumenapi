<?php
namespace App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use Log;


$router->get('/', function () use ($router) {
    return $router->app->version();
});

//Handle error cors to options method
$router->options('{all:.*}', ['middleware' => 'cors', function() {
    return response('');
}]);


$router->group(['middleware' => 'cors'], function () use ($router) {
    //approval dev
    $router->post('login', 'AuthController@loginAdminSilo');
    $router->get('hash-pass', 'AuthController@hashPasswordAdminSilo');
    $router->get('hash-by-nik/{db}/{table}/{nik}','AuthController@hashPasswordByNIK');
    $router->get('log_tes',function(){
        Log::error('Showing user: ');
    });
    $router->get('cek', function (Request $request) {
        if (password_verify($request->pass, $request->hash)) {
            echo 'Password is valid!';
        } else {
            echo 'Invalid password.';
        }
        // if(app('hash')->check($request->pass,$request->hash)){
        //     echo 'Password is valid!';
        // } else {
        //     echo 'Invalid password.';
        // }
    });

});

$router->get('storage/{filename}', function ($filename)
{
    if (!Storage::disk('s3')->exists('apv/'.$filename)) {
        $success['message'] = 'Dokumen tidak tersedia!';
        $success['status'] = false;
    }
    return Storage::disk('s3')->response('apv/'.$filename); 
});


$router->group(['middleware' => 'auth:silo'], function () use ($router) {
    
    $router->get('profile', 'AdminSiloController@profile2');
    $router->get('users/{id}', 'AdminSiloController@singleUser');
    $router->get('users', 'AdminSiloController@allUsers');
    $router->get('cek-payload', 'AdminSiloController@cekPayload');
    
    $router->post('update-password', 'AdminSiloController@updatePassword');
    
    //Menu
    $router->get('side-menu/{kode_klp}', 'Apv\HakaksesController@getSideMenu');

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

    //Master Kota
    $router->get('kota_all','Apv\KotaController@index');
    $router->get('kota/{kode_kota}','Apv\KotaController@show');
    $router->post('kota','Apv\KotaController@store');
    $router->put('kota/{kode_kota}','Apv\KotaController@update');
    $router->delete('kota/{kode_kota}','Apv\KotaController@destroy');
    $router->get('kota-aju','Apv\KotaController@getKotaByNIK');

    //Master Divisi
    $router->get('divisi_all','Apv\DivisiController@index');
    $router->get('divisi/{kode_divisi}','Apv\DivisiController@show');
    $router->post('divisi','Apv\DivisiController@store');
    $router->put('divisi/{kode_divisi}','Apv\DivisiController@update');
    $router->delete('divisi/{kode_divisi}','Apv\DivisiController@destroy');
    $router->get('divisi-aju','Apv\DivisiController@getDivisiByNIK');

    //Justifikasi Kebutuhan
    $router->get('juskeb','Apv\JuskebController@index');
    $router->get('juskeb-finish','Apv\JuskebController@getJuskebFinish');
    $router->get('juskeb/{no_bukti}','Apv\JuskebController@show');
    $router->get('kota','Apv\JuskebController@getKota');
    $router->get('divisi','Apv\JuskebController@getDivisi');
    $router->get('nik_verifikasi','Apv\JuskebController@getNIKVerifikasi');
    $router->get('nik_verifikasi2','Apv\JuskebController@getNIKVerifikasi2');
    $router->get('barang-klp','Apv\JuskebController@getBarangKlp');
    $router->get('generate-dok','Apv\JuskebController@generateDok');
    $router->post('juskeb','Apv\JuskebController@store');
    $router->post('juskeb/{no_bukti}','Apv\JuskebController@update');
    $router->delete('juskeb/{no_bukti}','Apv\JuskebController@destroy');
    $router->get('juskeb_history/{no_bukti}','Apv\JuskebController@getHistory');
    $router->get('juskeb_preview/{no_bukti}','Apv\JuskebController@getPreview');
    $router->get('juskeb_preview2/{no_bukti}','Apv\JuskebController@getPreview2');

    // Verifikasi
    $router->get('verifikasi','Apv\VerifikasiController@index');
    $router->get('verifikasi/{no_aju}','Apv\VerifikasiController@show');
    $router->post('verifikasi','Apv\VerifikasiController@store');
    $router->get('verifikasi_status','Apv\VerifikasiController@getStatus');
    $router->get('verifikasi_history','Apv\VerifikasiController@getHistory');
    $router->get('verifikasi_preview/{no_bukti}','Apv\VerifikasiController@getPreview');

    //Approval Justifikasi Kebutuhan
    $router->get('juskeb_app','Apv\JuskebApprovalController@index');
    $router->get('juskeb_aju','Apv\JuskebApprovalController@getPengajuan');
    $router->get('juskeb_app/{no_aju}','Apv\JuskebApprovalController@show');
    $router->post('juskeb_app','Apv\JuskebApprovalController@store');
    $router->get('juskeb_app_status','Apv\JuskebApprovalController@getStatus');
    $router->get('juskeb_app_preview/{no_bukti}/{id}','Apv\JuskebApprovalController@getPreview');
    
    //Justifikasi Pengadaan
    $router->get('juspo','Apv\JuspoController@index');
    $router->get('juspo_aju','Apv\JuspoController@getPengajuan');
    $router->get('juspo/{no_bukti}','Apv\JuspoController@show');
    $router->get('juspo_aju/{no_bukti}','Apv\JuspoController@getDetailJuskeb');
    $router->post('juspo','Apv\JuspoController@store');
    $router->post('juspo/{no_bukti}','Apv\JuspoController@update');
    $router->delete('juspo/{no_bukti}','Apv\JuspoController@destroy');
    $router->get('juspo_history/{no_bukti}','Apv\JuspoController@getHistory');
    $router->get('juspo_preview/{no_bukti}','Apv\JuspoController@getPreview');
    $router->get('generate-dok-juspo','Apv\JuspoController@generateDok');

    //Approval Justifikasi Pengadaan
    $router->get('juspo_app','Apv\JuspoApprovalController@index');
    $router->get('juspo_app_aju','Apv\JuspoApprovalController@getPengajuan');
    $router->get('juspo_app_status','Apv\JuspoApprovalController@getStatus');
    $router->get('juspo_app/{no_aju}','Apv\JuspoApprovalController@show');
    $router->post('juspo_app','Apv\JuspoApprovalController@store');
    $router->put('juspo_app/{no_aju}','Apv\JuspoApprovalController@update');
    $router->delete('juspo_app/{no_aju}','Apv\JuspoApprovalController@destroy');
    $router->get('juspo_app_preview/{no_bukti}/{id}','Apv\JuspoApprovalController@getPreview');

    //Dashboard
    $router->get('dash_databox','Apv\DashboardController@getDataBox');
    $router->get('dash_posisi','Apv\DashboardController@getPosisi');
    
    $router->get('dash_cek','Apv\DashboardController@cek');
    $router->post('notif_register','Apv\NotifikasiController@register');
    $router->post('notif_send','Apv\NotifikasiController@sendNotif');

    //Filter Laporan
    $router->get('filter-pp','Apv\FilterController@getFilterPP');
    $router->get('filter-kota','Apv\FilterController@getFilterKota');
    $router->get('filter-nobukti','Apv\FilterController@getFilterNoBukti');
    $router->get('filter-nodokumen','Apv\FilterController@getFilterNoDokumen');

    //Pihak ketiga

    //Laporan
    $router->get('lap-posisi','Apv\LaporanController@getPosisi');
    $router->get('lap-catt-app','Apv\LaporanController@getCattApp');
    $router->post('notif', 'Apv\NotifController@sendNotif');
    $router->get('notif', 'Apv\NotifController@getNotif');

    
    
    // $router->post('notif_send','Apv\NotifikasiController@sendNotif');
    
    $router->post('notif-pusher', 'Apv\NotifController@sendPusher');
    $router->get('notif-pusher', 'Apv\NotifController@getNotifPusher');
    $router->put('notif-update-status', 'Apv\NotifController@updateStatusRead');
});




?>