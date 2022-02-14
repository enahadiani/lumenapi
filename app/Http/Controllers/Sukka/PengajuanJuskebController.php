<?php

namespace App\Http\Controllers\Sukka;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Log;
use Carbon\Carbon; 

class PengajuanJuskebController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'sqlsrvyptkug';
    public $guard = 'yptkug';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->db)->select("select max(periode) as periode from periode where kode_lokasi ='$kode_lokasi' ");
        if(count($query) > 0){
            $periode = $query[0]->periode;
        }else{
            $periode = "-";
        }
        return $periode;
    }

    function namaPeriode($periode){
        $bulan = substr($periode,4,2);
        $tahun = substr($periode,0,4);
        switch ($bulan){
            case 1 : case '1' : case '01': $bulan = "Januari"; break;
            case 2 : case '2' : case '02': $bulan = "Februari"; break;
            case 3 : case '3' : case '03': $bulan = "Maret"; break;
            case 4 : case '4' : case '04': $bulan = "April"; break;
            case 5 : case '5' : case '05': $bulan = "Mei"; break;
            case 6 : case '6' : case '06': $bulan = "Juni"; break;
            case 7 : case '7' : case '07': $bulan = "Juli"; break;
            case 8 : case '8' : case '08': $bulan = "Agustus"; break;
            case 9 : case '9' : case '09': $bulan = "September"; break;
            case 10 : case '10' : case '10': $bulan = "Oktober"; break;
            case 11 : case '11' : case '11': $bulan = "November"; break;
            case 12 : case '12' : case '12': $bulan = "Desember"; break;
            default: $bulan = null;
        }
    
        return $bulan.' '.$tahun;
    }

    function doCekPeriode2($modul,$status,$periode) {
        try{
            
            $perValid = false;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            if ($status == "A") {

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal2 and per_akhir2";
            }else{

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal1 and per_akhir1";
            }

            $auth = DB::connection($this->db)->select($strSQL);
            $auth = json_decode(json_encode($auth),true);
            if(count($auth) > 0){
                $perValid = true;
                $msg = "ok";
            }else{
                if ($status == "A") {

                    $strSQL2 = "select per_awal2 as per_awal,per_akhir2 as per_akhir from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' ";
                }else{
    
                    $strSQL2 = "select per_awal1 as per_awal,per_akhir1 as per_akhir from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."'";
                }
                $get = DB::connection($this->db)->select($strSQL2);
                if(count($get) > 0){
                    $per_awal = $this->namaPeriode($get[0]->per_awal);
                    $per_akhir = $this->namaPeriode($get[0]->per_akhir);
                    $msg = "Transaksi tidak dapat disimpan karena tanggal di periode tersebut di tutup. Periode Aktif ".$per_awal." s/d ".$per_akhir;
                }else{
                    $msg = "Transaksi tidak dapat disimpan karena periode aktif modul $modul belum disetting.";
                }
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        // $result['sql'] = $strSQL;
        return $result;		
    }

    function doCekPeriode($periode) {
        try{
            
            $perValid = false;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            
            if($periode_aktif == $periode){
                $perValid = true;
                $msg = "ok";
            }else{
                if($periode_aktif > $periode){
                    $perValid = false;
                    $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh kurang dari periode aktif sistem.[$periode_aktif]";
                }else{
                    $perNext = $this->nextNPeriode($periode,1);
                    if($perNext == "1"){
                        $perValid = true;
                        $msg = "ok";
                    }else{
                        $perValid = false;
                        $msg = "Periode transaksi tidak valid. Periode transaksi tidak boleh melebihi periode aktif sistem.[$periode_aktif]";
                    }
                }
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        // $result['sql'] = $strSQL;
        return $result;		
    }

    function nextNPeriode($periode, $n) 
    {
        $bln = floatval(substr($periode,4,2));
        $thn = floatval(substr($periode,0,4));
        for ($i = 1; $i <= $n;$i++){
            if ($bln < 12) $bln++;
            else {
                $bln = 1;
                $thn++;
            }
        }
        if ($bln < 10) $bln = "0".$bln;
        return $thn."".$bln;
    }

    function cekPeriode($periode) {
        try{
            
            $perValid = false;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            
            if(substr($periode_aktif,0,4) == substr($periode,0,4)){
                $perValid = true;
                $msg = "ok";
            }else{
                $perValid = false;
                $msg = "Periode transaksi tidak valid. Periode transaksi harus dalam tahun anggaran yang sama.[".substr($periode_aktif,0,4)."]";
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        return $result;		
    }

    public function index(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.no_bukti,a.tanggal,a.nilai,a.kegiatan,a.periode,a.kode_pp,a.jenis,case a.progress when 'R' then 'Return' when 'S' then 'Selesai' else isnull(x.nama_jab,'-') end as progress,case when datediff(minute,a.tanggal,getdate()) <= 10 then 'baru' else 'lama' end as status
            from apv_juskeb_m a 	 		
            left join (select a.no_bukti,b.nama as nama_jab
                                from apv_flow a
                                inner join apv_jab b on a.kode_jab=b.kode_jab --and a.kode_lokasi=b.kode_lokasi
                                where a.kode_lokasi='$kode_lokasi' and a.status='1'
                                )x on a.no_bukti=x.no_bukti
            where a.progress in ('0')  and a.nik_buat='$nik' order by a.tanggal";

            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $r
     * @return \Illuminate\Http\Response
     */
    public function store(Request $r)
    {
        $this->validate($r, [
            'periode' => 'required|date_format:Ym',
            'kegiatan' => 'required|max:1000',
            'kode_pp' => 'required',
            'jenis' => 'required',
            'nilai' => 'required',
            'kode_jenis' => 'required',
            'latar' => 'required|max:1000',
            'aspek' => 'required|max:1000',
            'spesifikasi' => 'required|max:1000',
            'rencana' => 'required|max:1000',
            'nik' => 'required|array',
            'kode_jab' => 'required|array',
            'kode_role' => 'required|array',
            'email' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = date('Ym');
            $no_bukti = $this->generateKode("apv_juskeb_m", "no_bukti", $kode_lokasi."-JK".substr($periode,2,4).".", "0001");

            // CEK PERIODE
            // $cek = $this->cekPeriode($periode);
            // if($cek['status']){

                $ins_m = DB::connection($this->db)->insert("insert into apv_juskeb_m (no_bukti,kode_lokasi,kode_pp,tanggal,kegiatan,nik_buat,nilai,progress,latar,aspek,spesifikasi,rencana,jenis,periode,kode_jenis) values (?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$r->input('kode_pp'),$r->input('kegiatan'),$nik,$r->input('nilai'),'0',$r->input('latar'),$r->input('aspek'),$r->input('spesifikasi'),$r->input('rencana'),$r->input('jenis'),$r->input('periode'),$r->input('kode_jenis')));

                if(count($r->input('nik')) > 0){
                    for($i=0; $i < count($r->input('nik')); $i++){
                        if($i == 0){
                            $status = 1;
                            $app_email = $r->input('email')[$i];
                            $nik_app = $r->input('nik')[$i];
                        }else{
                            $status = 0;
                        }
                        $ins_d = DB::connection($this->db)->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,tgl_app,kode_pp,nik) values (?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$r->input('kode_role')[$i],$r->input('kode_jab')[$i],$i,$status,NULL,'-',$r->input('nik')[$i]));   
                    }
                }

                if(isset($nik_app) && $nik_app != ""){
                    $title = "Pengajuan Justifikasi Pengadaan";
                    $subtitle = "-";
                    $content = "Pengajuan Justifikasi Pengadaan No: $no_bukti menunggu approval Anda.";
                    $no_pesan = $this->generateKode("app_notif_m", "no_bukti","PSN".$periode.".", "000001");
                    $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app,'-',$no_bukti,'-','-',0,0]);
                    $success['no_pesan'] = $no_pesan;
                }

                if(isset($app_email) && $app_email != ""){
                    $pesan_header = "Pengajuan Justifikasi Pengadaan No: $no_bukti berikut menunggu approval Anda.";
                    $r->request->add(['no_bukti' => ["=",$no_bukti,""]]);
                    $result = app('App\Http\Controllers\Sukka\LaporanController')->getDataEmail($r);
                    $result = json_decode(json_encode($result),true);
                    $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".$periode.".", "000001");
                    $result['original']['judul'] = $pesan_header;
                    $html = view('email-sukka',$result['original'])->render();
                    $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-',$app_email,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
                    $success['no_pooling'] = $no_pool;
                }
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Pengajuan Justifikasi Kebutuhan berhasil disimpan";
                        
            // }else{
            //     DB::connection($this->db)->rollback();
            //     $success['status'] = false;
            //     $success['no_bukti'] = "-";
            //     $success['message'] = $cek["message"];
            // }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengajuan Justifikasi Kebutuhan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function edit(Fs $Fs)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $r
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $r)
    {
        $this->validate($r, [
            'no_bukti' => 'required',
            'periode' => 'required|date_format:Ym',
            'kegiatan' => 'required|max:1000',
            'kode_pp' => 'required',
            'jenis' => 'required',
            'nilai' => 'required',
            'kode_jenis' => 'required',
            'latar' => 'required|max:1000',
            'aspek' => 'required|max:1000',
            'spesifikasi' => 'required|max:1000',
            'rencana' => 'required|max:1000',
            'nik' => 'required|array',
            'kode_jab' => 'required|array',
            'kode_role' => 'required|array',
            'email' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            
            $periode = date('Ym');
            $no_bukti = $r->no_bukti;
            // CEK PERIODE
            // $cek = $this->cekPeriode($periode);
            // if($cek['status']){

                $del1 = DB::connection($this->db)->table('apv_juskeb_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();

                $del2 = DB::connection($this->db)->table('apv_flow')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_bukti', $no_bukti)
                ->delete();
                
                $ins_m = DB::connection($this->db)->insert("insert into apv_juskeb_m (no_bukti,kode_lokasi,kode_pp,tanggal,kegiatan,nik_buat,nilai,progress,latar,aspek,spesifikasi,rencana,jenis,periode,kode_jenis) values (?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$r->input('kode_pp'),$r->input('kegiatan'),$nik,$r->input('nilai'),'0',$r->input('latar'),$r->input('aspek'),$r->input('spesifikasi'),$r->input('rencana'),$r->input('jenis'),$r->input('periode'),$r->input('kode_jenis')));

                if(count($r->input('nik')) > 0){
                    for($i=0; $i < count($r->input('nik')); $i++){
                        if($i == 0){
                            $status = 1;
                            $app_email = $r->input('email')[$i];
                            $nik_app = $r->input('nik')[$i];
                        }else{
                            $status = 0;
                        }
                        $ins_d = DB::connection($this->db)->insert("insert into apv_flow (no_bukti,kode_lokasi,kode_role,kode_jab,no_urut,status,tgl_app,kode_pp,nik) values (?, ?, ?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$kode_lokasi,$r->input('kode_role')[$i],$r->input('kode_jab')[$i],$i,$status,NULL,'-',$r->input('nik')[$i]));   
                    }
                }

                if(isset($nik_app) && $nik_app != ""){
                    $title = "Update Justifikasi Pengadaan";
                    $subtitle = "-";
                    $content = "Pengajuan Justifikasi Pengadaan No: $no_bukti menunggu approval Anda.";
                    $no_pesan = $this->generateKode("app_notif_m", "no_bukti","PSN".substr($periode,4,2).".", "000001");
                    $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app,'-',$no_bukti,'-','-',0,0]);
                    $success['no_pesan'] = $no_pesan;
                }

                if(isset($app_email) && $app_email != ""){
                    
                    $pesan_header = "Pengajuan Justifikasi Pengadaan No: $no_bukti berikut menunggu approval Anda.";
                    $r->request->add(['no_bukti' => ["=",$no_bukti,""]]);
                    $result = app('App\Http\Controllers\Sukka\LaporanController')->getDataEmail($r);
                    $result = json_decode(json_encode($result),true);
                    $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".substr($periode,4,2).".", "000001");
                    $result['original']['judul'] = $pesan_header;
                    $html = view('email-sukka',$result['original'])->render();
                    $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-',$app_email,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
                    $success['no_pooling'] = $no_pool;
                }
                
                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Pengajuan Justifikasi Kebutuhan berhasil diubah";
            // }else{
            //     DB::connection($this->db)->rollback();
            //     $success['status'] = false;
            //     $success['no_bukti'] = "-";
            //     $success['message'] = $cek["message"];
            // }
                            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Data Pengajuan Justifikasi Kebutuhan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $r)
    {
        $this->validate($r, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $r->no_bukti;
    
            $del1 = DB::connection($this->db)->table('apv_juskeb_m')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();

            $del2 = DB::connection($this->db)->table('apv_flow')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('no_bukti', $no_bukti)
            ->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pengajuan Justifikasi Kebutuhan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengajuan Justifikasi Kebutuhan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function show(Request $r)
    {
        $this->validate($r,[
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select *,b.nama as nama_pp,c.nama as nama_jenis 
            from apv_juskeb_m a
            left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            left join apv_jenis c on a.kode_jenis=c.kode_jenis 
            where a.no_bukti=? and a.kode_lokasi=?";
            $rs = DB::connection($this->db)->select($strSQL,array($r->input('no_bukti'),$kode_lokasi));
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $strdet = "select *,b.nama,b.email from apv_flow a
                inner join apv_karyawan b on a.nik=b.nik
                where a.no_bukti = ? and a.kode_lokasi=? order by a.no_urut";
                $rsdet = DB::connection($this->db)->select($strdet,array($r->input('no_bukti'),$kode_lokasi));
                $resdet = json_decode(json_encode($rsdet),true);

                
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $resdet;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPreview(Request $request){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $request->no_bukti;

            $sql="select *,b.nama as nama_pp,c.nama as nama_jenis 
            from apv_juskeb_m a
            left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            left join apv_jenis c on a.kode_jenis=c.kode_jenis 
            where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti' ";
            
            $res = DB::connection($this->db)->select($sql);
            $res = json_decode(json_encode($res),true);

            $sql="select * from (select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,d.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut,a.tanggal as tgl
                from apv_juskeb_m a
                left join apv_karyawan c on a.nik_buat=c.nik and a.kode_lokasi=c.kode_lokasi
                left join apv_jab d on c.kode_jab=d.kode_jab and c.kode_lokasi=d.kode_lokasi
                where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
                union all
                select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,d.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.tanggal as tgl
                from apv_flow a
                inner join apv_juskeb_m b on a.no_bukti=b.no_bukti and a.kode_lokasi=b.kode_lokasi
                inner join apv_karyawan c on a.nik=c.nik
                left join apv_jab d on c.kode_jab=d.kode_jab and c.kode_lokasi=d.kode_lokasi
                inner join apv_pesan e on a.no_bukti=e.no_bukti and a.kode_lokasi=e.kode_lokasi and a.no_urut=e.no_urut
                where a.kode_lokasi='$kode_lokasi' and a.no_bukti='$no_bukti'
			) a
			order by a.no_app,a.tgl
            ";
            $res2 = DB::connection($this->db)->select($sql);
            $res2 = json_decode(json_encode($res2),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                $success["auth_status"] = 1;        

                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAppFlow(Request $r)
    {
        $this->validate($r,[
            'nilai' => 'required',
            'kode_jenis' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $strSQL = "select a.kode_role,b.kode_jab,c.nik,c.nama,c.email 
            from apv_role a
            inner join apv_role_jab b on a.kode_role=b.kode_role and a.kode_lokasi=b.kode_lokasi
            inner join apv_karyawan c on b.kode_jab=c.kode_jab 
            where a.jenis=? and ? between a.bawah and a.atas";

            $rs = DB::connection($this->db)->select($strSQL,array($r->input('kode_jenis'),$r->input('nilai')));
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = $r->input();
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPP(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $filter = "";
            if(isset($r->kode_pp) && $r->kode_pp != ""){
                $filter = " and a.kode_pp='$r->kode_pp' ";
            }

            $strSQL = "select a.kode_pp, a.nama  
            from pp a 
            where a.kode_lokasi = '".$kode_lokasi."' and a.tipe='posting' and a.flag_aktif ='1' $filter";
            
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getJenis(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $filter = "";
            if(isset($r->kode_jenis) && $r->kode_jenis != ""){
                $filter = " where kode_jenis='$r->kode_jenis' ";
            }

            $strSQL = "select kode_jenis, nama from apv_jenis $filter ";				
            $res = DB::connection($this->db)->select($strSQL);						
            $res= json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = true;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function sendNotifikasi(Request $r)
    {
        $this->validate($r,[
            "no_pooling" => 'required'
        ]);

        if($auth =  Auth::guard($this->guard)->user()){
            $nik= $auth->nik;
            $kode_lokasi= $auth->kode_lokasi;
        }

        DB::connection($this->db)->beginTransaction();
        try{
            $client = new Client();
            $res = DB::connection($this->db)->select("select no_hp,pesan,jenis,email from pooling where flag_kirim=0 and no_pool ='$r->no_pooling'  ");
            if(count($res) > 0){
                $msg = "";
                $sts = false;
                foreach($res as $row){
                    if($row->jenis == "EMAIL") {
                        $credentials = base64_encode('api:'.config('services.mailgun.secret'));
                        $domain = "https://api.mailgun.net/v3/".config('services.mailgun.domain')."/messages";
                        $response = $client->request('POST',  $domain,[
                            'headers' => [
                                'Authorization' => 'Basic '.$credentials
                            ],
                            'form_params' => [
                                'from' => 'devsaku5@gmail.com',
                                'to' => $row->email,
                                'subject' => 'Pengajuan Justifkasi Kebutuhan',
                                'html' => htmlspecialchars_decode($row->pesan)
                            ]
                        ]);
                        if ($response->getStatusCode() == 200) { // 200 OK
                            $response_data = $response->getBody()->getContents();
                            $data = json_decode($response_data,true);
                            if(isset($data["id"])){
                                $success['data2'] = $data;

                                $updt =  DB::connection($this->db)->table('pooling')
                                ->where('no_pool', $r->no_pooling)    
                                ->where('jenis', 'EMAIL')
                                ->where('flag_kirim', 0)
                                ->update(['tgl_kirim' => Carbon::now()->timezone("Asia/Jakarta"), 'flag_kirim' => 1]);

                                
                                DB::connection($this->db)->commit();
                                $sts = true;
                                $msg .= $data['message'];
                            }
                        }
                    }
                    
                }

                $success['message'] = $msg;
                $success['status'] = $sts;
            }else{
                $success['message'] = "Data pooling tidak valid";
                $success['status'] = false;
            }
            return response()->json($success, 200);
        } catch (BadResponseException $ex) {
            
            DB::connection($this->db)->rollback();
            $response = $ex->getResponse();
            $res = json_decode($response->getBody(),true);
            $data['message'] = $res;
            $data['status'] = false;
            return response()->json($data, 500);
        }
    }

    public function getEmailView(Request $r)
    {
        $this->validate($r, [
            'no_aju' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            
            $r->request->add(['no_bukti' => ["=",$r->no_aju,""]]);
            $result = app('App\Http\Controllers\Sukka\LaporanController')->getDataEmail($r);
            $result = json_decode(json_encode($result),true);
            $result['original']['judul'] = "Pengajuan Juskeb";
            return view('email-sukka',$result['original']);

        } catch (\Throwable $e) {
            dd($e->getMessage());
        }	
    }

}
