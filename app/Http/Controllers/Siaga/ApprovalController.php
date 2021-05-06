<?php

namespace App\Http\Controllers\Siaga;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Log;

class ApprovalController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public $successStatus = 200;
    public $db = 'dbsiaga';
    public $guard = 'siaga';

    function generateKode($tabel, $kolom_acuan, $prefix, $str_format){
        $query = DB::connection($this->db)->select("select right(max($kolom_acuan), ".strlen($str_format).")+1 as id from $tabel where $kolom_acuan like '$prefix%'");
        $query = json_decode(json_encode($query),true);
        $kode = $query[0]['id'];
        $id = $prefix.str_pad($kode, strlen($str_format), $str_format, STR_PAD_LEFT);
        return $id;
    }

    public function pengajuanVP(Request $request){

        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.kode_lokasi,a.kode_pp+' - '+c.nama as pp,a.no_pdrk as no_bukti,b.no_dokumen,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.sts_pdrk as jenis 
            from rra_pdrk_m a 
                inner join anggaran_m b on a.no_pdrk=b.no_agg and a.kode_lokasi=b.kode_lokasi 
                inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
            where a.modul = 'MULTI' and a.progress in ('8') and a.nik_app1='$nik' 
            ";

            $aju = DB::connection($this->db)->select($sql);
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']= [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Internal Server Error";
            return response()->json($success, 500);
        }
    }

    public function pengajuanUnit(Request $request){

        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select a.kode_lokasi,a.kode_pp+' - '+c.nama as pp,a.no_pdrk as no_bukti,b.no_dokumen,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.sts_pdrk as jenis 
            from rra_pdrk_m a 
                inner join anggaran_m b on a.no_pdrk=b.no_agg and a.kode_lokasi=b.kode_lokasi 
                inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
            where a.modul = 'MULTI' and a.progress in ('9') and a.nik_app2='$nik' ";

            $aju = DB::connection($this->db)->select($sql);
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']= [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            $Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Internal Server Error";
            return response()->json($success, $this->successStatus);
        }
    }

    public function pengajuanBudget(Request $request){

        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $aju = DB::connection($this->db)->select("select a.kode_lokasi,a.kode_pp+' - '+c.nama as pp,a.no_pdrk as no_bukti,b.no_dokumen,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.sts_pdrk as jenis 
            from rra_pdrk_m a 
                inner join anggaran_m b on a.no_pdrk=b.no_agg and a.kode_lokasi=b.kode_lokasi 
                inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi 
            where a.modul = 'MULTI' and a.progress in ('0','Z') and a.no_pdrk like '%-RRA%' and a.nik_app3='$nik' ");
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']= [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Internal Server Error";
            return response()->json($success, $this->successStatus);
        }
    }

    public function pengajuanDir(Request $request){

        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $aju = DB::connection($this->db)->select("select a.kode_lokasi,a.kode_pp+' - '+c.nama as pp,a.no_pdrk as no_bukti,b.no_dokumen,convert(varchar,a.tanggal,103) as tanggal,a.keterangan,a.sts_pdrk as jenis 
            from rra_pdrk_m a
                inner join anggaran_m b on a.no_pdrk=b.no_agg and a.kode_lokasi=b.kode_lokasi
                inner join pp c on a.kode_pp=c.kode_pp and a.kode_lokasi=c.kode_lokasi
            where a.modul = 'MULTI' and a.progress='1' and a.nik_app4 = '$nik'
            ");
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']= [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Internal Server Error";
            return response()->json($success, $this->successStatus);
        }
    }

    public function detail(Request $request){

        $this->validate($request,[
            'no_pdrk' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select substring(a.periode,5,2) as bulan,a.kode_akun,c.nama as nama_akun,a.nilai,case a.dc when 'D' then 'PENERIMA' else 'PEMBERI' end as jenis  
            from rra_pdrk_d a 
            		inner join masakun c on a.kode_akun=c.kode_akun and a.kode_lokasi=c.kode_lokasi 					 
            where a.no_pdrk = '".$request->no_pdrk."'  order by a.dc ";

            $det = DB::connection($this->db)->select($sql);
            $det = json_decode(json_encode($det),true);
            
            if(count($det) > 0){ //mengecek apakah data kosong atau tidak
                
                $success['status'] = true;
                $success['data'] = $det;
                $success['message'] = "Success!";
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']= [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Internal Server Error";
            return response()->json($success, $this->successStatus);
        }
    }

    public function approvalVP(Request $request)
    {
        
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $this->validate($request, [
            'status' => 'required|in:APPROVE,RETURN',
            'no_pdrk' => 'required',
            'keterangan' => 'required'
        ]);   
        
        date_default_timezone_set('Asia/Jakarta');             
        $str_format="0000";
        $tanggal=date('Y-m-d');
        $periode =substr($tanggal,0,4).substr($tanggal,5,2);
        $no_bukti = $this->generateKode("rra_app_m", "no_app", $kode_lokasi."-RRA".substr($periode,2,4).".", "0001");

        DB::connection($this->db)->beginTransaction();
        
        try {

            if ($request->input('status') == "APPROVE") {
                $vProg = "9";
            } else {
                $vProg = "V";	
                $del = DB::connection($this->db)->table('anggaran_d')->where('no_agg',$request->no_pdrk)->delete();
            }    
            
            $ins = DB::connection($this->db)->insert("insert into rra_app_m(no_app, kode_lokasi,tanggal,keterangan,modul,periode,no_del,nik_buat,nik_app,nik_user,tgl_input,jenis_form) values ('$no_bukti','$kode_lokasi',getdate(),'$request->keterangan','PUSAT','$periode','-','$nik','$nik','$nik',getdate(),'APPVP')");
            
            $insdet = DB::connection($this->db)->insert("insert into rra_app_d(no_app,modul,kode_lokasi,no_bukti,kode_lokbukti,sts_pdrk,catatan,status) values ('$no_bukti','PUSAT','$kode_lokasi','$request->no_pdrk','$kode_lokasi','RRR','-','$request->status') ");
            
            //---------------- flag bukti		
            $upd = DB::connection($this->db)->table('rra_pdrk_m')
            ->where('no_pdrk', $request->no_pdrk)       
            ->where('kode_lokasi', $kode_lokasi)
            ->update(['progress' => $vProg]);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['id'] = $no_bukti;
            $success['message'] = "Data Approval berhasil disimpan";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['id']= "-";
            $success['message'] = "Data Approval gagal disimpan. Internal Server Error.".$e;
            return response()->json($success, $this->successStatus);
        }																
    }

    public function approvalUnit(Request $request)
    {
        
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $this->validate($request, [
            'status' => 'required|in:APPROVE,RETURN',
            'no_pdrk' => 'required',
            'keterangan' => 'required'
        ]);   
        
        date_default_timezone_set('Asia/Jakarta');             
        $str_format="0000";
        $tanggal=date('Y-m-d');
        $periode =substr($tanggal,0,4).substr($tanggal,5,2);
        $no_bukti = $this->generateKode("rra_app_m", "no_app", $kode_lokasi."-RRA".substr($periode,2,4).".", "0001");

        DB::connection($this->db)->beginTransaction();
        
        try {

            if ($request->input('status') == "APPROVE") {
                $vProg = "0";
            } else {
                $vProg = "D";	
                $del = DB::connection($this->db)->table('anggaran_d')->where('no_agg',$request->no_pdrk)->delete();
            }    
            
            $ins = DB::connection($this->db)->insert("insert into rra_app_m(no_app, kode_lokasi,tanggal,keterangan,modul,periode,no_del,nik_buat,nik_app,nik_user,tgl_input,jenis_form) values ('$no_bukti','$kode_lokasi',getdate(),'$request->keterangan','PUSAT','$periode','-','$nik','$nik','$nik',getdate(),'APPDIRUS')");
            
            $insdet = DB::connection($this->db)->insert("insert into rra_app_d(no_app,modul,kode_lokasi,no_bukti,kode_lokbukti,sts_pdrk,catatan,status) values ('$no_bukti','PUSAT','$kode_lokasi','$request->no_pdrk','$kode_lokasi','RRR','-','$request->status') ");
            
            //---------------- flag bukti		
            $upd = DB::connection($this->db)->table('rra_pdrk_m')
            ->where('no_pdrk', $request->no_pdrk)       
            ->where('kode_lokasi', $kode_lokasi)
            ->update(['progress' => $vProg]);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['id'] = $no_bukti;
            $success['message'] = "Data Approval berhasil disimpan";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['id']= "-";
            $success['message'] = "Data Approval gagal disimpan. Internal Server Error";
            return response()->json($success, $this->successStatus);
        }					
    }

    public function approvalBudget(Request $request)
    {
        
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $this->validate($request, [
            'status' => 'required|in:APPROVE,RETURN',
            'no_pdrk' => 'required',
            'keterangan' => 'required'
        ]);   
        
        date_default_timezone_set('Asia/Jakarta');             
        $str_format="0000";
        $tanggal=date('Y-m-d');
        $periode =substr($tanggal,0,4).substr($tanggal,5,2);
        $no_bukti = $this->generateKode("rra_app_m", "no_app", $kode_lokasi."-RRA".substr($periode,2,4).".", "0001");

        DB::connection($this->db)->beginTransaction();
        
        try {

            if ($request->input('status') == "APPROVE") {
                $vProg = "1";
            } else {
                $vProg = "X";	
                $del = DB::connection($this->db)->table('anggaran_d')->where('no_agg',$request->no_pdrk)->delete();
            }    
            
            $ins = DB::connection($this->db)->insert("insert into rra_app_m(no_app, kode_lokasi,tanggal,keterangan,modul,periode,no_del,nik_buat,nik_app,nik_user,tgl_input,jenis_form) values ('$no_bukti','$kode_lokasi',getdate(),'$request->keterangan','PUSAT','$periode','-','$nik','$nik','$nik',getdate(),'APPPST')");
            
            $insdet = DB::connection($this->db)->insert("insert into rra_app_d(no_app,modul,kode_lokasi,no_bukti,kode_lokbukti,sts_pdrk,catatan,status) values ('$no_bukti','PUSAT','$kode_lokasi','$request->no_pdrk','$kode_lokasi','RRR','-','$request->status') ");
            
            //---------------- flag bukti		
            $upd = DB::connection($this->db)->table('rra_pdrk_m')
            ->where('no_pdrk', $request->no_pdrk)       
            ->where('kode_lokasi', $kode_lokasi)
            ->update(['progress' => $vProg]);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['id'] = $no_bukti;
            $success['message'] = "Data Approval berhasil disimpan";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['id']= "-";
            $success['message'] = "Data Approval gagal disimpan. Internal Server Error";
            return response()->json($success, $this->successStatus);
        }													
    }

    public function approvalDir(Request $request)
    {
        
        if($data =  Auth::guard($this->guard)->user()){
            $nik= $data->nik;
            $kode_lokasi= $data->kode_lokasi;
        }

        $this->validate($request, [
            'status' => 'required|in:APPROVE,RETURN',
            'no_pdrk' => 'required',
            'keterangan' => 'required'
        ]);   
        
        date_default_timezone_set('Asia/Jakarta');             
        $str_format="0000";
        $tanggal=date('Y-m-d');
        $periode =substr($tanggal,0,4).substr($tanggal,5,2);
        $no_bukti = $this->generateKode("rra_app_m", "no_app", $kode_lokasi."-RRD".substr($periode,2,4).".", "0001");

        DB::connection($this->db)->beginTransaction();
        
        try {

            if ($request->input('status') == "APPROVE") {
                $vProg = "2";
                $ins = DB::connection($this->db)->insert("insert into anggaran_d (no_agg,kode_lokasi,no_urut,kode_pp,kode_akun,kode_drk,volume,periode,nilai_sat,nilai,dc,satuan,nik_user,tgl_input,modul)  		
                select a.no_pdrk,a.kode_lokasi,a.no_urut,a.kode_pp,a.kode_akun,a.kode_drk,1,a.periode,a.nilai,a.nilai,a.dc,'-',b.nik_user,getdate(),'RRA' 
                from rra_pdrk_d a inner join rra_pdrk_m b on a.no_pdrk=b.no_pdrk  
                where a.no_pdrk = '".$request->no_pdrk."' and a.dc ='D' ");
            } else if ($request->input('status') == "APPROVE") {
                $vProg = "Z";	
                $del = DB::connection($this->db)->table('anggaran_d')->where('no_agg',$request->no_pdrk)->delete();
            }    
            
            $ins = DB::connection($this->db)->insert("insert into rra_app_m(no_app, kode_lokasi,tanggal,keterangan,modul,periode,no_del,nik_buat,nik_app,nik_user,tgl_input,jenis_form) values ('$no_bukti','$kode_lokasi',getdate(),'$request->keterangan','PUSAT','$periode','-','$nik','$nik','$nik',getdate(),'APPDIR')");
            
            $insdet = DB::connection($this->db)->insert("insert into rra_app_d(no_app,modul,kode_lokasi,no_bukti,kode_lokbukti,sts_pdrk,catatan,status) values ('$no_bukti','PUSAT','$kode_lokasi','$request->no_pdrk','$kode_lokasi','RRD','-','$request->status') ");
            
            //---------------- flag bukti		
            $upd = DB::connection($this->db)->table('rra_pdrk_m')
            ->where('no_pdrk', $request->no_pdrk)       
            ->where('kode_lokasi', $kode_lokasi)
            ->update(['progress' => $vProg]);
            
            DB::connection($this->db)->commit();
            $success['status'] = true;
            $success['id'] = $no_bukti;
            $success['message'] = "Data Approval berhasil disimpan";
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            DB::connection($this->db)->rollback();
            Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['id']= "-";
            $success['message'] = "Data Approval gagal disimpan. Internal Server Error";
            return response()->json($success, $this->successStatus);
        }																	
    }

    public function getPeriodeAju(Request $request){

        try {
            
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $sql="select distinct periode from rra_pdrk_m order by periode desc";

            $aju = DB::connection($this->db)->select($sql);
            $aju = json_decode(json_encode($aju),true);
            
            if(count($aju) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $aju;
                $success['message'] = "Success!";
                
                return response()->json($success, $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data']= [];
                $success['status'] = true;
                
                return response()->json($success, $this->successStatus);
            }
        } catch (\Throwable $e) {
            Log::error('Error App Siaga: '.$e);
            $success['status'] = false;
            $success['data']= [];
            $success['message'] = "Internal Server Error";
            return response()->json($success, $this->successStatus);
        }
    }
}
