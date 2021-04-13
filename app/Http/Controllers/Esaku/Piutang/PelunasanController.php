<?php

namespace App\Http\Controllers\Esaku\Piutang;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Imports\PiutangImport;
use App\Exports\PiutangExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 

class PelunasanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;
    public $db = 'tokoaws';
    public $guard = 'toko';

    
    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    function getPeriodeAktif($kode_lokasi){
        $query = DB::connection($this->db)->select("select max(periode) as periode from periode where $kode_lokasi ='$kode_lokasi' ");
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
                    $msg = "Transaksi tidak dapat disimpan karena tanggal periode sudah di tutup. Periode Aktif ".$per_awal." s/d ".$per_akhir;
                }else{
                    $msg = "Transaksi tidak dapat disimpan karena periode aktif belum disetting ";
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

    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);

            $res = DB::connection($this->db)->select("
            select a.no_bukti,convert(varchar,a.tanggal,103) as tgl,a.param3 as jenis,a.no_dokumen,a.keterangan,a.nilai1,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, a.tgl_input   
            from trans_m
            where a.periode='".$periode."' and a.kode_lokasi='".$kode_lokasi."' and a.form = 'KBPIU' and a.posted ='F'
            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['jurnal'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['jurnal']= [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
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
            'tanggal' => 'required',
            'jenis' => 'required',
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'akun_piutang' => 'required',
            'kode_cust' => 'required',
            'kode_pp_m' => 'required',
            'nik_app' => 'required',
            'no_piutang' => 'required',
            'kode_akun' => 'required|array',
            'keterangan' => 'required|array',
            'nilai' => 'required|array',
            'dc' => 'required|array',
            'kode_pp' => 'required|array'
            // 'kode_form' => 'required',
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            if ($request->jenis == "BM") {
				$kodeFlag = "035";
				$modul = "KB";
			} 
			else {
				$kodeFlag = "034";
				$modul = "MI";
			}

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-".$request->jenis.substr($periode,2,4).".", "0001");
            $cek = $this->doCekPeriode2($modul,$status_admin,$periode);

            if($cek['status']){

                $gt = DB::connection($this->db)->select("select (a.nilai+a.nilai_ppn-a.nilai_pph) -  isnull(b.bayar,0) as saldo, a.akun_piutang			             
                from piutang_d a						 
                     left join (
                        select no_piutang,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar
                        from piubayar_d
                		where no_bukti <> '".$no_bukti."'
                        group by no_piutang,kode_lokasi) b on a.no_piutang=b.no_piutang and a.kode_lokasi=b.kode_lokasi
                where a.no_piutang='".$request->no_piutang."' and a.kode_lokasi='".$kode_lokasi."'");
                if(count($gt) > 0){
                    $saldo = $gt[0]->saldo;
                }else{
                    $saldo = 0;
                }
                $nilai = 0;
                if (count($request->kode_akun) > 0){
                    for ($j=0;$j < count($request->kode_akun);$j++){
                        if($request->kode_akun != ""){
                            $nilai += floatval($request->nilai[$j]);
                            $tmp = explode(" - ",$request->kode_akun[$j]);
                            $tmp2 = explode(" - ",$request->kode_pp[$j]);
                            $ins[$j] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',".($j+1).",'".$tmp[0]."','".$request->dc[$j]."',".floatval($request->nilai[$j]).",".floatval($request->nilai[$j]).",'".$request->keterangan[$j]."','$modul','KB','IDR',1,'".$tmp2[0]."','-','-','-','-','-','-','-','-')");
                            
                        }
                    }
                }	

                if($nilai <= 0){
                    $sts = false;
                    $msg = "Transaksi tidak valid. Total Nilai tidak boleh nol atau kurang.";
                }else if($nilai > floatval($saldo)){
                    $sts = false;
                    $msg = "Transaksi tidak valid. Total Nilai tidak boleh melebihi saldo.";
                }else{

                    $insj =  DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',0,'".$request->akun_piutang."','C',".$nilai.",".$nilai.",'".$request->deskripsi."','$modul','PIUTANG','IDR',1,'".$request->kode_pp_m."','-','-','-','-','$request->no_piutang','-','-','-')");
                    
                    $insm = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','$modul','KBPIU','F','-','-','".$request->kode_pp_m."','".$request->tanggal."','".$request->no_dokumen."','".$request->deskripsi."','IDR',1,".$nilai.",0,0,'".$request->nik_app."','-','-','$request->akun_piutang','-','-','".$request->kode_cust."','".$request->jenis."','-')");
    
                    $inspiu = DB::connection($this->db)->insert("insert into piubayar_d(no_bukti,no_piutang,kode_lokasi,modul,periode,nik_user,tgl_input,dc,nilai) values ('".$no_bukti."','".$request->no_piutang."','".$kode_lokasi."','".$modul."','".$periode."','$nik',getdate(),'D',".floatval($nilai).")");
                    
                    $msg="sukses";
                    $sts=true;
                }
            }else{
                $msg = $cek['message'];
                $sts = false;
            }    
            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Pelunasan Piutang berhasil disimpan ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['no_bukti'] = "-";
                $success['message'] = $msg;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pelunasan Piutang gagal disimpan ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    
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
            'tanggal' => 'required',
            'jenis' => 'required',
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'akun_piutang' => 'required',
            'kode_cust' => 'required',
            'kode_pp_m' => 'required',
            'nik_app' => 'required',
            'no_piutang' => 'required',
            'kode_akun' => 'required|array',
            'keterangan' => 'required|array',
            'nilai' => 'required|array',
            'dc' => 'required|array',
            'kode_pp' => 'required|array'
            // 'kode_form' => 'required',
        ]);

        DB::connection($this->db)->beginTransaction();
        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            if ($request->jenis == "BM") {
				$kodeFlag = "035";
				$modul = "KB";
			} 
			else {
				$kodeFlag = "034";
				$modul = "MI";
			}

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2($modul,$status_admin,$periode);
            $no_bukti = $request->no_bukti;
            if($cek['status']){

                $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                $del3 = DB::connection($this->db)->table('piubayar_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                $gt = DB::connection($this->db)->select("select (a.nilai+a.nilai_ppn-a.nilai_pph) -  isnull(b.bayar,0) as saldo, a.akun_piutang			             
                from piutang_d a						 
                     left join (
                        select no_piutang,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar
                        from piubayar_d
                		where no_bukti <> '".$no_bukti."'
                        group by no_piutang,kode_lokasi) b on a.no_piutang=b.no_piutang and a.kode_lokasi=b.kode_lokasi
                where a.no_piutang='".$request->no_piutang."' and a.kode_lokasi='".$kode_lokasi."'");
                if(count($gt) > 0){
                    $saldo = $gt[0]->saldo;
                }else{
                    $saldo = 0;
                }
                $nilai = 0;
                if (count($request->kode_akun) > 0){
                    for ($j=0;$j < count($request->kode_akun);$j++){
                        if($request->kode_akun != ""){
                            $nilai += floatval($request->nilai[$j]);
                            $tmp = explode(" - ",$request->kode_akun[$j]);
                            $tmp2 = explode(" - ",$request->kode_pp[$j]);
                            $ins[$j] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',".($j+1).",'".$tmp[0]."','".$request->dc[$j]."',".floatval($request->nilai[$j]).",".floatval($request->nilai[$j]).",'".$request->keterangan[$j]."','$modul','KB','IDR',1,'".$tmp2[0]."','-','-','-','-','-','-','-','-')");
                            
                        }
                    }
                }	

                if($nilai <= 0){
                    $sts = false;
                    $msg = "Transaksi tidak valid. Total Nilai tidak boleh nol atau kurang.";
                }else if($nilai > floatval($saldo)){
                    $sts = false;
                    $msg = "Transaksi tidak valid. Total Nilai tidak boleh melebihi saldo.";
                }else{

                    $insj =  DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',0,'".$request->akun_piutang."','C',".$nilai.",".$nilai.",'".$request->deskripsi."','$modul','PIUTANG','IDR',1,'".$request->kode_pp_m."','-','-','-','-','$request->no_piutang','-','-','-')");
                    
                    $insm = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','$modul','KBPIU','F','-','-','".$request->kode_pp_m."','".$request->tanggal."','".$request->no_dokumen."','".$request->deskripsi."','IDR',1,".$nilai.",0,0,'".$request->nik_app."','-','-','$request->akun_piutang','-','-','".$request->kode_cust."','".$request->jenis."','-')");
    
                    $inspiu = DB::connection($this->db)->insert("insert into piubayar_d(no_bukti,no_piutang,kode_lokasi,modul,periode,nik_user,tgl_input,dc,nilai) values ('".$no_bukti."','".$request->no_piutang."','".$kode_lokasi."','".$modul."','".$periode."','$nik',getdate(),'D',".floatval($nilai).")");
                    
                    $msg="sukses";
                    $sts=true;
                }
               
            }else{
                $msg = $cek['message'];
                $sts = false;
            }    
            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Pelunasan Piutang berhasil diubah ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['no_bukti'] = "-";
                $success['message'] = $msg;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pelunasan Piutang gagal diubah ".$e;
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Jurnal  $Jurnal
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required',
            'periode' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        
        try {
            if($res =  Auth::guard($this->guard)->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
                $status_admin= $res->status_admin;
            }
            $no_bukti = $request->no_bukti;
            $periode = $request->periode;
            $jenis = substr($no_bukti,3,2);
            if ($jenis == "BM") {
				$modul = "KB";
			} 
			else {
				$modul = "MI";
			}

            $cek = $this->doCekPeriode2($modul,$status_admin,$periode);

            if($cek['status']){
                $ins = DB::connection($this->db)->insert("insert into trans_h 
                select no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync,'$nik',getdate()
                from trans_m 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi'  
                ");

                $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                $del3 = DB::connection($this->db)->table('piubayar_d')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Pelunasan Piutang berhasil dihapus";
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = $cek['message'];
            }
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Pelunasan Piutang gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'no_bukti' => 'required'
        ]);
        try {
            
            $no_bukti = $request->no_bukti;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select (a.nilai+a.nilai_ppn-a.nilai_pph) -  isnull(b.bayar,0) as saldo, a.akun_piutang,a.kode_cust,	
            e.param3 as jenis,e.periode,e.no_dokumen,e.tanggal,e.keterangan as ket_kas,e.nik1,e.no_ref1 
            from piutang_d a inner join trans_m e on e.no_ref1 = a.no_piutang and a.kode_lokasi=e.kode_lokasi 
            left join (select no_piutang,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar 
                               from piubayar_d where no_bukti <> '".$request->no_bukti."' group by no_piutang,kode_lokasi) b on a.no_piutang=b.no_piutang and a.kode_lokasi=b.kode_lokasi 
            where e.no_bukti='".$request->no_bukti."' and e.kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select a.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp  
            from trans_j a inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                           inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
            where a.jenis = 'KB' and a.no_bukti = '".$request->no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu");
            $res2 = json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $sql = "select a.no_piutang,a.keterangan from piutang_d a 
                inner join trans_m e on e.no_ref1 = a.no_piutang and a.kode_lokasi=e.kode_lokasi 
                where a.kode_cust='".$res[0]['kode_cust']."' and a.kode_lokasi='".$kode_lokasi."' ";
                $res3 = DB::connection($this->db)->select($sql);
                $success['data_piutang'] = json_decode(json_encode($res3),true);
                $success['status'] = true;
                $success['data'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['detail'] = [];
                $success['data_piutang'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getAkun(Request $request)
    {
        $this->validate($request,[
            'jenis' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_akun)){
                if($request->kode_akun != "-"){
                    $filter .= " and a.kode_akun='$request->kode_akun' ";
                }
            }

            if ($request->jenis == "BM") {
				$kodeFlag = "035";
				$modul = "KB";
			} 
			else {
				$kodeFlag = "034";
				$modul = "MI";
			}

            $res = DB::connection($this->db)->select("select a.kode_akun,a.nama from masakun a 
            inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '$kodeFlag' 					
            where a.block= '0' and a.kode_lokasi ='$kode_lokasi' $filter ");						
            $res= json_decode(json_encode($res),true);
            
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPP(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $filter = "";
            if(isset($request->kode_pp)){
                if($request->kode_pp != "-"){
                    $filter .= " and a.kode_pp='$request->kode_pp' ";
                }
            }

            $res = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);
            $kode_pp = $res[0]['kode_pp'];

            if ($status_admin == "U"){

				$sql = "select a.kode_pp,a.nama from pp a where a.kode_pp='".$kode_pp."'  and a.kode_lokasi = '".$kode_lokasi."' and a.flag_aktif='1' $filter ";
            }else{

                $sql = "select a.kode_pp,a.nama from pp a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='".$nik."' 
                where a.kode_lokasi = '".$kode_lokasi."' and a.flag_aktif='1' $filter";
            }

            $res2 = DB::connection($this->db)->select($sql);						
            $res2= json_decode(json_encode($res2),true);
            
           
            if(count($res2) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
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
                $status_admin = $data->status_admin;
            }

            $filter = "";
            if(isset($request->kode_pp)){
                if($request->kode_pp != "-"){
                    $filter .= " and a.kode_pp='$request->kode_pp' ";
                }
            }

            $res = DB::connection($this->db)->select("select a.flag,b.nama from spro a inner join karyawan b on a.flag=b.nik and a.kode_lokasi=b.kode_lokasi where kode_spro='KBAPP' and a.kode_lokasi='$kode_lokasi'
            ");
            $res = json_decode(json_encode($res),true);
            if(count($res) > 0){
                $nik = $res[0]['flag'];
                $nama = $res[0]['nama'];
            }else{
                $nik = "-";
                $nama = "-";
            }

            $success['nik_default'] = $nik;
            $success['nama_default'] = $nama;
			$sql = "select nik, nama from karyawan where kode_lokasi='".$kode_lokasi."' ";	
            $res2 = DB::connection($this->db)->select($sql);						
            $res2= json_decode(json_encode($res2),true);
            if(count($res2) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getPiutang(Request $request)
    {
        $this->validate($request,[
            'kode_cust' => 'required',
            'periode' => 'required',
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.no_piutang,a.keterangan from piutang_d a left join ( 
                select no_piutang,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar 
                from piubayar_d where no_bukti <> '".$request->no_bukti."' group by no_piutang,kode_lokasi) b on a.no_piutang=b.no_piutang and a.kode_lokasi=b.kode_lokasi 
            where a.kode_cust='".$request->kode_cust."' and a.nilai+a.nilai_ppn-a.nilai_pph>isnull(b.bayar,0) and a.periode<='".$request->periode."' and a.kode_lokasi='$kode_lokasi' ");						
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function getDetailPiutang(Request $request)
    {
        $this->validate($request,[
            'no_piutang' => 'required',
            'no_bukti' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select (a.nilai+a.nilai_ppn-a.nilai_pph) -  isnull(b.bayar,0) as saldo, a.akun_piutang 			             
            from piutang_d a 						 
                 left join (
                          select no_piutang,kode_lokasi,sum(case dc when 'D' then nilai else -nilai end) as bayar 
                          from piubayar_d 
            			  where no_bukti <> '".$request->no_bukti."' 
                          group by no_piutang,kode_lokasi) b on a.no_piutang=b.no_piutang and a.kode_lokasi=b.kode_lokasi 
            where a.no_piutang='".$request->no_piutang."' and a.kode_lokasi='".$kode_lokasi."' ");						
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['data'] = [];
                $success['status'] = false;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

}

