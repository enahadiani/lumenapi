<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class StsEduController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';


    public function cariStsEdu(Request $request) {
        $this->validate($request, [    
            'sts_edu' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select sts_edu, nama from hr_stsedu where sts_edu='".$request->sts_edu."'");
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

    public function isUnik($isi)
    {        
        $auth = DB::connection($this->sql)->select("select sts_edu from hr_stsedu where sts_edu ='".$isi."'");
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

            if(isset($request->sts_edu)){
                if($request->sts_edu == "all"){
                    $filter = "";
                }else{
                    $filter = " where sts_edu='".$request->sts_edu."' ";
                }
                $sql= "select sts_edu, nama from hr_stsedu ".$filter;
            } 
            else {
                $sql = "select sts_edu, nama from hr_stsedu ";
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
            'sts_edu' => 'required|max:10',
            'nama' => 'required|max:50',               
            'sts_aktif' => 'required|max:1'          
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->sts_edu)){

                $ins = DB::connection($this->sql)->insert("insert into hr_stsedu(sts_edu,nama,sts_aktif,tgl_input,nik_user) values 
                                                         ('".$request->sts_edu."','".$request->nama."','".$request->sts_aktif."',getdate(),'".$nik."')");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Status Pendidikan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Status Pendidikan sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Status Pendidikan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'sts_edu' => 'required|max:10',
            'nama' => 'required|max:50',    
            'sts_aktif' => 'required|max:1'  
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_stsedu')
            ->where('sts_edu', $request->sts_edu)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into hr_stsedu(sts_edu,nama,sts_aktif,tgl_input,nik_user) values 
                                                     ('".$request->sts_edu."','".$request->nama."','".$request->sts_aktif."',getdate(),'".$nik."')");
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Status Pendidikan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Status Pendidikan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'sts_edu' => 'required|max:10'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_stsedu')            
            ->where('sts_edu', $request->sts_edu)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Status Pendidikan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Status Pendidikan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
