<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Imports\JurnalImport;
use App\Exports\JurnalExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage; 
use App\JurnalTmp;

class JurnalController extends Controller
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

    function doCekPeriode2($modul,$status,$periode) {
        try{
            
            // $perValid = false;
            // if($data =  Auth::guard($this->guard)->user()){
            //     $nik= $data->nik;
            //     $kode_lokasi= $data->kode_lokasi;
            // }
            
            // if ($status == "A") {

            //     $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal2 and per_akhir2";
            // }else{

            //     $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal1 and per_akhir1";
            // }

            // $auth = DB::connection($this->db)->select($strSQL);
            // $auth = json_decode(json_encode($auth),true);
            // if(count($auth) > 0){
                $perValid = true;
            // }
            $msg = "ok";
        } catch (\Throwable $e) {		
            $msg= " error " .  $e;
            $perValid = false;
        } 	
        $result['status'] = $perValid;
        $result['message'] = $msg;
        // $result['sql'] = $strSQL;
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
    
    public function index()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select no_bukti,tanggal,no_dokumen,keterangan,nilai1,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status, tgl_input  from trans_m where modul='MI' and kode_lokasi='$kode_lokasi'	 
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
            'jurnal.*.no_dokumen' => 'required',
            'jurnal.*.tanggal' => 'required',
            'jurnal.*.jenis' => 'required',
            'jurnal.*.deskripsi' => 'required',
            'jurnal.*.total_debet' => 'required',
            'jurnal.*.total_kredit' => 'required',
            'jurnal.*.nik_periksa' => 'required',
            'jurnal.*.detail.*.kode_akun' => 'required',
            'jurnal.*.detail.*.keterangan' => 'required',
            'jurnal.*.detail.*.dc' => 'required',
            'jurnal.*.detail.*.nilai' => 'required',
            'jurnal.*.detail.*.kode_pp' => 'required'
        ]);

        try {

            if($rs =  Auth::guard($this->guard)->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $res = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);

            $kode_pp = $res[0]['kode_pp'];
            $data = $request->input('jurnal');
            DB::connection($this->db)->beginTransaction();

            if(count($data) > 0){
                for($i=0;$i<count($data);$i++){
                    $periode = substr($data[$i]['tanggal'],0,4).substr($data[$i]['tanggal'],5,2);
                    $no_bukti = $this->generateKode("trans_m", "no_bukti", $kode_lokasi."-JU".substr($periode,2,4).".", "0001");

                    $cek = $this->doCekPeriode2($data[$i]['jenis'],$status_admin,$periode);
                    
                    if($cek['status']){
                        $res = $this->isUnik($data[$i]['no_dokumen'],$no_bukti);
                        if($res['status']){
                            
                            $sql = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','MI','MI','F','-','-','".$kode_pp."','".$data[$i]['tanggal']."','".$data[$i]['no_dokumen']."','".$data[$i]['deskripsi']."','IDR',1,".floatval($data[$i]['total_debet']).",0,0,'".$nik."','".$data[$i]['nik_periksa']."','-','-','-','-','-','-','".$data[$i]['jenis']."')");

                            $data2 = $request->input('jurnal')[$i]['detail'];
                            
                            if (count($data2) > 0){
                                for ($j=0;$j < count($data2);$j++){
                                    if($data2[$j]['kode_akun'] != ""){
                                        
                                        $ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$data[$i]['no_dokumen']."','".$data[$i]['tanggal']."',".$j.",'".$data2[$j]['kode_akun']."','".$data2[$j]['dc']."',".floatval($data2[$j]['nilai']).",".floatval($data2[$j]['nilai']).",'".$data2[$j]['keterangan']."','MI','".$data[$i]['jenis']."','IDR',1,'".$data2[$j]['kode_pp']."','-','-','-','-','-','-','-','-')");
                                    }
                                }
                            }	

                            $tmp="sukses";
                            $sts=true;
                        
                        }else{
                            $tmp = "Transaksi tidak valid. No Dokumen '".$data[$i]['no_dokumen']."' sudah terpakai di No Bukti '".$res['no_bukti']."' .";
                            $sts = false;
                        }
                    }else{
                        $tmp = "Periode transaksi modul tidak valid (MI - LOCKED). Hubungi Administrator Sistem .";
                        $sts = false;
                    }         

                }
            }

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Jurnal berhasil disimpan ";
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
            $success['message'] = "Data Jurnal gagal disimpan ".$e;
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
            'jurnal.*.no_bukti' => 'required',
            'jurnal.*.no_dokumen' => 'required',
            'jurnal.*.tanggal' => 'required',
            'jurnal.*.jenis' => 'required',
            'jurnal.*.deskripsi' => 'required',
            'jurnal.*.total_debet' => 'required',
            'jurnal.*.total_kredit' => 'required',
            'jurnal.*.nik_periksa' => 'required',
            'jurnal.*.detail.*.kode_akun' => 'required',
            'jurnal.*.detail.*.keterangan' => 'required',
            'jurnal.*.detail.*.dc' => 'required',
            'jurnal.*.detail.*.nilai' => 'required',
            'jurnal.*.detail.*.kode_pp' => 'required'
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
            $data = $request->input('jurnal');
            DB::connection($this->db)->beginTransaction();

            if(count($data) > 0){
                for($i=0;$i<count($data);$i++){
                    $periode=substr($data[$i]['tanggal'],0,4).substr($data[$i]['tanggal'],5,2);
                   
                    $no_bukti = $data[$i]['no_bukti'];

                    $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                    $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                    $cek = $this->doCekPeriode2($data[$i]['jenis'],$status_admin,$periode);
                    
                    if($cek['status']){
                        $res = $this->isUnik($data[$i]['no_dokumen'],$no_bukti);
                        if($res['status']){
                            
                            $sql = DB::connection($this->db)->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','MI','MI','F','-','-','".$kode_pp."','".$data[$i]['tanggal']."','".$data[$i]['no_dokumen']."','".$data[$i]['deskripsi']."','IDR',1,".floatval($data[$i]['total_debet']).",0,0,'".$nik."','".$data[$i]['nik_periksa']."','-','-','-','-','-','-','".$data[$i]['jenis']."')");

                            $data2 = $request->input('jurnal')[$i]['detail'];
                            
                            if (count($data2) > 0){
                                for ($j=0;$j < count($data2);$j++){
                                    if($data2[$j]['kode_akun'] != ""){
                                        
                                        $ins = DB::connection($this->db)->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$data[$i]['no_dokumen']."','".$data[$i]['tanggal']."',".$j.",'".$data2[$j]['kode_akun']."','".$data2[$j]['dc']."',".floatval($data2[$j]['nilai']).",".floatval($data2[$j]['nilai']).",'".$data2[$j]['keterangan']."','MI','".$data[$i]['jenis']."','IDR',1,'".$data2[$j]['kode_pp']."','-','-','-','-','-','-','-','-')");
                                    }
                                }
                            }	

                            $tmp="sukses";
                            $sts=true;
                        
                        }else{
                            $tmp = "Transaksi tidak valid. No Dokumen '".$data[$i]['no_dokumen']."' sudah terpakai di No Bukti '".$res['no_bukti']."' .";
                            $sts = false;
                        }
                    }else{
                        $tmp = "Periode transaksi modul tidak valid (MI - LOCKED). Hubungi Administrator Sistem .";
                        $sts = false;
                    }         

                }
            }

            if($sts){
                DB::connection($this->db)->commit();
                $success['status'] = $sts;
                $success['no_bukti'] = $no_bukti;
                $success['message'] = "Data Jurnal berhasil diubah ";
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
            $success['message'] = "Data Jurnal gagal diubah ".$e;
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
            
            $del1 = DB::connection($this->db)->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            $del2 = DB::connection($this->db)->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jurnal berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal gagal dihapus ".$e;
            
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

            $res = DB::connection($this->db)->select("select a.tanggal,a.no_bukti,a.periode,keterangan as deskripsi,a.nilai1,a.no_dokumen,a.modul as jenis,a.nik2 as nik_periksa,b.nama as nama_periksa 
            from trans_m a
            inner join karyawan b on a.nik2=b.nik and a.kode_lokasi=b.kode_lokasi
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

    public function getNIKPeriksa()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select nik, nama from karyawan where kode_lokasi='".$kode_lokasi."' and flag_aktif='1'");						
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

    public function getNIKPeriksaByNIK($nik)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik_user= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select nik, nama from karyawan where kode_lokasi='".$kode_lokasi."' and flag_aktif='1' and nik='$nik' ");						
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

    public function getPP()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            
            $res = DB::connection($this->db)->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);
            $kode_pp = $res[0]['kode_pp'];

            if ($status_admin == "U"){

				$sql = "select a.kode_pp,a.nama from pp a where a.kode_pp='".$kode_pp."'  and a.kode_lokasi = '".$kode_lokasi."' and a.flag_aktif='1' ";
            }else{

                $sql = "select a.kode_pp,a.nama from pp a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='".$nik."' 
                where a.kode_lokasi = '".$kode_lokasi."' and a.flag_aktif='1' ";
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

    public function getPeriodeJurnal()
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            $sql = "select distinct a.periode from trans_m a  where a.kode_lokasi = '".$kode_lokasi."' and a.modul='MI'";

            $res = DB::connection($this->db)->select($sql);						
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

    
    public function validateJurnal($kode_akun,$kode_pp,$dc,$ket,$nilai,$kode_lokasi){
        $keterangan = "";
        $auth = DB::connection($this->db)->select("select kode_akun from masakun where kode_akun='$kode_akun' and kode_lokasi='$kode_lokasi'
        ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode Akun $kode_akun tidak valid. ";
        }

        $auth2 = DB::connection($this->db)->select("select kode_pp from pp where kode_pp='$kode_pp' and kode_lokasi='$kode_lokasi'
        ");
        $auth2 = json_decode(json_encode($auth2),true);
        if(count($auth2) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Kode PP $kode_pp tidak valid. ";
        }

        if(floatval($nilai) > 0){
            $keterangan .= "";
        }else{
            $keterangan .= "Nilai tidak valid. ";
        }

        if($ket != ""){
            $keterangan .= "";
        }else{
            $keterangan .= "Keterangan tidak valid. ";
        }

        if($dc == "D" || $dc == "C"){
            $keterangan .= "";
        }else{
            $keterangan .= "DC $dc tidak valid. ";
        }

        return $keterangan;
        // return $keterangan;

    }


    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,xls,xlsx',
            'nik_user' => 'required'
        ]);

        DB::connection($this->db)->beginTransaction();
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del1 = DB::connection($this->db)->table('jurnal_tmp')->where('kode_lokasi', $kode_lokasi)->where('nik_user', $request->nik_user)->delete();

            // menangkap file excel
            $file = $request->file('file');
    
            // membuat nama file unik
            $nama_file = rand().$file->getClientOriginalName();

            Storage::disk('local')->put($nama_file,file_get_contents($file));
            // $excel = Excel::import(new JurnalImport($request->nik_user), $nama_file);
            $dt = Excel::toArray(new JurnalImport($request->nik_user),$nama_file);
            $excel = $dt[0];
            $x = array();
            $status_validate = true;
            $no=1;
            foreach($excel as $row){
                if($row[0] != ""){
                    $ket = $this->validateJurnal(strval($row[0]),strval($row[4]),strval($row[1]),strval($row[2]),floatval($row[3]),$kode_lokasi);
                    if($ket != ""){
                        $sts = 0;
                        $status_validate = false;
                    }else{
                        $sts = 1;
                    }
                    $x[] = JurnalTmp::create([
                        'kode_akun' => strval($row[0]),
                        'dc' => strval($row[1]),
                        'keterangan' => strval($row[2]),
                        'nilai' => floatval($row[3]),
                        'kode_pp' => strval($row[4]),
                        'kode_lokasi' => $kode_lokasi,
                        'nik_user' => $request->nik_user,
                        'tgl_input' => date('Y-m-d H:i:s'),
                        'status' => $sts,
                        'ket_status' => $ket,
                        'nu' => $no
                    ]);
                    $no++;
                }
            }
            
            DB::connection($this->db)->commit();
            Storage::disk('local')->delete($nama_file);
            if($status_validate){
                $msg = "File berhasil diupload!";
            }else{
                $msg = "Ada error!";
            }
            
            $success['status'] = true;
            $success['validate'] = $status_validate;
            $success['message'] = $msg;
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function export(Request $request) 
    {
        $nik_user = $request->nik_user;
        $kode_lokasi = $request->kode_lokasi;
        $nik = $request->nik;
        date_default_timezone_set("Asia/Bangkok");
        return Excel::download(new JurnalExport($nik_user,$kode_lokasi), 'Jurnal_'.$nik.'_'.$kode_lokasi.'_'.date('dmy').'_'.date('Hi').'.xlsx');
    }

    public function getJurnalTmp(Request $request)
    {
        
        $nik_user = $request->nik_user;

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->db)->select("select a.kode_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,b.nama as nama_akun,c.nama as nama_pp 
            from jurnal_tmp a
            inner join masakun b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi
            inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            where a.nik_user = '".$nik_user."' and a.kode_lokasi='".$kode_lokasi."' order by a.nu");
            $res= json_decode(json_encode($res),true);

            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['detail'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!"; 
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
}

