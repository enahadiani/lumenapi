<?php

namespace App\Http\Controllers\Wisata;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SubjenisController extends Controller
{
    
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function isUnik($isi,$kode_lokasi){
        
        $auth = DB::connection($this->sql)->select("select kode_subjenis from par_subjenis where kode_subjenis ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
        $auth = json_decode(json_encode($auth),true);
        if(count($auth) > 0){
            return false;
        }else{
            return true;
        }
    }

    public function getJenis() {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_jenis,nama from par_jenis where kode_lokasi='".$kode_lokasi."'");						
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

    public function index(Request $request)
    {
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            if(isset($request->kode_subjenis)){
                if($request->kode_subjenis == "all"){
                    $filter = "";
                }else{
                    $filter = " and a.kode_subjenis='".$request->kode_subjenis."' ";
                }
                $sql= "select a.kode_subjenis,a.nama,a.kode_jenis,b.nama as nama_jenis from par_subjenis a inner join par_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi='".$kode_lokasi."' $filter ";
            }
            else {
                $sql = "select a.kode_subjenis,a.nama,a.kode_jenis,b.nama as nama_jenis from par_subjenis a inner join par_jenis b on a.kode_jenis=b.kode_jenis and a.kode_lokasi=b.kode_lokasi where a.kode_lokasi= '".$kode_lokasi."'";
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
            'kode_subjenis' => 'required|max:10',
            'nama' => 'required|max:100',
            'kode_jenis' => 'required'
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_jenis,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into par_subjenis(kode_subjenis,nama,kode_lokasi,kode_jenis) values ('".$request->kode_subjenis."','".$request->nama."','".$kode_lokasi."','".$request->kode_jenis."')");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Sub Jenis berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Sub Jenis sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_subjenis' => 'required|max:10',
            'nama' => 'required|max:100' ,
            'kode_jenis' => 'required'          
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('par_subjenis')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_jenis', $request->kode_jenis)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into par_subjenis(kode_subjenis,nama,kode_lokasi,kode_jenis) values ('".$request->kode_subjenis."','".$request->nama."','".$kode_lokasi."','".$request->kode_jenis."')");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Jenis berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Jenis gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_subjenis' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('par_subjenis')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_subjenis', $request->kode_subjenis)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Sub Jenis berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Sub Jenis gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    
}
