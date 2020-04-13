<?php

namespace App\Http\Controllers\Gl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
    $query = DB::connection('sqlsrv2')->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
    $query = json_decode(json_encode($query),true);
    $kode = $query[0]['id'];
    $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
    return $id;
}

function doCekPeriode2($modul,$status,$periode) {
    try{
        
        $perValid = false;
        if($data =  Auth::guard('admin')->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }
        
        if ($status == "A") {

            $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal2 and per_akhir2";
        }else{

            $strSQL = "select modul from periode_aktif where kode_lokasi ='".$kode_lokasi."'  and modul ='".$modul."' and '".$periode."' between per_awal1 and per_akhir1";
        }

        $auth = DB::connection('sqlsrv2')->select($strSQL);
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            $perValid = true;
        }
        $msg = "ok";
    } catch (\Throwable $e) {		
        $msg= " error " .  $e;
        $perValid = false;
    } 	
    $result['status'] = $perValid;
    $result['message'] = $msg;
    $result['sql'] = $strSQL;
    return $result;		
}

function isUnik($isi,$no_bukti){
    if($data =  Auth::guard('admin')->user()){
        $nik= $data->nik;
        $kode_lokasi= $data->kode_lokasi;
    }

    $strSQL = "select no_bukti from trans_m where no_dokumen = '".$isi."' and kode_lokasi='".$kode_lokasi."' and no_bukti <> '".$no_bukti."' ";

    $auth = DB::connection('sqlsrv2')->select($strSQL);
    $auth = json_decode(json_encode($auth),true);

    if(count($auth) > 0){
        $res['status'] = false;
        $res['no_bukti'] = $auth[0]['no_bukti'];
    }else{
        $res['status'] = true;
    }
    return $res;
}

class JurnalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;


    
    public function index()
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrv2')->select("select no_bukti,tanggal,no_dokumen,keterangan,nilai1 from trans_m where modul='MI' and kode_lokasi='$kode_lokasi'	 
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
                $success['status'] = true;
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

            if($rs =  Auth::guard('admin')->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin=$rs->status_admin;
            }

            $res = DB::connection('sqlsrv2')->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);

            $kode_pp = $res[0]['kode_pp'];
            $data = $request->input('jurnal');
            DB::connection('sqlsrv2')->beginTransaction();

            if(count($data) > 0){
                for($i=0;$i<count($data);$i++){
                    $periode = substr($data[$i]['tanggal'],0,4).substr($data[$i]['tanggal'],5,2);
                    $no_bukti = generateKode("trans_m", "no_bukti", $kode_lokasi."-JU".substr($periode,2,4).".", "0001");

                    $cek = doCekPeriode2($data[$i]['jenis'],$status_admin,$periode);
                    
                    if($cek['status']){
                        $res = isUnik($data[$i]['no_dokumen'],$no_bukti);
                        if($res['status']){
                            
                            $sql = DB::connection('sqlsrv2')->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','MI','MI','F','-','-','".$kode_pp."','".$data[$i]['tanggal']."','".$data[$i]['no_dokumen']."','".$data[$i]['deskripsi']."','IDR',1,".floatval($data[$i]['total_debet']).",0,0,'".$nik."','".$data[$i]['nik_periksa']."','-','-','-','-','-','-','".$data[$i]['jenis']."')");

                            $data2 = $request->input('jurnal')[$i]['detail'];
                            
                            if (count($data2) > 0){
                                for ($j=0;$j < count($data2);$j++){
                                    if($data2[$j]['kode_akun'] != ""){
                                        
                                        $ins = DB::connection('sqlsrv2')->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$data[$i]['no_dokumen']."','".$data[$i]['tanggal']."',".$j.",'".$data2[$j]['kode_akun']."','".$data2[$j]['dc']."',".floatval($data2[$j]['nilai']).",".floatval($data2[$j]['nilai']).",'".$data2[$j]['keterangan']."','MI','".$data[$i]['jenis']."','IDR',1,'".$data2[$j]['kode_pp']."','-','-','-','-','-','-','-','-')");
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
                DB::connection('sqlsrv2')->commit();
                $success['status'] = $sts;
                $success['message'] = "Data Jurnal berhasil disimpan ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection('sqlsrv2')->rollback();
                $success['status'] = $sts;
                $success['message'] = $tmp;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            // DB::connection('sqlsrv2')->rollback();
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

            if($rs =  Auth::guard('admin')->user()){
                $nik= $rs->nik;
                $kode_lokasi= $rs->kode_lokasi;
                $status_admin= $rs->status_admin;
            }

            $res = DB::connection('sqlsrv2')->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);

            $kode_pp = $res[0]['kode_pp'];
            $data = $request->input('jurnal');
            DB::connection('sqlsrv2')->beginTransaction();

            if(count($data) > 0){
                for($i=0;$i<count($data);$i++){
                    $periode=substr($data[$i]['tanggal'],0,4).substr($data[$i]['tanggal'],5,2);
                   
                    $no_bukti = $data[$i]['no_bukti'];

                    $del1 = DB::connection('sqlsrv2')->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                    $del2 = DB::connection('sqlsrv2')->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

                    $cek = doCekPeriode2($data[$i]['jenis'],$status_admin,$periode);
                    
                    if($cek['status']){
                        $res = isUnik($data[$i]['no_dokumen'],$no_bukti);
                        if($res['status']){
                            
                            $sql = DB::connection('sqlsrv2')->insert("insert into trans_m (no_bukti,kode_lokasi,tgl_input,nik_user,periode,modul,form,posted,prog_seb,progress,kode_pp,tanggal,no_dokumen,keterangan,kode_curr,kurs,nilai1,nilai2,nilai3,nik1,nik2,nik3,no_ref1,no_ref2,no_ref3,param1,param2,param3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','MI','MI','F','-','-','".$kode_pp."','".$data[$i]['tanggal']."','".$data[$i]['no_dokumen']."','".$data[$i]['deskripsi']."','IDR',1,".floatval($data[$i]['total_debet']).",0,0,'".$nik."','".$data[$i]['nik_periksa']."','-','-','-','-','-','-','".$data[$i]['jenis']."')");

                            $data2 = $request->input('jurnal')[$i]['detail'];
                            
                            if (count($data2) > 0){
                                for ($j=0;$j < count($data2);$j++){
                                    if($data2[$j]['kode_akun'] != ""){
                                        
                                        $ins = DB::connection('sqlsrv2')->insert("insert into trans_j (no_bukti,kode_lokasi,tgl_input,nik_user,periode,no_dokumen,tanggal,nu,kode_akun,dc,nilai,nilai_curr,keterangan,modul,jenis,kode_curr,kurs,kode_pp,kode_drk,kode_cust,kode_vendor,no_fa,no_selesai,no_ref1,no_ref2,no_ref3) values ('".$no_bukti."','".$kode_lokasi."',getdate(),'".$nik."','".$periode."','".$data[$i]['no_dokumen']."','".$data[$i]['tanggal']."',".$j.",'".$data2[$j]['kode_akun']."','".$data2[$j]['dc']."',".floatval($data2[$j]['nilai']).",".floatval($data2[$j]['nilai']).",'".$data2[$j]['keterangan']."','MI','".$data[$i]['jenis']."','IDR',1,'".$data2[$j]['kode_pp']."','-','-','-','-','-','-','-','-')");
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
                DB::connection('sqlsrv2')->commit();
                $success['status'] = $sts;
                $success['message'] = "Data Jurnal berhasil diubah ";
                return response()->json(['success'=>$success], $this->successStatus); 

            }else{
                DB::connection('sqlsrv2')->rollback();
                $success['status'] = $sts;
                $success['message'] = $msg;
                return response()->json(['success'=>$success], $this->successStatus); 
            }
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
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
    public function destroy($no_bukti)
    {
        DB::connection('sqlsrv2')->beginTransaction();
        
        try {
            if($res =  Auth::guard('admin')->user()){
                $nik= $res->nik;
                $kode_lokasi= $res->kode_lokasi;
            }
            
            $del1 = DB::connection('sqlsrv2')->table('trans_m')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            $del2 = DB::connection('sqlsrv2')->table('trans_j')->where('kode_lokasi', $kode_lokasi)->where('no_bukti', $no_bukti)->delete();

            DB::connection('sqlsrv2')->commit();
            $success['status'] = true;
            $success['message'] = "Data Jurnal berhasil dihapus";
            
            return response()->json(['success'=>$success], $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection('sqlsrv2')->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jurnal gagal dihapus ".$e;
            
            return response()->json(['success'=>$success], $this->successStatus); 
        }	
    }

    

    public function show($no_bukti)
    {
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrv2')->select("select tanggal,no_bukti,periode,keterangan as deskripsi,nilai1,no_dokumen,modul as jenis from trans_m where no_bukti = '".$no_bukti."' and kode_lokasi='".$kode_lokasi."'");						
            $res= json_decode(json_encode($res),true);
            
            $res2 = DB::connection('sqlsrv2')->select("select a.kode_akun,b.nama as nama_akun,a.dc,a.keterangan,a.nilai,a.kode_pp,c.nama as nama_pp 
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
                $success['status'] = true;
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
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection('sqlsrv2')->select("select a.kode_akun,a.nama from masakun a inner join flag_relasi b on a.kode_akun=b.kode_akun and a.kode_lokasi=b.kode_lokasi and b.kode_flag = '034' where a.block= '0' and a.kode_lokasi='$kode_lokasi' ");						
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
                $success['status'] = true;
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
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
                $status_admin = $data->status_admin;
            }

            
            $res = DB::connection('sqlsrv2')->select("select kode_pp from karyawan where kode_lokasi='$kode_lokasi' and nik='$nik'
            ");
            $res = json_decode(json_encode($res),true);
            $kode_pp = $res[0]['kode_pp'];

            if ($status_admin == "U"){

				$sql = "select a.kode_pp,a.nama from pp a where a.kode_pp='".$kode_pp."'  and a.kode_lokasi = '".$kode_lokasi."' and a.flag_aktif='1' ";
            }else{

                $sql = "select a.kode_pp,a.nama from pp a inner join karyawan_pp b on a.kode_pp=b.kode_pp and a.kode_lokasi=b.kode_lokasi and b.nik='".$nik."' 
                where a.kode_lokasi = '".$kode_lokasi."' and a.flag_aktif='1' ";
            }

            $res2 = DB::connection('sqlsrv2')->select($sql);						
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
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }
}
