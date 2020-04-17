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

    function getPeriode(){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_lokasi = "11";

            $res = DB::connection('sqlsrv2')->select("select distinct periode from dgw_reg where kode_lokasi='$kode_lokasi'
            ");
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

    function getPaket(Request $request){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_lokasi = "11";
            if($request->input('periode') == ""){
                $filter = "";
            }else{
                $filter = " and a.periode='".$request->input('periode')."' ";
            }

            $res = DB::connection('sqlsrv2')->select("select a.no_paket,b.nama 
            from dgw_reg a 
            inner join dgw_paket b on a.no_paket=b.no_paket and a.kode_lokasi = b.kode_lokasi 
            where kode_lokasi='$kode_lokasi' $filter
            ");
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

    function getJadwal(){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_lokasi = "11";
            if($request->input('periode') == ""){
                $filter = "";
            }else{
                $filter = " and a.periode='".$request->input('periode')."' ";
            }

            if($request->input('no_paket') == ""){
                $filter .= "";
            }else{
                $filter .= " and a.no_paket='".$request->input('no_paket')."' ";
            }

            $res = DB::connection('sqlsrv2')->select("select a.no_jadwal,b.tgl_berangkat 
            from dgw_reg a 
            inner join dgw_jadwal b on a.no_jadwal=b.no_jadwal and a.kode_lokasi=b.kode_lokasi and a.no_paket=b.no_paket 
            where a.kode_lokasi='$kode_lokasi' $filter");
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

    function getNoReg(){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_lokasi = "11";
            if($request->input('periode') == ""){
                $filter = "";
            }else{
                $filter = " and a.periode='".$request->input('periode')."' ";
            }

            if($request->input('no_paket') == ""){
                $filter .= "";
            }else{
                $filter .= " and a.no_paket='".$request->input('no_paket')."' ";
            }
            
            if($request->input('jadwal') == ""){
                $filter .= "";
            }else{
                $filter .= " and a.no_jadwal='".$request->input('jadwal')."' ";
            }

            $res = DB::connection('sqlsrv2')->select("select a.no_reg from dgw_reg a where a.kode_lokasi='$kode_lokasi' $filter");
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

    function getPeserta(){
        try {
            
            if($data =  Auth::guard('admin')->user()){
                $nik= $data->nik;
                $kode_lokasi= $data->kode_lokasi;
            }
            $kode_lokasi = "11";
            if($request->input('periode') == ""){
                $filter = "";
            }else{
                $filter = " and a.periode='".$request->input('periode')."' ";
            }

            if($request->input('no_paket') == ""){
                $filter .= "";
            }else{
                $filter .= " and a.no_paket='".$request->input('no_paket')."' ";
            }
            
            if($request->input('jadwal') == ""){
                $filter .= "";
            }else{
                $filter .= " and a.no_jadwal='".$request->input('jadwal')."' ";
            }

            if($request->input('no_reg') == ""){
                $filter .= "";
            }else{
                $filter .= " and a.no_reg='".$request->input('no_reg')."' ";
            }

            $res = DB::connection('sqlsrv2')->select("select a.no_peserta,b.nama 
            from dgw_reg a 
            inner join dgw_peserta b on a.no_peserta=b.no_peserta and a.kode_lokasi=b.kode_lokasi 
            where a.kode_lokasi='$kode_lokasi' $filter");
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
