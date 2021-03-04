<?php

namespace App\Http\Controllers\Esaku\Keuangan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MasakunDetailController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';


    public function listAkunAktif() {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_akun,nama from masakun where block = '0' and kode_lokasi='".$kode_lokasi."'");						
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

    public function cariAkunAktif(Request $request) {
        $this->validate($request, [    
            'kode_akun' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_akun, kode_lokasi, nama, modul, jenis, kode_curr, block, status_gar, normal from masakun where block='0' and kode_akun='".$request->kode_akun."' and kode_lokasi='".$kode_lokasi."'");
            $res = json_decode(json_encode($res),true);
            
            $success['status'] = true;
            $success['data'] = $res;
            $success['message'] = "Success!";
            return response()->json(['success'=>$success], $this->successStatus);     
            
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }        
    }

    public function isUnik($isi,$kode_lokasi)
    {        
        $auth = DB::connection($this->sql)->select("select kode_akun from masakun where kode_akun ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_akun)){
                if($request->kode_akun == "all"){
                    $filter = "";
                }else{
                    $filter = " and kode_akun='".$request->kode_akun."' ";
                }
                $sql= "select kode_akun, kode_lokasi, nama, modul, jenis, kode_curr, block, status_gar, normal from masakun where kode_lokasi='".$kode_lokasi."' $filter ";
            }
            else {
                $sql = "select kode_akun, kode_lokasi, nama, modul, jenis, kode_curr, block, status_gar, normal,case when datediff(minute,tgl_input,getdate()) <= 10 then 'baru' else 'lama' end as status,tgl_input from masakun where kode_lokasi= '".$kode_lokasi."'";
            }

            $res = DB::connection($this->sql)->select($sql);
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
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function show(Request $request)
    {
        $this->validate($request,[
            'kode_akun' => 'required'
        ]);
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = " and kode_akun='".$request->kode_akun."' ";

            $sql= "select kode_akun, kode_lokasi, nama, modul, jenis, kode_curr, block, status_gar, normal from masakun where kode_lokasi='".$kode_lokasi."' $filter ";

            $res = DB::connection($this->sql)->select($sql);
            $res = json_decode(json_encode($res),true);

            $akun2 = DB::connection($this->sql)->select("select b.kode_flag,b.nama as nama_flag from flag_relasi a inner join flag_akun b on a.kode_flag=b.kode_flag where a.kode_akun = '".$request->kode_akun."' and a.kode_lokasi='".$kode_lokasi."'
            ");

            $akun2 = json_decode(json_encode($akun2),true);

            $akun3 = DB::connection($this->sql)->select("select b.kode_fs,b.nama as nama_fs,c.kode_neraca,c.nama as nama_neraca
            from relakun a 
            inner join fs b on a.kode_fs=b.kode_fs and a.kode_lokasi=b.kode_lokasi 
            inner join neraca c on a.kode_neraca=c.kode_neraca and a.kode_fs=c.kode_fs and a.kode_lokasi=c.kode_lokasi 
            where a.kode_akun = '".$request->kode_akun."' and a.kode_lokasi='".$kode_lokasi."'
            ");

            $akun3 = json_decode(json_encode($akun3),true);	
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res; 
                $success['detail_relasi'] = $akun2;
                $success['detail_keuangan'] = $akun3;
                $success['message'] = "Success!";     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['data'] = [];
                $success['detail_relasi'] = [];
                $success['detail_keuangan'] = [];
                $success['status'] = false;
            }
            return response()->json($success, $this->successStatus);
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_akun' => 'required|max:20',
            'nama' => 'required|max:100',
            'modul' => 'required',
            'jenis' => 'required',
            'kode_curr' => 'required',
            'block' => 'required',
            'status_gar' => 'required',
            'normal' => 'required',
            'kode_flag'  => 'array',
            'kode_fs'  => 'array',
            'kode_neraca'  => 'array'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_akun,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into masakun(kode_akun,nama,kode_lokasi,modul,jenis,kode_curr,block,status_gar,normal,tgl_input) values 
                ('".$request->kode_akun."','".$request->nama."','".$kode_lokasi."','".$request->modul."','".$request->jenis."','".$request->kode_curr."','".$request->block."','".$request->status_gar."','".$request->normal."',getdate())");

                $flag = $request->input('kode_flag');
                if(count($flag) > 0){
                    for($f=0;$f<count($flag);$f++){
                        $tmp = explode(" - ", $request->kode_flag[$f]);
                        $ins2 = DB::connection($this->sql)->insert("insert into flag_relasi(kode_akun,kode_lokasi,kode_flag) values ('".$request->kode_akun."','".$kode_lokasi."','".$tmp[0]."' ) ");
                    }
                }

                $keu = $request->input('kode_neraca');

                if(count($keu) > 0){
                    for($k=0;$k<count($keu);$k++){
                        $tmp = explode(" - ", $request->kode_fs[$k]);
                        $tmp2 = explode(" - ", $request->kode_neraca[$k]);
                        $ins3 = DB::connection($this->sql)->insert("insert into relakun (kode_neraca,kode_fs,kode_akun,kode_lokasi) values ('".$tmp2[0]."','".$tmp[0]."','".$request->kode_akun."','$kode_lokasi') ");
                    }
                }
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Akun berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Akun sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akun gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_akun' => 'required|max:20',
            'nama' => 'required|max:100',
            'modul' => 'required',
            'jenis' => 'required',
            'kode_curr' => 'required',
            'block' => 'required',
            'status_gar' => 'required',
            'normal' => 'required',
            'kode_flag'  => 'array',
            'kode_fs'  => 'array',
            'kode_neraca'  => 'array'        
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('masakun')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_akun', $request->kode_akun)
            ->delete();

            $del2 = DB::connection($this->sql)->table('relakun')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_akun', $request->kode_akun)
            ->delete();

            $del3 = DB::connection($this->sql)->table('flag_relasi')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_akun', $request->kode_akun)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into masakun(kode_akun,nama,kode_lokasi,modul,jenis,kode_curr,block,status_gar,normal,tgl_input) values 
            ('".$request->kode_akun."','".$request->nama."','".$kode_lokasi."','".$request->modul."','".$request->jenis."','".$request->kode_curr."','".$request->block."','".$request->status_gar."','".$request->normal."',getdate())");

            $flag = $request->input('kode_flag');
            if(count($flag) > 0){
                for($f=0;$f<count($flag);$f++){
                    $tmp = explode(" - ", $request->kode_flag[$f]);
                    $ins2 = DB::connection($this->sql)->insert("insert into flag_relasi(kode_akun,kode_lokasi,kode_flag) values ('".$request->kode_akun."','".$kode_lokasi."','".$tmp[0]."' ) ");
                }
            }
            
            $keu = $request->input('kode_neraca');
            
            if(count($keu) > 0){
                for($k=0;$k<count($keu);$k++){
                    $tmp = explode(" - ", $request->kode_fs[$k]);
                    $tmp2 = explode(" - ", $request->kode_neraca[$k]);
                    $ins3 = DB::connection($this->sql)->insert("insert into relakun (kode_neraca,kode_fs,kode_akun,kode_lokasi) values ('".$tmp2[0]."','".$tmp[0]."','".$request->kode_akun."','$kode_lokasi') ");
                }
            }

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Akun berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akun gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_akun' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('masakun')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_akun', $request->kode_akun)
            ->delete();

            $del2 = DB::connection($this->sql)->table('relakun')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_akun', $request->kode_akun)
            ->delete();

            $del3 = DB::connection($this->sql)->table('flag_relasi')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_akun', $request->kode_akun)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Akun berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Akun gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }


    public function getFlagAkun(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_flag) && $request->kode_flag != ""){
                $filter = " where kode_flag='$request->kode_flag' ";
            }else{
                $filter = "";
            }

            $modul = DB::connection($this->sql)->select("select kode_flag, nama from flag_akun $filter
            ");
            $modul = json_decode(json_encode($modul),true);
            
            if(count($modul) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $modul;
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
    
    public function getNeraca(Request $request)
    {
        // $this->validate($request,[
        //     'kode_fs' => 'required'
        // ]);

        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $filter = "";
            if(isset($request->kode_neraca) && $request->kode_neraca != ""){
                $filter .= " and kode_neraca='$request->kode_neraca' ";
            }else{
                $filter .= "";
            }

            if(isset($request->kode_fs) && $request->kode_fs != ""){
                $filter .= " and kode_fs='$request->kode_fs' ";
            }else{
                $filter .= "";
            }

            $res = DB::connection($this->sql)->select("select distinct kode_neraca, nama from neraca where tipe = 'posting' and kode_lokasi='".$kode_lokasi."'
            $filter
            ");
            $res = json_decode(json_encode($res),true);
            
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
