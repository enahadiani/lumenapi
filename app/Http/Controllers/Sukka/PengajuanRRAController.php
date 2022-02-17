<?php

namespace App\Http\Controllers\Sukka;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Imports\RRAjuImport;
use App\Exports\RRAjuExport;
use Maatwebsite\Excel\Facades\Excel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Log;
use Carbon\Carbon; 

class PengajuanRRAController extends Controller
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

    public function generateNo(Request $r) {
        $this->validate($r, [    
            'tanggal' => 'required'       
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }	

            $periode = substr($r->tanggal,0,4).substr($r->tanggal,5,2);
            $no_bukti = $this->generateKode("apv_pdrk_m", "no_pdrk", $kode_lokasi."-RRA".substr($periode,2,4).".", "0001");

            $success['status'] = true;
            $success['no_bukti'] = $no_bukti;
            $success['message'] = "Success!";
            return response()->json($success, $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['no_bukti'] = "-";
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
            inner join apv_role_jab b on a.kode_role=b.kode_role 
            inner join apv_karyawan c on b.kode_jab=c.kode_jab 
            where a.jenis=? and ? between a.bawah and a.atas and a.form='RRA' ";

            $rs = DB::connection($this->db)->select($strSQL,array($r->input('kode_jenis'),$r->input('nilai')));
            $res = json_decode(json_encode($rs),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = $strSQL;
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

            // $sql="select a.no_pdrk,convert(varchar,a.tanggal,103) as tgl,b.no_dokumen,a.keterangan,case a.progress when 'R' then 'Return Approval RRA' when 'P' then 'Finish Approval RRA' else isnull(x.nama_jab,'-') end as progress,a.tanggal, b.nilai,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, a.tgl_input,a.progress as sts_log  
            // from apv_pdrk_m a 
            // inner join anggaran_m b on a.no_pdrk=b.no_agg 	
            // left join (select a.no_bukti,b.nama as nama_jab
            //             from apv_flow a
            //             inner join apv_jab b on a.kode_jab=b.kode_jab
            //             where a.status='1'
            //             )x on a.no_pdrk=x.no_bukti
            // where a.progress in ('0','R','P') and a.modul = 'PUSAT' order by a.tanggal";
            $sql ="select a.no_pdrk,a.keterangan
            from apv_pdrk_m a
            where a.progress in ('0','R','P') and a.modul = 'PUSAT' ";

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
            'tanggal' => 'required|date_format:Y-m-d',
            'no_dokumen' => 'required',
            'deskripsi' => 'required|max:200',
            'lokasi_terima' => 'required',
            'lokasi_beri' => 'required',
            'no_juskeb' => 'required',
            'total_terima' => 'required',
            'total_beri' => 'required',
            'kode_akun' => 'required|array',
            'kode_pp' => 'required|array',
            'kode_drk' => 'required|array',
            'tw' => 'required|array',
            'nilai' => 'required|array',
            'kode_akun_terima' => 'required|array',
            'kode_pp_terima' => 'required|array',
            'kode_drk_terima' => 'required|array',
            'tw_terima' => 'required|array',
            'nilai_terima' => 'required|array',
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

            $periode = substr($r->tanggal,0,4).substr($r->tanggal,5,2);
            $no_bukti = $this->generateKode("apv_pdrk_m", "no_pdrk", $kode_lokasi."-RRA".substr($periode,2,4).".", "0001");

            // CEK PERIODE
            // $cek = $this->cekPeriode($periode);
            // if($cek['status']){

                $j = 0;
                $total_beri = 0;
                if(count($r->kode_akun) > 0){
                    for ($i=0; $i<count($r->kode_akun); $i++){	
                        if ($r->tw[$i] == "TW1") { $bulan = "01"; }
                        if ($r->tw[$i] == "TW2") { $bulan = "04"; }
                        if ($r->tw[$i] == "TW3") { $bulan = "07"; }
                        if ($r->tw[$i] == "TW4") { $bulan = "10"; }
                        
                        $periode_beri = substr($periode,0,4).''.$bulan;
                        $insd1[$i] = DB::connection($this->db)->insert("insert into apv_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$r->input('lokasi_beri'),$i,$r->input('kode_akun')[$i],$r->input('kode_pp')[$i],$r->input('kode_drk')[$i],$periode_beri,0,floatval($r->input('nilai')[$i]),'C','-'));

                        $total_beri+= +floatval($r->input('nilai')[$i]);
                    }
                }

                $total_terima = 0;
                if(count($r->kode_akun_terima) > 0){
                    for ($i=0; $i<count($r->kode_akun_terima); $i++){	
                        if ($r->tw_terima[$i] == "TW1") { $bulan = "01"; }
                        if ($r->tw_terima[$i] == "TW2") { $bulan = "04"; }
                        if ($r->tw_terima[$i] == "TW3") { $bulan = "07"; }
                        if ($r->tw_terima[$i] == "TW4") { $bulan = "10"; }
                        
                        $periode_terima = substr($periode,0,4).''.$bulan;
                        $insd3[$i] = DB::connection($this->db)->insert("insert into apv_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$r->input('lokasi_terima'),$i,$r->input('kode_akun_terima')[$i],$r->input('kode_pp_terima')[$i],$r->input('kode_drk_terima')[$i],$periode_terima,0,floatval($r->input('nilai_terima')[$i]),'D','-'));

                        $total_terima+= +floatval($r->input('nilai_terima')[$i]);
                    }
                }

                if($total_beri != $total_terima){
                    $msg = "Transaksi tidak valid. Total Terima dan Pemberi tidak sama.";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                    if($total_beri > 0 && $total_terima > 0){
                        
                        $getpp = DB::connection($this->db)->select("select a.kode_pp from karyawan a  where a.nik='$nik' and a.kode_lokasi='".$kode_lokasi."'");
                        if (count($getpp) > 0){
                            $this_pp = $getpp[0]->kode_pp;;					 
                        } else {
                            $this_pp = "";		 
                        }

                        $insm2 = DB::connection($this->db)->insert("insert into apv_pdrk_m(no_pdrk,kode_lokasi,lok_donor,keterangan,kode_pp,kode_bidang,jenis_agg,tanggal,periode,nik_buat,sts_pdrk,justifikasi, nik_user, tgl_input,progress,modul,no_dokumen) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?)",array($no_bukti,$r->input('lokasi_terima'),$r->input('lokasi_beri'),$r->input('deskripsi'),$this_pp,'-','ANGGARAN',$r->input('tanggal'),$periode,$nik,'RRA',$r->input('no_juskeb'),$nik,'0','PUSAT',$r->input('no_dokumen')));

                        $arr_dok = array();
                        $arr_jenis = array();
                        $arr_no_urut = array();
                        $i=0;
                        $cek = $r->file_dok;
                        if(!empty($cek)){
                            if(count($r->input('nama_file_seb')) > 0){
                                //looping berdasarkan nama dok
                                for($i=0;$i<count($r->input('nama_file_seb'));$i++){
                                    //cek row i ada file atau tidak
                                    if(isset($r->file('file_dok')[$i])){
                                        $file = $r->file('file_dok')[$i];
                                        //kalo ada cek nama sebelumnya ada atau -
                                        if($r->input('nama_file_seb')[$i] != "-"){
                                            //kalo ada hapus yang lama
                                            Storage::disk('s3')->delete('sukka/'.$r->input('nama_file_seb')[$i]);
                                        }
                                        $nama_dok = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                        $dok = $nama_dok;
                                        if(Storage::disk('s3')->exists('sukka/'.$dok)){
                                            Storage::disk('s3')->delete('sukka/'.$dok);
                                        }
                                        Storage::disk('s3')->put('sukka/'.$dok,file_get_contents($file));
                                        $arr_dok[] = $dok;
                                        $arr_jenis[] = $r->input('kode_jenis')[$i];
                                        $arr_no_urut[] = $r->input('no_urut')[$i];
                                    }else if($r->input('nama_file_seb')[$i] != "-"){
                                        $arr_dok[] = $r->input('nama_file_seb')[$i];
                                        $arr_jenis[] = $r->input('kode_jenis')[$i];
                                        $arr_no_urut[] = $r->input('no_urut')[$i];
                                    }     
                                }
                                
                                $deldok = DB::connection($this->db)->table('apv_pdrk_dok')->where('no_bukti', $no_bukti)->delete();
                            }
            
                            if(count($arr_no_urut) > 0){
                                for($i=0; $i<count($arr_no_urut);$i++){
                                    $insdok[$i] = DB::connection($this->db)->insert("insert into apv_pdrk_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) values (?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'RRA',$no_bukti)); 
                                }
                            }
                        }

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
                            $title = "Pengajuan RRA";
                            $subtitle = "-";
                            $content = "Pengajuan RRA No: $no_bukti menunggu approval Anda.";
                            $no_pesan = $this->generateKode("app_notif_m", "no_bukti",$kode_lokasi."-PN".substr($periode,2,4).".", "000001");
                            $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app,'-',$no_bukti,'-','-',0,0]);
                            $success['no_pesan'] = $no_pesan;
                        }
        
                        if(isset($app_email) && $app_email != ""){
                            $pesan_header = "Pengajuan RRA No: $no_bukti berikut menunggu approval Anda.";
                            $r->request->add(['no_bukti' => ["=",$no_bukti,""]]);
                            $result = app('App\Http\Controllers\Sukka\LaporanController')->getRRAForm($r);
                            $result = json_decode(json_encode($result),true);
                            $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".substr($periode,2,4).".", "000001");
                            $result['original']['judul'] = $pesan_header;
                            $html = view('email-rra-sukka',$result['original'])->render();
                            $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-',$app_email,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
                            $success['no_pooling'] = $no_pool;
                        }
                        
                        DB::connection($this->db)->commit();
                        $success['status'] = true;
                        $success['no_bukti'] = $no_bukti;
                        $success['message'] = "Data Pengajuan RRA berhasil disimpan";
                        
                    }else{

                        DB::connection($this->db)->rollback();
                        $success['status'] = false;
                        $success['no_bukti'] = "-";
                        $success['message'] = "Transaksi tidak valid. Total Terima atau Pemberi tidak boleh kurang dari atau sama dengan nol";
                    }
                }
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
            $success['message'] = "Data Pengajuan RRA gagal disimpan ".$e;
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
            'tanggal' => 'required|date_format:Y-m-d',
            'no_dokumen' => 'required',
            'deskripsi' => 'required|max:200',
            'lokasi_terima' => 'required',
            'lokasi_beri' => 'required',
            'no_juskeb' => 'required',
            'total_terima' => 'required',
            'total_beri' => 'required',
            'kode_akun' => 'required|array',
            'kode_pp' => 'required|array',
            'kode_drk' => 'required|array',
            'tw' => 'required|array',
            'nilai' => 'required|array',
            'kode_akun_terima' => 'required|array',
            'kode_pp_terima' => 'required|array',
            'kode_drk_terima' => 'required|array',
            'tw_terima' => 'required|array',
            'nilai_terima' => 'required|array',
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
            
            $no_bukti = $r->no_bukti;

            $periode = substr($r->tanggal,0,4).substr($r->tanggal,5,2);
            if ($r->input('jenis') == "ANGGARAN") { 
                $jenis = "RRA";											
            }
            else {
                $jenis = "RRRMULTI";
            }

            // CEK PERIODE
            // $cek = $this->cekPeriode($periode);
            // if($cek['status']){

                $del1 = DB::connection($this->db)->table('apv_pdrk_m')
                ->where('no_pdrk', $no_bukti)
                ->delete();

                $del2 = DB::connection($this->db)->table('apv_pdrk_d')
                ->where('no_pdrk', $no_bukti)
                ->delete();

                $del3 = DB::connection($this->db)->table('apv_flow')
                ->where('no_bukti', $no_bukti)
                ->delete();

                $j = 0;
                $total_beri = 0;
                if(count($r->kode_akun) > 0){
                    for ($i=0; $i<count($r->kode_akun); $i++){	
                        if ($r->tw[$i] == "TW1") { $bulan = "01"; }
                        if ($r->tw[$i] == "TW2") { $bulan = "04"; }
                        if ($r->tw[$i] == "TW3") { $bulan = "07"; }
                        if ($r->tw[$i] == "TW4") { $bulan = "10"; }
                        
                        $periode_beri = substr($periode,0,4).''.$bulan;
                        $insd1[$i] = DB::connection($this->db)->insert("insert into apv_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$r->input('lokasi_beri'),$i,$r->input('kode_akun')[$i],$r->input('kode_pp')[$i],$r->input('kode_drk')[$i],$periode_beri,0,floatval($r->input('nilai')[$i]),'C','-'));

                        $total_beri+= +floatval($r->input('nilai')[$i]);
                    }
                }

                $total_terima = 0;
                if(count($r->kode_akun_terima) > 0){
                    for ($i=0; $i<count($r->kode_akun_terima); $i++){	
                        if ($r->tw_terima[$i] == "TW1") { $bulan = "01"; }
                        if ($r->tw_terima[$i] == "TW2") { $bulan = "04"; }
                        if ($r->tw_terima[$i] == "TW3") { $bulan = "07"; }
                        if ($r->tw_terima[$i] == "TW4") { $bulan = "10"; }
                        
                        $periode_terima = substr($periode,0,4).''.$bulan;
                        $insd3[$i] = DB::connection($this->db)->insert("insert into apv_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$r->input('lokasi_terima'),$i,$r->input('kode_akun_terima')[$i],$r->input('kode_pp_terima')[$i],$r->input('kode_drk_terima')[$i],$periode_terima,0,floatval($r->input('nilai_terima')[$i]),'D','-'));

                        $total_terima+= +floatval($r->input('nilai_terima')[$i]);
                    }
                }

                if($total_beri != $total_terima){
                    $msg = "Transaksi tidak valid. Total Terima dan Pemberi tidak sama.";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                    if($total_beri > 0 && $total_terima > 0){
                        
                        $getpp = DB::connection($this->db)->select("select a.kode_pp from karyawan a  where a.nik='$nik' and a.kode_lokasi='".$kode_lokasi."'");
                        if (count($getpp) > 0){
                            $this_pp = $getpp[0]->kode_pp;;					 
                        } else {
                            $this_pp = "";		 
                        }

                        $insm2 = DB::connection($this->db)->insert("insert into apv_pdrk_m(no_pdrk,kode_lokasi,lok_donor,keterangan,kode_pp,kode_bidang,jenis_agg,tanggal,periode,nik_buat,sts_pdrk,justifikasi, nik_user, tgl_input,progress,modul,no_dokumen) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?)",array($no_bukti,$r->input('lokasi_terima'),$r->input('lokasi_beri'),$r->input('deskripsi'),$this_pp,'-','ANGGARAN',$r->input('tanggal'),$periode,$nik,'RRA',$r->input('no_juskeb'),$nik,'0','PUSAT',$r->input('no_dokumen')));

                        $arr_dok = array();
                        $arr_jenis = array();
                        $arr_no_urut = array();
                        $i=0;
                        $cek = $r->file_dok;
                        if(!empty($cek)){
                            if(count($r->input('nama_file_seb')) > 0){
                                //looping berdasarkan nama dok
                                for($i=0;$i<count($r->input('nama_file_seb'));$i++){
                                    //cek row i ada file atau tidak
                                    if(isset($r->file('file_dok')[$i])){
                                        $file = $r->file('file_dok')[$i];
                                        //kalo ada cek nama sebelumnya ada atau -
                                        if($r->input('nama_file_seb')[$i] != "-"){
                                            //kalo ada hapus yang lama
                                            Storage::disk('s3')->delete('sukka/'.$r->input('nama_file_seb')[$i]);
                                        }
                                        $nama_dok = uniqid()."_".str_replace(' ', '_', $file->getClientOriginalName());
                                        $dok = $nama_dok;
                                        if(Storage::disk('s3')->exists('sukka/'.$dok)){
                                            Storage::disk('s3')->delete('sukka/'.$dok);
                                        }
                                        Storage::disk('s3')->put('sukka/'.$dok,file_get_contents($file));
                                        $arr_dok[] = $dok;
                                        $arr_jenis[] = $r->input('kode_jenis')[$i];
                                        $arr_no_urut[] = $r->input('no_urut')[$i];
                                    }else if($r->input('nama_file_seb')[$i] != "-"){
                                        $arr_dok[] = $r->input('nama_file_seb')[$i];
                                        $arr_jenis[] = $r->input('kode_jenis')[$i];
                                        $arr_no_urut[] = $r->input('no_urut')[$i];
                                    }     
                                }
                                
                                $deldok = DB::connection($this->db)->table('apv_pdrk_dok')->where('no_bukti', $no_bukti)->delete();
                            }
            
                            if(count($arr_no_urut) > 0){
                                for($i=0; $i<count($arr_no_urut);$i++){
                                    $insdok[$i] = DB::connection($this->db)->insert("insert into apv_pdrk_dok (no_bukti,no_gambar,nu,kode_jenis,kode_lokasi,modul,no_ref) values (?, ?, ?, ?, ?, ?, ?) ",array($no_bukti,$arr_dok[$i],$arr_no_urut[$i],$arr_jenis[$i],$kode_lokasi,'RRA',$no_bukti)); 
                                }
                            }
                        }

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
                            $title = "Update Pengajuan RRA";
                            $subtitle = "RRA";
                            $content = "Pengajuan RRA No: $no_bukti menunggu approval Anda.";
                            $no_pesan = $this->generateKode("app_notif_m", "no_bukti",$kode_lokasi."-PN".substr($periode,2,4).".", "000001");
                            $inspesan= DB::connection($this->db)->insert('insert into app_notif_m(no_bukti,kode_lokasi,judul,subjudul,pesan,nik,tgl_input,icon,ref1,ref2,ref3,sts_read,sts_kirim) values (?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)', [$no_pesan,$kode_lokasi,$title,$subtitle,$content,$nik_app,'-',$no_bukti,'-','-',0,0]);
                            $success['no_pesan'] = $no_pesan;
                        }
        
                        if(isset($app_email) && $app_email != ""){
                            $pesan_header = "Pengajuan RRA No: $no_bukti berikut menunggu approval Anda.";
                            $r->request->add(['no_bukti' => ["=",$no_bukti,""]]);
                            $result = app('App\Http\Controllers\Sukka\LaporanController')->getRRAForm($r);
                            $result = json_decode(json_encode($result),true);
                            $no_pool = $this->generateKode("pooling", "no_pool", $kode_lokasi."-PL".substr($periode,2,4).".", "000001");
                            $result['original']['judul'] = $pesan_header;
                            $html = view('email-rra-sukka',$result['original'])->render();
                            $inspool= DB::connection($this->db)->insert('insert into pooling(no_hp,email,pesan,flag_kirim,tgl_input,tgl_kirim,jenis,no_pool) values (?,?,?,?,getdate(),?,?,?)', ['-',$app_email,htmlspecialchars($html),'0',NULL,'EMAIL',$no_pool]);
                            $success['no_pooling'] = $no_pool;
                        }
                        
                        DB::connection($this->db)->commit();
                        $success['status'] = true;
                        $success['no_bukti'] = $no_bukti;
                        $success['message'] = "Data Pengajuan RRA berhasil diubah";
                        
                    }else{

                        DB::connection($this->db)->rollback();
                        $success['status'] = false;
                        $success['no_bukti'] = "-";
                        $success['message'] = "Transaksi tidak valid. Total Terima atau Pemberi tidak boleh kurang dari atau sama dengan nol";
                    }
                }
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
            $success['message'] = "Data Pengajuan RRA gagal diubah ".$e;
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
    
            $del3 = DB::connection($this->db)->table('apv_pdrk_m')
            ->where('no_pdrk', $no_bukti)
            ->delete();
            
            $del4 = DB::connection($this->db)->table('apv_pdrk_d')
            ->where('no_pdrk', $no_bukti)
            ->delete();
            
            $del5 = DB::connection($this->db)->table('apv_flow')
            ->where('no_bukti', $no_bukti)
            ->delete();
            
            $res = DB::connection($this->db)->select("select * from apv_pdrk_dok where no_bukti='$no_bukti' ");
            $res = json_decode(json_encode($res),true);
            for($i=0;$i<count($res);$i++){
                if(Storage::disk('s3')->exists('sukka/'.$res[$i]['no_gambar'])){
                    Storage::disk('s3')->delete('sukka/'.$res[$i]['no_gambar']);
                }
            }
            
            $deldok = DB::connection($this->db)->table('apv_pdrk_dok')->where('no_bukti', $no_bukti)->delete();
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Pengajuan RRA berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pengajuan RRA gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroyDok(Request $r)
    {
        $this->validate($r, [
            'no_bukti' => 'required',
            'kode_jenis' => 'required',
            'no_urut' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }		

            $sql3="select no_bukti,kode_lokasi,no_gambar as file_dok,nu,kode_jenis from apv_pdrk_dok where no_bukti='$r->no_bukti' and kode_jenis='$r->kode_jenis' and nu='$r->no_urut' ";
            $res3 = DB::connection($this->db)->select($sql3);
            $res3 = json_decode(json_encode($res3),true);

            if(count($res3) > 0){

                for($i=0;$i<count($res3);$i++){
                    Storage::disk('s3')->delete('sukka/'.$res3[$i]['file_dok']);
                }

                $del3 = DB::connection($this->db)->table('apv_pdrk_dok')
                ->where('no_bukti', $r->no_bukti)
                ->where('kode_jenis', $r->kode_jenis)
                ->where('nu', $r->no_urut)
                ->delete();

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Dokumen Pengajuan RRA berhasil dihapus";
            }else{
                $success['status'] = false;
                $success['message'] = "Data Dokumen Pengajuan RRA gagal dihapus.";
            }

            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Dokumen Pengajuan RRA gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function showH(Request $r)
    {
        $this->validate($r,[
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $no_app = isset($r->no_app) ? $r->no_app : '-';
            $strSQL = "select b.no_dokumen,a.justifikasi,e.nama as nama_jenis,a.tanggal,a.keterangan,a.kode_lokasi as lokasi_terima,a.lok_donor as lokasi_beri,c.nama as nama_terima, d.nama as nama_beri,a.no_pdrk,convert(varchar,a.tanggal,103) as tgl,a.jenis_agg,e.kode_pp,e.nilai,e.kode_jenis 
            from apv_pdrk_m a 
            inner join anggaran_m b on a.no_pdrk=b.no_agg 			
            left join lokasi c on a.kode_lokasi=c.kode_lokasi
            left join lokasi d on a.lok_donor=d.kode_lokasi
            left join apv_juskeb_m e on convert(varchar,a.justifikasi)=e.no_bukti 
            where a.no_pdrk = '".$r->no_bukti."' ";
        
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);

            $reslok = DB::connection($this->db)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='".$kode_lokasi."'");						
            $reslok= json_decode(json_encode($reslok),true);
            $success['lokasi'] = $reslok;
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                if($res[0]['jenis_agg'] == "ANGGARAN"){
                    $strd = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai,0 as saldo
                    --,dbo.fn_saldoGarTW(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,substring(a.periode,1,4),case substring(a.periode,5,2) when '01' then 'TW1' when '04' then 'TW2' when '07' then 'TW3' when '10' then 'TW4' end,a.no_pdrk) as saldo 
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk='".$r->no_bukti."' and a.dc ='C'";

                    $strt = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai,0 as saldo
                    --dbo.fn_saldoGarTW(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,substring(a.periode,1,4),case substring(a.periode,5,2) when '01' then 'TW1' when '04' then 'TW2' when '07' then 'TW3' when '10' then 'TW4' end,a.no_pdrk) as saldo 
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk='".$r->no_bukti."' and a.dc ='D'";
                }else{
                    $strd = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai,0 as saldo
                    --dbo.fn_saldoRilis(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,a.periode,a.no_pdrk) as saldo 
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk='".$r->no_bukti."' and a.dc ='C'";

                    $strt = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai, 0 as saldo
                    --dbo.fn_saldoRilis(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,a.periode,a.no_pdrk) as saldo 
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk='".$r->no_bukti."' and a.dc ='D'";
                }
                $rsd = DB::connection($this->db)->select($strd);
                $resd = json_decode(json_encode($rsd),true);

                $rst = DB::connection($this->db)->select($strt);
                $rest = json_decode(json_encode($rst),true);

                $strdok = "select b.kode_jenis as jenis,b.nama,a.no_gambar as fileaddres
                from pbh_dok a inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                where a.no_bukti = '".$r->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
                $rsdok = DB::connection($this->db)->select($strdok);
                $resdok = json_decode(json_encode($rsdok),true);

                $strdet = "select *,b.nama,b.email from apv_flow a
                inner join apv_karyawan b on a.nik=b.nik
                where a.no_bukti = ? order by a.no_urut";
                $rsdet = DB::connection($this->db)->select($strdet,array($r->input('no_bukti')));
                $resdet = json_decode(json_encode($rsdet),true);
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail_beri'] = $resd;
                $success['detail_app'] = $resdet;
                $success['detail_terima'] = $rest;
                $success['dokumen'] = $resdok;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_app'] = [];
                $success['detail_beri'] = [];
                $success['detail_terima'] = [];
                $success['dokumen'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail_app'] = [];
            $success['detail_beri'] = [];
            $success['detail_terima'] = [];
            $success['dokumen'] = [];
            $success['message'] = "Error ".$e;
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

            $strSQL = "select a.*,b.nama as nama_pp,c.nama as nama_jenis,d.nama as nama_terima,e.nama as nama_beri,isnull(f.no_pdrk,'-') as no_pdrk,convert(varchar,f.tanggal,103) as tgl_pdrk,f.no_dokumen,f.keterangan 
            from apv_juskeb_m a
            left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
            left join apv_jenis c on a.kode_jenis=c.kode_jenis 
            left join lokasi d on a.lok_terima=d.kode_lokasi
            left join lokasi e on a.lok_donor=e.kode_lokasi
            left join apv_pdrk_m f on a.no_bukti=convert(varchar,f.justifikasi)
            where a.no_bukti=?";
            $rs = DB::connection($this->db)->select($strSQL,array($r->input('no_bukti')));
            $res = json_decode(json_encode($rs),true);
            
            $reslok = DB::connection($this->db)->select("select a.nama,a.no_telp,a.alamat,a.kodepos,a.kota,a.email
            from lokasi a
            where a.kode_lokasi='".$kode_lokasi."'");						
            $reslok= json_decode(json_encode($reslok),true);
            $success['lokasi'] = $reslok;
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                if($res[0]['no_pdrk'] != "-"){
                    $strd = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk=? and a.dc ='C'";

                    $strt = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai
                    from apv_pdrk_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                        left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                        left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                    where a.no_pdrk=? and a.dc ='D'";
                
                    $rsd = DB::connection($this->db)->select($strd,array($res[0]['no_pdrk']));
                    $resd = json_decode(json_encode($rsd),true);
                    
                    $rst = DB::connection($this->db)->select($strt,array($res[0]['no_pdrk']));
                    $rest = json_decode(json_encode($rst),true);

                    $strdok = "select b.kode_jenis as jenis,b.nama,a.no_gambar as fileaddres,a.modul,a.nu
                    from apv_juskeb_dok a 
                    inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti = ?  
                    union all
                    select b.kode_jenis as jenis,b.nama,a.no_gambar as fileaddres,a.modul,a.nu
                    from apv_pdrk_dok a 
                    inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti = ? 
                    order by a.nu";
                    $rsdok = DB::connection($this->db)->select($strdok,array($r->input('no_bukti'),$res[0]['no_pdrk']));
                    $resdok = json_decode(json_encode($rsdok),true);
    
                    $strdet = "select *,b.nama,b.email from apv_flow a
                    inner join apv_karyawan b on a.nik=b.nik
                    where a.no_bukti = ? order by a.no_urut";
                    $rsdet = DB::connection($this->db)->select($strdet,array($res[0]['no_pdrk']));
                    $resdet = json_decode(json_encode($rsdet),true);
                }else{

                    $strd = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai
                        from apv_juskeb_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                            left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                            left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                        where a.no_bukti=? and a.dc ='C'";

                    $strt = "select substring(a.periode,5,2) as bulan,a.kode_pp,b.nama as nama_pp,a.kode_drk,isnull(d.nama,'-') as nama_drk,a.kode_akun,c.nama as nama_akun,a.nilai
                        from apv_juskeb_d a left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                            left join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 
                            left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(a.periode,1,4)  
                        where a.no_bukti=? and a.dc ='D'";
                    
                    $rsd = DB::connection($this->db)->select($strd,array($r->input('no_bukti')));
                    $resd = json_decode(json_encode($rsd),true);
                        
                    $rst = DB::connection($this->db)->select($strt,array($r->input('no_bukti')));
                    $rest = json_decode(json_encode($rst),true);

                    $strdok = "select b.kode_jenis as jenis,b.nama,a.no_gambar as fileaddres,a.modul
                    from apv_juskeb_dok a 
                    inner join dok_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi
                    where a.no_bukti = ?  order by a.nu";
                    $rsdok = DB::connection($this->db)->select($strdok,array($r->input('no_bukti')));
                    $resdok = json_decode(json_encode($rsdok),true);
    
                    $strdet = "select *,b.nama,b.email from apv_flow a
                    inner join apv_karyawan b on a.nik=b.nik
                    where a.no_bukti = ? order by a.no_urut";
                    $rsdet = DB::connection($this->db)->select($strdet,array($r->input('no_bukti')));
                    $resdet = json_decode(json_encode($rsdet),true);

                }
                
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $resdet;
                $success['detail_beri'] = $resd;
                $success['detail_terima'] = $rest;
                $success['dokumen'] = $resdok;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail'] = [];
                $success['detail_beri'] = [];
                $success['detail_terima'] = [];
                $success['dokumen'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['data'] = [];
            $success['detail'] = [];
            $success['detail_beri'] = [];
            $success['detail_terima'] = [];
            $success['dokumen'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }


    public function getPreview(Request $r){
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $no_bukti = $r->input('no_bukti');

            $sql = "select a.periode,convert(varchar,a.tanggal,103) as tanggal,a.no_pdrk,a.kode_lokasi,a.keterangan,a.nik_buat,b.nama as nama_buat
            from apv_pdrk_m a
            inner join karyawan b on a.nik_buat=b.nik
            where a.no_pdrk= ?
            order by a.no_pdrk";
            
            $res = DB::connection($this->db)->select($sql,array($no_bukti));
            $res = json_decode(json_encode($res),true);

            $sql="select * from (select 'Dibuat oleh' as ket,c.kode_jab,a.nik_buat as nik, c.nama as nama_kar,d.nama as nama_jab,convert(varchar,a.tanggal,103) as tanggal,'-' as no_app,'-' as status,-4 as nu, '-' as urut,a.tanggal as tgl
                from apv_pdrk_m a
                left join apv_karyawan c on a.nik_buat=c.nik 
                left join apv_jab d on c.kode_jab=d.kode_jab 
                where a.no_pdrk=?
                union all
                select 'Diapprove oleh' as ket,a.kode_jab,c.nik,c.nama as nama_kar,d.nama as nama_jab,isnull(convert(varchar,e.tanggal,103),'-') as tanggal,isnull(convert(varchar,e.id),'-') as no_app,case e.status when '2' then 'APPROVE' when '3' then 'REVISI' else '-' end as status,-2 as nu, isnull(convert(varchar,e.id),'X') as urut,e.tanggal as tgl
                from apv_flow a
                inner join apv_pdrk_m b on a.no_bukti=b.no_pdrk 
                inner join apv_karyawan c on a.nik=c.nik
                left join apv_jab d on c.kode_jab=d.kode_jab 
                inner join apv_pesan e on a.no_bukti=e.no_bukti and a.no_urut=e.no_urut
                where a.no_bukti=?
			) a
			order by a.no_app,a.tgl
            ";
            $res3 = DB::connection($this->db)->select($sql,array($no_bukti,$no_bukti));
            $res3 = json_decode(json_encode($res3),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $i=0;
                foreach($res as $row){
                    $sql2 = "select a.kode_akun,a.kode_pp,a.kode_drk,a.periode,a.dc,a.nilai,
                    b.nama as nama_akun,c.nama as nama_pp,d.nama as nama_drk, 
                    case when a.dc='D' then a.nilai else 0 end debet,case when a.dc='C' then a.nilai else 0 end kredit
                    from apv_pdrk_d a
                    left join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                    left join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                    left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun=substring(?,1,4)
                    where a.no_pdrk=? 
                    order by a.dc,a.kode_akun";
                    $res2 = DB::connection($this->db)->select($sql2,array($row['periode'],$row['no_pdrk']));
                    $res[$i]['detail'] = json_decode(json_encode($res2),true);
                    $i++;
                }
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res3;
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
            $success['detail_app'] = [];
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAkun(Request $r)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($r->kode_akun) && $r->kode_akun != ""){
                $filter = " and kode_akun='$r->kode_akun' ";
            }

            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $kode_lokasi = $r->kode_lokasi;
            }

            $strSQL = "select kode_akun,nama    from masakun where status_gar ='1' and block= '0' and kode_lokasi = '".$kode_lokasi."' $filter";

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

    public function getPP(Request $r)
    {

        $this->validate($r,[
            'kode_lokasi' => 'required'
        ]);
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
            where a.kode_lokasi = '".$r->kode_lokasi."' and a.tipe='posting' and a.flag_aktif ='1' $filter";
            
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

    public function getDRKPemberi(Request $r)
    {
        $this->validate($r,[
            'kode_akun' => 'required',
            'periode' => 'required',
            'kode_pp' => 'required',
            'kode_lokasi' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $sts = true;
            $data = DB::connection($this->db)->select("select count(distinct a.kode_drk) as jml from drk a inner join anggaran_d b on a.kode_drk=b.kode_drk where a.tahun=substring(b.periode,1,4) and b.periode like '".substr($r->periode,0,4)."%' and b.kode_akun='".$r->kode_akun."' and b.kode_pp = '".$r->kode_pp."' and a.kode_lokasi='".$r->kode_lokasi."' ");
            if(count($data) > 0){
                $line = $data[0];				
                if (intval($line->jml) != 0) $sts = false; 
                
            }

            $filter = "";
            if(isset($r->kode_drk) && $r->kode_drk != ""){
                $filter = " and a.kode_drk='$r->kode_drk' ";
            }

            $strSQL="select distinct a.kode_drk, a.nama from drk a inner join anggaran_d b on a.kode_drk=b.kode_drk where a.tahun=substring(b.periode,1,4) and b.periode like '".substr($r->periode,0,4)."%' and b.kode_akun='".$r->kode_akun."' and b.kode_pp = '".$r->kode_pp."' and a.kode_lokasi='".$r->kode_lokasi."' $filter union all select '-','-' ";

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

    public function getDRK(Request $r)
    {
        $this->validate($r,[
            'periode' => 'required',
            'kode_lokasi' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $filter = "";
            if(isset($r->kode_drk) && $r->kode_drk != ""){
                $filter = " and a.kode_drk='$r->kode_drk' ";
            }

            $strSQL="select a.kode_drk, a.nama from drk a where a.tahun='".substr($r->periode,0,4)."' and a.kode_lokasi='".$r->kode_lokasi."' $filter";

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
    
    public function getLokasi(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $filter = "";
            if(isset($r->kode_lokasi) && $r->kode_lokasi != ""){
                $filter = " and kode_lokasi='$r->kode_lokasi' ";
            }

            $strSQL = "select kode_lokasi, nama from lokasi where kode_lokasi<>'00' $filter ";	

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

    public function getJenisDokumen(Request $r)
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

            $strSQL = "select kode_jenis, nama  from dok_jenis where kode_lokasi='$kode_lokasi' $filter ";				
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

    
    public function getJuskeb(Request $r)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $filter = "";
            if(isset($r->no_pdrk) && $r->no_pdrk != ""){
                $strSQL = "select a.*,b.nama as nama_pp,c.nama as nama_jenis,isnull(d.no_pdrk,'-') as no_pdrk,isnull(d.progress,'-') as progress_pdrk
                from apv_juskeb_m a
                left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                left join apv_jenis c on a.kode_jenis=c.kode_jenis  
                inner join apv_pdrk_m d on a.no_bukti=convert(varchar,d.justifikasi)
                where a.progress ='J' and d.no_pdrk='$r->no_pdrk'  ";		
            }else{

                $strSQL = "select a.*,b.nama as nama_pp,c.nama as nama_jenis,isnull(d.no_pdrk,'-') as no_pdrk,isnull(d.progress,'-') as progress_pdrk
                from apv_juskeb_m a
                left join pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi 
                left join apv_jenis c on a.kode_jenis=c.kode_jenis  
                left join apv_pdrk_m d on a.no_bukti=convert(varchar,d.justifikasi)
                where a.progress ='J' and d.no_pdrk is null  ";				
            }

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

    public function validateJU($kode_akun,$kode_pp,$kode_drk,$kode_lokasi,$periode,$jenis){
        if($dt =  Auth::guard($this->guard)->user()){
            $app_nik= $dt->nik;
            $app_lokasi= $dt->kode_lokasi;
        }

        $keterangan = "";
        $auth = DB::connection($this->db)->select("select kode_akun from masakun where kode_akun='$kode_akun' and kode_lokasi='$app_lokasi' 
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode Akun $kode_akun tidak valid. ";
        }

        $authpp = DB::connection($this->db)->select("select kode_pp from pp where kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi' 
        ");
        $authpp = json_decode(json_encode($authpp),true);
        if(count($authpp) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode PP $kode_pp tidak valid. ";
        }

        if($jenis == "BERI"){
            $authdrk = DB::connection($this->db)->select("select distinct a.kode_drk, a.nama from drk a inner join anggaran_d b on a.kode_drk=b.kode_drk where a.tahun=substring(b.periode,1,4) and b.periode like '".substr($periode,0,4)."%' and b.kode_akun='".$kode_akun."' and b.kode_pp = '".$kode_pp."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_drk='$kode_drk'
            ");
        }else{
            $authdrk = DB::connection($this->db)->select("select a.kode_drk, a.nama from drk a where a.tahun='".substr($periode,0,4)."' and a.kode_lokasi='".$kode_lokasi."' and a.kode_drk='$kode_drk'
            ");
        }
        $authdrk = json_decode(json_encode($authdrk),true);
        if(count($authdrk) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode DRK $kode_drk tidak valid. ";
        }

        return $keterangan;

    }

    public function importExcel(Request $r)
    {
        $this->validate($r, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required',
            'no_bukti'=>'required',
            'kode_lokasi' => 'required',
            'jenis' => 'required',
            'periode' => 'required'
        ]);
        
        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('rra_aju_tmp')
            ->where('nik_user',$r->nik_user)
            ->where('kode_lokasi',$r->kode_lokasi)
            ->where('jenis',$r->jenis)
            ->delete();
            
            // menangkap file excel
            $file = $r->file('file');
            
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();
            
            Storage::disk('s3')->put($nama_file,file_get_contents($file));
            $dt = Excel::toArray(new RRAjuImport(),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            
            $sql = "";
            $begin = "SET NOCOUNT on;
            BEGIN tran;
            ";
            $commit = "commit tran;";
            
            $i=1;$no=1;
            set_time_limit(300);
            $ins = array(); 
            $periode = date('Ym');
            $no_bukti = $r->no_bukti;
            foreach($excel as $row){
                if($row[0] != ""){
                    $ket = $this->validateJU(strval($row[0]),strval($row[2]),strval($row[4]),$r->kode_lokasi,$r->periode,$r->jenis);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                        $ket = "-";
                    }
                    $ins[] = DB::connection($this->db)->insert("insert into rra_aju_tmp(no_bukti,kode_lokasi,kode_akun,kode_pp,kode_drk,tw,saldo,nilai,jenis,sts_upload,ket_upload,no_urut,nik_user,tgl_input) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate())",[$no_bukti,$r->kode_lokasi,$row[0],$row[2],$row[4],$row[6],0,$row[7],$r->jenis,$sts,$ket,$no,$r->nik_user]);
                    // $sql .= "insert into agg_rkm_d_tmp(no_bukti,kode_lokasi,nama,kode_dam,no_urut,tgl_input,nik_user,sts_upload,ket_upload) values ('$no_bukti','$kode_lokasi','".$this->removeKutip($row[0])."','".$this->removeKutip($row[1])."',$no,getdate(),'$r->nik_user','$sts','$ket') ";
                    
                    // if($i % 1000 == 0){
                    //     $sql = $begin.$sql.$commit;
                    //     $ins[] = DB::connection($this->db)->update($sql);
                    //     $sql = "";
                    // }
                    // if($i == count($excel) && ($i % 1000 != 0) ){
                    //     $sql = $begin.$sql.$commit;
                    //     $ins[] = DB::connection($this->db)->update($sql);
                    //     $sql = "";
                    // }
                    $i++;
                    $no++;
                }
            }
            
            DB::connection($this->db)->commit();
            Storage::disk('s3')->delete($nama_file);
            if($status_validate){
                $msg = "File berhasil diupload!";
            }else{
                $msg = "Ada error!";
            }
            
            $success['excel'] = count($excel);
            $success['i'] = $i;
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            // $success['message'] = "Error ".$e;
            $success['message'] = "Internal Server Error".$e;
            Log::error($e);
            return response()->json($success, $this->successStatus);
        }
        
    }
 
    public function export(Request $r) 
    {
        $this->validate($r, [
            'nik_user' => 'required',
            'kode_lokasi' => 'required',
            'jenis' => 'required',
            'nik' => 'required'
        ]);
        
        date_default_timezone_set("Asia/Bangkok");
        $nik_user = $r->nik_user;
        $nik = $r->nik;
        $kode_lokasi = $r->kode_lokasi;
        $jenis = $r->jenis;
        
        if(isset($r->type) && $r->type != ""){
            return Excel::download(new RRAjuExport($nik_user,$kode_lokasi,$jenis,$r->type), 'RRAju_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }else{
            return Excel::download(new RRAjuExport($nik_user,$kode_lokasi,$jenis,'error'), 'RRAju_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
        }
    }
 
    public function getDataTmp(Request $r)
    {
        $this->validate($r, [
            'nik_user' => 'required',
            'kode_lokasi' => 'required',
            'jenis'=>'required',
            'jenis_agg' => 'required'
        ]);
        
        $nik_user = $r->nik_user;
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $kode_lokasi= $data->kode_lokasi;
            }

            $tahun = date('Y');
            if($r->jenis == "BERI"){
                if($r->jenis_agg == "ANGGARAN"){
                    $ck_budget = "dbo.fn_saldoGarTW(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,d.tahun,a.tw,a.no_bukti)";
                }else{
                    $ck_budget = "dbo.fn_saldoRilis(a.kode_lokasi,a.kode_akun,a.kode_pp,a.kode_drk,d.tahun+(case when a.tw = 'TW1' then '01' when 'TW2' then '04' when 'TW3' then '07' when 'TW4' then '10' end),a.no_bukti";
                }
                $sql = "select distinct a.no_bukti,a.no_urut,a.kode_akun,b.nama as nama_akun,a.kode_pp,c.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.tw,round(".$ck_budget.",0) as saldo,a.nilai
                            from rra_aju_tmp a
                            left join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                            left join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                            left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun='$tahun'
                            left join anggaran_d e on a.kode_drk=e.kode_drk and d.kode_drk=e.kode_drk and d.kode_lokasi=e.kode_lokasi and a.kode_akun=e.kode_akun and a.kode_pp=e.kode_pp 
                            where a.nik_user='$nik_user' and a.kode_lokasi='$r->kode_lokasi' and a.jenis='$r->jenis'
                            order by a.no_bukti,a.no_urut ";
            }else{
                $sql = "select distinct a.no_bukti,a.no_urut,a.kode_akun,b.nama as nama_akun,a.kode_pp,c.nama as nama_pp,a.kode_drk,d.nama as nama_drk,a.tw,a.nilai
                            from rra_aju_tmp a
                            left join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
                            left join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
                            left join drk d on a.kode_drk=d.kode_drk and a.kode_lokasi=d.kode_lokasi and d.tahun='$tahun'
                            left join anggaran_d e on a.kode_drk=e.kode_drk and d.kode_drk=e.kode_drk and d.kode_lokasi=e.kode_lokasi and a.kode_akun=e.kode_akun and a.kode_pp=e.kode_pp 
                            where a.nik_user='$nik_user' and a.kode_lokasi='$r->kode_lokasi' and a.jenis='$r->jenis'
                            order by a.no_bukti,a.no_urut ";
            }
            $res = DB::connection($this->db)->select($sql);
            $res= json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['detail'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['detail'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            // $success['message'] = "Error ".$e;
            $success['message'] = "Internal Server Error";
            Log::error($e);
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
                                'subject' => 'Pengajuan RRA',
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
            $result = app('App\Http\Controllers\Sukka\LaporanController')->getRRAForm($r);
            $result = json_decode(json_encode($result),true);
            $result['original']['judul'] = "Pengajuan RRA";
            // dd($result);
            return view('email-rra-sukka',$result['original']);

        } catch (\Throwable $e) {
            dd($e->getMessage());
        }	
    }

}
