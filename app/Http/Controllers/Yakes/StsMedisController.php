<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class StsMedisController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';


    public function cariStsMedis(Request $request) {
        $this->validate($request, [    
            'sts_medis' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select sts_medis, nama from hr_stsmedis where sts_medis='".$request->sts_medis."'");
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
        $auth = DB::connection($this->sql)->select("select sts_medis from hr_stsmedis where sts_medis ='".$isi."'");
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

            if(isset($request->sts_medis)){
                if($request->sts_medis == "all"){
                    $filter = "";
                }else{
                    $filter = " where sts_medis='".$request->sts_medis."' ";
                }
                $sql= "select sts_medis, nama from hr_stsmedis ".$filter;
            } 
            else {
                $sql = "select sts_medis, nama from hr_stsmedis ";
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
            'sts_medis' => 'required|max:10',
            'nama' => 'required|max:50',   
            'jenis' => 'required|max:50',   
            'sts_aktif' => 'required|max:1'          
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->sts_medis)){

                $ins = DB::connection($this->sql)->insert("insert into hr_stsmedis(sts_medis,nama,jenis,sts_aktif,tgl_input,nik_user) values 
                                                         ('".$request->sts_medis."','".$request->nama."','".$request->jenis."','".$request->sts_aktif."',getdate(),'".$nik."')");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Status Medis berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Status Medis sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Status Medis gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'sts_medis' => 'required|max:10',
            'nama' => 'required|max:50',    
            'jenis' => 'required|max:50',        
            'sts_aktif' => 'required|max:1'  
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_stsmedis')
            ->where('sts_medis', $request->sts_medis)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into hr_stsmedis(sts_medis,nama,jenis,sts_aktif,tgl_input,nik_user) values 
                                                     ('".$request->sts_medis."','".$request->nama."','".$request->jenis."','".$request->sts_aktif."',getdate(),'".$nik."')");
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Status Medis berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Status Medis gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'sts_medis' => 'required|max:10'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_stsmedis')            
            ->where('sts_medis', $request->sts_medis)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Status Medis berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Status Medis gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
