<?php

namespace App\Http\Controllers\Bdh;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; 

class DropingKirimNonAjuController extends Controller
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

    public function generateNo(Request $request) {
        $this->validate($request, [    
            'tanggal' => 'required',
            'jenis' => 'required'       
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }	

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("kas_m", "no_kas", $kode_lokasi."-".$request->jenis.substr($periode,2,4).".", "0001");

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

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $get = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik' ");
            if(count($get) > 0){
                $kode_pp = $get[0]->kode_pp;
            }else{
                $kode_pp = "-";
            }

            $sql="select distinct a.no_kas,convert(varchar,a.tanggal,103) as tgl,a.jenis,a.no_dokumen,a.keterangan,a.nilai 
            from kas_m a left join yk_kasdrop_d b on a.no_kas=b.no_kas and a.kode_lokasi=b.kode_lokasi and b.no_kasterima<>'-' 
            where b.no_kasterima is null and a.kode_lokasi='".$kode_lokasi."' and a.modul = 'KBDROP' and a.posted ='F'";

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal' => 'required|date_format:Y-m-d',
            'jenis' => 'required',
            'no_dokumen' => 'required|max:50',
            'deskripsi' => 'required|max:200',
            'akun_mutasi' => 'required',
            'akun_kas' => 'required',
            'nik_app' => 'required',
            'total' => 'required',
            'kode_lokasi' => 'required|array',
            'keterangan' => 'required|array',
            'nilai' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("kas_m", "no_kas", $kode_lokasi."-".$request->jenis.substr($periode,2,4).".", "0001");

            $cek = $this->doCekPeriode($periode);
            if($cek['status']){

                $get = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik' ");
                if(count($get) > 0){
                    $kode_pp = $get[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }
            
                $j = 0;
                $total = 0;
                if(count($request->kode_lokasi) > 0){
                    for ($i=0; $i<count($request->kode_lokasi); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into yk_kasdrop_d(no_spb,nu,no_kas,no_dokumen,kode_lokasi,periode,kode_loktuj,kode_rek,keterangan,nilai,progress,akun_tak,no_kasterima) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$i,$no_bukti,$request->no_dokumen,$kode_lokasi,$periode,$request->kode_lokasi[$i],'-',$request->keterangan[$i],floatval($request->nilai[$i]),0,$request->akun_mutasi,'-'));
                        $total+= +floatval($request->nilai[$i]);
                    }
                }

                if($total != floatval($request->total)){
                    $msg = "Transaksi tidak valid. Total Droping ($request->total) dan Total Detail ($total) tidak sama.";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                    if($total > 0){

                        $insm = DB::connection($this->db)->insert("insert into kas_m (no_kas,kode_lokasi,no_dokumen,no_bg,akun_kb,tanggal,keterangan,kode_pp,modul,jenis,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$request->no_dokumen,'-',$request->akun_kas,$request->tanggal, $request->deskripsi,$kode_pp,'KBDROP',$request->jenis,$periode,'IDR',1,floatval($request->total),$nik,$request->nik_app,$nik,'F','-','-',$request->akun_mutasi,'-'));

                        $insj1 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?) ",array($no_bukti,$request->no_dokumen,$request->tanggal,0,$request->akun_mutasi,$request->deskripsi,'D',floatval($request->total),$kode_pp,'-','-','-',$kode_lokasi,'KBDROP','TAK',$periode,'IDR',1,$nik,'-'));

                        $insj2 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?) ",array($no_bukti,$request->no_dokumen,$request->tanggal,1,$request->akun_kas,$request->deskripsi,'C',floatval($request->total),$kode_pp,'-','-','-',$kode_lokasi,'KBDROP','KB',$periode,'IDR',1,$nik,'-'));
                        
                    
                        DB::connection($this->db)->commit();
                        $success['status'] = true;
                        $success['no_bukti'] = $no_bukti;
                        $success['message'] = "Data Droping berhasil disimpan";
                    }else{

                        DB::connection($this->db)->rollback();
                        $success['status'] = false;
                        $success['no_bukti'] = "-";
                        $success['message'] = "Transaksi tidak valid. Total Droping tidak boleh kurang dari atau sama dengan nol";
                    }
                }
            
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = "-";
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Droping gagal disimpan ".$e;
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required',
            'tanggal' => 'required|date_format:Y-m-d',
            'jenis' => 'required',
            'no_dokumen' => 'required|max:50',
            'deskripsi' => 'required|max:200',
            'akun_mutasi' => 'required',
            'akun_kas' => 'required',
            'nik_app' => 'required',
            'total' => 'required',
            'kode_lokasi' => 'required|array',
            'keterangan' => 'required|array',
            'nilai' => 'required|array'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }
            
            $no_bukti = $request->no_bukti;
            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);

            $cek = $this->doCekPeriode($periode);
            if($cek['status']){
                
                $get = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik' ");
                if(count($get) > 0){
                    $kode_pp = $get[0]->kode_pp;
                }else{
                    $kode_pp = "-";
                }
                
                $del = DB::connection($this->db)->table('ys_kasdrop_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kas', $no_bukti)
                ->delete();

                $del2 = DB::connection($this->db)->table('kas_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kas', $no_bukti)
                ->delete();

                $del3 = DB::connection($this->db)->table('kas_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kas', $no_bukti)
                ->delete();

                $j = 0;
                $total = 0;
                if(count($request->kode_lokasi) > 0){
                    for ($i=0; $i<count($request->kode_lokasi); $i++){	
                        $insj[$i] = DB::connection($this->db)->insert("insert into yk_kasdrop_d(no_spb,nu,no_kas,no_dokumen,kode_lokasi,periode,kode_loktuj,kode_rek,keterangan,nilai,progress,akun_tak,no_kasterima) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",array($no_bukti,$i,$no_bukti,$request->no_dokumen,$kode_lokasi,$periode,$request->kode_lokasi[$i],'-',$request->keterangan[$i],floatval($request->nilai[$i]),0,$request->akun_mutasi,'-'));
                        $total+= +floatval($request->nilai[$i]);
                    }
                }

                if($total != floatval($request->total)){
                    $msg = "Transaksi tidak valid. Total Droping ($request->total) dan Total Detail ($total) tidak sama.";
                    DB::connection($this->db)->rollback();
                    $success['status'] = false;
                    $success['no_bukti'] = "-";
                    $success['message'] = $msg;
                }else{
                    if($total > 0){

                        $insm = DB::connection($this->db)->insert("insert into kas_m (no_kas,kode_lokasi,no_dokumen,no_bg,akun_kb,tanggal,keterangan,kode_pp,modul,jenis,periode,kode_curr,kurs,nilai,nik_buat,nik_app,tgl_input,nik_user,posted,no_del,no_link,ref1,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?, ?, ?, ?, ?, ?)",array($no_bukti,$kode_lokasi,$request->no_dokumen,'-',$request->akun_kas,$request->tanggal, $request->deskripsi,$kode_pp,'KBDROP',$request->jenis,$periode,'IDR',1,floatval($request->total),$nik,$request->nik_app,$nik,'F','-','-',$request->akun_mutasi,'-'));

                        $insj1 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?) ",array($no_bukti,$request->no_dokumen,$request->tanggal,0,$request->akun_mutasi,$request->deskripsi,'D',floatval($request->total),$kode_pp,'-','-','-',$kode_lokasi,'KBDROP','TAK',$periode,'IDR',1,$nik,'-'));

                        $insj2 = DB::connection($this->db)->insert("insert into kas_j(no_kas,no_dokumen,tanggal,no_urut,kode_akun,keterangan,dc,nilai,kode_pp,kode_drk,kode_cf,ref1,kode_lokasi,modul,jenis,periode,kode_curr,kurs,nik_user,tgl_input,kode_bank) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, getdate(), ?) ",array($no_bukti,$request->no_dokumen,$request->tanggal,1,$request->akun_kas,$request->deskripsi,'C',floatval($request->total),$kode_pp,'-','-','-',$kode_lokasi,'KBDROP','KB',$periode,'IDR',1,$nik,'-'));
                        
                    
                        DB::connection($this->db)->commit();
                        $success['status'] = true;
                        $success['no_bukti'] = $no_bukti;
                        $success['message'] = "Data Droping berhasil disimpan";
                    }else{

                        DB::connection($this->db)->rollback();
                        $success['status'] = false;
                        $success['no_bukti'] = "-";
                        $success['message'] = "Transaksi tidak valid. Total Droping tidak boleh kurang dari atau sama dengan nol";
                    }
                }
            
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['no_bukti'] = "-";
                $success['message'] = $cek["message"];
            }
                            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['no_bukti'] = "-";
            $success['message'] = "Data Droping gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fs  $Fs
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'no_bukti' => 'required'
        ]);
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $no_bukti = $request->no_bukti;
           
            if(isset($request->periode) && $request->periode != ""){
                $periode = $request->periode;
            }else{
                $periode = date('Ym');
            }
            $cek = $this->doCekPeriode($periode);
            if($cek['status']){
            
                $del = DB::connection($this->db)->table('kas_m')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kas', $no_bukti)
                ->delete();

                $del2 = DB::connection($this->db)->table('kas_j')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kas', $no_bukti)
                ->delete();

                $del3 = DB::connection($this->db)->table('yk_kasdrop_d')
                ->where('kode_lokasi', $kode_lokasi)
                ->where('no_kas', $no_bukti)
                ->delete();

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Droping berhasil dihapus";
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = $cek["message"];
            }
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Droping gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select keterangan,no_dokumen,jenis,tanggal,akun_kb,ref1,nik_app 
            from kas_m 							 
            where no_kas = '".$request->no_bukti."' and kode_lokasi='".$kode_lokasi."' ";
            $rs = DB::connection($this->db)->select($strSQL);
            $res = json_decode(json_encode($rs),true);
            
            $strSQL2 = "select a.kode_loktuj,b.nama,a.keterangan,a.nilai 
            from yk_kasdrop_d a inner join lokasi b on a.kode_loktuj=b.kode_lokasi 
            where a.no_kas = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu";
            $rs2 = DB::connection($this->db)->select($strSQL2);
            $res2 = json_decode(json_encode($rs2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak

                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
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

    public function getAkunMutasi(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $strSQL = "select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi where b.kode_flag = '016' and a.kode_lokasi='$kode_lokasi' ";

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

    public function getAkunKas(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $strSQL = "select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
            where b.kode_flag in ('009') and a.kode_lokasi = '".$kode_lokasi."'";
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

    public function getNIKApp(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $get = DB::connection($this->db)->select("select a.flag,b.nama from spro a inner join karyawan b on a.flag=b.nik and a.kode_lokasi=b.kode_lokasi where kode_spro='KBAPP' and a.kode_lokasi='$kode_lokasi' ");
            if(count($get) > 0){
                $nik = $get[0]->flag;
                $nama = $get[0]->nama;
            }else{
                $nik = "-";
                $nama = "-";
            }

            $success['nik_default'] = $nik;
            $success['nama_nik_default'] = $nama;
            
            $strSQL = "select nik, nama from karyawan where flag_aktif='1' and kode_lokasi = '".$kode_lokasi."'";
            
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

    public function getLokasi(Request $request)
    {

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin= $data->status_admin;
            }

            $get = DB::connection($this->db)->select("select kode_lokkonsol from lokasi where kode_lokasi='$kode_lokasi' ");
            if(count($get) > 0){
                $kode_lokasi_konsol = $get[0]->kode_lokkonsol;
            }else{
                $kode_lokasi_konsol = "-";
            }

            $strSQL = "select kode_lokasi, nama from lokasi where kode_lokasi not in ('".$kode_lokasi_konsol."','".$kode_lokasi."')";
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

}
