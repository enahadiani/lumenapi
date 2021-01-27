<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PengajuanRRAController extends Controller
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

    function doCekPeriode2($modul,$status,$periode) {
        try{
            
            $perValid = false;
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $periode_aktif = $this->getPeriodeAktif($kode_lokasi);
            if ($status == "A") {

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between '$periode_aktif' and per_akhir2";
            }else{

                $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between '$periode_aktif' and per_akhir1";
            }

            $auth = DB::connection($this->db)->select($strSQL);
            $auth = json_decode(json_encode($auth),true);
            if(count($auth) > 0){
                $perValid = true;
                $msg = "ok";
            }else{
                $msg = "Periode transaksi yang diperbolehkan $periode_aktif s.d. periode akhir sistem";
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

    function cekAkunKas($kode_akun,$jenis,$dc,$kode_lokasi) {
        try{
            
            $data = DB::connection($this->db)->select("select kode_akun from flag_relasi where kode_flag in ('001','009') and kode_lokasi='".$kode_lokasi."'");
            $data = json_decode(json_encode($data),true);
            if (count($data) > 0){
                $dataJU = $data;
            } 
            $msg = "";
            $akunKB = $posisiDC = false;				
            $k=0;
            $sts = true;
            for ($j=0;$j < count($kode_akun); $j++){
                
                for ($i=0;$i<count($dataJU);$i++){
                    $line = $dataJU[$i];
                    if ($line['kode_akun'] == $kode_akun[$j]) {
                        $akunKB = true;	
                        if ($jenis == "BK" && $dc[$j] == "C"){ $posisiDC = true; }
                        if ($jenis == "BM" && $dc[$j] == "D"){ $posisiDC = true; }
                    }
                }
            }
            if (!$akunKB) {
                $msg .= "Transaksi tidak valid. Akun KasBank tidak ditemukan";
                $sts = false;						
            }
            if (!$posisiDC) {
                $msg .= "Transaksi tidak valid. Akun KasBank tidak sesuai posisi dengan jenis transaksi.(DC)";
                $sts = false;						
            }
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $sts = false;
        } 	
        $result['status'] = $sts;
        $result['message'] = $msg;
        return $result;		
    }

    function isUnik($isi,$no_bukti){
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $strSQL = "select no_bukti from trans_m where no_dokumen = '".$isi."' and kode_lokasi='".$kode_lokasi."' and no_bukti <> '".$no_bukti."' ";

        $auth = DB::connection($this->db)->select($strSQL);
        $auth = json_decode(json_encode($auth),true);

        if(count($auth) > 0){
            $res['status'] = false;
            $res['no_bukti'] = $auth[0]['no_bukti'];
        }else{
            $res['status'] = true;
        }
        return $res;
    }
    
    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("
            select a.no_pdrk,convert(varchar,a.tanggal,103) as tgl,b.no_dokumen,a.keterangan,a.progress,a.tgl_input,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status 
            from rra_pdrk_m a 
            inner join anggaran_m b on a.no_pdrk=b.no_agg and a.kode_lokasi=b.kode_lokasi
            where a.modul = 'MULTI' and a.kode_lokasi='".$kode_lokasi."' and a.progress in ('0','R') order by a.tanggal 
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
            'nik_app' => 'required',
            'donor' => 'required',
            'bulan_terima' => 'required',
            'total_kredit' => 'required',
            'kode_akun' => 'required|array',
            'keterangan' => 'required|array',
            'dc' => 'required|array',
            'nilai' => 'required|array',
            'kode_pp' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $this->generateKode("anggaran_m", "no_agg", $kode_lokasi."-RRA".substr($periode,2,4).".", "0001");

            $ins = DB::connection($this->db)->insert("insert into anggaran_m (no_agg,kode_lokasi,no_dokumen,tanggal,keterangan,tahun,kode_curr,nilai,tgl_input,nik_user,posted,no_del,nik_buat,nik_setuju,jenis) values ('$no_bukti','$kode_lokasi','$request->no_dokumen','$request->tanggal','$request->deskripsi','".substr($request->periode,0,4)."','IDR',".intval($request->donor).",getdate(),'".$nik."','T','-','".$nik."','".$request->nik_app."','RR')");	
            	
            $ins2 = DB::connection($this->db)->insert("insert into rra_pdrk_m(no_pdrk,kode_lokasi,keterangan,kode_pp,kode_bidang,jenis_agg,tanggal,periode,nik_buat,nik_app1,nik_app2,nik_app3,sts_pdrk,justifikasi, nik_user, tgl_input,progress,modul) values ('".$no_bukti."','".$kode_lokasi."','".$request->keterangan."','".$request->kode_pp."','-','-','".$request->tanggal."','".$periode."','".$nik."','".$nik."','".$request->nik_app."','".$request->nik_app."','RRR','-','".$nik."',getdate(),'0','MULTI')");
            
            $per = "";
            if (count($request->kode_akun) > 0){
                for ($i=0;$i < count($request->kode_akun);$i++){
                    $per = substr($periode,0,4).''.$request->bulan[$i];
                    $ins3[$i] = DB::connection($this->db)->insert("insert into rra_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values ('".$no_bukti."','".$kode_lokasi."',".$i.",'".$request->kode_akun[$i]."','".$request->kode_pp[$i]."','".$request->kode_drk[$i]."','".$periode."',".$request->saldo[$i].",".$request->nilai[$i].",'C','-')");

                    $ins4[$i] = DB::connection($this->db)->insert("insert into anggaran_d(no_agg,kode_lokasi,no_urut,kode_pp,kode_akun,kode_drk,volume,periode,nilai_sat,nilai,dc,satuan,nik_user,tgl_input,modul) values ('".$no_bukti."','".$kode_lokasi."',".$i.",'".$request->kode_pp[$i]."','".$request->kode_akun[$i]."','".$request->kode_drk[$i]."',1,'".$periode."',".$request->nilai[$i].",".$request->nilai[$i].",'C','-','".$nik."',getdate(),'RRA')");
                }
            }

            $per2 = substr($periode,0,4).''.$request->bulan_terima;
            $ins5 = DB::connection($this->db)->insert("insert into rra_pdrk_d(no_pdrk,kode_lokasi,no_urut,kode_akun,kode_pp,kode_drk,periode,saldo,nilai,dc,target) values ('".$no_bukti."','".$kode_lokasi."',999,'".$request->bulan."','".$request->kode_pp_terima."','".$request->kode_drk_terima."','".$per2."',0,".floatval($request->nilai_terima).",'D','-')");

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Kas Bank berhasil disimpan ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection($this->db)->rollback();
                $success['status'] = $sts;
                $success['no_bukti'] = "-";
                $success['message'] = $tmp;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            // DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Kas Bank gagal disimpan ".$e;
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
            'no_dokumen' => 'required',
            'jenis' => 'required',
            'status' => 'required',
            'tanggal' => 'required',
            'deskripsi' => 'required',
            'total_debet' => 'required',
            'total_kredit' => 'required',
            'kode_akun' => 'required|array',
            'keterangan' => 'required|array',
            'dc' => 'required|array',
            'nilai' => 'required|array',
            'kode_pp' => 'required|array'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }

            
            $res = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);

            $kode_pp = $res[0]['kode_pp'];
            DB::connection($this->db)->beginTransaction();

            $periode = substr($request->tanggal,0,4).substr($request->tanggal,5,2);
            $no_bukti = $request->no_bukti;
            $cek = $this->doCekPeriode2('KB',$status_admin,$periode);

            if($cek['status']){
                $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();
                $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                $res = $this->isUnik($request->no_dokumen,$no_bukti);
                if($res['status']){
                    $cekAkun = $this->cekAkunKas($request->kode_akun,$request->jenis,$request->dc,$kode_lokasi);
                    if($cekAkun['status']){
                        $nilai = 0;
                        if (count($request->kode_akun) > 0){
                            for ($j=0;$j < count($request->kode_akun);$j++){
                                if($request->kode_akun != ""){
                                    if($request->dc[$j] == "D"){
                                        $nilai += floatval($request->nilai[$j]);
                                    }
                                    $ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$request->no_dokumen."','".$request->tanggal."',".$j.",'".$request->kode_akun[$j]."','".$request->dc[$j]."',".floatval($request->nilai[$j]).",".floatval($request->nilai[$j]).",'".$request->keterangan[$j]."','KB','".$request->jenis."','IDR',1,'".$request->kode_pp[$j]."','-','-','-','-','-','-','-','-')");
                                    
                                }
                            }
                        }	
                        
                        $sql = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','KB','KB','F','-','-','".$kode_pp."','".$request->tanggal."','".$request->no_dokumen."','".$request->deskripsi."','IDR',1,".$nilai.",0,0,'".$nik."','-','-','-','-','-','-','".$request->status."','".$request->jenis."')");
                        
                        $tmp="sukses";
                        $sts=true;
                    }else{
                        $tmp= $cekAkun['message'];
                        $sts=false;
                    }
                }else{
                    $tmp = "Transaksi tidak valid. No Dokumen '".$request->no_dokumen."' sudah terpakai di No Bukti '".$res['no_bukti']."' .";
                    $sts = false;
                }
            }else{
                $tmp = "Periode transaksi modul tidak valid (KB - LOCKED). Hubungi Administrator Sistem . ".$cek['message'];
                $sts = false;
            }         


            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Kas Bank berhasil disimpan ";
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
            $success['message'] = "Data KasBank gagal diubah ".$e;
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
        DB::connection($this->db)->beginTransaction();
        
        try {
            if($res =  Auth::guard($this->guard)->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }
            $no_bukti = $request->no_bukti;
            
            $ins = DB::connection($this->db)->insert("insert into trans_h 
            select no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3,due_date,file_dok,id_sync,'$nik',getdate()
            from trans_m 
            where no_bukti='$no_bukti' and kode_lokasi='$kode_lokasi'  
            ");

            $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data KasBank berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data KasBank gagal dihapus ".$e;
            
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

            $res = DB::connection($this->db)->select("select a.tanggal,a.no_bukti,a.periode,keterangan as deskripsi,a.nilai1,a.no_dokumen,a.param3 as jenis,a.param2 as status
            from trans_m a
            where a.no_bukti = '".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection($this->db)->select("select a.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp 
                    from trans_j a 
                    inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi 
                    inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
                    where a.no_bukti = '".$no_bukti."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu");
            $res2= json_decode(json_encode($res2),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['jurnal'] = $res;
                $success['detail'] = $res2;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
                $success['jurnal'] = [];
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

    public function getAkun()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            // $res = DB::connection($this->db)->select("select a.kode_akun,a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '034' where a.block= '0' and a.kode_lokasi='$kode_lokasi' ");
            $res = DB::connection($this->db)->select("select a.kode_akun,a.nama from masakun a where a.block= '0' and a.kode_lokasi='$kode_lokasi' ");						
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

