<?php

namespace App\Http\Controllers\Ypt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FSController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'sqlsrvyptkug';
    public $guard = 'yptkug';


    public function listFSAktif() {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_fs, nama from fs where flag_status='1' and kode_lokasi='".$kode_lokasi."'");						
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

    public function cariFSAktif(Request $request) {
        $this->validate($request, [    
            'kode_fs' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_fs, nama from fs where flag_status='1' and kode_fs='".$request->kode_fs."' and kode_lokasi='".$kode_lokasi."'");
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
        $auth = DB::connection($this->sql)->select("select kode_fs from fs where kode_fs ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            if(isset($request->kode_fs)){
                if($request->kode_fs == "all"){
                    $filter = "";
                }else{
                    $filter = " and kode_fs='".$request->kode_fs."' ";
                }
                $sql= "select kode_fs, nama, flag_status from fs where kode_lokasi='".$kode_lokasi."' $filter ";
            }
            else {
                $sql = "select kode_fs, nama, flag_status from fs where kode_lokasi= '".$kode_lokasi."'";
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
            'kode_fs' => 'required|max:10',
            'nama' => 'required|max:100',
            'flag_status' => 'required'            
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_fs,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into fs(kode_fs,nama,kode_lokasi,flag_status,tgl_input) values 
                                                         ('".$request->kode_fs."','".$request->nama."','".$kode_lokasi."','".$request->flag_status."',getdate())");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data FS berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode FS sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data FS gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_fs' => 'required|max:10',
            'nama' => 'required|max:100',
            'flag_status' => 'required'                       
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('fs')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_fs', $request->kode_fs)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into fs(kode_fs,nama,kode_lokasi,flag_status,tgl_input) values 
                                                      ('".$request->kode_fs."','".$request->nama."','".$kode_lokasi."','".$request->flag_status."',getdate())");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data FS berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data FS gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_fs' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('fs')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_fs', $request->kode_fs)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data FS berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data FS gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    
}
