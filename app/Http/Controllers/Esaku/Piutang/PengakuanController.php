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

class PengakuanController extends Controller
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
            select distinct a.no_piutang,convert(varchar,a.tanggal,103) as tgl,a.kode_cust+'-'+b.nama as cust,a.no_dokumen,a.keterangan,a.nilai+a.nilai_ppn as total,case when datediff(minute,a.tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, a.tgl_input   
            from piutang_d a inner join cust b on a.kode_cust=b.kode_cust and a.kode_lokasi=b.kode_lokasi 
            	inner join trans_m d on a.no_piutang = d.no_bukti and a.kode_lokasi=d.kode_lokasi and d.posted ='F' 
                left join piubayar_d c on a.no_piutang=c.no_piutang and a.kode_lokasi=c.kode_lokasi 
            where c.no_bukti is null and a.periode='".$periode_aktif."' and a.kode_lokasi='$kode_lokasi' 
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
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'akun_piutang' => 'required',
            'kode_cust' => 'required',
            'kode_pp_m' => 'required',
            'faktur_pajak' => 'required',
            'ppn' => 'required',
            'nik_app' => 'required',
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

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-PU".substr($periode,2,4).".", "0001");
            $cek = $this->doCekPeriode2('AR',$status_admin,$periode);

            if($cek['status']){

                $flagGarFree = "0"; 
                $akunPPN = "-";
                $get = DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('HUTPPN') and kode_lokasi = '".$kode_lokasi."'");			
                if (count($get) > 0){
                   	if ($get[0]->kode_spro == "HUTPPN") {
                          $akunPPN = $get[0]->flag;			
                    }
                }	

                $nilai = 0;
                if (count($request->kode_akun) > 0){
                    for ($j=0;$j < count($request->kode_akun);$j++){
                        if($request->kode_akun != ""){
                            $nilai += floatval($request->nilai[$j]);
                            $tmp = explode(" - ",$request->kode_akun[$j]);
                            $tmp2 = explode(" - ",$request->kode_pp[$j]);
                            $ins[$j] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',".($j+1).",'".$tmp[0]."','".$request->dc[$j]."',".floatval($request->nilai[$j]).",".floatval($request->nilai[$j]).",'".$request->keterangan[$j]."','AR','PDPT','IDR',1,'".$tmp2[0]."','-','-','-','-','-','-','-','-')");
                            
                        }
                    }
                }	
                
                $insj =  DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',0,'".$request->akun_piutang."','D',".$nilai.",".$nilai.",'".$request->deskripsi."','AR','PIUTANG','IDR',1,'".$request->kode_pp_m."','-','-','-','-','-','-','-','-')");

                if (floatval($request->ppn) != 0) {
                    $insppn = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',999,'".$akunPPN."','C',".floatval($request->ppn).",".floatval($request->ppn).",'".$request->deskripsi."','AR','HUTPPN','IDR',1,'".$request->kode_pp_m."','-','-','-','-','-','-','-','-')");	
                }
                
                $insm = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','AR','AKRU','F','-','-','".$request->kode_pp_m."','".$request->tanggal."','".$request->no_dokumen."','".$request->deskripsi."','IDR',1,".$nilai.",".floatval($request->ppn).",0,'".$request->nik_app."','-','-','$request->faktur_pajak','-','-','".$request->kode_cust."','".$request->akun_piutang."','-')");

                $inspiu = DB::connection($this->db)->insert("insert into piutang_d(no_piutang,kode_lokasi,no_dokumen,tanggal,keterangan,kode_project,kode_cust,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_piutang,nilai_ppn,nilai_pph,no_fp) values ('".$no_bukti."','".$kode_lokasi."','".$request->no_dokumen."','".$request->tanggal."','".$request->deskripsi."','-','".$request->kode_cust."','IDR',1,'".$request->kode_pp_m."',".$nilai.",'".$periode."','".$nik."',getdate(),'".$request->akun_piutang."',".floatval($request->ppn).",0,'".$request->faktur_pajak."')");
                
                $tmp="sukses";
                $sts=true;
               
            }else{
                $tmp = $cek['message'];
                $sts = false;
            }    
            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Piutang berhasil disimpan ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['no_bukti'] = "-";
                $success['message'] = $tmp;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Piutang gagal disimpan ".$e;
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
            'no_dokumen' => 'required',
            'deskripsi' => 'required',
            'akun_piutang' => 'required',
            'kode_cust' => 'required',
            'kode_pp_m' => 'required',
            'faktur_pajak' => 'required',
            'ppn' => 'required',
            'nik_app' => 'required',
            'kode_akun' => 'required|array',
            'keterangan' => 'required|array',
            'nilai' => 'required|array',
            'dc' => 'required|array',
            'kode_pp' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $cek = $this->doCekPeriode2('AR',$status_admin,$periode);

            $no_bukti = $request->no_bukti;
            if($cek['status']){

                $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                $del3 = DB::connection($this->db)->table('piutang_d')->where('kode_lokasi', $kode_lokasi)->where('no_piutang', $no_bukti)->delete();
                $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                $flagGarFree = "0"; 
                $akunPPN = "-";
                $get = DB::connection($this->db)->select("select kode_spro,flag from spro where kode_spro in ('HUTPPN') and kode_lokasi = '".$kode_lokasi."'");			
                if (count($get) > 0){
                   	if ($get[0]->kode_spro == "HUTPPN") {
                          $akunPPN = $get[0]->flag;			
                    }
                }	

                $nilai = 0;
                if (count($request->kode_akun) > 0){
                    for ($j=0;$j < count($request->kode_akun);$j++){
                        if($request->kode_akun != ""){
                            $nilai += floatval($request->nilai[$j]);
                            $tmp = explode(" - ",$request->kode_akun[$j]);
                            $tmp2 = explode(" - ",$request->kode_pp[$j]);
                            $ins[$j] = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',".($j+1).",'".$tmp[0]."','".$request->dc[$j]."',".floatval($request->nilai[$j]).",".floatval($request->nilai[$j]).",'".$request->keterangan[$j]."','AR','PDPT','IDR',1,'".$tmp2[0]."','-','-','-','-','-','-','-','-')");
                            
                        }
                    }
                }	
                
                $insj =  DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',0,'".$request->akun_piutang."','D',".$nilai.",".$nilai.",'".$request->deskripsi."','AR','PIUTANG','IDR',1,'".$request->kode_pp_m."','-','-','-','-','-','-','-','-')");

                if (floatval($request->ppn) != 0) {
                    $insppn = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',999,'".$akunPPN."','C',".floatval($request->ppn).",".floatval($request->ppn).",'".$request->deskripsi."','AR','HUTPPN','IDR',1,'".$request->kode_pp_m."','-','-','-','-','-','-','-','-')");	
                }
                
                $insm = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','AR','AKRU','F','-','-','".$request->kode_pp_m."','".$request->tanggal."','".$request->no_dokumen."','".$request->deskripsi."','IDR',1,".$nilai.",".floatval($request->ppn).",0,'".$request->nik_app."','-','-','$request->faktur_pajak','-','-','".$request->kode_cust."','".$request->akun_piutang."','-')");

                $inspiu = DB::connection($this->db)->insert("insert into piutang_d(no_piutang,kode_lokasi,no_dokumen,tanggal,keterangan,kode_project,kode_cust,kode_curr,kurs,kode_pp,nilai,periode,nik_user,tgl_input,akun_piutang,nilai_ppn,nilai_pph,no_fp) values ('".$no_bukti."','".$kode_lokasi."','".$request->no_dokumen."','".$request->tanggal."','".$request->deskripsi."','-','".$request->kode_cust."','IDR',1,'".$request->kode_pp_m."',".$nilai.",'".$periode."','".$nik."',getdate(),'".$request->akun_piutang."',".floatval($request->ppn).",0,'".$request->faktur_pajak."')");
                
                $tmp="sukses";
                $sts=true;
               
            }else{
                $tmp = $cek['message'];
                $sts = false;
            }    
            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Piutang berhasil diubah ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['no_bukti'] = "-";
                $success['message'] = $tmp;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Piutang gagal diubah ".$e;
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
            $cek = $this->doCekPeriode2('AR',$status_admin,$periode);

            if($cek['status']){
                $ins = DB::connection($this->db)->insert("insert into trans_h 
                select no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync,'$nik',getdate()
                from trans_m 
                where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi'  
                ");

                $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                $del3 = DB::connection($this->db)->table('piutang_d')->where('kode_lokasi', $kode_lokasi)->where('no_piutang', $no_bukti)->delete();

                $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                DB::connection($this->db)->commit();
                $success['status'] = true;
                $success['message'] = "Data Piutang berhasil dihapus";
            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = false;
                $success['message'] = $cek['message'];
            }
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Piutang gagal dihapus ".$e;
            
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

            $res = DB::connection($this->db)->select("select a.tanggal,a.periode,a.no_dokumen,a.keterangan,a.kode_pp,a.akun_piutang,d.nik1 as nik_app,a.kode_cust,c.nama as nama_cust,a.nilai_ppn,a.no_fp 
            from piutang_d a 
            inner join cust c on a.kode_cust=c.kode_cust and a.kode_lokasi=c.kode_lokasi 
            inner join trans_m d on a.no_piutang=d.no_bukti and a.kode_lokasi=d.kode_lokasi 
            where a.no_piutang='".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select a.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp 
            from trans_j a inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                         	 inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 												 
            where a.jenis = 'PDPT' and a.no_bukti = '".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu");
            $res2= json_decode(json_encode($res2),true);

            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
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
            $res = DB::connection($this->db)->select("select a.kode_akun,a.nama from masakun a 
            inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '034' 					
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

    public function getAkunPiutang(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_akun)){
                if($request->kode_akun != "-"){
                    $filter .= " and a.kode_akun='$request->kode_akun' ";
                }
            }

            $res = DB::connection($this->db)->select("select a.kode_akun, a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi where b.kode_flag = '003' and a.kode_lokasi='".$kode_lokasi."' $filter ");					
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['daftar'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
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
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->nik)){
                if($request->nik != "-"){
                    $filter .= " and nik='$request->nik' ";
                }
            }

            $res = DB::connection($this->db)->select("select nik, nama from karyawan where flag_aktif='1' and kode_lokasi='".$kode_lokasi."' $filter ");					
            $res= json_decode(json_encode($res),true);
           
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['daftar'] = $res;
                $success['message'] = "Success!";
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['daftar'] = [];
                $success['status'] = false;
                return response()->json($success, $this->successStatus);
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

}

