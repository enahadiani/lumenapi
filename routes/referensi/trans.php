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


$router->group(['middleware' => 'auth:adminoci'], function () use ($router) {
    $router->get('tagihan','Rkap\TagihanController@index');
    $router->get('tagihan-detail','Rkap\TagihanController@show');
    $router->post('tagihan','Rkap\TagihanController@store');
    $router->put('tagihan','Rkap\TagihanController@update');
    $router->delete('tagihan','Rkap\TagihanController@destroy');
    $router->get('tagihan-load','Rkap\TagihanController@load');
    $router->post('tagihan-import','Rkap\TagihanController@importExcel');
    $router->get('tagihan-tmp','Rkap\TagihanController@getDataTmp');
    
    $router->get('pengeluaran','Rkap\PengeluaranController@index');
    $router->get('pengeluaran-detail','Rkap\PengeluaranController@show');
    $router->post('pengeluaran','Rkap\PengeluaranController@store');
    $router->put('pengeluaran','Rkap\PengeluaranController@update');
    $router->delete('pengeluaran','Rkap\PengeluaranController@destroy');
    $router->get('pengeluaran-load','Rkap\PengeluaranController@load');
    $router->post('pengeluaran-import','Rkap\PengeluaranController@importExcel');
    $router->get('pengeluaran-tmp','Rkap\PengeluaranController@getDataTmp');

    //Usulan Anggaran Umum
    $router->get('u-anggaran-umum','Rkap\UAnggaranUmumController@index');//ok
    $router->get('u-anggaran-umum-detail','Rkap\UAnggaranUmumController@show');//ok
    $router->post('u-anggaran-umum','Rkap\UAnggaranUmumController@store'); //ok
    $router->put('u-anggaran-umum','Rkap\UAnggaranUmumController@update'); //ok
    $router->delete('u-anggaran-umum','Rkap\UAnggaranUmumController@destroy'); //ok
    $router->get('u-anggaran-umum-load','Rkap\UAnggaranUmumController@load');
    $router->post('u-anggaran-umum-import','Rkap\UAnggaranUmumController@importExcel');
    $router->get('u-anggaran-umum-tmp','Rkap\UAnggaranUmumController@getDataTmp');
    $router->get('get-akun','Rkap\UAnggaranUmumController@get_akun'); //sementara sampai beres master akun
    $router->get('get-akun-detail','Rkap\UAnggaranUmumController@get_akun_detail'); //sementara sampai beres master akun

    // Pengajuan
    $router->get('aju','Rkap\PengajuanController@index');
    $router->get('aju-box','Rkap\PengajuanController@getDataBox');
    $router->get('aju-perbaikan','Rkap\PengajuanController@getDataPerbaikan');
    $router->get('aju-finish','Rkap\PengajuanController@getFinish');
    $router->get('aju-detail','Rkap\PengajuanController@show');
    $router->get('aju-dam','Rkap\PengajuanController@getDAM');
    $router->get('aju-rkm','Rkap\PengajuanController@getRKM');
    $router->get('aju-preview','Rkap\PengajuanController@getPreview');
    $router->get('aju-history','Rkap\PengajuanController@getHistory');
    $router->get('aju-preview-his','Rkap\PengajuanController@getPreviewHis');
    $router->get('aju-history-his','Rkap\PengajuanController@getHistoryHis');
    $router->post('aju','Rkap\PengajuanController@store');
    $router->post('aju-edit','Rkap\PengajuanController@update');
    $router->delete('aju','Rkap\PengajuanController@destroy');
    $router->get('cek-akses-form','Rkap\PengajuanController@cekAksesForm');
    $router->post('aju-notifikasi','Rkap\PengajuanController@sendNotifikasi'); 
    $router->get('aju-draft','Rkap\PengajuanController@AjuDraft');
    $router->get('aju-sedang','Rkap\PengajuanController@AjuSedang');
    $router->get('aju-selesai','Rkap\PengajuanController@AjuSelesai');
    
    //Approval 
    $router->get('app','Rkap\ApprovalController@index');
    $router->get('app-aju','Rkap\ApprovalController@getPengajuan');
    $router->get('app-detail','Rkap\ApprovalController@show');
    $router->post('app','Rkap\ApprovalController@store');
    $router->get('app-status','Rkap\ApprovalController@getStatus');
    $router->get('app-preview','Rkap\ApprovalController@getPreview');

    // Pengajuan DAM
    $router->get('ajudam','Rkap\PengajuanDamController@index');
    $router->get('ajudam-box','Rkap\PengajuanDamController@getDataBox');
    $router->get('ajudam-perbaikan','Rkap\PengajuanDamController@getDataPerbaikan');
    $router->get('ajudam-finish','Rkap\PengajuanDamController@getFinish');
    $router->get('ajudam-detail','Rkap\PengajuanDamController@show');
    $router->get('ajudam-si','Rkap\PengajuanDamController@getSI');
    $router->get('ajudam-dam','Rkap\PengajuanDamController@getDAM');
    $router->get('ajudam-preview','Rkap\PengajuanDamController@getPreview');
    $router->get('ajudam-history','Rkap\PengajuanDamController@getHistory');
    $router->get('ajudam-preview-his','Rkap\PengajuanDamController@getPreviewHis');
    $router->get('ajudam-history-his','Rkap\PengajuanDamController@getHistoryHis');
    $router->post('ajudam','Rkap\PengajuanDamController@store');
    $router->post('ajudam-edit','Rkap\PengajuanDamController@update');
    $router->delete('ajudam','Rkap\PengajuanDamController@destroy');
    $router->get('ajudam-draft','Rkap\PengajuanDamController@AjuDraft');
    $router->get('ajudam-sedang','Rkap\PengajuanDamController@AjuSedang');
    $router->get('ajudam-selesai','Rkap\PengajuanDamController@AjuSelesai');

    //Approval DAM
    $router->get('appdam','Rkap\ApprovalDamController@index');
    $router->get('appdam-aju','Rkap\ApprovalDamController@getPengajuan');
    $router->get('appdam-detail','Rkap\ApprovalDamController@show');
    $router->post('appdam','Rkap\ApprovalDamController@store');
    $router->get('appdam-status','Rkap\ApprovalDamController@getStatus');
    $router->get('appdam-preview','Rkap\ApprovalDamController@getPreview');

    // Pengajuan Outlook
    $router->get('aju-outlook','Rkap\PengajuanOutlookController@index');//pengajuan
    $router->get('aju-finish-outlook','Rkap\PengajuanOutlookController@getFinish'); //fHistoryAjuOutlook
    $router->get('aju-detail-outlook','Rkap\PengajuanOutlookController@show');//pengajuan
    $router->get('aju-dam-outlook','Rkap\PengajuanOutlookController@getDAM');//pengajuan
    $router->get('aju-rkm-outlook','Rkap\PengajuanOutlookController@getRKM');
    $router->get('aju-preview-outlook','Rkap\PengajuanOutlookController@getPreview');//pengajuan
    $router->get('aju-history-outlook','Rkap\PengajuanOutlookController@getHistory');//pengajuan

    $router->get('aju-preview-his-outlook','Rkap\PengajuanOutlookController@getPreviewHis');//fHistoryAjuOutlook
    $router->get('aju-history-his-outlook','Rkap\PengajuanOutlookController@getHistoryHis');//fHistoryAjuOutlook

    $router->post('aju-outlook','Rkap\PengajuanOutlookController@store');//pengajuan
    $router->post('aju-edit-outlook','Rkap\PengajuanOutlookController@update');//pengajuan
    $router->delete('aju-outlook','Rkap\PengajuanOutlookController@destroy');//pengajuan
    $router->get('cek-akses-form-outlook','Rkap\PengajuanOutlookController@cekAksesForm');//pengajuan
        
    //Approval Outlook
    $router->get('app-outlook','Rkap\ApprovalOutlookController@index');//fHistoryAppOutlook
    $router->get('app-aju-outlook','Rkap\ApprovalOutlookController@getPengajuan');//approval
    $router->get('app-detail-outlook','Rkap\ApprovalOutlookController@show');//approval
    $router->post('app-outlook','Rkap\ApprovalOutlookController@store');//approval
    $router->get('app-status-outlook','Rkap\ApprovalOutlookController@getStatus');
    $router->get('app-preview-outlook','Rkap\ApprovalOutlookController@getPreview');//dikerjakan

    // Pengajuan DAM
    $router->get('ajudrk','Rkap\PengajuanDrkController@index');
    $router->get('ajudrk-finish','Rkap\PengajuanDrkController@getFinish');
    $router->get('ajudrk-detail','Rkap\PengajuanDrkController@show');
    $router->get('ajudrk-rkm','Rkap\PengajuanDrkController@getRkm');
    $router->get('ajudrk-drk','Rkap\PengajuanDrkController@getDrk');
    $router->get('ajudrk-preview','Rkap\PengajuanDrkController@getPreview');
    $router->get('ajudrk-history','Rkap\PengajuanDrkController@getHistory');
    $router->get('ajudrk-preview-his','Rkap\PengajuanDrkController@getPreviewHis');
    $router->get('ajudrk-history-his','Rkap\PengajuanDrkController@getHistoryHis');
    $router->post('ajudrk','Rkap\PengajuanDrkController@store');
    $router->post('ajudrk-edit','Rkap\PengajuanDrkController@update');
    $router->delete('ajudrk','Rkap\PengajuanDrkController@destroy');   

    //Approval DAM
    $router->get('appdrk','Rkap\ApprovalDrkController@index');
    $router->get('appdrk-aju','Rkap\ApprovalDrkController@getPengajuan');
    $router->get('appdrk-detail','Rkap\ApprovalDrkController@show');
    $router->post('appdrk','Rkap\ApprovalDrkController@store');
    $router->get('appdrk-status','Rkap\ApprovalDrkController@getStatus');
    $router->get('appdrk-preview','Rkap\ApprovalDrkController@getPreview');
    $router->get('app-preview-outlook','Rkap\ApprovalOutlookController@getPreview');//approval

    // Pengajuan Usulan
    $router->get('aju-usul','Rkap\PengajuanUsulController@index');
    $router->get('aju-usul-box','Rkap\PengajuanUsulController@getDataBox');
    $router->get('aju-usul-perbaikan','Rkap\PengajuanUsulController@getDataPerbaikan');
    //$router->get('aju-usul-finish','Rkap\PengajuanUsulController@getFinish');
    $router->get('aju-usul-detail','Rkap\PengajuanUsulController@show');
    $router->get('aju-usul-rkm','Rkap\PengajuanUsulController@getRKM');
    $router->get('aju-usul-akun','Rkap\PengajuanUsulController@getAkun');
    $router->get('aju-usul-preview','Rkap\PengajuanUsulController@getPreview');
    $router->get('aju-usul-history','Rkap\PengajuanUsulController@getHistory');
    //$router->get('aju-usul-preview-his','Rkap\PengajuanUsulController@getPreviewHis');
    //$router->get('aju-usul-history-his','Rkap\PengajuanUsulController@getHistoryHis');
    $router->post('aju-usul','Rkap\PengajuanUsulController@store');
    $router->post('aju-usul-edit','Rkap\PengajuanUsulController@update');
    $router->delete('aju-usul','Rkap\PengajuanUsulController@destroy');
    $router->get('aju-usul-cek-akses-form','Rkap\PengajuanUsulController@cekAksesForm');
    //$router->post('aju-usul-notifikasi','Rkap\PengajuanUsulController@sendNotifikasi'); 
    $router->get('aju-usul-draft','Rkap\PengajuanUsulController@AjuDraft');
    $router->get('aju-usul-sedang','Rkap\PengajuanUsulController@AjuSedang');
    $router->get('aju-usul-selesai','Rkap\PengajuanUsulController@AjuSelesai');
    
    //Approval Usulan
    $router->get('app-usul','Rkap\ApprovalUsulController@index');
    $router->get('app-usul-aju','Rkap\ApprovalUsulController@getPengajuan');
    $router->get('app-usul-detail','Rkap\ApprovalUsulController@show');
    $router->post('app-usul','Rkap\ApprovalUsulController@store');
    //$router->get('app-usul-status','Rkap\ApprovalUsulController@getStatus');
    $router->get('app-usul-preview','Rkap\ApprovalUsulController@getPreview');
});

$router->get('tagihan-export', 'Rkap\TagihanController@export');

?>