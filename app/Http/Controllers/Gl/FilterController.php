<?php

namespace App\Http\Controllers\Gl;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FilterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $successStatus = 200;

    function getGlFilterLokasi(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
          
            $sql="select kode_lokasi,nama from lokasi where flag_aktif='1' order by kode_lokasi ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlFilterPeriode(){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $sql="select distinct periode from trans_m where kode_lokasi='$kode_lokasi'";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
        
    }

    function getGlFilterModul(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
          
            $sql="select distinct modul from trans_m where kode_lokasi='$kode_lokasi'";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlFilterBukti(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            
            $sql="select a.no_bukti,a.keterangan from trans_m a where kode_lokasi='$kode_lokasi' ";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlFilterAkun(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
          
            $sql="select kode_akun,nama from masakun where kode_lokasi='$kode_lokasi'";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    function getGlFilterFs(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
          
            $sql="select kode_fs,nama from fs where kode_lokasi='$kode_lokasi'";
            $res = DB::connection('sqlsrv2')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ //mengecek apakah data kosong atau tidak
                $success['status'] = true;
                $success['data'] = $res;
                $success['message'] = "Success!";
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                $success['message'] = "Data Kosong!";
                $success['status'] = true;
                return response()->json(['success'=>$success], $this->successStatus);
            }
        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

}
