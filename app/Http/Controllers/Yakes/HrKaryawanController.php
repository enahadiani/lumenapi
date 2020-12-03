<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HrKaryawanController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';


    public function cariNik(Request $request) {
        $this->validate($request, [    
            'nik' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select nik, nama from hr_karyawan where nik='".$request->nik."'");
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
        $auth = DB::connection($this->sql)->select("select nik from hr_karyawan where nik ='".$isi."'");
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

            if(isset($request->nik)){
                if($request->nik == "all"){
                    $filter = "";
                }else{
                    $filter = " where nik='".$request->nik."' ";
                }
                $sql= "select nik, nama from hr_karyawan ".$filter;
            } 
            else {
                $sql = "select nik, nama from hr_karyawan ";
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
            'nik' => 'required|max:10',
            'nama' => 'required|max:50',    
            'tgl_lahir' => 'required',
            'gender' => 'required|max:1',   
            'sts_organik' => 'required|max:10',          
            'sts_medis' => 'required|max:10',          
            'sts_edu' => 'required|max:10',                      
            'sts_aktif' => 'required|max:1',           
            'kode_pp' => 'required|max:10'           
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->nik)){

                $ins = DB::connection($this->sql)->insert("insert into hr_karyawan(nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,tgl_input,nik_user) values 
                                                         ('".$request->nik."','".$request->nama."','".$request->tgl_lahir."','".$request->gender."','".$request->sts_organik."','".$request->sts_medis."','".$request->sts_edu."','".$request->sts_aktif."','".$request->kode_pp."',getdate(),'".$nik."')");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Karyawan berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Karyawan sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required|max:10',
            'nama' => 'required|max:50',    
            'tgl_lahir' => 'required',
            'gender' => 'required|max:1',   
            'sts_organik' => 'required|max:10',          
            'sts_medis' => 'required|max:10',          
            'sts_edu' => 'required|max:10',                      
            'sts_aktif' => 'required|max:1',           
            'kode_pp' => 'required|max:10'           
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_karyawan')
            ->where('nik', $request->nik)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into hr_karyawan(nik,nama,tgl_lahir,gender,sts_organik,sts_medis,sts_edu,sts_aktif,kode_pp,tgl_input,nik_user) values 
                                                      ('".$request->nik."','".$request->nama."','".$request->tgl_lahir."','".$request->gender."','".$request->sts_organik."','".$request->sts_medis."','".$request->sts_edu."','".$request->sts_aktif."','".$request->kode_pp."',getdate(),'".$nik."')");
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'nik' => 'required|max:10'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_karyawan')            
            ->where('nik', $request->nik)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Karyawan berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Karyawan gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
