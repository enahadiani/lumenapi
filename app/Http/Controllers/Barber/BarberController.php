<?php

namespace App\Http\Controllers\Barber;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class BarberController extends Controller
{
    public $successStatus = 200;
    public $sql = 'tokoaws';
    public $guard = 'toko';

    public function listBarberAktif() {
        try {            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_barber,nama from bar_barber where flag_aktif = '1' and kode_lokasi='".$kode_lokasi."'");						
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

    public function cariBarberAktif(Request $request) {
        $this->validate($request, [    
            'kode_barber' => 'required'            
        ]);
        
        try {
            
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }

            $res = DB::connection($this->sql)->select("select kode_barber,nama from bar_barber where flag_aktif='1' and kode_barber='".$request->kode_barber."' and kode_lokasi='".$kode_lokasi."'");
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

    public function isUnik($isi,$kode_lokasi){        
        $auth = DB::connection($this->sql)->select("select kode_barber from bar_barber where kode_barber ='".$isi."' and kode_lokasi='".$kode_lokasi."' ");
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

            if(isset($request->kode_barber)){
                if($request->kode_barber == "all"){
                    $filter = "";
                }else{
                    $filter = " and kode_barber='".$request->kode_barber."' ";
                }
                $sql= "select kode_barber,nama,nama,alamat,no_hp,flag_aktif from bar_barber where kode_lokasi='".$kode_lokasi."' $filter ";
            }
            else {
                $sql = "select kode_barber,nama,nama,alamat,no_hp,flag_aktif from bar_barber where kode_lokasi= '".$kode_lokasi."'";
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
            'kode_barber' => 'required|max:10',
            'nama' => 'required|max:100',
            'alamat' => 'required',
            'no_hp' => 'required',
            'flag_aktif' => 'required',
            'nik_user' => 'required'    
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            if($this->isUnik($request->kode_barber,$kode_lokasi)){

                $ins = DB::connection($this->sql)->insert("insert into bar_barber(kode_barber,nama,kode_lokasi,alamat,no_hp,flag_aktif,nik_user,tgl_input) values ('".$request->kode_barber."','".$request->nama."','".$kode_lokasi."','".$request->alamat."','".$request->no_hp."','".$request->flag_aktif."','".$request->nik_user."',getdate())");
                
                DB::connection($this->sql)->commit();
                $success['status'] = true;
                $success['message'] = "Data Barber berhasil disimpan";
            }else{
                $success['status'] = false;
                $success['message'] = "Error : Duplicate entry. Kode Barber sudah ada di database!";
            }
            
            return response()->json($success, $this->successStatus);     
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Barber gagal disimpan ".$e;
            return response()->json($success, $this->successStatus); 
        }				
        
        
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'kode_barber' => 'required|max:10',
            'nama' => 'required|max:100',
            'alamat' => 'required',
            'no_hp' => 'reqiured',
            'flag_aktif' => 'required',
            'nik_user' => 'required'    
        ]);

        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('bar_barber')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_barber', $request->kode_barber)
            ->delete();

            $ins = DB::connection($this->sql)->insert("insert into bar_barber(kode_barber,nama,kode_lokasi,alamat,no_hp,flag_aktif,nik_user,tgl_input) values ('".$request->kode_barber."','".$request->nama."','".$kode_lokasi."','".$request->alamat."','".$request->no_hp."','".$request->flag_aktif."','".$request->nik_user."',getdate())");

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Barber berhasil diubah";
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Barber gagal diubah ".$e;
            return response()->json($success, $this->successStatus); 
        }	
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'kode_barber' => 'required'
        ]);
        DB::connection($this->sql)->beginTransaction();
        
        try {
            if($data =  Auth::guard($this->guard)->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $del = DB::connection($this->sql)->table('bar_barber')
            ->where('kode_lokasi', $kode_lokasi)
            ->where('kode_barber', $request->kode_barber)
            ->delete();

            DB::connection($this->sql)->commit();
            $success['status'] = true;
            $success['message'] = "Data Barber berhasil dihapus";
            
            return response()->json($success, $this->successStatus); 
        } catch (\Throwable $e) {
            DB::connection($this->sql)->rollback();
            $success['status'] = false;
            $success['message'] = "Data Barber gagal dihapus ".$e;
            
            return response()->json($success, $this->successStatus); 
        }	
    }

    
}
