<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Menu;

class ReportController extends Controller
{
    public $successStatus = 200;

    public function getLokasi(){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
            $res = DB::connection('sqlsrvyptkug')->select("select a.kode_lokasi,a.nama
            from lokasi a
            where a.kode_lokasi='$kode_lokasi' 
            order by a.kode_lokasi            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getAkun(){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
            $res = DB::connection('sqlsrvyptkug')->select("select a.kode_akun,a.nama
            from masakun a
            where a.kode_lokasi='$kode_lokasi' 
            order by a.kode_akun            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }

    public function getPp(Request $request){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
            if ($request->input('kode_pp') != "") {
                $kode_pp = $request->input('kode_pp');                
                $filterkode_pp = " and a.kode_pp='$kode_pp' ";
            
            }else{
                $filterkode_pp = "";
            }

            $res = DB::connection('sqlsrvyptkug')->select("select a.kode_pp,a.nama
            from pp a
            where a.kode_lokasi='$kode_lokasi' and a.flag_aktif='1' $filterkode_pp
            order by a.kode_pp            ");
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    public function getDrk(Request $request){
        try {
            if($data =  Auth::guard('yptkug')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }else{
                $nik= '';
                $kode_lokasi= '';
            }
            
            $tahun=$request->input('tahun');
            $filter="";

            if ($request->input('kode_drk') != "") {
                $kode_drk = $request->input('kode_drk');                
                $filter .= " and a.kode_drk='$kode_drk' ";
                
            }else{
                $filter .= "";
            }
            
            $sql="select a.kode_drk,a.nama
            from drk a
            where a.kode_lokasi='$kode_lokasi' and a.tahun='$tahun' $filter
             order by a.kode_drk ";
            $res = DB::connection('sqlsrvyptkug')->select($sql);
            $res = json_decode(json_encode($res),true);
            
            if(count($res) > 0){ 
                //$success['sql'] = $sql;
                $success['data'] = $res;
                $success['status'] = true;
                $success['message'] = "Success!";
                
                return response()->json(['success'=>$success], $this->successStatus);     
            }
            else{
                
                $success['data'] = [];
                $success['status'] = false;
                $success['message'] = "Data Kosong!";
                
                return response()->json(['success'=>$success], $this->successStatus);
            }

        } catch (\Throwable $e) {
            $success['status'] = false;
            $success['message'] = "Error ".$e;
            return response()->json($success, $this->successStatus);
        }
    }


    
    
}
