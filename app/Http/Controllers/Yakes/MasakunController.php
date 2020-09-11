<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MasakunController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';

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
                $sql = "select kode_akun, kode_lokasi, nama, modul, jenis, kode_curr, block, status_gar, normal from masakun where kode_lokasi= '".$kode_lokasi."'";
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

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_akun' => 'required|max:20',
            'nama' => 'required|max:100'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_akun,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into masakun(kode_akun,nama,kode_lokasi,modul,jenis,kode_curr,block,status_gar,normal) values 
                                                         ('".$request->kode_akun."','".$request->nama."','".$kode_lokasi."','".$request->modul."','".$request->jenis."','".$request->kode_curr."','".$request->block."','".$request->status_gar."','".$request->normal."')");
                
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
            'nama' => 'required|max:100'            
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

            $ins = DB::connection($this->sql)->insert("insert into masakun(kode_akun,nama,kode_lokasi,modul,jenis,kode_curr,block,status_gar,normal) values 
                                                      ('".$request->kode_akun."','".$request->nama."','".$kode_lokasi."','".$request->modul."','".$request->jenis."','".$request->kode_curr."','".$request->block."','".$request->status_gar."','".$request->normal."')");

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

    
}
