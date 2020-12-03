<?php

namespace App\Http\Controllers\Yakes;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DemogController extends Controller
{    
    public $successStatus = 200;
    public $sql = 'dbsapkug';
    public $guard = 'yakes';


    public function cariDemog(Request $request) {
        $this->validate($request, [    
            'kode_demog' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_demog, nama from hr_demog where kode_demog='".$request->kode_demog."'");
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
        $auth = DB::connection($this->sql)->select("select kode_demog from hr_demog where kode_demog ='".$isi."'");
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

            if(isset($request->kode_demog)){
                if($request->kode_demog == "all"){
                    $filter = "";
                }else{
                    $filter = " where kode_demog='".$request->kode_demog."' ";
                }
                $sql= "select kode_demog, nama from hr_demog ".$filter;
            } 
            else {
                $sql = "select kode_demog, nama from hr_demog ";
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
            'kode_demog' => 'required|max:10',
            'nama' => 'required|max:50',           
            'bawah' => 'required|numeric',
            'atas' => 'required|numeric'          
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_demog)){

                $ins = DB::connection($this->sql)->insert("insert into hr_demog(kode_demog,nama,bawah,atas,tgl_input,nik_user) values 
                                                         ('".$request->kode_demog."','".$request->nama."','".floatval($request->bawah)."','".floatval($request->atas)."',getdate(),'".$nik."')");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Demografi berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Data Demografi sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Demografi gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_demog' => 'required|max:10',
            'nama' => 'required|max:50',           
            'bawah' => 'required|numeric',
            'atas' => 'required|numeric'          
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_demog')
            ->where('kode_demog', $request->kode_demog)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into hr_demog(kode_demog,nama,bawah,atas,tgl_input,nik_user) values 
                                                         ('".$request->kode_demog."','".$request->nama."','".floatval($request->bawah)."','".floatval($request->atas)."',getdate(),'".$nik."')");
                
            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Demografi berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Demografi gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_demog' => 'required|max:10'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('hr_demog')            
            ->where('kode_demog', $request->kode_demog)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Demografi berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Demografi gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

}
